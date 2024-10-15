<?php
//To install odbc drivers for MSSQL in ubuntu please follow the tutorial:
//https://www.howtoinstall.me/ubuntu/18-04/unixodbc/
//https://www.programmersought.com/article/7272665245/
//https://askubuntu.com/questions/1165430/how-to-install-and-configure-the-latest-odbc-driivers-for-both-mysql-postgresq

include_once get_lib("org.phpframework.db.DB");
include_once get_lib("org.phpframework.db.statement.MySqlDBStatement");
include_once get_lib("org.phpframework.db.property.MySqlDBProperty");
include_once get_lib("org.phpframework.db.static.MySqlDBStatic");

class MySqlDB extends DB {
	use MySqlDBStatement;
	use MySqlDBProperty;
	use MySqlDBStatic;
	
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
	 * mysqli_connect($server, $user, $password, $database, $port);
	 * 
	 * $conn=new PDO("odbc:Driver=$driver;Server=$server;Database=$database;charset=$encoding;", $user, $password); //with odbc Driver
	 * $conn=new PDO('odbc:MYSQL_PHP', $user, $password); //with odbc data_source in /etc/odbc.ini
	 * $conn=new PDO("mysql:Server=$server;Database=$database;charset=$encoding;", $user, $password); //with mysql pdo extension
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
			$dsn .= $data_source . (substr($data_source, -1) == ";" ? "" : ";"); //odbc:MYSQL_PHP
		else if (empty($options["host"]) && self::$default_odbc_data_source) //then if no $data_source and no $options["host"], sets self::$default_odbc_data_source
			$dsn .= self::$default_odbc_data_source . (substr(self::$default_odbc_data_source, -1) == ";" ? "" : ";"); //odbc:MYSQL_PHP
		else if ($driver) //Then if $data_source and self::$default_odbc_data_source do NOT exists, sets $driver. Host should exists, otherwise it will give a connection error, on purpose!
			$dsn .= 'Driver={' . $driver . '};';
		else if (self::$default_odbc_driver) //Then if $data_source, self::$default_odbc_data_source and $driver do NOT exists, sets self::$default_odbc_driver
			$dsn .= 'Driver={' . self::$default_odbc_driver . '};';
		else if ($pdo_exists) { //Then if none above exists, sets default pdo extension.
			$dsn = 'mysql:';
			$with_dbname = true;
		}
		
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
				case "mysqli": return mysqli_get_server_version($this->link);
				case "mysql": return mysql_get_server_info($this->link);
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
				case "mysqli":
					$host = isset($this->options["host"]) ? $this->options["host"] : null;
					
					if(!empty($this->options["persistent"]) && empty($this->options["new_link"]))
						$host = 'p:' . $host;
					
					$this->link = mysqli_connect(
						$host, 
						isset($this->options["username"]) ? $this->options["username"] : null, 
						isset($this->options["password"]) ? $this->options["password"] : null, 
						isset($this->options["db_name"]) ? $this->options["db_name"] : null, 
						isset($this->options["port"]) ? $this->options["port"] : null
					);
					
					break;
					
				case "mysql":
					$host = isset($this->options["host"]) ? $this->options["host"] . ($this->options["port"] ? ":" . $this->options["port"] : "") : null;
					
					if(!empty($this->options["persistent"]) && empty($this->options["new_link"]))
						$this->link = mysql_pconnect(
							$host, 
							isset($this->options["username"]) ? $this->options["username"] : null, 
							isset($this->options["password"]) ? $this->options["password"] : null
						);
					else
						$this->link = mysql_connect(
							$host, 
							isset($this->options["username"]) ? $this->options["username"] : null, 
							isset($this->options["password"]) ? $this->options["password"] : null,
							isset($this->options["new_link"]) ? $this->options["new_link"] : null
						); 
					
					if ($this->link && !empty($this->options["db_name"]) && !mysql_select_db($this->options["db_name"], $this->link))
						$this->close();
					
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
				if (!empty($this->options["encoding"]) && !$this->setCharset($this->options["encoding"]))
					$this->close();
				else if (!empty($this->options["db_name"]))
					$this->db_selected = true;
			}
			
			if (!$this->link || !$this->db_selected) {
				$e = null;
				$error = $this->default_php_extension_type == "mysqli" && mysqli_connect_errno() ? mysqli_connect_error() : ($this->default_php_extension_type == "mysql" && mysql_errno() ? mysql_error() : ($this->default_php_extension_type == "odbc" ? odbc_errormsg() : null));
				
				if ($error)
					$e = new Exception("Failed to connect to MySQL: " . $error);
				
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
				case "mysqli":
					$host = isset($this->options["host"]) ? $this->options["host"] : null;
					
					if(!empty($this->options["persistent"]) && empty($this->options["new_link"]))
						$host = 'p:' . $host;
					
					$this->link = mysqli_connect(
						$host, 
						isset($this->options["username"]) ? $this->options["username"] : null, 
						isset($this->options["password"]) ? $this->options["password"] : null, 
						null, 
						isset($this->options["port"]) ? $this->options["port"] : null
					);
					break;
				
				case "mysql":
					$host = !empty($this->options["host"]) ? $this->options["host"] . (!empty($this->options["port"]) ? ":" . $this->options["port"] : "") : null;
					
					if(!empty($this->options["persistent"]) && empty($this->options["new_link"]))
						$this->link = mysql_pconnect(
							$host, 
							isset($this->options["username"]) ? $this->options["username"] : null, 
							isset($this->options["password"]) ? $this->options["password"] : null
						);
					else
						$this->link = mysql_connect(
							$host, 
							isset($this->options["username"]) ? $this->options["username"] : null, 
							isset($this->options["password"]) ? $this->options["password"] : null, 
							isset($this->options["new_link"]) ? $this->options["new_link"] : null
						); 
					break;
					
				case "pdo":
					$pdo_settings = !empty($this->options["pdo_settings"]) ? $this->options["pdo_settings"] : array();
					
					if (!array_key_exists(PDO::ATTR_ERRMODE, $pdo_settings))
						$pdo_settings[PDO::ATTR_ERRMODE] = PDO::ERRMODE_EXCEPTION;
			   		
					if(!empty($this->options["persistent"]) && empty($this->options["new_link"]))
						$pdo_settings[PDO::ATTR_PERSISTENT] = true;
					
					$options = $this->options;
					unset($options["db_name"]);
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
					unset($options["db_name"]);
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
				$error = $this->default_php_extension_type == "mysqli" && mysqli_connect_errno() ? mysqli_connect_error() : ($this->default_php_extension_type == "mysql" && mysql_errno() ? mysql_error() : ($this->default_php_extension_type == "odbc" ? odbc_errormsg() : null));
				
				if ($error)
					$e = new Exception("Failed to connect to MySQL: " . $error);
				
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
						case "mysqli": $closed = mysqli_close($this->link); break;
						case "mysql": $closed = mysql_close($this->link); break;
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
			if ($this->link) {
				switch ($this->default_php_extension_type) {
					case "mysqli": return @mysqli_ping($this->link);
					case "mysql": return @mysql_ping($this->link);
					case "pdo": return @$this->query("select 1");
					case "odbc": return @$this->query("select 1");
				}
			}
			
			return false;
		}catch(Exception $e) {
			return launch_exception(new SQLException(4, $e));
		}
	} 
	
	public function setCharset($charset = "utf8") {
		$this->init();
		
		try {
			switch ($this->default_php_extension_type) {
				case "mysqli": return mysqli_set_charset($this->link, $charset);
				case "mysql": return mysql_set_charset($charset, $this->link);
				case "pdo": return $this->link->query("SET NAMES $charset");
				case "odbc": return odbc_exec($this->link, "SET NAMES $charset");
			}
			return false;
		}catch(Exception $e) {
			return launch_exception(new SQLException(20, $e, $charset));
		}
	}
	
	public function selectDB($db_name) {
		$this->init();
		
		try {
			if ($db_name) 
				switch ($this->default_php_extension_type) {
					case "mysqli": return mysqli_select_db($this->link, $db_name);
					case "mysql": return mysql_select_db($db_name, $this->link);
					case "pdo": return $this->link->query("use $db_name");
					case "odbc": return odbc_exec($this->link, "use $db_name");
				}
			return false;
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
					case "mysqli": return mysqli_errno($this->link);
					case "mysql": return mysql_errno($this->link);
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
					case "mysqli": return mysqli_error($this->link);
					case "mysql": return mysql_error($this->link);
					case "pdo": return $this->link->errorInfo();
					case "odbc": return odbc_errormsg($this->link);
				}
			}
			
			return "This db driver was not initialized yet! Please call the connect method first!"; 
		}catch(Exception $e) {
			return launch_exception(new SQLException(5, $e));
		}
	}
	
	/*
	 * DELIMITER is a not a server-side statement. It's a client-side command recognised by the mysql command-line client (and, probably, others too). It is not recognised by the mysqli driver, hence there is nothing in the documentation. The documentation does not mention any way for one to change the mysqli driver's statement delimiter.
	 * DELIMITER isn't MySQL server command but MySQL CLI/Query Browser/Workbench etc. It only says when to send command to MySQL server. Omit DELIMITER and send whole SQL statements as one command, it should work.
	 * This means that we need to discard the DELIMITER statements and change the new delimiters to the default one which is ";".
	 */
	private function prepareSqlQueryToBeSend(&$sql, $options = false) {
		$sql = trim($sql);
		
		//if delimiter statement exists
		if ($sql && stripos($sql, "DELIMITER") !== false && preg_match("/\s*DELIMITER(\s*|'|\")/i", $sql)) {
			$delimiter = $options && !empty($options["delimiter"]) ? $options["delimiter"] : ";";
			$queries = self::splitSQL($sql);
			$new_sql = "";
			
			foreach ($queries as $query) {
				if (preg_match("/^DELIMITER(\s*|'|\")/i", $query)) {
					preg_match("/^DELIMITER\s*('|\")?(.*)('|\")?$/i", $query, $matches, PREG_OFFSET_CAPTURE);
					$delimiter = $matches[2][0];
					//$query = "-- @" . $query; //Discard delimiter statement. Do not include it!
					continue 1;
				}
				else if (substr($query, - strlen($delimiter)) == $delimiter)
					$query = substr($query, 0, - strlen($delimiter)) . ";"; //replaces any delimiter by the original delimiter bc MySQL server works with the ; delimiter.
				
				$new_sql .= $query . "\n";
			}
			
			$sql = $new_sql;
			//echo "\nsql:".$sql;die();
		}
		
		return $sql;
	}
	
	public function execute(&$sql, $options = false) {
		$this->init();
		
		try {
			$this->prepareSqlQueryToBeSend($sql, $options);
			//error_log("mysql execute sql:$sql\noptions:".print_r($options, 1)."\n\n", 3, "/var/www/html/livingroop/default/tmp/test.log");
			
			switch ($this->default_php_extension_type) {
				case "mysqli": 
					//when executing multiple insert queries through mysqli_query method, there are not getting saved in DB, so we must use mysqli_multi_query instead. The mysqli_query method only allows 1 sql query at once. If we wish to execute multiple queries, we must use the mysqli_multi_query instead.
					$delimiter = $options && !empty($options["delimiter"]) ? $options["delimiter"] : ";";
					$multiple_query = strpos($sql, $delimiter) !== false;
					//error_log("sql:$sql\noptions:".print_r($options, 1)."\n\n", 3, "/var/www/html/livingroop/default/tmp/test.log");
					
					//if is multi query.
					if ($multiple_query) { //This is very usefull to execute a file created from msqldump, where contains multiple queries
						//echo $sql;die();
						$status = mysqli_multi_query($this->link, $sql);
						
						if ($status) {
							do {
								if ($result = mysqli_store_result($this->link)) {
									if ($result === false) //$result could be an int(0)
										$status = $result;
									else if ($result && $this->isResultValid($result)) //free result just in case
										$this->freeResult($result);
								}
								
								mysqli_more_results($this->link); //we need to have this here
							} 
							while (mysqli_next_result($this->link));
						}
						
						return $status;
					}
					
					//if is single query
					return @mysqli_query($this->link, $sql); //@ is very important bc if the connection is stale, this will give a php warning and launch an exception. The @ char avoids the warning to be shown. This is ok, bc we always catch the exception and the db error.
				case "mysql": return @mysql_query($sql, $this->link); //@ is very important bc if the connection is stale, this will give a php warning and launch an exception. The @ char avoids the warning to be shown. This is ok, bc we always catch the exception and the db error.
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
			$this->prepareSqlQueryToBeSend($sql, $options);
			//error_log("mysql execute sql:$sql\noptions:".print_r($options, 1)."\n\n", 3, "/var/www/html/livingroop/default/tmp/test.log");
			
			if (is_array($options) && stripos($sql, "select ") === 0) { //$sql can be a procedure call
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
				
				if(isset($options["limit"]) && is_numeric($options["limit"])) {
					if (stripos($sql, " order by ") !== false)
						$sql = "SELECT * FROM (" . $sql . ") AS QUERY_WITH_PAGINATION LIMIT " . (!empty($options["start"]) ? $options["start"] : 0) . ", " . $options["limit"];
					else
						$sql .= " LIMIT " . (!empty($options["start"]) ? $options["start"] : 0) . ", " . $options["limit"];
				}
			}
			
			//echo "$sql<br>";
			//error_log($this->options["db_name"] . ":\n". $sql . "\n\n", 3, "/tmp/sql_log.log");
			//error_log($this->options["db_name"] . ":\n". $sql . "\n\n", 3, $GLOBALS["log_file_path"] ? $GLOBALS["log_file_path"] : "/var/www/html/livingroop/default/tmp/phpframework.log");
			//error_log("mysql query sql:\noptions:".print_r($options, true) . "\n$sql\n\n", 3, "/var/www/html/livingroop/default/tmp/test.log");
			
			switch ($this->default_php_extension_type) {
				case "mysqli": return @mysqli_query($this->link, $sql); //@ is very important bc if the connection is stale, this will give a php warning and launch an exception. The @ char avoids the warning to be shown. This is ok, bc we always catch the exception and the db error.
				case "mysql": return @mysql_query($sql, $this->link);
				case "pdo": return $this->link->query($sql);
				case "odbc": return odbc_exec($this->link, $sql);
			}
		} catch(Exception $e) {
			//error_log($this->options["db_name"] . ":\n". $sql . "\n\n", 3, $GLOBALS["log_file_path"] ? $GLOBALS["log_file_path"] : "/var/www/html/livingroop/default/tmp/phpframework.log");die();
			return launch_exception(new SQLException(6, $e, array($sql)));
		}
	} 
	 
	public function freeResult($result) {
		try {
			switch ($this->default_php_extension_type) {
				case "mysqli": mysqli_free_result($result); return true;
				case "mysql": return mysql_free_result($result);
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
				case "mysqli": return mysqli_num_rows($result);
				case "mysql": return mysql_num_rows($result);
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
				case "mysqli": return mysqli_num_fields($result);
				case "mysql": return mysql_num_fields($result);
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
				case "mysqli": 
					if ($array_type == DB::FETCH_OBJECT)
						return mysqli_fetch_object($result);
					
					$array_type = $this->convertFetchTypeToExtensionType($array_type ? $array_type : DB::FETCH_BOTH);
					return mysqli_fetch_array($result, $array_type);
				case "mysql": 
					if ($array_type == DB::FETCH_OBJECT)
						return mysql_fetch_object($result);
					
					$array_type = $this->convertFetchTypeToExtensionType($array_type ? $array_type : DB::FETCH_BOTH);
					return mysql_fetch_array($result, $array_type);
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
					case "mysqli": 
						$field = mysqli_fetch_field_direct($result, $offset);
						
						if ($field) 
							$this->prepareMysqliField($field);
						break;
					case "mysql": 
						$field = mysql_fetch_field($result, $offset);
						
						if ($field) 
							$this->prepareMysqlField($field);
						break;
					case "pdo": 
						$field = $result->getColumnMeta($offset);
						
						if ($field)
							self::preparePDOField($field, self::$mysqli_flags);
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
	
	/*
	array(
		name =>	The name of the column
		orgname =>	Original column name if an alias was specified
		table =>	The name of the table this field belongs to (if not calculated)
		orgtable =>	Original table name if an alias was specified
		def =>	The default value for this field, represented as a string
		max_length =>	The maximum width of the field for the result set.
		length =>	The width of the field, as specified in the table definition.
		charsetnr =>	The character set number for the field.
		flags =>	An integer representing the bit-flags for the field.
		type =>	The data type used for this field
		decimals =>	The number of decimals used (for numeric fields)
	)
	*/
	private function prepareMysqliField(&$field) {
		//echo "<pre>";print_r($field);
		if (is_array($field))
			$field = (object) $field; //cast to object
		
		$field_types = self::$mysqli_data_types[$field->type];
		
		if ($field->flags) {
			foreach (self::$mysqli_flags as $n => $t) 
			    	if ($field->flags & $n)
			    		$field->$t = true;
			
			$field_type_0 = isset($field_types[0]) ? $field_types[0] : null;
			$field_type_1 = isset($field_types[1]) ? $field_types[1] : null;
			
			switch($field->type) {
				//246 => array("decimal", "numeric")
				case MYSQLI_TYPE_NEWDECIMAL: 
					$field->type = empty($field->decimal) && !empty($field->numeric) ? $field_type_1 : $field_type_0;
					break;
			
				//249 => array("tinyblob", "tinytext"),
				//250 => array("mediumblob", "mediumtext"),
				//251 => array("longblob", "longtext"),
				//252 => array("blob", "text"),
				case MYSQLI_TYPE_TINY_BLOB: 
				case MYSQLI_TYPE_MEDIUM_BLOB: 
				case MYSQLI_TYPE_LONG_BLOB: 
				case MYSQLI_TYPE_BLOB: 
					$field->type = empty($field->blob) ? $field_type_1 : $field_type_0;
					break;
				
				default:
					$field->type = $field_type_0;
			}
		}
		else 
			$field->type = isset($field_types[0]) ? $field_types[0] : null;
		
		//echo "<pre>";print_r($field);die();
	}
	
	/*
	array(
		name => column name
		table => name of the table the column belongs to, which is the alias name if one is defined
		max_length => maximum length of the column
		not_null => 1 if the column cannot be null
		primary_key => 1 if the column is a primary key
		unique_key => 1 if the column is a unique key
		multiple_key => 1 if the column is a non-unique key
		numeric => 1 if the column is numeric
		blob => 1 if the column is a BLOB
		type => the type of the column
		unsigned => 1 if the column is unsigned
		zerofill => 1 if the column is zero-filled
	)
	*/
	private function prepareMysqlField(&$field) {
		if (is_array($field))
			$field = (object) $field; //cast to object
		
		//prepare attributes
		$field->length = $field->max_length; //optional
		
		//echo "<pre>";print_r($field);die();
	}
	
	public function isResultValid($result) {
		switch ($this->default_php_extension_type) {
			case "mysqli": return is_a($result, "mysqli_result");
			//case "mysql": return is_resource($result); //already below
			case "pdo": return is_a($result, "PDOStatement");
		}
		
		return is_resource($result) || is_object($result);
	}
	
	public function listDBs($options = false, $column_name = "name") {
		return parent::listDBs($options, "Database");
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
					"name" => /*(!empty($table["table_schema"]) && empty($this->options["schema"]) ? $table["table_schema"] . "." : "") . */(isset($table["table_name"]) ? $table["table_name"] : null), //The table schema is the database name in mysql, so basically this concept doesn't apply like it applies in other DBs like mssql and pgsql. //Only add schema if is not defined in options
					"table_name" => isset($table["table_name"]) ? $table["table_name"] : null,
					"schema" => isset($table["table_schema"]) ? $table["table_schema"] : null,
					"type" => isset($table["table_type"]) ? strtolower($table["table_type"]) : null,
					"engine" => isset($table["table_storage_engine"]) ? $table["table_storage_engine"] : null,
					"charset" => isset($table["table_charset"]) ? $table["table_charset"] : null,
					"collation" => isset($table["table_collation"]) ? $table["table_collation"] : null,//utf8_general_ci
					"comment" => isset($table["table_comment"]) ? $table["table_comment"] : null
			    	);
		
		return $tables;
	}
	
	/*public function listTableFields($table) {
		$fields = array();
		
		$sql = "SHOW FULL COLUMNS FROM {$table}";
		$result = $this->query($sql);
		
		if(isset($result)) {
			while($field = $this->fetchAssoc($result)) 
				if (isset($field["Field"])) {
					$field_type = isset($field["Type"]) ? $field["Type"] : "";
					$type = explode("(", $field_type);
					$length = isset($type[1]) ? explode(")", $type[1]) : array();
					$is_unsigned = strpos($field_type, "unsigned") > 0;
					
					$fields[ $field["Field"] ] = array(
						"type" => isset($type[0]) ? $type[0] : null,
						"length" => isset($length[0]) && is_numeric($length[0]) ? $length[0] : false,
						"null" => isset($field["Null"]) && $field["Null"] == "NO" ? false : true,
						"primary_key" => isset($field["Key"]) && $field["Key"] == "PRI" ? true : false,
						"unsigned" => $is_unsigned,
						"default" => isset($field["Default"]) ? $field["Default"] : null,
						"charset" => null,
						"collation" => isset($field["Collation"]) ? $field["Collation"] : null,
						"extra" => isset($field["Extra"]) ? $field["Extra"] : null,
						"comment" => isset($field["Comment"]) ? $field["Comment"] : null,
					);
				}
			
			$this->freeResult($result);
		}
		
		return $fields;
	}*/
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
				 	$ck = isset($field["column_key"]) ? $field["column_key"] : null;
					$ct = isset($field["column_type"]) ? $field["column_type"] : null;
					$dt = isset($field["data_type"]) ? $field["data_type"] : null;
					$cd = isset($field["column_default"]) ? $field["column_default"] : null;
					
					$lck = strtolower($ck);
					$lct = strtolower($ct);
					$is_unsigned = strpos($lct, "unsigned") > 0;
					$flags = null;
					
					$length = !empty($field["character_maximum_length"]) ? $field["character_maximum_length"] : (isset($field["numeric_precision"]) ? $field["numeric_precision"] : null);
					preg_match("/" . $dt . "\(([0-9]+)\)/", $ct, $matches);
					$l = !empty($matches[0]) ? $matches[1] : null;
					//$length = !is_numeric($length) || ($l > 0 && $l < $length) ? $l : $length; //JP 2021-01-25: this is not correct bc the character_maximum_length and numeric_precision don't always have the correct length. The length should be taken from the column_type, if possible.
					
					if (is_numeric($l))
						$length = $l;
					else if (is_numeric($length) && preg_match("/" . $dt . "\(([0-9]+),([0-9]+)\)/", $ct, $matches) && !empty($matches[0]))
						$length = $matches[1] . "," . $matches[2];
					
					//bc of mariaDB
					$cd = $cd == "''" ? "" : $cd;
					
					$props = array(
						"name" => $field["column_name"],
						"type" => self::convertColumnTypeFromDB($dt, $flags),
						"length" => $length,
						"null" => isset($field["is_nullable"]) && strtolower($field["is_nullable"]) == "no" ? false : true,
						"primary_key" => !empty($field["is_primary"]) || $lck == "pri" ? true : false,
						"unique" => !empty($field["is_primary"]) || !empty($field["is_unique"]) || $lck == "pri" || $lck == "uni" ? true : false,
						"unsigned" => $is_unsigned,
						"default" => $cd,
						"charset" => isset($field["character_set_name"]) ? $field["character_set_name"] : null,
						"collation" => isset($field["collation_name"]) ? $field["collation_name"] : null,
						"extra" => isset($field["extra"]) ? $field["extra"] : null,
						"comment" => isset($field["column_comment"]) ? $field["column_comment"] : null,
					);
					
					//set auto_increment and flags
					$auto_increment = in_array($dt, array("serial", "smallserial", "bigserial"));
					
					if ($flags)
						foreach ($flags as $k => $v) {
							if ($v && $k == "auto_increment")
								$auto_increment = true;
							else
								$props[$k] = $v;
						}
					
					if ($auto_increment && stripos($props["extra"], "auto_increment") === false)
						$props["extra"] .= ($props["extra"] ? " " : "") . "auto_increment";
					
					$props["auto_increment"] = $auto_increment || stripos($props["extra"], "auto_increment") !== false;
					
					$fields[ $field["column_name"] ] = $props;
				}
		
		return $fields;
	}
	
	public function getInsertedId($options = false) {
    		if ($this->init())
    			switch ($this->default_php_extension_type) {
				case "mysqli": 
					return mysqli_insert_id($this->link);
				case "mysql": 
					return mysql_insert_id($this->link);
				case "pdo": 
					try {
						return $this->link->lastInsertId(); //Note that the PDO driver may not support the lastInsertId function and in this case it will trigger an IM001 SQLSTATE. More info in https://www.php.net/manual/en/pdo.lastinsertid.php
					}
					catch (Exception $e) {
						//Do nothing and continue to code bellow
					}
				case "odbc": 
					$options = $options ? $options : array();
					$options["return_type"] = "result";
					$result = $this->getData("SELECT LAST_INSERT_ID() as id", $options);
					
					if ($result)
						return isset($result[0]["id"]) ? $result[0]["id"] : null;
			}
		
		return 0;
	}
	
	//Mysqli: MYSQLI_ASSOC, MYSQLI_NUM, or MYSQLI_BOTH
	//Mysql: MYSQL_ASSOC, MYSQL_NUM, or MYSQL_BOTH
	private function convertFetchTypeToExtensionType($fetch_type) {
		switch ($this->default_php_extension_type) {
			case "mysqli":
				switch ($fetch_type) {
					case DB::FETCH_ASSOC: return MYSQLI_ASSOC;
					case DB::FETCH_NUM: return MYSQLI_NUM;
					case DB::FETCH_BOTH: return MYSQLI_BOTH;
				}
				break;
			case "mysql":
				switch ($fetch_type) {
					case DB::FETCH_ASSOC: return MYSQL_ASSOC;
					case DB::FETCH_NUM: return MYSQL_NUM;
					case DB::FETCH_BOTH: return MYSQL_BOTH;
				}
				break;
		}
		
		return self::convertFetchTypeToPDOAndODBCExtensions($this->default_php_extension_type, $fetch_type);
	}
}
?>
