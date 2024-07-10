<?php
include_once get_lib("org.phpframework.util.io.handler.awss3.MyS3Handler");

class MyS3File extends MyS3Handler {
	public function __construct($awsAccessKey, $awsSecretKey) {
		parent::__construct($awsAccessKey, $awsSecretKey);
	}
	
	/*
	 * The $input variable corresponds to:
	 * - file path, 
	 * - file contents string 
	 * - resource buffer
	 */
	public function upload($input, $bucket, $uri, $perm = "p", $settings = array()) {
		$acl = $this->getACL($perm);
		$type = isset($settings["type"]) ? strtolower($settings["type"]) : "";
		
		$status = false;
		switch($type) {
			case "string": 
				if(!is_string($input)) {
					if (is_object($input) && in_array("__toString", get_class_methods($input)))
						$input = strval($input->__toString());
					else
						$input = strval($input);
				}
				
				$status = $this->S3->putObjectString($input, $bucket, $uri, $acl); 
				break;
				//Put an object from a string
			
			case "resource": 
				$status = $this->S3->putObject($this->S3->inputResource(fopen($input, 'rb'), filesize($input)), $bucket, $uri, $acl); 
				break;
				//Put an object from a resource (buffer/file size is required)
			
			default; 
				$status = $this->S3->putObject($this->S3->inputFile($input, false), $bucket, $uri, $acl);
				//Put an object from a file path
		}
		
		if($status && $type != "string" && file_exists($input)) 
			unlink($input);
		
		return $status;
	}
	
	/*
	 * create: creates new file
	 */
	public function create($input, $bucket, $uri, $perm = "p") {
		return $this->upload($input, $bucket, $uri, $perm, array("type" => "string"));
	}
	
	/*
	 * get: gets file content if exists.
	 */
	public function get($bucket, $uri, $save_to = false) {
		if($this->exists($bucket, $uri)) {
			$obj = $this->S3->getObject($bucket, $uri, $save_to);
			return $obj ? $obj->body : null;
		}
		return null;
	}
	
	/*
	 * delete: deletes file
	 */
	public function delete($bucket, $uri) {
		if($this->exists($bucket, $uri)) {
			return $this->S3->deleteObject($bucket, $uri);
		}
		return true;
	}
	
	/*
	 * copy: copy file
	 */
	public function copy($src_bucket, $src_uri, $dest_bucket, $dest_uri, $perm = "p") {
		if($src_bucket == $dest_bucket && $src_uri == $dest_uri)
			return false;
		
		if($this->exists($dest_bucket, $src_uri) && $dest_bucket && $dest_uri) {
			$acl = $this->getACL($perm);
			$status = $this->S3->copyObject($src_bucket, $src_uri, $dest_bucket, $dest_uri, $acl);
			return $status ? true : false;
		}
		return false;
	}
}
?>
