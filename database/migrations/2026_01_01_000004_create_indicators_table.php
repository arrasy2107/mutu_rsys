<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('indicators', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 30)->unique();
            $table->string('judul', 255);
            $table->text('dasar_pemikiran')->nullable();
            $table->string('dimensi_mutu', 100)->nullable(); // Efektif, Efisien, Aman, dll
            $table->text('tujuan')->nullable();
            $table->text('kriteria_inklusi')->nullable();
            $table->text('kriteria_eksklusi')->nullable();
            $table->text('formula')->nullable(); // (Numerator/Denominator) x 100
            $table->text('cara_pengumpulan')->nullable();
            $table->enum('tipe', ['struktur', 'proses', 'outcome'])->default('proses');
            $table->decimal('target_capaian', 5, 2)->default(100.00); // dalam persen
            $table->enum('target_operator', ['>=', '<='])->default('>='); // >= berarti "minimal sekian"
            $table->enum('frekuensi', ['harian', 'bulanan'])->default('bulanan');
            $table->enum('sumber_data', ['manual', 'simrs'])->default('manual');
            $table->string('penanggung_jawab', 100)->nullable();
            $table->foreignId('category_id')->constrained('indicator_categories')->restrictOnDelete();
            $table->foreignId('unit_id')->nullable()->constrained('units')->nullOnDelete(); // null = berlaku semua unit
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('indicators');
    }
};
