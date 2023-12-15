# A JSONPath evaluator for PHP

This library is a PHP based implementation of JSONPath ([Internet Draft Version 21](https://datatracker.ietf.org/doc/draft-ietf-jsonpath-base/21/)).

It allows to evaluate JSONPath expressions directly on PHP objects and/or arrays.\
This implementation passes all compliance tests of [JSONPath Compliance Test Suite](https://github.com/jsonpath-standard/jsonpath-compliance-test-suite).

### Requirements
* PHP >=8.1.0
* ext-ctype
* ext-intl
* ext-mbstring

## Installation
The library can be installed from a command line interface by using [composer](https://getcomposer.org/).

```
composer require ropi/json-path-evaluator
```

## Get values
The following example shows how to get matched values.\
The result is always an array of matches. If there are no matches, an empty array is returned.
```php
<?php
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


$evaluator = new \Ropi\JsonPathEvaluator\JsonPathEvaluator();


echo "Get authors of all books in the store:\n";

$result = $evaluator->getValues($data, '$.store.book[*].author');

echo json_encode($result, JSON_PRETTY_PRINT) . "\n";



echo "Get the prices of everything in the store:\n";

$result = $evaluator->getValues($data, '$.store..price');

echo json_encode($result, JSON_PRETTY_PRINT) . "\n";



echo "Get the last book in order:\n";

$result = $evaluator->getValues($data, '$..book[-1]');

echo json_encode($result, JSON_PRETTY_PRINT) . "\n";



echo "Get the first two books with union operator:\n";

$result = $evaluator->getValues($data, '$..book[0,1]');

echo json_encode($result, JSON_PRETTY_PRINT) . "\n";



echo "Get the first two books with array slice operator:\n";

$result = $evaluator->getValues($data, '$..book[:2]');

echo json_encode($result, JSON_PRETTY_PRINT) . "\n";



echo "Get all books with an ISBN number:\n";

$result = $evaluator->getValues($data, '$..book[?@.isbn]');

echo json_encode($result, JSON_PRETTY_PRINT) . "\n";



echo "Get all books cheaper than 10:\n";

$result = $evaluator->getValues($data, '$..book[?@.price<10]');

echo json_encode($result, JSON_PRETTY_PRINT) . "\n";



echo "Get all books where ISBN ends with 1, 2 or 3 (Regular Expression):\n";

$result = $evaluator->getValues($data, '$..book[?search(@.isbn, "[1-3]$")]');

echo json_encode($result, JSON_PRETTY_PRINT) . "\n";
```
The above example will output:
```

Get authors of all books in the store:
[
    "Nigel Rees",
    "Evelyn Waugh",
    "Herman Melville",
    "J. R. R. Tolkien"
]
Get the prices of everything in the store:
[
    8.95,
    12.99,
    8.99,
    22.99,
    399
]
Get the last book in order:
[
    {
        "category": "fiction",
        "author": "J. R. R. Tolkien",
        "title": "The Lord of the Rings",
        "isbn": "0-395-19395-8",
        "price": 22.99
    }
]
Get the first two books with union operator:
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
    }
]
Get the first two books with array slice operator:
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
    }
]
Get all books with an ISBN number:
[
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
]
Get all books cheaper than 10:
[
    {
        "category": "reference",
        "author": "Nigel Rees",
        "title": "Sayings of the Century",
        "price": 8.95
    },
    {
        "category": "fiction",
        "author": "Herman Melville",
        "title": "Moby Dick",
        "isbn": "0-553-21311-3",
        "price": 8.99
    }
]
Get all books where ISBN ends with 1, 2 or 3 (Regular Expression):
[
    {
        "category": "fiction",
        "author": "Herman Melville",
        "title": "Moby Dick",
        "isbn": "0-553-21311-3",
        "price": 8.99
    }
]

```

## Get paths
The following example shows how to get paths of matched values, where each path is represented as normalized JSONPath according to section 2.7 of [JSONPath internet draft](https://datatracker.ietf.org/doc/draft-ietf-jsonpath-base/21/).\
The result is always an array of matched paths. If there are no matches, an empty array is returned.
```php
<?php
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


$evaluator = new \Ropi\JsonPathEvaluator\JsonPathEvaluator();


echo "Get authors of all books in the store:\n";

$result = $evaluator->getPaths($data, '$.store.book[*].author');

echo json_encode($result, JSON_PRETTY_PRINT) . "\n";



echo "Get the prices of everything in the store:\n";

$result = $evaluator->getPaths($data, '$.store..price');

echo json_encode($result, JSON_PRETTY_PRINT) . "\n";



echo "Get all books cheaper than 10:\n";

$result = $evaluator->getPaths($data, '$..book[?@.price<10]');

echo json_encode($result, JSON_PRETTY_PRINT) . "\n";
```
The above example will output:
```
Get authors of all books in the store:
[
    "$['store']['book'][0]['author']",
    "$['store']['book'][1]['author']",
    "$['store']['book'][2]['author']",
    "$['store']['book'][3]['author']"
]
Get the prices of everything in the store:
[
    "$['store']['book'][0]['price']",
    "$['store']['book'][1]['price']",
    "$['store']['book'][2]['price']",
    "$['store']['book'][3]['price']",
    "$['store']['bicycle']['price']"
]
Get all books cheaper than 10:
[
    "$['store']['book'][0]",
    "$['store']['book'][2]"
]

```
## Set values
The following example shows how to set/replace values.
```php
<?php
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


$evaluator = new \Ropi\JsonPathEvaluator\JsonPathEvaluator();


echo "Set all prices to 10:\n";

$evaluator->setValues($data, '$..price', [10]);

echo json_encode($data, JSON_PRETTY_PRINT) . "\n";



echo "Set prices alternately to 1, 2 and 3:\n";

$evaluator->setValues($data, '$..price', [1, 2, 3]);

echo json_encode($data, JSON_PRETTY_PRINT) . "\n";
```
The above example will output:
```
Set all prices to 10:
{
    "store": {
        "book": [
            {
                "category": "reference",
                "author": "Nigel Rees",
                "title": "Sayings of the Century",
                "price": 10
            },
            {
                "category": "fiction",
                "author": "Evelyn Waugh",
                "title": "Sword of Honour",
                "price": 10
            },
            {
                "category": "fiction",
                "author": "Herman Melville",
                "title": "Moby Dick",
                "isbn": "0-553-21311-3",
                "price": 10
            },
            {
                "category": "fiction",
                "author": "J. R. R. Tolkien",
                "title": "The Lord of the Rings",
                "isbn": "0-395-19395-8",
                "price": 10
            }
        ],
        "bicycle": {
            "color": "red",
            "price": 10
        }
    }
}
Set prices alternately to 1, 2 and 3:
{
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
                "author": "Evelyn Waugh",
                "title": "Sword of Honour",
                "price": 2
            },
            {
                "category": "fiction",
                "author": "Herman Melville",
                "title": "Moby Dick",
                "isbn": "0-553-21311-3",
                "price": 3
            },
            {
                "category": "fiction",
                "author": "J. R. R. Tolkien",
                "title": "The Lord of the Rings",
                "isbn": "0-395-19395-8",
                "price": 1
            }
        ],
        "bicycle": {
            "color": "red",
            "price": 2
        }
    }
}

```
## Set values and create non-existent paths
The following example shows how to set values on non-existent paths.
```php
<?php
$evaluator = new \Ropi\JsonPathEvaluator\JsonPathEvaluator();


echo "Create non-existent paths as stdClass objects:\n";
$data = json_decode('{}');

$evaluator->setValues($data, '$.deep.path.value', ["new-value"], \Ropi\JsonPathEvaluator\NonExistentPathBehavior::CreateStdClass);

var_dump($data) . "\n";



echo "Create non-existent paths as arrays:\n";
$data = json_decode('[]');

$evaluator->setValues($data, '$.deep.path.value', ["new-value"], \Ropi\JsonPathEvaluator\NonExistentPathBehavior::CreateArray);

var_dump($data) . "\n";
```
The above example will output:
```
Create non-existent paths as stdClass objects:
object(stdClass)#8 (1) {
  ["deep"]=>
  object(stdClass)#37 (1) {
    ["path"]=>
    object(stdClass)#32 (1) {
      ["value"]=>
      string(9) "new-value"
    }
  }
}
Create non-existent paths as arrays:
array(1) {
  ["deep"]=>
  array(1) {
    ["path"]=>
    array(1) {
      ["value"]=>
      string(9) "new-value"
    }
  }
}

```
## Delete values
The following example shows how to delete/remove/unset values.
```php
<?php
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


$evaluator = new \Ropi\JsonPathEvaluator\JsonPathEvaluator();


echo "Delete all books that are more expensive than 9 euros:\n";

$evaluator->deleteValues($data, '$.store.book[@.price > 9]');

echo json_encode($data, JSON_PRETTY_PRINT) . "\n";
```
The above example will output:
```
Delete all books that are more expensive than 9 euros:
{
    "store": {
        "book": {
            "0": {
                "category": "reference",
                "author": "Nigel Rees",
                "title": "Sayings of the Century",
                "price": 8.95
            },
            "2": {
                "category": "fiction",
                "author": "Herman Melville",
                "title": "Moby Dick",
                "isbn": "0-553-21311-3",
                "price": 8.99
            }
        },
        "bicycle": {
            "color": "red",
            "price": 399
        }
    }
}

```
## Custom function extensions
The following example shows how to register custom function extensions according to section 2.4 of [JSONPath internet draft](https://datatracker.ietf.org/doc/draft-ietf-jsonpath-base/21/).
```php
<?php
$evaluator = new \Ropi\JsonPathEvaluator\JsonPathEvaluator();

$data = json_decode('{
    "values": [
        {"property": "valueA"},
        {"property": "valueB"}
    ]
}');

$evaluator = new \Ropi\JsonPathEvaluator\JsonPathEvaluator();

$evaluator->registerFunction('myFunction', function(\Ropi\JsonPathEvaluator\Types\AbstractValueType $parameter1) {
    if (!$parameter1 instanceof \Ropi\JsonPathEvaluator\Types\JsonValue) {
        return new \Ropi\JsonPathEvaluator\Types\LogicalFalse();
    }

    return $parameter1->getValue() === 'valueB'
        ? new \Ropi\JsonPathEvaluator\Types\LogicalTrue()
        : new \Ropi\JsonPathEvaluator\Types\LogicalFalse();
});

$result = $evaluator->getValues($data, '$.values[?myFunction(@.property)].property');

echo json_encode($result, JSON_PRETTY_PRINT) . "\n";
```
The above example will output:
```
[
    "valueB"
]

```