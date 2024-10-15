<?php
namespace WorkFlowTask\programming\createfunction;

include_once get_lib("org.phpframework.workflow.WorkFlowTask");

class WorkFlowTaskImpl extends \WorkFlowTask {
	
	public function createTaskPropertiesFromCodeStmt($stmt, $WorkFlowTaskCodeParser, &$exits = null, &$inner_tasks = null) {
		$stmt_type = strtolower($stmt->getType());
		
		if ($stmt_type == "stmt_function") {
			$code = $WorkFlowTaskCodeParser->printCodeStatement($stmt, true);
			$contents = '<?php ' . $code . ' ?>';
			$methods = \PHPCodePrintingHandler::getPHPClassesFromString($contents);
			$props = isset($methods[0]["methods"][0]) ? $methods[0]["methods"][0] : null;
			$props = $props ? $props : array();
			
			if (isset($props["arguments"]) && is_array($props["arguments"])) {
				$args = array();
				
				foreach ($props["arguments"] as $k => $v) {
					$v = trim($v);
					$quote_char = substr($v, 0, 1);
					$var_value_type = $v && ($quote_char == '"' || $quote_char == "'") && substr($v, -1) == $quote_char ? "string" : "";
					$v = $var_value_type == "string" ? substr($v, 1, -1) : $v;
					
					$args[] = array("name" => $k, "value" => $v, "var_type" => $var_value_type);
				}
				
				$props["arguments"] = $args;
			}
			
			if (!empty($props["comments"]) || !empty($props["doc_comments"])) {
				$doc_comments = !empty($props["doc_comments"]) ? implode("\n", $props["doc_comments"]) : "";
				$doc_comments = trim($doc_comments);
				$doc_comments = str_replace("\r", "", $doc_comments);
				$doc_comments = preg_replace("/^\/[*]+\s*/", "", $doc_comments);
				$doc_comments = preg_replace("/\s*[*]+\/\s*$/", "", $doc_comments);
				$doc_comments = preg_replace("/\s*\n\s*[*]*\s*/", "\n", $doc_comments);
				$doc_comments = preg_replace("/^\s*[*]*\s*/", "", $doc_comments);
				$doc_comments = trim($doc_comments);
				
				$comments = isset($props["comments"]) && is_array($props["comments"]) ? trim(implode("\n", $props["comments"])) : "";
				$comments .= $doc_comments ? "\n" . trim($doc_comments) : "";
				$comments = str_replace(array("/*", "*/", "//"), "", $comments);
				$comments = trim($comments);
				
				$props["comments"] = $comments;
				unset($props["doc_comments"]);
			}
			
			$name = isset($props["name"]) ? $props["name"] : null;
			$props["code"] = \PHPCodePrintingHandler::getFunctionCodeFromString($contents, $name);
			
			$props["label"] = "Define " . self::prepareTaskPropertyValueLabelFromCodeStmt($name);
			
			$props["exits"] = array(
				self::DEFAULT_EXIT_ID => array(
					"color" => "#426efa",
				),
			);
			
			return $props;
		}
	}
	
	public function parseProperties(&$task) {
		$raw_data = isset($task["raw_data"]) ? $task["raw_data"] : null;
		
		$properties = array(
			"name" => isset($raw_data["childs"]["properties"][0]["childs"]["name"][0]["value"]) ? $raw_data["childs"]["properties"][0]["childs"]["name"][0]["value"] : null,
			"comments" => isset($raw_data["childs"]["properties"][0]["childs"]["comments"][0]["value"]) ? $raw_data["childs"]["properties"][0]["childs"]["comments"][0]["value"] : null,
			"code" => isset($raw_data["childs"]["properties"][0]["childs"]["code"][0]["value"]) ? $raw_data["childs"]["properties"][0]["childs"]["code"][0]["value"] : null,
		);
		
		$aux = isset($raw_data["childs"]["properties"][0]["childs"]) ? $raw_data["childs"]["properties"][0]["childs"] : null;
		$aux = \MyXML::complexArrayToBasicArray($aux, array("lower_case_keys" => true));
		$properties["arguments"] = isset($aux["arguments"]) ? $aux["arguments"] : null;
		
		if ($properties["arguments"]) {
			$is_assoc = array_keys($properties["arguments"]) !== range(0, count($properties["arguments"]) - 1);
			
			if ($is_assoc)
				$properties["arguments"] = array($properties["arguments"]);
		}
		
		return $properties;
	}
	
	public function printCode($tasks, $stop_task_id, $prefix_tab = "", $options = null) {
		$data = isset($this->data) ? $this->data : null;
		
		$properties = isset($data["properties"]) ? $data["properties"] : null;
		
		if (!empty($properties["arguments"])) {
			$args = array();
			
			foreach ($properties["arguments"] as $arg) 
				if (isset($arg["name"]) && trim($arg["name"])) {
					$value = isset($arg["value"]) ? $arg["value"] : null;
					$var_type = isset($arg["var_type"]) ? $arg["var_type"] : null;
					
					$args[ $arg["name"] ] = self::getVariableValueCode($value, $var_type);
				}
				
			$properties["arguments"] = $args;
		}
		
		$code = "\n";
		$code .= $prefix_tab . str_replace("\n", "\n$prefix_tab", \PHPCodePrintingHandler::getFunctionString($properties)) . " {\n"; //str_replace bc of the comments
		$code .= $prefix_tab . "\t" . (isset($properties["code"]) ? str_replace("\n", "\n$prefix_tab\t", $properties["code"]) : "") . "\n";
		//$code .=  !$prefix_tab && !preg_match("/\s/", substr($code, -1)) ? " " : ""; //add space here, bc the $prefix_tab could be empty and the $code could end in <?php. If we do not add the space here, then we will get <?php} which will give a php error. NO NEED THIS BC WE ADD BEFORE "\n".
		$code .= $prefix_tab . "}\n";
		$code .= "\n";
		
		$exit_task_id = isset($data["exits"][self::DEFAULT_EXIT_ID]) ? $data["exits"][self::DEFAULT_EXIT_ID] : null;
		return $code . self::printTask($tasks, $exit_task_id, $stop_task_id, $prefix_tab, $options);
	}
}
?>
