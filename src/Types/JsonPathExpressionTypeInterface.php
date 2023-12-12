<?php
declare(strict_types=1);

namespace Ropi\JsonPathEvaluator\Types;

interface JsonPathExpressionTypeInterface
{
    function toBoolean(): bool;
    function toComparableValue(bool $equalityOnly = false): mixed;
}