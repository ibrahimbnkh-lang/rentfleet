<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained('invoices')->cascadeOnDelete();

            $table->string('description', 255);
            $table->decimal('quantity', 8, 2)->default(1.00);
            $table->decimal('unit_price_ht_dzd', 10, 2);
            $table->decimal('total_ht_dzd', 10, 2);
            $table->decimal('vat_rate', 5, 2)->default(19.00);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_items');
    }
};

