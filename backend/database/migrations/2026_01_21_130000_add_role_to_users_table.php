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
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('business')->after('password');
            $table->unsignedBigInteger('partner_id')->nullable()->after('role');
            $table->unsignedBigInteger('business_id')->nullable()->after('partner_id');

            $table->index('role');
            $table->index('partner_id');
            $table->index('business_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['role']);
            $table->dropIndex(['partner_id']);
            $table->dropIndex(['business_id']);

            $table->dropColumn(['role', 'partner_id', 'business_id']);
        });
    }
};
