<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('validations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('data_entry_id')->constrained('data_entries')->cascadeOnDelete();
            $table->foreignId('validator_id')->constrained('users')->restrictOnDelete();
            $table->enum('status', ['approved', 'rejected', 'revision']);
            $table->text('catatan_validator')->nullable();
            $table->timestamp('tanggal_validasi')->useCurrent();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('validations');
    }
};
