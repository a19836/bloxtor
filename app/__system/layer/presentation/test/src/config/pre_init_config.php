<?php
$project_path = dirname(dirname(str_replace(DIRECTORY_SEPARATOR, "/", __DIR__))) . "/";
$layer_path = dirname($project_path) . "/";

//Note that this project could be inside of folders and sub-folders
$presentation_id = substr($project_path, strlen($layer_path), -1); 

//session_start();//optional: only if you which to start a session, but if you DO, please do it here!

$project_default_template = "template1";

define("IS_SYSTEM_PHPFRAMEWORK", true); //very important to be here. All projects in side of __system must have this defined, otherwise the system will think that the system was hacked and purge and remove the system folders.
?>
