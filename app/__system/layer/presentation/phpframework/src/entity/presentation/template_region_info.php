<?php
include_once $EVC->getUtilPath("WorkFlowBeansFileHandler");

$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "access");

$bean_name = $_GET["bean_name"];
$bean_file_name = $_GET["bean_file_name"];
$path = $_GET["path"]; //template path
$region = $_GET["region"];
$popup = $_GET["popup"];

$path = str_replace("../", "", $path);//for security reasons
$sample_files = array();

if ($path && $region) {
	$WorkFlowBeansFileHandler = new WorkFlowBeansFileHandler($user_beans_folder_path . $bean_file_name, $user_global_variables_file_path);
	$PEVC = $WorkFlowBeansFileHandler->getEVCBeanObject($bean_name, $path);
	
	if ($PEVC) {
		$P = $PEVC->getPresentationLayer();
		$layer_path = $P->getLayerPathSetting();
		
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
					
					$folder_rel_path = $template_root_path . "region/" . $template_extra_path . $region . "/";
					$folder_abs_path = $layer_path . $folder_rel_path;
					
					if (!file_exists($folder_abs_path)) {
						$folder_rel_path = $template_root_path . "region/" . $template_extra_path . strtolower($region) . "/";
						$folder_abs_path = $layer_path . $folder_rel_path;
					}
					
					if (file_exists($folder_abs_path)) {
						//Through the $path and $region get the available regions list
						$files = scandir($folder_abs_path);
						
						foreach ($files as $file) 
							if ($file != "." && $file != ".." && !is_dir($folder_abs_path . $file))
								$sample_files[] = $folder_rel_path . $file;
					
						//echo "<pre>";print_r($sample_files);die();
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
