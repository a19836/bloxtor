<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

//include_once get_lib("org.phpframework.phpscript.PHPUICodeExpressionHandler");

class HashTagParameter {
	
	/*
	 * Note that the SQL_HASH_TAG_PARAMETER_PARTIAL_REGEX is different from the HTML_HASH_TAG_PARAMETER_PARTIAL_REGEX. The SQL_HASH_TAG_PARAMETER_PARTIAL_REGEX does NOT allow calls like #foo[0]#. Only simple calls like #foo#!
	 * Otherwise the automatic creation of business logic and presentation UIs will NOT work!
	 */
	const SQL_HASH_TAG_PARAMETER_PARTIAL_REGEX = "#([\w \-\+\.\>\<]+)#";
	const SQL_HASH_TAG_PARAMETER_FULL_REGEX = "/" . self::SQL_HASH_TAG_PARAMETER_PARTIAL_REGEX . "/u"; //'\w' means all words with '_' and '/u' means with accents and ç too.
	
	/*
	 * Do not add the /#([^#]+)#/ bc if the $value contains html with css it will the css colors with attributes, this is:
	 * 	.test1 {color:#000;}  .test2 {color:#000; background:#ccc; border:1px solid #000;} 
	 * Then the regex /#([^#]+)#/ will get the matches: ["#000;}  .test2 {color:#", "#ccc; border:1px solid #"], and this is not what we want.
	 *
	 * The correct regex is: /#([\w"' \-\+\[\]\.\\\$]+)#/u which means that includes accents and ç, this is, '\w' means all words with '_' and '/u' means with accents and ç too. '/u' converts unicode to accents chars.
	 *
	 * '\w' means all words with '_' and '/u' means with accents and ç too.
	 * The '\' in the regex is bc we want to parse the cases of #[\$idx][name]#
	 */
	const HTML_TAG_PARAMETER_PARTIAL_REGEX = "([\w\"' \-\+\[\]\.\\\$\\\\]+)"; 
	const HTML_HASH_TAG_PARAMETER_PARTIAL_REGEX = "#" . self::HTML_TAG_PARAMETER_PARTIAL_REGEX . "#"; 
	const HTML_HASH_TAG_PARAMETER_FULL_REGEX = "/" . self::HTML_HASH_TAG_PARAMETER_PARTIAL_REGEX . "/u"; 
	
	const HTML_SUPER_GLOBAL_PARTIAL_REGEX = "(_GET|_POST|_REQUEST|_FILES|_COOKIE|_ENV|_SERVER|_SESSION|GLOBALS)";
	const HTML_SUPER_GLOBAL_HASH_TAG_PARAMETER_FULL_REGEX = "/#" . self::HTML_SUPER_GLOBAL_PARTIAL_REGEX . "(|\[" . self::HTML_TAG_PARAMETER_PARTIAL_REGEX . ")#/u";
	const HTML_SUPER_GLOBAL_VAR_NAME_FULL_REGEX = "/^" . self::HTML_SUPER_GLOBAL_PARTIAL_REGEX . "($|\[)/";
	
	/*
	 * Note that some files contain the same regex than the HTML_HASH_TAG_PARAMETER_FULL_REGEX, but translated in javascript, this is:
	 *  - app/lib/org/phpframework/workflow/task/programming/common/webroot/js/PTLFieldsUtilObj.js
	 *  		/#([\w\u00C0-\u00D6\u00D8-\u00F6\u00F8-\u024F\u1EBD\u1EBC"' \-\+\[\]\.\$]+)#/g
	 *  - app/__system/layer/presentation/common/webroot/vendor/myhtmlbeautify/MyHtmlBeautify.js
	 *  		/\{\s*(\\?)\s*(\$[\w\u00C0-\u00D6\u00D8-\u00F6\u00F8-\u024F\u1EBD\u1EBC"' \-\+\[\]\.\$]+)\s*\}/g
	 *
	 * Which means that any changes in the HTML_HASH_TAG_PARAMETER_PARTIAL_REGEX should be updated in this other files too.
	 */
	
	/*
	 * Then there are other places with other regexes that we should play attention too:
	 *  - app/__system/layer/presentation/phpframework/src/util/CMSPresentationFormSettingsUIHandler.php
	 * 		/#([\w \-\+\.]+)#/iu
	 *
	 * Which means that any changes in the HTML_HASH_TAG_PARAMETER_PARTIAL_REGEX should be updated in this other files too, but with carefull, this is, in the case of the '/#([\w \-\+\.]+)#/iu' regex in CMSPresentationFormSettingsUIHandler.php, we only need to get the hash-tags-parameters with the characters: [, ], $, ' or ".
	 */
	
	/*
	 * To search if there are more places where #xxx# can be, please open your terminal and type:
	 * 	cd /var/www/html/phpframework/trunk
	 * 	grep -r "#" app/lib/org/phpframework/ app/__system/layer/presentation/phpframework/ | grep '\\w' | grep -v "/cache" | grep -v "/vendor/" | grep -v "/docbook/" | grep -v "\.ser$" | grep "\#"
	 */
	
	/*
	 * checks if exists hash-tag-parameters, like #foo#, in $text.
	 */
	public static function existsHTMLHashTagParameters($text) {
		$regex = HashTagParameter::HTML_HASH_TAG_PARAMETER_FULL_REGEX;
		
		return $text && is_string($text) && strpos($text, "#") !== false && preg_match($regex, $text);
	}
	
	/*
	 * checks if exists global-hash-tag-parameters, like #_GET[foo]#, in $text.
	 */
	public static function existsHTMLSuperGlobalHashTagParameters($text) {
		$regex = HashTagParameter::HTML_SUPER_GLOBAL_HASH_TAG_PARAMETER_FULL_REGEX;
		
		return $text && is_string($text) && strpos($text, "#") !== false && preg_match($regex, $text);
	}
	
	/*
	 * replaces all hash-tag-parameters, like #foo#, in $text by the correspondent $values.
	 * $replace_by_filter could be an array with names or regexes or a regex string
	 */
	public static function replaceHTMLHashTagParametersWithValues($text, $values, $replace_by_filter = null, $replace_global_vars = true) {
		$items = self::getHTMLHashTagParametersValues($text, $replace_by_filter, $replace_global_vars, "values");
		//echo "<pre>";print_r($items);
		//error_log("replaceHTMLHashTagParametersWithValues values:".print_r($values, 1)."\n", 3, $GLOBALS["log_file_path"] ? $GLOBALS["log_file_path"] : "/var/www/html/livingroop/default/tmp/phpframework.log");
		//error_log("replaceHTMLHashTagParametersWithValues items:".print_r($items, 1)."\n", 3, $GLOBALS["log_file_path"] ? $GLOBALS["log_file_path"] : "/var/www/html/livingroop/default/tmp/phpframework.log");
		
		foreach ($items as $hash_tag => $replacement) 
			if ($replacement) {
				eval('$replacement = ' . $replacement . ';');
				$text = str_replace($hash_tag, $replacement, $text);
			}
		
		return $text;
	}
	
	/*
	 * get all hash-tag-parameters, like #foo#, in $text.
	 * $replace_by_filter could be an array with names or regexes or a regex string
	 */
	public static function getHTMLHashTagParametersValues($text, $replace_by_filter = null, $replace_global_vars = true, $values_var_name = "values") {
		$items = array();
		
		if ($text && is_string($text) && strpos($text, "#") !== false) {
			//prepare filter regex
			$filter_regexes = array();
			
			if (is_array($replace_by_filter) && count($replace_by_filter)) //in case the filter be an array with names or regexes
				$filter_regexes = array_map(function($v) { 
					return substr($v, 0, 1) == "/" ? $v : "/#(\[|)(\"|')?" . preg_quote($v) . "(\"|')?(\]|\[|#)/"; 
				}, $replace_by_filter);
			else if ($replace_by_filter && !is_array($replace_by_filter)) //in case the filter be a regex or a simple name
				$filter_regexes[] = substr($replace_by_filter, 0, 1) == "/" ? $replace_by_filter : "/#(\[|)(\"|')?$replace_by_filter(\"|')?(\]|\[|#)/";
			//echo "filter_regexes:".print_r($filter_regexes, 1)."<br><textarea>$text</textarea>";
			
			//get hashtags
			$regex = HashTagParameter::HTML_HASH_TAG_PARAMETER_FULL_REGEX;
			preg_match_all($regex, $text, $matches, PREG_OFFSET_CAPTURE);//PREG_PATTERN_ORDER 
			
			//parse hashtags
			if (!empty($matches[1])) {
				$global_vars = $replace_global_vars ? array("_POST", "_GET", "_GLOBALS", "_ENV") : array();
				$t = count($matches[1]);
				
				for ($i = 0; $i < $t; $i++) {
					$m = $matches[1][$i][0];
					$replacement = "";
					//echo "m($text):$m<br>";
					
					//check if hashtag starts with global var
					$exists_global_var = false;
					foreach ($global_vars as $gv)
						if (stripos($m, $gv) === 0) {
							$exists_global_var = true;
							break;
						}
					
					//check if hashtag matches with any of the regexes
					$exists_in_filter = true; //by default sets to true, bc if there is no filter, it means we are allowing all hashtags
					if ($filter_regexes) {
						$exists_regex = false;
						
						foreach ($filter_regexes as $filter_regex) 
							if (preg_match($filter_regex, $matches[0][$i][0])) { 
								$exists_regex = true;
								break;
							}
						
						$exists_in_filter = $exists_regex;
					}
					//error_log("getHTMLHashTagParametersValues for '#$m#':\n- exists_global_var:$exists_global_var\n- exists_in_filter:$exists_in_filter\n", 3, $GLOBALS["log_file_path"] ? $GLOBALS["log_file_path"] : "/var/www/html/livingroop/default/tmp/phpframework.log");
					
					//parse hashtag
					if (strpos($m, "[") !== false || strpos($m, "]") !== false) { //if value == #[0]name# or #[$idx - 1][name]#, returns $results[0]["name"] or $results[$idx - 1]["name"]
						preg_match_all("/([^\[\]]+)/u", trim($m), $sub_matches, PREG_PATTERN_ORDER); //'/u' means converts to unicode.
						$sub_matches = isset($sub_matches[1]) ? $sub_matches[1] : null;
						
						if ($sub_matches) {
							//echo "1:";print_r($sub_matches);
							
							if ($exists_global_var)
								$gv = array_shift($sub_matches);
							
							$t2 = count($sub_matches);
							$keys = array();
							
							for ($j = 0; $j < $t2; $j++) {
								$sub_match = trim($sub_matches[$j]);
								
								if (strlen($sub_match)) { //ignores empty keys
									if (strpos($sub_match, "'") === false && strpos($sub_match, '"') === false) { //avoid php errors because one of the keys is a RESERVED PHP CODE string.
										//$sub_match_type = PHPUICodeExpressionHandler::getValueType($sub_match, array("non_set_type" => "string", "empty_string_type" => "string"));
										//$sub_matches[$j] = PHPUICodeExpressionHandler::getArgumentCode($sub_match, $sub_match_type);
										$sub_matches[$j] = '"' . $sub_match . '"';
									}
									
									$keys[] = $sub_matches[$j];
								}
							}
							
							if ($exists_global_var)
								$replacement = '$' . strtoupper($gv) . (count($keys) ? '[' . implode('][', $keys) . ']' : '');
							else if ($exists_in_filter) //only if exists in filter
								$replacement = '$' . $values_var_name . (count($keys) ? '[' . implode('][', $keys) . ']' : '');
						}
					}
					else if ($exists_global_var) //if #_POST# or #_GET#
						$replacement = '$' . $m;
					else if ($exists_in_filter) //if $text == #name#, returns $results["name"], but only if exists in filter
						$replacement = '$' . $values_var_name . '["' . $m . '"]';
					
					//set replacemente if exists
					if ($replacement)
						$items["#$m#"] = $replacement;
				}
			}
		}
		
		return $items;
	}
}
?>
