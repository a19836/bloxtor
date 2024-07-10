<?php
namespace WorkFlowTask\programming\validator;

include_once get_lib("org.phpframework.workflow.WorkFlowTask");
include_once get_lib("org.phpframework.util.text.TextValidator");
include_once get_lib("org.phpframework.object.ObjTypeHandler");

class WorkFlowTaskImpl extends \WorkFlowTask {
	
	public function createTaskPropertiesFromCodeStmt($stmt, $WorkFlowTaskCodeParser, &$exits = null, &$inner_tasks = null) {
		$props = $WorkFlowTaskCodeParser->getObjectMethodProps($stmt);
		
		if ($props && $props["method_name"] && $props["method_static"] && ($props["method_obj"] == "TextValidator" || $props["method_obj"] == "ObjTypeHandler")) {
			$method_name = $props["method_name"];
			$available_methods_1 = get_class_methods('TextValidator');
			$available_methods_2 = get_class_methods('ObjTypeHandler');
			
			if (in_array($method_name, $available_methods_1) || in_array($method_name, $available_methods_2)) {
				$args = $props["method_args"];
				
				$props["method"] = $props["method_obj"] . "::$method_name";
				
				$value = $args[0]["value"];
				$value_type = $args[0]["type"];
				
				$props["value"] = $value;
				$props["value_type"] = self::getConfiguredParsedType($value_type);
				
				if ($props["method_obj"] == "TextValidator" && substr($method_name, 0, 5) == "check") {
					$offset = $args[1]["value"];
					$offset_type = $args[1]["type"];
					
					$props["offset"] = $offset;
					$props["offset_type"] = self::getConfiguredParsedType($offset);
				}
				
				unset($props["method_name"]);
				unset($props["method_obj"]);
				unset($props["method_args"]);
				unset($props["method_static"]);
				
				$props["label"] = "$method_name " . self::prepareTaskPropertyValueLabelFromCodeStmt($value);
				
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
			"method" => $raw_data["childs"]["properties"][0]["childs"]["method"][0]["value"],
			"value" => $raw_data["childs"]["properties"][0]["childs"]["value"][0]["value"],
			"value_type" => $raw_data["childs"]["properties"][0]["childs"]["value_type"][0]["value"],
			"offset" => $raw_data["childs"]["properties"][0]["childs"]["offset"][0]["value"],
			"offset_type" => $raw_data["childs"]["properties"][0]["childs"]["offset_type"][0]["value"],
		);
		
		$properties = self::parseResultVariableProperties($raw_data, $properties);
		
		return $properties;
	}
	
	public function printCode($tasks, $stop_task_id, $prefix_tab = "", $options = null) {
		$data = $this->data;
		
		$properties = $data["properties"];
		
		$var_name = self::getPropertiesResultVariableCode($properties);
		$method = $properties["method"];
		$value = self::getVariableValueCode($properties["value"], $properties["value_type"]);
		$offset = self::getVariableValueCode($properties["offset"], $properties["offset_type"]);
		$code = "";
		
		if ($method) {
			$code = $prefix_tab . $var_name . "$method($value";
			
			if (strpos($method, "TextValidator::check") === 0)
				$code .= ", $offset";
			
			$code .= ");\n";
		}
		
		return $code . self::printTask($tasks, $data["exits"][self::DEFAULT_EXIT_ID], $stop_task_id, $prefix_tab, $options);
	}
}
?>
