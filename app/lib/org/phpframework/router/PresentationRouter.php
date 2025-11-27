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

include_once get_lib("org.phpframework.router.exception.PresentationRouterException");
include_once get_lib("org.phpframework.xmlfile.XMLFileParser");

class PresentationRouter {
	private $routers;
	public $settings;
	
	private $PresentationLayer;
	private $EVC;
	
	public function __construct($settings = array()) {
		$this->settings = $settings;
		
		$this->routers = array();
	}
	
	public function setPresentationLayer($PresentationLayer) { $this->PresentationLayer = $PresentationLayer; }
	public function getPresentationLayer() { return $this->PresentationLayer; }
	
	public function load() {
		if (empty($this->settings["routers_path"]))
			launch_exception(new PresentationRouterException(1, "PresentationRouter->settings[routers_path]"));
		
		if (empty($this->settings["routers_file_name"]))
			launch_exception(new PresentationRouterException(1, "PresentationRouter->settings[routers_file_name]"));
		
		$file_path = $this->PresentationLayer->getSelectedPresentationSetting("presentation_path") . $this->settings["routers_path"] . $this->settings["routers_file_name"];
		
		if(file_exists($file_path)) {
			$presentation_id = $this->PresentationLayer->getSelectedPresentationId();
			if($this->PresentationLayer->getModuleCacheLayer()->cachedModuleRoutersExists($presentation_id)) {
				$this->routers = $this->PresentationLayer->getModuleCacheLayer()->getCachedModuleRouters($presentation_id);
			}
			else {
				$xml_schema_file_path = get_lib("org.phpframework.xmlfile.schema.routers", "xsd");
				$nodes = XMLFileParser::parseXMLFileToArray($file_path, false, $xml_schema_file_path);
				
				$first_node_name = is_array($nodes) ? array_keys($nodes) : array();
				$first_node_name = isset($first_node_name[0]) ? $first_node_name[0] : null;
			
				$router_nodes = $first_node_name && isset($nodes[$first_node_name][0]["childs"]["router"]) ? $nodes[$first_node_name][0]["childs"]["router"] : null;
				$routers = array();
				$t = $router_nodes ? count($router_nodes) : 0;
				for($i = 0; $i < $t; $i++) {
					$router_node = $router_nodes[$i];
					
					$to_search = XMLFileParser::getAttribute($router_node, "to_search");
					$to_replace = XMLFileParser::getAttribute($router_node, "to_replace");
					
					$routers[] = array($to_search, $to_replace);
				}
				$this->routers = $routers;
				
				$this->PresentationLayer->getModuleCacheLayer()->setCachedModuleRouters($presentation_id, $this->routers);
			}
		}
		else {
			$this->routers = array();
		}
	}
	
	public function parse($url) {
		$url_without_slash = substr($url, strlen($url) - 1) == "/" ? substr($url, 0, strlen($url) - 1) : $url;
		$new_location = $this->getNewLocation($url_without_slash);
		
		if($new_location == $url_without_slash) {
			$new_location = $this->getNewLocation($url);
			
			if($new_location == $url) {
				$url_with_slash = substr($url, strlen($url) - 1) != "/" ? $url."/" : $url;
				$new_location = $this->getNewLocation($url_with_slash);
			}
		}
		return $new_location;
	}
	
	private function getNewLocation($url) {
		$t = count($this->routers);
		for($i = 0; $i < $t; $i++) {
			$router = $this->routers[$i];
			$old_location = isset($router[0]) ? $router[0] : null;
			$new_location = isset($router[1]) ? $router[1] : null;
			
			$regex = $this->getLocationRegex($old_location);
			if(preg_match_all($regex, $url, $matches)) {
				$input = array();
				$t = count($matches);
				for($j = 1; $j < $t; $j++)
					$input[] = $matches[$j][0];
				
				$new_location = str_replace("&lt;?", "<?", $new_location);
				$new_location = str_replace("?&gt;", "?>", $new_location);
				$vars = array("input" => $input);
				
				$new_location = PHPScriptHandler::parseContent($new_location, $vars);
				
				if(strpos($new_location, "?") !== false) {
					$parts = explode("?", $new_location);
					$new_location = $parts[0];
					$query_string = isset($parts[1]) ? $parts[1] : null;
					
					$this->parseQueryString($query_string);
 				}
				return $new_location;
			}
		}
		return $url;
	}
	
	private function parseQueryString($query_string) {
		$parts = explode("&", $query_string);
		$t = count($parts);
		for ($i = 0; $i < $t; $i++) {
			$part = $parts[$i];
			
			$sub_part = explode("=", $part);
			$var = trim($sub_part[0]);
			$value = isset($sub_part[1]) ? urldecode($sub_part[1]) : null;
			
			if ($var)
				$_GET[$var] = $value;
		}
	}
	
	private function getLocationRegex($old_location) {
		//$regex = "/^" . str_replace("/", "\/", $old_location) . "$/iu"; //'/u' means with accents and ç too.
		$regex = "/^" . preg_quote($old_location, "/") . "$/iu"; //'/u' means with accents and ç too.
		
		return $regex;
	}
}
?>
