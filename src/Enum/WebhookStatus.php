<?php

declare(strict_types=1);

namespace MLflow\Enum;

/**
 * Webhook status enumeration
 */
enum WebhookStatus: string
{
    case ACTIVE = 'ACTIVE';
    case INACTIVE = 'INACTIVE';
    case DISABLED = 'DISABLED';

    /**
     * Check if webhook is active
     */
    public function isActive(): bool
    {
        return $this === self::ACTIVE;
    }

    /**
     * Check if webhook is disabled
     */
    public function isDisabled(): bool
    {
        return $this === self::DISABLED;
    }

    /**
     * Get human-readable display name
     */
    public function getDisplayName(): string
    {
        return match ($this) {
            self::ACTIVE => 'Active',
            self::INACTIVE => 'Inactive',
            self::DISABLED => 'Disabled',
        };
    }
}
