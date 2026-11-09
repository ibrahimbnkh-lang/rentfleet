<?php

declare(strict_types=1);

namespace Modules\M03Reservations\Http\Controllers;

use App\Http\Controllers\ApiController;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\M00Core\Services\AuditService;
use Modules\M01Fleet\Models\Vehicle;
use Modules\M02Customers\Models\Customer;
use Modules\M03Reservations\Models\Reservation;
use Modules\M03Reservations\Services\ReservationAvailabilityService;

class ReservationController extends ApiController
{
    public function __construct(
        private readonly ReservationAvailabilityService $availabilityService,
        private readonly AuditService $auditService,
    ) {}

    /**
     * List reservations with filtering, pagination and overview metrics.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = Reservation::query()->with([
            'customer',
            'vehicle.category',
            'branch',
            'returnBranch',
        ]);

        // Branch scoping
        if ($user && ! $user->hasRole('Super Admin') && $user->branch_id) {
            $query->where('branch_id', $user->branch_id);
        } elseif ($request->filled('branch_id')) {
            $query->where('branch_id', $request->integer('branch_id'));
        }

        // Status filter
        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        // Vehicle filter
        if ($request->filled('vehicle_id')) {
            $query->where('vehicle_id', $request->integer('vehicle_id'));
        }

        // Customer filter
        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->integer('customer_id'));
        }

        // Date range filters
        if ($request->filled('from_date')) {
            $query->where('pickup_datetime', '>=', Carbon::parse((string) $request->input('from_date'))->startOfDay());
        }
        if ($request->filled('to_date')) {
            $query->where('return_datetime', '<=', Carbon::parse((string) $request->input('to_date'))->endOfDay());
        }

        // Search in reference_code, customer name, vehicle plate or model
        if ($request->filled('search')) {
            $search = '%' . $request->string('search') . '%';
            $query->where(function ($q) use ($search) {
                $q->where('reference_code', 'like', $search)
                  ->orWhereHas('customer', function ($cq) use ($search) {
                      $cq->where('first_name', 'like', $search)
                         ->orWhere('last_name', 'like', $search)
                         ->orWhere('company_name', 'like', $search)
                         ->orWhere('phone', 'like', $search);
                  })
                  ->orWhereHas('vehicle', function ($vq) use ($search) {
                      $vq->where('brand', 'like', $search)
                         ->orWhere('model', 'like', $search)
                         ->orWhere('registration_plate', 'like', $search);
                  });
            });
        }

        // Quick overview metrics calculation
        $metricsQuery = clone $query;
        $totalCount = (clone $metricsQuery)->count();
        $confirmedCount = (clone $metricsQuery)->where('status', Reservation::STATUS_CONFIRMED)->count();
        $inProgressCount = (clone $metricsQuery)->where('status', Reservation::STATUS_IN_PROGRESS)->count();
        $pendingCount = (clone $metricsQuery)->where('status', Reservation::STATUS_PENDING)->count();
        $totalRevenueDzd = (clone $metricsQuery)->whereNotIn('status', [Reservation::STATUS_CANCELLED])->sum('total_amount_dzd');

        $reservations = $query->orderByDesc('created_at')->paginate($request->integer('per_page', 15));

        return $this->ok($reservations->items(), [
            'pagination' => [
                'current_page' => $reservations->currentPage(),
                'last_page' => $reservations->lastPage(),
                'per_page' => $reservations->perPage(),
                'total' => $reservations->total(),
            ],
            'metrics' => [
                'total_count' => $totalCount,
                'confirmed_count' => $confirmedCount,
                'in_progress_count' => $inProgressCount,
                'pending_count' => $pendingCount,
                'total_revenue_dzd' => (float) $totalRevenueDzd,
            ],
        ]);
    }

    /**
     * Create a new reservation with vehicle availability check.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'vehicle_id' => 'required|exists:vehicles,id',
            'branch_id' => 'required|exists:branches,id',
            'return_branch_id' => 'nullable|exists:branches,id',
            'pickup_datetime' => 'required|date|after_or_equal:today',
            'return_datetime' => 'required|date|after:pickup_datetime',
            'status' => 'nullable|in:pending,confirmed,in_progress',
            'daily_rate_dzd' => 'nullable|numeric|min:0',
            'deposit_amount_dzd' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:1000',
        ]);

        $pickup = Carbon::parse($validated['pickup_datetime']);
        $return = Carbon::parse($validated['return_datetime']);

        // Check availability
        $availability = $this->availabilityService->isVehicleAvailable(
            (int) $validated['vehicle_id'],
            $pickup,
            $return
        );

        if (! $availability['available']) {
            return $this->badRequest($availability['reason'] ?? 'Le véhicule sélectionné n’est pas disponible pour ces dates.');
        }

        // Calculate rental days (minimum 1 day)
        $diffInHours = $pickup->diffInHours($return);
        $daysCount = max(1, (int) ceil($diffInHours / 24));

        $vehicle = Vehicle::findOrFail($validated['vehicle_id']);
        $dailyRate = isset($validated['daily_rate_dzd']) && $validated['daily_rate_dzd'] !== ''
            ? (float) $validated['daily_rate_dzd']
            : (float) $vehicle->daily_price_dzd;

        $totalAmount = $daysCount * $dailyRate;
        $deposit = isset($validated['deposit_amount_dzd']) && $validated['deposit_amount_dzd'] !== ''
            ? (float) $validated['deposit_amount_dzd']
            : (float) $vehicle->deposit_dzd;

        $status = $validated['status'] ?? Reservation::STATUS_CONFIRMED;

        $reservation = Reservation::create([
            'reference_code' => Reservation::generateReferenceCode(),
            'customer_id' => $validated['customer_id'],
            'vehicle_id' => $validated['vehicle_id'],
            'branch_id' => $validated['branch_id'],
            'return_branch_id' => $validated['return_branch_id'] ?? $validated['branch_id'],
            'created_by' => $request->user()?->id,
            'pickup_datetime' => $pickup,
            'return_datetime' => $return,
            'days_count' => $daysCount,
            'status' => $status,
            'daily_rate_dzd' => $dailyRate,
            'total_amount_dzd' => $totalAmount,
            'deposit_amount_dzd' => $deposit,
            'notes' => $validated['notes'] ?? null,
            'confirmed_at' => ($status === Reservation::STATUS_CONFIRMED) ? now() : null,
            'started_at' => ($status === Reservation::STATUS_IN_PROGRESS) ? now() : null,
        ]);

        // If directly started, mark vehicle rented
        if ($status === Reservation::STATUS_IN_PROGRESS) {
            $vehicle->update(['status' => 'rented']);
        }

        $this->auditService->log(
            module: 'M03Reservations',
            action: 'reservation.created',
            auditable: $reservation,
            newValues: [
                'reference_code' => $reservation->reference_code,
                'vehicle_id' => $vehicle->id,
                'customer_id' => $reservation->customer_id,
                'days' => $daysCount,
                'total_amount_dzd' => $totalAmount,
            ],
        );

        return $this->created($reservation->load(['customer', 'vehicle.category', 'branch', 'returnBranch']));
    }

    /**
     * Show reservation detail with customer KYC evaluation.
     */
    public function show(Reservation $reservation): JsonResponse
    {
        $reservation->load([
            'customer.documents',
            'customer.blacklists',
            'vehicle.category',
            'vehicle.photos',
            'branch',
            'returnBranch',
            'creator',
        ]);

        $eligibility = $this->availabilityService->checkCustomerEligibility($reservation->customer_id);

        return $this->ok([
            'reservation' => $reservation,
            'customer_eligibility' => $eligibility,
        ]);
    }

    /**
     * Update reservation details.
     */
    public function update(Request $request, Reservation $reservation): JsonResponse
    {
        $validated = $request->validate([
            'vehicle_id' => 'sometimes|required|exists:vehicles,id',
            'branch_id' => 'sometimes|required|exists:branches,id',
            'return_branch_id' => 'nullable|exists:branches,id',
            'pickup_datetime' => 'sometimes|required|date',
            'return_datetime' => 'sometimes|required|date|after:pickup_datetime',
            'daily_rate_dzd' => 'sometimes|nullable|numeric|min:0',
            'deposit_amount_dzd' => 'sometimes|nullable|numeric|min:0',
            'notes' => 'nullable|string|max:1000',
        ]);

        $pickup = isset($validated['pickup_datetime']) ? Carbon::parse($validated['pickup_datetime']) : $reservation->pickup_datetime;
        $return = isset($validated['return_datetime']) ? Carbon::parse($validated['return_datetime']) : $reservation->return_datetime;
        $vehicleId = isset($validated['vehicle_id']) ? (int) $validated['vehicle_id'] : $reservation->vehicle_id;

        // If dates or vehicle changed, check availability
        if ($reservation->vehicle_id !== $vehicleId || $reservation->pickup_datetime->ne($pickup) || $reservation->return_datetime->ne($return)) {
            $availability = $this->availabilityService->isVehicleAvailable($vehicleId, $pickup, $return, $reservation->id);
            if (! $availability['available']) {
                return $this->badRequest($availability['reason'] ?? 'Le véhicule sélectionné n’est pas disponible.');
            }
        }

        $diffInHours = $pickup->diffInHours($return);
        $daysCount = max(1, (int) ceil($diffInHours / 24));

        $dailyRate = isset($validated['daily_rate_dzd']) && $validated['daily_rate_dzd'] !== ''
            ? (float) $validated['daily_rate_dzd']
            : (float) $reservation->daily_rate_dzd;

        $totalAmount = $daysCount * $dailyRate;

        $reservation->update([
            'vehicle_id' => $vehicleId,
            'branch_id' => $validated['branch_id'] ?? $reservation->branch_id,
            'return_branch_id' => $validated['return_branch_id'] ?? $reservation->return_branch_id,
            'pickup_datetime' => $pickup,
            'return_datetime' => $return,
            'days_count' => $daysCount,
            'daily_rate_dzd' => $dailyRate,
            'total_amount_dzd' => $totalAmount,
            'deposit_amount_dzd' => isset($validated['deposit_amount_dzd']) ? (float) $validated['deposit_amount_dzd'] : $reservation->deposit_amount_dzd,
            'notes' => array_key_exists('notes', $validated) ? $validated['notes'] : $reservation->notes,
        ]);

        $this->auditService->log('M03Reservations', 'reservation.updated', $reservation, null, ['reference_code' => $reservation->reference_code]);

        return $this->ok($reservation->fresh()->load(['customer', 'vehicle.category', 'branch', 'returnBranch']));
    }

    /**
     * Confirm a pending reservation.
     */
    public function confirm(Request $request, Reservation $reservation): JsonResponse
    {
        if ($reservation->status === Reservation::STATUS_CANCELLED) {
            return $this->badRequest('Une réservation annulée ne peut pas être confirmée directement.');
        }

        $reservation->update([
            'status' => Reservation::STATUS_CONFIRMED,
            'confirmed_at' => now(),
        ]);

        $this->auditService->log('M03Reservations', 'reservation.confirmed', $reservation);

        return $this->ok($reservation->fresh());
    }

    /**
     * Start rental (vehicle handed over to customer).
     */
    public function start(Request $request, Reservation $reservation): JsonResponse
    {
        if ($reservation->status === Reservation::STATUS_CANCELLED) {
            return $this->badRequest('La réservation est annulée.');
        }

        // Verify KYC eligibility
        $eligibility = $this->availabilityService->checkCustomerEligibility($reservation->customer_id);
        if (! $eligibility['is_eligible'] && ! $request->boolean('force_start')) {
            return $this->badRequest('Le client n’est pas éligible à la location : ' . implode(' ', $eligibility['reasons']));
        }

        $reservation->update([
            'status' => Reservation::STATUS_IN_PROGRESS,
            'started_at' => now(),
        ]);

        // Update vehicle status
        $reservation->vehicle()->update(['status' => 'rented']);

        $this->auditService->log('M03Reservations', 'reservation.started', $reservation);

        return $this->ok($reservation->fresh()->load(['customer', 'vehicle']));
    }

    /**
     * Complete rental (vehicle returned).
     */
    public function complete(Request $request, Reservation $reservation): JsonResponse
    {
        $reservation->update([
            'status' => Reservation::STATUS_COMPLETED,
            'completed_at' => now(),
        ]);

        // Release vehicle back to available
        $reservation->vehicle()->update(['status' => 'available']);

        $this->auditService->log('M03Reservations', 'reservation.completed', $reservation);

        return $this->ok($reservation->fresh()->load(['customer', 'vehicle']));
    }

    /**
     * Cancel reservation.
     */
    public function cancel(Request $request, Reservation $reservation): JsonResponse
    {
        $validated = $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        $reservation->update([
            'status' => Reservation::STATUS_CANCELLED,
            'cancelled_at' => now(),
            'cancellation_reason' => $validated['reason'] ?? 'Annulée par l’opérateur',
        ]);

        // If vehicle was rented by this reservation, restore to available
        if ($reservation->vehicle->status === 'rented') {
            $reservation->vehicle()->update(['status' => 'available']);
        }

        $this->auditService->log('M03Reservations', 'reservation.cancelled', $reservation, null, [
            'reason' => $validated['reason'] ?? null,
        ]);

        return $this->ok($reservation->fresh());
    }

    /**
     * Delete reservation.
     */
    public function destroy(Request $request, Reservation $reservation): JsonResponse
    {
        $this->auditService->log('M03Reservations', 'reservation.deleted', $reservation, [
            'reference_code' => $reservation->reference_code,
        ]);

        $reservation->delete();

        return $this->noContent();
    }

    /**
     * Check vehicle availability or query all available vehicles for a date range.
     */
    public function checkAvailability(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'pickup_datetime' => 'required|date',
            'return_datetime' => 'required|date|after:pickup_datetime',
            'vehicle_id' => 'nullable|exists:vehicles,id',
            'branch_id' => 'nullable|exists:branches,id',
            'category_id' => 'nullable|exists:vehicle_categories,id',
            'exclude_reservation_id' => 'nullable|integer',
        ]);

        $pickup = Carbon::parse($validated['pickup_datetime']);
        $return = Carbon::parse($validated['return_datetime']);

        if (! empty($validated['vehicle_id'])) {
            $result = $this->availabilityService->isVehicleAvailable(
                (int) $validated['vehicle_id'],
                $pickup,
                $return,
                $validated['exclude_reservation_id'] ?? null
            );
            return $this->ok($result);
        }

        $availableVehicles = $this->availabilityService->getAvailableVehicles(
            $validated['branch_id'] ?? null,
            $pickup,
            $return,
            $validated['category_id'] ?? null,
            $validated['exclude_reservation_id'] ?? null
        );

        return $this->ok($availableVehicles);
    }

    /**
     * Timeline data endpoint for fleet planning calendar.
     */
    public function timeline(Request $request): JsonResponse
    {
        $user = $request->user();
        $startDate = $request->filled('start_date')
            ? Carbon::parse((string) $request->input('start_date'))->startOfDay()
            : now()->startOfMonth();

        $endDate = $request->filled('end_date')
            ? Carbon::parse((string) $request->input('end_date'))->endOfDay()
            : (clone $startDate)->addDays(30)->endOfDay();

        $branchId = null;
        if ($user && ! $user->hasRole('Super Admin') && $user->branch_id) {
            $branchId = $user->branch_id;
        } elseif ($request->filled('branch_id')) {
            $branchId = $request->integer('branch_id');
        }

        // Vehicles in branch
        $vehiclesQuery = Vehicle::query()
            ->with(['category', 'branch'])
            ->where('is_active', true);

        if ($branchId) {
            $vehiclesQuery->where('branch_id', $branchId);
        }

        if ($request->filled('category_id')) {
            $vehiclesQuery->where('category_id', $request->integer('category_id'));
        }

        $vehicles = $vehiclesQuery->orderBy('brand')->orderBy('model')->get();

        // Reservations overlapping this period
        $vehicleIds = $vehicles->pluck('id')->toArray();

        $reservations = Reservation::query()
            ->whereIn('vehicle_id', $vehicleIds)
            ->whereNotIn('status', [Reservation::STATUS_CANCELLED])
            ->where(function ($q) use ($startDate, $endDate) {
                $q->where('pickup_datetime', '<=', $endDate)
                  ->where('return_datetime', '>=', $startDate);
            })
            ->with(['customer', 'branch'])
            ->get();

        return $this->ok([
            'start_date' => $startDate->toDateString(),
            'end_date' => $endDate->toDateString(),
            'days_count' => $startDate->diffInDays($endDate) + 1,
            'vehicles' => $vehicles,
            'reservations' => $reservations,
        ]);
    }
}

