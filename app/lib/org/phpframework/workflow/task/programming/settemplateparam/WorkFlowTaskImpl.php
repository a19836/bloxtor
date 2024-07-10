<?php
namespace WorkFlowTask\programming\settemplateparam;

include_once get_lib("org.phpframework.workflow.WorkFlowTask");

class WorkFlowTaskImpl extends \WorkFlowTask {
	
	public function createTaskPropertiesFromCodeStmt($stmt, $WorkFlowTaskCodeParser, &$exits = null, &$inner_tasks = null) {
		$props = $WorkFlowTaskCodeParser->getObjectMethodProps($stmt);
		
		if ($props) {
			$method_name = $props["method_name"];
			
			if ($method_name == "setParam" && empty($props["method_static"])) {
				$args = $props["method_args"];
				
				$name = $args[0]["value"];
				$name_type = $args[0]["type"];
				$value = $args[1]["value"];
				$value_type = $args[1]["type"];
				
				unset($props["method_name"]);
				unset($props["method_args"]);
				unset($props["method_static"]);
				
				$props["name"] = $name;
				$props["name_type"] = self::getConfiguredParsedType($name_type);
				$props["value"] = $value;
				$props["value_type"] = self::getConfiguredParsedType($value_type);
				
				$props["label"] = "Set param: " . self::prepareTaskPropertyValueLabelFromCodeStmt($name);
				
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
		$raw_data = $task["raw_data"];
		
		$properties = array(
			"method_obj" => $raw_data["childs"]["properties"][0]["childs"]["method_obj"][0]["value"],
			"name" => $raw_data["childs"]["properties"][0]["childs"]["name"][0]["value"],
			"name_type" => $raw_data["childs"]["properties"][0]["childs"]["name_type"][0]["value"],
			"value" => $raw_data["childs"]["properties"][0]["childs"]["value"][0]["value"],
			"value_type" => $raw_data["childs"]["properties"][0]["childs"]["value_type"][0]["value"],
		);
		
		return $properties;
	}
	
	public function printCode($tasks, $stop_task_id, $prefix_tab = "", $options = null) {
		$data = $this->data;
		
		$properties = $data["properties"];
		
		$method_obj = $properties["method_obj"];
		if ($method_obj) {
			$static_pos = strpos($method_obj, "::");
			$non_static_pos = strpos($method_obj, "->");
			$method_obj = substr($method_obj, 0, 1) != '$' && (!$static_pos || ($non_static_pos && $static_pos > $non_static_pos)) ? '$' . $method_obj : $method_obj;
			$method_obj .= "->";
		}
		
		$code  = $prefix_tab . $method_obj . "setParam(" . self::getVariableValueCode($properties["name"], $properties["name_type"]) . ", " . self::getVariableValueCode($properties["value"], $properties["value_type"]) . ");\n";
		
		return $code . self::printTask($tasks, $data["exits"][self::DEFAULT_EXIT_ID], $stop_task_id, $prefix_tab, $options);
	}
}
?>
