<?php
declare(strict_types=1);

namespace Ropi\JsonPathEvaluator\Parser\Lexer\Token\Tokens;

use Ropi\JsonPathEvaluator\Parser\Lexer\Token\AbstractToken;

abstract class AbstractCharSequenceToken extends AbstractToken
{
    protected const CHAR_SEQUENCE = [];

    public static function consumeIfNext(array $chars, int &$position, array &$pairedTokenStack): static|null
    {
        if (!is_array(static::CHAR_SEQUENCE) || !static::CHAR_SEQUENCE) {
            throw new \LogicException(
                'Constant ' . static::class . '::CHAR_SEQUENCE is not defined.',
                1701807867
            );
        }

        for ($i = 0; $i < count(static::CHAR_SEQUENCE); $i++) {
            if ($chars[$i + $position] !== static::CHAR_SEQUENCE[$i]) {
                return null;
            }
        }

        $token = new static($position, implode('', static::CHAR_SEQUENCE));
        $position += count(static::CHAR_SEQUENCE);

        return $token;
    }
}