<?php
include_once get_lib("org.phpframework.compression.IFileCompressionHandler");

class GzipstreamFileCompressionHandler implements IFileCompressionHandler {
	protected $file_pointer = null;
	protected $deflate_context;
	
	public function __construct() {
		if (!function_exists("deflate_init"))
			throw new Exception("Gzipstream lib is not installed or deflate_init function does NOT exists!");
	}
	
	public function open($file_path) {
		$this->file_pointer = fopen($file_path, "wb");
		
		if ($this->file_pointer === false)
			throw new Exception("Could not open file! Please check if the '" . basename($file_path) . "' file is writeable...");
	
		$this->deflate_context = deflate_init(ZLIB_ENCODING_GZIP, array('level' => 9));
		return true;
	}

	public function write($str) {
		$bytes = fwrite($this->file_pointer, deflate_add($this->deflate_context, $str, ZLIB_NO_FLUSH));
		
		if ($bytes === false)
			throw new Exception("Could not write to file! Please check if you have enough free space...");
		
		return $bytes;
	}

	public function close() {
		fwrite($this->file_pointer, deflate_add($this->deflate_context, '', ZLIB_FINISH));
		
		return fclose($this->file_pointer);
	}
}
?>
