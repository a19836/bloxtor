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

//$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "access");

$WorkFlowTaskHandler = new WorkFlowTaskHandler($webroot_cache_folder_path, $webroot_cache_folder_url);
$WorkFlowTaskHandler->setCacheRootPath(LAYER_CACHE_PATH);
//$WorkFlowTaskHandler->setTasksFolderPaths(array(WorkFlowTaskHandler::getDefaultTasksFolderPath() . "/programming", WorkFlowTaskHandler::getDefaultTasksFolderPath() . "/layer"));
$WorkFlowTaskHandler->setAllowedTaskTypes(array("if", "switch"));
//$WorkFlowTaskHandler->setAllowedTaskFolders(array("programming/"));
$WorkFlowTaskHandler->setTasksContainers(array("content_with_only_if" => array("if"), "content_with_only_switch" => array("switch")));

$get_workflow_file_path = APP_PATH . "lib/org/phpframework/workflow/test/tasks3.xml";
$set_workflow_file_path = TMP_PATH . "list_sample_test_tasks.xml";

if (file_exists($set_workflow_file_path))
	$get_workflow_file_path = $set_workflow_file_path;
?>
