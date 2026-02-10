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

    public function getDescription(): string
    {
        return match ($this) {
            self::ACTIVE_ONLY => 'Active entities only',
            self::DELETED_ONLY => 'Deleted entities only',
            self::ALL => 'All entities (active and deleted)',
        };
    }

    public function includesActive(): bool
    {
        return match ($this) {
            self::ACTIVE_ONLY, self::ALL => true,
            self::DELETED_ONLY => false,
        };
    }

    public function includesDeleted(): bool
    {
        return match ($this) {
            self::DELETED_ONLY, self::ALL => true,
            self::ACTIVE_ONLY => false,
        };
    }

    public static function default(): self
    {
        return self::ACTIVE_ONLY;
    }
}