<?php
include_once get_lib("org.phpframework.phpscript.PHPCodePrintingHandler");

class CMSModuleInstallationBLNamespaceHandler {
	
	public function updateExtendedCommonServiceCodeInBusinessLogicPHPFiles($layers, $business_logic_module_paths) {
		$status = true;
		
		//change businesslogic services namespace
		if (is_array($layers) && $business_logic_module_paths)
			foreach ($layers as $Layer)
				if (is_a($Layer, "BusinessLogicLayer")) {
					if (empty($Layer->settings["business_logic_modules_service_common_file_path"]))
						launch_exception(new Exception("\$Layer->settings[business_logic_modules_service_common_file_path] cannot be empty!"));
					
					$common_file_path = $Layer->settings["business_logic_modules_service_common_file_path"];
					
					if (file_exists($common_file_path)) {
						$common_namespace = PHPCodePrintingHandler::getNamespacesFromFile($common_file_path);
						$common_namespace = $common_namespace[0];
						$common_namespace = substr($common_namespace, 0, 1) == "\\" ? substr($common_namespace, 1) : $common_namespace;
						$common_namespace = substr($common_namespace, -1) == "\\" ? substr($common_namespace, 0, -1) : $common_namespace;
						
						if ($common_namespace) {
							$layer_path = $Layer->getLayerPathSetting();
							$layer_path .= substr($layer_path, -1) == "/" ? "" : "/";
							
							//loop all business logic php files and for each search if contains "extends \CommonService". If yes, replaces it by "extends \$common_namespace\CommonService"
							foreach ($business_logic_module_paths as $module_path)
								if (substr($module_path, 0, strlen($layer_path)) == $layer_path && !self::updateExtendedCommonServiceCodeInBusinessLogicPHPFolder($module_path, $common_namespace))
										$status = false;
						}
					}
				}
		
		return $status;
	}
	
	private static function updateExtendedCommonServiceCodeInBusinessLogicPHPFolder($path, $namespace) {
		$status = true;
		
		if ($path && is_dir($path)) {
			$files = array_diff(scandir($path), array('..', '.'));
			
			foreach ($files as $file) {
				$file_path = "$path/$file";
				
				if (is_dir($file_path)) {
					if (!self::updateExtendedCommonServiceCodeInBusinessLogicPHPFolder($file_path, $namespace))
						$status = false;
				}
				else if (strtolower(pathinfo($file, PATHINFO_EXTENSION)) == "php") {
					$content = file_get_contents($file_path);
					
					if (preg_match("/\s+extends\s+\\\\CommonService/", $content)) {
						$content = preg_replace("/\s+extends\s+\\\\CommonService/", " extends \\$namespace\\CommonService", $content, 1);
						//error_log("$file_path($namespace), $content\n\n", 3, "/var/www/html/livingroop/default/tmp/phpframework.log");
						
						if (file_put_contents($file_path, $content) === false)
							$status = false;
					}
				}
			}
		}
		
		return $status;
	}
}
?>
