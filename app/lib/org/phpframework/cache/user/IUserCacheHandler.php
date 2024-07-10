<?php
interface IUserCacheHandler {
	public function read($file_name);
	public function write($file_name, $data);
	public function isValid($file_name);
	public function exists($file_name);
	public function delete($file_name);
}
?>
