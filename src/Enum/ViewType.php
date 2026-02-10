<?php

declare(strict_types=1);

namespace MLflow\Enum;

/**
 * View type enumeration for filtering entities by lifecycle stage
 */
enum ViewType: string
{
    case ACTIVE_ONLY = 'ACTIVE_ONLY';
    case DELETED_ONLY = 'DELETED_ONLY';
    case ALL = 'ALL';

    /**
     * Get the description of the view type
     */
    public function getDescription(): string
    {
        return match ($this) {
            self::ACTIVE_ONLY => 'Active entities only',
            self::DELETED_ONLY => 'Deleted entities only',
            self::ALL => 'All entities (active and deleted)',
        };
    }

    /**
     * Check if this view includes active entities
     */
    public function includesActive(): bool
    {
        return match ($this) {
            self::ACTIVE_ONLY, self::ALL => true,
            self::DELETED_ONLY => false,
        };
    }

    /**
     * Check if this view includes deleted entities
     */
    public function includesDeleted(): bool
    {
        return match ($this) {
            self::DELETED_ONLY, self::ALL => true,
            self::ACTIVE_ONLY => false,
        };
    }

    /**
     * Get the default view type
     */
    public static function default(): self
    {
        return self::ACTIVE_ONLY;
    }
}