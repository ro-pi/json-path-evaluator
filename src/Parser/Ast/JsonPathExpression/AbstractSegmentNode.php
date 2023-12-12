<?php
declare(strict_types=1);

namespace Ropi\JsonPathEvaluator\Parser\Ast\JsonPathExpression;

use Ropi\JsonPathEvaluator\Parser\Lexer\Token\TokenInterface;

abstract class AbstractSegmentNode extends AbstractJsonPathExpressionNode
{
    public function __construct(
        TokenInterface $token,
        public readonly AbstractSegmentNode|AbstractSelectorNode $leftNode,
        public readonly AbstractSegmentNode|AbstractSelectorNode $rightNode,
    ) {
        parent::__construct($token);
    }
}