<?php
interface IMemcacheHandler {
	public function connect($host = "", $port = "", $timeout = null);
	public function close();
	public function ok();
	public function getConn();
	public function get($key);
	public function set($key, $cont, $expire = 0);
}
?>
