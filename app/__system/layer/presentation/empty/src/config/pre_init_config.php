<?php
//Do not change any of these variables bc when a project is created, I'm changing this code based in str_replace.
$project_path = dirname(dirname(str_replace(DIRECTORY_SEPARATOR, "/", __DIR__))) . "/";
$layer_path = dirname($project_path) . "/";

//Note that this project could be inside of folders and sub-folders
$presentation_id = substr($project_path, strlen($layer_path), -1); 

//session_start();//optional: only if you which to start a session, but if you DO, please do it here!

$project_default_template = "blank";

?>
