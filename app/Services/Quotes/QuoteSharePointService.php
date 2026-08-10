<?php

namespace App\Services\Quotes;

use App\Models\QuoteDelivery;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Uploads the generated quote PDF to SharePoint via Microsoft Graph.
 *
 */
class QuoteSharePointService
{
    private const TOKEN_CACHE_KEY = 'sharepoint_graph_token';
    private const GRAPH_BASE      = 'https://graph.microsoft.com/v1.0';

    /**
     * Upload the delivery PDF to SharePoint and return the web URL.
     *
     * @throws \RuntimeException on unrecoverable error.
     */
    public function upload(QuoteDelivery $delivery): string
    {
        // ── Already uploaded ──────────────────────────────────────────────────
        if ($delivery->sharepoint_url) {
            Log::info('quote_delivery.sharepoint_reused', [
                'delivery_id'    => $delivery->id,
                'sharepoint_url' => $delivery->sharepoint_url,
            ]);

            return $delivery->sharepoint_url;
        }

        // ── Prerequisites ─────────────────────────────────────────────────────
        if (! $delivery->pdf_path) {
            throw new \RuntimeException(
                "Delivery #{$delivery->id} has no PDF path — cannot upload to SharePoint."
            );
        }

        $token    = $this->getAccessToken();
        $driveId  = config('services.sharepoint.drive_id');
        $quote    = $delivery->quote;

        // ── Build folder path ─────────────────────────────────────────────────
        $clientFolder = $this->buildClientFolderName(
            clientName: $quote->client_name ?? 'Unknown Client',
            website:    $quote->website,
        );

        // Full path: "Acme Corp (acme.com.au)/Quotes"
        $quotesFolder = "{$clientFolder}/Quotes";

        // ── Ensure folders exist ──────────────────────────────────────────────
        $this->ensureFolder($token, $driveId, $clientFolder);
        $this->ensureFolder($token, $driveId, $quotesFolder);

        $filename     = $delivery->pdf_filename;
        $fullItemPath = "{$quotesFolder}/{$filename}";

        $existingUrl = $this->findExistingFile($token, $driveId, $fullItemPath);

        if ($existingUrl) {
            Log::info('quote_delivery.sharepoint_file_exists', [
                'delivery_id' => $delivery->id,
                'path'        => $fullItemPath,
                'url'         => $existingUrl,
            ]);

            $delivery->update(['sharepoint_url' => $existingUrl]);

            return $existingUrl;
        }

        // ── Read PDF ──────────────────────────────────────────────────────────
        $pdfContent = Storage::disk($delivery->pdf_disk ?? 'local')
            ->get($delivery->pdf_path);

        if (! $pdfContent) {
            throw new \RuntimeException(
                "Could not read PDF from disk for delivery #{$delivery->id}."
            );
        }

        // ── Upload ────────────────────────────────────────────────────────────
        $sizeBytes = strlen($pdfContent);

        if ($sizeBytes > 4 * 1024 * 1024) {
            return $this->resumableUpload($token, $driveId, $fullItemPath, $pdfContent, $delivery);
        }

        return $this->simpleUpload($token, $driveId, $fullItemPath, $pdfContent, $delivery);
    }

    // -------------------------------------------------------------------------
    // Folder management
    // -------------------------------------------------------------------------

    /**
     * Ensure a folder exists at the given path, creating it if needed.
     *
     */
    private function ensureFolder(string $token, string $driveId, string $folderPath): void
    {
        $parts = explode('/', trim($folderPath, '/'));

        $parentPath = null; // null = drive root

        foreach ($parts as $folderName) {
            $this->ensureSingleFolder($token, $driveId, $parentPath, $folderName);

            $parentPath = $parentPath
                ? "{$parentPath}/{$folderName}"
                : $folderName;
        }
    }

    /**
     * Create a single folder inside a parent, or confirm it already exists.
     *
     * @param  string|null  $parentPath  null = drive root, otherwise relative path
     */
    private function ensureSingleFolder(
        string  $token,
        string  $driveId,
        ?string $parentPath,
        string  $folderName,
    ): void {
        // Choose the correct children endpoint based on whether we have a parent.
        $endpoint = $parentPath
            ? self::GRAPH_BASE . "/drives/{$driveId}/root:/{$parentPath}:/children"
            : self::GRAPH_BASE . "/drives/{$driveId}/root/children";

        $response = Http::withToken($token)
            ->post($endpoint, [
                'name'                        => $folderName,
                'folder'                      => new \stdClass(), // required: signals folder type
                '@microsoft.graph.conflictBehavior' => 'fail',   // 409 = already exists = OK
            ]);

        // 201 Created — folder was just created.
        // 409 Conflict — folder already exists — both are success for us.
        if ($response->status() === 201 || $response->status() === 409) {
            return;
        }

        throw new \RuntimeException(
            "Could not create SharePoint folder '{$folderName}' — " .
            "HTTP {$response->status()}: " . $this->extractGraphError($response)
        );
    }
    /**
     * Simple upload for files under 4 MB.
     */
    private function simpleUpload(
        string        $token,
        string        $driveId,
        string        $itemPath,
        string        $content,
        QuoteDelivery $delivery,
    ): string {
        $response = Http::withToken($token)
            ->withHeaders(['Content-Type' => 'application/pdf'])
            ->withBody($content, 'application/pdf')
            ->put(self::GRAPH_BASE . "/drives/{$driveId}/root:/{$itemPath}:/content");

        if ($response->failed()) {
            throw new \RuntimeException(
                "Graph simple upload failed — HTTP {$response->status()}: " .
                $this->extractGraphError($response)
            );
        }

        return $this->persistResult($response->json(), $delivery);
    }

    /**
     * Resumable upload for files over 4 MB.
     */
    private function resumableUpload(
        string        $token,
        string        $driveId,
        string        $itemPath,
        string        $content,
        QuoteDelivery $delivery,
    ): string {
        // Create upload session.
        $sessionResponse = Http::withToken($token)
            ->post(
                self::GRAPH_BASE . "/drives/{$driveId}/root:/{$itemPath}:/createUploadSession",
                [
                    'item' => [
                        '@microsoft.graph.conflictBehavior' => 'replace',
                        'name' => basename($itemPath),
                    ],
                ]
            );

        if ($sessionResponse->failed()) {
            throw new \RuntimeException(
                "Graph upload session creation failed — HTTP {$sessionResponse->status()}: " .
                $this->extractGraphError($sessionResponse)
            );
        }

        $uploadUrl = $sessionResponse->json('uploadUrl');
        $size      = strlen($content);

        // Upload in one chunk (session URLs are pre-authenticated).
        $uploadResponse = Http::withHeaders([
            'Content-Length' => $size,
            'Content-Range'  => "bytes 0-" . ($size - 1) . "/{$size}",
            'Content-Type'   => 'application/pdf',
        ])->withBody($content, 'application/pdf')
            ->put($uploadUrl);

        if ($uploadResponse->failed()) {
            throw new \RuntimeException(
                "Graph resumable upload failed — HTTP {$uploadResponse->status()}: " .
                $this->extractGraphError($uploadResponse)
            );
        }

        return $this->persistResult($uploadResponse->json(), $delivery);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Build the client folder name from the quote's client_name and website.
     * both the client name and domain to avoid Graph 400 errors.
     */
    public function buildClientFolderName(string $clientName, ?string $website): string
    {
        $cleanClient = $this->sanitiseFolderName($clientName);

        if (empty($website)) {
            return $cleanClient;
        }

        $domain = $this->cleanDomain($website);

        if (empty($domain)) {
            return $cleanClient;
        }

        return "{$cleanClient} ({$domain})";
    }

    /**
     * Clean a URL or domain string to a bare hostname without www.
     */
    private function cleanDomain(string $website): string
    {
        $website = trim($website);

        // Add scheme if missing so parse_url works reliably.
        if (! str_contains($website, '://')) {
            $website = 'https://' . $website;
        }

        $host = parse_url($website, PHP_URL_HOST) ?? $website;

        // Strip www. prefix.
        $host = preg_replace('/^www\./i', '', $host);

        // Strip port if present.
        $host = strtok($host, ':');

        return $this->sanitiseFolderName($host);
    }

    /**
     * Remove characters that are illegal in SharePoint folder names.
     * Illegal: ~ " # % & * : < > ? / \ { | }
     * Also collapses multiple spaces and trims.
     */
    private function sanitiseFolderName(string $name): string
    {
        // Replace illegal SharePoint name characters with nothing.
        $clean = preg_replace('/[~"#%&*:<>?\/\\\\{|}]/', '', $name);

        // Collapse multiple spaces.
        $clean = preg_replace('/\s+/', ' ', $clean);

        return trim($clean);
    }

    /**
     * Check whether a file already exists at the given path.
     * Returns the webUrl if found, null if not.
     */
    private function findExistingFile(string $token, string $driveId, string $itemPath): ?string
    {
        $response = Http::withToken($token)
            ->get(self::GRAPH_BASE . "/drives/{$driveId}/root:/{$itemPath}");

        if ($response->status() === 200) {
            return $response->json('webUrl');
        }

        return null;
    }

    /**
     * Persist SharePoint result to the delivery record and return the web URL.
     */
    private function persistResult(array $graphItem, QuoteDelivery $delivery): string
    {
        $webUrl = $graphItem['webUrl'] ?? null;
        $fileId = $graphItem['id'] ?? null;

        if (! $webUrl) {
            throw new \RuntimeException(
                "Graph upload returned no webUrl for delivery #{$delivery->id}."
            );
        }

        $delivery->update([
            'sharepoint_file_id' => $fileId,
            'sharepoint_url'     => $webUrl,
        ]);

        Log::info('quote_delivery.sharepoint_uploaded', [
            'delivery_id'    => $delivery->id,
            'quote_id'       => $delivery->quote_id,
            'file_id'        => $fileId,
            'sharepoint_url' => $webUrl,
        ]);

        return $webUrl;
    }

    // -------------------------------------------------------------------------
    // OAuth token
    // -------------------------------------------------------------------------

    private function getAccessToken(): string
    {
        return Cache::remember(
            self::TOKEN_CACHE_KEY,
            now()->addSeconds(3540),
            function () {
                $tenantId = config('services.sharepoint.tenant_id');

                $response = Http::asForm()->post(
                    "https://login.microsoftonline.com/{$tenantId}/oauth2/v2.0/token",
                    [
                        'grant_type'    => 'client_credentials',
                        'client_id'     => config('services.sharepoint.client_id'),
                        'client_secret' => config('services.sharepoint.client_secret'),
                        'scope'         => 'https://graph.microsoft.com/.default',
                    ]
                );

                if ($response->failed()) {
                    throw new \RuntimeException(
                        "Microsoft Graph authentication failed — HTTP {$response->status()}: " .
                        ($response->json('error_description') ?? $response->body())
                    );
                }

                $token = $response->json('access_token');

                if (! $token) {
                    throw new \RuntimeException(
                        'Microsoft Graph authentication returned no access token.'
                    );
                }

                return $token;
            }
        );
    }

    private function extractGraphError(\Illuminate\Http\Client\Response $response): string
    {
        $body = $response->json();

        return $body['error']['message']
            ?? $body['error_description']
            ?? $response->body();
    }

    public function humaniseError(\Throwable $e): string
    {
        $message = strtolower($e->getMessage());

        if (str_contains($message, 'authentication failed')
            || str_contains($message, 'invalid_client')
            || str_contains($message, 'unauthorized')) {
            return 'SharePoint upload failed: Microsoft Graph authentication error. Please contact support.';
        }

        if (str_contains($message, '403') || str_contains($message, 'forbidden')
            || str_contains($message, 'accessdenied')) {
            return 'SharePoint upload failed: The application does not have permission to write to SharePoint.';
        }

        if (str_contains($message, '404') || str_contains($message, 'not found')
            || str_contains($message, 'itemnotfound')) {
            return 'SharePoint upload failed: The destination drive or folder could not be found.';
        }

        if (str_contains($message, 'timeout') || str_contains($message, 'timed out')
            || str_contains($message, 'connection')) {
            return 'SharePoint upload failed: Could not connect to Microsoft Graph. Please try again.';
        }

        if (str_contains($message, 'quota') || str_contains($message, 'storage')) {
            return 'SharePoint upload failed: SharePoint storage quota exceeded.';
        }

        return 'SharePoint upload failed: An unexpected error occurred. Please try again.';
    }
}
