<?php
declare(strict_types=1);

namespace Ropi\JsonPathEvaluator\Parser\Lexer\Token\Tokens;

class LeftBracketToken extends AbstractStartToken
{
    protected const EXPECTED_CHAR = '[';
}