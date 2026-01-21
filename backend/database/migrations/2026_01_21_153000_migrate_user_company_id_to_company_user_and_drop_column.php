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
        if (Schema::hasColumn('users', 'company_id')) {
            $rows = DB::table('users')
                ->select(['id', 'company_id'])
                ->whereNotNull('company_id')
                ->get();

            foreach ($rows as $row) {
                $exists = DB::table('company_user')
                    ->where('company_id', $row->company_id)
                    ->where('user_id', $row->id)
                    ->exists();

                if (!$exists) {
                    DB::table('company_user')->insert([
                        'company_id' => $row->company_id,
                        'user_id' => $row->id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            Schema::table('users', function (Blueprint $table) {
                if (Schema::hasColumn('users', 'company_id')) {
                    try {
                        $table->dropForeign(['company_id']);
                    } catch (Throwable $e) {
                        // ignore
                    }

                    try {
                        $table->dropIndex(['company_id']);
                    } catch (Throwable $e) {
                        // ignore
                    }

                    $table->dropColumn('company_id');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'company_id')) {
                $table->unsignedBigInteger('company_id')->nullable()->after('partner_id');
            }
        });

        Schema::table('users', function (Blueprint $table) {
            try {
                $table->foreign('company_id')->references('id')->on('companies')->nullOnDelete();
            } catch (Throwable $e) {
                // ignore
            }
        });

        $rows = DB::table('company_user')
            ->select(['user_id', 'company_id'])
            ->orderBy('id')
            ->get();

        foreach ($rows as $row) {
            DB::table('users')
                ->where('id', $row->user_id)
                ->whereNull('company_id')
                ->update(['company_id' => $row->company_id]);
        }
    }
};
