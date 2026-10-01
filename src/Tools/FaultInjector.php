<?php

declare(strict_types=1);

namespace EidCloud\FunctionSimulator\Tools;

class FaultInjector
{
    /**
     * Rules list for faults.
     * @var list<array{
     *   tool: string|null,
     *   type: string,
     *   delay_ms: int,
     *   error_message: string,
     *   once: bool,
     *   triggered: bool
     * }>
     */
    private array $rules = [];

    /**
     * Inject a timeout fault for a tool or any tool.
     */
    public function injectTimeout(string $tool, int $simulatedDelayMs = 2500, string $error = 'Tool execution timed out after 2000ms'): self
    {
        $this->rules[] = [
            'tool' => $tool,
            'type' => 'timeout',
            'delay_ms' => $simulatedDelayMs,
            'error_message' => $error,
            'once' => true,
            'triggered' => false,
        ];
        return $this;
    }

    /**
     * Inject a database deadlock fault.
     */
    public function injectDeadlock(string $tool = 'database', string $error = 'Deadlock detected: lock wait timeout exceeded on transaction.'): self
    {
        $this->rules[] = [
            'tool' => $tool,
            'type' => 'deadlock',
            'delay_ms' => 150,
            'error_message' => $error,
            'once' => true,
            'triggered' => false,
        ];
        return $this;
    }

    /**
     * Inject a missing parameter / validation fault.
     */
    public function injectValidationError(string $tool, string $missingParam): self
    {
        $this->rules[] = [
            'tool' => $tool,
            'type' => 'validation_error',
            'delay_ms' => 10,
            'error_message' => "Schema validation failed: missing required parameter '{$missingParam}'",
            'once' => true,
            'triggered' => false,
        ];
        return $this;
    }

    /**
     * Inject custom fault.
     */
    public function injectCustomFault(string $tool, string $errorMessage, int $delayMs = 20): self
    {
        $this->rules[] = [
            'tool' => $tool,
            'type' => 'custom',
            'delay_ms' => $delayMs,
            'error_message' => $errorMessage,
            'once' => true,
            'triggered' => false,
        ];
        return $this;
    }

    /**
     * Checks if a fault rule matches and should trigger.
     *
     * @param string $toolName
     * @param array<string, mixed> $arguments
     * @return ToolResult|null
     */
    public function checkFault(string $toolName, array $arguments): ?ToolResult
    {
        foreach ($this->rules as &$rule) {
            if ($rule['triggered'] && $rule['once']) {
                continue;
            }

            if ($rule['tool'] === null || $rule['tool'] === $toolName) {
                $rule['triggered'] = true;

                if ($rule['delay_ms'] > 0) {
                    usleep(min($rule['delay_ms'], 100) * 1000); // capped at 100ms real sleep to keep tests fast
                }

                return ToolResult::fail(
                    $rule['error_message'],
                    (float)$rule['delay_ms'],
                    ['injected_fault' => $rule['type']]
                );
            }
        }
        return null;
    }

    /**
     * Reset fault triggers.
     */
    public function reset(): void
    {
        foreach ($this->rules as &$rule) {
            $rule['triggered'] = false;
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function getRules(): array
    {
        return $this->rules;
    }
}
