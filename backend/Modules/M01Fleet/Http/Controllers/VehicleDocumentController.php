<?php

declare(strict_types=1);

namespace Modules\M01Fleet\Http\Controllers;

use App\Http\Controllers\ApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\M00Core\Services\AuditService;
use Modules\M01Fleet\Models\Vehicle;
use Modules\M01Fleet\Models\VehicleDocument;
use Modules\M01Fleet\Services\ComplianceService;

class VehicleDocumentController extends ApiController
{
    public function __construct(
        private readonly AuditService $auditService,
        private readonly ComplianceService $complianceService,
    ) {}

    public function index(Vehicle $vehicle): JsonResponse
    {
        $docs = $vehicle->documents()->latest()->get();

        return $this->ok($docs);
    }

    public function store(Request $request, Vehicle $vehicle): JsonResponse
    {
        $validated = $request->validate([
            'type' => ['required', 'string', 'in:assurance,controle_technique,carte_grise,vignette,autorisation'],
            'reference_number' => ['required', 'string', 'max:100'],
            'issuer' => ['nullable', 'string', 'max:100'],
            'issued_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date'],
            'file_path' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
        ]);

        $document = $vehicle->documents()->create($validated);

        $this->auditService->log(
            module: 'M01Fleet',
            action: 'vehicle_document.created',
            auditable: $document,
            newValues: $document->toArray()
        );

        return $this->created($document);
    }

    public function destroy(Vehicle $vehicle, VehicleDocument $document): JsonResponse
    {
        $oldValues = $document->toArray();
        $document->delete();

        $this->auditService->log(
            module: 'M01Fleet',
            action: 'vehicle_document.deleted',
            auditable: $document,
            oldValues: $oldValues
        );

        return $this->noContent();
    }

    public function expiring(Request $request): JsonResponse
    {
        $days = (int) $request->input('days', 30);
        $user = $request->user();

        $branchId = null;
        if ($user && ! $user->hasRole('Super Admin') && $user->branch_id) {
            $branchId = $user->branch_id;
        } elseif ($request->filled('branch_id')) {
            $branchId = $request->integer('branch_id');
        }

        $expiring = $this->complianceService->getExpiringDocuments($days, $branchId);

        return $this->ok($expiring);
    }
}
