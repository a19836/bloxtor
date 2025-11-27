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

include_once get_lib("org.phpframework.util.io.exception.MyIOManagerException");

$FileManager = null;

if (!empty($FILEMANAGER_DATA)) {
	$FILE_MANAGER_TYPE = isset($FILEMANAGER_DATA["type"]) ? strtolower($FILEMANAGER_DATA["type"]) : null;
	$FILE_MANAGER_ARGS = isset($FILEMANAGER_DATA["args"]) ? $FILEMANAGER_DATA["args"] : null;
	
	if($FILE_MANAGER_TYPE == "awss3") {
		include_once get_lib("org.phpframework.util.io.manager.awss3.MyS3Manager");
		
		$awsaccesskey = isset($FILE_MANAGER_ARGS["awsaccesskey"]) ? $FILE_MANAGER_ARGS["awsaccesskey"] : null;
		$awssecretkey = isset($FILE_MANAGER_ARGS["awssecretkey"]) ? $FILE_MANAGER_ARGS["awssecretkey"] : null;
		$bucket = isset($FILE_MANAGER_ARGS["bucket"]) ? $FILE_MANAGER_ARGS["bucket"] : null;
		$root_path = isset($FILE_MANAGER_ARGS["root_path"]) ? $FILE_MANAGER_ARGS["root_path"] : null;
		
		$FileManager = new MyS3Manager($awsaccesskey, $awssecretkey);
		$FileManager->setBucket($bucket);
		$FileManager->setRootPath($root_path);
	}
	else if($FILE_MANAGER_TYPE == "ftp") {
		include_once get_lib("org.phpframework.util.io.manager.ftp.MyFTPManager");
	
		$ftp_host = isset($FILE_MANAGER_ARGS["ftp_host"]) ? $FILE_MANAGER_ARGS["ftp_host"] : null;
		$ftp_username = isset($FILE_MANAGER_ARGS["ftp_username"]) ? $FILE_MANAGER_ARGS["ftp_username"] : null;
		$ftp_password = isset($FILE_MANAGER_ARGS["ftp_password"]) ? $FILE_MANAGER_ARGS["ftp_password"] : null;
		$ftp_port = isset($FILE_MANAGER_ARGS["ftp_port"]) ? $FILE_MANAGER_ARGS["ftp_port"] : null;
		$ftp_passive_mode = isset($FILE_MANAGER_ARGS["ftp_passive_mode"]) ? $FILE_MANAGER_ARGS["ftp_passive_mode"] : null;
		$root_path = isset($FILE_MANAGER_ARGS["root_path"]) ? $FILE_MANAGER_ARGS["root_path"] : null;
		
		$FileManager = new MyFTPManager($ftp_host, $ftp_username, $ftp_password, $ftp_port, array("passive_mode" => $ftp_passive_mode));
		
		$FileManager->setRootPath($root_path, $root_path ? false : true);
	}
	elseif($FILE_MANAGER_TYPE == "file") {
		include_once get_lib("org.phpframework.util.io.manager.file.MyFileManager");
	
		$root_path = isset($FILE_MANAGER_ARGS["root_path"]) ? $FILE_MANAGER_ARGS["root_path"] : null;
		
		$FileManager = new MyFileManager();
		
		$FileManager->setRootPath($root_path, $root_path ? false : true);
	}
	elseif($FILE_MANAGER_TYPE == "youtube") {
		//TODO
	}
	else {
		launch_exception(new MyIOManagerException(2, $FILE_MANAGER_TYPE));
	}
	
	if(!empty($FILE_MANAGER_ARGS["file_type_allowed"]))
		$FileManager->setOption("file_type_allowed", $FILE_MANAGER_ARGS["file_type_allowed"]);
}
else {
	launch_exception(new MyIOManagerException(1, false));
}
?>
