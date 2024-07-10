<?php
include_once $EVC->getUtilPath("WorkFlowBeansFileHandler");
include_once get_lib("org.phpframework.phpscript.PHPCodePrintingHandler");

$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "access");

$bean_name = $_GET["bean_name"];
$bean_file_name = $_GET["bean_file_name"];
$path = $_GET["path"];
$type = $_GET["type"];
$class_name = $_GET["class_name"];
$method_id = $_GET["method"];
$function_id = $_GET["function"];

$path = str_replace("../", "", $path);//for security reasons

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
			$vars = is_array($bean_objs["vars"]) ? array_merge($bean_objs["vars"], $obj->settings) : $obj->settings;
			$vars["current_business_logic_module_path"] = $file_path;
			$vars["current_business_logic_module_id"] = substr($path, 0, strlen($path) - 4);//remove ".php"
			//$vars["current_business_logic_module_id"] = str_replace("/", ".", $vars["current_business_logic_module_id"]); //2021-01-17 JP: Or it could be this code. It doesn't really matter. Even if there are folders with "." in the names, the system detects it. The module_id with "/" is faster before cache happens, but after the first call for this module, it doesn't really matter anymore bc the module_path is cached with the correspondent module_id.
		}
	}
}

$PHPVariablesFileHandler = new PHPVariablesFileHandler(array($user_global_variables_file_path, $pre_init_config_file_path));
$PHPVariablesFileHandler->startUserGlobalVariables();

if ($path && $file_path && file_exists($file_path)) {
	//in case the $file_path has another include inside, like: $EVC->getModulePath("user/UserUtil");
	if ($PEVC) {
		$OLD_EVC = $EVC;
		$EVC = $PEVC;
	}
	
	switch ($type) {
		case "properties":
			//Note: Do not use ReflectionFunction bc if the class name inside of a file has a namespace, the ReflectionFunction will not work and launch an exception. Otherwise if the file has any php error, the ReflectionFunction will break too.
			$class_props = PHPCodePrintingHandler::getClassFromFile($file_path, $class_name);
			
			if (!$class_props) {
				$classes = PHPCodePrintingHandler::getPHPClassesFromFile($file_path);
				$class_props = PHPCodePrintingHandler::searchClassFromPHPClasses($classes, $class_name);
			}
			
			$props = array();
			
			if ($class_props) {
				$cn = PHPCodePrintingHandler::prepareClassNameWithNameSpace($class_props["name"], $class_props["namespace"]);
				$class_properties = PHPCodePrintingHandler::getClassPropertiesFromFile($file_path, $cn);
				
				if ($class_properties) 
					foreach ($class_properties as $prop) 
						if ($prop["type"] == "public") {
							$comments = is_array($prop["doc_comments"]) ? implode("", $prop["doc_comments"]) : $prop["doc_comments"];
							$is_hidden = !empty($comments) && strpos($comments, "@hidden") !== false;
								
							$props[] = array(
								"name" => $prop["name"],
								"static" => $prop["static"] ? 1 : 0,
								"hidden" => $is_hidden ? 1 : 0,
							);
						}
			}
			break;
		case "methods":
			//Note: Do not use ReflectionClass bc if the class name inside of a file has a namespace, the ReflectionClass will not work and launch an exception. Otherwise if the file has any php error, the ReflectionClass will break too.
			$class_props = PHPCodePrintingHandler::getClassFromFile($file_path, $class_name);
			
			if (!$class_props) {
				$classes = PHPCodePrintingHandler::getPHPClassesFromFile($file_path);
				$class_props = PHPCodePrintingHandler::searchClassFromPHPClasses($classes, $class_name);
			}
			
			$props = array();
			
			if ($class_props && $class_props["methods"]) 
				foreach ($class_props["methods"] as $method) 
					if ($method["name"] != "__construct" && $method["type"] == "public") {
						$comments = is_array($method["doc_comments"]) ? implode("", $method["doc_comments"]) : $method["doc_comments"];
						$is_hidden = !empty($comments) && strpos($comments, "@hidden") !== false;
						
						$prop = array(
							"name" => $method["name"],
							"static" => $method["static"] ? 1 : 0,
							"hidden" => $is_hidden ? 1 : 0,
						);
						
						if ($bean_name == "test_unit")
							$prop["enabled"] = !empty($comments) && strpos($comments, "@enabled") !== false;
						
						$props[] = $prop;
					}
			
			break;
		case "functions":
			//Note: Do not use ReflectionFunction bc if the class name inside of a file has a namespace, the ReflectionFunction will not work and launch an exception. Otherwise if the file has any php error, the ReflectionFunction will break too.
			$functions = PHPCodePrintingHandler::getPHPClassesFromFile($file_path);
			$functions = is_array($functions[0]["methods"]) ? $functions[0]["methods"] : array();
			
			$props = array();
			foreach ($functions as $func) {
				$comments = is_array($func["doc_comments"]) ? implode("", $func["doc_comments"]) : $func["doc_comments"];
				$is_hidden = !empty($comments) && strpos($comments, "@hidden") !== false;
				
				$props[] = array(
					"name" => $func["name"],
					"hidden" => $is_hidden ? 1 : 0,
				);
			}
			break;
		case "arguments":
			//Note: Do not use ReflectionMethod or ReflectionFunction bc if the class name inside of a file has a namespace, the ReflectionMethod or ReflectionFunction will not work and launch an exception. Otherwise if the file has any php error, the ReflectionMethod or ReflectionFunction will break too.
			$found_func = null;
			
			if ($class_name && $method_id) {
				$class_props = PHPCodePrintingHandler::getClassFromFile($file_path, $class_name);
				
				if (!$class_props) {
					$classes = PHPCodePrintingHandler::getPHPClassesFromFile($file_path);
					$class_props = PHPCodePrintingHandler::searchClassFromPHPClasses($classes, $class_name);
				}
				
				if ($class_props && $class_props["methods"])
					foreach ($class_props["methods"] as $method) 
						if ($method["name"] == $method_id) {
							$found_func = $method;
							break;
						}
			}
			else if ($function_id) {
				$functions = PHPCodePrintingHandler::getPHPClassesFromFile($file_path);
				$functions = is_array($functions[0]["methods"]) ? $functions[0]["methods"] : array();
				
				if ($functions)
					foreach ($functions as $func) 
						if ($func["name"] == $function_id) {
							$found_func = $func;
							break;
						}
			}
			
			$props = array();
			
			if ($found_func && $found_func["arguments"]) {
				foreach ($found_func["arguments"] as $key => $value) {
					$item = array(
						"name" => $key[0] == '$' ? substr($key, 1) : $key, //it should always start with $, but just in case...
					);
					
					if (strlen($value)) {
						$v = trim($v);
						
						if ($value == "null")
							$type = "";
						else if (preg_match("/^array\s*(/i", $value)) {
							$type = "array";
							eval("\$x = $value;");
							$value = json_encode($x); //TODO: Fix this error: if the value contains any reference to any constant, it will convert it to a string instead of a object. That's why we should use the Reflection methods like the getDefaultValue
						}
						else if (is_numeric($value))
							$type = "numeric";
						else if (preg_match("/^(true|false)$/i", $value))
							$type = "boolean";
						else
							$type = substr($v, 0, 1) == "$" && strpos($v, " ") === false ? "variable" : (!isset($value) ? "" : "string");
						
					}
				}
			}
			break;
	}
	
	if ($PEVC)
		$EVC = $OLD_EVC;
}

$PHPVariablesFileHandler->endUserGlobalVariables();
?>
