<?php

declare(strict_types=1);

namespace EidCloud\FunctionSimulator\Scenario;

use EidCloud\FunctionSimulator\Session\Session;
use EidCloud\FunctionSimulator\Tools\FaultInjector;
use EidCloud\FunctionSimulator\Tools\ToolBox;

interface ScenarioInterface
{
    /**
     * Unique identifier of the scenario (e.g. 'db-search', 'math-solve').
     */
    public function getName(): string;

    /**
     * Description of what this scenario tests and simulates.
     */
    public function getDescription(): string;

    /**
     * Run the scenario against the given simulator tools and record steps in session.
     *
     * @param ToolBox $toolBox
     * @param Session $session
     * @param FaultInjector|null $faultInjector
     * @param callable|null $stepCallback
     */
    public function run(
        ToolBox $toolBox,
        Session $session,
        ?FaultInjector $faultInjector = null,
        ?callable $stepCallback = null
    ): void;
}
