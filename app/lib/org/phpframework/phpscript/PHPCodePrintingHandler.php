<?php
include_once get_lib("org.phpframework.util.text.TextSanitizer");

//NOTE: This class methods only work with PHP code with the right format, bc these methods are based in the token_get_all function. If PHP is obfuscated with no spaces and no end-lines, the methods in this class won't work. 
class PHPCodePrintingHandler {
	
	/* START: GETTERS */
	public static function getPHPClassesFromFolder($folder_path) {
		$classes = array();
		
		$files = self::getAllFolderFiles($folder_path, false);
		
		$t = count($files);
		for ($i = 0; $i < $t; $i++) {
			$file_path = $files[$i];
			
			$classes[$file_path] = self::getPHPClassesFromFile($file_path);
		}
		return $classes;
	}
	
	public static function getPHPClassesFromFolderRecursively($folder_path) {
		$classes = array();
		
		$files = self::getAllFolderFiles($folder_path, true);
		
		$t = count($files);
		for ($i = 0; $i < $t; $i++) {
			$file_path = $files[$i];
			
			$classes[$file_path] = self::getPHPClassesFromFile($file_path);
		}
		return $classes;
	}
	
	public static function getPHPClassesFromFile($file_path) {
	    if ($file_path && file_exists($file_path)) {
		    $php_code = file_get_contents($file_path);
		    return self::getPHPClassesFromString($php_code);
		}
		return array();
	}
	
	//http://es.php.net/manual/pt_BR/tokens.php
	public static function getPHPClassesFromString($php_code) {
		$tokens = token_get_all($php_code);
		return self::getPHPClassesFromTokens($tokens);
	}
	
	public static function getPHPClassesFromTokens($tokens) {
		$methods = array();
		
		if (version_compare(PHP_VERSION, '8', '<=')) {
			if (!defined("T_NAME_FULLY_QUALIFIED"))
				define("T_NAME_FULLY_QUALIFIED", null);
			
			if (!defined("T_NAME_QUALIFIED"))
				define("T_NAME_QUALIFIED", null);
			
			if (!defined("T_NAME_RELATIVE"))
				define("T_NAME_RELATIVE", null);
		}
		
		if (version_compare(PHP_VERSION, '8.1', '<=')) {
			if (!defined("T_AMPERSAND_FOLLOWED_BY_VAR_OR_VARARG"))
				define("T_AMPERSAND_FOLLOWED_BY_VAR_OR_VARARG", null);
		}
		
		//print_r($tokens);die();
		$count = count($tokens);
		$start_function = false;
		$open_brackets_count = 0;
		$class_open_brackets_count = false;
		$function_open_brackets_count = false;
		$class_path = $namespace = "";
		$include_token_types = array(T_INCLUDE, T_INCLUDE_ONCE, T_REQUIRE, T_REQUIRE_ONCE);
		$include_token_types_once = array(T_INCLUDE_ONCE, T_REQUIRE_ONCE);
		$extends_implements_token_types = T_NAME_FULLY_QUALIFIED ? array(T_NAME_FULLY_QUALIFIED, T_NAME_QUALIFIED, T_NAME_RELATIVE) : array(); //only for php > 8.0
		$ampersand_followed_by_var_token_types = T_AMPERSAND_FOLLOWED_BY_VAR_OR_VARARG ? array(T_AMPERSAND_FOLLOWED_BY_VAR_OR_VARARG) : array(); //only for php > 8.1
		
		for ($i = 1; $i < $count; $i++) {
			if ($tokens[$i] == "{" || $tokens[$i][0] == T_CURLY_OPEN || $tokens[$i][0] == T_DOLLAR_OPEN_CURLY_BRACES)
				++$open_brackets_count;
			else if ($tokens[$i] == "}" || $tokens[$i][0] == "}"/* || $tokens[$i][0] == T_CURLY_CLOSE*/) {
				$line_index = is_array($tokens[$i]) && isset($tokens[$i][2]) && is_numeric($tokens[$i][2]) ? $tokens[$i][2] : null;
				$token_index = $i;
				
				//getting token line for }
				if (!is_numeric($line_index)) {
					$j = $i - 1;
					while (true) {
						$token = $j + 1 < $count ? $tokens[++$j] : null;
						
						if (!isset($token)) 
							break;
						else if (is_array($token) && isset($token[2]) && is_numeric($token[2])) {
							$line_index = $token[2];
							break;
						}
					}
				}
				
				if ($function_open_brackets_count == $open_brackets_count) {
					$function_open_brackets_count = false;
					
					if ($class_path) {
						if (!empty($methods[$class_path]["methods"])) {
							$idx = count($methods[$class_path]["methods"]) - 1;
							$methods[$class_path]["methods"][$idx]["end_brackets_line_index"] = $line_index;
							$methods[$class_path]["methods"][$idx]["end_brackets_token_index"] = $token_index;
							$methods[$class_path]["methods"][$idx]["end_line_index"] = $line_index;
							$methods[$class_path]["methods"][$idx]["end_token_index"] = $token_index;
						}
					}
					else {
						$idx = !empty($methods[0]["methods"]) ? count($methods[0]["methods"]) - 1 : 0;
						$methods[0]["methods"][$idx]["end_brackets_line_index"] = $line_index;
						$methods[0]["methods"][$idx]["end_brackets_token_index"] = $token_index;
						$methods[0]["methods"][$idx]["end_line_index"] = $line_index;
						$methods[0]["methods"][$idx]["end_token_index"] = $token_index;
					}
				}
				
				if ($class_open_brackets_count == $open_brackets_count && $class_path) {
					$class_open_brackets_count = false;
					
					$methods[$class_path]["end_brackets_line_index"] = $line_index;
					$methods[$class_path]["end_brackets_token_index"] = $token_index;
					$methods[$class_path]["end_line_index"] = $line_index;
					$methods[$class_path]["end_token_index"] = $token_index;
					
					$class_path = "";
				}
				
				--$open_brackets_count;
			}
			else if (!$class_open_brackets_count && !$function_open_brackets_count && ($tokens[$i][0] == T_CLASS || $tokens[$i][0] == T_INTERFACE)) { //in case there is a class inside of a function gets ignored	$class_path = "";
				$class_data = array();
				$class_name = "";
				$class_path = "";
				$line_index = isset($tokens[$i][2]) ? $tokens[$i][2] : null;
				$interface = $tokens[$i][0] == T_INTERFACE;
				$abstract = false;
				$start_function = $start_extends = $start_implements = false;
				$extends_index = $implements_index = 0;
				
				$class_idx = $i;
				
				//CHECKING IF ABSTRACT
				$j = $i - 1; //white space or comment
				while (true) {
					$token = $j - 1 >= 0 ? $tokens[--$j] : null;
					
					if (!is_array($token))
						break;
					else if ($token[0] == T_ABSTRACT) {
						$abstract = true;
						$line_index = isset($token[2]) ? $token[2] : null;
						$class_idx = $j;
						break;
					}
					else if ($token[0] != T_COMMENT)
						break;
				}
				
				//GETTING CLASS NAME
				while (true) {
					$token = $i + 1 < $count ? $tokens[++$i] : null;
					
					if (!isset($token)) 
						break;
					else if ($token == "{" || $token[0] == T_CURLY_OPEN || $token[0] == T_DOLLAR_OPEN_CURLY_BRACES) {
						--$i;
						break;
					}
					else if (!is_array($token)) 
						break;
					else if ($token[0] == T_STRING) 
						$class_name .= $token[1];
					else if ($token[0] != T_WHITESPACE) {
						--$i;
						break;
					}
				}
				
				//PREPARING EXTENDS AND IMPLEMENTS
				if (!empty($class_name)) {
					$class_path = self::prepareClassNameWithNameSpace($class_name, $namespace);
					
					$class_data = array(
						"name" => $class_name,
						"start_line_index" => $line_index,
						"start_token_index" => $class_idx,
						"interface" => $interface,
						"abstract" => $abstract,
						"namespace" => $namespace,
					);
					
					//PREPARING METHOD COMMENTS:
					self::prepareTokensComments($tokens, $class_idx, $class_data);
					
					while (true) {
						$token = $i + 1 < $count ? $tokens[++$i] : null;
						
						if (!isset($token)) 
							break;
						else if ($token == "{" || $token[0] == T_CURLY_OPEN || $token[0] == T_DOLLAR_OPEN_CURLY_BRACES) {
							--$i;
							break;
						}
						else if (!is_array($token) && $token != ",") 
							break;
						else if ($token[0] == T_EXTENDS) {
							$start_extends = true;
							$start_implements = false;
							$extends_index = 0;
						}
						else if ($token[0] == T_IMPLEMENTS) {
							$start_extends = false;
							$start_implements = true;
							$implements_index = 0;
						}
						else if ( ($start_extends || $start_implements) && ($token == "," || (is_array($token) && $token[1] == ","))) {
							if ($start_extends)
								$extends_index++;
							else
								$implements_index++;
						}
						else if ( ($start_extends || $start_implements) && ($token[0] == T_NS_SEPARATOR || $token[0] == T_STRING || in_array($token[0], $extends_implements_token_types))) {
							$type = $start_extends ? "extends" : "implements";
							$type_index = $start_extends ? $extends_index : $implements_index;
							
							if (!empty($class_data[$type][$type_index]))
								$class_data[$type][$type_index] .= $token[1];
							else
								$class_data[$type][$type_index] = $token[1];
						}
					}
				}
				
				//PREPARING OPEN BRACKETS: {
				while (true) {
					$token = $i + 1 < $count ? $tokens[++$i] : null;
					
					if (!isset($token)) 
						break;
					else if ($token == "{" || $token[0] == T_CURLY_OPEN || $token[0] == T_DOLLAR_OPEN_CURLY_BRACES) {
						if (!empty($class_path)) {
							$class_data["start_brackets_line_index"] = isset($tokens[$i][2]) && is_numeric($tokens[$i][2]) ? $tokens[$i][2] : (isset($tokens[$i + 1][2]) ? $tokens[$i + 1][2] : null);
							$class_data["start_brackets_token_index"] = $i;
						}
						
						++$open_brackets_count;
						$class_open_brackets_count = $open_brackets_count;
						break;
					}
				}
				
				if (!empty($class_path))
					$methods[$class_path] = $class_data;
			}
			else if (!$class_open_brackets_count && !$function_open_brackets_count && $tokens[$i][0] == T_NAMESPACE) {
				$namespace = "";
				
				while (true) {
					$token = $i + 1 < $count ? $tokens[++$i] : null;
					
					if (!isset($token)) 
						break;
					else if ($token == ";")
						break;
					
					$namespace .= is_array($token) ? $token[1] : $token;
				}
				
				$namespace = trim($namespace);
				
				if ($namespace)
					$methods[0]["namespaces"][] = $namespace;
			}
			else if (!$class_open_brackets_count && !$function_open_brackets_count && in_array($tokens[$i][0], $include_token_types)) {
				$include = "";
				$token_type = $tokens[$i][0];
				
				while (true) {
					$token = $i + 1 < $count ? $tokens[++$i] : null;
					
					if (!isset($token)) 
						break;
					else if ($token == ";")
						break;
					
					$include .= is_array($token) ? $token[1] : $token;
				}
				
				$include = trim($include);
				
				if ($include)
					$methods[0]["includes"][] = array($include, in_array($token_type, $include_token_types_once));
			}
			else if (!$class_open_brackets_count && !$function_open_brackets_count && $tokens[$i][0] == T_USE) {
				$use_name = $use_alias = "";
				$use_alias_active = false;
				
				while (true) {
					$token = $i + 1 < $count ? $tokens[++$i] : null;
					
					if (!isset($token)) 
						break;
					else if ($token == ";")
						break;
					else if ($token[0] == T_AS)
						$use_alias_active = true;
					else  {
						$char = is_array($token) ? $token[1] : $token;
						
						if ($use_alias_active)
							$use_alias .= $char;
						else
							$use_name .= $char;
					}
				}
				
				$use_name = trim($use_name);
				$use_alias = trim($use_alias);
				
				if ($use_name)
					$methods[0]["uses"][$use_name] = $use_alias;
			}
			else if (!$function_open_brackets_count && $tokens[$i][0] == T_FUNCTION) {
				$start_function = true;
			}
			else if (!$function_open_brackets_count && $start_function && $tokens[$i][0] == T_STRING) {
				$start_function = false;
				
				if ($open_brackets_count <= 0)
					$class_path = "";
				
				$method_name = $tokens[$i][1];
				$line_index = isset($tokens[$i][2]) ? $tokens[$i][2] : null;
				$method = array();
				
				//echo "$class_path::$method_name:$open_brackets_count\n";
				
				//PREPARING METHOD PROPERTIES:
				$type = false;
				$abstract = false;
				$is_static = false;
				
				$function_idx = $i;
				$j = $i - 1; //white space or comment
				while (true) {
					$token = $j - 1 >= 0 ? $tokens[--$j] : null;
					
					if (!is_array($token))
						break;
					else if ($token[0] == T_PUBLIC) {
						$type = "public";
						$line_index = isset($token[2]) ? $token[2] : null;
						$function_idx = $j;
					}
					else if ($token[0] == T_PRIVATE) {
						$type = "private";
						$line_index = isset($token[2]) ? $token[2] : null;
						$function_idx = $j;
					}
					else if ($token[0] == T_PROTECTED) {
						$type = "protected";
						$line_index = isset($token[2]) ? $token[2] : null;
						$function_idx = $j;
					}
					else if ($token[0] == T_FUNCTION) {
						$line_index = isset($token[2]) ? $token[2] : null;
						$function_idx = $j;
					}
					else if ($token[0] == T_STATIC) {
						$is_static = true;
						$line_index = isset($token[2]) ? $token[2] : null;
						$function_idx = $j;
					}
					else if ($token[0] == T_ABSTRACT) {
						$abstract = true;
						$line_index = isset($token[2]) ? $token[2] : null;
						$function_idx = $j;
					}
					else if ($token[0] != T_WHITESPACE)
						break;
				}
				
				if ($method_name == "__construct" && !empty($class_path))
					$type = "construct";
				else
					$type = $type ? $type : "public";
				
				$method = array(
					"name" => $method_name,
					"start_line_index" => $line_index,
					"start_token_index" => $function_idx,
					"type" => $type,
					"abstract" => $abstract,
					"static" => $is_static,
				);
				
				//PREPARING METHOD COMMENTS:
				self::prepareTokensComments($tokens, $function_idx, $method);
				
				//PREPARING METHOD ARGUMENTS:
				$arguments = array();
				$start_args = $arg_name = $arg_class = $start_arg_value = false;
				
				while (true) {
					$token = $i + 1 < $count ? $tokens[++$i] : null;
					
					if (!isset($token)) 
						break;
					else if ($token == "(") {
						$start_args = true;
						$method["start_args_token_index"] = $i;
					}
					else if ($token == ")" || empty($token)) {
						$method["end_args_token_index"] = $i;
						break;
					}
					else if ($token == ";") { //in case of interfaces and abstract classes
						--$i;
						break;
					}
					else if ($start_args) {
						if ($token[0] == T_VARIABLE) {
							$start_arg_value = false;
							$arg_name = $token[1];
							
							//check if variable is passed by reference
							if ($arg_class && preg_match("/&\s*$/", $arg_class)) { //remove '&' in $arg_class and add it to $arg_name, bc this happens in PHP 8, where the $arg_class ends with '&'
								$arg_class = preg_replace("/\s*&\s*$/", "", $arg_class); //erase & at the end of string
								$arg_name = "&" . $arg_name;
							}
							else { //check if prev char is &
								$j = $i;
								
								while (true) {
									$token = $j - 1 >= 0 ? $tokens[--$j] : null;
									
									if (!isset($token) || $token == "," || $token == ")")
										break;
									else if ($token == "&" || (isset($token[1]) && $token[1] == "&") || (isset($token[0]) && in_array($token[0], $ampersand_followed_by_var_token_types))) {
										$arg_name = "&" . $arg_name;
										break;
									}
								}
							}
							
							//add class to arg name
							if ($arg_class && trim($arg_class))
								$arg_name = trim($arg_class) . " " . $arg_name;
							
							$arguments[ $arg_name ] = null;
						}
						else if ($arg_name) {
							if ($token == "=") {
								$start_arg_value = true;
								$arguments[ $arg_name ] = "";
							}
							else if ($start_arg_value) {
								//GET ARGUMENT VALUE including if an array value
								$open_paranteses_count = 0;
								$arg_value = "";
								
								do {
									$arg_value .= is_array($token) ? $token[1] : $token;
									$token = $i + 1 < $count ? $tokens[++$i] : null;
									
									if (!isset($token)) 
										break;
									else if ($token == "(") 
										$open_paranteses_count++;
									elseif ($token == ")") {
										$open_paranteses_count--;
										
										if ($open_paranteses_count == 0) {
											$arg_value .= ")";
											++$i;
										}
									}
									
									if ($open_paranteses_count <= 0 && ($token == "," || $token == ")")) 
										break;
								} 
								while(true);
								
								$arguments[ $arg_name ] = trim($arg_value);
								//echo "$arg_name: ".$arguments[ $arg_name ]."!$open_paranteses_count!".print_r($token, true)."\n<br>";
								
								$arg_name = false;
								$arg_class = false;
								$start_arg_value = false;
								--$i;
							}
							else if ($token == ",") {
								$arg_name = false;
								$arg_class = false;
								$start_arg_value = false;
							}
						}
						//before $arg_name is defined, get the arg class if exists
						else if (isset($token[1]) && ($token[0] == T_NS_SEPARATOR || $token[0] == T_STRING || in_array($token[0], $extends_implements_token_types) || strlen($token[1]))) {
							$arg_class .= $token[1];
						}
					}
				}
				
				if (!empty($arguments))
					$method["arguments"] = $arguments;
				
				//PREPARING OPEN BRACKETS: {
				$last_line_index = $line_index;
				
				while (true) {
					$token = $i + 1 < $count ? $tokens[++$i] : null;
					
					if (is_array($token))
						$last_line_index = isset($token[2]) ? $token[2] : null;
					
					if (!isset($token))
						break;
					else if ($token == "{" || $token[0] == T_CURLY_OPEN || $token[0] == T_DOLLAR_OPEN_CURLY_BRACES) {
						$method["start_brackets_line_index"] = isset($tokens[$i][2]) && is_numeric($tokens[$i][2]) ? $tokens[$i][2] : (isset($tokens[$i + 1][2]) ? $tokens[$i + 1][2] : null);
						$method["start_brackets_token_index"] = $i;
						
						++$open_brackets_count;
						$function_open_brackets_count = $open_brackets_count;
						break;
					}
					else if ($token == ";") { //in case of interfaces and abstract classes
						$method["start_brackets_line_index"] = $last_line_index;
						$method["start_brackets_token_index"] = $i;
						$method["end_brackets_line_index"] = $last_line_index;
						$method["end_brackets_token_index"] = $i;
						$method["end_line_index"] = $last_line_index;
						$method["end_token_index"] = $i;
						break;
					}
				}
				
				//ADDING METHOD TO MAIN METHODS:
				if (!empty($class_path))
					$methods[$class_path]["methods"][] = $method;
				else {
					if ($namespace)
						$method["namespace"] = $namespace;
					
					$methods[0]["methods"][] = $method;
				}
			}
		}
		
		//print_r($methods);die();
		return $methods;
	}
	
	public static function prepareClassNameWithNameSpace($class_name, $namespace = "") {
		return ($namespace ? (substr($namespace, 0, 1) == "\\" ? "" : "\\") . $namespace . "\\" : "") . $class_name;
	}
	
	public static function decoupleClassNameWithNameSpace($class_name) {
		$name = $class_name;
		$namespace = "";
		
		if (strpos($class_name, "\\") !== false) {
			$pos = strrpos($class_name, "\\");
			$name = substr($class_name, $pos + 1);
			$namespace = substr($class_name, 0, $pos);
		}
		
		return array("class_name" => $class_name, "namespace" => $namespace, "name" => $name);
	}
	
	private static function prepareTokensComments($tokens, $idx, &$obj) {
		$comments = array();
		$doc_comments = array();
		
		$start_comments_line_index = null;
		$start_comments_token_index = null;
		$end_comments_line_index = isset($tokens[$idx]) && is_array($tokens[$idx]) ? $tokens[$idx][2] : null;
		$end_comments_token_index = isset($tokens[$idx]) && is_array($tokens[$idx]) ? $idx : null;
		
		while (true) {
			$token = $idx >= 0 ? $tokens[--$idx] : null;
			
			if (is_array($token) && ($token[0] == T_COMMENT || $token[0] == T_DOC_COMMENT || $token[0] == T_WHITESPACE)) {
				if ($token[0] == T_COMMENT) {
					if (!isset($start_comments_line_index)) {
						$end_comments_line_index = isset($token[2]) ? $token[2] : null + substr_count($token[1], "\n");
						$end_comments_token_index = $idx;
					}
					
					$comments[] = trim($token[1]);
					$start_comments_line_index = isset($token[2]) ? $token[2] : null;
					$start_comments_token_index = $idx;
				}
				else if ($token[0] == T_DOC_COMMENT) {
					if (!isset($start_comments_line_index)) {
						$end_comments_line_index = isset($token[2]) ? $token[2] : null + substr_count($token[1], "\n");
						$end_comments_token_index = $idx;
					}
					
					$doc_comments[] = trim($token[1]);
					$start_comments_line_index = isset($token[2]) ? $token[2] : null;
					$start_comments_token_index = $idx;
				}
				else if (!isset($start_comments_line_index)) {
					$end_comments_line_index = isset($token[2]) ? $token[2] : null;
					$end_comments_token_index = $idx;
				}
			}
			else
				break;
		}
		
		if (!empty($comments)) 
			$obj["comments"] = array_reverse($comments);
		
		if (!empty($doc_comments))
			$obj["doc_comments"] = array_reverse($doc_comments);
		
		if (isset($start_comments_line_index)) {
			$obj["start_comments_line_index"] = $start_comments_line_index;
			$obj["start_comments_token_index"] = $start_comments_token_index;
			$obj["end_comments_line_index"] = $end_comments_line_index;
			$obj["end_comments_token_index"] = $end_comments_token_index;
		}
	}
	
	public static function getClassFromFile($file_path, $class_name) {
		$class_name = $class_name ? trim($class_name) : $class_name;
		
		if ($class_name) {
			$arr = self::getPHPClassesFromFile($file_path);
			return self::getClassFromPHPClasses($arr, $class_name);
		}
		
		return null;
	}
	
	public static function getClassFromPHPClasses($classes, $class_name) {
		$class_name = $class_name ? trim($class_name) : $class_name;
		
		if (is_array($classes) && $class_name) {
			$class_name = strtolower($class_name);
			$class_name = (strpos($class_name, "\\") !== false && substr($class_name, 0, 1) != "\\" ? "\\" : "") . $class_name; //prepare class name with right namespace, this is, if exists namespace check if $class_name is already configured correctly.
			
			foreach ($classes as $cn => $c) 
				if (strtolower($cn) == $class_name) 
					return $c;
		}
		
		return null;
	}
	
	public static function searchClassFromPHPClasses($classes, $class_name) {
		$class_name = $class_name ? trim($class_name) : $class_name;
		
		if (is_array($classes) && $class_name) {
			$class_name = strtolower($class_name);
			$class_name = (strpos($class_name, "\\") !== false && substr($class_name, 0, 1) != "\\" ? "\\" : "") . $class_name; //prepare class name with right namespace, this is, if exists namespace check if $class_name is already configured correctly.
			
			foreach ($classes as $cn => $c) 
				if (strtolower(substr($cn, - strlen($class_name))) == $class_name) 
					return $c;
		}
		
		return null;
	}
	
	//checks if the $class_name really exists in file and if not checks for the correspondent with the namespace and return the class_path
	public static function getClassPathFromClassName($file_path, $class_name) {
		$arr = self::getPHPClassesFromFile($file_path);
		$c = self::getClassFromPHPClasses($arr, $class_name);
		
		if ($c)
			return $class_name;
		
		if (is_array($arr)) {
			$class_name = strtolower($class_name);
			
			foreach ($arr as $cn => $c) 
				if (isset($c["name"]) && strtolower($c["name"]) == $class_name) 
					return $cn;
		}
		
		return null;
	}
	
	//based in a file, get the correspondent class (basically based in the file name, get sthe correspondent class data inside of the file.
	public static function getClassOfFile($file_path) {
		if ($file_path && file_exists($file_path)) {
			$class_name = basename(pathinfo($file_path, PATHINFO_FILENAME));
			
			$arr = self::getPHPClassesFromFile($file_path);
			$c = self::getClassFromPHPClasses($arr, $class_name);
			
			if ($c)
				return $c;
			
			if (is_array($arr)) {
				$class_name = strtolower($class_name);
				
				foreach ($arr as $cn => $c) 
					if (isset($c["name"]) && strtolower($c["name"]) == $class_name) 
						return $c;
			}
		}
		
		return null;
	}
	
	public static function getClassPropertyFromFile($file_path, $class_name, $prop_name) {
		$properties = self::getClassPropertiesFromFile($file_path, $class_name);
		$prop_name = $prop_name ? strtolower(trim($prop_name)) : false;
		
		if ($properties)
			foreach ($properties as $property)
				if (isset($property["name"]) && strtolower($property["name"]) == $prop_name) 
					return $property;
		
		return null;
	}
	
	public static function getFunctionFromFile($file_path, $func_name, $class_name = 0) {
		if ($file_path && file_exists($file_path)) {
			$contents = file_get_contents($file_path);
			return self::getFunctionFromString($contents, $func_name, $class_name);
		}
		
		return null;
	}
	
	public static function getFunctionFromString($contents, $func_name, $class_name = 0) {
		$func_name = $func_name ? strtolower(trim($func_name)) : false;
		$class_name = $class_name ? trim($class_name) : $class_name;
		
		if ($contents && $func_name) {
			$arr = self::getPHPClassesFromString($contents);
			
			if (!$class_name)
				$methods = isset($arr[0]["methods"]) ? $arr[0]["methods"] : null;
			else {
				$c = self::getClassFromPHPClasses($arr, $class_name);
				$methods = $c && isset($c["methods"]) ? $c["methods"] : null;
			}
			
			if ($methods) {
				$t = count($methods);
				for ($i = 0; $i < $t; $i++) 
					if (isset($methods[$i]["name"]) && strtolower($methods[$i]["name"]) == $func_name) 
						return $methods[$i];
			}
		}
		
		return null;
	}
	
	public static function getNamespacesFromFile($file_path) {
		/*$instructions = self::getCodeInstructionFromFile($file_path, T_NAMESPACE);
		
		$namespaces = array();
		foreach ($instructions as $instruction)
			$namespaces[] = $instruction[0];
		
		return $namespaces;*/
		
		$classes = self::getPHPClassesFromFile($file_path);
		return isset($classes[0]["namespaces"]) ? $classes[0]["namespaces"] : null;
	}
	
	public static function getUsesFromFile($file_path) {
		/*$instructions = self::getCodeInstructionFromFile($file_path, T_USE);
		
		$uses = array();
		foreach ($instructions as $instruction)
			$uses[] = $instruction[0];
		
		return $uses;*/
		
		$classes = self::getPHPClassesFromFile($file_path);
		return isset($classes[0]["uses"]) ? $classes[0]["uses"] : null;
	}
	
	public static function getIncludesFromFile($file_path) {
		/*$token_types = array(T_INCLUDE, T_INCLUDE_ONCE, T_REQUIRE, T_REQUIRE_ONCE);
		$token_types_once = array(T_INCLUDE_ONCE, T_REQUIRE_ONCE);
		$instructions = self::getCodeInstructionFromFile($file_path, $token_types);
		
		$includes = array();
		foreach ($instructions as $instruction)
			$includes[] = array($instruction[0], in_array($instruction[1], $token_types_once));
		
		return $includes;*/
		
		$classes = self::getPHPClassesFromFile($file_path);
		return isset($classes[0]["includes"]) ? $classes[0]["includes"] : null;
	}
	
	//Note: Only get instructions from the global scope. All instructions inside of brackets {...} will be ignored!
	private static function getCodeInstructionFromFile($file_path, $token_types) {
		$instructions = array();
		
		if ($file_path && file_exists($file_path) && $token_types) {
			$token_types = is_array($token_types) ? $token_types : array($token_types);
			
			$contents = file_get_contents($file_path);
			$tokens = token_get_all($contents);
			$count = count($tokens);
			$open_brackets_count = 0;
			//print_r($tokens);die();
			
			for ($i = 1; $i < $count; $i++) {
				if ($tokens[$i] == "{" || $tokens[$i][0] == T_CURLY_OPEN || $tokens[$i][0] == T_DOLLAR_OPEN_CURLY_BRACES)
					++$open_brackets_count;
				else if ($tokens[$i] == "}" || $tokens[$i][0] == "}"/* || $tokens[$i][0] == T_CURLY_CLOSE*/)
					--$open_brackets_count;
				else if ($open_brackets_count <= 0 && in_array($tokens[$i][0], $token_types)) { //only if outside of class or function
					$instruction = "";
					$instruction_type = $tokens[$i][0];
					
					while (true) {
						$token = $i + 1 < $count ? $tokens[++$i] : null;
						
						if (!isset($token)) 
							break;
						else if ($token == ";")
							break;
						
						$instruction .= is_array($token) ? $token[1] : $token;
					}
					
					$instruction = trim($instruction);
					
					if ($instruction)
						$instructions[] = array($instruction, $instruction_type);
				}
			}
		}
		
		return $instructions;
	}
	/* This is DEPRECATED bc if php is obfuscated this doesn't work.
	private static function getCodeInstructionFromFile($file_path, $types) {
		$instructions = array();
		
		if ($file_path && file_exists($file_path) && $types) {
			$types = is_array($types) ? $types : array($types);
			
			$contents = file_get_contents($file_path);
			$contents = str_replace("\r\n", "\n", $contents);
			$lines = explode("\n", $contents);
			
			$t = count($lines);
			for ($i = 0; $i < $t; $i++) {
				$line = $lines[$i];
				$trimmed = trim($line);
				
				foreach ($types as $type)
					if ($type) {
						$pos = stripos($trimmed, $type);
						
						//checks instruction at the begginning of the line
						if ($pos === 0) {
							$start_pos = $pos + strlen($type);
							$end_pos = strpos($trimmed, ";", $start_pos);
							$end_pos = $end_pos !== false ? $end_pos : strlen($end_pos);
							
							$instruction = trim( substr($trimmed, $start_pos, $end_pos - $start_pos) );
							$instructions[] = array($instruction, $type);
							
							break;
						}
					}
			}
		}
		
		return $instructions;
	}*/
	
	public static function getClassPropertiesFromFile($file_path, $class_name) {
		if ($file_path && file_exists($file_path)) {
			$contents = file_get_contents($file_path);
			return self::getClassPropertiesFromString($contents, $class_name);
		}
		
		return array();
	}
	
	public static function getClassPropertiesFromString($contents, $class_name) {
		$properties = array();
		
		$arr = self::getPHPClassesFromString($contents);
		$c = self::getClassFromPHPClasses($arr, $class_name);
		
		if ($c) {
			$tokens = token_get_all($contents);
			$count = count($tokens);
			
			//filter tokens to class contents
			$start_brackets_token_index = isset($c["start_brackets_token_index"]) && is_numeric($c["start_brackets_token_index"]) ? $c["start_brackets_token_index"] : 0;
			$end_brackets_token_index = isset($c["end_brackets_token_index"]) && is_numeric($c["end_brackets_token_index"]) ? $c["end_brackets_token_index"] : $count;
			
			$intervals = array();
			$start_token_index = $start_brackets_token_index + 1;
			
			$t = !empty($c["methods"]) ? count($c["methods"]) : 0;
			
			for ($i = 0; $i < $t; $i++) {
				$sti = isset($c["methods"][$i]["start_comments_token_index"]) && is_numeric($c["methods"][$i]["start_comments_token_index"]) ? $c["methods"][$i]["start_comments_token_index"] : (
					isset($c["methods"][$i]["start_token_index"]) ? $c["methods"][$i]["start_token_index"] : null
				);
				
				$intervals[] = array($start_token_index, $sti - 1);
				$start_token_index = $c["methods"][$i]["end_token_index"] + 1;
			}
			
			$intervals[] = array($start_token_index, $end_brackets_token_index - 1);
			
			//getting properties
			foreach ($intervals as $interval) {
				$start_token_index = $interval[0];
				$end_token_index = $interval[1];
				
				$open_brackets_count = 0;
				$vars_token_types = array(T_VAR, T_PUBLIC, T_PRIVATE, T_PROTECTED, T_CONST, T_STATIC, T_ABSTRACT);
				//print_r($tokens);die();
				
				for ($i = $start_token_index; $i <= $end_token_index; $i++) {
					if ($tokens[$i] == "{" || $tokens[$i][0] == T_CURLY_OPEN || $tokens[$i][0] == T_DOLLAR_OPEN_CURLY_BRACES) 
						++$open_brackets_count;
					else if ($tokens[$i] == "}" || $tokens[$i][0] == "}"/* || $tokens[$i][0] == T_CURLY_CLOSE*/)
						--$open_brackets_count;
					else if ($open_brackets_count <= 0 && in_array($tokens[$i][0], $vars_token_types)) {
						$const = $tokens[$i][0] == T_CONST;
						$static = $tokens[$i][0] == T_STATIC;
						$private = $tokens[$i][0] == T_PRIVATE;
						$public = $tokens[$i][0] == T_PUBLIC;
						$protected = $tokens[$i][0] == T_PROTECTED;
						$abstract = $tokens[$i][0] == T_ABSTRACT;
						$var_name = "";
						$var_value = null;
						$var_start_line_index = isset($tokens[$i][2]) ? $tokens[$i][2] : null;
						$var_start_token_index = $i;
						
						while(true) {
							$token = $i + 1 < $count ? $tokens[++$i] : null;
							
							if (!isset($token))
								break;
							else if ($token == ";") {
								--$i;
								break;
							}
							else if ($token[0] == T_VARIABLE || ($const && $token[0] == T_STRING)) {
								$var_name = $token[1];
								break;
							}
							else if (!in_array($token[0], $vars_token_types) && $token[0] != T_WHITESPACE)
								break;
							else if ($token[0] == T_CONST)
								$const = true;
							else if ($token[0] == T_STATIC)
								$static = true;
							else if ($token[0] == T_ABSTRACT)
								$abstract = true;
							else if ($token[0] == T_PRIVATE)
								$private = true;
							else if ($token[0] == T_PUBLIC)
								$public = true;
							else if ($token[0] == T_PROTECTED)
								$protected = true;
						}
						
						$var_name = trim($var_name);
						
						if ($var_name) {
							$var_name = $var_name[0] == '$' ? substr($var_name, 1) : $var_name;
							
							//getting var value
							$has_value = false;
							$var_last_line_index = $var_start_line_index;
							$var_end_line_index = $var_end_token_index = null;
							
							while(true) {
								$token = $i + 1 < $count ? $tokens[++$i] : null;
								
								if (is_array($token))
									$var_last_line_index = isset($token[2]) ? $token[2] : null;
								
								if (!isset($token))
									break;
								else if ($token == ";") {
									$var_end_line_index = $var_last_line_index;
									$var_end_token_index = $i;
									break;
								}
								else if ($token == "=" || (isset($token[1]) && $token[1] == "="))
									$has_value = true;
								else if ($has_value)
									$var_value .= is_array($token) ? (isset($token[1]) ? $token[1] : null) : $token;
							}
							
							$var_value = trim($var_value);
							$quote_char = substr($var_value, 0, 1);
							$var_value_type = $var_value && ($quote_char == '"' || $quote_char == "'") && substr($var_value, -1) == $quote_char ? "string" : "";
							
							$property = array(
								"name" => $var_name,
								"type" => $private ? "private" : ($protected ? "protected" : "public"),
								"value" => $var_value,
								"var_type" => $var_value_type,
								"static" => $static,
								"abstract" => $abstract,
								"const" => $const,
								"start_line_index" => $var_start_line_index,
								"start_token_index" => $var_start_token_index,
								"end_line_index" => $var_end_line_index,
								"end_token_index" => $var_end_token_index,
							);
							
							//getting var comments
							self::prepareTokensComments($tokens, $var_start_token_index, $property);
							
							$properties[] = $property;
						}
					}
				}
			}
		}
		
		return $properties;
	}
	/* This is DEPRECATED bc if php is obfuscated this doesn't work. Besides it doesn't get the values correctly if they have comments at the first line of the properties value
	public static function getClassPropertiesFromFile($file_path, $class_name) {
		$properties = array();
		
		if ($file_path && file_exists($file_path) && $class_name) {
			$contents = file_get_contents($file_path);
			
			$classes = self::getPHPClassesFromString($contents);
			$c = self::getClassFromPHPClasses($classes, $class_name);
			$class_name = $c["name"];
			$start_brackets_line = $c["start_brackets_line_index"];
			
			if (is_numeric($start_brackets_line)) {
				$contents = str_replace("\r\n", "\n", $contents);
				$lines = explode("\n", $contents);
				
				//GETTING PROPERTIES IN TEXT
				$line = $lines[ $start_brackets_line - 1 ];
				$pos = strpos($line, "{");
				$pos = $pos !== false ? $pos : strlen($line);
				
				$intervals = array();
				$t = $c["methods"] ? count($c["methods"]) : 0;
				for ($i = 0; $i < $t; $i++) {
					$sli = is_numeric($c["methods"][$i]["start_comments_line_index"]) ? $c["methods"][$i]["start_comments_line_index"] : $c["methods"][$i]["start_line_index"];
					$intervals[] = array($sli, $c["methods"][$i]["end_line_index"]);
				}
				
				$new_c = substr($line, $pos + 1);
				$idx = $start_brackets_line - 1;
				while (true) {
					++$idx;
					
					if ($idx + 1 >= $c["end_line_index"])
						break;
					
					$allowed = true;
					foreach ($intervals as $interval)
						if ($idx + 1 >= $interval[0] && $idx + 1 <= $interval[1]) {
							$allowed = false;
							break;
						}
					
					if ($allowed && trim($lines[$idx])) 
						$new_c .= "\n" . $lines[$idx];
				}
				$new_c = trim($new_c);
				
				//PARSEING PROPERTIES
				$t = strlen($new_c);
				$open_single_quotes = $open_double_quotes = $open_multiple_comments = $open_single_comments = false;
				$prop_doc_comments = $prop_comments = array();
				
				for ($i = 0; $i < $t; $i++) {
					$char = $new_c[$i];
					
					if (!$open_single_quotes && !$open_double_quotes && !$open_multiple_comments && !$open_single_comments && ($char == '$' ||
						(strtolower($char) == "c" && strtolower($new_c[$i+1]) == "o" && strtolower($new_c[$i+2]) == "n" && strtolower($new_c[$i+3]) == "s" && strtolower($new_c[$i+4]) == "t")
					)) {
						$idx = $char == '$' ? $i : $i + 5;
						
						$settings = "";
						for ($j = $idx - 1; $j >= 0; --$j) {
							$char = $new_c[$j];
							
							if ($char == ";" || $char == "{" || $char == "}" || $char == "\n")
								break;
							else if ($char != "\t") 
								$settings = $char . $settings;
						}
						
						$var_name = "";
						for ($j = $idx + 1; $j < $t; $j++) {
							$char = $new_c[$j];
							
							if ($char == " " || $char == "=" || $char == "\t" || $char == "\n" || $char == ";")
								break;
							else 
								$var_name .= $char;
						}
						
						$value = "";
						$start_value = false;
						for ($j = $j - 1; $j < $t; $j++) {
							$char = $new_c[$j];
							
							if ($start_value) {
								$value .= $char;
								
								if ($char == "'" && !$open_double_quotes && !$open_multiple_comments && !$open_single_comments && !TextSanitizer::isCharEscaped($new_c, $j)) 
									$open_single_quotes = !$open_single_quotes;
								else if ($char == '"' && !$open_single_quotes && !$open_multiple_comments && !$open_single_comments && !TextSanitizer::isCharEscaped($new_c, $j)) 
									$open_double_quotes = !$open_double_quotes;
								else if ($char == '/' && $new_c[$j + 1] == "*" && !$open_single_quotes && !$open_double_quotes && !$open_single_comments) {
									$open_multiple_comments = true;
									++$j;
								}
								else if ($open_multiple_comments && $char == '*' && $new_c[$j + 1] == "/") {
									$open_multiple_comments = false;
									++$j;
								}
								else if ($char == '/' && $new_c[$j + 1] == "/" && !$open_single_quotes && !$open_double_quotes && !$open_multiple_comments) {
									$open_single_comments = true;
									++$j;
								}
								else if ($open_single_comments && $char == "\n")
									$open_single_comments = false;
							}
							
							if (!$open_single_quotes && !$open_double_quotes && !$open_multiple_comments && !$open_single_comments) {
								if ($char == ";") {
									if ($j == $i + 1)
										$value = null;
									else
										$value = substr($value, 0, strlen($value) - 1);//remove ;
									
									break;
								}
								else if (!$start_value && $char == "=")
									$start_value = true;
							}
						}
						$value = $value ? trim($value) : $value;
						
						$i = $j;
						
						$settings = trim(strtolower($settings));
						$type = strpos($settings, "private") !== false ? "private" : (strpos($settings, "protected") !== false ? "protected" : "public");
						
						$quote_char = substr($value, 0, 1);
						$var_type = $value && ($quote_char == '"' || $quote_char == "'") ? "string" : "";
						
						if ($quote_char == '"' || $quote_char == "'") {
							$value = substr($value, 1);
							$value = substr($value, -1) == $quote_char ? substr($value, 0, -1) : $value;
							$value = $quote_char == '"' ? str_replace('\\"', '"', $value) : str_replace("\\'", "'", $value);//in case of slashes and \" or \' accorddingly. Do not add the addcslashes here. We only want to add the \\" and \\', because we are getting php code directly.
							
							if ($quote_char == "'")
								$value = str_replace('$', '\\$', $value);//$variables inside of single quotes are escaped because are simply strings and not variables.
						}
						
						//check inline comments
						$pos = strpos($new_c, "\n", $i);
						$pos = $pos !== false ? $pos : $t;
						$aux = trim(substr($new_c, $i + 1, $pos - $i));
						
						if ($aux && substr($aux, 0, 2) == "//") {
							$prop_comments[] = $aux;
							$i = $pos;
						}
						
						$properties[] = array(
							"name" => $var_name,
							"type" => $type,
							"value" => $value,
							"var_type" => $var_type,
							"static" => strpos($settings, "static") !== false,
							"const" => strpos($settings, "const") !== false,
							"doc_comments" => $prop_doc_comments ? $prop_doc_comments : null,
							"comments" => $prop_comments ? $prop_comments : null,
						);
						
						$prop_doc_comments = $prop_comments = array();
					}
					else if ($char == "'" && !$open_double_quotes && !$open_multiple_comments && !$open_single_comments && !TextSanitizer::isCharEscaped($new_c, $i)) {
						$open_single_quotes = !$open_single_quotes;
					}
					else if ($char == '"' && !$open_single_quotes && !$open_multiple_comments && !$open_single_comments && !TextSanitizer::isCharEscaped($new_c, $i)) {
						$open_double_quotes = !$open_double_quotes;
					}
					else if ($char == '/' && $new_c[$i + 1] == "*" && !$open_single_quotes && !$open_double_quotes && !$open_single_comments) {
						$pos = strpos($new_c, "*" . "/", $i); //"*" . "/" is bc this method code is commented!
						$pos = $pos !== false ? $pos + 1 : $t;
						$prop_doc_comments[] = substr($new_c, $i, $pos - $i + 1);
						
						$open_multiple_comments = true;
						++$i;
					}
					else if ($open_multiple_comments && $char == '*' && $new_c[$i + 1] == "/") {
						$open_multiple_comments = false;
						++$i;
					}
					else if ($char == '/' && $new_c[$i + 1] == "/" && !$open_single_quotes && !$open_double_quotes && !$open_multiple_comments) {
						$pos = strpos($new_c, "\n", $i);
						$pos = $pos !== false ? $pos : $t;
						$prop_comments[] = substr($new_c, $i, $pos - $i);
						
						$open_single_comments = true;
						++$i;
					}
					else if ($open_single_comments && $char == "\n") {
						$open_single_comments = false;
					}
				}
			}
		}
		
		return $properties;
	}*/
	
	public static function getFunctionCodeFromFile($file_path, $func_name, $class_name = 0, $raw = false) {
		if ($file_path && file_exists($file_path)) {
			$contents = file_get_contents($file_path);
			return self::getFunctionCodeFromString($contents, $func_name, $class_name, $raw);
		}
		
		return false;
	}
	
	public static function getFunctionCodeFromString($contents, $func_name, $class_name = 0, $raw = false) {
		$f = self::getFunctionFromString($contents, $func_name, $class_name);
		
		if ($f) {
			//getting token from file
			$tokens = token_get_all($contents);
			$count = count($tokens);
			
			//getting function code
			$function_code = "";
			
			if (isset($f["start_brackets_token_index"]) && isset($f["end_brackets_token_index"]) && is_numeric($f["start_brackets_token_index"]) && is_numeric($f["end_brackets_token_index"]))
				for ($i = $f["start_brackets_token_index"] + 1; $i < $f["end_brackets_token_index"]; $i++)
					$function_code .= is_array($tokens[$i]) ? $tokens[$i][1] : $tokens[$i];
			
			if (!$raw) {
				$function_code = trim($function_code);
				$function_code = str_replace("\r\n", "\n", $function_code);
				$function_code = str_replace("\n\t", "\n", $function_code);
				
				if ($class_name)
					$function_code = str_replace("\n\t", "\n", $function_code);
			}
			
			return $function_code;
		}
		
		return false;
	}
	/* This is DEPRECATED bc if php is obfuscated this doesn't work.
	public static function getFunctionCodeFromFile($file_path, $function_name, $class_name = 0) {
		if ($file_path && file_exists($file_path) && $function_name) {
			$f = self::getFunctionFromFile($file_path, $function_name, $class_name);
			$function_name = $f["name"];
			$start_brackets_line = $f["start_brackets_line_index"];
			$end_line = $f["end_line_index"];
			
			if (is_numeric($start_brackets_line)) {
				$contents = file_get_contents($file_path);
				$contents = str_replace("\r\n", "\n", $contents);
				$lines = explode("\n", $contents);
				
				//TODO: change this code to work with obfuscated php
				
				$code = "";
				
				$line = $lines[ $start_brackets_line - 1 ];
				$pos = strpos($line, "{");
				$pos = $pos !== false ? $pos : strlen($line);
				$new_c = trim(substr($line, $pos + 1));
				if ($new_c)
					$code .= "$new_c";
				
				$idx = $start_brackets_line - 1;
				while (true) {
					++$idx;
					
					if ($idx + 1 >= $end_line) 
						break;
					
					$l = str_replace("\n", "", $lines[$idx]);
					$l = $l[0] == "\t" ? substr($l, 1) : $l;
					if ($class_name)//it executes twice if it is a method.
						$l = $l[0] == "\t" ? substr($l, 1) : $l;
					
					$code .= "\n$l";
				}
				
				$line = $lines[ $end_line - 1 ];
				$pos = strpos($line, "}");
				$pos = $pos !== false ? $pos : strlen($line);
				$new_c = trim(substr($line, 0, $pos));
				if ($new_c) 
					$code .= "\n$new_c";
				
				return trim($code);
			}
		}
		
		return false;
	}*/
	
	public static function getCodeWithoutCommentsFromFile($file_path) {
		if ($file_path && file_exists($file_path)) {
			$code = file_get_contents($file_path);
			return self::getCodeWithoutComments($code);
		}
	}
	
	public static function getCodeWithoutComments($code) {
		if ($code && (strpos($code, "//") !== false || strpos($code, "/*") !== false)) {
			$new_code  = '';
		
			$comment_tokens = array(T_COMMENT);
		
			if (defined('T_DOC_COMMENT'))
				$comment_tokens[] = T_DOC_COMMENT; // PHP 5
			if (defined('T_ML_COMMENT'))
				$comment_tokens[] = T_ML_COMMENT;  // PHP 4
		
			$tokens = token_get_all($code);

			foreach ($tokens as $token) {    
				if (is_array($token)) {
					if (in_array($token[0], $comment_tokens))
					    continue 1;

					$token = $token[1];
				}

				$new_code .= $token;
			}

			return $new_code;
		}
		
		return $code;
	}
	/* END: GETTERS */
	
	/* START: RENAMES */
	//$old_class_name and $new_class_name must be the full class names with namespaces
	public static function renameClassFromFile($file_path, $old_class_name, $new_class_name) {
		if ($file_path && file_exists($file_path) && $old_class_name && $new_class_name && $old_class_name != $new_class_name) {
			$c = self::getClassFromFile($file_path, $old_class_name);
			$cs = self::decoupleClassNameWithNameSpace($new_class_name);
			
			$new_class_settings = $c;
			$new_class_settings["name"] = isset($cs["name"]) ? $cs["name"] : null;
			$new_class_settings["namespace"] = isset($cs["namespace"]) ? $cs["namespace"] : null;
			$new_class_settings["comments"] = null; //remove comments in case exists bc to edit comments is not here. This will only avoid error in the getClassString method. it will not remove comments from file.
			
			return self::editClassFromFile($file_path, $c, $new_class_settings);
		}
		
		return false;
	}
	
	public static function renameFunctionFromFile($file_path, $old_func_name, $new_func_name, $class_name = 0) {
		if ($file_path && file_exists($file_path) && $old_func_name && $new_func_name && $old_func_name != $new_func_name) {
			$f = self::getFunctionFromFile($file_path, $old_func_name, $class_name);
			
			$new_func_settings = $f;
			$new_func_settings["name"] = $new_func_name;
			$new_func_settings["comments"] = null; //remove comments in case exists bc to edit comments is not here. This will only avoid error in the getClassString method. it will not remove comments from file.
			
			return self::editFunctionFromFile($file_path, $f, $new_func_settings, $class_name);
		}
		
		return false;
	}
	/* END: RENAMES */
	
	/* START: EDITS */
	public static function editClassFromFile($file_path, $old_class_settings, $new_class_settings) {
		if (!empty($new_class_settings["name"])) {
			$old_class_name = isset($old_class_settings["name"]) ? $old_class_settings["name"] : null;
			$old_class_namespace = isset($old_class_settings["namespace"]) ? $old_class_settings["namespace"] : null;
			$old_class_path = self::prepareClassNameWithNameSpace($old_class_name, $old_class_namespace);
			$c = self::getClassFromFile($file_path, $old_class_path);
			
			if ($c) {
				//getting token from file
				$contents = file_get_contents($file_path);
				$tokens = token_get_all($contents);
				$count = count($tokens);
				
				//editing class
				if (isset($c["start_token_index"]) && isset($c["start_brackets_token_index"]) && is_numeric($c["start_token_index"]) && is_numeric($c["start_brackets_token_index"])) {
					$new_contents = "";
					
					for ($i = 0; $i < $c["start_token_index"]; $i++)
						$new_contents .= is_array($tokens[$i]) ? $tokens[$i][1] : $tokens[$i];
					
					//remove namespace from $new_class_settings bc they will be take care after
					$new_namespace = isset($new_class_settings["namespace"]) ? $new_class_settings["namespace"] : null;
					$new_includes = isset($new_class_settings["includes"]) ? $new_class_settings["includes"] : null;
					$new_uses = isset($new_class_settings["uses"]) ? $new_class_settings["uses"] : null;
					$new_class_settings["namespace"] = null;
					$new_class_settings["includes"] = null;
					$new_class_settings["uses"] = null;
					
					$new_contents .= self::getClassString($new_class_settings) . " ";
					
					for ($i = $c["start_brackets_token_index"]; $i < $count; $i++)
						$new_contents .= is_array($tokens[$i]) ? $tokens[$i][1] : $tokens[$i];
					
					if ($new_contents == $contents || file_put_contents($file_path, $new_contents) !== false) {
						$status = true;
						$new_class_settings["namespace"] = $new_namespace;
						
						//update namespace if different
						if ($old_class_namespace != $new_class_settings["namespace"] && ($old_class_namespace || $new_class_settings["namespace"])) 
							$status = self::replaceNamespaceFromFile($file_path, $old_class_namespace, $new_class_settings["namespace"]);
						
						//add includes and uses to the begining of file or after the first namespace
						if ($status && ($new_includes || $new_uses)) {
							$new_c = "";
							
							if ($new_includes)
								foreach ($new_includes as $include)
									if (is_array($include) && trim($include[0]))
										$new_c .= "include" . ($include[1] ? "_once" : "") . " " . $include[0] . ";\n";
									else if (!is_array($include) && trim($include))
										$new_c .= "include_once $include;\n";
							
							if ($new_uses)
								foreach ($new_uses as $use => $alias)
									if (trim($use))
										$new_c .= "use $use" . (trim($alias) ? " as $alias" : "") . ";\n";
							
							$status = self::addCodeToBeginOfFileOrAfterFirstNamespace($file_path, $new_c);
						}
						
						if ($status && isset($new_class_settings["code"])) {
							$new_class_name = self::prepareClassNameWithNameSpace($new_class_settings["name"], $new_class_settings["namespace"]);
							
							$status = self::removeClassPropertiesFromFile($file_path, $new_class_name) && self::addClassPropertiesToFile($file_path, $new_class_name, $new_class_settings["code"]);
						}
						
						//if status false, re-save the old code
						if (!$status)
							file_put_contents($file_path, $contents);
						
						return $status;
					}
				}
			}
		}
		
		return false;
	}
	/* This is DEPRECATED bc if php is obfuscated this doesn't work.
	public static function editClassFromFile($file_path, $old_class_settings, $new_class_settings) {
		$old_class_name = self::prepareClassNameWithNameSpace($old_class_settings["name"], $old_class_settings["namespace"]);
		
		if ($file_path && file_exists($file_path) && $old_class_name && $new_class_settings["name"]) {
			$c = self::getClassFromFile($file_path, $old_class_name);
			
			$old_class_name = $c["name"];
			$start_line = $c["start_line_index"];
			$start_brackets_line = $c["start_brackets_line_index"];
			
			if (is_numeric($start_line)) {
				$contents = file_get_contents($file_path);
				$contents = str_replace("\r\n", "\n", $contents);
				$lines = explode("\n", $contents);
				
				//TODO: change this code to work with obfuscated php
				
				$new_contents = implode("\n", array_slice($lines, 0, $start_line - 1) );
				
				$line = $lines[ $start_line - 1 ];
				$new_c = "";
				$parts = explode(" ", $line);
				$t = count($parts);
				for ($i = 0; $i < $t; $i++) {
					if (strtolower(trim($parts[$i])) == "class" || strtolower(trim($parts[$i])) == "abstract") 
						break;
					else 
						$new_c .= ($i > 0 ? " " : "") . $parts[$i];
				}
				
				$new_c = trim($new_c);
				if ($new_c) 
					$new_contents .= "\n$new_c";
				
				//remove namespace from $new_class_settings bc they will be take care after
				$new_namespace = $new_class_settings["namespace"];
				$new_includes = $new_class_settings["includes"];
				$new_uses = $new_class_settings["uses"];
				$new_class_settings["namespace"] = null;
				$new_class_settings["includes"] = null;
				$new_class_settings["uses"] = null;
				
				$new_contents .= "\n" . self::getClassString($new_class_settings);
				
				if ($start_brackets_line == $start_line) 
					$pos = strpos($line, "{", strpos($line, $old_class_name) + 1);
				else {
					$line = $lines[ $start_brackets_line - 1 ];
					$pos = strpos($line, "{");
				}
				
				$pos = $pos !== false ? $pos : strlen($line);
				$new_c = trim(substr($line, $pos));
				if ($new_c) 
					$new_contents .= " $new_c";
				
				$new_contents .= "\n" . implode("\n", array_slice($lines, $start_brackets_line) );
				
				$new_contents = str_replace("\n\n\n", "\n\n", str_replace("\t\n", "\n", $new_contents));
				
				if (file_put_contents($file_path, $new_contents) > 0) {
					$status = true;
					$new_class_settings["namespace"] = $new_namespace;
					
					//update namespace if different
					if ($old_class_settings["namespace"] != $new_class_settings["namespace"] && ($old_class_settings["namespace"] || $new_class_settings["namespace"])) 
						$status = self::replaceNamespaceFromFile($file_path, $old_class_settings["namespace"], $new_class_settings["namespace"]);
					
					
					//add includes and uses to the begining of file or after the first namespace
					if ($status && ($new_includes || $new_uses)) {
						$new_c = "";
						
						if ($new_includes)
							foreach ($new_includes as $include)
								if (is_array($include) && trim($include[0]))
									$new_c .= "include" . ($include[1] ? "_once" : "") . " " . $include[0] . ";\n";
								else if (!is_array($include) && trim($include))
									$new_c .= "include_once $include;\n";
						
						if ($new_uses)
							foreach ($new_uses as $use => $alias)
								if (trim($use))
									$new_c .= "use $use" . (trim($alias) ? " as $alias" : "") . ";\n";
						
						$status = self::addCodeToBeginOfFileOrAfterFirstNamespace($file_path, $new_c);
					}
					
					if ($status && isset($new_class_settings["code"])) {
						$new_class_name = self::prepareClassNameWithNameSpace($new_class_settings["name"], $new_class_settings["namespace"]);
						
						$status = self::removeClassPropertiesFromFile($file_path, $new_class_name) && self::addClassPropertiesToFile($file_path, $new_class_name, $new_class_settings["code"]);
					}
					
					//if status false, re-save the old code
					if (!$status)
						file_put_contents($file_path, $contents);
					
					return $status;
				}
			}
		}
		
		return false;
	}*/
	
	public static function editFunctionFromFile($file_path, $old_func_settings, $new_func_settings, $class_name = 0) {
		$old_func_name = isset($old_func_settings["name"]) ? strtolower(trim($old_func_settings["name"])) : "";
		
		if ($old_func_name && !empty($new_func_settings["name"])) {
			$f = self::getFunctionFromFile($file_path, $old_func_name, $class_name);
			
			if ($f) {
				//getting token from file
				$contents = file_get_contents($file_path);
				$tokens = token_get_all($contents);
				$count = count($tokens);
				
				//editing function
				if (isset($f["start_token_index"]) && isset($f["start_brackets_token_index"]) && is_numeric($f["start_token_index"]) && is_numeric($f["start_brackets_token_index"])) {
					$new_contents = "";
					
					for ($i = 0; $i < $f["start_token_index"]; $i++)
						$new_contents .= is_array($tokens[$i]) ? $tokens[$i][1] : $tokens[$i];
					
					$is_abstract_or_interface = $tokens[ $f["start_brackets_token_index"] ] == ";";
					$new_contents .= trim( self::getFunctionString($new_func_settings, $class_name) ) . ($is_abstract_or_interface ? "" : " ");
					
					for ($i = $f["start_brackets_token_index"]; $i < $count; $i++)
						$new_contents .= is_array($tokens[$i]) ? $tokens[$i][1] : $tokens[$i];
					
					if ($new_contents == $contents || file_put_contents($file_path, $new_contents) !== false) {
						$status = true;
						
						if (isset($new_func_settings["code"])) 
							$status = self::replaceFunctionCodeFromFile($file_path, $new_func_settings["name"], $new_func_settings["code"], $class_name);
						
						//if status false, re-save the old code
						if (!$status)
							file_put_contents($file_path, $contents);
						
						return $status;
					}
				}
			}
		}
		
		return false;
	}
	/* This is DEPRECATED bc if php is obfuscated this doesn't work.
	public static function editFunctionFromFile($file_path, $old_func_settings, $new_func_settings, $class_name = 0) {
		$old_func_name = $old_func_settings["name"];
		
		if ($file_path && file_exists($file_path) && $old_func_name && $new_func_settings["name"]) {
			$f = self::getFunctionFromFile($file_path, $old_func_name, $class_name);
			$old_func_name = $f["name"];
			$start_line = $f["start_line_index"];
			$start_brackets_line = $f["start_brackets_line_index"];
			$end_line = $f["end_line_index"];
			
			if (is_numeric($start_line)) {
				$contents = file_get_contents($file_path);
				$contents = str_replace("\r\n", "\n", $contents);
				$lines = explode("\n", $contents);
				
				//TODO: change this code to work with obfuscated php
				
				$new_contents = implode("\n", array_slice($lines, 0, $start_line - 1) );
				
				$line = $lines[ $start_line - 1 ];
				$new_c = "";
				$parts = explode(" ", $line);
				$available_types = array("public", "private", "protected", "abstract", "static", "function");
				$t = count($parts);
				for ($i = 0; $i < $t; $i++) {
					if (in_array(strtolower(trim($parts[$i])), $available_types)) {
						break;
					}
					else {
						$new_c .= ($i > 0 ? " " : "") . $parts[$i];
					}
				}
				$new_c = trim($new_c);
				if ($new_c) 
					$new_contents .= "\n$new_c";
				
				$new_contents .= "\n" . self::getFunctionString($new_func_settings, $class_name);
				
				$line = $lines[ $start_brackets_line - 1 ];
				$pos = strpos($line, "{");
				$pos = $pos !== false ? $pos : strlen($line);
				$new_c = trim(substr($line, $pos));
				if ($new_c) 
					$new_contents .= " $new_c";
				
				$new_contents .= "\n" . implode("\n", array_slice($lines, $start_brackets_line) );
				
				$new_contents = str_replace("\n\n\n", "\n\n", str_replace("\t\n", "\n", $new_contents));
				if (file_put_contents($file_path, $new_contents) > 0) {
					$status = true;
					if (isset($new_func_settings["code"])) 
						$status = self::replaceFunctionCodeFromFile($file_path, $new_func_settings["name"], $new_func_settings["code"], $class_name);
					
					return $status;
				}
			}
		}
		
		return false;
	}*/
	
	public static function editClassCommentsFromFile($file_path, $class_name, $comments) {
		$c = self::getClassFromFile($file_path, $class_name);
		$valid = $c && (
			(isset($c["start_comments_token_index"]) && isset($c["end_comments_token_index"]) && is_numeric($c["start_comments_token_index"]) && is_numeric($c["end_comments_token_index"])) 
			|| 
			(isset($c["start_token_index"]) && is_numeric($c["start_token_index"]))
		);
		
		if ($valid) {
			//getting token from file
			$contents = file_get_contents($file_path);
			$tokens = token_get_all($contents);
			$count = count($tokens);
			
			//editing comments
			if (isset($c["start_comments_token_index"]) && is_numeric($c["start_comments_token_index"]) && isset($c["end_comments_token_index"]) && is_numeric($c["end_comments_token_index"])) {
				$st = $c["start_comments_token_index"];
				$et = $c["end_comments_token_index"] + 1;
			}
			else //adding comments
				$st = $et = $c["start_token_index"];
			
			//preparing new contents
			$new_contents = "";
			
			for ($i = 0; $i < $st; $i++)
				$new_contents .= is_array($tokens[$i]) ? $tokens[$i][1] : $tokens[$i];
			
			$comments = trim($comments);
			
			if ($comments)
				$new_contents .= $comments ? $comments . "\n" : "";
			else 
				$new_contents = rtrim($new_contents) . "\n\n"; //removes extra end-lines and spaces between the functions
			
			for ($i = $et; $i < $count; $i++)
				$new_contents .= is_array($tokens[$i]) ? $tokens[$i][1] : $tokens[$i];
			
			return $new_contents == $contents || file_put_contents($file_path, $new_contents) !== false;
		}
		
		return false;
	}
	/* This is DEPRECATED bc if php is obfuscated this doesn't work.
	public static function editClassCommentsFromFile($file_path, $class_name, $comments) {
		if ($class_name) {
			$c = self::getClassFromFile($file_path, $class_name);
			return self::editCommentsFromFile($file_path, $c, $comments);
		}
		return false;
	}*/
	
	public static function editFunctionCommentsFromFile($file_path, $func_name, $comments, $class_name = 0) {
		$f = self::getFunctionFromFile($file_path, $func_name, $class_name);
		$valid = $f && (
			(isset($f["start_comments_token_index"]) && isset($f["end_comments_token_index"]) && is_numeric($f["start_comments_token_index"]) && is_numeric($f["end_comments_token_index"])) 
			|| 
			(isset($f["start_token_index"]) && is_numeric($f["start_token_index"]))
		);
		
		if ($valid) {
			//getting token from file
			$contents = file_get_contents($file_path);
			$tokens = token_get_all($contents);
			$count = count($tokens);
			$suffix = "";
			
			//editing comments
			if (isset($f["start_comments_token_index"]) && is_numeric($f["start_comments_token_index"]) && isset($f["end_comments_token_index"]) && is_numeric($f["end_comments_token_index"])) {
				$st = $f["start_comments_token_index"];
				$et = $f["end_comments_token_index"] + 1;
			}
			else { //adding comments
				$st = $et = $f["start_token_index"];
				$suffix = $class_name ? "\t" : "";
			}
			
			//preparing new contents
			$new_contents = "";
			
			for ($i = 0; $i < $st; $i++)
				$new_contents .= is_array($tokens[$i]) ? $tokens[$i][1] : $tokens[$i];
			
			$comments = trim($comments);
			
			if ($comments)
				$new_contents .= ($class_name ? str_replace("\n", "\n\t", $comments) : $comments) . "\n$suffix";
			else 
				$new_contents = rtrim($new_contents) . ($class_name ? "\n\t\n\t" : "\n\n"); //removes extra end-lines and spaces between the functions
			
			for ($i = $et; $i < $count; $i++)
				$new_contents .= is_array($tokens[$i]) ? $tokens[$i][1] : $tokens[$i];
			
			return $new_contents == $contents || file_put_contents($file_path, $new_contents) !== false;
		}
		
		return false;
	}
	/* This is DEPRECATED bc if php is obfuscated this doesn't work.
	public static function editFunctionCommentsFromFile($file_path, $func_name, $comments, $class_name = 0) {
		if ($func_name) {
			$f = self::getFunctionFromFile($file_path, $func_name, $class_name);
			return self::editCommentsFromFile($file_path, $f, $comments);
		}
		return false;
	}*/
	
	/* This is DEPRECATED bc if php is obfuscated this doesn't work.
	private static function editCommentsFromFile($file_path, $obj_settings, $comments) {
		if ($file_path && file_exists($file_path)) {
		//echo "<pre>";print_r($obj_settings);die();
			$start_line = $obj_settings["start_comments_line_index"];
			$end_line = $obj_settings["end_comments_line_index"];
			
			if (is_numeric($start_line) || $comments) {
				if (!is_numeric($start_line)) {
					$start_line = $obj_settings["start_line_index"];
					$end_line = $obj_settings["start_line_index"];
				}
				
				$contents = file_get_contents($file_path);
				$contents = str_replace("\r\n", "\n", $contents);
				$lines = explode("\n", $contents);
				
				//TODO: change this code to work with obfuscated php
				
				$new_contents = implode("\n", array_slice($lines, 0, $start_line - 1) );
				
				$line = $lines[ $start_line - 1 ];
				$simple_comment_pos = strpos($line, "//");
				$multiple_comment_pos = strpos($line, "/*");
				
				$pos = $simple_comment_pos !== false && ($simple_comment_pos < $multiple_comment_pos || $multiple_comment_pos == false) ? $simple_comment_pos : $multiple_comment_pos;
				
				if ($pos !== false) {
					$c = substr($line, 0, $pos);
					$new_contents .= "\n" . (trim($c) ? $c : "");
					$new_contents .= trim($comments) ? "\n" . $comments : "";
					
					$line = $lines[ $end_line - 1 ];
					$simple_comment_pos = strrpos($line, "//");
					$multiple_comment_pos = strrpos($line, "*" . "/"); //"*" . "/" only bc this code is commented
					
					if ($simple_comment_pos === false && $multiple_comment_pos === false) {
						$new_contents .= trim($line) ? $line : "";
					}
					else if ($multiple_comment_pos !== false) {
						$c = substr($line, $multiple_comment_pos + 2);
						$new_contents .= trim($c) ? $c : "";
					}
				}
				else {
					$new_contents .= trim($comments) ? "\n" . $comments : "";
					$new_contents .= "\n" .  (trim($line) ? $line : "");
				}
				
				$new_contents .= "\n" . implode("\n", array_slice($lines, $end_line) );
				
				$new_contents = str_replace("\n\n\n", "\n\n", str_replace("\t\n", "\n", $new_contents));
				
				return file_put_contents($file_path, $new_contents) > 0;
			}
		}
		
		return false;
	}*/
	/* END: EDITS */
	
	/* START: REMOVES */
	
	public static function removeNamespacesFromFile($file_path) {
		return self::removeInstructionsFromFile($file_path, T_NAMESPACE);
	}
	
	public static function removeUsesFromFile($file_path) {
		return self::removeInstructionsFromFile($file_path, T_USE);
	}
	
	public static function removeIncludesFromFile($file_path) {
		$token_types = array(T_INCLUDE, T_INCLUDE_ONCE, T_REQUIRE, T_REQUIRE_ONCE);
		return self::removeInstructionsFromFile($file_path, $token_types);
	}
	
	//Note: Only remove instructions from the global scope. All instructions inside of brackets {...} will be ignored!
	private static function removeInstructionsFromFile($file_path, $token_types) {
		if ($file_path && file_exists($file_path) && $token_types) {
			$token_types = is_array($token_types) ? $token_types : array($token_types);
			
			$contents = file_get_contents($file_path);
			$tokens = token_get_all($contents);
			$count = count($tokens);
			$open_brackets_count = 0;
			$new_contents = "";
			//print_r($tokens);die();
			
			for ($i = 0; $i < $count; $i++) {
				$new_contents .= is_array($tokens[$i]) ? $tokens[$i][1] : $tokens[$i];
				
				if ($tokens[$i] == "{" || $tokens[$i][0] == T_CURLY_OPEN || $tokens[$i][0] == T_DOLLAR_OPEN_CURLY_BRACES)
					++$open_brackets_count;
				else if ($tokens[$i] == "}" || $tokens[$i][0] == "}"/* || $tokens[$i][0] == T_CURLY_CLOSE*/)
					--$open_brackets_count;
				else if ($open_brackets_count <= 0 && in_array($tokens[$i][0], $token_types)) { //only if outside of class or function
					$new_contents = preg_replace("/\s*" . $tokens[$i][1] . "$/", "", $new_contents); //remove spaces and include string
					
					while (true) {
						$token = $i + 1 < $count ? $tokens[++$i] : null;
						
						if (!isset($token)) 
							break;
						else if ($token == ";")
							break;
					}
				}
			}
			
			return $new_contents == $contents || file_put_contents($file_path, $new_contents) !== false;
		}
	}
	/* This is DEPRECATED bc if php is obfuscated this doesn't work.
	private static function removeInstructionsFromFile($file_path, $types) {
		if ($file_path && file_exists($file_path) && $types) {
			$types = is_array($types) ? $types : array($types);
			
			$contents = file_get_contents($file_path);
			$contents = str_replace("\r\n", "\n", $contents);
			$lines = explode("\n", $contents);
			
			//TODO: change this code to work with obfuscated php
			
			$new_contents = "";
			
			$t = count($lines);
			for ($i = 0; $i < $t; $i++) {
				$line = $lines[$i];
				$trimmed = trim($line);
				$exists = false;
				
				foreach ($types as $type) 
					if (stripos($trimmed, $type) === 0) {
						$fc = substr($trimmed, strlen($type), 1);
						
						if ($fc == " " || $fc == '$' || $fc == "'" || $fc == '"' || $fc == ";") {
							$exists = true;
							break;
						}
					}
				
				if (!$exists)
					$new_contents .= ($new_contents ? "\n" : "") . $line;
			}
			
			$new_contents = str_replace("\n\n\n", "\n\n", str_replace("\t\n", "\n", $new_contents));
			return file_put_contents($file_path, $new_contents) > 0;
		}
		
		return false;
	}*/
	
	public static function removeNamespaceFromFile($file_path, $namespace) {
		return self::removeInstructionFromFile($file_path, T_NAMESPACE, $namespace);
	}
	
	public static function removeUseFromFile($file_path, $use) {
		return self::removeInstructionFromFile($file_path, T_USE, $use);
	}
	
	public static function removeIncludeFromFile($file_path, $include) {
		$token_types = array(T_INCLUDE, T_INCLUDE_ONCE, T_REQUIRE, T_REQUIRE_ONCE);
		return self::removeInstructionFromFile($file_path, $token_types, $include);
	}
	
	//Note: Only remove instructions from the global scope. All instructions inside of brackets {...} will be ignored!
	private static function removeInstructionFromFile($file_path, $token_types, $value_to_search) {
		if ($file_path && file_exists($file_path) && $token_types) {
			$token_types = is_array($token_types) ? $token_types : array($token_types);
			
			$contents = file_get_contents($file_path);
			$tokens = token_get_all($contents);
			$count = count($tokens);
			$open_brackets_count = 0;
			$new_contents = "";
			//print_r($tokens);die();
			
			for ($i = 0; $i < $count; $i++) {
				$new_contents .= is_array($tokens[$i]) ? $tokens[$i][1] : $tokens[$i];
				
				if ($tokens[$i] == "{" || $tokens[$i][0] == T_CURLY_OPEN || $tokens[$i][0] == T_DOLLAR_OPEN_CURLY_BRACES)
					++$open_brackets_count;
				else if ($tokens[$i] == "}" || $tokens[$i][0] == "}"/* || $tokens[$i][0] == T_CURLY_CLOSE*/)
					--$open_brackets_count;
				else if ($open_brackets_count <= 0 && in_array($tokens[$i][0], $token_types)) { //only if outside of class or function
					$j = $i;
					$found_value = "";
					
					while (true) {
						$token = $j + 1 < $count ? $tokens[++$j] : null;
						
						if (!isset($token)) 
							break;
						else if ($token == ";")
							break;
						else
							$found_value .= is_array($token) ? $token[1] : $token;
					}
					
					if (trim($found_value) == trim($value_to_search)) {
						$new_contents = preg_replace("/\s*" . $tokens[$i][1] . "$/", "", $new_contents); //remove spaces and include string
						$i = $j;
					}
				}
			}
			
			return $new_contents == $contents || file_put_contents($file_path, $new_contents) !== false;
		}
	}
	
	public static function removeClassPropertiesFromFile($file_path, $class_name) {
		if (!file_exists($file_path))
			return true;
		
		$properties = self::getClassPropertiesFromFile($file_path, $class_name);
		
		if (!$properties && file_exists($file_path)) {
			$c = self::getClassFromFile($file_path, $class_name);
			return !empty($c);
		}
		
		//getting token from file
		$contents = file_get_contents($file_path);
		$tokens = token_get_all($contents);
		$count = count($tokens);
		
		//filter tokens to class contents
		$intervals = array();
		$start_token_index = 0;
		
		$t = count($properties);
		for ($i = 0; $i < $t; $i++) {
			$sti = isset($properties[$i]["start_comments_token_index"]) && is_numeric($properties[$i]["start_comments_token_index"]) ? $properties[$i]["start_comments_token_index"] : (
				isset($properties[$i]["start_token_index"]) ? $properties[$i]["start_token_index"] : null
			);
			
			//getting white spaces above
			$j = $sti;
			while(true) {
				$token = $j - 1 >= 0 ? $tokens[--$j] : null;
				
				if (!isset($token))
					break;
				else if ($token[0] == T_WHITESPACE)
					$sti = $j;
				else
					break;
			}
			
			$intervals[] = array($start_token_index, $sti - 1);
			$start_token_index = (isset($properties[$i]["end_token_index"]) ? $properties[$i]["end_token_index"] : null) + 1;
		}
		
		$intervals[] = array($start_token_index, $count - 1);
		
		//setting new contents
		$new_contents = "";
		
		foreach ($intervals as $interval) {
			$start_token_index = $interval[0];
			$end_token_index = $interval[1];
			
			for ($i = $start_token_index; $i <= $end_token_index; $i++) 
				$new_contents .= is_array($tokens[$i]) ? $tokens[$i][1] : $tokens[$i];
		}
		
		return $new_contents == $contents || file_put_contents($file_path, $new_contents) !== false;
	}
	/* This is DEPRECATED bc if php is obfuscated this doesn't work.
	public static function removeClassPropertiesFromFile($file_path, $class_name) {
		if ($file_path && file_exists($file_path) && $class_name) {
			$c = self::getClassFromFile($file_path, $class_name);
			$start_brackets_line = $c["start_brackets_line_index"];
			
			if (is_numeric($start_brackets_line)) {
				$contents = file_get_contents($file_path);
				$contents = str_replace("\r\n", "\n", $contents);
				$lines = explode("\n", $contents);
				
				//TODO: change this code to work with obfuscated php
				
				$new_contents = implode("\n", array_slice($lines, 0, $start_brackets_line - 1) );
				
				$line = $lines[ $start_brackets_line - 1 ];
				$pos = strpos($line, "{");
				$pos = $pos !== false ? $pos : strlen($line);
				$new_contents .= "\n" . substr($line, 0, $pos + 1);
				
				$intervals = array();
				$t = $c["methods"] ? count($c["methods"]) : 0;
				for ($i = 0; $i < $t; $i++) {
					$method = $c["methods"][$i];
					$sl = is_numeric($method["start_comments_line_index"]) ? $method["start_comments_line_index"] : $method["start_line_index"];
					$intervals[] = array($sl, $method["end_line_index"], $method);
				}
				
				$idx = $start_brackets_line - 1;
				while (true) {
					++$idx;
					
					if ($idx + 1 >= $c["end_line_index"]) 
						break;
					
					foreach ($intervals as $interval) {
						if ($idx + 1 >= $interval[0] && $idx + 1 <= $interval[1]) {
							if ($idx + 1 == $interval[0]) {
								$new_contents .= "\n";
							}
							
							$new_contents .= "\n" . $lines[$idx];
							break;
						}
					}
				}
				
				$new_contents .= "\n" . implode("\n", array_slice($lines, $idx) );
				
				$new_contents = str_replace("\n\n\n", "\n\n", str_replace("\t\n", "\n", $new_contents));
				return file_put_contents($file_path, $new_contents) > 0;
			}
		}
		
		return false;
	}*/
	
	public static function removeClassFromFile($file_path, $class_name) {
		if (!file_exists($file_path))
			return true;
		
		$c = self::getClassFromFile($file_path, $class_name);
		
		if (!$c && file_exists($file_path))
			return true;
		
		//getting token from file
		$contents = file_get_contents($file_path);
		$tokens = token_get_all($contents);
		$count = count($tokens);
		
		$start_token_index = isset($c["start_comments_token_index"]) && is_numeric($c["start_comments_token_index"]) ? $c["start_comments_token_index"] : (
			isset($c["start_token_index"]) ? $c["start_token_index"] : null
		);
		$end_token_index = isset($c["end_token_index"]) && is_numeric($c["end_token_index"]) ? $c["end_token_index"] : $count;
		
		//getting white spaces above
		$i = $start_token_index;
		while(true) {
			$token = $i - 1 >= 0 ? $tokens[--$i] : null;
			
			if (!isset($token))
				break;
			else if ($token[0] == T_WHITESPACE)
				$start_token_index = $i;
			else
				break;
		}
		
		//preparing new contents
		$new_contents = "";
		
		for ($i = 0; $i < $start_token_index; $i++)
			$new_contents .= is_array($tokens[$i]) ? $tokens[$i][1] : $tokens[$i];
		
		for ($i = $end_token_index + 1; $i < $count; $i++)
			$new_contents .= is_array($tokens[$i]) ? $tokens[$i][1] : $tokens[$i];
		
		return $new_contents == $contents || file_put_contents($file_path, $new_contents) !== false;
	}
	/* This is DEPRECATED bc if php is obfuscated this doesn't work.
	public static function removeClassFromFile($file_path, $class_name) {
		$class_name = $class_name ? trim($class_name) : $class_name;
		
		if ($file_path && file_exists($file_path) && $class_name) {
			self::editClassCommentsFromFile($file_path, $class_name, "");
			
			$contents = file_get_contents($file_path);
			$contents = str_replace("\r\n", "\n", $contents);
			
			$c = self::getClassFromFile($file_path, $class_name);
			$start_line = $c["start_line_index"];
			$end_line = $c["end_line_index"];
			
			//TODO: change this code to work with obfuscated php
			
			$available_types = array("class", "abstract");
			$new_contents = self::removeItemFromContents($contents, $start_line, $end_line, $available_types);
			
			$new_contents = str_replace("\n\n\n", "\n\n", str_replace("\t\n", "\n", $new_contents));
			return $contents != $new_contents ? file_put_contents($file_path, $new_contents) > 0 : true;
		}
		
		return false;
	}*/
	
	public static function removeFunctionFromFile($file_path, $func_name, $class_name = 0) {
		if (!file_exists($file_path))
			return true;
		
		$f = self::getFunctionFromFile($file_path, $func_name, $class_name);
		
		if (!$f && file_exists($file_path)) 
			return true;
		
		//getting token from file
		$contents = file_get_contents($file_path);
		$tokens = token_get_all($contents);
		$count = count($tokens);
		
		$start_token_index = isset($f["start_comments_token_index"]) && is_numeric($f["start_comments_token_index"]) ? $f["start_comments_token_index"] : (
			isset($f["start_token_index"]) ? $f["start_token_index"] : null
		);
		$end_token_index = isset($f["end_token_index"]) && is_numeric($f["end_token_index"]) ? $f["end_token_index"] : $count;
		
		//getting white spaces above
		$i = $start_token_index;
		while(true) {
			$token = $i - 1 >= 0 ? $tokens[--$i] : null;
			
			if (!isset($token))
				break;
			else if ($token[0] == T_WHITESPACE)
				$start_token_index = $i;
			else
				break;
		}
		
		//preparing new contents
		$new_contents = "";
		
		for ($i = 0; $i < $start_token_index; $i++)
			$new_contents .= is_array($tokens[$i]) ? $tokens[$i][1] : $tokens[$i];
		
		for ($i = $end_token_index + 1; $i < $count; $i++)
			$new_contents .= is_array($tokens[$i]) ? $tokens[$i][1] : $tokens[$i];
		
		return $new_contents == $contents || file_put_contents($file_path, $new_contents) !== false;
	}
	/* This is DEPRECATED bc if php is obfuscated this doesn't work.
	public static function removeFunctionFromFile($file_path, $func_name, $class_name = 0) {
		$func_name = $func_name ? trim($func_name) : false;
		
		if ($file_path && file_exists($file_path) && $func_name) {
			self::editFunctionCommentsFromFile($file_path, $func_name, "", $class_name);
			
			$f = self::getFunctionFromFile($file_path, $func_name, $class_name);
			$start_line = $f["start_line_index"];
			$end_line = $f["end_line_index"];
			
			$contents = file_get_contents($file_path);
			$contents = str_replace("\r\n", "\n", $contents);
			
			//TODO: change this code to work with obfuscated php
			
			$available_types = array("public", "private", "protected", "abstract", "static", "function");
			$new_contents = self::removeItemFromContents($contents, $start_line, $end_line, $available_types);
	
			$new_contents = str_replace("\n\n\n", "\n\n", str_replace("\t\n", "\n", $new_contents));
			return $contents != $new_contents ? file_put_contents($file_path, $new_contents) > 0 : true;
		}
		
		return false;
	}*/
	/* END: REMOVES */
	
	/* START: REPLACES */
	public static function replaceFunctionCodeFromFile($file_path, $func_name, $code, $class_name = 0) {
		$f = self::getFunctionFromFile($file_path, $func_name, $class_name);
		
		if ($f && isset($f["start_brackets_token_index"]) && isset($f["end_brackets_token_index"]) && is_numeric($f["start_brackets_token_index"]) && is_numeric($f["end_brackets_token_index"])) {
			//getting token from file
			$contents = file_get_contents($file_path);
			$tokens = token_get_all($contents);
			$count = count($tokens);
			
			//preparing new contents
			$new_contents = "";
			
			for ($i = 0; $i <= $f["start_brackets_token_index"]; $i++)
				$new_contents .= is_array($tokens[$i]) ? $tokens[$i][1] : $tokens[$i];
			
			$code = $code ? trim($code) : "";
			
			$token = isset($tokens[ $f["start_brackets_token_index"] ]) ? $tokens[ $f["start_brackets_token_index"] ] : null;
			$is_abstract_or_interface = $token == ";";
			
			if ($code || !$is_abstract_or_interface) {
				if ($is_abstract_or_interface)
					$new_contents = substr($new_contents, 0, -1) . " {"; //replace ; by {
				
				$code = $class_name ? "\n\t\t" . str_replace("\n", "\n\t\t", $code) : "\n\t" . str_replace("\n", "\n\t", $code);
				$new_contents .= $code ? $code . "\n" . ($class_name ? "\t" : "") : "";
				
				if ($is_abstract_or_interface)
					$new_contents .= "}";
			}
			
			$ebti = $f["end_brackets_token_index"] + ($code && $is_abstract_or_interface ? 1 : 0);
			
			for ($i = $ebti; $i < $count; $i++)
				$new_contents .= is_array($tokens[$i]) ? $tokens[$i][1] : $tokens[$i];
			
			return $new_contents == $contents || file_put_contents($file_path, $new_contents) !== false;
		}
		
		return false;
	}
	/* This is DEPRECATED bc if php is obfuscated this doesn't work.
	public static function replaceFunctionCodeFromFile($file_path, $function_name, $code, $class_name = 0) {
		if ($file_path && file_exists($file_path) && $function_name) {
			$f = self::getFunctionFromFile($file_path, $function_name, $class_name);
			$function_name = $f["name"];
			$start_brackets_line = $f["start_brackets_line_index"];
			$end_line = $f["end_line_index"];
			
			if (is_numeric($start_brackets_line)) {
				$contents = file_get_contents($file_path);
				$contents = str_replace("\r\n", "\n", $contents);
				$lines = explode("\n", $contents);
				
				//TODO: change this code to work with obfuscated php
				
				$prefix = $class_name ? "\t" : "";
				
				$new_contents = implode("\n", array_slice($lines, 0, $start_brackets_line - 1) );
				
				$line = $lines[ $start_brackets_line - 1 ];
				$pos = strpos($line, "{");
				$pos = $pos !== false ? $pos : strlen($line);
				$new_c = trim(substr($line, 0, $pos + 1));
				if ($new_c)
					$new_contents .= "\n$prefix$new_c";
				
				$code = $code ? $code : "";
				$new_contents .= $class_name ? "\n$prefix$prefix" . str_replace("\n", "\n$prefix$prefix", $code) : "\n\t" . str_replace("\n", "\n\t", $code);
				
				$line = $lines[ $end_line - 1 ];
				$pos = strpos($line, "}");
				$pos = $pos !== false ? $pos : 0;
				$new_c = trim(substr($line, $pos));
				if ($new_c)
					$new_contents .= "\n$prefix$new_c";
				
				$new_contents .= "\n" . implode("\n", array_slice($lines, $end_line) );
			
				$new_contents = str_replace("\n\n\n", "\n\n", str_replace("\t\n", "\n", $new_contents));
				return file_put_contents($file_path, $new_contents) > 0;
			}
		}
		
		return false;
	}*/
	
	public static function replaceNamespaceFromFile($file_path, $old_namespace, $new_namespace) {
		if ($file_path && file_exists($file_path)) {
			if ($old_namespace && substr($old_namespace, 0, 1) == "\\")
				$old_namespace = substr($old_namespace, 1);
			
			if ($new_namespace && substr($new_namespace, 0, 1) == "\\")
				$new_namespace = substr($new_namespace, 1);
			
			if ($old_namespace && $new_namespace) {
				$contents = file_get_contents($file_path);
				$contents = str_ireplace("namespace $old_namespace;", "namespace $new_namespace;", $contents);
				return file_put_contents($file_path, $contents) > 0;
			}
			else if ($old_namespace)
				return self::removeNamespaceFromFile($file_path, $old_namespace);
			else if ($new_namespace) 
				return self::addCodeToBeginOfFile($file_path, "namespace $new_namespace;\n");
		}
		
		return false;
	}
	/* END: REPLACES */
	
	/* START: ADDS */
	public static function addNamespacesToFile($file_path, $namespaces) {
		if ($file_path && file_exists($file_path) && $namespaces) {
			$code = "";
			
			foreach ($namespaces as $namespace)
				if (trim($namespace)) {
					$namespace = trim($namespace);
					$namespace = substr($namespace, 0, 1) == "\\" ? substr($namespace, 1) : $namespace;
					
					$code .= "namespace $namespace;\n";
				}
			return self::addCodeToBeginOfFile($file_path, $code);
		}
		
		return false;
	}
	
	public static function addUsesToFile($file_path, $uses) {
		if ($uses) {
			$code = "";
			
			foreach ($uses as $use => $alias)
				if (trim($use))
					$code .= "use $use" . (trim($alias) ? " as $alias" : "") . ";\n";
			
			return self::addCodeToBeginOfFile($file_path, $code);
		}
		
		return false;
	}
	
	public static function addIncludesToFile($file_path, $includes) {
		if ($includes) {
			$code = "";
			
			foreach ($includes as $include)
				if (is_array($include) && trim($include[0]))
					$code .= "include" . ($include[1] ? "_once" : "") . " " . $include[0] . ";\n";
				else if (!is_array($include) && trim($include))
					$code .= "include_once $include;\n";
			
			return self::addCodeToBeginOfFile($file_path, $code);
		}
		
		return false;
	}
	
	public static function addCodeToBeginOfFile($file_path, $code) {
		if ($file_path && file_exists($file_path) && $code) {
			$contents = file_get_contents($file_path);
			$contents = str_replace("\r\n", "\n", $contents);
			
			$new_contents = "<?php\n$code?>\n" . trim($contents);
			$new_contents = str_replace(array("?>\n<?php", "?>\n<?"), "", $new_contents);
			
			$new_contents = str_replace("\n\n\n", "\n\n", str_replace("\t\n", "\n", $new_contents));
			return file_put_contents($file_path, $new_contents) > 0;
		}
		
		return false;
	}
	
	public static function addCodeToBeginOfFileOrAfterFirstNamespace($file_path, $code) {
		if ($file_path && file_exists($file_path) && $code) {
			$namespaces = self::getNamespacesFromFile($file_path);
			
			if ($namespaces) {
				$contents = file_get_contents($file_path);
				$contents = str_replace("\r\n", "\n", $contents);
				
				$ns_pos = stripos($contents, "namespace ");
				if (!is_numeric($ns_pos))
					$ns_pos = stripos($contents, "namespace");
				
				$ns_pos = strpos($contents, ";", $ns_pos);
				
				if (is_numeric($ns_pos)) {
					$ns_pos++; //add 1 to $ns_pos otherwise it will add the $code before the semicolon.
					$new_contents = substr($contents, 0, $ns_pos) . "\n\n" . $code . substr($contents, $ns_pos);
					$new_contents = str_replace("\n\n\n", "\n\n", str_replace("\t\n", "\n", $new_contents));
					
					return file_put_contents($file_path, $new_contents) > 0;
				}
			}
			else
				return self::addCodeToBeginOfFile($file_path, $code);
		}
		
		return false;
	}
	
	public static function addClassToFile($file_path, $class_settings) {
		$class_name = isset($class_settings["name"]) ? trim($class_settings["name"]) : "";
		
		if ($file_path && $class_name) {
			//PREPARING CLASS STRING
			$str = self::getClassString($class_settings);
			
			//PREPARING CLASS CODE
			$code = isset($class_settings["code"]) ? $class_settings["code"] : null;
			$code = $code ? "\t" . str_replace("\n", "\n\t", $code) : "";
			$str .= " {\n$code\n}\n";
		
			if (file_exists($file_path)) {
				$contents = file_get_contents($file_path);
				$contents = str_replace("\r\n", "\n", $contents);
				$pos = strrpos($contents, "?>");
			
				if ($pos !== false) 
					$contents = substr($contents, 0, $pos) . "\n\n$str" . substr($contents, $pos);
				else
					$contents .= (empty($contents) ? "<?php" : "\n") . "\n$str?>";
			}
			else 
				$contents = "<?php\n$str?>";
			
			$contents = str_replace("\n\n\n", "\n\n", str_replace("\t\n", "\n", $contents));
			return file_put_contents($file_path, $contents) > 0;
		}
		
		return false;
	}
	
	public static function addClassPropertiesToFile($file_path, $class_name, $code) {
		if (!trim($code))
			return true;
		
		$c = self::getClassFromFile($file_path, $class_name);
		
		if ($c && isset($c["start_brackets_token_index"]) && is_numeric($c["start_brackets_token_index"])) {
			//getting token from file
			$contents = file_get_contents($file_path);
			$tokens = token_get_all($contents);
			$count = count($tokens);
			
			//preparing new contents
			$new_contents = "";
			
			for ($i = 0; $i <= $c["start_brackets_token_index"]; $i++)
				$new_contents .= is_array($tokens[$i]) ? $tokens[$i][1] : $tokens[$i];
			
			$code = trim($code);
			$new_contents .= "\n\t" . str_replace("\n", "\n\t", $code) . "\n\t";
			
			for ($i = $c["start_brackets_token_index"] + 1; $i < $count; $i++)
				$new_contents .= is_array($tokens[$i]) ? $tokens[$i][1] : $tokens[$i];
			
			return $new_contents == $contents || file_put_contents($file_path, $new_contents) !== false;
		}
		
		return false;
	}
	/* This is DEPRECATED bc if php is obfuscated this doesn't work.
	public static function addClassPropertiesToFile($file_path, $class_name, $code) {
		if ($file_path && file_exists($file_path) && $class_name && $code) {
			$c = self::getClassFromFile($file_path, $class_name);
			$start_brackets_line = $c["start_brackets_line_index"];
			
			if (is_numeric($start_brackets_line)) {
				$contents = file_get_contents($file_path);
				$contents = str_replace("\r\n", "\n", $contents);
				$lines = explode("\n", $contents);
				
				//TODO: change this code to work with obfuscated php
				
				$new_contents = implode("\n", array_slice($lines, 0, $start_brackets_line - 1) );
				
				$line = $lines[ $start_brackets_line - 1 ];
				$pos = strpos($line, "{");
				$pos = $pos !== false ? $pos : strlen($line);
				$new_contents .= "\n" . substr($line, 0, $pos + 1);
				
				$new_contents .= "\n\t" . str_replace("\n", "\n\t", $code) . "\n\t";
				
				$new_c = trim( substr($line, $pos + 1) );
				if ($new_c) {
					$new_contents .= "\n" . $new_c;
				}
				
				$new_contents .= "\n" . implode("\n", array_slice($lines, $start_brackets_line) );
				
				$new_contents = str_replace("\n\n\n", "\n\n", str_replace("\t\n", "\n", $new_contents));
				return file_put_contents($file_path, $new_contents) > 0;
			}
		}
		
		return false;
	}*/
	
	public static function addFunctionToFile($file_path, $function_settings, $class_name = 0) {
		$func_name = isset($function_settings["name"]) ? trim($function_settings["name"]) : "";
		
		if ($func_name) {
			//getting token from file
			$contents = file_exists($file_path) ? file_get_contents($file_path) : "";
			
			//preparing new contents
			$new_contents = "";
			
			$code = isset($function_settings["code"]) ? $function_settings["code"] : null;
			$code = $code ? ($class_name ? "\t\t" . str_replace("\n", "\n\t\t", $code) : "\t" . str_replace("\n", "\n\t", $code)) : "";
			
			$str = ($class_name ? "\t" : "") . "\n" . self::getFunctionString($function_settings, $class_name);
			$str .= " {\n$code\n" . ($class_name ? "\t" : "") . "}\n";
			
			if ($class_name) {
				$c = self::getClassFromFile($file_path, $class_name);
				
				if ($c && isset($c["end_brackets_token_index"]) && is_numeric($c["end_brackets_token_index"])) {
					$tokens = token_get_all($contents);
					$count = count($tokens);
					
					for ($i = 0; $i < $c["end_brackets_token_index"]; $i++)
						$new_contents .= is_array($tokens[$i]) ? $tokens[$i][1] : $tokens[$i];
					
					$new_contents .= $str;
					
					for ($i = $c["end_brackets_token_index"]; $i < $count; $i++)
						$new_contents .= is_array($tokens[$i]) ? $tokens[$i][1] : $tokens[$i];
				}
			}
			else {
				$new_contents = $contents;
				
				if ($new_contents) {
					$pos = strrpos($new_contents, "?>");
					
					if ($pos !== false) 
						$new_contents = substr($new_contents, 0, $pos) . $str . substr($new_contents, $pos);
					else 
						$new_contents .= (strpos($new_contents, "<?") === false ? "<?php" : "") . "$str?>";
				}
				else 
					$new_contents = "<?php$str?>";
			}
			
			return $new_contents == $contents || file_put_contents($file_path, $new_contents) !== false;
		}
			
		return false;
	}
	/* This is DEPRECATED bc if php is obfuscated this doesn't work.
	public static function addFunctionToFile($file_path, $function_settings, $class_name = 0) {
		$name = trim($function_settings["name"]);
		
		if ($file_path && $name) {
			//PREPARING FUNCTION STRING
			$str = self::getFunctionString($function_settings, $class_name);
			
			$code = $function_settings["code"];
			$code = $code ? ($class_name ? "\t\t" . str_replace("\n", "\n\t\t", $code) : str_replace("\n", "\n\t", $code)) : "";
			$str .= " {\n$code\n}\n";
			
			//SAVING FUNCTION STRING TO CLASS
			if ($class_name) {
				if (file_exists($file_path)) {
					$c = self::getClassFromFile($file_path, $class_name);
					$end_line = $c["end_line_index"];
					
					if (is_numeric($end_line)) {
						$contents = file_get_contents($file_path);
						$contents = str_replace("\r\n", "\n", $contents);
						$lines = explode("\n", $contents);
					
						//TODO: change this code to work with obfuscated php
						
						$new_contents = implode("\n", array_slice($lines, 0, $end_line - 1) );
					
						$line = $lines[ $end_line - 1 ];
						$pos = strpos($line, "}");
						$pos = $pos !== false ? $pos : 0;
					
						$new_c = trim(substr($line, 0, $pos));
						if ($new_c) 
							$new_contents .= "\n$new_c";
					
						$new_contents .= "\n\n$str";
					
						$new_c = trim(substr($line, $pos));
						if ($new_c) 
							$new_contents .= "\n$new_c";
					
						$new_contents .= "\n" . implode("\n", array_slice($lines, $end_line) );
					
						$new_contents = str_replace("\n\n\n", "\n\n", str_replace("\t\n", "\n", $new_contents));
						return file_put_contents($file_path, $new_contents) > 0;
					}
				}
			}
			//SAVING FUNCTION STRING AS A GENERIC FUNCTION
			else {
				if (file_exists($file_path)) {
					$contents = file_get_contents($file_path);
					$contents = str_replace("\r\n", "\n", $contents);
					$pos = strrpos($contents, "?>");
			
					if ($pos !== false) 
						$contents = substr($contents, 0, $pos) . "\n\n$str\n" . substr($contents, $pos);
					else 
						$contents .= (empty($contents) ? "<?php" : "\n") . "\n$str?>";
				}
				else 
					$contents = "<?php\n$str\n?>";
				
				$contents = str_replace("\n\n\n", "\n\n", str_replace("\t\n", "\n", $contents));
				return file_put_contents($file_path, $contents) > 0;
			}
		}
		
		return false;
	}*/
	/* END: ADDS */
	
	/* START: UTILS */
	public static function getClassString($class_settings) {
		$name = isset($class_settings["name"]) ? trim($class_settings["name"]) : "";
		$hidden = isset($class_settings["hidden"]) ? $class_settings["hidden"] : null;
		$interface = isset($class_settings["interface"]) ? $class_settings["interface"] : null;
		$abstract = isset($class_settings["abstract"]) ? $class_settings["abstract"] : null;
		$extends = isset($class_settings["extends"]) ? $class_settings["extends"] : null;
		$implements = isset($class_settings["implements"]) ? $class_settings["implements"] : null;
		$namespace = isset($class_settings["namespace"]) ? $class_settings["namespace"] : null;
		$uses = isset($class_settings["uses"]) ? $class_settings["uses"] : null;
		$includes = isset($class_settings["includes"]) ? $class_settings["includes"] : null;
		$comments = isset($class_settings["comments"]) ? trim($class_settings["comments"]) : "";
		
		$extends = !$extends || is_array($extends) ? $extends : array($extends);
		$implements = !$implements || is_array($implements) ? $implements : array($implements);
		$includes = !$includes || is_array($includes) ? $includes : array($includes);
		
		$str = "";
		
		//prepare namespace
		if ($namespace)
			$str .= "namespace $namespace;\n\n";
		
		//prepare includes
		if ($includes) {
			foreach ($includes as $include)
				if (is_array($include) && trim($include[0]))
					$str .= "include" . ($include[1] ? "_once" : "") . " " . $include[0] . ";\n";
				else if (!is_array($include) && trim($include))
					$str .= "include_once $include;\n";
			$str .= "\n";
		}
		
		//prepare uses
		if ($uses)  {
			foreach ($uses as $use => $alias)
				if (trim($use))
					$str .= "use $use" . (trim($alias) ? " as $alias" : "") . ";\n";
			$str .= "\n";
		}
		
		//prepare hidden flag
		if ($comments) 
			$comments = preg_replace("/@hidden(\s|$)/", "", $comments);
		
		if ($hidden) {
			if ($comments) {
				if (strpos($comments, "/*") !== false) {
					if (preg_match("/(\s*)(\/\*+)(\s*\n\s*)/", $comments))
						$comments = preg_replace("/(\s*)(\/\*+)(\s*\n\s*)/", "$1$2$3* @hidden$3", $comments, 1);
					else
						$comments = preg_replace("/(\s*)(\/\*+)/", "$1$2\n$1* @hidden\n$1", $comments, 1);
				}
				else
					$comments = "@hidden\n" . $comments;
			}
			else
				$comments = "/**\n * @hidden\n */";
		}
		
		//prepare comments
		if ($comments) 
			$str .= (substr($comments, 0, 2) != "/*" ? "/**\n * " . str_replace("\n", "\n * ", $comments) . "\n */" : $comments) . "\n";
		
		$str .= ($abstract ? "abstract " : "") . ($interface ? "interface" : "class") . " $name";
	
		//prepare extends
		if ($extends) 
			$str .= " extends " . implode(", ", $extends);
		
		//prepare implements
		if ($implements) 
			$str .= " implements " . implode(", ", $implements);
		
		return $str;
	}
	
	public static function getFunctionString($function_settings, $class_name = false) {
		$name = isset($function_settings["name"]) ? trim($function_settings["name"]) : "";
		$hidden = isset($function_settings["hidden"]) ? $function_settings["hidden"] : null;
		$type = isset($function_settings["type"]) ? $function_settings["type"] : null;
		$abstract = isset($function_settings["abstract"]) ? $function_settings["abstract"] : null;
		$static = isset($function_settings["static"]) ? $function_settings["static"] : null;
		$args = isset($function_settings["arguments"]) ? $function_settings["arguments"] : null;
		$comments = isset($function_settings["comments"]) ? trim($function_settings["comments"]) : "";
		$args_str = "";
		
		//prepare args
		if (is_array($args))
			foreach ($args as $arg_name => $arg_value) {
				if ($arg_name) {
					$arg_name = trim($arg_name);
					$space_pos = strrpos($arg_name, " ");
					$arg_class_name = $space_pos !== false ? substr($arg_name, 0, $space_pos) : "";
					$arg_name = $space_pos !== false ? substr($arg_name, $space_pos + 1) : $arg_name;
					
					$arg_name = substr($arg_name, 0, 1) == '$' ? $arg_name : (
						substr($arg_name, 0, 1) == '&' ? (
							substr($arg_name, 1, 1) == '$' ? $arg_name : '&$' . substr($arg_name, 1) //allows cases like: "&name" => "&$name"
						) : "\$$arg_name"
					);
					
					$args_str .= ($args_str ? ", " : "") . ($arg_class_name ? "$arg_class_name " : "") . $arg_name . (isset($arg_value) ? " = $arg_value" : ""); //Note that the $arg_value could be equal to the string "null", which will be the default value for this argument.
				}
			}
		
		$prefix = $class_name ? "\t" : "";
		
		$str = "";
		
		//prepare hidden flag
		if ($comments) 
			$comments = preg_replace("/@hidden(\s|$)/", "", $comments);
		
		if ($hidden) {
			if ($comments) {
				if (strpos($comments, "/*") !== false) {
					if (preg_match("/(\s*)(\/\*+)(\s*\n\s*)/", $comments))
						$comments = preg_replace("/(\s*)(\/\*+)(\s*\n\s*)/", "$1$2$3* @hidden$3", $comments, 1);
					else
						$comments = preg_replace("/(\s*)(\/\*+)/", "$1$2\n$1* @hidden\n$1", $comments, 1);
				}
				else
					$comments = "@hidden\n" . $comments;
			}
			else
				$comments = "/**\n * @hidden\n */";
		}
		
		//prepare comments
		if ($comments) {
			if (substr($comments, 0, 2) != "/*")
				$str .= "$prefix/**\n$prefix * " . str_replace("\n", "\n$prefix * ", $comments) . "\n$prefix */\n";
			else 
				$str .= $prefix . str_replace("\n", "\n$prefix", $comments) . "\n";
		}
		
		//prepare name
		if ($class_name) {
			$type = !$type && $class_name ? "public" : strtolower($type);
		
			$str .= $prefix . trim(strtolower($type) . ($abstract ? " abstract" : "") . ($static ? " static" : "") . " function $name ($args_str)");
		}
		else 
			$str .= $prefix . trim("function $name ($args_str)");
		
		return $str;
	}
	
	public static function getClassPropertyString($property_settings) {
		$str = "";
		
		$name = isset($property_settings["name"]) ? trim($property_settings["name"]) : "";
		
		if ($name) {
			$type = !empty($property_settings["type"]) ? $property_settings["type"] : "public";
			$type = !empty($property_settings["const"]) ? "const" : $type;
			$static = !empty($property_settings["static"]) ? " static" : "";
			$var_type = isset($property_settings["var_type"]) ? $property_settings["var_type"] : null;
			$value = isset($property_settings["value"]) ? $property_settings["value"] : null;
			$comments = isset($property_settings["comments"]) ? trim($property_settings["comments"]) : "";
			
			$name = $type == "const" ? $name : "\$$name";
			$value = $var_type == "string" ? '"' . addcslashes($value, '"') . '"' : $value;
			
			if ($comments) {
				$prefix = "\t";
		
				if (substr($comments, 0, 2) != "/*")
					$str .= "$prefix/**\n$prefix * " . str_replace("\n", "\n$prefix * ", $comments) . "\n$prefix */\n";
				else 
					$str .= $prefix . str_replace("\n", "\n$prefix", $comments) . "\n";
			}
			
			$str .= "$type$static $name" . (strlen($value) ? " = $value" : "") . ";";
		}
		
		return $str;
	}
	
	/* This is DEPRECATED bc if php is obfuscated this doesn't work.
	private static function removeItemFromContents($contents, $start_line, $end_line, $available_types) {
		$new_contents = $contents;
		
		if (is_numeric($start_line)) {
			$lines = explode("\n", $contents);
		
			$end_line = $end_line > $start_line ? $end_line : count($lines);
		
			$new_contents = implode("\n", array_slice($lines, 0, $start_line - 1) );
		
			$line = $lines[ $start_line - 1 ];
			$new_c = "";
			$parts = explode(" ", $line);
			$t = count($parts);
			for ($i = 0; $i < $t; $i++) {
				if (in_array(strtolower(trim($parts[$i])), $available_types)) 
					break;
				else 
					$new_c .= ($i > 0 ? " " : "") . $parts[$i];
			}
			$new_c = trim($new_c);
		
			if ($new_c) 
				$new_contents .= "\n$new_c";
		
			$line = $lines[ $end_line - 1 ];
			$pos = strpos($line, "}");
			$pos = $pos !== false ? $pos : 0;
			$new_c = trim(substr($line, $pos + 1));
			if ($new_c) 
				$new_contents .= "\n$new_c";
		
			$new_contents .= "\n" . implode("\n", array_slice($lines, $end_line) );
		}
		
		return $new_contents;
	}*/
	
	private static function getAllFolderFiles($path, $recursively = false) {
		$files = array();
		
		if (is_dir($path) && ($dir = opendir($path)) ) {
			while( ($file = readdir($dir)) !== false)
				if($file != "." && $file != "..") {
					$file_path = $path . "/" . $file;
					
					if (is_dir($file_path)) {
						$sub_files = self::getAllFolderFiles($file_path, $recursively);
						$files = array_merge($files, $sub_files);
					}
					else {
						$extension = pathinfo($file_path, PATHINFO_EXTENSION);
					
						if ($extension == "php")
							$files[] = str_replace("//", "/", $file_path);
					}
				}
			closedir($dir);
		}
		return $files;
	}
	/* END: UTILS */
}
?>
