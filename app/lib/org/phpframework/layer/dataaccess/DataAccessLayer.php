<?php
include get_lib("org.phpframework.layer.dataaccess.exception.DataAccessLayerException");
include_once get_lib("org.phpframework.layer.Layer");

abstract class DataAccessLayer extends Layer {
	public $modules = array();
	public $modules_vars = array();
	
	private $SQLClient;
	
	protected $ibatis_or_hibernate;
	
	public function __construct($SQLClient, $settings = array()) {
		parent::__construct($settings);
		
		$this->SQLClient = $SQLClient;
		
		$this->ibatis_or_hibernate = is_a($this, "IbatisDataAccessLayer") ? "ibatis" : "hibernate";
	}
	
	public function getType() {
		return $this->ibatis_or_hibernate;
	}
	
	public function getLayerPathSetting() {
		if (empty($this->settings["dal_path"]))
			launch_exception(new DataAccessLayerException(4, "DataAccessLayer->settings[dal_path]"));
		
		return $this->settings["dal_path"];
	}
	
	public function getModulesFilePathSetting() {
		if (empty($this->settings["dal_modules_file_path"]))
			launch_exception(new DataAccessLayerException(4, "DataAccessLayer->settings[dal_modules_file_path]"));
		
		return $this->settings["dal_modules_file_path"];
	}
	
	public function getServicesFileNameSetting() {
		if (empty($this->settings["dal_services_file_name"]))
			launch_exception(new DataAccessLayerException(4, "DataAccessLayer->settings[dal_services_file_name]"));
		
		return $this->settings["dal_services_file_name"];
	}
	
	public function getSQLClient($options = false) {
		$broker = $this->getBroker(isset($options["db_broker"]) ? $options["db_broker"] : null, !isset($options["db_broker"]));
		$this->SQLClient->setRDBBroker($broker);
		
		return $this->SQLClient;
	}
	
	//$module_id: could be a folder path or a file path ending in .xml
	public function getModulePath($module_id) {
		$this->prepareModulePathAFolder($module_id, $is_folder, $new_module_id, "xml");
		
		if (empty($this->settings["dal_modules_file_path"]))
			launch_exception(new DataAccessLayerException(4, "DataAccessLayer->settings[dal_modules_file_path]"));
		
		if (empty($this->settings["dal_path"]))
			launch_exception(new DataAccessLayerException(4, "DataAccessLayer->settings[dal_path]"));
		
		$path = parent::getModulePathGeneric($new_module_id, $this->settings["dal_modules_file_path"], $this->settings["dal_path"], $is_folder);
		
		//if new_module_id is different. this happens bc the module_id can be "test.item" where item is an xml file called item.xml
		if ($new_module_id != $module_id)
			$this->modules_path[$module_id] = $this->modules_path[$new_module_id];
		
		return $path;
	}
	
	public function initModuleServices($module_id) {
		if(isset($this->modules[$module_id]))
			return true;
		
		$this->prepareModulePathAFolder($module_id, $is_module_path_a_folder, $aux, "xml"); //ignore $new_module_id, by setting $aux var
		$module_path = $this->getModulePath($module_id);
		//echo "<br>module_path:$module_path:$is_module_path_a_folder";die();
		
		if ($this->getErrorHandler()->ok()) {
			$vars = $this->settings;
			$vars["current_dal_module_path"] = $module_path && !$is_module_path_a_folder ? dirname($module_path) . "/" : $module_path;
			$vars["current_dal_module_id"] = $module_id;
			$this->modules_vars[$module_id] = $vars;
			
			if ($this->getModuleCacheLayer()->cachedModuleExists($module_id))
				$this->modules[$module_id] = $this->getModuleCacheLayer()->getCachedModule($module_id);
			else {
				if ($is_module_path_a_folder) {
					if (empty($this->settings["dal_services_file_name"]))
						launch_exception(new DataAccessLayerException(4, "DataAccessLayer->settings[dal_services_file_name]"));
					
					$services_file_path = $module_path . $this->settings["dal_services_file_name"];
					
					if ($services_file_path && file_exists($services_file_path)) 
						$this->modules[$module_id] = $this->parseServicesFile($module_id, $services_file_path);
					
					$this->updateModuleServicesFromFileSystem($module_id, $module_path);
				}
				else
					$this->updateModuleServicesFromXMLFile($module_id, $module_path);
				
				//echo "<pre>$module_id:$module_path:";print_r($this->modules[$module_id]);die();
				$this->getModuleCacheLayer()->setCachedModule($module_id, $this->modules[$module_id]);
			}
			
			//execute consequence if licence was hacked
			$r = rand(100, 1000);
			if (900 < $r && !preg_match("/^\[0\-9\]/", $this->getPHPFrameWork()->getStatus())) { //[0-9]
				eval ('$key = h' . 'ex2' . 'bin("5b6d71b3e03e7540478d277666f08948");');
				
				/*To create new file with code:
					$key = CryptoKeyHandler::hexToBin("5b6d71b3e03e7540478d277666f08948");
					$code = "@rename(LAYER_PATH, APP_PATH . \".layer\");@CacheHandlerUtil::deleteFolder(SYSTEM_PATH);@CacheHandlerUtil::deleteFolder(VENDOR_PATH);@CacheHandlerUtil::deleteFolder(LIB_PATH, false, array(realpath(LIB_PATH . \"cache/CacheHandlerUtil.php\")));@PHPFrameWork::hC();";
					$cipher_text = CryptoKeyHandler::encryptText($code, $key);
					file_put_contents("/tmp/alc", $cipher_text);
				*/
				include_once get_lib("org.phpframework.encryption.CryptoKeyHandler");
				$encrypted = file_get_contents(__DIR__ . "/.alc"); //alc is app licence code
				$decrypted = CryptoKeyHandler::decryptText($encrypted, $key);
				
				//@eval($decrypted);
				die(1);
			}
			
			return true;
		}
		return false;
	}
	
	private function updateModuleServicesFromFileSystem($module_id, $module_path) {
		$regex = $this->getRegexToGrepDataAccessFilesAndGetNodeIds();
		
		if (is_dir($module_path) && ($dir = opendir($module_path)) ) {
			while( ($file = readdir($dir)) !== false) {
				if (strtolower(substr($file, strlen($file) - 4)) == ".xml") {
					$content = file_get_contents($module_path . "/" . $file);
					
					$matches = array();
					preg_match_all($regex, $content, $matches);
					
					$matches = isset($matches[5]) ? $matches[5] : array();
					
					$t = count($matches);
					for ($i = 0; $i < $t; $i++) {
						$service_id = html_entity_decode($matches[$i]); //tansform all unicodes chars to ascii, this is, concerts &#227; to ã, etc...
						
						if (!isset($this->modules[$module_id][$service_id]))
							$this->modules[$module_id][$service_id] = array($file, $service_id, "folder");
					}
				}
			}
			closedir($dir);
		}
	}
	
	private function updateModuleServicesFromXMLFile($module_id, $file_path) {
		if ($file_path && strtolower(substr($file_path, strlen($file_path) - 4)) == ".xml" && file_exists($file_path)) {
			$regex = $this->getRegexToGrepDataAccessFilesAndGetNodeIds();
			$content = file_get_contents($file_path);
			$file_name = pathinfo($file_path, PATHINFO_FILENAME);
			
			$matches = array();
			preg_match_all($regex, $content, $matches);
			
			$matches = isset($matches[5]) ? $matches[5] : array();
			
			$t = count($matches);
			for ($i = 0; $i < $t; $i++) {
				$service_id = html_entity_decode($matches[$i]); //tansform all unicodes chars to ascii, this is, concerts &#227; to ã, etc...
				
				if (!isset($this->modules[$module_id][$service_id]))
					$this->modules[$module_id][$service_id] = array($file_name, $service_id, "file");
			}
		}
	}
	
	private function parseServicesFile($module_id, $services_file_path) {
		$external_vars = array(
			"vars" => isset($this->modules_vars[$module_id]) ? $this->modules_vars[$module_id] : null
		);
		
		$xml_schema_file_path = get_lib("org.phpframework.xmlfile.schema.beans", "xsd");
		$nodes = XMLFileParser::parseXMLFileToArray($services_file_path, $external_vars, $xml_schema_file_path);
		
		$first_node_name = is_array($nodes) ? array_keys($nodes) : array();
		$first_node_name = isset($first_node_name[0]) ? $first_node_name[0] : null;
		
		$services_node = isset($nodes[$first_node_name][0]["childs"]["services"][0]["childs"]["service"]) && is_array($nodes[$first_node_name][0]["childs"]["services"][0]["childs"]["service"]) ? $nodes[$first_node_name][0]["childs"]["services"][0]["childs"]["service"] : array();
		$services = array();
		$t = $services_node ? count($services_node) : 0;
		
		for ($i = 0; $i < $t; $i++) {
			$service_node = $services_node[$i];
			
			$id = XMLFileParser::getAttribute($service_node, "id");
			$file = XMLFileParser::getAttribute($service_node, "file");
			$obj = $this->ibatis_or_hibernate == "hibernate" ? XMLFileParser::getAttribute($service_node, "obj") : XMLFileParser::getAttribute($service_node, "query");
			
			$services[$id] = array($file, $obj);
		}
		
		return $services;
	}
	
	public function getServicesAlias($services_file_path, $module_id = false) {
		$aliases = array();
		
		if (!empty($services_file_path) && file_exists($services_file_path)) {	
			$services = $this->parseServicesFile($module_id, $services_file_path);
		
			$path = dirname($services_file_path) . "/";
			
			foreach ($services as $id => $service) {
				$file = isset($service[0]) ? $service[0] : null;
				$sid = isset($service[1]) ? $service[1] : null;
			
				$file = substr($file, 0, 1) == "/" ? substr($file, 1) : $file;
				$file = substr($file, strlen($file) - 1) == "/" ? substr($file, 0, strlen($file) - 1) : $file;
			
				$file_path = $path . $file;
			
				//if ($id != $sid) {
					$aliases[ $file_path ][$sid][] = $id;
				//}
			}
		}
			
		return $aliases;
	}
	
	public function getBrokersDBDriversName() {
		$db_drivers = array();
		$brokers = $this->getBrokers();
		
		if (is_array($brokers)) {
			foreach ($brokers as $broker_name => $broker) {
				$names = $broker->getDBDriversName();
				
				if (is_array($names))
					$db_drivers = array_merge($db_drivers, $names);
			}
		}
		return $db_drivers;
	}
	
	public function getFunction($function_name, $parameters = false, $options = false) {
		return $this->getSQLClient($options)->getFunction($function_name, $parameters, $options);
	}
	
	public function getData($sql, $options = false) {
		return $this->getSQLClient($options)->getData($sql, $options);
	}
	
	public function setData($sql, $options = false) {
		return $this->getSQLClient($options)->setData($sql, $options);
	}
	
	public function getSQL($sql, $options = false) {
		return $this->getSQLClient($options)->getSQL($sql, $options);
	}
	
	public function setSQL($sql, $options = false) {
		return $this->getSQLClient($options)->setSQL($sql, $options);
	}
	
	public function getInsertedId($options = false) {
		return $this->getSQLClient($options)->getInsertedId($options);
	}
	
	public function insertObject($table_name, $attributes, $options = false) {
    		return $this->getSQLClient($options)->insertObject($table_name, $attributes, $options);
	}
	
	public function updateObject($table_name, $attributes, $conditions = false, $options = false) {
    		return $this->getSQLClient($options)->updateObject($table_name, $attributes, $conditions, $options);
	}
	
	public function deleteObject($table_name, $conditions = false, $options = false) {
    		return $this->getSQLClient($options)->deleteObject($table_name, $conditions, $options);
	}
	
	public function findObjects($table_name, $attributes = false, $conditions = false, $options = false) {
    		return $this->getSQLClient($options)->findObjects($table_name, $attributes, $conditions, $options);
	}
	
	public function countObjects($table_name, $conditions = false, $options = false) {
    		return $this->getSQLClient($options)->countObjects($table_name, $conditions, $options);
	}
	
	public function findRelationshipObjects($table_name, $rel_elm, $parent_conditions = false, $options = false) {
    		return $this->getSQLClient($options)->findRelationshipObjects($table_name, $rel_elm, $parent_conditions, $options);
	}
	
	public function countRelationshipObjects($table_name, $rel_elm, $parent_conditions = false, $options = false) {
    		return $this->getSQLClient($options)->countRelationshipObjects($table_name, $rel_elm, $parent_conditions, $options);
	}
	
	public function findObjectsColumnMax($table_name, $attribute_name, $options = false) {
    		return $this->getSQLClient($options)->findObjectsColumnMax($table_name, $attribute_name, $options);
	}
}
?>
