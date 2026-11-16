<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('indicator_categories', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 20)->unique(); // INM, IMP-RS, IMP-Unit
            $table->string('nama', 100);
            $table->text('deskripsi')->nullable();
            $table->string('warna', 20)->default('#4F46E5'); // untuk badge color
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('indicator_categories');
    }
};
