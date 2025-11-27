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

class RemoteDBLayoutTypePermissionService extends CommonService {
	
	public function dropAndCreateTable($data) {
		$options = isset($data["options"]) ? $data["options"] : null;
		
		$table_data = array(
			"table_name" => "sysauth_layout_type_permission",
			"attributes" => array(
				array(
					"name" => "layout_type_id",
					"type" => "bigint",
					"primary_key" => 1,
				),
				array(
					"name" => "permission_id",
					"type" => "bigint",
					"primary_key" => 1,
				),
				array(
					"name" => "object_type_id",
					"type" => "bigint",
					"primary_key" => 1,
				),
				array(
					"name" => "object_id",
					"type" => "varchar",
					"length" => 255,
					"primary_key" => 1,
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
	 * @param (name=data[layout_type_id], type=bigint, not_null=1, length=19)
	 * @param (name=data[permission_id], type=bigint, not_null=1, length=19)
	 * @param (name=data[object_type_id], type=bigint, not_null=1, length=19)
	 * @param (name=data[object_id], type=varchar, not_null=1, min_length=1, max_length=255)
	 */
	public function insert($data) {
		$options = isset($data["options"]) ? $data["options"] : null;
		
		$data["object_id"] = addcslashes($data["object_id"], "\\'");
		$data["created_date"] = !empty($data["created_date"]) ? $data["created_date"] : date("Y-m-d H:i:s");//leave this check because of the transport data between dbs
		$data["modified_date"] = !empty($data["modified_date"]) ? $data["modified_date"] : $data["created_date"];
			
		return $this->getBroker()->callInsert("auth", "insert_layout_type_permission", $data, $options);
	}
	
	/**
	 * @param (name=data[layout_type_id], type=bigint, not_null=1, length=19)
	 * @param (name=data[permission_id], type=bigint, not_null=1, length=19)
	 * @param (name=data[object_type_id], type=bigint, not_null=1, length=19)
	 * @param (name=data[old_object_id], type=varchar, not_null=1, min_length=1, max_length=255)
	 * @param (name=data[new_object_id], type=varchar, not_null=1, min_length=1, max_length=255)
	 */
	public function updateObjectId($data) {
		$options = isset($data["options"]) ? $data["options"] : null;
		
		$data["old_object_id"] = addcslashes($data["old_object_id"], "\\'");
		$data["new_object_id"] = addcslashes($data["new_object_id"], "\\'");
		$data["modified_date"] = date("Y-m-d H:i:s");
		
		return $this->getBroker()->callUpdate("auth", "update_layout_type_permission_object_id", $data, $options);
	}
	
	/**
	 * @param (name=data[old_object_prefix], type=varchar, not_null=1, min_length=1, max_length=255)
	 * @param (name=data[new_object_prefix], type=varchar, not_null=1, min_length=1, max_length=255)
	 */
	public function updateByObjectPrefix($data) {
		$options = isset($data["options"]) ? $data["options"] : null;
		
		$items = $this->getAll(array("options" => $options));
		
		if ($items) {
			$len = strlen($data["old_object_prefix"]);
			$t = count($items);
			$status = true;
			
			for ($i = 0; $i < $t; $i++) {
				$item = $items[$i];
				$object_id = $item["object_id"];
				
				if (substr($object_id, 0, $len) == $data["old_object_prefix"]) {
					$item["old_object_id"] = $item["object_id"];
					$item["new_object_id"] = $data["new_object_prefix"] . substr($object_id, $len);
					$item["options"] = $options;
					unset($item["object_id"]);
					
					if (!$this->updateObjectId($item))
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
	public function updateByObjectsPermissions($data) {
		$options = isset($data["options"]) ? $data["options"] : null;
		
		if ($data["layout_type_id"]) {
			$data_aux = array();
			$data_aux["conditions"] = array("layout_type_id" => $data["layout_type_id"]);
			$data_aux["options"] = $options;
			$this->deleteByConditions($data_aux);
			
			$status = true;
			$date = date("Y-m-d H:i:s");
			
			if (isset($data["permissions_by_objects"]) && is_array($data["permissions_by_objects"]))
				foreach ($data["permissions_by_objects"] as $object_type_id => $permissions_by_object)
					foreach ($permissions_by_object as $object_id => $permission_ids) 
						if ($permission_ids) {
							$t = count($permission_ids);
							for ($i = 0; $i < $t; $i++) {
								$permission_id = $permission_ids[$i];
						
								if ($permission_id) {
									$item_data = array(
										"layout_type_id" => $data["layout_type_id"],
										"permission_id" => $permission_id,
										"object_type_id" => $object_type_id,
										"object_id" => $object_id,
										"created_date" => $date,
										"modified_date" => $date,
										"options" => $options,
									);
								
									if (!$this->insert($item_data)) {
										$status = false;
									}
								}
							}
						}
			
			return $status;
		}
		return false;
	}
	
	/**
	 * @param (name=data[layout_type_id], type=bigint, not_null=1, length=19)
	 * @param (name=data[permission_id], type=bigint, not_null=1, length=19)
	 * @param (name=data[object_type_id], type=bigint, not_null=1, length=19)
	 * @param (name=data[object_id], type=varchar, not_null=1, min_length=1, max_length=255)
	 */
	public function delete($data) {
		$options = isset($data["options"]) ? $data["options"] : null;
		
		return $this->getBroker()->callDelete("auth", "delete_layout_type_permission", array("layout_type_id" => $data["layout_type_id"], "permission_id" => $data["permission_id"], "object_type_id" => $data["object_type_id"], "object_id" => $data["object_id"]), $options);
	}
	
	/**
	 * @param (name=data[conditions][layout_type_id], type=bigint, length=19)
	 * @param (name=data[conditions][permission_id], type=bigint, length=19)
	 * @param (name=data[conditions][object_type_id], type=bigint, length=19)
	 * @param (name=data[conditions][object_id], type=varchar, length=255)
	 */
	public function deleteByConditions($data) {
		$conditions = isset($data["conditions"]) ? $data["conditions"] : null;
		$conditions_join = isset($data["conditions_join"]) ? $data["conditions_join"] : null;
		$options = isset($data["options"]) ? $data["options"] : null;
		
		$cond = \DB::getSQLConditions($conditions, $conditions_join);
		return $this->getBroker()->callDelete("auth", "delete_layout_type_permissions_by_conditions", array("conditions" => $cond), $options);
	}
	
	/**
	 * @param (name=data[layout_type_id], type=bigint, not_null=1, length=19)
	 * @param (name=data[permission_id], type=bigint, not_null=1, length=19)
	 * @param (name=data[object_type_id], type=bigint, not_null=1, length=19)
	 * @param (name=data[object_id], type=varchar, not_null=1, min_length=1, max_length=255)
	 */
	public function get($data) {
		$options = isset($data["options"]) ? $data["options"] : null;
		
		$result = $this->getBroker()->callSelect("auth", "get_layout_type_permission", array("layout_type_id" => $data["layout_type_id"], "permission_id" => $data["permission_id"], "object_type_id" => $data["object_type_id"], "object_id" => $data["object_id"]), $options);
		return isset($result[0]) ? $result[0] : null;
	}
	
	public function getAll($data) {
		$options = isset($data["options"]) ? $data["options"] : null;
		
		return $this->getBroker()->callSelect("auth", "get_all_layout_type_permissions", null, $options);
	}
	
	/**
	 * @param (name=data[conditions][layout_type_id], type=bigint, length=19)
	 * @param (name=data[conditions][permission_id], type=bigint, length=19)
	 * @param (name=data[conditions][object_type_id], type=bigint, length=19)
	 * @param (name=data[conditions][object_id], type=varchar, length=255)
	 */
	public function search($data) {
		$conditions = isset($data["conditions"]) ? $data["conditions"] : null;
		$conditions_join = isset($data["conditions_join"]) ? $data["conditions_join"] : null;
		$options = isset($data["options"]) ? $data["options"] : null;
		
		$cond = \DB::getSQLConditions($conditions, $conditions_join);
		return $cond ? $this->getBroker()->callSelect("auth", "get_layout_type_permissions_by_conditions", array("conditions" => $cond), $options) : null;
	}
}
?>
