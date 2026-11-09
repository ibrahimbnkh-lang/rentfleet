<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->string('reference_code', 32)->unique();

            // Foreign keys
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('vehicle_id')->constrained('vehicles')->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignId('return_branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            // Period
            $table->dateTime('pickup_datetime');
            $table->dateTime('return_datetime');
            $table->unsignedInteger('days_count')->default(1);

            // Lifecycle status
            // pending: En attente, confirmed: Confirmée, in_progress: En cours (voiture remise), completed: Terminée, cancelled: Annulée
            $table->string('status', 24)->default('confirmed');

            // Financials in DZD
            $table->decimal('daily_rate_dzd', 10, 2);
            $table->decimal('total_amount_dzd', 10, 2);
            $table->decimal('deposit_amount_dzd', 10, 2)->default(0);

            // Notes and timestamps
            $table->text('notes')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->dateTime('confirmed_at')->nullable();
            $table->dateTime('started_at')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->dateTime('cancelled_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indices for fast availability and timeline search
            $table->index(['vehicle_id', 'status', 'pickup_datetime', 'return_datetime'], 'res_veh_status_dates_idx');
            $table->index(['branch_id', 'status'], 'res_branch_status_idx');
            $table->index(['customer_id'], 'res_customer_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservations');
    }
};

