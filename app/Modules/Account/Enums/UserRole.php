<?php

namespace App\Modules\Account\Enums;

/**
 * What a user may do (ADR-012). Vendors get their own tables in Phase 2, not a role here.
 */
enum UserRole: string
{
    case Customer = 'customer';
    case Staff = 'staff';
    case Admin = 'admin';

    public function label(): string
    {
        return match ($this) {
            self::Customer => __('Customer'),
            self::Staff => __('Staff'),
            self::Admin => __('Administrator'),
        };
    }

    public function canAccessAdminPanel(): bool
    {
        return $this !== self::Customer;
    }

    /**
     * Settings, delivery zones, coupons, CMS pages, staff accounts and cost prices.
     */
    public function canManageStore(): bool
    {
        return $this === self::Admin;
    }
}
