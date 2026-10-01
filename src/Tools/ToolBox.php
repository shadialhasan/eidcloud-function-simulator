<?php

declare(strict_types=1);

namespace EidCloud\FunctionSimulator\Tools;

use InvalidArgumentException;

class ToolBox
{
    /**
     * Registered tools map.
     * @var array<string, ToolInterface>
     */
    private array $tools = [];

    public function __construct()
    {
        // Register default tools
        $this->register(new FilesystemTool());
        $this->register(new DatabaseTool());
        $this->register(new HttpClientTool());
        $this->register(new ShellTool());
        $this->register(new BrowserTool());
        $this->register(new CalculatorTool());
    }

    public function register(ToolInterface $tool): self
    {
        $this->tools[$tool->getName()] = $tool;
        return $this;
    }

    public function has(string $name): bool
    {
        return isset($this->tools[$name]);
    }

    public function get(string $name): ToolInterface
    {
        if (!isset($this->tools[$name])) {
            throw new InvalidArgumentException("Tool not registered: '{$name}'");
        }
        return $this->tools[$name];
    }

    /**
     * @return array<string, ToolInterface>
     */
    public function all(): array
    {
        return $this->tools;
    }

    /**
     * Generate schema specifications of all registered tools for AI prompts.
     *
     * @return list<array{type: string, function: array{name: string, description: string, parameters: array<string, mixed>}}>
     */
    public function getDefinitions(): array
    {
        $definitions = [];
        foreach ($this->tools as $tool) {
            $definitions[] = [
                'type' => 'function',
                'function' => [
                    'name' => $tool->getName(),
                    'description' => $tool->getDescription(),
                    'parameters' => $tool->getParameters(),
                ],
            ];
        }
        return $definitions;
    }

    /**
     * Execute a tool call with fault injection capability.
     *
     * @param string $toolName
     * @param array<string, mixed> $arguments
     * @param FaultInjector|null $faultInjector
     * @return ToolResult
     */
    public function execute(string $toolName, array $arguments, ?FaultInjector $faultInjector = null): ToolResult
    {
        $start = microtime(true);

        if (!$this->has($toolName)) {
            return ToolResult::fail("Unknown tool: '{$toolName}'", (microtime(true) - $start) * 1000);
        }

        // Check for injected fault
        if ($faultInjector !== null) {
            $fault = $faultInjector->checkFault($toolName, $arguments);
            if ($fault !== null) {
                return $fault;
            }
        }

        return $this->get($toolName)->execute($arguments);
    }
}
