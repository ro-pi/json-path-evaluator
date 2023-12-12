<?php
declare(strict_types=1);

namespace Ropi\JsonPathEvaluator\Parser;

use Ropi\JsonPathEvaluator\Parser\Ast\NodeInterface;
use Ropi\JsonPathEvaluator\Parser\Exception\SyntaxException;
use Ropi\JsonPathEvaluator\Parser\Lexer\LexerInterface;
use Ropi\JsonPathEvaluator\Parser\Lexer\Token\TokenInterface;

abstract class AbstractParser implements ParserInterface
{
    private string $parsingExpression;

    /**
     * @var \Iterator<TokenInterface>
     */
    private \Iterator $tokens;

    /**
     * @var array<string, NodeInterface>
     */
    private array $cache = [];

    public function __construct(
        private readonly LexerInterface $lexer
    ) {}

    public function getLexer(): LexerInterface
    {
        return $this->lexer;
    }

    public function parse(string $expression): NodeInterface
    {
        if (isset($this->cache[$expression])) {
            return $this->cache[$expression];
        }

        $this->parsingExpression = $expression;
        $this->tokens = $this->getLexer()->tokenize($expression);

        return $this->cache[$expression] = $this->doParse();
    }

    abstract protected function doParse(): NodeInterface;

    protected function getCurrentToken(): TokenInterface
    {
        return $this->tokens->current();
    }

    protected function getParsingExpression(): string
    {
        return $this->parsingExpression;
    }

    /**
     * @throws SyntaxException
     */
    protected function consume(string $expectedTokenClassName, string ...$expectedTokenClassNames): TokenInterface
    {
        if (
            $expectedTokenClassName === $this->getCurrentToken()::class
            || in_array($this->getCurrentToken()::class, $expectedTokenClassNames)
        ) {
            $token = $this->getCurrentToken();
            $this->tokens->next();
            return $token;
        } else {
            throw new SyntaxException(
                'Unexpected token ' . $this->getCurrentToken()->value,
                $this->getCurrentToken()->position,
                $this->getParsingExpression(),
                1701481545
            );
        }
    }
}