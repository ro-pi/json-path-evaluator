<?php
declare(strict_types=1);

namespace Ropi\JsonPathEvaluator\Parser\Lexer\Token\Tokens;

class RightParenthesisToken extends AbstractEndToken
{
    protected const EXPECTED_CHAR = ')';
    protected const CORRESPONDING_START_TOKEN_CLASS = LeftParenthesisToken::class;
}