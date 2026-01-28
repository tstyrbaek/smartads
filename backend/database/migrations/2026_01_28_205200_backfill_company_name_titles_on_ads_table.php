<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('ads')
            ->leftJoin('companies', 'ads.company_id', '=', 'companies.id')
            ->select(['ads.id', 'ads.company_id', 'ads.title', 'ads.created_at', 'companies.name as company_name'])
            ->orderBy('ads.id')
            ->chunk(500, function ($rows) {
                foreach ($rows as $row) {
                    $id = (string) ($row->id ?? '');
                    $companyId = (string) ($row->company_id ?? '');
                    if ($id === '' || $companyId === '') {
                        continue;
                    }

                    $existingTitle = $row->title;

                    $isLegacyLong = is_string($existingTitle) && $existingTitle === ($companyId . '-' . $id);
                    $isLegacyShort = is_string($existingTitle) && preg_match('/^' . preg_quote($companyId, '/') . '-\d{6}-[A-Z0-9]{6}$/', $existingTitle) === 1;
                    $shouldUpdate = ($existingTitle === null) || $isLegacyLong || $isLegacyShort;

                    if (!$shouldUpdate) {
                        continue;
                    }

                    $companyName = (string) ($row->company_name ?? 'company');
                    $companyPart = Str::slug($companyName);
                    if ($companyPart === '') {
                        $companyPart = 'company';
                    }

                    $createdAt = $row->created_at ? Carbon::parse($row->created_at) : now();
                    $datePart = $createdAt->format('ymd');
                    $shortId = substr($id, -6);
                    $title = $companyPart . '-' . $datePart . '-' . $shortId;

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
