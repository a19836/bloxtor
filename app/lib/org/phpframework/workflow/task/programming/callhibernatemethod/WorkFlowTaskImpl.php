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
			$method_name = $props["method_name"];
			
			if (isset(self::$METHODS_ARGS[$method_name]) && empty($props["method_static"])) {
				$exists = $status = is_array($WorkFlowTaskCodeParser->shared_objects["hibernate_vars"]) && in_array($props["method_obj"], $WorkFlowTaskCodeParser->shared_objects["hibernate_vars"]);
				$code = "<?php\n" . $props["method_obj"] . "\n?>";
				
				if (!$status) {
					$tps = $WorkFlowTaskCodeParser->getParsedCodeAsArray($code);
					$status = $tps["task"][0]["childs"]["tag"][0]["value"] == "callhibernateobject";
				}
				
				if ($status) {
					if ($exists) {
						$props["broker_method_obj_type"] = "exists_hbn_var";
					}
					else {
						$method_obj_stmts = $WorkFlowTaskCodeParser->getPHPParserEmulative()->parse($code);
						$method_obj_props = $WorkFlowTaskCodeParser->getObjectMethodProps($method_obj_stmts[0]);
					
						$props["method_obj"] = $method_obj_props["method_obj"];
						$props["module_id"] = self::getConfiguredParsedType($method_obj_props["method_args"][0]["value"]);
						$props["module_id_type"] = $method_obj_props["method_args"][0]["type"];
						$props["service_id"] = self::getConfiguredParsedType($method_obj_props["method_args"][1]["value"]);
						$props["service_id_type"] = $method_obj_props["method_args"][1]["type"];
						$props["options"] = $method_obj_props["method_args"][2]["value"];
						$props["options_type"] = self::getConfiguredParsedType($method_obj_props["method_args"][2]["type"], array("", "string", "variable", "array"));
					}
					
					$props["service_method"] = $props["method_name"];
					$props["service_method_type"] = "string";
					
					if ($props["options_type"] == "array") {
						$opt_stmts = $WorkFlowTaskCodeParser->getPHPParserEmulative()->parse("<?php\n" . $props["options"] . "\n?>");
						//print_r($opt_stmts);
						$props["options"] = $WorkFlowTaskCodeParser->getArrayItems($opt_stmts[0]->items);
					}
					
					$ma = self::$METHODS_ARGS[$method_name];
					$ma = $ma ? $ma : array();
					foreach ($ma as $arg_idx => $arg_name) {
						$an = strtolower($arg_name);
						$props["sma_" . $an] = $props["method_args"][$arg_idx]["value"];
						$props["sma_" . $an . "_type"] = $props["method_args"][$arg_idx]["type"];
						
						if ($props["sma_" . $an . "_type"] == "array") {
							$sma_stmts = $WorkFlowTaskCodeParser->getPHPParserEmulative()->parse("<?php\n" . $props["sma_" . $an] . "\n?>");
							$props["sma_" . $an] = $WorkFlowTaskCodeParser->getArrayItems($sma_stmts[0]->items);
						}
						
						if ($an == "data" || $an == "data_options" || $an == "parent_ids" || $an == "options")
							$props["sma_" . $an . "_type"] = self::getConfiguredParsedType($props["sma_" . $an . "_type"], array("", "string", "variable", "array"));
						else
							$props["sma_" . $an . "_type"] = self::getConfiguredParsedType($props["sma_" . $an . "_type"]);
					}
					
					unset($props["method_name"]);
					unset($props["method_args"]);
					unset($props["method_static"]);
					
					$props["label"] = "Call " . ($props["service_id"] ? self::prepareTaskPropertyValueLabelFromCodeStmt($props["service_id"]) . "." : "") . self::prepareTaskPropertyValueLabelFromCodeStmt($props["service_method"]) . ($props["module_id"] ? " in " . self::prepareTaskPropertyValueLabelFromCodeStmt($props["module_id"]) : "");
					
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
		$raw_data = $task["raw_data"];
		
		$options_type = $raw_data["childs"]["properties"][0]["childs"]["options_type"][0]["value"];
		if ($options_type == "array") {
			$options = $raw_data["childs"]["properties"][0]["childs"]["options"];
			$options = self::parseArrayItems($options);
		}
		else {
			$options = $raw_data["childs"]["properties"][0]["childs"]["options"][0]["value"];
		}
		
		$properties = array(
			"broker_method_obj_type" => $raw_data["childs"]["properties"][0]["childs"]["broker_method_obj_type"][0]["value"],
			"method_obj" => $raw_data["childs"]["properties"][0]["childs"]["method_obj"][0]["value"],
			"module_id" => $raw_data["childs"]["properties"][0]["childs"]["module_id"][0]["value"],
			"module_id_type" => $raw_data["childs"]["properties"][0]["childs"]["module_id_type"][0]["value"],
			"service_id" => $raw_data["childs"]["properties"][0]["childs"]["service_id"][0]["value"],
			"service_id_type" => $raw_data["childs"]["properties"][0]["childs"]["service_id_type"][0]["value"],
			"options" => $options,
			"options_type" => $options_type,
			"service_method" => $raw_data["childs"]["properties"][0]["childs"]["service_method"][0]["value"],
			"service_method_type" => $raw_data["childs"]["properties"][0]["childs"]["service_method_type"][0]["value"],
		);
		
		$props = $raw_data["childs"]["properties"][0]["childs"];
		if (is_array($props)) {
			foreach ($props as $attr_name => $attr_value) {
				if (substr($attr_name, 0, 4) == "sma_") {
					if (substr($attr_name, -5) == "_type") {
						$properties[$attr_name] = $attr_value[0]["value"];
					}
					else {
						$properties[$attr_name] = $props[$attr_name . "_type"][0]["value"] == "array" ? self::parseArrayItems($attr_value) : $attr_value[0]["value"];
					}
				}
			}
		}
		
		$properties = self::parseResultVariableProperties($raw_data, $properties);
		
		return $properties;
	}
	
	public function printCode($tasks, $stop_task_id, $prefix_tab = "", $options = null) {
		$data = $this->data;
		
		$properties = $data["properties"];
		
		$code = "";
		if (($properties["broker_method_obj_type"] == "exists_hbn_var" || ($properties["module_id"] && $properties["service_id"])) && $properties["service_method"]) {
			$var_name = self::getPropertiesResultVariableCode($properties);
		
			$method_obj = $properties["method_obj"];
			if ($method_obj) {
				$static_pos = strpos($method_obj, "::");
				$non_static_pos = strpos($method_obj, "->");
				$method_obj = substr($method_obj, 0, 1) != '$' && (!$static_pos || ($non_static_pos && $static_pos > $non_static_pos)) ? '$' . $method_obj : $method_obj;
				$method_obj .= "->";
			}
			
			$service_method = self::getVariableValueCode($properties["service_method"], $properties["service_method_type"]);
			$service_method = $properties["service_method_type"] == "string" ? str_replace('"', '', $service_method) : $service_method;
			
			$ma = self::$METHODS_ARGS[$service_method];
			$ma = $ma ? $ma : array();
			
			$args = "";
			foreach ($ma as $arg_idx => $arg_name) {
				$an = strtolower($arg_name);
				
				$type = $properties["sma_" . $an . "_type"];
				$value = $properties["sma_" . $an];
				
				$arg = $type == "array" ? self::getArrayString($value) : self::getVariableValueCode($value, $type);
				$arg = isset($arg) && strlen($arg) ? $arg : "null";
				$args .= ($args ? ", " : "") . $arg;
			}
			
			$code  = $prefix_tab . $var_name;
			$code .= $method_obj;
			if ($properties["broker_method_obj_type"] != "exists_hbn_var") {
				$opts_type = $properties["options_type"];
				if ($opts_type == "array")
					$opts = self::getArrayString($properties["options"]);
				else
					$opts = self::getVariableValueCode($properties["options"], $opts_type);
				
				$code .= "callObject(";
				$code .= self::getVariableValueCode($properties["module_id"], $properties["module_id_type"]) . ", ";
				$code .= self::getVariableValueCode($properties["service_id"], $properties["service_id_type"]);
				$code .= $opts && $opts != "null" ? ", " . $opts : "";
				$code .= ")->";
			}
			$code .= "$service_method(" . $args . ");\n";
		}
		
		return $code . self::printTask($tasks, $data["exits"][self::DEFAULT_EXIT_ID], $stop_task_id, $prefix_tab, $options);
	}
}
?>
