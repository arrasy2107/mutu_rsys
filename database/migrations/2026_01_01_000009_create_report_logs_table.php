<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('report_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('jenis', ['bulanan', 'triwulan', 'tahunan', 'custom']);
            $table->smallInteger('tahun');
            $table->tinyInteger('bulan')->nullable(); // null untuk tahunan
            $table->tinyInteger('triwulan')->nullable(); // 1-4
            $table->enum('format', ['pdf', 'excel'])->default('pdf');
            $table->json('parameter')->nullable(); // filter tambahan (unit, kategori, dll)
            $table->string('file_path')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_logs');
    }
};
