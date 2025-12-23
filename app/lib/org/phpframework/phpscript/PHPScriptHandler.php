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
include_once get_lib("org.phpframework.util.ShellCmdHandler");

class PHPScriptHandler {
	
	public static function parseContent($content, &$external_vars = array(), &$return_values = array(), $ignore_undefined_vars_error = false) {
		//echo "content:!$content!<br>\n";
		
		$set_error_handler = empty($GLOBALS["ignore_undefined_vars_errors"]) && $ignore_undefined_vars_error;
		
		if ($set_error_handler)
			set_error_handler("ignore_undefined_var_error_handler", E_WARNING);
		
		//create vars based in the $external_vars
		$external_vars_backup_var_with_unique_name_that_does_not_enter_in_conflict_with_content = $external_vars;
		$global_keys_inside_of_globals = array("EVC", "_GET", "_POST", "_REQUEST", "_FILES", "_COOKIE", "_ENV", "_SERVER", "_SESSION");
		
		if (is_array($external_vars)) {
			//first globals then local vars
			if (isset($external_vars["GLOBALS"]) && is_array($external_vars["GLOBALS"])) //GLOBALS var cannot be replaced as a whole var. We can only change the inner vars.
				foreach ($external_vars["GLOBALS"] as $k => $v)
					if (!in_array($k, $global_keys_inside_of_globals))
						$GLOBALS[$k] = $v;
			
			foreach ($external_vars as $var_name => $var_value) 
				if ($var_name && $var_name != "GLOBALS") {
					//echo '$' . $var_name . ' = $var_value;'."\n<br>";
					eval('$' . $var_name . ' = $var_value;');
				}
		}
		
		//execute php code
		$start_delimiters = array("<?", "<?php", "<?=");
		$end_delimiters = array("?>");
		
		$new_content = $content;
		$offset = 0;
		$eval_runned = false;
		
		do {
			$exists = false;
	
			$start_delimiter_index = false;
			$end_delimiter_index = false;
			$start_delimiter_pos = false;
			$end_delimiter_pos = false;

			$t = count($start_delimiters);
			for ($i = 0; $i < $t; $i++) {
				$delimiter = $start_delimiters[$i];
				$pos = strpos($content, $delimiter, $offset);
			
				if($pos !== false && ($pos <= $start_delimiter_pos || $start_delimiter_pos === false)) {
					$next_char = substr($content, $pos + strlen($delimiter), 1);
					if($next_char == " " || $next_char == "(" || $next_char == "\$" || $next_char == "\n" || preg_match("/\s/", $next_char)) {
						$start_delimiter_index = $i;
						$start_delimiter_pos = $pos;
						$exists = true;
					}
				}
			}
		
			$t = count($end_delimiters);
			for ($i = 0; $i < $t; $i++) {
				$delimiter = $end_delimiters[$i];
				$pos = strpos($content, $delimiter, $start_delimiter_pos);
				
				if($pos !== false && ($pos <= $end_delimiter_pos || $end_delimiter_pos === false)) {
					$open_double_quotes = false;
					$open_single_quotes = false;
					for($j = $start_delimiter_pos + 1; $j < $pos; $j++) {
						if($content[$j] == '"' && !TextSanitizer::isCharEscaped($content, $j))
							$open_double_quotes = !$open_double_quotes;
						elseif($content[$j] == "'" && !TextSanitizer::isCharEscaped($content, $j))
							$open_single_quotes = !$open_single_quotes;
					}
					
					if(!$open_double_quotes && !$open_single_quotes) {
						$end_delimiter_index = $i;
						$end_delimiter_pos = $pos;
					}
				}
			}
	
			if (!is_numeric($end_delimiter_index)) {
				$end_delimiter_index = 0;
				$end_delimiter_pos = strlen($content);
				$end_delimiter = $end_delimiters[0];
				
				$offset = strlen($content);
			}
			else {
				$end_delimiter = $end_delimiters[$end_delimiter_index];
				
				$offset = $end_delimiter_pos + strlen($end_delimiter);
			}
			
			if ($exists && is_numeric($start_delimiter_index)) {
				$start_delimiter = $start_delimiters[$start_delimiter_index];
		
				$end = $end_delimiter_pos + strlen($end_delimiter);
				$code_to_search = substr($content, $start_delimiter_pos, $end - $start_delimiter_pos);
				
				$start = $start_delimiter_pos + strlen($start_delimiter);
				$code_to_replace = substr($content, $start, $end_delimiter_pos - $start);
				
				if($start_delimiter == "<?=") {
					$code_to_replace = "echo " . $code_to_replace;
					
					if(substr(trim($code_to_replace), strlen(trim($code_to_replace)) - 1) != ";")
						$code_to_replace .= ";";
				}
				
				//echo "code_to_replace:!$code_to_replace!<br>\n";
				//error_log($code_to_replace . "\n\n", 3, $GLOBALS["log_file_path"] ? $GLOBALS["log_file_path"] : "/var/www/html/livingroop/default/tmp/phpframework.log");
				
				ob_start(null, 0);
				
				try {
					//error_log("$start_delimiter_pos:$end_delimiter_pos => ".substr($code_to_search, 0, 100)."\n", 3, TMP_PATH . "/error_log_index");
					$eval_runned = true;
					
					$return_values[] = eval($code_to_replace);
				}
			   catch (Error $e) {
			   	self::debugError($e, $code_to_replace, "parseContent", "PHP error");
			   }
				catch(ParseError $e) {
			   	self::debugError($e, $code_to_replace, "parseContent", "Parse error");
				}
				catch(ErrorException $e) {
			   	self::debugError($e, $code_to_replace, "parseContent", "Error exception");
				}
				catch(Exception $e) {
			   	self::debugError($e, $code_to_replace, "parseContent", "Exception");
				}
				
				$code_to_replace = ob_get_contents();
				ob_end_clean();
				
				$new_content = str_replace($code_to_search, $code_to_replace, $new_content);
				
				//$code_to_search != $code_to_replace && error_log($code_to_replace."\n\n\n", 3, TMP_PATH . "/error_log_code");
			}
		}
		while($exists && $offset < strlen($content));
		
		//update external vars with the potential changed values
		$external_vars = $external_vars_backup_var_with_unique_name_that_does_not_enter_in_conflict_with_content;
		
		if ($eval_runned && is_array($external_vars)) {
			//in case the code_to_replace runned in the eval has some references
			unset($var_name);
			unset($var_value);
			unset($k);
			unset($v);
			
			//update external vars - first globals then local vars
			if (isset($external_vars["GLOBALS"]) && is_array($external_vars["GLOBALS"]))
				foreach ($external_vars["GLOBALS"] as $k => $v)
					if (!in_array($k, $global_keys_inside_of_globals))
						$external_vars["GLOBALS"][$k] = $GLOBALS[$k];
			
			foreach($external_vars as $var_name => $var_value)
				if ($var_name && $var_name != "GLOBALS") {
					eval('$external_vars["'. $var_name . '"] = $' . $var_name . ';'); //DO NOT DO THIS: $external_vars[$var_name] = ${$var_name};', OTHERWISE IF THE VAR_NAME == _POST OR _GET, IT WON'T WORK!!!
					
					//update globals too, otherwise the next time we call this function in the some logic flow, the Globals will have the original values.
					if (in_array($var_name, $global_keys_inside_of_globals) && isset($external_vars["GLOBALS"][$var_name]))
						$external_vars["GLOBALS"][$var_name] = $external_vars[$var_name];
				}
			
			//error_log("code_to_search:$code_to_search\nexternal_vars POST:".print_r($external_vars["_POST"], 1).print_r($_POST, 1)."\n", 3, "/var/www/html/livingroop/default/tmp/phpframework.log");
		}
		
		if ($set_error_handler)
			restore_error_handler();
		
		return $new_content;
	}
	
	//Not tested
	//The difference between this method and the parseContent method, is that, if there is an error in some php statement of the $content, this method returns an empty result, because the error will be catched with no return string. Instead of the parseContent method, that executes each php statement separately returning the rest of the result and only giving error in the catched php statement with error.
	public static function parseContentWithIncludeFile($content, &$external_vars = array(), &$return_values = array(), $ignore_undefined_vars_error = false) {
		//echo "content:!$content!<br>\n";
		$status = false;
		$file = tmpfile();
		$status = fwrite($file, $content);
		
		if ($status) {
			$file_path = stream_get_meta_data($file);
			$file_path = isset($file_path['uri']) ? $file_path['uri'] : null;
			
			$new_content = self::getIncludedFilePathHtml($file_path, $external_vars, $return_values, $ignore_undefined_vars_error);
		}
		else 
			$new_content = self::parseContent($content, $external_vars, $return_values, $ignore_undefined_vars_error);
		
		//fclose temp file to delete it
		if ($file)
			fclose($file);
		
		return $new_content;
	}
	
	//Not tested
	public static function getIncludedFilePathHtml($file_path, &$external_vars = array(), &$return_values = array(), $ignore_undefined_vars_error = false) {
		//execute php code
		ob_start(null, 0);
		
		self::includeFilePath($file_path, $external_vars, $return_values, $ignore_undefined_vars_error);
		
		$new_content = ob_get_contents();
		ob_end_clean();
		
		return $new_content;
	}
	
	//Not tested
	public static function includeFilePath($file_path, &$external_vars = array(), &$return_values = array(), $ignore_undefined_vars_error = false) {
		if (file_exists($file_path)) {
			$file_path_unique_var_name_that_will_never_be_replaced_by_the_external_vars = $file_path;
			
			$set_error_handler = empty($GLOBALS["ignore_undefined_vars_errors"]) && $ignore_undefined_vars_error;
			
			if ($set_error_handler)
				set_error_handler("ignore_undefined_var_error_handler", E_WARNING);
			
			//create vars based in the $external_vars
			$external_vars_backup_var_with_unique_name_that_does_not_enter_in_conflict_with_content = $external_vars;
			$global_keys_inside_of_globals = array("EVC", "_GET", "_POST", "_REQUEST", "_FILES", "_COOKIE", "_ENV", "_SERVER", "_SESSION");
			
			if (is_array($external_vars)) {
				//first globals then local vars
				if (isset($external_vars["GLOBALS"]) && is_array($external_vars["GLOBALS"])) //GLOBALS var cannot be replaced as a whole var. We can only change the inner vars.
					foreach ($external_vars["GLOBALS"] as $k => $v)
						if (!in_array($k, $global_keys_inside_of_globals))
							$GLOBALS[$k] = $v;
				
				foreach ($external_vars as $var_name => $var_value) 
					if ($var_name && $var_name != "GLOBALS") {
						//echo '$' . $var_name . ' = $var_value;'."\n<br>";
						eval('$' . $var_name . ' = $var_value;');
					}
			}
			
			//execute php code
			try {
				$return_values[] = include $file_path_unique_var_name_that_will_never_be_replaced_by_the_external_vars; //note that the include may return some value
			}
			catch (Error $e) {
				$content = file_get_contents($file_path_unique_var_name_that_will_never_be_replaced_by_the_external_vars);
				self::debugError($e, $content, "includeFilePath", "PHP error");
			}
			catch(ParseError $e) {
				$content = file_get_contents($file_path_unique_var_name_that_will_never_be_replaced_by_the_external_vars);
				self::debugError($e, $content, "includeFilePath", "Parse error");
			}
			catch(ErrorException $e) {
				$content = file_get_contents($file_path_unique_var_name_that_will_never_be_replaced_by_the_external_vars);
				self::debugError($e, $content, "includeFilePath", "Error exception");
			}
			catch(Exception $e) {
				$content = file_get_contents($file_path_unique_var_name_that_will_never_be_replaced_by_the_external_vars);
				self::debugError($e, $content, "includeFilePath", "Exception");
			}
			
			//update external vars with the potential changed values
			$external_vars = $external_vars_backup_var_with_unique_name_that_does_not_enter_in_conflict_with_content;
			
			if(is_array($external_vars)) {
				//in case the code_to_replace runned in the eval has some references
				unset($var_name);
				unset($var_value);
				unset($k);
				unset($v);
				
				//update external vars - first globals then local vars
				if (isset($external_vars["GLOBALS"]) && is_array($external_vars["GLOBALS"]))
					foreach ($external_vars["GLOBALS"] as $k => $v)
						if (!in_array($k, $global_keys_inside_of_globals))
							$external_vars["GLOBALS"][$k] = $GLOBALS[$k];
				
				foreach($external_vars as $var_name => $var_value)
					if ($var_name && $var_name != "GLOBALS") {
						eval('$external_vars["'. $var_name . '"] = $' . $var_name . ';'); //DO NOT DO THIS: $external_vars[$var_name] = ${$var_name};', OTHERWISE IF THE VAR_NAME == _POST OR _GET, IT WON'T WORK!!!
						
						//update globals too, otherwise the next time we call this function in the some logic flow, the Globals will have the original values.
						if (in_array($var_name, $global_keys_inside_of_globals) && isset($external_vars["GLOBALS"][$var_name]))
							$external_vars["GLOBALS"][$var_name] = $external_vars[$var_name];
					}
			}
			
			if ($set_error_handler)
				restore_error_handler();
		}
		
		return null;
	}
	
	/* 
	   Note that the $code must be a valid php code without being wraped in the PHP TAGS, bc the $code will be executed through the eval.
	   
	   Valid code samples:
	   	$code = "''";
	   	$code = '""';
		$code = '$x[23][\'asd\'][ $x["as"] ]';
		$code = '${x[23][\'asd\'][ $x["as"] ]}';
		$code = '"{$x[23][\'asd\'][ $x[\'as\'] ]}"';
		$code = '"asa" . "as"';
		$code = "'asd'";
		$code = 'Bar::foo("as", 234) . jplpinto() . "{$x[23][\'as\'][$y]}" . foo';
		$code = '"{$x[23][\'as\'][$y]}"';
		$code = '"asa" . $x. "as"';
		$code = '"onClick=\"return openChannelArticles(this, event, \'#url#\', \'#label#\')\""';
		$code = '$asd . "{$asd[\'as\']}" . $d';
		$code = "true";
		$code = 'aasd_as'; //php thinks it is a defined var
		$code = 'ArticleService . insert'; //php thinks "ArticleService" is an defined var with an object and "insert" a method.
		$code = 'ArticleService -> insert'; //php thinks "ArticleService" is an defined var with an object and "insert" a method.
		$code = 'ArticleService :: insert'; //php thinks "ArticleService" is an defined var with an object and "insert" a method.
		
	   Invalid code samples:
	   	$code = "";
		$code = 'aasd as d asd a';
		$code = '#previous_html##label##next_html#';
		$code = 'aasd_{$d}as';
		$code = 'aasd_${d}as';
	*/
	public static function isValidPHPCode($code, &$error_message) {
		try {
			if (mb_strlen($code)) { //if value is empty string, returns false, avoiding to execute empty code.
				//eval is safe here because it won't do anything
				//since the first thing we do is return
				//but we still get parse errors if it's not valid
				//If that happens, it will crash the whole script, 
				//so we need it to be in a try and catch or in a different request
				
				$return = eval("return 1; $code");  //return 1; is very important so the php doesn't execute the rest of the code.
				return $return;
			}
		}
      catch (Error $e) {
			$error_message = "PHP error: " . $e->getMessage();
			self::debugError($e, $code, "isValidPHPCode", "PHP error");
     	}
		catch(ParseError $e) {
			$error_message = "Parse error: " . $e->getMessage();
			self::debugError($e, $code, "isValidPHPCode", "Parse error");
		}
		catch(ErrorException $e) {
			$error_message = "Error exception: " . $e->getMessage();
			self::debugError($e, $code, "isValidPHPCode", "Error exception");
		}
		catch(Exception $e) {
			$error_message = $e->getMessage();
			self::debugError($e, $code, "isValidPHPCode", "Exception");
		}
		
		return false;
	}
	
	//Note that this function only check the php syntax. It needs the PhpParser library previous included
	public static function isValidPHPContents($contents, &$error = null) {
		if (!$contents)
			return true;
		
		if (!class_exists("PHPMultipleParser"))
			include_once get_lib("org.phpframework.phpscript.phpparser.phpparser_autoload");
		
		$PHPMultipleParser = new PHPMultipleParser();
		
		try {
			$stmts = $PHPMultipleParser->parse($contents);
			//print_r($stmts);
		}
     	catch (Error $e) {
     		//echo "Error";
			self::debugError($e, $contents, "isValidPHPContents", "PHP error");
     	}
		catch(ParseError $e) {
     		//echo "ParseError";
			self::debugError($e, $contents, "isValidPHPContents", "Parse error");
		}
		catch(ErrorException $e) {
     		//echo "ErrorException";
			self::debugError($e, $contents, "isValidPHPContents", "Error exception");
		}
		catch(Exception $e) {
     		//echo "Exception";
			self::debugError($e, $contents, "isValidPHPContents", "Exception");
		}
		
		if (!empty($e)) {
			$error = $e->getMessage();
			return false;
		}
		
		return true;
	}
	
	//The problem with this function is that it will execute the php code and we only wanted to check the php syntax. If we execute the PHP code will give erros bc there will be variables that will not be present here!
	public static function isValidPHPContents2($contents, &$error = null) {
		$temp = tmpfile();
			
		$pieces = str_split($contents, 1024 * 4);
		foreach ($pieces as $piece)
			fwrite($temp, $piece, strlen($piece));
		
		$meta_data = stream_get_meta_data($temp);
		$path = isset($meta_data['uri']) ? $meta_data['uri'] : null;
		
		$error_reporting = error_reporting();
		error_reporting(0);
		
		try {
			include $path; //must use "include", bc the php code can be catched if is included through a file. if the eval is used instead, it stops the script and launches the default php error message.
			
			//If there is an error, it stops here and no more php code will be executed.
		}
     	catch (Error $e) {
			self::debugError($e, $contents, "isValidPHPContents", "PHP error");
     	}
		catch(ParseError $e) {
			self::debugError($e, $contents, "isValidPHPContents", "Parse error");
		}
		catch(ErrorException $e) {
			self::debugError($e, $contents, "isValidPHPContents", "Error exception");
		}
		catch(Exception $e) {
			self::debugError($e, $contents, "isValidPHPContents", "Exception");
		}
		
		error_reporting($error_reporting);
		
		fclose($temp); // this removes the file
		
		if (!empty($e)) {
			$error = $e->getMessage();
			return false;
		}
		
		return true;
	}
	
	/*
	 * Send $code to the url page through curl and check if the answer is == 1. Othewise is not valid.
	 *
	 * The idea is to have a file that calls the isValidPHPContentsViaUrl which will call another file (via URL) which will include the php code and echo "1". The first file will check then the request's response and if it is == 1, the code is valid, otherwise it shows the error.
	 * 
	 * Note: this function is not tested.
	 */
	//The problem with this function is that it will execute the php code and we only wanted to check the php syntax. If we execute the PHP code will give erros bc there will be variables that will not be present here!
	public static function isValidPHPContentsViaUrl($url, $contents, &$error = null, $connection_timeout = 0) {
		if (!$contents)
			return true;
		
		$data = array("contents" => $contents);
		
		$url_host = parse_url($url, PHP_URL_HOST);
		$current_host = isset($_SERVER["HTTP_HOST"]) ? explode(":", $_SERVER["HTTP_HOST"]) : null; //maybe it contains the port
		$current_host = isset($current_host[0]) ? $current_host[0] : null;
		
		$settings = array(
			"url" => $url, 
			"post" => $data, 
			"cookie" => $current_host == $url_host ? $_COOKIE : null,
			"settings" => array(
				"referer" => isset($_SERVER["HTTP_REFERER"]) ? $_SERVER["HTTP_REFERER"] : null,
				"follow_location" => 1,
				"connection_timeout" => $connection_timeout,
			)
		);
		
		if (!empty($_SERVER["AUTH_TYPE"]) && !empty($_SERVER["PHP_AUTH_USER"])) {
			$settings["settings"]["http_auth"] = $_SERVER["AUTH_TYPE"];
			$settings["settings"]["user_pwd"] = $_SERVER["PHP_AUTH_USER"] . ":" . (isset($_SERVER["PHP_AUTH_PW"]) ? $_SERVER["PHP_AUTH_PW"] : null);
		}
		
		$MyCurl = new MyCurl();
		$MyCurl->initSingle($settings);
		$MyCurl->get_contents();
		$response = $MyCurl->getData();
		$response = isset($response[0]["content"]) ? $response[0]["content"] : null;
		
		if ($response == 1)
			return true;
		
		$error = $response;
		self::debugError(new Exception($error), $contents, "isValidPHPContentsViaUrl", "Exception");
		//echo $contents;die();
		return false;
	}
	
	/* We strongly DON'T advise the use of this method bc it uses command line, and if a server has the php command line defined differently, this command line won't work, this is, if a server doesn't have the php in the /bin or /usr/bin we cannot simply do "php ...", we must use the full path "/usr/bin/php/". 
	 * Additionally this function doesn't work on windows.
	 * TRY TO AVOID THIS METHOD
	 * Instead you can use the the methods isValidPHPContents or printPHPContentsViaUrl + isValidPHPContentsViaUrl. 
	 * The idea is to have a file that calls the isValidPHPContentsViaUrl which will call another file (via URL) which will include the php code and echo "1". The first file will check then the request's response and if it is == 1, the code is valid, otherwise it shows the error.
	 * 
	 * Additionally note that the shell_exec function should be avoid bc has some security risks
	 */
	//$contents must be wrapped in the PHP TAGS (<?)
	public static function isValidPHPContentsViaCommandLine($contents, &$error = null) {
		//Maybe this works on windows too. Only disable this line, when is tested on windows. I did NOT test this on windows. (20-03-2019)
		//$is_windows = stripos(php_uname(), "windows") !== false;
		$is_windows = false; 
		
		if (!$is_windows && ShellCmdHandler::isAllowed()) { //maybe shell_exec function was disabled in the php.ini for security reasons
			$result = null;
			
			/*
			 * Shell arg is invalid when we have a slash escaped like \\\". In shell, we do not need to escape the slash, but in php we need. This means that if the php code contains a slash escaped (this is: \\), the shell arg will return a php error. 
			 * Here is an example: 
			 * 	$contents = 'echo "joao \\\" paulo";'
			 * This code will give a php error, but this code is correct.
			 * However the code:
			 * 	$contents = 'echo "joao \\ paulo";'
			 * ...executes correctly.
			 * So we simple test if exists any case like \\\" and if so, we save the $contents to a file and then test it.
			 */
			//$is_shell_arg_invalid = strpos($contents, '\\\\\\"') !== false;
			$is_shell_arg_invalid = addcslashes(stripslashes($contents), '"') != $contents;
			//echo "is_shell_arg_invalid:$is_shell_arg_invalid";die();
			
			if (!$is_shell_arg_invalid)
				$result = trim(ShellCmdHandler::exec("echo " . ShellCmdHandler::escapeArg($contents) . " | php -l 2>&1"));
			
			if (!$result) { //it may means that the cmd exceeded the memory limited, so we need to use a different approach
				$temp = tmpfile();
				
				$pieces = str_split($contents, 1024 * 4);
				foreach ($pieces as $piece)
					fwrite($temp, $piece, strlen($piece));
				
				$meta_data = stream_get_meta_data($temp);
				$path = isset($meta_data['uri']) ? $meta_data['uri'] : null;
				
				$result = trim(ShellCmdHandler::exec("php -l " . ShellCmdHandler::escapeArg($path) . " 2>&1"));
				fclose($temp); // this removes the file
			}
			
			if (strpos($result, "No syntax errors detected in ") !== false)
				return true;
			
			$error = $result;
			self::debugError(new Exception($error), $contents, "isValidPHPContentsViaCommandLine", "Exception");
			//echo $contents;die();
			return false;
		}
		
		return true;
	}
	
	/*
	 * receives $code from POST, create a temp file with it, include file, catch all outputs and at the end returns "1". If echo 1 is the only thing printed, it means the code is correct.
	 * Note: this function should not return anything bc if there is a php syntax error, apache will stop executing the code at the "include" line and echo the php error.
	 * 
	 * Note: this function is not tested.
	 */
	public static function printPHPContentsViaUrl() {
		$data = htmlspecialchars_decode( file_get_contents("php://input") );
		$data = json_decode($data, true);
		$contents = isset($data["contents"]) ? $data["contents"] : null;
		
		$temp = tmpfile();
				
		$pieces = str_split($contents, 1024 * 4);
		foreach ($pieces as $piece)
			fwrite($temp, $piece, strlen($piece));
		
		$meta_data = stream_get_meta_data($temp);
		$path = isset($meta_data['uri']) ? $meta_data['uri'] : null;
		
		ob_start(null, 0);
		
		include $path;
		
		ob_end_clean();
		
		fclose($temp); // this removes the file
		
		echo "1";
	}
	
	private static function debugError($e, $code, $func, $type) {
		$traces = self::getTracesAsString();
		$code_traces = $e->getTraceAsString();
		$code = self::getCodeConfiguredForLogging($code);
		
		$traces = str_replace("\n", "\n\t", $traces);
		$code_traces = str_replace("\n", "\n\t", $code_traces);
		$code = str_replace("\n", "\n\t", $code);
		
		debug_log("[PHPScriptHandler::$func] $type: " . $e->getMessage() . "\n   In file " . $e->getFile() . ":" . $e->getLine() . "\n   With code traces:\n\t" . $code_traces . "\n\n   With code:\n\t" . $code . "\n   With PHPScriptHandler traces:\n\t" . $traces, "error"); //should be error and not exception, bc if exception it will add an extra level of back trace which is not user friendly.
	}
	
	private static function getCodeConfiguredForLogging(&$code) {
		$new_code = "";
		$lines = explode("\n", $code);
		foreach ($lines as $i => $line) 
			$new_code .= "line" . ($i + 1) . ": " . $line . "\n"; 
		
		return $new_code;
	}
	
	private static function getTracesAsString($ignore_traces = 1, $max_arg_len = null) {
		$str = "";
		
		//Note that the args will contain the same information then the $code, so we must omit this so the log doesn't get too confused.
		$traces = debug_backtrace();
		//$traces = self::getTracesAsString(debug_backtrace(3)); //DEPRECATED bc we filter the $code in the code below. debug_backtrace(3) or debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS): Populate index "object" and omit index "args". 
		
		$ignore_traces = $ignore_traces > 0 ? $ignore_traces : 0;
				
		if ($traces)
			foreach($traces as $i => $trace) {
				if ($i < $ignore_traces)
					continue;
				
				/**
				* THIS IS NEEDED! If all your objects have a __toString function it's not needed!
				* 
				* Catchable fatal error: Object of class B could not be converted to string
				* Catchable fatal error: Object of class A could not be converted to string
				* Catchable fatal error: Object of class B could not be converted to string
				*/
				if (isset($trace["object"]) && is_object($trace["object"]))
					$trace["object"] = /*"CONVERTED OBJECT OF CLASS " . */get_class($trace["object"]);
				
				if (isset($trace["args"]) && is_array($trace["args"]))
					foreach ($trace["args"] as &$arg)
						if (is_object($arg)) 
							$arg = /*"CONVERTED OBJECT OF CLASS " . */get_class($arg);
				
				$type = isset($trace["type"]) ? $trace["type"] : "";
				$object = isset($trace["object"]) ? $trace["object"] : "";
				$function = isset($trace["function"]) ? $trace["function"] : "";
				$args = isset($trace["args"]) ? $trace["args"] : "";
				
				if (is_array($args)) {
					foreach($args as $j => $v) {
						if (is_null($v))
							$v = 'null';
						else if (is_array($v))
							$v = 'Array['.sizeof($v).']';
						else if (is_object($v)) 
							$v = 'Object:'.get_class($v);
						else if (is_bool($v)) 
							$v = $v ? 'true' : 'false';
						else if ($j == 0 && is_string($v) && !$object && ($function == "parseContent" || $function == "parseContentWithIncludeFile" || $function == "isValidPHPCode" || $function == "isValidPHPContents" || $function == "isValidPHPContents2" || $function == "isValidPHPContentsViaCommandLine"))
							$v = '$executed_php_code';
						else if ($j == 1 && is_string($v) && !$object && ($function == "debugError" || $function == "isValidPHPContentsViaUrl"))
							$v = '$executed_php_code';
						else { 
							$v = (string) @$v;
							
							if ($max_arg_len) {
								$aux = htmlspecialchars(substr($v, 0, $max_arg_len));
								
								if (strlen($v) > $max_arg_len) 
									$aux .= '...';
								
								$v = $aux;
							}
						}
						
						$args[$j] = $v;
					}
					
					$args = implode(', ', $args);
				}
				
				$str .= "#" . ($i - $ignore_traces) . " " . $trace["file"] . "(" . $trace["line"] . ") ";
				$str .= $object ? $object . $type : "";
				$str .= $function . "(" . $args . ")\n";
			}
		
		return $str;
	}
}
?>
