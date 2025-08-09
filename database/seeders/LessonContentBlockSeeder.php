<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Lesson;
use App\Models\LessonContentBlock;

class LessonContentBlockSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get some lessons to add content blocks to
        $lessons = Lesson::take(5)->get();

        foreach ($lessons as $lesson) {
            // Add a video block
            LessonContentBlock::create([
                'lesson_id' => $lesson->id,
                'type' => 'video',
                'content' => [
                    'title' => 'Introduction Video',
                    'description' => 'Watch this video to understand the basics of this lesson.',
                    'link' => 'https://example.com/video1.mp4',
                    'thumbnail' => 'https://example.com/thumbnail1.jpg',
                    'duration' => '5:30'
                ],
                'order' => 0,
                'is_active' => true,
            ]);

            // Add a text block
            LessonContentBlock::create([
                'lesson_id' => $lesson->id,
                'type' => 'text',
                'content' => [
                    'title' => 'Lesson Overview',
                    'content' => '<p>This lesson will cover the fundamental concepts and provide practical examples.</p><p>By the end of this lesson, you will be able to understand and apply these concepts.</p>',
                    'style' => 'paragraph'
                ],
                'order' => 1,
                'is_active' => true,
            ]);

            // Add an image block
            LessonContentBlock::create([
                'lesson_id' => $lesson->id,
                'type' => 'image',
                'content' => [
                    'title' => 'Key Concepts Diagram',
                    'description' => 'This diagram illustrates the main concepts we will cover.',
                    'image_url' => 'https://example.com/diagram1.jpg',
                    'alt_text' => 'Concept diagram showing relationships between ideas'
                ],
                'order' => 2,
                'is_active' => true,
            ]);

            // Add a tips block
            LessonContentBlock::create([
                'lesson_id' => $lesson->id,
                'type' => 'tips',
                'content' => [
                    'title' => 'Pro Tips',
                    'description' => 'Here are some expert tips to help you master this lesson.',
                    'items' => [
                        [
                            'title' => 'Practice Regularly',
                            'content' => 'Consistent practice is key to mastering these concepts.',
                            'image_url' => 'https://example.com/tip1.jpg'
                        ],
                        [
                            'title' => 'Take Notes',
                            'content' => 'Writing down key points helps with retention.',
                            'image_url' => 'https://example.com/tip2.jpg'
                        ]
                    ]
                ],
                'order' => 3,
                'is_active' => true,
            ]);

            // Add an ingredients block
            LessonContentBlock::create([
                'lesson_id' => $lesson->id,
                'type' => 'ingredients',
                'content' => [
                    'title' => 'Required Materials',
                    'items' => [
                        [
                            'title' => 'Basic Tools',
                            'image_url' => 'https://example.com/tools.jpg',
                            'description' => 'Essential tools needed for this lesson'
                        ],
                        [
                            'title' => 'Reference Materials',
                            'image_url' => 'https://example.com/reference.jpg',
                            'description' => 'Books and resources for further study'
                        ]
                    ]
                ],
                'order' => 4,
                'is_active' => true,
            ]);
        }
    }
} 