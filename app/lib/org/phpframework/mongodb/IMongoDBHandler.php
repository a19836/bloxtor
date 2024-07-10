<?php
interface IMongoDBHandler {
	public function connect($host = "",  $db_name = "", $username = "", $password = "", $port = "", $options = null);
	public function close();
	public function ok();
	public function getConn();
	public function get($collection_name, $key);
	public function set($collection_name, $key, $cont);
}
?>
