<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('data_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('indicator_id')->constrained('indicators')->cascadeOnDelete();
            $table->foreignId('period_id')->constrained('indicator_periods')->cascadeOnDelete();
            $table->foreignId('unit_id')->constrained('units')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->date('tanggal')->nullable(); // untuk frekuensi harian
            $table->decimal('numerator', 12, 2)->default(0);
            $table->decimal('denominator', 12, 2)->default(0);
            $table->decimal('capaian', 6, 2)->nullable(); // auto-computed: (num/den)*100
            $table->text('catatan')->nullable();
            $table->string('file_bukti')->nullable(); // path file upload
            $table->enum('status', ['draft', 'submitted', 'approved', 'rejected', 'revision'])
                  ->default('draft');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();

            // Satu entri per hari per indikator per unit (untuk harian)
            // atau satu entri per periode per unit (untuk bulanan)
            $table->index(['indicator_id', 'period_id', 'unit_id', 'tanggal'], 'idx_entry_lookup');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('data_entries');
    }
};
