<?php
//NOTE: IF YOU MAKE	ANY CHANGES IN THIS FILE, PLEASE BE SURE THAT THE create_project.php COVERS THAT CHANGES AND DOESN'T BREAK ITS LOGIC.

include_once get_lib("org.phpframework.layer.presentation.cms.module.CMSEntityInstallationHandler");
include_once get_lib("org.phpframework.util.web.MyCurl");
include_once $EVC->getUtilPath("CMSPresentationLayerHandler");
include_once $EVC->getUtilPath("LayoutTypeProjectHandler");
include_once $EVC->getUtilPath("WorkFlowPresentationHandler");
include_once $EVC->getUtilPath("ConvertRemoteUrlHandler");

$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "access");

$bean_name = $_GET["bean_name"];
$bean_file_name = $_GET["bean_file_name"];
$path = $_GET["path"];
$filter_by_layout = $_GET["filter_by_layout"]; //optional
$on_success_js_func = $_GET["on_success_js_func"];
$popup = $_GET["popup"];

$path = str_replace("../", "", $path);//for security reasons
$filter_by_layout = str_replace("../", "", $filter_by_layout);//for security reasons

if ($bean_name && $bean_file_name && $path) {
	$WorkFlowBeansFileHandler = new WorkFlowBeansFileHandler($user_beans_folder_path . $bean_file_name, $user_global_variables_file_path);
	$PEVC = $WorkFlowBeansFileHandler->getEVCBeanObject($bean_name, $path);

	if ($PEVC) {
		$P = $PEVC->getPresentationLayer();
		$selected_project = $P->getSelectedPresentationId();
		$layer_path = $P->getLayerPathSetting();
		$file_path = $layer_path . $path;
		
		if (!file_exists($file_path))
			$error_message = "File does not exist!";
		else {
			$show_install_page = true;
			
			if ($_POST && ($_FILES["zip_file"] || trim($_POST["zip_url"]) || trim($_POST["remote_url"]))) {
				$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "write");
				$UserAuthenticationHandler->checkInnerFilePermissionAuthentication($file_path, "layer", "access");
				
				$webroot_folder_path = $PEVC->getWebrootPath();
				$blocks_folder_path = $PEVC->getBlocksPath();
				
				$is_remote_url = !empty(trim($_POST["remote_url"]));
				
				if (!$is_remote_url) {
					$is_zip_url = !$_FILES["zip_file"] && trim($_POST["zip_url"]);
					
					//download zip_url
					if ($is_zip_url) {
						$zip_url = $_POST["zip_url"];
						//echo "<pre>zip_url:$zip_url\n";die();
						
						$downloaded_file = MyCurl::downloadFile($zip_url, $fp);
						
						if ($downloaded_file && stripos($downloaded_file["type"], "zip") !== false)
							$_FILES["zip_file"] = $downloaded_file;
					}
					
					//install zip file
					if ($_FILES["zip_file"] && trim($_FILES["zip_file"]["name"])) {
						//echo "<pre>";print_r($_FILES["zip_file"]);die();
						$dest_folder_path = CMSEntityInstallationHandler::getTmpFolderPath();
						
						if (!$dest_folder_path)
							$error_message = "Error: trying to create tmp folder to upload '" . $_FILES["zip_file"]["name"] . "' file!";
						else {
							$zipped_file_path = $dest_folder_path . $_FILES["zip_file"]["name"];
							$dest_file_path = substr($zipped_file_path, 0, -4) . "/";
							
							$continue = $is_zip_url ? rename($_FILES["zip_file"]["tmp_name"], $zipped_file_path) : move_uploaded_file($_FILES["zip_file"]["tmp_name"], $zipped_file_path);
							
							if ($continue) {
								//Delete folder in case it exists before, bc we are uploading a new zip and we dont want the old zip files.
								CacheHandlerUtil::deleteFolder($dest_file_path);
								
								//unzip
								$unzipped_folder_path = CMSEntityInstallationHandler::unzipEntityFile($zipped_file_path, $dest_file_path);
								
								if ($unzipped_folder_path) {
									//install page
									$CMSEntityInstallationHandler = new CMSEntityInstallationHandler($file_path, $webroot_folder_path, $blocks_folder_path, $unzipped_folder_path);
									
									try {
										if ($CMSEntityInstallationHandler->install())
											$status = true;
									}
									catch(Exception $e) {
										$status = false;
										$messages[] = array("msg" => "STATUS: FALSE", "type" => "error");
										$messages[] = array("msg" => "ERROR MESSAGE: " . $e->getMessage(), "type" => "exception");
										$messages[] = array("msg" => $e->problem, "type" => "exception");
									}
									
									CMSModuleUtil::deleteFolder($unzipped_folder_path);
								}
							}
							else 
								$error_message = "Error: Could not upload file. Please try again...";
					
							unlink($zipped_file_path);
							CMSModuleUtil::deleteFolder($dest_folder_path);
						}
					}
					else 
						$error_message = "Error: Could not upload file. Please try again...";
					
					if ($is_zip_url && $fp)
						fclose($fp);
				}
				else { //prepare remote url
					$remote_url = $_POST["remote_url"];
					
					if ($remote_url) {
						$html = ConvertRemoteUrlHandler::getUrlHtml($remote_url, $real_remote_url);
						$html = trim($html);
						$remote_url = $real_remote_url;
						$parts = parse_url($remote_url);
						
						$page_webroot_folder_suffix .= "page/" . $parts["host"] . "/";
						$page_webroot_folder_path = $webroot_folder_path . $page_webroot_folder_suffix;
						
						if (!is_dir($page_webroot_folder_path))
							mkdir($page_webroot_folder_path, 0755, true);
						
						if (is_dir($page_webroot_folder_path)) {
							$status = true;
							
							//save attachments - save all local files in $html into $webroot_folder_path
							if (!ConvertRemoteUrlHandler::saveHtmlRelativeUrls($page_webroot_folder_path, $remote_url, $html, '{$project_url_prefix}' . $page_webroot_folder_suffix))
								$status = false;
							
							//replace final links in $html with '#'
							ConvertRemoteUrlHandler::replaceExtraUrlsInHtml($remote_url, $html);
							
							//parse url and get head and body tag and then add the html to the correspondent regions of the blank template. Set Blank template also in the file, with correspondent body attributes and disable bootstrap lib also.
							$head_props = WorkFlowPresentationHandler::getHtmlTagProps($html, "head", array("get_inline_code" => true));
							$body_props = WorkFlowPresentationHandler::getHtmlTagProps($html, "body", array("get_inline_code" => true));
							$head_html = $head_props["inline_code"];
							$body_html = $body_props["inline_code"];
							$body_attributes = $body_props["html_attributes"];
							
							$code = '<?php
//Templates:
$EVC->setTemplate("blank");

//Template params:
$EVC->getCMSLayer()->getCMSTemplateLayer()->setParam("is_bootstrap_lib_included_in_page_level", true);
$EVC->getCMSLayer()->getCMSTemplateLayer()->setParam("Body Attributes", "' . addcslashes($body_attributes, '\\"') . '");

//Regions-Blocks:
$EVC->getCMSLayer()->getCMSTemplateLayer()->addRegionHtml("Head", "' . addcslashes($head_html, '\\"') . '");

$EVC->getCMSLayer()->getCMSTemplateLayer()->addRegionHtml("Content", "' . addcslashes($body_html, '\\"') . '");
?>';
							
							//save file with new code
							if (file_put_contents($file_path, $code) === false)
								$status = false;
						}
						
						if (!$status)
							$error_message = "Error: Could not convert url to page. Please try again...";
					}
				}
			}
		}
	}
}
?>
