<?php
namespace WorkFlowTask\programming\dbdaoaction;

include_once get_lib("org.phpframework.workflow.WorkFlowTask");

class WorkFlowTaskImpl extends \WorkFlowTask {
	
	public function createTaskPropertiesFromCodeStmt($stmt, $WorkFlowTaskCodeParser, &$exits = null, &$inner_tasks = null) {
		$props = $WorkFlowTaskCodeParser->getObjectMethodProps($stmt);
		
		if ($props) {
			$method_name = $props["method_name"];
			$possible_methods = array("insertObject", "updateObject", "deleteObject", "findObjects", "countObjects", "findObjectsColumnMax", "findRelationshipObjects", "countRelationshipObjects");
			
			if (in_array($method_name, $possible_methods) && empty($props["method_static"]) && $method_name[0] != '$') {
				$args = $props["method_args"];
				
				if ($method_name == "insertObject" || $method_name == "findObjectsColumnMax") {
					$table_name = $args[0]["value"];
					$table_name_type = $args[0]["type"];
					$attributes = $args[1]["value"];
					$attributes_type = $args[1]["type"];
					$options = $args[2]["value"];
					$options_type = $args[2]["type"];
				}
				else if ($method_name == "updateObject" || $method_name == "findObjects") {
					$table_name = $args[0]["value"];
					$table_name_type = $args[0]["type"];
					$attributes = $args[1]["value"];
					$attributes_type = $args[1]["type"];
					$conditions = $args[2]["value"];
					$conditions_type = $args[2]["type"];
					$options = $args[3]["value"];
					$options_type = $args[3]["type"];
				}
				else if ($method_name == "deleteObject" || $method_name == "countObjects") {
					$table_name = $args[0]["value"];
					$table_name_type = $args[0]["type"];
					$conditions = $args[1]["value"];
					$conditions_type = $args[1]["type"];
					$options = $args[2]["value"];
					$options_type = $args[2]["type"];
				}
				else if ($method_name == "findRelationshipObjects" || $method_name == "countRelationshipObjects") {
					$table_name = $args[0]["value"];
					$table_name_type = $args[0]["type"];
					$relations = $args[1]["value"];
					$relations_type = $args[1]["type"];
					$parent_conditions = $args[2]["value"];
					$parent_conditions_type = $args[2]["type"];
					$options = $args[3]["value"];
					$options_type = $args[3]["type"];
				}
				
				if ($attributes_type == "array") {
					$arr_stmts = $WorkFlowTaskCodeParser->getPHPParserEmulative()->parse("<?php\n" . $attributes . "\n?>");
					$attributes = $WorkFlowTaskCodeParser->getArrayItems($arr_stmts[0]->items);
				}
				
				if ($conditions_type == "array") {
					$arr_stmts = $WorkFlowTaskCodeParser->getPHPParserEmulative()->parse("<?php\n" . $conditions . "\n?>");
					$conditions = $WorkFlowTaskCodeParser->getArrayItems($arr_stmts[0]->items);
				}
				
				if ($relations_type == "array") {
					$arr_stmts = $WorkFlowTaskCodeParser->getPHPParserEmulative()->parse("<?php\n" . $relations . "\n?>");
					$relations = $WorkFlowTaskCodeParser->getArrayItems($arr_stmts[0]->items);
				}
				
				if ($parent_conditions_type == "array") {
					$arr_stmts = $WorkFlowTaskCodeParser->getPHPParserEmulative()->parse("<?php\n" . $parent_conditions . "\n?>");
					$parent_conditions = $WorkFlowTaskCodeParser->getArrayItems($arr_stmts[0]->items);
				}
				
				if ($options_type == "array") {
					$opt_stmts = $WorkFlowTaskCodeParser->getPHPParserEmulative()->parse("<?php\n" . $options . "\n?>");
					$options = $WorkFlowTaskCodeParser->getArrayItems($opt_stmts[0]->items);
				}
				
				unset($props["method_args"]);
				unset($props["method_static"]);
				
				$props["table_name"] = $table_name;
				$props["table_name_type"] = self::getConfiguredParsedType($table_name_type);
				$props["attributes"] = $attributes;
				$props["attributes_type"] = self::getConfiguredParsedType($attributes_type, array("", "string", "variable", "array"));
				$props["conditions"] = $conditions;
				$props["conditions_type"] = self::getConfiguredParsedType($conditions_type, array("", "string", "variable", "array"));
				$props["relations"] = $relations;
				$props["relations_type"] = self::getConfiguredParsedType($relations_type, array("", "string", "variable", "array"));
				$props["parent_conditions"] = $parent_conditions;
				$props["parent_conditions_type"] = self::getConfiguredParsedType($parent_conditions_type, array("", "string", "variable", "array"));
				$props["options"] = $options;
				$props["options_type"] = self::getConfiguredParsedType($options_type, array("", "string", "variable", "array"));
				
				$props["label"] = "Call " . self::prepareTaskPropertyValueLabelFromCodeStmt($method_name) . " for " . self::prepareTaskPropertyValueLabelFromCodeStmt($table_name);
				
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
		
		$attributes_type = $raw_data["childs"]["properties"][0]["childs"]["attributes_type"][0]["value"];
		if ($attributes_type == "array") {
			$attributes = $raw_data["childs"]["properties"][0]["childs"]["attributes"];
			$attributes = self::parseArrayItems($attributes);
		}
		else
			$attributes = $raw_data["childs"]["properties"][0]["childs"]["attributes"][0]["value"];
		
		$conditions_type = $raw_data["childs"]["properties"][0]["childs"]["conditions_type"][0]["value"];
		if ($conditions_type == "array") {
			$conditions = $raw_data["childs"]["properties"][0]["childs"]["conditions"];
			$conditions = self::parseArrayItems($conditions);
		}
		else
			$conditions = $raw_data["childs"]["properties"][0]["childs"]["conditions"][0]["value"];
		
		$relations_type = $raw_data["childs"]["properties"][0]["childs"]["relations_type"][0]["value"];
		if ($relations_type == "array") {
			$relations = $raw_data["childs"]["properties"][0]["childs"]["relations"];
			$relations = self::parseArrayItems($relations);
		}
		else
			$relations = $raw_data["childs"]["properties"][0]["childs"]["relations"][0]["value"];
		
		$parent_conditions_type = $raw_data["childs"]["properties"][0]["childs"]["parent_conditions_type"][0]["value"];
		if ($parent_conditions_type == "array") {
			$parent_conditions = $raw_data["childs"]["properties"][0]["childs"]["parent_conditions"];
			$parent_conditions = self::parseArrayItems($parent_conditions);
		}
		else
			$parent_conditions = $raw_data["childs"]["properties"][0]["childs"]["parent_conditions"][0]["value"];
		
		$options_type = $raw_data["childs"]["properties"][0]["childs"]["options_type"][0]["value"];
		if ($options_type == "array") {
			$options = $raw_data["childs"]["properties"][0]["childs"]["options"];
			$options = self::parseArrayItems($options);
		}
		else
			$options = $raw_data["childs"]["properties"][0]["childs"]["options"][0]["value"];
		
		$properties = array(
			"method_obj" => $raw_data["childs"]["properties"][0]["childs"]["method_obj"][0]["value"],
			"method_name" => $raw_data["childs"]["properties"][0]["childs"]["method_name"][0]["value"],
			"table_name" => $raw_data["childs"]["properties"][0]["childs"]["table_name"][0]["value"],
			"table_name_type" => $raw_data["childs"]["properties"][0]["childs"]["table_name_type"][0]["value"],
			"attributes" => $attributes,
			"attributes_type" => $attributes_type,
			"conditions" => $conditions,
			"conditions_type" => $conditions_type,
			"relations" => $relations,
			"relations_type" => $relations_type,
			"parent_conditions" => $parent_conditions,
			"parent_conditions_type" => $parent_conditions_type,
			"options" => $options,
			"options_type" => $options_type,
		);
		
		$properties = self::parseResultVariableProperties($raw_data, $properties);
		
		return $properties;
	}
	
	public function printCode($tasks, $stop_task_id, $prefix_tab = "", $options = null) {
		$data = $this->data;
		//print_r($data);die();
		
		$properties = $data["properties"];
		$method_name = $properties["method_name"];
		
		$code = "";
		if ($method_name) {
			$var_name = self::getPropertiesResultVariableCode($properties);
		
			$method_obj = $properties["method_obj"];
			if ($method_obj) {
				$static_pos = strpos($method_obj, "::");
				$non_static_pos = strpos($method_obj, "->");
				$method_obj = substr($method_obj, 0, 1) != '$' && (!$static_pos || ($non_static_pos && $static_pos > $non_static_pos)) ? '$' . $method_obj : $method_obj;
				$method_obj .= "->";
			}
			
			$attributes_type = $properties["attributes_type"];
			if ($attributes_type == "array") 
				$attributes = self::getArrayString($properties["attributes"]);
			else 
				$attributes = self::getVariableValueCode($properties["attributes"], $attributes_type);
			
			$conditions_type = $properties["conditions_type"];
			if ($conditions_type == "array") 
				$conditions = self::getArrayString($properties["conditions"]);
			else 
				$conditions = self::getVariableValueCode($properties["conditions"], $conditions_type);
			
			$relations_type = $properties["relations_type"];
			if ($relations_type == "array") 
				$relations = self::getArrayString($properties["relations"]);
			else 
				$relations = self::getVariableValueCode($properties["relations"], $relations_type);
			
			$parent_conditions_type = $properties["parent_conditions_type"];
			if ($parent_conditions_type == "array") 
				$parent_conditions = self::getArrayString($properties["parent_conditions"]);
			else 
				$parent_conditions = self::getVariableValueCode($properties["parent_conditions"], $parent_conditions_type);
			
			$opts_type = $properties["options_type"];
			if ($opts_type == "array") 
				$opts = self::getArrayString($properties["options"]);
			else 
				$opts = self::getVariableValueCode($properties["options"], $opts_type);
			
			$code  = $prefix_tab . $var_name;
			$code .= $method_obj . $method_name . "(";
			$code .= self::getVariableValueCode($properties["table_name"], $properties["table_name_type"]);
			
			if ($method_name == "insertObject" || $method_name == "findObjectsColumnMax") {
				$code .= ", " . $attributes;
			}
			else if ($method_name == "updateObject" || $method_name == "findObjects") {
				$code .= ", " . $attributes;
				$code .= ", " . $conditions;
			}
			else if ($method_name == "deleteObject" || $method_name == "countObjects") {
				$code .= ", " . $conditions;
			}
			else if ($method_name == "findRelationshipObjects" || $method_name == "countRelationshipObjects") {
				$code .= ", " . $relations;
				$code .= ", " . $parent_conditions;
			}
			
			$code .= $opts && $opts != "null" ? ", " . $opts : "";
			$code .= ");\n";
		}
		
		return $code . self::printTask($tasks, $data["exits"][self::DEFAULT_EXIT_ID], $stop_task_id, $prefix_tab, $options);
	}
}
?>
