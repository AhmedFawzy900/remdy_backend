<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Ad;

class AdSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $ads = [
            [
                'title' => 'Premium Herbal Remedies',
                'image' => 'https://example.com/images/premium-herbs-ad.jpg',
                'status' => Ad::STATUS_ACTIVE,
            ],
            [
                'title' => 'Natural Health Solutions',
                'image' => 'https://example.com/images/natural-health-ad.jpg',
                'status' => Ad::STATUS_ACTIVE,
            ],
            [
                'title' => 'Organic Supplements',
                'image' => 'https://example.com/images/organic-supplements-ad.jpg',
                'status' => Ad::STATUS_ACTIVE,
            ],
            [
                'title' => 'Wellness Products',
                'image' => 'https://example.com/images/wellness-products-ad.jpg',
                'status' => Ad::STATUS_INACTIVE,
            ],
            [
                'title' => 'Herbal Medicine Guide',
                'image' => 'https://example.com/images/herbal-guide-ad.jpg',
                'status' => Ad::STATUS_ACTIVE,
            ]
        ];

        foreach ($ads as $ad) {
            Ad::create($ad);
        }
    }
}
