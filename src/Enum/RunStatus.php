<?php

declare(strict_types=1);

namespace MLflow\Enum;

/**
 * Run status enumeration
 */
enum RunStatus: string
{
    case RUNNING = 'RUNNING';
    case SCHEDULED = 'SCHEDULED';
    case FINISHED = 'FINISHED';
    case FAILED = 'FAILED';
    case KILLED = 'KILLED';

    /**
     * Check if the run is in a terminal state
     */
    public function isTerminal(): bool
    {
        return match ($this) {
            self::FINISHED, self::FAILED, self::KILLED => true,
            default => false,
        };
    }

    public function isActive(): bool
    {
        return match ($this) {
            self::RUNNING, self::SCHEDULED => true,
            default => false,
        };
    }

    public function getDisplayName(): string
    {
        return match ($this) {
            self::RUNNING => 'Running',
            self::SCHEDULED => 'Scheduled',
            self::FINISHED => 'Finished',
            self::FAILED => 'Failed',
            self::KILLED => 'Killed',
        };
    }

    public static function fromString(string $value): self
    {
        return self::from($value);
    }
}
