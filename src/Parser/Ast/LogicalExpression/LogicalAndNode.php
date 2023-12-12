<?php
declare(strict_types=1);

namespace Ropi\JsonPathEvaluator\Parser\Ast\LogicalExpression;

class LogicalAndNode extends AbstractBinaryOperatorNode
{
    public const PRECEDENCE = 40;
}