<?php

declare(strict_types=1);

namespace Modules\M01Fleet\Http\Controllers;

use App\Http\Controllers\ApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Modules\M00Core\Services\AuditService;
use Modules\M01Fleet\Models\VehicleCategory;

class VehicleCategoryController extends ApiController
{
    public function __construct(
        private readonly AuditService $auditService,
    ) {}

    public function index(): JsonResponse
    {
        $categories = VehicleCategory::withCount('vehicles')->get();

        return $this->ok($categories);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'slug' => ['nullable', 'string', 'max:100', 'unique:vehicle_categories,slug'],
            'description' => ['nullable', 'string'],
            'daily_rate_dzd' => ['required', 'numeric', 'min:0'],
            'deposit_amount_dzd' => ['required', 'numeric', 'min:0'],
        ]);

        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        $category = VehicleCategory::create($validated);

        $this->auditService->log(
            module: 'M01Fleet',
            action: 'category.created',
            auditable: $category,
            newValues: $category->toArray()
        );

        return $this->created($category);
    }

    public function show(VehicleCategory $category): JsonResponse
    {
        $category->loadCount('vehicles');

        return $this->ok($category);
    }

    public function update(Request $request, VehicleCategory $category): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:100'],
            'slug' => ['nullable', 'string', 'max:100', 'unique:vehicle_categories,slug,' . $category->id],
            'description' => ['nullable', 'string'],
            'daily_rate_dzd' => ['sometimes', 'required', 'numeric', 'min:0'],
            'deposit_amount_dzd' => ['sometimes', 'required', 'numeric', 'min:0'],
        ]);

        $oldValues = $category->toArray();
        $category->update($validated);

        $this->auditService->log(
            module: 'M01Fleet',
            action: 'category.updated',
            auditable: $category,
            oldValues: $oldValues,
            newValues: $category->fresh()->toArray()
        );

        return $this->ok($category);
    }

    public function destroy(VehicleCategory $category): JsonResponse
    {
        $oldValues = $category->toArray();
        $category->delete();

        $this->auditService->log(
            module: 'M01Fleet',
            action: 'category.deleted',
            auditable: $category,
            oldValues: $oldValues
        );

        return $this->noContent();
    }
}
