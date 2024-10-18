<?php
trait DBSQLConverter { 
	
	public static function convertObjectToDefaultSQL($data, $options = false) {
		return SQLQueryHandler::create($data);
	}
	
	public static function convertDefaultSQLToObject($sql, $options = false) {
		return SQLQueryHandler::parse($sql);
	}
	
	/*
	 * @param $table_name: string with table name
	 * @param $attributes: array with attributes and values, like: 
	 	array("attribute_name_1" => "attribute_value_1", ...)
	 */
	public static function buildDefaultTableInsertSQL($table_name, $attributes, $options = false) {
		$sql = null;
		
		if ($table_name && $attributes) {
			//version 1
			/*$attrs = array();
			
			if ($attributes)
				foreach($attributes as $key => $value)
					$attrs[] = array("column" => $key, "value" => $value);
			
			$sql = self::convertObjectToDefaultSQL(array(
				"type" => "insert",
				"main_table" => $table_name,
				"attributes" => $attrs
			));*/
		
			//version 2
			$sql_attrs = "";
			$sql_values = "";
			$check_reserved_values = isset($options["check_reserved_values"]) ? $options["check_reserved_values"] : true;
			
			foreach($attributes as $key => $value) {
				$sql_attrs .= (strlen($sql_attrs) ? ", " : "") . SQLQueryHandler::getParsedSqlColumnName($key);
				$sql_values .= (strlen($sql_values) ? ", " : "") . self::createBaseExprValue($value, $check_reserved_values);
			}
			
			if ($sql_attrs) 
				$sql = "INSERT INTO " . SQLQueryHandler::getParsedSqlTableName($table_name) . " ($sql_attrs) VALUES ($sql_values)";
		}
		
		//error_log("buildDefaultTableInsertSQL:$sql\n\n", 3,  "/var/www/html/livingroop/default/tmp/test.log");
		return $sql;
	}
	
	/*
	 * @param $table_name: string with table name
	 * @param $attributes: array with attributes and values, like: 
	 	array("attribute_name_1" => "attribute_value_1", ...)
	 * @param $conditions: array with conditions. See more info about this in the getSQLConditions method
	 * @param $options: array with: 
	 	array(
	 		"conditions_join" => null/"and"/"or"
	 		"all" => true/false
	 	)
	 */
	public static function buildDefaultTableUpdateSQL($table_name, $attributes, $conditions = false, $options = false) {
		$sql = null;
		
		if ($table_name && $attributes) {
			$options = is_array($options) ? $options : array();
			$conditions_join = isset($options["conditions_join"]) ? $options["conditions_join"] : null;
			$all = isset($options["all"]) ? $options["all"] : null;
			$extra_sql_conditions = isset($options["sql_conditions"]) ? $options["sql_conditions"] : null;
			$check_reserved_values = isset($options["check_reserved_values"]) ? $options["check_reserved_values"] : true;
			
			$sql_conditions = self::getSQLConditions($conditions, $conditions_join, "", $check_reserved_values);
			$sql_conditions .= $extra_sql_conditions ? ($sql_conditions ? " AND " : "") . $extra_sql_conditions : "";
			
			if ($sql_conditions || $all) {
				//version 1
				/*$attrs = array();
				
				if ($attributes)
					foreach($attributes as $key => $value)
						$attrs[] = array("column" => $key, "value" => $value);
				
				$sql = self::convertObjectToDefaultSQL(array(
					"type" => "update",
					"main_table" => $table_name,
					"attributes" => $attrs,
				));
				
				if ($sql && $sql_conditions)
					$sql .= " WHERE {$sql_conditions}";*/
				
				//version 2
				$sql_attrs = "";
				
				foreach($attributes as $key => $value)
					$sql_attrs .= ($sql_attrs ? ", " : "") . SQLQueryHandler::getParsedSqlColumnName($key) . "=" . self::createBaseExprValue($value, $check_reserved_values);
				
				$sql_where = $sql_conditions ? " WHERE {$sql_conditions}" : "";
				$sql = "UPDATE " . SQLQueryHandler::getParsedSqlTableName($table_name) . " SET {$sql_attrs}{$sql_where}";
			}
		}
		
		//error_log("buildDefaultTableUpdateSQL:$sql\n\n", 3,  "/var/www/html/livingroop/default/tmp/test.log");
		return $sql;
	}
	
	/*
	 * @param $table_name: string with table name
	 * @param $conditions: array with conditions. See more info about this in the getSQLConditions method
	 * @param $options: array with: 
	 	array(
	 		"conditions_join" => null/"and"/"or"
	 		"all" => true/false
	 	)
	 */
	public static function buildDefaultTableDeleteSQL($table_name, $conditions = false, $options = false) {
		$sql = null;
		
		if ($table_name) {
			$options = is_array($options) ? $options : array();
			$conditions_join = isset($options["conditions_join"]) ? $options["conditions_join"] : null;
			$all = isset($options["all"]) ? $options["all"] : null;
			$extra_sql_conditions = isset($options["sql_conditions"]) ? $options["sql_conditions"] : null;
			$check_reserved_values = isset($options["check_reserved_values"]) ? $options["check_reserved_values"] : true;
			
			$sql_conditions = self::getSQLConditions($conditions, $conditions_join, "", $check_reserved_values);
			$sql_conditions .= $extra_sql_conditions ? ($sql_conditions ? " AND " : "") . $extra_sql_conditions : "";
			
			if($sql_conditions || $all) {
				$sql_where = $sql_conditions ? " WHERE {$sql_conditions}" : "";
				$sql = "DELETE FROM " . SQLQueryHandler::getParsedSqlTableName($table_name) . $sql_where;
			}
		}
		//error_log("buildDefaultTableDeleteSQL:$sql\n\n", 3,  "/var/www/html/livingroop/default/tmp/test.log");
		
		return $sql;
	}
	
	/*
	 * @param $table_name: string with table name
	 * @param $attributes: array with attributes and values, like: 
	 	array("attribute_name_1", ...)
	 * @param $conditions: array with conditions. See more info about this in the getSQLConditions method
	 * @param $options: array with: 
	 	array(
	 		"conditions_join" => null/"and"/"or"
	 		"sorts" => array(
	 			array("column" => "attribute_name_1", "order" => "asc"),
	 			...
	 		)
	 	)
	 */
	public static function buildDefaultTableFindSQL($table_name, $attributes = false, $conditions = false, $options = false) {
		$sql = null;
		
		if ($table_name) {
			$options = is_array($options) ? $options : array();
			$conditions_join = isset($options["conditions_join"]) ? $options["conditions_join"] : null;
			$sorts = isset($options["sorts"]) ? $options["sorts"] : null;
			$extra_sql_conditions = isset($options["sql_conditions"]) ? $options["sql_conditions"] : null;
			$check_reserved_values = isset($options["check_reserved_values"]) ? $options["check_reserved_values"] : true;
			
			$sql_conditions = self::getSQLConditions($conditions, $conditions_join, "", $check_reserved_values);
			$sql_conditions .= $extra_sql_conditions ? ($sql_conditions ? " AND " : "") . $extra_sql_conditions : "";
			$sql_sort = self::getSQLSort($sorts);
			
			//version 1
			/*$attrs = array();
			
			if ($attributes)
				foreach($attributes as $key => $value)
					$attrs[] = array("table" => , "column" => $key, "name" => $key != $value ? $value : null);
			
			$sql = self::convertObjectToDefaultSQL(array(
				"type" => "select",
				"main_table" => $table_name,
				"attributes" => $attrs,
			));
			
			if ($sql) {
				if ($sql_conditions)
					$sql .= " WHERE {$sql_conditions}";
				
				if ($sql_sort)
					$sql .= " ORDER BY {$sql_sort}";
			}*/
			
			//version 2
			$sql_attrs = self::getSQLAttributes($attributes);
			
			$sql = "SELECT {$sql_attrs} FROM " . SQLQueryHandler::getParsedSqlTableName($table_name);
			$sql .= $sql_conditions ? " WHERE {$sql_conditions}" : "";
			$sql .= $sql_sort ? " ORDER BY {$sql_sort}" : "";
		}
		//error_log("buildDefaultTableFindSQL:$sql\n\n", 3,  "/var/www/html/livingroop/default/tmp/test.log");
		
		return $sql;
	}
	
	/*
	 * @param $table_name: string with table name
	 * @param $conditions: array with conditions. See more info about this in the getSQLConditions method
	 * @param $options: array with: 
	 	array(
	 		"conditions_join" => null/"and"/"or"
	 	)
	 */
	public static function buildDefaultTableCountSQL($table_name, $conditions = false, $options = false) {
		$sql = null;
		
		if ($table_name) {
			$options = is_array($options) ? $options : array();
			$conditions_join = isset($options["conditions_join"]) ? $options["conditions_join"] : null;
			$extra_sql_conditions = isset($options["sql_conditions"]) ? $options["sql_conditions"] : null;
			$check_reserved_values = isset($options["check_reserved_values"]) ? $options["check_reserved_values"] : true;
			
			$sql_conditions = self::getSQLConditions($conditions, $conditions_join, "", $check_reserved_values);
			$sql_conditions .= $extra_sql_conditions ? ($sql_conditions ? " AND " : "") . $extra_sql_conditions : "";
			
			$sql = "SELECT count(*) AS total FROM " . SQLQueryHandler::getParsedSqlTableName($table_name);
			$sql .= $sql_conditions ? " WHERE {$sql_conditions}" : "";
		
		}
		//error_log("buildDefaultTableCountSQL:$sql\n\n", 3,  "/var/www/html/livingroop/default/tmp/test.log");
		
		return $sql;
	}
	
	/*
	 * @param $table_name: string with table name
	 * @param $rel_elm: array with:
 		array(
 			"keys" => ...
 			"attributes" => ...
 			"conditions" => ...
 			"groups_by" => ...
 			"sorts" => ...
 		)
	 * @param $parent_conditions: array with conditions, like
	 	array(
	 		"attribute_name_1" => "attribute_value_1", 
	 		...
	 	)
	 * @param $options: array with: 
	 	array(
	 		"sorts" => array(
	 			array("column" => "attribute_name_1", "order" => "asc"),
	 			...
	 		)
	 	)
	 */
	public static function buildDefaultTableFindRelationshipSQL($table_name, $rel_elm, $parent_conditions = false, $options = false) {
		$sql = null;
		
		if ($table_name && $rel_elm) {
			$options = is_array($options) ? $options : array();
			$keys = isset($rel_elm["keys"]) ? $rel_elm["keys"] : null;
			$attributes = isset($rel_elm["attributes"]) ? $rel_elm["attributes"] : null;
			$conditions = isset($rel_elm["conditions"]) ? $rel_elm["conditions"] : null;
			$groups_by = isset($rel_elm["groups_by"]) ? $rel_elm["groups_by"] : null;
			$sorts = !empty($options["sorts"]) && empty($rel_elm["sorts"]) ? $options["sorts"] : (isset($rel_elm["sorts"]) ? $rel_elm["sorts"] : null);
			$extra_sql_conditions = isset($options["sql_conditions"]) ? $options["sql_conditions"] : null;
			$check_reserved_values = isset($options["check_reserved_values"]) ? $options["check_reserved_values"] : true;
			
			$sql_conditions = self::getSQLRelationshipConditions($conditions, $table_name, $parent_conditions, $check_reserved_values);
			$sql_conditions .= $extra_sql_conditions ? ($sql_conditions ? " AND " : "") . $extra_sql_conditions : "";
			$sql_groups_by = self::getSQLRelationshipGroupBy($groups_by, $table_name);
			$sql_sort = self::getSQLRelationshipSort($sorts, $table_name, ($sql_groups_by ? true : false));
			
			$sql = "SELECT ";
			$sql .= self::getSQLRelationshipAttributes($attributes, $table_name, $keys);
			$sql .= " FROM " . SQLQueryHandler::getParsedSqlTableName($table_name) . " " . self::getSQLRelationshipJoins($keys, $table_name, $check_reserved_values);
			$sql .= $sql_conditions || $extra_sql_conditions ? " WHERE $sql_conditions" : "";
			$sql .= $sql_groups_by ? " " . $sql_groups_by : "";
			
			if($sql_groups_by && $sql_sort) 
				$sql = "SELECT * FROM ({$sql}) Z ORDER BY {$sql_sort}";
			elseif($sql_sort) 
				$sql .= " ORDER BY {$sql_sort}";
		}
		//error_log("buildDefaultTableFindRelationshipSQL:$sql\n\n", 3,  "/var/www/html/livingroop/default/tmp/test.log");
		
		return $sql;
	}
	
	/*
	 * @param $table_name: string with table name
	 * @param $rel_elm: array with:
 		array(
 			"keys" => ...
 			"attributes" => ...
 			"conditions" => ...
 			"groups_by" => ...
 		)
	 * @param $parent_conditions: array with conditions, like
	 	array(
	 		"attribute_name_1" => "attribute_value_1", 
	 		...
	 	)
	 * @param $options: none. N/A for now...
	 */
	public static function buildDefaultTableCountRelationshipSQL($table_name, $rel_elm, $parent_conditions = false, $options = false) {
		$sql = null;
		
		if ($table_name && $rel_elm) {
			$keys = isset($rel_elm["keys"]) ? $rel_elm["keys"] : null;
			$attributes = isset($rel_elm["attributes"]) ? $rel_elm["attributes"] : null; //is only used if groups_by exists
			$conditions = isset($rel_elm["conditions"]) ? $rel_elm["conditions"] : null;
			$groups_by = isset($rel_elm["groups_by"]) ? $rel_elm["groups_by"] : null;
			$extra_sql_conditions = isset($options["sql_conditions"]) ? $options["sql_conditions"] : null;
			$check_reserved_values = isset($options["check_reserved_values"]) ? $options["check_reserved_values"] : true;
			
			$sql_conditions = self::getSQLRelationshipConditions($conditions, $table_name, $parent_conditions, $check_reserved_values);
			$sql_conditions .= $extra_sql_conditions ? ($sql_conditions ? " AND " : "") . $extra_sql_conditions : "";
			$sql_group_by = self::getSQLRelationshipGroupBy($groups_by, $table_name);
			
			$sql = " FROM " . SQLQueryHandler::getParsedSqlTableName($table_name) . " " . self::getSQLRelationshipJoins($keys, $table_name, $check_reserved_values);
			$sql .= $sql_conditions ? " WHERE {$sql_conditions}" : "";
			$sql .= $sql_group_by ? " " . $sql_group_by : "";
			
			if($sql_group_by)
				$sql = "SELECT count(*) AS total FROM (
					SELECT " . self::getSQLRelationshipAttributes($attributes, $table_name, $keys) . "
					$sql
				) Z";
			else
				$sql = "SELECT count(*) AS total " . $sql;
		}
		//error_log("buildDefaultTableCountRelationshipSQL:$sql\n\n", 3,  "/var/www/html/livingroop/default/tmp/test.log");
		
		return $sql;
	}
	
	/*
	 * @param $table_name: string with table name
	 * @param $attribute_name: string with attribute name
	 * @param $options: none. N/A for now...
	 */
	public static function buildDefaultTableFindColumnMaxSQL($table_name, $attribute_name, $options = false) {
		$sql = "SELECT MAX(" . SQLQueryHandler::getParsedSqlColumnName($attribute_name) . ") AS max FROM " . SQLQueryHandler::getParsedSqlTableName($table_name);
		
		return $sql;
	}
	
	/**************************** RELATIONSHIPS ***************************/
	
	/*
	   getSQLRelationshipConditions: parses the following:
		<condition column="user_id" operator="!=">10</condition><!-- user_id belongs to the employee table -->
		<condition column="type"><table value="computer" /><operator><![CDATA[<=]]></operator><value>hp</value></condition><!-- type belongs to the computer table -->
		<condition column="xxx" table="ttt" operator="&gt;" refcolumn="yyy" reftable="www" value="10" />
		<condition><![CDATA[length(item.title) > 0 and item.status=1]]></condition>	
	*/
	//used too in app/__system/layer/presentation/phpframework/src/util/CMSPresentationFormSettingsUIHandler.php
	public static function getSQLRelationshipConditions($conditions, $table_name = false, $parent_conditions = false, $check_reserved_values = true) {
		$sql = "";
		
		if(is_array($parent_conditions)) 
			$sql .= ($sql ? " AND " : "") . self::getSQLConditions($parent_conditions, null, $table_name, $check_reserved_values);
			/*foreach($parent_conditions as $key => $value) 
				$sql .= ($sql ? " AND " : "") . self::prepareTableAttributeWithFunction($key, $table_name) . "=" . self::createBaseExprValue($value, $check_reserved_values);*/
		
		$t = $conditions ? count($conditions) : 0;
		$is_numeric_array = $t == 0 || ( array_keys($conditions) === range(0, $t - 1) );
		
		if (!$is_numeric_array) //if associative array
			$sql .= ($sql ? " AND " : "") . self::getSQLConditions($conditions, null, $table_name, $check_reserved_values);
		else
			for ($i = 0; $i < $t; $i++) {
				$condition = $conditions[$i];
				
				if (is_array($condition)) {
					$column = isset($condition["column"]) ? $condition["column"] : null;
					$table = !empty($condition["table"]) ? $condition["table"] : null;
					$operator = !empty($condition["operator"]) ? $condition["operator"] : "=";
					$value = isset($condition["value"]) ? $condition["value"] : null;
					$ref_column = isset($condition["refcolumn"]) ? $condition["refcolumn"] : null;
					$ref_table = isset($condition["reftable"]) ? $condition["reftable"] : null;
				
					if ($column) {
						//get table from column
						if (!$table)
							$table = SQLQueryHandler::getTableFromColumn($column);
						
						if (!$table)
							$table = $table_name;
						
						if ($ref_column) {
							$ref_table = $ref_table ? $ref_table : $table_name;
						
							$sql .= ($sql ? " AND " : "") . self::prepareTableAttributeWithFunction($column, $table) . " {$operator} " . self::prepareTableAttributeWithFunction($ref_column, $ref_table);
						}
					
						if (isset($condition["value"])) {
							$lo = strtolower($operator);
							
							if ($lo == "in" || $lo == "not in")
								$value = self::createBaseExprValueForOperatorIn($value, $check_reserved_values);
							else if ($lo == "is" || $lo == "is not")
								$value = self::createBaseExprValueForOperatorIs($value, $check_reserved_values);
							else
								$value = self::createBaseExprValue($value, $check_reserved_values);
							
							$cond = array(
								$column => array(
									"operator" => $operator,
									"value" => $value
								)
							);
							$sql .= ($sql ? " AND " : "") . self::getSQLConditions($cond, null, $table, $check_reserved_values);
							//$sql .= ($sql ? " AND " : "") . self::prepareTableAttributeWithFunction($column, $table) . " {$operator} " . self::createBaseExprValue($value, $check_reserved_values);
						}
					}
				}
				else
					$sql .= ($sql ? " AND " : "") . $condition;
			}
		
		return $sql;
	}
	
	/*
	   getSQLRelationshipSort: parses the following:
		<sort column="title" table="computer" order="asc" />
		<sort column="id" order="desc" />
		
		$sorts:
		- array("id", "name")
		- array("id" => "asc", "name" => "desc")
		- array(array("column" => "id"), array("column" => "name", "order" => "asc"))
	*/
	protected static function getSQLRelationshipSort($sorts, $table_name, $group_by = false) {
		$sql = "";
		
		if ($sorts)
			foreach ($sorts as $idx => $sort) {
				if (is_array($sort)) {
					$column = isset($sort["column"]) ? $sort["column"] : null;
					$order = isset($sort["order"]) ? $sort["order"] : null;
					$table = !empty($sort["table"]) ? $sort["table"] : null;
					
					//get table from column
					if ($column && !$table)
						$table = SQLQueryHandler::getTableFromColumn($column);
				}
				else {
					$column = is_numeric($idx) ? $sort : $idx; //where $sorts is an associative array($column => $order);
					$order = is_numeric($idx) ? "" : $sort;
					$table = null;
					
					//get table from column
					if ($column)
						$table = SQLQueryHandler::getTableFromColumn($column);
				}
				
				if($column) {
					if (!$table)
						$table = $table_name;
					
					$field = $group_by ? $column : self::prepareTableAttributeWithFunction($column, $table);
					$sql .= ($sql ? ", " : "") . "{$field} {$order}";
				}
			}
		
		return $sql;
	}
	
	/*
	   getSQLRelationshipAttributes: parses the following:
	   	<attribute name="name" column="title" table="computer" />
		<attribute column="*" table="item" />
		
		$attrs:
		- array("id", "name")
		- array("id" => "", "name" => "title")
		- array(array("column" => "id"), array("column" => "name", "name" => "title"))
	*/
	protected static function getSQLRelationshipAttributes($attrs, $table_name, $keys) {
		$sql = "";
		
		if ($attrs)
			foreach ($attrs as $idx => $attr) {
				if (is_array($attr)) {
					$name = !empty($attr["name"]) ? $attr["name"] : (isset($attr["column"]) ? $attr["column"] : null);
					$column = isset($attr["column"]) ? $attr["column"] : null;
					$table = !empty($attr["table"]) ? $attr["table"] : null;
					
					//get table from column
					if ($column && !$table)
						$table = SQLQueryHandler::getTableFromColumn($column);
				}
				else {
					$name = $attr;
					$column = is_numeric($idx) ? $attr : $idx; //where $attrs is an associative array($column => $name)
					$table = null;
					
					//get table from column
					if ($column)
						$table = SQLQueryHandler::getTableFromColumn($column);
				}
				
				if($column) {
					if (!$table)
						$table = $table_name;
					
					$sql .= (strlen($sql) ? ", " : "") . self::prepareTableAttributeWithFunction($column, $table) . ($column != "*" && $column != $name && $name ? " AS \"{$name}\"" : "");
				}
			}
		
		if (!$sql && $keys) {
			$t = count($keys);
			for($i = 0; $i < $t; $i++) {
				$key = $keys[$i];
				
				if (!empty($key["ftable"]))
					$sql .= ($sql ? ", " : "") . SQLQueryHandler::getParsedSqlTableName($key["ftable"]) . ".*";
			}
			//print_r($attrs);echo"table:$table_name";print_r($keys);die($sql);
		}
		
		return $sql ? $sql : "*";
	}
	
	/*
	  getSQLRelationshipJoins: Parses the following:
	  	<key pcolumn="user_id" fcolumn="employee_id" ftable="employee_computer" join="left" />
		<key pcolumn="id" ptable="employee_computer" fcolumn="computer_id" ftable="computer" />
		<key pcolumn="title" ptable="employee_computer">jp</key>
		<key pcolumn="title" ptable="employee_computer" join="left">jp left</key>
		<key fcolumn="computer_model" ftable="computer" value="hp" />
		<key pcolumn="id" ptable="employee_computer" fcolumn="computer_id" ftable="computer" value="1" operator="!=" />
		<key pcolumn="yyy" ptable="item" fcolumn="yyy" ftable="item" />
		<key pcolumn="xxx" value="xxx" />
		<key pcolumn="www" ptable="item" value="www" />
		<key fcolumn="ttt" ftable="item" value="ttt" />
			
		$key["join"] can have the following values:
		- inner
		- left
		- right
	*/
	protected static function getSQLRelationshipJoins($keys, $table_name, $check_reserved_values = true) {
		$joins = array();
		
		$t = $keys ? count($keys) : 0;
		for($i = 0; $i < $t; $i++) {
			$key = $keys[$i];
			
			$p_table = !empty($key["ptable"]) ? $key["ptable"] : $table_name;
			$p_column = isset($key["pcolumn"]) ? $key["pcolumn"] : null;
			$f_table = isset($key["ftable"]) ? $key["ftable"] : null;
			$f_column = isset($key["fcolumn"]) ? $key["fcolumn"] : null;
			$join = !empty($key["join"]) ? strtoupper($key["join"]) : "inner";
			$operator = isset($key["operator"]) ? $key["operator"] : null;
			$value = isset($key["value"]) ? $key["value"] : null;
			
			$value_exists = isset($key["value"]) && strlen($key["value"]);
			
			$operator = $operator ? $operator : "=";
			$lo = strtolower($operator);
			
			//get p_table from p_column
			if ($p_column && !$p_table)
				$p_table = SQLQueryHandler::getTableFromColumn($p_column);
			
			//get f_table from f_column
			if ($f_column && !$f_table)
				$f_table = SQLQueryHandler::getTableFromColumn($f_column);
			
			if ($value_exists) {
				if ($lo == "in" || $lo == "not in")
					$value = self::createBaseExprValueForOperatorIn($value, $check_reserved_values);
				else if ($lo == "is" || $lo == "is not")
					$value = self::createBaseExprValueForOperatorIs($value, $check_reserved_values);
				else
					$value = self::createBaseExprValue($value, $check_reserved_values);
			}
			
			$join_keys = array();
			$join_key_index = null;
			
			if ($f_column && $f_table) {
				$table_alias = $f_table == $table_name ? $f_table . "_aux" : SQLQueryHandler::getAlias($f_table);
				$join_key_index = " {$join} JOIN " . SQLQueryHandler::getParsedSqlTableName($f_table) . ($table_alias != $f_table ? " {$table_alias}" : "") . " ON ";
				
				//Add new inner join table in case of multiple tables in joins
				$join_key_index_aux = " {$join} JOIN " . SQLQueryHandler::getParsedSqlTableName($p_table) . " ON ";
				if (!empty($joins[ $join_key_index ]) && $p_table && $p_table != $table_name && empty($joins[ $join_key_index_aux ])) {
					$join_key_index = $join_key_index_aux;
				}
				
				$join_keys = isset($joins[ $join_key_index ]) && is_array($joins[ $join_key_index ]) ? $joins[ $join_key_index ] : array();
				
				if($p_column) {
					$join_sql = " " . self::prepareTableAttributeWithFunction($f_column, $table_alias) . " $operator " . self::prepareTableAttributeWithFunction($p_column, $p_table);
					if(!in_array($join_sql, $join_keys)) {
						$join_keys[] = $join_sql;
					}
				}
				
				if ($value_exists) {
					$join_sql = " " . self::prepareTableAttributeWithFunction($f_column, $table_alias) . " $operator {$value}";
					if (!in_array($join_sql, $join_keys)) {
						$join_keys[] = $join_sql;
					}
					
					if ($p_column) {
						$join_sql = " " . self::prepareTableAttributeWithFunction($p_column, $p_table) . " $operator {$value}";
						if(!in_array($join_sql, $join_keys)) {
							$join_keys[] = $join_sql;
						}
					}
				}
			}
			else if ($p_column && $value_exists) {
				$table_alias = $p_table == $table_name ? $p_table . "_aux" : SQLQueryHandler::getAlias($p_table);
				$join_key_index = " {$join} JOIN " . SQLQueryHandler::getParsedSqlTableName($p_table) . ($table_alias != $p_table ? " {$table_alias}" : "") . " ON ";
				
				$join_keys = isset($joins[ $join_key_index ]) && is_array($joins[ $join_key_index ]) ? $joins[ $join_key_index ] : array();
				
				$join_sql = " " . self::prepareTableAttributeWithFunction($p_column, $table_alias) . " $operator {$value}";
				if(!in_array($join_sql, $join_keys)) {
					$join_keys[] = $join_sql;
				}
			}
			else if ($f_column && $value_exists) {
				$table_alias = $f_table == $table_name ? $f_table . "_aux" : SQLQueryHandler::getAlias($f_table);
				$join_key_index = " {$join} JOIN " . SQLQueryHandler::getParsedSqlTableName($f_table) . ($table_alias != $f_table ? " {$table_alias}" : "") . " ON ";
				
				$join_keys = isset($joins[ $join_key_index ]) && is_array($joins[ $join_key_index ]) ? $joins[ $join_key_index ] : array();
				
				$join_sql = " " . self::prepareTableAttributeWithFunction($f_column, $table_alias) . " $operator {$value}";
				if(!in_array($join_sql, $join_keys)) {
					$join_keys[] = $join_sql;
				}
			}
			
			if(count($join_keys))
				$joins[ $join_key_index ] = $join_keys;
		}
		
		$sql = "";
		foreach($joins as $join_table => $join_keys) 
			$sql .= $join_table . implode(" AND ", $join_keys);
		
		return $sql;
	}
	
	/*
	  getSQLRelationshipGroupBy: Parses the following:
	  	<group_by column="id" table="item" />
		<group_by column="id" table="item" having="max(item.id) = 1" />
		<group_by column="id" table="item">
			<having><![CDATA[max(item.id) > 1 and min(item.id)<10]]></having>
		</group_by>
	*/
	protected static function getSQLRelationshipGroupBy($group_by, $table_name) {
		$sql_group_by = "";
		$sql_having = "";
		
		$repeated_group_by_fields = array();
		
		if ($group_by)
			foreach ($group_by as $group_by_item) {
				if (is_array($group_by_item)) {
					$column = isset($group_by_item["column"]) ? $group_by_item["column"] : null;
					$having = isset($group_by_item["having"]) ? $group_by_item["having"] : null;
					$table = !empty($group_by_item["table"]) ? $group_by_item["table"] : null;
					
					//get table from column
					if ($column && !$table)
						$table = SQLQueryHandler::getTableFromColumn($column);
				}
				else {
					$column = $group_by_item;
					$having = "";
					$table = null;
					
					//get table from column
					if ($column)
						$table = SQLQueryHandler::getTableFromColumn($column);
				}
				
				if($column) {
					if (!$table)
						$table = $table_name;
					
					$group_by_field = self::prepareTableAttributeWithFunction($column, $table);
					
					if(!in_array($group_by_field, $repeated_group_by_fields)) {
						$repeated_group_by_fields[] = $group_by_field;
						$sql_group_by .= ($sql_group_by ? ", " : "") . $group_by_field;
					}
					$sql_having .= ($sql_having ? " AND " : "") . $having;
				}
			}
		
		$sql = "";
		if($sql_group_by) {
			$sql .= " GROUP BY " . $sql_group_by;
			
			if($sql_having) {
				$sql .= " HAVING " . $sql_having;
			}
		}
		return $sql;
	}
	
	/**************************** SQL ***************************/
	
	protected static function getSQLAttributes($attributes) {
		$sql = "";
		
		if (is_array($attributes) && count($attributes)) {
			$is_numeric_array = array_keys($attributes) === range(0, count($attributes) - 1);
			
			foreach($attributes as $attr_name => $attr_alias) {
				if ($is_numeric_array) //if $attributes is a numeric array, $attr_name is a numeric key and $attr_alias is the real attribute name.
					$attr_name = $attr_alias;
				
				if($attr_name) {
					$attr = self::prepareTableAttributeName($attr_name);
					
					$sql .= (strlen($sql) ? ", " : "") . $attr . ($attr_alias && $attr_alias != $attr_name ? " AS \"" . $attr_alias . "\"" : "");
				}
			}
		}
		else
			$sql = "*";
		
		return $sql;
	}
	
	/*public static function getSQLConditions($conditions) {
		$sql = "";
		if(is_array($conditions)) {
			foreach($conditions as $key => $value) {
				$sql .= ($sql ? " AND " : "") . $key . "=" . self::createBaseExprValue($value);
			}
		}
		return $sql;
	}*/
	
	/*
	$join = "and"; //$join= "or";
	$conditions = array(
		"age" => array(array("operator" => ">", "value" => 21), array("operator" => "<=", "value" => 24)),
		//"age" => array(22,23,24),
		
		"skin" => array("operator" => "in", "value" => "brown, 1"),
		//"skin" => array("operator" => "in", "value" => array("brown", 1)),
		//"skin" => array("operator" => "like", "value" => "br%n"),
		
		"or" => array(
			"end_date" => array("operator" => ">", "value" => date("Y-m-d H:i:00")),
			"and" => array(
				"end_date" => "0000-00-00 00:00:00",
				"begin_date" => array("operator" => ">", "value" => date("Y-m-d H:i:00")),
			),
		)
		//"or" => array(
		//	"AND" => array(
		//		"is_active" => 1,
		//		"is_white" => 0
		//	),
		//),
		
		"aNd" => "name like 'jo%o'",
	);
	
	$conditions = array(
		"a.type" => $_POST["type"] ? $_POST["type"] : 0,
		"a.employee_id" => $_POST["employee_id"],
		"a.appointment_id" => array(
			"value" => $_POST["appointment_id"] ? $_POST["appointment_id"] : 0,
			"operator" => "!="
		),
		"or" => array(
			0 => array(
				"and" => array(
					"da.begin_date" => array(
						"value" => $_POST["begin_date"],
						"operator" => ">="
					),
					"da.end_date" => array(
						"value" => $_POST["begin_date"],
						"operator" => ">="
					)
				)
			),
			1 => array(
				"and" => array(
					"da.begin_date" => array(
						"value" => $_POST["end_date"],
						"operator" => ">="
					),
					"da.end_date" => array(
						"value" => $_POST["end_date"],
						"operator" => ">="
					)
				)
			)
		)
	);
	R: `a`.`type` = 0 AND `a`.`employee_id` = '2' AND `a`.`appointment_id` != 0 AND (((`da`.`begin_date` >= '2019-06-25 08:30:00' AND `da`.`end_date` >= '2019-06-25 08:30:00')) OR ((`da`.`begin_date` >= '2019-06-25 08:45:00' AND `da`.`end_date` >= '2019-06-25 08:45:00')))
	
	$conditions = array(
		"a.type" => $_POST["type"] ? $_POST["type"] : 0,
		"a.employee_id" => $_POST["employee_id"],
		"a.appointment_id" => array(
			"value" => $_POST["appointment_id"] ? $_POST["appointment_id"] : 0,
			"operator" => "!="
		),
		"or" => array(
			0 => array(
				"and" => array(
					"da.begin_date" => array(
						"value" => $_POST["begin_date"],
						"operator" => "<="
					),
					"da.end_date" => array(
						"value" => $_POST["begin_date"],
						"operator" => ">="
					)
				)
			),
			"and" => array(
				"da.begin_date" => array(
					0 => array(
						"value" => $_POST["begin_date"],
						"operator" => ">="
					),
					1 => array(
						"value" => $_POST["end_date"],
						"operator" => "<="
					)
				)
			)
		)
	);
	R: `a`.`type` = 0 AND `a`.`employee_id` = '2' AND `a`.`appointment_id` != 0 AND (((`da`.`begin_date` <= '2019-06-25 08:20:00' AND `da`.`end_date` >= '2019-06-25 08:20:00')) OR (`da`.`begin_date` >= '2019-06-25 08:20:00' AND `da`.`begin_date` <= '2019-06-25 08:35:00'))
	*/
	public static function getSQLConditions($conditions, $join = false, $key_table_name = "", $check_reserved_values = true) {
		$sql = "";
		
		if (is_array($conditions)) {
			$join = $join ? strtoupper($join) : null;
			$join = $join == "AND" || $join == "OR" ? $join : "AND";
			
			foreach ($conditions as $key => $value) {
				$ukey = strtoupper($key);
				
				if ($ukey == "AND" || $ukey == "OR" || (is_numeric($key) && is_array($value))) {
					$sub_sql = is_array($value) ? self::getSQLConditions($value, $ukey, $key_table_name, $check_reserved_values) : (is_string($value) && $value ? $value : "");
					
					$sql .= $sub_sql ? ($sql ? " $join " : "") . "(" . $sub_sql . ")" : "";
				}
				else {
					$sql .= $sql ? " $join " : "";
					$key_str = self::prepareTableAttributeWithFunction($key, $key_table_name);
					
					if (is_array($value)) {
						$is_assoc = array_keys($value) !== range(0, count($value) - 1);
						
						if ($is_assoc)
							$value = array($value);
						
						$c = '';
						foreach ($value as $v) {
							$c .= ($c ? " $join " : "");
						
							if (is_array($v)) {
								$operator = "=";
								$val = "";
					
								foreach ($v as $k => $a) {
									$k = strtolower($k);
									
									if ($k == "operator")
										$operator = strtolower($a);
									else if ($k == "value")
										$val = $a;
								}
								
								if ($operator == "in" || $operator == "not in")
									$c .= "$key_str $operator " . self::createBaseExprValueForOperatorIn($val, $check_reserved_values);
								else if ($operator == "is" || $operator == "is not")
									$c .= "$key_str $operator " . self::createBaseExprValueForOperatorIs($val, $check_reserved_values);
								else
									$c .= "$key_str $operator " . self::createBaseExprValue($val, $check_reserved_values);
							}
							else
								$c .= "$key_str = " . self::createBaseExprValue($v, $check_reserved_values);
						}
						$sql .= $c;
					}
					else
						$sql .= "$key_str = " . self::createBaseExprValue($value, $check_reserved_values);
				}
			}
		}
		
		return $sql;
	}
	
	protected static function getSQLSort($sort) {
		$sql = "";
		
		if(is_array($sort) && count($sort)) {
			foreach($sort as $sort_item) {
				if(is_array($sort_item)) {
					$sort_column = "";
					$sort_order = "";
					foreach($sort_item as $key => $value) {
						switch(strtolower($key)) {
							case "column": $sort_column = $value; break;
							case "order": $sort_order = $value; break;
						}
					}
					
					if($sort_column)
						$sql .= ($sql ? ", " : "") . SQLQueryHandler::getParsedSqlColumnName($sort_column) . " {$sort_order}";
				}
			}
		}
		return $sql;
	}
	
	//parse attribute with functions like: lower(xxx)
	protected static function prepareTableAttributeWithFunction($attr_name, $table_name = false) {
		if (strpos($attr_name, "(") !== false) {
			$start_pos = strrpos($attr_name, "(") + 1;
			$end_pos = strpos($attr_name, ")", $start_pos);
			$end_pos = $end_pos >= $start_pos ? $end_pos : strlen($attr_name);
			
			$prev = substr($attr_name, 0, $start_pos);
			$real_attr_name = substr($attr_name, $start_pos, $end_pos - $start_pos);
			$next = substr($attr_name, $end_pos);
			
			$real_attr_name = self::prepareTableAttributeName($real_attr_name, $table_name);
			
			return $prev . $real_attr_name . $next;
		}
		
		return self::prepareTableAttributeName($attr_name, $table_name);
		
		/*
		$pos = strpos($key, ".");
		if ($pos > 0)
			$key_str = SQLQueryHandler::getParsedSqlTableName(substr($key, 0, $pos)) . "." . SQLQueryHandler::getParsedSqlColumnName(substr($key, $pos + 1));
		else
			$key_str = ($key_prefix ? SQLQueryHandler::getParsedSqlTableName($key_prefix) . "." : "") . SQLQueryHandler::getParsedSqlColumnName($key);
		*/
	}
	
	protected static function prepareTableAttributeName($attr_name, $table_name = false) {
		$attr_name = trim($attr_name);
		$pos = strrpos($attr_name, ".");
		
		if ($pos !== false) {
			$tn = SQLQueryHandler::removeInvalidCharsFromName(substr($attr_name, 0, $pos));
			$table_name = $tn ? $tn : $table_name;
			
			$attr_name = SQLQueryHandler::removeInvalidCharsFromName(substr($attr_name, $pos + 1));
		}
		
		$tn = $table_name ? SQLQueryHandler::getParsedSqlTableName($table_name) . "." : "";
		$attr_name = $attr_name == "*" ? "*" : SQLQueryHandler::getParsedSqlColumnName($attr_name);
		
		return $tn . $attr_name;
	}
	
	public static function createBaseExprValue($value, $check_reserved_values = true) {
		if (is_array($value)) {
			$value = isset($value["value"]) ? $value["value"] : null;
			$check_reserved_values = isset($value["check_reserved_values"]) ? $value["check_reserved_values"] : null;
		}
		
		if ($check_reserved_values) {
			//check if current class is an abastract class, bc the getSQLConditions method can be called from the DB abstract class which will then generate a php error, if we call the abstract methods: isReservedWord and isReservedWordFunction.
			$current_class_name = get_called_class();
			$class = new ReflectionClass($current_class_name);
			$abstract = $class->isAbstract();
			
			if (!$abstract) { 
				$is_reserved_word = self::isReservedWord($value); //check if is a reserved word
				$contains_reserved_word = self::isReservedWordFunction($value); //check if contains a function
			}
			else
				$is_reserved_word = $value == "DEFAULT";
		}
		
		return !empty($is_reserved_word) || !empty($contains_reserved_word) ? $value : SQLQueryHandler::createBaseExprValue($value);
	}
	
	public static function createBaseExprValueForOperatorIn($value, $check_reserved_values = true) {
		if (version_compare(PHP_VERSION, '7.1', '<')) {
			$GLOBALS["createBaseExprValueForOperatorIn_check_reserved_values"] = $check_reserved_values;
			$create_expr_value_func = function($v) {
				return self::createBaseExprValue($v, $GLOBALS["createBaseExprValueForOperatorIn_check_reserved_values"]);
			};
		}
		else
			$create_expr_value_func = function($v) use ($check_reserved_values) {
				return self::createBaseExprValue($v, $check_reserved_values);
			};
		
		return SQLQueryHandler::createBaseExprValueForOperatorIn($value, $create_expr_value_func);
	}
	
	public static function createBaseExprValueForOperatorIs($value, $check_reserved_values = true) {
		if (version_compare(PHP_VERSION, '7.1', '<')) {
			$GLOBALS["createBaseExprValueForOperatorIs_check_reserved_values"] = $check_reserved_values;
			$create_expr_value_func = function($v) {
				return self::createBaseExprValue($v, $GLOBALS["createBaseExprValueForOperatorIs_check_reserved_values"]);
			};
		}
		else
			$create_expr_value_func = function($v) use ($check_reserved_values) {
				return self::createBaseExprValue($v, $check_reserved_values);
			};
		
		return SQLQueryHandler::createBaseExprValueForOperatorIs($value, $create_expr_value_func);
	}
} 
?>
