<?php
declare(strict_types=1);

namespace Ropi\JsonPathEvaluator\Context;

use Ropi\JsonPathEvaluator\NonExistentPathBehavior;

class EvaluationContext
{
    /**
     * @var array<string, Node>
     */
    private array $nodes = [];
    private readonly Node $rootNode;

    /**
     * @var array<string, bool>
     */
    private array $dynamicNodePaths;

    public function __construct(
        public mixed &$rootData,
        public readonly string $expression,
        public readonly NonExistentPathBehavior $nonExistentPathBehavior,
    ) {
        $this->rootNode = new Node($this->rootData, ['']);
    }

    public function getRootNode(): Node
    {
        return $this->rootNode;
    }

    public function getChildNode(Node $node, string|int $childPathSegment, mixed &$nodeValue, bool $createdDynamically = false): Node
    {
        $nodeKey = spl_object_hash($node) . '#' . $childPathSegment;

        if (isset($this->nodes[$nodeKey])) {
            return $this->nodes[$nodeKey];
        }

        $pathSegments = array_merge($node->pathSegments, [$childPathSegment]);

        if ($createdDynamically) {
            $this->dynamicNodePaths[implode('|', $pathSegments)] = true;
        }

        return $this->nodes[$nodeKey] = new Node($nodeValue, $pathSegments);
    }

    public function nodeCreatedDynamically(Node $node): bool
    {
        return isset($this->dynamicNodePaths[implode('|', $node->pathSegments)]);
    }
}