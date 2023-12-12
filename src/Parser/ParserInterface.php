<?php
declare(strict_types=1);

namespace Ropi\JsonPathEvaluator\Parser;

use Ropi\JsonPathEvaluator\Parser\Ast\NodeInterface;
use Ropi\JsonPathEvaluator\Parser\Exception\ParseException;

interface ParserInterface
{
    /**
     * @throws ParseException
     */
    function parse(string $expression): NodeInterface;
}