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

include_once get_lib("org.phpframework.xmlfile.XMLFileParser");
include get_lib("org.phpframework.module.exception.ModulePathException");

class ModulePathHandler {
	
	public static function getModuleFolderPath($module_id, $modules_file_path, $layer_path, &$modules_path, $settings, $ModuleCacheLayer) {
		$module_path = self::getModulePath($module_id, $modules_file_path, $layer_path, $modules_path, $settings, $ModuleCacheLayer, true);
		
		if ($module_path) {
			if (is_dir($module_path))
				return $module_path;
		
			launch_exception(new ModulePathException(2, array(get_class($ModuleCacheLayer->getLayer()), $module_path)));
			return false;
		}
	}
	
	public static function getModuleFilePath($module_id, $modules_file_path, $layer_path, &$modules_path, $settings, $ModuleCacheLayer) {
		$module_path = self::getModulePath($module_id, $modules_file_path, $layer_path, $modules_path, $settings, $ModuleCacheLayer, false);
		
		if ($module_path) {
			if (file_exists($module_path))
				return $module_path;
		
			launch_exception(new ModulePathException(2, array(get_class($ModuleCacheLayer->getLayer()), $module_path)));
			return false;
		}
	}
	
	public static function getModulePath($module_id, $modules_file_path, $layer_path, &$modules_path, $settings, $ModuleCacheLayer, $is_folder) {
		if (!$module_id) {
			launch_exception(new ModulePathException(3, get_class($ModuleCacheLayer->getLayer())));
			return false;
		}
		
		if (isset($modules_path[$module_id])) {
			if (!file_exists($modules_path[$module_id]) && file_exists($layer_path . $modules_path[$module_id])) //For the cases where we have an ALIASES for a folder like TEST to the test/ folder.
				return $layer_path . $modules_path[$module_id] . ($is_folder ? "/" : "");
			
			return $modules_path[$module_id];
		}
		
		//get alias from modules.xml
		if ($ModuleCacheLayer->cachedModulesPathExists())
			$modules_path = $ModuleCacheLayer->getCachedModulesPath();
		else {
			$rel_modules_path = self::getRelativeModulesPath($modules_file_path, $settings);
			foreach($rel_modules_path as $key => $value)
				if ($value && empty($modules_path[$key]))
					$modules_path[$key] = $layer_path . $value . ($is_folder ? "/" : "");
			
			$ModuleCacheLayer->setCachedModulesPath($modules_path);
		}
		
		if (isset($modules_path[$module_id]) && !file_exists($modules_path[$module_id]) && file_exists($layer_path . $modules_path[$module_id])) //For the cases where we have an ALIASES for a folder like TEST to the test/ folder.
			$modules_path[$module_id] = $layer_path . $modules_path[$module_id] . ($is_folder ? "/" : "");
		else if (!isset($modules_path[$module_id]) && file_exists($layer_path . $module_id))
			$modules_path[$module_id] = $layer_path . $module_id . ($is_folder ? "/" : "");
		
		if (isset($modules_path[$module_id]) && $modules_path[$module_id]) {
			$ModuleCacheLayer->setCachedModulesPath($modules_path);
			return $modules_path[$module_id];
		}
		
		//START: in case of case insensitive.
		$module_id_lower = strtolower(trim($module_id));
		
		if ($modules_path)
			foreach ($modules_path as $key => $value) 
				if (strtolower(trim($key)) == $module_id_lower) {
					$modules_path[$module_id] = $value;
					$ModuleCacheLayer->setCachedModulesPath($modules_path);
					return $value;
				}
		
		$module_path = self::getModulePathFromFileSystem($module_id, $layer_path, $is_folder);
		
		if ($module_path && file_exists($module_path)) {
			$modules_path[$module_id] = $module_path;
			$ModuleCacheLayer->setCachedModulesPath($modules_path);
			return $module_path;
		}
		//END: in case of case insensitive.
		
		launch_exception(new ModulePathException(1, array(get_class($ModuleCacheLayer->getLayer()), $module_id)));
		return false;
	}
	
	public static function getModulePathFromFileSystem($module_id, $layer_path, $is_folder) {
		$module_path = false;
		
		$module_id_lower = strtolower(trim($module_id));
		
		$folders = array();
	
		if (is_dir($layer_path) && ($dir = opendir($layer_path)) ) {
			while( ($file = readdir($dir)) !== false) {
				if (is_dir($layer_path . "/" . $file)) {
					$folders_name[] = $file;
					
					if (strtolower(trim($file)) == $module_id_lower) {
						$module_path = $layer_path . "/" . $file . ($is_folder ? "/" : "");
						$module_path = preg_replace("/\/+/", "/", $module_path);
						break;
					}
				}
			}
			closedir($dir);
		}
		
		//check if $module_id contains "." and if so finds the correspondent file system path
		if (empty($module_path)) {
			$module_id_aux = $module_id;
			$layer_path_aux = $layer_path;
			
			do {
				$loop_again = false;
			
				$pos = strpos($module_id_aux, ".");
				
				if ($pos > 0) {
					$new_module_id = substr($module_id_aux, 0, $pos);
					$next_module_id = substr($module_id_aux, $pos + 1);
					
					$new_module_path = self::getModulePathFromFileSystem($new_module_id, $layer_path_aux, $is_folder);
				
					if ($new_module_path) {
						if (file_exists($new_module_path . "/" . $next_module_id)) {
							$module_path = $new_module_path . "/" . $next_module_id . ($is_folder ? "/" : "");
							$module_path = preg_replace("/\/+/", "/", $module_path);
						}
						else if (strpos($next_module_id, ".") > 0) {
							$layer_path_aux = $new_module_path;
							$module_id_aux = $next_module_id;
							
							$loop_again = true;
						}
					}
				}
			}
			while($loop_again);
		}
		
		/*echo "\n<br>";	
		echo "MODULE_ID: $module_id\n<br>";	
		echo "LAYER_PATH: $layer_path\n<br>";	
		echo "MODULE_PATH: $module_path\n<br>";	
		echo "\n<br>";*/
		
		return $module_path;
	}
	
	public static function getRelativeModulesPath($file_path, $settings) {
		$xml_schema_file_path = get_lib("org.phpframework.xmlfile.schema.modules", "xsd");
		$nodes = XMLFileParser::parseXMLFileToArray($file_path, array("vars" => $settings), $xml_schema_file_path);
		
		$first_node_name = is_array($nodes) ? array_keys($nodes) : array();
		$first_node_name = isset($first_node_name[0]) ? $first_node_name[0] : null;
		
		$module_nodes = $first_node_name && isset($nodes[$first_node_name][0]["childs"]["module"]) ? $nodes[$first_node_name][0]["childs"]["module"] : null;
		$modules_path = array();
		
		$t = $module_nodes ? count($module_nodes) : 0;
		for($i = 0; $i < $t; $i++) {
			$module = $module_nodes[$i];
			
			$id = XMLFileParser::getAttribute($module, "id");
			$value = XMLFileParser::getValue($module);
			
			$modules_path[$id] = $value;
		}
		
		return $modules_path;
	}
}
?>
