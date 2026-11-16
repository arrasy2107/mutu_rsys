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
        Schema::create('risk_registers', function (Blueprint $table) {
            $table->id();
            $table->string('kode_risiko', 50)->unique();
            $table->smallInteger('tahun')->default(2026);
            
            // Ruang Lingkup & Kategori (Sesuai Roadmap Butir III.A, III.B, III.C)
            $table->foreignId('unit_id')->constrained('units')->cascadeOnDelete();
            $table->enum('ruang_lingkup', ['unit', 'rs', 'mfk'])->default('unit');
            $table->boolean('is_rs_priority')->default(false); // Apakah ditarik ke Tingkat RS
            
            $table->string('kategori_risiko', 100); // Klinis, Non-Klinis/Operasional, Finansial, Hukum/Regulasi, MFK/K3RS
            $table->string('kategori_mfk', 100)->nullable(); // Keselamatan & Keamanan, B3, Bencana, Proteksi Kebakaran, Alat Medis, Sistem Utilitas
            
            // 1 & 2. Identifikasi Risiko
            $table->string('pernyataan_risiko'); // Judul/Pernyataan Risiko
            $table->text('deskripsi')->nullable();
            $table->text('penyebab')->nullable(); // Akar Masalah / Faktor Kontributor
            $table->text('dampak')->nullable(); // Konsekuensi Klinis/Finansial/Operasional
            
            // 3 & 4. Analisis & Profil Risiko (Inherent Risk / Risiko Awal)
            $table->tinyInteger('inherent_probabilitas')->default(1); // 1 s.d 5
            $table->tinyInteger('inherent_dampak')->default(1); // 1 s.d 5
            $table->tinyInteger('inherent_skor')->default(1); // probabilitas x dampak (1 s.d 25)
            $table->enum('inherent_band', ['rendah', 'sedang', 'tinggi', 'ekstrem'])->default('rendah');
            
            // 5. Pengendalian yang Ada Saat Ini (Existing Controls)
            $table->text('pengendalian_ada')->nullable();
            $table->enum('efektivitas_pengendalian', ['efektif', 'cukup', 'kurang'])->default('cukup');
            
            // 6. Rencana Penanganan / Mitigasi
            $table->enum('opsi_penanganan', ['mitigasi', 'hindari', 'transfer', 'terima'])->default('mitigasi');
            $table->text('rencana_penanganan')->nullable();
            $table->date('target_waktu')->nullable();
            $table->string('penanggung_jawab')->nullable(); // Risk Owner / PIC
            
            // Residu Risiko (Residual Risk setelah mitigasi)
            $table->tinyInteger('residual_probabilitas')->nullable();
            $table->tinyInteger('residual_dampak')->nullable();
            $table->tinyInteger('residual_skor')->nullable();
            $table->enum('residual_band', ['rendah', 'sedang', 'tinggi', 'ekstrem'])->nullable();
            
            // 7. Pemantauan & Status
            $table->enum('status_pemantauan', ['draft', 'dalam_proses', 'terkendali', 'ditutup'])->default('dalam_proses');
            $table->text('catatan_pemantauan')->nullable();
            $table->date('tanggal_review')->nullable();
            
            // Creator
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            
            $table->timestamps();

            // Indexes for Performance
            $table->index(['tahun', 'ruang_lingkup']);
            $table->index(['unit_id', 'tahun']);
            $table->index('inherent_band');
            $table->index('status_pemantauan');
            $table->index('is_rs_priority');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('risk_registers');
    }
};

