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

namespace WorkFlowTask\programming\createclass;

include_once get_lib("org.phpframework.workflow.WorkFlowTask");

class WorkFlowTaskImpl extends \WorkFlowTask {
	
	public function createTaskPropertiesFromCodeStmt($stmt, $WorkFlowTaskCodeParser, &$exits = null, &$inner_tasks = null) {
		$stmt_type = strtolower($stmt->getType());
		
		if ($stmt_type == "stmt_class" || $stmt_type == "stmt_interface" || $stmt_type == "stmt_trait") {
			$code = $WorkFlowTaskCodeParser->printCodeStatement($stmt);
			$contents = '<?php ' . $code . ' ?>';
			$classes = \PHPCodePrintingHandler::getPHPClassesFromString($contents);
			$class_name = key($classes);
			$props = isset($classes[$class_name]) ? $classes[$class_name] : null;
			$props = $props ? $props : array();
			
			if (!empty($props["extends"]) && is_array($props["extends"]))
				$props["extends"] = implode(", ", $props["extends"]);
			
			if (!empty($props["implements"]) && is_array($props["implements"]))
				$props["implements"] = implode(", ", $props["implements"]);
			
			$this->joinComments($props);
			
			$props["properties"] = \PHPCodePrintingHandler::getClassPropertiesFromString($contents, $class_name);
			
			if (is_array($props["properties"]))
				foreach ($props["properties"] as $k => $v) {
					if (isset($v["var_type"]) && $v["var_type"] == "string" && isset($v["value"]) && ($v["value"][0] == '"' || $v["value"][0] == "'"))
						$props["properties"][$k]["value"] = substr($v["value"], 1, -1);
					
					$this->joinComments($props["properties"][$k]);
					
					if (!empty($v["const"]))
						$props["properties"][$k]["type"] = "const";
				}
			
			if (isset($props["methods"]) && is_array($props["methods"]))
				foreach ($props["methods"] as $k => $v) {
					if (isset($v["arguments"]) && is_array($v["arguments"])) {
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
					
					$props["methods"][$k]["code"] = \PHPCodePrintingHandler::getFunctionCodeFromString($contents, isset($v["name"]) ? $v["name"] : null, $class_name);
				}
			
			$props["label"] = "Define " . self::prepareTaskPropertyValueLabelFromCodeStmt(isset($props["class_name"]) ? $props["class_name"] : null);
			
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
	}
	
	public function parseProperties(&$task) {
		$raw_data = isset($task["raw_data"]) ? $task["raw_data"] : null;
		
		$properties = array(
			"name" => isset($raw_data["childs"]["properties"][0]["childs"]["name"][0]["value"]) ? $raw_data["childs"]["properties"][0]["childs"]["name"][0]["value"] : null,
			"extends" => isset($raw_data["childs"]["properties"][0]["childs"]["extends"][0]["value"]) ? $raw_data["childs"]["properties"][0]["childs"]["extends"][0]["value"] : null,
			"implements" => isset($raw_data["childs"]["properties"][0]["childs"]["implements"][0]["value"]) ? $raw_data["childs"]["properties"][0]["childs"]["implements"][0]["value"] : null,
			"abstract" => isset($raw_data["childs"]["properties"][0]["childs"]["abstract"][0]["value"]) ? $raw_data["childs"]["properties"][0]["childs"]["abstract"][0]["value"] : null,
			"interface" => isset($raw_data["childs"]["properties"][0]["childs"]["interface"][0]["value"]) ? $raw_data["childs"]["properties"][0]["childs"]["interface"][0]["value"] : null,
			"trait" => isset($raw_data["childs"]["properties"][0]["childs"]["trait"][0]["value"]) ? $raw_data["childs"]["properties"][0]["childs"]["trait"][0]["value"] : null,
		);
		
		$aux = isset($raw_data["childs"]["properties"][0]["childs"]) ? $raw_data["childs"]["properties"][0]["childs"] : null;
		$aux = \MyXML::complexArrayToBasicArray($aux, array("lower_case_keys" => true));
		$properties["properties"] = isset($aux["properties"]) ? $aux["properties"] : null;
		$properties["methods"] = isset($aux["methods"]) ? $aux["methods"] : null;
		
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
				if (!empty($method["arguments"])) {
					$is_assoc = array_keys($method["arguments"]) !== range(0, count($method["arguments"]) - 1);
					
					if ($is_assoc)
						$properties["methods"][$idx]["arguments"] = array($method["arguments"]);
				}
		}
		
		return $properties;
	}
	
	public function printCode($tasks, $stop_task_id, $prefix_tab = "", $options = null) {
		$data = isset($this->data) ? $this->data : null;
		$code = "";
		
		$properties = isset($data["properties"]) ? $data["properties"] : null;
		$class_name = isset($properties["name"]) ? trim($properties["name"]) : "";
		
		if ($class_name) {
			$comments_exists = !empty($properties["comments"]);
			
			//unset comments bc they will be added in the parent::printTask method
			unset($properties["comments"]);
			
			$code .= $comments_exists ? "" : "\n";
			$code .= $prefix_tab . str_replace("\n", "\n$prefix_tab", \PHPCodePrintingHandler::getClassString($properties)) . " {\n"; //str_replace bc of the comments
			
			if (!empty($properties["properties"]))
				foreach ($properties["properties"] as $property) {
					$str = \PHPCodePrintingHandler::getClassPropertyString($property);
					
					if ($str)
						$code .= $prefix_tab . "\t" . str_replace("\n", "\n$prefix_tab\t", $str) . "\n"; //str_replace bc of the comments
				}
			
			if (!empty($properties["methods"]))
				foreach ($properties["methods"] as $method) {
					if (!empty($method["arguments"])) {
						$args = array();
						
						foreach ($method["arguments"] as $arg) 
							if (isset($arg["name"]) && trim($arg["name"]))
								$args[ $arg["name"] ] = self::getVariableValueCode(isset($arg["value"]) ? $arg["value"] : null, isset($arg["var_type"]) ? $arg["var_type"] : null);
						
						$method["arguments"] = $args;
					}
					
					$str = \PHPCodePrintingHandler::getFunctionString($method, $class_name);
					
					if ($str) {
						$code .= "\n";
						$code .= $prefix_tab . str_replace("\n", "\n$prefix_tab", $str) . " {\n"; //str_replace bc of the comments
						$code .= $prefix_tab . "\t\t" . (isset($method["code"]) ? str_replace("\n", "\n$prefix_tab\t\t", $method["code"]) : "") . "\n";
						$code .= $prefix_tab . "\t}\n";
						$code .= "\n";
					}
				}
			
			$code .=  !$prefix_tab && !preg_match("/\s/", substr($code, -1)) ? " " : ""; //add space here, bc the $prefix_tab could be empty and the $code could end in <?php. If we do not add the space here, then we will get <?php} which will give a php error.
			$code .= $prefix_tab . "}\n";
			$code .= "\n";
		}
		
		$exit_task_id = isset($data["exits"][self::DEFAULT_EXIT_ID]) ? $data["exits"][self::DEFAULT_EXIT_ID] : null;
		return $code . self::printTask($tasks, $exit_task_id, $stop_task_id, $prefix_tab, $options);
	}
}
?>
