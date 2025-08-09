<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\LessonProgress;
use App\Models\User;
use App\Models\Course;
use App\Models\Lesson;

class LessonProgressSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::take(5)->get();
        $courses = Course::with('lessons')->take(3)->get();

        foreach ($users as $user) {
            foreach ($courses as $course) {
                // Check if user purchased this course
                $purchase = $course->purchases()->where('user_id', $user->id)->first();
                
                if ($purchase) {
                    foreach ($course->lessons as $lesson) {
                        // Randomly decide lesson status
                        $status = ['not_started', 'in_progress', 'completed'][rand(0, 2)];
                        
                        $progressData = [
                            'user_id' => $user->id,
                            'lesson_id' => $lesson->id,
                            'course_id' => $course->id,
                            'status' => $status,
                            'started_at' => now()->subDays(rand(1, 20)),
                        ];

                        if ($status === 'completed') {
                            $progressData['completed_at'] = now()->subDays(rand(1, 10));
                        }

                        LessonProgress::create($progressData);
                    }
                }
            }
        }
    }
} 