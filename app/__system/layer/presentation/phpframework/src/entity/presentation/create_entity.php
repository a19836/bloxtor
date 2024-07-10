<?php
include_once $EVC->getUtilPath("CMSPresentationLayerHandler");

$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "access");

$bean_name = $_GET["bean_name"];
$bean_file_name = $_GET["bean_file_name"];
$path = $_GET["path"];
$filter_by_layout = $_GET["filter_by_layout"];
$popup = $_GET["popup"];
$creation_step = $_GET["creation_step"];
$on_success_js_func = $_GET["on_success_js_func"];

$path = str_replace("../", "", $path);//for security reasons
$filter_by_layout = str_replace("../", "", $filter_by_layout);//for security reasons

if (!$get_store_pages_url)
	$creation_step = 2;

if (!$creation_step) { //show 2 cards: one with empty page or browse pages
	$creation_step = 0;
}
else if ($creation_step == 1) { //list pages from store
	if ($_POST)
		$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "write"); //very important bc all the urls from install_program will now be on this page.
	
	//call install_page
	include_once $EVC->getEntityPath("presentation/install_page");
	
	if ($_POST && $status) {
		$status_message = 'Pre-built page successfully installed!';
		$creation_step = 2;
		$from_step_1 = true;
	}
}
else if ($creation_step == 2) { //show success message and call on_success_js_func
	//Do nothing
}
?>
