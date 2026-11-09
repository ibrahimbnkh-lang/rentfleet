<?php

declare(strict_types=1);

namespace Modules\M05Contracts\Http\Controllers;

use App\Http\Controllers\ApiController;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\M00Core\Services\AuditService;
use Modules\M01Fleet\Models\Vehicle;
use Modules\M02Customers\Models\Customer;
use Modules\M03Reservations\Models\Reservation;
use Modules\M05Contracts\Models\Contract;
use Modules\M05Contracts\Services\ContractService;

class ContractController extends ApiController
{
    public function __construct(
        private readonly ContractService $contractService,
        private readonly AuditService $auditService,
    ) {}

    /**
     * List contracts with filtering and KPI metrics.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = Contract::query()->with([
            'customer',
            'vehicle.category',
            'branch',
            'returnBranch',
            'agent',
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

        // Customer filter
        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->integer('customer_id'));
        }

        // Vehicle filter
        if ($request->filled('vehicle_id')) {
            $query->where('vehicle_id', $request->integer('vehicle_id'));
        }

        // Search in contract_number, customer name, phone, plate
        if ($request->filled('search')) {
            $search = '%' . $request->string('search') . '%';
            $query->where(function ($q) use ($search) {
                $q->where('contract_number', 'like', $search)
                  ->orWhereHas('customer', function ($cq) use ($search) {
                      $cq->where('first_name', 'like', $search)
                         ->orWhere('last_name', 'like', $search)
                         ->orWhere('company_name', 'like', $search)
                         ->orWhere('phone', 'like', $search)
                         ->orWhere('national_id_number', 'like', $search);
                  })
                  ->orWhereHas('vehicle', function ($vq) use ($search) {
                      $vq->where('brand', 'like', $search)
                         ->orWhere('model', 'like', $search)
                         ->orWhere('registration_plate', 'like', $search);
                  });
            });
        }

        // Metrics calculations
        $metricsQuery = clone $query;
        $totalContracts = (clone $metricsQuery)->count();
        $activeContracts = (clone $metricsQuery)->where('status', Contract::STATUS_ACTIVE)->count();
        $completedContracts = (clone $metricsQuery)->where('status', Contract::STATUS_COMPLETED)->count();
        $draftContracts = (clone $metricsQuery)->where('status', Contract::STATUS_DRAFT)->count();
        $totalRevenueDzd = (clone $metricsQuery)->whereNotIn('status', [Contract::STATUS_CANCELLED])->sum('total_amount_dzd');

        $contracts = $query->orderByDesc('created_at')->paginate($request->integer('per_page', 15));

        return $this->ok($contracts->items(), [
            'pagination' => [
                'current_page' => $contracts->currentPage(),
                'last_page' => $contracts->lastPage(),
                'per_page' => $contracts->perPage(),
                'total' => $contracts->total(),
            ],
            'metrics' => [
                'total_contracts' => $totalContracts,
                'active_contracts' => $activeContracts,
                'completed_contracts' => $completedContracts,
                'draft_contracts' => $draftContracts,
                'total_revenue_dzd' => (float) $totalRevenueDzd,
            ],
        ]);
    }

    /**
     * Create a new contract.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'vehicle_id' => 'required|exists:vehicles,id',
            'branch_id' => 'required|exists:branches,id',
            'return_branch_id' => 'nullable|exists:branches,id',
            'reservation_id' => 'nullable|exists:reservations,id',
            'start_datetime' => 'required|date',
            'end_datetime' => 'required|date|after:start_datetime',
            'daily_rate_dzd' => 'nullable|numeric|min:0',
            'deposit_amount_dzd' => 'nullable|numeric|min:0',
            'deposit_type' => 'nullable|string|in:cheque,cash,card,bank_transfer',
            'deposit_reference' => 'nullable|string|max:100',
            'start_mileage' => 'nullable|integer|min:0',
            'start_fuel_level' => 'nullable|string|max:16',
            'start_notes' => 'nullable|string|max:1000',
            'client_signature' => 'nullable|string',
            'agent_signature' => 'nullable|string',
            'status' => 'nullable|in:draft,active',
        ]);

        $contract = $this->contractService->createContract($validated, $request->user());

        return $this->created($contract->load(['customer', 'vehicle.category', 'branch', 'returnBranch', 'agent']));
    }

    /**
     * Show contract detail.
     */
    public function show(Contract $contract): JsonResponse
    {
        $contract->load([
            'customer.documents',
            'vehicle.category',
            'branch',
            'returnBranch',
            'agent',
            'reservation',
        ]);

        return $this->ok($contract);
    }

    /**
     * Update contract.
     */
    public function update(Request $request, Contract $contract): JsonResponse
    {
        $validated = $request->validate([
            'deposit_type' => 'sometimes|string|in:cheque,cash,card,bank_transfer',
            'deposit_reference' => 'nullable|string|max:100',
            'start_notes' => 'nullable|string|max:1000',
            'end_notes' => 'nullable|string|max:1000',
        ]);

        $contract->update($validated);

        $this->auditService->log('M05Contracts', 'contract.updated', $contract, null, $validated);

        return $this->ok($contract->fresh()->load(['customer', 'vehicle.category', 'branch']));
    }

    /**
     * E-Sign contract.
     */
    public function sign(Request $request, Contract $contract): JsonResponse
    {
        $validated = $request->validate([
            'client_signature' => 'required|string',
            'agent_signature' => 'nullable|string',
        ]);

        $contract = $this->contractService->signContract(
            $contract,
            $validated['client_signature'],
            $validated['agent_signature'] ?? null,
            $request->ip()
        );

        return $this->ok($contract->load(['customer', 'vehicle', 'branch']));
    }

    /**
     * Return inspection / check-out.
     */
    public function checkout(Request $request, Contract $contract): JsonResponse
    {
        $validated = $request->validate([
            'end_mileage' => 'required|integer|gte:' . $contract->start_mileage,
            'end_fuel_level' => 'required|string|max:16',
            'end_notes' => 'nullable|string|max:1000',
            'fuel_fee_dzd' => 'nullable|numeric|min:0',
            'extra_mileage_fee_dzd' => 'nullable|numeric|min:0',
            'damages_fee_dzd' => 'nullable|numeric|min:0',
        ]);

        $contract = $this->contractService->completeCheckout($contract, $validated, $request->user());

        return $this->ok($contract->load(['customer', 'vehicle', 'branch']));
    }

    /**
     * Create contract directly from an existing reservation.
     */
    public function createFromReservation(Request $request, Reservation $reservation): JsonResponse
    {
        $vehicle = $reservation->vehicle;

        $contractData = [
            'reservation_id' => $reservation->id,
            'customer_id' => $reservation->customer_id,
            'vehicle_id' => $reservation->vehicle_id,
            'branch_id' => $reservation->branch_id,
            'return_branch_id' => $reservation->return_branch_id ?? $reservation->branch_id,
            'start_datetime' => $reservation->pickup_datetime->toDateTimeString(),
            'end_datetime' => $reservation->return_datetime->toDateTimeString(),
            'daily_rate_dzd' => $reservation->daily_rate_dzd,
            'deposit_amount_dzd' => $reservation->deposit_amount_dzd,
            'deposit_type' => 'cheque',
            'start_mileage' => $vehicle ? $vehicle->mileage : 0,
            'start_fuel_level' => '100%',
            'start_notes' => $reservation->notes,
            'status' => Contract::STATUS_ACTIVE,
        ];

        $contract = $this->contractService->createContract($contractData, $request->user());

        return $this->created($contract->load(['customer', 'vehicle.category', 'branch', 'returnBranch', 'agent']));
    }

    /**
     * Delete contract.
     */
    public function destroy(Request $request, Contract $contract): JsonResponse
    {
        $this->auditService->log('M05Contracts', 'contract.deleted', $contract, ['contract_number' => $contract->contract_number]);

        $contract->delete();

        return $this->noContent();
    }
}
