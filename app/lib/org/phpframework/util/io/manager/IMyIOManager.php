<?php
interface IMyIOManager {
	public function add($type, $dir_path, $name, $settings = array());
	public function edit($dir_path, $name, $settings = array());
	public function delete($type, $dir_path, $name);
	public function copy($type, $src_dir_path, $src_name, $dest_dir_path, $settings = array());
	public function move($type, $src_dir_path, $src_name, $dest_dir_path, $settings = array());
	public function rename($dir_path, $ori_name, $new_name, $settings = array());
	public function getFile($dir_path, $name);
	public function getFileInfo($dir_path, $name);
	public function getFileNameExtension($name);
	public function getFiles($dir_path);
	public function getFilesCount($dir_path);
	public function upload($file_details, $dir_path, $new_name, $settings = array());
	public function exists($dir_path, $name);
	
	public function setOptions($options);
	public function setOption($option, $value);
}
?>
