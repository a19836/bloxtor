<?php
namespace WorkFlowTask\programming\rendertemplateregion;

include_once get_lib("org.phpframework.workflow.WorkFlowTask");

class WorkFlowTaskImpl extends \WorkFlowTask {
	
	public function createTaskPropertiesFromCodeStmt($stmt, $WorkFlowTaskCodeParser, &$exits = null, &$inner_tasks = null) {
		$props = $WorkFlowTaskCodeParser->getObjectMethodProps($stmt);
		
		if ($props) {
			$method_name = isset($props["method_name"]) ? $props["method_name"] : null;
			
			if ($method_name == "renderRegion" && empty($props["method_static"])) {
				$args = isset($props["method_args"]) ? $props["method_args"] : null;
				
				$region = isset($args[0]["value"]) ? $args[0]["value"] : null;
				$type = isset($args[0]["type"]) ? $args[0]["type"] : null;
				
				unset($props["method_name"]);
				unset($props["method_args"]);
				unset($props["method_static"]);
				
				$props["region"] = $region;
				$props["type"] = self::getConfiguredParsedType($type);
				
				$props["label"] = "Render " . self::prepareTaskPropertyValueLabelFromCodeStmt($region);
				
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
			"region" => isset($raw_data["childs"]["properties"][0]["childs"]["region"][0]["value"]) ? $raw_data["childs"]["properties"][0]["childs"]["region"][0]["value"] : null,
			"type" => isset($raw_data["childs"]["properties"][0]["childs"]["type"][0]["value"]) ? $raw_data["childs"]["properties"][0]["childs"]["type"][0]["value"] : null,
		);
		
		$properties = self::parseResultVariableProperties($raw_data, $properties);
		
		return $properties;
	}
	
	public function printCode($tasks, $stop_task_id, $prefix_tab = "", $options = null) {
		$data = isset($this->data) ? $this->data : null;
		
		$properties = isset($data["properties"]) ? $data["properties"] : null;
		
		$var_name = self::getPropertiesResultVariableCode($properties);
		
		$method_obj = isset($properties["method_obj"]) ? $properties["method_obj"] : null;
		if ($method_obj) {
			$static_pos = strpos($method_obj, "::");
			$non_static_pos = strpos($method_obj, "->");
			$method_obj = substr($method_obj, 0, 1) != '$' && substr($method_obj, 0, 2) != '@$' && (!$static_pos || ($non_static_pos && $static_pos > $non_static_pos)) ? '$' . $method_obj : $method_obj;
			$method_obj .= "->";
		}
		
		$region = isset($properties["region"]) ? $properties["region"] : null;
		$code  = $prefix_tab . $var_name . $method_obj . "renderRegion(" . self::getVariableValueCode($region, isset($properties["type"]) ? $properties["type"] : null) . ");\n";
		
		$exit_task_id = isset($data["exits"][self::DEFAULT_EXIT_ID]) ? $data["exits"][self::DEFAULT_EXIT_ID] : null;
		return $code . self::printTask($tasks, $exit_task_id, $stop_task_id, $prefix_tab, $options);
	}
}
?>
