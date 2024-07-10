<?php
//http://jplpinto.localhost/__system/test/tests/workflow

include_once get_lib("org.phpframework.workflow.WorkFlowTaskHandler");

//$task_folder_path = dirname(get_lib("org.phpframework.workflow.task.CallBusinessLogicWorkFlowTask"));

$webroot_cache_folder_path = $EVC->getPresentationLayer()->getSelectedPresentationSetting("presentation_webroot_path") . "__system/cache/";
$webroot_cache_folder_url = $project_url_prefix . "test/__system/cache/";

$WorkFlowTaskHandler = new WorkFlowTaskHandler($webroot_cache_folder_path, $webroot_cache_folder_url);
$WorkFlowTaskHandler->setCacheRootPath(LAYER_CACHE_PATH);
//$WorkFlowTaskHandler->setTasksFolderPaths(array(normalize_windows_path_to_linux(__DIR__) . "/../../../../../../../lib/org/phpframework/workflow/task/layer/"));
//$WorkFlowTaskHandler->setAllowedTaskFolders(array("default/layer/"));
$WorkFlowTaskHandler->flushCache();
$WorkFlowTaskHandler->initWorkFlowTasks();
//$settings = $WorkFlowTaskHandler->getLoadedTasksSettings();

//$file_path = get_lib("org.phpframework.workflow.test.tasks1", "xml");
//$file_path = get_lib("org.phpframework.workflow.test.tasks2", "xml");
//$file_path = "/tmp/workflow.xml";

$file_path = get_lib("org.phpframework.workflow.test.tasks3", "xml");//this should be used when we DON'T call the setTasksFolderPaths above.
$WorkFlowTaskHandler->initWorkFlowTasks();
//print_r($WorkFlowTaskHandler->getLoadedTasks());
$code = $WorkFlowTaskHandler->parseFile($file_path);
echo "<h1>PRINTED CODE - CASE 1</h1>";
echo "<pre>\n" . $code . "\n</pre>";

$WorkFlowTaskHandler->setTasksFolderPaths(array(WorkFlowTaskHandler::getDefaultTasksFolderPath() . "/programming", normalize_windows_path_to_linux(__DIR__) . "/../../../../../../../lib/org/phpframework/workflow/task/layer/"));
$WorkFlowTaskHandler->setAllowedTaskTypes(array("if", "switch", "presentation"));
$file_path = get_lib("org.phpframework.workflow.test.tasks3_only_if_and_switch", "xml");//this should be used when we call the setTasksFolderPaths above
$WorkFlowTaskHandler->initWorkFlowTasks();
//print_r($WorkFlowTaskHandler->getLoadedTasks());
$code = $WorkFlowTaskHandler->parseFile($file_path);
echo "<h1>PRINTED CODE - CASE 2</h1>";
echo "<pre>\n" . $code . "\n</pre>";

$WorkFlowTaskHandler->setTasksFolderPaths(array(normalize_windows_path_to_linux(__DIR__) . "/../../../../../../../lib/org/phpframework/workflow/task/programming/"));
$WorkFlowTaskHandler->setAllowedTaskTypes(array("if", "switch"));
$file_path = get_lib("org.phpframework.workflow.test.tasks3_only_if_and_switch2", "xml");//this should be used when we call the setTasksFolderPaths with ../../../../programming/
$WorkFlowTaskHandler->initWorkFlowTasks();
//print_r($WorkFlowTaskHandler->getLoadedTasks());
$code = $WorkFlowTaskHandler->parseFile($file_path);
echo "<h1>PRINTED CODE - CASE 3</h1>";
echo "<pre>\n" . $code . "\n</pre>";
?>
