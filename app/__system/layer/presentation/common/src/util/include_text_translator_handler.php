<?php
@include_once $EVC->getModulePath("translator/include_text_translator_handler", $EVC->getCommonProjectName());//@ in case it doens't exist

if (!function_exists("translateProjectLabel")) {
	function translateProjectLabel($EVC, $text, $project = null, $lang = null) {
		return $text;
	}
}
?>
