<?php
//psql -h localhost -U jplpinto -W test

//To install odbc drivers for MSSQL in ubuntu please follow the tutorial: 
//http://digitalitility.com/tutori-alitility/postgresql/odbc-setup-on-ubuntu-for-postgresql/
//https://askubuntu.com/questions/1165430/how-to-install-and-configure-the-latest-odbc-driivers-for-both-mysql-postgresq
//https://ubuntu.pkgs.org/18.04/ubuntu-universe-amd64/odbc-postgresql_10.01.0000-1_amd64.deb.html

include_once get_lib("org.phpframework.db.DB");
include_once get_lib("org.phpframework.db.statement.PostgresDBStatement");
include_once get_lib("org.phpframework.db.property.PostgresDBProperty");
include_once get_lib("org.phpframework.db.static.PostgresDBStatic");

class PostgresDB extends DB {
	use PostgresDBStatement;
	use PostgresDBProperty;
	use PostgresDBStatic;
	
	const DEFAULT_DB_NAME = 'postgres'; //by default if no dbname is passed, postgres engine takes username as the default database, so we must set it to the default database.
	
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
	 * pg_connect("host=$server;port=;dbname=$database;user=$user;$password=$password;");
	 * 
	 * $conn=new PDO("odbc:Driver=$driver;Server=$server;Database=$database;charset=$encoding;", $user, $password); //with odbc Driver
	 * $conn=new PDO('odbc:PGSQL_PHP', $user, $password); //with odbc data_source in /etc/odbc.ini
	 * $conn=new PDO("pgsql:host=$server;dbname=$database;", $user, $password); //with pgsql pdo extension. Note that "Server=" and "Database=" don't work here. It must be "host=" and "dbname=".
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
			$dsn .= $data_source . (substr($data_source, -1) == ";" ? "" : ";"); //odbc:PGSQL_PHP
		else if (empty($options["host"]) && self::$default_odbc_data_source) //then if no $data_source and no $options["host"], sets self::$default_odbc_data_source
			$dsn .= self::$default_odbc_data_source . (substr(self::$default_odbc_data_source, -1) == ";" ? "" : ";"); //odbc:PGSQL_PHP
		else if ($driver) //Then if $data_source and self::$default_odbc_data_source do NOT exists, sets $driver. Host should exists, otherwise it will give a connection error, on purpose!
			$dsn .= 'Driver={' . $driver . '};';
		else if (self::$default_odbc_driver) //Then if $data_source, self::$default_odbc_data_source and $driver do NOT exists, sets self::$default_odbc_driver
			$dsn .= 'Driver={' . self::$default_odbc_driver . '};';
		else if ($pdo_exists) //Then if none above exists, sets default pdo extension.
			$dsn = 'pgsql:';
		
		if ($pdo_exists)
			$with_host = $with_dbname = true;
		
		if (!empty($options["host"]))
			$dsn .= ($with_host ? 'host' : 'Server') . "=" . $options["host"] . (!empty($options["port"]) ? ':' . $options["port"] : '') . ';';
		
		if (!empty($options["db_name"]))
			$dsn .= ($with_dbname ? 'dbname' : 'Database') . '=' . $options["db_name"] . ';';
		
		if (!empty($options["encoding"]))
			$dsn .= 'client_encoding=' . $options["encoding"] . ';';
		
		if (!empty($options["extra_dsn"]))
			$dsn .= $options["extra_dsn"];
		
		return $dsn;
	}
	
	public function getVersion() {
		if ($this->link)
			switch ($this->default_php_extension_type) {
				case "pg": 
					$info = pg_version($this->link);
					return $info && isset($info["client"]) ? $info["client"] : null;
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
				case "pg":
					//conn_string is not a DSN! This means we cannot call the self::getDSN method!
					$conn_string = "host=" . (isset($this->options["host"]) ? $this->options["host"] : "") . " ". (isset($this->options["port"]) && is_numeric($this->options["port"]) ? " port=" . $this->options["port"] : "") . " dbname=" . (isset($this->options["db_name"]) ? $this->options["db_name"] : "") . (!empty($this->options["username"]) ? " user=" . $this->options["username"] . " " . (!empty($this->options["password"]) ? "password=" . $this->options["password"] : "") : "");
					
					if(!empty($this->options["persistent"]))
						$this->link = !empty($this->options["new_link"]) ? pg_pconnect($conn_string, PGSQL_CONNECT_FORCE_NEW) : pg_pconnect($conn_string);
					else
						$this->link = !empty($this->options["new_link"]) ? pg_connect($conn_string, PGSQL_CONNECT_FORCE_NEW) : pg_connect($conn_string);
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
					
			if ($this->link) {
				if (!empty($this->options["encoding"]) && empty($this->setCharset($this->options["encoding"])))
					$this->close();
				else if (!empty($this->options["db_name"]))
					$this->db_selected = true;
			}
			
			if (!$this->link || !$this->db_selected) {
				$e = null;
				$error = $this->default_php_extension_type == "pg" ? pg_last_error() : ($this->default_php_extension_type == "odbc" ? odbc_errormsg() : null);
				
				if ($error)
					$e = new Exception("Failed to connect to PGSQL: " . $error);
				
				launch_exception(new SQLException(1, $e, $this->options));
			}
		}
		catch(Exception $e) {
			launch_exception(new SQLException(1, $e, $this->options));
		}
		
		return $this->db_selected;
	}
	
	public function connectWithoutDB() {
		try{
			//close previous connection if exists
			if ($this->link)
				$this->close();
			
			$this->default_php_extension_type = !empty($this->options["extension"]) ? $this->options["extension"] : $this->default_php_extension_type;
			
			switch ($this->default_php_extension_type) {
				case "pg":
					$conn_string = "host=" . (isset($this->options["host"]) ? $this->options["host"] : "") . " ".(isset($this->options["port"]) && is_numeric($this->options["port"]) ? " port=" . $this->options["port"] : "") . " dbname=" . self::DEFAULT_DB_NAME . (!empty($this->options["username"]) ? " user=" . $this->options["username"] . " ". (!empty($this->options["password"]) ? "password=" . $this->options["password"] : "") : ""); //by default if no dbname is passed, postgres engine takes username as the default database, so we must set it to the default database.
					
					if(!empty($this->options["persistent"]))
						$this->link = !empty($this->options["new_link"]) ? pg_pconnect($conn_string, PGSQL_CONNECT_FORCE_NEW) : pg_pconnect($conn_string);
					else
						$this->link = !empty($this->options["new_link"]) ? pg_connect($conn_string, PGSQL_CONNECT_FORCE_NEW) : pg_connect($conn_string);
					break;
				
				case "pdo":
					$pdo_settings = !empty($this->options["pdo_settings"]) ? $this->options["pdo_settings"] : array();
					
					if (!array_key_exists(PDO::ATTR_ERRMODE, $pdo_settings))
						$pdo_settings[PDO::ATTR_ERRMODE] = PDO::ERRMODE_EXCEPTION;
			   		
					if(!empty($this->options["persistent"]) && empty($this->options["new_link"]))
						$pdo_settings[PDO::ATTR_PERSISTENT] = true;
					
					$options = $this->options;
					$options["db_name"] = self::DEFAULT_DB_NAME; //by default if no dbname is passed, postgres engine takes username as the default database, so we must set it to the default database.
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
					$options["db_name"] = self::DEFAULT_DB_NAME; //by default if no dbname is passed, postgres engine takes username as the default database, so we must set it to the default database.
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
			
			if ($this->link) {
				if (!empty($this->options["encoding"]) && !$this->setCharset($this->options["encoding"]))
					$this->close();
			}
			
			if (!$this->link) {
				$e = null;
				$error = $this->default_php_extension_type == "pg" ? pg_last_error() : ($this->default_php_extension_type == "odbc" ? odbc_errormsg() : null);
				
				if ($error)
					$e = new Exception("Failed to connect to PGSQL: " . $error);
				
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
						case "pg": $closed = pg_close($this->link); break;
						case "odbc": odbc_close($this->link); $closed = true;break;
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
			if ($this->link) {
				switch ($this->default_php_extension_type) {
					case "pg": return @pg_ping($this->link);
					case "pdo": return @$this->query("select 1");
					case "odbc": return @$this->query("select 1");
				}
			}
			
			return false;
		}catch(Exception $e) {
			return launch_exception(new SQLException(4, $e));
		}
	} 
	
	public function setCharset($charset = "unicode") {
		$this->init();
		
		try {
			switch ($this->default_php_extension_type) {
				case "pg": return pg_set_client_encoding($this->link, strtoupper($charset)) != -1;
				case "pdo": return $this->link->query("SET NAMES '$charset'"); //or: SET CLIENT_ENCODING TO 'value';
				case "odbc": return odbc_exec($this->link, "SET NAMES '$charset'"); //or: SET CLIENT_ENCODING TO 'value';
			}
			return false;
		}catch(Exception $e) {
			return launch_exception(new SQLException(20, $e, $charset));
		}
	}
	
	public function selectDB($db_name) {
		$this->init();
		
		try {
			if ($db_name) {
				//This doesn't work bc we must use the pg_connect function in order to connect to a DB
				//return $this->setData("\\c " . $db_name);// "\c $db_name" or "\connect $db_name"
				
				$db_name_bkp = isset($this->options["db_name"]) ? $this->options["db_name"] : null;
				$this->options["db_name"] = $db_name;
				$status = $this->connect(); //connect will close this connection and create a new one.
				$this->options["db_name"] = $db_name_bkp;
				
				return $status;
			}
		}catch(Exception $e) {
			return launch_exception(new SQLException(2, $e, array($db_name)));
		}
	}
	 
	//returns an int with the error number. zero means no error occurred. 
	public function errno() {
		try {
			if ($this->link) {
				switch ($this->default_php_extension_type) {
					case "pg": return 0;
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
	public function error() {
		try {
			if ($this->link) {
				switch ($this->default_php_extension_type) {
					case "pg": return pg_last_error($this->link);
					case "pdo": return $this->link->errorInfo();
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
			//error_log("pg execute sql:$sql\noptions:".print_r($options, 1)."\n\n", 3, "/var/www/html/livingroop/default/tmp/test.log");
			
			switch ($this->default_php_extension_type) {
				case "pg": return @pg_query($this->link, $sql); //@ is very important bc if the connection is stale, this will give a php warning and launch an exception. The @ char avoids the warning to be shown. This is ok, bc we always catch the exception and the db error.
				case "pdo": return $this->link->exec($sql);
				case "odbc": return odbc_exec($this->link, $sql);
			}
		} catch(Exception $e) {
			return launch_exception(new SQLException(6, $e, array($sql)));
		}
	}
	
	public function query(&$sql, $options = false) {
		$this->init();
		
		try {
			if (is_array($options) && preg_match("/^select\s+/i", trim($sql))) {//$sql can be a procedure call
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
					if (stripos($sql, " order by ") !== false)
						$sql = "SELECT * FROM (" . $sql . ") AS QUERY_WITH_PAGINATION LIMIT " . $options["limit"] . " OFFSET " . ($options["start"] ? $options["start"] : 0);
					else
						$sql .= " LIMIT " . $options["limit"] . " OFFSET " . ($options["start"] ? $options["start"] : 0);
				}
			}
			
			$sql = self::replaceSQLEnclosingDelimiter(trim($sql), "`", self::getEnclosingDelimiters()); //replace the mysql enclosing delimiter: ` with the postgres enclosing delimiter ".
			//error_log("pg query sql:$sql\noptions:".print_r($options, 1)."\n\n", 3, "/var/www/html/livingroop/default/tmp/test.log");
			
			switch ($this->default_php_extension_type) {
				case "pg": return @pg_query($this->link, $sql); //@ is very important bc if the connection is stale, this will give a php warning and launch an exception. The @ char avoids the warning to be shown. This is ok, bc we always catch the exception and the db error.
				case "pdo": return $this->link->query($sql);
				case "odbc": return odbc_exec($this->link, $sql);
			}
		}catch(Exception $e) {
			return launch_exception(new SQLException(6, $e, array($sql)));
		}
	} 
	 
	public function freeResult($result) {
		try {
			switch ($this->default_php_extension_type) {
				case "pg": return pg_free_result($result);
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
				case "pg": return pg_num_rows($result);
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
				case "pg": return pg_num_fields($result);
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
				case "pg": 
					if ($array_type == DB::FETCH_OBJECT)
						return pg_fetch_object($result);
					
					$array_type = $this->convertFetchTypeToExtensionType($array_type ? $array_type : DB::FETCH_BOTH);
					return pg_fetch_array($result, null, $array_type);
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
					case "pg": 
						$field = new stdClass();
						$field->name = pg_field_name($result, $offset);
						$field->type = pg_field_type($result, $offset);
						$field->length = pg_field_prtlen($result, $field->name); //optional. I think this is for textual attributes
						$field->max_length = pg_field_size($result, $offset); //optional. I think this is for numeric attributes
						$field->not_null = empty(pg_field_is_null($result, $offset)); //optional
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
		}catch(Exception $e) {
			return launch_exception(new SQLException(12, $e, array($result, $offset)));
		}
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
				    	"schema" => isset($table["table_schema"]) ? $table["table_schema"] : null
			    	);
		
		return $tables;
	}
	 
	/*public function listTableFields($table) {
		$fields = array();
				
		$sql = "SELECT
				a.attnum,
				a.attname AS field,
				t.typname AS type,
				a.attlen AS length,
				a.atttypmod AS lengthvar,
				a.attnotnull AS notnull
			FROM
				pg_class c,
				pg_attribute a,
				pg_type t
			WHERE
				c.relname = '{$table}'
				and a.attnum > 0
				and a.attrelid = c.oid
				and a.atttypid = t.oid
				order by a.attnum";
		
		$result = $this->query($sql);
		
		if($result) {
			while($field = $this->fetchAssoc($result)) 
				if (isset($field["field"])) {
					$fields[ $field["field"] ] = array(
						"type" => isset($field["type"]) ? $field["type"] : null,
						"length" => isset($field["length"]) && $field["length"] > 0 && is_numeric($field["length"]) ? $field["length"] : false,
						"null" => isset($field["notnull"]) && $field["notnull"] == "t" ? false : true,
						"primary_key" => ""
					);
				}
			$this->freeResult($result);
			
			$sql = "select column_name as field from information_schema.constraint_column_usage where table_name = '{$table}' and table_catalog = '" . (isset($this->options["db_name"]) ? $this->options["db_name"] : null) . "'";
			
			if (empty($this->options["db_name"]))
				return launch_exception(new SQLException(19, null, $sql));
			
			$result = $this->query($sql);
			
			if($result) {
				while($field = $this->fetchAssoc($result)) 
					if (isset($field["field"]))
						$fields[ $field["field"] ]["primary_key"] = true;
				
				$this->freeResult($result);
			}
		}
		
		return $fields;
	}*/
	public function listTableFields($table, $options = false) {
		$fields = array();
		
		$db_name = !$this->isDBSelected() && !empty($this->options["db_name"]) ? $this->options["db_name"] : null;
		$sql = self::getTableFieldsStatement($table, $db_name, $this->options);
		
		$table_props = self::parseTableName($table, $options);
		$table_name = isset($table_props["name"]) ? $table_props["name"] : null;
		
		if (empty($this->options["db_name"]))
			return launch_exception(new SQLException(19, null, $sql));
		
		$options = $options ? $options : array();
		$options["return_type"] = "result";
		$result = $this->getData($sql, $options);
		
		if($result)
			foreach ($result as $field) 
				if (isset($field["column_name"])) {
					$cn = isset($field["column_name"]) ? $field["column_name"] : null;
					$ccv = isset($field["check_constraint_value"]) ? $field["check_constraint_value"] : null;
					$dt = isset($field["data_type"]) ? $field["data_type"] : null;
					$cd = isset($field["column_default"]) ? $field["column_default"] : null;
					
					$min_value = self::getCheckConstraintMinValue($cn, $ccv);
					$is_unsigned = isset($min_value) && is_numeric($min_value) && $min_value >= 0;
					$flags = null;
					
					$length = !empty($field["character_maximum_length"]) ? $field["character_maximum_length"] : (isset($field["numeric_precision"]) ? $field["numeric_precision"] : null);
					
					$field["is_primary"] = isset($field["is_primary"]) ? explode(",", str_replace(array("{", "}"), "", strtolower(trim($field["is_primary"])))) : array();
					$field["is_unique"] = isset($field["is_unique"]) ? explode(",", str_replace(array("{", "}"), "", strtolower(trim($field["is_unique"])))) : array();
					$primary_key = in_array("t", $field["is_primary"]) || in_array(true, $field["is_primary"], true);
					
					$cd = $cd == "''" ? "" : $cd;
					
					$props = array(
						"name" => $field["column_name"],
						"type" => isset($field["data_type"]) ? self::convertColumnTypeFromDB($field["data_type"], $flags) : null,
						"length" => $length,
						"null" => isset($field["is_nullable"]) && strtolower(trim($field["is_nullable"])) == "no" ? false : true,
						"primary_key" => $primary_key,
						"unique" => $primary_key || in_array("t", $field["is_unique"]) || in_array(true, $field["is_unique"], true) || !empty($field["unique_constraint_name"]) ? true : false,
						"unsigned" => $is_unsigned,
						"default" => isset($cd) ? str_replace(array("::character varying", "'"), "", $cd) : null,
						"charset" => isset($field["character_set_name"]) ? $field["character_set_name"] : null,
						"collation" => isset($field["collation_name"]) ? $field["collation_name"] : null,
						"extra" => isset($field["extra"]) ? $field["extra"] : null,
						"comment" => isset($field["column_comment"]) ? $field["column_comment"] : null,
					);
					
					if (!empty($field["check_constraint_value"]))
						$props["extra"] .= ($props["extra"] ? " " : "") . "CHECK " . $field["check_constraint_value"];
					
					//set auto_increment and flags
					$auto_increment_seq = $table_name . "_" . $field["column_name"] . "_seq";
					$auto_increment = in_array($dt, array("serial", "smallserial", "bigserial")) || stripos($cd, "nextval('$auto_increment_seq") !== false;
					
					if ($flags)
						foreach ($flags as $k => $v) {
							if ($v && $k == "auto_increment")
								$auto_increment = true;
							else
								$props[$k] = $v;
						}
					
					if ($auto_increment)
						$props["extra"] .= ($props["extra"] ? " " : "") . "auto_increment";
					
					$props["auto_increment"] = $auto_increment;
					
					$fields[ $field["column_name"] ] = $props;
				}
		
		return $fields;
	}
	
	//example of the column check_constraint_value: (price > (0)::numeric)
	private static function getCheckConstraintMinValue($column_name, $check_constraint_value) {
		$find = "($column_name > (";
		$start_pos = strpos($check_constraint_value, $find);
		
		if ($start_pos === false) {
			$find = "($column_name > ";
			$start_pos = strpos($check_constraint_value, $find);
		}
		
		if ($start_pos !== false) {
			$end_pos = strpos($check_constraint_value, ")", $start_pos + strlen($find));
			
			if ($end_pos !== false) {
				$start_pos = $start_pos + strlen($find);
				$min_field = substr($check_constraint_value, $start_pos, $end_pos - $start_pos);
				
				return (int) $min_field;
			}
		}
		
		return null;
	}
	
	public function listDBCharsets() {
		return static::getDBCharsets();
	}
	
	//postgres doesn't support charset for table
	public function listTableCharsets() {
		return null;
	}
	
	//postgres doesn't support charset for column
	public function listColumnCharsets() {
		return null;
	}
	
	public function listDBCollations() {
		return static::getDBCollations();
	}
	
	//postgres doesn't support collation for table
	public function listTableCollations() {
		return null;
	}
	
	public function listColumnCollations() {
		$rows = array();
		
		$sql = static::getShowColumnCollationsStatement($this->options);
		
		if ($sql) {
			$options = array("return_type" => "result");
			$result = $this->getData($sql, $options);
			
			if($result)
				foreach ($result as $field)  {
					$id = $field["collname"];
					$rows[$id] = ucwords(str_replace("_", " ", $id));
				}
		}
		
		if (!$rows)
			$rows = static::getColumnCollations();
		
		return $rows;
	}
	
	//postgres doesn't support storage engines
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
				case "pg": //DO NOT USE pg_last_oid bc it doesnt work!
				case "odbc":
					$options = $options ? $options : array();
					$options["return_type"] = "result";
					$result = $this->getData("SELECT lastval() AS id", $options);
					
					if ($result)
						return isset($result[0]["id"]) ? $result[0]["id"] : null;
			}
		
		return 0;
	}
	
	//PG: PGSQL_ASSOC, PGSQL_NUM, PGSQL_BOTH
	private function convertFetchTypeToExtensionType($fetch_type) {
		if ($this->default_php_extension_type == "pg")
			switch ($fetch_type) {
				case DB::FETCH_ASSOC: return PGSQL_ASSOC;
				case DB::FETCH_NUM: return PGSQL_NUM;
				case DB::FETCH_BOTH: return PGSQL_BOTH;
			}
		
		return self::convertFetchTypeToPDOAndODBCExtensions($this->default_php_extension_type, $fetch_type);
	}
}
?>
