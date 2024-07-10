<?php
namespace __system\businesslogic;

include_once $vars["business_logic_modules_service_common_file_path"];
include_once get_lib("org.phpframework.db.DB");

class RemoteDBUserTypeService extends CommonService {
	
	public function dropAndCreateTable($data) {
		$options = isset($data["options"]) ? $data["options"] : null;
		
		$table_data = array(
			"table_name" => "sysauth_user_type",
			"attributes" => array(
				array(
					"name" => "user_type_id",
					"type" => "bigint",
					"primary_key" => 1,
					"auto_increment" => 1,
				),
				array(
					"name" => "name",
					"type" => "varchar",
					"length" => 50,
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
	 * @param (name=data[name], type=varchar, not_null=1, min_length=1, max_length=50)
	 */
	public function insert($data) {
		$options = isset($data["options"]) ? $data["options"] : null;
		
		$data["name"] = addcslashes($data["name"], "\\'");
		$data["created_date"] = !empty($data["created_date"]) ? $data["created_date"] : date("Y-m-d H:i:s");//leave this check because of the transport data between dbs
		$data["modified_date"] = !empty($data["modified_date"]) ? $data["modified_date"] : $data["created_date"];
		
		if (empty($data["user_type_id"]))
			$data["user_type_id"] = "DEFAULT";
		
		$status = $this->getBroker()->callInsert("auth", "insert_user_type", $data, $options);
		return $status ? ($data["user_type_id"] == "DEFAULT" ? $this->getBroker()->getInsertedId($options) : $data["user_type_id"]) : $status;
	}
	
	/**
	 * @param (name=data[user_type_id], type=bigint, not_null=1, length=19)
	 * @param (name=data[name], type=varchar, not_null=1, min_length=1, max_length=50)
	 */
	public function update($data) {
		$options = isset($data["options"]) ? $data["options"] : null;
		
		$data["name"] = addcslashes($data["name"], "\\'");
		$data["modified_date"] = date("Y-m-d H:i:s");
		
		return $this->getBroker()->callUpdate("auth", "update_user_type", $data, $options);
	}
	
	/**
	 * @param (name=data[user_type_id], type=bigint, not_null=1, length=19)
	 */
	public function delete($data) {
		$user_type_id = $data["user_type_id"];
		$options = isset($data["options"]) ? $data["options"] : null;
		
		return $this->getBroker()->callDelete("auth", "delete_user_type", array("user_type_id" => $user_type_id), $options);
	}
	
	/**
	 * @param (name=data[user_type_id], type=bigint, not_null=1, length=19)
	 */
	public function get($data) {
		$user_type_id = $data["user_type_id"];
		$options = isset($data["options"]) ? $data["options"] : null;
		
		$result = $this->getBroker()->callSelect("auth", "get_user_type", array("user_type_id" => $user_type_id), $options);
		return isset($result[0]) ? $result[0] : null;
	}
	
	public function getAll($data) {
		$options = isset($data["options"]) ? $data["options"] : null;
		
		return $this->getBroker()->callSelect("auth", "get_all_user_types", null, $options);
	}
	
	/**
	 * @param (name=data[conditions][user_type_id], type=bigint, length=19)
	 * @param (name=data[conditions][name], type=varchar, length=50)
	 */
	public function search($data) {
		$conditions = isset($data["conditions"]) ? $data["conditions"] : null;
		$conditions_join = isset($data["conditions_join"]) ? $data["conditions_join"] : null;
		$options = isset($data["options"]) ? $data["options"] : null;
		
		$cond = \DB::getSQLConditions($conditions, $conditions_join);
		return $cond ? $this->getBroker()->callSelect("auth", "get_user_types_by_conditions", array("conditions" => $cond), $options) : null;
	}
}
?>
