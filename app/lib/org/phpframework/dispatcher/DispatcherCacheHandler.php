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

include_once get_lib("org.phpframework.cache.service.filesystem.FileSystemServiceCacheHandler");
include_once get_lib("org.phpframework.module.ModuleCacheLayer");
include_once get_lib("org.phpframework.module.ModulePathHandler");
include_once get_lib("org.phpframework.xmlfile.XMLFileParser");
include_once get_lib("org.phpframework.dispatcher.Dispatcher");
include_once get_lib("org.phpframework.phpscript.PHPScriptHandler");

class DispatcherCacheHandler extends Dispatcher {
	public $settings;
	
	public $ServiceCacheHandler;
	private $ModuleCacheLayer;
	public $urls;
	public $selected_presentation_id;
	public $modules_path;
	
	public function __construct($settings, $project_settings) {
		$this->settings = array_merge($settings, $project_settings);
		
		if (!empty($this->settings["no_cache"]))
			$this->ServiceCacheHandler = false;
		else {
			if (empty($this->settings["dispatchers_cache_path"]))
				launch_exception(new Exception("'DispatcherCacheHandler->settings[dispatchers_cache_path]' variable cannot be empty!"));
			
			$dispatchers_module_cache_maximum_size = isset($this->settings["dispatchers_module_cache_maximum_size"]) ? $this->settings["dispatchers_module_cache_maximum_size"] : null;
			$dispatchers_default_cache_ttl = isset($this->settings["dispatchers_default_cache_ttl"]) ? $this->settings["dispatchers_default_cache_ttl"] : null;
			$dispatchers_default_cache_type = isset($this->settings["dispatchers_default_cache_type"]) ? $this->settings["dispatchers_default_cache_type"] : null;
			
			$this->ServiceCacheHandler = new FileSystemServiceCacheHandler($dispatchers_module_cache_maximum_size);
			$this->ServiceCacheHandler->setRootPath($this->settings["dispatchers_cache_path"]);
			$this->ServiceCacheHandler->setDefaultTTL($dispatchers_default_cache_ttl);
			$this->ServiceCacheHandler->setDefaultType($dispatchers_default_cache_type);
		}
		
		$this->ModuleCacheLayer = new ModuleCacheLayer($this);
		
		$this->urls = array();
	}
	
	public function getModuleCachedLayerDirPath() { return $this->ServiceCacheHandler ? $this->ServiceCacheHandler->getRootPath() : false; }
	
	public function setSelectedPresentationId($presentation_id) { 
		$this->selected_presentation_id = $presentation_id;
		
		if($this->ServiceCacheHandler) {
			if (empty($this->settings["dispatchers_cache_path"])) {
				launch_exception(new Exception("'DispatcherCacheHandler->settings[dispatchers_cache_path]' variable cannot be empty!"));
				return false;
			}
			
			$this->ServiceCacheHandler->setRootPath($this->settings["dispatchers_cache_path"] . $this->selected_presentation_id . "/");
		}
	}
	
	public function load() {
		if (empty($this->settings["dispatcher_caches_path"]))
			launch_exception(new Exception("'DispatcherCacheHandler->settings[dispatcher_caches_path]' variable cannot be empty!"));
		
		if (empty($this->settings["dispatchers_cache_file_name"]))
			launch_exception(new Exception("'DispatcherCacheHandler->settings[dispatchers_cache_file_name]' variable cannot be empty!"));
		
		$file_path = $this->getSelectedPresentationPath() . $this->settings["dispatcher_caches_path"] . $this->settings["dispatchers_cache_file_name"];
		
		if(file_exists($file_path)) {
			$urls_cache_code = "dispatcher_cache_settings";
			$urls_cache_prefix = "__system/cache_settings/".$this->selected_presentation_id."/";
			
			if($this->ServiceCacheHandler && $this->ServiceCacheHandler->isValid($urls_cache_prefix, $urls_cache_code, false, "php")) {
				$this->urls = $this->ServiceCacheHandler->get($urls_cache_prefix, $urls_cache_code, "php");
			}
			else {
				$xml_schema_file_path = get_lib("org.phpframework.xmlfile.schema.dispatchers", "xsd");
				$nodes = XMLFileParser::parseXMLFileToArray($file_path, false, $xml_schema_file_path);
				
				$first_node_name = is_array($nodes) ? array_keys($nodes) : array();
				$first_node_name = isset($first_node_name[0]) ? $first_node_name[0] : null;
		
				$url_nodes = isset($nodes[$first_node_name][0]["childs"]["url"]) ? $nodes[$first_node_name][0]["childs"]["url"] : null;
				$urls = array();
				$t = $url_nodes ? count($url_nodes) : 0;
				for($i = 0; $i < $t; $i++) {
					$url_node = $url_nodes[$i];
					
					$method = XMLFileParser::getAttribute($url_node, "method");
					$ttl = XMLFileParser::getAttribute($url_node, "ttl");
					$suffix_key = XMLFileParser::getAttribute($url_node, "suffix_key");
					$headers = XMLFileParser::getAttribute($url_node, "headers");
					$value = XMLFileParser::getValue($url_node);
					
					$method = $method ? strtolower($method) : "get";
					$urls[ $method ][] = array("url" => $value, "ttl" => $ttl, "suffix_key" => $suffix_key, "headers" => $headers);
				}
				$this->urls = $urls;
				
				if($this->ServiceCacheHandler) {
					$this->ServiceCacheHandler->create($urls_cache_prefix, $urls_cache_code, $this->urls, "php");
				}
			}
		}
		else {
			$this->urls = array();
		}
	}
	
	public function getCache($url) {
		if($this->getErrorHandler()->ok() && $this->ServiceCacheHandler) {
			$url_settings = $this->getURLSettings($url);
			if($url_settings) {
				$code = $this->getURLCode($url, $url_settings);
				
				if($this->ServiceCacheHandler->isValid(false, $code, isset($url_settings["ttl"]) ? $url_settings["ttl"] : null)) {
					return $this->ServiceCacheHandler->get(false, $code);
				}
			}
		}
		return false;
	}
	
	public function setCache($url, $html) {
		if($this->getErrorHandler()->ok() && $this->ServiceCacheHandler) {
			$url_settings = $this->getURLSettings($url);
			if($url_settings) {
				$code = $this->getURLCode($url, $url_settings);
				
				return $this->ServiceCacheHandler->create(false, $code, $html);
			}
		}
		return false;
	}
	
	public function getHeaders($url) {
		$url_settings = $this->getURLSettings($url);
		return $url_settings && isset($url_settings["headers"]) ? str_replace('\n', "\n", $url_settings["headers"]) : false;
	}
	
	public function prepareURL(&$url) {
		$url = trim($url);
		while(strpos($url, "//") !== false) {
			$url = str_replace("//", "/", $url);
		}
	}
	
	private function getURLCode($url, $url_settings = null) {
		$suffix_key = "";
		if ($url_settings && !empty($url_settings["suffix_key"])) {
			$url_settings["suffix_key"] = str_replace("&lt;?", "<?", str_replace("?&gt;", "?>", $url_settings["suffix_key"]));
			$suffix_key = "_" . PHPScriptHandler::parseContent($url_settings["suffix_key"]);
		}
		
		$get = isset($_GET) ? $_GET : null;
		$post = isset($_POST) ? $_POST : null;
		return "url-" . md5($url) . "_get-" . md5(serialize($get)) . "_post-" . md5(serialize($post)) . $suffix_key;
	}
	
	private function getURLSettings(&$url) {
		$rm = isset($_SERVER["REQUEST_METHOD"]) ? strtolower($_SERVER["REQUEST_METHOD"]) : null;
		$locations = $rm == "post" ? (isset($this->urls["post"]) ? $this->urls["post"] : null) : ($rm == "get" ? (isset($this->urls["get"]) ? $this->urls["get"] : null) : array());
		
		$url_without_slash = substr($url, strlen($url) - 1) == "/" ? substr($url, 0, strlen($url) - 1) : $url;
		$t = $locations ? count($locations) : 0;
		for($i = 0; $i < $t; $i++) {
			$location = $locations[$i];
			$location_url = isset($location["url"]) ? $location["url"] : null;
			
			$regex = $this->getLocationRegex($location_url);
			if(preg_match($regex, $url_without_slash)) {
				$url = $url_without_slash;
				return $location;
			}
		}
		
		for($i = 0; $i < $t; $i++) {
			$location = $locations[$i];
			$location_url = isset($location["url"]) ? $location["url"] : null;
			
			$regex = $this->getLocationRegex($location_url);
			if(preg_match($regex, $url)) {
				return $location;
			}
		}
		
		$url_with_slash = substr($url, strlen($url) - 1) != "/" ? $url."/" : $url;
		for($i = 0; $i < $t; $i++) {
			$location = $locations[$i];
			$location_url = isset($location["url"]) ? $location["url"] : null;
			
			$regex = $this->getLocationRegex($location_url);
			if(preg_match($regex, $url_with_slash)) {
				$url = $url_with_slash;
				return $location;
			}
		}
		
		return false;
	}
	
	private function getLocationRegex($location) {
		$regex = "/^" . str_replace("/", "\/", $location) . "$/iu"; //'/u' means with accents and ç too.
	
		return $regex;
	}
	
	private function getSelectedPresentationPath() {
		if (empty($this->settings["presentations_modules_file_path"]))
			launch_exception(new Exception("'DispatcherCacheHandler->settings[presentations_modules_file_path]' variable cannot be empty!"));
		
		if (empty($this->settings["presentations_path"]))
			launch_exception(new Exception("'DispatcherCacheHandler->settings[presentations_path]' variable cannot be empty!"));
		
		return ModulePathHandler::getModuleFolderPath($this->selected_presentation_id, $this->settings["presentations_modules_file_path"], $this->settings["presentations_path"], $this->modules_path, $this->settings, $this->ModuleCacheLayer);
	}
}
?>
