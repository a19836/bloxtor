<?php
include_once get_lib("org.phpframework.encryption.CryptoKeyHandler"); //used in this file and by other files that use this file.
include_once get_lib("org.phpframework.util.web.MyCurl");
include_once get_lib("org.phpframework.util.web.CSRFValidator");
include_once get_lib("org.phpframework.util.web.CookieHandler");
include_once $EVC->getUtilPath("PHPVariablesFileHandler");

class UserAuthenticationHandler {
	private $EVC;
	private $root_path;
	private $login_page_url;
	private $non_authorized_page_url;
	
	private $permission_table_encryption_key;
	private $user_table_encryption_key;
	private $user_type_table_encryption_key;
	private $object_type_table_encryption_key;
	private $user_type_permission_table_encryption_key;
	private $user_user_type_table_encryption_key;
	private $login_control_table_encryption_key;
	private $user_stats_table_encryption_key;
	private $layout_type_table_encryption_key;
	private $layout_type_permission_table_encryption_key;
	private $reserved_db_table_name_table_encryption_key;
	
	private $available_permissions;
	private $available_user_types;
	private $available_object_types;
	private $available_users;
	private $available_layout_types;
	
	private $user_type_permissions;
	private $user_type_permissions_by_object;
	private $current_object_type_id;
	private $current_object_id;
	private $layouts_type_permissions;
	private $layouts_type_permissions_by_object;
	
	private $maximum_failed_attempts = 3;
	private $user_blocked_expired_time = 3600; //60 * 60 = 3600 secs = 1 hour
	private $login_expired_time = 86400; //60 * 60 * 24 = 86400 secs = 1 day
	private $user_session_control_expired_time = 1800; //30 * 60 = 1800 secs = 30 minutes
	private $user_session_control_methods = array("post"); //POST bc it was high probable that was presented a form to the user in the previous request.
	private $is_local_db = true;
	
	public $auth;
	public static $USER_SESSION_ID_VARIABLE_NAME = "system_session_id";
	public static $USER_SESSION_CONTROL_VARIABLE_NAME = "system_session_control";
	public static $URL_BACK_VARIABLE_NAME = "system_url_back";
	
	public static $AVAILABLE_LAYOUTS_TYPES = array(0 => "From Project");
	public static $LAYOUTS_TYPE_FROM_PROJECT_ID = 0;
	public static $PERMISSION_BELONG_NAME = "belong";
	public static $PERMISSION_REFERENCED_NAME = "referenced";
	
	private $valid_urls_back = null;
	
	public function __construct($EVC, $root_path, $login_page_url, $non_authorized_page_url) {
		$this->EVC = $EVC;
		$this->root_path = $root_path;
		$this->login_page_url = $login_page_url;
		$this->non_authorized_page_url = $non_authorized_page_url;
		
		$this->valid_urls_back = self::getValidUrlsBack();
	}
	
	//public function setEncryptionKeys($permission_table_encryption_key, $user_table_encryption_key, $user_type_table_encryption_key, $object_type_table_encryption_key, $user_type_permission_table_encryption_key, $user_user_type_table_encryption_key, $login_control_table_encryption_key, $user_stats_table_encryption_key) {
	public function setEncryptionKeys($keys) {
		$this->permission_table_encryption_key = isset($keys["permission_table_encryption_key"]) ? $keys["permission_table_encryption_key"] : null;
		$this->user_table_encryption_key = isset($keys["user_table_encryption_key"]) ? $keys["user_table_encryption_key"] : null;
		$this->user_type_table_encryption_key = isset($keys["user_type_table_encryption_key"]) ? $keys["user_type_table_encryption_key"] : null;
		$this->object_type_table_encryption_key = isset($keys["object_type_table_encryption_key"]) ? $keys["object_type_table_encryption_key"] : null;
		$this->user_type_permission_table_encryption_key = isset($keys["user_type_permission_table_encryption_key"]) ? $keys["user_type_permission_table_encryption_key"] : null;
		$this->user_user_type_table_encryption_key = isset($keys["user_user_type_table_encryption_key"]) ? $keys["user_user_type_table_encryption_key"] : null;
		$this->login_control_table_encryption_key = isset($keys["login_control_table_encryption_key"]) ? $keys["login_control_table_encryption_key"] : null;
		$this->user_stats_table_encryption_key = isset($keys["user_stats_table_encryption_key"]) ? $keys["user_stats_table_encryption_key"] : null;
		$this->layout_type_table_encryption_key = isset($keys["layout_type_table_encryption_key"]) ? $keys["layout_type_table_encryption_key"] : null;
		$this->layout_type_permission_table_encryption_key = isset($keys["layout_type_permission_table_encryption_key"]) ? $keys["layout_type_permission_table_encryption_key"] : null;
		$this->reserved_db_table_name_table_encryption_key = isset($keys["reserved_db_table_name_table_encryption_key"]) ? $keys["reserved_db_table_name_table_encryption_key"] : null;
	}
	
	public function setAuthSettings($maximum_failed_attempts, $user_blocked_expired_time, $login_expired_time, $is_local_db) {
		if (is_numeric($maximum_failed_attempts)) 
			$this->maximum_failed_attempts = $maximum_failed_attempts;
		
		if (is_numeric($user_blocked_expired_time))
			$this->user_blocked_expired_time = $user_blocked_expired_time;
		
		if (is_numeric($login_expired_time))
			$this->login_expired_time = $login_expired_time;
		
		if (isset($is_local_db))
			$this->is_local_db = $is_local_db ? true : false;
	}
	
	public function isLocalDB() {
		return $this->is_local_db;
	}
	
	/* GENERIC FUNCTIONS */
	
	public function getURLContent($url, $post_data = null, $connection_timeout = 0) { //in seconds
		$url_host = parse_url($url, PHP_URL_HOST);
		$current_host = explode(":", isset($_SERVER["HTTP_HOST"]) ? $_SERVER["HTTP_HOST"] : null); //maybe it contains the port
		$current_host = isset($current_host[0]) ? $current_host[0] : null;
		
		$settings = array(
			"url" => $url, 
			"post" => $post_data, 
			"cookie" => $current_host == $url_host ? $_COOKIE : null,
			"settings" => array(
				"referer" => isset($_SERVER["HTTP_REFERER"]) ? $_SERVER["HTTP_REFERER"] : null,
				"follow_location" => 1,
				"connection_timeout" => $connection_timeout,
			)
		);
		
		if (!empty($_SERVER["AUTH_TYPE"]) && !empty($_SERVER["PHP_AUTH_USER"])) {
			$settings["settings"]["http_auth"] = $_SERVER["AUTH_TYPE"];
			$settings["settings"]["user_pwd"] = $_SERVER["PHP_AUTH_USER"] . ":" . (isset($_SERVER["PHP_AUTH_PW"]) ? $_SERVER["PHP_AUTH_PW"] : null);
		}
		
		$MyCurl = new MyCurl();
		$MyCurl->initSingle($settings);
		$MyCurl->get_contents();
		$content = $MyCurl->getData();
		
		return isset($content[0]["content"]) ? $content[0]["content"] : null;
	}
	
	/* MOVE DB FUNCTIONS */
	
	public function moveLocalDBToAnotherFolder($new_folder_path) {
		$status = true;
		
		if ($new_folder_path && $new_folder_path != $this->root_path) {
			if (!is_dir($new_folder_path)) 
				@mkdir($new_folder_path, 0755, true);
			
			if (!is_dir($new_folder_path))
				return false;
			
			$files = scandir($this->root_path);
			
			foreach ($files as $file) 
				if (!is_dir($this->root_path . "/$file") && $file != "." && $file != "..")
					if (!rename($this->root_path . "/$file", $new_folder_path . "/$file"))
						$status = false;
		}
		
		return $status;
	}
	
	//change the local db to MYSQL or MYSQL to Local DB
	public function moveLocalDBToRemoteDBOrViceVersa($is_local_db, $global_variables) {
		$status = true;
		
		$is_local_db = $is_local_db ? true : false;
		$update_db = $this->is_local_db != $is_local_db; //if was local and now is remote, or vice versa
		$is_different_db = false;
		
		if (!$update_db && !$is_local_db) { //if was remote db and now continues to be remote, but is another db
			$prefix = isset($GLOBALS["default_db_driver"]) ? $GLOBALS["default_db_driver"] : null;
			
			$old_db_credentials = $prefix . (isset($GLOBALS[$prefix . "_db_host"]) ? $GLOBALS[$prefix . "_db_host"] : null) . (isset($GLOBALS[$prefix . "_db_name"]) ? $GLOBALS[$prefix . "_db_name"] : null) . (isset($GLOBALS[$prefix . "_db_encoding"]) ? $GLOBALS[$prefix . "_db_encoding"] : null) . (isset($GLOBALS[$prefix . "_db_odbc_data_source"]) ? $GLOBALS[$prefix . "_db_odbc_data_source"] : null);
			
			$prefix = isset($global_variables["default_db_driver"]) ? $global_variables["default_db_driver"] : null;
			$new_db_credentials = $prefix . (isset($global_variables[$prefix . "_db_host"]) ? $global_variables[$prefix . "_db_host"] : null) . (isset($global_variables[$prefix . "_db_name"]) ? $global_variables[$prefix . "_db_name"] : null) . (isset($global_variables[$prefix . "_db_encoding"]) ? $global_variables[$prefix . "_db_encoding"] : null) . (isset($global_variables[$prefix . "_db_odbc_data_source"]) ? $global_variables[$prefix . "_db_odbc_data_source"] : null);
			
			$is_different_db = $old_db_credentials != $new_db_credentials;
		}
		
		if ($update_db || $is_different_db) {
			$db_data = $this->getAllDBData();
			
			$this->is_local_db = $is_local_db;
			
			$PHPVariablesFileHandler = new PHPVariablesFileHandler(GLOBAL_VARIABLES_PROPERTIES_FILE_PATH);
			$PHPVariablesFileHandler->startUserGlobalVariables();
			
			if ($is_different_db) {
				//get current bean objects to be update
				$PHPFrameWork = $this->EVC->getPresentationLayer()->getPHPFrameWork();
				$objs = $PHPFrameWork->getObjects();
				
				//change default db driver
				$default_db_driver = isset($GLOBALS["default_db_driver"]) ? $GLOBALS["default_db_driver"] : null;
				$PHPFrameWork->getObject("DBLayer")->setDefaultBrokerName($default_db_driver);
				
				//update db driver options with new options
				foreach ($objs as $obj_name => $obj) {
					if (is_a($obj, "DB") && $obj->getType() == $default_db_driver) {
						$db_options = $obj->getOptions();
						
						foreach ($db_options as $opt_name => $opt_value) {
							$name_to_check = $default_db_driver . "_" . (substr($opt_name, 0, 3) == "db_" ? "" : "db_") . $opt_name;
							
							if (isset($GLOBALS[$name_to_check]))
								$db_options[$opt_name] = $GLOBALS[$name_to_check];
						}
						
						$obj->setOptions($db_options, true);
					}
				}
			}
			
			$status = $this->insertRemoteDBSchema();
			
			if ($status) {
				//get sysauth tables names added recently
				$new_reserved_db_table_names = $this->getAllReservedDBTableNames();
				
				//clean table with no records	so we can insert the previous ones saved in $db_data
				if ($new_reserved_db_table_names) 
					foreach ($new_reserved_db_table_names as $item) {
						$reserved_db_table_name_id = isset($item["reserved_db_table_name_id"]) ? $item["reserved_db_table_name_id"] : null;
						
						if (!$this->deleteReservedDBTableName($reserved_db_table_name_id))
							$status = false;
					}
					
				if ($status) {
					//insert all data into sysauth tables
					$status = $this->insertRemoteDBData($db_data);
					
					//add new sysauth tables names in new_reserved_db_table_names
					if ($status && $new_reserved_db_table_names) 
						foreach ($new_reserved_db_table_names as $item)
							if (!$this->insertReservedDBTableNameIfNotExistsYet(array(
								"name" => isset($item["name"]) ? $item["name"] : null
							)))
								$status = false;
				}
			}
			
			$PHPVariablesFileHandler->endUserGlobalVariables();
		}
		
		return $status;
	}
	
	private function getAllDBData() {
		$login_controls = $this->getAllLoginControls();
		$permissions = $this->getAllPermissions();
		$users = $this->getAllUsers();
		$user_types = $this->getAllUserTypes();
		$object_types = $this->getAllObjectTypes();
		$user_type_permissions = $this->getAllUserTypePermissions();
		$user_user_types = $this->getAllUserUserTypes();
		$user_stats = $this->getAllUserStats();
		$layout_types = $this->getAllLayoutTypes();
		$layout_type_permissions = $this->getAllLayoutTypePermissions();
		$reserved_db_table_names = $this->getAllReservedDBTableNames();
		
		return array(
			"login_controls" => $login_controls,
			"permissions" => $permissions,
			"users" => $users,
			"user_types" => $user_types,
			"object_types" => $object_types,
			"user_type_permissions" => $user_type_permissions,
			"user_user_types" => $user_user_types,
			"user_stats" => $user_stats,
			"layout_types" => $layout_types,
			"layout_type_permissions" => $layout_type_permissions,
			"reserved_db_table_names" => $reserved_db_table_names,
		);
	}
	
	private function insertRemoteDBSchema() {
		//Note that the dropAndCreateReservedDBTableNameTable must be the first one, bc all the dropAndCreate methods call the RemoteDBReservedDBTableNameService.insertIfNotExistsYet service and so the reserved_db_table_name table must exist before this call.
		return $this->dropAndCreateReservedDBTableNameTable() && $this->dropAndCreateLoginControlTable() && $this->dropAndCreatePermissionTable() && $this->dropAndCreateUserTable() && $this->dropAndCreateUserTypeTable() && $this->dropAndCreateObjectTypeTable() && $this->dropAndCreateUserTypePermissionTable() && $this->dropAndCreateUserUserTypeTable() && $this->dropAndCreateUserStatsTable() && $this->dropAndCreateLayoutTypeTable() && $this->dropAndCreateLayoutTypePermissionTable();
	}
	
	private function insertRemoteDBData($db_data) {
		$status = true;
		
		$login_controls = isset($db_data["login_controls"]) ? $db_data["login_controls"] : null;
		$permissions = isset($db_data["permissions"]) ? $db_data["permissions"] : null;
		$users = isset($db_data["users"]) ? $db_data["users"] : null;
		$user_types = isset($db_data["user_types"]) ? $db_data["user_types"] : null;
		$object_types = isset($db_data["object_types"]) ? $db_data["object_types"] : null;
		$user_type_permissions = isset($db_data["user_type_permissions"]) ? $db_data["user_type_permissions"] : null;
		$user_user_types = isset($db_data["user_user_types"]) ? $db_data["user_user_types"] : null;
		$user_stats = isset($db_data["user_stats"]) ? $db_data["user_stats"] : null;
		$layout_types = isset($db_data["layout_types"]) ? $db_data["layout_types"] : null;
		$layout_type_permissions = isset($db_data["layout_type_permissions"]) ? $db_data["layout_type_permissions"] : null;
		$reserved_db_table_names = isset($db_data["reserved_db_table_names"]) ? $db_data["reserved_db_table_names"] : null;
		
		$t = $login_controls ? count($login_controls) : 0;
		for ($i = 0; $i < $t; $i++) 
			if (!$this->insertLoginControl( $login_controls[$i] ))
				$status = false;
		
		$t = $permissions ? count($permissions) : 0;
		for ($i = 0; $i < $t; $i++)
			if (!$this->insertPermission( $permissions[$i] ))
				$status = false;
		
		$t = $users ? count($users) : 0;
		for ($i = 0; $i < $t; $i++) {
			$users[$i]["options"]["raw_password"] = true;
			
			if (!$this->insertUser( $users[$i] ))
				$status = false;
		}
		
		$t = $user_types ? count($user_types) : 0;
		for ($i = 0; $i < $t; $i++)
			if (!$this->insertUserType( $user_types[$i] ))
				$status = false;
		
		$t = $object_types ? count($object_types) : 0;
		for ($i = 0; $i < $t; $i++)
			if (!$this->insertObjectType( $object_types[$i] ))
				$status = false;
		
		$t = $user_type_permissions ? count($user_type_permissions) : 0;
		for ($i = 0; $i < $t; $i++)
			if (!$this->insertUserTypePermission( $user_type_permissions[$i] ))
				$status = false;
		
		$t = $user_user_types ? count($user_user_types) : 0;
		for ($i = 0; $i < $t; $i++)
			if (!$this->insertUserUserType( $user_user_types[$i] ))
				$status = false;
		
		$t = $user_stats ? count($user_stats) : 0;
		for ($i = 0; $i < $t; $i++)
			if (!$this->insertUserStat( $user_stats[$i] ))
				$status = false;
		
		$t = $layout_types ? count($layout_types) : 0;
		for ($i = 0; $i < $t; $i++)
			if (!$this->insertLayoutType( $layout_types[$i] ))
				$status = false;
		
		$t = $layout_type_permissions ? count($layout_type_permissions) : 0;
		for ($i = 0; $i < $t; $i++)
			if (!$this->insertLayoutTypePermission( $layout_type_permissions[$i] ))
				$status = false;
		
		$t = $reserved_db_table_names ? count($reserved_db_table_names) : 0;
		for ($i = 0; $i < $t; $i++)
			if (!$this->insertReservedDBTableName( $reserved_db_table_names[$i] ))
				$status = false;
		
		return $status;
	}
	
	/* AUTH FUNCTIONS */
	
	public function login($username, $password) {
		$this->auth = false;
		
		$user_data = $this->getUserByUsernameAndPassword($username, $password);
		
		if ($user_data && !empty($user_data["username"])) {
			$session_id = isset($_COOKIE[ self::$USER_SESSION_ID_VARIABLE_NAME ]) ? $_COOKIE[ self::$USER_SESSION_ID_VARIABLE_NAME ] : null;
			$login_expired_time = time() + $this->login_expired_time;
			
			//if already exists a session with another username, reset $session_id so the system can create new one.
			if ($session_id) {
				$login_data = $this->getLoginControlBySessionId($session_id);
				
				if ($login_data && isset($login_data["username"]) && $login_data["username"] != $user_data["username"])
					$session_id = null;
			}
			
			$data = array(
				"username" => $username, 
				"login_expired_time" => $login_expired_time, 
				"session_id" => $session_id
			);
			$session_id = $this->insertLoginControl($data);
			
			if ($session_id) {
				unset($user_data["password"]);
				
				$this->auth = array(
					"login_expired_time" => $login_expired_time,
					"user_data" => $user_data,
				);
				
				$extra_flags = CSRFValidator::$COOKIES_EXTRA_FLAGS;
				CookieHandler::setSafeCookie(self::$USER_SESSION_ID_VARIABLE_NAME, $session_id, 0, "/", $extra_flags);
				
				//add session control bc of xss and csfr attacks
				$ttl = CryptoKeyHandler::encryptText( time() + $this->user_session_control_expired_time, $this->login_control_table_encryption_key );
				$ttl = CryptoKeyHandler::binToHex($ttl);
				CookieHandler::setSafeCookie(self::$USER_SESSION_CONTROL_VARIABLE_NAME, $ttl, 0, "/", $extra_flags);
				
				//reload permissions
				$this->loadLoggedUserPermissions();
				
				return true;
			}
		}
	}
	
	public function logout() {
		$session_id = isset($_COOKIE[ self::$USER_SESSION_ID_VARIABLE_NAME ]) ? $_COOKIE[ self::$USER_SESSION_ID_VARIABLE_NAME ] : null;
		
		if ($session_id)
			$this->expireSession($session_id);
		
		unset($_COOKIE[ self::$USER_SESSION_ID_VARIABLE_NAME ]);
		CookieHandler::setCurrentDomainEternalRootSafeCookie(self::$USER_SESSION_ID_VARIABLE_NAME, "", -1);
		
		unset($_COOKIE[ self::$URL_BACK_VARIABLE_NAME ]);
		CookieHandler::setCurrentDomainEternalRootSafeCookie(self::$URL_BACK_VARIABLE_NAME, "", -1);
		
		return true;
	}
	
	public function getUrlBack() {
		$url_back = isset($_COOKIE[ self::$URL_BACK_VARIABLE_NAME ]) ? $_COOKIE[ self::$URL_BACK_VARIABLE_NAME ] : null;
		
		if ($url_back)
			$url_back = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443 ? "https://" : "http://") . (isset($_SERVER["HTTP_HOST"]) ? $_SERVER["HTTP_HOST"] : "") . $url_back;
		
		return $url_back;
	}
	
	public function validateUrlBack($url_back) {
		if ($url_back) {
			if (empty($this->valid_urls_back))
				return true;
			
			$url_path = parse_url($url_back, PHP_URL_PATH);
			$url_path = preg_replace("/\/+/", "/", $url_path);
			$url_path = preg_replace("/\/+$/", "", $url_path);
			$url_path = preg_replace("/\/index$/", "/", $url_path);
			
			if (in_array($url_path, $this->valid_urls_back))
				return true;
			
			//echo $url_path;die();
			foreach ($this->valid_urls_back as $valid_url_back)
				if (preg_match("|$valid_url_back$|i", $url_path))
					return true;
		}
		
		return false;
	}
	
	public function checkPresentationFileAuthentication($file_path, $permissions) {
		return $this->checkFileAuthentication($file_path, "page", $permissions);
	}
	
	private function checkFileAuthentication($file_path, $file_type, $permissions) {
		$available_object_types = $this->getAvailableObjectTypes();
		$object_type_id = isset($available_object_types[$file_type]) ? $available_object_types[$file_type] : null;
		
		$object_id = str_replace(APP_PATH, "", $file_path);
		$object_id = substr($object_id, 0, 1) == "/" ? substr($object_id, 1) : $object_id;
		$object_id = str_replace("//", "/", $object_id);
		
		return $this->checkAuthentication($object_type_id, $object_id, $permissions);
	}
	
	private function checkAuthentication($object_type_id, $object_id, $permissions) {
		unset($_COOKIE[ self::$URL_BACK_VARIABLE_NAME ]);
		CookieHandler::setCurrentDomainEternalRootSafeCookie(self::$URL_BACK_VARIABLE_NAME, "", -1);
		
		$this->current_object_type_id = $object_type_id;
		$this->current_object_id = $object_id;
		
		if ($this->checkIfUserIsLoggedIn()) {
			$permissions = is_array($permissions) ? $permissions : array($permissions);
			
			foreach ($permissions as $permission)
				if (!$this->isCurrentPagePermissionAllowed($permission))
					$this->redirect($this->non_authorized_page_url);
		}
		else {
			$this->logout();
			$this->redirect($this->login_page_url);
		}
	}
	
	public function checkInnerFilePermissionAuthentication($file_path, $file_type, $permission, $omitted_permission_return = true) {
		unset($_COOKIE[ self::$URL_BACK_VARIABLE_NAME ]);
		CookieHandler::setCurrentDomainEternalRootSafeCookie(self::$URL_BACK_VARIABLE_NAME, "", -1);
		
		if ($this->checkIfUserIsLoggedIn()) {
			if (!$this->isInnerFilePermissionAllowed($file_path, $file_type, $permission, $omitted_permission_return))
				$this->redirect($this->non_authorized_page_url);
		}
		else {
			$this->logout();
			$this->redirect($this->login_page_url);
		}
	}
	
	public function isCurrentPagePermissionAllowed($permission) {
		return $this->isObjectPermissionAllowed($this->current_object_type_id, $this->current_object_id, $permission);
	}
	
	public function isPresentationFilePermissionAllowed($file_path, $permission) {
		return $this->isFilePermissionAllowed($file_path, "page", $permission);
	}
	
	public function isFilePermissionAllowed($file_path, $file_type, $permission) {
		$available_object_types = $this->getAvailableObjectTypes();
		$object_type_id = isset($available_object_types[$file_type]) ? $available_object_types[$file_type] : null;
		
		$object_id = str_replace(APP_PATH, "", $file_path);
		$object_id = substr($object_id, 0, 1) == "/" ? substr($object_id, 1) : $object_id;
		$object_id = str_replace("//", "/", $object_id);
		//echo "object_id:$object_id<br>\n";
		
		return $this->isObjectPermissionAllowed($object_type_id, $object_id, $permission);
	}
	
	//Check if the $file_path is inside of any of permited folders
	public function isInnerFilePermissionAllowed($file_path, $file_type, $permission, $omitted_permission_return = true) {
		$available_object_types = $this->getAvailableObjectTypes();
		$object_type_id = isset($available_object_types[$file_type]) ? $available_object_types[$file_type] : null;
		
		$objects_permissions = $object_type_id && isset($this->user_type_permissions_by_object[$object_type_id]) ? $this->user_type_permissions_by_object[$object_type_id] : null;
		
		if ($objects_permissions) {
			if (substr(strtolower($file_path), 0, strlen(APP_PATH)) == strtolower(APP_PATH))
				$object_id = substr($file_path, strlen(APP_PATH));
			else
				$object_id = $file_path;
			
			$object_id = substr($object_id, 0, 1) == "/" ? substr($object_id, 1) : $object_id;
			$object_id .= substr($object_id, -1) != "/" ? "/" : "";
			$object_id = preg_replace("/\/+/", "/", $object_id);
			
			$selected_obj_id = "";
			
			foreach ($objects_permissions as $obj_id => $permissions) {
				$oid = $obj_id . (substr($obj_id, -1) != "/" ? "/" : "");
				
				if (strlen($object_id) >= strlen($oid) && substr($object_id, 0, strlen($oid)) == $oid && strlen($selected_obj_id) < strlen($obj_id))
					$selected_obj_id = $obj_id;
			}
			//echo "<pre>";print_r($objects_permissions);
			//echo "object_id:$object_id<br>\n";
			//echo "selected_obj_id:$selected_obj_id<br>\n";
			
			if ($selected_obj_id) {
				//echo "$object_id|||$selected_obj_id|||".strlen($object_id)." >= ".(strlen($selected_obj_id)+1)."\n";die();
				//echo "object_id:$object_id:".print_r($objects_permissions[$selected_obj_id], 1)."<br>\n";
				
				return $this->isObjectPermissionAllowed($object_type_id, $selected_obj_id, $permission);
			}
			
			return $omitted_permission_return;
		}
		//echo"$file_path<br>";die();
		
		return false;
	}
	
	private function isObjectPermissionAllowed($object_type_id, $object_id, $permission) {
		$available_permissions = $this->getAvailablePermissions();
		$permission_id = isset($available_permissions[strtolower($permission)]) ? $available_permissions[strtolower($permission)] : null;
		$current_object_permissions = $object_type_id && $object_id && isset($this->user_type_permissions_by_object[$object_type_id][$object_id]) ? $this->user_type_permissions_by_object[$object_type_id][$object_id] : null;
		//echo "object_id:$object_id:";print_r($current_object_permissions);echo "<br>";
		
		return is_array($current_object_permissions) ? in_array($permission_id, $current_object_permissions) : false;
	}
	
	//Check if the $file_path is inside of any of permited folders
	//$permission could be an array with permissions
	//$include_children_access is used in the admin/manage_file.php to check if the files can be created inside of a folder that doesn't belong to a project, but has children that belong to it.
	public function isLayoutInnerFilePermissionAllowed($file_path, $layout, $file_type, $permission, $omitted_permission_return = false, $include_children_access = true) { 
		$available_object_types = $this->getAvailableObjectTypes();
		$object_type_id = isset($available_object_types[$file_type]) ? $available_object_types[$file_type] : null;
		
		$available_permissions = $this->getAvailablePermissions();
		
		if (is_array($permission)) {
			$permission_id = array();
			
			foreach ($permission as $p)
				$permission_id[] = isset($available_permissions[strtolower($p)]) ? $available_permissions[strtolower($p)] : null;
		}
		else
			$permission_id = isset($available_permissions[strtolower($permission)]) ? $available_permissions[strtolower($permission)] : null;
		
		$layout = preg_replace("/\/+/", "/", $layout); //replace multiple slashes
		$objects_permissions = $layout && $object_type_id && isset($this->layouts_type_permissions_by_object[$layout][$object_type_id]) ? $this->layouts_type_permissions_by_object[$layout][$object_type_id] : null;
		
		/*echo "file_path:$file_path<br>\n";
		echo "layout:$layout<br>\n";
		echo "object_type_id ($file_type):$object_type_id<br>\n";
		echo "permission_id ($permission):$permission_id".print_r($permission_id, 1)."<br>\n";
		echo "objects_permissions:".print_r($objects_permissions, 1)."<br>\n";
		echo "keys layouts_type_permissions_by_object:".print_r(array_keys($this->layouts_type_permissions_by_object), 1)."<br>\n";
		die();*/
		
		if ($permission_id && $objects_permissions) {
			if (substr(strtolower($file_path), 0, strlen(APP_PATH)) == strtolower(APP_PATH))
				$object_id = substr($file_path, strlen(APP_PATH));
			else
				$object_id = $file_path;
			
			$object_id = substr($object_id, 0, 1) == "/" ? substr($object_id, 1) : $object_id;
			$object_id .= substr($object_id, -1) != "/" ? "/" : "";
			$object_id = preg_replace("/\/+/", "/", $object_id);
			
			$selected_obj_id = "";
			$child_obj_id = "";
			
			foreach ($objects_permissions as $obj_id => $permissions) 
				if (is_array($permissions)) {
					//check if $permission_id is inside of $permissions
					if (is_array($permission_id))
						$permission_exists = count(array_intersect($permission_id, $permissions));
					else
						$permission_exists = in_array($permission_id, $permissions);
					
					//if permission exists, prepare correspondent object id
					if ($permission_exists) {
						$oid = $obj_id . (substr($obj_id, -1) != "/" ? "/" : "");
						
						if (strlen($object_id) >= strlen($oid) && substr($object_id, 0, strlen($oid)) == $oid 
							&& strlen($selected_obj_id) < strlen($obj_id)
						) //check if the object has access, this is, gets the closest parent inside of the $objects_permissions.
							$selected_obj_id = $obj_id;
						else if ($include_children_access && 
							strlen($object_id) < strlen($oid) && substr($oid, 0, strlen($object_id)) == $object_id
							&& (!$child_obj_id || strlen($child_obj_id) > strlen($obj_id))
						) //check if there are any children with access, this is, gets the closest children from $object_id inside of $objects_permissions.
							$child_obj_id = $obj_id;
					}
				}
			
			$new_object_id = $selected_obj_id ? $selected_obj_id : $child_obj_id; //if no selected_obj_id, check if there is a child with access
			
			/*echo "object_type_id:$object_type_id<br>\n";
			echo "permission:$permission<br>\n";
			echo "object_id:$object_id<br>\n";
			echo "selected_obj_id:$selected_obj_id<br>\n";
			echo "child_obj_id:$child_obj_id<br>\n";
			echo "new_object_id:$new_object_id<br>\n";
			echo "<pre>";print_r($objects_permissions);
			die();*/
			
			if ($new_object_id) {
				//echo "$object_id|||$new_object_id|||".strlen($object_id)." >= ".(strlen($new_object_id)+1)."\n";die();
				//echo "object_id:$object_id:".print_r($objects_permissions[$new_object_id], 1)."<br>\n";
				
				if (is_array($permission)) {
					foreach ($permission as $p)
						if ($this->isLayoutObjectPermissionAllowed($layout, $object_type_id, $new_object_id, $p))
							return true;
				}
				else
					return $this->isLayoutObjectPermissionAllowed($layout, $object_type_id, $new_object_id, $permission);
			}
			
			return $omitted_permission_return;
		}
		//echo"$file_path<br>";die();
		
		return false;
	}
	
	private function isLayoutObjectPermissionAllowed($layout, $object_type_id, $object_id, $permission) {
		$available_permissions = $this->getAvailablePermissions();
		$permission_id = isset($available_permissions[strtolower($permission)]) ? $available_permissions[strtolower($permission)] : null;
		$current_object_permissions = $layout && $object_type_id && $object_id && isset($this->layouts_type_permissions_by_object[$layout][$object_type_id][$object_id]) ? $this->layouts_type_permissions_by_object[$layout][$object_type_id][$object_id] : null;
		//echo "object_id:$object_id; permission_id:$permission_id; exists: ".in_array($permission_id, $current_object_permissions)."; current_object_permissions:";print_r($current_object_permissions);echo "<br>";
		
		return is_array($current_object_permissions) ? in_array($permission_id, $current_object_permissions) : false;
	}
	
	private function redirect($url) {
		//Do not add the domain in the cookies so we can save same space in the cookies.
		//$url_back = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443 ? "https://" : "http://") . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"];
		$url_back = isset($_SERVER["REQUEST_URI"]) ? $_SERVER["REQUEST_URI"] : null;
		
		$_COOKIE[ self::$URL_BACK_VARIABLE_NAME ] = $url_back;
		CookieHandler::setCurrentDomainEternalRootSafeCookie(self::$URL_BACK_VARIABLE_NAME, $url_back);
		
		header("Location: " . $url);
		echo "<script>document.location = '" . $url . "';</script>";
		die();
	}
	
	private function checkIfUserIsLoggedIn() {
		$session_id = isset($_COOKIE[ self::$USER_SESSION_ID_VARIABLE_NAME ]) ? $_COOKIE[ self::$USER_SESSION_ID_VARIABLE_NAME ] : null;
		
		if ($session_id) {
			$status = true;
			
			//check HTTP Referrer bc of CSRF attacks
			$status = CSRFValidator::validateRequest();
			
			if ($status) {
				if (empty($this->auth["user_data"]["user_id"])) {
					$login_data = $this->getLoginControlBySessionId($session_id);
					
					if (!empty($login_data["username"])) {
						$user_data = $this->searchUsers(array("username" => $login_data["username"]));
						$user_data = $user_data[0];
						unset($user_data["password"]);
						
						$this->auth["user_data"] = $user_data;
						$this->auth["login_expired_time"] = isset($login_data["login_expired_time"]) ? $login_data["login_expired_time"] : null;
					}
				}
				
				if (!empty($this->auth["user_data"]["user_id"])) {
					$this->loadLoggedUserPermissions();
					
					if (isset($this->auth["login_expired_time"]) && $this->auth["login_expired_time"] > time()) {
						$extra_flags = CSRFValidator::$COOKIES_EXTRA_FLAGS;
						
						//code against xss and csfr attacks
						if (isset($_SERVER['REQUEST_METHOD']) && in_array(strtolower($_SERVER['REQUEST_METHOD']), $this->user_session_control_methods)) {
							//check session control variable and renew the expiration time. this is very important bc of xss and csfr attacks.
							$user_session_control = isset($_COOKIE[ self::$USER_SESSION_CONTROL_VARIABLE_NAME ]) ? $_COOKIE[ self::$USER_SESSION_CONTROL_VARIABLE_NAME ] : null;
							if ($user_session_control) {
								$user_session_control = CryptoKeyHandler::hexToBin($user_session_control);
								$user_session_control = CryptoKeyHandler::decryptText($user_session_control, $this->login_control_table_encryption_key);
							}
							
							if (is_numeric($user_session_control) && $user_session_control >= time()) {
								//renew expiration time of the session control bc of xss and csfr attacks
								$ttl = CryptoKeyHandler::encryptText( time() + $this->user_session_control_expired_time, $this->login_control_table_encryption_key );
								$ttl = CryptoKeyHandler::binToHex($ttl);
								CookieHandler::setSafeCookie(self::$USER_SESSION_CONTROL_VARIABLE_NAME, $ttl, 0, "/", $extra_flags);
								
								//error_log("$user_session_control => ". (time() + $this->user_session_control_expired_time) ."\n", 3, $GLOBALS["log_file_path"] ? $GLOBALS["log_file_path"] : "/var/www/html/livingroop/default/tmp/phpframework.log");
								
								return isset($this->auth["user_data"]["user_id"]) ? $this->auth["user_data"]["user_id"] : null;
							}
						}
						else {
							//renew expiration time of the session control bc of xss and csfr attacks
							$ttl = CryptoKeyHandler::encryptText( time() + $this->user_session_control_expired_time, $this->login_control_table_encryption_key );
							$ttl = CryptoKeyHandler::binToHex($ttl);
							CookieHandler::setSafeCookie(self::$USER_SESSION_CONTROL_VARIABLE_NAME, $ttl, 0, "/", $extra_flags);
							
							return isset($this->auth["user_data"]["user_id"]) ? $this->auth["user_data"]["user_id"] : null;;
						}
					}
				}
			}
		 }
	}
	
	private function loadLoggedUserPermissions() {
		if (empty($this->user_type_permissions)) {
			$this->user_type_permissions = array();
			$this->user_type_permissions_by_object = array();
			
			if (!empty($this->auth["user_data"]["user_id"])) {
				$user_user_types = $this->getUserUserTypesByUserId($this->auth["user_data"]["user_id"]);
				
				foreach ($user_user_types as $user_type_id) {
					$user_type_permissions = $this->searchUserTypePermissions(array("user_type_id" => $user_type_id));
					
					if (is_array($user_type_permissions)) {//it could be with no permissions...
						$this->user_type_permissions = array_merge($this->user_type_permissions, $user_type_permissions);
						foreach ($user_type_permissions as $utp) {
							$object_type_id = isset($utp["object_type_id"]) ? $utp["object_type_id"] : null;
							$object_id = isset($utp["object_id"]) ? $utp["object_id"] : null;
							$permission_id = isset($utp["permission_id"]) ? $utp["permission_id"] : null;
							
							$this->user_type_permissions_by_object[$object_type_id][$object_id][] = $permission_id;
						}
					}
				}
			}
		}
	}
	
	public function loadLayoutPermissions($layout, $type_id = 0) {
		if (empty($this->layout_type_permissions[$layout])) {
			$this->layouts_type_permissions[$layout] = array();
			$this->layouts_type_permissions_by_object[$layout] = array();
			
			$layout_types = $this->getAvailableLayoutTypes($type_id);
			$layout_type_id = isset($layout_types[$layout]) ? $layout_types[$layout] : null;
			
			if ($layout_type_id) {
				$layout_type_permissions = $this->searchLayoutTypePermissions(array("layout_type_id" => $layout_type_id));
				
				if (is_array($layout_type_permissions)) {//it could be with no permissions...
					$this->layouts_type_permissions[$layout] = array_merge($this->layouts_type_permissions[$layout], $layout_type_permissions);
					
					foreach ($layout_type_permissions as $ptp) {
						$object_type_id = isset($ptp["object_type_id"]) ? $ptp["object_type_id"] : null;
						$object_id = isset($ptp["object_id"]) ? $ptp["object_id"] : null;
						$permission_id = isset($ptp["permission_id"]) ? $ptp["permission_id"] : null;
						
						$this->layouts_type_permissions_by_object[$layout][$object_type_id][$object_id][] = $permission_id;
					}
				}
			}
		}
	}
	
	/* LOGIN CONTROL FUNCTIONS */
	
	public function changeLoginControlTableEncryptionKey($new_encryption_key) {
		if ($this->is_local_db) {
			$data = array("root_path" => $this->root_path, "encryption_key" => $this->login_control_table_encryption_key, "new_encryption_key" => $new_encryption_key);
			return $this->EVC->getBroker()->callBusinessLogic("auth.localdb", "LocalDBLoginControlService.changeTableEncryptionKey", $data);
		}
	}
	
	private function dropAndCreateLoginControlTable() {
		if ($this->is_local_db) {
			$data = array("root_path" => $this->root_path, "encryption_key" => $this->login_control_table_encryption_key);
			return $this->EVC->getBroker()->callBusinessLogic("auth.localdb", "LocalDBLoginControlService.dropAndCreateTable", $data);
		}
		return $this->EVC->getBroker()->callBusinessLogic("auth.remotedb", "RemoteDBLoginControlService.dropAndCreateTable");
	}
	
	public function insertLoginControl($data) {
		if ($this->is_local_db) {
			$data["root_path"] = $this->root_path;
			$data["encryption_key"] = $this->login_control_table_encryption_key;
		
			return $this->EVC->getBroker()->callBusinessLogic("auth.localdb", "LocalDBLoginControlService.insert", $data);
		}
		return $this->EVC->getBroker()->callBusinessLogic("auth.remotedb", "RemoteDBLoginControlService.insert", $data);
	}
	
	public function insertFailedLoginAttempt($username) {
		if ($this->is_local_db) {
			$data = array("root_path" => $this->root_path, "encryption_key" => $this->login_control_table_encryption_key, "username" => $username);
			return $this->EVC->getBroker()->callBusinessLogic("auth.localdb", "LocalDBLoginControlService.insertFailedLoginAttempt", $data);
		}
		return $this->EVC->getBroker()->callBusinessLogic("auth.remotedb", "RemoteDBLoginControlService.insertFailedLoginAttempt", array("username" => $username));
	}
	
	public function expireSession($session_id) {
		if ($this->is_local_db) {
			$data = array("root_path" => $this->root_path, "encryption_key" => $this->login_control_table_encryption_key, "session_id" => $session_id);
			return $this->EVC->getBroker()->callBusinessLogic("auth.localdb", "LocalDBLoginControlService.expireSession", $data);
		}
		return $this->EVC->getBroker()->callBusinessLogic("auth.remotedb", "RemoteDBLoginControlService.expireSession", array("session_id" => $session_id));
	}
	
	public function resetFailedLoginAttempts($username) {
		if ($this->is_local_db) {
			$data = array("root_path" => $this->root_path, "encryption_key" => $this->login_control_table_encryption_key, "username" => $username);
			return $this->EVC->getBroker()->callBusinessLogic("auth.localdb", "LocalDBLoginControlService.resetFailedLoginAttempts", $data);
		}
		return $this->EVC->getBroker()->callBusinessLogic("auth.remotedb", "RemoteDBLoginControlService.resetFailedLoginAttempts", array("username" => $username));
	}
	
	public function getFailedLoginAttempts($username) {
		if ($this->is_local_db) {
			$data = array("root_path" => $this->root_path, "encryption_key" => $this->login_control_table_encryption_key, "username" => $username);
			return $this->EVC->getBroker()->callBusinessLogic("auth.localdb", "LocalDBLoginControlService.getFailedLoginAttempts", $data);
		}
		return $this->EVC->getBroker()->callBusinessLogic("auth.remotedb", "RemoteDBLoginControlService.getFailedLoginAttempts", array("username" => $username));
	}
	
	public function deleteLoginControl($username) {
		if ($this->is_local_db) {
			$data = array("root_path" => $this->root_path, "encryption_key" => $this->login_control_table_encryption_key, "username" => $username);
			return $this->EVC->getBroker()->callBusinessLogic("auth.localdb", "LocalDBLoginControlService.delete", $data);
		}
		return $this->EVC->getBroker()->callBusinessLogic("auth.remotedb", "RemoteDBLoginControlService.delete", array("username" => $username));
	}
	
	public function getLoginControl($username) {
		if ($this->is_local_db) {
			$data = array("root_path" => $this->root_path, "encryption_key" => $this->login_control_table_encryption_key, "username" => $username);
			return $this->EVC->getBroker()->callBusinessLogic("auth.localdb", "LocalDBLoginControlService.get", $data);
		}
		return $this->EVC->getBroker()->callBusinessLogic("auth.remotedb", "RemoteDBLoginControlService.get", array("username" => $username));
	}
	
	public function getLoginControlBySessionId($session_id) {
		if ($this->is_local_db) {
			$data = array("root_path" => $this->root_path, "encryption_key" => $this->login_control_table_encryption_key, "session_id" => $session_id);
			return $this->EVC->getBroker()->callBusinessLogic("auth.localdb", "LocalDBLoginControlService.getBySessionId", $data);
		}
		return $this->EVC->getBroker()->callBusinessLogic("auth.remotedb", "RemoteDBLoginControlService.getBySessionId", array("session_id" => $session_id));
	}
	
	public function isUserBlocked($username) {
		if ($this->is_local_db) {
			$data = array("root_path" => $this->root_path, "encryption_key" => $this->login_control_table_encryption_key, "username" => $username, "maximum_failed_attempts" => $this->maximum_failed_attempts, "user_blocked_expired_time" => $this->user_blocked_expired_time);
			return $this->EVC->getBroker()->callBusinessLogic("auth.localdb", "LocalDBLoginControlService.isUserBlocked", $data);
		}
		return $this->EVC->getBroker()->callBusinessLogic("auth.remotedb", "RemoteDBLoginControlService.isUserBlocked", array("username" => $username, "maximum_failed_attempts" => $this->maximum_failed_attempts, "user_blocked_expired_time" => $this->user_blocked_expired_time));
	}
	
	public function getAllLoginControls() {
		if ($this->is_local_db) {
			$data = array("root_path" => $this->root_path, "encryption_key" => $this->login_control_table_encryption_key);
			return $this->EVC->getBroker()->callBusinessLogic("auth.localdb", "LocalDBLoginControlService.getAll", $data);
		}
		return $this->EVC->getBroker()->callBusinessLogic("auth.remotedb", "RemoteDBLoginControlService.getAll");
	}
	
	/* PERMISSIONS FUNCTIONS */
	
	public function changePermissionTableEncryptionKey($new_encryption_key) {
		if ($this->is_local_db) {
			$data = array("root_path" => $this->root_path, "encryption_key" => $this->permission_table_encryption_key, "new_encryption_key" => $new_encryption_key);
			return $this->EVC->getBroker()->callBusinessLogic("auth.localdb", "LocalDBPermissionService.changeTableEncryptionKey", $data);
		}
	}
	
	private function dropAndCreatePermissionTable() {
		if ($this->is_local_db) {
			$data = array("root_path" => $this->root_path, "encryption_key" => $this->permission_table_encryption_key);
			return $this->EVC->getBroker()->callBusinessLogic("auth.localdb", "LocalDBPermissionService.dropAndCreateTable", $data);
		}
		return $this->EVC->getBroker()->callBusinessLogic("auth.remotedb", "RemoteDBPermissionService.dropAndCreateTable");
	}
	
	public function insertPermission($data) {
		if ($this->is_local_db) {
			$data["root_path"] = $this->root_path;
			$data["encryption_key"] = $this->permission_table_encryption_key;
			return $this->EVC->getBroker()->callBusinessLogic("auth.localdb", "LocalDBPermissionService.insert", $data);
		}
		return $this->EVC->getBroker()->callBusinessLogic("auth.remotedb", "RemoteDBPermissionService.insert", $data);
	}
	
	public function updatePermission($data) {
		if ($this->is_local_db) {
			$data["root_path"] = $this->root_path;
			$data["encryption_key"] = $this->permission_table_encryption_key;
			return $this->EVC->getBroker()->callBusinessLogic("auth.localdb", "LocalDBPermissionService.update", $data);
		}
		return $this->EVC->getBroker()->callBusinessLogic("auth.remotedb", "RemoteDBPermissionService.update", $data);
	}
	
	public function deletePermission($permission_id) {
		if ($this->is_local_db) {
			$data = array("root_path" => $this->root_path, "encryption_key" => $this->permission_table_encryption_key, "permission_id" => $permission_id);
			return $this->EVC->getBroker()->callBusinessLogic("auth.localdb", "LocalDBPermissionService.delete", $data);
		}
		return $this->EVC->getBroker()->callBusinessLogic("auth.remotedb", "RemoteDBPermissionService.delete", array("permission_id" => $permission_id));
	}
	
	public function getPermission($permission_id) {
		if ($this->is_local_db) {
			$data = array("root_path" => $this->root_path, "encryption_key" => $this->permission_table_encryption_key, "permission_id" => $permission_id);
			return $this->EVC->getBroker()->callBusinessLogic("auth.localdb", "LocalDBPermissionService.get", $data);
		}
		return $this->EVC->getBroker()->callBusinessLogic("auth.remotedb", "RemoteDBPermissionService.get", array("permission_id" => $permission_id));
	}
	
	public function getAllPermissions() {
		if ($this->is_local_db) {
			$data = array("root_path" => $this->root_path, "encryption_key" => $this->permission_table_encryption_key);
			return $this->EVC->getBroker()->callBusinessLogic("auth.localdb", "LocalDBPermissionService.getAll", $data);
		}
		return $this->EVC->getBroker()->callBusinessLogic("auth.remotedb", "RemoteDBPermissionService.getAll");
	}
	
	public function searchPermissions($conditions) {
		if ($this->is_local_db) {
			$data = array("root_path" => $this->root_path, "encryption_key" => $this->permission_table_encryption_key, "conditions" => $conditions);
			return $this->EVC->getBroker()->callBusinessLogic("auth.localdb", "LocalDBPermissionService.search", $data);
		}
		return $this->EVC->getBroker()->callBusinessLogic("auth.remotedb", "RemoteDBPermissionService.search", array("conditions" => $conditions));
	}
	
	public function getAvailablePermissions() {
		if (empty($this->available_permissions)) {
			$this->available_permissions = array();
			
			$permissions = $this->getAllPermissions();
			
			if ($permissions) {
				$t = count($permissions);
				for ($i = 0; $i < $t; $i++) {
					$p = $permissions[$i];
					$name = isset($p["name"]) ? $p["name"] : null;
					$permission_id = isset($p["permission_id"]) ? $p["permission_id"] : null;
					
					$this->available_permissions[$name] = $permission_id;
				}
			}
		}
		
		return $this->available_permissions;
	}
	
	public function getReservedPermissions() {
		return array(1, 2, 3);//permission 1, 2, 3 are> access, write, delete
	}
	
	/* USER FUNCTIONS */
	
	public function changeUserTableEncryptionKey($new_encryption_key) {
		if ($this->is_local_db) {
			$data = array("root_path" => $this->root_path, "encryption_key" => $this->user_table_encryption_key, "new_encryption_key" => $new_encryption_key);
			return $this->EVC->getBroker()->callBusinessLogic("auth.localdb", "LocalDBUserService.changeTableEncryptionKey", $data);
		}
	}
	
	private function dropAndCreateUserTable() {
		if ($this->is_local_db) {
			$data = array("root_path" => $this->root_path, "encryption_key" => $this->user_table_encryption_key);
			return $this->EVC->getBroker()->callBusinessLogic("auth.localdb", "LocalDBUserService.dropAndCreateTable", $data);
		}
		return $this->EVC->getBroker()->callBusinessLogic("auth.remotedb", "RemoteDBUserService.dropAndCreateTable");
	}
	
	public function insertUser($data) {
		if ($this->is_local_db) {
			$data["root_path"] = $this->root_path;
			$data["encryption_key"] = $this->user_table_encryption_key;
			return $this->EVC->getBroker()->callBusinessLogic("auth.localdb", "LocalDBUserService.insert", $data);
		}
		return $this->EVC->getBroker()->callBusinessLogic("auth.remotedb", "RemoteDBUserService.insert", $data);
	}
	
	public function updateUser($data) {
		if ($this->is_local_db) {
			$data["root_path"] = $this->root_path;
			$data["encryption_key"] = $this->user_table_encryption_key;
			return $this->EVC->getBroker()->callBusinessLogic("auth.localdb", "LocalDBUserService.update", $data);
		}
		return $this->EVC->getBroker()->callBusinessLogic("auth.remotedb", "RemoteDBUserService.update", $data);
	}
	
	public function deleteUser($user_id) {
		if ($this->is_local_db) {
			$data = array("root_path" => $this->root_path, "encryption_key" => $this->user_table_encryption_key, "user_id" => $user_id);
			return $this->EVC->getBroker()->callBusinessLogic("auth.localdb", "LocalDBUserService.delete", $data);
		}
		return $this->EVC->getBroker()->callBusinessLogic("auth.remotedb", "RemoteDBUserService.delete", array("user_id" => $user_id));
	}
	
	public function getUser($user_id) {
		if ($this->is_local_db) {
			$data = array("root_path" => $this->root_path, "encryption_key" => $this->user_table_encryption_key, "user_id" => $user_id);
			return $this->EVC->getBroker()->callBusinessLogic("auth.localdb", "LocalDBUserService.get", $data);
		}
		return $this->EVC->getBroker()->callBusinessLogic("auth.remotedb", "RemoteDBUserService.get", array("user_id" => $user_id));
	}
	
	public function getUserByUsernameAndPassword($username, $password) {
		if ($this->is_local_db) {
			$data = array("root_path" => $this->root_path, "encryption_key" => $this->user_table_encryption_key, "username" => $username, "password" => $password);
			return $this->EVC->getBroker()->callBusinessLogic("auth.localdb", "LocalDBUserService.getByUsernameAndPassword", $data);
		}
		return $this->EVC->getBroker()->callBusinessLogic("auth.remotedb", "RemoteDBUserService.getByUsernameAndPassword", array("username" => $username, "password" => $password));
	}
	
	public function getAllUsers() {
		if ($this->is_local_db) {
			$data = array("root_path" => $this->root_path, "encryption_key" => $this->user_table_encryption_key);
			return $this->EVC->getBroker()->callBusinessLogic("auth.localdb", "LocalDBUserService.getAll", $data);
		}
		return $this->EVC->getBroker()->callBusinessLogic("auth.remotedb", "RemoteDBUserService.getAll");
	}
	
	public function searchUsers($conditions) {
		if ($this->is_local_db) {
			$data = array("root_path" => $this->root_path, "encryption_key" => $this->user_table_encryption_key, "conditions" => $conditions);
			return $this->EVC->getBroker()->callBusinessLogic("auth.localdb", "LocalDBUserService.search", $data);
		}
		return $this->EVC->getBroker()->callBusinessLogic("auth.remotedb", "RemoteDBUserService.search", array("conditions" => $conditions));
	}
	
	public function getAvailableUsers() {
		if (empty($this->available_users)) {
			$this->available_users = array();
			
			$users = $this->getAllUsers();
			
			if ($users) {
				$t = count($users);
				for ($i = 0; $i < $t; $i++) {
					$u = $users[$i];
					$name = isset($u["name"]) ? $u["name"] : null;
					$user_id = isset($u["user_id"]) ? $u["user_id"] : null;
					
					$this->available_users[$name] = $user_id;
				}
			}
		}
		
		return $this->available_users;
	}
	
	/* USER TYPE FUNCTIONS */
	
	public function changeUserTypeTableEncryptionKey($new_encryption_key) {
		if ($this->is_local_db) {
			$data = array("root_path" => $this->root_path, "encryption_key" => $this->user_type_table_encryption_key, "new_encryption_key" => $new_encryption_key);
			return $this->EVC->getBroker()->callBusinessLogic("auth.localdb", "LocalDBUserTypeService.changeTableEncryptionKey", $data);
		}
	}
	
	private function dropAndCreateUserTypeTable() {
		if ($this->is_local_db) {
			$data = array("root_path" => $this->root_path, "encryption_key" => $this->user_type_table_encryption_key);
			return $this->EVC->getBroker()->callBusinessLogic("auth.localdb", "LocalDBUserTypeService.dropAndCreateTable", $data);
		}
		return $this->EVC->getBroker()->callBusinessLogic("auth.remotedb", "RemoteDBUserTypeService.dropAndCreateTable");
	}
	
	public function insertUserType($data) {
		if ($this->is_local_db) {
			$data["root_path"] = $this->root_path;
			$data["encryption_key"] = $this->user_type_table_encryption_key;
			return $this->EVC->getBroker()->callBusinessLogic("auth.localdb", "LocalDBUserTypeService.insert", $data);
		}
		return $this->EVC->getBroker()->callBusinessLogic("auth.remotedb", "RemoteDBUserTypeService.insert", $data);
	}
	
	public function updateUserType($data) {
		if ($this->is_local_db) {
			$data["root_path"] = $this->root_path;
			$data["encryption_key"] = $this->user_type_table_encryption_key;
			return $this->EVC->getBroker()->callBusinessLogic("auth.localdb", "LocalDBUserTypeService.update", $data);
		}
		return $this->EVC->getBroker()->callBusinessLogic("auth.remotedb", "RemoteDBUserTypeService.update", $data);
	}
	
	public function deleteUserType($user_type_id) {
		if ($this->is_local_db) {
			$data = array("root_path" => $this->root_path, "encryption_key" => $this->user_type_table_encryption_key, "user_type_id" => $user_type_id);
			return $this->EVC->getBroker()->callBusinessLogic("auth.localdb", "LocalDBUserTypeService.delete", $data);
		}
		return $this->EVC->getBroker()->callBusinessLogic("auth.remotedb", "RemoteDBUserTypeService.delete", array("user_type_id" => $user_type_id));
	}
	
	public function getUserType($user_type_id) {
		if ($this->is_local_db) {
			$data = array("root_path" => $this->root_path, "encryption_key" => $this->user_type_table_encryption_key, "user_type_id" => $user_type_id);
			return $this->EVC->getBroker()->callBusinessLogic("auth.localdb", "LocalDBUserTypeService.get", $data);
		}
		return $this->EVC->getBroker()->callBusinessLogic("auth.remotedb", "RemoteDBUserTypeService.get", array("user_type_id" => $user_type_id));
	}
	
	public function getAllUserTypes() {
		if ($this->is_local_db) {
			$data = array("root_path" => $this->root_path, "encryption_key" => $this->user_type_table_encryption_key);
			return $this->EVC->getBroker()->callBusinessLogic("auth.localdb", "LocalDBUserTypeService.getAll", $data);
		}
		return $this->EVC->getBroker()->callBusinessLogic("auth.remotedb", "RemoteDBUserTypeService.getAll");
	}
	
	public function searchUserTypes($conditions) {
		if ($this->is_local_db) {
			$data = array("root_path" => $this->root_path, "encryption_key" => $this->user_type_table_encryption_key, "conditions" => $conditions);
			return $this->EVC->getBroker()->callBusinessLogic("auth.localdb", "LocalDBUserTypeService.search", $data);
		}
		return $this->EVC->getBroker()->callBusinessLogic("auth.remotedb", "RemoteDBUserTypeService.search", array("conditions" => $conditions));
	}
	
	public function getAvailableUserTypes() {
		if (empty($this->available_user_types)) {
			$this->available_user_types = array();
			
			$user_types = $this->getAllUserTypes();
			
			if ($user_types) {
				$t = count($user_types);
				for ($i = 0; $i < $t; $i++) {
					$ut = $user_types[$i];
					$name = isset($ut["name"]) ? $ut["name"] : null;
					$user_type_id = isset($ut["user_type_id"]) ? $ut["user_type_id"] : null;
					
					$this->available_user_types[$name] = $user_type_id;
				}
			}
		}
		
		return $this->available_user_types;
	}
	
	/* OBJECT TYPE FUNCTIONS */
	
	public function changeObjectTypeTableEncryptionKey($new_encryption_key) {
		if ($this->is_local_db) {
			$data = array("root_path" => $this->root_path, "encryption_key" => $this->object_type_table_encryption_key, "new_encryption_key" => $new_encryption_key);
			return $this->EVC->getBroker()->callBusinessLogic("auth.localdb", "LocalDBObjectTypeService.changeTableEncryptionKey", $data);
		}
	}
	
	private function dropAndCreateObjectTypeTable() {
		if ($this->is_local_db) {
			$data = array("root_path" => $this->root_path, "encryption_key" => $this->object_type_table_encryption_key);
			return $this->EVC->getBroker()->callBusinessLogic("auth.localdb", "LocalDBObjectTypeService.dropAndCreateTable", $data);
		}
		return $this->EVC->getBroker()->callBusinessLogic("auth.remotedb", "RemoteDBObjectTypeService.dropAndCreateTable");
	}
	
	public function insertObjectType($data) {
		if ($this->is_local_db) {
			$data["root_path"] = $this->root_path;
			$data["encryption_key"] = $this->object_type_table_encryption_key;
			return $this->EVC->getBroker()->callBusinessLogic("auth.localdb", "LocalDBObjectTypeService.insert", $data);
		}
		return $this->EVC->getBroker()->callBusinessLogic("auth.remotedb", "RemoteDBObjectTypeService.insert", $data);
	}
	
	public function updateObjectType($data) {
		if ($this->is_local_db) {
			$data["root_path"] = $this->root_path;
			$data["encryption_key"] = $this->object_type_table_encryption_key;
			return $this->EVC->getBroker()->callBusinessLogic("auth.localdb", "LocalDBObjectTypeService.update", $data);
		}
		return $this->EVC->getBroker()->callBusinessLogic("auth.remotedb", "RemoteDBObjectTypeService.update", $data);
	}
	
	public function deleteObjectType($object_type_id) {
		if ($this->is_local_db) {
			$data = array("root_path" => $this->root_path, "encryption_key" => $this->object_type_table_encryption_key, "object_type_id" => $object_type_id);
			return $this->EVC->getBroker()->callBusinessLogic("auth.localdb", "LocalDBObjectTypeService.delete", $data);
		}
		return $this->EVC->getBroker()->callBusinessLogic("auth.remotedb", "RemoteDBObjectTypeService.delete", array("object_type_id" => $object_type_id));
	}
	
	public function getObjectType($object_type_id) {
		if ($this->is_local_db) {
			$data = array("root_path" => $this->root_path, "encryption_key" => $this->object_type_table_encryption_key, "object_type_id" => $object_type_id);
			return $this->EVC->getBroker()->callBusinessLogic("auth.localdb", "LocalDBObjectTypeService.get", $data);
		}
		return $this->EVC->getBroker()->callBusinessLogic("auth.remotedb", "RemoteDBObjectTypeService.get", array("object_type_id" => $object_type_id));
	}
	
	public function getAllObjectTypes() {
		if ($this->is_local_db) {
			$data = array("root_path" => $this->root_path, "encryption_key" => $this->object_type_table_encryption_key);
			return $this->EVC->getBroker()->callBusinessLogic("auth.localdb", "LocalDBObjectTypeService.getAll", $data);
		}
		return $this->EVC->getBroker()->callBusinessLogic("auth.remotedb", "RemoteDBObjectTypeService.getAll");
	}
	
	public function searchObjectTypes($conditions) {
		if ($this->is_local_db) {
			$data = array("root_path" => $this->root_path, "encryption_key" => $this->object_type_table_encryption_key, "conditions" => $conditions);
			return $this->EVC->getBroker()->callBusinessLogic("auth.localdb", "LocalDBObjectTypeService.search", $data);
		}
		return $this->EVC->getBroker()->callBusinessLogic("auth.remotedb", "RemoteDBObjectTypeService.search", array("conditions" => $conditions));
	}
	
	public function getAvailableObjectTypes() {
		if (empty($this->available_object_types)) {
			$this->available_object_types = array();
			
			$object_types = $this->getAllObjectTypes();
			
			if ($object_types) {
				$t = count($object_types);
				for ($i = 0; $i < $t; $i++) {
					$ot = $object_types[$i];
					$name = isset($ot["name"]) ? $ot["name"] : null;
					$object_type_id = isset($ot["object_type_id"]) ? $ot["object_type_id"] : null;
					
					$this->available_object_types[$name] = $object_type_id;
				}
			}
		}
		
		return $this->available_object_types;
	}
	
	public function getReservedObjectTypes() {
		return array(1);//object_type_id 1 is Page
	}
	
	/* USER TYPE PERMISSION FUNCTIONS */
	
	public function changeUserTypePermissionTableEncryptionKey($new_encryption_key) {
		if ($this->is_local_db) {
			$data = array("root_path" => $this->root_path, "encryption_key" => $this->user_type_permission_table_encryption_key, "new_encryption_key" => $new_encryption_key);
			return $this->EVC->getBroker()->callBusinessLogic("auth.localdb", "LocalDBUserTypePermissionService.changeTableEncryptionKey", $data);
		}
	}
	
	private function dropAndCreateUserTypePermissionTable() {
		if ($this->is_local_db) {
			$data = array("root_path" => $this->root_path, "encryption_key" => $this->user_type_permission_table_encryption_key);
			return $this->EVC->getBroker()->callBusinessLogic("auth.localdb", "LocalDBUserTypePermissionService.dropAndCreateTable", $data);
		}
		return $this->EVC->getBroker()->callBusinessLogic("auth.remotedb", "RemoteDBUserTypePermissionService.dropAndCreateTable");
	}
	
	public function insertUserTypePermission($data) {
		if ($this->is_local_db) {
			$data["root_path"] = $this->root_path;
			$data["encryption_key"] = $this->user_type_permission_table_encryption_key;
			return $this->EVC->getBroker()->callBusinessLogic("auth.localdb", "LocalDBUserTypePermissionService.insert", $data);
		}
		return $this->EVC->getBroker()->callBusinessLogic("auth.remotedb", "RemoteDBUserTypePermissionService.insert", $data);
	}
	
	/* $permissions_by_objects = array(
		"1" => array(
			"/asd/asd/asd" => array("v", "i", "d"),
			"/asd123asd" => array("v", "d"),
			...
		)
	) */
	public function updateUserTypesByObjectsPermissions($user_type_id, $permissions_by_objects) {
		if ($this->is_local_db) {
			$data = array("root_path" => $this->root_path, "encryption_key" => $this->user_type_permission_table_encryption_key, "user_type_id" => $user_type_id, "permissions_by_objects" => $permissions_by_objects);
			return $this->EVC->getBroker()->callBusinessLogic("auth.localdb", "LocalDBUserTypePermissionService.updateByObjectsPermissions", $data);
		}
		return $this->EVC->getBroker()->callBusinessLogic("auth.remotedb", "RemoteDBUserTypePermissionService.updateByObjectsPermissions", array("user_type_id" => $user_type_id, "permissions_by_objects" => $permissions_by_objects));
	}
	
	public function deleteUserTypePermission($user_type_id, $permission_id, $object_type_id, $object_id) {
		if ($this->is_local_db) {
			$data = array("root_path" => $this->root_path, "encryption_key" => $this->user_type_permission_table_encryption_key, "user_type_id" => $user_type_id, "permission_id" => $permission_id, "object_type_id" => $object_type_id, "object_id" => $object_id);
			return $this->EVC->getBroker()->callBusinessLogic("auth.localdb", "LocalDBUserTypePermissionService.delete", $data);
		}
		return $this->EVC->getBroker()->callBusinessLogic("auth.remotedb", "RemoteDBUserTypePermissionService.delete", array("user_type_id" => $user_type_id, "permission_id" => $permission_id, "object_type_id" => $object_type_id, "object_id" => $object_id));
	}
	
	public function deleteUserTypePermissionsByConditions($conditions) {
		if ($this->is_local_db) {
			$data = array("root_path" => $this->root_path, "encryption_key" => $this->user_type_permission_table_encryption_key, "conditions" => $conditions);
			return $this->EVC->getBroker()->callBusinessLogic("auth.localdb", "LocalDBUserTypePermissionService.deleteByConditions", $data);
		}
		return $this->EVC->getBroker()->callBusinessLogic("auth.remotedb", "RemoteDBUserTypePermissionService.deleteByConditions", array("conditions" => $conditions));
	}
	
	public function getUserTypePermission($user_type_id, $permission_id, $object_type_id, $object_id) {
		if ($this->is_local_db) {
			$data = array("root_path" => $this->root_path, "encryption_key" => $this->user_type_permission_table_encryption_key, "user_type_id" => $user_type_id, "permission_id" => $permission_id, "object_type_id" => $object_type_id, "object_id" => $object_id);
			return $this->EVC->getBroker()->callBusinessLogic("auth.localdb", "LocalDBUserTypePermissionService.get", $data);
		}
		return $this->EVC->getBroker()->callBusinessLogic("auth.remotedb", "RemoteDBUserTypePermissionService.get", array("user_type_id" => $user_type_id, "permission_id" => $permission_id, "object_type_id" => $object_type_id, "object_id" => $object_id));
	}
	
	public function getAllUserTypePermissions() {
		if ($this->is_local_db) {
			$data = array("root_path" => $this->root_path, "encryption_key" => $this->user_type_permission_table_encryption_key);
			return $this->EVC->getBroker()->callBusinessLogic("auth.localdb", "LocalDBUserTypePermissionService.getAll", $data);
		}
		return $this->EVC->getBroker()->callBusinessLogic("auth.remotedb", "RemoteDBUserTypePermissionService.getAll");
	}
	
	public function searchUserTypePermissions($conditions) {
		if ($this->is_local_db) {
			$data = array("root_path" => $this->root_path, "encryption_key" => $this->user_type_permission_table_encryption_key, "conditions" => $conditions);
			return $this->EVC->getBroker()->callBusinessLogic("auth.localdb", "LocalDBUserTypePermissionService.search", $data);
		}
		return $this->EVC->getBroker()->callBusinessLogic("auth.remotedb", "RemoteDBUserTypePermissionService.search", array("conditions" => $conditions));
	}
	
	/* USER USER TYPE FUNCTIONS */
	
	public function changeUserUserTypeTableEncryptionKey($new_encryption_key) {
		if ($this->is_local_db) {
			$data = array("root_path" => $this->root_path, "encryption_key" => $this->user_user_type_table_encryption_key, "new_encryption_key" => $new_encryption_key);
			return $this->EVC->getBroker()->callBusinessLogic("auth.localdb", "LocalDBUserUserTypeService.changeTableEncryptionKey", $data);
		}
	}
	
	private function dropAndCreateUserUserTypeTable() {
		if ($this->is_local_db) {
			$data = array("root_path" => $this->root_path, "encryption_key" => $this->user_user_type_table_encryption_key);
			return $this->EVC->getBroker()->callBusinessLogic("auth.localdb", "LocalDBUserUserTypeService.dropAndCreateTable", $data);
		}
		return $this->EVC->getBroker()->callBusinessLogic("auth.remotedb", "RemoteDBUserUserTypeService.dropAndCreateTable");
	}
	
	public function insertUserUserType($data) {
		if ($this->is_local_db) {
			$data["root_path"] = $this->root_path;
			$data["encryption_key"] = $this->user_user_type_table_encryption_key;
			return $this->EVC->getBroker()->callBusinessLogic("auth.localdb", "LocalDBUserUserTypeService.insert", $data);
		}
		return $this->EVC->getBroker()->callBusinessLogic("auth.remotedb", "RemoteDBUserUserTypeService.insert", $data);
	}
	
	public function deleteUserUserType($user_id, $user_type_id) {
		if ($this->is_local_db) {
			$data = array("root_path" => $this->root_path, "encryption_key" => $this->user_user_type_table_encryption_key, "user_id" => $user_id, "user_type_id" => $user_type_id);
			return $this->EVC->getBroker()->callBusinessLogic("auth.localdb", "LocalDBUserUserTypeService.delete", $data);
		}
		return $this->EVC->getBroker()->callBusinessLogic("auth.remotedb", "RemoteDBUserUserTypeService.delete", array("user_id" => $user_id, "user_type_id" => $user_type_id));
	}
	
	public function deleteUserUserTypesByConditions($conditions) {
		if ($this->is_local_db) {
			$data = array("root_path" => $this->root_path, "encryption_key" => $this->user_user_type_table_encryption_key, "conditions" => $conditions);
			return $this->EVC->getBroker()->callBusinessLogic("auth.localdb", "LocalDBUserUserTypeService.deleteByConditions", $data);
		}
		return $this->EVC->getBroker()->callBusinessLogic("auth.remotedb", "RemoteDBUserUserTypeService.deleteByConditions", array("conditions" => $conditions));
	}
	
	public function getUserUserType($user_id, $user_type_id) {
		if ($this->is_local_db) {
			$data = array("root_path" => $this->root_path, "encryption_key" => $this->user_user_type_table_encryption_key, "user_id" => $user_id, "user_type_id" => $user_type_id);
			return $this->EVC->getBroker()->callBusinessLogic("auth.localdb", "LocalDBUserUserTypeService.get", $data);
		}
		return $this->EVC->getBroker()->callBusinessLogic("auth.remotedb", "RemoteDBUserUserTypeService.get", array("user_id" => $user_id, "user_type_id" => $user_type_id));
	}
	
	public function getAllUserUserTypes() {
		if ($this->is_local_db) {
			$data = array("root_path" => $this->root_path, "encryption_key" => $this->user_user_type_table_encryption_key);
			return $this->EVC->getBroker()->callBusinessLogic("auth.localdb", "LocalDBUserUserTypeService.getAll", $data);
		}
		return $this->EVC->getBroker()->callBusinessLogic("auth.remotedb", "RemoteDBUserUserTypeService.getAll");
	}
	
	public function searchUserUserTypes($conditions) {
		if ($this->is_local_db) {
			$data = array("root_path" => $this->root_path, "encryption_key" => $this->user_user_type_table_encryption_key, "conditions" => $conditions);
			return $this->EVC->getBroker()->callBusinessLogic("auth.localdb", "LocalDBUserUserTypeService.search", $data);
		}
		return $this->EVC->getBroker()->callBusinessLogic("auth.remotedb", "RemoteDBUserUserTypeService.search", array("conditions" => $conditions));
	}
	
	public function getUserUserTypesByUserId($user_id) {
		$user_user_types = array();
		
		$data = $this->searchUserUserTypes(array("user_id" => $user_id));
		
		if ($data) {
			$t = count($data);
			for ($i = 0; $i < $t; $i++) 
				$user_user_types[] = isset($data[$i]["user_type_id"]) ? $data[$i]["user_type_id"] : null;
		}
		
		return $user_user_types;
	}
	
	/* USER STATS FUNCTIONS */
	
	public function changeUserStatsTableEncryptionKey($new_encryption_key) {
		if ($this->is_local_db) {
			$data = array("root_path" => $this->root_path, "encryption_key" => $this->user_stats_table_encryption_key, "new_encryption_key" => $new_encryption_key);
			return $this->EVC->getBroker()->callBusinessLogic("auth.localdb", "LocalDBUserStatsService.changeTableEncryptionKey", $data);
		}
	}
	
	private function dropAndCreateUserStatsTable() {
		if ($this->is_local_db) {
			$data = array("root_path" => $this->root_path, "encryption_key" => $this->user_stats_table_encryption_key);
			return $this->EVC->getBroker()->callBusinessLogic("auth.localdb", "LocalDBUserStatsService.dropAndCreateTable", $data);
		}
		return $this->EVC->getBroker()->callBusinessLogic("auth.remotedb", "RemoteDBUserStatsService.dropAndCreateTable");
	}
	
	public function insertUserStat($data) {
		if ($this->is_local_db) {
			$data["root_path"] = $this->root_path;
			$data["encryption_key"] = $this->user_stats_table_encryption_key;
		
			return $this->EVC->getBroker()->callBusinessLogic("auth.localdb", "LocalDBUserStatsService.insert", $data);
		}
		return $this->EVC->getBroker()->callBusinessLogic("auth.remotedb", "RemoteDBUserStatsService.insert", $data);
	}
	
	public function getUserStat($name) {
		if ($this->is_local_db) {
			$data = array("root_path" => $this->root_path, "encryption_key" => $this->user_stats_table_encryption_key, "name" => $name);
			return $this->EVC->getBroker()->callBusinessLogic("auth.localdb", "LocalDBUserStatsService.get", $data);
		}
		return $this->EVC->getBroker()->callBusinessLogic("auth.remotedb", "RemoteDBUserStatsService.get", array("name" => $name));
	}
	
	public function getAllUserStats() {
		if ($this->is_local_db) {
			$data = array("root_path" => $this->root_path, "encryption_key" => $this->user_stats_table_encryption_key);
			return $this->EVC->getBroker()->callBusinessLogic("auth.localdb", "LocalDBUserStatsService.getAll", $data);
		}
		return $this->EVC->getBroker()->callBusinessLogic("auth.remotedb", "RemoteDBUserStatsService.getAll");
	}
	
	/* LAYOUT TYPE FUNCTIONS */
	
	public function changeLayoutTypeTableEncryptionKey($new_encryption_key) {
		if ($this->is_local_db) {
			$data = array("root_path" => $this->root_path, "encryption_key" => $this->layout_type_table_encryption_key, "new_encryption_key" => $new_encryption_key);
			return $this->EVC->getBroker()->callBusinessLogic("auth.localdb", "LocalDBLayoutTypeService.changeTableEncryptionKey", $data);
		}
	}
	
	private function dropAndCreateLayoutTypeTable() {
		if ($this->is_local_db) {
			$data = array("root_path" => $this->root_path, "encryption_key" => $this->layout_type_table_encryption_key);
			return $this->EVC->getBroker()->callBusinessLogic("auth.localdb", "LocalDBLayoutTypeService.dropAndCreateTable", $data);
		}
		return $this->EVC->getBroker()->callBusinessLogic("auth.remotedb", "RemoteDBLayoutTypeService.dropAndCreateTable");
	}
	
	public function insertLayoutType($data) {
		if ($this->is_local_db) {
			$data["root_path"] = $this->root_path;
			$data["encryption_key"] = $this->layout_type_table_encryption_key;
			return $this->EVC->getBroker()->callBusinessLogic("auth.localdb", "LocalDBLayoutTypeService.insert", $data);
		}
		return $this->EVC->getBroker()->callBusinessLogic("auth.remotedb", "RemoteDBLayoutTypeService.insert", $data);
	}
	
	public function updateLayoutType($data) {
		if ($this->is_local_db) {
			$data["root_path"] = $this->root_path;
			$data["encryption_key"] = $this->layout_type_table_encryption_key;
			return $this->EVC->getBroker()->callBusinessLogic("auth.localdb", "LocalDBLayoutTypeService.update", $data);
		}
		return $this->EVC->getBroker()->callBusinessLogic("auth.remotedb", "RemoteDBLayoutTypeService.update", $data);
	}
	
	public function updateLayoutTypesByNamePrefix($old_name_prefix, $new_name_prefix) {
		if ($this->is_local_db) {
			$data = array("root_path" => $this->root_path, "encryption_key" => $this->layout_type_table_encryption_key, "old_name_prefix" => $old_name_prefix, "new_name_prefix" => $new_name_prefix);
			return $this->EVC->getBroker()->callBusinessLogic("auth.localdb", "LocalDBLayoutTypeService.updateByNamePrefix", $data);
		}
		return $this->EVC->getBroker()->callBusinessLogic("auth.remotedb", "RemoteDBLayoutTypeService.updateByNamePrefix", array("old_name_prefix" => $old_name_prefix, "new_name_prefix" => $new_name_prefix));
	}
	
	public function deleteLayoutType($layout_type_id) {
		if ($this->deleteLayoutTypePermissionsByConditions(array("layout_type_id" => $layout_type_id))) {
			if ($this->is_local_db) {
				$data = array("root_path" => $this->root_path, "encryption_key" => $this->layout_type_table_encryption_key, "layout_type_id" => $layout_type_id);
				return $this->EVC->getBroker()->callBusinessLogic("auth.localdb", "LocalDBLayoutTypeService.delete", $data);
			}
			return $this->EVC->getBroker()->callBusinessLogic("auth.remotedb", "RemoteDBLayoutTypeService.delete", array("layout_type_id" => $layout_type_id));
		}
		
		return false;
	}
	
	public function getLayoutType($layout_type_id) {
		if ($this->is_local_db) {
			$data = array("root_path" => $this->root_path, "encryption_key" => $this->layout_type_table_encryption_key, "layout_type_id" => $layout_type_id);
			return $this->EVC->getBroker()->callBusinessLogic("auth.localdb", "LocalDBLayoutTypeService.get", $data);
		}
		return $this->EVC->getBroker()->callBusinessLogic("auth.remotedb", "RemoteDBLayoutTypeService.get", array("layout_type_id" => $layout_type_id));
	}
	
	public function getAllLayoutTypes() {
		if ($this->is_local_db) {
			$data = array("root_path" => $this->root_path, "encryption_key" => $this->layout_type_table_encryption_key);
			return $this->EVC->getBroker()->callBusinessLogic("auth.localdb", "LocalDBLayoutTypeService.getAll", $data);
		}
		return $this->EVC->getBroker()->callBusinessLogic("auth.remotedb", "RemoteDBLayoutTypeService.getAll");
	}
	
	public function searchLayoutTypes($conditions) {
		if ($this->is_local_db) {
			$data = array("root_path" => $this->root_path, "encryption_key" => $this->layout_type_table_encryption_key, "conditions" => $conditions);
			return $this->EVC->getBroker()->callBusinessLogic("auth.localdb", "LocalDBLayoutTypeService.search", $data);
		}
		return $this->EVC->getBroker()->callBusinessLogic("auth.remotedb", "RemoteDBLayoutTypeService.search", array("conditions" => $conditions));
	}
	
	public function getAvailableLayoutTypes($type_id) {
		if (empty($this->available_layout_types)) {
			$this->available_layout_types = array();
			
			$layout_types = is_numeric($type_id) ? $this->searchLayoutTypes(array("type_id" => $type_id)) : $this->getAllLayoutTypes();
			
			if ($layout_types) {
				$t = count($layout_types);
				for ($i = 0; $i < $t; $i++) {
					$ut = $layout_types[$i];
					$name = isset($ut["name"]) ? $ut["name"] : null;
					$layout_type_id = isset($ut["layout_type_id"]) ? $ut["layout_type_id"] : null;
					
					$this->available_layout_types[$name] = $layout_type_id;
				}
			}
		}
		
		return $this->available_layout_types;
	}
	
	/* LAYOUT TYPE PERMISSION FUNCTIONS */
	
	public function changeLayoutTypePermissionTableEncryptionKey($new_encryption_key) {
		if ($this->is_local_db) {
			$data = array("root_path" => $this->root_path, "encryption_key" => $this->layout_type_permission_table_encryption_key, "new_encryption_key" => $new_encryption_key);
			return $this->EVC->getBroker()->callBusinessLogic("auth.localdb", "LocalDBLayoutTypePermissionService.changeTableEncryptionKey", $data);
		}
	}
	
	private function dropAndCreateLayoutTypePermissionTable() {
		if ($this->is_local_db) {
			$data = array("root_path" => $this->root_path, "encryption_key" => $this->layout_type_permission_table_encryption_key);
			return $this->EVC->getBroker()->callBusinessLogic("auth.localdb", "LocalDBLayoutTypePermissionService.dropAndCreateTable", $data);
		}
		return $this->EVC->getBroker()->callBusinessLogic("auth.remotedb", "RemoteDBLayoutTypePermissionService.dropAndCreateTable");
	}
	
	public function insertLayoutTypePermission($data) {
		if ($this->is_local_db) {
			$data["root_path"] = $this->root_path;
			$data["encryption_key"] = $this->layout_type_permission_table_encryption_key;
			return $this->EVC->getBroker()->callBusinessLogic("auth.localdb", "LocalDBLayoutTypePermissionService.insert", $data);
		}
		return $this->EVC->getBroker()->callBusinessLogic("auth.remotedb", "RemoteDBLayoutTypePermissionService.insert", $data);
	}
	
	public function updateLayoutTypePermission($data) {
		if ($this->is_local_db) {
			$data["root_path"] = $this->root_path;
			$data["encryption_key"] = $this->layout_type_permission_table_encryption_key;
			return $this->EVC->getBroker()->callBusinessLogic("auth.localdb", "LocalDBLayoutTypePermissionService.updateObjectId", $data);
		}
		return $this->EVC->getBroker()->callBusinessLogic("auth.remotedb", "RemoteDBLayoutTypePermissionService.updateObjectId", $data);
	}
	
	public function updateLayoutTypePermissionsByObjectPrefix($old_object_prefix, $new_object_prefix) {
		if ($this->is_local_db) {
			$data = array("root_path" => $this->root_path, "encryption_key" => $this->layout_type_permission_table_encryption_key, "old_object_prefix" => $old_object_prefix, "new_object_prefix" => $new_object_prefix);
			return $this->EVC->getBroker()->callBusinessLogic("auth.localdb", "LocalDBLayoutTypePermissionService.updateByObjectPrefix", $data);
		}
		return $this->EVC->getBroker()->callBusinessLogic("auth.remotedb", "RemoteDBLayoutTypePermissionService.updateByObjectPrefix", array("old_object_prefix" => $old_object_prefix, "new_object_prefix" => $new_object_prefix));
	}
	
	/* $permissions_by_objects = array(
		"1" => array(
			"/asd/asd/asd" => array("v", "i", "d"),
			"/asd123asd" => array("v", "d"),
			...
		)
	) */
	public function updateLayoutTypePermissionsByObjectsPermissions($layout_type_id, $permissions_by_objects) {
		if ($this->is_local_db) {
			$data = array("root_path" => $this->root_path, "encryption_key" => $this->layout_type_permission_table_encryption_key, "layout_type_id" => $layout_type_id, "permissions_by_objects" => $permissions_by_objects);
			return $this->EVC->getBroker()->callBusinessLogic("auth.localdb", "LocalDBLayoutTypePermissionService.updateByObjectsPermissions", $data);
		}
		return $this->EVC->getBroker()->callBusinessLogic("auth.remotedb", "RemoteDBLayoutTypePermissionService.updateByObjectsPermissions", array("layout_type_id" => $layout_type_id, "permissions_by_objects" => $permissions_by_objects));
	}
	
	public function deleteLayoutTypePermission($layout_type_id, $permission_id, $object_type_id, $object_id) {
		if ($this->is_local_db) {
			$data = array("root_path" => $this->root_path, "encryption_key" => $this->layout_type_permission_table_encryption_key, "layout_type_id" => $layout_type_id, "permission_id" => $permission_id, "object_type_id" => $object_type_id, "object_id" => $object_id);
			return $this->EVC->getBroker()->callBusinessLogic("auth.localdb", "LocalDBLayoutTypePermissionService.delete", $data);
		}
		return $this->EVC->getBroker()->callBusinessLogic("auth.remotedb", "RemoteDBLayoutTypePermissionService.delete", array("layout_type_id" => $layout_type_id, "permission_id" => $permission_id, "object_type_id" => $object_type_id, "object_id" => $object_id));
	}
	
	public function deleteLayoutTypePermissionsByConditions($conditions) {
		if ($this->is_local_db) {
			$data = array("root_path" => $this->root_path, "encryption_key" => $this->layout_type_permission_table_encryption_key, "conditions" => $conditions);
			return $this->EVC->getBroker()->callBusinessLogic("auth.localdb", "LocalDBLayoutTypePermissionService.deleteByConditions", $data);
		}
		return $this->EVC->getBroker()->callBusinessLogic("auth.remotedb", "RemoteDBLayoutTypePermissionService.deleteByConditions", array("conditions" => $conditions));
	}
	
	public function getLayoutTypePermission($layout_type_id, $permission_id, $object_type_id, $object_id) {
		if ($this->is_local_db) {
			$data = array("root_path" => $this->root_path, "encryption_key" => $this->layout_type_permission_table_encryption_key, "layout_type_id" => $layout_type_id, "permission_id" => $permission_id, "object_type_id" => $object_type_id, "object_id" => $object_id);
			return $this->EVC->getBroker()->callBusinessLogic("auth.localdb", "LocalDBLayoutTypePermissionService.get", $data);
		}
		return $this->EVC->getBroker()->callBusinessLogic("auth.remotedb", "RemoteDBLayoutTypePermissionService.get", array("layout_type_id" => $layout_type_id, "permission_id" => $permission_id, "object_type_id" => $object_type_id, "object_id" => $object_id));
	}
	
	public function getAllLayoutTypePermissions() {
		if ($this->is_local_db) {
			$data = array("root_path" => $this->root_path, "encryption_key" => $this->layout_type_permission_table_encryption_key);
			return $this->EVC->getBroker()->callBusinessLogic("auth.localdb", "LocalDBLayoutTypePermissionService.getAll", $data);
		}
		return $this->EVC->getBroker()->callBusinessLogic("auth.remotedb", "RemoteDBLayoutTypePermissionService.getAll");
	}
	
	public function searchLayoutTypePermissions($conditions) {
		if ($this->is_local_db) {
			$data = array("root_path" => $this->root_path, "encryption_key" => $this->layout_type_permission_table_encryption_key, "conditions" => $conditions);
			return $this->EVC->getBroker()->callBusinessLogic("auth.localdb", "LocalDBLayoutTypePermissionService.search", $data);
		}
		return $this->EVC->getBroker()->callBusinessLogic("auth.remotedb", "RemoteDBLayoutTypePermissionService.search", array("conditions" => $conditions));
	}
	
	/* RESERVED DB TABLE NAMES FUNCTIONS */
	
	public function changeReservedDBTableNameTableEncryptionKey($new_encryption_key) {
		if ($this->is_local_db) {
			$data = array("root_path" => $this->root_path, "encryption_key" => $this->reserved_db_table_name_table_encryption_key, "new_encryption_key" => $new_encryption_key);
			return $this->EVC->getBroker()->callBusinessLogic("auth.localdb", "LocalDBReservedDBTableNameService.changeTableEncryptionKey", $data);
		}
	}
	
	private function dropAndCreateReservedDBTableNameTable() {
		if ($this->is_local_db) {
			$data = array("root_path" => $this->root_path, "encryption_key" => $this->reserved_db_table_name_table_encryption_key);
			return $this->EVC->getBroker()->callBusinessLogic("auth.localdb", "LocalDBReservedDBTableNameService.dropAndCreateTable", $data);
		}
		return $this->EVC->getBroker()->callBusinessLogic("auth.remotedb", "RemoteDBReservedDBTableNameService.dropAndCreateTable");
	}
	
	public function insertReservedDBTableName($data) {
		if ($this->is_local_db) {
			$data["root_path"] = $this->root_path;
			$data["encryption_key"] = $this->reserved_db_table_name_table_encryption_key;
			return $this->EVC->getBroker()->callBusinessLogic("auth.localdb", "LocalDBReservedDBTableNameService.insert", $data);
		}
		return $this->EVC->getBroker()->callBusinessLogic("auth.remotedb", "RemoteDBReservedDBTableNameService.insert", $data);
	}
	
	public function insertReservedDBTableNameIfNotExistsYet($data) {
		if ($this->is_local_db) {
			$data["root_path"] = $this->root_path;
			$data["encryption_key"] = $this->reserved_db_table_name_table_encryption_key;
			return $this->EVC->getBroker()->callBusinessLogic("auth.localdb", "LocalDBReservedDBTableNameService.insertIfNotExistsYet", $data);
		}
		return $this->EVC->getBroker()->callBusinessLogic("auth.remotedb", "RemoteDBReservedDBTableNameService.insertIfNotExistsYet", $data);
	}
	
	public function updateReservedDBTableName($data) {
		if ($this->is_local_db) {
			$data["root_path"] = $this->root_path;
			$data["encryption_key"] = $this->reserved_db_table_name_table_encryption_key;
			return $this->EVC->getBroker()->callBusinessLogic("auth.localdb", "LocalDBReservedDBTableNameService.update", $data);
		}
		return $this->EVC->getBroker()->callBusinessLogic("auth.remotedb", "RemoteDBReservedDBTableNameService.update", $data);
	}
	
	public function deleteReservedDBTableName($reserved_db_table_name_id) {
		if ($this->is_local_db) {
			$data = array("root_path" => $this->root_path, "encryption_key" => $this->reserved_db_table_name_table_encryption_key, "reserved_db_table_name_id" => $reserved_db_table_name_id);
			return $this->EVC->getBroker()->callBusinessLogic("auth.localdb", "LocalDBReservedDBTableNameService.delete", $data);
		}
		return $this->EVC->getBroker()->callBusinessLogic("auth.remotedb", "RemoteDBReservedDBTableNameService.delete", array("reserved_db_table_name_id" => $reserved_db_table_name_id));
	}
	
	public function getReservedDBTableName($reserved_db_table_name_id) {
		if ($this->is_local_db) {
			$data = array("root_path" => $this->root_path, "encryption_key" => $this->reserved_db_table_name_table_encryption_key, "reserved_db_table_name_id" => $reserved_db_table_name_id);
			return $this->EVC->getBroker()->callBusinessLogic("auth.localdb", "LocalDBReservedDBTableNameService.get", $data);
		}
		return $this->EVC->getBroker()->callBusinessLogic("auth.remotedb", "RemoteDBReservedDBTableNameService.get", array("reserved_db_table_name_id" => $reserved_db_table_name_id));
	}
	
	public function getAllReservedDBTableNames() {
		if ($this->is_local_db) {
			$data = array("root_path" => $this->root_path, "encryption_key" => $this->reserved_db_table_name_table_encryption_key);
			return $this->EVC->getBroker()->callBusinessLogic("auth.localdb", "LocalDBReservedDBTableNameService.getAll", $data);
		}
		return $this->EVC->getBroker()->callBusinessLogic("auth.remotedb", "RemoteDBReservedDBTableNameService.getAll");
	}
	
	public function searchReservedDBTableNames($conditions) {
		if ($this->is_local_db) {
			$data = array("root_path" => $this->root_path, "encryption_key" => $this->reserved_db_table_name_table_encryption_key, "conditions" => $conditions);
			return $this->EVC->getBroker()->callBusinessLogic("auth.localdb", "LocalDBReservedDBTableNameService.search", $data);
		}
		return $this->EVC->getBroker()->callBusinessLogic("auth.remotedb", "RemoteDBReservedDBTableNameService.search", array("conditions" => $conditions));
	}
	
	/* LICENCE FUNCTIONS */
	
	public function isUsersMaximumNumberReached() {
		$li = $this->EVC->getPresentationLayer()->getPHPFrameWork()->getLicenceInfo();
		$users = $this->getAllUsers();
		$umn = isset($li["umn"]) ? $li["umn"] : null;
		
		return $umn != -1 && count($users) >= $umn;
	}
	
	public function isUsersMaximumNumberExceeded() {
		$li = $this->EVC->getPresentationLayer()->getPHPFrameWork()->getLicenceInfo();
		$users = $this->getAllUsers();
		$umn = isset($li["umn"]) ? $li["umn"] : null;
		
		return $umn != -1 && count($users) > $umn;
	}
	
	public static function checkUsersMaxNum($UserAuthenticationHandler) {
		//php -r '$string="\$exceeded = \$UserAuthenticationHandler->isUsersMaximumNumberExceeded(); \$msg=\"You exceed the maximum number of users that your licence allow.\";"; for($i=0; $i < strlen($string); $i++) echo dechex(ord($string[$i]));echo "\n";'
		$hash = "246578636565646564203d20245573657241757468656e7469636174696f6e48616e646c65722d3e697355736572734d6178696d756d4e756d626572457863656564656428293b20246d73673d22596f752065786365656420746865206d6178696d756d206e756d626572206f66207573657273207468617420796f7572206c6963656e636520616c6c6f772e223b";
		$code = "";
		for ($i = 0, $l = strlen($hash); $i < $l; $i += 2)
			$code .= chr( hexdec($hash[$i] . ($i+1 < $l ? $hash[$i+1] : "") ) );
		
		eval($code);
		
		if ($exceeded) {
			echo $msg;
			die(1);
		}
	}
	
	public function incrementUsedActionsTotal() {
		$data = $this->getUserStat("used_actions_total");
		
		if (!$data)
			$data = array(
				"name" => "used_actions_total",
				"value" => CryptoKeyHandler::binToHex(CryptoKeyHandler::encryptText(1, $this->user_stats_table_encryption_key)),
			);
		else {
			$value = isset($data["value"]) ? $data["value"] : null;
			$value = CryptoKeyHandler::decryptText(CryptoKeyHandler::hexTobin($value), $this->user_stats_table_encryption_key);
			$value++;
			$data["value"] = CryptoKeyHandler::binToHex(CryptoKeyHandler::encryptText($value, $this->user_stats_table_encryption_key));
		}
		
		return $this->insertUserStat($data);
	}
	
	public function getUsedActionsTotal() {
		$data = $this->getUserStat("used_actions_total");
		$value = isset($data["value"]) ? $data["value"] : null;
		return $data ? CryptoKeyHandler::decryptText(CryptoKeyHandler::hexTobin($value), $this->user_stats_table_encryption_key) : null;
	}
	
	public function isActionsMaximumNumberReached() {
		$li = $this->EVC->getPresentationLayer()->getPHPFrameWork()->getLicenceInfo();
		$actions_total = $this->getUsedActionsTotal();
		$amn = isset($li["amn"]) ? $li["amn"] : null;
		
		return $amn != -1 && $actions_total >= $amn;
	}
	
	public static function checkActionsMaxNum($UserAuthenticationHandler) {
		//php -r '$string="\$reached = \$UserAuthenticationHandler->isActionsMaximumNumberReached(); \$msg=\"You reached the maximum number of actions that your licence allow.\";"; for($i=0; $i < strlen($string); $i++) echo dechex(ord($string[$i]));echo "\n";'
		$hash = "2472656163686564203d20245573657241757468656e7469636174696f6e48616e646c65722d3e6973416374696f6e734d6178696d756d4e756d6265725265616368656428293b20246d73673d22596f75207265616368656420746865206d6178696d756d206e756d626572206f6620616374696f6e73207468617420796f7572206c6963656e636520616c6c6f772e223b";
		$code = "";
		for ($i = 0, $l = strlen($hash); $i < $l; $i += 2)
			$code .= chr( hexdec($hash[$i] . ($i+1 < $l ? $hash[$i+1] : "") ) );
		
		eval($code);
		
		if ($reached) {
			echo $msg;
			die(1);
		}
	}
	
	public function isAllowedDomain() {
		$li = $this->EVC->getPresentationLayer()->getPHPFrameWork()->getLicenceInfo();
		
		$cadp = isset($li["cadp"]) ? $li["cadp"] : null;
		
		$ad = isset($li["ad"]) ? str_replace(";", ",", trim($li["ad"])) : "";
		$ad .= $ad ? "," : "";
		$ad = preg_replace("/:80,/", ",", $ad);
		$ad = !$cadp ? preg_replace("/:[0-9]+,/", ",", $ad) : $ad;
		$ad = strtolower($ad);
		$ad = preg_replace("/,+/", ",", preg_replace("/(^,|,$)/", "", preg_replace("/\s*,\s*/", ",", $ad)));
		
		$hh = isset($_SERVER["HTTP_HOST"]) ? $_SERVER["HTTP_HOST"] : null;
		$status = !empty($hh);
		
		if ($status && $ad) {
			$hh = preg_replace("/:80$/", "", $hh);
			$hh = !$cadp ? preg_replace("/:[0-9]+$/", "", $hh) : $hh;
			$hh = strtolower($hh);
			
			$parts = explode(",", $ad);
			$status = array_search($hh, $parts) !== false;
			
			//check sub domain
			if (!$status)
				foreach ($parts as $part) {
					$part = trim($part);
					
					if ($part && strpos("$hh,", ".$part,") !== false) {
						$status = true;
						break;
					}
				}
		}
		
		return $status;
	}
	
	public function isAllowedPath() {
		$li = $this->EVC->getPresentationLayer()->getPHPFrameWork()->getLicenceInfo();
		
		$ap = isset($li["ap"]) ? str_replace(";", ",", preg_replace("/\/+/", "/", trim($li["ap"]))) : "";
		$cp = preg_replace("/\/+$/", "", preg_replace("/\/+/", "/", CMS_PATH)); //remove repeated and last slash
		return !$ap || preg_match("/,s*" . str_replace("/", "\\/", str_replace(".", "\\.", $cp)) . "\/?s*,/i", ",$ap,");
	}
	
	public static function getValidUrlsBack() {
		return array(
			"/admin",
			"/admin/admin_uis",
			"/admin/about",
			"/admin/admin_home",
			"/admin/admin_home_project",
			"/admin/choose_available_project",
			"/admin/edit_file_class",
			"/admin/edit_file_class_method",
			"/admin/edit_file_function",
			"/admin/edit_file_includes",
			"/admin/edit_raw_file",
			"/admin/feedback",
			"/admin/install_dependencies",
			"/admin/install_module",
			"/admin/install_program",
			"/admin/upload_file",
			"/admin/view_file",
			"/businesslogic/create_business_logic_objs_automatically",
			"/businesslogic/edit_file",
			"/businesslogic/edit_function",
			"/businesslogic/edit_includes",
			"/businesslogic/edit_method",
			"/businesslogic/edit_service",
			"/cms/wordpress/.*",
			"/dataaccess/create_data_access_objs_automatically",
			"/dataaccess/edit_file",
			"/dataaccess/edit_hbn_obj",
			"/dataaccess/edit_includes",
			"/dataaccess/edit_map",
			"/dataaccess/edit_query",
			"/dataaccess/edit_relationship",
			"/db/create_diagram_sql",
			"/db/db_dump",
			"/db/diagram",
			"/db/edit_table",
			"/db/edit_broker_table",
			"/db/execute_sql",
			"/db/export_table_data",
			"/db/generate_table_with_ai",
			"/db/import_table_data",
			"/db/manage_records",
			"/db/phpmyadmin",
			"/db/set_db_settings",
			"/deployment/",
			"/diff/",
			"/docbook/",
			"/docbook/file_code",
			"/docbook/file_docbook",
			"/layer/diagram",
			"/presentation/convert_url_to_template",
			"/presentation/create_block",
			"/presentation/create_entity",
			"/presentation/create_page_module_block",
			"/presentation/create_page_presentation_uis_diagram_block",
			"/presentation/create_presentation_uis_automatically",
			"/presentation/create_presentation_uis_diagram",
			"/presentation/create_project",
			"/presentation/edit_block",
			"/presentation/edit_config",
			"/presentation/edit_entity",
			"/presentation/edit_init",
			"/presentation/edit_page_module_block",
			"/presentation/edit_project_details",
			"/presentation/edit_project_global_variables",
			"/presentation/edit_simple_template_layout",
			"/presentation/edit_template",
			"/presentation/edit_util",
			"/presentation/edit_view",
			"/presentation/generate_template_with_ai",
			"/presentation/install_page",
			"/presentation/install_template",
			"/presentation/list",
			"/presentation/manage_projects",
			"/presentation/manage_references",
			"/setup",
			"/testunit/",
			"/testunit/edit_test",
			"/user/change_auth_settings",
			"/user/change_db_keys",
			"/user/edit_layout_type",
			"/user/edit_login_control",
			"/user/edit_object_type",
			"/user/edit_permission",
			"/user/edit_reserved_db_table_name",
			"/user/edit_user",
			"/user/edit_user_type",
			"/user/edit_user_user_type",
			"/user/manage_layout_type_permissions",
			"/user/manage_layout_types",
			"/user/manage_login_controls",
			"/user/manage_object_types",
			"/user/manage_permissions",
			"/user/manage_reserved_db_table_names",
			"/user/manage_users",
			"/user/manage_user_type_permissions",
			"/user/manage_user_types",
			"/user/manage_user_user_types",
		);
	}
}
?>
