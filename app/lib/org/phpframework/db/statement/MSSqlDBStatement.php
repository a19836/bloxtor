<?php
trait MSSqlDBStatement { //must be "trait" and not "class" bc this code will serve to be extended by the MSSqlDB class, whcih already have the extended "DB" class. Note that PHP only allows 1 extended class.
	
	public static function getCreateDBStatement($db_name, $options = false) {
		$collation = null;
		
		if (!empty($options["encoding"])) {
			$collation = self::$db_charsets_to_collations[ $options["encoding"] ];
			$collation = $collation ? " COLLATE " . $collation : "";
		}
		
		//creates DB if doesnt exists
		return "IF DB_ID ('" . $db_name . "') IS NULL CREATE DATABASE " . $db_name . $collation;
	}

	public static function getDropDatabaseStatement($db_name, $options = false) {
		return "/* DROP DATABASE IF EXISTS [$db_name] */;";
	}
	
	public static function getSelectedDBStatement($options = false) {
		return "SELECT DB_NAME() AS db";
	}
	
	public static function getDBsStatement($options = false) {
		return "SELECT name, database_id FROM sys.databases";
	}
	
	public static function getTablesStatement($db_name = false, $options = false) {
		$schema = $options && !empty($options["schema"]) ? $options["schema"] : null;
		
		//$sql = "SELECT * FROM sysobjects WHERE xtype='U'";
		$sql ="SELECT 
				t.TABLE_NAME AS 'table_name', 
				t.TABLE_TYPE AS 'table_type',
				t.TABLE_SCHEMA AS 'table_schema'
			FROM information_schema.TABLES t 
			INNER JOIN sys.tables st ON SCHEMA_NAME(st.schema_id)=t.TABLE_SCHEMA AND st.name=t.TABLE_NAME AND st.is_ms_shipped=0 
			WHERE t.TABLE_TYPE='BASE TABLE' AND t.TABLE_CATALOG=" . ($db_name ? "'$db_name'" : "DB_NAME()") . ($schema ? " AND t.TABLE_SCHEMA='$schema'" : "") . "
			ORDER BY t.TABLE_NAME ASC";
		
		return $sql;
	}
	
	public static function getTableFieldsStatement($table, $db_name = false, $options = false) {
		$table_props = self::parseTableName($table, $options);
		$table = isset($table_props["name"]) ? $table_props["name"] : null;
		$schema = isset($table_props["schema"]) ? $table_props["schema"] : null;
		$database = isset($table_props["database"]) ? $table_props["database"] : null;
		
		$db_name = $db_name ? $db_name : $database;
		
		$sql = "SELECT 
				isc.COLUMN_NAME AS 'column_name',  
				isc.DATA_TYPE AS 'data_type', 
				isc.COLUMN_DEFAULT AS 'column_default',  
				isc.IS_NULLABLE AS 'is_nullable', 
				isc.CHARACTER_MAXIMUM_LENGTH AS 'character_maximum_length', 
				isc.NUMERIC_PRECISION AS 'numeric_precision', 
				isc.NUMERIC_SCALE AS 'numeric_scale', 
				isc.CHARACTER_SET_NAME AS 'character_set_name', 
				isc.COLLATION_NAME AS 'collation_name', 
				sep.value AS 'column_comment',
				col.is_identity,
				ic.seed_value,
				ic.increment_value,
				isccu_pk.COLUMN_NAME AS 'is_primary_key',
				isccu_uk.COLUMN_NAME AS 'is_unique_key'
			FROM information_schema.COLUMNS AS isc 
			INNER JOIN sys.columns AS col ON col.name = isc.COLUMN_NAME
			INNER JOIN sys.tables AS tab ON tab.object_id = col.object_id AND tab.name = isc.TABLE_NAME
			LEFT JOIN sys.identity_columns AS ic ON ic.object_id = col.object_id AND ic.name = col.name
			LEFT JOIN sys.extended_properties sep ON tab.object_id = sep.major_id AND col.column_id = sep.minor_id AND sep.name = 'MS_Description'
			LEFT JOIN information_schema.TABLE_CONSTRAINTS istc_pk ON istc_pk.TABLE_CATALOG = isc.TABLE_CATALOG AND istc_pk.TABLE_NAME = isc.TABLE_NAME AND istc_pk.CONSTRAINT_TYPE = 'PRIMARY KEY'
			LEFT JOIN information_schema.CONSTRAINT_COLUMN_USAGE isccu_pk ON isccu_pk.TABLE_CATALOG = isc.TABLE_CATALOG AND isccu_pk.TABLE_NAME = isc.TABLE_NAME AND isccu_pk.CONSTRAINT_NAME = istc_pk.CONSTRAINT_NAME AND isccu_pk.COLUMN_NAME = isc.COLUMN_NAME
			LEFT JOIN information_schema.TABLE_CONSTRAINTS istc_uk ON istc_uk.TABLE_CATALOG = isc.TABLE_CATALOG AND istc_uk.TABLE_NAME = isc.TABLE_NAME AND istc_uk.CONSTRAINT_TYPE = 'UNIQUE'
			LEFT JOIN information_schema.CONSTRAINT_COLUMN_USAGE isccu_uk ON isccu_uk.TABLE_CATALOG = isc.TABLE_CATALOG AND isccu_uk.TABLE_NAME = isc.TABLE_NAME AND isccu_uk.CONSTRAINT_NAME = istc_uk.CONSTRAINT_NAME AND isccu_uk.COLUMN_NAME = isc.COLUMN_NAME
			WHERE isc.TABLE_CATALOG=" . ($db_name ? "'$db_name'" : "DB_NAME()") . ($schema ? " AND isc.TABLE_SCHEMA='$schema'" : "") . " AND isc.TABLE_NAME='$table'
			ORDER BY isc.ORDINAL_POSITION ASC;";
		
		return $sql;
	}
	
	public static function getForeignKeysStatement($table, $db_name = false, $options = false) {
		$table_props = self::parseTableName($table, $options);
		$table = isset($table_props["name"]) ? $table_props["name"] : null;
		$schema = isset($table_props["schema"]) ? $table_props["schema"] : null;
		$database = isset($table_props["database"]) ? $table_props["database"] : null;
		
		$db_name = $db_name ? $db_name : $database;
		
		/*$sql = "SELECT 
				    isc.TABLE_SCHEMA AS 'schema',
				    isc.TABLE_NAME AS 'child_table',
				    col.name AS 'child_column',
				    OBJECT_NAME(fk.referenced_object_id) AS 'parent_table',
				    col2.name AS 'parent_column',
				    OBJECT_NAME(fk.constraint_object_id) AS 'constraint_name'
				FROM information_schema.COLUMNS AS isc
				INNER JOIN sys.columns AS col ON col.name = isc.COLUMN_NAME
				INNER JOIN sys.tables AS tab ON tab.object_id = col.object_id AND tab.name = isc.TABLE_NAME
				INNER JOIN sys.foreign_key_columns fk ON fk.parent_object_id = tab.object_id AND fk.parent_column_id = col.column_id
				INNER JOIN sys.columns AS col2 ON col2.column_id = fk.referenced_column_id and col2.object_id = fk.referenced_object_id
				WHERE isc.TABLE_CATALOG=" . ($db_name ? "'$db_name'" : "DB_NAME()") . ($schema ? " AND isc.TABLE_SCHEMA='$schema'" : "") . " AND isc.TABLE_NAME='$table'";*/
		$sql = "SELECT   
			    OBJECT_NAME(f.parent_object_id) AS 'child_table',
			    COL_NAME(fc.parent_object_id, fc.parent_column_id) AS 'child_column',
			    OBJECT_NAME (f.referenced_object_id) AS 'parent_table',
			    COL_NAME(fc.referenced_object_id, fc.referenced_column_id) AS 'parent_column',
			    f.name AS 'constraint_name',
			    f.delete_referential_action,
			    f.delete_referential_action_desc,
                   CASE f.delete_referential_action
                      WHEN 0 THEN 'NO ACTION '
                      WHEN 1 THEN 'CASCADE '
                      WHEN 2 THEN 'SET NULL '
                      ELSE 'SET DEFAULT '
                     END AS on_delete,
			    f.update_referential_action,
			    f.update_referential_action_desc,
                   CASE f.update_referential_action
                      WHEN 0 THEN 'NO ACTION '
                      WHEN 1 THEN 'CASCADE '
                      WHEN 2 THEN 'SET NULL '
                      ELSE 'SET DEFAULT '
                     END AS on_update,
			    f.is_disabled,
			    CASE f.is_disabled
                      WHEN 0 THEN ' WITH CHECK '
                      ELSE ' WITH NOCHECK '
                     END AS disabled_code,
			    f.is_not_trusted,
			    CASE f.is_not_trusted
                      WHEN 0 THEN ' WITH CHECK '
                      ELSE ' WITH NOCHECK '
                     END AS not_trusted_code,
			    f.is_not_for_replication, 
                   CASE f.is_not_for_replication
	                 WHEN 1 THEN ' NOT FOR REPLICATION '
	                 ELSE ''
                     END AS replication_code
			FROM sys.foreign_keys AS f  
			INNER JOIN sys.foreign_key_columns AS fc ON f.object_id = fc.constraint_object_id
			INNER JOIN information_schema.COLUMNS AS isc ON isc.TABLE_CATALOG=" . ($db_name ? "'$db_name'" : "DB_NAME()") . " AND isc.TABLE_SCHEMA=SCHEMA_NAME(f.schema_id) AND OBJECT_ID(isc.TABLE_NAME)=f.parent_object_id AND isc.COLUMN_NAME=COL_NAME(fc.parent_object_id, fc.parent_column_id)
			WHERE " . ($schema ? " isc.TABLE_SCHEMA='$schema' AND " : "") . "isc.TABLE_NAME='$table'";
		
		return $sql;
	}
	
	public static function getCreateTableStatement($table_data, $options = false) {
		$table_name = !empty($table_data["table_name"]) ? $table_data["table_name"] : (isset($table_data["name"]) ? $table_data["name"] : null); //Note that $table_name can contains be: "schema.name"
		$table_collation = !empty($table_data["table_collation"]) ? $table_data["table_collation"] : (isset($table_data["collation"]) ? $table_data["collation"] : null);
		$attributes = isset($table_data["attributes"]) ? $table_data["attributes"] : null;
		
		$table_collation = $table_collation ? " COLLATE=$table_collation" : "";
		
		$sql_table_name = self::getParsedTableEscapedSQL($table_name, $options);
		$sql = "CREATE TABLE $sql_table_name (\n";
		
		if (is_array($attributes)) {
			$pks_sql = array();
			
			foreach ($attributes as $attribute) 
				if (isset($attribute["name"])) {
					$name = $attribute["name"];
					$pk = isset($attribute["primary_key"]) ? $attribute["primary_key"] : null;
					$pk = $pk == "1" || strtolower($pk) == "true";
					
					if ($pk)
						$pks_sql[] = $name;
					
					$at_sql = self::getCreateTableAttributeStatement($attribute);
					$sql .= "  " . $at_sql . ",\n";
				}
			
			if ($pks_sql)
				$sql .= "  PRIMARY KEY ([" . implode("], [", $pks_sql) . "])";
			else
				$sql = substr($sql, 0, strlen($sql) - 2);//remove the last comma ','
		}
		
		//This are not being used for now, but I should change the diagram to use this feature too
		if (isset($table_data["unique_keys"]) && is_array($table_data["unique_keys"]))
			foreach ($table_data["unique_keys"] as $key) 
				if (!empty($key["attribute"])) {
					$type = !empty($key["type"]) ? "WITH " . $key["type"] : "";
					$attrs = is_array($key["attribute"]) ? $key["attribute"] : array($key["attribute"]);
					
					$sql .= ",   " . (!empty($key["name"]) ? "CONSTRAINT " . $key["name"] . " " : "") . "UNIQUE ([" . implode('", "', $attrs) . "]) $type";
				}
		
		//This are not being used for now, but I should change the diagram to use this feature too
		if (isset($table_data["foreign_keys"]) && is_array($table_data["foreign_keys"]))
			foreach ($table_data["foreign_keys"] as $key) 
				if (!empty($key["attribute"]) || !empty($key["child_column"])) {
					$on_delete = !empty($key["on_delete"]) ? "ON DELETE " . $key["on_delete"] : "";
					$on_update = !empty($key["on_update"]) ? "ON UPDATE " . $key["on_update"] : "";
					$attr_name = !empty($key["attribute"]) ? $key["attribute"] : $key["child_column"]; //$key can come from getForeignKeysStatement method
					$ref_attr_name = !empty($key["reference_attribute"]) ? $key["reference_attribute"] : (isset($key["parent_column"]) ? $key["parent_column"] : null);
					$ref_table_name = !empty($key["reference_table"]) ? $key["reference_table"] : (isset($key["parent_table"]) ? $key["parent_table"] : null);
					$constraint_name = !empty($key["name"]) ? $key["name"] : (isset($key["constraint_name"]) ? $key["constraint_name"] : null);
					
					$attrs = is_array($attr_name) ? $attr_name : array($attr_name);
					$ref_attrs = is_array($ref_attr_name) ? $ref_attr_name : array($ref_attr_name);
					$sql_reference_table = self::getParsedTableEscapedSQL($ref_table_name, $options);
					
					$sql .= ",\n   " . ($constraint_name ? "CONSTRAINT " . $constraint_name . " " : "") . " FOREIGN KEY ([" . implode('", "', $attrs) . "]) REFERENCES " . $sql_reference_table . " ([" . implode('", "', $ref_attrs) . "]) $on_delete $on_update";
				}
		
		//This are not being used for now, but I should change the diagram to use this feature too
		if (isset($table_data["index_keys"]) && is_array($table_data["index_keys"]))
			foreach ($table_data["index_keys"] as $key) 
				if (!empty($key["attribute"])) {
					$type = !empty($key["type"]) ? "WITH " . $key["type"] : "";
					$attrs = is_array($key["attribute"]) ? $key["attribute"] : array($key["attribute"]);
					
					$sql .= ",\n   INDEX " . (isset($key["name"]) ? $key["name"] : "") . " ([" . implode('", "', $attrs) . "]) $type";
				}
		
		$suffix = $options && !empty($options["suffix"]) ? $options["suffix"] : "";
		
		$sql .= "\n) $table_collation $suffix";
		
		return trim($sql);
	}
	
	public static function getCreateTableAttributeStatement($attribute_data, $options = false, &$parsed_data = array()) {
		if (!empty($attribute_data["name"])) {
			$options = $options ? $options : array();
			
			//Prepare attributes
			$name = trim($attribute_data["name"]);
			$type = isset($attribute_data["type"]) ? self::convertColumnTypeToDB($attribute_data["type"], $flags) : null;
			$length = isset($attribute_data["length"]) ? $attribute_data["length"] : null;
			$pk = isset($attribute_data["primary_key"]) ? $attribute_data["primary_key"] : null;
			$pk = $pk == "1" || strtolower($pk) == "true";
			$extra = isset($attribute_data["extra"]) ? $attribute_data["extra"] : null;
			$auto_increment = isset($attribute_data["auto_increment"]) ? $attribute_data["auto_increment"] : null;
			$auto_increment = $auto_increment == "1" || strtolower($auto_increment) == "true" || stripos($extra, "auto_increment") !== false; //this can change in the $flags bellow
			//$unsigned = isset($attribute_data["unsigned"]) ? $attribute_data["unsigned"] : null;
			//$unsigned = $unsigned == "1" || strtolower($unsigned) == "true"; //unsigned not supported by ms-sql-server
			$unique = isset($attribute_data["unique"]) ? $attribute_data["unique"] : null;
			$unique = $unique == "1" || strtolower($unique) == "true";
			$null = isset($attribute_data["null"]) ? $attribute_data["null"] : null;
			$null = $null == "1" || strtolower($null) == "true";
			$default = isset($attribute_data["default"]) ? $attribute_data["default"] : null;
			$default_type = isset($attribute_data["default_type"]) ? $attribute_data["default_type"] : null;
			//$charset = isset($attribute_data["charset"]) ? $attribute_data["charset"] : null; //not used in mssql server
			$collation = isset($attribute_data["collation"]) ? $attribute_data["collation"] : null;
			//$comment = isset($attribute_data["comment"]) ? $attribute_data["comment"] : null; //comment not supported by ms-sql-server
			
			if (!empty($flags))
				foreach ($flags as $k => $v)
					if ($k != "unsigned" && $k != "charset" && $k != "comment")
						eval("\$$k = \$v;"); //may change the $auto_increment to true
			
			if ($auto_increment || stripos($extra, "auto_increment") !== false || stripos($extra, "identity") !== false) {
				$extra = preg_replace("/(^|\s)auto_increment(\s|$)/i", " ", $extra);
				$auto_increment = true;
			}
			
			$length = !self::ignoreColumnTypeDBProp($type, "length") && (is_numeric($length) || preg_match("/^([0-9]+),([0-9]+)$/", $length)) ? $length : self::getMandatoryLengthForColumnType($type);
			$auto_increment = !self::ignoreColumnTypeDBProp($type, "auto_increment") ? $auto_increment : null;
			//$unsigned = !self::ignoreColumnTypeDBProp($type, "unsigned") ? $unsigned : false; //unsigned not supported by ms-sql-server
			$unique = !self::ignoreColumnTypeDBProp($type, "unique") && !$pk ? $unique : false;
			$null = !self::ignoreColumnTypeDBProp($type, "null") ? $null : null;
			$default = !self::ignoreColumnTypeDBProp($type, "default") ? $default : null;
			//$charset = !self::ignoreColumnTypeDBProp($type, "charset") ? $charset : null; //not used in mssql server
			$collation = !self::ignoreColumnTypeDBProp($type, "collation") ? $collation : null;
			//$comment = !self::ignoreColumnTypeDBProp($type, "comment") ? $comment : null; //comment not supported by ms-sql-server
			
			//Prepare default value
			$is_reserved_word = self::isAttributeValueReservedWord($default); //check if is a reserved word
			$contains_reserved_word = self::isReservedWordFunction($default); //check if contains a function
			$is_numeric_type = in_array($type, self::getDBColumnNumericTypes());
			$default = isset($default) && $is_numeric_type && !is_numeric($default) && !$is_reserved_word && !$contains_reserved_word ? null : $default; //remove default if numeric field and default is not numeric.
			
			if (!isset($default) && isset($null) && !$null && !$pk) {
				$default = self::getDefaultValueForColumnType($type); //if not null set a default value
				
				//Do it again bc the $default changed
				$is_reserved_word = self::isAttributeValueReservedWord($default); //check if is a reserved word. 
				$contains_reserved_word = self::isReservedWordFunction($default); //check if contains a function
			}
			//When an attribute is a DATE or a numeric and the default value is an empty string, the DB server gives an error, not inserting/updating the attribute with the new type, bc I cannot set a Date or numeric attribute with the default value: ''. This means we need to set the correct default the value, even if the attribute is NULL.
			else if (isset($default) && is_string($default) && !strlen($default) && !$pk) {
				$default = self::getDefaultValueForColumnType($type); //set a default value with the correct value in case is an empty string
				
				//Do it again bc the $default changed
				$is_reserved_word = self::isAttributeValueReservedWord($default); //check if is a reserved word. 
				$contains_reserved_word = self::isReservedWordFunction($default); //check if contains a function
			}
			
			$default_type = $default_type ? $default_type : (isset($default) && (is_numeric($default) || $is_reserved_word || $contains_reserved_word) ? "numeric" : "string");//This is for the cases where the DEFAULT value could be something like NOW() or CURRENT_TIMESTAMP, etc...
			
			//Prepare parsed values
			$parsed_data["type"] = $type;
			$parsed_data["primary_key"] = $pk;
			$parsed_data["length"] = $length;
			$parsed_data["auto_increment"] = $auto_increment;
			//$parsed_data["unsigned"] = $unsigned; //unsigned not supported by ms-sql-server
			$parsed_data["unique"] = $unique;
			$parsed_data["null"] = $null;
			$parsed_data["default"] = $default;
			//$parsed_data["charset"] = $charset; //not used in mssql server
			$parsed_data["collation"] = $collation;
			//$parsed_data["comment"] = $comment; //comment not supported by ms-sql-server
			
			//Prepare sql parameters
			$length = $length && empty($options["ignore_length"]) ? "($length)" : "";
			$auto_increment = $auto_increment && empty($options["ignore_auto_increment"]) && stripos($extra, "identity") === false ? "IDENTITY (1,1)" : "";
			$unsigned = "";//$unsigned && empty($options["ignore_unsigned"]) ? "unsigned" : ""; //unsigned not supported by ms-sql-server
			$unique = $unique && empty($options["ignore_unique"]) ? "UNIQUE" : "";
			$null = isset($null) && empty($options["ignore_null"]) ? ($null ? "NULL" : "NOT NULL") : "";
			$default = isset($default) && empty($options["ignore_default"]) && !$auto_increment ? "DEFAULT " . ($default_type == "string" ? "'$default'" : $default) : "";
			$charset = "";//!empty($charset) && empty($options["ignore_charset"]) ? "CHARSET $charset" : ""; //not used in mssql server
			$collation = !empty($collation) && empty($options["ignore_collation"]) ? "COLLATE $collation" : "";
			$comment = "";//!empty($comment) && empty($options["ignore_comment"]) ? "COMMENT '$comment'" : ""; //comment not supported by ms-sql-server
			$extra = empty($options["ignore_extra"]) ? $extra : "";
			
			$suffix = !empty($options["suffix"]) ? $options["suffix"] : "";
			
			$sql = trim(preg_replace("/[ ]+/", " ", "[$name] $type{$length} $unsigned $charset $collation $null $auto_increment $unique $default $extra $comment $suffix"));
			
			return $sql;
		}
	}
	
	public static function getRenameTableStatement($old_table, $new_table, $options = false) {
		$sql_old_table = self::getParsedTableEscapedSQL($old_table, $options);
		$sql_new_table = self::getParsedTableEscapedSQL($new_table, $options);
		$suffix = $options && !empty($options["suffix"]) ? $options["suffix"] : "";
		
		return "EXEC sp_rename $sql_old_table, $sql_new_table $suffix";
	}
	
	public static function getDropTableStatement($table, $options = false) {
		$sql_table = self::getParsedTableEscapedSQL($table, $options);
		$suffix = $options && !empty($options["suffix"]) ? $options["suffix"] : "";
		
		return "DROP TABLE IF EXISTS $sql_table $suffix";
	}

	public static function getDropTableCascadeStatement($table, $options = false) {
		return self::getDropTableStatement($table, $options);
	}
	
	public static function getAddTableAttributeStatement($table, $attribute_data, $options = false) {
		$sql_table = self::getParsedTableEscapedSQL($table, $options);
		$suffix = $options && !empty($options["suffix"]) ? $options["suffix"] : "";
		$sql = self::getCreateTableAttributeStatement($attribute_data);
		
		return "ALTER TABLE $sql_table ADD $sql $suffix";
	}
	
	public static function getModifyTableAttributeStatement($table, $attribute_data, $options = false) {
		$sql_table = self::getParsedTableEscapedSQL($table, $options);
		$suffix = $options && !empty($options["suffix"]) ? $options["suffix"] : "";
		
		$table_props = self::parseTableName($table, $options);
		$table = isset($table_props["name"]) ? $table_props["name"] : null;
		$schema = isset($table_props["schema"]) ? $table_props["schema"] : null;
		
		//get the sql without default value, bc mssql doesn't support default values on alter column
		$attr_sql = self::getCreateTableAttributeStatement($attribute_data, array("ignore_default" => true, "ignore_unique" => true, "ignore_auto_increment" => true), $parsed_data);
		
		if (!empty($parsed_data["primary_key"]) && !empty($parsed_data["extra"]))
			$attr_sql = self::getCreateTableAttributeStatement($attribute_data, array("ignore_default" => true, "ignore_unique" => true, "ignore_auto_increment" => true, "ignore_extra" => true), $parsed_data);
		
		//update or remove default value
		$name = isset($attribute_data["name"]) ? $attribute_data["name"] : null;
		$default = null;
		$has_default = isset($parsed_data["default"]) && !empty($attribute_data["has_default"]); //only if has_default is set and is true, otherwise the default is to remove.
		$sql_default_value_definition = null;
		$rand = rand();
		//print_r($attribute_data);die();
		
		if ($has_default) {
			//'' is how you escape the ' in sql. ' must be escaped to be included in the @add_sql.
			$is_reserved_word = self::isAttributeValueReservedWord($parsed_data["default"]); //check if is a reserved word
			$contains_reserved_word = self::isReservedWordFunction($parsed_data["default"]); //check if contains a function
			$default = is_numeric($parsed_data["default"]) || $is_reserved_word || $contains_reserved_word ? $parsed_data["default"] : "''" . $parsed_data["default"] . "''"; 
			$sql_default_value_definition = is_numeric($parsed_data["default"]) ? "((" . $parsed_data["default"] . "))" : "(''" . $parsed_data["default"] . "'')";
		}
		
		//prepare default values, by changing the correspondent constraints. note that the default values in mssql are in contraints, not being possible change the default value through the "ALTER TABLE ALTER COLUMN" command.
		$sql = "
			--update default constraint with new default value, but only if different
			DECLARE @drop_sql NVARCHAR(MAX) = '';
			DECLARE @add_sql NVARCHAR(MAX) = '';
			DECLARE @add_sql_active TINYINT = " . (isset($default) ? 1 : 0) . ";
			DECLARE @is_default_different TINYINT = 1;
			
			SELECT TOP 1 
				@drop_sql = 'ALTER TABLE $sql_table DROP CONSTRAINT ' + dc.name + ';', 
				@add_sql = 'ALTER TABLE $sql_table ADD CONSTRAINT ' + dc.name + ' DEFAULT $default FOR [$name];',
				@is_default_different = CAST(
				   CASE
				        WHEN dc.definition != '$sql_default_value_definition'
				           THEN 1
				        ELSE 0
				   END AS bit)
			FROM sys.default_constraints dc 
			INNER JOIN sys.columns c ON c.default_object_id = dc.object_id
			INNER JOIN sys.objects o ON o.object_id = dc.parent_object_id
			WHERE dc.parent_object_id = OBJECT_ID('$table')" . ($schema ? " AND SCHEMA_NAME(o.schema_id)='$schema'" : "") . " AND c.name = '$name';
			
			IF @add_sql = ''
				SELECT @add_sql = 'ALTER TABLE $sql_table ADD CONSTRAINT df__{$table}__{$name}__pf$rand DEFAULT $default FOR [$name];';
			
			IF (@drop_sql != '' AND @is_default_different = 1)
				EXEC sp_executeSQL @drop_sql;
			";
		
		//drop unique contraint 
		/*In the future add to the where clause: schema_name(t.schema_id)=$schema
		SELECT
				t.name AS 'table', 
				col.name AS 'column',
				c.name AS constraint_name,
				i.name AS index_name
		*/
		$sql .= "
			--drop unique key if exists
			WHILE 1=1
			BEGIN
				SELECT 
					@drop_sql = 'ALTER TABLE $sql_table DROP CONSTRAINT ' + c.name + ';'
				FROM sys.objects t
				INNER JOIN sys.indexes i ON t.object_id = i.object_id
				INNER JOIN sys.key_constraints c ON i.object_id = c.parent_object_id AND i.index_id = c.unique_index_id
				INNER JOIN sys.index_columns ic ON ic.object_id = t.object_id AND ic.index_id = i.index_id
				INNER JOIN sys.columns col ON ic.object_id = col.object_id AND ic.column_id = col.column_id AND col.name = '$name'
				WHERE i.is_unique = 1 AND t.type = 'U' AND c.type = 'UQ' AND t.name='$table'" . ($schema ? " AND SCHEMA_NAME(t.schema_id)='$schema'" : "") . " AND t.is_ms_shipped <> 1;
				
				IF @@ROWCOUNT = 0 BREAK
				
				EXEC (@drop_sql);
			END
			";
		
		//If attribute is NOT NULL, update all null values with the default value, before it change it to NOT NULL. This is, if we have an attribute which is NULL and we are trying to change it to NOT NULL, and if this attribute contains any record with a NULL value, mssql will not let me modify this attribute to NOT NULL, giving a sql error. So we need to update first all records with NULL values.
		if (empty($parsed_data["primary_key"]) && isset($parsed_data["null"]) && !$parsed_data["null"] && isset($parsed_data["default"])) { //$has_default cannot be here bc it includes the $attribute_data["has_default"] and in this case we want the $parsed_data["default"] which contains the real default value set by the getCreateTableAttributeStatement method.
			$is_reserved_word = self::isAttributeValueReservedWord($parsed_data["default"]); //check if is a reserved word
			$contains_reserved_word = self::isReservedWordFunction($parsed_data["default"]); //check if contains a function
			$default = is_numeric($parsed_data["default"]) || $is_reserved_word || $contains_reserved_word ? $parsed_data["default"] : "'" . $parsed_data["default"] . "'"; 
			
			$sql .= "
			--if attribute is NOT NULL, update all null values with default values, before it change it to NOT NULL
			UPDATE $sql_table SET [$name] = $default WHERE [$name] IS NULL;
			";
		}
		
		//alter column with new definition
		$sql .= "
			--update column
			ALTER TABLE $sql_table ALTER COLUMN $attr_sql $suffix;
			";
		
		//add default constraint. Must be after we change the column
		$sql .= "
			IF (@add_sql_active = 1 AND @is_default_different = 1)
				EXEC sp_executeSQL @add_sql;
			";
		
		//add unique contraint. Must be after we change the column
		if (empty($parsed_data["primary_key"]) && !empty($parsed_data["unique"]))
			$sql .= "
			--add unique key
				ALTER TABLE $sql_table ADD CONSTRAINT uk__{$table}__{$name}__pf$rand UNIQUE ($name);
			";
		
		if (!empty($parsed_data["auto_increment"])) {
			//mssql doesn't support the alter columns for IDENTITY (1,1). More info in https://social.msdn.microsoft.com/Forums/sqlserver/en-US/04d69ee6-d4f5-4f8f-a115-d89f7bcbc032/how-to-alter-column-to-identity11?forum=transactsql
		}
		
		//echo "<pre>".$sql;die();
		return $sql;
	}
	
	public static function getRenameTableAttributeStatement($table, $old_attribute, $new_attribute, $options = false) {
		$sql_table = self::getParsedTableEscapedSQL($table, $options);
		$suffix = $options && !empty($options["suffix"]) ? $options["suffix"] : "";
		
		return "EXEC sp_rename '$sql_table.$old_attribute', '$new_attribute', 'COLUMN' $suffix";
	}
	
	public static function getDropTableAttributeStatement($table, $attribute, $options = false) {
		$sql_table = self::getParsedTableEscapedSQL($table, $options);
		$suffix = $options && !empty($options["suffix"]) ? $options["suffix"] : "";
		
		$table_props = self::parseTableName($table, $options);
		$table = isset($table_props["name"]) ? $table_props["name"] : null;
		$schema = isset($table_props["schema"]) ? $table_props["schema"] : null;
		
		//mssql doesn't let remove a column if exists contraints, so we must delete them first.
		//prepare default values, by changing the correspondent constraints. note that the default values in mssql are in contraints, not being possible change the default value through the "ALTER TABLE ALTER COLUMN" command.
		$sql = "
			DECLARE @drop_sql NVARCHAR(MAX) = '';
			
			--drop default constraint if exists
			SELECT TOP 1 
				@drop_sql = 'ALTER TABLE $sql_table DROP CONSTRAINT ' + dc.name + ';'
			FROM sys.default_constraints dc 
			INNER JOIN sys.columns c ON c.default_object_id = dc.object_id
			INNER JOIN sys.objects o ON o.object_id = dc.parent_object_id
			WHERE dc.parent_object_id = OBJECT_ID('$table')" . ($schema ? " AND SCHEMA_NAME(o.schema_id)='$schema'" : "") . " AND c.name = '$attribute';
			
			IF (@drop_sql != '')
				EXEC sp_executeSQL @drop_sql;
			
			--drop unique key if exists
			DECLARE @drop_sql2 NVARCHAR(MAX) = '';
			
			WHILE 1=1
			BEGIN
				SELECT 
					@drop_sql2 = 'ALTER TABLE $sql_table DROP CONSTRAINT ' + c.name + ';'
				FROM sys.objects t
				INNER JOIN sys.indexes i ON t.object_id = i.object_id
				INNER JOIN sys.key_constraints c ON i.object_id = c.parent_object_id AND i.index_id = c.unique_index_id
				INNER JOIN sys.index_columns ic ON ic.object_id = t.object_id AND ic.index_id = i.index_id
				INNER JOIN sys.columns col ON ic.object_id = col.object_id AND ic.column_id = col.column_id AND col.name = '$attribute'
				WHERE i.is_unique = 1 AND t.type = 'U' AND (c.type = 'UQ' OR i.type = 1 OR i.type = 2)" . ($schema ? " AND SCHEMA_NAME(t.schema_id)='$schema'" : "") . " AND t.name='$table' AND t.is_ms_shipped <> 1;
				
				IF @@ROWCOUNT = 0 BREAK
				
				EXEC (@drop_sql2);
			END
			
			ALTER TABLE $sql_table DROP COLUMN [$attribute] $suffix;";
		
		return $sql;
	}
	
	public static function getAddTablePrimaryKeysStatement($table, $attributes, $options = false) {
		$sql_table = self::getParsedTableEscapedSQL($table, $options);
		$suffix = $options && !empty($options["suffix"]) ? $options["suffix"] : "";
		$constraint_name = str_replace(" ", "_", strtolower($table)) . "_pk";
		$attributes = is_array($attributes) ? $attributes : array($attributes);
		
		$attributes_name = array();
		foreach ($attributes as $attr) {
			if (is_array($attr)) {
				if (!empty($attr["name"]))
					$attributes_name[] = $attr["name"];
			}
			else if ($attr)
				$attributes_name[] = $attr;
		}
		
		return "ALTER TABLE $sql_table ADD CONSTRAINT $constraint_name PRIMARY KEY ([" . implode("], [", $attributes_name) . "]) $suffix";
	}
	
	public static function getDropTablePrimaryKeysStatement($table, $options = false) {
		$sql_table = self::getParsedTableEscapedSQL($table, $options);
		$suffix = $options && !empty($options["suffix"]) ? $options["suffix"] : "";
		
		$table_props = self::parseTableName($table, $options);
		$table = isset($table_props["name"]) ? $table_props["name"] : null;
		$schema = isset($table_props["schema"]) ? $table_props["schema"] : null;
		
		return "DECLARE @sql NVARCHAR(MAX);
			   SELECT @sql = 'ALTER TABLE $sql_table DROP CONSTRAINT ' + name + ' $suffix;'
			    FROM sys.key_constraints
			    WHERE [type] = 'PK'
			    AND [parent_object_id] = OBJECT_ID('$table')" . ($schema ? " AND SCHEMA_NAME(t.schema_id)='$schema'" : "") . ";
			   EXEC sp_executeSQL @sql;";
	}
	
	//used in CMSDeploymentHandler
	public static function getAddTableForeignKeyStatement($table, $fk, $options = false) {
		if ($fk && (!empty($fk["attribute"]) || !empty($fk["child_column"]))) {
			$sql_table = self::getParsedTableEscapedSQL($table, $options);
			$suffix = $options && !empty($options["suffix"]) ? $options["suffix"] : "";
			
			$on_delete = !empty($fk["on_delete"]) ? "ON DELETE " . $fk["on_delete"] : "";
			$on_update = !empty($fk["on_update"]) ? "ON UPDATE " . $fk["on_update"] : "";
			$attr_name = !empty($fk["attribute"]) ? $fk["attribute"] : (isset($fk["child_column"]) ? $fk["child_column"] : null);
			$ref_attr_name = !empty($fk["reference_attribute"]) ? $fk["reference_attribute"] : (isset($fk["parent_column"]) ? $fk["parent_column"] : null);
			$ref_table_name = !empty($fk["reference_table"]) ? $fk["reference_table"] : (isset($fk["parent_table"]) ? $fk["parent_table"] : null);
			$constraint_name = !empty($fk["name"]) ? $fk["name"] : (isset($fk["constraint_name"]) ? $fk["constraint_name"] : null);
			$replication = !empty($fk["replication_code"]) ? $fk["replication_code"] : "";
		   	//$check = !empty($fk["not_trusted_code"]) ? $fk["not_trusted_code"] : "";
		   	$check = null;
		   	
			$attrs = is_array($attr_name) ? $attr_name : array($attr_name);
			$ref_attrs = is_array($ref_attr_name) ? $ref_attr_name : array($ref_attr_name);
			$sql_reference_table = self::getParsedTableEscapedSQL($ref_table_name, $options);
			
			return "ALTER TABLE $sql_table ADD " . ($constraint_name ? "CONSTRAINT [" . $constraint_name . "] " : "") . " FOREIGN KEY ([" . implode('], [', $attrs) . "]) REFERENCES " . $sql_reference_table . " ([" . implode('], [', $ref_attrs) . "]) $on_delete $on_update $replication $check $suffix";
		}
	}
	
	//used in CMSDeploymentHandler
	public static function getDropTableForeignKeysStatement($table, $options = false) {
		$table_props = self::parseTableName($table, $options);
		$table = isset($table_props["name"]) ? $table_props["name"] : null;
		$schema = isset($table_props["schema"]) ? $table_props["schema"] : null;
		$suffix = $options && !empty($options["suffix"]) ? $options["suffix"] : "";
		
		return "WHILE 1=1
BEGIN
  SELECT 
     @drop_sql = 'ALTER TABLE ' + isc.TABLE_NAME + ' DROP CONSTRAINT IF EXISTS ' + f.name + '$suffix;'
  FROM sys.foreign_keys AS f  
  INNER JOIN sys.foreign_key_columns AS fc ON f.object_id = fc.constraint_object_id
  INNER JOIN information_schema.COLUMNS AS isc ON isc.TABLE_CATALOG=DB_NAME() AND isc.TABLE_SCHEMA=SCHEMA_NAME(f.schema_id) AND OBJECT_ID(isc.TABLE_NAME)=f.parent_object_id AND isc.COLUMN_NAME=COL_NAME(fc.parent_object_id, fc.parent_column_id)
  WHERE " . ($schema ? "isc.TABLE_SCHEMA='$schema' AND " : "") . "isc.TABLE_NAME='$table'
  
  IF @@ROWCOUNT = 0 BREAK

  EXEC (@drop_sql);
END";
	}

	public static function getDropTableForeignConstraintStatement($table, $constraint_name, $options = false) {
		$sql_table = self::getParsedTableEscapedSQL($table, $options);
		
		$table_props = self::parseTableName($table, $options);
		$table = isset($table_props["name"]) ? $table_props["name"] : null;
		$schema = isset($table_props["schema"]) ? $table_props["schema"] : null;
		
		return "IF (EXISTS (SELECT * FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_CATALOG=DB_NAME()" . ($schema ? " AND TABLE_SCHEMA = '$schema'" : "") . " AND TABLE_NAME='$table' AND TABLE_TYPE='BASE TABLE'))
BEGIN
  ALTER TABLE $sql_table DROP CONSTRAINT IF EXISTS [$constraint_name];
END;";
	}
	
	//used in CMSModuleInstallationHandler
	public static function getAddTableIndexStatement($table, $attributes, $options = false) {
		$sql_table = self::getParsedTableEscapedSQL($table, $options);
		$suffix = $options && !empty($options["suffix"]) ? $options["suffix"] : "";
		$attributes = is_array($attributes) ? $attributes : array($attributes);
		
		$table_props = self::parseTableName($table, $options);
		$table = isset($table_props["name"]) ? $table_props["name"] : null;
		$index_name = "idx__{$table}__" . implode("_", $attributes) . "__pf" . rand();
		
		return "CREATE INDEX $index_name ON $sql_table ([" . implode("], [", $attributes) . "]) $suffix";
	}
	
	public static function getLoadTableDataFromFileStatement($file_path, $table, $options = false) {
		//https://docs.microsoft.com/en-us/sql/t-sql/statements/bulk-insert-transact-sql?view=sql-server-ver15
		//https://docs.microsoft.com/en-us/sql/relational-databases/import-export/bulk-import-and-export-of-data-sql-server?view=sql-server-ver15
		$sql_table = self::getParsedTableEscapedSQL($table, $options);
		$suffix = $options && !empty($options["suffix"]) ? $options["suffix"] : "";
		$fields_delimiter = !empty($options["fields_delimiter"]) ? $options["fields_delimiter"] : "\t";
		$lines_delimiter = !empty($options["lines_delimiter"]) ? $options["lines_delimiter"] : "\r\n";
		
		return "BULK INSERT $sql_table FROM '$file_path' WITH (FIELDTERMINATOR = '$fields_delimiter', ROWTERMINATOR = '$lines_delimiter' $suffix)";
	}

	//https://www.c-sharpcorner.com/UploadFile/67b45a/how-to-generate-a-create-table-script-for-an-existing-table/
	public static function getShowCreateTableStatement($table, $options = false) {
		$table_props = self::parseTableName($table, $options);
		$table = isset($table_props["name"]) ? $table_props["name"] : null;
		$schema = isset($table_props["schema"]) ? $table_props["schema"] : null;
		
		return "DECLARE  
	@object_name SYSNAME, 
	@object_id INT, 
	@SQL NVARCHAR(MAX);

SELECT 
	@object_name = '[' + OBJECT_SCHEMA_NAME(o.[object_id]) + '].[' + OBJECT_NAME([object_id]) + ']', 
	@object_id = [object_id]
FROM (SELECT [object_id] = OBJECT_ID('" . ($schema ? "$schema." : "") . "$table', 'U')) o;

SELECT @SQL = 'CREATE TABLE ' + @object_name + ' (' + '\n' + STUFF((  
    SELECT '\n' + '    , [' + c.name + '] ' +   
        CASE WHEN c.is_computed = 1  
            THEN 'AS ' + OBJECT_DEFINITION(c.[object_id], c.column_id)  
            ELSE   
                CASE WHEN c.system_type_id != c.user_type_id   
                    THEN '[' + SCHEMA_NAME(tp.[schema_id]) + '].[' + tp.name + ']'   
                    ELSE '[' + UPPER(tp.name) + ']'   
                END  +   
                CASE   
                    WHEN tp.name IN ('varchar', 'char', 'varbinary', 'binary')  
                        THEN '(' + CASE WHEN c.max_length = -1   
                                        THEN 'MAX'   
                                        ELSE CAST(c.max_length AS VARCHAR(5))   
                                    END + ')'  
                    WHEN tp.name IN ('nvarchar', 'nchar')  
                        THEN '(' + CASE WHEN c.max_length = -1   
                                        THEN 'MAX'   
                                        ELSE CAST(c.max_length / 2 AS VARCHAR(5))   
                                    END + ')'  
                    WHEN tp.name IN ('datetime2', 'time2', 'datetimeoffset')   
                        THEN '(' + CAST(c.scale AS VARCHAR(5)) + ')'  
                    WHEN tp.name = 'decimal'  
                        THEN '(' + CAST(c.[precision] AS VARCHAR(5)) + ',' + CAST(c.scale AS VARCHAR(5)) + ')'  
                    ELSE ''  
                END +  
                CASE WHEN c.collation_name IS NOT NULL AND c.system_type_id = c.user_type_id   
                    THEN ' COLLATE ' + c.collation_name  
                    ELSE ''  
                END +  
                CASE WHEN c.is_nullable = 1   
                    THEN ' NULL'  
                    ELSE ' NOT NULL'  
                END +  
                CASE WHEN c.default_object_id != 0   
                    THEN ' CONSTRAINT [' + OBJECT_NAME(c.default_object_id) + ']' +   
                         ' DEFAULT ' + OBJECT_DEFINITION(c.default_object_id)  
                    ELSE ''  
                END +   
                CASE WHEN cc.[object_id] IS NOT NULL   
                    THEN ' CONSTRAINT [' + cc.name + '] CHECK ' + cc.[definition]  
                    ELSE ''  
                END +  
                CASE WHEN c.is_identity = 1   
                    THEN ' IDENTITY(' + CAST(IDENTITYPROPERTY(c.[object_id], 'SeedValue') AS VARCHAR(5)) + ',' +   
                                    CAST(IDENTITYPROPERTY(c.[object_id], 'IncrementValue') AS VARCHAR(5)) + ')'   
                    ELSE ''   
                END   
        END  
    FROM sys.columns c WITH(NOLOCK)  
    JOIN sys.types tp WITH(NOLOCK) ON c.user_type_id = tp.user_type_id  
    LEFT JOIN sys.check_constraints cc WITH(NOLOCK)   
         ON c.[object_id] = cc.parent_object_id   
        AND cc.parent_column_id = c.column_id  
    WHERE c.[object_id] = @object_id  
    ORDER BY c.column_id  
    FOR XML PATH(''), TYPE).value('.', 'NVARCHAR(MAX)'), 1, 7, '      ') +   
    ISNULL((SELECT '  
    , CONSTRAINT [' + i.name + '] PRIMARY KEY ' +   
    CASE WHEN i.index_id = 1   
        THEN 'CLUSTERED'   
        ELSE 'NONCLUSTERED'   
    END +' (' + (  
    SELECT STUFF(CAST((  
        SELECT ', [' + COL_NAME(ic.[object_id], ic.column_id) + ']' +  
                CASE WHEN ic.is_descending_key = 1  
                    THEN ' DESC'  
                    ELSE ''  
                END  
        FROM sys.index_columns ic WITH(NOLOCK)  
        WHERE i.[object_id] = ic.[object_id]  
            AND i.index_id = ic.index_id  
        FOR XML PATH(N''), TYPE) AS NVARCHAR(MAX)), 1, 2, '')) + ')'  
    FROM sys.indexes i WITH(NOLOCK)  
    WHERE i.[object_id] = @object_id  
        AND i.is_primary_key = 1), '') + '\n' + ')';  
  
SELECT '$table' as 'Table', REPLACE(@SQL, '\n    , ', ',\n    ') as 'Create Table'";
	}

	//https://www.sqlservertutorial.net/sql-server-views/sql-server-get-view-information/
	public static function getShowCreateViewStatement($view, $options = false) {
		$table_props = self::parseTableName($view, $options);
		$view = isset($table_props["name"]) ? $table_props["name"] : null;
		
		return "SELECT '$view' as 'View', OBJECT_DEFINITION(OBJECT_ID('$view')) as 'Create View'";
	}

	//https://www.sqlservertutorial.net/sql-server-triggers/sql-server-view-trigger-definition/
	public static function getShowCreateTriggerStatement($trigger, $options = false) {
		$table_props = self::parseTableName($trigger, $options);
		$trigger = isset($table_props["name"]) ? $table_props["name"] : null;
		
		return "SELECT '$trigger' as 'Trigger', OBJECT_DEFINITION(OBJECT_ID('$trigger')) as 'SQL Original Statement'";
	}

	//https://database.guide/3-ways-to-list-all-stored-procedures-in-a-sql-server-database/
	public static function getShowCreateProcedureStatement($procedure, $options = false) {
		$table_props = self::parseTableName($procedure, $options);
		$procedure = isset($table_props["name"]) ? $table_props["name"] : null;
		
		return "SELECT ROUTINE_NAME as 'Procedure', ROUTINE_DEFINITION as 'Create Procedure' FROM INFORMATION_SCHEMA.ROUTINES WHERE ROUTINE_TYPE = 'PROCEDURE' and ROUTINE_NAME = '$procedure'";
	}

	//https://www.mytecbits.com/microsoft/sql-server/find-all-user-defined-functions-udf
	public static function getShowCreateFunctionStatement($function, $options = false) {
		$table_props = self::parseTableName($function, $options);
		$function = isset($table_props["name"]) ? $table_props["name"] : null;
		
		return "SELECT ROUTINE_NAME as 'Function', ROUTINE_DEFINITION as 'Create Function' FROM INFORMATION_SCHEMA.ROUTINES WHERE ROUTINE_TYPE = 'FUNCTION' and ROUTINE_NAME = '$function'";
	}

	public static function getShowCreateEventStatement($event, $options = false) {
		$table_props = self::parseTableName($event, $options);
		$event = isset($table_props["name"]) ? $table_props["name"] : null;
		
		return "SELECT '$event' as 'Event', OBJECT_DEFINITION(OBJECT_ID('$event')) as 'Create Event'";
	}

	public static function getShowTablesStatement($db_name, $options = false) {
		return str_replace("\n", " ", self::getTablesStatement($db_name, $options));
		/*return "SELECT ".
			"  t.TABLE_NAME as 'table_name', ".
			"  t.TABLE_SCHEMA as 'table_schema' ".
			"FROM INFORMATION_SCHEMA.TABLES t ".
			"INNER JOIN sys.tables st ON SCHEMA_NAME(st.schema_id)=t.TABLE_SCHEMA AND st.name=t.TABLE_NAME AND st.is_ms_shipped=0 ".
			"WHERE t.TABLE_TYPE='BASE TABLE' AND t.TABLE_CATALOG='$db_name'";*/
	}

	public static function getShowViewsStatement($db_name, $options = false) {
		return "SELECT v.TABLE_NAME AS 'view_name' ".
			"FROM INFORMATION_SCHEMA.VIEWS v ".
			"INNER JOIN sys.objects o ON SCHEMA_NAME(o.schema_id)=v.TABLE_SCHEMA AND o.name=v.TABLE_NAME AND o.type='V' AND o.is_ms_shipped=0 ".
			"WHERE v.TABLE_CATALOG='$db_name'";
	}

	public static function getShowTriggersStatement($db_name, $options = false) {
		return "SELECT trg.name AS 'Trigger', tab.TABLE_NAME AS 'table_name' ".
			"FROM sys.triggers trg ".
			"INNER JOIN INFORMATION_SCHEMA.TABLES tab ON tab.TABLE_NAME=OBJECT_NAME(trg.parent_id) AND tab.TABLE_SCHEMA=OBJECT_SCHEMA_NAME(trg.parent_id) ".
			"WHERE tab.TABLE_CATALOG='$db_name' and trg.is_ms_shipped=0";
	}

	public static function getShowTableColumnsStatement($table, $db_name = false, $options = false) {
		return str_replace("\n", " ", self::getTableFieldsStatement($table, $db_name, $options));
		/*return str_replace("\n", " ", "SELECT 
				isc.COLUMN_NAME as 'column_name',  
				isc.DATA_TYPE as 'data_type', 
				isc.COLUMN_DEFAULT as 'column_default',  
				isc.IS_NULLABLE as 'is_nullable', 
				isc.CHARACTER_MAXIMUM_LENGTH as 'character_maximum_length', 
				isc.NUMERIC_PRECISION as 'numeric_precision', 
				isc.NUMERIC_SCALE as 'numeric_scale', 
				isc.CHARACTER_SET_NAME as 'character_set_name', 
				isc.COLLATION_NAME as 'collation_name', 
				sep.value as 'column_comment',
				col.is_identity,
				ic.seed_value,
				ic.increment_value,
				isccu_pk.COLUMN_NAME as 'is_primary_key',
				isccu_uk.COLUMN_NAME as 'is_unique_key'
			FROM information_schema.COLUMNS as isc 
			INNER JOIN sys.columns as col ON col.name = isc.COLUMN_NAME
			INNER JOIN sys.tables as tab ON tab.object_id = col.object_id AND tab.name = isc.TABLE_NAME
			LEFT JOIN sys.identity_columns as ic ON ic.object_id = col.object_id AND ic.name = col.name
			LEFT JOIN sys.extended_properties sep ON tab.object_id = sep.major_id AND col.column_id = sep.minor_id AND sep.name = 'MS_Description'
			LEFT JOIN information_schema.TABLE_CONSTRAINTS istc_pk ON istc_pk.TABLE_CATALOG = isc.TABLE_CATALOG AND istc_pk.TABLE_NAME = isc.TABLE_NAME AND istc_pk.CONSTRAINT_TYPE = 'PRIMARY KEY'
			LEFT JOIN information_schema.CONSTRAINT_COLUMN_USAGE isccu_pk ON isccu_pk.TABLE_CATALOG = isc.TABLE_CATALOG AND isccu_pk.TABLE_NAME = isc.TABLE_NAME AND isccu_pk.CONSTRAINT_NAME = istc_pk.CONSTRAINT_NAME AND isccu_pk.COLUMN_NAME = isc.COLUMN_NAME
			LEFT JOIN information_schema.TABLE_CONSTRAINTS istc_uk ON istc_uk.TABLE_CATALOG = isc.TABLE_CATALOG AND istc_uk.TABLE_NAME = isc.TABLE_NAME AND istc_uk.CONSTRAINT_TYPE = 'UNIQUE'
			LEFT JOIN information_schema.CONSTRAINT_COLUMN_USAGE isccu_uk ON isccu_uk.TABLE_CATALOG = isc.TABLE_CATALOG AND isccu_uk.TABLE_NAME = isc.TABLE_NAME AND isccu_uk.CONSTRAINT_NAME = istc_uk.CONSTRAINT_NAME AND isccu_uk.COLUMN_NAME = isc.COLUMN_NAME
			WHERE isc.TABLE_CATALOG=DB_NAME() AND isc.TABLE_NAME='$table'
			ORDER BY isc.ORDINAL_POSITION ASC;"); // AND  isc.TABLE_SCHEMA='$schema'*/
	}

	public static function getShowForeignKeysStatement($table, $db_name = false, $options = false) {
		return str_replace("\t", "", self::getForeignKeysStatement($table, $db_name, $options));
		/*return str_replace("\t", "", "SELECT   
			    OBJECT_NAME(f.parent_object_id) AS 'child_table',
			    COL_NAME(fc.parent_object_id, fc.parent_column_id) AS 'child_column',
			    OBJECT_NAME (f.referenced_object_id) AS 'parent_table',
			    COL_NAME(fc.referenced_object_id, fc.referenced_column_id) AS 'parent_column',
			    f.name AS 'constraint_name',
			    f.delete_referential_action,
			    f.delete_referential_action_desc,
			    CASE f.delete_referential_action
				  WHEN 0 THEN 'NO ACTION '
				  WHEN 1 THEN 'CASCADE '
				  WHEN 2 THEN 'SET NULL '
				  ELSE 'SET DEFAULT '
				 END AS on_delete,
			    f.update_referential_action,
			    f.update_referential_action_desc,
			    CASE f.update_referential_action
				  WHEN 0 THEN 'NO ACTION '
				  WHEN 1 THEN 'CASCADE '
				  WHEN 2 THEN 'SET NULL '
				  ELSE 'SET DEFAULT '
				 END AS on_update,
			    f.is_disabled,
			    CASE f.is_disabled
				  WHEN 0 THEN ' WITH CHECK '
				  ELSE ' WITH NOCHECK '
				 END AS disabled_code,
			    f.is_not_trusted,
			    CASE f.is_not_trusted
				  WHEN 0 THEN ' WITH CHECK '
				  ELSE ' WITH NOCHECK '
				 END AS not_trusted_code,
			    f.is_not_for_replication, 
			    CASE f.is_not_for_replication
				  WHEN 1 THEN ' NOT FOR REPLICATION '
				  ELSE ''
				 END AS replication_code
			FROM sys.foreign_keys AS f  
			INNER JOIN sys.foreign_key_columns AS fc ON f.object_id = fc.constraint_object_id
			INNER JOIN information_schema.COLUMNS as isc ON isc.TABLE_CATALOG=DB_NAME() AND isc.TABLE_SCHEMA=SCHEMA_NAME(f.schema_id) AND OBJECT_ID(isc.TABLE_NAME)=f.parent_object_id AND isc.COLUMN_NAME=COL_NAME(fc.parent_object_id, fc.parent_column_id)
			WHERE isc.TABLE_NAME='$table'"); // AND  isc.TABLE_SCHEMA='$schema'*/ 
	}

	public static function getShowProceduresStatement($db_name, $options = false) {
		return "SELECT r.ROUTINE_NAME as 'procedure_name' ".
		  "FROM INFORMATION_SCHEMA.ROUTINES r ".
		  "INNER JOIN sys.objects o ON  SCHEMA_NAME(o.schema_id)=r.ROUTINE_SCHEMA AND o.name=r.ROUTINE_NAME AND o.type='P' AND o.is_ms_shipped=0 ".
		  "WHERE r.ROUTINE_TYPE='PROCEDURE' and r.ROUTINE_CATALOG='$db_name'";
	}

	public static function getShowFunctionsStatement($db_name, $options = false) {
		return "SELECT r.ROUTINE_NAME as 'function_name' ".
		  "FROM INFORMATION_SCHEMA.ROUTINES r ".
		  "INNER JOIN sys.objects o ON  SCHEMA_NAME(o.schema_id)=r.ROUTINE_SCHEMA AND o.name=r.ROUTINE_NAME AND o.type in ('AF', 'FN', 'FS', 'FT', 'IF', 'TF') AND o.is_ms_shipped=0 ".
		  "WHERE r.ROUTINE_TYPE='FUNCTION' and r.ROUTINE_CATALOG='$db_name'";
	}

	public static function getShowEventsStatement($db_name, $options = false) {
		return "SELECT o.name AS 'event_name' ".
		  "FROM sys.events e ".
		  "INNER JOIN sys.objects o ON o.object_id=e.object_id AND o.is_ms_shipped=0 ".
		  "INNER JOIN INFORMATION_SCHEMA.TABLES tab ON tab.TABLE_NAME=OBJECT_NAME(o.parent_object_id) AND tab.TABLE_SCHEMA=OBJECT_SCHEMA_NAME(o.parent_object_id)".
		  "WHERE tab.TABLE_CATALOG='$db_name'";
	}

	//https://www.red-gate.com/simple-talk/sql/t-sql-programming/questions-about-t-sql-transaction-isolation-levels-you-were-too-shy-to-ask/
	//https://docs.microsoft.com/en-us/sql/t-sql/statements/set-transaction-isolation-level-transact-sql?view=sql-server-ver15
	public static function getSetupTransactionStatement($options = false) {
		return "SET TRANSACTION ISOLATION LEVEL REPEATABLE READ";
	}

	public static function getStartTransactionStatement($options = false) {
		return "BEGIN TRANSACTION ".
	  "/* [transaction_name] WITH MARK [description] */";
	}

	public static function getCommitTransactionStatement($options = false) {
		return "COMMIT TRANSACTION";
	}

	//https://techyaz.com/sql-server/t-sql/how-to-disable-auto-commit-in-sql-server/
	//https://stackoverflow.com/questions/1090240/how-do-you-set-autocommit-in-an-sql-server-session
	public static function getStartDisableAutocommitStatement($options = false) {
		return "SET IMPLICIT_TRANSACTIONS ON;";
	}

	public static function getEndDisableAutocommitStatement($options = false) {
		return "SET IMPLICIT_TRANSACTIONS OFF;";
	}

	public static function getStartLockTableWriteStatement($table, $options = false) {
		return null;
	}

	public static function getStartLockTableReadStatement($table, $options = false) {
		return null;
	}

	public static function getEndLockTableStatement($options = false) {
		//return "UNLOCK TABLES;";
		return null;
	}

	public static function getStartDisableKeysStatement($table, $options = false) {
		return null;
	}

	public static function getEndDisableKeysStatement($table, $options = false) {
		return null;
	}

	public static function getDropTriggerStatement($trigger, $options = false) {
		$sql_trigger = self::getParsedTableEscapedSQL($trigger, $options);
		return "DROP TRIGGER IF EXISTS $sql_trigger;";
	}

	public static function getDropProcedureStatement($procedure, $options = false) {
		$sql_procedure = self::getParsedTableEscapedSQL($procedure, $options);
		return "DROP PROCEDURE IF EXISTS $sql_procedure;";
	}

	public static function getDropFunctionStatement($function, $options = false) {
		$sql_function = self::getParsedTableEscapedSQL($function, $options);
		return "DROP FUNCTION IF EXISTS $sql_function;";
	}

	public static function getDropEventStatement($event, $options = false) {
		$sql_event = self::getParsedTableEscapedSQL($event, $options);
		return "DROP EVENT IF EXISTS $sql_event;";
	}

	public static function getDropViewStatement($view, $options = false) {
		$sql_view = self::getParsedTableEscapedSQL($view, $options);
		return "DROP VIEW IF EXISTS $sql_view;";
	}
}
?>
