<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

include_once get_lib("org.phpframework.util.io.handler.file.MyFolder");
include_once get_lib("org.phpframework.util.io.handler.file.MyFile");

include_once get_lib("org.phpframework.util.io.manager.MyIOManager");

class MyFileManager extends MyIOManager {
	private $MyFolder;
	private $MyFile;
	
	public function __construct() {
		$this->MyFolder = new MyFolder();
		$this->MyFile = new MyFile();
		
		$this->MyFolder->EXEC_ALLOWED = false;
	}
	
	/*
	 * add: creates new file
	 */
	public function add($type, $dir_path, $name, $settings = array()) {
		$key = self::configurePath($this->getRootPath() . $dir_path) . self::configureName($name);
	
		if($type == 1) {
			$this->MyFolder->setFileName($key);
			$status = $this->MyFolder->create();
		}
		else {
			if(!$this->checkType($name))
				return false;
			
			$this->MyFile->setFileName($key);
			$status = $this->MyFile->create(isset($settings["content"]) ? $settings["content"] : null);
		}
		return $status;
	}
	
	/*
	 * edit: edits file content
	 */
	public function edit($dir_path, $name, $settings = array()) {
		$key = self::configurePath($this->getRootPath() . $dir_path) . self::configureName($name);
		
		$this->MyFile->setFileName($key);
		return $this->MyFile->create(isset($settings["content"]) ? $settings["content"] : null);
	}

	/*
	 * delete: deletes file
	 */
	public function delete($type, $dir_path, $name) {
		$key = self::configurePath($this->getRootPath() . $dir_path) . self::configureName($name);
		
		if($type == 1) {
			$this->MyFolder->setFileName($key);
			$status = $this->MyFolder->delete();
		}
		else {
			$this->MyFile->setFileName($key);
			$status = $this->MyFile->delete();
		}
		return $status;
	}
	
	/*
	 * copy: copy file
	 */
	public function copy($type, $src_dir_path, $src_name, $dest_dir_path, $settings = array()) {
		$dest_name = !empty($settings["dest_name"]) ? $settings["dest_name"] : $src_name;
		
		$src_key = self::configurePath($this->getRootPath() . $src_dir_path) . self::configureName($src_name);
		$dest_key = self::configurePath($this->getRootPath() . $dest_dir_path) . self::configureName($dest_name);
		
		if($type == 1) {
			$this->MyFolder->setFileName($src_key);
			$status = $this->MyFolder->copy($dest_key);
		}
		else {
			$this->MyFile->setFileName($src_key);
			$status = $this->MyFile->copy($dest_key);
		}
		return $status;
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
		$key = self::configurePath($this->getRootPath() . $dir_path) . self::configureName($ori_name);
		$new_name = self::configureName($new_name);
		
		$type = !empty($settings["type"]) ? $settings["type"] : 1;
		
		if($type != 1 && !$this->checkType($new_name))
			return false;
		
		$this->MyFile->setFileName($key);
		return $this->MyFile->rename($new_name);
	}
	
	/*
	 * getFile: gets file content
	 */
	public function getFile($dir_path, $name) {
		$key = self::configurePath($this->getRootPath() . $dir_path) . self::configureName($name);
		
		if(!$this->checkType($name))
			return false;
	
		$this->MyFile->setFileName($key);
		return $this->MyFile->get();
	}
	
	/*
	 * getFileInfo: gets file info
	 */
	public function getFileInfo($dir_path, $name) {
		$key = self::configurePath($this->getRootPath() . $dir_path) . self::configureName($name);
		
		if(!is_dir($key) && !$this->checkType($name))
			return false;
		
		$this->MyFile->setFileName($key);
		$info = $this->MyFile->getInfo();
		$info["path"] = isset($info["path"]) ? substr($info["path"], strlen($this->getRootPath())) : null;
		
		return $info;
	}

	/*
	 * getFiles: gets files from a folder
	 */
	public function getFiles($dir_path) {
		$prefix = self::configurePath($this->getRootPath() . $dir_path);
	
		$this->MyFolder->setFileName($prefix);
		$files = $this->MyFolder->getFiles();
		
		$result = array();
		$result["files"] = $this->prepareFiles($files);
		$result["is_truncate"] = isset($files["is_truncate"]) ? $files["is_truncate"] : null;
		$result["last_marker"] = $result["is_truncate"] && isset($files["last_marker"]) ? $files["last_marker"] : "";
		return $result;
	}

	/*
	 * getFilesCount: gets files number from a folder
	 */
	public function getFilesCount($dir_path) {
		$prefix = self::configurePath($this->getRootPath() . $dir_path);
		
		$this->MyFolder->setFileName($prefix);
		return $this->MyFolder->getFilesCount();
	}

	/*
	 * upload: uploads file
	 */
	public function upload($file_details, $dir_path, $new_name, $settings = array()) {
		$dir_path = self::configurePath($this->getRootPath() . $dir_path);
		$new_name = self::configureName($new_name);
		
		if(!$this->checkType($new_name))
			return false;
		
		return $this->MyFile->upload($file_details, $dir_path, $new_name);
	}

	/*
	 * exists: checks if file exists
	 */
	public function exists($dir_path, $name) {
		$key = self::configurePath($this->getRootPath() . $dir_path) . self::configureName($name);

		$this->MyFile->setFileName($key);
		return $this->MyFile->exists();
	}
	
	/*
	 * getMyIOHandler: gets MyIOHandler
	 */
	public function getMyIOHandler() {
		return $this->MyFile;
	}
	
}
?>
