<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

include_once get_lib("org.phpframework.sqlmap.SQLMapClient");
include get_lib("org.phpframework.sqlmap.SQLMapQueryHandler");
include get_lib("org.phpframework.sqlmap.SQLMapResultHandler");
include_once get_lib("org.phpframework.sqlmap.SQLMapIncludesHandler");
include get_lib("org.phpframework.sqlmap.ibatis.IBatisClientCache");
include get_lib("org.phpframework.sqlmap.ibatis.exception.IBatisException");

class IBatisClient extends SQLMapClient {
	private $QueryHandler;
	private $ResultHandler;
	
	public function __construct() {
		parent::__construct();
		
		$this->QueryHandler = new SQLMapQueryHandler();
		$this->ResultHandler = new SQLMapResultHandler();
		
		$this->setSQLMapClientCache(new IBatisClientCache());
	}
	
	public function loadXML($obj_path) {
		if ($this->getSQLMapClientCache()->cachedXMLElmExists($obj_path)) {
			$nodes = $this->getSQLMapClientCache()->getCachedXMLElm($obj_path);
			$this->setNodesData($nodes);
		}
		else {
			$xml_schema_file_path = get_lib("org.phpframework.xmlfile.schema.ibatis_sql_mapping", "xsd");
			$nodes = XMLFileParser::parseXMLFileToArray($obj_path, false, $xml_schema_file_path);
			
			$first_node_name = is_array($nodes) ? array_keys($nodes) : array();
			$first_node_name = isset($first_node_name[0]) ? $first_node_name[0] : null;
			
			if ($first_node_name && !empty($nodes[$first_node_name][0]["childs"])) {
				$nodes = self::getDataAccessNodesConfigured($nodes[$first_node_name][0]["childs"]);
			}
			else {
				$nodes = array();
			}
			
			$this->setNodesData($nodes);
			
			$this->getSQLMapClientCache()->setCachedXMLElm($obj_path, $nodes);
		}
	}
	
	public function getQuery($query_type, $query_id) {
		$query_type = strtolower($query_type);
		
		$available_types = array("insert", "update", "delete", "select", "procedure");
		if(in_array($query_type, $available_types)) {
			$nodes = $this->getNodesData();
			
			if(isset($nodes[$query_type][$query_id])) {
				return $nodes[$query_type][$query_id];
			}
			else {
				launch_exception(new IBatisException(2, array($query_type, $query_id)));
			}
		}
		else {
			launch_exception(new IBatisException(1, array($query_type, $available_types)));
		}
		return false;
	}
	
	public function getQuerySQL($query, $parameters = false, $options = false) {
		if($query) {
			$this->prepareParameters($query, $parameters);
			
			$sql = XMLFileParser::getValue($query);
			$auto_add_slashes = isset($options["auto_add_slashes"]) ? $options["auto_add_slashes"] : XMLFileParser::getAttribute($query, "auto_add_slashes");
			
			$auto_add_slashes = strtolower($auto_add_slashes);
			$auto_add_slashes = empty($auto_add_slashes) || (strlen($auto_add_slashes) > 0 && ($auto_add_slashes == "0" || in_array($auto_add_slashes, array("false", "no", "off")))) ? false : true;
			
			$this->QueryHandler->configureQuery($sql, $parameters, $auto_add_slashes);
			
			if($this->getErrorHandler()->ok()) {
				return $sql;
			}
		}
		return false;
	}
	
	public function getLibsOfResultClassAndMap($query) {
		$result_class = XMLFileParser::getAttribute($query, "result_class");
		$result_map = XMLFileParser::getAttribute($query, "result_map");
		
		if($result_map) {
			$xml_data = $this->getNodesData();
			if(isset($xml_data["result_map"][$result_map])) {
				$result_map = $xml_data["result_map"][$result_map];
			}
			else {
				launch_exception(new SQLMapResultException(4));
			}
		}
		
		$includes = SQLMapIncludesHandler::getLibsOfResultClassAndMap($result_class, $result_map);
		return $includes;
	}
	
	public function execQuery($query, $parameters = false, $options = false) {
		if($query) {
			$sql = $this->getQuerySQL($query, $parameters, $options);
			
			$query_type = isset($query["name"]) ? strtolower($query["name"]) : null;
			$result_class = XMLFileParser::getAttribute($query, "result_class");
			$result_map = XMLFileParser::getAttribute($query, "result_map");
			$hard_coded_ai_pk = XMLFileParser::getAttribute($query, "hard_coded_ai_pk");
			
			if($result_class && $result_map)
				launch_exception(new IBatisException(4, XMLFileParser::getAttribute($query, "id")));
			
			if($this->getErrorHandler()->ok()) {
				if($query_type == "select") {
					return $this->execSelect($sql, $result_class, $result_map, $options);
				}
				elseif($query_type == "procedure") {
					return $this->execProcedure($sql, $result_class, $result_map, $options);
				}
				else {
					//set hard_coded_ai_pk. This will be used by the mssql server
					if ($hard_coded_ai_pk && (!$options || is_array($options))) {
						if (!is_array($options))
							$options = array();
						
						$options["hard_coded_ai_pk"] = $hard_coded_ai_pk;
					}
					
					return $this->execIUD($sql, $options);
				}
			}
		}
		return false;
	}
	
	private function execProcedure($sql, $result_class, $result_map, $options) {
		$data = $this->getData($sql, $options);
		if(count($data) && $this->getErrorHandler()->ok()) {
			$this->ResultHandler->transformData($data, $result_class, $result_map, $this->getNodesData());
			return $data;
		}
		return false;
	}
	
	private function execSelect($sql, $result_class, $result_map, $options) {
		//Preparing $options[sort] according with result map
		if (is_array($options) && !empty($options["sort"])) {
			$this->ResultHandler->configureSortOptions($options["sort"], $result_map, $this->getNodesData());
		}
		
		$data = $this->getData($sql, $options);
		if(count($data) && $this->getErrorHandler()->ok()) {
			$this->ResultHandler->transformData($data, $result_class, $result_map, $this->getNodesData());
			return $data;
		}
		return false;
	}
	
	private function execIUD($sql, $options) {
		$status = $this->setData($sql, $options);
		
		if($this->getErrorHandler()->ok()) {
			return $status ? true : false;
		}
		elseif(is_a($status, "Exception"))
			return $status;
		return false;
	}
	
	private function prepareParameters($query, &$parameters) {
		if($query) {
			$parameter_class = XMLFileParser::getAttribute($query, "parameter_class");
			$parameter_map = XMLFileParser::getAttribute($query, "parameter_map");
			
			if($parameter_class && $parameter_map) {
				launch_exception(new IBatisException(3, XMLFileParser::getAttribute($query, "id")));
			}
			
			$this->QueryHandler->transformData($parameters, $parameter_class, $parameter_map, $this->getNodesData());
		}
	}
}
?>
