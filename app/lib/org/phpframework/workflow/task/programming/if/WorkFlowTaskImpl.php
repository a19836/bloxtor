<?php
namespace WorkFlowTask\programming\_if;

include_once get_lib("org.phpframework.workflow.WorkFlowTask");

class WorkFlowTaskImpl extends \WorkFlowTask {
	
	public function createTaskPropertiesFromCodeStmt($stmt, $WorkFlowTaskCodeParser, &$exits = null, &$inner_tasks = null) {
		//print_r($stmt);
		
		$stmt_type = strtolower($stmt->getType());
		
		if ($stmt_type == "stmt_if") {
			$if_cond = isset($stmt->cond) ? $stmt->cond : null;
			$if_stmts = isset($stmt->stmts) ? $stmt->stmts : null;
			$else_stmts = isset($stmt->else->stmts) ? $stmt->else->stmts : null;
			$elseifs = isset($stmt->elseifs) ? $stmt->elseifs : null;
			
			$if_cond_props = $WorkFlowTaskCodeParser->getConditions($if_cond);
			if (!isset($if_cond_props)) {
				return null;
			}
			
			$if_inner_tasks = self::createTasksPropertiesFromCodeStmts($if_stmts, $WorkFlowTaskCodeParser);
			
			$props = $if_cond_props;
			$props["exits"] = array(
				"true" => array(
					"color" => "#51D87A",
					"label" => "True",
				),
				"false" => array(
					"color" => "#FF4D4D",
					"label" => "False",
				),
			);
			
			$else_inner_tasks = self::createTasksPropertiesFromCodeStmts($else_stmts, $WorkFlowTaskCodeParser);
			
			$exits = array();
			$inner_tasks = array();
			
			if ($if_inner_tasks && !empty($if_inner_tasks[0]["id"])) {
				$exits["true"][] = array("task_id" => $if_inner_tasks[0]["id"]);
				
				$WorkFlowTaskCodeParser->addNextTaskToUndefinedTaskExits($if_inner_tasks[count($if_inner_tasks) - 1]);
				
				$inner_tasks[] = $if_inner_tasks;
			}
			else {
				$exits["true"][] = array("task_id" => "#next_task#");
			}
			
			$false_task_id = null;
			if ($else_inner_tasks) {
				$false_task_id = isset($else_inner_tasks[0]["id"]) ? $else_inner_tasks[0]["id"] : null;
				
				$WorkFlowTaskCodeParser->addNextTaskToUndefinedTaskExits($else_inner_tasks[count($else_inner_tasks) - 1]);
				
				$inner_tasks[] = $else_inner_tasks;
			}
			
			$this_task_info = $this->getTaskClassInfo();
			$this_task_info["obj"] = $this;
			
			if ($elseifs)
				for ($i = count($elseifs) - 1; $i >= 0; --$i) {
					$elseif = $elseifs[$i];
			
					$elseif_cond = isset($elseif->cond) ? $elseif->cond : null;
					$elseif_stmts = isset($elseif->stmts) ? $elseif->stmts : null;
			
					$elseif_cond_props = $WorkFlowTaskCodeParser->getConditions($elseif_cond);
					if (!isset($elseif_cond_props)) {
						return null;
					}
				
					$elseif_inner_tasks = self::createTasksPropertiesFromCodeStmts($elseif_stmts, $WorkFlowTaskCodeParser);
				
					$sub_props = $elseif_cond_props;
					$sub_props["exits"] = array(
						"true" => array(
							"color" => "#51D87A",
							"label" => "True",
						),
						"false" => array(
							"color" => "#FF4D4D",
							"label" => "False",
						),
					);
					
					$sub_exits = array();
					if ($elseif_inner_tasks && !empty($elseif_inner_tasks[0]["id"])) {
						$sub_exits["true"][] = array("task_id" => $elseif_inner_tasks[0]["id"]);
					}
					else {
						$sub_exits["true"][] = array("task_id" => "#next_task#");
					}
					
					if ($false_task_id) {
						$sub_exits["false"][] = array("task_id" => $false_task_id);
					}
					else {
						$sub_exits["false"][] = array("task_id" => "#next_task#");
					}
				
					$elseif_task = $WorkFlowTaskCodeParser->createXMLTask($this_task_info, $sub_props, $sub_exits);
					
					if ($elseif_task) {
						$false_task_id = isset($elseif_task["id"]) ? $elseif_task["id"] : null;
						
						$inner_tasks[] = array($elseif_task);
					}
					else {
						return null;
					}
					
					if ($elseif_inner_tasks) {
						$WorkFlowTaskCodeParser->addNextTaskToUndefinedTaskExits($elseif_inner_tasks[count($elseif_inner_tasks) - 1]);
						
						$inner_tasks[] = $elseif_inner_tasks;
					}
				}
			
			if ($if_cond)
				$props["label"] = "If " . self::prepareTaskPropertyValueLabelFromCodeStmt( $WorkFlowTaskCodeParser->printCodeStatement($if_cond) );
			
			if ($false_task_id) {
				$exits["false"][] = array("task_id" => $false_task_id);
			}
			else {
				$exits["false"][] = array("task_id" => "#next_task#");
			}
			
			return $props;
		}
	}
	
	public function parseProperties(&$task) {
		$raw_data = isset($task["raw_data"]) ? $task["raw_data"] : null;
		
		$first_group = isset($raw_data["childs"]["properties"][0]["childs"]["group"][0]) ? $raw_data["childs"]["properties"][0]["childs"]["group"][0] : null;
		
		$properties = self::parseGroup($first_group);
		
		return $properties;
	}
	
	public function printCode($tasks, $stop_task_id, $prefix_tab = "", $options = null) {
		$data = isset($this->data) ? $this->data : null;
		//error_log("task id:".$data["id"]."\n\n", 3, "/var/www/html/livingroop/default/tmp/phpframework.log");
		
		$properties = isset($data["properties"]) ? $data["properties"] : null;
		$true_task_ids = isset($data["exits"]["true"]) ? $data["exits"]["true"] : null;
		$false_task_ids = isset($data["exits"]["false"]) ? $data["exits"]["false"] : null;
		
		//prepare stop tasks
		$stops_id = array();
		if ($stop_task_id)
			$stops_id = is_array($stop_task_id) ? $stop_task_id : array($stop_task_id);
		
		//check if short if with variable assignment, by checking if the true and false exits have 2 assignment tasks for the same variable.
		$true_task = !empty($true_task_ids[0]) && isset($tasks[ $true_task_ids[0] ]) ? $tasks[ $true_task_ids[0] ] : null;
		$false_task = !empty($false_task_ids[0]) && isset($tasks[ $false_task_ids[0] ]) ? $tasks[ $false_task_ids[0] ] : null;
		
		if ( //check if this task is the an 'if' task with an assignment task inside of the TRUE and FALSE exits.
			$true_task && $false_task && //check if exit tasks exists
			$true_task->data["tag"] == "setvar" && $true_task->data["tag"] == $false_task->data["tag"] && //check exit tasks are setvar
			(
				(empty($true_task->data["exits"][self::DEFAULT_EXIT_ID]) && empty($false_task->data["exits"][self::DEFAULT_EXIT_ID])) //check if this task is the last task
				|| 
				($true_task->data["exits"][self::DEFAULT_EXIT_ID][0] == $false_task->data["exits"][self::DEFAULT_EXIT_ID][0]) //check if both exit tasks, have the same exit task
			) && 
			self::getPropertiesResultVariableCode($true_task->data["properties"]) == self::getPropertiesResultVariableCode($false_task->data["properties"]) //check if variable name and operator are the same for both exit tasks
		) {
			$common_exit_task_id = $true_task->data["exits"][self::DEFAULT_EXIT_ID][0];
			$stops_id[] = $common_exit_task_id;
			
			$var_name = self::getPropertiesResultVariableCode($true_task->data["properties"]);
			$conditions = self::printGroup($properties);
			$conditions = strpos($conditions, "&&") !== false || strpos($conditions, "||") !== false ? "($conditions)" : $conditions;
			
			$if_code = self::printTask($tasks, $true_task_ids, $stops_id, "", $options);
			$if_code = str_replace($var_name, "", $if_code);
			$if_code = preg_replace("/;+$/", "", $if_code);
			
			$else_code = self::printTask($tasks, $false_task_ids, $stops_id, "", $options);
			$else_code = str_replace($var_name, "", $else_code);
			$else_code = preg_replace("/;+$/", "", $else_code);
			
			$code = $prefix_tab . $var_name . $conditions . " ? " . trim($if_code) . " : " . trim($else_code) . ";\n";
		}
		/*else if ( //check if this task is the an 'if' task with an assignment task inside of the TRUE exit and no FALSE exit. => DEPRECATED, bc if there is no FALSE exit or FALSE exit is equal to TRUE DEFAULT-EXIT, it means that the variable should only be set if the 'if' conditions are true. Which means this case is covered below, by the last code. - DO NOT UNCOMMENT THIS CODE
			$true_task && //check if exit tasks exists
			$true_task->data["tag"] == "setvar" && //check exit true task is setvar
			(
				(empty($true_task->data["exits"][self::DEFAULT_EXIT_ID]) && !$false_task) //check if this task is the last task
				||
				($false_task && $true_task->data["exits"][self::DEFAULT_EXIT_ID][0] == $false_task->data["id"]) //check if true exit task, points to false task, meaning that false exit doesn't exists and is pointing to common task
			)
		) {
			$common_exit_task_id = $true_task->data["exits"][self::DEFAULT_EXIT_ID][0];
			$stops_id[] = $common_exit_task_id;
			
			$var_name = self::getPropertiesResultVariableCode($true_task->data["properties"]);
			$conditions = self::printGroup($properties);
			$conditions = strpos($conditions, "&&") !== false || strpos($conditions, "||") !== false ? "($conditions)" : $conditions;
			
			$if_code = self::printTask($tasks, $true_task_ids, $stops_id, "", $options);
			$if_code = str_replace($var_name, "", $if_code);
			$if_code = preg_replace("/;+$/", "", $if_code);
			
			$code = $prefix_tab . $var_name . $conditions . " ? " . trim($if_code) . " : null;\n";
		}*/
		else { //prepare all other 'if' cases
			$if_without_parenthesis = false;
			
			//check if is a simple 'if' task because the getCommonTaskExitIdFromTaskId method is too heavy and consumes a lot of memory
			if ($true_task && $false_task &&
				!empty($true_task->data["exits"][self::DEFAULT_EXIT_ID]) && 
				(
					(!empty($false_task->data["exits"][self::DEFAULT_EXIT_ID]) && $true_task->data["exits"][self::DEFAULT_EXIT_ID][0] == $false_task->data["exits"][self::DEFAULT_EXIT_ID][0]) //check if both exit tasks, have the same exit task
					||
					($true_task->data["exits"][self::DEFAULT_EXIT_ID][0] == $false_task->data["id"]) //check if true exit task, points to false task, meaning that false exit doesn't exists and is pointing to common task
				)
			) {
				$common_exit_task_id = $true_task->data["exits"][self::DEFAULT_EXIT_ID][0];
				$if_without_parenthesis = true;
			}
			
			//get common_exit_task_id
			if (!$common_exit_task_id)
				$common_exit_task_id = self::getCommonTaskExitIdFromTaskId($tasks, isset($data["id"]) ? $data["id"] : null);
			//error_log("common_exit_task_id:".$common_exit_task_id."\n\n", 3, "/var/www/html/livingroop/default/tmp/phpframework.log");
			
			if ($common_exit_task_id) 
				$stops_id[] = $common_exit_task_id;
			
			$if_code = self::printTask($tasks, $true_task_ids, $stops_id, $prefix_tab . "\t", $options);
			$else_code = self::printTask($tasks, $false_task_ids, $stops_id, $prefix_tab . "\t", $options);
			
			$if_code = $if_code ? $if_code : "\n\n";
			$else_code = $else_code ? $else_code : "\n\n";
			
			$code =  $prefix_tab . "if (" . self::printGroup($properties) . ")";
			$code .= $if_without_parenthesis ? "" : " {";
			$code .= $if_code;
			$code .=  !$prefix_tab && !preg_match("/\s/", substr($code, -1)) ? " " : ""; //add space here, bc the $prefix_tab could be empty and the $code could end in <?php. If we do not add the space here, then we will get <?php} which will give a php error.
			$code .= $if_without_parenthesis ? "" : $prefix_tab . "}\n"; 
			
			$else_code = trim($else_code);
			
			if (!empty($else_code)) {
				$code .= $prefix_tab . "else";
				$code .= $if_without_parenthesis ? "\n" : " {\n";
				$code .= $prefix_tab . "\t" . $else_code;
				$code .= $if_without_parenthesis ? "\n" : "\n$prefix_tab}\n";
			}
		}
		
		//echo "\n$common_exit_task_id";print_r($stop_task_id);
		return $code . ($common_exit_task_id ? self::printTask($tasks, $common_exit_task_id, $stop_task_id, $prefix_tab, $options) : '');
	}
}
?>
