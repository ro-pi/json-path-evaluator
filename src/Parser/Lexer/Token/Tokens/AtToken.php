<?php
declare(strict_types=1);

namespace Ropi\JsonPathEvaluator\Parser\Lexer\Token\Tokens;

use Ropi\JsonPathEvaluator\Parser\Lexer\Token\AbstractToken;

class AtToken extends AbstractToken
{
    public static function consumeIfNext(array $chars, int &$position, array &$pairedTokenStack): static|null
    {
        if ($chars[$position] !== '@') {
            return null;
        }

        return new static($position++, '@');
    }
}