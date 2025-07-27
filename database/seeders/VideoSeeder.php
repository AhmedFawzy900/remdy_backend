<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Video;

class VideoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $videos = [
            [
                'image' => 'https://example.com/video-thumbnail.jpg',
                'videoLink' => 'https://youtube.com/example-video',
                'title' => 'How to Make Herbal Tea for Cold Relief',
                'description' => 'Learn how to prepare effective herbal tea to combat cold symptoms.',
                'visiblePlans' => 'all',
                'status' => 'active',
                'ingredients' => [
                    [
                        'id' => 1,
                        'name' => 'Fresh Ginger',
                        'image' => 'https://example.com/ginger.jpg'
                    ],
                    [
                        'id' => 2,
                        'name' => 'Organic Honey',
                        'image' => 'https://example.com/honey.jpg'
                    ]
                ],
                'instructions' => [
                    [
                        'id' => 1,
                        'title' => 'Peel and slice the ginger',
                        'image' => 'https://example.com/peel-ginger.jpg'
                    ],
                    [
                        'id' => 2,
                        'title' => 'Boil water with ginger slices',
                        'image' => 'https://example.com/boil-water.jpg'
                    ]
                ],
                'benefits' => [
                    [
                        'id' => 1,
                        'title' => 'Relieves sore throat',
                        'image' => 'https://example.com/sore-throat.jpg'
                    ],
                    [
                        'id' => 2,
                        'title' => 'Boosts immunity',
                        'image' => 'https://example.com/immunity.jpg'
                    ]
                ]
            ],
            [
                'image' => 'https://example.com/video2.jpg',
                'videoLink' => 'https://youtube.com/another-video',
                'title' => 'Herbal Steam Inhalation for Sinus Relief',
                'description' => 'A step-by-step guide to herbal steam inhalation for sinus congestion.',
                'visiblePlans' => 'premium',
                'status' => 'active',
                'ingredients' => [
                    [
                        'id' => 1,
                        'name' => 'Eucalyptus Leaves',
                        'image' => 'https://example.com/eucalyptus.jpg'
                    ],
                    [
                        'id' => 2,
                        'name' => 'Peppermint Oil',
                        'image' => 'https://example.com/peppermint-oil.jpg'
                    ]
                ],
                'instructions' => [
                    [
                        'id' => 1,
                        'title' => 'Boil water and add eucalyptus leaves',
                        'image' => 'https://example.com/boil-eucalyptus.jpg'
                    ],
                    [
                        'id' => 2,
                        'title' => 'Add a few drops of peppermint oil',
                        'image' => 'https://example.com/add-oil.jpg'
                    ]
                ],
                'benefits' => [
                    [
                        'id' => 1,
                        'title' => 'Clears nasal passages',
                        'image' => 'https://example.com/nasal.jpg'
                    ],
                    [
                        'id' => 2,
                        'title' => 'Reduces sinus pressure',
                        'image' => 'https://example.com/sinus.jpg'
                    ]
                ]
            ]
        ];

        foreach ($videos as $video) {
            Video::create($video);
        }
    }
}
