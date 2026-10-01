<?php

declare(strict_types=1);

namespace EidCloud\FunctionSimulator\Visualizer;

use EidCloud\FunctionSimulator\Session\Session;
use EidCloud\FunctionSimulator\Session\Step;

class TuiRenderer
{
    private const COLOR_RESET = "\033[0m";
    private const COLOR_BOLD = "\033[1m";
    private const COLOR_DIM = "\033[2m";

    // Palette
    private const COLOR_CYAN = "\033[36m";
    private const COLOR_BLUE = "\033[34m";
    private const COLOR_YELLOW = "\033[33m";
    private const COLOR_GREEN = "\033[32m";
    private const COLOR_MAGENTA = "\033[35m";
    private const COLOR_RED = "\033[31m";
    private const COLOR_GRAY = "\033[90m";
    private const BG_DARK = "\033[48;5;236m";

    public function __construct(private readonly bool $useAnsi = true) {}

    /**
     * Render the visual loop header.
     */
    public function renderHeader(string $title, string $scenarioName): void
    {
        $bar = str_repeat('═', 76);
        echo "\n" . $this->color(self::COLOR_CYAN . self::COLOR_BOLD, "╔{$bar}╗") . "\n";
        echo $this->color(self::COLOR_CYAN . self::COLOR_BOLD, "║") . "  " .
             $this->color(self::COLOR_BOLD, "🕹️  EIDCLOUD AI FUNCTION CALLING SIMULATOR & EXECUTION SANDBOX") .
             str_repeat(' ', 12) . $this->color(self::COLOR_CYAN . self::COLOR_BOLD, "║") . "\n";
        echo $this->color(self::COLOR_CYAN . self::COLOR_BOLD, "╠{$bar}╣") . "\n";
        echo $this->color(self::COLOR_CYAN . self::COLOR_BOLD, "║") . "  " .
             $this->color(self::COLOR_DIM, "Scenario: ") . $this->color(self::COLOR_YELLOW . self::COLOR_BOLD, str_pad($scenarioName, 25)) .
             $this->color(self::COLOR_DIM, " Engine: ") . $this->color(self::COLOR_GREEN, "Pure PHP 8.2+ (Zero-dep)") .
             str_repeat(' ', 8) . $this->color(self::COLOR_CYAN . self::COLOR_BOLD, "║") . "\n";
        echo $this->color(self::COLOR_CYAN . self::COLOR_BOLD, "║") . "  " .
             $this->color(self::COLOR_DIM, "Flow: USER ➔ MODEL ➔ THINK ➔ TOOL CALL ➔ EXECUTION ➔ RESULT ➔ FINAL") .
             str_repeat(' ', 7) . $this->color(self::COLOR_CYAN . self::COLOR_BOLD, "║") . "\n";
        echo $this->color(self::COLOR_CYAN . self::COLOR_BOLD, "╚{$bar}╝") . "\n\n";
    }

    /**
     * Render a single lifecycle step.
     */
    public function renderStep(Step $step): void
    {
        $badge = $this->formatStageBadge($step->stage);
        $timeStr = $step->durationMs > 0 ? sprintf(" (%0.1fms)", $step->durationMs) : "";
        $timeBadge = $this->color(self::COLOR_DIM, $timeStr);

        echo " {$badge} {$this->color(self::COLOR_BOLD, $step->actor)}{$timeBadge}\n";
        echo "   │\n";

        // Format message lines
        $lines = explode("\n", trim($step->content));
        foreach ($lines as $i => $line) {
            $isLast = ($i === count($lines) - 1) && empty($step->payload);
            $prefix = $isLast ? "   └── " : "   ├── ";
            echo "{$prefix}" . $this->highlightContent($step->stage, $line) . "\n";
        }

        // Format payload details if present
        if (!empty($step->payload)) {
            $json = json_encode($step->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            $payloadLines = explode("\n", (string)$json);
            foreach ($payloadLines as $j => $pLine) {
                $isLast = ($j === count($payloadLines) - 1);
                $prefix = $isLast ? "   └── " : "   │   ";
                echo "{$prefix}" . $this->color(self::COLOR_GRAY, $pLine) . "\n";
            }
        }

        echo "\n";
    }

    /**
     * Render entire session summary.
     */
    public function renderSummary(Session $session): void
    {
        $bar = str_repeat('─', 76);
        echo $this->color(self::COLOR_DIM, "┌{$bar}┐") . "\n";
        echo $this->color(self::COLOR_DIM, "│") . "  " .
             $this->color(self::COLOR_GREEN . self::COLOR_BOLD, "✓ SIMULATION COMPLETE") . "  " .
             $this->color(self::COLOR_DIM, "Steps: " . count($session->getSteps()) . " | Session ID: " . $session->getId()) .
             "\n";
        echo $this->color(self::COLOR_DIM, "└{$bar}┘") . "\n";
    }

    private function formatStageBadge(string $stage): string
    {
        return match ($stage) {
            Step::STAGE_USER => $this->color(self::COLOR_BLUE . self::COLOR_BOLD, " [ USER ] "),
            Step::STAGE_MODEL => $this->color(self::COLOR_MAGENTA . self::COLOR_BOLD, " [ MODEL ] "),
            Step::STAGE_THINK => $this->color(self::COLOR_YELLOW . self::COLOR_BOLD, " [ THINK ] "),
            Step::STAGE_TOOL_CALL => $this->color(self::COLOR_CYAN . self::COLOR_BOLD, " [ TOOL_CALL ] "),
            Step::STAGE_EXECUTION => $this->color(self::COLOR_YELLOW . self::COLOR_BOLD, " [ DATABASE/HTTP ] "),
            Step::STAGE_RESULT => $this->color(self::COLOR_GREEN . self::COLOR_BOLD, " [ RESULT ] "),
            Step::STAGE_FINAL => $this->color(self::COLOR_GREEN . self::COLOR_BOLD, " [ FINAL ] "),
            default => $this->color(self::COLOR_BOLD, " [ {$stage} ] "),
        };
    }

    private function highlightContent(string $stage, string $text): string
    {
        return match ($stage) {
            Step::STAGE_USER => $this->color(self::COLOR_BLUE, $text),
            Step::STAGE_THINK => $this->color(self::COLOR_YELLOW, "💭 " . $text),
            Step::STAGE_TOOL_CALL => $this->color(self::COLOR_CYAN, "⚡ " . $text),
            Step::STAGE_EXECUTION => $this->color(self::COLOR_YELLOW, "⚙️  " . $text),
            Step::STAGE_RESULT => $this->color(self::COLOR_GREEN, "📥 " . $text),
            Step::STAGE_FINAL => $this->color(self::COLOR_BOLD . self::COLOR_GREEN, "🎯 " . $text),
            default => $text,
        };
    }

    private function color(string $ansiColor, string $text): string
    {
        if (!$this->useAnsi) {
            return $text;
        }
        return $ansiColor . $text . self::COLOR_RESET;
    }
}
