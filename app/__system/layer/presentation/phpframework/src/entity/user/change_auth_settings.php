<?php
include_once $EVC->getUtilPath("PHPVariablesFileHandler");
include_once $EVC->getUtilPath("WorkFlowBeansFileHandler");

$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "access");

//getting all available drivers
$WorkFlowBeansFileHandler = new WorkFlowBeansFileHandler(SYSTEM_BEAN_PATH . "db_layer.xml", GLOBAL_VARIABLES_PROPERTIES_FILE_PATH);
$WorkFlowBeansFileHandler->init();
$db_layer_brokers_names = $WorkFlowBeansFileHandler->getBeanBrokersReferences("DBLayer");

$available_drivers = array();

if ($db_layer_brokers_names)
	foreach ($db_layer_brokers_names as $driver_name => $driver_reference) 
		if ($driver_name)
			$available_drivers[$driver_name] = $driver_reference ? $driver_reference : $driver_name;

if (!$available_drivers && !$is_local_db) {
	launch_exception(new Exception("No DB Drivers in __system. Please fix your internal bean xml files!"));
	die();
}

//getting current db credentials
$authentication_db_driver = !empty($GLOBALS["default_db_driver"]) ? $GLOBALS["default_db_driver"] : "";
$authentication_db_extension = isset($GLOBALS[$authentication_db_driver . "_db_extension"]) ? $GLOBALS[$authentication_db_driver . "_db_extension"] : null;
$authentication_db_host = isset($GLOBALS[$authentication_db_driver . "_db_host"]) ? $GLOBALS[$authentication_db_driver . "_db_host"] : null;
$authentication_db_name = isset($GLOBALS[$authentication_db_driver . "_db_name"]) ? $GLOBALS[$authentication_db_driver . "_db_name"] : null;
$authentication_db_username = isset($GLOBALS[$authentication_db_driver . "_db_username"]) ? $GLOBALS[$authentication_db_driver . "_db_username"] : null;
$authentication_db_password = isset($GLOBALS[$authentication_db_driver . "_db_password"]) ? $GLOBALS[$authentication_db_driver . "_db_password"] : null;
$authentication_db_port = isset($GLOBALS[$authentication_db_driver . "_db_port"]) ? $GLOBALS[$authentication_db_driver . "_db_port"] : null;
$authentication_db_persistent = isset($GLOBALS[$authentication_db_driver . "_db_persistent"]) ? $GLOBALS[$authentication_db_driver . "_db_persistent"] : null;
$authentication_db_new_link = isset($GLOBALS[$authentication_db_driver . "_db_new_link"]) ? $GLOBALS[$authentication_db_driver . "_db_new_link"] : null;
$authentication_db_reconnect = isset($GLOBALS[$authentication_db_driver . "_db_reconnect"]) ? $GLOBALS[$authentication_db_driver . "_db_reconnect"] : null;
$authentication_db_encoding = isset($GLOBALS[$authentication_db_driver . "_db_encoding"]) ? $GLOBALS[$authentication_db_driver . "_db_encoding"] : null;
$authentication_db_schema = isset($GLOBALS[$authentication_db_driver . "_db_schema"]) ? $GLOBALS[$authentication_db_driver . "_db_schema"] : null;
$authentication_db_odbc_data_source = isset($GLOBALS[$authentication_db_driver . "_db_odbc_data_source"]) ? $GLOBALS[$authentication_db_driver . "_db_odbc_data_source"] : null;
$authentication_db_odbc_driver = isset($GLOBALS[$authentication_db_driver . "_db_odbc_driver"]) ? $GLOBALS[$authentication_db_driver . "_db_odbc_driver"] : null;
$authentication_db_extra_dsn = isset($GLOBALS[$authentication_db_driver . "_db_extra_dsn"]) ? $GLOBALS[$authentication_db_driver . "_db_extra_dsn"] : null;
$authentication_db_extra_settings = isset($GLOBALS[$authentication_db_driver . "_db_extra_settings"]) ? $GLOBALS[$authentication_db_driver . "_db_extra_settings"] : null;

//posting new data
if (!empty($_POST)) {
	$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "write");
	
	$is_local_db_bkp = $is_local_db;
	
	$maximum_failed_attempts = isset($_POST["maximum_failed_attempts"]) ? $_POST["maximum_failed_attempts"] : null;
	$user_blocked_expired_time = isset($_POST["user_blocked_expired_time"]) ? $_POST["user_blocked_expired_time"] : null;
	$login_expired_time = isset($_POST["login_expired_time"]) ? $_POST["login_expired_time"] : null;
	$auth_db_path = isset($_POST["auth_db_path"]) ? $_POST["auth_db_path"] : null;
	$is_local_db = isset($_POST["is_local_db"]) ? $_POST["is_local_db"] : null;
	$authentication_db_driver = isset($_POST["authentication_db_driver"]) ? $_POST["authentication_db_driver"] : null;
	$authentication_db_extension = isset($_POST["authentication_db_extension"]) ? $_POST["authentication_db_extension"] : null;
	$authentication_db_host = isset($_POST["authentication_db_host"]) ? $_POST["authentication_db_host"] : null;
	$authentication_db_port = isset($_POST["authentication_db_port"]) ? $_POST["authentication_db_port"] : null;
	$authentication_db_name = isset($_POST["authentication_db_name"]) ? $_POST["authentication_db_name"] : null;
	$authentication_db_username = isset($_POST["authentication_db_username"]) ? $_POST["authentication_db_username"] : null;
	$authentication_db_password = isset($_POST["authentication_db_password"]) ? $_POST["authentication_db_password"] : null;
	$authentication_db_persistent = isset($_POST["authentication_db_persistent"]) ? $_POST["authentication_db_persistent"] : null;
	$authentication_db_new_link = isset($_POST["authentication_db_new_link"]) ? $_POST["authentication_db_new_link"] : null;
	$authentication_db_reconnect = isset($_POST["authentication_db_reconnect"]) ? $_POST["authentication_db_reconnect"] : null;
	$authentication_db_encoding = isset($_POST["authentication_db_encoding"]) ? $_POST["authentication_db_encoding"] : null;
	$authentication_db_schema = isset($_POST["authentication_db_schema"]) ? $_POST["authentication_db_schema"] : null;
	$authentication_db_odbc_data_source = isset($_POST["authentication_db_odbc_data_source"]) ? $_POST["authentication_db_odbc_data_source"] : null;
	$authentication_db_odbc_driver = isset($_POST["authentication_db_odbc_driver"]) ? $_POST["authentication_db_odbc_driver"] : null;
	$authentication_db_extra_dsn = isset($_POST["authentication_db_extra_dsn"]) ? $_POST["authentication_db_extra_dsn"] : null;
	$authentication_db_extra_settings = isset($_POST["authentication_db_extra_settings"]) ? $_POST["authentication_db_extra_settings"] : null;
	
	if (!is_numeric($maximum_failed_attempts) || $maximum_failed_attempts < 0)
		$error_message = "Maximum # of Failed Attempts must be numeric and bigger or equal than 0! Please try again...";
	else if (!is_numeric($user_blocked_expired_time) || $user_blocked_expired_time < 0)
		$error_message = "User Blocked Expired Time must be numeric and bigger or equal than 0! Please try again...";
	else if (!is_numeric($login_expired_time) || $login_expired_time < 0)
		$error_message = "Login Expired Time must be numeric and bigger or equal than 0! Please try again...";
	else {
		$authentication_config_file_path = $EVC->getConfigPath("authentication");
	
		if (file_exists($authentication_config_file_path)) {
			//Preparing auth settings
			$code = file_get_contents($authentication_config_file_path);
			
			replaceVarInCode($code, "maximum_failed_attempts", $maximum_failed_attempts);
			replaceVarInCode($code, "user_blocked_expired_time", $user_blocked_expired_time);
			replaceVarInCode($code, "login_expired_time", $login_expired_time);
			replaceVarInCode($code, "is_local_db", $is_local_db ? "true" : "false");
			
			//Preparing auth_db_path
			if ($auth_db_path) {
				if (substr($auth_db_path, 0, 1) == "/")
					replaceVarInCode($code, "authentication_db_path", '"' . $auth_db_path . '"');
				else {
					$auth_db_path = CMS_PATH . $auth_db_path;
					
					if (strpos($auth_db_path, SYSTEM_PATH) !== false)
						$auth_db_path_str = 'SYSTEM_PATH . "' . str_replace(SYSTEM_PATH, "", $auth_db_path) . '"';
					else if (strpos($auth_db_path, LAYER_PATH) !== false)
						$auth_db_path_str = 'LAYER_PATH . "' . str_replace(LAYER_PATH, "", $auth_db_path) . '"';
					else if (strpos($auth_db_path, APP_PATH) !== false) 
						$auth_db_path_str = 'APP_PATH . "' . str_replace(APP_PATH, "", $auth_db_path) . '"';
					else
						$auth_db_path_str = 'CMS_PATH . "' . str_replace(CMS_PATH, "", $auth_db_path) . '"';
					
					replaceVarInCode($code, "authentication_db_path", $auth_db_path_str);
				}
			}
			
			//Saving new settings to file
			if (file_put_contents($authentication_config_file_path, $code) !== false) {
				//Preparing db credentials
				$global_variables = PHPVariablesFileHandler::getVarsFromFileContent(GLOBAL_VARIABLES_PROPERTIES_FILE_PATH);
				$global_variables["default_db_driver"] = $authentication_db_driver;
				$global_variables[$authentication_db_driver . "_db_extension"] = $authentication_db_extension;
				$global_variables[$authentication_db_driver . "_db_host"] = $authentication_db_host;
				$global_variables[$authentication_db_driver . "_db_port"] = $authentication_db_port;
				$global_variables[$authentication_db_driver . "_db_name"] = $authentication_db_name;
				$global_variables[$authentication_db_driver . "_db_username"] = $authentication_db_username;
				$global_variables[$authentication_db_driver . "_db_password"] = $authentication_db_password;
				$global_variables[$authentication_db_driver . "_db_persistent"] = $authentication_db_persistent;
				$global_variables[$authentication_db_driver . "_db_new_link"] = $authentication_db_new_link;
				$global_variables[$authentication_db_driver . "_db_reconnect"] = $authentication_db_reconnect;
				$global_variables[$authentication_db_driver . "_db_encoding"] = $authentication_db_encoding;
				$global_variables[$authentication_db_driver . "_db_schema"] = $authentication_db_schema;
				$global_variables[$authentication_db_driver . "_db_odbc_data_source"] = $authentication_db_odbc_data_source;
				$global_variables[$authentication_db_driver . "_db_odbc_driver"] = $authentication_db_odbc_driver;
				$global_variables[$authentication_db_driver . "_db_extra_dsn"] = $authentication_db_extra_dsn;
				$global_variables[$authentication_db_driver . "_db_extra_settings"] = $authentication_db_extra_settings;
				
				//Saving db credentials
				if (PHPVariablesFileHandler::saveVarsToFile(GLOBAL_VARIABLES_PROPERTIES_FILE_PATH, $global_variables, true)) {
					//Move Local DB to Remote DB or vice versa
					try {
						if ($UserAuthenticationHandler->moveLocalDBToRemoteDBOrViceVersa($is_local_db, $global_variables)) {
							//Move Local DB to another location
							if ($UserAuthenticationHandler->moveLocalDBToAnotherFolder($auth_db_path)) {
								$authentication_db_path = $auth_db_path;
								$status_message = "Auth Settings changed successfully...";
							}
							else {
								//Putting back old auth db path
								replaceVarInCode($code, "authentication_db_path", '"' . $authentication_db_path . '"');
								file_put_contents($authentication_config_file_path, $code);
								
								$error_message = "There was an error trying to move Local DB to another location. Please try again...";
							}
						}
						else
							$error_message = "There was an error trying to move " . ($is_local_db ? "Remote DB to Local DB" : "Local DB to Remote DB") . ". Please try again...";
					}
					catch(Exception $e) {
						if ($is_local_db != $is_local_db_bkp) {
							replaceVarInCode($code, "is_local_db", $is_local_db_bkp ? "true" : "false");
							file_put_contents($authentication_config_file_path, $code);
						}
						
						$error_message = $e->getMessage();
						debug_log($e->getMessage() . (!empty($e->problem) ? "\n" . $e->problem : ""), "exception");
						//launch_exception($e);
					}
				}
				else
					$error_message = "There was an error trying to save DB credentials to global variables. Please try again...";
			}
			else
				$error_message = "There was an error trying to change the auth settings. Please try again...";
		}
		else
			$error_message = "Config Authentication file doesn't exist. Please talk with the SysAdmin for further information...";
	}
}

//Preparing default values
$data = array();
$data["maximum_failed_attempts"] = $maximum_failed_attempts;
$data["user_blocked_expired_time"] = $user_blocked_expired_time;
$data["login_expired_time"] = $login_expired_time;
$data["auth_db_path"] = str_replace(CMS_PATH, "", $authentication_db_path);
$data["is_local_db"] = $is_local_db;
$data["authentication_db_driver"] = $authentication_db_driver;
$data["authentication_db_extension"] = $authentication_db_extension;
$data["authentication_db_host"] = $authentication_db_host;
$data["authentication_db_port"] = $authentication_db_port;
$data["authentication_db_name"] = $authentication_db_name;
$data["authentication_db_username"] = $authentication_db_username;
$data["authentication_db_password"] = $authentication_db_password;
$data["authentication_db_persistent"] = $authentication_db_persistent;
$data["authentication_db_new_link"] = $authentication_db_new_link;
$data["authentication_db_reconnect"] = $authentication_db_reconnect;
$data["authentication_db_encoding"] = $authentication_db_encoding;
$data["authentication_db_schema"] = $authentication_db_schema;
$data["authentication_db_odbc_data_source"] = $authentication_db_odbc_data_source;
$data["authentication_db_odbc_driver"] = $authentication_db_odbc_driver;
$data["authentication_db_extra_dsn"] = $authentication_db_extra_dsn;
$data["authentication_db_extra_settings"] = $authentication_db_extra_settings;

//Preparing local and remote options
$local_and_remote_options = array(
	array("value" => "1", "label" => "YES, IS A LOCAL DB!")
);

if ($available_drivers)
	$local_and_remote_options[] = array("value" => "", "label" => "NO, IS A REMOTE DB!");

//preparing db extensions
$drivers_extensions = DB::getAllExtensionsByType();
$available_extensions_options = array();

if ($authentication_db_driver && isset($drivers_extensions[$authentication_db_driver]) && is_array($drivers_extensions[$authentication_db_driver]))
	foreach ($drivers_extensions[$authentication_db_driver] as $idx => $enc)
		$available_extensions_options[] = array("value" => $enc, "label" => $enc . ($idx == 0 ? " - Default" : ""));

if ($authentication_db_extension && (empty($drivers_extensions[$authentication_db_driver]) || !in_array($authentication_db_extension, $drivers_extensions[$authentication_db_driver])))
	$available_extensions_options[] = array("value" => $authentication_db_extension, "label" => $authentication_db_extension . " - DEPRECATED");

//preparing db encodings
$drivers_encodings = DB::getAllDBConnectionEncodingsByType();
$available_encodings_options = array(array("value" => "", "label" => "-- Default --"));

if ($authentication_db_driver && isset($drivers_encodings[$authentication_db_driver]) && is_array($drivers_encodings[$authentication_db_driver]))
	foreach ($drivers_encodings[$authentication_db_driver] as $enc => $label)
		$available_encodings_options[] = array("value" => $enc, "label" => $label);

if ($authentication_db_encoding && (empty($drivers_encodings[$authentication_db_driver]) || !array_key_exists($authentication_db_encoding, $drivers_encodings[$authentication_db_driver])))
	$available_encodings_options[] = array("value" => $authentication_db_encoding, "label" => $authentication_db_encoding . " - DEPRECATED");

//preparing ignore db options
$drivers_ignore_connection_options = DB::getAllIgnoreConnectionOptionsByType();
$drivers_ignore_connection_options_by_extension = DB::getAllIgnoreConnectionOptionsByExtensionAndType();

//functions
function replaceVarInCode(&$code, $var_name, $var_value) {
	if (strpos($code, '$' . $var_name) !== false)
		$code = preg_replace('/\$' . $var_name . '\s*=\s*([^;]+);/u', '$' . $var_name . ' = ' . $var_value . ';', $code); //'/u' means with accents and ç too.
	else
		$code = str_replace("?>", '$' . $var_name . ' = ' . $var_value . ';' . "\n?>", $code, $count = 1);
}
?>
