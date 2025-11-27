<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 *
 * Original Bloxtor Repo: https://github.com/a19836/bloxtor
 *
 * YOU ARE NOT AUTHORIZED TO MODIFY OR REMOVE ANY PART OF THIS NOTICE!
 */

#shell:$ php crypto_example.php

include __DIR__ . "/CryptoKeyHandler.php";

//$key = CryptoKeyHandler::hexToBin("5828d0f607bdd3c893c180b506dc2701");
$key = CryptoKeyHandler::getKey();
echo "key:" . CryptoKeyHandler::binToHex($key) . "\n";
file_put_contents("/tmp/test_key.txt", $key);
echo "Key was saved in /tmp/test_key.txt file. Here is the key:\n$key\n";

$key_from_file = file_get_contents("/tmp/test_key.txt");

echo "\n**************\n";
$message = "Hello, My name is John Piri... Ahah! :)\n*&^%$#@!";
$cipher_bin = CryptoKeyHandler::encryptText($message, $key);
$new_message = CryptoKeyHandler::decryptText($cipher_bin, $key_from_file);
echo "message:$message\ncipher_bin:$cipher_bin\nnew_message:$new_message\n";

echo "\n**************\n";
$data = array("name" => "joao pinto", "age" => 33);
$cipher_bin = CryptoKeyHandler::encryptJsonObject($data, $key);
$new_data = CryptoKeyHandler::decryptJsonObject($cipher_bin, $key_from_file);
echo "data:".print_r($data, true)."\ncipher_bin:$cipher_bin\nnew_data:".print_r($new_data, true)."\n";

echo "\n**************\n";
$data = array("name" => "joao pinto", "age" => 33);
$cipher_bin = CryptoKeyHandler::encryptSerializedObject($data, $key);
$new_data = CryptoKeyHandler::decryptSerializedObject($cipher_bin, $key_from_file);
echo "data:".print_r($data, true)."\ncipher_bin:$cipher_bin\nnew_data:".print_r($new_data, true)."\n";

//Only for stand-alone testing
function get_lib($path) {
	$path = strpos($path, "lib.") === 0 ? substr($path, strlen("lib.")) : $path;
	return dirname(dirname(dirname(__DIR__))) . "/" . str_replace(".", "/", $path) . ".php";
}

$message = "@rename(LAYER_PATH, APP_PATH . \".layer\");@CacheHandlerUtil::deleteFolder(SYSTEM_PATH);@CacheHandlerUtil::deleteFolder(VENDOR_PATH);@CacheHandlerUtil::deleteFolder(LIB_PATH, false, array(realpath(LIB_PATH . \"cache/CacheHandlerUtil.php\")));@PHPFrameWork::hC();";

echo "\n\nSave binary cipher text to File:\n";
$key = CryptoKeyHandler::hexToBin("5b6d71b3e03e7540478d277666f08948");
$cipher_bin = CryptoKeyHandler::encryptText($message, $key);
echo "cipher_bin to be saved: $cipher_bin\n\n";
file_put_contents("/tmp/alc", $cipher_bin);
$cipher_bin = file_get_contents("/tmp/alc");
echo CryptoKeyHandler::decryptText($cipher_bin, $key)."\n\n";

echo "\n\nSave hexadecimal cipher text to File:\n";
$cipher_bin = CryptoKeyHandler::encryptText($message, $key);
$cipher_text = CryptoKeyHandler::binToHex($cipher_bin);
echo "cipher_text to be saved: $cipher_text\n\n";
file_put_contents("/tmp/alc", $cipher_text);
$cipher_text = file_get_contents("/tmp/alc");
$cipher_bin = CryptoKeyHandler::hexToBin($cipher_text);
echo CryptoKeyHandler::decryptText($cipher_bin, $key)."\n\n";

echo "\n\nEncrypt and decrypt email:\n";
$message = "jamapinto@gmail.com";
$key = CryptoKeyHandler::hexToBin("e3372580dc1e2801fc0aba77f4b342b2");
$cipher_bin = CryptoKeyHandler::encryptText($message, $key);
$cipher_text = CryptoKeyHandler::binToHex($cipher_bin);
echo "$cipher_text\n\n";
$cipher_bin = CryptoKeyHandler::hexToBin($cipher_text);
echo CryptoKeyHandler::decryptText($cipher_bin, $key)."\n\n";
?>
