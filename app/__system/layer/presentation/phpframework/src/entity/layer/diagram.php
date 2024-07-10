<?php
include_once get_lib("org.phpframework.workflow.WorkFlowTaskHandler");

$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "access");

//Note that this code will be used in the setup/layers.php too

$containers = array(
	"layer_presentations" => array("presentation"),
	"layer_bls" => array("businesslogic"),
	"layer_dals" => array("dataaccess"), 
	"layer_dbs" => array("db"), 
	"layer_drivers" => array("dbdriver"),
);

$tasks_order_by_tag = array("presentation", "businesslogic", "dataaccess", "db", "dbdriver");

$WorkFlowTaskHandler = new WorkFlowTaskHandler($webroot_cache_folder_path, $webroot_cache_folder_url);
$WorkFlowTaskHandler->setCacheRootPath(LAYER_CACHE_PATH);
$WorkFlowTaskHandler->setAllowedTaskFolders(array("layer/"));
$WorkFlowTaskHandler->setTasksContainers($containers);

$workflow_path_id = "layer";

$diagram_already_exists = file_exists($workflow_paths_id[ $workflow_path_id ]);

if (!$diagram_already_exists) {
	$content = file_get_contents($EVC->getPresentationLayer()->getSelectedPresentationSetting("presentation_webroot_path") . "/assets/default_layers_workflow_with_db.xml");
	$content = str_replace("\$db_type", "mysql", $content);
	$content = str_replace("\$db_encoding", "utf8", $content);
	$content = str_replace("\$driver_label", "mysql", $content);
	$content = str_replace(array("\$db_extension", "\$db_host", "\$db_port", "\$db_name", "\$db_username", "\$db_password", "\$db_odbc_data_source", "\$db_odbc_driver", "\$db_extra_dsn"), "", $content);
	file_put_contents($workflow_paths_id[ $workflow_path_id ], $content);
}
?>
