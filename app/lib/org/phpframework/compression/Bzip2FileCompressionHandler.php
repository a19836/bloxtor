<?php
include_once get_lib("org.phpframework.compression.IFileCompressionHandler");

class Bzip2FileCompressionHandler implements IFileCompressionHandler {
	protected $file_pointer = null;

	public function __construct() {
		if (!function_exists("bzopen"))
			throw new Exception("Bzip2 lib is not installed or bzopen function does NOT exists!");
	}

	public function open($file_path) {
		$this->file_pointer = bzopen($file_path, "w");

		if ($this->file_pointer === false)
			throw new Exception("Could not open file! Please check if the '" . basename($file_path) . "' file is writeable...");

		return true;
	}

	public function write($str) {
		$bytes = bzwrite($this->file_pointer, $str);

		if ($bytes === false)
			throw new Exception("Could not write to file! Please check if you have enough free space...");

		return $bytes;
	}

	public function close() {
		return bzclose($this->file_pointer);
	}
}
?>
