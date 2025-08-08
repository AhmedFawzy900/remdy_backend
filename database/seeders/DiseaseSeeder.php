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
                'status' => 'active',
                'symptoms' => json_encode(['Shortness of breath', 'Chest tightness', 'Wheezing', 'Coughing']),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Common Cold',
                'image' => 'https://example.com/images/common-cold.jpg',
                'description' => 'A viral infection of the upper respiratory tract.',
                'status' => 'active',
                'symptoms' => json_encode(['Runny nose', 'Sore throat', 'Cough', 'Congestion']),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Joint Pain',
                'image' => 'https://example.com/images/joint-pain.jpg',
                'description' => 'Pain in the joints that can be caused by various conditions.',
                'status' => 'active',
                'symptoms' => json_encode(['Stiffness', 'Swelling', 'Reduced range of motion', 'Pain with movement']),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Anxiety',
                'image' => 'https://example.com/images/anxiety.jpg',
                'description' => 'A mental health disorder characterized by feelings of worry and fear.',
                'status' => 'active',
                'symptoms' => json_encode(['Excessive worrying', 'Restlessness', 'Difficulty concentrating', 'Sleep problems']),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
} 