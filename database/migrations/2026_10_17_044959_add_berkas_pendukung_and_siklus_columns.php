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
        if (Schema::hasTable('indicator_selections') && !Schema::hasColumn('indicator_selections', 'berkas_pendukung')) {
            Schema::table('indicator_selections', function (Blueprint $table) {
                $table->text('berkas_pendukung')->nullable()->after('notulen');
            });
        }

        if (Schema::hasTable('pdsa_records') && !Schema::hasColumn('pdsa_records', 'siklus')) {
            Schema::table('pdsa_records', function (Blueprint $table) {
                $table->unsignedInteger('siklus')->default(1)->after('id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('indicator_selections') && Schema::hasColumn('indicator_selections', 'berkas_pendukung')) {
            Schema::table('indicator_selections', function (Blueprint $table) {
                $table->dropColumn('berkas_pendukung');
            });
        }

        if (Schema::hasTable('pdsa_records') && Schema::hasColumn('pdsa_records', 'siklus')) {
            Schema::table('pdsa_records', function (Blueprint $table) {
                $table->dropColumn('siklus');
            });
        }
    }
};
