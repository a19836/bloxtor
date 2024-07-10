<?php
include_once get_lib("org.phpframework.util.text.TextSanitizer");

$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "access");

$last_file_created_time = $_GET["file_created_time"];
$last_file_pointer = $_GET["file_pointer"];
$number_of_lines = $_GET["number_of_lines"];
$popup = $_GET["popup"];
$ajax = $_GET["ajax"];

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
		"output" => $output,
		"file_created_time" => $file_created_time,
		"file_pointer" => $file_pointer
	);
	echo json_encode($obj);
	die();
}
?>
