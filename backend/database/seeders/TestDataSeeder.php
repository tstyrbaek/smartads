<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class TestDataSeeder extends Seeder
{
    public function run(): void
    {
        // Create test user
        $user = User::firstOrCreate(
            ['email' => 'test@smartads.com'],
            [
                'name' => 'Test User',
                'password' => Hash::make('password'),
                'role' => 'business',
            ]
        );

        // Create or update test company
        $company = Company::firstOrCreate(
            ['name' => 'Test Company A/S'],
            [
                'website_url' => 'https://testcompany.dk',
                'company_description' => 'Vi er en test virksomhed der sælger innovative produkter til danske forbrugere.',
                'target_audience_description' => 'Vores målgruppe er mænd og kvinder mellem 25-45 år med interesse i teknologi og design.',
            ]
        );

        // Attach user to company if not already attached
        if (!$company->users()->where('user_id', $user->id)->exists()) {
            $company->users()->attach($user->id);
        }

        // Create or update brand for company
        Brand::firstOrCreate(
            ['company_id' => $company->id],
            [
                'name' => $company->name . ' Brand',
                'logo_path' => 'brands/test-logo.png',
                'color_1' => '#1E40AF',
                'color_2' => '#3B82F6', 
                'color_3' => '#60A5FA',
                'color_4' => '#93C5FD',
                'fonts' => 'Inter, sans-serif',
                'slogan' => 'Innovation i hverdagen',
                'visual_guidelines' => 'Clean, modern design with blue color scheme. Minimal layout with focus on product photography.',
            ]
        );

        // Create admin user
        User::firstOrCreate(
            ['email' => 'admin@smartads.com'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('password'),
                'role' => 'admin',
            ]
        );

        // Create additional test companies with subscriptions
        $this->createCompanyWithSubscription(
            'Pro Company A/S',
            'pro@smartads.com',
            'Pro plan subscriber',
            2 // Pro plan ID
        );

        $this->createCompanyWithSubscription(
            'Enterprise Corp',
            'enterprise@smartads.com', 
            'Enterprise plan subscriber',
            3 // Enterprise plan ID
        );

        $this->command->info('Test data created successfully!');
        $this->command->info('Login credentials:');
        $this->command->info('Business: test@smartads.com / password');
        $this->command->info('Admin: admin@smartads.com / password');
    }

    private function createCompanyWithSubscription(string $companyName, string $email, string $description, int $planId): void
    {
        DB::transaction(function () use ($companyName, $email, $description, $planId) {
            // Create user
            $user = User::firstOrCreate(
                ['email' => $email],
                [
                    'name' => 'User for ' . $companyName,
                    'password' => Hash::make('password'),
                    'role' => 'business',
                ]
            );

            // Create company
            $company = Company::firstOrCreate(
                ['name' => $companyName],
                [
                    'website_url' => 'https://' . strtolower(str_replace([' ', '/'], ['', '-'], $companyName)) . '.dk',
                    'company_description' => $description . ' - en innovativ virksomhed.',
                    'target_audience_description' => 'Professionelle B2B kunder i Danmark.',
                ]
            );

            // Attach user to company if not already attached
            if (!$company->users()->where('user_id', $user->id)->exists()) {
                $company->users()->attach($user->id);
            }

            // Create or update brand
            Brand::firstOrCreate(
                ['company_id' => $company->id],
                [
                    'name' => $company->name . ' Brand',
                    'logo_path' => 'brands/' . strtolower(str_replace([' ', '/'], ['', '-'], $companyName)) . '-logo.png',
                    'color_1' => '#059669',
                    'color_2' => '#10B981',
                    'color_3' => '#34D399',
                    'color_4' => '#6EE7B7',
                    'fonts' => 'Roboto, sans-serif',
                    'slogan' => 'Quality in Business',
                    'visual_guidelines' => 'Professional B2B design with green color scheme.',
                ]
            );

            // Create subscription if not exists
            $subscription = \App\Models\Subscription::firstOrCreate(
                [
                    'company_id' => $company->id,
                    'plan_id' => $planId,
                    'is_active' => true,
                ],
                [
                    'starts_at' => now(),
                    'ends_at' => now()->addMonth(),
                    'auto_renew' => true,
                ]
            );

            // Update company with subscription
            $company->subscription_id = $subscription->id;
            $company->save();
        });
    }
}
