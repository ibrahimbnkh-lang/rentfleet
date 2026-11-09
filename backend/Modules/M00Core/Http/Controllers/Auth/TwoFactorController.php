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

class TwoFactorController extends ApiController
{
    public function __construct(
        private readonly TwoFactorService $twoFactorService,
        private readonly AuditService $auditService,
    ) {}

    public function enable(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $secret = $this->twoFactorService->generateSecret();
        $qrSvg = $this->twoFactorService->getQrCodeSvg(
            company: 'RentFleet DZ',
            email: $user->email,
            secret: $secret
        );
        $recoveryCodes = $this->twoFactorService->generateRecoveryCodes();

        // Stash secret temporarily or return to client for confirmation
        return $this->ok([
            'secret' => $secret,
            'qr_code' => 'data:image/svg+xml;base64,' . base64_encode($qrSvg),
            'recovery_codes' => $recoveryCodes,
        ]);
    }

    public function confirm(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'secret' => ['required', 'string'],
            'code' => ['required', 'string', 'size:6'],
            'recovery_codes' => ['required', 'array'],
        ]);

        /** @var User $user */
        $user = $request->user();

        if (! $this->twoFactorService->verify($validated['secret'], $validated['code'])) {
            return $this->validationError([
                'code' => ['Le code 2FA saisi est invalide ou a expiré.'],
            ]);
        }

        $user->forceFill([
            'two_factor_secret' => $validated['secret'],
            'two_factor_confirmed_at' => now(),
            'two_factor_recovery_codes' => $validated['recovery_codes'],
        ])->save();

        $this->auditService->log(
            module: 'M00Core',
            action: 'auth.2fa_enabled',
            auditable: $user
        );

        return $this->ok([
            'message' => 'Authentification à deux facteurs activée avec succès.',
            'has_2fa' => true,
        ]);
    }

    public function disable(Request $request): JsonResponse
    {
        $request->validate([
            'password' => ['required', 'string'],
        ]);

        /** @var User $user */
        $user = $request->user();

        if (! Hash::check($request->input('password'), $user->password)) {
            return $this->validationError([
                'password' => ['Mot de passe incorrect.'],
            ]);
        }

        $user->forceFill([
            'two_factor_secret' => null,
            'two_factor_confirmed_at' => null,
            'two_factor_recovery_codes' => null,
        ])->save();

        $this->auditService->log(
            module: 'M00Core',
            action: 'auth.2fa_disabled',
            auditable: $user
        );

        return $this->ok([
            'message' => 'Authentification à deux facteurs désactivée.',
            'has_2fa' => false,
        ]);
    }

    public function verify(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string'],
        ]);

        /** @var User $user */
        $user = $request->user();

        if (! $user->hasTwoFactorEnabled()) {
            return $this->error('2fa_not_enabled', 'La double authentification n\'est pas activée.', 400);
        }

        $valid = $this->twoFactorService->verify($user->two_factor_secret, $validated['code']);

        return $this->ok(['valid' => $valid]);
    }
}
