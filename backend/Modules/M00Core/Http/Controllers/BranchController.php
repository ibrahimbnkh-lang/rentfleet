<?php

declare(strict_types=1);

namespace Modules\M00Core\Http\Controllers;

use App\Http\Controllers\ApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\M00Core\Models\Branch;
use Modules\M00Core\Services\AuditService;
use Modules\M00Core\Services\LicenseService;
use Symfony\Component\HttpFoundation\Response;

class BranchController extends ApiController
{
    public function __construct(
        private readonly AuditService $auditService,
        private readonly LicenseService $licenseService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = Branch::query()->withCount('users');

        if ($request->filled('wilaya_code')) {
            $query->where('wilaya_code', $request->string('wilaya_code'));
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        if ($request->filled('search')) {
            $search = '%' . $request->string('search') . '%';
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', $search)
                  ->orWhere('code', 'like', $search)
                  ->orWhere('city', 'like', $search);
            });
        }

        $perPage = (int) $request->input('per_page', 15);
        $branches = $query->latest()->paginate($perPage);

        $activeLicense = $this->licenseService->getActiveLicense();
        $totalBranches = Branch::count();

        return $this->ok([
            'data' => $branches->items(),
            'meta' => [
                'current_page' => $branches->currentPage(),
                'per_page' => $branches->perPage(),
                'total' => $branches->total(),
                'last_page' => $branches->lastPage(),
                'quota' => $activeLicense ? [
                    'current' => $totalBranches,
                    'max' => $activeLicense->max_branches,
                    'is_reached' => $totalBranches >= $activeLicense->max_branches,
                ] : null,
            ],
            'links' => [
                'first' => $branches->url(1),
                'last' => $branches->url($branches->lastPage()),
                'prev' => $branches->previousPageUrl(),
                'next' => $branches->nextPageUrl(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $activeLicense = $this->licenseService->getActiveLicense();
        $totalBranches = Branch::count();

        if ($activeLicense && $totalBranches >= $activeLicense->max_branches) {
            return $this->error(
                code: 'license_quota_exceeded',
                message: "Limite de licence atteinte : votre licence autorise un maximum de {$activeLicense->max_branches} agence(s) (actuellement : {$totalBranches}). Veuillez mettre à niveau votre licence pour ajouter de nouvelles agences.",
                status: Response::HTTP_FORBIDDEN,
                details: [
                    'quota' => 'max_branches',
                    'current' => $totalBranches,
                    'max' => $activeLicense->max_branches,
                ]
            );
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'code' => ['required', 'string', 'max:20', 'unique:branches,code'],
            'wilaya_code' => ['required', 'string', 'max:3'],
            'city' => ['required', 'string', 'max:100'],
            'address' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:100'],
            'is_active' => ['boolean'],
        ]);

        $branch = Branch::create($validated);

        $this->auditService->log(
            module: 'M00Core',
            action: 'branch.created',
            auditable: $branch,
            newValues: $branch->toArray()
        );

        return $this->created($branch);
    }

    public function show(Branch $branch): JsonResponse
    {
        $branch->loadCount('users');

        return $this->ok($branch);
    }

    public function publicShow(string $code): JsonResponse
    {
        $branch = Branch::query()
            ->where('code', $code)
            ->orWhere('id', $code)
            ->firstOrFail();

        return $this->ok($branch);
    }

    public function update(Request $request, Branch $branch): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:150'],
            'code' => ['sometimes', 'required', 'string', 'max:20', 'unique:branches,code,' . $branch->id],
            'wilaya_code' => ['sometimes', 'required', 'string', 'max:3'],
            'city' => ['sometimes', 'required', 'string', 'max:100'],
            'address' => ['sometimes', 'required', 'string', 'max:255'],
            'phone' => ['sometimes', 'required', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:100'],
            'is_active' => ['boolean'],
        ]);

        $oldValues = $branch->toArray();
        $branch->update($validated);

        $this->auditService->log(
            module: 'M00Core',
            action: 'branch.updated',
            auditable: $branch,
            oldValues: $oldValues,
            newValues: $branch->fresh()->toArray()
        );

        return $this->ok($branch);
    }

    public function destroy(Branch $branch): JsonResponse
    {
        $oldValues = $branch->toArray();
        $branch->delete();

        $this->auditService->log(
            module: 'M00Core',
            action: 'branch.deleted',
            auditable: $branch,
            oldValues: $oldValues
        );

        return $this->noContent();
    }
}
