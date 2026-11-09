<?php

declare(strict_types=1);

namespace Modules\M00Core\Http\Controllers;

use App\Http\Controllers\ApiController;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Modules\M00Core\Services\AuditService;

class UserController extends ApiController
{
    public function __construct(
        private readonly AuditService $auditService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = User::query()->with(['branch', 'roles']);

        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->integer('branch_id'));
        }

        if ($request->filled('role')) {
            $query->role($request->string('role'));
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        if ($request->filled('search')) {
            $search = '%' . $request->string('search') . '%';
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', $search)
                  ->orWhere('email', 'like', $search)
                  ->orWhere('phone', 'like', $search);
            });
        }

        $perPage = (int) $request->input('per_page', 15);
        $users = $query->latest()->paginate($perPage);

        return $this->ok([
            'data' => $users->items(),
            'meta' => [
                'current_page' => $users->currentPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
                'last_page' => $users->lastPage(),
            ],
            'links' => [
                'first' => $users->url(1),
                'last' => $users->url($users->lastPage()),
                'prev' => $users->previousPageUrl(),
                'next' => $users->nextPageUrl(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:150', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'phone' => ['nullable', 'string', 'max:30'],
            'branch_id' => ['nullable', 'exists:branches,id'],
            'roles' => ['nullable', 'array'],
            'roles.*' => ['string', 'exists:roles,name'],
            'is_active' => ['boolean'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'phone' => $validated['phone'] ?? null,
            'branch_id' => $validated['branch_id'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        if (! empty($validated['roles'])) {
            $user->syncRoles($validated['roles']);
        }

        $user->load(['branch', 'roles']);

        $this->auditService->log(
            module: 'M00Core',
            action: 'user.created',
            auditable: $user,
            newValues: [
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $user->getRoleNames(),
            ]
        );

        return $this->created($user);
    }

    public function show(User $user): JsonResponse
    {
        $user->load(['branch', 'roles.permissions']);

        return $this->ok($user);
    }

    public function update(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:150'],
            'email' => ['sometimes', 'required', 'email', 'max:150', 'unique:users,email,' . $user->id],
            'password' => ['nullable', 'string', 'min:8'],
            'phone' => ['nullable', 'string', 'max:30'],
            'branch_id' => ['nullable', 'exists:branches,id'],
            'roles' => ['nullable', 'array'],
            'roles.*' => ['string', 'exists:roles,name'],
            'is_active' => ['boolean'],
        ]);

        $oldValues = [
            'name' => $user->name,
            'email' => $user->email,
            'branch_id' => $user->branch_id,
            'is_active' => $user->is_active,
            'roles' => $user->getRoleNames(),
        ];

        if (! empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->fill(collect($validated)->except('password', 'roles')->toArray());
        $user->save();

        if (array_key_exists('roles', $validated)) {
            $user->syncRoles($validated['roles'] ?? []);
        }

        $user->load(['branch', 'roles']);

        $this->auditService->log(
            module: 'M00Core',
            action: 'user.updated',
            auditable: $user,
            oldValues: $oldValues,
            newValues: [
                'name' => $user->name,
                'email' => $user->email,
                'branch_id' => $user->branch_id,
                'is_active' => $user->is_active,
                'roles' => $user->getRoleNames(),
            ]
        );

        return $this->ok($user);
    }

    public function destroy(User $user): JsonResponse
    {
        // Prevent deleting oneself
        if (auth()->id() === $user->id) {
            return $this->error('cannot_delete_self', 'Vous ne pouvez pas supprimer votre propre compte.', 400);
        }

        $oldValues = ['id' => $user->id, 'email' => $user->email];
        $user->delete();

        $this->auditService->log(
            module: 'M00Core',
            action: 'user.deleted',
            auditable: $user,
            oldValues: $oldValues
        );

        return $this->noContent();
    }
}
