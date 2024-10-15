<?php
$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "access");

$bean_name = isset($_GET["bean_name"]) ? $_GET["bean_name"] : null;
$bean_file_name = isset($_GET["bean_file_name"]) ? $_GET["bean_file_name"] : null;
$parent_entity_file_path = isset($_GET["path"]) ? $_GET["path"] : null;
$task_tag = isset($_GET["task_tag"]) ? $_GET["task_tag"] : null;
$task_tag_action = isset($_GET["task_tag_action"]) ? $_GET["task_tag_action"] : null;
$db_driver = isset($_GET["db_driver"]) ? $_GET["db_driver"] : null;
$db_type = isset($_GET["db_type"]) ? $_GET["db_type"] : null;
$db_table = isset($_GET["db_table"]) ? $_GET["db_table"] : null;
$parent_add_block_func = isset($_GET["parent_add_block_func"]) ? $_GET["parent_add_block_func"] : null;
$popup = isset($_GET["popup"]) ? $_GET["popup"] : null;

$parent_entity_file_path = str_replace("../", "", $parent_entity_file_path);//for security reasons

if (!$parent_add_block_func) {
	echo "parent_add_block_func missing";
	die();
}

//prepare new folder path and new $_GET["path"] for entity/presentation/create_presentation_uis_diagram.php
if ($bean_name && $parent_entity_file_path) {
	$new_path = dirname($parent_entity_file_path) . "/" . pathinfo($parent_entity_file_path, PATHINFO_FILENAME) . "/";
	$_GET["path"] = $new_path;
}

$do_not_load_or_save_workflow = true;
$do_not_save_vars_file = true;
$do_not_check_if_path_exists = true;

$task_tag_action = str_replace(array(";", "|"), ",", $task_tag_action);
$task_tag_action = explode(",", $task_tag_action);

include $EVC->getEntityPath("presentation/create_presentation_uis_diagram");
?>
