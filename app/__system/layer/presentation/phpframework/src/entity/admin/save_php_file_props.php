<?php
include_once get_lib("org.phpframework.util.MyArray");
include_once $EVC->getUtilPath("WorkFlowBeansFileHandler");
include_once $EVC->getUtilPath("WorkFlowPHPFileHandler");

$bean_name = isset($_GET["bean_name"]) ? $_GET["bean_name"] : null;
$bean_file_name = isset($_GET["bean_file_name"]) ? $_GET["bean_file_name"] : null;
$path = isset($_GET["path"]) ? $_GET["path"] : null;
$item_type = isset($_GET["item_type"]) ? $_GET["item_type"] : null;
$class_id = isset($_GET["class"]) ? $_GET["class"] : null;
$method_id = isset($_GET["method"]) ? $_GET["method"] : null;
$function_id = isset($_GET["function"]) ? $_GET["function"] : null;
$remove_file_if_no_class = isset($_GET["remove_file_if_no_class"]) ? $_GET["remove_file_if_no_class"] : null; //is set in the entity/businesslogic/remove_service.php
$rename_file_with_class = isset($_GET["rename_file_with_class"]) ? $_GET["rename_file_with_class"] : null; //is set in the view/businesslogic/edit_service.php
$file_modified_time = isset($_GET["file_modified_time"]) ? $_GET["file_modified_time"] : null;

$path = str_replace("../", "", $path);//for security reasons

$WorkFlowBeansFileHandler = new WorkFlowBeansFileHandler($user_beans_folder_path . $bean_file_name, $user_global_variables_file_path);

if ($item_type == "dao") 
	$layer_path = DAO_PATH;
else if ($item_type == "vendor")
	$layer_path = VENDOR_PATH;
else if ($item_type == "test_unit")
	$layer_path = TEST_UNIT_PATH;
else if ($item_type == "other")
	$layer_path = OTHER_PATH;
else {
	$obj = $WorkFlowBeansFileHandler->getBeanObject($bean_name);
	
	$layer_path = null;
	if ($obj)
		$layer_path = $obj->getLayerPathSetting();
}
	
if ($layer_path && !empty($_POST)) { //bc of hackings, like trying to know the code for libs or system files or other files...
	$file_path = $layer_path . $path;
	
	if ($path && file_exists($file_path)) {
		$layer_object_id = $item_type == "dao" ? "vendor/dao/$path" : ($item_type == "vendor" || $item_type == "other" ? "$item_type/$path" : ($item_type == "test_unit" ? "vendor/testunit/$path" : $file_path));
		$UserAuthenticationHandler->checkInnerFilePermissionAuthentication($layer_object_id, "layer", "access");
		UserAuthenticationHandler::checkUsersMaxNum($UserAuthenticationHandler);
		UserAuthenticationHandler::checkActionsMaxNum($UserAuthenticationHandler);
		
		$object = isset($_POST["object"]) ? $_POST["object"] : null;
		
		$folder_path = substr($file_path, strlen($file_path) - 1) == "/" ? $file_path : dirname($file_path);
		if (!is_dir($folder_path))
			mkdir($folder_path, 0755, true);
		
		MyArray::arrKeysToLowerCase($object, true);
		
		switch ($file_type) {
			case "save_file_class":
				$status = WorkFlowPHPFileHandler::saveClass($file_path, $object, $class_id, $rename_file_with_class);
				break;
			case "remove_file_class":
				$status = WorkFlowPHPFileHandler::removeClass($file_path, $class_id, $remove_file_if_no_class);
				break;
			case "save_file_class_method":
			case "save_file_function":
				$file_was_changed = file_exists($file_path) && $file_modified_time && $file_modified_time < filemtime($file_path);
				
				//check if syntax is correct
				$ret = null;
				$error = null;
				$status = !isset($object["code"]) || PHPScriptHandler::isValidPHPContents("<? " . $object["code"] . " ?>", $error);
				
				if ($status) {
					if ($file_was_changed) {
						$old_code = file_exists($file_path) ? file_get_contents($file_path) : "";
						$tmp_file_path = tempnam(TMP_PATH, $file_type . "_");
						file_put_contents($tmp_file_path, $old_code);
						
						if ($file_type == "save_file_class_method")
							$status = WorkFlowPHPFileHandler::saveClassMethod($tmp_file_path, $object, $class_id, $method_id);
						else
							$status = WorkFlowPHPFileHandler::saveFunction($tmp_file_path, $object, $function_id);
						
						$ret = array(
							"status" => "CHANGED",
							"old_code" => $old_code,
							"new_code" => file_exists($tmp_file_path) ? file_get_contents($tmp_file_path) : "",
						);
						
						unlink($tmp_file_path);
					}
					else {
						if ($file_type == "save_file_class_method")
							$status = WorkFlowPHPFileHandler::saveClassMethod($file_path, $object, $class_id, $method_id);
						else
							$status = WorkFlowPHPFileHandler::saveFunction($file_path, $object, $function_id);
						
						clearstatcache(true, $file_path); //very important otherwise the filemtime will contain the old modified time.
						
						$ret = array(
							"status" => $status,
							"modified_time" => filemtime($file_path),
						);
					}
				}
				else if (!empty($error))
					$ret = $error;
				
				$status = json_encode($ret);
				break;
			case "remove_file_class_method":
				$status = WorkFlowPHPFileHandler::removeClassMethod($file_path, $class_id, $method_id);
				break;
			case "remove_file_function":
				$status = WorkFlowPHPFileHandler::removeFunction($file_path, $function_id);
				break;
			case "save_file_includes":
				$status = WorkFlowPHPFileHandler::saveIncludesAndNamespacesAndUses($file_path, $object);
				break;
		}
		
		if (!empty($status))
			$UserAuthenticationHandler->incrementUsedActionsTotal();
	}
}

if (empty($do_not_die_on_save)) {
	echo isset($status) ? $status : null;
	die();
}
?>
