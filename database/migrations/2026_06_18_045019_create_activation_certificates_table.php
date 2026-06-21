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
        Schema::create('activation_certificates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activation_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('serial_key_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->string('device_fingerprint');
            $table->uuid('license_id')->unique();
            $table->string('server_url');
            $table->string('api_url');
            $table->json('certificate_data');
            $table->text('digital_signature');
            $table->timestamp('issued_at');
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activation_certificates');
    }
};
