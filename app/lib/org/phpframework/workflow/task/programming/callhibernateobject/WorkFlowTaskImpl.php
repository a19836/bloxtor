<?php
namespace WorkFlowTask\programming\callhibernateobject;

include_once get_lib("org.phpframework.workflow.WorkFlowTask");

class WorkFlowTaskImpl extends \WorkFlowTask {
	
	public function createTaskPropertiesFromCodeStmt($stmt, $WorkFlowTaskCodeParser, &$exits = null, &$inner_tasks = null) {
		$props = $WorkFlowTaskCodeParser->getObjectMethodProps($stmt);
		
		if ($props) {
			$method_name = isset($props["method_name"]) ? $props["method_name"] : null;
			
			if ($method_name == "callObject" && empty($props["method_static"])) {
				$args = isset($props["method_args"]) ? $props["method_args"] : null;
				
				$module_id = isset($args[0]["value"]) ? $args[0]["value"] : null;
				$module_id_type = isset($args[0]["type"]) ? $args[0]["type"] : null;
				$service_id = isset($args[1]["value"]) ? $args[1]["value"] : null;
				$service_id_type = isset($args[1]["type"]) ? $args[1]["type"] : null;
				$options = isset($args[2]["value"]) ? $args[2]["value"] : null;
				$options_type = isset($args[2]["type"]) ? $args[2]["type"] : null;
					
				if ($options_type == "array") {
					$opt_stmts = $WorkFlowTaskCodeParser->getPHPMultipleParser()->parse("<?php\n" . $options . "\n?>");
					//print_r($opt_stmts);
					$items = $WorkFlowTaskCodeParser->getStmtArrayItems($opt_stmts[0]);
					$options = $WorkFlowTaskCodeParser->getArrayItems($items);
				}
				
				unset($props["method_name"]);
				unset($props["method_args"]);
				unset($props["method_static"]);
				
				$props["module_id"] = $module_id;
				$props["module_id_type"] = self::getConfiguredParsedType($module_id_type);
				$props["service_id"] = $service_id;
				$props["service_id_type"] = self::getConfiguredParsedType($service_id_type);
				$props["options"] = $options;
				$props["options_type"] = self::getConfiguredParsedType($options_type, array("", "string", "variable", "array"));
				
				$props["label"] = "Get " . self::prepareTaskPropertyValueLabelFromCodeStmt($module_id) . "." . self::prepareTaskPropertyValueLabelFromCodeStmt($service_id);
				
				$props["exits"] = array(
					self::DEFAULT_EXIT_ID => array(
						"color" => "#426efa",
					),
				);
				
				$WorkFlowTaskCodeParser->shared_objects["hibernate_vars"][] = self::getPropertiesResultVariableCode($props, false);
				
			//print_r($props);
				return $props;
			}
		}
	}
	
	public function parseProperties(&$task) {
		$raw_data = isset($task["raw_data"]) ? $task["raw_data"] : null;
		
		$options_type = isset($raw_data["childs"]["properties"][0]["childs"]["options_type"][0]["value"]) ? $raw_data["childs"]["properties"][0]["childs"]["options_type"][0]["value"] : null;
		if ($options_type == "array") {
			$options = isset($raw_data["childs"]["properties"][0]["childs"]["options"]) ? $raw_data["childs"]["properties"][0]["childs"]["options"] : null;
			$options = self::parseArrayItems($options);
		}
		else {
			$options = isset($raw_data["childs"]["properties"][0]["childs"]["options"][0]["value"]) ? $raw_data["childs"]["properties"][0]["childs"]["options"][0]["value"] : null;
		}
		
		$properties = array(
			"method_obj" => isset($raw_data["childs"]["properties"][0]["childs"]["method_obj"][0]["value"]) ? $raw_data["childs"]["properties"][0]["childs"]["method_obj"][0]["value"] : null,
			"module_id" => isset($raw_data["childs"]["properties"][0]["childs"]["module_id"][0]["value"]) ? $raw_data["childs"]["properties"][0]["childs"]["module_id"][0]["value"] : null,
			"module_id_type" => isset($raw_data["childs"]["properties"][0]["childs"]["module_id_type"][0]["value"]) ? $raw_data["childs"]["properties"][0]["childs"]["module_id_type"][0]["value"] : null,
			"service_id" => isset($raw_data["childs"]["properties"][0]["childs"]["service_id"][0]["value"]) ? $raw_data["childs"]["properties"][0]["childs"]["service_id"][0]["value"] : null,
			"service_id_type" => isset($raw_data["childs"]["properties"][0]["childs"]["service_id_type"][0]["value"]) ? $raw_data["childs"]["properties"][0]["childs"]["service_id_type"][0]["value"] : null,
			"options" => $options,
			"options_type" => $options_type,
		);
		
		$properties = self::parseResultVariableProperties($raw_data, $properties);
		
		return $properties;
	}
	
	public function printCode($tasks, $stop_task_id, $prefix_tab = "", $options = null) {
		$data = isset($this->data) ? $this->data : null;
		
		$properties = isset($data["properties"]) ? $data["properties"] : null;
		
		$code = "";
		if (!empty($properties["module_id"]) && !empty($properties["service_id"])) {
			$var_name = self::getPropertiesResultVariableCode($properties);
		
			$method_obj = isset($properties["method_obj"]) ? $properties["method_obj"] : null;
			if ($method_obj) {
				$static_pos = strpos($method_obj, "::");
				$non_static_pos = strpos($method_obj, "->");
				$method_obj = substr($method_obj, 0, 1) != '$' && substr($method_obj, 0, 2) != '@$' && (!$static_pos || ($non_static_pos && $static_pos > $non_static_pos)) ? '$' . $method_obj : $method_obj;
				$method_obj .= "->";
			}
			
			$opts_type = isset($properties["options_type"]) ? $properties["options_type"] : null;
			$opts = isset($properties["options"]) ? $properties["options"] : null;
			if ($opts_type == "array") 
				$opts = self::getArrayString($opts);
			else 
				$opts = self::getVariableValueCode($opts, $opts_type);
			
			$module_id = isset($properties["module_id"]) ? $properties["module_id"] : null;
			$module_id_type = isset($properties["module_id_type"]) ? $properties["module_id_type"] : null;
			$service_id = isset($properties["service_id"]) ? $properties["service_id"] : null;
			$service_id_type = isset($properties["service_id_type"]) ? $properties["service_id_type"] : null;
			
			$code  = $prefix_tab . $var_name;
			$code .= $method_obj . "callObject(";
			$code .= self::getVariableValueCode($module_id, $module_id_type) . ", ";
			$code .= self::getVariableValueCode($service_id, $service_id_type);
			$code .= $opts && $opts != "null" ? ", " . $opts : "";
			$code .= ");\n";
		}
		
		$exit_task_id = isset($data["exits"][self::DEFAULT_EXIT_ID]) ? $data["exits"][self::DEFAULT_EXIT_ID] : null;
		return $code . self::printTask($tasks, $exit_task_id, $stop_task_id, $prefix_tab, $options);
	}
}
?>
