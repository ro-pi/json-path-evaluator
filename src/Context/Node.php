<?php
declare(strict_types=1);

namespace Ropi\JsonPathEvaluator\Context;

class Node
{
    /**
     * @param array<string|int> $pathSegments
     */
    public function __construct(
        public readonly mixed $value,
        public readonly array $pathSegments
    ) {}
}