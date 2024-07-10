<?php
include_once get_lib("org.phpframework.util.web.html.CssAndJSFilesOptimizer");

//load external lib dynamically bc is a LGPL licence, which means our framework must work without this library also. This means if the user doesn't have this library installed or if he removes it, our code still needs to work.
if (file_exists( get_lib("lib.vendor.phpjavascriptpacker.src.Packer") ))
	include_once get_lib("lib.vendor.phpjavascriptpacker.src.Packer");

class CMSObfuscateJSFilesHandler {
	private $cms_path;
	private $packer_class_exists = false;
	
	public function __construct($cms_path) {
		$this->cms_path = $cms_path;
		
		$this->packer_class_exists = class_exists("Tholu\Packer\Packer");
	}
	
	public function obfuscate($opts, $files_settings) {
		//remove files that don't exists
		$files_settings = $this->removeUnexistentFilesSettings($files_settings);
		
		//obfuscate files
		$encoding = isset($opts["encoding"]) ? $opts["encoding"] : null;
	 	$fast_decode = !empty($opts["fast_decode"]);
	 	$special_chars = !empty($opts["special_chars"]);
	 	$remove_semi_colons = !empty($opts["remove_semi_colons"]);
	 	$copyright = isset($opts["copyright"]) ? $opts["copyright"] : null;
	 	$allowed_domains = isset($opts["allowed_domains"]) ? trim($opts["allowed_domains"]) : ""; //separated by comma
	 	$check_allowed_domains_port = isset($opts["check_allowed_domains_port"]) ? $opts["check_allowed_domains_port"] : null;
	 	$php_packer = is_array($opts) && array_key_exists("php_packer", $opts) ? $opts["php_packer"] : true;
	 	
	 	$errors = array();
		$status = true;
	 	
	 	foreach ($files_settings as $file_path => $file_settings) {
	 		if (!is_array($file_settings[1]))
	 			$file_settings[1] = array();
	 		
	 		$file_opts = array(
		 		"encoding" => array_key_exists("encoding", $file_settings[1]) ? $file_settings[1]["encoding"] : $encoding,
		 		"fast_decode" => array_key_exists("fast_decode", $file_settings[1]) ? $file_settings[1]["fast_decode"] : $fast_decode,
		 		"special_chars" => array_key_exists("special_chars", $file_settings[1]) ? $file_settings[1]["special_chars"] : $special_chars,
		 		"remove_semi_colons" => array_key_exists("remove_semi_colons", $file_settings[1]) ? $file_settings[1]["remove_semi_colons"] : $remove_semi_colons,
		 		"copyright" => array_key_exists("copyright", $file_settings[1]) ? $file_settings[1]["copyright"] : $copyright,
		 		"allowed_domains" => array_key_exists("allowed_domains", $file_settings[1]) ? $file_settings[1]["allowed_domains"] : $allowed_domains,
		 		"check_allowed_domains_port" => $check_allowed_domains_port,
		 		"php_packer" => array_key_exists("php_packer", $file_settings[1]) ? $file_settings[1]["php_packer"] : $php_packer,
	 		);
	 		
	 		$save_path = isset($file_settings[1]["save_path"]) ? $file_settings[1]["save_path"] : null;
	 		
	 		if (!$this->obfuscateJavaScriptFile($file_path, $save_path, $file_opts, $errors))
	 			$status = false;
		}
		
		return array(
			"status" => $status,
			"errors" => $errors,
		);
	}
	
	public function getConfiguredOptions($options) {
		$opts = array();
		
		if (!is_array($options))
			parse_str($options, $opts);
		else
			$opts = $options;
		
		if (!isset($opts["encoding"]))
			$opts["encoding"] = "Normal";
		
		if (!isset($opts["fast_decode"]))
			$opts["fast_decode"] = true;
		
		if (!isset($opts["special_chars"]))
			$opts["special_chars"] = false;
		
		if (!isset($opts["remove_semi_colons"]))
			$opts["remove_semi_colons"] = true;
		
		if (!isset($opts["copyright"]))
			$opts["copyright"] = '/*
 * Copyright (c) 2024 Bloxtor - http://bloxtor.com
 * 
 * Please note that this code belongs to the Bloxtor framework and must comply with the Bloxtor license.
 * If you do not accept these provisions, or if the Bloxtor License is not present or cannot be found, you are not entitled to use this code and must stop and delete it immediately.
 */';
	 	
	 	return $opts;
	}
	
	private function removeUnexistentFilesSettings($files_settings) {
		if ($files_settings)
			foreach ($files_settings as $file_path => $file_settings)
				if (!file_exists($file_path))
					unset($files_settings[$file_path]);
		
		return $files_settings;
	}
	
	public function getDefaultFilesSettings($dest, $cms_relative_common_webroot_path, $cms_relative_system_common_webroot_path, $cms_relative_system_webroot_path) {
		$files_settings = array(
			//MyJSLib.js
			$this->cms_path . $cms_relative_common_webroot_path . "js/MyJSLib.js" => array(
				1 => array(
					"save_path" => $dest . $cms_relative_common_webroot_path . "js/MyJSLib.js",
				),
			),
			$this->cms_path . $cms_relative_system_common_webroot_path . "js/MyJSLib.js" => array(
				1 => array(
					"save_path" => $dest . $cms_relative_system_common_webroot_path . "js/MyJSLib.js",
				),
			),
			
			//MyWidgetResourceLib.js
			$this->cms_path . $cms_relative_common_webroot_path . "js/MyWidgetResourceLib.js" => array(
				1 => array(
					"save_path" => $dest . $cms_relative_common_webroot_path . "js/MyWidgetResourceLib.js",
				),
			),
			$this->cms_path . $cms_relative_system_common_webroot_path . "js/MyWidgetResourceLib.js" => array(
				1 => array(
					"save_path" => $dest . $cms_relative_system_common_webroot_path . "js/MyWidgetResourceLib.js",
				),
			),
			
			//LayoutUIEditor JS Files
			$this->cms_path . $cms_relative_system_webroot_path . "lib/jquerylayoutuieditor/js/" => array(
				1 => array(
					"save_path" => $dest . $cms_relative_system_webroot_path . "lib/jquerylayoutuieditor/js/",
				),
			),
			
			//jquery.myfancybox.js
			$this->cms_path . $cms_relative_common_webroot_path . "vendor/jquerymyfancylightbox/js/jquery.myfancybox.js" => array(
				1 => array(
					"save_path" => $dest . $cms_relative_common_webroot_path . "vendor/jquerymyfancylightbox/js/jquery.myfancybox.js",
				),
			),
			$this->cms_path . $cms_relative_system_common_webroot_path . "vendor/jquerymyfancylightbox/js/jquery.myfancybox.js" => array(
				1 => array(
					"save_path" => $dest . $cms_relative_system_common_webroot_path . "vendor/jquerymyfancylightbox/js/jquery.myfancybox.js",
				),
			),
			
			//myautocomplete.js
			$this->cms_path . $cms_relative_common_webroot_path . "vendor/myautocomplete/js/MyAutoComplete.js" => array(
				1 => array(
					"save_path" => $dest . $cms_relative_common_webroot_path . "vendor/myautocomplete/js/MyAutoComplete.js",
				),
			),
			$this->cms_path . $cms_relative_system_common_webroot_path . "vendor/myautocomplete/js/MyAutoComplete.js" => array(
				1 => array(
					"save_path" => $dest . $cms_relative_system_common_webroot_path . "vendor/myautocomplete/js/MyAutoComplete.js",
				),
			),
			
			//codebeautifier.js
			$this->cms_path . $cms_relative_common_webroot_path . "vendor/mycodebeautifier/js/MyCodeBeautifier.js" => array(
				1 => array(
					"save_path" => $dest . $cms_relative_common_webroot_path . "vendor/mycodebeautifier/js/MyCodeBeautifier.js",
				),
			),
			$this->cms_path . $cms_relative_system_common_webroot_path . "vendor/mycodebeautifier/js/MyCodeBeautifier.js" => array(
				1 => array(
					"save_path" => $dest . $cms_relative_system_common_webroot_path . "vendor/mycodebeautifier/js/MyCodeBeautifier.js",
				),
			),
			
			//mytree.js
			$this->cms_path . $cms_relative_common_webroot_path . "vendor/jquerymytree/js/mytree.js" => array(
				1 => array(
					"save_path" => $dest . $cms_relative_common_webroot_path . "vendor/jquerymytree/js/mytree.js",
				),
			),
			$this->cms_path . $cms_relative_system_common_webroot_path . "vendor/jquerymytree/js/mytree.js" => array(
				1 => array(
					"save_path" => $dest . $cms_relative_system_common_webroot_path . "vendor/jquerymytree/js/mytree.js",
				),
			),
			
			//TaskFlowChart.js
			$this->cms_path . $cms_relative_system_webroot_path . "lib/jquerytaskflowchart/js/TaskFlowChart.js" => array(
				1 => array(
					"save_path" => $dest . $cms_relative_system_webroot_path . "lib/jquerytaskflowchart/js/TaskFlowChart.js",
				),
			),
			
			//ExternalLibHandler.js
			$this->cms_path . $cms_relative_system_webroot_path . "lib/jquerytaskflowchart/js/ExternalLibHandler.js" => array(
				1 => array(
					"save_path" => $dest . $cms_relative_system_webroot_path . "lib/jquerytaskflowchart/js/ExternalLibHandler.js",
				),
			),
			
			//MyHtmlBeautify.js
			/* For some reason the Tholu\Packer\Packer doesn't like too much the code:
			  	'if (str[i] == "\\")', which is replacing it with 'if (str[i] == "\")'
			 and 
			 	'/^<\/?(php|ptl|\?):(.+)$/i.exec(ptl)', which is replacing it with '/^<\/??(php|ptl|):(.+)$/i.exec(ptl)'
			 So we need to disable obfuscate for this files and simple minified it.
			 
			 In the future find a better way to obfuscate the MyHtmlBeautify.js */
			$this->cms_path . $cms_relative_system_webroot_path . "lib/myhtmlbeautify/MyHtmlBeautify.js" => array(
				1 => array(
					"save_path" => $dest . $cms_relative_system_webroot_path . "lib/myhtmlbeautify/MyHtmlBeautify.js",
					"php_packer" => false, 
				),
			),
		);
		
		return $files_settings;
	}
	
	private function obfuscateJavaScriptFile($src_path, $dst_path, $file_opts, &$errors) {
		$status = true;
		
		if (file_exists($src_path)) {
			if (is_dir($src_path)) {
				$files = scandir($src_path);
				
				if ($files)
					foreach ($files as $file_name) 
						if ($file_name != "." && $file_name != ".." && (is_dir("$src_path/$file_name") || substr($file_name, -3) == ".js")) //be sure that only js files are obfuscated inside of folder.
							if (!$this->obfuscateJavaScriptFile("$src_path/$file_name", "$dst_path/$file_name", $file_opts, $errors))
								$status = false;
			}
			else { //if (substr($src_path, -3) == ".js") { //Do not uncomment this, bc if there is a file without a '.js' extension, it's possible to obfuscate it too, but only if is registered in the $files_settings variable above.
				if (!$dst_path) {
					$errors[] = "Save Path '$dst_path' is empty!";
					$status = false;
				}
				else if (!is_dir(dirname($dst_path)) && !mkdir(dirname($dst_path), 0755, true)) {
					$errors[] = "Save Path '" . dirname($dst_path) . "' is not a folder!";
					$status = false;
				}
				else {
					$code = file_get_contents($src_path);
					
					if ($code) {
						$copyright = isset($file_opts["copyright"]) ? $file_opts["copyright"] : null;
						$allowed_domains = isset($file_opts["allowed_domains"]) ? $file_opts["allowed_domains"] : null;
						$check_allowed_domains_port = isset($file_opts["check_allowed_domains_port"]) ? $file_opts["check_allowed_domains_port"] : null;
						$php_packer = !is_array($file_opts) || !array_key_exists("php_packer", $file_opts) || $file_opts["php_packer"];
						
						if ($allowed_domains) {
							$search_str = '/* #ADD_SECURITY_CODE_HERE# */';
							$pos = strpos($code, $search_str);
							
							if ($pos !== false) {
								//echo "\nFOUND: " . substr($code, $pos, 100);
								$location_var = $check_allowed_domains_port ? 'location.host' : 'location.hostname';
								$allowed_domains = str_replace('"', '', $allowed_domains);
								
								$security_code = '
var cd = "" + ' . $location_var . ';
var ads = "' . ($check_allowed_domains_port ? $allowed_domains . "," : preg_replace("/:[0-9]+,/", ",", $allowed_domains . ",")) . '";

cd = cd ? cd.replace(/:80$/, "").toLowerCase() : "";
ads = ads.replace(/;/g, ",").replace(/:80,/g, ",").toLowerCase();

if (!cd)
	return;
else {
	var arr = ads.split(",");
	var parsed_arr = [];
	
	for (var i = 0, t = arr.length; i < t; i++) {
		var ad = arr[i].replace(/(^\s+|\s+$)/, "");
		
		if (ad)
			parsed_arr.push(ad);
	}
	
	if (parsed_arr.length > 0 && parsed_arr.indexOf(cd) == -1) {
		var sd = false;
		
		for (var i = 0, t = parsed_arr.length; i < t; i++)
			if ((cd + ",").indexOf("." + parsed_arr[i] + ",")) {
				sd = true;
				break;
			}
		
		if (!sd)
			return;
	}
}';
								$security_code = preg_replace("/\n+/", "", $security_code);
								
								$code = str_replace($search_str, $security_code, $code);
							}
						}
						
						if ($php_packer && $this->packer_class_exists) {
							$encoding = isset($file_opts["encoding"]) ? $file_opts["encoding"] : null;
							$fast_decode = isset($file_opts["fast_decode"]) ? $file_opts["fast_decode"] : null;
							$special_chars = isset($file_opts["special_chars"]) ? $file_opts["special_chars"] : null;
							$remove_semi_colons = isset($file_opts["remove_semi_colons"]) ? $file_opts["remove_semi_colons"] : null;
							
							$packer = new Tholu\Packer\Packer($code, $encoding, $fast_decode, $special_chars, $remove_semi_colons);
							$packed_code = $packer->pack();
						}
						else {
							//remove comments. Don't do anything else bc it could be already encrypted or minimized.
							$code_opts = array(
								"remove_single_line_comments" => true,
								"remove_multiple_lines_comments" => true,
								"remove_white_spaces" => true,
							);
							$packed_code = CssAndJSFilesOptimizer::removeCommentsAndEndLines($code, $code_opts, "js");
						}
						
						if ($packed_code) {
							$packed_code = $copyright . $packed_code;
							
							if (file_put_contents($dst_path, $packed_code) === false)
								$status = false;
						}
						else {
							$errors[] = "Obfuscate content is empty in '$src_path'!";
							$status = false;
						}
					}
					else {
						$errors[] = "Empty content in '$src_path'!";
						$status = false;
					}
				}
			}
		}
		else {
			$errors[] = "File '$src_path' does not exist!";
			$status = false;
		}
		
		return $status;
	}
}
?>
