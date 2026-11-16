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
        Schema::table('data_entries', function (Blueprint $table): void {
            $table->unsignedSmallInteger('form_schema_version')->nullable()->after('log_harian');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('data_entries', function (Blueprint $table): void {
            $table->dropColumn('form_schema_version');
        });
    }
};
