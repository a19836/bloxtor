<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

include_once get_lib("org.phpframework.util.io.handler.awss3.MyS3Handler");
include_once get_lib("org.phpframework.util.io.handler.awss3.MyS3File");

class MyS3Folder extends MyS3Handler {
	public $invalid_files = array("..", ".svn");
	
	private $delimiter = "/";
	private $MyS3File;
	
	public function __construct($awsAccessKey, $awsSecretKey, $settings = array()) {
		parent::__construct($awsAccessKey, $awsSecretKey);
		
		if(isset($settings["invalid_files"]) && is_array($settings["invalid_files"]))
			$this->invalid_files = array_merge($this->invalid_files, $settings["invalid_files"]);
		
		$this->MyS3File = new MyS3File($awsAccessKey, $awsSecretKey);
	}
	
	/*
	 * getFiles: gets files from a directory
	 */
	public function getFiles($bucket, $prefix = null, $marker = null, $max_keys = null) {
		$folder_type = $this->getFolderType();
	
		$rest = new S3Request("GET", $bucket, "");
		if ($prefix !== null && $prefix !== "") {
			$prefix = $this->configurePrefix($prefix);
			$rest->setParameter("prefix", $prefix);
		}
		if ($marker !== null && $marker !== "") $rest->setParameter("marker", $marker);
		if ($max_keys !== null && $max_keys !== "") $rest->setParameter("max-keys", $max_keys);
		if ($this->delimiter !== null && $this->delimiter !== "") $rest->setParameter("delimiter", $this->delimiter);
		
		$response = $rest->getResponse();
		if ($response->error === false && $response->code !== 200)
			$response->error = array("code" => $response->code, "message" => "Unexpected HTTP status");
		if ($response->error !== false) {
			$error_code = isset($response->error["code"]) ? $response->error["code"] : null;
			$error_message = isset($response->error["message"]) ? $response->error["message"] : null;
			trigger_error(sprintf("S3::getBucket(): [%s] %s", $error_code, $error_message), E_USER_WARNING);
			return false;
		}
//print_r($response);
	
		$last_marker = null;
		$is_truncate = null;
		$files = array();
		
		if(isset($response->body)) {
			$prefix_len = strlen($prefix);
			
			if (isset($response->body->Contents)) {
				foreach ($response->body->Contents as $c) {
					$file_name = substr((string)$c->Key, $prefix_len);
					if($this->isValid(basename($file_name)) && $file_name != ".") {
						$type = $this->getType($file_name);
						$type_id = isset($type["id"]) ? $type["id"] : null;
						$folder_type_id = isset($folder_type["id"]) ? $folder_type["id"] : null;
						
						if($type_id == $folder_type_id) {
							$dir_name = dirname($file_name);
							$files[$dir_name] = array(
								"path" => $prefix . $dir_name,
								"name" => basename($dir_name),
								"type" => $type_id, 
								"type_desc" => isset($type["desc"]) ? $type["desc"] : null
							);
						}
						else {
							$files[$file_name] = array(
								"path" => $prefix . $file_name,
								"name" => basename($file_name),
								"time" => strtotime((string)$c->LastModified),
								"size" => (int)$c->Size,
								"hash" => substr((string)$c->ETag, 1, -1),
								"type" => $type_id, 
								"type_desc" => isset($type["desc"]) ? $type["desc"] : null
							);
						}
					}
					$last_marker = (string)$c->Key;
				}
			}
			
			if (isset($response->body->CommonPrefixes)) {
				foreach ($response->body->CommonPrefixes as $c) {
					$dir_name = substr((string)$c->Prefix, $prefix_len);
					if($this->isValid(basename($dir_name))) {					
						$dir_name = $this->delimiter && substr($dir_name, strlen($dir_name) - 1) == $this->delimiter ? substr($dir_name, 0, strlen($dir_name) - strlen($this->delimiter)) : $dir_name;
						
						$files[$dir_name] = array(
							"name" => basename($dir_name),
							"path" => $prefix . $dir_name,
							"type" => isset($folder_type["id"]) ? $folder_type["id"] : null, 
							"type_desc" => isset($folder_type["desc"]) ? $folder_type["desc"] : null
						);
					}
					$last_marker = (string)$c->Prefix;
				}
			}
			
			$is_truncate = (string)$response->body->IsTruncated == "true" ? true : false;
		}
		
		// Loop through truncated results if maxKeys isn't specified
		if ($max_keys == null && $last_marker !== null && $is_truncate)
		do {
			$sub_result = $this->getFiles($bucket, $prefix, $last_marker, $max_keys);
			if(is_array($sub_result)) {
				$files = isset($sub_result["files"]) ? array_merge($files, $sub_result["files"]) : $files;
				$is_truncate = isset($sub_result["is_truncate"]) ? $sub_result["is_truncate"] : null;
				$last_marker = isset($sub_result["last_marker"]) ? $sub_result["last_marker"] : null;
			}
		} while ($sub_result !== false && $is_truncate);
			
		$result["files"] = $files;
		$result["is_truncate"] = $is_truncate;
		$result["last_marker"] = $is_truncate ? $last_marker : "";
		
		return $result;
	}
	
	/*
	 * getFilesRecursevly: gets files recursevly from a directory and all his sub-directories
	 */
	public function getFilesRecursevly($bucket, $prefix = null) {
		$prefix = $this->configurePrefix($prefix);
		
		$delimiter_aux = $this->delimiter;
		$this->delimiter = "";
		$result = $this->getFiles($bucket, $prefix);
		$this->delimiter = $delimiter_aux;
		
		$new_result = array();
		$new_result["files"] = isset($result["files"]) ? $this->prepareFilesArray($result["files"], ".") : array();
		$new_result["is_truncate"] = false;
		$new_result["last_marker"] = "";
		
		return $new_result;
	}
	
	/*
	 * getFilesCount: gets the files number from a directory
	 */
	public function getFilesCount($bucket, $prefix = null) {
		$result = $this->getFiles($bucket, $prefix);
		$keys = isset($result["files"]) ? array_keys($result["files"]) : array();
		return count($keys);
	}
	
	/*
	 * delete: deletes a folder and all the sub-files
	 */
	public function delete($bucket, $prefix = null) {
		$prefix = $this->configurePrefix($prefix);
		
		$delimiter_aux = $this->delimiter;
		$this->delimiter = "";
		$files = $this->getFiles($bucket, $prefix);
		$this->delimiter = $delimiter_aux;
	
		$folder_type = $this->getFolderType();
		$folder_type_id = isset($folder_type["id"]) ? $folder_type["id"] : null;
		
		$status = true;
		foreach($files["files"] as $value) {
			$value_type = isset($value["type"]) ? $value["type"] : null;
			$value_path = isset($value["path"]) ? $value["path"] : null;
			
			$path = $value_type == $folder_type_id ? $value_path . "/." : $value_path;
			
			if(!$this->MyS3File->delete($bucket, $path))
				$status = false;
		}
		
		if($status)
			$status = $this->MyS3File->delete($bucket, $prefix . ".");
		
		return $status;
	}
	
	/*
	 * copy: copy folder
	 */
	public function copy($src_bucket, $src_uri, $dest_bucket, $dest_uri, $perm = "p") {
		if($src_bucket == $dest_bucket && $src_uri == $dest_uri)
			return false;
		
		$status = true;
		
		$files = $this->getFilesRecursevly($src_bucket, $src_uri);
		$files = isset($files["files"]) ? $files["files"] : null;
		$keys = array_keys($files);
		$t = count($keys);
		for($i = 0; $i < $t; $i++) {
			$file = $files[ $keys[$i] ];
			$file_path = isset($file["path"]) ? $file["path"] : null;
			
			$sub_file_name = substr($file_path, strlen($src_uri));
			$sub_dest_key = $dest_uri . $sub_file_name;
	
			if(!$this->MyS3File->copy($src_bucket, $file_path, $dest_bucket, $sub_dest_key, $perm))
				$status = false;
		}
		return $status;
	}
	
	/*
	 * prepareFilesArray: prepares files array
	 */
	private function prepareFilesArray($files, $parent_dir_name, $index = 0) {
		$new_files = array();
		
		if(is_array($files)) {
			$folder_type = $this->getFolderType();
			$folder_type_id = isset($folder_type["id"]) ? $folder_type["id"] : null;
			
			$keys = array_keys($files);
			$t = count($keys);
			for($i = $index; $i < $t; $i++) {
				$file_name = $keys[$i];
				$dir_name = dirname($file_name);
				
				if($dir_name == $parent_dir_name) {
					$value = $files[$keys[$i]];
					$value_type = isset($value["type"]) ? $value["type"] : null;
					
					if($value_type == $folder_type_id) {
						$value["subfiles"] = $this->prepareFilesArray($files, $file_name, $i + 1);
					}
					
					$file_base_name = basename($file_name);
					$new_files[$file_base_name] = $value;
				}
			}
		}
		return $new_files;
	}
	
	/*
	 * configurePrefix: configures prefix
	 */
	private function configurePrefix($prefix) {
		if ($prefix !== null && $prefix !== "") {
			$prefix .= substr($prefix, strlen($prefix) - strlen($this->delimiter)) != $this->delimiter ? $this->delimiter : "";
		}
		return $prefix;
	}
	
	/*
	 * isValid: checks the file_name is valid
	 */
	private function isValid($file_name) {
		$file_name = basename($file_name);
		
		return array_search($file_name, $this->invalid_files) === false ? true : false;
	}
}
?>
