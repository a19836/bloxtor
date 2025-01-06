<?php
include_once get_lib("org.phpframework.workflow.WorkFlowTaskHandler");
include_once get_lib("org.phpframework.layer.presentation.cms.CMSFileHandler");

ini_set('xdebug.max_nesting_level', 2000);

class WorkFlowTaskCodeParser {
	private $WorkFlowTaskHandler;
	private $with_comments;
	private $tasks_settings;
	private $available_statements;
	private $reserved_static_method_class_names;
	private $reserved_object_method_names;
	private $reserved_function_names;
	
	private $PHPMultipleParser;
	private $PHPParserTraverser;
	private $PHPParserPrettyPrinter;
	
	private $default_task_code;
	
	public $shared_objects;
	
	public function __construct($WorkFlowTaskHandler, $with_comments = true) {
		$this->WorkFlowTaskHandler = $WorkFlowTaskHandler;
		$this->with_comments = $with_comments;
		
		$this->PHPMultipleParser = new PHPMultipleParser();
		$this->PHPParserTraverser = new PHPParserNodeTraverser;
		$this->PHPParserPrettyPrinter = new PHPParserPrettyPrinter();
		$this->PHPParserPrettyPrinter->disableNoIndentToken(); //very important, otherwise it will add a weird code like an _NO_INDENT_ string.
		
		$this->PHPParserTraverser->addNodeTraverserVisitor(new PHPParserTraverserNodeVisitor);
		
		$this->init();
	}
	
	public function init() {
		if (empty($this->WorkFlowTaskHandler->getLoadedTasksSettings())) {
			$this->WorkFlowTaskHandler->initWorkFlowTasks();
		}
		
		$this->tasks_settings = $this->WorkFlowTaskHandler->getLoadedTasksSettings();
		
		$this->available_statements = $this->getConfiguredAvailableStatements();
		$this->reserved_static_method_class_names = $this->getConfiguredReservedStaticMethodClassNames();
		$this->reserved_object_method_names = $this->getConfiguredReservedObjectMethodNames();
		$this->reserved_function_names = $this->getConfiguredReservedFunctionNames();
		
		$this->default_task_code = $this->WorkFlowTaskHandler->getTasksByTag("code", 1);
		$this->default_task_code = $this->default_task_code && isset($this->default_task_code[0]) ? $this->default_task_code[0] : null;
		
		$this->shared_objects = array();
	}
	
	public function withComments() {
		return $this->with_comments;
	}
	
	public function getParsedCodeAsXml($code) {
		$tasks = $this->getParsedCodeAsArray($code);
		
		$MyXMLArray = new MyXMLArray($tasks);
		$xml = $MyXMLArray->toXML(array("lower_case_keys" => true, "prefix_tab" => "\t"));
		$xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<tasks>" . $xml . "\n</tasks>";
		//echo $xml;
		
		return $xml;
	}
	
	public function getParsedCodeAsArray($code) {
		$stmts = $this->PHPMultipleParser->parse($code);
		//print_r($stmts);die();
		
		$stmts = $this->PHPParserTraverser->nodesTraverse($stmts);
		//print_r($stmts);die();
		
		$tasks_properties = WorkFlowTask::createTasksPropertiesFromCodeStmts($stmts, $this);
		$tasks_properties = $this->cleanInvalidExitsFromTasks($tasks_properties);
		//print_r($tasks_properties);die();
		
		$start_count = 1;
		if (!empty($tasks_properties[0])) {
			$tasks_properties[0]["start"] = $start_count;
		}
		
		/*$t = count($tasks_properties);
		for ($i = 0; $i < $t; $i++) {
			if (!empty($tasks_properties[$i]["is_return"]) && $i + 1 < $t && !empty($tasks_properties[$i + 1][""])) {
				$tasks_properties[$i + 1]["start"] = ++$start_count;
			}
		}*/
		
		$tasks_properties = array("task" => $tasks_properties);
		$tasks_properties = MyXML::basicArrayToComplexArray($tasks_properties, array("lower_case_keys" => true));
		//print_r($tasks_properties);
		
		return $tasks_properties;
	}
	
	public function createUndefinedXMLTask($stmts, $exits = null) {
		$code = "";
		$code_label = "";
		
		if ($this->default_task_code) {
			$prop_exits = "";
			
			$t = $stmts ? count($stmts) : 0;
			for ($i = 0; $i < $t; $i++) {
				$stmt = $stmts[$i];
				$props = $this->default_task_code["obj"]->createTaskPropertiesFromCodeStmt($stmt, $this);
				
				if (is_array($props) && !empty($props) && $this->with_comments) {
					$comments = $this->printComments($stmt);
					
					if ($comments) {
						if (!empty($props["comments"])) {
							if (strpos($props["comments"], $comments) === false) //avoid repeated comments
								$props["comments"] = $comments . "\n" . $props["comments"];
						}
						else
							$props["comments"] = $comments;
					}
				}
				
				if (!empty($props["code"])) {
					$comments = !empty($props["comments"]) ? $props["comments"] : "";
					$code .= ($code ? "\n" : "") . ($comments ? $comments . "\n" : "") . $props["code"];
					$code_label = substr(str_replace(array("<?php", "<?", "?>"), "", $props["code"]), 0, 20) . "...";
				}
				
				if (!$prop_exits) {
					$prop_exits = isset($props["exits"]) ? $props["exits"] : null;
				}
			}
			
			$is_loop = $this->default_task_code["obj"]->isLoopTask();
			$is_return = $this->default_task_code["obj"]->isReturnTask();
			$is_break = $this->default_task_code["obj"]->isBreakTask();
		}
		else {
			$code = $this->PHPParserPrettyPrinter->stmtsPrettyPrint($stmts, $this->with_comments);
			$code_label = substr(str_replace(array("<?php", "<?", "?>"), "", $this->PHPParserPrettyPrinter->stmtsPrettyPrint($stmts)), 0, 20) . "...";
			$prop_exits = array(
				WorkFlowTask::DEFAULT_EXIT_ID => array(
					"color" => "#000",
				)
			);
			
			$is_loop = false;
			$is_return = false;
			$is_break = false;
		}
		
		$task = $this->default_task_code;
		$task_settings = $task && isset($task["type"]) ? $this->getTaskSettingsByTaskType($task["type"]) : null;
		
		if (!$task_settings) {
			$task_settings = array(
				"label" => "Undefined",
				"tag" => "undefined",
			);
			
			if (empty($task))
				$task = array();
			
			$task["type"] = "undefined";
		}
		
		$xml_task = array(
			"id" => $task["type"] . "_" . hash("crc32b", $code) . "_" . rand(0, 1000),
			"label" => "Code: " . $code_label,
			"type" => $task["type"],
			"tag" => $task_settings["tag"],
			"is_loop" => $is_loop,
			"is_return" => $is_return,
			"is_break" => $is_break,
			"properties" => array(
				"exits" => $prop_exits,
				"code" => $code,
			),
		);
		
		if ($exits) {
			$xml_task["exits"] = $exits;
		}
		
		return $xml_task;
	}
	
	public function createXMLTask($task, $props, $exits = null) {
		$is_loop = $task["obj"]->isLoopTask();
		$is_return = $task["obj"]->isReturnTask();
		$is_break = $task["obj"]->isBreakTask();
		unset($task["obj"]);
		
		$task_type = isset($task["type"]) ? $task["type"] : null;
		$task_settings = $this->getTaskSettingsByTaskType($task_type);
		
		if (!$task_settings) {
			return null;
		}
		
		$xml_task = array(
			"id" => !empty($props["id"]) ? $props["id"] : $task_type . "_" . hash("crc32b", serialize($props)) . "_" . rand(0, 1000),
			"type" => $task_type,
			"tag" => isset($task_settings["tag"]) ? $task_settings["tag"] : null,
			"is_loop" => $is_loop,
			"is_return" => $is_return,
			"is_break" => $is_break,
		);
		
		if (!empty($props["label"])) {
			$xml_task["label"] = $props["label"];
			unset($props["label"]);
		}
		else {
			$xml_task["label"] = isset($task_settings["label"]) ? $task_settings["label"] : null;
		}
		
		if (empty($props["exits"]) && !$is_return && !$is_break)
		//if (empty($props["exits"]) && !$is_return)
			$props["exits"][WorkFlowTask::DEFAULT_EXIT_ID] = array();
		
		unset($props["id"]);
		$xml_task["properties"] = $props;
		
		if ($exits) {
			$xml_task["exits"] = $exits;
		}
		
		return $xml_task;
	}
	
	//This is only for internal usage, like a connector for the cases like: $x = $y == "as" ? true : false;
	public function createConnectorXMLTask($exits = null) {
		$xml_task = array(
			"id" => "connector_" . rand(0, 1000) . rand(0, 1000),
			"type" => WorkFlowTask::CONNECTOR_TASK_TYPE,
			"is_loop" => false,
			"is_return" => false,
			"is_break" => false,
			"properties" => array(
				"exits" => array(
					WorkFlowTask::DEFAULT_EXIT_ID => array(),
				),
			),
		);
		
		if ($exits) {
			$xml_task["exits"] = $exits;
		}
		
		return $xml_task;
	}
	
	public function replaceNextTaskInNotBreakTasksExits(&$inner_tasks, $next_task = false) {
		if ($inner_tasks)
			foreach ($inner_tasks as $idx => $task)
				if (empty($task["is_break"]))
					$this->replaceNextTaskInTaskExits($inner_tasks[$idx], $next_task);
	}
	
	public function replaceNextTaskInTaskExits(&$current_task, $next_task) {
		if ($current_task && !empty($next_task["id"])) {
			$exits = isset($current_task["properties"]["exits"]) ? $current_task["properties"]["exits"] : null;
		
			if (is_array($exits))
				foreach ($exits as $exit_id => $exit) 
					if (!empty($current_task["exits"][$exit_id])) {
						if (isset($current_task["exits"][$exit_id]["task_id"]))
							$current_task["exits"][$exit_id] = array($current_task["exits"][$exit_id]);
						
						$t = count($current_task["exits"][$exit_id]);
						for ($i = 0; $i < $t; $i++) 
							if (isset($current_task["exits"][$exit_id][$i]["task_id"]) && $current_task["exits"][$exit_id][$i]["task_id"] == "#next_task#") 
								$current_task["exits"][$exit_id][$i]["task_id"] = $next_task["id"];
					}
		}
	}
	
	public function addNextTaskToAllTaskExits(&$current_task, $next_task = false) {
		$next_task_id = !empty($next_task["id"]) ? $next_task["id"] : "#next_task#";
		
		if ($current_task) {
			$exits = isset($current_task["properties"]["exits"]) ? $current_task["properties"]["exits"] : null;
			
			if (is_array($exits))
				foreach ($exits as $exit_id => $exit) {
					if (isset($current_task["exits"][$exit_id]["task_id"]))
						$current_task["exits"][$exit_id] = array($current_task["exits"][$exit_id]);
					
					$current_task["exits"][$exit_id][] = array("task_id" => $next_task_id);
				}
			else
				$current_task["exits"][WorkFlowTask::DEFAULT_EXIT_ID][] = array("task_id" => $next_task_id);
		}
	}
	
	public function addNextTaskToUndefinedTaskExits(&$current_task, $next_task = false) {
		$next_task_id = !empty($next_task["id"]) ? $next_task["id"] : "#next_task#";
		
		//if ($current_task && !$current_task["is_return"]) {
		if ($current_task && empty($current_task["is_return"]) && empty($current_task["is_break"])) {
			$exits = isset($current_task["properties"]["exits"]) ? $current_task["properties"]["exits"] : null;
			
			if (is_array($exits)) {
				foreach ($exits as $exit_id => $exit)
					if (!isset($current_task["exits"][$exit_id]["task_id"]) && !isset($current_task["exits"][$exit_id][0]["task_id"]))
						$current_task["exits"][$exit_id][0] = array("task_id" => $next_task_id);
			}
			else
				$current_task["exits"][WorkFlowTask::DEFAULT_EXIT_ID][] = array("task_id" => $next_task_id);
		}
	}
	
	//The tasks inside of a loop should NOT be connected to any other tasks outside of the loop.
	//This function makes sure that the last inner task will never be added to any other task and that the workflow ends here for this block.
	//The IS_RETURN attribute makes sure that the following case will never happen: if/switch task has a loop task inside and then the if/switch are connected to some other tasks outside of the if/switch.
	public function stopLoopInnerTasksToBeConnectedToOtherOutsideTasks($inner_tasks) {
		if ($inner_tasks && empty($inner_tasks[count($inner_tasks) - 1]["is_break"])) 
			$inner_tasks[count($inner_tasks) - 1]["is_return"] = true; //if last task doesn't have this property the system will add a new exit and relate it to the next task. WE DON?T WANT THAT!
		
		return $inner_tasks;
	}
	
	public function cleanInvalidExitsFromTasks($inner_tasks) {
		$t = $inner_tasks ? count($inner_tasks) : 0;
		for ($i = 0; $i < $t; $i++)
			$inner_tasks[$i] = $this->cleanInvalidExitsFromTask($inner_tasks[$i]);
		
		return $inner_tasks;
	}
	
	public function cleanInvalidExitsFromTask($task) {
		$exits = isset($task["exits"]) ? $task["exits"] : null;
		
		if (is_array($exits)) {
			foreach ($exits as $exit_id => $items) {
				if (isset($items["task_id"]))
					$items = array($items);
				
				$new_items = array();
				$t = $items ? count($items) : 0;
				for ($j = 0; $j < $t; $j++) {
					$task_id = isset($items[$j]["task_id"]) ? $items[$j]["task_id"] : null;
					
					if ($task_id != "#next_task#")
						$new_items[] = $items[$j];
				}
				
				$task["exits"][$exit_id] = $new_items;
			}
		}
		
		return $task;
	}
	
	public function convertStmtExpressionToSimpleStmt($stmt) {
		if ($stmt && is_object($stmt) && method_exists($stmt, "getType") && strtolower($stmt->getType()) == "stmt_expression" && isset($stmt->expr)) {
			if ($stmt->hasAttribute("comments")) {
				$comments = $stmt->expr->getAttribute("comments");
				
				if (!$comments)
					$comments = array();
				
				if ($stmt->getAttribute("comments"))
					$comments = array_merge($stmt->getAttribute("comments"), $comments);
				
				$stmt->expr->setAttribute("comments", $comments);
			}
			
			if ($stmt->hasAttribute("my_comments")) {
				$comments = $stmt->expr->getAttribute("my_comments");
				
				if (!$comments)
					$comments = array();
				
				if ($stmt->getAttribute("my_comments"))
					$comments = array_merge($stmt->getAttribute("my_comments"), $comments);
				
				$stmt->expr->setAttribute("my_comments", $comments);
			}
			
			$stmt = $stmt->expr;
		}
		
		return $stmt;
	}
	
	public function convertStmtsExpressionToSimpleStmts($stmts) {
		if ($stmts)
			foreach ($stmts as $idx => $stmt)
				$stmts[$idx] = $this->convertStmtExpressionToSimpleStmt($stmt);
		
		return $stmts;
	}
	
	public function printComments($stmt) {
		if ($stmt->hasAttribute("my_comments")) {
			$comments = $stmt->getAttribute("my_comments");
			
			if ($comments) {
				$str = $this->PHPParserPrettyPrinter->printComments($comments);
				$str = preg_replace("/(^|\n)\s*\/\/\s*/", "\n", $str); //clean '//'
				$str = preg_replace("/((^|\n)\s*\/\*+|\n\s*\*+\/|\*+\/\s*$|\n\s*\*+\s*)/", "\n", $str); //clean '/*', ' *' and '*/'
				$str = preg_replace("/\s*\n\s*+/", "\n", $str); //clean extra spaces
				$str = trim($str);//trim all lines
				
				return $str;
			}
		}
		
		return "";
	}
	
	public function printCodeStatement($stmt, $with_comments = null) {
		return $this->PHPParserPrettyPrinter->stmtsPrettyPrint(array($stmt), is_bool($with_comments) ? $with_comments : $this->with_comments);
	}
	
	public function printCodeExpr($expr, $with_comments = null) {
		return $this->PHPParserPrettyPrinter->nodeExprPrettyPrint($expr, is_bool($with_comments) ? $with_comments : $this->with_comments);
	}
	
	public function printCodeNodeName($node) {
		if (is_object($node)) {
			if (isset($node->name) && is_object($node->name)) {
				$node_type = strtolower($node->name->getType()); //JP: getType added in 19-07-2019
				
				if ($node_type == "name")
					return $this->PHPParserPrettyPrinter->printName($node->name);
				else if ($node_type == "identifier") //JP: Identifier type added in 20-09-2024
					return $this->PHPParserPrettyPrinter->printIdentifier($node->name);
			}
			
			if (isset($node->parts) && is_array($node->parts))
				return $this->PHPParserPrettyPrinter->printName($node);
			
			return isset($node->name) ? $node->name : null;
		}
		return $node;
	}
	
	public function getVariableName($stmt) {
		$var = isset($stmt->var) ? $stmt->var : null;
		$var_type = strtolower($var->getType());
		
		if ($var_type == "expr_propertyfetch" || $var_type == "expr_staticpropertyfetch") {
			if ($var_type == "expr_staticpropertyfetch") {
				$obj_name = isset($var->class->name) ? $var->class->name : null;
			
				if (!$obj_name && isset($var->class->parts) && is_array($var->class->parts))
					$obj_name = implode("::", $var->class->parts);
			}
			else
				$obj_name = $this->printCodeExpr(isset($var->var) ? $var->var : null, false);
			
			if (isset($var->name) && is_object($var->name)) {
				$prop_name = $this->printCodeExpr($var->name, false);
				$prop_name = substr($prop_name, 0, 1) == '$' ? substr($prop_name, 1) : (substr($prop_name, 0, 2) == '@$' ? substr($prop_name, 2) : $prop_name);
			}
			else
				$prop_name = isset($var->name) ? $var->name : null;
			
			if ($obj_name && $prop_name)
				return array(
					"obj_name" => $obj_name,
					"prop_name" => $prop_name,
					"static" => $var_type == "expr_staticpropertyfetch" ? 1 : 0,
				);
		}
		else if ($var_type == "expr_variable") {
			$var_name = isset($var->name) ? $var->name : null;
			
			if ($var_name)
				return array(
					"var_name" => $var_name,
				);
		}
		else if ($var_type == "expr_arraydimfetch") {
			$var_name = $this->printCodeExpr($var, false);
			
			if ($var_name) {
				$var_name = substr($var_name, 0, 1) == '$' ? substr($var_name, 1) : (substr($var_name, 0, 2) == '@$' ? substr($var_name, 2) : $var_name);
				
				return array(
					"var_name" => $var_name,
				);
			}
		}
		
		return null;
	}
	
	public function getAssignmentType($stmt) {
		$stmt_type = strtolower($stmt->getType());
		
		return $stmt_type == "expr_assignop_concat" ? "concatenate" : ($stmt_type == "expr_assignop_plus" ? "increment" : ($stmt_type == "expr_assignop_minus" ? "decrement" : ""));
	}
	
	public function getVariableNameProps($stmt) {
		$stmt = $this->convertStmtExpressionToSimpleStmt($stmt);
		$props = null;
		
		if (strtolower($stmt->getType()) == "stmt_echo") {
			$props = array(
				"result_echo" => true,
			);
		}
		else if (strtolower($stmt->getType()) == "stmt_return") {
			$props = array(
				"result_return" => true,
			);
		}
		else {
			$var_name = $this->getVariableName($stmt);
			
			$var = isset($stmt->var) ? $stmt->var : null;
			$var_type = strtolower($var->getType());
		
			if ($var_type == "expr_propertyfetch" || $var_type == "expr_staticpropertyfetch") {
				$obj_name = isset($var_name["obj_name"]) ? $var_name["obj_name"] : null;
				$prop_name = isset($var_name["prop_name"]) ? $var_name["prop_name"] : null;
				
				if ($obj_name && $prop_name) {
					$props = array(
						"result_obj_name" => $obj_name,
						"result_prop_name" => $prop_name,
						"result_static" => $var_type == "expr_staticpropertyfetch" ? 1 : 0,
						"result_prop_assignment" => self::getAssignmentType($stmt),
					);
				}
			}
			else {
				$var_name = isset($var_name["var_name"]) ? $var_name["var_name"] : null;
			
				if ($var_name) {
					$props = array(
						"result_var_name" => $var_name,
						"result_var_assignment" => self::getAssignmentType($stmt),
					);
				}
			}
		}
		
		$props["comments"] = $this->printComments($stmt);
		
		return $props;
	}
	
	public function isAssignExpr($stmt) {
		$stmt_type = strtolower($stmt->getType());
		
		return $stmt_type == "expr_assign" || $stmt_type == "expr_assignop_concat" || $stmt_type == "expr_assignop_plus" || $stmt_type == "expr_assignop_minus";
	}
	
	public function getFunctionProps($stmt) {
		$stmt = $this->convertStmtExpressionToSimpleStmt($stmt);
		$stmt_type = strtolower($stmt->getType());
		
		if ($this->isAssignExpr($stmt) || ($stmt_type == "stmt_echo" && isset($stmt->exprs) && count($stmt->exprs) == 1 && !$this->isAssignExpr($stmt->exprs[0]))) {
			//print_r($stmt);
			$expr = $stmt_type == "stmt_echo" ? (isset($stmt->exprs[0]) ? $stmt->exprs[0] : null) : (isset($stmt->expr) ? $stmt->expr : null);
			$expr_type = $expr ? strtolower($expr->getType()) : "";
			
			if ($expr_type == "expr_funccall") {
				$props = $this->getVariableNameProps($stmt);
				
				if ($props) {
					$func_name = $this->printCodeNodeName($expr);
					$args = $this->getArgs(isset($expr->args) ? $expr->args : null);
				
					if ($func_name) {
						$props["func_name"] = $func_name;
						$props["func_args"] = $args;
						$props["label"] = "Call $func_name()";
						$props["comments"] = $this->printComments($stmt);
						
						return $props;
					}
				}
			}
		}
		else if ($stmt_type == "expr_funccall") {
			$func_name = $this->printCodeNodeName($stmt);
			$args = $this->getArgs(isset($stmt->args) ? $stmt->args : null);
			
			if ($func_name) {
				$props = array(
					"func_name" => $func_name,
					"func_args" => $args,
					"label" => "Call $func_name()",
					"comments" => $this->printComments($stmt)
				);
				
				return $props;
			}
		}
	}
	
	public function getObjectMethodProps($stmt) {
		$stmt = $this->convertStmtExpressionToSimpleStmt($stmt);
		$stmt_type = strtolower($stmt->getType());
		
		if ($this->isAssignExpr($stmt) || ($stmt_type == "stmt_echo" && isset($stmt->exprs) && count($stmt->exprs) == 1 && !$this->isAssignExpr($stmt->exprs[0]))) {
			//print_r($stmt);
			$expr = $stmt_type == "stmt_echo" ? (isset($stmt->exprs[0]) ? $stmt->exprs[0] : null) : (isset($stmt->expr) ? $stmt->expr : null);
			$expr_type = $expr ? strtolower($expr->getType()) : "";
			
			if ($expr_type == "expr_methodcall" || $expr_type == "expr_staticcall") {
				$props = $this->getVariableNameProps($stmt);
				
				if ($props) {
					if ($expr_type == "expr_staticcall") {
						$obj_name = $this->printCodeNodeName(isset($expr->class) ? $expr->class : null);
					}
					else {
						$obj_name = $this->printCodeExpr(isset($expr->var) ? $expr->var : null, false);
					}
					
					$method_name = $this->printCodeNodeName($expr);
					
					if ($obj_name && $method_name) {
						$args = $this->getArgs(isset($expr->args) ? $expr->args : null);
					
						$props["method_obj"] = $obj_name;
						$props["method_name"] = $method_name;
						$props["method_args"] = $args;
						$props["method_static"] = $expr_type == "expr_staticcall" ? 1 : 0;
						$props["label"] = "Call $obj_name" . ($expr_type == "expr_staticcall" ? "::" : "->") . "$method_name(...)";
						$props["comments"] = $this->printComments($stmt);
						
						return $props;
					}
				}
			}
		}
		else if ($stmt_type == "expr_methodcall" || $stmt_type == "expr_staticcall") {
			if ($stmt_type == "expr_staticcall")
				$obj_name = $this->printCodeNodeName(isset($stmt->class) ? $stmt->class : null);
			else
				$obj_name = $this->printCodeExpr(isset($stmt->var) ? $stmt->var : null, false);
			
			$method_name = $this->printCodeNodeName($stmt);
			
			if ($obj_name && $method_name) {
				$args = $this->getArgs(isset($stmt->args) ? $stmt->args : null);
				
				$props = array(
					"method_obj" => $obj_name,
					"method_name" => $method_name,
					"method_args" => $args,
					"method_static" => $stmt_type == "expr_staticcall" ? 1 : 0,
					"label" => "Call $obj_name" . ($stmt_type == "expr_staticcall" ? "::" : "->") . "$method_name(...)",
					"comments" => $this->printComments($stmt)
				);
				
				return $props;
			}
		}
	}
	
	public function getNewObjectProps($stmt) {
		$stmt = $this->convertStmtExpressionToSimpleStmt($stmt);
		$stmt_type = strtolower($stmt->getType());
		
		if ($this->isAssignExpr($stmt) || ($stmt_type == "stmt_echo" && isset($stmt->exprs) && count($stmt->exprs) == 1 && !$this->isAssignExpr($stmt->exprs[0]))) {
			//print_r($stmt);
			$expr = $stmt_type == "stmt_echo" ? (isset($stmt->exprs[0]) ? $stmt->exprs[0] : null) : (isset($stmt->expr) ? $stmt->expr : null);
			$expr_type = $expr ? strtolower($expr->getType()) : "";
			
			if ($expr_type == "expr_new") {
				$props = $this->getVariableNameProps($stmt);
			
				if ($props) {
					$class_name = $this->printCodeNodeName(isset($expr->class) ? $expr->class : null);
					$args = $this->getArgs(isset($expr->args) ? $expr->args : null);
				
					if ($class_name) {
						$props["class_name"] = $class_name;
						$props["class_args"] = $args;
						$props["label"] = "Create $class_name obj";
						$props["comments"] = $this->printComments($stmt);
					
						return $props;
					}
				}
			}
		}
		else if ($stmt_type == "expr_new") {
			$class_name = $this->printCodeNodeName(isset($stmt->class) ? $stmt->class : null);
			$args = $this->getArgs(isset($stmt->args) ? $stmt->args : null);
			
			if ($class_name) {
				$props = array(
					"class_name" => $class_name,
					"class_args" => $args,
					"label" => "Create $class_name obj",
					"comments" => $this->printComments($stmt)
				);
				
				return $props;
			}
		}
	}
	
	public function getArgs($stmt_args) {
		$args = array();
		
		if (is_array($stmt_args)) {
			$t = count($stmt_args);
			for ($i = 0; $i < $t; $i++) {
				$arg = $stmt_args[$i];
				
				$arg_type = strtolower($arg->value->getType());
				
				$value = $this->PHPParserPrettyPrinter->printArg($arg);
				$value = $this->getStmtValueAccordingWithType($value, $arg_type);
				
				$value_type = $this->getStmtType(isset($arg->value) ? $arg->value : null);
				
				$args[] = array(
					"value" => $value,
					"type" => $value_type,
				);
			}
		}
		
		return $args;
	}
	
	public function getArrayItems($items) {
		$props = array();
		
		$t = $items ? count($items) : 0;
		for ($i = 0; $i < $t; $i++) {
			$item = $items[$i];
			
			$key = isset($item->key) ? $item->key : null;
			$value = isset($item->value) ? $item->value : null;
			
			$key_type = is_object($key) ? strtolower($key->getType()) : null;
			$value_type = is_object($value) ? strtolower($value->getType()) : null;
			
			if ($key_type) {
				$key = $this->printCodeExpr($key, false);
				$key = $this->getStmtValueAccordingWithType($key, $key_type);
				
				$key_type = $this->getStmtType($item->key);
			}
			else {
				$key = null;
			}
			
			if ($value_type) {
				if ($value_type == "expr_array") {
					$value = $this->getArrayItems(isset($value->items) ? $value->items : null);
				}
				else {
					$value = $this->printCodeExpr($value, false);
					$value = $this->getStmtValueAccordingWithType($value, $value_type);
				}
				
				$value_type = $this->getStmtType($item->value);
			}
			else {
				$value = null;
			}
			
			$prop = array(
				"key" => $key,
				"key_type" => isset($key) ? $key_type : "null"
			);
			
			if (is_array($value)) {
				$prop["items"] = $value;
			}
			else {
				$prop["value"] = $value;
				$prop["value_type"] = isset($value) ? $value_type : "null";
			}
			
			$props[] = $prop;
		}
		
		return $props;
	}
	
	//Possible cases of left/right:
		//PhpParser\Node\Expr\BinaryOp\BooleanOr
		//PhpParser\Node\Expr\BinaryOp\BooleanAnd
	
		//PhpParser\Node\Expr\BinaryOp\Identical
		//PhpParser\Node\Expr\BinaryOp\NotIdentical
		//PhpParser\Node\Expr\BinaryOp\Equal
		//PhpParser\Node\Expr\BinaryOp\NotEqual
		//PhpParser\Node\Expr\BinaryOp\Greater
		//PhpParser\Node\Expr\BinaryOp\GreaterOrEqual
		//PhpParser\Node\Expr\BinaryOp\Smaller
		//PhpParser\Node\Expr\BinaryOp\SmallerOrEqual
	
		//PhpParser\Node\Expr\Variable => $y
		//PhpParser\Node\Expr\ConstFetch ==> true
		//PhpParser\Node\Scalar\LNumber ==> 1
	
		//PhpParser\Node\Expr\FuncCall
		//PhpParser\Node\Expr\MethodCall
		//PhpParser\Node\Expr\StaticCall
	
		//PhpParser\Node\Expr\Assign
	public function getConditions($cond) {
		$cond = $this->getCondition($cond);
		
		if (isset($cond)) {
			$cond = isset($cond["value"]) ? array("first" => $cond, "operator" => "==", "second" => array("value" => "true", "type" => "")) : $cond;
			
			$cond = array("group" => (isset($cond["join"]) ? $cond : array("join" => "and", "item" => $cond)));
			
			return $cond;
		}
		
		return null;
	}
	
	private function getCondition($cond) {
		$joins = array(
			"expr_binaryop_booleanor" => "or", 
			"expr_binaryop_booleanand" => "and", 
		);
		
		$operators = array(
			"expr_binaryop_equal" => "==", 
			"expr_binaryop_notequal" => "!=", 
			"expr_binaryop_greater" => ">", 
			"expr_binaryop_greaterorequal" => ">=", 
			"expr_binaryop_smaller" => "<", 
			"expr_binaryop_smallerorequal" => "<=",
			"expr_binaryop_identical" => "===",
			"expr_binaryop_notidentical" => "!==",
		);
		
		$type = strtolower($cond->getType());
		
		if (isset($operators[$type])) {
			$operator = $operators[$type];
			$left = $this->getCondition($cond->left);
			$right = $this->getCondition($cond->right);
			
			if (!isset($left) || !isset($right)) {
				return null;
			}
			
			$props = array("operator" => $operator);
			
			if (isset($left["join"])) {
				$props["group"] = $left;
			}
			else {
				$props["first"] = $left;
			}
			
			if (isset($right["join"])) {
				$props["group"] = isset($left["join"]) ? array($props["group"], $right) : $right;
			}
			else {
				$props["second"] = $right;
			}
			
			return $props;
		}
		else if (isset($joins[$type])) {
			$join = $joins[$type];
			$left = $this->getCondition($cond->left);
			$right = $this->getCondition($cond->right);
			
			if (!isset($left) || !isset($right)) {
				return null;
			}
			
			$props = array("join" => $join);
			
			if (isset($left["join"])) {
				$props["group"] = $left;
			}
			else {
				$props["item"] = isset($left["value"]) ? array("first" => $left, "operator" => "==", "second" => array("value" => "true", "type" => "")) : $left;
			}
			
			if (isset($right["join"])) {
				$props["group"] = isset($left["join"]) ? array($props["group"], $right) : $right;
			}
			else {
				$right = isset($right["value"]) ? array("first" => $right, "operator" => "==", "second" => array("value" => "true", "type" => "")) : $right;
				$props["item"] = !isset($left["join"]) ? array($props["item"], $right) : $right;
			}
			
			return $props;
		}
		else {
			$value = $this->printCodeExpr($cond, false);
			$value = $this->getStmtValueAccordingWithType($value, $type);
			
			$value_type = $this->getStmtType($cond);
			
			return array(
				"value" => $value,
				"type" => $value_type,
			);
		}
		
		return null;
	}
	
	public function getStmtArrayItems($stmt) {
		if ($stmt) {
			$stmt = $this->convertStmtExpressionToSimpleStmt($stmt);
			
			if ($stmt && isset($stmt->items))
				return $stmt->items;
		}
		
		return null;
	}
	
	public function getStmtType($stmt) {
		$type = strtolower($stmt->getType());
		
		return $type == "scalar_string" || $type == "scalar_encapsed" ? "string" : (
			$type == "expr_variable" ? "variable" : (
			$type == "expr_funccall" ? "function" : (
			$type == "expr_methodcall" || $type == "expr_staticcall" ? "method" : (
			$type == "expr_array" ? "array" : ""
		))));
	}
	
	public function getStmtValueAccordingWithType($value, $value_type) {
		return CMSFileHandler::getStmtValueAccordingWithType($value, $value_type);
	}
	
	//2020-09-13: deprecated bc of issues with multiple backslashes and other issues. Use instead the CMSFileHandler::getStmtValueAccordingWithType
	public function getStmtValueAccordingWithTypeOld($value, $value_type) {
		//echo "$value, $value_type<br>\n";
		if ($value_type == "scalar_string" || $value_type == "scalar_encapsed") {
			$first_char = substr($value, 0, 1);
			
			$value = str_replace('\t', "\t", str_replace('\n', "\n", substr($value, 1, -1)));
			$value = stripslashes($value);
			
			$value = str_replace('$', '\$', $value);
			
			if ($first_char == '"')
				$value = str_replace('{\$', '{$', $value);
		}
		//echo "$value, $value_type<br>\n";
		return $value;
	}
	
	private function getTaskSettingsByTaskType($task_type) {
		if ($task_type && is_array($this->tasks_settings)) { 
			foreach ($this->tasks_settings as $folder_id => $folder_tasks) {
				if (!empty($folder_tasks[$task_type])) {
					return $folder_tasks[$task_type];
				}
			}
		}
		
		return null;
	}
	
	private function getConfiguredAvailableStatements() {
		$statements_to_tasks = array();
		
		if (is_array($this->tasks_settings)) 
			foreach ($this->tasks_settings as $folder_id => $folder_tasks)
				foreach ($folder_tasks as $task_type => $task_settings) 
					if (!empty($task_settings["code_parser"]["statements"])) {
						$t = count($task_settings["code_parser"]["statements"]);
						for ($i = 0; $i < $t; $i++) {
							$stmt = $task_settings["code_parser"]["statements"][$i];
							
							if ($stmt) {
								$task = $this->WorkFlowTaskHandler->getTaskByType($task_type);
								$task_obj = isset($task["obj"]) ? $task["obj"] : null;
								$task_class = isset($task["class"]) ? $task["class"] : null;
								
								if (get_class($task_obj) == $task_class)
									$statements_to_tasks[ strtolower($stmt) ][] = $task;
							}
						}
					}
		
		//Preparing tasks priorities
		foreach ($statements_to_tasks as $stmt_type => $tasks) {
			usort($tasks, function($a, $b) {
				return 
					(!empty($a["obj"]) && !empty($b["obj"])) 
					&& 
					(!$b["obj"]->getPriority() || $a["obj"]->getPriority() > $b["obj"]->getPriority()) 
					? -1 : 1;
			});
			
			$statements_to_tasks[$stmt_type] = $tasks;
		}
		
		return $statements_to_tasks;
	}
	
	private function getConfiguredReservedStaticMethodClassNames() {
		$reserved_static_method_class_names = array();
		
		if (is_array($this->tasks_settings)) { 
			foreach ($this->tasks_settings as $folder_id => $folder_tasks) {
				foreach ($folder_tasks as $task_type => $task_settings) {
					$names = isset($task_settings["code_parser"]["reserved_static_method_class_names"]) ? $task_settings["code_parser"]["reserved_static_method_class_names"] : null;
					
					if (is_array($names)) {
						$reserved_static_method_class_names = array_merge($reserved_static_method_class_names, $names);
					}
				}
			}
		}
		
		return $reserved_static_method_class_names;
	}
	
	private function getConfiguredReservedObjectMethodNames() {
		$reserved_object_method_names = array();
		
		if (is_array($this->tasks_settings)) { 
			foreach ($this->tasks_settings as $folder_id => $folder_tasks) {
				foreach ($folder_tasks as $task_type => $task_settings) {
					$names = isset($task_settings["code_parser"]["reserved_object_method_names"]) ? $task_settings["code_parser"]["reserved_object_method_names"] : null;
					
					if (is_array($names)) {
						$reserved_object_method_names = array_merge($reserved_object_method_names, $names);
					}
				}
			}
		}
		
		return $reserved_object_method_names;
	}
	
	private function getConfiguredReservedFunctionNames() {
		$reserved_function_names = array();
		
		if (is_array($this->tasks_settings)) { 
			foreach ($this->tasks_settings as $folder_id => $folder_tasks) {
				foreach ($folder_tasks as $task_type => $task_settings) {
					$names = isset($task_settings["code_parser"]["reserved_function_names"]) ? $task_settings["code_parser"]["reserved_function_names"] : null;
					
					if (is_array($names)) {
						$reserved_function_names = array_merge($reserved_function_names, $names);
					}
				}
			}
		}
		
		return $reserved_function_names;
	}
	
	public function getAvailableStatements() { return $this->available_statements; }
	public function setAvailableStatements($available_statements) { $this->available_statements = $available_statements; }
	public function addAvailableStatement($stmt_type, $task_types) { 
		$stmt_type = strtolower($stmt_type);
		
		$task_types = is_array($task_types) || !$task_types ? $task_types : array($task_types);
		if ($task_types) {
			$t = count($task_types);
			for ($i = 0; $i < $t; $i++) {
				$task_type = trim($task_types[$i]);
				
				if ($task_type && (empty($this->available_statements[$stmt_type]) || !in_array($task_type, $this->available_statements[$stmt_type])) ) {
					$task = $this->WorkFlowTaskHandler->getTaskByType($task_type);
					$task_obj = isset($task["obj"]) ? $task["obj"] : null;
					$task_class = isset($task["class"]) ? $task["class"] : null;
					
					if (get_class($task_obj) == $task_class)
						$this->available_statements[$stmt_type][] = $task;
				}
			}
		}
	}
	
	public function getReservedStaticMethodClassNames() { return $this->reserved_static_method_class_names; }
	public function setReservedStaticMethodClassNames($reserved_static_method_class_names) { $this->reserved_static_method_class_names = $reserved_static_method_class_names; }
	public function addReservedStaticMethodClassName($reserved_static_method_class_name) { $this->reserved_static_method_class_names[] = $reserved_static_method_class_name; }
	
	public function getReservedObjectMethodNames() { return $this->reserved_object_method_names; }
	public function setReservedObjectMethodNames($reserved_object_method_names) { $this->reserved_object_method_names = $reserved_object_method_names; }
	public function addReservedObjectMethodName($reserved_object_method_name) { $this->reserved_object_method_names[] = $reserved_object_method_name; }
	
	public function getReservedFunctionNames() { return $this->reserved_function_names; }
	public function setReservedFunctionNames($reserved_function_names) { $this->reserved_function_names = $reserved_function_names; }
	public function addReservedFunctionName($reserved_function_name) { $this->reserved_function_names[] = $reserved_function_name; }
	
	public function isReservedStaticMethodClassName($method_props) {
		$reserved_static_method_class_names = $this->getReservedStaticMethodClassNames();
		
		$obj_name = isset($method_props["method_obj"]) ? $method_props["method_obj"] : null;
		$static = isset($method_props["method_static"]) ? $method_props["method_static"] : null;
		
		if ($obj_name && $static && $reserved_static_method_class_names)
			return in_array($obj_name, $reserved_static_method_class_names);
		
		return false;
	}
	
	public function isReservedObjectMethodName($method_props) {
		$reserved_method_names = $this->getReservedObjectMethodNames();
		
		$obj_name = isset($method_props["method_obj"]) ? $method_props["method_obj"] : null;
		$method_name = isset($method_props["method_name"]) ? $method_props["method_name"] : null;
		$static = isset($method_props["method_static"]) ? $method_props["method_static"] : null;
		
		if ($method_name && $reserved_method_names)
			foreach ($reserved_method_names as $reserved_method_name) {
				$pos = strpos($reserved_method_name, "::");
				$reserved_method_object = "";
				
				//compare method name and class
				if ($pos !== false) {
					$reserved_method_object = substr($reserved_method_name, 0, $pos);
					$reserved_method_name = substr($reserved_method_name, $pos + 2);
					
					if ($reserved_method_name == $method_name && $static && $reserved_method_object == $obj_name)
						return true;
				}
				else {
					//compare method name and object variable
					$pos = strpos($reserved_method_name, "->");
					
					if ($pos !== false) {
						$reserved_method_var = substr($reserved_method_name, 0, $pos);
						$reserved_method_name = substr($reserved_method_name, $pos + 2);
						
						if ($reserved_method_name == $method_name && !$static && $reserved_method_var == $obj_name)
							return true;
					}
					//compare only method name
					else if (!$static && $reserved_method_name == $method_name)
						return true;
				}
				
			}
		
		return false;
	}
	
	public function getPHPMultipleParser() { return $this->PHPMultipleParser; }
	public function getPHPParserTraverser() { return $this->PHPParserTraverser; }
	public function getPHPParserPrettyPrinter() { return $this->PHPParserPrettyPrinter; }
}
?>
