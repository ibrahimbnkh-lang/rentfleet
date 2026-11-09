<?php

declare(strict_types=1);

namespace Modules\M07Invoicing\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\M00Core\Models\Branch;
use Modules\M02Customers\Models\Customer;
use Modules\M05Contracts\Models\Contract;

class Invoice extends Model
{
    use HasFactory, SoftDeletes;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_ISSUED = 'issued';
    public const STATUS_PARTIALLY_PAID = 'partially_paid';
    public const STATUS_PAID = 'paid';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'invoice_number',
        'contract_id',
        'customer_id',
        'branch_id',
        'created_by',
        'status',
        'type',
        'issue_date',
        'due_date',
        'subtotal_ht_dzd',
        'vat_rate',
        'vat_amount_dzd',
        'stamp_duty_dzd',
        'total_ttc_dzd',
        'paid_amount_dzd',
        'remaining_amount_dzd',
        'customer_name',
        'customer_nif',
        'customer_rc',
        'customer_nis',
        'customer_ai',
        'customer_address',
        'payment_method',
        'notes',
        'payment_terms',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'due_date' => 'date',
        'subtotal_ht_dzd' => 'float',
        'vat_rate' => 'float',
        'vat_amount_dzd' => 'float',
        'stamp_duty_dzd' => 'float',
        'total_ttc_dzd' => 'float',
        'paid_amount_dzd' => 'float',
        'remaining_amount_dzd' => 'float',
    ];

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class, 'contract_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class, 'invoice_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'invoice_id')->orderByDesc('payment_date');
    }

    /**
     * Generate unique official Algerian invoice number: FAC-YYYY-XXXX
     */
    public static function generateInvoiceNumber(): string
    {
        $year = date('Y');
        $prefix = "FAC-{$year}-";

        $last = static::withTrashed()
            ->where('invoice_number', 'like', "{$prefix}%")
            ->orderByDesc('id')
            ->value('invoice_number');

        if ($last) {
            $num = (int) substr($last, strlen($prefix));
            $next = str_pad((string) ($num + 1), 4, '0', STR_PAD_LEFT);
        } else {
            $next = '0001';
        }

        return "{$prefix}{$next}";
    }
}

