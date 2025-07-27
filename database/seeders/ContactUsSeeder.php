<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ContactUsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('contact_us')->insert([
            [
                'name' => 'Alice Johnson',
                'email' => 'alice@example.com',
                'phone' => '+1234567890',
                'subject' => 'Inquiry about remedies',
                'message' => 'Can you recommend a remedy for headaches?',
                'status' => 'new',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Bob Smith',
                'email' => 'bob@example.com',
                'phone' => null,
                'subject' => 'Account issue',
                'message' => 'I am unable to log in to my account.',
                'status' => 'read',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Carol Lee',
                'email' => 'carol@example.com',
                'phone' => '+1987654321',
                'subject' => 'Feedback',
                'message' => 'Great app! Very helpful.',
                'status' => 'archived',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
} 