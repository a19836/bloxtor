<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

//Sanitize XSS (Cross-Site Scripting) Attacks
//load external lib dynamically bc is a LGPL licence, which means our framework must work without this library also. This means if the user doesn't have this library installed or if he removes it, our code still needs to work.
if (file_exists( get_lib("lib.vendor.xsssanitizer.src.Sanitizer") )) {
	include_once get_lib("lib.vendor.xsssanitizer.src.FilterRunnerTrait");
	include_once get_lib("lib.vendor.xsssanitizer.src.FilterInterface");
	include_once get_lib("lib.vendor.xsssanitizer.src.AttributeFinder");
	include_once get_lib("lib.vendor.xsssanitizer.src.TagFinderInterface");
	include_once get_lib("lib.vendor.xsssanitizer.src.TagFinder.ByAttribute");
	include_once get_lib("lib.vendor.xsssanitizer.src.TagFinder.ByTag");
	include_once get_lib("lib.vendor.xsssanitizer.src.Filter.AttributeCleaner");
	include_once get_lib("lib.vendor.xsssanitizer.src.Filter.AttributeContentCleaner");
	include_once get_lib("lib.vendor.xsssanitizer.src.Filter.EscapeTags");
	include_once get_lib("lib.vendor.xsssanitizer.src.Filter.FilterRunner");
	include_once get_lib("lib.vendor.xsssanitizer.src.Filter.MetaRefresh");
	include_once get_lib("lib.vendor.xsssanitizer.src.Filter.RemoveAttributes");
	include_once get_lib("lib.vendor.xsssanitizer.src.Filter.RemoveBlocks");
	include_once get_lib("lib.vendor.xsssanitizer.src.Filter.AttributeContent.CompactExplodedWords");
	include_once get_lib("lib.vendor.xsssanitizer.src.Filter.AttributeContent.DecodeEntities");
	include_once get_lib("lib.vendor.xsssanitizer.src.Filter.AttributeContent.DecodeUtf8");
	include_once get_lib("lib.vendor.xsssanitizer.src.Sanitizer");
}

class XssSanitizer {
	
	public static function sanitizeHtml($html) {
		if ($html && class_exists("Phlib\XssSanitizer\Sanitizer")) {
			$Sanitizer = new Phlib\XssSanitizer\Sanitizer();
			return $Sanitizer->sanitize($html);
		}
		
		return $html;
	}
	
	//remove all script tags and other html tags from variable
	public static function sanitizeVariable($var) {
		if ($var) {
			//error_log("before sanitizeVariable:".print_r($var, 1)."\n\n", 3, $GLOBALS["log_file_path"] ? $GLOBALS["log_file_path"] : "/var/www/html/livingroop/default/tmp/phpframework.log");
			
			if (is_array($var) || is_object($var)) {
				foreach ($var as $k => $v)
					$var[$k] = self::sanitizeVariable($v);
			}
			else
				$var = self::sanitizeHtml($var);
			
			//error_log("after sanitizeVariable:".print_r($var, 1)."\n\n", 3, $GLOBALS["log_file_path"] ? $GLOBALS["log_file_path"] : "/var/www/html/livingroop/default/tmp/phpframework.log");
		}
		
		return $var;
	}
}
?>
