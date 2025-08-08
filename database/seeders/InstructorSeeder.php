<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Instructor;

class InstructorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $instructors = [
            [
                'name' => 'Dr. Sarah Johnson',
                'description' => 'Expert in natural remedies and herbal medicine with over 15 years of experience.',
                'image' => 'https://example.com/images/instructors/sarah-johnson.jpg',
                'specialization' => 'Herbal Medicine & Natural Remedies',
                'experience_years' => 15,
                'bio' => 'Dr. Sarah Johnson is a renowned herbalist and natural medicine practitioner. She has dedicated her career to researching and teaching traditional healing methods.',
                'status' => 'active',
            ],
            [
                'name' => 'Prof. Michael Chen',
                'description' => 'Specialist in traditional Chinese medicine and acupuncture techniques.',
                'image' => 'https://example.com/images/instructors/michael-chen.jpg',
                'specialization' => 'Traditional Chinese Medicine',
                'experience_years' => 20,
                'bio' => 'Professor Michael Chen is a certified acupuncturist and TCM practitioner with extensive knowledge in ancient Chinese healing practices.',
                'status' => 'active',
            ],
            [
                'name' => 'Dr. Emily Rodriguez',
                'description' => 'Expert in Ayurvedic medicine and holistic wellness practices.',
                'image' => 'https://example.com/images/instructors/emily-rodriguez.jpg',
                'specialization' => 'Ayurvedic Medicine',
                'experience_years' => 12,
                'bio' => 'Dr. Emily Rodriguez specializes in Ayurvedic medicine and has helped thousands of patients achieve optimal health through natural methods.',
                'status' => 'active',
            ],
            [
                'name' => 'Dr. James Wilson',
                'description' => 'Specialist in homeopathic remedies and natural healing therapies.',
                'image' => 'https://example.com/images/instructors/james-wilson.jpg',
                'specialization' => 'Homeopathy',
                'experience_years' => 18,
                'bio' => 'Dr. James Wilson is a certified homeopath with deep understanding of natural healing principles and personalized treatment approaches.',
                'status' => 'active',
            ],
            [
                'name' => 'Dr. Lisa Thompson',
                'description' => 'Expert in nutrition-based healing and dietary remedies.',
                'image' => 'https://example.com/images/instructors/lisa-thompson.jpg',
                'specialization' => 'Nutritional Healing',
                'experience_years' => 14,
                'bio' => 'Dr. Lisa Thompson focuses on using food as medicine and has developed numerous dietary protocols for various health conditions.',
                'status' => 'active',
            ],
        ];

        foreach ($instructors as $instructor) {
            Instructor::create($instructor);
        }
    }
}
