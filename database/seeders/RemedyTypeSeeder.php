<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\RemedyType;

class RemedyTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $remedyTypes = [
            [
                'name' => 'Herbal Remedies',
                'description' => 'Natural remedies made from plants, herbs, and botanical extracts.',
                'image' => 'https://example.com/images/herbal-remedies.jpg',
                'status' => RemedyType::STATUS_ACTIVE,
            ],
            [
                'name' => 'Homeopathic Remedies',
                'description' => 'Alternative medicine based on the principle of treating like with like.',
                'image' => 'https://example.com/images/homeopathic-remedies.jpg',
                'status' => RemedyType::STATUS_ACTIVE,
            ],
            [
                'name' => 'Ayurvedic Remedies',
                'description' => 'Traditional Indian medicine system using natural herbs and minerals.',
                'image' => 'https://example.com/images/ayurvedic-remedies.jpg',
                'status' => RemedyType::STATUS_ACTIVE,
            ],
            [
                'name' => 'Traditional Chinese Medicine',
                'description' => 'Ancient Chinese healing system using herbs, acupuncture, and other methods.',
                'image' => 'https://example.com/images/tcm-remedies.jpg',
                'status' => RemedyType::STATUS_ACTIVE,
            ],
            [
                'name' => 'Essential Oils',
                'description' => 'Concentrated plant extracts used for aromatherapy and topical application.',
                'image' => 'https://example.com/images/essential-oils.jpg',
                'status' => RemedyType::STATUS_ACTIVE,
            ],
            [
                'name' => 'Supplements',
                'description' => 'Vitamins, minerals, and other nutritional supplements.',
                'image' => 'https://example.com/images/supplements.jpg',
                'status' => RemedyType::STATUS_INACTIVE,
            ],
            [
                'name' => 'Tea Remedies',
                'description' => 'Medicinal teas made from various herbs and plants.',
                'image' => 'https://example.com/images/tea-remedies.jpg',
                'status' => RemedyType::STATUS_ACTIVE,
            ],
            [
                'name' => 'Topical Remedies',
                'description' => 'Ointments, creams, and balms applied directly to the skin.',
                'image' => 'https://example.com/images/topical-remedies.jpg',
                'status' => RemedyType::STATUS_INACTIVE,
            ],
        ];

        foreach ($remedyTypes as $remedyType) {
            RemedyType::create($remedyType);
        }
    }
}
