<?php
include_once $EVC->getUtilPath("CMSPresentationLayerHandler");

$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "access");

$bean_name = $_GET["bean_name"];
$bean_file_name = $_GET["bean_file_name"];
$path = $_GET["path"]; //template path
$region = $_GET["region"];

$path = str_replace("../", "", $path);//for security reasons

if ($path) {
	$WorkFlowBeansFileHandler = new WorkFlowBeansFileHandler($user_beans_folder_path . $bean_file_name, $user_global_variables_file_path);
	$PEVC = $WorkFlowBeansFileHandler->getEVCBeanObject($bean_name, $path);
	
	if ($PEVC) {
		$P = $PEVC->getPresentationLayer();
		$layer_path = $P->getLayerPathSetting();
		$selected_project_id = $P->getSelectedPresentationId();
		
		$file_path = $layer_path . $path;
		
		if (file_exists($file_path)) {
			$dir = dirname($path) . "/";
			$pos = strpos($dir, "/src/template/");
			
			if ($pos !== false) {
				$start_pos = $pos + strlen("/src/template/");
				$end_pos = strpos($dir, "/", $start_pos);
				
				if ($end_pos !== false) {
					$template_root_path = substr($dir, 0, $end_pos + 1);
					$template_extra_path = substr($dir, $end_pos + 1) . pathinfo($path, PATHINFO_FILENAME) . "/";
					//echo "template_root_path:$template_root_path\n<br>template_extra_path:$template_extra_path";
					
					$parent_folders_rel_path = $template_root_path . "region/" . $template_extra_path;
					$parent_folders_abs_path = $layer_path . $parent_folders_rel_path;
					$folders_rel_path = array();
					
					if ($region) {
						$folder_rel_path = $parent_folders_rel_path . $region . "/";
						
						if (!file_exists($parent_folders_abs_path . $region))
							$folder_rel_path = $parent_folders_rel_path . strtolower($region) . "/";
						
						$folders_rel_path[] = $folder_rel_path;
					}
					else if (is_dir($parent_folders_abs_path)) {
						$folders = scandir($parent_folders_abs_path);
						
						foreach ($folders as $folder) 
							if ($folder != "." && $folder != ".." && is_dir($parent_folders_abs_path . $folder))
								$folders_rel_path[] = $parent_folders_rel_path . $folder . "/";
					}
					
					if ($folders_rel_path) {
						//GET TEMPLATE AVAILABLE REGIONS
						$PHPVariablesFileHandler = new PHPVariablesFileHandler(array($user_global_variables_file_path, $PEVC->getConfigPath("pre_init_config")));
						$PHPVariablesFileHandler->startUserGlobalVariables();
						
						$template_contents = CMSFileHandler::getFileContents($file_path);
						$available_regions_list = CMSPresentationLayerHandler::getAvailableRegionsListFromCode($template_contents, $selected_project_id, false); //show regions even if they are already defined in the template.
						
						$PHPVariablesFileHandler->endUserGlobalVariables();
						
						//GET TEMPLATE REGIONS SAMPLES
						$sample_files_by_region = array();
						
						foreach ($folders_rel_path as $folder_rel_path) {
							$folder_abs_path = $layer_path . $folder_rel_path;
							
							if (file_exists($folder_abs_path)) {
								//Through the $path and $region get the available regions list
								$files = scandir($folder_abs_path);
								$region_folder = strtolower(basename($folder_rel_path));
								$region = null;
								
								foreach ($available_regions_list as $region_code) {
									$r = strtolower(str_replace(array("'", '"'), "", $region_code));
									
									if ($r == $region_folder) {
										$region = $region_code;
										break;
									}
								}
								
								if ($region)
									foreach ($files as $file) 
										if ($file != "." && $file != ".." && !is_dir($folder_abs_path . $file)) {
											$sample_html = file_get_contents($folder_abs_path . $file);
											
											$sample_files_by_region[$region][] = $sample_html;
										}
							}
						}
						
						//echo "<pre>";print_r($sample_files_by_region);die();
						$obj = $sample_files_by_region;
					}
				}
			}
		}
		else {
			launch_exception(new Exception("File '$path' does not exist!"));
			die();
		}
	}
	else {
		launch_exception(new Exception("PEVC doesn't exists!"));
		die();
	}
}
else if (!$path) {
	launch_exception(new Exception("Undefined path!"));
	die();
}
else if (!$region) {
	launch_exception(new Exception("Undefined region!"));
	die();
}
?>
