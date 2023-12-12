<?php
declare(strict_types=1);

namespace Ropi\JsonPathEvaluator\Parser\Ast\JsonPathExpression;

use Ropi\JsonPathEvaluator\Parser\Lexer\Token\TokenInterface;

class NameSelectorNode extends AbstractSelectorNode
{
    public function __construct(
        TokenInterface $token,
        public readonly string $quoteChar
    ) {
        parent::__construct($token);
    }

    public function getUnquotedValue(): string
    {
        if (!$this->quoteChar) {
            return $this->token->value;
        }

        return substr($this->token->value, 1, -1);
    }
}