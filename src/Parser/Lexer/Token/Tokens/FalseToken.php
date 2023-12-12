<?php
declare(strict_types=1);

namespace Ropi\JsonPathEvaluator\Parser\Lexer\Token\Tokens;

class FalseToken extends AbstractCharSequenceToken
{
    protected const CHAR_SEQUENCE = ['f', 'a', 'l', 's', 'e'];
}