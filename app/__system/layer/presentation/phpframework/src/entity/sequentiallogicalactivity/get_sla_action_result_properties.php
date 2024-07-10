<?php
$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "access");

$action_type = $_POST["action_type"];
$action_value = $_POST["action_value"];

if (is_array($action_value)) {
	MyArray::arrKeysToLowerCase($action_value, true);
	//print_r($action_value);
	$props = array();
	
	if ($action_type == "restconnector") {
		$result_type = getValueBasedInValueType($action_value["result_type"], $action_value["result_type_type"]);
		$allowed_result_types = array("content", "content_json", "content_xml", "content_xml_simple", "content_serialized");
		
		if (is_array($action_value["data"]) && in_array($result_type, $allowed_result_types)) {
			//prepare data
			foreach ($action_value["data"] as $idx => $item) {
				if ($item["key_type"] == "options")
					$action_value["data"][$idx]["key_type"] = "string";
				
				if ($item["key"] == "settings" && is_array($item["items"]))
					foreach ($item["items"] as $idj => $sub_item) {
						if ($sub_item["key_type"] == "options")
							$action_value["data"][$idx]["items"][$idj]["key_type"] = "string";
					}
			}
			
			$data = convertComplexArrayToSimpleArray($action_value["data"]);
			
			//call rest request
			include_once get_lib("org.phpframework.connector.RestConnector");
			$res = RestConnector::connect($data, $result_type);
			
			//echo "$result_type:";print_r($data);
			//print_r($res);
			
			$props = parseRequestResult($res, $result_type);
		}
	}
	else if ($action_type == "soapconnector") {
		$type = getValueBasedInValueType($action_value["data"]["type"], $action_value["data"]["type_type"]);
		$allowed_types = array("callSoapClient", "callSoapFunction");
		
		if (is_array($action_value["data"]) && in_array($type, $allowed_types)) {
			$result_type = getValueBasedInValueType($action_value["result_type"], $action_value["result_type_type"]);
			$allowed_result_types = array("content", "content_json", "content_xml", "content_xml_simple", "content_serialized");
			
			if ($type != "callSoapFunction" || in_array($result_type, $allowed_result_types)) {
				//prepare options
				if ($action_value["data"]["options_type"] == "options") {
					$options = array();
					
					if (is_array($action_value["data"]["options"])) 
						foreach ($action_value["data"]["options"] as $opt)
							if ($opt["name"])
								$options[ $opt["name"] ] = getValueBasedInValueType($opt["value"], $opt["var_type"]);
				}
				else
					$options = getValueBasedInValueType($action_value["data"]["options"], $action_value["data"]["options_type"]);
				
				//prepare headers
				if ($action_value["data"]["headers_type"] == "options") {
					$headers = array();
					
					if (is_array($action_value["data"]["headers"])) 
						foreach ($action_value["data"]["headers"] as $header)
							if (is_array($header)) {
								$h = array();
								
								foreach ($header as $k => $v)
									if (array_key_exists($k . "_type", $header)) {
										$v_type = $header[$k . "_type"];
										
										if ($v_type == "array" && is_array($v))
											$h[$k] = convertComplexArrayToSimpleArray($v);
										else
											$h[$k] = getValueBasedInValueType($v, $v_type);
									}
									
								$headers[] = $h;
							}
				}
				else
					$headers = getValueBasedInValueType($action_value["data"]["headers"], $action_value["data"]["headers_type"]);
				
				//prepare function agrs
				if ($action_value["data"]["remote_function_args_type"] == "array")
					$remote_function_args = convertComplexArrayToSimpleArray($action_value["data"]["remote_function_args"]);
				else
					$remote_function_args = getValueBasedInValueType($action_value["data"]["remote_function_args"], $action_value["data"]["remote_function_args_type"]);
				
				//prepare main data
				$data = array(
					"type" => $type,
					"wsdl_url" => getValueBasedInValueType($action_value["data"]["wsdl_url"], $action_value["data"]["wsdl_url_type"]),
					"options" => $options,
					"headers" => $headers,
				);
				
				if ($type == "callSoapFunction") {
					$data["remote_function_name"] = getValueBasedInValueType($action_value["data"]["remote_function_name"], $action_value["data"]["remote_function_name_type"]);
					$data["remote_function_args"] = $remote_function_args;
				}
				
				//echo "$type|$result_type:";print_r($data);
				
				//call soap request
				include_once get_lib("org.phpframework.connector.SoapConnector");
				
				if ($type == "callSoapClient") {
					$SoapClient = SoapConnector::connect($data);
					
					if ($SoapClient) {
						$props = array(
							"functions" => array()
						);
						$res = $SoapClient->__getFunctions();
						
						if ($res) 
							foreach ($res as $func_str) {
								preg_match("/ (\w+)\(/", $func_str, $match);
								$func_name = $match[1];
								
								$props["functions"][] = array(
									"name" => $func_name,
									"func" => $func_str
								);
							}
					}
				}
				else {
					$res = SoapConnector::connect($data, $result_type);
					$props = parseRequestResult($res, $result_type);
				}
				
				//print_r($res);
			}
		}
	}
}

function convertComplexArrayToSimpleArray($arr) {
	$new = array();
	
	if (is_array($arr))
		foreach ($arr as $k => $v) {
			if ($v["items"])
				$value = convertComplexArrayToSimpleArray($v["items"]);
			else
				$value = getValueBasedInValueType($v["value"], $v["value_type"]);
			
			if ($v["key_type"] == "null")
				$new[] = $value;
			else {
				$key = getValueBasedInValueType($v["key"], $v["key_type"]);
				$new[$key] = $value;
			}
		}
	
	return $new;
}

function getValueBasedInValueType($value, $value_type) {
	if ($value_type == "string" || $value_type == "options")
		return $value;
	
	if (!$value_type && strlen($value)) {
		eval('$aux = ' . $value . ';');
		return $aux;
	}
	
	return ""; //if variable or somethifn else return empty string bc there are no variables in this file.
}

function parseRequestResult($res, $result_type) {
	$props = array();
	
	if (($result_type == "content_json" || $result_type == "content_serialized") && is_array($res)) {
		$res_keys = array_keys($res);
		$is_multiple = $res_keys === range(0, count($res) - 1);
		
		//checks if the json is a list and if so, get the attributes from the first list item.
		if ($is_multiple)
			$res = $res[ $res_keys[0] ];
		
		if (is_array($res)) {
			$props = array(
				"attributes" => array(),
				"is_multiple" => $is_multiple,
			);
			
			foreach ($res as $k => $v)
				$props["attributes"][] = array(
					"column" => $k,
				);
		}
	}
	else if (($result_type == "content_xml" || $result_type == "content_xml_simple") && is_array($res)) {
		$first_key = key($res);
		$childs = $res[$first_key][0]["childs"];
		
		if (is_array($childs)) {
			$childs_keys = array_keys($childs);
			$sub_childs_key = $childs_keys[0];
			//echo "sub_childs_key:$sub_childs_key\n";
			
			//checks if the xml is a list and if so, get the attributes from the first list item.
			if (count($childs_keys) == 1 && !is_numeric($sub_childs_key)) {
				$sub_childs = $childs[$sub_childs_key];
				$sub_childs_keys = array_keys($sub_childs);
				$is_multiple = is_array($sub_childs) && $sub_childs_keys === range(0, count($sub_childs) - 1);
				//echo "is_multiple:$is_multiple\n";
				
				if ($is_multiple)
					$childs = $sub_childs[ $sub_childs_keys[0] ]["childs"];
			}
			//else it means the xml is a single item where the attributes are already in the first level $childs
			//print_r($childs);
			
			if (is_array($childs)) {
				$props = array(
					"attributes" => array(),
					"is_multiple" => $is_multiple,
				);
				
				foreach ($childs as $k => $v)
					$props["attributes"][] = array(
						"column" => $k,
					);
			}
		}
	}
	
	return $props;
}
?>
