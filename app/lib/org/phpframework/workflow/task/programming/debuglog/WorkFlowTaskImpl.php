<?php
namespace WorkFlowTask\programming\debuglog;

include_once get_lib("org.phpframework.workflow.WorkFlowTask");

class WorkFlowTaskImpl extends \WorkFlowTask {
	
	public function createTaskPropertiesFromCodeStmt($stmt, $WorkFlowTaskCodeParser, &$exits = null, &$inner_tasks = null) {
		$props = $WorkFlowTaskCodeParser->getFunctionProps($stmt);
		
		if ($props) {
			$func_name = $props["func_name"];
			$args = $props["func_args"];
			
			if ($func_name && strtolower($func_name) == "debug_log") {
				if (count($args) <= 2) {
					$message = $args[0]["value"];
					$message_type = $args[0]["type"];
					$log_type = $args[1]["value"];
					$log_type_type = $args[1]["type"];
					
					unset($props["func_name"]);
					unset($props["func_args"]);
					unset($props["label"]);
					
					$props["message"] = $message;
					$props["message_type"] = self::getConfiguredParsedType($message_type);
					$props["log_type"] = $log_type;
					$props["log_type_type"] = self::getConfiguredParsedType($log_type_type);
					
					$props["label"] = "Define date " . self::prepareTaskPropertyValueLabelFromCodeStmt($props["format"]);
					
					$props["exits"] = array(
						self::DEFAULT_EXIT_ID => array(
							"color" => "#426efa",
						),
					);
					
					return $props;
				}
			}
		}
	}
	
	public function parseProperties(&$task) {
		$raw_data = $task["raw_data"];
		
		$properties = array(
			"message" => $raw_data["childs"]["properties"][0]["childs"]["message"][0]["value"],
			"message_type" => $raw_data["childs"]["properties"][0]["childs"]["message_type"][0]["value"],
			"log_type" => $raw_data["childs"]["properties"][0]["childs"]["log_type"][0]["value"],
			"log_type_type" => $raw_data["childs"]["properties"][0]["childs"]["log_type_type"][0]["value"],
		);
		
		return $properties;
	}
	
	public function printCode($tasks, $stop_task_id, $prefix_tab = "", $options = null) {
		$data = $this->data;
		
		$properties = $data["properties"];
		
		$message = self::getVariableValueCode($properties["message"], $properties["message_type"]);
		$log_type = self::getVariableValueCode($properties["log_type"], $properties["log_type_type"]);
		
		$code = $prefix_tab . "debug_log($message";
		$code .= $log_type ? ", $log_type" : "";
		$code .= ");\n";
		
		return $code . self::printTask($tasks, $data["exits"][self::DEFAULT_EXIT_ID], $stop_task_id, $prefix_tab, $options);
	}
}
?>
