<?php
include_once get_lib("org.phpframework.object.ObjTypeHandler");
include_once get_lib("org.phpframework.phpscript.docblock.DocBlockParser");
include_once $EVC->getUtilPath("WorkFlowBeansFileHandler");

$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "access");

$bean_name = isset($_GET["bean_name"]) ? $_GET["bean_name"] : null;
$bean_file_name = isset($_GET["bean_file_name"]) ? $_GET["bean_file_name"] : null;
$path = isset($_GET["path"]) ? $_GET["path"] : null;
$service_id = isset($_GET["service"]) ? $_GET["service"] : null;

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
		$vars = isset($bean_objs["vars"]) && is_array($bean_objs["vars"]) ? array_merge($bean_objs["vars"], $obj->settings) : $obj->settings;
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
				$found_class_name = isset($found_class_data["name"]) ? $found_class_data["name"] : null;
				$found_class_name = PHPCodePrintingHandler::prepareClassNameWithNameSpace($found_class_name, isset($found_class_data["namespace"]) ? $found_class_data["namespace"] : null);
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
			$type = isset($args["type"]) ? $args["type"] : null;
			
			if ($name) {
				$sub_names = null;
				
				if (strpos($name, "[") !== false) {
					preg_match_all("/^([^\[]*)((\[[^\[]*\])+)/u", $name, $matches, PREG_PATTERN_ORDER); //'/u' means converts to unicode.
					
					if (!empty($matches[0])) {
						$names = preg_replace("/(^\[|\]$)/", "", $matches[2][0]);
						$parts = explode("][", $names);
						$name = array_shift($parts);
						$sub_names = $parts;
						//print_r($matches);
					}
				}
				
				if ($name) {
					$prop_type = $type && !in_array($type, $numeric_types) ? "string" : "";
					
					if (!isset($props[$name]))
						$props[$name] = $prop_type;
					
					if ($sub_names) {
						$prop_obj = $prop_type;
						
						for ($j = count($sub_names) - 1; $j >= 0; $j--) {
							$sub_name = $sub_names[$j];
							
							$prop_obj = array($sub_name => $prop_obj);
						}
						
						if (is_array($props[$name]))
							$props[$name] = array_merge($props[$name], $prop_obj);
						else
							$props[$name] = $prop_obj;
					}
				}
			}
		}
	}
}

$PHPVariablesFileHandler->endUserGlobalVariables();
?>
