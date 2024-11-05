<?php
trait PostgresDBStatement { //must be "trait" and not "class" bc this code will serve to be extended by the PostgresDB class, whcih already have the extended "DB" class. Note that PHP only allows 1 extended class.
	
	public static function getCreateDBStatement($db_name, $options = false) {
		$sql = "CREATE DATABASE \"" . $db_name . "\"";
		
		if (!empty($options["encoding"]) || !empty($options["collation"])) {
			$sql .= " WITH";
			
			if (!empty($options["encoding"]))
				$sql .= " ENCODING '" . strtoupper($options["encoding"]) . "'";
			
			if (!empty($options["collation"]))
				$sql .= " LC_COLLATE '" . strtoupper($options["collation"]) . "'";
		}
		
		return $sql;
	}

	public static function getDropDatabaseStatement($db_name, $options = false) {
		return "/*!40000 DROP DATABASE IF EXISTS \"$db_name\" */;";
	}
	
	public static function getSelectedDBStatement($options = false) {
		return "SELECT current_database() AS db";
	}
	
	public static function getDBsStatement($options = false) {
		return "SELECT datname AS name FROM pg_database";
	}
	
	public static function getTablesStatement($db_name = false, $options = false) {
		$schema = $options && !empty($options["schema"]) ? $options["schema"] : null;
		
		$sql = "SELECT 
				t.table_name AS \"table_name\",
				t.table_schema AS \"table_schema\"
			FROM information_schema.tables t
			WHERE t.table_catalog=" . ($db_name ? "'$db_name'" : "current_database()") . " AND " . ($schema ? "t.table_schema='$schema'" : "t.table_schema!='pg_catalog' AND t.table_schema!='information_schema'") . " AND t.table_type='BASE TABLE'
			ORDER BY t.table_name ASC"; //table_schemas: pg_catalog and information_schema are postgres default schemas
		/*$sql = "SELECT DISTINCT C.relname AS table_name
			FROM pg_class C, pg_namespace N, pg_attribute A, pg_type T 
			WHERE (C.relkind='r') 
				AND (N.oid=C.relnamespace) 
				AND (A.attrelid=C.oid) 
				AND (A.atttypid=T.oid) 
				AND (A.attnum>0) 
				AND (NOT A.attisdropped) 
				AND (N.nspname ILIKE 'public')
			ORDER BY C.relname ASC";*/
		/*$sql = "SELECT tablename AS table_name
			FROM pg_catalog.pg_tables
			WHERE schemaname!='pg_catalog' AND schemaname!='information_schema'
			ORDER BY tablename ASC";*/
		
		return $sql;
	}
	
	public static function getTableFieldsStatement($table, $db_name = false, $options = false) {
		$table_props = self::parseTableName($table, $options);
		$table = isset($table_props["name"]) ? $table_props["name"] : null;
		$schema = isset($table_props["schema"]) ? $table_props["schema"] : null; //schema = 'public'
		$database = isset($table_props["database"]) ? $table_props["database"] : null;
		
		$db_name = $db_name ? $db_name : $database;
		
		//This query was tested successfully with different users with different permissions. It was tested with Postgres DB version 9.3.5.	
		//unique_constraint_name is very important bc the pkt.indisunique is not enough if an attribute is NOT a PK but is UNIQUE.
		$sql = "
		SELECT 
			isc.column_name, 
			isc.column_default, 
			isc.is_nullable, 
			isc.data_type, 
			isc.character_maximum_length, 
			isc.numeric_precision, 
			isc.character_set_name, 
			isc.character_set_schema, 
			isc.collation_name, 
			isc.collation_schema, 
			ARRAY_AGG(pkt.indisprimary) AS is_primary,
			ARRAY_AGG(pkt.indisunique) AS is_unique,
			checkconstraint.check_constraint_name,
			checkconstraint.check_constraint_value,
			uk.constraint_name AS unique_constraint_name,
			dt.column_comment
		FROM information_schema.columns isc 
		LEFT JOIN (
			SELECT               
			    pg_attribute.attname AS attname, 
			    format_type(pg_attribute.atttypid, pg_attribute.atttypmod) AS type,
	    		    pg_index.indisunique, 
	    		    pg_index.indisprimary, 
	    		    pg_index.indisexclusion, 
	    		    pg_index.indimmediate, 
	    		    pg_index.indisclustered, 
	    		    pg_index.indisvalid, 
	    		    pg_index.indcheckxmin, 
	    		    pg_index.indisready, 
	    		    pg_index.indislive
			 FROM pg_index, pg_class, pg_attribute, pg_namespace 
			 WHERE 
			     pg_class.oid = '$table'::regclass AND 
			     indrelid = pg_class.oid AND 
			     " . ($schema ? "nspname = '$schema'" : "nspname NOT LIKE 'pg%' AND nspname <> 'information_schema'") . " AND 
			     pg_class.relnamespace = pg_namespace.oid AND 
			     pg_attribute.attrelid = pg_class.oid AND 
			     pg_attribute.attnum = any(pg_index.indkey)
		) pkt ON pkt.attname = isc.column_name and pkt.type = isc.data_type
		LEFT JOIN (
			SELECT 
			  column_name AS cn, 
			  consrc AS check_constraint_value,
			  constraint_name AS check_constraint_name
			FROM information_schema.constraint_column_usage
			INNER JOIN pg_constraint on conname = constraint_name and contype='c'
			WHERE table_catalog = " . ($db_name ? "'$db_name'" : "current_database()") . " and table_name = '$table'
		) checkconstraint ON checkconstraint.cn = isc.column_name
		LEFT JOIN (
			SELECT 
			  c.column_name cn, 
			  pgd.description column_comment
			FROM pg_catalog.pg_statio_all_tables AS st
			  INNER JOIN pg_catalog.pg_description pgd on (pgd.objoid=st.relid)
			  INNER JOIN information_schema.columns c on (pgd.objsubid=c.ordinal_position and c.table_schema=st.schemaname and c.table_name=st.relname)
			WHERE " . ($schema ? "st.schemaname='$schema'": "st.schemaname NOT LIKE 'pg%' AND st.schemaname <> 'information_schema'") . " and st.relname='$table'
		) dt ON dt.cn = isc.column_name
		LEFT JOIN information_schema.table_constraints tc ON tc.table_schema=isc.table_schema AND tc.table_name=isc.table_name AND constraint_type = 'UNIQUE'
		LEFT JOIN (
			SELECT c.conname AS constraint_name, a.attname AS column, '$table' AS table
			FROM pg_constraint c
			INNER JOIN  (
				SELECT attname, array_agg(attnum::int) AS attkey
				FROM pg_attribute
				WHERE attrelid = '$table'::regclass
				GROUP BY attname
			) a ON c.conkey::int[] <@ a.attkey AND c.conkey::int[] @> a.attkey
			WHERE c.contype='u' AND c.conrelid='$table'::regclass
		) uk ON uk.table=isc.table_name AND uk.column=isc.column_name
		WHERE isc.table_catalog=" . ($db_name ? "'$db_name'" : "current_database()") . " and " . ($schema ? "isc.table_schema = '$schema'" : "isc.table_schema NOT LIKE 'pg%' AND isc.table_schema <> 'information_schema'") . " and isc.table_name='$table'
		GROUP BY 
			isc.column_name, 
			isc.column_default, 
			isc.is_nullable, 
			isc.data_type, 
			isc.character_maximum_length, 
			isc.numeric_precision, 
			isc.character_set_name, 
			isc.character_set_schema, 
			isc.collation_name, 
			isc.collation_schema, 
			checkconstraint.check_constraint_name,
			checkconstraint.check_constraint_value,
			uk.constraint_name,
			dt.column_comment";
		//ORDER BY isc.ordinal_position";
		
		return $sql;
	}
	
	public static function getForeignKeysStatement($table, $db_name = false, $options = false) {
		$table_props = self::parseTableName($table, $options);
		$table = isset($table_props["name"]) ? $table_props["name"] : null;
		$schema = isset($table_props["schema"]) ? $table_props["schema"] : null; //schema = 'public'
		$database = isset($table_props["database"]) ? $table_props["database"] : null;
		
		$db_name = $db_name ? $db_name : $database;
		
		//This query was tested successfully with different users with different permissions. It was tested with Postgres DB version 9.3.5.	
		$sql = "SELECT 
			  col.table_catalog AS catalog, 
			  col.table_schema AS schema, 
			  col.table_name AS child_table, 
			  --col.column_name AS child_column,
			  att2.attname AS child_column, 
			  cl.relname AS parent_table, 
			  att.attname AS parent_column,
			  tc.constraint_name AS constraint_name,
			  con.confupdtype,
			  CASE con.confupdtype
		            WHEN 'a' THEN 'NO ACTION '
		            WHEN 'r' THEN 'RESTRICT '
		            WHEN 'c' THEN 'CASCADE '
		            WHEN 'n' THEN 'SET NULL '
		            ELSE 'SET DEFAULT '
		           END AS on_update,
			  con.confdeltype,
			  CASE con.confdeltype
		            WHEN 'a' THEN 'NO ACTION '
		            WHEN 'r' THEN 'RESTRICT '
		            WHEN 'c' THEN 'CASCADE '
		            WHEN 'n' THEN 'SET NULL '
		            ELSE 'SET DEFAULT '
		           END AS on_delete,
			  pg_get_constraintdef(pgc.oid, true) AS constraint_def
			FROM (
				SELECT 
				  unnest(con1.conkey) AS parent, 
				  unnest(con1.confkey) AS child, 
				  con1.confrelid, 
				  con1.conrelid,
				  ns.nspname,
				  cl.relname,
				  con1.confupdtype,
				  con1.confdeltype
				FROM pg_class cl
				INNER JOIN pg_namespace ns ON cl.relnamespace = ns.oid" . ($schema ? " AND ns.nspname='$schema'" : "") . "
				INNER JOIN pg_constraint con1 ON con1.conrelid = cl.oid AND con1.contype = 'f'
				WHERE cl.relname = '$table'
			) con
			INNER JOIN pg_attribute att ON att.attrelid = con.confrelid AND att.attnum = con.child
			INNER JOIN pg_class cl ON cl.oid = con.confrelid
			INNER JOIN pg_attribute att2 ON att2.attrelid = con.conrelid AND att2.attnum = con.parent
			INNER JOIN pg_namespace ns ON cl.relnamespace = ns.oid AND ns.nspname=con.nspname
			INNER JOIN information_schema.table_constraints tc ON ns.nspname = tc.constraint_schema AND tc.constraint_schema=con.nspname AND tc.table_name=con.relname AND tc.constraint_type = 'FOREIGN KEY'
			INNER JOIN pg_constraint pgc ON pgc.conname = tc.constraint_name AND pgc.connamespace = ns.oid AND pgc.conrelid = con.conrelid AND pgc.contype = 'f'
			INNER JOIN information_schema.columns col ON col.table_schema = tc.table_schema AND col.table_name = tc.table_name AND col.ordinal_position=ANY(pgc.conkey);";
		
		return $sql;
	}
	
	public static function getCreateTableStatement($table_data, $options = false) {
		$table_name = !empty($table_data["table_name"]) ? $table_data["table_name"] : (isset($table_data["name"]) ? $table_data["name"] : null); //Note that $table_name can contains be: "schema.name"
		//$table_charset = !empty($table_data["table_charset"]) ? $table_data["table_charset"] : (isset($table_data["charset"]) ? $table_data["charset"] : null); //postgres doesn't set the charset for table
		//$table_collation = !empty($table_data["table_collation"]) ? $table_data["table_collation"] : (isset($table_data["collation"]) ? $table_data["collation"] : null); //postgres doesn't set the collation for table
		$table_storage_engine = !empty($table_data["table_storage_engine"]) ? $table_data["table_storage_engine"] : (isset($table_data["engine"]) ? $table_data["engine"] : null);
		$attributes = isset($table_data["attributes"]) ? $table_data["attributes"] : null;
		
		$enc = "";
		if (!empty($table_charset) || !empty($table_collation) || !empty($table_storage_engine)) {
			$enc = "WITH ";
			$enc .= !empty($table_storage_engine) ? "($table_storage_engine) " : "";
			//$enc .= !empty($table_charset) ? "ENCODING '$table_charset' " : ""; //postgres doesn't set the charset for table
			//$enc .= !empty($table_collation) ? "LC_COLLATE '$table_collation' " : ""; //postgres doesn't set the collation for table
		}
		
		$sql_table_name = self::getParsedTableEscapedSQL($table_name, $options);
		$sql = "CREATE TABLE $sql_table_name (\n";
		
		if (is_array($attributes)) {
			$pks_sql = array();
			
			foreach ($attributes as $attribute) 
				if (!empty($attribute["name"])) {
					$name = $attribute["name"];
					$pk = isset($attribute["primary_key"]) ? $attribute["primary_key"] : null;
					$pk = $pk == "1" || strtolower($pk) == "true";
					
					if ($pk)
						$pks_sql[] = $name;
					
					$at_sql = self::getCreateTableAttributeStatement($attribute);
					$sql .= "  " . $at_sql . ",\n";
				}
			
			if ($pks_sql) {
				$sql .= "  PRIMARY KEY (\"" . implode("\", \"", $pks_sql) . "\")";
			}
			else {
				$sql = substr($sql, 0, strlen($sql) - 2);//remove the last comma ','
			}
		}
		
		//This are not being used for now, but I should change the diagram to use this feature too
		if (isset($table_data["unique_keys"]) && is_array($table_data["unique_keys"]))
			foreach ($table_data["unique_keys"] as $key) 
				if (!empty($key["attribute"])) {
					$attrs = is_array($key["attribute"]) ? $key["attribute"] : array($key["attribute"]);
					
					$sql .= ",   " . (!empty($key["name"]) ? "CONSTRAINT " . $key["name"] . " " : "") . " UNIQUE (\"" . implode('", "', $attrs) . "\")";
				}
		
		//This are not being used for now, but I should change the diagram to use this feature too
		if (isset($table_data["foreign_keys"]) && is_array($table_data["foreign_keys"]))
			foreach ($table_data["foreign_keys"] as $key) 
				if (!empty($key["attribute"]) || !empty($key["child_column"])) {
					$on_delete = !empty($key["on_delete"]) ? "ON DELETE " . $key["on_delete"] : "";
					$on_update = !empty($key["on_update"]) ? "ON UPDATE " . $key["on_update"] : "";
					$attr_name = !empty($key["attribute"]) ? $key["attribute"] : (isset($key["child_column"]) ? $key["child_column"] : null); //$key can come from getForeignKeysStatement method
					$ref_attr_name = !empty($key["reference_attribute"]) ? $key["reference_attribute"] : (isset($key["parent_column"]) ? $key["parent_column"] : null);
					$ref_table_name = !empty($key["reference_table"]) ? $key["reference_table"] : (isset($key["parent_table"]) ? $key["parent_table"] : null);
					$constraint_name = !empty($key["name"]) ? $key["name"] : (isset($key["constraint_name"]) ? $key["constraint_name"] : null);
					
					$attrs = is_array($attr_name) ? $attr_name : array($attr_name);
					$ref_attrs = is_array($ref_attr_name) ? $ref_attr_name : array($ref_attr_name);
					$sql_reference_table = self::getParsedTableEscapedSQL($ref_table_name, $options);
					
					$sql .= ",\n   " . ($constraint_name ? "CONSTRAINT " . $constraint_name . " " : "") . " FOREIGN KEY (\"" . implode('", "', $attrs) . "\") REFERENCES " . $sql_reference_table . " (\"" . implode('", "', $ref_attrs) . "\") $on_delete $on_update";
				}
		
		$suffix = $options && !empty($options["suffix"]) ? $options["suffix"] : "";
		
		$sql .= "\n) $enc $suffix";
		
		//This are not being used for now, but I should change the diagram to use this feature too
		if (isset($table_data["index_keys"]) && is_array($table_data["index_keys"]))
			foreach ($table_data["index_keys"] as $key) 
				if (!empty($key["attribute"])) {
					$type = !empty($key["type"]) ? "USING " . $key["type"] : "";
					$attrs = is_array($key["attribute"]) ? $key["attribute"] : array($key["attribute"]);
					
					$sql .= "\nCREATE INDEX " . (isset($key["name"]) ? $key["name"] : "") . " ON $sql_table_name $type (\"" . implode('", "', $attrs) . "\")";
				}
		
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
			$auto_increment = isset($attribute_data["auto_increment"]) ? $attribute_data["auto_increment"] : null;
			$auto_increment = $auto_increment == "1" || strtolower($auto_increment) == "true"; //this can change in the $flags bellow
			$unsigned = isset($attribute_data["unsigned"]) ? $attribute_data["unsigned"] : null;
			$unsigned = $unsigned == "1" || strtolower($unsigned) == "true";
			$unique = isset($attribute_data["unique"]) ? $attribute_data["unique"] : null;
			$unique = $unique == "1" || strtolower($unique) == "true";
			$null = isset($attribute_data["null"]) ? $attribute_data["null"] : null;
			$null = $null == "1" || strtolower($null) == "true";
			$default = isset($attribute_data["default"]) ? $attribute_data["default"] : null;
			$default_type = isset($attribute_data["default_type"]) ? $attribute_data["default_type"] : null;
			$extra = isset($attribute_data["extra"]) ? $attribute_data["extra"] : null;
			//$charset = isset($attribute_data["charset"]) ? $attribute_data["charset"] : null; //Not used in pgsql server
			$collation = isset($attribute_data["collation"]) ? $attribute_data["collation"] : null;
			//$comment = isset($attribute_data["comment"]) ? $attribute_data["comment"] : null; //not used in pgsql server
			
			if (!empty($flags))
				foreach ($flags as $k => $v)
					if ($k != "charset" && $k != "comment")
						eval("\$$k = \$v;"); //may change the $auto_increment to true
			
			if ($auto_increment || stripos($extra, "auto_increment") !== false) {
				$extra = preg_replace("/(^|\s)auto_increment(\s|$)/i", " ", $extra);
				$auto_increment = true;
				
				if (empty($options["do_not_convert_to_serial"]))
					$type = $type == "bigint" ? "bigserial" : ($type == "smallint" ? "smallserial" : "serial");
			}
			
			$length = !self::ignoreColumnTypeDBProp($type, "length") && (is_numeric($length) || preg_match("/^([0-9]+),([0-9]+)$/", $length)) ? $length : self::getMandatoryLengthForColumnType($type);
			$unsigned = !self::ignoreColumnTypeDBProp($type, "unsigned") ? $unsigned : false;
			$unique = !self::ignoreColumnTypeDBProp($type, "unique") ? $unique : false;
			$null = !self::ignoreColumnTypeDBProp($type, "null") ? $null : null;
			$default = !self::ignoreColumnTypeDBProp($type, "default") ? $default : null;
			//$charset = !self::ignoreColumnTypeDBProp($type, "charset") ? $charset : null; //not used in pgsql server
			$collation = !self::ignoreColumnTypeDBProp($type, "collation") ? $collation : null;
			//$comment = !self::ignoreColumnTypeDBProp($type, "comment") ? $comment : null; //not used in pgsql server
			
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
			$parsed_data["unsigned"] = $unsigned;
			$parsed_data["unique"] = $unique;
			$parsed_data["null"] = $null;
			$parsed_data["default"] = $default;
			//$parsed_data["charset"] = $charset; //not used in pgsql server
			$parsed_data["collation"] = $collation;
			//$parsed_data["comment"] = $comment; //not used in pgsql server
			
			//Prepare sql parameters
			$length = $length && empty($options["ignore_length"]) ? "($length)" : "";
			$unsigned = $unsigned && empty($options["ignore_unsigned"]) ? "CHECK (\"$name\" > 0)" : "";
			$unique = $unique && empty($options["ignore_unique"]) && !$pk ? "UNIQUE" : "";
			$null = isset($null) && empty($options["ignore_null"]) ? ($null ? "NULL" : "NOT NULL") : "";
			$default = isset($default) && empty($options["ignore_default"]) && !$auto_increment ? "DEFAULT " . ($default_type == "string" ? "'$default'" : $default) : "";
			$charset = "";//!empty($charset) && empty($options["ignore_charset"]) ? "ENCODING '$charset'" : ""; //not used in pgsql server
			$collation = !empty($collation) && empty($options["ignore_collation"]) ? "COLLATE '$collation'" : "";
			$comment = "";//!empty($comment) && empty($options["ignore_comment"]) ? "COMMENT '$comment'" : ""; //not used in pgsql server
			$extra = empty($options["ignore_extra"]) ? $extra : "";
			
			$suffix = !empty($options["suffix"]) ? $options["suffix"] : "";
			
			//remove repeated CHECK constaint, this is, if extra already contains a CHECK sql, ignore unsigned.
			if ($unsigned && preg_match("/CHECK\s*\(+\s*\"?$name\"?\s*>/i", $extra))
				$unsigned = "";
			
			return trim(preg_replace("/[ ]+/", " ", "\"$name\" $type{$length} $unique $null $charset $collation $default $comment $extra $unsigned $suffix"));
		}
	}
	
	public static function getRenameTableStatement($old_table, $new_table, $options = false) {
		$sql_old_table = self::getParsedTableEscapedSQL($old_table, $options);
		$sql_new_table = self::getParsedTableEscapedSQL($new_table, $options);
		$suffix = $options && !empty($options["suffix"]) ? $options["suffix"] : "";
		
		return "RENAME TABLE $sql_old_table RENAME TO $sql_new_table $suffix";
	}
	
	public static function getModifyTableEncodingStatement($table, $charset, $collation, $options = false) {
		return null; //not possible in postgres
	}
	
	public static function getModifyTableStorageEngineStatement($table, $engine, $options = false) {
		return null; //not possible in postgres
	}
	
	public static function getDropTableStatement($table, $options = false) {
		$sql_table = self::getParsedTableEscapedSQL($table, $options);
		$suffix = $options && !empty($options["suffix"]) ? $options["suffix"] : "";
		return "DROP TABLE IF EXISTS $sql_table $suffix";
	}

	public static function getDropTableCascadeStatement($table, $options = false) {
		$options["suffix"] = "CASCADE " . (isset($options["suffix"]) ? $options["suffix"] : "");
		return self::getDropTableStatement($table, $options);
	}
	
	public static function getAddTableAttributeStatement($table, $attribute_data, $options = false) {
		$sql_table = self::getParsedTableEscapedSQL($table, $options);
		$suffix = $options && !empty($options["suffix"]) ? $options["suffix"] : "";
		$sql = self::getCreateTableAttributeStatement($attribute_data);
		
		return "ALTER TABLE $sql_table ADD COLUMN $sql $suffix";
	}
	
	public static function getModifyTableAttributeStatement($table, $attribute_data, $options = false) {
		$sql_table = self::getParsedTableEscapedSQL($table, $options);
		$suffix = $options && !empty($options["suffix"]) ? $options["suffix"] : "";
		
		$table_props = self::parseTableName($table, $options);
		$table = isset($table_props["name"]) ? $table_props["name"] : null;
		$schema = isset($table_props["schema"]) ? $table_props["schema"] : null;
		
		$attr_sql = self::getCreateTableAttributeStatement($attribute_data, array("do_not_convert_to_serial" => true, "ignore_null" => true, "ignore_default" => true, "ignore_unique" => true, "ignore_unsigned" => true, "ignore_auto_increment" => true), $parsed_data);
		
		if (!empty($parsed_data["primary_key"]))
			$attr_sql = self::getCreateTableAttributeStatement($attribute_data, array("do_not_convert_to_serial" => true, "ignore_null" => true, "ignore_default" => true, "ignore_unique" => true, "ignore_unsigned" => true, "ignore_auto_increment" => true, "ignore_extra" => true), $parsed_data);
		
		$pos = strpos(trim($attr_sql), '"', 1);
		$attr_sql = substr($attr_sql, 0, $pos + 1) . " TYPE" . substr($attr_sql, $pos + 1);
		
		$name = isset($attribute_data["name"]) ? $attribute_data["name"] : null;
		
		//echo "<pre>";print_r($attribute_data);print_r($parsed_data);die();
		
		//remove default value
		$sql = "--remove default value
			ALTER TABLE $sql_table ALTER COLUMN \"$name\" DROP DEFAULT;
			";
		
		//remove unique constraint
		$sql .= "
			--remove unique constraint
			DO $$
			DECLARE myvar varchar = '';
			BEGIN
			    SELECT concat('ALTER TABLE $sql_table DROP CONSTRAINT ', tc.constraint_name) INTO myvar
				FROM information_schema.table_constraints tc
				INNER JOIN pg_namespace nsp on nsp.nspname = tc.constraint_schema
				INNER JOIN pg_constraint pgc on pgc.conname = tc.constraint_name and pgc.connamespace = nsp.oid and pgc.contype = 'u'
				INNER JOIN information_schema.columns col on col.table_schema = tc.table_schema and col.table_name = tc.table_name and col.ordinal_position=ANY(pgc.conkey)
				WHERE " . ($schema ? "tc.constraint_schema='$schema' AND " : "") . "tc.table_name='$table' AND col.column_name='$name' AND tc.constraint_type = 'UNIQUE';
			    
			    IF myvar != '' THEN
			    	 --raise notice 'drop constraint: %', myvar;
		   	 	 EXECUTE myvar;
		   	    END IF;
			END $$;
			";
		
		//set new type
		$sql .= "
			--modify attribute with new type
			ALTER TABLE $sql_table ALTER COLUMN $attr_sql $suffix;
			";
		
		//set not null value
		if (isset($parsed_data["null"])) {
			if (!$parsed_data["null"]) {
				//If attribute is NOT NULL, update all null values with the default value, before it change it to NOT NULL. This is, if we have an attribute which is NULL and we are trying to change it to NOT NULL, and if this attribute contains any record with a NULL value, mssql will not let me modify this attribute to NOT NULL, giving a sql error. So we need to update first all records with NULL values.
				if (empty($parsed_data["primary_key"]) && isset($parsed_data["default"])) { //$has_default cannot be here bc it includes the $attribute_data["has_default"] and in this case we want the $parsed_data["default"] which contains the real default value set by the getCreateTableAttributeStatement method.
					$is_reserved_word = self::isAttributeValueReservedWord($parsed_data["default"]); //check if is a reserved word
					$contains_reserved_word = self::isReservedWordFunction($parsed_data["default"]); //check if contains a function
					$default = is_numeric($parsed_data["default"]) || $is_reserved_word || $contains_reserved_word ? $parsed_data["default"] : "'" . $parsed_data["default"] . "'";
					
					//if attribute is NOT NULL, update all null values with default values, before it change it to NOT NULL
					$sql .= "
			--set attribute values with default value for NULL values
			UPDATE $sql_table SET \"$name\" = $default WHERE \"$name\" IS NULL;
			";
				}
				
				$sql .= "
			--set attribute to NOT NULL
			ALTER TABLE $sql_table ALTER COLUMN \"$name\" SET NOT NULL;
			";
			}
			else
				$sql .= "
			--set attribute to NULL
			ALTER TABLE $sql_table ALTER COLUMN \"$name\" DROP NOT NULL;
			";
		}
		
		//add default value
		$has_default = isset($parsed_data["default"]) && !empty($attribute_data["has_default"]); //only if has_default is set and is true, otherwise the default is to remove.
		
		//set auto-increment sequence
		if (!empty($parsed_data["auto_increment"])) {
			$sql .= "
			--set auto increment constraint for PK: $name
			DO $$
			DECLARE myvar varchar = '';
			DECLARE sequence_name varchar = '';
			DECLARE max_value bigint = 0;
			BEGIN
				SELECT pg_get_serial_sequence('$sql_table', '$name') INTO sequence_name;
				
				IF sequence_name = '' OR sequence_name IS NULL THEN
			   	 	SELECT max(\"$name\") INTO max_value FROM $sql_table;
			   	 	
			   	 	IF max_value > 0 THEN
			   	 		max_value = max_value + 1;
			   	 	END IF;
			   	 	
			   	 	sequence_name = '{$table}_{$name}_seq';
					myvar = concat('CREATE SEQUENCE ', sequence_name, ' START ', max_value, ' OWNED BY $sql_table.\"$name\";');
		   	     ELSE
		   	     	myvar = concat('ALTER SEQUENCE ', sequence_name, ' OWNED BY $sql_table.\"$name\";');
		   	     END IF;
		   	     
		   	     --raise notice 'create or modify sequence: %', myvar;
		   	     EXECUTE myvar;
		   	     
		   	     myvar = CONCAT('ALTER TABLE $sql_table ALTER COLUMN \"$name\" SET DEFAULT nextval(''', sequence_name, ''');');
				--raise notice 'set sequence to table: %', myvar;
		   	     EXECUTE myvar;
			END $$;
			";
		}
		else if ($has_default) { //add default value to non auto-incremented atributes, including pks. Note that postgres doesn't allow default for primary keys.
			$is_reserved_word = self::isAttributeValueReservedWord($parsed_data["default"]); //check if is a reserved word
			$contains_reserved_word = self::isReservedWordFunction($parsed_data["default"]); //check if contains a function
			$default = is_numeric($parsed_data["default"]) || $is_reserved_word || $contains_reserved_word ? $parsed_data["default"] : "'" . $parsed_data["default"] . "'"; //must be single quotes 
			$sql .= "
			--set default value
			ALTER TABLE $sql_table ALTER COLUMN \"$name\" SET DEFAULT $default;
			";
			
			//drop auto-increment sequence
			if (empty($parsed_data["auto_increment"])) {
				$sql .= "
				--set auto increment constraint for PK: $name
				DO $$
				DECLARE myvar varchar = '';
				DECLARE sequence_name varchar = '';
				DECLARE max_value bigint = 0;
				BEGIN
					SELECT pg_get_serial_sequence('$sql_table', '$name') INTO sequence_name;
					
					IF sequence_name != '' AND sequence_name IS NOT NULL THEN
			   	     	myvar = concat('DROP SEQUENCE IF EXISTS ', sequence_name, ' CASCADE;');
				   	     --raise notice 'drop sequence: %', myvar;
				   	     EXECUTE myvar;
			   	     END IF;
				END $$;
				";
			}
		}
		
		//add and remove unsigned
		$sql .= "
			--add and remove unsigned constraint
			DO $$
			DECLARE myvar varchar = '';
			DECLARE constraint_name varchar = '';
			DECLARE add_constraint smallint = " . (!empty($parsed_data["unsigned"]) ? 1 : 0) . ";
			BEGIN
				SELECT 
					--tc.table_schema,
					--tc.table_name,
					--string_agg(col.column_name, ', ') AS columns,
					--cc.check_clause,
					tc.constraint_name INTO constraint_name
				FROM information_schema.table_constraints tc
				INNER JOIN information_schema.check_constraints cc on tc.constraint_schema = cc.constraint_schema and tc.constraint_name = cc.constraint_name
				INNER JOIN pg_namespace nsp on nsp.nspname = cc.constraint_schema
				INNER JOIN pg_constraint pgc on pgc.conname = cc.constraint_name and pgc.connamespace = nsp.oid and pgc.contype = 'c'
				INNER JOIN information_schema.columns col on col.table_schema = tc.table_schema and col.table_name = tc.table_name and col.ordinal_position=ANY(pgc.conkey)
				WHERE " . ($schema ? "tc.constraint_schema='$schema' AND " : "") . "tc.table_name='$table' AND col.column_name='$name' AND cc.check_clause = CONCAT('((', col.column_name, ' > 0))')
				GROUP BY tc.table_schema, tc.table_name, tc.constraint_name, cc.check_clause
				ORDER BY tc.table_schema, tc.table_name
				LIMIT 1;
				
				--raise notice 'constraint_name: %', constraint_name;
				
				IF add_constraint <> 1 AND constraint_name != '' AND constraint_name IS NOT NULL THEN
					myvar = CONCAT('ALTER TABLE $sql_table DROP CONSTRAINT ' , constraint_name, ';');
					--raise notice 'drop check constraint: %', myvar;
					EXECUTE myvar;
				END IF;
				
				IF add_constraint = 1 AND (constraint_name = '' OR constraint_name IS NULL) THEN
					myvar = CONCAT('ALTER TABLE $sql_table ADD CONSTRAINT {$table}_{$name}_check CHECK ($name > 0);');
					--raise notice 'add check constraint: %', myvar;
					EXECUTE myvar;
				END IF;
			END $$;
			";
		
		//add unique contraint. Must be after we change the column
		if (empty($parsed_data["primary_key"]) && !empty($parsed_data["unique"])) {
			$rand = rand();
			$sql .= "
			--add unique constraint
			ALTER TABLE $sql_table ADD CONSTRAINT uk__{$table}__{$name}__pf$rand UNIQUE ($name);
			";
		}
		
		//echo "<pre>$sql";die();
		return $sql;
	}
	
	public static function getRenameTableAttributeStatement($table, $old_attribute, $new_attribute, $options = false) {
		$sql_table = self::getParsedTableEscapedSQL($table, $options);
		$suffix = $options && !empty($options["suffix"]) ? $options["suffix"] : "";
		return "ALTER TABLE $sql_table RENAME COLUMN \"$old_attribute\" TO \"$new_attribute\" $suffix";
	}
	
	public static function getDropTableAttributeStatement($table, $attribute, $options = false) {
		$sql_table = self::getParsedTableEscapedSQL($table, $options);
		$suffix = $options && !empty($options["suffix"]) ? $options["suffix"] : "";
		return "ALTER TABLE $sql_table DROP COLUMN \"$attribute\" $suffix";
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
		
		$table_props = self::parseTableName($table, $options);
		$table = isset($table_props["name"]) ? $table_props["name"] : null;
		$schema = isset($table_props["schema"]) ? $table_props["schema"] : null;
		
		//return "ALTER TABLE $table ADD CONSTRAINT $constraint_name PRIMARY KEY (" . implode(", ", $attributes_name) . ") $suffix";
		$sql = "ALTER TABLE $sql_table ADD PRIMARY KEY (\"" . implode("\", \"", $attributes_name) . "\") $suffix;
		";
		
		foreach ($attributes as $attr) 
			if (is_array($attr)) {
				self::getCreateTableAttributeStatement($attr, null, $parsed_data); //only call this to use $parsed_data
				
				if (!empty($parsed_data["auto_increment"])) {
					$name = $attr["name"];
					
					$sql .= "
					--set auto increment constraint for PK: $name
					DO $$
					DECLARE myvar varchar = '';
					DECLARE sequence_name varchar = '';
					DECLARE max_value bigint = 0;
					BEGIN
						SELECT pg_get_serial_sequence('$sql_table', '$name') INTO sequence_name;
						
						IF sequence_name = '' OR sequence_name IS NULL THEN
					   	 	SELECT max(\"$name\") INTO max_value FROM $sql_table;
					   	 	
					   	 	IF max_value > 0 THEN
					   	 		max_value = max_value + 1;
					   	 	END IF;
					   	 	
					   	 	sequence_name = '{$table}_{$name}_seq';
							myvar = concat('CREATE SEQUENCE ', sequence_name, ' START ', max_value, ' OWNED BY $sql_table.\"$name\";');
				   	     ELSE
				   	     	myvar = concat('ALTER SEQUENCE ', sequence_name, ' OWNED BY $sql_table.\"$name\";');
				   	     END IF;
				   	     
				   	     --raise notice 'create or modify sequence: %', myvar;
				   	     EXECUTE myvar;
				   	     
				   	     myvar = CONCAT('ALTER TABLE $sql_table ALTER COLUMN \"$name\" SET DEFAULT nextval(''', sequence_name, ''');');
						--raise notice 'set sequence to table: %', myvar;
				   	     EXECUTE myvar;
					END $$;
					";
				}
			}
		
		return $sql;
	}
	
	public static function getDropTablePrimaryKeysStatement($table, $options = false) {
		$sql_table = self::getParsedTableEscapedSQL($table, $options);
		$suffix = $options && !empty($options["suffix"]) ? $options["suffix"] : "";
		
		$table_props = self::parseTableName($table, $options);
		$table = isset($table_props["name"]) ? $table_props["name"] : null;
		$schema = isset($table_props["schema"]) ? $table_props["schema"] : null;
		
		//return "ALTER TABLE $table DROP PRIMARY KEY $suffix"; //Not sure if this is correct
		//return "ALTER TABLE $table DROP CONSTRAINT $constraint_name $suffix;"; //This is the correct sql but we don't have the constraint_name so we do:
		return "DO $$
				DECLARE myvar varchar = '';
				DECLARE sequence_name varchar = '';
				DECLARE attr_name varchar = '';
				DECLARE t_row record;
				BEGIN
			   		-- get pk attr name if exists
			   		FOR t_row in 
			   			SELECT a.attname, format_type(a.atttypid, a.atttypmod) AS data_type
						 FROM pg_index i
						 INNER JOIN pg_attribute a ON a.attrelid = i.indrelid AND a.attnum = ANY(i.indkey)
						 " . ($schema ? "INNER JOIN pg_class c ON c.oid = i.indrelid AND a.attrelid = c.oid
						 INNER JOIN pg_namespace ns ON ns.nspname = '$schema' AND c.relnamespace = ns.oid" : "") . "
						 WHERE i.indrelid = '$table'::regclass AND i.indisprimary 
					LOOP
						attr_name = '';
						sequence_name = '';
						
						SELECT t_row.attname INTO attr_name;
				   		--raise notice 'attr_name: %', attr_name;
				   		
				   		IF attr_name != '' AND attr_name IS NOT NULL THEN
					   		-- get sequence name if exists
							myvar = concat('SELECT pg_get_serial_sequence(''$sql_table'', ''', attr_name, ''');');
							--raise notice 'pg_get_serial_sequence: %', myvar;
							EXECUTE myvar INTO sequence_name;
							--raise notice 'sequence_name: %', sequence_name;
							
							--drop sequence
							IF sequence_name != '' AND sequence_name IS NOT NULL THEN
					   	     	myvar = concat('DROP SEQUENCE IF EXISTS ', sequence_name, ' CASCADE;');
					   	     	--raise notice 'drop sequence: %', myvar;
					   	     	EXECUTE myvar;
					   	     END IF;
					   	END IF;
					END LOOP;
				   	
					--drop pk constraint
					SELECT concat('ALTER TABLE $sql_table DROP CONSTRAINT ', constraint_name, ';') INTO myvar
					 FROM information_schema.table_constraints
					 WHERE " . ($schema ? "table_schema = '$schema' AND " : "") . "table_name = '$table' AND constraint_type = 'PRIMARY KEY';

					IF myvar != '' THEN
						--raise notice 'drop pk contraint: %', myvar;
						EXECUTE myvar;
					END IF;
				END $$;";
	}
	
	//used in CMSDeploymentHandler
	public static function getAddTableForeignKeyStatement($table, $fk, $options = false) {
		if ($fk && (!empty($fk["attribute"]) || !empty($fk["child_column"]))) {
			$sql_table = self::getParsedTableEscapedSQL($table, $options);
			$suffix = $options && !empty($options["suffix"]) ? $options["suffix"] : "";
			
			$on_delete = !empty($fk["on_delete"]) ? "ON DELETE " . $fk["on_delete"] : "";
			$on_update = !empty($fk["on_update"]) ? "ON UPDATE " . $fk["on_update"] : "";
			$attr_name = !empty($fk["attribute"]) ? $fk["attribute"] : $fk["child_column"];
			$ref_attr_name = !empty($fk["reference_attribute"]) ? $fk["reference_attribute"] : (isset($fk["parent_column"]) ? $fk["parent_column"] : null);
			$ref_table_name = !empty($fk["reference_table"]) ? $fk["reference_table"] : (isset($fk["parent_table"]) ? $fk["parent_table"] : null);
			$constraint_name = !empty($fk["name"]) ? $fk["name"] : (isset($fk["constraint_name"]) ? $fk["constraint_name"] : null);
			
			$attrs = is_array($attr_name) ? $attr_name : array($attr_name);
			$ref_attrs = is_array($ref_attr_name) ? $ref_attr_name : array($ref_attr_name);
			$sql_reference_table = self::getParsedTableEscapedSQL($ref_table_name, $options);
			
			return "ALTER TABLE $sql_table ADD CONSTRAINT " . $constraint_name . " FOREIGN KEY (\"" . implode('", "', $attrs) . "\") REFERENCES " . $sql_reference_table . " (\"" . implode('", "', $ref_attrs) . "\") $on_delete $on_update $suffix";
		}
	}
	
	//used in CMSDeploymentHandler
	public static function getDropTableForeignKeysStatement($table, $options = false) {
		$table_props = self::parseTableName($table, $options);
		$table = isset($table_props["name"]) ? $table_props["name"] : null;
		$suffix = $options && !empty($options["suffix"]) ? $options["suffix"] : "";
		
		return "DO $$
DECLARE myvar varchar = '';
DECLARE t_row record;
BEGIN
	FOR t_row in 
		SELECT tc.table_name AS 'table_name', tc.constraint_name AS 'constraint_name' 
		FROM information_schema.table_constraints tc
		INNER JOIN pg_namespace nsp on nsp.nspname = tc.constraint_schema
		INNER JOIN pg_constraint pgc on pgc.conname = tc.constraint_name and pgc.connamespace = nsp.oid and pgc.contype = 'f'
		INNER JOIN information_schema.columns col on col.table_schema = tc.table_schema and col.table_name = tc.table_name and col.ordinal_position=ANY(pgc.conkey)
		WHERE tc.constraint_schema NOT LIKE 'pg%' AND tc.constraint_schema <> 'information_schema' AND tc.table_name='$table' AND tc.constraint_type = 'FOREIGN KEY'
	LOOP
		myvar = '';
		
		SELECT concat('ALTER TABLE IF EXISTS \"', t_row.table_name, '\" DROP CONSTRAINT \"', t_row.constraint_name, '\"$suffix;') INTO myvar;
		
		IF myvar != '' AND myvar IS NOT NULL THEN
			EXECUTE myvar;
	   	END IF;
	END LOOP;
END $$;";
	}

	public static function getDropTableForeignConstraintStatement($table, $constraint_name, $options = false) {
		$sql_table = self::getParsedTableEscapedSQL($table, $options);
		return "ALTER TABLE IF EXISTS $sql_table DROP CONSTRAINT \"$constraint_name\";";
	}
	
	//used in CMSModuleInstallationHandler
	public static function getAddTableIndexStatement($table, $attributes, $options = false) {
		$sql_table = self::getParsedTableEscapedSQL($table, $options);
		$suffix = $options && !empty($options["suffix"]) ? $options["suffix"] : "";
		$attributes = is_array($attributes) ? $attributes : array($attributes);
		
		return "CREATE INDEX ON $sql_table (\"" . implode("\", \"", $attributes) . "\") $suffix";
	}
	
	public static function getLoadTableDataFromFileStatement($file_path, $table, $options = false) {
		//http://www.postgresql.org/docs/current/static/sql-copy.html
		$sql_table = self::getParsedTableEscapedSQL($table, $options);
		$suffix = $options && !empty($options["suffix"]) ? $options["suffix"] : "";
		$attributes = $options && !empty($options["attributes"]) ? "(" . (is_array($options["attributes"]) ? implode(", ", $options["attributes"]) : $options["attributes"]) . ")" : "";
		$fields_delimiter = !empty($options["fields_delimiter"]) ? $options["fields_delimiter"] : "\t";
		
		return "COPY $sql_table $attributes FROM '$file_path' WITH DELIMITER '$fields_delimiter' $suffix";
	}
	
	public static function getShowCreateTableStatement($table, $options = false) {
		return ""; //Don't worry bc DBDumper will not use this method.
	}

	public static function getShowCreateViewStatement($view, $options = false) {
		$table_props = self::parseTableName($view, $options);
		$view = isset($table_props["name"]) ? $table_props["name"] : null;
		
		return "select '$view' as \"View\", pg_get_viewdef('$view') as \"Create View\""; //more functions in https://www.postgresql.org/docs/current/functions-info.html
	}

	public static function getShowCreateTriggerStatement($trigger, $options = false) {
		$table_props = self::parseTableName($trigger, $options);
		$trigger = isset($table_props["name"]) ? $table_props["name"] : null;
		
		return "SELECT tgname as \"Trigger\", pg_get_triggerdef(oid) as \"SQL Original Statement\" ".
		   "FROM pg_trigger WHERE tgname='$trigger'";
	}

	public static function getShowCreateProcedureStatement($procedure, $options = false) {
		$table_props = self::parseTableName($procedure, $options);
		$procedure = isset($table_props["name"]) ? $table_props["name"] : null;
		
		//return "select '$procedure' as \"Procedure\", pg_get_functiondef('$procedure') as \"Create Procedure\"";
		//WHERE n.nspname = 'public' AND 
		return "SELECT proname as \"Procedure\", pg_get_functiondef(f.oid) as \"Create Procedure\"
			FROM pg_catalog.pg_proc f
			INNER JOIN pg_catalog.pg_namespace n ON (f.pronamespace = n.oid)
			WHERE proname='$procedure'";
	}

	public static function getShowCreateFunctionStatement($function, $options = false) {
		$table_props = self::parseTableName($function, $options);
		$function = isset($table_props["name"]) ? $table_props["name"] : null;
		
		//WHERE n.nspname = 'public' AND 
		return "SELECT proname as \"Function\", pg_get_functiondef(f.oid) as \"Create Function\"
			FROM pg_catalog.pg_proc f
			INNER JOIN pg_catalog.pg_namespace n ON (f.pronamespace = n.oid)
			WHERE proname='$function'";
	}

	public static function getShowCreateEventStatement($event, $options = false) {
		$table_props = self::parseTableName($event, $options);
		$event = isset($table_props["name"]) ? $table_props["name"] : null;
		
		return "SELECT tgname as \"Event\", pg_get_triggerdef(oid) as \"Create Event\" ".
		   "FROM pg_trigger WHERE tgname='$event'";
	}
	
	public static function getShowTablesStatement($db_name, $options = false) {
		return str_replace("\t", "", self::getTablesStatement($db_name, $options));
		/*return "SELECT ".
		  " TABLE_NAME AS \"table_name\", ".
		  " TABLE_SCHEMA AS \"table_schema\" ".
		  "FROM INFORMATION_SCHEMA.TABLES ".
		  "WHERE TABLE_TYPE='BASE TABLE' AND TABLE_CATALOG='$db_name' AND TABLE_SCHEMA NOT LIKE 'pg%' AND TABLE_SCHEMA <> 'information_schema'";*/
	}

	public static function getShowViewsStatement($db_name, $options = false) {
		return "SELECT TABLE_NAME AS \"view_name\" ".
		  "FROM INFORMATION_SCHEMA.views ".
		  "WHERE TABLE_CATALOG = '$db_name' AND TABLE_SCHEMA NOT LIKE 'pg%' AND TABLE_SCHEMA <> 'information_schema'";
	}

	public static function getShowTriggersStatement($db_name, $options = false) {
		return "SELECT TRIGGER_NAME AS \"Trigger\", event_object_table AS \"table_name\" ".
		  "FROM INFORMATION_SCHEMA.TRIGGERS ".
		  "WHERE TRIGGER_CATALOG='$db_name' AND TRIGGER_SCHEMA NOT LIKE 'pg%' AND TRIGGER_SCHEMA <> 'information_schema'";
	}

	public static function getShowTableColumnsStatement($table, $db_name = false, $options = false) {
		return str_replace("\n", " ", self::getTableFieldsStatement($table, $db_name, $options));
		/*$sql = "
		SELECT 
			isc.column_name, 
			isc.column_default, 
			isc.is_nullable, 
			isc.data_type, 
			isc.character_maximum_length, 
			isc.numeric_precision, 
			isc.character_set_name, 
			isc.character_set_schema, 
			isc.collation_name, 
			isc.collation_schema, 
			ARRAY_AGG(pkt.indisprimary) as is_primary,
			ARRAY_AGG(pkt.indisunique) as is_unique,
			checkconstraint.check_constraint_name,
			checkconstraint.check_constraint_value,
			uk.constraint_name as unique_constraint_name,
			dt.column_comment
		FROM information_schema.columns isc 
		LEFT JOIN (
			SELECT               
			    pg_attribute.attname as attname, 
			    format_type(pg_attribute.atttypid, pg_attribute.atttypmod) as type,
			    pg_index.indisunique, 
			    pg_index.indisprimary, 
			    pg_index.indisexclusion, 
			    pg_index.indimmediate, 
			    pg_index.indisclustered, 
			    pg_index.indisvalid, 
			    pg_index.indcheckxmin, 
			    pg_index.indisready, 
			    pg_index.indislive
			 FROM pg_index, pg_class, pg_attribute, pg_namespace 
			 WHERE 
				pg_class.oid = '$table'::regclass AND 
				indrelid = pg_class.oid AND 
				nspname NOT LIKE 'pg%' AND nspname <> 'information_schema' AND 
				pg_class.relnamespace = pg_namespace.oid AND 
				pg_attribute.attrelid = pg_class.oid AND 
				pg_attribute.attnum = any(pg_index.indkey)
		) pkt ON pkt.attname = isc.column_name and pkt.type = isc.data_type
		LEFT JOIN (
			SELECT 
			  column_name as cn, 
			  consrc as check_constraint_value,
			  constraint_name as check_constraint_name
			FROM information_schema.constraint_column_usage
			INNER JOIN pg_constraint on conname = constraint_name and contype='c'
			WHERE table_catalog = current_database() and table_name = '$table'
		) checkconstraint ON checkconstraint.cn = isc.column_name
		LEFT JOIN (
			SELECT 
			  c.column_name cn, 
			  pgd.description column_comment
			FROM pg_catalog.pg_statio_all_tables as st
			  INNER JOIN pg_catalog.pg_description pgd on (pgd.objoid=st.relid)
			  INNER JOIN information_schema.columns c on (pgd.objsubid=c.ordinal_position and c.table_schema=st.schemaname and c.table_name=st.relname)
			WHERE st.schemaname NOT LIKE 'pg%' AND st.schemaname <> 'information_schema' and st.relname='$table'
		) dt ON dt.cn = isc.column_name
		LEFT JOIN information_schema.table_constraints tc ON tc.table_schema=isc.table_schema AND tc.table_name=isc.table_name AND constraint_type = 'UNIQUE'
		LEFT JOIN (
			SELECT c.conname as constraint_name, a.attname as column, '$table' as table
			FROM pg_constraint c
			INNER JOIN  (
				SELECT attname, array_agg(attnum::int) AS attkey
				FROM pg_attribute
				WHERE attrelid = '$table'::regclass
				GROUP BY attname
			) a ON c.conkey::int[] <@ a.attkey AND c.conkey::int[] @> a.attkey
			WHERE c.contype='u' AND c.conrelid='$table'::regclass
		) uk ON uk.table=isc.table_name AND uk.column=isc.column_name
		WHERE isc.table_catalog=current_database() and isc.table_name='$table' and isc.table_schema NOT LIKE 'pg%' AND isc.table_schema <> 'information_schema'
		GROUP BY 
			isc.column_name, 
			isc.column_default, 
			isc.is_nullable, 
			isc.data_type, 
			isc.character_maximum_length, 
			isc.numeric_precision, 
			isc.character_set_name, 
			isc.character_set_schema, 
			isc.collation_name, 
			isc.collation_schema, 
			checkconstraint.check_constraint_name,
			checkconstraint.check_constraint_value,
			uk.constraint_name,
			dt.column_comment";
		//ORDER BY isc.ordinal_position";

		return str_replace("\t", "", $sql);*/
	}

	//${args[0]} => table_name
	public static function getShowForeignKeysStatement($table, $db_name = false, $options = false) {
		return str_replace("\t", "", self::getForeignKeysStatement($table, $db_name, $options));
		/*$schema = null;
		
		return str_replace("\t", "", "SELECT 
			  col.table_catalog AS catalog, 
			  col.table_schema AS schema, 
			  col.table_name AS child_table, 
			  --col.column_name AS child_column,
			  att2.attname AS child_column, 
			  cl.relname AS parent_table, 
			  att.attname AS parent_column,
			  tc.constraint_name,
			  con.confupdtype,
			  CASE con.confupdtype
				  WHEN 'a' THEN 'NO ACTION '
				  WHEN 'r' THEN 'RESTRICT '
				  WHEN 'c' THEN 'CASCADE '
				  WHEN 'n' THEN 'SET NULL '
				  ELSE 'SET DEFAULT '
				 END AS on_update,
			  con.confdeltype,
			  CASE con.confdeltype
				  WHEN 'a' THEN 'NO ACTION '
				  WHEN 'r' THEN 'RESTRICT '
				  WHEN 'c' THEN 'CASCADE '
				  WHEN 'n' THEN 'SET NULL '
				  ELSE 'SET DEFAULT '
				 END AS on_delete,
			  pg_get_constraintdef(pgc.oid, true) AS constraint_def
			FROM (
				SELECT 
				  unnest(con1.conkey) AS parent, 
				  unnest(con1.confkey) AS child, 
				  con1.confrelid, 
				  con1.conrelid,
				  ns.nspname,
				  cl.relname,
				  con1.confupdtype,
				  con1.confdeltype
				FROM pg_class cl
				INNER JOIN pg_namespace ns ON cl.relnamespace = ns.oid" . ($schema ? " AND ns.nspname='$schema'" : "") . "
				INNER JOIN pg_constraint con1 ON con1.conrelid = cl.oid AND con1.contype = 'f'
				WHERE cl.relname = '$table'
			) con
			INNER JOIN pg_attribute att ON att.attrelid = con.confrelid AND att.attnum = con.child
			INNER JOIN pg_class cl ON cl.oid = con.confrelid
			INNER JOIN pg_attribute att2 ON att2.attrelid = con.conrelid AND att2.attnum = con.parent
			INNER JOIN pg_namespace ns ON cl.relnamespace = ns.oid AND ns.nspname=con.nspname
			INNER JOIN information_schema.table_constraints tc ON ns.nspname = tc.constraint_schema AND tc.constraint_schema=con.nspname AND tc.table_name=con.relname AND tc.constraint_type = 'FOREIGN KEY'
			INNER JOIN pg_constraint pgc ON pgc.conname = tc.constraint_name AND pgc.connamespace = ns.oid AND pgc.conrelid = con.conrelid AND pgc.contype = 'f'
			INNER JOIN information_schema.columns col ON col.table_catalog=current_database() AND col.table_schema = tc.table_schema AND col.table_name = tc.table_name AND col.ordinal_position=ANY(pgc.conkey);");*/
	}

	public static function getShowProceduresStatement($db_name, $options = false) {
		return "SELECT p.oid, n.nspname as \"schema\", p.proname as \"procedure_name\" ".
		  "FROM pg_proc p ".
		  "INNER JOIN pg_namespace n ON n.oid = p.pronamespace ".
		  "INNER JOIN information_schema.routines rt ON rt.routine_catalog='$db_name' AND rt.routine_type='PROCEDURE' AND routine_schema=n.nspname AND rt.routine_name=p.proname ".
		  "WHERE n.nspname NOT LIKE 'pg%' AND n.nspname <> 'information_schema' ".
		  "GROUP BY p.oid, n.nspname, p.proname";
	}

	public static function getShowFunctionsStatement($db_name, $options = false) {
		return "SELECT p.oid, n.nspname as \"schema\", p.proname as \"function_name\" ".
		  "FROM pg_proc p ".
		  "INNER JOIN pg_namespace n ON n.oid = p.pronamespace ".
		  "INNER JOIN information_schema.routines rt ON rt.routine_catalog='$db_name' AND rt.routine_type='FUNCTION' AND routine_schema=n.nspname AND rt.routine_name=p.proname ".
		  "WHERE n.nspname NOT LIKE 'pg%' AND n.nspname <> 'information_schema' ".
		  "GROUP BY p.oid, n.nspname, p.proname";
	}

	public static function getShowEventsStatement($db_name, $options = false) {
		return "SELECT TRIGGER_NAME AS \"event_name\" ".
		  "FROM INFORMATION_SCHEMA.TRIGGERS ".
		  "WHERE TRIGGER_CATALOG='$db_name' AND TRIGGER_SCHEMA NOT LIKE 'pg%' AND TRIGGER_SCHEMA <> 'information_schema'";
	}

	public static function getSetupTransactionStatement($options = false) {
		return "SET TRANSACTION ISOLATION LEVEL REPEATABLE READ";
	}

	public static function getStartTransactionStatement($options = false) {
		return "START TRANSACTION ".
	 	 "/* [transaction_name] WITH MARK [description] */";
	}

	public static function getCommitTransactionStatement($options = false) {
		return "COMMIT TRANSACTION";
	}

	public static function getStartDisableAutocommitStatement($options = false) {
		return "SET AUTOCOMMIT TO OFF;";
	}

	public static function getEndDisableAutocommitStatement($options = false) {
		return "SET AUTOCOMMIT TO ON;";
	}

	public static function getStartLockTableWriteStatement($table, $options = false) {
		return null;
	}

	public static function getStartLockTableReadStatement($table, $options = false) {
		return null;
	}

	public static function getEndLockTableStatement($options = false) {
		//return "UNLOCK TABLES;";
		return null; //postgres doesn't have unlock
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
		return "DROP EVENT TRIGGER IF EXISTS $sql_event;";
	}

	public static function getDropViewStatement($view, $options = false) {
		$sql_view = self::getParsedTableEscapedSQL($view, $options);
		return "DROP VIEW IF EXISTS $sql_view;";
	}
	
	//postgres doesn't have a sql to get all available charsets
	public static function getShowDBCharsetsStatement($options = false) {
		return null;
	}
	
	//postgres doesn't support charset for table
	public static function getShowTableCharsetsStatement($options = false) {
		return null;
	}
	
	//postgres doesn't support charset for column
	public static function getShowColumnCharsetsStatement($options = false) {
		return null;
	}
	
	//postgres doesn't set the collation for table
	public static function getShowDBCollationsStatement($options = false) {
		return null;
	}
	
	//postgres doesn't support collation for table
	public static function getShowTableCollationsStatement($options = false) {
		return null;
	}
	
	public static function getShowColumnCollationsStatement($options = false) {
		return "SELECT collname FROM pg_collation";
	}
	
	//postgres doesn't support storage engines
	public static function getShowDBStorageEnginesStatement($options = false) {
		return null;
	}
}
?>
