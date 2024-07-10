<?php
include_once get_lib("org.phpframework.object.ObjTypeHandler");
include_once get_lib("org.phpframework.phpscript.docblock.DocBlockParser");
include_once $EVC->getUtilPath("WorkFlowBeansFileHandler");

$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "access");

$bean_name = $_GET["bean_name"];
$bean_file_name = $_GET["bean_file_name"];
$path = $_GET["path"];
$service_id = $_GET["service"];

$path = str_replace("../", "", $path);//for security reasons

$PHPVariablesFileHandler = new PHPVariablesFileHandler($user_global_variables_file_path);
$PHPVariablesFileHandler->startUserGlobalVariables();

$WorkFlowBeansFileHandler = new WorkFlowBeansFileHandler($user_beans_folder_path . $bean_file_name, $user_global_variables_file_path);
$obj = $WorkFlowBeansFileHandler->getBeanObject($bean_name);

if ($obj && is_a($obj, "BusinessLogicLayer")) {
	$layer_path = $obj->getLayerPathSetting();
	$file_path = $layer_path . $path;
	
	if ($path && file_exists($file_path)) {
		$bean_objs = $obj->getPHPFrameWork()->getObjects();
		$vars = is_array($bean_objs["vars"]) ? array_merge($bean_objs["vars"], $obj->settings) : $obj->settings;
		$vars["current_business_logic_module_path"] = $file_path;
		$vars["current_business_logic_module_id"] = substr($path, 0, strlen($path) - 4);//remove ".php"
		//$vars["current_business_logic_module_id"] = str_replace("/", ".", $vars["current_business_logic_module_id"]); //2021-01-17 JP: Or it could be this code. It doesn't really matter. Even if there are folders with "." in the names, the system detects it. The module_id with "/" is faster before cache happens, but after the first call for this module, it doesn't really matter anymore bc the module_path is cached with the correspondent module_id.
		
		include_once $file_path; //We must include file bc the DocBlockParser uses the php loaded classes
		
		$DocBlockParser = new DocBlockParser();
		
		if (($pos = strpos($service_id, ".")) !== false) {
			$class_name = substr($service_id, 0, $pos);
			$method_name = substr($service_id, $pos + 1);
			
			//prepare right class name with namespace
			$classes = PHPCodePrintingHandler::getPHPClassesFromFile($file_path);
			$found_class_data = PHPCodePrintingHandler::searchClassFromPHPClasses($classes, $class_name);
			
			if ($found_class_data) {
				$found_class_name = PHPCodePrintingHandler::prepareClassNameWithNameSpace($found_class_data["name"], $found_class_data["namespace"]);
				$class_name = $found_class_name;
				//echo $found_class_name;print_r($found_class_data);
			}
			
			$DocBlockParser->ofMethod($class_name, $method_name);
		}
		else
			$DocBlockParser->ofFunction($service_id);
		
		$params = $DocBlockParser->getTagParams();
		
		$numeric_types = array_merge(ObjTypeHandler::getPHPNumericTypes(), ObjTypeHandler::getDBNumericTypes());
		
		$props = array();
		$t = count($params);
		for ($i = 0; $i < $t; $i++) {
			$param = $params[$i];
			$args = $param->getArgs();
			
			$name = !empty($args["name"]) ? $args["name"] : (isset($args["index"]) ? $args["index"] : $i);
			$type = $args["type"];
			
			if ($name) {
				if (strpos($name, "[") !== false) {
					preg_match_all("/^([^\[]*)\[([^\[]*)\]/u", $name, $matches, PREG_PATTERN_ORDER); //'/u' means converts to unicode.
					
					if ($matches[0]) 
						$name = $matches[2][0];
				}
				
				if ($name && !isset($props[$name]))
					$props[$name] = $type && !in_array($type, $numeric_types) ? "string" : "";
			}
		}
	}
}

$PHPVariablesFileHandler->endUserGlobalVariables();
?>
