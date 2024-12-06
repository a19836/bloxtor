<?php
//NOTE: IF YOU MAKE	ANY CHANGES IN THIS FILE, PLEASE BE SURE THAT THE create_project.php COVERS THAT CHANGES AND DOESN'T BREAK ITS LOGIC.

include_once get_lib("org.phpframework.layer.presentation.cms.module.CMSEntityInstallationHandler");
include_once get_lib("org.phpframework.util.web.MyCurl");
include_once $EVC->getUtilPath("CMSPresentationLayerHandler");
include_once $EVC->getUtilPath("LayoutTypeProjectHandler");
include_once $EVC->getUtilPath("WorkFlowPresentationHandler");
include_once $EVC->getUtilPath("ConvertRemoteUrlHandler");
include_once $EVC->getUtilPath("OpenAIActionHandler");

$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "access");

$bean_name = isset($_GET["bean_name"]) ? $_GET["bean_name"] : null;
$bean_file_name = isset($_GET["bean_file_name"]) ? $_GET["bean_file_name"] : null;
$path = isset($_GET["path"]) ? $_GET["path"] : null;
$filter_by_layout = isset($_GET["filter_by_layout"]) ? $_GET["filter_by_layout"] : null; //optional
$on_success_js_func = isset($_GET["on_success_js_func"]) ? $_GET["on_success_js_func"] : null;
$popup = isset($_GET["popup"]) ? $_GET["popup"] : null;

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
			
			if (!empty($_POST) && (
				!empty($_FILES["zip_file"]) || 
				(isset($_POST["zip_url"]) && trim($_POST["zip_url"])) || 
				(isset($_POST["remote_url"]) && trim($_POST["remote_url"])) || 
				(isset($_POST["instructions"]) && trim($_POST["instructions"])) || 
				!empty($_FILES["image"])
			)) {
				$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "write");
				$UserAuthenticationHandler->checkInnerFilePermissionAuthentication($file_path, "layer", "access");
				
				$webroot_folder_path = $PEVC->getWebrootPath();
				$blocks_folder_path = $PEVC->getBlocksPath();
				
				$is_ai = (isset($_POST["instructions"]) && !empty(trim($_POST["instructions"]))) || !empty($_FILES["image"]);
				$is_remote_url = !$is_ai && isset($_POST["remote_url"]) && !empty(trim($_POST["remote_url"]));
				
				if ($is_ai) {
					if (!$openai_encryption_key)
						$error_message = "Artificial Intelligence is disabled. To enable it, please add your OpenAI Key in the 'Manage Permissions/Users' panel.";
					else {
						$instructions = isset($_POST["instructions"]) ? $_POST["instructions"] : null;
						$image = isset($_FILES["image"]) ? $_FILES["image"] : null;
						
						if ($image && file_exists($image["tmp_name"])) {
							$reply = OpenAIActionHandler::describeImage($openai_encryption_key, array($image), $instructions);
							
							if ($reply)
								$instructions .= "\n\n" . $reply;
						}
						
						$res = OpenAIActionHandler::generateHTMLPage($openai_encryption_key, $instructions);
						$html = isset($res["html"]) ? $res["html"] : null;
						$status = false;
						
						if ($html) {
							//get head and body tag and then add the html to the correspondent regions of the blank template. Set Blank template also in the file, with correspondent body attributes and disable bootstrap lib also.
							$head_props = WorkFlowPresentationHandler::getHtmlTagProps($html, "head", array("get_inline_code" => true));
							$body_props = WorkFlowPresentationHandler::getHtmlTagProps($html, "body", array("get_inline_code" => true));
							$head_html = isset($head_props["inline_code"]) ? $head_props["inline_code"] : null;
							$body_html = isset($body_props["inline_code"]) ? $body_props["inline_code"] : null;
							$body_attributes = isset($body_props["html_attributes"]) ? $body_props["html_attributes"] : null;
							
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
							$status = file_put_contents($file_path, $code) !== false;
						}
						
						if (!$status)
							$error_message = "Error: Could not generate page. Please try again...";
					}
				}
				else if (!$is_remote_url) {
					$is_zip_url = empty($_FILES["zip_file"]) && isset($_POST["zip_url"]) && trim($_POST["zip_url"]);
					
					//download zip_url
					if ($is_zip_url) {
						$zip_url = isset($_POST["zip_url"]) ? $_POST["zip_url"] : null;
						//echo "<pre>zip_url:$zip_url\n";die();
						
						$downloaded_file = MyCurl::downloadFile($zip_url, $fp);
						
						if ($downloaded_file && isset($downloaded_file["type"]) && stripos($downloaded_file["type"], "zip") !== false)
							$_FILES["zip_file"] = $downloaded_file;
					}
					
					//install zip file
					if (!empty($_FILES["zip_file"]) && isset($_FILES["zip_file"]["name"]) && trim($_FILES["zip_file"]["name"]) && !empty($_FILES["zip_file"]["tmp_name"])) {
						$name = $_FILES["zip_file"]["name"];
						
						//echo "<pre>";print_r($_FILES["zip_file"]);die();
						$entities_temp_folder_path = CMSEntityInstallationHandler::getTmpRootFolderPath();
						$zipped_file_path = $entities_temp_folder_path . $name;
						$dest_file_path = substr($zipped_file_path, 0, -4) . "/";
						$extension = strtolower( pathinfo($name, PATHINFO_EXTENSION) );
						
						if ($extension != "zip")
							$error_message = "File '$name' must be a zip file!";
						else if (!is_dir($entities_temp_folder_path) && !mkdir($entities_temp_folder_path, 0755, true))
							$error_message = "Error: trying to create tmp folder to upload '$name' file!";
						else {
							$continue = $is_zip_url ? rename($_FILES["zip_file"]["tmp_name"], $zipped_file_path) : move_uploaded_file($_FILES["zip_file"]["tmp_name"], $zipped_file_path);
							
							if ($continue) {
								//Delete folder in case it exists before, bc we are uploading a new zip and we dont want the old zip files.
								CacheHandlerUtil::deleteFolder($dest_file_path);
								
								//unzip
								$unzipped_folder_path = CMSEntityInstallationHandler::unzipEntityFile($zipped_file_path, $dest_file_path); //unzipped_module_path is the same than dest_file_path if unzip successfully
								
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
										
										if (!empty($e->problem))
											$messages[] = array("msg" => $e->problem, "type" => "exception");
									}
									
									CacheHandlerUtil::deleteFolder($unzipped_folder_path); //unzipped_module_path is the same than dest_file_path
								}
								
								unlink($zipped_file_path);
							}
							else 
								$error_message = "Error: Could not upload file. Please try again...";
						}
					}
					else 
						$error_message = "Error: Could not upload file. Please try again...";
					
					if ($is_zip_url && $fp)
						fclose($fp);
				}
				else { //prepare remote url
					$remote_url = isset($_POST["remote_url"]) ? $_POST["remote_url"] : null;
					
					if ($remote_url) {
						$html = ConvertRemoteUrlHandler::getUrlHtml($remote_url, $real_remote_url);
						$html = trim($html);
						$remote_url = $real_remote_url;
						$parts = parse_url($remote_url);
						$status = false;
						
						if (!empty($parts["host"])) {
							$page_webroot_folder_suffix = "page/" . $parts["host"] . "/";
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
								$head_html = isset($head_props["inline_code"]) ? $head_props["inline_code"] : null;
								$body_html = isset($body_props["inline_code"]) ? $body_props["inline_code"] : null;
								$body_attributes = isset($body_props["html_attributes"]) ? $body_props["html_attributes"] : null;
								
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
