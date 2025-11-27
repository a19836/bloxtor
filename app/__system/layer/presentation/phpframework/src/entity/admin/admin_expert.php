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

include_once get_lib("org.phpframework.util.FilePermissionHandler");
include_once get_lib("org.phpframework.layer.presentation.cms.module.CMSModuleUtil");
include_once get_lib("org.phpframework.compression.ZipHandler");
include_once get_lib("org.phpframework.util.MimeTypeHandler");

$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "access");
$is_admin_ui_expert_allowed = $UserAuthenticationHandler->isFilePermissionAllowed("expert", "admin_ui", "access");

if (empty($is_admin_ui_expert_allowed)) {
	echo '<script>
		alert("You don\'t have permission to access this Workspace!");
		document.location="' . $project_url_prefix . 'auth/logout";
	</script>';
	die();
}

$action = isset($_GET["action"]) ? $_GET["action"] : null;
$path = isset($_GET["path"]) ? $_GET["path"] : null;
$extra = isset($_GET["extra"]) ? trim($_GET["extra"]) : null;

$path = str_replace("../", "", $path);//for security reasons

if ($action) {
	//only allow action if referer is the same, this is if this request comes from the expert workspace.
	$referer = isset($_SERVER["HTTP_REFERER"]) ? $_SERVER["HTTP_REFERER"] : "";
	$referer = explode("?", $referer);
	$referer = $referer[0];
	$referer = explode("#", $referer);
	$referer = $referer[0];
	$referer = preg_replace("/\/+$/", "", $referer);
	$allow_action = $referer == $project_url_prefix . "admin";
	//echo "referer:$referer|allow_action:$allow_action";die();
	
	if (!$allow_action) {
		echo "Not Allowed action";
		die();
	}
	else if ($action == "edit") {
		$layer_path = CMS_PATH;
		include $EVC->getEntityPath("admin/edit_raw_file");
	}
	else if ($action == "upload" && empty($_FILES["file"])) {
		$root_path = CMS_PATH;
		include $EVC->getEntityPath("admin/upload_file");
	}
	else if ($action == "get_sub_files") {
		$sub_files = getAction($entity_path, $UserAuthenticationHandler, $action, $path, $extra);
		include $EVC->getEntityPath("admin/get_sub_files");
	}
	else {
		$output = getAction($entity_path, $UserAuthenticationHandler, $action, $path, $extra);
		
		echo $output;
		die();
	}
}
else {
	$default_page = isset($_GET["default_page"]) ? $_GET["default_page"] : null;
	
	if ($default_page)
		CookieHandler::setCurrentDomainEternalRootSafeCookie("default_page", $default_page);
	else if (!empty($_COOKIE["default_page"]))
		$default_page = $_COOKIE["default_page"];

	if (isset($_GET) && array_key_exists("main_navigator_side", $_GET)) {
		$_COOKIE["main_navigator_side"] = $_GET["main_navigator_side"]; //set cookie directly so it takes efect in the template->body class
		CookieHandler::setCurrentDomainEternalRootSafeCookie("main_navigator_side", $_GET["main_navigator_side"]);
	}

	//Preparing tools permissions
	$is_flush_cache_allowed = $UserAuthenticationHandler->isPresentationFilePermissionAllowed($EVC->getEntityPath("admin/flush_cache"), "delete");
	$is_manage_modules_allowed = $UserAuthenticationHandler->isPresentationFilePermissionAllowed($EVC->getEntityPath("admin/manage_modules"), "access");
	$is_manage_projects_allowed = $UserAuthenticationHandler->isPresentationFilePermissionAllowed($EVC->getEntityPath("presentation/manage_projects"), "access");
	$is_manage_users_allowed = $UserAuthenticationHandler->isPresentationFilePermissionAllowed($EVC->getEntityPath("user/manage_users"), "access");
	$is_manage_layers_allowed = $UserAuthenticationHandler->isPresentationFilePermissionAllowed($EVC->getEntityPath("setup/layers"), "access");
	$is_deployment_allowed = $UserAuthenticationHandler->isPresentationFilePermissionAllowed($EVC->getEntityPath("deployment/index"), "access");
	$is_testunits_allowed = $UserAuthenticationHandler->isPresentationFilePermissionAllowed($EVC->getEntityPath("testunit/index"), "access");
	$is_program_installation_allowed = $UserAuthenticationHandler->isPresentationFilePermissionAllowed($EVC->getEntityPath("admin/install_program"), "access");
	$is_diff_files_allowed = $UserAuthenticationHandler->isPresentationFilePermissionAllowed($EVC->getEntityPath("diff/index"), "access");
	$is_terminal_console_allowed = $UserAuthenticationHandler->isPresentationFilePermissionAllowed($EVC->getEntityPath("admin/terminal_console"), "access");
	
	$is_module_user_installed = file_exists($EVC->getModulesPath($EVC->getCommonProjectName()) . "user/");

	//prepare admin uis permissions
	include $EVC->getUtilPath("admin_uis_permissions");

	//prepare main node
	$nodes = array(
		basename(CMS_PATH) => array(
			"properties" => array(
				"path" => ".",
				"item_id" => "root",
				"item_type" => "folder",
				"item_menu" => getFileItemMenu(CMS_PATH),
			)
		)
	);
}

function getAction($entity_path, $UserAuthenticationHandler, $action, $path, $extra = null) {
	$status = false;
	$path = $path == "." ? "" : $path;
	$abs_path = CMS_PATH . $path;
	
	$UserAuthenticationHandler->checkInnerFilePermissionAuthentication($abs_path, "layer", "access");
	
	switch($action) {
		case "get_sub_files":
			$dir = preg_replace("/\/+$/", "/", $abs_path . "/");
			$files = array_diff(scandir($dir), array('..', '.'));
			
			//echo "<pre>$CMS_PATH$path";print_r($files);die();
			
			$nodes = array(
				"properties" => array(
					"bean_name" => "expert"
				)
			);
			
			if ($files)
				foreach ($files as $file) {
					$file_path = $dir . $file;
					$node_path = substr($file_path, strlen(CMS_PATH));
					$node = array(
						"properties" => array(
							"path" => $node_path,
							"item_id" => strtolower(preg_replace('/[^\w]+/u', 'a', base64_encode(hash("crc32b", $node_path)))),
							"item_type" => is_dir($file_path) ? "folder" : (pathinfo($file, PATHINFO_EXTENSION) == "zip" ? "zip_file" : "file"),
							"item_menu" => getFileItemMenu($file_path),
						)
					);
					$nodes[$file] = $node;
				}
			
			return $nodes;
		case "create_folder":
			$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "write");
			
			if ($extra) {
				$UserAuthenticationHandler->checkInnerFilePermissionAuthentication("$abs_path/$extra", "layer", "access");
				
				if (!file_exists($abs_path))
					mkdir($abs_path, 0755, true);
				
				if (file_exists($abs_path)) {
					$dest = "$abs_path/$extra";
					$status = file_exists($dest) || mkdir($dest, 0755, true);
				}
			}
			break;
		case "create_file":
			$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "write");
			
			if ($extra) {
				$UserAuthenticationHandler->checkInnerFilePermissionAuthentication("$abs_path/$extra", "layer", "access");
				
				if (!file_exists($abs_path))
					mkdir($abs_path, 0755, true);
				
				if (file_exists($abs_path))
					$status = file_exists("$abs_path/$extra") || file_put_contents("$abs_path/$extra", "") !== false;		
			}
			break;
		case "rename":
			$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "write");
			
			if ($extra) {
				$UserAuthenticationHandler->checkInnerFilePermissionAuthentication(dirname($abs_path) . "/$extra", "layer", "access");
				
				if (basename($path) == $extra) 
					$status = true;
				else { 
					$dst = dirname($abs_path) . "/$extra";
					$dst_folder = dirname($dst);
					
					if (!is_dir($dst_folder))
						mkdir($dst_folder, 0755, true);
					
					$status = !file_exists($dst) && is_dir($dst_folder) ? rename($abs_path, $dst) : $dst == $abs_path;
				}
			}
			break;
		case "remove":
			$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "delete");
			
			if (is_dir($abs_path))
				$status = CMSModuleUtil::deleteFolder($abs_path);
			else
				$status = !file_exists($abs_path) || unlink($abs_path);
			
			break;
		case "upload":
			$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "write");
			
			if (is_dir($abs_path) && !empty($_FILES["file"]) && isset($_FILES['file']['name'])) {
				$file_name = basename($_FILES['file']['name']);
				
				$UserAuthenticationHandler->checkInnerFilePermissionAuthentication("$abs_path/$file_name", "layer", "access");
				
				$status = move_uploaded_file( $_FILES['file']['tmp_name'], $abs_path . "/" . $file_name);
			}
			break;
		case "download":
			if (is_dir($abs_path)) {
				$tmp_file = tmpfile();
				$tmp_file_path = stream_get_meta_data($tmp_file);
				$tmp_file_path = isset($tmp_file_path['uri']) ? $tmp_file_path['uri'] : null; //eg: /tmp/phpFx0513a
				
				if (ZipHandler::zip($abs_path, $tmp_file_path)) {
					header('Content-Type: application/zip');
					header('Content-Length: ' . filesize($tmp_file_path));
					header('Content-Disposition: attachment; filename="' . basename($abs_path) . '.zip"');
					
					readfile($tmp_file_path);
				}
				
				unlink($tmp_file_path); 
			}
			else {
				$mime_type = MimeTypeHandler::getFileMimeType($abs_path);
				$mime_type = $mime_type ? $mime_type : "application/octet-stream";
				
				header('Content-Type: ' . $mime_type);
				header('Content-Length: ' . filesize($abs_path));
				header('Content-Disposition: attachment; filename="' . basename($abs_path) . '"');
				
				readfile($abs_path);
			}
			
			die();
			break;
		case "zip":
			$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "write");
			
			$dest = $extra ? CMS_PATH . $extra : dirname($abs_path);
			
			if ($dest) {
				$file_name = basename($path);
				
				$UserAuthenticationHandler->checkInnerFilePermissionAuthentication("$dest/$file_name.zip", "layer", "access");
				
				if (!is_dir($dest))
					mkdir($dest, 0755, true);
				
				include_once get_lib("org.phpframework.compression.ZipHandler");
				
				$dest = "$dest/$file_name.zip";
				$status = ZipHandler::zip($abs_path, $dest);
			}
			
			break;
		case "unzip":
			$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "write");
			
			$dest = $extra ? CMS_PATH . $extra : dirname($abs_path);
			
			if ($dest && strtolower(pathinfo($path, PATHINFO_EXTENSION)) == "zip") {
				$UserAuthenticationHandler->checkInnerFilePermissionAuthentication($dest, "layer", "access");
				
				if (!is_dir($dest))
					mkdir($dest, 0755, true);
				
				include_once get_lib("org.phpframework.compression.ZipHandler");
				
				$status = ZipHandler::unzip($abs_path, $dest);
			}
			break;
		case "paste":
		case "paste_and_remove":
			$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "write");
			
			if ($action == "paste_and_remove")
				$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "delete");
			
			$extra = explode(",", str_replace(array("[", "]"), "", $extra));
			
			if ($extra) {
				$fp = isset($extra[0]) ? $extra[0] : null;//file_path
				$fp = str_replace("../", "", $fp);//for security reasons
				$src = CMS_PATH . $fp;
				$dst = $abs_path . "/" . basename($src);
				
				//prepare dst folder
				$dst_folder = dirname($dst) . "/";
				
				$UserAuthenticationHandler->checkInnerFilePermissionAuthentication($dst_folder, "layer", "access");
				
				if (!is_dir($dst_folder))
					mkdir($dst_folder, 0755, true);
				
				if (is_dir($dst_folder)) {
					if ($action == "paste_and_remove")
						$status = rename($src, $dst);
					else
						$status = CMSModuleUtil::copyFile($src, $dst);
				}
			}
			break;
	}
	
	return $status;
}

function getFileItemMenu($file_path) {
	$menu = array(
		"file_name" => pathinfo($file_path, PATHINFO_BASENAME),
		"modified_date" => date("Y-m-d H:i:s", filemtime($file_path))
	);
	
	$file_owner = FilePermissionHandler::getFileUserOwnerInfo($file_path);
	
	if ($file_owner)
		$menu["user_owner"] = isset($file_owner["name"]) ? $file_owner["name"] : null;
	
	$file_group = FilePermissionHandler::getFileUserGroupInfo($file_path);
	
	if ($file_group)
		$menu["user_group"] = isset($file_group["name"]) ? $file_group["name"] : null;
	
	$menu["permissions"] = FilePermissionHandler::getFilePermissionsInfo($file_path);
	$menu["writable_by_server"] = is_writable($file_path);
	
	return $menu;
}
?>
