<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

include_once get_lib("org.phpframework.layer.presentation.cms.module.CMSTemplateInstallationHandler");
include_once $EVC->getUtilPath("WorkFlowBeansFileHandler");
include_once $EVC->getUtilPath("ConvertRemoteUrlHandler");

$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "access");

$bean_name = isset($_GET["bean_name"]) ? $_GET["bean_name"] : null;
$bean_file_name = isset($_GET["bean_file_name"]) ? $_GET["bean_file_name"] : null;
$path = isset($_GET["path"]) ? $_GET["path"] : null;

$path = str_replace("../", "", $path);//for security reasons

if ($bean_name && $bean_file_name && $path) {
	$WorkFlowBeansFileHandler = new WorkFlowBeansFileHandler($user_beans_folder_path . $bean_file_name, $user_global_variables_file_path);
	$PEVC = $WorkFlowBeansFileHandler->getEVCBeanObject($bean_name, $path);

	if ($PEVC) {
		$PHPVariablesFileHandler = new PHPVariablesFileHandler(array($user_global_variables_file_path, $PEVC->getConfigPath("pre_init_config")));
		$PHPVariablesFileHandler->startUserGlobalVariables();
		
		$P = $PEVC->getPresentationLayer();
		$selected_project = $P->getSelectedPresentationId();
		
		//$raw_post_data = htmlspecialchars_decode( file_get_contents("php://input") );
		$raw_post_data = file_get_contents("php://input");
		
		if (!empty($_POST) || $raw_post_data) {
			$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "write");
			$UserAuthenticationHandler->checkInnerFilePermissionAuthentication($PEVC->getTemplatesPath(), "layer", "access");
			
			if (!empty($_POST["load"])) {
				$url = isset($_POST["url"]) ? $_POST["url"] : null;
				
				if ($url) {
					$real_url = null;
					$html = ConvertRemoteUrlHandler::getUrlHtml($url, $real_url);
					$url = $real_url;
					
					echo "$url\n$html";
				}
				
				die();
			}
			else {
				$settings = json_decode($raw_post_data, true); //Not working 
				
				if (!empty($settings["save"]) && !empty($settings["template_name"])) {
					$template_name = str_replace(array("../", "./"), "", $settings["template_name"]);
					$layout_name = isset($settings["layout_name"]) ? str_replace(array("../", "./", "/", "\\"), "", $settings["layout_name"]) : "";
					$url = isset($settings["url"]) ? $settings["url"] : null;
					$doc_type = isset($settings["doc_type"]) ? $settings["doc_type"] : null;
					$html = isset($settings["html"]) ? trim($settings["html"]) : "";
					$regions = isset($settings["regions"]) ? $settings["regions"] : null;
					$params = isset($settings["params"]) ? $settings["params"] : null;
					
					if (!$layout_name)
						$layout_name = "index";
					else if (strtolower(pathinfo($layout_name, PATHINFO_EXTENSION)) == "php")
						$layout_name = substr($layout_name, 0, -4); //remove php extension
					
					$original_html = $html;
					
					//prepare regions and params php code. Replace code created from convert_url_to_template.js with real php code
					if ($regions)
						foreach ($regions as $region_name => $region_html) {
							$code = '&lt;? echo $EVC-&gt;getCMSLayer()-&gt;getCMSTemplateLayer()-&gt;renderRegion("' . $region_name . '"); ?&gt;';
							$html = str_replace($code, htmlspecialchars_decode($code), $html);
							$original_html = str_replace($code, $region_html, $original_html);
						}
					
					if ($params)
						foreach ($params as $param_name => $param_html) {
							$code = '&lt;? echo $EVC-&gt;getCMSLayer()-&gt;getCMSTemplateLayer()-&gt;getParam("' . $param_name . '"); ?&gt;';
							$html = str_replace($code, htmlspecialchars_decode($code), $html);
							$original_html = str_replace($code, $param_html, $original_html);
						}
					
					//add html previous code
					$html = $doc_type . $html;
					$original_html = $doc_type . $original_html;
					
					if ($params) {
						$prev_html = "<?\n";
					
						foreach ($params as $param_name => $param_value) {
							$prev_html .= '$EVC->getCMSLayer()->getCMSTemplateLayer()->setParam("' . $param_name . '", "' . addcslashes($param_value, '"') . '");' . "\n";
						}
						
						$prev_html .= "?>\n";
						$html = $prev_html . $html;
					}
					
					//create template files
					$status = false;
					$template_folder_path = $PEVC->getTemplatesPath() . $template_name . "/";
					
					if (!is_dir($template_folder_path))
						mkdir($template_folder_path, 0755, true);
					
					if (is_dir($template_folder_path)) {
						$status = true;
						
						//prepare webroot folder path based in the $url
						$webroot_folder_path = $PEVC->getWebrootPath() . "template/$template_name/";
						if (!is_dir($webroot_folder_path))
							mkdir($webroot_folder_path, 0755, true);
						
						//save attachments - save all local files in $html into $webroot_folder_path
						if (!is_dir($webroot_folder_path) || !ConvertRemoteUrlHandler::saveHtmlRelativeUrls($webroot_folder_path, $url, $html, '<?php echo $original_project_url_prefix; ?>template/' . $template_name))
							$status = false;
						
						//prepare regions
						if ($regions) {
							$template_regions_folder = $template_folder_path . "region/$layout_name/";
							
							if (!is_dir($template_regions_folder))
								mkdir($template_regions_folder, 0755, true);
							
							if (is_dir($template_regions_folder)) {
								foreach ($regions as $region_name => $region_html) {
									$region_name = str_replace(array("../", "./", "/", "\\"), "", $region_name);
									$template_region_folder = $template_regions_folder . $region_name . "/";
									
									if (!is_dir($template_region_folder))
										mkdir($template_region_folder, 0755, true);
									
									//save all local files in $region_html into $webroot_folder_path
									if (!is_dir($webroot_folder_path) || !ConvertRemoteUrlHandler::saveHtmlRelativeUrls($webroot_folder_path, $url, $region_html, '<?php echo $project_url_prefix; ?>template/' . $template_name))
										$status = false;
									
									//replace final links in $region_html with '#'
									ConvertRemoteUrlHandler::replaceExtraUrlsInHtml($url, $region_html);
									
									//save sample.htm
									if (!is_dir($template_region_folder) || file_put_contents($template_region_folder . "sample.htm", $region_html) === false)
										$status = false;
								}
							}
							else
								$status = false;
						}
						
						//save all local files in $param_html into $webroot_folder_path
						if ($params) {
							foreach ($params as $param_name => $param_html)
								if (!is_dir($webroot_folder_path) || !ConvertRemoteUrlHandler::saveHtmlRelativeUrls($webroot_folder_path, $url, $param_html, '<?php echo $original_project_url_prefix; ?>template/' . $template_name))
									$status = false;
						}
						
						//replace final links in $html with '#'
						ConvertRemoteUrlHandler::replaceExtraUrlsInHtml($url, $html);
						
						//create file with $template_name/$layout_name.php with content: $html. Only save $html here bc saveHtmlRelativeUrls may change the $html.
						if (file_put_contents($template_folder_path . "$layout_name.php", $html) === false)
							$status = false;
						
						//replace final links in $original_html without the $url hostname, leaving only local urls pointing to the new local downloaded files.
						ConvertRemoteUrlHandler::replaceLocalUrlsInHtml($url, $original_html);
						
						//replace final links in $original_html with '#'
						ConvertRemoteUrlHandler::replaceExtraUrlsInHtml($url, $original_html);
						
						//create file with $template_name/webroot/$layout_name.html with content: $html.
						if (file_put_contents($webroot_folder_path . "$layout_name.html", $original_html) === false)
							$status = false;
						
						//add new layout to template.xml, if exists
						$CMSTemplateInstallationHandler = new CMSTemplateInstallationHandler($template_folder_path, $webroot_folder_path, null);
						if (!$CMSTemplateInstallationHandler->addLayoutToTemplateXml($layout_name, true))
							$status = false;
					}
					
					echo $status;
					die();
				}
			}
		}
		
		$PHPVariablesFileHandler->endUserGlobalVariables();
	}
}
?>
