<?php
namespace WorkFlowTask\programming\sendemail;

include_once get_lib("org.phpframework.workflow.WorkFlowTask");

class WorkFlowTaskImpl extends \WorkFlowTask {
	
	public function createTaskPropertiesFromCodeStmt($stmt, $WorkFlowTaskCodeParser, &$exits = null, &$inner_tasks = null) {
		$props = $WorkFlowTaskCodeParser->getObjectMethodProps($stmt);
		
		if ($props && $props["method_name"] && $props["method_static"] && $props["method_obj"] == "SendEmailHandler") {
			$method_name = $props["method_name"];
			
			if ($method_name == "sendEmail" || $method_name == "sendSMTPEmail") {
				$args = $props["method_args"];
				
				$props["method"] = $props["method_obj"] . "::$method_name";
				
				$settings = $args[0]["value"];
				$settings_type = $args[0]["type"];
				
				if ($settings_type == "array") {
					$param_stmts = $WorkFlowTaskCodeParser->getPHPParserEmulative()->parse("<?php\n" . $settings . "\n?>");
					//print_r($param_stmts);
					$settings = $WorkFlowTaskCodeParser->getArrayItems($param_stmts[0]->items);
				}
				
				$props["settings"] = $settings;
				$props["settings_type"] = self::getConfiguredParsedType($settings_type, array("", "string", "variable", "array"));
				
				unset($props["method_name"]);
				unset($props["method_obj"]);
				unset($props["method_args"]);
				unset($props["method_static"]);
				
				$props["label"] = $method_name;
				
				$props["exits"] = array(
					self::DEFAULT_EXIT_ID => array(
						"color" => "#426efa",
					),
				);
				
				return $props;
			}
		}
	}
	
	public function parseProperties(&$task) {
		$raw_data = $task["raw_data"];
		
		$settings_type = $raw_data["childs"]["properties"][0]["childs"]["settings_type"][0]["value"];
		if ($settings_type == "array") {
			$settings = $raw_data["childs"]["properties"][0]["childs"]["settings"];
			$settings = self::parseArrayItems($settings);
		}
		else {
			$settings = $raw_data["childs"]["properties"][0]["childs"]["settings"][0]["value"];
		}
		
		$properties = array(
			"method" => $raw_data["childs"]["properties"][0]["childs"]["method"][0]["value"],
			"settings" => $settings,
			"settings_type" => $settings_type,
		);
		
		$properties = self::parseResultVariableProperties($raw_data, $properties);
		
		return $properties;
	}
	
	public function printCode($tasks, $stop_task_id, $prefix_tab = "", $options = null) {
		$data = $this->data;
		
		$properties = $data["properties"];
		
		$var_name = self::getPropertiesResultVariableCode($properties);
		$method = $properties["method"];
		$code = "";
		
		if ($method) {
			$settings_type = $properties["settings_type"];
			if ($settings_type == "array")
				$settings = self::getArrayString($properties["settings"]);
			else
				$settings = self::getVariableValueCode($properties["settings"], $settings_type);
			
			$code = $prefix_tab . $var_name . "$method(";
			$code .= $settings ? $settings : "null";
			$code .= ");\n";
		}
		
		return $code . self::printTask($tasks, $data["exits"][self::DEFAULT_EXIT_ID], $stop_task_id, $prefix_tab, $options);
	}
}
?>
