<?php
@include_once $EVC->getModulePath("translator/include_text_translator_handler", $EVC->getCommonProjectName());//@ in case it doens't exist

if (!function_exists("translateText")) {
	function translateText($EVC, $text, $category = null, $lang = null) {
		return $text;
	}

	if (!function_exists("translateCategoryText")) {
		function translateCategoryText($EVC, $text, $category = null, $lang = null) {
			return $text;
		}
	}

	if (!function_exists("translateProjectLabel")) {
		function translateProjectLabel($EVC, $text, $project = null, $lang = null) {
			return $text;
		}
	}

	if (!function_exists("translateProjectText")) {
		function translateProjectText($EVC, $text, $project = null, $lang = null) {
			return $text;
		}
	}

	if (!function_exists("translateProjectFormSettings")) {
		function translateProjectFormSettings($EVC, &$form_settings, $project = null, $lang = null) {
			return $form_settings;
		}
	}

	if (!function_exists("translateProjectFormSettingsElement")) {
		function translateProjectFormSettingsElement($EVC, &$form_element, $project = null, $lang = null) {
			return $form_element;
		}
	}

	if (!function_exists("initTextTranslatorHandler")) {
		function initTextTranslatorHandler($EVC) {
			return null;
		}
	}

	if (!function_exists("initMyJSLibTranslations")) {
		function initMyJSLibTranslations($EVC, $project = null, $lang = null) {
			return "";
		}
	}
}
?>
