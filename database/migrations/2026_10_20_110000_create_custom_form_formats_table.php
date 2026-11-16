<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('custom_form_formats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_id')->nullable()->constrained('units')->nullOnDelete();
            $table->foreignId('indicator_id')->nullable()->constrained('indicators')->cascadeOnDelete();
            $table->string('nama_format');
            $table->string('table_title');
            $table->string('row_type')->default('fixed_rows'); // daily_grid, fixed_rows, dynamic_rows, entity_rows
            $table->json('schema'); // columns, rows, formula_config
            $table->text('petunjuk_pengisian')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('custom_form_formats');
    }
};
