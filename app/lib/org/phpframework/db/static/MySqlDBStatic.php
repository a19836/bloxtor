<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

trait MySqlDBStatic {
	public static function getType() {
		return "mysql";
	}
	
	public static function getLabel() {
		return "MySQL";
	}
	
	public static function getEnclosingDelimiters() {
		return array("`", "`");
	}
	
	public static function getAliasEnclosingDelimiters() {
		return array("'", "'");
	}
	
	public static function getDBConnectionEncodings() {
		return static::$db_connection_encodings;
	}
	
	public static function getTableCharsets() {
		return static::$db_table_charsets;
	}
	
	//mysql doesn't support charsets for columns
	public static function getColumnCharsets() {
		return null;
	}
	
	public static function getTableCollations() {
		return static::$db_table_column_collations;
	}
	
	public static function getColumnCollations() {
		return static::$db_table_column_collations;
	}
	
	public static function getStorageEngines() {
		return static::$storage_engines;
	}
	
	public static function getPHPToDBColumnTypes() {
		return static::$php_to_db_column_types;
	}
	
	public static function getDBToPHPColumnTypes() {
		return static::$db_to_php_column_types;
	}
	
	public static function getDBColumnTypes() {
		return static::$db_column_types;
	}
	
	public static function getDBColumnSimpleTypes() {
		return static::$db_column_simple_types;
	}
	
	public static function getDBColumnDefaultValuesByType() {
		return static::$db_column_default_values_by_type;
	}
	
	public static function getDBColumnTypesIgnoredProps() {
		return static::$db_column_types_ignored_props;
	}
	
	public static function getDBColumnTypesHiddenProps() {
		return static::convertDBColumnTypesIgnoredPropsToHiddenProps();
	}
	
	public static function getDBColumnNumericTypes() {
		return static::$db_column_numeric_types;
	}
	
	public static function getDBColumnDateTypes() {
		return static::$db_column_date_types;
	}
	
	public static function getDBColumnTextTypes() {
		return static::$db_column_text_types;
	}
	
	public static function getDBColumnBlobTypes() {
		return static::$db_column_blob_types;
	}
	
	public static function getDBColumnBooleanTypes() {
		return static::$db_column_boolean_types;
	}
	
	public static function getDBColumnMandatoryLengthTypes() {
		return static::$db_column_mandatory_length_types;
	}
	
	public static function getDBColumnAutoIncrementTypes() {
		return static::$db_column_auto_increment_types;
	}
	
	public static function getDBBooleanTypeAvailableValues() {
		return static::$db_boolean_type_available_values;
	}
	
	public static function getDBCurrentTimestampAvailableValues() {
		return static::$db_current_timestamp_available_values;
	}
	
	public static function getAttributeValueReservedWords() {
		return static::$attribute_value_reserved_words;
	}
	
	public static function getReservedWords() {
		return static::$reserved_words;
	}
	
	public static function getDefaultSchema() {
		return static::$default_schema;
	}
	
	public static function getIgnoreConnectionOptions() {
		return static::$ignore_connection_options;
	}
	
	public static function getIgnoreConnectionOptionsByExtension() {
		return static::$ignore_connection_options_by_extension;
	}
	
	public static function getAvailablePHPExtensionTypes() {
		if (static::$available_php_extension_types)
			return static::$available_php_extension_types;
		
		static::$available_php_extension_types = array();
		
		$msqli_exists = function_exists("mysqli_connect");
		
		if ($msqli_exists)
			static::$available_php_extension_types[] = "mysqli";
		
		if (class_exists("pdo"))
			static::$available_php_extension_types[] = "pdo";
		
		if (function_exists("odbc_connect"))
			static::$available_php_extension_types[] = "odbc";
		
		//mysql extension is deprecated so we only add it if mysqli does not exists
		if (!$msqli_exists && function_exists("mysql_connect"))
			static::$available_php_extension_types[] = "mysql";
		
		return static::$available_php_extension_types;
	}
	
	public static function allowTableAttributeSorting() {
		return true; //can only be true if the MySqlDBStatement::getAddTableAttributeStatement method contains the code to add after/before another attribute
	}
	
	public static function allowModifyTableEncoding() {
		return true; //can only be true if the MySqlDBStatement::getModifyTableEncodingStatement method returns a sql statement
	}
	
	public static function allowModifyTableStorageEngine() {
		return true; //can only be true if the MySqlDBStatement::getModifyTableStorageEngineStatement method returns a sql statement
	}
}
?>
