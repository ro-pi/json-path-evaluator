<?php
declare(strict_types=1);

namespace Ropi\JsonPathEvaluator\Parser\Lexer\Token\Tokens;

use Ropi\JsonPathEvaluator\Parser\Lexer\Token\AbstractToken;

abstract class AbstractStartToken extends AbstractToken
{
    protected const EXPECTED_CHAR = null;

    public static function consumeIfNext(array $chars, int &$position, array &$pairedTokenStack): static|null
    {
        $expectedChar = static::EXPECTED_CHAR;

        if (!is_string($expectedChar)) {
            throw new \LogicException(
                'Constant ' . static::class . '::EXPECTED_CHAR is not defined.',
                1701384481
            );
        }

        if ($chars[$position] !== $expectedChar) {
            return null;
        }

        $token = new static($position++, $expectedChar);
        $pairedTokenStack[] = $token;

        return $token;
    }
}