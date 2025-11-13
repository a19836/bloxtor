<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

include_once get_lib("org.phpframework.ptl.exception.PHPTemplateLanguageException");
include_once get_lib("org.phpframework.cache.user.IUserCacheHandler");
include_once get_lib("org.phpframework.phpscript.PHPScriptHandler");
include_once get_lib("org.phpframework.util.text.TextSanitizer");

/*
$template = '
<php:define name "name">

<div class="div_class">
	<form action="?name=<?:echo @$_GET[name]>" method="post">
		<php:for $i = 0; $i < 2; $i++>
			<php:if @$_GET[name] == 123 && 1 == 1>
				<input type="text" name="name" value="<php:echo $_GET[name]>" />
			<php:elseif asd == 123>
				<input type="text" name="name" value="<php:echo @$_GET[name]>" />
			<ptl:else>
				<textarea name="name"><?:echo @$_GET[name]></textarea>
			</?:if>
		</php:for>
		
		<?:echo joao 123>
		<!--?:echo "joao 123"-->
	</form>
</div>';

$PHPTemplateLanguage = new PHPTemplateLanguage();
echo $PHPTemplateLanguage->parseTemplate($template);

Some functions:
	<php:funcXXX jp 12>
		funcXXX("jp", 12);
	
	<php:funcXXX (12.3412 as) jp>
		funcxxx ((12.3412 . "as"), "jp");
		
	<php:funcXXX jp . pau (1 + 12.3412 1 < 2)>
		funcxxx ("jp" . pau(1 + 12.3412, 1 < 2));
	
	<php:echo @$_GET ? print_r($_GET, 1) : "NO GET ARRAY">
		echo @$_GET ? print_r($_GET, 1) : "NO GET ARRAY";
	<php:echo +$_GET[NAME]>
		echo "+" . $_GET["NAME"];
	<php:echo +.$_GET[NAME]>
		echo "+" . $_GET["NAME"];
	<php:echo +@$_GET[NAME]>
		echo "+" . @$_GET["NAME"];
	<php:echo +.@$_GET[NAME]>
		echo "+" . @$_GET["NAME"];
	<php:echo +2as()>
		echo "+" . 2as();
	<php:echo + ()>
		echo "+" . "()";
	<php:echo as +asdsd-12>
		echo "as+asdsd-12";
	<php:echo @$_GET[NAME]>
		echo @$_GET["NAME"];
		
		If NAME is a defined variable, ignore it and convert it to ["NAME"].
		If you wish to call the defined NAME, we must do first:
			<php:definevar:name NAME>
			<php:echo $_GET[$name]>
				$name = NAME;
				echo $name;
	
	<php:echo as +asd " " sd-12>
		echo "as+asd sd-12";
		
	<php:echo as +asd" "sd-12>
		echo "as+asd\" \"sd-12";

	<php:definevar:name NAME>
		$name = NAME;
		
	<php:define NAME name>
		define("NAME","name");
	
	<php:echo name intval($y) callFuncXX (asd, floatVal(123), array(1,2,asd))>
		echo name . intval($y) . callFuncXX (asd, floatVal(123), array(1,2,asd));
	
	<php:echo name intval($y) "," callFuncXX (asd, floatVal(123), array(1,2,asd))>
		echo "name" . intval($y) . "," . callFuncXX("asd", floatVal(123), array(1, 2, "asd"));
	
	<ptl:echo @$arr[@$_GET[0]]$arr[ @$_GET[$name ] ] or $arr[ $_GET[name ]][joao][paulo]>
		echo @$arr[@$_GET[0]] . $arr[ @$_GET[$name ] ] . "or" . $arr[ $_GET["name" ]]["joao"]["paulo"];
	
	<ptl:echo asd/ss{$var[asd][asd(asd 12)]}ss/asd>
		echo "asd/ss" . {$var["asd"][asd("asd", 12)]} . "sd/ss{/asd";

	<ptl:echo asd/ss${var[asd][asd(asd 12)]}ss/asd>
		echo "asd/ss" . {$var["asd"][asd("asd", 12)]} . "sd/ss/asd";

	<ptl:echo "asd/ss{$var[asd][asdss]}ss/asd">
		echo "asd/ss{$var[asd][asdss]}ss/asd";
		
	<ptl:foo @$_GET[bar]>
		foo(@$_GET["bar"]);
	
	<php:var:name '.' intval($y) . callFuncXX (asd, floatVal(123), array(1,2,asd), paulo>
		$name = '.' . intval($y) . callFuncXX("asd", floatVal(123), array(1, 2, "asd"), "paulo");
	
	- <php:var:name intval($y) + callFuncXX (asd, floatVal(123) . 2, array(1,2,asd), (joao paulo))>
		echo '- ';
		$name = intval($y) + callFuncXX("asd", floatVal(123) . 2, array(1, 2, "asd"), ("joao" . "paulo"));
	
	<php:if @$x == joao || intval($y) &gt; 1 && callFuncXX (12 floatVal(sads), array(1,2,asd), @$_POST)>
		if (@$x == "joao" || intval($y) > 1 && callFuncXX(12, floatVal("sads"), array(1, 2, "asd"), @$_POST)) {
	
	<php:elseif $x == joao || intval($y) &gt; 1 && callFuncXX (12 floatVal(sads), array(1,2,asd))>
		} else if ($x == "joao" || intval($y) > 1 && callFuncXX(12, floatVal("sads"), array(1, 2, "asd"))) {
	
	<php:for $i = $iterator; $i < intval($y) . callFuncXX (asd, floatVal(123), array(1,2,asd)); $i++></php:for>
		for ($i = $iterator ; $i < intval($y) . callFuncXX("asd", floatVal(123), array(1, 2, "asd")) ; $i++) {

	<php:for $i = 0 $i < (intval($y) callFuncXX (asd, floatVal(123), array(1,2,asd))) $i++></php:for>
		for ($i = 0; $i < (intval($y) . callFuncXX("asd", floatVal(123), array(1, 2, "asd"))); $i++) {

	<php:foreach $arr $item></php:foreach>
		foreach ($arr as $item) {

	<php:foreach array((arr jp) 12) k $item></php:foreach>
		foreach (array(("arr" . "jp"), 12) as $k => $item) {

	<php:foreach callFuncXX (asd, floatVal(123)) k item></php:foreach>
		foreach (callFuncXX("asd", floatVal(123)) as $k => $item) {
	
	<php:include $path /asd/qwe/$asd/as12s.php>
		include $path . "/asd/qwe/" . $asd . "/as12sphp";

	<php:include $path \'/asd/qwe/$asd/as12s.php\'>
		include $path . '/asd/qwe/$asd/as12s.php';

	<php:switch $name>
		switch ($name) {

	<php:case joao>
		case "joao":

	<php:default>
		default:

	<php:try>
		try {

	<php:catch MyException $exc>
		} catch (MyException $exc) {
	
	<php:catch MyException e>
		} catch (MyException $e) {
	
	<php:throw:Exception asdasd 123>
		throw new Exception("asdasd", 123);	
		
	<php:throw $e>
		throw $e;

	<php:throw new Exception("asdasd", 123)>
		throw new Exception("asdasd", 123);
		
	<php:throw foo(new Exception("asdasd", 123))>
		throw foo(new Exception("asdasd", 123));
		
		Note that you must add quotes otherwise it won't work for this case:
			<php:throw new Exception(asdasd, 123)>
				throw new Exception(asdasd, 123);
				asdasd must be inside of quotes
				Or you can simply call this instead: <php:throw:Exception asdasd 123>
	
	<php:class:MyClass:extends:CommonClass>
		class MyClass extends CommonClass {

	<php:function:public:static:bar $x $y = 0>
		public static function bar($x, $y = 0) {
	
	<php:function:foo x y = 0>
		function foo($x, $y = 0) {

	<php:function:foo $x $y = 0>
		function foo($x, $y = 0) {

	<php:code $i = 0 * 2; $x = "asd"; $obj = new Foo(); return $i;>
		$i = 0 * 2; $x = "asd"; $obj = new Foo(); return $i;
	
	<php:code $obj-&gt;foo = 123;>
		$obj->foo = 123;
		
		In this case, use <php:code> instead of <php:var>
			<php:var:obj-&gt;foo 123>
	
	<php:code $obj-&gt;foo = $bar-&gt;xxx();>
		$obj->foo = $bar->xxx();
		
		In this case, use <php:code> instead of <php:var>
			<php:var:obj::foo $bar-&gt;xxx()>
	
	</php:if>
	</php:elseif>
	</php:for>
	</php:foreach>
	</php:switch>
	</php:try>
	</php:class>
	</php:function>
		}
	
	<ptl:var:PaginationLayout new PaginationLayout(0, 50, array("current_page" =&gt; 0), "current_page") />
		$PaginationLayout = new PaginationLayout(0, 50, array("current_page" => 0), "current_page");	
*/
class PHPTemplateLanguage {
	const CACHE_DIR_NAME = "ptl/";
	
	private $CacheHandler;
	private $code_to_execute;
	
	public function setCacheHandler(IUserCacheHandler $CacheHandler) {
		$this->CacheHandler = $CacheHandler;
		$this->CacheHandler->config(false, false); //Disables serialize option
	}
	public function getCacheHandler() { return $this->CacheHandler; }
   
	public function parseTemplate($template, $input_vars = false, $encoding = false) {
		$html = null;
		
		try {
			$ignore_undefined_vars_errors = !empty($GLOBALS["ignore_undefined_vars_errors"]);
			
			if ($ignore_undefined_vars_errors)
				set_error_handler(function($errno, $errstr, $errfile, $errline) {
					$status = ignore_undefined_var_error_handler($errno, $errstr, $errfile, $errline); //global funciton defined in lib/org/phpframework/app.php
					
					debug_log("[PHPTemplateLanguage->parseTemplate] WARNING [$errno] $errstr on line $errline in file $errfile when tried to execute the following code:\n" . $this->code_to_execute, "debug");
					
					return $status;
				}, E_WARNING);
			
			//echo "$template\n";
			$code = $this->getTemplateCode($template, $encoding);
			//echo "<textarea>$code</textarea>";die();
			//print_r($input_vars);
			//echo "$code\n";
			
			if ($code) {
				$this->code_to_execute = $code;
				
				if ($input_vars)
					foreach ($input_vars as $name => $value)
						if ($name)
							${$name} = $value;
				
				//error_log($code . "\n\n", 3, $GLOBALS["log_file_path"] ? $GLOBALS["log_file_path"] : "/var/www/html/livingroop/default/tmp/phpframework.log");
				//debug_log_function("DB->getData", array($sql)); // Do not overload the logs
				
				/*	Do not call here the 
						PHPScriptHandler::isValidPHPCode($code, $error_message)
					because if the code contains any function or class creation, the code will break in the eval code bellow, bc it will try to create the function or class again, which will give a duplicate error.
					
					I tried to catch the ParseError like this one and it is not possible when execute EVAL. The php site says it possible with php 7, but still it doesn't work!
				*/
				ob_start(null, 0);
				
				eval($code); //Do not add the @eval, bc if there is an error php will stop and no errors will be shown. PHP ERRORS MUST BE SHOWN so the programmer knows what is happening.
				
				$html = ob_get_contents();
				ob_end_clean();
			}
		}
        	catch (Error $e) {
			launch_exception(new PHPTemplateLanguageException(7, $code, new Exception("PHP error: " . $e->getMessage())));
        	}
		catch(ParseError $e) {
			launch_exception(new PHPTemplateLanguageException(7, $code, new Exception("Parse error: " . $e->getMessage())));
		}
		catch(ErrorException $e) {
			launch_exception(new PHPTemplateLanguageException(7, $code, new Exception("Error exception: " . $e->getMessage())));
		}
		catch(Exception $e) {
			launch_exception(new PHPTemplateLanguageException(7, $code, $e));
		}
		
		if ($ignore_undefined_vars_errors)
			restore_error_handler();
		
		return $html;
	}
	
	public function getTemplateCode($template, $encoding = false) {
		$cache_key = self::CACHE_DIR_NAME . md5($template . $encoding);
		
		if ($this->CacheHandler && $this->CacheHandler->isValid($cache_key))
			return $this->CacheHandler->read($cache_key);
		
		if ($encoding)
			$template = mb_convert_encoding($template, 'HTML-ENTITIES', $encoding);
		
		//Remove php code comments, this is, remove "<!--php:xxx yyy>... -->", etc...
		$template = preg_replace('/<!--(php|ptl|\?):(.*?)-->/si', "", $template);
		
		$offset = 0;
		$length = strlen($template);
		$odq = $osq = false;
		$code_lines = array();
		$line_prefix = '';
		
		do {
			//preg_match('/<\/?(php|ptl|\?):([a-z0-9][a-z0-9\-\_\:\[\]"\'\(\)]*)/i', $template, $matches, PREG_OFFSET_CAPTURE, $offset);
			preg_match('/<\/?(php|ptl|\?):(\w)/iu', $template, $matches, PREG_OFFSET_CAPTURE, $offset); //'\w' means all words with '_' and '/u' means with accents and ç too.
			//print_r($matches);
			
			if ($matches) {
				$start = $matches[2][1];
				$end = $length;
				$tag = $matches[0][0];
				$tag_name = $tag_code = "";
				$tag_name_finished = false;
				
				//init $tag, $tag_name and $tag_code
				for ($i = $start; $i < $length; $i++) {
					$char = $template[$i];
					
					if ($i > $start) //skip first char bc it is already in the $tag variable
						$tag .= $char;
					
					if ($char == '"' && !$osq && !TextSanitizer::isCharEscaped($template, $i))
						$odq = !$odq;
					else if ($char == "'" && !$odq && !TextSanitizer::isCharEscaped($template, $i))
						$osq = !$osq;
					else if ($char == " " && !$odq && !$osq)
						$tag_name_finished = true;
					else if ($char == ">" && !$odq && !$osq) {
						$end = $i;
						break;
					}
					
					if (!$tag_name_finished)
						$tag_name .= $char;
					else
						$tag_code .= $char;
				}
				
				if ($tag_name) {
					//preparing $tag_name
					$is_closing_tag = substr($matches[0][0], 0, 2) == "</";
					$tag_name = ($is_closing_tag ? "/" : "") . $tag_name; //add slash to the begining of $tag_name, like: "/if"
					$tag_name = substr($tag_name, -1) == "/" ? substr($tag_name, 0, -1) : $tag_name; //remove slash from "/>"
					
					//preparing $tag_code
					if ($is_closing_tag)
						$tag_code = ""; //if is a closing tag, clean $tag_code
					else {
						$tag_code = substr($tag_code, 1); //$tag_code will always start with a space which is unnecessary
						$tag_code = substr($tag_code, -1) == "/" ? substr($tag_code, 0, -1) : $tag_code; //remove slash from "/>"
					}
					
					//In case of: <ptl:var:asd-&gt;xxx 123 />, converts to asd->xxx
					if (strpos($tag_name, "&gt;") !== false)
						$tag_name = str_replace("&gt;", ">", $tag_name);
					
					//In case of: <ptl:var:asd[xxx] 123 />, add quotes to ["xxx"]
					if (strpos($tag_name, "[") !== false)
						$tag_name = str_replace(']', '"]', str_replace('[', '["', str_replace(array('"', "'"), '', $tag_name)));
					
					//preparing previous code
					$c = substr($template, $offset, $matches[0][1] - $offset);
					$is_c = strlen( str_replace(array("\t", "\r\n", "\n"), '', $c) ); //Note that I cannot remove the \n (end lines) from $c, bc if there is a javascript code inside with some // (comments), removing the \n will mess the javascript code...
					
					if ($is_c)
						$code_lines[] = array("echo '" . addcslashes($c, "\\'") . "';", $line_prefix);
					
					//preparing tag code 
					$pc = trim( $this->parseTag($tag, $tag_name, $tag_code) );
					if (strlen($pc)) {
						if (substr($pc, 0, 1) == "}") //closing some tag like /if
							$line_prefix = substr($line_prefix, 0, -1); //remove one tab (\t)
						
						$code_lines[] = array($pc, $line_prefix);
						
						if (substr($pc, -1) == "{") //openning some tag like: if
							$line_prefix .= "\t"; //add one tab (\t)
					}
					//echo "$start|$offset|$end|$tag_name||||$tag||||$c||||$pc\n";
				}
				else 
					launch_exception(new PHPTemplateLanguageException(1, $tag));
				
				$offset = $end + 1;
			}
			else {
				$c = substr($template, $offset, $length);
				$is_c = strlen( str_replace(array("\t", "\r\n", "\n"), '', $c) ); //Note that I cannot remove the \n (end lines) from $c, bc if there is a javascript code inside with some // (comments), removing the \n will mess the javascript code...
				
				if ($is_c)
					$code_lines[] = array("echo '" . addcslashes($c, "\\'") . "';", $line_prefix);
			}
		} 
		while ($matches && $offset < $length);
		
		//print_r($code_lines);
		//For testing purposes only, you can comment this code
		//join multiple echo commands into just 1.
		$t = count($code_lines);
		for ($i = 0; $i < $t; $i++) {
			$line = $code_lines[$i][0];
			$prev_line = $i > 0 ? $code_lines[$i - 1][0] : null;
			
			if ($prev_line && substr($line, 0, 5) == "echo " && substr($prev_line, 0, 5) == "echo ") {
				$code_lines[$i][0] = substr($prev_line, 0, -1) . " . " . substr($line, 5); //removes ; from $prev_line
				
				$code_lines[$i - 1] = null;
				unset($code_lines[$i - 1]);
			}
		}
		
		//implode $code_lines
		$code = "";
		foreach ($code_lines as $cl)
			if (strlen($cl[0]))
				$code .= $cl[1] . $cl[0] . "\n";
		
		if ($this->CacheHandler)
			$this->CacheHandler->write($cache_key, $code);
		
		return $code;
	}
	
	private function parseTag($tag, $tag_name, $tag_code) {
		//echo "tag:$tag\n";
		//echo "tag_name:$tag_name\n";
		//echo "tag_code:$tag_code\n";
		$extra_tag_name = null;
		
		$pos = strpos($tag_name, ":");
		if ($pos > 0) {
			$extra_tag_name = substr($tag_name, $pos + 1);
			$tag_name = substr($tag_name, 0, $pos);
		}
		
		$tag_name_lower = strtolower($tag_name);
		
		switch ($tag_name_lower) {
			case "definevar": //<php:definevar:name NAME>
				$this->prepareTagFuncArgs($tag_code);
				
				if (!self::isString($tag_code) && strlen($tag_code) > 2) //must be a string and not an empty string
					launch_exception(new PHPTemplateLanguageException(2, array($tag, $tag_code)));
				
				//return '$' . $extra_tag_name . " = constant(" . $tag_code . ");";
				return '$' . $extra_tag_name . " = " . substr($tag_code, 1, -1) . ";";
			case "var": //<php:var:name 123>
			case "incvar": //<php:incvar:name 123>
			case "decvar": //<php:decvar:name 123>
			case "joinvar": //<php:joinvar:name asd>
			case "concatvar": //<php:concatvar:name asd>
				$this->prepareTagFuncArgs($tag_code);
				$tag_code = strlen($tag_code) ? $tag_code : '""';
				
				//in case of Obj::xxx = 123
				$extra_extra_tag_name = "";
				$pos = strpos($extra_tag_name, "::");
				if ($pos) {
					$extra_extra_tag_name = substr($extra_tag_name, $pos);
					$extra_tag_name = substr($extra_tag_name, 0, $pos);
				}
				
				$operator = "=";
				switch ($tag_name_lower) {
					case "incvar": $operator = "+="; break;
					case "decvar": $operator = "-="; break;
					case "joinvar": 
					case "concatvar": 
						$operator = ".="; break;
				}
				
				$parts = explode(":", $extra_tag_name);
				$name = array_pop($parts) . $extra_extra_tag_name;
				return implode(" ", $parts) . " " . (!in_array("const", array_map("strtolower", $parts)) && !$pos ? '$' : '') . $name . " $operator " . $tag_code . ";";
				
				//TODO Add <php:var:xxx> ... big html code here ... </php:var>
			case "for":
				$delimiters = array(";" => ";");
				$this->prepareTagFuncArgs($tag_code, "; ", $delimiters);
				return "for (" . $tag_code . ") {";
			case "foreach":
				$arguments = $this->prepareTagFuncArgs($tag_code, ","); //is very important to have "," otherwise it will merge the arguments[1] and $argument[2].
				$size = count($arguments);
				
				if ($size >= 2 && $size <= 3) {
					if (self::isString($arguments[0]) || is_numeric($arguments[0]))
						launch_exception(new PHPTemplateLanguageException(5, array($tag, $arguments[0], "1st")));
					
					if (self::isString($arguments[1]))
						$arguments[1] = '$' . substr($arguments[1], 1, -1);
					else if (strlen($arguments[1]) == 0 || $arguments[1][0] != '$')
						launch_exception(new PHPTemplateLanguageException(4, array($tag, $arguments[1], "2nd")));
					
					if (!empty($arguments[2])) {
						if (self::isString($arguments[2]))
							$arguments[2] = '$' . substr($arguments[2], 1, -1);
						else if (strlen($arguments[2]) == 0 || $arguments[2][0] != '$')
							launch_exception(new PHPTemplateLanguageException(4, array($tag, $arguments[2], "3rd")));
					}
					
					return "foreach (" . $arguments[0] . " as " . ($size == 3 ? $arguments[1] . " => " . $arguments[2] : $arguments[1]) . ") {";
				}
				else 
					launch_exception(new PHPTemplateLanguageException(6, $tag));
			case "if":
				$this->prepareTagFuncArgs($tag_code);
				return "if (" . $tag_code . ") {";
			case "elseif":
				$this->prepareTagFuncArgs($tag_code);
				return "} else if (" . $tag_code . ") {";
			case "else":
				return "} else {";
			case "echo":
			case "print":
			case "return":
				$this->prepareTagFuncArgs($tag_code);
				return strlen($tag_code) ? "$tag_name_lower " . $tag_code . ";" : "";
			case "break":
				return "break;";
			case "die":
				$this->prepareTagFuncArgs($tag_code);
				return "die(" . $tag_code . ");";
			case "require":
			case "include":
			case "include_once":
			case "require_once":
				$this->prepareTagFuncArgs($tag_code);
				return "$tag_name_lower " . $tag_code . ";";
			case "switch":
				$this->prepareTagFuncArgs($tag_code);
				return "switch (" . $tag_code . ") {";
			case "case":
				$this->prepareTagFuncArgs($tag_code);
				
				if (self::isString($tag_code) || substr($tag_code, 0, 1) == '$' || substr($tag_code, 0, 2) == '@$')
					return "case " . $tag_code . ":";
				else
					launch_exception(new PHPTemplateLanguageException(4, array($tag, $tag_code)));
			case "default":
				return "default:";
			case "try":
				return "try {";
			case "catch":
				$arguments = $this->prepareTagFuncArgs($tag_code, ","); //is very important to have "," otherwise it will merge the arguments[1] and $argument[2].
				$size = count($arguments);
				
				if ($size > 1 && self::isString($arguments[1])) 
					$arguments[1] = '$' . substr($arguments[1], 1, -1);
				
				if (empty($arguments)) 
					$arguments = array('"Exception"', '$e');
				else if (!self::isString($arguments[0])) //first argument must be a class name
					launch_exception(new PHPTemplateLanguageException(2, array($tag, $arguments[0], "1st")));
				else if ($size <= 1 || strlen($arguments[1]) == 0 || $arguments[1][0] != '$') //second argument argument must be a variable,
					launch_exception(new PHPTemplateLanguageException(3, array($tag, $arguments[1], "2nd")));
				
				return "} catch (" . substr($arguments[0], 1, -1) . " " . $arguments[1] . ") {";
			case "throw":
				if ($extra_tag_name) {
					$this->prepareTagFuncArgs($tag_code, ", ");
					return  "throw new " . $extra_tag_name . "(" . $tag_code . ");";
				}
				
				return "throw " . $tag_code . ";";
			case "class":
				return "class " . implode(" ", explode(":", $extra_tag_name)) . " {";
			case "function":
				$arguments = $this->prepareTagFuncArgs($tag_code, ",");
				
				//converts string arguments in variables
				$tag_code = '';
				$t = count($arguments);
				for ($i = 0; $i < $t; $i++) {
					$arg = $arguments[$i];
					
					if ($arg == "=") {
						$tag_code .= " = " . ($i + 1 < $t ? $arguments[$i + 1] : null);
						$i++;
					}
					else
						$tag_code .= ($tag_code ? ", " : "") . (self::isString($arg) ? '$' . substr($arg, 1, -1) : $arg);
				}
				
				$parts = explode(":", $extra_tag_name);
				$name = array_pop($parts);
				return implode(" ", $parts) . " function " . $name . "(" . $tag_code . ") {";
			case "code"://write php code directly
				return str_replace("&gt;", ">", $tag_code);
				
			case "/for":
			case "/foreach":
			case "/if":
			case "/switch":
			case "/try":
			case "/class":
			case "/function":
				return "}";
			
			default:
				if (substr($tag_name, 0, 1) == "/") //for cases that don't matter, like: /else /elseif /case /echo
					return "";
				
				$this->prepareTagFuncArgs($tag_code, ", ", array("," => ","));
				return "$tag_name (" . $tag_code . ");";
		}
	}
	
	//This method might have some issues with the unicode chars. Maybe I should change it with the TextSanitizer::isMBCharEscaped and TextSanitizer::mbStrSplit
	private function prepareTagFuncArgs(&$tag_code, $join = " . ", $extra_delimiters = null) {
		$arguments = array();
		
		if ($tag_code) {
			//prepaer delimiters
			$delimiters = array(
				"." => ".",
				"+" => "+", 
				"-" => array("->", "-&gt;", "-"), //-> or -&gt; in case of being a $obj->prop
				"*" => "*", 
				"/" => "/", 
				"%" => "%", 
				"=" => array("===", "==", "=>", "=&gt;", "="), //=> or =&gt; in case of being an associative array
				"!" => array("!==", "!=", "!"), 
				"<" => array("<==", "<=", "<"),  
				">" => array(">==", ">=", ">"), 
				"&" => array("&&", "&gt;==", "&gt;=", "&gt;", "&lt;==", "&lt;=", "&lt;"),
				"|" => "||",
				"?" => "?", // if something ? do foo : else do bar
				":" => ":", // if something ? do foo : else do bar
			);
			
			$numeric_only_delimiters = array("+", "-", "*", "/", "%", ">==", ">=", ">", "<==", "<=", "<", "&gt;==", "&gt;=", "&gt;", "&lt;==", "&lt;=", "&lt;");
			
			//add extra delimiters to $delimiters var
			if ($extra_delimiters) 
				foreach ($extra_delimiters as $k => $v)
					if (isset($delimiters[$k])) {
						if (!is_array($delimiters[$k]))
							$delimiters[$k] = array($delimiters[$k]);
						
						if (is_array($v))
							$delimiters[$k] = array_merge($delimiters[$k], $v);
						else
							$delimiters[$k][] = $v;
					}
					else
						$delimiters[$k] = $v;
		
			$delimiters_chars = array_keys($delimiters);
		
			//Prepare reserved words
			$reserved_keywords = array("new");
			
			//Parse code
			$start = 0;
			$odq = $osq = false;
			$tag_code_chars = TextSanitizer::mbStrSplit($tag_code);
			$length = count($tag_code_chars);
			
			for ($i = $start; $i < $length; $i++) {
				$char = $tag_code_chars[$i];
				$next_char = $i + 1 < $length ? $tag_code_chars[$i + 1] : null;
				//echo "char:$char|".count($arguments)."\n";
				
				if ($char == '"' && !$osq && !TextSanitizer::isMBCharEscaped($tag_code, $i, $tag_code_chars)) {
					$c = implode("", array_slice($tag_code_chars, $start, $i - $start));
					$c = !$odq ? trim($c) : $c;
					
					// || $odq because it could be an empty string like this: str_repalce("xx", "", $t))
					if (strlen($c) || $odq) { //strlen is faster thean mb_strlen and we only want to know if exists anything in $c
						$prev_str = count($arguments) > 0 ? $arguments[ count($arguments) - 1 ] : null;
						$arguments[] = !$odq && (in_array($c, $reserved_keywords) || $prev_str == "->" || $prev_str == "-&gt;") ? $c : '"' . $c . '"';
					}
					
					$start = $i + 1;
					$odq = !$odq;
				}
				else if ($char == "'" && !$odq && !TextSanitizer::isMBCharEscaped($tag_code, $i, $tag_code_chars)) {
					$c = implode("", array_slice($tag_code_chars, $start, $i - $start));
					$c = !$osq ? trim($c) : $c;
					
					// || $osq because it could be an empty string like this: str_repalce("xx", '', $t))
					if (strlen($c) || $osq) { //strlen is faster thean mb_strlen and we only want to know if exists anything in $c
						$prev_str = count($arguments) > 0 ? $arguments[ count($arguments) - 1 ] : null;
						$arguments[] = !$osq && (in_array($c, $reserved_keywords) || $prev_str == "->" || $prev_str == "-&gt;") ? $c : "'" . $c . "'";
					}
					
					$start = $i + 1;
					$osq = !$osq;
				}
				else if (!$odq && !$osq) {
					if (($char == " " || $char == "," || $char == '"' || $char == "'" || $char == '$' || ($char == "@" && $next_char == '$')) && !TextSanitizer::isMBCharEscaped($tag_code, $i, $tag_code_chars)) { //check spaces and , (, is for the function args)
						//echo "enter space before ($char):";print_r($arguments);
						$c = trim(implode("", array_slice($tag_code_chars, $start, $i - $start)));
						
						if (strlen($c)) { //strlen is faster thean mb_strlen and we only want to know if exists anything in $c
							$prev_str = count($arguments) > 0 ? $arguments[ count($arguments) - 1 ] : null;
							$arguments[] = in_array($c, $reserved_keywords) || $prev_str == "->" || $prev_str == "-&gt;" ? $c : self::configureArg($c, $char == "'");
						}
						
						if (self::isDelimiter($char, $delimiters)) //in case of $char=="," and exists in the extra_delimiters, adds it to $arguments, so we know easily to diferenciate the multiple function arguments
							$arguments[] = $char;
						
						if ($char == '@') {
							$start = $i;
							$i = $i + 1;
						}
						else
							$start = $char == '$' ? $i : $i + 1;
						//echo "enter space after ($char):";print_r($arguments);
					}
					else if (in_array($char, $delimiters_chars)) { //check delimiters: conditions in if, for, elseif, echo, var...
						//echo "enter delimiter before ($char):";print_r($arguments);
						$delimiter = $delimiters[$char];
						$arr = is_array($delimiter) ? $delimiter : array($delimiter);
						$next_str = implode("", array_slice($tag_code_chars, $i));
						
						foreach ($arr as $item) {
							$regex = preg_replace("/([\.\+\-\*\/\%\(\)\?])/i", '\\\\$1', $item);
							
							//strlen is faster thean mb_strlen and here its ok!
							$prev_str = implode("", array_slice($tag_code_chars, 0, $i + strlen($item))); 
							$str = implode("", array_slice($tag_code_chars, $i, strlen($item))); 
							
							if ($item && $item == $str) {
								$is_increment_pre = $is_increment_pos = false;
								$is_operator = $item == ".";//check if is string increment/joint operator
								
								if ($item == "->" || $item == "-&gt;") { //check if variable
									if (isset($tag_code_chars[0])) {
										if ($tag_code_chars[0] == '$')
											$is_operator = true;
										else if (isset($tag_code_chars[1]) && $tag_code_chars[0] == '@' && $tag_code_chars[1] == '$')
											$is_operator = true;
									}
								}
								else if (preg_match('/^' . $regex . '[ \w\$"\'\(]+/iu', $next_str) && ($item == "!" || preg_match('/[ \w"\'\)]+' . $regex . '$/iu', $prev_str))) //check if a math or conditional operator //'\w' means all words with '_' and '/u' means with accents and ç too.
									$is_operator = true;
								else if ($item == "+" || $item == "-") { //check if is increment operator: $i++ or --$i
									$is_increment_pre = preg_match('/^' . $regex . $regex . '\s*\$\w+/iu', $next_str); //'\w' means all words with '_' and '/u' means with accents and ç too.
									
									$str = implode("", array_slice($tag_code_chars, 0, $i + 2)); 
									$is_increment_pos = preg_match('/\$\w+\s*' . $regex . $regex . '$/iu', $str); //'\w' means all words with '_' and '/u' means with accents and ç too.
									$is_operator = $is_increment_pre || $is_increment_pos;
								}
								
								//add previous strings
								$c = implode("", array_slice($tag_code_chars, $start, $i - $start)); 
								//echo "c:$c\n";
								if (strlen($c))
									$arguments[] = self::configureArg($c);
								
								//add operators
								if ($is_increment_pre) {//++$i or --$i
									//echo "is_increment_pre:$item\n";
									$arguments[] = "$item$item";
									$start = $i + 2;
								}
								else if ($is_increment_pos) {//$i++ or $i--
									//echo "is_increment_pos:$item\n";
									$arguments[ count($arguments) - 1 ] = (count($arguments) > 0 ? $arguments[ count($arguments) - 1 ] : "") . "$item$item";
									$start = $i + 2; 
								}
								else {
									$del = implode("", array_slice($tag_code_chars, $i, strlen($item))); //strlen is faster thean mb_strlen and here its ok!
									$del = str_replace("&lt;", "<", str_replace("&gt;", ">", $item)); //str_replace is faster thean mb_str_replace and here its ok!
									$arguments[] = $is_operator ? $del : '"' . $del . '"';
									
									$start = $i + strlen($item); //strlen is faster thean mb_strlen and here its ok!
								}
								
								$i = $start - 1;
								break;
							}
						}
					
						//echo "$start: enter delimiter after ($char):";print_r($arguments);
					}
					else if ($char == '[') { //for these cases: $arr[$_GET[0]]$arr[ $_GET[$name ] ] or $arr[ $_GET[name ]]>
						//echo "enter [ before ($char):";print_r($arguments);
						$p = trim(implode("", array_slice($tag_code_chars, $start, $i - $start)));
						
						if (empty($arguments) || strlen($p))
							$previous = $p;
						else
							$previous = count($arguments) > 0 ? $arguments[ count($arguments) - 1 ] : null;
					
						$is_var = $previous && preg_match('/^@?\$\{?\w/iu', trim($previous)); //'\w' means all words with '_' and '/u' means with accents and ç too.
						//echo "previous($is_var):$previous\n";
						
						if ($is_var) {
							$count = 0;
							$sub_odq = $sub_osq = false;
							
							for ($j = $i + 1; $j < $length; $j++) {
								$char = $tag_code_chars[$j];
								
								if ($char == '"' && !$sub_osq && !TextSanitizer::isMBCharEscaped($tag_code, $j, $tag_code_chars))
									$sub_odq = !$sub_odq;
								else if ($char == "'" && !$sub_odq && !TextSanitizer::isMBCharEscaped($tag_code, $j, $tag_code_chars))
									$sub_osq = !$sub_osq;
								else if (!$sub_odq && !$sub_osq) {
									if ($char == "[")
										++$count;
									else if ($char == "]") {
										if ($count == 0) 
											break;
										--$count;
									}
								}
							}
							
							$sub_tag_code = implode("", array_slice($tag_code_chars, $i + 1, $j - ($i + 1)));
						 	$this->prepareTagFuncArgs($sub_tag_code);
						 			 	
							//convert [name] into ["name"]
							$sub_tag_code = preg_replace_callback('/\[([\$\w"\' \-\+\.]+)\]/u', function ($matches) { //'\w' means all words with '_' and '/u' means with accents and ç too.
								return "[" . (!self::isString($matches[1]) && !is_numeric($matches[1]) && $matches[1][0] != '$' && substr($matches[1], 0, 2) != '@$' ? '"' . trim($matches[1]) . '"' : $matches[1]) . "]";
							  }, $sub_tag_code);
							  
							if (empty($arguments) || strlen($p))
								$arguments[] = $previous . "[$sub_tag_code]";
							else 
								$arguments[ count($arguments) - 1 ] = $previous . "[$sub_tag_code]";
							
							$i = $j;
							$start = $j + 1;
						}
						//echo "enter [ after ($char):";print_r($arguments);
					}
					else if ($char == "(") { //check if func args
						//echo "enter ( before ($char):";print_r($arguments);
						$p = trim(implode("", array_slice($tag_code_chars, $start, $i - $start)));
						
						if (empty($arguments) || strlen($p))
							$previous = $p;
						else {
							$previous_chars = count($arguments) > 0 ? TextSanitizer::mbStrSplit($arguments[ count($arguments) - 1 ]) : array();
							$previous = implode("", array_slice($previous_chars, 1, -1));
						}
						
						// /\w+/ == /[a-zA-Z\_]+/
						$str = trim(implode("", array_slice($tag_code_chars, 0, $i)));
						$is_func = $previous && preg_match('/^\w+$/u', $previous) && preg_match('/[^"\']$/iu', $str); //'\w' means all words with '_' and '/u' means with accents and ç too.
						//echo "previous($is_func):$previous\n";
						
						if ($is_func) {
							$count = 0;
							$sub_odq = $sub_osq = false;
							
							for ($j = $i + 1; $j < $length; $j++) {
								$char = $tag_code_chars[$j];
								
								if ($char == '"' && !$sub_osq && !TextSanitizer::isMBCharEscaped($tag_code, $j, $tag_code_chars))
									$sub_odq = !$sub_odq;
								else if ($char == "'" && !$sub_odq && !TextSanitizer::isMBCharEscaped($tag_code, $j, $tag_code_chars))
									$sub_osq = !$sub_osq;
								else if (!$sub_odq && !$sub_osq) {
									if ($char == "(")
										++$count;
									else if ($char == ")") {
										if ($count == 0)
											break;
										
										--$count;
									}
								}
							}
				
							//echo "i:$i|j:$j|".substr($tag_code, $i, 1)."|".substr($tag_code, $j, 1)."\n";
							//echo "tag_code:$tag_code|".($i + 1)."|".($j - ($i + 1))."\n";
							//echo substr($tag_code, $i + 1)."!\n";
							//echo substr($tag_code, 0, $i)."!	\n";
							$sub_tag_code = implode("", array_slice($tag_code_chars, $i + 1, $j - ($i + 1)));
							//echo "sub_tag_code:$sub_tag_code|\n";
							$this->prepareTagFuncArgs($sub_tag_code, ", ", array("," => ","));
							
							if (empty($arguments) || strlen($p))
								$arguments[] = $previous . "($sub_tag_code)";
							else 
								$arguments[ count($arguments) - 1 ] = $previous . "($sub_tag_code)";
							
							$i = $j;
							$start = $j + 1;
						}
						else {
							if (strlen($p))
								$arguments[] = self::configureArg($p);
						
							$arguments[] = $char;
							$start = $i + 1;
							//echo "no func:$p";
						}
						//echo "enter ( after ($char):";print_r($arguments);
					}
					else if ($char == ")") {
						$p = implode("", array_slice($tag_code_chars, $start, $i - $start));
						if (strlen($p))
							$arguments[] = self::configureArg($p);
						//echo "p:$p\n";
					
						$arguments[] = $char;
						$start = $i + 1;
					}
				}
			}
			//print_r($arguments);
			
			self::prepareArgumentsWithQuotesThatAreNotReservedWords($arguments, $tag_code, $start, $length, $reserved_keywords);
			//print_r($arguments);
			
			self::prepareArgumentsWithNumericOperationsWithStrings($arguments, $numeric_only_delimiters);
			//print_r($arguments);
			
			self::prepareArgumentsWithShortIfCode($arguments, $join, $numeric_only_delimiters);
			//print_r($arguments);
			
			$join_trimmed = trim($join);
			self::prepareArgumentsWithUnnecessaryFollowedStrings($arguments, $join_trimmed);
			//echo "join_trimmed:$join_trimmed\n";
			//print_r($arguments);
			
			self::prepareArgumentsWithNullItems($arguments);
			//print_r($arguments);
			
			$tag_code = self::convertArgumentsToTagCode($arguments, $join, $delimiters, $reserved_keywords);
		}
		//echo "tag_code:$tag_code\n";
		
		self::prepareTagCodeWithLostQuote($tag_code);
		//echo "tag_code:$tag_code\n";
		
		return $arguments;
	}
	//This method is deprecated bc is a little bit more slow and don't allow unicode characters
	/*private function prepareTagFuncArgs(&$tag_code, $join = " . ", $extra_delimiters = null) {
		$arguments = array();
		
		if ($tag_code) {
			//prepaer delimiters
			$delimiters = array(
				"." => ".",
				"+" => "+", 
				"-" => array("->", "-&gt;", "-"), //-> or -&gt; in case of being a $obj->prop
				"*" => "*", 
				"/" => "/", 
				"%" => "%", 
				"=" => array("===", "==", "=>", "=&gt;", "="), //=> or =&gt; in case of being an associative array
				"!" => array("!==", "!=", "!"), 
				"<" => array("<==", "<=", "<"),  
				">" => array(">==", ">=", ">"), 
				"&" => array("&&", "&gt;==", "&gt;=", "&gt;", "&lt;==", "&lt;=", "&lt;"),
				"|" => "||",
				"?" => "?", // if something ? do foo : else do bar
				":" => ":", // if something ? do foo : else do bar
			);
			
			$numeric_only_delimiters = array("+", "-", "*", "/", "%", ">==", ">=", ">", "<==", "<=", "<", "&gt;==", "&gt;=", "&gt;", "&lt;==", "&lt;=", "&lt;");
			
			//add extra delimiters to $delimiters var
			if ($extra_delimiters) 
				foreach ($extra_delimiters as $k => $v)
					if (isset($delimiters[$k])) {
						if (!is_array($delimiters[$k]))
							$delimiters[$k] = array($delimiters[$k]);
						
						if (is_array($v))
							$delimiters[$k] = array_merge($delimiters[$k], $v);
						else
							$delimiters[$k][] = $v;
					}
					else
						$delimiters[$k] = $v;
		
			$delimiters_chars = array_keys($delimiters);
		
			//Parse code
			$start = 0;
			$length = strlen($tag_code);
			$odq = $osq = false;
			$reserved_keywords = array("new");
			
			for ($i = $start; $i < $length; $i++) {
				$char = $tag_code[$i];
				//echo "char:$char|".count($arguments)."\n";
				
				if ($char == '"' && !$osq && !TextSanitizer::isCharEscaped($tag_code, $i)) {
					$c = substr($tag_code, $start, $i - $start);
					$c = !$odq ? trim($c) : $c;
					if (strlen($c) || $odq) { // || $odq because it could be an empty string like this: str_repalce("xx", "", $t))
						$prev_str = $arguments[ count($arguments) - 1 ];
						$arguments[] = !$odq && (in_array($c, $reserved_keywords) || $prev_str == "->" || $prev_str == "-&gt;") ? $c : '"' . $c . '"';
					}
					
					$start = $i + 1;
					$odq = !$odq;
				}
				else if ($char == "'" && !$odq && !TextSanitizer::isCharEscaped($tag_code, $i)) {
					$c = substr($tag_code, $start, $i - $start);
					$c = !$osq ? trim($c) : $c;
					if (strlen($c) || $osq) { // || $osq because it could be an empty string like this: str_repalce("xx", '', $t))
						$prev_str = $arguments[ count($arguments) - 1 ];
						$arguments[] = !$osq && (in_array($c, $reserved_keywords) || $prev_str == "->" || $prev_str == "-&gt;") ? $c : "'" . $c . "'";
					}
					
					$start = $i + 1;
					$osq = !$osq;
				}
				else if (!$odq && !$osq) {
					if (($char == " " || $char == "," || $char == '"' || $char == "'" || $char == '$') && !TextSanitizer::isCharEscaped($tag_code, $i)) { //check spaces and , (, is for the function args)
						//echo "enter space before ($char):";print_r($arguments);
						$c = trim(substr($tag_code, $start, $i - $start));
						if (strlen($c)) {
							$prev_str = $arguments[ count($arguments) - 1 ];
							$arguments[] = in_array($c, $reserved_keywords) || $prev_str == "->" || $prev_str == "-&gt;" ? $c : self::configureArg($c, $char == "'");
						}
						
						if (self::isDelimiter($char, $delimiters)) //in case of $char=="," and exists in the extra_delimiters, adds it to $arguments, so we know easily to diferenciate the multiple function arguments
							$arguments[] = $char;
						
						$start = $char == '$' ? $i : $i + 1;
						//echo "enter space after ($char):";print_r($arguments);
					}
					else if (in_array($char, $delimiters_chars)) { //check delimiters: conditions in if, for, elseif, echo, var...
						//echo "enter delimiter before ($char):";print_r($arguments);
						$delimiter = $delimiters[$char];
						$arr = is_array($delimiter) ? $delimiter : array($delimiter);
						$next_str = substr($tag_code, $i);
						
						foreach ($arr as $item) {
							$regex = preg_replace("/([\.\+\-\*\/\%\(\)\?])/i", '\\\\$1', $item);
							$prev_str = substr($tag_code, 0, $i + strlen($item));
							
							if ($item && $item == substr($next_str, 0, strlen($item))) {
								$is_increment_pre = $is_increment_pos = false;
								$is_operator = $item == ".";//check if is string increment/joint operator
								
								if ($item == "->" || $item == "-&gt;") //check if variable
									$is_operator = substr($prev_str, 0, 1) == '$';
								else if (preg_match('/^' . $regex . '[ \w\$"\'\(]+/iu', $next_str) && ($item == "!" || preg_match('/[ \w"\'\)]+' . $regex . '$/iu', $prev_str))) //check if a math or conditional operator //'\w' means all words with '_' and '/u' means with accents and ç too.
									$is_operator = true;
								else if ($item == "+" || $item == "-") { //check if is increment operator: $i++ or --$i
									$is_increment_pre = preg_match('/^' . $regex . $regex . '\s*\$\w+/iu', $next_str); //'\w' means all words with '_' and '/u' means with accents and ç too.
									$is_increment_pos = preg_match('/\$\w+\s*' . $regex . $regex . '$/iu', substr($tag_code, 0, $i + 2)); //'\w' means all words with '_' and '/u' means with accents and ç too.
									$is_operator = $is_increment_pre || $is_increment_pos;
								}
								
								//add previous strings
								$c = trim(substr($tag_code, $start, $i - $start));
								//echo "c:$c\n";
								if (strlen($c))
									$arguments[] = self::configureArg($c);
								
								//add operators
								if ($is_increment_pre) {//++$i or --$i
									//echo "is_increment_pre:$item\n";
									$arguments[] = "$item$item";
									$start = $i + 2;
								}
								else if ($is_increment_pos) {//$i++ or $i--
									//echo "is_increment_pos:$item\n";
									$arguments[ count($arguments) - 1 ] = $arguments[ count($arguments) - 1 ] . "$item$item";
									$start = $i + 2; 
								}
								else {
									$del = substr($tag_code, $i, strlen($item));
									$del = str_replace("&lt;", "<", str_replace("&gt;", ">", $item));
									$arguments[] = $is_operator ? $del : '"' . $del . '"';
								
									$start = $i + strlen($item);
								}
								
								$i = $start - 1;
								break;
							}
						}
					
						//echo "$start: enter delimiter after ($char):";print_r($arguments);
					}
					else if ($char == '[') { //for these cases: $arr[$_GET[0]]$arr[ $_GET[$name ] ] or $arr[ $_GET[name ]]>
						//echo "enter [ before ($char):";print_r($arguments);
						$p = trim(substr($tag_code, $start, $i - $start));
				
						if (empty($arguments) || strlen($p))
							$previous = $p;
						else
							$previous = $arguments[ count($arguments) - 1 ];
					
						$is_var = $previous && preg_match('/^@?\$\{?\w/iu', trim($previous)); //'\w' means all words with '_' and '/u' means with accents and ç too.
						//echo "previous($is_var):$previous\n";
						
						if ($is_var) {
							$count = 0;
							$sub_odq = $sub_osq = false;
							
							for ($j = $i + 1; $j < $length; $j++) {
								$char = $tag_code[$j];
								
								if ($char == '"' && !$sub_osq && !TextSanitizer::isCharEscaped($tag_code, $j))
									$sub_odq = !$sub_odq;
								else if ($char == "'" && !$sub_odq && !TextSanitizer::isCharEscaped($tag_code, $j))
									$sub_osq = !$sub_osq;
								else if (!$sub_odq && !$sub_osq) {
									if ($char == "[")
										++$count;
									else if ($char == "]") {
										if ($count == 0) 
											break;
										--$count;
									}
								}
							}
							
							$sub_tag_code = substr($tag_code, $i + 1, $j - ($i + 1));
						 	$this->prepareTagFuncArgs($sub_tag_code);
						 			 	
							//convert [name] into ["name"]
							$sub_tag_code = preg_replace_callback("/\[([\w\"' \-\+\.]+)\]/u", function ($matches) { //'\w' means all words with '_' and '/u' means with accents and ç too.
								return "[" . (!self::isString($matches[1]) && !is_numeric($matches[1]) && substr($matches[1], 0, 1) != '$' ? '"' . trim($matches[1]) . '"' : $matches[1]) . "]";
							  }, $sub_tag_code);
							  
							if (empty($arguments) || strlen($p))
								$arguments[] = $previous . "[$sub_tag_code]";
							else 
								$arguments[ count($arguments) - 1 ] = $previous . "[$sub_tag_code]";
							
							$i = $j;
							$start = $j + 1;
						}
						//echo "enter [ after ($char):";print_r($arguments);
					}
					else if ($char == "(") { //check if func args
						//echo "enter ( before ($char):";print_r($arguments);
						$p = trim(substr($tag_code, $start, $i - $start));
				
						if (empty($arguments) || strlen($p))
							$previous = $p;
						else
							$previous = substr($arguments[ count($arguments) - 1 ], 1, -1);
						
						// /\w+/ == /[a-zA-Z\_]+/
						$is_func = $previous && preg_match('/^\w+$/u', $previous) && preg_match('/[^"\']$/iu', trim(substr($tag_code, 0, $i))); //'\w' means all words with '_' and '/u' means with accents and ç too.
						//echo "previous($is_func):$previous\n";
						
						if ($is_func) {
							$count = 0;
							$sub_odq = $sub_osq = false;
							
							for ($j = $i + 1; $j < $length; $j++) {
								$char = $tag_code[$j];
								
								if ($char == '"' && !$sub_osq && !TextSanitizer::isCharEscaped($tag_code, $j))
									$sub_odq = !$sub_odq;
								else if ($char == "'" && !$sub_odq && !TextSanitizer::isCharEscaped($tag_code, $j))
									$sub_osq = !$sub_osq;
								else if (!$sub_odq && !$sub_osq) {
									if ($char == "(")
										++$count;
									else if ($char == ")") {
										if ($count == 0)
											break;
										
										--$count;
									}
								}
							}
				
							//echo "i:$i|j:$j|".substr($tag_code, $i, 1)."|".substr($tag_code, $j, 1)."\n";
							//echo "tag_code:$tag_code|".($i + 1)."|".($j - ($i + 1))."\n";
							//echo substr($tag_code, $i + 1)."!\n";
							//echo substr($tag_code, 0, $i)."!	\n";
							$sub_tag_code = substr($tag_code, $i + 1, $j - ($i + 1));
							//echo "sub_tag_code:$sub_tag_code|\n";
							$this->prepareTagFuncArgs($sub_tag_code, ", ", array("," => ","));
							
							if (empty($arguments) || strlen($p))
								$arguments[] = $previous . "($sub_tag_code)";
							else 
								$arguments[ count($arguments) - 1 ] = $previous . "($sub_tag_code)";
							
							$i = $j;
							$start = $j + 1;
						}
						else {
							if (strlen($p))
								$arguments[] = self::configureArg($p);
						
							$arguments[] = $char;
							$start = $i + 1;
							//echo "no func:$p";
						}
						//echo "enter ( after ($char):";print_r($arguments);
					}
					else if ($char == ")") {
						$p = trim(substr($tag_code, $start, $i - $start));
						if (strlen($p))
							$arguments[] = self::configureArg($p);
						//echo "p:$p\n";
					
						$arguments[] = $char;
						$start = $i + 1;
					}
				}
			}
			//print_r($arguments);
			
			self::prepareArgumentsWithQuotesThatAreNotReservedWords($arguments, $tag_code, $start, $length, $reserved_keywords);
			//print_r($arguments);
			
			self::prepareArgumentsWithNumericOperationsWithStrings($arguments, $numeric_only_delimiters);
			//print_r($arguments);
			
			self::prepareArgumentsWithShortIfCode($arguments, $join, $numeric_only_delimiters);
			//print_r($arguments);
			
			self::prepareArgumentsWithUnnecessaryFollowedStrings($arguments, $join_trimmed);
			//echo "join_trimmed:$join_trimmed\n";
			//print_r($arguments);
			
			self::prepareArgumentsWithNullItems($arguments);
			//print_r($arguments);
			
			$tag_code = self::convertArgumentsToTagCode($arguments, $join, $delimiters, $reserved_keywords);
		}
		//echo "tag_code:$tag_code\n";
		
		self::prepareTagCodeWithLostQuote($tag_code);
		//echo "tag_code:$tag_code\n";
		
		return $arguments;
	}*/
	
	//check if there are args which are strings, not reserved and don't have quotes and add quotes...
	public static function prepareArgumentsWithQuotesThatAreNotReservedWords(&$arguments, $tag_code, $start, $length, $reserved_keywords) {
		if ($start < $length) {
			$c = trim(substr($tag_code, $start, $length - $start));
			
			if (strlen($c)) {
				$prev_str = count($arguments) > 0 ? $arguments[ count($arguments) - 1 ] : null;
				$arguments[] = in_array($c, $reserved_keywords) || $prev_str == "->" || $prev_str == "-&gt;" ? $c : self::configureArg($c);
			}
		}
	}
	
	//Checks if the char is a numeric_delimiter and if prev and next strings are numeric. If not add quotes around it.
	private static function prepareArgumentsWithNumericOperationsWithStrings(&$arguments, $numeric_only_delimiters) {
		$t = count($arguments);
		
		for ($i = 0; $i < $t; $i++) {
			$arg = $arguments[$i];
			$prev_arg = $i > 0 ? $arguments[$i - 1] : null;
			$next_arg = $i + 1 < $t ? $arguments[$i + 1] : null;
			
			if (in_array($arg, $numeric_only_delimiters) && (self::isString($prev_arg) || self::isString($next_arg)))
				$arguments[$i] = '"' . $arg . '"';
		}
	}
	
	//checks if exists "shor if codes" and prepare arguments accordingly
	private static function prepareArgumentsWithShortIfCode(&$arguments, $join, $numeric_only_delimiters) {
		$join_trimmed = trim($join);
		
		//Checks if exists "short if code" (something like: condition ? true : false), and if yes, checks if exists any empty string missing and if so, add it.
		$exists_question_mark = false;
		$t = count($arguments);
		
		for ($i = 0; $i < $t; $i++) {
			$arg = $arguments[$i];
			
			if ($arg == "?")
				$exists_question_mark = true;
			else if ($exists_question_mark && $arg == ":") {
				$prev_arg = $i > 0 ? $arguments[$i - 1] : null;
				$next_arg = $i + 1 < $t ? $arguments[$i + 1] : null;
				
				if ($prev_arg == "?") { //empty string missing, so add it
					for ($j = $t; $j >= $i; $j--) //pushing all elements forward
						$arguments[$j] = $arguments[$j - 1];
					
					$arguments[$i] = '""';
					$t = count($arguments);
					$i++;
				}
				
				if ($next_arg == ")") { //empty string missing, so add it
					for ($j = $t; $j > $i; $j--) //pushing all elements forward
						$arguments[$j] = $arguments[$j - 1];
					
					$arguments[$i + 1] = '""';
					$t = count($arguments);
					$i++;
				}
				else if (!isset($next_arg)) { //empty string missing, so add it
					$arguments[$i + 1] = '""';
					$t = count($arguments);
				}
			}
			else if (!$exists_question_mark && $arg == ":") //it means that is not a delimiter but a normal string
				$arguments[$i] = '":"';
		}
		//print_r($arguments);
		//echo "exists_question_mark:";print_r($question_mark_arg_start_indexes);echo"|\n";
		
		//if exists "short if code" (something like: condition ? true : false), adds parentesis around it
		if ($exists_question_mark) {
			/*
			Find what is the start and end index for the "short if code".
			
			$tag_code = "str_replace('</textarea', '&lt;/textarea', \$_POST[description] ? \$_POST[description] : \$input[appointment][description])";
			$arguments = array(
			    [0] => '</textarea'
			    [1] => '&lt;/textarea'
			    [2] => $_POST["description"]
			    [3] => ?
			    [4] => $_POST["description"]
			    [5] => :
			    [6] => $input["appointment"]["description"]
			)
			
			$tag_code = "str_replace('</textarea', '&lt;/textarea', \$asd !== \$_POST[description] ? \$_POST[description] : \$input[appointment][description])";
			$arguments = array(
			    [0] => '</textarea'
			    [1] => '&lt;/textarea'
			    [2] => $asd
			    [3] => !==
			    [4] => $_POST["description"]
			    [5] => ?
			    [6] => $_POST["description"]
			    [7] => :
			    [8] => $input["appointment"]["description"]
			)
			or
			$arguments = array(
			    [0] => '</textarea'
			    [1] => ,
			    [2] => '&lt;/textarea'
			    [3] => ,
			    [4] => $asd
			    [5] => !==
			    [6] => $_POST["description"]
			    [7] => ?
			    [8] => $_POST["description"]
			    [9] => :
			    [10] => $input["appointment"]["description"]
			)
			
			$tag_code = "<php:for $i = $y ? 2 : 7; $i < ($g ? 10 : 11); $i++></php:for>"
			$tag_code = "<php:for $i = $y ? 2 : 7; $i < 10 ? true : false; $i++></php:for>"
			$arguments = array(
			    [0] => $i
			    [1] => =
			    [2] => $y
			    [3] => ?
			    [4] => 2
			    [5] => :
			    [6] => 7
			    [7] => ;
			    [8] => $i
			    [9] => <
			    [10] => (
			    [11] => $g
			    [12] => ?
			    [13] => 10
			    [14] => :
			    [15] => 11
			    [16] => )
			    [17] => ;
			    [18] => $i++
			)
			This case has multiple "short if codes"
			*/
			$join_delimiters = array_merge($numeric_only_delimiters, array("===", "==", "!==", "!=", ".", "&&", "||"));
			$function_and_loop_delimiters = array(",", ";");
			$is_function_or_loop = in_array($join_trimmed, $function_and_loop_delimiters); //if is inside of a function's args or a loop's args
			
			$question_mark_arg_start_idx = $question_mark_arg_end_idx = null;
			$parentesis_count = 0;
			$t = count($arguments);
			//print_r($arguments);
			
			for ($i = 0; $i < $t; $i++) {
				$arg = $arguments[$i];
				
				if ($arg == "?" && $parentesis_count == 0) //if there is a inner "short if code" (like $x ? ($y ? 1 : 0) : 1), we need to be sure that we don't find the next ? inside of the parentesis. So the $parentesis_count must be == 0
					$question_mark_arg_start_idx = $i;
				else if (is_numeric($question_mark_arg_start_idx)) {
					if ($arg == "(") 
						$parentesis_count++;
					else if ($arg == ")")
						$parentesis_count--;
					else if ($arg == ":" && $parentesis_count == 0) {
						$question_mark_arg_end_idx = $i;
						//echo "question_mark_arg_indexes: $question_mark_arg_start_idx|$question_mark_arg_end_idx\n";
						
						$there_are_internal_parentesis = false;
						$string_increment_delimiter = true;
						
						$start_arg_idx = 0;
						$end_arg_idx = count($arguments) - 1;
						
						//updates the $start_arg_idx with the correct index
						for ($j = $question_mark_arg_start_idx - 1; $j >= 0; $j--) {
							$arg = $arguments[$j];
							
							if ($parentesis_count == 0 && $arg != ")") { //bc we may have something like: (2 ? w : t) ? (2 ? x : y) : 7
								if ($arg == "(") { //if beginning of function
									$start_arg_idx = $j;
									break;
								}
								else if ($is_function_or_loop) { //only if is a function or a loop
									if ($arg == $join_trimmed) { //if there is a function or loop delimiter: "," or ";".
										$start_arg_idx = $j + 1;
										break;
									}
									else if (in_array($arg, $join_delimiters)) //if is a delimiter to combine strings
										$string_increment_delimiter = true;
									else if (substr($arg, 0, 1) == '$' || substr($arg, 0, 2) == '@$' || self::isString($arg) || is_numeric($arg) || strtolower($arg) == "true" || strtolower($arg) == "false" || strtolower($arg) == "null") { //if a string or a var or a numeric value or boolean
										if (!$string_increment_delimiter) { //if next char is not a join delimiter, stop!
											$start_arg_idx = $j + (substr($arg, 0, 2) == '@$' ? 2 : 1);
											break;
										}
										
										$string_increment_delimiter = false;
									}
									else { //if arg is a delimiter of another type or anything else, should break.
										$start_arg_idx = $j + 1;
										break;
									}
								}
							}
							
							if ($arg == ")") {
								$parentesis_count--;
								$there_are_internal_parentesis = true;
							}
							else if ($arg == "(") {
								$parentesis_count++;
								$there_are_internal_parentesis = true;
							}
						}
						
						$parentesis_count = 0;
						$string_increment_delimiter = true;
						
						//updates the $end_arg_idx with the correct index
						for ($j = $question_mark_arg_end_idx + 1; $j < $t; $j++) {
							$arg = $arguments[$j];
							
							if ($parentesis_count == 0 && $arg != "(") { //bc we may have something like: 7 ? (2 ? x : y) : (2 ? w : t)
								if ($arg == ")") { //if beginning of function
									$end_arg_idx = $j;
									break;
								}
								else if ($is_function_or_loop) { //only if is a function or a loop
									if ($arg == $join_trimmed) { //if there is a function or loop delimiter: "," or ";".
										$end_arg_idx = $j - 1;
										break;
									}
									else if (in_array($arg, $join_delimiters)) //if is a delimiter to combine strings
										$string_increment_delimiter = true;
									else if (substr($arg, 0, 1) == '$' || substr($arg, 0, 2) == '@$' || self::isString($arg) || is_numeric($arg) || strtolower($arg) == "true" || strtolower($arg) == "false" || strtolower($arg) == "null") { //if a string or a var or a numeric value or boolean
										if (!$string_increment_delimiter) { //if next char is not a join delimiter, stop!
											$end_arg_idx = $j - 1;
											break;
										}
										
										$string_increment_delimiter = false;
									}
									else { //if arg is a delimiter of another type or anything else, should break.
										$end_arg_idx = $j - 1;
										break;
									}
								}
							}
							
							if ($arg == ")") {
								$parentesis_count--;
								$there_are_internal_parentesis = true;
							}
							else if ($arg == "(") {
								$parentesis_count++;
								$there_are_internal_parentesis = true;
							}
						}
						//echo "start_arg_idx:$start_arg_idx|end_arg_idx:$end_arg_idx|\n";
						//print_r($arguments);
						
						//adds paranthesis to arguments "short if code" exists. 
						//This is very important, bc if there will be multiple echo commands, the system will add them and if there no paranthesis this "short if code" will not work, bc it will be prepended to the others echos without paranthesis.
						if ($arguments[$start_arg_idx] != "(" || $arguments[$end_arg_idx] != ")" || $there_are_internal_parentesis) { 
							$arr1 = array_slice($arguments, 0, $start_arg_idx);
							$arr2 = array_slice($arguments, $start_arg_idx, ($end_arg_idx + 1) - $start_arg_idx);
							$arr3 = array_slice($arguments, $end_arg_idx + 1);
							
							array_unshift($arr2, "(");
							$arr2[] = ")";
							
							$arguments = array_merge($arr1, $arr2, $arr3);
							$t = count($arguments);
							
							//increment one, bc we added "(" to the argments array
							$question_mark_arg_start_idx++;
							$question_mark_arg_end_idx++;
							$start_arg_idx++;
							$end_arg_idx++;
						}
						else if ($arguments[$start_arg_idx] == "(" && $arguments[$end_arg_idx] == ")" && !$there_are_internal_parentesis) {
							//we need to increase the $start_arg_idx and $end_arg_idx bc when we get the $true_condition_arguments and $false_condition_arguments we need to have the $start_arg_idx and $end_arg_idx with the right values, without the "(" and ")"
							$start_arg_idx++;
							$end_arg_idx--;
						}
						
						//prepare inner "short if codes"
						$true_condition_arguments = array_slice($arguments, $question_mark_arg_start_idx + 1, $question_mark_arg_end_idx - ($question_mark_arg_start_idx + 1));
						$false_condition_arguments = array_slice($arguments, $question_mark_arg_end_idx + 1, ($end_arg_idx + 1) - ($question_mark_arg_end_idx + 1));
						
						self::prepareArgumentsWithShortIfCode($true_condition_arguments, $join, $numeric_only_delimiters);
						self::prepareArgumentsWithShortIfCode($false_condition_arguments, $join, $numeric_only_delimiters);
						
						//echo "$question_mark_arg_start_idx|$question_mark_arg_end_idx|$end_arg_idx\n";
						//print_r($arguments);
						//echo "\ntrue_condition_arguments:";print_r($true_condition_arguments);
						//echo "\nfalse_condition_arguments:";print_r($false_condition_arguments);
						
						//reset vars to loop the next "short if code"
						$question_mark_arg_start_idx = $question_mark_arg_end_idx = null;
						$parentesis_count = 0;
						$i = $end_arg_idx;
					}
				}
			}
		}
	}
	
	/* Joins multiple followed strings.
		Optimize $arguments in case of a file system paths and other unnecessary string joins
			transform: "path_" . "asd" / "qwe" / $asd / "as12s" . "php"
			to: "path_asd/qwe/$asd/as12sphp"
			
			Note that ".php" was removed to "php". If you wish to have the ".php" you must write the path inside of quotes in your template, this is:
				<php:include $path "/asd/qwe/$asd/as12s.php">
		or
			transform: "asd" + "asd"
			to: "asd+asd"
		or
			transform: "+" . "asd" "jp"
			to: "+asdjp"
	*/
	
	private static function prepareArgumentsWithUnnecessaryFollowedStrings(&$arguments, $join_trimmed) {
		$join_delimiters = array(".", "+", "-", "*", "/", "%");
		$parentesis_count = 0;
		$t = count($arguments);
		for ($i = 0; $i < $t; $i++) {
			$arg = $arguments[$i];
			$prev_arg = $i > 0 ? $arguments[$i - 1] : null;
			$next_arg = $i + 1 < $t ? $arguments[$i + 1] : null;
			//echo "!$prev_arg!$arg!$next_arg\n";
			
			if ((substr($arg, 0, 1) == '$' || substr($arg, 0, 2) == '@$') && self::isString($prev_arg) && self::isString($next_arg)) { //Prepare variables: {$asd[0][1]} or {$a}
				//Note that $prev_arg and $next_arg are strings with quotes
				$closed = substr(trim(substr($next_arg, 1)), 0, 1) == "}";//remove first quote, then trim, then get first char
				
				if (substr($prev_arg, -2, 1) == "{" && $closed) { //get the prev last char, bc the last char is a quote
					$arguments[$i - 1] = substr($prev_arg, 0, -2) . substr($prev_arg, -1);//remove {
					$arguments[$i + 1] = substr($next_arg, 0, 1) . substr(trim(substr($next_arg, 1)), 1);//Remove }
				}
				else if (substr($arg, 0, 1) == '$' && substr($arg, 1, 1) == "{") {
					$arguments[$i] = '$' . substr($arguments[$i], 2);//add ${var}
					
					if ($closed)
						$arguments[$i + 1] = substr($next_arg, 0, 1) . substr(trim(substr($next_arg, 1)), 1);//Remove }
				}
				else if (substr($arg, 0, 2) == '@$' && substr($arg, 2, 1) == "{") {
					$arguments[$i] = '@$' . substr($arguments[$i], 3);//add @${var}
					
					if ($closed)
						$arguments[$i + 1] = substr($next_arg, 0, 1) . substr(trim(substr($next_arg, 1)), 1);//Remove }
				}
			}
			else if ($arg == "(" && $next_arg == ")") { //for this case: ()
				$arguments[$i] = ".";
				$arguments[$i + 1] = '"()"';
				$i--;
			}
			else if (self::isString($arg) && self::isString($next_arg) && ($join_trimmed == "." || ($parentesis_count > 0 && $join_trimmed != ",")) && $arg[0] == $next_arg[0]) { //only if both have the same type of quotes. Note that if the $join==',' and there is 2 args strings, we cannot concatenate them, bc are 2 diferent args!
				$arguments[$i] = false;
				$arguments[$i + 1] = $arg[0] . substr($arg, 1, -1) . substr($next_arg, 1, -1) . $arg[0];
			}
			else if (in_array($arg, $join_delimiters)) { //join multiple following strings
				$is_prev_str = self::isString($prev_arg);
				$is_next_str = self::isString($next_arg);
				
				if ($is_prev_str && $is_next_str && $prev_arg[0] == $next_arg[0]) { //only if both have the same type of quotes
					$arguments[$i - 1] = $arguments[$i] = false;
					$arguments[$i + 1] = substr($prev_arg, 0, -1) . ($arg == "." ? "" : $arg) . substr($next_arg, 1);
				}
				else if ($is_prev_str && is_numeric($next_arg)) {
					$arguments[$i - 1] = $arguments[$i] = false;
					$arguments[$i + 1] = substr($prev_arg, 0, -1) . ($arg == "." ? "" : $arg) . $next_arg . $prev_arg[0];
				}
				else if (is_numeric($prev_arg) && $is_next_str) {
					$arguments[$i - 1] = $arguments[$i] = false;
					$arguments[$i + 1] = $next_arg[0] . $prev_arg . ($arg == "." ? "" : $arg) . substr($next_arg, 1);
				}
				else if ($is_prev_str && $arg != ".") {
					$arguments[$i - 1] = substr($prev_arg, 0, -1) . $arg . $prev_arg[0];//$prev_arg[0] is a double or single quote
					$arguments[$i] = ".";
				}
				else if ($is_next_str && $arg != ".") {
					$arguments[$i] = ".";
					$arguments[$i + 1] = $next_arg[0] . $arg . substr($next_arg, 1);//$next_arg[0] is a double or single quote
				}
			}
			else if ($arg == "(")
				++$parentesis_count;
			else if ($arg == ")")
				--$parentesis_count;
		}
	}
	
	//remove empty $args. Very important this step otherwise the $prev_arg and $next_arg in the next code will be all messy and won't work.
	private static function prepareArgumentsWithNullItems(&$arguments) {
		$arguments = array_values(array_filter($arguments, function($item) {
			return strlen($item) > 0;
		  } ));
	}
	
	//Write new code based in $arguments
	private static function convertArgumentsToTagCode(&$arguments, $join, $delimiters, $reserved_keywords) {
		//echo "join:$join\n";
		//echo "exists, in delimiters:".($delimiters[","]?1:0)."\n";
		//print_r($arguments);
		//print_r($delimiters);
		//print_r($reserved_keywords);
		
		$tag_code = "";
		$parentesis_count = 0;
		$t = count($arguments);
		
		$join_trimmed = trim($join);
		
		for ($i = 0; $i < $t; $i++) {
			$arg = $arguments[$i];
			$prev_arg = $i > 0 ? $arguments[$i - 1] : null;
			$next_arg = $i + 1 < $t ? $arguments[$i + 1] : null;
			$is_delimiter = self::isDelimiter($arg, $delimiters);
			$is_prev_arg_reserved = in_array($prev_arg, $reserved_keywords);
			
			$tag_code .= strlen($tag_code) && !$is_delimiter && !self::isDelimiter($prev_arg, $delimiters) && !$is_prev_arg_reserved && 
						$prev_arg != "(" && $arg != ")" ? (
							$parentesis_count > 0 ? " . " : $join //independent if the $join_trimmed == ",", when $parentesis_count > 0 the join must be ".". If is a function, by default there will not be parentesis, so the join will "," by default
						) : "";
			/*
				if we add the condition:
					$parentesis_count > 0 && $join_trimmed != "," ? " . " : $join
				then for the this case:
					<ptl:echo foo((12.3412 as) jp) />
				we will have:
					echo foo((12.3412, "as"), "jp");
				instead of:
					echo foo((12.3412 . "as"), "jp");
				
				PLEASE DO NOT ADD THE '$join_trimmed != ","' => JP 2019-07-25
			*/
			
			if ($arg == "." && is_numeric($prev_arg) && is_numeric($next_arg))//for decimal cases
				$tag_code .= $arg;
			else if ($is_prev_arg_reserved)
				$tag_code .= " $arg";
			else if ($is_delimiter && $arg != "!" && $arg != "->")
				$tag_code .= " $arg ";
			else if (
				(strtolower($arg) == "true" || strtolower($arg) == "false") && //if true or false
				(strlen($prev_arg) || strlen($next_arg)) && //and if contains something before or after
				(!strlen($prev_arg) || $prev_arg == "." || !self::isDelimiter($prev_arg, $delimiters)) && //if contains something before, checks if it is == . or if is not a delimiter
				(!strlen($next_arg) || $next_arg == "." || !self::isDelimiter($next_arg, $delimiters)) //if contains something after, checks if it is == . or if is not a delimiter
			)
				$tag_code .= '"' . $arg . '"';
			else
				$tag_code .= $arg;
			
			if ($arg == "(")
				++$parentesis_count;
			else if ($arg == ")")
				--$parentesis_count;
		}
		
		$tag_code = str_replace("&amp;", "&", str_replace("&gt;", ">", $tag_code));
		
		return $tag_code;
	}
	
	//Fix this case: <ptl:echo assd"as2sd />\ where returns: echo "assdas2sd />\"; which gives a php error
	public static function prepareTagCodeWithLostQuote(&$tag_code) {
		//check if the exist one of these 2 cases: ...\" or ...\' And if so, add slash to the last slash, this is: ...\\" or ...\\'
		if ((substr($tag_code, -1) == '"' || substr($tag_code, -1) == "'") && TextSanitizer::isCharEscaped($tag_code, strlen($tag_code) - 1)) 
			$tag_code = substr($tag_code, 0, -1) . '\\' . substr($tag_code, -1);
	}
	
	private static function isString($arg) {
		return isset($arg[0]) && (
			($arg[0] == '"' && substr($arg, -1) == '"') || ($arg[0] == "'" && substr($arg, -1) == "'")
		);
	}
	
	private static function isDelimiter($arg, $delimiters) {
		$char = substr($arg, 0, 1);
		$delimiters_chars = array_keys($delimiters);
		
		if (in_array($char, $delimiters_chars)) {
			$delimiter = $delimiters[$char];
			$arr = is_array($delimiter) ? $delimiter : array($delimiter);
			
			return in_array($arg, $arr);
		}
		
		return false;
	}
	
	private static function configureArg($arg, $single_quotes = false) {
		$arg_0 = isset($arg[0]) ? $arg[0] : null;
		
		if (is_numeric($arg) || $arg_0 == '$')
			return $arg;
		if ($arg_0 == '@' && isset($arg[1]) && $arg[1] == '$')
			return $arg;
		else if ($arg_0 == '"')
			return $arg . (substr($arg, -1) == '"' ? "" : '"');
		else if ($arg_0 == "'")
			return $arg . (substr($arg, -1) == "'" ? "" : "'");
		else if (strtolower($arg) == "true" || strtolower($arg) == "false" || strtolower($arg) == "null") //Then we recheck at the end, to replace the true or false which are strings instead of Booleans
			return $arg;
		
		//echo "arg:$arg\n";
		return ($single_quotes ? "'" : '"') . $arg . ($single_quotes ? "'" : '"'); //DO NOT ADD addcslashes($arg, '"') or addcslashes($arg, '\\'), bc these cases must and should already be defined in the ptl code of the $tag_code. Otherwise we should get an php error (on purpose!).
	}
}
?>
