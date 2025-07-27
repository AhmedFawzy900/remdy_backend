<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\BodySystem;

class BodySystemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $bodySystems = [
            [
                'title' => 'Cardiovascular System',
                'description' => 'The cardiovascular system includes the heart and blood vessels, responsible for circulating blood throughout the body.',
                'status' => BodySystem::STATUS_ACTIVE,
            ],
            [
                'title' => 'Nervous System',
                'description' => 'The nervous system controls and coordinates all body functions through the brain, spinal cord, and nerves.',
                'status' => BodySystem::STATUS_ACTIVE,
            ],
            [
                'title' => 'Digestive System',
                'description' => 'The digestive system processes food and absorbs nutrients through organs like the stomach and intestines.',
                'status' => BodySystem::STATUS_ACTIVE,
            ],
            [
                'title' => 'Respiratory System',
                'description' => 'The respiratory system facilitates breathing and gas exchange through the lungs and airways.',
                'status' => BodySystem::STATUS_ACTIVE,
            ],
            [
                'title' => 'Musculoskeletal System',
                'description' => 'The musculoskeletal system provides structure, support, and movement through bones, muscles, and joints.',
                'status' => BodySystem::STATUS_ACTIVE,
            ],
            [
                'title' => 'Endocrine System',
                'description' => 'The endocrine system regulates body functions through hormones produced by glands.',
                'status' => BodySystem::STATUS_INACTIVE,
            ],
            [
                'title' => 'Immune System',
                'description' => 'The immune system protects the body from infections and diseases.',
                'status' => BodySystem::STATUS_ACTIVE,
            ],
            [
                'title' => 'Lymphatic System',
                'description' => 'The lymphatic system helps maintain fluid balance and supports the immune system.',
                'status' => BodySystem::STATUS_INACTIVE,
            ],
        ];

        foreach ($bodySystems as $bodySystem) {
            BodySystem::create($bodySystem);
        }
    }
}
