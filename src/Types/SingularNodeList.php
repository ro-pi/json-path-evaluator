<?php
declare(strict_types=1);

namespace Ropi\JsonPathEvaluator\Types;

use Ropi\JsonPathEvaluator\Context\Node;

class SingularNodeList extends NodeList
{
    public function addNode(Node $node): void
    {
        if ($this->getNodes()) {
            throw new \LogicException(
                'Can not add more than one node to singular node list.',
                1702172540
            );
        }

        parent::addNode($node);
    }

    public function toComparableValue(bool $equalityOnly = false): mixed
    {
        return $this->getFirstNodeValue();
    }
}