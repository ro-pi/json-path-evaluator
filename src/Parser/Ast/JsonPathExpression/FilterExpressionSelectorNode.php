<?php
declare(strict_types=1);

namespace Ropi\JsonPathEvaluator\Parser\Ast\JsonPathExpression;

use Ropi\JsonPathEvaluator\Parser\Ast\LogicalExpression\AbstractLogicalExpressionNode;
use Ropi\JsonPathEvaluator\Parser\Lexer\Token\TokenInterface;

class FilterExpressionSelectorNode extends AbstractSelectorNode
{
    public function __construct(
        TokenInterface $token,
        public readonly AbstractSegmentNode|AbstractSelectorNode|AbstractLogicalExpressionNode $expressionNode,
    ) {
        parent::__construct($token);
    }
}