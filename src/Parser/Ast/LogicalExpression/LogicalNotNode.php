<?php
declare(strict_types=1);

namespace Ropi\JsonPathEvaluator\Parser\Ast\LogicalExpression;

class LogicalNotNode extends AbstractUnaryOperatorNode
{
    public const PRECEDENCE = 140;
}