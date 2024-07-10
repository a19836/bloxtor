<?php
$bean_name = $_GET["bean_name"];
$bean_file_name = $_GET["bean_file_name"];
$path = $_GET["path"];
$popup = $_GET["popup"];

$path = str_replace("../", "", $path);//for security reasons

$view_project_url = $project_url_prefix . "phpframework/presentation/view_project?bean_name=$bean_name&bean_file_name=$bean_file_name&path=$path&";

if ($_POST) {
	$post_vars = $_POST["post_vars"];
	$get_vars = $_POST["get_vars"];
	$vars = array("post_vars" => array(), "get_vars" => array());
	
	if ($post_vars)
		foreach ($post_vars as $var)
			$vars["post_vars"][ $var["name"] ] = $var["value"];
	
	if ($get_vars)
		foreach ($get_vars as $var)
			$vars["get_vars"][ $var["name"] ] = $var["value"];
	
	$view_project_url .= http_build_query($vars);
}
?>
