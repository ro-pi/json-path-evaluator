<?php
declare(strict_types=1);

namespace Ropi\JsonPathEvaluator\Parser\Ast\LogicalExpression;

class LogicalOrNode extends AbstractBinaryOperatorNode
{
    public const PRECEDENCE = 30;
}