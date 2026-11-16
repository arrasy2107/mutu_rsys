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
            $table->json('log_harian')->nullable()->after('denominator')->comment('Format: {"1":{"n":0,"d":0}, "2":...}');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('data_entries', function (Blueprint $table) {
            $table->dropColumn('log_harian');
        });
    }
};
