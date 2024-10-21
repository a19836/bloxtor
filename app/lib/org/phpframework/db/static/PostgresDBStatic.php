<?php
trait PostgresDBStatic {
	public static function getType() {
		return "pg";
	}
	
	public static function getLabel() {
		return "Postgres";
	}
	
	public static function getEnclosingDelimiters() {
		return array('"', '"');
	}
	
	public static function getAliasEnclosingDelimiters() {
		return array('"', '"');
	}
	
	public static function getDBCharsets() {
		return static::$db_charsets;
	}
	
	//postgres doesn't support charset for table
	public static function getTableCharsets() {
		return null;
	}
	
	//postgres doesn't support charset for column
	public static function getColumnCharsets() {
		return null;
	}
	
	public static function getDBCollations() {
		return static::$db_collations;
	}
	
	//postgres doesn't support collation for table
	public static function getTableCollations() {
		return null;
	}
	
	public static function getColumnCollations() {
		return static::$column_collations;
	}
	
	//postgres doesn't support storage engines
	public static function getStorageEngines() {
		return null;
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
		
		if (function_exists("pg_connect"))
			static::$available_php_extension_types[] = "pg";
		
		if (class_exists("pdo"))
			static::$available_php_extension_types[] = "pdo";
		
		if (function_exists("odbc_connect"))
			static::$available_php_extension_types[] = "odbc";
		
		return static::$available_php_extension_types;
	}
	
	public static function allowTableAttributeSorting() {
		return false;
	}
}
?>
