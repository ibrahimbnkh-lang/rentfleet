<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number', 32)->unique();

            // Relations
            $table->foreignId('contract_id')->nullable()->constrained('contracts')->nullOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            // Status: draft, issued, partially_paid, paid, cancelled
            $table->string('status', 24)->default('issued');

            // Type: standard, deposit_invoice, extra_fees, credit_note
            $table->string('type', 24)->default('standard');

            // Dates
            $table->date('issue_date');
            $table->date('due_date');

            // Algerian Fiscal Totals in DZD
            $table->decimal('subtotal_ht_dzd', 12, 2)->default(0);
            $table->decimal('vat_rate', 5, 2)->default(19.00); // Taux TVA 19%
            $table->decimal('vat_amount_dzd', 12, 2)->default(0);
            $table->decimal('stamp_duty_dzd', 10, 2)->default(0); // Droit de timbre
            $table->decimal('total_ttc_dzd', 12, 2)->default(0);

            // Settlement tracking
            $table->decimal('paid_amount_dzd', 12, 2)->default(0);
            $table->decimal('remaining_amount_dzd', 12, 2)->default(0);

            // Algerian Fiscal Identifiers of customer at invoice time
            $table->string('customer_name', 200);
            $table->string('customer_nif', 32)->nullable();
            $table->string('customer_rc', 32)->nullable();
            $table->string('customer_nis', 32)->nullable();
            $table->string('customer_ai', 32)->nullable(); // Article d'Imposition
            $table->string('customer_address', 255)->nullable();

            // Payment method intended or primary
            $table->string('payment_method', 32)->default('cash'); // cash, cheque, bank_transfer, cib_edahabia

            // Notes and text terms
            $table->text('notes')->nullable();
            $table->string('payment_terms', 100)->default('Règlement à réception');

            $table->timestamps();
            $table->softDeletes();

            $table->index(['customer_id', 'status'], 'inv_cust_status_idx');
            $table->index(['branch_id', 'status'], 'inv_branch_status_idx');
            $table->index(['issue_date', 'status'], 'inv_date_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};

