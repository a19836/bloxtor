<?php
include_once $EVC->getUtilPath("CMSPresentationLayerHandler");

$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "access");

$bean_name = $_GET["bean_name"];
$bean_file_name = $_GET["bean_file_name"];
$path = $_GET["path"];
$popup = $_GET["popup"];
$creation_step = $_GET["creation_step"]; //Do not use step bc is already used in the install_program pages
$on_success_js_func = $_GET["on_success_js_func"]; //used by the choose_available_template.js

$path = str_replace("../", "", $path);//for security reasons

if (!$creation_step || !$_POST) { //show edit_project_details so the user can insert the name of the project
	//in case the user adds a creation_step=3 ro click back button in the browser, then it means the method is a GET (not POST) and that the we need to edit_project_details will open that creation_step on submit its form. This is wrong, so we need to refresh the page withtout the creation_step var.
	if (is_numeric($creation_step) && $creation_step != 0) {
		$refresh_page_without_creation_step = true;
		$creation_step = 0;
	}
	else {
		$creation_step = 0;
		
		//call edit_project_details
		include_once $EVC->getEntityPath("presentation/edit_project_details");
		
		$project_exists = $is_existent_project;
		
		if ($_POST && $status) {
			$layer_bean_folder_name = WorkFlowBeansFileHandler::getLayerObjFolderName( $PEVC->getPresentationLayer() ); //get layer_bean_folder_name
			$new_filter_by_layout = "$layer_bean_folder_name/$path"; //$path already has duplicates and end / removed
		}
	}
}
else if ($_POST) { //Note that the $path here already contains the created project
	$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "write"); //very important bc all the urls from install_program will now be on this page.
	
	$project_exists = true;
	$on_success_js_func = $_POST["on_success_js_func"];
	$on_success_js_func_opts = $_POST["on_success_js_func_opts"];
	$msg = $_POST["msg"];	
	
	if ($creation_step == 3) { //show list with programs in our store so the user can install them
		$step = $_POST["step"];
		
		//call install program
		include_once $EVC->getEntityPath("admin/install_program");
		
		$is_last_step_successfull = $_POST && $step >= 3 && !$errors && !$error_message && !$next_step_html;
		
		//if is last step successfully, change the index.php of the project to redirect to the installed program.
		if ($is_last_step_successfull) {
			$index_changed = false;
			
			if ($PEVC && $program_name) {
				$index_path = $PEVC->getEntityPath("index");
				$code = "<?php\n\$url = \"\${project_url_prefix}$program_name\";\nheader(\"Location: \$url\");\necho \"<script>document.location='\$url';</script>\";\ndie();\n?>";
				$index_changed = file_put_contents($index_path, $code) !== false;
			}
			
			if (!$index_changed)
				$error_message = "The index.php in the root of this project was not pointed to the installed program. Please do this manually...";
		}
	}
	else if ($creation_step == 2) { //show success message and call on_success_js_func
		$WorkFlowBeansFileHandler = new WorkFlowBeansFileHandler($user_beans_folder_path . $bean_file_name, $user_global_variables_file_path);
		$PEVC = $WorkFlowBeansFileHandler->getEVCBeanObject($bean_name, $path);
		
		$layer_bean_folder_name = WorkFlowBeansFileHandler::getLayerObjFolderName( $PEVC->getPresentationLayer() ); //get layer_bean_folder_name
		$filter_by_layout = "$layer_bean_folder_name/" . $PEVC->getPresentationLayer()->getSelectedPresentationId();
	}
	else if ($creation_step == 1) { //show blank project or based in a program
		//do nothing
	}
}
?>
