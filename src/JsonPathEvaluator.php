<?php
declare(strict_types=1);

namespace Ropi\JsonPathEvaluator;

use Ropi\JsonPathEvaluator\Context\Node;
use Ropi\JsonPathEvaluator\Context\PathEvaluationContext;
use Ropi\JsonPathEvaluator\Exception\EvaluationException;
use Ropi\JsonPathEvaluator\Exception\PathEvaluatorException;
use Ropi\JsonPathEvaluator\Parser\Ast\JsonPathExpression\AbstractJsonPathExpressionNode;
use Ropi\JsonPathEvaluator\Parser\Ast\JsonPathExpression\AbstractSegmentNode;
use Ropi\JsonPathEvaluator\Parser\Ast\JsonPathExpression\AbstractSelectorNode;
use Ropi\JsonPathEvaluator\Parser\Ast\JsonPathExpression\ArraySliceSelectorNode;
use Ropi\JsonPathEvaluator\Parser\Ast\JsonPathExpression\ChildSegmentNode;
use Ropi\JsonPathEvaluator\Parser\Ast\JsonPathExpression\DescendantSegmentNode;
use Ropi\JsonPathEvaluator\Parser\Ast\JsonPathExpression\FilterExpressionSelectorNode;
use Ropi\JsonPathEvaluator\Parser\Ast\JsonPathExpression\IndexSelectorNode;
use Ropi\JsonPathEvaluator\Parser\Ast\JsonPathExpression\NameSelectorNode;
use Ropi\JsonPathEvaluator\Parser\Ast\JsonPathExpression\NodeIdentifierNode;
use Ropi\JsonPathEvaluator\Parser\Ast\JsonPathExpression\UnionSelectorNode;
use Ropi\JsonPathEvaluator\Parser\Ast\JsonPathExpression\WildcardSelectorNode;
use Ropi\JsonPathEvaluator\Parser\Ast\LogicalExpression\AbstractBinaryOperatorNode;
use Ropi\JsonPathEvaluator\Parser\Ast\LogicalExpression\AbstractLogicalExpressionNode;
use Ropi\JsonPathEvaluator\Parser\Ast\LogicalExpression\AbstractUnaryOperatorNode;
use Ropi\JsonPathEvaluator\Parser\Ast\LogicalExpression\BooleanNode;
use Ropi\JsonPathEvaluator\Parser\Ast\LogicalExpression\EqualNode;
use Ropi\JsonPathEvaluator\Parser\Ast\LogicalExpression\FloatNode;
use Ropi\JsonPathEvaluator\Parser\Ast\LogicalExpression\FunctionNode;
use Ropi\JsonPathEvaluator\Parser\Ast\LogicalExpression\GreaterThanEqualNode;
use Ropi\JsonPathEvaluator\Parser\Ast\LogicalExpression\GreaterThanNode;
use Ropi\JsonPathEvaluator\Parser\Ast\LogicalExpression\IntegerNode;
use Ropi\JsonPathEvaluator\Parser\Ast\LogicalExpression\LessThanEqualNode;
use Ropi\JsonPathEvaluator\Parser\Ast\LogicalExpression\LessThanNode;
use Ropi\JsonPathEvaluator\Parser\Ast\LogicalExpression\LogicalAndNode;
use Ropi\JsonPathEvaluator\Parser\Ast\LogicalExpression\LogicalNotNode;
use Ropi\JsonPathEvaluator\Parser\Ast\LogicalExpression\LogicalOrNode;
use Ropi\JsonPathEvaluator\Parser\Ast\LogicalExpression\NullNode;
use Ropi\JsonPathEvaluator\Parser\Ast\LogicalExpression\StringNode;
use Ropi\JsonPathEvaluator\Parser\Ast\LogicalExpression\UnequalNode;
use Ropi\JsonPathEvaluator\Parser\Ast\NodeInterface;
use Ropi\JsonPathEvaluator\Parser\JsonPathParser;
use Ropi\JsonPathEvaluator\Parser\Lexer\JsonPathLexer;
use Ropi\JsonPathEvaluator\Parser\ParserInterface;
use Ropi\JsonPathEvaluator\Types\AbstractLogicalType;
use Ropi\JsonPathEvaluator\Types\AbstractNodesType;
use Ropi\JsonPathEvaluator\Types\AbstractValueType;
use Ropi\JsonPathEvaluator\Types\JsonPathExpressionTypeInterface;
use Ropi\JsonPathEvaluator\Types\JsonValue;
use Ropi\JsonPathEvaluator\Types\LogicalFalse;
use Ropi\JsonPathEvaluator\Types\LogicalTrue;
use Ropi\JsonPathEvaluator\Types\NodeList;
use Ropi\JsonPathEvaluator\Types\Nothing;
use Ropi\JsonPathEvaluator\Types\SingularNodeList;

class JsonPathEvaluator implements JsonPathEvaluatorInterface
{
    /**
     * @var array<string, \Closure>
     */
    private array $functions = [];

    public function __construct(
        private ?ParserInterface $parser = null
    ) {
        $this->registerFunction('length', $this->lengthFunction(...));
        $this->registerFunction('count', $this->countFunction(...));
        $this->registerFunction('match', $this->matchFunction(...));
        $this->registerFunction('search', $this->searchFunction(...));
        $this->registerFunction('value', $this->valueFunction(...));
    }

    public function getParser(): ParserInterface
    {
        return $this->parser ??= new JsonPathParser(new JsonPathLexer());
    }

    public function registerFunction(string $name, \Closure $function): void
    {
        if (isset($this->functions[$name])) {
            throw new \InvalidArgumentException(
                'A JSONPath function with name \'' . $name . '\' is already registered.',
                1701811057
            );
        }

        $this->functions[$name] = $function;
    }

    /**
     * @return \Closure[]
     */
    public function getFunctions(): array
    {
        return $this->functions;
    }

    /**
     * @throws \ReflectionException
     * @throws PathEvaluatorException
     */
    public function getValues(array|\stdClass $data, string $path): array
    {
        return $this->evaluate($data, $path)->getNodeValues();
    }

    /**
     * @throws PathEvaluatorException
     * @throws \ReflectionException
     */
    public function setValues(array|\stdClass $data, string $path, array $values): void
    {
        $nodeList = $this->evaluate($data, $path);

        if ($values) {
            $newValuePointer = 0;

            foreach ($nodeList->getNodes() as $node) {
                $currentData =& $data;

                foreach ($node->pathSegments as $pathSegment) {
                    if ($pathSegment === '') {
                        continue;
                    }

                    if (is_array($currentData)) {
                        $currentData =& $currentData[$pathSegment];
                    } elseif ($currentData instanceof \stdClass) {
                        $currentData =& $currentData->$pathSegment;
                    }
                }

                $currentData = $values[$newValuePointer++];
                if ($newValuePointer >= count($values)) {
                    $newValuePointer = 0;
                }
            }
        }
    }

    /**
     * @throws PathEvaluatorException
     * @throws \ReflectionException
     */
    public function deleteValues(array|\stdClass $data, string $path): void
    {
        $nodeList = $this->evaluate($data, $path);

        foreach ($nodeList->getNodes() as $node) {
            $currentData =& $data;
            $lastPathSegmentIndex = count($node->pathSegments) - 1;

            foreach ($node->pathSegments as $pathSegmentIndex => $pathSegment) {
                if ($pathSegment === '') {
                    continue;
                }

                if ($pathSegmentIndex >= $lastPathSegmentIndex) {
                    if (is_array($currentData)) {
                        unset($currentData[$pathSegment]);
                    } elseif ($currentData instanceof \stdClass) {
                        unset($currentData->$pathSegment);
                    }
                } else {
                    if (is_array($currentData)) {
                        $currentData =& $currentData[$pathSegment];
                    } elseif ($currentData instanceof \stdClass) {
                        $currentData =& $currentData->$pathSegment;
                    }
                }
            }
        }
    }

    /**
     * @throws \ReflectionException
     * @throws PathEvaluatorException
     */
    public function getPaths(array|\stdClass $data, string $path): array
    {
        $nodeList = $this->evaluate($data, $path);

        $paths = [];

        foreach ($nodeList->getNodes() as $node) {
            $path = '';

            foreach ($node->pathSegments as $pathSegment) {
                if ($pathSegment === '') {
                    $path .= '$';
                    continue;
                }

                $path .= '[';

                if (is_int($pathSegment)) {
                    $path .= $pathSegment;
                } else {
                    $name = str_replace(
                        [
                            '\\',
                            '\'',
                            "\u{0008}",
                            "\u{000C}",
                            "\u{000A}",
                            "\u{000D}",
                            "\u{0009}",
                        ],
                        [
                            '\\\\',
                            '\\\'',
                            '\b',
                            '\f',
                            '\n',
                            '\r',
                            '\t',
                        ],
                        $pathSegment
                    );

                    $name = preg_replace_callback(
                        '/[\x{0000}-\x{0007}\x{000b}\x{000e}-\x{000f}\x{0010}-\x{001f}]/u',
                        function(array $matches) {
                            $unicodeChar = mb_convert_encoding($matches[0], 'UTF-16BE', 'UTF-8');
                            $escapedUnicode = '';
                            for ($i = 0; $i < strlen($unicodeChar); $i += 2) {
                                $escapedUnicode .= '\\u' . sprintf('%04x', ord($unicodeChar[$i]) << 8 | ord($unicodeChar[$i + 1]));
                            }
                            return $escapedUnicode;
                        },
                        $name
                    );

                    $path .= '\'' . $name . '\'';
                }

                $path .= ']';
            }

            $paths[] = $path;
        }

        return $paths;
    }

    /**
     * @param \stdClass|array<scalar, mixed> $data
     * @throws \ReflectionException
     * @throws PathEvaluatorException
     */
    public function evaluate(array|\stdClass $data, string $path): NodeList
    {
        $pathEvaluationContext = new PathEvaluationContext($data, $path);

        return $this->evaluateJsonPathExpression(
            $this->getParser()->parse($path),
            $pathEvaluationContext->getRootNode(),
            $pathEvaluationContext
        );
    }

    /**
     * @throws EvaluationException
     * @throws \ReflectionException
     */
    protected function evaluateJsonPathExpression(
        NodeInterface $astNode,
        Node $currentNode,
        PathEvaluationContext $pathEvaluationContext
    ): NodeList {
        if ($astNode instanceof AbstractSelectorNode) {
            $resultNodeList = $this->evaluateSelector($astNode, $currentNode, $pathEvaluationContext);
        } elseif ($astNode instanceof AbstractSegmentNode) {
            $resultNodeList = $this->evaluateSegmentNode($astNode, $currentNode, $pathEvaluationContext);
        } else {
            $resultNodeList = new NodeList();
        }

        return $resultNodeList;
    }

    /**
     * @throws EvaluationException
     * @throws \ReflectionException
     */
    protected function evaluateSegmentNode(
        AbstractSegmentNode $astNode,
        Node $currentNode,
        PathEvaluationContext $pathEvaluationContext
    ): NodeList {
        $resultNodeList = new NodeList();

        $leftExpressionResult = $this->evaluateJsonPathExpression($astNode->leftNode, $currentNode, $pathEvaluationContext);

        if ($astNode instanceof ChildSegmentNode) {
            foreach ($leftExpressionResult->getNodes() as $node) {
                $resultNodeList = $this->combineNodeLists(
                    $resultNodeList,
                    $this->evaluateJsonPathExpression($astNode->rightNode, $node, $pathEvaluationContext)
                );
            }

            return $resultNodeList;
        } elseif ($astNode instanceof DescendantSegmentNode) {
            foreach ($leftExpressionResult->getNodes() as $node) {
                $resultNodeList = $this->combineNodeLists(
                    $resultNodeList,
                    $this->evaluateDescendantSegmentNode(
                        $astNode->rightNode,
                        $node,
                        $pathEvaluationContext
                    )
                );
            }
        }

        return $resultNodeList;
    }

    /**
     * @throws EvaluationException
     * @throws \ReflectionException
     */
    protected function evaluateDescendantSegmentNode(
        AbstractSegmentNode|AbstractSelectorNode $astNode,
        Node $currentNode,
        PathEvaluationContext $pathEvaluationContext
    ): NodeList {
        $resultNodeList = new NodeList();

        if ($astNode instanceof AbstractSegmentNode) {
            $resultNodeList = $this->combineNodeLists(
                $resultNodeList,
                $this->evaluateJsonPathExpression($astNode->rightNode, $currentNode, $pathEvaluationContext)
            );
        } else {
            $resultNodeList = $this->combineNodeLists(
                $resultNodeList,
                $this->evaluateSelector($astNode, $currentNode, $pathEvaluationContext)
            );
        }

        if ($currentNode->value instanceof \stdClass || is_array($currentNode->value)) {
            /* @phpstan-ignore-next-line */
            foreach ($currentNode->value as $segment => $childNodeValue) {
                if ($childNodeValue instanceof \stdClass || is_array($childNodeValue)) {
                    $resultNodeList = $this->combineNodeLists(
                        $resultNodeList,
                        $this->evaluateDescendantSegmentNode(
                            $astNode,
                            $pathEvaluationContext->getChildNode($currentNode, $segment, $childNodeValue),
                            $pathEvaluationContext
                        ),
                    );
                }
            }
        }

        return $resultNodeList;
    }

    /**
     * @throws EvaluationException
     * @throws \ReflectionException
     */
    protected function evaluateSelector(
        AbstractSelectorNode  $astNode,
        Node $currentNode,
        PathEvaluationContext $pathEvaluationContext
    ): NodeList {
        if ($astNode instanceof NodeIdentifierNode) {
            $resultNodeList = new SingularNodeList();

            if ($astNode->token->value === '$') {
                $resultNodeList->addNode($pathEvaluationContext->getRootNode());
            } else {
                $resultNodeList->addNode($currentNode);
            }

            return $resultNodeList;
        }

        if ($astNode instanceof NameSelectorNode || $astNode instanceof IndexSelectorNode) {
            $resultNodeList = new SingularNodeList();

            $segment = $astNode instanceof IndexSelectorNode
                ? (int)$astNode->token->value
                : $astNode->getUnquotedValue();

            if ($currentNode->value instanceof \stdClass) {
                $segment = (string)$segment;
                if (property_exists($currentNode->value, $segment)) {
                    $resultNodeList->addNode(
                        $pathEvaluationContext->getChildNode(
                            $currentNode,
                            $segment,
                            $currentNode->value->{$segment}
                        )
                    );
                }
            } elseif (is_array($currentNode->value)) {
                if (is_int($segment) && $segment < 0) {
                    if (count($currentNode->value) >= abs($segment)) {
                        $slice = array_slice($currentNode->value, $segment, 1, true);
                        if ($slice) {
                            $realSegment = array_key_first($slice);
                            $resultNodeList->addNode(
                                $pathEvaluationContext->getChildNode(
                                    $currentNode,
                                    $realSegment,
                                    $slice[$realSegment]
                                )
                            );
                        }
                    }
                } else {
                    if (array_key_exists($segment, $currentNode->value)) {
                        $resultNodeList->addNode(
                            $pathEvaluationContext->getChildNode(
                                $currentNode,
                                $segment,
                                $currentNode->value[$segment]
                            )
                        );
                    }
                }
            }

            return $resultNodeList;
        }

        $resultNodeList = new NodeList();

        if ($astNode instanceof WildcardSelectorNode) {
            if (is_array($currentNode->value) || $currentNode->value instanceof \stdClass) {
                /* @phpstan-ignore-next-line */
                foreach ($currentNode->value as $segment => $propertyValue) {
                    $resultNodeList->addNode(
                        $pathEvaluationContext->getChildNode(
                            $currentNode,
                            $segment,
                            $propertyValue
                        )
                    );
                }
            }

            return $resultNodeList;
        }

        if ($astNode instanceof ArraySliceSelectorNode) {
            if (is_array($currentNode->value) && is_int(array_key_first($currentNode->value))) {
                $start = is_string($astNode->start) ? (int)$astNode->start : null;
                $end = is_string($astNode->end) ? (int)$astNode->end : null;
                $step = is_string($astNode->step) ? (int)$astNode->step : 1;

                foreach ($this->sliceArray($currentNode->value, $start, $end, $step) as $segment => $nodeValue) {
                    $resultNodeList->addNode(
                        $pathEvaluationContext->getChildNode(
                            $currentNode,
                            $segment,
                            $nodeValue
                        )
                    );
                }
            }

            return $resultNodeList;
        }

        if ($astNode instanceof FilterExpressionSelectorNode) {
            if (is_array($currentNode->value) || $currentNode->value instanceof \stdClass) {
                /* @phpstan-ignore-next-line */
                foreach ($currentNode->value as $segment => $childNodeValue) {
                    $childNode = $pathEvaluationContext->getChildNode($currentNode, $segment, $childNodeValue);

                    $expressionResult = $this->evaluateLogicalExpressionNode(
                        $astNode->expressionNode,
                        $childNode,
                        $pathEvaluationContext
                    );

                    if ($expressionResult instanceof AbstractNodesType) {
                        if ($expressionResult->getNodes()) {
                            $resultNodeList->addNode($childNode);
                        }
                    } elseif ($expressionResult instanceof AbstractLogicalType) {
                        if ($expressionResult->toBoolean()) {
                            $resultNodeList->addNode($childNode);
                        }
                    }
                }
            }

            return $resultNodeList;
        }

        if ($astNode instanceof UnionSelectorNode) {
            foreach ($astNode->selectorNodes as $selectorNode) {
                $selectorNodeList = $this->evaluateSelector($selectorNode, $currentNode, $pathEvaluationContext);
                foreach ($selectorNodeList->getNodes() as $node) {
                    $resultNodeList->addNode($node);
                }
            }

            return $resultNodeList;
        }

        return $resultNodeList;
    }

    /**
     * @throws EvaluationException
     * @throws \ReflectionException
     */
    protected function evaluateLogicalExpressionNode(
        AbstractSegmentNode|AbstractSelectorNode|AbstractLogicalExpressionNode $astNode,
        Node $currentNode,
        PathEvaluationContext $pathEvaluationContext
    ): mixed {
        if ($astNode instanceof AbstractJsonPathExpressionNode) {
            return $this->evaluateJsonPathExpression($astNode, $currentNode, $pathEvaluationContext);
        }

        if ($astNode instanceof FunctionNode) {
            $function = $this->getFunctions()[$astNode->token->value] ?? null;
            if (!$function) {
                throw new EvaluationException(
                    $astNode->token->value . ' is not defined',
                    $astNode->token->position,
                    $pathEvaluationContext->expression,
                    1701811755
                );
            }

            $reflectionFunction = new \ReflectionFunction($function);
            $reflectionParameters = $reflectionFunction->getParameters();

            $arguments = [];

            foreach ($astNode->argumentNodes as $argumentIndex => $argumentNode) {
                $reflectionParameter = $reflectionParameters[$argumentIndex] ?? null;
                if (!$reflectionParameter) {
                    $arguments[] = null;
                    continue;
                }

                $argument = $this->evaluateLogicalExpressionNode($argumentNode, $currentNode, $pathEvaluationContext);

                $reflectionParameterType = $reflectionParameter->getType();
                if (!$reflectionParameterType) {
                    // Any parameter type is accepted
                    $arguments[] = $argument;
                    continue;
                }

                $parameterTypeNames = [];

                if ($reflectionParameterType instanceof \ReflectionNamedType) {
                    $parameterTypeNames[] = $reflectionParameterType->getName();
                } elseif ($reflectionParameterType instanceof \ReflectionUnionType) {
                    foreach ($reflectionParameterType->getTypes() as $type) {
                        if ($type instanceof \ReflectionNamedType) {
                            $parameterTypeNames[] = $type->getName();
                        }
                    }
                }

                if (!$parameterTypeNames) {
                    throw new \LogicException(
                        'Parameter #'
                        . ($argumentIndex + 1)
                        . ' of function '
                        . $astNode->token->value
                        . ' has an unsupported parameter type hint (only named types and union types are supported).',
                        1701897972
                    );
                }

                foreach ($parameterTypeNames as $parameterTypeName) {
                    if (
                        $parameterTypeName === AbstractValueType::class
                        || is_subclass_of($parameterTypeName, AbstractValueType::class)
                    ) {
                        if ($argument instanceof SingularNodeList) {
                            if (count($argument->getNodes()) === 1) {
                                $argument = new JsonValue($argument->getFirstNodeValue());
                            } elseif (!$argument->getNodes()) {
                                $argument = new Nothing();
                            }
                        } elseif (!$argument instanceof JsonPathExpressionTypeInterface) {
                            $argument = new JsonValue($argument);
                        }
                    } elseif (
                        $parameterTypeName === AbstractLogicalType::class
                        || is_subclass_of($parameterTypeName, AbstractLogicalType::class)
                    ) {
                        if (!$argument instanceof AbstractLogicalType && $argument instanceof JsonPathExpressionTypeInterface) {
                            $argument = $argument->toBoolean() ? new LogicalTrue() : new LogicalFalse();
                        }
                    }

                    if (is_object($argument)) {
                        /* @phpstan-ignore-next-line */
                        if (!($argument::class === $parameterTypeName || is_subclass_of($argument, $parameterTypeName))) {
                            continue; // not well-typed
                        }
                    } else {
                        if (gettype($argument) !== $parameterTypeName) {
                            continue; // not well-typed
                        }
                    }

                    $arguments[] = $argument;
                    break;
                }
            }

            if (count($reflectionParameters) !== count($arguments)) {
                return new Nothing();
            }

            $functionResult = $function(...$arguments);
            return $functionResult instanceof JsonValue ? $functionResult->getValue() : $functionResult;
        }

        if ($astNode instanceof IntegerNode) {
            return (int)$astNode->token->value;
        }

        if ($astNode instanceof FloatNode) {
            return (float)$astNode->token->value;
        }

        if ($astNode instanceof StringNode) {
            return $astNode->getUnquotedValue();
        }

        if ($astNode instanceof BooleanNode) {
            return $astNode->token->value === 'true';
        }

        if ($astNode instanceof NullNode) {
            return null;
        }

        if ($astNode instanceof AbstractBinaryOperatorNode) {
            return $this->evaluateBinaryOperatorNode($astNode, $currentNode, $pathEvaluationContext);
        }

        if ($astNode instanceof AbstractUnaryOperatorNode) {
            return $this->evaluateUnaryOperatorNode($astNode, $currentNode, $pathEvaluationContext);
        }

        throw new EvaluationException(
            'Can not evaluate unexpected node ' . $astNode->token->value . ' (' . get_class($astNode) . ')',
            $astNode->token->position,
            $pathEvaluationContext->expression,
            1701659747
        );
    }

    /**
     * @throws EvaluationException
     * @throws \ReflectionException
     */
    protected function evaluateUnaryOperatorNode(
        AbstractUnaryOperatorNode $astNode,
        Node $currentNode,
        PathEvaluationContext $pathEvaluationContext
    ): LogicalFalse|LogicalTrue|Nothing|bool {
        /** @noinspection PhpConditionAlreadyCheckedInspection */
        if ($astNode instanceof LogicalNotNode) {
            $expressionResult = $this->evaluateLogicalExpressionNode($astNode->termNode, $currentNode, $pathEvaluationContext);
            if ($expressionResult instanceof AbstractNodesType) {
                return $expressionResult->getNodes() ? new LogicalFalse() : new LogicalTrue();
            }

            if ($expressionResult instanceof LogicalTrue) {
                return new LogicalFalse();
            }

            if ($expressionResult instanceof LogicalFalse) {
                return new LogicalTrue();
            }

            if ($expressionResult instanceof Nothing) {
                return new LogicalTrue();
            }

            return $expressionResult ? new LogicalFalse() : new LogicalTrue();
        }

        throw new EvaluationException(
            'Unexpected unary operator node ' . $astNode->token->value,
            $astNode->token->position,
            $pathEvaluationContext->expression,
            1701659703
        );
    }

    /**
     * @throws EvaluationException
     * @throws \ReflectionException
     */
    protected function evaluateBinaryOperatorNode(
        AbstractBinaryOperatorNode $astNode,
        Node $currentNode,
        PathEvaluationContext $pathEvaluationContext
    ): AbstractLogicalType {
        $leftExpressionResult = $this->evaluateLogicalExpressionNode($astNode->leftNode, $currentNode, $pathEvaluationContext);
        $rightExpressionResult = $this->evaluateLogicalExpressionNode($astNode->rightNode, $currentNode, $pathEvaluationContext);

        if (
            $astNode instanceof EqualNode
            || $astNode instanceof UnequalNode
            || $astNode instanceof GreaterThanEqualNode
            || $astNode instanceof LessThanEqualNode
        ) {
            if ($leftExpressionResult instanceof JsonPathExpressionTypeInterface) {
                $leftExpressionResult = $leftExpressionResult->toComparableValue(true);
            }

            if ($rightExpressionResult instanceof JsonPathExpressionTypeInterface) {
                $rightExpressionResult = $rightExpressionResult->toComparableValue(true);
            }

            if (is_object($leftExpressionResult) && is_object($rightExpressionResult)) {
                $comparisonResult = ($leftExpressionResult == $rightExpressionResult);
            } elseif (
                (is_int($leftExpressionResult) || is_float($leftExpressionResult))
                && (is_int($rightExpressionResult) || is_float($rightExpressionResult))
            ) {
                $comparisonResult = ($leftExpressionResult == $rightExpressionResult);
            } else {
                $comparisonResult = ($leftExpressionResult === $rightExpressionResult);
            }

            if ($astNode instanceof EqualNode) {
                return $comparisonResult ? new LogicalTrue() : new LogicalFalse();
            }

            if ($astNode instanceof UnequalNode) {
                return $comparisonResult ? new LogicalFalse() : new LogicalTrue();
            }

            if ($comparisonResult) {
                return new LogicalTrue();
            }
        }

        if (
            $astNode instanceof GreaterThanEqualNode
            || $astNode instanceof GreaterThanNode
            || $astNode instanceof LessThanEqualNode
            || $astNode instanceof LessThanNode
        ) {
            if ($leftExpressionResult instanceof JsonPathExpressionTypeInterface) {
                $leftExpressionResult = $leftExpressionResult->toComparableValue();
            }

            if ($rightExpressionResult instanceof JsonPathExpressionTypeInterface) {
                $rightExpressionResult = $rightExpressionResult->toComparableValue();
            }

            if (
                !is_scalar($leftExpressionResult)
                || !is_scalar($rightExpressionResult)
                || (
                    gettype($leftExpressionResult) !== gettype($rightExpressionResult)
                    && !(
                        (is_int($leftExpressionResult) || is_float($leftExpressionResult))
                        && (is_int($rightExpressionResult) || is_float($rightExpressionResult))
                    )
                )
            ) {
                return new LogicalFalse();
            }

            if ($astNode instanceof LessThanNode || $astNode instanceof LessThanEqualNode) {
                return $leftExpressionResult < $rightExpressionResult ? new LogicalTrue() : new LogicalFalse();
            }

            return $leftExpressionResult > $rightExpressionResult ? new LogicalTrue() : new LogicalFalse();
        }

        if ($astNode instanceof LogicalAndNode || $astNode instanceof LogicalOrNode) {
            if ($leftExpressionResult instanceof JsonPathExpressionTypeInterface) {
                $leftExpressionResult = $leftExpressionResult->toBoolean();
            }

            if ($rightExpressionResult instanceof JsonPathExpressionTypeInterface) {
                $rightExpressionResult = $rightExpressionResult->toBoolean();
            }

            if ($astNode instanceof LogicalOrNode) {
                return $leftExpressionResult || $rightExpressionResult ? new LogicalTrue() : new LogicalFalse();
            }

            return $leftExpressionResult && $rightExpressionResult ? new LogicalTrue() : new LogicalFalse();
        }

        throw new EvaluationException(
            'Unexpected binary operator node ' . $astNode->token->value,
            $astNode->token->position,
            $pathEvaluationContext->expression,
            1701659368
        );
    }

    protected function lengthFunction(AbstractValueType $value): int|Nothing
    {
        if (!$value instanceof JsonValue) {
            return new Nothing();
        }

        $value = $value->getValue();

        if (is_string($value)) {
            return mb_strlen($value, 'UTF-8');
        }

        if (is_array($value)) {
            return count($value);
        }

        if (is_object($value)) {
            return count(get_object_vars($value));
        }

        return new Nothing();
    }

    protected function countFunction(AbstractNodesType $nodeList): int
    {
        return count($nodeList->getNodes());
    }

    protected function matchFunction(AbstractValueType $string, AbstractValueType $regexp): LogicalFalse|LogicalTrue
    {
        return $this->regexFunction($string, $regexp, true);
    }

    protected function searchFunction(AbstractValueType $string, AbstractValueType $regexp): LogicalFalse|LogicalTrue
    {
        return $this->regexFunction($string, $regexp, false);
    }

    protected function valueFunction(AbstractNodesType $nodeList): mixed
    {
        if (count($nodeList->getNodes()) !== 1) {
            return new Nothing();
        }

        return $nodeList->getFirstNodeValue();
    }

    /**
     * @param array<scalar, mixed> $array
     * @return array<int, mixed>
     */
    private function sliceArray(array $array, ?int $start, ?int $end, int $step): array
    {
        $result = [];

        if ($step === 0) {
            return $result;
        }

        $length = count($array);

        if ($start === null) {
            $start = ($step > 0) ? 0 : $length - 1;
        }

        if ($end === null) {
            $end = ($step > 0) ? $length : -$length - 1;
        }

        if ($start < 0) {
            $start = $length + $start;
        }

        if ($end < 0) {
            $end = $length + $end;
        }

        if ($step >= 0) {
            $lowerBound = min(max($start, 0), $length);
            $upperBound = min(max($end, 0), $length);
        } else {
            $upperBound = min(max($start, -1), $length - 1);
            $lowerBound = min(max($end, -1), $length - 1);
        }

        if ($step > 0) {
            $i = $lowerBound;
            while ($i < $upperBound) {
                $result[$i] = $array[$i];
                $i += $step;
            }
        } else {
            $i = $upperBound;
            while ($lowerBound < $i) {
                $result[$i] = $array[$i];
                $i += $step;
            }
        }

        return $result;
    }

    private function regexFunction(AbstractValueType $string, AbstractValueType $regexp, bool $fullMatch): LogicalFalse|LogicalTrue
    {
        if (!$string instanceof JsonValue || !$regexp instanceof JsonValue) {
            return new LogicalFalse();
        }

        $string = $string->getValue();
        $regexp = $regexp->getValue();

        if (!is_string($string) || !is_string($regexp)) {
            return new LogicalFalse();
        }

        if ($fullMatch) {
            return @preg_match('{^' . $regexp . '$}u', $string) ? new LogicalTrue() : new LogicalFalse();
        }

        return @preg_match('{' . $regexp . '}u', $string) ? new LogicalTrue() : new LogicalFalse();
    }

    private function combineNodeLists(NodeList $destinationNodeList, NodeList $sourceNodeList): NodeList
    {
        if (!$destinationNodeList->getNodes()) {
            return $sourceNodeList;
        }

        if ($destinationNodeList instanceof SingularNodeList && $sourceNodeList->getNodes()) {
            $singularNodeList = $destinationNodeList;
            $destinationNodeList = new NodeList();
            $destinationNodeList->addNode($singularNodeList->getNodes()[0]);
        }

        foreach ($sourceNodeList->getNodes() as $node) {
            $destinationNodeList->addNode($node);
        }

        return $destinationNodeList;
    }
}