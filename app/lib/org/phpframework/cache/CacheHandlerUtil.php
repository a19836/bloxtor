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

include_once get_lib("org.phpframework.util.HashCode");

class CacheHandlerUtil {
	/*private*/ const CACHE_FILE_EXTENSION = "cache";
	
	public static function getCacheFilePath($file_path) {
		if ($file_path)
			$file_path = $file_path . "." . self::CACHE_FILE_EXTENSION;
		
		return $file_path;
	}
	
	public static function getFilePathKey($file_name) {
		return HashCode::getHashCodePositive($file_name);
	}
	
	public static function getConfigureRegexp($regexp) {
		$pos = strrpos($regexp, "/");
		if($pos > 0)
			return $regexp;
			
		$regexp = substr($regexp, 0, 1) != "/" ? "/" . $regexp : $regexp;
		$regexp .= substr($regexp, strlen($regexp) - 1) != "/" ? "/" : "";
		
		return $regexp;
	}
	
	public static function checkIfKeyTypeMatchValue($value, $key, $type) {
		$status = false;
		
		if (empty($type))
			$status = $value === $key;
		else {
			$type = strtolower($type);
			
			switch ($type) {
				case "regexp":
				case "regex":
					$regexp = self::getConfigureRegexp($key);
					$status = preg_match($regexp, $value);
					break;
					
				case "start":
				case "begin":
				case "prefix":
					$status = substr($value, 0, strlen($key)) == $key;
					//echo "<br>$value ### ".substr($value, 0, strlen($key))." == $key:$status<br>";
					break;
					
				case "middle":
					$pos = strpos($value, $key);
					$status = is_numeric($pos) && $pos >= 0;
					break;
					
				case "end":
				case "finish":
				case "suffix":
					$status = substr($value, strlen($value) - strlen($key)) == $key;
					break;
			}
		}
		
		return $status;
	}
	
	public static function getCorrectKeyType($type) {
		switch(strtolower($type)) {
			case "regex":
			case "regexp": return "regexp";
			
			case "start":
			case "begin":
			case "prefix": return "prefix";
			
			case "middle": return "middle";
			
			case "end":
			case "finish":
			case "suffix": return "suffix";
		}
		return "";
	}
	
	public static function getRegexFromKeyType($key, $type) {
		$type = self::getCorrectKeyType($type);
		
		$regex = false;
		
		switch(strtolower($type)) {
			case "regexp":
				$regex = $key;
				break;
			case "prefix": 
				$regex = $key . "(.*)";
				break;
			case "middle":
				$regex = "(.*)" . $key . "(.*)";
				break;
			case "suffix":
				$regex = "(.*)" . $key;
				break;
		}
		
		$regex = self::getConfigureRegexp($regex);
		
		return $regex;
	}
	
	public static function configureFolderPath(&$dir_path) {
		$dir_path .= $dir_path && substr($dir_path, -1) != "/" ? "/" : "";
	}
	
	public static function preparePath($dir_path) {
		/*$dir_path_aux = $dir_path;
		$folders_to_create = array();
		do {
			if(file_exists($dir_path_aux))
				break;
			
			$folders_to_create[] = $dir_path_aux;
			$dir_path_aux = dirname($dir_path_aux);
		} while($dir_path_aux && $dir_path_aux != "/" && $dir_path_aux != "." && $dir_path_aux != "..");
	
		for($i = count($folders_to_create) - 1; $i >= 0; --$i) {
			$folder_to_create = $folders_to_create[$i];
			$base_name = basename($folder_to_create);
			
			if($base_name != ".." && $base_name != ".") {
				//echo "$folder_to_create\n";
				if(!mkdir($folder_to_create, 0777))
					return false;
			}
		}
		return true;*/
		
		return !empty($dir_path) && !file_exists($dir_path) ? @mkdir($dir_path, 0755, true) : true;//before it was 0777. It the framework starts giving errors, it could be because of this.
	}
	
	public static function deleteFolder($dir, $rm_parent = true, $reserved_files = array()) {
		if ($dir) {
			if (is_dir($dir)) {
				$status = true;
				$exists = false;
				$files = array_diff(scandir($dir), array('.', '..'));
				
				foreach ($files as $file) {
					$fp = $dir . "/" . $file;
					
					if (in_array(realpath($fp), $reserved_files))
						$exists = true;
					else if (is_dir($fp)) {
						if (!self::deleteFolder($fp, true))
							$status = false;
					}
					else if (!unlink($fp)) 
						$status = false;
				}
				
				return !$exists && $rm_parent && $status ? rmdir($dir) : $status;
			}
		}
		return true;
	}
	
	public static function serializeContent($content) {
		return serialize($content);
	}
	
	public static function unserializeContent($content) {
		return !empty($content) ? unserialize($content) : false;
	}
}
?>
