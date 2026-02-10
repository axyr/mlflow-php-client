<?php

declare(strict_types=1);

namespace MLflow\Enum;

/**
 * Lifecycle stage enumeration for experiments and runs
 */
enum LifecycleStage: string
{
    case ACTIVE = 'active';
    case DELETED = 'deleted';

    /**
     * Check if the entity is active
     */
    public function isActive(): bool
    {
        return $this === self::ACTIVE;
    }

    /**
     * Check if the entity is deleted
     */
    public function isDeleted(): bool
    {
        return $this === self::DELETED;
    }

    /**
     * Get the display name for the lifecycle stage
     */
    public function getDisplayName(): string
    {
        return match ($this) {
            self::ACTIVE => 'Active',
            self::DELETED => 'Deleted',
        };
    }

    /**
     * Get the default lifecycle stage
     */
    public static function default(): self
    {
        return self::ACTIVE;
    }

    /**
     * Convert to ViewType enum
     */
    public function toViewType(): ViewType
    {
        return match ($this) {
            self::ACTIVE => ViewType::ACTIVE_ONLY,
            self::DELETED => ViewType::DELETED_ONLY,
        };
    }
}