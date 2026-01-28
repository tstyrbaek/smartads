<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ad;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdController extends Controller
{
    public function index(Request $request): View
    {
        $companyId = $request->query('company_id');

        $sort = $request->query('sort', 'created_at');
        $dir = $request->query('dir', 'desc');

        $dir = $dir === 'asc' ? 'asc' : 'desc';

        $adsQuery = Ad::query()
            ->with(['company', 'user']);

        if ($sort === 'company') {
            $adsQuery
                ->leftJoin('companies', 'ads.company_id', '=', 'companies.id')
                ->select('ads.*')
                ->orderBy('companies.name', $dir);
        } elseif ($sort === 'user') {
            $adsQuery
                ->leftJoin('users', 'ads.user_id', '=', 'users.id')
                ->select('ads.*')
                ->orderBy('users.name', $dir);
        } else {
            $allowedSorts = [
                'created_at' => 'ads.created_at',
                'total_tokens' => 'ads.total_tokens',
                'status' => 'ads.status',
                'company_id' => 'ads.company_id',
                'id' => 'ads.id',
            ];

            $column = $allowedSorts[$sort] ?? 'ads.created_at';
            $adsQuery->orderBy($column, $dir);
        }

        if (is_string($companyId) && $companyId !== '') {
            $adsQuery->where('company_id', $companyId);
        }

        $ads = $adsQuery->paginate(50)->withQueryString();

        $usdToDkk = (float) config('services.gemini.usd_to_dkk', 7.0);
        $usdPerInputToken = 2 / 1_000_000;
        $usdPerOutputImageToken = 120 / 1_000_000;

        $ads->getCollection()->transform(function (Ad $ad) use ($usdToDkk, $usdPerInputToken, $usdPerOutputImageToken) {
            $priceDkk = null;

            if ($ad->status === 'success' && is_numeric($ad->prompt_tokens) && is_numeric($ad->output_tokens)) {
                $promptTokens = (int) $ad->prompt_tokens;
                $outputTokens = (int) $ad->output_tokens;

                $priceUsd = ($promptTokens * $usdPerInputToken) + ($outputTokens * $usdPerOutputImageToken);
                $priceDkk = $priceUsd * $usdToDkk;
            }

            $ad->setAttribute('estimated_price_dkk', $priceDkk);

            return $ad;
        });

        return view('admin.ads.index', [
            'ads' => $ads,
            'companies' => Company::query()->orderBy('name')->get(),
            'selectedCompanyId' => is_string($companyId) ? $companyId : '',
            'sort' => is_string($sort) ? $sort : 'created_at',
            'dir' => $dir,
        ]);
    }
}
