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
        Schema::create('patient_safety_incidents', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_laporan', 50)->unique();
            $table->date('tanggal_laporan');
            $table->dateTime('tanggal_kejadian');
            $table->string('tipe_insiden', 100); // Prosedur Medis, Medikasi/Farmasi, Dokumentasi, Pasien Jatuh, dll.
            $table->string('insiden'); // Ringkasan/Judul Insiden
            $table->text('kronologi');
            $table->enum('jenis_insiden', ['KTC', 'KTD', 'KNC', 'KPC', 'Sentinel']);
            $table->enum('grading', ['biru', 'hijau', 'kuning', 'merah']);
            
            // Relasi Unit
            $table->foreignId('unit_pelapor_id')->constrained('units')->cascadeOnDelete();
            $table->foreignId('unit_terkait_id')->nullable()->constrained('units')->nullOnDelete();
            
            // Penyelesaian & Tindak Lanjut
            $table->enum('tipe_penyelesaian', ['investigasi_sederhana', 'rca'])->nullable();
            $table->date('tanggal_selesai_penyelesaian')->nullable();
            $table->enum('kriteria_waktu_penyelesaian', ['sesuai', 'tidak_sesuai', 'dalam_proses'])->default('dalam_proses');
            
            $table->text('rencana_tindak_lanjut')->nullable();
            $table->string('penanggung_jawab_rtl')->nullable();
            $table->string('bukti_rtl_path')->nullable();
            
            // Status & Petugas
            $table->enum('status', ['draft', 'dilaporkan', 'investigasi', 'selesai'])->default('dilaporkan');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            
            $table->timestamps();

            // Indexes for Analytics/Mapping Filters
            $table->index(['tanggal_laporan', 'jenis_insiden']);
            $table->index(['grading', 'tipe_insiden']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('patient_safety_incidents');
    }
};

