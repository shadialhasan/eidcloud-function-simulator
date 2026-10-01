<?php

declare(strict_types=1);

namespace EidCloud\FunctionSimulator\Tools;

class CalculatorTool implements ToolInterface
{
    public function getName(): string
    {
        return 'calculator';
    }

    public function getDescription(): string
    {
        return 'Precise mathematical solver evaluating arithmetic expressions and operations safely.';
    }

    public function getParameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'expression' => [
                    'type' => 'string',
                    'description' => 'Mathematical expression (e.g., "(125 * 4) / 2 + 15")',
                ],
                'operation' => [
                    'type' => 'string',
                    'enum' => ['evaluate', 'stats'],
                    'description' => 'Operation mode',
                ],
                'numbers' => [
                    'type' => 'array',
                    'items' => ['type' => 'number'],
                    'description' => 'List of numbers for statistical analysis (sum, mean, min, max)',
                ],
            ],
        ];
    }

    public function execute(array $arguments): ToolResult
    {
        $start = microtime(true);
        $operation = $arguments['operation'] ?? 'evaluate';

        if ($operation === 'stats' && isset($arguments['numbers']) && is_array($arguments['numbers'])) {
            $nums = array_map('floatval', $arguments['numbers']);
            if (empty($nums)) {
                return ToolResult::fail('Numbers list is empty', (microtime(true) - $start) * 1000);
            }

            $count = count($nums);
            $sum = array_sum($nums);
            $mean = $sum / $count;
            $min = min($nums);
            $max = max($nums);

            return ToolResult::ok([
                'count' => $count,
                'sum' => $sum,
                'mean' => $mean,
                'min' => $min,
                'max' => $max,
            ], (microtime(true) - $start) * 1000);
        }

        $expr = $arguments['expression'] ?? null;
        if (!$expr || !is_string($expr)) {
            return ToolResult::fail("Missing 'expression' or 'numbers' parameter", (microtime(true) - $start) * 1000);
        }

        $expr = trim($expr);
        // Security check: only allow digits, operators, parentheses, decimal points, and whitespace
        if (!preg_match('/^[\d\s\+\-\*\/\(\)\.\,\^%]+$/', $expr)) {
            return ToolResult::fail("Invalid characters in mathematical expression: '{$expr}'", (microtime(true) - $start) * 1000);
        }

        try {
            $result = $this->evaluateMath($expr);

            return ToolResult::ok([
                'expression' => $expr,
                'result' => $result,
                'formatted' => is_float($result) ? round($result, 6) : $result,
            ], (microtime(true) - $start) * 1000);
        } catch (\Throwable $e) {
            return ToolResult::fail("Math evaluation error: " . $e->getMessage(), (microtime(true) - $start) * 1000);
        }
    }

    private function evaluateMath(string $expr): float
    {
        $tokens = preg_split('/([+\\-*\\/()^%])|\\s+/', $expr, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
        $pos = 0;
        
        $parseFactor = null;
        $parsePower = null;
        $parseTerm = null;
        $parseExpr = null;

        $parseFactor = function() use (&$parseExpr, &$parseFactor, &$tokens, &$pos): float {
            if ($pos >= count($tokens)) {
                throw new \InvalidArgumentException("Unexpected end of expression");
            }
            $t = $tokens[$pos++];
            if ($t === '+') return +$parseFactor();
            if ($t === '-') return -$parseFactor();
            if ($t === '(') {
                $val = $parseExpr();
                if ($pos >= count($tokens) || $tokens[$pos++] !== ')') {
                    throw new \InvalidArgumentException("Mismatched parenthesis");
                }
                return $val;
            }
            if (!is_numeric($t)) {
                throw new \InvalidArgumentException("Invalid number: '{$t}'");
            }
            return (float)$t;
        };

        $parsePower = function() use (&$parsePower, &$parseFactor, &$tokens, &$pos): float {
            $val = $parseFactor();
            while ($pos < count($tokens) && $tokens[$pos] === '^') {
                $pos++;
                $rhs = $parseFactor();
                $val = pow($val, $rhs);
            }
            return $val;
        };

        $parseTerm = function() use (&$parseTerm, &$parsePower, &$tokens, &$pos): float {
            $val = $parsePower();
            while ($pos < count($tokens) && in_array($tokens[$pos], ['*', '/', '%'], true)) {
                $op = $tokens[$pos++];
                $rhs = $parsePower();
                if ($op === '*') {
                    $val *= $rhs;
                } elseif ($op === '/') {
                    if ($rhs == 0.0) throw new \DivisionByZeroError("Division by zero");
                    $val /= $rhs;
                } elseif ($op === '%') {
                    if ($rhs == 0.0) throw new \DivisionByZeroError("Modulo by zero");
                    $val = fmod($val, $rhs);
                }
            }
            return $val;
        };

        $parseExpr = function() use (&$parseExpr, &$parseTerm, &$tokens, &$pos): float {
            $val = $parseTerm();
            while ($pos < count($tokens) && in_array($tokens[$pos], ['+', '-'], true)) {
                $op = $tokens[$pos++];
                $rhs = $parseTerm();
                $val = $op === '+' ? $val + $rhs : $val - $rhs;
            }
            return $val;
        };

        $res = $parseExpr();
        if ($pos < count($tokens)) {
            throw new \InvalidArgumentException("Unexpected token: '{$tokens[$pos]}'");
        }
        return $res;
    }
}
