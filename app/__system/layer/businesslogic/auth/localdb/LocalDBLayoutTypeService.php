<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

namespace __system\businesslogic;

include_once $vars["current_business_logic_module_path"] . "LocalDBAuthService.php";

class LocalDBLayoutTypeService extends LocalDBAuthService {
	
	/**
	 * @param (name=data[root_path], type=varchar, not_null=1, min_length=1)
	 * @param (name=data[encryption_key], type=varchar, not_null=1, min_length=1)
	 * @param (name=data[new_encryption_key], type=varchar, not_null=1, min_length=1)
	 */
	public function changeTableEncryptionKey($data) {
		$this->initLocalDBTableHandler($data);
		
		return $this->LocalDBTableHandler->changeDBTableEncryptionKey("layout_type", $data["new_encryption_key"]);
	}
	
	/**
	 * @param (name=data[root_path], type=varchar, not_null=1, min_length=1)
	 * @param (name=data[encryption_key], type=varchar, not_null=1, min_length=1)
	 */
	public function dropAndCreateTable($data) {
		$this->initLocalDBTableHandler($data);
		
		return $this->LocalDBTableHandler->writeTableItems("", "layout_type");
	}
	
	/**
	 * @param (name=data[root_path], type=varchar, not_null=1, min_length=1)
	 * @param (name=data[encryption_key], type=varchar, not_null=1, min_length=1)
	 * @param (name=data[type_id], type=tinyint, not_null=1, default=0, length=1)
	 * @param (name=data[name], type=varchar, not_null=1, min_length=1, max_length=255)
	 */
	public function insert($data) {
		$this->initLocalDBTableHandler($data);
		
		$data["created_date"] = !empty($data["created_date"]) ? $data["created_date"] : date("Y-m-d H:i:s");//leave this check because of the transport data between dbs
		$data["modified_date"] = !empty($data["modified_date"]) ? $data["modified_date"] : $data["created_date"];
		
		if (empty($data["layout_type_id"]))
			$data["layout_type_id"] = $this->LocalDBTableHandler->getPKMaxValue("layout_type", "layout_type_id") + 1;
		
		return $this->LocalDBTableHandler->insertItem("layout_type", $data, array("layout_type_id")) ? $data["layout_type_id"] : null;
	}
	
	/**
	 * @param (name=data[root_path], type=varchar, not_null=1, min_length=1)
	 * @param (name=data[encryption_key], type=varchar, not_null=1, min_length=1)
	 * @param (name=data[layout_type_id], type=bigint, not_null=1, length=19)
	 * @param (name=data[type_id], type=tinyint, not_null=1, default=0, length=1)
	 * @param (name=data[name], type=varchar, not_null=1, min_length=1, max_length=255)
	 */
	public function update($data) {
		$this->initLocalDBTableHandler($data);
		
		$data["modified_date"] = date("Y-m-d H:i:s");
		
		return $this->LocalDBTableHandler->updateItem("layout_type", $data, array("layout_type_id"));
	}
	
	/**
	 * @param (name=data[root_path], type=varchar, not_null=1, min_length=1)
	 * @param (name=data[encryption_key], type=varchar, not_null=1, min_length=1)
	 * @param (name=data[old_name_prefix], type=varchar, not_null=1, min_length=1, max_length=255)
	 * @param (name=data[new_name_prefix], type=varchar, not_null=1, min_length=1, max_length=255)
	 */
	public function updateByNamePrefix($data) {
		$this->initLocalDBTableHandler($data);
		
		$items = $this->getAll(array("root_path" => $data["root_path"], "encryption_key" => $data["encryption_key"]));
		
		if ($items) {
			$len = strlen($data["old_name_prefix"]);
			$t = count($items);
			$changed = false;
			
			for ($i = 0; $i < $t; $i++) {
				$item = $items[$i];
				$name = $item["name"];
				
				if (substr($name, 0, $len) == $data["old_name_prefix"]) {
					$item["name"] = $data["new_name_prefix"] . substr($name, $len);
					$item["modified_date"] = date("Y-m-d H:i:s");
					
					$items[$i] = $item;
					$changed = true;
				}
			}
			
			if ($changed) {
				//echo "<pre>";print_r($items);die();
				return $this->LocalDBTableHandler->writeTableItems($items, "layout_type");
			}
		}
		
		return true;
	}
	
	/**
	 * @param (name=data[root_path], type=varchar, not_null=1, min_length=1)
	 * @param (name=data[encryption_key], type=varchar, not_null=1, min_length=1)
	 * @param (name=data[layout_type_id], type=bigint, not_null=1, length=19)
	 */
	public function delete($data) {
		$this->initLocalDBTableHandler($data);
		
		return $this->LocalDBTableHandler->deleteItem("layout_type", array("layout_type_id" => $data["layout_type_id"]));
	}
	
	/**
	 * @param (name=data[root_path], type=varchar, not_null=1, min_length=1)
	 * @param (name=data[encryption_key], type=varchar, not_null=1, min_length=1)
	 * @param (name=data[layout_type_id], type=bigint, not_null=1, length=19)
	 */
	public function get($data) {
		$this->initLocalDBTableHandler($data);
		
		$items = $this->LocalDBTableHandler->getItems("layout_type");
		$new_items = $this->LocalDBTableHandler->filterItems($items, array("layout_type_id" => $data["layout_type_id"]), false, 1);
		return isset($new_items[0]) ? $new_items[0] : null;
	}
	
	/**
	 * @param (name=data[root_path], type=varchar, not_null=1, min_length=1)
	 * @param (name=data[encryption_key], type=varchar, not_null=1, min_length=1)
	 */
	public function getAll($data) {
		$this->initLocalDBTableHandler($data);
		
		return $this->LocalDBTableHandler->getItems("layout_type");
	}
	
	/**
	 * @param (name=data[root_path], type=varchar, not_null=1, min_length=1)
	 * @param (name=data[encryption_key], type=varchar, not_null=1, min_length=1)
	 * @param (name=data[conditions][layout_type_id], type=bigint, length=19)
	 * @param (name=data[conditions][type_id], type=tinyint, length=1)
	 * @param (name=data[conditions][name], type=varchar, length=255)
	 */
	public function search($data) {
		$this->initLocalDBTableHandler($data);
		
		$items = $this->LocalDBTableHandler->getItems("layout_type");
		$conditions = isset($data["conditions"]) ? $data["conditions"] : null;
		return $this->LocalDBTableHandler->filterItems($items, $conditions, false);
	}
}
?>
