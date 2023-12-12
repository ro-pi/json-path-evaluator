<?php
declare(strict_types=1);

namespace Ropi\JsonPathEvaluator\Parser\Ast\LogicalExpression;

class EqualNode extends AbstractBinaryOperatorNode
{
    public const PRECEDENCE = 80;
}