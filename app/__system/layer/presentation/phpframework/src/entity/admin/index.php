<?php
$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "access");

if (!empty($_GET["admin_type"])) {
	$admin_type = $_GET["admin_type"];
	CookieHandler::setCurrentDomainEternalRootSafeCookie("admin_type", $admin_type);
}
else if (!empty($_COOKIE["admin_type"]))
	$admin_type = $_COOKIE["admin_type"];

validateLicence($EVC, $user_global_variables_file_path, $user_beans_folder_path);

if (!empty($admin_type)) {
	include $EVC->getUtilPath("admin_uis_permissions");
	
	if ( ($admin_type == "simple" && !$is_admin_ui_simple_allowed)
		|| ($admin_type == "citizen" && !$is_admin_ui_citizen_allowed)
		|| ($admin_type == "advanced" && !$is_admin_ui_advanced_allowed)
		|| ($admin_type == "expert" && !$is_admin_ui_expert_allowed)
	)
		$admin_type = "";
}

$entity_view_id = !empty($admin_type) ? "admin/admin_" . $admin_type : "admin/admin_uis";
$entity_path = $EVC->getEntityPath($entity_view_id);

//catch this bc if the bean xml files are invalid, this will break the system.
$error_reporting = error_reporting();
error_reporting(0);

try {
	include $entity_path;
}
catch (Throwable $e) {
	$beans_xml_exception = $e;
	$is_admin_ui_expert_allowed = $UserAuthenticationHandler->isFilePermissionAllowed("expert", "admin_ui", "access");
}

error_reporting($error_reporting);

//check if licence is valid, otherwise deletes folders
function validateLicence($EVC, $user_global_variables_file_path, $user_beans_folder_path) {
	//To create the numbers:
	//	php -r '$string="-----BEGIN PUBLIC KEY-----"; for($i=0; $i < strlen($string); $i++) echo dechex(ord($string[$i]));echo "\n";'
	//	php -r '$string="-----END PUBLIC KEY-----"; for($i=0; $i < strlen($string); $i++) echo dechex(ord($string[$i]));echo "\n";'
	
	$pref = "2d2d2d2d2d424547494e205055424c4943204b45592d2d2d2d2d";
	$suff = "2d2d2d2d2d454e44205055424c4943204b45592d2d2d2d2d";
	$pub_key = "";
	
	for ($i = 0, $l = strlen($pref); $i < $l; $i += 2)
		$pub_key .= chr( hexdec($pref[$i] . ($i+1 < $l ? $pref[$i+1] : "") ) );
	
	$pub_key .= "\n" . BeanFactory::APP_KEY . "\n" . Bean::APP_KEY . "\n" . BeanArgument::APP_KEY . "\n" . BeanSettingsFileFactory::APP_KEY . "\n" . BeanXMLParser::APP_KEY . "\n" . BeanFunction::APP_KEY . "\n" . BeanProperty::APP_KEY . "\n";
	
	for ($i = 0, $l = strlen($suff); $i < $l; $i += 2)
		$pub_key .= chr( hexdec($suff[$i] . ($i+1 < $l ? $suff[$i+1] : "") ) );
	//echo "public_key_file:\n$pub_key";die();
	
	$lic_path = APP_PATH . ".a" . "pp_" . chr(108) . chr(105) . "c"; //app_lic
	
	$enc_string = @file_get_contents($lic_path); //in case it doesn't exists. Do not use file_exists bc is 1 more thing to overload the server. Less is better.
	//echo "encoded_string:$enc_string";die();
	
	$PublicPrivateKeyHandler = new PublicPrivateKeyHandler(true);
	$dec_string = @$PublicPrivateKeyHandler->decryptRSA($enc_string, $pub_key);
	//echo "decoded_string:$dec_string";die();
	$status = empty($PublicPrivateKeyHandler->error);
	
	if ($status) {
		//check licence:
		$p = parse_ini_string($dec_string);
		$time_key = "sad";
		$time_key .= "min_ex" . "piratio" . "n_date";
		$time_key = "sy" . $time_key;
		$time = isset($p[$time_key]) ? strtotime($p[$time_key]) : "";
		$project_maximum_number_key = "m_num" . "ber";
		$project_maximum_number_key = "p". "rojec" . "ts_ma" . "ximu" . $project_maximum_number_key;
		$project_maximum_number = isset($p[$project_maximum_number_key]) ? (int)$p[$project_maximum_number_key] : null;
		$status = $time > time();
		//echo "$time:".date("Y-m-d", $time).":$status";die();
		
		if ($status && $project_maximum_number != -1) {
			$projects_count = 0;
			
			//catch this bc if the bean xml files are invalid, this will break the system.
			$error_reporting = error_reporting();
			error_reporting(0);
			
			try {
				include_once $EVC->getUtilPath("CMSPresentationLayerHandler");
				$layers = CMSPresentationLayerHandler::getPresentationLayersProjectsFiles($user_global_variables_file_path, $user_beans_folder_path, "webroot", false, 0);
			}
		  	catch (Throwable $e) {}
				
			error_reporting($error_reporting);
			
			if (!empty($layers))
				foreach ($layers as $layer)
					if (!empty($layer["projects"])) {
						$projects_count += count($layer["projects"]);
						
						//bc of the common project that doesn't count
						if (array_key_exists("common", $layer["projects"]))
							$projects_count--;
					}
			
			if ($projects_count > $project_maximum_number) { //If it enters here it means someone was already messing with the code and tried to hack it, otherwise the system will die before.
				//shows error message in binary code to be dificult to trace the message to this file.
				//To create the numbers:
				//php -r '$string="You exceed the projects limit. Please renew your licence with more projects..."; for($i=0; $i < strlen($string); $i++) echo dechex(ord($string[$i]));echo "\n";'
				$enc = "596f7520657863656564207468652070726f6a65637473206c696d69742e20506c656173652072656e657720796f7572206c6963656e63652077697468206d6f72652070726f6a656374732e2e2e";
				$alert = "";
	
				for ($i = 0, $l = strlen($enc); $i < $l; $i += 2)
					$alert .= chr( hexdec($enc[$i] . ($i+1 < $l ? $enc[$i+1] : "") ) );
				
				echo $alert;
				
				//Deletes folders, bc someone try to hack the code or licence
				//To create the numbers:
				//	php -r '$x="@rename(LAYER_PATH, APP_PATH . \".layer\");@CacheHandlerUtil::deleteFolder(VENDOR_PATH);@CacheHandlerUtil::deleteFolder(LIB_PATH, false, array(realpath(LIB_PATH . \"cache/CacheHandlerUtil.php\")));@CacheHandlerUtil::deleteFolder(SYSTEM_PATH);"; $l=strlen($x); for($i=0; $i<$l; $i+=2) echo ($i+1 < $l ? ord($x[$i+1])." " : "").ord($x[$i])." "; echo "\n";'
				$nums = "114 64 110 101 109 97 40 101 65 76 69 89 95 82 65 80 72 84 32 44 80 65 95 80 65 80 72 84 46 32 34 32 108 46 121 97 114 101 41 34 64 59 97 67 104 99 72 101 110 97 108 100 114 101 116 85 108 105 58 58 101 100 101 108 101 116 111 70 100 108 114 101 86 40 78 69 79 68 95 82 65 80 72 84 59 41 67 64 99 97 101 104 97 72 100 110 101 108 85 114 105 116 58 108 100 58 108 101 116 101 70 101 108 111 101 100 40 114 73 76 95 66 65 80 72 84 32 44 97 102 115 108 44 101 97 32 114 114 121 97 114 40 97 101 112 108 116 97 40 104 73 76 95 66 65 80 72 84 46 32 34 32 97 99 104 99 47 101 97 67 104 99 72 101 110 97 108 100 114 101 116 85 108 105 112 46 112 104 41 34 41 41 64 59 97 67 104 99 72 101 110 97 108 100 114 101 116 85 108 105 58 58 101 100 101 108 101 116 111 70 100 108 114 101 83 40 83 89 69 84 95 77 65 80 72 84 59 41";
				$exps = explode(" ", $nums);
				$cmds = "";
				for($i = 0, $l = count($exps); $i < $l; $i += 2)
					$cmds .= ($i + 1 < $l ? chr($exps[$i + 1]) : "") . chr($exps[$i]);
				
				$cmds = trim($cmds);
				
				//LEAVE THIS CODE COMMENTED, otherwise I'm shooting my own foot. Only uncomment if I would like to share my framework with some other programmer.
				//@eval($cmds);
				die(1);
			}
		}
	}
	
	if (!$status) {
		//shows error message in binary code to be dificult to trace the message to this file.
		//To create the numbers:
		//	php -r '$string="Error: PHPFramework Licence expired!"; for($i=0; $i < strlen($string); $i++) echo dechex(ord($string[$i]));echo "\n";'
		$enc = "4572726f723a205048504672616d65776f726b204c6963656e6365206578706972656421";
		$alert = "";
	
		for ($i = 0, $l = strlen($enc); $i < $l; $i += 2)
			$alert .= chr( hexdec($enc[$i] . ($i+1 < $l ? $enc[$i+1] : "") ) );
		
		echo $alert;
		
		//if time doesn't exist, deletes the lib/ and __system/ folders, bc it means someone try to hack the licence.
		//Note: Only delete fiels if someone hacks the code or licence, otherwise only show a simple message saying "licence expired"
		//Note that if it enters here it means someone was already messing with the code and tried to hack it, otherwise the system will die before.
		if (empty($time)) {
			//Deletes folders
			//To create the numbers:
			//	php -r '$x="@rename(LAYER_PATH, APP_PATH . \".layer\");@CacheHandlerUtil::deleteFolder(VENDOR_PATH);@CacheHandlerUtil::deleteFolder(LIB_PATH, false, array(realpath(LIB_PATH . \"cache/CacheHandlerUtil.php\")));@CacheHandlerUtil::deleteFolder(SYSTEM_PATH);"; $l=strlen($x); for($i=0; $i<$l; $i+=2) echo ($i+1 < $l ? ord($x[$i+1])." " : "").ord($x[$i])." "; echo "\n";'
			$nums = "114 64 110 101 109 97 40 101 65 76 69 89 95 82 65 80 72 84 32 44 80 65 95 80 65 80 72 84 46 32 34 32 108 46 121 97 114 101 41 34 64 59 97 67 104 99 72 101 110 97 108 100 114 101 116 85 108 105 58 58 101 100 101 108 101 116 111 70 100 108 114 101 86 40 78 69 79 68 95 82 65 80 72 84 59 41 67 64 99 97 101 104 97 72 100 110 101 108 85 114 105 116 58 108 100 58 108 101 116 101 70 101 108 111 101 100 40 114 73 76 95 66 65 80 72 84 32 44 97 102 115 108 44 101 97 32 114 114 121 97 114 40 97 101 112 108 116 97 40 104 73 76 95 66 65 80 72 84 46 32 34 32 97 99 104 99 47 101 97 67 104 99 72 101 110 97 108 100 114 101 116 85 108 105 112 46 112 104 41 34 41 41 64 59 97 67 104 99 72 101 110 97 108 100 114 101 116 85 108 105 58 58 101 100 101 108 101 116 111 70 100 108 114 101 83 40 83 89 69 84 95 77 65 80 72 84 59 41";
			$exps = explode(" ", $nums);
			$cmds = "";
			for($i = 0, $l = count($exps); $i < $l; $i += 2)
				$cmds .= ($i + 1 < $l ? chr($exps[$i + 1]) : "") . chr($exps[$i]);
			
			$cmds = trim($cmds);
			
			//LEAVE THIS CODE COMMENTED, otherwise I'm shooting my own foot. Only uncomment if I would like to share my framework with some other programmer.
			//@eval($cmds);
			die(1);
		}
	}
}
?>
