<?php
declare(strict_types=1);

namespace Ropi\JsonPathEvaluator\Parser\Ast\LogicalExpression;

class GreaterThanEqualNode extends AbstractBinaryOperatorNode
{
    public const PRECEDENCE = 90;
}