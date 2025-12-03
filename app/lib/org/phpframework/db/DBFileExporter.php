<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 *
 * Original Bloxtor Repo: https://github.com/a19836/bloxtor
 *
 * YOU ARE NOT AUTHORIZED TO MODIFY OR REMOVE ANY PART OF THIS NOTICE!
 */

include_once get_lib("org.phpframework.db.DB");
include_once get_lib("org.phpframework.util.text.TextValidator");

class DBFileExporter { 
	private $DBDriver;
	
	private $options;
	private $errors;
	
	public function __construct(DB $DBDriver) {
		$this->DBDriver = $DBDriver;
		
		$this->options = array(
			"rows_delimiter" => "\n",
			"columns_delimiter" => "\t",
			"enclosed_by" => "",
			"export_type" => "txt",
			"include_sep_header" => false
		);
		$this->errors = array();
		
		if (!$DBDriver)
			launch_exception(new Exception("DBFileExporter 1st argument must be a DBDriver and cannot be null!"));
	}
	
	public function getErrors() {
		return $this->errors;
	}
	
	public function setOptions($options) {
		if (is_array($options)) {
			foreach ($options as $opt_name => $opt_value)
				$this->options[$opt_name] = $opt_value;
			
			if (!isset($options["include_sep_header"]))
				$this->options["include_sep_header"] = $this->options["export_type"] == "csv";
			
			//set default export_type
			if (!isset($options["export_type"]) && !$this->options["export_type"])
				$this->options["export_type"] = "txt";
			
			//set default rows_delimiter
			if (!isset($options["rows_delimiter"]) && !$this->options["rows_delimiter"])
				$this->options["rows_delimiter"] = "\n";
			
			//set default columns_delimiter
			if (!isset($options["columns_delimiter"]))
				$this->options["columns_delimiter"] = $this->options["export_type"] == "csv" ? "," : "\t";
			
			//set default enclosed_by
			if (!isset($options["enclosed_by"]))
				$this->options["enclosed_by"] = $this->options["export_type"] == "csv" ? '"' : "";
		}
	}
	
	public function exportFile($sql, $doc_name = null, $stop = true) {
		$status = false;
		
		if (!$sql) 
			$this->errors[] = "Please write a select sql statement.";
		else {
			try {
				$data = $this->DBDriver->getData($sql);
				//echo "<pre>";print_r($data);die();
				
				//set output
				$str = "";
				
				if ($data && is_array($data)) {
					$columns = isset($data["fields"]) ? $data["fields"] : null;
					$columns_length = count($columns);
					$results = isset($data["result"]) ? $data["result"] : null;
					
					$export_type = $this->options["export_type"];
					$rows_delimiter = $this->options["rows_delimiter"];
					$columns_delimiter = $this->options["columns_delimiter"];
					$enclosed_by = $this->options["enclosed_by"];
					$include_sep_header = $this->options["include_sep_header"];
					
					//Alguns programas, como o Microsoft Excel 2010, requerem ainda um indicador "sep=" na primeira linha do arquivo, apontando o caráter de separação.
					if ($include_sep_header) 
						$str .= "sep=$columns_delimiter$rows_delimiter";
					
					//prepare columns
					for ($i = 0; $i < $columns_length; $i++)
						$str .= ($i > 0 ? $columns_delimiter : "") . $enclosed_by . addcslashes($columns[$i]->name, $columns_delimiter . $enclosed_by . "\\") . $enclosed_by;
					
					//prepare rows
					if ($str && is_array($results)) {
						$str .= $rows_delimiter;
						
						foreach ($results as $row)
							if (is_array($row)) {
								for ($i = 0; $i < $columns_length; $i++) {
									$val = $row[ $columns[$i]->name ];
									
									if (TextValidator::isBinary($val))
										$val = base64_encode($val);
									
									$str .= ($i > 0 ? $columns_delimiter : "") . $enclosed_by . addcslashes($val, $columns_delimiter . $enclosed_by . "\\") . $enclosed_by;
								}
								
								$str .= $rows_delimiter;
							}
					}
				}
				
				$status = true;
				
				//set header
				if (!$doc_name) {
					$doc_name = self::getSQLTable($sql);
					
					if (!$doc_name)
						$doc_name = "table_export";
				}
				
				$content_type = $export_type == "xls" ? "application/vnd.ms-excel" : ($export_type == "csv" ? "text/csv" : "text/plain");
				
				header("Content-Type: $content_type");
				header('Content-Disposition: attachment; filename="' . $doc_name . '.' . $export_type . '"');
				
				//print export file
				echo $str;
				
				if ($stop)
					die();
			}
			catch(Exception $e) {
				$message = $e->getMessage();
				$problem = isset($e->problem) ? $e->problem : null;
				$msg = $message != $problem ? "$message\n$problem" : $problem;
				$this->errors[] = $msg;
				
				launch_exception($e);
			}
		}
		
		return $status;
	}
	
	private static function getSQLTable($sql) {
		// Normalize
		$sql = trim($sql);

		// Remove inner SELECT (...) blocks to avoid false FROM matches
		$clean = preg_replace('/\(\s*select[\s\S]*?\)/i', '(subquery)', $sql);

		// Match FROM ... and get the first table after it
		if (preg_match('/\bfrom\s+([`"]?[\w\.]+[`"]?)/i', $clean, $m))
			return trim($m[1], "`\"");

		return null;
	}
}
?>
