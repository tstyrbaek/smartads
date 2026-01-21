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
        Schema::table('brands', function (Blueprint $table) {
            $table->string('color_1', 7)->nullable()->after('logo_path');
            $table->string('color_2', 7)->nullable()->after('color_1');
            $table->string('color_3', 7)->nullable()->after('color_2');
            $table->string('color_4', 7)->nullable()->after('color_3');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('brands', function (Blueprint $table) {
            $table->dropColumn(['color_1', 'color_2', 'color_3', 'color_4']);
        });
    }
};
