<?php

namespace App\Services;

use App\Models\User;

/**
 * AI token usage for staff (supervisors/coordinators).
 * Uses users.ai_tokens_allocation and users.ai_tokens_used.
 */
class AiTokenService
{
    public function getStatus(User $user): array
    {
        $allocation = (int) ($user->ai_tokens_allocation ?? 0);
        $used = (int) ($user->ai_tokens_used ?? 0);
        $remaining = max(0, $allocation - $used);

        return [
            'allocation' => $allocation,
            'used' => $used,
            'remaining' => $remaining,
        ];
    }

    public function getRemaining(User $user): int
    {
        $status = $this->getStatus($user);

        return $status['remaining'];
    }
}
