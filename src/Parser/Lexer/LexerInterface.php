<?php
declare(strict_types=1);

namespace Ropi\JsonPathEvaluator\Parser\Lexer;

use Ropi\JsonPathEvaluator\Parser\Lexer\Exception\LexicalException;
use Ropi\JsonPathEvaluator\Parser\Lexer\Token\TokenInterface;

interface LexerInterface
{
    /**
     * @return \Iterator<TokenInterface>
     * @throws LexicalException
     */
    function tokenize(string $expression): \Iterator;
}