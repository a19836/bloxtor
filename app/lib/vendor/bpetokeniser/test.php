<?php
include __DIR__ . "/autoload.php";

use Danny50610\BpeTokeniser\EncodingFactory;

$enc = EncodingFactory::createByEncodingName('cl100k_base');

var_dump($enc->encode("hello world"));
/**
 * output: 
 * array(2) {
 *  [0]=>
 *  int(15339)
 *  [1]=>
 *  int(1917)
 * }
 */

var_dump($enc->decode($enc->encode("hello world")));
// output: string(11) "hello world"

$enc = EncodingFactory::createByModelName('gpt-3.5-turbo');

var_dump($enc->decode($enc->encode("hello world")));
// output: string(11) "hello world"
?>
