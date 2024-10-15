<?php
$project_path = dirname(dirname(str_replace(DIRECTORY_SEPARATOR, "/", __DIR__))) . "/";
$layer_path = dirname($project_path) . "/";

//Note that this project could be inside of folders and sub-folders
$presentation_id = substr($project_path, strlen($layer_path), -1); 

//session_start();//optional: only if you which to start a session, but if you DO, please do it here!

$project_default_template = "main";
$project_with_auto_view = true;
$log_level = 3;

if (!defined("IS_SYSTEM_PHPFRAMEWORK"))
	define("IS_SYSTEM_PHPFRAMEWORK", true);
?>
