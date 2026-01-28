<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('ads')
            ->select(['id', 'company_id', 'title', 'created_at'])
            ->orderBy('id')
            ->chunk(500, function ($rows) {
                foreach ($rows as $row) {
                    $id = (string) ($row->id ?? '');
                    $companyId = (string) ($row->company_id ?? '');
                    if ($id === '' || $companyId === '') {
                        continue;
                    }

                    $existingTitle = $row->title;
                    $isLegacyTitle = is_string($existingTitle) && $existingTitle === ($companyId . '-' . $id);
                    $shouldUpdate = ($existingTitle === null) || $isLegacyTitle;

                    if (!$shouldUpdate) {
                        continue;
                    }

                    $createdAt = $row->created_at ? Carbon::parse($row->created_at) : now();
                    $datePart = $createdAt->format('ymd');
                    $shortId = substr($id, -6);
                    $title = $companyId . '-' . $datePart . '-' . $shortId;

                    DB::table('ads')
                        ->where('id', $id)
                        ->update(['title' => $title]);
                }
            });
    }

    public function down(): void
    {
    }
};
