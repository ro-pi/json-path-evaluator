<?php
declare(strict_types=1);

namespace Ropi\JsonPathEvaluator\Parser\Lexer\Token\Tokens;

use Ropi\JsonPathEvaluator\Parser\Lexer\Exception\LexicalException;
use Ropi\JsonPathEvaluator\Parser\Lexer\Token\AbstractToken;

class EofToken extends AbstractToken
{
    public static function consumeIfNext(array $chars, int &$position, array &$pairedTokenStack): static|null
    {
        if (isset($chars[$position])) {
            return null;
        }

        $token = new static($position, 'EOF');

        if ($pairedTokenStack) {
            $unclosedToken = $pairedTokenStack[count($pairedTokenStack) - 1];
            throw new LexicalException(
                'Unclosed ' . $unclosedToken->value,
                $unclosedToken->position,
                implode('', $chars),
                1701383010
            );
        }

        return $token;
    }
}