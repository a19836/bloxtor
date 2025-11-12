<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

include_once get_lib("org.phpframework.util.io.handler.IMyIOHandler");

abstract class MyIOHandler implements IMyIOHandler {
	
	/*
	 * getFileExtension: gets file extension
	 */
	public static function getFileExtension($file_name) {
		if($file_name) {
			return pathinfo($file_name, PATHINFO_EXTENSION);
			
			/*$pos = strrpos(basename($file_name), ".");
			if(is_numeric($pos) && $pos > 0) 
				return substr(basename($file_name), $pos+1);*/
		}
		return false;
	}
	
	/*
	 * getFileTypes: gets file available types
	 */
	public static function getFileTypes() {
		$types = array();
		$types["folder"] = array("id" => 1, "desc" => "folder");
		$types["file"] = array("id" => 2, "desc" => "file");
		$types["video"] = array("id" => 3, "desc" => "video");
		$types["image"] = array("id" => 4, "desc" => "image");
		$types["text"] = array("id" => 5, "desc" => "text");
		
		return $types;
	}
	
	/*
	 * getFileType: gets file type
	 */
	public static function getFileType($file_name) {
		$extension = self::getFileExtension($file_name);
		$types = self::getFileTypes();
		
		$type = false;
		$keys = array_keys($types);
		for($i = count($keys) - 1; $i >= 0; --$i) {
			$key = $keys[$i];
			$type_id = isset($types[$key]["id"]) ? $types[$key]["id"] : null;
			
			if(self::getFileMimeTypeByExtension($extension, $type_id)) {
				$type = $key;
				break;
			}
		}
		
		return $type ? $type : "file";
	}
	
	/*
	 * getFolderType: gets the folder type
	 */
	public static function getFolderType() {
		$types = self::getFileTypes();
		return isset($types["folder"]) ? $types["folder"] : null;
	}
	
	/*
	 * getInvalidFiles: gets the invalid file names
	 */
	public static function getInvalidFiles() {
		return array(".", "..", ".svn");
	}
	
	/**
	* getAvailableVideoTypes: gets the available video file types
	*/
	public static function getAvailableVideoTypes() {
		return array(
			"flv" => "video/x-flv",
			"flv" => "application/x-flash-video",
			"m4v" => "video/x-m4v",
			"m4a" => "video/x-m4a",
			"mp4" => "video/mp4",
			"mov" => "video/quicktime",
			"qtl" => "video/quicktime",
			"swf" => "application/x-shockwave-flash"
		);
	}
	
	/**
	* getAvailableImageTypes: gets the available image file types
	*/
	public static function getAvailableImageTypes() {
		return array(
			"gif" => "image/gif",
			"jpg" => "image/jpeg",
			"png" => "image/png",
			"jpeg" => "image/jpeg",
			"tif" => "image/tif",
			"tiff" => "image/tif",
			"bmp" => "image/bmp"
		);
	}
	
	/**
	* getAvailableTextTypes: gets the available text file types
	*/
	public static function getAvailableTextTypes() {
		return array(
			"txt" => "text/plain",
			"htm" => "text/html",
			"html" => "text/html",
			"php" => "text/plain",
			"asp" => "text/plain",
			"aspx" => "text/plain",
			"js" => "text/plain",
			"css" => "text/css",
			"xml" => "text/xml",
			"xslt" => "text/xml",
			"java" => "text/plain"
		);
	}
	
	/**
	* getAvailableFileTypes: gets the available mime types
	*/
	public static function getAvailableFileTypes($type = false) {
		if($type == 3)
			return self::getAvailableVideoTypes();
		else if($type == 4)
			return self::getAvailableImageTypes();
		else if($type == 5)
			return self::getAvailableTextTypes();
		else if($type == 2 || $type === false)//if type == generic file or ALL
			return array_merge(self::getAvailableVideoTypes(), self::getAvailableImageTypes(), self::getAvailableTextTypes());
		else
			return array();
	}
	
	/**
	* getAvailableFileExtensions: gets the available file extensions
	*/
	public static function getAvailableFileExtensions($type = false) {
		$types = self::getAvailableFileTypes($type);
		
		return $types ? array_keys($types) : array();
	}
	
	/**
	* getAvailableFileMimeTypes: gets the available file extensions
	*/
	public static function getAvailableFileMimeTypes($type = false) {
		$types = self::getAvailableFileTypes($type);
		
		return $types ? array_values($types) : array();
	}
	
	/**
	* getFileExtensionByMimeType: gets video extension by mime type
	*/
	public static function getFileExtensionByMimeType($mime_type, $type = false) {
		$mime_type = strtolower($mime_type);
		
		$mime_types = self::getAvailableFileTypes($type);
		$extensions = array_flip($mime_types);
		return isset($extensions[$mime_type]) ? $extensions[$mime_type] : false;
	}
	
	/**
	* getFileMimeTypeByExtension: gets video mime-type by extension
	*/
	public static function getFileMimeTypeByExtension($extension, $type = false) {
		$extension = strtolower($extension);
	
		$mime_types = self::getAvailableFileTypes($type);
		return isset($mime_types[$extension]) ? $mime_types[$extension] : false;
	}
}
?>
