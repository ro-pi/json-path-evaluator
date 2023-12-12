<?php
declare(strict_types=1);

namespace Ropi\JsonPathEvaluator\Types;

class LogicalTrue extends AbstractLogicalType
{
    public function toBoolean(): bool
    {
        return true;
    }
}