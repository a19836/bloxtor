<?php
namespace WorkFlowTask\programming\getbeanobject;

include_once get_lib("org.phpframework.workflow.WorkFlowTask");

class WorkFlowTaskImpl extends \WorkFlowTask {
	
	public function createTaskPropertiesFromCodeStmt($stmt, $WorkFlowTaskCodeParser, &$exits = null, &$inner_tasks = null) {
		$props = $WorkFlowTaskCodeParser->getObjectMethodProps($stmt);
		
		if ($props) {
			$method_name = $props["method_name"];
			
			if ($method_name == "getObject" && empty($props["method_static"])) {
				$args = $props["method_args"];
				
				$bean_name = $args[0]["value"];
				$bean_name_type = $args[0]["type"];
				
				unset($props["method_name"]);
				unset($props["method_args"]);
				unset($props["method_static"]);
				
				$props["phpframework_obj"] = $props["method_obj"];
				$props["bean_name"] = $bean_name;
				$props["bean_name_type"] = self::getConfiguredParsedType($bean_name_type);
				
				$props["label"] = "Get " . self::prepareTaskPropertyValueLabelFromCodeStmt($bean_name);
				
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
			"phpframework_obj" => $raw_data["childs"]["properties"][0]["childs"]["phpframework_obj"][0]["value"],
			"bean_name" => $raw_data["childs"]["properties"][0]["childs"]["bean_name"][0]["value"],
			"bean_name_type" => $raw_data["childs"]["properties"][0]["childs"]["bean_name_type"][0]["value"],
		);
		
		$properties = self::parseResultVariableProperties($raw_data, $properties);
		
		return $properties;
	}
	
	public function printCode($tasks, $stop_task_id, $prefix_tab = "", $options = null) {
		$data = $this->data;
		
		$properties = $data["properties"];
		
		$code = "";
		if ($properties["bean_name"]) {
			$var_name = self::getPropertiesResultVariableCode($properties);
		
			$phpframework_obj = $properties["phpframework_obj"];
			if ($phpframework_obj) {
				$static_pos = strpos($phpframework_obj, "::");
				$non_static_pos = strpos($phpframework_obj, "->");
				$phpframework_obj = substr($phpframework_obj, 0, 1) != '$' && (!$static_pos || ($non_static_pos && $static_pos > $non_static_pos)) ? '$' . $phpframework_obj : $phpframework_obj;
				$phpframework_obj .= "->";
			}
			
			$code  = $prefix_tab . $var_name;
			$code .= $phpframework_obj . "getObject(";
			$code .= self::getVariableValueCode($properties["bean_name"], $properties["bean_name_type"]);
			$code .= ");\n";
		}
		
		return $code . self::printTask($tasks, $data["exits"][self::DEFAULT_EXIT_ID], $stop_task_id, $prefix_tab, $options);
	}
}
?>
