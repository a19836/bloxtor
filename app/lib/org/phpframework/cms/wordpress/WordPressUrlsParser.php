<?php
include_once get_lib("org.phpframework.util.text.TextSanitizer");

class WordPressUrlsParser {
	
	const WORDPRESS_FOLDER_PREFIX = "cms/wordpress";
	
	//used by the WordPressCMSBlockHandler.php
	//needs to have is_object bc the $arr can contain objects returned from the wordpress functions in the WordPressHacker::executeFunctions method
	public static function prepareArrayWithWordPressUrls($arr, $options, $content) {
		$new_arr = is_object($arr) ? $arr : array();
		
		if ($arr) {
			$wordpress_site_url = isset($content["wordpress_site_url"]) ? $content["wordpress_site_url"] : null;
			$current_page_url = isset($content["current_page_url"]) ? $content["current_page_url"] : null;
			
			foreach ($arr as $k => $v) {
				if (is_array($v) || is_object($v))
					$v = self::prepareArrayWithWordPressUrls($v, $options, $content);
				else
					$v = self::parseWordPressHtml($v, $wordpress_site_url, $current_page_url, $options);
				
				if (is_object($arr))
					$new_arr->$k = $v;
				else
					$new_arr[$k] = $v;
			}
		}
		
		return $new_arr;
	}
	
	public static function parseWordPressHtml($html, $wordpress_site_url, $current_page_url, $options) {
		$parsed_html = "";
		if (!$wordpress_site_url || empty($options["parse_wordpress_urls"]))
			$parsed_html = $html;
		else if ($html) {
			$wordpress_site_url = preg_replace("/\/+$/", "", $wordpress_site_url); //remove last slashes from $wordpress_site_url
			$current_page_url = preg_replace("/\/+$/", "", $current_page_url); //remove last slashes from $wordpress_site_url
			
			//prepare html replacing it based on the url path names
			if (!empty($options["parse_wordpress_relative_urls"])) {
				$wordpress_site_url_path = parse_url($wordpress_site_url, PHP_URL_PATH);
				$current_page_url_path = parse_url($current_page_url, PHP_URL_PATH);
				
				$html = self::parseWordPressString($html, $wordpress_site_url_path, $current_page_url_path, $options);
				$html = self::parseWordPressString($html, addcslashes($wordpress_site_url_path, '/'), addcslashes($current_page_url_path, '/'), $options, true); //bc of json code that escapes the slashes
			}
			
			//prepare html replacing it based on full url
			$html = self::parseWordPressString($html, $wordpress_site_url, $current_page_url, $options);
			
			//prepare html replacing it based on html element attributes
			$current_page_url .= strpos($current_page_url, "?") === false ? "?" : "&";
			$wordpress_site_url_without_protocol = preg_replace("/^(\/\/|https?:\/\/)/i", "", $wordpress_site_url);
			
			if (!empty($options["allowed_wordpress_urls"]))
				$options["allowed_wordpress_urls"] = is_array($options["allowed_wordpress_urls"]) ? $options["allowed_wordpress_urls"] : array($options["allowed_wordpress_urls"]);
			
			$html_chars = TextSanitizer::mbStrSplit($html);
			$l = count($html_chars);
			$odq = $osq = $ot = false;
			
			for ($i = 0; $i < $l; $i++) {
				$char = $html_chars[$i];
				
				if ($char == '"' && !$osq && !TextSanitizer::isMBCharEscaped($html, $i, $html_chars))
					$odq = !$odq;
				else if ($char == "'" && !$odq && !TextSanitizer::isMBCharEscaped($html, $i, $html_chars))
					$osq = !$osq;
				else if ($char == "<" && !$odq && !$osq) {
					//avoid script and style code
					if ($html_chars[$i + 1] != "/") {
						$aux = implode("", array_slice($html_chars, $i, 10));
						$tag_to_jump = array("script", "style");
						$is_tag_to_jump = false;
						
						foreach ($tag_to_jump as $tag)
							if (strtolower(substr($aux, 0, strlen($tag))) == "<$tag") {
								$is_tag_to_jump = true;
								
								$pos = mb_stripos($html, "</$tag", $i + 1);
								$pos = $pos === false ? $l : $pos;
								$pos = mb_strpos($html, ">", $pos);
								$pos = $pos === false ? $l : $pos;
								break;
							}
						
						if ($is_tag_to_jump)  {
							$parsed_html .= implode("", array_slice($html_chars, $i, ($pos - $i) + 1));
							$i = $pos;
							continue 1;
						}
					}
					
					$ot = true;
				}
				else if ($char == ">" && !$odq && !$osq)
					$ot = false;
				else if ($ot && ($odq || $osq)) { //if is inside of a tag attribute 
					for ($j = $i; $j < $l; $j++) {
						$char = $html_chars[$j];
						
						if ($char == '"' && $odq && !TextSanitizer::isMBCharEscaped($html, $j, $html_chars))
							break;
						else if ($char == "'" && $osq && !TextSanitizer::isMBCharEscaped($html, $j, $html_chars))
							break;
					}
					
					$attr_value = trim(implode("", array_slice($html_chars, $i, $j - $i)));
					
					//prepare new url
					if ($attr_value) {
						$convert_url = false;
						
						//check html for urls that start with absolute url: $wordpress_site_url
						if (stripos($attr_value, $wordpress_site_url) === 0) {
							$next_chars = trim(substr($attr_value, strlen($wordpress_site_url)));
							$convert_url = !$next_chars || preg_match("/^(\/|\\\\\/|\$|#|&)/", $next_chars); //check next char to be sure is not a letter
						}
						else if (substr($attr_value, 0, 2) == "//" && stripos(substr($attr_value, 2), $wordpress_site_url_without_protocol) === 0) {
							$next_chars = trim(substr($attr_value, strlen($wordpress_site_url_without_protocol) + 2));
							$convert_url = !$next_chars || preg_match("/^(\/|\\\\\/|\$|#|&)/", $next_chars); //check next char to be sure is not a letter
						}
						
						//if no matches, check for relative path
						if (!$convert_url && !empty($options["parse_wordpress_relative_urls"]) && isset($wordpress_site_url_path) && stripos($attr_value, $wordpress_site_url_path) !== false) {
							$pos = stripos($attr_value, $wordpress_site_url_path);
							$next_chars = trim(substr($attr_value, $pos + strlen($wordpress_site_url_path)));
							$convert_url = !$next_chars || preg_match("/^(\/|\\\\\/|\$|#|&)/", $next_chars); //check next char to be sure is not a letter
						}
						
						if ($convert_url && !self::isAllowedWordPressUrl($attr_value, $options)) {
							$is_php_file = self::isWordPressPHPFile($attr_value);
							
							if ($is_php_file || !self::isWordPressRawFile($attr_value)) { //avoids changing images, css, js and other static files
								//echo "Previous attr_value:$attr_value\n<br>";
								//error_log("Previous $attr_value\n", 3, "/var/www/html/livingroop/default/tmp/test.log");
								$attr_value = self::convertUrlToRedirectUrl($wordpress_site_url, $current_page_url, $attr_value, $options, $is_php_file ? "wp_file" : "wp_url");
								//error_log("After $attr_value\n", 3, "/var/www/html/livingroop/default/tmp/test.log");
								//echo "After attr_value:$attr_value\n<br><br>";
							}
						}
					}
					
					$parsed_html .= $attr_value;
					$i = $j - 1;
					continue 1;
				}
				
				$parsed_html .= $char;
			}
		}
		
		return $parsed_html;	
	}
	
	private static function parseWordPressString($html, $wordpress_site_url, $current_page_url, $options, $escaped = false) {
		if ($html) {
			$offset = 0;
			
			do {
				$pos = stripos($html, $wordpress_site_url, $offset);
				
				if ($pos !== false) {
					$data = self::getUrlFromString($html, $wordpress_site_url, $pos, $options);
					$full_url = isset($data[0]) ? $data[0] : null;
					$start = isset($data[1]) ? $data[1] : null;
					$offset = isset($data[2]) ? $data[2] : null;
					//error_log("$full_url\n", 3, "/var/www/html/livingroop/default/tmp/test.log");
					
					$pos = stripos($full_url, $wordpress_site_url);
					$next_chars = trim(substr($full_url, $pos + strlen($wordpress_site_url)));
					$convert_url = !$next_chars || preg_match("/^(\/|\\\\\/|\$|#|&|\"|')/", $next_chars); //check next char to be sure is not a letter
					
					if ($convert_url && !self::isAllowedWordPressUrl($full_url, $options)) {
						//replace full_url by new_url
						$is_php_file = self::isWordPressPHPFile($full_url);
						
						if ($is_php_file || !self::isWordPressRawFile($full_url)) { //avoids changing images, css, js and other static files
							//echo "$full_url<br>\n";
							//error_log("Before $full_url\n", 3, "/var/www/html/livingroop/default/tmp/test.log");
							$new_url = self::convertUrlToRedirectUrl($wordpress_site_url, $current_page_url, $full_url, $options, $is_php_file ? "wp_file" : "wp_url", $escaped);
							//error_log("After $new_url\n", 3, "/var/www/html/livingroop/default/tmp/test.log");
							
							if ($new_url != $full_url) {
								$html = substr($html, 0, $start) . $new_url . substr($html, $offset);
								$offset = $start + strlen($new_url); //set new offset
							}
						}
					}
				}
			}
			while($pos !== false);
		}
		
		return $html;
	}
	
	private static function getUrlFromString($html, $searched_url, $pos, $options) {
		//prepare delimiter
		$delimiters = null;
		$html_length = strlen($html);
		$searched_url_length = strlen($searched_url);
		$extra_delimiters = $options && isset($options["delimiters"]) ? $options["delimiters"] : "";
		
		if (is_numeric($html))
			$html = (string)$html; //bc of php > 7.4 if we use $var[$i] gives an warning
		
		for ($i = $pos - 1; $i >= 0; $i--) {
			$char = $html[$i];
			
			if ($char == '"' || $char == "'") {
				$delimiters = array($char);
				break;
			}
			else if ($extra_delimiters && !empty($extra_delimiters[$char])) {
				$delimiters = is_array($extra_delimiters[$char]) ? $extra_delimiters[$char] : array($extra_delimiters[$char]);
				break;
			}
			else if (!preg_match("/[a-z0-9_\-\.\:\/\\\\]/i", $char)) //is not allowed spaces in the host domain so
				break;
		}
		
		//prepare full_url
		for ($j = $pos + $searched_url_length; $j < $html_length; $j++) {
			$char = $html[$j];
			
			if ( ($delimiters && in_array($char, $delimiters)) || (!$delimiters && preg_match("/(\s|\"|\'|>)/", $char)) )
				break;
		}
		
		$i++; //increment $i, otherwise $i corresponds to the delimiter " or ' or somethinf else not belonging to the url
		$full_url = substr($html, $i, $j - $i);
		$offset = $j;
		
		//error_log("pos: $pos, i: $i, j: $j\n", 3, "/var/www/html/livingroop/default/tmp/test.log");
		//error_log("searched_url: $searched_url\n", 3, "/var/www/html/livingroop/default/tmp/test.log");
		//error_log("full_url: $full_url\n", 3, "/var/www/html/livingroop/default/tmp/test.log");
		return array($full_url, $i, $j);
	}
	
	//check if headers contain any redirect and if so check if it is a wordpress url, and if yes change it to a phpframework url
	public static function parseWordPressHeaders($wordpress_site_url, $current_page_url, $options, &$stop = false) {
		$headers = headers_list();
		//error_log("headers: ".print_r($headers, 1)."\n", 3, "/var/www/html/livingroop/default/tmp/test.log");
		
		if ($headers && $wordpress_site_url && !empty($options["parse_wordpress_urls"])) {
			$wordpress_site_url = preg_replace("/\/+$/", "", $wordpress_site_url); //remove last slashes from $wordpress_site_url
			$current_page_url = preg_replace("/\/+$/", "", $current_page_url); //remove last slashes from $wordpress_site_url
			$current_page_url .= strpos($current_page_url, "?") === false ? "?" : "&";
			$wordpress_site_url_path = $current_page_url_path = null;
			
			if (!empty($options["parse_wordpress_relative_urls"])) {
				$wordpress_site_url_path = parse_url($wordpress_site_url, PHP_URL_PATH);
				$current_page_url_path = parse_url($current_page_url, PHP_URL_PATH);
			}
			
			foreach ($headers as $header) {
				$parts = explode(":", $header);
				
				if (count($parts) > 1) {
					$name = array_shift($parts);
					$value = implode(":", $parts);
					$options["delimiters"] = array("<" => ">");
					$new_value = $value;
					
					if (!empty($options["parse_wordpress_relative_urls"])) {
						$new_value = self::parseWordPressString($new_value, $wordpress_site_url_path, $current_page_url_path, $options);
						$new_value = self::parseWordPressString($new_value, addcslashes($wordpress_site_url_path, '/'), addcslashes($current_page_url_path, '/'), $options, true); //bc of json code that escapes the slashes
					}
					
					if (stripos($new_value, $wordpress_site_url) !== false)
						$new_value = self::parseWordPressString($new_value, $wordpress_site_url, $current_page_url, $options);
					
					if ($new_value != $value) {
						//error_log("header old value: $value\n", 3, "/var/www/html/livingroop/default/tmp/test.log");
						//error_log("header new value: $new_value\n\n", 3, "/var/www/html/livingroop/default/tmp/test.log");
						$header = $name . ":" . $new_value;
						header($header);
					}
					
					if (strtolower(trim($name)) == "location")
						$stop = true;
				}
			}
		}
	}
	
	//used in the WordPressCMSBlockHandler.php
	public static function convertUrlToRedirectUrl($wordpress_site_url, $current_page_url, $url_to_replace, $options, $attr_name = "wp_url", $escaped = false) {
		//if ($escaped){error_log("$url_to_replace\n", 3, "/var/www/html/livingroop/default/tmp/test.log");}
		
		//prepare some vars and decode chars like #038;
		$wordpress_site_url = htmlspecialchars_decode($wordpress_site_url);
		$current_page_url = htmlspecialchars_decode($current_page_url);
		$url_to_replace = htmlspecialchars_decode($url_to_replace);
		
		//prepare some vars
		$original_wordpress_site_url = $wordpress_site_url;
		$original_current_page_url = $current_page_url;
		
		$wordpress_site_url = self::addHostToUrlIfAny($wordpress_site_url, $url_to_replace, $escaped);
		$current_page_url = self::addHostToUrlIfAny($current_page_url, $url_to_replace, $escaped);
		
		$wordpress_site_url_without_protocol = preg_replace("/^(\/\/|https?:\/\/)/i", "", $wordpress_site_url);
		$wordpress_site_url_path = parse_url($wordpress_site_url, PHP_URL_PATH); //if wordpress_site_url contains protocol, get the pathname, otherwise is already the relative url without protocol and hostname.
		
		//parse absolute url 1
		//in case url_to_replace starts with $wordpress_site_url, get relative url from $wordpress_site_url
		self::prepareRelativeUrl($url_to_replace, $wordpress_site_url);
		
		//parse absolute url 2
		//in case url_to_replace starts with //, get relative url from //$wordpress_site_url_without_protocol
		if (substr($url_to_replace, 0, 2) == "//") 
			self::prepareRelativeUrl($url_to_replace, $wordpress_site_url_without_protocol);
		
		//parse absolute url 2
		//if url_to_replace is still absolute url, force relative url if domain and port is the same than $current_page_url. Protocol doesnt matter here, so we should use the $url_to_replace's protocol.
		$parts = parse_url($url_to_replace);
		
		if (isset($parts["scheme"])) {
			$scheme = parse_url($url_to_replace, PHP_URL_SCHEME); //get protocol from url_to_replace
			$cp_parts = parse_url($current_page_url);
			$cpu = $scheme . "://" . (isset($cp_parts["host"]) ? $cp_parts["host"] : "") . (isset($cp_parts["port"]) ? ":" . $cp_parts["port"] : "") . "/";
			self::prepareRelativeUrl($url_to_replace, $cpu);
		}
		
		//prepare url_to_replace properties
		$parts = parse_url($url_to_replace);
		
		 //if url_to_replace is still an absolute url from a differente domain, we should not do anything. This can happen when search for the relative urls, this is, when the $options["parse_wordpress_relative_urls"] is active...
		if (isset($parts["scheme"]))
			return $url_to_replace;
			
		$url_to_replace = isset($parts["path"]) ? $parts["path"] : null;
		$query_string = isset($parts["query"]) ? $parts["query"] : null;
		$hash = isset($parts["fragment"]) ? $parts["fragment"] : null;
		
		//parse relative url
		//if the $url_to_replace path contains the wordpress_site_url path, the wordpress_site_url path should be removed from $url_to_replace
		$wps_url_path = $wordpress_site_url_path . (substr($wordpress_site_url_path, -1) == "/" ? "" : "/");
		if (strpos($url_to_replace, $wps_url_path) === 0)
			$url_to_replace = substr($url_to_replace, strlen($wps_url_path));
		else if ($url_to_replace == $wordpress_site_url_path)
			$url_to_replace = "";
		
		//error_log("$url_to_replace\n", 3, "/var/www/html/livingroop/default/tmp/test.log");
		
		//remove page id if is equal to previous request, otherwise we will have an infinity loop caused from wordpress
		if (preg_match("/paged=([0-9]*)/", $current_page_url, $matches)) {
			$old_paged = $matches[1];
			$current_page_url = preg_replace("/paged=([0-9]*)&?/", "", $current_page_url);
			
			if (preg_match("/paged=([0-9]*)/", $query_string, $matches)) {
				$new_paged = $matches[1];
				
				if ($new_paged == $old_paged)
					$query_string = preg_replace("/paged=([0-9]*)&?/", "", $query_string);
			}
		}
		
		//remove repeated params from $current_page_url and threat query string params
		if ($query_string) {
			parse_str($query_string, $params);
			
			if ($params) {
				foreach ($params as $param_name => $param_value) {
					//remove param from current_page_url bc it will be added through the $query_string
					$current_page_url = preg_replace("/$param_name=[^&]*/", "", $current_page_url);
					
					//check if there is any param with the wordpress url
					if ($param_name != "wp_url" && $param_name != "wp_file") {
						$param_value = urldecode($param_value);
						$convert_url = false;
						
						if (stripos($param_value, $wordpress_site_url) === 0)
							$convert_url = true;
						else if (substr($param_value, 0, 2) == "//" && stripos(substr($param_value, 2), $wordpress_site_url_without_protocol) === 0)
							$convert_url = true;
						else if (!empty($options["parse_wordpress_relative_urls"]) && stripos($param_value, $wordpress_site_url_path) !== false) { //check html for urls that start with relative url: $wordpress_site_url_path
							$pos = stripos($param_value, $wordpress_site_url_path);
							$convert_url = isset($param_value[$pos + 1]) ? preg_match("/(|\s|\"|'|\/|\?|#)/", $param_value[$pos + 1]) : false;
						}
						
						if ($convert_url && !self::isAllowedWordPressUrl($param_value, $options)) {
							$is_php_file = self::isWordPressPHPFile($param_value);
							
							if ($is_php_file || !self::isWordPressRawFile($param_value)) { //avoids changing images, css, js and other static files
								//error_log("param_value $param_value\n", 3, "/var/www/html/livingroop/default/tmp/test.log");
								$new_param_value = self::convertUrlToRedirectUrl($original_wordpress_site_url, $original_current_page_url, $param_value, $options, $is_php_file ? "wp_file" : "wp_url");
								
								$query_string = "&$query_string&"; //add &, so we can use the str_replace safetly in case exists other param with the same $param_value or that contains this $param_value.
								$query_string = str_replace("&$param_name=$param_value&", "&$param_name=" . urlencode($new_param_value) . "&", $query_string); //Do NOT use the http_build_query bc it will convert the query_string params with urlencode and we don't want to change the other params.
								$query_string = substr($query_string, 1, -1); //remove & added above
							}
						}
					}
				}
			}
		}
		
		//$escaped is true when the $url_to_replace contains the slashes escaped bc it comes from the json code that escapes the slashes. in this case we want to unescape the $url_to_replace bc it will be urlencoded after. The $url_to_replace is only the url path name so it's fine to use the stripslashes!
		if ($escaped)
			$url_to_replace = stripcslashes($url_to_replace);
		
		if ($url_to_replace == "/")
			$url_to_replace = "";
		
		//create new url
		$current_page_url = $current_page_url . (strpos($current_page_url, "?") === false ? "?" : "&") . "$attr_name=" . urlencode($url_to_replace) . ($query_string ? "&$query_string" : "");
		
		//force adding or replacing the phpframework_block_id if exists in $options
		if (!empty($options["phpframework_block_id"]))
			self::replaceUrlPhpFrameworkBlockId($current_page_url, $options["phpframework_block_id"]);
		
		//adding hash to url
		if ($hash)
			$current_page_url .= "#$hash";
		 
		//remove repeated &
		$current_page_url = str_replace("?&", "?", preg_replace("/&+/", "&", $current_page_url));
		
		//error_log("$current_page_url\n", 3, "/var/www/html/livingroop/default/tmp/test.log");
		//if ($escaped){error_log("$current_page_url\n\n", 3, "/var/www/html/livingroop/default/tmp/test.log");}
		
		//return new url
		return $current_page_url;
	}
	
	private static function addHostToUrlIfAny($url, $absolute_url, $escaped) {
		if ($escaped) {
			$url = stripcslashes($url);
			$absolute_url = stripcslashes($absolute_url);
		}
		
		$url_parts = parse_url($url);
		$absolute_url_parts = parse_url($absolute_url);
		
		if (empty($url_parts["host"]) && !empty($absolute_url_parts["host"])) {
			$user = isset($absolute_url_parts["user"]) ? $absolute_url_parts["user"] : null;
			$pass = !empty($absolute_url_parts["pass"]) ? ":" . $absolute_url_parts["pass"] : "";
			$port = isset($absolute_url_parts["port"]) ? $absolute_url_parts["port"] : null;
			
			$url = ($user || $pass ? "$user$pass@" : "") . $absolute_url_parts["host"] . ($port ? ":$port" : "") . (substr($url, 0, 1) == "/" ? "" : "/") . $url;
		}
		
		if (empty($url_parts["scheme"]) && !empty($absolute_url_parts["scheme"]))
			$url = $absolute_url_parts["scheme"] . "://" . $url;
		
		if ($escaped) {
			$url = addcslashes($url, '/');
			$absolute_url = addcslashes($absolute_url, '/');
		}
		
		return $url;
	}
	
	private static function prepareRelativeUrl(&$url, $base_url) {
		self::prepareUrl($url);
		$url = $base_url && strpos($url, $base_url) === 0 ? substr($url, strlen($base_url)) : $url;
	}
	
	//used in the WordPressCMSBlockHandler.php
	public static function prepareUrl(&$url) {
		$url = trim($url);
		$url = preg_replace("/(\?|&)(phpframework_block_id|wp_url|wp_file)=([^&]*)/", "\${1}", $url);
		$url = preg_replace("/&+/", "&", $url);
		$url = preg_replace("/\?&/", "?", $url);
	}
	
	//used in the WordPressCMSBlockHandler.php
	public static function replaceUrlPhpFrameworkBlockId(&$url, $block_id) {
		$url = preg_replace("/(\?|&)phpframework_block_id=([^&]*)/", "\${1}", $url);
		$url .= (strpos($url, "?") === false ? "?" : "&") . "phpframework_block_id=" . $block_id;
	}
	
	//used in the WordPressCMSBlockHandler.php
	public static function isWordPressPHPFile($url) {
		$pos = strpos($url, "?");
		if ($pos !== false)
			$url = substr($url, 0, $pos);
		
		$pos = strpos($url, "#");
		if ($pos !== false)
			$url = substr($url, 0, $pos);
		
		$url = trim($url);
		
		return strtolower(substr($url, -4)) == ".php";
	}
	
	//used in the WordPressCMSBlockHandler.php
	public static function isWordPressRawFile($url) {
		$pos = strpos($url, "?");
		if ($pos !== false)
			$url = substr($url, 0, $pos);
		
		$pos = strpos($url, "#");
		if ($pos !== false)
			$url = substr($url, 0, $pos);
		
		$url = trim($url);
		
		if (substr($url, -1) == "/")
			return false;
		else if (stripos($url, "/wp-content/uploads/") !== false)
			return true;
		
		$url = str_replace("://", "", $url);
		$url = preg_replace("/\/+/", "/", $url); //remove duplicated slashes
		$parts = explode("/", $url);
		$last = $parts[ count($parts) - 1 ];
		
		$pos = strrpos($last, ".");
		$extension = $pos !== false ? substr($last, $pos + 1) : '';
		
		return $extension && strlen($extension) < 4; //avoids changing images, css, js and other static files
	}
	
	private static function isAllowedWordPressUrl($url, $options) {
		if ($options && !empty($options["allowed_wordpress_urls"])) 
			foreach ($options["allowed_wordpress_urls"] as $regex) {
				$is_regex = isset($regex[0]) && $regex[0] == "/" && substr($regex, -1) == "/";
				
				if ( ($is_regex && preg_match($regex, $url)) || (!$is_regex && $regex == $url) )
					return true;
			}
			
		return false;
	}
}
?>
