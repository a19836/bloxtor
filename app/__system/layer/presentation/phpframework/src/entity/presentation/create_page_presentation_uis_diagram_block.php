<?php
$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "access");

$bean_name = $_GET["bean_name"];
$bean_file_name = $_GET["bean_file_name"];
$parent_entity_file_path = $_GET["path"];
$task_tag = $_GET["task_tag"];
$task_tag_action = $_GET["task_tag_action"];
$db_driver = $_GET["db_driver"];
$db_type = $_GET["db_type"];
$db_table = $_GET["db_table"];
$parent_add_block_func = $_GET["parent_add_block_func"];
$popup = $_GET["popup"];

$parent_entity_file_path = str_replace("../", "", $parent_entity_file_path);//for security reasons

if (!$parent_add_block_func)
	die("parent_add_block_func missing");

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
