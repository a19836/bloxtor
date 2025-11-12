<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

include_once get_lib("org.phpframework.phpscript.PHPScriptHandler");
include_once get_lib("org.phpframework.util.MyArray");
include_once get_lib("org.phpframework.util.xml.MyXML");
include_once get_lib("org.phpframework.util.xml.MyXMLArray");
include_once get_lib("org.phpframework.util.xml.MyXSD");
include_once get_lib("org.phpframework.xmlfile.exception.XMLFileParserException");

class XMLFileParser {
	
	public static function parseXMLFileToArray($file_path, $external_vars = false, $xml_schema_file_path = false, $parse_php = true) {
		$content = file_get_contents($file_path);
		
		return self::parseXMLContentToArray($content, $external_vars, $file_path, $xml_schema_file_path, $parse_php);
	}
	
	public static function parseXMLContentToArray($content, $external_vars = false, $file_path = false, $xml_schema_file_path = false, $parse_php = true) {
		if ($content) {
			$arr = self::getXMLContentNodes($content, $external_vars, $file_path, $parse_php);
			
			if($xml_schema_file_path) {
				$MyXMLArray = new MyXMLArray($arr);
				$content = $MyXMLArray->toXML();
				
				$status = MyXSD::validate($content, $xml_schema_file_path);
				if($status !== true) 
					launch_exception(new MyXSDException(1, array($status, $xml_schema_file_path, $file_path)));
			}
			
			return $arr;
		}
	}
	
	private static function getXMLContentNodes($content, $external_vars = false, $file_path = false, $parse_php = true, $xml_order_id_prefix = false) {
		$content = $parse_php ? PHPScriptHandler::parseContent($content, $external_vars) : $content;
		$MyXML = new MyXML($content);
		$arr = $MyXML->toArray(array("simple" => false, "lower_case_keys" => true, "xml_order_id_prefix" => $xml_order_id_prefix));
		
		$first_node_name = is_array($arr) ? array_keys($arr) : array();
		$first_node_name = isset($first_node_name[0]) ? $first_node_name[0] : null;
		
		$nodes_data = $first_node_name && isset($arr[$first_node_name][0]["childs"]) && is_array($arr[$first_node_name][0]["childs"]) ? $arr[$first_node_name][0]["childs"] : array();
		$nodes_data = self::addImportsToNodes($nodes_data, $external_vars, $file_path, $parse_php);
		
		$arr[$first_node_name][0]["childs"] = $nodes_data;
		
		return $arr;
	}
	
	private static function addImportsToNodes($nodes, $external_vars = false, $file_path = false, $parse_php = true) {
		$imports = false;
		
		$keys = array_keys($nodes);
		
		$total = count($keys);
		for($i = 0; $i < $total; $i++) {
			$key = $keys[$i];
			$node = $nodes[$key];
			
			if(strtolower($key) == "import") {
				$imports = $node;
				unset($nodes[$key]);
			}
			else {
				$sub_total = $node ? count($node) : 0;
				for($j = 0; $j < $sub_total; $j++) {
					if(isset($node[$j]["childs"])) {
						$nodes[$key][$j]["childs"] = self::addImportsToNodes($node[$j]["childs"], $external_vars, $file_path, $parse_php);
					}
				}
			}
		}
	
		//START IMPORT
		if($imports) {
			$dir_path = dirname($file_path). "/";

			$t = count($imports);
			for($i = 0; $i < $t; $i++) {
				$import = $imports[$i];

				$import_path = trim(self::getValue($import));
				
				//MyArray::arrKeysToLowerCase($import["@"]);
				$relative = strtolower(self::getAttribute($import, "relative"));
				if($relative && $relative != "false" && $relative != "0" && $relative != "null") {
					$import_path = $dir_path . $import_path;
				}

				if($import_path && file_exists($import_path)) {
					$arr = self::getImportedXMLFileNodes($import_path, $external_vars, $parse_php, isset($import["xml_order_id"]) ? $import["xml_order_id"] : null);
	
					$first_node_name = is_array($arr) ? array_keys($arr) : array();
					$first_node_name = isset($first_node_name[0]) ? $first_node_name[0] : null;
					
					if($first_node_name) {
						$imports_data = isset($arr[$first_node_name][0]["childs"]) ? $arr[$first_node_name][0]["childs"] : null;
						
						if(is_array($imports_data)) {
							foreach($imports_data as $key => $value) {
								if(isset($nodes[$key])) {
									$nodes[$key] = array_merge($nodes[$key], $value);
								}
								else {
									$nodes[$key] = $value;
								}
							
								//START: Sort xml nodes by order
								$sort = array();
								$sub_total = !empty($nodes[$key]) ? count($nodes[$key]) : 0;
								for($j = 0; $j < $sub_total; $j++) {
									$sort[$j] = isset($nodes[$key][$j]["xml_order_id"]) ? $nodes[$key][$j]["xml_order_id"] : null;
								}
							
								asort($sort);
							
								$new_node_key = array();
								foreach($sort as $j => $xml_order_id) {
									$new_node_key[] = $nodes[$key][$j];
								}
								$nodes[$key] = $new_node_key;
								//END: Sort xml nodes by order
							}
						}
					}
				}
				else {
					launch_exception(new XMLFileParserException(1, $import_path));
				}
			}

			foreach($nodes as $key => $value) {
				if(strtolower($key) == "import") {
					$nodes = self::addImportsToNodes($nodes, $external_vars, $file_path, $parse_php);
				}
			}
		}
		//END IMPORT
		
		return $nodes;
	}
	
	private static function getImportedXMLFileNodes($file_path, $external_vars = false, $parse_php = true, $xml_order_id_prefix = false) {
		$content = file_get_contents($file_path);
		
		return self::getXMLContentNodes($content, $external_vars, $file_path, $parse_php, $xml_order_id_prefix);
	}
	
	public static function getAttributes($node, $var_names) {
		$attrs = array();
		$t = $var_names ? count($var_names) : 0;
		for($i = 0; $i < $t; $i++) {
			$var_name = $var_names[$i];
			$var_value = self::getAttribute($node, $var_name);
			
			if(strlen($var_value)) {
				$attrs[$var_name] = $var_value;
			}
		}
		return $attrs;
	}
	
	//this covers the case where value is equal to 0.
	public static function getAttribute($node, $var_name) {
		if(isset($node["@"][$var_name]) && strlen($node["@"][$var_name])) {
			return $node["@"][$var_name];
		}
		elseif(isset($node["childs"][$var_name])) {
			if(isset($node["childs"][$var_name][0]["@"]["value"]) && strlen($node["childs"][$var_name][0]["@"]["value"])) {
				return $node["childs"][$var_name][0]["@"]["value"];
			}
			return isset($node["childs"][$var_name][0]["value"]) ? $node["childs"][$var_name][0]["value"] : null;
		}
		return null;
	}
	public static function getValue($node) {
		$value = self::getAttribute($node, "value");
		return trim( strlen($value) ? $value : (isset($node["value"]) ? $node["value"] : null) );
	}
	
	public static function combineMultipleNodesInASingleNode($nodes) {
		$new_node = array();
		
		$t = $nodes ? count($nodes) : 0;
		for ($i = 0; $i < $t; $i++) {
			$node = $nodes[$i];
			
			if ($i == 0)
				$new_node = $node;
			else {
				if (!empty($new_node["@"]) && !empty($node["@"]))
					$new_node["@"] = array_merge($new_node["@"], $node["@"]);
				else if (!empty($node["@"]))
					$new_node["@"] = $node["@"];
				
				if (isset($node["childs"]) && is_array($node["childs"])) {
					foreach ($node["childs"] as $child_key => $child) {
						if (!empty($new_node["childs"][$child_key]))
							$new_node["childs"][$child_key] = array_merge($new_node["childs"][$child_key], $node["childs"][$child_key]);
						else
							$new_node["childs"][$child_key] = $child;
					}
				}
			}
		}
		
		return array($new_node);
	}
}
?>
