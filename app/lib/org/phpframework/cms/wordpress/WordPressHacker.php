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

include_once get_lib("org.phpframework.util.web.html.HtmlStringHandler");

/*
 * More info about wordpress functions in https://developer.wordpress.org/themes/references/list-of-template-tags/
 * TODO: add more functions from the link above to this class, like get serach form, get login, etc... Don't forget to add the $this->user_authenticated validation if these methods are not public.
 */
class WordPressHacker {
	private $wordpress_path;
	private $wordpress_request_uri; 
	private $wordpress_request_file;
	private $user_authenticated;
	
	private $server_bkp = null;
	
	//if $user_authenticated == true, it means we have access to all the methods of this class
	public function __construct($wordpress_path, $wordpress_request_uri, $wordpress_request_file, $user_authenticated = true) {
		$this->wordpress_path = trim($wordpress_path);
		$this->wordpress_request_uri = trim($wordpress_request_uri);
		$this->wordpress_request_file = trim($wordpress_request_file);
		$this->user_authenticated = $user_authenticated;
		
		$this->wordpress_path .= substr($this->wordpress_path, -1) == "/" ? "" : "/";
		
		if (!file_exists($this->wordpress_path))
			launch_exception(new Exception("WordPress installation doesn't exists!"));
	}
	
	/* CONTENT */
	
	public function callFile($relative_file_path) {
		global $phpframework_wp_request_uri;
		
		$phpframework_wp_request_uri = $this->wordpress_request_uri; //used in the wordpress/wp-config.php. This is very important bc of the relative request uris
		
		$this->startServerSimulation($relative_file_path);
		
		$fp = $this->wordpress_path . $relative_file_path;
		
		if (file_exists($fp)) {
			ob_start(null, 0);
			
			include $fp;
			
			$html = ob_get_contents();
			ob_end_clean();
		}
		else //call wordpress error page
			$html = self::get404PageHtml();
		
		$this->endServerSimulation();
		
		return $html;
	}
	
	//used to get functions on the current selected wordpress theme too
	public function getContent($url_query, $options) {
		global $phpframework_template, $phpframework_options, $phpframework_results, $current_phpframework_result_key, $phpframework_wp_request_uri, $show_admin_bar;
		
		$results = array();
		$phpframework_template = self::getPHPFrameworkFromOptions($options); //set global var to the phpframework template, so the phpframework plugin change the default selected template.
		$phpframework_options = $options;
		$phpframework_results = array();
		$current_phpframework_result_key = "theme_content";
		$phpframework_wp_request_uri = $this->wordpress_request_uri; //used in the wordpress/wp-config.php. This is very important bc of the relative request uris.
		
		//error_log("url_query:$url_query\n", 3, "/var/www/html/livingroop/default/tmp/test.log");
		
		$this->startServerSimulation($url_query);
		
		ob_start(null, 0);
		
		$show_admin_bar = false; //hide the admin top bar if the user is logged into the wordpress admin panel.
		
		include $this->wordpress_path . "index.php";
		
		//$current_phpframework_result_key could be theme_content or after_footer
		$obgc = ob_get_contents();
		$current_phpframework_result_key_label = ucwords(str_replace('_', ' ', $current_phpframework_result_key));
		$obgc = "<!-- phpframework:template:region: \"Before $current_phpframework_result_key_label\" -->$obgc<!-- phpframework:template:region: \"After $current_phpframework_result_key_label\" -->";
		
		$phpframework_results[$current_phpframework_result_key] = $obgc;
		$phpframework_results["full_page_html"] = $obgc;
		
		ob_end_clean();
		
		//execute options after resetting global vars
		if ($options) {
			$phpframework_options = null; //reset global so it can execute options without ob_start functions inside of get_header, get_footer and get_sidebar functions
			
			$results = $this->executeOptions($options, $phpframework_results);
		}
		
		$this->endServerSimulation();
		
		//reset globals
		$phpframework_results = $current_phpframework_result_key = null;
		
		/*setcookie("wordpress_89092b51f3dc2d3119fb86ae6efe3338", "", time() - 3600, "/", $_SERVER["HTTP_HOST"]);
		setcookie("wordpress_logged_in_89092b51f3dc2d3119fb86ae6efe3338", "", time() - 3600, "/", $_SERVER["HTTP_HOST"]);
		setcookie("wordpress_20d1a3921414b92aae5b808fd7055ba5", "", time() - 3600, "/", $_SERVER["HTTP_HOST"]);
		setcookie("wordpress_logged_in_20d1a3921414b92aae5b808fd7055ba5", "", time() - 3600, "/", $_SERVER["HTTP_HOST"]);
		setcookie("wp-settings-1", "", time() - 3600, "/", $_SERVER["HTTP_HOST"]);
		setcookie("wp-settings-time-1", "", time() - 3600, "/", $_SERVER["HTTP_HOST"]);*/
		
		return $results;
	}
	
	/* OPTIONS */
	
	public static function getPHPFrameworkFromOptions($options) {
		if ($options && !empty($options["phpframework_template"]))
			return $options["phpframework_template"] === true ? "phpframework" : $options["phpframework_template"];
		
		return null;
	}
	
	public function executeOptions($options, $results = null) {
		$htmls = $results ? $results : array(); //set results if exists as the default $htmls
		
		if ($options)
			foreach ($options as $k => $v)
				if ($v)
					switch($k) {
						//header
						case "header":
							$htmls["header"] = !empty($htmls["header"]) ? $htmls["header"] : self::getTemplateHeaderHtml();
							break;
						//footer
						case "footer":
							$htmls["footer"] = !empty($htmls["footer"]) ? $htmls["footer"] : self::getTemplateFooterHtml();
							break;
						
						//post
						case "post":
							$pretty_comments = !empty($v["comments"]) && is_array($v["comments"]) && $v["comments"]["pretty"];
							$raw_comments = !empty($v["comments"]) && is_array($v["comments"]) && $v["comments"]["raw"];
							$withcomments = $GLOBALS["withcomments"] = "1"; //very important otherwise the comments_template method won't display anything. This is a wordpress variable!

							if ( have_posts() ) {
								while ( have_posts() ) {
									the_post(); //the_post is in wordpress/wp-includes/query.php
									
									$post = array();
									
									if (!empty($v["title"]))
										$post["title"] = self::getCurrentPostTitleHtml();
									
									if (!empty($v["content"]))
										$post["content"] = self::getCurrentPostContentHtml();
									
									if (!empty($v["comments"])) {
										if ($raw_comments || !$pretty_comments)
											$post["raw_comments"] = self::getCurrentPostCommentsWithAddFormRawHtml();
										
										if ($pretty_comments) {
											$comments_from_theme = false;
											$post_id = null;
											
											if (isset($v["comments"]["pretty"]) && is_array($v["comments"]["pretty"])) {
												$comments_from_theme = isset($v["comments"]["pretty"]["comments_from_theme"]) ? $v["comments"]["pretty"]["comments_from_theme"] : null;
												$post_id = self::getCurrentPostId();
											}
											
											$post["pretty_comments"] = $comments_from_theme && $post_id && !empty($htmls["theme_comments"]) && !empty($htmls["theme_comments"][$post_id]) ? $htmls["theme_comments"][$post_id][0] : self::getCurrentPostCommentsWithAddFormPrettyHtml();
										}
									}
									
									$htmls["posts"][] = $post;
								}
							}
							else {
								$post = array();
								
								if (!empty($v["title"]))
									$post["title"] = self::getCurrentPostTitleHtml();
								
								if (!empty($v["content"]))
									$post["content"] = self::getCurrentPostContentHtml();
								
								if (!empty($v["comments"])) {
									if ($raw_comments || !$pretty_comments)
										$post["raw_comments"] = self::getCurrentPostCommentsWithAddFormRawHtml();
									
									if ($pretty_comments) {
										$comments_from_theme = false;
										$post_id = null;
										
										if (isset($v["comments"]["pretty"]) && is_array($v["comments"]["pretty"])) {
											$comments_from_theme = isset($v["comments"]["pretty"]["comments_from_theme"]) ? $v["comments"]["pretty"]["comments_from_theme"] : null;
											$post_id = self::getCurrentPostId();
										}
										
										$post["pretty_comments"] = $comments_from_theme && $post_id && !empty($htmls["theme_comments"]) && !empty($htmls["theme_comments"][$post_id]) ? $htmls["theme_comments"][$post_id][0] : self::getCurrentPostCommentsWithAddFormPrettyHtml();
									}
								}
								
								$htmls["posts"][] = $post;
							}
							break;
						
						//menu
						case "default_menu":
							$menu_from_theme = is_array($v) && isset($v["menu_from_theme"]) ? $v["menu_from_theme"] : false;
							$htmls["default_menu"] = $menu_from_theme && !empty($htmls["theme_menus"]) && !empty($htmls["theme_menus"][0]) ? $htmls["theme_menus"][0][0] : self::getDefaultMenuHtml();
							break;
						
						case "menu_name":
							$menu_from_theme = false;
							
							if (is_array($v)) {
								$menu_from_theme = isset($v["menu_from_theme"]) ? $v["menu_from_theme"] : null;
								$v = isset($v["name"]) ? $v["name"] : null;
							}
							
							$htmls["menu"] = $menu_from_theme && !empty($htmls["theme_menus"]) && !empty($htmls["theme_menus"][$v]) ? $htmls["theme_menus"][$v][0] : self::getMenuHtml($v);
							break;
						
						case "menus_name":
							$v = is_array($v) ? $v : array($v);
							
							foreach ($v as $menu_name) {
								$menu_from_theme = false;
								
								if (is_array($menu_name)) {
									$menu_from_theme = isset($menu_name["menu_from_theme"]) ? $menu_name["menu_from_theme"] : null;
									$menu_name = isset($menu_name["name"]) ? $menu_name["name"] : null;
								}
								
								$htmls["menus"][] = $menu_from_theme && !empty($htmls["theme_menus"]) && !empty($htmls["theme_menus"][$menu_name]) ? $htmls["theme_menus"][$menu_name][0] : self::getMenuHtml($menu_name);
							}
							break;
						
						//menu location
						case "menu_location_name":
							$menu_from_theme = false;
							
							if (is_array($v)) {
								$menu_from_theme = isset($v["menu_from_theme"]) ? $v["menu_from_theme"] : null;
								$v = isset($v["name"]) ? $v["name"] : null;
							}
							
							if ($menu_from_theme) {
								$menu_id = self::getMenuIdByLocation($v);
								$htmls["menu_location"] = $menu_id && !empty($htmls["theme_menus"]) && !empty($htmls["theme_menus"][$menu_id]) ? $htmls["theme_menus"][$menu_id][0] : self::getMenuHtml($menu_id);
							}
							else
								$htmls["menu_location"] = self::getMenuHtmlByLocation($v);
							
							break;
						
						case "menu_locations_name":
							$v = is_array($v) ? $v : array($v);
							
							foreach ($v as $menu_location_name) {
								$menu_from_theme = false;
								
								if (is_array($menu_location_name)) {
									$menu_from_theme = isset($menu_location_name["menu_from_theme"]) ? $menu_location_name["menu_from_theme"] : null;
									$menu_location_name = isset($menu_location_name["name"]) ? $menu_location_name["name"] : null;
								}
								
								if ($menu_from_theme) {
									$menu_id = self::getMenuIdByLocation($menu_location_name);
									$htmls["menu_locations"][] = $menu_id && !empty($htmls["theme_menus"]) && !empty($htmls["theme_menus"][$menu_id]) ? $htmls["theme_menus"][$menu_id][0] : self::getMenuHtml($menu_id);
								}
								else
									$htmls["menu_locations"][] = self::getMenuHtmlByLocation($menu_location_name);
							}
							break;
						
						//side_bar
						case "default_side_bar":
							$side_bar_from_theme = is_array($v) && isset($v["side_bar_from_theme"]) ? $v["side_bar_from_theme"] : false;
							$htmls["default_side_bar"] = $side_bar_from_theme && !empty($htmls["theme_side_bars"]) && !empty($htmls["theme_side_bars"][0]) ? $htmls["theme_side_bars"][0][0] : self::getDefaultSideBarHtml();
							break;
							
						case "side_bar_name":
							$side_bar_from_theme = false;
							
							if (is_array($v)) {
								$side_bar_from_theme = isset($v["side_bar_from_theme"]) ? $v["side_bar_from_theme"] : null;
								$v = isset($v["name"]) ? $v["name"] : null;
							}
							
							$htmls["side_bar"] = $side_bar_from_theme && !empty($htmls["theme_side_bars"]) && !empty($htmls["theme_side_bars"][$v]) ? $htmls["theme_side_bars"][$v][0] : self::getSideBarHtml($v);
							break;
							
						case "side_bars_name":
							$v = is_array($v) ? $v : array($v);
							
							foreach ($v as $side_bar_name) {
								$side_bar_from_theme = false;
								
								if (is_array($side_bar_name)) {
									$side_bar_from_theme = isset($side_bar_name["side_bar_from_theme"]) ? $side_bar_name["side_bar_from_theme"] : null;
									$side_bar_name = isset($side_bar_name["name"]) ? $side_bar_name["side_bar_from_theme"] : null;
								}
								
								$htmls["side_bars"][] = $side_bar_from_theme && !empty($htmls["theme_side_bars"]) && !empty($htmls["theme_side_bars"][$side_bar_name]) ? $htmls["theme_side_bars"][$side_bar_name][0] : self::getSideBarHtml($side_bar_name);
							}
							
							break;
						
						//widget options
						case "widget_options":
							if ($this->user_authenticated) {
								$widget_instance = isset($v["widget_instance"]) ? $v["widget_instance"] : null;
								
								if (!empty($v["widget_id"]))
									$htmls["widget_options"] = self::getWidgetControlOptionsById($v["widget_id"], $widget_instance);
								else if (!empty($v["widget_class"]))
									$htmls["widget_options"] = self::getWidgetControlOptionsByClass($v["widget_class"], $widget_instance);
							}
							break;
						
						case "widgets_options":
							if ($this->user_authenticated) {
								$v = is_array($v) ? $v : array($v);
								
								foreach ($v as $widget_options) {
									$widget_instance = isset($widget_options["widget_instance"]) ? $widget_options["widget_instance"] : null;
									
									if (!empty($widget_options["widget_id"]))
										$htmls["widgets_options"][] = self::getWidgetControlOptionsById($widget_options["widget_id"], $widget_instance);
									else if (!empty($widget_options["widget_class"]))
										$htmls["widgets_options"][] = self::getWidgetControlOptionsByClass($widget_options["widget_class"], $widget_instance);
								}
							}
							break;
						
						//widget display
						case "widget_display":
							$widget_instance = isset($v["widget_instance"]) ? $v["widget_instance"] : null;
							$widget_args = isset($v["widget_args"]) ? $v["widget_args"] : null;
							
							if ($v["widget_id"])
								$htmls["widget"] = self::getWidgetHtmlById($v["widget_id"], $widget_instance, $widget_args);
							else if ($v["widget_class"])
								$htmls["widget"] = self::getWidgetHtmlByClass($v["widget_class"], $widget_instance, $widget_args);
							break;
						
						case "widgets_display":
							$v = is_array($v) ? $v : array($v);
							
							foreach ($v as $widget_options) {
								$widget_instance = isset($widget_options["widget_instance"]) ? $widget_options["widget_instance"] : null;
								$widget_args = isset($widget_options["widget_args"]) ? $widget_options["widget_args"] : null;
								
								if ($widget_options["widget_id"])
									$htmls["widgets"][] = self::getWidgetHtmlById($widget_options["widget_id"], $widget_instance, $widget_args);
								else if ($widget_options["widget_class"])
									$htmls["widgets"][] = self::getWidgetHtmlByClass($widget_options["widget_class"], $widget_instance, $widget_args);
							}
							break;
						
						//pages list
						case "pages_list":
							$htmls["pages_list"] = self::getPagesListHtml( is_array($v) && isset($v["args"]) ? $v["args"] : null );
							break;
						
						//functions
						case "functions":
							$htmls["functions"] = $this->executeFunctions($v);
							break;
					}
		
		return $htmls;
	}
			
	public function executeFunctions($functions) {
		$results = array();
		
		//only call functions if $this->user_authenticated is true
		if ($functions && $this->user_authenticated) {
			$functions = is_array($functions) ? $functions : array($functions);
			$curr_class = get_class($this);
			
			foreach ($functions as $function) {
				if ($function && !empty($function["name"]) && method_exists($curr_class, $function["name"])) {
					$callback = array($curr_class, $function["name"]);
					
					if (!isset($function["args"]))
						$results[] = call_user_func($callback);
					else if (is_array($function["args"])) {
						$function["args"] = array_values($function["args"]);
						$results[] = @call_user_func_array($callback, $function["args"]); //Note that the @ is very important here bc in PHP 8 this gives an warning, this is: 'Warning: Array to string conversion in...'
					}
					else
						$results[] = @call_user_func($callback, $function["args"]);
				}
				else 
					$results[] = null;
			}
		}
		
		return $results;
	}
	
	/* SERVER SIMULATION */
	
	/*
	 * Original $_SERVER when access directly the wordpress:
		Array
		(
		    [CONTEXT_DOCUMENT_ROOT] => /home3/jplp4686/public_html
		    [CONTEXT_PREFIX] => 
		    [DOCUMENT_ROOT] => /home3/jplp4686/public_html
		    [GATEWAY_INTERFACE] => CGI/1.1
		    [HTTP_ACCEPT_ENCODING] => gzip, deflate
		    [HTTP_ACCEPT_LANGUAGE] => en-US,en;q=0.9,es;q=0.8,pt;q=0.7,la;q=0.6
		    [HTTP_CONNECTION] => keep-alive
		    [HTTP_COOKIE] => wordpress_test_cookie=WP+Cookie+check; wordpress_logged_in_aafcc8e980edfa62fe7299fb2dcb0c4b=admin%7C1604509989%7CKBriQjLzYHgGwHws87tFIXz6Vkmq1vRl9JuLFQCLjAb%7C79ff4cadd95ad22243e9703fd9137b077245ff2b55887889c312ea07e53dffa8; tk_ai=woo%3AOeoNbKbQ%2BN2WU7kZEkLatuJk; wp-settings-1=editor%3Dtinymce%26posts_list_mode%3Dlist%26libraryContent%3Dbrowse; wp-settings-time-1=1604053334; wordpress_test_cookie=WP+Cookie+check; optimizelyEndUserId=oeu1594549653343r0.3398387643148353; _ga=GA1.2.1189568872.1594549669; _fbp=fb.1.1594549668785.1995052073; __utma=25239014.1189568872.1594549669.1595666120.1595666120.1; __utmz=25239014.1595666120.1.1.utmcsr=(direct)|utmccn=(direct)|utmcmd=(none); timezone=Europe/London; _hjid=cf102f63-bb36-45fd-8f96-0d183473b6e0; _gcl_au=1.1.1188642076.1603300129; notify_stamp=1603303130056; notify_count=0; notify_flag=notify_set; _gid=GA1.2.8035722.1604167156; _uetsid=ca672c201ba211eb885c150e6664ba95; _uetvid=06de4ba00a2611eb935ae98285b200b3
		    [HTTP_HOST] => jplpinto.com
		    [HTTP_UPGRADE_INSECURE_REQUESTS] => 1
		    [HTTP_USER_AGENT] => Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/86.0.4240.75 Safari/537.36
		    [PATH] => /bin:/usr/bin
		    [PHPRC] => /home3/jplp4686/public_html/others/sites/to_remove/
		    [PHP_INI_SCAN_DIR] => /opt/php70/bin/php-cgi/etc:/opt/php70/bin/php-cgi/etc/php.d:.
		    [QUERY_STRING] => 
		    [REDIRECT_SCRIPT_URI] => http://jplpinto.com/others/sites/to_remove/wordpress/2020/10/21/hello-world/
		    [REDIRECT_SCRIPT_URL] => /others/sites/to_remove/wordpress/2020/10/21/hello-world/
		    [REDIRECT_STATUS] => 200
		    [REDIRECT_UNIQUE_ID] => X555ikV3FV1tbN47dEKPDwAAAng
		    [REDIRECT_URL] => /others/sites/to_remove/wordpress/2020/10/21/hello-world/
		    [REMOTE_ADDR] => 5.249.40.110
		    [REMOTE_PORT] => 32892
		    [REQUEST_METHOD] => GET
		    [REQUEST_SCHEME] => http
		    [REQUEST_URI] => /others/sites/to_remove/wordpress/2020/10/21/hello-world/
		    [SCRIPT_FILENAME] => /home3/jplp4686/public_html/others/sites/to_remove/index.php
		    [SCRIPT_NAME] => /others/sites/to_remove/index.php
		    [SCRIPT_URI] => http://jplpinto.com/others/sites/to_remove/wordpress/2020/10/21/hello-world/
		    [SCRIPT_URL] => /others/sites/to_remove/wordpress/2020/10/21/hello-world/
		    [SERVER_ADDR] => 192.185.48.178
		    [SERVER_ADMIN] => webmaster@jplpinto.com
		    [SERVER_NAME] => jplpinto.com
		    [SERVER_PORT] => 80
		    [SERVER_PROTOCOL] => HTTP/1.1
		    [SERVER_SIGNATURE] => 
		    [SERVER_SOFTWARE] => Apache
		    [UNIQUE_ID] => X555ikV3FV1tbN47dEKPDwAAAng
		    [PHP_SELF] => /others/sites/to_remove/index.php
		    [REQUEST_TIME_FLOAT] => 1604221323.1869
		    [REQUEST_TIME] => 1604221323
		    [argv] => Array
			   (
			   )

		    [argc] => 0
		)	
	 */
	private function startServerSimulation($url_query) {
		//echo "<pre>";print_r($_SERVER);die();
		
		if (!$this->server_bkp) {
			$this->server_bkp = array();
			
			$arr = array(
				"REQUEST_URI", 
				"REDIRECT_SCRIPT_URI", 
				"REDIRECT_SCRIPT_URL", 
				"REDIRECT_URL", 
				"SCRIPT_URI", 
				"SCRIPT_URL", 
			);
			$current_request_uri = isset($_SERVER["REQUEST_URI"]) ? $_SERVER["REQUEST_URI"] : null;
			$wordpress_request_uri = $this->wordpress_request_uri . preg_replace("/^\/+/", "", $url_query);
			
			$pos = strpos($current_request_uri, "?");
			$current_request_uri = $pos !== false ? substr($current_request_uri, 0, $pos) : $current_request_uri;
			
			$pos = strpos($current_request_uri, "#");
			$current_request_uri = $pos !== false ? substr($current_request_uri, 0, $pos) : $current_request_uri;
			
			foreach ($arr as $item) 
				if (isset($_SERVER[$item])) {
					$this->server_bkp[$item] = $_SERVER[$item];
					$_SERVER[$item] = str_replace($current_request_uri, $wordpress_request_uri, $_SERVER[$item]);
					$_SERVER[$item] = preg_replace("/(\?|&)(phpframework_block_id|wp_url|wp_file)=([^&]*)/", "\${1}", $_SERVER[$item]);
					$_SERVER[$item] = preg_replace("/&+/", "&", $_SERVER[$item]);
					$_SERVER[$item] = preg_replace("/\?&/", "?", $_SERVER[$item]);
					$_SERVER[$item] = preg_replace("/[&\?]+$/", "", $_SERVER[$item]); //remove last & or ? if exists, otherwise the wordpress will remove it and reload the page again. If this is not removed, we have an infinit loop
					$_SERVER[$item] = preg_replace("/[&\?]+#/", "#", $_SERVER[$item]); //remove last & or ? if exists, otherwise the wordpress will remove it and reload the page again. If this is not removed, we have an infinit loop
				}
			
			$arr = array(
				"SCRIPT_FILENAME", 
				"SCRIPT_NAME", 
				"PHP_SELF",
			);
			$current_request_file = isset($_SERVER["SCRIPT_NAME"]) ? $_SERVER["SCRIPT_NAME"] : null;
			$wordpress_request_file = $this->wordpress_request_file;
			foreach ($arr as $item)
				if (isset($_SERVER[$item])) {
					$this->server_bkp[$item] = $_SERVER[$item];
					$_SERVER[$item] = str_replace($current_request_file, $wordpress_request_file, $_SERVER[$item]);
				}
			
			/*echo "current_request_uri:$current_request_uri\n<br>
			wordpress_request_uri:$wordpress_request_uri\n<br>
			current_request_file:$current_request_file\n<br>
			wordpress_request_file:$wordpress_request_file\n<br>";*/
		}
		
		//echo "<pre>";print_r($_SERVER);echo "</pre>";die();
		//error_log(print_r($_SERVER, 1)."\nurl_query:$url_query\ncurrent_request_uri:$current_request_uri\nwordpress_request_uri:$wordpress_request_uri\ncurrent_request_file:$current_request_file\nwordpress_request_file:$wordpress_request_file\n", 3, "/var/www/html/livingroop/default/tmp/test.log");
	}
	
	private function endServerSimulation() {
		if ($this->server_bkp)
			foreach ($this->server_bkp as $k => $v)
				$_SERVER[$k] = $this->server_bkp[$k];
		
		$this->server_bkp = null;
	}
	
	/* TEMPLATE FUNCTIONS */
	
	public static function getTemplateHeaderHtml() {
		ob_start(null, 0);
		
		get_header(); //get_header is in wordpress/wp-includes/general-template.php
		
		$html = ob_get_contents();
		ob_end_clean();
		
		return $html;
	}
	
	public static function getTemplateFooterHtml() {
		ob_start(null, 0);
		
		get_footer(); //get_footer is in wordpress/wp-includes/general-template.php
		
		$html = ob_get_contents();
		ob_end_clean();
		
		return $html;
	}
	
	/* POST FUNCTIONS */
	
	public static function getCurrentPostId() {
		global $post;
		
		return $post->ID;
	}
	
	public static function getCurrentPostTitleHtml() {
		return the_title('', '', false); //the_title is in wordpress/wp-includes/post-template.php
	}
	
	public static function getCurrentPostContentHtml() {
		ob_start(null, 0);
		
		the_content(); //the_content is in wordpress/wp-includes/post-template.php
		
		$html = ob_get_contents();
		ob_end_clean();
		
		return $html;
	}
	
	/* COMMENTS FUNCTIONS */
	
	public static function getPostCommentsHtml($post_id) {
		$html = "";
		$comments = get_comments( array('post_id' => $post_id) );
		
		if ($comments) { //Do not use the have_comments() method bc it does not work!
			$comment_order = strtolower(get_settings("comment_order"));
			
			//wp_list_comments is in wordpress/wp-includes/comment-template.php
			$html = wp_list_comments(array(
			    'echo' => false,
			    //'per_page' => 10, //Allow comment pagination
			    'reverse_top_level' => $comment_order == "asc" //important so it can show the same thing that the comments_template();
			), $comments);
		}
		
		return $html;
	}
	
	public static function getNewCommentForm() {
		ob_start(null, 0);
		
		//comment_form is in wordpress/wp-includes/comment-template.php
		comment_form();
		
		$html = ob_get_contents();
		ob_end_clean();
		
		return $html;
	}
	
	public static function isNewCommentFormAllowed() {
		return comments_open() || pings_open();
	}
	
	public static function getCurrentPostCommentsHtml() {
		global $post;
		
		return self::getPostCommentsHtml($post->ID);
	}
	
	public static function getCurrentPostCommentsWithAddFormRawHtml() {
		$html = self::getCurrentPostCommentsHtml();
		
		if (self::isNewCommentFormAllowed())
			$html .= self::getNewCommentForm();
		
		return $html;
	}
	
	public static function getCurrentPostCommentsWithAddFormPrettyHtml() {
		ob_start(null, 0);
		
		//Get wp-comments.php template
		comments_template(); //comments_template is in wordpress/wp-includes/comment-template.php
		
		$html = ob_get_contents();
		ob_end_clean();
		
		return $html;
	}

	/* MENUS FUNCTIONS */
	
	public static function getMenuHtml($menu_name) {
		//wp_get_nav_menu_object is in wordpress/wp-includes/nav-menu.php
		$menu = wp_get_nav_menu_object($menu_name); //very important to check if exists first, otherwise the wp_nav_menu will get the default menu if not exists.
		
		if ($menu) {
			ob_start(null, 0);
			
			//wp_nav_menu is in wordpress/wp-includes/nav-menu-template.php
			$html = wp_nav_menu(array('menu' => $menu_name, 'echo' => false));
			
			$html .= ob_get_contents(); //just in case someone changes the "echo" arg to true
			ob_end_clean();
		}
			
		return $html;
	}
	
	public static function getDefaultMenuHtml() {
		ob_start(null, 0);
		
		//wp_nav_menu is in wordpress/wp-includes/nav-menu-template.php
		$html = wp_nav_menu(array('echo' => false));
		
		$html .= ob_get_contents(); //just in case someone changes the "echo" arg to true
		ob_end_clean();
		
		return $html;
	}
	
	public static function getMenuHtmlByLocation($location_name) {
		$menu_id = self::getMenuIdByLocation($location_name);
		return $menu_id ? self::getMenuHtml($menu_id) : null;
	}
	
	public static function getMenuIdByLocation($location_name) {
		$locations = self::getMenusByLocations();
		
		if ($locations && isset($locations[$location_name]))
			return $locations[$location_name];
	}
	
	public static function getMenusByLocations() {
		return get_nav_menu_locations(); //get_nav_menu_locations is in wordpress/wp-includes/nav-menu.php. Must be get_nav_menu_locations and not get_registered_nav_menus, bc the get_registered_nav_menus only returns the available locations without menus related
	}
	
	public static function getAvailableMenuLocations() {
		// Get the nav menu based on the theme_location.
		//get_registered_nav_menus is in wordpress/wp-includes/nav-menu.php
		return get_registered_nav_menus(); //must be get_registered_nav_menus and not get_nav_menu_locations, bc the get_nav_menu_locations only returns the locations with menus related
	}
	
	public static function getAvailableMenus() {
		//wp_get_nav_menus is in wordpress/wp-includes/nav-menu.php
		return wp_get_nav_menus();
	}
	
	/* SIDEBARS FUNCTIONS */

	public static function getAvailableSideBars() {
		//$wp_registered_sidebars and wp_get_widget_defaults is in wordpress/wp-includes/widgets.php
		global $wp_registered_sidebars;
		
		return $wp_registered_sidebars; //Do not use wp_get_widget_defaults() bc it returns only the sidebars id;
	}

	public static function getAvailableSideBarsWithWidgets() {
		//wp_get_sidebars_widgets is in wordpress/wp-includes/widgets.php
		return wp_get_sidebars_widgets();
		//return retrieve_widgets();
	}
	
	public static function getSideBarHtml($side_bar_name) {
		global $wp_registered_sidebars, $phpframework_options, $phpframework_results;
		
		if (is_active_sidebar($side_bar_name)) {
			//first tries to get the dynamic sidebar
			ob_start(null, 0);
			
			dynamic_sidebar($side_bar_name);
			
			$html = ob_get_contents();
			ob_end_clean();
			
			//only if not exists, it may be called already, so checks the $phpframework_results
			if ($phpframework_options && !$html && !empty($phpframework_results['theme_side_bars'][$side_bar_name][0])) 
				$html = $phpframework_results['theme_side_bars'][$side_bar_name][0];
			
			//only if still not exists tries to get the sidebar template file
			if (!$html) {
				ob_start(null, 0);
				
				self::get_sidebar($side_bar_name, array(), false);
				//include('sidebar-recommendations.php');
				
				$html = ob_get_contents();
				ob_end_clean();
			}
			
			return $html;
		}
	}
	
	public static function getDefaultSideBarHtml() {
		ob_start(null, 0);
		
		self::get_sidebar();
		
		$html = ob_get_contents();
		ob_end_clean();
		
		return $html;
	}
	
	//replicate the get_sidebar function from wordpress/wp-includes/general-template.php, but allows multiple calls. The default wordpress function only allows 1 call for each sidebar.
	private static function get_sidebar($name = null, $args = array(), $include_default = true) {
		/**
		* Fires before the sidebar template file is loaded.
		*
		* @since 2.2.0
		* @since 2.8.0 The `$name` parameter was added.
		* @since 5.5.0 The `$args` parameter was added.
		*
		* @param string|null $name Name of the specific sidebar file to use. Null for the default sidebar.
		* @param array       $args Additional arguments passed to the sidebar template.
		*/
		do_action( 'get_sidebar', $name, $args );

		$templates = array();
		$name = (string) $name;
		
		if ('' !== $name)
			$templates[] = "sidebar-{$name}.php";
		
		if ($include_default)
			$templates[] = 'sidebar.php';

		if (!locate_template($templates, true, false, $args))  //locate_template is in wordpress/wp-includes/template.php
			return false; //sidebar not exists or is a dynamic sidebar
		
		return true; //it means that the sidebar exists.
	}

	/* WIDGETS FUNCTIONS */

	public static function getAvailableWidgets() {
		global $wp_registered_widgets;

		return $wp_registered_widgets;
	}

	public static function getWidgetIdByClass($class) {
		global $wp_widget_factory;

		$widget_obj = isset($wp_widget_factory->widgets[$class]) ? $wp_widget_factory->widgets[$class] : null;

		return $widget_obj ? $widget_obj->id : null;
	}

	public static function getWidgetCallbackObjById($widget_id) {
		global $wp_registered_widgets;

		$control = isset($wp_registered_widgets[$widget_id]) ? $wp_registered_widgets[$widget_id] : null; //to show widget form options to insert new widget

		if (isset($control['callback']))
			return $control['callback'][0];
	}
	
	public static function getWidgetHtmlById($widget_id, $instance = array(), $args = array()) {
		$obj = self::getWidgetCallbackObjById($widget_id);
		
		if ($obj) {
			$widget_class = get_class($obj);
			
			return self::getWidgetHtmlByClass($widget_class, $instance, $args);
		}
	}
	
	public static function getWidgetHtmlByClass($widget_class, $instance = array(), $args = array()) {
		global $wp_widget_factory;
		
		if ($widget_class) {
			$widget_obj = isset($wp_widget_factory->widgets[$widget_class]) ? $wp_widget_factory->widgets[$widget_class] : null;
			
			if ($widget_obj) {
				$number = $widget_obj->number;
				
				ob_start(null, 0);
				
				the_widget($widget_class, $instance, $args); //the_widget is in wordpress/wp-includes/widgets.php
				
				$html = ob_get_contents();
				ob_end_clean();
				
				//reset widget to be shown again or to access control options. This is very important otherwise when we try to call the widget control options or display it again, it won't work!
				$widget_obj->_set($number);
				
				return $html;
			}
		}
	}

	public static function getWidgetControlOptionsById($widget_id, $instance = null) {
		global $wp_registered_widget_controls;

		$control = isset($wp_registered_widget_controls[$widget_id]) ? $wp_registered_widget_controls[$widget_id] : null; //to show widget form options to insert new widget
		$html = "";

		if (isset($control['callback'])) {
			$obj = isset($control['callback'][0]) ? $control['callback'][0] : null;
			$instance = apply_filters('widget_form_callback', $instance, $obj);
			
		     if (false !== $instance) {
				ob_start(null, 0);
				
		          $ret = $obj->form($instance);
				do_action_ref_array('in_widget_form', array(&$obj, &$ret, $instance));
				
				$html = ob_get_contents();
				ob_end_clean();

				if ($html) {
					$num = isset($instance["multi_number"]) && is_numeric($instance["multi_number"]) ? (int) $instance["multi_number"] : (int) $obj->number;
					$widget_id = !empty($instance["widget-id"]) ? $instance["widget-id"] : $control["id"];
					$id_base = !empty($instance["id_base"]) ? $instance["id_base"] : $obj->id_base;
					
					$html = '<form method="post">
							' . $html . '
							<input type="hidden" name="widget-id" class="widget-id" value="' . $widget_id . '" />
							<input type="hidden" name="id_base" class="id_base" value="' . $id_base . '" />
							<input type="hidden" name="multi_number" class="multi_number" value="' . $num . '" />
							
							<input type="submit" name="savewidget" class="button button-primary widget-control-save right" value="Saved">
						</form>';
				}
			}
		}
		
		if (!$html)
			$html = "<p>" . __( 'There are no options for this widget.' ) . "</p>\n";

		return $html;
	}
	
	public static function getWidgetControlOptionsByClass($widget_class, $instance = null) {
		$widget_id = self::getWidgetIdByClass($widget_class);
		return self::getWidgetControlOptionsById($widget_id, $instance);
	}
	
	//This function should be called based in the form request created from the getWidgetControlOptions method
	public static function getWidgetControlOptionsToSave($old_instance = array()) {
		global $wp_registered_widget_updates;
		
		$instance = false; //must be false bc the $obj->update returns false too.

		if (isset($_POST['savewidget']) && !empty($_POST['widget-id']) && !empty($_POST['id_base'])) {
			$id_base = $_POST['id_base'];
			$control = $wp_registered_widget_updates[ $id_base ]; //to show widget form options to update existent widget
			
			if ($control) {
				//finish this function and check better this files
				//wp-admin/includes/ajax-actions.php:wp_ajax_save_widget
				//wp-admin/widgets.php:205
				$obj = isset($control['callback'][0]) ? $control['callback'][0] : null;
				$num = !empty($_POST['multi_number']) ? (int) $_POST['multi_number'] : (!empty($_POST['widget_number']) ? (int) $_POST['widget_number'] : 0);
				$new_instance = array();
			
				if (isset($_POST['widget-' . $id_base]) && is_array($_POST['widget-' . $id_base]))
		          	$new_instance = isset($_POST['widget-' . $id_base][$num]) ? $_POST['widget-' . $id_base][$num] : null;
		                  	
				ob_start(null, 0);		

		          $instance = $obj->update($new_instance, $old_instance);
				$instance = apply_filters('widget_update_callback', $instance, $new_instance, $old_instance, $obj);
				
				ob_end_clean();
			}
		}

		return $instance;
	}
	
	/*public static function updateWidgetControlOptions() {
		global $wp_registered_widget_updates;
		
		$status = false;	

		if (isset( $_POST['savewidget'] ) && !empty($_POST['widget-id']) && !empty($_POST['id_base'])) {
			$id_base = $_POST['id_base'];
			$control = $wp_registered_widget_updates[ $id_base ]; //to show widget form options to update existent widget
			
			if ($control) {
				$old_instance = array(); //TODO: get from my DB based in the $_POST['widget-id']
				   $new_instance = self::getWidgetControlOptionsToSave($old_instance);
				
				if ($new_instance !== false)
					$status = true; //TODO: save to my DB
			}
		}

		return $status;
	}*/
	
	/* PAGES FUNCTIONS */
	
	public static function getPagesListHtml($args = null) {
		ob_start(null, 0);
			
		wp_list_pages($args); //wp_list_pages is in wordpress/wp-includes/post-template.php
		
		$html = ob_get_contents();
		ob_end_clean();
		
		return $html;
	}

	/* UTILS FUNCTIONS */

	public static function getPages($args = null) {
		$default_args = array(
		           'depth'        => 0,
		           'show_date'    => '',
		           'date_format'  => get_option( 'date_format' ),
		           'child_of'     => 0,
		           'exclude'      => '',
		           'title_li'     => __( 'Pages' ),
		           'echo'         => 1,
		           'authors'      => '',
		           'sort_column'  => 'menu_order, post_title',
		           'link_before'  => '',
		           'link_after'   => '',
		           'item_spacing' => 'preserve',
		           'walker'       => '',
			'hierarchical' => 0,
		   );
		
		$args = $args ? array_merge($default_args, $args) : $default_args;
		$pages = get_pages($args); //get_pages is in wordpress/wp-includes/post.php

		return $pages;
	}

	public static function getPostById($post_id) {
		return get_post($post_id); //get_post is in wordpress/wp-includes/post.php
	}

	public static function getAllPosts($args = null) {
		return get_posts($args); //get_posts is in wordpress/wp-includes/post.php
	}

	public static function getPostsByCategory($category, $args = null) {
		if (!$args)
			$args = array();

		if ($category)
			$args["category"] = get_cat_ID($category); //get_cat_ID is in wordpress/wp-includes/category.php

		return get_posts($args); //get_posts is in wordpress/wp-includes/post.php
	}

	public static function getPostsByTag($tag, $args = null) {
		if (!$args)
			$args = array();

		if ($tag)
			$args["tag"] = $tag;

		return get_posts($args); //get_posts is in wordpress/wp-includes/post.php
	}

	public static function getPostsAfterDate($date, $args = null) {
		if (!$args)
			$args = array();

		if ($date) {
			//more info in https://developer.wordpress.org/reference/classes/wp_query/#date-parameters
			//or in https://wisdmlabs.com/blog/query-posts-or-comments-by-date-time/
			$args['date_query'] = array(
				'after' => $date, //date('Y-m-d', strtotime('-30 days')) 
			);
			$args['suppress_filters'] = false;

			return get_posts($args); //get_posts is in wordpress/wp-includes/post.php
		}
	}

	public static function getPostsByDate($date, $args = null) {
		if (!$args)
			$args = array();

		if ($date) {
			//more info in https://developer.wordpress.org/reference/classes/wp_query/#date-parameters
			//or in https://wisdmlabs.com/blog/query-posts-or-comments-by-date-time/
			$args['date_query'] = array(
				'year' => date('Y', strtotime($date)), 
				'month' => date('m', strtotime($date)), 
				'day' => date('d', strtotime($date)), 
			);
			$args['suppress_filters'] = false;

			return get_posts($args); //get_posts is in wordpress/wp-includes/post.php
		}
	}

	public static function getCategories($args = null) {
		return get_categories($args); //get_categories is in wordpress/wp-includes/category.php
	}

	public static function getTags($args = null) {
		return get_tags($args); //get_tags is in wordpress/wp-includes/category.php
	}

	public static function getMedias($args = null) {
		$default_args = array(
		    'post_type'      => 'attachment',
		    'numberposts' => -1,
		    'post_status' => null,
		    'post_parent' => null, // any parent
		    //'post_mime_type' => 'image',
		);

		$args = $args ? array_merge($default_args, $args) : $default_args;
		
		return get_posts($args); //get_posts is in wordpress/wp-includes/post.php
	}

	public static function getHomeUrl() {
		return get_home_url(); //get_home_url is in wordpress/wp-includes/link-template.php
	}

	public static function getSiteUrl() {
		return site_url(); //site_url is in wordpress/wp-includes/link-template.php
	}

	public static function getAvailableThemes() {
		return wp_get_themes(); //wp_get_themes is in wordpress/wp-includes/theme.php
	}

	public static function getCurrentTheme() {
		return get_template(); //get_template is in wordpress/wp-includes/theme.php
	}
	
	public static function get404PageHtml() {
		global $wp_query; //$wp_query is in wp-settings.php
		
		ob_start(null, 0);
		
		$wp_query->set_404();
		get_template_part(404); //get_template_part is in wordpress/wp-includes/general-template.php
		
		$html = ob_get_contents();
		ob_end_clean();
		
		return $html;
		
	}
	
	public static function convertContentArrayToHtml($arr) {
		$html = "";
		
		foreach ($arr as $k => $v) {
			//$html .= "<h2>$k</h2>";
			
			if (is_array($v))
				$html .= self::convertContentArrayToHtml($v);
			else
				$html .= $v;
		}
		
		return $html;
	}
	
	//Do NOT use the php DomDocument class, bc the Html may be incomplete and not have closed tags. If I use the DomDocument class it will close the missing html tags. And I don't want this!
	public static function convertHtmlIntoInnerHtml($html) {
		if (!$html)
			return $html;
		
		//remove meta tags
		$tags = HtmlStringHandler::getHtmlTags($html, "meta", false);
		foreach ($tags as $tag) {
			if (preg_match("/(\s+|\"|')name=(\"|')?viewport(\"|'|\s|\/|>)/i", $tag)) //don't remove view-port bc of the UI
				continue 1;
			
			$html = str_replace($tag, "", $html);
		}
		
		//remove title tags
		$tags = HtmlStringHandler::getHtmlTags($html, "title", true);
		foreach ($tags as $tag)
			$html = str_replace($tag, "", $html);
		
		//remove link tags that are not css
		$tags = HtmlStringHandler::getHtmlTags($html, "link", false);
		foreach ($tags as $tag) {
			if (preg_match("/(\s+|\"|')rel=(\"|')?stylesheet(\"|'|\s|\/|>)/i", $tag) || preg_match("/(\s+|\"|')type=(\"|')?text\/css(\"|'|\s|\/|>)/i", $tag)) //don't remove css
				continue 1;
			
			$html = str_replace($tag, "", $html);
		}
		
		//remove html tag
		$html = preg_replace("/<(\/)?html[^>]*>/i", "", $html);
		
		//remove doctype tag
		$html = preg_replace("/<!doctype[^>]*>/i", "", $html);
		
		//replace head, body and foot tags by divs. Do not replace this tags bc they may have the class attribute and this will break the UI...
		$html = preg_replace("/<(\/)?(head|body|foot)(\s+|>)/i", "<\${1}div\${3}", $html);
		
		return $html;
	}
	
	//Do NOT use the php DomDocument class, bc the Html may be incomplete and not have closed tags. If I use the DomDocument class it will close the missing html tags. And I don't want this!
	public static function getCssAndJsFromHtml($html, $css = true, $js = true) {
		if ($html && ($css || $js)) {
			$new_html = "";
			
			if ($css) {
				$tags = HtmlStringHandler::getHtmlTags($html, "link", false);
				foreach ($tags as $tag)
					if (preg_match("/(\s+|\"|')rel=(\"|')?stylesheet(\"|'|\s|\/|>)/i", $tag) || preg_match("/(\s+|\"|')type=(\"|')?text\/css(\"|'|\s|\/|>)/i", $tag))
						$new_html .= trim($tag) . "\n";
				
				if ($new_html)
					$new_html .= "\n";
				
				$tags = HtmlStringHandler::getHtmlTags($html, "style", true);
				foreach ($tags as $tag) 
					$new_html .= trim($tag) . "\n";
			}
			
			if ($js) {	
				if ($new_html)
					$new_html .= "\n";
				
				$tags = HtmlStringHandler::getHtmlTags($html, "script", true);
				foreach ($tags as $tag) 
					$new_html .= trim($tag) . "\n";
			}
			
			return trim($new_html);
		}
		
		return $html;
	}
	
	//$type == "above" or "bellow"
	public static function getContentParentsHtml($html, $type = "above") {
		return $type == "bellow" ? self::getContentParentsHtmlBellow($html) : self::getContentParentsHtmlAbove($html);
	}
	
	//Do NOT use the php DomDocument class, bc the Html may be incomplete and not have closed tags. If I use the DomDocument class it will close the missing html tags. And I don't want this!
	//get last opened html element and go up and delete all siblings and elements that are not parents. This is, only leave the html parents structure.
	public static function getContentParentsHtmlAbove($html) {
		if ($html) {
			$elements = HtmlStringHandler::convertHtmlToElementsArray($html);
			$new_elements = self::getContentParentsHtmlElementsAbove($elements);
			
			$html = HtmlStringHandler::convertElementsArrayToHtml($new_elements);
		}
		
		return $html;
	}
	
	//Do NOT use the php DomDocument class, bc the Html may be incomplete and not have closed tags. If I use the DomDocument class it will close the missing html tags. And I don't want this!
	//get first closed html element and go down and delete all siblings and elements that are not close tags of its' parents. This is, only leave the html parents structure.
	public static function getContentParentsHtmlBellow($html) {
		if ($html) {
			$elements = HtmlStringHandler::convertHtmlToElementsArray($html);
			$elements = HtmlStringHandler::joinElementsArrayTextNodes($elements);
			
			foreach ($elements as $idx => $element)
				if (is_array($element) && isset($element["nodeType"]) && $element["nodeType"] == 1) //1 == is Element node
					unset($elements[$idx]);
			
			$html = HtmlStringHandler::convertElementsArrayToHtml($elements);
		}
		
		return $html;
	}
	
	private static function getContentParentsHtmlElementsAbove($elements) {
		$new_elements = array();
		
		for ($i = count($elements); $i >= 0; $i--) {
			$element = $elements[$i];
			$node_type = isset($element["nodeType"]) ? $element["nodeType"] : null;
			
			//if is an element node and not a text or comment node
			if ($node_type == 1 && empty($element["closeTag"])) {
				if (!empty($element["childNodes"]))
					$element["childNodes"] = self::getContentParentsHtmlElementsAbove($element["childNodes"]);
				
				$new_elements[] = $element;
				break;
			}
		}
		
		return $new_elements;
	}
}
?>
