<?php
declare(strict_types=1);

namespace Ropi\JsonPathEvaluator\Types;

class Nothing extends AbstractValueType
{
    public function toBoolean(): bool
    {
        return false;
    }

    public function toComparableValue(bool $equalityOnly = false): Nothing
    {
        return $this;
    }
}