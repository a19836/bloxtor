<?php
namespace WorkFlowTask\programming\trycatchexception;

include_once get_lib("org.phpframework.workflow.WorkFlowTask");

class WorkFlowTaskImpl extends \WorkFlowTask {
	
	public function createTaskPropertiesFromCodeStmt($stmt, $WorkFlowTaskCodeParser, &$exits = null, &$inner_tasks = null) {
		$stmt_type = strtolower($stmt->getType());
		
		if ($stmt_type == "stmt_trycatch") {
			//print_r($stmt);
			
			$props = array(
				"label" => "Catch Exception:",
				"exits" => array(
					"try" => array(
						"color" => "#51D87A",
						"label" => "No exception",
					),
					"catch" => array(
						"color" => "#FF4D4D",
						"label" => "Catched exception",
					),
				),
			);
			
			$inner_tasks = array();
			$try_stmts = $stmt->stmts;
			
			$try_inner_tasks = self::createTasksPropertiesFromCodeStmts($try_stmts, $WorkFlowTaskCodeParser);
			
			$try_task_id = null;
			if ($try_inner_tasks) {
				$try_task_id = $try_inner_tasks[0]["id"];
				
				$WorkFlowTaskCodeParser->addNextTaskToUndefinedTaskExits($try_inner_tasks[count($try_inner_tasks) - 1]);
				
				$inner_tasks[] = $try_inner_tasks;
			}
			
			$catches = $stmt->catches;
			if (!$catches) {
				return null;
			}
			
			$this_task_info = $this->getTaskClassInfo();
			$this_task_info["obj"] = $this;
			
			$t = $catches ? count($catches) : 0;
			for ($i = 0; $i < $t; $i++) {
				$catch = $catches[$i];
				//print_r($catch);
				
				$class_name = $WorkFlowTaskCodeParser->printCodeNodeName($catch->types[0]);
				$var_name = $catch->var;
				$catch_stmts = $catch->stmts;
				
				$catch_props = $props;
				$catch_props["class_name"] = $class_name;
				$catch_props["var_name"] = $var_name;
				
				$props["label"] .= " $class_name";
				
				$sub_exits = array();
				
				if ($try_task_id) {
					$sub_exits["try"][] = array("task_id" => $try_task_id);
				}
				else {
					$sub_exits["try"][] = array("task_id" => "#next_task#");
				}
				
				$catch_inner_tasks = self::createTasksPropertiesFromCodeStmts($catch_stmts, $WorkFlowTaskCodeParser);
				if ($catch_inner_tasks && $catch_inner_tasks[0]["id"]) {
					$sub_exits["catch"][] = array("task_id" => $catch_inner_tasks[0]["id"]);
				}
				else {
					$sub_exits["catch"][] = array("task_id" => "#next_task#");
				}
				
				if ($i < $t - 1) {
					$catch_task = $WorkFlowTaskCodeParser->createXMLTask($this_task_info, $catch_props, $sub_exits);
					
					if ($catch_task) {
						$try_task_id = $catch_task["id"];
					
						$inner_tasks[] = array($catch_task);
					}
					else {
						return null;
					}
				}
				
				if ($catch_inner_tasks) {
					$WorkFlowTaskCodeParser->addNextTaskToUndefinedTaskExits($catch_inner_tasks[count($catch_inner_tasks) - 1]);
					
					$inner_tasks[] = $catch_inner_tasks;
				}
			}
			
			$props = $catch_props;
			$exits = $sub_exits;
			
			return $props;
		}
	}
	
	public function parseProperties(&$task) {
		$raw_data = $task["raw_data"];
		
		$properties = array(
			"class_name" => $raw_data["childs"]["properties"][0]["childs"]["class_name"][0]["value"],
			"var_name" => $raw_data["childs"]["properties"][0]["childs"]["var_name"][0]["value"],
		);
		
		return $properties;
	}
	
	public function printCode($tasks, $stop_task_id, $prefix_tab = "", $options = null) {
		$data = $this->data;
		//print_r($data);
		
		$properties = $data["properties"];
		
		$common_exit_task_id = self::getCommonTaskExitIdFromTaskPaths($tasks, $data["id"]);
		
		$stops_id = array();
		if ($stop_task_id)
			$stops_id = is_array($stop_task_id) ? $stop_task_id : array($stop_task_id);
		if ($common_exit_task_id) 
			$stops_id[] = $common_exit_task_id;
		
		$class_name = $properties["class_name"] ? $properties["class_name"] : "Exception";
		$var_name = self::getVariableValueCode($properties["var_name"], "variable");
		
		$try_code = self::printTask($tasks, $data["exits"]["try"][0], $stops_id, $prefix_tab . "\t", $options);
		$is_try_next_code = !$try_code && in_array($data["exits"]["try"][0], $stops_id);
		$try_code = $try_code || $is_try_next_code ? $try_code : "\n$prefix_tab\t\n";
		
		$catch_code = self::printTask($tasks, $data["exits"]["catch"][0], $stops_id, $prefix_tab . "\t", $options);
		$catch_code = $catch_code ? $catch_code : "\n$prefix_tab\t\n";
		
		$next_code = $common_exit_task_id ? self::printTask($tasks, $common_exit_task_id, $stop_task_id, $prefix_tab . ($is_try_next_code ? "\t" : ""), $options) : '';
		
		if ($is_try_next_code) {
			$try_code = $next_code;
			$next_code = "";
		}
		
		$code .= $prefix_tab . "try {";
		$code .= $try_code;
		$code .= $prefix_tab . "}\n";
		$code .= $prefix_tab . "catch ($class_name $var_name) {";
		$code .= $catch_code;
		$code .= $prefix_tab . "}\n";
		
		return $code . $next_code;
	}
}
?>
