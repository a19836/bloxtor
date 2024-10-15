<?php
$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "access");

$type = isset($_GET["type"]) ? $_GET["type"] : null;
$url = $type == "modules" ? $get_store_modules_url : (
	$type == "templates" ? $get_store_templates_url : (
		$type == "programs" ? $get_store_programs_url : (
			$type == "pages" ? $get_store_pages_url : null
		)
	)
);

if ($url) {
	$query_string = isset($_SERVER["QUERY_STRING"]) ? $_SERVER["QUERY_STRING"] : null;
	
	$settings = array(
		"url" => $url, 
		"settings" => array(
			"connection_timeout" => 60, //in seconds
			"follow_location" => 1,
		)
	);
	
	$MyCurl = new MyCurl();
	$MyCurl->initSingle($settings);
	$MyCurl->get_contents();
	$data = $MyCurl->getData();
	$json = isset($data[0]["content"]) ? $data[0]["content"] : null;
	
	echo $json;
}

die();
?>
