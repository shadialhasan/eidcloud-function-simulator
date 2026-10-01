<?php

declare(strict_types=1);

namespace EidCloud\FunctionSimulator\Tests;

use EidCloud\FunctionSimulator\Simulator;
use EidCloud\FunctionSimulator\Session\Session;
use EidCloud\FunctionSimulator\Session\Step;
use EidCloud\FunctionSimulator\Tools\DatabaseTool;
use EidCloud\FunctionSimulator\Tools\FilesystemTool;
use EidCloud\FunctionSimulator\Tools\HttpClientTool;
use EidCloud\FunctionSimulator\Tools\ShellTool;
use EidCloud\FunctionSimulator\Tools\BrowserTool;
use EidCloud\FunctionSimulator\Tools\CalculatorTool;
use EidCloud\FunctionSimulator\Tools\ToolBox;
use EidCloud\FunctionSimulator\Tools\FaultInjector;

class SimulatorTest
{
    private int $passed = 0;
    private int $failed = 0;

    public function runAll(): void
    {
        $this->testFilesystemTool();
        $this->testDatabaseTool();
        $this->testHttpClientTool();
        $this->testShellTool();
        $this->testBrowserTool();
        $this->testCalculatorTool();
        $this->testToolBoxDefinitions();
        $this->testFaultInjector();
        $this->testSessionSerialization();
        $this->testFullSimulationWorkflow();
        $this->testAllScenarios();

        echo "\n------------------------------------------------------------\n";
        echo "TEST RESULTS: {$this->passed} Passed, {$this->failed} Failed\n";
        echo "------------------------------------------------------------\n";

        if ($this->failed > 0) {
            exit(1);
        }
    }

    private function assert(bool $condition, string $message): void
    {
        if ($condition) {
            $this->passed++;
            echo "  \033[32m✓ PASS:\033[0m {$message}\n";
        } else {
            $this->failed++;
            echo "  \033[31m✗ FAIL:\033[0m {$message}\n";
        }
    }

    public function testFilesystemTool(): void
    {
        echo "Testing FilesystemTool...\n";
        $fs = new FilesystemTool();

        // Read existing file
        $res = $fs->execute(['action' => 'read', 'path' => '/etc/config.json']);
        $this->assert($res->isSuccess(), 'Virtual FS read existing file succeeds');
        $this->assert(str_contains($res->getData()['content'], 'eidcloud'), 'Virtual FS content verified');

        // Write new file
        $resWrite = $fs->execute(['action' => 'write', 'path' => '/tmp/test.txt', 'content' => 'Hello EidCloud']);
        $this->assert($resWrite->isSuccess(), 'Virtual FS write succeeds');

        // Read written file
        $resReadBack = $fs->execute(['action' => 'read', 'path' => '/tmp/test.txt']);
        $this->assert($resReadBack->getData()['content'] === 'Hello EidCloud', 'Virtual FS read back matches written content');

        // Read non-existent file
        $resFail = $fs->execute(['action' => 'read', 'path' => '/invalid/file.txt']);
        $this->assert(!$resFail->isSuccess(), 'Virtual FS read missing file fails gracefully');
    }

    public function testDatabaseTool(): void
    {
        echo "Testing DatabaseTool...\n";
        $db = new DatabaseTool();

        // Query users
        $res = $db->execute(['action' => 'query', 'table' => 'users', 'filter' => ['role' => 'admin']]);
        $this->assert($res->isSuccess(), 'DB query filtered by role succeeds');
        $this->assert($res->getData()['count'] === 1, 'DB query returned exactly 1 admin user');
        $this->assert($res->getData()['rows'][0]['username'] === 'alhasan_admin', 'DB admin username matched');

        // Insert new record
        $resInsert = $db->execute([
            'action' => 'insert',
            'table' => 'orders',
            'data' => ['user_id' => 101, 'amount' => 999.00, 'status' => 'processing', 'item' => 'H100 Cluster']
        ]);
        $this->assert($resInsert->isSuccess(), 'DB insert new order succeeds');

        // Verify insertion
        $resCheck = $db->execute(['action' => 'query', 'table' => 'orders', 'filter' => ['item' => 'H100 Cluster']]);
        $this->assert($resCheck->getData()['count'] === 1, 'DB newly inserted item queryable');
    }

    public function testHttpClientTool(): void
    {
        echo "Testing HttpClientTool...\n";
        $http = new HttpClientTool();

        $res = $http->execute(['method' => 'GET', 'url' => 'https://api.weather.com/v1/current']);
        $this->assert($res->isSuccess(), 'HTTP GET mock endpoint succeeds');
        $this->assert($res->getData()['status'] === 200, 'HTTP status is 200');
        $this->assert($res->getData()['data']['location'] === 'Dubai', 'HTTP response payload location matches Dubai');
    }

    public function testShellTool(): void
    {
        echo "Testing ShellTool...\n";
        $shell = new ShellTool();

        $resWhoami = $shell->execute(['command' => 'whoami']);
        $this->assert($resWhoami->isSuccess(), 'Shell whoami succeeds');
        $this->assert(trim($resWhoami->getData()['stdout']) === 'eidcloud-agent', 'Shell whoami returns eidcloud-agent');

        $resEcho = $shell->execute(['command' => 'echo "Production Sandbox"']);
        $this->assert($resEcho->isSuccess(), 'Shell safe echo succeeds');
        $this->assert(trim($resEcho->getData()['stdout']) === 'Production Sandbox', 'Shell echo output verified');

        $resBlocked = $shell->execute(['command' => 'rm -rf /']);
        $this->assert(!$resBlocked->isSuccess(), 'Dangerous command blocked by shell policy');
    }

    public function testBrowserTool(): void
    {
        echo "Testing BrowserTool...\n";
        $browser = new BrowserTool();

        $res = $browser->execute(['action' => 'navigate', 'url' => 'https://eidcloud.com']);
        $this->assert($res->isSuccess(), 'Browser navigate succeeds');
        $this->assert(str_contains($res->getData()['page_title'], 'EidCloud'), 'Browser title contains EidCloud');
    }

    public function testCalculatorTool(): void
    {
        echo "Testing CalculatorTool...\n";
        $calc = new CalculatorTool();

        $res = $calc->execute(['expression' => '(25 * 4) + 50 / 2']);
        $this->assert($res->isSuccess(), 'Calculator evaluation succeeds');
        $this->assert($res->getData()['result'] == 125, 'Math evaluation (25 * 4) + 50 / 2 equals 125');

        $resStats = $calc->execute(['operation' => 'stats', 'numbers' => [10, 20, 30, 40]]);
        $this->assert($resStats->isSuccess(), 'Calculator stats succeeds');
        $this->assert($resStats->getData()['mean'] == 25, 'Calculator mean is 25');
    }

    public function testToolBoxDefinitions(): void
    {
        echo "Testing ToolBox definitions...\n";
        $box = new ToolBox();
        $defs = $box->getDefinitions();

        $this->assert(count($defs) >= 6, 'ToolBox contains at least 6 tools');
        $names = array_column(array_column($defs, 'function'), 'name');
        $this->assert(in_array('database', $names, true), 'ToolBox includes database tool');
        $this->assert(in_array('filesystem', $names, true), 'ToolBox includes filesystem tool');
        $this->assert(in_array('calculator', $names, true), 'ToolBox includes calculator tool');
    }

    public function testFaultInjector(): void
    {
        echo "Testing FaultInjector...\n";
        $injector = new FaultInjector();
        $injector->injectDeadlock('database');

        $box = new ToolBox();
        $resFault = $box->execute('database', ['action' => 'query', 'table' => 'users'], $injector);

        $this->assert(!$resFault->isSuccess(), 'Fault injector intercepted execution');
        $this->assert(str_contains($resFault->getError() ?? '', 'Deadlock detected'), 'Injected deadlock error captured');

        // Next call should succeed since fault was one-time
        $resNormal = $box->execute('database', ['action' => 'query', 'table' => 'users'], $injector);
        $this->assert($resNormal->isSuccess(), 'Subsequent execution recovers after one-time fault');
    }

    public function testSessionSerialization(): void
    {
        echo "Testing Session serialization and deserialization...\n";
        $session = Session::create('test-scenario');
        $session->addStep(new Step(
            stage: Step::STAGE_USER,
            actor: 'TestUser',
            content: 'Hello World',
            payload: ['key' => 'value']
        ));

        $json = $session->toJson(false);
        $this->assert(!empty($json), 'Session serialized to JSON');

        $restored = Session::fromJson($json);
        $this->assert($restored->getId() === $session->getId(), 'Restored session ID matches');
        $this->assert(count($restored->getSteps()) === 1, 'Restored session step count matches');
        $this->assert($restored->getSteps()[0]->content === 'Hello World', 'Restored step content matches');
    }

    public function testFullSimulationWorkflow(): void
    {
        echo "Testing full Simulator run...\n";
        $simulator = new Simulator();
        $session = $simulator->run('db-search', false);

        $this->assert(count($session->getSteps()) >= 8, 'db-search produced full multi-turn cycle');
        $lastStep = $session->getSteps()[count($session->getSteps()) - 1];
        $this->assert($lastStep->stage === Step::STAGE_FINAL, 'Simulation terminated with FINAL stage');
    }

    public function testAllScenarios(): void
    {
        echo "Testing all registered scenarios...\n";
        $simulator = new Simulator();
        $scenarios = ['db-search', 'math-solve', 'web-audit'];

        foreach ($scenarios as $sc) {
            $session = $simulator->run($sc, false);
            $this->assert(count($session->getSteps()) > 0, "Scenario '{$sc}' completed with active steps");
        }
    }
}
