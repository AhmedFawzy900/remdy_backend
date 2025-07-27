<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ReviewSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('reviews')->insert([
            [
                'user_id' => 1,
                'type' => 'remedy',
                'element_id' => 1,
                'rate' => 5,
                'message' => 'This remedy worked wonders for me!',
                'status' => 'accepted',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'user_id' => 1,
                'type' => 'course',
                'element_id' => 1,
                'rate' => 4,
                'message' => 'The course was very informative and well structured.',
                'status' => 'accepted',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'user_id' => 1,
                'type' => 'video',
                'element_id' => 1,
                'rate' => 5,
                'message' => 'Great video, easy to follow instructions!',
                'status' => 'accepted',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
} 