<?php
declare(strict_types=1);

namespace Ropi\JsonPathEvaluator\Parser\Lexer\Token\Tokens;

use Ropi\JsonPathEvaluator\Parser\Lexer\Token\AbstractToken;

class IntegerToken extends AbstractToken
{
    public static function consumeIfNext(array $chars, int &$position, array &$pairedTokenStack): static|null
    {
        if (
            !(
                ctype_digit($chars[$position])
                || (
                    str_contains('-+', $chars[$position])
                    && ctype_digit($chars[$position + 1] ?? '')
                )
            )
        ) {
            return null;
        }

        $startPosition = $position;
        $value = $chars[$position++];

        while (ctype_digit($chars[$position] ?? '')) {
            $value .= $chars[$position];
            $position++;
        }

        return new static($startPosition, $value);
    }
}