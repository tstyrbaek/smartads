<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ads', function (Blueprint $table) {
            $table->text('instructions')->nullable()->after('text');
            $table->longText('prompt')->nullable()->after('instructions');
            $table->string('prompt_version', 32)->default('v1')->after('prompt');
            $table->json('brand_snapshot')->nullable()->after('prompt_version');
        });
    }

    public function down(): void
    {
        Schema::table('ads', function (Blueprint $table) {
            $table->dropColumn(['instructions', 'prompt', 'prompt_version', 'brand_snapshot']);
        });
    }
};
