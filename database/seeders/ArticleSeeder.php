<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Article;

class ArticleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $articles = [
            [
                'title' => 'The Healing Power of Herbs',
                'image' => 'https://example.com/images/healing-herbs.jpg',
                'description' => 'Discover the amazing healing properties of common herbs and how they can improve your health naturally.',
                'plants' => [
                    [
                        'image' => 'https://example.com/images/chamomile.jpg',
                        'title' => 'Chamomile',
                        'description' => 'A gentle herb known for its calming properties and ability to promote sleep.'
                    ],
                    [
                        'image' => 'https://example.com/images/lavender.jpg',
                        'title' => 'Lavender',
                        'description' => 'Famous for its soothing aroma and stress-relieving benefits.'
                    ],
                    [
                        'image' => 'https://example.com/images/peppermint.jpg',
                        'title' => 'Peppermint',
                        'description' => 'Excellent for digestive issues and providing natural energy.'
                    ]
                ],
                'plans' => 'basic',
                'status' => Article::STATUS_ACTIVE,
            ],
            [
                'title' => 'Advanced Herbal Remedies',
                'image' => 'https://example.com/images/advanced-herbs.jpg',
                'description' => 'Advanced techniques and combinations for experienced herbalists.',
                'plants' => [
                    [
                        'image' => 'https://example.com/images/echinacea.jpg',
                        'title' => 'Echinacea',
                        'description' => 'Powerful immune booster and natural antibiotic.'
                    ],
                    [
                        'image' => 'https://example.com/images/ginseng.jpg',
                        'title' => 'Ginseng',
                        'description' => 'Adaptogenic herb that helps the body cope with stress.'
                    ],
                    [
                        'image' => 'https://example.com/images/turmeric.jpg',
                        'title' => 'Turmeric',
                        'description' => 'Anti-inflammatory powerhouse with numerous health benefits.'
                    ]
                ],
                'plans' => 'premium',
                'status' => Article::STATUS_ACTIVE,
            ],
            [
                'title' => 'Professional Herbal Medicine',
                'image' => 'https://example.com/images/professional-herbs.jpg',
                'description' => 'Professional-grade herbal medicine techniques and advanced formulations.',
                'plants' => [
                    [
                        'image' => 'https://example.com/images/ashwagandha.jpg',
                        'title' => 'Ashwagandha',
                        'description' => 'Ancient adaptogenic herb for stress management and vitality.'
                    ],
                    [
                        'image' => 'https://example.com/images/reishi.jpg',
                        'title' => 'Reishi Mushroom',
                        'description' => 'Medicinal mushroom with powerful immune and longevity benefits.'
                    ],
                    [
                        'image' => 'https://example.com/images/cordyceps.jpg',
                        'title' => 'Cordyceps',
                        'description' => 'Energy-boosting mushroom that enhances physical performance.'
                    ]
                ],
                'plans' => 'pro',
                'status' => Article::STATUS_ACTIVE,
            ],
            [
                'title' => 'Essential Oils for Beginners',
                'image' => 'https://example.com/images/essential-oils.jpg',
                'description' => 'A comprehensive guide to using essential oils safely and effectively.',
                'plants' => [
                    [
                        'image' => 'https://example.com/images/tea-tree.jpg',
                        'title' => 'Tea Tree Oil',
                        'description' => 'Natural antiseptic and antifungal oil for skin care.'
                    ],
                    [
                        'image' => 'https://example.com/images/lemon-oil.jpg',
                        'title' => 'Lemon Oil',
                        'description' => 'Purifying and uplifting oil with cleansing properties.'
                    ],
                    [
                        'image' => 'https://example.com/images/rosemary-oil.jpg',
                        'title' => 'Rosemary Oil',
                        'description' => 'Memory-enhancing oil that improves focus and concentration.'
                    ]
                ],
                'plans' => 'basic',
                'status' => Article::STATUS_INACTIVE,
            ]
        ];

        foreach ($articles as $article) {
            Article::create($article);
        }
    }
}
