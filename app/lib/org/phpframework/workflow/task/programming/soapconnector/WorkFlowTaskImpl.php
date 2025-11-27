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

namespace WorkFlowTask\programming\soapconnector;

include_once get_lib("org.phpframework.workflow.WorkFlowTask");

class WorkFlowTaskImpl extends \WorkFlowTask {
	
	public function createTaskPropertiesFromCodeStmt($stmt, $WorkFlowTaskCodeParser, &$exits = null, &$inner_tasks = null) {
		$props = $WorkFlowTaskCodeParser->getObjectMethodProps($stmt);
		
		if ($props) {
			$method_name = isset($props["method_name"]) ? $props["method_name"] : null;
			
			if ($method_name == "connect" && !empty($props["method_static"]) && isset($props["method_obj"]) && $props["method_obj"] == "SoapConnector") {
				$args = isset($props["method_args"]) ? $props["method_args"] : null;
				
				$data = isset($args[0]["value"]) ? $args[0]["value"] : null;
				$data_type = isset($args[0]["type"]) ? $args[0]["type"] : null;
				
				$result_type = isset($args[1]["value"]) ? $args[1]["value"] : null;
				$result_type_type = isset($args[1]["type"]) ? $args[1]["type"] : null;
				
				if ($data_type == "array") {
					$data_stmts = $WorkFlowTaskCodeParser->getPHPMultipleParser()->parse("<?php\n" . $data . "\n?>");
					$items = $WorkFlowTaskCodeParser->getStmtArrayItems($data_stmts[0]);
					$data_arr = $WorkFlowTaskCodeParser->getArrayItems($items);
					$data = array();
					
					if (is_array($data_arr)) {
						$t = count($data_arr);
						for ($i = 0; $i < $t; $i++) {
							$item = $data_arr[$i];
							$key = isset($item["key"]) ? strtolower($item["key"]) : "";
							
							if (isset($item["items"])) {
								$value = $item["items"];
								$value_type = "array";
								
								if ($key == "options") {
									$new_value = array();
									
									foreach ($value as $k => $v)
										$new_value[] = array(
											"name" => isset($v["key"]) ? $v["key"] : null,
											"value" => !empty($v["items"]) ? $v["items"] : (isset($v["value"]) ? $v["value"] : null),
											"var_type" => !empty($v["items"]) ? "array" : (isset($v["value_type"]) ? $v["value_type"] : null),
										);
									
									$value = $new_value;
								}
								else if ($key == "headers") {
									$new_value = array();
									
									foreach ($value as $k => $v) 
										if (!empty($v["items"])) {
											$items = $v["items"];
											$item_value = array();
											
											foreach ($items as $ik => $iv) {
												$item_key = isset($iv["key"]) ? $iv["key"] : null;
												$item_value[ $item_key ] = !empty($iv["items"]) ? $iv["items"] : (isset($iv["value"]) ? $iv["value"] : null);
												$item_value[ $item_key . "_type" ] = !empty($iv["items"]) ? "array" : (isset($iv["value_type"]) ? $iv["value_type"] : null);
											}
											
											$new_value[] = $item_value;
										}
									
									$value = $new_value;
								}
							}
							else {
								$value = isset($item["value"]) ? $item["value"] : null;
								$value_type = isset($item["value_type"]) ? $item["value_type"] : null;
							}
							
							$data[ $key ] = $value;
							$data[ $key . "_type" ] = $value_type;
						}	
					}
				}
				
				unset($props["method_obj"]);
				unset($props["method_name"]);
				unset($props["method_args"]);
				unset($props["method_static"]);
				
				$props["data"] = $data;
				$props["data_type"] = $data_type;
				$props["result_type"] = $result_type;
				$props["result_type_type"] = self::getConfiguredParsedType($result_type_type);
				
				$props["label"] = "Call soap request";
				
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
		
		$data_type = isset($raw_data["childs"]["properties"][0]["childs"]["data_type"][0]["value"]) ? $raw_data["childs"]["properties"][0]["childs"]["data_type"][0]["value"] : null;
		if ($data_type == "options" || $data_type == "array") {
			$aux = \MyXML::complexArrayToBasicArray($raw_data["childs"]["properties"][0]["childs"]);
			$data = $aux["data"];
			$data_type = "options";
		}
		else
			$data = isset($raw_data["childs"]["properties"][0]["childs"]["data"][0]["value"]) ? $raw_data["childs"]["properties"][0]["childs"]["data"][0]["value"] : null;
		
		$properties = array(
			"data" => $data,
			"data_type" => $data_type,
			"result_type" => isset($raw_data["childs"]["properties"][0]["childs"]["result_type"][0]["value"]) ? $raw_data["childs"]["properties"][0]["childs"]["result_type"][0]["value"] : null,
			"result_type_type" => isset($raw_data["childs"]["properties"][0]["childs"]["result_type_type"][0]["value"]) ? $raw_data["childs"]["properties"][0]["childs"]["result_type_type"][0]["value"] : null,
		);
		
		$properties = self::parseResultVariableProperties($raw_data, $properties);
		
		return $properties;
	}
	
	public function printCode($tasks, $stop_task_id, $prefix_tab = "", $options = null) {
		$data = isset($this->data) ? $this->data : null;
		
		$properties = isset($data["properties"]) ? $data["properties"] : null;
		
		$var_name = self::getPropertiesResultVariableCode($properties);
		
		$dt_type = isset($properties["data_type"]) ? $properties["data_type"] : null;
		$dt = isset($properties["data"]) ? $properties["data"] : null;
		
		if ($dt_type == "options") {
			$opts = array();
			$headers = array();
			$remote_function_args = null;
			
			$dt_options = isset($dt["options"]) ? $dt["options"] : null;
			$dt_options_type = isset($dt["options_type"]) ? $dt["options_type"] : null;
			
			if (is_array($dt_options)) {
				$is_assoc = $dt_options && array_keys($dt_options) !== range(0, count($dt_options) - 1);
				if ($is_assoc)
					$dt_options = array($dt_options);
				
				foreach ($dt_options as $option) {
					$option_name = isset($option["name"]) ? $option["name"] : null;
					$option_value = isset($option["value"]) ? $option["value"] : null;
					$option_var_type = isset($option["var_type"]) ? $option["var_type"] : null;
					
					$opts[$option_name] = self::getVariableValueCode($option_value, $option_var_type);
				}
			}
			else
				$opts = self::getVariableValueCode($dt_options, $dt_options_type);
			
			$dt_headers = isset($dt["headers"]) ? $dt["headers"] : null;
			$dt_headers_type = isset($dt["headers_type"]) ? $dt["headers_type"] : null;
			
			if (is_array($dt_headers)) {
				$is_assoc = $dt_headers && array_keys($dt_headers) !== range(0, count($dt["headers"]) - 1);
				if ($is_assoc)
					$dt_headers = array($dt_headers);
				
				foreach ($dt_headers as $header) {
					$parameters = null;
					$parameters_type = isset($header["parameters_type"]) ? $header["parameters_type"] : null;
					$header_parameters = isset($header["parameters"]) ? $header["parameters"] : null;
					
					if ($parameters_type == "array") {
						$is_assoc = $header_parameters && array_keys($header_parameters) !== range(0, count($header_parameters) - 1);
						if ($is_assoc)
							$header_parameters = array($header_parameters);
						
						$parameters = trim(self::getArrayString($header_parameters, "$prefix_tab\t\t\t"));
					}
					else
						$parameters = self::getVariableValueCode($header_parameters, $parameters_type);
					
					$header_namespace = isset($header["namespace"]) ? $header["namespace"] : null;
					$header_namespace_type = isset($header["namespace_type"]) ? $header["namespace_type"] : null;
					$header_name = isset($header["name"]) ? $header["name"] : null;
					$header_name_type = isset($header["name_type"]) ? $header["name_type"] : null;
					$header_must_understand = isset($header["must_understand"]) ? $header["must_understand"] : null;
					$header_must_understand_type = isset($header["must_understand_type"]) ? $header["must_understand_type"] : null;
					$header_actor = isset($header["actor"]) ? $header["actor"] : null;
					$header_actor_type = isset($header["actor_type"]) ? $header["actor_type"] : null;
					
					$headers[] = array(
						"namespace" => self::getVariableValueCode($header_namespace, $header_namespace_type),
						"name" => self::getVariableValueCode($header_name, $header_name_type),
						"must_understand" => self::getVariableValueCode($header_must_understand, $header_must_understand_type),
						"actor" => self::getVariableValueCode($header_actor, $header_actor_type),
						"parameters" => $parameters,
					);
				}
			}
			else
				$headers = self::getVariableValueCode($dt_headers, $dt_headers_type);
			
			$dt_remote_function_args_type = isset($dt["remote_function_args_type"]) ? $dt["remote_function_args_type"] : null;
			$dt_remote_function_args = isset($dt["remote_function_args"]) ? $dt["remote_function_args"] : null;
			
			if ($dt_remote_function_args_type == "array") {
				$is_assoc = $dt_remote_function_args && array_keys($dt_remote_function_args) !== range(0, count($dt_remote_function_args) - 1);
				if ($is_assoc)
					$dt_remote_function_args = array($dt_remote_function_args);
				
				$remote_function_args = trim(self::getArrayString($dt_remote_function_args, "$prefix_tab\t"));
			}
			else
				$remote_function_args = self::getVariableValueCode($dt_remote_function_args, $dt_remote_function_args_type);
			
			$dt_type = isset($dt["type"]) ? $dt["type"] : null;
			$dt_type_type = isset($dt["type_type"]) ? $dt["type_type"] : null;
			$dt_wsdl_url = isset($dt["wsdl_url"]) ? $dt["wsdl_url"] : null;
			$dt_wsdl_url_type = isset($dt["wsdl_url_type"]) ? $dt["wsdl_url_type"] : null;
			$dt_remote_function_name = isset($dt["remote_function_name"]) ? $dt["remote_function_name"] : null;
			$dt_remote_function_name_type = isset($dt["remote_function_name_type"]) ? $dt["remote_function_name_type"] : null;
			
			$new_dt = array(
				"type" => self::getVariableValueCode($dt_type, $dt_type_type),
				"wsdl_url" => self::getVariableValueCode($dt_wsdl_url, $dt_wsdl_url_type),
				"options" => $opts,
				"headers" => $headers,
				"remote_function_name" => self::getVariableValueCode($dt_remote_function_name, $dt_remote_function_name_type),
				"remote_function_args" => $remote_function_args,
			);
			
			$dt = self::convertArrayToString($new_dt, $prefix_tab);
		}
		else
			$dt = self::getVariableValueCode($dt, $dt_type);
		
		$result_type = isset($properties["result_type"]) ? $properties["result_type"] : null;
		$result_type_type = isset($properties["result_type_type"]) ? $properties["result_type_type"] : null;
		
		$code  = $prefix_tab . $var_name . "SoapConnector::connect(" . $dt . ($result_type ? ", " . self::getVariableValueCode($result_type, $result_type_type) : "" ) . ");\n";
		
		$exit_task_id = isset($data["exits"][self::DEFAULT_EXIT_ID]) ? $data["exits"][self::DEFAULT_EXIT_ID] : null;
		return $code . self::printTask($tasks, $exit_task_id, $stop_task_id, $prefix_tab, $options);
	}
	
	private static function convertArrayToString($arr, $prefx = "") {
		if (!is_array($arr))
			return "null";
		
		$code = $prefx . "array(\n";
		
		foreach ($arr as $k => $v)
			$code .= "$prefx\t" . (is_numeric($k) || substr($k, 0, 1) == '$' || substr($k, 0, 2) == '@$' ? $k : '"' . $k . '"') . " => " . (is_array($v) ? trim(self::convertArrayToString($v, $prefx . "\t")) : (strlen($v) ? $v : "''")) . ", \n";
		
		$code .= "$prefx)";
		
		return $code;
	}
}
?>
