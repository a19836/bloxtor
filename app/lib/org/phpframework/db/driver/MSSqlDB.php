<?php
//To install odbc drivers for MSSQL in ubuntu please follow the tutorial:
//https://docs.microsoft.com/en-us/sql/connect/odbc/linux-mac/installing-the-microsoft-odbc-driver-for-sql-server?view=sql-server-ver15

include_once get_lib("org.phpframework.db.DB");
include_once get_lib("org.phpframework.db.statement.MSSqlDBStatement");
include_once get_lib("org.phpframework.db.property.MSSqlDBProperty");
include_once get_lib("org.phpframework.db.static.MSSqlDBStatic");

class MSSqlDB extends DB {
	use MSSqlDBStatement;
	use MSSqlDBProperty;
	use MSSqlDBStatic;
	
	const DEFAULT_DB_NAME = 'msdb'; //by default if no dbname is passed, ms-sql-server engine takes username as the default database, so we must set it to the default database.
	
	public function __construct() {
		if (!$this->default_php_extension_type) {
			$exts = self::getAvailablePHPExtensionTypes();
			$this->default_php_extension_type = isset($exts[0]) ? $exts[0] : null;
		}
	}
	
	public function parseDSN($dsn) {
		return self::convertDSNToOptions($dsn);
	}
	
	/*
	 * sqlsrv_connect($server, array("Database" => $database, "UID" => $user, "PWD" => $password, "CharacterSet" => $encoding));
	 * 
	 * $conn=new PDO("odbc:Driver=$driver;Server=$server;Database=$database;charset=$encoding;", $user, $password); //with odbc Driver
	 * $conn=new PDO('odbc:MSSQL_PHP', $user, $password); //with odbc data_source in /etc/odbc.ini
	 * $conn=new PDO("sqlsrv:Server=$server;Database=$database;charset=$encoding;", $user, $password); //with sqlsvr pdo extension
	 * 
	 * $conn=odbc_connect("Driver={$driver};Server=$server;Database=$database;charset=$encoding;", $user, $password); //with odbc Driver
	 * $conn=odbc_connect($data_source, $user, $password); //with odbc data_source in /etc/odbc.ini
	 */
	public function getDSN($options = null) {
		$options = $options ? $options : $this->options;
		$dsn = in_array("pdo", self::$available_php_extension_types) ? 'odbc:' : '';
		$driver = !empty($options["odbc_driver"]) ? $options["odbc_driver"] : null;
		$data_source = !empty($options["odbc_data_source"]) ? $options["odbc_data_source"] : null;
		$with_host = $with_dbname = false;
		$pdo_exists = in_array("pdo", self::$available_php_extension_types);
		
		if ($data_source) //if $data_source, sets data_source first
			$dsn .= $data_source . (substr($data_source, -1) == ";" ? "" : ";"); //odbc:MSSQL_PHP
		else if (empty($options["host"]) && self::$default_odbc_data_source) //then if no $data_source and no $options["host"], sets self::$default_odbc_data_source
			$dsn .= self::$default_odbc_data_source . (substr(self::$default_odbc_data_source, -1) == ";" ? "" : ";"); //odbc:MSSQL_PHP
		else if ($driver) //Then if $data_source and self::$default_odbc_data_source do NOT exists, sets $driver. Host should exists, otherwise it will give a connection error, on purpose!
			$dsn .= 'Driver={' . $driver . '};';
		else if (self::$default_odbc_driver) //Then if $data_source, self::$default_odbc_data_source and $driver do NOT exists, sets self::$default_odbc_driver
			$dsn .= 'Driver={' . self::$default_odbc_driver . '};';
		else if ($pdo_exists) //Then if none above exists, sets default pdo extension.
			$dsn = 'sqlsvr:';
		
		//if ($pdo_exists)
		//	$with_host = $with_dbname = true;
		
		if (!empty($options["host"]))
			$dsn .= ($with_host ? 'host' : 'Server') . "=" . $options["host"] . (!empty($options["port"]) ? ':' . $options["port"] : '') . ';';
		
		if (!empty($options["db_name"]))
			$dsn .= ($with_dbname ? 'dbname' : 'Database') . '=' . $options["db_name"] . ';';
		
		if (!empty($options["encoding"]))
			$dsn .= 'charset=' . $options["encoding"] . ';';
		
		if (!empty($options["extra_dsn"]))
			$dsn .= $options["extra_dsn"];
		
		return $dsn;
	}
	
	public function getVersion() {
		if ($this->link)
			switch ($this->default_php_extension_type) {
				case "sqlsrv": 
					$res = sqlsrv_server_info($this->link);
					return isset($res["SQLServerVersion"]) ? $res["SQLServerVersion"] : null;
				case "pdo": return $this->link->getAttribute(PDO::ATTR_SERVER_VERSION);
				case "odbc": return null;
			}
		return null;
	}
	
	public function connect() {
		$this->db_selected = false;
		
		try{
			//close previous connection if exists
			if ($this->link)
				$this->close();
			
			$this->default_php_extension_type = !empty($this->options["extension"]) ? $this->options["extension"] : $this->default_php_extension_type;
			
			switch ($this->default_php_extension_type) {
				case "sqlsrv":
					$server_name = isset($this->options["host"]) ? $this->options["host"] . (!empty($this->options["port"]) ? ', ' . $this->options["port"] : '') : null;
					$connection_info = array();
					
					if (!empty($this->options["db_name"]))
						$connection_info["Database"] = $this->options["db_name"];
					
					if (!empty($this->options["username"])) {
						$connection_info["UID"] = $this->options["username"];
						
						if (!empty($this->options["password"]))
							$connection_info["PWD"] = $this->options["password"];
					}
					
					if (!empty($this->options["encoding"]))
						$connection_info["CharacterSet"] = $this->options["encoding"]; //Beware though that only two options exist: 'UTF-8' and SQLSRV_ENC_CHAR (constant) for ANSI, which is the default
					
					//prepare extra settings
					$parsed_extra_settings = isset($this->options["extra_settings"]) ? self::parseExtraSettings($this->options["extra_settings"]) : null;
					
					if ($parsed_extra_settings)
						foreach ($parsed_extra_settings as $es_name => $es_value)
							if ($es_name)
								$connection_info[$es_name] = $es_value;
					
					//Note: I could not find how to create persistent connections through sqlsrv_ extension.
					$this->link = sqlsrv_connect($server_name, $connection_info);
					break;
				
				case "pdo":
					$pdo_settings = !empty($this->options["pdo_settings"]) ? $this->options["pdo_settings"] : array();
					
					if (!array_key_exists(PDO::ATTR_ERRMODE, $pdo_settings))
						$pdo_settings[PDO::ATTR_ERRMODE] = PDO::ERRMODE_EXCEPTION;
			   		
					if(!empty($this->options["persistent"]) && empty($this->options["new_link"]))
						$pdo_settings[PDO::ATTR_PERSISTENT] = true;
					
					//prepare extra settings
					$extra_pdo_settings = isset($this->options["extra_settings"]) ? self::parseExtraSettingsAsPDOSettings($this->options["extra_settings"]) : null;
					
					if ($extra_pdo_settings)
						foreach ($extra_pdo_settings as $pdos_name => $pdos_value)
							if ($pdos_name)
								$pdo_settings[$pdos_name] = $pdos_value;
					
					$dsn = self::getDSN($this->options);
					$this->link = new PDO(
						$dsn, 
						isset($this->options["username"]) ? $this->options["username"] : null, 
						isset($this->options["password"]) ? $this->options["password"] : null, 
						$pdo_settings
					);
					break;
				
				case "odbc":
					$dsn = self::getDSN($this->options);
					
					if(!empty($this->options["persistent"]) && empty($this->options["new_link"]))
						$this->link = odbc_pconnect(
							$dsn, 
							isset($this->options["username"]) ? $this->options["username"] : null, 
							isset($this->options["password"]) ? $this->options["password"] : null
						);
					else
						$this->link = odbc_connect(
							$dsn, 
							isset($this->options["username"]) ? $this->options["username"] : null, 
							isset($this->options["password"]) ? $this->options["password"] : null
						);
					break;
			}
			
			if ($this->link && !empty($this->options["db_name"]))
				$this->db_selected = true;
			
			if (!$this->link || !$this->db_selected) {
				$e = null;
				$error = $this->default_php_extension_type == "sqlsrv" && sqlsrv_errors() ? print_r(sqlsrv_errors(), true) : ($this->default_php_extension_type == "odbc" ? odbc_errormsg() : null);
				
				if ($error)
					$e = new Exception("Failed to connect to MSSQL: " . $error);
				
				launch_exception(new SQLException(1, $e, $this->options));
			}
		} 
		catch(Exception $e) {
			launch_exception(new SQLException(1, $e, $this->options));
		}
		
		//error_log("DB '" . $this->options["db_name"] . "' is connected? OK:". $this->db_selected . "\n", 3, "/tmp/sql_log.log");
		return $this->db_selected;
	}
	
	public function connectWithoutDB() {
		try{
			//close previous connection if exists
			if ($this->link)
				$this->close();
			
			$this->default_php_extension_type = !empty($this->options["extension"]) ? $this->options["extension"] : $this->default_php_extension_type;
			
			switch ($this->default_php_extension_type) {
				case "sqlsrv":
					$server_name = isset($this->options["host"]) ? $this->options["host"] . (!empty($this->options["port"]) ? ', ' . $this->options["port"] : '') : null;
					$connection_info = array();
					
					$connection_info["Database"] = self::DEFAULT_DB_NAME; //by default if no dbname is passed, ms-sql-server engine takes username as the default database, so we must set it to the default database.
					
					if (!empty($this->options["username"])) {
						$connection_info["UID"] = $this->options["username"];
						
						if (!empty($this->options["password"]))
							$connection_info["PWD"] = $this->options["password"];
					}
					
					if (!empty($this->options["encoding"]))
						$connection_info["CharacterSet"] = $this->options["encoding"]; //Beware though that only two options exist: 'UTF-8' and SQLSRV_ENC_CHAR (constant) for ANSI, which is the default
					
					//Note: I could not find how to create persistent connections through sqlsrv_ extension.
					$this->link = sqlsrv_connect($server_name, $connection_info);
					break;
				
				case "pdo":
					$pdo_settings = !empty($this->options["pdo_settings"]) ? $this->options["pdo_settings"] : array();
					
					if (!array_key_exists(PDO::ATTR_ERRMODE, $pdo_settings))
						$pdo_settings[PDO::ATTR_ERRMODE] = PDO::ERRMODE_EXCEPTION;
			   		
					if(!empty($this->options["persistent"]) && empty($this->options["new_link"]))
						$pdo_settings[PDO::ATTR_PERSISTENT] = true;
					
					$options = $this->options;
					$options["db_name"] = self::DEFAULT_DB_NAME; //by default if no dbname is passed, ms-sql-server engine takes username as the default database, so we must set it to the default database.
					$dsn = self::getDSN($options);
					
					$this->link = new PDO(
						$dsn, 
						isset($this->options["username"]) ? $this->options["username"] : null, 
						isset($this->options["password"]) ? $this->options["password"] : null, 
						$pdo_settings
					);
					break;
				
				case "odbc":
					$options = $this->options;
					$options["db_name"] = self::DEFAULT_DB_NAME; //by default if no dbname is passed, ms-sql-server engine takes username as the default database, so we must set it to the default database.
					$dsn = self::getDSN($options);
					
					if(!empty($this->options["persistent"]) && empty($this->options["new_link"]))
						$this->link = odbc_pconnect(
							$dsn, 
							isset($this->options["username"]) ? $this->options["username"] : null, 
							isset($this->options["password"]) ? $this->options["password"] : null
						);
					else
						$this->link = odbc_connect(
							$dsn, 
							isset($this->options["username"]) ? $this->options["username"] : null, 
							isset($this->options["password"]) ? $this->options["password"] : null
						);
					break;
			}
			
			if (!$this->link) {
				$e = null;
				$error = $this->default_php_extension_type == "sqlsrv" && sqlsrv_errors() ? print_r(sqlsrv_errors(), true) : ($this->default_php_extension_type == "odbc" ? odbc_errormsg() : null);
				
				if ($error)
					$e = new Exception("Failed to connect to MSSQL: " . $error);
				
				launch_exception(new SQLException(1, $e, $this->options));
			}
		} 
		catch(Exception $e) {
			launch_exception(new SQLException(1, $e, $this->options));
		}
		
		return $this->link ? true : false;
	}
	 
	public function close() { 
		try {
			if ($this->link) {
				$closed = true;
				
				if ($this->ping())
					switch ($this->default_php_extension_type) {
						case "sqlsrv": $closed = sqlsrv_close($this->link); break;
						case "odbc": odbc_close($this->link); $closed = true; break;
					}
				
				if ($closed) {
					$this->db_selected = false;
					$this->link = null;
					return true;
				}
			}
			
			return false;
		}catch(Exception $e) {
			return launch_exception(new SQLException(3, $e));
		}
	}  
	
	public function ping() {
		try {
			if ($this->link)
				return @$this->query("select 1");
			
			return false;
		}catch(Exception $e) {
			return launch_exception(new SQLException(4, $e));
		}
	} 
	
	public function setCharset($charset = "utf8") {
		$this->init();
		
		try {
			return ini_set('mssql.charset', $charset) !== false;
		}catch(Exception $e) {
			return launch_exception(new SQLException(20, $e, $charset));
		}
	}
	
	public function selectDB($db_name) {
		$this->init();
		
		try {
			return $db_name && $this->setData("use $db_name");
		}catch(Exception $e) {
			return launch_exception(new SQLException(2, $e, array($db_name)));
		}
	}
	 
	//returns an int with the error number. zero means no error occurred. 
	//-1 for the db driver not initialized yet
	public function errno() {
		try {
			if ($this->link) {
				switch ($this->default_php_extension_type) {
					case "sqlsrv": 
						$errors = sqlsrv_errors();
						
						if ($errors)
							foreach($errors as $error)
								return isset($error["code"]) ? $error["code"] : null;
							
						break;
					case "pdo": return $this->link->errorCode();
					case "odbc": return odbc_error($this->link);
				}
			}
			
			return -1;
		}catch(Exception $e) {
			return launch_exception(new SQLException(4, $e));
		}
	} 
	
	//returns a string with the error. An empty string if no error occurred.
	//if not init yet return error: the db driver was not initialized yet
	public function error() {
		try {
			if ($this->link) {
				switch ($this->default_php_extension_type) {
					case "sqlsrv": 
						$error = sqlsrv_errors();
						$msg = "";
						
						if ($error) 
							$msg = "SQLSTATE error code: " . (isset($error["SQLSTATE"]) ? $error["SQLSTATE"] : "") . "\nDriver-specific error code: " . (isset($error["code"]) ? $error["code"] : "") . "\nDriver-specific error message: " . (isset($error["message"]) ? $error["message"] : "");
						
						return $msg;
					case "pdo": 
						$error = $this->link->errorInfo();
						$msg = "";
						
						if ($error)
							$msg = "SQLSTATE error code: " . (isset($error[0]) ? $error[0] : "") . "\nDriver-specific error code: " . (isset($error[1]) ? $error[1] : "") . "\nDriver-specific error message: " . (isset($error[2]) ? $error[2] : "");
						
						return $msg;
					case "odbc": return odbc_errormsg($this->link);
				}
			}
			
			return "This db driver was not initialized yet! Please call the connect method first!"; 
		}catch(Exception $e) {
			return launch_exception(new SQLException(5, $e));
		}
	} 
	
	public function execute(&$sql, $options = false) {
		$this->init();
		
		try {
			$sql = self::replaceSQLEnclosingDelimiter(trim($sql), "`", self::getEnclosingDelimiters()); //replace the mysql enclosing delimiter: ` with the postgres enclosing delimiter ".
			
			//bc mssql server doesn't allow manual inserts on auto-increment pks by default, we must execute this sql first
			if ($options && !empty($options["hard_coded_ai_pk"]) && preg_match("/^insert\s+into\s+/i", trim($sql))) {
				$data = SQLQueryHandler::parse($sql);
				
				if (!empty($data["table"])) {
					$table = strpos($data["table"], "[") !== false ? $data["table"] : "[" . $data["table"] . "]"; //is not yet escaped, adds [...]
					$sql = "SET IDENTITY_INSERT $table ON; " . $sql . "; SET IDENTITY_INSERT $table OFF;"; //must set on and then off, otherwise we cannot set IDENTITY_INSERT to another table.
				}
			}
			
			//error_log("mssql execute [".$this->options["db_name"] . "]:\n". $sql . "\n\n", 3, "/var/www/html/livingroop/default/tmp/test.log");
			
			/*
			 * The CREATE VIEW/FUNCTION/PROCEDURE/TRIGGER/EVENT must be first statement in a batch! It's not so much that it must be the first statement in the batch, but rather that it must be the only statement in the batch. For the same reason CREATE PROCEDURE, CREATE FUNCTION, etc. all have to be in their own batch ... they need to be compiled independently of other code. One reason is to ensure that anything in the batch created before the object actually exists when it is created, and anything that refers to the object afterward has something to point to. We don't want some code that don't belong to the procedure, be included in the definition of the stored procedure.
			 * So we ned to execute the CREATE VIEW/FUNCTION/PROCEDURE/TRIGGER/EVENT separately. In order to do this the keyword 'GO' is used to split the SQL in batches. This keyword is not recognized by the SQL Server, but is from the client softwares. So we emulate the same behaviour here.
			 */
			preg_match_all("/(\s|;|)(GO\s*;?|(CREATE\s(VIEW|FUNCTION|PROCEDURE|TRIGGER|EVENT)))(\s|$)/i", $sql, $matches, PREG_OFFSET_CAPTURE);
			$batches = array();
			
			if ($matches && $matches[2]) {
				$start_pos = 0;
				
				foreach ($matches[2] as $match) {
					$end_pos = $match[1];
					$is_go_statement = strtoupper(substr(trim($match[0]), 0, 2)) == "GO";
					$query = substr($sql, $start_pos, $end_pos - $start_pos);
					
					if (trim($query))
						$batches[] = $query;
					
					$start_pos = $end_pos;
					
					if ($is_go_statement)
						$start_pos += strlen($match[0]);
				}
			
				$query = substr($sql, $start_pos);
				if (trim($query))
					$batches[] = $query;
			}
			else
				$batches[] = $sql;
			//print_r($batches);echo "batches count:".count($batches)."\n";die();
			
			//execute sql
			$status = true;
			
			foreach ($batches as $batch) {
				$result = null;
				
				switch ($this->default_php_extension_type) {
					case "sqlsrv": 
						$result = @sqlsrv_query($this->link, $batch); //@ is very important bc if the connection is stale, this will give a php warning and launch an exception. The @ char avoids the warning to be shown. This is ok, bc we always catch the exception and the db error.
						break;
					case "pdo": 
						$result = $this->link->exec($batch);
						break;
					case "odbc": 
						$result = odbc_exec($this->link, $batch);
						break;
				}
				
				if ($result === false) //result could be an int(0)
					$status = false;
				else if ($result && $this->isResultValid($result)) //free result just in case
					$this->freeResult($result);
			}
			
			return $status;
		} catch(Exception $e) {
			return launch_exception(new SQLException(6, $e, array($sql)));
		}
	}
	
	public function query(&$sql, $options = false) {
		$this->init();
		
		try {
			if (is_array($options) && preg_match("/^select\s+/i", trim($sql))) { //$sql can be a procedure call
				if (substr($sql, -1) == ";") 
					$sql = substr($sql, 0, -1);
				
				if (!empty($options["sort"])) {
					$sort = self::addSortOptionsToSQL($options["sort"]);
					
					if ($sort) {
						if (stripos($sql, " limit ") !== false)
							$sql = "SELECT * FROM (" . $sql . ") AS QUERY_WITH_SORTING ORDER BY " . $sort;
						else 
							$sql .= " ORDER BY " . $sort;
					}
				}
				
				//parse sql and check if ends in "limit N,N", and if so replace it by the correct sql offsets
				if (stripos($sql, "limit") && preg_match("/\s+limit\s*([0-9]+)(|\s*,\s*([0-9]+))\s*$/i", $sql, $match)) {
					if (empty($options["start"]))
						$options["start"] = $match[1];
					
					if (empty($options["limit"]))
						$options["limit"] = $match[3];
					
					$sql = preg_replace("/\s+limit\s*([0-9]+)(|\s*,\s*([0-9]+))\s*$/i", "", $sql);
				}
				
				if(isset($options["limit"]) && is_numeric($options["limit"])) {
					if (stripos($sql, " order by ") === false)
						$sql .= " ORDER BY (SELECT NULL)"; //OFFSET must have an "ORDER BY" statement otherwise it will give a sql error.
					
					$sql .= " OFFSET " . (!empty($options["start"]) ? $options["start"] : 0) . " ROWS FETCH NEXT " . $options["limit"] . " ROWS ONLY;";
				}
			}
			
			$sql = self::replaceSQLEnclosingDelimiter(trim($sql), "`", self::getEnclosingDelimiters()); //replace the mysql enclosing delimiter: ` with the postgres enclosing delimiter ".
			
			//echo "$sql<br>";
			//error_log($this->options["db_name"] . ":\n". $sql . "\n\n", 3, "/tmp/sql_log.log");
			//error_log("mssql query [".$this->options["db_name"] . "]:\n". $sql . "\n\n", 3, "/var/www/html/livingroop/default/tmp/test.log");
			
			switch ($this->default_php_extension_type) {
				case "sqlsrv": return @sqlsrv_query($this->link, $sql); //@ is very important bc if the connection is stale, this will give a php warning and launch an exception. The @ char avoids the warning to be shown. This is ok, bc we always catch the exception and the db error.
				case "pdo": return $this->link->query($sql);
				case "odbc": return odbc_exec($this->link, $sql);
			}
		} catch(Exception $e) {
			return launch_exception(new SQLException(6, $e, array($sql)));
		}
	} 
	
	public function freeResult($result) {
		try {
			switch ($this->default_php_extension_type) {
				case "sqlsrv": return sqlsrv_free_stmt($result);
				case "pdo": return $result->closeCursor();
				case "odbc": return odbc_free_result($result);
			}
		}catch(Exception $e) {
			return launch_exception(new SQLException(7, $e, array($result)));
		}
	}
	
	public function numRows($result) {
		try {
			switch ($this->default_php_extension_type) {
				case "sqlsrv": return sqlsrv_num_rows($result);
				case "pdo": return $result->rowCount();
				case "odbc": return odbc_num_rows($result);
			}
		}catch(Exception $e) {
			return launch_exception(new SQLException(13, $e, array($result)));
		}
	} 
	
	public function numFields($result) {
		try {
			switch ($this->default_php_extension_type) {
				case "sqlsrv": return sqlsrv_num_fields($result);
				case "pdo": return $result->columnCount();
				case "odbc": return odbc_num_fields($result);
			} 
		}catch(Exception $e) {
			return launch_exception(new SQLException(14, $e, array($result)));
		}
	}
	
	public function fetchArray($result, $array_type = false) {
		try {
			switch ($this->default_php_extension_type) {
				case "sqlsrv": 
					if ($array_type == DB::FETCH_OBJECT)
						return sqlsrv_fetch_object($result);
					
					$array_type = $this->convertFetchTypeToExtensionType($array_type ? $array_type : DB::FETCH_BOTH);
					return sqlsrv_fetch_array($result, $array_type);
				case "pdo": 
					if ($array_type == DB::FETCH_OBJECT)
						return $result->fetch(PDO::FETCH_OBJ);
					
					$array_type = $this->convertFetchTypeToExtensionType($array_type ? $array_type : DB::FETCH_BOTH);
					return $result->fetch($array_type);
				case "odbc": 
					if ($array_type == DB::FETCH_OBJECT)
						return odbc_fetch_object($result);
					
					$is_assoc = false;
					$records = false;
					
					if ($array_type == DB::FETCH_ASSOC || $array_type == DB::FETCH_BOTH || !$array_type) {
						$records = odbc_fetch_array($result);
						$is_assoc = true;
					}
					
					if ($array_type == DB::FETCH_NUM || $array_type == DB::FETCH_BOTH || !$array_type)
						$records = is_array($records) ? array_merge($records, array_values($records)) : ($is_assoc ? $records : odbc_fetch_row($result));
					
					return $records;
			}
		}
		catch(Exception $e) {
			return launch_exception(new SQLException(8, $e, array($result, $array_type)));
		}
		catch(Error $e) {
			return launch_exception(new SQLException(8, $e, array($result, $array_type)));
		}
	} 
	
	public function fetchField($result, $offset) {
		try {
			$field = null;
			
			try {
				switch ($this->default_php_extension_type) {
					case "sqlsrv": 
						$fields = sqlsrv_field_metadata($result);
						$field = $fields && isset($fields[$offset]) ? $fields[$offset] : null;
						
						if ($field)
							$this->prepareMssqlsrvField($field);
						break;
					case "pdo": 
						$field = $result->getColumnMeta($offset);
						
						if ($field)
							self::preparePDOField($field);
						break;
					case "odbc": 
						$field = new stdClass();
						$field->name = odbc_field_name($result, $offset);
						$field->type = strtolower( odbc_field_type($result, $offset) );
						$field->length = odbc_field_len($result, $offset); //optional
						$field->precision = odbc_field_precision($result, $offset); //optional. same than odbc_field_len
						$field->scale = odbc_field_scale($result, $offset); //optional
						$field->not_null = null; //optional. There is no function to find if this field is null or not. TODO: try to use: odbc_gettypeinfo
						break;
				}
			}
			catch (PDOException $e) {
				//echo "Failed getColumnMeta: " . $e->getMessage();
			}
			
			if ($field) {
				$field->type = self::convertColumnTypeFromDB($field->type, $flags);
				
				if ($flags)
					foreach ($flags as $k => $v)
						$field->$k = $v;
			}
			
			//echo "<pre>";print_r($field);die();
			return $field;
		}
		catch(Exception $e) {
			return launch_exception(new SQLException(12, $e, array($result, $offset)));
		}
	}
	
	/*	https://www.php.net/manual/en/function.sqlsrv-field-metadata.php
		Array(
			[Name] => id
			[Type] => 3 //The numeric value for the SQL type.
			[Size] => 10 //The number of characters for fields of character type, the number of bytes for fields of binary type, or null for other types.
			[Precision] => 8 //The precision for types of variable precision, null for other types. The decimal integers.
			[Scale] => 2 //The scale for types of variable scale, null for other types. The decimal digits.
			[Nullable] => 0 //An enumeration indicating whether the column is nullable, not nullable, or if it is not known.
		)
	*/
	private function prepareMssqlsrvField(&$field) {
		//echo "<pre>";print_r($field);
		if (is_array($field))
			$field = (object) $field; //cast to object
		
		//make all attributes lower case
		foreach ($field as $k => $v) {
			unset($field->$k);
			$k = strtolower($k);
			$field->$k = $v;
		}
		
		//prepare attributes
		$field->length = $field->size; //optional
		$field->not_null = empty($field->nullable); //optional
		unset($field->size);
		unset($field->nullable);
		
		$field_types = isset(self::$mssqlserver_data_types[$field->type]) ? self::$mssqlserver_data_types[$field->type] : null;
		$field->type = isset($field_types[0]) ? $field_types[0] : null;
		
		//echo "<pre>";print_r($field);die();
	}
	
	public function isResultValid($result) {
		switch ($this->default_php_extension_type) {
			case "pdo": return is_a($result, "PDOStatement");
		}
		
		return is_resource($result) || is_object($result);
	}
	
	public function listTables($db_name = false, $options = false) {
		$tables = array();
		
		$db_name = $db_name ? $db_name : (!$this->isDBSelected() && !empty($this->options["db_name"]) ? $this->options["db_name"] : null);
		
		$options = $options ? $options : array();
		$options["return_type"] = "result";
		$sql = self::getTablesStatement($db_name, $this->options);
		$result = $this->getData($sql, $options);
		
		if($result)
			foreach ($result as $table)
			    	$tables[] = array(
				    	"name" => (!empty($table["table_schema"]) && empty($this->options["schema"]) ? $table["table_schema"] . "." : "") . (isset($table["table_name"]) ? $table["table_name"] : null), //Only add schema if is not defined in options
				    	"table_name" => isset($table["table_name"]) ? $table["table_name"] : null,
				    	"schema" => isset($table["table_schema"]) ? $table["table_schema"] : null,
				    	"type" => isset($table["table_type"]) ? strtolower($table["table_type"]) : null
			    	);
		
		return $tables;
	}
	
	public function listTableFields($table, $options = false) {
		$fields = array();
		
		$db_name = !$this->isDBSelected() && !empty($this->options["db_name"]) ? $this->options["db_name"] : null;
		$sql = self::getTableFieldsStatement($table, $db_name, $this->options);
		//error_log($sql . "\n\n", 3, $GLOBALS["log_file_path"] ? $GLOBALS["log_file_path"] : "/var/www/html/livingroop/default/tmp/phpframework.log");
		
		if (empty($this->options["db_name"]))
			return launch_exception(new SQLException(19, null, $sql));
		
		$options = $options ? $options : array();
		$options["return_type"] = "result";
		$result = $this->getData($sql, $options);
		
		if($result)
			foreach ($result as $field) 
				if (isset($field["column_name"])) {
					$cd = isset($field["column_default"]) ? $field["column_default"] : null;
					
					$flags = null;
					$length = !empty($field["character_maximum_length"]) ? $field["character_maximum_length"] : (isset($field["numeric_precision"]) ? $field["numeric_precision"] : null);
					
					if (is_numeric($length) && isset($field["numeric_scale"]) && is_numeric($field["numeric_scale"]))
						$length += $field["numeric_scale"];
					
					$cd = $cd == "''" ? "" : $cd;
					
					$props = array(
						"name" => $field["column_name"],
						"type" => isset($field["data_type"]) ? self::convertColumnTypeFromDB($field["data_type"], $flags) : null,
						"length" => $length,
						"null" => isset($field["is_nullable"]) && strtolower($field["is_nullable"]) == "no" ? false : true,
						"primary_key" => !empty($field["is_primary_key"]) ? true : false,
						"unique" => !empty($field["is_primary_key"]) || !empty($field["is_unique_key"]) ? true : false,
						"unsigned" => false, //mssql server doesnt have unsigned types
						"default" => $cd,
						"charset" => isset($field["character_set_name"]) ? $field["character_set_name"] : null,
						"collation" => isset($field["collation_name"]) ? $field["collation_name"] : null,
						"extra" => "", //no extra. Extras will be added bellow via php code
						"comment" => isset($field["column_comment"]) ? $field["column_comment"] : null,
					);
					
					//set auto_increment and flags
					$identity_str = "IDENTITY (" . (isset($field["seed_value"]) && is_numeric($field["seed_value"]) ? $field["seed_value"] : 1) . ", " . (isset($field["increment_value"]) && is_numeric($field["increment_value"]) ? $field["increment_value"] : 1) . ")";
					$auto_increment = isset($field["data_type"]) ? in_array($field["data_type"], array("serial", "smallserial", "bigserial")) : false;
					
					if (!empty($field["is_identity"]))
						$props["extra"] .= $identity_str;
					
					if ($flags)
						foreach ($flags as $k => $v) {
							if ($v && ($k == "auto_increment" || $k == "identity"))
								$auto_increment = true;
							else
								$props[$k] = $v;
						}
					
					if ($auto_increment && stripos($props["extra"], "IDENTITY") === false)
						$props["extra"] .= ($props["extra"] ? " " : "") . $identity_str;
					
					$props["auto_increment"] = $auto_increment || stripos($props["extra"], "IDENTITY") !== false;
					
					//preparing default value:
					//For some reason, mssql server adds parenthesis between the default values and double parenthesis if default value is numeric.
					// if default exists and is inside of (...) or ((...))
					if ($props["default"] && preg_match("/^\(+/", $props["default"]) && preg_match("/\)+$/", $props["default"])) {
						$props["default"] = preg_replace("/^\(+/", "", $props["default"]);
						$props["default"] = preg_replace("/\)+$/", "", $props["default"]);
						
						//non numeric values are inside of '
						if (!is_numeric($props["default"]) && preg_match("/^'+/", $props["default"]) && preg_match("/'+$/", $props["default"])) {
							$props["default"] = preg_replace("/^'+/", "", $props["default"]);
							$props["default"] = preg_replace("/'+$/", "", $props["default"]);
						}
					}
					
					$fields[ $field["column_name"] ] = $props;
				}
		
		return $fields;
	}
	
	public function listDBCharsets() {
		return static::getDBCharsets();
	}
	
	//mssql doesn't support charset for table
	public function listTableCharsets() {
		return null;
	}
	
	//mssql doesn't support charset for column
	public function listColumnCharsets() {
		return null;
	}
	
	public function listDBCollations() {
		$rows = array();
		
		$sql = static::getShowDBCollationsStatement($this->options);
		
		if ($sql) {
			$options = array("return_type" => "result");
			$result = $this->getData($sql, $options);
			
			if($result)
				foreach ($result as $field) {
					$id = $field["name"];
					$rows[$id] = !empty($field["description"]) ? $field["description"] : ucwords(str_replace("_", " ", $id));
				}
		}
		
		if (!$rows)
			$rows = static::getDBCollations();
		
		return $rows;
	}
	
	public function listTableCollations() {
		return $this->listDBCollations();
	}
	
	public function listColumnCollations() {
		return $this->listDBCollations();
	}
	
	//mssql doesn't support storage engines
	public function listStorageEngines() {
		return null;
	}
	
	public function getInsertedId($options = false) {
    		if ($this->init())
    			switch ($this->default_php_extension_type) {
				case "pdo": 
					try {
						return $this->link->lastInsertId(); //Note that the PDO driver may not support the lastInsertId function and in this case it will trigger an IM001 SQLSTATE. More info in https://www.php.net/manual/en/pdo.lastinsertid.php
					}
					catch (Exception $e) {
						//Do nothing and continue to code bellow
					}
				case "sqlsrv": 
				case "odbc": 
					$options = $options ? $options : array();
					$options["return_type"] = "result";
					$result = $this->getData("SELECT @@IDENTITY AS id", $options);
					
					if ($result)
						return isset($result[0]["id"]) ? $result[0]["id"] : null;
			}
		
		return 0;
	}
	
	//SQLSVR: SQLSRV_FETCH_ASSOC, SQLSRV_FETCH_NUMERIC, SQLSRV_FETCH_BOTH
	private function convertFetchTypeToExtensionType($fetch_type) {
		if ($this->default_php_extension_type == "sqlsrv")
			switch ($fetch_type) {
				case DB::FETCH_ASSOC: return SQLSRV_FETCH_ASSOC;
				case DB::FETCH_NUM: return SQLSRV_FETCH_NUMERIC;
				case DB::FETCH_BOTH: return SQLSRV_FETCH_BOTH;
			}
		
		return self::convertFetchTypeToPDOAndODBCExtensions($this->default_php_extension_type, $fetch_type);
	}
}
?>
