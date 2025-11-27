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

interface IDB { 
	/* Abstract Static Methods */
	public static function getType();
	public static function getLabel();
	public static function getEnclosingDelimiters();
	public static function getAliasEnclosingDelimiters();
	public static function getDBConnectionEncodings();
	public static function getTableCharsets();
	public static function getColumnCharsets();
	public static function getTableCollations();
	public static function getColumnCollations();
	public static function getStorageEngines();
	public static function getPHPToDBColumnTypes();
	public static function getDBToPHPColumnTypes();
	public static function getDBColumnTypes();
	public static function getDBColumnSimpleTypes();
	public static function getDBColumnDefaultValuesByType();
	public static function getDBColumnTypesIgnoredProps();
	public static function getDBColumnTypesHiddenProps();
	public static function getDBColumnNumericTypes();
	public static function getDBColumnDateTypes();
	public static function getDBColumnTextTypes();
	public static function getDBColumnBlobTypes();
	public static function getDBColumnBooleanTypes();
	public static function getDBColumnMandatoryLengthTypes();
	public static function getDBColumnAutoIncrementTypes();
	public static function getDBBooleanTypeAvailableValues();
	public static function getDBCurrentTimestampAvailableValues();
	public static function getAttributeValueReservedWords();
	public static function getReservedWords();
	public static function getDefaultSchema();
	public static function getIgnoreConnectionOptions();
	public static function getIgnoreConnectionOptionsByExtension();
	public static function getAvailablePHPExtensionTypes();
	public static function allowTableAttributeSorting();
	public static function allowModifyTableEncoding();
	public static function allowModifyTableStorageEngine();
	
	/* Abstract Methods */
	public function parseDSN($dsn);
	public function getDSN($options = null);
	public function getVersion();
	public function connect();
	public function connectWithoutDB();
	public function disconnect(); 
	public function close(); 
	public function ping(); 
	public function setConnectionEncoding($encoding = 'utf8'); 
	public function selectDB($db_name);
	public function error(); 
	public function errno(); 
	public function execute(&$sql, $options = false); 
	public function query(&$sql, $options = false); 
	public function freeResult($result); 
	public function numRows($result); 
	public function numFields($result);
	public function fetchArray($result, $array_type = false); 
	public function fetchRow($result); 
	public function fetchAssoc($result); 
	public function fetchObject($result); 
	public function fetchField($result, $offset); 
	public function isResultValid($result); 
	
	public function createDB($db_name, $options = false);
	public function getSelectedDB($options = false);
	public function listDBs($options = false, $column_name = "name"); 
	public function listTables($db_name = false, $options = false); 
	public function listTableFields($table, $options = false); 
	public function listForeignKeys($table, $options = false);
	public function listTableCharsets();
	public function listColumnCharsets();
	public function listTableCollations();
	public function listColumnCollations();
	public function listStorageEngines();
	public function listViews($db_name = false, $options = false);
	public function listTriggers($db_name = false, $options = false);
	public function listProcedures($db_name = false, $options = false);
	public function listFunctions($db_name = false, $options = false);
	public function listEvents($db_name = false, $options = false);
	public function getInsertedId($options = false); 
	
	public function convertObjectToSQL($data, $options = false);
	public function convertSQLToObject($sql, $options = false);
	public function buildTableInsertSQL($table_name, $attributes, $options = false);
	public function buildTableUpdateSQL($table_name, $attributes, $conditions = false, $options = false);
	public function buildTableDeleteSQL($table_name, $conditions = false, $options = false);
	public function buildTableFindSQL($table_name, $attributes = false, $conditions = false, $options = false);
	public function buildTableCountSQL($table_name, $conditions = false, $options = false);
	public function buildTableFindRelationshipSQL($table_name, $rel_elm, $parent_conditions = false, $options = false);
	public function buildTableCountRelationshipSQL($table_name, $rel_elm, $parent_conditions = false, $options = false);
	public function buildTableFindColumnMaxSQL($table_name, $attribute_name, $options = false);
	
	/* Abstract Statement Methods */
	public static function getCreateDBStatement($db_name, $options = false);
	public static function getDropDatabaseStatement($db_name, $options = false);
	public static function getSelectedDBStatement($options = false);
	public static function getDBsStatement($options = false);
	public static function getTablesStatement($db_name = false, $options = false);
	public static function getTableFieldsStatement($table, $db_name = false, $options = false);
	public static function getForeignKeysStatement($table, $db_name = false, $options = false);
	public static function getCreateTableStatement($table_data, $options = false);
	public static function getCreateTableAttributeStatement($attribute_data, $options = false, &$parsed_data = array());
	public static function getRenameTableStatement($old_table, $new_table, $options = false);
	public static function getModifyTableEncodingStatement($table, $charset, $collation, $options = false);
	public static function getModifyTableStorageEngineStatement($table, $engine, $options = false);
	public static function getDropTableStatement($table, $options = false);
	public static function getDropTableCascadeStatement($table, $options = false);
	public static function getAddTableAttributeStatement($table, $attribute_data, $options = false);
	public static function getModifyTableAttributeStatement($table, $attribute_data, $options = false);
	public static function getRenameTableAttributeStatement($table, $old_attribute, $new_attribute, $options = false);
	public static function getDropTableAttributeStatement($table, $attribute, $options = false);
	public static function getAddTablePrimaryKeysStatement($table, $attributes, $options = false);
	public static function getDropTablePrimaryKeysStatement($table, $options = false);
	public static function getAddTableForeignKeyStatement($table, $fk, $options = false);
	public static function getDropTableForeignKeysStatement($table, $options = false);
	public static function getDropTableForeignConstraintStatement($table, $constraint_name, $options = false);
	public static function getAddTableIndexStatement($table, $attributes, $options = false);
	public static function getDropTableIndexStatement($table, $constraint_name, $options = false);
	public static function getTableIndexesStatement($table, $options = false);
	public static function getLoadTableDataFromFileStatement($file_path, $table, $options = false);
	public static function getShowCreateTableStatement($table, $options = false);
	public static function getShowCreateViewStatement($view, $options = false);
	public static function getShowCreateTriggerStatement($trigger, $options = false);
	public static function getShowCreateProcedureStatement($procedure, $options = false);
	public static function getShowCreateFunctionStatement($function, $options = false);
	public static function getShowCreateEventStatement($event, $options = false);
	public static function getShowTablesStatement($db_name, $options = false);
	public static function getShowViewsStatement($db_name, $options = false);
	public static function getShowTriggersStatement($db_name, $options = false);
	public static function getShowTableColumnsStatement($table, $db_name = false, $options = false);
	public static function getShowForeignKeysStatement($table, $db_name = false, $options = false);
	public static function getShowProceduresStatement($db_name, $options = false);
	public static function getShowFunctionsStatement($db_name, $options = false);
	public static function getShowEventsStatement($db_name, $options = false);
	public static function getSetupTransactionStatement($options = false);
	public static function getStartTransactionStatement($options = false);
	public static function getCommitTransactionStatement($options = false);
	public static function getStartDisableAutocommitStatement($options = false);
	public static function getEndDisableAutocommitStatement($options = false);
	public static function getStartLockTableWriteStatement($table, $options = false);
	public static function getStartLockTableReadStatement($table, $options = false);
	public static function getEndLockTableStatement($options = false);
	public static function getStartDisableKeysStatement($table, $options = false);
	public static function getEndDisableKeysStatement($table, $options = false);
	public static function getDropTriggerStatement($trigger, $options = false);
	public static function getDropProcedureStatement($procedure, $options = false);
	public static function getDropFunctionStatement($function, $options = false);
	public static function getDropEventStatement($event, $options = false);
	public static function getDropViewStatement($view, $options = false);
	public static function getShowTableCharsetsStatement($options = false);
	public static function getShowColumnCharsetsStatement($options = false);
	public static function getShowTableCollationsStatement($options = false);
	public static function getShowColumnCollationsStatement($options = false);
	public static function getShowDBStorageEnginesStatement($options = false);
	
	/* Public Static Methods - DBSQLConverter */
	public static function convertObjectToDefaultSQL($data, $options = false);
	public static function convertDefaultSQLToObject($sql, $options = false);
	public static function buildDefaultTableInsertSQL($table_name, $attributes, $options = false);
	public static function buildDefaultTableUpdateSQL($table_name, $attributes, $conditions = false, $options = false);
	public static function buildDefaultTableDeleteSQL($table_name, $conditions = false, $options = false);
	public static function buildDefaultTableFindSQL($table_name, $attributes = false, $conditions = false, $options = false);
	public static function buildDefaultTableCountSQL($table_name, $conditions = false, $options = false);
	public static function buildDefaultTableFindRelationshipSQL($table_name, $rel_elm, $parent_conditions = false, $options = false);
	public static function buildDefaultTableCountRelationshipSQL($table_name, $rel_elm, $parent_conditions = false, $options = false);
	public static function buildDefaultTableFindColumnMaxSQL($table_name, $attribute_name, $options = false);
	
	/* Public Static Methods */
	public static function getDriverClassNameByPath($driver_path);
	public static function getDriverTypeByClassName($driver_class);
	public static function getDriverTypeByPath($driver_path);
	public static function getAvailableDriverClassNames();
	public static function getDriverClassNameByType($type);
	public static function getDriverPathByType($type);
	public static function createDriverByType($type);
	public static function convertDSNToOptions($dsn);
	public static function getDSNByType($type, $options);
	public static function getAllDriverLabelsByType();
	public static function getAllDBConnectionEncodingsByType();
	public static function getAllStorageEnginesByType();
	public static function getAllExtensionsByType();
	public static function getAllIgnoreConnectionOptionsByType();
	public static function getAllIgnoreConnectionOptionsByExtensionAndType();
	public static function getAllColumnTypesByType();
	public static function getAllColumnTypes();
	public static function getAllSharedColumnTypes();
	public static function getAllColumnSimpleTypesByType();
	public static function getAllColumnSimpleTypes();
	public static function getAllSharedColumnSimpleTypes();
	public static function getAllColumnNumericTypesByType();
	public static function getAllColumnNumericTypes();
	public static function getAllSharedColumnNumericTypes();
	public static function getAllColumnDateTypesByType();
	public static function getAllColumnDateTypes();
	public static function getAllSharedColumnDateTypes();
	public static function getAllColumnTextTypesByType();
	public static function getAllColumnTextTypes();
	public static function getAllSharedColumnTextTypes();
	public static function getAllColumnBlobTypesByType();
	public static function getAllColumnBlobTypes();
	public static function getAllSharedColumnBlobTypes();
	public static function getAllColumnBooleanTypesByType();
	public static function getAllColumnBooleanTypes();
	public static function getAllSharedColumnBooleanTypes();
	public static function getAllColumnMandatoryLengthTypesByType();
	public static function getAllColumnMandatoryLengthTypes();
	public static function getAllSharedColumnMandatoryLengthTypes();
	public static function getAllColumnAutoIncrementTypesByType();
	public static function getAllColumnAutoIncrementTypes();
	public static function getAllSharedColumnAutoIncrementTypes();
	public static function getAllBooleanTypeAvailableValuesByType();
	public static function getAllBooleanTypeAvailableValues();
	public static function getAllSharedBooleanTypeAvailableValues();
	public static function getAllCurrentTimestampAvailableValuesByType();
	public static function getAllCurrentTimestampAvailableValues();
	public static function getAllSharedCurrentTimestampAvailableValues();
	public static function getAllColumnTypesIgnoredPropsByType();
	public static function getAllColumnTypesIgnoredProps();
	public static function getAllSharedColumnTypesIgnoredProps();
	public static function getAllColumnTypesHiddenPropsByType();
	public static function getAllColumnTypesHiddenProps();
	public static function getAllSharedColumnTypesHiddenProps();
	public static function getAllAttributeValueReservedWordsByType();
	public static function getAllAttributeValueReservedWords();
	public static function getAllSharedAttributeValueReservedWords();
	public static function getAllReservedWordsByType();
	public static function getAllReservedWords();
	public static function getAllSharedReservedWords();
	
	public static function splitSQL($sql, $options = false);
	public static function removeSQLComments($sql, $options = false);
	public static function removeSQLRepeatedDelimiters($sql, $options = false);
	public static function replaceSQLEnclosingDelimiter($sql, $delimiters_to_search, $delimiters_to_replace);
	public static function isTheSameStaticTableName($table_name_1, $table_name_2, $options = false);
	public static function isStaticTableInNamesList($tables_name_list, $table_to_search, $options = false);
	public static function getStaticTableInNamesList($tables_name_list, $table_to_search, $options = false);
	
	/* Public Methods */
	public function isConnected();
	public function isDBSelected();
	public function getConnectionLink();
	public function getConnectionPHPExtensionType();
	public function getOptions();
	public function getOption($option_name);
	public function setOptions($options, $launch_exception = false);
	public function areOptionsValid($options, $launch_exception = false);
	public function getFunction($function_name, $parameters = false, $options = false);
	public function getData($sql, $options = false);
	public function setData($sql, $options = false);
	public function getSQL($sql, $options = false);
	public function setSQL($sql, $options = false);
	public function isTheSameTableName($table_name_1, $table_name_2);
	public function isTableInNamesList($tables_list, $table_to_search);
	public function getTableInNamesList($tables_list, $table_to_search);
	
	/* Public DAO Methods */
	public function insertObject($table_name, $attributes, $options = false);
	public function updateObject($table_name, $attributes, $conditions = false, $options = false);
	public function deleteObject($table_name, $conditions = false, $options = false);
	public function findObjects($table_name, $attributes = false, $conditions = false, $options = false);
	public function countObjects($table_name, $conditions = false, $options = false);
	public function findRelationshipObjects($table_name, $rel_elm, $parent_conditions = false, $options = false);
	public function countRelationshipObjects($table_name, $rel_elm, $parent_conditions = false, $options = false);
	public function findObjectsColumnMax($table_name, $attribute_name, $options = false);
} 
?>
