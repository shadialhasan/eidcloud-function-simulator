<?php

declare(strict_types=1);

namespace EidCloud\FunctionSimulator\Scenario;

use InvalidArgumentException;

class ScenarioRunner
{
    /**
     * @var array<string, ScenarioInterface>
     */
    private array $scenarios = [];

    public function __construct()
    {
        $this->register(new DatabaseSearchScenario());
        $this->register(new MathSolveScenario());
        $this->register(new WebAuditScenario());
    }

    public function register(ScenarioInterface $scenario): self
    {
        $this->scenarios[$scenario->getName()] = $scenario;
        return $this;
    }

    public function has(string $name): bool
    {
        return isset($this->scenarios[$name]);
    }

    public function get(string $name): ScenarioInterface
    {
        if (!isset($this->scenarios[$name])) {
            throw new InvalidArgumentException("Unknown scenario: '{$name}'. Available: " . implode(', ', array_keys($this->scenarios)));
        }
        return $this->scenarios[$name];
    }

    /**
     * @return array<string, ScenarioInterface>
     */
    public function all(): array
    {
        return $this->scenarios;
    }
}
