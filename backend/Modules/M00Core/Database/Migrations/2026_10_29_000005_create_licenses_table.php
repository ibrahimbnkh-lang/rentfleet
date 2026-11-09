<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('licenses', function (Blueprint $table) {
            $table->id();
            $table->string('license_key')->unique();
            $table->string('client_name');
            $table->string('client_email');
            $table->json('modules');
            $table->timestamp('expires_at');
            $table->unsignedInteger('max_vehicles')->default(50);
            $table->unsignedInteger('max_branches')->default(5);
            $table->longText('payload');
            $table->text('signature');
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('licenses');
    }
};
