<?php

declare(strict_types=1);

namespace Modules\M05Contracts\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\M00Core\Models\Branch;
use Modules\M01Fleet\Models\Vehicle;
use Modules\M02Customers\Models\Customer;
use Modules\M03Reservations\Models\Reservation;

class Contract extends Model
{
    use HasFactory, SoftDeletes;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'contract_number',
        'reservation_id',
        'customer_id',
        'vehicle_id',
        'branch_id',
        'return_branch_id',
        'agent_id',
        'status',
        'start_datetime',
        'end_datetime',
        'actual_return_datetime',
        'days_count',
        'daily_rate_dzd',
        'total_amount_dzd',
        'deposit_amount_dzd',
        'deposit_type',
        'deposit_reference',
        'fuel_fee_dzd',
        'extra_mileage_fee_dzd',
        'damages_fee_dzd',
        'start_mileage',
        'start_fuel_level',
        'start_notes',
        'start_inspected_at',
        'end_mileage',
        'end_fuel_level',
        'end_notes',
        'end_inspected_at',
        'signed_at',
        'client_signature',
        'agent_signature',
        'signer_ip',
        'signer_national_id',
        'clauses_accepted',
        'terms_and_conditions',
    ];

    protected $casts = [
        'start_datetime' => 'datetime',
        'end_datetime' => 'datetime',
        'actual_return_datetime' => 'datetime',
        'start_inspected_at' => 'datetime',
        'end_inspected_at' => 'datetime',
        'signed_at' => 'datetime',
        'days_count' => 'integer',
        'start_mileage' => 'integer',
        'end_mileage' => 'integer',
        'daily_rate_dzd' => 'decimal:2',
        'total_amount_dzd' => 'decimal:2',
        'deposit_amount_dzd' => 'decimal:2',
        'fuel_fee_dzd' => 'decimal:2',
        'extra_mileage_fee_dzd' => 'decimal:2',
        'damages_fee_dzd' => 'decimal:2',
        'clauses_accepted' => 'boolean',
    ];

    protected $appends = [
        'grand_total_dzd',
        'driven_mileage',
    ];

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class, 'reservation_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class, 'vehicle_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function returnBranch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'return_branch_id');
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'agent_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function getGrandTotalDzdAttribute(): float
    {
        return (float) ($this->total_amount_dzd + $this->fuel_fee_dzd + $this->extra_mileage_fee_dzd + $this->damages_fee_dzd);
    }

    public function getDrivenMileageAttribute(): ?int
    {
        if ($this->end_mileage !== null && $this->start_mileage !== null) {
            return max(0, $this->end_mileage - $this->start_mileage);
        }
        return null;
    }

    /**
     * Generate unique official contract code: CTR-YYYY-XXXX
     */
    public static function generateContractNumber(): string
    {
        $year = date('Y');
        $prefix = "CTR-{$year}-";

        $last = static::withTrashed()
            ->where('contract_number', 'like', "{$prefix}%")
            ->orderByDesc('id')
            ->value('contract_number');

        if ($last) {
            $num = (int) substr($last, strlen($prefix));
            $next = str_pad((string) ($num + 1), 4, '0', STR_PAD_LEFT);
        } else {
            $next = '0001';
        }

        return "{$prefix}{$next}";
    }
}

