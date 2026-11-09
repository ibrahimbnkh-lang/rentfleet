<?php

declare(strict_types=1);

namespace Modules\M02Customers\Http\Controllers;

use App\Http\Controllers\ApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\M00Core\Services\AuditService;
use Modules\M02Customers\Models\Customer;
use Modules\M02Customers\Services\KycService;

class KycController extends ApiController
{
    public function __construct(
        private readonly AuditService $auditService,
        private readonly KycService $kycService,
    ) {}

    public function show(Customer $customer): JsonResponse
    {
        $customer->load(['documents', 'verifiedBy']);
        $eligibility = $this->kycService->checkRentalEligibility($customer);

        return $this->ok([
            'customer' => $customer,
            'kyc_status' => $customer->kyc_status,
            'eligibility' => $eligibility,
        ]);
    }

    public function verify(Request $request, Customer $customer): JsonResponse
    {
        $user = $request->user();
        $oldValues = $customer->toArray();

        $customer->update([
            'kyc_status' => 'verified',
            'kyc_verified_at' => now(),
            'kyc_verified_by' => $user?->id,
            'kyc_rejection_reason' => null,
        ]);

        $this->auditService->log(
            module: 'M02Customers',
            action: 'customer.kyc_verified',
            auditable: $customer,
            oldValues: $oldValues,
            newValues: $customer->fresh()->toArray()
        );

        return $this->ok([
            'message' => 'Dossier KYC validé avec succès.',
            'customer' => $customer->fresh(['verifiedBy']),
        ]);
    }

    public function reject(Request $request, Customer $customer): JsonResponse
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);

        $user = $request->user();
        $oldValues = $customer->toArray();

        $customer->update([
            'kyc_status' => 'rejected',
            'kyc_verified_at' => now(),
            'kyc_verified_by' => $user?->id,
            'kyc_rejection_reason' => $validated['reason'],
        ]);

        $this->auditService->log(
            module: 'M02Customers',
            action: 'customer.kyc_rejected',
            auditable: $customer,
            oldValues: $oldValues,
            newValues: $customer->fresh()->toArray()
        );

        return $this->ok([
            'message' => 'Dossier KYC rejeté.',
            'customer' => $customer->fresh(['verifiedBy']),
        ]);
    }
}
