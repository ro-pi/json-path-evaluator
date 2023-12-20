<?php
declare(strict_types=1);

namespace Ropi\JsonPathEvaluator;

use Ropi\JsonPathEvaluator\Context\Node;
use Ropi\JsonPathEvaluator\Context\EvaluationContext;
use Ropi\JsonPathEvaluator\Exception\JsonPathEvaluationException;
use Ropi\JsonPathEvaluator\Exception\JsonPathEvaluatorException;
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
     * @throws JsonPathEvaluatorException
     */
    public function getValues(array|\stdClass $data, string $path): array
    {
        return $this->evaluate($data, $path, NonExistentPathBehavior::Skip)->getNodeValues();
    }

    /**
     * @throws JsonPathEvaluatorException
     * @throws \ReflectionException
     */
    public function setValues(
        array|\stdClass &$data,
        string $path,
        array $values,
        NonExistentPathBehavior $nonExistentPathBehavior = NonExistentPathBehavior::Skip
    ): int {
        $numValuesSet = 0;
        $nodeList = $this->evaluate($data, $path, $nonExistentPathBehavior);

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

            if ($values) {
                $currentData = $values[$newValuePointer++];
                if ($newValuePointer >= count($values)) {
                    $newValuePointer = 0;
                }

                $numValuesSet++;
            } elseif ($nonExistentPathBehavior !== NonExistentPathBehavior::Skip) {
                foreach ($nodeList->getNodes() as $n) {
                    var_dump('$' . implode('.', $n->pathSegments));
                }
                $currentData = null;
                $numValuesSet++;
            }
        }

        return $numValuesSet;
    }

    /**
     * @throws JsonPathEvaluatorException
     * @throws \ReflectionException
     */
    public function deletePaths(array|\stdClass &$data, string $path): int
    {
        $numPathsDeleted = 0;
        $nodeList = $this->evaluate($data, $path, NonExistentPathBehavior::Skip);

        foreach ($nodeList->getNodes() as $node) {
            $currentData =& $data;
            $lastPathSegmentIndex = count($node->pathSegments) - 1;

            foreach ($node->pathSegments as $pathSegmentIndex => $pathSegment) {
                if ($pathSegment === '') {
                    if ($pathSegmentIndex >= $lastPathSegmentIndex) {
                        $data = null;
                        $numPathsDeleted++;
                        break;
                    }

                    continue;
                }

                if ($pathSegmentIndex >= $lastPathSegmentIndex) {
                    if (is_array($currentData)) {
                        unset($currentData[$pathSegment]);
                    } elseif ($currentData instanceof \stdClass) {
                        unset($currentData->$pathSegment);
                    }

                    $numPathsDeleted++;
                } else {
                    if (is_array($currentData)) {
                        $currentData =& $currentData[$pathSegment];
                    } elseif ($currentData instanceof \stdClass) {
                        $currentData =& $currentData->$pathSegment;
                    }
                }
            }
        }

        return $numPathsDeleted;
    }

    /**
     * @throws \ReflectionException
     * @throws JsonPathEvaluatorException
     */
    public function getPaths(array|\stdClass $data, string $path): array
    {
        $nodeList = $this->evaluate($data, $path, NonExistentPathBehavior::Skip);

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
     * @throws JsonPathEvaluatorException
     */
    public function evaluate(
        array|\stdClass &$data,
        string $path,
        NonExistentPathBehavior $nonExistentPathBehavior
    ): NodeList {
        $evaluationContext = new EvaluationContext($data, $path, $nonExistentPathBehavior);

        return $this->evaluateJsonPathExpression(
            $this->getParser()->parse($path),
            $evaluationContext->getRootNode(),
            $evaluationContext
        );
    }

    /**
     * @throws JsonPathEvaluationException
     * @throws \ReflectionException
     */
    protected function evaluateJsonPathExpression(
        NodeInterface $astNode,
        Node $currentNode,
        EvaluationContext $evaluationContext
    ): NodeList {
        if ($astNode instanceof AbstractSelectorNode) {
            $resultNodeList = $this->evaluateSelector($astNode, $currentNode, $evaluationContext);
        } elseif ($astNode instanceof AbstractSegmentNode) {
            $resultNodeList = $this->evaluateSegmentNode($astNode, $currentNode, $evaluationContext);
        } else {
            $resultNodeList = new NodeList();
        }

        return $resultNodeList;
    }

    /**
     * @throws JsonPathEvaluationException
     * @throws \ReflectionException
     */
    protected function evaluateSegmentNode(
        AbstractSegmentNode $astNode,
        Node $currentNode,
        EvaluationContext $evaluationContext
    ): NodeList {
        $resultNodeList = new NodeList();

        $leftExpressionResult = $this->evaluateJsonPathExpression($astNode->leftNode, $currentNode, $evaluationContext);

        if ($astNode instanceof ChildSegmentNode) {
            foreach ($leftExpressionResult->getNodes() as $node) {
                $resultNodeList = $this->combineNodeLists(
                    $resultNodeList,
                    $this->evaluateJsonPathExpression($astNode->rightNode, $node, $evaluationContext)
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
                        $evaluationContext
                    )
                );
            }
        }

        return $resultNodeList;
    }

    /**
     * @throws JsonPathEvaluationException
     * @throws \ReflectionException
     */
    protected function evaluateDescendantSegmentNode(
        AbstractSegmentNode|AbstractSelectorNode $astNode,
        Node $currentNode,
        EvaluationContext $evaluationContext
    ): NodeList {
        $resultNodeList = new NodeList();

        if ($astNode instanceof AbstractSegmentNode) {
            $resultNodeList = $this->combineNodeLists(
                $resultNodeList,
                $this->evaluateJsonPathExpression($astNode->rightNode, $currentNode, $evaluationContext)
            );
        } else {
            $resultNodeList = $this->combineNodeLists(
                $resultNodeList,
                $this->evaluateSelector($astNode, $currentNode, $evaluationContext)
            );
        }

        if ($currentNode->value instanceof \stdClass) {
            $currentNodeValue = get_object_vars($currentNode->value);
        } elseif (is_array($currentNode->value)) {
            $currentNodeValue = $currentNode->value;
        }

        if (isset($currentNodeValue)) {
            foreach ($currentNodeValue as $segment => &$childNodeValue) {
                if ($childNodeValue instanceof \stdClass || is_array($childNodeValue)) {
                    $childNode = $evaluationContext->getChildNode($currentNode, $segment, $childNodeValue);

                    if (
                        $evaluationContext->nonExistentPathBehavior !== NonExistentPathBehavior::Skip
                        && $evaluationContext->nodeCreatedDynamically($childNode)
                    ) {
                        continue;
                    }

                    $resultNodeList = $this->combineNodeLists(
                        $resultNodeList,
                        $this->evaluateDescendantSegmentNode(
                            $astNode,
                            $childNode,
                            $evaluationContext
                        ),
                    );
                }
            }
        }

        return $resultNodeList;
    }

    /**
     * @throws JsonPathEvaluationException
     * @throws \ReflectionException
     */
    protected function evaluateSelector(
        AbstractSelectorNode  $astNode,
        Node $currentNode,
        EvaluationContext $evaluationContext
    ): NodeList {
        if ($astNode instanceof NodeIdentifierNode) {
            $resultNodeList = new SingularNodeList();

            if ($astNode->token->value === '$') {
                $resultNodeList->addNode($evaluationContext->getRootNode());
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

            if (
                $evaluationContext->nonExistentPathBehavior === NonExistentPathBehavior::CreateStdClass
                && is_int($segment)
                && $currentNode->value instanceof \stdClass
                && !get_object_vars($currentNode->value)
                && $evaluationContext->nodeCreatedDynamically($currentNode)
            ) {
                $currentNode->value = [];
            }

            if ($currentNode->value instanceof \stdClass) {
                $segment = (string)$segment;
                $createdDynamically = false;

                if (!property_exists($currentNode->value, $segment)) {
                    if ($evaluationContext->nonExistentPathBehavior === NonExistentPathBehavior::Skip) {
                        return $resultNodeList;
                    }

                    $currentNode->value->{$segment} = $evaluationContext->nonExistentPathBehavior === NonExistentPathBehavior::CreateArray
                        ? []
                        : new \stdClass();

                    $createdDynamically = true;
                }

                $resultNodeList->addNode(
                    $evaluationContext->getChildNode(
                        $currentNode,
                        $segment,
                        $currentNode->value->{$segment},
                        $createdDynamically
                    )
                );
            } elseif (is_array($currentNode->value)) {
                if (is_int($segment) && $segment < 0) {
                    if (count($currentNode->value) >= abs($segment)) {
                        [$segment] = $this->calculateArraySliceBounds($segment, $segment + 1, 1, count($currentNode->value));
                    } elseif ($evaluationContext->nonExistentPathBehavior !== NonExistentPathBehavior::Skip) {
                        $segment = 0;
                    }
                }

                $createdDynamically = false;

                if (!array_key_exists($segment, $currentNode->value)) {
                    if ($evaluationContext->nonExistentPathBehavior === NonExistentPathBehavior::Skip) {
                        return $resultNodeList;
                    }

                    $createDynamically = !$currentNode->value
                        || (is_int(array_key_first($currentNode->value)) && is_int($segment));

                    if (!$createDynamically) {
                        return $resultNodeList;
                    }

                    $currentNode->value[$segment] = $evaluationContext->nonExistentPathBehavior === NonExistentPathBehavior::CreateArray
                        ? []
                        : new \stdClass();

                    $createdDynamically = true;
                }

                $resultNodeList->addNode(
                    $evaluationContext->getChildNode(
                        $currentNode,
                        $segment,
                        $currentNode->value[$segment],
                        $createdDynamically
                    )
                );
            }

            return $resultNodeList;
        }

        $resultNodeList = new NodeList();

        if ($astNode instanceof WildcardSelectorNode) {
            if ($currentNode->value instanceof \stdClass) {
                $currentNodeValue = get_object_vars($currentNode->value);
            } elseif (is_array($currentNode->value)) {
                $currentNodeValue = $currentNode->value;
            }

            if (isset($currentNodeValue)) {
                foreach ($currentNodeValue as $segment => &$propertyValue) {
                    $resultNodeList->addNode(
                        $evaluationContext->getChildNode(
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
            if (is_array($currentNode->value) && (!$currentNode->value || is_int(array_key_first($currentNode->value)))) {
                $start = is_string($astNode->start) ? (int)$astNode->start : null;
                $end = is_string($astNode->end) ? (int)$astNode->end : null;
                $step = is_string($astNode->step) ? (int)$astNode->step : 1;
                $slice = $this->sliceArray($currentNode->value, $start, $end, $step);

                $createdDynamically = false;

                if (!$slice && $evaluationContext->nonExistentPathBehavior !== NonExistentPathBehavior::Skip) {
                    [, , $start, $end] = $this->calculateArraySliceBounds(
                        $start,
                        $end,
                        $step,
                        count($currentNode->value)
                    );

                    if ($start < 0) {
                        $start = 0;
                    }

                    if ($end < 0) {
                        $end = 0;
                    }

                    $end += 1;

                    $i = $start;
                    while ($i < $end) {
                        $slice[$i] = $evaluationContext->nonExistentPathBehavior === NonExistentPathBehavior::CreateArray
                            ? []
                            : new \stdClass();

                        $i += $step;
                    }

                    $createdDynamically = true;
                }

                foreach ($slice as $segment => &$nodeValue) {
                    $resultNodeList->addNode(
                        $evaluationContext->getChildNode(
                            $currentNode,
                            $segment,
                            $nodeValue,
                            $createdDynamically
                        )
                    );
                }
            }

            return $resultNodeList;
        }

        if ($astNode instanceof FilterExpressionSelectorNode) {
            if ($currentNode->value instanceof \stdClass) {
                $currentNodeValue = get_object_vars($currentNode->value);
            } elseif (is_array($currentNode->value)) {
                $currentNodeValue = $currentNode->value;
            }

            if (isset($currentNodeValue)) {
                foreach ($currentNodeValue as $segment => &$childNodeValue) {
                    $childNode = $evaluationContext->getChildNode($currentNode, $segment, $childNodeValue);

                    $expressionResult = $this->evaluateLogicalExpressionNode(
                        $astNode->expressionNode,
                        $childNode,
                        $evaluationContext
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
                $selectorNodeList = $this->evaluateSelector($selectorNode, $currentNode, $evaluationContext);
                foreach ($selectorNodeList->getNodes() as $node) {
                    $resultNodeList->addNode($node);
                }
            }

            return $resultNodeList;
        }

        return $resultNodeList;
    }

    /**
     * @throws JsonPathEvaluationException
     * @throws \ReflectionException
     */
    protected function evaluateLogicalExpressionNode(
        AbstractSegmentNode|AbstractSelectorNode|AbstractLogicalExpressionNode $astNode,
        Node $currentNode,
        EvaluationContext $evaluationContext
    ): mixed {
        if ($astNode instanceof AbstractJsonPathExpressionNode) {
            return $this->evaluateJsonPathExpression($astNode, $currentNode, $evaluationContext);
        }

        if ($astNode instanceof FunctionNode) {
            $function = $this->getFunctions()[$astNode->token->value] ?? null;
            if (!$function) {
                throw new JsonPathEvaluationException(
                    $astNode->token->value . ' is not defined',
                    $astNode->token->position,
                    $evaluationContext->expression,
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

                $argument = $this->evaluateLogicalExpressionNode($argumentNode, $currentNode, $evaluationContext);

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
            return $this->evaluateBinaryOperatorNode($astNode, $currentNode, $evaluationContext);
        }

        if ($astNode instanceof AbstractUnaryOperatorNode) {
            return $this->evaluateUnaryOperatorNode($astNode, $currentNode, $evaluationContext);
        }

        throw new JsonPathEvaluationException(
            'Can not evaluate unexpected node ' . $astNode->token->value . ' (' . get_class($astNode) . ')',
            $astNode->token->position,
            $evaluationContext->expression,
            1701659747
        );
    }

    /**
     * @throws JsonPathEvaluationException
     * @throws \ReflectionException
     */
    protected function evaluateUnaryOperatorNode(
        AbstractUnaryOperatorNode $astNode,
        Node $currentNode,
        EvaluationContext $evaluationContext
    ): LogicalFalse|LogicalTrue|Nothing|bool {
        /** @noinspection PhpConditionAlreadyCheckedInspection */
        if ($astNode instanceof LogicalNotNode) {
            $expressionResult = $this->evaluateLogicalExpressionNode($astNode->termNode, $currentNode, $evaluationContext);
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

        throw new JsonPathEvaluationException(
            'Unexpected unary operator node ' . $astNode->token->value,
            $astNode->token->position,
            $evaluationContext->expression,
            1701659703
        );
    }

    /**
     * @throws JsonPathEvaluationException
     * @throws \ReflectionException
     */
    protected function evaluateBinaryOperatorNode(
        AbstractBinaryOperatorNode $astNode,
        Node $currentNode,
        EvaluationContext $evaluationContext
    ): AbstractLogicalType {
        $leftExpressionResult = $this->evaluateLogicalExpressionNode($astNode->leftNode, $currentNode, $evaluationContext);
        $rightExpressionResult = $this->evaluateLogicalExpressionNode($astNode->rightNode, $currentNode, $evaluationContext);

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

            if (
                (is_object($leftExpressionResult) && is_object($rightExpressionResult))
                || (is_array($leftExpressionResult) && is_array($rightExpressionResult))
                || (
                    (is_int($leftExpressionResult) || is_float($leftExpressionResult))
                    && (is_int($rightExpressionResult) || is_float($rightExpressionResult))
                )
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

        throw new JsonPathEvaluationException(
            'Unexpected binary operator node ' . $astNode->token->value,
            $astNode->token->position,
            $evaluationContext->expression,
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

        [$lowerBound, $upperBound] = $this->calculateArraySliceBounds($start, $end, $step, $length);

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

    /**
     * @return array{int, int, int, int}
     */
    private function calculateArraySliceBounds(?int $start, ?int $end, int $step, int $length): array
    {
        if ($step === 0) {
            return [0, 0, 0, 0];
        }

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

        return [$lowerBound, $upperBound, $start, $end];
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