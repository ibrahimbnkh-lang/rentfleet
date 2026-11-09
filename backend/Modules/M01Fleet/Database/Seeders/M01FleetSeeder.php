<?php

declare(strict_types=1);

namespace Modules\M01Fleet\Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Modules\M00Core\Models\Branch;
use Modules\M01Fleet\Models\Vehicle;
use Modules\M01Fleet\Models\VehicleCategory;
use Modules\M01Fleet\Models\VehicleDocument;
use Modules\M01Fleet\Models\VehicleEquipment;

class M01FleetSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Seed Categories
        $catEco = VehicleCategory::firstOrCreate(
            ['slug' => 'economique'],
            [
                'name' => 'Économique',
                'description' => 'Citadines maniables et économiques en carburant, idéales pour la ville.',
                'daily_rate_dzd' => 4500.00,
                'deposit_amount_dzd' => 45000.00,
            ]
        );

        $catBerline = VehicleCategory::firstOrCreate(
            ['slug' => 'berline'],
            [
                'name' => 'Berline',
                'description' => 'Confort supérieur et habitabilité pour les longs trajets et voyages d\'affaires.',
                'daily_rate_dzd' => 8000.00,
                'deposit_amount_dzd' => 80000.00,
            ]
        );

        $catSuv = VehicleCategory::firstOrCreate(
            ['slug' => 'suv-4x4'],
            [
                'name' => 'SUV & 4x4',
                'description' => 'Espace, garde au sol élevée et robustesse pour tout type de route.',
                'daily_rate_dzd' => 11000.00,
                'deposit_amount_dzd' => 120000.00,
            ]
        );

        $catPremium = VehicleCategory::firstOrCreate(
            ['slug' => 'premium'],
            [
                'name' => 'Premium & Luxe',
                'description' => 'Prestige, finitions d\'exception et motorisations haut de gamme.',
                'daily_rate_dzd' => 25000.00,
                'deposit_amount_dzd' => 250000.00,
            ]
        );

        // 2. Fetch Branches
        $branchAlger = Branch::where('code', 'ALG-01')->first() ?? Branch::first();
        $branchOran = Branch::where('code', 'ORN-01')->first() ?? Branch::first();
        $branchConstantine = Branch::where('code', 'CST-01')->first() ?? Branch::first();

        // 3. Seed Vehicles Dataset
        $vehiclesData = [
            // Alger
            [
                'branch_id' => $branchAlger->id,
                'category_id' => $catEco->id,
                'brand' => 'Renault',
                'model' => 'Symbol 1.6 Ess.',
                'year' => 2023,
                'registration_plate' => '01234-123-16',
                'vin' => 'VF1LB000012345671',
                'color' => 'Blanc Glacier',
                'fuel_type' => 'essence',
                'transmission' => 'manuelle',
                'mileage' => 34500,
                'daily_price_dzd' => 4500.00,
                'deposit_dzd' => 45000.00,
                'status' => 'available',
            ],
            [
                'branch_id' => $branchAlger->id,
                'category_id' => $catSuv->id,
                'brand' => 'Dacia',
                'model' => 'Duster 1.5 dCi 4x2',
                'year' => 2024,
                'registration_plate' => '05678-124-16',
                'vin' => 'VF1HSD00023456782',
                'color' => 'Gris Comète',
                'fuel_type' => 'diesel',
                'transmission' => 'manuelle',
                'mileage' => 18200,
                'daily_price_dzd' => 7500.00,
                'deposit_dzd' => 75000.00,
                'status' => 'available',
            ],
            [
                'branch_id' => $branchAlger->id,
                'category_id' => $catSuv->id,
                'brand' => 'Hyundai',
                'model' => 'Tucson 2.0 CRDi',
                'year' => 2023,
                'registration_plate' => '09101-123-16',
                'vin' => 'KMHT3813334567893',
                'color' => 'Noir Phantom',
                'fuel_type' => 'diesel',
                'transmission' => 'automatique',
                'mileage' => 42100,
                'daily_price_dzd' => 13500.00,
                'deposit_dzd' => 130000.00,
                'status' => 'available',
            ],
            [
                'branch_id' => $branchAlger->id,
                'category_id' => $catEco->id,
                'brand' => 'Peugeot',
                'model' => '208 GT Line',
                'year' => 2024,
                'registration_plate' => '03456-124-16',
                'vin' => 'VF3UPH00045678904',
                'color' => 'Bleu Vertigo',
                'fuel_type' => 'essence',
                'transmission' => 'automatique',
                'mileage' => 12500,
                'daily_price_dzd' => 6500.00,
                'deposit_dzd' => 65000.00,
                'status' => 'rented',
            ],
            [
                'branch_id' => $branchAlger->id,
                'category_id' => $catPremium->id,
                'brand' => 'Mercedes-Benz',
                'model' => 'Classe C 220d',
                'year' => 2023,
                'registration_plate' => '07890-123-16',
                'vin' => 'WDD20500056789015',
                'color' => 'Argent Iridium',
                'fuel_type' => 'diesel',
                'transmission' => 'automatique',
                'mileage' => 25800,
                'daily_price_dzd' => 28000.00,
                'deposit_dzd' => 280000.00,
                'status' => 'available',
            ],

            // Oran
            [
                'branch_id' => $branchOran->id,
                'category_id' => $catEco->id,
                'brand' => 'Dacia',
                'model' => 'Sandero Stepway',
                'year' => 2023,
                'registration_plate' => '02345-123-31',
                'vin' => 'VF1BSD00067890126',
                'color' => 'Orange Atacama',
                'fuel_type' => 'essence',
                'transmission' => 'manuelle',
                'mileage' => 29400,
                'daily_price_dzd' => 5000.00,
                'deposit_dzd' => 50000.00,
                'status' => 'available',
            ],
            [
                'branch_id' => $branchOran->id,
                'category_id' => $catBerline->id,
                'brand' => 'Volkswagen',
                'model' => 'Golf 8 R-Line',
                'year' => 2023,
                'registration_plate' => '06789-123-31',
                'vin' => 'WVWZZZCD078901237',
                'color' => 'Blanc Pur',
                'fuel_type' => 'essence',
                'transmission' => 'automatique',
                'mileage' => 38000,
                'daily_price_dzd' => 14000.00,
                'deposit_dzd' => 140000.00,
                'status' => 'available',
            ],
            [
                'branch_id' => $branchOran->id,
                'category_id' => $catSuv->id,
                'brand' => 'Kia',
                'model' => 'Sportage 1.6 CRDi',
                'year' => 2024,
                'registration_plate' => '04567-124-31',
                'vin' => 'KNAFP812089012348',
                'color' => 'Gris Acier',
                'fuel_type' => 'diesel',
                'transmission' => 'automatique',
                'mileage' => 15200,
                'daily_price_dzd' => 13000.00,
                'deposit_dzd' => 125000.00,
                'status' => 'available',
            ],

            // Constantine
            [
                'branch_id' => $branchConstantine->id,
                'category_id' => $catEco->id,
                'brand' => 'Renault',
                'model' => 'Clio 4 dCi',
                'year' => 2022,
                'registration_plate' => '01122-122-25',
                'vin' => 'VF15R000090123459',
                'color' => 'Rouge Flamme',
                'fuel_type' => 'diesel',
                'transmission' => 'manuelle',
                'mileage' => 55000,
                'daily_price_dzd' => 5200.00,
                'deposit_dzd' => 50000.00,
                'status' => 'available',
            ],
            [
                'branch_id' => $branchConstantine->id,
                'category_id' => $catSuv->id,
                'brand' => 'Toyota',
                'model' => 'Hilux Double Cabine',
                'year' => 2023,
                'registration_plate' => '03344-123-25',
                'vin' => 'AHTFR22G001234560',
                'color' => 'Blanc Polaire',
                'fuel_type' => 'diesel',
                'transmission' => 'manuelle',
                'mileage' => 48200,
                'daily_price_dzd' => 12000.00,
                'deposit_dzd' => 120000.00,
                'status' => 'maintenance',
            ],
        ];

        foreach ($vehiclesData as $vItem) {
            $vehicle = Vehicle::firstOrCreate(
                ['registration_plate' => $vItem['registration_plate']],
                $vItem
            );

            // Add standard equipment
            $standardEquipment = [
                'Roue de secours',
                'Cric & Manivelle',
                'Gilet & Triangle',
                'Trousse de secours',
                'Extincteur',
            ];

            foreach ($standardEquipment as $eqName) {
                VehicleEquipment::firstOrCreate(
                    ['vehicle_id' => $vehicle->id, 'name' => $eqName],
                    ['is_present' => true, 'condition' => 'bon_etat']
                );
            }

            // Add regulatory documents
            // Make one vehicle have an expiring document (15 days remaining) for alerts demo
            $isExpiringDemo = ($vehicle->registration_plate === '01234-123-16');

            // Assurance
            VehicleDocument::firstOrCreate(
                ['vehicle_id' => $vehicle->id, 'type' => 'assurance'],
                [
                    'reference_number' => 'POL-SAA-' . rand(100000, 999999),
                    'issuer' => 'SAA Assurances Algérie',
                    'issued_at' => Carbon::now()->subMonths(6),
                    'expires_at' => $isExpiringDemo ? Carbon::now()->addDays(12) : Carbon::now()->addMonths(6),
                ]
            );

            // Contrôle technique
            VehicleDocument::firstOrCreate(
                ['vehicle_id' => $vehicle->id, 'type' => 'controle_technique'],
                [
                    'reference_number' => 'PV-CT-' . rand(10000, 99999),
                    'issuer' => 'Centre de Contrôle Technique Agréé',
                    'issued_at' => Carbon::now()->subMonths(3),
                    'expires_at' => Carbon::now()->addMonths(9),
                ]
            );

            // Carte grise
            VehicleDocument::firstOrCreate(
                ['vehicle_id' => $vehicle->id, 'type' => 'carte_grise'],
                [
                    'reference_number' => 'CG-' . $vehicle->registration_plate,
                    'issuer' => 'Wilaya / Daïra',
                    'issued_at' => Carbon::now()->subYears(1),
                    'expires_at' => null, // carte grise permanent
                ]
            );
        }
    }
}
