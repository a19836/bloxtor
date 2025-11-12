<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

//This file is called directly in some wordpress files, so we need to create the get_lib and normalize_windows_path_to_linux functions
if (!function_exists("normalize_windows_path_to_linux")) {
	function normalize_windows_path_to_linux($path) { //This function will be used everytime that we use the php code: __FILE__ and __DIR__
		return DIRECTORY_SEPARATOR != "/" ? str_replace(DIRECTORY_SEPARATOR, "/", $path) : $path;
	}
}

if (!function_exists("get_lib")) {
	function get_lib($path) {
		$path = strpos($path, "lib.") === 0 ? substr($path, strlen("lib.")) : $path;
		return dirname(dirname(dirname(dirname(normalize_windows_path_to_linux(__DIR__))))) . "/" . str_replace(".", "/", $path) . ".php";
	}
}

include_once get_lib("org.phpframework.cms.wordpress.WordPressUrlsParser");

class WordPressRequestHandler {
	public $parse_output;
	
	private $wordpress_folder;
	private $cookies_prefix;
	private $start_catching_output = false;
	
	public function __construct($wordpress_folder, $cookies_prefix) {
		$this->wordpress_folder = $wordpress_folder;
		$this->cookies_prefix = $cookies_prefix ? $cookies_prefix : $wordpress_folder;
		
		$this->parse_output = $this->isPHPFrameworkRequestToParse();
	}
	
	/* AJAX */
	
	public function isPHPFrameworkRequestToParse() {
		global $phpframework_options, $current_phpframework_result_key;
		
		//if exists phpframework_options or current_phpframework_result_key, it means that the wordpress index.php file was called through the WordPressHacker class. So we don't need to do this.
		if (isset($phpframework_options) || isset($current_phpframework_result_key))
			return false;
		
		/*
		 * If is not called from WordPressHacker but was a phpframework_url page that called this page: it could be an ajax request or some other request
		 *
		 * There is a case where this can be weird, which is:
		 * 1st: open a wordpress block page from phpframework, like: http://jplpinto.localhost/test/test/wordpress/test?wp_url=cart%2F
		 *	This sets the $_COOKIE["phpframework_url"] to the current page url
		 * 2nd: then open the phpframework block settings, like: http://jplpinto.localhost/__system/phpframework/presentation/edit_block?bean_name=PresentationPLayer&bean_file_name=presentation_pl.xml&path=test/src/block/test/wordpress/test.php&edit_block_type=simple
		 *	Then this sets the $_COOKIE["phpframework_url"] to the __system url
		 * 3rd: then go back to the first page and click in some link... What will happen?
		 * 
		 * R: It will open the last $_COOKIE["phpframework_url"], which is the __system url. BUT THIS IS OK! DON'T WORRY WITH THIS BC THIS WILL NOT HAPPEN IN PROD BC THE FINAL END-USER DOESN'T HAVE ACCESS TO THE __system UI.
		*/
		if (!empty($_SERVER["HTTP_REFERER"]) && !empty($_COOKIE[$this->cookies_prefix . "_phpframework_url"])) {
			$referer = explode("#", $_SERVER["HTTP_REFERER"]);
			$referer = explode("?", $referer[0]);
			$referer = $referer[0];
			
			$phpframework_url = explode("#", $_COOKIE[$this->cookies_prefix . "_phpframework_url"]);
			$phpframework_url = explode("?", $phpframework_url[0]);
			$phpframework_url = $phpframework_url[0];
			
			//echo "referer:$referer\n";
			//echo "phpframework_url:$phpframework_url\n";
			
			return $phpframework_url == $referer;
		}
		
		return false;
	}
	
	public function startCatchingOutput() {
		if ($this->parse_output) {
			register_shutdown_function(array($this, "endCatchingOutput")); //register shutdown function bc the wordpress template may have exit or die function calls...
			$this->start_catching_output = true;
			
			ob_start(null, 0);
		}
	}
	
	public function endCatchingOutput() {
		if ($this->start_catching_output) { //We need to use the $this->start_catching_output bc PHP doesn't have any unregister_shutdown_function function.
			$this->start_catching_output = false; //if for some reason, this gets called again, it will not do nothing. This happens in case we call multiple times the wordpress, then we will have multiples calls to register_shutdown_function. In this case we only want to call this once.
			
			$html = ob_get_contents();
			ob_end_clean();
			
			//change wordpress urls
			//prepare vars
			$wordpress_site_url = site_url() . "/"; //site_url is in wordpress/wp-includes/link-template.php
			$current_page_url = isset($_COOKIE[$this->cookies_prefix . "_phpframework_url"]) ? $_COOKIE[$this->cookies_prefix . "_phpframework_url"] : null;
			$allowed_wordpress_urls = isset($_COOKIE[$this->cookies_prefix . "_allowed_wordpress_urls"]) ? $_COOKIE[$this->cookies_prefix . "_allowed_wordpress_urls"] : null;
			$parse_wordpress_urls = isset($_COOKIE[$this->cookies_prefix . "_parse_wordpress_urls"]) ? $_COOKIE[$this->cookies_prefix . "_parse_wordpress_urls"] : null;
			$parse_wordpress_relative_urls = isset($_COOKIE[$this->cookies_prefix . "_parse_wordpress_relative_urls"]) ? $_COOKIE[$this->cookies_prefix . "_parse_wordpress_relative_urls"] : null;
			
			$content = array(
				"wordpress_site_url" => $wordpress_site_url,
				"current_page_url" => $current_page_url
			);
			
			$options = array(
				"allowed_wordpress_urls" => unserialize($allowed_wordpress_urls),
				"parse_wordpress_urls" => $parse_wordpress_urls,
				"parse_wordpress_relative_urls" => $parse_wordpress_relative_urls,
			);
			
			//prepare urls in headers
			WordPressUrlsParser::parseWordPressHeaders($content["wordpress_site_url"], $content["current_page_url"], $options);
			
			//prepare urls in results html
			if ($html) {
				$json_obj = substr($html, 0, 1) == "{" && substr($html, -1) == "}" ? json_decode($html, true) : null;
				
				if ($json_obj) {
					$json_obj = WordPressUrlsParser::prepareArrayWithWordPressUrls($json_obj, $options, $content);
					$html = json_encode($json_obj);
				}
				else
					$html = WordPressUrlsParser::parseWordPressHtml($html, $content["wordpress_site_url"], $content["current_page_url"], $options);
			}
			
			//print_r($_SERVER);
			echo $html;
		}
	}
}
?>
