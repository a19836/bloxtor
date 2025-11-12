<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

include_once get_lib("org.phpframework.db.dump.IDBDumper");

abstract class DBDumper implements IDBDumper {
	protected $DBDumperHandler = null;
	
	public function setDBDumperHandler($DBDumperHandler) {
		$this->DBDumperHandler = $DBDumperHandler;
	}

	/* Abstract Methods - defined in each driver dumper */
	
	abstract public function databases($db_name);
	
	abstract public function createTable($res, $table_name, $foreign_keys_to_ignore = false);
	abstract public function createRecordsInsertStmt($table_name, $rows);
	abstract public function getSqlStmtWithLimit($sql, $limit);
	abstract public function createStandInTableForView($view_name, $inner_sql);
	abstract public function getTableAttributeProperties($attr_type);
	abstract public function getTableAttributesPropertiesBitHexFunc($attr_name);
	abstract public function getTableAttributesPropertiesBlobHexFunc($attr_name);
	
	abstract public function createView($row);
	abstract public function createTrigger($row);
	abstract public function createProcedure($row);
	abstract public function createFunction($row);
	abstract public function createEvent($row);
	
	abstract public function backupParameters();
	abstract public function restoreParameters();
	abstract public function startDisableConstraintsAndTriggersStmt($tables);
	abstract public function endDisableConstraintsAndTriggersStmt($tables);

	/* Factory */
	
	public static function create($DBDriver, $DBDumperHandler) {
		return self::createByDriverClass( get_class($DBDriver), $DBDumperHandler);
	}
	
	public static function createByDriverClass($class_prefix, $DBDumperHandler) {
		$valid = self::isDriverClassValid($class_prefix);
		
		if (!$valid)
			throw new Exception("DB dumper method ($class_prefix) is not allowed!");
		
		$class_name = "{$class_prefix}Dumper";
		return new $class_name($DBDumperHandler);
	}
	
	public static function isValid($DBDriver) {
		return self::isDriverClassValid( get_class($DBDriver) );
	}
	
	public static function isDriverClassValid($class_prefix) {
		$class_name = "{$class_prefix}Dumper";
		$file_path = get_lib("org.phpframework.db.dump.{$class_name}");
		
		if (file_exists($file_path)) {
			include_once $file_path;
			
			return is_a($class_name, "IDBDumper", true);
		}
		
		return false;
	}
	
	/* Escape functions */
	
	public function escapeTableAttributeAlias($alias) {
		$delimiters = $this->DBDumperHandler->getDBDriver()->getAliasEnclosingDelimiters();
		$delimiter_begin = isset($delimiters[0]) ? $delimiters[0] : null;
		$delimiter_end = isset($delimiters[1]) ? $delimiters[1] : null;
		
		return $delimiter_begin . $alias . $delimiter_end;
	}
	
	public function escapeTable($table_name) {
		$delimiters = $this->DBDumperHandler->getDBDriver()->getEnclosingDelimiters();
		$delimiter_begin = isset($delimiters[0]) ? $delimiters[0] : null;
		$delimiter_end = isset($delimiters[1]) ? $delimiters[1] : null;
		
		return $delimiter_begin . $table_name . $delimiter_end;
	}

	public function escapeTableAttribute($attr_name, $table_name = false) {
		$delimiters = $this->DBDumperHandler->getDBDriver()->getEnclosingDelimiters();
		$delimiter_begin = isset($delimiters[0]) ? $delimiters[0] : null;
		$delimiter_end = isset($delimiters[1]) ? $delimiters[1] : null;
		
		return $table_name ? $delimiter_begin . $table_name . $delimiter_end . "." . $delimiter_begin . $attr_name . $delimiter_end : $delimiter_begin . $attr_name . $delimiter_end;
	}

	/* Public util functions */
	
	public function getDatabaseHeader($db_name) {
		return "--".PHP_EOL.
		  "-- Current Database: `$db_name`".PHP_EOL.
		  "--".PHP_EOL.PHP_EOL;
	}

	public function lockTable($table_name) {
		$sql = $this->getStartLockTableReadStmt($table_name);
		return $sql ? $this->DBDumperHandler->getDBDriver()->setSQL($sql) : false;
	}

	public function unlockTable() {
		$sql = $this->getEndLockTableStmt();
		return $sql ? $this->DBDumperHandler->getDBDriver()->setSQL($sql) : false;
	}
	
	/* Statement functions */
	
	//Statement functions - Database
	public function getDropDatabaseStmt($db_name) {
		$options = $this->DBDumperHandler->getDBDriver()->getOptions();
		return self::prepareStatement( 
			self::prepareStatement( $this->DBDumperHandler->getDBDriver()->getDropDatabaseStatement($db_name, $options) ) 
		);
	}
	
	//Statement functions - Table
	public function getShowTablesStmt($db_name) {
		$options = $this->DBDumperHandler->getDBDriver()->getOptions();
		return self::prepareStatement( $this->DBDumperHandler->getDBDriver()->getShowTablesStatement($db_name, $options) );
	}
	public function getShowTableColumnsStmt($table, $db_name = false) {
		$options = $this->DBDumperHandler->getDBDriver()->getOptions();
		return self::prepareStatement( $this->DBDumperHandler->getDBDriver()->getShowTableColumnsStatement($table, $db_name, $options) );
	}
	public function getShowForeignKeysStmt($table, $db_name = false) {
		$options = $this->DBDumperHandler->getDBDriver()->getOptions();
		return self::prepareStatement( $this->DBDumperHandler->getDBDriver()->getShowForeignKeysStatement($table, $db_name, $options) );
	}
	public function getShowCreateTableStmt($table) {
		$options = $this->DBDumperHandler->getDBDriver()->getOptions();
		return self::prepareStatement( $this->DBDumperHandler->getDBDriver()->getShowCreateTableStatement($table, $options) );
	}
	public function getDropTableStmt($table) {
		$options = $this->DBDumperHandler->getDBDriver()->getOptions();
		return self::prepareStatement( $this->DBDumperHandler->getDBDriver()->getDropTableCascadeStatement($table, $options) ); //Cascade bc of the foreign keys.
		//return self::prepareStatement( $this->DBDumperHandler->getDBDriver()->getDropTableStatement($table, $options) );
	}
	public function getDropTableForeignConstraintStmt($table, $constraint_name) {
		$options = $this->DBDumperHandler->getDBDriver()->getOptions();
		return self::prepareStatement( $this->DBDumperHandler->getDBDriver()->getDropTableForeignConstraintStatement($table, $options) );
	}
	
	//Statement functions - Table - Locks and Keys
	public function getStartLockTableWriteStmt($table) {
		$options = $this->DBDumperHandler->getDBDriver()->getOptions();
		return self::prepareStatement( $this->DBDumperHandler->getDBDriver()->getStartLockTableWriteStatement($table, $options) );
	}
	public function getStartLockTableReadStmt($table) {
		$options = $this->DBDumperHandler->getDBDriver()->getOptions();
		return self::prepareStatement( $this->DBDumperHandler->getDBDriver()->getStartLockTableReadStatement($table, $options) );
	}
	public function getEndLockTableStmt() {
		$options = $this->DBDumperHandler->getDBDriver()->getOptions();
		return self::prepareStatement( $this->DBDumperHandler->getDBDriver()->getEndLockTableStatement($options) );
	}
	public function getStartDisableKeysStmt($table) {
		$options = $this->DBDumperHandler->getDBDriver()->getOptions();
		return self::prepareStatement( $this->DBDumperHandler->getDBDriver()->getStartDisableKeysStatement($table, $options) );
	}
	public function getEndDisableKeysStmt($table) {
		$options = $this->DBDumperHandler->getDBDriver()->getOptions();
		return self::prepareStatement( $this->DBDumperHandler->getDBDriver()->getEndDisableKeysStatement($table, $options) );
	}
	
	//Statement functions - View
	public function getShowViewsStmt($db_name) {
		$options = $this->DBDumperHandler->getDBDriver()->getOptions();
		return self::prepareStatement( $this->DBDumperHandler->getDBDriver()->getShowViewsStatement($db_name, $options) );
	}
	public function getShowCreateViewStmt($view) {
		$options = $this->DBDumperHandler->getDBDriver()->getOptions();
		return self::prepareStatement( $this->DBDumperHandler->getDBDriver()->getShowCreateViewStatement($view, $options) );
	}
	public function getDropViewStmt($view) {
		$options = $this->DBDumperHandler->getDBDriver()->getOptions();
		return self::prepareStatement( $this->DBDumperHandler->getDBDriver()->getDropViewStatement($view, $options) );
	}
	
	//Statement functions - Trigger
	public function getShowTriggersStmt($db_name) {
		$options = $this->DBDumperHandler->getDBDriver()->getOptions();
		return self::prepareStatement( $this->DBDumperHandler->getDBDriver()->getShowTriggersStatement($db_name, $options) );
	}
	public function getShowCreateTriggerStmt($trigger) {
		$options = $this->DBDumperHandler->getDBDriver()->getOptions();
		return self::prepareStatement( $this->DBDumperHandler->getDBDriver()->getShowCreateTriggerStatement($trigger, $options) );
	}
	public function getDropTriggerStmt($trigger) {
		$options = $this->DBDumperHandler->getDBDriver()->getOptions();
		return self::prepareStatement( $this->DBDumperHandler->getDBDriver()->getDropTriggerStatement($trigger, $options) );
	}
	
	//Statement functions - Procedure
	public function getShowProceduresStmt($db_name) {
		$options = $this->DBDumperHandler->getDBDriver()->getOptions();
		return self::prepareStatement( $this->DBDumperHandler->getDBDriver()->getShowProceduresStatement($db_name, $options) );
	}
	public function getShowCreateProcedureStmt($procedure) {
		$options = $this->DBDumperHandler->getDBDriver()->getOptions();
		return self::prepareStatement( $this->DBDumperHandler->getDBDriver()->getShowCreateProcedureStatement($procedure, $options) );
	}
	public function getDropProcedureStmt($procedure) {
		$options = $this->DBDumperHandler->getDBDriver()->getOptions();
		return self::prepareStatement( $this->DBDumperHandler->getDBDriver()->getDropProcedureStatement($procedure, $options) );
	}
	
	//Statement functions - Function
	public function getShowFunctionsStmt($db_name) {
		$options = $this->DBDumperHandler->getDBDriver()->getOptions();
		return self::prepareStatement( $this->DBDumperHandler->getDBDriver()->getShowFunctionsStatement($db_name, $options) );
	}
	public function getShowCreateFunctionStmt($function) {
		$options = $this->DBDumperHandler->getDBDriver()->getOptions();
		return self::prepareStatement( $this->DBDumperHandler->getDBDriver()->getShowCreateFunctionStatement($function, $options) );
	}
	public function getDropFunctionStmt($function) {
		$options = $this->DBDumperHandler->getDBDriver()->getOptions();
		return self::prepareStatement( $this->DBDumperHandler->getDBDriver()->getDropFunctionStatement($function, $options) );
	}
	
	//Statement functions - Event
	public function getShowEventsStmt($db_name) {
		$options = $this->DBDumperHandler->getDBDriver()->getOptions();
		return self::prepareStatement( $this->DBDumperHandler->getDBDriver()->getShowEventsStatement($db_name, $options) );
	}
	public function getShowCreateEventStmt($event) {
		$options = $this->DBDumperHandler->getDBDriver()->getOptions();
		return self::prepareStatement( $this->DBDumperHandler->getDBDriver()->getShowCreateEventStatement($event, $options) );
	}
	public function getDropEventStmt($event) {
		$options = $this->DBDumperHandler->getDBDriver()->getOptions();
		return self::prepareStatement( $this->DBDumperHandler->getDBDriver()->getDropEventStatement($event, $options) );
	}
	
	//Statement functions - Transaction
	public function getSetupTransactionStmt() {
		$options = $this->DBDumperHandler->getDBDriver()->getOptions();
		return self::prepareStatement( $this->DBDumperHandler->getDBDriver()->getSetupTransactionStatement($options) );
	}
	public function getStartTransactionStmt() {
		$options = $this->DBDumperHandler->getDBDriver()->getOptions();
		return self::prepareStatement( $this->DBDumperHandler->getDBDriver()->getStartTransactionStatement($options) );
	}
	public function getCommitTransactionStmt() {
		$options = $this->DBDumperHandler->getDBDriver()->getOptions();
		return self::prepareStatement( $this->DBDumperHandler->getDBDriver()->getCommitTransactionStatement($options) );
	}
	public function getStartDisableAutocommitStmt() {
		$options = $this->DBDumperHandler->getDBDriver()->getOptions();
		return self::prepareStatement( $this->DBDumperHandler->getDBDriver()->getStartDisableAutocommitStatement($options) );
	}
	public function getEndDisableAutocommitStmt() {
		$options = $this->DBDumperHandler->getDBDriver()->getOptions();
		return self::prepareStatement( $this->DBDumperHandler->getDBDriver()->getEndDisableAutocommitStatement($options) );
	}
	
	/* Private util functions */
	
	private static function prepareStatement($sql) {
		return $sql . ($sql ? (preg_match("/;\s*$/", $sql) ? "" : ";") . PHP_EOL : "");
	}
}
?>
