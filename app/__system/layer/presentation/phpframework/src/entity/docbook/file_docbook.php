<?php
$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "access");

$path = $_GET["path"];

$path = str_replace("../", "", $path);//for security reasons

$file_path = $path ? APP_PATH . $path : null;
$file_exists = $file_path ? file_exists($file_path) : null;

if ($file_exists) {
	$is_docbook_allowed = strpos($file_path, LIB_PATH . "org/") === 0;
	$is_contents_allowed = strpos($file_path, LIB_PATH) === 0;
	
	if ($is_docbook_allowed) {
		//get $classes_properties from cache
		$relative_file_path = substr($file_path, strlen(APP_PATH));
		$cached_path = $EVC->getEntitiesPath() . "docbook/files/$relative_file_path.ser";
		
		$classes_properties = file_exists($cached_path) ? unserialize(file_get_contents($cached_path)) : null;
		//echo "<pre>";print_r($classes_properties);die();
	}
	else if ($is_contents_allowed)
		$contents = file_get_contents($file_path);
}
?>
