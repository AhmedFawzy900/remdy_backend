<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Remedy;

class RemedySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $remedies = [
            [
                'title' => 'Ginger Tea for Cold Relief',
                'main_image_url' => 'https://example.com/images/ginger-tea.jpg',
                'disease' => 'Common Cold',
                'disease_id' => null, // Will be set to null for now since Common Cold is not in diseases table
                'remedy_type_id' => 1, // Herbal Remedies
                'body_system_id' => 4, // Respiratory System
                'description' => 'A natural remedy using ginger to help relieve cold symptoms and boost immunity.',
                'visible_to_plan' => 'all',
                'status' => Remedy::STATUS_ACTIVE,
                'ingredients' => [
                    [
                        'image_url' => 'https://example.com/images/ginger.jpg',
                        'name' => 'Fresh Ginger Root'
                    ],
                    [
                        'image_url' => 'https://example.com/images/honey.jpg',
                        'name' => 'Raw Honey'
                    ],
                    [
                        'image_url' => 'https://example.com/images/lemon.jpg',
                        'name' => 'Lemon'
                    ]
                ],
                'instructions' => [
                    [
                        'image_url' => 'https://example.com/images/step1.jpg',
                        'name' => 'Peel and slice fresh ginger root'
                    ],
                    [
                        'image_url' => 'https://example.com/images/step2.jpg',
                        'name' => 'Boil water and add ginger slices'
                    ],
                    [
                        'image_url' => 'https://example.com/images/step3.jpg',
                        'name' => 'Add honey and lemon juice'
                    ]
                ],
                'benefits' => [
                    [
                        'image_url' => 'https://example.com/images/benefit1.jpg',
                        'name' => 'Relieves sore throat'
                    ],
                    [
                        'image_url' => 'https://example.com/images/benefit2.jpg',
                        'name' => 'Reduces inflammation'
                    ],
                    [
                        'image_url' => 'https://example.com/images/benefit3.jpg',
                        'name' => 'Boosts immune system'
                    ]
                ],
                'precautions' => [
                    [
                        'image_url' => 'https://example.com/images/precaution1.jpg',
                        'name' => 'Avoid if allergic to ginger'
                    ],
                    [
                        'image_url' => 'https://example.com/images/precaution2.jpg',
                        'name' => 'Not recommended for children under 2'
                    ]
                ],
                'product_link' => 'https://amazon.com/ginger-tea-product'
            ],
            [
                'title' => 'Turmeric Milk for Joint Pain',
                'main_image_url' => 'https://example.com/images/turmeric-milk.jpg',
                'disease' => 'Joint Pain',
                'disease_id' => null, // Will be set to null for now since Joint Pain is not in diseases table
                'remedy_type_id' => 3, // Ayurvedic Remedies
                'body_system_id' => 5, // Musculoskeletal System
                'description' => 'Traditional Ayurvedic remedy using turmeric and milk to reduce joint inflammation and pain.',
                'visible_to_plan' => 'skilled',
                'status' => Remedy::STATUS_ACTIVE,
                'ingredients' => [
                    [
                        'image_url' => 'https://example.com/images/turmeric.jpg',
                        'name' => 'Turmeric Powder'
                    ],
                    [
                        'image_url' => 'https://example.com/images/milk.jpg',
                        'name' => 'Warm Milk'
                    ],
                    [
                        'image_url' => 'https://example.com/images/black-pepper.jpg',
                        'name' => 'Black Pepper'
                    ]
                ],
                'instructions' => [
                    [
                        'image_url' => 'https://example.com/images/step1.jpg',
                        'name' => 'Heat milk until warm'
                    ],
                    [
                        'image_url' => 'https://example.com/images/step2.jpg',
                        'name' => 'Add turmeric powder and black pepper'
                    ],
                    [
                        'image_url' => 'https://example.com/images/step3.jpg',
                        'name' => 'Stir well and drink before bedtime'
                    ]
                ],
                'benefits' => [
                    [
                        'image_url' => 'https://example.com/images/benefit1.jpg',
                        'name' => 'Reduces joint inflammation'
                    ],
                    [
                        'image_url' => 'https://example.com/images/benefit2.jpg',
                        'name' => 'Improves mobility'
                    ],
                    [
                        'image_url' => 'https://example.com/images/benefit3.jpg',
                        'name' => 'Natural pain relief'
                    ]
                ],
                'precautions' => [
                    [
                        'image_url' => 'https://example.com/images/precaution1.jpg',
                        'name' => 'Consult doctor if on blood thinners'
                    ],
                    [
                        'image_url' => 'https://example.com/images/precaution2.jpg',
                        'name' => 'May cause stomach upset in some people'
                    ]
                ],
                'product_link' => 'https://amazon.com/turmeric-milk-product'
            ],
            [
                'title' => 'Lavender Oil for Anxiety',
                'main_image_url' => 'https://example.com/images/lavender-oil.jpg',
                'disease' => 'Anxiety',
                'disease_id' => null, // Will be set to null for now since Anxiety is not in diseases table
                'remedy_type_id' => 5, // Essential Oils
                'body_system_id' => 2, // Nervous System
                'description' => 'Aromatherapy using lavender essential oil to promote relaxation and reduce anxiety.',
                'visible_to_plan' => 'master',
                'status' => Remedy::STATUS_ACTIVE,
                'ingredients' => [
                    [
                        'image_url' => 'https://example.com/images/lavender-oil.jpg',
                        'name' => 'Lavender Essential Oil'
                    ],
                    [
                        'image_url' => 'https://example.com/images/carrier-oil.jpg',
                        'name' => 'Carrier Oil (Coconut or Almond)'
                    ]
                ],
                'instructions' => [
                    [
                        'image_url' => 'https://example.com/images/step1.jpg',
                        'name' => 'Dilute lavender oil with carrier oil'
                    ],
                    [
                        'image_url' => 'https://example.com/images/step2.jpg',
                        'name' => 'Apply to temples and wrists'
                    ],
                    [
                        'image_url' => 'https://example.com/images/step3.jpg',
                        'name' => 'Use in diffuser for aromatherapy'
                    ]
                ],
                'benefits' => [
                    [
                        'image_url' => 'https://example.com/images/benefit1.jpg',
                        'name' => 'Reduces anxiety and stress'
                    ],
                    [
                        'image_url' => 'https://example.com/images/benefit2.jpg',
                        'name' => 'Improves sleep quality'
                    ],
                    [
                        'image_url' => 'https://example.com/images/benefit3.jpg',
                        'name' => 'Promotes relaxation'
                    ]
                ],
                'precautions' => [
                    [
                        'image_url' => 'https://example.com/images/precaution1.jpg',
                        'name' => 'Do not ingest essential oils'
                    ],
                    [
                        'image_url' => 'https://example.com/images/precaution2.jpg',
                        'name' => 'Test on small skin area first'
                    ]
                ],
                'product_link' => 'https://amazon.com/lavender-oil-product'
            ]
        ];

        foreach ($remedies as $remedy) {
            Remedy::create($remedy);
        }
    }
}
