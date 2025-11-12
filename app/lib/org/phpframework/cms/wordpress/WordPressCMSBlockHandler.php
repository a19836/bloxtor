<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

include_once get_lib("org.phpframework.util.web.CookieHandler");
include_once get_lib("org.phpframework.util.web.MyCurl");
include_once get_lib("org.phpframework.encryption.CryptoKeyHandler");
include_once get_lib("org.phpframework.cms.wordpress.WordPressHacker");
include_once get_lib("org.phpframework.cms.wordpress.WordPressUrlsParser");

class WordPressCMSBlockHandler {
	private $EVC;
	private $settings;
	private $user_authenticated;
	
	private $stop = false;
	
	/*
	 * $settings = array(
	 *	"wordpress_folder" => "...", //db_driver name
	 * 	"wordpress_request_content_url" => "...", //url for the get_wordpress_content.php like "{$project_url_prefix}module/wordpress/get_html_contens/get_wordpress_content"
	 * 	"wordpress_request_content_connection_timeout" => 0, //number in seconds
	 * 	"wordpress_request_content_encryption_key" => "...", //hexadecimal key created through CryptoKeyHandler::getKey()
	 * 	"cookies_prefix" => null,
	 * );
	 * 
	 * if $user_authenticated == true, it means we have access to all the methods of the WordPressHacker class
	 */
	public function __construct($EVC, $settings, $user_authenticated = true) {
		if (empty($settings["cookies_prefix"]))
			$settings["cookies_prefix"] = isset($settings["wordpress_folder"]) ? $settings["wordpress_folder"] : null;
		
		if (empty($settings["cookies_prefix"]))
			launch_exception(new Exception("WordPress folder cannot be empty!"));
		
		$this->EVC = $EVC;
		$this->settings = $settings;
		$this->user_authenticated = $user_authenticated;
	}
	
	public static function convertContentsHtmlToPHPTemplate($html) {
		/*
		 * Prepare template-regions code, this is,
		 * replace:
			<!-- phpframework:template:region: "xxx" -->
		 * by:
		 * 	<?= $EVC->getCMSLayer()->getCMSTemplateLayer()->renderRegion("xxx"); ?>
		 */
		if ($html && preg_match_all("/<!--\s*phpframework:template:(region|param):\s*/", $html, $matches, PREG_OFFSET_CAPTURE) && $matches[0]) {
			$html_length = strlen($html);
			$new_html = $html;
			
			foreach ($matches[0] as $idx => $match) {
				$start_pos = $match[1] + strlen($match[0]);
				$end_pos = strpos($html, "-->", $start_pos);
				$end_pos = $end_pos !== false ? $end_pos : $html_length;
				$method = $matches[1][$idx][0] == "region" ? "renderRegion" : "getParam";
				
				$region = trim( substr($html, $start_pos, $end_pos - $start_pos) );
				$region = isset($region[0]) && $region[0] == '"' && substr($region, -1) == '"' ? stripcslashes(substr($region, 1, -1)) : $region;
				$region = isset($region[0]) && $region[0] == "'" && substr($region, -1) == "'" ? stripcslashes(substr($region, 1, -1)) : $region;
				
				//echo "$region|$method<br>";
				$str = substr($html, $match[1], ($end_pos + 3) - $match[1]);
				$new_html = str_replace($str, '<?= $EVC->getCMSLayer()->getCMSTemplateLayer()->' . $method . '("' . addcslashes($region, '"') . '"); ?>', $new_html);
			}
			
			$html = $new_html;
		}
		
		return $html;
	}
	
	//replace all regions and params with correspondent xml code
	public static function addTemplateXMLRegionsAndParamsToPHPTemplate($content) {
		$offset = 0;
		
		do {
			preg_match('/\$EVC->getCMSLayer\(\)->getCMSTemplateLayer\(\)->(renderRegion|getParam)\(("|\')([^\)]*)("|\')\)/', $content, $matches, PREG_OFFSET_CAPTURE, $offset);
			//echo "<pre>";print_r($matches);die();
			
			if ($matches) {
				$first_offset = $matches[0][1];
				$full_match = $matches[0][0];
				$full_match_length = strlen($full_match);
				$method = $matches[1][0] == "renderRegion" ? "region" : "param";
				$region_param_id = $matches[3][0];
				$replacement = "<!--phpframework:template:$method:$region_param_id-->";
				
				$semi_colon_pos = strpos($content, ";", $first_offset + $full_match_length);
				$end_php_tag_pos = strpos($content, "?>", $first_offset + $full_match_length);
				$pos = null;
				
				if ($semi_colon_pos && (!$end_php_tag_pos || $semi_colon_pos < $end_php_tag_pos)) {
					$pos = $semi_colon_pos + 1;
					$replacement = " echo '$replacement';";
				}
				else if ($end_php_tag_pos && (!$semi_colon_pos || $semi_colon_pos > $end_php_tag_pos)) {
					$pos = $end_php_tag_pos + 2;
					$replacement = "<?php echo '$replacement'; ?>";
				}
				
				if ($pos) {
					$content = substr($content, 0, $pos) . $replacement . substr($content, $pos);
					$offset = $pos + strlen($replacement);
				}
				else
					$offset = $first_offset + $full_match_length;
			}
		}
		while ($matches);
		
		return $content;
	}
	
	/*
	 * $block_id = "region_content_1"
	 * 
	 * $url_query = "/2020/10/21/hello-world/"
	 * $url_query = "/product/product-1/"
	 * 
	 * $options = array(
	 * 	"parse_wordpress_urls" => true/false, //If true, means that the system will replace all wordpress urls with the phpframework url
	 *	"parse_wordpress_relative_urls" => true/false, //If true, means that the system will replace all wordpress relative urls with the phpframework url
	 *	"allowed_wordpress_urls" => array("/some url regex/", "or a full url"), //regex for the urls that the system shouold leave alone
	 * )
	 * 
	 * if wordpress was previously called and the new wordpress template is different than the previous wordpress call, do a curl request. Do not call wordpress directly bc it was already initialized before with the template: $first_wordpress_theme_called. If we want to call the wordpress with a new template, we must do it now with a curl request, otherwise the data will not be trustable.
	 * Because of this reason we should call this method getBlockContent and not the getBlockContentDirectly directly.
	 * 
	 * returns an array
	 */
	public function getBlockContent($block_id, $url_query, $options) {
		global $wordpress_already_called, $first_wordpress_theme_to_call, $first_wordpress_theme_called;
		
		//prepare current block id - This wil be usd in the WordPressUrlsParser::convertUrlToRedirectUrl and in the self::prepareRedirectUrl
		$GLOBALS["current_phpframework_block_id"] = $block_id;
		
		//error_log("getBlockContent: $block_id, $url_query, ".print_r($options, 1)."\n", 3, "/var/www/html/livingroop/default/tmp/test.log");
		//error_log("class WordPressCMSBlockHandler::settings: ".print_r($this->settings, 1)."\n", 3, "/var/www/html/livingroop/default/tmp/test.log");
		
		$content = null;
		$wordpress_theme_to_call = WordPressHacker::getPHPFrameworkFromOptions($options);
		//echo "<h1>$wordpress_already_called: $first_wordpress_theme_to_call == $first_wordpress_theme_called == $wordpress_theme_to_call</h1>";
		
		//if ($wordpress_already_called && $wordpress_theme_to_call != $first_wordpress_theme_to_call && $wordpress_theme_to_call != $first_wordpress_theme_called) { //Bc of the redirects from wordpress (this is, the set Location headers), this if is disabled, bc if there is a page with 2 wordpress blocks and both set a Location header, this will cause a infinit loop, so we must force the second wordpress block to be called via curl.
		if ($wordpress_already_called) {
			//prepare curl url
			$url = isset($this->settings["wordpress_request_content_url"]) ? $this->settings["wordpress_request_content_url"] : null;
			
			if (!$url)
				launch_exception(new Exception('You are calling multiple wordpress instances with different templates, so you must defined the "wordpress_request_content_url" settings when creating a WordPressCMSBlockHandler object!'));
			
			//prepare curl url with correspondent query_string and hash
			$url_parts = isset($_SERVER["REQUEST_URI"]) ? parse_url($_SERVER["REQUEST_URI"]) : null;
			$url .= !empty($url_parts["query"]) ? (strpos($url, "?") !== false ? "" : "?") . $url_parts["query"] : ""; //Note: cannot use $_SERVER["QUERY_STRING"] bc is invalid.
			$url .= !empty($url_parts["fragment"]) ? "#" . $url_parts["fragment"] : "";
			
			//prepare curl post data
			$post_data = array(
				"settings" => $this->settings,
				"block_id" => $block_id,
				"url_query" => $url_query,
				"options" => $options,
			);
			
			//for security reasons unset this variables, bc they will not be needed.
			unset($post_data["settings"]["wordpress_request_content_url"]);
			unset($post_data["settings"]["wordpress_request_content_connection_timeout"]);
			unset($post_data["settings"]["wordpress_request_content_encryption_key"]);
			
			//set current_page_url for the curl request
			$current_protocol = isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] === "on" ? "https" : "http";
			$current_page_url = $current_protocol . "://" . (isset($_SERVER["HTTP_HOST"]) ? $_SERVER["HTTP_HOST"] : "") . (isset($_SERVER["REQUEST_URI"]) ? $_SERVER["REQUEST_URI"] : ""); //get current page url
			$post_data["options"]["current_page_url"] = $current_page_url;
			
			//set request method type
			$post_data["options"]["request_method"] = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : null;
			
			//add a new header with an authentication token encrypted with: time() . "_" . md5(serialize($post_data));
			if (!empty($this->settings["wordpress_request_content_encryption_key"])) {
				$serialized = serialize($post_data);
				$str = time() . "_" . md5($serialized) . "_" . $serialized;
				
				$encryption_key = $this->settings["wordpress_request_content_encryption_key"];
				$key = CryptoKeyHandler::hexToBin($encryption_key);
				$cipher_bin = CryptoKeyHandler::encryptText($str, $key);
				$cipher_text = CryptoKeyHandler::binToHex($cipher_bin);
				
				$post_data = array("data" => $cipher_text);
			}
			
			$post = isset($_POST) ? $_POST : null;
			$post = $post ? $post : array();
			$post["phpframework_wordpress_data"] = $post_data;
			
			$url_host = parse_url($url, PHP_URL_HOST);
			$current_host = parse_url($current_page_url, PHP_URL_HOST);
			
			$settings = array(
				"url" => $url, 
				"post" => $post, 
				"cookie" => $current_host == $url_host ? $_COOKIE : null,
				"settings" => array(
					"referer" => isset($_SERVER["HTTP_REFERER"]) ? $_SERVER["HTTP_REFERER"] : null,
					"follow_location" => 0, //must be false - check bellow
					"connection_timeout" => isset($this->settings["wordpress_request_content_connection_timeout"]) ? $this->settings["wordpress_request_content_connection_timeout"] : null,
				)
			);
			
			if (!empty($_SERVER["AUTH_TYPE"]) && !empty($_SERVER["PHP_AUTH_USER"])) {
				$settings["settings"]["http_auth"] = $_SERVER["AUTH_TYPE"];
				$settings["settings"]["user_pwd"] = $_SERVER["PHP_AUTH_USER"] . ":" . (isset($_SERVER["PHP_AUTH_PW"]) ? $_SERVER["PHP_AUTH_PW"] : "");
			}
			
			$previous_redirect = null;
			
			//loop for multiple curl requests in cae exists a redirect url. Note that the follow_location must be false, bc the redirect url will be based in the $current_page_url, which means that we will loose the wordpress_request_content_url connection response. Everytime there is a redirect url, we must replace it with the wordpress_request_content_url, so we can then get the seralized request response.
			while (true) {
				//error_log("\nblock: $block_id\ncurrent_page_url: $current_page_url\n", 3, "/var/www/html/livingroop/default/tmp/test.log");
				//error_log("EXECUTE CURL:".$settings["url"]."\n", 3, "/var/www/html/livingroop/default/tmp/test.log");
				
				$MyCurl = new MyCurl();
				$MyCurl->initSingle($settings);
				$MyCurl->get_contents();
				$data = $MyCurl->getData();
				
				//error_log("END CURL:".$settings["url"]."\n", 3, "/var/www/html/livingroop/default/tmp/test.log");
				
				if (!empty($data[0]["info"]["redirect_url"])) {
					$redirect_url = $data[0]["info"]["redirect_url"];
					//error_log("redirect_url:$redirect_url\n", 3, "/var/www/html/livingroop/default/tmp/test.log");
					
					if ($block_id)
						WordPressUrlsParser::replaceUrlPhpFrameworkBlockId($redirect_url, $block_id);
					
					$pos = strpos($current_page_url, "?");
					$current_page_url_path = $pos !== false ? substr($current_page_url, 0, $pos) : $current_page_url;
					
					$pos = strpos($redirect_url, "?");
					$redirect_url_path = $pos !== false ? substr($redirect_url, 0, $pos) : $redirect_url;
					$wordpress_request_content_url = isset($this->settings["wordpress_request_content_url"]) ? $this->settings["wordpress_request_content_url"] : null;
					
					if ($redirect_url_path == $current_page_url_path || $redirect_url_path == $wordpress_request_content_url) {
						$settings["url"] = $wordpress_request_content_url;
						
						if ($pos !== false)
							$settings["url"] .= (strpos($settings["url"], "?") === false ? "?" : "&") . substr($redirect_url, $pos + 1);
						
						//error_log("new settings url:".$settings["url"]."\n", 3, "/var/www/html/livingroop/default/tmp/test.log");
						
						if ($previous_redirect == $settings["url"]) { //just in case bc infinit loops
							header("Location: $redirect_url");
							return;
						}
						else
							$previous_redirect = $settings["url"];
					}
					else {
						header("Location: $redirect_url");
						return;
					}
				}
				else 
					break;
			}
			
			$response = isset($data[0]["content"]) ? $data[0]["content"] : null;
			$content = unserialize($response);
			
			//error_log("CONTENT OUT:".print_r(array_keys($content), 1), 3, "/var/www/html/livingroop/default/tmp/test.log");
			//error_log(print_r($data, 1), 3, "/var/www/html/livingroop/default/tmp/test.log");
			//error_log(print_r($content, 1), 3, "/var/www/html/livingroop/default/tmp/test.log");
			//error_log(substr($content["results"]["theme_content"], 0, 100), 3, "/var/www/html/livingroop/default/tmp/test.log");
		}
		else {
			$content = $this->getBlockContentDirectly($block_id, $url_query, $options);
			
			if (!$wordpress_already_called) {
				$wordpress_already_called = true;
				$first_wordpress_theme_to_call = $wordpress_theme_to_call;
				$first_wordpress_theme_called = WordPressHacker::getCurrentTheme();
			}
		}
		
		//set block id so the system adds the block id to all urls
		if ($block_id)
			$options["phpframework_block_id"] = $block_id;
		
		//change wordpress urls
		//prepare urls in headers
		WordPressUrlsParser::parseWordPressHeaders(
			isset($content["wordpress_site_url"]) ? $content["wordpress_site_url"] : null,
			isset($content["current_page_url"]) ? $content["current_page_url"] : null,
			$options,
			$this->stop
		);
		
		//prepare urls in results html
		if ($content && !empty($content["results"]))
			$content["results"] = WordPressUrlsParser::prepareArrayWithWordPressUrls($content["results"], $options, $content);
		
		//resets current block id
		$GLOBALS["current_phpframework_block_id"] = null;
		
		//error_log(print_r($content["results"]["theme_content"], 1), 3, "/var/www/html/livingroop/default/tmp/test.log");
		return $content;
	}
	
	/*
	 * Try to avoid calling this method directly bc if wordpress was previously called and the new wordpress template is different than the previous wordpress call, the returned data will not be trustable, bc wordpress was already initialized wi tht first template and the data will be related with this template and not with the new template!
	 * Only call this method in the get_wordpress_content_array.php, bc of infinit loops with curl!
	 *
	 * returns an array
	 */
	public function getBlockContentDirectly($block_id, $url_query, $options = null) {
		if (!$this->stop) {
			//prepare globals vars
			$phpframework_block_id = isset($_GET["phpframework_block_id"]) ? $_GET["phpframework_block_id"] : null;
			$wp_url = isset($_GET["wp_url"]) ? $_GET["wp_url"] : null;
			$wp_file = isset($_GET["wp_file"]) ? $_GET["wp_file"] : null;
			
			//prepare local vars
			$wp_relative_file_path = null;
			$query_string = null;
			$old_get_vars = isset($_GET) ? $_GET : null;
			$old_request_vars = isset($_REQUEST) ? $_REQUEST : null;
			$new_vars = array();
			
			//prepare current page url with block_id
			if (!empty($options["current_page_url"])) //this is iset when the wordpress is called from a Mycurl request
				$current_page_url = $options["current_page_url"];
			else {
				$current_protocol = isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] === "on" ? "https" : "http";
				$current_page_url = $current_protocol . "://" . (isset($_SERVER["HTTP_HOST"]) ? $_SERVER["HTTP_HOST"] : "") . (isset($_SERVER["REQUEST_URI"]) ? $_SERVER["REQUEST_URI"] : ""); //get current page url
			}
			
			WordPressUrlsParser::prepareUrl($current_page_url);
			
			if ($block_id)
				WordPressUrlsParser::replaceUrlPhpFrameworkBlockId($current_page_url, $block_id);
			
			//set cookie url to be used in the redirect function
			$cookie_flags = array("SameSite" => "Strict", "httponly" => true); //SameSite=Strict or SameSite=Lax or SameSite=None. SameSite=None is not advisable, because of CSRF attacks. Please read https://web.dev/samesite-cookies-explained/;
			$cookie_prefix = isset($this->settings["cookies_prefix"]) ? $this->settings["cookies_prefix"] : "";
			
			//set phpframework_url cookie
			CookieHandler::setSafeCookie($cookie_prefix . "_phpframework_url", $current_page_url, 0, "/", $cookie_flags);
			$_COOKIE[$cookie_prefix . "_phpframework_url"] = $current_page_url;
			
			//set other cookies so they can be used in the ajax requests by the WordPressUrlsParser::endCatchingOutput() method.
			//set allowed_wordpress_urls cookie
			$allowed_wordpress_urls = serialize( isset($options["allowed_wordpress_urls"]) ? $options["allowed_wordpress_urls"] : null );
			CookieHandler::setSafeCookie($cookie_prefix . "_allowed_wordpress_urls", $allowed_wordpress_urls, 0, "/", $cookie_flags);
			$_COOKIE[$cookie_prefix . "_allowed_wordpress_urls"] = $allowed_wordpress_urls;
			
			//set parse_wordpress_urls cookie
			$parse_wordpress_urls = !empty($options["parse_wordpress_urls"]) ? 1 : 0;
			CookieHandler::setSafeCookie($cookie_prefix . "_parse_wordpress_urls", $parse_wordpress_urls, 0, "/", $cookie_flags);
			$_COOKIE[$cookie_prefix . "_parse_wordpress_urls"] = $parse_wordpress_urls;
			
			//set parse_wordpress_relative_urls cookie
			$parse_wordpress_relative_urls = !empty($options["parse_wordpress_relative_urls"]) ? 1 : 0;
			CookieHandler::setSafeCookie($cookie_prefix . "_parse_wordpress_relative_urls", $parse_wordpress_relative_urls, 0, "/", $cookie_flags);
			$_COOKIE[$cookie_prefix . "_parse_wordpress_relative_urls"] = $parse_wordpress_relative_urls;
			
			//prepare wordpress settings
			$EVC = $this->EVC;
			
			include $EVC->getConfigPath("config");
			
			//cannot use $project_common_relative_url_prefix directly coming from common/src/config/config.php bc the project/src/config/config.php may change the $project_common_url_prefix, like it happens in the hospital project that JP did in 2020 for FISIRIO with multiple versions. Here is an example: http://hospital.localhost/v3/hospital/... where exists v1, v2, v3...
			$project_common_relative_url_prefix = parse_url($project_common_url_prefix, PHP_URL_PATH);
			$project_common_relative_url_prefix .= substr($project_common_relative_url_prefix, -1) != "/" ? "/" : "";
			
			$wordpress_folder_relative_prefix = WordPressUrlsParser::WORDPRESS_FOLDER_PREFIX . "/" . (isset($this->settings["wordpress_folder"]) ? $this->settings["wordpress_folder"] : "") . "/"; //do not add a slash as the first character.
			$wordpress_folder_path = $EVC->getWebrootPath("common") . $wordpress_folder_relative_prefix;
			$wordpress_request_uri = $project_common_relative_url_prefix . $wordpress_folder_relative_prefix;
			$wordpress_request_file = $this->getWordPressUriFile($wordpress_folder_relative_prefix);
			$wordpress_request_url_prefix = $project_common_url_prefix . $wordpress_folder_relative_prefix;
			//echo "wordpress_folder_relative_prefix:$wordpress_folder_relative_prefix<br>wordpress_folder_path:$wordpress_folder_path<br>wordpress_request_uri:$wordpress_request_uri<br>wordpress_request_file:$wordpress_request_file<br>wordpress_request_url_prefix:$wordpress_request_url_prefix";die();
			
			if (!file_exists($wordpress_folder_path))
				launch_exception(new Exception("WordPress installation with folder '" . (isset($this->settings["wordpress_folder"]) ? $this->settings["wordpress_folder"] : "") . "' doesn't exists!"));
			
			if ($block_id == $phpframework_block_id || !$phpframework_block_id) {
				//error_log("GET in $block_id:".print_r($_GET, 1), 3, "/var/www/html/livingroop/default/tmp/test.log");
				
				if ($wp_url) { //parse url and prepare new url_query
					$wp_url = htmlspecialchars_decode($wp_url); //prepare some vars and decode chars like #038;
					$wp_url = urldecode($wp_url);
					
					$parts = explode("#", $wp_url);
					$wp_url = $parts[0];
					
					$parts = explode("?", $wp_url);
					$url_query = trim($parts[0]);
					$query_string = isset($parts[1]) ? trim($parts[1]) : "";
				}
				else if ($wp_file) { //prepare wordpress file
					$wp_file = htmlspecialchars_decode($wp_file); //prepare some vars and decode chars like #038;
					$wp_file = urldecode($wp_file);
					$wp_relative_file_path = preg_replace("/^\/+/", "", $wp_file); //remove first back-slash
					$wordpress_request_file = $wordpress_request_uri . $wp_relative_file_path;
					$url_query = null;
				}
			}
			//echo "wp_url:$wp_url\n<br>wp_file:$wp_file\n<br>wp_relative_file_path:$wp_relative_file_path\n<br>url_query:$url_query\n<br>";die();
			
			//prepare url_query in case it contains any hash or query string
			if ($url_query && (strpos($url_query, "?") !== false || strpos($url_query, "#") !== false)) {
				$parts = explode("#", $url_query);
				$url_query = $parts[0];
				
				$parts = explode("?", $url_query);
				$url_query = trim($parts[0]);
				$query_string = isset($parts[1]) ? trim($parts[1]) : "";
			}
			
			//prepare new $_GET vars
			if ($query_string) {
				parse_str($query_string, $new_vars);
				
				if ($new_vars) 
					foreach ($new_vars as $k => $v) {
						$_GET[$k] = $v;
						
						if (!isset($_POST[$k]))
							$_REQUEST[$k] = $v;
					}
			}
			
			//error_log("block_id:$block_id\nwp_url:$wp_url\nwp_file:$wp_file\nwp_relative_file_path:$wp_relative_file_path\nurl_query:$url_query\nquery_string:$query_string\n".print_r($_GET, 1), 3, "/var/www/html/livingroop/default/tmp/test.log");
			
			//call WordPressHacker
			$WordPressHacker = new WordPressHacker($wordpress_folder_path, $wordpress_request_uri, $wordpress_request_file, $this->user_authenticated);
			
			if ($wp_relative_file_path) //call wordpress files
				$results = array( $WordPressHacker->callFile($wp_relative_file_path) );
			else
				$results = $WordPressHacker->getContent($url_query, $options);
			
			//backup original $_GET and $_REQUEST vars
			if ($new_vars) {
				$_GET = $old_get_vars;
				$_REQUEST = $old_request_vars;
			}
				
			//reset wp_url and wp_file bc if a page contains 2 wordpress blocks, the wp_url or wp_file should only correspond to one block, this is, should NOT affect both blocks, but instead, only one of them. So we must reset the wp_url and wp_file after they been used!
			if ($block_id == $phpframework_block_id || !$phpframework_block_id) {
				unset($_GET["wp_url"]);
				unset($_GET["wp_file"]);
				//error_log("RESET wp_url and wp_file in $block_id:".print_r($_GET, 1), 3, "/var/www/html/livingroop/default/tmp/test.log");
			}
			
			//get wordpress page url
			$wordpress_site_url = WordPressHacker::getSiteUrl() . "/";
			
			return array(
				"results" => $results,
				"wordpress_folder_relative_prefix" => $wordpress_folder_relative_prefix,
				"current_page_url" => $current_page_url,
				"wordpress_site_url" => $wordpress_site_url,
				"url_query" => $url_query,
			);
		}
	}
	
	//called in the wordpress/wp-includes/pluggable.php file
	//leave prepareRedirectUrl here and DO NOT COPY IT to WordPressUrlsParser, bc the pluggable.php will only call this method if exists and the WordPressCMSBlockHandler class will only exists when called from the phpframework. If the user calls the wordpress directly, the prepareRedirectUrl method should not be called!
	public static function prepareRedirectUrl(&$location, $cookies_prefix) {
		$wordpress_site_url = site_url() . "/";
		
		if (substr($location, 0, strlen($wordpress_site_url)) == $wordpress_site_url && !empty($_COOKIE[$cookies_prefix . "_parse_wordpress_urls"])) {
			//check if phpframework cookie exists
			$phpframework_url = isset($_COOKIE[$cookies_prefix . "_phpframework_url"]) ? $_COOKIE[$cookies_prefix . "_phpframework_url"] : null;
			
			if ($phpframework_url) {
				//clean url with previous vars
				WordPressUrlsParser::prepareUrl($phpframework_url);
				
				$is_php_file = WordPressUrlsParser::isWordPressPHPFile($location);
				$is_raw_file = !$is_php_file && WordPressUrlsParser::isWordPressRawFile($location); //avoids changing images, css, js and other static files
				
				if (!$is_raw_file) {
					$options = array(
						"allowed_wordpress_urls" => unserialize($_COOKIE[$cookies_prefix . "_allowed_wordpress_urls"]),
						"parse_wordpress_urls" => $_COOKIE[$cookies_prefix . "_parse_wordpress_urls"],
						"parse_wordpress_relative_urls" => isset($_COOKIE[$cookies_prefix . "_parse_wordpress_relative_urls"]) ? $_COOKIE[$cookies_prefix . "_parse_wordpress_relative_urls"] : null,
						"phpframework_block_id" => !empty($GLOBALS["current_phpframework_block_id"]) ? $GLOBALS["current_phpframework_block_id"] : (isset($_GET["phpframework_block_id"]) ? $_GET["phpframework_block_id"] : null), //overwrite the block id in the $phpframework_url.
					);
					
					//error_log("old location:$location\n", 3, "/var/www/html/livingroop/default/tmp/test.log");
					
					$location = WordPressUrlsParser::convertUrlToRedirectUrl($wordpress_site_url, $phpframework_url, $location, $options, $is_php_file ? "wp_file" : "wp_url");
					
					//error_log("new location:$location\n", 3, "/var/www/html/livingroop/default/tmp/test.log");
					//echo "location:$location";die();
					//Note: Do not remove the cookie phpframework_url bc the wp_redirect function can be called multiple times inside of the wordpress, and consequent the wp_check_redirect_url function too.
				}
			}
		}
	}
	
	private function getWordPressUriFile($wordpress_folder_relative_prefix) {
		$presentation_id = isset($GLOBALS["presentation_id"]) ? $GLOBALS["presentation_id"] : null;
		$script_name = isset($_SERVER["SCRIPT_NAME"]) ? $_SERVER["SCRIPT_NAME"] : null;
		$system_phpframework = "/__system/layer/presentation/phpframework/webroot/"; //hard code bc this will never be changed.
		
		$parts = explode($system_phpframework, $script_name); //This is used when the this class is called from the __system
		
		if (count($parts) > 1) 
			$prefix_url_file = $parts[0] . "/layer/presentation";
		else {
			$parts = explode("/$presentation_id/webroot/", $script_name);
			$prefix_url_file = "";
			
			if (count($parts) > 1) 
				$prefix_url_file = $parts[0];
			else {
				$parts = explode("/webroot/", $script_name);

				if (count($parts) > 1)
					$prefix_url_file = $parts[0];
				else
					$prefix_url_file = dirname($script_name);
			}
		}
		
		$common_project_name = $this->EVC->getCommonProjectName();
		return $prefix_url_file . "/$common_project_name/webroot/" . $wordpress_folder_relative_prefix . "index.php";
	}
}
?>
