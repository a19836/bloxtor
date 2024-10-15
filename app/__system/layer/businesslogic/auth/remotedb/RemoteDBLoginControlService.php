<?php
namespace __system\businesslogic;

include_once $vars["business_logic_modules_service_common_file_path"];

class RemoteDBLoginControlService extends CommonService {
	
	public function dropAndCreateTable($data) {
		$options = isset($data["options"]) ? $data["options"] : null;
		
		$table_data = array(
			"table_name" => "sysauth_login_control",
			"attributes" => array(
				array(
					"name" => "username",
					"type" => "varchar",
					"length" => 50,
					"primary_key" => 1,
				),
				array(
					"name" => "session_id",
					"type" => "varchar",
					"length" => 200,
				),
				array(
					"name" => "failed_login_attempts",
					"type" => "smallint",
					"default" => "0",
				),
				array(
					"name" => "failed_login_time",
					"type" => "bigint",
					"default" => "0",
				),
				array(
					"name" => "login_expired_time",
					"type" => "bigint",
					"default" => "0",
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
	 */
	public function insert($data) {
		$options = isset($data["options"]) ? $data["options"] : null;
		
		$old_item = $this->get($data);
		
		if (empty($data["login_expired_time"]))
			$data["login_expired_time"] = time() + 3600;//60 * 60 = 3600 secs = 1 hour
		
		if (empty($data["session_id"]))
			$data["session_id"] = \CryptoKeyHandler::getHexKey();
		
		$data["username"] = addcslashes($data["username"], "\\'");
		$data["failed_login_attempts"] = 0;
		$data["failed_login_time"] = 0;
		$data["created_date"] = !empty($data["created_date"]) ? $data["created_date"] : date("Y-m-d H:i:s");//leave this check because of the transport data between dbs
		$data["modified_date"] = !empty($data["modified_date"]) ? $data["modified_date"] : $data["created_date"];
		
		if ($old_item)
			$status = $this->getBroker()->callUpdate("auth", "update_login_control", $data, $options);
		else
			$status = $this->getBroker()->callInsert("auth", "insert_login_control", $data, $options);
		
		return $status ? $data["session_id"] : null;
	}
	
	/**
	 * @param (name=data[username], type=varchar, not_null=1, min_length=1, max_length=50)
	 */
	public function insertFailedLoginAttempt($data) {
		$options = isset($data["options"]) ? $data["options"] : null;
		
		$failed_attempts = $this->getFailedLoginAttempts($data);
		$old_item = $this->get($data);
		
		$date = date("Y-m-d H:i:s");
		
		$item = array(
			"username" => addcslashes($data["username"], "\\'"),
			"session_id" => $old_item["session_id"],
			"failed_login_attempts" => $failed_attempts + 1,
			"failed_login_time" => time(),
			"login_expired_time" => 0,
			"created_date" => !empty($old_item["created_date"]) ? $old_item["created_date"] : $date,
			"modified_date" => !empty($old_item["modified_date"]) ? $old_item["modified_date"] : $date,
		);
		
		if ($old_item)
			return $this->getBroker()->callUpdate("auth", "update_login_control", $item, $options);
		
		return $this->getBroker()->callInsert("auth", "insert_login_control", $item, $options);
	}
	
	/**
	 * @param (name=data[session_id], type=varchar, not_null=1, min_length=1, max_length=200)
	 */
	public function expireSession($data) {
		$options = isset($data["options"]) ? $data["options"] : null;
		
		$item = $this->getBySessionId($data);
		
		if ($item && !empty($item["session_id"]) && !empty($item["username"])) {
			$item["login_expired_time"] = time() - 1;
			$item["modified_date"] = date("Y-m-d H:i:s");
			
			return $this->getBroker()->callUpdate("auth", "update_login_control", $item, $options);
		}
		
		return false;
	}
	
	/**
	 * @param (name=data[username], type=varchar, not_null=1, min_length=1, max_length=50)
	 */
	public function resetFailedLoginAttempts($data) {
		$options = isset($data["options"]) ? $data["options"] : null;
		
		$item = $this->get($data);
		
		if ($item) {
			$item["failed_login_attempts"] = 0;
			$item["failed_login_time"] = 0;
			$item["modified_date"] = date("Y-m-d H:i:s");
			
			return $this->getBroker()->callUpdate("auth", "update_login_control", $item, $options);
		}
		return true;
	}
	
	/**
	 * @param (name=data[username], type=varchar, not_null=1, min_length=1, max_length=50)
	 */
	public function delete($data) {
		$username = addcslashes($data["username"], "\\'");
		$options = isset($data["options"]) ? $data["options"] : null;
		
		return $this->getBroker()->callDelete("auth", "delete_login_control", array("username" => $username), $options);
	}
	
	/**
	 * @param (name=data[username], type=varchar, not_null=1, min_length=1, max_length=50)
	 */
	public function getFailedLoginAttempts($data) {
		$item = $this->get($data);
		return $item && !empty($item["username"]) && isset($item["failed_login_attempts"]) ? $item["failed_login_attempts"] : null;
	}
	
	/**
	 * @param (name=data[username], type=varchar, not_null=1, min_length=1, max_length=50)
	 */
	public function get($data) {
		$username = addcslashes($data["username"], "\\'");
		$options = isset($data["options"]) ? $data["options"] : null;
		
		$items = $this->getBroker()->callSelect("auth", "get_login_control", array("username" => $username), $options);
		return isset($items[0]) ? $items[0] : null;
	}
	
	/**
	 * @param (name=data[session_id], type=varchar, not_null=1, min_length=1, max_length=200)
	 */
	public function getBySessionId($data) {
		$session_id = addcslashes($data["session_id"], "\\'");
		$options = isset($data["options"]) ? $data["options"] : null;
		
		$items = $this->getBroker()->callSelect("auth", "get_login_control_by_session_id", array("session_id" => $session_id), $options);
		return isset($items[0]) ? $items[0] : null;
	}
	
	/**
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
	
	public function getAll($data) {
		$options = isset($data["options"]) ? $data["options"] : null;
		
		return $this->getBroker()->callSelect("auth", "get_all_login_controls", null, $options);
	}
}
?>
