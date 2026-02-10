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

    public function isActive(): bool
    {
        return $this === self::ACTIVE;
    }

    public function isDeleted(): bool
    {
        return $this === self::DELETED;
    }

    public function getDisplayName(): string
    {
        return match ($this) {
            self::ACTIVE => 'Active',
            self::DELETED => 'Deleted',
        };
    }

    public static function default(): self
    {
        return self::ACTIVE;
    }

    public function toViewType(): ViewType
    {
        return match ($this) {
            self::ACTIVE => ViewType::ACTIVE_ONLY,
            self::DELETED => ViewType::DELETED_ONLY,
        };
    }
}