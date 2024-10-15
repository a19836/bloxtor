<?php
include_once $EVC->getUtilPath("CMSPresentationLayerHandler");

$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "access");

$bean_name = isset($_GET["bean_name"]) ? $_GET["bean_name"] : null;
$bean_file_name = isset($_GET["bean_file_name"]) ? $_GET["bean_file_name"] : null;
$path = isset($_GET["path"]) ? $_GET["path"] : null;
$popup = isset($_GET["popup"]) ? $_GET["popup"] : null;
$creation_step = isset($_GET["creation_step"]) ? $_GET["creation_step"] : null; //Do not use step bc is already used in the install_program pages
$on_success_js_func = isset($_GET["on_success_js_func"]) ? $_GET["on_success_js_func"] : null; //used by the choose_available_template.js

$path = str_replace("../", "", $path);//for security reasons

if (!$creation_step || empty($_POST)) { //show edit_project_details so the user can insert the name of the project
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
		
		if (!empty($_POST) && !empty($status)) {
			$layer_bean_folder_name = WorkFlowBeansFileHandler::getLayerObjFolderName( $PEVC->getPresentationLayer() ); //get layer_bean_folder_name
			$new_filter_by_layout = "$layer_bean_folder_name/$path"; //$path already has duplicates and end / removed
		}
	}
}
else if (!empty($_POST)) { //Note that the $path here already contains the created project
	$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "write"); //very important bc all the urls from install_program will now be on this page.
	
	$project_exists = true;
	$on_success_js_func = isset($_POST["on_success_js_func"]) ? $_POST["on_success_js_func"] : null;
	$on_success_js_func_opts = isset($_POST["on_success_js_func_opts"]) ? $_POST["on_success_js_func_opts"] : null;
	$msg = isset($_POST["msg"]) ? $_POST["msg"] : null;	
	
	if ($creation_step == 3) { //show list with programs in our store so the user can install them
		$step = isset($_POST["step"]) ? $_POST["step"] : null;
		
		//call install program
		include_once $EVC->getEntityPath("admin/install_program");
		
		$is_last_step_successfull = $_POST && $step >= 3 && empty($errors) && empty($error_message) && empty($next_step_html);
		
		//if is last step successfully, change the index.php of the project to redirect to the installed program.
		if ($is_last_step_successfull) {
			$index_changed = false;
			
			if (!empty($PEVC) && !empty($program_name)) {
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
