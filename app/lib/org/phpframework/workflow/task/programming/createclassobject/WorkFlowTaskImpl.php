<?php
namespace WorkFlowTask\programming\createclassobject;

include_once get_lib("org.phpframework.workflow.WorkFlowTask");

class WorkFlowTaskImpl extends \WorkFlowTask {
	
	public function createTaskPropertiesFromCodeStmt($stmt, $WorkFlowTaskCodeParser, &$exits = null, &$inner_tasks = null) {
		$props = $WorkFlowTaskCodeParser->getNewObjectProps($stmt);
		
		if ($props) {
			$props["label"] = "New " . self::prepareTaskPropertyValueLabelFromCodeStmt($props["class_name"]);
			
			$props["exits"] = array(
				self::DEFAULT_EXIT_ID => array(
					"color" => "#426efa",
				),
			);
			
			return $props;
		}
	}
	
	public function parseProperties(&$task) {
		$raw_data = $task["raw_data"];
		
		$properties = array(
			"class_name" => $raw_data["childs"]["properties"][0]["childs"]["class_name"][0]["value"],
			"class_args" => $raw_data["childs"]["properties"][0]["childs"]["class_args"],
		);
		
		$properties = self::parseResultVariableProperties($raw_data, $properties);
		
		return $properties;
	}
	
	public function printCode($tasks, $stop_task_id, $prefix_tab = "", $options = null) {
		$data = $this->data;
		
		$properties = $data["properties"];
		
		$code = "";
		if (trim($properties["class_name"])) {
			$var_name = self::getPropertiesResultVariableCode($properties);
			
			$args = self::getParametersString($properties["class_args"]);
			$code = $prefix_tab . $var_name . "new " . $properties["class_name"] . "($args);\n";
		}
		
		return $code . self::printTask($tasks, $data["exits"][self::DEFAULT_EXIT_ID], $stop_task_id, $prefix_tab, $options);
	}
}
?>
