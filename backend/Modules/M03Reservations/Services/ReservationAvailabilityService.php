<?php

declare(strict_types=1);

namespace Modules\M03Reservations\Services;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Modules\M01Fleet\Models\Vehicle;
use Modules\M02Customers\Models\Customer;
use Modules\M02Customers\Services\KycService;
use Modules\M03Reservations\Models\Reservation;

class ReservationAvailabilityService
{
    public function __construct(
        private readonly KycService $kycService,
    ) {}

    /**
     * Check if a specific vehicle is available for the given dates.
     *
     * @return array{
     *     available: bool,
     *     reason: ?string,
     *     conflicts: array<int, array<string, mixed>>,
     * }
     */
    public function isVehicleAvailable(
        int $vehicleId,
        Carbon|string $pickup,
        Carbon|string $return,
        ?int $excludeReservationId = null
    ): array {
        $vehicle = Vehicle::find($vehicleId);

        if (! $vehicle) {
            return [
                'available' => false,
                'reason' => 'Véhicule introuvable.',
                'conflicts' => [],
            ];
        }

        if (! $vehicle->is_active) {
            return [
                'available' => false,
                'reason' => "Le véhicule {$vehicle->brand} {$vehicle->model} est désactivé du parc.",
                'conflicts' => [],
            ];
        }

        if (in_array($vehicle->status, ['maintenance', 'out_of_service'], true)) {
            $statusLabel = $vehicle->status === 'maintenance' ? 'en maintenance technique' : 'hors service';
            return [
                'available' => false,
                'reason' => "Le véhicule est actuellement {$statusLabel}.",
                'conflicts' => [],
            ];
        }

        // Check overlapping reservations
        $overlapping = Reservation::query()
            ->where('vehicle_id', $vehicleId)
            ->overlapping($pickup, $return, $excludeReservationId)
            ->with(['customer'])
            ->get();

        if ($overlapping->isNotEmpty()) {
            $first = $overlapping->first();
            $clientName = $first->customer ? $first->customer->full_name : 'un autre client';
            $dates = $first->pickup_datetime->format('d/m/Y H:i') . ' au ' . $first->return_datetime->format('d/m/Y H:i');

            return [
                'available' => false,
                'reason' => "Conflit avec la réservation {$first->reference_code} ({$clientName}) du {$dates}.",
                'conflicts' => $overlapping->map(fn ($r) => [
                    'id' => $r->id,
                    'reference_code' => $r->reference_code,
                    'pickup' => $r->pickup_datetime->toIso8601String(),
                    'return' => $r->return_datetime->toIso8601String(),
                    'status' => $r->status,
                ])->all(),
            ];
        }

        return [
            'available' => true,
            'reason' => null,
            'conflicts' => [],
        ];
    }

    /**
     * Get all vehicles available for the specified date range and branch.
     *
     * @return Collection<int, Vehicle>
     */
    public function getAvailableVehicles(
        ?int $branchId,
        Carbon|string $pickup,
        Carbon|string $return,
        ?int $categoryId = null,
        ?int $excludeReservationId = null
    ): Collection {
        $p = $pickup instanceof Carbon ? $pickup : Carbon::parse($pickup);
        $r = $return instanceof Carbon ? $return : Carbon::parse($return);

        // Subquery of vehicle IDs that have overlapping reservations
        $busyVehicleIds = Reservation::query()
            ->overlapping($p, $r, $excludeReservationId)
            ->pluck('vehicle_id')
            ->toArray();

        return Vehicle::query()
            ->where('is_active', true)
            ->whereNotIn('status', ['maintenance', 'out_of_service'])
            ->whereNotIn('id', $busyVehicleIds)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->when($categoryId, fn ($q) => $q->where('category_id', $categoryId))
            ->with(['category', 'branch', 'photos'])
            ->orderBy('brand')
            ->orderBy('model')
            ->get();
    }

    /**
     * Check customer eligibility for reservation.
     *
     * @return array{
     *     is_eligible: bool,
     *     reasons: array<int, string>,
     *     criteria: array<string, mixed>,
     * }
     */
    public function checkCustomerEligibility(int $customerId): array
    {
        $customer = Customer::find($customerId);

        if (! $customer) {
            return [
                'is_eligible' => false,
                'reasons' => ['Client introuvable.'],
                'criteria' => [],
            ];
        }

        return $this->kycService->checkRentalEligibility($customer);
    }
}

