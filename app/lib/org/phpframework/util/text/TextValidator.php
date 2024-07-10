<?php
class TextValidator {
	
	public static function isBinary($value) {
		return preg_match('~[^\x20-\x7E\t\r\n]~', $value);
	}
	
	public static function isEmail($value) {
		if(preg_match("/^([a-z0-9\+\-\_\.]+)(\@)([a-z0-9\-\_\.]+)\.([a-z]{2,10})$/i", $value))
			return strpos($value, "..") === false;
		return false;
	}
	
	public static function isDomain($value) {
		if(preg_match("/^([a-z0-9-_]+\.)*[a-z0-9][a-z0-9-_]+\.[a-z]{2,}$/i", $value))
			return strpos($value, "..") === false;
		return false;
	}
	
	public static function isPhone($value) {
		return preg_match("/^([\+]*)([0-9\- \)\(]*)$/i", $value);
	}
	
	public static function isNumber($value) {
		return is_numeric($value);//preg_match("/^-?[0-9]+$/", $value);
	}
	
	public static function isDecimal($value) {
		return preg_match("/^-?([0-9]+|[0-9]+\.[0-9]+)$/", $value);
	}
	
	public static function isSmallInt($value) {
		return preg_match("/^[0,1]{1}$/", $value);
	}
	
	//Format: yyyy-mm-dd
	public static function isDate($value) {
		return preg_match("/^([0-9]{4})-([0-9]{1,2})-([0-9]{1,2})$/", $value);
	}
	
	//Format: yyyy-mm-dd hh:ii:ss
	public static function isDateTime($value) {
		return preg_match("/^([0-9]{4})-([0-9]{1,2})-([0-9]{1,2})(([ T]{1})([0-9]{1,2}):([0-9]{1,2})(:([0-9]{1,2}))?)?$/", $value);
	}
	
	//Format: hh:ii:ss
	public static function isTime($value) {
		return preg_match("/^([0-9]{1,2}):([0-9]{1,2})(:([0-9]{1,2}))?$/", $value);
	}
	
	public static function isIPAddress($value) {
		return preg_match("/^((25[0-5]|2[0-4][0-9]|1[0-9][0-9]|[1-9][0-9]|[0-9])\.){3}(25[0-5]|2[0-4][0-9]|1[0-9][0-9]|[1-9][0-9]|[0-9])$/", $value);
	}
	
	public static function isFileName($value) {
		return preg_match("/^[\w\-\+\.]+$/u", $value); //'\w' means all words with '_' and '/u' means with accents and ç too.
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
