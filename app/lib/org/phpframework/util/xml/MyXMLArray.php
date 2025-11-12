<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

include get_lib("org.phpframework.util.xml.MyXMLArrayItem");
include get_lib("org.phpframework.util.xml.exception.MyXMLArrayException");
include_once get_lib("org.phpframework.util.xml.UnicodeUTF8");

class MyXMLArray {
	private $data;
	
	public function __construct($data) {
		$this->data = is_array($data) ? $data : array();
	}
	
	public function toXML($options = false) {
		$prefix_tab = isset($options["prefix_tab"]) ? $options["prefix_tab"] : null;
		
		return self::toXMLAux($this->data, $options, $prefix_tab);
		//return self::toXMLAux2($this->data, $options, $prefix_tab);
	}
	
	//pretifies better the xml
	private static function toXMLAux($arr, $options = false, $prefix_tab = "") {
		$xml = "";
		
		$to_decimal = isset($options["to_decimal"]) ? $options["to_decimal"] : null;
		
		foreach ($arr as $key => $items) {
			$key = !empty($options["lower_case_keys"]) ? strtolower($key) : (!empty($options["upper_case_keys"]) ? strtoupper($key) : $key);
			
			$t = $items ? count($items) : 0;
			for ($i = 0; $i < $t; $i++) {
				$item = $items[$i];
				
				$item_attrs = "";
				if (!empty($item["@"])) {
					foreach($item["@"] as $attr_name => $attr_value) {
						$attr_name = !empty($options["lower_case_keys"]) ? strtolower($attr_name) : (!empty($options["upper_case_keys"]) ? strtoupper($attr_name) : $attr_name);
					
						$attr_value = htmlentities($attr_value);
						if($to_decimal) 
							$attr_value = UnicodeUTF8::to_decimal(htmlentities(html_entity_decode($attr_value)));
						else
							$attr_value = str_replace("&", "&amp;", str_replace("&amp;", "&", $attr_value));
						
						$item_attrs .= " {$attr_name}=\"{$attr_value}\"";
					}
				}
				
				$sub_xml = "";
				
				if (isset($item["value"]))
					$sub_xml .= is_numeric($item["value"]) || is_bool($item["value"]) ? $item["value"] : (!empty($item["value"]) ? "<![CDATA[" . $item["value"] . "]]>" : $item["value"]);
				
				if (!empty($item["childs"]))
					$sub_xml .= self::toXMLAux($item["childs"], $options, $prefix_tab . "\t");
				
				$xml .= "\n$prefix_tab<{$key}" . $item_attrs;
				$xml .= empty($sub_xml) && !is_numeric($sub_xml) ? " />" : ">$sub_xml" . (!empty($item["childs"]) ? "\n$prefix_tab" : "") . "</{$key}>";
			}
		}
		return $xml;
	}
	/* 
	 * This is other way that works too but the entities are converted into decimal. However the xml pretify doesn't work very well 
	 * TODO: This doesn't add the CDATA, so untill this gets implemented, please use the method: self::toXMLAux(...)
	 */
	private static function toXMLAux2($arr, $options = false, $prefix_tab = "") {
		$xml = "";
		$root_node = new SimpleXMLElement("<root/>");
		
		self::prepareXMLNode($root_node, $arr, $options);
		
		foreach ($root_node->children() as $child)
			$xml .= $child->asXML();
		
		return $xml;
	}
	private static function prepareXMLNode(&$node, $arr, $options = false) {
		$to_decimal = isset($options["to_decimal"]) ? $options["to_decimal"] : null;
		
		if ($arr) 
			foreach($arr as $key => $items) {
				$key = !empty($options["lower_case_keys"]) ? strtolower($key) : (!empty($options["upper_case_keys"]) ? strtoupper($key) : $key);
				
				$t = $items ? count($items) : 0;
				for ($i = 0; $i < $t; $i++) {
					$item = $items[$i];
					
					$child = $node->addChild($key, isset($item["value"]) ? $item["value"] : null);
				
					if (!empty($item["@"]))
						foreach($item["@"] as $attr_name => $attr_value) {
							$attr_name = !empty($options["lower_case_keys"]) ? strtolower($attr_name) : (!empty($options["upper_case_keys"]) ? strtoupper($attr_name) : $attr_name);
						
							if($to_decimal) 
								$attr_value = UnicodeUTF8::to_decimal(htmlentities($attr_value));
								//$attr_value = UnicodeUTF8::to_decimal(htmlentities(html_entity_decode(htmlentities($attr_value))));
							
							$child->addAttribute($attr_name, $attr_value);
						}
					
					self::prepareXMLNode($child, isset($item["childs"]) ? $item["childs"] : null, $options);
				}
			}
	}
	
	public function getNodeValue($nodes_path = "", $conditions = false, $index = 0) {
		$nodes = $this->getNodes($nodes_path, $conditions, true);
		$item = isset($nodes[$index]) ? $nodes[$index] : null;
		
		return $item ? $item->getValue() : "";
	}
	
	public function getNodes($nodes_path = "", $conditions = false, $item_obj = false) {
		$items = $this->getItemsPath($nodes_path, $this->data);
		
		$nodes = array();
		$t = count($items);
		for($i = 0; $i < $t; $i++) {
			$item = $this->getItem($i, $items);
			if($item && $item->checkAttributes($conditions)) {
				$nodes[] = $item_obj ? $item : $items[$i];
			}
		}
		return $nodes;
	}
	
	private function getItemsPath($nodes_path, $data, $path_index = -1) {
		$node_path = "";
		$node_index_num = false;
		$node_conditions = false;
		$sub_nodes_path_exists = false;
		
		$paths = explode("/", $nodes_path);//$nodes_path should be a path splited with '/' like in XSLT
		$t = count($paths);
		for($i = 0; $i < $t; $i++) {
			$key = $paths[$i];
			if($key && $i > $path_index) {
				$path_index = $i;
				
				$explode = explode("[", $key);
				$node_path = $explode[0];
				$node_condition = !empty($explode[1]) ? explode("]", $explode[1]) : false;
				
				$node_condition = $node_condition && trim($node_condition[0]) ? trim($node_condition[0]) : false;
				
				if(is_numeric($node_condition)) {// sample: /RESULT/VARS/XX[1]
					$node_index_num = $node_condition - 1;
				}
				elseif($node_condition) {// sample: /RESULT/VARS/XX[@guid = '123' or (@guid = 456 && @name='jo\'ao' and @type ="x\"xx") || ID=2]
					$node_conditions = $this->parseNodeCondition($node_condition);
				}
				
				for($j = $i + 1; $j < $t; $j++) {
					if($paths[$j]) {
						$sub_nodes_path_exists = true;
						break;
					}
				}
				break;
			}
		}
		
		if(!$node_path) {
			return $data;
		}
		
		$nodes = array();
		if(!empty($data[ $node_path ])) {
			$items = $data[ $node_path ];
			
			$t = count($items);
			for($i = 0; $i < $t; $i++) {
				$item = is_numeric($node_index_num) ? (isset($items[$node_index_num]) ? $items[$node_index_num] : null) : $items[$i];
				
				$continue = $node_conditions ? $this->checkNodeConditions($item, $node_conditions) : true;
				if($continue) {
					if($sub_nodes_path_exists) {
						$childs = isset($item["childs"]) ? $item["childs"] : null;
						if($childs) {
							$sub_nodes = $this->getItemsPath($nodes_path, $childs, $path_index);
					
							$t2 = count($sub_nodes);
							for($j = 0; $j < $t2; $j++) {
								$nodes[] = $sub_nodes[$j];
							}
						}
					}
					else {
						$nodes[] = $item;
					}
				}
				
				if(is_numeric($node_index_num)) {
					break;
				}
			}
		}
		return $nodes;
	}
	
	private function getItem($index = 0, $data = false) {
		$data = $this->getConfiguredData($data);
	
		$item_data = !empty($data[$index]) ? $data[$index] : false;
		if($item_data) {
			$item = new MyXMLArrayItem($item_data);
			return $item;
		}
		return false;
	}
	
	private function getConfiguredData($data) {
		$data = $data ? $data : $this->data;
	
		return is_array($data) ? $data : array();
	}
	
	private function checkNodeConditions($node, $node_conditions) {
		$php_conditions_code = "";
		$t = $node_conditions ? count($node_conditions) : 0;
		for ($i = 0; $i < $t; $i++) {
			$node_condition = $node_conditions[$i];
			$node_condition_type = isset($node_condition["type"]) ? $node_condition["type"] : null;
			$node_condition_value = isset($node_condition["value"]) ? $node_condition["value"] : null;
			
			if ($node_condition_type == "node_or_attr") {
				$node_condition_name = isset($node_condition["name"]) ? $node_condition["name"] : null;
				$node_condition_operator = isset($node_condition["operator"]) ? $node_condition["operator"] : null;
				
				$php_conditions_code .= "\$node[\"@\"][\"".$node_condition_name."\"] ".$node_condition_operator." ".$node_condition_value;
			}
			else {
				$php_conditions_code .= " ".$node_condition_value." ";
			}
		}
		
		if($php_conditions_code) {
			try {
				$php_conditions_code = "\$status = (".$php_conditions_code.") ? true : false;";
				eval($php_conditions_code);
				return $status;
			}
			catch(Exception $e) {
				return false;
			}
		}
		return true;
	}
	
	private function parseNodeCondition($node_condition) {
		$conditions = array();
		
		$regex_to_get_conditions = '/(((@?)([\w\-\+]+)(\s*)(=|==|\\!=|<>|<=|>=|<|>)(\s*)([0-9]+))|((@?)([\w\-\+]+)(\s*)(=|==|\\!=|<>|<=|>=|<|>)(\s*)((?<!\\\\)\')(([^\']*)(((?<=\\\\)\')*)([^\']*))((?<!\\\\)\'))|((@?)([\w\-\+]+)(\s*)(=|==|\\!=|<>|<=|>=|<|>)(\s*)((?<!\\\\)")(([^"]*)(((?<=\\\\)")*)([^"]*))((?<!\\\\)"))|((\s*)(or|and|&&|\|\||\(|\))*(\s*)))/iu'; //'\w' means all words with '_' and '/u' means with accents and ç too.
		
		$regex_to_parse_condition = '/(@?)([\w\-\+]+)(\s*)(=|==|\\!=|<>|<=|>=|<|>)(\s*)(.*)/u'; //'\w' means all words with '_' and '/u' means with accents and ç too.

		preg_match_all($regex_to_get_conditions, $node_condition, $matches);
		$matches = isset($matches[1]) ? $matches[1] : null;
		
		$t = is_array($matches) ? count($matches) : 0;
		for ($i = 0; $i < $t; $i++) {
			$match = $matches[$i];
			
			preg_match_all($regex_to_parse_condition, $match, $sub_matches);
			
			if (!empty($sub_matches[0])) {
				$is_attribute = $sub_matches[1][0] == "@";
				$condition_name = $sub_matches[2][0];
				$condition_operator = $sub_matches[4][0] == "=" ? "==" : $sub_matches[4][0];
				$condition_value = $sub_matches[6][0];
				
				if(!$is_attribute) {
					if(function_exists("launch_exception")) {
						launch_exception(new MyXMLArrayException(1, $condition_name));
					}
					else {
						throw new MyXMLArrayException(1, $condition_name);
					}
					return false;
				}
				
				$conditions[] = array("type" => "node_or_attr", "name" => $condition_name, "operator" => $condition_operator, "value" => $condition_value);
			}
			else {
				$match = strtolower(trim($match));
				
				if(count($conditions) && ($match == "and" || $match == "&&" || $match == "or" || $match == "||")) {
					$match = $match == "and" ? "&&" : ($match == "or" ? "||" : $match);
					$conditions[] = array("type" => "gate", "value" => $match);
				}
				else if($match == "(" || $match == ")") {
					$conditions[] = array("type" => "separator", "value" => $match);
				}
				else if($match) {
					if(function_exists("launch_exception")) {
						launch_exception(new MyXMLArrayException(2, $match));
					}
					else {
						throw new MyXMLArrayException(2, $match);
					}
					return false;
				}
			}
		}
		
		return $conditions;
	}
}
?>
