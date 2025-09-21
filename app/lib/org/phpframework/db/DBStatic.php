<?php
include_once get_lib("org.phpframework.util.text.TextSanitizer");
include_once get_lib("org.phpframework.db.SQLQueryHandler");

trait DBStatic { 
	/* Private Variables */
	
	private static $saved_data_by_func = array();
	
	/* Abstract Static Methods */
	/* Abstract Static Methods - In DB Driver */
	abstract public static function getType();
	abstract public static function getLabel();
	abstract public static function getEnclosingDelimiters();
	abstract public static function getAliasEnclosingDelimiters();
	abstract public static function getDBConnectionEncodings();
	abstract public static function getTableCharsets();
	abstract public static function getColumnCharsets();
	abstract public static function getTableCollations();
	abstract public static function getColumnCollations();
	abstract public static function getStorageEngines();
	abstract public static function getPHPToDBColumnTypes();
	abstract public static function getDBToPHPColumnTypes();
	abstract public static function getDBColumnTypes();
	abstract public static function getDBColumnSimpleTypes();
	abstract public static function getDBColumnDefaultValuesByType();
	abstract public static function getDBColumnTypesIgnoredProps();
	abstract public static function getDBColumnTypesHiddenProps();
	abstract public static function getDBColumnNumericTypes();
	abstract public static function getDBColumnDateTypes();
	abstract public static function getDBColumnTextTypes();
	abstract public static function getDBColumnBlobTypes();
	abstract public static function getDBColumnBooleanTypes();
	abstract public static function getDBColumnMandatoryLengthTypes();
	abstract public static function getDBColumnAutoIncrementTypes();
	abstract public static function getDBBooleanTypeAvailableValues();
	abstract public static function getDBCurrentTimestampAvailableValues();
	abstract public static function getAttributeValueReservedWords();
	abstract public static function getReservedWords();
	abstract public static function getDefaultSchema();
	abstract public static function getIgnoreConnectionOptions();
	abstract public static function getIgnoreConnectionOptionsByExtension();
	abstract public static function getAvailablePHPExtensionTypes();
	abstract public static function allowTableAttributeSorting();
	abstract public static function allowModifyTableEncoding();
	abstract public static function allowModifyTableStorageEngine();
	
	/* Abstract Static Methods - In DB Driver Statement */
	abstract public static function getCreateDBStatement($db_name, $options = false);
	abstract public static function getDropDatabaseStatement($db_name, $options = false);
	abstract public static function getSelectedDBStatement($options = false);
	abstract public static function getDBsStatement($options = false);
	abstract public static function getTablesStatement($db_name = false, $options = false);
	abstract public static function getTableFieldsStatement($table, $db_name = false, $options = false);
	abstract public static function getForeignKeysStatement($table, $db_name = false, $options = false);
	abstract public static function getCreateTableStatement($table_data, $options = false);
	abstract public static function getCreateTableAttributeStatement($attribute_data, $options = false, &$parsed_data = array());
	abstract public static function getRenameTableStatement($old_table, $new_table, $options = false);
	abstract public static function getModifyTableEncodingStatement($table, $charset, $collation, $options = false);
	abstract public static function getModifyTableStorageEngineStatement($table, $engine, $options = false);
	abstract public static function getDropTableStatement($table, $options = false);
	abstract public static function getDropTableCascadeStatement($table, $options = false);
	abstract public static function getAddTableAttributeStatement($table, $attribute_data, $options = false);
	abstract public static function getModifyTableAttributeStatement($table, $attribute_data, $options = false);
	abstract public static function getRenameTableAttributeStatement($table, $old_attribute, $new_attribute, $options = false);
	abstract public static function getDropTableAttributeStatement($table, $attribute, $options = false);
	abstract public static function getAddTablePrimaryKeysStatement($table, $attributes, $options = false);
	abstract public static function getDropTablePrimaryKeysStatement($table, $options = false);
	abstract public static function getAddTableForeignKeyStatement($table, $fk, $options = false);
	abstract public static function getDropTableForeignKeysStatement($table, $options = false);
	abstract public static function getDropTableForeignConstraintStatement($table, $constraint_name, $options = false);
	abstract public static function getAddTableIndexStatement($table, $attributes, $options = false);
	abstract public static function getLoadTableDataFromFileStatement($file_path, $table, $options = false);
	abstract public static function getShowCreateTableStatement($table, $options = false);
	abstract public static function getShowCreateViewStatement($view, $options = false);
	abstract public static function getShowCreateTriggerStatement($trigger, $options = false);
	abstract public static function getShowCreateProcedureStatement($procedure, $options = false);
	abstract public static function getShowCreateFunctionStatement($function, $options = false);
	abstract public static function getShowCreateEventStatement($event, $options = false);
	abstract public static function getShowTablesStatement($db_name, $options = false);
	abstract public static function getShowViewsStatement($db_name, $options = false);
	abstract public static function getShowTriggersStatement($db_name, $options = false);
	abstract public static function getShowTableColumnsStatement($table, $db_name = false, $options = false);
	abstract public static function getShowForeignKeysStatement($table, $db_name = false, $options = false);
	abstract public static function getShowProceduresStatement($db_name, $options = false);
	abstract public static function getShowFunctionsStatement($db_name, $options = false);
	abstract public static function getShowEventsStatement($db_name, $options = false);
	abstract public static function getSetupTransactionStatement($options = false);
	abstract public static function getStartTransactionStatement($options = false);
	abstract public static function getCommitTransactionStatement($options = false);
	abstract public static function getStartDisableAutocommitStatement($options = false);
	abstract public static function getEndDisableAutocommitStatement($options = false);
	abstract public static function getStartLockTableWriteStatement($table, $options = false);
	abstract public static function getStartLockTableReadStatement($table, $options = false);
	abstract public static function getEndLockTableStatement($options = false);
	abstract public static function getStartDisableKeysStatement($table, $options = false);
	abstract public static function getEndDisableKeysStatement($table, $options = false);
	abstract public static function getDropTriggerStatement($trigger, $options = false);
	abstract public static function getDropProcedureStatement($procedure, $options = false);
	abstract public static function getDropFunctionStatement($function, $options = false);
	abstract public static function getDropEventStatement($event, $options = false);
	abstract public static function getDropViewStatement($view, $options = false);
	abstract public static function getShowTableCharsetsStatement($options = false);
	abstract public static function getShowColumnCharsetsStatement($options = false);
	abstract public static function getShowTableCollationsStatement($options = false);
	abstract public static function getShowColumnCollationsStatement($options = false);
	abstract public static function getShowDBStorageEnginesStatement($options = false);
	
	/* Public Static Methods */
	
	public static function getDriverClassNameByPath($driver_path) {
		$parts = explode(".", $driver_path);
		return $parts[ count($parts) - 1 ];
	}
	
	public static function getDriverTypeByClassName($driver_class) {
		if (!class_exists($driver_class))
			include_once get_lib("org.phpframework.db.driver.$driver_class");
		
		return $driver_class::getType();
	}
	
	public static function getDriverTypeByPath($driver_path) {
		$driver_class = self::getDriverClassNameByPath($driver_path);
		return $driver_class ? self::getDriverTypeByClassName($driver_class) : null;
	}
	
	public static function getAvailableDriverClassNames() {
		//return array("MySqlDB", "PostgresDB", "MSSqlDB");
		if (!empty(self::$saved_data_by_func["getAvailableDriverClassNames"]))
			return self::$saved_data_by_func["getAvailableDriverClassNames"];
		
		$files = array_diff(scandir(__DIR__ . "/driver"), array('..', '.'));
		$driver_names = array();
		
		foreach ($files as $file)
			$driver_names[] = pathinfo($file, PATHINFO_FILENAME);
		
		self::$saved_data_by_func["getAvailableDriverClassNames"] = $driver_names;
		
		return $driver_names;
	}
	
	public static function getDriverClassNameByType($type) {
		$drivers = self::getAvailableDriverClassNames();
		
		foreach ($drivers as $driver_class) {
			if (!class_exists($driver_class))
				include_once get_lib("org.phpframework.db.driver.$driver_class");
			
			if ($type == $driver_class::getType())
				return $driver_class;
		}
		
		return null;
	}
	
	public static function getDriverPathByType($type) {
		$driver_class = self::getDriverClassNameByType($type);
		
		return $driver_class ? "org.phpframework.db.driver.$driver_class": "";
	}
	
	public static function createDriverByType($type) {
		$driver_class = self::getDriverClassNameByType($type);
		
		return $driver_class ? new $driver_class() : null;
	}
	
	public static function convertDSNToOptions($dsn) {
		if (empty($dsn) || (false === ($pos = strpos($dsn, ":"))))
			return null;
		
		$options = array(
			"extension" => strtolower(substr($dsn, 0, $pos)),
		);
		
		$dsn = substr($dsn, $pos + 1);
		$parts = explode(";", $dsn);
		
		foreach ($parts as $part) {
			$arr = explode("=", $part);
			$options[ strtolower($arr[0]) ] = isset($arr[1]) ? $arr[1] : null;
		}
		
		if (empty($options['host']) && !empty($options['unix_socket']))
			$options["host"] = $options['unix_socket'];
		
		if (empty($options["host"]) && !empty($options['server'])) {
			$parts = explode(":", $options['server']);
			$options['host'] = $parts[0];
			
			if (!empty($parts[1]) && empty($options['port']))
				$options['port'] = $parts[1];
		}
		
		if (empty($options['dbname']) && !empty($options['database']))
			$options['dbname'] = $options['database'];
		
		return $options;
	}
	
	public static function getDSNByType($type, $options) {
		$obj = self::createDriverByType($type);
		return $obj ? $obj->getDSN($options) : null;
	}
	
	public static function getAllDriverLabelsByType() {
		if (!empty(self::$saved_data_by_func["getAllDriverLabelsByType"]))
			return self::$saved_data_by_func["getAllDriverLabelsByType"];
		
		$drivers = self::getAvailableDriverClassNames();
		$items = array();
		
		foreach ($drivers as $driver_class) {
			if (!class_exists($driver_class))
				include_once get_lib("org.phpframework.db.driver.$driver_class");
			
			$type = $driver_class::getType();
			$items[$type] = $driver_class::getLabel();
		}
		
		self::$saved_data_by_func["getAllDriverLabelsByType"] = $items;
		
		return $items;
	}
	
	public static function getAllDBConnectionEncodingsByType() {  //change this to AllDBCharsets
		if (!empty(self::$saved_data_by_func["getAllDBConnectionEncodingsByType"]))
			return self::$saved_data_by_func["getAllDBConnectionEncodingsByType"];
		
		$drivers = self::getAvailableDriverClassNames();
		$items = array();
		
		foreach ($drivers as $driver_class) {
			if (!class_exists($driver_class))
				include_once get_lib("org.phpframework.db.driver.$driver_class");
			
			$type = $driver_class::getType();
			$items[$type] = $driver_class::getDBConnectionEncodings();
		}
		
		self::$saved_data_by_func["getAllDBConnectionEncodingsByType"] = $items;
		
		return $items;
	}
	
	public static function getAllStorageEnginesByType() {
		if (!empty(self::$saved_data_by_func["getAllStorageEnginesByType"]))
			return self::$saved_data_by_func["getAllStorageEnginesByType"];
		
		$drivers = self::getAvailableDriverClassNames();
		$items = array();
		
		foreach ($drivers as $driver_class) {
			if (!class_exists($driver_class))
				include_once get_lib("org.phpframework.db.driver.$driver_class");
			
			$type = $driver_class::getType();
			$items[$type] = $driver_class::getStorageEngines();
		}
		
		self::$saved_data_by_func["getAllStorageEnginesByType"] = $items;
		
		return $items;
	}
	
	public static function getAllExtensionsByType() {
		if (!empty(self::$saved_data_by_func["getAllExtensionsByType"]))
			return self::$saved_data_by_func["getAllExtensionsByType"];
		
		$drivers = self::getAvailableDriverClassNames();
		$items = array();
		
		foreach ($drivers as $driver_class) {
			if (!class_exists($driver_class))
				include_once get_lib("org.phpframework.db.driver.$driver_class");
			
			$type = $driver_class::getType();
			$items[$type] = $driver_class::getAvailablePHPExtensionTypes();
		}
		
		self::$saved_data_by_func["getAllExtensionsByType"] = $items;
		
		return $items;
	}
	
	public static function getAllIgnoreConnectionOptionsByType() {
		if (!empty(self::$saved_data_by_func["getAllIgnoreConnectionOptionsByType"]))
			return self::$saved_data_by_func["getAllIgnoreConnectionOptionsByType"];
		
		$drivers = self::getAvailableDriverClassNames();
		$items = array();
		
		foreach ($drivers as $driver_class) {
			if (!class_exists($driver_class))
				include_once get_lib("org.phpframework.db.driver.$driver_class");
			
			$type = $driver_class::getType();
			$items[$type] = $driver_class::getIgnoreConnectionOptions();
		}
		
		self::$saved_data_by_func["getAllIgnoreConnectionOptionsByType"] = $items;
		
		return $items;
	}
	
	public static function getAllIgnoreConnectionOptionsByExtensionAndType() {
		if (!empty(self::$saved_data_by_func["getAllIgnoreConnectionOptionsByExtensionAndType"]))
			return self::$saved_data_by_func["getAllIgnoreConnectionOptionsByExtensionAndType"];
		
		$drivers = self::getAvailableDriverClassNames();
		$items = array();
		
		foreach ($drivers as $driver_class) {
			if (!class_exists($driver_class))
				include_once get_lib("org.phpframework.db.driver.$driver_class");
			
			$type = $driver_class::getType();
			$items[$type] = $driver_class::getIgnoreConnectionOptionsByExtension();
		}
		
		self::$saved_data_by_func["getAllIgnoreConnectionOptionsByExtensionAndType"] = $items;
		
		return $items;
	}
	
	//Result will be grouped by driver type and then by column type
	public static function getAllColumnTypesByType() {
		if (!empty(self::$saved_data_by_func["getAllColumnTypesByType"]))
			return self::$saved_data_by_func["getAllColumnTypesByType"];
		
		$drivers = self::getAvailableDriverClassNames();
		$items = array();
		
		foreach ($drivers as $driver_class) {
			if (!class_exists($driver_class))
				include_once get_lib("org.phpframework.db.driver.$driver_class");
			
			$type = $driver_class::getType();
			$items[$type] = $driver_class::getDBColumnTypes();
		}
		
		self::$saved_data_by_func["getAllColumnTypesByType"] = $items;
		
		return $items;
	}
	
	//Result will be grouped by column type
	public static function getAllColumnTypes() {
		return self::mergeItems( self::getAllColumnTypesByType() );
	}
	
	//Result will be grouped by column type
	public static function getAllSharedColumnTypes() {
		return self::intersectItems( self::getAllColumnTypesByType() );
	}
	
	//Result will be grouped by driver type and then by column type
	public static function getAllColumnSimpleTypesByType() {
		if (!empty(self::$saved_data_by_func["getAllColumnSimpleTypesByType"]))
			return self::$saved_data_by_func["getAllColumnSimpleTypesByType"];
		
		$drivers = self::getAvailableDriverClassNames();
		$items = array();
		
		foreach ($drivers as $driver_class) {
			if (!class_exists($driver_class))
				include_once get_lib("org.phpframework.db.driver.$driver_class");
			
			$type = $driver_class::getType();
			$items[$type] = $driver_class::getDBColumnSimpleTypes();
		}
		
		self::$saved_data_by_func["getAllColumnSimpleTypesByType"] = $items;
		
		return $items;
	}
	
	//Result will be grouped by column type
	public static function getAllColumnSimpleTypes() {
		return self::mergeItems( self::getAllColumnSimpleTypesByType() );
	}
	
	//Result will be grouped by column type

	public static function getAllSharedColumnSimpleTypes() {
		return self::intersectItems( self::getAllColumnSimpleTypesByType() );
	}
	
	public static function getAllColumnNumericTypesByType() {
		if (!empty(self::$saved_data_by_func["getAllColumnNumericTypesByType"]))
			return self::$saved_data_by_func["getAllColumnNumericTypesByType"];
		
		$drivers = self::getAvailableDriverClassNames();
		$items = array();
		
		foreach ($drivers as $driver_class) {
			if (!class_exists($driver_class))
				include_once get_lib("org.phpframework.db.driver.$driver_class");
			
			$type = $driver_class::getType();
			$items[$type] = $driver_class::getDBColumnNumericTypes();
		}
		
		self::$saved_data_by_func["getAllColumnNumericTypesByType"] = $items;
		
		return $items;
	}
	
	public static function getAllColumnNumericTypes() {
		return array_values( self::mergeItems( self::getAllColumnNumericTypesByType() ) );
	}
	
	public static function getAllSharedColumnNumericTypes() {
		return array_values( self::intersectItems( self::getAllColumnNumericTypesByType() ) );
	}
	
	public static function getAllColumnDateTypesByType() {
		if (!empty(self::$saved_data_by_func["getAllColumnDateTypesByType"]))
			return self::$saved_data_by_func["getAllColumnDateTypesByType"];
		
		$drivers = self::getAvailableDriverClassNames();
		$items = array();
		
		foreach ($drivers as $driver_class) {
			if (!class_exists($driver_class))
				include_once get_lib("org.phpframework.db.driver.$driver_class");
			
			$type = $driver_class::getType();
			$items[$type] = $driver_class::getDBColumnDateTypes();
		}
		
		self::$saved_data_by_func["getAllColumnDateTypesByType"] = $items;
		
		return $items;
	}
	
	public static function getAllColumnDateTypes() {
		return array_values( self::mergeItems( self::getAllColumnDateTypesByType() ) );
	}
	
	public static function getAllSharedColumnDateTypes() {
		return array_values( self::intersectItems( self::getAllColumnDateTypesByType() ) );
	}
	
	public static function getAllColumnTextTypesByType() {
		if (!empty(self::$saved_data_by_func["getAllColumnTextTypesByType"]))
			return self::$saved_data_by_func["getAllColumnTextTypesByType"];
		
		$drivers = self::getAvailableDriverClassNames();
		$items = array();
		
		foreach ($drivers as $driver_class) {
			if (!class_exists($driver_class))
				include_once get_lib("org.phpframework.db.driver.$driver_class");
			
			$type = $driver_class::getType();
			$items[$type] = $driver_class::getDBColumnTextTypes();
		}
		
		self::$saved_data_by_func["getAllColumnTextTypesByType"] = $items;
		
		return $items;
	}
	
	public static function getAllColumnTextTypes() {
		return array_values( self::mergeItems( self::getAllColumnTextTypesByType() ) );
	}
	
	public static function getAllSharedColumnTextTypes() {
		return array_values( self::intersectItems( self::getAllColumnTextTypesByType() ) );
	}
	
	public static function getAllColumnBlobTypesByType() {
		if (!empty(self::$saved_data_by_func["getAllColumnBlobTypesByType"]))
			return self::$saved_data_by_func["getAllColumnBlobTypesByType"];
		
		$drivers = self::getAvailableDriverClassNames();
		$items = array();
		
		foreach ($drivers as $driver_class) {
			if (!class_exists($driver_class))
				include_once get_lib("org.phpframework.db.driver.$driver_class");
			
			$type = $driver_class::getType();
			$items[$type] = $driver_class::getDBColumnBlobTypes();
		}
		
		self::$saved_data_by_func["getAllColumnBlobTypesByType"] = $items;
		
		return $items;
	}
	
	public static function getAllColumnBlobTypes() {
		return array_values( self::mergeItems( self::getAllColumnBlobTypesByType() ) );
	}
	
	public static function getAllSharedColumnBlobTypes() {
		return array_values( self::intersectItems( self::getAllColumnBlobTypesByType() ) );
	}
	
	public static function getAllColumnBooleanTypesByType() {
		if (!empty(self::$saved_data_by_func["getAllColumnBooleanTypesByType"]))
			return self::$saved_data_by_func["getAllColumnBooleanTypesByType"];
		
		$drivers = self::getAvailableDriverClassNames();
		$items = array();
		
		foreach ($drivers as $driver_class) {
			if (!class_exists($driver_class))
				include_once get_lib("org.phpframework.db.driver.$driver_class");
			
			$type = $driver_class::getType();
			$items[$type] = $driver_class::getDBColumnBooleanTypes();
		}
		
		self::$saved_data_by_func["getAllColumnBooleanTypesByType"] = $items;
		
		return $items;
	}
	
	public static function getAllColumnBooleanTypes() {
		return array_values( self::mergeItems( self::getAllColumnBooleanTypesByType() ) );
	}
	
	public static function getAllSharedColumnBooleanTypes() {
		return array_values( self::intersectItems( self::getAllColumnBooleanTypesByType() ) );
	}
	
	public static function getAllColumnMandatoryLengthTypesByType() {
		if (!empty(self::$saved_data_by_func["getAllColumnMandatoryLengthTypesByType"]))
			return self::$saved_data_by_func["getAllColumnMandatoryLengthTypesByType"];
		
		$drivers = self::getAvailableDriverClassNames();
		$items = array();
		
		foreach ($drivers as $driver_class) {
			if (!class_exists($driver_class))
				include_once get_lib("org.phpframework.db.driver.$driver_class");
			
			$type = $driver_class::getType();
			$items[$type] = $driver_class::getDBColumnMandatoryLengthTypes();
		}
		
		self::$saved_data_by_func["getAllColumnMandatoryLengthTypesByType"] = $items;
		
		return $items;
	}
	
	public static function getAllColumnMandatoryLengthTypes() {
		return self::mergeItems( self::getAllColumnMandatoryLengthTypesByType() );
	}
	
	public static function getAllSharedColumnMandatoryLengthTypes() {
		$array_with_multiple_arrays = self::getAllColumnMandatoryLengthTypesByType();
		$args = array();
		
		foreach ($array_with_multiple_arrays as $type => $items)
			if ($items)
				$args[] = $items;
		
		return @call_user_func_array("array_intersect_key", $args); //Note that the @ is very important here bc in PHP 8 this gives an warning, this is: 'Warning: Array to string conversion in...'
	}
	
	public static function getAllColumnAutoIncrementTypesByType() {
		if (!empty(self::$saved_data_by_func["getAllColumnAutoIncrementTypesByType"]))
			return self::$saved_data_by_func["getAllColumnAutoIncrementTypesByType"];
		
		$drivers = self::getAvailableDriverClassNames();
		$items = array();
		
		foreach ($drivers as $driver_class) {
			if (!class_exists($driver_class))
				include_once get_lib("org.phpframework.db.driver.$driver_class");
			
			$type = $driver_class::getType();
			$items[$type] = $driver_class::getDBColumnAutoIncrementTypes();
		}
		
		self::$saved_data_by_func["getAllColumnAutoIncrementTypesByType"] = $items;
		
		return $items;
	}
	
	public static function getAllColumnAutoIncrementTypes() {
		return array_values( self::mergeItems( self::getAllColumnAutoIncrementTypesByType() ) );
	}
	
	public static function getAllSharedColumnAutoIncrementTypes() {
		return array_values( self::intersectItems( self::getAllColumnAutoIncrementTypesByType() ) );
	}
	
	public static function getAllBooleanTypeAvailableValuesByType() {
		if (!empty(self::$saved_data_by_func["getAllBooleanTypeAvailableValuesByType"]))
			return self::$saved_data_by_func["getAllBooleanTypeAvailableValuesByType"];
		
		$drivers = self::getAvailableDriverClassNames();
		$items = array();
		
		foreach ($drivers as $driver_class) {
			if (!class_exists($driver_class))
				include_once get_lib("org.phpframework.db.driver.$driver_class");
			
			$type = $driver_class::getType();
			$items[$type] = $driver_class::getDBBooleanTypeAvailableValues();
		}
		
		self::$saved_data_by_func["getAllBooleanTypeAvailableValuesByType"] = $items;
		
		return $items;
	}
	
	public static function getAllBooleanTypeAvailableValues() {
		return self::mergeItems( self::getAllBooleanTypeAvailableValuesByType() );
	}
	
	public static function getAllSharedBooleanTypeAvailableValues() {
		$array_with_multiple_arrays = self::getAllBooleanTypeAvailableValuesByType();
		$args = array();
		
		foreach ($array_with_multiple_arrays as $type => $items)
			if ($items)
				$args[] = $items;
		
		return @call_user_func_array("array_intersect_key", $args); //Note that the @ is very important here bc in PHP 8 this gives an warning, this is: 'Warning: Array to string conversion in...'
	}
	
	public static function getAllCurrentTimestampAvailableValuesByType() {
		if (!empty(self::$saved_data_by_func["getAllCurrentTimestampAvailableValuesByType"]))
			return self::$saved_data_by_func["getAllCurrentTimestampAvailableValuesByType"];
		
		$drivers = self::getAvailableDriverClassNames();
		$items = array();
		
		foreach ($drivers as $driver_class) {
			if (!class_exists($driver_class))
				include_once get_lib("org.phpframework.db.driver.$driver_class");
			
			$type = $driver_class::getType();
			$items[$type] = $driver_class::getDBCurrentTimestampAvailableValues();
		}
		
		self::$saved_data_by_func["getAllCurrentTimestampAvailableValuesByType"] = $items;
		
		return $items;
	}
	
	public static function getAllCurrentTimestampAvailableValues() {
		return array_values( self::mergeItems( self::getAllCurrentTimestampAvailableValuesByType() ) );
	}
	
	public static function getAllSharedCurrentTimestampAvailableValues() {
		return array_values( self::intersectItems( self::getAllCurrentTimestampAvailableValuesByType() ) );
	}
	
	//Result will be grouped by driver type and then by column type
	public static function getAllColumnTypesIgnoredPropsByType() {
		if (!empty(self::$saved_data_by_func["getAllColumnTypesIgnoredPropsByType"]))
			return self::$saved_data_by_func["getAllColumnTypesIgnoredPropsByType"];
		
		$drivers = self::getAvailableDriverClassNames();
		$items = array();
		
		foreach ($drivers as $driver_class) {
			if (!class_exists($driver_class))
				include_once get_lib("org.phpframework.db.driver.$driver_class");
			
			$type = $driver_class::getType();
			$items[$type] = $driver_class::getDBColumnTypesIgnoredProps();
		}
		
		self::$saved_data_by_func["getAllColumnTypesIgnoredPropsByType"] = $items;
		
		return $items;
	}
	
	//Result will be grouped by column type
	public static function getAllColumnTypesIgnoredProps() {
		return self::mergeItems( self::getAllColumnTypesIgnoredPropsByType() );
	}
	
	//Result will be grouped by column type
	public static function getAllSharedColumnTypesIgnoredProps() {
		$array_with_multiple_arrays = self::getAllColumnTypesIgnoredPropsByType();
		$args = array();
		
		foreach ($array_with_multiple_arrays as $type => $items)
			if ($items)
				$args[] = $items;
		
		return @call_user_func_array("array_intersect_key", $args); //Note that the @ is very important here bc in PHP 8 this gives an warning, this is: 'Warning: Array to string conversion in...'
	}
	
	public static function getAllColumnTypesHiddenPropsByType() {
		if (!empty(self::$saved_data_by_func["getAllColumnTypesHiddenPropsByType"]))
			return self::$saved_data_by_func["getAllColumnTypesHiddenPropsByType"];
		
		$drivers = self::getAvailableDriverClassNames();
		$items = array();
		
		foreach ($drivers as $driver_class) {
			if (!class_exists($driver_class))
				include_once get_lib("org.phpframework.db.driver.$driver_class");
			
			$type = $driver_class::getType();
			$items[$type] = $driver_class::getDBColumnTypesHiddenProps();
		}
		
		self::$saved_data_by_func["getAllColumnTypesHiddenPropsByType"] = $items;
		
		return $items;
	}
	
	public static function getAllColumnTypesHiddenProps() {
		return array_values( self::mergeItems( self::getAllColumnTypesHiddenPropsByType() ) );
	}
	
	public static function getAllSharedColumnTypesHiddenProps() {
		$array_with_multiple_arrays = self::getAllColumnTypesHiddenPropsByType();
		$args = array();
		
		foreach ($array_with_multiple_arrays as $type => $items)
			$args[] = $items ? $items : array();
		
		return array_values( @call_user_func_array("array_intersect", $args) ); //Note that the @ is very important here bc in PHP 8 this gives an warning, this is: 'Warning: Array to string conversion in...'
	}
	
	public static function getAllAttributeValueReservedWordsByType() {
		if (!empty(self::$saved_data_by_func["getAllAttributeValueReservedWordsByType"]))
			return self::$saved_data_by_func["getAllAttributeValueReservedWordsByType"];
		
		$drivers = self::getAvailableDriverClassNames();
		$items = array();
		
		foreach ($drivers as $driver_class) {
			if (!class_exists($driver_class))
				include_once get_lib("org.phpframework.db.driver.$driver_class");
			
			$type = $driver_class::getType();
			$items[$type] = $driver_class::getAttributeValueReservedWords();
		}
		
		self::$saved_data_by_func["getAllAttributeValueReservedWordsByType"] = $items;
		
		return $items;
	}
	
	public static function getAllAttributeValueReservedWords() {
		return array_values( self::mergeItems( self::getAllAttributeValueReservedWordsByType() ) );
	}
	
	public static function getAllSharedAttributeValueReservedWords() {
		return array_values( self::intersectItems( self::getAllAttributeValueReservedWordsByType() ) );
	}
	
	public static function getAllReservedWordsByType() {
		if (!empty(self::$saved_data_by_func["getAllReservedWordsByType"]))
			return self::$saved_data_by_func["getAllReservedWordsByType"];
		
		$drivers = self::getAvailableDriverClassNames();
		$items = array();
		
		foreach ($drivers as $driver_class) {
			if (!class_exists($driver_class))
				include_once get_lib("org.phpframework.db.driver.$driver_class");
			
			$type = $driver_class::getType();
			$items[$type] = $driver_class::getReservedWords();
		}
		
		self::$saved_data_by_func["getAllReservedWordsByType"] = $items;
		
		return $items;
	}
	
	public static function getAllReservedWords() {
		return array_values( self::mergeItems( self::getAllReservedWordsByType() ) );
	}
	
	public static function getAllSharedReservedWords() {
		return array_values( self::intersectItems( self::getAllReservedWordsByType() ) );
	}
	
	/*
	 * Splits a text into sql queries. Removes all comments too.
	 */
	public static function splitSQL($sql, $options = false) {
		$queries = array();
		$delimiter = $options && !empty($options["delimiter"]) ? $options["delimiter"] : ";";
		$remove_comments = $options && !empty($options["remove_comments"]);
		
		if (strpos($sql, $delimiter) !== false) {
			$open_double_quotes = false;
			$open_single_quotes = false;
			
			$start = 0;
			$end = strlen($sql);
			$delimiter_length = strlen($delimiter);
			
			if (is_numeric($sql))
				$sql = (string)$sql; //bc of php > 7.4 if we use $sql[$i] gives an warning
			
			for ($i = 0 ; $i < $end; $i++) {
				$char = $sql[$i];
				
				if ($char == '"' && !TextSanitizer::isCharEscaped($sql, $i) && !$open_single_quotes)
					$open_double_quotes = !$open_double_quotes;
				else if ($char == "'" && !TextSanitizer::isCharEscaped($sql, $i) && !$open_double_quotes) 
					$open_single_quotes = !$open_single_quotes;
				else if ($remove_comments && $char == "\n" && $i + 2 < $end && $sql[$i + 1] == "-" && $sql[$i + 2] == "-" && !$open_double_quotes && !$open_single_quotes) { //jump comments and avoid conflits
					$pos = strpos($sql, "\n", $i + 3);
					$pos = is_numeric($pos) ? $pos : $end;
					$i = $pos - 1; //previous char from \n, so the next char is \n and it parses again the next comment. This is very important, otherwise it will not parse the next comments and the query will still contain comments.
				}
				else if ($remove_comments && $i == 0 && $sql[$i] == "-" && $i + 1 < $end && $sql[$i + 1] == "-" && !$open_double_quotes && !$open_single_quotes) { //jump comments and avoid conflits
					$pos = strpos($sql, "\n", $i + 2);
					$pos = is_numeric($pos) ? $pos : $end;
					$i = $pos - 1; //previous char from \n, so the next char is \n and it parses again the next comment. This is very important, otherwise it will not parse the next comments and the query will still contain comments.
				}
				else if ($remove_comments && $char == "/" && $i + 1 < $end && $sql[$i + 1] == "*" && !$open_double_quotes && !$open_single_quotes) { //jump comments and avoid conflits
					$pos = strpos($sql, "*/", $i + 2);
					$pos = is_numeric($pos) ? $pos : $end;
					$i = $pos + 1;
				}
				else if (!$open_double_quotes && !$open_single_quotes && strtoupper($char) == "D" && preg_match("/^DELIMITER(\s+|'|\")/i", substr($sql, $i, 10))) {
					$query = trim( substr($sql, $start, $i - $start) ); //include ;
					
					if ($query) {
						if ($remove_comments)
							$query = self::removeSQLComments($query, $options);
						
						if ($query && $query != $delimiter)
							$queries[] = $query;
					}
					
					//If query is a DELIMITER query, change the $delimiter var and do not add the DELIMITER query alone, bc DB->setData method cannot parse the DELIMITER query in the mysqli methods! DELIMITER is a not a server-side statement. It's a client-side command recognised by the mysql command-line client (and, probably, others too). It is not recognised by the mysqli driver, hence there is nothing in the documentation. The documentation does not mention any way for one to change the mysqli driver's statement delimiter.
					$last_pos = strpos($sql, "\n", $i + 10);
					$last_pos = $last_pos !== false ? $last_pos : $end;
					
					$new_delimiter = trim( substr($sql, $i + 10, $last_pos - ($i + 10)) );
					
					if ($new_delimiter && ( 
						($new_delimiter[0] == "'" && substr($new_delimiter, -1) == "'") ||
						($new_delimiter[0] == '"' && substr($new_delimiter, -1) == '"')
					))
						$new_delimiter = substr($new_delimiter, 1, -1);
					
					if ($new_delimiter) {
						$delimiter = $new_delimiter;
						$delimiter_length = strlen($delimiter);
						//echo "change delimiter:$delimiter\n";
					}
					
					$queries[] = trim( substr($sql, $i, $last_pos - $i) ); //include delimiter statement to $queries. In mysql this statement will be discarded in the execute and query methods.
					//echo "delimiter:$delimiter|\nold start:$start\nnew start:$last_pos\n";
					$start = $last_pos + 1;
					$i = $last_pos;
				}
				else if ($char == $delimiter[0] && substr($sql, $i, $delimiter_length) == $delimiter && !$open_double_quotes && !$open_single_quotes && $i != $end - 1) {
					$query = trim( substr($sql, $start, ($i - $start) + $delimiter_length) ); //+$delimiter_length to include delimiter
					
					if ($query) {
						if ($remove_comments)
							$query = self::removeSQLComments($query, $options);
						
						if ($query && $query != $delimiter)
							$queries[] = $query;
					}
					
					/*
					 * $start = $i + ($delimiter_length - 1) + 1; == $start = $i + $delimiter_length;
					 * 	$delimiter_length - 1 => bc the loop will add $i++. 
					 * 	+1 so it can be the next char after delimiter.
					 */
					$start = $i + $delimiter_length;
				}
				
				if ($i == $end - 1) {
					$query = trim( substr($sql, $start) );
					
					if ($query)  {
						if ($remove_comments)
							$query = self::removeSQLComments($query, $options);
						
						if ($query && $query != $delimiter)
							$queries[] = $query;
					}
				}
			}
		}
		else if (trim($sql)) {
			if ($remove_comments)
				$sql = self::removeSQLComments($sql, $options);
			
			if ($sql)
				$queries[] = $sql;
		}
		
		return $queries;
	}
	
	public static function removeSQLComments($sql, $options = false) {
		if (strpos($sql, "/*") !== false || strpos($sql, "--") !== false) {
			$open_double_quotes = false;
			$open_single_quotes = false;
			
			$start = 0;
			$end = strlen($sql);
			$new_sql = "";
			
			if (is_numeric($sql))
				$sql = (string)$sql; //bc of php > 7.4 if we use $sql[$i] gives an warning
			
			for ($i = 0 ; $i < $end; $i++) {
				$char = $sql[$i];
			
				if ($char == '"' && !TextSanitizer::isCharEscaped($sql, $i) && !$open_single_quotes)
					$open_double_quotes = !$open_double_quotes;
				else if ($char == "'" && !TextSanitizer::isCharEscaped($sql, $i) && !$open_double_quotes) 
					$open_single_quotes = !$open_single_quotes;
				else if ($char == "\n" && $i + 2 < $end && $sql[$i + 1] == "-" && $sql[$i + 2] == "-" && !$open_double_quotes && !$open_single_quotes) { //remove comments
					$new_sql .= substr($sql, $start, ($i - $start) + 1); //Do not trim bc there could be a end-line at the beggining of the substr that should not be removed, otherwise we are combining 2 lines and maybe 2 words that should not be combined!
					
					$pos = strpos($sql, "\n", $i + 3);
					$pos = is_numeric($pos) ? $pos : $end;
					$start = $pos;
					$i = $pos - 1; //previous char from \n, so the next char is \n and it parses again the next comment. This is very important, otherwise it will not parse the next comments and the query will still contain comments.
				}
				else if ($i == 0 && $sql[$i] == "-" && $i + 1 < $end && $sql[$i + 1] == "-" && !$open_double_quotes && !$open_single_quotes) { //remove comments
					$new_sql .= substr($sql, $start, ($i - $start)); //Do not trim bc there could be a end-line at the beggining of the substr that should not be removed, otherwise we are combining 2 lines and maybe 2 words that should not be combined!
					
					$pos = strpos($sql, "\n", $i + 2);
					$pos = is_numeric($pos) ? $pos : $end;
					$start = $pos;
					$i = $pos - 1; //previous char from \n, so the next char is \n and it parses again the next comment. This is very important, otherwise it will not parse the next comments and the query will still contain comments.
				}
				else if ($char == "/" && $i + 1 < $end && $sql[$i + 1] == "*" && !$open_double_quotes && !$open_single_quotes) { //remove comments
					$new_sql .= substr($sql, $start, ($i - $start)); //Do not trim bc there could be a end-line at the beggining of the substr that should not be removed, otherwise we are combining 2 lines and maybe 2 words that should not be combined!
					
					$pos = strpos($sql, "*/", $i + 2);
					$pos = is_numeric($pos) ? $pos : $end;
					$start = $pos + 2;
					$i = $pos + 1;
				}
				
				if ($i == $end - 1) 
					$new_sql .= substr($sql, $start);
			}
		}
		else
			$new_sql = $sql;
		
		return self::removeSQLRepeatedDelimiters($new_sql, $options); //remove repeatd semicolons bc when we remove comments we will habve duplicated semicolons on the mysqldumps...
	}
	
	public static function removeSQLRepeatedDelimiters($sql, $options = false) {
		$delimiter = $options && !empty($options["delimiter"]) ? $options["delimiter"] : ";";
		$delimiter_length = strlen($delimiter);
		
		$open_double_quotes = false;
		$open_single_quotes = false;
		$open_delimiter = false;
		
		$end = strlen($sql);
		$new_sql = "";
		
		if (is_numeric($sql))
			$sql = (string)$sql; //bc of php > 7.4 if we use $sql[$i] gives an warning
		
		for ($i = 0 ; $i < $end; $i++) {
			$char = $sql[$i];
		
			if ($char == '"' && !TextSanitizer::isCharEscaped($sql, $i) && !$open_single_quotes) {
				$open_double_quotes = !$open_double_quotes;
				$open_delimiter = false;
			}
			else if ($char == "'" && !TextSanitizer::isCharEscaped($sql, $i) && !$open_double_quotes) {
				$open_single_quotes = !$open_single_quotes;
				$open_delimiter = false;
			}
			else if (!$open_double_quotes && !$open_single_quotes) {
				if (strtoupper($char) == "D" && preg_match("/^DELIMITER(\s+|'|\")/i", substr($sql, $i, 10))) { //check if is a DELIMITER statement
					$pos = strpos($sql, "\n", $i + 10);
					$pos = $pos !== false ? $pos : $end;
					
					$new_delimiter = trim(substr($sql, $i + 10, $pos - ($i + 10)));
					
					if ($new_delimiter && ( 
						($new_delimiter[0] == "'" && substr($new_delimiter, -1) == "'") ||
						($new_delimiter[0] == '"' && substr($new_delimiter, -1) == '"')
					))
						$new_delimiter = substr($new_delimiter, 1, -1);
					
					if ($new_delimiter) {
						$delimiter = $new_delimiter;
						$delimiter_length = strlen($new_delimiter);
					}
					
					$new_sql .= substr($sql, $i, $pos - $i + 1); //+1 to include the end-line
					$i = $pos;
					$open_delimiter = true;
					continue 1;
				}
				else if ($open_delimiter && preg_match("/\s/", $char))
					continue 1;
				else if ($char == $delimiter[0] && substr($sql, $i, $delimiter_length) == $delimiter) {
					if ($open_delimiter) //repeated delimiter, so ignore it.
						continue 1;
					else {
						$open_delimiter = true;
						
						if ($delimiter_length > 1) { //in case the delimiter have more than 1 character
							$char = $delimiter;
							$i += $delimiter_length - 1; //-1 bc the loop will add $i++
						}
						
						$char .= "\n";
					}
				}
				else
					$open_delimiter = false;
			}
			
			$new_sql .= $char;
		}
		
		$new_sql = preg_replace("/^\s*;+\s*/", "", $new_sql); //removing semicolons from beginning
		
		return trim($new_sql);
	}
	
	/*
	 * @param $delimiters_to_search	array where first item is the start delimiter and second is the end delimiter
	 * @param $delimiters_to_replace	array where first item is the start delimiter and second is the end delimiter
	 */
	public static function replaceSQLEnclosingDelimiter($sql, $delimiters_to_search, $delimiters_to_replace) {
		$start_delimiter_to_search = is_array($delimiters_to_search) ? (isset($delimiters_to_search[0]) ? $delimiters_to_search[0] : null) : $delimiters_to_search;
		$end_delimiter_to_search = is_array($delimiters_to_search) ? (isset($delimiters_to_search[1]) ? $delimiters_to_search[1] : null) : $delimiters_to_search;
		$start_delimiter_to_replace = is_array($delimiters_to_replace) ? (isset($delimiters_to_replace[0]) ? $delimiters_to_replace[0] : null) : $delimiters_to_replace;
		$end_delimiter_to_replace = is_array($delimiters_to_replace) ? (isset($delimiters_to_replace[1]) ? $delimiters_to_replace[1] : null) : $delimiters_to_replace;
		
		if (!$end_delimiter_to_search)
			$end_delimiter_to_search = $start_delimiter_to_search;
		
		if (!$end_delimiter_to_replace)
			$end_delimiter_to_replace = $start_delimiter_to_replace;
		
		if ($start_delimiter_to_search && strpos($sql, $start_delimiter_to_search) !== false) {
			$open_double_quotes = false;
			$open_single_quotes = false;
			
			$start = 0;
			$end = strlen($sql);
			$new_sql = "";
			$start_delimiter_length = strlen($start_delimiter_to_search);
			$end_delimiter_length = strlen($end_delimiter_to_search);
			
			if (is_numeric($sql))
				$sql = (string)$sql; //bc of php > 7.4 if we use $sql[$i] gives an warning
		
			for ($i = 0 ; $i < $end; $i++) {
				$char = $sql[$i];
			
				if ($char == '"' && !TextSanitizer::isCharEscaped($sql, $i) && !$open_single_quotes)
					$open_double_quotes = !$open_double_quotes;
				else if ($char == "'" && !TextSanitizer::isCharEscaped($sql, $i) && !$open_double_quotes) 
					$open_single_quotes = !$open_single_quotes;
				else if (!$open_double_quotes && !$open_single_quotes && 
					($char == $start_delimiter_to_search || ($start_delimiter_length > 1 && substr($sql, $i, $start_delimiter_length) == $start_delimiter_to_search))
				) { //replace start delimiter
					$new_sql .= substr($sql, $start, $i - $start); //Do not trim bc there could be a end-line at the beggining of the substr that should not be removed, otherwise we are combining 2 lines and maybe 2 words that should not be combined!
					$new_sql .= $start_delimiter_to_replace;
					
					$pos = $end_delimiter_length ? strpos($sql, $end_delimiter_to_search, $i + $start_delimiter_length) : false;
					
					//if end delimiter exists, replace end delimiter. 
					//Note that the end delimiter will only be replaced if the start delimiter is found.
					if (is_numeric($pos)) {
						$new_sql .= substr($sql, $i + 1, $pos - ($i + 1));
						$new_sql .= $end_delimiter_to_replace;
						$start = $pos + $end_delimiter_length;
						$i = $start - 1;
					}
					else { //if no end delimiter, continues to loop to find other start delimiters
						$start = $i + $start_delimiter_length;
						$i = $start - 1;
					}
				}
				
				if ($i == $end - 1)
					$new_sql .= substr($sql, $start);
			}
		}
		else
			$new_sql = $sql;
		
		return trim($new_sql);
	}
	
	public static function isTheSameStaticTableName($table_name_1, $table_name_2, $options = false) {
		$table_name_1 = strtolower($table_name_1);
		$table_name_2 = strtolower($table_name_2);
		
		if ($table_name_1 == $table_name_2)
			return true;
		
		$parts = SQLQueryHandler::parseTableName($table_name_1);
		$size = count($parts);
		$name_1 = trim($parts[$size - 1]);
		$schema_1 = isset($parts[$size - 2]) ? trim($parts[$size - 2]) : null;
		$database_1 = isset($parts[$size - 3]) ? trim($parts[$size - 3]) : null;
		
		if (!$schema_1)
			$schema_1 = $options && !empty($options["schema"]) ? $options["schema"] : "";
		
		if (!$database_1)
			$database_1 = $options && !empty($options["db_name"]) ? $options["db_name"] : "";
		
		$parts = SQLQueryHandler::parseTableName($table_name_2);
		$size = count($parts);
		$name_2 = trim($parts[$size - 1]);
		$schema_2 = isset($parts[$size - 2]) ? trim($parts[$size - 2]) : null;
		$database_2 = isset($parts[$size - 3]) ? trim($parts[$size - 3]) : null;
		
		if (!$schema_2)
			$schema_2 = $options && !empty($options["schema"]) ? $options["schema"] : "";
		
		if (!$database_2)
			$database_2 = $options && !empty($options["db_name"]) ? $options["db_name"] : "";
		
		return $name_1 == $name_2 && (
				!empty($options["simple_comparison"]) || 
				($schema_1 == $schema_2 && $database_1 == $database_2)
			);
	}
	
	public static function isStaticTableInNamesList($tables_list, $table_to_search, $options = false) {
		$table = self::getStaticTableInNamesList($tables_list, $table_to_search, $options);
		return !empty($table);
	}
	
	public static function getStaticTableInNamesList($tables_list, $table_to_search, $options = false) {
		if (is_array($tables_list) && $table_to_search) {
			if (in_array($table_to_search, $tables_list))
				return $table_to_search;
			
			$tables_list_aux = array();
			foreach ($tables_list as $table_props)
				$tables_list_aux[] = is_array($table_props) ? (isset($table_props["name"]) ? $table_props["name"] : null) : $table_props;
			
			if (in_array($table_to_search, $tables_list_aux))
				return $table_to_search;
			
			$table_to_search = strtolower($table_to_search);
			$parts = SQLQueryHandler::parseTableName($table_to_search);
			$size = count($parts);
			$name_to_search = trim($parts[$size - 1]);
			$schema_to_search = isset($parts[$size - 2]) ? trim($parts[$size - 2]) : null;
			$database_to_search = isset($parts[$size - 3]) ? trim($parts[$size - 3]) : null;
			
			if (!$schema_to_search)
				$schema_to_search = $options && !empty($options["schema"]) ? $options["schema"] : "";
			
			if (!$database_to_search)
				$database_to_search = $options && !empty($options["db_name"]) ? $options["db_name"] : "";
			
			foreach ($tables_list_aux as $table_name) {
				$table_name = strtolower($table_name);
				$parts = SQLQueryHandler::parseTableName($table_name);
				$size = count($parts);
				$name = trim($parts[$size - 1]);
				$schema = isset($parts[$size - 2]) ? trim($parts[$size - 2]) : null;
				$database = isset($parts[$size - 3]) ? trim($parts[$size - 3]) : null;
				
				if (!$schema)
					$schema = $options && !empty($options["schema"]) ? $options["schema"] : "";
				
				if (!$database)
					$database = $options && !empty($options["db_name"]) ? $options["db_name"] : "";
				
				if ($name == $name_to_search && $schema == $schema_to_search && $database == $database_to_search)
					return $table_name;
			}
		}
		
		return null;
	}
	
	/* Protected Static Methods - UTILS */
	
	/*
		Array(
		    [native_type] => LONG
		    [pdo_type] => 2
		    [flags] => Array //not sure if this is correct
			   (
				  [0] => not_null
				  [1] => primary_key
			   )

		    [table] => parent
		    [name] => id
		    [len] => 10
		    [precision] => 0
		)
	*/
	protected static function preparePDOField(&$field, $available_flags = null) {
		//echo "<pre>";print_r($field);
		if (is_array($field))
			$field = (object) $field; //cast to object
		
		//prepare attributes
		$field->length = isset($field->len) ? $field->len : null; //optional
		unset($field->len);
		
		if ($field)
			switch($field->pdo_type) {
				//0 => array("null"),
				case PDO::PARAM_NULL: 
					$field->type = "null";
					break;
				
				//5 => array("bool")
				case PDO::PARAM_BOOL: 
					$field->type = "tinyint";
					
					if (empty($field->length))
						$field->length = 1;
					break;
				
				//1 => array("int"),
				case PDO::PARAM_INT: 
					$field->type = "int";
					break;
				
				//2 => array("varchar"),
				//1073741824 => array("varchar"),
				case PDO::PARAM_STR: 
				case PDO::PARAM_STR_NATL:
					$field->type = "varchar";
					break;
				
				//536870912 => array("char"),
				case PDO::PARAM_STR_CHAR: 
					$field->type = "char";
					break;
				
				//3 => array("text"),
				//4 => array("text"), //PARAM_STMT: Represents a recordset type. Not currently supported by any drivers.
				//2147483648 => array("blob", "storeprocedure"), //PARAM_INPUT_OUTPUT: Specifies that the parameter is an INOUT parameter for a stored procedure. You must bitwise-OR this value with an explicit PDO::PARAM_* data type.
				case PDO::PARAM_LOB: 
				case PDO::PARAM_STMT:
				case PDO::PARAM_INPUT_OUTPUT: 
					$field->type = "text";
					break;
				
				default:
					$field->type = strtolower($field->native_type);
					
					switch ($field->native_type) {
					   case 'TINY': $field->type = "tinyint"; break;
					   case 'SHORT': $field->type = "smallint"; break;
					   case 'LONG': $field->type = "int"; break;
					   case 'INT24': $field->type = "mediumint"; break;
					   case 'LONGLONG': $field->type = "bigint"; break;
					   default:
					   	$field->type = strtolower($field->native_type);
				    }
			}
		
		if (!empty($field->flags)) {
			if (is_numeric($field->flags)) {
				foreach ($available_flags as $n => $t) 
				    	if ($field->flags & $n)
				    		$field->$t = true;
			}
			else if (is_array($field->flags)) { //I'm not sure how it will be the $field->flags array???
				$is_numeric_array = array_values($field->flags) == $field->flags;
				
				if ($is_numeric_array)
					foreach ($field->flags as $t)
						$field->$t = true;
			}
		}
		
		//echo "<pre>";print_r($field);die();
	}
	
	protected static function convertColumnTypeToDB($type, &$flags = null) {
		$php_to_db_column_types = static::getPHPToDBColumnTypes(); //must be "static" and "self" bc we are calling an abstract static method
		$db_type = isset($php_to_db_column_types[$type]) ? $php_to_db_column_types[$type] : null;//do not change with the strtolower otherwise it may become slow.
		
		if (is_array($db_type)) {
			$flags = $db_type;
			$db_type = isset($db_type["type"]) ? $db_type["type"] : null;
			unset($flags["type"]);
		}
		
		return $db_type ? $db_type : $type;
	}
	
	protected static function convertColumnTypeFromDB($type, &$flags = null) {
		if ($type) {
			$db_to_php_column_types = static::getDBToPHPColumnTypes(); //must be "static" and "self" bc we are calling an abstract static method
			$php_type = isset($db_to_php_column_types[$type]) ? $db_to_php_column_types[$type] : null;//do not change with the strtolower otherwise it may become slow.
			
			if (is_array($php_type)) {
				$flags = $php_type;
				$php_type = isset($php_type["type"]) ? $php_type["type"] : null;
				unset($flags["type"]);
			}
			
			return $php_type ? $php_type : $type;
		}
		
		return $type;
	}
	
	protected static function convertDBColumnTypesIgnoredPropsToHiddenProps() {
		$db_column_types_ignored_props = static::getDBColumnTypesIgnoredProps(); //must be "static" and "self" bc we are calling an abstract static method
		
		if (is_array($db_column_types_ignored_props)) {
			$args = array();
			
			foreach ($db_column_types_ignored_props as $type => $items)
				$args[] = $items ? $items : array();
			
			return array_values( @call_user_func_array("array_intersect", $args) ); //Note that the @ is very important here bc in PHP 8 this gives an warning, this is: 'Warning: Array to string conversion in...'
		}
		
		return array();
	}
	
	protected static function ignoreColumnTypeDBProp($type, $prop_name) {
		$db_column_types_ignored_props = static::getDBColumnTypesIgnoredProps(); //must be "static" and "self" bc we are calling an abstract static method
		$type_props = isset($db_column_types_ignored_props[$type]) ? $db_column_types_ignored_props[$type] : null; //do not change with the strtolower otherwise it may become slow.
		
		return is_array($type_props) ? in_array($prop_name, $type_props) : false;
	}
	
	protected static function getDefaultValueForColumnType($type) {
		$default_values = static::getDBColumnDefaultValuesByType();
		return isset($default_values[$type]) ? $default_values[$type] : null;
	}
	
	protected static function getMandatoryLengthForColumnType($type) {
		$mandatory_lengths = static::getDBColumnMandatoryLengthTypes();
		return isset($mandatory_lengths[$type]) ? $mandatory_lengths[$type] : null;
	}
	
	protected static function isAttributeValueReservedWord($value) {
		$attribute_value_reserved_words = static::getAttributeValueReservedWords();
		return isset($value) && is_array($attribute_value_reserved_words) && in_array(strtoupper(trim($value)), $attribute_value_reserved_words);
	}
	
	protected static function isReservedWord($value) {
		$reserved_words = static::getReservedWords();
		return isset($value) && is_array($reserved_words) && in_array(strtoupper(trim($value)), $reserved_words);
	}
	
	//check if there is a function inside of the value
	//USE THIS METHOD CAREFULLY BC OF SQL INJECTION. This should only be used by the getCreateTableAttributeStatement method.
	protected static function isReservedWordFunction($value) {
		$reserved_words = static::getReservedWords();
		
		$is_function = $value && 
			preg_match("/^([a-z_]+)\s*\(/i", trim($value), $match, PREG_OFFSET_CAPTURE) && //get matches
			$match && //check if exists a match
			in_array(strtoupper($match[1][0]), $reserved_words) && //check if function is a real function
			strpos($value, ")", $match[1][1] + strlen($match[1][0]) + 1) !== false; //check if exists ) after the function call
		
		//check if there is any open quotes
		$open_double_quotes = false;
		$open_single_quotes = false;
		$is_multi_line_comment_open = false;
		$is_single_line_comment_open = false;
		$is_dash_line_comment_open = false;
		$semicolon = false;
		$end = strlen($value);
		
		if (is_numeric($value))
			$value = (string)$value; //bc of php > 7.4 if we use $value[$i] gives an warning
		
		for ($i = 0 ; $i < $end; $i++) {
			$char = $value[$i];
			
			if ($char == '"' && !TextSanitizer::isCharEscaped($value, $i) && !$open_single_quotes && !$is_multi_line_comment_open && !$is_single_line_comment_open && !$is_dash_line_comment_open)
				$open_double_quotes = !$open_double_quotes;
			else if ($char == "'" && !TextSanitizer::isCharEscaped($value, $i) && !$open_double_quotes && !$is_multi_line_comment_open && !$is_single_line_comment_open && !$is_dash_line_comment_open) 
				$open_single_quotes = !$open_single_quotes;
			else if ($char == "/" && $i + 1 < $end && $value[$i + 1] == "*" && !$open_double_quotes && !$open_single_quotes && !$is_multi_line_comment_open && !$is_single_line_comment_open && !$is_dash_line_comment_open) 
				$is_multi_line_comment_open = true;
			else if ($char == "*" && $i + 1 < $end && $value[$i + 1] == "/" && !$open_double_quotes && !$open_single_quotes && $is_multi_line_comment_open && !$is_single_line_comment_open && !$is_dash_line_comment_open) 
				$is_multi_line_comment_open = false;
			else if ($char == "/" && $i + 1 < $end && $value[$i + 1] == "/" && !$open_double_quotes && !$open_single_quotes && !$is_multi_line_comment_open && !$is_single_line_comment_open && !$is_dash_line_comment_open) 
				$is_single_line_comment_open = true;
			else if ($char == "\n" && !$open_double_quotes && !$open_single_quotes && !$is_multi_line_comment_open && $is_single_line_comment_open && !$is_dash_line_comment_open) 
				$is_single_line_comment_open = false;
			else if ($char == "-" && $i + 1 < $end && $value[$i + 1] == "-" && !$open_double_quotes && !$open_single_quotes && !$is_multi_line_comment_open && !$is_single_line_comment_open && !$is_dash_line_comment_open) 
				$is_dash_line_comment_open = true;
			else if ($char == "\n" && !$open_double_quotes && !$open_single_quotes && !$is_multi_line_comment_open && !$is_single_line_comment_open && $is_dash_line_comment_open) 
				$is_dash_line_comment_open = false;
			else if ($char == ";" && !$open_double_quotes && !$open_single_quotes && !$is_multi_line_comment_open && !$is_single_line_comment_open && !$is_dash_line_comment_open)
				$semicolon = true;
		}
		
		return $is_function && !$open_double_quotes && !$open_single_quotes && !$is_multi_line_comment_open && !$is_single_line_comment_open && !$is_dash_line_comment_open && !$semicolon;
	}
	
	protected static function parseTableName($table, $options = false) {
		$enclosing_delimiters = static::getEnclosingDelimiters();
		$start_delimiter = is_array($enclosing_delimiters) ? (isset($enclosing_delimiters[0]) ? $enclosing_delimiters[0] : null) : $enclosing_delimiters;
		$end_delimiter = is_array($enclosing_delimiters) ? (isset($enclosing_delimiters[1]) ? $enclosing_delimiters[1] : null) : $enclosing_delimiters;
		$end_delimiter = $end_delimiter ? $end_delimiter : $start_delimiter;
		
		$parts = SQLQueryHandler::parseTableName($table, $start_delimiter, $end_delimiter);
		
		$size = count($parts);
		$table_name = trim($parts[$size - 1]);
		$table_schema = isset($parts[$size - 2]) ? trim($parts[$size - 2]) : null;
		$table_database = isset($parts[$size - 3]) ? trim($parts[$size - 3]) : null;
		
		if (!$table_schema)
			$table_schema = $options && !empty($options["schema"]) ? $options["schema"] : static::getDefaultSchema();
		
		return array(
			"name" => $table_name,
			"schema" => $table_schema,
			"database" => $table_database,
		);
	}
	
	protected static function getParsedTableEscapedSQL($table, $options = false) {
		$table_props = self::parseTableName($table, $options);
		$name = isset($table_props["name"]) ? $table_props["name"] : null;
		$schema = isset($table_props["schema"]) ? $table_props["schema"] : null;
		$database = isset($table_props["database"]) ? $table_props["database"] : null;
		
		$enclosing_delimiters = static::getEnclosingDelimiters();
		$start_delimiter = is_array($enclosing_delimiters) ? (isset($enclosing_delimiters[0]) ? $enclosing_delimiters[0] : null) : $enclosing_delimiters;
		$end_delimiter = is_array($enclosing_delimiters) ? (isset($enclosing_delimiters[1]) ? $enclosing_delimiters[1] : null) : $enclosing_delimiters;
		$end_delimiter = $end_delimiter ? $end_delimiter : $start_delimiter;
		
		return ($database ? "$start_delimiter$database$end_delimiter." : "") . ($schema ? "$start_delimiter$schema$end_delimiter." : "") . "$start_delimiter$name$end_delimiter";
	}
	
	protected static function addSortOptionsToSQL($sorts) {
		$sql = "";
		
		if (is_array($sorts))
			foreach ($sorts as $k => $sort) {
				if (!is_numeric($k) && $k && !is_array($sort) && (!$sort || strtolower($sort) == "asc" || strtolower($sort) == "desc")) {
					if (strpos($k, "`") === false)
						$k = "`" . str_replace(".", "`.`", $k) . "`";
					
					$sql .= ($sql ? ", " : "") . $k . ($sort ? " " . $sort : "");
				}
				else if (!empty($sort["column"])) {
						$column = $sort["column"];
						
						if (strpos($column, "`") === false)
							$column = "`" . str_replace(".", "`.`", $column) . "`";
						
						$sql .= ($sql ? ", " : "") . $column . (!empty($sort["order"]) ? " " . $sort["order"] : "");
					}
			}
		
		return $sql;
	}
	
	protected static function mergeItems($array_with_multiple_arrays) {
		$merged = array();
		
		foreach ($array_with_multiple_arrays as $type => $items)
			$merged = array_merge($merged, $items);
		
		return array_unique($merged);
	}
	
	protected static function intersectItems($array_with_multiple_arrays) {
		$args = array();
		
		foreach ($array_with_multiple_arrays as $type => $items)
			if ($items)
				$args[] = $items;
		
		return @call_user_func_array("array_intersect", $args); //Note that the @ is very important here bc in PHP 8 this gives an warning, this is: 'Warning: Array to string conversion in...'
	}
	
	protected static function parseExtraSettings($extra_settings) {
		if (is_array($extra_settings))
			return $extra_settings;
		
		$parsed_extra_settings = array();
		$pairs = explode('&', $extra_settings);
		
		foreach ($pairs as $pair) {
			//split into name and value
			list($name, $value) = explode('=', $pair, 2);

			//if name already exists
			if (isset($parsed_extra_settings[$name])) {
				//stick multiple values into an array
				if (is_array($parsed_extra_settings[$name]))
					$parsed_extra_settings[$name][] = $value;
				else
					$parsed_extra_settings[$name] = array($parsed_extra_settings[$name], $value);
			}
			else //otherwise, simply stick it in a scalar
				$parsed_extra_settings[$name] = $value;
		}
		
		return $parsed_extra_settings;
	}
	
	protected static function parseExtraSettingsAsPDOSettings($extra_settings) {
		$parsed_extra_settings = self::parseExtraSettings($extra_settings);
		$pdo_settings = array();
		
		if ($parsed_extra_settings)
			foreach ($parsed_extra_settings as $es_name => $es_value)
				if ($es_name && substr($es_name, 0, 5) == "PDO::" && defined($es_name)) {
					$es_name = constant($es_name);
					
					if ($es_value && substr($es_value, 0, 5) == "PDO::" && defined($es_value))
						$es_value = constant($es_value);
					
					$pdo_settings[$es_name] = $es_value;
				}
		
		return $pdo_settings;
	}
	
	/**
	 * In MSSQLDB, PDO and ODBC: there is no option to return dates as strings; we have to convert them manually. In sqlsvr there is: '"ReturnDatesAsStrings" => true'. More info in the MSSQLDB::connect method.
	 *
	 * Normalizes a record returned from the DB:
	 * - converts DateTimeInterface to 'Y-m-d H:i:s'
	 * - converts objects with __toString() to strings
	 * - for other objects, tries to convert public properties (get_object_vars),
	 * otherwise, replaces with the class name (fallback)
	 * - applies recursively to nested arrays
	 */
	protected static function normalizeRecord($record) {
		 if (is_array($record)) {
		     foreach ($record as $k => $v) {
		         if ($v instanceof \DateTimeInterface) {
		             $record[$k] = $v->format('Y-m-d H:i:s');
		         } 
		         elseif (is_array($v)) {
		             $record[$k] = $this->normalizeRecord($v);
		         } 
		         elseif (is_object($v)) {
		             if ($v instanceof \DateTimeInterface) {
		                 $record[$k] = $v->format('Y-m-d H:i:s');
		             } 
		             elseif (method_exists($v, '__toString')) {
		                 $record[$k] = (string)$v;
		             } 
		             else {
		                 $vars = get_object_vars($v);
		                 
		                 if ($vars) {
		                     $record[$k] = $this->normalizeRecord($vars);
		                 } 
		                 else {
		                     $record[$k] = get_class($v);
		                 }
		             }
		         }
		     }
		     
		     return $record;
		 }
		 else if (is_object($record)) {
		     foreach (get_object_vars($record) as $k => $v) {
		         if ($v instanceof \DateTimeInterface) {
		             $record->$k = $v->format('Y-m-d H:i:s');
		         } 
		         elseif (is_array($v)) {
		             $record->$k = $this->normalizeRecord($v);
		         } 
		         elseif (is_object($v)) {
		             if ($v instanceof \DateTimeInterface) {
		                 $record->$k = $v->format('Y-m-d H:i:s');
		             } 
		             elseif (method_exists($v, '__toString')) {
		                 $record->$k = (string)$v;
		             } 
		             else {
		                 $record->$k = get_class($v);
		             }
		         }
		     }
		     
		     return $record;
		 }

		 return $record;
	}
}
?>
