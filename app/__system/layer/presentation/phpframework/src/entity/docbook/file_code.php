<?php
$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "access");

$path = $_GET["path"];
$popup = $_GET["popup"];

$path = str_replace("../", "", $path);//for security reasons

$file_path = $path ? APP_PATH . $path : null;
$file_exists = $file_path ? file_exists($file_path) : null;

$readonly = true;

if ($file_exists) {
	$is_contents_allowed = strpos($file_path, LIB_PATH) === 0;
	
	if ($is_contents_allowed) {
		$available_extensions = array("xml" => "xml", "php" => "php", "js" => "javascript", "css" => "css", "" => "text", "txt" => "text", "html" => "html", "htm" => "html");
		$editor_code_type = $available_extensions[ strtolower(pathinfo($file_path, PATHINFO_EXTENSION)) ];
		
		$code = file_get_contents($file_path);
	}
}
?>
