<?php
include_once get_lib("org.phpframework.util.xml.MyXML");
include_once get_lib("org.phpframework.util.xml.MyXMLArray");
include_once get_lib("org.phpframework.xmlfile.XMLFileParser");
include_once get_lib("org.phpframework.app");
include_once $EVC->getUtilPath("PHPVariablesFileHandler");
include_once $EVC->getUtilPath("WorkFlowBeansConverter");

class WorkFlowBeansFileHandler {
	private $beans_file_path;
	private $nodes;
	private $error;
	
	private $PHPVariablesFileHandler;
	
	public function __construct($beans_file_path, $global_variables_file_path) {
		$this->beans_file_path = $beans_file_path;
		
		$this->PHPVariablesFileHandler = new PHPVariablesFileHandler($global_variables_file_path);
	}
	
	public function getError() {
		return $this->error;
	}
	
	public function init() {
		if (!empty($this->beans_file_path) && file_exists($this->beans_file_path)) {
			$xml = file_get_contents($this->beans_file_path);
			$MyXML = new MyXML($xml);
			$this->nodes = $MyXML->toArray(array("lower_case_keys" => true));
		}
	}
	
	public function saveNodesBeans() {
		$MyXMLArray = new MyXMLArray($this->nodes);
		$xml = $MyXMLArray->toXML(array("lower_case_keys" => true));
		$xml = '<?xml version="1.0" encoding="UTF-8"?>' . str_replace("&amp;", "&", $xml);
		
		return file_put_contents($this->beans_file_path, $xml);
	}
	
	public function getBeanObject($bean_name) {
		$PHPFrameWork = new PHPFrameWork();
		$PHPFrameWork->init();
		
		$this->PHPVariablesFileHandler->startUserGlobalVariables();
		
		try {
			$PHPFrameWork->loadBeansFile($this->beans_file_path);
		}
		catch(Exception $e) {
			$this->error = isset($e->problem) ? $e->problem : null;
			return false;
		}
		
		$object = $PHPFrameWork->getObject($bean_name);
		
		if (is_a($object, "Layer"))
			$object->setPHPFrameWork($PHPFrameWork); //very important bc when we get $object->getPHPFrameWork()->getObject("UserCacheHandler") or $PEVC->getPresentationLayer()->getPHPFrameWork()->getObject("UserCacheHandler"), we want to get the UserCacheHandler from the PEVC and not from the __system.
		
		$this->PHPVariablesFileHandler->endUserGlobalVariables();
		
		return $object;
	}
	
	public function getBeanFromBeanPropertyReference($bean_class_to_search, $bean_property_name_to_search, $bean_property_reference_to_search) {
		$this->PHPVariablesFileHandler->startUserGlobalVariables();
		
		$BeanFactory = new BeanFactory();
		$BeanFactory->init(array("file" => $this->beans_file_path));
		$beans = $BeanFactory->getBeans();
		$found_bean = null;
		
		foreach($beans as $bn => $b) {
			$BeanFactory->initObject($bn);
			$obj = $BeanFactory->getObject($bn);
			
			if (is_a($obj, $bean_class_to_search) && $b->properties)
				foreach ($b->properties as $property)
					if ($property->name == $bean_property_name_to_search && $property->reference == $bean_property_reference_to_search) {
						$found_bean = $b;
						break;
					}
			
			if ($found_bean)
				break;
		}
		
		$this->PHPVariablesFileHandler->endUserGlobalVariables();
		
		return $found_bean;
	}
	
	public function getEVCBeanObject($bean_name, $path = false) {
		$evc_bean = $this->getBeanFromBeanPropertyReference("EVC", "presentationLayer", $bean_name);
		$object = $evc_bean ? $this->getBeanObject($evc_bean->name) : null;
		
		if ($object && $path) {
			//get the project_id, in case the path be a project. If is only a folder with projects inside, the $selected_project_id will be null;
			$selected_project_id = self::getPresentationProjectIdFromPath($object->getPresentationLayer(), $path);
			
			if ($selected_project_id) { //it may not be a project. It may be a folder with multiple projects within.
				$object->getPresentationLayer()->setSelectedPresentationId($selected_project_id);
			
				$layer_path = $object->getPresentationLayer()->getLayerPathSetting();
				
				$pre_init_config_file_path = $object->getConfigPath("pre_init_config");
				if (file_exists($pre_init_config_file_path)) {
					$global_variables_file_paths = $this->PHPVariablesFileHandler->getGlobalVariablesFilePaths();
					$global_variables_file_paths[] = $pre_init_config_file_path;
					
					$PHPVariablesFileHandlerForPreInitConfig = new PHPVariablesFileHandler($global_variables_file_paths);
					$PHPVariablesFileHandlerForPreInitConfig->startUserGlobalVariables();
					
					$PHPFrameWork = new PHPFrameWork();
					$PHPFrameWork->init();
					
					try {
						$PHPFrameWork->loadBeansFile($this->beans_file_path);
					}
					catch(Exception $e) {
						$this->error = isset($e->problem) ? $e->problem : null;
						return false;
					}
			
					$object = $PHPFrameWork->getObject($evc_bean->name);
					$object->getPresentationLayer()->setPHPFrameWork($PHPFrameWork);
					$object->getPresentationLayer()->setSelectedPresentationId($selected_project_id);
					
					$PHPVariablesFileHandlerForPreInitConfig->endUserGlobalVariables();
				}
			}
		}
		
		return $object;
	}
	
	public static function getPresentationProjectIdFromPath($PresentationLayer, $path) {
		if (empty($PresentationLayer->settings["presentation_webroot_path"]))
			launch_exception(new Exception("'PresentationLayer->settings[presentation_webroot_path]' cannot be undefined!"));
		
		$layer_path = $PresentationLayer->getLayerPathSetting();
		$webroot_path = isset($PresentationLayer->settings["presentation_webroot_path"]) ? $PresentationLayer->settings["presentation_webroot_path"] : null;
		
		$parts = explode("/", str_replace("\\", "/", $path));//because of windows
		$p = "";
		foreach ($parts as $part) {
			if ($part)
				$p .= ($p ? "/" : "") . $part;
			
			if (is_dir($layer_path . $p) && is_dir($layer_path . $p . "/" . $webroot_path)) {
				return $p;
			}
		}
		
		return null;
	}
	
	//used in the WorkFlowTestUnitHandler
	public static function getAllBeanObjects($global_variables_file_path, $beans_folder_path) {
		$bean_objects = array();
		
		//SET USER GLOBAL VARIABLES
		$PHPVariablesFileHandler = new PHPVariablesFileHandler($global_variables_file_path);
		$PHPVariablesFileHandler->startUserGlobalVariables();
		
		//PREPARE BEANS
		if (is_dir($beans_folder_path) && ($dir = opendir($beans_folder_path)) ) {
			while( ($file = readdir($dir)) !== false) {
				if (substr($file, strlen($file) - 4) == ".xml" && $file != "app.xml" && !strstr($file, "_bll_common_services.xml", true)) {
					$BeanFactory = new BeanFactory();
					$BeanFactory->init(array("file" => $beans_folder_path . $file));
					$BeanFactory->initObjects();
					
					$objs = $BeanFactory->getObjects();
					$bean_objects = array_merge($bean_objects, $objs);
				}
			}
			closedir($dir);
		}
		
		//ROLLBACK TO ORIGINAL GLOBAL VARIABLES
		$PHPVariablesFileHandler->endUserGlobalVariables();
		
		return $bean_objects;
	}
	
	public static function getAllLayersBeanObjs($global_variables_file_path, $beans_folder_path) {
		$objs = array();
		
		//SET USER GLOBAL VARIABLES
		$PHPVariablesFileHandler = new PHPVariablesFileHandler($global_variables_file_path);
		$PHPVariablesFileHandler->startUserGlobalVariables();
		
		//PREPARE BEANS
		if (is_dir($beans_folder_path) && ($dir = opendir($beans_folder_path)) ) {
			while( ($file = readdir($dir)) !== false) {
				if (substr($file, strlen($file) - 4) == ".xml" && $file != "app.xml" && !strstr($file, "_bll_common_services.xml", true)) {
					$BeanFactory = new BeanFactory();
					$BeanFactory->init(array("file" => $beans_folder_path . $file));
					$beans = $BeanFactory->getBeans();
					
					$BeanFactory->initObjects();
					
					foreach ($beans as $bean_name => $bean) {
						$obj = $BeanFactory->getObject($bean_name);
						
						if (is_a($obj, "ILayer"))
							$objs[ $bean_name ] = $obj;
					}
				}
			}
			
			closedir($dir);
		}
		
		//ROLLBACK TO ORIGINAL GLOBAL VARIABLES
		$PHPVariablesFileHandler->endUserGlobalVariables();
		
		return $objs;
	}
	
	public static function getBeanFilePath($global_variables_file_path, $beans_folder_path, $bean_name) {
		$bean_file_path = null;
		$ubn = strtolower($bean_name);
		
		//SET USER GLOBAL VARIABLES
		$PHPVariablesFileHandler = new PHPVariablesFileHandler($global_variables_file_path);
		$PHPVariablesFileHandler->startUserGlobalVariables();
		
		if (is_dir($beans_folder_path) && ($dir = opendir($beans_folder_path)) ) {
			while( ($file = readdir($dir)) !== false) {
				if (substr($file, strlen($file) - 4) == ".xml" && $file != "app.xml" && !strstr($file, "_bll_common_services.xml", true)) {
					$file_path = $beans_folder_path . $file;
					
					//use MyXML bc we want to parse the php first and we don't want to include the imports tags
					$xml = file_get_contents($file_path);
					$xml = PHPScriptHandler::parseContent($xml);
					$MyXML = new MyXML($xml);
					$nodes = $MyXML->toArray(array("lower_case_keys" => true));
					
					if (!empty($nodes["beans"][0]["childs"]["bean"])) {
						foreach ($nodes["beans"][0]["childs"]["bean"] as $bean)
							if (strtolower($bean["@"]["name"]) == $ubn) {
								$bean_file_path = $file_path;
								break;
							}
					}
					
					if ($bean_file_path)
						break;
				}
			}
			
			closedir($dir);
		}
		
		//ROLLBACK TO ORIGINAL GLOBAL VARIABLES
		$PHPVariablesFileHandler->endUserGlobalVariables();
		
		return $bean_file_path;
	}
	
	public static function getBeanName($global_variables_file_path, $beans_folder_path, $obj) {
		$obj_bean_name = null;
		
		if ($obj && is_a($obj, "ILayer")) {
			$layer_path = $obj->getLayerPathSetting();
			
			//SET USER GLOBAL VARIABLES
			$PHPVariablesFileHandler = new PHPVariablesFileHandler($global_variables_file_path);
			$PHPVariablesFileHandler->startUserGlobalVariables();
			
			//PREPARE BEANS
			if (is_dir($beans_folder_path) && ($dir = opendir($beans_folder_path)) ) {
				while( ($file = readdir($dir)) !== false) {
					if (substr($file, strlen($file) - 4) == ".xml" && $file != "app.xml" && !strstr($file, "_bll_common_services.xml", true)) {
						$BeanFactory = new BeanFactory();
						$BeanFactory->init(array("file" => $beans_folder_path . $file));
						$beans = $BeanFactory->getBeans();
						
						$BeanFactory->initObjects();
						
						foreach ($beans as $bean_name => $bean) {
							$bean_obj = $BeanFactory->getObject($bean_name);
							
							if (is_a($bean_obj, "ILayer") && $bean_obj->getLayerPathSetting() == $layer_path) {
								$obj_bean_name = $bean_name;
								break;
							}
						}
					}
				}
				
				closedir($dir);
			}
			
			//ROLLBACK TO ORIGINAL GLOBAL VARIABLES
			$PHPVariablesFileHandler->endUserGlobalVariables();
		}
		
		return $obj_bean_name;
	}
	
	public static function getLayerBeanFolderName($bean_file_path, $bean_name, $global_variables_file_path = false) {
		$WorkFlowBeansFileHandler = new WorkFlowBeansFileHandler($bean_file_path, $global_variables_file_path);
		$obj = $WorkFlowBeansFileHandler->getBeanObject($bean_name);
		
		return self::getLayerObjFolderName($obj);
	}
	
	public static function getLayerObjFolderName($obj) {
		if ($obj && is_a($obj, "ILayer")) {
			$layer_path = $obj->getLayerPathSetting();
			$layer_path = substr($layer_path, strlen(LAYER_PATH));
			$layer_folder_name = substr($layer_path, -1) == "/" ? substr($layer_path, 0, -1) : $layer_path;
			
			return $layer_folder_name;
		}
		
		return null;
	}
	
	//To be used by the AdminMenuHandler and WorkFlowTestUnitHandler
	public static function getLayerNameFromBeanObject($bean_name, $obj) {
		$suffix = null;
		
		if (is_a($obj, "DBLayer"))
			$suffix = "DBLayer";
		else if (is_a($obj, "DataAccessLayer"))
			$suffix = is_a($obj, "HibernateDataAccessLayer") ? "HDALayer" : "IDALayer";
		else if (is_a($obj, "BusinessLogicLayer")) 
			$suffix = "BLLayer";
		else if (is_a($obj, "PresentationLayer"))
			$suffix = "PLayer";
		
		return $suffix && substr($bean_name, - strlen($suffix)) == $suffix ? substr($bean_name, 0, strlen($bean_name) - strlen($suffix)) : $bean_name;
	}
	
	public static function getLayerBrokersSettings($global_variables_file_path, $beans_folder_path, $brokers, $get_broker_code_function = "") {
		$business_logic_brokers = array();
		$business_logic_brokers_obj = array();

		$data_access_brokers = array();
		$data_access_brokers_obj = array();

		$ibatis_brokers = array();
		$ibatis_brokers_obj = array();

		$hibernate_brokers = array();
		$hibernate_brokers_obj = array();

		$db_brokers = array();
		$db_brokers_obj = array();
		
		if ($brokers)
			foreach ($brokers as $broker_name => $broker) {
				$layer_props = self::getLocalBeanLayerFromBroker($global_variables_file_path, $beans_folder_path, $broker);
				$layer_bean_name = isset($layer_props[0]) ? $layer_props[0] : null;
				$layer_bean_file_path = isset($layer_props[1][0]) ? $layer_props[1][0] : null;
				$layer_bean_file_name = substr($layer_bean_file_path, strlen($beans_folder_path));
				$layer_bean_file_name = substr($layer_bean_file_name, 0, 1) == "/" ? substr($layer_bean_file_name, 1) : $layer_bean_file_name;
				
				$prop = array(
					$broker_name, //broker_name
					$layer_bean_file_name, //bean_file_name
					$layer_bean_name, //bean_name
				);
				
				if (is_a($broker, "IBusinessLogicBrokerClient")) {
					$business_logic_brokers[] = $prop;
					$business_logic_brokers_obj[$broker_name] = $get_broker_code_function . '("' . $broker_name . '")';
				}
				else if (is_a($broker, "IDataAccessBrokerClient")) {
					$data_access_brokers[] = $prop;
					$data_access_brokers_obj[$broker_name] = $get_broker_code_function . '("' . $broker_name . '")';
					
					if (is_a($broker, "IHibernateDataAccessBrokerClient")) {
						$hibernate_brokers[] = $data_access_brokers[ count($data_access_brokers) - 1 ];
						$hibernate_brokers_obj[$broker_name] = $data_access_brokers_obj[$broker_name];
					}
					else {
						$ibatis_brokers[] = $data_access_brokers[ count($data_access_brokers) - 1 ];
						$ibatis_brokers_obj[$broker_name] = $data_access_brokers_obj[$broker_name];
					}
				}
				else if (is_a($broker, "IDBBrokerClient")) {
					$db_brokers[] = $prop;
					$db_brokers_obj[$broker_name] = $get_broker_code_function . '("' . $broker_name . '")';
				}
			}
		
		return array(
			"business_logic_brokers" => $business_logic_brokers,
			"business_logic_brokers_obj" => $business_logic_brokers_obj,
			"data_access_brokers" => $data_access_brokers,
			"data_access_brokers_obj" => $data_access_brokers_obj,
			"ibatis_brokers" => $ibatis_brokers,
			"ibatis_brokers_obj" => $ibatis_brokers_obj,
			"hibernate_brokers" => $hibernate_brokers,
			"hibernate_brokers_obj" => $hibernate_brokers_obj,
			"db_brokers" => $db_brokers,
			"db_brokers_obj" => $db_brokers_obj,
		);
	}
	
	public static function getLocalBeanLayersFromBrokers($global_variables_file_path, $beans_folder_path, $brokers, $recursive = false, $repeated_layers = null, &$beans_files_path = array(), &$beans_brokers_name = array()) {
		$layers = array();
		
		if ($brokers) {
			foreach ($brokers as $broker_name => $broker) 
				if (!$repeated_layers || !isset($repeated_layers[$broker_name])) {
					$layer_props = self::getLocalBeanLayerFromBroker($global_variables_file_path, $beans_folder_path, $broker);
					$layer_bean_name = isset($layer_props[0]) ? $layer_props[0] : null;
					
					if ($layer_bean_name) {
						$beans_files_path[$layer_bean_name] = isset($layer_props[1]) ? $layer_props[1] : null; //already an array
						$beans_brokers_name[$layer_bean_name][] = $broker_name;
						$layers[$layer_bean_name] = isset($layer_props[2]) ? $layer_props[2] : null;
					}
				}
			
			if ($recursive) 
				foreach ($layers as $bn => $obj)
					if (is_a($obj, "BusinessLogicLayer") || is_a($obj, "DataAccessLayer")) {
						$sub_layers = self::getLocalBeanLayersFromBrokers($global_variables_file_path, $beans_folder_path, $obj->getBrokers(), $recursive, $layers, $beans_files_path, $beans_brokers_name);
						$layers = array_merge($layers, $sub_layers);
					}
		}
		
		return $layers;
	}
	
	public static function getLocalBeanLayerFromBroker($global_variables_file_path, $beans_folder_path, $broker) {
		$layer_bean_name = $layer_bean_file_path = $layer_obj = null;
		
		if (is_a($broker, "LocalBusinessLogicBrokerClient") || is_a($broker, "LocalDataAccessBrokerClient") || is_a($broker, "LocalDBBrokerClient")) {
			$broker_server_bean_files_path = $broker->getBeansFilesPath();
			$broker_server_bean_name = $broker->getBeanName();
			
			if ($broker_server_bean_name) {
				$exists = false;
				
				//SET USER GLOBAL VARIABLES
				$PHPVariablesFileHandler = new PHPVariablesFileHandler($global_variables_file_path);
				$PHPVariablesFileHandler->startUserGlobalVariables();
				
				//Get broker_server_bean_name from files defined inside of the broker
				//This action is faster bc it goes directly to the xml file where the broker_server_bean_name is
				if ($broker_server_bean_files_path)
					foreach ($broker_server_bean_files_path as $broker_server_bean_file_path) {
						$BeanFactory = new BeanFactory();
						$BeanFactory->init(array("file" => $broker_server_bean_file_path));
						$bean = $BeanFactory->getBean($broker_server_bean_name);
						
						if ($bean) {
							$layer_bean_name = isset($bean->constructor_args[1]->reference) ? $bean->constructor_args[1]->reference : null;
							
							if ($layer_bean_name) {
								$BeanFactory->initObjects();
								$layer_obj = $BeanFactory->getObject($layer_bean_name);
								$layer_bean_file_path = $broker_server_bean_files_path;
								$exists = true;
								break;
							}
						}
					}
				
				//Get broker_server_bean_name from files defined outside of the broker, this is, maybe the broker bean is already defined in the same xml file than the $brokers or in same other file that was included before.
				//This action is more slower than the action above, bc it parses all xml files to find where the broker_server_bean_name is
				if (!$exists) {
					$broker_server_bean_file_path = self::getBeanFilePath($global_variables_file_path, $beans_folder_path, $broker_server_bean_name);
					
					$BeanFactory = new BeanFactory();
					$BeanFactory->init(array("file" => $broker_server_bean_file_path));
					$bean = $BeanFactory->getBean($broker_server_bean_name);
					
					if ($bean) {
						$layer_bean_name = isset($bean->constructor_args[1]->reference) ? $bean->constructor_args[1]->reference : null;
						
						if ($layer_bean_name) {
							$BeanFactory->initObjects();
							$layer_obj = $BeanFactory->getObject($layer_bean_name);
							$layer_bean_file_path = $broker_server_bean_files_path;
						}
					}
				}
				
				//ROLLBACK TO ORIGINAL GLOBAL VARIABLES
				$PHPVariablesFileHandler->endUserGlobalVariables();
			}
		}
		
		return array($layer_bean_name, $layer_bean_file_path, $layer_obj);
	}
	
	public static function getBrokersDBDrivers($global_variables_file_path, $beans_folder_path, $brokers, $recursive = false) {
		$brokers_db_drivers = array();
		
		if ($brokers) {
			//SET USER GLOBAL VARIABLES
			$PHPVariablesFileHandler = new PHPVariablesFileHandler($global_variables_file_path);
			$PHPVariablesFileHandler->startUserGlobalVariables();
			
			//loop brokers and get dbdrivers
			foreach ($brokers as $broker_name => $broker) {
				if (is_a($broker, "IDB")) {
					if (!array_key_exists($broker_name, $brokers_db_drivers)) //only if not exists yet otherwise can overwrite the local db drivers
						$brokers_db_drivers[$broker_name] = array(); //cannot get bean details
				}
				//if recursive and not local 
				else if ($recursive && !is_a($broker, "LocalBrokerClient")) { //the local brokers will be parsed bellow 
					$db_drivers = null;
					
					if (is_a($broker, "IBusinessLogicBrokerClient") || is_a($broker, "IDataAccessBrokerClient"))
						$db_drivers = $broker->getBrokersDBDriversName();
					else if (is_a($broker, "IDBBrokerClient"))
						$db_drivers = $broker->getDBDriversName();
					
					if ($db_drivers)
						foreach ($db_drivers as $db_driver_name)
							if (!array_key_exists($db_driver_name, $brokers_db_drivers)) //only if not exists yet otherwise can overwrite the local db drivers
								$brokers_db_drivers[$db_driver_name] = array(); //cannot get bean details
				}
			}
			
			//loop related local layers and get dbdrivers
			if ($recursive) {
				$layers = self::getLocalBeanLayersFromBrokers($global_variables_file_path, $beans_folder_path, $brokers, true, null, $beans_files_path);
				
				foreach ($layers as $bean_name => $Layer) {
					$bean_files_path = isset($beans_files_path[$bean_name]) ? $beans_files_path[$bean_name] : null;
					
					if ($bean_files_path)
						foreach ($bean_files_path as $bean_file_path) {
							$WorkFlowBeansFileHandler = new WorkFlowBeansFileHandler($bean_file_path, $global_variables_file_path);
							$WorkFlowBeansFileHandler->init();
							
							if ($WorkFlowBeansFileHandler->beanExists($bean_name)) {
								$bean_brokers = $WorkFlowBeansFileHandler->getBeanBrokersReferences($bean_name);
								
								if ($bean_brokers) {
									$layer_brokers = $Layer->getBrokers();
									
									foreach ($bean_brokers as $bean_broker_name => $bean_broker_bean_name) {
										$bean_broker_bean_obj = isset($layer_brokers[$bean_broker_name]) ? $layer_brokers[$bean_broker_name] : null;
										
										if ($bean_broker_bean_obj) {
											if (is_a($bean_broker_bean_obj, "IDB")) { //check if is a db driver
												$bean_broker_bean_file_path = self::getBeanFilePath($global_variables_file_path, $beans_folder_path, $bean_broker_bean_name);
												
												$bean_broker_bean_file_name = substr($bean_broker_bean_file_path, strlen($beans_folder_path));
												$bean_broker_bean_file_name = substr($bean_broker_bean_file_name, 0, 1) == "/" ? substr($bean_broker_bean_file_name, 1) : $bean_broker_bean_file_name;
												
												//overwrite $brokers_db_drivers[$bean_broker_name] is exists with bean details.
												$brokers_db_drivers[$bean_broker_name] = array(
													$bean_broker_name, //db_driver_name
													$bean_broker_bean_file_name, //bean_file_name
													$bean_broker_bean_name, //bean_name
												);
											}
											//get the db drivers from the other layers that have rest brokers
											else if (!is_a($bean_broker_bean_obj, "LocalBrokerClient")) { //is not local
												$db_drivers = null;
												
												if (is_a($bean_broker_bean_obj, "IBusinessLogicBrokerClient") || is_a($bean_broker_bean_obj, "IDataAccessBrokerClient"))
													$db_drivers = $bean_broker_bean_obj->getBrokersDBDriversName();
												else if (is_a($bean_broker_bean_obj, "IDBBrokerClient"))
													$db_drivers = $bean_broker_bean_obj->getDBDriversName();
												
												if ($db_drivers)
													foreach ($db_drivers as $db_driver_name)
														if (!array_key_exists($db_driver_name, $brokers_db_drivers)) //only if not exists yet otherwise can overwrite the local db drivers
															$brokers_db_drivers[$db_driver_name] = array(); //cannot get bean details
											}
										}
									}
								}
								
								break;
							}
						}
				}
			}
			
			//ROLLBACK TO ORIGINAL GLOBAL VARIABLES
			$PHPVariablesFileHandler->endUserGlobalVariables();
		}
		
		return $brokers_db_drivers;
	}
	
	public static function getLayerDBDrivers($global_variables_file_path, $beans_folder_path, $Layer, $recursive = false) {
		$db_drivers = self::getBrokersDBDrivers($global_variables_file_path, $beans_folder_path, $Layer->getBrokers(), $recursive);
		
		if ($db_drivers) {
			/* No need for this code, bc the $layer is from the "Layer" class.
			if (is_a($Layer, "IDBBrokerClient")) {
				$db_layer_props = self::getLocalBeanLayerFromBroker($global_variables_file_path, $beans_folder_path, $Layer);
				$db_layer_bean_name = isset($db_layer_props[0]) ? $db_layer_props[0] : null;
				$db_layer_bean_file_name = isset($db_layer_props[1]) ? $db_layer_props[1] : null;
				$db_layer_obj = isset($db_layer_props[2]) ? $db_layer_props[2] : null;
				
				if ($db_layer_obj)
					$Layer = $db_layer_obj;
			}
			else if (is_a($Layer, "IDBBrokerServer") && is_a($Layer, "LocalBrokerServer"))
				$Layer = $Layer->getBrokerLayer();*/
			
			if (is_a($Layer, "DBLayer")) {
				$inited = false;
				
				foreach ($db_drivers as $db_driver_name => $db_driver_props)
					if (empty($db_driver_props)) {
						if (!$inited) {
							$inited = true;
							
							$layer_bean_name = self::getBeanName($global_variables_file_path, $beans_folder_path, $Layer);
							$layer_bean_file_name = $layer_bean_name ? self::getBeanFilePath($global_variables_file_path, $beans_folder_path, $layer_bean_name) : null;
							
							if ($layer_bean_name && $layer_bean_file_name) {
								$WorkFlowBeansFileHandler = new WorkFlowBeansFileHandler($layer_bean_file_name, $global_variables_file_path);
								$WorkFlowBeansFileHandler->init();
								$db_brokers = $WorkFlowBeansFileHandler->getBeanBrokersReferences($layer_bean_name);
							}
						}
						
						if (empty($db_brokers))
							break;
						else {
							$db_driver_bean_name = $db_brokers && isset($db_brokers[$db_driver_name]) ? $db_brokers[$db_driver_name] : null;
							$db_driver_bean_file_name = $db_driver_bean_name ? self::getBeanFilePath($global_variables_file_path, $beans_folder_path, $db_driver_bean_name) : null;
							
							if ($db_driver_bean_name && $db_driver_bean_file_name) {
								$db_driver_bean_file_name = substr($db_driver_bean_file_name, strlen($beans_folder_path));
								$db_driver_bean_file_name = substr($db_driver_bean_file_name, 0, 1) == "/" ? substr($db_driver_bean_file_name, 1) : $db_driver_bean_file_name;
								
								$db_drivers[$db_driver_name] = array(
									$db_driver_name, //db_driver_name
									$db_driver_bean_file_name, //bean_file_name
									$db_driver_bean_name, //bean_name
								);
							}
						}
					}
			}
		}
		
		return $db_drivers;
	}
	
	public static function getLayerDBDriverProps($global_variables_file_path, $beans_folder_path, $Layer, $db_driver) {
		if ($db_driver) {
			$db_driver = strtolower($db_driver);
			$db_drivers = self::getLayerDBDrivers($global_variables_file_path, $beans_folder_path, $Layer, true);
			
			if ($db_drivers)
				foreach ($db_drivers as $db_driver_name => $db_driver_props)
					if ($db_driver_name == $db_driver)
						return $db_driver_props;
		}
		
		return null;
	}
	
	public static function getLayerBrokerNameForChildBrokerDBDriver($global_variables_file_path, $beans_folder_path, $Layer, $db_driver, &$found_broker_obj = false, &$found_broker_props = false) {
		return self::getBrokersBrokerNameForChildBrokerDBDriver($global_variables_file_path, $beans_folder_path, $Layer->getBrokers(), $db_driver, $found_broker_obj, $found_broker_props);
	}
	
	//loop all layer brokers and check which one has access to the $db_driver and return the correspondent broker_name
	//the diference between getBrokersLocalDBBrokerNameForChildBrokerDBDriver and getBrokersBrokerNameForChildBrokerDBDriver is that this method returns the highest level broker name independent if it corresponds to the a IDBBrokerClient or not! This returns the highest level broker_name from the $brokers variable.
	public static function getBrokersBrokerNameForChildBrokerDBDriver($global_variables_file_path, $beans_folder_path, $brokers, $db_driver, &$found_broker_obj = false, &$found_broker_props = false) {
		$found_broker_name = null;
		
		if ($brokers) {
			//SET USER GLOBAL VARIABLES
			$PHPVariablesFileHandler = new PHPVariablesFileHandler($global_variables_file_path);
			$PHPVariablesFileHandler->startUserGlobalVariables();
			
			foreach ($brokers as $broker_name => $broker) {
				$is_local = is_a($broker, "LocalBrokerClient");
				
				if (is_a($broker, "IDBBrokerClient")) {
					$db_drivers = $broker->getDBDriversName();
					
					if ($db_drivers)
						foreach ($db_drivers as $db_driver_name)
							if ($db_driver_name == $db_driver) {
								$found_broker_obj = $broker;
								$found_broker_bean_files_path = $found_broker_obj->getBeansFilesPath();
								$found_broker_props = array(
									"broker" => $broker_name,
									"bean_name" => $found_broker_obj->getBeanName(),
									"bean_file_name" => isset($found_broker_bean_files_path[0]) ? basename($found_broker_bean_files_path[0]) : "",
									"bean_files_paths" => $found_broker_bean_files_path,
									"is_from_rest_broker" => !$is_local,
								);
								
								if ($is_local)
									return $broker_name;
								else if (!$found_broker_name || !empty($found_broker_props["is_from_rest_broker"])) //overwrites broker_name bc the previous $found_broker_name doesn't exists or is a lower level broker name. However if the previous $found_broker_name is a lower level but local, do not change it! Always leave the local brokers.
									$found_broker_name = $broker_name;
							}
				}
				else if ($is_local) {
					$layer_props = self::getLocalBeanLayerFromBroker($global_variables_file_path, $beans_folder_path, $broker);
					$layer_obj = isset($layer_props[2]) ? $layer_props[2] : null;
					
					if ($layer_obj) {
						$sub_broker_name = self::getBrokersBrokerNameForChildBrokerDBDriver($global_variables_file_path, $beans_folder_path, $layer_obj->getBrokers(), $db_driver, $sub_found_broker_obj, $sub_found_broker_props);
						
						if ($sub_broker_name) {
							//overwrites broker_name bc the previous $found_broker_name doesn't exists or if $sub_broker_name is local and the previous $found_broker_name is a rest broker name.
							if (!$found_broker_name || (empty($sub_found_broker_props["is_from_rest_broker"]) && !empty($found_broker_props["is_from_rest_broker"]))) {
								$found_broker_obj = $broker;
								$found_broker_bean_files_path = $found_broker_obj->getBeansFilesPath();
								$found_broker_props = array(
									"broker" => $broker_name,
									"bean_name" => $found_broker_obj->getBeanName(),
									"bean_file_name" => isset($found_broker_bean_files_path[0]) ? basename($found_broker_bean_files_path[0]) : "",
									"bean_files_paths" => $found_broker_bean_files_path,
									"is_from_rest_broker" => $found_broker_props["is_from_rest_broker"],
								);
								$found_broker_name = $broker_name;
							}
						}
					}
				}
				else if (is_a($broker, "IBusinessLogicBrokerClient") || is_a($broker, "IDataAccessBrokerClient")) { //if rest broker
					$db_drivers = $broker->getBrokersDBDriversName();
					
					if ($db_drivers)
						foreach ($db_drivers as $db_driver_name)
							if ($db_driver_name == $db_driver && !$found_broker_name) { //only updates broker_name if the previous $found_broker_name doesn't exists.
								$found_broker_obj = $broker;
								$found_broker_bean_files_path = $found_broker_obj->getBeansFilesPath();
								$found_broker_props = array(
									"broker" => $broker_name,
									"bean_name" => $found_broker_obj->getBeanName(),
									"bean_file_name" => isset($found_broker_bean_files_path[0]) ? basename($found_broker_bean_files_path[0]) : "",
									"bean_files_paths" => $found_broker_bean_files_path,
									"is_from_rest_broker" => true,
								);
								$found_broker_name = $broker_name;
							}
					
				}
			}
			
			//ROLLBACK TO ORIGINAL GLOBAL VARIABLES
			$PHPVariablesFileHandler->endUserGlobalVariables();
		}
		
		return $found_broker_name;
	}
	
	public static function getLayerLocalDBBrokerNameForChildBrokerDBDriver($global_variables_file_path, $beans_folder_path, $Layer, $db_driver, &$found_broker_obj = false, &$found_broker_props = false) {
		return self::getBrokersLocalDBBrokerNameForChildBrokerDBDriver($global_variables_file_path, $beans_folder_path, $Layer->getBrokers(), $db_driver, $found_broker_obj, $found_broker_props);
	}
	
	//loop all layer brokers and check which one has access to the $db_driver and return the correspondent DB broker_name
	//the diference between getBrokersLocalDBBrokerNameForChildBrokerDBDriver and getBrokersBrokerNameForChildBrokerDBDriver is that this method returns the lower level broker name correspondent to the IDBBrokerClient
	public static function getBrokersLocalDBBrokerNameForChildBrokerDBDriver($global_variables_file_path, $beans_folder_path, $brokers, $db_driver, &$found_broker_obj = false, &$found_broker_props = false) {
		$found_broker_name = null;
		
		if ($brokers) {
			//SET USER GLOBAL VARIABLES
			$PHPVariablesFileHandler = new PHPVariablesFileHandler($global_variables_file_path);
			$PHPVariablesFileHandler->startUserGlobalVariables();
			
			foreach ($brokers as $broker_name => $broker) {
				$is_local = is_a($broker, "LocalBrokerClient");
				
				if (is_a($broker, "IDBBrokerClient")) {
					$db_drivers = $broker->getDBDriversName();
					
					if ($db_drivers)
						foreach ($db_drivers as $db_driver_name)
							if ($db_driver_name == $db_driver) {
								$found_broker_obj = $broker;
								$found_broker_bean_files_path = $found_broker_obj->getBeansFilesPath();
								$found_broker_props = array(
									"broker" => $broker_name,
									"bean_name" => $found_broker_obj->getBeanName(),
									"bean_file_name" => isset($found_broker_bean_files_path[0]) ? basename($found_broker_bean_files_path[0]) : "",
									"bean_files_paths" => $found_broker_bean_files_path,
									"is_from_rest_broker" => !$is_local,
								);
								
								if ($is_local)
									return $broker_name;
								else
									$found_broker_name = $broker_name;
							}
				}
				else if ($is_local) {
					$layer_props = self::getLocalBeanLayerFromBroker($global_variables_file_path, $beans_folder_path, $broker);
					$layer_obj = isset($layer_props[2]) ? $layer_props[2] : null;
					
					if ($layer_obj) {
						$sub_broker_name = self::getBrokersLocalDBBrokerNameForChildBrokerDBDriver($global_variables_file_path, $beans_folder_path, $layer_obj->getBrokers(), $db_driver, $sub_found_broker_obj, $sub_found_broker_props);
						
						if ($sub_broker_name) {
							if (is_a($sub_found_broker_obj, "LocalBrokerClient")) {
								$found_broker_obj = $sub_found_broker_obj;
								$found_broker_bean_files_path = $found_broker_obj->getBeansFilesPath();
								$found_broker_props = array(
									"broker" => $sub_broker_name,
									"bean_name" => $found_broker_obj->getBeanName(),
									"bean_file_name" => isset($found_broker_bean_files_path[0]) ? basename($found_broker_bean_files_path[0]) : "",
									"bean_files_paths" => $found_broker_bean_files_path,
									"is_from_rest_broker" => $sub_found_broker_props["is_from_rest_broker"],
								);
								return $sub_broker_name;
							}
							else if (!$found_broker_name) {
								$found_broker_obj = $sub_found_broker_obj;
								$found_broker_bean_files_path = $found_broker_obj->getBeansFilesPath();
								$found_broker_props = array(
									"broker" => $sub_broker_name,
									"bean_name" => $found_broker_obj->getBeanName(),
									"bean_file_name" => isset($found_broker_bean_files_path[0]) ? basename($found_broker_bean_files_path[0]) : "",
									"bean_files_paths" => $found_broker_bean_files_path,
									"is_from_rest_broker" => $sub_found_broker_props["is_from_rest_broker"],
								);
								$found_broker_name = $sub_broker_name;
							}
						}
					}
				}
			}
			
			//ROLLBACK TO ORIGINAL GLOBAL VARIABLES
			$PHPVariablesFileHandler->endUserGlobalVariables();
		}
		
		return $found_broker_name;
	}
	
	public function getDBSettings($bean_name, &$db_settings_variables = false, $new_settings_data = false) {
		$db_settings = array();
		$ubn = strtolower($bean_name);
		$db_settings_variables = !is_array($db_settings_variables) ? array() : $db_settings_variables;
		
		//SET USER GLOBAL VARIABLES
		$this->PHPVariablesFileHandler->startUserGlobalVariables();
		
		//PREPARE DB SETTINGS
		$nodes = $this->nodes;
		
		if (!empty($nodes["beans"][0]["childs"]["bean"])) {
			$t = count($nodes["beans"][0]["childs"]["bean"]);
			for ($i = 0; $i < $t; $i++) {
				$bean = $nodes["beans"][0]["childs"]["bean"][$i];
				$bean_ubn = isset($bean["@"]["name"]) ? strtolower($bean["@"]["name"]) : "";
				
				if ($bean_ubn == $ubn) {
					//set driver type
					$db_settings["type"] = DB::getDriverTypeByPath(isset($bean["@"]["path"]) ? $bean["@"]["path"] : null);
					
					//prepare db driver type
					$db_type = isset($new_settings_data["type"]) ? $new_settings_data["type"] : null;
					
					if ($db_type && $db_type != $db_settings["type"]) { 
						$driver_path = DB::getDriverPathByType($db_type);
						
						if ($driver_path) {
							$nodes["beans"][0]["childs"]["bean"][$i]["@"]["path"] = $driver_path;
							$db_settings["type"] = $db_type;
						}
					}
					
					//prepare db options and credentials
					if (!empty($bean["childs"]["function"])) {
						$t2 = count($bean["childs"]["function"]);
						for ($j = 0; $j < $t2; $j++) {
							$func = $bean["childs"]["function"][$j];
						
							if (isset($func["@"]["name"]) && strtolower($func["@"]["name"]) == "setoptions") {
								$parameter = isset($func["childs"]["parameter"][0]) ? $func["childs"]["parameter"][0] : null;
								$reference = isset($parameter["@"]["reference"]) ? $parameter["@"]["reference"] : null;
								
								if ($reference) {
									for ($w = 0; $w < $t; $w++) {
										$bean_var = isset($nodes["beans"][0]["childs"]["var"][$w]) ? $nodes["beans"][0]["childs"]["var"][$w] : null;
										
										if ($bean_var["@"]["name"] == $reference) {
											if(isset($bean_var["childs"]["list"])) {
												$items = isset($bean_var["childs"]["list"][0]["childs"]["item"]) ? $bean_var["childs"]["list"][0]["childs"]["item"] : null;
												$existent_properties = array();
												
												foreach($items as $idx => $item_value) {
													$key = XMLFileParser::getAttribute($item_value, "name");
													$value = XMLFileParser::getValue($item_value);
													//echo "$key: $value\n<br/>";
													
													$is_php_start_statement = substr($value, 0, strlen("&lt;?php echo ")) == "&lt;?php echo " || substr($value, 0, strlen("&lt;? echo ")) == "&lt;?php echo " || substr($value, 0, strlen("&lt;?=")) == "&lt;?=";
													$is_php_end_statement = substr($value, - strlen("?&gt;")) == "?&gt;" || substr($value, -2) == "?>";
													
													if ($is_php_start_statement && $is_php_end_statement)
														$value = preg_replace("/\?&gt;$/", "?>", preg_replace("/^&lt;\?/", "<?", $value));
													
													$contains_new_value = isset($new_settings_data[$key]);
													$new_value = $contains_new_value ? $new_settings_data[$key] : null;
													$existent_properties[] = $key;
													
													//if is variable change it to global variable code
													if (substr(trim($new_value), 0, 1) == '$') {
														$aux = substr(trim($new_value), 1);
														$new_value = '<?php echo isset($GLOBALS[\'' . $aux . '\']) ? $GLOBALS[\'' . $aux . '\'] : \'\'; ?>';
													}
													else if (substr(trim($new_value), 0, 2) == '@$') {
														$aux = substr(trim($new_value), 2);
														$new_value = '<?php echo isset($GLOBALS[\'' . $aux . '\']) ? $GLOBALS[\'' . $aux . '\'] : \'\'; ?>';
													}
													
													//if is a new change from the user
													if ($contains_new_value) {
														$global_var_name = $this->getPHPVariable($new_value);
														$db_settings[$key] = $global_var_name ? (isset($GLOBALS[$global_var_name]) ? $GLOBALS[$global_var_name] : null) : $new_value;//if global variable, gets the global value, otherwise simply returns the user inputs (which should be a strig/number/etc... - but not a variable)
													}
													else
														$db_settings[$key] = $this->prepareValue($value);//if there is no user input, returns the existent/default value
													
													$db_settings_variables[$key] = $this->getPHPVariable($contains_new_value ? $new_value : $value);
								
													$bean_var["childs"]["list"][0]["childs"]["item"][$idx]["value"] = $contains_new_value/* && empty($db_settings_variables[$key])*/ ? $new_value : $value;
													//echo "<pre>";print_r($bean_var["childs"]["list"][0]["childs"]["item"]);die();
												}
												
												if ($new_settings_data)
													foreach($new_settings_data as $key => $new_value)
														if (!in_array($key, $existent_properties) && !in_array($key, array("type"))) {
															//if is variable change it to global variable code
															if (substr(trim($new_value), 0, 1) == '$') {
																$aux = substr(trim($new_value), 1);
																$new_value = '<?php echo isset($GLOBALS[\'' . $aux . '\']) ? $GLOBALS[\'' . $aux . '\'] : \'\'; ?>';
															}
															else if (substr(trim($new_value), 0, 2) == '@$') {
																$aux = substr(trim($new_value), 2);
																$new_value = '<?php echo isset($GLOBALS[\'' . $aux . '\']) ? $GLOBALS[\'' . $aux . '\'] : \'\'; ?>';
															}
															
															$global_var_name = $this->getPHPVariable($new_value);
															$db_settings[$key] = $global_var_name ? (isset($GLOBALS[$global_var_name]) ? $GLOBALS[$global_var_name] : null) : $new_value;//if global variable, gets the global value, otherwise simply returns the user inputs (which should be a strig/number/etc... - but not a variable)
															
															$bean_var["childs"]["list"][0]["childs"]["item"][] = array(
																"name" => "item",
																"@" => array("name" => $key),
																"value" => $new_value,
															);
														}
												//echo "<pre>";print_r($bean_var["childs"]["list"][0]["childs"]["item"]);die();
											}
										}
										$nodes["beans"][0]["childs"]["var"][$w] = $bean_var;
									}
								}
							}
						}
					}
					break;
				}
			}
		}
		
		$this->nodes = $nodes;
	
		//ROLLBACK TO ORIGINAL GLOBAL VARIABLES
		$this->PHPVariablesFileHandler->endUserGlobalVariables();
		
		return $db_settings;
	}

	public function getBeanBrokersReferences($bean_name) {
		$ubn = strtolower($bean_name);
		$brokers = array();
		
		if (!empty($this->nodes["beans"][0]["childs"]["bean"])) {
			$t = count($this->nodes["beans"][0]["childs"]["bean"]);
			for ($i = 0; $i < $t; $i++) {
				$bean = $this->nodes["beans"][0]["childs"]["bean"][$i];
				$bean_ubn = isset($bean["@"]["name"]) ? strtolower($bean["@"]["name"]) : "";
				
				if ($bean_ubn == $ubn) {
					if (!empty($bean["childs"]["function"])) {
						$t2 = count($bean["childs"]["function"]);
						for ($j = 0; $j < $t2; $j++) {
							$func = $bean["childs"]["function"][$j];
						
							if (isset($func["@"]["name"]) && strtolower($func["@"]["name"]) == "addbroker") {
								$parameters = isset($func["childs"]["parameter"]) ? $func["childs"]["parameter"] : null;
								$pt = count($parameters);
								$broker_name = $broker_reference = null;
								
								for ($w = 0; $w < $pt; $w++) {
									$parameter = $parameters[$w];
									$index = isset($parameter["@"]["index"]) ? $parameter["@"]["index"] : null;
									
									if (($index && $index == 1) || (!$index && $w == 0))
										$broker_reference = isset($parameter["@"]["reference"]) ? $parameter["@"]["reference"] : null;
									else if (($index && $index == 2) || (!$index && $w == 1))
										$broker_name = isset($parameter["@"]["value"]) ? $parameter["@"]["value"] : null;
								}
								
								if ($broker_name || $broker_reference)
									$brokers[ $broker_name ] = $broker_reference;
							}
						}
					}
					break;
				}
			}
		}
		
		return $brokers;
	}
	
	public function beanExists($bean_name) {
		$ubn = strtolower($bean_name);
		
		if (!empty($this->nodes["beans"][0]["childs"]["bean"])) {
			$t = count($this->nodes["beans"][0]["childs"]["bean"]);
			for ($i = 0; $i < $t; $i++) {
				$bean = $this->nodes["beans"][0]["childs"]["bean"][$i];
				
				if (isset($bean["@"]["name"]) && strtolower($bean["@"]["name"]) == $ubn)
					return true;
			}
		}
		
		return false;
	}

	private function prepareValue($value) {
		if (strpos($value, "<?php") !== false) {
			$value = trim(str_replace(array("<?php echo ", "?>"), "", $value));
		
			eval("\$value = $value;");
		}
	
		return $value;
	}

	private function getPHPVariable($value) {
		if (strpos($value, "<?") !== false) {
			$value = trim(str_replace(array("<?php echo ", "<? echo ", "?>", ";"), "", $value));
			
			if (strpos($value, '$GLOBALS[') !== false) {
				preg_match_all('/\$GLOBALS\[\'([^\']+)\'\]/u', $value, $m, PREG_SET_ORDER);
				
				if ($m && isset($m[0][1]))
					$value = $m[0][1];
				else {
					preg_match_all('/\$GLOBALS\["([^"]+)"\]/u', $value, $m, PREG_SET_ORDER); //'\w' means all words with '_' and '/u' means with accents and ç too. '/u' converts unicode to accents chars. 
					
					if ($m && isset($m[0][1]))
						$value = $m[0][1];
				}
				
				/* DEPRECATED bc the $value can be: '<? echo isset($GLOBALS['xxx']) ? $GLOBALS['xxx'] : null; ?>'
				$value = trim(str_replace(array("GLOBALS['", "GLOBALS[\""), "", $value));
			
				if (substr($value, strlen($value) - 2) == "']" || substr($value, strlen($value) - 2) == "\"]") {
					$value = substr($value, 0, strlen($value) - 2);
				}*/
				
				return $value;
			}
			else if (preg_match_all('/\$([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)/u', $value, $m, PREG_SET_ORDER) && $m && isset($m[0][1])) { //'\w' means all words with '_' and '/u' means with accents and ç too. '/u' converts unicode to accents chars. 
				return isset($m[0][1]) ? $m[0][1] : null;
			}
		}
		return false;
	}
}
?>
