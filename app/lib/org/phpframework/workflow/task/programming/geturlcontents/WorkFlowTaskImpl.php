<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

namespace WorkFlowTask\programming\geturlcontents;

include_once get_lib("org.phpframework.workflow.WorkFlowTask");

class WorkFlowTaskImpl extends \WorkFlowTask {
	
	//restconnector task changes this
	protected $method_obj = "MyCurl";
	protected $method_name = "getUrlContents";
	
	public function createTaskPropertiesFromCodeStmt($stmt, $WorkFlowTaskCodeParser, &$exits = null, &$inner_tasks = null) {
		$props = $WorkFlowTaskCodeParser->getObjectMethodProps($stmt);
		
		if ($props) {
			$method_name = isset($props["method_name"]) ? $props["method_name"] : null;
			
			if ($method_name == $this->method_name && !empty($props["method_static"]) && isset($props["method_obj"]) && $props["method_obj"] == $this->method_obj) {
				$args = isset($props["method_args"]) ? $props["method_args"] : null;
				
				$data = isset($args[0]["value"]) ? $args[0]["value"] : null;
				$data_type = isset($args[0]["type"]) ? $args[0]["type"] : null;
				
				$result_type = isset($args[1]["value"]) ? $args[1]["value"] : null;
				$result_type_type = isset($args[1]["type"]) ? $args[1]["type"] : null;
				
				if ($data_type == "array") {
					$data_stmts = $WorkFlowTaskCodeParser->getPHPMultipleParser()->parse("<?php\n" . $data . "\n?>");
					$items = $WorkFlowTaskCodeParser->getStmtArrayItems($data_stmts[0]);
					$data = $WorkFlowTaskCodeParser->getArrayItems($items);
				}
				
				unset($props["method_obj"]);
				unset($props["method_name"]);
				unset($props["method_args"]);
				unset($props["method_static"]);
				
				$props["data"] = $data;
				$props["data_type"] = $data_type;
				$props["result_type"] = $result_type;
				$props["result_type_type"] = self::getConfiguredParsedType($result_type_type);
				
				$props["label"] = "Get curl url";
				
				$props["exits"] = array(
					self::DEFAULT_EXIT_ID => array(
						"color" => "#426efa",
					),
				);
				
				return $props;
			}
		}
	}
	
	public function parseProperties(&$task) {
		$raw_data = isset($task["raw_data"]) ? $task["raw_data"] : null;
		
		$data_type = isset($raw_data["childs"]["properties"][0]["childs"]["data_type"][0]["value"]) ? $raw_data["childs"]["properties"][0]["childs"]["data_type"][0]["value"] : null;
		if ($data_type == "array") {
			$data = isset($raw_data["childs"]["properties"][0]["childs"]["data"]) ? $raw_data["childs"]["properties"][0]["childs"]["data"] : null;
			$data = self::parseArrayItems($data);
		}
		else
			$data = isset($raw_data["childs"]["properties"][0]["childs"]["data"][0]["value"]) ? $raw_data["childs"]["properties"][0]["childs"]["data"][0]["value"] : null;
		
		$properties = array(
			"data" => $data,
			"data_type" => $data_type,
			"result_type" => isset($raw_data["childs"]["properties"][0]["childs"]["result_type"][0]["value"]) ? $raw_data["childs"]["properties"][0]["childs"]["result_type"][0]["value"] : null,
			"result_type_type" => isset($raw_data["childs"]["properties"][0]["childs"]["result_type_type"][0]["value"]) ? $raw_data["childs"]["properties"][0]["childs"]["result_type_type"][0]["value"] : null,
		);
		
		$properties = self::parseResultVariableProperties($raw_data, $properties);
		
		return $properties;
	}
	
	public function printCode($tasks, $stop_task_id, $prefix_tab = "", $options = null) {
		$data = isset($this->data) ? $this->data : null;
		
		$properties = isset($data["properties"]) ? $data["properties"] : null;
		
		$var_name = self::getPropertiesResultVariableCode($properties);
		
		$dt_type = isset($properties["data_type"]) ? $properties["data_type"] : null;
		$dt = isset($properties["data"]) ? $properties["data"] : null;
		if ($dt_type == "array")
			$dt = self::getArrayString($dt);
		else
			$dt = self::getVariableValueCode($dt, $dt_type);
		
		$result_type_type = isset($properties["result_type_type"]) ? $properties["result_type_type"] : null;
		$result_type = isset($properties["result_type"]) ? $properties["result_type"] : null;
		
		$code  = $prefix_tab . $var_name . $this->method_obj . "::" . $this->method_name . "(" . $dt . ($result_type ? ", " . self::getVariableValueCode($result_type, $result_type_type) : "" ) . ");\n";
		
		$exit_task_id = isset($data["exits"][self::DEFAULT_EXIT_ID]) ? $data["exits"][self::DEFAULT_EXIT_ID] : null;
		return $code . self::printTask($tasks, $exit_task_id, $stop_task_id, $prefix_tab, $options);
	}
}
?>
