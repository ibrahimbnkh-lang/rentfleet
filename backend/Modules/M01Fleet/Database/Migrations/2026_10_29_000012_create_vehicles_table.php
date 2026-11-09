<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('vehicle_categories')->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->string('brand');
            $table->string('model');
            $table->string('generation')->nullable();
            $table->unsignedSmallInteger('year');
            $table->string('registration_plate', 30)->unique()->index();
            $table->string('vin', 50)->unique()->index();
            $table->string('color', 50)->nullable();
            $table->string('fuel_type', 30)->default('essence'); // essence, diesel, hybride, gpl, electrique
            $table->string('transmission', 30)->default('manuelle'); // manuelle, automatique
            $table->unsignedTinyInteger('seats')->default(5);
            $table->unsignedTinyInteger('doors')->default(5);
            $table->unsignedInteger('mileage')->default(0);
            $table->decimal('daily_price_dzd', 10, 2)->nullable();
            $table->decimal('deposit_dzd', 10, 2)->nullable();
            $table->string('status', 30)->default('available')->index(); // available, rented, maintenance, reserved, out_of_service
            $table->boolean('is_active')->default(true)->index();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
