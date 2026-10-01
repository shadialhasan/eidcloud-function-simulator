<?php

declare(strict_types=1);

namespace EidCloud\FunctionSimulator\Scenario;

use EidCloud\FunctionSimulator\Session\Session;
use EidCloud\FunctionSimulator\Session\Step;
use EidCloud\FunctionSimulator\Tools\FaultInjector;
use EidCloud\FunctionSimulator\Tools\ToolBox;

class MathSolveScenario implements ScenarioInterface
{
    public function getName(): string
    {
        return 'math-solve';
    }

    public function getDescription(): string
    {
        return 'Multi-step computational task evaluating nested mathematical expressions and statistical aggregations.';
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
            actor: 'User (Scientist)',
            content: 'Calculate compound annual growth: (1 + 0.12)^5 * 10000, then compute the average across quarterly projections [2500, 3100, 2900, 3400].',
            timestamp: microtime(true)
        ));

        // 2. MODEL -> THINK
        $emit(new Step(
            stage: Step::STAGE_THINK,
            actor: 'Model (LLM Reasoner)',
            content: 'I need to use the calculator tool in two steps: first to evaluate (1 + 0.12)^5 * 10000, then to compute statistical aggregations for the quarterly list.',
            timestamp: microtime(true)
        ));

        // 3. TOOL CALL 1: expression
        $exprArgs = ['expression' => '(1 + 0.12)^5 * 10000'];
        $emit(new Step(
            stage: Step::STAGE_TOOL_CALL,
            actor: 'Model',
            content: 'Invoking calculator: evaluate expression "(1 + 0.12)^5 * 10000"',
            payload: ['tool' => 'calculator', 'arguments' => $exprArgs],
            timestamp: microtime(true)
        ));

        $res1 = $toolBox->execute('calculator', $exprArgs, $faultInjector);
        $emit(new Step(
            stage: Step::STAGE_RESULT,
            actor: 'Calculator Service',
            content: sprintf("Expression evaluated to: %s", $res1->getData()['formatted'] ?? 'error'),
            payload: $res1->toArray(),
            timestamp: microtime(true),
            durationMs: $res1->getExecutionTimeMs()
        ));

        // 4. TOOL CALL 2: stats
        $statsArgs = ['operation' => 'stats', 'numbers' => [2500, 3100, 2900, 3400]];
        $emit(new Step(
            stage: Step::STAGE_TOOL_CALL,
            actor: 'Model',
            content: 'Invoking calculator: compute statistics for [2500, 3100, 2900, 3400]',
            payload: ['tool' => 'calculator', 'arguments' => $statsArgs],
            timestamp: microtime(true)
        ));

        $res2 = $toolBox->execute('calculator', $statsArgs, $faultInjector);
        $statsData = $res2->getData();
        $emit(new Step(
            stage: Step::STAGE_RESULT,
            actor: 'Calculator Service',
            content: sprintf("Stats computed: Mean = %0.2f, Min = %0.2f, Max = %0.2f", $statsData['mean'] ?? 0, $statsData['min'] ?? 0, $statsData['max'] ?? 0),
            payload: $res2->toArray(),
            timestamp: microtime(true),
            durationMs: $res2->getExecutionTimeMs()
        ));

        // 5. FINAL
        $cagr = $res1->getData()['formatted'] ?? 'N/A';
        $mean = $statsData['mean'] ?? 'N/A';
        $emit(new Step(
            stage: Step::STAGE_FINAL,
            actor: 'Model Response',
            content: "Calculation Results:\n1. 5-Year Compound Value: \${$cagr}\n2. Quarterly Average: \${$mean} (Range: \${$statsData['min']} - \${$statsData['max']})",
            timestamp: microtime(true)
        ));
    }
}
