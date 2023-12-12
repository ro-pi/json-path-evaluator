<?php
declare(strict_types=1);

namespace Ropi\JsonPathEvaluator\Parser\Lexer\Token\Tokens;

use Ropi\JsonPathEvaluator\Parser\Lexer\Token\AbstractToken;

class GreaterThanEqualToken extends AbstractToken
{
    public static function consumeIfNext(array $chars, int &$position, array &$pairedTokenStack): static|null
    {
        if (!($chars[$position] === '>' && ($chars[$position + 1] ?? '') === '=')) {
            return null;
        }

        $token = new static($position, '>=');

        $position += 2;

        return $token;
    }
}