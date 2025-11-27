<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 *
 * Original Bloxtor Repo: https://github.com/a19836/bloxtor
 *
 * YOU ARE NOT AUTHORIZED TO MODIFY OR REMOVE ANY PART OF THIS NOTICE!
 */

namespace WorkFlowTask\programming\loop;

include_once get_lib("org.phpframework.workflow.WorkFlowTask");

class WorkFlowTaskImpl extends \WorkFlowTask {
	
	protected $is_loop_task = true;
	
	public function createTaskPropertiesFromCodeStmt($stmt, $WorkFlowTaskCodeParser, &$exits = null, &$inner_tasks = null) {
		//print_r($stmt);
		
		$stmt_type = strtolower($stmt->getType());
		
		if ($stmt_type == "stmt_for" || $stmt_type == "stmt_do" || $stmt_type == "stmt_while") {
			$init = isset($stmt->init) ? $stmt->init : null;
			$cond = isset($stmt->cond) ? $stmt->cond : null;
			$loop = isset($stmt->loop) ? $stmt->loop : null;
			$sub_stmts = isset($stmt->stmts) ? $stmt->stmts : null;
			
			$init = !$init || is_array($init) ? $init : array($init);
			$cond = !$cond || is_array($cond) ? $cond : array($cond);
			$loop = !$loop || is_array($loop) ? $loop : array($loop);
			
			//parse init: assign simple: type could be a string or default (code)
			//parse loop: check if increment or increment, otherwise is code
			
			//START $init
			$init_props = array();
			if ($init) {
				$t = count($init);
				for ($i = 0; $i < $t; $i++) {
					$item = $init[$i];
					$var_name = null;
					
					if ($WorkFlowTaskCodeParser->isAssignExpr($item)) {
						$props = $WorkFlowTaskCodeParser->getVariableNameProps($item);
						
						$var_name = self::getPropertiesResultVariableCode($props, false);
					}
					
					if ($var_name) {	
						$expr = isset($item->expr) ? $item->expr : null;
						$expr_type = $expr ? strtolower($expr->getType()) : "";
						$value = $WorkFlowTaskCodeParser->printCodeExpr($expr, false);
						$value = $WorkFlowTaskCodeParser->getStmtValueAccordingWithType($value, $expr_type);
						
						$props = array(
							"name" => $var_name,
							"value" => $value,
							"type" => $WorkFlowTaskCodeParser->getStmtType($expr),
						);
					}
					else {
						$props = array(
							"code" => $WorkFlowTaskCodeParser->printCodeExpr($item, false),
						);
					}
				
					$init_props[] = $props;
				}
			}
			
			//START $cond
			$cond_props = array();
			if ($cond) {
				$t = count($cond);
				for ($i = 0; $i < $t; $i++) {
					$item = $cond[$i];
				
					$props = $WorkFlowTaskCodeParser->getConditions($item);
					if (isset($props)) {
						$cond_props[] = $props;
					}
				}
			}
			
			//START $loop
			$loop_props = array();
			if ($loop) {
				$t = count($loop);
				for ($i = 0; $i < $t; $i++) {
					$item = $loop[$i];
					$item_type = strtolower($item->getType());
					$var_name = null;
					
					if ($item_type == "expr_postinc" || $item_type == "expr_preinc" || $item_type == "expr_predec" || $item_type == "expr_postdec") {
						$props = $WorkFlowTaskCodeParser->getVariableNameProps($item);
						$var_name = self::getPropertiesResultVariableCode($props, false);
					}
					
					if ($var_name) {
						$props = array(
							"name" => $var_name,
							"inc_or_dec" => $item_type == "expr_postinc" || $item_type == "expr_preinc" ? "inc" : "dec",
						);
					}
					else {
						$props = array(
							"code" => $WorkFlowTaskCodeParser->printCodeExpr($item, false),
						);
					}
				
					$loop_props[] = $props;	
				}
			}
			
			$sub_inner_tasks = self::createTasksPropertiesFromCodeStmts($sub_stmts, $WorkFlowTaskCodeParser);
			
			//PREPARING MAIN PROPS
			$props = array(
				"init" => $init_props,
				"cond" => $cond_props,
				"inc" => $loop_props,
				"execute_first_iteration" => $stmt_type == "stmt_do" ? 1 : 0,
				"label" => "Loop",
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
			
			$exits = array();
			$exits[self::DEFAULT_EXIT_ID][] = array("task_id" => "#next_task#");
			
			//PREPARING EXITS AND INNER TASKS
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
		
		$init = isset($raw_data["childs"]["properties"][0]["childs"]["init"]) ? $raw_data["childs"]["properties"][0]["childs"]["init"] : null;
		$cond = isset($raw_data["childs"]["properties"][0]["childs"]["cond"][0]["childs"]["group"][0]) ? $raw_data["childs"]["properties"][0]["childs"]["cond"][0]["childs"]["group"][0] : null;
		$inc = isset($raw_data["childs"]["properties"][0]["childs"]["inc"]) ? $raw_data["childs"]["properties"][0]["childs"]["inc"] : null;
		$execute_first_iteration = isset($raw_data["childs"]["properties"][0]["childs"]["execute_first_iteration"][0]["value"]) ? $raw_data["childs"]["properties"][0]["childs"]["execute_first_iteration"][0]["value"] : null;
		
		//PREPARING INIT
		$init_props = array();
		$t = $init ? count($init) : 0;
		for ($i = 0; $i < $t; $i++) {
			$item = isset($init[$i]["childs"]) ? $init[$i]["childs"] : null;
			
			if (isset($item["name"])) {
				$init_props[] = array(
					"name" => isset($item["name"][0]["value"]) ? $item["name"][0]["value"] : null,
					"value" => isset($item["value"][0]["value"]) ? $item["value"][0]["value"] : null,
					"type" => isset($item["type"][0]["value"]) ? $item["type"][0]["value"] : null,
				);
			}
			else {
				$init_props[] = array(
					"code" => isset($item["code"][0]["value"]) ? $item["code"][0]["value"] : null,
				);
			}
		}
		
		//PREPARING INC
		$inc_props = array();
		$t = $inc ? count($inc) : 0;
		for ($i = 0; $i < $t; $i++) {
			$item = isset($inc[$i]["childs"]) ? $inc[$i]["childs"] : null;
			
			if (isset($item["name"])) {
				$inc_props[] = array(
					"name" => isset($item["name"][0]["value"]) ? $item["name"][0]["value"] : null,
					"inc_or_dec" => isset($item["inc_or_dec"][0]["value"]) ? $item["inc_or_dec"][0]["value"] : null,
				);
			}
			else {
				$inc_props[] = array(
					"code" => isset($item["code"][0]["value"]) ? $item["code"][0]["value"] : null,
				);
			}
		}
		
		$properties = array(
			"init" => $init_props,
			"cond" => self::parseGroup($cond),
			"inc" => $inc_props,
			"execute_first_iteration" => $execute_first_iteration,
		);
		
		return $properties;
	}
	
	public function printCode($tasks, $stop_task_id, $prefix_tab = "", $options = null) {
		$data = isset($this->data) ? $this->data : null;
		
		$properties = isset($data["properties"]) ? $data["properties"] : null;
		
		$init = isset($properties["init"]) ? $properties["init"] : null;
		$cond = isset($properties["cond"]) ? $properties["cond"] : null;
		$inc = isset($properties["inc"]) ? $properties["inc"] : null;
		$execute_first_iteration = isset($properties["execute_first_iteration"]) ? $properties["execute_first_iteration"] : null;
		
		//PREPARING INIT CODE
		$init_delimiter = $execute_first_iteration == "1" ? ";\n$prefix_tab" : ", ";
		
		$init_counters_code = "";
		$t = $init ? count($init) : 0;
		for ($i = 0; $i < $t; $i++) {
			$item = $init[$i];
			
			if (isset($item["name"])) {
				$item_value = isset($item["value"]) ? $item["value"] : null;
				$item_type = isset($item["type"]) ? $item["type"] : null;
				
				$c = self::getVariableValueCode($item["name"], "variable") . " = " . self::getVariableValueCode($item_value, $item_type);
			}
			else {
				$c = isset($item["code"]) ? trim($item["code"]) : "";
				$c = substr($c, strlen($c) - 1) == ";" ? substr($c, 0, strlen($c) - 1) : $c;
			}
			
			if ($c) {
				$init_counters_code .= ($init_counters_code ? $init_delimiter : "") . $c;
			}
		}
		
		//PREPARING COND CODE
		$test_counters_code = self::printGroup($cond);
		
		//PREPARING INC CODE
		$inc_delimiter = $execute_first_iteration == "1" ? ";\n$prefix_tab\t" : ", ";
		
		$inc_counters_code = "";
		$t = $inc ? count($inc) : 0;
		for ($i = 0; $i < $t; $i++) {
			$item = $inc[$i];
			
			if (isset($item["name"])) {
				$c = self::getVariableValueCode($item["name"], "variable") . (isset($item["inc_or_dec"]) && $item["inc_or_dec"] == "decrement" ? "--" : "++");
			}
			else {
				$c = isset($item["code"]) ? trim($item["code"]) : "";
				$c = substr($c, strlen($c) - 1) == ";" ? substr($c, 0, strlen($c) - 1) : $c;
			}
			
			if ($c) {
				$inc_counters_code .= ($inc_counters_code ? $inc_delimiter : "") . $c;
			}
		}
		
		//PREPARING INNER TASKS CODE
		$stops_id = array();
		
		if ($stop_task_id)
			$stops_id = is_array($stop_task_id) ? $stop_task_id : array($stop_task_id);
		
		if (!empty($data["exits"][self::DEFAULT_EXIT_ID][0]))
			$stops_id = array_merge($stops_id, $data["exits"][self::DEFAULT_EXIT_ID]);
		
		$start_exit_task_id = isset($data["exits"]["start_exit"]) ? $data["exits"]["start_exit"] : null;
		$loop_code = self::printTask($tasks, $start_exit_task_id, $stops_id, $prefix_tab . "\t", $options);
		
		//PREPARING MAIN CODE
		if ($execute_first_iteration) {
			$code =  $init_counters_code ? $prefix_tab . "$init_counters_code;\n\n" : "";
			$code .= $prefix_tab . "do {";
			$code .= !$loop_code && !$inc_counters_code ? "\n\n" : $loop_code;
			$code .= $inc_counters_code ? "\n" . $prefix_tab . "\t$inc_counters_code;\n" : "";
			$code .=  !$prefix_tab && !preg_match("/\s/", substr($code, -1)) ? " " : ""; //add space here, bc the $prefix_tab could be empty and the $code could end in <?php. If we do not add the space here, then we will get <?php} which will give a php error.
			$code .= $prefix_tab . "}\n";
			$code .= $prefix_tab . "while ($test_counters_code);\n";
		}
		else {
			if (!$init_counters_code && !$inc_counters_code) {
				$code =  $prefix_tab . "while ($test_counters_code) {";
			}
			else {
				$code =  $prefix_tab . "for ($init_counters_code; $test_counters_code; $inc_counters_code) {";
			}
			
			$code .= $loop_code ? $loop_code : "\n\n";
			$code .=  !$prefix_tab && !preg_match("/\s/", substr($code, -1)) ? " " : ""; //add space here, bc the $prefix_tab could be empty and the $code could end in <?php. If we do not add the space here, then we will get <?php} which will give a php error.
			$code .= $prefix_tab . "}\n";
		}
		
		$exit_task_id = isset($data["exits"][self::DEFAULT_EXIT_ID][0]) ? $data["exits"][self::DEFAULT_EXIT_ID][0] : null;
		return $code . self::printTask($tasks, $exit_task_id, $stop_task_id, $prefix_tab, $options);
	}
}
?>
