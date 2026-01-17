<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class AdminRolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles & permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        /*
        |--------------------------------------------------------------------------
        | 1. Create Permissions
        |--------------------------------------------------------------------------
        */
        $permissions = [

            // Users & RBAC
            'users.view','users.create','users.update','users.delete',
            'roles.view','roles.create','roles.update','roles.delete',
            'permissions.view','permissions.create','permissions.update','permissions.delete',

            // Categories & Products
            'categories.view','categories.create','categories.update','categories.delete',
            'products.view','products.create','products.update','products.delete',

            // Stock
            'stock.increase','stock.decrease','stock.view_alerts',

            // Suppliers & Purchases
            'suppliers.view','suppliers.create','suppliers.update','suppliers.delete',
            'purchases.view','purchases.create','purchases.receive',

            // Sales & Payments
            'sales.view','sales.create','sales.refund',
            'payments.view','payments.create',

            // Dashboard
            'dashboard.view','dashboard.income','dashboard.products_pie',
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate([
                'name' => $perm,
                'guard_name' => 'api',
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | 2. Create Roles
        |--------------------------------------------------------------------------
        */
        $adminRole   = Role::firstOrCreate(['name' => 'admin',   'guard_name' => 'api']);
        $managerRole = Role::firstOrCreate(['name' => 'manager', 'guard_name' => 'api']);
        $cashierRole = Role::firstOrCreate(['name' => 'cashier', 'guard_name' => 'api']);
        $staffRole   = Role::firstOrCreate(['name' => 'staff',   'guard_name' => 'api']);

        /*
        |--------------------------------------------------------------------------
        | 3. Assign Permissions to Roles
        |--------------------------------------------------------------------------
        */

        // Admin → everything
        $adminRole->syncPermissions(Permission::all());

        // Manager → reports + view + approve
        $managerRole->syncPermissions([
            'dashboard.view','dashboard.income','dashboard.products_pie',
            'products.view','categories.view',
            'purchases.view','sales.view','payments.view',
            'stock.view_alerts',
        ]);

        // Cashier → sales + payments
        $cashierRole->syncPermissions([
            'products.view',
            'sales.view','sales.create',
            'payments.create',
        ]);

        // Staff → inventory only
        $staffRole->syncPermissions([
            'products.view',
            'categories.view',
            'stock.increase','stock.decrease','stock.view_alerts',
        ]);

        /*
        |--------------------------------------------------------------------------
        | 4. Create Admin User
        |--------------------------------------------------------------------------
        */
        $admin = User::updateOrCreate(
            ['email' => 'admin@mail.com'],
            [
                'name' => 'System Admin',
                'password' => Hash::make('123456'),
                'is_active' => true,
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | 5. Assign Admin Role
        |--------------------------------------------------------------------------
        */
        if (!$admin->hasRole('admin')) {
            $admin->assignRole($adminRole);
        }
    }
}
