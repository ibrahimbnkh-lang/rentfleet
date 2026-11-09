<?php

declare(strict_types=1);

namespace Modules\M01Fleet\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\M00Core\Models\Branch;

class Vehicle extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'category_id',
        'branch_id',
        'brand',
        'model',
        'generation',
        'year',
        'registration_plate',
        'vin',
        'color',
        'fuel_type',
        'transmission',
        'seats',
        'doors',
        'mileage',
        'daily_price_dzd',
        'deposit_dzd',
        'status',
        'is_active',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'seats' => 'integer',
            'doors' => 'integer',
            'mileage' => 'integer',
            'daily_price_dzd' => 'decimal:2',
            'deposit_dzd' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(VehicleCategory::class, 'category_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(VehicleDocument::class);
    }

    public function photos(): HasMany
    {
        return $this->hasMany(VehiclePhoto::class);
    }

    public function equipment(): HasMany
    {
        return $this->hasMany(VehicleEquipment::class);
    }

    public function getEffectiveDailyPriceAttribute(): float
    {
        if ($this->daily_price_dzd !== null && (float) $this->daily_price_dzd > 0) {
            return (float) $this->daily_price_dzd;
        }

        return (float) ($this->category?->daily_rate_dzd ?? 5000);
    }

    public function getEffectiveDepositAttribute(): float
    {
        if ($this->deposit_dzd !== null && (float) $this->deposit_dzd > 0) {
            return (float) $this->deposit_dzd;
        }

        return (float) ($this->category?->deposit_amount_dzd ?? 50000);
    }

    /**
     * Check if vehicle is legally and technically rentable.
     */
    public function isRentable(): bool
    {
        if (! $this->is_active || $this->status !== 'available') {
            return false;
        }

        return $this->hasValidMandatoryDocuments();
    }

    /**
     * Check that mandatory documents (assurance, controle technique) are present and unexpired.
     */
    public function hasValidMandatoryDocuments(): bool
    {
        $insurance = $this->documents()
            ->where('type', 'assurance')
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->exists();

        $technical = $this->documents()
            ->where('type', 'controle_technique')
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->exists();

        return $insurance && $technical;
    }
}
