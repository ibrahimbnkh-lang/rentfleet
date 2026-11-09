<?php

declare(strict_types=1);

namespace Modules\M02Customers\Http\Controllers;

use App\Http\Controllers\ApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\M00Core\Services\AuditService;
use Modules\M02Customers\Models\Customer;
use Modules\M02Customers\Models\CustomerDocument;

class CustomerDocumentController extends ApiController
{
    public function __construct(
        private readonly AuditService $auditService,
    ) {}

    public function index(Customer $customer): JsonResponse
    {
        $docs = $customer->documents()->with('reviewedBy')->latest()->get();

        return $this->ok($docs);
    }

    public function store(Request $request, Customer $customer): JsonResponse
    {
        $validated = $request->validate([
            'type' => ['required', 'string', 'in:cni_recto,cni_verso,permis_recto,permis_verso,passeport,justificatif_domicile,registre_commerce,autre'],
            'file_path' => ['required', 'string'],
            'file_name' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', 'in:pending,approved,rejected'],
            'notes' => ['nullable', 'string'],
        ]);

        $user = $request->user();

        $doc = $customer->documents()->create([
            'type' => $validated['type'],
            'file_path' => $validated['file_path'],
            'file_name' => $validated['file_name'] ?? basename($validated['file_path']),
            'status' => $validated['status'] ?? 'pending',
            'reviewed_by' => $user?->id,
            'reviewed_at' => isset($validated['status']) && $validated['status'] !== 'pending' ? now() : null,
            'notes' => $validated['notes'] ?? null,
        ]);

        $this->auditService->log(
            module: 'M02Customers',
            action: 'customer_document.created',
            auditable: $doc,
            newValues: $doc->toArray()
        );

        return $this->created($doc->load('reviewedBy'));
    }

    public function destroy(Customer $customer, CustomerDocument $document): JsonResponse
    {
        $oldValues = $document->toArray();
        $document->delete();

        $this->auditService->log(
            module: 'M02Customers',
            action: 'customer_document.deleted',
            auditable: $document,
            oldValues: $oldValues
        );

        return $this->noContent();
    }
}
