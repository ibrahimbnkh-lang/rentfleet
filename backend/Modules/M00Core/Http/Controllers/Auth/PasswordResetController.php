<?php

declare(strict_types=1);

namespace Modules\M00Core\Http\Controllers\Auth;

use App\Http\Controllers\ApiController;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Modules\M00Core\Services\AuditService;

class PasswordResetController extends ApiController
{
    public function __construct(
        private readonly AuditService $auditService,
    ) {}

    public function sendLink(Request $request): JsonResponse
    {
        $request->validate(['email' => ['required', 'email']]);

        // In a real application, send password reset notification.
        // Return generic message to avoid email enumeration.
        return $this->ok([
            'message' => 'Si cette adresse existe, un lien de réinitialisation a été envoyé.',
        ]);
    }

    public function reset(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'token' => ['nullable', 'string'],
        ]);

        $user = User::where('email', $validated['email'])->first();
        if (! $user) {
            return $this->error('invalid_token', 'Impossible de réinitialiser le mot de passe.', 400);
        }

        $user->forceFill([
            'password' => Hash::make($validated['password']),
            'remember_token' => Str::random(60),
        ])->save();

        $this->auditService->log(
            module: 'M00Core',
            action: 'auth.password_reset',
            auditable: $user
        );

        return $this->ok(['message' => 'Mot de passe réinitialisé avec succès.']);
    }
}
