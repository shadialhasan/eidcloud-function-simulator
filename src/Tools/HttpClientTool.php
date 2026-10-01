<?php

declare(strict_types=1);

namespace EidCloud\FunctionSimulator\Tools;

class HttpClientTool implements ToolInterface
{
    /**
     * Pre-configured mock HTTP endpoints with simulated response payload and latency.
     * @var array<string, array{status: int, body: array<string, mixed>, headers: array<string, string>, latency_ms: int}>
     */
    private array $endpoints = [
        'GET https://api.weather.com/v1/current' => [
            'status' => 200,
            'body' => ['location' => 'Dubai', 'temp_c' => 31.5, 'condition' => 'Clear', 'humidity' => 45],
            'headers' => ['content-type' => 'application/json', 'x-request-id' => 'req-wx-998'],
            'latency_ms' => 45,
        ],
        'GET https://api.eidcloud.internal/v1/status' => [
            'status' => 200,
            'body' => ['cluster' => 'alpha', 'healthy' => true, 'nodes' => 24, 'load_avg' => 0.42],
            'headers' => ['content-type' => 'application/json', 'server' => 'eidcloud-gateway'],
            'latency_ms' => 25,
        ],
        'POST https://api.notifications.com/send' => [
            'status' => 201,
            'body' => ['status' => 'queued', 'message_id' => 'msg-8871-ok'],
            'headers' => ['content-type' => 'application/json'],
            'latency_ms' => 60,
        ],
    ];

    public function getName(): string
    {
        return 'http_client';
    }

    public function getDescription(): string
    {
        return 'Mock HTTP client simulating REST API requests with configurable endpoints, headers, and synthetic network latency.';
    }

    public function getParameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'method' => [
                    'type' => 'string',
                    'enum' => ['GET', 'POST', 'PUT', 'DELETE'],
                    'description' => 'HTTP method',
                ],
                'url' => [
                    'type' => 'string',
                    'description' => 'Target URL',
                ],
                'headers' => [
                    'type' => 'object',
                    'description' => 'Request HTTP headers',
                ],
                'body' => [
                    'type' => 'object',
                    'description' => 'JSON payload for POST/PUT requests',
                ],
            ],
            'required' => ['method', 'url'],
        ];
    }

    public function execute(array $arguments): ToolResult
    {
        $start = microtime(true);
        $method = strtoupper($arguments['method'] ?? 'GET');
        $url = $arguments['url'] ?? '';

        if (empty($url)) {
            return ToolResult::fail('Missing URL parameter', (microtime(true) - $start) * 1000);
        }

        $lookupKey = "{$method} {$url}";

        // Simulate network latency if endpoint matches
        if (isset($this->endpoints[$lookupKey])) {
            $mock = $this->endpoints[$lookupKey];
            $latency = $mock['latency_ms'];
            usleep($latency * 1000); // simulate real latency in microseconds

            $elapsed = (microtime(true) - $start) * 1000;
            return ToolResult::ok([
                'status' => $mock['status'],
                'url' => $url,
                'method' => $method,
                'headers' => $mock['headers'],
                'data' => $mock['body'],
                'synthetic_latency_ms' => $latency,
            ], $elapsed);
        }

        // Generic mock response for any arbitrary URL
        usleep(30 * 1000); // 30ms latency
        $elapsed = (microtime(true) - $start) * 1000;

        return ToolResult::ok([
            'status' => 200,
            'url' => $url,
            'method' => $method,
            'headers' => ['content-type' => 'application/json', 'x-simulated' => 'true'],
            'data' => [
                'message' => "Simulated HTTP response for {$method} {$url}",
                'echo_payload' => $arguments['body'] ?? null,
                'timestamp' => date('c'),
            ],
            'synthetic_latency_ms' => 30,
        ], $elapsed);
    }
}
