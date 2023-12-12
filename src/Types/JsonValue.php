<?php
declare(strict_types=1);

namespace Ropi\JsonPathEvaluator\Types;

class JsonValue extends AbstractValueType
{
    public function __construct(
        private readonly mixed $value
    ) {}

    public function getValue(): mixed
    {
        return $this->value;
    }

    public function toBoolean(): bool
    {
        return (bool)$this->value;
    }

    public function toComparableValue(bool $equalityOnly = false): mixed
    {
        return $this->value;
    }
}