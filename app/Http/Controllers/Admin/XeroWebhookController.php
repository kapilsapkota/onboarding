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
        if (empty($payload) || $payload === '{}') {
            Log::info('XeroWebhook: intent-to-receive handshake');
            return response('OK', 200);
        }

        if (! $this->verifySignature($payload, $sigHeader)) {
            Log::warning('XeroWebhook: invalid signature', [
                'received'  => $sigHeader,
            ]);
            return response('Invalid signature', 401);
        }

        $body = json_decode($payload, true);

        if (empty($body['events'])) {
            Log::info('XeroWebhook: received empty events payload (ping)');
            return response('OK', 200);
        }

        Log::info('XeroWebhook: received events', [
            'count'               => count($body['events']),
            'firstEventSequence'  => $body['firstEventSequence'] ?? null,
            'lastEventSequence'   => $body['lastEventSequence'] ?? null,
        ]);

        foreach ($body['events'] as $event) {
            $this->dispatchEvent($event);
        }

        return response('OK', 200);
    }

    // -------------------------------------------------------------------------

    private function dispatchEvent(array $event): void
    {
        $category   = $event['eventCategory'] ?? null;
        $type       = $event['eventType'] ?? null;
        $resourceId = $event['resourceId'] ?? null;
        $tenantId   = $event['tenantId'] ?? null;

        Log::info('XeroWebhook: dispatching event', [
            'category'   => $category,
            'type'       => $type,
            'resourceId' => $resourceId,
            'tenantId'   => $tenantId,
            'event'      => $event,
        ]);

        $tenant = XeroTenant::where('tenant_id', $tenantId)->first();

        if (! $tenant) {
            Log::warning('XeroWebhook: no local tenant found for tenantId', [
                'tenantId' => $tenantId,
            ]);
            return;
        }

        if (! $tenant->connection) {
            Log::warning('XeroWebhook: tenant has no active connection', [
                'tenant_id' => $tenant->id,
            ]);
            return;
        }

        match ($category) {
            'INVOICE' => $this->handleInvoiceEvent($tenant, $resourceId, $type),
            'CONTACT' => $this->handleContactEvent($tenant, $resourceId, $type),
            default   => Log::info('XeroWebhook: unhandled event category', [
                'category' => $category,
            ]),
        };
    }

    private function handleInvoiceEvent(XeroTenant $tenant, string $resourceId, string $type): void
    {
        SyncTenantInvoiceJob::dispatch(
            connectionId:  $tenant->connection->id,
            tenantId:      $tenant->id,
            modifiedAfter: null,
            fullResync:    false,
        );

        Log::info('XeroWebhook: dispatched invoice sync', [
            'tenant_id'  => $tenant->id,
            'resourceId' => $resourceId,
            'type'       => $type,
        ]);
    }

    private function handleContactEvent(XeroTenant $tenant, string $resourceId, string $type): void
    {
        SyncXeroTenantContacts::dispatch(
            connectionId: $tenant->connection->id,
            tenantId:     $tenant->id,
        );

        Log::info('XeroWebhook: dispatched contact sync', [
            'tenant_id'  => $tenant->id,
            'resourceId' => $resourceId,
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
