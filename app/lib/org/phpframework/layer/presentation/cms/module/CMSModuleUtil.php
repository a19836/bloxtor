<?php
include_once get_lib("org.phpframework.cache.CacheHandlerUtil");

class CMSModuleUtil {
	
	public static function copyFolder($src, $dst) {
		if ($src && $dst && is_dir($src)) {
			if (!is_dir($dst))
				@mkdir($dst, 0755, true);
			
			if (is_dir($dst)) {
				$status = true;
				$files = scandir($src);
				
				if ($files)
					foreach ($files as $file) 
						if ($file != '.' && $file != '..') { 
							if (is_dir("$src/$file")) { 
								if (!self::copyFolder("$src/$file", "$dst/$file")) 
									$status = false;
							} 
							else if (!copy("$src/$file", "$dst/$file"))
									$status = false;
						} 
					
				return $status; 
			}
		}
	}

	public static function copyFile($src, $dst) {
		if ($src && $dst && file_exists($src)) {
			if (is_dir($src))
				return self::copyFolder($src, $dst);
			
			$dir = dirname($dst);
			
			if ($dir && !is_dir($dir))
				@mkdir($dir, 0755, true);
			
			return is_dir($dir) && copy($src, $dst);
		}
	}
	
	public static function copyFileToLayers($src, $dst, $src_module_path, $layer_module_paths) {
		if ($layer_module_paths && $src) {
			$status = true;
			$t = count($layer_module_paths);
			
			for ($i = 0; $i < $t; $i++)
				if (!self::copyFile($src_module_path . "/$src", $layer_module_paths[$i] . "/$dst"))
					$status = false;
		
			return $status;
		}
	}
	
	public static function deleteFiles($files, $reserved_files = array()) {
		$status = true;
		
		if ($files) {
			$t = count($files);
			for ($i = 0; $i < $t; $i++) {
				$path = $files[$i];
				
				if ($path && file_exists($path)) {
					if (is_dir($path)) {
						if (!self::deleteFolder($path, $reserved_files))
							$status = false;
					}
					else if (!unlink($path))
						$status = false;
				}
			}
		}
		return $status;
	}
	
	public static function deleteFolder($dir, $reserved_files = array()) {
		return CacheHandlerUtil::deleteFolder($dir, true, $reserved_files);
	}
	
	public static function deleteFileFromLayers($src, $layer_module_paths, $reserved_files = array()) {
		if ($layer_module_paths && $src) {
			$status = true;
			$files = array();
			$t = count($layer_module_paths);
			
			for ($i = 0; $i < $t; $i++)
				$files[] = $layer_module_paths[$i] . "/$src";
			
			return self::deleteFiles($files, $reserved_files);
		}
	}
}
?>
