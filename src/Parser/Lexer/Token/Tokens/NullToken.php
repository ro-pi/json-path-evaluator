<?php
declare(strict_types=1);

namespace Ropi\JsonPathEvaluator\Parser\Lexer\Token\Tokens;

class NullToken extends AbstractCharSequenceToken
{
    protected const CHAR_SEQUENCE = ['n', 'u', 'l', 'l'];

    public static function consumeIfNext(array $chars, int &$position, array &$pairedTokenStack): static|null
    {
        if (!$pairedTokenStack || !$pairedTokenStack[0] instanceof LeftBracketToken) {
            return null;
        }

        return parent::consumeIfNext($chars, $position, $pairedTokenStack);
    }
}