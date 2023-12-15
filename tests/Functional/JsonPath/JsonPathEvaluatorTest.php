<?php
declare(strict_types=1);

namespace Ropi\JsonPathEvaluator\Tests\Functional\JsonPath;

use Ropi\JsonPathEvaluator\Exception\JsonPathEvaluatorException;
use Ropi\JsonPathEvaluator\NonExistentPathBehavior;
use Ropi\JsonPathEvaluator\Parser\Exception\ParseException;
use Ropi\JsonPathEvaluator\Types\AbstractLogicalType;
use Ropi\JsonPathEvaluator\Types\AbstractNodesType;
use Ropi\JsonPathEvaluator\Types\AbstractValueType;
use Ropi\JsonPathEvaluator\Types\LogicalTrue;

class JsonPathEvaluatorTest extends AbstractJsonPathEvaluatorTestCase
{
    /**
     * @throws \ReflectionException
     * @throws \Ropi\JsonPathEvaluator\Exception\JsonPathEvaluatorException
     */
    public function testGeneral(): void
    {
        $evaluator = new \Ropi\JsonPathEvaluator\JsonPathEvaluator();

        $data = json_decode('{ "store": {
               "book": [
                 { "category": "reference",
                   "author": "Nigel Rees",
                   "title": "Sayings of the Century",
                   "price": 8.95
                 },
                 { "category": "fiction",
                   "author": "Evelyn Waugh",
                   "title": "Sword of Honour",
                   "price": 12.99
                 },
                 { "category": "fiction",
                   "author": "Herman Melville",
                   "title": "Moby Dick",
                   "isbn": "0-553-21311-3",
                   "price": 8.99
                 },
                 { "category": "fiction",
                   "author": "J. R. R. Tolkien",
                   "title": "The Lord of the Rings",
                   "isbn": "0-395-19395-8",
                   "price": 22.99
                 }
               ],
               "bicycle": {
                 "color": "red",
                 "price": 399
               }
             }
           }');

        $this->assertJsonPathResult(
            $evaluator->getValues($data, '$.store.book[*].author'),
            '[
              "Nigel Rees",
              "Evelyn Waugh",
              "Herman Melville",
              "J. R. R. Tolkien"
            ]',
            'Expected the authors of all books in the store'
        );

        $this->assertJsonPathResult(
            $evaluator->getValues($data, '$..author'),
            '[
              "Nigel Rees",
              "Evelyn Waugh",
              "Herman Melville",
              "J. R. R. Tolkien"
            ]',
            'Expected all authors'
        );

        $this->assertJsonPathResult(
            $evaluator->getValues($data, '$.store.*'),
            '[
              [
                {
                  "category": "reference",
                  "author": "Nigel Rees",
                  "title": "Sayings of the Century",
                  "price": 8.95
                },
                {
                  "category": "fiction",
                  "author": "Evelyn Waugh",
                  "title": "Sword of Honour",
                  "price": 12.99
                },
                {
                  "category": "fiction",
                  "author": "Herman Melville",
                  "title": "Moby Dick",
                  "isbn": "0-553-21311-3",
                  "price": 8.99
                },
                {
                  "category": "fiction",
                  "author": "J. R. R. Tolkien",
                  "title": "The Lord of the Rings",
                  "isbn": "0-395-19395-8",
                  "price": 22.99
                }
              ],
              {
                "color": "red",
                "price": 399
              }
            ]',
            'Expected all things in store, which are some books and a red bicycle'
        );

        $this->assertJsonPathResult(
            $evaluator->getValues($data, '$.store..price'),
            '[
              8.95,
              12.99,
              8.99,
              22.99,
              399
            ]',
            'Expected the prices of everything in the store'
        );

        $this->assertJsonPathResult(
            $evaluator->getValues($data, '$..book[2]'),
            '[
              {
                "category": "fiction",
                "author": "Herman Melville",
                "title": "Moby Dick",
                "isbn": "0-553-21311-3",
                "price": 8.99
              }
            ]',
            'Expected the third book'
        );

        $this->assertJsonPathResult(
            $evaluator->getValues($data, '$..book[2].author'),
            '[
              "Herman Melville"
            ]',
            'Expected the third book\'s author'
        );

        $this->assertJsonPathResult(
            $evaluator->getValues($data, '$..book[2].publisher'),
            '[]',
            'Expected empty result: the third book does not have a "publisher" member'
        );

        $this->assertJsonPathResult(
            $evaluator->getValues($data, '$..book[-1]'),
            '[
              {
                "category":"fiction",
                "author":"J. R. R. Tolkien",
                "title":"The Lord of the Rings",
                "isbn":"0-395-19395-8",
                "price":22.99
              }
            ]',
            'Expected the last book in order'
        );

        $this->assertJsonPathResult(
            $evaluator->getValues($data, '$..book[0,1]'),
            '[
              {
                "category":"reference",
                "author":"Nigel Rees",
                "title":"Sayings of the Century",
                "price":8.95
              },
              {
                "category":"fiction",
                "author":"Evelyn Waugh",
                "title":"Sword of Honour",
                "price":12.99
              }
            ]',
            'Expected the first two books'
        );

        $this->assertJsonPathResult(
            $evaluator->getValues($data, '$..book[:2]'),
            '[
              {
                "category":"reference",
                "author":"Nigel Rees",
                "title":"Sayings of the Century",
                "price":8.95
              },
              {
                "category":"fiction",
                "author":"Evelyn Waugh",
                "title":"Sword of Honour",
                "price":12.99
              }
            ]',
            'Expected the first two books'
        );

        $this->assertJsonPathResult(
            $evaluator->getValues($data, '$..book[?@.isbn]'),
            '[
              {
                "category":"fiction",
                "author":"Herman Melville",
                "title":"Moby Dick",
                "isbn":"0-553-21311-3",
                "price":8.99
              },
              {
                "category":"fiction",
                "author":"J. R. R. Tolkien",
                "title":"The Lord of the Rings",
                "isbn":"0-395-19395-8",
                "price":22.99
              }
            ]',
            'Expected all books with an ISBN number'
        );

        $this->assertJsonPathResult(
            $evaluator->getValues($data, '$..book[?@.price<10]'),
            '[
              {
                "category":"reference",
                "author":"Nigel Rees",
                "title":"Sayings of the Century",
                "price":8.95
              },
              {
                "category":"fiction",
                "author":"Herman Melville",
                "title":"Moby Dick",
                "isbn":"0-553-21311-3",
                "price":8.99
              }
            ]',
            'Expected all books cheaper than 10'
        );

        $this->assertJsonPathResult(
            $evaluator->getValues($data, '$..*'),
            '[
              {
                "book":[
                  {
                    "category":"reference",
                    "author":"Nigel Rees",
                    "title":"Sayings of the Century",
                    "price":8.95
                  },
                  {
                    "category":"fiction",
                    "author":"Evelyn Waugh",
                    "title":"Sword of Honour",
                    "price":12.99
                  },
                  {
                    "category":"fiction",
                    "author":"Herman Melville",
                    "title":"Moby Dick",
                    "isbn":"0-553-21311-3",
                    "price":8.99
                  },
                  {
                    "category":"fiction",
                    "author":"J. R. R. Tolkien",
                    "title":"The Lord of the Rings",
                    "isbn":"0-395-19395-8",
                    "price":22.99
                  }
                ],
                "bicycle":{
                  "color":"red",
                  "price":399
                }
              },
              [
                {
                  "category":"reference",
                  "author":"Nigel Rees",
                  "title":"Sayings of the Century",
                  "price":8.95
                },
                {
                  "category":"fiction",
                  "author":"Evelyn Waugh",
                  "title":"Sword of Honour",
                  "price":12.99
                },
                {
                  "category":"fiction",
                  "author":"Herman Melville",
                  "title":"Moby Dick",
                  "isbn":"0-553-21311-3",
                  "price":8.99
                },
                {
                  "category":"fiction",
                  "author":"J. R. R. Tolkien",
                  "title":"The Lord of the Rings",
                  "isbn":"0-395-19395-8",
                  "price":22.99
                }
              ],
              {
                "color":"red",
                "price":399
              },
              {
                "category":"reference",
                "author":"Nigel Rees",
                "title":"Sayings of the Century",
                "price":8.95
              },
              {
                "category":"fiction",
                "author":"Evelyn Waugh",
                "title":"Sword of Honour",
                "price":12.99
              },
              {
                "category":"fiction",
                "author":"Herman Melville",
                "title":"Moby Dick",
                "isbn":"0-553-21311-3",
                "price":8.99
              },
              {
                "category":"fiction",
                "author":"J. R. R. Tolkien",
                "title":"The Lord of the Rings",
                "isbn":"0-395-19395-8",
                "price":22.99
              },
              "reference",
              "Nigel Rees",
              "Sayings of the Century",
              8.95,
              "fiction",
              "Evelyn Waugh",
              "Sword of Honour",
              12.99,
              "fiction",
              "Herman Melville",
              "Moby Dick",
              "0-553-21311-3",
              8.99,
              "fiction",
              "J. R. R. Tolkien",
              "The Lord of the Rings",
              "0-395-19395-8",
              22.99,
              "red",
              399
            ]',
            'Expected all member values and array elements contained in the input value'
        );
    }

    /**
     * @throws JsonPathEvaluatorException
     * @throws \ReflectionException
     */
    public function testSetValues(): void
    {
        $evaluator = new \Ropi\JsonPathEvaluator\JsonPathEvaluator();

        $data = json_decode('{ "store": {
               "book": [
                 { "category": "reference",
                   "author": "Nigel Rees",
                   "title": "Sayings of the Century",
                   "price": 8.95
                 },
                 { "category": "fiction",
                   "author": "Herman Melville",
                   "title": "Moby Dick",
                   "isbn": "0-553-21311-3",
                   "price": 8.99
                 }
               ],
               "bicycle": {
                 "color": "red",
                 "price": 399
               }
             }
           }');

        $evaluator->setValues($data, '$..price', []);

        $this->assertJsonStringEqualsJsonString(
            (string)json_encode($data),
            '{ "store": {
               "book": [
                 { "category": "reference",
                   "author": "Nigel Rees",
                   "title": "Sayings of the Century",
                   "price": 8.95
                 },
                 { "category": "fiction",
                   "author": "Herman Melville",
                   "title": "Moby Dick",
                   "isbn": "0-553-21311-3",
                   "price": 8.99
                 }
               ],
               "bicycle": {
                 "color": "red",
                 "price": 399
               }
             }
           }',
            'No value to set'
        );

        $evaluator->setValues($data, '$..nonExistent', [1]);

        $this->assertJsonStringEqualsJsonString(
            (string)json_encode($data),
            '{ "store": {
               "book": [
                 { "category": "reference",
                   "author": "Nigel Rees",
                   "title": "Sayings of the Century",
                   "price": 8.95
                 },
                 { "category": "fiction",
                   "author": "Herman Melville",
                   "title": "Moby Dick",
                   "isbn": "0-553-21311-3",
                   "price": 8.99
                 }
               ],
               "bicycle": {
                 "color": "red",
                 "price": 399
               }
             }
           }',
            'Set non existent'
        );

        $evaluator->setValues($data, '$..price', [1]);

        $this->assertJsonStringEqualsJsonString(
            (string)json_encode($data),
            '{
                "store": {
                    "book": [
                        {
                            "category": "reference",
                            "author": "Nigel Rees",
                            "title": "Sayings of the Century",
                            "price": 1
                        },
                        {
                            "category": "fiction",
                            "author": "Herman Melville",
                            "title": "Moby Dick",
                            "isbn": "0-553-21311-3",
                            "price": 1
                        }
                    ],
                    "bicycle": {
                        "color": "red",
                        "price": 1
                    }
                }
            }',
            'One value to set'
        );

        $evaluator->setValues($data, '$..price', [1, 2, 3]);

        $this->assertJsonStringEqualsJsonString(
            (string)json_encode($data),
            '{
                "store": {
                    "book": [
                        {
                            "category": "reference",
                            "author": "Nigel Rees",
                            "title": "Sayings of the Century",
                            "price": 1
                        },
                        {
                            "category": "fiction",
                            "author": "Herman Melville",
                            "title": "Moby Dick",
                            "isbn": "0-553-21311-3",
                            "price": 2
                        }
                    ],
                    "bicycle": {
                        "color": "red",
                        "price": 3
                    }
                }
            }',
            'Multiple values to set'
        );

        $evaluator->setValues($data, '$.store.*.color', ["white"]);

        $this->assertJsonStringEqualsJsonString(
            (string)json_encode($data),
            '{
                "store": {
                    "book": [
                        {
                            "category": "reference",
                            "author": "Nigel Rees",
                            "title": "Sayings of the Century",
                            "price": 1
                        },
                        {
                            "category": "fiction",
                            "author": "Herman Melville",
                            "title": "Moby Dick",
                            "isbn": "0-553-21311-3",
                            "price": 2
                        }
                    ],
                    "bicycle": {
                        "color": "white",
                        "price": 3
                    }
                }
            }',
            'Wildcard set'
        );

        $evaluator->setValues($data, '$..book[1:].price', [10]);

        $this->assertJsonStringEqualsJsonString(
            (string)json_encode($data),
            '{
                "store": {
                    "book": [
                        {
                            "category": "reference",
                            "author": "Nigel Rees",
                            "title": "Sayings of the Century",
                            "price": 1
                        },
                        {
                            "category": "fiction",
                            "author": "Herman Melville",
                            "title": "Moby Dick",
                            "isbn": "0-553-21311-3",
                            "price": 10
                        }
                    ],
                    "bicycle": {
                        "color": "white",
                        "price": 3
                    }
                }
            }',
            'Array slice set'
        );

        $evaluator->setValues($data, '$..book[?@.price > 1].price', [2]);

        $this->assertJsonStringEqualsJsonString(
            (string)json_encode($data),
            '{
                "store": {
                    "book": [
                        {
                            "category": "reference",
                            "author": "Nigel Rees",
                            "title": "Sayings of the Century",
                            "price": 1
                        },
                        {
                            "category": "fiction",
                            "author": "Herman Melville",
                            "title": "Moby Dick",
                            "isbn": "0-553-21311-3",
                            "price": 2
                        }
                    ],
                    "bicycle": {
                        "color": "white",
                        "price": 3
                    }
                }
            }',
            'Set all book prices greater than 1'
        );

        $evaluator->setValues($data, '$', ['root']);

        $this->assertEquals('root', $data, 'Set root');
    }

    /**
     * @throws JsonPathEvaluatorException
     * @throws \ReflectionException
     */
    public function testSetValuesCreateNonExistent(): void
    {
        $evaluator = new \Ropi\JsonPathEvaluator\JsonPathEvaluator();

        $data = json_decode('{ "store": {
               "book": [
                 { "category": "reference",
                   "author": "Nigel Rees",
                   "title": "Sayings of the Century",
                   "price": 8.95
                 },
                 { "category": "fiction",
                   "author": "Herman Melville",
                   "title": "Moby Dick",
                   "isbn": "0-553-21311-3",
                   "price": 8.99
                 }
               ],
               "bicycle": {
                 "color": "red",
                 "price": 399
               }
             }
           }');

        $evaluator->setValues($data, '$..stdClass', [], NonExistentPathBehavior::CreateStdClass);

        $this->assertJsonStringEqualsJsonString(
            (string)json_encode($data),
            '{
                "store": {
                    "book": [
                        {
                            "category": "reference",
                            "author": "Nigel Rees",
                            "title": "Sayings of the Century",
                            "price": 8.95,
                            "stdClass": {}
                        },
                        {
                            "category": "fiction",
                            "author": "Herman Melville",
                            "title": "Moby Dick",
                            "isbn": "0-553-21311-3",
                            "price": 8.99,
                            "stdClass": {}
                        }
                    ],
                    "bicycle": {
                        "color": "red",
                        "price": 399,
                        "stdClass": {}
                    },
                    "stdClass": {}
                },
                "stdClass": {}
            }',
            'Create stdClass on non existent path'
        );

        $evaluator->setValues($data, '$.store.book[0].array', [], NonExistentPathBehavior::CreateArray);

        $this->assertJsonStringEqualsJsonString(
            (string)json_encode($data),
            '{
                "store": {
                    "book": [
                        {
                            "category": "reference",
                            "author": "Nigel Rees",
                            "title": "Sayings of the Century",
                            "price": 8.95,
                            "stdClass": {},
                            "array": []
                        },
                        {
                            "category": "fiction",
                            "author": "Herman Melville",
                            "title": "Moby Dick",
                            "isbn": "0-553-21311-3",
                            "price": 8.99,
                            "stdClass": {}
                        }
                    ],
                    "bicycle": {
                        "color": "red",
                        "price": 399,
                        "stdClass": {}
                    },
                    "stdClass": {}
                },
                "stdClass": {}
            }',
            'Create array on non existent path'
        );

        $evaluator->setValues($data, '$.store2.book[0]', [], NonExistentPathBehavior::CreateStdClass);

        $this->assertJsonStringEqualsJsonString(
            (string)json_encode($data),
            '{
                "store": {
                    "book": [
                        {
                            "category": "reference",
                            "author": "Nigel Rees",
                            "title": "Sayings of the Century",
                            "price": 8.95,
                            "stdClass": {},
                            "array": []
                        },
                        {
                            "category": "fiction",
                            "author": "Herman Melville",
                            "title": "Moby Dick",
                            "isbn": "0-553-21311-3",
                            "price": 8.99,
                            "stdClass": {}
                        }
                    ],
                    "bicycle": {
                        "color": "red",
                        "price": 399,
                        "stdClass": {}
                    },
                    "stdClass": {}
                },
                "store2": {
                    "book": [
                        {}
                    ]
                },
                "stdClass": {}
            }',
            'Create empty book in store2'
        );

        $data = new \stdClass();
        $evaluator->setValues($data, '$.my.deep.path', [10], NonExistentPathBehavior::CreateStdClass);

        $this->assertJsonStringEqualsJsonString(
            (string)json_encode($data),
            '{
                "my": {
                    "deep": {
                        "path": 10
                    }
                }
            }',
            'Create deep path and set value'
        );

        $data = new \stdClass();
        $evaluator->setValues($data, '$.prices[*].value', [10], NonExistentPathBehavior::CreateStdClass);

        $this->assertJsonStringEqualsJsonString(
            (string)json_encode($data),
            '{
                "prices": {}
            }',
            'Wildcard can not be created dynamically'
        );

        $data = new \stdClass();
        $evaluator->setValues($data, '$.prices[?@.value].value', [10], NonExistentPathBehavior::CreateStdClass);

        $this->assertJsonStringEqualsJsonString(
            (string)json_encode($data),
            '{
                "prices": {}
            }',
            'Filter expression can not be created dynamically'
        );
    }

    /**
     * @throws JsonPathEvaluatorException
     * @throws \ReflectionException
     */
    public function testDeleteValues(): void
    {
        $evaluator = new \Ropi\JsonPathEvaluator\JsonPathEvaluator();

        $data = json_decode('{ "store": {
               "book": [
                 { "category": "reference",
                   "author": "Nigel Rees",
                   "title": "Sayings of the Century",
                   "price": 8.95
                 },
                 { "category": "fiction",
                   "author": "Herman Melville",
                   "title": "Moby Dick",
                   "isbn": "0-553-21311-3",
                   "price": 8.99
                 }
               ],
               "bicycle": {
                 "color": "red",
                 "price": 399
               }
             }
           }');

        $evaluator->deleteValues($data, '$..price');

        $this->assertJsonStringEqualsJsonString(
            (string)json_encode($data),
            '{ "store": {
               "book": [
                 { "category": "reference",
                   "author": "Nigel Rees",
                   "title": "Sayings of the Century"
                 },
                 { "category": "fiction",
                   "author": "Herman Melville",
                   "title": "Moby Dick",
                   "isbn": "0-553-21311-3"
                 }
               ],
               "bicycle": {
                 "color": "red"
               }
             }
           }',
            'Delete all prices'
        );

        $evaluator->deleteValues($data, '$.store.bicycle');

        $this->assertJsonStringEqualsJsonString(
            (string)json_encode($data),
            '{ "store": {
               "book": [
                 { "category": "reference",
                   "author": "Nigel Rees",
                   "title": "Sayings of the Century"
                 },
                 { "category": "fiction",
                   "author": "Herman Melville",
                   "title": "Moby Dick",
                   "isbn": "0-553-21311-3"
                 }
               ]
             }
           }',
            'Delete bicycle'
        );

        $evaluator->deleteValues($data, '$.store.book[1:]');

        $this->assertJsonStringEqualsJsonString(
            (string)json_encode($data),
            '{ "store": {
               "book": [
                 { "category": "reference",
                   "author": "Nigel Rees",
                   "title": "Sayings of the Century"
                 }
               ]
             }
           }',
            'Delete array slice'
        );

        $evaluator->deleteValues($data, '$.store.book[*]');

        $this->assertJsonStringEqualsJsonString(
            (string)json_encode($data),
            '{ "store": {
               "book": []
             }
           }',
            'Delete wildcard'
        );

        $evaluator->deleteValues($data, '$');

        $this->assertNull($data, 'Delete root');
    }

    /**
     * @return void
     * @throws \ReflectionException
     * @throws \Ropi\JsonPathEvaluator\Exception\JsonPathEvaluatorException
     */
    public function testRoot(): void
    {
        $evaluator = new \Ropi\JsonPathEvaluator\JsonPathEvaluator();

        $data = json_decode('{"k": "v"}');

        $this->assertJsonPathResult(
            $evaluator->getValues($data, '$'),
            '[{"k": "v"}]',
            'Root node'
        );

        $this->assertJsonPathResult(
            $evaluator->getPaths($data, '$'),
            '["$"]',
            'Paths: Root node'
        );
    }

    /**
     * @return void
     * @throws \ReflectionException
     * @throws \Ropi\JsonPathEvaluator\Exception\JsonPathEvaluatorException
     */
    public function testNameSelector(): void
    {
        $evaluator = new \Ropi\JsonPathEvaluator\JsonPathEvaluator();

        $data = json_decode('{
             "o": {"j j": {"k.k": 3}},
             "\'": {"@": 2}
         }');

        $this->assertJsonPathResult(
            $evaluator->getValues($data, '$.o[\'j j\']'),
            '[{"k.k":3}]',
            'Named value in nested object'
        );

        $this->assertJsonPathResult(
            $evaluator->getPaths($data, '$.o[\'j j\']'),
            (string)json_encode(["$['o']['j j']"]),
            'Paths: Named value in nested object'
        );

        $this->assertJsonPathResult(
            $evaluator->getValues($data, '$.o[\'j j\'][\'k.k\']'),
            '[3]',
            'Nesting further down'
        );

        $this->assertJsonPathResult(
            $evaluator->getPaths($data, '$.o[\'j j\'][\'k.k\']'),
            (string)json_encode(["$['o']['j j']['k.k']"]),
            'Paths: Nesting further down'
        );

        $this->assertJsonPathResult(
            $evaluator->getValues($data, '$.o["j j"]["k.k"]'),
            '[3]',
            'Different delimiter in query, unchanged normalized path'
        );

        $this->assertJsonPathResult(
            $evaluator->getPaths($data, '$.o["j j"]["k.k"]'),
            (string)json_encode(["$['o']['j j']['k.k']"]),
            'Paths: Different delimiter in query, unchanged normalized path'
        );

        $this->assertJsonPathResult(
            $evaluator->getValues($data, '$["\'"]["@"]'),
            '[2]',
            'Unusual member names'
        );

        $this->assertJsonPathResult(
            $evaluator->getPaths($data, '$["\'"]["@"]'),
            (string)json_encode(["$['\'']['@']"]),
            'Paths: Unusual member names'
        );
    }

    /**
     * @return void
     * @throws \ReflectionException
     * @throws \Ropi\JsonPathEvaluator\Exception\JsonPathEvaluatorException
     */
    public function testWildcard(): void
    {
        $evaluator = new \Ropi\JsonPathEvaluator\JsonPathEvaluator();

        $data = json_decode('{
             "o": {"j": 1, "k": 2},
             "a": [5, 3]
           }');

        $this->assertJsonPathResult(
            $evaluator->getValues($data, '$[*]'),
            '[{"j": 1, "k": 2}, [5, 3]]',
            'Object value'
        );

        $this->assertJsonPathResult(
            $evaluator->getPaths($data, '$[*]'),
            (string)json_encode(["$['o']", "$['a']"]),
            'Paths: Object value'
        );

        $this->assertJsonPathResult(
            $evaluator->getValues($data, '$.o[*]'),
            '[1,2]',
            'Object values'
        );

        $this->assertJsonPathResult(
            $evaluator->getPaths($data, '$.o[*]'),
            (string)json_encode(["$['o']['j']", "$['o']['k']"]),
            'Paths: Object values'
        );

        $this->assertJsonPathResult(
            $evaluator->getValues($data, '$.o[*,*]'),
            '[1,2,1,2]',
            'Non-deterministic ordering'
        );

        $this->assertJsonPathResult(
            $evaluator->getPaths($data, '$.o[*,*]'),
            (string)json_encode(["$['o']['j']", "$['o']['k']", "$['o']['j']", "$['o']['k']"]),
            'Paths: Non-deterministic ordering'
        );

        $this->assertJsonPathResult(
            $evaluator->getValues($data, '$.a[*]'),
            '[5,3]',
            'Array members'
        );

        $this->assertJsonPathResult(
            $evaluator->getPaths($data, '$.a[*]'),
            (string)json_encode(["$['a'][0]", "$['a'][1]"]),
            'Paths: Array members'
        );
    }

    /**
     * @return void
     * @throws \ReflectionException
     * @throws \Ropi\JsonPathEvaluator\Exception\JsonPathEvaluatorException
     */
    public function testIndexSelector(): void
    {
        $evaluator = new \Ropi\JsonPathEvaluator\JsonPathEvaluator();

        $data = json_decode('["a","b"]');

        $this->assertJsonPathResult(
            $evaluator->getValues($data, '$[1]'),
            '["b"]',
            'Element of array'
        );

        $this->assertJsonPathResult(
            $evaluator->getPaths($data, '$[1]'),
            (string)json_encode(["$[1]"]),
            'Paths: Element of array'
        );

        $this->assertJsonPathResult(
            $evaluator->getValues($data, '$[-2]'),
            '["a"]',
            'Element of array, from the end'
        );

        $this->assertJsonPathResult(
            $evaluator->getPaths($data, '$[-2]'),
            (string)json_encode(["$[0]"]),
            'Paths: Element of array, from the end'
        );
    }

    /**
     * @return void
     * @throws \ReflectionException
     * @throws \Ropi\JsonPathEvaluator\Exception\JsonPathEvaluatorException
     */
    public function testArraySliceSelector(): void
    {
        $evaluator = new \Ropi\JsonPathEvaluator\JsonPathEvaluator();

        $data = json_decode('["a", "b", "c", "d", "e", "f", "g"]');

        $this->assertJsonPathResult(
            $evaluator->getValues($data, '$[1:3]'),
            '["b", "c"]',
            'Slice with default step'
        );

        $this->assertJsonPathResult(
            $evaluator->getPaths($data, '$[1:3]'),
            (string)json_encode(["$[1]", "$[2]"]),
            'Paths: Slice with default step'
        );

        $this->assertJsonPathResult(
            $evaluator->getValues($data, '$[5:]'),
            '["f", "g"]',
            'Slice with no end index'
        );

        $this->assertJsonPathResult(
            $evaluator->getPaths($data, '$[5:]'),
            (string)json_encode(["$[5]", "$[6]"]),
            'Paths: Slice with no end index'
        );

        $this->assertJsonPathResult(
            $evaluator->getValues($data, '$[1:5:2]'),
            '["b", "d"]',
            'Slice with step 2'
        );

        $this->assertJsonPathResult(
            $evaluator->getPaths($data, '$[1:5:2]'),
            (string)json_encode(["$[1]", "$[3]"]),
            'Paths: Slice with step 2'
        );

        $this->assertJsonPathResult(
            $evaluator->getValues($data, '$[5:1:-2]'),
            '["f", "d"]',
            'Slice with negative step'
        );

        $this->assertJsonPathResult(
            $evaluator->getPaths($data, '$[5:1:-2]'),
            (string)json_encode(["$[5]", "$[3]"]),
            'Paths: Slice with negative step'
        );

        $this->assertJsonPathResult(
            $evaluator->getValues($data, '$[::-1]'),
            '["g", "f", "e", "d", "c", "b", "a"]',
            'Slice in reverse order'
        );

        $this->assertJsonPathResult(
            $evaluator->getPaths($data, '$[::-1]'),
            (string)json_encode(["$[6]", "$[5]", "$[4]", "$[3]", "$[2]", "$[1]", "$[0]"]),
            'Paths: Slice in reverse order'
        );
    }

    /**
     * @return void
     * @throws \ReflectionException
     * @throws \Ropi\JsonPathEvaluator\Exception\JsonPathEvaluatorException
     */
    public function testFilterSelector(): void
    {
        $evaluator = new \Ropi\JsonPathEvaluator\JsonPathEvaluator();

        $data = json_decode('{
             "obj": {"x": "y"},
             "arr": [2, 3]
           }');

        $this->assertTrue((bool)$evaluator->getValues($data, '$[?$.absent1 == $.absent2]'), 'Empty nodelists');
        $this->assertTrue((bool)$evaluator->getValues($data, '$[?$.absent1 <= $.absent2]'), '== implies <=');
        $this->assertFalse((bool)$evaluator->getValues($data, '$[?$.absent == \'g\']'), 'Empty nodelists');
        $this->assertFalse((bool)$evaluator->getValues($data, '$[?$.absent1 != $.absent2]'), 'Empty nodelists');
        $this->assertTrue((bool)$evaluator->getValues($data, '$[?$.absent != \'g\']'), 'Empty nodelists');
        $this->assertTrue((bool)$evaluator->getValues($data, '$[?1 <= 2]'), 'Numeric comparison');
        $this->assertFalse((bool)$evaluator->getValues($data, '$[?1 > 2]'), 'Strict, numeric comparison');
        $this->assertFalse((bool)$evaluator->getValues($data, '$[?13 == \'13\']'), 'Type mismatch');
        $this->assertTrue((bool)$evaluator->getValues($data, '$[?\'a\' <= \'b\']'), 'String comparison');
        $this->assertFalse((bool)$evaluator->getValues($data, '$[?\'a\' > \'b\']'), 'Strict, string comparison');
        $this->assertFalse((bool)$evaluator->getValues($data, '$[?$.obj == $.arr]'), 'Type mismatch');
        $this->assertTrue((bool)$evaluator->getValues($data, '$[?$.obj != $.arr]'), 'Type mismatch');
        $this->assertTrue((bool)$evaluator->getValues($data, '$[?$.obj == $.obj]'), 'Object comparison');
        $this->assertFalse((bool)$evaluator->getValues($data, '$[?$.obj != $.obj]'), 'Object comparison');
        $this->assertTrue((bool)$evaluator->getValues($data, '$[?$.arr == $.arr]'), 'Array comparison');
        $this->assertFalse((bool)$evaluator->getValues($data, '$[?$.arr != $.arr]'), 'Array comparison');
        $this->assertFalse((bool)$evaluator->getValues($data, '$[?$.obj == 17]'), 'Type mismatch');
        $this->assertTrue((bool)$evaluator->getValues($data, '$[?$.obj != 17]'), 'Type mismatch');
        $this->assertFalse((bool)$evaluator->getValues($data, '$[?$.obj <= $.arr]'), 'Objects and arrays do not offer < comparison');
        $this->assertFalse((bool)$evaluator->getValues($data, '$[?$.obj < $.arr]'), 'Objects and arrays do not offer < comparison');
        $this->assertTrue((bool)$evaluator->getValues($data, '$[?$.obj <= $.obj]'), '== implies <=');
        $this->assertTrue((bool)$evaluator->getValues($data, '$[?$.arr <= $.arr]'), '== implies <=');
        $this->assertFalse((bool)$evaluator->getValues($data, '$[?1 <= $.arr]'), 'Arrays do not offer < comparison');
        $this->assertFalse((bool)$evaluator->getValues($data, '$[?1 >= $.arr]'), 'Arrays do not offer < comparison');
        $this->assertFalse((bool)$evaluator->getValues($data, '$[?1 > $.arr]'), 'Arrays do not offer < comparison');
        $this->assertFalse((bool)$evaluator->getValues($data, '$[?1 < $.arr]'), 'Arrays do not offer < comparison');
        $this->assertTrue((bool)$evaluator->getValues($data, '$[?true <= true]'), '== implies <=');
        $this->assertFalse((bool)$evaluator->getValues($data, '$[?true > true]'), 'Booleans do not offer < comparison');

        $data = json_decode('{
             "a": [3, 5, 1, 2, 4, 6,
                   {"b": "j"},
                   {"b": "k"},
                   {"b": {}},
                   {"b": "kilo"}
                  ],
             "o": {"p": 1, "q": 2, "r": 3, "s": 5, "t": {"u": 6}},
             "e": "f"
           }');

        $this->assertJsonPathResult(
            $evaluator->getValues($data, '$.a[?@.b ==\'kilo\']'),
            '[{"b":"kilo"}]',
            'Member value comparison'
        );

        $this->assertJsonPathResult(
            $evaluator->getPaths($data, '$.a[?@.b ==\'kilo\']'),
            (string)json_encode(["$['a'][9]"]),
            'Paths: Member value comparison'
        );

        $this->assertJsonPathResult(
            $evaluator->getValues($data, '$.a[?(@.b ==\'kilo\')] '),
            '[{"b":"kilo"}]',
            'Equivalent query with enclosing parentheses'
        );

        $this->assertJsonPathResult(
            $evaluator->getPaths($data, '$.a[?(@.b ==\'kilo\')] '),
            (string)json_encode(["$['a'][9]"]),
            'Paths: Equivalent query with enclosing parentheses'
        );

        $this->assertJsonPathResult(
            $evaluator->getValues($data, '$.a[?@>3.5]'),
            '[5,4,6]',
            'Array value comparison'
        );

        $this->assertJsonPathResult(
            $evaluator->getPaths($data, '$.a[?@>3.5]'),
            (string)json_encode(["$['a'][1]", "$['a'][4]", "$['a'][5]"]),
            'Paths: Array value comparison'
        );

        $this->assertJsonPathResult(
            $evaluator->getValues($data, '$.a[?@.b]'),
            '[
              {
                "b":"j"
              },
              {
                "b":"k"
              },
              {
                "b":{}
              },
              {
                "b":"kilo"
              }
            ]',
            'Array value existence'
        );

        $this->assertJsonPathResult(
            $evaluator->getPaths($data, '$.a[?@.b]'),
            (string)json_encode(["$['a'][6]", "$['a'][7]", "$['a'][8]", "$['a'][9]"]),
            'Paths: Array value existence'
        );

        $this->assertJsonPathResult(
            $evaluator->getValues($data, '$[?@.*]'),
            '[
                [
                    3,
                    5,
                    1,
                    2,
                    4,
                    6,
                    {
                        "b": "j"
                    },
                    {
                        "b": "k"
                    },
                    {
                        "b": {}
                    },
                    {
                        "b": "kilo"
                    }
                ],
                {
                    "p": 1,
                    "q": 2,
                    "r": 3,
                    "s": 5,
                    "t": {
                        "u": 6
                    }
                }
            ]',
            'Existence of non-singular queries'
        );

        $this->assertJsonPathResult(
            $evaluator->getPaths($data, '$[?@.*]'),
            (string)json_encode(["$['a']", "$['o']"]),
            'Paths: Existence of non-singular queries'
        );

        $this->assertJsonPathResult(
            $evaluator->getValues($data, '$[?@[?@.b]]'),
            '[
                [
                    3,
                    5,
                    1,
                    2,
                    4,
                    6,
                    {
                        "b": "j"
                    },
                    {
                        "b": "k"
                    },
                    {
                        "b": {}
                    },
                    {
                        "b": "kilo"
                    }
                ]
            ]',
            'Nested filters'
        );

        $this->assertJsonPathResult(
            $evaluator->getPaths($data, '$[?@[?@.b]]'),
            (string)json_encode(["$['a']"]),
            'Paths: Nested filters'
        );

        $this->assertJsonPathResult(
            $evaluator->getValues($data, '$.o[?@<3, ?@<3]'),
            '[1,2,1,2]',
            'Non-deterministic ordering'
        );

        $this->assertJsonPathResult(
            $evaluator->getPaths($data, '$.o[?@<3, ?@<3]'),
            (string)json_encode(["$['o']['p']", "$['o']['q']", "$['o']['p']", "$['o']['q']"]),
            'Paths: Non-deterministic ordering'
        );

        $this->assertJsonPathResult(
            $evaluator->getValues($data, '$.a[?@<2 || @.b == "k"]'),
            '[1, {"b": "k"}]',
            'Array value logical OR'
        );

        $this->assertJsonPathResult(
            $evaluator->getPaths($data, '$.a[?@<2 || @.b == "k"]'),
            (string)json_encode(["$['a'][2]", "$['a'][7]"]),
            'Paths: Array value logical OR'
        );

        $this->assertJsonPathResult(
            $evaluator->getValues($data, '$.a[?match(@.b, "[jk]")]'),
            '[{"b": "j"}, {"b": "k"}]',
            'Array value regular expression match'
        );

        $this->assertJsonPathResult(
            $evaluator->getPaths($data, '$.a[?match(@.b, "[jk]")]'),
            (string)json_encode(["$['a'][6]", "$['a'][7]"]),
            'Paths: Array value regular expression match'
        );

        $this->assertJsonPathResult(
            $evaluator->getValues($data, '$.a[?search(@.b, "[jk]")]'),
            '[{"b": "j"}, {"b": "k"}, {"b": "kilo"}]',
            'Array value regular expression search'
        );

        $this->assertJsonPathResult(
            $evaluator->getPaths($data, '$.a[?search(@.b, "[jk]")]'),
            (string)json_encode(["$['a'][6]", "$['a'][7]", "$['a'][9]"]),
            'Paths: Array value regular expression search'
        );

        $this->assertJsonPathResult(
            $evaluator->getValues($data, '$.o[?@>1 && @<4]'),
            '[2, 3]',
            'Object value logical AND'
        );

        $this->assertJsonPathResult(
            $evaluator->getPaths($data, '$.o[?@>1 && @<4]'),
            (string)json_encode(["$['o']['q']", "$['o']['r']"]),
            'Paths: Object value logical AND'
        );

        $this->assertJsonPathResult(
            $evaluator->getValues($data, '$.o[?@.u || @.x]'),
            '[{"u": 6}]',
            'Object value logical OR'
        );

        $this->assertJsonPathResult(
            $evaluator->getPaths($data, '$.o[?@.u || @.x]'),
            (string)json_encode(["$['o']['t']"]),
            'Paths: Object value logical OR'
        );

        $this->assertJsonPathResult(
            $evaluator->getValues($data, '$.a[?@.b == $.x]'),
            '[
                3,
                5,
                1,
                2,
                4,
                6
            ]',
            'Comparison of queries with no values'
        );

        $this->assertJsonPathResult(
            $evaluator->getPaths($data, '$.a[?@.b == $.x]'),
            (string)json_encode(["$['a'][0]", "$['a'][1]", "$['a'][2]", "$['a'][3]", "$['a'][4]", "$['a'][5]"]),
            'Paths: Comparison of queries with no values'
        );

        $this->assertJsonPathResult(
            $evaluator->getValues($data, '$.a[?@ == @]'),
            '[
                3,
                5,
                1,
                2,
                4,
                6,
                {
                    "b": "j"
                },
                {
                    "b": "k"
                },
                {
                    "b": {}
                },
                {
                    "b": "kilo"
                }
            ]',
            'Comparison of primitive and of structured values'
        );

        $this->assertJsonPathResult(
            $evaluator->getPaths($data, '$.a[?@ == @]'),
            (string)json_encode(["$['a'][0]", "$['a'][1]", "$['a'][2]", "$['a'][3]", "$['a'][4]", "$['a'][5]", "$['a'][6]", "$['a'][7]", "$['a'][8]", "$['a'][9]"]),
            'Paths: Comparison of primitive and of structured values'
        );
    }

    /**
     * @return void
     * @throws \ReflectionException
     * @throws \Ropi\JsonPathEvaluator\Exception\JsonPathEvaluatorException
     */
    public function testFunctionExtensions(): void
    {
        $evaluator = new \Ropi\JsonPathEvaluator\JsonPathEvaluator();

        $evaluator->registerFunction('foo', function(AbstractNodesType $nodes): AbstractNodesType {
            return $nodes;
        });

        $evaluator->registerFunction('bar', function($parameter): AbstractLogicalType {
            return new LogicalTrue();
        });

        $evaluator->registerFunction('bnl', function(AbstractNodesType|AbstractLogicalType $parameter): AbstractLogicalType {
            return new LogicalTrue();
        });

        $evaluator->registerFunction('blt', function(AbstractLogicalType $parameter): AbstractLogicalType {
            return new LogicalTrue();
        });

        $evaluator->registerFunction('bal', function(AbstractValueType $parameter): AbstractLogicalType {
            return new LogicalTrue();
        });

        $data = json_decode('[{ "timezone": "Europe/Berlin", "color": "red" }]');

        $this->assertTrue(
            (bool)$evaluator->getValues($data, '$[?length(@) < 3]'),
            'well-typed'
        );

        $this->assertFalse(
            (bool)$evaluator->getValues($data, '$[?length(@.*) < 3]'),
            'not well-typed since @.* is a non-singular query'
        );

        $this->assertTrue(
            (bool)$evaluator->getValues($data, '$[?count(@.*) == 2]'),
            'well-typed'
        );

        $this->assertFalse(
            (bool)$evaluator->getValues($data, '$[?count(1) == 1]'),
            'not well-typed since 1 is not a query or function expression'
        );

        $this->assertTrue(
            (bool)$evaluator->getValues($data, '$[?count(foo(@.*))== 2]'),
            'well-typed, where foo() is a function extension with a parameter of type NodesType and result type NodesType'
        );

        $this->assertTrue(
            (bool)$evaluator->getValues($data, '$[?match(@.timezone,\'Europe/.*\')]'),
            'well-typed'
        );

        $this->assertFalse(
            (bool)$evaluator->getValues($data, '$[?match(@.timezone,\'Europe/.*\') == true]'),
            'not well-typed as LogicalType may not be used in comparisons'
        );

        $this->assertTrue(
            (bool)$evaluator->getValues($data, '$[?value(@..color)=="red"]'),
            'well-typed'
        );

        $this->assertTrue(
            (bool)$evaluator->getValues($data, '$[?bar(@.color)]'),
            'well-typed for any function bar() with a parameter of any declared type and result type LogicalType'
        );

        $this->assertTrue(
            (bool)$evaluator->getValues($data, '$[?bnl(@.*)]'),
            'well-typed for any function bnl() with a parameter of declared type NodesType or LogicalType and result type LogicalType'
        );

        $this->assertTrue(
            (bool)$evaluator->getValues($data, '$[?blt(1==1)]'),
            'well-typed, where blt() is a function with a parameter of declared type LogicalType and result type LogicalType'
        );

        $this->assertFalse(
            (bool)$evaluator->getValues($data, '$[?blt(1)]'),
            'not well-typed for the same function blt(), as 1 is not a query, logical-expr, or function expression'
        );

        $this->assertTrue(
            (bool)$evaluator->getValues($data, '$[?bal(1)]'),
            'well-typed, where bal() is a function with a parameter of declared type ValueType and result type LogicalType'
        );
    }

    /**
     * @return void
     * @throws \ReflectionException
     * @throws \Ropi\JsonPathEvaluator\Exception\JsonPathEvaluatorException
     */
    public function testChildSegment(): void
    {
        $evaluator = new \Ropi\JsonPathEvaluator\JsonPathEvaluator();

        $data = json_decode('["a", "b", "c", "d", "e", "f", "g"]');

        $this->assertJsonPathResult(
            $evaluator->getValues($data, '$[0,3]'),
            '["a", "d"]',
            'Indices'
        );

        $this->assertJsonPathResult(
            $evaluator->getPaths($data, '$[0,3]'),
            (string)json_encode(["$[0]", "$[3]"]),
            'Paths: Indices'
        );

        $this->assertJsonPathResult(
            $evaluator->getValues($data, '$[0:2,5]'),
            '["a", "b", "f"]',
            'Slice and index'
        );

        $this->assertJsonPathResult(
            $evaluator->getPaths($data, '$[0:2,5]'),
            (string)json_encode(["$[0]", "$[1]", "$[5]"]),
            'Paths: Slice and index'
        );

        $this->assertJsonPathResult(
            $evaluator->getValues($data, '$[0,0]'),
            '["a", "a"]',
            'Duplicate entries'
        );

        $this->assertJsonPathResult(
            $evaluator->getPaths($data, '$[0,0]'),
            (string)json_encode(["$[0]", "$[0]"]),
            'Paths: Duplicated entries'
        );
    }

    /**
     * @return void
     * @throws \ReflectionException
     * @throws \Ropi\JsonPathEvaluator\Exception\JsonPathEvaluatorException
     */
    public function testDescendantSegment(): void
    {
        $evaluator = new \Ropi\JsonPathEvaluator\JsonPathEvaluator();

        $data = json_decode('{
             "o": {"j": 1, "k": 2},
             "a": [5, 3, [{"j": 4}, {"k": 6}]]
           }');

        $this->assertJsonPathResult(
            $evaluator->getValues($data, '$..j'),
            '[1, 4]',
            'Object values'
        );

        $this->assertJsonPathResult(
            $evaluator->getPaths($data, '$..j'),
            (string)json_encode(["$['o']['j']", "$['a'][2][0]['j']"]),
            'Paths: Object values'
        );

        $this->assertJsonPathResult(
            $evaluator->getValues($data, '$..[0]'),
            '[5, {"j": 4}]',
            'Array values'
        );

        $this->assertJsonPathResult(
            $evaluator->getPaths($data, '$..[0]'),
            (string)json_encode(["$['a'][0]", "$['a'][2][0]"]),
            'Paths: Array values'
        );

        $this->assertJsonPathResult(
            $evaluator->getValues($data, '$..[*]'),
            '[
                {"j":1,"k":2},
                [
                    5,3,
                    [{"j":4},{"k":6}
                    ]],
                    1,
                    2,
                    5,
                    3,
                    [{"j":4},
                    {"k":6}],
                    {"j":4},
                    {"k":6},
                    4,
                    6
                ]',
            'All values'
        );

        $this->assertJsonPathResult(
            $evaluator->getPaths($data, '$..[*]'),
            (string)json_encode([
                "$['o']",
                "$['a']",
                "$['o']['j']",
                "$['o']['k']",
                "$['a'][0]",
                "$['a'][1]",
                "$['a'][2]",
                "$['a'][2][0]",
                "$['a'][2][1]",
                "$['a'][2][0]['j']",
                "$['a'][2][1]['k']"
            ]),
            'Paths: All values'
        );

        $this->assertJsonPathResult(
            $evaluator->getValues($data, '$..*'),
            '[
                {"j":1,"k":2},
                [
                    5,3,
                    [{"j":4},{"k":6}
                    ]],
                    1,
                    2,
                    5,
                    3,
                    [{"j":4},
                    {"k":6}],
                    {"j":4},
                    {"k":6},
                    4,
                    6
                ]',
            'All values'
        );

        $this->assertJsonPathResult(
            $evaluator->getPaths($data, '$..*'),
            (string)json_encode([
                "$['o']",
                "$['a']",
                "$['o']['j']",
                "$['o']['k']",
                "$['a'][0]",
                "$['a'][1]",
                "$['a'][2]",
                "$['a'][2][0]",
                "$['a'][2][1]",
                "$['a'][2][0]['j']",
                "$['a'][2][1]['k']"
            ]),
            'Paths: All values'
        );

        $this->assertJsonPathResult(
            $evaluator->getValues($data, '$..o'),
            '[ {"j": 1, "k": 2} ]',
            'Input value is visited'
        );

        $this->assertJsonPathResult(
            $evaluator->getPaths($data, '$..o'),
            (string)json_encode(["$['o']"]),
            'Paths: Input value is visited'
        );

        $this->assertJsonPathResult(
            $evaluator->getValues($data, '$.o..[*,*] '),
            '[1,2,1,2]',
            'Non-deterministic ordering'
        );

        $this->assertJsonPathResult(
            $evaluator->getPaths($data, '$.o..[*,*] '),
            (string)json_encode(["$['o']['j']", "$['o']['k']", "$['o']['j']", "$['o']['k']"]),
            'Paths: Non-deterministic ordering'
        );

        $this->assertJsonPathResult(
            $evaluator->getValues($data, '$.a..[0,1]'),
            '[5,3,{"j": 4},{"k": 6}]',
            'Multiple segments'
        );

        $this->assertJsonPathResult(
            $evaluator->getPaths($data, '$.a..[0,1]'),
            (string)json_encode(["$['a'][0]", "$['a'][1]", "$['a'][2][0]", "$['a'][2][1]"]),
            'Paths: Multiple segments'
        );
    }

    /**
     * @return void
     * @throws \ReflectionException
     * @throws \Ropi\JsonPathEvaluator\Exception\JsonPathEvaluatorException
     */
    public function testNull(): void
    {
        $evaluator = new \Ropi\JsonPathEvaluator\JsonPathEvaluator();

        $data = json_decode('{"a": null, "b": [null], "c": [{}], "null": 1}');

        $this->assertJsonPathResult(
            $evaluator->getValues($data, '$.a'),
            '[null]',
            'Object value'
        );

        $this->assertJsonPathResult(
            $evaluator->getPaths($data, '$.a'),
            (string)json_encode(["$['a']"]),
            'Paths: Object value'
        );

        $this->assertJsonPathResult(
            $evaluator->getValues($data, '$.a[0]'),
            '[]',
            'null used as array'
        );

        $this->assertJsonPathResult(
            $evaluator->getPaths($data, '$.a[0]'),
            (string)json_encode([]),
            'Paths: null used as array'
        );

        $this->assertJsonPathResult(
            $evaluator->getValues($data, '$.a.d'),
            '[]',
            'null used as object'
        );

        $this->assertJsonPathResult(
            $evaluator->getPaths($data, '$.a.d'),
            (string)json_encode([]),
            'Paths: null used as object'
        );

        $this->assertJsonPathResult(
            $evaluator->getValues($data, '$.b[0]'),
            '[null]',
            'Array value'
        );

        $this->assertJsonPathResult(
            $evaluator->getPaths($data, '$.b[0]'),
            (string)json_encode(["$['b'][0]"]),
            'Paths: Array value'
        );

        $this->assertJsonPathResult(
            $evaluator->getValues($data, '$.b[*]'),
            '[null]',
            'Array value'
        );

        $this->assertJsonPathResult(
            $evaluator->getPaths($data, '$.b[*]'),
            (string)json_encode(["$['b'][0]"]),
            'Paths: Array value'
        );

        $this->assertJsonPathResult(
            $evaluator->getValues($data, '$.b[?@]'),
            '[null]',
            'Existence'
        );

        $this->assertJsonPathResult(
            $evaluator->getPaths($data, '$.b[?@]'),
            (string)json_encode(["$['b'][0]"]),
            'Paths: Existence'
        );

        $this->assertJsonPathResult(
            $evaluator->getValues($data, '$.b[?@==null]'),
            '[null]',
            'Comparison'
        );

        $this->assertJsonPathResult(
            $evaluator->getPaths($data, '$.b[?@==null]'),
            (string)json_encode(["$['b'][0]"]),
            'Paths: Comparison'
        );

        $this->assertJsonPathResult(
            $evaluator->getValues($data, '$.c[?@.d==null]'),
            '[]',
            'Comparison with "missing" value'
        );

        $this->assertJsonPathResult(
            $evaluator->getPaths($data, '$.c[?@.d==null]'),
            (string)json_encode([]),
            'Paths: Comparison with "missing" value'
        );

        $this->assertJsonPathResult(
            $evaluator->getValues($data, '$.null'),
            '[1]',
            'Not JSON null at all, just a member name string'
        );

        $this->assertJsonPathResult(
            $evaluator->getPaths($data, '$.null'),
            (string)json_encode(["$['null']"]),
            'Paths: Not JSON null at all, just a member name string'
        );
    }

    /**
     * @throws JsonPathEvaluatorException
     * @throws \ReflectionException
     */
    public function testNormalizedPaths(): void
    {
        $evaluator = new \Ropi\JsonPathEvaluator\JsonPathEvaluator();

        $this->assertJsonPathResult(
            $evaluator->getPaths(json_decode('{"a": [1]}'), '$.a'),
            (string)json_encode(["$['a']"]),
            'Paths: Object value'
        );

        $this->assertJsonPathResult(
            $evaluator->getPaths(json_decode('[1,2,3,4,5]'), '$[1]'),
            (string)json_encode(["$[1]"]),
            'Paths: Array index'
        );

        $this->assertJsonPathResult(
            $evaluator->getPaths(json_decode('[1,2,3,4,5]'), '$[-3]'),
            (string)json_encode(["$[2]"]),
            'Paths: Negative array index for an array of length 5'
        );

        $this->assertJsonPathResult(
            $evaluator->getPaths(json_decode('{"a": {"b": [1,2,3,4,5]}}'), '$.a.b[1:2]'),
            (string)json_encode(["$['a']['b'][1]"]),
            'Paths: Nested structure'
        );

        $this->assertJsonPathResult(
            $evaluator->getPaths(json_decode('{"\\u000b":1}'), '$["\u000B"]'),
            (string)json_encode(["$['\\u000b']"]),
            'Paths: Unicode escape'
        );

        $this->assertJsonPathResult(
            $evaluator->getPaths(["\n" => 1], '$["\n"]'),
            (string)json_encode(["$['\\n']"]),
            'Paths: Unicode escape 2'
        );

        $this->assertJsonPathResult(
            $evaluator->getPaths(json_decode('{"😀":1}'), '$["😀"]'),
            (string)json_encode(["$['😀']"]),
            'Paths: Unicode unescaped'
        );

        $this->assertJsonPathResult(
            $evaluator->getPaths(["𝄞" => 1], '$["𝄞"]'),
            (string)json_encode(["$['𝄞']"]),
            'Paths: Unicode surrogate'
        );
    }

    /**
     * @throws \ReflectionException
     * @throws \Ropi\JsonPathEvaluator\Exception\JsonPathEvaluatorException
     */
    public function testCompliance(): void
    {
        $testSuite = json_decode((string) file_get_contents(__DIR__ . '/../../Resources/jsonpath-compliance-test-suite/cts.json'));
        assert($testSuite instanceof \stdClass);

        $evaluator = new \Ropi\JsonPathEvaluator\JsonPathEvaluator();

        foreach ($testSuite->tests as $test) {
            $data = $test->document ?? new \stdClass();

            if (
                in_array($test->name, [
                    'basic, no leading whitespace',
                    'basic, no trailing whitespace'
                ])
            ) {
                continue;
            }

            if ($test->invalid_selector ?? false) {
                try {
                    $evaluator->getValues($data, $test->selector);
                } catch (ParseException $parseException) {

                }

                $this->assertNotNull($parseException ?? null);

                /** @var ParseException $parseException */
                $this->assertInstanceOf(JsonPathEvaluatorException::class, $parseException);
            } else {
                $this->assertJsonPathResult(
                    $evaluator->getValues($data, $test->selector),
                    (string)json_encode($test->result),
                    $test->name
                );
            }
        }
    }

    /**
     * @throws \ReflectionException
     * @throws \Ropi\JsonPathEvaluator\Exception\JsonPathEvaluatorException
     */
    public function testComplianceAssociativeArray(): void
    {
        $testSuite = json_decode((string) file_get_contents(__DIR__ . '/../../Resources/jsonpath-compliance-test-suite/cts.json'), true);
        assert(is_array($testSuite));

        $evaluator = new \Ropi\JsonPathEvaluator\JsonPathEvaluator();

        foreach ($testSuite['tests'] as $test) {
            /** @var array<string, array|string> $test */

            /** @var array<scalar, mixed> $data */
            $data = isset($test['document'] ) && is_array($test['document']) ? $test['document'] : [];

            if (
                in_array($test['name'], [
                    'basic, no leading whitespace',
                    'basic, no trailing whitespace'
                ])
            ) {
                continue;
            }

            /** @var string $selector */
            $selector = $test['selector'];

            /** @var string $testName */
            $testName = $test['name'];

            if ($test['invalid_selector'] ?? false) {
                try {
                    $evaluator->getValues($data, $selector);
                } catch (ParseException $parseException) {

                }

                $this->assertNotNull($parseException ?? null, $testName);

                /** @var ParseException $parseException */
                $this->assertInstanceOf(JsonPathEvaluatorException::class, $parseException, $testName);
            } else {
                $this->assertJsonPathResult(
                    $evaluator->getValues($data, $selector),
                    (string)json_encode($test['result']),
                    $testName
                );
            }
        }
    }
}
