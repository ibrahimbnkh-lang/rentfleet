<?php

declare(strict_types=1);

namespace Modules\M00Core\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Modules\M00Core\Models\License;
use RuntimeException;

class LicenseService
{
    private const CACHE_KEY = 'rentfleet.active_license';

    /**
     * Get the active license (cached for 10 minutes).
     */
    public function getActiveLicense(): ?License
    {
        return Cache::remember(self::CACHE_KEY, 600, function () {
            return License::query()
                ->where('is_active', true)
                ->where('expires_at', '>', now())
                ->latest()
                ->first();
        });
    }

    /**
     * Check if a specific module is licensed.
     */
    public function isModuleLicensed(string $moduleId): bool
    {
        if ($moduleId === 'M00Core') {
            return true;
        }

        $license = $this->getActiveLicense();
        if (! $license) {
            return false;
        }

        return $license->allowsModule($moduleId);
    }

    /**
     * Issue a signed license token.
     *
     * @param array<string> $modules
     */
    public function issueLicense(
        string $clientName,
        string $clientEmail,
        array $modules,
        int $days = 365,
        int $maxVehicles = 50,
        int $maxBranches = 5,
        ?string $secretKey = null,
    ): array {
        $secret = $secretKey ?? config('app.key', 'rentfleet-secret-signing-key');
        $expiresAt = Carbon::now()->addDays($days)->toIso8601String();
        $licenseKey = 'RF-' . strtoupper(Str::random(4)) . '-' . strtoupper(Str::random(4)) . '-' . strtoupper(Str::random(4));

        $payloadData = [
            'license_key' => $licenseKey,
            'client_name' => $clientName,
            'client_email' => $clientEmail,
            'modules' => $modules,
            'expires_at' => $expiresAt,
            'max_vehicles' => $maxVehicles,
            'max_branches' => $maxBranches,
            'issued_at' => now()->toIso8601String(),
        ];

        $payloadJson = json_encode($payloadData, JSON_THROW_ON_ERROR);
        $signature = hash_hmac('sha256', $payloadJson, $secret);

        $token = base64_encode(json_encode([
            'payload' => $payloadJson,
            'signature' => $signature,
        ], JSON_THROW_ON_ERROR));

        return [
            'token' => $token,
            'license_key' => $licenseKey,
            'payload' => $payloadData,
            'signature' => $signature,
        ];
    }

    /**
     * Validate and activate a license token.
     */
    public function activate(string $licenseToken, ?string $secretKey = null): License
    {
        $secret = $secretKey ?? config('app.key', 'rentfleet-secret-signing-key');

        $decoded = base64_decode(trim($licenseToken), true);
        if (! $decoded) {
            throw new RuntimeException('Invalid license token: not valid base64.');
        }

        $envelope = json_decode($decoded, true);
        if (! is_array($envelope) || empty($envelope['payload']) || empty($envelope['signature'])) {
            throw new RuntimeException('Invalid license token structure.');
        }

        $payloadJson = $envelope['payload'];
        $signature = $envelope['signature'];

        // Verify HMAC signature
        $expectedSignature = hash_hmac('sha256', $payloadJson, $secret);
        if (! hash_equals($expectedSignature, $signature)) {
            throw new RuntimeException('License signature verification failed.');
        }

        $data = json_decode($payloadJson, true);
        if (! is_array($data) || empty($data['license_key']) || empty($data['expires_at'])) {
            throw new RuntimeException('Malformed license payload.');
        }

        $expiresAt = Carbon::parse($data['expires_at']);
        if ($expiresAt->isPast()) {
            throw new RuntimeException('This license has already expired.');
        }

        // Deactivate previous licenses
        License::query()->update(['is_active' => false]);

        // Create or update license
        $license = License::updateOrCreate(
            ['license_key' => $data['license_key']],
            [
                'client_name' => $data['client_name'] ?? 'Client',
                'client_email' => $data['client_email'] ?? 'client@rentfleet.dz',
                'modules' => $data['modules'] ?? ['M00Core'],
                'expires_at' => $expiresAt,
                'max_vehicles' => $data['max_vehicles'] ?? 50,
                'max_branches' => $data['max_branches'] ?? 5,
                'payload' => $payloadJson,
                'signature' => $signature,
                'is_active' => true,
            ]
        );

        Cache::forget(self::CACHE_KEY);

        return $license;
    }
}
