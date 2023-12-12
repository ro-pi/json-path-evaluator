<?php

require_once __DIR__ . '/vendor/autoload.php';

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