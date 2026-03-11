<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE integration_instances MODIFY credentials LONGTEXT NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE integration_instances MODIFY credentials JSON NULL');
    }
};
