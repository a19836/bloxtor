<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

include_once get_lib("org.phpframework.util.text.TextSanitizer");

class TextValidator {
	
	public static function isBinary($value) {
		//note that if the $value contains accents, then it may be in this regex, so we need to remove accents and then check again
		if (is_string($value) && preg_match('~[^\x20-\x7E\t\r\n]~', $value)) {
			$value_without_accents = TextSanitizer::normalizeAccents($value);
			
			return preg_match('~[^\x20-\x7E\t\r\n]~', $value_without_accents);
		}
		
		return false;
	}
	
	public static function isEmail($value) {
		if(is_string($value) && preg_match("/^([a-z0-9\+\-\_\.]+)(\@)([a-z0-9\-\_\.]+)\.([a-z]{2,10})$/i", $value))
			return strpos($value, "..") === false;
		return false;
	}
	
	public static function isDomain($value) {
		if(is_string($value) && preg_match("/^([a-z0-9-_]+\.)*[a-z0-9][a-z0-9-_]+\.[a-z]{2,}$/i", $value))
			return strpos($value, "..") === false;
		return false;
	}
	
	public static function isPhone($value) {
		return (is_string($value) || is_numeric($value)) && preg_match("/^([\+]*)([0-9\- \)\(]*)$/i", $value);
	}
	
	public static function isNumber($value) {
		return is_numeric($value);//(is_string($value) || is_numeric($value)) && preg_match("/^-?[0-9]+$/", $value);
	}
	
	public static function isDecimal($value) {
		return (is_string($value) || is_numeric($value)) && preg_match("/^-?([0-9]+|[0-9]+\.[0-9]+)$/", $value);
	}
	
	public static function isSmallInt($value) {
		return (is_string($value) || is_numeric($value)) && preg_match("/^[0,1]{1}$/", $value);
	}
	
	//Format: yyyy-mm-dd
	public static function isDate($value) {
		return is_string($value) && preg_match("/^([0-9]{4})-([0-9]{1,2})-([0-9]{1,2})$/", $value);
	}
	
	//Format: yyyy-mm-dd hh:ii:ss
	public static function isDateTime($value) {
		return is_string($value) && preg_match("/^([0-9]{4})-([0-9]{1,2})-([0-9]{1,2})(([ T]{1})([0-9]{1,2}):([0-9]{1,2})(:([0-9]{1,2}))?)?$/", $value);
	}
	
	//Format: hh:ii:ss
	public static function isTime($value) {
		return is_string($value) && preg_match("/^([0-9]{1,2}):([0-9]{1,2})(:([0-9]{1,2}))?$/", $value);
	}
	
	public static function isIPAddress($value) {
		return is_string($value) && preg_match("/^((25[0-5]|2[0-4][0-9]|1[0-9][0-9]|[1-9][0-9]|[0-9])\.){3}(25[0-5]|2[0-4][0-9]|1[0-9][0-9]|[1-9][0-9]|[0-9])$/", $value);
	}
	
	public static function isFileName($value) {
		return is_string($value) && preg_match("/^[\w\-\+\.]+$/u", $value); //'\w' means all words with '_' and '/u' means with accents and ç too.
	}
	
	public static function checkMinLength($value, $length) {
		return is_numeric($length) && strlen("$value") >= $length;
	}
	
	public static function checkMaxLength($value, $length) {
		return is_numeric($length) && strlen("$value") <= $length;
	}
	
	public static function checkMinValue($value, $min) {
		return is_numeric($value) && is_numeric($min) && $value >= $min;
	}
	
	public static function checkMaxValue($value, $max) {
		return is_numeric($value) && is_numeric($max) && $value <= $max;
	}
	
	public static function checkMinWords($value, $min) {
		return is_numeric($min) && str_word_count($value) >= $min;
	}
	
	public static function checkMaxWords($value, $max) {
		return is_numeric($max) && str_word_count($value) <= $max;
	}
	
	public static function checkMinDate($value, $min) {
		$v = strtotime($value);
		$m = strtotime($min);
		
		return self::checkMinValue($v, $m);
	}
	
	public static function checkMaxDate($value, $max) {
		$v = strtotime($value);
		$m = strtotime($max);
		
		return self::checkMaxValue($v, $m);
	}
}
?>
