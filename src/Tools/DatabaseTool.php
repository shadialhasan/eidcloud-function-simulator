<?php

declare(strict_types=1);

namespace EidCloud\FunctionSimulator\Tools;

class DatabaseTool implements ToolInterface
{
    /**
     * In-memory mock database tables.
     * @var array<string, list<array<string, mixed>>>
     */
    private array $tables = [
        'users' => [
            ['id' => 101, 'username' => 'alhasan_admin', 'role' => 'admin', 'email' => 'admin@eidcloud.com', 'active' => 1],
            ['id' => 102, 'username' => 'sarah_cloud', 'role' => 'devops', 'email' => 'sarah@eidcloud.com', 'active' => 1],
            ['id' => 103, 'username' => 'tariq_sec', 'role' => 'secops', 'email' => 'tariq@eidcloud.com', 'active' => 0],
        ],
        'orders' => [
            ['id' => 5001, 'user_id' => 101, 'amount' => 450.00, 'status' => 'completed', 'item' => 'GPU Node A100'],
            ['id' => 5002, 'user_id' => 102, 'amount' => 120.50, 'status' => 'processing', 'item' => 'Cloud Storage 5TB'],
            ['id' => 5003, 'user_id' => 101, 'amount' => 89.00, 'status' => 'pending', 'item' => 'VPC Peering'],
        ],
        'metrics' => [
            ['key' => 'cpu_load', 'value' => 42.8, 'region' => 'us-east1'],
            ['key' => 'mem_usage', 'value' => 68.2, 'region' => 'us-east1'],
            ['key' => 'disk_iops', 'value' => 1250, 'region' => 'eu-central1'],
        ],
    ];

    public function getName(): string
    {
        return 'database';
    }

    public function getDescription(): string
    {
        return 'In-memory relational database simulator supporting query filtering, table lookups, and record insertion.';
    }

    public function getParameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'action' => [
                    'type' => 'string',
                    'enum' => ['query', 'insert', 'tables'],
                    'description' => 'Database operation: query, insert, or tables list',
                ],
                'table' => [
                    'type' => 'string',
                    'description' => 'Target table name (e.g., users, orders, metrics)',
                ],
                'filter' => [
                    'type' => 'object',
                    'description' => 'Key-value pairs to filter records by exact match',
                ],
                'data' => [
                    'type' => 'object',
                    'description' => 'Record attributes to insert',
                ],
            ],
            'required' => ['action'],
        ];
    }

    public function execute(array $arguments): ToolResult
    {
        $start = microtime(true);
        $action = $arguments['action'] ?? null;

        if (!$action) {
            return ToolResult::fail('Missing required parameter: action', (microtime(true) - $start) * 1000);
        }

        if ($action === 'tables') {
            return ToolResult::ok([
                'tables' => array_keys($this->tables),
                'row_counts' => array_map(fn($rows) => count($rows), $this->tables),
            ], (microtime(true) - $start) * 1000);
        }

        $table = $arguments['table'] ?? null;
        if (!$table || !isset($this->tables[$table])) {
            return ToolResult::fail("Table not found or not specified: '{$table}'", (microtime(true) - $start) * 1000);
        }

        if ($action === 'query') {
            $filter = $arguments['filter'] ?? [];
            $records = $this->tables[$table];

            if (!empty($filter) && is_array($filter)) {
                $records = array_values(array_filter($records, function (array $row) use ($filter): bool {
                    foreach ($filter as $key => $val) {
                        if (!array_key_exists($key, $row) || (string)$row[$key] !== (string)$val) {
                            return false;
                        }
                    }
                    return true;
                }));
            }

            return ToolResult::ok([
                'table' => $table,
                'count' => count($records),
                'rows' => $records,
            ], (microtime(true) - $start) * 1000);
        }

        if ($action === 'insert') {
            $data = $arguments['data'] ?? [];
            if (empty($data) || !is_array($data)) {
                return ToolResult::fail("Insert requires 'data' payload object", (microtime(true) - $start) * 1000);
            }

            if (!isset($data['id'])) {
                $data['id'] = count($this->tables[$table]) + 1;
            }

            $this->tables[$table][] = $data;

            return ToolResult::ok([
                'table' => $table,
                'inserted' => true,
                'id' => $data['id'],
                'record' => $data,
            ], (microtime(true) - $start) * 1000);
        }

        return ToolResult::fail("Unknown database action '{$action}'", (microtime(true) - $start) * 1000);
    }
}
