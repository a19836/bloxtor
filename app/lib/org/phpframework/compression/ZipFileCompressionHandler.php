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

include_once get_lib("org.phpframework.compression.IFileCompressionHandler");

class ZipFileCompressionHandler implements IFileCompressionHandler {
	protected $file_pointer = null;
	protected $file_name = null;
	protected $tmp_file = null;
	
	public function __construct() {
		
	}

	public function open($file_path) {
		$this->file_pointer = new ZipArchive();
		$this->file_name = basename($file_path);
		$this->tmp_file = tmpfile();
		
		if ($this->file_pointer === false || !$this->file_pointer->open($file_path, ZIPARCHIVE::CREATE) || !$this->file_pointer->addFromString($this->file_name, "") || !$this->tmp_file)
			throw new Exception("Could not open file! Please check if the '" . $this->file_name . "' file is writeable...");
		
		return true;
	}

	public function write($str) {
		$bytes = fwrite($this->tmp_file, $str);
		
		if ($bytes === false)
			throw new Exception("Could not write to file! Please check if you have enough free space...");
		
		return $bytes;
	}

	public function close() {
		fseek($this->tmp_file, 0);
		
		$contents = "";
		while (!feof($this->tmp_file))
    			$contents .= fread($this->tmp_file, 8192);
		
		fclose($this->tmp_file);
		
		//Note that the addFromString replaces all contents in file, if it already exists
		$status = $this->file_pointer->addFromString($this->file_name, $contents);
		
		if ($status === false)
			throw new Exception("Could not write to file! Please check if you have enough free space...");
		
		return $this->file_pointer->close();
	}
}
?>
