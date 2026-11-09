<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->string('payment_reference', 32)->unique();

            $table->foreignId('invoice_id')->constrained('invoices')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();

            $table->decimal('amount_dzd', 12, 2);
            $table->string('payment_method', 32); // cash, cheque, bank_transfer, cib_edahabia
            $table->dateTime('payment_date');

            $table->string('reference_details', 100)->nullable(); // e.g. Chèque N° / Banque, N° Virement
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['invoice_id', 'payment_date'], 'pay_inv_date_idx');
            $table->index(['branch_id', 'payment_date'], 'pay_branch_date_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};

