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

include_once $vars["business_logic_modules_service_common_file_path"];
include_once get_lib("org.phpframework.db.DB");

class RemoteDBUserService extends CommonService {
	
	public function dropAndCreateTable($data) {
		$options = isset($data["options"]) ? $data["options"] : null;
		
		$table_data = array(
			"table_name" => "sysauth_user",
			"attributes" => array(
				array(
					"name" => "user_id",
					"type" => "bigint",
					"primary_key" => 1,
					"auto_increment" => 1,
				),
				array(
					"name" => "username",
					"type" => "varchar",
					"length" => 50,
					"unique" => 1,
				),
				array(
					"name" => "password",
					"type" => "varchar",
					"length" => 50,
				),
				array(
					"name" => "name",
					"type" => "varchar",
					"length" => 50,
					"null" => 1,
				),
				array(
					"name" => "created_date",
					"type" => "timestamp",
					"null" => 1,
				),
				array(
					"name" => "modified_date",
					"type" => "timestamp",
					"null" => 1,
				),
			)
		);
		
		if (!isset($options["schema"]))
			$options["schema"] = $this->getBroker()->getFunction("getOption", "schema");
		
		$drop_sql = $this->getBroker()->getFunction("getDropTableStatement", $table_data["table_name"], $options);
		$create_sql = $this->getBroker()->getFunction("getCreateTableStatement", array($table_data), $options);
		
		$status = $this->getBroker()->setData($drop_sql, $options) && $this->getBroker()->setData($create_sql, $options);
		
		$this->getBusinessLogicLayer()->callBusinessLogic("auth.remotedb", "RemoteDBReservedDBTableNameService.insertIfNotExistsYet", array("name" => $table_data["table_name"]));
		
		return $status;
	}
	
	/**
	 * @param (name=data[username], type=varchar, not_null=1, min_length=1, max_length=50)
	 * @param (name=data[password], type=varchar, not_null=1, min_length=1, max_length=50)
	 * @param (name=data[name], type=varchar, length=50)
	 */
	public function insert($data) {
		$options = isset($data["options"]) ? $data["options"] : null;
		
		$this->prepareUserData($data);
		$data["username"] = addcslashes($data["username"], "\\'");
		$data["name"] = isset($data["name"]) ? addcslashes($data["name"], "\\'") : "";
		$data["created_date"] = !empty($data["created_date"]) ? $data["created_date"] : date("Y-m-d H:i:s");//leave this check because of the transport data between dbs
		$data["modified_date"] = !empty($data["modified_date"]) ? $data["modified_date"] : $data["created_date"];
		
		if (empty($data["user_id"]))
			$data["user_id"] = "DEFAULT";
		
		$status = $this->getBroker()->callInsert("auth", "insert_user", $data, $options);
		return $status ? ($data["user_id"] == "DEFAULT" ? $this->getBroker()->getInsertedId($options) : $data["user_id"]) : $status;
	}
	
	/**
	 * @param (name=data[user_id], type=bigint, not_null=1, length=19)
	 * @param (name=data[username], type=varchar, not_null=1, min_length=1, max_length=50)
	 * @param (name=data[password], type=varchar, not_null=1, min_length=1, max_length=50)
	 * @param (name=data[name], type=varchar, length=50)
	 */
	public function update($data) {
		$options = isset($data["options"]) ? $data["options"] : null;
		
		$this->prepareUserData($data);
		$data["username"] = addcslashes($data["username"], "\\'");
		$data["name"] = isset($data["name"]) ? addcslashes($data["name"], "\\'") : "";
		$data["modified_date"] = date("Y-m-d H:i:s");
		
		return $this->getBroker()->callUpdate("auth", "update_user", $data, $options);
	}
	
	/**
	 * @param (name=data[user_id], type=bigint, not_null=1, length=19)
	 */
	public function delete($data) {
		$user_id = $data["user_id"];
		$options = isset($data["options"]) ? $data["options"] : null;
		
		return $this->getBroker()->callDelete("auth", "delete_user", array("user_id" => $user_id), $options);
	}
	
	/**
	 * @param (name=data[user_id], type=bigint, not_null=1, length=19)
	 */
	public function get($data) {
		$user_id = $data["user_id"];
		$options = isset($data["options"]) ? $data["options"] : null;
		
		$result = $this->getBroker()->callSelect("auth", "get_user", array("user_id" => $user_id), $options);
		return isset($result[0]) ? $result[0] : null;
	}
	
	/**
	 * @param (name=data[username], type=varchar, not_null=1, min_length=1, max_length=50)
	 * @param (name=data[password], type=varchar, not_null=1, min_length=1, max_length=50)
	 */
	public function getByUsernameAndPassword($data) {
		$data["conditions"]["username"] = $data["username"];
		$data["conditions"]["password"] = $data["password"];
		
		unset($data["username"]);
		unset($data["password"]);
		
		$items = $this->search($data);
		return isset($items[0]) ? $items[0] : null;
	}
	
	public function getAll($data) {
		$options = isset($data["options"]) ? $data["options"] : null;
		
		return $this->getBroker()->callSelect("auth", "get_all_users", null, $options);
	}
	
	/**
	 * @param (name=data[conditions][user_id], type=bigint, length=19)
	 * @param (name=data[conditions][username], type=varchar, length=50)
	 * @param (name=data[conditions][password], type=varchar, length=50)
	 * @param (name=data[conditions][name], type=varchar, length=50)
	 */
	public function search($data) {
		$conditions = isset($data["conditions"]) ? $data["conditions"] : null;
		$conditions_join = isset($data["conditions_join"]) ? $data["conditions_join"] : null;
		$options = isset($data["options"]) ? $data["options"] : null;
		
		$this->prepareUserData($conditions);
		
		$cond = \DB::getSQLConditions($conditions, $conditions_join);
		return $cond ? $this->getBroker()->callSelect("auth", "get_users_by_conditions", array("conditions" => $cond), $options) : null;
	}
	
	private function prepareUserData(&$data) {
		if (isset($data["password"]) && empty($data["options"]["raw_password"])) 
			$data["password"] = md5($data["password"]);
	}
}
?>
