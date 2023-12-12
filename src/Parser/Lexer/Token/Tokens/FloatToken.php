<?php
declare(strict_types=1);

namespace Ropi\JsonPathEvaluator\Parser\Lexer\Token\Tokens;

use Ropi\JsonPathEvaluator\Parser\Lexer\Token\AbstractToken;

class FloatToken extends AbstractToken
{
    public static function consumeIfNext(array $chars, int &$position, array &$pairedTokenStack): static|null
    {
        if (!$pairedTokenStack || !$pairedTokenStack[0] instanceof LeftBracketToken) {
            return null;
        }

        if (
            !(
                ctype_digit($chars[$position])
                || (
                    str_contains('-+', $chars[$position])
                    && ctype_digit($chars[$position + 1] ?? '')
                ) || (
                    $chars[$position] === '.' && ctype_digit(($chars[$position + 1] ?? ''))
                )
            )
        ) {
            return null;
        }

        if ($chars[$position] !== '.') {
            $charPointer = $position + 1;
            while (ctype_digit($chars[$charPointer] ?? '')) {
                $charPointer++;
            }

            if (
                $chars[$charPointer] !== '.'
                && !(
                    str_contains('eE', $chars[$charPointer])
                    && (
                        str_contains('+-', $chars[$charPointer + 1] ?? '')
                        || ctype_digit($chars[$charPointer + 1] ?? '')
                    )
                )
            ) {
                return null;
            }
        }

        $startPosition = $position;
        $value = $chars[$position++];

        $eProcessed = false;
        $dotProcessed = false;

        while (
            ctype_digit($chars[$position] ?? '')
            || ($chars[$position] === '.' && !$dotProcessed)
            || (str_contains('eE', $chars[$position] ?? '') && !$eProcessed)
        ) {
            if ($chars[$position] === '.') {
                $dotProcessed = true;
            } elseif (str_contains('eE', $chars[$position])) {
                $eProcessed = true;
                $dotProcessed = true;

                $value .= $chars[$position++];

                if (str_contains('+-', ($chars[$position + 1] ?? ''))) {
                    $value .= $chars[$position++];
                }
            }

            $value .= $chars[$position++];
        }

        return new static($startPosition, $value);
    }
}