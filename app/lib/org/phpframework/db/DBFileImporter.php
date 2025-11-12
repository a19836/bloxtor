<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

include_once get_lib("org.phpframework.db.DB");
include_once get_lib("org.phpframework.util.text.TextSanitizer");

class DBFileImporter { 
	private $DBDriver;
	
	private $options;
	private $errors;
	private $wrong_lines;
	private $wrong_sqls;
	private $wrong_data;
	
	public function __construct(DB $DBDriver) {
		$this->DBDriver = $DBDriver;
		
		$this->options = array(
			"ignore_rows_number" => 1,
			"rows_delimiter" => "\n",
			"columns_delimiter" => "\t",
			"enclosed_by" => "",
			"insert_ignore" => true,
			"update_existent" => false,
		);
		$this->errors = array();
		$this->wrong_lines = array();
		$this->wrong_sqls = array();
		$this->wrong_data = array();
		
		if (!$DBDriver)
			launch_exception(new Exception("DBFileImporter 1st argument must be a DBDriver and cannot be null!"));
	}
	
	public function getErrors() {
		return $this->errors;
	}
	
	public function getWrongLines() {
		return $this->wrong_lines;
	}
	
	public function getWrongSQLs() {
		return $this->wrong_sqls;
	}
	
	public function getWrongData() {
		return $this->wrong_data;
	}
	
	public function setOptions($options) {
		if (is_array($options))
			foreach ($options as $opt_name => $opt_value)
				$this->options[$opt_name] = $opt_value;
	}
	
	//$force means that even if a line gives an error, the script will continute to insert the other records.
	public function importFile($file_path, $table, $columns_attributes, $force = false) {
		$status = false;
		
		if (file_exists($file_path) && $columns_attributes) {
			$columns_attributes_exists = false;
			
			foreach ($columns_attributes as $idx => $attribute_name)
				if ($attribute_name) {
					$columns_attributes_exists = true;
					break;
				}
			
			if ($columns_attributes_exists) {
				$handle = fopen($file_path, "r");
			
				if ($handle) {
					$pks = array();
					
					if ($this->options["update_existent"]) {
						$db_table_attrs = $this->DBDriver->listTableFields($table);
						
						if ($db_table_attrs)
							foreach ($db_table_attrs as $attr) 
								if (!empty($attr["primary_key"]))
									$pks[] = $attr["name"];
					}
					
					$status = true;
					$line = $new_line = "";
					$lines_count = 0;
					
					while (($buffer = fgets($handle, 4096)) !== false) {
						$pos = strpos($buffer, $this->options["rows_delimiter"]);
						
						if ($pos !== false) {
							$line .= substr($buffer, 0, $pos);
							$new_line = substr($buffer, $pos + 1);
							
							if ($this->options["rows_delimiter"] == "\n" || $this->options["rows_delimiter"] == "\r") {
								$line = str_replace(array("\n", "\r"), "", $line);
								$new_line = str_replace(array("\n", "\r"), "", $new_line);
							}
							
							//calls parseLine if $lines_count > $this->options["ignore_rows_number"]
							$lines_count++;
							
							if ($line && $lines_count > $this->options["ignore_rows_number"] && !$this->parseLine($line, $table, $columns_attributes, $pks)) {
								$status = false;
								
								if (!$force) {
									$line = null;
									break;
								}
							}
							
							$line = $new_line;
						}
						else
							$line .= $buffer;
					}
					
					//calls parseLine if $lines_count > $this->options["ignore_rows_number"]
					$lines_count++;
					
					if ($line && $lines_count > $this->options["ignore_rows_number"] && !$this->parseLine($line, $table, $columns_attributes, $pks))
						$status = false;
					
					fclose($handle);
				}
			}
		}
		
		return $status;
	}
	
	public function parseLine($line, $table, $columns_attributes, $pks = null) {
		$columns_values = self::convertLineToArray($line);
		
		$data = array();
		$data_by_attr_name = array();
		$is_update = false;
		
		if ($columns_attributes)
			foreach ($columns_attributes as $idx => $attribute_name)
				if ($attribute_name) {
					$value = isset($columns_values[$idx]) ? $columns_values[$idx] : null;
					$data[] = array(
						"column" => $attribute_name, 
						"value" => $value
					);
					$data_by_attr_name[$attribute_name] = $value;
				}
		
		if (!$data) { //if no data it means that $columns_attributes don't have any attribute_name, so nothing should be done!
			$this->errors[] = "Error: no data to be inserted!";
			return false;
		}
		
		/*
		 * Only checks if record exists if $pks exists too, otherwise it doesn't make sense to check, bc there are no PKs. 
		 * Do not confuse PK with unique keys. In case exists unique keys, and exists records with repeated value, the system should fail and return error on insert statement!
		 * Only executes update if there are PKs!!!
		 */
		if ($this->options["update_existent"] && $pks) {
			$conditions = array();
			
			foreach ($pks as $pk)
				if (in_array($pk, $columns_attributes))
					$conditions[] = array(
						"column" => $pk, 
						"value" => isset($data_by_attr_name[$pk]) ? $data_by_attr_name[$pk] : null
					);
			
			$sql = $this->DBDriver->convertObjectToSQL(array(
				"type" => "select",
				"main_table" => $table,
				"attributes" => array(
					array("column" => "count($pk)", "name" => "total")
				),
				"conditions" => $conditions,
			));
			
			$exists = $this->DBDriver->getData($sql);
			$is_update = isset($exists["result"][0]["total"]) && $exists["result"][0]["total"] > 0;
		}
		
		if ($is_update)
			$sql = $this->DBDriver->convertObjectToSQL(array(
				"type" => "update",
				"main_table" => $table,
				"attributes" => $data,
				"conditions" => isset($conditions) ? $conditions : null,
			));
		else
			$sql = $this->DBDriver->convertObjectToSQL(array(
				"type" => "insert",
				"main_table" => $table,
				"attributes" => $data,
				"ignore" => $this->options["insert_ignore"],
			));
		
		$status = $this->DBDriver->setData($sql);
		
		if ($status === true) 
			return true;
		
		//prepare errors
		$this->wrong_lines[] = $line;
		$this->wrong_sqls[] = $sql;
		$this->wrong_data[] = $data_by_attr_name;
		
		$msg = is_a($status, "Exception") ? "\n\n" . $status->getMessage() : "";
		$this->errors[] = "Error trying to insert sql: $sql" . $msg;
		
		return false;
	}
	
	public function convertLineToArray($line) {
		if ($this->options["enclosed_by"]) {
			$chars = TextSanitizer::mbStrSplit($line);
			$l = count($chars);
			$column_value = "";
			$is_open = false;
			$columns_values = array();
			
			for ($i = 0; $i < $l; $i++) {
				$c = $chars[$i];
				
				if ($c == $this->options["enclosed_by"] && !TextSanitizer::isMBCharEscaped($line, $i, $chars)) {
					$is_open = !$is_open;
					$column_value .= $c;
				}
				else if ($c == $this->options["columns_delimiter"] && !$is_open) {
					if ($column_value[0] == $this->options["enclosed_by"] && substr($column_value, -1) == $this->options["enclosed_by"])
						$column_value = TextSanitizer::stripCSlashes(substr($column_value, 1, -1), $this->options["enclosed_by"]);
					
					$columns_values[] = $column_value;
					$column_value = "";
				}
				else
					$column_value .= $c;
			}
			
			if ($column_value) {
				if ($column_value[0] == $this->options["enclosed_by"] && substr($column_value, -1) == $this->options["enclosed_by"])
					$column_value = TextSanitizer::stripCSlashes(substr($column_value, 1, -1), $this->options["enclosed_by"]);
				
				$columns_values[] = $column_value;
			}
		}
		else
			$columns_values = explode($this->options["columns_delimiter"], $line);
		
		return $columns_values;
	}
}
?>
