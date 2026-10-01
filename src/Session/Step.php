<?php

declare(strict_types=1);

namespace EidCloud\FunctionSimulator\Session;

class Step
{
    public const STAGE_USER = 'USER';
    public const STAGE_MODEL = 'MODEL';
    public const STAGE_THINK = 'THINK';
    public const STAGE_TOOL_CALL = 'TOOL CALL';
    public const STAGE_EXECUTION = 'DATABASE/HTTP';
    public const STAGE_RESULT = 'RESULT';
    public const STAGE_FINAL = 'FINAL';

    /**
     * @param string $stage
     * @param string $actor
     * @param string $content
     * @param array<string, mixed> $payload
     * @param float $timestamp
     * @param float $durationMs
     */
    public function __construct(
        public readonly string $stage,
        public readonly string $actor,
        public readonly string $content,
        public readonly array $payload = [],
        public readonly float $timestamp = 0.0,
        public readonly float $durationMs = 0.0
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'stage' => $this->stage,
            'actor' => $this->actor,
            'content' => $this->content,
            'payload' => $this->payload,
            'timestamp' => $this->timestamp,
            'duration_ms' => round($this->durationMs, 2),
        ];
    }

    /**
     * @param array<string, mixed> $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            stage: $data['stage'] ?? self::STAGE_MODEL,
            actor: $data['actor'] ?? 'system',
            content: $data['content'] ?? '',
            payload: $data['payload'] ?? [],
            timestamp: (float)($data['timestamp'] ?? microtime(true)),
            durationMs: (float)($data['duration_ms'] ?? 0.0)
        );
    }
}
