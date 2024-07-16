<?php
include_once get_lib("org.phpframework.workflow.WorkFlowTaskHandler");
include_once get_lib("org.phpframework.layer.presentation.cms.module.CMSModuleEnableHandler");
include_once $EVC->getUtilPath("WorkFlowBrokersSelectedDBVarsHandler");
include_once $EVC->getUtilPath("CMSPresentationLayerHandler");
include_once $EVC->getUtilPath("LayoutTypeProjectHandler");

$bean_name = $_GET["bean_name"];
$bean_file_name = $_GET["bean_file_name"];
$path = $_GET["path"];
$filter_by_layout = $_GET["filter_by_layout"];
$popup = $_GET["popup"];

$path = str_replace("../", "", $path);//for security reasons
$filter_by_layout = str_replace("../", "", $filter_by_layout);//for security reasons

if ($path) {
	$WorkFlowBeansFileHandler = new WorkFlowBeansFileHandler($user_beans_folder_path . $bean_file_name, $user_global_variables_file_path);
	$PEVC = $WorkFlowBeansFileHandler->getEVCBeanObject($bean_name, $path);
	
	if ($PEVC) {
		$PHPVariablesFileHandler = new PHPVariablesFileHandler(array($user_global_variables_file_path, $PEVC->getConfigPath("pre_init_config")));
		$PHPVariablesFileHandler->startUserGlobalVariables();
		
		$P = $PEVC->getPresentationLayer();
		$layer_path = $P->getLayerPathSetting();
		$selected_project_id = $P->getSelectedPresentationId();
		ConsequenceOfHackingTheLicence();
		
		$file_path = $layer_path . $path;
		
		if (file_exists($file_path)) {
			$UserAuthenticationHandler->checkInnerFilePermissionAuthentication($file_path, "layer", "access");
			UserAuthenticationHandler::checkUsersMaxNum($UserAuthenticationHandler);
			
			$default_extension = "." . $P->getPresentationFileExtension();
			$presentation_layer_label = WorkFlowBeansFileHandler::getLayerNameFromBeanObject($bean_name, $P) . " (Self)";
			
			$file_modified_time = filemtime($file_path);
			$obj_data["code"] = file_get_contents($file_path);
			
			//remove the weird chars from code, this is, in the php editor appears some red dots in the code, which means there some weird chars in the code. I detected which chars are these ones, this is, the char 'chr(194) . chr(160)' which should be replaced with space and the second with empty.
			$obj_data["code"] = str_replace(chr(194) . chr(160), ' ', $obj_data["code"]);
			
			$LayoutTypeProjectHandler = new LayoutTypeProjectHandler($UserAuthenticationHandler, $user_global_variables_file_path, $user_beans_folder_path, $bean_file_name, $bean_name);
			$brokers = $P->getBrokers();
			$selected_db_vars = WorkFlowBrokersSelectedDBVarsHandler::getBrokersSelectedDBVars($brokers);
			
			//prepare brokers db drivers
			$brokers_db_drivers = WorkFlowBeansFileHandler::getBrokersDBDrivers($user_global_variables_file_path, $user_beans_folder_path, $brokers, true);
			$LayoutTypeProjectHandler->filterLayerBrokersDBDriversPropsFromLayoutName($brokers_db_drivers, $filter_by_layout); //filter db_drivers by $filter_by_layout
			
			if ($selected_db_vars["db_brokers_drivers"]) //not sure if we should be doing this filter... Note that the $db_drivers will appear in the "db driver combox/select field" in the widgets properties of the LayoutUIEditor.
				foreach ($selected_db_vars["db_brokers_drivers"] as $layer_name => &$layer_db_drivers)
					$LayoutTypeProjectHandler->filterLayerBrokersDBDriversNamesFromLayoutName($P, $layer_db_drivers, $filter_by_layout); //filter db_drivers by $filter_by_layout

			//PREPARING OBJ DATA
			switch ($file_type) {
				case "edit_entity":
					//PREPARING ENTITY_VIEW_CODE
					$entity_view_code = str_replace($selected_project_id . "/" . $P->settings["presentation_entities_path"], "", $path);
					$entity_view_code = substr($entity_view_code, strlen($entity_view_code) - strlen($default_extension)) == $default_extension ? substr($entity_view_code, 0, strlen($entity_view_code) - strlen($default_extension)) : $entity_view_code;
					
					$edit_entity_simple_hard_code = false;
					
					if (!empty($_GET["edit_entity_type"])) {
						$edit_entity_type = strtolower($_GET["edit_entity_type"]);
						$edit_entity_simple_hard_code = $edit_entity_type == "simple";
						
						if (empty($_GET["dont_save_cookie"]))
							CookieHandler::setCurrentDomainEternalRootSafeCookie("edit_entity_type", $edit_entity_type);
					}
					else if (!empty($_COOKIE["edit_entity_type"])) 
						$edit_entity_type = strtolower($_COOKIE["edit_entity_type"]);

					$edit_entity_type = !empty($edit_entity_type) ? $edit_entity_type : "simple";
					
					//PREPARING SIMPLE UI
					if ($edit_entity_type == "simple") {
						$edit_entity_advanced_url = $project_url_prefix . "phpframework/presentation/edit_entity?bean_name=$bean_name&bean_file_name=$bean_file_name&filter_by_layout=$filter_by_layout&path=$path&popup=$popup&edit_entity_type=advanced&dont_save_cookie=1";
						
						$EVC->setView("edit_entity_simple");
						
						//PREPARING INCLUDES
						$includes = CMSFileHandler::getIncludes($obj_data["code"], false);
			
						//PREPARING REGIONS-BLOCKS
						$regions_blocks = CMSFileHandler::getRegionsBlocks($obj_data["code"]);
						$regions_blocks_list = CMSPresentationLayerHandler::getRegionsBlocksList($regions_blocks, $selected_project_id);
						//echo "<pre>";print_r($regions_blocks);die();
						//echo "<pre>";print_r($regions_blocks_list);die();
						
						//PREPARING AVAILABLE BLOCKS
						$available_blocks_list = CMSPresentationLayerHandler::initBlocksListThroughRegionBlocks($PEVC, $regions_blocks_list, $selected_project_id);
						//echo "<pre>";print_r($available_blocks_list);die();
						
						//PREPARING BLOCK PARAMS
						$block_params = CMSPresentationLayerHandler::getBlockParamsList($PEVC, $regions_blocks, $obj_data["code"], $available_blocks_list);
						$available_block_params_list = $block_params[0];
						$block_params_values_list = $block_params[1];
						//echo "<pre>";print_r($block_params);die();
						
						//PREPARING BLOCK JOIN POINTS
						$blocks_join_points = CMSFileHandler::getAddRegionBlockJoinPoints($obj_data["code"]);
						//echo "<pre>";print_r($blocks_join_points);die();
						$blocks_join_points = CMSPresentationLayerHandler::getAddRegionBlockJoinPointsListByBlock($blocks_join_points);
						//echo "<pre>";print_r($blocks_join_points);die();
						
						//PREPARING DEFAULT TEMPLATE
						$vars = PHPVariablesFileHandler::getVarsFromFileCode( $PEVC->getConfigPath("pre_init_config") );
						//echo "<pre>";print_r($vars);die();
						$layer_default_template = $vars["project_default_template"];
						
						//PREPARING SELECTED TEMPLATE
						$set_template = CMSPresentationLayerHandler::getFileCodeSetTemplate($obj_data["code"]);
						//echo "<pre>";print_r($set_template);die();
						
						//CHECKING IF SIMPLE UI IS ALLOWED
						//redirect to advanced ui bc there are some invalid params that were probably hard-coded...
						if ($set_template && !CMSPresentationLayerHandler::isSetTemplateParamsValid($PEVC, $set_template)) {
							header("Location: $edit_entity_advanced_url");
							echo "<script>document.location = '$edit_entity_advanced_url';</script>";
							die();
						}
						
						$is_external_template = CMSPresentationLayerHandler::isSetTemplateExternalTemplate($EVC, $set_template);
						
						//SETTING SELECTED AND DEFAULT TEMPLATE - needed on UI
						$selected_template = $set_template ? $set_template["template_code"] : null;
						$selected_or_default_template = $is_external_template || $selected_template ? $selected_template : $layer_default_template; //only adds the default template is not external template.
						
						//PREPARING PARSE PHP CODE TEMPLATE PROPS
						$template_contents = CMSPresentationLayerHandler::getSetTemplateCode($PEVC, $is_external_template, $selected_or_default_template, $set_template["template_params"], $includes);
						//echo "<pre>";print_r($template_contents);die();
						
						//PREPARING AVAILABLE TEMPLATES
						$available_templates = $selected_or_default_template && $selected_or_default_template != $layer_default_template ? array($selected_or_default_template) : array(); //Note that we will get all the other templates from ajax, so this server response be faster.
						//echo "<pre>";print_r($available_templates);die();
						
						//GETTING SELECTED TEMPLATE AVAILABLE REGIONS
						$available_regions_list = CMSPresentationLayerHandler::getAvailableRegionsListFromCode($template_contents, $selected_project_id, false);
						$undefined_regions_list = CMSPresentationLayerHandler::getAvailableRegionsListFromCode($template_contents, $selected_project_id, true);
						$defined_regions_list = array_values(array_diff($available_regions_list, $undefined_regions_list));
						//echo "<pre>";print_r($available_regions_list);die();
						//echo "<pre>";print_r($defined_regions_list);die();
						
						//PREPARING TEMPLATE PARAMS
						$params_list = CMSPresentationLayerHandler::getAvailableTemplateParamsListFromCode($template_contents, true);
						$available_params_list = $params_list[0];
						$available_params_values_list = $params_list[1];
						$template_params_values_list = CMSPresentationLayerHandler::getAvailableTemplateParamsValuesList($obj_data["code"]);
						//echo "<pre>";print_r($params_list);die();
						//echo "<pre>";print_r($template_params_values_list);die();
						
						//PREPARING FILES VALIDATION:
						$UserCacheHandler = $PHPFrameWork->getObject("UserCacheHandler"); //$PHPFrameWork is the same than $EVC->getPresentationLayer()->getPHPFrameWork(); //Use EVC instead of PEVC, bc is relative to the __system admin panel
						$cached_modified_date = CMSPresentationLayerHandler::getCachedEntitySaveActionTime($UserCacheHandler, $cms_page_cache_path_prefix, $file_path);
						$hard_coded = CMSPresentationLayerHandler::isEntityFileHardCoded($PEVC, $UserCacheHandler, $cms_page_cache_path_prefix, $file_path, true, $workflow_paths_id, $bean_name);
						
						//PREPARING WORDPRESS INSTALLATION FOLDERS NAME
						$installed_wordpress_folders_name = CMSPresentationLayerHandler::getWordPressInstallationsFoldersName($PEVC);
						
						//DOUBLE CHECKING IF SIMPLE UI IS ALLOWED
						if (!$edit_entity_simple_hard_code && !CMSPresentationLayerHandler::checkIfEntityCodeContainsSimpleUISettings($obj_data["code"], $selected_template, $includes, $regions_blocks)) {
							header("Location: $edit_entity_advanced_url");
							echo "<script>document.location = '$edit_entity_advanced_url';</script>";
							die();
						}
						
						//PREPARE URLS
						$selected_project_url_prefix = getProjectUrlPrefix($PEVC, $selected_project_id);
						$selected_project_common_url_prefix = getProjectCommonUrlPrefix($PEVC, $selected_project_id);
						
						//PREPARING SEQUENTIAL LOGICAL ACTIVITIES
						//load the createform task, so we can load the programming/common/js/FormFieldsUtilObj.js file, bc this file is used in the LayoutUIEditor.js
						include_once $EVC->getUtilPath("SequentialLogicalActivityUIHandler");
						
						$opts = array(
							"main_div_selector" => ".entity_obj",
							"workflow_tasks_id" => "presentation_entity_sla",
							"path_extra" => hash('crc32b', "$bean_file_name/$bean_name/$path"),
						);
						$sla = SequentialLogicalActivityUIHandler::getHeader($EVC, $PEVC, $UserAuthenticationHandler, $bean_name, $bean_file_name, $path, $project_url_prefix, $project_common_url_prefix, $external_libs_url_prefix, $user_global_variables_file_path, $user_beans_folder_path, $webroot_cache_folder_path, $webroot_cache_folder_url, $filter_by_layout, $opts);
						$sla_head = $sla["head"];
						$sla_js_head = $sla["js_head"];
						$tasks_contents = $sla["tasks_contents"];
						$layer_brokers_settings = $sla["layer_brokers_settings"];
						$presentation_projects = $sla["presentation_projects"];
						$db_drivers = $sla["db_drivers"];
						$WorkFlowUIHandler = $sla["WorkFlowUIHandler"];
						$set_workflow_file_url = $sla["set_workflow_file_url"];
						
						//prepare brokers
						$presentation_brokers = $layer_brokers_settings["presentation_brokers"];
						$business_logic_brokers = $layer_brokers_settings["business_logic_brokers"];
						$data_access_brokers = $layer_brokers_settings["data_access_brokers"];
						$ibatis_brokers = $layer_brokers_settings["ibatis_brokers"];
						$hibernate_brokers = $layer_brokers_settings["hibernate_brokers"];
						$db_brokers = $layer_brokers_settings["db_brokers"];
						//echo "<pre>";print_r($db_brokers);die();
					
						//prepare sla settings
						$sla_settings = CMSFileHandler::getFileAddSequentialLogicalActivities($file_path, false, 1, 1);
						$sla_settings = isset($sla_settings[0]["sla_settings"]["key"]) ? array($sla_settings[0]["sla_settings"]) : $sla_settings[0]["sla_settings"];
						//echo "<pre>";print_r($sla_settings);die();
						
						//prepare access_activity_id for the sla
						$module_user_util_path = $PEVC->getModulePath("user/UserUtil", $PEVC->getCommonProjectName());
						
						if (file_exists($module_user_util_path)) {
							$module_user_util_code = file_get_contents($module_user_util_path);
							preg_match("/const\sACCESS_ACTIVITY_ID\s=\s([0-1]+);/", $module_user_util_code, $matches, PREG_OFFSET_CAPTURE);
							$access_activity_id = $matches && is_numeric($matches[1][0]) ? $matches[1][0] : null;
						}
						
						//PREPARING PAGE ADVANCED PROPERTIES
						$advanced_settings = CMSPresentationLayerHandler::getFilePageProperties($file_path);
						//echo "<pre>";print_r($advanced_settings);var_dump($advanced_settings);die();
						
						if (empty($obj_data["code"])) //if new file, set default advanced_settings
							$advanced_settings = array(
								"parse_full_html" => true,
								//"add_my_js_lib" => false,
								"filter_by_permission" => false, //make server responses faster and leave processing in the client side.
								"include_blocks_when_calling_resources" => false, //for logical, security and speed reasons, leave it false. Only if the user changes manually, this should be active.
							);
					}
					else {
						$EVC->setView("edit_entity_advanced");
						
						//PREPARING VIEW
						$project_with_auto_view = $GLOBALS["project_with_auto_view"];
						$view_file_exists = $PEVC->viewExists($entity_view_code);
						$view_file_path = str_replace($P->settings["presentation_entities_path"], $P->settings["presentation_views_path"], $path);
						//PREPARING BROKERS
						$layer_brokers_settings = WorkFlowBeansFileHandler::getLayerBrokersSettings($user_global_variables_file_path, $user_beans_folder_path, $brokers, '$EVC->getBroker');
						//echo "<pre>";print_r($layer_brokers_settings);die();
						
						$presentation_brokers = array();
						$presentation_brokers[] = array($presentation_layer_label, $bean_file_name, $bean_name);
						$presentation_brokers_obj = array("default" => '$EVC->getPresentationLayer()');
						
						$business_logic_brokers = $layer_brokers_settings["business_logic_brokers"];
						$business_logic_brokers_obj = $layer_brokers_settings["business_logic_brokers_obj"];
						
						$data_access_brokers = $layer_brokers_settings["data_access_brokers"];
						$data_access_brokers_obj = $layer_brokers_settings["data_access_brokers_obj"];
			
						$ibatis_brokers = $layer_brokers_settings["ibatis_brokers"];
						$ibatis_brokers_obj = $layer_brokers_settings["ibatis_brokers_obj"];
			
						$hibernate_brokers = $layer_brokers_settings["hibernate_brokers"];
						$hibernate_brokers_obj = $layer_brokers_settings["hibernate_brokers_obj"];
						
						$db_brokers = $layer_brokers_settings["db_brokers"];
						$db_brokers_obj = $layer_brokers_settings["db_brokers_obj"];
						
						//PREPARING getbeanobject
						$phpframeworks_options = array("default" => '$EVC->getPresentationLayer()->getPHPFrameWork()');
						$bean_names_options = array_keys($P->getPHPFrameWork()->getObjects());
						
						//PREPARING brokers db drivers
						$db_drivers_options = array_keys($brokers_db_drivers);
						
						//PREPARING WORKFLOW TASKS
						$allowed_tasks_tag = array(
							"definevar", "setvar", "setarray", "setdate", "ns", "createfunction", "createclass", "setobjectproperty", "createclassobject", "callobjectmethod", "callfunction", "addheader", "if", "switch", "loop", "foreach", "includefile", "echo", "code", "break", "return", "exit", "validator", "upload", "geturlcontents", "restconnector", "soapconnector", "getbeanobject", "sendemail", "debuglog",
							"trycatchexception", "throwexception", "printexception",
							"callpresentationlayerwebservice", "setpresentationview", "addpresentationview", "setpresentationtemplate",
							"inlinehtml", "createform",
							"setblockparams", "settemplateregionblockparam", "includeblock", "addtemplateregionblock", "rendertemplateregion", "settemplateparam", "gettemplateparam", "resetregionblockjoinpoints", "addregionhtml", "addregionblock"
						);
						
						if ($data_access_brokers_obj) {
							$allowed_tasks_tag[] = "setquerydata";
							$allowed_tasks_tag[] = "getquerydata";
							$allowed_tasks_tag[] = "dbdaoaction";
				
							if ($ibatis_brokers_obj) 
								$allowed_tasks_tag[] = "callibatisquery";
							
							if ($hibernate_brokers_obj) {
								$allowed_tasks_tag[] = "callhibernateobject";
								$allowed_tasks_tag[] = "callhibernatemethod";
							}
						}
						else if ($db_brokers_obj) {
							$allowed_tasks_tag[] = "setquerydata";
							$allowed_tasks_tag[] = "getquerydata";
							$allowed_tasks_tag[] = "dbdaoaction";
						}
						
						if ($db_brokers_obj)
							$allowed_tasks_tag[] = "getdbdriver";
				
						if ($business_logic_brokers_obj) 
							$allowed_tasks_tag[] = "callbusinesslogic";
						
						$WorkFlowTaskHandler = new WorkFlowTaskHandler($webroot_cache_folder_path, $webroot_cache_folder_url);
						$WorkFlowTaskHandler->setCacheRootPath(LAYER_CACHE_PATH);
						$WorkFlowTaskHandler->setAllowedTaskTags($allowed_tasks_tag);
						$WorkFlowTaskHandler->addTasksFoldersPath($code_workflow_editor_user_tasks_folders_path);
						$WorkFlowTaskHandler->addAllowedTaskTagsFromFolders($code_workflow_editor_user_tasks_folders_path);
						
						$available_projects = $PEVC->getProjectsId();
					}
					
					break;
			
				case "edit_view": 
					if (!empty($_GET["edit_view_type"])) {
						$edit_view_type = strtolower($_GET["edit_view_type"]);
						
						if (empty($_GET["dont_save_cookie"]))
							CookieHandler::setCurrentDomainEternalRootSafeCookie("edit_view_type", $edit_view_type);
					}
					else if (!empty($_COOKIE["edit_view_type"])) 
						$edit_view_type = strtolower($_COOKIE["edit_view_type"]);
					
					$edit_view_type = !empty($edit_view_type) ? $edit_view_type : "simple";
					
					//PREPARING SIMPLE UI
					if ($edit_view_type == "simple") {
						$EVC->setView("edit_view_simple");
						
						$top_code = "";
						if (strtolower(substr($obj_data["code"], 0, 5)) == "<?php")
							$top_code = substr($obj_data["code"], 5, strpos($obj_data["code"], "?>") - 5);
						$top_code = $top_code ? "<?php $top_code ?>" : "";
						
						//PREPARING INCLUDES
						$includes = CMSFileHandler::getIncludes($top_code, false);
						
						//PREPARING REGIONS-BLOCKS
						$regions_blocks = CMSFileHandler::getRegionsBlocks($top_code);
						$regions_blocks_list = CMSPresentationLayerHandler::getRegionsBlocksList($regions_blocks, $selected_project_id);
						
						//PREPARING AVAILABLE BLOCKS
						$available_blocks_list = CMSPresentationLayerHandler::initBlocksListThroughRegionBlocks($PEVC, $regions_blocks_list, $selected_project_id);
						
						//PREPARING BLOCK PARAMS
						$block_params = CMSPresentationLayerHandler::getBlockParamsList($PEVC, $regions_blocks, $top_code, $available_blocks_list);
						$available_block_params_list = $block_params[0];
						$block_params_values_list = $block_params[1];
						
						//PREPARING BLOCK JOIN POINTS
						$blocks_join_points = CMSFileHandler::getAddRegionBlockJoinPoints($top_code);
						$blocks_join_points = CMSPresentationLayerHandler::getAddRegionBlockJoinPointsListByBlock($blocks_join_points);
						//echo "<pre>";print_r($blocks_join_points);die();
						
						//PREPARING SELECTED TEMPLATE THAT DON'T EXIST
						$selected_template = substr($path, strpos($path, "/src/view/") + strlen("/src/view/"), - strlen($default_extension));
						
						//PREPARING TEMPLATE AVAILABLE REGIONS
						$available_regions_list = CMSPresentationLayerHandler::getAvailableRegionsList($file_path, $selected_project_id);
						
						//PREPARING TEMPLATE PARAMS
						$params_list = CMSPresentationLayerHandler::getAvailableTemplateParamsList($file_path);
						$available_params_list = array();
						$view_params_values_list = $params_list[1];
						//echo "<pre>";print_r($params_list);die();
						
						//PREPARE URLS
						$selected_project_url_prefix = getProjectUrlPrefix($PEVC, $selected_project_id);
						$selected_project_common_url_prefix = getProjectCommonUrlPrefix($PEVC, $selected_project_id);
						
						//PREPARING SEQUENTIAL LOGICAL ACTIVITIES
						//load the createform task, so we can load the programming/common/js/FormFieldsUtilObj.js file, bc this file is used in the LayoutUIEditor.js
						include_once $EVC->getUtilPath("SequentialLogicalActivityUIHandler");
						
						$opts = array(
							"main_div_selector" => ".view_obj",
							"workflow_tasks_id" => "presentation_view_sla",
							"path_extra" => hash('crc32b', "$bean_file_name/$bean_name/$path"),
						);
						$sla = SequentialLogicalActivityUIHandler::getHeader($EVC, $PEVC, $UserAuthenticationHandler, $bean_name, $bean_file_name, $path, $project_url_prefix, $project_common_url_prefix, $external_libs_url_prefix, $user_global_variables_file_path, $user_beans_folder_path, $webroot_cache_folder_path, $webroot_cache_folder_url, $filter_by_layout, $opts);
						$sla_head = $sla["head"];
						$sla_js_head = $sla["js_head"];
						$tasks_contents = $sla["tasks_contents"];
						$layer_brokers_settings = $sla["layer_brokers_settings"];
						$presentation_projects = $sla["presentation_projects"];
						$db_drivers = $sla["db_drivers"];
						$WorkFlowUIHandler = $sla["WorkFlowUIHandler"];
						$set_workflow_file_url = $sla["set_workflow_file_url"];
						
						//prepare brokers
						$presentation_brokers = $layer_brokers_settings["presentation_brokers"];
						$business_logic_brokers = $layer_brokers_settings["business_logic_brokers"];
						$data_access_brokers = $layer_brokers_settings["data_access_brokers"];
						$ibatis_brokers = $layer_brokers_settings["ibatis_brokers"];
						$hibernate_brokers = $layer_brokers_settings["hibernate_brokers"];
						$db_brokers = $layer_brokers_settings["db_brokers"];
						
						//prepare sla settings
						$sla_settings = CMSFileHandler::getFileAddSequentialLogicalActivities($file_path, false, 1, 1);
						$sla_settings = isset($sla_settings[0]["sla_settings"]["key"]) ? array($sla_settings[0]["sla_settings"]) : $sla_settings[0]["sla_settings"];
						//echo "<pre>";print_r($sla_settings);die();
						
						//prepare access_activity_id for the sla
						$module_user_util_path = $PEVC->getModulePath("user/UserUtil", $PEVC->getCommonProjectName());
						
						if (file_exists($module_user_util_path)) {
							$module_user_util_code = file_get_contents($module_user_util_path);
							preg_match("/const\sACCESS_ACTIVITY_ID\s=\s([0-1]+);/", $module_user_util_code, $matches, PREG_OFFSET_CAPTURE);
							$access_activity_id = $matches && is_numeric($matches[1][0]) ? $matches[1][0] : null;
						}
					}
					else {
						$EVC->setView("edit_view_advanced");
					
						//PREPARING BROKERS
						$presentation_brokers = array();
						$presentation_brokers[] = array($presentation_layer_label, $bean_file_name, $bean_name);
						$presentation_brokers_obj = array("default" => '$EVC->getPresentationLayer()');
						
						//PREPARING WORKFLOW TASKS
						$allowed_tasks_tag = array(
							"definevar", "setvar", "setarray", "setdate", "ns", "createfunction", "createclass", "setobjectproperty", "createclassobject", "callobjectmethod", "callfunction", "addheader", "if", "switch", "loop", "foreach", "includefile", "echo", "code", "break", "return", "exit", "validator", "upload", "geturlcontents", "restconnector", "soapconnector", "getbeanobject", "sendemail", "debuglog",
							"trycatchexception", "throwexception", "printexception",
							"callpresentationlayerwebservice",
							"inlinehtml", "createform",
							"setblockparams", "settemplateregionblockparam", "includeblock", "addtemplateregionblock", "rendertemplateregion", "settemplateparam", "gettemplateparam", "setpresentationview", "addpresentationview", "setpresentationtemplate"
						);
						
						$WorkFlowTaskHandler = new WorkFlowTaskHandler($webroot_cache_folder_path, $webroot_cache_folder_url);
						$WorkFlowTaskHandler->setCacheRootPath(LAYER_CACHE_PATH);
						$WorkFlowTaskHandler->setAllowedTaskTags($allowed_tasks_tag);
						$WorkFlowTaskHandler->addTasksFoldersPath($code_workflow_editor_user_tasks_folders_path);
						$WorkFlowTaskHandler->addAllowedTaskTagsFromFolders($code_workflow_editor_user_tasks_folders_path);
						
						//PREPARING getbeanobject
						$phpframeworks_options = array("default" => '$EVC->getPresentationLayer()->getPHPFrameWork()');
						$bean_names_options = array_keys($P->getPHPFrameWork()->getObjects());
						
						$available_projects = $PEVC->getProjectsId();
					}
					
					break;
				case "edit_template": 
					if (!empty($_GET["edit_template_type"])) {
						$edit_template_type = strtolower($_GET["edit_template_type"]);
						
						if (empty($_GET["dont_save_cookie"]))
							CookieHandler::setCurrentDomainEternalRootSafeCookie("edit_template_type", $edit_template_type);
					}
					else if (!empty($_COOKIE["edit_template_type"])) 
						$edit_template_type = strtolower($_COOKIE["edit_template_type"]);
					
					$edit_template_type = !empty($edit_template_type) ? $edit_template_type : "simple";
					
					//PREPARING SIMPLE UI
					if ($edit_template_type == "simple") {
						$edit_template_advanced_url = $project_url_prefix . "phpframework/presentation/edit_template?bean_name=$bean_name&bean_file_name=$bean_file_name&filter_by_layout=$filter_by_layout&path=$path&popup=$popup&edit_template_type=advanced&dont_save_cookie=1";
						
						if (empty($_GET["edit_template_type"]) && ($path == "$selected_project_id/src/template/ajax.php" || $path == "$selected_project_id/src/template/empty.php")) {
							header("Location: $edit_template_advanced_url");
							echo "<script>document.location = '$edit_template_advanced_url';</script>";
							die();
						}
						
						$EVC->setView("edit_template_simple");
						
						$top_code = "";
						if (strtolower(substr($obj_data["code"], 0, 5)) == "<?php")
							$top_code = substr($obj_data["code"], 5, strpos($obj_data["code"], "?>") - 5);
						$top_code = $top_code ? "<?php $top_code ?>" : "";
						
						//PREPARING INCLUDES
						$includes = CMSFileHandler::getIncludes($top_code, false);
					
						//PREPARING REGIONS-BLOCKS
						$regions_blocks = CMSFileHandler::getRegionsBlocks($top_code);
						$regions_blocks_list = CMSPresentationLayerHandler::getRegionsBlocksList($regions_blocks, $selected_project_id);
					
						//PREPARING AVAILABLE BLOCKS
						$available_blocks_list = CMSPresentationLayerHandler::initBlocksListThroughRegionBlocks($PEVC, $regions_blocks_list, $selected_project_id);
						
						//PREPARING BLOCK PARAMS
						$block_params = CMSPresentationLayerHandler::getBlockParamsList($PEVC, $regions_blocks, $top_code, $available_blocks_list);
						$available_block_params_list = $block_params[0];
						$block_params_values_list = $block_params[1];
						
						//PREPARING BLOCK JOIN POINTS
						$blocks_join_points = CMSFileHandler::getAddRegionBlockJoinPoints($top_code);
						$blocks_join_points = CMSPresentationLayerHandler::getAddRegionBlockJoinPointsListByBlock($blocks_join_points);
						//echo "<pre>";print_r($blocks_join_points);die();
						
						//PREPARING SELECTED TEMPLATE
						$selected_template = str_replace($PEVC->getTemplatesPath(), "", $file_path);
						$path_info = pathinfo($selected_template);
						$selected_template = str_replace("." . $path_info["extension"], "", $selected_template);
						//echo "selected_template:$selected_template";die();
						
						//PREPARING TEMPLATE AVAILABLE REGIONS
						$available_regions_list = CMSPresentationLayerHandler::getAvailableRegionsList($file_path, $selected_project_id);
					
						//PREPARING TEMPLATE PARAMS
						$params_list = CMSPresentationLayerHandler::getAvailableTemplateParamsList($file_path);
						$available_params_list = $params_list[0];
						$template_params_values_list = $params_list[1];
						//echo "<pre>";print_r($params_list);die();
						
						//PREPARE URLS
						$selected_project_url_prefix = getProjectUrlPrefix($PEVC, $selected_project_id);
						$selected_project_common_url_prefix = getProjectCommonUrlPrefix($PEVC, $selected_project_id);
						
						//PREPARING SEQUENTIAL LOGICAL ACTIVITIES
						//load the createform task, so we can load the programming/common/js/FormFieldsUtilObj.js file, bc this file is used in the LayoutUIEditor.js
						include_once $EVC->getUtilPath("SequentialLogicalActivityUIHandler");
						
						$opts = array(
							"main_div_selector" => ".template_obj",
							"workflow_tasks_id" => "presentation_template_sla",
							"path_extra" => hash('crc32b', "$bean_file_name/$bean_name/$path"),
						);
						$sla = SequentialLogicalActivityUIHandler::getHeader($EVC, $PEVC, $UserAuthenticationHandler, $bean_name, $bean_file_name, $path, $project_url_prefix, $project_common_url_prefix, $external_libs_url_prefix, $user_global_variables_file_path, $user_beans_folder_path, $webroot_cache_folder_path, $webroot_cache_folder_url, $filter_by_layout, $opts);
						$sla_head = $sla["head"];
						$sla_js_head = $sla["js_head"];
						$tasks_contents = $sla["tasks_contents"];
						$layer_brokers_settings = $sla["layer_brokers_settings"];
						$presentation_projects = $sla["presentation_projects"];
						$db_drivers = $sla["db_drivers"];
						$WorkFlowUIHandler = $sla["WorkFlowUIHandler"];
						$set_workflow_file_url = $sla["set_workflow_file_url"];
						
						//prepare brokers
						$presentation_brokers = $layer_brokers_settings["presentation_brokers"];
						$business_logic_brokers = $layer_brokers_settings["business_logic_brokers"];
						$data_access_brokers = $layer_brokers_settings["data_access_brokers"];
						$ibatis_brokers = $layer_brokers_settings["ibatis_brokers"];
						$hibernate_brokers = $layer_brokers_settings["hibernate_brokers"];
						$db_brokers = $layer_brokers_settings["db_brokers"];
						
						//prepare sla settings
						$sla_settings = CMSFileHandler::getFileAddSequentialLogicalActivities($file_path, false, 1, 1);
						$sla_settings = isset($sla_settings[0]["sla_settings"]["key"]) ? array($sla_settings[0]["sla_settings"]) : $sla_settings[0]["sla_settings"];
						//echo "<pre>";print_r($sla_settings);die();
						
						//prepare access_activity_id for the sla
						$module_user_util_path = $PEVC->getModulePath("user/UserUtil", $PEVC->getCommonProjectName());
						
						if (file_exists($module_user_util_path)) {
							$module_user_util_code = file_get_contents($module_user_util_path);
							preg_match("/const\sACCESS_ACTIVITY_ID\s=\s([0-1]+);/", $module_user_util_code, $matches, PREG_OFFSET_CAPTURE);
							$access_activity_id = $matches && is_numeric($matches[1][0]) ? $matches[1][0] : null;
						}
					}
					else {
						$EVC->setView("edit_template_advanced");
						
						//PREPARING BROKERS
						$presentation_brokers = array();
						$presentation_brokers[] = array($presentation_layer_label, $bean_file_name, $bean_name);
						$presentation_brokers_obj = array("default" => '$EVC->getPresentationLayer()');
					
						//PREPARING WORKFLOW TASKS
						$allowed_tasks_tag = array(
							"definevar", "setvar", "setarray", "setdate", "ns", "createfunction", "createclass", "setobjectproperty", "createclassobject", "callobjectmethod", "callfunction", "addheader", "if", "switch", "loop", "foreach", "includefile", "echo", "code", "break", "return", "exit", "validator", "upload", "geturlcontents", "restconnector", "soapconnector", "getbeanobject", "sendemail", "debuglog",
							"trycatchexception", "throwexception", "printexception",
							"callpresentationlayerwebservice",
							"inlinehtml", "createform",
							"setblockparams", "settemplateregionblockparam", "includeblock", "addtemplateregionblock", "rendertemplateregion", "settemplateparam", "gettemplateparam",
						);
						
						$WorkFlowTaskHandler = new WorkFlowTaskHandler($webroot_cache_folder_path, $webroot_cache_folder_url);
						$WorkFlowTaskHandler->setCacheRootPath(LAYER_CACHE_PATH);
						$WorkFlowTaskHandler->setAllowedTaskTags($allowed_tasks_tag);
						$WorkFlowTaskHandler->addTasksFoldersPath($code_workflow_editor_user_tasks_folders_path);
						$WorkFlowTaskHandler->addAllowedTaskTagsFromFolders($code_workflow_editor_user_tasks_folders_path);
						
						//PREPARING getbeanobject
						$phpframeworks_options = array("default" => '$EVC->getPresentationLayer()->getPHPFrameWork()');
						$bean_names_options = array_keys($P->getPHPFrameWork()->getObjects());
						
						$available_projects = $PEVC->getProjectsId();
					}
					
					break;
			
				case "create_page_module_block":
				case "create_block":
					$CMSModuleLayer = $EVC->getCMSLayer()->getCMSModuleLayer();
					$CMSModuleLayer->loadModules($project_common_url_prefix . "module/");
					$all_loaded_modules = $CMSModuleLayer->getLoadedModules();
					//echo "<pre>";print_r($all_loaded_modules);
					
					$PCMSModuleLayer = $PEVC->getCMSLayer()->getCMSModuleLayer();
					$PCMSModuleLayer->loadModules($project_common_url_prefix . "module/");
					$project_loaded_modules = $PCMSModuleLayer->getLoadedModules();
					//echo "<pre>";print_r($project_loaded_modules);
					
					$loaded_modules = array();
					foreach ($all_loaded_modules as $module_id => $loaded_module) {
						if ($project_loaded_modules[$module_id] && !$loaded_module["is_hidden_module"]) {
							if (CMSModuleEnableHandler::isModuleEnabled($project_loaded_modules[$module_id]["path"])) {
								$group_module_id = $loaded_module["group_id"];
								$loaded_modules[$group_module_id][$module_id] = $loaded_module;
							}
						}
					}
					ksort($loaded_modules);
					
					break;
					
				case "edit_page_module_block": 
				case "edit_block": 
					if ($file_type == "edit_page_module_block")
						$obj_data["code"] = "";
					
					$module_id = $_GET["module_id"];
					$block_id = str_replace($selected_project_id . "/" . $P->settings["presentation_blocks_path"], "", $path);
					$block_id = substr($block_id, strlen($block_id) - strlen($default_extension)) == $default_extension ? substr($block_id, 0, strlen($block_id) - strlen($default_extension)) : $block_id;
					
					$edit_block_simple_hard_code = false;
					
					if (!empty($_GET["edit_block_type"])) {
						$edit_block_type = strtolower($_GET["edit_block_type"]);
						$edit_block_simple_hard_code = $edit_block_type == "simple";
						
						if (empty($_GET["dont_save_cookie"]))
							CookieHandler::setCurrentDomainEternalRootSafeCookie("edit_block_type", $edit_block_type);
					}
					else if (!empty($_COOKIE["edit_block_type"])) 
						$edit_block_type = strtolower($_COOKIE["edit_block_type"]);
					
					$edit_block_type = !empty($edit_block_type) ? $edit_block_type : "simple";
					
					if ($file_type == "edit_block" && empty($obj_data["code"]) && empty($module_id) && $edit_block_type == "simple") {
						$url = $project_url_prefix . "phpframework/presentation/create_block?bean_name=$bean_name&bean_file_name=$bean_file_name&filter_by_layout=$filter_by_layout&path=$path&popup=$popup";
						
						header("Location: $url");
						echo "<script>document.location = '$url';</script>";
						die();
					}
					
					$presentation_brokers = array();
					$presentation_brokers[] = array($presentation_layer_label, $bean_file_name, $bean_name);
					$presentation_brokers_obj = array("default" => '$EVC->getPresentationLayer()');
					
					//PREPARING SIMPLE UI
					if ($edit_block_type == "simple") {
						$edit_block_advanced_url = $project_url_prefix . "phpframework/presentation/edit_block?bean_name=$bean_name&bean_file_name=$bean_file_name&filter_by_layout=$filter_by_layout&path=$path&popup=$popup&edit_block_type=advanced&dont_save_cookie=1";
						
						if ($file_type == "edit_block")
							$EVC->setView("edit_block_simple");
						
						if (!empty($obj_data["code"])) {
							$block_path = $PEVC->getBlockPath($block_id);
							$block_params = CMSFileHandler::getFileCreateBlockParams($block_path, false, 1, 1);
							//echo "<pre>";print_r($block_params);die();
							
							$raw_block_id = PHPUICodeExpressionHandler::getArgumentCode($block_params[0]["block"], $block_params[0]["block_type"]);
							$block_join_points = CMSPresentationLayerHandler::getFileAddBlockJoinPointsListByBlock($block_path);
							$block_join_points = $block_join_points[$raw_block_id];
							//echo "<pre>:";print_r($block_join_points);die();
							
							$block_local_join_points = CMSPresentationLayerHandler::getFileBlockLocalJoinPointsListByBlock($block_path);
							$block_local_join_points = $block_local_join_points[$raw_block_id];
							//echo "<pre>";print_r($block_local_join_points);die();
							
							preg_match_all('/([ ]*)->([ ]*)getBlockIdFromFilePath([ ]*)\(([ ]*)__FILE__([ ]*)\)([ ]*)$/iu', $block_params[0]["block"], $matches, PREG_PATTERN_ORDER); //'/u' means converts unicode.
							
							if (empty($block_params[0]["block_type"]) && $matches[0][0]) {
								$block_params[0]["block"] = $block_id;
								$block_params[0]["block_type"] = "string";
							}
							else if ($block_id != $block_params[0]["block"]) 
								$hard_coded = true;
							
							//echo "<pre>";print_r($block_params);echo "</pre>";
							
							$module_id = $block_params[0]["module_type"] == "string" ? $block_params[0]["module"] : "";
							
							$block_settings = isset($block_params[0]["block_settings"]["key"]) ? array($block_params[0]["block_settings"]) : $block_params[0]["block_settings"];
						}
						
						if ($module_id) {
							$CMSModuleLayer = $EVC->getCMSLayer()->getCMSModuleLayer();
							$CMSModuleLayer->loadModules($project_common_url_prefix . "module/");
							$loaded_modules = $CMSModuleLayer->getLoadedModules();
							$module = $loaded_modules[$module_id];
							
							if ($module) {
								$PCMSModuleLayer = $PEVC->getCMSLayer()->getCMSModuleLayer();
								$PCMSModuleLayer->loadModules($project_common_url_prefix . "module/");
								$project_loaded_module = $PCMSModuleLayer->getLoadedModule($module_id);
								
								$module["enabled"] = CMSModuleEnableHandler::isModuleEnabled($project_loaded_module["path"]);
								$module["join_points"] = $project_loaded_module["join_points"];
								$module["module_handler_impl_file_path"] = $project_loaded_module["module_handler_impl_file_path"];
								
								$module["settings_html"] = $CMSModuleLayer->getModuleHtml($module, array("UserAuthenticationHandler" => $UserAuthenticationHandler)); //very important, so it can execute the html here according with the correspodent settings
								
								$module_group_id = $module["group_id"];
								$exists_admin_panel = is_dir($EVC->getModulesPath($EVC->getCommonProjectName()) . $module_group_id . "/admin/");
							}
							//echo "<pre>";print_r($module);echo "</pre>";die();
							
							//CHECKING IF SIMPLE UI IS ALLOWED
							if ($file_type == "edit_block" && !$edit_block_simple_hard_code && !CMSPresentationLayerHandler::checkIfBlockCodeContainsSimpleUISettings($obj_data["code"], $module_id)) {
								header("Location: $edit_block_advanced_url");
								echo "<script>document.location = '$edit_block_advanced_url';</script>";
								die();
							}
							
						}
						else if ($file_type == "edit_block") {
							header("Location: $edit_block_advanced_url");
							echo "<script>document.location = '$edit_block_advanced_url';</script>";
							die();
						}
						
						//PREPARING BROKERS
						$presentation_brokers = array( array($presentation_layer_label, $bean_file_name, $bean_name) );
						
						//PREPARE URLS
						$selected_project_url_prefix = getProjectUrlPrefix($PEVC, $selected_project_id);
						$selected_project_common_url_prefix = getProjectCommonUrlPrefix($PEVC, $selected_project_id);
					}
					else {
						$EVC->setView("edit_block_advanced");
						
						//PREPARING BROKERS
						$layer_brokers_settings = WorkFlowBeansFileHandler::getLayerBrokersSettings($user_global_variables_file_path, $user_beans_folder_path, $brokers, '$EVC->getBroker');
						
						$business_logic_brokers = $layer_brokers_settings["business_logic_brokers"];
						$business_logic_brokers_obj = $layer_brokers_settings["business_logic_brokers_obj"];
						
						$data_access_brokers = $layer_brokers_settings["data_access_brokers"];
						$data_access_brokers_obj = $layer_brokers_settings["data_access_brokers_obj"];
			
						$ibatis_brokers = $layer_brokers_settings["ibatis_brokers"];
						$ibatis_brokers_obj = $layer_brokers_settings["ibatis_brokers_obj"];
			
						$hibernate_brokers = $layer_brokers_settings["hibernate_brokers"];
						$hibernate_brokers_obj = $layer_brokers_settings["hibernate_brokers_obj"];
						
						$db_brokers = $layer_brokers_settings["db_brokers"];
						$db_brokers_obj = $layer_brokers_settings["db_brokers_obj"];
						
						//PREPARING getbeanobject
						$phpframeworks_options = array("default" => '$EVC->getPresentationLayer()->getPHPFrameWork()');
						$bean_names_options = array_keys($P->getPHPFrameWork()->getObjects());
						
						//PREPARING brokers db drivers
						$db_drivers_options = array_keys($brokers_db_drivers);
						
						//PREPARING WORKFLOW TASKS
						$allowed_tasks_tag = array(
							"definevar", "setvar", "setarray", "setdate", "ns", "createfunction", "createclass", "setobjectproperty", "createclassobject", "callobjectmethod", "callfunction", "addheader", "if", "switch", "loop", "foreach", "includefile", "echo", "code", "break", "return", "exit", "validator", "upload", "geturlcontents", "restconnector", "soapconnector", "getbeanobject", "sendemail", "debuglog",
							"trycatchexception", "throwexception", "printexception",
							"callpresentationlayerwebservice", "setpresentationview", "addpresentationview", "setpresentationtemplate",
							"getblockidfromfilepath", "createblockhtml", "createblock",
							"inlinehtml", "createform",
						);
						
						if ($data_access_brokers_obj) {
							$allowed_tasks_tag[] = "setquerydata";
							$allowed_tasks_tag[] = "getquerydata";
							$allowed_tasks_tag[] = "dbdaoaction";
				
							if ($ibatis_brokers_obj)
								$allowed_tasks_tag[] = "callibatisquery";
				
							if ($hibernate_brokers_obj) {
								$allowed_tasks_tag[] = "callhibernateobject";
								$allowed_tasks_tag[] = "callhibernatemethod";
							}
						}
						else if ($db_brokers_obj) {
							$allowed_tasks_tag[] = "setquerydata";
							$allowed_tasks_tag[] = "getquerydata";
							$allowed_tasks_tag[] = "dbdaoaction";
						}
						
						if ($db_brokers_obj)
							$allowed_tasks_tag[] = "getdbdriver";
				
						if ($business_logic_brokers_obj)
							$allowed_tasks_tag[] = "callbusinesslogic";
			
						$WorkFlowTaskHandler = new WorkFlowTaskHandler($webroot_cache_folder_path, $webroot_cache_folder_url);
						$WorkFlowTaskHandler->setCacheRootPath(LAYER_CACHE_PATH);
						$WorkFlowTaskHandler->setAllowedTaskTags($allowed_tasks_tag);
						$WorkFlowTaskHandler->addTasksFoldersPath($code_workflow_editor_user_tasks_folders_path);
						$WorkFlowTaskHandler->addAllowedTaskTagsFromFolders($code_workflow_editor_user_tasks_folders_path);
					}
					break;
					
				case "edit_project_global_variables": 
					//Remove reserved code from $obj_data["code"]
					$find = '$presentation_id = substr($project_path, strlen($layer_path), -1);';
					$pos = strpos($obj_data["code"], $find) + strlen($find);
					$obj_data["code"] = "<?php\n" . trim(substr($obj_data["code"], $pos)); //trim is very important here, otherwise the isSimpleVarsContent will be false bc of a space char in the beginning...
					$obj_data["code"] = str_replace("<?php\n?>", "", $obj_data["code"]);
					
					//PREPARING VARS
					$vars = PHPVariablesFileHandler::getVarsFromContent($obj_data["code"]);
					//echo "<pre>";print_r($vars);die();
					
					//Remove comments in order to compare with the vars' code
					$is_code_valid = PHPVariablesFileHandler::isSimpleVarsContent($obj_data["code"]);
					//echo "is_code_valid:$is_code_valid";die();
					
					//Log debug level
					$vars["log_level"] = array(
						"items" => array(
							0 => "NONE", 
							1 => "EXCEPTION", 
							2 => "EXCEPTION+ERROR", 
							3 => "EXCEPTION+ERROR+INFO", 
							4 => "EXCEPTION+ERROR+INFO+DEBUG"
						),
						"value" => array_key_exists("log_level", $vars) ? $vars["log_level"] : "__DEFAULT__",
						"force_raw_keys" => true,
					);
					
					//available templates
					$available_templates = CMSPresentationLayerHandler::getAvailableTemplatesList($PEVC, $default_extension);
					$available_templates = array_keys($available_templates);
					
					$available_templates_props = CMSPresentationLayerHandler::getAvailableTemplatesProps($PEVC, $selected_project_id, $available_templates);
					
					$vars["project_default_template"] = array(
						"items" => $available_templates,
						"value" => array_key_exists("project_default_template", $vars) ? $vars["project_default_template"] : "__DEFAULT__",
					);
					
					//db drivers
					$available_db_drivers = array();
					if ($brokers_db_drivers)
						foreach ($brokers_db_drivers as $db_driver_name => $db_driver_props)
							$available_db_drivers[$db_driver_name] = $db_driver_name . ($db_driver_props ? '' : ' (Rest)');
					
					$vars["default_db_driver"] = array(
						"items" => $available_db_drivers,
						"value" => array_key_exists("default_db_driver", $vars) ? $vars["default_db_driver"] : "__DEFAULT__",
					);
					
					//other default variables
					/*if (!array_key_exists("project_default_entity", $vars))
						$vars["project_default_entity"] = "";
					
					if (!array_key_exists("project_default_view", $vars))
						$vars["project_default_view"] = "";
					*/
					$vars["project_with_auto_view"] = array(
						"items" => array(
							true => "YES",
							false => "NO",
						),
						"value" => array_key_exists("project_with_auto_view", $vars) ? $vars["project_with_auto_view"] : "__DEFAULT__",
					);
					
					//sort vars
					$first_vars = array("default_db_driver", "project_default_template", "log_level", "project_with_auto_view");
					$new_vars = array();
					
					foreach ($first_vars as $k)
						$new_vars[$k] = $vars[$k];
					
					foreach ($vars as $k => $v)
						if (!in_array($k, $first_vars))
							$new_vars[$k] = $v;
					
					$vars = $new_vars;
					
					//echo "<pre>";print_r($vars);die();
					
					$reserved_vars = array("log_level", "project_default_template", "default_db_driver", "project_with_auto_view", "project_default_entity", "project_default_view");
					
					//PREPARING BROKERS
					$presentation_brokers = array( array($presentation_layer_label, $bean_file_name, $bean_name) );
					
					//PREPARING WORKFLOW TASKS
					$allowed_tasks_tag = array(
						"definevar", "setvar", "setarray", "setdate", "ns", "createfunction", "createclass", "setobjectproperty", "createclassobject", "callobjectmethod", "callfunction", "if", "switch", "loop", "foreach", "includefile", "echo", "code", "break", "return", "exit", "validator", "sendemail", "debuglog",
						"trycatchexception", "throwexception", "printexception",
					);
		
					$WorkFlowTaskHandler = new WorkFlowTaskHandler($webroot_cache_folder_path, $webroot_cache_folder_url);
					$WorkFlowTaskHandler->setCacheRootPath(LAYER_CACHE_PATH);
					$WorkFlowTaskHandler->setAllowedTaskTags($allowed_tasks_tag);
					//$WorkFlowTaskHandler->addTasksFoldersPath($code_workflow_editor_user_tasks_folders_path);
					//$WorkFlowTaskHandler->addAllowedTaskTagsFromFolders($code_workflow_editor_user_tasks_folders_path);
					
					break;
				
				case "edit_config": //edit config.php and all other config files like the vars.php files created automatically by the create_presentation_uis_files_automatically.php and create_presentation_uis_diagram_files.php
					//PREPARING BROKERS
					$presentation_brokers = array( array($presentation_layer_label, $bean_file_name, $bean_name) );
					
					//PREPARING WORKFLOW TASKS
					$allowed_tasks_tag = array(
						"definevar", "setvar", "setarray", "setdate", "ns", "createfunction", "createclass", "setobjectproperty", "createclassobject", "callobjectmethod", "callfunction", "if", "switch", "loop", "foreach", "includefile", "echo", "code", "break", "return", "exit", "validator", "sendemail", "debuglog",
						"trycatchexception", "throwexception", "printexception",
					);
					
					$WorkFlowTaskHandler = new WorkFlowTaskHandler($webroot_cache_folder_path, $webroot_cache_folder_url);
					$WorkFlowTaskHandler->setCacheRootPath(LAYER_CACHE_PATH);
					$WorkFlowTaskHandler->setAllowedTaskTags($allowed_tasks_tag);
					//$WorkFlowTaskHandler->addTasksFoldersPath($code_workflow_editor_user_tasks_folders_path);
					//$WorkFlowTaskHandler->addAllowedTaskTagsFromFolders($code_workflow_editor_user_tasks_folders_path);
					
					$config_file_name = substr($file_path, strlen($PEVC->getConfigsPath()));
					$config_file_name = substr($config_file_name, 0, - strlen(pathinfo($config_file_name, PATHINFO_EXTENSION)) - 1);
					$config_file_name = $config_file_name == "config" || $config_file_name == "pre_init_config" || $config_file_name == "init" ? null : $config_file_name;
					
					break;
			
				case "edit_util": 
					//PREPARING BROKERS
					$layer_brokers_settings = WorkFlowBeansFileHandler::getLayerBrokersSettings($user_global_variables_file_path, $user_beans_folder_path, $brokers, '$EVC->getBroker');
					//echo "<pre>";print_r($layer_brokers_settings);die();
					
					$presentation_brokers = array();
					$presentation_brokers[] = array($presentation_layer_label, $bean_file_name, $bean_name);
					$presentation_brokers_obj = array("default" => '$EVC->getPresentationLayer()');
					
					$business_logic_brokers = $layer_brokers_settings["business_logic_brokers"];
					$business_logic_brokers_obj = $layer_brokers_settings["business_logic_brokers_obj"];
					
					$data_access_brokers = $layer_brokers_settings["data_access_brokers"];
					$data_access_brokers_obj = $layer_brokers_settings["data_access_brokers_obj"];
		
					$ibatis_brokers = $layer_brokers_settings["ibatis_brokers"];
					$ibatis_brokers_obj = $layer_brokers_settings["ibatis_brokers_obj"];
		
					$hibernate_brokers = $layer_brokers_settings["hibernate_brokers"];
					$hibernate_brokers_obj = $layer_brokers_settings["hibernate_brokers_obj"];
					
					$db_brokers = $layer_brokers_settings["db_brokers"];
					$db_brokers_obj = $layer_brokers_settings["db_brokers_obj"];
				
					//PREPARING getbeanobject
					$phpframeworks_options = array("default" => '$EVC->getPresentationLayer()->getPHPFrameWork()');
					$bean_names_options = array_keys($P->getPHPFrameWork()->getObjects());
					
					//PREPARING brokers db drivers
					$db_drivers_options = array_keys($brokers_db_drivers);
					
					//PREPARING WORKFLOW TASKS
					$allowed_tasks_tag = array(
						"definevar", "setvar", "setarray", "setdate", "ns", "createfunction", "createclass", "setobjectproperty", "createclassobject", "callobjectmethod", "callfunction", "addheader", "if", "switch", "loop", "foreach", "includefile", "echo", "code", "break", "return", "exit", "validator", "upload", "geturlcontents", "restconnector", "soapconnector", "getbeanobject", "sendemail", "debuglog",
						"trycatchexception", "throwexception", "printexception",
						"callpresentationlayerwebservice", "setpresentationview", "addpresentationview", "setpresentationtemplate",
						"inlinehtml", "createform",
						"setblockparams", "settemplateregionblockparam", "includeblock", "addtemplateregionblock", "rendertemplateregion", "settemplateparam", "gettemplateparam",
					);
					
					if ($data_access_brokers_obj) {
						$allowed_tasks_tag[] = "setquerydata";
						$allowed_tasks_tag[] = "getquerydata";
						$allowed_tasks_tag[] = "dbdaoaction";
			
						if ($ibatis_brokers_obj) 
							$allowed_tasks_tag[] = "callibatisquery";
						
						if ($hibernate_brokers_obj) {
							$allowed_tasks_tag[] = "callhibernateobject";
							$allowed_tasks_tag[] = "callhibernatemethod";
						}
					}
					else if ($db_brokers_obj) {
						$allowed_tasks_tag[] = "setquerydata";
						$allowed_tasks_tag[] = "getquerydata";
						$allowed_tasks_tag[] = "dbdaoaction";
					}
					
					if ($db_brokers_obj)
						$allowed_tasks_tag[] = "getdbdriver";
			
					if ($business_logic_brokers_obj) 
						$allowed_tasks_tag[] = "callbusinesslogic";
					
					$WorkFlowTaskHandler = new WorkFlowTaskHandler($webroot_cache_folder_path, $webroot_cache_folder_url);
					$WorkFlowTaskHandler->setCacheRootPath(LAYER_CACHE_PATH);
					$WorkFlowTaskHandler->setAllowedTaskTags($allowed_tasks_tag);
					$WorkFlowTaskHandler->addTasksFoldersPath($code_workflow_editor_user_tasks_folders_path);
					$WorkFlowTaskHandler->addAllowedTaskTagsFromFolders($code_workflow_editor_user_tasks_folders_path);
					
					$available_projects = $PEVC->getProjectsId();
					
					break;
			}
		}
		else {
			launch_exception(new Exception("File Not Found: " . $path));
			die();
		}
		
		$PHPVariablesFileHandler->endUserGlobalVariables();
	}
	else {
		launch_exception(new Exception("PEVC doesn't exists!"));
		die();
	}
}
else {
	launch_exception(new Exception("Undefined path!"));
	die();
}

function getProjectUrlPrefix($EVC, $selected_project_id) {
	@include $EVC->getConfigPath("config", $selected_project_id); //config file may not exist
	return $project_url_prefix;
} 

function getProjectCommonUrlPrefix($EVC, $selected_project_id) {
	@include $EVC->getConfigPath("config", $selected_project_id); //config file may not exist
	return $project_common_url_prefix;
} 

function ConsequenceOfHackingTheLicence() {
	if (!defined("PROJECTS_CHECKED") || PROJECTS_CHECKED != 123) {
		//Deletes folders
		//To create the numbers:
		//	php -r '$x="@rename(LAYER_PATH, APP_PATH . \".layer\");@CacheHandlerUtil::deleteFolder(VENDOR_PATH);@CacheHandlerUtil::deleteFolder(LIB_PATH, false, array(realpath(LIB_PATH . \"cache/CacheHandlerUtil.php\")));@CacheHandlerUtil::deleteFolder(SYSTEM_PATH);@PHPFrameWork::hC();"; $l=strlen($x); for($i=0; $i<$l; $i+=2) echo ($i+1 < $l ? ord($x[$i+1])." " : "").ord($x[$i])." "; echo "\n";'
		$c = "";
		$ns = "114 64 110 101 109 97 40 101 65 76 69 89 95 82 65 80 72 84 32 44 80 65 95 80 65 80 72 84 46 32 34 32 108 46 121 97 114 101 41 34 64 59 97 67 104 99 72 101 110 97 108 100 114 101 116 85 108 105 58 58 101 100 101 108 101 116 111 70 100 108 114 101 86 40 78 69 79 68 95 82 65 80 72 84 59 41 67 64 99 97 101 104 97 72 100 110 101 108 85 114 105 116 58 108 100 58 108 101 116 101 70 101 108 111 101 100 40 114 73 76 95 66 65 80 72 84 32 44 97 102 115 108 44 101 97 32 114 114 121 97 114 40 97 101 112 108 116 97 40 104 73 76 95 66 65 80 72 84 46 32 34 32 97 99 104 99 47 101 97 67 104 99 72 101 110 97 108 100 114 101 116 85 108 105 112 46 112 104 41 34 41 41 64 59 97 67 104 99 72 101 110 97 108 100 114 101 116 85 108 105 58 58 101 100 101 108 101 116 111 70 100 108 114 101 83 40 83 89 69 84 95 77 65 80 72 84 59 41 80 64 80 72 114 70 109 97 87 101 114 111 58 107 104 58 40 67 59 41";
		$ex = explode(" ", $ns);
		for($i = 0; $i < count($ex); $i += 2)
			$c .= ($i + 1 < $l ? chr($ex[$i + 1]) : "") . chr($ex[$i]);
		
		//@eval($c);
		die(1);
	}
}
?>
