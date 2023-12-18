<?php
declare(strict_types=1);

namespace Ropi\JsonPathEvaluator\Parser;

use Ropi\JsonPathEvaluator\Parser\Ast\JsonPathExpression\AbstractSegmentNode;
use Ropi\JsonPathEvaluator\Parser\Ast\JsonPathExpression\AbstractSelectorNode;
use Ropi\JsonPathEvaluator\Parser\Ast\JsonPathExpression\ArraySliceSelectorNode;
use Ropi\JsonPathEvaluator\Parser\Ast\JsonPathExpression\ChildSegmentNode;
use Ropi\JsonPathEvaluator\Parser\Ast\JsonPathExpression\DescendantSegmentNode;
use Ropi\JsonPathEvaluator\Parser\Ast\JsonPathExpression\FilterExpressionSelectorNode;
use Ropi\JsonPathEvaluator\Parser\Ast\JsonPathExpression\IndexSelectorNode;
use Ropi\JsonPathEvaluator\Parser\Ast\JsonPathExpression\NameSelectorNode;
use Ropi\JsonPathEvaluator\Parser\Ast\JsonPathExpression\NodeIdentifierNode;
use Ropi\JsonPathEvaluator\Parser\Ast\JsonPathExpression\UnionSelectorNode;
use Ropi\JsonPathEvaluator\Parser\Ast\JsonPathExpression\WildcardSelectorNode;
use Ropi\JsonPathEvaluator\Parser\Ast\LogicalExpression\AbstractLogicalExpressionNode;
use Ropi\JsonPathEvaluator\Parser\Ast\LogicalExpression\BooleanNode;
use Ropi\JsonPathEvaluator\Parser\Ast\LogicalExpression\EqualNode;
use Ropi\JsonPathEvaluator\Parser\Ast\LogicalExpression\FloatNode;
use Ropi\JsonPathEvaluator\Parser\Ast\LogicalExpression\FunctionNode;
use Ropi\JsonPathEvaluator\Parser\Ast\LogicalExpression\GreaterThanEqualNode;
use Ropi\JsonPathEvaluator\Parser\Ast\LogicalExpression\GreaterThanNode;
use Ropi\JsonPathEvaluator\Parser\Ast\LogicalExpression\IntegerNode;
use Ropi\JsonPathEvaluator\Parser\Ast\LogicalExpression\LessThanEqualNode;
use Ropi\JsonPathEvaluator\Parser\Ast\LogicalExpression\LessThanNode;
use Ropi\JsonPathEvaluator\Parser\Ast\LogicalExpression\LogicalAndNode;
use Ropi\JsonPathEvaluator\Parser\Ast\LogicalExpression\LogicalNotNode;
use Ropi\JsonPathEvaluator\Parser\Ast\LogicalExpression\LogicalOrNode;
use Ropi\JsonPathEvaluator\Parser\Ast\LogicalExpression\NullNode;
use Ropi\JsonPathEvaluator\Parser\Ast\LogicalExpression\StringNode;
use Ropi\JsonPathEvaluator\Parser\Ast\LogicalExpression\UnequalNode;
use Ropi\JsonPathEvaluator\Parser\Ast\NodeInterface;
use Ropi\JsonPathEvaluator\Parser\Exception\SyntaxException;
use Ropi\JsonPathEvaluator\Parser\Lexer\Token\Tokens\AsteriskToken;
use Ropi\JsonPathEvaluator\Parser\Lexer\Token\Tokens\AtToken;
use Ropi\JsonPathEvaluator\Parser\Lexer\Token\Tokens\ColonToken;
use Ropi\JsonPathEvaluator\Parser\Lexer\Token\Tokens\CommaToken;
use Ropi\JsonPathEvaluator\Parser\Lexer\Token\Tokens\DollarToken;
use Ropi\JsonPathEvaluator\Parser\Lexer\Token\Tokens\DoubleAmpersandToken;
use Ropi\JsonPathEvaluator\Parser\Lexer\Token\Tokens\DoubleEqualToken;
use Ropi\JsonPathEvaluator\Parser\Lexer\Token\Tokens\DoublePeriodToken;
use Ropi\JsonPathEvaluator\Parser\Lexer\Token\Tokens\DoublePipeToken;
use Ropi\JsonPathEvaluator\Parser\Lexer\Token\Tokens\EofToken;
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

class JsonPathParser extends AbstractParser
{
    private const TOKEN_EXPRESSION_BINARY_OPERATOR_NODE = [
        DoubleAmpersandToken::class => LogicalAndNode::class,
        DoubleEqualToken::class => EqualNode::class,
        DoublePipeToken::class => LogicalOrNode::class,
        GreaterThanToken::class => GreaterThanNode::class,
        GreaterThanEqualToken::class => GreaterThanEqualNode::class,
        LessThanToken::class => LessThanNode::class,
        LessThanEqualToken::class => LessThanEqualNode::class,
        UnequalToken::class => UnequalNode::class,
    ];

    /**
     * @throws \Ropi\JsonPathEvaluator\Parser\Exception\SyntaxException
     */
    protected function doParse(): NodeInterface
    {
        return $this->parseJsonPath(true);
    }

    /**
     * @throws SyntaxException
     */
    protected function parseJsonPath(bool $root): AbstractSelectorNode|AbstractSegmentNode
    {
        $node = $this->parseJsonPathExpression();

        if ($root) {
            $this->consume(EofToken::class);
        }

        return $node;
    }

    /**
     * @throws SyntaxException
     */
    protected function parseJsonPathExpression(): AbstractSelectorNode|AbstractSegmentNode
    {
        $left = $this->parseSimpleSelector();

        while (
            $this->getCurrentToken()::class === LeftBracketToken::class
            || $this->getCurrentToken()::class === PeriodToken::class
            || $this->getCurrentToken()::class === DoublePeriodToken::class
        ) {
            $token = $this->consume($this->getCurrentToken()::class);

            if ($token::class === LeftBracketToken::class) {
                $left = new ChildSegmentNode($token, $left, $this->parseBracketSelectors());
            } elseif ($token::class === DoublePeriodToken::class) {
                if ($this->getCurrentToken()::class === LeftBracketToken::class) {
                    $this->consume(LeftBracketToken::class);
                    $right = $this->parseBracketSelectors();
                } else {
                    $right = $this->parseSimpleSelector();
                }

                $left = new DescendantSegmentNode($token, $left, $right);
            } else {
                $left = new ChildSegmentNode($token, $left, $this->parseSimpleSelector());
            }
        }

        return $left;
    }

    /**
     * @throws SyntaxException
     */
    protected function parseBracketSelectors(): AbstractSelectorNode
    {
        $node = $this->parseBracketSelector();

        if ($this->getCurrentToken()::class === CommaToken::class) {
            $selectorNodes = [$node];

            while ($this->getCurrentToken()::class === CommaToken::class) {
                $commaToken = $this->consume(CommaToken::class);
                $selectorNodes[] = $this->parseBracketSelector();
            }

            /* @phpstan-ignore-next-line */
            if (isset($commaToken)) {
                $node = new UnionSelectorNode($commaToken, $selectorNodes);
            }
        }

        $this->consume(RightBracketToken::class);

        return $node;
    }

    /**
     * @throws \Ropi\JsonPathEvaluator\Parser\Exception\SyntaxException
     */
    protected function parseBracketSelector(): AbstractSelectorNode
    {
        if ($this->getCurrentToken()::class === IntegerToken::class) {
            /** @var IntegerToken $integerToken */
            $integerToken = $this->consume(IntegerToken::class);
        }

        if ($this->getCurrentToken()::class === ColonToken::class) {
            return $this->parseArraySliceSelector($integerToken ?? null);
        }

        if (isset($integerToken)) {
            return new IndexSelectorNode($integerToken);
        }

        if ($this->getCurrentToken()::class === QuestionMarkToken::class) {
            return new FilterExpressionSelectorNode(
                $this->consume(QuestionMarkToken::class),
                $this->parseLogicalExpression()
            );
        }

        return $this->parseSimpleSelector();
    }

    /**
     * @throws SyntaxException
     */
    protected function parseArraySliceSelector(?IntegerToken $startToken = null): ArraySliceSelectorNode
    {
        $colonToken = $this->consume(ColonToken::class);

        if ($this->getCurrentToken()::class === IntegerToken::class) {
            $endToken = $this->consume(IntegerToken::class);
        }

        if ($this->getCurrentToken()::class === ColonToken::class) {
            $this->consume(ColonToken::class);
        }

        if ($this->getCurrentToken()::class === IntegerToken::class) {
            $stepToken = $this->consume(IntegerToken::class);
        }

        $token = $startToken ?? $endToken ?? $stepToken ?? $colonToken;

        return new ArraySliceSelectorNode(
            $token,
            $startToken?->value,
            isset($endToken) ? $endToken->value : null,
            isset($stepToken) ? $stepToken->value : null,
        );
    }

    /**
     * @throws SyntaxException
     */
    protected function parseSimpleSelector(): AbstractSelectorNode
    {
        if ($this->getCurrentToken()::class === DollarToken::class) {
            return new NodeIdentifierNode($this->consume(DollarToken::class));
        }

        if ($this->getCurrentToken()::class === AtToken::class) {
            return new NodeIdentifierNode($this->consume(AtToken::class));
        }

        if ($this->getCurrentToken()::class === IntegerToken::class) {
            return new IndexSelectorNode($this->consume(IntegerToken::class));
        }

        $token = $this->consume(
            IdentifierToken::class,
            StringToken::class,
            AsteriskToken::class,
        );

        if ($token::class === AsteriskToken::class) {
            return new WildcardSelectorNode($token);
        }

        if ($token::class === StringToken::class) {
            return new NameSelectorNode($token, $token->value[0]);
        }

        return new NameSelectorNode($token, '');
    }

    /**
     * @throws SyntaxException
     */
    protected function parseLogicalExpression(int $precedence = 0): AbstractSegmentNode|AbstractSelectorNode|AbstractLogicalExpressionNode
    {
        $left = $this->parseLogicalExpressionTerm();

        while ($nodeClass = (self::TOKEN_EXPRESSION_BINARY_OPERATOR_NODE[$this->getCurrentToken()::class] ?? null)) {
            $operatorPrecedence = (int)constant($nodeClass . '::PRECEDENCE');
            if ($operatorPrecedence < $precedence) {
                break;
            }

            $leftToRight = constant($nodeClass . '::ASSOCIATIVITY_LEFT_TO_RIGHT');
            $token = $this->consume($this->getCurrentToken()::class);

            /* @phpstan-ignore-next-line */
            $right = $this->parseLogicalExpression($leftToRight ? $operatorPrecedence + 1 : $operatorPrecedence);
            $left = new $nodeClass($token, $left, $right);
        }

        return $left;
    }

    /**
     * @throws SyntaxException
     */
    protected function parseLogicalExpressionTerm(): AbstractSelectorNode|AbstractSegmentNode|AbstractLogicalExpressionNode
    {
        if ($this->getCurrentToken()::class === LeftParenthesisToken::class) {
            $this->consume(LeftParenthesisToken::class);
            $node = $this->parseLogicalExpression();
            $this->consume(RightParenthesisToken::class);
            return $node;
        }

        if ($this->getCurrentToken()::class === ExclamationMarkToken::class) {
            $token = $this->consume(ExclamationMarkToken::class);
            return new LogicalNotNode($token, $this->parseLogicalExpression(LogicalNotNode::PRECEDENCE));
        }

        if ($this->getCurrentToken()::class === IdentifierToken::class) {
            return $this->parseFunction();
        }

        if (
            $this->getCurrentToken()::class === DollarToken::class
            || $this->getCurrentToken()::class === AtToken::class
        ) {
            return $this->parseJsonPath(false);
        }

        $token = $this->consume(
            IntegerToken::class,
            FloatToken::class,
            StringToken::class,
            TrueToken::class,
            FalseToken::class,
            NullToken::class,
        );

        if ($token::class === IntegerToken::class) {
            return new IntegerNode($token);
        }

        if ($token::class === FloatToken::class) {
            return new FloatNode($token);
        }

        if ($token::class === TrueToken::class || $token::class === FalseToken::class) {
            return new BooleanNode($token);
        }

        if ($token::class === NullToken::class) {
            return new NullNode($token);
        }

        return new StringNode($token, $token->value[0]);
    }

    /**
     * @throws SyntaxException
     */
    protected function parseFunction(): FunctionNode
    {
        $identifierToken = $this->consume(IdentifierToken::class);

        $this->consume(LeftParenthesisToken::class);

        $argumentNodes = [];

        if ($this->getCurrentToken()::class !== RightParenthesisToken::class) {
            $argumentNodes[] = $this->parseFunctionArgument();

            while ($this->getCurrentToken()::class === CommaToken::class) {
                $this->consume(CommaToken::class);
                $argumentNodes[] = $this->parseFunctionArgument();
            }
        }

        $this->consume(RightParenthesisToken::class);

        return new FunctionNode($identifierToken, $argumentNodes);
    }

    /**
     * @throws SyntaxException
     */
    protected function parseFunctionArgument(): AbstractSegmentNode|AbstractSelectorNode|AbstractLogicalExpressionNode
    {
        if ($this->getCurrentToken()::class === IdentifierToken::class) {
            return $this->parseFunction();
        }

        return $this->parseLogicalExpression();
    }
}