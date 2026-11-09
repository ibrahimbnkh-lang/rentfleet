<?php

declare(strict_types=1);

namespace Modules\M01Fleet\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class VehicleCategory extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'daily_rate_dzd',
        'deposit_amount_dzd',
    ];

    protected function casts(): array
    {
        return [
            'daily_rate_dzd' => 'decimal:2',
            'deposit_amount_dzd' => 'decimal:2',
        ];
    }

    public function vehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class, 'category_id');
    }
}
