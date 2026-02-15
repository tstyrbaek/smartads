<?php

namespace Database\Seeders;

use App\Models\IntegrationDefinition;
use Illuminate\Database\Seeder;

class IntegrationDefinitionSeeder extends Seeder
{
    public function run(): void
    {
        IntegrationDefinition::query()->updateOrCreate(
            ['key' => 'website_embed'],
            [
                'type' => 'publish',
                'name' => 'Website embed',
                'description' => null,
                'capabilities' => ['publish_ad', 'embed_script'],
                'is_active' => true,
            ]
        );
    }
}
