<?php
declare(strict_types=1);

namespace Ropi\JsonPathEvaluator\Types;

use Ropi\JsonPathEvaluator\Context\Node;

abstract class AbstractNodesType implements JsonPathExpressionTypeInterface
{
    /**
     * @var array<Node> $nodes
     */
    private array $nodes = [];

    public function addNode(Node $node): void
    {
        $this->nodes[] = $node;
    }

    /**
     * @return array<Node>
     */
    public function getNodes(): array
    {
        return $this->nodes;
    }

    /**
     * @return list<mixed>
     */
    public function getNodeValues(): array
    {
        $values = [];

        foreach ($this->nodes as $node) {
            $values[] = $node->value;
        }

        return $values;
    }

    public function getFirstNodeValue(): mixed
    {
        if (!$this->nodes) {
            return new Nothing();
        }

        return $this->nodes[array_key_first($this->nodes)]->value;
    }

    public function toBoolean(): bool
    {
        return (bool)$this->nodes;
    }

    public function toComparableValue(bool $equalityOnly = false): mixed
    {
        return $this->nodes ?: new Nothing();
    }
}