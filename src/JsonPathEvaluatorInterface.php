<?php
declare(strict_types=1);

namespace Ropi\JsonPathEvaluator;

use Ropi\JsonPathEvaluator\Exception\JsonPathEvaluatorException;

interface JsonPathEvaluatorInterface
{
    /**
     * @param \stdClass|array<scalar, mixed> $data
     * @return list<mixed>
     * @throws JsonPathEvaluatorException
     */
    function getValues(array|\stdClass $data, string $path): array;

    /**
     * @param \stdClass|array<scalar, mixed> $data
     * @return list<string>
     * @throws JsonPathEvaluatorException
     */
    function getPaths(array|\stdClass $data, string $path): array;

    /**
     * @param \stdClass|array<scalar, mixed> $data
     * @param list<mixed> $values
     * @throws JsonPathEvaluatorException
     */
    function setValues(
        array|\stdClass &$data,
        string $path,
        array $values,
        NonExistentPathBehavior $nonExistentPathBehavior = NonExistentPathBehavior::Skip
    ): int;

    /**
     * @param \stdClass|array<scalar, mixed> $data
     * @throws JsonPathEvaluatorException
     */
    function deletePaths(array|\stdClass &$data, string $path): int;
}