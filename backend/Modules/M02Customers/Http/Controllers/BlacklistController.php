<?php

declare(strict_types=1);

namespace Modules\M02Customers\Http\Controllers;

use App\Http\Controllers\ApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\M00Core\Services\AuditService;
use Modules\M02Customers\Models\Customer;
use Modules\M02Customers\Models\CustomerBlacklist;

class BlacklistController extends ApiController
{
    public function __construct(
        private readonly AuditService $auditService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = CustomerBlacklist::query()->with(['customer', 'reportedBy', 'branch']);

        if ($request->filled('severity')) {
            $query->where('severity', $request->input('severity'));
        }

        if ($request->filled('reason')) {
            $query->where('reason', $request->input('reason'));
        }

        $records = $query->latest()->paginate($request->integer('per_page', 20));

        return $this->ok([
            'items' => $records->items(),
            'meta'  => [
                'current_page' => $records->currentPage(),
                'last_page'    => $records->lastPage(),
                'per_page'     => $records->perPage(),
                'total'        => $records->total(),
            ],
        ]);
    }

    public function show(Customer $customer): JsonResponse
    {
        $history = $customer->blacklists()->with(['reportedBy', 'branch'])->latest()->get();

        return $this->ok([
            'is_blacklisted' => (bool) $customer->is_blacklisted,
            'incidents' => $history,
        ]);
    }

    public function add(Request $request, Customer $customer): JsonResponse
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'in:impayes,degradation,vol,comportement,infraction_grave,autre'],
            'severity' => ['required', 'string', 'in:low,medium,high'],
            'incident_date' => ['nullable', 'date'],
            'notes' => ['required', 'string', 'max:1000'],
            'branch_id' => ['nullable', 'exists:branches,id'],
        ]);

        $user = $request->user();

        $blacklistEntry = CustomerBlacklist::create([
            'customer_id' => $customer->id,
            'reason' => $validated['reason'],
            'severity' => $validated['severity'],
            'incident_date' => $validated['incident_date'] ?? now()->toDateString(),
            'reported_by' => $user?->id,
            'branch_id' => $validated['branch_id'] ?? $user?->branch_id,
            'notes' => $validated['notes'],
        ]);

        $customer->update(['is_blacklisted' => true]);

        $this->auditService->log(
            module: 'M02Customers',
            action: 'customer.blacklisted',
            auditable: $blacklistEntry,
            newValues: $blacklistEntry->toArray()
        );

        return $this->created([
            'message' => 'Client inscrit sur la liste noire RentFleet.',
            'entry' => $blacklistEntry->load(['reportedBy', 'branch']),
        ]);
    }

    public function remove(Request $request, Customer $customer): JsonResponse
    {
        $customer->update(['is_blacklisted' => false]);

        $this->auditService->log(
            module: 'M02Customers',
            action: 'customer.unblacklisted',
            auditable: $customer,
            newValues: ['is_blacklisted' => false]
        );

        return $this->ok([
            'message' => 'Client retiré de la liste noire.',
            'customer' => $customer->fresh(),
        ]);
    }
}
