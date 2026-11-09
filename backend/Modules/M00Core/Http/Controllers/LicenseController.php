<?php

declare(strict_types=1);

namespace Modules\M00Core\Http\Controllers;

use App\Http\Controllers\ApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\M00Core\Services\AuditService;
use Modules\M00Core\Services\LicenseService;
use Throwable;

class LicenseController extends ApiController
{
    public function __construct(
        private readonly LicenseService $licenseService,
        private readonly AuditService $auditService,
    ) {}

    public function show(): JsonResponse
    {
        $license = $this->licenseService->getActiveLicense();

        if (! $license) {
            return $this->ok([
                'is_active' => false,
                'message' => 'Aucune licence active trouvée.',
            ]);
        }

        $daysRemaining = max(0, (int) now()->diffInDays($license->expires_at, false));

        return $this->ok([
            'is_active' => true,
            'license_key' => $license->license_key,
            'client_name' => $license->client_name,
            'client_email' => $license->client_email,
            'modules' => $license->modules,
            'expires_at' => $license->expires_at->toIso8601String(),
            'days_remaining' => $daysRemaining,
            'max_vehicles' => $license->max_vehicles,
            'max_branches' => $license->max_branches,
            'is_expired' => $license->isExpired(),
        ]);
    }

    public function activate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'license_token' => ['required', 'string'],
        ]);

        try {
            $license = $this->licenseService->activate($validated['license_token']);

            $this->auditService->log(
                module: 'M00Core',
                action: 'license.activated',
                auditable: $license,
                newValues: ['key' => $license->license_key, 'modules' => $license->modules]
            );

            return $this->ok([
                'message' => 'Licence activée avec succès.',
                'license' => $license,
            ]);
        } catch (Throwable $e) {
            return $this->error('license_activation_failed', $e->getMessage(), 422);
        }
    }

    public function upload(Request $request): JsonResponse
    {
        $request->validate([
            'license_file' => ['required', 'file', 'max:1024'],
        ]);

        $content = file_get_contents($request->file('license_file')->getRealPath());

        try {
            $license = $this->licenseService->activate(trim($content));

            $this->auditService->log(
                module: 'M00Core',
                action: 'license.uploaded',
                auditable: $license,
                newValues: ['key' => $license->license_key, 'modules' => $license->modules]
            );

            return $this->ok([
                'message' => 'Fichier de licence importé et activé avec succès.',
                'license' => $license,
            ]);
        } catch (Throwable $e) {
            return $this->error('license_upload_failed', $e->getMessage(), 422);
        }
    }
}
