<?php
include_once get_lib("org.phpframework.util.web.MyCurl");
include_once get_lib("org.phpframework.compression.ZipHandler");
include_once get_lib("org.phpframework.util.web.CSRFValidator");

class DependenciesInstallationHandler {
	
	public static $INSTALL_DEPENDENCIES_VARIABLE_NAME = "install_dependencies";
	
	public static function setCookieInstallDependencies($status) {
		$extra_flags = CSRFValidator::$COOKIES_EXTRA_FLAGS;
		CookieHandler::setSafeCookie(self::$INSTALL_DEPENDENCIES_VARIABLE_NAME, $status, 0, "/", $extra_flags);
	}
	
	public static function isDependencyInstalled($dependency_folder_path, &$error_message = null) {
		return file_exists($dependency_folder_path) && is_dir($dependency_folder_path);
	}
	
	public static function installDependencies($repo_url, $zips_name, &$error_message = null) {
		$errors = array();
		
		if ($repo_url && $zips_name) {
			$zips_name = is_array($zips_name) ? $zips_name : array($zips_name);
			$files_to_close = array();
			
			foreach ($zips_name as $zip_name => $folders_to_copy) 
				if ($zip_name && $folders_to_copy) {
					$folders_to_copy = is_array($folders_to_copy) ? $folders_to_copy : array($folders_to_copy);
					$folders_to_install = array();
					
					foreach ($folders_to_copy as $folder_to_copy) 
						if ($folder_to_copy) {
							$dirname_exists = is_dir(dirname($folder_to_copy)); //in case doesn't exist yet, like the LAYER_PATH that is only created after setup runs.
							
							if ($dirname_exists && !file_exists($folder_to_copy))
								$folders_to_install[] = $folder_to_copy;
						}
					
					if (!empty($folders_to_install)) {
						$url = "$repo_url/$zip_name";
						$downloaded_file = MyCurl::downloadFile($url, $fp);
						
						if ($fp)
							$files_to_close[] = $fp;
						
						if ($downloaded_file && !empty($downloaded_file["tmp_name"]) && !empty($downloaded_file["type"]) && stripos($downloaded_file["type"], "zip") !== false) {
							foreach ($folders_to_install as $folder_to_install)
								if (!ZipHandler::unzip($downloaded_file["tmp_name"], $folder_to_install))
									$errors[] = "Could not install $zip_name in $folder_to_install.<br/>";
						}
						else
							$errors[] = "Could not download $url or invalid zip file.<br/>";
					}
				}
			
			if ($files_to_close)
				foreach ($files_to_close as $fp)
					fclose($fp);
		}
		else if ($repo_url)
			$errors[] = "No repo url defined!";
		else if ($zips_name)
			$errors[] = "No zips name defined!";
		
		if ($errors) {
			$error_message = ($error_message ? $error_message : "") . implode("<br/>", $errors);
			return false;
		}
		
		return true;
	}
	
	public static function getDependencyZipFilesToInstall() {
		return array(
			"phpjavascriptpacker.zip" => LIB_PATH . "vendor/phpjavascriptpacker",
			"phpmailer.zip" => LIB_PATH . "vendor/phpmailer",
			"xsssanitizer.zip" => LIB_PATH . "vendor/xsssanitizer",
			
			"ckeditor.zip" => array(SYSTEM_LAYER_PATH . "presentation/common/webroot/vendor/ckeditor", LAYER_PATH . "presentation/common/webroot/vendor/ckeditor", LAYER_PATH . "pres/common/webroot/vendor/ckeditor"),
			"tinymce.zip" => array(SYSTEM_LAYER_PATH . "presentation/common/webroot/vendor/tinymce", LAYER_PATH . "presentation/common/webroot/vendor/tinymce", LAYER_PATH . "pres/common/webroot/vendor/tinymce"),
		);
	}
	
	//Note that this array should be sync with the setup.php
	public static function getFilesToValidate() {
		return array(
			TMP_PATH,  
			VENDOR_PATH, //CMS_PATH . "vendor/",
			DAO_PATH, //CMS_PATH . "vendor/dao/",
			CODE_WORKFLOW_EDITOR_PATH, //CMS_PATH . "vendor/codeworkfloweditor/",
			CODE_WORKFLOW_EDITOR_TASK_PATH, //CMS_PATH . "vendor/codeworkfloweditor/task/",
			LAYOUT_UI_EDITOR_PATH, //CMS_PATH . "vendor/layoutuieditor/",
			LAYOUT_UI_EDITOR_WIDGET_PATH, //CMS_PATH . "vendor/layoutuieditor/widget/",
			TEST_UNIT_PATH, //CMS_PATH . "vendor/testunit/",
			
			OTHER_PATH . "authdb/", //CMS_PATH . "other/authdb/",
			OTHER_PATH . "authdb/permission.tbl", //CMS_PATH . "other/authdb/permission.tbl",
			OTHER_PATH . "authdb/user.tbl", //CMS_PATH . "other/authdb/user.tbl",
			OTHER_PATH . "authdb/user_type.tbl", //CMS_PATH . "other/authdb/user_type.tbl",
			OTHER_PATH . "authdb/user_type_permission.tbl", //CMS_PATH . "other/authdb/user_type_permission.tbl",
			OTHER_PATH . "authdb/user_stats.tbl", //CMS_PATH . "other/authdb/user_stats.tbl",
			OTHER_PATH . "authdb/user_user_type.tbl", //CMS_PATH . "other/authdb/user_user_type.tbl",
			OTHER_PATH . "authdb/login_control.tbl", //CMS_PATH . "other/authdb/login_control.tbl",
			OTHER_PATH . "authdb/layout_type.tbl", //CMS_PATH . "other/authdb/layout_type.tbl",
			OTHER_PATH . "authdb/layout_type_permission.tbl", //CMS_PATH . "other/authdb/layout_type_permission.tbl",
			OTHER_PATH . "authdb/module_db_table_name.tbl", //CMS_PATH . "other/authdb/module_db_table_name.tbl",
			OTHER_PATH . "authdb/object_type.tbl", //CMS_PATH . "other/authdb/object_type.tbl",
			OTHER_PATH . "authdb/reserved_db_table_name.tbl", //CMS_PATH . "other/authdb/reserved_db_table_name.tbl",
			OTHER_PATH . "workflow/", //CMS_PATH . "other/workflow/",
			
			CONFIG_PATH, //CMS_PATH . "app/config/",
			LAYER_PATH, //CMS_PATH . "app/layer/", 
			LIB_PATH . "vendor/", //CMS_PATH . "app/lib/vendor/", 
			
			SYSTEM_CONFIG_PATH . "global_variables.php", //CMS_PATH . "app/__system/config/global_variables.php", 
			
			SYSTEM_LAYER_PATH . "presentation/phpframework/src/config/authentication.php", //CMS_PATH . "app/__system/layer/presentation/phpframework/src/config/authentication.php", 
			SYSTEM_LAYER_PATH . "presentation/phpframework/webroot/vendor/", //CMS_PATH . "app/__system/layer/presentation/phpframework/webroot/vendor/", 
			SYSTEM_LAYER_PATH . "presentation/phpframework/webroot/__system/", //CMS_PATH . "app/__system/layer/presentation/phpframework/webroot/__system/", 
			
			SYSTEM_LAYER_PATH . "presentation/test/webroot/__system/", //CMS_PATH . "app/__system/layer/presentation/test/webroot/__system/", 
			
			SYSTEM_LAYER_PATH . "presentation/common/webroot/__system/", //CMS_PATH . "app/__system/layer/presentation/common/webroot/__system/",
			SYSTEM_LAYER_PATH . "presentation/common/src/module/", //CMS_PATH . "app/__system/layer/presentation/common/src/module/",
			SYSTEM_LAYER_PATH . "presentation/common/webroot/module/", //CMS_PATH . "app/__system/layer/presentation/common/webroot/module/",
			SYSTEM_LAYER_PATH . "presentation/common/webroot/vendor/", //CMS_PATH . "app/__system/layer/presentation/common/webroot/vendor/",
		);
	}
	
	//Note that this array should be sync with the setup.php
	public static function getOptionalFilesToValidate() {
		//These files may not exist in the beginning
		return array(
			OTHER_PATH . "authdb/layout_type.tbl", //CMS_PATH . "other/authdb/layout_type.tbl",
			OTHER_PATH . "authdb/layout_type_permission.tbl", //CMS_PATH . "other/authdb/layout_type_permission.tbl",
			
			SYSTEM_LAYER_PATH . "presentation/test/webroot/__system/", //CMS_PATH . "app/__system/layer/presentation/test/webroot/__system/",
			SYSTEM_LAYER_PATH . "presentation/common/webroot/__system/", //CMS_PATH . "app/__system/layer/presentation/common/webroot/__system/",
		);
	}
}
?>
