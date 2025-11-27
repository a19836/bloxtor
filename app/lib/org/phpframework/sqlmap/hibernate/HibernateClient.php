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

include_once get_lib("org.phpframework.sqlmap.SQLMapClient");
include get_lib("org.phpframework.sqlmap.hibernate.HibernateClientCache");
include get_lib("org.phpframework.sqlmap.hibernate.HibernateClassHandler");
include get_lib("org.phpframework.sqlmap.hibernate.exception.HibernateException");

class HibernateClient extends SQLMapClient {
	private $HibernateClassHandler;
	
	private $CacheLayer;
	
	public function __construct() {
		parent::__construct();
		
		$this->HibernateClassHandler = new HibernateClassHandler();
		
		$this->setSQLMapClientCache(new HibernateClientCache());
		$this->HibernateClassHandler->setHibernateClientCache($this->getSQLMapClientCache());
	}
	
	public function setRDBBroker($RDBBroker) {
		parent::setRDBBroker($RDBBroker);
		
		$this->HibernateClassHandler->setRDBBroker($this->getRDBBroker());
	}
	
	public function loadXML($obj_path) {
		if($this->getSQLMapClientCache()->cachedXMLElmExists($obj_path)) {
			$nodes = $this->getSQLMapClientCache()->getCachedXMLElm($obj_path);
			$this->setNodesData($nodes);
		}
		else {
			$nodes = self::getHibernateObjectNodeConfiguredFromFilePath($obj_path);
			$this->setNodesData($nodes);
			
			$this->getSQLMapClientCache()->setCachedXMLElm($obj_path, $nodes);
		}
	}
	
	public static function getHibernateObjectNodeConfiguredFromFilePath($obj_path) {
		$xml_schema_file_path = get_lib("org.phpframework.xmlfile.schema.hibernate_sql_mapping", "xsd");
		$nodes = XMLFileParser::parseXMLFileToArray($obj_path, false, $xml_schema_file_path);
		
		$first_node_name = is_array($nodes) ? array_keys($nodes) : array();
		$first_node_name = isset($first_node_name[0]) ? $first_node_name[0] : null;
		
		if (!empty($nodes[$first_node_name][0]["childs"])) {
			$nodes = $nodes[$first_node_name][0]["childs"];
			
			$new_nodes = array();
			if (!empty($nodes["class"])) {
				$new_nodes["class"] = array();
				
				$t = count($nodes["class"]);
				for($i = 0; $i < $t; $i++) {
					$node = $nodes["class"][$i];
					
					self::prepareObjNode($node);
					
					$name = XMLFileParser::getAttribute($node, "name");
					if($name) {
						if(!isset($new_nodes["class"][$name]))
							$new_nodes["class"][$name] = $node;
						else
							launch_exception(new HibernateException(7, $name));
					}
					else
						launch_exception(new HibernateException(6, $obj_path));
				}
			}
			
			$nodes = $new_nodes;
		}
		else
			$nodes = array();
		
		return $nodes;
	}
	
	public static function prepareObjNode(&$node) {
		if (isset($node["childs"]["id"])) {
			$node["childs"]["id"] = HibernateClassHandler::convertIds($node["childs"]["id"]);
		}
		
		if (isset($node["childs"]["parameter_map"][0])) {
			SQLMapClient::configureMap($node["childs"]["parameter_map"][0], "parameter_map");
		}
		
		if (isset($node["childs"]["result_map"][0])) {
			SQLMapClient::configureMap($node["childs"]["result_map"][0], "result_map");
		}
		
		$node["childs"]["relationships"] = isset($node["childs"]["relationships"]) ? $node["childs"]["relationships"] : null;
		$node["childs"]["relationships"] = XMLFileParser::combineMultipleNodesInASingleNode($node["childs"]["relationships"]);
		$node["childs"]["relationships"] = isset($node["childs"]["relationships"][0]["childs"]) ? $node["childs"]["relationships"][0]["childs"] : null;
		
		$map_types = array("parameter_map", "result_map");
		foreach ($map_types as $map_type) {
			if (isset($node["childs"]["relationships"][$map_type])) {
				$new_maps = array();
				$t = count($node["childs"]["relationships"][$map_type]);
				
				for ($j = 0; $j < $t; $j++) {
					$map = $node["childs"]["relationships"][$map_type][$j];
					SQLMapClient::configureMap($map, $map_type);
					$map_id = isset($map["attrib"]["id"]) ? $map["attrib"]["id"] : null;
					
					if ($map_id)
						$new_maps[$map_id] = $map;
				}
				
				$node["childs"]["relationships"][$map_type] = $new_maps;
			}
		}
		
		$relationship_types = array("many_to_one", "many_to_many", "one_to_many", "one_to_one");
		foreach ($relationship_types as $relationship_type) {
			if (isset($node["childs"]["relationships"][$relationship_type])) {
				$node["childs"]["relationships"][$relationship_type] = HibernateClassHandler::convertRelations($node["childs"]["relationships"][$relationship_type], $node);
			}
		}
		//echo "<pre>";print_r($node["childs"]["relationships"]);
		
		$node["childs"]["queries"] = isset($node["childs"]["queries"]) ? $node["childs"]["queries"] : null;
		$node["childs"]["queries"] = XMLFileParser::combineMultipleNodesInASingleNode($node["childs"]["queries"]);
		$node["childs"]["queries"] = isset($node["childs"]["queries"][0]["childs"]) ? self::getDataAccessNodesConfigured($node["childs"]["queries"][0]["childs"]) : null;
		
		/* NOT TESTED! NOT SURE IF THIS MAKES SENSE (Basically it sets the default parameter and result maps for queries and relationships - Maybe this part is already done somewhere else?)
		$name = XMLFileParser::getAttribute($node, "name");
		
		$map = $node["childs"]["parameter_map"][0];
		if ($map) {
			if (!$map["attrib"]["id"]) {
				$map["attrib"]["id"] = trim($name) . "ParameterMap";
			}
			
			$map_id = $map["attrib"]["id"];
			
			if (!isset($node["childs"]["queries"]["parameter_map"][$map_id])) {
				$node["childs"]["queries"]["parameter_map"][$map_id] = $map;
			}
		}
		
		$map = $node["childs"]["result_map"][0];
		if ($map) {
			if (!$map["attrib"]["id"]) {
				$map["attrib"]["id"] = trim($name) . "ResultMap";
			}
			
			$map_id = $map["attrib"]["id"];
			
			if (!isset($node["childs"]["queries"]["result_map"][$map_id])) {
				$node["childs"]["queries"]["result_map"][$map_id] = $map;
			}
		}*/
		
		/* DEPRECATED : TO DELETE
		if ($node["childs"]["queries"]["result_map"]) {
			$maps = $node["childs"]["relationships"]["result_map"];
			
			if ($maps) {
				$node["childs"]["relationships"]["result_map"] = array_merge($node["childs"]["relationships"]["result_map"], $maps);
			}
		}*/
	
		return $node;
	}
	
	public function getHbnObj($obj_name, $module_id, $service_id, $options = false) {
		$xml_data = $this->getNodesData();
		$obj_data = isset($xml_data["class"][$obj_name]) ? $xml_data["class"][$obj_name] : null;
	
		if ($obj_data) {
			$options = is_array($options) ? $options : array();
			
			$class_file_obj_name = $obj_name . '_' . hash("crc32b", serialize(array($module_id, $service_id, $options)));
			
			$class_file_path = $this->HibernateClassHandler->getClassFilePath($class_file_obj_name, $obj_data, $options);
			if ($class_file_path && file_exists($class_file_path)) {
				include_once $class_file_path;
			
				if (ObjectHandler::checkObjClass($class_file_obj_name, "HibernateModel") && $this->getErrorHandler()->ok()) {
					eval("\$obj = new ".$class_file_obj_name."();");
					
					if ($this->getErrorHandler()->ok()) {
						//if the obj's file already exists previously, it will have other default options, so we want to merge them with the new options, which will be the default ones. Unless the $options["reset_hbn_default_options"] is set.
						if (!empty($options["reset_hbn_default_options"])) {
							$default_options = $options;
							unset($default_options["reset_hbn_default_options"]);
						}
						else {
							$default_options = $obj->getDefaultOptions();
							$default_options = $default_options ? array_merge($default_options, $options) : $options;
						}
						$obj->setDefaultOptions($default_options);
						
						$obj->setRDBBroker($this->getRDBBroker());
						$obj->setCacheLayer($this->getCacheLayer());
						$obj->setModuleId($module_id);
						$obj->setServiceId($service_id);
						return $obj;
					}
				}
			}
			else {
				launch_exception(new HibernateException(1, array($obj_name, $class_file_path)));
			}
		}
		else {
			launch_exception(new HibernateException(2, $obj_name));
		}
		return false;
	}
	
	public function setCacheLayer($CacheLayer) {$this->CacheLayer = $CacheLayer;}
	public function getCacheLayer() {return $this->CacheLayer;}
}
?>
