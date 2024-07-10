<?php
include_once get_lib("org.phpframework.broker.server.rest.RESTBrokerServer");
include_once get_lib("org.phpframework.broker.server.local.LocalIbatisDataAccessBrokerServer");

class RESTIbatisDataAccessBrokerServer extends RESTBrokerServer {
	
	protected function setLocalBrokerServer() {
		$this->LocalBrokerServer = new LocalIbatisDataAccessBrokerServer($this->Layer);
	}
	
	protected function executeWebServiceResponse() {
		//example: http://jplpinto.localhost/iorm/hospital/select/get_appointment?appointment_id=117
		
		$parts = explode("/", $this->url);
		$parts_cloned = $parts;
		$service = array_pop($parts);
		$type = strtolower(array_pop($parts));
		$module = implode("/", $parts);
		
		$type_exists = true;
		
		//error_log("\nRESTIbatisDataAccessBrokerServer\nurl:{$this->url}\nservice:{$service}!\ntype:{$type}!\nmodule:{$module}!\nparts_cloned:".print_r($parts_cloned, 1)."\nparameters:".print_r($this->parameters, 1)."\noptions:".print_r($this->options, 1)."\n\n", 3, "/var/www/html/livingroop/default/tmp/test.log");
		
		switch($type) {
			case "select": 
				$result = $this->LocalBrokerServer->callSelect($module, $service, $this->parameters, $this->options);
				break;
			case "select-sql": 
				$result = $this->LocalBrokerServer->callSelectSQL($module, $service, $this->parameters, $this->options); 
				break;
			case "insert": 
				$result = $this->LocalBrokerServer->callInsert($module, $service, $this->parameters, $this->options); 
				break;
			case "insert-sql": 
				$result = $this->LocalBrokerServer->callInsertSQL($module, $service, $this->parameters, $this->options);
				break;
			case "update": 
				$result = $this->LocalBrokerServer->callUpdate($module, $service, $this->parameters, $this->options); 
				break;
			case "update-sql": 
				$result = $this->LocalBrokerServer->callUpdateSQL($module, $service, $this->parameters, $this->options); 
				break;
			case "delete": 
				$result = $this->LocalBrokerServer->callDelete($module, $service, $this->parameters, $this->options);
				break;
			case "delete-sql": 
				$result = $this->LocalBrokerServer->callDeleteSQL($module, $service, $this->parameters, $this->options); 
				break;
			case "procedure": 
				$result = $this->LocalBrokerServer->callProcedure($module, $service, $this->parameters, $this->options); 
				break;
			case "procedure-sql": 
				$result = $this->LocalBrokerServer->callProcedureSQL($module, $service, $this->parameters, $this->options); 
				break;
			
			default: 
				$part_0 = isset($parts_cloned[0]) ? $parts_cloned[0] : null;
				$part_1 = isset($parts_cloned[1]) ? $parts_cloned[1] : null;
				
				//data access methods
				switch(strtolower($part_0)) { //module could be the method_name
					case "getbrokersdbdriversname": 
						$func = "getDBDriversName";
						$func_args = array();
						$result = $this->LocalBrokerServer->getBrokersDBDriversName(); 
						break;
					case "getfunction": 
						$func = "getFunction";
						$func_args = array("func_name" => $part_1, "parameters" => $this->parameters, "options" => $this->options);
						$result = $this->LocalBrokerServer->getFunction($part_1, $this->parameters, $this->options); 
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
						
						if ($result && strtolower($part_1) == "getinsertedid")
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
						
						if ($result && strtolower($part_1) == "getinsertedid")
							$result = $this->LocalBrokerServer->getInsertedId($this->options);
						break;
					case "getinsertedid": 
						$func = "getInsertedId";
						$func_args = array("options" => $this->options);
						$result = $this->LocalBrokerServer->getInsertedId($this->options);
						break;
					case "insertobject": 
						$func = "insertObject";
						$func_args = array("parameters" => $this->parameters, "options" => $options);
						$result = $this->LocalBrokerServer->insertObject(
							isset($this->parameters["table_name"]) ? $this->parameters["table_name"] : null,
							isset($this->parameters["attributes"]) ? $this->parameters["attributes"] : null,
							$this->options
						);
						break;
					case "updateobject": 
						$func = "updateObject";
						$func_args = array("parameters" => $this->parameters, "options" => $options);
						$result = $this->LocalBrokerServer->updateObject(
							isset($this->parameters["table_name"]) ? $this->parameters["table_name"] : null,
							isset($this->parameters["attributes"]) ? $this->parameters["attributes"] : null,
							isset($this->parameters["conditions"]) ? $this->parameters["conditions"] : null,
							$this->options
						);
						break;
					case "deleteobject": 
						$func = "deleteObject";
						$func_args = array("parameters" => $this->parameters, "options" => $options);
						$result = $this->LocalBrokerServer->deleteObject(
							isset($this->parameters["table_name"]) ? $this->parameters["table_name"] : null,
							isset($this->parameters["conditions"]) ? $this->parameters["conditions"] : null,
							$this->options
						);
						break;
					case "findobjects": 
						$func = "findObjects";
						$func_args = array("parameters" => $this->parameters, "options" => $options);
						$result = $this->LocalBrokerServer->findObjects(
							isset($this->parameters["table_name"]) ? $this->parameters["table_name"] : null,
							isset($this->parameters["attributes"]) ? $this->parameters["attributes"] : null,
							isset($this->parameters["conditions"]) ? $this->parameters["conditions"] : null,
							$this->options
						);
						break;
					case "countobjects": 
						$func = "countObjects";
						$func_args = array("parameters" => $this->parameters, "options" => $options);
						$result = $this->LocalBrokerServer->countObjects(
							isset($this->parameters["table_name"]) ? $this->parameters["table_name"] : null,
							isset($this->parameters["conditions"]) ? $this->parameters["conditions"] : null,
							$this->options
						);
						break;
					case "findrelationshipobjects": 
						$func = "findRelationshipObjects";
						$func_args = array("parameters" => $this->parameters, "options" => $options);
						$result = $this->LocalBrokerServer->findRelationshipObjects(
							isset($this->parameters["table_name"]) ? $this->parameters["table_name"] : null,
							isset($this->parameters["rel_elm"]) ? $this->parameters["rel_elm"] : null,
							isset($this->parameters["parent_conditions"]) ? $this->parameters["parent_conditions"] : null,
							$this->options
						);
						break;
					case "countrelationshipobjects": 
						$func = "countRelationshipObjects";
						$func_args = array("parameters" => $this->parameters, "options" => $options);
						$result = $this->LocalBrokerServer->countRelationshipObjects(
							isset($this->parameters["table_name"]) ? $this->parameters["table_name"] : null,
							isset($this->parameters["rel_elm"]) ? $this->parameters["rel_elm"] : null,
							isset($this->parameters["parent_conditions"]) ? $this->parameters["parent_conditions"] : null,
							$this->options
						);
						break;
					case "findObjectsColumnMax": 
						$func = "findObjectsColumnMax";
						$func_args = array("parameters" => $this->parameters, "options" => $options);
						$result = $this->LocalBrokerServer->findObjectsColumnMax(
							isset($this->parameters["table_name"]) ? $this->parameters["table_name"] : null,
							isset($this->parameters["attribute_name"]) ? $this->parameters["attribute_name"] : null,
							$this->options
						);
						break;
					
					default: 
						$type_exists = false;
				}
		}
		
		if ($type_exists) {
			$type_parts = explode("-", $type);
			$available_types = array("select", "insert", "update", "delete");
			
			if (in_array($type_parts[0], $available_types)) {
				$func = "call" . ucfirst($type_parts[0]) . ($type_parts[1] ? "SQL" : "");
				$func_args = array("module" => $module, "service" => $service, "parameters" => $this->parameters, "options" => $this->options);
			}
		/*error_log("\n".print_r($this->parameters, 1), 3, "/var/www/html/livingroop/default/tmp/test.log");
		error_log("\n".print_r($this->options, 1), 3, "/var/www/html/livingroop/default/tmp/test.log");
		error_log("\nServer:".get_class($this)."\n".print_r($result, 1), 3, "/var/www/html/livingroop/default/tmp/test.log");
		error_log("\nurl:".$this->url, 3, "/var/www/html/livingroop/default/tmp/test.log");
		error_log("\nresponse_type:".$this->response_type, 3, "/var/www/html/livingroop/default/tmp/test.log");
		error_log($this->getWebServiceResponse($func, $func_args, $result, $this->response_type), 3, "/var/www/html/livingroop/default/tmp/test.log");*/
				
			return $this->getWebServiceResponse($func, $func_args, $result, $this->response_type);
		}
	}
}
?>
