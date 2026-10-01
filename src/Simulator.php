<?php

declare(strict_types=1);

namespace EidCloud\FunctionSimulator;

use EidCloud\FunctionSimulator\Scenario\ScenarioInterface;
use EidCloud\FunctionSimulator\Scenario\ScenarioRunner;
use EidCloud\FunctionSimulator\Session\Session;
use EidCloud\FunctionSimulator\Session\Step;
use EidCloud\FunctionSimulator\Tools\FaultInjector;
use EidCloud\FunctionSimulator\Tools\ToolBox;
use EidCloud\FunctionSimulator\Visualizer\TuiRenderer;

class Simulator
{
    private ToolBox $toolBox;
    private ScenarioRunner $scenarioRunner;
    private FaultInjector $faultInjector;
    private TuiRenderer $renderer;

    public function __construct(?ToolBox $toolBox = null, ?FaultInjector $faultInjector = null)
    {
        $this->toolBox = $toolBox ?? new ToolBox();
        $this->faultInjector = $faultInjector ?? new FaultInjector();
        $this->scenarioRunner = new ScenarioRunner();
        $this->renderer = new TuiRenderer(true);
    }

    public function getToolBox(): ToolBox
    {
        return $this->toolBox;
    }

    public function getFaultInjector(): FaultInjector
    {
        return $this->faultInjector;
    }

    public function getScenarioRunner(): ScenarioRunner
    {
        return $this->scenarioRunner;
    }

    /**
     * Run a simulation by scenario name or ScenarioInterface instance.
     *
     * @param string|ScenarioInterface $scenario
     * @param bool $visualize
     * @param float $delaySeconds Delay between steps in visual mode
     * @return Session
     */
    public function run(string|ScenarioInterface $scenario, bool $visualize = false, float $delaySeconds = 0.0): Session
    {
        $instance = is_string($scenario) ? $this->scenarioRunner->get($scenario) : $scenario;
        $session = Session::create($instance->getName());

        if ($visualize) {
            $this->renderer->renderHeader('Simulation Execution', $instance->getName());
        }

        $stepCallback = function (Step $step) use ($visualize, $delaySeconds): void {
            if ($visualize) {
                $this->renderer->renderStep($step);
                if ($delaySeconds > 0) {
                    usleep((int)($delaySeconds * 1_000_000));
                }
            }
        };

        $instance->run($this->toolBox, $session, $this->faultInjector, $stepCallback);

        if ($visualize) {
            $this->renderer->renderSummary($session);
        }

        return $session;
    }

    /**
     * Replay an existing session log.
     *
     * @param Session $session
     * @param float $speed Multiplier for replay speed (e.g. 1.0 = 1x, 2.0 = 2x, 0 = instant)
     */
    public function replay(Session $session, float $speed = 1.0): void
    {
        $this->renderer->renderHeader('Session Replay', $session->getScenarioName() . ' [ID: ' . $session->getId() . ']');

        $steps = $session->getSteps();
        $delayMs = $speed > 0 ? (int)(150 / $speed) : 0;

        foreach ($steps as $step) {
            $this->renderer->renderStep($step);
            if ($delayMs > 0) {
                usleep($delayMs * 1000);
            }
        }

        $this->renderer->renderSummary($session);
    }
}
