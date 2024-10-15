<?php
include_once get_lib("lib.vendor.sqlparser.src.PHPSQLParser");
include_once get_lib("lib.vendor.sqlparser.src.PHPSQLCreator");
include_once get_lib("org.phpframework.util.text.TextSanitizer");

class SQLQueryHandler {
	
	public static $exception = null;
	public static $reserved_conditions = array("#searching_condition#");
	
	public static function parse($sql) {
	//$sql = "insert into activity (name, id)values('as', 12);";
	//$sql = "update activity set name='as', date='1223123' where activity_id=12;";
	//$sql = "delete from activity where activity_id=12;";
	//$sql = "select * from activity where activity_id=12;";
	//$sql = "SELECT count(domain_id) AS total FROM `mcondo_domain` WHERE 1=1";
	/*$sql = "SELECT cd.*, GROUP_CONCAT(cdo.domain SEPARATOR '|') AS domains
          FROM `mcondo_details` cd
          INNER JOIN mcondo_master_user cmu ON cmu.condo_id=cd.condo_id AND cmu.user_id=#user_id#
          INNER JOIN mcondo_domain cdo ON cdo.condo_id=cd.condo_id
          GROUP BY cd.condo_id";*/
	
		$data = null;
		self::$exception = null;
		
		try {
			if ($sql) {
				$parser = new PHPSQLParser();
				$parsed = $parser->parse($sql);
				//echo "<pre>";print_r($parsed);die();
			
				if (isset($parsed["INSERT"])) {
					$data = self::parseInsert($parsed);
					$data["type"] = "insert";
				}
				else if (isset($parsed["UPDATE"])) {
					$data = self::parseUpdate($parsed);
					$data["type"] = "update";
				}
				else if (isset($parsed["DELETE"])) {
					$data = self::parseDelete($parsed);
					$data["type"] = "delete";
				}
				else if (isset($parsed["SELECT"])) {
					//echo "<pre>";print_r($parsed);
					$data = self::parseSelect($parsed);
					//echo "<pre>";print_r($data);die();
					$data["type"] = "select";
				}
			}
		}
		catch(Exception $e) {
			//Do nothing
			self::$exception = $e;
		}
		
		return $data;
	}
	
	public static function parseInsert($parsed) {
		$data = array(
			"table" => isset($parsed["INSERT"][0]["table"]) ? self::getName($parsed["INSERT"][0]["table"]) : "",
			"attributes" => array(),
		);
		
		$columns = isset($parsed["INSERT"][0]["columns"]) ? $parsed["INSERT"][0]["columns"] : null;
		$values = isset($parsed["VALUES"][0]["data"]) ? $parsed["VALUES"][0]["data"] : null;
		
		$total = $columns ? count($columns) : 0;
		for ($i = 0; $i < $total; $i++) {
			$data["attributes"][] = array(
				"table" => self::getName($data["table"]),
				"column" => isset($columns[$i]["base_expr"]) ? self::getName($columns[$i]["base_expr"]) : "",
				"operator" => "=",
				"value" => isset($values[$i]) ? self::getBaseExprValue($values[$i]) : "",
			);
		}
		
		return $data;
	}
	
	public static function parseUpdate($parsed) {
		$data = array(
			"table" => isset($parsed["UPDATE"][0]["table"]) ? self::getName($parsed["UPDATE"][0]["table"]) : "",
			"attributes" => array(),
			"conditions" => array(),
		);
		
		$conditions = self::parseConditions($parsed, $data["table"], null);
		$data["conditions"] = array_merge($conditions["keys"], $conditions["conditions"]);
		
		$total = !empty($parsed["SET"]) ? count($parsed["SET"]) : 0;
		for ($i = 0; $i < $total; $i++) {
			$item = $parsed["SET"][$i];
			
			$data["attributes"][] = array(
				"table" => self::getName($data["table"]),
				"column" => isset($item["sub_tree"][0]["base_expr"]) ? self::getName($item["sub_tree"][0]["base_expr"]) : "",
				"operator" => isset($item["sub_tree"][1]["base_expr"]) ? $item["sub_tree"][1]["base_expr"] : null,
				"value" => isset($item["sub_tree"][2]) ? self::getBaseExprValue($item["sub_tree"][2]) : "",
			);
		}
		
		return $data;
	}
	
	public static function parseDelete($parsed) {
		$data = array(
			"table" => isset($parsed["DELETE"]["TABLES"][0]) ? self::getName($parsed["DELETE"]["TABLES"][0]) : "",
			"conditions" => array(),
		);
		
		$conditions = self::parseConditions($parsed, $data["table"], null);
		$data["conditions"] = array_merge($conditions["keys"], $conditions["conditions"]);
		
		return $data;
	}
	
	public static function parseSelect($parsed) {
		$data = array(
			"table" => "",
			"attributes" => array(),
			"keys" => array(),
			"conditions" => array(),
			"groups_by" => array(),
			"sorts" => array(),
			"start" => isset($parsed["LIMIT"]["offset"]) ? $parsed["LIMIT"]["offset"] : null,
			"limit" => isset($parsed["LIMIT"]["rowcount"]) ? $parsed["LIMIT"]["rowcount"] : null,
		);
		
		$aliases = array();
		$main_table = null;
		
		$total = !empty($parsed["FROM"]) ? count($parsed["FROM"]) : 0;
		for ($i = 0; $i < $total; $i++) {
			$item = $parsed["FROM"][$i];
			
			if (isset($item["expr_type"]) && $item["expr_type"] == "table") {
				$table = isset($item["table"]) ? self::getName($item["table"]) : "";
				$alias = "";
				
				if (isset($item["alias"]["name"]))
					$alias = !empty($item["alias"]["as"]) ? self::getAlias($item["base_expr"]) : self::getName($item["alias"]["name"]);
				
				$aliases[ ($alias ? self::getName($alias) : $table) ] = $table . ($alias ? " $alias" : "");
				
				if (!$main_table) {
					$main_table = $table . ($alias ? " " . $alias : "");
					$data["table"] = $main_table; //must be the table with alias, so it be coerent with the other ptables and ftables which contain the alias too. Note that this is very important for the sql editor in dataaccess/edit_single_query.js.
				}
			}
		}
		
		//PREPARING ATTRIBUTES
		$SelectBuilder = new SelectBuilder();
		$total = !empty($parsed["SELECT"]) ? count($parsed["SELECT"]) : 0;
		for ($i = 0; $i < $total; $i++) {
			$item = $parsed["SELECT"][$i];
			$table = $column = $name = "";
			
			if (isset($item["expr_type"]) && $item["expr_type"] == "colref") {
				$base_expr = isset($item["base_expr"]) ? $item["base_expr"] : null;
				//echo "<pRE>colref:";print_r($item);
			
				$base_expr = self::parseBaseExpr($base_expr, $main_table, $aliases);
				$parts = explode(" ", $base_expr[1]);
				
				$table = $base_expr[0];
				$column = $parts[0];
				$name = isset($parts[1]) ? $parts[1] : "";
				
				if (!$name && !empty($item["alias"]["name"]))
					$name = self::getName($item["alias"]["name"]);
			}
			else {
				$attr_to_parse = $item;
				$attr_to_parse["alias"] = null;
				$column = $SelectBuilder->build(array($attr_to_parse));
				$column = substr($column, 7);//remove "SELECT "
				$name = isset($item["alias"]["name"]) ? self::getName($item["alias"]["name"]) : "";
				
				//in case we have an expression with reference to a table and between parenthesis, when build the query through build method, it creates the sql with comma delimiter at the end.
				$delimiter = !empty($item["delim"]) ? $item["delim"] : ",";
				
				if (preg_match("/$delimiter\s*$/", $column))
					$column = preg_replace("/\s*$delimiter\s*$/", "", $column);
			}
			
			$data["attributes"][] = array(
				"table" => $table,
				"column" => $column,
				"name" => $name,
			);
		}
		
		//print_r($parsed);die();
		//PREPARING KEYS
		$total = !empty($parsed["FROM"]) ? count($parsed["FROM"]) : 0;
		for ($i = 0; $i < $total; $i++) {
			$item = $parsed["FROM"][$i];
			
			if (isset($item["expr_type"]) && $item["expr_type"] == "table") {
				$table = isset($item["table"]) ? self::getName($item["table"]) : "";
				$alias = "";
				
				if (isset($item["alias"]["name"]))
					$alias = !empty($item["alias"]["as"]) ? self::getAlias($item["base_expr"]) : self::getName($item["alias"]["name"]);
				
				$table_alias = $table . ($alias ? " $alias" : "");
				
				$aliases[ ($alias ? self::getName($alias) : $table) ] = $table_alias;
				
				if ($main_table != $table_alias) {
					$join_type = isset($item["join_type"]) ? $item["join_type"] : null;
					$conditions = isset($item["ref_clause"]) ? $item["ref_clause"] : null;
					
					$t = $conditions ? count($conditions) : 0;
					for ($j = 0; $j < $t; $j++) {
						$condition = $conditions[$j];
						
						if (isset($condition["expr_type"]) && ($condition["expr_type"] == "colref" || $condition["expr_type"] == "const")) {
							$ptable = $pcolumn = $ftable = $fcolumn = $value = $operator = null;
							$condition_base_expr = isset($condition["base_expr"]) ? $condition["base_expr"] : null;
							$next_condition = isset($conditions[$j + 1]) ? $conditions[$j + 1] : null;
							$next_condition_expr_type = isset($next_condition["expr_type"]) ? $next_condition["expr_type"] : null;
							
							$next_twice_condition = isset($conditions[$j + 2]) ? $conditions[$j + 2] : null;
							$next_twice_condition_expr_type = isset($next_twice_condition["expr_type"]) ? $next_twice_condition["expr_type"] : null;
							$next_twice_condition_base_expr = isset($next_twice_condition["base_expr"]) ? $next_twice_condition["base_expr"] : null;
							
							$operator = $next_condition_expr_type == "operator" && isset($next_condition["base_expr"]) ? $next_condition["base_expr"] : null;
							
							if ($condition["expr_type"] == "colref") {
								$base_expr = self::parseBaseExpr($condition_base_expr, $main_table, $aliases);
								$ptable = $base_expr[0];
								$pcolumn = $base_expr[1];
								
								if ($operator) {
									
									if ($next_twice_condition_expr_type == "colref" && !self::isBaseExpressionSQLVariable($next_twice_condition_base_expr)) {//different than #column_name#
										$base_expr = self::parseBaseExpr($next_twice_condition_base_expr, $main_table, $aliases);
										$ftable = $base_expr[0];
										$fcolumn = $base_expr[1];
									}
									else
										$value = self::getBaseExprValue($next_twice_condition);
								}
								else {
									$ptable = null;
									$value = $pcolumn;
								}
							}
							else if ($condition["expr_type"] == "const") {
								$value = self::getBaseExprValue($condition);
								
								if ($operator) {
									if ($next_twice_condition_expr_type == "colref" && !self::isBaseExpressionSQLVariable($next_twice_condition_base_expr)) {//different than #column_name#
										$base_expr = self::parseBaseExpr($next_twice_condition_base_expr, $main_table, $aliases);
										$ptable = $base_expr[0];
										$pcolumn = $base_expr[1];
									}
									else
										$value = "$value $operator " . self::getBaseExprValue($next_twice_condition);
								}
							}
							
							if ($operator && ($pcolumn || $fcolumn)) {
								if (strtolower($operator) == "in" || strtolower($operator) == "not in") {
									$value = trim($value);
									$value = substr($value, 0, 1) == "(" && substr($value, strlen($value) - 1) == ")" ? substr($value, 1, strlen($value) - 2) : $value;
								}
								
								$data["keys"][] = array(
									"ptable" => $ptable,
									"pcolumn" => $pcolumn,
									"ftable" => $ftable,
									"fcolumn" => $fcolumn,
									"value" => $value,
									"join" => $join_type == "JOIN" ? "INNER" : $join_type,
									"operator" => $operator,
								);
							}
							else if ($value) {//This is for the cases where we only have '#SOME_VAR_WITH_OTHER_SQL#' in the sql statement
								$data["keys"][] = array(
									"value" => $value,
								);
							}
							
							$j = $j + ($operator ? 2 : 0);
							
							$ptable = $pcolumn = $ftable = $fcolumn = $value = $operator = null;
						}
					}
				}
			}
		}
		//print_r($data);print_r($parsed);die();
		
		//PREPARING CONDITIONS
		$conditions = self::parseConditions($parsed, $main_table, $aliases);
		$data["keys"] = array_merge($data["keys"], $conditions["keys"]);
		$data["conditions"] = $conditions["conditions"];
		
		//PREPARING GROUPS BY
		$total = !empty($parsed["GROUP"]) ? count($parsed["GROUP"]) : 0;
		for ($i = 0; $i < $total; $i++) {
			$item = $parsed["GROUP"][$i];
	
			if (isset($item["expr_type"]) && $item["expr_type"] == "colref") {
				$base_expr = isset($item["base_expr"]) ? $item["base_expr"] : null;
				$base_expr = self::parseBaseExpr($base_expr, $main_table, $aliases);
				$table = $base_expr[0];
				$column = $base_expr[1];
			
				if ($column) {
					$data["groups_by"][] = array(
						"table" => $table,
						"column" => $column,
					);
				}
			}
		}
		
		//PREPARING SORTINGS
		$total = !empty($parsed["ORDER"]) ? count($parsed["ORDER"]) : 0;
		for ($i = 0; $i < $total; $i++) {
			$item = $parsed["ORDER"][$i];
	
			if (isset($item["expr_type"]) && $item["expr_type"] == "colref") {
				$base_expr = isset($item["base_expr"]) ? $item["base_expr"] : null;
				$base_expr = self::parseBaseExpr($base_expr, $main_table, $aliases);
				$table = $base_expr[0];
				$column = $base_expr[1];
			
				if ($column) {
					$data["sorts"][] = array(
						"table" => $table,
						"column" => $column,
						"order" => $item["direction"],
					);
				}
			}
		}
		
		return $data;
	}
	
	public static function parseConditions($parsed, $main_table, $aliases) {
		$data = array(
			"keys" => array(),
			"conditions" => array(),
		);
		
		//PREPARING CONDITIONS
		$items = isset($parsed["WHERE"]) ? $parsed["WHERE"] : null;
		$total = $items ? count($items) : 0;
		for ($i = 0; $i < $total; $i++) {
			$item = $items[$i];
			
			if (isset($item["expr_type"]) && ($item["expr_type"] == "colref" || $item["expr_type"] == "const")) {
				$table = $column = $value = $operator = null;
				$next_item = isset($items[$i + 1]) ? $items[$i + 1] : null;
				$next_item_expr_type = isset($next_item["expr_type"]) ? $next_item["expr_type"] : null;
				
				$next_twice_item = isset($items[$i + 2]) ? $items[$i + 2] : null;
				$next_twice_item_expr_type = isset($next_twice_item["expr_type"]) ? $next_twice_item["expr_type"] : null;
				$next_twice_item_base_expr = isset($next_twice_item["base_expr"]) ? $next_twice_item["base_expr"] : null;
				
				$operator = $next_item_expr_type == "operator" && isset($next_item["base_expr"]) ? $next_item["base_expr"] : null;
				$is_not_operator = false;
				
				//for operators with "not" like: "not like", "not in" and "is not"
				if ($next_twice_item_expr_type == "operator") {
					$is_not_operator = true;
					
					if (isset($next_twice_item_base_expr))
						$operator .= " " . $next_twice_item_base_expr;
					
					$next_twice_item = isset($items[$i + 3]) ? $items[$i + 3] : null;
					$next_twice_item_expr_type = isset($next_twice_item["expr_type"]) ? $next_twice_item["expr_type"] : null;
					$next_twice_item_base_expr = isset($next_twice_item["base_expr"]) ? $next_twice_item["base_expr"] : null;
				}
				
				if ($item["expr_type"] == "colref") {
					$base_expr = isset($item["base_expr"]) ? $item["base_expr"] : null;
					$base_expr = self::parseBaseExpr($base_expr, $main_table, $aliases);
					$table = $base_expr[0];
					$column = $base_expr[1];
					
					if ($operator) {
						if ($next_twice_item_expr_type == "colref" && !self::isBaseExpressionSQLVariable($next_twice_item_base_expr)) {//different than #column_name#
							$base_expr = self::parseBaseExpr($next_twice_item_base_expr, $main_table, $aliases);
							$ftable = $base_expr[0];
							$fcolumn = $base_expr[1];
							$fc = trim($fcolumn);
						
							if ($column && $fcolumn) {
								$data["keys"][] = array(
									"ptable" => $table,
									"pcolumn" => $column,
									"ftable" => $ftable,
									"fcolumn" => $fcolumn,
									"value" => "",
									"join" => "INNER",
									"operator" => $operator,
								);
							
								$table = $column = $value = $operator = null;
							}
							else
								$value = "";
						}
						else
							$value = self::getBaseExprValue($next_twice_item);
					}
					else {
						$table = null;
						$value = $column;
					}
				}
				else if ($item["expr_type"] == "const") {
					$value = self::getBaseExprValue($item);
					
					if ($operator) {
						if ($next_twice_item_expr_type == "colref" && !self::isBaseExpressionSQLVariable($next_twice_item_base_expr)) {//different than #column_name#
							$base_expr = self::parseBaseExpr($next_twice_item_base_expr, $main_table, $aliases);
							$table = $base_expr[0];
							$column = $base_expr[1];
						}
						else
							$value = "$value $operator " . self::getBaseExprValue($next_twice_item);
					}
				}
				
				if ($operator && $column) {
					if (strtolower($operator) == "in" || strtolower($operator) == "not in") {
						$value = trim($value);
						$value = substr($value, 0, 1) == "(" && substr($value, -1) == ")" ? substr($value, 1, strlen($value) - 2) : $value;
					}	
					
					$data["conditions"][] = array(
						"table" => $table,
						"column" => $column,
						"operator" => $operator,
						"value" => $value,
					);
				}
				else if ($value) {//This is for the cases where we only have '#SOME_VAR_WITH_OTHER_SQL#' in the sql statement
					$data["conditions"][] = array(
						"value" => $value,
					);
				}
					
				$i = $i + ($operator ? ($is_not_operator ? 3 : 2) : 0);
				
				$table = $column = $value = $operator = null;
			}
		}
		
		return $data;
	}
	
	public static function create($data) {
		if (isset($data["type"])) {
			if ($data["type"] == "insert")
				return self::createInsert($data);
			else if ($data["type"] == "update")
				return self::createUpdate($data);
			else if ($data["type"] == "delete")
				return self::createDelete($data);
			else if ($data["type"] == "select")
				return self::createSelect($data);
		}
		return null;
	}
	
	public static function createInsert($data) {
		$sql = null;
		
		$table_name = !empty($data["main_table"]) ? $data["main_table"] : (isset($data["table"]) ? $data["table"] : null); //table is bc the parseInsert
		$attributes = isset($data["attributes"]) ? $data["attributes"] : null;
		
		if ($table_name && !empty($attributes)) {
			$columns = array();
			$values = array();
			
			$total = count($attributes);
			for ($i = 0; $i < $total; $i++) {
				$column = isset($attributes[$i]["column"]) ? $attributes[$i]["column"] : null;
				$value = isset($attributes[$i]["value"]) ? $attributes[$i]["value"] : null;
				
				if ($column) {
					$columns[] = array(
						"expr_type" => "colref",
						"base_expr" => self::getParsedSqlColumnName($column),
					);
			
					$values[] = array(
						"expr_type" => "const",
						"base_expr" => self::createBaseExprValue($value),
					);
				}
			}
		
			$parsed_data = array(
				"INSERT" => array(),
				"VALUES" => array(),
			);
			
			$parsed_data["INSERT"][] = array(
				"table" => self::getParsedSqlTableName($table_name),
				"columns" => $columns,
			);
			
			$parsed_data["VALUES"][] = array(
				"expr_type" => "record",
				"data" => $values,
			);
			
			if (!empty($parsed_data["INSERT"][0]["columns"])) { 
				try {
					$creator = new PHPSQLCreator($parsed_data);
					$sql = $creator->created;
					
					if (!empty($data["ignore"]))
						$sql = preg_replace("/^(INSERT)(\s+)/i", '$1 IGNORE $2', $sql);
				}
				catch(Exception $e) {
					//Do nothing
				}
			}
		}
		
		return $sql;
	}
	
	public static function createUpdate($data) {
		$sql = null;
		
		$table_name = !empty($data["main_table"]) ? $data["main_table"] : (isset($data["table"]) ? $data["table"] : null); //table is bc the parseUpdate
		$attributes = isset($data["attributes"]) ? $data["attributes"] : null;
		$conditions = isset($data["conditions"]) ? $data["conditions"] : null;
		
		if ($table_name && !empty($attributes)) {
			$parsed_data = array(
				"UPDATE" => array(),
				"SET" => array(),
			);
			
			$parsed_data["UPDATE"][] = array(
				"expr_type" => "table",
				"table" => self::getParsedSqlTableName($table_name),
			);
			
			$total = count($attributes);
			for ($i = 0; $i < $total; $i++) {
				$column = isset($attributes[$i]["column"]) ? $attributes[$i]["column"] : null;
				$operator = "=";
				$value = isset($attributes[$i]["value"]) ? $attributes[$i]["value"] : null;
				
				if ($column) {
					$parsed_data["SET"][] = array(
						"expr_type" => "expression",
						"sub_tree" => array(
							array(
								"expr_type" => "colref",
								"base_expr" => self::getParsedSqlColumnName($column),
							),
							array(
								"expr_type" => "operator",
								"base_expr" => $operator,
							),
							array(
								"expr_type" => "const",
								"base_expr" => self::createBaseExprValue($value),
							),
						),
					);
				}
			}
			
			//PREPARING CONDITIONS
			$pd = self::createConditions($conditions, $table_name);
			$parsed_data["WHERE"] = isset($pd["WHERE"]) ? $pd["WHERE"] : null;
			
			if (!empty($parsed_data["SET"])) {
				try {
					$creator = new PHPSQLCreator($parsed_data);
					$sql = $creator->created;
				}
				catch(Exception $e) {
					//Do nothing
				}
			}
		}
		
		return $sql;
	}
	
	public static function createDelete($data) {
		$sql = null;
		
		$table_name = !empty($data["main_table"]) ? $data["main_table"] : (isset($data["table"]) ? $data["table"] : null); //table is bc the parseDelete
		$conditions = isset($data["conditions"]) ? $data["conditions"] : null;
		
		if ($table_name) {
			$parsed_data = array(
				"DELETE" => array(
					"TABLES" => array(),
				),
				"FROM" => array(),
			);
			
			$parsed_data["DELETE"]["TABLES"][] = $table_name;
			
			$parsed_data["FROM"][] = array(
				"expr_type" => "table",
				"table" => self::getParsedSqlTableName($table_name),
			);
			
			//PREPARING CONDITIONS
			$pd = self::createConditions($conditions, $table_name);
			$parsed_data["WHERE"] = isset($pd["WHERE"]) ? $pd["WHERE"] : null;
			
			try {
				$creator = new PHPSQLCreator($parsed_data);
				$sql = $creator->created;
			}
			catch(Exception $e) {
				//Do nothing
			}
		}
		
		return $sql;
	}
	
	public static function createSelect($data) {
		$sql = null;
			
		$attributes = isset($data["attributes"]) ? $data["attributes"] : null;
		$keys = isset($data["keys"]) ? $data["keys"] : null;
		$conditions = isset($data["conditions"]) ? $data["conditions"] : null;
		$groups_by = isset($data["groups_by"]) ? $data["groups_by"] : null;
		$sorts = isset($data["sorts"]) ? $data["sorts"] : null;
		$start = isset($data["start"]) ? $data["start"] : null;
		$limit = isset($data["limit"]) ? $data["limit"] : null;
		$main_table = !empty($data["main_table"]) ? $data["main_table"] : (!empty($data["table"]) ? $data["table"] : self::getMainTable($data)); //table comes from parseSelect method
		
		$parsed_data = array();
		
		$total = $attributes ? count($attributes) : 0;
		if ($total) {
			//PREPARING MAIN TABLE WITH ALIAS, IF APPLY
			if ((!empty($data["main_table"]) || !empty($data["table"])) && self::getAlias($main_table) == self::getTableName($main_table)) {
				$table_alias = self::getMainTableAlias($data, $main_table);
				
				if ($table_alias)
					$main_table .= " " . $table_alias;
			}
			
			$tables = self::getAllTables($data, $main_table);
			
			//PREPARING ATTRIBUTES
			for ($i = 0; $i < $total; $i++) {
				$item = $attributes[$i];
				
				$table = isset($item["table"]) ? $item["table"] : null;
				$column = isset($item["column"]) ? $item["column"] : null;
				$name = isset($item["name"]) ? $item["name"] : null;
				
				if ($column) {
					//get table from column
					if (empty($table))
						$table = self::getTableFromColumn($column);
					
					if (empty($table))
						$table = $main_table;
					
					$table_alias = self::getAlias($table);
					$table_name = self::getTableName($table);
					
					$parsed_data["SELECT"][] = array(
						"expr_type" => "colref",
						"alias" => !$name ? null : array(
							"name" => $name,
						),
						"base_expr" => "\n     " . self::getParsedSqlTableColumnName($table_alias, $column, $table_alias != $table_name),
						"delim" => $i + 1 < $total ? "," : "",
					);
				}
			}
		
			//PREPARING MAIN TABLE
			$table_name = self::getTableName($main_table);
			$table_alias = self::getAlias($main_table);
			
			if ($table_name) {
				$parsed_data["FROM"][] = array(
					"expr_type" => "table",
					"table" => self::getParsedSqlTableName($table_name),
					"alias" => !$table_alias || $table_alias == $table_name ? null : array(
					    "name" => self::getParsedSqlAlias($table_alias),
					),
				);
				$tables[$main_table] = true;
			}
			
			//PREPARING JOINS
			$connections = self::getKeysConnections($keys, $main_table);
			$joins = array();
			//print_r($connections);
			
			foreach ($connections as $connection) {
				$source_table = isset($connection["source_table"]) ? $connection["source_table"] : null;
				$target_table = isset($connection["target_table"]) ? $connection["target_table"] : null;
				$source_alias = self::getAlias($source_table);
				$target_alias = self::getAlias($target_table);
				$source_name = self::getTableName($source_table);
				$target_name = self::getTableName($target_table);
				
				$join_table = !empty($tables[$source_table]) && $target_table != $main_table ? $target_table : $source_table;
				
				$total = !empty($connection["source_columns"]) ? count($connection["source_columns"]) : 0;
				if ($total) {
					$join_type = isset($connection["tables_join"]) ? $connection["tables_join"] : null;
			 		$join_conditions = !empty($joins[$join_table][$join_type]) ? $joins[$join_table][$join_type] : array();
					
					for ($i = 0; $i < $total; $i++) {
						$sc = isset($connection["source_columns"][$i]) ? $connection["source_columns"][$i] : null;
						$tc = isset($connection["target_columns"][$i]) ? $connection["target_columns"][$i] : null;
						$cv = isset($connection["column_values"][$i]) ? $connection["column_values"][$i] : null;
						$operator = isset($connection["operators"][$i]) ? $connection["operators"][$i] : null;
						
						$operator = $operator ? $operator : "=";
						$lo = strtolower($operator);
						
						if ($lo == "in" || $lo == "not in")
							$pcv = self::createBaseExprValueForOperatorIn($cv);
						else if ($lo == "is" || $lo == "is not")
							$pcv = self::createBaseExprValueForOperatorIs($cv);
						else 
							$pcv = self::createBaseExprValue($cv);
						
						if ($sc || $tc) {
							if ($i > 0 || $join_conditions) {
								$join_conditions[] = array(
									"expr_type" => "operator",
									"base_expr" => "AND",
								);
							}
					
							if ($sc && $tc) {
								$join_conditions[] = array(
									"expr_type" => "colref",
									"base_expr" => self::getParsedSqlTableColumnName($source_alias, $sc, $source_alias != $source_name),
								);
								$join_conditions[] = array(
									"expr_type" => "operator",
									"base_expr" => $operator,
								);
								$join_conditions[] = array(
									"expr_type" => "colref",
									"base_expr" => self::getParsedSqlTableColumnName($target_alias, $tc, $target_alias != $target_name),
								);
							
								if ($cv || $cv === 0) {
									$join_conditions[] = array(
										"expr_type" => "operator",
										"base_expr" => "AND",
									);
									$join_conditions[] = array(
										"expr_type" => "colref",
										"base_expr" => self::getParsedSqlTableColumnName($source_alias, $sc, $source_alias != $source_name),
									);
									$join_conditions[] = array(
										"expr_type" => "operator",
										"base_expr" => $operator,
									);
									$join_conditions[] = array(
										"expr_type" => "const",
										"base_expr" => $pcv,
									);
								
									$join_conditions[] = array(
										"expr_type" => "operator",
										"base_expr" => "AND",
									);
									$join_conditions[] = array(
										"expr_type" => "colref",
										"base_expr" => self::getParsedSqlTableColumnName($target_alias, $tc, $target_alias != $target_name),
									);
									$join_conditions[] = array(
										"expr_type" => "operator",
										"base_expr" => $operator,
									);
									$join_conditions[] = array(
										"expr_type" => "const",
										"base_expr" => $pcv,
									);
								}
							}
							else if ($sc) {
								$join_conditions[] = array(
									"expr_type" => "colref",
									"base_expr" => self::getParsedSqlTableColumnName($source_alias, $sc, $source_alias != $source_name),
								);
								$join_conditions[] = array(
									"expr_type" => "operator",
									"base_expr" => $operator,
								);
								$join_conditions[] = array(
									"expr_type" => "const",
									"base_expr" => $pcv,
								);
							}
							else if ($tc) {
								$join_conditions[] = array(
									"expr_type" => "colref",
									"base_expr" => self::getParsedSqlTableColumnName($target_alias, $tc, $target_alias != $target_name),
								);
								$join_conditions[] = array(
									"expr_type" => "operator",
									"base_expr" => $operator,
								);
								$join_conditions[] = array(
									"expr_type" => "const",
									"base_expr" => $pcv,
								);
							}
						}
						else if ($cv) {
							if ($i > 0 || $join_conditions)
								$join_conditions[] = array(
									"expr_type" => "operator",
									"base_expr" => "AND",
								);
							
							$join_conditions[] = array(
								"expr_type" => "const",
								"base_expr" => $cv,
							);
						}
					}
				
					$joins[$join_table][$join_type] = $join_conditions;
				}
			
				$tables[$join_table] = true;
			}
			
			foreach ($joins as $join_table => $item) {
				$table_alias = self::getAlias($join_table);
				$table_name = self::getTableName($join_table);
				
				if ($table_name)
					foreach ($item as $join_type => $join_conditions)
						$parsed_data["FROM"][] = array(
							"expr_type" => "table",
							"table" => self::getParsedSqlTableName($table_name),
							"alias" => !$table_alias || $table_alias == $table_name ? null : array(
							    "name" => self::getParsedSqlAlias($table_alias),
							),
							"join_type" => $join_type == "INNER" ? "JOIN" : $join_type,
							"ref_type" => "ON",
							"ref_clause" => $join_conditions,
						);
			}
			
			//PREPARING CONDITIONS
			$pd = self::createConditions($conditions, $main_table);
			$parsed_data["WHERE"] = isset($pd["WHERE"]) ? $pd["WHERE"] : null;
			
			//PREPARING GROUPS BY
			$total = $groups_by ? count($groups_by) : 0;
			for ($i = 0; $i < $total; $i++) {
				$item = $groups_by[$i];
		
				$table = isset($item["table"]) ? $item["table"] : null;
				$column = isset($item["column"]) ? $item["column"] : null;
		
				if ($column) {
					//get table from column
					if (empty($table))
						$table = self::getTableFromColumn($column);
					
					if (empty($table))
						$table = $main_table;
					
					$table_alias = self::getAlias($table);
					$table_name = self::getTableName($table);
					
					$parsed_data["GROUP"][] = array(
						"expr_type" => "colref",
						"base_expr" => self::getParsedSqlTableColumnName($table_alias, $column, $table_alias != $table_name),
					);
				}
			}
			
			//PREPARING SORTINGS
			$total = $sorts ? count($sorts) : 0;
			for ($i = 0; $i < $total; $i++) {
				$item = $sorts[$i];
		
				$table = isset($item["table"]) ? $item["table"] : null;
				$column = isset($item["column"]) ? $item["column"] : null;
				$order = isset($item["order"]) ? $item["order"] : null;
				
				if ($column) {
					//get table from column
					if (empty($table))
						$table = self::getTableFromColumn($column);
					
					if (empty($table))
						$table = $main_table;
					
					$table_alias = self::getAlias($table);
					$table_name = self::getTableName($table);
					
					$parsed_data["ORDER"][] = array(
						"expr_type" => "colref",
						"base_expr" => self::getParsedSqlTableColumnName($table_alias, $column, $table_alias != $table_name),
						"direction" => $order,
					);
				}
			}
			
			if ($limit)
				$parsed_data["LIMIT"] = array(
					"offset" => $start,
					"rowcount" => $limit,
				);
			
			try {
				$creator = new PHPSQLCreator($parsed_data);
				$sql = $creator->created;
			
				$sql = str_replace(" FROM ", "\n FROM ", $sql);
				$sql = str_replace(" INNER JOIN ", "\n   INNER JOIN ", $sql);
				$sql = str_replace(" LEFT JOIN ", "\n   LEFT JOIN ", $sql);
				$sql = str_replace(" RIGHT JOIN ", "\n   RIGHT JOIN ", $sql);
				$sql = str_replace(" WHERE ", "\n WHERE ", $sql);
				$sql = str_replace(" GROUP BY ", "\n GROUP BY ", $sql);
				$sql = str_replace(" ORDER BY ", "\n ORDER BY ", $sql);
				$sql = str_replace(" LIMIT ", "\n LIMIT ", $sql);
			}
			catch(Exception $e) {
				//Do nothing
			}
		}
		
		return $sql;
	}
	
	public static function createConditions($conditions, $main_table) {
		$parsed_data = array();
		
		$total = $conditions ? count($conditions) : 0;
		for ($i = 0; $i < $total; $i++) {
			$item = $conditions[$i];
	
			$table = isset($item["table"]) ? $item["table"] : null;
			$column = isset($item["column"]) ? $item["column"] : null;
			$operator = isset($item["operator"]) ? $item["operator"] : null;
			$value = isset($item["value"]) ? $item["value"] : null;
		
			if ($column) {
				//get table from column
				if (empty($table))
					$table = self::getTableFromColumn($column);
				
				if (empty($table))
					$table = $main_table;
				
				$operator = $operator ? $operator : "=";
				$lo = strtolower($operator);
				//$value = is_numeric($value) ? $value : "'" . addcslashes($value, "\\'") . "'";
				
				if ($lo == "in" || $lo == "not in")
					$value = self::createBaseExprValueForOperatorIn($value);
				else if ($lo == "is" || $lo == "is not")
					$value = self::createBaseExprValueForOperatorIs($value);
				else
					$value = self::createBaseExprValue($value);
				
				$table_alias = self::getAlias($table);
				$table_name = self::getTableName($table);
				
				if ($i > 0) {
					$parsed_data["WHERE"][] = array(
						"expr_type" => "operator",
						"base_expr" => "AND",
					);
				}
				
				$parsed_data["WHERE"][] = array(
					"expr_type" => "colref",
					"base_expr" => self::getParsedSqlTableColumnName($table_alias, $column, $table_alias != $table_name),
				);
				$parsed_data["WHERE"][] = array(
					"expr_type" => "operator",
					"base_expr" => $operator,
				);
				$parsed_data["WHERE"][] = array(
					"expr_type" => "const",
					"base_expr" => $value,
				);
			}
			else if ($value) {
				if ($i > 0 && !in_array($value, self::$reserved_conditions))
					$parsed_data["WHERE"][] = array(
						"expr_type" => "operator",
						"base_expr" => "AND",
					);
				
				$parsed_data["WHERE"][] = array(
					"expr_type" => "const",
					"base_expr" => $value,
				);
			}
		}
		
		return $parsed_data;
	}
	
	public static function create2($data) {
		$attributes = isset($data["attributes"]) ? $data["attributes"] : null;
		$keys = isset($data["keys"]) ? $data["keys"] : null;
		$conditions = isset($data["conditions"]) ? $data["conditions"] : null;
		$groups_by = isset($data["groups_by"]) ? $data["groups_by"] : null;
		$sorts = isset($data["sorts"]) ? $data["sorts"] : null;
		$start = isset($data["start"]) ? $data["start"] : null;
		$limit = isset($data["limit"]) ? $data["limit"] : null;
		$main_table = !empty($data["main_table"]) ? $data["main_table"] : (!empty($data["table"]) ? $data["table"] : self::getMainTable($data)); //table comes from parseSelect method
		
		$sql = "";
		
		$total = $attributes ? count($attributes) : 0;
		if ($total) {
			$tables = array();
			$sql = "SELECT \n\t";
			
			//PREPARING MAIN TABLE WITH ALIAS, IF APPLY
			if ((!empty($data["main_table"]) || !empty($data["table"])) && self::getAlias($main_table) == self::getTableName($main_table)) {
				$table_alias = self::getMainTableAlias($data, $main_table);
				
				if ($table_alias)
					$main_table .= " " . $table_alias;
			}
			
			//PREPARING ATTRIBUTES
			for ($i = 0; $i < $total; $i++) {
				$item = $attributes[$i];
				
				$table = isset($item["table"]) ? $item["table"] : null;
				$column = isset($item["column"]) ? $item["column"] : null;
				$name = isset($item["name"]) ? $item["name"] : null;
				
				//get table from column
				if (empty($table))
					$table = self::getTableFromColumn($column);
				
				if (empty($table))
					$table = $main_table;
				
				$tables[$table] = false;
				
				$table_alias = self::getAlias($table);
				$table_name = self::getTableName($table);
				
				$sql .= ($i > 0 ? ", \n\t" : "") . self::getParsedSqlTableColumnName($table_alias, $column, $table_alias != $table_name) . ($name ? " " . self::getAlias($name) : "");
			}
			
			//PREPARING MAIN TABLE
			$table_name = self::getTableName($main_table);
			$table_alias = self::getAlias($main_table);
			
			$sql .= "\n FROM " . self::getParsedSqlTableName($table_name) . ($table_alias ? " " . self::getParsedSqlAlias($table_alias) : "");
			$tables[$main_table] = true;
			
			//PREPARING JOINS
			$connections = self::getKeysConnections($keys, $main_table);
			$joins = array();
			
			foreach ($connections as $connection) {
				$source_table = isset($connection["source_table"]) ? $connection["source_table"] : null;
				$target_table = isset($connection["target_table"]) ? $connection["target_table"] : null;
				$source_alias = self::getAlias($source_table);
				$target_alias = self::getAlias($target_table);
				$source_name = self::getTableName($source_table);
				$target_name = self::getTableName($target_table);
				
				$join_table = !empty($tables[$source_table]) ? $target_table : $source_table;
				
				$total = !empty($connection["source_columns"]) ? count($connection["source_columns"]) : 0;
				if ($total) {
					$join_type = isset($connection["tables_join"]) ? $connection["tables_join"] : null;
			 		$sql_on = "";
					
					for ($i = 0; $i < $total; $i++) {
						$sc = isset($connection["source_columns"][$i]) ? $connection["source_columns"][$i] : null;
						$tc = isset($connection["target_columns"][$i]) ? $connection["target_columns"][$i] : null;
						$cv = isset($connection["column_values"][$i]) ? $connection["column_values"][$i] : null;
						$operator = isset($connection["operators"][$i]) ? $connection["operators"][$i] : null;
						
						$pcv = is_numeric($cv) ? $cv : "'" . addcslashes($cv, "\\'") . "'";
						$operator = $operator ? $operator : "=";
						
						if ($sc && $tc) {
							$sql_on .= ($sql_on ? " AND" : "") . " " . self::getParsedSqlTableColumnName($source_alias, $sc, $source_alias != $source_name) . " " . $operator . " " . self::getParsedSqlTableColumnName($target_alias, $tc, $target_alias != $target_name);
							
							if ($cv || $cv === 0)
								$sql_on .= " AND " . self::getParsedSqlTableColumnName($source_alias, $sc, $source_alias != $source_name) . " " . $operator . " " . $pcv . " AND " . self::getParsedSqlTableColumnName($target_alias, $tc, $target_alias != $target_name) . " " . $operator . " " . $pcv;
						}
						else if ($sc)
							$sql_on .= ($sql_on ? " AND" : "") . " " . self::getParsedSqlTableColumnName($source_alias, $sc, $source_alias != $source_name) . " " . $operator . " " . $pcv;
						else if ($tc)
							$sql_on .= ($sql_on ? " AND" : "") . " " . self::getParsedSqlTableColumnName($target_alias, $tc, $target_alias != $target_name) . " " . $operator . " " . $pcv;
					}
					
					$joins[$join_table][$join_type][] = $sql_on;
				}
				
				$tables[$join_table] = true;
			}
			
			foreach ($joins as $join_table => $item) 
				foreach ($item as $join_type => $sql_joins) {
					$sql_on = "";
					foreach ($sql_joins as $sql_join) 
						$sql_on .= ($sql_on ? " AND" : "") . $sql_join;
					
					$sql .= "\n   " . $join_type . " JOIN " . self::getParsedSqlTableName($join_table) . ($sql_on ? " ON " . $sql_on : "");
				}
			
			foreach ($tables as $table => $is_already_included) 
				if (!$is_already_included) 
					$sql .= "\n   INNER JOIN " . self::getParsedSqlTableName($table);
			
			//PREPARING CONDITIONS
			$total = $conditions ? count($conditions) : 0;
			if ($total) {
				$sql .= "\n WHERE \n\t";
				
				for ($i = 0; $i < $total; $i++) {
					$item = $conditions[$i];
			
					$table = isset($item["table"]) ? $item["table"] : null;
					$column = isset($item["column"]) ? $item["column"] : null;
					$operator = isset($item["operator"]) ? $item["operator"] : null;
					$value = isset($item["value"]) ? $item["value"] : null;
				
					if ($column) {
						//get table from column
						if (empty($table))
							$table = self::getTableFromColumn($column);
						
						if (empty($table))
							$table = $main_table;
						
						$operator = $operator ? $operator : "=";
						$value = is_numeric($value) ? $value : "'" . addcslashes($value, "\\'") . "'";
						
						$table_alias = self::getAlias($table);
						$table_name = self::getTableName($table);
						
						$sql .= ($i > 0 ? " AND \n\t" : "") . " " . self::getParsedSqlTableColumnName($table_alias, $column, $table_alias != $table_name) . " " . $operator . " " . $value;
					}
				}
			}
			
			//PREPARING GROUPS BY
			$total = $groups_by ? count($groups_by) : 0;
			if ($total) {
				$sql .= "\n GROUP BY \n\t";
				
				for ($i = 0; $i < $total; $i++) {
					$item = $groups_by[$i];
			
					$table = isset($item["table"]) ? $item["table"] : null;
					$column = isset($item["column"]) ? $item["column"] : null;
					
					if ($column) {
						//get table from column
						if (empty($table))
							$table = self::getTableFromColumn($column);
						
						if (empty($table))
							$table = $main_table;
						
						$table_alias = self::getAlias($table);
						$table_name = self::getTableName($table);
						
						$sql .= ($i > 0 ? ", \n\t" : "") . self::getParsedSqlTableColumnName($table_alias, $column, $table_alias != $table_name);
					}
				}
			}
			
			//PREPARING SORTINGS
			$total = $sorts ? count($sorts) : 0;
			if ($total) {
				$sql .= "\n ORDER BY \n\t";
				
				for ($i = 0; $i < $total; $i++) {
					$item = $sorts[$i];
			
					$table = isset($item["table"]) ? $item["table"] : null;
					$column = isset($item["column"]) ? $item["column"] : null;
					$order = isset($item["order"]) ? $item["order"] : null;
					
					if ($column) {
						//get table from column
						if (empty($table))
							$table = self::getTableFromColumn($column);
						
						if (empty($table))
							$table = $main_table;
						
						$table_alias = self::getAlias($table);
						$table_name = self::getTableName($table);
						
						$sql .= ($i > 0 ? ", \n\t" : "") . self::getParsedSqlTableColumnName($table_alias, $column, $table_alias != $table_name) . " " . $order;
					}
				}
			}
			
			if ($limit) 
				$sql .= "\n LIMIT " . ($start ? $start . ", " : "") . $limit;
		}
		
		return $sql;
	}
	
	private static function getKeysConnections($keys, $main_table) {
		$connections = array();
		
		$total = $keys ? count($keys) : 0;
		for ($i = 0; $i < $total; $i++) {
			$item = $keys[$i];
			
			$ptable = isset($item["ptable"]) ? $item["ptable"] : null;
			$pcolumn = isset($item["pcolumn"]) ? $item["pcolumn"] : null;
			$ftable = isset($item["ftable"]) ? $item["ftable"] : null;
			$fcolumn = isset($item["fcolumn"]) ? $item["fcolumn"] : null;
			$value = isset($item["value"]) ? $item["value"] : null;
			$join = isset($item["join"]) ? $item["join"] : null;
			$operator = isset($item["operator"]) ? $item["operator"] : null;
			
			//get table from column
			if ($pcolumn && !$ptable)
				$ptable = self::getTableFromColumn($pcolumn);
			
			if ($fcolumn && !$ftable)
				$ftable = self::getTableFromColumn($fcolumn);
			
			$ptable = empty($ptable) ? $main_table : $ptable;
			$ftable = empty($ftable) ? $main_table : $ftable;
			$join = empty($join) ? "INNER" : strtoupper($join);
			$value = isset($value) ? $value : "";
			$operator = empty($operator) ? "=" : $operator;
			
			if ($ptable && $ftable) {
				$c_id = $ptable . "_" . $ftable . "_" . $join;
		
				if (!isset($connections[$c_id])) {
					$connections[$c_id] = array(
						"source_table" => $ptable, 
						"target_table" => $ftable, 
						"tables_join" => $join, 
						"source_columns" => array(), 
						"target_columns" => array(),  
						"column_values" => array(),
						"operators" => array(), 
					);
				}
			
				$connections[$c_id]["source_columns"][] = $pcolumn;
				$connections[$c_id]["target_columns"][] = $fcolumn;
				$connections[$c_id]["column_values"][] = $value;
				$connections[$c_id]["operators"][] = $operator;
			}
		}
		
		return $connections;
	}
	
	/*
	 * eg:
	 * - table_a table_b
	 * - table_as AS table_b
	 * - table_as AS table_b inner join .... => in case of the $table variable be a base_expr
	 */
	public static function getAlias($table) {
		$parts = explode(" ", trim($table));
		$alias = $parts[0];
		
		if (count($parts) > 1) {
			if (strtolower($parts[1]) != "as")
				$alias = $parts[1];
			else
				$alias = $parts[2];
		}
		
		return $alias;
	}
	
	private static function getTableName($table) {
		$parts = explode(" ", $table);
		return $parts[0];
	}
	
	public static function getTableFromColumn($column) {
		$table = null;
		
		if ($column && strpos($column, ".") !== false) {
			$c = explode(" ", $column);
			$parts = self::parseTableName($c[0]);
			array_pop($parts);
			$table = implode(".", $parts);
		}
		
		return $table;
	}
	
	private static function parseBaseExpr($base_expr, $main_table, &$aliases) {
		$parts = explode(".", $base_expr);
		
		if (count($parts) < 2) {
			$table = $main_table;
			$attr = self::getName($parts[0]);
		}
		else {
			$attr = array_pop($parts); //remove last part that is the name
			
			$part = self::getName(implode(".", $parts));
			
			if (empty($aliases[$part]) && $part) 
				$aliases[$part] = $part;
			
			$table = isset($aliases[$part]) ? $aliases[$part] : null;
			$attr = self::getName($attr);
		}
		
		return array($table, $attr);
	}
	
	//GETTING NAME WITH NO APOSTROFES
	private static function getName($name) {
		return str_replace("`", "", $name);
	}
	
	private static function getParsedSqlAlias($alias) {
		//return $alias;
		return "`" . self::getName($alias) . "`"; //call getName method in case the alias already have "`"
	}
	
	//used in DB
	//check if table_name has schema and db appended
	public static function getParsedSqlTableName($table) {
		$sql = "";
		$parts = self::parseTableName($table);
		
		foreach ($parts as $part)
			if ($part)
				$sql .= ($sql ? "." : "") . "`" . $part . "`";
		
		return $sql;
	}
	
	//used in DB
	public static function getParsedSqlColumnName($column) {
		return $column == "*" ? "*" : "`" . self::removeInvalidCharsFromName($column) . "`";
	}
	
	//used in DB
	public static function removeInvalidCharsFromName($name) {
		return trim(str_replace(array("'", '"', "`"), "", $name));
	}
	
	private static function getParsedSqlTableColumnName($table, $column, $is_alias) {
		$is_func = strpos($column, "(") !== false;
		
		if ($is_func)
			return $column;
		
		if ($table)
			return ($is_alias ? self::getParsedSqlAlias($table) : self::getParsedSqlTableName($table)) . "." . self::getParsedSqlColumnName($column);
		
		return self::getParsedSqlColumnName($column);
	}
	
	//GETTING VALUE WITH NO QUOTES
	private static function getBaseExprValue($item) {
		$base_expr = "";
		
		if (!empty($item)) {
			if (empty($item["expr_type"]) || ($item["expr_type"] != "const" && $item["expr_type"] != "colref" && $item["expr_type"] != "in-list")) {
				$item["alias"] = null;
				
				$SelectBuilder = new SelectBuilder();
				//echo "<br><pre>";print_r($item);
				$base_expr = $SelectBuilder->build(array($item));
				$base_expr = substr($base_expr, 7);//remove "SELECT "
			}
			else if (isset($item["base_expr"]))
				$base_expr = $item["base_expr"];
		}
		
		$aux = trim($base_expr);
		
		if (strlen($aux) > 0) {
			if ($aux[0] == "'" && $aux[ strlen($aux) - 1 ] == "'")
				return substr($aux, 1, strlen($aux) - 2);
			else if ($aux[0] == '"' && $aux[ strlen($aux) - 1 ] == '"')
				return substr($aux, 1, strlen($aux) - 2);
		}
		
		return $base_expr;
	}
	
	//SETTING VALUE WITH QUOTES
	public static function createBaseExprValue($value) {
		//$v = trim($value);
		
		//return is_numeric($v) ? $value : (substr($v, 0, 1) == "#" && substr($v, strlen($v) - 1, 1) == "#" ? $value : "'" . addcslashes($value, "\\'") . "'");
		
		//return is_numeric($v) ? $value : "'" . addcslashes($value, "\\'") . "'";
		return $value === null ? "null" : (is_numeric($value) && !is_string($value) ? $value : "'" . addcslashes($value, "\\'") . "'");
	}
	
	//it's used in the DB too.
	public static function createBaseExprValueForOperatorIn($value, $create_expr_value_func = null) {
		$value = is_array($value) ? implode(", ", $value) : $value;
		
		$values = array();
		$v = "";
		$open_single_quotes = false;
		$open_double_quotes = false;
		
		if (is_numeric($value))
			$value = (string)$value; //bc of php > 7.4 if we use $var[$i] gives an warning
		
		$t = strlen($value);
		for ($i = 0; $i < $t; $i++) {
			$c = $value[$i];
			
			if ($c == "," && !$open_single_quotes && !$open_double_quotes) {
				//$values[] = is_numeric($v) ? $v : "'" . addcslashes($v, "\\'") . "'";
				$values[] = $create_expr_value_func ? $create_expr_value_func($v) : self::createBaseExprValue($v);
				$v = "";
			}
			else if ($c == "'" && !$open_double_quotes && !TextSanitizer::isCharEscaped($value, $i)) 
				$open_single_quotes = !$open_single_quotes;
			else if ($c == '"' && !$open_single_quotes && !TextSanitizer::isCharEscaped($value, $i))
				$open_double_quotes = !$open_double_quotes;
			else if ($open_single_quotes || $open_double_quotes) 
				$v .= $c;
			else if ($v !== "" || $c != " ")
				$v .= $c;
		}
		
		if (strlen($v) || !count($values))
			//$values[] = is_numeric($v) ? $v : "'" . addcslashes($v, "\\'") . "'";
			$values[] = $create_expr_value_func ? $create_expr_value_func($v) : self::createBaseExprValue($v);
		
		return "(" . implode(", ", $values) . ")";
	}
	
	//it's used in the DB too.
	public static function createBaseExprValueForOperatorIs($value, $create_expr_value_func = null) {
		$lv = strtolower($value);
		
		if ($lv == "null" || $lv == "true" || $lv == "false" || $lv == "unknown")
			return $value;
		
		return $create_expr_value_func ? $create_expr_value_func($value) : self::createBaseExprValue($value); //if $value is not allowed, return the value with "". This should give an sql error when executed to the DB, but at least the DB will not be hacked and we can see then that the sql query is wrong.
	}
	
	private static function stripQuotes($str) {
		return str_replace(array("`", "'", '"'), "", $str);
	}
	
	private static function getMainTable($data) {
		$types = array("attributes", "keys", "conditions", "groups_by", "sorts");
		
		$t = count($types);
		for ($i = 0; $i < $t; $i++) {
			$type = $types[$i];
			$items = isset($data[$type]) ? $data[$type] : null;
			
			$total = $items ? count($items) : 0;
			for ($j = 0; $j < $total; $j++) {
				$item = $items[$j];
				
				if (!empty($item["table"]))
					return $item["table"];
				else if (!empty($item["ptable"])) 
					return $item["ptable"];
				else if (!empty($item["ftable"]))
					return $item["ftable"];
			}
		}
		
		return "";
	}
	
	private static function getMainTableAlias($data, $table_name) {
		$types = array("attributes", "keys", "conditions", "groups_by", "sorts");
		
		$t = count($types);
		for ($i = 0; $i < $t; $i++) {
			$type = $types[$i];
			$items = isset($data[$type]) ? $data[$type] : null;
			
			$total = $items ? count($items) : 0;
			for ($j = 0; $j < $total; $j++) {
				$item = $items[$j];
				$table = null;
				
				if (!empty($item["table"]))
					$table = $item["table"];
				else if (!empty($item["ptable"])) 
					$table = $item["ptable"];
				else if (!empty($item["ftable"]))
					$table = $item["ftable"];
				
				if ($table && self::getTableName($table) == $table_name && self::getAlias($table))
					return self::getAlias($table);
			}
		}
		
		return "";
	}
	
	private static function getAllTables($data, $main_table) {
		$types = array("attributes", "keys", "conditions", "groups_by", "sorts");
		
		$tables = array();
		$t = count($types);
		for ($i = 0; $i < $t; $i++) {
			$type = $types[$i];
			$items = isset($data[$type]) ? $data[$type] : null;
			
			$total = $items ? count($items) : 0;
			for ($j = 0; $j < $total; $j++) {
				$item = $items[$j];
				
				$table = null;
				
				if (!empty($item["table"]))
					$table = $item["table"];
				else if (!empty($item["ptable"]))
					$table = $item["ptable"];
				else if (!empty($item["ftable"]))
					$table = $item["ftable"];
				
				if (isset($table)) {
					$table = !$table ? $main_table : $table;
					$tables[ $table ] = false;
				}
			}
		}
		
		return $tables;
	}
	
	//checks if a base_expr is #var_name# or $var_name
	private static function isBaseExpressionSQLVariable($base_expr) {
		$base_expr = trim($base_expr);
		return substr($base_expr, 0, 1) == '$' || substr($base_expr, 0, 2) == '@$' || (substr($base_expr, 0, 1) == "#" && substr($base_expr, -1, 1) == "#");
	}
	
	//used in DB::parseTableName in DB.php
	public static function parseTableName($table, $start_delimiter = "`", $end_delimiter = false) {
		if ($table) {
			if ($start_delimiter) {
				if (!$end_delimiter)
					$end_delimiter = $start_delimiter;
				
				$parts = array();
				$len = strlen($table);
				$start = 0;
				
				do {
					$pos = strpos($table, ".", $start); //split based in "."
					$pos_delimiter = strpos($table, $start_delimiter, $start); //split based in delimiters
					$delimiter_active = false;
					
					if ($pos_delimiter !== false && ($pos === false || $pos > $pos_delimiter)) {
						$pos = $pos_delimiter; //set pos to start delimiter
						$delimiter_active = true;
					}
					
					if ($pos === false) 
						$pos = $len; //set delimiter to length
					
					$str = trim( substr($table, $start, $pos - $start) );
					$start = $pos + 1;
					
					if ($str)
						$parts[] = $str;
					
					if ($delimiter_active && $start < $len) {
						$pos = strpos($table, $end_delimiter, $start);
						$pos = $pos !== false ? $pos : $len;
						
						$str = substr($table, $start, $pos - $start); //Don't trim $str bc is inside of enclosing, which means the user really wanted to leave a space here.
						$start = $pos + 1;
						
						if ($str)
							$parts[] = $str;
					}
				}
				while ($start < $len);
			}
			else
				$parts = explode(".", $table);
			
			return $parts;
		}
		
		return array($table); //this should be an empty value.
	}
}
?>
