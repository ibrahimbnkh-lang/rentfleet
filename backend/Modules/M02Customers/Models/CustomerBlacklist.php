<?php

declare(strict_types=1);

namespace Modules\M02Customers\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\M00Core\Models\Branch;

class CustomerBlacklist extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'reason',
        'severity',
        'incident_date',
        'reported_by',
        'branch_id',
        'notes',
    ];

    protected $casts = [
        'incident_date' => 'date:Y-m-d',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function reportedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }
}
