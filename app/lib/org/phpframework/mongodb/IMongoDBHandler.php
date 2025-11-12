<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

interface IMongoDBHandler {
	public function connect($host = "",  $db_name = "", $username = "", $password = "", $port = "", $options = null);
	public function close();
	public function ok();
	public function getConn();
	public function get($collection_name, $key);
	public function set($collection_name, $key, $cont);
}
?>
