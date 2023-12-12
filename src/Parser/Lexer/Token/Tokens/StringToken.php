<?php
declare(strict_types=1);

namespace Ropi\JsonPathEvaluator\Parser\Lexer\Token\Tokens;

use Ropi\JsonPathEvaluator\Parser\Lexer\Exception\LexicalException;
use Ropi\JsonPathEvaluator\Parser\Lexer\Token\AbstractToken;

class StringToken extends AbstractToken
{
    public static function consumeIfNext(array $chars, int &$position, array &$pairedTokenStack): static|null
    {
        $quoteCharacter = $chars[$position];
        if ($quoteCharacter !== '\'' && $quoteCharacter !== '"') {
            return null;
        }

        $startPosition = $position;
        $value = $quoteCharacter;

        $position++;

        while ($chars[$position] !== $quoteCharacter) {
            if ($chars[$position] === '\\') {
                $nextChar = $chars[$position + 1] ?? null;
                if ($nextChar) {
                    if ($nextChar === 'b') {
                        $value .= "\u{0008}";
                        $position += 2;
                        continue;
                    }

                    if ($nextChar === 't') {
                        $value .= "\u{0009}";
                        $position += 2;
                        continue;
                    }

                    if ($nextChar === 'n') {
                        $value .= "\u{000A}";
                        $position += 2;
                        continue;
                    }

                    if ($nextChar === 'f') {
                        $value .= "\u{000C}";
                        $position += 2;
                        continue;
                    }

                    if ($nextChar === 'r') {
                        $value .= "\u{000D}";
                        $position += 2;
                        continue;
                    }

                    if ($nextChar === 'u') {
                        $hex = self::readUnicodeHex($chars, $position);

                        if (is_int($hex)) {
                            $position += 6;

                            if ($hex >= 0xD800 && $hex <= 0xDBFF) {
                                $lowSurrogateHex = self::readUnicodeHex($chars, $position);
                            }

                            if (isset($lowSurrogateHex)) {
                                $position += 6;
                                $codePoint = 0x10000 + (($hex - 0xD800) * 0x400) + ($lowSurrogateHex - 0xDC00);
                                $value .= mb_convert_encoding('&#' . $codePoint . ';', 'UTF-8', 'HTML-ENTITIES');
                            } else {
                                $value .= \IntlChar::chr($hex);
                            }
                        }

                        continue;
                    }

                    $value .= $nextChar;
                    $position += 2;
                }
            } else {
                $value .= $chars[$position];
                $position++;
            }

            if (!isset($chars[$position + 1])) {
                throw new LexicalException(
                    'String opened but not closed',
                    $startPosition,
                    implode('', $chars),
                    1701390061
                );
            }
        }

        // Consume closing character
        $value .= $chars[$position++];

        return new static($startPosition, $value);
    }

    /**
     * @param string[] $chars
     */
    private static function readUnicodeHex(array $chars, int $position): ?int
    {
        if (($chars[$position] ?? null) !== '\\' || ($chars[$position + 1] ?? null) !== 'u') {
            return null;
        }

        if (
            !ctype_xdigit($chars[$position + 2] ?? '')
            || !ctype_xdigit($chars[$position + 3] ?? '')
            || !ctype_xdigit($chars[$position + 4] ?? '')
            || !ctype_xdigit($chars[$position + 5] ?? '')
        ) {
            return null;
        }

        return (int)hexdec(
            $chars[$position + 2]
            . $chars[$position + 3]
            . $chars[$position + 4]
            . $chars[$position + 5]
        );
    }
}