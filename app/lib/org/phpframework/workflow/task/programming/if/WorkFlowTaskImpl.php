<?php
namespace WorkFlowTask\programming\_if;

include_once get_lib("org.phpframework.workflow.WorkFlowTask");

class WorkFlowTaskImpl extends \WorkFlowTask {
	
	public function createTaskPropertiesFromCodeStmt($stmt, $WorkFlowTaskCodeParser, &$exits = null, &$inner_tasks = null) {
		//print_r($stmt);
		
		$stmt_type = strtolower($stmt->getType());
		
		if ($stmt_type == "stmt_if") {
			$if_cond = $stmt->cond;
			$if_stmts = $stmt->stmts;
			$else_stmts = $stmt->else->stmts;
			$elseifs = $stmt->elseifs;
			
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
			
			if ($if_inner_tasks && $if_inner_tasks[0]["id"]) {
				$exits["true"][] = array("task_id" => $if_inner_tasks[0]["id"]);
				
				$WorkFlowTaskCodeParser->addNextTaskToUndefinedTaskExits($if_inner_tasks[count($if_inner_tasks) - 1]);
				
				$inner_tasks[] = $if_inner_tasks;
			}
			else {
				$exits["true"][] = array("task_id" => "#next_task#");
			}
			
			$false_task_id = null;
			if ($else_inner_tasks) {
				$false_task_id = $else_inner_tasks[0]["id"];
				
				$WorkFlowTaskCodeParser->addNextTaskToUndefinedTaskExits($else_inner_tasks[count($else_inner_tasks) - 1]);
				
				$inner_tasks[] = $else_inner_tasks;
			}
			
			$this_task_info = $this->getTaskClassInfo();
			$this_task_info["obj"] = $this;
			
			if ($elseifs)
				for ($i = count($elseifs) - 1; $i >= 0; --$i) {
					$elseif = $elseifs[$i];
			
					$elseif_cond = $elseif->cond;
					$elseif_stmts = $elseif->stmts;
			
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
					if ($elseif_inner_tasks && $elseif_inner_tasks[0]["id"]) {
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
						$false_task_id = $elseif_task["id"];
						
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
		$raw_data = $task["raw_data"];
		
		$first_group = $raw_data["childs"]["properties"][0]["childs"]["group"][0];
		
		$properties = self::parseGroup($first_group);
		
		return $properties;
	}
	
	public function printCode($tasks, $stop_task_id, $prefix_tab = "", $options = null) {
		$data = $this->data;
		
		$properties = $data["properties"];
		
		//error_log("task id:".$data["id"]."\n\n", 3, "/var/www/html/livingroop/default/tmp/phpframework.log");
		$common_exit_task_id = self::getCommonTaskExitIdFromTaskPaths($tasks, $data["id"]);
		//error_log("common_exit_task_id:".$common_exit_task_id."\n\n", 3, "/var/www/html/livingroop/default/tmp/phpframework.log");
		
		$stops_id = array();
		if ($stop_task_id)
			$stops_id = is_array($stop_task_id) ? $stop_task_id : array($stop_task_id);
		if ($common_exit_task_id) 
			$stops_id[] = $common_exit_task_id;
		
		$if_code = self::printTask($tasks, $data["exits"]["true"], $stops_id, $prefix_tab . "\t", $options);
		$else_code = self::printTask($tasks, $data["exits"]["false"], $stops_id, $prefix_tab . "\t", $options);
		
		$if_code = $if_code ? $if_code : "\n\n";
		$else_code = $else_code ? $else_code : "\n\n";
		
		$code =  $prefix_tab . "if (" . self::printGroup($properties) . ") {";
		$code .= $if_code;
		$code .=  !$prefix_tab && !preg_match("/\s/", substr($code, -1)) ? " " : ""; //add space here, bc the $prefix_tab could be empty and the $code could end in <?php. If we do not add the space here, then we will get <?php} which will give a php error.
		$code .= $prefix_tab . " }\n"; 
		
		$else_code = trim($else_code);
		
		if (!empty($else_code)) {
			$code .= $prefix_tab . "else {";
			$code .= $else_code;
			$code .= "\n$prefix_tab}\n";
		}
		
		//echo "\n$common_exit_task_id";print_r($stop_task_id);
		return $code . ($common_exit_task_id ? self::printTask($tasks, $common_exit_task_id, $stop_task_id, $prefix_tab, $options) : '');
	}
}
?>
