<?php
declare(strict_types=1);

namespace Ropi\JsonPathEvaluator\Context;

class PathEvaluationContext
{
    /**
     * @var array<string, node>
     */
    private array $nodes = [];
    private Node $rootNode;

    public function __construct(
        public readonly mixed $rootData,
        public readonly string $expression,
    ) {
        $this->rootNode = new Node($this->rootData, ['']);
    }

    public function getRootNode(): Node
    {
        return $this->rootNode;
    }

    public function getChildNode(Node $node, string|int $childPathSegment, mixed $nodeValue): Node
    {
        $nodeKey = spl_object_hash($node) . '#' . $childPathSegment;

        if (isset($this->nodes[$nodeKey])) {
            return $this->nodes[$nodeKey];
        }

        return $this->nodes[$nodeKey] = new Node($nodeValue, array_merge($node->pathSegments, [$childPathSegment]));
    }
}