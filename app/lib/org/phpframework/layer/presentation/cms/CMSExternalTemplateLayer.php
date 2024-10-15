<?php
include_once get_lib("org.phpframework.phpscript.PHPScriptHandler");
include_once get_lib("org.phpframework.cms.wordpress.WordPressCMSBlockHandler");
include_once get_lib("org.phpframework.cms.wordpress.WordPressCMSBlockSettings");

class CMSExternalTemplateLayer {
	
	/*private*/ const CACHE_DIR_NAME = "cms/external_template_layer/";
	
	public static function getParsedTemplateCode($EVC, $template_params, $external_vars) {
		$code = self::getTemplateCode($EVC, $template_params, $external_vars);
		
		if ($code) {
			if (!empty($template_params["external_vars"]) && is_array($template_params["external_vars"]))
				$external_vars = array_merge($external_vars, $template_params["external_vars"]);
			
			return PHPScriptHandler::parseContent($code, $external_vars);
		}
	}
	
	public static function getTemplateCode($EVC, $template_params, $external_vars) {
		if (is_array($template_params)) {
			$ttl = isset($template_params["cache_ttl"]) ? $template_params["cache_ttl"] : null;
			
			if ($ttl) {
				$CacheHandler = $EVC->getPresentationLayer()->getPHPFrameWork()->getObject("UserCacheHandler");
				
				if ($CacheHandler) {
					$cached_file_name = self::CACHE_DIR_NAME . md5(serialize($template_params));
					$ttl = $ttl * 60; //$ttl is in minutes and should be converted to seconds.
					$CacheHandler->config($ttl, false);
					
					if ($CacheHandler->isValid($cached_file_name))
						return $CacheHandler->read($cached_file_name);
				}
			}
			
			$template_params_type = isset($template_params["type"]) ? $template_params["type"] : null;
			$code = null;
			
			switch($template_params_type) {
				case "project": 
					$code = self::getTemplateCodeFromProjectTemplateContents($EVC, $template_params, $external_vars); 
					break;
					
				case "block": 
					$code = self::getTemplateCodeFromBlockContents($EVC, $template_params, $external_vars); 
					break;
					
				case "wordpress_template": 
					$code = self::getTemplateCodeFromWordPressContents($EVC, $template_params, $external_vars);  
					break;
					
				case "url": 
					$code = self::getTemplateCodeFromUrlContents($EVC, $template_params, $external_vars);  
					break;
					
				default:
					launch_exception(new Exception("No template code to parse! Invalid template params: " . var_export($template_params, true)));
			}
			
			if ($ttl && !empty($CacheHandler)) 
				$CacheHandler->write($cached_file_name, $code);
			
			return $code;
		}
	}
	
	private static function getTemplateCodeFromProjectTemplateContents($EVC, $template_params, $external_vars) {
		$template_id = isset($template_params["template_id"]) ? $template_params["template_id"] : null;
		
		if ($template_id) {
			$external_project_id = isset($template_params["external_project_id"]) ? $template_params["external_project_id"] : null;
			$template_path = $EVC->getTemplatePath($template_id, $external_project_id);
			
			if ($template_path && file_exists($template_path)) {
				//echo "<pre>";print_r($external_vars);die();
				$content = file_get_contents($template_path);
				//echo "<textarea>$content</textarea>";die();
				
				//replace all regions and params with correspondent xml code
				if (!empty($template_params["add_template_xml_regions_and_params"]))
					$content = WordPressCMSBlockHandler::addTemplateXMLRegionsAndParamsToPHPTemplate($content);
				
				//prepare external vars
				if (!empty($template_params["keep_original_project_url_prefix"])) {
					//set project global vars
					$current_project_id = $EVC->getPresentationLayer()->getSelectedPresentationId();
					$external_project_id = isset($template_params["external_project_id"]) ? $template_params["external_project_id"] : null;
					$EVC->getPresentationLayer()->setSelectedPresentationId($external_project_id);
					$GLOBALS["presentation_id"] = $external_project_id;
					
					$external_vars = self::getExternalVarsFromProjectTemplateContents($EVC, $external_vars);
				}
				
				//parse php and return html
				$code = PHPScriptHandler::parseContent($content, $external_vars); //Note that the external_vars already contain the EVC set, which must be the same then the $EVC
				
				//replace regions and params xml code with real php code
				$code = WordPressCMSBlockHandler::convertContentsHtmlToPHPTemplate($code);
				//echo "<textarea>$code</textarea>";die();
				
				if (!empty($template_params["keep_original_project_url_prefix"])) {
					//set original project global vars
					$GLOBALS["presentation_id"] = $current_project_id;
					$EVC->getPresentationLayer()->setSelectedPresentationId($current_project_id);
				}
				
				//$code = $content;
				
				return $code;
			}
			else {
				header("HTTP/1.0 404 Not Found");
				launch_exception(new Exception("No template path for template id '$template_id' in template code 'parse_php_code.php'."));
			}
		}
		else
			launch_exception(new Exception("No template id '$template_id' in template code 'parse_php_code.php'."));
	}
	
	private static function getExternalVarsFromProjectTemplateContents($EVC, $external_vars) {
		//load config project_url_prefix var. Note: Do not load the other config vars bc they are specific from the external_project_id
		include $EVC->getConfigPath("config");
		
		//only get the external_project_id url prefix
		$external_vars["original_project_url_prefix"] = $project_url_prefix;
		
		return $external_vars;
	}
	
	private static function getTemplateCodeFromBlockContents($EVC, $template_params, $external_vars) {
		$block_id = isset($template_params["block_id"]) ? $template_params["block_id"] : null;
		
		if ($block_id) {
			$external_project_id = isset($template_params["external_project_id"]) ? $template_params["external_project_id"] : null;
			$block_path = $EVC->getBlockPath($block_id, $external_project_id);
			
			if (file_exists($block_path)) {
				$block_local_variables = !empty($template_params["block_local_variables"]) ? $template_params["block_local_variables"] : array();
				$external_vars["block_local_variables"] = $block_local_variables;
				
				$content = file_get_contents($block_path);
				$content .= '<?php echo $EVC->getCMSLayer()->getCMSBlockLayer()->getCurrentBlock(); ?>';
				//echo "<textarea>$content</textarea>";die();
				$code = PHPScriptHandler::parseContent($content, $external_vars);
				$code = WordPressCMSBlockHandler::convertContentsHtmlToPHPTemplate($code);
				//echo "<textarea>$code</textarea>";die();
				
				return $code;
			}
			else {
				header("HTTP/1.0 404 Not Found");
				launch_exception(new Exception("No block path for block id '$block_id' in template code 'parse_php_code.php'."));
			}
		}
		else
			launch_exception(new Exception("No block id '$block_id' in template code 'parse_php_code.php'."));
	}

	private static function getTemplateCodeFromWordPressContents($EVC, $template_params, $external_vars) {
		$set_error_handler = empty($GLOBALS["ignore_undefined_vars_errors"]);
		
		if ($set_error_handler)
			set_error_handler("ignore_undefined_var_error_handler", E_WARNING);
		
		$url_query = isset($template_params["url_query"]) ? $template_params["url_query"] : null;
		$db_driver = !empty($template_params["wordpress_installation_name"]) ? $template_params["wordpress_installation_name"] : (isset($GLOBALS["default_db_driver"]) ? $GLOBALS["default_db_driver"] : null); //db_driver name
		$project_url_prefix = isset($external_vars["project_url_prefix"]) ? $external_vars["project_url_prefix"] : null;
		
		$wordpress_settings = array(
			"wordpress_folder" => $db_driver,
			"wordpress_request_content_url" => $project_url_prefix . "module/wordpress/get_html_contens/get_wordpress_content",
			"wordpress_request_content_connection_timeout" => WordPressCMSBlockSettings::getSetting("WORDPRESS_REQUEST_CONTENT_CONNECTION_TIMEOUT"),
			"wordpress_request_content_encryption_key" => WordPressCMSBlockSettings::getSetting("WORDPRESS_REQUEST_CONTENT_ENCRYPTION_KEY_HEX"),
			"cookies_prefix" => $db_driver,
		);
		
		$wordpress_options = array(
			"allowed_wordpress_urls" => isset($template_params["allowed_wordpress_urls"]) ? $template_params["allowed_wordpress_urls"] : null,
			"parse_wordpress_urls" => array_key_exists("parse_wordpress_urls", $template_params) ? $template_params["parse_wordpress_urls"] : true,
			"parse_wordpress_relative_urls" => array_key_exists("parse_wordpress_relative_urls", $template_params) ? $template_params["parse_wordpress_relative_urls"] : true,
		);
		
		$WordPressCMSBlockHandler = new WordPressCMSBlockHandler($EVC, $wordpress_settings);
		$content = $WordPressCMSBlockHandler->getBlockContent(null, $url_query, $wordpress_options);
		$code = $content && isset($content["results"]["full_page_html"]) ? $content["results"]["full_page_html"] : "";
		$code = WordPressCMSBlockHandler::convertContentsHtmlToPHPTemplate($code);
		
		if ($set_error_handler)
			restore_error_handler();
		
		return $code;
	}
	
	private static function getTemplateCodeFromUrlContents($EVC, $template_params, $external_vars) {
		$url = isset($template_params["url"]) ? $template_params["url"] : null;
		$code = null;
		
		if ($url) {
			$url_host = parse_url($url, PHP_URL_HOST);
			$current_host = isset($_SERVER["HTTP_HOST"]) ? explode(":", $_SERVER["HTTP_HOST"]) : null; //maybe it contains the port
			$current_host = isset($current_host[0]) ? $current_host[0] : null;
			
			$settings = array(
				"url" => $url, 
				"cookie" => $current_host == $url_host ? $_COOKIE : null,
				"settings" => array(
					"referer" => isset($_SERVER["HTTP_REFERER"]) ? $_SERVER["HTTP_REFERER"] : null,
					"follow_location" => 1,
					"connection_timeout" => isset($template_params["connection_timeout"]) ? $template_params["connection_timeout"] : null,
				)
			);
			
			$MyCurl = new MyCurl();
			$MyCurl->initSingle($settings);
			$MyCurl->get_contents();
			$data = $MyCurl->getData();
			$content = isset($data[0]["content"]) ? $data[0]["content"] : null;
			
			$code = WordPressCMSBlockHandler::convertContentsHtmlToPHPTemplate($content);
		}
			
		return $code;
	}
}
?>
