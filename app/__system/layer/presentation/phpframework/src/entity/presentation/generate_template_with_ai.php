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
include_once $EVC->getUtilPath("OpenAIActionHandler");

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
		
		if (!$openai_encryption_key)
			$error_message = "Artificial Intelligence is disabled. To enable it, please add your OpenAI Key in the 'Manage Permissions/Users' panel.";
		else if (!empty($_POST)) {
			$status = false;
			
			if (!empty($_POST["template_name"]) && !empty($_POST["instructions"])) {
				$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "write");
				$UserAuthenticationHandler->checkInnerFilePermissionAuthentication($PEVC->getTemplatesPath(), "layer", "access");
				
				$template_name = $_POST["template_name"];
				$layout_name = isset($_POST["layout_name"]) ? str_replace(array("../", "./", "/", "\\"), "", $_POST["layout_name"]) : "";
				$instructions = $_POST["instructions"];
				$regions = isset($_POST["regions"]) ? $_POST["regions"] : null;
				
				$instructions .= "The template background color should be defined by a class called `template-color`.";
				
				$res = OpenAIActionHandler::generateHTMLTemplate($openai_encryption_key, $instructions, $regions);
				
				if (!empty($res["html"])) {
					$html = $res["html"];
					$original_html = $res["original_html"];
					$regions = $res["regions"];
					
					$template_folder_path = $PEVC->getTemplatesPath() . $template_name . "/";
					
					if (!is_dir($template_folder_path))
						mkdir($template_folder_path, 0755, true);
					
					if (is_dir($template_folder_path)) {
						$status = true;
						$head_region_exists = false;
						
						//prepare webroot folder path based in the $url
						$webroot_folder_path = $PEVC->getWebrootPath() . "template/$template_name/";
						if (!is_dir($webroot_folder_path))
							mkdir($webroot_folder_path, 0755, true);
						
						if (!$layout_name)
							$layout_name = "index";
						else if (strtolower(pathinfo($layout_name, PATHINFO_EXTENSION)) == "php")
							$layout_name = substr($layout_name, 0, -4); //remove php extension
						
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
									
									//save sample.htm
									if (!is_dir($template_region_folder) || file_put_contents($template_region_folder . "sample.htm", $region_html) === false)
										$status = false;
									
									if (strtolower($region_name) == "head")
										$head_region_exists = true;
								}
							}
							else
								$status = false;
						}
						
						//set template-color class color
						if (preg_match("/\.template-color\s*\{([^\}]+)\}/", $html, $match, PREG_OFFSET_CAPTURE)) {
							$bg_color = $text_color = "";
							$replacement = $match[0][0];
							
							if (preg_match("/(\s|;|\{)background-color\s*:([^;]+);/", $match[1][0], $m, PREG_OFFSET_CAPTURE)) {
								$bg_color = trim($m[2][0]);
								$replacement = str_replace($m[0][0], "", $replacement);
							}
							
							if (preg_match("/(\s|;|\{)color\s*:([^;]+);/", $match[1][0], $m, PREG_OFFSET_CAPTURE)) {
								$text_color = trim($m[2][0]);
								$replacement = str_replace($m[0][0], "", $replacement);
							}
							
							if ($bg_color || $text_color) {
								$replacement = preg_replace("/\/\*.*?\*\/|\/\/[^\r\n]*/s", "", $replacement); //remove comments if any
								
								//remove .template-color style
								$html = str_replace($match[0][0], $replacement, $html);
								$html = preg_replace("/\.template-color\s*\{\s*\}/", "", $html);
								$html = preg_replace("/<style([^>]*)>\s*<\/style([^>]*)>/", "", $html);
								
								//add default color params
								$html = '<?php
//Template params:
$EVC->getCMSLayer()->getCMSTemplateLayer()->setParam("Template Background Color", "' . $bg_color . '");
$EVC->getCMSLayer()->getCMSTemplateLayer()->setParam("Template Text Color", "' . $text_color . '");
?>' . $html;
							}
						}
						
						//add head code
						$head_html = '
	<?php
		if (empty($GLOBALS["UserSessionActivitiesHandler"])) {
			@include_once $EVC->getUtilPath("user_session_activities_handler", $EVC->getCommonProjectName());
			@initUserSessionActivitiesHandler($EVC);
		}
		
		if (@$GLOBALS["UserSessionActivitiesHandler"])
			$user_data = $GLOBALS["UserSessionActivitiesHandler"]->getUserData();
		
		$user_type_ids_classes = @$user_data && $user_data["user_type_ids"] ? "user_type_id_" . implode(" user_type_id_", $user_data["user_type_ids"]) : "";
	?>
	<meta name="keywords" content="<?= $EVC->getCMSLayer()->getCMSTemplateLayer()->getParam("Page Keywords"); ?>" />
	<meta name="description" content="<?= $EVC->getCMSLayer()->getCMSTemplateLayer()->getParam("Page Description"); ?>" />
	<meta name="author" content="<?= $EVC->getCMSLayer()->getCMSTemplateLayer()->getParam("Page Author"); ?>">
	<link href="<?= $EVC->getCMSLayer()->getCMSTemplateLayer()->getParam("Icon Url"); ?>" rel="shortcut icon" type="image/x-icon" />

	<title><?= translateProjectLabel($EVC, $EVC->getCMSLayer()->getCMSTemplateLayer()->getParam("Page Title")); ?></title>

	<style>
		:root {
			--template-bg-color:<?php echo $EVC->getCMSLayer()->getCMSTemplateLayer()->getParam("Template Background Color"); ?>;
			--template-text-color:<?php echo $EVC->getCMSLayer()->getCMSTemplateLayer()->getParam("Template Text Color"); ?>;
		}
		.template-color {
			background-color:var(--template-bg-color) !important;
			color:var(--template-text-color) !important;
		}
	</style>
						';
						
						if (!$head_region_exists)
							$head_html .= '
	<?= $EVC->getCMSLayer()->getCMSTemplateLayer()->renderRegion("Head"); ?>';
						
						$html = preg_replace("/<title([^>]*)>.*?<\/title([^>]*)>/i", "", $html);
						$html = preg_replace("/(<\/head)/i", "$head_html$1", $html);
						
						//create file with $template_name/$layout_name.php with content: $html. Only save $html here bc saveHtmlRelativeUrls may change the $html.
						if (file_put_contents($template_folder_path . "$layout_name.php", $html) === false)
							$status = false;
						
						//create file with $template_name/webroot/$layout_name.html with content: $html.
						if (file_put_contents($webroot_folder_path . "$layout_name.html", $original_html) === false)
							$status = false;
						
						//add new layout to template.xml, if exists
						$CMSTemplateInstallationHandler = new CMSTemplateInstallationHandler($template_folder_path, $webroot_folder_path, null);
						if (!$CMSTemplateInstallationHandler->addLayoutToTemplateXml($layout_name, true))
							$status = false;
					}
				}
			}
			else if (empty($_POST["template_name"]))
				$status = "Template name cannot be undefined";
			else
				$status = "Template instructions cannot be undefined";
			
			echo $status;
			die();
		}
		
		$PHPVariablesFileHandler->endUserGlobalVariables();
	}
}
?>
