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

        $guard = 'api';

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
            'sales.view','sales.create','sales.update','sales.refund',
            'payments.view','payments.create',

            // Dashboard
            'dashboard.view','dashboard.income','dashboard.products_pie',
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate([
                'name' => $perm,
                'guard_name' => $guard,
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | 2. Create Roles
        |--------------------------------------------------------------------------
        */
        $adminRole   = Role::firstOrCreate(['name' => 'admin',   'guard_name' => $guard]);
        $cashierRole = Role::firstOrCreate(['name' => 'cashier', 'guard_name' => $guard]);

        /*
        |--------------------------------------------------------------------------
        | 3. Assign Permissions to Roles
        |--------------------------------------------------------------------------
        */

        // ✅ Admin → everything BUT only for guard=api (prevents GuardDoesNotMatch)
        $adminRole->syncPermissions(
            Permission::where('guard_name', $guard)->get()
        );

        // ✅ Cashier → sales + payments (choose what you want them to do)
        $cashierRole->syncPermissions(
            Permission::where('guard_name', $guard)
                ->whereIn('name', [
                    'products.view',
                    'sales.view','sales.create',
                    // add these if cashier can do them:
                    // 'sales.update',
                    // 'sales.refund',
                    'payments.create',
                ])->get()
        );

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
        // ✅ Ensure correct guard roles are used
        if (!$admin->hasRole($adminRole->name, $guard)) {
            $admin->syncRoles([$adminRole]);
        }
    }
}
