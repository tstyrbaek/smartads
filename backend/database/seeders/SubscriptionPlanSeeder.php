<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SubscriptionPlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'name' => 'Basic',
                'description' => 'Perfekt til små virksomheder og startups',
                'max_tokens_per_month' => 10000,
                'price_per_month' => 299.00,
                'features' => [
                    'Op til 10.000 tokens pr. måned',
                    'Standard annonce generering',
                    'Basic support',
                    '1 GB lagerplads',
                ],
                'is_active' => true,
            ],
            [
                'name' => 'Pro',
                'description' => 'Ideel til voksende virksomheder',
                'max_tokens_per_month' => 50000,
                'price_per_month' => 799.00,
                'features' => [
                    'Op til 50.000 tokens pr. måned',
                    'Avanceret annonce generering',
                    'Priority support',
                    '10 GB lagerplads',
                    'Custom branding',
                    'API adgang',
                ],
                'is_active' => true,
            ],
            [
                'name' => 'Enterprise',
                'description' => 'Komplet løsning til store virksomheder',
                'max_tokens_per_month' => 200000,
                'price_per_month' => 2499.00,
                'features' => [
                    'Op til 200.000 tokens pr. måned',
                    'Ubegrænset annonce generering',
                    '24/7 support',
                    'Ubegrænset lagerplads',
                    'Custom integrations',
                    'Dedicated account manager',
                    'White label options',
                    'Custom AI modeller',
                ],
                'is_active' => true,
            ],
        ];

        foreach ($plans as $plan) {
            DB::table('subscription_plans')->insert([
                'name' => $plan['name'],
                'description' => $plan['description'],
                'max_tokens_per_month' => $plan['max_tokens_per_month'],
                'price_per_month' => $plan['price_per_month'],
                'features' => json_encode($plan['features']),
                'is_active' => $plan['is_active'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
