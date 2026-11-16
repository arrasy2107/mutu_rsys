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
        // 1. Definisi kolom/variabel instrumen pengumpulan data per indikator
        Schema::create('indicator_form_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('indicator_id')->constrained('indicators')->cascadeOnDelete();
            $table->string('name', 255); // contoh: "Jlh Pendamping yg ssi jadwal"
            $table->string('key', 50);   // slug: num, den, aux_1, aux_2, dll
            $table->enum('type', ['numerator', 'denominator', 'auxiliary'])->default('auxiliary');
            $table->unsignedTinyInteger('order')->default(1);
            $table->timestamps();

            $table->index(['indicator_id', 'order']);
        });

        // 2. Pencatatan log nilai harian (tanggal 1 s.d. 31)
        Schema::create('indicator_daily_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('indicator_id')->constrained('indicators')->cascadeOnDelete();
            $table->foreignId('unit_id')->constrained('units')->cascadeOnDelete();
            $table->unsignedSmallInteger('tahun');
            $table->unsignedTinyInteger('bulan'); // 1 - 12
            $table->unsignedTinyInteger('hari');  // 1 - 31
            $table->date('tanggal')->nullable();
            $table->json('data')->nullable(); // format: {"num": 19, "den": 30, "aux_1": 11}
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['indicator_id', 'unit_id', 'tahun', 'bulan', 'hari'], 'uniq_daily_entry');
            $table->index(['indicator_id', 'unit_id', 'tahun', 'bulan']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('indicator_daily_records');
        Schema::dropIfExists('indicator_form_fields');
    }
};
