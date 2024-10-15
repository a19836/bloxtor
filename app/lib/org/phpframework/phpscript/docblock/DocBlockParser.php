<?php
include_once get_lib("org.phpframework.phpscript.docblock.annotation.Annotation");
include_once get_lib("org.phpframework.cache.user.IUserCacheHandler");
include_once get_lib("org.phpframework.util.text.TextSanitizer");

class DocBlockParser {
	/**
	 * The description of the symbol
	 * @type String
	 */
	private $desc;

	/**
	 * The tags defined in the docblock.
	 * K(eys are the tag names and the values are other arrays where each item is an entry for the correspondent tag)
	 * @type Array
	 */
	private $tags;
	
	/**
	 * The array tag's objs.
	 * @type Array
	 */
	private $objs;
	
	/**
	 * The array tag's errors.
	 * @type Array
	 */
	private $errors;

	/**
	 * The entire DocBlockParser comment that was parsed.
	 * @type String
	 */
	private $comment;
	
	/**
	 * All the tags' name parsed in the comment.
	 * @type Array
	 */
	private $included_tags;
	
	/**
	 * The cache handler.
	 * @type UserCacheHandler
	 */
	private $CacheHandler;
	
	/**
	 * The function default parameters
	 * @type Array
	 */
	private $func_default_parameters;
	
	/* ===================== PUBLIC METHODS ===================== */
	
	/**
	 * The docblock of a class.
	 * @param String $class The class name
	 * @return bool
	 */
	public function ofClass($class) {
		$cache_key = md5($class);
		
		if ($this->CacheHandler && $this->CacheHandler->isValid($cache_key))
			return $this->getCache($cache_key);
		
		$this->of(new ReflectionClass($class));
		
		return !$this->CacheHandler || $this->setCache($cache_key);
	}

	/**
	 * The docblock of a class property.
	 * @param String $class The class on which the property is defined
	 * @param String $property The name of the property
	 * @return bool
	 */
	public function ofProperty($class, $property) {
		$cache_key = md5("$class->\$$property");
		
		if ($this->CacheHandler && $this->CacheHandler->isValid($cache_key))
			return $this->getCache($cache_key);
		
		$this->of(new ReflectionProperty($class, $property));
		
		return !$this->CacheHandler || $this->setCache($cache_key);
	}

	/**
	 * The docblock of a class method.
	 * @param String $class The class on which the method is defined
	 * @param String $method The name of the method
	 * @return bool
	 */
	public function ofMethod($class, $method) {
		$cache_key = md5("$class->$method()");
		
		if ($this->CacheHandler && $this->CacheHandler->isValid($cache_key)) 
			return $this->getCache($cache_key);
		
		$reflector = new ReflectionMethod($class, $method);
		$this->of($reflector);
		
		$this->func_default_parameters = array_map( function($ReflectionParameter) { 
			return $ReflectionParameter->getName(); 
		   }, $reflector->getParameters());
		
		return !$this->CacheHandler || $this->setCache($cache_key);
	}

	/**
	 * The docblock of a function.
	 * @param String $function The name of the function
	 * @return bool
	 */
	public function ofFunction($function) {
		$cache_key = md5($function);
		
		if ($this->CacheHandler && $this->CacheHandler->isValid($cache_key))
			return $this->getCache($cache_key);
		
		$reflector = new ReflectionFunction($function);
		$this->of($reflector);
		
		$this->func_default_parameters = array_map( function($ReflectionParameter) { 
			return $ReflectionParameter->getName(); 
		   }, $reflector->getParameters());
		
		return !$this->CacheHandler || $this->setCache($cache_key);
	}

	/**
	 * The docblock of a class method.
	 * @param String $comment The class on which the method is defined
	 * @return bool
	 */
	public function ofComment($comment) { 
		$this->reset();
		
		if ($comment) {
			$cache_key = "comment_" . md5($comment);
			if ($this->CacheHandler && $this->CacheHandler->isValid($cache_key))
				return $this->getCache($cache_key);
		
			$this->init($comment);
			
			return !$this->CacheHandler || $this->setCache($cache_key);
		}
		return false;
	}

	/**
	 * The docblock of a reflection.
	 * @param Reflector $ref A reflector object defining `getDocComment`.
	 * @return bool
	 */
	public function of($ref) {
		$this->reset();
		
		if (method_exists($ref, 'getDocComment')) {
			$comment = $ref->getDocComment();
			
			if ($comment) {
				$this->init($comment);
				return true;
			}
		}
		return false;
	}

	/* ===================== CHECK METHODS ===================== */

	/**
	 * $method_args_data must be an associative array, something like this: [arg1_name => arg1_obj, arg2_name => arg2_obj, ...],
	 * where the arg1_name/arg2_name are the names of the method parameters;
	 */
	public function checkInputMethodAnnotations(&$method_params_data) { return $this->checkMethodAnnotations($method_params_data, "input"); }
	public function checkOutputMethodAnnotations(&$method_params_data) { return $this->checkMethodAnnotations($method_params_data, "output"); }
	private function checkMethodAnnotations(&$method_params_data, $type = "input") {
		$status = true;
		
		if (is_array($this->objs)) 
			foreach ($this->objs as $tag_name => $objs) 
				if ($objs) {
					$t = count($objs);
					for ($i = 0; $i < $t; $i++) {
						$obj = $objs[$i];
						
						if (($obj->isInput() && $type == "input") || ($obj->isOutput() && $type == "output"))
							if (!$obj->checkMethodAnnotations($method_params_data, $i))
								$status = false;
					}
				}
		
		return $status;
	}

	/**
	 * Get tag Params Tag's, if any
	 */
	public function getTagParams() {
		$params = array();
		
		if (!empty($this->objs["params"])) {
			$t = count($this->objs["params"]);
			for ($i = 0; $i < $t; $i++) {
				$sub_params = $this->objs["params"][$i]->getArgs();
				
				$params = array_merge($params, $sub_params);
			}
		}
		
		if (isset($this->objs["param"][0])) {
			$params = array_merge($params, $this->objs["param"]);
		}
		
		return $params;
	}
	
	/**
	 * Get tag Params Tag's errors, if any
	 */
	public function getTagParamsErrors() {
		$errors = array();
		$params = $this->getTagParams();
		
		if (!empty($params)) {
			$t = count($params);
			for ($i = 0; $i < $t; $i++) {
				$obj = $params[$i];
				
				$args = $obj->getArgs();
				$name = !empty($args["name"]) ? $args["name"] : (isset($args["index"]) ? $args["index"] : $i);
				
				$obj_errors = $obj->getErrors();
				
				if (!empty($obj_errors)) {
					$errors[$name] = $obj_errors;
				}
			}
		}
		
		return $errors ? $errors : null;
	}
	
	/**
	 * Get tag Return Tag's errors, if any
	 */
	public function getTagReturnErrors() {
		if (isset($this->objs["return"][0])) {
			return $this->objs["return"][0]->getErrors();
		}
		
		return null;
	}
	
	/* ===================== GETTERS && SETTERS METHODS ===================== */

	public function setDescription($desc) { $this->desc = $desc; }
	public function getDescription() { return $this->desc; }
	
	public function setTags($tags) { $this->tags = $tags; }
	public function getTags() { return $this->tags; }
	
	public function setObjects($objs) { $this->objs = $objs; }
	public function getObjects() { return $this->objs; }
	
	public function setComment($comment) { $this->comment = $comment; }
	public function getComment() { return $this->comment; }
	
	public function setIncludedTags($included_tags) { $this->included_tags = $included_tags; }
	public function getIncludedTags() { return $this->included_tags; }
	
	public function setIncludedTag($included_tag) { $this->included_tags[ $included_tag ] = 1; }
	
	public function setErrors($errors) { $this->errors = $errors; }
	public function getErrors() { return $this->errors; }
	
	public function setFunctionDefaultParameters($func_default_parameters) { $this->func_default_parameters = $func_default_parameters; }
	public function getFunctionDefaultParameters() { return $this->func_default_parameters; }
	
	public function setCacheHandler(IUserCacheHandler $CacheHandler) { 
		$this->CacheHandler = $CacheHandler; 
		$this->CacheHandler->config();
	}
	public function getCacheHandler() { return $this->CacheHandler; }
	
	/* ===================== PRIVATE METHODS ===================== */

	/**
	 * Set and parse the docblock comment.
	 * @param String $comment The docblock
	 */
	private function init($comment) {
		$result = $this->parseComment($comment);
		
		$this->comment = $comment;
		$this->desc = isset($result["desc"]) ? $result["desc"] : null;
		$this->tags = isset($result["tags"]) ? $result["tags"] : null;
		$this->objs = $this->parseTags($this->tags);
	}
	
	/**
	 * Resets all internal variables.
	 */
	private function reset() {
		$this->comment = "";
		$this->desc = "";
		$this->tags = array();
		$this->objs = array();
		$this->errors = array();
		$this->included_tags = array();
		$this->func_default_parameters = array();
	}
	
	/**
	 * Parse the tags into the correspondent tags' object.
	 * @param Array $tags
	 */
	private function parseTags($tags) {
		$objects = array();
		
		if (is_array($tags)) {
			foreach ($tags as $tag_name => $items) 
				if ($items) {
					$t = count($items);
					
					for ($i = 0; $i < $t; $i++) {
						$obj = $this->getTagObject($tag_name);
						
						if ($obj) {
							$obj->parseArgs($this, $items[$i]);
							
							$this->setIncludedTag($tag_name);
							$objects[$tag_name][$i] = $obj;
						}
						else {
							$this->errors[$tag_name] = "No Annotation Class for $tag_name";
						}
					}
				}
		}
		
		return $objects;
	}
	
	/**
	 * Get the object for the correspondent tag.
	 * @param String $tag_name
	 */
	private function getTagObject($tag_name) {
		if ($tag_name) {
			$path = self::getTagObjectFilePath($tag_name);
			
			if (!$path || !file_exists($path)) {
				$path = self::getTagObjectFilePath("other");
				$tag_name = "other";
			}
			
			if ($path && file_exists($path)) {
				include_once $path;
			
				eval ('$obj = new DocBlockParser\\Annotation\\' . ucfirst($tag_name) . 'Annotation();');
			
				if ($obj && is_a($obj, "DocBlockParser\\Annotation\\Annotation"))
					return $obj;
			}
		}
		
		return null;
	}
	
	private static function getTagObjectFilePath($tag_name) {
		return $tag_name ? normalize_windows_path_to_linux(__DIR__) . "/annotation/" . ucfirst($tag_name) . "Annotation.php" : null;
	}

	/**
	 * Parse the comment into the component parts and set the state of the object.
	 * @param String $comment The docblock
	 */
	private function parseComment($comment) {
		// Strip the opening and closing tags of the docblock
		$comment = substr($comment, 3, -2);

		// Split into arrays of lines
		$comment = preg_split('/\r?\n\r?/', $comment);

		// Trim asterisks and whitespace from the beginning and whitespace from the end of lines
		$comment = array_map(function($line) {
			return ltrim(rtrim($line), "* \t\n\r\0\x0B");
		}, $comment);
		
		$blocks = $this->getCommentBlocks($comment);
		//print_r($blocks);die();
		return $this->parseBlocks($blocks);
	}

	private function getCommentBlocks($comment) {
		// Group the lines together by @tags
		$blocks = array();
		$b = -1;
		$open_straight_brackets = $open_regular_brackets = $open_parentesis = 0;
		$open_double_quotes = $open_single_quotes = false;
		
		foreach ($comment as $line) {
			if($b == -1) {
				$b = 0;
				$blocks[] = array();
			}
			else {
				if (self::isTagged($line)) {
					$open_single_quotes = false;//This is to fix the issue where you can have a description with an apostrofe and in the next line a new tagged line.
					
					if (!$open_straight_brackets && !$open_regular_brackets && !$open_parentesis && !$open_double_quotes && !$open_single_quotes) {
						$b++;
						$blocks[] = array();
					}
				}
				
				if ($b > 0) {
					$l = strlen($line);
					
					if (is_numeric($line))
						$line = (string)$line; //bc of php > 7.4 if we use $var[$i] gives an warning
					
					for ($i = 0; $i < $l; $i++) {
						$char = $line[$i];
						
						if ($char == "'" && !$open_double_quotes && !TextSanitizer::isCharEscaped($line, $i))
							$open_single_quotes = !$open_single_quotes;
						else if ($char == '"' && !$open_single_quotes && !TextSanitizer::isCharEscaped($line, $i))
							$open_double_quotes = !$open_double_quotes;
						else if (!$open_double_quotes && !$open_single_quotes) {
							switch ($char) {
								case "[": $open_straight_brackets++; break;
								case "]": $open_straight_brackets > 0 ? $open_straight_brackets-- : 0; break;
								case "{": $open_regular_brackets++; break;
								case "}": $open_regular_brackets > 0 ? $open_regular_brackets-- : 0; break;
								case "(": $open_parentesis++; break;
								case ")": $open_parentesis > 0 ? $open_parentesis-- : 0; break;
							}
						}
					}
				}
			} 
			
			$blocks[$b][] = $line;
		}
		
		return $blocks;
	}

	private function parseBlocks($blocks) {
		//print_r($blocks);die();
		$tags = array();
		$description = array();
		
		//Parse the blocks
		foreach ($blocks as $block => $body) {
			$body = trim(implode("\n", $body));

			if ($block == 0 && !self::isTagged($body)) {
				// This is the description block
				$description = $body;
				continue 1;
			}
			else {
				// This block is tagged
				$tag = strtolower( substr(self::strTag($body), 1) );
				$body = ltrim(substr($body, strlen($tag)+1));
				
				if (strlen($body) && $body[0] == "(") {
					// The tagged block is a args group
					$tags[$tag][] = $this->parseAnnotationTagArgs($body);
				}
				else {
					$vectors = $this->getTagVectors($tag);
					
					if (isset($vectors)) {
						// The tagged block is a vector
						$tags[$tag][] = $this->parseAnnotationTagArgsIntoList($vectors, $body);
					}
					else {
						// The tagged block is only text
						$tags[$tag][] = $body; // Add tag even if body is empty, bc we can have empty tags like @enabled or @hidden
					}
				}
			}
		}
		
		return array("desc" => $description, "tags" => $tags);
	}
	
	private function getTagVectors($tag_name) {
		$obj = $this->getTagObject($tag_name);
		return $obj ? $obj->getVectors() : null;
	}
	
	private function parseAnnotationTagArgsIntoList($list, $body, $args_delimiter = " ") {
		$args = array();
		$remove_last_parentesis = null;
		
		$body = trim(str_replace(array("\n\r", "\n", "\t"), $args_delimiter, $body));
		if (substr($body, 0, 1) != "(") {
			$body = "($body)";
			$remove_last_parentesis = true;
		}
		$items = $this->parseAnnotationTagArgs($body, "(", $args_delimiter);
		
		if (isset($items["desc"])) {
			$desc = $items["desc"];
			$desc = $remove_last_parentesis && substr($desc, -1) == ")" ? substr($desc, 0, -1) : $desc;
			unset($items["desc"]);
		}
		
		$lt = $list ? count($list) : 0;
		$i = 0;
		
		foreach ($items as $name => $val) {
			if ($i < $lt)
				$args[ strtolower($list[$i]) ] = $val;
			else {//last one is the description
				$list_item = isset($args[ $list[$lt - 1] ]) ? $args[ $list[$lt - 1] ] : null;
				
				$text = $list_item;
				$text = is_array($text) || is_object($text) ? json_encode($text) : $text;
				$text .= " " . ($name && !is_numeric($name) ? "$name=" : "") . (is_array($val) || is_object($val) ? json_encode($val) : $val);
				$args[ strtolower($list_item) ] = trim($text);
			}
			
			$i++;
		}
		
		if ($lt > $i)
			for ($j = $i; $j < $lt; $j++)
				$args[ strtolower($list[$j]) ] = "";
		
		if (!empty($desc))
			$args["desc"] = (!empty($args["desc"]) ? $args["desc"] . " " : "") . $desc;
		
		return $args;
	}
	
	private function parseAnnotationTagArgs($body, $main_delimiter = "(", $args_delimiter = ",") {
		$args = array();
			
		$body = $body ? trim($body) : "";
		
		if (strlen($body) && $body[0] == $main_delimiter) {
			$open_straight_brackets = $open_regular_brackets = $open_parentesis = 0;
			$open_double_quotes = $open_single_quotes = false;
			$value = "";
		
			$l = strlen($body);
			for ($i = 0; $i < $l; $i++) {
				$char = $body[$i];
			
				if ($char == $args_delimiter && !$open_double_quotes && !$open_single_quotes) {
					switch ($main_delimiter) {
						case "(": $add =  !$open_straight_brackets && !$open_regular_brackets && $open_parentesis == 1; break;
						case "[": $add =  $open_straight_brackets == 1 && !$open_regular_brackets && !$open_parentesis; break;
						case "{": $add =  !$open_straight_brackets && $open_regular_brackets == 1 && !$open_parentesis; break;
						default: $add = false;
					}
					
					if ($add) {
						$args[] = trim($value);
						$value = "";
						continue 1;
					}
				}
				else if ($char == "'" && !$open_double_quotes && !TextSanitizer::isCharEscaped($body, $i)) {
					$open_single_quotes = !$open_single_quotes;
				}
				else if ($char == '"' && !$open_single_quotes && !TextSanitizer::isCharEscaped($body, $i)) {
					$open_double_quotes = !$open_double_quotes;
				}
				else if (!$open_double_quotes && !$open_single_quotes) {
					$add = false;
					$continue = false;
					
					switch ($char) {
						case "[": 
							$open_straight_brackets++; 
							
							if ($open_straight_brackets == 1 && !$open_regular_brackets && !$open_parentesis) {
								$continue = true;
							}
							break;
						case "]": 
							$open_straight_brackets > 0 ? $open_straight_brackets-- : 0; 
							$add = $main_delimiter == "[" && !$open_straight_brackets && !$open_regular_brackets && !$open_parentesis;
							break;
						case "{": 
							$open_regular_brackets++; 
							
							if (!$open_straight_brackets && $open_regular_brackets == 1 && !$open_parentesis) {
								$continue = true;
							}
							break;
						case "}": 
							$open_regular_brackets > 0 ? $open_regular_brackets-- : 0; 
							$add = $main_delimiter == "{" && !$open_straight_brackets && !$open_regular_brackets && !$open_parentesis;
							break;
						case "(": 
							$open_parentesis++; 
							
							if (!$open_straight_brackets && !$open_regular_brackets && $open_parentesis == 1) {
								$continue = true;
							}
							break;
						case ")": 
							$open_parentesis > 0 ? $open_parentesis-- : 0; 
							$add = $main_delimiter == "(" && !$open_straight_brackets && !$open_regular_brackets && !$open_parentesis;
							break;
					}
					
					if ($add) {
						$args[] = trim($value);
						$value = "";
						continue 1;
					}
					else if ($continue) {
						continue 1;
					}
				}
				
				$value .= $char;
			}
			
			$description = trim($value);
			
			$new_args = array();
			$t = count($args);
			for ($i = 0; $i < $t; $i++) {
				$arg = trim(str_replace("\n", "", $args[$i]));
				
				preg_match_all("/^(\w+)(\s*)(=)(.*)/u", $arg, $matches, PREG_PATTERN_ORDER); //'\w' means all words with '_' and '/u' means with accents and ç too.
				
				$name = $val = "";
				
				if (!empty($matches[0])) {
					$name = $matches[1][0];
					$val = trim($matches[4][0]);
				}
				else {
					$val = $arg;
				}
				
				if ($name) {
					$new_args[ strtolower($name) ] = $val;
				}
				else {
					$new_args[$i + 1] = $val;
				}
			}
			$args = $new_args;
			
			if ($description) {
				$description = substr($description, 0, 1) == ";" ? trim(substr($description, 1)) : $description;
				$args["desc"] = $description;
			}
		}
			
		return $args;
	}

	/**
	 * Whether or not a string begins with a @tag
	 * @param  String $str
	 * @return bool
	 */
	private static function isTagged($str) {
		return strlen($str) >= 2 && $str[0] == '@' && ctype_alpha($str[1]);
	}

	/**
	 * The tag at the beginning of a string
	 * @param  String $str
	 * @return String|null
	 */
	private static function strTag($str) {
		if (preg_match('/^@\w+/u', $str, $matches)) //'\w' means all words with '_' and '/u' means with accents and ç too.
			return $matches[0];
		return null;
	}
	
	private function getCache($key) {
		$this->reset();
		
		$includes = $this->CacheHandler->read($key . "_includes");
		
		if ($includes) {
			$t = count($includes);
			for ($i = 0; $i < $t; $i++)
				include_once $includes[$i];
		}
		
		$DocBlockParser = $this->CacheHandler->read($key);
		
		if ($DocBlockParser) {
			$this->comment = $DocBlockParser->getComment();
			$this->desc = $DocBlockParser->getDescription();
			$this->tags = $DocBlockParser->getTags();
			$this->objs = $DocBlockParser->getObjects();
			$this->errors = $DocBlockParser->getErrors();
			$this->included_tags = $DocBlockParser->getIncludedTags();
			$this->func_default_parameters = $DocBlockParser->getFunctionDefaultParameters();
			
			return true;
		}
		
		return false;
	}
	
	private function setCache($key) {
		$includes = $this->getIncludedTagsObjectFilePaths();
		
		return $this->CacheHandler->write($key . "_includes", $includes) && $this->CacheHandler->write($key, $this);
	}
	
	private function getIncludedTagsObjectFilePaths() {
		$includes = array();
		if (is_array($this->included_tags)) {
			foreach ($this->included_tags as $tag_name => $aux) {
				$path = self::getTagObjectFilePath($tag_name);
				
				if ($path && file_exists($path)) //only if file exists bc $tag_name could be @hidden or something else which don't have any file.
					$includes[] = $path;
			}
		}
		return $includes;
	}
}
