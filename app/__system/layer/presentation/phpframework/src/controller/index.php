<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 *
 * Original Bloxtor Repo: https://github.com/a19836/bloxtor
 *
 * YOU ARE NOT AUTHORIZED TO MODIFY OR REMOVE ANY PART OF THIS NOTICE!
 */

include $EVC->getConfigPath("config");
checkLicenceProjects($EVC, $user_global_variables_file_path, $user_beans_folder_path);
include $EVC->getUtilPath("sanitize_html_in_post_request", $EVC->getCommonProjectName());
include $EVC->getConfigPath("authentication");

//check current projects count
//Note that the create_deployment_package.sh will change this function name checkLicenceProjects to cLP
function checkLicenceProjects($EVC, $user_global_variables_file_path, $user_beans_folder_path) {
	$projs_max_num = substr(LA_REGEX, strpos(LA_REGEX, "]") + 1);
	
	if ($projs_max_num == -1) 
		$status = true;
	else if (is_numeric($projs_max_num)) {
		include_once $EVC->getUtilPath("CMSPresentationLayerHandler");
		$files = CMSPresentationLayerHandler::getPresentationLayersProjectsFiles($user_global_variables_file_path, $user_beans_folder_path, "webroot", false, 0);
		
		$projs_count = 0;
		if ($files)
			foreach ($files as $file)
				if (!empty($file["projects"])) {
					$projs_count += count($file["projects"]);
					
					//bc of the common project that doesn't count
					if (array_key_exists("common", $file["projects"]))
						$projs_count--;
				}

		if ($projs_count <= $projs_max_num)
			$status = true;
	}

	//php -r '$string="define(\"PROJECTS_CHECKED\", 123);"; for($i=0; $i < strlen($string); $i++) echo dechex(ord($string[$i]));echo "\n";'
	//php -r '$string="You exceed the maximum number of projects that your licence allow."; for($i=0; $i < strlen($string); $i++) echo dechex(ord($string[$i]));echo "\n";'
	$hash = !empty($status) ? "646566696e65282250524f4a454354535f434845434b4544222c20313233293b" : "596f752065786365656420746865206d6178696d756d206e756d626572206f662070726f6a65637473207468617420796f7572206c6963656e636520616c6c6f772e";
	$msg = "";
	for ($i = 0, $l = strlen($hash); $i < $l; $i += 2)
		$msg .= chr( hexdec($hash[$i] . ($i+1 < $l ? $hash[$i+1] : "") ) );
	
	if (!empty($status))
		eval($msg);
	else {
		echo $msg;
		die(1);
	}
}

include $EVC->getControllerPath("index", $EVC->getCommonProjectName());
?>
