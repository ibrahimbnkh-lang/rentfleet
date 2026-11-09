<?php

declare(strict_types=1);

namespace Modules\M03Reservations\Database\Seeders;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Modules\M00Core\Models\Branch;
use Modules\M01Fleet\Models\Vehicle;
use Modules\M02Customers\Models\Customer;
use Modules\M03Reservations\Models\Reservation;

class M03ReservationsSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::first();
        $branchAlger = Branch::where('code', 'ALG-01')->first() ?? Branch::first();
        $branchOran = Branch::where('code', 'ORN-01')->first() ?? $branchAlger;

        $vehicles = Vehicle::all()->keyBy('registration_plate');
        $customers = Customer::all();

        if ($customers->isEmpty() || $vehicles->isEmpty()) {
            return;
        }

        $customer1 = $customers->first(); // Nadir Larbi
        $customer2 = $customers->count() > 1 ? $customers->get(1) : $customer1;
        $customer3 = $customers->count() > 2 ? $customers->get(2) : $customer1;
        $customer4 = $customers->count() > 3 ? $customers->get(3) : $customer2;

        $vehicleList = $vehicles->values();
        $veh1 = $vehicleList->get(0);
        $veh2 = $vehicleList->count() > 1 ? $vehicleList->get(1) : $veh1;
        $veh3 = $vehicleList->count() > 2 ? $vehicleList->get(2) : $veh1;
        $veh4 = $vehicleList->count() > 3 ? $vehicleList->get(3) : $veh2;
        $veh5 = $vehicleList->count() > 4 ? $vehicleList->get(4) : $veh3;

        // 1. In-progress rental (started 2 days ago, returns in 3 days)
        $pickup1 = Carbon::now()->subDays(2)->setHour(10)->setMinute(0);
        $return1 = Carbon::now()->addDays(3)->setHour(10)->setMinute(0);
        $days1 = 5;
        $rate1 = (float) $veh1->daily_price_dzd;
        Reservation::firstOrCreate(
            ['reference_code' => 'RES-2026-0001'],
            [
                'customer_id' => $customer1->id,
                'vehicle_id' => $veh1->id,
                'branch_id' => $branchAlger->id,
                'return_branch_id' => $branchAlger->id,
                'created_by' => $admin?->id,
                'pickup_datetime' => $pickup1,
                'return_datetime' => $return1,
                'days_count' => $days1,
                'status' => Reservation::STATUS_IN_PROGRESS,
                'daily_rate_dzd' => $rate1,
                'total_amount_dzd' => $days1 * $rate1,
                'deposit_amount_dzd' => (float) $veh1->deposit_dzd,
                'notes' => 'Location touristique Alger - Tipaza. Caution versée par chèque de garantie.',
                'confirmed_at' => Carbon::now()->subDays(5),
                'started_at' => $pickup1,
            ]
        );
        $veh1->update(['status' => 'rented']);

        // 2. Confirmed upcoming booking (starts tomorrow for 4 days)
        $pickup2 = Carbon::now()->addDay()->setHour(9)->setMinute(0);
        $return2 = Carbon::now()->addDays(5)->setHour(18)->setMinute(0);
        $days2 = 4;
        $rate2 = (float) $veh2->daily_price_dzd;
        Reservation::firstOrCreate(
            ['reference_code' => 'RES-2026-0002'],
            [
                'customer_id' => $customer2->id,
                'vehicle_id' => $veh2->id,
                'branch_id' => $branchAlger->id,
                'return_branch_id' => $branchAlger->id,
                'created_by' => $admin?->id,
                'pickup_datetime' => $pickup2,
                'return_datetime' => $return2,
                'days_count' => $days2,
                'status' => Reservation::STATUS_CONFIRMED,
                'daily_rate_dzd' => $rate2,
                'total_amount_dzd' => $days2 * $rate2,
                'deposit_amount_dzd' => (float) $veh2->deposit_dzd,
                'notes' => 'Départ pour mission professionnelle à Béjaïa. Siège bébé demandé.',
                'confirmed_at' => Carbon::now()->subDay(),
            ]
        );

        // 3. Confirmed upcoming booking for vehicle 3 (starts in 3 days, 6 days)
        $pickup3 = Carbon::now()->addDays(3)->setHour(14)->setMinute(0);
        $return3 = Carbon::now()->addDays(9)->setHour(12)->setMinute(0);
        $days3 = 6;
        $rate3 = (float) $veh3->daily_price_dzd;
        Reservation::firstOrCreate(
            ['reference_code' => 'RES-2026-0003'],
            [
                'customer_id' => $customer3->id,
                'vehicle_id' => $veh3->id,
                'branch_id' => $branchOran->id,
                'return_branch_id' => $branchOran->id,
                'created_by' => $admin?->id,
                'pickup_datetime' => $pickup3,
                'return_datetime' => $return3,
                'days_count' => $days3,
                'status' => Reservation::STATUS_CONFIRMED,
                'daily_rate_dzd' => $rate3,
                'total_amount_dzd' => $days3 * $rate3,
                'deposit_amount_dzd' => (float) $veh3->deposit_dzd,
                'notes' => 'Client régulier. Réservation confirmée avec acompte de 20 000 DZD en espèces.',
                'confirmed_at' => Carbon::now()->subHours(6),
            ]
        );

        // 4. Pending booking for vehicle 4 (starts next week)
        $pickup4 = Carbon::now()->addDays(8)->setHour(8)->setMinute(30);
        $return4 = Carbon::now()->addDays(12)->setHour(19)->setMinute(0);
        $days4 = 4;
        $rate4 = (float) $veh4->daily_price_dzd;
        Reservation::firstOrCreate(
            ['reference_code' => 'RES-2026-0004'],
            [
                'customer_id' => $customer4->id,
                'vehicle_id' => $veh4->id,
                'branch_id' => $branchAlger->id,
                'return_branch_id' => $branchAlger->id,
                'created_by' => $admin?->id,
                'pickup_datetime' => $pickup4,
                'return_datetime' => $return4,
                'days_count' => $days4,
                'status' => Reservation::STATUS_PENDING,
                'daily_rate_dzd' => $rate4,
                'total_amount_dzd' => $days4 * $rate4,
                'deposit_amount_dzd' => (float) $veh4->deposit_dzd,
                'notes' => 'En attente de validation du dossier KYC complet avant confirmation.',
            ]
        );

        // 5. Past completed booking for vehicle 5
        $pickup5 = Carbon::now()->subDays(10)->setHour(9)->setMinute(0);
        $return5 = Carbon::now()->subDays(6)->setHour(17)->setMinute(0);
        $days5 = 4;
        $rate5 = (float) $veh5->daily_price_dzd;
        Reservation::firstOrCreate(
            ['reference_code' => 'RES-2026-0005'],
            [
                'customer_id' => $customer1->id,
                'vehicle_id' => $veh5->id,
                'branch_id' => $branchAlger->id,
                'return_branch_id' => $branchAlger->id,
                'created_by' => $admin?->id,
                'pickup_datetime' => $pickup5,
                'return_datetime' => $return5,
                'days_count' => $days5,
                'status' => Reservation::STATUS_COMPLETED,
                'daily_rate_dzd' => $rate5,
                'total_amount_dzd' => $days5 * $rate5,
                'deposit_amount_dzd' => (float) $veh5->deposit_dzd,
                'notes' => 'Restitution effectuée en bon état. Kilométrage respecté.',
                'confirmed_at' => Carbon::now()->subDays(12),
                'started_at' => $pickup5,
                'completed_at' => $return5,
            ]
        );
    }
}

