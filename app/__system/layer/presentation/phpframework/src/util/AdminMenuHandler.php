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

include_once get_lib("org.phpframework.bean.BeanFactory");
include_once get_lib("org.phpframework.phpscript.PHPCodePrintingHandler");
include_once get_lib("org.phpframework.xmlfile.XMLFileParser");
include_once get_lib("org.phpframework.sqlmap.ibatis.IBatisClient");
include_once get_lib("org.phpframework.sqlmap.hibernate.HibernateClient");
include_once get_lib("org.phpframework.layer.dataaccess.IbatisDataAccessLayer");
include_once get_lib("org.phpframework.layer.dataaccess.HibernateDataAccessLayer");
include_once get_lib("org.phpframework.layer.businesslogic.BusinessLogicLayer");
include_once get_lib("org.phpframework.layer.presentation.PresentationLayer");
include_once get_lib("org.phpframework.util.FilePermissionHandler");
include_once get_lib("org.phpframework.cms.VendorFrameworkHandler");
include_once $EVC->getUtilPath("WorkFlowBeansFileHandler");
include_once $EVC->getUtilPath("LayoutTypeProjectUIHandler");

class AdminMenuHandler {
	
	public static function addReferencedFolderToFilesList(&$files, $bean_file_name, $bean_name, $path, $item_type) {
		$file_key = "referenced";
		self::prepareListKeyIfAlreadyExists($file_key, $files);
		
		$files[$file_key] = array(
			"properties" => array(
				"path" => $path,
				"bean_file_name" => $bean_file_name,
				"bean_name" => $bean_name,
				"item_id" => self::getItemId("$bean_file_name/$bean_name/$path/$file_key"),
				"item_type" => "referenced_folder",
				"parse_get_sub_files_url_handler" => LayoutTypeProjectUIHandler::getJavascriptHandlerToParseGetSubFilesUrlWithOnlyReferencedFiles(),
			)
		);
		
		return $files;
	}
	
	public static function getLayersFiles($global_variables_file_path) {
		$layers_references = self::getLayers($global_variables_file_path);
		
		$layers = array();
		foreach ($layers_references as $type => $layer) 
			foreach ($layer as $name => $file) 
				$layers[$type][$name] = self::getBeanObjs($file, $name, $global_variables_file_path, false, 0);
		
		$layers["vendors"]["vendor"] = self::getVendorObjs(false, 1);
		
		return $layers;
	}
	
	public static function getLayers($global_variables_file_path) {
		//SET USER GLOBAL VARIABLES
		$PHPVariablesFileHandler = new PHPVariablesFileHandler($global_variables_file_path);
		$PHPVariablesFileHandler->startUserGlobalVariables();
		
		$layers_references = array();
		$path = BEAN_PATH;
				
		//PREPARE BEANS
		if (is_dir($path) && ($dir = opendir($path)) ) {
			while( ($file = readdir($dir)) !== false) {
				if (substr($file, strlen($file) - 4) == ".xml" && $file != "app.xml" && !strstr($file, "_bll_common_services.xml", true)) {
					$BeanFactory = new BeanFactory();
					$BeanFactory->init(array("file" => $path . $file));
					$beans = $BeanFactory->getBeans();
					$BeanFactory->initObjects();
					
					foreach ($beans as $bean_name => $bean) {
						$obj = $BeanFactory->getObject($bean_name);
						
						if (is_a($obj, "ILayer")) {
							if (is_a($obj, "DBLayer"))
								$layers_references["db_layers"][ $bean->name ] = $file;
							else if (is_a($obj, "DataAccessLayer"))
								$layers_references["data_access_layers"][ $bean->name ] = $file;
							else if (is_a($obj, "BusinessLogicLayer")) 
								$layers_references["business_logic_layers"][ $bean->name ] = $file;
							else if (is_a($obj, "PresentationLayer"))
								$layers_references["presentation_layers"][ $bean->name ] = $file;
						}
					}
				}
			}
			
			closedir($dir);
		}
		
		//ROLLBACK TO ORIGINAL GLOBAL VARIABLES
		$PHPVariablesFileHandler->endUserGlobalVariables();
		
		return $layers_references;
	}
	/*public static function getLayers($global_variables_file_path) {
		//SET USER GLOBAL VARIABLES
		$PHPVariablesFileHandler = new PHPVariablesFileHandler($global_variables_file_path);
		$PHPVariablesFileHandler->startUserGlobalVariables();
		
		$layers_references = array();
		$path = BEAN_PATH;
				
		//PREPARE BEANS
		if (is_dir($path) && ($dir = opendir($path)) ) {
			while( ($file = readdir($dir)) !== false) {
				if (substr($file, strlen($file) - 4) == ".xml" && $file != "app.xml" && !strstr($file, "_bll_common_services.xml", true)) {
					$BeanFactory = new BeanFactory();
					$BeanFactory->init(array("file" => $path . $file));
					$beans = $BeanFactory->getBeans();
					$vars = $BeanFactory->getObjects();
					
					$BeanFactory->initObjects();
					foreach ($beans as $bean_name => $bean) {
						$obj = $BeanFactory->getObject($bean_name);
						
						if (is_a($obj, "ILayer")) {
							if (is_a($obj, "DBLayer")) {
								$name = substr($bean->name, 0, strlen($bean->name) - strlen("DBLayer"));
								$layers_references["db_layers"][$name] = $file;
							}
							else if (is_a($obj, "DataAccessLayer")) {
								$name = substr($bean->name, 0, strlen($bean->name) - strlen("IDALayer"));
								$layers_references["data_access_layers"][$name] = $file;
							}
							else if (is_a($obj, "BusinessLogicLayer")) {
								$name = substr($bean->name, 0, strlen($bean->name) - strlen("BLLayer"));
								$layers_references["business_logic_layers"][$name] = $file;
							}
							else if (is_a($obj, "PresentationLayer")) {
								$name = substr($bean->name, 0, strlen($bean->name) - strlen("PLayer"));
								$layers_references["presentation_layers"][$name] = $file;
							}
						}
					}
				}
			}
			closedir($dir);
		}
		
		//ROLLBACK TO ORIGINAL GLOBAL VARIABLES
		$PHPVariablesFileHandler->endUserGlobalVariables();
		
		return $layers_references;
	}*/
	
	public static function getDaoObjs($path = false, $recursive_level = -1) {
		$objs = array();
		
		$daos_path = DAO_PATH;
		
		$objs = self::getGenericFiles($daos_path, $path, "dao", $recursive_level, true);
		$objs["properties"] = array(
			"path" => "",
			"item_id" => self::getItemId($daos_path),
			"item_type" => "dao",
		);
		
		return $objs;
	}
	
	public static function getTestUnitObjs($path = false, $recursive_level = -1) {
		$objs = array();
		
		$test_units_path = TEST_UNIT_PATH;
		
		$objs = self::getGenericFiles($test_units_path, $path, "test_unit", $recursive_level, true);
		$objs["properties"] = array(
			"path" => "",
			"item_id" => self::getItemId($test_units_path),
			"item_type" => "test_unit",
		);
		
		return $objs;
	}
	
	public static function getLibObjs($path = false, $recursive_level = -1) {
		$objs = array();
		
		$libs_path = LIB_PATH;
		
		$objs = self::getGenericFiles($libs_path, $path, "lib", $recursive_level, true);
		$objs["properties"] = array(
			"path" => "",
			"item_id" => self::getItemId($libs_path),
			"item_type" => "lib",
		);
		
		return $objs;
	}
	
	public static function getVendorObjs($path = false, $recursive_level = -1) {
		$objs = array();
		
		if (substr($path, 0, 3) == "dao" && (strlen($path) == 3 || substr($path, 3, 1) == "/"))
			$objs = self::getDaoObjs($path, $recursive_level);
		else if (substr($path, 0, 8) == "testunit" && (strlen($path) == 8 || substr($path, 8, 1) == "/"))
			$objs = self::getTestUnitObjs($path, $recursive_level);
		else {
			$vendors_path = VENDOR_PATH;
			
			$objs = self::getGenericFiles($vendors_path, $path, "vendor", $recursive_level, true);
			$objs = self::prepareVendorBeanObjects($objs);
			
			$objs["properties"] = array(
				"path" => "",
				"item_id" => self::getItemId($vendors_path),
				"item_type" => "vendor",
			);
		}
		
		return $objs;
	}
	
	public static function getOtherObjs($path = false, $recursive_level = -1) {
		$objs = array();
		
		$others_path = OTHER_PATH;
		
		$objs = self::getGenericFiles($others_path, $path, "other", $recursive_level, true);
		$objs = self::prepareOtherBeanObjects($objs);
		
		$objs["properties"] = array(
			"path" => "",
			"item_id" => self::getItemId($others_path),
			"item_type" => "other",
		);
		
		return $objs;
	}
	
	private static function prepareVendorBeanObjects($bean_objs) {
		if ($bean_objs)
			foreach ($bean_objs as $file_name => $bean_obj) {
				$props = isset($bean_obj["properties"]) ? $bean_obj["properties"] : null;
				
				if (isset($props["item_type"]) && $props["item_type"] == "folder") {
					$path = isset($props["path"]) ? $props["path"] : null;
					$path = substr($path, -1) == "/" ? substr($path, 0, -1) : $path;
					
					if ($path == "dao") {
						$bean_objs[$file_name]["properties"] = array(
							"path" => "",
							"item_type" => "dao",
							"item_id" => self::getItemId(DAO_PATH)
						);
					}
					else if ($path == "testunit") {
						$bean_objs[$file_name]["properties"] = array(
							"path" => "",
							"item_id" => self::getItemId(TEST_UNIT_PATH),
							"item_type" => "test_unit",
						);
					}
					else if ($path == "codeworkfloweditor") 
						$bean_objs[$file_name]["properties"]["item_type"] = "code_workflow_editor";
					else if ($path == "codeworkfloweditor/task") 
						$bean_objs[$file_name]["properties"]["item_type"] = "code_workflow_editor_task";
					else if ($path == "layoutuieditor") 
						$bean_objs[$file_name]["properties"]["item_type"] = "layout_ui_editor";
					else if ($path == "layoutuieditor/widget") 
						$bean_objs[$file_name]["properties"]["item_type"] = "layout_ui_editor_widget";
					else if ($path == "phpmyadmin") 
						unset($bean_objs["phpmyadmin"]);
				}
			}
		
		return $bean_objs;
	}
	
	private static function prepareOtherBeanObjects($bean_objs) {
		if ($bean_objs)
			foreach ($bean_objs as $file_name => $bean_obj) {
				$props = isset($bean_obj["properties"]) ? $bean_obj["properties"] : null;
				
				if (isset($props["item_type"]) && $props["item_type"] == "folder") {
					$path = isset($props["path"]) ? $props["path"] : null;
					$path = substr($path, -1) == "/" ? substr($path, 0, -1) : $path;
					
					if ($path == "workflow" || $path == "authdb")
						unset($bean_objs[$file_name]);
				}
			}
		
		return $bean_objs;
	}
	
	public static function getPresentationFolderFiles($bean_file_name, $bean_name, $global_variables_file_path = false, $path = false, $recursive_level = -1, $folder_type = "", $options = null) {
		$bean_objs = array();
		
		//SET USER GLOBAL VARIABLES
		$PHPVariablesFileHandler = new PHPVariablesFileHandler($global_variables_file_path);
		$PHPVariablesFileHandler->startUserGlobalVariables();
		
		$bean_file_path = BEAN_PATH . $bean_file_name;
		$BeanFactory = new BeanFactory();
		$BeanFactory->init(array("file" => $bean_file_path));
		
		$bean = $BeanFactory->getBean($bean_name);
		
		$BeanFactory->initObject($bean->name);
		$obj = $BeanFactory->getObject($bean->name);
		
		if (is_a($obj, "PresentationLayer")) {
			$pres_options = self::getPresentationBeanRelatedBeanObjects($BeanFactory, $bean, $obj);
			$options = is_array($options) ? array_merge($pres_options, $options) : $pres_options;
			$bean_objs = self::getSubFiles($obj, $path, $recursive_level, $folder_type == "webroot" ? "" : $folder_type, $options);
			
			$PresentationCacheLayer = $obj->getCacheLayer();
			$common_project_name = $obj->getCommonProjectName();
			$is_common_project = substr($path, 0, strlen("$common_project_name/")) == "$common_project_name/";
			
			if ($folder_type == "cache" && $PresentationCacheLayer && !empty($PresentationCacheLayer->settings["presentation_caches_path"]) && substr($path, strlen($PresentationCacheLayer->settings["presentation_caches_path"]) * -1) == $PresentationCacheLayer->settings["presentation_caches_path"]) {
				$project_prefix = substr($path, 0, strlen($PresentationCacheLayer->settings["presentation_caches_path"]) * -1);
				//$is_common_project = substr($project_prefix, 0, -1) == $obj->getCommonProjectName(); //substr($project_prefix, 0, -1) remove last slash
				
				if (!$is_common_project) {
					$bean_objs = self::preparePresentationCachePageBeanObjects($bean_objs, $options, $project_prefix, $PresentationCacheLayer);
					$bean_objs = self::preparePresentationCacheModuleAndBlockBeanObjects($bean_objs, $options, $project_prefix);
					$bean_objs = self::preparePresentationDispatcherBeanObjects($bean_objs, $options, $project_prefix);
				}
			}
			else if ($folder_type == "config" && substr($path, strlen("/src/config/") * -1) == "/src/config/") //if is root of config folder
				$bean_objs = self::preparePresentationConfigBeanObjects($bean_objs, $options);
			else if ($folder_type == "util")
				$bean_objs = self::preparePresentationUtilBeanObjects($bean_objs, $options, $obj);
			else if ($folder_type == "template" && substr($path, strlen("/src/template/") * -1) == "/src/template/") //if is root of template folder
				$bean_objs = self::preparePresentationTemplateBeanObjectsForMainTemplateFolders($bean_objs, $options);
			else if ($folder_type == "template" && substr(dirname($path) . "/", strlen("/src/template/") * -1) == "/src/template/") //if $path is a child of the template root folder
				$bean_objs = self::preparePresentationTemplateBeanObjects($bean_objs, $options);
			else if (($folder_type == "" || $folder_type == "webroot") && substr($path, strlen("/webroot/") * -1) == "/webroot/") { //if is root of webroot folder
				$bean_objs = self::preparePresentationWebrootBeanObjects($bean_objs, $options);
				$bean_objs = self::preparePresentationCommonWebrootBeanObjects($bean_objs, $options, $path, $common_project_name);
			}
			else if (($folder_type == "" || $folder_type == "webroot") && $is_common_project && substr($path, 0, strlen("$common_project_name/webroot/")) == "$common_project_name/webroot/") //if is inside of common/webroot folder
				$bean_objs = self::preparePresentationCommonWebrootBeanObjects($bean_objs, $options, $path, $common_project_name);
			else if ($folder_type == "module")  //if is NOT root of module folder, this is, if is an inner module folder
				$bean_objs = self::preparePresentationModuleBeanObjects($bean_objs, $options, $obj);
			
			$bean_objs["properties"] = array(
				"path" => $path,
				"bean_file_name" => $bean_file_name,
				"bean_name" => $bean_name,
				"item_id" => self::getItemId("$bean_file_name/$bean_name/$path"),
				"item_type" => self::getLayerTypeFromBeanObject($obj),
			);
		}
		
		//ROLLBACK TO ORIGINAL GLOBAL VARIABLES
		$PHPVariablesFileHandler->endUserGlobalVariables();
		
		return $bean_objs;
	}
	
	public static function getBeanObjs($bean_file_name, $bean_name, $global_variables_file_path = false, $path = false, $recursive_level = -1, $options = null) {
		$bean_objs = array();
		$item_type = "";
		
		if (!empty($path) && substr($path, strlen($path) - 1) != "/")
			$path .= "/";
		
		//SET USER GLOBAL VARIABLES
		$PHPVariablesFileHandler = new PHPVariablesFileHandler($global_variables_file_path);
		$PHPVariablesFileHandler->startUserGlobalVariables();
		
		$bean_file_path = BEAN_PATH . $bean_file_name;
		$BeanFactory = new BeanFactory();
		$BeanFactory->init(array("file" => $bean_file_path));
		$bean = $BeanFactory->getBean($bean_name);
				
		if ($bean) {
			$BeanFactory->initObject($bean->name);
			$obj = $BeanFactory->getObject($bean->name);
		
			if (is_a($obj, "DBLayer"))
				$bean_objs = self::getBeanDBObjs($bean, $BeanFactory->getBeans(), $BeanFactory->getObjects()); 
			else if (is_a($obj, "DataAccessLayer"))
				$bean_objs = self::getBeanLayerObjs($obj, $path, $recursive_level); 
			else if (is_a($obj, "BusinessLogicLayer"))
				$bean_objs = self::getBeanLayerObjs($obj, $path, $recursive_level, $options); 
			else if (is_a($obj, "PresentationLayer")) {
				$pres_options = self::getPresentationBeanRelatedBeanObjects($BeanFactory, $bean, $obj);
				$options = is_array($options) ? array_merge($pres_options, $options) : $pres_options;
				$bean_objs = self::getBeanLayerObjs($obj, $path, $recursive_level, $options); 
			}
			
			if ($bean_objs) {
				$bean_objs["properties"]["bean_file_name"] = $bean_file_name;
				$bean_objs["properties"]["bean_name"] = $bean_name;
				$bean_objs["properties"]["item_id"] = self::getItemId("$bean_file_name/$bean_name");
				$bean_objs["properties"]["item_type"] = self::getLayerTypeFromBeanObject($obj);
				$bean_objs["properties"]["item_label"] = WorkFlowBeansFileHandler::getLayerNameFromBeanObject($bean->name, $obj);
			}
		}
		
		//ROLLBACK TO ORIGINAL GLOBAL VARIABLES
		$PHPVariablesFileHandler->endUserGlobalVariables();
		
		return $bean_objs;
	}
	/*public static function getBeanObjs($bean_file_name, $bean_name, $global_variables_file_path = false, $path = false, $recursive_level = -1) {
		$bean_objs = array();
		
		//SET USER GLOBAL VARIABLES
		$PHPVariablesFileHandler = new PHPVariablesFileHandler($global_variables_file_path);
		$PHPVariablesFileHandler->startUserGlobalVariables();
		
		$bean_file_path = BEAN_PATH . $bean_file_name;
		$BeanFactory = new BeanFactory();
		$BeanFactory->init(array("file" => $bean_file_path));
		$vars = $BeanFactory->getObjects();
		
		$layer_type = substr($bean_file_name, strlen($bean_file_name) - 7, 3);
		
		if (!empty($path) && substr($path, strlen($path) - 1) != "/")
			$path .= "/";
		
		$item_type = "";
		
		switch($layer_type) {
			case "dbl": 
				$bean = $BeanFactory->getBean($bean_name . "DBLayer");
				
				if (!empty($bean)) {
					$BeanFactory->initObject($bean->name);
					$obj = $BeanFactory->getObject($bean->name);
				
					if (is_a($obj, "DBLayer")) {
						$bean_objs = self::getBeanDBObjs($bean, $BeanFactory->getBeans(), $BeanFactory->getObjects()); 
						$item_type = "db";
					}
				}
				break;
			case "dal": 
				$bean = $BeanFactory->getBean($bean_name . "IDALayer");
				if(empty($bean))
					$bean = $BeanFactory->getBean($bean_name . "HDALayer");
				
				if (!empty($bean)) {
					$BeanFactory->initObject($bean->name);
					$obj = $BeanFactory->getObject($bean->name);
				
					if (is_a($obj, "DataAccessLayer")) {
						$bean_objs = self::getBeanLayerObjs($obj, $path, $recursive_level); 
						$item_type = $obj->getType();
					}
				}
				break;
			case "bll": 
				$bean = $BeanFactory->getBean($bean_name . "BLLayer");
				
				if (!empty($bean)) {
					$BeanFactory->initObject($bean->name);
					$obj = $BeanFactory->getObject($bean->name);
			
					if (is_a($obj, "BusinessLogicLayer")) {
						$bean_objs = self::getBeanLayerObjs($obj, $path, $recursive_level); 
						$item_type = "businesslogic";
					}
				}
				break;
			case "_pl": 
				$bean = $BeanFactory->getBean($bean_name . "PLayer");
				
				if (!empty($bean)) {
					$BeanFactory->initObject($bean->name);
					$obj = $BeanFactory->getObject($bean->name);
			
					if (is_a($obj, "PresentationLayer")) {
						$options = array();
						
						$bean = $BeanFactory->getBean($bean_name . "PRouter");
						if (!empty($bean)) {
							$BeanFactory->initObject($bean->name);
							$options["PresentationPRouter"] = $BeanFactory->getObject($bean->name);
						}
						
						$bean = $BeanFactory->getBean($bean_name . "MultipleCMSCacheLayer");
						if (!empty($bean)) {
							$BeanFactory->initObject($bean->name);
							$options["PresentationMultipleCMSCacheLayer"] = $BeanFactory->getObject($bean->name);
						}
						
						$bean = $BeanFactory->getBean($bean_name . "PDispatcherCacheHandler");
						if (!empty($bean)) {
							$BeanFactory->initObject($bean->name);
							$options["PresentationPDispatcherCacheHandler"] = $BeanFactory->getObject($bean->name);
						}
						
						$bean_objs = self::getBeanLayerObjs($obj, $path, $recursive_level, $options); 
						$item_type = "presentation";
					}
				}
				break;
		}
		
		if (!empty($bean_objs)) {
			$bean_objs["properties"]["bean_file_name"] = $bean_file_name;
			$bean_objs["properties"]["bean_name"] = $bean_name;
			$bean_objs["properties"]["item_type"] = $item_type;
			$bean_objs["properties"]["item_id"] = self::getItemId("$bean_file_name/$bean_name");
		}
		
		//ROLLBACK TO ORIGINAL GLOBAL VARIABLES
		$PHPVariablesFileHandler->endUserGlobalVariables();
		
		return $bean_objs;
	}*/
	
	private static function getPresentationBeanRelatedBeanObjects($BeanFactory, $bean, $obj) {
		$options = array();
		
		$b = self::getBeanFromBeanPropertyReference($BeanFactory, "PresentationRouter", "presentationLayer", $bean->name);
		if ($b)
			$options["PresentationPRouter"] = $BeanFactory->getObject($b->name);
		
		$b = self::getBeanFromBeanPropertyReference($BeanFactory, "EVC", "presentationLayer", $bean->name);
		if ($b) {
			$b = self::getBeanFromBeanConstructorArgReference($BeanFactory, "CMSLayer", 1, $b->name);
			
			if ($b) {
				$b = self::getBeanFromBeanConstructorArgReference($BeanFactory, "MultipleCMSCacheLayer", 1, $b->name);
				
				if ($b)
					$options["PresentationMultipleCMSCacheLayer"] = $BeanFactory->getObject($b->name);
			}
		}
		
		$b = self::getBeanFromBeanConstructorArgReference($BeanFactory, "DispatcherCacheHandler", 2, $bean->constructor_args[1]->reference);
		if (!$b) {
			$bean_layer_name = WorkFlowBeansFileHandler::getLayerNameFromBeanObject($bean->name, $obj);
			$b = $BeanFactory->getBean($bean_layer_name . "PDispatcherCacheHandler");
			
			if (!$b) 
				$b = self::getBeanFromBeanClass($BeanFactory, "DispatcherCacheHandler");
		}
		
		if ($b)
			$options["PresentationPDispatcherCacheHandler"] = $BeanFactory->getObject($b->name);
		
		return $options;
	}
	
	private static function getBeanFromBeanPropertyReference($BeanFactory, $bean_class_to_search, $bean_property_name_to_search, $bean_property_reference_to_search) {
		$beans = $BeanFactory->getBeans();
		
		foreach($beans as $bn => $b) {
			$BeanFactory->initObject($bn);
			$obj = $BeanFactory->getObject($bn);
			
			if (is_a($obj, $bean_class_to_search) && $b->properties)
				foreach ($b->properties as $property)
					if ($property->name == $bean_property_name_to_search && $property->reference == $bean_property_reference_to_search)
						return $b;
		}
		
		return null;
	}
	
	private static function getBeanFromBeanConstructorArgReference($BeanFactory, $bean_class_to_search, $bean_constructor_arg_index_to_search, $bean_constructor_arg_reference_to_search) {
		$bean_constructor_arg_index_to_search = $bean_constructor_arg_index_to_search ? $bean_constructor_arg_index_to_search : 0;
		
		$beans = $BeanFactory->getBeans();
		
		foreach($beans as $bn => $b) {
			$BeanFactory->initObject($bn);
			$obj = $BeanFactory->getObject($bn);
			
			if (is_a($obj, $bean_class_to_search) && $b->constructor_args)
				foreach ($b->constructor_args as $constructor_arg)
					if ($constructor_arg->index == $bean_constructor_arg_index_to_search && $constructor_arg->reference == $bean_constructor_arg_reference_to_search)
						return $b;
		}
		
		return null;
	}
	
	private static function getBeanFromBeanClass($BeanFactory, $bean_class_to_search) {
		$beans = $BeanFactory->getBeans();
		
		foreach($beans as $bn => $b) {
			$BeanFactory->initObject($bn);
			$obj = $BeanFactory->getObject($bn);
			
			if (is_a($obj, $bean_class_to_search))
				return $b;
		}
		
		return null;
	}
	
	private static function getLayerTypeFromBeanObject($obj) {
		if (is_a($obj, "DBLayer"))
			return "db";
		else if (is_a($obj, "DataAccessLayer"))
			return $obj->getType();
		else if (is_a($obj, "BusinessLogicLayer"))
			return "businesslogic";
		else if (is_a($obj, "PresentationLayer"))
			return "presentation";
		
		return null;
	}
	
	public static function getBeanLayerObjs($Layer, $path, $recursive_level = -1, $options = null) {
		$bean_objs = array();
		
		$modules_file_path = $Layer->getModulesFilePathSetting();
		$aliases = !empty($modules_file_path) ? $Layer->getModulesAlias($modules_file_path) : array();
		
		if (is_a($Layer, "DataAccessLayer")) {
			self::prepareLayerServices($Layer, $path, $aliases);
			$objs = self::getBeanDataAccessObjs($Layer, $path, $recursive_level, $aliases);
		}
		else if (is_a($Layer, "BusinessLogicLayer")) {
			self::prepareLayerServices($Layer, $path, $aliases);
			$objs = self::getBeanBusinessLogicModules($Layer, $path, $recursive_level, $aliases, $options);
			
			//check if business logic layer is connected to any data access layers
			if ($objs)
				$objs["properties"]["automatic_ui"] = self::isLayerAccessableToAnotherLayer($Layer, array("IDataAccessBrokerClient", "IDBBrokerClient"));
		}
		else if(is_a($Layer, "PresentationLayer")) {
			$objs = self::getBeanPresentationProjects($Layer, $path, $recursive_level, $aliases, $options);
			
			//check if presentation layer is connected to any business logic or data access layers
			if ($objs)
				$objs["properties"]["automatic_ui"] = self::isLayerAccessableToAnotherLayer($Layer, array("IBusinessLogicBrokerClient", "IDataAccessBrokerClient", "IDBBrokerClient"));
		}
		
		self::prepareAliases($Layer, $aliases);
		$bean_objs["aliases"] = $aliases;
		
		$bean_objs = !empty($objs) ? array_replace($bean_objs, $objs) : array(); //if $Layer is DBLAyer then $objs is null
		
		return $bean_objs;
	}
	
	//layers_type: IBusinessLogicBrokerClient or IDataAccessBrokerClient or IDBBrokerClient
	private static function isLayerAccessableToAnotherLayer($Layer, $layers_interface_class) {
		$layers_interface_class = is_array($layers_interface_class) ? $layers_interface_class : ($layers_interface_class ? array($layers_interface_class) : null);
		$brokers = $Layer->getBrokers();
		
		if ($layers_interface_class && $brokers)
			foreach ($layers_interface_class as $layer_interface_class)
				foreach ($brokers as $broker_name => $broker)
					if (is_a($broker, $layer_interface_class))
						return true;
		
		return false;
	}
	
	private static function prepareLayerServices($Layer, $path, &$aliases) {
		$services_file_name = $Layer->getServicesFileNameSetting();
		
		$path_prefix = $Layer->getLayerPathSetting();
		
		if (!empty($services_file_name) && !empty($path)) {
			do {
				$path = dirname($path) . "/";
				$path = $path == "./" ? "" : $path;
				
				$aliases = array_merge($aliases, $Layer->getServicesAlias($path_prefix . $path . $services_file_name));
			} 
			while(!empty($path)); 
		}
	}
	
	private static function getGenericFiles($path_prefix, $path, $layer_type, $recursive_level = -1, $allow_zip = true) {
		$absolute_path = $path_prefix . $path;
		$rl = $recursive_level > 0 ? $recursive_level - 1 : $recursive_level;
		
		$files = array();
		
		if ( ($recursive_level > 0 || $recursive_level == -1) && is_dir($absolute_path) && ($dir = opendir($absolute_path)) ) {
			while( ($file = readdir($dir)) !== false) {
				if (substr($file, 0, 1) != ".") {
					$file_path = $absolute_path . $file;
					$file_key = $file;
					
					$properties = array(
						"item_type" => (is_dir($file_path) ? "folder" : "file"),
						"item_id" => self::getItemId($file_path),
						"item_menu" => self::getFileItemMenu($file_path)
					);
					
					if (is_dir($file_path)) {
						$properties["path"] = $path . $file . "/";
						
						//fix issue when exists multiple files with the same name
						self::prepareListKeyIfAlreadyExists($file_key, $files);
						
						if ($recursive_level > 0 || $recursive_level == -1) {
							$sub_daos = self::getGenericFiles($path_prefix, $properties["path"], $layer_type, $rl, $allow_zip);
							$files[$file_key] = array_merge(array("properties" => $properties), $sub_daos);
						}
						else
							$files[$file_key] = array("properties" => $properties);
						
						if ($layer_type == "dao") {
							$is_common_folder = $absolute_path == $path_prefix && $file == "common";
							$is_cms_modules_folder = $absolute_path == $path_prefix && $file == "module";
							$is_cms_programs_folder = $absolute_path == $path_prefix && $file == "program";
							$is_cms_resources_folder = $absolute_path == $path_prefix && $file == "resource";
							
							$files[$file_key]["properties"]["item_type"] = $is_common_folder ? "cms_common" : ($is_cms_modules_folder ? "cms_module" : ($is_cms_programs_folder ? "cms_program" : ($is_cms_resources_folder ? "cms_resource" : "folder")));
						}
					}
					else {
						$path_info = pathinfo($file_path);
						$file_extension = isset($path_info["extension"]) ? strtolower($path_info["extension"]) : "";
						$type = null;
						
						if ($file_extension == "zip" && $allow_zip)
							$type = "zip_file";
						else if ($layer_type == "dao" && $file_extension == "php") {
							try {
								include_once $file_path;
							
								$type = is_subclass_of($path_info["filename"], "ObjType") ? "objtype" : (is_subclass_of($path_info["filename"], "HibernateModel") ? "hibernatemodel" : "file");
							}
						   	catch (Error $e) {
								//Do not do anything
						   	}
							catch(ParseError $e) {
								//Do not do anything
							}
							catch(ErrorException $e) {
								//Do not do anything
							}
							catch(Exception $e) {
								//Do not do anything
							}
						}
						else 
							$type = "file";
						
						$file_key = $file_extension == "php" ? $path_info["filename"] : $file;
						
						//fix issue when exists multiple files with the same name
						self::prepareFileKeyIfAlreadyExists($file_key, $files, $absolute_path . $file_key);
						
						$properties["path"] = $path . $file;
						$properties["item_type"] = $type;
						$files[$file_key] = array("properties" => $properties);
						
						if ($layer_type == "test_unit" && $file_extension == "php") {
							try {
								$contents = file_get_contents($file_path);
								
								//if $file_path doesn't contain exactly this code 'public function execute($settings = false)', when we include it, it will give a php error and the code will break and stop executing. Note that all these files must implements this function ITestUnit. so DO NOT USE is_subclass_of($path_info["filename"], "TestUnit"), otherwise if there is a wrong implementation of the ITestUnit::execute method, this will give a php error and the scrpt will stop executing...
								if (preg_match('/public\s+function\s+execute\s*\(\s*\)/', $contents) && preg_match('/\s+extends\s+TestUnit\s+/', $contents)) {
									$methods = PHPCodePrintingHandler::getPHPClassesFromFile($file_path);
									unset($methods[0]);
								
									if (!empty($methods) && count($methods) == 1) {//with class inside of $file_path
										foreach ($methods as $class_path => $class_data) {
											$class_name = isset($class_data["name"]) ? $class_data["name"] : null;
											$is_test_unit = false;
											
											if (!empty($class_data["methods"])) {
												$t = count($class_data["methods"]);
												
												for ($i = 0; $i < $t; $i++) {
													$method = $class_data["methods"][$i];
													
													if (isset($method["name"]) && $method["name"] == "execute") {
														$is_test_unit = true;
														break;
													}
												}
											}
											
											//only one testunit class per file allowed!
											if ($is_test_unit && $class_path == $path_info["filename"]) {
												$properties = array("path" => $path . $file);
												$properties["item_type"] = "test_unit_obj";
												$properties["item_id"] = self::getItemId("$path$file/$class_path");
												
												$cn = $class_path;
												self::prepareListKeyIfAlreadyExists($cn, $files[$file_key]);
												
												$files[$file_key][$cn] = array("properties" => $properties);
												$files[$file_key]["properties"]["item_class"] = "disabled";
											}
										}
									}
								}	
							}
						   	catch (Error $e) {
								//Do not do anything
						   	}
							catch(ParseError $e) {
								//Do not do anything
							}
							catch(ErrorException $e) {
								//Do not do anything
							}
							catch(Exception $e) {
								//Do not do anything
							}
						}
					}
				}
			}
			closedir($dir);
		}
		
		ksort($files);
		
		return $files;
	}
	
	public static function getBeanDBObjs($bean, $beans, $bean_objs) {
		$objs = array();
		
		$t = count($bean->functions);
		for ($i = 0; $i < $t; $i++) {
			if ($bean->functions[$i]->name == "addBroker") {
				$reference = $bean->functions[$i]->parameters[1]->reference;
				
				if (!empty($beans[$reference])) {
					$db_bean = $beans[$reference];
					$db_properties = array(
						"type" => DB::getDriverTypeByClassName($db_bean->class_name)
					);
					
					$t2 = count($db_bean->functions);
					for ($j = 0; $j < $t2; $j++) {
						if (strtolower($db_bean->functions[$j]->name) == "setoptions") {
							foreach ($db_bean->functions[$j]->parameters as $parameter) {
								$bean_options = $bean_objs[$parameter->reference];
								
								if (is_array($bean_options))
									$db_properties = array_merge($db_properties, $bean_options);
								
								break;//only gets the first parameter
							}
						}
					}
					
					if (isset($db_properties["password"]))
						$db_properties["password"] = "***";
					
					if (isset($db_properties["persistent"]))
						$db_properties["persistent"] = $db_properties["persistent"] ? "YES" : "NO";
					
					if (isset($db_properties["new_link"]))
						$db_properties["new_link"] = $db_properties["new_link"] ? "YES" : "NO";
					
					$objs[ $db_bean->name ]["properties"] = array(
						"item_type" => "db_driver",
						"item_id" => self::getItemId($db_bean->path . "/" . $db_bean->name),
						"item_class" => "db_driver_" . $db_properties["type"],
						"item_menu" => $db_properties,
						"bean_name" => $db_bean->name,
						"bean_file_name" => self::getBeanFilePathByBeanName($db_bean->name),
					);
					//print_r($objs[ $db_bean->name ]);
				}
			}
		}
		
		ksort($objs);
		
		return $objs;
	}
	
	private static function getBeanFilePathByBeanName($bean_name, $path = "") {
		$path .= $path && substr($path, -1) != "/" ? "/" : "";
		$beans_files = scandir(BEAN_PATH . $path);
		
		if ($beans_files)
			foreach ($beans_files as $beans_file)
				if ($beans_file != "." && $beans_file != "..") {
					$relative_file_path = $path . $beans_file;
					$file_path = BEAN_PATH . $relative_file_path;
					
					if (is_dir($file_path)) {
						$bean_file_name = self::getBeanFilePathByBeanName($bean_name, $relative_file_path);
					}
					else {
						$contents = file_get_contents($file_path);
						preg_match('/<bean([^>]*)name="' . $bean_name . '"/', $contents, $matches, PREG_OFFSET_CAPTURE);
						
						if ($matches)
							$bean_file_name = $relative_file_path;
					}
					
					if (!empty($bean_file_name))
						return str_replace("//", "/", $bean_file_name);
				}
	}
	
	private static function getBeanDataAccessObjs($DataAccessLayer, $path, $recursive_level = -1, &$aliases = false) {
		$path_prefix = $DataAccessLayer->getLayerPathSetting();
		
		$objs = array("properties" => array("path" => $path));
		$aliases = empty($aliases) ? array() : $aliases;
		
		$absolute_path = $path_prefix . $path;
		$rl = $recursive_level > 0 ? $recursive_level - 1 : $recursive_level;
		
		$reserved_dal_file_names = array("modules.xml", "services.xml", "cache.xml", "cache_handler.xml");
		
		//PREPARING SERVICES ALIAS
		$services_file_name = $DataAccessLayer->getServicesFileNameSetting();
		if (!empty($services_file_name))
			$aliases = array_merge($aliases, $DataAccessLayer->getServicesAlias($absolute_path . $services_file_name));
		
		//PREPARING SERVICES FROM FILE SYSTEM
		if ( ($recursive_level > 0 || $recursive_level == -1) && is_dir($absolute_path) && ($dir = opendir($absolute_path)) ) {
			while( ($file = readdir($dir)) !== false) {
				if (substr($file, 0, 1) != ".") {
					$file_path = $absolute_path . $file;
					$path_info = pathinfo($file);
					$file_extension = isset($path_info["extension"]) ? strtolower($path_info["extension"]) : "";
					$file_key = $file;
					$item_menu = self::getFileItemMenu($file_path);
					
					if (is_dir($file_path)) {
						$is_common_folder = $absolute_path == $path_prefix && $file == "common";
						$is_cms_modules_folder = $absolute_path == $path_prefix && $file == "module";
						$is_cms_programs_folder = $absolute_path == $path_prefix && $file == "program";
						$is_cms_resources_folder = $absolute_path == $path_prefix && $file == "resource";
						
						if ($recursive_level > 0 || $recursive_level == -1) {
							$sub_objs = self::getBeanDataAccessObjs($DataAccessLayer, $path . $file . "/", $rl, $aliases);
						}
						else {
							$sub_objs = array("properties" => array("path" => $path . $file . "/"));
						}
						
						$sub_objs["properties"]["item_type"] = $is_common_folder ? "cms_common" : ($is_cms_modules_folder ? "cms_module" : ($is_cms_programs_folder ? "cms_program" : ($is_cms_resources_folder ? "cms_resource" : "folder")));
						$sub_objs["properties"]["item_id"] = self::getItemId($file_path);
						$sub_objs["properties"]["item_menu"] = $item_menu;
						
						if (isset($aliases[ $file_path ])) {
							$sub_objs["properties"]["alias"] = $aliases[ $file_path ];
						}
						
						//fix issue when exists multiple files with the same name
						self::prepareListKeyIfAlreadyExists($file_key, $objs);
						
						$objs[$file_key] = $sub_objs;
					}
					else if ($file_extension == "xml") {
						$file_key = $file_extension == "xml" ? $path_info["filename"] : $file_key;
						
						//fix issue when exists multiple files with the same name
						self::prepareFileKeyIfAlreadyExists($file_key, $objs, $file_path);
						
						try {
							$xml_arr = self::getXmlFileContentArray($file_path);
							
							$file_type = null;
							$sub_objs = array();
							
							if (isset($xml_arr["sql_mapping"])) {
								$nodes = isset($xml_arr["sql_mapping"][0]["childs"]) ? $xml_arr["sql_mapping"][0]["childs"] : null;
								//print_r($nodes);
								
								if ($DataAccessLayer->getType() == "ibatis") {
									$nodes = HibernateClient::getDataAccessNodesConfigured($nodes);
									
									$sub_objs = self::getDataAccessNodeSubQueries($nodes, $aliases, $file_path, $path_prefix, "queries");
								}
								else if (!empty($nodes["class"])) {
									$t = count($nodes["class"]);
									for($i = 0; $i < $t; $i++) {
										$obj = $nodes["class"][$i];
										
										HibernateClient::prepareObjNode($obj);
										//print_r($obj);
										
										$obj_id = XMLFileParser::getAttribute($obj, "name");
										
										//print_r($obj["childs"]["queries"]);
										$queries = !empty($obj["childs"]["queries"]) ? self::getDataAccessNodeSubQueries($obj["childs"]["queries"], $aliases, $file_path, $path_prefix, "queries", $obj_id) : array();
										//print_r($queries);
									
										//print_r($obj["childs"]["relationships"]);
										$relationships = !empty($obj["childs"]["relationships"]) ? self::getDataAccessNodeSubQueries($obj["childs"]["relationships"], $aliases, $file_path, $path_prefix, "relationships", $obj_id) : array();
										//echo "<pre>";print_r($relationships);die();
										
										$natives = self::getHibernateNativeNodeFunctions($file_path, $path_prefix, $obj_id);
										
										$sub_properties = isset($obj["@"]) ? $obj["@"] : null;
										if (isset($aliases[$file_path][$obj_id])) {
											$sub_properties["alias"] = $aliases[$file_path][$obj_id];
										}
									
										$sub_properties["path"] = $path . $file;
										$sub_properties["item_type"] = "obj";
										$sub_properties["item_menu"] = $item_menu;
										
										$sub_objs[$obj_id] = array_merge(array("properties" => $sub_properties), $natives, $relationships, $queries);
									}
								}
								
								if (!empty($nodes["import"])) {
									$t = count($nodes["import"]);
									for ($i = 0; $i < $t; $i++) {
										$import_id = XMLFileParser::getValue($nodes["import"][$i]);
										
										if ($import_id) {
											$b = basename($import_id);
											$import_id = strlen($b) >= 20 ? $b : "..." . substr($import_id, strlen($import_id) - 20);
										}
										else {
											$import_id = "import_" . ($i + 1);
										}
										
										$sub_objs[$import_id] = array(
											"properties" => array(
												"path" => $path . $file, 
												"item_type" => "call_import", 
												"item_id" => self::getItemId("$file_path/$import_id"),
												"item_menu" => $item_menu
											),
										);
									}
								}
								
								$file_type = "file";
							}
							else if (isset($xml_arr["import"])) {
								$nodes = isset($xml_arr["import"][0]["childs"]) ? $xml_arr["import"][0]["childs"] : null;
								
								$sub_objs = $nodes ? self::getDataAccessNodeSubQueries($nodes, $aliases, $file_path, $path_prefix, "import", false) : array();
									
								$file_type = "import";
							}
							
							$properties = array(
								"path" => $path . $file, 
								"item_type" => in_array($file, $reserved_dal_file_names) ? "reserved_file" : ($file_type ? $file_type : "file"), 
								"item_id" => self::getItemId($file_path),
								"item_class" => $file_type == "file" ? "disabled" : "",
								"item_menu" => $item_menu
							);
							$objs[$file_key] = array_merge(array("properties" => $properties), $sub_objs);
						}
						catch(Exception $e) {
							/*echo "file_path:$file_path == ".substr($file, strlen($file) - 4)."\n<br>";
							echo "STRING:".$e->__toString()."\n";
							echo $e->getTraceAsString();*/
							
							$objs[$file_key] = array("properties" => array(
								"path" => $path . $file, 
								"item_type" => in_array($file, $reserved_dal_file_names) ? "reserved_file" : "file", 
								"item_id" => self::getItemId($file_path),
								"item_menu" => $item_menu
							));
						}
					}
					else if (!empty($path)) {
						//fix issue when exists multiple files with the same name
						self::prepareFileKeyIfAlreadyExists($file_key, $objs, $file_path);
						
						$objs[$file_key] = array("properties" => array(
							"path" => $path . $file, 
							"item_type" => in_array($file, $reserved_dal_file_names) ? "reserved_file" : ($file_extension == "zip" ? "zip_file": "undefined_file"), 
							"item_id" => self::getItemId($file_path),
							"item_menu" => $item_menu
						));
					}
				}
			}
			closedir($dir);
		}
		
		ksort($objs);
		
		return $objs;
	}
	
	private static function getDataAccessNodeSubQueries($nodes, $aliases, $file_path, $path_prefix, $relationship_type, $hbn_obj_id = false) {
		$sub_queries = array();
		$item_menu = self::getFileItemMenu($file_path);
		
		foreach ($nodes as $node_type => $sub_nodes) {
			$item_type = null;
			
			if ($node_type == "insert" || $node_type == "update" || $node_type == "select" || $node_type == "delete" || $node_type == "procedure") {
				$item_type = "query";
			}
			else if ($node_type == "one_to_one" || $node_type == "one_to_many" || $node_type == "many_to_one" || $node_type == "many_to_many") {
				$item_type = "relationship";
			}
			else if ($node_type == "parameter_map" || $node_type == "result_map") {
				$item_type = "map";
			}
			
			if ($item_type) {
				foreach ($sub_nodes as $node_id => $node) {
					if (is_numeric($node_id)) //This part will be used for the Maps in the HBN RELATIONSHIPS
						$node_id = XMLFileParser::getAttribute($node, "id");
					
					if ($node_id) {
						$sub_properties = array(
							"path" => substr($file_path, strlen($path_prefix)),
							"item_type" => $item_type, 
							"item_id" => self::getItemId("$file_path/$node_id"),
							"item_menu" => $item_menu,
							"item_title" => $node_type . " " . $item_type,
							"query_type" => $node_type,
							"relationship_type" => $relationship_type,
						);
				
						if ($hbn_obj_id)
							$sub_properties["hbn_obj_id"] = $hbn_obj_id;
						
						if (isset($aliases[$file_path][$node_id]))
							$sub_properties["alias"] = $aliases[$file_path][$node_id];
						
						$sub_queries[$node_id] = array("properties" => $sub_properties);
					}
				}
			}
		}
		
		ksort($sub_queries);
		
		return $sub_queries;
	}
	
	private static function getHibernateNativeNodeFunctions($file_path, $path_prefix, $hbn_obj_id) {
		$funcs = array("insert", "insertAll", "update", "updateAll", "insertOrUpdate", "insertOrUpdateAll", "updatePrimaryKeys", "delete", "deleteAll", "findById", "find", "count", "findRelationships", "findRelationship", "countRelationships", "countRelationship");
		
		$t = count($funcs);
		for ($i = 0; $i < $t; $i++) {
			$node_id = $funcs[$i];
			
			$properties = array(
				"path" => substr($file_path, strlen($path_prefix)),
				"item_type" => "hbn_native", 
				"item_id" => self::getItemId("$file_path/$node_id"),
				"query_type" => "",
				"relationship_type" => "native",
				"hbn_obj_id" => $hbn_obj_id,
			);
			$nodes[$node_id] = array("properties" => $properties);
		}
		
		return $nodes;
	}
	
	private static function getBeanBusinessLogicModules($BusinessLogicLayer, $path, $recursive_level = -1, &$aliases = false, $options = null) {
		$path_prefix = $BusinessLogicLayer->getLayerPathSetting();
		$show_all = $options && !empty($options["all"]);
		
		$modules = array("properties" => array("path" => $path));
		$aliases = empty($aliases) ? array() : $aliases;
		
		$absolute_path = $path_prefix . $path;
		$rl = $recursive_level > 0 ? $recursive_level - 1 : $recursive_level;
		
		$reserved_business_logic_file_names = array("modules.xml", "services.xml", "cache.xml", "cache_handler.xml"/*, "CommonService.php"*/); //all these files can be editable!
		
		//PREPARING IF LARAVEL
		$vendor_framework = VendorFrameworkHandler::getVendorFrameworkFolder($absolute_path);
		
		if ($vendor_framework) {
			$modules["properties"]["item_class"] = $vendor_framework;
			$modules["properties"]["vendor_framework"] = $vendor_framework;
		}
		else if (!empty($options["vendor_framework"]))
			$vendor_framework = $options["vendor_framework"];
		
		//PREPARING SERVICES ALIAS
		$services_file_name = $BusinessLogicLayer->getServicesFileNameSetting();
		if (!empty($services_file_name)) 
			$aliases = array_merge($aliases, $BusinessLogicLayer->getServicesAlias($absolute_path . $services_file_name));
		
		//PREPARING SERVICES FROM FILE SYSTEM
		if ( ($recursive_level > 0 || $recursive_level == -1) && is_dir($absolute_path) && ($dir = opendir($absolute_path)) ) {
			while( ($file = readdir($dir)) !== false) {
				if (substr($file, 0, 1) != ".") {
					$file_path = $absolute_path . $file;
					$file_key = $file;
					$item_menu = self::getFileItemMenu($file_path);
					
					if (is_dir($file_path)) {
						$is_common_folder = $absolute_path == $path_prefix && $file == "common";
						$is_cms_modules_folder = $absolute_path == $path_prefix && $file == "module";
						$is_cms_programs_folder = $absolute_path == $path_prefix && $file == "program";
						$is_cms_resources_folder = $absolute_path == $path_prefix && $file == "resource";
						
						if ($recursive_level > 0 || $recursive_level == -1) 
							$sub_modules = self::getBeanBusinessLogicModules($BusinessLogicLayer, $path . $file . "/", $rl, $aliases);
						else {
							$sub_modules = array("properties" => array("path" => $path . $file . "/"));
							$sub_vendor_framework = VendorFrameworkHandler::getVendorFrameworkFolder($file_path);
							
							if ($sub_vendor_framework) {
								$sub_modules["properties"]["item_class"] = $sub_vendor_framework;
								$sub_modules["properties"]["vendor_framework"] = $sub_vendor_framework;
							}
						}
						
						$sub_modules["properties"]["item_type"] = $is_common_folder ? "cms_common" : ($is_cms_modules_folder ? "cms_module" : ($is_cms_programs_folder ? "cms_program" : ($is_cms_resources_folder ? "cms_resource" : "folder")));
						$sub_modules["properties"]["item_id"] = self::getItemId($file_path);
						$sub_modules["properties"]["item_menu"] = $item_menu;
						
						if (!empty($options["vendor_framework"]))
							$sub_modules["properties"]["vendor_framework"] = $options["vendor_framework"];
						
						if (isset($aliases[ $file_path ])) 
							$sub_modules["properties"]["alias"] = $aliases[ $file_path ];
						
						//fix issue when exists multiple files with the same name
						self::prepareListKeyIfAlreadyExists($file_key, $modules);
						
						$modules[$file_key] = $sub_modules;
					}
					else if (!empty($path)) {
						$path_info = pathinfo($file);
						$file_extension = isset($path_info["extension"]) ? strtolower($path_info["extension"]) : "";
						$file_key = $file_extension == "php" ? $path_info["filename"] : $file_key;
						
						//fix issue when exists multiple files with the same name
						self::prepareFileKeyIfAlreadyExists($file_key, $modules, $file_path);
						
						if (in_array($file, $reserved_business_logic_file_names)) {
							$properties = array(
								"path" => $path . $file, 
								"item_type" => "file", //must be file bc needs to be editable. If is reserved_file it will not be editable!
								"item_id" => self::getItemId($file_path),
								"item_menu" => $item_menu
							);
						
							$modules[$file_key] = array("properties" => $properties);
						}
						else {
							$methods = $functions = false;
							$is_php_file = $file_extension == "php";
							
							$properties = array(
								"path" => $path . $file, 
								"item_type" => $is_php_file ? "file" : ($file_extension == "zip" ? "zip_file" : "undefined_file"),
								"item_id" => self::getItemId($file_path),
								"item_menu" => $item_menu
							);
							
							$modules[$file_key] = array("properties" => $properties);
							
							if ($is_php_file) {
								$methods = PHPCodePrintingHandler::getPHPClassesFromFile($file_path);
								//echo "<pre>";print_r($methods);die();
								$classes_count = 0;
								$is_class_different = false;
								
								$functions = isset($methods[0]) ? $methods[0] : null;
								unset($methods[0]);
								
								if (!empty($methods)) {//with class inside of $file_path
									$classes = array();
									
									foreach ($methods as $class_path => $class_data) {
										$class_name = isset($class_data["name"]) ? $class_data["name"] : null;
										$comments = isset($class_data["doc_comments"]) && is_array($class_data["doc_comments"]) ? implode("\n", $class_data["doc_comments"]) : "";
										$is_class_hidden = strpos($comments, "@hidden") !== false;
										//if ($is_class_hidden)echo "$class_path:$is_class_hidden,show_all:$show_all";
										
										if (!$is_class_hidden || $show_all) {
											$classes_count++;
											
											if ($class_name != pathinfo($file, PATHINFO_FILENAME))
												$is_class_different = true;
											
											$properties = array("path" => $path . $file);
											$services = array();
											
											//in case it exists 2 class names with the same name but with different namespaces
											if (!empty($classes[$class_name])) {
												//TODO: check this better. Not sure if this code is correct
												if ($classes[$class_name]) {
													//change previous class name with the namespace
													$cp = isset($classes[$class_name]["properties"]["service"]) ? $classes[$class_name]["properties"]["service"] : null;
													$classes[$cp] = $classes[$class_name];
													unset($classes[$class_name]);
												}
												else if (!empty($classes[$class_name . " "])) {
													//change previous class name with the namespace
													$cp = isset($classes[$class_name . " "]["properties"]["service"]) ? $classes[$class_name . " "]["properties"]["service"] : null;
													$classes[$cp] = $classes[$class_name . " "];
													unset($classes[$class_name . " "]);
												}
												
												$cn = $class_path;
											}
											else
												$cn = $class_name;
											
											//fix issue when exists multiple classes with the same name
											self::prepareFileKeyIfAlreadyExists($cn, $classes, $absolute_path . $class_name);
											self::prepareListKeyIfAlreadyExists($cn, $modules);
											
											if (!empty($class_data["methods"])) {
												$t = count($class_data["methods"]);
												for ($i = 0; $i < $t; $i++) {
													$method = $class_data["methods"][$i];
													$method_type = isset($method["type"]) ? $method["type"] : null;
													$is_public = $method_type == "public" || $method_type == "construct";
													
													if ($is_public || $show_all) {
														$comments = isset($method["doc_comments"]) && is_array($method["doc_comments"]) ? implode("\n", $method["doc_comments"]) : "";
														$is_hidden = strpos($comments, "@hidden") !== false;
												
														if (!$is_hidden || $show_all) {
															$method_name = isset($method["name"]) ? $method["name"] : null;
												
															$sub_properties = array(
																"path" => $path . $file, 
																"item_type" => "method", 
																//"item_id" => self::getItemId("$file_path/$class_path/$method_name"),  //if exists a function with the same name than the class name, then we will overwrite it with "$file_path/$class_path". So we MUST use the "$file_path/$cn" instead!
																"item_id" => self::getItemId("$file_path/$cn/$method_name"),
																"item_class" => !$is_public || $is_hidden ? "hidden" : "",
																"service" => $class_path
															);
															
															if (isset($aliases[$file_path][$class_name][$method_name])) 
																$sub_properties["alias"] = $aliases[$file_path][$class_name][$method_name];
															
															if (isset($aliases[$file_path][$class_path][$method_name])) 
																$sub_properties["alias"] = $aliases[$file_path][$class_path][$method_name];
															
															$services[$method_name] = array("properties" => $sub_properties);
														}
													}
												}
											}
											
											if (!empty($class_data["extends"]))
												$properties["extends"] = $class_data["extends"];
											
											unset($properties["alias"]);
											if (isset($aliases[$file_path][$class_name])) {
												$keys = array_keys($aliases[$file_path][$class_name]);
												foreach ($keys as $key)
													if (is_numeric($key))
														$properties["alias"][] = $aliases[$file_path][$class_name][$key];
											}
											
											if (isset($aliases[$file_path][$class_path])) {
												$keys = array_keys($aliases[$file_path][$class_path]);
												foreach ($keys as $key)
													if (is_numeric($key))
														$properties["alias"][] = $aliases[$file_path][$class_path][$key];
											}
											
											$properties["item_type"] = "service";
											//$properties["item_id"] = self::getItemId("$file_path/$class_path"); //if exists a function with the same name than the class name, then we will overwrite it with "$file_path/$class_path". So we MUST use the "$file_path/$cn" instead!
											$properties["item_id"] = self::getItemId("$file_path/$cn");
											$properties["service"] = $class_path;
											$properties["item_menu"] = $item_menu;
											$properties["item_class"] = $is_class_hidden ? "hidden" : "";
											
											$classes[$cn] = array_merge(array("properties" => $properties), $services);
										}
									}
									
									$modules[$file_key] = array_merge($modules[$file_key], $classes);
								}
								
								if (is_array($functions) && !empty($functions["methods"])) {//without class inside of $file_path
									$properties = array("path" => $path . $file, "item_type" => "function");
									
									$funcs = array();
									
									//echo "Aliases:";print_r($aliases);die();
									$t = count($functions["methods"]);
									for ($i = 0; $i < $t; $i++) {
										$method = $functions["methods"][$i];
										
										$comments = isset($method["doc_comments"]) && is_array($method["doc_comments"]) ? implode("\n", $method["doc_comments"]) : "";
										$is_hidden = strpos($comments, "@hidden") !== false;
									
										if (!$is_hidden || $show_all) {
											$method_name = isset($method["name"]) ? $method["name"] : null;
											
											unset($properties["alias"]);
											if (isset($aliases[$file_path][0][$method_name]))
												$properties["alias"] = $aliases[$file_path][0][$method_name];
											
											//fix issue when exists multiple functions with the same name
											self::prepareFileKeyIfAlreadyExists($method_name, $funcs, $absolute_path . $method_name);
											self::prepareListKeyIfAlreadyExists($method_name, $modules);
											
											$properties["function"] = $method;
											$properties["item_id"] = self::getItemId("$file_path/$method_name");
											$properties["item_menu"] = $item_menu;
											$properties["item_class"] = $is_hidden ? "hidden" : "";
											
											$funcs[$method_name] = array("properties" => $properties);
										}
									}
									
									$modules[$file_key] = array_merge($modules[$file_key], $funcs);
								}
								else if ($classes_count == 1 && !$is_class_different)
									$modules[$file_key]["properties"]["item_class"] = "disabled";
							}
						}
					}
				}
			}
			closedir($dir);
			
			if ($vendor_framework) {
				$options = $options ? $options : array();
				$options["hidden"] = true;
				$sub_modules = self::getSubFiles($BusinessLogicLayer, $path, $recursive_level, "", $options);
				
				if ($sub_modules) {
					$discard_types = array("css_file", "js_file", "img_file", "undefined_file");
					
					foreach ($sub_modules as $file_key => $file_props) {
						if (!empty($modules[$file_key]) && $file_props["properties"]["item_type"] == "file" && !preg_match("/Service\.php$/", $file_props["properties"]["path"]))
							$modules[$file_key] = $file_props;
						else if (empty($modules[$file_key]) || in_array($modules[$file_key]["properties"]["item_type"], $discard_types)) {
							$file_props["properties"]["item_type"] = "undefined_file";
							$modules[$file_key] = $file_props;
						}
					}
				}
			}
		}
		
		ksort($modules);
		
		return $modules;
	}
	
	private static function getBeanPresentationProjects($PresentationLayer, $path, $recursive_level = -1, &$aliases = false, $options = null) {
		$path_prefix = $PresentationLayer->getLayerPathSetting();
		
		$projects = array("properties" => array("path" => $path));
		$aliases = empty($aliases) ? array() : $aliases;
		
		$absolute_path = $path_prefix . $path;
		$rl = $recursive_level > 0 ? $recursive_level - 1 : $recursive_level;
		
		if (empty($PresentationLayer->settings["presentation_webroot_path"]))
			launch_exception(new Exception("'PresentationLayer->settings[presentation_webroot_path]' cannot be undefined!"));
		
		$webroot_file_relative_path = $PresentationLayer->settings["presentation_webroot_path"];
		$common_project_name = $PresentationLayer->getCommonProjectName();
		
		$is_path_a_project = $path ? is_dir($absolute_path . $webroot_file_relative_path) : false;
		if ($is_path_a_project) {
			$file = substr($path, -1) == "/" ? substr($path, 0, -1) : $path;
			$projects = self::getBeanPresentationProject($PresentationLayer, "", $rl, $file, $absolute_path, $common_project_name, $options);
		}
		else {
			//PREPARING PROJECTS FROM FILE SYSTEM
			if ( ($recursive_level > 0 || $recursive_level == -1) && is_dir($absolute_path) && ($dir = opendir($absolute_path)) ) {
				while( ($file = readdir($dir)) !== false) {
					if (substr($file, 0, 1) != ".") {
						$file_path = $absolute_path . $file;
						$file_key = $file;
						
						if (is_dir($file_path)) {
							$file_path .= "/";
							
							//fix issue when exists multiple files with the same name
							self::prepareListKeyIfAlreadyExists($file_key, $projects);
							
							if (!is_dir($file_path . $webroot_file_relative_path)) {
								if ($recursive_level > 0 || $recursive_level == -1)
									$sub_projects = self::getBeanPresentationProjects($PresentationLayer, $path . $file . "/", $rl, $aliases, $options);
								else
									$sub_projects = array("properties" => array("path" => $path . $file . "/"));
								
								$sub_projects["properties"]["item_type"] = "project_folder";
								$sub_projects["properties"]["item_id"] = self::getItemId($file_path);
								$sub_projects["properties"]["item_menu"] = self::getFileItemMenu($file_path);
								$sub_projects["properties"]["folder_type"] = "project_folder";
								
								if (isset($aliases[ $file_path ]))
									$sub_projects["properties"]["alias"] = $aliases[ $file_path ];
								
								$projects[$file_key] = $sub_projects;
							}
							else
								$projects[$file_key] = self::getBeanPresentationProject($PresentationLayer, $path, $rl, $file, $file_path, $common_project_name, $options);
						}
						else if ($path || !in_array($file, array("init.php", "modules.xml", ".htaccess"))) {
							//fix issue when exists multiple files with the same name
							self::prepareFileKeyIfAlreadyExists($file_key, $projects, $file_path);
							
							$projects[$file_key] = array(
								"properties" => array(
									"path" => $path . $file, 
									"item_type" => pathinfo($file, PATHINFO_EXTENSION) == "zip" ? "zip_file" : "file",
									"item_id" => self::getItemId($file_path),
									"item_menu" => self::getFileItemMenu($file_path)
								)
							);
						}
					}
				}
				closedir($dir);
				
				ksort($projects);
			}
		}
		
		return $projects;
	}
	
	private static function getBeanPresentationProject($PresentationLayer, $path, $rl, $file, $file_path, $common_project_name, $options = null) {
		$is_common_project = $path . $file == $common_project_name;
		
		$project = array(
			"properties" => array(
				"path" => $path . $file . "/", 
				"item_type" => $is_common_project ? "project_common" : "project", 
				"item_id" => self::getItemId($file_path),
				"item_menu" => self::getFileItemMenu($file_path)
			)
		);
		
		if (empty($PresentationLayer->settings["presentation_entities_path"]))
			launch_exception(new Exception("'PresentationLayer->settings[presentation_entities_path]' cannot be undefined!"));
		else if (empty($PresentationLayer->settings["presentation_views_path"]))
			launch_exception(new Exception("'PresentationLayer->settings[presentation_views_path]' cannot be undefined!"));
		else if (empty($PresentationLayer->settings["presentation_templates_path"]))
			launch_exception(new Exception("'PresentationLayer->settings[presentation_templates_path]' cannot be undefined!"));
		else if (empty($PresentationLayer->settings["presentation_blocks_path"]))
			launch_exception(new Exception("'PresentationLayer->settings[presentation_blocks_path]' cannot be undefined!"));
		else if (empty($PresentationLayer->settings["presentation_utils_path"]))
			launch_exception(new Exception("'PresentationLayer->settings[presentation_utils_path]' cannot be undefined!"));
		else if (empty($PresentationLayer->settings["presentation_configs_path"]))
			launch_exception(new Exception("'PresentationLayer->settings[presentation_configs_path]' cannot be undefined!"));
		else if (empty($PresentationLayer->settings["presentation_controllers_path"]))
			launch_exception(new Exception("'PresentationLayer->settings[presentation_controllers_path]' cannot be undefined!"));
		else if (empty($PresentationLayer->settings["presentation_webroot_path"]))
			launch_exception(new Exception("'PresentationLayer->settings[presentation_webroot_path]' cannot be undefined!"));
		else if (empty($PresentationLayer->settings["presentation_modules_path"]))
			launch_exception(new Exception("'PresentationLayer->settings[presentation_modules_path]' cannot be undefined!"));
		
		$entities_path = $path . $file . "/" . $PresentationLayer->settings["presentation_entities_path"];
		$views_path = $path . $file . "/" . $PresentationLayer->settings["presentation_views_path"];
		$templates_path = $path . $file . "/" . $PresentationLayer->settings["presentation_templates_path"];
		$blocks_path = $path . $file . "/" . $PresentationLayer->settings["presentation_blocks_path"];
		$utils_path = $path . $file . "/" . $PresentationLayer->settings["presentation_utils_path"];
		$configs_path = $path . $file . "/" . $PresentationLayer->settings["presentation_configs_path"];
		$controllers_path = $path . $file . "/" . $PresentationLayer->settings["presentation_controllers_path"];
		$webroot_path = $path . $file . "/" . $PresentationLayer->settings["presentation_webroot_path"];
		$modules_path = $path . $file . "/" . $PresentationLayer->settings["presentation_modules_path"];
		
		$project["configs"] = self::getSubFiles($PresentationLayer, $configs_path, $rl, "config", $options);
		$project["pages (entities)"] = self::getSubFiles($PresentationLayer, $entities_path, $rl, "entity", $options);
		$project["views"] = self::getSubFiles($PresentationLayer, $views_path, $rl, "view", $options);
		$project["templates"] = self::getSubFiles($PresentationLayer, $templates_path, $rl, "template", $options);
		$project["blocks"] = self::getSubFiles($PresentationLayer, $blocks_path, $rl, "block", $options);
		$project["utils"] = self::getSubFiles($PresentationLayer, $utils_path, $rl, "util", $options);
		$project["webroot"] = self::getSubFiles($PresentationLayer, $webroot_path, $rl, "", $options);
		$project["others"] = array(
			"properties" => array(
				"item_type" => "properties", 
			),
			"controllers" => self::getSubFiles($PresentationLayer, $controllers_path, $rl, "controller", $options),
			"modules" => self::getSubFiles($PresentationLayer, $modules_path, $rl, "module", $options),
		);
		
		//prepare caches
		//prepare caches - pages
		$PresentationCacheLayer = $PresentationLayer->getCacheLayer();
		
		if ($PresentationCacheLayer && !empty($PresentationCacheLayer->settings["presentation_caches_path"])) {
			$caches_path = $path . $file . "/" . $PresentationCacheLayer->settings["presentation_caches_path"];
			
			$project["others"]["caches"] = self::getSubFiles($PresentationLayer, $caches_path, $rl, "cache", $options);
			
			$project["others"]["caches"]["properties"] = array(
				"path" => $caches_path,
				"item_type" => "caches_folder", 
				"item_id" => self::getItemId($caches_path),
				"folder_type" => "cache",
			);
			
			if (!$is_common_project)
				$project["others"]["caches"] = self::preparePresentationCachePageBeanObjects($project["others"]["caches"], $options, $path . $file . "/", $PresentationCacheLayer);
		}
		
		if ($options && !$is_common_project) {
			$project_prefix = $path . $file . "/";
			
			$project["others"]["caches"] = isset($project["others"]["caches"]) ? $project["others"]["caches"] : null;
			$project["others"]["caches"] = self::preparePresentationCacheModuleAndBlockBeanObjects($project["others"]["caches"], $options, $project_prefix);
			$project["others"]["caches"] = self::preparePresentationDispatcherBeanObjects($project["others"]["caches"], $options, $project_prefix);
			
			$project["others"]["routers"] = isset($project["others"]["routers"]) ? $project["others"]["routers"] : null;
			$project["others"]["routers"] = self::preparePresentationRouterBeanObjects($project["others"]["routers"], $options, $project_prefix);
		}
		
		$project["configs"] = self::preparePresentationConfigBeanObjects($project["configs"], $options);
		$project["utils"] = self::preparePresentationUtilBeanObjects($project["utils"], $options, $PresentationLayer);
		$project["webroot"] = self::preparePresentationWebrootBeanObjects($project["webroot"], $options);
		
		if ($is_common_project)
			$project["webroot"] = self::preparePresentationCommonWebrootBeanObjects($project["webroot"], $options, $path . $file . "/webroot/", $common_project_name);
		
		$project["templates"] = self::preparePresentationTemplateBeanObjectsForMainTemplateFolders($project["templates"], $options); //only fo this for /src/template folder
		
		if ($project["templates"])
			foreach ($project["templates"] as $k => $v)
				if ($k != "properties" && isset($v["properties"]["item_type"]) && $v["properties"]["item_type"] == "template_folder")
					$project["templates"][$k] = self::preparePresentationTemplateBeanObjects($v, $options); //only do this for the 1st level inside of templates, if exists...
		
		$project["configs"]["properties"] = array(
			"path" => $configs_path,
			"item_type" => "configs_folder", 
			"item_id" => self::getItemId($configs_path),
			"folder_type" => "config",
		);
		$project["pages (entities)"]["properties"] = array(
			"path" => $entities_path,
			"item_type" => "entities_folder", 
			"item_id" => self::getItemId($entities_path),
			"folder_type" => "entity",
		);
		$project["views"]["properties"] = array(
			"path" => $views_path,
			"item_type" => "views_folder", 
			"item_id" => self::getItemId($views_path),
			"folder_type" => "view",
		);
		$project["templates"]["properties"] = array(
			"path" => $templates_path,
			"item_type" => "templates_folder", 
			"item_id" => self::getItemId($templates_path),
			"folder_type" => "template",
		);
		$project["blocks"]["properties"] = array(
			"path" => $blocks_path,
			"item_type" => "blocks_folder", 
			"item_id" => self::getItemId($blocks_path),
			"folder_type" => "block",
		);
		$project["utils"]["properties"] = array(
			"path" => $utils_path,
			"item_type" => "utils_folder", 
			"item_id" => self::getItemId($utils_path),
			"folder_type" => "util",
		);
		$project["webroot"]["properties"] = array(
			"path" => $webroot_path,
			"item_type" => "webroot_folder", 
			"item_id" => self::getItemId($webroot_path),
			"folder_type" => "",
		);
		$project["others"]["controllers"]["properties"] = array(
			"path" => $controllers_path,
			"item_type" => "controllers_folder", 
			"item_id" => self::getItemId($controllers_path),
			"folder_type" => "controller",
		);
		
		if (file_exists($file_path . "/" . $PresentationLayer->settings["presentation_modules_path"])) {
			if ($is_common_project && $project["others"]["modules"])
				$project["others"]["modules"] = self::preparePresentationModuleBeanObjects($project["others"]["modules"], $options, $PresentationLayer);
		
			$project["others"]["modules"]["properties"] = array(
				"path" => $modules_path,
				"item_type" => "cms_module",
				"item_id" => self::getItemId($modules_path),
				"folder_type" => "module",
			);
		}
		else 
			unset($project["others"]["modules"]);
		
		if (file_exists($file_path . "/" . $PresentationLayer->settings["presentation_entities_path"]))
			$project["pages (entities)"]["properties"]["project_with_auto_view"] = self::getProjectWithAutoViewGlobalVariable($PresentationLayer, $file_path);
		
		return $project;
	}
	
	private static function getProjectWithAutoViewGlobalVariable($PresentationLayer, $file_path) {
		if (empty($PresentationLayer->settings["presentation_configs_path"]))
			launch_exception(new Exception("'PresentationLayer->settings[presentation_configs_path]' cannot be undefined!"));
		
		$pre_init_config_path = $file_path . "/" . $PresentationLayer->settings["presentation_configs_path"] . "pre_init_config." . $PresentationLayer->getPresentationFileExtension();
		
		//SET USER GLOBAL VARIABLES
		//Note that there is no need to include the $global_variables_file_path, bc this function will be called by the self::getBeanObjs which already call the $global_variables_file_path, this is, $PHPVariablesFileHandler = new PHPVariablesFileHandler($global_variables_file_path);
		$PHPVariablesFileHandler = new PHPVariablesFileHandler($pre_init_config_path);
		$PHPVariablesFileHandler->startUserGlobalVariables();
		
		$project_with_auto_view = !empty($GLOBALS["project_with_auto_view"]) ? 1 : 0;
		
		//ROLLBACK TO ORIGINAL GLOBAL VARIABLES
		$PHPVariablesFileHandler->endUserGlobalVariables();
		
		return $project_with_auto_view;
	}
	
	private static function preparePresentationConfigBeanObjects($bean_objs, $options) {
		if (!empty($bean_objs["init.php"]))
			$bean_objs["init.php"]["properties"]["item_type"] = "reserved_file";
		else if (!empty($bean_objs["init"]))
			$bean_objs["init"]["properties"]["item_type"] = "reserved_file";
		
		if (!empty($bean_objs["pre_init_config.php"]))
			$bean_objs["pre_init_config.php"]["properties"]["item_type"] = "reserved_file";
		else if (!empty($bean_objs["pre_init_config"]))
			$bean_objs["pre_init_config"]["properties"]["item_type"] = "reserved_file";
		
		if (!empty($bean_objs["config.php"]))
			$bean_objs["config.php"]["properties"]["item_type"] = "reserved_file";
		else if (!empty($bean_objs["config"]))
			$bean_objs["config"]["properties"]["item_type"] = "reserved_file";
		
		if (!empty($bean_objs["cache"]))
			$bean_objs["cache"]["properties"]["item_type"] = "reserved_folder"; //cache is done from a diferent place
			
		//remove routers file from configs folder
		if ($options && 
			!empty($options["PresentationPRouter"]) && 
			is_a($options["PresentationPRouter"], "PresentationRouter") && 
			!empty($options["PresentationPRouter"]->settings["routers_file_name"]) && 
			!empty($bean_objs[ $options["PresentationPRouter"]->settings["routers_file_name"] ])
		)
			unset($bean_objs[ $options["PresentationPRouter"]->settings["routers_file_name"] ]);
		
		return $bean_objs;
	}
	
	private static function preparePresentationUtilBeanObjects($bean_objs, $options, $PresentationLayer) {
		$path_prefix = $PresentationLayer->getLayerPathSetting();
		$show_all = $options && !empty($options["all"]);
		
		if ($bean_objs)
			foreach ($bean_objs as $file_name => $bean_obj) {
				$props = isset($bean_obj["properties"]) ? $bean_obj["properties"] : null;
				
				if (isset($props["item_type"]) && $props["item_type"] == "util_file") {
					$file_path = $path_prefix . $props["path"];
					
					if (file_exists($file_path) && pathinfo($file_path, PATHINFO_EXTENSION) == "php") {
						$modules = array();
						$methods = PHPCodePrintingHandler::getPHPClassesFromFile($file_path);
						//echo "<pre>";print_r($methods);die();
						
						$functions = isset($methods[0]) ? $methods[0] : null;
						unset($methods[0]);
						
						if (!empty($methods)) {//with class inside of $file_path
							foreach ($methods as $class_path => $class_data) {
								$class_name = isset($class_data["name"]) ? $class_data["name"] : null;
								$comments = isset($class_data["doc_comments"]) && is_array($class_data["doc_comments"]) ? implode("\n", $class_data["doc_comments"]) : "";
								$is_class_hidden = strpos($comments, "@hidden") !== false;
								
								if (!$is_class_hidden || $show_all) {
									$properties = array("path" => $props["path"]);
									$class_methods = array();
									
									$cn = $class_name;
									
									//fix issue when exists multiple classes with the same name
									self::prepareListKeyIfAlreadyExists($cn, $modules);
									self::prepareListKeyIfAlreadyExists($cn, $bean_objs);
									
									if (!empty($class_data["methods"])) {
										$t = count($class_data["methods"]);
										
										for ($i = 0; $i < $t; $i++) {
											$method = $class_data["methods"][$i];
											$method_type = isset($method["type"]) ? $method["type"] : null;
											$is_public = $method_type == "public" || $method_type == "construct";
											
											if ($is_public || $show_all) {
												$comments = isset($method["doc_comments"]) && is_array($method["doc_comments"]) ? implode("\n", $method["doc_comments"]) : "";
												$is_hidden = strpos($comments, "@hidden") !== false;
												
												if (!$is_hidden || $show_all) {
													$method_name = isset($method["name"]) ? $method["name"] : null;
													
													$sub_properties = array(
														"path" => $props["path"], 
														"item_type" => "method", 
														//"item_id" => self::getItemId("$file_path/$class_path/$method_name"), //if exists a function with the same name than the class name, then we will overwrite it with "$file_path/$class_path". So we MUST use the "$file_path/$cn" instead!
														"item_id" => self::getItemId("$file_path/$cn/$method_name"), 
														"item_class" => !$is_public || $is_hidden ? "hidden" : "",
														"class" => $class_path
													);
													
													$class_methods[$method_name] = array("properties" => $sub_properties);
												}
											}
										}
									}
									
									//There are no aliases here! Aliases are only for the classes in business logic layers. Not in the presentation layers.
									
									$properties["item_type"] = "class";
									//$properties["item_id"] = self::getItemId("$file_path/$class_path"); //if exists a function with the same name than the class name, then we will overwrite it with "$file_path/$class_path". So we MUST use the "$file_path/$cn" instead!
									$properties["item_id"] = self::getItemId("$file_path/$cn");
									$properties["item_class"] = $is_class_hidden ? "hidden" : "";
									$properties["class"] = $class_path;
									
									$modules[$cn] = array_merge(array("properties" => $properties), $class_methods);
								}
							}
						}
						
						if (is_array($functions) && !empty($functions["methods"])) {//without class inside of $file_path
							$properties = array("path" => $props["path"], "item_type" => "function");
							
							$t = count($functions["methods"]);
							for ($i = 0; $i < $t; $i++) {
								$method = $functions["methods"][$i];
								$method_name = isset($method["name"]) ? $method["name"] : null;
								$comments = isset($method["doc_comments"]) && is_array($method["doc_comments"]) ? implode("\n", $method["doc_comments"]) : "";
								$is_hidden = strpos($comments, "@hidden") !== false;

								if (!$is_hidden || $show_all) {
									//There are no aliases here! Aliases are only for the methods in business logic layers. Not in the presentation layers.
									
									//fix issue when exists multiple functions with the same name
									self::prepareListKeyIfAlreadyExists($method_name, $modules);
									self::prepareListKeyIfAlreadyExists($method_name, $bean_objs);
									
									$properties["function"] = $method;
									$properties["item_id"] = self::getItemId("$file_path/$method_name");
									$properties["item_class"] = $is_hidden ? "hidden" : "";
									
									$modules[$method_name] = array("properties" => $properties);
								}
							}
						}
						
						if ($modules)
							$bean_objs[$file_name] = array_merge($bean_obj, $modules);
					}
				}
			}
		
		return $bean_objs;
	}
	
	private static function preparePresentationTemplateBeanObjects($bean_objs, $options) {
		if (!empty($bean_objs["region"]))
			$bean_objs["region"]["properties"]["item_type"] = "reserved_folder";
		
		if (!empty($bean_objs["module"]))
			$bean_objs["module"]["properties"]["item_type"] = "reserved_folder";
		
		return $bean_objs;
	}
	
	//only do this for the 1st level folders inside of the /src/template/ folder.
	private static function preparePresentationTemplateBeanObjectsForMainTemplateFolders($bean_objs, $options) {
		if ($bean_objs)
			foreach ($bean_objs as $name => $bean_obj) 
				if ($name != "properties" && isset($bean_obj["properties"]["item_type"]) && $bean_obj["properties"]["item_type"] == "folder" && substr(dirname($bean_obj["properties"]["path"]) . "/", strlen("/src/template/") * -1) == "/src/template/")
					$bean_objs[$name]["properties"]["item_type"] = "template_folder";
		
		return $bean_objs;
	}
	
	private static function preparePresentationWebrootBeanObjects($bean_objs, $options) {
		if (!empty($bean_objs["index.php"]))
			$bean_objs["index.php"]["properties"]["item_type"] = "reserved_file";
		else if (!empty($bean_objs["index"]))
			$bean_objs["index"]["properties"]["item_type"] = "reserved_file";
		
		if (!empty($bean_objs["script.php"]))
			$bean_objs["script.php"]["properties"]["item_type"] = "reserved_file";
		else if (!empty($bean_objs["script"]))
			$bean_objs["script"]["properties"]["item_type"] = "reserved_file";
		
		return $bean_objs;
	}
	
	private static function preparePresentationCommonWebrootBeanObjects($bean_objs, $options, $project_prefix, $common_project_name) {
		//if webroot path, this is, if first level
		if ($project_prefix == "$common_project_name/webroot/") {
			if (!empty($bean_objs["module"]))
				$bean_objs["module"]["properties"]["item_type"] = "cms_module";
			
			if (!empty($bean_objs["cms"]))
				$bean_objs["cms"]["properties"]["item_type"] = "cms_folder";
		}
		else if ($project_prefix == "$common_project_name/webroot/cms/") {
			if (!empty($bean_objs["wordpress"]))
				$bean_objs["wordpress"]["properties"]["item_type"] = "wordpress_folder";
		}
		else if ($project_prefix == "$common_project_name/webroot/cms/wordpress/") {
			foreach ($bean_objs as $bean_name => $bean_obj)
				$bean_objs[$bean_name]["properties"]["item_type"] = "wordpress_installation_folder";
		}
		
		return $bean_objs;
	}
	
	private static function preparePresentationModuleBeanObjects($bean_objs, $options, $PresentationLayer) {
		$path_prefix = $PresentationLayer->getLayerPathSetting();
		
		if ($bean_objs)
			foreach ($bean_objs as $file_name => $bean_obj) {
				$props = isset($bean_obj["properties"]) ? $bean_obj["properties"] : null;
				
				if (isset($props["item_type"]) && $props["item_type"] == "folder") {
					$module_path = $path_prefix . $props["path"];
					$module_impl_path = CMSModuleHandler::getCMSModuleHandlerImplFilePath($module_path);
					
					if (file_exists($module_impl_path)) {
						$is_enable = CMSModuleEnableHandler::isModuleEnabled($module_path);
						$bean_objs[$file_name]["properties"]["item_type"] = "module_folder";
						$bean_objs[$file_name]["properties"]["item_class"] = $is_enable ? "module_folder_enabled" : "module_folder_disabled";
					}
				}
			}
		
		return $bean_objs;
	}
	
	private static function preparePresentationCachePageBeanObjects($bean_objs, $options, $project_prefix, $PresentationCacheLayer) {
		if (!empty($PresentationCacheLayer->settings["presentations_cache_file_name"])) {
			if (empty($PresentationCacheLayer->settings["presentation_caches_path"]))
				launch_exception(new Exception("'PresentationCacheLayer->settings[presentation_caches_path]' cannot be undefined!"));
			
			$xml_path = $project_prefix . $PresentationCacheLayer->settings["presentation_caches_path"] . $PresentationCacheLayer->settings["presentations_cache_file_name"];
			
			$bean_objs["pages"] = array(
				"properties" => array(
					"path" => $xml_path,
					"item_type" => "cache_file", 
					"item_id" => self::getItemId($xml_path),
					"folder_type" => "",
				)
			);
			
			if (!empty($bean_objs[ $PresentationCacheLayer->settings["presentations_cache_file_name"] ]))
				unset($bean_objs[ $PresentationCacheLayer->settings["presentations_cache_file_name"] ]);
		}
		
		return $bean_objs;
	}
	
	private static function preparePresentationCacheModuleAndBlockBeanObjects($bean_objs, $options, $project_prefix) {
		if ($options && !empty($options["PresentationMultipleCMSCacheLayer"]) && is_a($options["PresentationMultipleCMSCacheLayer"], "MultipleCMSCacheLayer")) {
			//prepare caches - modules
			$module_settings = $options["PresentationMultipleCMSCacheLayer"]->getCMSModuleCacheLayer()->settings;
			
			if (!empty($module_settings["presentation_cms_module_caches_path"]) && !empty($module_settings["presentations_cms_module_cache_file_name"])) {
				$xml_path = $project_prefix . $module_settings["presentation_cms_module_caches_path"] . $module_settings["presentations_cms_module_cache_file_name"];
				
				$bean_objs["modules"] = array(
					"properties" => array(
						"path" => $xml_path,
						"item_type" => "cache_file", 
						"item_id" => self::getItemId($xml_path),
						"folder_type" => "",
					)
				);
				
				if (!empty($bean_objs[ $module_settings["presentations_cms_module_cache_file_name"] ]))
					unset($bean_objs[ $module_settings["presentations_cms_module_cache_file_name"] ]);
			}
			
			//prepare caches - blocks
			$block_settings = $options["PresentationMultipleCMSCacheLayer"]->getCMSBlockCacheLayer()->settings;
			
			if (!empty($block_settings["presentation_cms_block_caches_path"]) && !empty($block_settings["presentations_cms_block_cache_file_name"])) {
				$xml_path = $project_prefix . $block_settings["presentation_cms_block_caches_path"] . $block_settings["presentations_cms_block_cache_file_name"];
				
				$bean_objs["blocks"] = array(
					"properties" => array(
						"path" => $xml_path,
						"item_type" => "cache_file", 
						"item_id" => self::getItemId($xml_path),
						"folder_type" => "",
					)
				);
				
				if (!empty($bean_objs[ $block_settings["presentations_cms_block_cache_file_name"] ]))
					unset($bean_objs[ $block_settings["presentations_cms_block_cache_file_name"] ]);
			}
		}
		
		return $bean_objs;
	}
	
	private static function preparePresentationDispatcherBeanObjects($bean_objs, $options, $project_prefix) {
		//prepare caches - dispatchers
		if (
			!empty($options["PresentationPDispatcherCacheHandler"]) && 
			is_a($options["PresentationPDispatcherCacheHandler"], "DispatcherCacheHandler") && 
			!empty($options["PresentationPDispatcherCacheHandler"]->settings["dispatcher_caches_path"]) && 
			!empty($options["PresentationPDispatcherCacheHandler"]->settings["dispatchers_cache_file_name"])
		) {
			$xml_path = $project_prefix . $options["PresentationPDispatcherCacheHandler"]->settings["dispatcher_caches_path"] . $options["PresentationPDispatcherCacheHandler"]->settings["dispatchers_cache_file_name"];
			
			$bean_objs["dispatcher"] = array(
				"properties" => array(
					"path" => $xml_path,
					"item_type" => "cache_file", 
					"item_id" => self::getItemId($xml_path),
					"folder_type" => "",
				)
			);
			
			if (!empty($bean_objs[ $options["PresentationPDispatcherCacheHandler"]->settings["dispatchers_cache_file_name"] ]))
				unset($bean_objs[ $options["PresentationPDispatcherCacheHandler"]->settings["dispatchers_cache_file_name"] ]);
		}
		
		return $bean_objs;
	}
	
	private static function preparePresentationRouterBeanObjects($bean_objs, $options, $project_prefix) {
		//prepare routers
		if (
			!empty($options["PresentationPRouter"]) && 
			is_a($options["PresentationPRouter"], "PresentationRouter") && 
			!empty($options["PresentationPRouter"]->settings["routers_path"]) && 
			!empty($options["PresentationPRouter"]->settings["routers_file_name"])
		) {
			$xml_path = $project_prefix . $options["PresentationPRouter"]->settings["routers_path"] . $options["PresentationPRouter"]->settings["routers_file_name"];
			
			$bean_objs["properties"] = array(
				"path" => $xml_path,
				"item_type" => "cache_file", 
				"item_id" => self::getItemId($xml_path),
				"folder_type" => "",
			);
		}
		
		return $bean_objs;
	}
	
	private static function getSubFiles($PresentationLayer, $path, $recursive_level = -1, $folder_type = "", $options = null) {
		$path_prefix = $PresentationLayer->getLayerPathSetting();
		
		$absolute_path = $path_prefix . $path;
		$rl = $recursive_level > 0 ? $recursive_level - 1 : $recursive_level;
		$show_hidden_files = $options && !empty($options["hidden"]);
		
		//prepare laravel
		$vendor_framework = VendorFrameworkHandler::getVendorFrameworkFolder($absolute_path);
		
		if ($vendor_framework || !empty($options["vendor_framework"]))
			$show_hidden_files = true;
		
		//prepare sub files
		$sub_files = array();
		
		if ( ($recursive_level > 0 || $recursive_level == -1) && is_dir($absolute_path) && ($dir = opendir($absolute_path)) ) {
			while( ($file = readdir($dir)) !== false) {
				if ($file != "." && $file != ".." && (substr($file, 0, 1) != "." || $show_hidden_files)) {
					$file_path = $absolute_path . $file;
					
					if (is_dir($file_path))
						$item_type = "folder";
					else {
						$extension = strtolower( pathinfo($file_path, PATHINFO_EXTENSION) );
						
						if (!$folder_type) {
							/*if ($extension == "php")
								continue 1;
							else */if ($extension == "php")
								$item_type = "file";
							else if ($extension == "css")
								$item_type = "css_file";
							else if ($extension == "js")
								$item_type = "js_file";
							else if ($extension == "zip")
								$item_type = "zip_file";
							else if (function_exists("exif_imagetype") && exif_imagetype($file_path)) 
								$item_type = "img_file";
							else 
								$item_type = "undefined_file";
						}
						else {
							if ($extension != "php" && ($folder_type == "entity" || $folder_type == "view" || $folder_type == "template" || $folder_type == "util" || $folder_type == "controller" || $folder_type == "config" || $folder_type == "module" || $folder_type == "block")) 
								$item_type = $extension == "zip" ? "zip_file" : "undefined_file";
							else
								$item_type = strtolower($folder_type) . "_file";
						}
					}
					
					$properties = array(
						"item_type" => $item_type,
						"item_id" => self::getItemId($file_path),
						"folder_type" => $folder_type,
						"item_menu" => self::getFileItemMenu($file_path),
					);
					
					if (is_dir($file_path)) {
						$properties["path"] = $path . $file . "/";
						$file_key = $file;
						
						//prepare laravel sub folder
						$sub_vendor_framework = VendorFrameworkHandler::getVendorFrameworkFolder($file_path);
						
						if ($sub_vendor_framework) {
							$properties["item_class"] = $sub_vendor_framework;
							$properties["vendor_framework"] = $sub_vendor_framework;
						}
						
						if ($show_hidden_files || $sub_vendor_framework) {
							$options = $options ? $options : array();
							$options["hidden"] = true;
						}
						
						//fix issue when exists multiple files with the same name
						self::prepareListKeyIfAlreadyExists($file_key, $sub_files);
						
						if ($recursive_level > 0 || $recursive_level == -1) {
							$aux = self::getSubFiles($PresentationLayer, $properties["path"], $rl, $folder_type, $options);
							$sub_files[$file_key] = array_merge(array("properties" => $properties), $aux);
						}
						else {
							$sub_files[$file_key] = array("properties" => $properties);
						}
					}
					else {
						$path_info = pathinfo($file);
						$file_key = isset($path_info["extension"]) && strtolower($path_info["extension"]) == "php" ? $path_info["filename"] : $file;
						
						//fix issue when exists multiple files with the same name
						self::prepareFileKeyIfAlreadyExists($file_key, $sub_files, $absolute_path . $file_key);
						
						$properties["path"] = $path . $file;
						$sub_files[$file_key] = array("properties" => $properties);
					}
				}
			}
			closedir($dir);
		}
		
		ksort($sub_files);
		
		return $sub_files;
	}
	
	private static function prepareFileKeyIfAlreadyExists(&$file_key, $files_list, $file_path = null) {
		if ($file_path && is_dir($file_path))
			$file_key .= " ";
		
		self::prepareListKeyIfAlreadyExists($file_key, $files_list);
	}
	
	private static function prepareListKeyIfAlreadyExists(&$file_key, $files_list) {
		$file_key .= $file_key == "properties" || $file_key == "aliases" ? " " : "";
		
		//fix issue when exists multiple files with the same name
		while (!empty($files_list[$file_key]))
			$file_key .= " ";
		
		//echo "!$file_key!\n";
	}
	
	private static function getFileItemMenu($file_path) {
		$menu = array(
			"file_name" => pathinfo($file_path, PATHINFO_BASENAME),
			"modified_date" => date("Y-m-d H:i:s", filemtime($file_path))
		);
		
		$file_owner = FilePermissionHandler::getFileUserOwnerInfo($file_path);
		
		if ($file_owner)
			$menu["user_owner"] = isset($file_owner["name"]) ? $file_owner["name"] : null;
		
		$file_group = FilePermissionHandler::getFileUserGroupInfo($file_path);
		
		if ($file_group)
			$menu["user_group"] = isset($file_group["name"]) ? $file_group["name"] : null;
		
		$menu["permissions"] = FilePermissionHandler::getFilePermissionsInfo($file_path);
		
		return $menu;
	}
	
	private static function prepareAliases($Layer, &$aliases) {
		$path_prefix = $Layer->getLayerPathSetting();
		
		$new_aliases = array();
		
		foreach ($aliases as $file_path => $node) {
			foreach ($node as $key => $sub_node) {
				if (is_array($sub_node)) {//SERVICES
					foreach ($sub_node as $sub_key => $service) {
						if (is_array($service)) {//METHODS
							$t = count($service);
							for ($i = 0; $i < $t; $i++) {
								$sa = $service[$i];
								
								$new_aliases[$sa] = array("properties" => array(
									"path" => self::getRelativePath($file_path, $path_prefix), 
									"class" => $key, 
									"function" => $sub_key, 
									"item_id" => self::getItemId("$file_path/method/$sa")
								));
							}
						}
						else if (is_numeric($sub_key)) {//CLASS	
							$new_aliases[$service] = array("properties" => array(
								"path" => self::getRelativePath($file_path, $path_prefix), 
								"class" => $key, 
								"item_id" => self::getItemId("$file_path/class/$service")
							));
						}
					}
				}
				else if (is_numeric($key)) {//MODULES
					$new_aliases[$sub_node] = array("properties" => array(
						"path" => $file_path, //this cannot have the self::getRelativePath function because is already the relative path
						"item_id" => self::getItemId("$file_path/module/$sub_node")
					));
				}
			}
		}
		
		$aliases = $new_aliases;
		ksort($aliases);
		
		return $new_aliases;
	}
	
	public static function getItemId($str) {
		return strtolower(preg_replace('/[^\w]+/u', 'a', base64_encode(hash("crc32b", $str)))); //'\w' means all words with '_' and '/u' means with accents and ç too. '/u' converts unicode to accents chars.
	}
	
	private static function getRelativePath($path, $path_prefix) {
		return substr($path, strlen($path_prefix));
	}
	
	private static function getXmlFileContentArray($file_path) {
		if (file_exists($file_path)) {
			$xml_content = file_get_contents($file_path);
			
			$xml_content = PHPScriptHandler::parseContent($xml_content);
			$MyXML = new MyXML($xml_content);
			$arr = $MyXML->toArray(array("lower_case_keys" => true));
			
			return $arr;
		}
		return null;
	}
}
?>
