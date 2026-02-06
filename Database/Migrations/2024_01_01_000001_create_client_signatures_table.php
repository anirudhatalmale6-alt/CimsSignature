<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('client_signatures', function (Blueprint $table) {
            $table->id();
            $table->string('client_name', 255);
            $table->string('client_reference', 100)->nullable()->index();
            $table->string('id_number', 50)->nullable();
            $table->text('signature_data'); // Base64 encoded PNG
            $table->string('signature_hash', 64)->nullable(); // SHA256 hash for verification
            $table->string('purpose', 255)->nullable(); // What was signed
            $table->string('document_reference', 100)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('device_info', 255)->nullable();
            $table->unsignedBigInteger('captured_by')->nullable();
            $table->timestamp('captured_at');
            $table->timestamps();

            $table->index('client_name');
            $table->index('captured_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_signatures');
    }
};
