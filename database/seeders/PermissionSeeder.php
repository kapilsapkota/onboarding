<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $grouped = [
            'Users' => [
                'view-user',
                'create-user',
                'edit-user',
                'delete-user',
            ],
            'Roles & Permissions' => [
                'view-role',
                'create-role',
                'edit-role',
                'delete-role',
                'view-permission',
                'create-permission',
                'edit-permission',
                'delete-permission',
            ],
            'Clients' => [
                'view-client',
                'create-client',
                'edit-client',
                'delete-client',
            ],
            'Contacts' => [
                'view-contact',
                'create-contact',
                'edit-contact',
                'delete-contact',
            ],
            'Mandates' => [
                'view-mandate',
                'create-mandate',
                'edit-mandate',
                'delete-mandate',
            ],
            'Direct Debit' => [
                'view-direct-debit',
                'create-direct-debit',       // manually initiate a charge
                'retry-direct-debit',        // retry a failed payment
                'cancel-direct-debit',       // cancel a pending/processing payment
            ],
            'Invoices' => [
                'view-invoice',
                'sync-invoice',              // trigger a manual Xero invoice sync
            ],
            'Xero' => [
                'view-xero',
                'connect-xero',              // OAuth connect / reconnect
                'disconnect-xero',           // remove a connection
                'configure-xero',            // set DD bank account, tenant settings
                'sync-xero',                 // manually push a payment to Xero
                'sync-xero-contacts',        // trigger contact sync
                'sync-xero-invoices',        // trigger invoice sync
            ],
            'Payments' => [
                'view-payment',
                'refund-payment',
            ],
            'Reports' => [
                'view-report',
                'export-report',
            ],
            'Settings' => [
                'view-settings',
                'edit-settings',
            ],
        ];

        $permissions = collect($grouped)->flatten()->all();

        // Create any missing permissions and backfill their module group
        foreach ($grouped as $group => $names) {
            foreach ($names as $name) {
                $permission = Permission::firstOrCreate(['name' => $name]);
                if ($permission->group !== $group) {
                    $permission->update(['group' => $group]);
                }
            }
        }

        // ── Super Admin ───────────────────────────────────────────────────────
        // Gets everything.
        $superAdmin = Role::firstOrCreate(['name' => 'super-admin']);
        $superAdmin->syncPermissions($permissions);

        // ── Admin ─────────────────────────────────────────────────────────────
        // Full client/invoice/payment management but can't touch roles,
        // permissions, or Xero OAuth credentials.
        $admin = Role::firstOrCreate(['name' => 'admin']);
        $admin->syncPermissions([
            'view-user', 'create-user', 'edit-user', 'delete-user',

            'view-client', 'create-client', 'edit-client', 'delete-client',
            'view-contact', 'create-contact', 'edit-contact', 'delete-contact',

            'view-mandate', 'create-mandate', 'edit-mandate', 'delete-mandate',

            'view-direct-debit', 'create-direct-debit', 'retry-direct-debit', 'cancel-direct-debit',

            'view-invoice', 'sync-invoice',

            'view-xero', 'configure-xero', 'sync-xero', 'sync-xero-contacts', 'sync-xero-invoices',

            'view-payment', 'refund-payment',

            'view-report', 'export-report',

            'view-settings',
        ]);

        // ── Accounts / Billing Staff ──────────────────────────────────────────
        // Can see and charge clients, view Xero data, but can't delete anything
        // or touch system config.
        $accounts = Role::firstOrCreate(['name' => 'accounts']);
        $accounts->syncPermissions([
            'view-client', 'edit-client',
            'view-contact',

            'view-mandate',

            'view-direct-debit', 'create-direct-debit', 'retry-direct-debit',

            'view-invoice',

            'view-xero', 'sync-xero', 'sync-xero-invoices',

            'view-payment',

            'view-report', 'export-report',
        ]);

        // ── Read-only ─────────────────────────────────────────────────────────
        // View everything, change nothing. Useful for management/observers.
        $readOnly = Role::firstOrCreate(['name' => 'read-only']);
        $readOnly->syncPermissions([
            'view-client',
            'view-contact',
            'view-mandate',
            'view-direct-debit',
            'view-invoice',
            'view-xero',
            'view-payment',
            'view-report',
        ]);

        // ── Manager ─────────────────────────────────────────────────────────────
        // Can manage users (no delete) + clients, for the Admin/Manager/User trio.
        $manager = Role::firstOrCreate(['name' => 'manager']);
        $manager->syncPermissions([
            'view-user', 'create-user', 'edit-user',

            'view-client', 'create-client', 'edit-client',
            'view-contact', 'create-contact', 'edit-contact',

            'view-mandate',

            'view-direct-debit',

            'view-invoice',

            'view-xero',

            'view-payment',

            'view-report', 'export-report',
        ]);

        // ── User ────────────────────────────────────────────────────────────────
        // Standard staff role: view users/clients, manage own mandate.
        $plainUser = Role::firstOrCreate(['name' => 'user']);
        $plainUser->syncPermissions([
            'view-user',

            'view-client',
            'view-contact',
            'view-mandate',
            'view-direct-debit',
            'view-invoice',
            'view-xero',
            'view-payment',
            'view-report',
        ]);

        // ── Customer ──────────────────────────────────────────────────────────
        // Portal-facing role — can only manage their own mandate.
        $customer = Role::firstOrCreate(['name' => 'customer']);
        $customer->syncPermissions([
            'create-mandate',
            'view-mandate',
        ]);

        // ── Friendly display names (backfilled on every run) ────────────────
        foreach ([
            'super-admin' => 'Super Admin',
            'admin' => 'Administrator',
            'accounts' => 'Accounts',
            'read-only' => 'Read Only',
            'manager' => 'Manager',
            'user' => 'User',
            'customer' => 'Customer',
        ] as $name => $displayName) {
            $role = Role::where('name', $name)->first();
            if ($role && $role->display_name !== $displayName) {
                $role->update(['display_name' => $displayName]);
            }
        }

        // ── Assign super-admin to all existing users (dev/initial seed only) ──
        foreach (User::all() as $user) {
            if (! $user->hasRole('super-admin')) {
                $user->assignRole('super-admin');
            }
        }
    }
}
