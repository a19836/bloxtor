<?php
namespace WorkFlowTask\programming\createclass;

include_once get_lib("org.phpframework.workflow.WorkFlowTask");

class WorkFlowTaskImpl extends \WorkFlowTask {
	
	public function createTaskPropertiesFromCodeStmt($stmt, $WorkFlowTaskCodeParser, &$exits = null, &$inner_tasks = null) {
		$stmt_type = strtolower($stmt->getType());
		
		if ($stmt_type == "stmt_class" || $stmt_type == "stmt_interface") {
			$code = $WorkFlowTaskCodeParser->printCodeStatement($stmt, true);
			$contents = '<?php ' . $code . ' ?>';
			$classes = \PHPCodePrintingHandler::getPHPClassesFromString($contents);
			$class_name = key($classes);
			$props = $classes[$class_name];
			$props = $props ? $props : array();
			
			if ($props["extends"] && is_array($props["extends"]))
				$props["extends"] = implode(", ", $props["extends"]);
			
			if ($props["implements"] && is_array($props["implements"]))
				$props["implements"] = implode(", ", $props["implements"]);
			
			$this->joinComments($props);
			
			$props["properties"] = \PHPCodePrintingHandler::getClassPropertiesFromString($contents, $class_name);
			
			if (is_array($props["properties"]))
				foreach ($props["properties"] as $k => $v) {
					if ($v["var_type"] == "string" && ($v["value"][0] == '"' || $v["value"][0] == "'"))
						$props["properties"][$k]["value"] = substr($v["value"], 1, -1);
					
					$this->joinComments($props["properties"][$k]);
					
					if ($v["const"])
						$props["properties"][$k]["type"] = "const";
				}
			
			if (is_array($props["methods"]))
				foreach ($props["methods"] as $k => $v) {
					if (is_array($v["arguments"])) {
						$args = array();
						
						foreach ($v["arguments"] as $args_k => $args_v) {
							$args_v = trim($args_v);
							$quote_char = substr($args_v, 0, 1);
							$var_value_type = $args_v && ($quote_char == '"' || $quote_char == "'") && substr($args_v, -1) == $quote_char ? "string" : "";
							$args_v = $var_value_type == "string" ? substr($args_v, 1, -1) : $args_v;
							
							$args[] = array("name" => $args_k, "value" => $args_v, "var_type" => $var_value_type);
						}
						
						$this->joinComments($props["methods"][$k]);
						
						$props["methods"][$k]["arguments"] = $args;
					}
					
					$props["methods"][$k]["code"] = \PHPCodePrintingHandler::getFunctionCodeFromString($contents, $v["name"], $class_name);
				}
			
			$props["label"] = "Define " . self::prepareTaskPropertyValueLabelFromCodeStmt($props["class_name"]);
			
			$props["exits"] = array(
				self::DEFAULT_EXIT_ID => array(
					"color" => "#426efa",
				),
			);
			//print_r($props);die();
			
			return $props;
		}
	}
	
	private function joinComments(&$props) {
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
	}
	
	public function parseProperties(&$task) {
		$raw_data = $task["raw_data"];
		
		$properties = array(
			"name" => $raw_data["childs"]["properties"][0]["childs"]["name"][0]["value"],
			"extends" => $raw_data["childs"]["properties"][0]["childs"]["extends"][0]["value"],
			"implements" => $raw_data["childs"]["properties"][0]["childs"]["implements"][0]["value"],
			"abstract" => $raw_data["childs"]["properties"][0]["childs"]["abstract"][0]["value"],
			"interface" => $raw_data["childs"]["properties"][0]["childs"]["interface"][0]["value"],
			"comments" => $raw_data["childs"]["properties"][0]["childs"]["comments"][0]["value"],
		);
		
		$aux = \MyXML::complexArrayToBasicArray($raw_data["childs"]["properties"][0]["childs"], array("lower_case_keys" => true));
		$properties["properties"] = $aux["properties"];
		$properties["methods"] = $aux["methods"];
		
		if ($properties["properties"]) {
			$is_assoc = array_keys($properties["properties"]) !== range(0, count($properties["properties"]) - 1);
			
			if ($is_assoc)
				$properties["properties"] = array($properties["properties"]);
		}
		
		if ($properties["methods"]) {
			$is_assoc = array_keys($properties["methods"]) !== range(0, count($properties["methods"]) - 1);
			
			if ($is_assoc)
				$properties["methods"] = array($properties["methods"]);
			
			foreach ($properties["methods"] as $idx => $method) 
				if ($method["arguments"]) {
					$is_assoc = array_keys($method["arguments"]) !== range(0, count($method["arguments"]) - 1);
					
					if ($is_assoc)
						$properties["methods"][$idx]["arguments"] = array($method["arguments"]);
				}
		}
		
		return $properties;
	}
	
	public function printCode($tasks, $stop_task_id, $prefix_tab = "", $options = null) {
		$data = $this->data;
		$code = "";
		
		$properties = $data["properties"];
		$class_name = trim($properties["name"]);
		
		if ($class_name) {
			$code .= "\n";
			$code .= $prefix_tab . str_replace("\n", "\n$prefix_tab", \PHPCodePrintingHandler::getClassString($properties)) . " {\n"; //str_replace bc of the comments
			
			if ($properties["properties"]) 
				foreach ($properties["properties"] as $property) {
					$str = \PHPCodePrintingHandler::getClassPropertyString($property);
					
					if ($str)
						$code .= $prefix_tab . "\t" . str_replace("\n", "\n$prefix_tab\t", $str) . "\n"; //str_replace bc of the comments
				}
			
			if ($properties["methods"]) 
				foreach ($properties["methods"] as $method) {
					if ($method["arguments"]) {
						$args = array();
						
						foreach ($method["arguments"] as $arg) 
							if (trim($arg["name"]))
								$args[ $arg["name"] ] = self::getVariableValueCode($arg["value"], $arg["var_type"]);
						
						$method["arguments"] = $args;
					}
					
					$str = \PHPCodePrintingHandler::getFunctionString($method, $class_name);
					
					if ($str) {
						$code .= "\n";
						$code .= $prefix_tab . str_replace("\n", "\n$prefix_tab", $str) . " {\n"; //str_replace bc of the comments
						$code .= $prefix_tab . "\t\t" . str_replace("\n", "\n$prefix_tab\t\t", $method["code"]) . "\n";
						$code .= $prefix_tab . "\t}\n";
						$code .= "\n";
					}
				}
			
			$code .=  !$prefix_tab && !preg_match("/\s/", substr($code, -1)) ? " " : ""; //add space here, bc the $prefix_tab could be empty and the $code could end in <?php. If we do not add the space here, then we will get <?php} which will give a php error.
			$code .= $prefix_tab . "}\n";
			$code .= "\n";
		}
			
		return $code . self::printTask($tasks, $data["exits"][self::DEFAULT_EXIT_ID], $stop_task_id, $prefix_tab, $options);
	}
}
?>
