<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Course;

class CourseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $courses = [
            [
                'image' => 'https://example.com/course-image.jpg',
                'title' => 'Advanced Herbal Remedies Course',
                'description' => 'A comprehensive course on advanced herbal remedies for common ailments.',
                'duration' => '8 weeks',
                'sessionsNumber' => 4,
                'price' => 199.99,
                'plan' => 'Master',
                'overview' => 'This course will teach you advanced herbal preparation techniques...',
                'courseContent' => [
                    [
                        'title' => 'Introduction to Herbal Medicine',
                        'image' => 'https://example.com/herbal-intro.jpg'
                    ],
                    [
                        'title' => 'Advanced Preparation Methods',
                        'image' => 'https://example.com/prep-methods.jpg'
                    ]
                ],
                'instructors' => [
                    [
                        'name' => 'Dr. Jane Smith',
                        'description' => 'Herbalist with 20 years of experience',
                        'image' => 'https://example.com/jane-smith.jpg'
                    ]
                ],
                'selectedRemedies' => [1, 2],
                'relatedCourses' => [],
                'status' => 'active',
                'sessions' => [
                    [
                        'day' => 1,
                        'title' => 'Introduction to Course',
                        'description' => 'Overview of what will be covered in this course',
                        'videoUrl' => 'https://youtube.com/intro-video',
                        'videoDescription' => 'Watch this introduction before proceeding',
                        'lessonContent' => [
                            [
                                'title' => 'Course Objectives',
                                'image' => 'https://example.com/objectives.jpg'
                            ]
                        ],
                        'remedies' => [],
                        'tip' => 'Take notes throughout the course',
                        'isCompleted' => false
                    ],
                    [
                        'day' => 2,
                        'title' => 'Herbal Basics',
                        'description' => 'Fundamentals of herbal medicine',
                        'videoUrl' => '',
                        'videoDescription' => '',
                        'lessonContent' => [
                            [
                                'title' => 'Common Herbs',
                                'image' => 'https://example.com/common-herbs.jpg'
                            ],
                            [
                                'title' => 'Preparation Techniques',
                                'image' => 'https://example.com/prep-tech.jpg'
                            ]
                        ],
                        'remedies' => [],
                        'tip' => 'Start with small batches when trying new recipes',
                        'isCompleted' => false
                    ]
                ]
            ],
            [
                'image' => 'https://example.com/course2.jpg',
                'title' => 'Beginner Herbalism',
                'description' => 'Start your journey into herbal medicine with this beginner-friendly course.',
                'duration' => '4 weeks',
                'sessionsNumber' => 2,
                'price' => 49.99,
                'plan' => 'Basic',
                'overview' => 'Learn the basics of herbalism, including plant identification and simple remedies.',
                'courseContent' => [
                    [
                        'title' => 'Getting Started',
                        'image' => 'https://example.com/getting-started.jpg'
                    ]
                ],
                'instructors' => [
                    [
                        'name' => 'Dr. John Doe',
                        'description' => 'Expert in natural medicine',
                        'image' => 'https://example.com/john-doe.jpg'
                    ]
                ],
                'selectedRemedies' => [3],
                'relatedCourses' => ['Advanced Herbal Remedies Course'],
                'status' => 'active',
                'sessions' => [
                    [
                        'day' => 1,
                        'title' => 'Welcome',
                        'description' => 'Introduction to the course',
                        'videoUrl' => '',
                        'videoDescription' => '',
                        'lessonContent' => [
                            [
                                'title' => 'Meet Your Instructor',
                                'image' => 'https://example.com/instructor.jpg'
                            ]
                        ],
                        'remedies' => [],
                        'tip' => 'Ask questions in the forum',
                        'isCompleted' => false
                    ]
                ]
            ]
        ];

        foreach ($courses as $course) {
            Course::create($course);
        }
    }
}
