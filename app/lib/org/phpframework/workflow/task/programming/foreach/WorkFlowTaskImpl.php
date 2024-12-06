<?php
namespace WorkFlowTask\programming\_foreach;

include_once get_lib("org.phpframework.workflow.WorkFlowTask");

class WorkFlowTaskImpl extends \WorkFlowTask {
	
	protected $is_loop_task = true;
	
	public function createTaskPropertiesFromCodeStmt($stmt, $WorkFlowTaskCodeParser, &$exits = null, &$inner_tasks = null) {
		$stmt_type = strtolower($stmt->getType());
		
		if ($stmt_type == "stmt_foreach") {
			$expr = isset($stmt->expr) ? $stmt->expr : null;
			$keyVar = isset($stmt->keyVar) ? $stmt->keyVar : null;
			$valueVar = isset($stmt->valueVar) ? $stmt->valueVar : null;
			$sub_stmts = isset($stmt->stmts) ? $stmt->stmts : null;
			
			$obj = $WorkFlowTaskCodeParser->printCodeExpr($expr, false);
			$key = $keyVar ? $WorkFlowTaskCodeParser->printCodeExpr($keyVar, false) : "";
			$value = $WorkFlowTaskCodeParser->printCodeExpr($valueVar, false);
			
			$sub_inner_tasks = self::createTasksPropertiesFromCodeStmts($sub_stmts, $WorkFlowTaskCodeParser);
			
			$props = array(
				"obj" => $obj,
				"key" => $key,
				"value" => $value,
				"label" => "loop " . self::prepareTaskPropertyValueLabelFromCodeStmt($obj),
				"exits" => array(
					"start_exit" => array(
						"color" => "#31498f",
						"label" => "Start loop",
					),
					self::DEFAULT_EXIT_ID => array(
						"color" => "#2C2D34",
						"label" => "End loop",
					),
				),
			);
			
			//PREPARING EXITS AND INNER TASKS
			$exits = array();
			$exits[self::DEFAULT_EXIT_ID][] = array("task_id" => "#next_task#");
			
			if ($sub_inner_tasks && isset($sub_inner_tasks[0]["id"])) {
				$exits["start_exit"][] = array("task_id" => $sub_inner_tasks[0]["id"]);
				
				//The tasks inside of a loop should NOT be connected to any other tasks outside of the loop.
				$sub_inner_tasks = $WorkFlowTaskCodeParser->cleanInvalidExitsFromTasks($sub_inner_tasks);
				$sub_inner_tasks = $WorkFlowTaskCodeParser->stopLoopInnerTasksToBeConnectedToOtherOutsideTasks($sub_inner_tasks);
				
				$inner_tasks = array($sub_inner_tasks);
			}
			
			return $props;
		}
	}
	
	public function parseProperties(&$task) {
		$raw_data = isset($task["raw_data"]) ? $task["raw_data"] : null;
		
		$properties = array(
			"obj" => isset($raw_data["childs"]["properties"][0]["childs"]["obj"][0]["value"]) ? $raw_data["childs"]["properties"][0]["childs"]["obj"][0]["value"] : null,
			"key" => isset($raw_data["childs"]["properties"][0]["childs"]["key"][0]["value"]) ? $raw_data["childs"]["properties"][0]["childs"]["key"][0]["value"] : null,
			"value" => isset($raw_data["childs"]["properties"][0]["childs"]["value"][0]["value"]) ? $raw_data["childs"]["properties"][0]["childs"]["value"][0]["value"] : null,
		);
		
		return $properties;
	}
	
	public function printCode($tasks, $stop_task_id, $prefix_tab = "", $options = null) {
		$data = isset($this->data) ? $this->data : null;
		
		$properties = isset($data["properties"]) ? $data["properties"] : null;
		
		$stops_id = array();
		if ($stop_task_id)
			$stops_id = is_array($stop_task_id) ? $stop_task_id : array($stop_task_id);
		if (!empty($data["exits"][self::DEFAULT_EXIT_ID][0]))
			$stops_id = array_merge($stops_id, $data["exits"][self::DEFAULT_EXIT_ID]);
		
		//PREPARING INNER TASKS CODE
		$exit_task_id = isset($data["exits"]["start_exit"]) ? $data["exits"]["start_exit"] : null;
		$loop_code = self::printTask($tasks, $exit_task_id, $stops_id, $prefix_tab . "\t", $options);
		
		//PREPARING MAIN CODE
		$obj = !empty($properties["obj"]) ? self::getVariableValueCode($properties["obj"], "variable") : null;
		$key = !empty($properties["key"]) ? self::getVariableValueCode($properties["key"], "variable") : null;
		$value = !empty($properties["value"]) ? self::getVariableValueCode($properties["value"], "variable") : null;
		
		if ($obj && $value) {
			$key = $key ? "$key => " : "";
			
			$code =  $prefix_tab . "foreach ($obj as $key$value) {";
			$code .= $loop_code ? $loop_code : "\n\n";
			$code .=  !$prefix_tab && !preg_match("/\s/", substr($code, -1)) ? " " : ""; //add space here, bc the $prefix_tab could be empty and the $code could end in <?php. If we do not add the space here, then we will get <?php} which will give a php error.
			$code .= $prefix_tab . "}\n";
		}
		else {
			$code = "";
		}
		
		$exit_task_id = isset($data["exits"][self::DEFAULT_EXIT_ID][0]) ? $data["exits"][self::DEFAULT_EXIT_ID][0] : null;
		return $code . self::printTask($tasks, $exit_task_id, $stop_task_id, $prefix_tab, $options);
	}
}
?>
