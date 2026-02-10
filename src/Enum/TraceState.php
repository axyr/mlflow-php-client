<?php

declare(strict_types=1);

namespace MLflow\Enum;

enum TraceState: string
{
    case IN_PROGRESS = 'IN_PROGRESS';
    case OK = 'OK';
    case ERROR = 'ERROR';

    public function isTerminal(): bool
    {
        return $this !== self::IN_PROGRESS;
    }

    public function isError(): bool
    {
        return $this === self::ERROR;
    }

    public function isOk(): bool
    {
        return $this === self::OK;
    }

    public function isInProgress(): bool
    {
        return $this === self::IN_PROGRESS;
    }
}
