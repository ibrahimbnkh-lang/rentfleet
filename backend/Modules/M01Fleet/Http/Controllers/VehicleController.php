<?php

declare(strict_types=1);

namespace Modules\M01Fleet\Http\Controllers;

use App\Http\Controllers\ApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\M00Core\Services\AuditService;
use Modules\M00Core\Services\LicenseService;
use Modules\M01Fleet\Models\Vehicle;
use Modules\M01Fleet\Models\VehicleEquipment;
use Modules\M01Fleet\Services\ComplianceService;
use Symfony\Component\HttpFoundation\Response;

class VehicleController extends ApiController
{
    public function __construct(
        private readonly AuditService $auditService,
        private readonly ComplianceService $complianceService,
        private readonly LicenseService $licenseService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = Vehicle::query()->with(['category', 'branch', 'photos']);

        // Auto-scope to manager's branch if user is not Super Admin
        if ($user && ! $user->hasRole('Super Admin') && $user->branch_id) {
            $query->where('branch_id', $user->branch_id);
        } elseif ($request->filled('branch_id')) {
            $query->where('branch_id', $request->integer('branch_id'));
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->integer('category_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('fuel_type')) {
            $query->where('fuel_type', $request->string('fuel_type'));
        }

        if ($request->filled('transmission')) {
            $query->where('transmission', $request->string('transmission'));
        }

        if ($request->filled('search')) {
            $search = '%' . $request->string('search') . '%';
            $query->where(function ($q) use ($search) {
                $q->where('brand', 'like', $search)
                  ->orWhere('model', 'like', $search)
                  ->orWhere('registration_plate', 'like', $search)
                  ->orWhere('vin', 'like', $search);
            });
        }

        $perPage = (int) $request->input('per_page', 15);
        $vehicles = $query->latest()->paginate($perPage);
        $activeLicense = $this->licenseService->getActiveLicense();
        $totalVehicles = Vehicle::count();

        return $this->ok([
            'data' => $vehicles->items(),
            'meta' => [
                'current_page' => $vehicles->currentPage(),
                'per_page' => $vehicles->perPage(),
                'total' => $vehicles->total(),
                'last_page' => $vehicles->lastPage(),
                'quota' => $activeLicense ? [
                    'current' => $totalVehicles,
                    'max' => $activeLicense->max_vehicles,
                    'is_reached' => $totalVehicles >= $activeLicense->max_vehicles,
                ] : null,
            ],
            'links' => [
                'first' => $vehicles->url(1),
                'last' => $vehicles->url($vehicles->lastPage()),
                'prev' => $vehicles->previousPageUrl(),
                'next' => $vehicles->nextPageUrl(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $activeLicense = $this->licenseService->getActiveLicense();
        $totalVehicles = Vehicle::count();

        if ($activeLicense && $totalVehicles >= $activeLicense->max_vehicles) {
            return $this->error(
                code: 'license_quota_exceeded',
                message: "Limite de licence atteinte : votre licence autorise un maximum de {$activeLicense->max_vehicles} véhicule(s) (actuellement : {$totalVehicles}). Veuillez mettre à niveau votre licence pour ajouter de nouveaux véhicules.",
                status: Response::HTTP_FORBIDDEN,
                details: [
                    'quota' => 'max_vehicles',
                    'current' => $totalVehicles,
                    'max' => $activeLicense->max_vehicles,
                ]
            );
        }

        $validated = $request->validate([
            'branch_id' => ['required', 'exists:branches,id'],
            'category_id' => ['required', 'exists:vehicle_categories,id'],
            'brand' => ['required', 'string', 'max:100'],
            'model' => ['required', 'string', 'max:100'],
            'generation' => ['nullable', 'string', 'max:50'],
            'year' => ['required', 'integer', 'min:2000', 'max:' . (date('Y') + 1)],
            'registration_plate' => ['required', 'string', 'max:30', 'unique:vehicles,registration_plate'],
            'vin' => ['required', 'string', 'max:50', 'unique:vehicles,vin'],
            'color' => ['nullable', 'string', 'max:50'],
            'fuel_type' => ['required', 'string', 'in:essence,diesel,hybride,gpl,electrique'],
            'transmission' => ['required', 'string', 'in:manuelle,automatique'],
            'seats' => ['nullable', 'integer', 'min:1', 'max:15'],
            'doors' => ['nullable', 'integer', 'min:2', 'max:7'],
            'mileage' => ['required', 'integer', 'min:0'],
            'daily_price_dzd' => ['nullable', 'numeric', 'min:0'],
            'deposit_dzd' => ['nullable', 'numeric', 'min:0'],
            'status' => ['nullable', 'string', 'in:available,rented,maintenance,reserved,out_of_service'],
            'notes' => ['nullable', 'string'],
        ]);

        $vehicle = Vehicle::create($validated);

        // Initialize standard Algerian safety equipment checklist
        $standardEquipment = [
            'Roue de secours',
            'Cric & Manivelle',
            'Gilet de sécurité & Triangle',
            'Trousse de premiers secours',
            'Extincteur conforme',
        ];

        foreach ($standardEquipment as $eqName) {
            VehicleEquipment::create([
                'vehicle_id' => $vehicle->id,
                'name' => $eqName,
                'is_present' => true,
                'condition' => 'bon_etat',
            ]);
        }

        $this->auditService->log(
            module: 'M01Fleet',
            action: 'vehicle.created',
            auditable: $vehicle,
            newValues: $vehicle->toArray()
        );

        $vehicle->load(['category', 'branch', 'equipment']);

        return $this->created($vehicle);
    }

    public function show(Vehicle $vehicle): JsonResponse
    {
        $vehicle->load(['category', 'branch', 'documents', 'photos', 'equipment']);

        $compliance = $this->complianceService->checkCompliance($vehicle);

        return $this->ok([
            'vehicle' => $vehicle,
            'compliance' => $compliance,
        ]);
    }

    public function update(Request $request, Vehicle $vehicle): JsonResponse
    {
        $validated = $request->validate([
            'branch_id' => ['sometimes', 'required', 'exists:branches,id'],
            'category_id' => ['sometimes', 'required', 'exists:vehicle_categories,id'],
            'brand' => ['sometimes', 'required', 'string', 'max:100'],
            'model' => ['sometimes', 'required', 'string', 'max:100'],
            'generation' => ['nullable', 'string', 'max:50'],
            'year' => ['sometimes', 'required', 'integer', 'min:2000', 'max:' . (date('Y') + 1)],
            'registration_plate' => ['sometimes', 'required', 'string', 'max:30', 'unique:vehicles,registration_plate,' . $vehicle->id],
            'vin' => ['sometimes', 'required', 'string', 'max:50', 'unique:vehicles,vin,' . $vehicle->id],
            'color' => ['nullable', 'string', 'max:50'],
            'fuel_type' => ['sometimes', 'required', 'string', 'in:essence,diesel,hybride,gpl,electrique'],
            'transmission' => ['sometimes', 'required', 'string', 'in:manuelle,automatique'],
            'seats' => ['nullable', 'integer', 'min:1', 'max:15'],
            'doors' => ['nullable', 'integer', 'min:2', 'max:7'],
            'mileage' => ['sometimes', 'required', 'integer', 'min:0'],
            'daily_price_dzd' => ['nullable', 'numeric', 'min:0'],
            'deposit_dzd' => ['nullable', 'numeric', 'min:0'],
            'status' => ['sometimes', 'required', 'string', 'in:available,rented,maintenance,reserved,out_of_service'],
            'notes' => ['nullable', 'string'],
        ]);

        $oldValues = $vehicle->toArray();
        $vehicle->update($validated);

        $this->auditService->log(
            module: 'M01Fleet',
            action: 'vehicle.updated',
            auditable: $vehicle,
            oldValues: $oldValues,
            newValues: $vehicle->fresh()->toArray()
        );

        $vehicle->load(['category', 'branch']);

        return $this->ok($vehicle);
    }

    public function destroy(Vehicle $vehicle): JsonResponse
    {
        $oldValues = $vehicle->toArray();
        $vehicle->delete();

        $this->auditService->log(
            module: 'M01Fleet',
            action: 'vehicle.deleted',
            auditable: $vehicle,
            oldValues: $oldValues
        );

        return $this->noContent();
    }

    public function isRentable(Vehicle $vehicle): JsonResponse
    {
        $compliance = $this->complianceService->checkCompliance($vehicle);
        $rentable = $vehicle->isRentable();

        return $this->ok([
            'is_rentable' => $rentable,
            'status' => $vehicle->status,
            'compliance' => $compliance,
        ]);
    }

    public function publicAgencyFleet(string $code): JsonResponse
    {
        $branch = \Modules\M00Core\Models\Branch::query()
            ->where('code', $code)
            ->orWhere('id', $code)
            ->firstOrFail();

        $vehicles = Vehicle::query()
            ->with(['category', 'photos'])
            ->where('branch_id', $branch->id)
            ->where('is_active', true)
            ->get();

        $branches = \Modules\M00Core\Models\Branch::query()
            ->where('is_active', true)
            ->select(['id', 'code', 'name', 'city'])
            ->get();

        return $this->ok([
            'branch' => $branch,
            'vehicles' => $vehicles,
            'branches' => $branches,
        ]);
    }
}
