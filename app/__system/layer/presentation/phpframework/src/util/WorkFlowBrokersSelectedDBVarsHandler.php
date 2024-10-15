<?php

class WorkFlowBrokersSelectedDBVarsHandler {
	
	public static function getBrokersSelectedDBVars($brokers) {
		$db_brokers_drivers = array();
		$selected_dal_broker = null;
		$selected_db_driver = null;
		$selected_type = "db"; // db or diagram
		
		if ($brokers) 
			foreach ($brokers as $broker_name => $broker)
				if (is_a($broker, "IDataAccessBrokerClient") || is_a($broker, "IDBBrokerClient")) {
					$db_brokers_drivers[$broker_name] = is_a($broker, "IDBBrokerClient") ? $broker->getDBDriversName() : $broker->getBrokersDBDriversName();
					
					if (empty($selected_dal_broker)) {
						$selected_dal_broker = $broker_name;
						
					 	if (!empty($GLOBALS["default_db_driver"]) && in_array($GLOBALS["default_db_driver"], $db_brokers_drivers[$broker_name]))
							$selected_db_driver = $GLOBALS["default_db_driver"];
						else if (!$selected_db_driver)
							$selected_db_driver = isset($db_brokers_drivers[$broker_name][0]) ? $db_brokers_drivers[$broker_name][0] : null;
					}
				}
		
		return array(
			"db_brokers_drivers" => $db_brokers_drivers,
			"dal_broker" => $selected_dal_broker,
			"db_driver" => $selected_db_driver,
			"type" => $selected_type,
		);
	}
	
	public static function printSelectedDBVarsJavascriptCode($project_url_prefix, $bean_name, $bean_file_name, $selected_db_vars) {
		$code = '';
		
		if ($project_url_prefix && $bean_name && $bean_file_name)
			$code .= 'var get_broker_db_data_url = typeof get_broker_db_data_url != "undefined" && get_broker_db_data_url ? get_broker_db_data_url : "' . $project_url_prefix . 'phpframework/dataaccess/get_broker_db_data?bean_name=' . $bean_name . '&bean_file_name=' . $bean_file_name . '";';
		
		if ($selected_db_vars) {
			if (array_key_exists("dal_broker", $selected_db_vars)) 
				$code .= 'var default_dal_broker = "' . (isset($selected_db_vars["dal_broker"]) ? $selected_db_vars["dal_broker"] : "") . '";';
			
			if (array_key_exists("db_driver", $selected_db_vars)) 
				$code .= 'var default_db_driver = "' . (isset($selected_db_vars["db_driver"]) ? $selected_db_vars["db_driver"] : "") . '";';
			
			if (array_key_exists("type", $selected_db_vars)) 
				$code .= 'var default_db_type = "' . (isset($selected_db_vars["type"]) ? $selected_db_vars["type"] : "") . '";';
			
			if (array_key_exists("db_table", $selected_db_vars)) 
				$code .= 'var default_db_table = "' . (isset($selected_db_vars["db_table"]) ? $selected_db_vars["db_table"] : "") . '";';
			
			if (!empty($selected_db_vars["db_brokers_drivers"])) {
				$code .= '
				if (typeof db_brokers_drivers_tables_attributes == "undefined") {
					var db_brokers_drivers_tables_attributes = {};';
				
				foreach ($selected_db_vars["db_brokers_drivers"] as $db_broker => $db_driver_names) {
					$code .= 'db_brokers_drivers_tables_attributes["' . $db_broker . '"] = {};';
					
					if ($db_driver_names) {
						$t = count($db_driver_names);
						
						for ($i = 0; $i < $t; $i++)
							$code .= 'db_brokers_drivers_tables_attributes["' . $db_broker . '"]["' . $db_driver_names[$i] . '"] = {
								db: {},
								diagram: {}
							};';
					}
				}
				
				$code .= '}';
			}
			
			return $code;
		}
		
		return "";
	}
}
?>
