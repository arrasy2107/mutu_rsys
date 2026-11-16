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
        Schema::table('data_entries', function (Blueprint $table) {
            $table->json('workbook_snapshot')->nullable()->after('form_schema_version');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('data_entries', function (Blueprint $table) {
            $table->dropColumn('workbook_snapshot');
        });
    }
};
