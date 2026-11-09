<?php

declare(strict_types=1);

namespace Modules\M00Core\Http\Controllers;

use App\Http\Controllers\ApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\M00Core\Services\AuditService;
use Spatie\Permission\Models\Role;

class RoleController extends ApiController
{
    public function __construct(
        private readonly AuditService $auditService,
    ) {}

    public function index(): JsonResponse
    {
        $roles = Role::with('permissions')->get();

        return $this->ok($roles);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:50', 'unique:roles,name'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ]);

        $role = Role::create([
            'name' => $validated['name'],
            'guard_name' => 'web',
        ]);

        if (! empty($validated['permissions'])) {
            $role->syncPermissions($validated['permissions']);
        }

        $role->load('permissions');

        $this->auditService->log(
            module: 'M00Core',
            action: 'role.created',
            auditable: $role,
            newValues: ['name' => $role->name, 'permissions' => $role->permissions->pluck('name')]
        );

        return $this->created($role);
    }

    public function show(Role $role): JsonResponse
    {
        $role->load('permissions');

        return $this->ok($role);
    }

    public function update(Request $request, Role $role): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:50', 'unique:roles,name,' . $role->id],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ]);

        $oldValues = [
            'name' => $role->name,
            'permissions' => $role->permissions->pluck('name')->toArray(),
        ];

        if (! empty($validated['name'])) {
            $role->name = $validated['name'];
            $role->save();
        }

        if (array_key_exists('permissions', $validated)) {
            $role->syncPermissions($validated['permissions'] ?? []);
        }

        $role->load('permissions');

        $this->auditService->log(
            module: 'M00Core',
            action: 'role.updated',
            auditable: $role,
            oldValues: $oldValues,
            newValues: [
                'name' => $role->name,
                'permissions' => $role->permissions->pluck('name')->toArray(),
            ]
        );

        return $this->ok($role);
    }

    public function destroy(Role $role): JsonResponse
    {
        if ($role->name === 'Super Admin') {
            return $this->error('cannot_delete_super_admin', 'Le rôle Super Admin ne peut pas être supprimé.', 400);
        }

        $oldValues = ['id' => $role->id, 'name' => $role->name];
        $role->delete();

        $this->auditService->log(
            module: 'M00Core',
            action: 'role.deleted',
            auditable: $role,
            oldValues: $oldValues
        );

        return $this->noContent();
    }
}
