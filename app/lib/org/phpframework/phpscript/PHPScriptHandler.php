<?php
include_once get_lib("org.phpframework.util.text.TextSanitizer");

class PHPScriptHandler {
	
	public static function parseContent($content, &$external_vars = array(), &$return_values = array()) {
		//echo "content:!$content!<br>\n";
		$start_delimiters = array("<?", "<?php", "<?=");
		$end_delimiters = array("?>");
		
		//create vars based in the $external_vars
		if(is_array($external_vars))
			foreach($external_vars as $var_name => $var_value) 
				if ($var_name) {
					//echo '$' . $var_name . ' = $var_value;'."\n<br>";
					eval('$' . $var_name . ' = $var_value;');
				}
				
		//execute php code
		$new_content = $content;
		$offset = 0;
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
					if($next_char == " " || $next_char == "(" || $next_char == "\$" || $next_char == "\n") {
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
					$return_values[] = eval($code_to_replace);
				}
			   	catch (Error $e) {
					$error_message = "PHP error: " . $e->getMessage();
					debug_log("[PHPScriptHandler::parseContent] $error_message: \n" . self::getCodeConfiguredForLogging($code_to_replace), "error");
			   	}
				catch(ParseError $e) {
					$error_message = "Parse error: " . $e->getMessage();
					debug_log("[PHPScriptHandler::parseContent] $error_message: \n" . self::getCodeConfiguredForLogging($code_to_replace), "error");
				}
				catch(ErrorException $e) {
					$error_message = "Error exception: " . $e->getMessage();
					debug_log("[PHPScriptHandler::parseContent] $error_message: \n" . self::getCodeConfiguredForLogging($code_to_replace), "error");
				}
				catch(Exception $e) {
					$error_message = $e->getMessage();
					debug_log("[PHPScriptHandler::parseContent] $error_message: \n" . self::getCodeConfiguredForLogging($code_to_replace), "error");
				}
				
				$code_to_replace = ob_get_contents();
				ob_end_clean();
				
				$new_content = str_replace($code_to_search, $code_to_replace, $new_content);
			}
		}
		while($exists && $offset < strlen($content));
		
		//update external vars with the potential changed values
		if(is_array($external_vars))
			foreach($external_vars as $var_name => $var_value)
				if ($var_name)
					eval('$external_vars["'. $var_name . '"] = $' . $var_name . ';'); //DO NOT DO THIS: $external_vars[$var_name] = ${$var_name};', OTHERWISE IF THE VAR_NAME == _POST OR _GET, IT WON'T WORK!!!
			
		return $new_content;
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
			debug_log("[PHPScriptHandler::isValidPHPCode] $error_message: \n" . self::getCodeConfiguredForLogging($code), "error");
        	}
		catch(ParseError $e) {
			$error_message = "Parse error: " . $e->getMessage();
			debug_log("[PHPScriptHandler::isValidPHPCode] $error_message: \n" . self::getCodeConfiguredForLogging($code), "error");
		}
		catch(ErrorException $e) {
			$error_message = "Error exception: " . $e->getMessage();
			debug_log("[PHPScriptHandler::isValidPHPCode] $error_message: \n" . self::getCodeConfiguredForLogging($code), "error");
		}
		catch(Exception $e) {
			$error_message = $e->getMessage();
			debug_log("[PHPScriptHandler::isValidPHPCode] $error_message: \n" . self::getCodeConfiguredForLogging($code), "error");
		}
		
		return false;
	}
	
	//Note that this function only check the php syntax. It needs the PhpParser library previous included
	public static function isValidPHPContents($contents, &$error = null) {
		if (!$contents)
			return true;
		
		if (!class_exists("PhpParser\Parser\Php7"))
			include_once get_lib("lib.vendor.phpparser.lib.bootstrap");
		
		$PHPParser5 = new PhpParser\Parser\Php5(new PhpParser\Lexer\Emulative);
		$PHPParser7 = new PhpParser\Parser\Php7(new PhpParser\Lexer\Emulative);
		$PHPParserEmulative = new PhpParser\Parser\Multiple(array($PHPParser5, $PHPParser7));
		
		$exception = null;
		
		try {
			$stmts = $PHPParserEmulative->parse($contents);
			//print_r($stmts);
		}
        	catch (Error $e) {
        		//echo "Error";
			$exception = $e;
        	}
		catch(ParseError $e) {
        		//echo "ParseError";
			$exception = $e;
		}
		catch(ErrorException $e) {
        		//echo "ErrorException";
			$exception = $e;
		}
		catch(Exception $e) {
        		//echo "Exception";
			$exception = $e;
		}
		
		if ($exception) {
			$error = $exception->getMessage();
			debug_log("[PHPScriptHandler::isValidPHPContents] $error: \n" . self::getCodeConfiguredForLogging($contents), "error");
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
		
		$exception = null;
		
		try {
			include $path; //must use "include", bc the php code can be catched if is included through a file. if the eval is used instead, it stops the script and launches the default php error message.
			
			//If there is an error, it stops here and no more php code will be executed.
		}
        	catch (Error $e) {
			$exception = $e;
        	}
		catch(ParseError $e) {
			$exception = $e;
		}
		catch(ErrorException $e) {
			$exception = $e;
		}
		catch(Exception $e) {
			$exception = $e;
		}
		
		error_reporting($error_reporting);
		
		fclose($temp); // this removes the file
		
		if ($exception) {
			$error = $exception->getMessage();
			debug_log("[PHPScriptHandler::isValidPHPContents] $error: \n" . self::getCodeConfiguredForLogging($contents), "error");
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
		$current_host = explode(":", $_SERVER["HTTP_HOST"]); //maybe it contains the port
		$current_host = $current_host[0];
		
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
		
		$error = $result;
		debug_log("[PHPScriptHandler::isValidPHPContentsViaUrl] $error: \n" . self::getCodeConfiguredForLogging($contents), "error");
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
		
		if (!$is_windows && function_exists("shell_exec")) { //maybe shell_exec function was disabled in the php.ini for security reasons
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
				$result = trim(shell_exec("echo " . escapeshellarg($contents) . " | php -l 2>&1"));
			
			if (!$result) { //it may means that the cmd exceeded the memory limited, so we need to use a different approach
				$temp = tmpfile();
				
				$pieces = str_split($contents, 1024 * 4);
				foreach ($pieces as $piece)
					fwrite($temp, $piece, strlen($piece));
				
				$meta_data = stream_get_meta_data($temp);
				$path = isset($meta_data['uri']) ? $meta_data['uri'] : null;
				
				$result = trim(shell_exec("php -l $path 2>&1"));
				fclose($temp); // this removes the file
			}
			
			if (strpos($result, "No syntax errors detected in ") !== false)
				return true;
			
			$error = $result;
			debug_log("[PHPScriptHandler::isValidPHPContentsViaCommandLine] $error: \n" . self::getCodeConfiguredForLogging($contents), "error");
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
		
		include $path;
		
		fclose($temp); // this removes the file
		
		echo "1";
	}
	
	private static function getCodeConfiguredForLogging(&$code) {
		$new_code = "";
		$lines = explode("\n", $code);
		foreach ($lines as $i => $line) 
			$new_code .= "line" . ($i + 1) . ": " . $line . "\n"; 
		
		return $new_code;
	}
}
?>
