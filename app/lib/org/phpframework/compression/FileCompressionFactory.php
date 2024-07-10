<?php
class FileCompressionFactory {
	
	public static function create($class_prefix) {
		$is_valid = self::isValid($class_prefix);
		
		if (!$is_valid)
			throw new Exception("Compression method ($class_prefix) is not allowed!");
		
		$class_name = "{$class_prefix}FileCompressionHandler";
		return new $class_name();
	}
	
	public static function isValid($class_prefix) {
		$class_name = "{$class_prefix}FileCompressionHandler";
		$file_path = get_lib("org.phpframework.compression.{$class_name}");
		
		if (file_exists($file_path)) {
			include_once $file_path;
			
			return is_a($class_name, "IFileCompressionHandler", true);
		}
		
		return false;
	}
}
?>
