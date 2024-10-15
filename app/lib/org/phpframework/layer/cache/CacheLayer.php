<?php
include get_lib("org.phpframework.layer.cache.exception.CacheLayerException");
include_once get_lib("org.phpframework.cache.CacheHandlerUtil");
include_once get_lib("org.phpframework.xmlfile.XMLFileParser");
include_once get_lib("org.phpframework.bean.BeanSettingsFileFactory");

/*
Basically this is used by the layer.cache.CacheLayer which is used by the layer.cache.BusinessLogicCacheLayer, layer.cache.PresentationCacheLayer, layer.cache.DataAccessCacheLayer.
*/
abstract class CacheLayer {
	public $modules_cache = array();
	public $keys = array();
	public $service_related_keys_to_delete = array();
	
	public $bean_objs;
	public $Layer;
	public $settings;
	
	public function __construct($Layer, $settings) {
		$this->Layer = $Layer;
		$this->settings = $settings;
	}
		
	abstract public function getModulePath($module_id);
	abstract public function initModuleCache($module_id);
	abstract public function getModuleCacheObj($module_id, $service_id, $data);
	abstract public function getCachedDirPath();
	
	public function prepareModulesCache($module_id) {
		if (empty($this->modules_cache[$module_id]["beans"]) || !is_array($this->modules_cache[$module_id]["beans"]))
			$this->modules_cache[$module_id]["beans"] = array();
		if (empty($this->modules_cache[$module_id]["services"]) || !is_array($this->modules_cache[$module_id]["services"]))
			$this->modules_cache[$module_id]["services"] = array();
		
		$services = $this->modules_cache[$module_id]["services"];
		$service_keys = array_keys($services);
		$t = count($service_keys);
		for ($i = 0; $i < $t; $i++) {
			$key = $service_keys[$i];
	
			if (empty($services[$key]["key"])) {
				$this->modules_cache[$module_id]["services"][$key]["key"] = $key;
				$this->keys[$module_id][$key] = $key;
			}
			else
				$this->keys[$module_id][ $services[$key]["key"] ] = $key;
			
			if (empty($services[$key]["module_id"]))
				$this->modules_cache[$module_id]["services"][$key]["module_id"] = $module_id;
		}
	
		$this->prepareServiceRelatedRulesToDelete($module_id);
	}
	
	/*
	Reads each service and gets the TO_DELETE information.
	Then checks for each key what are the KEYS that are IN the services, this is:
		Imagine that you have:
			Lets say that you have a service: INSERT_ITEM which has:
				<to_delete>
					<service type="prefix">
						<key>select_item_id-</key>
					</service>
					<service type="prefix">
						<key>select_items</key>
					</service>
				</to_delete>
			Then you have other services SELECT_ITEM and SELECT_ITEMS:
				<service id="select_item" module_id="<?php echo $vars["current_business_logic_module_id"]; ?>" cache_handler="ServiceCacheHandler" to_cache="true" cache_type="php" ttl="600">
					<key>select_item_id-&lt;?php echo $input;?&gt;-&lt;?php echo $options['db_driver'];?&gt;</key>
				</service>
				
				<service id="select_items" module_id="<?php echo $vars["current_business_logic_module_id"]; ?>" cache_handler="ServiceCacheHandler" to_cache="true" cache_type="php" ttl="600">
					<key>select_items_type-&lt;?php echo $input["type"];?&gt;_rownum-&lt;?php echo $input["row"];?&gt;-&lt;?php echo $options['db_driver'];?&gt;</key>
				</service>
			
		Then the prepareServiceRelatedRulesToDelete creates the following array $this->service_related_keys_to_delete[$module_id]:
			Array
			(
			    [select_item] => Array
			        (
			            [0] => Array
			                (
			                    [TYPE] => PREFIX
			                    [KEY] => select_item_id-
			                )

			        )
			    [select_items] => Array
			        (
			            [0] => Array
			                (
			                    [TYPE] => PREFIX
			                    [KEY] => select_items
			                )

			        )
			    [procedure_items] => Array
			        (
			        )
			    [procedure_items_class] => Array
			        (
			        )
			)
	*/
	private function prepareServiceRelatedRulesToDelete($module_id) {
		$services = isset($this->modules_cache[$module_id]["services"]) ? $this->modules_cache[$module_id]["services"] : null;
		$service_keys = array_keys($services);
		$t = count($service_keys);
		
		for ($i = 0; $i < $t; $i++) {
			$read_service_id = isset($service_keys[$i]) ? $service_keys[$i] : null;
			$read_service_key = isset($services[ $read_service_id ]["key"]) ? $services[ $read_service_id ]["key"] : null;
			
			if (empty($services[ $read_service_id ]["to_cache"])) {
				$service_related_keys_to_delete = array();
				
				for ($j = 0; $j < $t; $j++) {
					$write_service_key = $service_keys[$j];
					
					if (!empty($services[ $write_service_key ]["to_delete"])) {
						$to_delete = $services[ $write_service_key ]["to_delete"];
						$t3 = count($to_delete);
						
						for($w = 0; $w < $t3; $w++) {
							$item = $to_delete[$w];
							$item_key = isset($item["key"]) ? $item["key"] : null;
							$item_type = isset($item["type"]) ? $item["type"] : null;
							
							if(CacheHandlerUtil::checkIfKeyTypeMatchValue($read_service_key, $item_key, $item_type)) {
								//echo "<br>$read_service_key, $item_key, $item_type\n";
								$service_related_keys_to_delete[md5(serialize($item))] = $item;//md5 serves to take the repeated values
							}
						}
					}
				}
				$this->service_related_keys_to_delete[$module_id][$read_service_id] = array_values($service_related_keys_to_delete);
			}
		}
		//echo "<pre>";print_r($services);die();
	}
	
	/*
	Checks if the $service_id has cache and it has, create the cache for the $service_id.
	Additionally add the correspondent cache key ($service_key) for the $service_id, to the related services, this is call:
		$CacheHandler->addServiceToRelatedKeysToDelete($service_id, $key, $service_related_keys_to_delete, $service["cache_type"]);
		
		(Basically the addServiceToRelatedKeysToDelete function, loops the $service_related_keys_to_delete and for each item, adds the $service_key => but check this explanation bellow)
	
	Then based in the TO_DELETE keys, gets all the service_ids to delete and for each call the delete method.
	*/
	public function check($module_id, $service_id, $data, &$result, $options = false) {
//echo "<br>\n".get_class($this)."::check($module_id, $service_id, ...)";
		$this->initModuleCache($module_id);
		
		$service = $this->getServiceData($module_id, $service_id);
		
		if ($service) {
			$service_module_id = isset($service["module_id"]) ? $service["module_id"] : null;
			$CacheHandler = $this->getModuleCacheObj($service_module_id, $service_id, $data);
			
			if ($CacheHandler) {
				$status = $this->getValidationScriptStatus($service, $data, $result);
				
				if ($status) {
					//CACHE CREATION
					//echo "\nservice:<pre>";print_r($service);
					if (!empty($service["to_cache"])) {
						$service_cache_type = isset($service["cache_type"]) ? $service["cache_type"] : null;
						$service_key = isset($service["key"]) ? $service["key"] : null;
						$key = $this->getKey($service_key, $data, $options);
						
						if ($CacheHandler->create($service_id, $key, $result, $service_cache_type)) {
							$service_related_keys_to_delete = $this->service_related_keys_to_delete[$service_module_id][$service_id];
							//echo "<pre>";print_r($this->service_related_keys_to_delete);die();
							//echo "\nservice_related_keys_to_delete:<pre>";print_r($service_related_keys_to_delete);
							$CacheHandler->addServiceToRelatedKeysToDelete($service_id, $key, $service_related_keys_to_delete, $service_cache_type);
						}
					}
					
					//DELETE CACHE RELATIONS
					$relations = isset($service["to_delete"]) ? $service["to_delete"] : null;
					$services_to_delete = $this->search($module_id, $relations);
					
					//echo "\nservices_to_delete for $module_id::$service_id:<pre>";print_r($relations);print_r($services_to_delete);print_r($data);echo "</pre>";die();
					
					/*
					CACHE.XML:
					...
						<to_delete>
							<service>
								<key>article_properties_&lt;?php echo $_SERVER["HTTP_HOST"] . "_" . $GLOBALS["condo_id"] . "_" . $_GET["article_id"]; ?&gt;</key>
							</service>
							<service type="prefix">
								<key>channel_articles_&lt;?php echo $_SERVER["HTTP_HOST"] . "_" . $input["object_to_objects"][0]["object_type_id"] . "_" . $input["object_to_objects"][0]["object_id"] . "_" . $input["object_to_objects"][0]["group"] . "_"; ?&gt;</key>
							</service>
						</to_delete>
					...
					
					$relations sample:
						Array(
						    [0] => Array
							   (
								  [key] => article_properties_<?php echo $_SERVER["HTTP_HOST"] . "_" . $GLOBALS["condo_id"] . "_" . $_GET["article_id"]; ?>
								  [module_id] => 
							   )

						    [1] => Array
							   (
								  [type] => prefix
								  [key] => channel_articles_<?php echo $_SERVER["HTTP_HOST"] . "_" . $input["object_to_objects"][0]["object_type_id"] . "_" . $input["object_to_objects"][0]["object_id"] . "_" . $input["object_to_objects"][0]["group"] . "_"; ?>
								  [module_id] => 
							   )
						)
					
					$services_to_delete sample:
						Array(
						    [private/article/article_properties] => Array
							   (
								  [key] => article_properties_<?php echo $_SERVER["HTTP_HOST"] . "_" . $GLOBALS["condo_id"] . "_" . $_GET["article_id"]; ?>
								  [module_id] => 
							   )

						    [private/article/channel_articles] => Array
							   (
								  [type] => prefix
								  [key] => channel_articles_<?php echo $_SERVER["HTTP_HOST"] . "_" . $input["object_to_objects"][0]["object_type_id"] . "_" . $input["object_to_objects"][0]["object_id"] . "_" . $input["object_to_objects"][0]["group"] . "_"; ?>
								  [module_id] => 
							   )
						)
					*/
					
					$this->deleteSearchedServices($service_module_id, $services_to_delete, $data, $options);
				}
			}
		}
		//echo "\n----------------------------------------\n";
		return true;
	}
	
	/*
	$searched_services sample:
		Array(
		    [ #service_id# ] => Array
		        (
		            [TYPE] => PREFIX | SUFFIX => the search type
		            [KEY] => #the key in the <to_delete> tag for each service. Check the XML sample bellow#
		        )
		        
		or
		
		Array(
		    [select_item] => Array
		        (
		            [TYPE] => PREFIX
		            [KEY] => select_item_id-
		        )

		    [select_items] => Array
		        (
		            [TYPE] => PREFIX
		            [KEY] => select_items
		        )
		)
		
		or
		
		Array(
		    [private/article/article_properties] => Array
			   (
				  [key] => article_properties_<?php echo $_SERVER["HTTP_HOST"] . "_" . $GLOBALS["condo_id"] . "_" . $_GET["article_id"]; ?>
				  [module_id] => 
			   )

		    [private/article/channel_articles] => Array
			   (
				  [type] => prefix
				  [key] => channel_articles_<?php echo $_SERVER["HTTP_HOST"] . "_" . $input["object_to_objects"][0]["object_type_id"] . "_" . $input["object_to_objects"][0]["object_id"] . "_" . $input["object_to_objects"][0]["group"] . "_"; ?>
				  [module_id] => 
			   )
		)
	
	FROM XML FILE:
		<service id="private/article/article_properties" cache_handler="ServiceCacheHandler" to_cache="true" ttl="600">
			<validation_script>return $_GET["article_id"] > 0;</validation_script>
			<key>article_properties_&lt;?php echo $_SERVER["HTTP_HOST"] . "_" . $GLOBALS["condo_id"] . "_" . $_GET["article_id"]; ?&gt;</key>
		</service>
		<service id="private/article/channel_articles" cache_handler="ServiceCacheHandler" to_cache="true" ttl="600">
			<key>channel_articles_&lt;?php echo $_SERVER["HTTP_HOST"] . "_" . $input["object_type_id"] . "_" . $input["object_id"] . "_" . $input["group"] . "_" . hash("crc32b", strtolower($_GET["tag"])); ?&gt;</key>
		</service>
		<service id="private/admin/article/edit_article" cache_handler="ServiceCacheHandler">
			<validation_script>return $_POST;</validation_script>
			<to_delete>
				<service>
					<key>article_properties_&lt;?php echo $_SERVER["HTTP_HOST"] . "_" . $GLOBALS["condo_id"] . "_" . $_GET["article_id"]; ?&gt;</key>
				</service>
				<service type="prefix">
					<key>channel_articles_&lt;?php echo $_SERVER["HTTP_HOST"] . "_" . $input["object_to_objects"][0]["object_type_id"] . "_" . $input["object_to_objects"][0]["object_id"] . "_" . $input["object_to_objects"][0]["group"] . "_"; ?&gt;</key>
				</service>
			</to_delete>
		</service>
	*/
	public function deleteSearchedServices($module_id, $searched_services, $data = array(), $options = false) {
		$status = true;
		
		if ($searched_services)
			foreach($searched_services as $name => $value) {
				$data_aux = $data;
				$service_data = $value;
				$service_data["id"] = $name;
		
				if (!empty($service_data["script"]))
					$this->executeScript($service_data["script"], $data_aux);
				
				if (!$this->delete($module_id, $service_data, $data_aux, $options))
					$status = false;
			}
		
		return $status;
	}
	
	/*
	$service_key_to_search: channel_articles_&lt;?php echo $_SERVER["HTTP_HOST"] . "_" . $input["object_to_objects"][0]["object_type_id"] . "_" . $input["object_to_objects"][0]["object_id"] . "_" . $input["object_to_objects"][0]["group"] . "_"; ?&gt;
	$service_type_to_search: prefix
	*/
	public function deleteServiceBySearch($module_id, $service_key_to_search, $service_type_to_search, $data, $options = false) {
		$services_to_search = array(
			array("key" => $service_key_to_search, "type" => $service_type_to_search)
		);
		
		$searched_services = $this->search($module_id, $services_to_search);
		
		return $this->deleteSearchedServices($module_id, $searched_services, $data, $options);
	}
	
	/*
	For each TO_DELETE element call delete with the following parameters:
		$service_id = "select_item";
		$service_key = "select_item_id-";
		$service_key_type = "prefix";
		$service_module_id = "test";
	
	Gets the real service_key from the $service_id, this is, $real_service_key_from_service_id.
		$real_service_key_from_service_id = "select_item_id-&lt;?php echo $input;?&gt;-&lt;?php echo $options['db_driver'];?&gt;";
	
	Then checks if the $service_key is IN the $real_service_key_from_service_id, based of the checkIfKeyTypeMatchValue function and the $service_key_type.
	If it does deleteAll elements from the $service_id;
	Otherwise call $CacheHandler->delete;
	*/
	public function delete($module_id, $service_data, $data, $options = false) {
		//echo "<br>module_id:$module_id<pre>";print_r($service_data);print_r($data);print_r($options);echo"</pre>";
		$service_id = isset($service_data["id"]) ? $service_data["id"] : null;
		$service_key = isset($service_data["key"]) ? $service_data["key"] : null;
		$service_key_type = isset($service_data["type"]) ? $service_data["type"] : null;
		$service_module_id = !empty($service_data["module_id"]) ? $service_data["module_id"] : $module_id;
		//echo "<pre>";print_r($this->modules_cache[$service_module_id]["services"]);die();
		
		$this->initModuleCache($service_module_id);
	
		$service = $this->getServiceData($service_module_id, $service_id);
		
		if ($service) {
			$CacheHandler = $this->getModuleCacheObj(isset($service["module_id"]) ? $service["module_id"] : null, $service_id, $data);
			
			if ($CacheHandler) {
				$key = $this->getKey($service_key, $data, $options);
				$real_service_key_from_service_id = isset($this->modules_cache[$service_module_id]["services"][$service_id]["key"]) ? $this->modules_cache[$service_module_id]["services"][$service_id]["key"] : null;
				$service_cache_type = isset($service["cache_type"]) ? $service["cache_type"] : null;
				
				//echo "<br>$service_id($key, $real_service_key_from_service_id, $service_key, $service_key_type)<br>";
				//echo "status:".strlen($real_service_key_from_service_id)." == ".strlen($service_key)."!".($real_service_key_from_service_id == $service_key)."<br>";
				
				/*Checks if $service_key is equal to $service_id or (if $real_service_key_from_service_id == $service_key and $service_key == $key)
				  Basically checks if the $service_key is the same than $service_id and if so, deletes all data
				  Or if not, checks if $real_service_key_from_service_id is the same than $service_key but only if the $service_key is a static value, this is, without php code to parse.
				  
				  example:
				  	if:
				  		$service_id = "get_admin_user_data"
				  		$service_key = "get_admin_user_data"
				  	then
				  		deletes all
				  	
				  	else if:
				  		$service_id = "admin/user/get_data"
				  		$real_service_key_from_service_id = "get_admin_user_data"
				  		$service_key = "get_admin_user_data"
				  	then 
				  		deletes all
				  		
				  	else if:
				  		$service_id = "get_admin_user_data"
				  		$real_service_key_from_service_id = "get_admin_user_data_<?= $_GET["user_id"] ?>"
				  		$service_key = "get_admin_user_data_<?= $_GET["user_id"] ?>"
				  	then 
				  		do NOT deletes all.
				  		but deletes only a specific cached file
				*/
				
				if(CacheHandlerUtil::checkIfKeyTypeMatchValue($service_id, $service_key, $service_key_type) || (CacheHandlerUtil::checkIfKeyTypeMatchValue($real_service_key_from_service_id, $service_key, $service_key_type) && $key == $service_key)) {
					//echo "<br>deleteAll($service_id, ${service[cache_type]})";die();
					return $CacheHandler->deleteAll($service_id, $service_cache_type);
				}
				else {
					$delete_mode = $this->getDeleteMode(isset($service["module_id"]) ? $service["module_id"] : null, $service_id, $service_key, $service_key_type);
					//echo "<br>$service_id, $key, array(cache_type => ${service[cache_type]}, key_type => $service_key_type, original_key => $service_key, delete_mode => $delete_mode)<br>";
					return $CacheHandler->delete($service_id, $key, array("cache_type" => $service_cache_type, "key_type" => $service_key_type, "original_key" => $service_key, "delete_mode" => $delete_mode));
				}
			}
		}
		return true;
	}
	
	/*
	if:
		$service_key_type = "prefix";
		$service_id = "select_item";
		$service_key = "select_item_id-";
		$service_related_keys_to_delete = Array
	        (
	            [0] => Array
	                (
	                    [TYPE] => PREFIX
	                    [KEY] => select_items
	                )
	        )
		
		If the $service_related_keys_to_delete[$i]["key"] == $service_key; return 3
		otherwise return 2 if the $service_key_type == "prefix" or REGEX, etc...
		otherwise return 1, which means it must be the EQUAL.
	*/
	private function getDeleteMode($module_id, $service_id, $service_key, $service_key_type) {
		if ($service_key_type == "regexp" || $service_key_type == "regex" || $service_key_type == "start" || $service_key_type == "begin" || $service_key_type == "prefix" || $service_key_type == "middle" || $service_key_type == "end" || $service_key_type == "finish" || $service_key_type == "suffix") {
			$service_related_keys_to_delete = isset($this->service_related_keys_to_delete[ $module_id ][ $service_id ]) ? $this->service_related_keys_to_delete[ $module_id ][ $service_id ] : null;
			//echo $service_id."|".$service_key;print_r($service_related_keys_to_delete);
			$t = $service_related_keys_to_delete ? count($service_related_keys_to_delete) : 0;
			
			for ($i = 0; $i < $t; $i++)
				if (isset($service_related_keys_to_delete[$i]["key"]) && isset($service_related_keys_to_delete[$i]["type"]) && $service_related_keys_to_delete[$i]["key"] == $service_key && $service_related_keys_to_delete[$i]["type"] == $service_key_type)
					return 3;
			
			return 2;
		}
		return 1;
	}
	
	/*
	Gets the data of a service
	*/
	public function get($module_id, $service_id, $data, $options = false) {
//echo "<br>\nget($module_id, $service_id, ...)<pre>";print_r($data);print_r($options);die();
		$this->initModuleCache($module_id);
		
		$service = $this->getServiceData($module_id, $service_id);
		
		if ($service) {
			$CacheHandler = $this->getModuleCacheObj(isset($service["module_id"]) ? $service["module_id"] : null, $service_id, $data);
			
			if ($CacheHandler) {
				$key = $this->getKey(isset($service["key"]) ? $service["key"] : null, $data, $options);
				return $CacheHandler->get($service_id, $key, isset($service["cache_type"]) ? $service["cache_type"] : null);
			}
		}
		
		return false;
	}
	
	/*
	Gets the headers of a service
	*/
	public function getHeaders($module_id, $service_id) {
//echo "<br>\nget($module_id, $service_id, ...)<pre>";die();
		$this->initModuleCache($module_id);
		
		$service = $this->getServiceData($module_id, $service_id);
		//echo "asd";print_r($service);die();
		return $service ? str_replace('\n', "\n", isset($service["headers"]) ? $service["headers"] : "") : false;
	}
	
	/*
	Checks if the data of a service is valid according with the TTL and the validation script if exists.
	Additionally calls the $CacheHandler->checkServiceToRelatedKeysToDelete for the service_related_keys_to_delete of the correspondent $service_id.
	*/
	public function isValid($module_id, $service_id, $data, $options = false) {
//echo "<br>\nisValid($module_id, $service_id, ...)";print_r($data);	
		$this->initModuleCache($module_id);
		
		$service = $this->getServiceData($module_id, $service_id);
		//if ($module_id===1){echo "<pre>";print_r($service);die("asds");}
		
		if ($service) {
			$service_module_id = isset($service["module_id"]) ? $service["module_id"] : null;
			$CacheHandler = $this->getModuleCacheObj($service_module_id, $service_id, $data);
			
			if ($CacheHandler) {
				//checks the validation script
				$status = $this->getValidationScriptStatus($service, $data, $aux);
				
				if ($status) {
					$service_key = isset($service["key"]) ? $service["key"] : null;
					$key = $this->getKey($service_key, $data, $options);
					
					//checks the file TTL
					$service_ttl = isset($service["ttl"]) ? $service["ttl"] : null;
					$service_cache_type = isset($service["cache_type"]) ? $service["cache_type"] : null;
					$is_valid = $CacheHandler->isValid($service_id, $key, $service_ttl, $service_cache_type);
					
					if ($is_valid) {
						$service_related_keys_to_delete = $this->service_related_keys_to_delete[$service_module_id][$service_id];
						$CacheHandler->checkServiceToRelatedKeysToDelete($service_id, $key, $service_related_keys_to_delete, $service["cache_type"]);
				
						return true;
					}
				}
			}
		}
		return false;
	}
	
	/*
	For each item in the $services_to_search, loop the $services_keys and checks if any of the keys are inside of the others.
	Then return the searched result;
	Sample of the result:
		Array(
		    [ #service_id# ] => Array
		        (
		            [TYPE] => PREFIX | SUFFIX => the search type
		            [KEY] => #the key in the <to_delete> tag for each service. Check the XML sample bellow#
		        )
		        
		or
		
		Array(
		    [select_item] => Array
		        (
		            [TYPE] => PREFIX
		            [KEY] => select_item_id-
		        )

		    [select_items] => Array
		        (
		            [TYPE] => PREFIX
		            [KEY] => select_items
		        )
		)
		
		or 
		
		Array(
		    [private/article/article_properties] => Array
			   (
				  [key] => article_properties_<?php echo $_SERVER["HTTP_HOST"] . "_" . $GLOBALS["condo_id"] . "_" . $_GET["article_id"]; ?>
				  [module_id] => 
			   )

		    [private/article/channel_articles] => Array
			   (
				  [type] => prefix
				  [key] => channel_articles_<?php echo $_SERVER["HTTP_HOST"] . "_" . $input["object_to_objects"][0]["object_type_id"] . "_" . $input["object_to_objects"][0]["object_id"] . "_" . $input["object_to_objects"][0]["group"] . "_"; ?>
				  [module_id] => 
			   )
		)
	
	FROM XML FILE:
		<service id="private/article/article_properties" cache_handler="ServiceCacheHandler" to_cache="true" ttl="600">
			<validation_script>return $_GET["article_id"] > 0;</validation_script>
			<key>article_properties_&lt;?php echo $_SERVER["HTTP_HOST"] . "_" . $GLOBALS["condo_id"] . "_" . $_GET["article_id"]; ?&gt;</key>
		</service>
		<service id="private/article/channel_articles" cache_handler="ServiceCacheHandler" to_cache="true" ttl="600">
			<key>channel_articles_&lt;?php echo $_SERVER["HTTP_HOST"] . "_" . $input["object_type_id"] . "_" . $input["object_id"] . "_" . $input["group"] . "_" . hash("crc32b", strtolower($_GET["tag"])); ?&gt;</key>
		</service>
		<service id="private/admin/article/edit_article" cache_handler="ServiceCacheHandler">
			<validation_script>return $_POST;</validation_script>
			<to_delete>
				<service>
					<key>article_properties_&lt;?php echo $_SERVER["HTTP_HOST"] . "_" . $GLOBALS["condo_id"] . "_" . $_GET["article_id"]; ?&gt;</key>
				</service>
				<service type="prefix">
					<key>channel_articles_&lt;?php echo $_SERVER["HTTP_HOST"] . "_" . $input["object_to_objects"][0]["object_type_id"] . "_" . $input["object_to_objects"][0]["object_id"] . "_" . $input["object_to_objects"][0]["group"] . "_"; ?&gt;</key>
				</service>
			</to_delete>
		</service>
	*/
	public function search($module_id, $services_to_search) {
		$this->initModuleCache($module_id);
	
		$service_keys = array_keys($this->keys[$module_id]);
		//echo "<pre>";print_r($this->keys[$module_id]);die();
		
		//echo "<pre>";print_r($services_to_search);die();
		$found_services = array();
		$t = $services_to_search ? count($services_to_search) : 0;
		
		for ($i = 0; $i < $t; $i++) {
			$service_to_search = $services_to_search[$i];
			$key = isset($service_to_search["key"]) ? $service_to_search["key"] : null;
			$type = isset($service_to_search["type"]) ? $service_to_search["type"] : null;
			$key_module_id = !empty($service_to_search["module_id"]) ? $service_to_search["module_id"] : $module_id;
			
			if ($key_module_id == $module_id) {
				if (!empty($this->keys[$module_id][ $key ]))
					$found_services[ $this->keys[$module_id][ $key ] ] = $service_to_search;
				else if (in_array($key, $this->keys[$module_id]))
					$found_services[ $key ] = $service_to_search;
				else {
					$key_aux = self::stripKeyPHPCode($key);
					$predefined_type = ($type == "regexp" || $type == "regex" || $type == "start" || $type == "begin" || $type == "prefix" || $type == "middle" || $type == "end" || $type == "finish" || $type == "suffix") ? true : false;
					
					$t2 = count($service_keys);
					
					for ($j = 0; $j < $t2; $j++) {
						$service_key = $service_keys[$j];
						
						//strip php tags to find more related services
						$service_keys_aux = self::stripKeyPHPCode($service_key);
						
						$continue = false;
						if ($predefined_type) {
							if (CacheHandlerUtil::checkIfKeyTypeMatchValue($service_keys_aux, $key_aux, $type))
								$continue = true;
						}
						else if ($service_keys_aux == $key_aux)
							$continue = true;
						
						if ($continue && !empty($this->keys[$module_id][$service_key]))
							$found_services[ $this->keys[$module_id][$service_key] ] = $service_to_search;
					}
				}
			}
			else {
				$found_services_aux = $this->search($key_module_id, array($service_to_search));
				$found_services = array_merge($found_services, $found_services_aux);
			}
		}
		return $found_services;
	}
	
	private function getServiceData($module_id, $service_id) {
		if (!empty($this->modules_cache[$module_id]["services"][$service_id]) && empty($this->modules_cache[$module_id]["services"][$service_id]["id_type"]))
			return $this->modules_cache[$module_id]["services"][$service_id];
		else if (!empty($this->modules_cache[$module_id]["services"]))
			foreach ($this->modules_cache[$module_id]["services"] as $id => $service)
				if (!empty($service["id_type"]) && CacheHandlerUtil::checkIfKeyTypeMatchValue($service_id, $id, $service["id_type"])) 
					return $service;
		
		return null;
	}
	
	private function getKey($key, $input, $options = false) {
		//echo "<pre>";print_r($input);die();
		$key = str_replace("&lt;?", "<?", $key);
		$key = str_replace("?&gt;", "?>", $key);
		$vars = array("input" => $input, "options" => $options);
		
		$key = PHPScriptHandler::parseContent($key, $vars);
		
		if (isset($options["key_prefix"]))
			$key = $options["key_prefix"] . $key;
		
		if (isset($options["key_suffix"]))
			$key .= $options["key_suffix"];
		
		return $key;
	}
	
	private static function stripKeyPHPCode($key) {
		$key = str_replace("&lt;?", "<?", $key);
		$key = str_replace("?&gt;", "?>", $key);
	
		$start = strpos($key, "<?");
		
		if ($start !== false) {
			$new_key = substr($key, 0, $start);
			
			$double_quotes_open = false;
			$single_quotes_open = false;
			$l = strlen($key);
			
			if (is_numeric($key))
				$key = (string)$key; //bc of php > 7.4 if we use $var[$i] gives an warning
			
			for ($i = $start + 2; $i < $l; $i++) {
				if ($key[$i] == "'" && !$double_quotes_open) {
					$single_quotes_open = !$single_quotes_open;
				}
				elseif ($key[$i] == '"' && !$single_quotes_open) {
					$double_quotes_open = !$double_quotes_open;
				}
				elseif ($key[$i] == "?" && $i + 1 < $l && $key[$i + 1] == ">" && !$double_quotes_open && !$single_quotes_open) {
					$start = strpos($key, "<?", $i + 2);
					$start = $start === false ? strlen($key) : $start;
				
					$new_key .= substr($key, $i + 2, $start - ($i + 2));
					$i = $start + 1;
				}
			}
			return $new_key;
		}
		return $key;
	}
	
	private function getValidationScriptStatus($service, &$data, &$return) {
		$validation_script = isset($service["validation_script"]) ? $service["validation_script"] : null;
		return $this->executeScript($validation_script, $data, $return);
	}
	
	private function executeScript($script, &$input, &$output = false) {
		if ($script) {
			$status = null;
			
			try {
				$status = eval($script);
			} catch(Exception $e) {
				launch_exception($e);
			}
			
			return $status;
		}
		return true;
	}
	
	public function parseCacheFile($module_id, $cache_file_path) {
		$objs = $this->bean_objs;
		$external_vars = array(
			"objs" => $objs, 
			"vars" => isset($objs["vars"]) ? $objs["vars"] : null
		);
	
		$BeanSettingsFileFactory = new BeanSettingsFileFactory();
		$beans = $BeanSettingsFileFactory->getSettingsFromFile($cache_file_path, $external_vars);
		
		$content = file_get_contents($cache_file_path);
		$xml_schema_file_path = get_lib("org.phpframework.xmlfile.schema.beans", "xsd");
		$nodes = XMLFileParser::parseXMLContentToArray($content, $external_vars, $cache_file_path, $xml_schema_file_path);
		
		$first_node_name = is_array($nodes) ? array_keys($nodes) : array();
		$first_node_name = isset($first_node_name[0]) ? $first_node_name[0] : null;
		
		$services_node = isset($nodes[$first_node_name][0]["childs"]["services"][0]["childs"]["service"]) && is_array($nodes[$first_node_name][0]["childs"]["services"][0]["childs"]["service"]) ? $nodes[$first_node_name][0]["childs"]["services"][0]["childs"]["service"] : array();
		$services = array();
		$t = $services_node ? count($services_node) : 0;
		for($i = 0; $i < $t; $i++) {
			$service_node = $services_node[$i];
			
			$id = XMLFileParser::getAttribute($service_node, "id");
			
			$attr_names = array("file", "query", "obj", "constructor", "function", "cache_handler", "to_cache", "module_id", "cache_type", "ttl", "key", "validation_script", "id_type", "headers"); //Doesn't make sense add "namespace" here, bc the cache service id must be equal to the service_id called through "$EVC->getBroker()->callBusinessLogic($module_id, $service_id, ...)".
			$service = XMLFileParser::getAttributes($service_node, $attr_names);
			
			if(isset($service["to_cache"])) {
				$to_cache = strtolower($service["to_cache"]);
				$service["to_cache"] = $to_cache && $to_cache != "0" && $to_cache != "false" && $to_cache != "null" ? true : false;
			}
			
			if(isset($service_node["childs"]["to_delete"][0]["childs"]["service"])) {
				$to_delete_nodes = $service_node["childs"]["to_delete"][0]["childs"]["service"];
				
				$to_delete_services = array();
				$t2 = $to_delete_nodes ? count($to_delete_nodes) : 0;
				for($j = 0; $j < $t2; $j++) {
					$to_delete_node = $to_delete_nodes[$j];
					
					$attr_names = array("type", "module_id", "key", "script");
					$to_delete_service = XMLFileParser::getAttributes($to_delete_node, $attr_names);
					
					if(isset($to_delete_service["type"]))
						$to_delete_service["type"] = strtolower($to_delete_service["type"]);
					
					$to_delete_services[] = $to_delete_service;
				}
				$service["to_delete"] = $to_delete_services;
			}
			
			$services[$id] = $service;
		}
	
		//print_r($services);die();
		return array("beans" => $beans, "services" => $services);
	}
}
?>
