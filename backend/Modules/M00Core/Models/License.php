<?php

declare(strict_types=1);

namespace Modules\M00Core\Models;

use Illuminate\Database\Eloquent\Model;

class License extends Model
{
    protected $fillable = [
        'license_key',
        'client_name',
        'client_email',
        'modules',
        'expires_at',
        'max_vehicles',
        'max_branches',
        'payload',
        'signature',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'modules' => 'array',
            'expires_at' => 'datetime',
            'max_vehicles' => 'integer',
            'max_branches' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function allowsModule(string $moduleId): bool
    {
        if ($moduleId === 'M00Core') {
            return true;
        }

        return in_array($moduleId, $this->modules ?? [], true);
    }
}
