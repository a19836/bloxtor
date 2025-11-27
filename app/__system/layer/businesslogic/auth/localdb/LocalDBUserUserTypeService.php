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

class LocalDBUserUserTypeService extends LocalDBAuthService {
	
	/**
	 * @param (name=data[root_path], type=varchar, not_null=1, min_length=1)
	 * @param (name=data[encryption_key], type=varchar, not_null=1, min_length=1)
	 * @param (name=data[new_encryption_key], type=varchar, not_null=1, min_length=1)
	 */
	public function changeTableEncryptionKey($data) {
		$this->initLocalDBTableHandler($data);
		
		return $this->LocalDBTableHandler->changeDBTableEncryptionKey("user_user_type", $data["new_encryption_key"]);
	}
	
	/**
	 * @param (name=data[root_path], type=varchar, not_null=1, min_length=1)
	 * @param (name=data[encryption_key], type=varchar, not_null=1, min_length=1)
	 */
	public function dropAndCreateTable($data) {
		$this->initLocalDBTableHandler($data);
		
		return $this->LocalDBTableHandler->writeTableItems("", "user_user_type");
	}
	
	/**
	 * @param (name=data[root_path], type=varchar, not_null=1, min_length=1)
	 * @param (name=data[encryption_key], type=varchar, not_null=1, min_length=1)
	 * @param (name=data[user_id], type=bigint, not_null=1, length=19)
	 * @param (name=data[user_type_id], type=bigint, not_null=1, length=19)
	 */
	public function insert($data) {
		$this->initLocalDBTableHandler($data);
		
		$data["created_date"] = !empty($data["created_date"]) ? $data["created_date"] : date("Y-m-d H:i:s");//leave this check because of the transport data between dbs
		$data["modified_date"] = !empty($data["modified_date"]) ? $data["modified_date"] : $data["created_date"];
		
		return $this->LocalDBTableHandler->insertItem("user_user_type", $data, array("user_id", "user_type_id"));
	}
	
	/**
	 * @param (name=data[root_path], type=varchar, not_null=1, min_length=1)
	 * @param (name=data[encryption_key], type=varchar, not_null=1, min_length=1)
	 * @param (name=data[user_id], type=bigint, not_null=1, length=19)
	 * @param (name=data[user_type_id], type=bigint, not_null=1, length=19)
	 */
	public function delete($data) {
		$this->initLocalDBTableHandler($data);
		
		return $this->LocalDBTableHandler->deleteItem("user_user_type", array("user_id" => $data["user_id"], "user_type_id" => $data["user_type_id"]));
	}
	
	/**
	 * @param (name=data[root_path], type=varchar, not_null=1, min_length=1)
	 * @param (name=data[encryption_key], type=varchar, not_null=1, min_length=1)
	 * @param (name=data[conditions][use_id], type=bigint, length=19)
	 * @param (name=data[conditions][user_type_id], type=bigint, length=19)
	 */
	public function deleteByConditions($data) {
		$this->initLocalDBTableHandler($data);
		
		$conditions = isset($data["conditions"]) ? $data["conditions"] : null;
		return $this->LocalDBTableHandler->deleteItem("user_user_type", $conditions);
	}
	
	/**
	 * @param (name=data[root_path], type=varchar, not_null=1, min_length=1)
	 * @param (name=data[encryption_key], type=varchar, not_null=1, min_length=1)
	 * @param (name=data[user_id], type=bigint, not_null=1, length=19)
	 * @param (name=data[user_type_id], type=bigint, not_null=1, length=19)
	 */
	public function get($data) {
		$this->initLocalDBTableHandler($data);
		
		$items = $this->LocalDBTableHandler->getItems("user_user_type");
		$new_items = $this->LocalDBTableHandler->filterItems($items, array("user_id" => $data["user_id"], "user_type_id" => $data["user_type_id"]), false, 1);
		return isset($new_items[0]) ? $new_items[0] : null;
	}
	
	/**
	 * @param (name=data[root_path], type=varchar, not_null=1, min_length=1)
	 * @param (name=data[encryption_key], type=varchar, not_null=1, min_length=1)
	 */
	public function getAll($data) {
		$this->initLocalDBTableHandler($data);
		
		return $this->LocalDBTableHandler->getItems("user_user_type");
	}
	
	/**
	 * @param (name=data[root_path], type=varchar, not_null=1, min_length=1)
	 * @param (name=data[encryption_key], type=varchar, not_null=1, min_length=1)
	 * @param (name=data[conditions][user_id], type=bigint, length=19)
	 * @param (name=data[conditions][user_type_id], type=bigint, length=19)
	 */
	public function search($data) {
		$this->initLocalDBTableHandler($data);
		
		$items = $this->LocalDBTableHandler->getItems("user_user_type");
		$conditions = isset($data["conditions"]) ? $data["conditions"] : null;
		return $this->LocalDBTableHandler->filterItems($items, $conditions, false);
	}
}
?>
