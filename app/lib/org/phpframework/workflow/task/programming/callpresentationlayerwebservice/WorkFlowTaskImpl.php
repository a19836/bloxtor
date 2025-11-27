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

namespace WorkFlowTask\programming\callpresentationlayerwebservice;

include_once get_lib("org.phpframework.workflow.WorkFlowTask");

class WorkFlowTaskImpl extends \WorkFlowTask {

	public function createTaskPropertiesFromCodeStmt($stmt, $WorkFlowTaskCodeParser, &$exits = null, &$inner_tasks = null) {
		$props = $WorkFlowTaskCodeParser->getFunctionProps($stmt);
		
		if ($props) {
			$func_name = isset($props["func_name"]) ? $props["func_name"] : null;
			$args = isset($props["func_args"]) ? $props["func_args"] : null;
			
			//$html = call_presentation_layer_web_service(array("presentation_id" => $presentation_id, "url" => false, "external_vars" => $external_vars, "includes" => $includes, "includes_once" => $includes_once));
			if ($func_name && strtolower($func_name) == "call_presentation_layer_web_service") {
				$settings = isset($args[0]["value"]) ? $args[0]["value"] : null;
				$settings_type = isset($args[0]["type"]) ? $args[0]["type"] : null;
				
				$project = $project_type = $page = $page_type = $external_vars = $external_vars_type = $includes = $includes_type = $includes_once = $includes_once_type = null;
				
				if ($settings_type == "array") {
					$settings_stmts = $WorkFlowTaskCodeParser->getPHPMultipleParser()->parse("<?php\n" . $settings . "\n?>");
					$items = $WorkFlowTaskCodeParser->getStmtArrayItems($settings_stmts[0]);
					$settings = $WorkFlowTaskCodeParser->getArrayItems($items);
					
					if (is_array($settings)) {
						$t = count($settings);
						for ($i = 0; $i < $t; $i++) {
							$setting = $settings[$i];
							$setting_key = isset($setting["key"]) ? $setting["key"] : null;
							$setting_value = isset($setting["value"]) ? $setting["value"] : null;
							$setting_value_type = isset($setting["value_type"]) ? $setting["value_type"] : null;
							
							switch (strtolower($setting_key)) {
								case "presentation_id": 
									$project = $setting_value;
									$project_type = $setting_value_type;
									break;
								case "url": 
									$page = $setting_value;
									$page_type = $setting_value_type;
									break;
								case "external_vars": 
									if (isset($setting["items"])) {
										$external_vars = $setting["items"];
										$external_vars_type = "array";
									}
									else {
										$external_vars = $setting_value;
										$external_vars_type = $setting_value_type;
									}
									break;
								case "includes": 
									if (isset($setting["items"])) {
										$includes = $setting["items"];
										$includes_type = "array";
									}
									else {
										$includes = $setting_value;
										$includes_type = $setting_value_type;
									}
									break;
								case "includes_once":
									if (isset($setting["items"])) {
										$includes_once = $setting["items"];
										$includes_once_type = "array";
									}
									else {
										$includes_once = $setting_value;
										$includes_once_type = $setting_value_type;
									}
									break;
							}
						}	
					}
				}
				
				unset($props["func_name"]);
				unset($props["func_args"]);
				
				$new_props = array(
					"project" => $project,
					"project_type" => self::getConfiguredParsedType($project_type),
					"page" => $page,
					"page_type" => self::getConfiguredParsedType($page_type),
					"external_vars" => $external_vars,
					"external_vars_type" => self::getConfiguredParsedType($external_vars_type, array("", "string", "variable", "array")),
					"includes" => $includes,
					"includes_type" => self::getConfiguredParsedType($includes_type, array("", "string", "variable", "array")),
					"includes_once" => $includes_once,
					"includes_once_type" => self::getConfiguredParsedType($includes_once_type, array("", "string", "variable", "array")),
					"exits" => array(
						self::DEFAULT_EXIT_ID => array(
							"color" => "#426efa",
						),
					),
				);
				
				$props = array_merge($props, $new_props);
				
				$props["label"] = "Call " . self::prepareTaskPropertyValueLabelFromCodeStmt($project) . "/" . self::prepareTaskPropertyValueLabelFromCodeStmt($page);
				
				return $props;
			}
		}
	}
	
	public function parseProperties(&$task) {
		$raw_data = isset($task["raw_data"]) ? $task["raw_data"] : null;
		
		$external_vars_type = isset($raw_data["childs"]["properties"][0]["childs"]["external_vars_type"][0]["value"]) ? $raw_data["childs"]["properties"][0]["childs"]["external_vars_type"][0]["value"] : null;
		if ($external_vars_type == "array") {
			$external_vars = isset($raw_data["childs"]["properties"][0]["childs"]["external_vars"]) ? $raw_data["childs"]["properties"][0]["childs"]["external_vars"] : null;
			$external_vars = self::parseArrayItems($external_vars);
		}
		else
			$external_vars = isset($raw_data["childs"]["properties"][0]["childs"]["external_vars"][0]["value"]) ? $raw_data["childs"]["properties"][0]["childs"]["external_vars"][0]["value"] : null;
		
		$includes_type = isset($raw_data["childs"]["properties"][0]["childs"]["includes_type"][0]["value"]) ? $raw_data["childs"]["properties"][0]["childs"]["includes_type"][0]["value"] : null;
		if ($includes_type == "array") {
			$includes = isset($raw_data["childs"]["properties"][0]["childs"]["includes"]) ? $raw_data["childs"]["properties"][0]["childs"]["includes"] : null;
			$includes = self::parseArrayItems($includes);
		}
		else
			$includes = isset($raw_data["childs"]["properties"][0]["childs"]["includes"][0]["value"]) ? $raw_data["childs"]["properties"][0]["childs"]["includes"][0]["value"] : null;
		
		$includes_once_type = isset($raw_data["childs"]["properties"][0]["childs"]["includes_once_type"][0]["value"]) ? $raw_data["childs"]["properties"][0]["childs"]["includes_once_type"][0]["value"] : null;
		if ($includes_once_type == "array") {
			$includes_once = isset($raw_data["childs"]["properties"][0]["childs"]["includes_once"]) ? $raw_data["childs"]["properties"][0]["childs"]["includes_once"] : null;
			$includes_once = self::parseArrayItems($includes_once);
		}
		else
			$includes_once = isset($raw_data["childs"]["properties"][0]["childs"]["includes_once"][0]["value"]) ? $raw_data["childs"]["properties"][0]["childs"]["includes_once"][0]["value"] : null;
		
		$properties = array(
			"project" => isset($raw_data["childs"]["properties"][0]["childs"]["project"][0]["value"]) ? $raw_data["childs"]["properties"][0]["childs"]["project"][0]["value"] : null,
			"project_type" => isset($raw_data["childs"]["properties"][0]["childs"]["project_type"][0]["value"]) ? $raw_data["childs"]["properties"][0]["childs"]["project_type"][0]["value"] : null,
			"page" => isset($raw_data["childs"]["properties"][0]["childs"]["page"][0]["value"]) ? $raw_data["childs"]["properties"][0]["childs"]["page"][0]["value"] : null,
			"page_type" => isset($raw_data["childs"]["properties"][0]["childs"]["page_type"][0]["value"]) ? $raw_data["childs"]["properties"][0]["childs"]["page_type"][0]["value"] : null,
			"external_vars" => $external_vars,
			"external_vars_type" => $external_vars_type,
			"includes" => $includes,
			"includes_type" => $includes_type,
			"includes_once" => $includes_once,
			"includes_once_type" => $includes_once_type,
		);
		
		$properties = self::parseResultVariableProperties($raw_data, $properties);
		
		return $properties;
	}
	
	public function printCode($tasks, $stop_task_id, $prefix_tab = "", $options = null) {
		$data = isset($this->data) ? $this->data : null;
		
		$properties = isset($data["properties"]) ? $data["properties"] : null;
		
		$code = "";
		if (!empty($properties["project"]) && !empty($properties["page"])) {
			$var_name = self::getPropertiesResultVariableCode($properties);
		
			$external_vars_type = isset($properties["external_vars_type"]) ? $properties["external_vars_type"] : null;
			$external_vars = isset($properties["external_vars"]) ? $properties["external_vars"] : null;
			if ($external_vars_type == "array")
				$external_vars = self::getArrayString($external_vars);
			else
				$external_vars = self::getVariableValueCode($external_vars, $external_vars_type);
			
			$includes_type = isset($properties["includes_type"]) ? $properties["includes_type"] : null;
			$includes = isset($properties["includes"]) ? $properties["includes"] : null;
			if ($includes_type == "array")
				$includes = self::getArrayString($includes);
			else
				$includes = self::getVariableValueCode($includes, $includes_type);
			
			$includes_once_type = isset($properties["includes_once_type"]) ? $properties["includes_once_type"] : null;
			$includes_once = isset($properties["includes_once"]) ? $properties["includes_once"] : null;
			if ($includes_once_type == "array")
				$includes_once = self::getArrayString($includes_once);
			else
				$includes_once = self::getVariableValueCode($includes_once, $includes_once_type);
			
			$project_type = isset($properties["project_type"]) ? $properties["project_type"] : null;
			$page_type = isset($properties["page_type"]) ? $properties["page_type"] : null;
			
			$code  = $prefix_tab . $var_name;
			$code .= "call_presentation_layer_web_service(array(";
			$code .= '"presentation_id" => ' . self::getVariableValueCode($properties["project"], $project_type) . ", ";
			$code .= '"url" => ' . self::getVariableValueCode($properties["page"], $page_type) . ", ";
			$code .= '"external_vars" => ' . ($external_vars ? $external_vars : "null") . ", ";
			$code .= '"includes" => ' . ($includes ? $includes : "null") . ", ";
			$code .= '"includes_once" => ' . ($includes_once ? $includes_once : "null");
			$code .= "));\n";
		}
		
		$exit_task_id = isset($data["exits"][self::DEFAULT_EXIT_ID]) ? $data["exits"][self::DEFAULT_EXIT_ID] : null;
		return $code . self::printTask($tasks, $exit_task_id, $stop_task_id, $prefix_tab, $options);
	}
}
?>
