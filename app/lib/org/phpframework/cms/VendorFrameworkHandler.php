<?php
class VendorFrameworkHandler {
	
	public static function getVendorFrameworkFolder($path) {
		if (self::isLaravelProjectFolder($path))
			return "laravel";
		
		return null;
	}
	
	public static function isLaravelProjectFolder($path) {
		return file_exists("$path/artisan") && is_dir("$path/app") && is_dir("$path/bootstrap") && is_dir("$path/config") && file_exists("$path/composer.json") && strpos(file_get_contents("$path/composer.json"), '"laravel/framework"');
	}
}
?>
