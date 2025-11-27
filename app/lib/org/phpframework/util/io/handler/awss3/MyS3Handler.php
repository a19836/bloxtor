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

include_once get_lib("org.phpframework.util.io.handler.MyIOHandler");
include_once get_lib("lib.vendor.awss3.S3");

class MyS3Handler extends MyIOHandler {
	public $S3;
	
	public function __construct($awsAccessKey, $awsSecretKey) {
		$this->S3 = new S3($awsAccessKey, $awsSecretKey);
	}
	
	/*
	 * getType: gets file type
	 */
	public function getType($file_path) {
		$types = $this->getFileTypes();
		$file_name = basename($file_path);
		
		if ($file_name == ".")
			return isset($types["folder"]) ? $types["folder"] : null;
		
		$type = self::getFileType($file_name);
		return isset($types[$type]) ? $types[$type] : null;
	}
	
	/*
	 * getACL: gets ACL permission
	 */
	public function getACL($perm) {
		switch(strtolower($perm)) {
			case "p": $acl = S3::ACL_PRIVATE; break;
			case "r": $acl = S3::ACL_PUBLIC_READ; break;
			case "w": $acl = S3::ACL_PUBLIC_READ_WRITE; break;
			default: $acl = S3::ACL_PRIVATE;
		}
		return $acl;
	}
	
	/*
	 * exists: checks if the file exists
	 */
	public function exists($bucket, $uri) {
		return $this->S3->getObjectInfo($bucket, $uri, false);
	}
	
	/*
	 * getInfo: gets file info
	 */
	public function getInfo($bucket, $uri) {
		if($this->exists($bucket, $uri)) {
			$type = $this->getType($uri);
			
			$info = array();
			$info["type"] = isset($type["id"]) ? $type["id"] : null;
			$info["type_desc"] = isset($type["desc"]) ? $type["desc"] : null;
			
			if($info["type"] == 1) {
				$info["path"] = $uri;
				$info["name"] = basename(dirname($uri));
			}
			else {
				$info["path"] = $uri;
				$info["name"] = basename($uri);
				
				$info["extension"] = $this->getFileExtension($uri);
				$info["mime_type"] = $this->getFileMimeTypeByExtension($info["extension"]);
			}
			return $info;
		}
		else
			return array();
	}
}
?>
