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

include_once $EVC->getUtilPath("PHPVariablesFileHandler");
include_once $EVC->getUtilPath("CMSPresentationLayerHandler");

$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "access");

$bean_name = isset($_GET["bean_name"]) ? $_GET["bean_name"] : null;
$bean_file_name = isset($_GET["bean_file_name"]) ? $_GET["bean_file_name"] : null;
$project = isset($_GET["project"]) ? $_GET["project"] : null;
$block = isset($_GET["block"]) ? $_GET["block"] : null;

/*The ENT_NOQUOTES will avoid converting the &quot; to ". If this is not here and if we have some form settings with PTL code like: 
	$form_settings = array("ptl" => array("code" => "<ptl:echo str_replace('\"', '&quot;', \$var_aux_910) />"));
...it will give a php error, because it will convert &quot; into ", which will be:
	$form_settings = array("ptl" => array("code" => "<ptl:echo str_replace('\"', '"', \$var_aux_910) />"));
Note that " is not escaped. It should be:
	$form_settings = array("ptl" => array("code" => "<ptl:echo str_replace('\"', '\"', \$var_aux_910) />"));

This ENT_NOQUOTES option was added in 2018-01-09, and I did not tested it for other cases
*/
$post_data = json_decode( htmlspecialchars_decode( file_get_contents("php://input"), ENT_NOQUOTES), true);
//echo "<pre>post_data:";print_r($post_data);die();

$status = $block_code_id = $block_code_time = null;

if ($project && $block && $post_data) {
	$WorkFlowBeansFileHandler = new WorkFlowBeansFileHandler($user_beans_folder_path . $bean_file_name, $user_global_variables_file_path);
	$PEVC = $WorkFlowBeansFileHandler->getEVCBeanObject($bean_name, $project);
	
	if ($PEVC) {
		$block_path = $PEVC->getBlockPath($block);
		
		if (file_exists($block_path)) {
			//get block's module id
			$block_params = CMSFileHandler::getFileCreateBlockParams($block_path, false, 1, 1);
			//echo "<pre>block_params:";print_r($block_params);die();
			$module_id = isset($block_params[0]["module_type"]) && $block_params[0]["module_type"] == "string" ? $block_params[0]["module"] : "";
			
			$old_block_code_id = md5(file_get_contents($block_path));
			$old_block_code_time = filemtime($block_path);
			
			//if module_id exists which means it has the block_settings
			if ($module_id && isset($post_data["block_code_id"]) && $old_block_code_id == $post_data["block_code_id"] && isset($post_data["block_code_time"]) && $old_block_code_time == $post_data["block_code_time"]) {
				$setting_path = isset($post_data["setting_path"]) ? $post_data["setting_path"] : null;
				$setting_value = isset($post_data["setting_value"]) ? $post_data["setting_value"] : null;
				$status = CMSFileHandler::setFileBlockSettingsPropertyValue($block_path, $setting_path, $setting_value);
				
				if ($status) {
					clearstatcache(); //clear cache otherwise when we get the filemtime, it will get the same time than before.
					
					$new_block_code_id = md5(file_get_contents($block_path));
					$new_block_code_time = filemtime($block_path);
				}
			}
		}
	}
}
?>
