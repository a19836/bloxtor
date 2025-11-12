<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

interface IMemcacheHandler {
	public function connect($host = "", $port = "", $timeout = null);
	public function close();
	public function ok();
	public function getConn();
	public function get($key);
	public function set($key, $cont, $expire = 0);
}
?>
