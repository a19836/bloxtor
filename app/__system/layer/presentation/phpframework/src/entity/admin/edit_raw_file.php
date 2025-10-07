<?php
include_once get_lib("org.phpframework.workflow.WorkFlowTaskHandler");
include_once get_lib("org.phpframework.util.MimeTypeHandler");
include_once $EVC->getUtilPath("WorkFlowBeansFileHandler");

$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "access");
UserAuthenticationHandler::checkUsersMaxNum($UserAuthenticationHandler);

$bean_name = isset($_GET["bean_name"]) ? $_GET["bean_name"] : null;
$bean_file_name = isset($_GET["bean_file_name"]) ? $_GET["bean_file_name"] : null;
$path = isset($_GET["path"]) ? $_GET["path"] : null;
$item_type = isset($_GET["item_type"]) ? $_GET["item_type"] : null;
$scroll_top = isset($_GET["scroll_top"]) ? $_GET["scroll_top"] : null;
$create_dependencies = isset($_GET["create_dependencies"]) ? $_GET["create_dependencies"] : null;
$file_modified_time = isset($_GET["file_modified_time"]) ? $_GET["file_modified_time"] : null;
$popup = isset($_GET["popup"]) ? $_GET["popup"] : null;

$path = str_replace("../", "", $path);//for security reasons

$WorkFlowBeansFileHandler = new WorkFlowBeansFileHandler($user_beans_folder_path . $bean_file_name, $user_global_variables_file_path);
$layer_path = !empty($layer_path) ? $layer_path : null;

if ($item_type == "dao") 
	$layer_path = DAO_PATH;
else if ($item_type == "vendor")
	$layer_path = VENDOR_PATH;
else if ($item_type == "test_unit")
	$layer_path = TEST_UNIT_PATH;
else if ($item_type == "other")
	$layer_path = OTHER_PATH;
else if ($bean_name) {
	if ($item_type != "presentation")
		$obj = $WorkFlowBeansFileHandler->getBeanObject($bean_name);
	else {
		$PEVC = $WorkFlowBeansFileHandler->getEVCBeanObject($bean_name, $path);
		$obj = $PEVC ? $PEVC->getPresentationLayer() : null;
	}
	
	if ($obj)
		$layer_path = $obj->getLayerPathSetting();
}

if ($layer_path) { //bc of hackings, like trying to know the code for libs or system files or other files...
	$file_path = $layer_path . $path;

	$path_info = pathinfo($file_path);
	$path_info_extension_lower = strtolower($path_info["extension"]);
	
	$available_extensions = array("xml" => "xml", "php" => "php", "js" => "javascript", "css" => "css", "" => "text", "txt" => "text", "html" => "html", "htm" => "html");
	$editor_code_type = isset($available_extensions[$path_info_extension_lower]) ? $available_extensions[$path_info_extension_lower] : null;
	
	if (!$editor_code_type) {
		$mime_type = MimeTypeHandler::getFileMimeType($file_path);
		$is_text = $mime_type && MimeTypeHandler::isTextMimeType($mime_type);
		
		if ($is_text)
			$editor_code_type = "text";
	}
	
	$code = "";
	$file_exists = file_exists($file_path);
	
	if ($path) {
		$layer_object_id = $item_type == "dao" ? "vendor/dao/$path" : ($item_type == "vendor" || $item_type == "other" ? "$item_type/$path" : ($item_type == "test_unit" ? "vendor/testunit/$path" : $file_path));
		$UserAuthenticationHandler->checkInnerFilePermissionAuthentication($layer_object_id, "layer", "access");
		
		$code = $file_exists ? file_get_contents($file_path) : "";
		
		if (!empty($_POST) && empty($readonly)) {
			$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "write");
			UserAuthenticationHandler::checkActionsMaxNum($UserAuthenticationHandler);
			
			$new_code = isset($_POST["code"]) ? $_POST["code"] : null;
			$code_id = isset($_POST["code_id"]) ? $_POST["code_id"] : null;
			$force = isset($_POST["force"]) ? $_POST["force"] : null;
			
			$file_was_changed = $file_exists && $file_modified_time && $code_id != md5($code) && $file_modified_time < filemtime($file_path);
			
			if (!$force && ($code_id != md5($code) || $file_was_changed))
				$ret = array(
					"status" => "CHANGED",
					"old_code" => $code,
					"new_code" => $new_code,
				);
			else {
				$continue = true;
				
				if (!$file_exists && $create_dependencies && is_dir($layer_path)) {
					$folder_path = dirname($file_path) . "/";
					
					if ($layer_path != $folder_path && !file_exists($folder_path))
						$continue = mkdir($folder_path, 0755, true);
				}
				
				$status = $continue ? file_put_contents($file_path, $new_code) !== false : false;
				
				clearstatcache(true, $file_path); //very important otherwise the filemtime will contain the old modified time.
				
				if ($status)
					$UserAuthenticationHandler->incrementUsedActionsTotal();
				
				$ret = array(
					"status" => $status, 
					"code_id" => md5($new_code),
					"modified_time" => filemtime($file_path),
				);
			}
			
			if (empty($do_not_die_on_save)) {
				echo json_encode($ret);
				die();
			}
		}
		else if ($file_exists)
			$file_modified_time = filemtime($file_path);
	}
	
	if (!$editor_code_type) {
		$is_binary = $code && preg_match('~[^\x20-\x7E\t\r\n]~', $code) > 0;
		
		if (!$code || !$is_binary)
			$editor_code_type = "text";
	}
}
?>
