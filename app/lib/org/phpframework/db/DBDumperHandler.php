<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

include_once get_lib("org.phpframework.compression.FileCompressionFactory");
include_once get_lib("org.phpframework.db.IDB");
include_once get_lib("org.phpframework.db.dump.DBDumper");

class DBDumperHandler {

	const MAX_LINE_SIZE = 1000000; //default of mysqldump

	//compression types - class prefixes
	const GZIP  = 'Gzip';
	const BZIP2 = 'Bzip2';
	const NONE  = 'None';
	const GZIPSTREAM = 'Gzipstream';
	const ZIP = 'Zip';

	//encodings
	const UTF8    = 'utf8';
	const UTF8MB4 = 'utf8mb4';

	//output dump file path
	public $output_file_path = 'php://stdout'; //Default to stdout.
	
	//vars with load data
	private $tables = array();
	private $tables_fks = array();
	private $tables_outside_fks = array();
	private $tables_extra_sql = array();
	private $tables_attributes_types = array();
	private $table_limits = array(); //array('some_table' => 1000)
	private $table_sql_conditions = array(); //array('some_table' => 'created_date > NOW()')
	private $views = array();
	private $triggers = array();
	private $procedures = array();
	private $functions = array();
	private $events = array();
	private $version;
	
	//callables
	private $callable_when_parsing_table_row;
	private $callable_when_parsing_table_row_attribute;
	private $callable_to_parse_table_records_info;
	
	//internal object dependecies and settings
	private $DBDriver = null;
	private $FileCompressionHandler = null;
	private $DBDriverDumper = null;
	private $db_dumper_settings = array();
	private $pdo_db_connection_settings = array();
	
	//internal vars
	private $db_driver_connected_internally = false;
	private $original_db_driver_options = null;
	
	//defaults settings
	private $dump_default_settings = array(
		'include-tables' => array(),
		'exclude-tables' => array(),
		'include-views' => array(),
		'compress' => self::NONE,
		'init_commands' => array(),
		'no-data' => array(),
		'reset-auto-increment' => false,
		'add-drop-database' => false,
		'add-drop-table' => true,
		'add-drop-trigger' => false,
		'add-drop-routine' => false,
		'add-drop-event' => false,
		'add-locks' => true,
		'complete-insert' => false,
		'databases' => false,
		'default-character-set' => self::UTF8,
		'disable-keys' => true,
		'extended-insert' => true,
		'events' => false,
		'hex-blob' => false, /* better than escaped table record values */
		'insert-ignore' => false,
		'net_buffer_length' => self::MAX_LINE_SIZE,
		'no-autocommit' => true,
		'no-create-info' => false,
		'lock-tables' => true,
		'routines' => false,
		'single-transaction' => false,
		'skip-triggers' => false,
		'skip-tz-utc' => false,
		'skip-comments' => false,
		'skip-dump-date' => false,
		'skip-definer' => false,
		'where' => '',
	);

	private $pdo_default_settings = array(
		PDO::ATTR_PERSISTENT => true,
		PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
		PDO::ATTR_ORACLE_NULLS => PDO::NULL_NATURAL,
	);

	/**
	 * @param IDB $DBDriver
	 * @param array $db_dumper_settings: SQL database dump settings
	 * @param array $pdo_db_connection_settings: PDO connection settings
	 */
	public function __construct(IDB $DBDriver, $db_dumper_settings = array(), $pdo_db_connection_settings = array()) {
		if (!$DBDriver)
			throw new Exception("DBDriver cannot be null!");

		$this->DBDriver = $DBDriver;
		$this->original_db_driver_options = $this->DBDriver->getOptions();

		$this->init($db_dumper_settings, $pdo_db_connection_settings);
	}

	public function __destruct() {
		$this->disconnect();
	}

	public function reset() {
		$this->tables = array();
		$this->tables_fks = array();
		$this->tables_outside_fks = array();
		$this->tables_extra_sql = array();
		$this->tables_attributes_types = array();
		$this->table_limits = array();
		$this->table_sql_conditions = array();
		$this->views = array();
		$this->triggers = array();
		$this->procedures = array();
		$this->functions = array();
		$this->events = array();
	}
	
	/* INIT FUNCTIONS */
	
	public function init($db_dumper_settings, $pdo_db_connection_settings) {
		$this->initDBDumperSettings($db_dumper_settings);
		$this->initPdoDBConnectionSettings($pdo_db_connection_settings);
		$this->initFileCompressionHandler();
	}

	public function initDBDumperSettings($db_dumper_settings) {
		$this->db_dumper_settings = $db_dumper_settings ? array_replace_recursive($this->dump_default_settings, $db_dumper_settings) : $this->dump_default_settings;

		if ($this->getDBDriverType() === "mysql") {
			if (empty($this->db_dumper_settings['init_commands']) || !is_array($this->db_dumper_settings['init_commands']))
				$this->db_dumper_settings['init_commands'] = $this->db_dumper_settings['init_commands'] ? array($this->db_dumper_settings['init_commands']) : array();
			
			if (isset($this->db_dumper_settings['skip-tz-utc']) && $this->db_dumper_settings['skip-tz-utc'] === false)
				array_unshift($this->db_dumper_settings['init_commands'], "SET TIME_ZONE='+00:00'");
			
			if (!empty($this->db_dumper_settings['default-character-set']))
				array_unshift($this->db_dumper_settings['init_commands'], "SET NAMES " . $this->db_dumper_settings['default-character-set']);
		}

		$diff = array_diff(array_keys($this->db_dumper_settings), array_keys($this->dump_default_settings));

		if (count($diff) > 0)
			throw new Exception("Unexpected value in db_dumper_settings: (" . implode(",", $diff) . ")");
	
		if (empty($this->db_dumper_settings['include-tables']))
			$this->db_dumper_settings['include-tables'] = array();
		else if (!is_array($this->db_dumper_settings['include-tables']))
			throw new Exception("Include-tables must be array variables");

		if (empty($this->db_dumper_settings['include-tables']))
			$this->db_dumper_settings['include-tables'] = array();
		else if (!is_array($this->db_dumper_settings['exclude-tables']))
			throw new Exception("Exclude-tables must be array variables");
		
		//If no include-views is passed in, dump the same views as tables, mimic mysqldump behaviour.
		if (!isset($db_dumper_settings['include-views']))
			$this->db_dumper_settings['include-views'] = $this->db_dumper_settings['include-tables'];
		else if (!empty($this->db_dumper_settings['include-views']) && !is_array($this->db_dumper_settings['include-views']))
			throw new Exception("Include-views must be array variables");
	}
    	
	public function initPdoDBConnectionSettings($pdo_db_connection_settings) {
		//This drops MYSQL dependency, only use the constant if it's defined.
		if ($this->getDBDriverType() === "mysql")
			$this->pdo_default_settings[PDO::MYSQL_ATTR_USE_BUFFERED_QUERY] = false;
		else
			unset($this->pdo_default_settings[PDO::MYSQL_ATTR_USE_BUFFERED_QUERY]);

		$this->pdo_db_connection_settings = $pdo_db_connection_settings ? array_replace_recursive($this->pdo_default_settings, $pdo_db_connection_settings) : $this->pdo_default_settings;
	}

	public function initFileCompressionHandler() {
		//Create a new FileCompressionHandler to manage compressed output
		$this->FileCompressionHandler = FileCompressionFactory::create(isset($this->db_dumper_settings['compress']) ? $this->db_dumper_settings['compress'] : null);
	}
    
	/* SET/GET FUNCTIONS */
	
	public function setDBDumperSettings($db_dumper_settings) {
		if (!$db_dumper_settings)
			$db_dumper_settings = array();
		
		$this->reset();
		
		$diff = array_diff(array_keys($db_dumper_settings), array_keys($this->dump_default_settings));
		
		if (count($diff) > 0)
			throw new Exception("Unexpected value in \$db_dumper_settings: (" . implode(",", $diff) . ")");

		if (array_key_exists('include-tables', $db_dumper_settings) && !is_array($db_dumper_settings['include-tables']))
			throw new Exception("Include-tables must be array variables");

		if (array_key_exists('exclude-tables', $db_dumper_settings) && !is_array($db_dumper_settings['exclude-tables']))
			throw new Exception("Exclude-tables must be array variables");

		if (array_key_exists('include-views', $db_dumper_settings) && !is_array($db_dumper_settings['include-views']))
			throw new Exception("Include-views must be array variables");

		//If no include-views is passed in, dump the same views as tables, mimic mysqldump behaviour.
		if (!isset($db_dumper_settings['include-views']) && isset($db_dumper_settings['include-tables']))
			$db_dumper_settings['include-views'] = $db_dumper_settings['include-tables'];

		if (array_key_exists('init_commands', $db_dumper_settings)) {
			if (!is_array($db_dumper_settings['init_commands']))
				$db_dumper_settings['init_commands'] = $db_dumper_settings['init_commands'] ? array($db_dumper_settings['init_commands']) : array();

			if ($this->isConnected())
				throw new Exception("'init_commands' cannot be executed because the " . $this->getDBDriverType() . " connection was already initialized! To proceed, please disconnect this driver connection and then call the DBDumperHandler->initDBDumperSettings(db_dumper_settings) method");
			else if ($this->getDBDriverType() === "mysql") {
				if (isset($db_dumper_settings['skip-tz-utc']) && $db_dumper_settings['skip-tz-utc'] === false)
					array_unshift($db_dumper_settings['init_commands'], "SET TIME_ZONE='+00:00'");
				
				if (!empty($db_dumper_settings['default-character-set']))
					array_unshift($db_dumper_settings['init_commands'], "SET NAMES " . $db_dumper_settings['default-character-set']);
				else if (!empty($this->db_dumper_settings['default-character-set']))
					array_unshift($db_dumper_settings['init_commands'], "SET NAMES " . $this->db_dumper_settings['default-character-set']);
			}
		}
		
		$compress_old = isset($this->db_dumper_settings['compress']) ? $this->db_dumper_settings['compress'] : null;
		$compress_new = isset($db_dumper_settings['compress']) ? $db_dumper_settings['compress'] : null;
		$different_settings = $compress_old != $compress_new;
		
		$this->db_dumper_settings = array_replace_recursive($this->db_dumper_settings, $db_dumper_settings);

		if ($different_settings)
			$this->initFileCompressionHandler();
	}

	public function setPdoDBConnectionSettings($pdo_db_connection_settings) {
		$this->pdo_db_connection_settings = $pdo_db_connection_settings ? array_replace_recursive($this->pdo_default_settings, $pdo_db_connection_settings) : $this->pdo_default_settings;
	}

	public function setTableExtraSql($table_name, $sql) {
		$this->tables_extra_sql[$table_name] .= $sql;
	}
    
	//$table_limits: array('some_table' => 100)
	public function setTableLimits($table_limits) {
		if (!is_array($table_limits))
			return false;
		
		$this->table_limits = $table_limits;
		return true;
	}

	public function getTableLimit($table_name) {
		if (!$this->table_limits || !array_key_exists($table_name, $this->table_limits))
			return false;

		$limit = $this->table_limits[$table_name];
		
		if (is_numeric($limit))
			return $limit;
		
		return false;
	}
	
	//$table_sql_conditions: array('some_table' => 'created_date > NOW()')
	public function setTableSQLConditions($table_sql_conditions) {
		if (!is_array($table_sql_conditions))
			return false;
		
		$this->table_sql_conditions = $table_sql_conditions;
		return true;
	}

    	public function getTableSQLCondition($table_name) {
		if (!empty($this->table_sql_conditions[$table_name]))
			return $this->table_sql_conditions[$table_name];
		else if (!empty($this->db_dumper_settings['where']))
			return $this->db_dumper_settings['where'];

		return false;
	}

	public function isConnected() {
		return $this->DBDriver->isConnected();
	}

	public function getDBDumperSettings() {
		return $this->db_dumper_settings;
	}

	public function getDBDriver() {
		return $this->DBDriver;
	}

	public function getFileCompressionHandler() {
		return $this->FileCompressionHandler;
	}

	public function getDBDriverType() {
		return $this->DBDriver->getType();
	}

	public function getConnectionSelectedDBName() {
		return $this->DBDriver->getOption("db_name");
	}

	/* CONNECTION FUNCTIONS */
	
	public function connect() {
		try {
			$options = $this->DBDriver->getOptions();
			
			//if ($this->DBDriver->isConnected())
			//	$this->DBDriver->disconnect();
			
			if (!$this->DBDriver->isConnected()) {
				$options["pdo_settings"] = !empty($options["pdo_settings"]) ? array_merge($options["pdo_settings"], $this->pdo_db_connection_settings) : $this->pdo_db_connection_settings;
				$this->DBDriver->setOptions($options);
				$this->DBDriver->connect();

				$this->db_driver_connected_internally = true;
			}
			else if ($this->pdo_db_connection_settings && $this->DBDriver->getConnectionPHPExtensionType() == "pdo") { //set pdo attributes
				foreach ($this->pdo_db_connection_settings as $k => $v)
					if (empty($options["pdo_settings"]) || !isset($options["pdo_settings"][$k]) || $options["pdo_settings"][$k] != $v)
						$this->DBDriver->getConnectionLink()->setAttribute($k, $v);

				$options["pdo_settings"] = !empty($options["pdo_settings"]) ? array_merge($options["pdo_settings"], $this->pdo_db_connection_settings) : $this->pdo_db_connection_settings;
				$this->DBDriver->setOptions($options);
			}
			
			//Execute init commands once connected
			if (!empty($this->db_dumper_settings['init_commands']))
				foreach ($this->db_dumper_settings['init_commands'] as $stmt)
					$this->DBDriver->setSQL($stmt);
			
			//Store server version
			try {
				$this->version = $this->DBDriver->getVersion();
			}
			catch (Exception $e) {
				$this->version = 0;
			}
		}
		catch (Exception $e) {
			echo "SB connection to " . $this->getDBDriverType()." failed: " . $e->getMessage() . "\n";
			throw $e;
		}

		$this->DBDriverDumper = DBDumper::create($this->DBDriver, $this);

		return $this->DBDriver->isConnected();
	}
    
	public function disconnect() {
		if (!$this->db_driver_connected_internally || $this->DBDriver->disconnect()) {
			if ($this->original_db_driver_options)
				$this->DBDriver->setOptions($this->original_db_driver_options);

			return true;
		}
		
		return false;
	}
	
	/* PUBLIC FUNCTIONS */
	
	//$output_file_path: file path to write sql dump
	public function run($output_file_path = '') {
		//Output file can be redefined here
		if (!empty($output_file_path))
			$this->output_file_path = $output_file_path;

		if (!$this->isConnected()) {
			throw new Exception("Exception on DBDumperHandler::start method because probably the " . $this->getDBDriverType()." DB is not connected");
			return;
		}

		//Create output file
		$this->FileCompressionHandler->open($this->output_file_path);
		
		//Write some basic info to output file
		$text = $this->getDumpFileHeader();
		
		//Store server settings and use sanner defaults to dump
		$text .= $this->DBDriverDumper->backupParameters();
		
		if (!empty($this->db_dumper_settings['databases'])) {
			$text .= $this->DBDriverDumper->getDatabaseHeader( $this->getConnectionSelectedDBName() );
			
			if (!empty($this->db_dumper_settings['add-drop-database']))
				$text .= $this->DBDriverDumper->getDropDatabaseStmt( $this->getConnectionSelectedDBName() );
		}
		$this->FileCompressionHandler->write($text);

		//Get table, view, trigger, procedures, functions and events structures from database.
		$this->LoadDatabaseTables();
		$this->loadDatabaseViews();
		$this->loadDatabaseTriggers();
		$this->loadDatabaseProcedures();
		$this->loadDatabaseFunctions();
		$this->loadDatabaseEvents();

		if (!empty($this->db_dumper_settings['databases'])) {
			$text = $this->DBDriverDumper->databases( $this->getConnectionSelectedDBName() );
			$this->FileCompressionHandler->write($text);
		}

		//If there still are some tables/views in include-tables array, this means that some tables or views weren't found. So throws exception and exit. This code will not happen if include-tables supports regexps.
		if (!empty($this->db_dumper_settings['include-tables']) && count($this->db_dumper_settings['include-tables']) > 0) {
			$name = implode(",", $this->db_dumper_settings['include-tables']);
			
			throw new Exception("Error: User table (" . $name . ") does NOT exists in database");
		}
		
		$this->prepareTablesSQL();
		$this->prepareTriggersSQL();
		$this->prepareFunctionsSQL();
		$this->prepareProceduresSQL();
		$this->prepareViewsSQL();
		$this->prepareEventsSQL();

		//Restore saved parameters.
		$text = $this->DBDriverDumper->restoreParameters();
		
		//Write some stats to output file.
		$text .= $this->getDumpFileFooter();
		
		$this->FileCompressionHandler->write($text);
		
		//Close output file.
		$this->FileCompressionHandler->close();
		
		//return true if previous code executed successfully.
		return true;
	}
    
	/* PRIVATE FUNCTIONS */
	
	private function getDumpFileHeader() {
		$header = '';
		
		if (empty($this->db_dumper_settings['skip-comments'])) {
			$host = $this->DBDriver->getoption("host");

			//Some info about software, source and time
			$header = "--" . PHP_EOL . 
			"-- Host: {$host}" . PHP_EOL . 
			"-- Database: " . $this->getConnectionSelectedDBName() . PHP_EOL .
			"-- ------------------------------------------------------" . PHP_EOL;

			if (!empty($this->version))
				$header .= "-- Server version: " . $this->version . PHP_EOL;

			if (empty($this->db_dumper_settings['skip-dump-date']))
				$header .= "-- Date: " . date('r') . PHP_EOL . PHP_EOL;
		}
		
		return $header;
	}

	private function getDumpFileFooter() {
		$footer = '';
		
		if (empty($this->db_dumper_settings['skip-comments'])) {
			$footer .= '-- Dump completed';
			
			if (empty($this->db_dumper_settings['skip-dump-date']))
				$footer .= ' on: ' . date('r');
			
			$footer .= PHP_EOL;
		}
		
		return $footer;
	}

	private function LoadDatabaseTables() {
		//Listing all tables from database
		$sql = $this->DBDriverDumper->getShowTablesStmt( $this->getConnectionSelectedDBName() );
		$rows = $this->DBDriver->getSQL($sql);
		
		if ($rows) {
			if (empty($this->db_dumper_settings['include-tables'])) {
				//include all tables for now, blacklisting happens later
				foreach ($rows as $row) {
					$name = current($row);
					
					if ($name)
						$this->tables[] = $name;
				}
			} 
			else {
				//include only the tables mentioned in include-tables
				foreach ($rows as $row) {
					$name = current($row);
					
					if ($name && in_array($name, $this->db_dumper_settings['include-tables'], true)) {
						$this->tables[] = $name;
						
						$idx = array_search($name, $this->db_dumper_settings['include-tables']);
						unset($this->db_dumper_settings['include-tables'][$idx]);
					}
				}
			}
			
			$this->reorderDatabaseTables();
		}
		
		return;
	}
    
	private function reorderDatabaseTables() {
		//get tables foreign keys
		$this->tables_fks = array();
		$repeated = array();

		foreach ($this->tables as $i => $table) 
			if (!in_array($table, $repeated)) {
				$this->tables_fks[$table] = array();

				/* row:
					[child_table] => sub_item
					[child_column] => item_id
					[parent_table] => item
					[parent_column] => id
				*/
				$sql = $this->DBDriverDumper->getShowForeignKeysStmt($table);
				$rows = $this->DBDriver->getSQL($sql);
				
				foreach ($rows as $row)
					if ($row) {
						if (!isset($row['child_column']) || !isset($row['parent_table']) || !isset($row['parent_column']) || !isset($row['constraint_name']))
							throw new \Exception("Error getting table foreign keys, unknown output");

						array_push($this->tables_fks[$table], $row);
					}
				   
				//re-order tables in $this->tables, according with the tables foreign keys, this is, tables without foreign keys or parent tables must be first. This means that tables with fks will be at the end of $this->tables.
				if ($this->tables_fks[$table]) {
					unset($this->tables[$i]);
					array_push($this->tables, $table);
				}
			}

		//re-order tables in $this->tables according with FKs. Parent tables should be first.
		//if infinity loop, set settings to dump table foreign key as an alter foreign key, by adding the $fk to $this->tables_outside_fks.
		$parsed = array();

		$this->tables = array_values($this->tables); //very important bc of the length of array_slice method. See code below.

		for ($i = 0; $i < count($this->tables); $i++) {
			$table = $this->tables[$i];

			if (!empty($this->tables_fks[$table])) {
				//echo "\nParsing $table";
				//echo "\nAll fks for $table";print_r($this->tables_fks[$table]);
        		
				foreach ($this->tables_fks[$table] as $fk) {
					$parent_table = isset($fk["parent_table"]) ? $fk["parent_table"] : null; //no need for isset here, bc it was checked before
					$found_index = array_search($parent_table, $this->tables);
					//echo "\n$table: $i\n$parent_table:$found_index\nis parent parsed:" . $parsed[$table]."\n";

					//check table fk and check if parent table is first in $this->tables
					if ($found_index !== false && $i < $found_index) {
						//if (in_array($table, array("item", "sub_item"))){ echo "\nfk for $table:";print_r($fk);}

						//If a table as already parsed and if $i < $found_index, it means that this parsed table was already moved automatically bc of another table, which has a foreign key to it. In this case, we should not move it, otherwise we can have an infinity loop. We should register this table so we can have the correspondent foreign key sql separated from the created table sql.
						if (!empty($parsed[$table])) {
							$this->tables_outside_fks[$table][] = $fk;
							//echo "add outside fk:";print_r($fk);
						}
						else { //if not parsed yet, add the parent table before the current table.
							$this->tables = array_merge(
								array_slice($this->tables, 0, $i), 
								array($this->tables[$found_index]), 
								array_slice($this->tables, $i, $found_index - $i), 
								array_slice($this->tables, $found_index + 1)
							);
							$this->tables = array_values($this->tables);

							$i--; //next $i should be the parent table
						}
					}
				}
			}

			$parsed[$table] = true;
		}
		
		$this->tables = array_values($this->tables);
		//print_r($this->tables);echo "\ntables_outside_fks:";print_r($this->tables_outside_fks);die();
		
		return;
	}

	private function loadDatabaseViews() {
		//Listing all views from database
		$sql = $this->DBDriverDumper->getShowViewsStmt( $this->getConnectionSelectedDBName() );
		$rows = $this->DBDriver->getSQL($sql);
		
		if ($rows) {
			if (empty($this->db_dumper_settings['include-views'])) {
				//include all views for now, blacklisting happens later
				foreach ($rows as $row) {
					$name = current($row);
					
					if ($name)
						$this->views[] = $name;
				}
			}
			else {
				//include only the tables mentioned in include-tables
				foreach ($rows as $row) {
					$name = current($row);
					
					if ($name && in_array($name, $this->db_dumper_settings['include-views'], true)) {
						$this->views[] = $name;
						
						$idx = array_search($name, $this->db_dumper_settings['include-views']);
						unset($this->db_dumper_settings['include-views'][$idx]);
					}
				}
			}
		}
		
		return;
	}

	private function loadDatabaseTriggers() {
		//Listing all triggers from database
		if (isset($this->db_dumper_settings['skip-triggers']) && $this->db_dumper_settings['skip-triggers'] === false) {
			$sql = $this->DBDriverDumper->getShowTriggersStmt( $this->getConnectionSelectedDBName() );
			$rows = $this->DBDriver->getSQL($sql);
			
			foreach ($rows as $row)
				if (!empty($row['trigger_name']))
					$this->triggers[] = $row['trigger_name'];
		}
		
		return;
	}

	private function loadDatabaseProcedures() {
		//Listing all procedures from database
		if (!empty($this->db_dumper_settings['routines'])) {
			$sql = $this->DBDriverDumper->getShowProceduresStmt( $this->getConnectionSelectedDBName() );
			$rows = $this->DBDriver->getSQL($sql);
			
			foreach ($rows as $row)
				if (!empty($row['procedure_name']))
					$this->procedures[] = $row['procedure_name'];
		}
		
		return;
	}

	private function loadDatabaseFunctions() {
		//Listing all functions from database
		if (!empty($this->db_dumper_settings['routines'])) {
			$sql = $this->DBDriverDumper->getShowFunctionsStmt( $this->getConnectionSelectedDBName() );
			$rows = $this->DBDriver->getSQL($sql);
			
			foreach ($rows as $row) 
				if (!empty($row['function_name']))
					$this->functions[] = $row['function_name'];
		}
		
		return;
	}

	private function loadDatabaseEvents() {
		//Listing all events from database
		if (!empty($this->db_dumper_settings['events'])) {
			$sql = $this->DBDriverDumper->getShowEventsStmt( $this->getConnectionSelectedDBName() );
			$rows = $this->DBDriver->getSQL($sql);
			
			foreach ($rows as $row)
				if (!empty($row['event_name']))
					$this->events[] = $row['event_name'];
		}
		
		return;
	}

	private function tableNameExistsIn($table, $patterns) {
		$exists = false;

		if (is_array($patterns)) {
			foreach ($patterns as $pattern) {
				if (!isset($pattern[0]) || $pattern[0] != '/')
					continue;
				
				if (preg_match($pattern, $table) == 1)
					$exists = true;
			}

			return in_array($table, $patterns) || $exists;
		}

		return $exists;
	}

	private function prepareTablesSQL() {
		$text = "";
		
		//Delete some tables' foreign keys first
		if (!empty($this->db_dumper_settings['add-drop-table'])) {
			$sql = "";
			
			foreach ($this->tables as $table)
				if ($this->tables_outside_fks[$table])
					foreach ($this->tables_outside_fks[$table] as $fk)
						if (!empty($fk["constraint_name"]))
							$sql .= $this->DBDriverDumper->getDropTableForeignConstraintStmt($table, $fk["constraint_name"]);

			if ($sql) 
				$text .=  "--" . PHP_EOL . 
						"-- Disable some tables' foreign keys" . PHP_EOL .
						$sql . 
						"--" . PHP_EOL . PHP_EOL;
		}
        
		//Disable all constraints and triggers
		$text .=  "--" . PHP_EOL . 
				"-- Disable all constraints and triggers" . PHP_EOL .
				$this->DBDriverDumper->startDisableConstraintsAndTriggersStmt($this->tables) . 
				"--" . PHP_EOL . PHP_EOL;
		$this->FileCompressionHandler->write($text);

		//Exporting tables structure
		$no_data = isset($this->db_dumper_settings['no-data']) ? $this->db_dumper_settings['no-data'] : null;
		
		foreach ($this->tables as $table) {
			if (!empty($this->db_dumper_settings['exclude-tables']) && $this->tableNameExistsIn($table, $this->db_dumper_settings['exclude-tables']))
				continue;

			$this->prepareTableSQL($table);

			if ($no_data === false) //don't break compatibility with old trigger
				$this->prepareTableRecordsSQL($table);
			else if ($no_data === true || $this->tableNameExistsIn($table, $no_data))
				continue;
			else
				$this->prepareTableRecordsSQL($table);
		}
        	
		//Exporting tables outside foreign keys one by one
		foreach ($this->tables_extra_sql as $table => $extra_sql)
			if ($extra_sql) {
				if (empty($this->db_dumper_settings['skip-comments']))
					$extra_sql =   "--" . PHP_EOL . 
								"-- Foreign keys for table $table" . PHP_EOL .
								"--" . PHP_EOL . PHP_EOL . 
								$extra_sql;

				$this->FileCompressionHandler->write($extra_sql);
			}

		//Re-enable all constraints and triggers
		$this->FileCompressionHandler->write(
			"--" . PHP_EOL . 
			"-- Re-enable all constraints and triggers" . PHP_EOL .
			$this->DBDriverDumper->endDisableConstraintsAndTriggersStmt($this->tables).
			"--" . PHP_EOL . PHP_EOL
		);
	}

	private function prepareViewsSQL() {
		if (isset($this->db_dumper_settings['no-create-info']) && $this->db_dumper_settings['no-create-info'] === false) {
			foreach ($this->views as $view) {
				if (!empty($this->db_dumper_settings['exclude-tables']) && $this->tableNameExistsIn($view, $this->db_dumper_settings['exclude-tables']))
					continue;

				$this->tables_attributes_types[$view] = $this->getTableAttributesTypes($view);
				
				$this->prepareViewTableSQL($view);
			}

			foreach ($this->views as $view) {
				if (!empty($this->db_dumper_settings['exclude-tables']) && $this->tableNameExistsIn($view, $this->db_dumper_settings['exclude-tables']))
					continue;

				$this->prepareViewSQL($view);
			}
		}
	}

	private function prepareTriggersSQL() {
		foreach ($this->triggers as $trigger)
			$this->prepareTriggerSQL($trigger);
	}

	private function prepareProceduresSQL() {
		foreach ($this->procedures as $procedure) 
			$this->prepareProcedureSQL($procedure);
	}

	private function prepareFunctionsSQL() {
		foreach ($this->functions as $function)
			$this->prepareFunctionSQL($function);
	}

	private function prepareEventsSQL() {
		foreach ($this->events as $event)
			$this->prepareEventSQL($event);
	}

	private function prepareTableSQL($table_name) {
		if (empty($this->db_dumper_settings['no-create-info'])) {
			$sql = $this->DBDriverDumper->getShowCreateTableStmt($table_name);
			$rows = $this->DBDriver->getSQL($sql);
			
			$res = array();
			foreach ($rows as $r)
				$res[] = $r;
			
			$sql = $this->DBDriverDumper->createTable($res, $table_name, $this->tables_outside_fks[$table_name]);
			
			if ($sql) {
				if (empty($this->db_dumper_settings['skip-comments']))
					$sql = "--" . PHP_EOL . 
						  "-- Structure for table $table_name" . PHP_EOL .
						  "--" . PHP_EOL . PHP_EOL . 
						  $sql;
				
				//Add code to drop table
				if (!empty($this->db_dumper_settings['add-drop-table']))
					$sql = $this->DBDriverDumper->getDropTableStmt($table_name) . $sql;

				$this->FileCompressionHandler->write($sql);
			}
		}

		$this->tables_attributes_types[$table_name] = $this->getTableAttributesTypes($table_name);
	}
    
	private function prepareTableForeignKeySQL($table_name, $fk) {
		if (empty($this->db_dumper_settings['no-create-info'])) {
			$sql = $this->DBDriverDumper->alter_table_add_foreign_key($table_name, $fk);

			if ($sql) {
				if (empty($this->db_dumper_settings['skip-comments']))
					$sql = "--" . PHP_EOL . 
						  "-- Foreign keys for table $table_name -> " . (isset($fk["parent_table"]) ? $fk["parent_table"] : null) . PHP_EOL .
						  "--" . PHP_EOL . PHP_EOL . 
						  $sql;
				
				$this->FileCompressionHandler->write($sql);
			}
		}
	}

	private function getTableAttributesTypes($table_name) {
		$attr_types = array();
		
		$sql = $this->DBDriverDumper->getShowTableColumnsStmt($table_name);
		$attrs = $this->DBDriver->getSQL($sql);
		
		foreach ($attrs as $key => $attr) {
			$attr_props = $this->DBDriverDumper->getTableAttributeProperties($attr);
			
			if (!empty($attr_props['field']))
				$attr_types[ $attr_props['field'] ] = array(
					'is_numeric'=> isset($attr_props['is_numeric']) ? $attr_props['is_numeric'] : null,
					'is_blob' => isset($attr_props['is_blob']) ? $attr_props['is_blob'] : null,
					'is_boolean' => isset($attr_props['is_boolean']) ? $attr_props['is_boolean'] : null,
					'type' => isset($attr_props['type']) ? $attr_props['type'] : null,
					'type_sql' => isset($attr_props['type_sql']) ? $attr_props['type_sql'] : null,
					'is_virtual' => isset($attr_props['is_virtual']) ? $attr_props['is_virtual'] : null,
					'is_nullable' => isset($attr_props['is_nullable']) ? $attr_props['is_nullable'] : null,
				);
		}

		return $attr_types;
	}

	private function prepareViewTableSQL($view_name) {
		if (empty($this->db_dumper_settings['skip-comments']))
			$this->FileCompressionHandler->write(
				"--" . PHP_EOL . 
				"-- Stand-In structure for view {$view_name}" . PHP_EOL .
				"--" . PHP_EOL . PHP_EOL
			);
		
		$sql = $this->DBDriverDumper->getShowCreateViewStmt($view_name);
		$rows = $this->DBDriver->getSQL($sql);
		
		//create views as tables, to resolve dependencies
		foreach ($rows as $r) {
			if (!empty($this->db_dumper_settings['add-drop-table']))
				$this->FileCompressionHandler->write( $this->DBDriverDumper->getDropTableStmt($view_name) );

			$stand_in_table_sql = $this->prepareViewStandInTableSQL($view_name);

			if ($stand_in_table_sql)
				$this->FileCompressionHandler->write($stand_in_table_sql);

			break;
		}
	}

	public function prepareViewStandInTableSQL($view_name) {
		$ret = array();
		foreach ($this->tables_attributes_types[$view_name] as $k => $v)
			$ret[] = $this->escapeTableAttribute($k, $view_name) . " " . (isset($v["type_sql"]) ? $v["type_sql"] : null);
		
		$ret = implode(PHP_EOL . ",", $ret);

		if (trim($ret))
			return $this->DBDriverDumper->createStandInTableForView($view_name, $ret);
		
		return "";
	}

	private function prepareViewSQL($view_name) {
		if (empty($this->db_dumper_settings['skip-comments']))
			$this->FileCompressionHandler->write(
				"--" . PHP_EOL . 
				"-- Structure for View {$view_name}" . PHP_EOL .
				"--" . PHP_EOL . PHP_EOL
			);

		$sql = $this->DBDriverDumper->getShowCreateViewStmt($view_name);
		$rows = $this->DBDriver->getSQL($sql);
		
		//create views, to resolve dependencies
		//replacing tables with views
		foreach ($rows as $r) {
			//because we must replace table with view, we should delete it
			$this->FileCompressionHandler->write( $this->DBDriverDumper->getDropViewStmt($view_name) );
			$this->FileCompressionHandler->write( $this->DBDriverDumper->createView($r) );
			
			break;
		}
	}

	private function prepareTriggerSQL($trigger_name) {
		$sql = $this->DBDriverDumper->getShowCreateTriggerStmt($trigger_name);
		$rows = $this->DBDriver->getSQL($sql);
		
		foreach ($rows as $row) {
			if (!empty($this->db_dumper_settings['add-drop-trigger']))
				$this->FileCompressionHandler->write( $this->DBDriverDumper->getDropTriggerStmt($trigger_name) );
			
			$this->FileCompressionHandler->write( $this->DBDriverDumper->createTrigger($row) );
			
			return;
		}
	}

	private function prepareProcedureSQL($procedure_name) {
		if (empty($this->db_dumper_settings['skip-comments']))
			$this->FileCompressionHandler->write(
				"--" . PHP_EOL . 
				"-- Procedures for '" . $this->getConnectionSelectedDBName()."' DB" . PHP_EOL .
				"--" . PHP_EOL . PHP_EOL
			);
		
		$sql = $this->DBDriverDumper->getShowCreateProcedureStmt($procedure_name);
		$rows = $this->DBDriver->getSQL($sql);
		
		foreach ($rows as $row) {
			if (!empty($this->db_dumper_settings['add-drop-routine']))
				$this->FileCompressionHandler->write( $this->DBDriverDumper->getDropProcedureStmt($procedure_name) );
			
			$this->FileCompressionHandler->write( $this->DBDriverDumper->createProcedure($row) );
			
			return;
		}
	}

	private function prepareFunctionSQL($function_name) {
		if (empty($this->db_dumper_settings['skip-comments']))
			$this->FileCompressionHandler->write(
				"--" . PHP_EOL . 
				"-- functions for '" . $this->getConnectionSelectedDBName()."' DB" . PHP_EOL .
				"--" . PHP_EOL . PHP_EOL
			);
		
		$sql = $this->DBDriverDumper->getShowCreateFunctionStmt($function_name);
		$rows = $this->DBDriver->getSQL($sql);
		
		foreach ($rows as $row) {
			if (!empty($this->db_dumper_settings['add-drop-routine']))
				$this->FileCompressionHandler->write( $this->DBDriverDumper->getDropFunctionStmt($function_name) );
			
			$this->FileCompressionHandler->write( $this->DBDriverDumper->createFunction($row) );
			
			return;
		}
	}

	private function prepareEventSQL($event_name) {
		if (empty($this->db_dumper_settings['skip-comments']))
			$this->FileCompressionHandler->write(
				"--" . PHP_EOL . 
				"-- Events for '" . $this->getConnectionSelectedDBName()."' DB" . PHP_EOL .
				"--" . PHP_EOL . PHP_EOL
			);

		$sql = $this->DBDriverDumper->getShowCreateEventStmt($event_name);
		$rows = $this->DBDriver->getSQL($sql);
		
		foreach ($rows as $row) {
			if (!empty($this->db_dumper_settings['add-drop-event']))
				$this->FileCompressionHandler->write( $this->DBDriverDumper->getDropEventStmt($event_name) );
			
			$this->FileCompressionHandler->write( $this->DBDriverDumper->createEvent($row) );
			
			return;
		}
	}

	public function prepareTableRowAttributes($table_name, array $row) {
		$ret = array();
		$attr_props = isset($this->tables_attributes_types[$table_name]) ? $this->tables_attributes_types[$table_name] : null;

		if ($this->callable_when_parsing_table_row)
			$row = call_user_func($this->callable_when_parsing_table_row, $table_name, $row);
		
		foreach ($row as $attr_name => $attr_value) {
			if ($this->callable_when_parsing_table_row_attribute)
				$attr_value = call_user_func($this->callable_when_parsing_table_row_attribute, $table_name, $attr_name, $attr_value, $row);
			
			$ret[] = $this->escape($attr_value, isset($attr_props[$attr_name]) ? $attr_props[$attr_name] : null);
		}

		return $ret;
	}
	
	//escapes a value based on type
	private function escape($attr_value, $attr_type) {
		if (is_null($attr_value))
			return "NULL";
		else if (!empty($attr_type['is_numeric']))
			return is_numeric($attr_value) ? $attr_value : (!empty($attr_type["is_nullable"]) ? "NULL" : $this->quote($attr_value));
		else if (!empty($attr_type['is_boolean']))
			return $attr_value ? "true" : "false";
		else if (!empty($this->db_dumper_settings['hex-blob']) && !empty($attr_type['is_blob']) && $this->DBDriverDumper->getTableAttributesPropertiesBlobHexFunc("")) {
			if ((isset($attr_type['type']) && $attr_type['type'] == 'bit') || !empty($attr_value))
				return "0x{$attr_value}";
			else
				return "''";
		}
		else
			return $this->quote($attr_value);
	}
	
	/*
	 * https://dev.mysql.com/doc/refman/8.0/en/string-functions.html#function_quote
	 * Quotes a string to produce a result that can be used as a properly escaped data value in an SQL statement. The string is returned enclosed by single quotation marks and with each instance of backslash (\), single quote ('), ASCII NUL, and Control+Z preceded by a backslash. If the argument is NULL, the return value is the word “NULL” without enclosing single quotation marks.
	 */
	public function quote($str) {
		if ($this->DBDriver)
			switch ($this->DBDriver->getConnectionPHPExtensionType()) {
				case "pdo": 
					$ret = $this->DBDriver->quote($str); //if server doesn't support this feature will return false.
					
					if ($ret !== false)
						return $ret;
			}
		
		return !is_null($str) ? "'" . addcslashes($str, "'\\") . "'" : "NULL"; //TODO: backslash ASCII NUL, and Control+Z 
	}

	//Set method to be called when transforming table rows
	public function setCallableWhenParsingTableRow($callable) {
		$this->callable_when_parsing_table_row = $callable;
	}

	//Set method to be called when transforming column values
	public function setCallableWhenParsingTableRowAttribute($callable) {
		$this->callable_when_parsing_table_row_attribute = $callable;
	}

	//Set method to be called when reporting dump information
	public function setCallableToParseTableRecordsInfo($callable) {
		$this->callable_to_parse_table_records_info = $callable;
	}

	private function prepareTableRecordsSQL($table_name) {
		//col_sql is used to form a query to obtain row values
		$attr_sql = $this->getTableAttributesProperties($table_name);
		$attr_sql = implode(",", $attr_sql);

		$sql = "SELECT " . $attr_sql . " FROM " . $this->escapeTable($table_name);

		//add table condition in where clause
		$condition = $this->getTableSQLCondition($table_name);

		if ($condition)
			$sql .= " WHERE {$condition}";
		
		//add table limit
		$limit = $this->getTableLimit($table_name);

		if ($limit !== false) 
			$sql = $this->DBDriverDumper->getSqlStmtWithLimit($sql, $limit);
		
		//get records
		$rows = $this->DBDriver->getSQL($sql);
		
		if ($rows) {
			//prepare insert statements
			$this->prepareTableRecordsStartSQL($table_name);

			$count = $this->DBDriverDumper->createRecordsInsertStmt($table_name, $rows);
			
			$this->prepareTableRecordsEndSQL($table_name, $count);
			
			//add other info from user callable
			if ($this->callable_to_parse_table_records_info)
				call_user_func($this->callable_to_parse_table_records_info, 'table', array('name' => $table_name, 'rowCount' => $count));
		}
	}

	//Adds some statements before insert statements
	public function prepareTableRecordsStartSQL($table_name) {
		if (empty($this->db_dumper_settings['skip-comments'])) 
			$this->FileCompressionHandler->write(
				"--" . PHP_EOL . 
				"-- Dumping data for table $table_name" . PHP_EOL . 
				"--" . PHP_EOL . PHP_EOL
			);

		if (!empty($this->db_dumper_settings['single-transaction'])) {
			$this->DBDriver->setSQL( $this->DBDriverDumper->getSetupTransactionStmt() );
			$this->DBDriver->setSQL( $this->DBDriverDumper->getStartTransactionStmt() );
		}

		if (!empty($this->db_dumper_settings['lock-tables']) && empty($this->db_dumper_settings['single-transaction']))
			$this->DBDriverDumper->lockTable($table_name);
		
		$text = "";
		
		if (!empty($this->db_dumper_settings['add-locks']))
			$text .= $this->DBDriverDumper->getStartLockTableWriteStmt($table_name);

		if (!empty($this->db_dumper_settings['disable-keys']))
			$text .= $this->DBDriverDumper->getStartDisableKeysStmt($table_name);

		//Disable autocommit for faster reload
		if (!empty($this->db_dumper_settings['no-autocommit']))
			$text .= $this->DBDriverDumper->getStartDisableAutocommitStmt();
		
		if ($text)
			$this->FileCompressionHandler->write($text);
		
		return;
	}

	//Close locks and commits after insert statements
	public function prepareTableRecordsEndSQL($table_name, $count = 0) {
		$text = "";
		
		if (!empty($this->db_dumper_settings['disable-keys']))
			$text .= $this->DBDriverDumper->getEndDisableKeysStmt($table_name);

		if (!empty($this->db_dumper_settings['add-locks']))
			$text .= $this->DBDriverDumper->getEndLockTableStmt($table_name);

		if ($text)
			$this->FileCompressionHandler->write($text);
		
		if (!empty($this->db_dumper_settings['single-transaction']))
			$this->DBDriver->setSQL( $this->DBDriverDumper->getCommitTransactionStmt() );
		
		if (!empty($this->db_dumper_settings['lock-tables']) && empty($this->db_dumper_settings['single-transaction']))
			$this->DBDriverDumper->unlockTable($table_name);

		$text = "";
		
		//Commit to enable autocommit
		if (!empty($this->db_dumper_settings['no-autocommit']))
			$text .= $this->DBDriverDumper->getEndDisableAutocommitStmt();

		$text .= PHP_EOL;

		if (empty($this->db_dumper_settings['skip-comments']))
			$text .= "-- Dumped table " . $table_name." with $count row(s)" . PHP_EOL . 
				    "--" . PHP_EOL . PHP_EOL;

		if ($text)
			$this->FileCompressionHandler->write($text);
		
		return;
	}

	//Creates sql for table columns, so it can be used to create the table SELECT statement
	public function getTableAttributesProperties($table_name) {
		$attrs = array();
		
		foreach ($this->tables_attributes_types[$table_name] as $attr_name => $attr_type) {
			$cn = $this->escapeTableAttribute($attr_name, $table_name);
			
			if ((!empty($attr_type['type']) && $attr_type['type'] == 'bit') && !empty($this->db_dumper_settings['hex-blob']))
				$attrs[] = $this->DBDriverDumper->getTableAttributesPropertiesBitHexFunc($cn) . " AS " . $this->escapeTableAttributeAlias($attr_name);
			else if (!empty($attr_type['is_blob']) && !empty($this->db_dumper_settings['hex-blob']))
				$attrs[] = $this->DBDriverDumper->getTableAttributesPropertiesBlobHexFunc($cn) . " AS " . $this->escapeTableAttributeAlias($attr_name);
			else if (!empty($attr_type['is_virtual'])) {
				$this->db_dumper_settings['complete-insert'] = true;
				continue;
			} 
			else
				$attrs[] = $cn;
		}

		return $attrs;
	}

	//Creates sql for table columns, so it can be used to create the table INSERT statement
	public function getTableAttributesNames($table_name, $with_table_name = false) {
		$attr_names = array();
		
		foreach ($this->tables_attributes_types[$table_name] as $attr_name => $attr_type) {
			if (!empty($attr_type['is_virtual'])) {
				$this->db_dumper_settings['complete-insert'] = true;
				continue;
			} 
			else
				$attr_names[] = $this->escapeTableAttribute($attr_name, $with_table_name ? $table_name : false);
		}
		
		return $attr_names;
	}

	private function escapeTableAttributeAlias($name) {
		return $this->DBDriverDumper->escapeTableAttributeAlias($name);
	}

	private function escapeTable($name) {
		return $this->DBDriverDumper->escapeTable($name);
	}

	private function escapeTableAttribute($attr_name, $table_name = false) {
		return $this->DBDriverDumper->escapeTableAttribute($attr_name, $table_name);
	}
}
?>
