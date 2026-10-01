<?php

declare(strict_types=1);

namespace EidCloud\FunctionSimulator\Tools;

class BrowserTool implements ToolInterface
{
    /**
     * Simulated websites with DOM structure and title.
     * @var array<string, array{title: string, content: string, status: int, forms: list<string>}>
     */
    private array $virtualWeb = [
        'https://eidcloud.com' => [
            'title' => 'EidCloud - Sovereign Cloud & AI Operations',
            'content' => "Welcome to EidCloud. Next-generation sovereign cloud infrastructure and AI orchestration engines.",
            'status' => 200,
            'forms' => ['login-form', 'contact-form'],
        ],
        'https://docs.eidcloud.com/agents' => [
            'title' => 'EidCloud Agent Documentation',
            'content' => "EidCloud Agents support multi-turn tool calling, deterministic replay, and sandboxed execution.",
            'status' => 200,
            'forms' => ['search-docs'],
        ],
    ];

    public function getName(): string
    {
        return 'browser';
    }

    public function getDescription(): string
    {
        return 'Mock headless browser simulator rendering DOM, inspecting elements, and simulating page navigation.';
    }

    public function getParameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'action' => [
                    'type' => 'string',
                    'enum' => ['navigate', 'render_dom', 'screenshot_text'],
                    'description' => 'Browser action to execute',
                ],
                'url' => [
                    'type' => 'string',
                    'description' => 'Target webpage URL to visit',
                ],
            ],
            'required' => ['action', 'url'],
        ];
    }

    public function execute(array $arguments): ToolResult
    {
        $start = microtime(true);
        $action = $arguments['action'] ?? 'navigate';
        $url = $arguments['url'] ?? '';

        if (empty($url)) {
            return ToolResult::fail("Parameter 'url' is required", (microtime(true) - $start) * 1000);
        }

        // Check if page exists in virtual web
        if (isset($this->virtualWeb[$url])) {
            $page = $this->virtualWeb[$url];
            return ToolResult::ok([
                'action' => $action,
                'url' => $url,
                'status_code' => $page['status'],
                'page_title' => $page['title'],
                'dom_snippet' => "<html><head><title>{$page['title']}</title></head><body><h1>{$page['title']}</h1><p>{$page['content']}</p></body></html>",
                'extracted_text' => $page['content'],
                'interactive_forms' => $page['forms'],
            ], (microtime(true) - $start) * 1000);
        }

        // Generic mock webpage render
        return ToolResult::ok([
            'action' => $action,
            'url' => $url,
            'status_code' => 200,
            'page_title' => 'Mock Page for ' . parse_url($url, PHP_URL_HOST),
            'dom_snippet' => "<html><body><h1>Simulation Output</h1><p>Rendered content from {$url}</p></body></html>",
            'extracted_text' => "Rendered content from {$url}",
            'interactive_forms' => [],
        ], (microtime(true) - $start) * 1000);
    }
}
