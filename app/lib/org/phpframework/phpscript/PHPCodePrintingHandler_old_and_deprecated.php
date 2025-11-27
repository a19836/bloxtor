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
		    $php_code = str_replace("\r\n", "\n", $php_code);
		    
		    return self::getPHPClassesFromString($php_code);
		}
		return array();
	}
	
	//http://es.php.net/manual/pt_BR/tokens.php
	public static function getPHPClassesFromString($php_code) {
		$methods = array();

		$tokens = token_get_all($php_code);
		//print_r($tokens);die();
		$count = count($tokens);
		$start_function = false;
		$open_brackets_count = 0;
		$namespace = "";
		
		for ($i = 1; $i < $count; $i++) {
			if ($tokens[$i] == "{" || $tokens[$i][0] == T_CURLY_OPEN || $tokens[$i][0] == T_DOLLAR_OPEN_CURLY_BRACES) {
				++$open_brackets_count;
			}
			else if ($tokens[$i] == "}" || $tokens[$i][0] == "}"/* || $tokens[$i][0] == T_CURLY_CLOSE*/) {
				$line_index = null;
				$token_index = $i;
				
				$j = $i - 1;
				while (true) {
					$token = $tokens[++$j];
					
					if (is_array($token) && is_numeric($token[2])) {
						$line_index = $token[2];
						break;
					}
					else if (!isset($token)) {
						break;
					}
				}
				
				if ($line_index) {
					if (!empty($class_path)) {
						$methods[$class_path]["end_line_index"] = $line_index;
						$methods[$class_path]["end_token_index"] = $token_index;
						
						//echo "$i-open_brackets_count:$class_path($line_index=$c):$open_brackets_count\n";
						if ($methods[$class_path]["methods"] && $open_brackets_count > 1) {
							$idx = count($methods[$class_path]["methods"]) - 1;
							$methods[$class_path]["methods"][$idx]["end_line_index"] = $line_index;
							$methods[$class_path]["methods"][$idx]["end_token_index"] = $token_index;
						}
					}
					else {
						$idx = $methods[0]["methods"] ? count($methods[0]["methods"]) - 1 : 0;
						$methods[0]["methods"][$idx]["end_line_index"] = $line_index;
						$methods[0]["methods"][$idx]["end_token_index"] = $token_index;
					}
				}
				
				--$open_brackets_count;
			}
			else if ($tokens[$i][0] == T_CLASS) {
				$class_path = "";
				$class_name = "";
				$line_index = $tokens[$i][2];
				$is_abstract = false;
				$start_function = $start_extends = $start_implements = false;
				$extends_index = $implements_index = 0;
				$open_brackets_count = 0;
				
				$class_idx = $i;
				
				//CHECKING IF ABSTRACT
				$j = $i - 1;
				while (true) {
					$token = $tokens[--$j];
					
					if (!is_array($token))
						break;
					else if ($token[0] == T_ABSTRACT) {
						$is_abstract = true;
						$line_index = $token[2];
						$class_idx = $j;
						break;
					}
					else if ($token[0] != T_COMMENT)
						break;
				}
				
				//PREPARING METHOD COMMENTS:
				self::prepareTokensComments($tokens, $class_idx, $class_comments);
				
				//GETTING CLASS NAME
				while (true) {
					$token = $tokens[++$i];
					
					if ($token == "{" || $token[0] == T_CURLY_OPEN || $token[0] == T_DOLLAR_OPEN_CURLY_BRACES) {
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
					
					$methods[$class_path] = array(
						"name" => $class_name,
						"start_line_index" => $line_index,
						"start_token_index" => $class_idx,
						"abstract" => $is_abstract,
						"comments" => $class_comments["comments"],
						"doc_comments" => $class_comments["doc_comments"],
						"start_comments_line_index" => $class_comments["start_comments_line_index"],
						"end_comments_line_index" => $class_comments["end_comments_line_index"],
						"namespace" => $namespace,
					);
					
					while (true) {
						$token = $tokens[++$i];
						
						if ($token == "{" || $token[0] == T_CURLY_OPEN || $token[0] == T_DOLLAR_OPEN_CURLY_BRACES) {
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
						else if ( ($start_extends || $start_implements) && ($token[0] == T_STRING || $token[0] == T_NS_SEPARATOR)) {
							$type = $start_extends ? "extends" : "implements";
							$type_index = $start_extends ? $extends_index : $implements_index;
							
							if ($methods[$class_path][$type][$type_index])
								$methods[$class_path][$type][$type_index] .= $token[1];
							else
								$methods[$class_path][$type][$type_index] = $token[1];
						}
					}
				}
				
				//PREPARING OPEN BRACKETS: {
				++$open_brackets_count;
				
				while (true) {
					$token = $tokens[++$i];
					
					if ($token == "{" || $token[0] == T_CURLY_OPEN || $token[0] == T_DOLLAR_OPEN_CURLY_BRACES) {
						if (!empty($class_name)) {
							$methods[$class_path]["start_brackets_line_index"] = is_numeric($tokens[$i][2]) ? $tokens[$i][2] : $tokens[$i + 1][2];
							$methods[$class_path]["start_brackets_token_index"] = $i;
						}
						break;
					}
					else if (!isset($token)) {
						break;
					}
				}
			}
			else if ($tokens[$i][0] == T_NAMESPACE) {
				$namespace = "";
				
				while (true) {
					$token = $tokens[$i + 1];
					
					if ($token == ";")
						break;
					
					$char = is_array($token) ? $token[1] : $token;
					$namespace .= $char;
					
					$i++;
				}
				
				$namespace = trim($namespace);
				
				if ($namespace)
					$methods[0]["namespaces"][] = $namespace;
			}
			else if ($tokens[$i][0] == T_USE) {
				$use_name = $use_alias = "";
				$use_alias_active = false;
				
				while (true) {
					$token = $tokens[$i + 1];
					
					if ($token == ";")
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
						
					$i++;
				}
				
				$use_name = trim($use_name);
				$use_alias = trim($use_alias);
				
				if ($use_name)
					$methods[0]["uses"][$use_name] = $use_alias;
			}
			else if ($tokens[$i][0] == T_FUNCTION) {
				$start_function = true;
			}
			else if ($start_function && $tokens[$i][0] == T_STRING) {
				$start_function = false;
				
				if ($open_brackets_count <= 0)
					$class_name = $class_path = "";
				
				$method_name = $tokens[$i][1];
				$line_index = $tokens[$i][2];
				$method = array();
				
				//echo "$class_path::$method_name:$open_brackets_count\n";
				
				//PREPARING METHOD PROPERTIES:
				$type = false;
				$is_abstract = false;
				$is_static = false;
				
				$function_type_idx = $i - 1;
				while (true) {
					$token = $tokens[--$function_type_idx];
					
					if (!is_array($token)) {
						break;
					}
					else if ($token[0] == T_PUBLIC) {
						$type = "public";
						$line_index = $token[2];
						break;
					}
					else if ($token[0] == T_PRIVATE) {
						$type = "private";
						$line_index = $token[2];
						break;
					}
					else if ($token[0] == T_PROTECTED) {
						$type = "protected";
						$line_index = $token[2];
						break;
					}
					else if ($token[0] == T_FUNCTION) {
						$line_index = $token[2];
					}
					else if ($token[0] == T_STATIC) {
						$is_static = true;
						$line_index = $token[2];
					}
					else if ($token[0] == T_ABSTRACT) {
						$is_abstract = true;
						$line_index = $token[2];
					}
					else if ($token[0] != T_WHITESPACE) {
						$function_type_idx++;
						break;
					}
				}
				
				if ($method_name == "__construct" && !empty($class_name))
					$type = "construct";
				else
					$type = $type ? $type : "public";
				
				$method = array(
					"name" => $method_name,
					"type" => $type,
					"start_line_index" => $line_index,
					"abstract" => $is_abstract,
					"static" => $is_static,
				);
				
				//PREPARING METHOD COMMENTS:
				self::prepareTokensComments($tokens, $function_type_idx, $method);
				
				//PREPARING METHOD ARGUMENTS:
				$arguments = array();
				$start_args = $arg_name = $start_arg_value = false;
				
				while (true) {
					$token = $tokens[++$i];
					
					if ($token == "(")
						$start_args = true;
					else if ($token == ")" || empty($token))
						break;
					else if (!isset($token))
						break;
					else if ($start_args) {
						if ($token[0] == T_VARIABLE) {
							$start_arg_value = false;
							$arg_name = $token[1];
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
									$token = $tokens[++$i];
									
									if ($token == "(") 
										$open_paranteses_count++;
									elseif ($token == ")") {
										$open_paranteses_count--;
										
										if ($open_paranteses_count == 0) {
											$arg_value .= ")";
											++$i;
										}
									}
									else if (!isset($token)) 
										break;
									
									if ($open_paranteses_count <= 0 && ($token == "," || $token == ")")) 
										break;
								} 
								while(true);
								
								$arguments[ $arg_name ] = trim($arg_value);
								//echo "$arg_name: ".$arguments[ $arg_name ]."!$open_paranteses_count!".print_r($token, true)."\n<br>";
								
								$arg_name = false;
								$start_arg_value = false;
								--$i;
							}
							else if ($token == ",") {
								$arg_name = false;
								$start_arg_value = false;
							}
						}
					}
				}
				
				if (!empty($arguments))
					$method["arguments"] = $arguments;
				
				//PREPARING OPEN BRACKETS: {
				++$open_brackets_count;
				
				while (true) {
					$token = $tokens[++$i];
					
					if ($token == "{" || $token[0] == T_CURLY_OPEN || $token[0] == T_DOLLAR_OPEN_CURLY_BRACES) {
						$method["start_brackets_line_index"] = is_numeric($tokens[$i][2]) ? $tokens[$i][2] : $tokens[$i + 1][2];
						$method["start_brackets_token_index"] = $i;
						break;
					}
					else if (!isset($token))
						break;
				}
				
				//ADDING METHOD TO MAIN METHODS:
				if (!empty($class_name))
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
		$end_comments_line_index = is_array($tokens[$idx]) ? $tokens[$idx][2] : null;
		
		while (true) {
			$token = $tokens[--$idx];
			
			if (is_array($token) && ($token[0] == T_COMMENT || $token[0] == T_DOC_COMMENT || $token[0] == T_WHITESPACE)) {
				if ($token[0] == T_COMMENT) {
					if (!isset($start_comments_line_index))
						$end_comments_line_index = $token[2] + substr_count($token[1], "\n");
					
					$comments[] = $token[1];
					$start_comments_line_index = $token[2];
				}
				else if ($token[0] == T_DOC_COMMENT) {
					if (!isset($start_comments_line_index))
						$end_comments_line_index = $token[2] + substr_count($token[1], "\n");
					
					$doc_comments[] = $token[1];
					$start_comments_line_index = $token[2];
				}
				else if (!isset($start_comments_line_index)) 
					$end_comments_line_index = $token[2];
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
			$obj["end_comments_line_index"] = $end_comments_line_index;
		}
	}
	
	public static function getFunctionParamsFromContent($contents, $func_to_search, &$start_pos) {
		$params = array();
		
		$start_pos = strpos($contents, $func_to_search, $start_pos);
		
		if ($start_pos !== false) {
			$start_pos += strlen($func_to_search);
			$contents_length = strlen($contents);
			
			$open_double_quotes = $open_single_quotes = false;
			$open_parentesis = 0;
			$arg = "";
			$j = $start_pos;
			
			for (; $j < $contents_length; $j++) {
				$char = $contents[$j];
				
				if ($char == '"')
					$open_double_quotes = !$open_double_quotes;
				else if ($char == '"')
					$open_single_quotes = !$open_single_quotes;
				else if ($char == '(' && !$open_double_quotes && !$open_single_quotes)
					$open_parentesis++;
				else if ($char == ')' && !$open_double_quotes && !$open_single_quotes)
					$open_parentesis--;
				
				if (!$open_double_quotes && !$open_single_quotes && $open_parentesis <= 0) {
					if ($char == ';' || $open_parentesis < 0) {
						$arg = trim($arg);
						if (strlen($arg)) 
							$params[] = $arg;
						
						break; 
					} 
					else if ($char == ',') {
						$params[] = trim($arg);
						$arg = "";
					}
					else
						$arg .= $char;
				}
				else {
					$arg .= $char;
				}
			}
			
			$start_pos = $j + 1;
		}
		
		return $params;
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
	
	//based in a file, get the correspondent class
	public static function getClassOfFile($file_path) {
		if ($file_path && file_exists($file_path)) {
			$class_name = basename(pathinfo($file_path, PATHINFO_FILENAME));
			
			$c = self::getClassFromFile($file_path, $class_name);
			if ($c)
				return $c;
			
			$arr = self::getPHPClassesFromFile($file_path);
			
			if (is_array($arr)) {
				$class_name = strtolower($class_name);
				
				foreach ($arr as $cn => $c) 
					if (strtolower($c["name"]) == $class_name) 
						return $c;
			}
		}
		
		return null;
	}
	
	public static function getFunctionFromFile($file_path, $func_name, $class_name = 0) {
		$func_name = $func_name ? strtolower(trim($func_name)) : false;
		$class_name = $class_name ? trim($class_name) : $class_name;
		
		if ($file_path && file_exists($file_path) && $func_name) {
			$contents = file_get_contents($file_path);
			$contents = str_replace("\r\n", "\n", $contents);
			$arr = self::getPHPClassesFromString($contents);
			
			if (!$class_name)
				$methods = $arr[0]["methods"];
			else {
				$c = self::getClassFromPHPClasses($arr, $class_name);
				$methods = $c ? $c["methods"] : null;
			}
			
			if ($methods) {
				$t = count($methods);
				for ($i = 0; $i < $t; $i++) 
					if (strtolower($methods[$i]["name"]) == $func_name) 
						return $methods[$i];
			}
		}
		
		return null;
	}
	
	public static function getNamespacesFromFile($file_path) {
		/*$types = array("namespace ");
		$instructions = self::getCodeInstructionFromFile($file_path, $types);
		
		$namespaces = array();
		foreach ($instructions as $instruction)
			$namespaces[] = $instruction[0];
		
		return $namespaces;
		*/
		
		$classes = self::getPHPClassesFromFile($file_path);
		return $classes[0]["namespaces"];
	}
	
	public static function getUsesFromFile($file_path) {
		/*$types = array("use ");
		$instructions = self::getCodeInstructionFromFile($file_path, $types);
		
		$uses = array();
		foreach ($instructions as $instruction)
			$uses[] = $instruction[0];
		
		return $uses;
		*/
		
		$classes = self::getPHPClassesFromFile($file_path);
		return $classes[0]["uses"];
	}
	
	public static function getIncludesFromFile($file_path) {
		$types = array("include ", "include_once ", "require ", "require_once ");
		$instructions = self::getCodeInstructionFromFile($file_path, $types);
		
		//TODO: change this code to work with obfuscated php
		
		$includes = array();
		foreach ($instructions as $instruction)
			$includes[] = array($instruction[0], strpos($instruction[1], "_once") !== false);
		
		return $includes;
	}
	
	private static function getCodeInstructionFromFile($file_path, $types) {
		$instructions = array();
		
		if ($file_path && file_exists($file_path) && $types) {
			$types = is_array($types) ? $types : array($types);
			
			$contents = file_get_contents($file_path);
			$contents = str_replace("\r\n", "\n", $contents);
			$lines = explode("\n", $contents);
			
			//TODO: change this code to work with obfuscated php
			
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
	}
	
	public static function getClassPropertiesFromFile($file_path, $class_name) {
		$properties = array();
		
		if ($file_path && file_exists($file_path) && $class_name) {
			$contents = file_get_contents($file_path);
			
			//TODO: change this code to work with obfuscated php
			
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
						$pos = strpos($new_c, "*/", $i);
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
	}
	
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
	}
	
	public static function getCodeWithoutCommentsFromFile($file_path) {
		if ($file_path && file_exists($file_path)) {
			$code = file_get_contents($file_path);
			return self::getCodeWithoutComments($code);
		}
	}
	
	public static function getCodeWithoutComments($code) {
		if ($code) {
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
			$new_class_settings["name"] = $cs["name"];
			$new_class_settings["namespace"] = $cs["namespace"];
			
			return self::editClassFromFile($file_path, $c, $new_class_settings);
		}
		
		return false;
	}
	
	public static function renameFunctionFromFile($file_path, $old_func_name, $new_func_name, $class_name = 0) {
		if ($file_path && file_exists($file_path) && $old_func_name && $new_func_name && $old_func_name != $new_func_name) {
			$f = self::getFunctionFromFile($file_path, $old_func_name, $class_name);
			
			$new_func_settings = $f;
			$new_func_settings["name"] = $new_func_name;
			
			return self::editFunctionFromFile($file_path, $f, $new_func_settings, $class_name);
		}
		
		return false;
	}
	/* END: RENAMES */
	
	/* START: EDITS */
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
	}
	
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
	}
	
	public static function editClassCommentsFromFile($file_path, $class_name, $comments) {
		if ($class_name) {
			$c = self::getClassFromFile($file_path, $class_name);
			return self::editCommentsFromFile($file_path, $c, $comments);
		}
		return false;
	}
	
	public static function editFunctionCommentsFromFile($file_path, $func_name, $comments, $class_name = 0) {
		if ($func_name) {
			$f = self::getFunctionFromFile($file_path, $func_name, $class_name);
			return self::editCommentsFromFile($file_path, $f, $comments);
		}
		return false;
	}
	
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
					$multiple_comment_pos = strrpos($line, "*/");
					
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
	}
	/* END: EDITS */
	
	/* START: REMOVES */
	
	public static function removeNamespacesFromFile($file_path) {
		return self::removeInstructionsFromFile($file_path, "namespace");
	}
	
	public static function removeUsesFromFile($file_path) {
		return self::removeInstructionsFromFile($file_path, "use");
	}
	
	public static function removeIncludesFromFile($file_path) {
		return self::removeInstructionsFromFile($file_path, array("include", "include_once", "require", "require_once"));
	}
	
	public static function removeInstructionsFromFile($file_path, $types) {
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
	}
	
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
	}
	
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
	}
	
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
	}
	/* END: REMOVES */
	
	/* START: REPLACES */
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
	}
	
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
				return self::removeInstructionsFromFile($file_path, "namespace $old_namespace"); //do not add ; bc removeInstructionsFromFile will check if the next char is ;
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
		$name = trim($class_settings["name"]);
		
		if ($file_path && $name) {
			//PREPARING CLASS STRING
			$str = self::getClassString($class_settings);
			
			//PREPARING CLASS CODE
			$code = $class_settings["code"];
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
	}
	
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
	}
	/* END: ADDS */
	
	/* START: UTILS */
	private static function getClassString($class_settings) {
		$name = trim($class_settings["name"]);
		$is_abstract = $class_settings["abstract"];
		$extends = $class_settings["extends"];
		$implements = $class_settings["implements"];
		$namespace = $class_settings["namespace"];
		$uses = $class_settings["uses"];
		$includes = $class_settings["includes"];
		$comments = trim($class_settings["comments"]);
		
		$extends = !$extends || is_array($extends) ? $extends : array($extends);
		$implements = !$implements || is_array($implements) ? $implements : array($implements);
		$includes = !$includes || is_array($includes) ? $includes : array($includes);
		
		$str = "";
		
		if ($namespace)
			$str .= "namespace $namespace;\n\n";
		
		if ($includes) {
			foreach ($includes as $include)
				if (is_array($include) && trim($include[0]))
					$str .= "include" . ($include[1] ? "_once" : "") . " " . $include[0] . ";\n";
				else if (!is_array($include) && trim($include))
					$str .= "include_once $include;\n";
			$str .= "\n";
		}
		
		if ($uses)  {
			foreach ($uses as $use => $alias)
				if (trim($use))
					$str .= "use $use" . (trim($alias) ? " as $alias" : "") . ";\n";
			$str .= "\n";
		}
		
		if ($comments) 
			$str .= (substr($comments, 0, 2) != "/*" ? "/**\n * " . str_replace("\n", "\n * ", $comments) . "\n */" : $comments) . "\n";
		
		$str .= ($is_abstract ? "abstract " : "") . "class $name";
	
		if ($extends) 
			$str .= " extends " . implode(", ", $extends);
		
		if ($implements) 
			$str .= " implements " . implode(", ", $implements);
		
		return $str;
	}
	
	private static function getFunctionString($function_settings, $class_name = false) {
		$name = trim($function_settings["name"]);
		$type = $function_settings["type"];
		$is_abstract = $function_settings["abstract"];
		$is_static = $function_settings["static"];
		$args = $function_settings["arguments"];
		$comments = trim($function_settings["comments"]);
		
		if (is_array($args)) {
			$args_str = ""; 
			foreach ($args as $arg_name => $arg_value) {
				if ($arg_name) {
					$arg_name = trim($arg_name);
					$arg_name = substr($arg_name, 0, 1) == '$' ? $arg_name : "\$$arg_name";
					
					$args_str .= ($args_str ? ", " : "") . "$arg_name";
				
					if (isset($arg_value)) {
						$args_str .= " = $arg_value";
					}
				}
			}
		}
		
		$prefix = $class_name ? "\t" : "";
		
		$str = "";
		
		if ($comments) {
			if (substr($comments, 0, 2) != "/*")
				$str .= "$prefix/**\n$prefix * " . str_replace("\n", "\n$prefix * ", $comments) . "\n$prefix */\n";
			else 
				$str .= $prefix . str_replace("\n", "\n$prefix", $comments) . "\n";
		}
		
		if ($class_name) {
			$type = !$type && $class_name ? "public" : strtolower($type);
		
			$str .= $prefix . trim(strtolower($type) . ($is_abstract ? " abstract" : "") . ($is_static ? " static" : "") . " function $name ($args_str)");
		}
		else 
			$str .= $prefix . trim("function $name ($args_str)");
		
		return $str;
	}
	
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
	}
	
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
