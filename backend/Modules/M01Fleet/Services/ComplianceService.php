<?php

declare(strict_types=1);

namespace Modules\M01Fleet\Services;

use Modules\M01Fleet\Models\Vehicle;
use Modules\M01Fleet\Models\VehicleDocument;

class ComplianceService
{
    /**
     * Mandatory documents required by Algerian regulation.
     */
    public const MANDATORY_DOCS = [
        'assurance' => 'Assurance Automobile',
        'controle_technique' => 'Procès-Verbal de Contrôle Technique',
        'carte_grise' => 'Carte Grise (Certificat d\'immatriculation)',
    ];

    /**
     * Check regulatory compliance of a single vehicle.
     *
     * @return array{is_compliant: bool, status: string, issues: array<string>, documents: array<string, mixed>}
     */
    public function checkCompliance(Vehicle $vehicle): array
    {
        $vehicle->loadMissing('documents');
        $docs = $vehicle->documents;

        $issues = [];
        $docStatuses = [];
        $isCompliant = true;

        foreach (self::MANDATORY_DOCS as $type => $label) {
            $matchingDoc = $docs->firstWhere('type', $type);

            if (! $matchingDoc) {
                $isCompliant = false;
                $issues[] = "Document obligatoire manquant : {$label}";
                $docStatuses[$type] = ['status' => 'missing', 'label' => $label];
                continue;
            }

            if ($matchingDoc->isExpired()) {
                $isCompliant = false;
                $issues[] = "Document expiré : {$label} (depuis le {$matchingDoc->expires_at?->format('d/m/Y')})";
                $docStatuses[$type] = [
                    'status' => 'expired',
                    'label' => $label,
                    'expires_at' => $matchingDoc->expires_at?->toIso8601String(),
                ];
            } elseif ($matchingDoc->isExpiringSoon(30)) {
                $issues[] = "Document expire bientôt : {$label} (le {$matchingDoc->expires_at?->format('d/m/Y')})";
                $docStatuses[$type] = [
                    'status' => 'expiring_soon',
                    'label' => $label,
                    'expires_at' => $matchingDoc->expires_at?->toIso8601String(),
                ];
            } else {
                $docStatuses[$type] = [
                    'status' => 'valid',
                    'label' => $label,
                    'expires_at' => $matchingDoc->expires_at?->toIso8601String(),
                ];
            }
        }

        $overallStatus = 'compliant';
        if (! $isCompliant) {
            $overallStatus = 'non_compliant';
        } elseif (count($issues) > 0) {
            $overallStatus = 'warning';
        }

        return [
            'is_compliant' => $isCompliant,
            'status' => $overallStatus,
            'issues' => $issues,
            'documents' => $docStatuses,
        ];
    }

    /**
     * Get expiring or expired documents across the fleet.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, VehicleDocument>
     */
    public function getExpiringDocuments(int $days = 30, ?int $branchId = null)
    {
        $limitDate = now()->addDays($days);

        $query = VehicleDocument::query()
            ->with(['vehicle.branch'])
            ->whereHas('vehicle')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', $limitDate);

        if ($branchId) {
            $query->whereHas('vehicle', function ($q) use ($branchId) {
                $q->where('branch_id', $branchId);
            });
        }

        return $query->orderBy('expires_at', 'asc')->get();
    }
}
