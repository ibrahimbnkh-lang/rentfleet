<?php

declare(strict_types=1);

namespace Modules\M00Core\Database\Seeders;

use App\Models\User;
use App\Services\ModuleLoader;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Modules\M00Core\Models\Branch;
use Modules\M00Core\Models\Setting;
use Modules\M00Core\Services\LicenseService;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class M00CoreSeeder extends Seeder
{
    public function run(ModuleLoader $moduleLoader, LicenseService $licenseService): void
    {
        // 1. Seed Permissions from all module manifests
        $permissions = $moduleLoader->allPermissions();
        foreach ($permissions as $permName) {
            Permission::firstOrCreate(['name' => $permName, 'guard_name' => 'web']);
        }

        // 2. Seed Roles
        $superAdmin = Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);
        $superAdmin->syncPermissions(Permission::all());

        $manager = Role::firstOrCreate(['name' => 'Manager', 'guard_name' => 'web']);
        $managerPerms = Permission::where(function ($q) {
            $q->where('name', 'like', 'core.branch.view')
              ->orWhere('name', 'like', 'core.user.%')
              ->orWhere('name', 'like', 'core.audit.view')
              ->orWhere('name', 'like', 'core.settings.view')
              ->orWhere('name', 'like', 'fleet.%')
              ->orWhere('name', 'like', 'customers.%')
              ->orWhere('name', 'like', 'reservations.%')
              ->orWhere('name', 'like', 'contracts.%')
              ->orWhere('name', 'like', 'invoicing.%');
        })->get();
        $manager->syncPermissions($managerPerms);

        $agent = Role::firstOrCreate(['name' => 'Agent', 'guard_name' => 'web']);
        $agentPerms = Permission::where(function ($q) {
            $q->where('name', 'like', 'fleet.vehicle.view')
              ->orWhere('name', 'like', 'fleet.document.%')
              ->orWhere('name', 'like', 'customers.%')
              ->orWhere('name', 'like', 'reservations.%')
              ->orWhere('name', 'like', 'contracts.%')
              ->orWhere('name', 'like', 'invoicing.payment.%');
        })->get();
        $agent->syncPermissions($agentPerms);

        $accountant = Role::firstOrCreate(['name' => 'Accountant', 'guard_name' => 'web']);
        $accountantPerms = Permission::where('name', 'like', 'invoicing.%')->get();
        $accountant->syncPermissions($accountantPerms);
        $mechanic = Role::firstOrCreate(['name' => 'Mechanic', 'guard_name' => 'web']);

        // 3. Seed Default Branches (Algeria)
        $branchAlger = Branch::firstOrCreate(
            ['code' => 'ALG-01'],
            [
                'name' => 'Agence Alger Centre',
                'wilaya_code' => '16',
                'city' => 'Alger',
                'address' => '14 Rue Didouche Mourad, Alger Centre',
                'phone' => '+213 21 73 12 34',
                'email' => 'alger@rentfleet.dz',
                'is_active' => true,
            ]
        );

        $branchOran = Branch::firstOrCreate(
            ['code' => 'ORN-01'],
            [
                'name' => 'Agence Oran Es Senia',
                'wilaya_code' => '31',
                'city' => 'Oran',
                'address' => 'Aéroport International Ahmed Ben Bella, Es Senia',
                'phone' => '+213 41 55 98 76',
                'email' => 'oran@rentfleet.dz',
                'is_active' => true,
            ]
        );

        Branch::firstOrCreate(
            ['code' => 'CST-01'],
            [
                'name' => 'Agence Constantine Ville',
                'wilaya_code' => '25',
                'city' => 'Constantine',
                'address' => '05 Boulevard Zighoud Youcef, Constantine',
                'phone' => '+213 31 92 44 11',
                'email' => 'constantine@rentfleet.dz',
                'is_active' => true,
            ]
        );

        // 4. Seed Default Users
        $admin = User::firstOrCreate(
            ['email' => 'admin@rentfleet.dz'],
            [
                'name' => 'Administrateur Principal',
                'password' => Hash::make('password'),
                'phone' => '+213 550 11 22 33',
                'branch_id' => $branchAlger->id,
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );
        $admin->syncRoles(['Super Admin']);

        $managerAlger = User::firstOrCreate(
            ['email' => 'manager.alger@rentfleet.dz'],
            [
                'name' => 'Sofiane Hamidi',
                'password' => Hash::make('password'),
                'phone' => '+213 550 88 77 66',
                'branch_id' => $branchAlger->id,
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );
        $managerAlger->syncRoles(['Manager']);

        $managerUser = User::firstOrCreate(
            ['email' => 'manager.oran@rentfleet.dz'],
            [
                'name' => 'Karim Benali',
                'password' => Hash::make('password'),
                'phone' => '+213 555 44 33 22',
                'branch_id' => $branchOran->id,
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );
        $managerUser->syncRoles(['Manager']);

        $agentUser = User::firstOrCreate(
            ['email' => 'agent.alger@rentfleet.dz'],
            [
                'name' => 'Yasmine Mansouri',
                'password' => Hash::make('password'),
                'phone' => '+213 661 88 99 00',
                'branch_id' => $branchAlger->id,
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );
        $agentUser->syncRoles(['Agent']);

        // 5. Seed Platform Settings
        $settings = [
            'company_name' => ['RentFleet Algérie SARL', 'general'],
            'company_phone' => ['+213 23 45 67 89', 'general'],
            'company_email' => ['contact@rentfleet.dz', 'general'],
            'company_address' => ['Cité 1200 Logements, Bab Ezzouar, Alger', 'general'],
            'company_nif' => ['002116012345678', 'general'],
            'company_rc' => ['16/00-1234567B21', 'general'],
            'currency' => ['DZD', 'financial'],
            'vat_rate' => ['19', 'financial'],
            'timezone' => ['Africa/Algiers', 'general'],
            'date_format' => ['d/m/Y', 'general'],
        ];

        foreach ($settings as $key => [$value, $group]) {
            Setting::firstOrCreate(
                ['key' => $key],
                ['value' => $value, 'group' => $group, 'is_public' => true]
            );
        }

        // 6. Issue and activate Default Dev License
        $licenseData = $licenseService->issueLicense(
            clientName: 'RentFleet Algérie SARL',
            clientEmail: 'contact@rentfleet.dz',
            modules: ['M00Core', 'M01Fleet', 'M02Customers', 'M03Reservations', 'M05Contracts', 'M07Invoicing'],
            days: 365,
            maxVehicles: 150,
            maxBranches: 10
        );

        $licenseService->activate($licenseData['token']);
    }
}
