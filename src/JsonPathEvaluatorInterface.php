<?php
declare(strict_types=1);

namespace Ropi\JsonPathEvaluator;

use Ropi\JsonPathEvaluator\Exception\PathEvaluatorException;

interface JsonPathEvaluatorInterface
{
    /**
     * @param \stdClass|array<scalar, mixed> $data
     * @return array<scalar, mixed>
     * @throws PathEvaluatorException
     */
    function getValues(array|\stdClass $data, string $path): array;

    /**
     * @param \stdClass|array<scalar, mixed> $data
     * @return array<int, string>
     * @throws PathEvaluatorException
     */
    function getPaths(array|\stdClass $data, string $path): array;

    /**
     * @param \stdClass|array<scalar, mixed> $data
     * @param array<int, mixed> $values
     * @throws PathEvaluatorException
     */
    function setValues(array|\stdClass $data, string $path, array $values): void;

    /**
     * @param \stdClass|array<scalar, mixed> $data
     * @throws PathEvaluatorException
     */
    function deleteValues(array|\stdClass $data, string $path): void;
}