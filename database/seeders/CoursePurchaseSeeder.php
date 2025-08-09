<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\CoursePurchase;
use App\Models\User;
use App\Models\Course;

class CoursePurchaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::take(5)->get();
        $courses = Course::take(3)->get();

        foreach ($users as $user) {
            foreach ($courses as $course) {
                // Randomly decide if user purchased this course
                if (rand(0, 1)) {
                    CoursePurchase::create([
                        'user_id' => $user->id,
                        'course_id' => $course->id,
                        'payment_method' => null,
                        'payment_token' => null,
                        'amount_paid' => $course->price,
                        'status' => 'completed',
                        'purchased_at' => now()->subDays(rand(1, 30)),
                    ]);
                }
            }
        }
    }
} 