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

namespace WorkFlowTask\programming\dbdaoaction;

include_once get_lib("org.phpframework.workflow.WorkFlowTask");

class WorkFlowTaskImpl extends \WorkFlowTask {
	
	public function createTaskPropertiesFromCodeStmt($stmt, $WorkFlowTaskCodeParser, &$exits = null, &$inner_tasks = null) {
		$props = $WorkFlowTaskCodeParser->getObjectMethodProps($stmt);
		
		if ($props) {
			$method_name = isset($props["method_name"]) ? $props["method_name"] : null;
			$possible_methods = array("insertObject", "updateObject", "deleteObject", "findObjects", "countObjects", "findObjectsColumnMax", "findRelationshipObjects", "countRelationshipObjects");
			
			if (in_array($method_name, $possible_methods) && empty($props["method_static"]) && $method_name[0] != '$' && substr($method_name, 0, 2) != '@$') {
				$args = isset($props["method_args"]) ? $props["method_args"] : null;
				$table_name = $table_name_type = $attributes = $attributes_type = $conditions = $conditions_type = $relations = $relations_type = $parent_conditions = $parent_conditions_type = $options = $options_type = null;
				
				if ($method_name == "insertObject" || $method_name == "findObjectsColumnMax") {
					$table_name = isset($args[0]["value"]) ? $args[0]["value"] : null;
					$table_name_type = isset($args[0]["type"]) ? $args[0]["type"] : null;
					$attributes = isset($args[1]["value"]) ? $args[1]["value"] : null;
					$attributes_type = isset($args[1]["type"]) ? $args[1]["type"] : null;
					$options = isset($args[2]["value"]) ? $args[2]["value"] : null;
					$options_type = isset($args[2]["type"]) ? $args[2]["type"] : null;
				}
				else if ($method_name == "updateObject" || $method_name == "findObjects") {
					$table_name = isset($args[0]["value"]) ? $args[0]["value"] : null;
					$table_name_type = isset($args[0]["type"]) ? $args[0]["type"] : null;
					$attributes = isset($args[1]["value"]) ? $args[1]["value"] : null;
					$attributes_type = isset($args[1]["type"]) ? $args[1]["type"] : null;
					$conditions = isset($args[2]["value"]) ? $args[2]["value"] : null;
					$conditions_type = isset($args[2]["type"]) ? $args[2]["type"] : null;
					$options = isset($args[3]["value"]) ? $args[3]["value"] : null;
					$options_type = isset($args[3]["type"]) ? $args[3]["type"] : null;
				}
				else if ($method_name == "deleteObject" || $method_name == "countObjects") {
					$table_name = isset($args[0]["value"]) ? $args[0]["value"] : null;
					$table_name_type = isset($args[0]["type"]) ? $args[0]["type"] : null;
					$conditions = isset($args[1]["value"]) ? $args[1]["value"] : null;
					$conditions_type = isset($args[1]["type"]) ? $args[1]["type"] : null;
					$options = isset($args[2]["value"]) ? $args[2]["value"] : null;
					$options_type = isset($args[2]["type"]) ? $args[2]["type"] : null;
				}
				else if ($method_name == "findRelationshipObjects" || $method_name == "countRelationshipObjects") {
					$table_name = isset($args[0]["value"]) ? $args[0]["value"] : null;
					$table_name_type = isset($args[0]["type"]) ? $args[0]["type"] : null;
					$relations = isset($args[1]["value"]) ? $args[1]["value"] : null;
					$relations_type = isset($args[1]["type"]) ? $args[1]["type"] : null;
					$parent_conditions = isset($args[2]["value"]) ? $args[2]["value"] : null;
					$parent_conditions_type = isset($args[2]["type"]) ? $args[2]["type"] : null;
					$options = isset($args[3]["value"]) ? $args[3]["value"] : null;
					$options_type = isset($args[3]["type"]) ? $args[3]["type"] : null;
				}
				
				if ($attributes_type == "array") {
					$arr_stmts = $WorkFlowTaskCodeParser->getPHPMultipleParser()->parse("<?php\n" . $attributes . "\n?>");
					$items = $WorkFlowTaskCodeParser->getStmtArrayItems($arr_stmts[0]);
					$attributes = $WorkFlowTaskCodeParser->getArrayItems($items);
				}
				
				if ($conditions_type == "array") {
					$arr_stmts = $WorkFlowTaskCodeParser->getPHPMultipleParser()->parse("<?php\n" . $conditions . "\n?>");
					$items = $WorkFlowTaskCodeParser->getStmtArrayItems($arr_stmts[0]);
					$conditions = $WorkFlowTaskCodeParser->getArrayItems($items);
				}
				
				if ($relations_type == "array") {
					$arr_stmts = $WorkFlowTaskCodeParser->getPHPMultipleParser()->parse("<?php\n" . $relations . "\n?>");
					$items = $WorkFlowTaskCodeParser->getStmtArrayItems($arr_stmts[0]);
					$relations = $WorkFlowTaskCodeParser->getArrayItems($items);
				}
				
				if ($parent_conditions_type == "array") {
					$arr_stmts = $WorkFlowTaskCodeParser->getPHPMultipleParser()->parse("<?php\n" . $parent_conditions . "\n?>");
					$items = $WorkFlowTaskCodeParser->getStmtArrayItems($arr_stmts[0]);
					$parent_conditions = $WorkFlowTaskCodeParser->getArrayItems($items);
				}
				
				if ($options_type == "array") {
					$opt_stmts = $WorkFlowTaskCodeParser->getPHPMultipleParser()->parse("<?php\n" . $options . "\n?>");
					$items = $WorkFlowTaskCodeParser->getStmtArrayItems($opt_stmts[0]);
					$options = $WorkFlowTaskCodeParser->getArrayItems($items);
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
		$raw_data = isset($task["raw_data"]) ? $task["raw_data"] : null;
		
		$attributes_type = isset($raw_data["childs"]["properties"][0]["childs"]["attributes_type"][0]["value"]) ? $raw_data["childs"]["properties"][0]["childs"]["attributes_type"][0]["value"] : null;
		if ($attributes_type == "array") {
			$attributes = isset($raw_data["childs"]["properties"][0]["childs"]["attributes"]) ? $raw_data["childs"]["properties"][0]["childs"]["attributes"] : null;
			$attributes = self::parseArrayItems($attributes);
		}
		else
			$attributes = isset($raw_data["childs"]["properties"][0]["childs"]["attributes"][0]["value"]) ? $raw_data["childs"]["properties"][0]["childs"]["attributes"][0]["value"] : null;
		
		$conditions_type = isset($raw_data["childs"]["properties"][0]["childs"]["conditions_type"][0]["value"]) ? $raw_data["childs"]["properties"][0]["childs"]["conditions_type"][0]["value"] : null;
		if ($conditions_type == "array") {
			$conditions = isset($raw_data["childs"]["properties"][0]["childs"]["conditions"]) ? $raw_data["childs"]["properties"][0]["childs"]["conditions"] : null;
			$conditions = self::parseArrayItems($conditions);
		}
		else
			$conditions = isset($raw_data["childs"]["properties"][0]["childs"]["conditions"][0]["value"]) ? $raw_data["childs"]["properties"][0]["childs"]["conditions"][0]["value"] : null;
		
		$relations_type = isset($raw_data["childs"]["properties"][0]["childs"]["relations_type"][0]["value"]) ? $raw_data["childs"]["properties"][0]["childs"]["relations_type"][0]["value"] : null;
		if ($relations_type == "array") {
			$relations = isset($raw_data["childs"]["properties"][0]["childs"]["relations"]) ? $raw_data["childs"]["properties"][0]["childs"]["relations"] : null;
			$relations = self::parseArrayItems($relations);
		}
		else
			$relations = isset($raw_data["childs"]["properties"][0]["childs"]["relations"][0]["value"]) ? $raw_data["childs"]["properties"][0]["childs"]["relations"][0]["value"] : null;
		
		$parent_conditions_type = isset($raw_data["childs"]["properties"][0]["childs"]["parent_conditions_type"][0]["value"]) ? $raw_data["childs"]["properties"][0]["childs"]["parent_conditions_type"][0]["value"] : null;
		if ($parent_conditions_type == "array") {
			$parent_conditions = isset($raw_data["childs"]["properties"][0]["childs"]["parent_conditions"]) ? $raw_data["childs"]["properties"][0]["childs"]["parent_conditions"] : null;
			$parent_conditions = self::parseArrayItems($parent_conditions);
		}
		else
			$parent_conditions = isset($raw_data["childs"]["properties"][0]["childs"]["parent_conditions"][0]["value"]) ? $raw_data["childs"]["properties"][0]["childs"]["parent_conditions"][0]["value"] : null;
		
		$options_type = isset($raw_data["childs"]["properties"][0]["childs"]["options_type"][0]["value"]) ? $raw_data["childs"]["properties"][0]["childs"]["options_type"][0]["value"] : null;
		if ($options_type == "array") {
			$options = isset($raw_data["childs"]["properties"][0]["childs"]["options"]) ? $raw_data["childs"]["properties"][0]["childs"]["options"] : null;
			$options = self::parseArrayItems($options);
		}
		else
			$options = isset($raw_data["childs"]["properties"][0]["childs"]["options"][0]["value"]) ? $raw_data["childs"]["properties"][0]["childs"]["options"][0]["value"] : null;
		
		$properties = array(
			"method_obj" => isset($raw_data["childs"]["properties"][0]["childs"]["method_obj"][0]["value"]) ? $raw_data["childs"]["properties"][0]["childs"]["method_obj"][0]["value"] : null,
			"method_name" => isset($raw_data["childs"]["properties"][0]["childs"]["method_name"][0]["value"]) ? $raw_data["childs"]["properties"][0]["childs"]["method_name"][0]["value"] : null,
			"table_name" => isset($raw_data["childs"]["properties"][0]["childs"]["table_name"][0]["value"]) ? $raw_data["childs"]["properties"][0]["childs"]["table_name"][0]["value"] : null,
			"table_name_type" => isset($raw_data["childs"]["properties"][0]["childs"]["table_name_type"][0]["value"]) ? $raw_data["childs"]["properties"][0]["childs"]["table_name_type"][0]["value"] : null,
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
		$data = isset($this->data) ? $this->data : null;
		//print_r($data);die();
		
		$properties = isset($data["properties"]) ? $data["properties"] : null;
		$method_name = isset($properties["method_name"]) ? $properties["method_name"] : null;
		
		$code = "";
		if ($method_name) {
			$var_name = self::getPropertiesResultVariableCode($properties);
		
			$method_obj = isset($properties["method_obj"]) ? $properties["method_obj"] : null;
			if ($method_obj) {
				$static_pos = strpos($method_obj, "::");
				$non_static_pos = strpos($method_obj, "->");
				$method_obj = substr($method_obj, 0, 1) != '$' && substr($method_obj, 0, 2) != '@$' && (!$static_pos || ($non_static_pos && $static_pos > $non_static_pos)) ? '$' . $method_obj : $method_obj;
				$method_obj .= "->";
			}
			
			$attributes_type = isset($properties["attributes_type"]) ? $properties["attributes_type"] : null;
			$attributes = isset($properties["attributes"]) ? $properties["attributes"] : null;
			if ($attributes_type == "array") 
				$attributes = self::getArrayString($attributes);
			else 
				$attributes = self::getVariableValueCode($attributes, $attributes_type);
			
			$conditions_type = isset($properties["conditions_type"]) ? $properties["conditions_type"] : null;
			$conditions = isset($properties["conditions"]) ? $properties["conditions"] : null;
			if ($conditions_type == "array") 
				$conditions = self::getArrayString($conditions);
			else 
				$conditions = self::getVariableValueCode($conditions, $conditions_type);
			
			$relations_type = isset($properties["relations_type"]) ? $properties["relations_type"] : null;
			$relations = isset($properties["relations"]) ? $properties["relations"] : null;
			if ($relations_type == "array") 
				$relations = self::getArrayString($relations);
			else 
				$relations = self::getVariableValueCode($relations, $relations_type);
			
			$parent_conditions_type = isset($properties["parent_conditions_type"]) ? $properties["parent_conditions_type"] : null;
			$parent_conditions = isset($properties["parent_conditions"]) ? $properties["parent_conditions"] : null;
			if ($parent_conditions_type == "array") 
				$parent_conditions = self::getArrayString($parent_conditions);
			else 
				$parent_conditions = self::getVariableValueCode($parent_conditions, $parent_conditions_type);
			
			$opts_type = isset($properties["options_type"]) ? $properties["options_type"] : null;
			$opts = isset($properties["options"]) ? $properties["options"] : null;
			if ($opts_type == "array") 
				$opts = self::getArrayString($opts);
			else 
				$opts = self::getVariableValueCode($opts, $opts_type);
			
			$table_name_type = isset($properties["table_name_type"]) ? $properties["table_name_type"] : null;
			$table_name = isset($properties["table_name"]) ? $properties["table_name"] : null;
			
			$code  = $prefix_tab . $var_name;
			$code .= $method_obj . $method_name . "(";
			$code .= self::getVariableValueCode($table_name, $table_name_type);
			
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
		
		$exit_task_id = isset($data["exits"][self::DEFAULT_EXIT_ID]) ? $data["exits"][self::DEFAULT_EXIT_ID] : null;
		return $code . self::printTask($tasks, $exit_task_id, $stop_task_id, $prefix_tab, $options);
	}
}
?>
