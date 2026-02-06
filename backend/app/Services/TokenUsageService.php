<?php

namespace App\Services;

use App\Models\Ad;
use App\Models\Company;
use App\Models\MonthlyTokenUsage;
use App\Models\TokenUsage;
use Illuminate\Support\Facades\DB;

class TokenUsageService
{
    public function recordTokenUsage(Ad $ad): void
    {
        if (!$ad->total_tokens) {
            return;
        }

        // Record detailed token usage
        TokenUsage::create([
            'ad_id' => $ad->id,
            'company_id' => $ad->company_id,
            'user_id' => $ad->user_id,
            'token_type' => 'total',
            'tokens_used' => $ad->total_tokens,
            'cost_dkk' => $this->calculateCost($ad->total_tokens),
        ]);

        // Update monthly usage
        $this->updateMonthlyUsage($ad->company_id, $ad->total_tokens);
    }

    public function getMonthlyUsage(int $companyId, int $year = null, int $month = null): MonthlyTokenUsage
    {
        $year = $year ?? now()->year;
        $month = $month ?? now()->month;

        return MonthlyTokenUsage::firstOrCreate(
            [
                'company_id' => $companyId,
                'year' => $year,
                'month' => $month,
            ],
            [
                'total_tokens' => 0,
                'total_cost_dkk' => 0,
            ]
        );
    }

    public function getRemainingTokens(Company $company): int
    {
        return $company->getRemainingTokensThisMonth();
    }

    public function canGenerateAd(Company $company, int $estimatedTokens = 1000): bool
    {
        $remaining = $this->getRemainingTokens($company);
        return $remaining >= $estimatedTokens;
    }

    public function getUsagePercentage(Company $company): float
    {
        $subscription = $company->getCurrentSubscription();
        if (!$subscription) {
            return 100.0; // No subscription = 100% used
        }

        $maxTokens = $subscription->plan->max_tokens_per_month;
        if ($maxTokens === 0) {
            return 0.0;
        }

        $used = $maxTokens - $this->getRemainingTokens($company);
        return min(100.0, ($used / $maxTokens) * 100);
    }

    private function updateMonthlyUsage(int $companyId, int $tokens): void
    {
        $monthlyUsage = $this->getMonthlyUsage($companyId);
        
        $monthlyUsage->total_tokens += $tokens;
        $monthlyUsage->total_cost_dkk += $this->calculateCost($tokens);
        $monthlyUsage->save();
    }

    private function calculateCost(int $tokens): float
    {
        // Using the same conversion rate as in Ad model
        $usdToDkk = 6.5; // From config or env
        $costPerToken = 0.000004; // Example rate
        
        return $tokens * $costPerToken * $usdToDkk;
    }

    public function getUsageHistory(Company $company, int $months = 6): array
    {
        $history = MonthlyTokenUsage::where('company_id', $company->id)
            ->orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->limit($months)
            ->get()
            ->reverse();

        return $history->map(function ($usage) {
            return [
                'period' => $usage->year . '-' . str_pad($usage->month, 2, '0', STR_PAD_LEFT),
                'tokens' => $usage->total_tokens,
                'cost' => $usage->total_cost_dkk,
            ];
        })->toArray();
    }
}
