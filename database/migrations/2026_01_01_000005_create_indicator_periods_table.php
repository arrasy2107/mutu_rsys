<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('indicator_periods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('indicator_id')->constrained('indicators')->cascadeOnDelete();
            $table->foreignId('unit_id')->constrained('units')->cascadeOnDelete();
            $table->smallInteger('tahun');
            $table->tinyInteger('bulan'); // 1-12
            $table->enum('status', ['draft', 'open', 'closed', 'validated'])->default('open');
            $table->date('deadline_input')->nullable(); // Batas input data
            $table->timestamp('tanggal_validasi')->nullable();
            $table->foreignId('validated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['indicator_id', 'unit_id', 'tahun', 'bulan'], 'unique_period');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('indicator_periods');
    }
};
