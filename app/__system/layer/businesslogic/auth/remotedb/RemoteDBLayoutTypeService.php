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

class RemoteDBLayoutTypeService extends CommonService {
	
	public function dropAndCreateTable($data) {
		$options = isset($data["options"]) ? $data["options"] : null;
		
		$table_data = array(
			"table_name" => "sysauth_layout_type",
			"attributes" => array(
				array(
					"name" => "layout_type_id",
					"type" => "bigint",
					"primary_key" => 1,
					"auto_increment" => 1,
				),
				array(
					"name" => "type_id",
					"type" => "tinyint",
					"length" => 1,
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
		
		$this->getBusinessLogicLayer()->callBusinessLogic("auth.remotedb", "RemoteDBReservedDBTableNameService.insertIfNotExistsYet", array("name" => $table_data["table_name"]));
		
		return $status;
	}
	
	/**
	 * @param (name=data[name], type=varchar, not_null=1, min_length=1, max_length=255)
	 * @param (name=data[type_id], type=tinyint, not_null=1, default=0, length=1)
	 */
	public function insert($data) {
		$options = isset($data["options"]) ? $data["options"] : null;
		
		$data["name"] = addcslashes($data["name"], "\\'");
		$data["created_date"] = !empty($data["created_date"]) ? $data["created_date"] : date("Y-m-d H:i:s");//leave this check because of the transport data between dbs
		$data["modified_date"] = !empty($data["modified_date"]) ? $data["modified_date"] : $data["created_date"];
		
		if (empty($data["layout_type_id"]))
			$data["layout_type_id"] = "DEFAULT";
		
		$status = $this->getBroker()->callInsert("auth", "insert_layout_type", $data, $options);
		return $status ? ($data["layout_type_id"] == "DEFAULT" ? $this->getBroker()->getInsertedId($options) : $data["layout_type_id"]) : $status;
	}
	
	/**
	 * @param (name=data[layout_type_id], type=bigint, not_null=1, length=19)
	 * @param (name=data[type_id], type=tinyint, not_null=1, default=0, length=1)
	 * @param (name=data[name], type=varchar, not_null=1, min_length=1, max_length=255)
	 */
	public function update($data) {
		$options = isset($data["options"]) ? $data["options"] : null;
		
		$data["name"] = addcslashes($data["name"], "\\'");
		$data["modified_date"] = date("Y-m-d H:i:s");
		
		return $this->getBroker()->callUpdate("auth", "update_layout_type", $data, $options);
	}
	
	/**
	 * @param (name=data[old_name_prefix], type=varchar, not_null=1, min_length=1, max_length=255)
	 * @param (name=data[new_name_prefix], type=varchar, not_null=1, min_length=1, max_length=255)
	 */
	public function updateByNamePrefix($data) {
		$options = isset($data["options"]) ? $data["options"] : null;
		
		$items = $this->getAll(array("options" => $options));
		
		if ($items) {
			$len = strlen($data["old_name_prefix"]);
			$t = count($items);
			$status = true;
			
			for ($i = 0; $i < $t; $i++) {
				$item = $items[$i];
				$name = $item["name"];
				
				if (substr($name, 0, $len) == $data["old_name_prefix"]) {
					$item["name"] = $data["new_name_prefix"] . substr($name, $len);
					$item["options"] = $options;
					
					if (!$this->update($item))
						$status = false;
				}
			}
			
			return $status;
		}
		
		return true;
	}
	
	/**
	 * @param (name=data[layout_type_id], type=bigint, not_null=1, length=19)
	 */
	public function delete($data) {
		$layout_type_id = $data["layout_type_id"];
		$options = isset($data["options"]) ? $data["options"] : null;
		
		return $this->getBroker()->callDelete("auth", "delete_layout_type", array("layout_type_id" => $layout_type_id), $options);
	}
	
	/**
	 * @param (name=data[layout_type_id], type=bigint, not_null=1, length=19)
	 */
	public function get($data) {
		$layout_type_id = $data["layout_type_id"];
		$options = isset($data["options"]) ? $data["options"] : null;
		
		$result = $this->getBroker()->callSelect("auth", "get_layout_type", array("layout_type_id" => $layout_type_id), $options);
		return $result[0];
	}
	
	public function getAll($data) {
		$options = isset($data["options"]) ? $data["options"] : null;
		
		return $this->getBroker()->callSelect("auth", "get_all_layout_types", null, $options);
	}
	
	/**
	 * @param (name=data[conditions][layout_type_id], type=bigint, length=19)
	 * @param (name=data[conditions][type_id], type=tinyint, length=1)
	 * @param (name=data[conditions][name], type=varchar, length=255)
	 */
	public function search($data) {
		$conditions = isset($data["conditions"]) ? $data["conditions"] : null;
		$conditions_join = isset($data["conditions_join"]) ? $data["conditions_join"] : null;
		$options = isset($data["options"]) ? $data["options"] : null;
		
		$cond = \DB::getSQLConditions($conditions, $conditions_join);
		return $cond ? $this->getBroker()->callSelect("auth", "get_layout_types_by_conditions", array("conditions" => $cond), $options) : null;
	}
}
?>
