<?php
declare(strict_types=1);

namespace Ropi\JsonPathEvaluator\Parser\Lexer\Token\Tokens;

use Ropi\JsonPathEvaluator\Parser\Lexer\Token\AbstractToken;

class IdentifierToken extends AbstractToken
{
    private const NAME_CHAR_FIRST = 'A-Za-z_\x{80}-\x{D7FF}\x{E000}-\x{10FFFF}';
    private const NAME_CHAR = self::NAME_CHAR_FIRST . '0-9';

    public static function consumeIfNext(array $chars, int &$position, array &$pairedTokenStack): static|null
    {
        $char = $chars[$position];
        if (!preg_match('/^[' . self::NAME_CHAR_FIRST . ']$/u', $char)) {
            return null;
        }

        $startPosition = $position;
        $value = '';

        while (preg_match('/^[' . self::NAME_CHAR . ']$/u', $char)) {
            $value .= $char;
            $char = $chars[++$position] ?? '';
        }

        return new static($startPosition, $value);
    }
}