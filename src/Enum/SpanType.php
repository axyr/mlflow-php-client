<?php

declare(strict_types=1);

namespace MLflow\Enum;

/**
 * MLflow span types
 *
 * Note: Uses class with constants (not enum) to allow custom span types
 * This matches MLflow's implementation for extensibility
 */
class SpanType
{
    public const UNKNOWN = 'UNKNOWN';

    public const AGENT = 'AGENT';

    public const CHAIN = 'CHAIN';

    public const LLM = 'LLM';

    public const TOOL = 'TOOL';

    public const RETRIEVER = 'RETRIEVER';

    public const EMBEDDING = 'EMBEDDING';

    public const PARSER = 'PARSER';

    public const RERANKER = 'RERANKER';

    public const CHAT_MODEL = 'CHAT_MODEL';

    public const MEMORY = 'MEMORY';

    public const WORKFLOW = 'WORKFLOW';

    public const TASK = 'TASK';

    public const GUARDRAIL = 'GUARDRAIL';

    public const EVALUATOR = 'EVALUATOR';

    public static function isValid(string $type): bool
    {
        $reflection = new \ReflectionClass(self::class);

        return in_array($type, $reflection->getConstants(), true);
    }

    public static function isLLMRelated(string $type): bool
    {
        return in_array($type, [
            self::LLM,
            self::CHAT_MODEL,
            self::EMBEDDING,
        ], true);
    }

    /**
     * @return array<string>
     */
    public static function all(): array
    {
        $reflection = new \ReflectionClass(self::class);
        $constants = $reflection->getConstants();

        return array_values(array_filter($constants, 'is_string'));
    }
}
