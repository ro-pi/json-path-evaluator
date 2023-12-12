<?php
declare(strict_types=1);

namespace Ropi\JsonPathEvaluator\Exception;

class JsonPathEvaluatorException extends \Exception
{
    public function __construct(
        string                  $message,
        private readonly int    $affectedPosition,
        private readonly string $expression,
        int                     $code = 0,
        ?\Throwable             $previous = null
    ) {
        parent::__construct(
            $message
            . ' at position '
            . $this->getAffectedPosition()
            . ': '
            . "\n"
            . $this->getAffectedSnippet(),
            $code,
            $previous
        );
    }

    public function getAffectedPosition(): int
    {
        return $this->affectedPosition;
    }

    public function getAffectedSnippet(): string
    {
        $snippet = '';

        $startPosition = $this->getAffectedPosition() - 16;
        if ($startPosition < 0) {
            $startPosition = 0;
        }

        $chars = mb_str_split($this->getExpression(), 1, 'UTF-8');

        for ($i = $startPosition; $i < ($startPosition + 32); $i++) {
            $char = $chars[$i] ?? null;
            if (!is_string($char)) {
                break;
            }

            if ($i === $this->getAffectedPosition()) {
                $snippet .= "\u{32D}"; // Arrow mark
            }

            $snippet .= $char;
        }

        if ($this->getAffectedPosition() >= count($chars)) {
            $snippet .= "\u{32D}";
        }

        return $snippet;
    }

    public function getExpression(): string
    {
        return $this->expression;
    }
}