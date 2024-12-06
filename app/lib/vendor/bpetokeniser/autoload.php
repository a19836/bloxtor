<?php
if (version_compare(PHP_VERSION, '8.1.0', '<')) {
	include __DIR__ . "/src/php56/Encoding.php";
	include __DIR__ . "/src/php56/EncodingFactory.php";
}
else {
	include __DIR__ . "/src/php81/Encoding.php";
	include __DIR__ . "/src/php81/EncodingFactory.php";
}
?>
