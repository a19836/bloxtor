<?php
interface IDBDumper {
	//defined in each driver dumper - abstract methods
	public function databases($db_name);
	
	public function createTable($res, $table_name, $foreign_keys_to_ignore = false);
	public function createRecordsInsertStmt($table_name, $rows);
	public function getSqlStmtWithLimit($sql, $limit);
	public function createStandInTableForView($view_name, $inner_sql);
	public function getTableAttributeProperties($attr_type);
	public function getTableAttributesPropertiesBitHexFunc($attr_name);
	public function getTableAttributesPropertiesBlobHexFunc($attr_name);
	
	public function createView($row);
	public function createTrigger($row);
	public function createProcedure($row);
	public function createFunction($row);
	public function createEvent($row);
	
	public function backupParameters();
	public function restoreParameters();
	public function startDisableConstraintsAndTriggersStmt($tables);
	public function endDisableConstraintsAndTriggersStmt($tables);
	
	//defined in DBDumper - utils
	public function setDBDumperHandler($DBDumperHandler);
	public static function create($class_prefix, $DBDumperHandler);
	public function escapeTableAttributeAlias($alias);
	public function escapeTable($table_name);
	public function escapeTableAttribute($attr_name, $table_name = false);
	public function getDatabaseHeader($db_name);
	public function lockTable($table_name);
	public function unlockTable();
	
	//defined in DBDumper - statments
	public function getDropDatabaseStmt($db_name);
	
	public function getShowTablesStmt($db_name);
	public function getShowTableColumnsStmt($table, $db_name = false);
	public function getShowForeignKeysStmt($table, $db_name = false);
	public function getShowCreateTableStmt($table);
	public function getDropTableStmt($table);
	public function getDropTableForeignConstraintStmt($table, $constraint_name);
	
	public function getStartLockTableWriteStmt($table);
	public function getStartLockTableReadStmt($table);
	public function getEndLockTableStmt();
	public function getStartDisableKeysStmt($table);
	public function getEndDisableKeysStmt($table);
	
	public function getShowViewsStmt($db_name);
	public function getShowCreateViewStmt($view);
	public function getDropViewStmt($view);
	
	public function getShowTriggersStmt($db_name);
	public function getShowCreateTriggerStmt($trigger);
	public function getDropTriggerStmt($trigger);
	
	public function getShowProceduresStmt($db_name);
	public function getShowCreateProcedureStmt($procedure);
	public function getDropProcedureStmt($procedure);
	
	public function getShowFunctionsStmt($db_name);
	public function getShowCreateFunctionStmt($function);
	public function getDropFunctionStmt($function);
	
	public function getShowEventsStmt($db_name);
	public function getShowCreateEventStmt($event);
	public function getDropEventStmt($event);
	
	public function getSetupTransactionStmt();
	public function getStartTransactionStmt();
	public function getCommitTransactionStmt();
	public function getStartDisableAutocommitStmt();
	public function getEndDisableAutocommitStmt();
}
?>
