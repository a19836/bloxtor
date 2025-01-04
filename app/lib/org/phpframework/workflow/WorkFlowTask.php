<?php
include_once get_lib("org.phpframework.workflow.IWorkFlowTask");
include_once get_lib("org.phpframework.workflow.exception.WorkFlowTaskException");
include_once get_lib("org.phpframework.util.text.TextSanitizer");

abstract class WorkFlowTask implements IWorkFlowTask {
	public $data;
	const DEFAULT_EXIT_ID = "default_exit";
	const CONNECTOR_TASK_TYPE = "__connector__";
	
	private $task_class_info;
	
	protected $is_loop_task = false;
	protected $is_return_task = false;
	protected $is_break_task = false;
	protected $priority = false;
	
	//This methods are now defined in the interface: IWorkFlowTask.
	//public abstract function parseProperties(&$task);
	//public abstract function printCode($tasks, $stop_task_id);
	
	public function cloneTask() {
		eval ('$WorkFlowTaskClone = new ' . get_class($this) . '();');
		
		if ($WorkFlowTaskClone) {
			$WorkFlowTaskClone->setTaskClassInfo( $this->task_class_info );
			$WorkFlowTaskClone->data = $this->data;
		}
		
		return $WorkFlowTaskClone;
	}
	
	public function parse($task_data) {
		$task = array(
			"id" => self::getTaskId($task_data),
			"type" => self::getTaskType($task_data),
			"label" => isset($task_data["childs"]["label"][0]["value"]) ? $task_data["childs"]["label"][0]["value"] : null,
			"tag" => isset($task_data["childs"]["tag"][0]["value"]) ? $task_data["childs"]["tag"][0]["value"] : null,
			"raw_data" => $task_data,
		);
		
		$start = isset($task["raw_data"]["@"]["start"]) ? $task["raw_data"]["@"]["start"] : null;
		if (strlen($start))
			$task["start"] = $start == "true" ? 1 : (is_numeric($start) && $start > 0 ? $start : false);
		
		$exits = self::getTaskExists($task_data);
		if ($exits)
			$task["exits"] = $exits;
		
		$properties = $this->parseProperties($task);
		$properties = $properties ? $properties : array();
		
		$properties["comments"] = isset($task_data["childs"]["properties"][0]["childs"]["comments"][0]["value"]) ? $task_data["childs"]["properties"][0]["childs"]["comments"][0]["value"] : null;
		
		$task["properties"] = $properties;
		
		unset($task["raw_data"]);
		
		$this->data = $task;
	}
	
	protected static function parseGroup($main_group) {
		$group_data = array(
			"type" => "group",
			"join" => isset($main_group["@"]["join"]) ? $main_group["@"]["join"] : null,
		);
		
		$group_items = array();
		
		if (!empty($main_group["childs"]) && is_array($main_group["childs"])) {
			foreach ($main_group["childs"] as $type => $items_groups) {
				if ($type == "item") {
					foreach ($items_groups as $item) {
						$first = array(
							"value" => isset($item["childs"]["first"][0]["value"]) ? $item["childs"]["first"][0]["value"] : null, 
							"type" => isset($item["childs"]["first"][0]["@"]["type"]) ? $item["childs"]["first"][0]["@"]["type"] : null
						);
						$second = array(
							"value" => isset($item["childs"]["second"][0]["value"]) ? $item["childs"]["second"][0]["value"] : null, 
							"type" => isset($item["childs"]["second"][0]["@"]["type"]) ? $item["childs"]["second"][0]["@"]["type"] : null
						);
						
						if (isset($item["childs"]["first"][0]["childs"]["value"][0]["value"])) {
							$first["value"] = $item["childs"]["first"][0]["childs"]["value"][0]["value"];
						}
						
						if (isset($item["childs"]["first"][0]["childs"]["type"][0]["value"])) {
							$first["type"] = $item["childs"]["first"][0]["childs"]["type"][0]["value"];
						}
						
						if (isset($item["childs"]["second"][0]["childs"]["value"][0]["value"])) {
							$second["value"] = $item["childs"]["second"][0]["childs"]["value"][0]["value"];
						}
						
						if (isset($item["childs"]["second"][0]["childs"]["type"][0]["value"])) {
							$second["type"] = $item["childs"]["second"][0]["childs"]["type"][0]["value"];
						}
						
						$xml_order_id = isset($item["xml_order_id"]) ? $item["xml_order_id"] : null;
						$group_items[$xml_order_id] = array(
							"type" => $type,
							"first" => $first,
							"operator" => isset($item["childs"]["operator"][0]["value"]) ? $item["childs"]["operator"][0]["value"] : null,
							"second" => $second,
						);
					}
				}
				else if ($type == "group") {
					foreach ($items_groups as $group) {
						$sub_group_data = self::parseGroup($group);
						$xml_order_id = isset($group["xml_order_id"]) ? $group["xml_order_id"] : null;
						
						$group_items[$xml_order_id] = $sub_group_data;
					}
				}
				else if ($type == "join") {
					$group_data["join"] = isset($items_groups[0]["value"]) ? $items_groups[0]["value"] : null;
				}
			}
		}
		
		/* START: SORT ITEMS */
		$new_group_items = array();
		$keys = array_keys($group_items);
		sort($keys);
		foreach($keys as $key) {
			$new_group_items[] = $group_items[$key];
		}
		/* END: SORT ITEMS */
		
		$group_data["item"] = $new_group_items;
		
		return $group_data;
	}
	
	//if type is not contained in the avaialble types, it convert it to an empty type, which corresponds to "code"
	protected static function getConfiguredParsedType($type, $available_types = array("", "string", "variable")) {
		return $type && is_array($available_types) ? (in_array($type, $available_types) ? $type : "") : $type;
	}
	
	public static function printTask($tasks, $task_ids, $stop_tasks_id, $prefix_tab = "", $options = null) {
		$return_obj = $options && isset($options["return_obj"]) ? $options["return_obj"] : false;
		$code_with_comments = $options && isset($options["code_with_comments"]) ? $options["code_with_comments"] : true;
		$res = $return_obj ? array() : "";
		
		//print_r($tasks);
		//print_r($task_ids);
		
		if (isset($task_ids)) {
			if (!is_array($task_ids))
				$task_ids = array($task_ids);
			
			$stop_tasks_id = is_array($stop_tasks_id) ? $stop_tasks_id : array($stop_tasks_id);
			
			$total = count($task_ids);
			for ($i = 0; $i < $total; $i++) {
				$task_id = $task_ids[$i];
				
				if ($task_id && !in_array($task_id, $stop_tasks_id)) {
					if (isset($tasks[ $task_id ])) {
						$task = $tasks[ $task_id ];
						$task_code = $task->printCode($tasks, $stop_tasks_id, $prefix_tab, $options);
						
						$task_comments = "";
						
						if (!empty($task->data["properties"]["comments"])) {
							$task_comments = $task->data["properties"]["comments"];
							
							//prepare comments
							if (strpos($task_comments, "\n") === false)
								$task_comments = $prefix_tab . "//" . $task_comments; //adding single line of comments its fine now (2024-12-04), bc in the LayoutUIEditor, whe we open a html element with an attribute with php code, the LayoutUIEditor will put that php code in a 1 single line, but before this, it will convert the single line comments into multi-line comments, in order to avoid commenting all the php code forward. This means its ok to use single-line comments.
							else
								$task_comments = $prefix_tab . "/**\n$prefix_tab * " . str_replace("\n", "\n$prefix_tab * ", $task_comments) . "\n$prefix_tab */"; //set tab prefixes
						}
						
						if ($return_obj)
							$res[] = array("comments" => $task_comments, "code" => $task_code);
						else if (isset($task_code))
							$res .= "\n" . ($code_with_comments && $task_comments ? $task_comments . "\n" : "") . $task_code;
					}
					else
						launch_exception(new WorkFlowTaskException(2, $task_id));
				}
			}
		}
		
		return $res;
	}
	
	protected static function printGroup($group) {
		$code = "";
		
		if (isset($group["item"]) && is_array($group["item"])) {
			$join = isset($group["join"]) && strtolower($group["join"]) == "and" ? "&&" : "||";
			
			$add_join = false;
			foreach($group["item"] as $item) {
				$code .= ($add_join ? " " . $join . " " : "");
				$type = isset($item["type"]) ? $item["type"] : null;
				
				if ($type == "group") {
					$code .= "(" . self::printGroup($item) . ")";
				}
				else if ($type == "item") {
					$item_first = isset($item["first"]) ? $item["first"] : null;
					$item_second = isset($item["second"]) ? $item["second"] : null;
					
					if (is_array($item_first)) {
						$item_first_value = isset($item_first["value"]) ? $item_first["value"] : null;
						$item_first_type = isset($item_first["type"]) ? $item_first["type"] : null;
						$first_value = self::getVariableValueCode($item_first_value, $item_first_type);
					}
					else {
						$first_value = $item_first;
					}
					
					if (is_array($item_second)) {
						$item_second_value = isset($item_second["value"]) ? $item_second["value"] : null;
						$item_second_type = isset($item_second["type"]) ? $item_second["type"] : null;
						$second_value = self::getVariableValueCode($item_second_value, $item_second_type);
					}
					else {
						$second_value = $item_second;
					}
					
					$operator = !empty($item["operator"]) ? $item["operator"] : "==";
					
					$lfv = strtolower($first_value);
					$lsv = strtolower($second_value);
					
					if ($lfv == "true" || $lsv == "true") {
						if ($lfv == $lsv)
							$code .= $first_value;
						else if ($operator == "==")
							$code .= $lfv == "true" ? $second_value : $first_value;
						else if ($operator == "!=")
							$code .= $lfv == "true" ? "!$second_value" : "!$first_value";
						else
							$code .= $first_value . " " . $operator . " " . $second_value;
					}
					else if ($lfv == "false" || $lsv == "false") {
						if ($lfv == $lsv)
							$code .= $first_value;
						else if ($operator == "==")
							$code .= $lfv == "false" ? "!$second_value" : "!$first_value";
						else if ($operator == "!=")
							$code .= $lfv == "false" ? $second_value : $first_value;
						else
							$code .= $first_value . " " . $operator . " " . $second_value;
					}
					else
						$code .= $first_value . " " . $operator . " " . $second_value;
				}
				
				$add_join = true;
			}
		}
		
		return $code;
	}
	
	public static function getTaskPaths($tasks, $task_id, $strip_loops_start_path = false) {
		$paths = array();
		
		if ($task_id) {
			$visited = array();
			
			// Start DFS from the start task
			self::dfsForTaskPaths($task_id, array(), $paths, $tasks, $visited, $strip_loops_start_path);
		}
		
		//error_log("task_id:$task_id:\n".print_r(array_slice($paths, 0, 100), 1)."\n\n", 3, $GLOBALS["log_file_path"]);
		return $paths;
	}
	/* DEPRECATED bc it consumes a lot of memory and gives a memory dump if we have a more than 16 lines of code similar with '$x = $y ? $y : null;', which gives almost 100.000 items in the paths array, blocking apache server and consuming 100% CPU.
	public static function getTaskPaths($tasks, $task_id, $strip_loops_start_path = false) {
		$paths = array();
		
		if ($task_id) {
			if (isset($tasks[ $task_id ])) {
				$task = $tasks[ $task_id ];
				$paths[] = array($task_id);
				$exits = isset($task->data["exits"]) ? $task->data["exits"] : null;
				
				if (is_array($exits)) { //Get all the paths even with the tasks after the break tasks. Otherwise it messes the code, bc we will loose 1 common path, and the correspondent next tasks will only be associated with 1 path that could be only inside of an else statement.
					$new_paths = array();
					
					if ($strip_loops_start_path && $task->isLoopTask()) //strips the exit which contains the inner code of the loop.
						$exits = array(
							self::DEFAULT_EXIT_ID => isset($exits[self::DEFAULT_EXIT_ID]) ? $exits[self::DEFAULT_EXIT_ID] : null
						);
					
					foreach ($exits as $exit_id => $exit) {
						if (!is_array($exit))
							$exit = array($exit);
						
						$t = count($exit);
						for ($i = 0; $i < $t; $i++) {
							$exit_task_id = $exit[$i];
							
							$sub_paths = self::getTaskPaths($tasks, $exit_task_id, $strip_loops_start_path);
							
							$t2 = count($sub_paths);
							for ($j = 0; $j < $t2; $j++) {
								$sub_path = $sub_paths[$j];
								array_unshift($sub_path, $task_id);
								
								$new_paths[] = $sub_path;
							}
						}
					}
					
					if ($new_paths)
						$paths = $new_paths;
				}
			}
			else
				launch_exception(new WorkFlowTaskException(2, $task_id));
		}
		
		return $paths;
	}*/
	
	// Helper function for DFS - Depth-First Search - with cycle detection
	private static function dfsForTaskPaths($task_id, $current_path, &$paths, $tasks, &$visited, $strip_loops_start_path) {
		$current_path[] = $task_id; // Add the current task to the path
		//error_log("task_id:$task_id:\n".print_r($current_path, 1)."\n\n", 3, $GLOBALS["log_file_path"]);
		
		if (in_array($task_id, $visited)) {
			// Stop if the node is already visited in the current path (cycle detected)
			return;
		}

		$visited[] = $task_id; // Mark the node as visited
		$task = isset($tasks[$task_id]) ? $tasks[$task_id] : null;
		
		// If the node has no children, it's a leaf, so save the path
		if (empty($task))
			launch_exception(new WorkFlowTaskException(2, $task_id));
		else { // Recursively explore each child
			$exits = isset($task->data["exits"]) ? $task->data["exits"] : null;
			$exists_exits = false;
			
			if (is_array($exits)) { //Get all the paths even with the tasks after the break tasks. Otherwise it messes the code, bc we will loose 1 common path, and the correspondent next tasks will only be associated with 1 path that could be only inside of an else statement.
				if ($strip_loops_start_path && $task->isLoopTask()) //strips the exit which contains the inner code of the loop.
					$exits = array(
						self::DEFAULT_EXIT_ID => isset($exits[self::DEFAULT_EXIT_ID]) ? $exits[self::DEFAULT_EXIT_ID] : null
					);
				
				foreach ($exits as $exit_id => $exit) {
					if (!is_array($exit))
						$exit = array($exit);
					
					$t = count($exit);
					for ($i = 0; $i < $t; $i++) {
						$exit_task_id = $exit[$i];
						
						if ($exit_task_id) {
							$exists_exits = true;
							self::dfsForTaskPaths($exit_task_id, $current_path, $paths, $tasks, $visited, $strip_loops_start_path);
						}
					}
				}
			}
			
			if (!$exists_exits)
				$paths[] = $current_path;
		}

		// Remove the node from the visited list (backtrack)
		array_pop($visited);
	}
	
	//This function supposes that each task can have multiple exits but only 1 connection per exit.
	public static function getCommonTaskExitIdFromTaskPaths($tasks, $task_id) {
		$paths = self::getTaskPaths($tasks, $task_id, true);
		//print_r($paths);
		//error_log("paths:".print_r($paths, 1)."\n\n", 3, "/var/www/html/livingroop/default/tmp/phpframework.log");
		
		$paths_total = $paths ? count($paths) : 0;
		
		$paths_belong_to_the_same_group = self::pathsBelongToTheSameGroup($paths);
		//error_log("paths_belong_to_the_same_group:$paths_belong_to_the_same_group\n\n", 3, "/var/www/html/livingroop/default/tmp/phpframework.log");
		
		//Find common task in all paths, but only if there are more than one path. 
		//Yes, there is a case where we can hv just one path, this is something like this: "if ($x) foreach ($arr as $item) if ($item[0]) $x = 1;", there will be only 1 path, or multiple paths but from the same branch.
		//If paths belong to the same branch return null;
		if (!$paths_belong_to_the_same_group) {
			$path = isset($paths[0]) ? $paths[0] : null;
			$t = $path ? count($path) : 0;
			for ($i = 1; $i < $t; $i++) {//first item is always common because is a if or a switch
				$exit_task_id = $path[$i];
				
				$status = true;
				for ($j = 1; $j < $paths_total; $j++)
					if (!in_array($exit_task_id, $paths[$j]))
						$status = false;
			
				if ($status)
					return $exit_task_id;
			}
		}
		
		//find if all paths without a return task.
		$paths_without_return_task = array();
		for ($j = 0; $j < $paths_total; $j++) {
			$path = $paths[$j];
			
			if (is_array($path)) {
				$last_task_id = $path[ count($path) - 1 ];
				$last_task = $tasks[$last_task_id];
			
				if (!$last_task->isReturnTask())
					$paths_without_return_task[] = $j;
			}
		}
		
		$pwrt_count = count($paths_without_return_task);
		//error_log("$pwrt_count!=$paths_total\n\n", 3, "/var/www/html/livingroop/default/tmp/phpframework.log");
		
		//$pwrt_count != count($paths) => otherwise already return the common task id from the code above
		if ($pwrt_count >= 2 && $pwrt_count != $paths_total) {//Find common task for paths without return
			$pwrt = array();
			for ($i = 0; $i < $pwrt_count; $i++)
				$pwrt[] = $paths[ $paths_without_return_task[$i] ];
			
			$paths_belong_to_the_same_group = self::pathsBelongToTheSameGroup($pwrt);
			
			//Find common task in all paths, but only if there are more than one path. 
			//Yes, there is a case where we can hv just one path, this is something like this: "if ($x) foreach ($arr as $item) if ($item[0]) $x = 1;", there will be only 1 path, or multiple paths but from the same branch.
			//If paths belong to the same branch return null;
			if (!$paths_belong_to_the_same_group) {
				$idx = $paths_without_return_task[0];
				$path = $paths[$idx];
		
				$t = $path ? count($path) : 0;
				for ($i = 1; $i < $t; $i++) {//first item s always common because is a if or a switch
					$exit_task_id = $path[$i];
				
					$status = true;
					for ($j = 1; $j < $pwrt_count; $j++) {
						$idx = $paths_without_return_task[$j];
					
						if (!in_array($exit_task_id, $paths[$idx]))
							$status = false;
					}
				
					if ($status)
						return $exit_task_id;
				}
			}
		}
		else if ($pwrt_count == 0) {//Find common task for paths with return
			$paths_count = array();
		
			//1st: get the counts for the common tasks
			for ($i = 0; $i < $paths_total; $i++) {
				$path = $paths[$i];
				
				do {
					$path_str = implode(",", $path); //get path in a string
				
					$count = 0;
					for ($w = 0; $w < $paths_total; $w++) 
						if ($w != $i) {
							$other_path = $paths[$w];
							$other_path_str = implode(",", $other_path);//get other path in a string
							
							if (strpos($other_path_str, $path_str) !== false) //check if the path_str exists in the other_path_str. For each tasks path, see how many times it appears in the other paths and saves the correspondent path with the correspondent count.
								$count++;
							
							//NOTE: I already tried to add a diferent logic here, but everytime I try, I find out that this is the better and more stable logic which includes all cases, even if it repeates the code. 
							//I tried to add a logic without the strpos and with the $common_task_id in all paths and only a few and then get the bigger count, but this is a problem and doesn't work for all cases. 
							//IS BETTER LEAVE THIS LOGIC WITH THE STRPOS WHICH REPEATS MORE CODE, BC COVERS ALL CASES!
							//Last change was in 2018-02-10
						}
					
					if ($count > 0)
						$paths_count[$count][] = $path_str;
					
					array_shift($path);
				} 
				while(!empty($path));
			}
			//error_log("paths_count:".print_r($paths_count, 1)."\n\n", 3, "/var/www/html/livingroop/default/tmp/phpframework.log");
	
			//2nd: gets the task which belongs to the bigger tasks path.
			if ($paths_count) {
				krsort($paths_count);
				$keys = array_keys($paths_count);
				$common_paths = $paths_count[ $keys[0] ];
				
				$count = 0;
				$common_task_id = null;
				
				$t = $common_paths ? count($common_paths) : 0;
				for ($i = 0; $i < $t; $i++) {
					$common_path = explode(",", $common_paths[$i]);
					$path_count = count($common_path);
					
					if ($path_count > $count) {
						$count = $path_count;
						$common_task_id = $common_path[0];
					}
				}
				
				//in case all exits of the if/switch/try tasks be connected to the same task, then get the second task.
				if ($common_task_id == $task_id && $paths_belong_to_the_same_group && $paths[0]) {
					$all_paths_are_the_same = true;
					$prev_path_str = null;
					
					for ($i = 0; $i < $paths_total; $i++) 
						if (is_array($paths[$i]) && count($paths[$i]) > 0) {
							$path_str = implode(",", $paths[$i]);
							
							if ($prev_path_str && $prev_path_str != $path_str) {
								$all_paths_are_the_same = false;
								break;
							}
							
							$prev_path_str = $path_str;
						}
						else {
							$all_paths_are_the_same = false;
							break;
						}
					
					if ($all_paths_are_the_same)
						$common_task_id = $paths[0][1];
				}
				
				return $common_task_id;
			}
		}
		
		return null;
	}
	
	private static function pathsBelongToTheSameGroup($paths) {
		if ($paths) {
			$t = count($paths);
			for ($i = 1; $i < $t; $i++) 
				if ($paths[$i][1] != $paths[0][1]) //[0] means first path and [1] first task_id. [0] is the main task so will always be common.
					return false;
		}
		return true;
	}
	
	public static function createTasksPropertiesFromCodeStmts($stmts, $WorkFlowTaskCodeParser) {
		if ($stmts) {
			//print_r($stmts);
			$tasks_properties = array();
			$tasks_properties_inner_tasks = array();
			$undefined_stmts = array();
			
			$available_statements = $WorkFlowTaskCodeParser->getAvailableStatements();
			//print_r($available_statements);die();
			
			$stmts = $WorkFlowTaskCodeParser->convertStmtsExpressionToSimpleStmts($stmts);
			//print_r($stmts);
			
			foreach ($stmts as $stmt) {
				$stmt_type = strtolower($stmt->getType());
				//echo "stmt_type:$stmt_type\n";
				$tasks = isset($available_statements[$stmt_type]) ? $available_statements[$stmt_type] : null;
				//print_r($available_statements);
				
				$exists = false;
				
				$t = $tasks ? count($tasks) : 0;
				for ($i = 0; $i < $t; $i++) {
					$task = $tasks[$i];
			
					if ($task) {
						$exits = $inner_tasks = array();
						$props = $task["obj"]->createTaskPropertiesFromCodeStmt($stmt, $WorkFlowTaskCodeParser, $exits, $inner_tasks, $tasks_properties);
						
						$xml_task = null;
						if (is_array($props) && !empty($props)) {
							if ($WorkFlowTaskCodeParser->withComments()) {
								$comments = $WorkFlowTaskCodeParser->printComments($stmt);
								
								if ($comments) {
									if (!empty($props["comments"])) {
										if (strpos($props["comments"], $comments) === false) //avoid repeated comments
											$props["comments"] = $comments . "\n" . $props["comments"];
									}
									else
										$props["comments"] = $comments;
								}
							}
							
							$xml_task = $WorkFlowTaskCodeParser->createXMLTask($task, $props, $exits);
						}
						else if ($inner_tasks)
							$xml_task = $WorkFlowTaskCodeParser->createConnectorXMLTask($exits);
						
						if ($xml_task) {
							if (!empty($undefined_stmts)) {
								$tasks_properties[] = $WorkFlowTaskCodeParser->createUndefinedXMLTask($undefined_stmts);
								$undefined_stmts = array();
							}
							
							$tasks_properties[] = $xml_task;
							
							if ($inner_tasks)
								$tasks_properties_inner_tasks[ count($tasks_properties) - 1 ] = $inner_tasks;
							
							$exists = true;
							break;
						}
					}
				}
			
				if (!$exists)
					$undefined_stmts[] = $stmt;
			}
			
			if ($undefined_stmts)
				$tasks_properties[] = $WorkFlowTaskCodeParser->createUndefinedXMLTask($undefined_stmts);
			
			//print_r($tasks_properties);print_r($tasks_properties_inner_tasks);
			
			//PREARING TASKS EXITS
			$new_tasks_properties = array();
			$t = count($tasks_properties);
			for ($i = 0; $i < $t; $i++) {
				$current_task = $tasks_properties[$i];
				$inner_tasks = isset($tasks_properties_inner_tasks[$i]) ? $tasks_properties_inner_tasks[$i] : null;
				
				//GET NEXT TASK
				do {
					$i++;
					$next_task = isset($tasks_properties[$i]) ? $tasks_properties[$i] : null;
					
					if (isset($next_task["type"]) && $next_task["type"] == self::CONNECTOR_TASK_TYPE) {
						$next_inner_tasks = isset($tasks_properties_inner_tasks[$i]) ? $tasks_properties_inner_tasks[$i] : null;
						$next_task = !empty($next_inner_tasks[0]["type"]) ? $next_inner_tasks[0] : (isset($next_inner_tasks[0][0]) ? $next_inner_tasks[0][0] : null);
					}
				} while (!$next_task && $i < $t - 1);
				
				$i--;
				
				//echo $current_task["tag"]."->".$next_task["tag"]."\n";
				
				//PREPARE CURRENT TASK - ONLY IF IT'S NOT A CONNECTOR
				$current_task_type = isset($current_task["type"]) ? $current_task["type"] : null;
				
				if ($current_task_type != self::CONNECTOR_TASK_TYPE) {
					if (!empty($current_task["exits"]) && !empty($next_task["id"]))
						$WorkFlowTaskCodeParser->replaceNextTaskInTaskExits($current_task, $next_task);
					else if (empty($current_task["exits"]) && empty($current_task["is_return"]) && empty($current_task["is_break"]))
						$WorkFlowTaskCodeParser->addNextTaskToAllTaskExits($current_task, $next_task);
					
					$new_tasks_properties[] = $current_task;
				}
				
				//PREPARE INNER TASKS
				//REPLACE #NEXT_TASK# IN INNER TASK NODES ($inner_tasks), WITH THE NEXT TASK ID ($next_task["id"])
				if ($inner_tasks) {
					$inner_tasks_groups = !empty($inner_tasks[0]["type"]) ? array($inner_tasks) : $inner_tasks;//this is useful for the switch and if tasks, because they can have multiple exits.
					
					$t2 = $inner_tasks_groups ? count($inner_tasks_groups) : 0;
					for ($j = 0; $j < $t2; $j++) {
						$inner_tasks = $inner_tasks_groups[$j];
						$t3 = $inner_tasks ? count($inner_tasks) : 0;
						
						if (!empty($next_task["id"]))
							for ($w = 0; $w < $t3; $w++) 
								if (!empty($inner_tasks[$w]["exits"]) && empty($inner_tasks[$w]["is_break"]))
									$WorkFlowTaskCodeParser->replaceNextTaskInTaskExits($inner_tasks[$w], $next_task);
						
						$new_tasks_properties = array_merge($new_tasks_properties, $inner_tasks);
					}
					//print_r($inner_tasks);
				}
			}
			$tasks_properties = $new_tasks_properties;
			
			return $tasks_properties;
		}
		
		return null;
	}
	
	public static function joinTaskPropertiesWithIncludeFileTaskPropertiesSibling(&$task_properties, &$tasks_properties) {
		//check if previous task was an include
		if ($tasks_properties) {
			$last_task_properties = $tasks_properties[ count($tasks_properties) - 1 ];
			
			if ($last_task_properties && isset($last_task_properties["tag"]) && $last_task_properties["tag"] == "includefile") {
				if (!is_array($task_properties))
					$task_properties = array();
				
				$task_properties["include_file_path"] = isset($last_task_properties["properties"]["file_path"]) ? $last_task_properties["properties"]["file_path"] : null;
				$task_properties["include_file_path_type"] = isset($last_task_properties["properties"]["type"]) ? $last_task_properties["properties"]["type"] : null;
				$task_properties["include_once"] = isset($last_task_properties["properties"]["once"]) ? $last_task_properties["properties"]["once"] : null;
				
				array_pop($tasks_properties);
			}
		}
	}
	
	protected static function prepareTaskPropertyValueLabelFromCodeStmt($value) {
		return substr($value, 0, 1) == '$' ? substr($value, 1) : (substr($value, 0, 2) == '@$' ? substr($value, 2) : $value);
	}
	
	public static function getTaskType($task_data) {
		return isset($task_data["childs"]["type"][0]["value"]) ? strtolower($task_data["childs"]["type"][0]["value"]) : false;
	}
	
	public static function getTaskId($task_data) {
		return isset($task_data["childs"]["id"][0]["value"]) ? $task_data["childs"]["id"][0]["value"] : false;
	}
	
	public static function getTaskExists($task_data) {
		$exits = array();
		
		if (isset($task_data["childs"]["exits"][0]["childs"]) && is_array($task_data["childs"]["exits"][0]["childs"]))
			foreach ($task_data["childs"]["exits"][0]["childs"] as $exit_key => $exit_data) 
				if ($exit_data) {
					$total = count($exit_data);
					for ($i = 0; $i < $total; $i++) 
						if (!empty($exit_data[$i]["childs"]["task_id"][0]["value"]))
							$exits[ $exit_key ][] = $exit_data[$i]["childs"]["task_id"][0]["value"];
				}
		
		return $exits;
	}
	
	//DEPRECATED
	protected static function parseParameters($parameters) {
		/*$new_parameters = array();
		
		if (is_array($parameters)) {
			foreach ($parameters as $parameter) {
				$has_childs = isset($parameter["childs"]["items"][0]);
				if ($has_childs) {
					$value = self::parseArrayItems($parameter["childs"]["items"][0]);
				}
				else {
					$value = $parameter["value"];
				}
				
				if (isset($parameter["@"]["key"])) {
					$new_parameters[ $parameter["@"]["key"] ] = $value;
				}
				else {
					$new_parameters[] = $value;
				}
			}
		}
		
		return $new_parameters;*/
	}
	
	protected static function parseIncludes($includes) {
		$new_includes = array();
		
		if (is_array($includes)) {
			foreach ($includes as $include) {
				$new_includes[] = array(
					"type" => isset($include["@"]["type"]) ? $include["@"]["type"] : "",
					"include" => isset($include["value"]) ? $include["value"] : null,
				);
			}
		}
		
		return $new_includes;
	}
	
	protected static function parseArrayItems($items) {
		$new_items = array();
		
		$t = $items ? count($items) : 0;
		for ($i = 0; $i < $t; $i++) {
			$item = isset($items[$i]["childs"]) ? $items[$i]["childs"] : null;
			
			if ($item) {
				$key = isset($item["key"][0]["value"]) ? $item["key"][0]["value"] : null;
				$key_type = isset($item["key_type"][0]["value"]) ? $item["key_type"][0]["value"] : null;
				$sub_items = isset($item["items"]) ? $item["items"] : null;
			
				if ($sub_items) {
					$new_items[] = array(
						"key" => $key,
						"key_type" => $key_type,
						"items" => self::parseArrayItems($sub_items),
					);
				}
				else {
					$value = isset($item["value"][0]["value"]) ? $item["value"][0]["value"] : null;
					$value_type = isset($item["value_type"][0]["value"]) ? $item["value_type"][0]["value"] : null;
				
					$new_items[] = array(
						"key" => $key,
						"key_type" => $key_type,
						"value" => $value,
						"value_type" => $value_type,
					);
				}
			}
		}
		
		return $new_items;
	}
	
	//must be public bc is used in some modules like the form module
	public static function getArrayString($items, $prefix_tab = "") {
	//print_r($items);die();
		if (empty($items) || !is_array($items))
			return "array()";
		
		$code = $prefix_tab . "array(\n";
		$first_elm = true;
		
		//this is suppose to be a numeric keys array, but it can start with the key=1 or key=3, so we cannot do the regular for.
		foreach ($items as $item) {
			$key = isset($item["key"]) ? $item["key"] : null;
			$key_type = isset($item["key_type"]) ? $item["key_type"] : null;
			$sub_items = isset($item["items"]) ? $item["items"] : null;
			
			if ($sub_items) {
				$value = self::getArrayString($sub_items, $prefix_tab . "\t");
				$value = ltrim($value);
			}
			else {
				$value = isset($item["value"]) ? $item["value"] : null;
				$value_type = isset($item["value_type"]) ? $item["value_type"] : null;
				
				$value = self::getVariableValueCode($value, $value_type);
			}
			
			if (!$first_elm) {
				$code .= ",\n";
			}
			
			if ($key_type != "null" && isset($key) && strlen($key) > 0) {
				$key = self::getVariableValueCode($key, $key_type);
				
				$code .= "$prefix_tab\t$key => " . $value;
			}
			else {
				$code .= "$prefix_tab\t$value";
			}
			
			$first_elm = false;
		}
		
		$code .= "\n$prefix_tab)";
		
		return $code;
	}
	
	protected static function getParametersString($parameters) {
		$str = "";
		
		if (is_array($parameters)) {
			$t = count($parameters);
			for ($i = 0; $i < $t; $i++) {
				$parameter = $parameters[$i];
				
				$value = isset($parameter["childs"]["value"][0]["value"]) ? $parameter["childs"]["value"][0]["value"] : null;
				$type = isset($parameter["childs"]["type"][0]["value"]) ? $parameter["childs"]["type"][0]["value"] : null;
				
				if (isset($value)) {
					$parameter = self::getVariableValueCode($value, $type);
					$parameter = strlen($parameter) ? $parameter : "null";
				
					$str .= ($i > 0 ? ", " : "") . $parameter;
				}
			}
		}
		
		return $str;
	}
	
	protected static function getVariableAssignmentOperator($assignment = false) {
		return $assignment == "concat" || $assignment == "concatenate" ? ".=" : ($assignment == "increment" ? "+=" : ($assignment == "decrement" ? "-=" : "="));
	}
	
	//must be public bc is used in some modules like the form module
	public static function getVariableValueCode($variable, $type = null) {
		if (!isset($variable))
			return $type == "string" || $type == "date" ? "''" : (!$type ? "null" : "");
		
		//echo "$variable, $type<br>\n";
		$v = trim($variable);
		
		if ($type == "variable" && $v)
			return (substr($v, 0, 1) != '$' && substr($v, 0, 2) != '@$' ? '$' : '') . $v;
		else if ($type == "string" || $type == "date") {
			/*
			 * 2021-10-30: All comments below were copied from app/lib/org/phpframework/phpscript/PHPUICodeExpressionHandler::getArgumentCode.
			 *
			 * 2019-11-28: 
			 * By default the $arg already contains the right number of slashes. The only missing slash is the ". 
			 * 
			 * Do not add the addslashes method bc it will add slashes to a bunch of chars that we do not want!
			 * Do not add the addcslashes($arg, '\\"') otherwise this will add an extra \\ to all the other \\. 
			 * Do add slash (\\) to the addcslashes method otherwise the escaped vars (like \$xxx) will be converted in real php vars (\\$xxx). 
			 * In case exists a case with an escaped double quote (this is, a back slash and quote like '\"'), the addcslashes($arg, '"') will convert it to a wrong code like: '\\"', which will give a php error because the $arg = "\\"". So we need to use the TextSanitizer::addCSlashes method instead, bc covers this case. 
			 * This case can happen when we are creating the ui files automatically through create_presentation_uis_diagram_files.php in the loadPageWithNewNavigation javascript function, which contains the following code:
			 * 		eval("url = decodeURI(url).replace(/" + page_attr_name + "=[^&]+/gi, \"\");");
			 * If we use the addcslashes($arg, '"'), we will have the code:
			 * 		eval(\"url = decodeURI(url).replace(/\" + page_attr_name + \"=[^&]+/gi, \\"\\");\");
			 * 	which is wrong be '\\"\\"' are not escaped.
			 * If we use the TextSanitizer::addCSlashes($arg, '"'), we will have:
			 * 		eval(\"url = decodeURI(url).replace(/\" + page_attr_name + \"=[^&]+/gi, \\\"\\\");\");
			 * 	which is correct: '\\\"\\\"'
			 * This means that we cannot use at all the addcslashes or addslashes method and that we must use the TextSanitizer::addCSlashes method!
			 * //Please do not add the addcslashes($variable, '\\"') otherwise it will create an extra \\. The correct is without the \\, because yo are editing php code directly.
			 * return "\"" . addcslashes($variable, '"') . "\""; //DO NOT UNCOMMENT THIS LINE!!! Do not use addcslashes method bc of the reasons below!
			 * 
			 * Note: 3rd argument must be true, bc the php vars should be escaped, otherwise gives a php error, this is:
			 * 	example: 
			 * 		'"' . self::addCharSlashes('this is a simple phrase with double quotes " and the var {$person["name"]}!', '"') . '"'
			 * 	if true, returns:
			 *		'"this is a simple phrase with double quotes \" and the var {$person["name"]}!"'
			 * 	if false, returns:
			 *		'"this is a simple phrase with double quotes \" and the var {$person[\"name\"]}!"'
			 *		...which will return a php error, bc what is inside of {$...} will be executed first in php!
			 */
			return '"' . TextSanitizer::addCSlashes($variable, '"', true) . '"';//Please do not add the addcslashes($variable, '\\"') otherwise it will create an extra \\. The correct is without the \\, because yo are editing php code directly.
		}
		else if (!$type && strlen($v) == 0)
			return "null";
		else if (!$type && substr($v, 0, 5) == '<?php' && substr($v, -2) == '?>')
			return trim(substr($v, 5, -2));
		else if (!$type && substr($v, 0, 3) == '<?=' && substr($v, -2) == '?>')
			return trim(substr($v, 3, -2));
		else if (!$type && substr($v, 0, 2) == '<?' && substr($v, -2) == '?>')
			return trim(substr($v, 2, -2));
		else
			return $v;
	}
	
	protected static function getPropertiesResultVariableCode($properties, $show_operator = true) {
		$var_name = isset($properties["result_var_name"]) ? $properties["result_var_name"] : null;
		$var_assignment = isset($properties["result_var_assignment"]) ? $properties["result_var_assignment"] : null;
		$obj_name = isset($properties["result_obj_name"]) ? $properties["result_obj_name"] : null;
		$prop_name = isset($properties["result_prop_name"]) ? $properties["result_prop_name"] : null;
		$static = isset($properties["result_static"]) ? $properties["result_static"] : null;
		$prop_assignment = isset($properties["result_prop_assignment"]) ? $properties["result_prop_assignment"] : null;
		$echo = isset($properties["result_echo"]) ? $properties["result_echo"] : null;
		$return = isset($properties["result_return"]) ? $properties["result_return"] : null;
		$type = isset($properties["result_var_type"]) ? $properties["result_var_type"] : null;
		
		return self::getResultVariableCode($var_name, $var_assignment, $obj_name, $prop_name, $static, $prop_assignment, $echo, $return, $type, $show_operator);
	}
	
	protected static function getResultVariableCode($var_name, $var_assignment, $obj_name, $prop_name, $static, $prop_assignment, $echo = null, $return = null, $type = null, $show_operator = true) {
		$var_name = isset($var_name) ? trim($var_name) : $var_name;
		$obj_name = isset($obj_name) ? trim($obj_name) : $obj_name;
		$prop_name = isset($prop_name) ? trim($prop_name) : $prop_name;
		
		if ( (isset($type) && $type == "variable") || (!isset($type) && $var_name) ) {
			if (empty($var_name)) {
				return null;
			}
			
			$operator = self::getVariableAssignmentOperator($var_assignment);
			
			return (substr($var_name, 0, 1) != '$' && substr($var_name, 0, 2) != '@$' ? '$' : '') . $var_name . ($show_operator ? " $operator " : "");
		}
		else if ( (isset($type) && $type == "obj_prop") || (!isset($type) && $obj_name && $prop_name) ) {
			if (empty($obj_name) || empty($prop_name)) {
				return null;
			}
			
			$operator = self::getVariableAssignmentOperator($prop_assignment);
			
			if ($static) {
				return $obj_name . '::' . (substr($prop_name, 0, 1) != '$' && substr($prop_name, 0, 2) != '@$' ? '$' : '') . $prop_name . ($show_operator ? " $operator " : "");
			}
			else {
				return (substr($obj_name, 0, 1) != '$' && substr($obj_name, 0, 2) != '@$' ? '$' : '') . $obj_name . '->' . $prop_name . ($show_operator ? " $operator " : "");
			}
		}
		else if (!empty($echo)) {
			return "echo ";
		}
		else if (!empty($return)) {
			return "return ";
		}
		
		return null;
	}
	
	protected static function getPropertiesIncludeFileCode($properties) {
		if (!empty($properties["include_file_path"])) {
			$var_name = self::getVariableValueCode($properties["include_file_path"], isset($properties["include_file_path_type"]) ? $properties["include_file_path_type"] : null);
			
			if ($var_name)
				return "include" . (!empty($properties["include_once"]) ? "_once" : "") . " $var_name;";
		}
		
		return null;
	}
	
	protected static function parseResultVariableProperties($raw_data, $properties = array()) {
		$props = array(
			"result_var_type" => isset($raw_data["childs"]["properties"][0]["childs"]["result_var_type"][0]["value"]) ? $raw_data["childs"]["properties"][0]["childs"]["result_var_type"][0]["value"] : null,
			"result_var_name" => isset($raw_data["childs"]["properties"][0]["childs"]["result_var_name"][0]["value"]) ? $raw_data["childs"]["properties"][0]["childs"]["result_var_name"][0]["value"] : null,
			"result_var_assignment" => isset($raw_data["childs"]["properties"][0]["childs"]["result_var_assignment"][0]["value"]) ? $raw_data["childs"]["properties"][0]["childs"]["result_var_assignment"][0]["value"] : null,
			"result_obj_name" => isset($raw_data["childs"]["properties"][0]["childs"]["result_obj_name"][0]["value"]) ? $raw_data["childs"]["properties"][0]["childs"]["result_obj_name"][0]["value"] : null,
			"result_prop_name" => isset($raw_data["childs"]["properties"][0]["childs"]["result_prop_name"][0]["value"]) ? $raw_data["childs"]["properties"][0]["childs"]["result_prop_name"][0]["value"] : null,
			"result_static" => isset($raw_data["childs"]["properties"][0]["childs"]["result_static"][0]["value"]) ? $raw_data["childs"]["properties"][0]["childs"]["result_static"][0]["value"] : null,
			"result_prop_assignment" => isset($raw_data["childs"]["properties"][0]["childs"]["result_prop_assignment"][0]["value"]) ? $raw_data["childs"]["properties"][0]["childs"]["result_prop_assignment"][0]["value"] : null,
			"result_echo" => isset($raw_data["childs"]["properties"][0]["childs"]["result_echo"][0]["value"]) ? $raw_data["childs"]["properties"][0]["childs"]["result_echo"][0]["value"] : null,
			"result_return" => isset($raw_data["childs"]["properties"][0]["childs"]["result_return"][0]["value"]) ? $raw_data["childs"]["properties"][0]["childs"]["result_return"][0]["value"] : null,
		);
		
		return array_merge($props, $properties);
	}
	
	protected static function parseIncludeFileProperties($raw_data, $properties = array()) {
		$props = array(
			"include_file_path" => isset($raw_data["childs"]["properties"][0]["childs"]["include_file_path"][0]["value"]) ? $raw_data["childs"]["properties"][0]["childs"]["include_file_path"][0]["value"] : null,
			"include_file_path_type" => isset($raw_data["childs"]["properties"][0]["childs"]["include_file_path_type"][0]["value"]) ? $raw_data["childs"]["properties"][0]["childs"]["include_file_path_type"][0]["value"] : null,
			"include_once" => isset($raw_data["childs"]["properties"][0]["childs"]["include_once"][0]["value"]) ? $raw_data["childs"]["properties"][0]["childs"]["include_once"][0]["value"] : null,
		);
		
		return array_merge($props, $properties);
	}
	
	public function setTaskClassInfo($task_class_info) { $this->task_class_info = $task_class_info;}
	public function getTaskClassInfo() { return $this->task_class_info;}
	
	public function isLoopTask() { return $this->is_loop_task; }
	public function isReturnTask() { return $this->is_return_task; }
	public function isBreakTask() { return $this->is_break_task; }
	
	public function setPriority($priority) { $this->priority = $priority; }
	public function getPriority() { return $this->priority; }
}
?>
