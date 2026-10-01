<?php

declare(strict_types=1);

namespace EidCloud\FunctionSimulator\Tools;

class ShellTool implements ToolInterface
{
    /**
     * Allowed sandboxed commands and their mock outputs.
     * @var array<string, array{exit_code: int, stdout: string, stderr: string}>
     */
    private array $safeCommands = [
        'whoami' => ['exit_code' => 0, 'stdout' => "eidcloud-agent\n", 'stderr' => ''],
        'uname -a' => ['exit_code' => 0, 'stdout' => "Linux eidcloud-box 6.5.0-simulated-x86_64\n", 'stderr' => ''],
        'date' => ['exit_code' => 0, 'stdout' => "2026-10-01T20:00:00Z\n", 'stderr' => ''],
        'uptime' => ['exit_code' => 0, 'stdout' => " 20:00:00 up 45 days, 12:34,  2 users,  load average: 0.15, 0.22, 0.18\n", 'stderr' => ''],
        'docker ps' => [
            'exit_code' => 0,
            'stdout' => "CONTAINER ID   IMAGE                 COMMAND                  CREATED        STATUS        PORTS     NAMES\n" .
                        "a1b2c3d4e5f6   eidcloud/agent:latest \"/entrypoint.sh\"        2 hours ago    Up 2 hours              agent-primary\n" .
                        "f6e5d4c3b2a1   redis:7-alpine        \"docker-entrypoint.s…\"   3 days ago     Up 3 days     6379/tcp  cache-redis\n",
            'stderr' => ''
        ],
        'git status' => ['exit_code' => 0, 'stdout' => "On branch main\nYour branch is up to date with 'origin/main'.\nnothing to commit, working tree clean\n", 'stderr' => ''],
    ];

    public function getName(): string
    {
        return 'shell';
    }

    public function getDescription(): string
    {
        return 'Secure sandboxed shell environment for testing CLI invocations and tool outputs safely.';
    }

    public function getParameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'command' => [
                    'type' => 'string',
                    'description' => 'Shell command string to execute in sandboxed environment',
                ],
                'env' => [
                    'type' => 'object',
                    'description' => 'Optional environment variables',
                ],
            ],
            'required' => ['command'],
        ];
    }

    public function execute(array $arguments): ToolResult
    {
        $start = microtime(true);
        $command = trim((string)($arguments['command'] ?? ''));

        if (empty($command)) {
            return ToolResult::fail('Command argument cannot be empty', (microtime(true) - $start) * 1000);
        }

        // Check if command is in our safe simulated table
        if (isset($this->safeCommands[$command])) {
            $cmd = $this->safeCommands[$command];
            return ToolResult::ok([
                'command' => $command,
                'exit_code' => $cmd['exit_code'],
                'stdout' => $cmd['stdout'],
                'stderr' => $cmd['stderr'],
                'sandboxed' => true,
            ], (microtime(true) - $start) * 1000);
        }

        // Safe simulated echo command: e.g. "echo hello"
        if (str_starts_with($command, 'echo ')) {
            $text = substr($command, 5);
            $text = trim($text, '"\'');
            return ToolResult::ok([
                'command' => $command,
                'exit_code' => 0,
                'stdout' => $text . "\n",
                'stderr' => '',
                'sandboxed' => true,
            ], (microtime(true) - $start) * 1000);
        }

        // Block dangerous arbitrary execution
        return ToolResult::fail(
            "Command '{$command}' is restricted by the sandbox security policy. Allowed commands: " . implode(', ', array_keys($this->safeCommands)) . ", echo <text>",
            (microtime(true) - $start) * 1000
        );
    }
}
