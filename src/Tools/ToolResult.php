<?php

declare(strict_types=1);

namespace EidCloud\FunctionSimulator\Tools;

class ToolResult
{
    /**
     * @param bool $success
     * @param mixed $data
     * @param string|null $error
     * @param float $executionTimeMs
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        private readonly bool $success,
        private readonly mixed $data = null,
        private readonly ?string $error = null,
        private readonly float $executionTimeMs = 0.0,
        private readonly array $metadata = []
    ) {}

    public static function ok(mixed $data, float $executionTimeMs = 0.0, array $metadata = []): self
    {
        return new self(
            success: true,
            data: $data,
            error: null,
            executionTimeMs: $executionTimeMs,
            metadata: $metadata
        );
    }

    public static function fail(string $error, float $executionTimeMs = 0.0, array $metadata = []): self
    {
        return new self(
            success: false,
            data: null,
            error: $error,
            executionTimeMs: $executionTimeMs,
            metadata: $metadata
        );
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function getData(): mixed
    {
        return $this->data;
    }

    public function getError(): ?string
    {
        return $this->error;
    }

    public function getExecutionTimeMs(): float
    {
        return $this->executionTimeMs;
    }

    /**
     * @return array<string, mixed>
     */
    public function getMetadata(): array
    {
        return $this->metadata;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'data' => $this->data,
            'error' => $this->error,
            'execution_time_ms' => round($this->executionTimeMs, 2),
            'metadata' => $this->metadata,
        ];
    }
}
