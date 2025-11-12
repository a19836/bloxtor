<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

namespace __system\businesslogic;

include_once $vars["current_business_logic_module_path"] . "LocalDBAuthService.php";

class LocalDBLoginControlService extends LocalDBAuthService {
	
	/**
	 * @param (name=data[root_path], type=varchar, not_null=1, min_length=1)
	 * @param (name=data[encryption_key], type=varchar, not_null=1, min_length=1)
	 * @param (name=data[new_encryption_key], type=varchar, not_null=1, min_length=1)
	 */
	public function changeTableEncryptionKey($data) {
		$this->initLocalDBTableHandler($data);
		
		return $this->LocalDBTableHandler->changeDBTableEncryptionKey("login_control", $data["new_encryption_key"]);
	}
	
	/**
	 * @param (name=data[root_path], type=varchar, not_null=1, min_length=1)
	 * @param (name=data[encryption_key], type=varchar, not_null=1, min_length=1)
	 */
	public function dropAndCreateTable($data) {
		$this->initLocalDBTableHandler($data);
		
		return $this->LocalDBTableHandler->writeTableItems("", "login_control");
	}
	
	/**
	 * @param (name=data[root_path], type=varchar, not_null=1, min_length=1)
	 * @param (name=data[encryption_key], type=varchar, not_null=1, min_length=1)
	 * @param (name=data[username], type=varchar, not_null=1, min_length=1, max_length=50)
	 */
	public function insert($data) {
		$this->initLocalDBTableHandler($data);
		
		$old_item = $this->get($data);
		
		if (empty($data["login_expired_time"])) {
			$data["login_expired_time"] = time() + 3600;//60 * 60 = 3600 secs = 1 hour
		}
		
		if (empty($data["session_id"])) {
			$data["session_id"] = \CryptoKeyHandler::getHexKey();
		}
		
		$data["created_date"] = !empty($data["created_date"]) ? $data["created_date"] : date("Y-m-d H:i:s");//leave this check because of the transport data between dbs
		$data["modified_date"] = !empty($data["modified_date"]) ? $data["modified_date"] : $data["created_date"];
		
		if ($old_item) 
			$status = $this->LocalDBTableHandler->updateItem("login_control", $data, array("username"));
		else 
			$status = $this->LocalDBTableHandler->insertItem("login_control", $data, array("username"));
		
		return $status ? $data["session_id"] : null;
	}
	
	/**
	 * @param (name=data[root_path], type=varchar, not_null=1, min_length=1)
	 * @param (name=data[encryption_key], type=varchar, not_null=1, min_length=1)
	 * @param (name=data[username], type=varchar, not_null=1, min_length=1, max_length=50)
	 */
	public function insertFailedLoginAttempt($data) {
		$failed_attempts = $this->getFailedLoginAttempts($data);
		$old_item = $this->get($data);
		
		$date = date("Y-m-d H:i:s");
		
		$item = array(
			"username" => $data["username"],
			"failed_login_attempts" => $failed_attempts + 1,
			"failed_login_time" => time(),
			"created_date" => !empty($old_item["created_date"]) ? $old_item["created_date"] : $date,
			"modified_date" => !empty($old_item["modified_date"]) ? $old_item["modified_date"] : $date,
		);
		
		if ($old_item) 
			return $this->LocalDBTableHandler->updateItem("login_control", $item, array("username"));
		
		return $this->LocalDBTableHandler->insertItem("login_control", $item, array("username"));
	}
	
	/**
	 * @param (name=data[root_path], type=varchar, not_null=1, min_length=1)
	 * @param (name=data[encryption_key], type=varchar, not_null=1, min_length=1)
	 * @param (name=data[session_id], type=varchar, not_null=1, min_length=1, max_length=200)
	 */
	public function expireSession($data) {
		$item = $this->getBySessionId($data);
		
		if ($item && !empty($item["session_id"]) && !empty($item["username"])) {
			$item["login_expired_time"] = time() - 1;
			$item["modified_date"] = date("Y-m-d H:i:s");
			
			return $this->LocalDBTableHandler->updateItem("login_control", $item, array("username"));
		}
		
		return false;
	}
	
	/**
	 * @param (name=data[root_path], type=varchar, not_null=1, min_length=1)
	 * @param (name=data[encryption_key], type=varchar, not_null=1, min_length=1)
	 * @param (name=data[username], type=varchar, not_null=1, min_length=1, max_length=50)
	 */
	public function resetFailedLoginAttempts($data) {
		$this->initLocalDBTableHandler($data);
		
		$item = $this->get($data);
		
		if ($item) {
			$item["failed_login_attempts"] = null;
			$item["failed_login_time"] = null;
			$item["modified_date"] = date("Y-m-d H:i:s");
		
			return $this->LocalDBTableHandler->updateItem("login_control", $item, array("username"));
		}
		return true;
	}
	
	/**
	 * @param (name=data[root_path], type=varchar, not_null=1, min_length=1)
	 * @param (name=data[encryption_key], type=varchar, not_null=1, min_length=1)
	 * @param (name=data[username], type=varchar, not_null=1, min_length=1, max_length=50)
	 */
	public function delete($data) {
		$this->initLocalDBTableHandler($data);
		
		return $this->LocalDBTableHandler->deleteItem("login_control", array("username" => $data["username"]));
	}
	
	/**
	 * @param (name=data[root_path], type=varchar, not_null=1, min_length=1)
	 * @param (name=data[encryption_key], type=varchar, not_null=1, min_length=1)
	 * @param (name=data[username], type=varchar, not_null=1, min_length=1, max_length=50)
	 */
	public function getFailedLoginAttempts($data) {
		$item = $this->get($data);
		return $item && !empty($item["username"]) && isset($item["failed_login_attempts"]) ? $item["failed_login_attempts"] : null;
	}
	
	/**
	 * @param (name=data[root_path], type=varchar, not_null=1, min_length=1)
	 * @param (name=data[encryption_key], type=varchar, not_null=1, min_length=1)
	 * @param (name=data[username], type=varchar, not_null=1, min_length=1, max_length=50)
	 */
	public function get($data) {
		$this->initLocalDBTableHandler($data);
		
		$items = $this->LocalDBTableHandler->getItems("login_control");
		$new_items = $this->LocalDBTableHandler->filterItems($items, array("username" => $data["username"]), false, 1);
		return isset($new_items[0]) ? $new_items[0] : null;
	}
	
	/**
	 * @param (name=data[root_path], type=varchar, not_null=1, min_length=1)
	 * @param (name=data[encryption_key], type=varchar, not_null=1, min_length=1)
	 * @param (name=data[session_id], type=varchar, not_null=1, min_length=1, max_length=200)
	 */
	public function getBySessionId($data) {
		$this->initLocalDBTableHandler($data);
		
		$items = $this->LocalDBTableHandler->getItems("login_control");
		$new_items = $this->LocalDBTableHandler->filterItems($items, array("session_id" => $data["session_id"]), false, 1);
		return isset($new_items[0]) ? $new_items[0] : null;
	}
	
	/**
	 * @param (name=data[root_path], type=varchar, not_null=1, min_length=1)
	 * @param (name=data[encryption_key], type=varchar, not_null=1, min_length=1)
	 * @param (name=data[username], type=varchar, not_null=1, min_length=1, max_length=50)
	 */
	public function isUserBlocked($data) {
		$item = $this->get($data);
		
		if (empty($data["maximum_failed_attempts"]))
			$data["maximum_failed_attempts"] = 3;
		
		if (empty($data["expired_time"]))
			$data["expired_time"] = 3600;//60 * 60 = 3600 secs = 1 hour
		
		$failed_login_attempts = isset($item["failed_login_attempts"]) ? $item["failed_login_attempts"] : null;
		$failed_login_time = isset($item["failed_login_time"]) ? $item["failed_login_time"] : null;
		
		return ($failed_login_attempts > $data["maximum_failed_attempts"]) && ($failed_login_time + $data["expired_time"] >= time());
	}
	
	/**
	 * @param (name=data[root_path], type=varchar, not_null=1, min_length=1)
	 * @param (name=data[encryption_key], type=varchar, not_null=1, min_length=1)
	 */
	public function getAll($data) {
		$this->initLocalDBTableHandler($data);
		
		return $this->LocalDBTableHandler->getItems("login_control");
	}
}
?>
