<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\SubscriptionHistory;
use App\Models\User;

class SubscriptionHistorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::take(10)->get();

        foreach ($users as $user) {
            // Create some historical subscriptions for each user
            $subscriptions = [
                [
                    'plan' => 'rookie',
                    'interval' => null,
                    'started_at' => now()->subMonths(6),
                    'ends_at' => null,
                    'status' => 'cancelled',
                    'action' => 'activated',
                    'amount_paid' => 0,
                ],
                [
                    'plan' => 'skilled',
                    'interval' => 'monthly',
                    'started_at' => now()->subMonths(5),
                    'ends_at' => now()->subMonths(4),
                    'status' => 'expired',
                    'action' => 'activated',
                    'amount_paid' => 19.99,
                    'payment_method' => 'stripe_card',
                ],
                [
                    'plan' => 'master',
                    'interval' => 'yearly',
                    'started_at' => now()->subMonths(3),
                    'ends_at' => now()->addMonths(9),
                    'status' => 'active',
                    'action' => 'activated',
                    'amount_paid' => 199.99,
                    'payment_method' => 'stripe_card',
                    'reference' => 'pi_' . uniqid(),
                ],
            ];

            foreach ($subscriptions as $index => $subscription) {
                SubscriptionHistory::create([
                    'user_id' => $user->id,
                    'plan' => $subscription['plan'],
                    'interval' => $subscription['interval'],
                    'started_at' => $subscription['started_at'],
                    'ends_at' => $subscription['ends_at'],
                    'reference' => $subscription['reference'] ?? 'ref_' . uniqid(),
                    'status' => $subscription['status'],
                    'action' => $subscription['action'],
                    'amount_paid' => $subscription['amount_paid'],
                    'payment_method' => $subscription['payment_method'] ?? null,
                    'notes' => $index === 0 ? 'Initial signup' : null,
                ]);
            }
        }
    }
}