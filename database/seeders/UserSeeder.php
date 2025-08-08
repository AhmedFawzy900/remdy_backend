<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = [
            [
                'name' => 'John Smith',
                'email' => 'john.smith@example.com',
                'password' => Hash::make('password123'),
                'profile_image' => 'https://example.com/images/john-smith.jpg',
                'full_name' => 'John Michael Smith',
                'subscription_plan' => User::PLAN_ROOKIE,
                'account_status' => User::STATUS_ACTIVE,
            ],
            [
                'name' => 'Sarah Johnson',
                'email' => 'sarah.johnson@example.com',
                'password' => Hash::make('password123'),
                'profile_image' => 'https://example.com/images/sarah-johnson.jpg',
                'full_name' => 'Sarah Elizabeth Johnson',
                'subscription_plan' => User::PLAN_MASTER,
                'account_status' => User::STATUS_ACTIVE,
            ],
            [
                'name' => 'Mike Davis',
                'email' => 'mike.davis@example.com',
                'password' => Hash::make('password123'),
                'profile_image' => 'https://example.com/images/mike-davis.jpg',
                'full_name' => 'Michael Robert Davis',
                'subscription_plan' => User::PLAN_SKILLED,
                'account_status' => User::STATUS_ACTIVE,
            ],
            [
                'name' => 'Emily Wilson',
                'email' => 'emily.wilson@example.com',
                'password' => Hash::make('password123'),
                'profile_image' => 'https://example.com/images/emily-wilson.jpg',
                'full_name' => 'Emily Grace Wilson',
                'subscription_plan' => User::PLAN_ROOKIE,
                'account_status' => User::STATUS_INACTIVE,
            ],
            [
                'name' => 'David Brown',
                'email' => 'david.brown@example.com',
                'password' => Hash::make('password123'),
                'profile_image' => 'https://example.com/images/david-brown.jpg',
                'full_name' => 'David Christopher Brown',
                'subscription_plan' => User::PLAN_MASTER,
                'account_status' => User::STATUS_SUSPENDED,
            ],
            [
                'name' => 'Lisa Garcia',
                'email' => 'lisa.garcia@example.com',
                'password' => Hash::make('password123'),
                'profile_image' => 'https://example.com/images/lisa-garcia.jpg',
                'full_name' => 'Lisa Maria Garcia',
                'subscription_plan' => User::PLAN_ROOKIE,
                'account_status' => User::STATUS_ACTIVE,
            ],
            [
                'name' => 'Tom Anderson',
                'email' => 'tom.anderson@example.com',
                'password' => Hash::make('password123'),
                'profile_image' => 'https://example.com/images/tom-anderson.jpg',
                'full_name' => 'Thomas James Anderson',
                'subscription_plan' => User::PLAN_SKILLED,
                'account_status' => User::STATUS_ACTIVE,
            ],
            [
                'name' => 'Jessica Lee',
                'email' => 'jessica.lee@example.com',
                'password' => Hash::make('password123'),
                'profile_image' => 'https://example.com/images/jessica-lee.jpg',
                'full_name' => 'Jessica Ann Lee',
                'subscription_plan' => User::PLAN_MASTER,
                'account_status' => User::STATUS_ACTIVE,
            ]
        ];

        foreach ($users as $user) {
            User::create($user);
        }
    }
}
