<?php

namespace App\Http\Controllers\Concerns;

use App\Models\User;

trait ChecksPlanAccess
{
    private function normalizeRequiredPlan(?string $requiredPlan): ?string
    {
        if (!$requiredPlan) {
            return null;
        }
        $plan = strtolower(trim($requiredPlan));
        if ($plan === 'all') {
            return null; // accessible to all
        }
        if (in_array($plan, ['rookie', 'skilled', 'master'], true)) {
            return $plan;
        }
        return null;
    }

    private function planRank(string $plan): int
    {
        return match ($plan) {
            'rookie' => 1,
            'skilled' => 2,
            'master' => 3,
            default => 1,
        };
    }

    protected function userEffectivePlan(?User $user): string
    {
        if (!$user) {
            return User::PLAN_ROOKIE;
        }
        // Normalize to lowercase for comparison safety
        $rawPlan = $user->subscription_plan ?? User::PLAN_ROOKIE;
        $plan = strtolower(trim((string) $rawPlan));

        // Fallback to rookie if unexpected value
        if (!in_array($plan, [User::PLAN_ROOKIE, User::PLAN_SKILLED, User::PLAN_MASTER], true)) {
            $plan = User::PLAN_ROOKIE;
        }

        // If higher-tier plan but subscription inactive (when method exists), downgrade to rookie
        if (in_array($plan, [User::PLAN_SKILLED, User::PLAN_MASTER], true)) {
            if (method_exists($user, 'hasActiveSubscription') && !$user->hasActiveSubscription()) {
                return User::PLAN_ROOKIE;
            }
        }

        return $plan;
    }

    protected function canAccessByPlan(?User $user, ?string $requiredPlan): bool
    {
        $normalized = $this->normalizeRequiredPlan($requiredPlan);
        if ($normalized === null) {
            return true; // public or not set
        }
        $userPlan = $this->userEffectivePlan($user);
        return $this->planRank($userPlan) >= $this->planRank($normalized);
    }
}


