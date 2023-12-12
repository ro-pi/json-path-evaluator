<?php
declare(strict_types=1);

namespace Ropi\JsonPathEvaluator\Parser\Ast\LogicalExpression;

use Ropi\JsonPathEvaluator\Parser\Ast\JsonPathExpression\AbstractSegmentNode;
use Ropi\JsonPathEvaluator\Parser\Ast\JsonPathExpression\AbstractSelectorNode;
use Ropi\JsonPathEvaluator\Parser\Lexer\Token\TokenInterface;

class FunctionNode extends AbstractLogicalExpressionNode
{
    /**
     * @param (AbstractLogicalExpressionNode|AbstractSegmentNode|AbstractSelectorNode)[] $argumentNodes
     */
    public function __construct(
        TokenInterface $token,
        public readonly array $argumentNodes,
    ) {
        parent::__construct($token);
    }
}