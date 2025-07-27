<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DiseaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('diseases')->insert([
            [
                'name' => 'Diabetes',
                'image' => 'https://example.com/images/diabetes.jpg',
                'description' => 'A chronic condition that affects the way the body processes blood sugar.',
                'status' => 'active',
                'symptoms' => json_encode(['Increased thirst', 'Frequent urination', 'Extreme hunger', 'Unexplained weight loss']),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Hypertension',
                'image' => 'https://example.com/images/hypertension.jpg',
                'description' => 'A condition in which the force of the blood against the artery walls is too high.',
                'status' => 'active',
                'symptoms' => json_encode(['Headaches', 'Shortness of breath', 'Nosebleeds', 'Flushing']),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Asthma',
                'image' => 'https://example.com/images/asthma.jpg',
                'description' => 'A condition in which your airways narrow and swell and may produce extra mucus.',
                'status' => 'inactive',
                'symptoms' => json_encode(['Shortness of breath', 'Chest tightness', 'Wheezing', 'Coughing']),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
} 