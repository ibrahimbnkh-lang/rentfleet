<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained('vehicles')->cascadeOnDelete();
            $table->string('type', 50)->index(); // assurance, controle_technique, carte_grise, vignette, autorisation
            $table->string('reference_number', 100);
            $table->string('issuer', 100)->nullable(); // e.g. SAA, CAAT, CIAR, DGSN
            $table->date('issued_at')->nullable();
            $table->date('expires_at')->nullable()->index();
            $table->string('file_path')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_documents');
    }
};
