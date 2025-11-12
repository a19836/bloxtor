<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

interface IUserCacheHandler {
	public function read($file_name);
	public function write($file_name, $data);
	public function isValid($file_name);
	public function exists($file_name);
	public function delete($file_name);
}
?>
