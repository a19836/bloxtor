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

class RemoteDBUserStatsService extends CommonService {
	
	public function dropAndCreateTable($data) {
		$options = isset($data["options"]) ? $data["options"] : null;
		
		$table_data = array(
			"table_name" => "sysauth_user_stats",
			"attributes" => array(
				array(
					"name" => "name",
					"type" => "varchar",
					"length" => 100,
					"primary_key" => 1,
				),
				array(
					"name" => "value",
					"type" => "varchar",
					"length" => 200,
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
	 * @param (name=data[name], type=varchar, not_null=1, min_length=1, max_length=100)
	 */
	public function insert($data) {
		$options = isset($data["options"]) ? $data["options"] : null;
		
		$old_item = $this->get($data);
		
		$data["name"] = addcslashes($data["name"], "\\'");
		$data["value"] = isset($data["value"]) ? addcslashes($data["value"], "\\'") : "";
		$data["created_date"] = !empty($data["created_date"]) ? $data["created_date"] : date("Y-m-d H:i:s");//leave this check because of the transport data between dbs
		$data["modified_date"] = !empty($data["modified_date"]) ? $data["modified_date"] : $data["created_date"];
		
		if ($old_item) {
			$data["created_date"] = isset($old_item["created_date"]) ? $old_item["created_date"] : null;
			$status = $this->getBroker()->callInsert("auth", "update_user_stats", $data, $options);
		}
		else
			$status = $this->getBroker()->callInsert("auth", "insert_user_stats", $data, $options);
		
		return $status ? $data["name"] : null;
	}
	
	/**
	 * @param (name=data[name], type=varchar, not_null=1, min_length=1, max_length=100)
	 */
	public function get($data) {
		$name = addcslashes($data["name"], "\\'");
		$options = isset($data["options"]) ? $data["options"] : null;
		
		$items = $this->getBroker()->callSelect("auth", "get_user_stats", array("name" => $name), $options);
		return isset($items[0]) ? $items[0] : null;
	}
	
	public function getAll($data) {
		$options = isset($data["options"]) ? $data["options"] : null;
		
		return $this->getBroker()->callSelect("auth", "get_all_user_statss", null, $options);
	}
}
?>
