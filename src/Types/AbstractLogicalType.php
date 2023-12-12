<?php
declare(strict_types=1);

namespace Ropi\JsonPathEvaluator\Types;

abstract class AbstractLogicalType implements JsonPathExpressionTypeInterface
{
    public function toComparableValue(bool $equalityOnly = false): static|bool
    {
        return $equalityOnly ? $this : $this->toBoolean();
    }
}