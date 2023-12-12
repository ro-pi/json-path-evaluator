<?php
declare(strict_types=1);

namespace Ropi\JsonPathEvaluator\Parser\Lexer\Token;

use Ropi\JsonPathEvaluator\Parser\Lexer\Exception\LexicalException;

/**
 * @property int $position
 * @property string $value
 */
interface TokenInterface
{
    /**
     * @param string[] $chars
     * @param TokenInterface[] $pairedTokenStack
     * @throws LexicalException
     */
    static function consumeIfNext(array $chars, int &$position, array &$pairedTokenStack): static|null|int;
}