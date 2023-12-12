<?php
declare(strict_types=1);

namespace Ropi\JsonPathEvaluator\Parser\Lexer\Token\Tokens;

use Ropi\JsonPathEvaluator\Parser\Lexer\Exception\LexicalException;
use Ropi\JsonPathEvaluator\Parser\Lexer\Token\AbstractToken;

abstract class AbstractEndToken extends AbstractToken
{
    protected const EXPECTED_CHAR = null;
    protected const CORRESPONDING_START_TOKEN_CLASS = null;

    public static function consumeIfNext(array $chars, int &$position, array &$pairedTokenStack): static|null
    {
        $expectedChar = static::EXPECTED_CHAR;
        if (!is_string($expectedChar)) {
            throw new \LogicException(
                'Constant ' . static::class . '::EXPECTED_CHAR is not defined.',
                1701384560
            );
        }

        $correspondingStartToken = static::CORRESPONDING_START_TOKEN_CLASS;
        if (!is_string($correspondingStartToken)) {
            throw new \LogicException(
                'Constant ' . static::class . '::CORRESPONDING_START_TOKEN_CLASS is not defined.',
                1701384821
            );
        }

        if ($chars[$position] !== $expectedChar) {
            return null;
        }

        if (!$pairedTokenStack) {
            throw new LexicalException(
                'Unexpected ' . $expectedChar,
                $position,
                implode('', $chars),
                1701383893
            );
        }

        $startToken = array_pop($pairedTokenStack);
        if ($startToken::class !== $correspondingStartToken) {
            throw new LexicalException(
                'Unclosed ' . $startToken->value,
                $startToken->position,
                implode('', $chars),
                1701384055
            );
        }

        return new static($position++, $expectedChar);
    }
}