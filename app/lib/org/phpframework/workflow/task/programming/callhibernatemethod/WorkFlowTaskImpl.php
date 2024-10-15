<?php
namespace WorkFlowTask\programming\callhibernatemethod;

include_once get_lib("org.phpframework.workflow.WorkFlowTask");

class WorkFlowTaskImpl extends \WorkFlowTask {
	
	private static $METHODS_ARGS = array(
		"insert" => array("data", "ids", "options"),
		"insertAll" => array("data", "statuses", "ids", "options"),
		"update" => array("data", "options"),
		"updateAll" => array("data", "statuses", "options"),
		"insertOrUpdate" => array("data", "ids", "options"),
		"insertOrUpdateAll" => array("data", "statuses", "ids", "options"),
		"delete" => array("data", "options"),
		"deleteAll" => array("data", "statuses", "options"),
		"updatePrimaryKeys" => array("data", "options"),
		"findById" => array("data", "data_options", "options"), //data_options will not be used
		"find" => array("data", "options"),
		"count" => array("data", "options"),
		"findRelationships" => array("parent_ids", "options"),
		"findRelationship" => array("rel_name", "parent_ids", "options"),
		"countRelationships" => array("parent_ids", "options"),
		"countRelationship" => array("rel_name", "parent_ids", "options"),
		"callQuerySQL" => array("query_type", "query_id", "data", "options"),
		"callQuery" => array("query_type", "query_id", "data", "options"),
		"callInsertSQL" => array("query_id", "data", "options"),
		"callInsert" => array("query_id", "data", "options"),
		"callUpdateSQL" => array("query_id", "data", "options"),
		"callUpdate" => array("query_id", "data", "options"),
		"callDeleteSQL" => array("query_id", "data", "options"),
		"callDelete" => array("query_id", "data", "options"),
		"callSelectSQL" => array("query_id", "data", "options"),
		"callSelect" => array("query_id", "data", "options"),
		"callProcedureSQL" => array("query_id", "data", "options"),
		"callProcedure" => array("query_id", "data", "options"),
		"getFunction" => array("function_name", "data", "options"),
		"getData" => array("sql", "options"),
		"setData" => array("sql", "options"),
		"getInsertedId" => array("options"),
	);
	
	public function __construct() {
		$this->priority = 2;
	}
	
	public function createTaskPropertiesFromCodeStmt($stmt, $WorkFlowTaskCodeParser, &$exits = null, &$inner_tasks = null) {
		$props = $WorkFlowTaskCodeParser->getObjectMethodProps($stmt);
		
		if ($props) {
			$method_name = isset($props["method_name"]) ? $props["method_name"] : null;
			
			if (array_key_exists($method_name, self::$METHODS_ARGS) && empty($props["method_static"])) {
				$hibernate_vars = isset($WorkFlowTaskCodeParser->shared_objects["hibernate_vars"]) ? $WorkFlowTaskCodeParser->shared_objects["hibernate_vars"] : null;
				$method_obj = isset($props["method_obj"]) ? $props["method_obj"] : null;
				$exists = $status = is_array($hibernate_vars) && in_array($method_obj, $hibernate_vars);
				
				$code = "<?php\n" . $method_obj . "\n?>";
				
				if (!$status) {
					$tps = $WorkFlowTaskCodeParser->getParsedCodeAsArray($code);
					$status = isset($tps["task"][0]["childs"]["tag"][0]["value"]) && $tps["task"][0]["childs"]["tag"][0]["value"] == "callhibernateobject";
				}
				
				if ($status) {
					if ($exists) {
						$props["broker_method_obj_type"] = "exists_hbn_var";
					}
					else {
						$method_obj_stmts = $WorkFlowTaskCodeParser->getPHPMultipleParser()->parse($code);
						$method_obj_props = $WorkFlowTaskCodeParser->getObjectMethodProps($method_obj_stmts[0]);
						
						$props["method_obj"] = isset($method_obj_props["method_obj"]) ? $method_obj_props["method_obj"] : null;
						$props["module_id"] = isset($method_obj_props["method_args"][0]["value"]) ? $method_obj_props["method_args"][0]["value"] : null;
						$props["module_id_type"] = isset($method_obj_props["method_args"][0]["type"]) ? self::getConfiguredParsedType($method_obj_props["method_args"][0]["type"]) : null;
						$props["service_id"] = isset($method_obj_props["method_args"][1]["value"]) ? $method_obj_props["method_args"][1]["value"] : null;
						$props["service_id_type"] = isset($method_obj_props["method_args"][1]["type"]) ? self::getConfiguredParsedType($method_obj_props["method_args"][1]["type"]) : null;
						$props["options"] = isset($method_obj_props["method_args"][2]["value"]) ? $method_obj_props["method_args"][2]["value"] : null;
						$props["options_type"] = isset($method_obj_props["method_args"][2]["type"]) ? self::getConfiguredParsedType($method_obj_props["method_args"][2]["type"], array("", "string", "variable", "array")) : null;
					}
					
					$props["service_method"] = isset($props["method_name"]) ? $props["method_name"] : null;
					$props["service_method_type"] = "string";
					
					if ($props["options_type"] == "array") {
						$opt_stmts = $WorkFlowTaskCodeParser->getPHPMultipleParser()->parse("<?php\n" . $props["options"] . "\n?>");
						//print_r($opt_stmts);
						$items = $WorkFlowTaskCodeParser->getStmtArrayItems($opt_stmts[0]);
						$props["options"] = $WorkFlowTaskCodeParser->getArrayItems($items);
					}
					
					$ma = self::$METHODS_ARGS[$method_name];
					$ma = $ma ? $ma : array();
					foreach ($ma as $arg_idx => $arg_name) {
						$an = strtolower($arg_name);
						$props["sma_" . $an] = isset($props["method_args"][$arg_idx]["value"]) ? $props["method_args"][$arg_idx]["value"] : null;
						$props["sma_" . $an . "_type"] = isset($props["method_args"][$arg_idx]["type"]) ? $props["method_args"][$arg_idx]["type"] : null;
						
						if ($props["sma_" . $an . "_type"] == "array") {
							$sma_stmts = $WorkFlowTaskCodeParser->getPHPMultipleParser()->parse("<?php\n" . $props["sma_" . $an] . "\n?>");
							$items = $WorkFlowTaskCodeParser->getStmtArrayItems($sma_stmts[0]);
							$props["sma_" . $an] = $WorkFlowTaskCodeParser->getArrayItems($items);
						}
						
						if ($an == "data" || $an == "data_options" || $an == "parent_ids" || $an == "options")
							$props["sma_" . $an . "_type"] = self::getConfiguredParsedType($props["sma_" . $an . "_type"], array("", "string", "variable", "array"));
						else
							$props["sma_" . $an . "_type"] = self::getConfiguredParsedType($props["sma_" . $an . "_type"]);
					}
					
					unset($props["method_name"]);
					unset($props["method_args"]);
					unset($props["method_static"]);
					
					$props["label"] = "Call " . (!empty($props["service_id"]) ? self::prepareTaskPropertyValueLabelFromCodeStmt($props["service_id"]) . "." : "") . self::prepareTaskPropertyValueLabelFromCodeStmt($props["service_method"]) . (!empty($props["module_id"]) ? " in " . self::prepareTaskPropertyValueLabelFromCodeStmt($props["module_id"]) : "");
					
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
			"broker_method_obj_type" => isset($raw_data["childs"]["properties"][0]["childs"]["broker_method_obj_type"][0]["value"]) ? $raw_data["childs"]["properties"][0]["childs"]["broker_method_obj_type"][0]["value"] : null,
			"method_obj" => isset($raw_data["childs"]["properties"][0]["childs"]["method_obj"][0]["value"]) ? $raw_data["childs"]["properties"][0]["childs"]["method_obj"][0]["value"] : null,
			"module_id" => isset($raw_data["childs"]["properties"][0]["childs"]["module_id"][0]["value"]) ? $raw_data["childs"]["properties"][0]["childs"]["module_id"][0]["value"] : null,
			"module_id_type" => isset($raw_data["childs"]["properties"][0]["childs"]["module_id_type"][0]["value"]) ? $raw_data["childs"]["properties"][0]["childs"]["module_id_type"][0]["value"] : null,
			"service_id" => isset($raw_data["childs"]["properties"][0]["childs"]["service_id"][0]["value"]) ? $raw_data["childs"]["properties"][0]["childs"]["service_id"][0]["value"] : null,
			"service_id_type" => isset($raw_data["childs"]["properties"][0]["childs"]["service_id_type"][0]["value"]) ? $raw_data["childs"]["properties"][0]["childs"]["service_id_type"][0]["value"] : null,
			"options" => $options,
			"options_type" => $options_type,
			"service_method" => isset($raw_data["childs"]["properties"][0]["childs"]["service_method"][0]["value"]) ? $raw_data["childs"]["properties"][0]["childs"]["service_method"][0]["value"] : null,
			"service_method_type" => isset($raw_data["childs"]["properties"][0]["childs"]["service_method_type"][0]["value"]) ? $raw_data["childs"]["properties"][0]["childs"]["service_method_type"][0]["value"] : null,
		);
		
		$props = isset($raw_data["childs"]["properties"][0]["childs"]) ? $raw_data["childs"]["properties"][0]["childs"] : null;
		if (is_array($props)) {
			foreach ($props as $attr_name => $attr_value) {
				if (substr($attr_name, 0, 4) == "sma_") {
					if (substr($attr_name, -5) == "_type") {
						$properties[$attr_name] = isset($attr_value[0]["value"]) ? $attr_value[0]["value"] : null;
					}
					else {
						$properties[$attr_name] = isset($props[$attr_name . "_type"][0]["value"]) && $props[$attr_name . "_type"][0]["value"] == "array" ? self::parseArrayItems($attr_value) : (isset($attr_value[0]["value"]) ? $attr_value[0]["value"] : null);
					}
				}
			}
		}
		
		$properties = self::parseResultVariableProperties($raw_data, $properties);
		
		return $properties;
	}
	
	public function printCode($tasks, $stop_task_id, $prefix_tab = "", $options = null) {
		$data = isset($this->data) ? $this->data : null;
		
		$properties = isset($data["properties"]) ? $data["properties"] : null;
		$broker_method_obj_type = isset($properties["broker_method_obj_type"]) ? $properties["broker_method_obj_type"] : null;
		$module_id = isset($properties["module_id"]) ? $properties["module_id"] : null;
		$module_id_type = isset($properties["module_id_type"]) ? $properties["module_id_type"] : null;
		$service_id = isset($properties["service_id"]) ? $properties["service_id"] : null;
		$service_id_type = isset($properties["service_id_type"]) ? $properties["service_id_type"] : null;
		$service_method = isset($properties["service_method"]) ? $properties["service_method"] : null;
		$service_method_type = isset($properties["service_method_type"]) ? $properties["service_method_type"] : null;
		
		$code = "";
		if (($broker_method_obj_type == "exists_hbn_var" || ($module_id && $service_id)) && $service_method) {
			$var_name = self::getPropertiesResultVariableCode($properties);
		
			$method_obj = isset($properties["method_obj"]) ? $properties["method_obj"] : null;
			if ($method_obj) {
				$static_pos = strpos($method_obj, "::");
				$non_static_pos = strpos($method_obj, "->");
				$method_obj = substr($method_obj, 0, 1) != '$' && substr($method_obj, 0, 2) != '@$' && (!$static_pos || ($non_static_pos && $static_pos > $non_static_pos)) ? '$' . $method_obj : $method_obj;
				$method_obj .= "->";
			}
			
			$service_method = self::getVariableValueCode($service_method, $service_method_type);
			$service_method = $service_method_type == "string" ? str_replace('"', '', $service_method) : $service_method;
			
			$ma = isset(self::$METHODS_ARGS[$service_method]) ? self::$METHODS_ARGS[$service_method] : null;
			$ma = $ma ? $ma : array();
			
			$args = "";
			foreach ($ma as $arg_idx => $arg_name) {
				$an = strtolower($arg_name);
				
				$type = isset($properties["sma_" . $an . "_type"]) ? $properties["sma_" . $an . "_type"] : null;
				$value = isset($properties["sma_" . $an]) ? $properties["sma_" . $an] : null;
				
				$arg = $type == "array" ? self::getArrayString($value) : self::getVariableValueCode($value, $type);
				$arg = isset($arg) && strlen($arg) ? $arg : "null";
				$args .= ($args ? ", " : "") . $arg;
			}
			
			$code  = $prefix_tab . $var_name;
			$code .= $method_obj;
			if ($broker_method_obj_type != "exists_hbn_var") {
				$opts_type = isset($properties["options_type"]) ? $properties["options_type"] : null;
				$opts = isset($properties["options"]) ? $properties["options"] : null;
				
				if ($opts_type == "array")
					$opts = self::getArrayString($opts);
				else
					$opts = self::getVariableValueCode($opts, $opts_type);
				
				$code .= "callObject(";
				$code .= self::getVariableValueCode($module_id, $module_id_type) . ", ";
				$code .= self::getVariableValueCode($service_id, $service_id_type);
				$code .= $opts && $opts != "null" ? ", " . $opts : "";
				$code .= ")->";
			}
			$code .= "$service_method(" . $args . ");\n";
		}
		
		$exit_task_id = isset($data["exits"][self::DEFAULT_EXIT_ID]) ? $data["exits"][self::DEFAULT_EXIT_ID] : null;
		return $code . self::printTask($tasks, $exit_task_id, $stop_task_id, $prefix_tab, $options);
	}
}
?>
