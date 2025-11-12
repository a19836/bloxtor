<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

namespace __system\businesslogic;

include_once $vars["current_business_logic_module_path"] . "LocalDBAuthService.php";

class LocalDBLayoutTypePermissionService extends LocalDBAuthService {
	
	/**
	 * @param (name=data[root_path], type=varchar, not_null=1, min_length=1)
	 * @param (name=data[encryption_key], type=varchar, not_null=1, min_length=1)
	 * @param (name=data[new_encryption_key], type=varchar, not_null=1, min_length=1)
	 */
	public function changeTableEncryptionKey($data) {
		$this->initLocalDBTableHandler($data);
		
		return $this->LocalDBTableHandler->changeDBTableEncryptionKey("layout_type_permission", $data["new_encryption_key"]);
	}
	
	/**
	 * @param (name=data[root_path], type=varchar, not_null=1, min_length=1)
	 * @param (name=data[encryption_key], type=varchar, not_null=1, min_length=1)
	 */
	public function dropAndCreateTable($data) {
		$this->initLocalDBTableHandler($data);
		
		return $this->LocalDBTableHandler->writeTableItems("", "layout_type_permission");
	}
	
	/**
	 * @param (name=data[root_path], type=varchar, not_null=1, min_length=1)
	 * @param (name=data[encryption_key], type=varchar, not_null=1, min_length=1)
	 * @param (name=data[layout_type_id], type=bigint, not_null=1, length=19)
	 * @param (name=data[permission_id], type=bigint, not_null=1, length=19)
	 * @param (name=data[object_type_id], type=bigint, not_null=1, length=19)
	 * @param (name=data[object_id], type=varchar, not_null=1, min_length=1, max_length=255)
	 */
	public function insert($data) {
		$this->initLocalDBTableHandler($data);
		
		$data["created_date"] = !empty($data["created_date"]) ? $data["created_date"] : date("Y-m-d H:i:s");//leave this check because of the transport data between dbs
		$data["modified_date"] = !empty($data["modified_date"]) ? $data["modified_date"] : $data["created_date"];
		
		return $this->LocalDBTableHandler->insertItem("layout_type_permission", $data, array("layout_type_id", "permission_id", "object_type_id", "object_id"));
	}
	
	/**
	 * @param (name=data[root_path], type=varchar, not_null=1, min_length=1)
	 * @param (name=data[encryption_key], type=varchar, not_null=1, min_length=1)
	 * @param (name=data[layout_type_id], type=bigint, not_null=1, length=19)
	 * @param (name=data[permission_id], type=bigint, not_null=1, length=19)
	 * @param (name=data[object_type_id], type=bigint, not_null=1, length=19)
	 * @param (name=data[old_object_id], type=varchar, not_null=1, min_length=1, max_length=255)
	 * @param (name=data[new_object_id], type=varchar, not_null=1, min_length=1, max_length=255)
	 */
	public function updateObjectId($data) {
		$this->initLocalDBTableHandler($data);
		
		$items = $this->getAll(array("root_path" => $data["root_path"], "encryption_key" => $data["encryption_key"]));
		
		if ($items) {
			$len = strlen($data["old_object_prefix"]);
			$t = count($items);
			
			for ($i = 0; $i < $t; $i++) {
				$item = $items[$i];
				
				if ($item["layout_type_id"] == $data["layout_type_id"] && $item["permission_id"] == $data["permission_id"] && $item["object_type_id"] == $data["object_type_id"] && $item["object_id"] == $data["old_object_id"]) {
					$item["object_id"] = $data["new_object_id"];
					$item["modified_date"] = date("Y-m-d H:i:s");
					
					$items[$i] = $item;
					
					return $this->LocalDBTableHandler->writeTableItems($items, "layout_type_permission");
				}
			}
		}
		
		return false;
	}
	
	/**
	 * @param (name=data[root_path], type=varchar, not_null=1, min_length=1)
	 * @param (name=data[encryption_key], type=varchar, not_null=1, min_length=1)
	 * @param (name=data[old_object_prefix], type=varchar, not_null=1, min_length=1, max_length=255)
	 * @param (name=data[new_object_prefix], type=varchar, not_null=1, min_length=1, max_length=255)
	 */
	public function updateByObjectPrefix($data) {
		$this->initLocalDBTableHandler($data);
		
		$items = $this->getAll(array("root_path" => $data["root_path"], "encryption_key" => $data["encryption_key"]));
		
		if ($items) {
			$len = strlen($data["old_object_prefix"]);
			$t = count($items);
			$changed = false;
			
			for ($i = 0; $i < $t; $i++) {
				$item = $items[$i];
				$object_id = $item["object_id"];
				
				if (substr($object_id, 0, $len) == $data["old_object_prefix"]) {
					$item["object_id"] = $data["new_object_prefix"] . substr($object_id, $len);
					$item["modified_date"] = date("Y-m-d H:i:s");
					
					$items[$i] = $item;
					$changed = true;
				}
			}
			
			if ($changed) {
				//echo "<pre>";print_r($items);die();
				return $this->LocalDBTableHandler->writeTableItems($items, "layout_type_permission");
			}
		}
		
		return true;
	}
	
	/**
	 * @param (name=data[root_path], type=varchar, not_null=1, min_length=1)
	 * @param (name=data[encryption_key], type=varchar, not_null=1, min_length=1)
	 * @param (name=data[layout_type_id], type=bigint, not_null=1, length=19)
	 */
	public function updateByObjectsPermissions($data) {
		if ($data["layout_type_id"]) {
			$data_aux = $data;
			$data_aux["conditions"] = array("layout_type_id" => $data["layout_type_id"]);
			$this->deleteByConditions($data_aux);
			
			$items = $this->getAll(array("root_path" => $data["root_path"], "encryption_key" => $data["encryption_key"]));
			$date = date("Y-m-d H:i:s");
			
			if (isset($data["permissions_by_objects"]) && is_array($data["permissions_by_objects"]))
				foreach ($data["permissions_by_objects"] as $object_type_id => $permissions_by_object)
					foreach ($permissions_by_object as $object_id => $permission_ids) 
						if ($permission_ids) {
							$t = count($permission_ids);
							for ($i = 0; $i < $t; $i++) {
								$permission_id = $permission_ids[$i];
						
								if (is_numeric($permission_id)) {
									$items[] = array(
										"layout_type_id" => $data["layout_type_id"],
										"permission_id" => $permission_id,
										"object_type_id" => $object_type_id,
										"object_id" => $object_id,
										"created_date" => $date,
										"modified_date" => $date,
									);
								}
							}
						}
			
			//echo "<pre>";print_r($items);die();
			return $this->LocalDBTableHandler->writeTableItems($items, "layout_type_permission");
		}
		return false;
	}
	
	/**
	 * @param (name=data[root_path], type=varchar, not_null=1, min_length=1)
	 * @param (name=data[encryption_key], type=varchar, not_null=1, min_length=1)
	 * @param (name=data[layout_type_id], type=bigint, not_null=1, length=19)
	 * @param (name=data[permission_id], type=bigint, not_null=1, length=19)
	 * @param (name=data[object_type_id], type=bigint, not_null=1, length=19)
	 * @param (name=data[object_id], type=varchar, not_null=1, min_length=1, max_length=255)
	 */
	public function delete($data) {
		$this->initLocalDBTableHandler($data);
		
		return $this->LocalDBTableHandler->deleteItem("layout_type_permission", array("layout_type_id" => $data["layout_type_id"], "permission_id" => $data["permission_id"], "object_type_id" => $data["object_type_id"], "object_id" => $data["object_id"]));
	}
	
	/**
	 * @param (name=data[root_path], type=varchar, not_null=1, min_length=1)
	 * @param (name=data[encryption_key], type=varchar, not_null=1, min_length=1)
	 * @param (name=data[conditions][layout_type_id], type=bigint, length=19)
	 * @param (name=data[conditions][permission_id], type=bigint, length=19)
	 * @param (name=data[conditions][object_type_id], type=bigint, length=19)
	 * @param (name=data[conditions][object_id], type=varchar, length=255)
	 */
	public function deleteByConditions($data) {
		$this->initLocalDBTableHandler($data);
		
		$conditions = isset($data["conditions"]) ? $data["conditions"] : null;
		return $this->LocalDBTableHandler->deleteItem("layout_type_permission", $conditions);
	}
	
	/**
	 * @param (name=data[root_path], type=varchar, not_null=1, min_length=1)
	 * @param (name=data[encryption_key], type=varchar, not_null=1, min_length=1)
	 * @param (name=data[layout_type_id], type=bigint, not_null=1, length=19)
	 * @param (name=data[permission_id], type=bigint, not_null=1, length=19)
	 * @param (name=data[object_type_id], type=bigint, not_null=1, length=19)
	 * @param (name=data[object_id], type=varchar, not_null=1, min_length=1, max_length=255)
	 */
	public function get($data) {
		$this->initLocalDBTableHandler($data);
		
		$items = $this->LocalDBTableHandler->getItems("layout_type_permission");
		$new_items = $this->LocalDBTableHandler->filterItems($items, array("layout_type_id" => $data["layout_type_id"], "permission_id" => $data["permission_id"], "object_type_id" => $data["object_type_id"], "object_id" => $data["object_id"]), false, 1);
		return isset($new_items[0]) ? $new_items[0] : null;
	}
	
	/**
	 * @param (name=data[root_path], type=varchar, not_null=1, min_length=1)
	 * @param (name=data[encryption_key], type=varchar, not_null=1, min_length=1)
	 */
	public function getAll($data) {
		$this->initLocalDBTableHandler($data);
		
		return $this->LocalDBTableHandler->getItems("layout_type_permission");
	}
	
	/**
	 * @param (name=data[root_path], type=varchar, not_null=1, min_length=1)
	 * @param (name=data[encryption_key], type=varchar, not_null=1, min_length=1)
	 * @param (name=data[conditions][layout_type_id], type=bigint, length=19)
	 * @param (name=data[conditions][permission_id], type=bigint, length=19)
	 * @param (name=data[conditions][object_type_id], type=bigint, length=19)
	 * @param (name=data[conditions][object_id], type=varchar, length=255)
	 */
	public function search($data) {
		$this->initLocalDBTableHandler($data);
		
		$items = $this->LocalDBTableHandler->getItems("layout_type_permission");
		$conditions = isset($data["conditions"]) ? $data["conditions"] : null;
		return $this->LocalDBTableHandler->filterItems($items, $conditions, false);
	}
}
?>
