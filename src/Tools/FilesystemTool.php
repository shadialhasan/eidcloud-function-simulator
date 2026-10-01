<?php

declare(strict_types=1);

namespace EidCloud\FunctionSimulator\Tools;

class FilesystemTool implements ToolInterface
{
    /**
     * In-memory virtual sandboxed filesystem.
     * @var array<string, string>
     */
    private array $virtualFs = [
        '/var/log/system.log' => "[2026-10-01 08:00:01] System boot initialized.\n[2026-10-01 08:00:15] Service 'eidcloud-agent' started.\n[2026-10-01 08:12:44] Database connection pool: 10 active.",
        '/etc/config.json' => "{\n  \"env\": \"production\",\n  \"version\": \"1.0.0\",\n  \"api_endpoint\": \"https://api.eidcloud.internal/v1\"\n}",
        '/workspace/data.csv' => "id,name,role,status\n1,Alice,Architect,active\n2,Bob,Engineer,active\n3,Charlie,Security,pending",
        '/tmp/cache.lock' => "locked",
    ];

    public function getName(): string
    {
        return 'filesystem';
    }

    public function getDescription(): string
    {
        return 'Sandboxed virtual filesystem supporting read, write, and list operations without touching host storage.';
    }

    public function getParameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'action' => [
                    'type' => 'string',
                    'enum' => ['read', 'write', 'list'],
                    'description' => 'Filesystem operation to perform',
                ],
                'path' => [
                    'type' => 'string',
                    'description' => 'Target virtual file or directory path',
                ],
                'content' => [
                    'type' => 'string',
                    'description' => 'Content to write (required if action is write)',
                ],
            ],
            'required' => ['action', 'path'],
        ];
    }

    public function execute(array $arguments): ToolResult
    {
        $start = microtime(true);
        $action = $arguments['action'] ?? null;
        $path = $arguments['path'] ?? null;

        if (!$action || !$path) {
            return ToolResult::fail('Missing required parameters: action and path are mandatory.', (microtime(true) - $start) * 1000);
        }

        switch ($action) {
            case 'read':
                if (!array_key_exists($path, $this->virtualFs)) {
                    return ToolResult::fail("File not found: '{$path}'", (microtime(true) - $start) * 1000);
                }
                return ToolResult::ok([
                    'path' => $path,
                    'size' => strlen($this->virtualFs[$path]),
                    'content' => $this->virtualFs[$path],
                ], (microtime(true) - $start) * 1000);

            case 'write':
                $content = $arguments['content'] ?? '';
                $this->virtualFs[$path] = (string) $content;
                return ToolResult::ok([
                    'path' => $path,
                    'bytes_written' => strlen($content),
                    'status' => 'created_or_updated',
                ], (microtime(true) - $start) * 1000);

            case 'list':
                $prefix = rtrim($path, '/') . '/';
                $matches = [];
                foreach (array_keys($this->virtualFs) as $item) {
                    if (str_starts_with($item, $prefix) || $path === '/' || $path === '') {
                        $matches[] = $item;
                    }
                }
                return ToolResult::ok([
                    'directory' => $path,
                    'count' => count($matches),
                    'files' => $matches,
                ], (microtime(true) - $start) * 1000);

            default:
                return ToolResult::fail("Unsupported action '{$action}'. Supported: read, write, list", (microtime(true) - $start) * 1000);
        }
    }
}
