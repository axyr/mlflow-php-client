<?php

declare(strict_types=1);

namespace MLflow\Model;

/**
 * Represents file information in MLflow artifacts (immutable)
 */
readonly class FileInfo implements \JsonSerializable, \Stringable
{
    public function __construct(
        public string $path,
        public bool $isDir,
        public ?int $fileSize = null,
    ) {
    }

    /**
     * @param array{path: string, is_dir?: bool, file_size?: int|null} $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            path: $data['path'],
            isDir: $data['is_dir'] ?? false,
            fileSize: isset($data['file_size']) ? (int) $data['file_size'] : null,
        );
    }

    /**
     * @return array{path: string, is_dir: bool, file_size?: int}
     */
    public function toArray(): array
    {
        $data = [
            'path' => $this->path,
            'is_dir' => $this->isDir,
        ];

        if ($this->fileSize !== null) {
            $data['file_size'] = $this->fileSize;
        }

        return $data;
    }

    /**
     * @return array{path: string, is_dir: bool, file_size?: int}
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    public function getName(): string
    {
        return basename($this->path);
    }

    public function getParentPath(): string
    {
        return dirname($this->path);
    }

    public function getHumanReadableSize(): ?string
    {
        if ($this->fileSize === null) {
            return null;
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = $this->fileSize;
        $unitIndex = 0;

        while ($bytes >= 1024 && $unitIndex < count($units) - 1) {
            $bytes /= 1024;
            $unitIndex++;
        }

        return sprintf('%.2f %s', $bytes, $units[$unitIndex]);
    }

    public function __toString(): string
    {
        $type = $this->isDir ? 'dir' : 'file';
        $size = $this->getHumanReadableSize() ?? 'unknown';

        return "{$this->path} ({$type}, {$size})";
    }

    public function isFile(): bool
    {
        return !$this->isDir;
    }
}
