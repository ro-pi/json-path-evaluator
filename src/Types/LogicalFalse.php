<?php
declare(strict_types=1);

namespace Ropi\JsonPathEvaluator\Types;

class LogicalFalse extends AbstractLogicalType
{
    public function toBoolean(): bool
    {
        return false;
    }
}