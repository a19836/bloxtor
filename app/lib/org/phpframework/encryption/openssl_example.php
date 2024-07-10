<?php
#shell:$ php openssl_example.php

include __DIR__ . "/OpenSSLCipherHandler.php";

$salt = "some string here. whatever you want!!!";
$text = "some message to be encrypted";

echo "\n**** SIMPLE TEXT TO ENCRYPT ****";
$cipher_text = OpenSSLCipherHandler::encryptText($text, $salt);
$decrypted_text = OpenSSLCipherHandler::decryptText($cipher_text, $salt);

echo "
salt: $salt
text: $text
cipher_text: $cipher_text
decrypted_text: $decrypted_text
";

echo "\n**** ARRAY TO ENCRYPT ****";
$var = array(
	"text1" => "some message 1 to be encrypted",
	"text2" => "some text 2 to be encrypted",
);
$cipher_var = OpenSSLCipherHandler::encryptVariable($var, $salt);
$decrypted_var = OpenSSLCipherHandler::decryptVariable($cipher_var, $salt);
echo "\nsalt: $salt";
echo "\nvar:";print_r($var);
echo "\ncipher_var:";print_r($cipher_var);
echo "\ndecrypted_var:";print_r($decrypted_var);
?>
