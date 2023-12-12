<?php
declare(strict_types=1);

namespace Ropi\JsonPathEvaluator\Parser\Lexer\Token;

abstract class AbstractToken implements TokenInterface
{
    final protected function __construct(
        public readonly int $position,
        public readonly string $value,
    ) {}
}