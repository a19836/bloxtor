<?php
namespace WorkFlowTask\programming\_switch;

include_once get_lib("org.phpframework.workflow.WorkFlowTask");

class WorkFlowTaskImpl extends \WorkFlowTask {
	
	public function createTaskPropertiesFromCodeStmt($stmt, $WorkFlowTaskCodeParser, &$exits = null, &$inner_tasks = null) {
		//print_r($stmt);die();
		
		$stmt_type = strtolower($stmt->getType());
		
		if ($stmt_type == "stmt_switch") {
			$cond = $stmt->cond;
			$cases = $stmt->cases;
			
			if (!$cond) {
				return null;
			}
			
			$object_type =  $WorkFlowTaskCodeParser->getStmtType($cond);
			$object_var = $WorkFlowTaskCodeParser->printCodeExpr($cond);
			$object_var = $object_type == "variable" && substr($object_var, 0, 1) == "$" ? substr($object_var, 1, strlen($object_var)) : $object_var;
			
			$props = array(
				"object_var" => $object_var,
				"object_type" => self::getConfiguredParsedType($object_type),
				"cases" => array(
					"case" => array(),
				),
				"label" => "Switch " . self::prepareTaskPropertyValueLabelFromCodeStmt($object_var),
			);
			
			$cases_without_break = array();
			$cases_inner_tasks = array();
			$default_inner_tasks = null;
			
			$t = $cases ? count($cases) : 0;
			for ($i = 0; $i < $t; $i++) {
				$case = $cases[$i];
				$case_cond = $case->cond;
				$case_stmts = $case->stmts;
				
				if ($case_cond) { //case xxx:
					$case_type = strtolower($case_cond->getType());
					$case_cond = $WorkFlowTaskCodeParser->printCodeExpr($case_cond);
					$case_cond = $WorkFlowTaskCodeParser->getStmtValueAccordingWithType($case_cond, $case_type);
					
					$case_props = array(
						"value" => $case_cond,
						"type" => $WorkFlowTaskCodeParser->getStmtType($case->cond),
						"exit" => "e" . hash('crc32b', $case_cond) . "_exit",//it must always start with a letter, so we added the char 'e' at the beginning.
					);
					
					$idx = count($props["cases"]["case"]);
					
					$cases_without_break[$idx] = $this->existsCaseBreakStmtInStmts($case_stmts) == false;
					
					if ($case_stmts) {
						$this->removeCaseDefaultBreakStmtFromStmts($case_stmts);
						$cases_inner_tasks[$idx] = self::createTasksPropertiesFromCodeStmts($case_stmts, $WorkFlowTaskCodeParser);
					}
					
					$props["cases"]["case"][$idx] = $case_props;
					$props["exits"][ $case_props["exit"] ] = array();
				}
				else if ($case_stmts) { //default:
					$this->removeCaseDefaultBreakStmtFromStmts($case_stmts);
					$default_inner_tasks = self::createTasksPropertiesFromCodeStmts($case_stmts, $WorkFlowTaskCodeParser);
				}
			}
			
			$props["default"] = array(
				"exit" => "default_exit",
			);
			$props["exits"][ $props["default"]["exit"] ] = array(
				"color" => "#000",
			);
			
			$exits = array();
			$inner_tasks = array();
			
			if ($default_inner_tasks && $default_inner_tasks[0]["id"]) {
				$exits[ $props["default"]["exit"] ][] = array("task_id" => $default_inner_tasks[0]["id"]);
				
				$WorkFlowTaskCodeParser->addNextTaskToUndefinedTaskExits($default_inner_tasks[count($default_inner_tasks) - 1]);
				
				$inner_tasks[] = $default_inner_tasks;
			}
			else
				$exits[ $props["default"]["exit"] ][] = array("task_id" => "#next_task#");
			
			foreach ($props["cases"]["case"] as $idx => $case) {
				$case_inner_tasks = $cases_inner_tasks[$idx];
				
				//if break does NOT exist
				if ($cases_without_break[$idx]) {
					//sets inner tasks until exists one case with break/return/die/exit
					$case_inner_tasks = $case_inner_tasks ? $case_inner_tasks : array();
					$idx_aux = $idx + 1;
					$has_break = false;
					while ($props["cases"]["case"][$idx_aux]) {
						if ($cases_inner_tasks[$idx_aux] && $cases_inner_tasks[$idx_aux][0]["id"]) {
							if ($case_inner_tasks)
								$WorkFlowTaskCodeParser->replaceNextTaskInNotBreakTasksExits($case_inner_tasks, $cases_inner_tasks[$idx_aux][0]);
							
							$case_inner_tasks =  array_merge($case_inner_tasks, $cases_inner_tasks[$idx_aux]);
						}
						
						$has_break = !$cases_without_break[$idx_aux]; //if has break, exit loop
						++$idx_aux;
						
						if ($has_break) //if has break, exit loop
							break;
						
					}
					
					//if last case does not have a break either, relate it with the default code, if exists any...
					if (!$has_break && $default_inner_tasks && $default_inner_tasks[0]["id"]) {
						if ($case_inner_tasks)
							$WorkFlowTaskCodeParser->replaceNextTaskInNotBreakTasksExits($case_inner_tasks, $default_inner_tasks[0]);
						$case_inner_tasks =  array_merge($case_inner_tasks, $default_inner_tasks);
					}
				}
				
				//init case exits
				if ($case_inner_tasks && $case_inner_tasks[0]["id"]) { //if break exists and contains some code
					$WorkFlowTaskCodeParser->addNextTaskToUndefinedTaskExits($case_inner_tasks[count($case_inner_tasks) - 1]);
					
					$exits[ $case["exit"] ][] = array("task_id" => $case_inner_tasks[0]["id"]);
					$inner_tasks[] = $case_inner_tasks;
				}
				else
					$exits[ $case["exit"] ][] = array("task_id" => "#next_task#");
			}
			//print_r($inner_tasks);
			
			return $props;
		}
	}
	
	private function existsCaseBreakStmtInStmts($case_stmts) {
		if ($case_stmts)
			foreach ($case_stmts as $case_stmt) {
				$type = strtolower($case_stmt->getType());
				if ($type == "stmt_break" || $type == "stmt_return" || $type == "stmt_die" || $type == "stmt_exit") 
					return true;
			}
		return false;
	}
	
	//removes default breaks, this is, the breaks that have value == 1
	private function removeCaseDefaultBreakStmtFromStmts(&$case_stmts) {
		if ($case_stmts) {
			//Removing the break stmt;
			$new_case_stmts = array();
			foreach ($case_stmts as $case_stmt) {
				$is_break = strtolower($case_stmt->getType()) == "stmt_break";
				$value = $is_break && $case_stmt->num && is_numeric($case_stmt->num->value) && $case_stmt->num->value > 1 ? $case_stmt->num->value : 1;
				
				if (!$is_break || $value > 1) //adds non break tasks or breaks with skip level more than 1
					$new_case_stmts[] = $case_stmt;
			}
			$case_stmts = $new_case_stmts;
		}
	}
	
	public function parseProperties(&$task) {
		$raw_data = $task["raw_data"];
		
		$cases = $raw_data["childs"]["properties"][0]["childs"]["cases"][0]["childs"]["case"];
		
		$new_cases = array();
		if (is_array($cases)) {
			foreach ($cases as $case) {
				$value = isset($case["@"]["value"]) ? $case["@"]["value"] : (isset($case["childs"]["value"][0]["value"]) ? $case["childs"]["value"][0]["value"] : null);
				$exit = isset($case["@"]["exit"]) ? $case["@"]["exit"] : (isset($case["childs"]["exit"][0]["value"]) ? $case["childs"]["exit"][0]["value"] : null);
				
				$exit_ids = isset($task["exits"][strtolower($exit)]) ? $task["exits"][strtolower($exit)] : null;
				
				$new_cases[] = array(
					"value" => $value,
					"exit" => $exit_ids,
				);
			}
		}
		
		$default = $raw_data["childs"]["properties"][0]["childs"]["default"][0];
		$default = isset($default["@"]["exit"]) ? $default["@"]["exit"] : (isset($default["childs"]["exit"][0]["value"]) ? $default["childs"]["exit"][0]["value"] : null);
		
		$default_ids = isset($task["exits"][strtolower($default)]) ? $task["exits"][strtolower($default)] : null;
		
		$properties = array(
			"object_var" => $raw_data["childs"]["properties"][0]["childs"]["object_var"][0]["value"],
			"object_type" => $raw_data["childs"]["properties"][0]["childs"]["object_type"][0]["value"],
			"cases" => $new_cases,
			"default" => array(
				"exit" => $default_ids,
			),
		);
		
		return $properties;
	}
	
	public function printCode($tasks, $stop_task_id, $prefix_tab = "", $options = null) {
		$data = $this->data;
		
		$properties = $data["properties"];
		
		$common_exit_task_id = self::getCommonTaskExitIdFromTaskPaths($tasks, $data["id"]);
		
		$stops_id = array();
		if ($stop_task_id)
			$stops_id = is_array($stop_task_id) ? $stop_task_id : array($stop_task_id);
		if ($common_exit_task_id) 
			$stops_id[] = $common_exit_task_id;
		//echo "common_exit_task_id:$common_exit_task_id, ".implode(",", $stop_task_id).".\n";
		
		$object_var = self::getVariableValueCode($properties["object_var"], $properties["object_type"]);
		
		$code = $prefix_tab . "switch(" . $object_var . ") {\n";
		$t = $properties["cases"] ? count($properties["cases"]) : 0;
		for($i = 0; $i < $t; $i++) {
			$case = $properties["cases"][$i];
			
			//Prepare case stops id and try to avoid some repeated code
			$case_stops_id = $stops_id;
			$next_case_exit_id = $i + 1 < $t ? $properties["cases"][$i + 1]["exit"][0] : $properties["default"]["exit"][0];
			$is_next_case_related = self::checkIfCaseIsRelatedWithNextCase($tasks, $case["exit"][0], $next_case_exit_id);//This detects if a case doesn't have break at the end and is connected with the code from the next case.
			if ($is_next_case_related) 
				$case_stops_id[] = $next_case_exit_id;
			
			//print case
			$case_code = self::printTask($tasks, $case["exit"], $case_stops_id, $prefix_tab . "\t\t", $options);
			$case_code = $case_code ? $case_code : "\n"; //if there is no code it means that the case is connected is the next case.
			
			$code .= $prefix_tab . "\tcase " . (is_numeric($case["value"]) ? $case["value"] : '"' . $case["value"] . '"') . ": ";
			$code .= $case_code;
			$code .= $is_next_case_related ? '' : $prefix_tab . "\t\tbreak;\n"; //even if already exists a BREAK task, we still write the break. bc usually we won't have any break. But if exists already a break, there is no problem either, bc only the first break will be executed. The only case where we don't want to hard-code the BREAK, is if this case is related with the next case.
		}
		
		$default_code = self::printTask($tasks, $properties["default"]["exit"], $stops_id, $prefix_tab . "\t\t", $options);
		if ($default_code)
			$code .= $prefix_tab . "\tdefault: " . $default_code; //only show default if there is some code to write.
		
		$code .=  !$prefix_tab && !preg_match("/\s/", substr($code, -1)) ? " " : ""; //add space here, bc the $prefix_tab could be empty and the $code could end in <?php. If we do not add the space here, then we will get <?php} which will give a php error.
		$code .= $prefix_tab . "}\n";
		
		return $code . ($common_exit_task_id ? self::printTask($tasks, $common_exit_task_id, $stop_task_id, $prefix_tab, $options) : '');
	}
	
	//This detects if a case doesn't have break at the end and is connected with the code from the next case.
	private static function checkIfCaseIsRelatedWithNextCase($tasks, $case_exit_id, $next_case_exit_id) {
		if ($next_case_exit_id) {
			$case_paths = self::getTaskPaths($tasks, $case_exit_id, true);
			
			$t = $case_paths ? count($case_paths) : 0;
			for ($i = 0; $i < $t; $i++) 
				if (!in_array($next_case_exit_id, $case_paths[$i])) 
					return false;
			
			return $t > 0; //returns true but only if exist paths, which means it entered in the loop above.
		}
	}
}
?>
