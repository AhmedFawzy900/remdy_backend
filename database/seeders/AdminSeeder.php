<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Admin;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admins = [
            [
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'phone' => '+1234567890',
                'password' => Hash::make('password123'),
            ],
            [
                'name' => 'Jane Smith',
                'email' => 'jane@example.com',
                'phone' => '+1987654321',
                'password' => Hash::make('password123'),
            ],
            [
                'name' => 'Mike Johnson',
                'email' => 'mike@example.com',
                'phone' => '+1122334455',
                'password' => Hash::make('password123'),
            ],
            [
                'name' => 'Sarah Wilson',
                'email' => 'sarah@example.com',
                'phone' => '+1555666777',
                'password' => Hash::make('password123'),
            ],
            [
                'name' => 'David Brown',
                'email' => 'david@example.com',
                'phone' => '+1888999000',
                'password' => Hash::make('password123'),
            ],
        ];

        foreach ($admins as $admin) {
            Admin::create($admin);
        }
    }
}
