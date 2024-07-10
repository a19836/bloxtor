<?php
include_once get_lib("org.phpframework.util.xml.UnicodeUTF8");
include_once get_lib("org.phpframework.util.MyArray");

class MyXML extends SimpleXMLElement {
	
	/**
      * @param string $xmlContent A well-formed XML string
      * @param string $version 1.0
      * @param string $encoding utf-8
      * @return bool
      */
    	public static function isXMLContentValid($xmlContent, $version = '1.0', $encoding = 'utf-8') {
		if (trim($xmlContent) == '')
			return false;

		libxml_use_internal_errors(true);

		$doc = new DOMDocument($version, $encoding);
		$doc->loadXML($xmlContent);

		$errors = libxml_get_errors();
		libxml_clear_errors();

		return empty($errors);
	}
	
	public function getNodes($node_path, $conditions = false) {
		$nodes = $this->xpath($node_path);
		
		if(!$nodes)
			$nodes = array();
		else if(count($nodes)) {
			$condition_keys = is_array($conditions) && count($conditions) ? array_keys($conditions) : false;
			
			if($condition_keys) {
				$found_nodes = array();
				
				$t = count($nodes);
				$t2 = count($condition_keys);
				
				for($i = 0; $i < $t; $i++) {
					$mxn_node = $nodes[$i];
					
					$exists = true;
					for($t = 0; $t < $t2; $t++) {
						if($mxn_node->getAttribute($condition_keys[$t]) != $conditions[ $condition_keys[$t] ]) {
							$exists = false;
							break;
						}
					}
										
					if($exists)
						$found_nodes[] = $mxn_node;
				}
				$nodes = $found_nodes;
			}
		}
		return $nodes;
	}

	public function getChildrenCount($namespace = false) {
		$cnt = 0;
		$children = $this->children($namespace);
		
		foreach($children as $node)
			$cnt++;
		
		return (int)$cnt;
	}
	
	public function getAttribute($name, $namespace = false) {
		$attrs = $namespace ? $this->attributes($namespace, true) : $this->attributes();
		
		foreach($attrs as $key => $val) {
			if($key == $name)
				return (string)$val;
		}
		return false;
	}
    
	public function getAttributesName($namespace = false) {
		$arrTemp = array();
		$attrs = $namespace ? $this->attributes($namespace, true) : $this->attributes();
		
		foreach($attrs as $key => $val) 
			$arrTemp[] = (string)$key;
		
		return (array)$arrTemp;
	}

	public function getAttributesArray($namespace = false) {
		$arrTemp = array();
		
		$attrs = $namespace ? $this->attributes($namespace, true) : $this->attributes();
		
		foreach($attrs as $key => $val) {
			$key = $key;
			$arrTemp[$key] = (string)$val;
		}
		
		return (array)$arrTemp;
	}
	
	public function getAttributesCount($namespace = false) {
		$names = $this->getAttributesName($namespace);
		
		return count($names);
	}
	
	public function getDocDefinedNamespaces() {
		$defined_namespaces = array();
		$node_namespaces = $this->getDocNamespaces();
		
		//echo $data["name"]."<pre>";print_r($this);die();
		foreach($node_namespaces as $prefix => $ns) {
			$prefix = $prefix ? "xmlns:" . $prefix : "xmlns";
			$prefix = !empty($options["upper_case_keys"]) ? strtoupper($prefix) : (!empty($options["lower_case_keys"]) ? strtolower($prefix) : $prefix);
			$defined_namespaces[$prefix] = $ns;
		}
		
		return $defined_namespaces;
	}
	
	public function toArray($options = false, $main_prefix = false) {
		$data = array();
		
		$simple = isset($options["simple"]) ? $options["simple"] : null;
		$from_decimal = isset($options["from_decimal"]) ? $options["from_decimal"] : null;
		
		//prepare name
		$data["name"] = ($main_prefix ? $main_prefix . ":" : "") . $this->getName();
		
		//prepare value
		$value = (string)$this;
		if(strlen(trim($value)))
			$data["value"] = $value;
		
		//prepare children
		$childs = $this->children();
		$childs_data = $this->childsToArray($childs, $options, false);
		
		$all_namespaces = $this->getNamespaces(true);
		foreach($all_namespaces as $prefix => $ns) {
			$childs = $this->children($prefix, true);
			
			if (!$childs)
				$childs = $this->children($ns);
			//echo "<pre>$prefix:".print_r($childs, 1)."<pre><br/>";
			
			$ns_childs_data = $this->childsToArray($childs, $options, $prefix);
			//echo "<pre>$prefix:".print_r($ns_childs_data, 1)."<pre><br/>";
			
			$childs_data = array_merge($childs_data, $ns_childs_data);
		}
		
		//prepare attributes
		if(!$simple) {
			$node_attrs = array();
			$node_namespaces = $this->getDocNamespaces(true, false);
			//echo "$main_prefix:".$data["name"].print_r($node_namespaces, 1)."<br>";
			
			//echo $data["name"]."<pre>";print_r($this);die();
			foreach($node_namespaces as $prefix => $ns) {
				$prefix = $prefix ? "xmlns:" . $prefix : "xmlns";
				$prefix = !empty($options["upper_case_keys"]) ? strtoupper($prefix) : (!empty($options["lower_case_keys"]) ? strtolower($prefix) : $prefix);
				$node_attrs[$prefix] = $ns;
			}
			
			$all_namespaces = $this->getNamespaces(true);
			foreach($all_namespaces as $prefix => $ns) {
				$attrs = $this->getAttributesArray($prefix);
				//echo $data["name"]."-$prefix:".print_r($attrs, 1)."<br>";
				
				if(!empty($attrs)) {
					!empty($options["upper_case_keys"]) ? MyArray::arrKeysToUpperCase($attrs, false) : (!empty($options["lower_case_keys"]) ? MyArray::arrKeysToLowerCase($attrs, false) : null);
					
					if ($attrs)
						foreach ($attrs as $k => $v) {
							if ($from_decimal)
								$v = html_entity_decode($v);
							
							$k = ($prefix ? $prefix . ":" : "") . $key;
							$node_attrs[$k] = $v;
						}
				}
			}
			
			$attrs = $this->getAttributesArray();
			if(!empty($attrs)) {
				!empty($options["upper_case_keys"]) ? MyArray::arrKeysToUpperCase($attrs, false) : (!empty($options["lower_case_keys"]) ? MyArray::arrKeysToLowerCase($attrs, false) : null);
				
				if ($attrs)
					foreach ($attrs as $k => $v) {
						if ($from_decimal)
							$v = html_entity_decode($v);
						
						$node_attrs[$k] = $v;
					}
			}
			
			if ($node_attrs)
				$data["@"] = $node_attrs;
			
			//echo "$main_prefix:".$data["name"].print_r($data["@"], 1)."<br>";
		}
		
		//add children to node data
		if (!empty($childs_data)) {
			$data["childs"] = $childs_data;
			//echo "<pre>childs_data:".print_r($childs_data, 1)."<pre><br/>";
		}
		
		//add node data with node key
		$arr = array();
		$key = !empty($options["upper_case_keys"]) ? strtoupper($data["name"]) : (!empty($options["lower_case_keys"]) ? strtolower($data["name"]) : $data["name"]);
		$arr[$key][] = $data;
		//echo "$key:<pre>".print_r($arr, 1)."</pre>";
		
		return $arr;
	}
	
	public function childsToArray($childs, $options = false, $prefix = false) {
		$childs_data = array();
		
		$xml_order_id_prefix = isset($options["xml_order_id_prefix"]) ? $options["xml_order_id_prefix"] : null;
		
		$xml_order_id = 0;
		
		foreach($childs as $child) {
			++$xml_order_id;
			
			$new_xml_order_id_prefix = ($xml_order_id_prefix ? $xml_order_id_prefix . "." : "") . $xml_order_id;
			
			$new_options = $options;
			$new_options["xml_order_id_prefix"] = $new_xml_order_id_prefix;
			
			$child_data = $child->toArray($new_options, $prefix);
			
			$child_name = ($prefix ? $prefix . ":" : "") . $child->getName();
			
			$child_key = !empty($options["upper_case_keys"]) ? strtoupper($child_name) : (!empty($options["lower_case_keys"]) ? strtolower($child_name) : $child_name);
			
			$child_data[$child_key][0]["xml_order_id"] = $new_xml_order_id_prefix;
			//echo "<pre>$child_name:";print_r($child_data);
			
			$childs_data[$child_key][] = $child_data[$child_key][0];
		}
		
		return $childs_data;
	}
	
	public static function complexArrayToBasicArray($arr, $options = false) {
		$new_arr = array();
		//echo "<pre>$arr:".print_r($arr, 1)."<pre><br/>";die();
		
		if (is_array($arr) && !empty($arr)) {
			$trim = !empty($options["trim"]);
			
			foreach ($arr as $nodes) {
				$total = $nodes ? count($nodes) : 0;
				for ($i = 0; $i < $total; $i++) {
					$node = $nodes[$i];
					$node_keys = array_keys($node);
					$node_lower_keys = array_flip(array_map("strtolower", $node_keys));
					
					MyArray::arrKeysToLowerCase($node, false);
					
					if (isset($node["value"])) {
						$new_node = $trim ? trim($node["value"]) : $node["value"];
						
						if (!empty($node["childs"])) {
							$value_key = $node_keys[ $node_lower_keys["value"] ];//value/VALUE/Value...
							 
							$aux = array(
								$value_key => $new_node,
							);
							
							$childs = self::complexArrayToBasicArray($node["childs"], $options);
							$new_node = array_merge($childs, $aux);
						}
					}
					else if (isset($node["childs"])) {
						$new_node = self::complexArrayToBasicArray($node["childs"], $options);
					}
					else {
						$new_node = null;
					}
					
					if (isset($node["@"]) && empty($options["convert_without_attributes"])) {
						!empty($options["upper_case_keys"]) ? MyArray::arrKeysToUpperCase($node["@"], false) : (!empty($options["upper_case_keys"]) ? MyArray::arrKeysToLowerCase($node["@"], false) : null);
						$attrs = $node["@"];
						
						if (is_array($new_node)) {
							$new_node = array_merge($attrs, $new_node);
						}
						else if (isset($new_node)) {
							$value_key = isset($node_lower_keys["value"]) ? $node_keys[ $node_lower_keys["value"] ] : "value";//value/VALUE/Value...
							$new_node = array(
								$value_key => $new_node,
								"@" => $attrs,
							);
						}
						else {
							$new_node = $attrs;
						}
					}
					
					!empty($options["upper_case_keys"]) ? MyArray::arrKeysToUpperCase($new_node, false) : (!empty($options["upper_case_keys"]) ? MyArray::arrKeysToLowerCase($new_node, false) : null);
			
					$key = !empty($options["upper_case_keys"]) ? strtoupper($node["name"]) : (!empty($options["lower_case_keys"]) ? strtolower($node["name"]) : $node["name"]);
					
					if ($total > 1) {
						$new_arr[ $key ][] = $new_node;
					}
					else {
						$new_arr[ $key ] = $new_node;
					}
				}
			}
		}
		
		if (is_array($options))
			foreach ($options as $option_key => $option_value) 
				if (!empty($option_value)) {
					/* No need this here bc we are calling this directly above
					if ($option_key == "convert_without_attributes")
						$new_arr = self::convertBasicArrayWithoutAttributes($new_arr, $options);
					else */if ($option_key == "convert_childs_to_attributes")
						$new_arr = self::convertChildsToAttributesInBasicArray($new_arr, $options);
					else if ($option_key == "convert_attributes_to_childs")
						$new_arr = self::convertAttributesToChildsInBasicArray($new_arr, $options);
					else if ($option_key == "discard_nodes")
						$new_arr = self::discardNodesInBasicArray($new_arr, $options);
				}
		
		return $new_arr;
	}
	
	public static function complexChildsArrayToBasicArray($arr, $options = false) {
		$new_arr = array();
		
		if (is_array($arr) && !empty($arr)) {
			$arr = array("aux" => $arr);
			$new_arr = self::complexArrayToBasicArray($arr, $options);
		}
		
		return $new_arr;
	}
	
	public static function basicArrayToComplexArray($arr, $options = false) {
		$new_arr = array();
		
		if (is_array($arr) && !empty($arr)) {
			$trim = !empty($options["trim"]);
			$xml_order_id_prefix = !empty($options["xml_order_id_prefix"]) ? $options["xml_order_id_prefix"] . "." : "";
		
			$xml_order_id = 1;
			
			$is_main_numeric_keys = true;
			foreach ($arr as $key => $aux)
				if (!is_numeric($key)) {
					$is_main_numeric_keys = false;
					break;
				}
			
			if ($is_main_numeric_keys)
				$arr = array("default" => $arr);
		
			foreach ($arr as $key => $nodes) {
				if ($key != "@") {
					$original_nodes = $nodes;
					MyArray::arrKeysToLowerCase($nodes, false);
					
					//We must remove the @ in the $original_nodes bc this var will be used has sub_nodes or node childs.
					if (is_array($original_nodes))
						unset($original_nodes["@"]);
					
					$k = !empty($options["upper_case_keys"]) ? strtoupper($key) : (!empty($options["lower_case_keys"]) ? strtolower($key) : $key);
					
					$is_primitive = !is_array($nodes) || count($nodes) == 0 || isset($nodes["value"]) || 
								(is_array($nodes) && array_key_exists("@", $nodes) && count($nodes) == 1); //nodes can have only attributes without any value or with a primitive value. So we must count the $nodes, which will not include the @.
					
					if ($is_primitive) {
						if (isset($nodes["value"])) {
							$value = $nodes["value"];
							unset($nodes["value"]);
						}
						else
							$value = $original_nodes;
					
						$n = array(
							"name" => $key,
							"xml_order_id" => $xml_order_id_prefix . $xml_order_id,
						);
						
						if (!is_array($value))
							$n["value"] = $trim && $value ? trim($value) : $value;
						else if (count($value) > 0)
							$n["value"] = $value;
						
						if (is_array($nodes) && array_key_exists("@", $nodes)) {
							if (is_array($nodes["@"]) && empty($options["convert_without_attributes"])) {
								!empty($options["upper_case_keys"]) ? MyArray::arrKeysToUpperCase($nodes["@"], false) : (!empty($options["upper_case_keys"]) ? MyArray::arrKeysToLowerCase($nodes["@"], false) : null);
								$n["@"] = $nodes["@"];
							}
							
							unset($nodes["@"]);
						}
				
						if (!empty($nodes)) {
							$sub_options = $options ? $options : array();
							$sub_options["xml_order_id_prefix"] = $xml_order_id_prefix . $xml_order_id;
							
							$sub_nodes = self::basicArrayToComplexArray($original_nodes, $sub_options);
							
							if ($sub_nodes)
								$n["childs"] = $sub_nodes;
						}
						
						!empty($options["upper_case_keys"]) ? MyArray::arrKeysToUpperCase($n, false) : (!empty($options["upper_case_keys"]) ? MyArray::arrKeysToLowerCase($n, false) : null);
						
						$new_arr[$k][] = $n;
					
						$xml_order_id++;
					}
					else {
						//Check if numeric keys;
						$is_numeric_keys = true;
						foreach ($nodes as $nk => $aux) {
							if (!is_numeric($nk)) {
								$is_numeric_keys = false;
								break;
							}
						}
					
						if ($is_numeric_keys) {
							$total = $nodes ? count($nodes) : 0;
							for ($i = 0; $i < $total; $i++) {
								$item = $nodes[$i];
								$original_item = $item;
								
								//We must remove the @ in the $original_nodes bc this var will be used has sub_nodes or node childs.
								if (is_array($original_item))
									unset($original_item["@"]);
								
								MyArray::arrKeysToLowerCase($item, false);
								
								$n = array(
									"name" => $key,
									"xml_order_id" => $xml_order_id_prefix . $xml_order_id,
								);
								
								if (is_array($item) && array_key_exists("@", $item)) {
									if (is_array($item["@"]) && empty($options["convert_without_attributes"])) {
										!empty($options["upper_case_keys"]) ? MyArray::arrKeysToUpperCase($item["@"], false) : (!empty($options["upper_case_keys"]) ? MyArray::arrKeysToLowerCase($item["@"], false) : null);
										$n["@"] = $item["@"];
									}
									
									unset($item["@"]); //unset @ so the $is_primitive var be correct.
								}
								
								$is_primitive = !is_array($item) || count($item) == 0 || isset($item["value"]);
								
								if ($is_primitive) {
									if (isset($item["value"])) {
										$value = $item["value"];
										unset($item["value"]);
									}
									else
										$value = $original_item;
									
									if (!is_array($value))
										$n["value"] = $trim && $value ? trim($value) : $value;
									else if (count($value) > 0)
										$n["value"] = $value;
									
									if (!empty($item)) {
										$sub_options = $options ? $options : array();
										$sub_options["xml_order_id_prefix"] = $xml_order_id_prefix . $xml_order_id;
										
										$sub_nodes = self::basicArrayToComplexArray($original_item, $sub_options);
										
										if ($sub_nodes)
											$n["childs"] = $sub_nodes;
									}
								}
								else {
									$sub_options = $options ? $options : array();
									$sub_options["xml_order_id_prefix"] = $xml_order_id_prefix . $xml_order_id;
							
									$sub_nodes = self::basicArrayToComplexArray($original_item, $sub_options);
									
									if ($sub_nodes)
										$n["childs"] = $sub_nodes;
								}
								
								!empty($options["upper_case_keys"]) ? MyArray::arrKeysToUpperCase($n, false) : (!empty($options["upper_case_keys"]) ? MyArray::arrKeysToLowerCase($n, false) : null);
								
								$new_arr[$k][] = $n;
								$xml_order_id++;
							}
						}
						else {
							$sub_options = $options ? $options : array();
							$sub_options["xml_order_id_prefix"] = $xml_order_id_prefix . $xml_order_id;
							
							$sub_nodes = self::basicArrayToComplexArray($original_nodes, $sub_options);
							
							$n = array(
								"name" => $key,
								"xml_order_id" => $xml_order_id_prefix . $xml_order_id,
							);
							
							if (array_key_exists("@", $nodes) && is_array($nodes["@"]) && empty($options["convert_without_attributes"])) {
								!empty($options["upper_case_keys"]) ? MyArray::arrKeysToUpperCase($nodes["@"], false) : (!empty($options["upper_case_keys"]) ? MyArray::arrKeysToLowerCase($nodes["@"], false) : null);
								$n["@"] = $nodes["@"];
							}
							
							if ($sub_nodes)
								$n["childs"] = $sub_nodes;
							
							!empty($options["upper_case_keys"]) ? MyArray::arrKeysToUpperCase($n, false) : (!empty($options["upper_case_keys"]) ? MyArray::arrKeysToLowerCase($n, false) : null);
							
							$new_arr[$k][] = $n;
							$xml_order_id++;
						}
					}
				}
			}
		}
		
		if (is_array($options))
			foreach ($options as $option_key => $option_value) 
				if (!empty($option_value)) {
					/* No need this here bc we are calling this directly above
					if ($option_key == "convert_without_attributes")
						$new_arr = self::convertComplexArrayWithoutAttributes($new_arr, $options);
					else */if ($option_key == "convert_childs_to_attributes")
						$new_arr = self::convertChildsToAttributesInComplexArray($new_arr, $options);
					else if ($option_key == "convert_attributes_to_childs")
						$new_arr = self::convertAttributesToChildsInComplexArray($new_arr, $options);
					else if ($option_key == "discard_nodes")
						$new_arr = self::discardNodesInComplexArray($new_arr, $options);
				}
		
		return $new_arr;
	}
	
	public static function discardNodesInComplexArray($arr, $options = false) {
		if (is_array($arr) && !empty($arr) && $options && isset($options["discard_nodes"]) && is_array($options["discard_nodes"])) {
			foreach ($arr as $key => $nodes) {
				$is_numeric_keys = true;
				foreach ($nodes as $k => $aux) {
					if (!is_numeric($k)) {
						$is_numeric_keys = false;
						break;
					}
				}
			
				if (!$is_numeric_keys) {
					$nodes = array($nodes);
				}
			
				$t = $nodes ? count($nodes) : 0;
				for ($i = 0; $i < $t; $i++) {
					if (!empty($nodes[$i]["childs"]))
						foreach ($options["discard_nodes"] as $node_key)
							if (array_key_exists($node_key, $nodes[$i]["childs"]))
								unset($nodes[$i]["childs"][$node_key]);
				}
				
				$arr[$key] = !$is_numeric_keys ? $nodes[0] : $nodes;
			}
		}
		
		return $arr;
	}
	
	public static function discardNodesInBasicArray($arr, $options = false) {
		if (is_array($arr) && !empty($arr) && $options && isset($options["discard_nodes"]) && is_array($options["discard_nodes"])) {
			foreach ($arr as $key => $nodes) {
				if (is_array($nodes)) {
					$is_numeric_keys = true;
					foreach ($nodes as $k => $aux) {
						if (!is_numeric($k)) {
							$is_numeric_keys = false;
							break;
						}
					}
				
					if (!$is_numeric_keys) 
						$nodes = array($nodes);
					
					$changed = false;
					$t = $nodes ? count($nodes) : 0;
					for ($i = 0; $i < $t; $i++) 
						if (is_array($nodes[$i])) {
							foreach ($options["discard_nodes"] as $node_key)
								if (array_key_exists($node_key, $nodes[$i])) {
									unset($nodes[$i][$node_key]);
									$changed = true;
								}
						}
					
					if ($changed)
						$arr[$key] = !$is_numeric_keys ? $nodes[0] : $nodes;
				}
			}
		}
		
		return $arr;
	}
	
	public static function convertComplexArrayWithoutAttributes($arr, $options = false) {
		if (is_array($arr) && !empty($arr)) {
			foreach ($arr as $key => $nodes) {
				$is_numeric_keys = true;
				foreach ($nodes as $k => $aux) {
					if (!is_numeric($k)) {
						$is_numeric_keys = false;
						break;
					}
				}
			
				if (!$is_numeric_keys) {
					$nodes = array($nodes);
				}
			
				$t = $nodes ? count($nodes) : 0;
				for ($i = 0; $i < $t; $i++) {
					unset($nodes[$i]["@"]);
				}
				
				$arr[$key] = !$is_numeric_keys ? $nodes[0] : $nodes;
			}
		}
		
		return $arr;
	}
	
	public static function convertBasicArrayWithoutAttributes($arr, $options = false) {
		if (is_array($arr) && !empty($arr)) {
			foreach ($arr as $key => $nodes) {
				if (is_array($nodes)) {
					$is_numeric_keys = true;
					foreach ($nodes as $k => $aux) {
						if (!is_numeric($k)) {
							$is_numeric_keys = false;
							break;
						}
					}
				
					if (!$is_numeric_keys) 
						$nodes = array($nodes);
					
					$changed = false;
					$t = $nodes ? count($nodes) : 0;
					for ($i = 0; $i < $t; $i++) 
						if (is_array($nodes[$i])) {
							unset($nodes[$i]["@"]);
							$keys = array_keys($nodes[$i]);
							
							if (count($keys) == 1 && array_key_exists("value", $nodes[$i]))
								$nodes[$i] = $nodes[$i]["value"];
							else if (count($keys) == 0)
								$nodes[$i] = null;
							
							$changed = true;
						}
					
					if ($changed)
						$arr[$key] = !$is_numeric_keys ? $nodes[0] : $nodes;
				}
			}
		}
		
		return $arr;
	}
	
	public static function convertAttributesToChildsInComplexArray($arr, $options = false) {
		if (is_array($arr) && !empty($arr)) {
			$upper_case_keys = !empty($options["upper_case_keys"]);
			$lower_case_keys = !empty($options["lower_case_keys"]);
			$trim = !empty($options["trim"]);
			
			foreach ($arr as $key => $nodes) {
				$is_numeric_keys = true;
				foreach ($nodes as $k => $aux) {
					if (!is_numeric($k)) {
						$is_numeric_keys = false;
						break;
					}
				}
			
				if (!$is_numeric_keys) {
					$nodes = array($nodes);
				}
			
				$t = $nodes ? count($nodes) : 0;
				for ($i = 0; $i < $t; $i++) {
					if (isset($nodes[$i]["@"]) && is_array($nodes[$i]["@"])) {
						foreach ($nodes[$i]["@"] as $attr_key => $attr_value) {
							$ak = $upper_case_keys ? strtoupper($attr_key) : ($lower_case_keys ? strtolower($attr_key) : $attr_key);
							$attr_value = $trim && $attr_value ? trim($attr_value) : $attr_value;
							
							//checks if nodes is in uppercase or lowercase. In this case, default is lowercase.
							$name_key = isset($nodes[$i]["NAME"]) ? "NAME" : "name";
							$value_key = isset($nodes[$i]["NAME"]) ? "VALUE" : "value";
							
							$n = array(
								$name_key => $attr_key,
								$value_key => $attr_value
							);
							
							$node_keys = array_keys($nodes[$i]);
							$node_lower_keys = array_flip(array_map("strtolower", $node_keys));
							$childs_key = isset($node_lower_keys["childs"]) ? $node_keys[ $node_lower_keys["childs"] ] : (isset($nodes[$i]["NAME"]) ? "CHILDS" : "childs");//childs/CHILDS/Childs...
							
							if (!isset($nodes[$i][$childs_key][$ak])) {
								$nodes[$i][$childs_key][$ak] = $n;
							}
							else {
								if (isset($nodes[$i][$childs_key][$ak][$name_key])) {
									$nodes[$i][$childs_key][$ak] = array($nodes[$i][$childs_key][$ak]);
								}
								
								$nodes[$i][$childs_key][$ak][] = $n;
							}
						}
				
						unset($nodes[$i]["@"]);
					}
				}
				
				$arr[$key] = !$is_numeric_keys ? $nodes[0] : $nodes;
			}
			
		}
		
		return $arr;
	}
	
	public static function convertAttributesToChildsInBasicArray($arr, $options = false) {
		if (is_array($arr) && !empty($arr)) {
			$upper_case_keys = !empty($options["upper_case_keys"]);
			$lower_case_keys = !empty($options["lower_case_keys"]);
			$trim = !empty($options["trim"]);
			//echo"<pre>";print_r($arr);
			
			foreach ($arr as $key => $nodes) {
				if (is_array($nodes)) {
					$is_numeric_keys = true;
					if (!is_array($nodes)){echo "$key<pre>";print_r($arr);}
					foreach ($nodes as $k => $aux) {
						if (!is_numeric($k)) {
							$is_numeric_keys = false;
							break;
						}
					}
					
					if (!$is_numeric_keys) 
						$nodes = array($nodes);
				
					$t = $nodes ? count($nodes) : 0;
					for ($i = 0; $i < $t; $i++)
						if (isset($nodes[$i]["@"]) && is_array($nodes[$i]["@"])) {
							foreach ($nodes[$i]["@"] as $attr_key => $attr_value) {
								$ak = $upper_case_keys ? strtoupper($attr_key) : ($lower_case_keys ? strtolower($attr_key) : $attr_key);
								$attr_value = $trim && $attr_value ? trim($attr_value) : $attr_value;
						
								if (!isset($nodes[$i][$ak]))
									$nodes[$i][$ak] = $attr_value;
								else {
									if (!is_array($nodes[$i][$ak]))
										$nodes[$i][$ak] = array($nodes[$i][$ak]);
									
									$nodes[$i][$ak][] = $attr_value;
								}
							}
					
							unset($nodes[$i]["@"]);
						}
					
					$arr[$key] = !$is_numeric_keys ? $nodes[0] : $nodes;
				}
			}
		}
		
		return $arr;
	}
	
	public static function convertChildsToAttributesInComplexArray($arr, $options = false) {
		if (is_array($arr) && !empty($arr)) {
			$upper_case_keys = !empty($options["upper_case_keys"]);
			$lower_case_keys = !empty($options["lower_case_keys"]);
			$trim = !empty($options["trim"]);
			
			$aux = $arr;
			
			foreach ($aux as $key => $nodes) {
				if (is_array($nodes)) {
					$is_numeric_keys = true;
					foreach ($nodes as $k => $aux) {
						if (!is_numeric($k)) {
							$is_numeric_keys = false;
							break;
						}
					}
			
					if (!$is_numeric_keys) 
						$nodes = array($nodes);
			
					$t = $nodes ? count($nodes) : 0;
					for ($i = 0; $i < $t; $i++) {
						$node_keys = array_keys($nodes[$i]);
						$node_lower_keys = array_flip(array_map("strtolower", $node_keys));
						$childs_key = $node_keys[ $node_lower_keys["childs"] ];//childs/CHILDS/Childs...
					
						if (isset($nodes[$i][$childs_key]) && is_array($nodes[$i][$childs_key])) {
							foreach ($nodes[$i][$childs_key] as $child_key => $child_nodes) {
								$cnk = array_keys($child_nodes[0]);
								$cnlk = array_flip(array_map("strtolower", $cnk));
								$sub_childs_key = isset($cnlk["childs"]) ? $cnk[ $cnlk["childs"] ] : "childs";//childs/CHILDS/Childs...
								
								if ($child_nodes && count($child_nodes) == 1 && empty($child_nodes[0][$sub_childs_key])) {
									$sub_name_key = isset($cnlk["name"]) ? $cnk[ $cnlk["name"] ] : "name";//name/NAME
									$sub_value_key = isset($cnlk["value"]) ? $cnk[ $cnlk["value"] ] : "value";//value/VALUE
									
									$attr_key = isset($child_nodes[0][$sub_name_key]) ? $child_nodes[0][$sub_name_key] : null;
									$attr_value = isset($child_nodes[0][$sub_value_key]) ? $child_nodes[0][$sub_value_key] : null;
									
									$attr_key = $upper_case_keys ? strtoupper($attr_key) : ($lower_case_keys ? strtolower($attr_key) : $attr_key);
									$attr_value = $trim && $attr_value && !is_array($attr_value) ? trim($attr_value) : $attr_value;
								
									$nodes[$i]["@"][$attr_key] = $attr_value;
									unset($nodes[$i][$childs_key][$child_key]);
								}
							}
							
							if (empty($nodes[$i][$childs_key])) {
								unset($nodes[$i][$childs_key]);
							}
						}
					}
				
					$arr[$key] = !$is_numeric_keys ? $nodes[0] : $nodes;
				}
				else {
					$k = $upper_case_keys ? strtoupper($key) : ($lower_case_keys ? strtolower($key) : $key);
					$nodes = $trim && $nodes ? trim($nodes) : $nodes;
					
					$nodes["@"][$k] = $nodes;
					unset($arr[$key]);
				}
			}
		}
		
		return $arr;
	}
	
	public static function convertChildsToAttributesInBasicArray($arr, $options = false) {
		if (is_array($arr) && !empty($arr)) {
			$upper_case_keys = !empty($options["upper_case_keys"]);
			$lower_case_keys = !empty($options["lower_case_keys"]);
			$trim = !empty($options["trim"]);
			
			$aux = $arr;
			
			foreach ($aux as $key => $nodes) {
				if (is_array($nodes)) {
					$is_numeric_keys = true;
					foreach ($nodes as $k => $aux) {
						if (!is_numeric($k)) {
							$is_numeric_keys = false;
							break;
						}
					}
			
					if (!$is_numeric_keys) {
						$nodes = array($nodes);
					}
					
					$t = $nodes ? count($nodes) : 0;
					for ($i = 0; $i < $t; $i++) {
						$node = $nodes[$i];
					
						foreach ($node as $child_key => $child_nodes) {
							if (strtolower($child_key) != "value" && $child_key != "@") {
								if (!is_array($child_nodes)) {
									$ck = $upper_case_keys ? strtoupper($child_key) : ($lower_case_keys ? strtolower($child_key) : $child_key);
									$child_nodes = $trim && $child_nodes ? trim($child_nodes) : $child_nodes;
								
									$nodes[$i]["@"][$ck] = $child_nodes;
									unset($nodes[$i][$child_key]);
								}
								else {
									$nodes[$i][$child_key] = self::convertChildsToAttributesInBasicArray($child_nodes, $options);
								}
							}
						}
					}
					
					$arr[$key] = !$is_numeric_keys ? $nodes[0] : $nodes;
				}
				else if (strtolower($key) != "value" && $key != "@") {
					$k = $upper_case_keys ? strtoupper($key) : ($lower_case_keys ? strtolower($key) : $key);
					$nodes = $trim && $nodes ? trim($nodes) : $nodes;
					
					$arr["@"][$k] = $nodes;
					unset($arr[$key]);
				}
			}
		}
		
		return $arr;
	}
	
	public function toXML() {
		return $this->asXML();
	}
	/*
	public function toXML($options = false) {
		$name = $this->getName();
		
		$simple = $options["simple"];
		$to_decimal = $options["to_decimal"];
		
		$attrs_str = "";
		if($simple) {
			$attrs = $this->getAttributesArray();
			foreach($attrs as $attr_name => $attr_value) {
				$attr_value = htmlentities($attr_value);
				if($to_decimal) {
					$attr_value = UnicodeUTF8::to_decimal(htmlentities(html_entity_decode($attr_value)));
				} 
				else {
					$attr_value = str_replace("&", "&amp;", str_replace("&amp;", "&", $attr_value));
				}
				$attrs_str .= " {$attr_name}=\"{$attr_value}\"";
			}
		}
		
		$xml = "<{$name}" . $attrs_str . ">";
		if($this->getChildrenCount() > 0) {
			foreach($this->children() as $node){
				$xml .= $node->toXML($options);
			}
		}
		else {
			$xml .= "<![CDATA[" . ((string)$this) . "]]>";
		}
		$xml .= "</{$name}>";
		
		return $xml;
	}*/
	
	/*
	* SimpleXMLElement->addAttribute()  Adds an attribute to the SimpleXML element
	* SimpleXMLElement->addChild()  Adds a child element to the XML node
	* SimpleXMLElement->asXML()  Return a well-formed XML string based on SimpleXML element
	* SimpleXMLElement->attributes()  Identifies an element's attributes
	* SimpleXMLElement->children()  Finds children of given node
	* SimpleXMLElement->__construct()  Creates a new SimpleXMLElement object
	* SimpleXMLElement->getDocNamespaces()  Returns namespaces declared in document
	* SimpleXMLElement->getName()  Gets the name of the XML element
	* SimpleXMLElement->getNamespaces()  Returns namespaces used in document
	* SimpleXMLElement->registerXPathNamespace()  Creates a prefix/ns context for the next XPath query
	* SimpleXMLElement->xpath()  Runs XPath query on XML data
	* simplexml_import_dom  Get a SimpleXMLElement object from a DOM node.
	* simplexml_load_file  Interprets an XML file into an object
	* simplexml_load_string  Interprets 	
	*/
}
?>
