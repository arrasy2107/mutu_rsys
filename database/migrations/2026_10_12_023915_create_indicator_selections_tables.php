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
        Schema::create('indicator_selections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_id')->nullable()->constrained('units')->nullOnDelete();
            $table->enum('tipe', ['unit', 'rs'])->default('unit');
            $table->date('tanggal');
            $table->string('jam', 20)->nullable();
            $table->string('tempat', 255)->nullable();
            $table->text('notulen')->nullable();
            $table->enum('status', ['draft', 'final'])->default('draft');
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });

        Schema::create('indicator_selection_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('selection_id')->constrained('indicator_selections')->cascadeOnDelete();
            $table->string('usulan_imut', 255);
            $table->text('data_pendukung')->nullable();
            
            // Algoritma Pemilihan (Tahap 1: Skrining Kelayakan)
            $table->boolean('ssi_visi_misi')->default(false);
            $table->boolean('problem_prone')->default(false);
            $table->boolean('high_volume')->default(false);
            $table->boolean('high_risk_cost')->default(false);
            $table->boolean('ssi_aturan')->default(false);
            
            // Algoritma Pemilihan (Tahap 2: Lingkup Kendali)
            $table->boolean('dpt_dikendalikan_unit')->default(false);
            $table->boolean('tdk_dpt_dikendalikan_rs')->default(false);
            
            // Algoritma Pemilihan (Tahap 3: Kemampuan Ukur)
            $table->boolean('dpt_diukur')->default(false);
            
            // Hasil Skrining
            $table->boolean('is_lolos_skrining')->default(false);

            // Skoring Prioritas 8 Parameter (Skala 1 - 5)
            $table->unsignedTinyInteger('skor_masalah_banyak')->default(1);
            $table->unsignedTinyInteger('skor_high_volume')->default(1);
            $table->unsignedTinyInteger('skor_high_proses')->default(1);
            $table->unsignedTinyInteger('skor_ketidakpuasan')->default(1);
            $table->unsignedTinyInteger('skor_kemudahan_ukur')->default(1);
            $table->unsignedTinyInteger('skor_ketentuan_pemerintah')->default(1);
            $table->unsignedTinyInteger('skor_sesuai_renstra')->default(1);
            $table->unsignedTinyInteger('skor_patient_experience')->default(1);
            $table->unsignedSmallInteger('total_skor')->default(8);

            // Penetapan Akhir & Relasi ke Indikator Profil
            $table->boolean('is_ditetapkan')->default(false);
            $table->foreignId('indicator_id')->nullable()->constrained('indicators')->nullOnDelete();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('indicator_selection_items');
        Schema::dropIfExists('indicator_selections');
    }
};
