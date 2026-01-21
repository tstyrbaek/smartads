<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ads', function (Blueprint $table) {
            $table->ulid('id')->primary();

            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            $table->text('text');
            $table->string('status', 32);

            $table->text('error')->nullable();
            $table->string('local_file_path')->nullable();

            $table->json('input_image_paths')->nullable();
            $table->json('debug')->nullable();

            $table->timestamps();

            $table->index(['company_id', 'updated_at']);
            $table->index(['company_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ads');
    }
};
