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

include_once get_lib("org.phpframework.workflow.WorkFlowTaskHandler");
include_once get_lib("org.phpframework.layer.presentation.cms.module.CMSProgramInstallationHandler");
include_once get_lib("org.phpframework.layer.presentation.cms.module.CMSModuleInstallationHandler");
include_once get_lib("org.phpframework.layer.presentation.cms.module.CMSTemplateInstallationHandler");
include_once get_lib("org.phpframework.layer.presentation.cms.module.CMSEntityInstallationHandler");
include_once $EVC->getUtilPath("CMSPresentationLayerHandler");

class FlushCacheHandler {
	
	public static function flushCache($EVC, $webroot_cache_folder_path, $webroot_cache_folder_url, $workflow_paths_id, $user_global_variables_file_path, $user_beans_folder_path, $css_and_js_optimizer_webroot_cache_folder_path, $deployments_temp_folder_path, &$errors = null) {
		if (!is_array($errors))
			$errors = array();
		
		//Delete workflows in LAYER_CACHE_PATH and in app/__system/layer/presentation/phpframework/webroot/__system/cache/
		$WorkFlowTaskHandler = new WorkFlowTaskHandler($webroot_cache_folder_path, $webroot_cache_folder_url);
		$WorkFlowTaskHandler->setCacheRootPath(LAYER_CACHE_PATH);
		$status = $WorkFlowTaskHandler->flushCache();
		
		try {
			//Delete test workflows cache in app/__system/layer/presentation/test/webroot/__system/cache/
			CacheHandlerUtil::deleteFolder( $EVC->getWebrootPath("test") . "__system/cache/" ); //This must be inside of the try-catch bc this project may not exists.
		}
		catch (Exception $e) {
			//in case the __system/test project doesn't exists, it won't give error
		}
		
		//Delete css and js optimizer cache in app/__system/layer/presentation/phpframework/webroot/__system/cache/
		CacheHandlerUtil::deleteFolder($css_and_js_optimizer_webroot_cache_folder_path);
		
		//Delete jquerylayoutuieditor and all other cache in app/__system/layer/presentation/phpframework/webroot/__system/cache/
		CacheHandlerUtil::deleteFolder($webroot_cache_folder_path, false);
		
		//Delete tmp workflows
		!empty($workflow_paths_id["business_logic_workflow_tmp"]) && CacheHandlerUtil::deleteFolder( dirname($workflow_paths_id["business_logic_workflow_tmp"]) );
		!empty($workflow_paths_id["presentation_workflow_tmp"]) && CacheHandlerUtil::deleteFolder( dirname($workflow_paths_id["presentation_workflow_tmp"]) );
		!empty($workflow_paths_id["presentation_block_workflow_tmp"]) && CacheHandlerUtil::deleteFolder( dirname($workflow_paths_id["presentation_block_workflow_tmp"]) );
		!empty($workflow_paths_id["presentation_block_form_tmp"]) && CacheHandlerUtil::deleteFolder( dirname($workflow_paths_id["presentation_block_form_tmp"]) );
		
		//Delete generic CACHE_PATH
		CacheHandlerUtil::deleteFolder(CACHE_PATH, false);
		
		//Delete user and projects tmp folders
		$cache_relative_path = substr(CACHE_PATH, strlen(TMP_PATH));
		
		$user_tmp_path = self::getUserTmpPath($user_global_variables_file_path);
		if ($user_tmp_path)
			CacheHandlerUtil::deleteFolder("$user_tmp_path/$cache_relative_path");
		
		$error_reporting = error_reporting();
		error_reporting(0);
		
		try {
			$files = CMSPresentationLayerHandler::getPresentationLayersProjectsFiles($user_global_variables_file_path, $user_beans_folder_path);
			if (is_array($files))
				foreach ($files as $layer_name => $layer_props)
					if (isset($layer_props["projects"]) && is_array($layer_props["projects"])) {
						$bean_file_name = isset($layer_props["bean_file_name"]) ? $layer_props["bean_file_name"] : null;
						$WorkFlowBeansFileHandler = new WorkFlowBeansFileHandler($user_beans_folder_path . $bean_file_name, $user_global_variables_file_path);
						$PEVC = $WorkFlowBeansFileHandler->getEVCBeanObject($layer_name);
					
						foreach ($layer_props["projects"] as $project_name => $project_props) {
							$user_tmp_path = self::getUserTmpPath(array($user_global_variables_file_path, $PEVC->getConfigPath("pre_init_config", $project_name)));
							
							if ($user_tmp_path)
								CacheHandlerUtil::deleteFolder("$user_tmp_path/$cache_relative_path");
						}
					}
		}
     	catch (Throwable $e) {
			$errors[] = $e->getMessage();
			launch_exception($e);
			$status = false;
     	}
		
		error_reporting($error_reporting);
		
		//Delete old deployments
		if (is_dir($deployments_temp_folder_path)) {
			$files = array_diff(scandir($deployments_temp_folder_path), array('..', '.'));
			
			foreach ($files as $file) {
				$fp = $deployments_temp_folder_path . $file;
				
				if (filemtime($fp) < time() - (60 * 60 * 24 * 3)) { //3 days old
					if (is_dir($fp))
						CacheHandlerUtil::deleteFolder($fp);
					else 
						unlink($fp);
				}
			}
		}
		
		//Delete old programs
		$temp_folders_path = array(
			CMSModuleInstallationHandler::getTmpRootFolderPath(), 
			CMSTemplateInstallationHandler::getTmpRootFolderPath(), 
			CMSProgramInstallationHandler::getTmpRootFolderPath(), 
			CMSEntityInstallationHandler::getTmpRootFolderPath()
		);
		
		foreach ($temp_folders_path as $temp_folder_path)
			if (is_dir($temp_folder_path)) {
				$files = array_diff(scandir($temp_folder_path), array('..', '.'));
				
				foreach ($files as $file) {
					$fp = $temp_folder_path . $file;
					
					if (filemtime($fp) < time() - (60 * 60 * 24)) { //24h old
						if (is_dir($fp))
							CacheHandlerUtil::deleteFolder($fp);
						else 
							unlink($fp);
					}
				}
			}
		
		return $status;
	}
	
	private static function getUserTmpPath($user_global_variables_file_path) {
		$PHPVariablesFileHandler = new PHPVariablesFileHandler($user_global_variables_file_path);
		$PHPVariablesFileHandler->startUserGlobalVariables();
		$user_tmp_path = isset($GLOBALS["tmp_path"]) ? $GLOBALS["tmp_path"] : null;
		$PHPVariablesFileHandler->endUserGlobalVariables();
		
		return $user_tmp_path;
	}
}
?>
