<?php
declare(strict_types=1);

namespace Ropi\JsonPathEvaluator\Parser\Ast\JsonPathExpression;

use Ropi\JsonPathEvaluator\Parser\Lexer\Token\TokenInterface;

class ArraySliceSelectorNode extends AbstractSelectorNode
{
    public function __construct(
        TokenInterface $token,
        public readonly null|string $start,
        public readonly null|string $end,
        public readonly null|string $step,
    ) {
        parent::__construct($token);
    }
}