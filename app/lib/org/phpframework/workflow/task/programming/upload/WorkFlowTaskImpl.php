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

namespace WorkFlowTask\programming\upload;

include_once get_lib("org.phpframework.workflow.WorkFlowTask");
include_once get_lib("org.phpframework.util.web.UploadHandler");

class WorkFlowTaskImpl extends \WorkFlowTask {
	
	public function createTaskPropertiesFromCodeStmt($stmt, $WorkFlowTaskCodeParser, &$exits = null, &$inner_tasks = null) {
		$props = $WorkFlowTaskCodeParser->getObjectMethodProps($stmt);
		
		if ($props && isset($props["method_name"]) && $props["method_name"] == "upload" && !empty($props["method_static"]) && isset($props["method_obj"]) && $props["method_obj"] == "UploadHandler") {
			$args = isset($props["method_args"]) ? $props["method_args"] : null;
			
			$file = isset($args[0]["value"]) ? $args[0]["value"] : null;
			$file_type = isset($args[0]["type"]) ? $args[0]["type"] : null;
			$dst_folder = isset($args[1]["value"]) ? $args[1]["value"] : null;
			$dst_folder_type = isset($args[1]["type"]) ? $args[1]["type"] : null;
			$validation = isset($args[2]["value"]) ? $args[2]["value"] : null;
			$validation_type = isset($args[2]["type"]) ? $args[2]["type"] : null;
			
			$props["file"] = $file;
			$props["file_type"] = self::getConfiguredParsedType($file_type);
			$props["dst_folder"] = $dst_folder;
			$props["dst_folder_type"] = self::getConfiguredParsedType($dst_folder_type);
			
			if ($validation_type == "array") {
				$param_stmts = $WorkFlowTaskCodeParser->getPHPMultipleParser()->parse("<?php\n" . $validation . "\n?>");
				//print_r($param_stmts);
				$items = $WorkFlowTaskCodeParser->getStmtArrayItems($param_stmts[0]);
				$validation = $WorkFlowTaskCodeParser->getArrayItems($items);
			}
			
			$props["validation"] = $validation;
			$props["validation_type"] = self::getConfiguredParsedType($validation_type, array("", "string", "variable", "array"));
			
			unset($props["method_name"]);
			unset($props["method_obj"]);
			unset($props["method_args"]);
			unset($props["method_static"]);
			
			$props["label"] = "Upload " . self::prepareTaskPropertyValueLabelFromCodeStmt($file);
			
			$props["exits"] = array(
				self::DEFAULT_EXIT_ID => array(
					"color" => "#426efa",
				),
			);
			//print_r($props);die();
			
			return $props;
		}
	}
	
	public function parseProperties(&$task) {
		$raw_data = isset($task["raw_data"]) ? $task["raw_data"] : null;
		
		$validation_type = isset($raw_data["childs"]["properties"][0]["childs"]["validation_type"][0]["value"]) ? $raw_data["childs"]["properties"][0]["childs"]["validation_type"][0]["value"] : null;
		if ($validation_type == "array") {
			$validation = isset($raw_data["childs"]["properties"][0]["childs"]["validation"]) ? $raw_data["childs"]["properties"][0]["childs"]["validation"] : null;
			$validation = self::parseArrayItems($validation);
		}
		else {
			$validation = isset($raw_data["childs"]["properties"][0]["childs"]["validation"][0]["value"]) ? $raw_data["childs"]["properties"][0]["childs"]["validation"][0]["value"] : null;
		}
		
		$properties = array(
			"method" => isset($raw_data["childs"]["properties"][0]["childs"]["method"][0]["value"]) ? $raw_data["childs"]["properties"][0]["childs"]["method"][0]["value"] : null,
			"file" => isset($raw_data["childs"]["properties"][0]["childs"]["file"][0]["value"]) ? $raw_data["childs"]["properties"][0]["childs"]["file"][0]["value"] : null,
			"file_type" => isset($raw_data["childs"]["properties"][0]["childs"]["file_type"][0]["value"]) ? $raw_data["childs"]["properties"][0]["childs"]["file_type"][0]["value"] : null,
			"dst_folder" => isset($raw_data["childs"]["properties"][0]["childs"]["dst_folder"][0]["value"]) ? $raw_data["childs"]["properties"][0]["childs"]["dst_folder"][0]["value"] : null,
			"dst_folder_type" => isset($raw_data["childs"]["properties"][0]["childs"]["dst_folder_type"][0]["value"]) ? $raw_data["childs"]["properties"][0]["childs"]["dst_folder_type"][0]["value"] : null,
			"validation" => $validation,
			"validation_type" => $validation_type,
		);
		
		$properties = self::parseResultVariableProperties($raw_data, $properties);
		
		return $properties;
	}
	
	public function printCode($tasks, $stop_task_id, $prefix_tab = "", $options = null) {
		$data = isset($this->data) ? $this->data : null;
		
		$properties = isset($data["properties"]) ? $data["properties"] : null;
		
		$var_name = self::getPropertiesResultVariableCode($properties);
		
		$file = isset($properties["file"]) ? $properties["file"] : null;
		$file = self::getVariableValueCode($file, isset($properties["file_type"]) ? $properties["file_type"] : null);
		
		$dst_folder = isset($properties["dst_folder"]) ? $properties["dst_folder"] : null;
		$dst_folder = self::getVariableValueCode($dst_folder, isset($properties["dst_folder_type"]) ? $properties["dst_folder_type"] : null);
		
		$validation_type = isset($properties["validation_type"]) ? $properties["validation_type"] : null;
		$validation = isset($properties["validation"]) ? $properties["validation"] : null;
		if ($validation_type == "array")
			$validation = self::getArrayString($validation);
		else
			$validation = self::getVariableValueCode($validation, $validation_type);
		
		$code = $prefix_tab . $var_name . "UploadHandler::upload($file, $dst_folder";
		$code .= $validation ? ", $validation" : "";
		$code .= ");\n";
		
		$exit_task_id = isset($data["exits"][self::DEFAULT_EXIT_ID]) ? $data["exits"][self::DEFAULT_EXIT_ID] : null;
		return $code . self::printTask($tasks, $exit_task_id, $stop_task_id, $prefix_tab, $options);
	}
}
?>
