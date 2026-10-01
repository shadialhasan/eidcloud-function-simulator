<?php

declare(strict_types=1);

namespace EidCloud\FunctionSimulator\Session;

class Session
{
    /**
     * @var list<Step>
     */
    private array $steps = [];

    /**
     * @param string $id
     * @param string $scenarioName
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        private readonly string $id,
        private readonly string $scenarioName = 'generic',
        private array $metadata = []
    ) {
        $this->metadata['created_at'] = date('c');
    }

    public static function create(string $scenarioName = 'generic'): self
    {
        $id = 'sim-' . bin2hex(random_bytes(6));
        return new self($id, $scenarioName);
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getScenarioName(): string
    {
        return $this->scenarioName;
    }

    public function addStep(Step $step): self
    {
        $this->steps[] = $step;
        return $this;
    }

    /**
     * @return list<Step>
     */
    public function getSteps(): array
    {
        return $this->steps;
    }

    /**
     * @return array<string, mixed>
     */
    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function setMetadata(string $key, mixed $value): self
    {
        $this->metadata[$key] = $value;
        return $this;
    }

    /**
     * Export session as array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'session_id' => $this->id,
            'scenario' => $this->scenarioName,
            'metadata' => $this->metadata,
            'total_steps' => count($this->steps),
            'steps' => array_map(fn(Step $s) => $s->toArray(), $this->steps),
        ];
    }

    /**
     * Serialize session to JSON string.
     */
    public function toJson(bool $pretty = true): string
    {
        $flags = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;
        if ($pretty) {
            $flags |= JSON_PRETTY_PRINT;
        }
        return (string) json_encode($this->toArray(), $flags);
    }

    /**
     * Restore session from JSON string.
     */
    public static function fromJson(string $json): self
    {
        $data = json_decode($json, true);
        if (!is_array($data)) {
            throw new \InvalidArgumentException('Invalid JSON provided for session restoration.');
        }

        $session = new self(
            id: $data['session_id'] ?? ('sim-' . bin2hex(random_bytes(4))),
            scenarioName: $data['scenario'] ?? 'generic',
            metadata: $data['metadata'] ?? []
        );

        if (isset($data['steps']) && is_array($data['steps'])) {
            foreach ($data['steps'] as $stepData) {
                if (is_array($stepData)) {
                    $session->addStep(Step::fromArray($stepData));
                }
            }
        }

        return $session;
    }
}
