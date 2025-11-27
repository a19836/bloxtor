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

include_once $EVC->getUtilPath("WorkFlowBeansFileHandler");
include_once get_lib("org.phpframework.phpscript.PHPCodePrintingHandler");

$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "access");

$bean_name = isset($_GET["bean_name"]) ? $_GET["bean_name"] : null;
$bean_file_name = isset($_GET["bean_file_name"]) ? $_GET["bean_file_name"] : null;
$path = isset($_GET["path"]) ? $_GET["path"] : null;
$type = isset($_GET["type"]) ? $_GET["type"] : null;
$class_name = isset($_GET["class_name"]) ? $_GET["class_name"] : null;
$method_id = isset($_GET["method"]) ? $_GET["method"] : null;
$function_id = isset($_GET["function"]) ? $_GET["function"] : null;

$path = str_replace("../", "", $path);//for security reasons
$pre_init_config_file_path = null;

if ($bean_name == "dao") {
	$UserAuthenticationHandler->checkInnerFilePermissionAuthentication("vendor/dao/$path", "layer", "access");
	
	$file_path = DAO_PATH . $path;
}
else if ($bean_name == "lib") 
	$file_path = LIB_PATH . $path;
else if ($bean_name == "vendor") {
	$UserAuthenticationHandler->checkInnerFilePermissionAuthentication("vendor/$path", "layer", "access");
		
	$file_path = VENDOR_PATH . $path;
}
else if ($bean_name == "test_unit") {
	$UserAuthenticationHandler->checkInnerFilePermissionAuthentication("vendor/testunit/$path", "layer", "access");
		
	$file_path = TEST_UNIT_PATH . $path;
}
else {
	$WorkFlowBeansFileHandler = new WorkFlowBeansFileHandler($user_beans_folder_path . $bean_file_name, $user_global_variables_file_path);
	$obj = $WorkFlowBeansFileHandler->getBeanObject($bean_name);
	
	if (is_a($obj, "BusinessLogicLayer"))
		$item_type = "business_logic";
	else {
		$item_type = "presentation";
		$PEVC = $WorkFlowBeansFileHandler->getEVCBeanObject($bean_name, $path);
		
		if ($PEVC) {
			$obj = $PEVC->getPresentationLayer();
			$pre_init_config_file_path = $PEVC->getConfigPath("pre_init_config");
		}
		else
			$obj = null;
	}
	
	if ($obj) {
		$layer_path = $obj->getLayerPathSetting();
		
		$UserAuthenticationHandler->checkInnerFilePermissionAuthentication($layer_path . $path, "layer", "access");
		
		$file_path = $layer_path . $path;
		
		if ($item_type == "business_logic") {
			$bean_objs = $obj->getPHPFrameWork()->getObjects();
			$vars = isset($bean_objs["vars"]) && is_array($bean_objs["vars"]) ? array_merge($bean_objs["vars"], $obj->settings) : $obj->settings;
			$vars["current_business_logic_module_path"] = $file_path;
			$vars["current_business_logic_module_id"] = substr($path, 0, strlen($path) - 4);//remove ".php"
			//$vars["current_business_logic_module_id"] = str_replace("/", ".", $vars["current_business_logic_module_id"]); //2021-01-17 JP: Or it could be this code. It doesn't really matter. Even if there are folders with "." in the names, the system detects it. The module_id with "/" is faster before cache happens, but after the first call for this module, it doesn't really matter anymore bc the module_path is cached with the correspondent module_id.
		}
	}
}

$PHPVariablesFileHandler = new PHPVariablesFileHandler(array($user_global_variables_file_path, $pre_init_config_file_path));
$PHPVariablesFileHandler->startUserGlobalVariables();

if ($path && !empty($file_path) && file_exists($file_path)) {
	//in case the $file_path has another include inside, like: $EVC->getModulePath("user/UserUtil");
	if (!empty($PEVC)) {
		$OLD_EVC = $EVC;
		$EVC = $PEVC;
	}
	
	switch ($type) {
		case "properties":
			include_once $file_path;
			
			$props = array();
			$class_name = PHPCodePrintingHandler::getClassPathFromClassName($file_path, $class_name);
			
			if ($class_name) {
				$reflect = new ReflectionClass($class_name);
				$publics = $reflect->getProperties(ReflectionProperty::IS_PUBLIC);
				
				foreach ($publics as $prop) {
					$comments = $prop->getDocComment();
					$is_hidden = !empty($comments) && strpos($comments, "@hidden") !== false;
						
					$props[] = array(
						"name" => $prop->getName(),
						"static" => $prop->isStatic() ? 1 : 0,
						"hidden" => $is_hidden ? 1 : 0,
					);
				}
			}
			break;
		case "methods": 
			include_once $file_path;
	
			$props = array();
			$class_name = PHPCodePrintingHandler::getClassPathFromClassName($file_path, $class_name);
			
			if ($class_name) {
				$reflect = new ReflectionClass($class_name);
				$publics = $reflect->getMethods(ReflectionProperty::IS_PUBLIC);
				
				foreach ($publics as $prop) {
					$name = $prop->getName();
					
					if ($name != "__construct") {
						$comments = $prop->getDocComment();
						$is_hidden = !empty($comments) && strpos($comments, "@hidden") !== false;
						
						$prop = array(
							"name" => $name,
							"static" => $prop->isStatic() ? 1 : 0,
							"hidden" => $is_hidden ? 1 : 0,
						);
						
						if ($bean_name == "test_unit")
							$prop["enabled"] = !empty($comments) && strpos($comments, "@enabled") !== false;
						
						$props[] = $prop;
					}
				}
			}
			break;
		case "functions":
			include_once $file_path;
			
			$functions = PHPCodePrintingHandler::getPHPClassesFromFile($file_path);
			$functions = isset($functions[0]["methods"]) && is_array($functions[0]["methods"]) ? $functions[0]["methods"] : array();
			
			$props = array();
			foreach ($functions as $func) {
				$name = isset($func["name"]) ? $func["name"] : null;
				
				$reflect = new ReflectionFunction($name);
				$comments = $reflect->getDocComment();
				$is_hidden = !empty($comments) && strpos($comments, "@hidden") !== false;
				
				$props[] = array(
					"name" => $name,
					"hidden" => $is_hidden ? 1 : 0,
				);
			}
			break;
		case "arguments":
			include_once $file_path;
			
			$params = null;
			if ($class_name && $method_id) {
				$class_name = PHPCodePrintingHandler::getClassPathFromClassName($file_path, $class_name);
				
				if ($class_name) {
					$method = new ReflectionMethod($class_name, $method_id);
					$params = $method->getParameters();
				}
			}
			else if ($function_id) {
				$function = new ReflectionFunction($function_id);
				$params = $function->getParameters();
			}
			
			$props = array();
			
			if ($params) {
				$docbook_params = null;
				
				//get params from docbook if file is from lib. This is very usefull bc if the lib is encrypted the params name will be encrypted too, so we must return the original name.
				if ($bean_name == "lib") {
					$SYSTEM_EVC = !empty($OLD_EVC) ? $OLD_EVC : $EVC;
					$docbook_file_path = $SYSTEM_EVC->getEntitiesPath() . "docbook/files" . substr($file_path, strlen( dirname(LIB_PATH) )) . ".ser";
					
					if (file_exists($docbook_file_path)) {
						$file_properties = unserialize(file_get_contents($docbook_file_path));
						
						if ($file_properties) {
							if ($class_name && $method_id)
								$docbook_params = isset($file_properties[$class_name]["methods"][$method_id]["arguments"]) ? $file_properties[$class_name]["methods"][$method_id]["arguments"] : null;
							else
								$docbook_params = isset($file_properties[0]["methods"][$function_id]["arguments"]) ? $file_properties[0]["methods"][$function_id]["arguments"] : null;
							
							if ($docbook_params)
								$docbook_params = array_keys($docbook_params);
						}
					}
				}
				
				foreach ($params as $i => $param) {
					$item = array(
						"name" => $param->getName(),
					);
					
					//if the lib is encrypted the param name will be encrypted too, so we must return the original name.
					if ($docbook_params && !empty($docbook_params[$i]))
						$item["name"] = substr($docbook_params[$i], 0, 1) == '$' ? substr($docbook_params[$i], 1) : $docbook_params[$i];
					
					if ($param->isDefaultValueAvailable()) {
						$value = $param->getDefaultValue();
						
						if (!$param->isDefaultValueAvailable()) {
							$type = "";
							$value = "null";
						}
						else if ($param->isArray() || is_array($value)) {
							$type = "array";
							$value = json_encode($value);
						}
						else if (is_numeric($value)) {
							$type = "numeric";
							$value = "$value";
						}
						else if (is_bool($value)) {
							$type = "boolean";
							$value = $value ? "true" : "false";
						}
						else {
							$type = (substr($value, 0, 1) == '$' || substr($value, 0, 2) == '@$') && strpos($value, " ") === false ? "variable" : (!isset($value) ? "" : "string");
						}
						
						$item["value"] = $value;
						$item["type"] = $type;
					}
					
					$props[] = $item;
				}
			}
			break;
	}
	
	if (!empty($PEVC))
		$EVC = $OLD_EVC;
}

$PHPVariablesFileHandler->endUserGlobalVariables();
?>
