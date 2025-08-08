<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Reminder;
use App\Models\User;

class ReminderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get a user to create reminders for
        $user = User::first();
        
        if (!$user) {
            $this->command->info('No users found. Please run UserSeeder first.');
            return;
        }

        // Create sample reminders
        $reminders = [
            [
                'user_id' => $user->id,
                'element_type' => 'remedy',
                'element_id' => 1,
                'day' => 'monday',
                'time' => '08:00:00',
                'is_active' => true,
            ],
            [
                'user_id' => $user->id,
                'element_type' => 'article',
                'element_id' => 1,
                'day' => null, // All days
                'time' => '12:30:00',
                'is_active' => true,
            ],
            [
                'user_id' => $user->id,
                'element_type' => 'course',
                'element_id' => 1,
                'day' => 'wednesday',
                'time' => '18:00:00',
                'is_active' => true,
            ],
            [
                'user_id' => $user->id,
                'element_type' => 'video',
                'element_id' => 1,
                'day' => 'friday',
                'time' => '20:15:00',
                'is_active' => false,
            ],
        ];

        foreach ($reminders as $reminderData) {
            Reminder::create($reminderData);
        }

        $this->command->info('ReminderSeeder completed successfully.');
    }
} 