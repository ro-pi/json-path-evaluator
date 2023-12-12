<?php
declare(strict_types=1);

namespace Ropi\JsonPathEvaluator\Parser\Lexer\Token\Tokens;

class LeftParenthesisToken extends AbstractStartToken
{
    protected const EXPECTED_CHAR = '(';
}