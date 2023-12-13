<?php
declare(strict_types=1);

namespace Ropi\JsonPathEvaluator\Parser\Ast\LogicalExpression;

use Ropi\JsonPathEvaluator\Parser\Ast\JsonPathExpression\AbstractSegmentNode;
use Ropi\JsonPathEvaluator\Parser\Ast\JsonPathExpression\AbstractSelectorNode;
use Ropi\JsonPathEvaluator\Parser\Lexer\Token\TokenInterface;

abstract class AbstractBinaryOperatorNode extends AbstractLogicalExpressionNode
{
    public const PRECEDENCE = null;

    /**
     * @noinspection PhpUnused
     */
    public const ASSOCIATIVITY_LEFT_TO_RIGHT = true;

    public function __construct(
        TokenInterface $token,
        public readonly AbstractSegmentNode|AbstractSelectorNode|AbstractLogicalExpressionNode $leftNode,
        public readonly AbstractSegmentNode|AbstractSelectorNode|AbstractLogicalExpressionNode $rightNode,
    ) {
        parent::__construct($token);
    }
}