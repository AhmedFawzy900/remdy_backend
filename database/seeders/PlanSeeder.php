<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('plans')->insert([
            [
                'name' => 'Rookie',
                'slug' => 'rookie',
                'price_monthly' => 0,
                'price_yearly' => 0,
                'trial_days' => null,
                'description' => 'Perfect for beginners exploring natural remedies.',
                'features' => json_encode([
                    'Access limited remedy library',
                    'Watch free educational videos',
                    'Add up to 10 items to your Kit',
                    'Basic profile & preferences',
                    'Standard notifications',
                ]),
                'badge_emoji' => '🧪',
                'status' => 'active',
                'order' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Skilled',
                'slug' => 'skilled',
                'price_monthly' => 9.99,
                'price_yearly' => 99.00,
                'trial_days' => 3,
                'description' => 'For those ready to go deeper into holistic healing.',
                'features' => json_encode([
                    'Unlock all remedies',
                    'Unlock all videos',
                    'Add Unlimited remedies to kit',
                    'AI Remedy Search',
                    'Priority content access',
                    'Smart search & filters',
                    'Ad-free experience',
                    'Save 17% with yearly plan',
                    '3-day free trial included',
                ]),
                'badge_emoji' => '🌿',
                'status' => 'active',
                'order' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Master',
                'slug' => 'master',
                'price_monthly' => 29.99,
                'price_yearly' => 249.00,
                'trial_days' => 3,
                'description' => 'For serious users, practitioners, or holistic educators.',
                'features' => json_encode([
                    'All Skilled features',
                    'AI Remedy Assistant – get tailored suggestions based on symptoms',
                    'Unlock all premium health courses',
                    'Course progress tracking',
                    'Remedy Scheduler',
                    'Community badge and VIP perks',
                    'Save 30% with yearly plan',
                    '3-day free trial included',
                ]),
                'badge_emoji' => '🔮',
                'status' => 'active',
                'order' => 3,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
} 