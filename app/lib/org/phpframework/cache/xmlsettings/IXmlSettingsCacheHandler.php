<?php
interface IXmlSettingsCacheHandler {
	public function getCache($file_path);
	public function setCache($file_path, $data);
	public function isCacheValid($file_path);
	public function deleteCache($file_path);
}
?>
