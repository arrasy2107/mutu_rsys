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
        if (Schema::hasTable('indicator_selections')) {
            Schema::table('indicator_selections', function (Blueprint $table) {
                if (!Schema::hasColumn('indicator_selections', 'pemimpin_rapat_id')) {
                    $table->foreignId('pemimpin_rapat_id')->nullable()->after('status')->constrained('users')->nullOnDelete();
                }
                if (!Schema::hasColumn('indicator_selections', 'pemimpin_rapat_nama')) {
                    $table->string('pemimpin_rapat_nama')->nullable()->after('pemimpin_rapat_id');
                }
                if (!Schema::hasColumn('indicator_selections', 'imp_item_id')) {
                    $table->unsignedBigInteger('imp_item_id')->nullable()->after('pemimpin_rapat_nama');
                }
            });
        }

        if (Schema::hasTable('indicator_selection_items')) {
            Schema::table('indicator_selection_items', function (Blueprint $table) {
                if (!Schema::hasColumn('indicator_selection_items', 'is_imp')) {
                    $table->boolean('is_imp')->default(false)->after('total_skor');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('indicator_selection_items')) {
            Schema::table('indicator_selection_items', function (Blueprint $table) {
                if (Schema::hasColumn('indicator_selection_items', 'is_imp')) {
                    $table->dropColumn('is_imp');
                }
            });
        }

        if (Schema::hasTable('indicator_selections')) {
            Schema::table('indicator_selections', function (Blueprint $table) {
                $cols = [];
                if (Schema::hasColumn('indicator_selections', 'imp_item_id')) $cols[] = 'imp_item_id';
                if (Schema::hasColumn('indicator_selections', 'pemimpin_rapat_nama')) $cols[] = 'pemimpin_rapat_nama';
                if (Schema::hasColumn('indicator_selections', 'pemimpin_rapat_id')) {
                    $table->dropForeign(['pemimpin_rapat_id']);
                    $cols[] = 'pemimpin_rapat_id';
                }
                if (!empty($cols)) {
                    $table->dropColumn($cols);
                }
            });
        }
    }
};
