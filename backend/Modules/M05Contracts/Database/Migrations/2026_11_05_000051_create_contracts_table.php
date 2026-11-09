<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->string('contract_number', 32)->unique();

            // Foreign keys
            $table->foreignId('reservation_id')->nullable()->constrained('reservations')->nullOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('vehicle_id')->constrained('vehicles')->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignId('return_branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->foreignId('agent_id')->nullable()->constrained('users')->nullOnDelete();

            // Status: draft, active, completed, cancelled
            $table->string('status', 24)->default('active');

            // Period
            $table->dateTime('start_datetime');
            $table->dateTime('end_datetime');
            $table->dateTime('actual_return_datetime')->nullable();
            $table->unsignedInteger('days_count')->default(1);

            // Financials in DZD
            $table->decimal('daily_rate_dzd', 10, 2);
            $table->decimal('total_amount_dzd', 10, 2);
            $table->decimal('deposit_amount_dzd', 10, 2)->default(0);
            $table->string('deposit_type', 32)->default('cheque'); // cheque, cash, card, bank_transfer
            $table->string('deposit_reference', 64)->nullable(); // e.g. chq N°, bank

            // Additional fees at return
            $table->decimal('fuel_fee_dzd', 10, 2)->default(0);
            $table->decimal('extra_mileage_fee_dzd', 10, 2)->default(0);
            $table->decimal('damages_fee_dzd', 10, 2)->default(0);

            // Check-in (Départ)
            $table->unsignedInteger('start_mileage')->default(0);
            $table->string('start_fuel_level', 16)->default('100%'); // 25%, 50%, 75%, 100%
            $table->text('start_notes')->nullable();
            $table->dateTime('start_inspected_at')->nullable();

            // Check-out (Retour)
            $table->unsignedInteger('end_mileage')->nullable();
            $table->string('end_fuel_level', 16)->nullable();
            $table->text('end_notes')->nullable();
            $table->dateTime('end_inspected_at')->nullable();

            // E-Signature & Algerian legal consent
            $table->dateTime('signed_at')->nullable();
            $table->longText('client_signature')->nullable(); // Base64 data URI
            $table->longText('agent_signature')->nullable();  // Base64 data URI
            $table->string('signer_ip', 45)->nullable();
            $table->string('signer_national_id', 32)->nullable();
            $table->boolean('clauses_accepted')->default(true);
            $table->text('terms_and_conditions')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['vehicle_id', 'status'], 'ctr_veh_status_idx');
            $table->index(['customer_id', 'status'], 'ctr_cust_status_idx');
            $table->index(['branch_id', 'status'], 'ctr_branch_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contracts');
    }
};

