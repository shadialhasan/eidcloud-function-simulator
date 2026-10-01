<?php

declare(strict_types=1);

namespace EidCloud\FunctionSimulator\Scenario;

use EidCloud\FunctionSimulator\Session\Session;
use EidCloud\FunctionSimulator\Session\Step;
use EidCloud\FunctionSimulator\Tools\FaultInjector;
use EidCloud\FunctionSimulator\Tools\ToolBox;

class WebAuditScenario implements ScenarioInterface
{
    public function getName(): string
    {
        return 'web-audit';
    }

    public function getDescription(): string
    {
        return 'Simulates navigating cloud web endpoints, inspecting DOM headers, and querying internal health metrics.';
    }

    public function run(
        ToolBox $toolBox,
        Session $session,
        ?FaultInjector $faultInjector = null,
        ?callable $stepCallback = null
    ): void {
        $emit = function (Step $step) use ($session, $stepCallback): void {
            $session->addStep($step);
            if ($stepCallback !== null) {
                $stepCallback($step);
            }
        };

        // 1. USER
        $emit(new Step(
            stage: Step::STAGE_USER,
            actor: 'User (SRE Specialist)',
            content: 'Inspect the health of our cloud gateway at "https://api.eidcloud.internal/v1/status" and check active shell processes.',
            timestamp: microtime(true)
        ));

        // 2. MODEL THINK
        $emit(new Step(
            stage: Step::STAGE_THINK,
            actor: 'Model',
            content: 'I need to make an HTTP GET request to the internal gateway URL, then run "docker ps" via sandboxed shell to inspect running containers.',
            timestamp: microtime(true)
        ));

        // 3. TOOL HTTP
        $httpArgs = ['method' => 'GET', 'url' => 'https://api.eidcloud.internal/v1/status'];
        $emit(new Step(
            stage: Step::STAGE_TOOL_CALL,
            actor: 'Model',
            content: 'Invoking http_client: GET https://api.eidcloud.internal/v1/status',
            payload: ['tool' => 'http_client', 'arguments' => $httpArgs],
            timestamp: microtime(true)
        ));

        $resHttp = $toolBox->execute('http_client', $httpArgs, $faultInjector);
        $emit(new Step(
            stage: Step::STAGE_EXECUTION,
            actor: 'Gateway Endpoint',
            content: 'HTTP 200 OK received from internal gateway',
            payload: $resHttp->toArray(),
            timestamp: microtime(true),
            durationMs: $resHttp->getExecutionTimeMs()
        ));

        // 4. TOOL SHELL
        $shellArgs = ['command' => 'docker ps'];
        $emit(new Step(
            stage: Step::STAGE_TOOL_CALL,
            actor: 'Model',
            content: 'Invoking shell: docker ps',
            payload: ['tool' => 'shell', 'arguments' => $shellArgs],
            timestamp: microtime(true)
        ));

        $resShell = $toolBox->execute('shell', $shellArgs, $faultInjector);
        $emit(new Step(
            stage: Step::STAGE_EXECUTION,
            actor: 'Container Daemon',
            content: 'Sandboxed docker process table queried',
            payload: $resShell->toArray(),
            timestamp: microtime(true),
            durationMs: $resShell->getExecutionTimeMs()
        ));

        // 5. FINAL
        $emit(new Step(
            stage: Step::STAGE_FINAL,
            actor: 'Model Response',
            content: "Cluster status is HEALTHY (24 nodes active, load 0.42). 2 active Docker containers confirmed running: 'agent-primary' and 'cache-redis'.",
            timestamp: microtime(true)
        ));
    }
}
