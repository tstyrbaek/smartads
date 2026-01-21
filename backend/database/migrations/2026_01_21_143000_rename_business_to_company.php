<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('brands')) {
            Schema::table('brands', function (Blueprint $table) {
                $table->dropForeign(['business_id']);
                $table->dropUnique(['business_id']);
            });
        }

        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropForeign(['business_id']);
                $table->dropIndex(['business_id']);
            });
        }

        Schema::rename('businesses', 'companies');

        DB::statement('ALTER TABLE brands CHANGE business_id company_id BIGINT UNSIGNED NOT NULL');
        DB::statement('ALTER TABLE users CHANGE business_id company_id BIGINT UNSIGNED NULL');

        Schema::table('brands', function (Blueprint $table) {
            $table->unique('company_id');
            $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->index('company_id');
            $table->foreign('company_id')->references('id')->on('companies')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('brands')) {
            Schema::table('brands', function (Blueprint $table) {
                $table->dropForeign(['company_id']);
                $table->dropUnique(['company_id']);
            });
        }

        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropForeign(['company_id']);
                $table->dropIndex(['company_id']);
            });
        }

        DB::statement('ALTER TABLE brands CHANGE company_id business_id BIGINT UNSIGNED NOT NULL');
        DB::statement('ALTER TABLE users CHANGE company_id business_id BIGINT UNSIGNED NULL');

        Schema::rename('companies', 'businesses');

        Schema::table('brands', function (Blueprint $table) {
            $table->unique('business_id');
            $table->foreign('business_id')->references('id')->on('businesses')->cascadeOnDelete();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->index('business_id');
            $table->foreign('business_id')->references('id')->on('businesses')->nullOnDelete();
        });
    }
};
