# A JSONPath evaluator for PHP

This library is a PHP based implementation of JSONPath ([RFC 9535](https://datatracker.ietf.org/doc/rfc9535/)).

It allows to evaluate JSONPath expressions directly on PHP objects and/or arrays.\
This implementation passes all compliance tests of [JSONPath Compliance Test Suite](https://github.com/jsonpath-standard/jsonpath-compliance-test-suite).

## Requirements
* PHP >= 8.1
* ext-ctype
* ext-intl
* ext-mbstring

## Table of contents
* [Installation](#installation)
* [Examples](#examples)
  * [Get values](#get-values)
  * [Get paths](#get-paths)
  * [Set values](#set-values)
  * [Set values and create non-existent paths](#set-values-and-create-non-existent-paths)
  * [Delete paths](#delete-paths)
  * [Custom function extensions](#custom-function-extensions)

## Installation
The library can be installed from a command line interface by using [composer](https://getcomposer.org/).

```
composer require ropi/json-path-evaluator
```
## Examples

### Get values
The following example shows how to get values that match a JSONPath.\
The result is always an array of values that match the JSONPath. If there are no matches, an empty array is returned.
```php
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

$result = $evaluator->getValues($data, '$.store.book[*].author');
echo "Authors of all books in the store:\n" . json_encode($result, JSON_PRETTY_PRINT) . "\n";

$result = $evaluator->getValues($data, '$.store..price');
echo "Prices of everything in the store:\n" . json_encode($result, JSON_PRETTY_PRINT) . "\n";

$result = $evaluator->getValues($data, '$..book[-1]');
echo "Last book in order:\n" . json_encode($result, JSON_PRETTY_PRINT) . "\n";

$result = $evaluator->getValues($data, '$..book[0,1]');
echo "First two books with union operator:\n" . json_encode($result, JSON_PRETTY_PRINT) . "\n";

$result = $evaluator->getValues($data, '$..book[:2]');
echo "First two books with array slice operator:\n" . json_encode($result, JSON_PRETTY_PRINT) . "\n";

$result = $evaluator->getValues($data, '$..book[?@.isbn]');
echo "All books with an ISBN number:\n" . json_encode($result, JSON_PRETTY_PRINT) . "\n";

$result = $evaluator->getValues($data, '$..book[?@.price<10]');
echo "All books cheaper than 10:\n" . json_encode($result, JSON_PRETTY_PRINT) . "\n";

$result = $evaluator->getValues($data, '$..book[?search(@.isbn, "[1-3]$")]'); // regular expression
echo "All books where ISBN ends with 1, 2 or 3:\n" . json_encode($result, JSON_PRETTY_PRINT) . "\n";
```
The above example will output:
```
Authors of all books in the store:
[
    "Nigel Rees",
    "Evelyn Waugh",
    "Herman Melville",
    "J. R. R. Tolkien"
]
Prices of everything in the store:
[
    8.95,
    12.99,
    8.99,
    22.99,
    399
]
Last book in order:
[
    {
        "category": "fiction",
        "author": "J. R. R. Tolkien",
        "title": "The Lord of the Rings",
        "isbn": "0-395-19395-8",
        "price": 22.99
    }
]
First two books with union operator:
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
First two books with array slice operator:
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
All books with an ISBN number:
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
All books cheaper than 10:
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
All books where ISBN ends with 1, 2 or 3:
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

### Get paths
The following example shows how to get value paths that match a JSONPath, where each value path is represented as normalized JSONPath according to section 2.7 of [RFC 9535](https://datatracker.ietf.org/doc/rfc9535/).\
The result is always an array of matched paths. If there are no matches, an empty array is returned.
```php
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

$result = $evaluator->getPaths($data, '$.store.book[*].author');
echo "Paths of authors of all books in store:\n" . json_encode($result, JSON_PRETTY_PRINT) . "\n";

$result = $evaluator->getPaths($data, '$.store..price');
echo "Paths of prices of everything in the store:\n" . json_encode($result, JSON_PRETTY_PRINT) . "\n";

$result = $evaluator->getPaths($data, '$..book[?@.price<10]');
echo "Paths of all books cheaper than 10:\n" . json_encode($result, JSON_PRETTY_PRINT) . "\n";
```
The above example will output:
```
Paths of authors of all books in store:
[
    "$['store']['book'][0]['author']",
    "$['store']['book'][1]['author']",
    "$['store']['book'][2]['author']",
    "$['store']['book'][3]['author']"
]
Paths of prices of everything in the store:
[
    "$['store']['book'][0]['price']",
    "$['store']['book'][1]['price']",
    "$['store']['book'][2]['price']",
    "$['store']['book'][3]['price']",
    "$['store']['bicycle']['price']"
]
Paths of all books cheaper than 10:
[
    "$['store']['book'][0]",
    "$['store']['book'][2]"
]
```
### Set values
The following example shows how to set/replace values.
```php
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

$numSet = $evaluator->setValues($data, '$..price', [10]);
echo "Set all $numSet prices to 10:\n" . json_encode($data, JSON_PRETTY_PRINT) . "\n";

$numSet = $evaluator->setValues($data, '$..price', [1, 2, 3]);
echo "Set all $numSet alternately to 1, 2 and 3:\n" . json_encode($data, JSON_PRETTY_PRINT) . "\n";
```
The above example will output:
```
Set all 5 prices to 10:
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
Set all 5 alternately to 1, 2 and 3:
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
### Set values and create non-existent paths
The following example shows how to set values on non-existent paths.
```php
$evaluator = new \Ropi\JsonPathEvaluator\JsonPathEvaluator();

// Create non-existent paths as stdClass objects
$data = json_decode('{}');
$evaluator->setValues($data, '$.deep.path.value', ["stdClassExample"], \Ropi\JsonPathEvaluator\NonExistentPathBehavior::CreateStdClass);
var_dump($data) . "\n";

// Create non-existent paths as arrays
$data = json_decode('[]');
$evaluator->setValues($data, '$.deep.path.value', ["arrayExample"], \Ropi\JsonPathEvaluator\NonExistentPathBehavior::CreateArray);
var_dump($data) . "\n";
```
The above example will output:
```
object(stdClass)#8 (1) {
  ["deep"]=>
  object(stdClass)#37 (1) {
    ["path"]=>
    object(stdClass)#32 (1) {
      ["value"]=>
      string(15) "stdClassExample"
    }
  }
}
array(1) {
  ["deep"]=>
  array(1) {
    ["path"]=>
    array(1) {
      ["value"]=>
      string(12) "arrayExample"
    }
  }
}
```
### Delete paths
The following example shows how to delete/remove/unset paths that match a JSONPath.

```php
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

$numDeleted = $evaluator->deletePaths($data, '$.store.book[?@.price > 9]');
echo "Deleted all $numDeleted books that are more expensive than 9:\n" . json_encode($data, JSON_PRETTY_PRINT) . "\n";
```
The above example will output:
```
Deleted all 2 books in store that are more expensive than 9:
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
### Custom function extensions
The following example shows how to register custom function extensions according to section 2.4 of [RFC 9535](https://datatracker.ietf.org/doc/rfc9535/).
```php
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
echo json_encode($result, JSON_PRETTY_PRINT);
```
The above example will output:
```
[
    "valueB"
]
```