<?php

declare(strict_types=1);

namespace Modules\M02Customers\Services;

use Modules\M02Customers\Models\Customer;

class KycService
{
    /**
     * Check if a customer is eligible for vehicle rental in Algeria.
     *
     * @return array{
     *     is_eligible: bool,
     *     reasons: array<int, string>,
     *     criteria: array<string, mixed>,
     * }
     */
    public function checkRentalEligibility(Customer $customer, int $minAge = 21, int $minLicenseYears = 2): array
    {
        $reasons = [];
        $criteria = [
            'is_active' => $customer->is_active,
            'is_blacklisted' => (bool) $customer->is_blacklisted,
            'kyc_status' => $customer->kyc_status,
            'age' => $customer->age,
            'min_age_required' => $minAge,
            'license_seniority_years' => $customer->license_seniority_years,
            'min_license_years_required' => $minLicenseYears,
            'has_license_number' => ! empty($customer->driver_license_number),
            'has_national_id' => ! empty($customer->national_id_number),
        ];

        // 1. Blacklist check
        if ($customer->is_blacklisted) {
            $latestIncident = $customer->blacklists()->latest()->first();
            $detail = $latestIncident ? " (Raison: {$latestIncident->reason})" : '';
            $reasons[] = "Client inscrit sur la liste noire RentFleet Algérie{$detail}. Location strictement interdite.";
        }

        // 2. Account active check
        if (! $customer->is_active) {
            $reasons[] = 'Le compte client est désactivé.';
        }

        // 3. KYC status check
        if ($customer->kyc_status !== 'verified') {
            if ($customer->kyc_status === 'rejected') {
                $reasons[] = 'Dossier KYC rejeté : ' . ($customer->kyc_rejection_reason ?? 'Documents non conformes.');
            } else {
                $reasons[] = 'Vérification KYC en attente. Les pièces d’identité doivent être validées avant signature du contrat.';
            }
        }

        // 4. Driver license presence
        if (empty($customer->driver_license_number)) {
            $reasons[] = 'Numéro de permis de conduire manquant.';
        }

        // 5. Age check
        if ($customer->type === 'individual') {
            if ($customer->age === null) {
                $reasons[] = 'Date de naissance non renseignée (âge invérifiable).';
            } elseif ($customer->age < $minAge) {
                $reasons[] = "Âge insuffisant ({$customer->age} ans). L’âge minimum requis pour louer est de {$minAge} ans.";
            }

            // 6. License seniority check
            if ($customer->license_seniority_years === null) {
                $reasons[] = 'Date de délivrance du permis non renseignée (ancienneté invérifiable).';
            } elseif ($customer->license_seniority_years < $minLicenseYears) {
                $reasons[] = "Ancienneté du permis insuffisante ({$customer->license_seniority_years} an(s)). Minimum requis : {$minLicenseYears} ans de conduite.";
            }
        }

        $isEligible = empty($reasons);

        return [
            'is_eligible' => $isEligible,
            'reasons' => $reasons,
            'criteria' => $criteria,
        ];
    }
}
