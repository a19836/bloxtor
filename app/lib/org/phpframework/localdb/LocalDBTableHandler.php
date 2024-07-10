<?php
include_once get_lib("org.phpframework.encryption.CryptoKeyHandler");

class LocalDBTableHandler {
	private $root_path;
	private $encryption_key;
	
	public function __construct($root_path, $encryption_key) {
		$this->root_path = trim($root_path);
		$this->encryption_key = $encryption_key;
		
		$this->root_path .= substr($this->root_path, -1) == "/" ? "" : "/";
	}
	
	public function changeDBTableEncryptionKey($table_name, $new_encryption_key) {
		$items = $this->getItems($table_name);
		
		$old_encryption_key = $this->encryption_key;
		$this->encryption_key = $new_encryption_key;
		
		if ($this->writeTableItems($items, $table_name)) {
			return true;
		}
		
		$this->encryption_key = $old_encryption_key;
		return false;
	}
	
	public function getPKMaxValue($table_name, $pk_name, &$items = null) {
		$max = null;
		
		if (empty($items)) {
			$items = $this->getItems($table_name);
		}
		
		if (is_array($items) && $pk_name) {
			
			foreach ($items as $idx => $item) {
				if (isset($item[$pk_name]) && $item[$pk_name] > $max) {
					$max = $item[$pk_name];
				}
			}
		}
		
		return $max;
	}
	
	public function insertItem($table_name, $data, $pks, &$items = null) {
		if (empty($items)) {
			$items = $this->getItems($table_name);
		}
		
		if (!is_array($items)) {
			$items = array();
		}
		else if (is_array($pks)) {
			$conditions = array();
			foreach ($pks as $pk) {
				if (isset($data[$pk])) {
					$conditions[$pk] = $data[$pk];
				}
			}
			
			$filtered = $this->filterItems($items, $conditions);
			if (!empty($filtered)) {
				return false;
			}
		}
		
		$items[] = $data;
		
		return $this->writeTableItems($items, $table_name);
	}
	
	public function updateItem($table_name, $data, $pks, &$items = null) {
		if (empty($items)) {
			$items = $this->getItems($table_name);
		}
		
		if (is_array($items) && is_array($pks)) {
			$conditions = array();
			foreach ($pks as $pk) {
				if (isset($data[$pk])) {
					$conditions[$pk] = $data[$pk];
				}
			}
			
			if ($conditions) {
				$searched_items = $this->filterItems($items, $conditions);
				
				$exists = false;
				foreach ($searched_items as $idx => $item) {
					if (!$exists) {
						$items[$idx] = $data;
						$exists = true;
					}
					else {
						unset($items[$idx]);
					}
				}
			
				$items = array_values($items);
				
				if (!$exists) {
					$items[] = $data;
				}
				
				return $this->writeTableItems($items, $table_name);
			}
		}
		
		return false;
	}
	
	public function deleteItem($table_name, $conditions, &$items = null) {
		if (empty($items)) {
			$items = $this->getItems($table_name);
		}
		
		if (is_array($items) && is_array($conditions) && !empty($conditions)) {
			$searched_items = $this->filterItems($items, $conditions);
			
			foreach ($searched_items as $idx => $item) {
				unset($items[$idx]);
			}
			
			$items = array_values($items);
			
			return $this->writeTableItems($items, $table_name);
		}
		
		return false;
	}
	
	public function getItems($table_name) {
		$items = $this->readTableItems($table_name);
		return is_array($items) ? $items : array();
	}
	
	public function filterItems($items, $conditions, $preserve_indexes = true, $limit = null) {
		$new_items = array();
		
		if (is_array($items)) {
			if (is_array($conditions)) {
				$count = 0;
				
				foreach ($items as $idx => $item) {
					if (is_array($item)) {
						$status = true;
						foreach ($conditions as $key => $value) {
							$item_value = isset($item[$key]) ? $item[$key] : null;
							
							if ($item_value != $value) {
								$status = false;
								break;
							}
						}
					
						if ($status) {
							$new_items[$idx] = $item;
							$count++;
							
							if ($limit > 0 && $count >= $limit)
								break;
						}
					}
				}
			}
			else {
				$new_items = $items;
				
				if ($limit > 0)
					$new_items = array_slice($new_items, 0, $limit, $preserve_indexes);
			}
			
			$new_items = $preserve_indexes ? $new_items : array_values($new_items);
		}
		
		return $new_items;
	}
	
	public function readTableItems($table_name) {
		$path = $this->getTableFilePath($table_name);
		
		if (file_exists($path)) {
			$contents = file_get_contents($path);
			return $contents ? \CryptoKeyHandler::decryptJsonObject($contents, $this->encryption_key) : null;
		}
		
		return null;
	}
	
	//$items should be an array
	public function writeTableItems($items, $table_name) {
		$path = $this->getTableFilePath($table_name);
		
		$parent_path = dirname($path);
		if ($parent_path && !is_dir($parent_path)) {
			mkdir($parent_path, 0755, true);
		}
		
		if (is_dir($parent_path)) {
			$cipher_text = $items ? \CryptoKeyHandler::encryptJsonObject($items, $this->encryption_key) : null;
			
			return file_put_contents($path, $cipher_text) !== false;
		}
		
		return null;
	}
	
	public function getTableFilePath($table_name) {
		return $this->root_path . "$table_name.tbl";
	}
}
?>
