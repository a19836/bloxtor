<?php
include_once get_lib("org.phpframework.db.IDB");
include_once get_lib("org.phpframework.db.DBSQLConverter");
include_once get_lib("org.phpframework.db.DBStatic");
include_once get_lib("org.phpframework.db.DBDAO");
include_once get_lib("org.phpframework.db.exception.SQLException");

abstract class DB implements IDB { 
	use DBStatic;
	use DBSQLConverter;
	use DBDAO;
	
	/* CONSTANTS */
	
	const FETCH_ASSOC = 1;
	const FETCH_NUM = 2;
	const FETCH_BOTH = 3;
	const FETCH_OBJECT = 4;
	
	/* Protected Variables */
	
	protected $db_selected = false;
	protected $link = null; 
	protected $options = null;
	protected $default_php_extension_type = ""; //will be init on construct
	
	/* Abstract Methods */
	
	abstract public function parseDSN($dsn);
	abstract public function getDSN($options = null);
	abstract public function getVersion();
	abstract public function connect();
	abstract public function connectWithoutDB();
	abstract public function close(); 
	abstract public function ping(); 
	abstract public function setConnectionEncoding($encoding = 'utf8'); 
	abstract public function selectDB($db_name);
	abstract public function error(); 
	abstract public function errno(); 
	abstract public function execute(&$sql, $options = false); 
	abstract public function query(&$sql, $options = false); 
	abstract public function freeResult($result); 
	abstract public function numRows($result); 
	abstract public function numFields($result);
	abstract public function fetchArray($result, $array_type = false);  
	abstract public function fetchField($result, $offset); 
	abstract public function isResultValid($result); 
	
	abstract public function listTables($db_name = false, $options = false); 
	abstract public function listTableFields($table, $options = false); 
	abstract public function listViews($db_name = false, $options = false);
	abstract public function listTriggers($db_name = false, $options = false);
	abstract public function listProcedures($db_name = false, $options = false);
	abstract public function listFunctions($db_name = false, $options = false);
	abstract public function listEvents($db_name = false, $options = false);
	abstract public function getInsertedId($options = false); 
	abstract public function listTableCharsets();
	abstract public function listColumnCharsets();
	abstract public function listTableCollations();
	abstract public function listColumnCollations();
	abstract public function listStorageEngines();
	
	/* Public Methods */
	
	public function disconnect() { 
		return $this->close();
	} 
	
	public function isConnected() { 
		return $this->link ? true : false; 
	}
	
	public function isDBSelected() { 
		return $this->db_selected; 
	}
	
	public function getConnectionLink() { 
		return $this->link; 
	}
	
	public function getConnectionPHPExtensionType() { 
		return $this->default_php_extension_type; 
	}
	
	public function getOptions() { 
		return $this->options; 
	}
	
	public function getOption($option_name) { 
		return isset($this->options[$option_name]) ? $this->options[$option_name] : null;
	}
	
	public function setOptions($options) {
		$this->options = $options;
		
		$this->options["persistent"] = empty($this->options["persistent"]) || $this->options["persistent"] == "false" || $this->options["persistent"] == "0" || $this->options["persistent"] == "null" ? false : true;
		$this->options["new_link"] = empty($this->options["new_link"]) || $this->options["new_link"] == "false" || $this->options["new_link"] == "0" || $this->options["new_link"] == "null" ? false : true;
		$this->options["port"] = isset($this->options["port"]) && is_numeric($this->options["port"]) ? $this->options["port"] : null;
		$this->options["encoding"] = !empty($this->options["encoding"]) ? $this->options["encoding"] : "utf8";
		
		if (empty($this->options["data_source"]) && (empty($this->options["host"]) || empty($this->options["username"]))) {
			launch_exception(new SQLException(18, null, $this->options));
			return false;
		}
		
		return true;	
	}
	
	/*
	 * $options will not be used here, but in the future could be usefull to pass some arguments like user permissions or functions to ignore, etc...
	 */
	public function getFunction($function_name, $parameters = false, $options = false) {
		$exists = method_exists($this, $function_name);
		
		if (!$exists) {
			$fn = strtolower($function_name);
			$class_methods = get_class_methods($this);
			
			if ($class_methods)
				foreach ($class_methods as $method_name)
					if (strtolower($method_name) == $fn) {
						$function_name = $method_name;
						$exists = true;
						break;
					}
		}
		
		if ($exists) {
			$func_args = is_array($parameters) ? $parameters : ($parameters ? array($parameters) : array()); //$parameters could be an array with arguments or a simple attribute (string or numeric) which means it should be converted to first argument of the $function_name method.
			$func_args = array_values($func_args);
			
			//echo "<pre>".$this->getType()."::getFunction: $function_name";print_r($func_args);die();
			$result = @call_user_func_array(array($this, $function_name), $func_args); //Note that the @ is very important here bc in PHP 8 this gives an warning, this is: 'Warning: Array to string conversion in...'
			
			//bc of security issues don't allow password retrieval.
			if ($function_name == "getOptions")
				$result["password"] = "";
			else if ($function_name == "getOption" && $func_args == "password")
				$result = "";
			
			return $result;
		}
		
		return null;
	}
	/* DEPRECATED:
	public function getFunction($function_name, $parameters = false, $options = false) {
		$fn = strtolower($function_name);
		
		switch($fn) {
			//case "createdb": return $this->createDB($parameters, $options); //already below
			case "getselecteddb": return $this->getSelectedDB($options);
			case "listdbs": return $this->listDBs($options);
			//case "listtables": return $this->listTables($parameters, $options); //already below
			//case "listtablefields": return $this->listTableFields($parameters, $options); //already below
			//case "listforeignkeys": return $this->listForeignKeys($parameters, $options); //already below
			case "getinsertedid": return $this->getInsertedId($options);
			//case "getcreatedbstatement": return $this->getCreateDBStatement($parameters, $options); //already below
			case "getselecteddbstatement": return $this->getSelectedDBStatement($options);
			case "getdbsstatement": return $this->getDBsStatement($options);
			//case "gettablesstatement": return $this->getTablesStatement($parameters, $options); //already below
			case "gettablefieldsstatement": return $this->getTableFieldsStatement($parameters[0], $parameters[1], $options);
			case "getforeignkeysstatement": return $this->getForeignKeysStatement($parameters[0], $parameters[1], $options);
			//case "getcreatetablestatement": return $this->getCreateTableStatement($parameters, $options); //already below
			//case "getcreatetableattributestatement": return $this->getCreateTableAttributeStatement($parameters, $options); //already below
			case "getrenametablestatement": return $this->getRenameTableStatement($parameters[0], $parameters[1], $options);
			//case "getdroptablestatement": return $this->getDropTableStatement($parameters, $options); //already below
			case "getaddtableattributestatement": return $this->getAddTableAttributeStatement($parameters[0], $parameters[1], $options);
			case "getmodifytableattributestatement": return $this->getModifyTableAttributeStatement($parameters[0], $parameters[1], $options);
			case "getrenametableattributestatement": return $this->getRenameTableAttributeStatement($parameters[0], $parameters[1], $parameters[2], $options);
			case "getdroptableattributestatement": return $this->getDropTableAttributeStatement($parameters[0], $parameters[1], $options);
			case "getaddtableprimarykeysstatement": return $this->getAddTablePrimaryKeysStatement($parameters[0], $parameters[1], $options);
			//case "getdroptableprimarykeysstatement": return $this->getDropTablePrimaryKeysStatement($parameters, $options); //already below
			//case "getDropTableForeignKeysStatement": return $this->getDropTableForeignKeysStatement($parameters, $options); //already below
			case "getloadtabledatafromfilestatement": return $this->getLoadTableDataFromFileStatement($parameters[0], $parameters[1], $options);
		}
		
		if (method_exists($this, $function_name))
			return call_user_func(array($this, $function_name), $parameters, $options);
		
		return null;
	}*/
	
	public function fetchRow($result) {
		try {
			return $this->fetchArray($result, DB::FETCH_NUM);
		}catch(Exception $e) {
			return launch_exception(new SQLException(9, $e, array($result)));
		}
	} 
	 
	public function fetchAssoc($result) {
		try {
			return $this->fetchArray($result, DB::FETCH_ASSOC);
		}catch(Exception $e) {
			return launch_exception(new SQLException(10, $e, array($result)));
		}
	} 
	 
	public function fetchObject($result) {
		try {
			return $this->fetchArray($result, DB::FETCH_OBJECT);
		}catch(Exception $e) {
			return launch_exception(new SQLException(11, $e, array($result)));
		}
	}
	
	public function getData($sql, $options = false) {
	//echo "$sql<br>\n";
	//error_log($sql . "\n\n", 3, "/tmp/sql_log.log");
	//error_log($sql . "\n\n", 3, $GLOBALS["log_file_path"] ? $GLOBALS["log_file_path"] : "/var/www/html/livingroop/default/tmp/phpframework.log");
	//error_log("sql:$sql\noptions:".print_r($options, 1)."\n\n", 3, "/var/www/html/livingroop/default/tmp/test.log");
		debug_log_function("DB->getData", array($sql), "debug"); //Only overload the logs if is debug type
		
		$options = $options ? $options : array();
		$data = array("fields" => array(), "result" => array());
		
		try {
			if (is_array($sql)) {
				$queries = array();
				
				for ($i = 0, $t = count($sql); $i < $t; $i++) {
					$query = $sql[$i];
					$items = !empty($options["split_sql"]) ? self::splitSQL($query, $options) : array((
						!empty($options["remove_comments"]) ? self::removeSQLComments($query, $options) : (
							!empty($options["remove_repeated_semicolons"]) ? self::removeSQLRepeatedDelimiters($query, $options) : $query
						)
					)); //Note that by default it should NOT split sql so we can execute multiple commands directly like a store procedure.
					
					$queries = array_merge($queries, $items);
				}
			}
			else
				$queries = !empty($options["split_sql"]) ? self::splitSQL($sql, $options) : array((
					!empty($options["remove_comments"]) ? self::removeSQLComments($sql, $options) : (
						!empty($options["remove_repeated_semicolons"]) ? self::removeSQLRepeatedDelimiters($sql, $options) : $sql
					)
				)); //Note that by default it should NOT split sql so we can execute multiple commands directly like a store procedure.
			//echo $sql;print_r($queries);die();
			//error_log("\nqueries:".print_r($queries, true) . "\n\n", 3, "/var/www/html/livingroop/default/tmp/test.log");
			
			for ($i = 0, $t = count($queries); $i < $t; $i++) {
				$query = trim($queries[$i]);
				
				if($query) {
					//execute query
					$result = $this->query($query, $options);
					
					//if result is empty and connection inactive, reconnect and query again
					$reconnect = !empty($options["reconnect"]) || !empty($this->options["reconnect"]);
					
					if ($reconnect && empty($result) && !$this->ping() && ( 
						(!empty($this->options["db_name"]) && $this->connect()) || 
						(empty($this->options["db_name"]) && $this->connectWithoutDB())
					))
						$result = $this->query($query, $options);
					
					//prepare result
					if ($result) {
						if ($this->isResultValid($result)) {//mysqli and pg result
							//prepare fields
							$prepare_fields = empty($options["return_type"]) || $options["return_type"] != "result";
							
							if ($prepare_fields) {
								$count = $this->numFields($result);
								$get_fields_from_results = false;
								
								for ($j = 0; $j < $count; $j++) {
									$field = $this->fetchField($result, $j);
									$data["fields"][] = $field;
									
									if (!$field)
										$get_fields_from_results = true;
								}
							}
							
							//prepare results
							$prepare_results = empty($options["return_type"]) || $options["return_type"] != "fields";
							
							if ($prepare_results)
								while ($row = $this->fetchAssoc($result))
									$data["result"][] = $row;
							
							//set fields if none set before. Note that fetchField in PDO doesn't work for some drivers like mssql server, so we need to perfomr this code.
							if ($prepare_fields && !empty($get_fields_from_results) && !empty($data["result"][0])) {
								$j = 0;
								
								foreach ($data["result"][0] as $k => $v)
									if (!is_numeric($k)) {
										$obj = new stdClass();
										$obj->name = $k;
										
										$data["fields"][$j] = $obj;
										$j++;
									}
							}
							
							$this->freeResult($result);
						}
						else if ($result === true) //if query is insert/update/delete query returns true (if is true)
							$data["result"] = $result;
						else
							launch_exception(new SQLException(21, null, array($query))); //This will be catched in the code below
					}
					else { //It means the result is false. Note that we may want to use this method for insert/update/delete queries, which means the result can be true or false. If false launch exception
						launch_exception(new SQLException(6, null, array($query))); //This will be catched in the code below
					}
				}
			}
		} 
		catch(Exception $e) {
			$error = $this->error();
			
			if ($error) {
				$error_code = $this->errno();
				
				$error = " NATIVE ERROR on query #" . ($i + 1) . ($error_code ? "[Code: $error_code]" : "") . ":" . PHP_EOL . $error . PHP_EOL . "Completed SQL: " . (is_array($sql) ? implode("\n", $sql) : $sql); //$e already contains the individual sql query executed
				$e = new SQLException(5, $e, array($error));
			}
			
			launch_exception($e);
		}
		
		//return data
		if (!empty($options["return_type"]))
			switch (strtolower($options["return_type"])) {
				case "fields": return $data["fields"];
				case "result": return $data["result"];
			}
		
		return $data;
	}
	
	public function setData($sql, $options = false) {
	//echo "SELECTED DATABASE: ".print_r($this->getData("select DATABASE() as DB;")["result"][0]["DB"], 1)."<br>";
	//echo "$sql<br>\n";
	//error_log($sql . "\n\n", 3, "/tmp/sql_log.log");
	//error_log($sql . "\n\n", 3, $GLOBALS["log_file_path"] ? $GLOBALS["log_file_path"] : "/var/www/html/livingroop/default/tmp/phpframework.log");
		//error_log("DB setData sql:$sql\noptions:".print_r($options, 1)."\n\n", 3, "/var/www/html/livingroop/default/tmp/test.log");
		debug_log_function("DB->setData", array($sql), "debug"); //Only overload the logs if is debug type
		
		$status = true;
		
		try {
			$options = $options ? $options : array();
			
			if (is_array($sql)) {
				$queries = array();
				
				for ($i = 0, $t = count($sql); $i < $t; $i++) {
					$query = $sql[$i];
					$items = !empty($options["split_sql"]) ? self::splitSQL($query, $options) : array((
						!empty($options["remove_comments"]) ? self::removeSQLComments($query, $options) : (
							!empty($options["remove_repeated_semicolons"]) ? self::removeSQLRepeatedDelimiters($query, $options) : $query
						)
					)); //Note that by default it should NOT split sql so we can execute multiple commands directly like a store procedure.
					
					$queries = array_merge($queries, $items);
				}
			}
			else
				$queries = !empty($options["split_sql"]) ? self::splitSQL($sql, $options) : array((
					!empty($options["remove_comments"]) ? self::removeSQLComments($sql, $options) : (
						!empty($options["remove_repeated_semicolons"]) ? self::removeSQLRepeatedDelimiters($sql, $options) : $sql
					)
				)); //Note that by default it should NOT split sql so we can execute multiple commands directly like a store procedure.
			//echo $sql;print_r($queries);die();
			//error_log("\nqueries:".print_r($queries, true) . "\n\n", 3, "/var/www/html/livingroop/default/tmp/test.log");
			
			for ($i = 0, $t = count($queries); $i < $t; $i++) {
				$query = trim($queries[$i]);
				
				if($query) {
					//execute query
					$result = $this->execute($query, $options);
					
					//if result is empty and connection inactive, reconnect and query again
					$reconnect = !empty($options["reconnect"]) || !empty($this->options["reconnect"]);
					
					if ($reconnect && empty($result) && !$this->ping() && ( 
						(!empty($this->options["db_name"]) && $this->connect()) || 
						(empty($this->options["db_name"]) && $this->connectWithoutDB())
					))
						$result = $this->execute($query, $options);
					
					//prepare result
					if ($result === false)
						$status = false;
					else if ($result && $this->isResultValid($result))
						$this->freeResult($result);
				}
			}
		} 
		catch(Exception $e) {
			$status = false;
			
			$error = $this->error();
			
			if ($error) {
				$error_code = $this->errno();
				
				$error = " NATIVE ERROR on query #" . ($i + 1) . ($error_code ? "[Code: $error_code]" : "") . ":" . PHP_EOL . $error . PHP_EOL . "Completed SQL: " . (is_array($sql) ? implode("\n", $sql) : $sql); //$e already contains the individual sql query executed
				$e = new SQLException(5, $e, array($error));
			}
			
			launch_exception($e);
			return $status; //this makes sure that the code below is not executed
		}
		
		if (!$status && $this->error()) {
			$error = $this->error();
			$error_code = $this->errno();
			
			$error = " NATIVE ERROR on query #" . ($i + 1) . ($error_code ? "[Code: $error_code]" : "") . ":" . PHP_EOL . $error . PHP_EOL . "Completed SQL: " . (is_array($sql) ? implode("\n", $sql) : $sql);
			
			$SQLException = new SQLException(5, null, array($error));
			launch_exception($SQLException);
		}
		
		return $status;
	}
	
	public function getSQL($sql, $options = false) {
		$options = $options ? $options : array();
		$options["return_type"] = "result";
		
		return $this->getData($sql, $options);
	}
	
	public function setSQL($sql, $options = false) {
		return $this->setData($sql, $options) === true;
	}
	
	public function createDB($db_name, $options = false) {
		$status = false;
		
		if ($db_name) {
			//connects to server
			if (!$this->link)
				$this->connectWithoutDB();
			
			if ($this->link) {
				//check if DB exists
				$dbs = $this->listDBs($options);
				
				if ($dbs && !in_array($db_name, $dbs)) {
					//if DB doesnt exist, creates it
					$sql = static::getCreateDBStatement($db_name, $this->options);
					$status = $this->setData($sql, $options);
					sleep(1); //just in case sleeps 1 second before it selects the new DB. This should give time to the DB engine sets the new DB.
				}
				
				if (!$this->db_selected) {
					$this->options["db_name"] = $db_name;
					$this->db_selected = $this->selectDB($db_name);
				}
			}
			
			if (!$this->link || !$this->db_selected)
				launch_exception(new SQLException(1, null, $this->options));
		}
		
		return $status;
	}
	
	public function getSelectedDB($options = false) {
		$options = $options ? $options : array();
		$options["return_type"] = "result";
		$sql = static::getSelectedDBStatement();
		$result = $this->getData($sql, $options);

		return $result && isset($result[0]["db"]) ? $result[0]["db"] : null;
	}
	
	public function listDBs($options = false, $column_name = "name") {
		$dbs = array();
		
		$options = $options ? $options : array();
		$options["return_type"] = "result";
		$sql = static::getDBsStatement();
		$result = $this->getData($sql, $options);
		
		if($result) 
			foreach ($result as $row)
				if (isset($row[$column_name]))
					$dbs[] = $row[$column_name];
		
		return $dbs;
	}
	
	public function listForeignKeys($table, $options = false) {
		$rows = array();
		
		$db_name = !$this->isDBSelected() && !empty($this->options["db_name"]) ? $this->options["db_name"] : null;
		$sql = static::getForeignKeysStatement($table, $db_name, $this->options);
		
		if (empty($this->options["db_name"]))
			return launch_exception(new SQLException(19, null, $sql));
		
		$options = $options ? $options : array();
		$options["return_type"] = "result";
		$result = $this->getData($sql, $options);
		
		if($result)
			foreach ($result as $field) 
				$rows[] = $field;
		
		return $rows;
	}
	
	public function convertObjectToSQL($data, $options = false) {
		return self::convertObjectToDefaultSQL($data, $options);
	}
	
	public function convertSQLToObject($sql, $options = false) {
		return self::convertDefaultSQLToObject($sql, $options);
	}
	
	public function buildTableInsertSQL($table_name, $attributes, $options = false) {
		return self::buildDefaultTableInsertSQL($table_name, $attributes, $options);
	}
	
	public function buildTableUpdateSQL($table_name, $attributes, $conditions = false, $options = false) {
		return self::buildDefaultTableUpdateSQL($table_name, $attributes, $conditions, $options);
	}
	
	public function buildTableDeleteSQL($table_name, $conditions = false, $options = false) {
		return self::buildDefaultTableDeleteSQL($table_name, $conditions, $options);
	}
	
	public function buildTableFindSQL($table_name, $attributes = false, $conditions = false, $options = false) {
		return self::buildDefaultTableFindSQL($table_name, $attributes, $conditions, $options);
	}
	
	public function buildTableCountSQL($table_name, $conditions = false, $options = false) {
		return self::buildDefaultTableCountSQL($table_name, $conditions, $options);
	}
	
	public function buildTableFindRelationshipSQL($table_name, $rel_elm, $parent_conditions = false, $options = false) {
		return self::buildDefaultTableFindRelationshipSQL($table_name, $rel_elm, $parent_conditions, $options);
	}
	
	public function buildTableCountRelationshipSQL($table_name, $rel_elm, $parent_conditions = false, $options = false) {
		return self::buildDefaultTableCountRelationshipSQL($table_name, $rel_elm, $parent_conditions, $options);
	}
	
	public function buildTableFindColumnMaxSQL($table_name, $attribute_name, $options = false) {
		return self::buildDefaultTableFindColumnMaxSQL($table_name, $attribute_name, $options);
	}
	
	public function isTheSameTableName($table_name_1, $table_name_2) {
		$options = $this->getOptions();
		
		if (empty($options["schema"]))
			$options["schema"] = static::getDefaultSchema();
		
		return self::isTheSameStaticTableName($table_name_1, $table_name_2, $options);
	}
	
	public function isTableInNamesList($tables_list, $table_to_search) {
		$table = $this->getTableInNamesList($tables_list, $table_to_search);
		return !empty($table);
	}
	
	public function getTableInNamesList($tables_list, $table_to_search) {
		$options = $this->getOptions();
		
		if (empty($options["schema"]))
			$options["schema"] = static::getDefaultSchema();
		
		return self::getStaticTableInNamesList($tables_list, $table_to_search, $options);
	}
	
	/* Protected Methods */
	
	protected function init() {
		return $this->link ? true : $this->connect();
	}
	
	/* Protected Static Methods with constants - Particular case bc traits don't allow constants */
	
	//PDO: PDO::FETCH_ASSOC, PDO::FETCH_NUM, PDO::FETCH_BOTH
	//ODBC: odbc_fetch_array as associative; odbc_fetch_row as numeric
	protected static function convertFetchTypeToPDOAndODBCExtensions($php_extension_type, $fetch_type) {
		switch ($php_extension_type) {
			case "pdo": 
				switch ($fetch_type) {
					case DB::FETCH_ASSOC: return PDO::FETCH_ASSOC;
					case DB::FETCH_NUM: return PDO::FETCH_NUM;
					case DB::FETCH_BOTH: return PDO::FETCH_BOTH;
				}
				break;
			case "odbc": 
				return $fetch_type;
		}
		
		return null;
	}
}
?>
