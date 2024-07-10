<?php
include_once get_lib("org.phpframework.compression.IFileCompressionHandler");

class NoneFileCompressionHandler implements IFileCompressionHandler {
	protected $file_pointer = null;

	public function __construct() {
		
	}

	public function open($file_path) {
		$this->file_pointer = fopen($file_path, "wb");
		
		if ($this->file_pointer === false)
			throw new Exception("Could not open file! Please check if the '" . basename($file_path) . "' file is writeable...");

		return true;
	}

	public function write($str) {
		$bytes = fwrite($this->file_pointer, $str);
		
		if ($bytes === false)
			throw new Exception("Could not write to file! Please check if you have enough free space...");
		
		return $bytes;
	}

	public function close() {
		return fclose($this->file_pointer);
	}
}
?>
