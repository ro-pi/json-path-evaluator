<?php
declare(strict_types=1);

namespace Ropi\JsonPathEvaluator\Parser\Ast;

use Ropi\JsonPathEvaluator\Parser\Lexer\Token\TokenInterface;

abstract class AbstractNode implements NodeInterface
{
    public function __construct(
        public readonly TokenInterface $token,
    ) {}
}