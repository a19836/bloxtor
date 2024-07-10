<?php
namespace WorkFlowTask\programming\soapconnector;

include_once get_lib("org.phpframework.workflow.WorkFlowTask");

class WorkFlowTaskImpl extends \WorkFlowTask {
	
	public function createTaskPropertiesFromCodeStmt($stmt, $WorkFlowTaskCodeParser, &$exits = null, &$inner_tasks = null) {
		$props = $WorkFlowTaskCodeParser->getObjectMethodProps($stmt);
		
		if ($props) {
			$method_name = $props["method_name"];
			
			if ($method_name == "connect" && $props["method_static"] && $props["method_obj"] == "SoapConnector") {
				$args = $props["method_args"];
				
				$data = $args[0]["value"];
				$data_type = $args[0]["type"];
				
				$result_type = $args[1]["value"];
				$result_type_type = $args[1]["type"];
				
				if ($data_type == "array") {
					$data_stmts = $WorkFlowTaskCodeParser->getPHPParserEmulative()->parse("<?php\n" . $data . "\n?>");
					$data_arr = $WorkFlowTaskCodeParser->getArrayItems($data_stmts[0]->items);
					$data = array();
					
					if (is_array($data_arr)) {
						$t = count($data_arr);
						for ($i = 0; $i < $t; $i++) {
							$item = $data_arr[$i];
							$key = strtolower($item["key"]);
							
							if (isset($item["items"])) {
								$value = $item["items"];
								$value_type = "array";
								
								if ($key == "options") {
									$new_value = array();
									
									foreach ($value as $k => $v)
										$new_value[] = array(
											"name" => $v["key"],
											"value" => $v["items"] ? $v["items"] : $v["value"],
											"var_type" => $v["items"] ? "array" : $v["value_type"],
										);
									
									$value = $new_value;
								}
								else if ($key == "headers") {
									$new_value = array();
									
									foreach ($value as $k => $v) 
										if ($v["items"]) {
											$items = $v["items"];
											$item_value = array();
											
											foreach ($items as $ik => $iv) {
												$item_key = $iv["key"];
												$item_value[ $item_key ] = $iv["items"] ? $iv["items"] : $iv["value"];
												$item_value[ $item_key . "_type" ] = $iv["items"] ? "array" : $iv["value_type"];
											}
											
											$new_value[] = $item_value;
										}
									
									$value = $new_value;
								}
							}
							else {
								$value = $item["value"];
								$value_type = $item["value_type"];
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
		$raw_data = $task["raw_data"];
		
		$data_type = $raw_data["childs"]["properties"][0]["childs"]["data_type"][0]["value"];
		if ($data_type == "options" || $data_type == "array") {
			$aux = \MyXML::complexArrayToBasicArray($raw_data["childs"]["properties"][0]["childs"]);
			$data = $aux["data"];
			$data_type = "options";
		}
		else
			$data = $raw_data["childs"]["properties"][0]["childs"]["data"][0]["value"];
		
		$properties = array(
			"data" => $data,
			"data_type" => $data_type,
			"result_type" => $raw_data["childs"]["properties"][0]["childs"]["result_type"][0]["value"],
			"result_type_type" => $raw_data["childs"]["properties"][0]["childs"]["result_type_type"][0]["value"],
		);
		
		$properties = self::parseResultVariableProperties($raw_data, $properties);
		
		return $properties;
	}
	
	public function printCode($tasks, $stop_task_id, $prefix_tab = "", $options = null) {
		$data = $this->data;
		
		$properties = $data["properties"];
		
		$var_name = self::getPropertiesResultVariableCode($properties);
		
		$dt_type = $properties["data_type"];
		
		if ($dt_type == "options") {
			$dt = $properties["data"];
			$opts = array();
			$headers = array();
			$remote_function_args = null;
			
			if (is_array($dt["options"])) {
				$is_assoc = $dt["options"] && array_keys($dt["options"]) !== range(0, count($dt["options"]) - 1);
				if ($is_assoc)
					$dt["options"] = array($dt["options"]);
				
				foreach ($dt["options"] as $option)
					$opts[ $option["name"] ] = self::getVariableValueCode($option["value"], $option["var_type"]);
			}
			else
				$opts = self::getVariableValueCode($dt["options"], $dt["options_type"]);
			
			if (is_array($dt["headers"])) {
				$is_assoc = $dt["headers"] && array_keys($dt["headers"]) !== range(0, count($dt["headers"]) - 1);
				if ($is_assoc)
					$dt["headers"] = array($dt["headers"]);
				
				foreach ($dt["headers"] as $header) {
					$parameters = null;
					$parameters_type = $header["parameters_type"];
					if ($parameters_type == "array") {
						$is_assoc = $header["parameters"] && array_keys($header["parameters"]) !== range(0, count($header["parameters"]) - 1);
						if ($is_assoc)
							$header["parameters"] = array($header["parameters"]);
						
						$parameters = trim(self::getArrayString($header["parameters"], "$prefix_tab\t\t\t"));
					}
					else
						$parameters = self::getVariableValueCode($header["parameters"], $parameters_type);
					
					$headers[] = array(
						"namespace" => self::getVariableValueCode($header["namespace"], $header["namespace_type"]),
						"name" => self::getVariableValueCode($header["name"], $header["name_type"]),
						"must_understand" => self::getVariableValueCode($header["must_understand"], $header["must_understand_type"]),
						"actor" => self::getVariableValueCode($header["actor"], $header["actor_type"]),
						"parameters" => $parameters,
					);
				}
			}
			else
				$headers = self::getVariableValueCode($dt["headers"], $dt["headers_type"]);
			
			$remote_function_args_type = $dt["remote_function_args_type"];
			if ($remote_function_args_type == "array") {
				$is_assoc = $dt["remote_function_args"] && array_keys($dt["remote_function_args"]) !== range(0, count($dt["remote_function_args"]) - 1);
				if ($is_assoc)
					$dt["remote_function_args"] = array($dt["remote_function_args"]);
				
				$remote_function_args = trim(self::getArrayString($dt["remote_function_args"], "$prefix_tab\t"));
			}
			else
				$remote_function_args = self::getVariableValueCode($dt["remote_function_args"], $remote_function_args_type);
			
			$new_dt = array(
				"type" => self::getVariableValueCode($dt["type"], $dt["type_type"]),
				"wsdl_url" => self::getVariableValueCode($dt["wsdl_url"], $dt["wsdl_url_type"]),
				"options" => $opts,
				"headers" => $headers,
				"remote_function_name" => self::getVariableValueCode($dt["remote_function_name"], $dt["remote_function_name_type"]),
				"remote_function_args" => $remote_function_args,
			);
			
			$dt = self::convertArrayToString($new_dt, $prefix_tab);
		}
		else
			$dt = self::getVariableValueCode($properties["data"], $dt_type);
		
		$code  = $prefix_tab . $var_name . "SoapConnector::connect(" . $dt . ($properties["result_type"] ? ", " . self::getVariableValueCode($properties["result_type"], $properties["result_type_type"]) : "" ) . ");\n";
		
		return $code . self::printTask($tasks, $data["exits"][self::DEFAULT_EXIT_ID], $stop_task_id, $prefix_tab, $options);
	}
	
	private static function convertArrayToString($arr, $prefx = "") {
		if (!is_array($arr))
			return "null";
		
		$code = $prefx . "array(\n";
		
		foreach ($arr as $k => $v)
			$code .= "$prefx\t" . (is_numeric($k) || substr($k, 0, 1) == '$' ? $k : '"' . $k . '"') . " => " . (is_array($v) ? trim(self::convertArrayToString($v, $prefx . "\t")) : (strlen($v) ? $v : "''")) . ", \n";
		
		$code .= "$prefx)";
		
		return $code;
	}
}
?>
