<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Lesson;
use App\Models\Course;

class LessonSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get the first course to associate lessons with
        $course = Course::first();
        
        if (!$course) {
            $this->command->info('No courses found. Please run CourseSeeder first.');
            return;
        }

        $lessons = [
            [
                'course_id' => $course->id,
                'title' => 'Day 1 - Lesson 1',
                'description' => 'Start your gut detox journey with mindful activities and wellness practices.',
                'image' => 'https://example.com/lesson1-image.jpg',
                'whats_included' => [
                    [
                        'image' => 'https://example.com/activity-icon.png',
                        'title' => 'Daily Activity & Exercise'
                    ],
                    [
                        'image' => 'https://example.com/recipe-icon.png',
                        'title' => 'Plant Based Recipes'
                    ],
                    [
                        'image' => 'https://example.com/video-icon.png',
                        'title' => 'Video Tutorial'
                    ],
                    [
                        'image' => 'https://example.com/tips-icon.png',
                        'title' => 'Wellness Tips'
                    ]
                ],
                'activities' => [
                    'title' => 'Day 1 - Activity & Exercise',
                    'items' => [
                        ['title' => 'Take a mindful walk in nature—breathe deeply, listen to the breeze, and reconnect with the earth.'],
                        ['title' => 'Soak in the morning sun for 10 minutes to boost your mood and vitamin D levels naturally.']
                    ]
                ],
                'video' => [
                    'title' => 'Unwind: Simple Steps to De-Stress',
                    'description' => 'Watch to learn how to do your morning exercise and yoga.',
                    'link' => 'https://example.com/video1.mp4'
                ],
                'instructions' => [
                    [
                        'title' => 'Mix all ingredients',
                        'image' => 'https://example.com/mix-instruction.jpg'
                    ],
                    [
                        'title' => 'Drink first thing in the morning',
                        'image' => 'https://example.com/drink-instruction.jpg'
                    ]
                ],
                'ingredients' => [
                    [
                        'title' => '1 cup warm water',
                        'image' => 'https://example.com/water-ingredient.jpg'
                    ],
                    [
                        'title' => '1 tbsp grated ginger',
                        'image' => 'https://example.com/ginger-ingredient.jpg'
                    ],
                    [
                        'title' => '1/2 lemon (juiced)',
                        'image' => 'https://example.com/lemon-ingredient.jpg'
                    ],
                    [
                        'title' => '1 tsp honey (optional)',
                        'image' => 'https://example.com/honey-ingredient.jpg'
                    ]
                ],
                'tips' => [
                    'title' => 'Wellness Tips',
                    'description' => 'Short, helpful suggestions to support your healing journey.',
                    'content' => [
                        [
                            'title' => 'Stay Hydrated',
                            'image' => 'https://example.com/hydration-tip.jpg'
                        ]
                    ]
                ],
                'status' => 'active',
                'order' => 1
            ],
            [
                'course_id' => $course->id,
                'title' => 'Day 2 - Lesson 1',
                'description' => 'Continue your detox journey with advanced breathing techniques and mindful eating practices.',
                'image' => 'https://example.com/lesson2-image.jpg',
                'whats_included' => [
                    [
                        'image' => 'https://example.com/meditation-icon.png',
                        'title' => 'Meditation Practice'
                    ],
                    [
                        'image' => 'https://example.com/nutrition-icon.png',
                        'title' => 'Nutrition Guide'
                    ],
                    [
                        'image' => 'https://example.com/exercise-icon.png',
                        'title' => 'Gentle Exercises'
                    ],
                    [
                        'image' => 'https://example.com/journal-icon.png',
                        'title' => 'Journaling'
                    ]
                ],
                'activities' => [
                    'title' => 'Day 2 - Mindful Practices',
                    'items' => [
                        ['title' => 'Practice 10 minutes of mindful meditation focusing on your breath.'],
                        ['title' => 'Write down three things you\'re grateful for today.']
                    ]
                ],
                'video' => [
                    'title' => 'Mindful Eating: A Complete Guide',
                    'description' => 'Learn the art of mindful eating and how it supports your gut health.',
                    'link' => 'https://example.com/video2.mp4'
                ],
                'instructions' => [
                    [
                        'title' => 'Blend all fruits and vegetables',
                        'image' => 'https://example.com/blend-instruction.jpg'
                    ],
                    [
                        'title' => 'Top with granola and berries',
                        'image' => 'https://example.com/top-instruction.jpg'
                    ],
                    [
                        'title' => 'Serve immediately for best taste',
                        'image' => 'https://example.com/serve-instruction.jpg'
                    ]
                ],
                'ingredients' => [
                    [
                        'title' => '1 cup frozen berries',
                        'image' => 'https://example.com/berries-ingredient.jpg'
                    ],
                    [
                        'title' => '1 banana',
                        'image' => 'https://example.com/banana-ingredient.jpg'
                    ],
                    [
                        'title' => '1 cup almond milk',
                        'image' => 'https://example.com/almond-milk-ingredient.jpg'
                    ],
                    [
                        'title' => '1 tbsp chia seeds',
                        'image' => 'https://example.com/chia-ingredient.jpg'
                    ]
                ],
                'tips' => [
                    'title' => 'Daily Wellness Tips',
                    'description' => 'Simple practices to enhance your detox journey.',
                    'content' => [
                        [
                            'title' => 'Mindful Eating',
                            'image' => 'https://example.com/mindful-eating.jpg'
                        ],
                        [
                            'title' => 'Stress Management',
                            'image' => 'https://example.com/stress-management.jpg'
                        ]
                    ]
                ],
                'status' => 'active',
                'order' => 2
            ],
            [
                'course_id' => $course->id,
                'title' => 'Day 3 - Lesson 1',
                'description' => 'Deepen your practice with advanced breathing techniques and gut-friendly recipes.',
                'image' => 'https://example.com/lesson3-image.jpg',
                'whats_included' => [
                    [
                        'image' => 'https://example.com/breathing-icon.png',
                        'title' => 'Breathing Techniques'
                    ],
                    [
                        'image' => 'https://example.com/herbs-icon.png',
                        'title' => 'Herbal Remedies'
                    ],
                    [
                        'image' => 'https://example.com/yoga-icon.png',
                        'title' => 'Yoga Flow'
                    ],
                    [
                        'image' => 'https://example.com/reflection-icon.png',
                        'title' => 'Daily Reflection'
                    ]
                ],
                'activities' => [
                    'title' => 'Day 3 - Advanced Practices',
                    'items' => [
                        ['title' => 'Practice the 4-7-8 breathing technique for 5 minutes.'],
                        ['title' => 'Complete a gentle 15-minute yoga flow focusing on twists and forward folds.']
                    ]
                ],
                'video' => [
                    'title' => 'Advanced Breathing for Gut Health',
                    'description' => 'Master breathing techniques that support your digestive system.',
                    'link' => 'https://example.com/video3.mp4'
                ],
                'instructions' => [
                    [
                        'title' => 'Simmer bones for 12-24 hours',
                        'image' => 'https://example.com/simmer-instruction.jpg'
                    ],
                    [
                        'title' => 'Add vegetables in the last hour',
                        'image' => 'https://example.com/add-vegetables-instruction.jpg'
                    ],
                    [
                        'title' => 'Strain and store in refrigerator',
                        'image' => 'https://example.com/strain-instruction.jpg'
                    ]
                ],
                'ingredients' => [
                    [
                        'title' => '2 lbs beef bones',
                        'image' => 'https://example.com/beef-bones-ingredient.jpg'
                    ],
                    [
                        'title' => '1 onion, chopped',
                        'image' => 'https://example.com/onion-ingredient.jpg'
                    ],
                    [
                        'title' => '2 carrots, chopped',
                        'image' => 'https://example.com/carrots-ingredient.jpg'
                    ],
                    [
                        'title' => '2 celery stalks, chopped',
                        'image' => 'https://example.com/celery-ingredient.jpg'
                    ],
                    [
                        'title' => '2 tbsp apple cider vinegar',
                        'image' => 'https://example.com/vinegar-ingredient.jpg'
                    ]
                ],
                'tips' => [
                    'title' => 'Advanced Wellness Tips',
                    'description' => 'Expert tips to accelerate your healing journey.',
                    'content' => [
                        [
                            'title' => 'Breathing for Digestion',
                            'image' => 'https://example.com/breathing-digestion.jpg'
                        ],
                        [
                            'title' => 'Herbal Support',
                            'image' => 'https://example.com/herbal-support.jpg'
                        ]
                    ]
                ],
                'status' => 'active',
                'order' => 3
            ]
        ];

        foreach ($lessons as $lessonData) {
            Lesson::create($lessonData);
        }

        $this->command->info('Lessons seeded successfully!');
    }
}
