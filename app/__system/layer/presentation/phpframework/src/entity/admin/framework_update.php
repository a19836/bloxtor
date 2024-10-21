<?php
$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "access");

$is_remote_update_allowed = function_exists("exec") && function_exists("posix_getpwuid") && file_exists(SYSTEM_PATH);
$step = 0;

if ($is_remote_update_allowed) {
	$web_server_user = posix_getpwuid(posix_getuid());
	$os_account_user = posix_getpwuid(fileowner(SYSTEM_PATH));
	
	$is_remote_update_allowed = !empty($web_server_user["name"]) && !empty($os_account_user["name"]) && $web_server_user["name"] == $os_account_user["name"];
	
	if ($is_remote_update_allowed && !empty($_POST)) {
		$step = isset($_POST["step"]) ? $_POST["step"] : null;
		
		if ($step == 2) {
			//call git update
			exec("/bin/git pull '" . CMS_PATH . "'", $output);
		}
		else if ($step == 1) {
			//check changed files
			$changed_files = array("asdasd");
			exec("/bin/git ls-files -m", $changed_files);
			
			if (empty($changed_files)) {
				//call git update
				exec("/bin/git pull '" . CMS_PATH . "'", $output);
			}
		}
	}
}
?>
