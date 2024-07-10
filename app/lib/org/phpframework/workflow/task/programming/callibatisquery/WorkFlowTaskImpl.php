<?php
namespace WorkFlowTask\programming\callibatisquery;

include_once get_lib("org.phpframework.workflow.WorkFlowTask");

class WorkFlowTaskImpl extends \WorkFlowTask {
	
	public function createTaskPropertiesFromCodeStmt($stmt, $WorkFlowTaskCodeParser, &$exits = null, &$inner_tasks = null) {
		$props = $WorkFlowTaskCodeParser->getObjectMethodProps($stmt);
		
		if ($props) {
			$method_name = $props["method_name"];
			$available_methods = array("callQuery", "callQuerySQL", "callInsert", "callInsertSQL", "callUpdate", "callUpdateSQL", "callDelete", "callDeleteSQL", "callSelect", "callSelectSQL", "callProcedure", "callProcedureSQL");
			
			if (in_array($method_name, $available_methods) && empty($props["method_static"])) {
				$args = $props["method_args"];
				
				$query_type = substr($method_name, strlen($method_name) - 3) == "SQL" ? 1 : 0;
				$module_id = $args[0]["value"];
				$module_id_type = $args[0]["type"];
				
				if ($method_name == "callQuery" || $method_name == "callQuerySQL") {
					$service_type = $args[1]["value"];
					$service_type_type = $args[1]["type"];
					$service_id = $args[2]["value"];
					$service_id_type = $args[2]["type"];
					$parameters = $args[3]["value"];
					$parameters_type = $args[3]["type"];
					$options = $args[4]["value"];
					$options_type = $args[4]["type"];
				}
				else {
					$service_type = $this->getServiceTypeFromMethodName($method_name);
					$service_type_type = "string";
					$service_id = $args[1]["value"];
					$service_id_type = $args[1]["type"];
					$parameters = $args[2]["value"];
					$parameters_type = $args[2]["type"];
					$options = $args[3]["value"];
					$options_type = $args[3]["type"];
				}
				
				if ($parameters_type == "array") {
					$param_stmts = $WorkFlowTaskCodeParser->getPHPParserEmulative()->parse("<?php\n" . $parameters . "\n?>");
					//print_r($param_stmts);
					$parameters = $WorkFlowTaskCodeParser->getArrayItems($param_stmts[0]->items);
				}
				
				if ($options_type == "array") {
					$opt_stmts = $WorkFlowTaskCodeParser->getPHPParserEmulative()->parse("<?php\n" . $options . "\n?>");
					//print_r($opt_stmts);
					$options = $WorkFlowTaskCodeParser->getArrayItems($opt_stmts[0]->items);
				}
				
				unset($props["method_name"]);
				unset($props["method_args"]);
				unset($props["method_static"]);
				
				$props["query_type"] = $query_type;
				$props["module_id"] = $module_id;
				$props["module_id_type"] = self::getConfiguredParsedType($module_id_type);
				$props["service_type"] = $service_type;
				$props["service_type_type"] = self::getConfiguredParsedType($service_type_type);
				$props["service_id"] = $service_id;
				$props["service_id_type"] = self::getConfiguredParsedType($service_id_type);
				$props["parameters"] = $parameters;
				$props["parameters_type"] = self::getConfiguredParsedType($parameters_type, array("", "string", "variable", "array"));
				$props["options"] = $options;
				$props["options_type"] = self::getConfiguredParsedType($options_type, array("", "string", "variable", "array"));
				
				$props["label"] = "Call " . self::prepareTaskPropertyValueLabelFromCodeStmt($service_id) . " in " . self::prepareTaskPropertyValueLabelFromCodeStmt($module_id);
				
				$props["exits"] = array(
					self::DEFAULT_EXIT_ID => array(
						"color" => "#426efa",
					),
				);
				
			//print_r($props);
				return $props;
			}
		}
	}
	
	private function getServiceTypeFromMethodName($method_name) {
		switch($method_name) {
			case "callInsert":
			case "callInsertSQL":
				return "insert";
			case "callUpdate":
			case "callUpdateSQL":
				return "update";
			case "callDelete":
			case "callDeleteSQL":
				return "delete";
			case "callSelect":
			case "callSelectSQL":
				return "select";
			case "callProcedure":
			case "callProcedureSQL":
				return "procedure";
		}
		return "";
	}
	
	public function parseProperties(&$task) {
		$raw_data = $task["raw_data"];
		
		$parameters_type = $raw_data["childs"]["properties"][0]["childs"]["parameters_type"][0]["value"];
		if ($parameters_type == "array") {
			$parameters = $raw_data["childs"]["properties"][0]["childs"]["parameters"];
			$parameters = self::parseArrayItems($parameters);
		}
		else {
			$parameters = $raw_data["childs"]["properties"][0]["childs"]["parameters"][0]["value"];
		}
		
		$options_type = $raw_data["childs"]["properties"][0]["childs"]["options_type"][0]["value"];
		if ($options_type == "array") {
			$options = $raw_data["childs"]["properties"][0]["childs"]["options"];
			$options = self::parseArrayItems($options);
		}
		else {
			$options = $raw_data["childs"]["properties"][0]["childs"]["options"][0]["value"];
		}
		
		$properties = array(
			"method_obj" => $raw_data["childs"]["properties"][0]["childs"]["method_obj"][0]["value"],
			"query_type" => $raw_data["childs"]["properties"][0]["childs"]["query_type"][0]["value"],
			"module_id" => $raw_data["childs"]["properties"][0]["childs"]["module_id"][0]["value"],
			"module_id_type" => $raw_data["childs"]["properties"][0]["childs"]["module_id_type"][0]["value"],
			"service_type" => $raw_data["childs"]["properties"][0]["childs"]["service_type"][0]["value"],
			"service_type_type" => $raw_data["childs"]["properties"][0]["childs"]["service_type_type"][0]["value"],
			"service_id" => $raw_data["childs"]["properties"][0]["childs"]["service_id"][0]["value"],
			"service_id_type" => $raw_data["childs"]["properties"][0]["childs"]["service_id_type"][0]["value"],
			"parameters" => $parameters,
			"parameters_type" => $parameters_type,
			"options" => $options,
			"options_type" => $options_type,
		);
		
		$properties = self::parseResultVariableProperties($raw_data, $properties);
		
		return $properties;
	}
	
	public function printCode($tasks, $stop_task_id, $prefix_tab = "", $options = null) {
		$data = $this->data;
		
		$properties = $data["properties"];
		
		$code = "";
		if ($properties["module_id"] && $properties["service_id"]) {
			$var_name = self::getPropertiesResultVariableCode($properties);
		
			$method_obj = $properties["method_obj"];
			if ($method_obj) {
				$static_pos = strpos($method_obj, "::");
				$non_static_pos = strpos($method_obj, "->");
				$method_obj = substr($method_obj, 0, 1) != '$' && (!$static_pos || ($non_static_pos && $static_pos > $non_static_pos)) ? '$' . $method_obj : $method_obj;
				$method_obj .= "->";
			}
			
			$exist_method_type = false;
			switch($properties["service_type"]) {
				case "insert":  $method_name = "callInsert"; $exist_method_type = true; break;
				case "update":  $method_name = "callUpdate"; $exist_method_type = true; break;
				case "delete":  $method_name = "callDelete"; $exist_method_type = true; break;
				case "select":  $method_name = "callSelect"; $exist_method_type = true; break;
				case "procedure":  $method_name = "callProcedure"; $exist_method_type = true; break;
				default: $method_name = "callQuery";
			}
			$method_name .= $properties["query_type"] == 1 ? "SQL" : "";
			
			$parameters_type = $properties["parameters_type"];
			if ($parameters_type == "array") {
				$parameters = self::getArrayString($properties["parameters"]);
			}
			else {
				$parameters = self::getVariableValueCode($properties["parameters"], $parameters_type);
			}
			
			$opts_type = $properties["options_type"];
			if ($opts_type == "array") 
				$opts = self::getArrayString($properties["options"]);
			else
				$opts = self::getVariableValueCode($properties["options"], $opts_type);
			
			$code  = $prefix_tab . $var_name;
			$code .= $method_obj . $method_name . "(";
			$code .= self::getVariableValueCode($properties["module_id"], $properties["module_id_type"]) . ", ";
			$code .= (!$exist_method_type ? self::getVariableValueCode($properties["service_type"], $properties["service_type_type"]) . ", " : "");
			$code .= self::getVariableValueCode($properties["service_id"], $properties["service_id_type"]) . ", ";
			$code .= ($parameters ? $parameters : "null");
			$code .= $opts && $opts != "null" ? ", " . $opts : "";
			$code .= ");\n";
		}
		
		return $code . self::printTask($tasks, $data["exits"][self::DEFAULT_EXIT_ID], $stop_task_id, $prefix_tab, $options);
	}
}
?>
