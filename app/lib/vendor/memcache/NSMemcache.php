<?php
/**
 * Memcached interface class with namespaces support
 *
 * @author Savu Andrei
 */
class NSMemcache extends Memcache {

	/**
	 * Build a key available only in the current namespace
	 *
	 * Each namespace has a memcache var attached with a
	 * counter representing the current namespace instance. 
	 * You can flush a namespace by incrementing this counter.
	 */
	private function build_key($ns, $key) {
		$ns_key = '__ns_'.$ns;
		$id = 1;
		if(!$this->add($ns_key, $id, false, 0)) {
			$id = $this->get($ns_key);
		}
		return '__'.$ns.'_'.$id.'_'.$key;
	}

	/**
	 * Flush a specific namespace
	 *
	 * @param  $ns	namespace 
	 * @return boolean
	 */
	public function ns_flush($ns) {
		$ns_key = '__ns_'.$ns;
		if(!$this->increment($ns_key)) {
			$this->set($ns_key, 1, false, 0);
		}
	}

	public function ns_add($ns, $key, $var, $flag=false, $expire=0) {
		$nkey = $this->build_key($ns, $key);
		return $this->add($nkey, $var, $flag, $expire);
	}
	
	/**
	 * Store an item var with key on the memcached server
	 *
	 * @return bool
	 */
	public function ns_set($ns, $key, $var, $flag=false, $expire=0) {
		$nkey = $this->build_key($ns, $key);
		return $this->set($nkey, $var, $flag, $expire);
	}

	/**
	 * Get the value of an item
	 */
	public function ns_get($ns, $key) {
		$nkey = $this->build_key($ns, $key);
		return $this->get($nkey);
	}

	/**
	 * Replace value of the existing item
	 *
	 * @return bool
	 */
	public function ns_replace($ns, $key, $var, $flag=false, $expire=0) {
		$nkey = $this->build_key($ns, $key);
		return $this->replace($nkey, $var, $flag, $expire);
	}

	/**
	 * Increment the value of item
	 */
	public function ns_increment($ns, $key, $amount) {
		$nkey = $this->build_key($ns, $key);
		return $this->increment($nkey, $amount);
	}

	/**
	 * Decrement the value of an item
	 */
	public function ns_decrement($ns, $key, $amount) {
		$nkey = $this->build_key($ns, $key);
		return $this->decrement($nkey, $amount);
	}

	/**
	 * Remove a key from this namespace
	 */
	public function ns_delete($ns, $key, $timeout=0) {
		$nkey = $this->build_key($ns, $key);
		return $this->delete($nkey, $timeout);
	}
}

?>
