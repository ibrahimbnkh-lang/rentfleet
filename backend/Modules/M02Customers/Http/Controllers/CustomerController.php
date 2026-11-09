<?php

declare(strict_types=1);

namespace Modules\M02Customers\Http\Controllers;

use App\Http\Controllers\ApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\M00Core\Services\AuditService;
use Modules\M02Customers\Models\Customer;
use Modules\M02Customers\Services\KycService;

class CustomerController extends ApiController
{
    public function __construct(
        private readonly AuditService $auditService,
        private readonly KycService $kycService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = Customer::query()->withCount('documents');

        if ($request->filled('search')) {
            $search = '%' . $request->input('search') . '%';
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', $search)
                    ->orWhere('last_name', 'like', $search)
                    ->orWhere('company_name', 'like', $search)
                    ->orWhere('phone', 'like', $search)
                    ->orWhere('email', 'like', $search)
                    ->orWhere('national_id_number', 'like', $search)
                    ->orWhere('driver_license_number', 'like', $search);
            });
        }

        if ($request->filled('type')) {
            $query->where('type', $request->input('type'));
        }

        if ($request->filled('wilaya_code')) {
            $query->where('wilaya_code', $request->input('wilaya_code'));
        }

        if ($request->filled('kyc_status')) {
            $query->where('kyc_status', $request->input('kyc_status'));
        }

        if ($request->filled('is_blacklisted')) {
            $query->where('is_blacklisted', $request->boolean('is_blacklisted'));
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $perPage = min($request->integer('per_page', 15), 100);
        $customers = $query->latest()->paginate($perPage);

        return $this->ok([
            'items' => $customers->items(),
            'meta'  => [
                'current_page' => $customers->currentPage(),
                'last_page'    => $customers->lastPage(),
                'per_page'     => $customers->perPage(),
                'total'        => $customers->total(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type' => ['sometimes', 'required', 'in:individual,company'],
            'first_name' => ['nullable', 'string', 'max:100'],
            'last_name' => ['nullable', 'string', 'max:100'],
            'company_name' => ['nullable', 'string', 'max:150'],
            'company_tax_id' => ['nullable', 'string', 'max:50'],
            'phone' => ['required', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:150'],
            'national_id_number' => ['nullable', 'string', 'max:30'],
            'driver_license_number' => ['nullable', 'string', 'max:50'],
            'driver_license_issued_at' => ['nullable', 'date'],
            'driver_license_wilaya' => ['nullable', 'string', 'max:10'],
            'birth_date' => ['nullable', 'date'],
            'birth_place' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'string'],
            'wilaya_code' => ['nullable', 'string', 'max:5'],
            'city' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
        ]);

        $customer = Customer::create($validated);

        $this->auditService->log(
            module: 'M02Customers',
            action: 'customer.created',
            auditable: $customer,
            newValues: $customer->toArray()
        );

        return $this->created($customer);
    }

    public function show(Customer $customer): JsonResponse
    {
        $customer->load(['documents', 'blacklists.reportedBy', 'blacklists.branch', 'verifiedBy']);

        $eligibility = $this->kycService->checkRentalEligibility($customer);

        return $this->ok([
            'customer' => $customer,
            'eligibility' => $eligibility,
        ]);
    }

    public function update(Request $request, Customer $customer): JsonResponse
    {
        $validated = $request->validate([
            'type' => ['sometimes', 'required', 'in:individual,company'],
            'first_name' => ['nullable', 'string', 'max:100'],
            'last_name' => ['nullable', 'string', 'max:100'],
            'company_name' => ['nullable', 'string', 'max:150'],
            'company_tax_id' => ['nullable', 'string', 'max:50'],
            'phone' => ['sometimes', 'required', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:150'],
            'national_id_number' => ['nullable', 'string', 'max:30'],
            'driver_license_number' => ['nullable', 'string', 'max:50'],
            'driver_license_issued_at' => ['nullable', 'date'],
            'driver_license_wilaya' => ['nullable', 'string', 'max:10'],
            'birth_date' => ['nullable', 'date'],
            'birth_place' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'string'],
            'wilaya_code' => ['nullable', 'string', 'max:5'],
            'city' => ['nullable', 'string', 'max:100'],
            'is_active' => ['sometimes', 'boolean'],
            'notes' => ['nullable', 'string'],
        ]);

        $oldValues = $customer->toArray();
        $customer->update($validated);

        $this->auditService->log(
            module: 'M02Customers',
            action: 'customer.updated',
            auditable: $customer,
            oldValues: $oldValues,
            newValues: $customer->fresh()->toArray()
        );

        return $this->ok($customer);
    }

    public function destroy(Customer $customer): JsonResponse
    {
        $oldValues = $customer->toArray();
        $customer->delete();

        $this->auditService->log(
            module: 'M02Customers',
            action: 'customer.deleted',
            auditable: $customer,
            oldValues: $oldValues
        );

        return $this->noContent();
    }

    public function isEligible(Customer $customer): JsonResponse
    {
        $eligibility = $this->kycService->checkRentalEligibility($customer);

        return $this->ok($eligibility);
    }
}
