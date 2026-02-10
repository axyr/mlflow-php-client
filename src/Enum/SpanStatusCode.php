<?php

declare(strict_types=1);

namespace MLflow\Enum;

enum SpanStatusCode: string
{
    case UNSET = 'UNSET';
    case OK = 'OK';
    case ERROR = 'ERROR';

    public function isError(): bool
    {
        return $this === self::ERROR;
    }

    public function isOk(): bool
    {
        return $this === self::OK;
    }

    public function isUnset(): bool
    {
        return $this === self::UNSET;
    }
}
