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
			$props = $methods[0]["methods"][0];
			$props = $props ? $props : array();
			
			if (is_array($props["arguments"])) {
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
			
			if ($props["comments"] || $props["doc_comments"]) {
				$doc_comments = $props["doc_comments"] ? implode("\n", $props["doc_comments"]) : "";
				$doc_comments = trim($doc_comments);
				$doc_comments = str_replace("\r", "", $doc_comments);
				$doc_comments = preg_replace("/^\/[*]+\s*/", "", $doc_comments);
				$doc_comments = preg_replace("/\s*[*]+\/\s*$/", "", $doc_comments);
				$doc_comments = preg_replace("/\s*\n\s*[*]*\s*/", "\n", $doc_comments);
				$doc_comments = preg_replace("/^\s*[*]*\s*/", "", $doc_comments);
				$doc_comments = trim($doc_comments);
				
				$comments = is_array($props["comments"]) ? trim(implode("\n", $props["comments"])) : "";
				$comments .= $doc_comments ? "\n" . trim($doc_comments) : "";
				$comments = str_replace(array("/*", "*/", "//"), "", $comments);
				$comments = trim($comments);
				
				$props["comments"] = $comments;
				unset($props["doc_comments"]);
			}
			
			$props["code"] = \PHPCodePrintingHandler::getFunctionCodeFromString($contents, $props["name"]);
			
			$props["label"] = "Define " . self::prepareTaskPropertyValueLabelFromCodeStmt($props["name"]);
			
			$props["exits"] = array(
				self::DEFAULT_EXIT_ID => array(
					"color" => "#426efa",
				),
			);
			
			return $props;
		}
	}
	
	public function parseProperties(&$task) {
		$raw_data = $task["raw_data"];
		
		$properties = array(
			"name" => $raw_data["childs"]["properties"][0]["childs"]["name"][0]["value"],
			"comments" => $raw_data["childs"]["properties"][0]["childs"]["comments"][0]["value"],
			"code" => $raw_data["childs"]["properties"][0]["childs"]["code"][0]["value"],
		);
		
		$aux = \MyXML::complexArrayToBasicArray($raw_data["childs"]["properties"][0]["childs"], array("lower_case_keys" => true));
		$properties["arguments"] = $aux["arguments"];
		
		if ($properties["arguments"]) {
			$is_assoc = array_keys($properties["arguments"]) !== range(0, count($properties["arguments"]) - 1);
			
			if ($is_assoc)
				$properties["arguments"] = array($properties["arguments"]);
		}
		
		return $properties;
	}
	
	public function printCode($tasks, $stop_task_id, $prefix_tab = "", $options = null) {
		$data = $this->data;
		
		$properties = $data["properties"];
		
		if ($properties["arguments"]) {
			$args = array();
			
			foreach ($properties["arguments"] as $arg) 
				if (trim($arg["name"]))
					$args[ $arg["name"] ] = self::getVariableValueCode($arg["value"], $arg["var_type"]);
			
			$properties["arguments"] = $args;
		}
		
		$code = "\n";
		$code .= $prefix_tab . str_replace("\n", "\n$prefix_tab", \PHPCodePrintingHandler::getFunctionString($properties)) . " {\n"; //str_replace bc of the comments
		$code .= $prefix_tab . "\t" . str_replace("\n", "\n$prefix_tab\t", $properties["code"]) . "\n";
		//$code .=  !$prefix_tab && !preg_match("/\s/", substr($code, -1)) ? " " : ""; //add space here, bc the $prefix_tab could be empty and the $code could end in <?php. If we do not add the space here, then we will get <?php} which will give a php error. NO NEED THIS BC WE ADD BEFORE "\n".
		$code .= $prefix_tab . "}\n";
		$code .= "\n";
		
		return $code . self::printTask($tasks, $data["exits"][self::DEFAULT_EXIT_ID], $stop_task_id, $prefix_tab, $options);
	}
}
?>
