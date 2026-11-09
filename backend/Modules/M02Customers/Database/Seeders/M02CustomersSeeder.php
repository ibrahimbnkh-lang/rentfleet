<?php

declare(strict_types=1);

namespace Modules\M02Customers\Database\Seeders;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Modules\M00Core\Models\Branch;
use Modules\M02Customers\Models\Customer;
use Modules\M02Customers\Models\CustomerBlacklist;
use Modules\M02Customers\Models\CustomerDocument;

class M02CustomersSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('email', 'admin@rentfleet.dz')->first();
        $algerBranch = Branch::where('code', 'ALG-01')->first();
        $oranBranch = Branch::where('code', 'ORN-01')->first();
        $cstBranch = Branch::where('code', 'CST-01')->first();

        // 1. Verified Eligible Customer - Amine Belkacem
        $amine = Customer::firstOrCreate(
            ['phone' => '+213 550 11 22 33'],
            [
                'type' => 'individual',
                'first_name' => 'Amine',
                'last_name' => 'Belkacem',
                'email' => 'amine.belkacem@gmail.com',
                'national_id_number' => '116890123456789012',
                'driver_license_number' => '16/2018/98412',
                'driver_license_issued_at' => Carbon::now()->subYears(6)->toDateString(),
                'driver_license_wilaya' => '16',
                'birth_date' => Carbon::now()->subYears(32)->toDateString(),
                'birth_place' => 'Alger Centre',
                'address' => '14 Rue Didouche Mourad',
                'wilaya_code' => '16',
                'city' => 'Alger',
                'kyc_status' => 'verified',
                'kyc_verified_at' => Carbon::now()->subDays(10),
                'kyc_verified_by' => $admin?->id,
                'is_blacklisted' => false,
                'is_active' => true,
                'notes' => 'Client fidèle, 4 locations sans incident.',
            ]
        );

        CustomerDocument::firstOrCreate(
            ['customer_id' => $amine->id, 'type' => 'cni_recto'],
            [
                'file_path' => '/uploads/customers/amine_cni_recto.jpg',
                'file_name' => 'CNI_Amine_Belkacem_Recto.jpg',
                'status' => 'approved',
                'reviewed_by' => $admin?->id,
                'reviewed_at' => Carbon::now()->subDays(10),
            ]
        );

        CustomerDocument::firstOrCreate(
            ['customer_id' => $amine->id, 'type' => 'permis_recto'],
            [
                'file_path' => '/uploads/customers/amine_permis_recto.jpg',
                'file_name' => 'Permis_Amine_Belkacem_Recto.jpg',
                'status' => 'approved',
                'reviewed_by' => $admin?->id,
                'reviewed_at' => Carbon::now()->subDays(10),
            ]
        );

        // 2. Verified Corporate Customer - SARL NUMIDIA LOGISTICS
        Customer::firstOrCreate(
            ['phone' => '+213 560 44 55 66'],
            [
                'type' => 'company',
                'company_name' => 'SARL NUMIDIA LOGISTICS',
                'company_tax_id' => '001916012345678',
                'first_name' => 'Yacine',
                'last_name' => 'Benali',
                'email' => 'contact@numidia-logistics.dz',
                'national_id_number' => '116850987654321098',
                'driver_license_number' => '16/2012/45123',
                'driver_license_issued_at' => Carbon::now()->subYears(12)->toDateString(),
                'driver_license_wilaya' => '16',
                'birth_date' => Carbon::now()->subYears(41)->toDateString(),
                'birth_place' => 'Bab Ezzouar',
                'address' => 'Zone d’Activité Bab Ezzouar, Lot 14',
                'wilaya_code' => '16',
                'city' => 'Bab Ezzouar',
                'kyc_status' => 'verified',
                'kyc_verified_at' => Carbon::now()->subDays(20),
                'kyc_verified_by' => $admin?->id,
                'is_blacklisted' => false,
                'is_active' => true,
                'notes' => 'Compte entreprise, flotte commerciale.',
            ]
        );

        // 3. Pending KYC Customer - Meriem Bouziane (Oran)
        $meriem = Customer::firstOrCreate(
            ['phone' => '+213 770 99 88 77'],
            [
                'type' => 'individual',
                'first_name' => 'Meriem',
                'last_name' => 'Bouziane',
                'email' => 'meriem.bouziane@gmail.com',
                'national_id_number' => '131030112233445566',
                'driver_license_number' => '31/2023/12890',
                'driver_license_issued_at' => Carbon::now()->subMonths(14)->toDateString(), // < 2 years seniority!
                'driver_license_wilaya' => '31',
                'birth_date' => Carbon::now()->subYears(22)->toDateString(),
                'birth_place' => 'Oran',
                'address' => '22 Boulevard de la Soummam',
                'wilaya_code' => '31',
                'city' => 'Oran',
                'kyc_status' => 'pending',
                'is_blacklisted' => false,
                'is_active' => true,
                'notes' => 'Nouvelle inscription via le site agence Oran. Permis récent (1 an et 2 mois).',
            ]
        );

        CustomerDocument::firstOrCreate(
            ['customer_id' => $meriem->id, 'type' => 'cni_recto'],
            [
                'file_path' => '/uploads/customers/meriem_cni.jpg',
                'file_name' => 'CNI_Meriem_Bouziane.jpg',
                'status' => 'pending',
            ]
        );

        // 4. Blacklisted Customer - Karim Saadi (Constantine)
        $karim = Customer::firstOrCreate(
            ['phone' => '+213 661 22 33 44'],
            [
                'type' => 'individual',
                'first_name' => 'Karim',
                'last_name' => 'Saadi',
                'email' => 'karim.saadi@hotmail.com',
                'national_id_number' => '125880456123789045',
                'driver_license_number' => '25/2015/34112',
                'driver_license_issued_at' => Carbon::now()->subYears(9)->toDateString(),
                'driver_license_wilaya' => '25',
                'birth_date' => Carbon::now()->subYears(36)->toDateString(),
                'birth_place' => 'Constantine',
                'address' => '5 Cité Ciloc',
                'wilaya_code' => '25',
                'city' => 'Constantine',
                'kyc_status' => 'verified',
                'kyc_verified_at' => Carbon::now()->subMonths(6),
                'kyc_verified_by' => $admin?->id,
                'is_blacklisted' => true,
                'is_active' => true,
                'notes' => 'ATTENTION: Inscrit sur la liste noire nationale suite à incident non régularisé.',
            ]
        );

        CustomerBlacklist::firstOrCreate(
            ['customer_id' => $karim->id],
            [
                'reason' => 'degradation',
                'severity' => 'high',
                'incident_date' => Carbon::now()->subMonths(2)->toDateString(),
                'reported_by' => $admin?->id,
                'branch_id' => $cstBranch?->id,
                'notes' => 'Accident responsable non déclaré avec dégradation grave de la carrosserie d’une Renault Symbol (00124 123 25). Refus catégorique de payer le reliquat de franchise.',
            ]
        );

        // 5. Eligible Customer - Dr. Nadir Larbi (Alger)
        Customer::firstOrCreate(
            ['phone' => '+213 555 44 33 22'],
            [
                'type' => 'individual',
                'first_name' => 'Nadir',
                'last_name' => 'Larbi',
                'email' => 'nadir.larbi@sante.dz',
                'national_id_number' => '116800778899001122',
                'driver_license_number' => '16/2010/87654',
                'driver_license_issued_at' => Carbon::now()->subYears(14)->toDateString(),
                'driver_license_wilaya' => '16',
                'birth_date' => Carbon::now()->subYears(44)->toDateString(),
                'birth_place' => 'Hydra',
                'address' => '3 Chemin Sidi Yahia, Hydra',
                'wilaya_code' => '16',
                'city' => 'Hydra',
                'kyc_status' => 'verified',
                'kyc_verified_at' => Carbon::now()->subMonths(3),
                'kyc_verified_by' => $admin?->id,
                'is_blacklisted' => false,
                'is_active' => true,
                'notes' => 'Client régulier catégorie Premium (Mercedes Classe C).',
            ]
        );
    }
}
