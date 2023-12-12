<?php
declare(strict_types=1);

namespace Ropi\JsonPathEvaluator\Parser\Ast\LogicalExpression;

use Ropi\JsonPathEvaluator\Parser\Ast\JsonPathExpression\AbstractSegmentNode;
use Ropi\JsonPathEvaluator\Parser\Ast\JsonPathExpression\AbstractSelectorNode;
use Ropi\JsonPathEvaluator\Parser\Lexer\Token\TokenInterface;

abstract class AbstractUnaryOperatorNode extends AbstractLogicalExpressionNode
{
    public const PRECEDENCE = null;

    public function __construct(
        TokenInterface $token,
        public readonly AbstractSegmentNode|AbstractSelectorNode|AbstractLogicalExpressionNode $termNode,
    ) {
        parent::__construct($token);
    }
}