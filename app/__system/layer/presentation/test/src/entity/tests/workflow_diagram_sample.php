<?php
//http://jplpinto.localhost/__system/test/tests/workflow_diagram_sample

include_once get_lib("org.phpframework.workflow.WorkFlowTaskHandler");
include $EVC->getUtilPath("WorkFlowUIHandler", "phpframework");
include $EVC->getConfigPath("config", "phpframework");

$webroot_cache_folder_url = str_replace("/__system/phpframework/__system/cache/", "/__system/test/__system/cache/", $webroot_cache_folder_url); //replace webroot_cache_folder_url for the test project

//entity
$WorkFlowTaskHandler = new WorkFlowTaskHandler($webroot_cache_folder_path, $webroot_cache_folder_url);
$WorkFlowTaskHandler->setCacheRootPath(LAYER_CACHE_PATH);
//$WorkFlowTaskHandler->setTasksFolderPaths(array(WorkFlowTaskHandler::getDefaultTasksFolderPath() . "/programming", WorkFlowTaskHandler::getDefaultTasksFolderPath() . "/layer"));
$WorkFlowTaskHandler->setAllowedTaskTypes(array("if", "switch"));
//$WorkFlowTaskHandler->setAllowedTaskFolders(array("programming/"));
$WorkFlowTaskHandler->setTasksContainers(array("content_with_only_if" => array("if"), "content_with_only_switch" => array("switch")));

$get_workflow_file_path = "/home/jplpinto/Desktop/phpframework/trunk/app/lib/org/phpframework/workflow/test/tasks3.xml";
$set_workflow_file_path = "/tmp/test_tasks.xml";

//view
$WorkFlowUIHandler = new WorkFlowUIHandler($WorkFlowTaskHandler, $project_url_prefix, $project_common_url_prefix, $external_libs_url_prefix, $user_global_variables_file_path, $webroot_cache_folder_path, $webroot_cache_folder_url);

$head = $WorkFlowUIHandler->getHeader();
$head .= $WorkFlowUIHandler->getJS($get_workflow_file_path, $set_workflow_file_path);
$head .= '<style type="text/css">
	.tasks_flow #content_with_only_if {width:100%; height:50%; background-color:#FF0000;}
	.tasks_flow #content_with_only_switch {width:100%; height:50%; background-color:#00FFFF;}
</style>';

$main_content = $WorkFlowUIHandler->getContent();

/*
TODO:
- escape (addcslashes($value, '\\"')) values in each task of the: public function printCode($tasks) 
- design UI for each task
*/
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
"http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<link rel="icon" href="data:;base64,=" />
	
	<link rel="stylesheet" href="<?php echo $project_common_url_prefix; ?>css/global.css" type="text/css" charset="utf-8" />
	<link rel="stylesheet" href="<?php echo $project_url_prefix; ?>phpframework/css/global.css" type="text/css" charset="utf-8" />
	<link rel="stylesheet" href="<?php echo $project_url_prefix; ?>phpframework/css/message.css" type="text/css" charset="utf-8" />
	
	<script language="javascript" type="text/javascript" src="<?php echo $project_common_url_prefix; ?>js/global.js"></script>
	
	<!-- Colors -->
	<script language="javascript" type="text/javascript" src="<?php echo $project_common_url_prefix; ?>js/color.js"></script>
	
	<!-- Json -->
	<script language="javascript" type="text/javascript" src="<?php echo $project_common_url_prefix; ?>vendor/json/js/json2.js"></script>
	
	<!-- Jquery -->
	<script language="javascript" type="text/javascript" src="<?php echo $project_common_url_prefix; ?>vendor/jquery/js/jquery-1.8.1.min.js"></script>
	<script language="javascript" type="text/javascript" src="<?php echo $project_common_url_prefix; ?>vendor/jquery/js/jquery.center.js"></script>
	
	<!-- Add Jquery UI JS and CSS files -->
	<link rel="stylesheet" href="<?php echo $project_common_url_prefix; ?>vendor/jqueryui/css/jquery-ui-1.11.4.css" type="text/css" />
	<script language="javascript" type="text/javascript" src="<?php echo $project_common_url_prefix; ?>vendor/jqueryui/js/jquery-ui-1.11.4.min.js"></script>
	
	<!-- MyJSLib -->
	<script language="javascript" type="text/javascript" src="<?php echo $project_common_url_prefix; ?>js/MyJSLib.js"></script>
	
	<!-- Fancy LighBox -->
	<link rel="stylesheet" href="<?php echo $project_common_url_prefix; ?>vendor/jquerymyfancylightbox/css/style.css" type="text/css" charset="utf-8" />
	<script language="javascript" type="text/javascript" src="<?php echo $project_common_url_prefix; ?>vendor/jquerymyfancylightbox/js/jquery.myfancybox.js"></script>
	
	<!-- Message -->
	<link rel="stylesheet" href="<?php echo $project_common_url_prefix; ?>vendor/jquerymystatusmessage/css/style.css" type="text/css" charset="utf-8" />
	<script language="javascript" type="text/javascript" src="<?php echo $project_common_url_prefix; ?>vendor/jquerymystatusmessage/js/statusmessage.js"></script>
	
	<script>
	$(function () {
		var is_iframe = (window.location != window.parent.location) ? true : false;
		if (isMSIE() && $.browser.version > 0 && $.browser.version < 11 && !is_iframe)
			alert("Please upgrade your IE to a version equal or bigger than 11 or use other browser like: Firefox, Safari or Chrome... Otherwise this application can be buggy!");
		
		StatusMessageHandler.init();
	});
	</script>
	
	<?= $head; ?>
</head>
<body>
	<div id="container">
		<?= $main_content; ?>
	</div>
</body>
</html>
