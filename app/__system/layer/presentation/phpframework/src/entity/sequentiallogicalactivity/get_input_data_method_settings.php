<?php
include_once get_lib("org.phpframework.workflow.WorkFlowTaskHandler");
include_once get_lib("org.phpframework.workflow.WorkFlowTaskCodeParser");

$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "access");

$method = $_POST["method"];

if ($method) {
	$code = "<?php " . htmlspecialchars_decode($method) . " ?>";

	$allowed_tasks = array("callbusinesslogic", "callibatisquery", "callhibernatemethod", "getquerydata", "setquerydata", "callhibernateobject", "callfunction", "callobjectmethod", "restconnector", "soapconnector");
	$WorkFlowTaskHandler = new WorkFlowTaskHandler($webroot_cache_folder_path, $webroot_cache_folder_url);
	$WorkFlowTaskHandler->setCacheRootPath(LAYER_CACHE_PATH);
	$WorkFlowTaskHandler->setAllowedTaskTags($allowed_tasks);
	$WorkFlowTaskHandler->initWorkFlowTasks();
	
	$WorkFlowTaskCodeParser = new WorkFlowTaskCodeParser($WorkFlowTaskHandler);
	$arr = $WorkFlowTaskCodeParser->getParsedCodeAsArray($code);
	$arr = $arr["task"][0]["childs"];
	$tag = $arr["tag"][0]["value"];
	
	if (in_array($tag, $allowed_tasks)) {
		$properties = $arr["properties"][0]["childs"];
		$properties = MyXML::complexArrayToBasicArray($properties, array("lower_case_keys" => true));
		
		foreach ($properties as $k => $v) {
			if (is_array($v)) {
				$is_assoc = array_keys($v) !== range(0, count($v) - 1);
				
				if ($is_assoc) {
					$properties[$k] = array($v);
				}
			}
		}
	}
}

$obj = array("brokers" => $properties, "brokers_layer_type" => $tag);
?>
