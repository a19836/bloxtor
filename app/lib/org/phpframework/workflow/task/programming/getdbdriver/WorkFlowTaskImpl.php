<?php
namespace WorkFlowTask\programming\getdbdriver;

include_once get_lib("org.phpframework.workflow.WorkFlowTask");

class WorkFlowTaskImpl extends \WorkFlowTask {
	
	public function createTaskPropertiesFromCodeStmt($stmt, $WorkFlowTaskCodeParser, &$exits = null, &$inner_tasks = null) {
		$props = $WorkFlowTaskCodeParser->getObjectMethodProps($stmt);
		
		if ($props) {
			$method_name = $props["method_name"];
			
			if ($method_name == "getBroker" && empty($props["method_static"])) { //Note that this task will get the others getBroker code, even if are not db drivers. In the future we should add some check here to only detect db drivers.
				$args = $props["method_args"];
				
				$db_driver = $args[0]["value"];
				$db_driver_type = $args[0]["type"];
				
				unset($props["method_name"]);
				unset($props["method_args"]);
				unset($props["method_static"]);
				
				$props["db_driver"] = $db_driver;
				$props["db_driver_type"] = self::getConfiguredParsedType($db_driver_type);
				
				$props["label"] = "Get " . self::prepareTaskPropertyValueLabelFromCodeStmt($db_driver);
				
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
			"db_driver" => $raw_data["childs"]["properties"][0]["childs"]["db_driver"][0]["value"],
			"db_driver_type" => $raw_data["childs"]["properties"][0]["childs"]["db_driver_type"][0]["value"],
		);
		
		$properties = self::parseResultVariableProperties($raw_data, $properties);
		
		return $properties;
	}
	
	public function printCode($tasks, $stop_task_id, $prefix_tab = "", $options = null) {
		$data = $this->data;
		
		$properties = $data["properties"];
		
		$code = "";
		if ($properties["db_driver"]) {
			$var_name = self::getPropertiesResultVariableCode($properties);
		
			$method_obj = $properties["method_obj"];
			if ($method_obj) {
				$static_pos = strpos($method_obj, "::");
				$non_static_pos = strpos($method_obj, "->");
				$method_obj = substr($method_obj, 0, 1) != '$' && (!$static_pos || ($non_static_pos && $static_pos > $non_static_pos)) ? '$' . $method_obj : $method_obj;
				$method_obj .= "->";
			}
			
			$code  = $prefix_tab . $var_name;
			$code .= $method_obj . "getBroker(";
			$code .= self::getVariableValueCode($properties["db_driver"], $properties["db_driver_type"]);
			$code .= ");\n";
		}
		
		return $code . self::printTask($tasks, $data["exits"][self::DEFAULT_EXIT_ID], $stop_task_id, $prefix_tab, $options);
	}
}
?>
