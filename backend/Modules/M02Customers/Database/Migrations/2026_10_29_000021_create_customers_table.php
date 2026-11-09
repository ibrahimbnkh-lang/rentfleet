<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('type', 20)->default('individual'); // individual | company
            $table->string('first_name', 100)->nullable();
            $table->string('last_name', 100)->nullable();
            $table->string('company_name', 150)->nullable();
            $table->string('company_tax_id', 50)->nullable(); // NIF / RC
            $table->string('phone', 30)->index();
            $table->string('email', 150)->nullable();
            $table->string('national_id_number', 30)->nullable()->index(); // NIN 18 digits or CNI
            $table->string('driver_license_number', 50)->nullable()->index();
            $table->date('driver_license_issued_at')->nullable();
            $table->string('driver_license_wilaya', 10)->nullable();
            $table->date('birth_date')->nullable();
            $table->string('birth_place', 100)->nullable();
            $table->string('address')->nullable();
            $table->string('wilaya_code', 5)->nullable()->index();
            $table->string('city', 100)->nullable();
            $table->string('kyc_status', 20)->default('pending')->index(); // pending | verified | rejected
            $table->timestamp('kyc_verified_at')->nullable();
            $table->foreignId('kyc_verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('kyc_rejection_reason')->nullable();
            $table->boolean('is_blacklisted')->default(false)->index();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
