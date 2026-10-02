<?php

namespace App\Support;

final class RoleHome
{
    public static function routeName(string $role): string
    {
        return match ($role) {
            'admin' => 'admin.dashboard',
            'teacher', 'instructor' => 'students',
            'operator' => 'notifications',
            default => 'dashboard',
        };
    }
}
