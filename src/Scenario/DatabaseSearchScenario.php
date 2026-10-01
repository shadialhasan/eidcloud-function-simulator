<?php

declare(strict_types=1);

namespace EidCloud\FunctionSimulator\Scenario;

use EidCloud\FunctionSimulator\Session\Session;
use EidCloud\FunctionSimulator\Session\Step;
use EidCloud\FunctionSimulator\Tools\FaultInjector;
use EidCloud\FunctionSimulator\Tools\ToolBox;

class DatabaseSearchScenario implements ScenarioInterface
{
    public function getName(): string
    {
        return 'db-search';
    }

    public function getDescription(): string
    {
        return 'Multi-step database lookup of admin user and related active cloud orders, followed by metric calculation.';
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

        // 1. USER Prompt
        $emit(new Step(
            stage: Step::STAGE_USER,
            actor: 'User (DevOps Lead)',
            content: 'Find the total spend on orders placed by the admin user "alhasan_admin" in the system.',
            timestamp: microtime(true)
        ));

        // 2. MODEL -> THINK
        $emit(new Step(
            stage: Step::STAGE_THINK,
            actor: 'Model (LLM Reasoner)',
            content: 'I need to find the user_id for "alhasan_admin" first by querying the users table. Then I will query the orders table for that user_id and calculate the sum.',
            timestamp: microtime(true)
        ));

        // 3. TOOL CALL 1: database
        $userQueryArgs = ['action' => 'query', 'table' => 'users', 'filter' => ['username' => 'alhasan_admin']];
        $emit(new Step(
            stage: Step::STAGE_TOOL_CALL,
            actor: 'Model',
            content: 'Invoking database tool: query users where username=alhasan_admin',
            payload: ['tool' => 'database', 'arguments' => $userQueryArgs],
            timestamp: microtime(true)
        ));

        // 4. EXECUTION 1
        $t0 = microtime(true);
        $resUser = $toolBox->execute('database', $userQueryArgs, $faultInjector);
        $duration = (microtime(true) - $t0) * 1000;

        $emit(new Step(
            stage: Step::STAGE_EXECUTION,
            actor: 'Database Engine',
            content: 'Executing SELECT * FROM users WHERE username = \'alhasan_admin\'',
            payload: $resUser->toArray(),
            timestamp: microtime(true),
            durationMs: $duration
        ));

        // 5. RESULT 1
        $userData = $resUser->getData();
        $adminId = $userData['rows'][0]['id'] ?? 101;
        $emit(new Step(
            stage: Step::STAGE_RESULT,
            actor: 'System Sandbox',
            content: "Found user 'alhasan_admin' with ID: {$adminId}",
            payload: $userData,
            timestamp: microtime(true)
        ));

        // 6. MODEL THINK 2
        $emit(new Step(
            stage: Step::STAGE_THINK,
            actor: 'Model (LLM Reasoner)',
            content: "User ID is {$adminId}. Now querying orders table for user_id={$adminId}.",
            timestamp: microtime(true)
        ));

        // 7. TOOL CALL 2: database orders
        $orderQueryArgs = ['action' => 'query', 'table' => 'orders', 'filter' => ['user_id' => $adminId]];
        $emit(new Step(
            stage: Step::STAGE_TOOL_CALL,
            actor: 'Model',
            content: "Invoking database tool: query orders where user_id={$adminId}",
            payload: ['tool' => 'database', 'arguments' => $orderQueryArgs],
            timestamp: microtime(true)
        ));

        // 8. EXECUTION 2
        $t1 = microtime(true);
        $resOrders = $toolBox->execute('database', $orderQueryArgs, $faultInjector);
        $duration2 = (microtime(true) - $t1) * 1000;

        $emit(new Step(
            stage: Step::STAGE_EXECUTION,
            actor: 'Database Engine',
            content: "Executing SELECT * FROM orders WHERE user_id = {$adminId}",
            payload: $resOrders->toArray(),
            timestamp: microtime(true),
            durationMs: $duration2
        ));

        // 9. RESULT 2
        $ordersData = $resOrders->getData();
        $rows = $ordersData['rows'] ?? [];
        $amounts = array_column($rows, 'amount');

        $emit(new Step(
            stage: Step::STAGE_RESULT,
            actor: 'System Sandbox',
            content: sprintf("Found %d order(s) for user_id=%d.", count($rows), $adminId),
            payload: $ordersData,
            timestamp: microtime(true)
        ));

        // 10. TOOL CALL 3: calculator
        $calcArgs = ['operation' => 'stats', 'numbers' => $amounts];
        $emit(new Step(
            stage: Step::STAGE_TOOL_CALL,
            actor: 'Model',
            content: 'Invoking calculator tool to compute accurate total spend',
            payload: ['tool' => 'calculator', 'arguments' => $calcArgs],
            timestamp: microtime(true)
        ));

        $resCalc = $toolBox->execute('calculator', $calcArgs, $faultInjector);
        $emit(new Step(
            stage: Step::STAGE_RESULT,
            actor: 'Calculator Service',
            content: sprintf("Computed total spend: $%0.2f across %d order(s)", $resCalc->getData()['sum'] ?? 0, count($amounts)),
            payload: $resCalc->toArray(),
            timestamp: microtime(true),
            durationMs: $resCalc->getExecutionTimeMs()
        ));

        // 11. FINAL RESPONSE
        $total = $resCalc->getData()['sum'] ?? 0.0;
        $emit(new Step(
            stage: Step::STAGE_FINAL,
            actor: 'Model Response',
            content: sprintf(
                "User 'alhasan_admin' (ID: %d) has %d recorded orders totaling $%0.2f (Items: %s).",
                $adminId,
                count($rows),
                $total,
                implode(', ', array_column($rows, 'item'))
            ),
            timestamp: microtime(true)
        ));
    }
}
