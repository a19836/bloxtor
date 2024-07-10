<?php
include_once $EVC->getUtilPath("CMSPresentationLayerHandler");

$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "access");

$bean_name = $_GET["bean_name"];
$bean_file_name = $_GET["bean_file_name"];
$path = $_GET["path"];
$project = $_GET["project"];
$block = $_GET["block"];

/*The ENT_NOQUOTES will avoid converting the &quot; to ". If this is not here and if we have some form settings with PTL code like: 
	$form_settings = array("ptl" => array("code" => "<ptl:echo str_replace('\"', '&quot;', \$var_aux_910) />"));
...it will give a php error, because it will convert &quot; into ", which will be:
	$form_settings = array("ptl" => array("code" => "<ptl:echo str_replace('\"', '"', \$var_aux_910) />"));
Note that " is not escaped. It should be:
	$form_settings = array("ptl" => array("code" => "<ptl:echo str_replace('\"', '\"', \$var_aux_910) />"));

This ENT_NOQUOTES option was added in 2018-01-09, and I did not tested it for other cases
*/
$data = json_decode( htmlspecialchars_decode( file_get_contents("php://input"), ENT_NOQUOTES), true);
//echo "<pre>data:";print_r($data);die();

$page_region_block_type = $data["page_region_block_type"];
$page_region_block_params = $data["page_region_block_params"];
$page_region_block_join_points = $data["page_region_block_join_points"];
//echo "<pre>";print_r($page_region_block_params);print_r($page_region_block_join_points);die();

$path = str_replace("../", "", $path);//for security reasons

$html = $editable_settings = $block_code_id = $block_code_time = null;

if ($path && $project && $block) {
	$WorkFlowBeansFileHandler = new WorkFlowBeansFileHandler($user_beans_folder_path . $bean_file_name, $user_global_variables_file_path);
	$PEVC = $WorkFlowBeansFileHandler->getEVCBeanObject($bean_name, $path);
	
	if ($PEVC) {
		$PHPVariablesFileHandler = new PHPVariablesFileHandler(array($user_global_variables_file_path, $PEVC->getConfigPath("pre_init_config")));
		$PHPVariablesFileHandler->startUserGlobalVariables();
		
		$P = $PEVC->getPresentationLayer();
		
		//get page includes
		$page_include_files = getPageIncludeFiles($PEVC, $data["template_includes"]);
		//echo "<pre>includes:";print_r($page_include_files);die();
		
		//set new presentation bc the $project can be different from the page's project
		$P->setSelectedPresentationId($project);
		//echo "selected project id:".$P->getSelectedPresentationId();die();
		
		//get block path
		$is_block = $page_region_block_type != "view";
		$is_view = $page_region_block_type == "view";
		$block_path = $is_view ? $PEVC->getViewPath($block) : $PEVC->getBlockPath($block);
		//echo "block_path:$block_path";die();
		
		if (file_exists($block_path)) {
			$module_id = null;
			
			if ($is_block) {
				//get block's module id
				$block_params = CMSFileHandler::getFileCreateBlockParams($block_path, false, 1, 1);
				//echo "<pre>block_params:";print_r($block_params);die();
				$module_id = $block_params[0]["module_type"] == "string" ? $block_params[0]["module"] : "";
				
				$block_code_id = md5(file_get_contents($block_path));
				$block_code_time = filemtime($block_path);
			}
			
			if ($module_id) {
				$CMSModuleSimulatorHandler = $EVC->getCMSLayer()->getCMSModuleLayer()->getModuleSimulatorObj($module_id); //must be $EVC so we get the system path
				
				if ($CMSModuleSimulatorHandler) {
					//if module_id execute it and get correspondent html
					$CMSModuleLayer = $PEVC->getCMSLayer()->getCMSModuleLayer();
					$CMSModuleHandler = $CMSModuleLayer->getModuleObj($module_id);
					
					if ($CMSModuleHandler) {
						$cms_settings = array(
							"module_id" => $module_id, //raw module id without any parseing
							"block_id" => $block,
						);
						$CMSModuleHandler->setCMSSettings($cms_settings);
						
						$CMSModuleSimulatorHandler->setCMSModuleHandler($CMSModuleHandler);
						
						//prepare page block settings
						$block_settings = getProjectBlockSettings($PEVC, $project, $block_params, $page_include_files, $page_region_block_params);
						//echo "<pre>block_settings:";print_r($block_settings);die();
						
						//prepare page join points
						if ($page_region_block_join_points) {
							foreach ($page_region_block_join_points as $join_point_name => $join_point) {
								foreach ($join_point as $idx => $join_point_settings) 
									if (is_array($join_point_settings)) {
										$join_point_settings = getProjectJoinPointSettings($PEVC, $project, $page_include_files, $join_point_settings);
										$PEVC->getCMSLayer()->getCMSJoinPointLayer()->addBlockJoinPoint($block, $join_point_name, $block_join_point_properties);
									}
							}
						}
						
						$html = $CMSModuleSimulatorHandler->simulate($block_settings, $editable_settings);
						//echo $html;die();
					}
				}
			}
			else { //if block is free code without any module call, get the correspondent html
				$html = getProjectBlockHtml($PEVC, $project, $block_path, $page_include_files);
			}
		}
		
		$PHPVariablesFileHandler->endUserGlobalVariables();
	}
}

function getPageIncludeFiles($EVC, $includes) {
	$page_include_files = array();
	
	if (is_array($includes)) {
		foreach ($includes as $include) 
			if ($include["path"]) {
				$code = "\$include_path = " . $include["path"] . ";";
				eval($code);
				
				if ($include_path && file_exists($include_path))
					$page_include_files[] = $include_path;
			}
	}
	
	return $page_include_files;
}

function getProjectJoinPointSettings($EVC, $selected_project_id, $page_include_files, $join_point_settings) {
	//prepare external vars
	$before_defined_vars = get_defined_vars();
	
	//include default files
	include $EVC->getConfigPath("config", $selected_project_id);
	include_once $EVC->getUtilPath("include_text_translator_handler", $EVC->getCommonProjectName());
	
	//include page files
	if (is_array($page_include_files)) {
		foreach ($page_include_files as $include) 
			@include_once $include;
	}
	
	$after_defined_vars = get_defined_vars();
	$external_vars = array_diff_key($after_defined_vars, $before_defined_vars);
	$external_vars["EVC"] = $EVC;
	unset($external_vars["before_defined_vars"]);
	
	$code = "<?php return " . CMSPresentationLayerHandler::getJoinPointPropertiesCode($join_point_settings) . '; ?>';
	//echo "code:<textarea>$code</textarea>";die();
	
	PHPScriptHandler::parseContent($code, $external_vars, $return_values);
	//echo "<pre>return_values:";print_r($return_values);die();
	$join_point_settings = $return_values[0];
	
	return $join_point_settings;
}

function getProjectBlockSettings($EVC, $selected_project_id, $block_params, $page_include_files, $page_region_block_params) {
	//prepare external vars
	$before_defined_vars = get_defined_vars();
	
	//include default files
	include $EVC->getConfigPath("config", $selected_project_id);
	include_once $EVC->getUtilPath("include_text_translator_handler", $EVC->getCommonProjectName());
	
	//include page files
	if (is_array($page_include_files)) {
		foreach ($page_include_files as $include) 
			@include_once $include;
	}
	
	$after_defined_vars = get_defined_vars();
	$external_vars = array_diff_key($after_defined_vars, $before_defined_vars);
	$external_vars["EVC"] = $EVC;
	unset($external_vars["before_defined_vars"]);
	
	$code = "<?php \n";
	
	//prepare $page_region_block_params
	if ($page_region_block_params) {
		$arr_code = "";
		
		foreach($page_region_block_params as $k => $v)
			$arr_code .= ($arr_code ? ", " : "") . "$k => $v";
		
		$code .= '$block_local_variables = array(' . $arr_code . ");\n";
	}
	
	$code .= 'return ';
	
	if (is_array($block_params[0]["block_settings"]))
		$code .= WorkFlowTask::getArrayString($block_params[0]["block_settings"]);
	else
		$code .= strlen($block_params[0]["block_settings"]) ? PHPUICodeExpressionHandler::getArgumentCode($block_params[0]["block_settings"], $block_params[0]["block_settings_type"]) : '""';
	
	$code .= '; ?>';
	//echo "code:<textarea>$code</textarea>";die();
	
	PHPScriptHandler::parseContent($code, $external_vars, $return_values);
	//echo "<pre>return_values:";print_r($return_values);die();
	$block_settings = $return_values[0];
	
	return $block_settings;
}

function getProjectBlockHtml($EVC, $selected_project_id, $block_path, $page_include_files) {
	include $EVC->getConfigPath("config", $selected_project_id);
	include_once $EVC->getUtilPath("include_text_translator_handler", $EVC->getCommonProjectName());
	
	if (is_array($page_include_files)) {
		foreach ($page_include_files as $include) 
			@include_once $include;
	}
	
	ob_start(null, 0);
	include $block_path;
	$html = ob_get_contents();
	ob_end_clean();
	
	return $html;
}
?>
