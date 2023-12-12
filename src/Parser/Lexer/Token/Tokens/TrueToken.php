<?php
declare(strict_types=1);

namespace Ropi\JsonPathEvaluator\Parser\Lexer\Token\Tokens;

class TrueToken extends AbstractCharSequenceToken
{
    protected const CHAR_SEQUENCE = ['t', 'r', 'u', 'e'];

    public static function consumeIfNext(array $chars, int &$position, array &$pairedTokenStack): static|null
    {
        if (!$pairedTokenStack || !$pairedTokenStack[0] instanceof LeftBracketToken) {
            return null;
        }

        return parent::consumeIfNext($chars, $position, $pairedTokenStack);
    }
}