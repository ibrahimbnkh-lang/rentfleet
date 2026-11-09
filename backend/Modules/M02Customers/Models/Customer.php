<?php

declare(strict_types=1);

namespace Modules\M02Customers\Models;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'first_name',
        'last_name',
        'company_name',
        'company_tax_id',
        'phone',
        'email',
        'national_id_number',
        'driver_license_number',
        'driver_license_issued_at',
        'driver_license_wilaya',
        'birth_date',
        'birth_place',
        'address',
        'wilaya_code',
        'city',
        'kyc_status',
        'kyc_verified_at',
        'kyc_verified_by',
        'kyc_rejection_reason',
        'is_blacklisted',
        'notes',
        'is_active',
    ];

    protected $attributes = [
        'type' => 'individual',
        'kyc_status' => 'pending',
        'is_blacklisted' => false,
        'is_active' => true,
    ];

    protected $casts = [
        'birth_date' => 'date:Y-m-d',
        'driver_license_issued_at' => 'date:Y-m-d',
        'kyc_verified_at' => 'datetime',
        'is_blacklisted' => 'boolean',
        'is_active' => 'boolean',
    ];

    protected $appends = [
        'full_name',
        'age',
        'license_seniority_years',
    ];

    public function documents(): HasMany
    {
        return $this->hasMany(CustomerDocument::class);
    }

    public function blacklists(): HasMany
    {
        return $this->hasMany(CustomerBlacklist::class);
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'kyc_verified_by');
    }

    public function getFullNameAttribute(): string
    {
        if ($this->type === 'company' && ! empty($this->company_name)) {
            return $this->company_name;
        }

        return trim(($this->first_name ?? '') . ' ' . ($this->last_name ?? ''));
    }

    public function getAgeAttribute(): ?int
    {
        if (! $this->birth_date) {
            return null;
        }

        return Carbon::parse($this->birth_date)->age;
    }

    public function getLicenseSeniorityYearsAttribute(): ?int
    {
        if (! $this->driver_license_issued_at) {
            return null;
        }

        $issued = Carbon::parse($this->driver_license_issued_at);
        $diffYears = (int) $issued->diffInYears(now());

        return max(0, $diffYears);
    }
}
