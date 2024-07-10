<?php
if (!defined('T_ML_COMMENT'))
	define('T_ML_COMMENT',T_COMMENT);  // if not PHP 4
		
class PHPCodeObfuscator {
	private $delimiters = array(
		T_WHITESPACE,
		T_COMMENT,
		T_ML_COMMENT
	);
	
	private $reserved = array(
		"_SERVER",
		"_ENV",
		"_COOKIE",
		"_GET",
		"_POST",
		"_FILES",
		"_SESSION",
		"_REQUEST",
		"GLOBALS",
		"php_errormsg",
		"this",
		"self",
		"static",
		"parent",
		"HTTP_SERVER_VARS",
		"HTTP_ENV_VARS",
		"HTTP_COOKIE_VARS",
		"HTTP_GET_VARS",
		"HTTP_POST_VARS",
		"HTTP_FILES_VARS",
		"HTTP_SESSION_VARS"
	);
	
	private $files_settings;
	private $serialized_files;
	private $current_options;
	private $errors;
	private $reserved_functions;
	
	public function __construct($files_settings, $serialized_files = null) {
		$this->serialized_files = $serialized_files;
		
		//prepare files_settings with standardized paths
		$this->files_settings = $this->getConfiguredFilesSettings($files_settings);
		
		//merge all files default settings with main default settings
		if ($this->files_settings) 
			foreach ($this->files_settings as $settings)
				if (!empty($settings[0]))
					$this->files_settings[0] = is_array($this->files_settings[0]) ? array_merge($this->files_settings[0], $settings[0]) : $settings[0];
		
		//set all native functions as reserved functions that cannot be obfuscated
		$defined_functions = get_defined_functions();
		$this->reserved_functions = isset($defined_functions["internal"]) ? $defined_functions["internal"] : null;
	}
	
	public function addReservedFunctions($reserved_functions) {
		if ($reserved_functions) {
			if (is_array($reserved_functions))
				$this->reserved_functions = array_merge($this->reserved_functions, $reserved_functions);
			else
				$this->reserved_functions[] = $reserved_functions;
		}
	}
	
	public function isReservedFunction($func_name) {
		return in_array($func_name, $this->reserved_functions);
	}
	
	public function getErrors() {
		return $this->errors;
	}
	
	public function getIncludesWarningMessage($avoid_warnings_for_files = array()) {
		$msg = "";
		
		//prepare avoid_warnings_for_files with standardized paths
		if ($avoid_warnings_for_files)
			foreach ($avoid_warnings_for_files as $idx => $file_path)
				$avoid_warnings_for_files[$idx] = $this->getConfiguredPath($file_path);
		
		//checks if there are any path with * in the $avoid_warnings_for_files
		$regexes = array();
		if ($avoid_warnings_for_files)
			foreach ($avoid_warnings_for_files as $file_path)
				if (strpos($file_path, "*") !== false)
					$regexes[] = "|" . str_replace('*', '.+', addcslashes($file_path, '.')) . "|iu"; //'/u' means with accents and ç too.
		
		if ($this->files_settings)
			foreach ($this->files_settings as $file_path => $settings) {
				$continue = !$avoid_warnings_for_files || !in_array($file_path, $avoid_warnings_for_files);
				
				if ($continue && $regexes) //check if file_path matches the regexes
					foreach ($regexes as $regex)
						if (preg_match($regex, $file_path)) {
							$continue = false;
							break;
						}
				
				if ($continue)
					foreach ($settings as $class_name => $class_settings) {
						$has_includes = !empty($settings[$class_name]["__includes"]);
						$has_evals = !empty($settings[$class_name]["__evals"]);
					
						if ($has_includes || $has_evals) {
							$names = array();
							$ok = true;
						
							if ($has_includes)
								foreach ($settings[$class_name]["__includes"] as $func_name => $aux)
									if ($class_name === 0 && $this->obfuscateFunctionCode($file_path, $func_name)) {
										$names[] = $func_name;
									
										if (!$this->obfuscateFunctionEncapsedString($file_path, $func_name))
											$ok = false;
									}
									else if ($class_name !== 0 && $this->obfuscateClassMethodCode($file_path, $class_name, $func_name)) {
										$names[] = "$class_name->$func_name";
									
										if (!$this->obfuscateClassMethodEncapsedString($file_path, $class_name, $func_name))
											$ok = false;
									}
								
							if ($has_evals)
								foreach ($settings[$class_name]["__evals"] as $func_name => $aux)
									if ($class_name === 0 && $this->obfuscateFunctionCode($file_path, $func_name)) {
										$names[] = $func_name;
									
										if (!$this->obfuscateFunctionEncapsedString($file_path, $func_name))
											$ok = false;
									}
									else if ($class_name !== 0 && $this->obfuscateClassMethodCode($file_path, $class_name, $func_name)) {
										$names[] = "$class_name->$func_name";
									
										if (!$this->obfuscateClassMethodEncapsedString($file_path, $class_name, $func_name))
											$ok = false;
									}
							$names = array_unique($names);
							$msg .= "\n- [" . ($ok ? "OK" : "TO_CHECK") . "] $file_path (" . ($has_includes ? "INCLUDES" . ($has_evals ? " & " : "") : "") . ($has_evals ? "EVALS" : "") . "): " . implode(" & ", $names);
						}
					}
			}
		
		return $msg ? "\nWARNING: OBFUSCATION WITH INCLUDES OR EVALS CALL inside of:$msg\n\n\n" : false;
	}
	
	public function obfuscateFiles($options) {
		$status = true;
		
		$this->current_options = $options;
		$this->errors = array();
		
		if ($this->files_settings) 
			foreach ($this->files_settings as $file_path => $aux)
				if ($file_path) {
					if (is_dir($file_path)) {
						if (!$this->obfuscateFolder($file_path))
							$status = false;
					}
					else if (empty($this->files_settings[$file_path][1]["file_already_parsed"]) && !$this->obfuscateFile($file_path)) {
						$status = false;
						$this->errors[] = $file_path;
					}
				}
				else if (!is_numeric($file_path)) { //$file_path could be [0], bc it's where we save all the global functions
					$status = false;
					$this->errors[] = "There is an empty file path in the file_settings.";
				}
		
		return $status;
	}
	
	private function obfuscateFolder($folder_path) {
		if ($folder_path && file_exists($folder_path) && is_dir($folder_path) && $this->files_settings[$folder_path]) {
			if (!empty($this->files_settings[$folder_path][1]["skip"]))
				return true;
			
			$status = true;
			$folder_save_path = isset($this->files_settings[$folder_path][1]["save_path"]) ? $this->files_settings[$folder_path][1]["save_path"] : null;
			
			$files = scandir($folder_path);
			if ($files)
				foreach ($files as $file_name) 
					if ($file_name != "." && $file_name != "..") {
						$file_path = $this->getConfiguredPath("$folder_path/$file_name"); //standardized path
						
						if (empty($this->files_settings[$file_path][1]["save_path"]))
							$this->files_settings[$file_path][1]["save_path"] = $folder_save_path . "/$file_name"; 
						
						$this->files_settings[$file_path] = $this->mergeFileSettings($this->files_settings[$folder_path], $this->files_settings[$file_path]);
						
						if (is_dir($file_path)) {
							if (!$this->obfuscateFolder($file_path))
								$status = false;
						}
						else if (strtolower(substr($file_name, -4)) == ".php" && !$this->obfuscateFile($file_path)) {
							$status = false;
							$this->errors[] = $file_path;
						}
					}
			
			return $status;
		}
		else
			$this->errors[] = $folder_path;
	}
	
	//This function does NOT obfuscate the methods from variable objects, like: "$foo->bar();" or "$foo->var", because we cannot know the type of the variable $foo. Even if we read the code, there will be always cases that we cannot know, bc $foo can be passed as a function's argument.
	private function obfuscateFile($file_path) {
		if ($file_path && file_exists($file_path) && is_file($file_path)) {
			$this->files_settings[$file_path][1]["file_already_parsed"] = true;
			
			if (!empty($this->files_settings[$file_path][1]["skip"]))
				return true;
			
			//if serialized file, doesn't do anything
			if ($this->isSerializedFile($file_path))
				return true;
			
			$obfuscated_content = "";
			
			$content = file_get_contents($file_path);
			$tokens = token_get_all($content);
			//print_r($tokens);die();
			$tokens_count = count($tokens);
			
			$func_found = $class_found = $new_found = $const_found = $obj_found = $implements_extends_found = $global_fnd = false; 
			$current_class = $current_func = $object_found_class = false;
			$brackets = $class_brackets = $func_brackets = 0;
			$current_funcs_classes = $current_variables = array();
			
			$this->initClassesMethodsAndVariables($file_path, $tokens);
			
			$i = 0;
			while ($i < $tokens_count) {
				$token = $tokens[$i++];
				$is_open_brackets = $is_closed_brackets = $clear_current_func = false;
				
				if (is_string($token)) {
					$func_found = $class_found = $new_found = $const_found = $obj_found = false;
					$obfuscated_content .= $token;
					
					if ($token == "{") {
						$brackets++;
						$is_open_brackets = true;
					}
					else if ($token == "}") {
						$brackets -= $brackets > 0 ? 1 : 0;
						$is_closed_brackets = true;
					}
					else if ($token == ";") {
						$global_fnd = false;
						
						if (isset($current_variables[0][1]) && $current_variables[0][1] == $brackets) //we must do this check, bc the variable can be a function like: $v = function(...) {...}
							$current_variables = array(); //this is only used for the classes' properties/vars and for the global variables
						
						$cfc = count($current_funcs_classes) ? $current_funcs_classes[ count($current_funcs_classes) - 1 ] : null;
						if ($current_class && $cfc && $cfc[0] == T_FUNCTION && $cfc[2] > $brackets) //in case of being an Interface or abstract class where the functions are not set, something like: "public function getName();"
							$clear_current_func = true;
					}
				} 
				else {
					list($tok, $txt) = $token;
				
					switch ($tok) {
						case T_DOLLAR_OPEN_CURLY_BRACES: //${
						case T_CURLY_OPEN: //{$
							$brackets++;
							$is_open_brackets = true;
							break;
						//case T_CURLY_CLOSE: //}
						case "}": //}
							$brackets -= $brackets > 0 ? 1 : 0;
							$is_closed_brackets = true;
							break;
						case T_FUNCTION:
							$func_found = true;
							//Note: DO NOT add the $func_brackets here, because the if could be something like: $x = function() {}. For this case, we simply ignore it.
							break;
						case T_NEW:
							$new_found = true;
							break;
						case T_CONST:
							$const_found = true;
							break;
						case T_IMPLEMENTS:
						case T_EXTENDS:
							$implements_extends_found = true;
							break;
						case T_CLASS:
						case T_INTERFACE:
						case T_TRAIT:
							$class_found = true;
							break;
						case T_OBJECT_OPERATOR:
						case T_DOUBLE_COLON:
							$obj_found = T_DOUBLE_COLON ? 2 : 1;
							break;
						case T_GLOBAL:
							$global_fnd = true;
							break;
						
						case T_STRING:
							if ($func_found) { //prepare functions or methods
								$cfc = count($current_funcs_classes) ? $current_funcs_classes[ count($current_funcs_classes) - 1 ] : null;
								$is_inner_function = $current_func && $cfc && $cfc[0] == T_FUNCTION; 
								
								$func_found = false;
								$current_func = $txt;
								$func_brackets = $brackets + 1;
								
								$parent_func_global_vars = isset($cfc[3]) ? $cfc[3] : null; //$cfc[3] are the global_variables from the parent function
								$current_funcs_classes[] = array(T_FUNCTION, $current_func, $func_brackets, $parent_func_global_vars);
								
								if ($is_inner_function) {
									$cf = $this->getParentFunction($current_func, $current_funcs_classes);
									$obfuscate = $this->isObfuscateInnerFunction($file_path, $current_class, $cf, $current_func);
								}
								else 
									$obfuscate = ($current_class && $this->obfuscateClassMethodName($file_path, $current_class, $current_func)) || (!$current_class && $this->obfuscateFunctionName($file_path, $current_func));
								
								$txt = $obfuscate ? $this->encode('F', $txt) : $txt;
							}
							else if ($class_found) { //prepare class creation: class XXX
								$class_found = false;
								$current_class = $txt;
								$class_brackets = $brackets + 1;
								
								$cfc = count($current_funcs_classes) ? $current_funcs_classes[ count($current_funcs_classes) - 1 ] : null;
								$parent_func_global_vars = isset($cfc[3]) ? $cfc[3] : null; //$cfc[3] are the global_variables from the parent function
								$current_funcs_classes[] = array(T_CLASS, $current_class, $class_brackets, $parent_func_global_vars);
								
								$txt = $this->obfuscateClassName($file_path, $current_class) ? $this->encode('F', $txt) : $txt;
							} 
							else if ($new_found) { //prepare class object creation, thi sis, new XXX(..
								$new_found = false;
								$txt = $this->obfuscateClassName($file_path, $txt) ? $this->encode('F', $txt) : $txt;
							} 
							else if ($const_found) { //prepare const variable inside of a class
								$const_found = false;
								$txt = $current_class && $this->obfuscateClassVariableName($file_path, $current_class, $txt) ? $this->encode('F', $txt) : $txt;
							}
							else if ($implements_extends_found) { //prepare const variable inside of a class
								$txt = $this->obfuscateClassName($file_path, $txt) ? $this->encode('F', $txt) : $txt;
							}
							else if ($obj_found) { //preparing ->xxx or ::xxx. We can have this too: $foo->xxx, where we dont know the type for $foo, so the $object_found_class will be empty. The isObfuscateObjectMethod will take care of all these cases.
								//echo "---$object_found_class:$txt\n";
								$txt_var = (strtolower($object_found_class) == '$this' ? '$' : '') . $txt;
								$object_found_class = $current_class && (strtolower($object_found_class) == '$this' || strtolower($object_found_class) == 'self' || strtolower($object_found_class) == 'static') ? $current_class : $object_found_class;
								//echo "---$object_found_class:$txt\n";
								
								for ($j = $i; $j < $tokens_count; $j++) {
									$next_token = $tokens[$j];
									
									if (is_string($next_token)) {
										if ($next_token == '(') {
											if ($this->isObfuscateObjectMethod($file_path, $current_class, $current_func, $object_found_class, $txt))
												$txt = $this->encode('F', $txt);
										} 
										else if ($this->isObfuscateObjectVariable($file_path, $current_class, $current_func, $object_found_class, $txt_var))
											$txt = $this->encode('V', $txt);
										
										break;
									} 
									else {
										list($next_tok, $next_txt) = $next_token;
									
										if (!in_array($next_tok, $this->delimiters)) {
											if ($this->isObfuscateObjectVariable($file_path, $current_class, $current_func, $object_found_class, $txt_var))
												$txt = $this->encode('V', $txt);
											
											break;
										}
									}
								}
								
								$obj_found = $object_found_class = false;
							}
							else {
								//Prepareing $object_found_class: get class for the :: if exist, like: X::getName() => get class name: X
								$object_found_class = false;
								
								for ($j = $i; $j < $tokens_count; $j++) {
									$next_token = $tokens[$j];
									
									if (is_string($next_token))
										break;
									else if ($next_token[0] == T_DOUBLE_COLON) {
										$object_found_class = $txt;
										//echo "---object_found_class:$object_found_class\n";
										
										if (!in_array($txt, $this->reserved) && $this->obfuscateClassName($file_path, $txt))
											$txt = $this->encode('F', $txt);
										
										break;
									}
									else if ($next_token[0] != T_WHITESPACE)
										break;
								}
								
								if (!$object_found_class)
									//check for functions calls: native and user functions... like str_replace(...), foo(...), etc...
									for ($j = $i; $j < $tokens_count; $j++) {
										$next_token = $tokens[$j];
										
										if (is_string($next_token)) {
											if ($next_token == '(') {
												$cf = $this->getParentFunction($current_func, $current_funcs_classes);
												
												if ($this->obfuscateFunctionName($file_path, $txt) || $this->isObfuscateInnerFunction($file_path, $current_class, $cf, $txt))
													$txt = $this->encode('F', $txt);
											}
											break;
										}
										else if (!in_array($next_token[0], $this->delimiters))
											break;
									}
							}
							
							break;
							
						case T_VARIABLE: 
							if (!in_array(substr($txt, 1), $this->reserved)) {
								$obfuscate = false;
								
								if ($global_fnd) { //add global variable. This is for the code: global $a, $b;
									$cfc = count($current_funcs_classes) ? $current_funcs_classes[ count($current_funcs_classes) - 1 ] : null;
									
									if ($cfc && $cfc[0] == T_FUNCTION)
										$current_funcs_classes[ count($current_funcs_classes) - 1 ][3][$txt] = true; // adding local global variable to the last func
									
									$obfuscate = $this->obfuscateGlobalVariableName($file_path, $txt);
								}
								else if ($object_found_class && $object_found_class != '$this') { //in case exists a object_found_class like: self::$foo or static::$foo or BAR::$foo 0> constants. Avoid this case $this->$xxx, bc $xxx is a local variable
									$object_found_class = $current_class && (strtolower($object_found_class) == 'self' || strtolower($object_found_class) == 'static') ? $current_class : $object_found_class;
									$obfuscate = $this->obfuscateClassVariableName($file_path, $object_found_class, $txt);
									$object_found_class = false;
								}
								else if ($current_func) { //prepare func/method local/global variables
									if (count($current_funcs_classes) && !empty($current_funcs_classes[ count($current_funcs_classes) - 1 ][3][$txt])) //prepare func global variables
										$obfuscate = $this->obfuscateGlobalVariableName($file_path, $txt);
									else { //prepare func/method variables
										$cf = $this->getParentFunction($current_func, $current_funcs_classes); //get parent function in case of exists a inner function
										
										if ($current_class) //prepare method local variables
											$obfuscate = $this->obfuscateClassMethodCode($file_path, $current_class, $cf) && !$this->ignoreClassMethodVariable($file_path, $current_class, $cf, $txt);
										else //prepare func local variables
											$obfuscate = $this->obfuscateFunctionCode($file_path, $cf) && !$this->ignoreFunctionVariable($file_path, $cf, $txt);
									}
								}
								else if ($current_class) { //prepare class properties
									$obfuscate = $this->obfuscateClassVariableName($file_path, $current_class, $txt);
									$current_variables[] = array($txt, $class_brackets);
								}
								else { //prepare file global vars
									$obfuscate = $this->obfuscateGlobalVariableName($file_path, $txt);
									$current_variables[] = array($txt, 0); //$brackets here are always 0, bc is a global variable
								}
								
								//echo "$txt:$current_class:$current_func\n";
								if ($obfuscate)
									$txt = $this->encode('V', $txt);
							}
							else if (strtolower($txt) == '$this') { //check if next nodes is ->, bc it could be something like: func($this)
								for ($j = $i; $j < $tokens_count; $j++) {
									$next_token = $tokens[$j];
									
									if (is_string($next_token))
										break;
									else if ($next_token[0] == T_OBJECT_OPERATOR) {
										$object_found_class = $txt;
										break;
									}
									else if ($next_token[0] != T_WHITESPACE)
										break;
								}
							}
							
							break;
							
						case T_CONSTANT_ENCAPSED_STRING:
						case T_ENCAPSED_AND_WHITESPACE:	
							$main_current_variable = isset($current_variables[0][0]) ? $current_variables[0][0] : null;
							$cf = $this->getParentFunction($current_func, $current_funcs_classes);
							$obfuscate = $this->isObfuscateEncapsedString($file_path, $current_class, $cf, $main_current_variable);
							
							if ($obfuscate) {
								$local_global_vars = isset($current_funcs_classes[ count($current_funcs_classes) - 1 ][3]) ? $current_funcs_classes[ count($current_funcs_classes) - 1 ][3] : null; //$current_funcs_classes[ count($current_funcs_classes) - 1 ][3] are the local global variables
								$txt = $this->prepareEncapsedVariables($txt, $file_path, $current_class, $cf, $local_global_vars);
							}
							
							$strip_encapsed_string_eol = $this->isStripEncapsedStringEOL($file_path, $current_class, $cf, $main_current_variable);
							if ($strip_encapsed_string_eol)
								$txt = str_replace(array("\r", "\n", "\t"), " ", $txt);
							
							break;
						
						case T_INLINE_HTML:
							$main_current_variable = isset($current_variables[0][0]) ? $current_variables[0][0] : null;
							$cf = $this->getParentFunction($current_func, $current_funcs_classes);
							$strip_encapsed_string_eol = $this->isStripEncapsedStringEOL($file_path, $current_class, $cf, $main_current_variable);
							if ($strip_encapsed_string_eol)
								$txt = str_replace(array("\r", "\n", "\t"), " ", $txt);
							
							break;
							
						case T_COMMENT:
							$strip_eol = $this->stripEOLFromFile($file_path);
							
							if ($this->stripCommentsFromFile($file_path)) {
								$txt = '';
								
								if (!$strip_eol) //in case the comments be indent with some tabs or spaces, we must add an end line, otherwise the next code will be indented twice.
									$txt .= "\n";
							}
							else if ($strip_eol) {
								if (substr(trim($txt), 0, 2) == "//") 
									$txt = "/* " . substr(trim($txt), 2) . " */";
							
								$txt = str_replace("\r", "", str_replace(array("\n", "\t"), " ", $txt));
							}
							
							break;
						
						case T_DOC_COMMENT: //Doc comment can be used for @param or @result or to execute any other code. So we cannot remove it from some files... DOC_COMMENT are not regular comments!
							if ($this->stripDocCommentsFromFile($file_path)) {
								$txt = '';
								
								if (!$this->stripEOLFromFile($file_path)) //in case the comments be indent with some tabs or spaces, we must add an end line, otherwise the next code will be indented twice.
									$txt .= "\n";
							}
							
							break;
						
						case T_WHITESPACE:
							if ($this->stripEOLFromFile($file_path))
								$txt = substr($obfuscated_content, -1) == ' ' ? '' : ' ';
							
							break;
					}
					
					$obfuscated_content .= $txt;
				}
				
				if ($is_open_brackets) {
					if ($class_brackets && $class_brackets == $brackets)
						$implements_extends_found = false;
				}
				else if ($is_closed_brackets) {
					if ($class_brackets && $class_brackets == $brackets + 1) {
						$class_brackets = 0;
						$current_class = false;
						array_pop($current_funcs_classes);
					}
					
					if ($func_brackets && $func_brackets == $brackets + 1)
						$clear_current_func = true;
				}
				
				if ($clear_current_func) {
					$func_brackets = 0;
					$current_func = false;
					array_pop($current_funcs_classes);
				
					for ($j = count($current_funcs_classes) - 1; $j >= 0; $j--) { //in case be a function inside of function
						$fc = $current_funcs_classes[$j];
						
						if ($fc && $fc[0] == T_FUNCTION) {
							$current_func = $fc[1];
							$func_brackets = $fc[2];
							break;
						}
					}
				}
			}
			
			$copyright = $this->addCopyrightToFile($file_path);
			if ($copyright) {
				if (preg_match("/^\s*<\?/", $obfuscated_content))
					$obfuscated_content = "<?php\n" . trim($copyright) . preg_replace("/^\s*<\?(php)?/", "", $obfuscated_content);
				else
					$obfuscated_content = "<?php\n" . trim($copyright) . "\n?>" . $obfuscated_content;
			}
			
			//Saving new code to file
			return $this->saveObfuscatedFile($file_path, $obfuscated_content);
		}
	}
	
	private function isSerializedFile($file_path) {
		if (is_array($this->serialized_files))
			foreach ($this->serialized_files as $serialized_file) {
				if ($serialized_file && substr($serialized_file, -1) != "/" && is_dir($serialized_file))
					$serialized_file .= "/";
				
				if (substr($file_path, 0, strlen($serialized_file)) == $serialized_file)
					return true;
			}
		
		return false;
	}
	
	//Note that it is possible to have functions inside of functions and classes inside of functions. However PHP does not allow classes inside of classes.
	private function initClassesMethodsAndVariables($file_path, $tokens) {
		$tokens_count = count($tokens);
		
		$func_found = $class_found = $priv_found = $pub_found = false; 
		$current_class = $current_func = false;
		$brackets = $class_brackets = $func_brackets = 0;
		$current_funcs_classes = array();
		
		$i = 0;
		while ($i < $tokens_count) {
			$token = $tokens[$i++];
			$is_closed_brackets = $clear_current_func = false;
			
			if (is_string($token)) {
				$func_found = $class_found = $priv_found = $pub_found = false;
				
				if ($token == "{")
					$brackets++;
				else if ($token == "}") {
					$brackets -= $brackets > 0 ? 1 : 0;
					$is_closed_brackets = true;
				}
				else if ($token == ";") {
					$cfc = count($current_funcs_classes) ? $current_funcs_classes[ count($current_funcs_classes) - 1 ] : null;
					
					if ($current_class && $cfc && $cfc[0] == T_FUNCTION && $cfc[2] > $brackets) //in case of being an Interface or abstract class where the functions are not set, something like: "public function getName();"
						$clear_current_func = true;
				}
			} 
			else {
				list($tok, $txt) = $token;
				
				switch ($tok) {
					case T_DOLLAR_OPEN_CURLY_BRACES: //${
					case T_CURLY_OPEN: //{$
						$brackets++;
						break;
					//case T_CURLY_CLOSE: //}
					case "}": //}
						$brackets -= $brackets > 0 ? 1 : 0;
						$is_closed_brackets = true;
						break;
					case T_PRIVATE:
						$priv_found = true;
						break;
					case T_PUBLIC:
					case T_VAR:
					case T_CONST:
						$pub_found = true;
						break;
					case T_FUNCTION:
						$func_found = true;
						break;
					case T_CLASS:
					case T_INTERFACE:
					case T_TRAIT:
						$class_found = true;
						break;
					case T_INCLUDE:
					case T_INCLUDE_ONCE:
					case T_REQUIRE:
					case T_REQUIRE_ONCE:
						if ($current_class && $current_func)
							$this->activateClassMethodInclude($file_path, $current_class, $current_func);
						else if ($current_func)
							$this->activateFunctionInclude($file_path, $current_func);
						
						break;
					case T_EVAL:
						if ($current_class && $current_func)
							$this->activateClassMethodEval($file_path, $current_class, $current_func);
						else if ($current_func)
							$this->activateFunctionEval($file_path, $current_func);
						
						break;
					case T_STRING:
						if ($func_found) {
							$is_class_method = $current_class && isset($current_funcs_classes[ count($current_funcs_classes) - 1 ][0]) && $current_funcs_classes[ count($current_funcs_classes) - 1 ][0] == T_CLASS; //avoids inner functions inside of main functions
							
							if ($is_class_method && ($priv_found || $pub_found)) 
								$this->addClassMethod($priv_found ? T_PRIVATE : T_PUBLIC, $file_path, $current_class, $txt);
							else if (!$is_class_method && $current_func) {
								$cf = $this->getParentFunction($current_func, $current_funcs_classes); //get parent function in case of exists a inner function
								if ($current_class) 
									$this->addClassMethodInnerFunction($file_path, $current_class, $cf, $txt);	
								else
									$this->addFunctionInnerFunction($file_path, $cf, $txt);	
							}
							
							$func_found = false;
							$current_func = $txt;
							$func_brackets = $brackets + 1;
							$current_funcs_classes[] = array(T_FUNCTION, $current_func, $func_brackets);
						}
						else if ($class_found) {
							$class_found = false;
							$current_class = $txt;
							$class_brackets = $brackets + 1;
							$current_funcs_classes[] = array(T_CLASS, $current_class, $class_brackets);
						} 
						break;
						
					case T_VARIABLE: 
						$is_class_variable = $current_class && isset($current_funcs_classes[ count($current_funcs_classes) - 1 ][0]) && $current_funcs_classes[ count($current_funcs_classes) - 1 ][0] == T_CLASS; //avoids local variables inside of functions
						
						if ($is_class_variable && !in_array(substr($txt, 1), $this->reserved)) {
							if ($priv_found || $pub_found) 
								$this->addClassVariable($priv_found ? T_PRIVATE : T_PUBLIC, $file_path, $current_class, $txt);
						}
						break;
				}
			}
			
			if ($is_closed_brackets) {
				if ($class_brackets && $class_brackets == $brackets + 1) {
					$class_brackets = 0;
					$current_class = false;
					array_pop($current_funcs_classes);
				}
				
				if ($func_brackets && $func_brackets == $brackets + 1)
					$clear_current_func = true;
			}
			
			if ($clear_current_func) {
				$func_brackets = 0;
				$current_func = false;
				array_pop($current_funcs_classes);
				
				for ($j = count($current_funcs_classes) - 1; $j >= 0; $j--) { //in case be a function inside of function
					$fc = $current_funcs_classes[$j];
					
					if ($fc && $fc[0] == T_FUNCTION) {
						$current_func = $fc[1];
						$func_brackets = $fc[2];
						break;
					}
				}
			}
		}
		
		//print_r($this->files_settings);
	}
	
	//Obfuscate the variables and methods inside of the ENCAPSED STRINGs
	private function prepareEncapsedVariables($txt, $file_path, $current_class, $current_func, $local_global_vars) {
		//DO NOT TOUCH in this regex: \\\\*\$[a-z]. It's weird but I tested in multiple cases and must be like this.
		//Here are some test strings that you can use to test again:
		//$txt = "bla \\\$this->x self::\$bar2 self::bar4 \$this->a \$d xxxx self::\$bar3 123213 \$this->bar1 ble self :: bar1() \$this -> getClass(\$c) \$GLOBALS[test] X::getName() \$as->getName()";
		//$txt = 'bla $this->x self::$bar2 self::bar4 $this->a $d xxxx self::$bar3 123213 $this->bar1 ble self :: bar1() $this -> getClass($c) $GLOBALS[test] X::getName() $as->getName()';
		//$txt = 'bla $this->x static::$bar2 static::bar4 $this->a $d xxxx static::$bar3 123213 $this->bar1 ble static :: bar1() $this -> getClass($c) $GLOBALS[test] X::getName() $as->getName()';
		$regex = '/(\$this\s*->\s*\w\w*\s*\(?|\w+\s*::\s*\\\\*\$\w\w*|\$\w\w*|\$\{\w\w*\}|\w+\s*::\s*\w\w*\s*\(?|->\s*\w\w*\s*\(?)/iu'; //'\w' means all words with '_' and '/u' means with accents and ç too.
		//$regex = '/(\$this[ ]*->[ ]*[a-z][a-z_0-9]*[ ]*\(?|[a-z_0-9]+[ ]*::[ ]*\\\\*\$[a-z][a-z_0-9]*|\$[a-z][a-z_0-9]*|[a-z_0-9]+[ ]*::[ ]*[a-z][a-z_0-9]*[ ]*\(?|->[ ]*[a-z][a-z_0-9]*[ ]*\(?)/i';
		//$regex = '/(\$this[ ]*->[ ]*[a-z][a-z_0-9]*[ ]*\(?|self[ ]*::[ ]*\\\\*\$[a-z][a-z_0-9]*|\$[a-z][a-z_0-9]*|self[ ]*::[ ]*[a-z][a-z_0-9]*[ ]*\(?|->[ ]*[a-z][a-z_0-9]*[ ]*\(?)/i'; //DEPRECATED bc we only care with this and self and inner object methods like ->xxx.
		preg_match_all($regex, $txt, $matches, PREG_OFFSET_CAPTURE);
		$matches = $matches[1];
		//var_dump($txt);print_r($matches);//if ($matches)die();
		
		if ($matches)
			foreach ($matches as $match) {
				$m = str_replace(" ", "", $match[0]); //replaces spaces for: $this  -> xxx
				$replacement = $parse_var = false;
				//echo "match: $m\n";
				$is_static = strpos($m, '::') !== false;
				$is_obj_mv = strpos($m, '->') !== false;
				$is_encapsulate_var = false;
				
				//Preparing $this->variable or $this->method or $xx->method or $xx->variable
				if ($is_obj_mv) {
					$is_this = strpos($m, '$this->') === 0;
					$object_found_class = $is_this ? $current_class : false;
					
					$aux = substr($m, strpos($m, '->') + 2);
					$var = $is_this ? '$' . $aux : $aux;
					
					if (!$is_this) //it means is some variable with an object and it should be parsed as a variable later on.
						$parse_var = substr($m, 0, strpos($m, '->'));
					
					if (substr($aux, -1) == "(") { //checks if class method
						if ($this->isObfuscateObjectMethod($file_path, $current_class, $current_func, $object_found_class, substr($aux, 0, -1)))
							$replacement = $this->encode('F', substr($aux, 0, -1)) . "(";
					}
					else if ($this->isObfuscateObjectVariable($file_path, $current_class, $current_func, $object_found_class, $var))  //checks if class variable
					
						$replacement = $this->encode('V', $aux);
					else if ($this->isObfuscateObjectMethod($file_path, $current_class, $current_func, $object_found_class, $aux))  //checks again if method bc it could be an eval with "(" later on.
						$replacement = $this->encode('F', $aux);
				}
				//Preparing self::$variable or self::method or self::const_var or static::$variable
				else if ($is_static) {
					$is_self = strpos($m, 'self::') === 0 || strpos($m, 'static::') === 0;
					$object_found_class = $is_self ? $current_class : substr($m, 0, strpos($m, '::'));
					
					$aux = substr($m, strpos($m, '::') + 2);
					
					if (substr($aux, 0, 2) == '\\$') { //checks if class const variables escaped: self::\\$xxx or static::\\$xxx
						if ($this->isObfuscateObjectVariable($file_path, $current_class, $current_func, $object_found_class, substr($aux, 1)))
							$replacement = '\\$' . $this->encode('V', substr($aux, 2));
					}
					else if (substr($aux, 0, 1) == '$') { //checks if class const variables NOT escaped: self::$xxx or static::$xxx
						if ($this->isObfuscateObjectVariable($file_path, $current_class, $current_func, $object_found_class, $aux))
							$replacement = '$' . $this->encode('V', substr($aux, 1));
					}
					else if (substr($aux, -1) == "(") { //checks if class methods
						if ($this->isObfuscateObjectMethod($file_path, $current_class, $current_func, $object_found_class, substr($aux, 0, -1)))
							$replacement = $this->encode('F', substr($aux, 0, -1)) . "(";
					}
					else if ($this->isObfuscateObjectMethod($file_path, $current_class, $current_func, $object_found_class, $aux)) //checks again if method bc it could be an eval with "(" later on.
						$replacement = $this->encode('F', $aux);
					else if ($this->isObfuscateObjectVariable($file_path, $current_class, $current_func, $object_found_class, $aux)) //checks if it could be a const variable like self::XXX (in case the OnlyBustLocal is false)
						$replacement = $this->encode('V', $aux);
				}
				else if (substr($m, 0, 1) == '$' && substr($m, 1, 1) != '{' && !in_array(substr($m, 1), $this->reserved)) { //means it is a local variable, like: $x
					$parse_var = $m;
				}
				else if (substr($m, 0, 1) == '$' && substr($m, 1, 1) == '{' && substr($m, -1) == '}' && !in_array(substr($m, 2, -1), $this->reserved)) { //means it is a local variable, like: ${x}
					$parse_var = '$' . substr($m, 2, -1);
					$is_encapsulate_var = true;
				}
				else { //checks if global function
					$aux = $m;
					
					if (substr($aux, -1) == "(") {
						if ($this->obfuscateFunctionName($file_path, substr($aux, 0, -1)))
							$replacement = $this->encode('F', substr($aux, 0, -1)) . "(";
					}
					else if ($this->obfuscateFunctionName($file_path, $aux)) //checks if string is function bc it could be an eval with "(" later on. However if $m is a string with the same name than a function, it will be replaced too. This is a risk that you need to make! Otherwise disable this code.
						$replacement = $this->encode('F', $aux);
				}
				
				if ($parse_var) {
					$obfuscate = false;
					
					if ($current_func) { //prepare func/method local/global variables
						if (!empty($local_global_vars[$parse_var])) //checks if func global variables
							$obfuscate = $this->obfuscateGlobalVariableName($file_path, $parse_var);
						else if ($current_class) //local variable inside of class method. 
							$obfuscate = !$this->ignoreClassMethodVariable($file_path, $current_class, $current_func, $parse_var) && $this->obfuscateClassMethodCode($file_path, $current_class, $current_func);
						else //local variable inside of function. 
							$obfuscate = !$this->ignoreFunctionVariable($file_path, $current_func, $parse_var) && $this->obfuscateFunctionCode($file_path, $current_func);
					}
					else //encapsed string is in global file.
						$obfuscate = $this->obfuscateGlobalVariableName($file_path, $parse_var);
					
					if ($obfuscate) {
						$var_name = substr($parse_var, 1);
						$var_encoded = $this->encode('V', $var_name);
						
						if ($is_encapsulate_var) {
							$aux = '${' . $var_name . '}';
							$replacement = '${' . $var_encoded . '}';
						}
						else {
							$aux = $parse_var;
							$replacement = '$' . $var_encoded;
						}
					}
				}
				
				if ($replacement) {
					$new_m = str_replace($aux, $replacement, $match[0]);
					//echo "$aux | $replacement ==> $new_m\n";
					$txt = str_replace($match[0], $new_m, $txt);
				}
			}
		
		//echo "txt:$txt\n";
		return $txt;
	}
	
	//In case of exists inner functions, get parent function and then check if it can obfuscate, but with the parent function settings, bc there are no settings for the inner functions
	private function getParentFunction($current_func, $current_funcs_classes) {
		$cf = $current_func;
		$t = count($current_funcs_classes);
		
		for ($i = $t - 2; $i >= 0; $i--) 
			if (isset($current_funcs_classes[$i][0])) {
				if ($current_funcs_classes[$i][0] == T_FUNCTION)
					$cf = $current_funcs_classes[$i][1];
				else if ($current_funcs_classes[$i][0] == T_CLASS)
					break;
			}
		
		return $cf;
	}
	
	private function isObfuscateObjectMethod($file_path, $current_class, $current_func, $object_found_class, $object_found_method) {
		//for the cases $this->xxx() or self::xxx() or X:xxx()
		if ($object_found_class)
			return $this->obfuscateClassMethodName($file_path, $object_found_class, $object_found_method);
		
		//for the cases $xxx->getName(), that we cannot know the type of $xxx, bc maybe it was passed as a function's argument or something else...
		if ($current_class && $current_func)
			return $this->obfuscateObjectMethodOrVariableInClassMethod($file_path, $current_class, $current_func, $object_found_method);
		else if ($current_func)
			return $this->obfuscateObjectMethodOrVariableInFunction($file_path, $current_func, $object_found_method);
	}
	
	private function isObfuscateObjectVariable($file_path, $current_class, $current_func, $object_found_class, $object_found_var) {
		//for the cases $this->xxx or self::$xxx or X:xxx or static::$xxx
		if ($object_found_class)
			return $this->obfuscateClassVariableName($file_path, $object_found_class, $object_found_var);
		
		//for the cases $xxx->x, that we cannot know the type of $xxx, bc maybe it was passed as a function's argument or something else...
		if ($current_class && $current_func)
			return $this->obfuscateObjectMethodOrVariableInClassMethod($file_path, $current_class, $current_func, $object_found_var);
		else if ($current_func)
			return $this->obfuscateObjectMethodOrVariableInFunction($file_path, $current_func, $object_found_var);
	}
	
	private function isObfuscateInnerFunction($file_path, $current_class, $current_func, $inner_function) {
		//check if reserved function
		if ($this->isReservedFunction($inner_function))
			return false;
		
		if ($current_class)
			return $this->obfuscateClassMethodInnerFunction($file_path, $current_class, $current_func, $inner_function);
		
		return $this->obfuscateFunctionInnerFunction($file_path, $current_func, $inner_function);
	}
	
	private function isObfuscateEncapsedString($file_path, $current_class, $current_func, $current_variable) {
		$obfuscate = false;
		
		if ($current_class && $current_func)
			$obfuscate = $this->obfuscateClassMethodEncapsedString($file_path, $current_class, $current_func);
		else if ($current_func)
			$obfuscate = $this->obfuscateFunctionEncapsedString($file_path, $current_func);
		else if ($current_class && $current_variable)
			$obfuscate = $this->obfuscateClassVariableEncapsedString($file_path, $current_class, $current_variable);
		else if ($current_variable)
			$obfuscate = $this->obfuscateGlobalVariableEncapsedString($file_path, $current_variable);
		else //obfuscate generic variables in file, like, echo "asd \$other asd"
			$obfuscate = $this->obfuscateEncapsedStringFromFile($file_path);
		
		return $obfuscate;
	}
	
	private function isStripEncapsedStringEOL($file_path, $current_class, $current_func, $current_variable) {
		$strip_encapsed_string_eol = false;
		
		if ($current_class && $current_func)
			$strip_encapsed_string_eol =  $this->stripEOLFromClassMethodEncapsedString($file_path, $current_class, $current_func);
		else if ($current_func)
			$strip_encapsed_string_eol = $this->stripEOLFromFunctionEncapsedString($file_path, $current_func);
		else if ($current_class && $current_variable)
			$strip_encapsed_string_eol = $this->stripEOLFromClassVariableEncapsedString($file_path, $current_class, $current_variable);
		else if ($current_variable)
			$strip_encapsed_string_eol = $this->stripEOLFromGlobalVariableEncapsedString($file_path, $current_variable);
		else //generic variables in file, like, echo "asd \$other asd"
			$strip_encapsed_string_eol = $this->stripEncapsedStringEOLFromFile($file_path);
		
		return $strip_encapsed_string_eol;
	}
	
	private function saveObfuscatedFile($file_path, $obfuscated_content) {
		//Saving new code to file
		$save_path = $this->getFileSavePath($file_path);
		
		if ($save_path) {
			$folder = dirname($save_path);
			
			//attempted to create folder
			if (is_dir($folder) || mkdir($folder, 0755, true))
				return file_put_contents($save_path, $obfuscated_content) !== false;
		}
	}
	
	private function getFileSavePath($file_path) {
		return isset($this->files_settings[$file_path][1]["save_path"]) ? $this->files_settings[$file_path][1]["save_path"] : null;
	}
	
	private function addCopyrightToFile($file_path) {
		if (isset($this->files_settings[$file_path][1]["copyright"]))
			return $this->files_settings[$file_path][1]["copyright"];
		
		return isset($this->current_options["copyright"]) ? $this->current_options["copyright"] : null;
	}
	
	private function stripEOLFromFile($file_path) {
		if (isset($this->files_settings[$file_path][1]["strip_eol"]))
			return $this->files_settings[$file_path][1]["strip_eol"];
		
		return isset($this->current_options["strip_eol"]) ? $this->current_options["strip_eol"] : null;
	}
	
	private function stripCommentsFromFile($file_path) {
		if (isset($this->files_settings[$file_path][1]["strip_comments"]))
			return $this->files_settings[$file_path][1]["strip_comments"];
		
		return isset($this->current_options["strip_comments"]) ? $this->current_options["strip_comments"] : null;
	}
	
	private function stripDocCommentsFromFile($file_path) {
		if (isset($this->files_settings[$file_path][1]["strip_doc_comments"]))
			return $this->files_settings[$file_path][1]["strip_doc_comments"];
		
		return isset($this->current_options["strip_doc_comments"]) ? $this->current_options["strip_doc_comments"] : null;
	}
	
	private function obfuscateEncapsedStringFromFile($file_path) {
		if (isset($this->files_settings[$file_path][1]["obfuscate_encapsed_string"]))
			return $this->files_settings[$file_path][1]["obfuscate_encapsed_string"];
		
		return isset($this->current_options["obfuscate_encapsed_string"]) ? $this->current_options["obfuscate_encapsed_string"] : null;
	}
	
	private function stripEncapsedStringEOLFromFile($file_path) {
		if (isset($this->files_settings[$file_path][1]["strip_encapsed_string_eol"]))
			return $this->files_settings[$file_path][1]["strip_encapsed_string_eol"];
		
		return isset($this->current_options["strip_encapsed_string_eol"]) ? $this->current_options["strip_encapsed_string_eol"] : null;
	}
	
	private function obfuscateClassName($file_path, $class_name) {
		if (isset($this->files_settings[$file_path][$class_name]["obfuscate_name"]))
			return $this->files_settings[$file_path][$class_name]["obfuscate_name"];
		
		if (isset($this->files_settings[$file_path][1]["all_classes"]["obfuscate_name"]))
			return $this->files_settings[$file_path][1]["all_classes"]["obfuscate_name"];
			
		return isset($this->current_options["obfuscate_name"]) ? $this->current_options["obfuscate_name"] : null;
	}
	
	private function obfuscateClassVariableName($file_path, $class_name, $var) {
		if (isset($this->files_settings[$file_path][$class_name]["properties"][$var])) {
			$s = $this->files_settings[$file_path][$class_name]["properties"][$var];
			
			return is_array($s) ? (isset($s["obfuscate_name"]) ? $s["obfuscate_name"] : null) : $s;
		}
		
		if (isset($this->files_settings[$file_path][$class_name]["all_properties"]["obfuscate_name"]))
			return $this->files_settings[$file_path][$class_name]["all_properties"]["obfuscate_name"];
			
		if (isset($this->files_settings[$file_path][1]["all_properties"]["obfuscate_name"]))
			return $this->files_settings[$file_path][1]["all_properties"]["obfuscate_name"];
			
		if (isset($this->current_options["obfuscate_name"]))
			return $this->current_options["obfuscate_name"];
		
		if (!empty($this->files_settings[$file_path][$class_name]["__vars"][T_PRIVATE][$var])) {
			if (isset($this->files_settings[$file_path][$class_name]["all_properties"]["obfuscate_name_private"]))
				return $this->files_settings[$file_path][$class_name]["all_properties"]["obfuscate_name_private"];
				
			if (isset($this->files_settings[$file_path][1]["all_properties"]["obfuscate_name_private"]))
				return $this->files_settings[$file_path][1]["all_properties"]["obfuscate_name_private"];
			
			return isset($this->current_options["obfuscate_name_private"]) ? $this->current_options["obfuscate_name_private"] : null;
		}
		
		return false;
	}
	
	private function obfuscateClassVariableEncapsedString($file_path, $class_name, $var) {
		if (isset($this->files_settings[$file_path][$class_name]["properties"][$var]["obfuscate_encapsed_string"]))
			return $this->files_settings[$file_path][$class_name]["properties"][$var]["obfuscate_encapsed_string"];
		
		if (isset($this->files_settings[$file_path][$class_name]["all_properties"]["obfuscate_encapsed_string"]))
			return $this->files_settings[$file_path][$class_name]["all_properties"]["obfuscate_encapsed_string"];
		
		if (isset($this->files_settings[$file_path][1]["all_properties"]["obfuscate_encapsed_string"]))
			return $this->files_settings[$file_path][1]["all_properties"]["obfuscate_encapsed_string"];
			
		if (isset($this->current_options["obfuscate_encapsed_string"]))
			return $this->current_options["obfuscate_encapsed_string"];
		
		if (!empty($this->files_settings[$file_path][$class_name]["__vars"][T_PRIVATE][$var])) {
			if (isset($this->files_settings[$file_path][$class_name]["all_properties"]["obfuscate_encapsed_string_private"]))
				return $this->files_settings[$file_path][$class_name]["all_properties"]["obfuscate_encapsed_string_private"];
				
			if (isset($this->files_settings[$file_path][1]["all_properties"]["obfuscate_encapsed_string_private"]))
				return $this->files_settings[$file_path][1]["all_properties"]["obfuscate_encapsed_string_private"];
				
			return isset($this->current_options["obfuscate_encapsed_string_private"]) ? $this->current_options["obfuscate_encapsed_string_private"] : null;
		}
		
		return false;
	}
	
	private function stripEOLFromClassVariableEncapsedString($file_path, $class_name, $var) {
		if (isset($this->files_settings[$file_path][$class_name]["properties"][$var]["strip_encapsed_string_eol"]))
			return $this->files_settings[$file_path][$class_name]["properties"][$var]["strip_encapsed_string_eol"];
		
		if (isset($this->files_settings[$file_path][$class_name]["all_properties"]["strip_encapsed_string_eol"]))
			return $this->files_settings[$file_path][$class_name]["all_properties"]["strip_encapsed_string_eol"];
		
		if (isset($this->files_settings[$file_path][1]["all_properties"]["strip_encapsed_string_eol"]))
			return $this->files_settings[$file_path][1]["all_properties"]["strip_encapsed_string_eol"];
			
		if (isset($this->current_options["strip_encapsed_string_eol"]))
			return $this->current_options["strip_encapsed_string_eol"];
		
		if (!empty($this->files_settings[$file_path][$class_name]["__vars"][T_PRIVATE][$var])) {
			if (isset($this->files_settings[$file_path][$class_name]["all_properties"]["strip_encapsed_string_eol_private"]))
				return $this->files_settings[$file_path][$class_name]["all_properties"]["strip_encapsed_string_eol_private"];
				
			if (isset($this->files_settings[$file_path][1]["all_properties"]["strip_encapsed_string_eol_private"]))
				return $this->files_settings[$file_path][1]["all_properties"]["strip_encapsed_string_eol_private"];
				
			return isset($this->current_options["strip_encapsed_string_eol_private"]) ? $this->current_options["strip_encapsed_string_eol_private"] : null;
		}
		
		return false;
	}
	
	private function addClassVariable($type, $file_path, $class_name, $var) {
		$this->files_settings[$file_path][$class_name]["__vars"][$type][$var] = true;
	}
	
	private function obfuscateClassMethodName($file_path, $class_name, $method_name) {
		if (substr($method_name, 0, 2) == "__")
			return false;
		
		if (isset($this->files_settings[$file_path][$class_name]["methods"][$method_name]["obfuscate_name"]))
			return $this->files_settings[$file_path][$class_name]["methods"][$method_name]["obfuscate_name"];
		
		if (isset($this->files_settings[$file_path][$class_name]["all_methods"]["obfuscate_name"]))
			return $this->files_settings[$file_path][$class_name]["all_methods"]["obfuscate_name"];
		
		if (isset($this->files_settings[$file_path][1]["all_methods"]["obfuscate_name"]))
			return $this->files_settings[$file_path][1]["all_methods"]["obfuscate_name"];
			
		if (isset($this->current_options["obfuscate_name"]))
			return $this->current_options["obfuscate_name"];
		
		if (!empty($this->files_settings[$file_path][$class_name]["__methods"][T_PRIVATE][$method_name])) {
			if (isset($this->files_settings[$file_path][$class_name]["all_methods"]["obfuscate_name_private"]))
				return $this->files_settings[$file_path][$class_name]["all_methods"]["obfuscate_name_private"];
				
			if (isset($this->files_settings[$file_path][1]["all_methods"]["obfuscate_name_private"]))
				return $this->files_settings[$file_path][1]["all_methods"]["obfuscate_name_private"];
				
			return isset($this->current_options["obfuscate_name_private"]) ? $this->current_options["obfuscate_name_private"] : null;
		}
		
		return false;
	}
	
	private function obfuscateClassMethodCode($file_path, $class_name, $method_name) {
		if (isset($this->files_settings[$file_path][$class_name]["methods"][$method_name]["obfuscate_code"]))
			return $this->files_settings[$file_path][$class_name]["methods"][$method_name]["obfuscate_code"];
		
		if (isset($this->files_settings[$file_path][$class_name]["all_methods"]["obfuscate_code"]))
			return $this->files_settings[$file_path][$class_name]["all_methods"]["obfuscate_code"];
		
		if (isset($this->files_settings[$file_path][1]["all_methods"]["obfuscate_code"]))
			return $this->files_settings[$file_path][1]["all_methods"]["obfuscate_code"];
			
		if (isset($this->current_options["obfuscate_code"]))
			return $this->current_options["obfuscate_code"];
		
		if (!empty($this->files_settings[$file_path][$class_name]["__methods"][T_PRIVATE][$method_name])) {
			if (isset($this->files_settings[$file_path][$class_name]["all_methods"]["obfuscate_code_private"]))
				return $this->files_settings[$file_path][$class_name]["all_methods"]["obfuscate_code_private"];
				
			if (isset($this->files_settings[$file_path][1]["all_methods"]["obfuscate_code_private"]))
				return $this->files_settings[$file_path][1]["all_methods"]["obfuscate_code_private"];
				
			return isset($this->current_options["obfuscate_code_private"]) ? $this->current_options["obfuscate_code_private"] : null;
		}
		
		return false;
	}
	
	private function obfuscateClassMethodEncapsedString($file_path, $class_name, $method_name) {
		if (isset($this->files_settings[$file_path][$class_name]["methods"][$method_name]["obfuscate_encapsed_string"]))
			return $this->files_settings[$file_path][$class_name]["methods"][$method_name]["obfuscate_encapsed_string"];
		
		if (isset($this->files_settings[$file_path][$class_name]["all_methods"]["obfuscate_encapsed_string"]))
			return $this->files_settings[$file_path][$class_name]["all_methods"]["obfuscate_encapsed_string"];
		
		if (isset($this->files_settings[$file_path][1]["all_methods"]["obfuscate_encapsed_string"]))
			return $this->files_settings[$file_path][1]["all_methods"]["obfuscate_encapsed_string"];
			
		if (isset($this->current_options["obfuscate_encapsed_string"]))
			return $this->current_options["obfuscate_encapsed_string"];

		if (!empty($this->files_settings[$file_path][$class_name]["__methods"][T_PRIVATE][$method_name])) {
			if (isset($this->files_settings[$file_path][$class_name]["all_methods"]["obfuscate_encapsed_string_private"]))
				return $this->files_settings[$file_path][$class_name]["all_methods"]["obfuscate_encapsed_string_private"];
				
			if (isset($this->files_settings[$file_path][1]["all_methods"]["obfuscate_encapsed_string_private"]))
				return $this->files_settings[$file_path][1]["all_methods"]["obfuscate_encapsed_string_private"];
				
			return isset($this->current_options["obfuscate_encapsed_string_private"]) ? $this->current_options["obfuscate_encapsed_string_private"] : null;
		}
		
		return false;
	}
	
	private function stripEOLFromClassMethodEncapsedString($file_path, $class_name, $method_name) {
		if (isset($this->files_settings[$file_path][$class_name]["methods"][$method_name]["strip_encapsed_string_eol"]))
			return $this->files_settings[$file_path][$class_name]["methods"][$method_name]["strip_encapsed_string_eol"];
		
		if (isset($this->files_settings[$file_path][$class_name]["all_methods"]["strip_encapsed_string_eol"]))
			return $this->files_settings[$file_path][$class_name]["all_methods"]["strip_encapsed_string_eol"];
		
		if (isset($this->files_settings[$file_path][1]["all_methods"]["strip_encapsed_string_eol"]))
			return $this->files_settings[$file_path][1]["all_methods"]["strip_encapsed_string_eol"];
			
		if (isset($this->current_options["strip_encapsed_string_eol"]))
			return $this->current_options["strip_encapsed_string_eol"];
		
		if (!empty($this->files_settings[$file_path][$class_name]["__methods"][T_PRIVATE][$method_name])) {
			if (isset($this->files_settings[$file_path][$class_name]["all_methods"]["strip_encapsed_string_eol_private"]))
				return $this->files_settings[$file_path][$class_name]["all_methods"]["strip_encapsed_string_eol_private"];
				
			if (isset($this->files_settings[$file_path][1]["all_methods"]["strip_encapsed_string_eol_private"]))
				return $this->files_settings[$file_path][1]["all_methods"]["strip_encapsed_string_eol_private"];
				
			return isset($this->current_options["strip_encapsed_string_eol_private"]) ? $this->current_options["strip_encapsed_string_eol_private"] : null;
		}
		
		return false;
	}
	
	private function obfuscateObjectMethodOrVariableInClassMethod($file_path, $class_name, $method_name, $object_method) {
		$methods = isset($this->files_settings[$file_path][$class_name]["methods"][$method_name]["objects_methods_or_vars"]) ? $this->files_settings[$file_path][$class_name]["methods"][$method_name]["objects_methods_or_vars"] : null;
		
		return is_array($methods) ? in_array($object_method, $methods) : false;
	}
	
	private function ignoreClassMethodVariable($file_path, $class_name, $method_name, $var) {
		$vars1 = isset($this->files_settings[$file_path][1]["all_methods"]["ignore_local_variables"]) ? $this->files_settings[$file_path][1]["all_methods"]["ignore_local_variables"] : null;
		$vars2 = isset($this->files_settings[$file_path][$class_name]["all_methods"]["ignore_local_variables"]) ? $this->files_settings[$file_path][$class_name]["all_methods"]["ignore_local_variables"] : null;
		$vars3 = isset($this->files_settings[$file_path][$class_name]["methods"][$method_name]["ignore_local_variables"]) ? $this->files_settings[$file_path][$class_name]["methods"][$method_name]["ignore_local_variables"] : null;
		
		$vars1 = $vars1 ? $vars1 : array();
		$vars2 = $vars2 ? $vars2 : array();
		$vars3 = $vars3 ? $vars3 : array();
		
		$vars = array_merge($vars1, $vars2, $vars3);
		
		return is_array($vars) ? in_array($var, $vars) : false;
	}
	
	private function addClassMethod($type, $file_path, $class_name, $method_name) {
		$this->files_settings[$file_path][$class_name]["__methods"][$type][$method_name] = true;
	}
	
	private function addClassMethodInnerFunction($file_path, $class_name, $method_name, $func_name) {
		$this->files_settings[$file_path][$class_name]["__inner_functions"][$method_name][$func_name] = true;
	}
	
	private function obfuscateClassMethodInnerFunction($file_path, $class_name, $method_name, $func_name) {
		return !empty($this->files_settings[$file_path][$class_name]["__inner_functions"][$method_name][$func_name]) ? $this->obfuscateClassMethodCode($file_path, $class_name, $method_name) : false;
	}
	
	private function activateClassMethodInclude($file_path, $class_name, $method_name) {
		$this->files_settings[$file_path][$class_name]["__includes"][$method_name] = true;
	}
	
	private function activateClassMethodEval($file_path, $class_name, $method_name) {
		$this->files_settings[$file_path][$class_name]["__evals"][$method_name] = true;
	}
	
	private function obfuscateFunctionName($file_path, $func_name) {
		if (isset($this->files_settings[$file_path][0][$func_name]["obfuscate_name"]))
			return $this->files_settings[$file_path][0][$func_name]["obfuscate_name"];
		
		//check if reserved function
		if ($this->isReservedFunction($func_name))
			return false;
		
		if (isset($this->files_settings[$file_path][1]["all_functions"]["obfuscate_name"]))
			return $this->files_settings[$file_path][1]["all_functions"]["obfuscate_name"];
			
		return $this->current_options["obfuscate_name"];
	}
	
	private function obfuscateFunctionCode($file_path, $func_name) {
		if (isset($this->files_settings[$file_path][0][$func_name]["obfuscate_code"]))
			return $this->files_settings[$file_path][0][$func_name]["obfuscate_code"];
		
		if (isset($this->files_settings[$file_path][1]["all_functions"]["obfuscate_code"]))
			return $this->files_settings[$file_path][1]["all_functions"]["obfuscate_code"];
			
		return isset($this->current_options["obfuscate_code"]) ? $this->current_options["obfuscate_code"] : null;
	}
	
	private function obfuscateFunctionEncapsedString($file_path, $func_name) {
		if (isset($this->files_settings[$file_path][0][$func_name]["obfuscate_encapsed_string"]))
			return $this->files_settings[$file_path][0][$func_name]["obfuscate_encapsed_string"];
		
		if (isset($this->files_settings[$file_path][1]["all_functions"]["obfuscate_encapsed_string"]))
			return $this->files_settings[$file_path][1]["all_functions"]["obfuscate_encapsed_string"];
			
		return isset($this->current_options["obfuscate_encapsed_string"]) ? $this->current_options["obfuscate_encapsed_string"] : null;
	}
	
	private function stripEOLFromFunctionEncapsedString($file_path, $func_name) {
		if (isset($this->files_settings[$file_path][0][$func_name]["strip_encapsed_string_eol"]))
			return $this->files_settings[$file_path][0][$func_name]["strip_encapsed_string_eol"];
		
		if (isset($this->files_settings[$file_path][1]["all_functions"]["strip_encapsed_string_eol"]))
			return $this->files_settings[$file_path][1]["all_functions"]["strip_encapsed_string_eol"];
			
		return isset($this->current_options["strip_encapsed_string_eol"]) ? $this->current_options["strip_encapsed_string_eol"] : null;
	}
	
	private function obfuscateObjectMethodOrVariableInFunction($file_path, $func_name, $object_method) {
		$methods = isset($this->files_settings[$file_path][0][$func_name]["objects_methods_or_vars"]) ? $this->files_settings[$file_path][0][$func_name]["objects_methods_or_vars"] : null;
		return is_array($methods) ? in_array($object_method, $methods) : false;
	}
	
	private function ignoreFunctionVariable($file_path, $func_name, $var) {
		$vars1 = isset($this->files_settings[$file_path][1]["all_functions"]["ignore_local_variables"]) ? $this->files_settings[$file_path][1]["all_functions"]["ignore_local_variables"] : null;
		$vars2 = isset($this->files_settings[$file_path][0][$func_name]["ignore_local_variables"]) ? $this->files_settings[$file_path][0][$func_name]["ignore_local_variables"] : null;
		
		$vars1 = $vars1 ? $vars1 : array();
		$vars2 = $vars2 ? $vars2 : array();
		
		$vars = array_merge($vars1, $vars2);
		
		return is_array($vars) ? in_array($var, $vars) : false;
	}
	
	private function addFunctionInnerFunction($file_path, $func_name, $inner_func_name) {
		$this->files_settings[$file_path][0]["__inner_functions"][$func_name][$inner_func_name] = true;
	}
	
	private function obfuscateFunctionInnerFunction($file_path, $func_name, $inner_func_name) {
		return !empty($this->files_settings[$file_path][0]["__inner_functions"][$func_name][$inner_func_name]) ? $this->obfuscateFunctionCode($file_path, $func_name) : false;
	}
	
	private function activateFunctionInclude($file_path, $func_name) {
		$this->files_settings[$file_path][0]["__includes"][$func_name] = true;
	}
	
	private function activateFunctionEval($file_path, $func_name) {
		$this->files_settings[$file_path][0]["__evals"][$func_name] = true;
	}
	
	private function obfuscateGlobalVariableName($file_path, $var) {
		$s = isset($this->files_settings[$file_path][0][$var]) ? $this->files_settings[$file_path][0][$var] : null;
		return !empty($this->current_options["obfuscate_name"]) || (
			is_array($s) ? (isset($s["obfuscate_name"]) ? $s["obfuscate_name"] : null) : $s
		);
	}
	
	private function obfuscateGlobalVariableEncapsedString($file_path, $var) {
		if (isset($this->files_settings[$file_path][0][$var]["obfuscate_encapsed_string"]))
			return $this->files_settings[$file_path][0][$var]["obfuscate_encapsed_string"];
		
		return isset($this->current_options["obfuscate_encapsed_string"]) ? $this->current_options["obfuscate_encapsed_string"] : null;
	}
	
	private function stripEOLFromGlobalVariableEncapsedString($file_path, $var) {
		if (isset($this->files_settings[$file_path][0][$var]["strip_encapsed_string_eol"]))
			return $this->files_settings[$file_path][0][$var]["strip_encapsed_string_eol"];
		
		return isset($this->current_options["strip_encapsed_string_eol"]) ? $this->current_options["strip_encapsed_string_eol"] : null;
	}
	
	// Returns hashed text
	private function encode($type, $text) {
		$type = strtolower($type);
		
		if ($type == "v" && substr($text, 0, 1) == '$') {
			$prefix = '$';
			$text = substr($text, 1);
		}
		
		$hash = hash("md4", $text);
		$ord = ord(substr($hash, 0, 1));
		
		$length = 5;
		if (($ord >= 97 && $ord <= 110) || ($ord >= 65 && $ord <= 78)) { //between a-n or A-N
			$length = $type == "v" ? 4 : 6; 
			$type = $type == "v" ? 'p' : 'm';
		}
		
		return $prefix . $type . (!empty($this->current_options["plain_encode"]) ? $text : substr($hash, 0, $length) . substr($hash, -$length));
	}
	
	//The value for the $settings2 is always more priority!
	private function mergeFileSettings($settings1, $settings2) {
		if (is_array($settings1) && is_array($settings2))
			foreach ($settings1 as $k => $v)
				if (isset($settings2[$k])) //Note: if $settings1[$k] is not an array and $settings2[$k] exists, $settings2[$k] stays with his value. The value for the $settings2 is always more priority!
					$settings2[$k] = $this->mergeFileSettings($settings1[$k], $settings2[$k]);
				else
					$settings2[$k] = $v;
		
		return $settings2;
	}
	
	private function getConfiguredFilesSettings($files_settings) {
		if ($files_settings) {
			$fs = array();
			
			foreach ($files_settings as $file_path => $file_settings)
				$fs[ $this->getConfiguredPath($file_path) ] = $file_settings;
			
			$files_settings = $fs;
		}
		
		return $files_settings;
	}
	
	private function getConfiguredPath($path) {
		if ($path)
			while (strpos($path, "//") !== false)
				$path = str_replace("//", "/", $path);
		
		return $path;
	}
}
?>
