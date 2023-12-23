<?php
declare(strict_types=1);

namespace Ropi\JsonPathEvaluator\Tests\Compliance;

use PHPUnit\Framework\TestCase;

abstract class AbstractJsonPathEvaluatorTestCase extends TestCase
{
    /**
     * @param array<scalar, mixed> $result
     */
    protected function assertJsonPathResult(array $result, string $expectedJson, string $message): void
    {
        $this->assertJsonStringEqualsJsonString((string)json_encode($result), $expectedJson, $message);
    }
}
