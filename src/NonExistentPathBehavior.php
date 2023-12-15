<?php
declare(strict_types=1);

namespace Ropi\JsonPathEvaluator;

enum NonExistentPathBehavior
{
    case Skip;
    case CreateArray;
    case CreateStdClass;
}