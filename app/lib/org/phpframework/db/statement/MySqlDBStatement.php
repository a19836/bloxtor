<?php
trait MySqlDBStatement { //must be "trait" and not "class" bc this code will serve to be extended by the MySqlDB class, whcih already have the extended "DB" class. Note that PHP only allows 1 extended class.
	
	public static function getCreateDBStatement($db_name, $options = false) {
		return "CREATE DATABASE IF NOT EXISTS `" . $db_name . "`" . (!empty($options["encoding"]) ? " DEFAULT CHARACTER SET " . $options["encoding"] : "");
	}

	public static function getDropDatabaseStatement($db_name, $options = false) {
		return "/*!40000 DROP DATABASE IF EXISTS `$db_name`*/;";
	}
	
	public static function getSelectedDBStatement($options = false) {
		return "SELECT DATABASE() AS db";
	}
	
	public static function getDBsStatement($options = false) {
		return "SHOW DATABASES";
	}
	
	public static function getTablesStatement($db_name = false, $options = false) {
		$schema = $options && !empty($options["schema"]) ? $options["schema"] : null;
		$db_name = $db_name ? $db_name : $schema; //schema is the database in mysql
		
		//$sql = "SHOW TABLES FROM $db_name";
		$sql = "SELECT 
				TABLE_NAME AS 'table_name', 
				TABLE_TYPE AS 'table_type',
				TABLE_SCHEMA AS 'table_schema', 
				ENGINE AS 'table_storage_engine', 
				'' AS 'table_charset',
				TABLE_COLLATION AS 'table_collation', 
				TABLE_COMMENT AS 'table_comment'
			FROM information_schema.TABLES 
			WHERE TABLE_TYPE='BASE TABLE' AND TABLE_SCHEMA=" . ($db_name ? "'$db_name'" : "DATABASE()") . "
			ORDER BY TABLE_NAME ASC";
		
		return $sql;
	}
		
	public static function getTableFieldsStatement($table, $db_name = false, $options = false) {
		$table_props = self::parseTableName($table, $options);
		$table = isset($table_props["name"]) ? $table_props["name"] : null;
		$schema = isset($table_props["schema"]) ? $table_props["schema"] : null;
		
		$db_name = $db_name ? $db_name : $schema; //schema is the database in mysql
		
		//$sql = "SHOW COLUMNS FROM `$table`;";
		$sql = "SELECT 
				COLUMN_NAME AS 'column_name', 
				DATA_TYPE AS 'data_type', 
				COLUMN_TYPE AS 'column_type', 
				COLUMN_DEFAULT AS 'column_default', 
				IS_NULLABLE AS 'is_nullable', 
				CHARACTER_MAXIMUM_LENGTH AS 'character_maximum_length', 
				NUMERIC_PRECISION AS 'numeric_precision', 
				CHARACTER_SET_NAME AS 'character_set_name', 
				COLLATION_NAME AS 'collation_name', 
				COLUMN_KEY AS 'column_key', 
				EXTRA AS 'extra', 
				COLUMN_COMMENT AS 'column_comment', 
				IF(LOWER(COLUMN_KEY) = 'pri', 1, 0) AS is_primary,
				IF(LOWER(COLUMN_KEY) = 'uni', 1, 0) AS is_unique
			FROM information_schema.COLUMNS 
			WHERE TABLE_SCHEMA=" . ($db_name ? "'$db_name'" : "DATABASE()") . " AND TABLE_NAME='$table'
			ORDER BY ORDINAL_POSITION ASC";
		
		return $sql;
	}
	
	public static function getForeignKeysStatement($table, $db_name = false, $options = false) {
		$table_props = self::parseTableName($table, $options);
		$table = isset($table_props["name"]) ? $table_props["name"] : null;
		$schema = isset($table_props["schema"]) ? $table_props["schema"] : null;
		
		$db_name = $db_name ? $db_name : $schema; //schema is the database in mysql
		
		$sql = "select 
				kcu.table_catalog AS 'catalog', 
				kcu.table_schema AS 'schema', 
				kcu.column_name AS 'child_column', 
				kcu.referenced_table_name AS 'parent_table', 
				kcu.referenced_column_name AS 'parent_column',
				kcu.constraint_name,
				rc.UPDATE_RULE AS 'on_update',
				rc.DELETE_RULE AS 'on_delete'
		from information_schema.key_column_usage kcu
		left join information_schema.referential_constraints rc ON rc.constraint_name=kcu.constraint_name and rc.CONSTRAINT_CATALOG=kcu.CONSTRAINT_CATALOG and rc.CONSTRAINT_SCHEMA=kcu.CONSTRAINT_SCHEMA and rc.TABLE_NAME=kcu.TABLE_NAME and rc.REFERENCED_TABLE_NAME=kcu.REFERENCED_TABLE_NAME
		where kcu.referenced_table_name is not null and kcu.table_schema = " . ($db_name ? "'$db_name'" : "DATABASE()") . " and kcu.table_name = '$table'";
		
		/*$sql = "SELECT
			    tc.constraint_name, tc.table_name, kcu.column_name, 
			    ccu.table_name AS foreign_table_name,
			    ccu.column_name AS foreign_column_name 
			FROM 
			    information_schema.table_constraints AS tc 
			    JOIN information_schema.key_column_usage AS kcu
			      ON tc.constraint_name = kcu.constraint_name
			    JOIN information_schema.constraint_column_usage AS ccu
			      ON ccu.constraint_name = tc.constraint_name
			WHERE constraint_type = 'FOREIGN KEY' AND tc.table_name='$table'";*/
		
		return $sql;
	}
	
	public static function getCreateTableStatement($table_data, $options = false) {
		$table_name = !empty($table_data["table_name"]) ? $table_data["table_name"] : (isset($table_data["name"]) ? $table_data["name"] : null); //Note that $table_name can contains be: "schema.name"
		$table_charset = !empty($table_data["table_charset"]) ? $table_data["table_charset"] : (isset($table_data["charset"]) ? $table_data["charset"] : null);
		$table_collation = !empty($table_data["table_collation"]) ? $table_data["table_collation"] : (isset($table_data["collation"]) ? $table_data["collation"] : null);
		$table_storage_engine = !empty($table_data["table_storage_engine"]) ? $table_data["table_storage_engine"] : (isset($table_data["engine"]) ? $table_data["engine"] : null);
		$attributes = isset($table_data["attributes"]) ? $table_data["attributes"] : null;
		
		$table_storage_engine = !empty($table_storage_engine) ? "ENGINE=$table_storage_engine" : "";
		$table_charset = !empty($table_charset) ? "DEFAULT CHARACTER SET=$table_charset" : "";
		$table_collation = !empty($table_collation) ? (empty($table_charset) ? "DEFAULT" : "") . " COLLATE=$table_collation" : "";
		
		$sql_table_name = self::getParsedTableEscapedSQL($table_name, $options);
		$sql = "CREATE TABLE $sql_table_name (\n";
		
		if (is_array($attributes)) {
			$pks_sql = array();
			
			foreach ($attributes as $attribute) 
				if (!empty($attribute["name"])) {
					$name = $attribute["name"];
					$primary_key = isset($attribute["primary_key"]) ? $attribute["primary_key"] : null;
					$pk = $primary_key == "1" || strtolower($primary_key) == "true";
					
					if ($pk)
						$pks_sql[] = $name;
					
					$at_sql = self::getCreateTableAttributeStatement($attribute);
					$sql .= "  " . $at_sql . ",\n";
				}
			
			if ($pks_sql)
				$sql .= "  PRIMARY KEY (`" . implode("`, `", $pks_sql) . "`)";
			else
				$sql = substr($sql, 0, strlen($sql) - 2);//remove the last comma ','
		}
		
		//This are not being used for now, but I should change the diagram to use this feature too
		if (isset($table_data["unique_keys"]) && is_array($table_data["unique_keys"]))
			foreach ($table_data["unique_keys"] as $key) 
				if (!empty($key["attribute"])) {
					$type = !empty($key["type"]) ? "USING " . $key["type"] : "";
					$attrs = is_array($key["attribute"]) ? $key["attribute"] : array($key["attribute"]);
					
					$sql .= ",   UNIQUE " . (!empty($key["name"]) ? $key["name"] : "") . " $type (`" . implode('`, `', $attrs) . "`)";
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
					
					$sql .= ",\n   FOREIGN KEY " . $constraint_name . " (`" . implode('`, `', $attrs) . "`) REFERENCES " . $sql_reference_table . " (`" . implode('`, `', $ref_attrs) . "`) $on_delete $on_update";
				}
		
		//This are not being used for now, but I should change the diagram to use this feature too
		if (isset($table_data["index_keys"]) && is_array($table_data["index_keys"]))
			foreach ($table_data["index_keys"] as $key) 
				if (!empty($key["attribute"])) {
					$type = !empty($key["type"]) ? "USING " . $key["type"] : "";
					$attrs = is_array($key["attribute"]) ? $key["attribute"] : array($key["attribute"]);
					
					$sql .= ",\n   INDEX " . (isset($key["name"]) ? $key["name"] : "") . " $type (`" . implode('`, `', $attrs) . "`),\n";
				}

		$suffix = $options && !empty($options["suffix"]) ? $options["suffix"] : "";
		
		$sql .= "\n) $table_storage_engine $table_charset $table_collation $suffix";
		
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
			$pk = $pk == "1" || strtolower($pk) == "true"; //$ == "1" includes $ === true  too.
			$auto_increment = isset($attribute_data["auto_increment"]) ? $attribute_data["auto_increment"] : null;
			$auto_increment = $auto_increment == "1" || strtolower($auto_increment) == "true"; //this can change in the $flags bellow. $ == "1" includes $ === true  too.
			$unsigned = isset($attribute_data["unsigned"]) ? $attribute_data["unsigned"] : null;
			$unsigned = $unsigned == "1" || strtolower($unsigned) == "true"; //$ == "1" includes $ === true  too.
			$unique = isset($attribute_data["unique"]) ? $attribute_data["unique"] : null;
			$unique = $unique == "1" || strtolower($unique) == "true"; //$ == "1" includes $ === true  too.
			$null = isset($attribute_data["null"]) ? $attribute_data["null"] : null;
			$null = $null == "1" || strtolower($null) == "true"; //$ == "1" includes $ === true  too.
			$default = isset($attribute_data["default"]) ? $attribute_data["default"] : null;
			$default_type = isset($attribute_data["default_type"]) ? $attribute_data["default_type"] : null;
			$extra = isset($attribute_data["extra"]) ? $attribute_data["extra"] : null;
			//$charset = isset($attribute_data["charset"]) ? $attribute_data["charset"] : null; //Not used in mysql server
			$collation = isset($attribute_data["collation"]) ? $attribute_data["collation"] : null;
			$comment = isset($attribute_data["comment"]) ? $attribute_data["comment"] : null;
			
			if ($flags)
				foreach ($flags as $k => $v)
					if ($k != "charset")
						eval("\$$k = \$v;"); //may change the $auto_increment to true
			
			if ($auto_increment || stripos($extra, "auto_increment") !== false) {
				$extra = preg_replace("/(^|\s)auto_increment(\s|$)/i", " ", $extra);
				$auto_increment = true;
			}
			
			$length = !self::ignoreColumnTypeDBProp($type, "length") && (is_numeric($length) || preg_match("/^([0-9]+),([0-9]+)$/", $length)) ? $length : self::getMandatoryLengthForColumnType($type);
			$auto_increment = !self::ignoreColumnTypeDBProp($type, "auto_increment") ? $auto_increment : null;
			$unsigned = !self::ignoreColumnTypeDBProp($type, "unsigned") ? $unsigned : false;
			$unique = !self::ignoreColumnTypeDBProp($type, "unique") ? $unique : false;
			$null = !self::ignoreColumnTypeDBProp($type, "null") ? $null : null;
			$default = !self::ignoreColumnTypeDBProp($type, "default") ? $default : null;
			//$charset = !self::ignoreColumnTypeDBProp($type, "charset") ? $charset : null; //not used in mysql server
			$collation = !self::ignoreColumnTypeDBProp($type, "collation") ? $collation : null;
			$comment = !self::ignoreColumnTypeDBProp($type, "comment") ? $comment : null;
			
			//Prepare default value
			$is_reserved_word = self::isReservedWord($default); //check if is a reserved word
			$contains_reserved_word = self::isReservedWordFunction($default); //check if contains a function
			$is_numeric_type = in_array($type, self::getDBColumnNumericTypes());
			$default = isset($default) && $is_numeric_type && !is_numeric($default) && !$is_reserved_word && !$contains_reserved_word ? null : $default; //remove default if numeric field and default is not numeric.
			
			//Doesn't need this bc mysql does this automatically.
			/*if (!isset($default) && isset($null) && !$null && !$pk) {
				$default = self::getDefaultValueForColumnType($type); //if not null set a default value
				
				//Do it again bc the $default changed
				$is_reserved_word = self::isReservedWord($default); //check if is a reserved word. 
				$contains_reserved_word = self::isReservedWordFunction($default); //check if contains a function
			}
			else*/
			//When an attribute is a DATE or a numeric and the default value is an empty string, the DB server gives an error, not inserting/updating the attribute with the new type, bc I cannot set a Date or numeric attribute with the default value: ''. This means we need to set the correct default the value, even if the attribute is NULL.
			if (isset($default) && is_string($default) && !strlen($default) && !$pk) {
				$default = self::getDefaultValueForColumnType($type); //set a default value with the correct value in case is an empty string
				
				//Do it again bc the $default changed
				$is_reserved_word = self::isReservedWord($default); //check if is a reserved word. 
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
			//$parsed_data["charset"] = $charset; //not used in mysql server
			$parsed_data["collation"] = $collation;
			$parsed_data["comment"] = $comment;
			
			//Prepare sql parameters
			$length = $length && empty($options["ignore_length"]) ? "($length)" : "";
			$auto_increment = $auto_increment && empty($options["ignore_auto_increment"]) ? "AUTO_INCREMENT" : "";
			$unsigned = $unsigned && empty($options["ignore_unsigned"]) ? "unsigned" : "";
			$unique = $unique && empty($options["ignore_unique"]) && !$pk ? "UNIQUE" : "";
			$null = isset($null) && empty($options["ignore_null"]) ? ($null ? "NULL" : "NOT NULL") : "";
			$default = isset($default) && empty($options["ignore_default"]) && !$auto_increment ? "DEFAULT " . ($default_type == "string" ? "'$default'" : $default) : "";
			$charset = "";//!empty($charset) && empty($options["ignore_charset"]) ? "CHARSET $charset" : ""; //not used in mysql server
			$collation = !empty($collation) && empty($options["ignore_collation"]) ? "COLLATE $collation" : "";
			$comment = !empty($comment) && empty($options["ignore_comment"]) ? "COMMENT '$comment'" : "";
			$extra = empty($options["ignore_extra"]) ? $extra : "";
			
			$suffix = !empty($options["suffix"]) ? $options["suffix"] : "";
			
			return trim(preg_replace("/[ ]+/", " ", "`$name` $type{$length} $unsigned $null $auto_increment $unique $charset $collation $default $extra $comment $suffix"));
		}
	}
	
	public static function getRenameTableStatement($old_table, $new_table, $options = false) {
		$sql_old_table = self::getParsedTableEscapedSQL($old_table, $options);
		$sql_new_table = self::getParsedTableEscapedSQL($new_table, $options);
		$suffix = $options && !empty($options["suffix"]) ? $options["suffix"] : "";
		
		return "RENAME TABLE $sql_old_table TO $sql_new_table $suffix";
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
		
		$after_attribute = isset($attribute_data["after"]) ? $attribute_data["after"] : null;
		$first_attribute = isset($attribute_data["first"]) ? $attribute_data["first"] : null;
		$insert_after_attribute_or_in_first_position = $after_attribute ? "AFTER `$after_attribute`" : ($first_attribute ? "FIRST" : "");
		
		return "ALTER TABLE $sql_table ADD COLUMN $sql $insert_after_attribute_or_in_first_position $suffix";
	}
	
	public static function getModifyTableAttributeStatement($table, $attribute_data, $options = false) {
		$sql_table = self::getParsedTableEscapedSQL($table, $options);
		$suffix = $options && !empty($options["suffix"]) ? $options["suffix"] : "";
		$sql = self::getCreateTableAttributeStatement($attribute_data);
		
		$after_attribute = isset($attribute_data["after"]) ? $attribute_data["after"] : null;
		$first_attribute = isset($attribute_data["first"]) ? $attribute_data["first"] : null;
		$insert_after_attribute_or_in_first_position = $after_attribute ? "AFTER `$after_attribute`" : ($first_attribute ? "FIRST" : "");
		
		return "ALTER TABLE $sql_table MODIFY COLUMN $sql $insert_after_attribute_or_in_first_position $suffix";
	}
	
	public static function getRenameTableAttributeStatement($table, $old_attribute, $new_attribute, $attribute_data = null, $options = false) {
		$sql_table = self::getParsedTableEscapedSQL($table, $options);
		$suffix = $options && !empty($options["suffix"]) ? $options["suffix"] : "";
		
		if ($attribute_data) {
			$attribute_data["name"] = $new_attribute;
			$sql = self::getCreateTableAttributeStatement($attribute_data);
			
			return "ALTER TABLE $sql_table CHANGE `$old_attribute` $sql $suffix"; //This statement is allowed in all the Mysql versions
		}
		else
			return "ALTER TABLE $sql_table RENAME COLUMN `$old_attribute` TO `$new_attribute` $suffix"; //This statement is only allowed from Mysql v8. The Mysql v5 does NOT allow the RENAME COLUMN statement!
	}
	
	public static function getDropTableAttributeStatement($table, $attribute, $options = false) {
		$sql_table = self::getParsedTableEscapedSQL($table, $options);
		$suffix = $options && !empty($options["suffix"]) ? $options["suffix"] : "";
		return "ALTER TABLE $sql_table DROP COLUMN `$attribute` $suffix";
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
		
		return "ALTER TABLE $sql_table ADD CONSTRAINT $constraint_name PRIMARY KEY (`" . implode("`, `", $attributes_name) . "`) $suffix";
	}
	
	public static function getDropTablePrimaryKeysStatement($table, $options = false) {
		$sql_table = self::getParsedTableEscapedSQL($table, $options);
		$suffix = $options && !empty($options["suffix"]) ? $options["suffix"] : "";
		return "ALTER TABLE $sql_table DROP PRIMARY KEY $suffix";
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
			
			$attrs = is_array($attr_name) ? $attr_name : array($attr_name);
			$ref_attrs = is_array($ref_attr_name) ? $ref_attr_name : array($ref_attr_name);
			$sql_reference_table = self::getParsedTableEscapedSQL($ref_table_name, $options);
			
			return "ALTER TABLE $sql_table ADD FOREIGN KEY " . $constraint_name . " (`" . implode('`, `', $attrs) . "`) REFERENCES " . $sql_reference_table . " (`" . implode('`, `', $ref_attrs) . "`) $on_delete $on_update $suffix";
		}
	}
	
	//used in CMSDeploymentHandler
	public static function getDropTableForeignKeysStatement($table, $options = false) {
		$table_props = self::parseTableName($table, $options);
		$table = isset($table_props["name"]) ? $table_props["name"] : null;
		$suffix = $options && !empty($options["suffix"]) ? $options["suffix"] : "";
		
		return "DROP PROCEDURE IF EXISTS dropAllDBForeignKeys;
DELIMITER ;;
CREATE PROCEDURE dropAllDBForeignKeys()
BEGIN
  DECLARE bDone INT;
  DECLARE sql_str VARCHAR(1000);

  DECLARE curs CURSOR FOR SELECT 
	  CONCAT('ALTER TABLE ', TABLE_NAME, ' DROP FOREIGN KEY ', CONSTRAINT_NAME, '$suffix;') AS 'drop_sql'
	FROM information_schema.key_column_usage 
	WHERE CONSTRAINT_SCHEMA = DATABASE() AND referenced_table_name IS NOT NULL AND TABLE_NAME='$table';
  DECLARE CONTINUE HANDLER FOR NOT FOUND SET bDone = 1;

  OPEN curs;

  SET bDone = 0;
  REPEAT
    FETCH curs INTO sql_str;

    IF sql_str != '' THEN
	  SET @sql = sql_str;
	  PREPARE stmt FROM @sql;
	  EXECUTE stmt;
	  DEALLOCATE PREPARE stmt;
    END IF;
  UNTIL bDone END REPEAT;

  CLOSE curs;
END;;
DELIMITER ;

CALL dropAllDBForeignKeys();
DROP PROCEDURE IF EXISTS dropAllDBForeignKeys;";
	}

	public static function getDropTableForeignConstraintStatement($table, $constraint_name, $options = false) {
		$sql_table = self::getParsedTableEscapedSQL($table, $options);
		
		$table_props = self::parseTableName($table, $options);
		$table = isset($table_props["name"]) ? $table_props["name"] : null;
		
		//no need bc we are disabling the foreign keys on drop table and CASCADE option.
		return "DROP PROCEDURE IF EXISTS dropTableForeignKey;
DELIMITER ;;
CREATE PROCEDURE dropTableForeignKey()
BEGIN
  IF (SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name='$table') = 1
  THEN 
  	IF (SELECT COUNT(*) FROM information_schema.key_column_usage WHERE referenced_table_name is not null AND table_schema = DATABASE() AND table_name='$table' AND constraint_name='$constraint_name') >= 1
  	THEN 
	  	ALTER TABLE $sql_table DROP FOREIGN KEY `$constraint_name`;
  	END IF;
  END IF;
END;;
DELIMITER ;

CALL dropTableForeignKey();
DROP PROCEDURE IF EXISTS dropTableForeignKey;";
	}
	
	//used in CMSModuleInstallationHandler
	public static function getAddTableIndexStatement($table, $attributes, $options = false) {
		$sql_table = self::getParsedTableEscapedSQL($table, $options);
		$suffix = $options && !empty($options["suffix"]) ? $options["suffix"] : "";
		$attributes = is_array($attributes) ? $attributes : array($attributes);
		
		return "ALTER TABLE $sql_table ADD INDEX (`" . implode("`, `", $attributes) . "`) $suffix";
	}
	
	public static function getLoadTableDataFromFileStatement($file_path, $table, $options = false) {
		//http://dev.mysql.com/doc/refman/5.1/en/load-data.html
		$sql_table = self::getParsedTableEscapedSQL($table, $options);
		$suffix = $options && !empty($options["suffix"]) ? $options["suffix"] : "";
		$attributes = $options && !empty($options["attributes"]) ? "(" . (is_array($options["attributes"]) ? implode(", ", $options["attributes"]) : $options["attributes"]) . ")" : "";
		$fields_delimiter = !empty($options["fields_delimiter"]) ? $options["fields_delimiter"] : "\t";
		$lines_delimiter = !empty($options["lines_delimiter"]) ? $options["lines_delimiter"] : "\r\n";
		
		return "LOAD DATA LOCAL INFILE '$file_path' INTO TABLE $sql_table FIELDS TERMINATED BY '$fields_delimiter' LINES TERMINATED BY '$lines_delimiter' $attributes $suffix";
	}
	
	public static function getShowCreateTableStatement($table, $options = false) {
		$sql_table = self::getParsedTableEscapedSQL($table, $options);
		return "SHOW CREATE TABLE $sql_table";
	}

	public static function getShowCreateViewStatement($view, $options = false) {
		$sql_view = self::getParsedTableEscapedSQL($view, $options);
		return "SHOW CREATE VIEW $sql_view";
	}

	public static function getShowCreateTriggerStatement($trigger, $options = false) {
		$sql_trigger = self::getParsedTableEscapedSQL($trigger, $options);
		return "SHOW CREATE TRIGGER $sql_trigger";
	}

	public static function getShowCreateProcedureStatement($procedure, $options = false) {
		$sql_procedure = self::getParsedTableEscapedSQL($procedure, $options);
		return "SHOW CREATE PROCEDURE $sql_procedure";
	}

	public static function getShowCreateFunctionStatement($function, $options = false) {
		$sql_function = self::getParsedTableEscapedSQL($function, $options);
		return "SHOW CREATE FUNCTION $sql_function";
	}

	public static function getShowCreateEventStatement($event, $options = false) {
		$sql_event = self::getParsedTableEscapedSQL($event, $options);
		return "SHOW CREATE EVENT $sql_event";
	}
	
	public static function getShowTablesStatement($db_name, $options = false) {
		return str_replace("\t", "", self::getTablesStatement($db_name, $options));
		/*return "SELECT TABLE_NAME AS table_name ".
			"FROM INFORMATION_SCHEMA.TABLES ".
			"WHERE TABLE_TYPE='BASE TABLE' AND TABLE_SCHEMA='$db_name'";*/
	}

	public static function getShowViewsStatement($db_name, $options = false) {
		return "SELECT TABLE_NAME AS table_name ".
			"FROM INFORMATION_SCHEMA.TABLES ".
			"WHERE TABLE_TYPE='VIEW' AND TABLE_SCHEMA='$db_name'";
	}

	public static function getShowTriggersStatement($db_name, $options = false) {
		return "SHOW TRIGGERS FROM `$db_name`;";
	}

	public static function getShowTableColumnsStatement($table, $db_name = false, $options = false) {
		return str_replace("\t", "", self::getTableFieldsStatement($table, $db_name, $options));
		//return "SHOW COLUMNS FROM `$table`;";
		/*return "SELECT 
			COLUMN_NAME as 'column_name', 
			DATA_TYPE as 'data_type', 
			COLUMN_TYPE as 'column_type', 
			COLUMN_DEFAULT as 'column_default', 
			IS_NULLABLE as 'is_nullable', 
			CHARACTER_MAXIMUM_LENGTH as 'character_maximum_length', 
			NUMERIC_PRECISION as 'numeric_precision', 
			CHARACTER_SET_NAME as 'character_set_name', 
			COLLATION_NAME as 'collation_name', 
			COLUMN_KEY as 'column_key', 
			EXTRA as 'extra', 
			IF(LOWER(COLUMN_KEY) = 'pri', 1, 0) as is_primary,
			IF(LOWER(COLUMN_KEY) = 'uni', 1, 0) as is_unique
			FROM information_schema.COLUMNS 
			WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='$table'
			ORDER BY ORDINAL_POSITION ASC";*/
	}

	public static function getShowForeignKeysStatement($table, $db_name = false, $options = false) {
		return str_replace("\t", "", self::getForeignKeysStatement($table, $db_name, $options));
		/*return str_replace("\t", "", "SELECT 
			table_catalog AS 'catalog', 
			table_schema AS 'schema', 
			table_name AS 'child_table', 
			column_name AS 'child_column', 
			referenced_table_name AS 'parent_table', 
			referenced_column_name AS 'parent_column',
			constraint_name
			FROM information_schema.key_column_usage
			WHERE referenced_table_name is not null AND table_schema = DATABASE() AND table_name = '$table'");*/
	}

	public static function getShowProceduresStatement($db_name, $options = false) {
		return "SELECT SPECIFIC_NAME AS procedure_name ".
			"FROM INFORMATION_SCHEMA.ROUTINES ".
			"WHERE ROUTINE_TYPE='PROCEDURE' AND ROUTINE_SCHEMA='$db_name'";
	}

	public static function getShowFunctionsStatement($db_name, $options = false) {
		return "SELECT SPECIFIC_NAME AS function_name ".
			"FROM INFORMATION_SCHEMA.ROUTINES ".
			"WHERE ROUTINE_TYPE='FUNCTION' AND ROUTINE_SCHEMA='$db_name'";
	}

	public static function getShowEventsStatement($db_name, $options = false) {
		return "SELECT EVENT_NAME AS event_name ".
			"FROM INFORMATION_SCHEMA.EVENTS ".
			"WHERE EVENT_SCHEMA='$db_name'";
	}

	public static function getSetupTransactionStatement($options = false) {
		return "SET SESSION TRANSACTION ISOLATION LEVEL REPEATABLE READ";
	}

	public static function getStartTransactionStatement($options = false) {
		return "START TRANSACTION ".
		"/*!40100 WITH CONSISTENT SNAPSHOT */";
	}

	public static function getCommitTransactionStatement($options = false) {
		return "COMMIT";
	}
	
	public static function getStartDisableAutocommitStatement($options = false) {
		return "SET autocommit=0;";
	}

	public static function getEndDisableAutocommitStatement($options = false) {
		return "COMMIT;";
	}

	public static function getStartLockTableWriteStatement($table, $options = false) {
		$sql_table = self::getParsedTableEscapedSQL($table, $options);
		return "LOCK TABLES $sql_table WRITE;";
	}

	public static function getStartLockTableReadStatement($table, $options = false) {
		$sql_table = self::getParsedTableEscapedSQL($table, $options);
		return "LOCK TABLES $sql_table READ LOCAL;";
	}

	public static function getEndLockTableStatement($options = false) {
		return "UNLOCK TABLES;";
	}

	public static function getStartDisableKeysStatement($table, $options = false) {
		$sql_table = self::getParsedTableEscapedSQL($table, $options);
		return "/*!40000 ALTER TABLE $sql_table DISABLE KEYS */;";
	}

	public static function getEndDisableKeysStatement($table, $options = false) {
		$sql_table = self::getParsedTableEscapedSQL($table, $options);
		return "/*!40000 ALTER TABLE $sql_table ENABLE KEYS */;";
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
