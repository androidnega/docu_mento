<?php

namespace App\Http\Controllers\Admin\Concerns;

use App\Models\User;

trait InteractsWithAdminSession
{
    protected function adminUser(): ?User
    {
        $user = auth()->user();
        return $user instanceof User ? $user : null;
    }

    /** Route prefix for redirects: unified dashboard. */
    protected function staffRoutePrefix(): string
    {
        return 'dashboard';
    }
}
