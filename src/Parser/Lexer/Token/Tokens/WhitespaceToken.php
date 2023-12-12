<?php
declare(strict_types=1);

namespace Ropi\JsonPathEvaluator\Parser\Lexer\Token\Tokens;

use Ropi\JsonPathEvaluator\Parser\Lexer\Token\AbstractToken;

class WhitespaceToken extends AbstractToken
{
    public static function consumeIfNext(array $chars, int &$position, array &$pairedTokenStack): int|null
    {
        if (!ctype_space($chars[$position])) {
            return null;
        }

        $startPosition = $position;

        while (ctype_space($chars[$position] ?? '')) {
            $position++;
        }

        return $startPosition;
    }
}