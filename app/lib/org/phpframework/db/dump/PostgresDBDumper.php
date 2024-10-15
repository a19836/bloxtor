<?php
//TODO: finish to fill the methods with the proper SQL

include_once get_lib("org.phpframework.db.dump.DBDumper");

class PostgresDBDumper extends DBDumper {

	public $types_without_length = array();
	
	public function __construct($DBDumperHandler) {
		$this->DBDumperHandler = $DBDumperHandler;
		
		$this->types_without_length = array();
		$db_column_types_ignored_props = $this->DBDumperHandler->getDBDriver()->getDBColumnTypesIgnoredProps();
		
		if (is_array($db_column_types_ignored_props))
			foreach ($db_column_types_ignored_props as $type => $props)
				if (is_array($props) && in_array("length", $props))
					$this->types_without_length[] = $type;
	}

	public function databases($db_name) {
		$ret = "CREATE DATABASE /*!32312 IF NOT EXISTS*/ \"{$db_name}\"" . PHP_EOL . PHP_EOL . 
		  "\\c \"{$db_name}\";" . PHP_EOL . PHP_EOL;

		return $ret;
	}

	public function createTable($res, $table_name, $foreign_keys_to_ignore = false) {
		//$res here is null bc the show_create_table method return an empty string
		$create_table = "";

		//prepare sequences
		//WHERE table_schema='public' AND 
		$stmt = str_replace("\t", "", "SELECT 
			    table_name, 
			    column_name, 
			    column_default, 
			    pg_get_serial_sequence(table_name, column_name) as sequence_name
			FROM information_schema.columns 
			WHERE table_name='$table_name'");
		$seq_rows = $this->DBDumperHandler->getDBDriver()->getSQL($stmt);
		
		foreach ($seq_rows as $r)
			if (!empty($r["sequence_name"])) {
				$last_value_res = $this->DBDumperHandler->getDBDriver()->getSQL("SELECT last_value FROM " . $r["sequence_name"]);
				$start_value = isset($last_value_res[0]["last_value"]) ? $last_value_res[0]["last_value"] : null;
				$start_value = is_numeric($start_value) ? $start_value + 1 : 0;

				$create_table .= "CREATE SEQUENCE IF NOT EXISTS " . $r["sequence_name"] . " START $start_value;" . PHP_EOL;
			}
    	   
		//prepare create table sql
		$stmt = $this->getShowTableColumnsStmt($table_name);
		$res = array();
		$rows = $this->DBDumperHandler->getDBDriver()->getSQL($stmt);
		
		foreach ($rows as $r) 
			$res[] = $r;
    	   
		$create_table .= $this->convertColumnsIntoCreateTable($res, $table_name) . ";";

		//prepare constraints
		$repeated_keys = array();

		//prepare pks, fks and unique constraints
		$fks_to_ignore = array();
		if ($foreign_keys_to_ignore)
			foreach ($foreign_keys_to_ignore as $fk)
				if (isset($fk["constraint_name"]))
					$fks_to_ignore[ $fk["constraint_name"] ] = $fk;
		
		//WHERE connamespace='public'::regnamespace AND
		$stmt = str_replace("\t", "", "SELECT 
			  conname AS constraint_name, 
			  contype AS constraint_type,
			  pg_get_constraintdef(oid) AS constraint_def
			FROM pg_constraint
			WHERE conrelid::regclass='$table_name'::regclass
			ORDER BY conrelid::regclass::text, contype DESC");
		$sql = "";
		$rows = $this->DBDumperHandler->getDBDriver()->getSQL($stmt);
		foreach ($rows as $r) {
			$cn = isset($r["constraint_name"]) ? $r["constraint_name"] : null;
			$cd = isset($r["constraint_def"]) ? $r["constraint_def"] : null;
			
			$c_sql = "CONSTRAINT " . $cn . " " . $cd;

			if (isset($r["constraint_type"]) && strtolower($r["constraint_type"]) == "f" && $fks_to_ignore[$cn]) {
				$fk = $fks_to_ignore[$cn];
				$fk_pt = isset($fk["parent_table"]) ? $fk["parent_table"] : null;
				
				$this->DBDumperHandler->setTableExtraSql($fk_pt, "ALTER TABLE \"$table_name\" ADD $c_sql;" . PHP_EOL);
			}
			else
				$sql .= "," . PHP_EOL . "  " . $c_sql;

			$repeated_keys[] = $cn;
		}

		if ($sql) {
			$pos = strrpos($create_table, ")");
			$create_table = trim(substr($create_table, 0, $pos)) . $sql . PHP_EOL . substr($create_table, $pos);
		}
    	   
		//prepare indexes
		//WHERE schemaname='public' AND 
		$stmt = str_replace("\t", "", "SELECT 
			  indexname AS index_name,
			  indexdef AS index_def
			FROM pg_indexes
			WHERE tablename='$table_name'
			ORDER BY tablename DESC");
		$rows = $this->DBDumperHandler->getDBDriver()->getSQL($stmt);
		foreach ($rows as $r) {
			$in = isset($r["index_name"]) ? $r["index_name"] : null;
			
			if (!in_array($in, $repeated_keys) && !empty($r["index_def"])) {
				$create_table .= PHP_EOL . $r["index_def"] . ";";
				$repeated_keys[] = $in;
			}
		}
    	   	
		//changing indexes owner
		foreach ($seq_rows as $r)
			if (!empty($r["sequence_name"]) && isset($r["table_name"]) && isset($r["column_name"]))
				$create_table .= PHP_EOL . "ALTER SEQUENCE " . $r["sequence_name"] . " OWNED BY \"" . $r["table_name"] . "\".\"" . $r["column_name"] . "\";";
	    
    	   	//$db_dumper_settings = $this->DBDumperHandler->getDBDumperSettings();
    	
		if (!$create_table) {
			//throw new \Exception("Error getting table code, unknown output");
			return "/* Error getting table code, unknown output. */" . PHP_EOL
		  		 . PHP_EOL;
		}

		$ret = $create_table . PHP_EOL . 
			PHP_EOL;
		
		return $ret;
    }
    
    private function convertColumnsIntoCreateTable($res, $table_name) {
		$sql = "CREATE TABLE \"$table_name\" (" . PHP_EOL;

		if ($res) {
			foreach ($res as $row) {
				$name = isset($row["column_name"]) ? $row["column_name"] : null;
				$type = isset($row["data_type"]) ? $row["data_type"] : null;
				$length = isset($row["character_maximum_length"]) ? $row["character_maximum_length"] : (isset($row["numeric_precision"]) ? $row["numeric_precision"] : null);
				
				$row["is_primary"] = isset($row["is_primary"]) ? explode(",", str_replace(array("{", "}"), "", strtolower(trim($row["is_primary"])))) : array();
				$row["is_unique"] = isset($row["is_unique"]) ? explode(",", str_replace(array("{", "}"), "", strtolower(trim($row["is_unique"])))) : array();
				$pk = in_array("t", $row["is_primary"]) || in_array(true, $row["is_primary"], true);
				
				//$check_constraint_value = isset($row["check_constraint_value"]) ? $row["check_constraint_value"] : null; //is included in the create_table method.
				//$unique_constraint_name = isset($row["unique_constraint_name"]) ? $row["unique_constraint_name"] : null;
				
				//$unique = $pk ||  in_array("t", $row["is_unique"]) || in_array(true, $row["is_unique"], true) || $unique_constraint_name ? true : false; //is included in the create_table method.
				$null = isset($row["is_nullable"]) && strtolower(trim($row["is_nullable"])) == "no" ? false : true;
				$default = isset($row["column_default"]) && strlen($row["column_default"]) ? $row["column_default"] : null;
				
				$collation = isset($row["collation_name"]) ? $row["collation_name"] : null;
				//$charset = isset($row["character_set_name"]) ? $row["character_set_name"] : null; //not used in pgsql server
				//$comment = isset($row["column_comment"]) ? $row["column_comment"] : null; //not used in pgsql server
				
				$length = !in_array($type, $this->types_without_length) && is_numeric($length) && $length > 0 ? "($length)" : "";
				//$check_constraint_value = $check_constraint_value ? " CHECK $check_constraint_value" : ""; //is included in the create_table method.
				//$unique = $unique && !$pk ? "UNIQUE" : ""; //is included in the create_table method.
				$null = $null ? "NULL" : "NOT NULL";
				$default = isset($default) ? "DEFAULT " . $default : "";
				//$charset = !empty($charset) ? "ENCODING '$charset'" : ""; //not used in pgsql server
				$collation = !empty($collation) ? "COLLATE '$collation'" : "";
				//$comment = !empty($comment) ? "COMMENT '$comment'" : ""; //not used in pgsql server
				
				//$sql .= "  " . trim("\"$name\" $type{$length} $unique $null $charset $collation $default $extra $comment $check_constraint_value") . "," . PHP_EOL;
				$sql .= "  " . trim("\"$name\" $type{$length} $null $collation $default") . "," . PHP_EOL;
			}
			
			$sql = substr($sql, 0, strlen($sql) - 2) . PHP_EOL;//remove the last comma ','
		}
		
		$sql .= ")";
		
		return $sql;
	}

	public function createRecordsInsertStmt($table_name, $rows) {
		$db_dumper_settings = $this->DBDumperHandler->getDBDumperSettings();
		$CompressionHandler = $this->DBDumperHandler->getFileCompressionHandler();

		// colNames is used to get the name of the columns when using complete-insert
		if (!empty($db_dumper_settings['complete-insert']))
			$attr_names = $this->DBDumperHandler->getTableAttributesNames($table_name);

		$line_size = 0;
		$only_once = true;
		$count = 0;
		$ignore = !empty($db_dumper_settings['insert-ignore']) ? ' ON CONFLICT DO NOTHING' : '';
		$net_buffer_length = isset($db_dumper_settings['net_buffer_length']) ? $db_dumper_settings['net_buffer_length'] : null;
		
		foreach ($rows as $row) {
			$count++;
			$vals = $this->DBDumperHandler->prepareTableRowAttributes($table_name, $row);

			if ($only_once || empty($db_dumper_settings['extended-insert'])) {
				if (!empty($db_dumper_settings['complete-insert']))
					$str = "INSERT INTO " . $this->escapeTable($table_name) . " (" . implode(", ", $attr_names) . ") VALUES (" . implode(",", $vals) . ")";
				else
					$str = "INSERT INTO " . $this->escapeTable($table_name) . " VALUES (" . implode(",", $vals) . ")";

				$only_once = false;
			} 
			else
				$str = ",(" . implode(",", $vals) . ")";
			
			$line_size += $CompressionHandler->write($str);
			
			if (empty($db_dumper_settings['extended-insert']) || $line_size > $net_buffer_length) {
				$only_once = true;
				$line_size = $CompressionHandler->write("$ignore;" . PHP_EOL);
			}
		}

		if (!$only_once) 
			$CompressionHandler->write("$ignore;" . PHP_EOL);

		return $count;
	}
	
	public function getSqlStmtWithLimit($sql, $limit) {
		return $sql . " LIMIT {$limit}";
	}
    
	public function createStandInTableForView($view_name, $inner_sql) {
    		return "CREATE TABLE IF NOT EXISTS " . $this->escapeTable($view_name) . " (".
            PHP_EOL . $inner_sql . PHP_EOL . ");" . PHP_EOL;
	}

	public function getTableAttributeProperties($attr_type) {
		$db_column_numeric_types = $this->DBDumperHandler->getDBDriver()->getDBColumnNumericTypes();
		$db_column_blob_types = $this->DBDumperHandler->getDBDriver()->getDBColumnBlobTypes();
		$db_column_boolean_types = $this->DBDumperHandler->getDBDriver()->getDBColumnBooleanTypes();
		
		$attr_props = array();
		$attr_props['field'] = isset($attr_type['column_name']) ? $attr_type['column_name'] : null;
		$attr_props['type_sql'] = isset($attr_type['data_type']) ? $attr_type['data_type'] : null;
		$parts = explode(" ", $attr_props['type_sql']);

		if ($pos = strpos($parts[0], "(")) {
		  $attr_props['type'] = substr($parts[0], 0, $pos);
		  $attr_props['length'] = str_replace(")", "", substr($parts[0], $pos + 1));
		  $attr_props['attributes'] = isset($parts[1]) ? $parts[1] : null;
		} 
		else {
		  $attr_props['type'] = $parts[0];
		  $attr_props['length'] = !empty($attr_type["character_maximum_length"]) ? $attr_type["character_maximum_length"] : (isset($attr_type["numeric_precision"]) ? $attr_type["numeric_precision"] : null);
		}

		$attr_props['is_nullable'] = !isset($attr_type['is_nullable']) || strtolower($attr_type['is_nullable']) != "no" ? false : true;
		$attr_props['is_numeric'] = is_array($db_column_numeric_types) && in_array($attr_props['type'], $db_column_numeric_types);
		$attr_props['is_blob'] = is_array($db_column_blob_types) && in_array($attr_props['type'], $db_column_blob_types);
		$attr_props['is_boolean'] = is_array($db_column_boolean_types) && in_array($attr_props['type'], $db_column_boolean_types);
		
		return $attr_props;
	}

	public function getTableAttributesPropertiesBitHexFunc($attr_name) {
		return $attr_name;
	}

	public function getTableAttributesPropertiesBlobHexFunc($attr_name) {
		return $attr_name;
	}

	public function createView($row) {
		$ret = "";
		
		if (!isset($row['Create View'])) {
			//throw new \Exception("Error getting view structure, unknown output");
			return "/* Error getting view structure, unknown output */" . PHP_EOL
				 . PHP_EOL;
		}

		$ret .= "/* DROP VIEW IF EXISTS ".(isset($row['View']) ? $row['View'] : "")." */;" . PHP_EOL . 
			$row['Create View'].(substr($row['Create View'], -1) == ";" ? "" : ";") . PHP_EOL
			 . PHP_EOL;
		
		return $ret;
	}

	public function createTrigger($row) {
		$ret = "";
		if (!isset($row['SQL Original Statement'])) {
			//throw new \Exception("Error getting trigger code, unknown output");
			return "/* Error getting trigger code, unknown output */" . PHP_EOL
				 . PHP_EOL;
		}

		$ret .= "/* DROP TRIGGER IF EXISTS ".(isset($row['Trigger']) ? $row['Trigger'] : "")." */;" . PHP_EOL . 
			$row['SQL Original Statement'].(substr($row['SQL Original Statement'], -1) == ";" ? "" : ";") . PHP_EOL . 
			PHP_EOL;
		
		return $ret;
	}

	public function createProcedure($row) {
		$ret = "";
		
		if (!isset($row['Create Procedure'])) {
			//throw new \Exception("Error getting procedure code, unknown output.");
			return "/* Error getting procedure code, unknown output. */" . PHP_EOL
			 . PHP_EOL;
		}

		$ret .= "/* DROP PROCEDURE IF EXISTS ".(isset($row['Procedure']) ? $row['Procedure'] : "")." */;" . PHP_EOL . 
			$row['Create Procedure'] . (substr($row['Create Procedure'], -1) == ";" ? "" : ";") . PHP_EOL . 
			PHP_EOL;
		
		return $ret;
	}

	public function createFunction($row) {
		$ret = "";
		if (!isset($row['Create Function'])) {
			//throw new \Exception("Error getting function code, unknown output.");
			return "/* Error getting function code, unknown output. */" . PHP_EOL
				 . PHP_EOL;
		}

		$ret .= "/* DROP FUNCTION IF EXISTS ".(isset($row['Function']) ? $row['Function'] : "")." */;" . PHP_EOL . 
			$row['Create Function'].(substr($row['Create Function'], -1) == ";" ? "" : ";") . PHP_EOL . 
			PHP_EOL;
		
		return $ret;
	}

	public function createEvent($row) {
		$ret = "";
		
		if (!isset($row['Create Event'])) {
			//throw new \Exception("Error getting event code, unknown output.");
			return "/* Error getting event code, unknown output. */" . PHP_EOL
				 . PHP_EOL;
		}
		
		$ret .= "/* DROP EVENT IF EXISTS ".(isset($row['Event']) ? $row['Event'] : "")." */;" . PHP_EOL . 
			$row['Create Event'] . (substr($row['Create Event'], -1) == ";" ? "" : ";") . PHP_EOL . 
			PHP_EOL;
		
		return $ret;
	}

	public function backupParameters() {
		return PHP_EOL;
	}

	public function restoreParameters() {
		return PHP_EOL;
	}

	public function startDisableConstraintsAndTriggersStmt($tables) {
		//Disables foreign keys and triggers for all tables
		$sql = "-- Needs root permission
--SET session_replication_role = replica;" . PHP_EOL;
	   
		$db_dumper_settings = $this->DBDumperHandler->getDBDumperSettings();

		if ($tables) {
			$tables = is_array($tables) ? $tables : array($tables);
			$sql .= PHP_EOL;

			//Disables foreign keys and triggers for a specific table
			foreach ($tables as $t)
				if ($t)
		   			$sql .= "--ALTER TABLE IF EXISTS \"$t\" DISABLE TRIGGER ALL;" . PHP_EOL;
		   
			//Drops all foreign keys constraints. This is redundant, but just in case we hv the code here to be executed.
			if (!empty($db_dumper_settings['add-drop-table']))
				$sql .= PHP_EOL . "-- Does NOT need root permission.
DO $$
DECLARE myvar varchar = '';
DECLARE t_row record;
BEGIN
	FOR t_row in 
		SELECT tc.table_name, tc.constraint_name 
		FROM information_schema.table_constraints tc
		INNER JOIN pg_namespace nsp on nsp.nspname = tc.constraint_schema
		INNER JOIN pg_constraint pgc on pgc.conname = tc.constraint_name and pgc.connamespace = nsp.oid and pgc.contype = 'f'
		INNER JOIN information_schema.columns col on col.table_schema = tc.table_schema and col.table_name = tc.table_name and col.ordinal_position=ANY(pgc.conkey)
		WHERE tc.constraint_schema NOT LIKE 'pg%' AND tc.constraint_schema <> 'information_schema' AND tc.table_name in ('" . implode("', '", $tables) . "') AND tc.constraint_type = 'FOREIGN KEY'
	LOOP
		myvar = '';
		
		SELECT concat('ALTER TABLE IF EXISTS \"', t_row.table_name, '\" DROP CONSTRAINT \"', t_row.constraint_name, '\";') INTO myvar;
		
		IF myvar != '' AND myvar IS NOT NULL THEN
			EXECUTE myvar;
	   	END IF;
	END LOOP;
END $$;" . PHP_EOL;
			else if (!empty($db_dumper_settings['no-create-info']) && empty($db_dumper_settings['no-data'])) { //only if is dump data without schema
				$stmt = str_replace("\t", "", "SELECT 
		  		   tc.table_name, 
		  		   tc.constraint_name
				FROM information_schema.table_constraints tc
				INNER JOIN pg_namespace nsp on nsp.nspname = tc.constraint_schema
				INNER JOIN pg_constraint pgc on pgc.conname = tc.constraint_name and pgc.connamespace = nsp.oid and pgc.contype = 'f'
				INNER JOIN information_schema.columns col on col.table_schema = tc.table_schema and col.table_name = tc.table_name and col.ordinal_position=ANY(pgc.conkey)
				WHERE tc.constraint_schema NOT LIKE 'pg%' AND tc.constraint_schema <> 'information_schema' AND tc.table_name in ('" . implode("', '", $tables) . "') AND tc.constraint_type = 'FOREIGN KEY'");
	    	   	
				$sql .= PHP_EOL . "--set foreign keys to deferred";
		    	   	
		    	   	$rows = $this->DBDumperHandler->getDBDriver()->getSQL($stmt);
		    	   	
				foreach ($rows as $r)
					if (!empty($r["table_name"]) && isset($r["constraint_name"]))
						$sql .= PHP_EOL . "ALTER TABLE " . $r["table_name"] . " ALTER CONSTRAINT " . $r["constraint_name"] . " DEFERRABLE INITIALLY DEFERRED;";
		    	   	
				$sql .= PHP_EOL;
			}
		}
	  
		return $sql;
	}

	public function endDisableConstraintsAndTriggersStmt($tables) {
		//Re-enables foreign keys and triggers for all tables
		$sql = "-- Needs root permission
--SET session_replication_role = DEFAULT;" . PHP_EOL;
	   
		//old version
		$db_dumper_settings = $this->DBDumperHandler->getDBDumperSettings();

		if ($tables) {
			$tables = is_array($tables) ? $tables : array($tables);
			$sql .= PHP_EOL;

			//Re-enables foreign keys and triggers for a specific table
			foreach ($tables as $t)
				if ($t)
					$sql .= "--ALTER TABLE IF EXISTS \"$t\" ENABLE TRIGGER ALL;" . PHP_EOL;
		   
			if (empty($db_dumper_settings['add-drop-table']) && !empty($db_dumper_settings['no-create-info']) && empty($db_dumper_settings['no-data'])) { //only if is dump data without schema
				$stmt = str_replace("\t", "", "SELECT tc.table_name, tc.constraint_name, tc.is_deferrable, tc.initially_deferred, pgc.condeferrable, pgc.condeferred
		FROM information_schema.table_constraints tc
		INNER JOIN pg_namespace nsp on nsp.nspname = tc.constraint_schema
		INNER JOIN pg_constraint pgc on pgc.conname = tc.constraint_name and pgc.connamespace = nsp.oid and pgc.contype = 'f'
		INNER JOIN information_schema.columns col on col.table_schema = tc.table_schema and col.table_name = tc.table_name and col.ordinal_position=ANY(pgc.conkey)
		WHERE tc.constraint_schema NOT LIKE 'pg%' AND tc.constraint_schema <> 'information_schema' AND tc.table_name in ('" . implode("', '", $tables) . "') AND tc.constraint_type = 'FOREIGN KEY'");
	    	   	
				$sql .= PHP_EOL . "--set foreign keys to initial deferred state";
				$sql .= PHP_EOL . "COMMIT;"; //commit here is very important othewise we will get a sql error saying: 'PHP Warning:  pg_query(): Query failed: ERROR:  cannot ALTER TABLE "sub_item" because it has pending trigger events in /var/www/html/hospital/v11/.backups/backup_4_101/deploying/migrate_dbs/lib/org/phpframework/db/driver/PostgresDB.php on line 436'
	    	   		
	    	   		$rows = $this->DBDumperHandler->getDBDriver()->getSQL($stmt);
	    	   		
	    	   		foreach ($rows as $r) 
	    	   			if (!empty($r["table_name"]) && isset($r["constraint_name"])) {
			    	   		$deferrable = isset($r["is_deferrable"]) && strtolower($r["is_deferrable"]) == "no" ? "NOT DEFERRABLE" : "DEFERRABLE";
			    	   		$deferred = isset($r["initially_deferred"]) && strtolower($r["initially_deferred"]) == "no" ? "INITIALLY IMMEDIATE" : "INITIALLY DEFERRED";
			    	   		
			    	   		$sql .= PHP_EOL . "ALTER TABLE " . $r["table_name"] . " ALTER CONSTRAINT " . $r["constraint_name"] . " $deferrable $deferred;";
			    	   	}
	    	   	
	    	   		$sql .= PHP_EOL;
			}
		}
	    
		return $sql;
	}
}
?>
