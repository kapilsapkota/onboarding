<?php

namespace App\Http\Controllers\Admin\Concerns;

use App\Models\XeroTenant;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;

trait ResolvesXeroTenant
{
    /**
     * All active tenants for pickers / filters, with connection eager-loaded.
     *
     * @return Collection<int, XeroTenant>
     */
    protected function activeTenants()
    {
        return XeroTenant::with('connection')
            ->where('is_active', true)
            ->orderBy('tenant_name')
            ->get();
    }

    /**
     * Resolve the working tenant from ?tenant= (local id) or fall back to
     * the first active tenant. Aborts 404 when nothing is connected.
     */
    protected function resolveTenant(Request $request): XeroTenant
    {
        $tenants = $this->activeTenants();

        if ($tenants->isEmpty()) {
            abort(404, 'No active Xero organisation. Connect Xero first.');
        }

        $tenantId = $request->query('tenant', $request->input('xero_tenant_id'));

        if ($tenantId) {
            $tenant = $tenants->firstWhere('id', (int) $tenantId);

            if (! $tenant) {
                abort(404, 'Unknown Xero organisation.');
            }

            return $tenant;
        }

        return $tenants->first();
    }

    /**
     * Resolve a tenant by local id (route / form scope) without a request.
     */
    protected function findTenant(int $id): XeroTenant
    {
        return XeroTenant::with('connection')->where('is_active', true)->findOrFail($id);
    }
}
