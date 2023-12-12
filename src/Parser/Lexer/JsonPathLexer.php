<?php
declare(strict_types=1);

namespace Ropi\JsonPathEvaluator\Parser\Lexer;

use Ropi\JsonPathEvaluator\Parser\Lexer\Token\Tokens\AsteriskToken;
use Ropi\JsonPathEvaluator\Parser\Lexer\Token\Tokens\AtToken;
use Ropi\JsonPathEvaluator\Parser\Lexer\Token\Tokens\ColonToken;
use Ropi\JsonPathEvaluator\Parser\Lexer\Token\Tokens\CommaToken;
use Ropi\JsonPathEvaluator\Parser\Lexer\Token\Tokens\DollarToken;
use Ropi\JsonPathEvaluator\Parser\Lexer\Token\Tokens\DoubleAmpersandToken;
use Ropi\JsonPathEvaluator\Parser\Lexer\Token\Tokens\DoubleEqualToken;
use Ropi\JsonPathEvaluator\Parser\Lexer\Token\Tokens\DoublePeriodToken;
use Ropi\JsonPathEvaluator\Parser\Lexer\Token\Tokens\DoublePipeToken;
use Ropi\JsonPathEvaluator\Parser\Lexer\Token\Tokens\ExclamationMarkToken;
use Ropi\JsonPathEvaluator\Parser\Lexer\Token\Tokens\FalseToken;
use Ropi\JsonPathEvaluator\Parser\Lexer\Token\Tokens\FloatToken;
use Ropi\JsonPathEvaluator\Parser\Lexer\Token\Tokens\GreaterThanEqualToken;
use Ropi\JsonPathEvaluator\Parser\Lexer\Token\Tokens\GreaterThanToken;
use Ropi\JsonPathEvaluator\Parser\Lexer\Token\Tokens\IdentifierToken;
use Ropi\JsonPathEvaluator\Parser\Lexer\Token\Tokens\IntegerToken;
use Ropi\JsonPathEvaluator\Parser\Lexer\Token\Tokens\LeftBracketToken;
use Ropi\JsonPathEvaluator\Parser\Lexer\Token\Tokens\LeftParenthesisToken;
use Ropi\JsonPathEvaluator\Parser\Lexer\Token\Tokens\LessThanEqualToken;
use Ropi\JsonPathEvaluator\Parser\Lexer\Token\Tokens\LessThanToken;
use Ropi\JsonPathEvaluator\Parser\Lexer\Token\Tokens\NullToken;
use Ropi\JsonPathEvaluator\Parser\Lexer\Token\Tokens\PeriodToken;
use Ropi\JsonPathEvaluator\Parser\Lexer\Token\Tokens\QuestionMarkToken;
use Ropi\JsonPathEvaluator\Parser\Lexer\Token\Tokens\RightBracketToken;
use Ropi\JsonPathEvaluator\Parser\Lexer\Token\Tokens\RightParenthesisToken;
use Ropi\JsonPathEvaluator\Parser\Lexer\Token\Tokens\StringToken;
use Ropi\JsonPathEvaluator\Parser\Lexer\Token\Tokens\TrueToken;
use Ropi\JsonPathEvaluator\Parser\Lexer\Token\Tokens\UnequalToken;
use Ropi\JsonPathEvaluator\Parser\Lexer\Token\Tokens\WhitespaceToken;

class JsonPathLexer extends AbstractLexer
{
    protected array $tokenClassNames = [
        WhitespaceToken::class,

        FloatToken::class,
        IntegerToken::class,
        FalseToken::class,
        TrueToken::class,
        NullToken::class,
        StringToken::class,
        IdentifierToken::class,

        DoubleAmpersandToken::class,
        DoubleEqualToken::class,
        DoublePeriodToken::class,
        DoublePipeToken::class,
        GreaterThanEqualToken::class,
        GreaterThanToken::class,
        LessThanEqualToken::class,
        LessThanToken::class,
        UnequalToken::class,

        AsteriskToken::class,
        AtToken::class,
        ColonToken::class,
        CommaToken::class,
        DollarToken::class,
        ExclamationMarkToken::class,
        PeriodToken::class,
        QuestionMarkToken::class,

        LeftBracketToken::class,
        RightBracketToken::class,
        LeftParenthesisToken::class,
        RightParenthesisToken::class,
    ];
}