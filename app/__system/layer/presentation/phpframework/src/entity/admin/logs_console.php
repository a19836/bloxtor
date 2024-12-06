<?php
include_once get_lib("org.phpframework.util.text.TextSanitizer");

$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "access");

$popup = isset($_GET["popup"]) ? $_GET["popup"] : null;
$last_file_created_time = isset($_GET["file_created_time"]) ? $_GET["file_created_time"] : null;
$last_file_pointer = isset($_GET["file_pointer"]) ? $_GET["file_pointer"] : null;
$number_of_lines = isset($_GET["number_of_lines"]) ? $_GET["number_of_lines"] : null;
$ajax = isset($_GET["ajax"]) ? $_GET["ajax"] : null;

$file_path = $GLOBALS["GlobalLogHandler"]->getFilePath();

if (file_exists($file_path)) {
	$file_created_time = filectime($file_path);
	$file_pointer = filesize($file_path);
	
	//from ajax
	if ($last_file_pointer) {
		//detect if log was rotated and if yes, reset file pointer
		//if ($file_created_time != $last_file_created_time) //Note that in unix the filectime will always change everytime the log file gets modified. So there is no point of doing this
		//	$last_file_pointer = 0;
		
		$output = $GLOBALS["GlobalLogHandler"]->tail(0, $last_file_pointer);
	}
	else {
		//set_time_limit(0); //sets no limit for execution for big log files with hude number in $number_of_lines var.
		$number_of_lines = $number_of_lines > 0 ? $number_of_lines : 100;
		$output = $GLOBALS["GlobalLogHandler"]->tail($number_of_lines);
	}
	
	$output = TextSanitizer::convertBinaryCodeInTextToBase64($output);
}

if ($ajax) {
	$obj = array(
		"output" => isset($output) ? $output : null,
		"file_created_time" => isset($file_created_time) ? $file_created_time : null,
		"file_pointer" => isset($file_pointer) ? $file_pointer : null
	);
	echo json_encode($obj);
	die();
}
?>
