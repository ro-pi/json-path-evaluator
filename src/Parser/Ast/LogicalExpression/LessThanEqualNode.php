<?php
declare(strict_types=1);

namespace Ropi\JsonPathEvaluator\Parser\Ast\LogicalExpression;

class LessThanEqualNode extends AbstractBinaryOperatorNode
{
    public const PRECEDENCE = 90;
}