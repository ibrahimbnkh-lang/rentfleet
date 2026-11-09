<?php

declare(strict_types=1);

namespace Modules\M05Contracts\Database\Seeders;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Modules\M00Core\Models\Branch;
use Modules\M01Fleet\Models\Vehicle;
use Modules\M02Customers\Models\Customer;
use Modules\M03Reservations\Models\Reservation;
use Modules\M05Contracts\Models\Contract;

class M05ContractsSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::first();
        $branchAlger = Branch::where('code', 'ALG-01')->first() ?? Branch::first();
        $customers = Customer::all();
        $vehicles = Vehicle::all();

        if ($customers->isEmpty() || $vehicles->isEmpty()) {
            return;
        }

        $customer1 = $customers->first();
        $customer2 = $customers->count() > 1 ? $customers->get(1) : $customer1;
        $customer3 = $customers->count() > 2 ? $customers->get(2) : $customer1;

        $veh1 = $vehicles->get(0);
        $veh2 = $vehicles->count() > 1 ? $vehicles->get(1) : $veh1;
        $veh3 = $vehicles->count() > 2 ? $vehicles->get(2) : $veh1;

        $res1 = Reservation::where('reference_code', 'RES-2026-0001')->first();

        // Sample SVG/PNG base64 signature simulation
        $mockSignature = 'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="150" height="60"><path d="M10 40 Q 30 10, 60 30 T 110 20 T 140 45" stroke="%232563EB" stroke-width="2.5" fill="none"/></svg>';
        $mockAgentSignature = 'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="150" height="60"><path d="M15 35 Q 45 15, 80 40 T 135 25" stroke="%231E293B" stroke-width="2" fill="none"/></svg>';

        // 1. Active Contract linked to RES-2026-0001
        $start1 = Carbon::now()->subDays(2)->setHour(10)->setMinute(0);
        $end1 = Carbon::now()->addDays(3)->setHour(10)->setMinute(0);
        $days1 = 5;
        $rate1 = (float) $veh1->daily_price_dzd;

        Contract::firstOrCreate(
            ['contract_number' => 'CTR-2026-0001'],
            [
                'reservation_id' => $res1?->id,
                'customer_id' => $customer1->id,
                'vehicle_id' => $veh1->id,
                'branch_id' => $branchAlger->id,
                'return_branch_id' => $branchAlger->id,
                'agent_id' => $admin?->id,
                'status' => Contract::STATUS_ACTIVE,
                'start_datetime' => $start1,
                'end_datetime' => $end1,
                'days_count' => $days1,
                'daily_rate_dzd' => $rate1,
                'total_amount_dzd' => $days1 * $rate1,
                'deposit_amount_dzd' => (float) $veh1->deposit_dzd,
                'deposit_type' => 'cheque',
                'deposit_reference' => 'BNA Chèque N° 00481920',
                'start_mileage' => (int) ($veh1->mileage ?? 45000),
                'start_fuel_level' => '100%',
                'start_notes' => 'Véhicule remis propre intérieur et extérieur. Roue de secours et gilet de sécurité vérifiés.',
                'start_inspected_at' => $start1,
                'signed_at' => $start1,
                'client_signature' => $mockSignature,
                'agent_signature' => $mockAgentSignature,
                'signer_ip' => '197.200.12.84',
                'signer_national_id' => $customer1->national_id_number,
                'clauses_accepted' => true,
            ]
        );

        // 2. Completed Past Contract
        $start2 = Carbon::now()->subDays(15)->setHour(9)->setMinute(0);
        $end2 = Carbon::now()->subDays(10)->setHour(18)->setMinute(0);
        $days2 = 5;
        $rate2 = (float) $veh2->daily_price_dzd;
        $startMil2 = 62000;
        $endMil2 = 62850;

        Contract::firstOrCreate(
            ['contract_number' => 'CTR-2026-0002'],
            [
                'customer_id' => $customer2->id,
                'vehicle_id' => $veh2->id,
                'branch_id' => $branchAlger->id,
                'return_branch_id' => $branchAlger->id,
                'agent_id' => $admin?->id,
                'status' => Contract::STATUS_COMPLETED,
                'start_datetime' => $start2,
                'end_datetime' => $end2,
                'actual_return_datetime' => $end2,
                'days_count' => $days2,
                'daily_rate_dzd' => $rate2,
                'total_amount_dzd' => $days2 * $rate2,
                'deposit_amount_dzd' => (float) $veh2->deposit_dzd,
                'deposit_type' => 'cheque',
                'deposit_reference' => 'BADR Chèque N° 77124901',
                'start_mileage' => $startMil2,
                'start_fuel_level' => '100%',
                'start_notes' => 'Départ conforme sans dommage apparent.',
                'start_inspected_at' => $start2,
                'end_mileage' => $endMil2,
                'end_fuel_level' => '100%',
                'end_notes' => 'Véhicule restitué en parfait état. Caution chèque restituée au client.',
                'end_inspected_at' => $end2,
                'signed_at' => $start2,
                'client_signature' => $mockSignature,
                'agent_signature' => $mockAgentSignature,
                'signer_ip' => '197.202.44.10',
                'signer_national_id' => $customer2->national_id_number,
                'clauses_accepted' => true,
            ]
        );

        // 3. Active Contract with Cash Deposit
        $start3 = Carbon::now()->subDay()->setHour(14)->setMinute(30);
        $end3 = Carbon::now()->addDays(6)->setHour(14)->setMinute(30);
        $days3 = 7;
        $rate3 = (float) $veh3->daily_price_dzd;

        Contract::firstOrCreate(
            ['contract_number' => 'CTR-2026-0003'],
            [
                'customer_id' => $customer3->id,
                'vehicle_id' => $veh3->id,
                'branch_id' => $branchAlger->id,
                'return_branch_id' => $branchAlger->id,
                'agent_id' => $admin?->id,
                'status' => Contract::STATUS_ACTIVE,
                'start_datetime' => $start3,
                'end_datetime' => $end3,
                'days_count' => $days3,
                'daily_rate_dzd' => $rate3,
                'total_amount_dzd' => $days3 * $rate3,
                'deposit_amount_dzd' => (float) $veh3->deposit_dzd,
                'deposit_type' => 'cash',
                'deposit_reference' => 'Reçu Espèces N° REC-2026-084',
                'start_mileage' => (int) ($veh3->mileage ?? 18000),
                'start_fuel_level' => '75%',
                'start_notes' => 'Légère micro-rayure pare-chocs arrière droit consignée sur la fiche d’état.',
                'start_inspected_at' => $start3,
                'signed_at' => $start3,
                'client_signature' => $mockSignature,
                'agent_signature' => $mockAgentSignature,
                'signer_ip' => '105.101.99.32',
                'signer_national_id' => $customer3->national_id_number,
                'clauses_accepted' => true,
            ]
        );
    }
}

