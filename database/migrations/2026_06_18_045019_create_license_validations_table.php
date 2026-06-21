<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('license_validations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activation_certificate_id')->constrained()->cascadeOnDelete();
            $table->string('device_fingerprint');
            $table->enum('validation_result', ['valid', 'suspended', 'revoked', 'expired'])->default('valid');
            $table->string('ip_address')->nullable();
            $table->timestamp('validated_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('license_validations');
    }
};
