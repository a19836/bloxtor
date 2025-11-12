<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

include_once get_lib("org.phpframework.broker.server.rest.RESTBrokerServer");
include_once get_lib("org.phpframework.broker.server.local.LocalDBBrokerServer");

class RESTDBBrokerServer extends RESTBrokerServer {
	
	protected function setLocalBrokerServer() {
		$this->LocalBrokerServer = new LocalDBBrokerServer($this->Layer);
	}
	
	protected function executeWebServiceResponse() {
		$parts = explode("/", $this->url);
		$method = strtolower($parts[0]);
		$method_name = isset($parts[1]) ? strtolower($parts[1]) : "";//only for the getFunction, otherwise is empty
		
		$method_exists = true;
		
		switch($method) {
			case "getdbdriversname": 
				$func = "getDBDriversName";
				$func_args = array();
				$result = $this->LocalBrokerServer->getDBDriversName(); 
				break;
			case "getfunction": 
				$func = "getFunction";
				$func_args = array("func_name" => $method_name, "parameters" => $this->parameters, "options" => $this->options);
				$result = $this->LocalBrokerServer->getFunction($method_name, $this->parameters, $this->options);
				break;
			case "getdata": 
				$func = "getData";
				$func_args = array("parameters" => $this->parameters, "options" => $this->options);
				$result = $this->LocalBrokerServer->getData($this->parameters, $this->options);
				break;
			case "setdata": 
				$func = "setData";
				$func_args = array("parameters" => $this->parameters, "options" => $this->options);
				$result = $this->LocalBrokerServer->setData($this->parameters, $this->options);
				
				if ($result && strtolower($method_name) == "getinsertedid")
					$result = $this->LocalBrokerServer->getInsertedId($this->options);
				break;
			case "getsql": 
				$func = "getSQL";
				$func_args = array("parameters" => $this->parameters, "options" => $this->options);
				$result = $this->LocalBrokerServer->getSQL($this->parameters, $this->options);
				break;
			case "setsql": 
				$func = "setSQL";
				$func_args = array("parameters" => $this->parameters, "options" => $this->options);
				$result = $this->LocalBrokerServer->setSQL($this->parameters, $this->options);
				
				if ($result && strtolower($method_name) == "getinsertedid")
					$result = $this->LocalBrokerServer->getInsertedId($this->options);
				break;
			case "getinsertedid": 
				$func = "getInsertedId";
				$func_args = array("options" => $this->options);
				$result = $this->LocalBrokerServer->getInsertedId($this->options);
				break;
			case "insertobject": 
				$func = "insertObject";
				$func_args = array("parameters" => $this->parameters, "options" => $this->options);
				$result = $this->LocalBrokerServer->insertObject(
					isset($this->parameters["table_name"]) ? $this->parameters["table_name"] : null,
					isset($this->parameters["attributes"]) ? $this->parameters["attributes"] : null,
					$this->options
				);
				break;
			case "updateobject": 
				$func = "updateObject";
				$func_args = array("parameters" => $this->parameters, "options" => $this->options);
				$result = $this->LocalBrokerServer->updateObject(
					isset($this->parameters["table_name"]) ? $this->parameters["table_name"] : null,
					isset($this->parameters["attributes"]) ? $this->parameters["attributes"] : null,
					isset($this->parameters["conditions"]) ? $this->parameters["conditions"] : null,
					$this->options
				);
				break;
			case "deleteobject": 
				$func = "deleteObject";
				$func_args = array("parameters" => $this->parameters, "options" => $this->options);
				$result = $this->LocalBrokerServer->deleteObject(
					isset($this->parameters["table_name"]) ? $this->parameters["table_name"] : null,
					isset($this->parameters["conditions"]) ? $this->parameters["conditions"] : null,
					$this->options
				);
				break;
			case "findobjects": 
				$func = "findObjects";
				$func_args = array("parameters" => $this->parameters, "options" => $this->options);
				$result = $this->LocalBrokerServer->findObjects(
					isset($this->parameters["table_name"]) ? $this->parameters["table_name"] : null,
					isset($this->parameters["attributes"]) ? $this->parameters["attributes"] : null,
					isset($this->parameters["conditions"]) ? $this->parameters["conditions"] : null,
					$this->options
				);
				break;
			case "countobjects": 
				$func = "countObjects";
				$func_args = array("parameters" => $this->parameters, "options" => $this->options);
				$result = $this->LocalBrokerServer->countObjects(
					isset($this->parameters["table_name"]) ? $this->parameters["table_name"] : null,
					isset($this->parameters["conditions"]) ? $this->parameters["conditions"] : null,
					$this->options
				);
				break;
			case "findrelationshipobjects": 
				$func = "findRelationshipObjects";
				$func_args = array("parameters" => $this->parameters, "options" => $this->options);
				$result = $this->LocalBrokerServer->findRelationshipObjects(
					isset($this->parameters["table_name"]) ? $this->parameters["table_name"] : null,
					isset($this->parameters["rel_elm"]) ? $this->parameters["rel_elm"] : null,
					isset($this->parameters["parent_conditions"]) ? $this->parameters["parent_conditions"] : null,
					$this->options
				);
				break;
			case "countrelationshipobjects": 
				$func = "countRelationshipObjects";
				$func_args = array("parameters" => $this->parameters, "options" => $this->options);
				$result = $this->LocalBrokerServer->countRelationshipObjects(
					isset($this->parameters["table_name"]) ? $this->parameters["table_name"] : null,
					isset($this->parameters["rel_elm"]) ? $this->parameters["rel_elm"] : null,
					isset($this->parameters["parent_conditions"]) ? $this->parameters["parent_conditions"] : null,
					$this->options
				);
				break;
			case "findObjectsColumnMax": 
				$func = "findObjectsColumnMax";
				$func_args = array("parameters" => $this->parameters, "options" => $this->options);
				$result = $this->LocalBrokerServer->findObjectsColumnMax(
					isset($this->parameters["table_name"]) ? $this->parameters["table_name"] : null,
					isset($this->parameters["attribute_name"]) ? $this->parameters["attribute_name"] : null,
					$this->options
				);
				break;
			
			default: 
				$method_exists = false;
		}
		
		if ($method_exists)
			return $this->getWebServiceResponse($func, $func_args, $result, $this->response_type);
	}
}
?>
