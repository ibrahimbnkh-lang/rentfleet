<?php

declare(strict_types=1);

namespace Modules\M01Fleet\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VehicleDocument extends Model
{
    protected $fillable = [
        'vehicle_id',
        'type',
        'reference_number',
        'issuer',
        'issued_at',
        'expires_at',
        'file_path',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'issued_at' => 'date',
            'expires_at' => 'date',
        ];
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function isExpired(): bool
    {
        if (! $this->expires_at) {
            return false;
        }

        return $this->expires_at->isPast();
    }

    public function isExpiringSoon(int $days = 30): bool
    {
        if (! $this->expires_at) {
            return false;
        }

        return $this->expires_at->isFuture() && $this->expires_at->diffInDays(now()) <= $days;
    }
}
