<?php
declare(strict_types=1);

namespace Ropi\JsonPathEvaluator\Parser\Ast\JsonPathExpression;

use Ropi\JsonPathEvaluator\Parser\Lexer\Token\TokenInterface;

class UnionSelectorNode extends AbstractSelectorNode
{
    /**
     * @param (AbstractSelectorNode)[] $selectorNodes
     */
    public function __construct(
        TokenInterface $token,
        public readonly array $selectorNodes,
    ) {
        parent::__construct($token);
    }
}