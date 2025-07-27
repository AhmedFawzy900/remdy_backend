<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class NotificationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('notifications')->insert([
            [
                'user_id' => 1,
                'admin_id' => null,
                'title' => 'Welcome to Natural Remedies!',
                'body' => 'Thank you for joining our platform. Explore remedies and courses now.',
                'type' => 'success',
                'status' => 'unread',
                'data' => json_encode(['action' => 'explore']),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'user_id' => null,
                'admin_id' => 1,
                'title' => 'New Contact Message',
                'body' => 'You have received a new contact message from Alice Johnson.',
                'type' => 'info',
                'status' => 'unread',
                'data' => json_encode(['contact_id' => 1]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'user_id' => 1,
                'admin_id' => null,
                'title' => 'Course Update',
                'body' => 'A new session has been added to your course.',
                'type' => 'custom',
                'status' => 'read',
                'data' => json_encode(['course_id' => 1]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
} 