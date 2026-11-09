<?php

declare(strict_types=1);

namespace Modules\M07Invoicing\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_id',
        'description',
        'quantity',
        'unit_price_ht_dzd',
        'total_ht_dzd',
        'vat_rate',
    ];

    protected $casts = [
        'quantity' => 'float',
        'unit_price_ht_dzd' => 'float',
        'total_ht_dzd' => 'float',
        'vat_rate' => 'float',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'invoice_id');
    }
}

