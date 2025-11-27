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

namespace WorkFlowTask\programming\sendemail;

include_once get_lib("org.phpframework.workflow.WorkFlowTask");

class WorkFlowTaskImpl extends \WorkFlowTask {
	
	public function createTaskPropertiesFromCodeStmt($stmt, $WorkFlowTaskCodeParser, &$exits = null, &$inner_tasks = null) {
		$props = $WorkFlowTaskCodeParser->getObjectMethodProps($stmt);
		
		if ($props && !empty($props["method_name"]) && !empty($props["method_static"]) && isset($props["method_obj"]) && $props["method_obj"] == "SendEmailHandler") {
			$method_name = $props["method_name"];
			
			if ($method_name == "sendEmail" || $method_name == "sendSMTPEmail") {
				$args = isset($props["method_args"]) ? $props["method_args"] : null;
				
				$props["method"] = $props["method_obj"] . "::$method_name";
				
				$settings = isset($args[0]["value"]) ? $args[0]["value"] : null;
				$settings_type = isset($args[0]["type"]) ? $args[0]["type"] : null;
				
				if ($settings_type == "array") {
					$param_stmts = $WorkFlowTaskCodeParser->getPHPMultipleParser()->parse("<?php\n" . $settings . "\n?>");
					//print_r($param_stmts);
					$items = $WorkFlowTaskCodeParser->getStmtArrayItems($param_stmts[0]);
					$settings = $WorkFlowTaskCodeParser->getArrayItems($items);
				}
				
				$props["settings"] = $settings;
				$props["settings_type"] = self::getConfiguredParsedType($settings_type, array("", "string", "variable", "array"));
				
				unset($props["method_name"]);
				unset($props["method_obj"]);
				unset($props["method_args"]);
				unset($props["method_static"]);
				
				$props["label"] = $method_name;
				
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
		
		$settings_type = isset($raw_data["childs"]["properties"][0]["childs"]["settings_type"][0]["value"]) ? $raw_data["childs"]["properties"][0]["childs"]["settings_type"][0]["value"] : null;
		if ($settings_type == "array") {
			$settings = isset($raw_data["childs"]["properties"][0]["childs"]["settings"]) ? $raw_data["childs"]["properties"][0]["childs"]["settings"] : null;
			$settings = self::parseArrayItems($settings);
		}
		else {
			$settings = isset($raw_data["childs"]["properties"][0]["childs"]["settings"][0]["value"]) ? $raw_data["childs"]["properties"][0]["childs"]["settings"][0]["value"] : null;
		}
		
		$properties = array(
			"method" => isset($raw_data["childs"]["properties"][0]["childs"]["method"][0]["value"]) ? $raw_data["childs"]["properties"][0]["childs"]["method"][0]["value"] : null,
			"settings" => $settings,
			"settings_type" => $settings_type,
		);
		
		$properties = self::parseResultVariableProperties($raw_data, $properties);
		
		return $properties;
	}
	
	public function printCode($tasks, $stop_task_id, $prefix_tab = "", $options = null) {
		$data = isset($this->data) ? $this->data : null;
		
		$properties = isset($data["properties"]) ? $data["properties"] : null;
		
		$var_name = self::getPropertiesResultVariableCode($properties);
		$method = isset($properties["method"]) ? $properties["method"] : null;
		$code = "";
		
		if ($method) {
			$settings_type = isset($properties["settings_type"]) ? $properties["settings_type"] : null;
			$settings = isset($properties["settings"]) ? $properties["settings"] : null;
			
			if ($settings_type == "array")
				$settings = self::getArrayString($settings);
			else
				$settings = self::getVariableValueCode($settings, $settings_type);
			
			$code = $prefix_tab . $var_name . "$method(";
			$code .= $settings ? $settings : "null";
			$code .= ");\n";
		}
		
		$exit_task_id = isset($data["exits"][self::DEFAULT_EXIT_ID]) ? $data["exits"][self::DEFAULT_EXIT_ID] : null;
		return $code . self::printTask($tasks, $exit_task_id, $stop_task_id, $prefix_tab, $options);
	}
}
?>
