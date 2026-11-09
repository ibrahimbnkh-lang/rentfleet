<?php

declare(strict_types=1);

namespace Modules\M00Core\Http\Controllers;

use App\Http\Controllers\ApiController;
use App\Services\ModuleLoader;
use Illuminate\Http\JsonResponse;
use Spatie\Permission\Models\Permission;

class PermissionController extends ApiController
{
    public function index(ModuleLoader $loader): JsonResponse
    {
        $permissions = Permission::all();

        // Group permissions by module prefix (e.g., core.*, fleet.*, customers.*)
        $grouped = [];

        foreach ($permissions as $permission) {
            $parts = explode('.', $permission->name);
            $moduleKey = $parts[0] ?? 'other';
            $resource = $parts[1] ?? 'general';

            $grouped[$moduleKey][$resource][] = [
                'id' => $permission->id,
                'name' => $permission->name,
                'action' => $parts[2] ?? $permission->name,
            ];
        }

        return $this->ok([
            'all' => $permissions,
            'grouped' => $grouped,
        ]);
    }
}
