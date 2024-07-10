<?php
namespace WorkFlowTask\programming\ns;

include_once get_lib("org.phpframework.workflow.WorkFlowTask");

class WorkFlowTaskImpl extends \WorkFlowTask {
	
	public function createTaskPropertiesFromCodeStmt($stmt, $WorkFlowTaskCodeParser, &$exits = null, &$inner_tasks = null) {
		$stmt_type = strtolower($stmt->getType());
		
		if ($stmt_type == "stmt_namespace") {
			$value = $WorkFlowTaskCodeParser->printCodeNodeName($stmt);
			
			$sub_stmts = $stmt->stmts;
			$sub_inner_tasks = self::createTasksPropertiesFromCodeStmts($sub_stmts, $WorkFlowTaskCodeParser);
			
			$props = array(
				"value" => $value,
				"label" => "Namespace: " . self::prepareTaskPropertyValueLabelFromCodeStmt($value),
				"exits" => array(
					self::DEFAULT_EXIT_ID => array(
						"color" => "#426efa",
					),
				)
			);
			
			$exits = array();
			
			//PREPARING EXITS AND INNER TASKS
			if ($sub_inner_tasks) {
				$exits[self::DEFAULT_EXIT_ID][] = array("task_id" => $sub_inner_tasks[0]["id"]);
				
				$WorkFlowTaskCodeParser->addNextTaskToUndefinedTaskExits($sub_inner_tasks[count($sub_inner_tasks) - 1]);
				
				$inner_tasks = array($sub_inner_tasks);
			}
			else
				$exits[self::DEFAULT_EXIT_ID][] = array("task_id" => "#next_task#");
			
			return $props;
		}
	}
	
	public function parseProperties(&$task) {
		$raw_data = $task["raw_data"];
		
		$properties = array(
			"value" => $raw_data["childs"]["properties"][0]["childs"]["value"][0]["value"],
		);
		
		return $properties;
	}
	
	public function printCode($tasks, $stop_task_id, $prefix_tab = "", $options = null) {
		$data = $this->data;
		
		$properties = $data["properties"];
		
		$code = "namespace " . $properties["value"] . ";";
		
		return $code . self::printTask($tasks, $data["exits"][self::DEFAULT_EXIT_ID], $stop_task_id, $prefix_tab, $options);
	}
}
?>
