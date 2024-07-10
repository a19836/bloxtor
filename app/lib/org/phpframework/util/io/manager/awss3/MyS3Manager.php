<?php
include_once get_lib("org.phpframework.util.io.handler.awss3.MyS3Bucket");
include_once get_lib("org.phpframework.util.io.handler.awss3.MyS3Folder");
include_once get_lib("org.phpframework.util.io.handler.awss3.MyS3File");

include_once get_lib("org.phpframework.util.io.manager.MyIOManager");

class MyS3Manager extends MyIOManager {
	private $MyS3Bucket;
	private $MyS3Folder;
	private $MyS3File;
	
	private $bucket;
	private $free = false;
	
	public function __construct($AWSACCESSKEY, $AWSSECRETKEY) {
		$this->MyS3Bucket = new MyS3Bucket($AWSACCESSKEY, $AWSSECRETKEY);
		$this->MyS3Folder = new MyS3Folder($AWSACCESSKEY, $AWSSECRETKEY);
		$this->MyS3File = new MyS3File($AWSACCESSKEY, $AWSSECRETKEY);
	}
	
	/*************** START: BUCKET FUNCTIONS *******************/
	/*
	 * addBucket: creates new bucket
	 */
	public function addBucket($bucket, $settings = array()) {
		$perm = !empty($settings["perm"]) ? $settings["perm"] : "p";
		
		$status = $this->MyS3Bucket->create($bucket, $perm);
		
		if($status) {
			$status = self::add(2, $bucket, "/", ".", $settings = array("content" => "."));
		}
		return $status;
	}
	
	/*
	 * deleteBucket: deletes bucket
	 */
	public function deleteBucket($bucket) {
		return $this->MyS3Bucket->delete($bucket);
	}
	
	/*
	 * getBuckets: gets available buckets
	 */
	public function getBuckets() {
		return $this->MyS3Bucket->getList(false);
	}
	/*************** END: BUCKET FUNCTIONS *******************/
	
	/*
	 * add: creates new file
	 */
	public function add($type, $dir_path, $name, $settings = array()) {
		$perm = !empty($settings["perm"]) ? $settings["perm"] : "p";
		$key = self::configurePath($this->getRootPath() . $dir_path, $this->free) . self::configureName($name);
		
		if($type == 1) {
			$key = self::configureFolderPath($key);
			$content = ".";
		}
		else {
			if(!$this->checkType($name))
				return false;
			
			$content = isset($settings["content"]) && strlen($settings["content"]) > 0 ? $settings["content"] : " ";
		}
		return $this->MyS3File->upload($content, $this->bucket, $key, $perm, array("type" => "string"));
	}
	
	/*
	 * edit: edits file content
	 */
	public function edit($dir_path, $name, $settings = array()) {
		$perm = !empty($settings["perm"]) ? $settings["perm"] : "p";
		$key = self::configurePath($this->getRootPath() . $dir_path, $this->free) . self::configureName($name);
		
		$content = isset($settings["content"]) && strlen($settings["content"]) > 0 ? $settings["content"] : " ";
		return $this->MyS3File->upload($content, $this->bucket, $key, $perm, array("type" => "string"));
	}

	/*
	 * delete: deletes file
	 */
	public function delete($type, $dir_path, $name) {
		$key = self::configurePath($this->getRootPath() . $dir_path, $this->free) . self::configureName($name);
		
		if($type == 1) {
			return $this->MyS3Folder->delete($this->bucket, $key);
		}
		else {
			return $this->MyS3File->delete($this->bucket, $key);
		}
	}
	
	/*
	 * copy: copy file
	 */
	public function copy($type, $src_dir_path, $src_name, $dest_dir_path, $settings = array()) {
		$perm = !empty($settings["perm"]) ? $settings["perm"] : "p";
		$dest_bucket = !empty($settings["dest_bucket"]) ? $settings["dest_bucket"] : $this->bucket;
		$dest_name = !empty($settings["dest_name"]) ? $settings["dest_name"] : $src_name;
		
		$src_key = self::configurePath($this->getRootPath() . $src_dir_path, $this->free) . self::configureName($src_name);
		$dest_key = self::configurePath($this->getRootPath() . $dest_dir_path, $this->free) . self::configureName($dest_name);
		
		if($type == 1) {
			$src_key_folder = self::configureFolderPath($src_key);
			$dest_key_folder = self::configureFolderPath($dest_key);
			if($this->MyS3File->copy($this->bucket, $src_key_folder, $dest_bucket, $dest_key_folder, $perm)) {
				$src_key .= "/";
				$dest_key .= "/";
				
				return $this->MyS3Folder->copy($this->bucket, $src_key, $dest_bucket, $dest_key, $perm);
			}
			return false;
		}
		else {
			return $this->MyS3File->copy($this->bucket, $src_key, $dest_bucket, $dest_key, $perm);
		}
	}
	
	/*
	 * move: moves file
	 */
	public function move($type, $src_dir_path, $src_name, $dest_dir_path, $settings = array()) {
		if($this->copy($type, $src_dir_path, $src_name, $dest_dir_path, $settings))
			return $this->delete($type, $src_dir_path, $src_name);
		return false;
	}
	
	/*
	 * rename: renames file
	 */
	public function rename($dir_path, $ori_name, $new_name, $settings = array()) {
		$type = !empty($settings["type"]) ? $settings["type"] : 1;
		
		if($type != 1 && !$this->checkType($new_name))
			return false;
		
		$settings["dest_name"] = self::configureName($new_name);
		return $this->move($type, $dir_path, $ori_name, $dir_path, $settings);
	}
	
	/*
	 * getFile: gets file content
	 */
	public function getFile($dir_path, $name, $save_to = false) {
		$key = self::configurePath($this->getRootPath() . $dir_path, $this->free) . self::configureName($name);
		
		if(!$this->checkType($name))
			return false;
		
		return $this->MyS3File->get($this->bucket, $key, $save_to);
	}
	
	/*
	 * getFileInfo: gets file info
	 */
	public function getFileInfo($dir_path, $name, $type = 2) {
		$key = self::configurePath($this->getRootPath() . $dir_path, $this->free) . self::configureName($name);
		
		if($type != 1 && !$this->checkType($name))
			return false;
		
		if($type == 1) {
			$key = self::configureFolderPath($key);
		}
		
		$info = $this->MyS3File->getInfo($this->bucket, $key);
		
		if (isset($info["path"]))
			$info["path"] = $type == 1 ? dirname($info["path"]) : $info["path"];
		
		return $info;
	}

	/*
	 * getFiles: gets files from a folder
	 */
	public function getFiles($dir_path) {
		$prefix = self::configurePath($this->getRootPath() . $dir_path, $this->free);
		
		$files = $this->MyS3Folder->getFiles($this->bucket, $prefix);
		$files["files"] = $this->prepareFiles($files["files"]);
		return $files;
	}

	/*
	 * getFilesCount: gets files number from a folder
	 */
	public function getFilesCount($dir_path) {
		$prefix = self::configurePath($this->getRootPath() . $dir_path, $this->free);
		
		return $this->MyS3Folder->getFilesCount($this->bucket, $prefix);
	}

	/*
	 * upload: uploads file
	 */
	public function upload($file_details, $dir_path, $new_name, $settings = array()) {
		$perm = !empty($settings["perm"]) ? $settings["perm"] : "p";
		
		$file_name = trim($new_name) ? $new_name : (isset($file_details["name"]) ? $file_details["name"] : null);
		$key = self::configurePath($this->getRootPath() . $dir_path, $this->free) . self::configureName($file_name);
		
		if(!$this->checkType($file_name))
			return false;
		
		return isset($file_details['tmp_name']) ? $this->MyS3File->upload($file_details['tmp_name'], $this->bucket, $key, $perm) : false;
	}

	/*
	 * exists: checks if file exists
	 */
	public function exists($dir_path, $name, $type = 2) {
		$key = self::configurePath($this->getRootPath() . $dir_path, $this->free) . self::configureName($name);
		
		if($type == 1) {
			$key = self::configureFolderPath($key);
		}
		
		return $this->MyS3File->exists($this->bucket, $key);
	}

	/*
	 * configureFolderPath: configures folder path
	 */
	public static function configureFolderPath($path) {
		$path = self::removeDuplicates($path);
		if(substr($path, strlen($path) - 2) != "/.")
			$path = self::configurePath($path) . ".";
		
		return $path;
	}
	
	/*
	 * setBucket: sets bucket
	 */
	public function setBucket($bucket) {
		$this->bucket = $bucket;
	}
	
	/*
	 * getBucket: gets bucket
	 */
	public function getBucket() {
		return $this->bucket;
	}
	
	/*
	 * setFree: sets free
	 */
	public function setFree($free) {
		$this->free = $free;
	}
	
	/*
	 * getMyIOHandler: gets MyIOHandler
	 */
	public function getMyIOHandler() {
		return $this->MyS3File;
	}
}
?>
