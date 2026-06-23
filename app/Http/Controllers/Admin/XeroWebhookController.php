<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\SyncTenantInvoiceJob;
use App\Jobs\SyncXeroTenantContacts;
use App\Models\XeroTenant;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class XeroWebhookController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $payload   = $request->getContent();
        $sigHeader = $request->header('x-xero-signature');

        // Intent-to-receive handshake: Xero sends an empty body to verify the
        // endpoint exists. Must return 200 immediately — no signature to check.
        if (empty($payload) || $payload === '{}') {
            Log::info('XeroWebhook: intent-to-receive handshake');
            return response('OK', 200);
        }

        // Signature failure is the only case where we return non-200.
        // Xero treats repeated non-200 responses as a reason to disable the
        // subscription, so everything else below must always return 200.
        if (! $this->verifySignature($payload, $sigHeader)) {
            Log::warning('XeroWebhook: invalid signature', ['received' => $sigHeader]);
            return response('Invalid signature', 401);
        }

        $body = json_decode($payload, true);

        if (empty($body['events'])) {
            Log::info('XeroWebhook: ping with empty events');
            return response('OK', 200);
        }

        Log::info('XeroWebhook: received events', [
            'count'              => count($body['events']),
            'firstEventSequence' => $body['firstEventSequence'] ?? null,
            'lastEventSequence'  => $body['lastEventSequence'] ?? null,
        ]);
        try {
            foreach ($body['events'] as $event) {
                $this->dispatchEvent($event);
            }
        } catch (\Throwable $e) {
            Log::error('XeroWebhook: unexpected error during dispatch', [
                'error' => $e->getMessage(),
            ]);
        }

        return response('OK', 200);
    }

    // -------------------------------------------------------------------------

    private function dispatchEvent(array $event): void
    {
        $category   = $event['eventCategory'] ?? null;
        $type       = $event['eventType']     ?? null;
        $resourceId = $event['resourceId']    ?? null;
        $tenantId   = $event['tenantId']      ?? null;

        $tenant = XeroTenant::where('tenant_id', $tenantId)->first();

        if (! $tenant) {
            Log::warning('XeroWebhook: no local tenant for tenantId', ['tenantId' => $tenantId]);
            return;
        }

        $connection = $tenant->connection;

        if (! $connection) {
            Log::warning('XeroWebhook: tenant has no connection', ['tenant_id' => $tenant->id]);
            return;
        }
        if ($connection->needs_reauth) {
            Log::error('XeroWebhook: skipping dispatch — connection requires re-authorization', [
                'tenant_id'     => $tenant->id,
                'connection_id' => $connection->id,
                'reason'        => $connection->reauth_reason,
                'event'         => ['category' => $category, 'type' => $type, 'resourceId' => $resourceId],
            ]);
            return;
        }

        if (! $connection->is_active) {
            Log::info('XeroWebhook: skipping dispatch — connection is inactive', [
                'tenant_id'     => $tenant->id,
                'connection_id' => $connection->id,
            ]);
            return;
        }

        Log::info('XeroWebhook: dispatching event', [
            'category'   => $category,
            'type'       => $type,
            'resourceId' => $resourceId,
            'tenantId'   => $tenantId,
        ]);

        match ($category) {
            'INVOICE' => $this->handleInvoiceEvent($tenant, $resourceId, $type),
            'CONTACT' => $this->handleContactEvent($tenant, $resourceId, $type),
            default   => Log::info('XeroWebhook: unhandled event category', ['category' => $category]),
        };
    }

    private function handleInvoiceEvent(XeroTenant $tenant, string $resourceId, string $type): void
    {
        SyncTenantInvoiceJob::dispatch(
            connectionId:  $tenant->connection->id,
            tenantId:      $tenant->id,
            invoiceId:     $resourceId,
            modifiedAfter: null,
            fullResync:    false,
        );

        Log::info('XeroWebhook: dispatched invoice sync', [
            'tenant_id'  => $tenant->id,
            'invoice_id' => $resourceId,
            'type'       => $type,
        ]);
    }

    private function handleContactEvent(XeroTenant $tenant, string $resourceId, string $type): void
    {
        SyncXeroTenantContacts::dispatch(
            connectionId: $tenant->connection->id,
            tenantId:     $tenant->id,
            contactId:    $resourceId,
        );

        Log::info('XeroWebhook: dispatched contact sync', [
            'tenant_id'  => $tenant->id,
            'contact_id' => $resourceId,
            'type'       => $type,
        ]);
    }

    // -------------------------------------------------------------------------

    private function verifySignature(?string $payload, ?string $signature): bool
    {
        if ($signature === null || $payload === null) {
            return false;
        }

        $secret   = config('services.xero.webhook_secret');
        $computed = base64_encode(hash_hmac('sha256', $payload, $secret, true));

        return hash_equals($computed, $signature);
    }
}
