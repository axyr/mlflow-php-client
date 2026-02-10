<?php

declare(strict_types=1);

namespace MLflow\Enum;

/**
 * Model stage enumeration for model registry
 */
enum ModelStage: string
{
    case NONE = 'None';
    case STAGING = 'Staging';
    case PRODUCTION = 'Production';
    case ARCHIVED = 'Archived';

    /**
     * Check if the model is in an active stage (not archived)
     */
    public function isActive(): bool
    {
        return $this !== self::ARCHIVED;
    }

    /**
     * Check if the model is deployed (Staging or Production)
     */
    public function isDeployed(): bool
    {
        return match ($this) {
            self::STAGING, self::PRODUCTION => true,
            default => false,
        };
    }

    /**
     * Get the priority of the stage (higher number = higher priority)
     */
    public function getPriority(): int
    {
        return match ($this) {
            self::PRODUCTION => 3,
            self::STAGING => 2,
            self::NONE => 1,
            self::ARCHIVED => 0,
        };
    }

    /**
     * Get the display name for the stage
     */
    public function getDisplayName(): string
    {
        return match ($this) {
            self::NONE => 'None',
            self::STAGING => 'Staging',
            self::PRODUCTION => 'Production',
            self::ARCHIVED => 'Archived',
        };
    }

    /**
     * Get all deployable stages
     *
     * @return array<self>
     */
    public static function getDeployableStages(): array
    {
        return [self::STAGING, self::PRODUCTION];
    }

    /**
     * Validate if a transition from current stage to target stage is allowed
     */
    public function canTransitionTo(self $targetStage): bool
    {
        // Any stage can transition to any other stage in MLflow
        // But we can add business rules here if needed
        return true;
    }
}