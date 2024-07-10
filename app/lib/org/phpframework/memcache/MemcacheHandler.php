<?php
include_once get_lib("org.phpframework.memcache.exception.MemcacheException");
include_once get_lib("org.phpframework.memcache.IMemcacheHandler");
include_once get_lib("lib.vendor.memcache.NSMemcache");

class MemcacheHandler implements IMemcacheHandler {
	private $conn;
	private $ok;
	
	public function connect($host = "", $port = "", $timeout = null) {
		try{
			$this->ok = false;
			
			$this->conn = new NSMemcache();
			
			if ($this->conn) {
				if (empty($timeout)) 
					$timeout = 1;
			
				$this->ok = $this->conn->connect($host, $port, $timeout);
			}
			
			if (empty($this->ok))
				launch_exception(new MemcacheException(1, null, array($host, $port, $timeout)));
		}
		catch(Exception $e) {
			launch_exception(new MemcacheException(1, $e, array($host, $port, $timeout)));
		}
	}
	
	public function close() {
		if ($this->ok)
			$this->conn->close();
	} 
	
	public function ok() { 
		return $this->ok;
	}
	
	public function getConn() {
		return $this->ok ? $this->conn : null;
	}
	
	public function get($key) {
		if ($this->ok && !empty($key))
			return $this->conn->get($key);
		
		return false;
	}
	
	public function nsGet($ns, $key) {
		if ($this->ok && !empty($ns) && !empty($key))
			return $this->conn->ns_get($ns, $key);
		
		return false;
	}
	
	public function set($key, $cont, $expire = 0) {
		if ($this->ok && !empty($key))
			return $this->conn->set($key, $cont, MEMCACHE_COMPRESSED, $expire);
		
		return false;
	}
	
	public function nsSet($ns, $key, $cont, $expire = 0) {
		if ($this->ok && !empty($ns) && !empty($key))
			return $this->conn->ns_set($ns, $key, $cont, MEMCACHE_COMPRESSED, $expire);
		
		return false;
	}
	
	public function nsFlush($ns) {
		if ($this->ok && !empty($ns))
			return $this->conn->ns_flush($ns);
		
		return false;
	}
	
	public function delete($key) {
		if ($this->ok && !empty($key))
			return $this->conn->delete($key);
		
		return false;
	}
	
	public function nsDelete($ns, $key, $expire = 0) {
		if ($this->ok && !empty($ns) && !empty($key))
			return $this->conn->ns_delete($ns, $key, $expire);
		
		return false;
	}
}
?>
