<?php
include_once get_lib("org.phpframework.db.dump.DBDumper");

class MSSqlDBDumper extends DBDumper {

	public function __construct($DBDumperHandler) {
		$this->DBDumperHandler = $DBDumperHandler;
	}

	public function databases($db_name) {
		$sql = "SELECT SERVERPROPERTY('collation') as 'collation';";
		$rows = $this->DBDumperHandler->getDBDriver()->getSQL($sql);
		$collation = isset($rows[0]["collation"]) ? $rows[0]["collation"] : null;

		$ret = "";

		$ret .= "/* IF DB_ID ('{$db_name}') IS NULL */".
			" CREATE DATABASE {$db_name}".
			" /* COLLATE {$collation} */;".PHP_EOL.PHP_EOL.
			"USE {$db_name};".PHP_EOL.PHP_EOL;

		return $ret;
	}

	public function createTable($res, $table_name, $foreign_keys_to_ignore = false) {
		$row = isset($res[0]) ? $res[0] : null;
		//$db_dumper_settings = $this->DBDumperHandler->getDBDumperSettings();

		if (!$row || !isset($row['Create Table'])) {
			//throw new \Exception("Error getting table code, unknown output");
			return "/* Error getting table code, unknown output. */".PHP_EOL
				.PHP_EOL;
		}

		$create_table = $row['Create Table'] . ";";

		//prepare constraints
		$sql = "";
		$repeated_keys = array();

		//prepare fks constraints
		$fks_to_ignore = array();
		if ($foreign_keys_to_ignore)
		   	foreach ($foreign_keys_to_ignore as $fk) {
		   		if (isset($fk["child_column"]) && isset($fk["parent_table"]) && isset($fk["parent_column"]))
			   		$fks_to_ignore[ $fk["child_column"] ][ $fk["parent_table"] ][ $fk["parent_column"] ] = $fk;
			   	
			   	if (isset($fk["constraint_name"]))
			   		$fks_to_ignore[ $fk["constraint_name"] ] = $fk;
        		}
        	
		$stmt = $this->getShowForeignKeysStmt($table_name);
		$rows = $this->DBDumperHandler->getDBDriver()->getSQL($stmt);
		
		foreach ($rows as $r) {
			$cn = isset($r["constraint_name"]) ? $r["constraint_name"] : null;
			$cc = isset($r["child_column"]) ? $r["child_column"] : null;
			$pt = isset($r["parent_table"]) ? $r["parent_table"] : null;
			$pc = isset($r["parent_column"]) ? $r["parent_column"] : null;
			
			$on_delete = !empty($r["on_delete"]) ? " ON DELETE " . $r["on_delete"] : "";
			$on_update = !empty($r["on_update"]) ? " ON UPDATE " . $r["on_update"] : "";
			$replication = !empty($r["replication_code"]) ? $r["replication_code"] : "";
			//$check = !empty($r["not_trusted_code"]) ? $r["not_trusted_code"] : "";
			$check = null;
			
			$fk_sql = ($cn ? "CONSTRAINT [" . $cn . "] " : "") . " FOREIGN KEY ([" . $cc . "]) REFERENCES [" . $pt . "] ([" . $pc . "]) $on_delete $on_update $replication $check";
		   	
			if ( ($cn && !empty($fks_to_ignore[$cn])) || (
				$fks_to_ignore[$cc] && 
				$fks_to_ignore[$cc][$pt] && 
				$fks_to_ignore[$cc][$pt][$pc]
			)) {
				$fk = !empty($fks_to_ignore[$cn]) ? $fks_to_ignore[$cn] : $fks_to_ignore[$cc][$pt][$pc];
				$fk_table = isset($fk["parent_table"]) ? $fk["parent_table"] : null;
				$this->DBDumperHandler->setTableExtraSql($fk_table, "ALTER TABLE [$table_name] ADD $fk_sql;" . PHP_EOL);
			}
			else
				$sql .= "," . PHP_EOL . "    " . $fk_sql;

			$repeated_keys[] = $cn;
		}
    	   
		//prepare unique constraints
		$stmt = str_replace("\t", "", "SELECT c.name as 'constraint_name', col.name as 'col_name', t.name as 'table_name'
				FROM sys.objects t
				INNER JOIN sys.indexes i ON t.object_id = i.object_id
				INNER JOIN sys.key_constraints c ON i.object_id = c.parent_object_id AND i.index_id = c.unique_index_id
				INNER JOIN sys.index_columns ic ON ic.object_id = t.object_id AND ic.index_id = i.index_id
				INNER JOIN sys.columns col ON ic.object_id = col.object_id AND ic.column_id = col.column_id
				WHERE i.is_unique = 1 AND t.type = 'U' AND c.type = 'UQ' AND t.name='$table_name' AND t.is_ms_shipped=0;"); // AND SCHEMA_NAME(t.schema_id)='dbo'
		$rows = $this->DBDumperHandler->getDBDriver()->getSQL($stmt);
		
		foreach ($rows as $r) {
			$cn = isset($r["constraint_name"]) ? $r["constraint_name"] : null;
			
			$sql .= "," . PHP_EOL . "    " . ($cn ? "CONSTRAINT [" . $cn . "] " : "") . " UNIQUE ([" . $r["col_name"] . "])";

			$repeated_keys[] = $cn;
		}
        
		//prepare indexes
		$stmt = str_replace("\t", "", "SELECT 
    	   				i.name as 'index_name', 
    	   				i.type_desc 'index_type', 
    	   				col.name as 'col_name', 
    	   				t.name as 'table_name'
				FROM sys.objects t
				INNER JOIN sys.indexes i ON t.object_id = i.object_id
				INNER JOIN sys.index_columns ic ON ic.object_id = t.object_id AND ic.index_id = i.index_id
				INNER JOIN sys.columns col ON ic.object_id = col.object_id AND ic.column_id = col.column_id
				WHERE t.name='$table_name' AND t.is_ms_shipped=0 and i.is_primary_key=0 AND i.is_unique_constraint=0 AND i.is_unique=0"); // AND SCHEMA_NAME(t.schema_id)='dbo'
		$rows = $this->DBDumperHandler->getDBDriver()->getSQL($stmt);
		
		foreach ($rows as $r) {
			$in = isset($r["index_name"]) ? $r["index_name"] : null;
			
			if (!in_array($in, $repeated_keys)) {
				$it = isset($r["index_type"]) ? $r["index_type"] : null;
				$cn = isset($r["col_name"]) ? $r["col_name"] : null;
				
				$sql .= "," . PHP_EOL . "    INDEX " . ($in ? "[" . $in . "] " : "") . $it . " ([" . $cn . "])";
				//$create_table .= PHP_EOL . "CREATE " . $it . " INDEX " . ($in ? "[" . $in . "]" : "") . " ON [" . $table_name . "] ([" . $cn . "]);";
				
				$repeated_keys[] = $in;
			}
		}

		if ($sql) {
			$pos = strrpos($create_table, ")");
			$create_table = trim(substr($create_table, 0, $pos)) . $sql . PHP_EOL . substr($create_table, $pos);
		}

		$ret = $create_table.PHP_EOL.
		  PHP_EOL;
		return $ret;
	}
    
	public function createRecordsInsertStmt($table_name, $rows) {
		$db_dumper_settings = $this->DBDumperHandler->getDBDumperSettings();
		$CompressionHandler = $this->DBDumperHandler->getFileCompressionHandler();

		//bc mssql server doesn't allow manual inserts on auto-increment pks by default, we must execute this sql first
		$CompressionHandler->write("SET IDENTITY_INSERT [" . $table_name . "] ON;".PHP_EOL);
		
		// colNames is used to get the name of the columns when using complete-insert
		if (!empty($db_dumper_settings['complete-insert']))
			$attr_names = $this->DBDumperHandler->getTableAttributesNames($table_name);
		
		$line_size = 0;
		$only_once = true;
		$count = 0;
		$net_buffer_length = isset($db_dumper_settings['net_buffer_length']) ? $db_dumper_settings['net_buffer_length'] : null;
		
		foreach ($rows as $row) {
			$count++;
			$vals = $this->DBDumperHandler->prepareTableRowAttributes($table_name, $row);

			if ($only_once || empty($db_dumper_settings['extended-insert'])) {
				if (!empty($db_dumper_settings['complete-insert']))
					$str = "INSERT INTO " . $this->escapeTable($table_name) . " (" . implode(", ", $attr_names) . ") VALUES (".implode(",", $vals).")";
				else
					$str = "INSERT INTO " . $this->escapeTable($table_name) . " VALUES (".implode(",", $vals).")";

				$only_once = false;
			} 
			else
				$str = ",(".implode(",", $vals).")";
            
			$line_size += $CompressionHandler->write($str);

			if (empty($db_dumper_settings['extended-insert']) || $line_size > $net_buffer_length) {
				$only_once = true;
				$line_size = $CompressionHandler->write(";".PHP_EOL);
			}
		}

		if (!$only_once)
			$CompressionHandler->write(";".PHP_EOL);

		//sets the default value. Must set on and then off, otherwise we cannot set IDENTITY_INSERT to another table.
		$CompressionHandler->write("SET IDENTITY_INSERT [" . $table_name . "] OFF;".PHP_EOL);

		return $count;
	}
	
	public function getSqlStmtWithLimit($sql, $limit) {
		if (stripos($sql, " order by ") === false)
			$sql .= " ORDER BY (SELECT NULL)"; //OFFSET must have an "ORDER BY" statement otherwise it will give a sql error.

		return $sql . " OFFSET 0 ROWS FETCH NEXT $limit ROWS ONLY;";
	}
    
	public function createStandInTableForView($view_name, $inner_sql) {
		return "CREATE TABLE " . $this->escapeTable($view_name) . " (".
			PHP_EOL . $inner_sql . PHP_EOL . ");" . PHP_EOL;
	}

	public function getTableAttributeProperties($attr_type) {
		$db_column_numeric_types = $this->DBDumperHandler->getDBDriver()->getDBColumnNumericTypes();
		$db_column_blob_types = $this->DBDumperHandler->getDBDriver()->getDBColumnBlobTypes();
		$db_column_boolean_types = $this->DBDumperHandler->getDBDriver()->getDBColumnBooleanTypes();
		
		$attr_props = array();
		$attr_props['field'] = isset($attr_type['column_name']) ? $attr_type['column_name'] : null;
		$attr_props['type'] = isset($attr_type['data_type']) ? $attr_type['data_type'] : null;
		$attr_props['type_sql'] = isset($attr_type['data_type']) ? $attr_type['data_type'] : null;

		$length = !empty($attr_type["character_maximum_length"]) ? $attr_type["character_maximum_length"] : (isset($attr_type["numeric_precision"]) ? $attr_type["numeric_precision"] : null);

		if (is_numeric($length) && isset($attr_type["numeric_scale"]) && is_numeric($attr_type["numeric_scale"]))
			$length += $attr_type["numeric_scale"];
		
		$attr_props['is_nullable'] = !isset($attr_type['is_nullable']) || strtolower($attr_type['is_nullable']) != "no" ? false : true;
		$attr_props['is_nullable'] = isset($attr_type['is_nullable']) ? $attr_type['is_nullable'] : null;
		$attr_props['is_numeric'] = is_array($db_column_numeric_types) && in_array($attr_props['type'], $db_column_numeric_types);
		$attr_props['is_blob'] = is_array($db_column_blob_types) && in_array($attr_props['type'], $db_column_blob_types);
		$attr_props['is_boolean'] = is_array($db_column_boolean_types) && in_array($attr_props['type'], $db_column_boolean_types);
		
		return $attr_props;
	}

	public function getTableAttributesPropertiesBitHexFunc($attr_name) {
		//return "convert(bit, $attr_name)";
		return $attr_name;
	}
    
	public function getTableAttributesPropertiesBlobHexFunc($attr_name) {
		//return "convert(varbinary, $attr_name)";
		return $attr_name;
	}

	public function createView($row) {
		$ret = "";
		if (!isset($row['Create View'])) {
			//throw new \Exception("Error getting view structure, unknown output");
			return "/* Error getting view structure, unknown output */".PHP_EOL.
			PHP_EOL;
		}

		//The CREATE VIEW/FUNCTION/PROCEDURE/TRIGGER/EVENT must be first and only statement in a batch to be executed, so we add the keyword 'GO', bc this keyword will then be parsed from our MSSqlDB Driver class which will split the sql in batches. The same behaviour is used from the MS-SQL-Server client softwares.
		$ret .= "/* DROP VIEW IF EXISTS ".(isset($row['View']) ? $row['View'] : null)." */;".PHP_EOL.
			"GO".PHP_EOL.
			$row['Create View'].(substr($row['Create View'], -1) == ";" ? "" : ";").PHP_EOL.
			"GO".PHP_EOL.
			PHP_EOL;
		return $ret;
	}

	public function createTrigger($row) {
		$ret = "";
		if (!isset($row['SQL Original Statement'])) {
			//throw new \Exception("Error getting trigger code, unknown output");
			return "/* Error getting trigger code, unknown output */".PHP_EOL.
				PHP_EOL;
		}

		//The CREATE VIEW/FUNCTION/PROCEDURE/TRIGGER/EVENT must be first and only statement in a batch to be executed, so we add the keyword 'GO', bc this keyword will then be parsed from our MSSqlDB Driver class which will split the sql in batches. The same behaviour is used from the MS-SQL-Server client softwares.
		$ret .= "/* DROP TRIGGER IF EXISTS ".(isset($row['Trigger']) ? $row['Trigger'] : null)." */;".PHP_EOL.
			"GO".PHP_EOL.
			$row['SQL Original Statement'].(substr($row['SQL Original Statement'], -1) == ";" ? "" : ";").PHP_EOL.
			"GO".PHP_EOL.
			PHP_EOL;
		return $ret;
	}

	public function createProcedure($row) {
		$ret = "";
		if (!isset($row['Create Procedure'])) {
			//throw new \Exception("Error getting procedure code, unknown output.");
			return "/* Error getting procedure code, unknown output. */".PHP_EOL.
				PHP_EOL;
		}

		//The CREATE VIEW/FUNCTION/PROCEDURE/TRIGGER/EVENT must be first and only statement in a batch to be executed, so we add the keyword 'GO', bc this keyword will then be parsed from our MSSqlDB Driver class which will split the sql in batches. The same behaviour is used from the MS-SQL-Server client softwares.
		$ret .= "/* DROP PROCEDURE IF EXISTS ".(isset($row['Procedure']) ? $row['Procedure'] : null)." */;".PHP_EOL.
			"GO".PHP_EOL.
			$row['Create Procedure'].(substr($row['Create Procedure'], -1) == ";" ? "" : ";").PHP_EOL.
			"GO".PHP_EOL.
			PHP_EOL;
		return $ret;
	}

	public function createFunction($row) {
		$ret = "";

		if (!isset($row['Create Function'])) {
			//throw new \Exception("Error getting function code, unknown output.");
			return "/* Error getting function code, unknown output. */".PHP_EOL.
				PHP_EOL;
		}

		//The CREATE VIEW/FUNCTION/PROCEDURE/TRIGGER/EVENT must be first and only statement in a batch to be executed, so we add the keyword 'GO', bc this keyword will then be parsed from our MSSqlDB Driver class which will split the sql in batches. The same behaviour is used from the MS-SQL-Server client softwares.
		$ret .= "/* DROP FUNCTION IF EXISTS ".(isset($row['Function']) ? $row['Function'] : null)." */;".PHP_EOL.
			"GO".PHP_EOL.
			$row['Create Function'].(substr($row['Create Function'], -1) == ";" ? "" : ";").PHP_EOL.
			"GO".PHP_EOL.
			PHP_EOL;
		
		return $ret;
	}

	public function createEvent($row) {
		$ret = "";
		
		if (!isset($row['Create Event'])) {
			//throw new \Exception("Error getting event code, unknown output.");
			return "/* Error getting event code, unknown output. */".PHP_EOL.
				PHP_EOL;
		}

		//The CREATE VIEW/FUNCTION/PROCEDURE/TRIGGER/EVENT must be first and only statement in a batch to be executed, so we add the keyword 'GO', bc this keyword will then be parsed from our MSSqlDB Driver class which will split the sql in batches. The same behaviour is used from the MS-SQL-Server client softwares.
		$ret .= "/* DROP EVENT IF EXISTS ".(isset($row['Event']) ? $row['Event'] : null)." */;".PHP_EOL.
		  "GO".PHP_EOL.
		  $row['Create Event'].(substr($row['Create Event'], -1) == ";" ? "" : ";").PHP_EOL.
		  "GO".PHP_EOL.
		  PHP_EOL;
		
		return $ret;
	}

	public function backupParameters() {
		return PHP_EOL;
	}

	public function restoreParameters() {
		return PHP_EOL;
	}

	//disable all constraints and triggers
	public function startDisableConstraintsAndTriggersStmt($tables) {
		//Disables foreign keys and triggers for all tables
		$sql = "exec sp_msforeachtable \"ALTER TABLE ? NOCHECK CONSTRAINT all\";
exec sp_msforeachtable \"ALTER TABLE ? DISABLE TRIGGER  all\";".PHP_EOL;
	   
		//if drop too. This is redundant, but just in case we hv the code here to be executed.
		$db_dumper_settings = $this->DBDumperHandler->getDBDumperSettings();

		if ($tables && !empty($db_dumper_settings['add-drop-table'])) {
			$tables = is_array($tables) ? $tables : array($tables);
			//$schema = $args[1];
			$schema = null;

			//Drops all foreign keys constraints
			$sql .= "
DECLARE @drop_sql NVARCHAR(MAX) = '';

WHILE 1=1
BEGIN
  SELECT 
     @drop_sql = 'ALTER TABLE ' + isc.TABLE_NAME + ' DROP CONSTRAINT IF EXISTS ' + f.name + ';'
  FROM sys.foreign_keys AS f  
  INNER JOIN sys.foreign_key_columns AS fc ON f.object_id = fc.constraint_object_id
  INNER JOIN information_schema.COLUMNS AS isc ON isc.TABLE_CATALOG=DB_NAME() AND isc.TABLE_SCHEMA=SCHEMA_NAME(f.schema_id) AND OBJECT_ID(isc.TABLE_NAME)=f.parent_object_id AND isc.COLUMN_NAME=COL_NAME(fc.parent_object_id, fc.parent_column_id)
  WHERE " . ($schema ? "isc.TABLE_SCHEMA='$schema' AND " : "") . "isc.TABLE_NAME in ('" . implode("', '", $tables) . "')
  
  IF @@ROWCOUNT = 0 BREAK

  EXEC (@drop_sql);
END".PHP_EOL;
		}
	  
		return $sql;
	}
	
	//enables all constraints and triggers
	public function endDisableConstraintsAndTriggersStmt($tables) {
		//Re-enables foreign keys and triggers for all tables
		return "exec sp_msforeachtable @command1=\"print '?'\", @command2=\"ALTER TABLE ? WITH CHECK CHECK CONSTRAINT all\";
exec sp_msforeachtable @command1=\"print '?'\", @command2=\"ALTER TABLE ? ENABLE TRIGGER  all\";" . PHP_EOL;
	}
}
?>
