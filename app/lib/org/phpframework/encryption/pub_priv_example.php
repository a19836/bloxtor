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

#shell:$ php example.php

include __DIR__ . "/PublicPrivateKeyHandler.php";

$text = "This is only a test to see if this works... blab ble AHAHA LOL :)";
$root_dir = dirname(dirname(dirname(dirname(__DIR__))));
$public_key_file = $root_dir . "/.app_pub_key.pem";
$private_key_file = $root_dir . "/.app_priv_key.pem";
$passphrase = "***";

$PublicPrivateKeyHandler = new PublicPrivateKeyHandler(true);

echo "String to test: $text\n\n\n";

$encoded_string = $PublicPrivateKeyHandler->encryptString($text, $private_key_file, $passphrase);
echo "Encoded string: $encoded_string\n\n\n";

$decoded_string = $PublicPrivateKeyHandler->decryptString($encoded_string, $public_key_file);
echo "Decoded string: $decoded_string\n";
?>
