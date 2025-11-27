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

include_once get_lib("org.phpframework.util.ShellCmdHandler");

$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "access");

$popup = isset($_GET["popup"]) ? $_GET["popup"] : null;
$is_allowed = ShellCmdHandler::isAllowed();

if ($is_allowed) {
	if (isset($_POST['cmd'])) {
		$command = urldecode($_POST["cmd"]);
		$command = preg_replace("/;+\s*$/", "", $command);
		$command .= " 2>&1";
		//echo "command:$command!\n".urldecode($command);die();
		
		$output = ShellCmdHandler::exec($command);
		$output = preg_split('/[\n]/', $output);
		
		foreach ($output as $line)
			echo htmlentities($line, ENT_QUOTES | ENT_HTML5, 'UTF-8') . "<br>";
		
		die();
	}
	else if (!empty($_FILES['file']['tmp_name']) && !empty($_POST['path'])) {
		$file_name = $_FILES["file"]["name"];
		$path = $_POST['path'];
		$path .= $path != "/" ? "/" : "";
		
		if (move_uploaded_file($_FILES["file"]["tmp_name"], $path . $file_name))
			echo htmlentities($file_name) . " successfully uploaded to " . htmlentities($path);
		else
			echo "Error uploading " . htmlentities($file_name);
		
		die();
	}
}
?>
