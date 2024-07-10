<?php
include_once get_lib("org.phpframework.layer.dataaccess.DataAccessLayer");
include_once get_lib("org.phpframework.sqlmap.SQLMapIncludesHandler");

class IbatisDataAccessLayer extends DataAccessLayer {
	
	/*
	$xxx->callQuerySQL("TEST", "insert", "insert_item_not_registered"); //modules.xml has an alias TEST => test
	$xxx->callQuerySQL("test", "insert", "insert_item_not_registered"); //test is a folder
	$xxx->callQuerySQL("test.item.xml", "insert", "insert_item_not_registered");
	$xxx->callQuerySQL("test/item.xml", "insert", "insert_item_not_registered");
	$xxx->callQuerySQL("test.item", "insert", "insert_item_not_registered"); //default_extension will be added. item is a xml file.
	$xxx->callQuerySQL("test/item", "insert", "insert_item_not_registered"); //default_extension will be added. item is a xml file.
	*/
	public function callQuerySQL($module_id, $service_type, $service_id, $parameters = false, $options = false) {
		debug_log_function("IbatisDataAccessLayer->callQuerySQL", array($module_id, $service_type, $service_id, $parameters));
	
		$options["call_query_sql"] = true;
		
		$this->initModuleServices($module_id);
		
		if ($this->getErrorHandler()->ok())
			return $this->callService($module_id, $service_type, $service_id, $parameters, $options);
		
		return false;
	}
	
	public function callQuery($module_id, $service_type, $service_id, $parameters = false, $options = false) {
		debug_log_function("IbatisDataAccessLayer->callQuery(", array($module_id, $service_type, $service_id, $parameters, $options));
		
		$is_cache_active = $this->isCacheActive();
		
		$options = $options ? $options : array();
		$includes_options = array_merge($options, array("key_suffix" => "_includes"));
		
		if ($is_cache_active && empty($options["no_cache"]) && $this->getCacheLayer()->isValid($module_id, $service_id, $parameters, $options)) {
			$includes = $this->getCacheLayer()->get($module_id, $service_id, $parameters, $includes_options);
			SQLMapIncludesHandler::includeLibsOfResultClassAndMap($includes);
			
			return $this->getCacheLayer()->get($module_id, $service_id, $parameters, $options);
		}
		
		$this->initModuleServices($module_id);
		
		if ($this->getErrorHandler()->ok()) {
			$result = $this->callService($module_id, $service_type, $service_id, $parameters, $options, $includes);
			
			if($this->getErrorHandler()->ok()) {
				if($is_cache_active) {
					$this->getCacheLayer()->check($module_id, $service_id, $parameters, $includes, $includes_options);
					$this->getCacheLayer()->check($module_id, $service_id, $parameters, $result, $options);
				}
				
				return $result;
			}
		}
		
		return false;
	}
	
	public function getQueryProps($module_id, $service_type, $service_id, $parameters = false, $options = false) {
		$props = array();
		
		$this->initModuleServices($module_id);
		
		if($this->getErrorHandler()->ok()) {
			$module = $this->modules[$module_id];
			$module_path = $this->modules_path[$module_id];
			//echo "$module_id, $service_type, $service_id<br>";
			//echo "<pre>$module_id:";print_r($this->modules_path);print_r(array_keys($module));print_r($module[$service_id]);echo "</pre>";
			
			$props["module"] = $module;
			$props["module_path"] = $module_path;
			
			if(isset($module[$service_id])) {
				$service = $module[$service_id];
				$file_name = isset($service[0]) ? $service[0] : null;
				$file_service_id = isset($service[1]) ? $service[1] : null;
				$file_type = isset($service[2]) ? $service[2] : null;
				
				$query_path = $module_path . ($file_type != "file" ? "/" . $file_name : "");
				$query_id = $file_service_id;
				//echo "$query_path | $query_id<br>";
				
				$props["service"] = $service;
				$props["query_path"] = $query_path;
				$props["query_id"] = $query_id;
			}
		}
		
		return $props;
	}
	
	public function callSelectSQL($module, $service, $parameters = false, $options = false) {
		return $this->callQuerySQL($module, "select", $service, $parameters, $options);
	}
	public function callSelect($module, $service, $parameters = false, $options = false) {
		return $this->callQuery($module, "select", $service, $parameters, $options);
	}
	
	public function callInsertSQL($module, $service, $parameters = false, $options = false) {
		return $this->callQuerySQL($module, "insert", $service, $parameters, $options);
	}
	public function callInsert($module, $service, $parameters = false, $options = false) {
		return $this->callQuery($module, "insert", $service, $parameters, $options);
	}
	
	public function callUpdateSQL($module, $service, $parameters = false, $options = false) {
		return $this->callQuerySQL($module, "update", $service, $parameters, $options);
	}
	public function callUpdate($module, $service, $parameters = false, $options = false) {
		return $this->callQuery($module, "update", $service, $parameters, $options);
	}
	
	public function callDeleteSQL($module, $service, $parameters = false, $options = false) {
		return $this->callQuerySQL($module, "delete", $service, $parameters, $options);
	}
	public function callDelete($module, $service, $parameters = false, $options = false) {
		return $this->callQuery($module, "delete", $service, $parameters, $options);
	}
	
	public function callProcedureSQL($module, $service, $parameters = false, $options = false) {
		return $this->callQuerySQL($module, "procedure", $service, $parameters, $options);
	}
	public function callProcedure($module, $service, $parameters = false, $options = false) {
		return $this->callQuery($module, "procedure", $service, $parameters, $options);
	}
	
	private function callService($module_id, $service_type, $service_id, $parameters, $options, &$includes = false) {
		$module = $this->modules[$module_id];
		$module_path = $this->modules_path[$module_id];
		//echo "$module_id, $service_type, $service_id<br>";
		//echo "<pre>$module_id:";print_r($this->modules_path);print_r(array_keys($module));print_r($module[$service_id]);echo "</pre>";
		
		if(isset($module[$service_id])) {
			$service = $module[$service_id];
			$file_name = isset($service[0]) ? $service[0] : null;
			$file_service_id = isset($service[1]) ? $service[1] : null;
			$file_type = isset($service[2]) ? $service[2] : null;
			
			$query_path = $module_path . ($file_type != "file" ? "/" . $file_name : "");
			$query_id = $file_service_id;
			//echo "$query_path | $query_id<br>";
			
			if($query_path && file_exists($query_path)) {
				$SQLClient = $this->getSQLClient($options);
				
				if($this->isCacheActive()) {
					$SQLClient->setCacheRootPath( $this->getCacheLayer()->getCachedDirPath() );
				}
				else {
					$SQLClient->setCacheRootPath(false);
				}
				
				$SQLClient->loadXML($query_path);
				$query = $SQLClient->getQuery($service_type, $query_id);
				//echo "<pre>";print_r($query);echo "</pre>";
				
				if($query) {
					if($options["call_query_sql"]) {
						return $SQLClient->getQuerySQL($query, $parameters, $options);
					}
					else {
						$includes = $SQLClient->getLibsOfResultClassAndMap($query);
						
						return $SQLClient->execQuery($query, $parameters, $options);
					}
				}
				return false;
			}
			launch_exception(new DataAccessLayerException(1, $query_path));
			return false;
		}
		launch_exception(new DataAccessLayerException(2, $module_id . "::" . $service_type . "::" . $service_id));
		return false;
	}
	
	protected function getRegexToGrepDataAccessFilesAndGetNodeIds() {
		return "/<(insert|update|delete|select|procedure)([^>]*)([ ]+)id=([\"]?)([\w\-\+&#;\s\.]+)([\"]?)/iu"; //'\w' means all words with '_' and '/u' means with accents and ç too. And &#; bc the query may contain accents in unicode which will be someting like &#222;
	}
}
?>
