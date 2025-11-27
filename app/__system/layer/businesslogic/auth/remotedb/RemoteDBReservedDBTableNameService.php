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

class RemoteDBReservedDBTableNameService extends CommonService {
	
	public function dropAndCreateTable($data) {
		$options = isset($data["options"]) ? $data["options"] : null;
		
		$table_data = array(
			"table_name" => "sysauth_reserved_db_table_name",
			"attributes" => array(
				array(
					"name" => "reserved_db_table_name_id",
					"type" => "bigint",
					"primary_key" => 1,
					"auto_increment" => 1,
				),
				array(
					"name" => "name",
					"type" => "varchar",
					"length" => 255,
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
		
		$this->insertIfNotExistsYet(array("name" => $table_data["table_name"]));
		
		return $status;
	}
	
	/**
	 * @param (name=data[name], type=varchar, not_null=1, min_length=1, max_length=255)
	 */
	public function insert($data) {
		$options = isset($data["options"]) ? $data["options"] : null;
		
		$data["name"] = addcslashes($data["name"], "\\'");
		$data["created_date"] = !empty($data["created_date"]) ? $data["created_date"] : date("Y-m-d H:i:s");//leave this check because of the transport data between dbs
		$data["modified_date"] = !empty($data["modified_date"]) ? $data["modified_date"] : $data["created_date"];
		
		if (empty($data["reserved_db_table_name_id"]))
			$data["reserved_db_table_name_id"] = "DEFAULT";
		
		$status = $this->getBroker()->callInsert("auth", "insert_reserved_db_table_name", $data, $options);
		return $status ? ($data["reserved_db_table_name_id"] == "DEFAULT" ? $this->getBroker()->getInsertedId($options) : $data["reserved_db_table_name_id"]) : $status;
	}
	
	/**
	 * @param (name=data[name], type=varchar, not_null=1, min_length=1, max_length=255)
	 */
	public function insertIfNotExistsYet($data) {
		$options = isset($data["options"]) ? $data["options"] : null;
		$results = $this->search(array("conditions" => array("name" => $data["name"]), "options" => $options));
		
		if (!empty($results[0]))
			return true;
		
		return $this->insert($data);
	}
	
	/**
	 * @param (name=data[reserved_db_table_name_id], type=bigint, not_null=1, length=19)
	 * @param (name=data[name], type=varchar, not_null=1, min_length=1, max_length=255)
	 */
	public function update($data) {
		$options = isset($data["options"]) ? $data["options"] : null;
		
		$data["name"] = addcslashes($data["name"], "\\'");
		$data["modified_date"] = date("Y-m-d H:i:s");
		
		return $this->getBroker()->callUpdate("auth", "update_reserved_db_table_name", $data, $options);
	}
	
	/**
	 * @param (name=data[reserved_db_table_name_id], type=bigint, not_null=1, length=19)
	 */
	public function delete($data) {
		$reserved_db_table_name_id = $data["reserved_db_table_name_id"];
		$options = isset($data["options"]) ? $data["options"] : null;
		
		return $this->getBroker()->callDelete("auth", "delete_reserved_db_table_name", array("reserved_db_table_name_id" => $reserved_db_table_name_id), $options);
	}
	
	/**
	 * @param (name=data[reserved_db_table_name_id], type=bigint, not_null=1, length=19)
	 */
	public function get($data) {
		$reserved_db_table_name_id = $data["reserved_db_table_name_id"];
		$options = isset($data["options"]) ? $data["options"] : null;
		
		$result = $this->getBroker()->callSelect("auth", "get_reserved_db_table_name", array("reserved_db_table_name_id" => $reserved_db_table_name_id), $options);
		return isset($result[0]) ? $result[0] : null;
	}
	
	public function getAll($data) {
		$options = isset($data["options"]) ? $data["options"] : null;
		
		return $this->getBroker()->callSelect("auth", "get_all_reserved_db_table_names", null, $options);
	}
	
	/**
	 * @param (name=data[conditions][reserved_db_table_name_id], type=bigint, length=19)
	 * @param (name=data[conditions][name], type=varchar, length=255)
	 */
	public function search($data) {
		$conditions = isset($data["conditions"]) ? $data["conditions"] : null;
		$conditions_join = isset($data["conditions_join"]) ? $data["conditions_join"] : null;
		$options = isset($data["options"]) ? $data["options"] : null;
		
		$cond = \DB::getSQLConditions($conditions, $conditions_join);
		return $cond ? $this->getBroker()->callSelect("auth", "get_reserved_db_table_names_by_conditions", array("conditions" => $cond), $options) : null;
	}
}
?>
