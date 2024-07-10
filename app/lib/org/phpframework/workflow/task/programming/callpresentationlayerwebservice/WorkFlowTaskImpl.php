<?php
namespace WorkFlowTask\programming\callpresentationlayerwebservice;

include_once get_lib("org.phpframework.workflow.WorkFlowTask");

class WorkFlowTaskImpl extends \WorkFlowTask {

	public function createTaskPropertiesFromCodeStmt($stmt, $WorkFlowTaskCodeParser, &$exits = null, &$inner_tasks = null) {
		$props = $WorkFlowTaskCodeParser->getFunctionProps($stmt);
		
		if ($props) {
			$func_name = $props["func_name"];
			$args = $props["func_args"];
			
			//$html = call_presentation_layer_web_service(array("presentation_id" => $presentation_id, "url" => false, "external_vars" => $external_vars, "includes" => $includes, "includes_once" => $includes_once));
			if ($func_name && strtolower($func_name) == "call_presentation_layer_web_service") {
				$settings = $args[0]["value"];
				$settings_type = $args[0]["type"];
				
				if ($settings_type == "array") {
					$settings_stmts = $WorkFlowTaskCodeParser->getPHPParserEmulative()->parse("<?php\n" . $settings . "\n?>");
					$settings = $WorkFlowTaskCodeParser->getArrayItems($settings_stmts[0]->items);
					
					if (is_array($settings)) {
						$t = count($settings);
						for ($i = 0; $i < $t; $i++) {
							$setting = $settings[$i];
							
							switch (strtolower($setting["key"])) {
								case "presentation_id": 
									$project = $setting["value"];
									$project_type = $setting["value_type"];
									break;
								case "url": 
									$page = $setting["value"];
									$page_type = $setting["value_type"];
									break;
								case "external_vars": 
									if (isset($setting["items"])) {
										$external_vars = $setting["items"];
										$external_vars_type = "array";
									}
									else {
										$external_vars = $setting["value"];
										$external_vars_type = $setting["value_type"];
									}
									break;
								case "includes": 
									if (isset($setting["items"])) {
										$includes = $setting["items"];
										$includes_type = "array";
									}
									else {
										$includes = $setting["value"];
										$includes_type = $setting["value_type"];
									}
									break;
								case "includes_once":
									if (isset($setting["items"])) {
										$includes_once = $setting["items"];
										$includes_once_type = "array";
									}
									else {
										$includes_once = $setting["value"];
										$includes_once_type = $setting["value_type"];
									}
									break;
							}
						}	
					}
				}
				
				unset($props["func_name"]);
				unset($props["func_args"]);
				
				$new_props = array(
					"project" => $project,
					"project_type" => self::getConfiguredParsedType($project_type),
					"page" => $page,
					"page_type" => self::getConfiguredParsedType($page_type),
					"external_vars" => $external_vars,
					"external_vars_type" => self::getConfiguredParsedType($external_vars_type, array("", "string", "variable", "array")),
					"includes" => $includes,
					"includes_type" => self::getConfiguredParsedType($includes_type, array("", "string", "variable", "array")),
					"includes_once" => $includes_once,
					"includes_once_type" => self::getConfiguredParsedType($includes_once_type, array("", "string", "variable", "array")),
					"exits" => array(
						self::DEFAULT_EXIT_ID => array(
							"color" => "#426efa",
						),
					),
				);
				
				$props = array_merge($props, $new_props);
				
				$props["label"] = "Call " . self::prepareTaskPropertyValueLabelFromCodeStmt($project) . "/" . self::prepareTaskPropertyValueLabelFromCodeStmt($page);
				
				return $props;
			}
		}
	}
	
	public function parseProperties(&$task) {
		$raw_data = $task["raw_data"];
		
		$external_vars_type = $raw_data["childs"]["properties"][0]["childs"]["external_vars_type"][0]["value"];
		if ($external_vars_type == "array") {
			$external_vars = $raw_data["childs"]["properties"][0]["childs"]["external_vars"];
			$external_vars = self::parseArrayItems($external_vars);
		}
		else
			$external_vars = $raw_data["childs"]["properties"][0]["childs"]["external_vars"][0]["value"];
		
		$includes_type = $raw_data["childs"]["properties"][0]["childs"]["includes_type"][0]["value"];
		if ($includes_type == "array") {
			$includes = $raw_data["childs"]["properties"][0]["childs"]["includes"];
			$includes = self::parseArrayItems($includes);
		}
		else
			$includes = $raw_data["childs"]["properties"][0]["childs"]["includes"][0]["value"];
		
		$includes_once_type = $raw_data["childs"]["properties"][0]["childs"]["includes_once_type"][0]["value"];
		if ($includes_once_type == "array") {
			$includes_once = $raw_data["childs"]["properties"][0]["childs"]["includes_once"];
			$includes_once = self::parseArrayItems($includes_once);
		}
		else
			$includes_once = $raw_data["childs"]["properties"][0]["childs"]["includes_once"][0]["value"];
		
		$properties = array(
			"project" => $raw_data["childs"]["properties"][0]["childs"]["project"][0]["value"],
			"project_type" => $raw_data["childs"]["properties"][0]["childs"]["project_type"][0]["value"],
			"page" => $raw_data["childs"]["properties"][0]["childs"]["page"][0]["value"],
			"page_type" => $raw_data["childs"]["properties"][0]["childs"]["page_type"][0]["value"],
			"external_vars" => $external_vars,
			"external_vars_type" => $external_vars_type,
			"includes" => $includes,
			"includes_type" => $includes_type,
			"includes_once" => $includes_once,
			"includes_once_type" => $includes_once_type,
		);
		
		$properties = self::parseResultVariableProperties($raw_data, $properties);
		
		return $properties;
	}
	
	public function printCode($tasks, $stop_task_id, $prefix_tab = "", $options = null) {
		$data = $this->data;
		
		$properties = $data["properties"];
		
		$code = "";
		if ($properties["project"] && $properties["page"]) {
			$var_name = self::getPropertiesResultVariableCode($properties);
		
			$external_vars_type = $properties["external_vars_type"];
			if ($external_vars_type == "array")
				$external_vars = self::getArrayString($properties["external_vars"]);
			else
				$external_vars = self::getVariableValueCode($properties["external_vars"], $external_vars_type);
			
			$includes_type = $properties["includes_type"];
			if ($includes_type == "array")
				$includes = self::getArrayString($properties["includes"]);
			else
				$includes = self::getVariableValueCode($properties["includes"], $includes);
			
			$includes_once_type = $properties["includes_once_type"];
			if ($includes_once_type == "array")
				$includes_once = self::getArrayString($properties["includes_once"]);
			else
				$includes_once = self::getVariableValueCode($properties["includes_once"], $includes_once);
			
			$code  = $prefix_tab . $var_name;
			$code .= "call_presentation_layer_web_service(array(";
			$code .= '"presentation_id" => ' . self::getVariableValueCode($properties["project"], $properties["project_type"]) . ", ";
			$code .= '"url" => ' . self::getVariableValueCode($properties["page"], $properties["page_type"]) . ", ";
			$code .= '"external_vars" => ' . ($external_vars ? $external_vars : "null") . ", ";
			$code .= '"includes" => ' . ($includes ? $includes : "null") . ", ";
			$code .= '"includes_once" => ' . ($includes_once ? $includes_once : "null");
			$code .= "));\n";
		}
		
		return $code . self::printTask($tasks, $data["exits"][self::DEFAULT_EXIT_ID], $stop_task_id, $prefix_tab, $options);
	}
}
?>
