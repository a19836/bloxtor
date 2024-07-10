<?php
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
		
		return $file_name == "." ? $types["folder"] : $types[ self::getFileType($file_name) ];
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
			$info["type"] = $type["id"];
			$info["type_desc"] = $type["desc"];
			
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
