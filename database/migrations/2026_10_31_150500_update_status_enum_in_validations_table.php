<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Ubah status agar mendukung 'submitted', 'approved', 'rejected', 'revision'
        Schema::table('validations', function (Blueprint $table) {
            $table->string('status', 20)->default('submitted')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('validations', function (Blueprint $table) {
            $table->enum('status', ['approved', 'rejected', 'revision'])->change();
        });
    }
};

