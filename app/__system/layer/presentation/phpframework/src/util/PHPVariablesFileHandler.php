<?php
include_once get_lib("org.phpframework.phpscript.PHPCodePrintingHandler");

class PHPVariablesFileHandler {
	private $global_variables_file_paths;
	private $old_globals;
	private $old_global_vars_name;
	
	public function __construct($global_variables_file_paths) {
		$this->global_variables_file_paths = is_array($global_variables_file_paths) ? $global_variables_file_paths : array($global_variables_file_paths);
	}
	
	private function resetSystemPreInitConfigGlobalVarsName() {
		$system_pre_init_config_vars_name = array();
		$file_path = dirname(__DIR__) . "/config/pre_init_config.php";
		
		if (file_exists($file_path)) {
			
			if (self::isSimpleVarsFile($file_path))
				$vars = self::getVarsFromFileContent($file_path);
			else
				$vars = self::getVarsFromFileCode($file_path);
			
			$system_pre_init_config_vars_name = $vars ? array_keys($vars) : array();
		}
		//print_r($system_pre_init_config_vars_name);die();
		
		return $system_pre_init_config_vars_name;
	}
	
	public function startUserGlobalVariables() {
		$this->old_globals = array();
		$this->old_global_vars_name = array_keys($GLOBALS);
		$system_pre_init_config_vars_name = array_flip($this->resetSystemPreInitConfigGlobalVarsName());
		
		if ($this->global_variables_file_paths) {
			foreach ($this->global_variables_file_paths as $global_variables_file_path) {
				if (self::isSimpleVarsFile($global_variables_file_path))
					$user_global_variables = self::getVarsFromFileContent($global_variables_file_path);
				else
					$user_global_variables = self::getVarsFromFileCode($global_variables_file_path);
				
				if ($user_global_variables)
					foreach ($user_global_variables as $var_name => $var_value) {
						$var_name_pos = strpos($var_name, "->");
						
						//Saving old var value
						if (!isset($this->old_globals[$var_name])) {
							if (strpos($var_name, "::") !== false || $var_name_pos !== false) {
								if ($var_name_pos !== false)
									eval("global " . substr($var_name, 0, $var_name_pos) . ";");
						
								eval("\$this->old_globals[\$var_name] = isset($var_name) ? $var_name : null;");
							}
							else 
								$this->old_globals[$var_name] = isset($GLOBALS[$var_name]) ? $GLOBALS[$var_name] : null;
						}
					
						//Preparing var_value
						if (is_string($var_value)) {//$var_value could be an array coming from the getVarsFromFileCode function
							if (strpos($var_value, "::") !== false || strpos($var_value, "->") !== false) {
								$pos = strpos($var_value, "->");
								if ($pos !== false)
									eval("global " . substr($var_value, 0, $pos) . ";");
						
								eval("\$var_value = isset($var_value) ? $var_value : null;");
							}
							else if (substr(trim($var_value), 0, 1) == '$') {
								$aux = substr(trim($var_value), 1);
								$var_value = isset($GLOBALS[$aux]) ? $GLOBALS[$aux] : null;
							}
							else if (substr(trim($var_value), 0, 2) == '@$') {
								$aux = substr(trim($var_value), 2);
								$var_value = isset($GLOBALS[$aux]) ? $GLOBALS[$aux] : null;
							}
						}
						
						//Preparing var_name with correspondent var_value
						if (strpos($var_name, "::") !== false || $var_name_pos !== false) {
							if ($var_name_pos !== false)
								eval("global " . substr($var_name, 0, $var_name_pos) . ";");
						
							eval("$var_name = \$var_value;");
						}
						else 
							$GLOBALS[$var_name] = $var_value;
						
						unset($system_pre_init_config_vars_name[$var_name]);
					}
			}
		}
		
		//reset the system pre init config vars, but first save them to the old_vars. this means that are varibles defined in the $GLOBALS that correspond to the system and that the project didn't set them, which means they have the __system value. So we must unset these variables.
		if ($system_pre_init_config_vars_name)
			foreach ($system_pre_init_config_vars_name as $var_name => $aux) {
				$var_name_pos = strpos($var_name, "->");
				
				//Saving old var value
				if (!isset($this->old_globals[$var_name])) {
					if (strpos($var_name, "::") !== false || $var_name_pos !== false) {
						if ($var_name_pos !== false)
							eval("global " . substr($var_name, 0, $var_name_pos) . ";");
				
						eval("\$this->old_globals[\$var_name] = isset($var_name) ? $var_name : null;");
					}
					else 
						$this->old_globals[$var_name] = isset($GLOBALS[$var_name]) ? $GLOBALS[$var_name] : null;
				}
				
				unset($GLOBALS[$var_name]);
			}
	}
	
	public function endUserGlobalVariables() {	
		foreach ($this->old_globals as $var_name => $var_value) {
			if (strpos($var_name, "::") !== false || strpos($var_name, "->") !== false) {
				$pos = strpos($var_name, "->");
				if ($pos !== false)
					eval("global " . substr($var_name, 0, $pos) . ";");
				
				eval("$var_name = \$var_value;");
			}
			else
				$GLOBALS[$var_name] = $var_value;
		}
		
		if ($this->old_global_vars_name) {
			$global_keys = array_keys($GLOBALS);
			$non_isset_global_vars_name = array_diff($global_keys, $this->old_global_vars_name);
			
			foreach ($non_isset_global_vars_name as $var_name) 
				if (isset($GLOBALS[$var_name]))
					unset($GLOBALS[$var_name]);
		}
	}
	
	public static function isSimpleVarsFile($file_path) {
		$content = file_exists($file_path) ? file_get_contents($file_path) : "";
		return self::isSimpleVarsContent($content);
	}
	
	public static function isSimpleVarsContent($content) {
		$vars = self::getVarsFromContent($content);
		//echo "<pre>";print_r($vars);die();
		
		//Remove comments in order to compare with the vars' code
		$raw_code = PHPCodePrintingHandler::getCodeWithoutComments($content);
		$raw_code = trim(preg_replace("/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", "\n", $raw_code));
		
		//replace " = '...';", by ' = "...";'
		$raw_code = preg_replace("/\s*=\s*'/", ' = "', $raw_code); 
		$raw_code = preg_replace("/'\s*;/", '";', $raw_code);
		
		//remove spaces and end-lines
		$raw_code = preg_replace("/\s/", "", $raw_code);
		
		$code_to_compare = self::getVarsCode($vars);
		$code_to_compare = preg_replace("/\s/", "", $code_to_compare); //remove spaces and end-lines
		//error_log("$raw_code\n\n======\n\n$code_to_compare\n\n", 3, "/var/www/html/livingroop/default/tmp/test.log");
		
		return $code_to_compare == $raw_code;
	}
	
	public static function saveVarsToFile($file_path, $vars, $only_init_if_not_yet_set = false) {
		return file_put_contents($file_path, self::getVarsCode($vars, $only_init_if_not_yet_set)) !== false;
	}
	
	public static function getVarsCode($vars, $only_init_if_not_yet_set = false) {
		$content = "<?php\n";
		
		if ($vars)
			foreach ($vars as $key => $value) {
				$key = (strpos($key, "::") !== false || strpos($key, "->") !== false ? "" : "\$") . $key;
				
				if (is_bool($value))
					$value = $value ? "true" : "false";
				else if (is_numeric($value) || substr(trim($value), 0, 1) == '$' || substr(trim($value), 0, 2) == '@$' || strpos($value, "::") !== false || strpos($value, "->") !== false)
					$value = $value;
				else if (isset($value))
					$value = '"' . addcslashes($value, '"') . '"'; //Do not add the '\\' in the addcslashes, bc the user may have variables with password that contain the char '$', which means, the user must escape this char in the UI. If we add '\\' in the addcslashes, the '$' will stop being escaped and will be considered a variable.
				else
					$value = "null";
				
				$content .= $key . " = " . ($only_init_if_not_yet_set ? "isset(" . $key . ") ? " . $key . " : $value" : $value) . ";\n";
			}
		$content .= "?>";
		
		return $content;
	}
	
	public static function getVarsFromFileCode($file_path) {
		if (file_exists($file_path)) {
			$old_defined_vars = get_defined_vars();
			
			//if this file was included before the PHP cached it, so we must remove the cache.
			if (function_exists('opcache_invalidate') && strlen(ini_get("opcache.restrict_api")) < 1)
				opcache_invalidate($file_path);
			
			ob_start(null, 0);
			include $file_path; //file_path could have some html which will be outputed. we want to avoid any outputs
			ob_end_clean();
			
			$new_defined_vars = get_defined_vars();
			unset($new_defined_vars["old_defined_vars"]);
			unset($new_defined_vars["file_path"]);
			
			$aux = new PHPVariablesFileHandler(null);
			
			$vars1 = array_diff_key($new_defined_vars, $old_defined_vars);
			$vars2 = array_udiff($new_defined_vars, $old_defined_vars, array('PHPVariablesFileHandler', 'customCompare'));//must be array_udiff because the array_diff give error when comparing Class Objects
			$vars = array_merge($vars1, $vars2);
			//echo "<pre>";print_r($vars);die();
			
			return $vars;
		}
	}
	
	public static function customCompare($obj1, $obj2) {
		return $obj1 === $obj2 ? 0 : 1;
	}
	
	public static function getVarsFromFileContent($file_path) {
		$content = file_exists($file_path) ? file_get_contents($file_path) : "";
		return self::getVarsFromContent($content);
	}
	
	public static function getVarsFromContent($content) {
		$vars = array();
			
		if ($content) {
			//$content = PHPCodePrintingHandler::getCodeWithoutComments($content);
			
			preg_match_all('/([\w\$@:\-\>]+)([ ]*)=([ ]*)([^;]+);/u', $content, $matches, PREG_SET_ORDER); //'\w' means all words with '_' and '/u' means with accents and ç too. '/u' converts unicode to accents chars. 
			//echo "<pre>";print_r($matches);die();
			
			$t = count($matches);
			for ($i = 0; $i < $t; $i++) {
				$name = $matches[$i][1];
				$value = trim($matches[$i][4]);
				$char = substr($value, 0, 1);
				
				//check if real name, this is, if $... or Class::something
				if (substr($name, 0, 1) == '$' || substr($name, 0, 2) == '@$' || strpos($name, "::") !== false) {
					//it means is not a real variable and maybe there is a comment that is confusing the regex, this is, something like this: '//optional => only if you which to start a session, but if you DO, please do it here!' This example happens in the pre_init_config.php
				
					$name = substr($name, 0, 1) == "\$" && strpos($name, "::") === false && strpos($name, "->") === false ? substr($name, 1) : $name;
					$name = substr($name, 0, 2) == "@\$" && strpos($name, "::") === false && strpos($name, "->") === false ? substr($name, 2) : $name;
					
					if ($char == '"' || $char == "'") {
						$value = str_replace("\\" . $char, $char, substr($value, 1, -1)); //remove the \" added from the saveVarsToFile
					}
					else {
						preg_match_all('/isset([ ]*)\(([ ]*)([\w\$@:\-\>]+)([ ]*)\)([ ]*)\?([ ]*)([\w\$@:\-\>]+)([ ]*)\:([ ]*)([^;]+);/iu', "$value;", $m, PREG_SET_ORDER); //'\w' means all words with '_' and '/u' means with accents and ç too. '/u' converts unicode to accents chars. 
						
						$value_lower = strtolower($value);
						
						if(isset($m[0][10])) {
							$value = $m[0][10];
							$char = substr($value, 0, 1);
							
							$value = $char == '"' || $char == "'" ? str_replace("\\" . $char, $char, substr($value, 1, -1)) : (strtolower($value) == "null" ? null : $value); //remove the \" added from the saveVarsToFile
						}
						else if ($value_lower == "true")
							$value = true;
						else if ($value_lower == "false")
							$value = false;
						else if ($value_lower == "null")
							$value = null;
					}
			
					$vars[$name] = $value;
				}
			}
		}
		
		return $vars;
	}
	
	public function getGlobalVariablesFilePaths() { return $this->global_variables_file_paths; }
}
?>
