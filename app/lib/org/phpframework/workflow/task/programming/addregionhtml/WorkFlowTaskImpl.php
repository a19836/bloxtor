<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

namespace WorkFlowTask\programming\addregionhtml;

include_once get_lib("org.phpframework.workflow.WorkFlowTask");

class WorkFlowTaskImpl extends \WorkFlowTask {
	
	public function createTaskPropertiesFromCodeStmt($stmt, $WorkFlowTaskCodeParser, &$exits = null, &$inner_tasks = null) {
		$props = $WorkFlowTaskCodeParser->getObjectMethodProps($stmt);
		
		if ($props) {
			$method_name = isset($props["method_name"]) ? $props["method_name"] : null;
			
			if ($method_name == "addRegionHtml" && empty($props["method_static"])) {
				$args = isset($props["method_args"]) ? $props["method_args"] : null;
				
				if (count($args) != 2)
					return null;
				
				$region_id = isset($args[0]["value"]) ? $args[0]["value"] : null;
				$region_id_type = isset($args[0]["type"]) ? $args[0]["type"] : null;
				$html = isset($args[1]["value"]) ? $args[1]["value"] : null;
				$html_type = isset($args[1]["type"]) ? $args[1]["type"] : null;
				
				unset($props["method_name"]);
				unset($props["method_args"]);
				unset($props["method_static"]);
				
				$props["region_id"] = $region_id;
				$props["region_id_type"] = self::getConfiguredParsedType($region_id_type);
				$props["html"] = $html;
				$props["html_type"] = self::getConfiguredParsedType($html_type);
				
				$props["label"] = "Add html in region: " . self::prepareTaskPropertyValueLabelFromCodeStmt($region_id);
				
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
		
		$properties = array(
			"method_obj" => isset($raw_data["childs"]["properties"][0]["childs"]["method_obj"][0]["value"]) ? $raw_data["childs"]["properties"][0]["childs"]["method_obj"][0]["value"] : null,
			"region_id" => isset($raw_data["childs"]["properties"][0]["childs"]["region_id"][0]["value"]) ? $raw_data["childs"]["properties"][0]["childs"]["region_id"][0]["value"] : null,
			"region_id_type" => isset($raw_data["childs"]["properties"][0]["childs"]["region_id_type"][0]["value"]) ? $raw_data["childs"]["properties"][0]["childs"]["region_id_type"][0]["value"] : null,
			"html" => isset($raw_data["childs"]["properties"][0]["childs"]["html"][0]["value"]) ? $raw_data["childs"]["properties"][0]["childs"]["html"][0]["value"] : null,
			"html_type" => isset($raw_data["childs"]["properties"][0]["childs"]["html_type"][0]["value"]) ? $raw_data["childs"]["properties"][0]["childs"]["html_type"][0]["value"] : null,
		);
		
		return $properties;
	}
	
	public function printCode($tasks, $stop_task_id, $prefix_tab = "", $options = null) {
		$data = isset($this->data) ? $this->data : null;
		
		$properties = isset($data["properties"]) ? $data["properties"] : null;
		
		$method_obj = isset($properties["method_obj"]) ? $properties["method_obj"] : null;
		if ($method_obj) {
			$static_pos = strpos($method_obj, "::");
			$non_static_pos = strpos($method_obj, "->");
			$method_obj = substr($method_obj, 0, 1) != '$' && substr($method_obj, 0, 2) != '@$' && (!$static_pos || ($non_static_pos && $static_pos > $non_static_pos)) ? '$' . $method_obj : $method_obj;
			$method_obj .= "->";
		}
		
		$region_id_type = isset($properties["region_id_type"]) ? $properties["region_id_type"] : null;
		$region_id = isset($properties["region_id"]) ? $properties["region_id"] : null;
		$html_type = isset($properties["html_type"]) ? $properties["html_type"] : null;
		$html = isset($properties["html"]) ? $properties["html"] : null;
		
		$code  = $prefix_tab . $method_obj . "addRegionHtml(" . self::getVariableValueCode($region_id, $region_id_type) . ", " . self::getVariableValueCode($html, $html_type) . ");\n";
		
		$exit_task_id = isset($data["exits"][self::DEFAULT_EXIT_ID]) ? $data["exits"][self::DEFAULT_EXIT_ID] : null;
		return $code . self::printTask($tasks, $exit_task_id, $stop_task_id, $prefix_tab, $options);
	}
}
?>
