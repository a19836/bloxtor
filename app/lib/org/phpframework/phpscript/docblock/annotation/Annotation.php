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

namespace DocBlockParser\Annotation;

include_once get_lib("org.phpframework.object.ObjectHandler");
include_once get_lib("org.phpframework.object.ObjTypeHandler");
include_once get_lib("org.phpframework.phpscript.docblock.annotation.exception.AnnotationException");

abstract class Annotation {
	protected $vectors;
	protected $is_input = false;
	protected $is_output = false;
	
	protected $args;
	protected $errors = array();
	
	public abstract function parseArgs($DocBlockParser, $args);
	public abstract function checkMethodAnnotations(&$method_params_data, $annotation_idx);
	
	private $xss_sanitize_lib_included = false;
	
	protected static function getConfiguredArgs($args) {
		$new_args = array();
		
		if (is_array($args)) {
			if (isset($args["default"]))
				$default = $args["default"];
			
			if (isset($args["type"]))
				$type = $args["type"];
			
			if (isset($args["mandatory"])) //only for params
				$mandatory = $args["mandatory"];
			
			if (isset($args["notnull"]) || isset($args["not_null"])) 
				$not_null = isset($args["notnull"]) ? $args["notnull"] : $args["not_null"];
			
			if (isset($args["add_sql_slashes"]))
				$add_sql_slashes = $args["add_sql_slashes"];
			
			if (isset($args["sanitize_html"])) //sanitize_html will remove all inline javascript bc of XSS attacks
				$sanitize_html = $args["sanitize_html"];
			
			//length
			if (isset($args["min_length"]))
				$min_length = $args["min_length"];
			else if (isset($args["min_size"]))
				$min_length = $args["min_size"];
			
			if (isset($args["max_length"]))
				$max_length = $args["max_length"];
			else if (isset($args["max_size"]))
				$max_length = $args["max_size"];
			else if (isset($args["length"]))
				$max_length = $args["length"];
			else if (isset($args["size"]))
				$max_length = $args["size"];
			
			//words count
			if (isset($args["min_words"]))
				$min_words = $args["min_words"];
			
			if (isset($args["max_words"]))
				$max_words = $args["max_words"];
			
			//value
			if (isset($args["min_value"]))
				$min_value = $args["min_value"];
			
			if (isset($args["max_value"]))
				$max_value = $args["max_value"];
			
			foreach ($args as $k => $v) {
				if (is_numeric($k)) {
					$lv = strtolower($v);
					
					if ($lv == "notnull" || $lv == "not_null" || $lv == "@notnull" || $lv == "@not_null")
						$not_null = true;
					else if (strpos($lv, "@notnull") === 0 || strpos($lv, "@not_null") === 0)
						$not_null = self::getParamAnnotationValue($v);
					else if (strpos($lv, "@default") === 0)
						$default = self::getParamAnnotationValue($v);
					else if (strpos($lv, "@mandatory") === 0)
						$mandatory = self::getParamAnnotationValue($v);
					else if (strpos($lv, "@type") === 0)
						$type = self::getParamAnnotationValue($v);
					else if (strpos($lv, "@addsqlslashes") === 0)
						$add_sql_slashes = self::getParamAnnotationValue($v);
					else if (strpos($lv, "@sanitizehtml") === 0)
						$sanitize_html = self::getParamAnnotationValue($v);
					else if (strpos($lv, "@minlength") === 0 || strpos($lv, "@minsize") === 0)
						$min_length = self::getParamAnnotationValue($v);
					else if (strpos($lv, "@maxlength") === 0 || strpos($lv, "@length") === 0 || strpos($lv, "@maxsize") === 0 || strpos($lv, "@size") === 0)
						$max_length = self::getParamAnnotationValue($v);
					else if (strpos($lv, "@minwords") === 0)
						$min_words = self::getParamAnnotationValue($v);
					else if (strpos($lv, "@maxwords") === 0)
						$max_words = self::getParamAnnotationValue($v);
					else if (strpos($lv, "@minvalue") === 0)
						$min_value = self::getParamAnnotationValue($v);
					else if (strpos($lv, "@maxvalue") === 0)
						$max_value = self::getParamAnnotationValue($v);
					else if (trim(substr($v, 0, 1)) == "@") {
						$func = self::getParamAnnotationName($v);
						$new_args["others"][$func][] = $v;
					}
				}
			}
			
			if (isset($default))
				$new_args["default"] = self::parseValue($default);
			
			if (isset($mandatory))
				$new_args["mandatory"] = self::parseValue($mandatory) ? true : false;
			
			if (isset($type))
				$new_args["type"] = $type;
			
			if (isset($not_null) && $not_null)
				$new_args["not_null"] = self::parseValue($not_null) ? true : false;
			
			if (isset($add_sql_slashes))
				$new_args["add_sql_slashes"] = self::parseValue($add_sql_slashes) ? true : false;
			
			if (isset($sanitize_html))
				$new_args["sanitize_html"] = self::parseValue($sanitize_html) ? true : false;
			
			if (isset($min_length) && is_numeric($min_length))
				$new_args["min_length"] = $min_length;
			
			if (isset($max_length) && is_numeric($max_length))
				$new_args["max_length"] = $max_length;
			
			if (isset($min_words) && is_numeric($min_words))
				$new_args["min_words"] = $min_words;
			
			if (isset($max_words) && is_numeric($max_words))
				$new_args["max_words"] = $max_words;
			
			if (isset($min_value) && is_numeric($min_value))
				$new_args["min_value"] = $min_value;
			
			if (isset($max_value) && is_numeric($max_value))
				$new_args["max_value"] = $max_value;
			
			if (isset($args["desc"]))
				$new_args["desc"] = self::parseValue($args["desc"]);
		}
		
		//echo "<pre>";print_r($new_args);die();
		return $new_args;
	}
	
	protected function checkValueAnnotations(&$method_param_value, &$value_changed = false) {
		//echo "arg name:".$this->args["name"]."<br>";
		
		if (is_array($this->args)) {
			//echo "<pre>".print_r($this->args, 1)."</pre>";//die();
			
			$mpv = $method_param_value;
			
			//if default value exists and if the method_param_value is not isset
			if (isset($this->args["default"]) && !isset($method_param_value)) {
				//echo "<pre>Value(".$this->args["default"]."): ";echo $this->getDefaultValue($this->args["default"], $status);echo "status:$status</pre><br>";
				
				$method_param_value = $this->getDefaultValue($this->args["default"], $status);
				$value_changed = $method_param_value != $mpv;
				
				if (!$status)
					return false;
			}
			
			if (isset($this->args["not_null"]) && !$this->checkNotNull($method_param_value))
				return false;
			
			if (isset($method_param_value)) {
				//Note that if TYPE exists (like bigint) and $method_param_value can be NULL (not_null=0) and $method_param_value is an EMPTY string, so it should NOT verify the type, bc the $method_param_value can be NULL. The ALLOW NULL check-up is done before the code gets here.
				//To verifiy the type, the $method_param_value must exists or it cannot be NULL (not_null=1)
				$is_method_param_value_empty = (is_array($method_param_value) && count($method_param_value) == 0) || (!is_array($method_param_value) && !is_object($method_param_value) && mb_strlen($method_param_value) == 0); //note that I cannot use is_string bc the numeric value are not strings...
				
				if (!empty($this->args["type"]) && !$is_method_param_value_empty && !$this->checkType($method_param_value, $this->args["type"]))
					return false;
			
				if (isset($this->args["min_length"]) && !$this->checkMinLength($method_param_value, $this->args["min_length"]))
					return false;
			
				if (isset($this->args["max_length"]) && !$this->checkMaxLength($method_param_value, $this->args["max_length"])) {
					//Check if exists @lstrcut, @mstrcut or @rstrcut and change $method_param_value accordingly
					if (is_string($method_param_value)) {
						$max_length = $this->args["max_length"];
						
						if (!empty($this->args["others"]["lstrcut"])) {
							$method_param_value = substr($method_param_value, 0, $max_length);
							$value_changed = $method_param_value != $mpv;
						}
						else if (!empty($this->args["others"]["mstrcut"])) {
							$m2 = (int) ($max_length / 2);
							$method_param_value = substr($method_param_value, 0, $m2) . substr($method_param_value, -$m2);
							$value_changed = $method_param_value != $mpv;
						}
						else if (!empty($this->args["others"]["rstrcut"])) {
							$method_param_value = substr($method_param_value, -$max_length);
							$value_changed = $method_param_value != $mpv;
						}
						else
							return false;
						
						//error_log(print_r($this->args, 1) . "\nmethod_param_value:$method_param_value", 3, "/tmp/log.log");
					}
					else
						return false;
				}
				
				if (is_string($method_param_value)) {
					if (isset($this->args["min_words"]) && !$this->checkMinWords($method_param_value, $this->args["min_words"]))
						return false;
				
					if (isset($this->args["max_words"]) && !$this->checkMaxWords($method_param_value, $this->args["max_words"]))
						return false;
				}
				
				if (is_numeric($method_param_value)) {
					if (isset($this->args["min_value"]) && !$this->checkMinValue($method_param_value, $this->args["min_value"]))
						return false;
				
					if (isset($this->args["max_value"]) && !$this->checkMaxValue($method_param_value, $this->args["max_value"]))
						return false;
				}
				
				if (isset($this->args["add_sql_slashes"]) && !$this->isBinary($method_param_value) && is_string($method_param_value)) { //only if not binary and if not array  - must be string to avoid this cases
					$method_param_value = addcslashes($method_param_value, "\\'");
					$value_changed = $method_param_value != $mpv;
				}
				
				if (isset($this->args["sanitize_html"])) {
					if (!$this->xss_sanitize_lib_included)
						include_once get_lib("org.phpframework.util.web.html.XssSanitizer"); //leave this here, otherwise it could be over-loading for every request to include without need it...
					
					$this->xss_sanitize_lib_included = true;
					
					$method_param_value = \XssSanitizer::sanitizeVariable($method_param_value);
					$value_changed = $method_param_value != $mpv;
				}
			}
		}
		
		return true;
	}
	
	private static function getParamAnnotationName($text) {
		preg_match_all("/^@(\w+)([^\w]*)/u", $text, $matches, PREG_PATTERN_ORDER); //'\w' means all words with '_' and '/u' means with accents and ç too.
		
		if (!empty($matches[0])) 
			return self::parseValue($matches[1][0]);
		return null;
	}
	
	private static function getParamAnnotationValue($text) {
		preg_match_all("/^@(\w+)(\s*)\(([^\)]*)\)/u", $text, $matches, PREG_PATTERN_ORDER); //'\w' means all words with '_' and '/u' means with accents and ç too.
		
		if (!empty($matches[0])) 
			return self::parseValue($matches[3][0]);
		return null;
	}
	
	private function checkMinLength($value, $length) { //checks min chars/items
		if (isset($value) && ((is_array($value) && count($value) >= $length) || (!is_array($value) && !is_object($value) && mb_strlen($value) >= $length)))
			return true;
		
		$this->errors[] = self::encodeValueForPrinting($value) . " length is smaller than $length";
		return false;
	}
	
	private function checkMaxLength($value, $length) { //checks max chars/items
		if (!isset($value) || (is_array($value) && count($value) <= $length) || (!is_array($value) && !is_object($value) && mb_strlen($value) <= $length))
			return true;
		
		$this->errors[] = self::encodeValueForPrinting($value) . " length is bigger than $length";
		return false;
	}
	
	private function checkMinWords($value, $length) { //checks min words
		if (isset($value) && !is_array($value) && !is_object($value) && count(preg_split('/\s+/', trim($value))) >= $length) //Do not use str_word_count bc it does not count the numbers, this is "jp 12" will return 1 word. And in this case scenario we need to return 2 words: jp + 12
			return true;
		
		$this->errors[] = self::encodeValueForPrinting($value) . " words count is smaller than $length";
		return false;
	}
	
	private function checkMaxWords($value, $length) { //checks max words
		if (isset($value) && !is_array($value) && !is_object($value) && count(preg_split('/\s+/', trim($value))) <= $length) //Do not use str_word_count bc it does not count the numbers, this is "jp 12" will return 1 word. And in this case scenario we need to return 2 words: jp + 12
			return true;
		
		$this->errors[] = self::encodeValueForPrinting($value) . " words count is bigger than $length";
		return false;
	}
	
	private function checkMinValue($value, $v) { //checks min value
		if (isset($value) && is_numeric($value) && $value >= $v)
			return true;
		
		$this->errors[] = self::encodeValueForPrinting($value) . " is smaller than $v";
		return false;
	}
	
	private function checkMaxValue($value, $v) { //checks max value
		if (isset($value) && is_numeric($value) && $value <= $v)
			return true;
		
		$this->errors[] = self::encodeValueForPrinting($value) . " is bigger than $v";
		return false;
	}
	
	private function checkNotNull($value) {
		if (isset($value))
			return true;
		
		$this->errors[] = self::encodeValueForPrinting($value) . " cannot be null";
		return false;
	}
	
	private function checkType($value, $type) {
		$parts = explode("|", $type);
		$types = array();
		
		foreach ($parts as $t) {
			$t = trim($t);
			
			if ($t) {
				if (strtolower($t) == "mixed")
					return true;
				
				$types[] = $t;
			}
		}
		
		if ($types) {	
			foreach ($types as $t) {
				$t = \ObjTypeHandler::convertSimpleTypeIntoCompositeType($t);
				
				try {
					$obj = \ObjectHandler::createInstance($t);
				
					if (\ObjectHandler::checkIfObjType($obj) && $obj->setInstance($value))
						return true;
				}
				catch (\Throwable $e) { //includes Exception, Error, ParseError, etc...
					//$message = "[Annotation::checkType] Exception: " . $e->getMessage() . "\n   In file " . $e->getFile() . ":" . $e->getLine() . "\n   With code traces:\n\t" . str_replace("\n", "\n\t", $e->getTraceAsString());
					//debug_log($message, "exception");
					
					global $GlobalErrorHandler;
					$GlobalErrorHandler && $GlobalErrorHandler->start();
					
					//Don't do anything else because it will launch another exception later.
				}
				catch (\Exception $e) { //for php 5.6 because Throwable is only for PHP 7+
					global $GlobalErrorHandler;
					$GlobalErrorHandler && $GlobalErrorHandler->start();
					
					//Don't do anything else because it will launch another exception later.
				}
			}
		
			$this->errors[] = self::encodeValueForPrinting($value) . " is not a " . implode(" or ", $types);
			return false;
		}
		
		return true;
	}
	
	private function isArgTypeNumericType($type) {
		$parts = explode("|", $type);
		$types = array();
		
		foreach ($parts as $t) {
			$t = trim($t);
			
			if ($t) {
				if (strtolower($t) == "mixed")
					return false;
				
				$types[] = $t;
			}
		}
			
		if ($types) {	
			foreach ($types as $t) {
				$t = \ObjTypeHandler::convertSimpleTypeIntoCompositeType($t);
				
				if (!\ObjTypeHandler::isPHPTypeNumeric($t))
					return false;
				
			}
			
			return true;
		}
		
		return false;
	}
	
	private function isBinary($value) {
		return is_string($value) && preg_match('~[^\x20-\x7E\t\r\n]~', $value) > 0;
	}
	
	private function encodeValueForPrinting($value) {
		return preg_replace_callback('/\\\\u([0-9a-fA-F]{4})/', function ($match) {
		    return mb_convert_encoding(pack('H*', $match[1]), 'UTF-8', 'UCS-2BE');
		}, json_encode($value));
	}
	
	protected static function parseValue($value) {
		$value = trim($value);
		
		if ($value) {
			$fc = substr($value, 0, 1);
			$lc = substr($value, -1);
			
			if ($fc == "[" || $fc == "{")
				$value = json_decode($value);
			else if ($fc == '$' || substr($value, 0, 2) == '@$')
				eval ('$value = ' . $value . ';');
			else if (($fc == '"' && $lc == '"') || ($fc == "'" && $lc == "'"))
				$value = substr($value, 1, -1);
			else {
				$lv = strtolower($value);
				
				if ($lv == "true")
					$value = true;
				else if ($lv == "false") 
					$value = false;
				else if ($lv == "null") 
					$value = null;
			}
		}
		return $value;
	}
	
	protected function getDefaultValue($value, &$status) {
		$status = true;
		
		if (substr($value, 0, 1) == "@") {//is a php function to be executed
			try {
				$status = eval('$value = ' . substr($value, 1) . '; return 1;');
			} 
			catch (Exception $e) {
				$status = false;
				$n = $this->is_input ? 'param: ' . (isset($this->args["name"]) ? $this->args["name"] : "") : 'return';
				launch_exception(new AnnotationException(1, $e, array($n, substr($value, 1))));
			}
			
			if (!$status) {
				if (empty($n))
					$n = $this->is_input ? 'param: ' . (isset($this->args["name"]) ? $this->args["name"] : "") : 'return';
				
				$this->errors[] = "Error in annotation ' . $n . ', when executing php function: " . substr($value, 1);
			}
		}
		
		return $value;
	}
	
	public function getVectors() { return $this->vectors; }
	public function isInput() { return $this->is_input; }
	public function isOutput() { return $this->is_output; }
	public function getArgs() { return $this->args; }
	public function getErrors() { return $this->errors; }
}
?>
