<?php

declare(strict_types=1);

namespace EidCloud\FunctionSimulator\Tools;

interface ToolInterface
{
    /**
     * Unique identifier/name of the tool (e.g., 'database_query', 'fs_read').
     */
    public function getName(): string;

    /**
     * Human-readable description for AI system prompts.
     */
    public function getDescription(): string;

    /**
     * Parameter definitions schema matching JSON Schema / OpenAPI specs.
     *
     * @return array<string, mixed>
     */
    public function getParameters(): array;

    /**
     * Execute the tool with given arguments.
     *
     * @param array<string, mixed> $arguments
     * @return ToolResult
     */
    public function execute(array $arguments): ToolResult;
}
