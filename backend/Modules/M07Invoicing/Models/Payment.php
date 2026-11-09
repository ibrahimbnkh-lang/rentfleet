<?php

declare(strict_types=1);

namespace Modules\M07Invoicing\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\M00Core\Models\Branch;
use Modules\M02Customers\Models\Customer;

class Payment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'payment_reference',
        'invoice_id',
        'customer_id',
        'branch_id',
        'received_by',
        'amount_dzd',
        'payment_method',
        'payment_date',
        'reference_details',
        'notes',
    ];

    protected $casts = [
        'payment_date' => 'datetime',
        'amount_dzd' => 'float',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'invoice_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    /**
     * Generate payment receipt reference: PAY-YYYY-XXXX
     */
    public static function generatePaymentReference(): string
    {
        $year = date('Y');
        $prefix = "PAY-{$year}-";

        $last = static::withTrashed()
            ->where('payment_reference', 'like', "{$prefix}%")
            ->orderByDesc('id')
            ->value('payment_reference');

        if ($last) {
            $num = (int) substr($last, strlen($prefix));
            $next = str_pad((string) ($num + 1), 4, '0', STR_PAD_LEFT);
        } else {
            $next = '0001';
        }

        return "{$prefix}{$next}";
    }
}

