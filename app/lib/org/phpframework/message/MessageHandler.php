<?php
include get_lib("org.phpframework.message.Message");
include_once get_lib("org.phpframework.cache.service.filesystem.FileSystemServiceCacheHandler");
include_once get_lib("org.phpframework.module.ModuleCacheLayer");
include_once get_lib("org.phpframework.module.ModulePathHandler");
include_once get_lib("org.phpframework.xmlfile.XMLFileParser");

class MessageHandler {
	private $loaded_messages = array();
	private $reported_messages = array();
	private $repeated_reported_messages = array();
	private $root_path;
	
	private $settings = array();
	private $modules_path = array();
	
	private $ServiceCacheHandler;
	private $ModuleCacheLayer;
	private $modules_messages_cache_folder_path = "__system/modules_messages_cache/";
	
	public function __construct($settings = array()) {
		$this->settings = $settings;
		
		if (!empty($this->settings["no_cache"]))
			$this->ServiceCacheHandler = false;
		else {
			$messages_module_cache_maximum_size = isset($this->settings["messages_module_cache_maximum_size"]) ? $this->settings["messages_module_cache_maximum_size"] : null;
			$messages_cache_path = isset($this->settings["messages_cache_path"]) ? $this->settings["messages_cache_path"] : null;
			$messages_default_cache_ttl = isset($this->settings["messages_default_cache_ttl"]) ? $this->settings["messages_default_cache_ttl"] : null;
			$messages_default_cache_type = isset($this->settings["messages_default_cache_type"]) ? $this->settings["messages_default_cache_type"] : null;
			
			$this->ServiceCacheHandler = new FileSystemServiceCacheHandler($messages_module_cache_maximum_size);
			$this->ServiceCacheHandler->setRootPath($messages_cache_path);
			$this->ServiceCacheHandler->setDefaultTTL($messages_default_cache_ttl);
			$this->ServiceCacheHandler->setDefaultType($messages_default_cache_type);
		}
		
		$this->ModuleCacheLayer = new ModuleCacheLayer($this);
	}
	
	public function getModuleCachedLayerDirPath() { return $this->ServiceCacheHandler ? $this->ServiceCacheHandler->getRootPath() : false; }
	
	public function setRootPath($root_path) {$this->root_path = $root_path;}
	public function getRootPath() {return $this->root_path;}
	
	public function addMessage($module_id, $message_id, $message, $attributes = false) {
		$Message = new Message();
		$Message->setModule($module_id);
		$Message->setId($message_id);
		$Message->setMessage($message);
		$Message->setAttributes($attributes);
		
		$this->loaded_messages[$module_id][strtolower($message_id)] = $Message;
	}
	
	public function getMessages($module_id, $attributes = false) {
		if (!isset($this->loaded_messages[$module_id]))
			$this->loadMessages($module_id);
		
		$module_messages = isset($this->loaded_messages[$module_id]) ? $this->loaded_messages[$module_id] : null;
		
		if (is_array($attributes) && count($attributes)) {
			$new_module_messages = array();
			
			foreach ($module_messages as $message_id => $Message) {
				if ($Message->checkAttributes($attributes))
					$new_module_messages[$message_id] = $Message;
			}
			return $new_module_messages;
		}
		
		return $module_messages ? $module_messages : array();
	}
	
	public function getMessage($module_id, $message_id) {
		$module_messages = $this->getMessages($module_id);
		$message_id = strtolower($message_id);
		
		return isset($module_messages[$message_id]) ? $module_messages[$message_id] : false;
	}
	
	public function reportMessage($module_id, $message_id) {
		$Message = $this->getMessage($module_id, $message_id);
		
		if ($Message) {
			$key = md5("{$module_id}_{$message_id}");
			
			if (!in_array($key, $this->repeated_reported_messages)) {
				$this->reported_messages[] = $Message;
				$this->repeated_reported_messages[] = $key;
			}
		}
		return true;
	}
	
	public function getReportedMessages($attributes = false) {
		if ($attributes) {
			$new_reported_messages = array();
			$t = count($this->reported_messages);
			
			for ($i = 0; $i < $t; $i++) {
				$Message = $this->reported_messages[$i];
			
				if ($Message->checkAttributes($attributes))
					$new_reported_messages[] = $Message;
			}
			return $new_reported_messages;
		}
		return $this->reported_messages;
	}
	
	public function emptyReportedMessages() {
		$this->reported_messages = array();
	}
	
	private function loadMessages($module_id) {
		$file_path = $this->getModulePath($module_id);
		
		if ($file_path) {
			if ($this->ServiceCacheHandler && $this->ServiceCacheHandler->isValid($this->modules_messages_cache_folder_path, $module_id))
				$module_messages = $this->ServiceCacheHandler->get($this->modules_messages_cache_folder_path, $module_id);
			else {
				$xml_schema_file_path = "";//get_lib("org.phpframework.xmlfile.schema.messages", "xsd");//We remove this bc the message.xml files can have their own free structure. The only mandatory node is the MESSAGE node which must correspond to a specific schema. Unfortunately I could NOT do this xsd.
				$nodes = XMLFileParser::parseXMLFileToArray($file_path, array("vars" => $this->settings), $xml_schema_file_path);
				
				$first_node_name = is_array($nodes) ? array_keys($nodes) : array();
				$first_node_name = isset($first_node_name[0]) ? $first_node_name[0] : null;
				
				$childs = $first_node_name && isset($nodes[$first_node_name][0]["childs"]) ? $nodes[$first_node_name][0]["childs"] : null;
				$module_messages = self::loadNodeMessages($module_id, $childs);
				
				if ($this->ServiceCacheHandler)
					$this->ServiceCacheHandler->create($this->modules_messages_cache_folder_path, $module_id, $module_messages);
			}
			
			$this->loaded_messages[$module_id] = $module_messages;
		}
	}
	
	private static function loadNodeMessages($module_id, $nodes, $prefix_id = "") {
		$module_messages = array();
		
		foreach ($nodes as $key => $sub_nodes) {
			$total = $sub_nodes ? count($sub_nodes) : 0;
			
			if ($key == "message") {
				for ($i = 0; $i < $total; $i++) {
					$message = $sub_nodes[$i];
					
					$id = $prefix_id . XMLFileParser::getAttribute($message, "id");
					$value = XMLFileParser::getValue($message);
			
					$Message = new Message();
					$Message->setModule($module_id);
					$Message->setId($id);
					$Message->setMessage($value);
					$Message->setAttributes(isset($message["@"]) ? $message["@"] : null);
		
					$module_messages[ strtolower($id) ] = $Message;
				}
			}
			else {
				for ($i = 0; $i < $total; $i++) {
					$sub_node_name = isset($sub_nodes[$i]["name"]) ? $sub_nodes[$i]["name"] : null;
					$sub_node_childs = isset($sub_nodes[$i]["childs"]) ? $sub_nodes[$i]["childs"] : null;
					
					$sub_module_messages = self::loadNodeMessages($module_id, $sub_node_childs, $sub_node_name . "/");
					$module_messages = array_merge($module_messages, $sub_module_messages);
				}
			}
		}
		
		return $module_messages;
	}
	
	private function getModulePath($module_id) {
		if (empty($this->settings["messages_modules_file_path"]))
			launch_exception(new Exception("MessageHandler->settings[messages_modules_file_path]"));
		
		if (empty($this->settings["messages_path"]))
			launch_exception(new Exception("MessageHandler->settings[messages_path]"));
		
		return ModulePathHandler::getModuleFilePath($module_id, $this->settings["messages_modules_file_path"], $this->settings["messages_path"], $this->modules_path, $this->settings, $this->ModuleCacheLayer);
	}
}
?>
