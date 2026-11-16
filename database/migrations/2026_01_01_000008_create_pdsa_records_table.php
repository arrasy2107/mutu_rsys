<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pdsa_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('indicator_id')->constrained('indicators')->cascadeOnDelete();
            $table->foreignId('period_id')->constrained('indicator_periods')->cascadeOnDelete();
            $table->foreignId('unit_id')->constrained('units')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->decimal('capaian_aktual', 6, 2)->nullable(); // Capaian yg diraih
            $table->decimal('target_capaian', 6, 2)->nullable(); // Target yang seharusnya
            $table->text('masalah'); // Uraian masalah
            $table->text('akar_masalah')->nullable(); // Analisis akar masalah
            $table->text('plan')->nullable(); // Rencana perbaikan
            $table->text('do_action')->nullable(); // Tindakan yang dilakukan
            $table->text('study')->nullable(); // Evaluasi hasil
            $table->text('act')->nullable(); // Standardisasi/tindak lanjut
            $table->date('rencana_selesai')->nullable();
            $table->enum('status', ['draft', 'submitted', 'reviewed'])->default('draft');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('catatan_review')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pdsa_records');
    }
};
