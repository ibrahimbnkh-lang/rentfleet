<?php

declare(strict_types=1);

namespace Modules\M05Contracts\Services;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;
use Modules\M00Core\Services\AuditService;
use Modules\M01Fleet\Models\Vehicle;
use Modules\M02Customers\Models\Customer;
use Modules\M03Reservations\Models\Reservation;
use Modules\M05Contracts\Models\Contract;

class ContractService
{
    public function __construct(
        private readonly AuditService $auditService,
    ) {}

    /**
     * Create a new contract (direct walk-in or linked to reservation).
     */
    public function createContract(array $data, ?User $agent): Contract
    {
        $vehicle = Vehicle::findOrFail($data['vehicle_id']);
        $customer = Customer::findOrFail($data['customer_id']);

        $start = Carbon::parse($data['start_datetime']);
        $end = Carbon::parse($data['end_datetime']);

        $diffHours = $start->diffInHours($end);
        $daysCount = max(1, (int) ceil($diffHours / 24));

        $dailyRate = isset($data['daily_rate_dzd']) && $data['daily_rate_dzd'] !== ''
            ? (float) $data['daily_rate_dzd']
            : (float) $vehicle->daily_price_dzd;

        $totalAmount = $daysCount * $dailyRate;
        $deposit = isset($data['deposit_amount_dzd']) && $data['deposit_amount_dzd'] !== ''
            ? (float) $data['deposit_amount_dzd']
            : (float) $vehicle->deposit_dzd;

        $startMileage = isset($data['start_mileage'])
            ? (int) $data['start_mileage']
            : (int) ($vehicle->mileage ?? 0);

        $status = $data['status'] ?? Contract::STATUS_ACTIVE;

        $contract = Contract::create([
            'contract_number' => Contract::generateContractNumber(),
            'reservation_id' => $data['reservation_id'] ?? null,
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'branch_id' => $data['branch_id'] ?? $vehicle->branch_id,
            'return_branch_id' => $data['return_branch_id'] ?? $data['branch_id'] ?? $vehicle->branch_id,
            'agent_id' => $agent?->id,
            'status' => $status,
            'start_datetime' => $start,
            'end_datetime' => $end,
            'days_count' => $daysCount,
            'daily_rate_dzd' => $dailyRate,
            'total_amount_dzd' => $totalAmount,
            'deposit_amount_dzd' => $deposit,
            'deposit_type' => $data['deposit_type'] ?? 'cheque',
            'deposit_reference' => $data['deposit_reference'] ?? null,
            'start_mileage' => $startMileage,
            'start_fuel_level' => $data['start_fuel_level'] ?? '100%',
            'start_notes' => $data['start_notes'] ?? null,
            'start_inspected_at' => now(),
            'signed_at' => ! empty($data['client_signature']) ? now() : null,
            'client_signature' => $data['client_signature'] ?? null,
            'agent_signature' => $data['agent_signature'] ?? null,
            'signer_ip' => $data['signer_ip'] ?? request()->ip(),
            'signer_national_id' => $customer->national_id_number,
            'clauses_accepted' => true,
        ]);

        // If contract is active, set vehicle status to rented
        if ($status === Contract::STATUS_ACTIVE) {
            $vehicle->update(['status' => 'rented']);

            // If reservation linked, start it
            if (! empty($data['reservation_id'])) {
                Reservation::where('id', $data['reservation_id'])
                    ->where('status', '!=', Reservation::STATUS_IN_PROGRESS)
                    ->update([
                        'status' => Reservation::STATUS_IN_PROGRESS,
                        'started_at' => now(),
                    ]);
            }
        }

        $this->auditService->log(
            module: 'M05Contracts',
            action: 'contract.created',
            auditable: $contract,
            newValues: [
                'contract_number' => $contract->contract_number,
                'vehicle_id' => $vehicle->id,
                'customer_id' => $customer->id,
                'total_amount_dzd' => $totalAmount,
            ],
        );

        return $contract;
    }

    /**
     * E-Sign contract.
     */
    public function signContract(
        Contract $contract,
        string $clientSignature,
        ?string $agentSignature = null,
        ?string $ip = null
    ): Contract {
        $contract->update([
            'client_signature' => $clientSignature,
            'agent_signature' => $agentSignature ?? $contract->agent_signature,
            'signed_at' => now(),
            'signer_ip' => $ip ?? request()->ip(),
            'status' => Contract::STATUS_ACTIVE,
            'clauses_accepted' => true,
        ]);

        $contract->vehicle()->update(['status' => 'rented']);

        $this->auditService->log(
            module: 'M05Contracts',
            action: 'contract.signed',
            auditable: $contract,
            newValues: ['contract_number' => $contract->contract_number],
        );

        return $contract->fresh();
    }

    /**
     * Finalize return inspection (Check-out).
     */
    public function completeCheckout(Contract $contract, array $data, ?User $agent): Contract
    {
        $endMileage = (int) ($data['end_mileage'] ?? $contract->start_mileage);

        if ($endMileage < $contract->start_mileage) {
            throw ValidationException::withMessages([
                'end_mileage' => ["Le kilométrage de retour ({$endMileage} km) ne peut pas être inférieur au kilométrage de départ ({$contract->start_mileage} km)."],
            ]);
        }

        $fuelFee = (float) ($data['fuel_fee_dzd'] ?? 0);
        $extraMileageFee = (float) ($data['extra_mileage_fee_dzd'] ?? 0);
        $damagesFee = (float) ($data['damages_fee_dzd'] ?? 0);

        $contract->update([
            'status' => Contract::STATUS_COMPLETED,
            'actual_return_datetime' => now(),
            'end_mileage' => $endMileage,
            'end_fuel_level' => $data['end_fuel_level'] ?? '100%',
            'end_notes' => $data['end_notes'] ?? null,
            'end_inspected_at' => now(),
            'fuel_fee_dzd' => $fuelFee,
            'extra_mileage_fee_dzd' => $extraMileageFee,
            'damages_fee_dzd' => $damagesFee,
        ]);

        // Update vehicle odometer and release vehicle
        $vehicle = $contract->vehicle;
        if ($vehicle) {
            $vehicle->update([
                'mileage' => $endMileage,
                'status' => 'available',
                'branch_id' => $contract->return_branch_id ?? $contract->branch_id,
            ]);
        }

        // If linked to reservation, complete it
        if ($contract->reservation_id) {
            Reservation::where('id', $contract->reservation_id)->update([
                'status' => Reservation::STATUS_COMPLETED,
                'completed_at' => now(),
            ]);
        }

        $this->auditService->log(
            module: 'M05Contracts',
            action: 'contract.completed',
            auditable: $contract,
            newValues: [
                'contract_number' => $contract->contract_number,
                'driven_mileage' => $contract->driven_mileage,
                'grand_total_dzd' => $contract->grand_total_dzd,
            ],
        );

        return $contract->fresh();
    }
}
