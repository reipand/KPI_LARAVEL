<?php

use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('kpi.role.{role}', function (?User $user, string $role) {
    if (!$user) {
        return false;
    }

    return $user->hasKpiRole($role);
});

Broadcast::channel('kpi.user.{userId}', function (?User $user, int $userId) {
    if (!$user) {
        return false;
    }

    if ((int) $user->id === (int) $userId) {
        return true;
    }

    return collect(['admin', 'hr_manager', 'direktur'])
        ->contains(fn (string $role) => $user->hasKpiRole($role));
});
