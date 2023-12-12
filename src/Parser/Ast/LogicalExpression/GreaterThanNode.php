<?php
declare(strict_types=1);

namespace Ropi\JsonPathEvaluator\Parser\Ast\LogicalExpression;

class GreaterThanNode extends AbstractBinaryOperatorNode
{
    public const PRECEDENCE = 90;
}