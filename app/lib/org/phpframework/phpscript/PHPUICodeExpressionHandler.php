<?php
include_once get_lib("org.phpframework.util.text.TextSanitizer");

class PHPUICodeExpressionHandler {
	
	public static function getArgumentCode($arg, $arg_type) {
		if ((!isset($arg) && $arg_type != "string") || $arg_type == "null") //in case I use the non_set_type=string in the getValueType method
			return "null";
		
		if (!$arg_type && is_string($arg))
			return mb_strlen($arg) ? $arg : "null";
		
		if ($arg_type == "boolean")
			return $arg ? "true" : "false";
		
		if ($arg_type == "variable")
			return (substr($arg, 0, 1) != '$' && substr($arg, 0, 2) != '@$' ? '$' : "") . $arg;
		
		if ($arg_type == "string") {
			/*
			 * 2019-11-28: 
			 * By default the $arg already contains the right number of slashes. The only missing slash is the ". 
			 * 
			 * Do not add the addslashes method bc it will add slashes to a bunch of chars that we do not want!
			 * Do not add the addcslashes($arg, '\\"') otherwise this will add an extra \\ to all the other \\. 
			 * Do add slash (\\) to the addcslashes method otherwise the escaped vars (like \$xxx) will be converted in real php vars (\\$xxx). 
			 * In case exists a case with an escaped double quote (this is, a back slash and quote like '\"'), the addcslashes($arg, '"') will convert it to a wrong code like: '\\"', which will give a php error because the $arg = "\\"". So we need to use the TextSanitizer::addCSlashes method instead, bc covers this case. 
			 * This case can happen when we are creating the ui files automatically through create_presentation_uis_diagram_files.php in the loadPageWithNewNavigation javascript function, which contains the following code:
			 * 		eval("url = decodeURI(url).replace(/" + page_attr_name + "=[^&]+/gi, \"\");");
			 * If we use the addcslashes($arg, '"'), we will have the code:
			 * 		eval(\"url = decodeURI(url).replace(/\" + page_attr_name + \"=[^&]+/gi, \\"\\");\");
			 * 	which is wrong be '\\"\\"' are not escaped.
			 * If we use the TextSanitizer::addCSlashes($arg, '"'), we will have:
			 * 		eval(\"url = decodeURI(url).replace(/\" + page_attr_name + \"=[^&]+/gi, \\\"\\\");\");
			 * 	which is correct: '\\\"\\\"'
			 * This means that we cannot use at all the addcslashes or addslashes method and that we must use the TextSanitizer::addCSlashes method!
			 * $arg = '"' . addcslashes($arg, '"') . '"'; //DO NOT UNCOMMENT THIS LINE!!! Do not use addcslashes method bc of the above reasons!
			 *
			 * Note: 3rd argument must be true, bc the php vars should be escaped, otherwise gives a php error, this is:
			 * 	example: 
			 * 		'"' . self::addCharSlashes('this is a simple phrase with double quotes " and the var {$person["name"]}!', '"') . '"'
			 * 	if true, returns:
			 *		'"this is a simple phrase with double quotes \" and the var {$person["name"]}!"'
			 * 	if false, returns:
			 *		'"this is a simple phrase with double quotes \" and the var {$person[\"name\"]}!"'
			 *		...which will return a php error, bc what is inside of {$...} will be executed first in php!
			 */
			$arg = '"' . TextSanitizer::addCSlashes($arg, '"', true) . '"'; //MUST BE TRUE: CHECK ABOVE!!!
			
			//Do not remove this line otherwise the form_js and form_css fields will look messy in module/form/system_settings/create_form_settings_code.php, if they contain any escaped end-lines ('\n' or "\\n")
			$arg = TextSanitizer::replaceIfNotEscaped('\n', '\\\n', $arg); //2019-10-17: In case there was an end-line escaped (this is: "\\n" or '\n'), we must escaped the previous slash correspondent to the end-line, otherwise we will have \n which then will write a real end-line and if it is inside of javascript, it will give a javascript error.
			//Note: if you want to add a real end line in $arg, please add it with "\n". '\n' or '<enter key>' it will be escaped!
			
			return $arg;
		}
		
		return $arg;
	}
	
	//note that if $value is an empty string, the type returned is STRING and not NULL.
	public static function getValueType($value, $options = null) {
		$non_set_type = is_array($options) && !empty($options["non_set_type"]) ? $options["non_set_type"] : "null";
		$empty_string_type = is_array($options) && !empty($options["empty_string_type"]) ? $options["empty_string_type"] : "null";
		
		if (!isset($value))
			return $non_set_type;
		
		$type = strtolower(gettype($value));
		
		if ($type == "string") {
			if (mb_strlen($value) == 0)
				return $empty_string_type;
			
			if (is_numeric($value) || self::isValidPHPCode($value))
				return ""; //if is a valid php code simple return empty type "", which means php code.
			
			return "string";
		}
		
		return $type;
	}
	
	/*
	  Is php code:
		- if 1 var or multiple vars joined by operators: like: "$x . $y" or "$x + $y" or "$x ? $y : 1" or "$x && $y"
		- if starts with <? and ends in ?>
		- if string wrapped in quotes
			Everytime that there is a string wrapped in quotes in the first level of the quote, it means that it is a code!
			This is:
			- text must be outside of var
			- and not escaped (with backslash)
			- and in the beginning of the argument
			- or at the end of the argument
			- precedent/followed by the concat operator: "."
			- outside of ( and [ => must be the first level of code
		
		This is, is code if:
			- if there is a $var or a quote-text-quote, where both are joined by operators like: . + - / & | : ? % !
		Otherwise is string.
		
		This is php code: 
		- $asd[0]["a"]
		- <?= foo() ?>
		- "asd" . 
		- $asd . "asd"
		- "" . foo("as", 123)
		- foo() . "dd"
		- '"" . foo("as", 'as') . "dd"'
		
		This isn't php code:
		- asd("asd" . "fggho") => bc is inside of a "(". Not at the first level
		- $asd["asd" . "fggho"] => bc is inside of a "(". Not at the first level
	*/
	private static function isValidPHPCode($value) {
		//if value is empty string, returns false
		if (!mb_strlen(trim($value)))
			return false;
		
		if (is_numeric($value) || strtolower($value) == "true" || strtolower($value) == "false")
			return true;
		
		//if value contains ", ', $ or <?, checks if value is a php code
		if (strpos($value, '"') !== false || strpos($value, "'") !== false || strpos($value, '$') !== false || strpos($value, '<?') !== false) { 
			$tokens = self::parseCode($value);
			$t = count($tokens);
			
			//remove empty strings, bc to check if is a php code, we must first to remove the empty strings to check the rules bellow. If we have the code: '$x . $y', we need to convert it first to: '$x.$y', in order to check the rules bellow. However do not remove the empty quotes...
			$new_tokens = array();
			for ($i = 0; $i < $t; $i++)
				if (trim($tokens[$i][0]) || !empty($tokens[$i][1])) //avoid removing the empty quotes...
					$new_tokens[] = $tokens[$i];
			
			//echo "value:$value\n";print_r($new_tokens);
			
			$t = count($new_tokens);
			if ($t) {
				if ($t == 1 && isset($new_tokens[0][1]) && ($new_tokens[0][1] == "php" || $new_tokens[0][1] == "variable" || $new_tokens[0][1] == "quotes")) //if is php code with php open and close tags or a variable or quotes
					return true;
				
				$exists_variables_or_quotes_joinned = false;
				$parenthesis = 0;
				
				for ($i = 0; $i < $t; $i++) {
					$tt = isset($new_tokens[$i][1]) ? $new_tokens[$i][1] : null;
					$ntt = $i + 1 < $t && isset($new_tokens[$i + 1][1]) ? $new_tokens[$i + 1][1] : null;
					
					if ($tt == "variable" || $tt == "quotes") {
						if ($i >= 0 && $i + 2 < $t) { //first or middle char and if exists next char, but that is not the last char.
							if ($ntt != "operator") //we cannot have variables or quotes before something else than an operator. If char is variable/quotes, next char must be operator.
								return false;
							else if ($i + 1 < $t && $new_tokens[$i + 1][0] == ".") //if there is a variable or quotes followed by an operator join ".", is php code! This solve the case: '"" . foo("as")'
								$exists_variables_or_quotes_joinned = true; 
						}
						else if ($i + 1 <= $t && $i - 1 > 0) { //last or middle char and if exists previous char, but that is not the first char.
							if (!isset($new_tokens[$i - 1][1]) || $new_tokens[$i - 1][1] != "operator") //we cannot have variables or quotes after something else than an operator. If char is variable/quotes, previous char must be a operator.
								return false;
							else if (isset($new_tokens[$i - 1][0]) && $new_tokens[$i - 1][0] == ".") //if there is a variable or quotes preceeded by an operator join ".", is php code! This solve the case: 'foo() . "dd"'
								$exists_variables_or_quotes_joinned = true; 
						}
					}
					else if ($tt == "operator") {
						if ($i == 0 || $i + 1 == $t) // first or last char cannot be operator
							return false;
						else if ($ntt == "operator") //.+ => 2 operators together is wrong
							return false;
					}
				}
				
				//echo "exists_variables_or_quotes_joinned:$exists_variables_or_quotes_joinned\n";
				return $exists_variables_or_quotes_joinned; //if there isn't any variable or quotes it means is not a code!
			}
		}
		
		return false;
	}
	
	//check if $value is a simple variable like: $xxx, $x["bla"], ...
	public static function isSimpleVariable($value) {
		$value = trim($value);
		
		if (substr($value, 0, 1) == '$' || substr($value, 0, 2) == '@$') {
			$tokens = self::parseCode($value);
			$t = count($tokens);
			
			//remove empty strings, bc to check if is a php code, we must first to remove the empty strings to check the rules bellow. If we have the code: '$x . $y', we need to convert it first to: '$x.$y', in order to check the rules bellow. However do not remove the empty quotes...
			$new_tokens = array();
			for ($i = 0; $i < $t; $i++)
				if (trim($tokens[$i][0]) || !empty($tokens[$i][1])) //avoid removing the empty quotes...
					$new_tokens[] = $tokens[$i];
			
			$t = count($new_tokens);
			if ($t == 1 && isset($new_tokens[0][1]) && $new_tokens[0][1] == "variable")
				return true;
		}
		return false;	
	}
	
	//Parses a text code and divides it with tokens
	private static function parseCode($text) {
		$tokens = array();
		$odq = $osq = false;
		$offset = 0;
		$current_type = "";
		$operators = array(
			"." => ".", //for concat strings
			"+" => "+",
			"-" => "-",
			"/" => "/",
			"*" => "*",
			"%" => "%",
			">" => array(">==", ">=", ">"),
			"<" => array("<==", "<=", "<"),
			"=" => array("===", "=="),
			"&" => "&",//for if conditions
			"|" => "|",//for if conditions
			"!" => "!", //for: !$xxx
			"?" => "?", //for: xxx ? yyy : www
			":" => ":", //for: xxx ? yyy : www
		);
		
		$text_chars = TextSanitizer::mbStrSplit($text);
		$l = count($text_chars);
		
		for ($i = 0; $i < $l; $i++) {
			$char = $text_chars[$i];
			
			if (!TextSanitizer::isMBCharEscaped($text, $i, $text_chars)) {
				if ($char == '"' && !$osq) { //for open/close double quotes
					$odq = !$odq;
					
					$str = implode("", array_slice($text_chars, $offset, $i - $offset + (!$odq ? 1 : 0)));
					if ($str)
						$tokens[] = array($str, !$odq ? "quotes" : $current_type);
					
					$current_type = !$odq ? "" : "quotes";
					$offset = !$odq ? $i + 1 : $i;
				}
				else if ($char == "'" && !$odq) { //for open/close single quotes
					$osq = !$osq;
					
					$str = implode("", array_slice($text_chars, $offset, $i - $offset + (!$osq ? 1 : 0)));
					if ($str)
						$tokens[] = array($str, !$osq ? "quotes" : $current_type);
					
					$current_type = !$osq ? "" : "quotes";
					$offset = !$osq ? $i + 1 : $i;
				}
				else if (($char == '$' || ($char == '@' && $i + 1 < $l && $text_chars[$i + 1] == '$')) && !$osq && !$odq) { //for variables
					$is_ignore_var = $char == "@";
					
					if ($is_ignore_var)
						$i++;
					
					$str = implode("", array_slice($text_chars, $i));
					preg_match('/^\$[\w]+/u', $str, $match); //'\w' means all words with '_' and '/u' means with accents and ç too. '/u' converts unicode to accents chars.
					$match = isset($match[0]) ? $match[0] : null;
					
					if ($match) {
						$bc = 0;
						$pc = 0;
						$match_chars = TextSanitizer::mbStrSplit($match);
						
						for ($j = $i + count($match_chars); $j < $l; $j++) {
							$char = $text_chars[$j];

							if ($char == '"' && !$osq && !TextSanitizer::isMBCharEscaped($text, $j, $text_chars))
								$odq = !$odq;
							else if ($char == "'" && !$odq && !TextSanitizer::isMBCharEscaped($text, $j, $text_chars))
								$osq = !$osq;
							else if ($char == "[" && !$osq && !$odq)
								++$bc;
							else if ($char == "]" && !$osq && !$odq)
								--$bc;
							else if ($char == "(" && !$osq && !$odq) //could be a method inside of a variable with an object
								++$pc;
							else if ($char == ")" && !$osq && !$odq)
								--$pc;
							else if ($char == "-" && $j + 1 < $l && $text_chars[$j + 1] == ">" && !$osq && !$odq) { //for the cases like: $EVC->getUtilPath("MasterCondoUtil", "mastercondo")
								$str = implode("", array_slice($text_chars, $j + 2));
								preg_match('/^[\w]+/u', $str, $sub_match); //'\w' means all words with '_' and '/u' means with accents and ç too. '/u' converts unicode to accents chars.
								$sub_match = isset($sub_match[0]) ? $sub_match[0] : null;
								
								if ($sub_match) {
									$sub_match_chars = TextSanitizer::mbStrSplit($sub_match);
									$j += 1 + count($sub_match_chars);
								}
								else
									break;
							}
							else if (!$osq && !$odq && $bc <= 0 && $pc <= 0)
								break;
						}
						
						$str = implode("", array_slice($text_chars, $offset, $i - $offset - ($is_ignore_var ? 1 : 0)));
						if ($str)
							$tokens[] = array($str, $current_type);
						
						$str = implode("", array_slice($text_chars, $i, $j - $i));
						
						if ($is_ignore_var)
							$str = "@" . $str;
						
						$tokens[] = array($str, "variable");
						$current_type = "";
						$i = $j - 1;
						$offset = $j;
					}
					//else doesn't do anything and treat $ as a normal character
				}
				else if ($char == "<" && $i + 1 < $l && $text_chars[$i + 1] == "?" && !$osq && !$odq) { //for php open and close tags
					for ($j = $i + 2; $j < $l; $j++) {
						$char = $text_chars[$j];

						if ($char == '"' && !$osq && !TextSanitizer::isMBCharEscaped($text, $j, $text_chars))
							$odq = !$odq;
						else if ($char == "'" && !$odq && !TextSanitizer::isMBCharEscaped($text, $j, $text_chars))
							$osq = !$osq;
						else if ($char == "?" && $j + 1 < $l && $text_chars[$j + 1] == ">" && !$osq && !$odq)
							break;
					}
					
					$str = implode("", array_slice($text_chars, $offset, $i - $offset));
					if ($str)
						$tokens[] = array($str, $current_type);
					
					$str = implode("", array_slice($text_chars, $i, $j - $i + 2));
					$tokens[] = array($str, "php");
					$current_type = "";
					$i = $j + 1;
					$offset = $j + 2;
				}
				else if ($char == "(" && !$osq && !$odq) { //for paranthesis. Only do the first level of code. If there are methods with args, ignore them...
					$pc = 1;
					
					for ($j = $i + 1; $j < $l; $j++) {
						$char = $text_chars[$j];

						if ($char == '"' && !$osq && !TextSanitizer::isMBCharEscaped($text, $j, $text_chars))
							$odq = !$odq;
						else if ($char == "'" && !$odq && !TextSanitizer::isMBCharEscaped($text, $j, $text_chars))
							$osq = !$osq;
						else if ($char == "(" && !$osq && !$odq)
							++$pc;
						else if ($char == ")" && !$osq && !$odq) {
							--$pc;
							
							if (!$osq && !$odq && $pc <= 0)
								break;
						}
					}
					
					$i = $j;
				}
				else if (!empty($operators[$char]) && !$osq && !$odq) {
					$ops = $operators[$char];
					$ops = !is_array($ops) ? array($ops) : $ops;
					
					foreach ($ops as $op) {
						$str = implode("", array_slice($text_chars, $i, strlen($op)));
						
						if ($str == $op) {
							$str = implode("", array_slice($text_chars, $offset, $i - $offset));
							if ($str)
								$tokens[] = array($str, $current_type);
							
							$tokens[] = array($op, "operator");
							$current_type = "";
							$i += strlen($op) - 1;
							$offset = $i + 1;
							
							break;
						}
					}
				}
			}
		}
		
		$str = implode("", array_slice($text_chars, $offset));
		if ($str)
			$tokens[] = array($str, $current_type);
		
		//error_log(print_r($tokens, 1)."\n\n", 3, "/var/www/html/livingroop/default/tmp/test.log");
		
		//echo "text:$text:".print_r($tokens, 1)."\n";
		return $tokens;
	}
	//This method is deprecated bc is a little bit more slow and don't allow unicode characters
	/*private static function parseCode($text) {
		$tokens = array();
		$odq = $osq = false;
		$offset = 0;
		$current_type = "";
		$operators = array(
			"." => ".", //for concat strings
			"+" => "+",
			"-" => "-",
			"/" => "/",
			"*" => "*",
			"%" => "%",
			">" => array(">==", ">=", ">"),
			"<" => array("<==", "<=", "<"),
			"=" => array("===", "=="),
			"&" => "&",//for if conditions
			"|" => "|",//for if conditions
			"!" => "!", //for: !$xxx
			"?" => "?", //for: xxx ? yyy : www
			":" => ":", //for: xxx ? yyy : www
		);
		
		$l = strlen($text);
		for ($i = 0; $i < $l; $i++) {
			$char = $text[$i];
			
			if (!TextSanitizer::isCharEscaped($text, $i)) {
				if ($char == '"' && !$osq) { //for open/close double quotes
					$odq = !$odq;
					
					$str = substr($text, $offset, $i - $offset + (!$odq ? 1 : 0));
					if ($str)
						$tokens[] = array($str, !$odq ? "quotes" : $current_type);
					
					$current_type = !$odq ? "" : "quotes";
					$offset = !$odq ? $i + 1 : $i;
				}
				else if ($char == "'" && !$odq) { //for open/close single quotes
					$osq = !$osq;
					
					$str = substr($text, $offset, $i - $offset + (!$osq ? 1 : 0));
					if ($str)
						$tokens[] = array($str, !$osq ? "quotes" : $current_type);
					
					$current_type = !$osq ? "" : "quotes";
					$offset = !$osq ? $i + 1 : $i;
				}
				else if ($char == '$' && !$osq && !$odq) { //for variables
					preg_match('/^\$[\w]+/u', substr($text, $i), $match); //'\w' means all words with '_' and '/u' means with accents and ç too. '/u' converts unicode to accents chars.
					$match = $match[0];
					
					if ($match) {
						$bc = 0;
						$pc = 0;
						
						for ($j = $i + strlen($match); $j < $l; $j++) {
							$char = $text[$j];

							if ($char == '"' && !TextSanitizer::isCharEscaped($text, $j) && !$osq)
								$odq = !$odq;
							else if ($char == "'" && !TextSanitizer::isCharEscaped($text, $j) && !$odq)
								$osq = !$osq;
							else if ($char == "[" && !$osq && !$odq)
								++$bc;
							else if ($char == "]" && !$osq && !$odq)
								--$bc;
							else if ($char == "(" && !$osq && !$odq) //could be a method inside of a variable with an object
								++$pc;
							else if ($char == ")" && !$osq && !$odq)
								--$pc;
							else if ($char == "-" && $j + 1 < $l && $text[$j + 1] == ">" && !$osq && !$odq) { //for the cases like: $EVC->getUtilPath("MasterCondoUtil", "mastercondo")
								preg_match('/^[\w]+/u', substr($text, $j + 2), $sub_match); //'\w' means all words with '_' and '/u' means with accents and ç too. '/u' converts unicode to accents chars.
								$sub_match = isset($sub_match[0]) ? $sub_match[0] : null;
								
								if ($sub_match) 
									$j += 1 + strlen($sub_match);
								else
									break;
							}
							else if (!$osq && !$odq && $bc <= 0 && $pc <= 0)
								break;
						}
						
						$str = substr($text, $offset, $i - $offset);
						if ($str)
							$tokens[] = array($str, $current_type);
						
						$tokens[] = array(substr($text, $i, $j - $i), "variable");
						$current_type = "";
						$i = $j - 1;
						$offset = $j;
					}
					//else doesn't do anything and treat $ as a normal character
				}
				else if ($char == "<" && $i + 1 < $l && $text[$i + 1] == "?" && !$osq && !$odq) { //for php open and close tags
					for ($j = $i + 2; $j < $l; $j++) {
						$char = $text[$j];

						if ($char == '"' && !TextSanitizer::isCharEscaped($text, $j) && !$osq)
							$odq = !$odq;
						else if ($char == "'" && !TextSanitizer::isCharEscaped($text, $j) && !$odq)
							$osq = !$osq;
						else if ($char == "?" && $text[$j + 1] == ">" && !$osq && !$odq)
							break;
					}
					
					$str = substr($text, $offset, $i - $offset);
					if ($str)
						$tokens[] = array($str, $current_type);
					
					$tokens[] = array(substr($text, $i, $j - $i + 2), "php");
					$current_type = "";
					$i = $j + 1;
					$offset = $j + 2;
				}
				else if ($char == "(" && !$osq && !$odq) { //for paranthesis. Only do the first level of code. If there are methods with args, ignore them...
					$pc = 1;
					
					for ($j = $i + 1; $j < $l; $j++) {
						$char = $text[$j];

						if ($char == '"' && !TextSanitizer::isCharEscaped($text, $j) && !$osq)
							$odq = !$odq;
						else if ($char == "'" && !TextSanitizer::isCharEscaped($text, $j) && !$odq)
							$osq = !$osq;
						else if ($char == "(" && !$osq && !$odq)
							++$pc;
						else if ($char == ")" && !$osq && !$odq) {
							--$pc;
							
							if (!$osq && !$odq && $pc <= 0)
								break;
						}
					}
					
					$i = $j;
				}
				else if (!empty($operators[$char]) && !$osq && !$odq) {
					$ops = $operators[$char];
					$ops = !is_array($ops) ? array($ops) : $ops;
					
					foreach ($ops as $op)
						if (substr($text, $i, strlen($op)) == $op) {
							$str = substr($text, $offset, $i - $offset);
							if ($str)
								$tokens[] = array($str, $current_type);
							
							$tokens[] = array($op, "operator");
							$current_type = "";
							$i += strlen($op) - 1;
							$offset = $i + 1;
							
							break;
						}
				}
			}
		}
		
		$str = substr($text, $offset);
		if ($str)
			$tokens[] = array($str, $current_type);
		
		//echo "text:$text:".print_r($tokens, 1)."\n";
		return $tokens;
	}*/
}
?>
