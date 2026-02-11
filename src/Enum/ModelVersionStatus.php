<?php

declare(strict_types=1);

namespace MLflow\Enum;

/**
 * MLflow Model Version Status
 */
enum ModelVersionStatus: string
{
    case PENDING_REGISTRATION = 'PENDING_REGISTRATION';
    case FAILED_REGISTRATION = 'FAILED_REGISTRATION';
    case READY = 'READY';

    public function isReady(): bool
    {
        return $this === self::READY;
    }

    public function isPending(): bool
    {
        return $this === self::PENDING_REGISTRATION;
    }

    public function isFailed(): bool
    {
        return $this === self::FAILED_REGISTRATION;
    }
}
