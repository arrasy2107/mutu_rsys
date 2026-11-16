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
        Schema::create('hva_assessments', function (Blueprint $table) {
            $table->id();
            $table->smallInteger('tahun')->default(2026)->index();
            $table->enum('ruang_lingkup', ['unit', 'rs'])->default('unit')->index();
            $table->foreignId('unit_id')->nullable()->constrained('units')->nullOnDelete();
            $table->enum('kategori', ['natural', 'technological', 'human', 'hazardous_materials'])->index();
            $table->string('nama_kejadian', 150);
            $table->integer('urutan')->default(0);

            // 7 Skor Kaiser Permanente (0-3)
            $table->tinyInteger('probabilitas')->default(0);
            $table->tinyInteger('dampak_manusia')->default(0);
            $table->tinyInteger('dampak_properti')->default(0);
            $table->tinyInteger('dampak_bisnis')->default(0);
            $table->tinyInteger('kesiapan')->default(0);
            $table->tinyInteger('respon_internal')->default(0);
            $table->tinyInteger('respon_eksternal')->default(0);

            // Perhitungan Otomatis
            $table->tinyInteger('skor_keparahan')->default(0);
            $table->decimal('skor_risiko_relatif', 5, 2)->default(0.00);
            $table->integer('peringkat_prioritas')->nullable();

            // Rencana Tindak Lanjut (RTL) / Mitigasi
            $table->text('rencana_tindak_lanjut')->nullable();
            $table->string('pj_mitigasi', 150)->nullable();
            $table->string('target_waktu', 100)->nullable();
            $table->enum('status_mitigasi', ['belum_mulai', 'proses', 'selesai'])->default('belum_mulai');

            $table->timestamps();

            // Indexes for fast querying
            $table->index(['tahun', 'ruang_lingkup', 'kategori']);
            $table->index(['tahun', 'ruang_lingkup', 'skor_risiko_relatif']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hva_assessments');
    }
};

