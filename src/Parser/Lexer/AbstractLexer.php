<?php
declare(strict_types=1);

namespace Ropi\JsonPathEvaluator\Parser\Lexer;

use Ropi\JsonPathEvaluator\Parser\Lexer\Exception\LexicalException;
use Ropi\JsonPathEvaluator\Parser\Lexer\Token\TokenInterface;
use Ropi\JsonPathEvaluator\Parser\Lexer\Token\Tokens\EofToken;

abstract class AbstractLexer implements LexerInterface
{
    /**
     * @var class-string[]
     */
    protected array $tokenClassNames = [];

    public function tokenize(string $expression): \Iterator
    {
        $chars = mb_str_split($expression, 1, 'UTF-8');
        $expressionLength = mb_strlen($expression, 'UTF-8');

        $position = 0;
        $pairedTokenStack = [];

        while ($position < $expressionLength) {
            foreach ($this->tokenClassNames as $tokenClassName) {
                /** @noinspection PhpUndefinedMethodInspection */
                $token = $tokenClassName::consumeIfNext($chars, $position, $pairedTokenStack);

                if (is_int($token)) {
                    // Insignificant token
                    continue 2;
                }

                if ($token instanceof TokenInterface) {
                    yield $token;
                    continue 2;
                }
            }

            throw new LexicalException(
                'Unexpected ' . $chars[$position],
                $position,
                $expression,
                1701474271
            );
        }

        $eofToken = EofToken::consumeIfNext($chars, $position, $pairedTokenStack);
        if (!$eofToken) {
            throw new LexicalException(
                'Unexpected EOF',
                $position,
                $expression,
                1701472409
            );
        }

        yield $eofToken;
    }
}