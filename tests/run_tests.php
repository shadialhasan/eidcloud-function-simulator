<?php

declare(strict_types=1);

// Zero-dependency automated test runner
require_once __DIR__ . '/../autoload.php';
require_once __DIR__ . '/SimulatorTest.php';

use EidCloud\FunctionSimulator\Tests\SimulatorTest;

echo "============================================================\n";
echo "🕹️  RUNNING EIDCLOUD FUNCTION SIMULATOR TEST SUITE (PHP " . PHP_VERSION . ")\n";
echo "============================================================\n\n";

$suite = new SimulatorTest();
$suite->runAll();
