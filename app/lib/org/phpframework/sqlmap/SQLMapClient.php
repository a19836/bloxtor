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

include_once get_lib("org.phpframework.sqlmap.SQLMap");
include_once get_lib("org.phpframework.xmlfile.XMLFileParser");
include get_lib("org.phpframework.sqlmap.exception.SQLMapClientException");

class SQLMapClient extends SQLMap {
	private $nodes_data;
	
	private $RDBBroker;
	private $SQLMapClientCache;
	
	public function __construct() {
		parent::__construct();
	}
	
	public function setNodesData($nodes_data) {$this->nodes_data = $nodes_data;}
	public function getNodesData() {return $this->nodes_data;}
	
	public function setRDBBroker($RDBBroker) {$this->RDBBroker = $RDBBroker;}
	public function getRDBBroker() {return $this->RDBBroker;}
	
	public function setSQLMapClientCache($SQLMapClientCache) {$this->SQLMapClientCache = $SQLMapClientCache;}
	public function getSQLMapClientCache() {return $this->SQLMapClientCache;}
	
	public function setCacheRootPath($dir_path) {
		$this->SQLMapClientCache->initCacheDirPath($dir_path);
	}
	
	public static function getDataAccessNodesConfigured($nodes) {
		$available_types = array("insert", "update", "delete", "select", "procedure");
		
		if(is_array($nodes)) {
			$new_nodes = array();
			foreach($nodes as $node_type => $node) {
				if ($node_type != "import" && $node) {
					$t = count($node);
					for($i = 0; $i < $t; $i++) {
						$node_i = $node[$i];
					
						$id = XMLFileParser::getAttribute($node_i, "id");
					
						$new_node_i = array();
						if(in_array($node_type, $available_types)) {
							$new_node_i["name"] = isset($node_i["name"]) ? $node_i["name"] : null;
							$new_node_i["@"] = XMLFileParser::getAttributes($node_i, array("id", "parameter_class", "parameter_map", "result_class", "result_map", "hard_coded_ai_pk"));
							$new_node_i["value"] = XMLFileParser::getValue($node_i);
							
							//prepare hard_coded_ai_pk values
							if (!empty($new_node_i["@"]["hard_coded_ai_pk"]))
								$new_node_i["@"]["hard_coded_ai_pk"] = $new_node_i["@"]["hard_coded_ai_pk"] == 1 || in_array(strtolower($new_node_i["@"]["hard_coded_ai_pk"]), array("true", "yes", "on")); //all other values will be false
						}
						elseif($node_type == "parameter_map" || $node_type == "result_map") {
							$new_node_i = $node_i;
							self::configureMap($new_node_i, $node_type);
						}
						else {
							launch_exception(new SQLMapClientException(1, $node_type));
						}
					
						if($id) {
							if(!isset($new_nodes[$node_type][$id])) {
								$new_nodes[$node_type][$id] = $new_node_i;
							}
							else {
								launch_exception(new SQLMapClientException(3, array($node_type, $id)));
							}
						}
						else {
							launch_exception(new SQLMapClientException(2, $node_type));
						}
					}
				}
			}
			$nodes = $new_nodes;
		}
		return $nodes;
	}
	
	public static function configureMap(&$map, $map_type) {
		$child_node_name = "";
		switch(strtolower($map_type)) {
			case "parameter_map": 
				$child_node_name = "parameter"; 
				break;
			
			default: $child_node_name = "result";
		}
		
		$new_map = array();
		$new_map["attrib"] = XMLFileParser::getAttributes($map, array("id", "class"));
		
		$results = isset($map["childs"][$child_node_name]) ? $map["childs"][$child_node_name] : null;
		$t = $results ? count($results) : 0;
		for($i = 0; $i < $t; $i++) 
			$new_map[$child_node_name][] = XMLFileParser::getAttributes($results[$i], array("input_name", "input_type", "output_name", "output_type", "mandatory"));
		
		$map = $new_map;
	}
	
	public function getFunction($function_name, $parameters = false, $options = false) {
		return $this->getRDBBroker()->getFunction($function_name, $parameters, $options);
	}
	
	public function getData($sql, $options = false) {
		return $this->getRDBBroker()->getData($sql, $options);
	}
	
	public function setData($sql, $options = false) {
		return $this->getRDBBroker()->setData($sql, $options);
	}
	
	public function getSQL($sql, $options = false) {
		return $this->getRDBBroker()->getSQL($sql, $options);
	}
	
	public function setSQL($sql, $options = false) {
		return $this->getRDBBroker()->setSQL($sql, $options);
	}

    	public function getInsertedId($options = false) {
    		return $this->getRDBBroker()->getInsertedId($options);
	}
	
	public function insertObject($table_name, $attributes, $options = false) {
    		return $this->getRDBBroker()->insertObject($table_name, $attributes, $options);
	}
	
	public function updateObject($table_name, $attributes, $conditions = false, $options = false) {
    		return $this->getRDBBroker()->updateObject($table_name, $attributes, $conditions, $options);
	}
	
	public function deleteObject($table_name, $conditions = false, $options = false) {
    		return $this->getRDBBroker()->deleteObject($table_name, $conditions, $options);
	}
	
	public function findObjects($table_name, $attributes = false, $conditions = false, $options = false) {
    		return $this->getRDBBroker()->findObjects($table_name, $attributes, $conditions, $options);
	}
	
	public function countObjects($table_name, $conditions = false, $options = false) {
    		return $this->getRDBBroker()->countObjects($table_name, $conditions, $options);
	}
	
	public function findRelationshipObjects($table_name, $rel_elm, $parent_conditions = false, $options = false) {
    		return $this->getRDBBroker()->findRelationshipObjects($table_name, $rel_elm, $parent_conditions, $options);
	}
	
	public function countRelationshipObjects($table_name, $rel_elm, $parent_conditions = false, $options = false) {
    		return $this->getRDBBroker()->countRelationshipObjects($table_name, $rel_elm, $parent_conditions, $options);
	}
	
	public function findObjectsColumnMax($table_name, $attribute_name, $options = false) {
    		return $this->getRDBBroker()->findObjectsColumnMax($table_name, $attribute_name, $options);
	}
}
?>
