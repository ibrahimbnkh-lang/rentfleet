<?php

declare(strict_types=1);

namespace Modules\M03Reservations\Models;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\M00Core\Models\Branch;
use Modules\M01Fleet\Models\Vehicle;
use Modules\M02Customers\Models\Customer;

class Reservation extends Model
{
    use HasFactory, SoftDeletes;

    public const STATUS_PENDING = 'pending';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'reference_code',
        'customer_id',
        'vehicle_id',
        'branch_id',
        'return_branch_id',
        'created_by',
        'pickup_datetime',
        'return_datetime',
        'days_count',
        'status',
        'daily_rate_dzd',
        'total_amount_dzd',
        'deposit_amount_dzd',
        'notes',
        'cancellation_reason',
        'confirmed_at',
        'started_at',
        'completed_at',
        'cancelled_at',
    ];

    protected $casts = [
        'pickup_datetime' => 'datetime',
        'return_datetime' => 'datetime',
        'confirmed_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'days_count' => 'integer',
        'daily_rate_dzd' => 'decimal:2',
        'total_amount_dzd' => 'decimal:2',
        'deposit_amount_dzd' => 'decimal:2',
    ];

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

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function contract(): HasOne
    {
        return $this->hasOne(\Modules\M05Contracts\Models\Contract::class, 'reservation_id');
    }

    /**
     * Scope active / non-cancelled reservations
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNotIn('status', [self::STATUS_CANCELLED]);
    }

    /**
     * Scope reservations overlapping a given date range
     */
    public function scopeOverlapping(Builder $query, Carbon|string $start, Carbon|string $end, ?int $excludeReservationId = null): Builder
    {
        $startStr = $start instanceof Carbon ? $start->toDateTimeString() : Carbon::parse($start)->toDateTimeString();
        $endStr = $end instanceof Carbon ? $end->toDateTimeString() : Carbon::parse($end)->toDateTimeString();

        return $query->active()
            ->where(function (Builder $q) use ($startStr, $endStr) {
                $q->where('pickup_datetime', '<', $endStr)
                  ->where('return_datetime', '>', $startStr);
            })
            ->when($excludeReservationId, fn (Builder $q) => $q->where('id', '!=', $excludeReservationId));
    }

    /**
     * Generate unique reference code: RES-YYYY-XXXX
     */
    public static function generateReferenceCode(): string
    {
        $year = date('Y');
        $prefix = "RES-{$year}-";

        $last = static::withTrashed()
            ->where('reference_code', 'like', "{$prefix}%")
            ->orderByDesc('id')
            ->value('reference_code');

        if ($last) {
            $num = (int) substr($last, strlen($prefix));
            $next = str_pad((string) ($num + 1), 4, '0', STR_PAD_LEFT);
        } else {
            $next = '0001';
        }

        return "{$prefix}{$next}";
    }
}

