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

include_once get_lib("org.phpframework.xmlfile.XMLFileParser");

class BeanXMLParser {
	const APP_KEY = "T/9BrU8a/67O79LODQW6BwCRgp9U0u8RTxNMJa61HbD4NM23tJoRMrh1rLArgCRh"; //DO NOT CHANGE THIS. THIS IS THEPHPMYFRAMEWORK PUBLIC KEY TO DECODE THE LICENCE
	
	public static function parseXML($content, $external_vars = array(), $xml_file_path = false) {
		$xml_schema_file_path = get_lib("org.phpframework.xmlfile.schema.beans", "xsd");
		$nodes = XMLFileParser::parseXMLContentToArray($content, $external_vars, $xml_file_path, $xml_schema_file_path);
		
		$first_node_name = is_array($nodes) ? array_keys($nodes) : array();
		$first_node_name = isset($first_node_name[0]) ? $first_node_name[0] : null;
		
		$node_types = $first_node_name && isset($nodes[$first_node_name][0]["childs"]) && is_array($nodes[$first_node_name][0]["childs"]) ? $nodes[$first_node_name][0]["childs"] : array();
		
		$beans = array();
		if(is_array($node_types)) {
			foreach($node_types as $node_type_key => $node_type_value) {
				if(is_array($node_type_value)) {
					foreach($node_type_value as $node_type_value_i) {
						switch($node_type_key) {
							case "var": 
								$bean = array("name" => self::getName($node_type_value_i));
								
								$reference = self::getReference($node_type_value_i);
								if($reference)
									$bean["reference"] = $reference;
								else 
									$bean["value"] = self::getValue($node_type_value_i);
								
								$beans[]["var"] = $bean;
								break;
					
							case "bean":
								$bean = array();
								
								if (!empty($node_type_value_i["@"]))
									foreach($node_type_value_i["@"] as $att_key => $att_value)
										$bean[$att_key] = $att_value;
								
								$attrs = array("name", "path", "path_prefix", "extend");
								foreach ($attrs as $attr) 
									if(!isset($bean[$attr])) {
										$attr_value = XMLFileParser::getAttribute($node_type_value_i, $attr);
										
										if (isset($attr_value)) 
											$bean[$attr] = $attr_value;
									}
								
								if (!empty($bean["path"]) && empty($bean["path_prefix"])) {
									$lib_settings = get_lib_settings($bean["path"]);
									$bean["path_prefix"] = isset($lib_settings[0]) ? $lib_settings[0] : null;
									$bean["path"] = isset($lib_settings[1]) ? $lib_settings[1] : null;
								}
						
								if(isset($bean["extend"]))
									$bean["extend"] = self::prepareExtend($bean["extend"]);
								
								if(isset($node_type_value_i["childs"]["constructor_arg"]))
									$bean["constructor_args"] = self::getConstructorArgs($node_type_value_i);
								
								if(isset($node_type_value_i["childs"]["property"]))
									$bean["properties"] = self::getProperties($node_type_value_i);
								
								if(isset($node_type_value_i["childs"]["function"]))
									$bean["functions"] = self::getFunctions($node_type_value_i);
								
								$beans[]["bean"] = $bean;
								break;
							
							case "function":
								$bean = self::getFunction($node_type_value_i);
								
								$beans[]["function"] = $bean;
								break;
						}
					}
				}
			}
		}
		return $beans;
	}
	
	private static function getName($node) {
		return XMLFileParser::getAttribute($node, "name");
	}
	
	private static function getIndex($node) {
		return XMLFileParser::getAttribute($node, "index");
	}
	
	private static function getReference($node) {
		return XMLFileParser::getAttribute($node, "reference");
	}
	
	private static function getValue($node) {
		$list = self::getList($node);
		if(is_array($list)) {
			return $list;
		}
		
		//TODO: change the correspondent xsd to have this case.
		if (!empty($node["childs"]["value"])) {
			$value = "";
			
			$sub_nodes = $node["childs"]["value"];
			$total = $sub_nodes ? count($sub_nodes) : 0;
			for ($i = 0; $i < $total; $i++) {
				$value .= self::getValue($sub_nodes[$i]);
			}
			
			return $value;
		}
		
		return XMLFileParser::getValue($node);
	}
	
	private static function getList($node) {
		if(!empty($node["childs"]["list"][0]["childs"])) {
			$items = isset($node["childs"]["list"][0]["childs"]["item"]) ? $node["childs"]["list"][0]["childs"]["item"] : null;
			
			$var_value = array();
			
			if ($items)
				foreach($items as $item_value) {
					$item_key = self::getName($item_value);
					
					if (isset($item_key))
						$var_value[$item_key] = self::getValue($item_value);
					else
						$var_value[] = self::getValue($item_value);
				}
			return $var_value;
		}
		return false;		
	}
	
	private static function prepareExtend($extend) {
		$extend = preg_replace('/[\s;]+/', "", $extend);
		$extend = preg_replace('/[,]+/', ",", $extend);
		
		return explode(",", $extend);
	}
	
	private static function getConstructorArgs($node) {
		$construct_args = array();
		$t = !empty($node["childs"]["constructor_arg"]) ? count($node["childs"]["constructor_arg"]) : 0;
		for($i = 0; $i < $t; $i++) {
			$arg_node = $node["childs"]["constructor_arg"][$i];
			
			$construct_arg = array();
			
			$index = self::getIndex($arg_node);
			if(strlen($index))
				$construct_arg["index"] = $index;
			
			$reference = self::getReference($arg_node);
			if($reference)
				$construct_arg["reference"] = $reference;
			else
				$construct_arg["value"] = self::getValue($arg_node);
			
			$construct_args[] = $construct_arg;
		}
		return $construct_args;
	}
	
	private static function getProperties($node) {
		$properties = array();
		$t = !empty($node["childs"]["property"]) ? count($node["childs"]["property"]) : 0;
		for($i = 0; $i < $t; $i++) {
			$property_node = $node["childs"]["property"][$i];
			
			$property = array("name" => self::getName($property_node));
			
			$reference = self::getReference($property_node);
			if($reference) {
				$property["reference"] = $reference;
			}
			else {
				$property["value"] = self::getValue($property_node);
			}
			$properties[] = $property;
		}
		return $properties;
	}
	
	private static function getFunctions($node) {
		$functions = array();
		$t = !empty($node["childs"]["function"]) ? count($node["childs"]["function"]) : 0;
		for($i = 0; $i < $t; $i++) {
			$function_node = $node["childs"]["function"][$i];
			
			$function = self::getFunction($function_node);
			
			$functions[] = $function;
		}
		return $functions;
	}
	
	private static function getFunction($function_node) {
		$parameter_nodes = isset($function_node["childs"]["parameter"]) ? $function_node["childs"]["parameter"] : null;
		
		$parameters = array();
		$t = $parameter_nodes ? count($parameter_nodes) : 0;
		for($j = 0; $j < $t; $j++) {
			$parameter_node = $parameter_nodes[$j];
			
			$parameter = array();
			
			$index = self::getIndex($parameter_node);
			if(strlen($index)) {
				$parameter["index"] = $index;
			}
		
			$reference = self::getReference($parameter_node);
			if($reference) {
				$parameter["reference"] = $reference;
			}
			else {
				$parameter["value"] = self::getValue($parameter_node);
			}
			$parameters[] = $parameter;
		}
		$function = array("name" => self::getName($function_node), "parameters" => $parameters);
		
		$reference = self::getReference($function_node);
		if (isset($reference) && $reference) {
			$function["reference"] = $reference;
		}
		
		return $function;
	}
}
?>
