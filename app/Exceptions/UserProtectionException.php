<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when a role or status change on an existing account is refused by
 * the US-704/§13 write-path guards: an admin changing their own role away
 * from admin, deactivating their own account, or a change that would leave
 * the active-admin fleet below two. The refusal is always recorded in the
 * admin audit trail as a failed entry BEFORE this throws, so a blocked
 * change is never silent.
 */
class UserProtectionException extends RuntimeException
{
    public static function selfRoleChange(): self
    {
        return new self('You cannot change your own role away from admin.');
    }

    public static function selfDeactivation(): self
    {
        return new self('You cannot deactivate your own account.');
    }

    public static function lastActiveAdmin(): self
    {
        return new self('This change would leave fewer than two active admins.');
    }
}
