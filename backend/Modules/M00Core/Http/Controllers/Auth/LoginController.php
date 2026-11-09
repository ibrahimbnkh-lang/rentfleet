<?php

declare(strict_types=1);

namespace Modules\M00Core\Http\Controllers\Auth;

use App\Http\Controllers\ApiController;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Modules\M00Core\Services\AuditService;
use Modules\M00Core\Services\TwoFactorService;

class LoginController extends ApiController
{
    public function __construct(
        private readonly AuditService $auditService,
        private readonly TwoFactorService $twoFactorService,
    ) {}

    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'two_factor_code' => ['nullable', 'string'],
            'recovery_code' => ['nullable', 'string'],
        ]);

        $user = User::query()->where('email', $validated['email'])->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            return $this->error('invalid_credentials', 'Identifiants invalides.', 401);
        }

        if (! $user->is_active) {
            return $this->forbidden('Votre compte a été désactivé.', 'account_disabled');
        }

        // Check 2FA
        if ($user->hasTwoFactorEnabled()) {
            $code = $validated['two_factor_code'] ?? null;
            $recoveryCode = $validated['recovery_code'] ?? null;

            if (empty($code) && empty($recoveryCode)) {
                return $this->ok([
                    'requires_2fa' => true,
                    'email' => $user->email,
                ]);
            }

            $twoFactorVerified = false;

            if (! empty($code)) {
                $twoFactorVerified = $this->twoFactorService->verify($user->two_factor_secret, $code);
            } elseif (! empty($recoveryCode)) {
                $recoveryCodes = $user->two_factor_recovery_codes ?? [];
                if (in_array(strtoupper($recoveryCode), $recoveryCodes, true)) {
                    $twoFactorVerified = true;
                    // Consume used recovery code
                    $user->two_factor_recovery_codes = array_values(array_diff($recoveryCodes, [strtoupper($recoveryCode)]));
                    $user->save();
                }
            }

            if (! $twoFactorVerified) {
                return $this->error('invalid_2fa_code', 'Code de sécurité 2FA incorrect.', 401);
            }
        }

        // Update last login
        $user->forceFill(['last_login_at' => now()])->save();

        // Create Sanctum Token
        $token = $user->createToken('admin_token')->plainTextToken;

        // Log audit
        $this->auditService->log(
            module: 'M00Core',
            action: 'auth.login',
            auditable: $user,
            newValues: ['ip' => $request->ip(), 'user_agent' => $request->userAgent()]
        );

        $user->load(['branch', 'roles.permissions']);

        return $this->ok([
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'branch_id' => $user->branch_id,
                'branch' => $user->branch,
                'is_active' => $user->is_active,
                'has_2fa' => $user->hasTwoFactorEnabled(),
                'roles' => $user->getRoleNames(),
                'permissions' => $user->getAllPermissions()->pluck('name'),
            ],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user) {
            $user->currentAccessToken()?->delete();
            $this->auditService->log(
                module: 'M00Core',
                action: 'auth.logout',
                auditable: $user
            );
        }

        return $this->ok(['message' => 'Déconnexion réussie.']);
    }

    public function me(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $user->load(['branch', 'roles.permissions']);

        return $this->ok([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'branch_id' => $user->branch_id,
            'branch' => $user->branch,
            'is_active' => $user->is_active,
            'has_2fa' => $user->hasTwoFactorEnabled(),
            'roles' => $user->getRoleNames(),
            'permissions' => $user->getAllPermissions()->pluck('name'),
        ]);
    }
}
