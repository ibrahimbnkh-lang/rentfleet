<?php

declare(strict_types=1);

namespace Modules\M01Fleet\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VehicleEquipment extends Model
{
    protected $table = 'vehicle_equipment';

    protected $fillable = [
        'vehicle_id',
        'name',
        'is_present',
        'condition',
    ];

    protected function casts(): array
    {
        return [
            'is_present' => 'boolean',
        ];
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }
}
