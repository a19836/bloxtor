<?php
interface IFileCompressionHandler {
	public function open($file_path);
	public function write($str);
	public function close();
}
?>
