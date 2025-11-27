<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 *
 * Original Bloxtor Repo: https://github.com/a19836/bloxtor
 *
 * YOU ARE NOT AUTHORIZED TO MODIFY OR REMOVE ANY PART OF THIS NOTICE!
 */

namespace __system\businesslogic;

include_once $vars["current_business_logic_module_path"] . "LocalDBAuthService.php";

class LocalDBReservedDBTableNameService extends LocalDBAuthService {
	
	/**
	 * @param (name=data[root_path], type=varchar, not_null=1, min_length=1)
	 * @param (name=data[encryption_key], type=varchar, not_null=1, min_length=1)
	 * @param (name=data[new_encryption_key], type=varchar, not_null=1, min_length=1)
	 */
	public function changeTableEncryptionKey($data) {
		$this->initLocalDBTableHandler($data);
		
		return $this->LocalDBTableHandler->changeDBTableEncryptionKey("reserved_db_table_name", $data["new_encryption_key"]);
	}
	
	/**
	 * @param (name=data[root_path], type=varchar, not_null=1, min_length=1)
	 * @param (name=data[encryption_key], type=varchar, not_null=1, min_length=1)
	 */
	public function dropAndCreateTable($data) {
		$this->initLocalDBTableHandler($data);
		
		return $this->LocalDBTableHandler->writeTableItems("", "reserved_db_table_name");
	}
	
	/**
	 * @param (name=data[root_path], type=varchar, not_null=1, min_length=1)
	 * @param (name=data[encryption_key], type=varchar, not_null=1, min_length=1)
	 * @param (name=data[name], type=varchar, not_null=1, min_length=1, max_length=255)
	 */
	public function insert($data) {
		$this->initLocalDBTableHandler($data);
		
		$data["created_date"] = !empty($data["created_date"]) ? $data["created_date"] : date("Y-m-d H:i:s");//leave this check because of the transport data between dbs
		$data["modified_date"] = !empty($data["modified_date"]) ? $data["modified_date"] : $data["created_date"];
		
		if (empty($data["reserved_db_table_name_id"]))
			$data["reserved_db_table_name_id"] = $this->LocalDBTableHandler->getPKMaxValue("reserved_db_table_name", "reserved_db_table_name_id") + 1;
		
		return $this->LocalDBTableHandler->insertItem("reserved_db_table_name", $data, array("reserved_db_table_name_id")) ? $data["reserved_db_table_name_id"] : null;
	}
	
	/**
	 * @param (name=data[root_path], type=varchar, not_null=1, min_length=1)
	 * @param (name=data[encryption_key], type=varchar, not_null=1, min_length=1)
	 * @param (name=data[name], type=varchar, not_null=1, min_length=1, max_length=255)
	 */
	public function insertIfNotExistsYet($data) {
		$results = $this->search(array("root_path" => $data["root_path"], "encryption_key" => $data["encryption_key"], "conditions" => array("name" => $data["name"])));
		
		if (!empty($results[0]))
			return true;
		
		return $this->insert($data);
	}
	
	/**
	 * @param (name=data[root_path], type=varchar, not_null=1, min_length=1)
	 * @param (name=data[encryption_key], type=varchar, not_null=1, min_length=1)
	 * @param (name=data[reserved_db_table_name_id], type=bigint, not_null=1, length=19)
	 * @param (name=data[name], type=varchar, not_null=1, min_length=1, max_length=255)
	 */
	public function update($data) {
		$this->initLocalDBTableHandler($data);
		
		$data["modified_date"] = date("Y-m-d H:i:s");
		
		return $this->LocalDBTableHandler->updateItem("reserved_db_table_name", $data, array("reserved_db_table_name_id"));
	}
	
	/**
	 * @param (name=data[root_path], type=varchar, not_null=1, min_length=1)
	 * @param (name=data[encryption_key], type=varchar, not_null=1, min_length=1)
	 * @param (name=data[reserved_db_table_name_id], type=bigint, not_null=1, length=19)
	 */
	public function delete($data) {
		$this->initLocalDBTableHandler($data);
		
		return $this->LocalDBTableHandler->deleteItem("reserved_db_table_name", array("reserved_db_table_name_id" => $data["reserved_db_table_name_id"]));
	}
	
	/**
	 * @param (name=data[root_path], type=varchar, not_null=1, min_length=1)
	 * @param (name=data[encryption_key], type=varchar, not_null=1, min_length=1)
	 * @param (name=data[reserved_db_table_name_id], type=bigint, not_null=1, length=19)
	 */
	public function get($data) {
		$this->initLocalDBTableHandler($data);
		
		$items = $this->LocalDBTableHandler->getItems("reserved_db_table_name");
		$new_items = $this->LocalDBTableHandler->filterItems($items, array("reserved_db_table_name_id" => $data["reserved_db_table_name_id"]), false, 1);
		return isset($new_items[0]) ? $new_items[0] : null;
	}
	
	/**
	 * @param (name=data[root_path], type=varchar, not_null=1, min_length=1)
	 * @param (name=data[encryption_key], type=varchar, not_null=1, min_length=1)
	 */
	public function getAll($data) {
		$this->initLocalDBTableHandler($data);
		
		return $this->LocalDBTableHandler->getItems("reserved_db_table_name");
	}
	
	/**
	 * @param (name=data[root_path], type=varchar, not_null=1, min_length=1)
	 * @param (name=data[encryption_key], type=varchar, not_null=1, min_length=1)
	 * @param (name=data[conditions][reserved_db_table_name_id], type=bigint, length=19)
	 * @param (name=data[conditions][name], type=varchar, length=255)
	 */
	public function search($data) {
		$this->initLocalDBTableHandler($data);
		
		$items = $this->LocalDBTableHandler->getItems("reserved_db_table_name");
		$conditions = isset($data["conditions"]) ? $data["conditions"] : null;
		return $this->LocalDBTableHandler->filterItems($items, $conditions, false);
	}
}
?>
