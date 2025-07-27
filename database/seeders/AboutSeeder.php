<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AboutSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('abouts')->insert([
            [
                'main_description' => '<p>At Natural Remedies, we believe in the power of nature to heal and nurture. Our mission is to provide you with the highest quality natural remedies, backed by centuries of traditional wisdom and modern scientific research. We are committed to helping you achieve optimal health through safe, effective, and sustainable natural solutions.</p>',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
} 