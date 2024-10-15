<?php
include_once get_lib("org.phpframework.broker.server.rest.RESTBrokerServer");
include_once get_lib("org.phpframework.broker.server.local.LocalHibernateDataAccessBrokerServer");

class RESTHibernateDataAccessBrokerServer extends RESTBrokerServer {
	
	protected function setLocalBrokerServer() {
		$this->LocalBrokerServer = new LocalHibernateDataAccessBrokerServer($this->Layer);
	}
	
	protected function executeWebServiceResponse() {
		$parts = explode("/", $this->url);
		$parts_cloned = $parts;
		$function = array_pop($parts);
		$service = array_pop($parts);
		$module = implode("/", $parts);
		
		if (strtolower($function) == "callobject") {
			$obj = $this->LocalBrokerServer->callObject($module, $service, $this->options);
			
			return $this->getWebServiceResponse("callObject", array("module" => $module, "service" => $service, "options" => $this->options), $obj, $this->response_type);
		}
		else {
			$part_0 = isset($parts_cloned[0]) ? $parts_cloned[0] : null;
			$part_1 = isset($parts_cloned[1]) ? $parts_cloned[1] : null;
			
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
					$arguments = isset($_GET["args"]) ? $_GET["args"] : null;
					
					$func = "callObjectMethod";
					$func_args = array("module" => $module, "service" => $service, "options" => $this->options, "function" => $function, "func_args" => array("arguments" => $arguments, "parameters" => $this->parameters, "options" => $this->options));
					
					$args = !empty($arguments) && is_array($arguments) ? "'".(implode("','", $arguments))."', \$this->parameters,\$this->options" : "\$this->parameters,\$this->options";
					$obj = $this->LocalBrokerServer->callObject($module, $service, $this->options);
					eval("\$result = \$obj->{$function}({$args});");
			}
		/*error_log("\nServer:".get_class($this)."\n".print_r($result, 1), 3, "/var/www/html/livingroop/default/tmp/test.log");
		error_log("\nurl:".$this->url, 3, "/var/www/html/livingroop/default/tmp/test.log");
		error_log("\nresponse_type:".$this->response_type, 3, "/var/www/html/livingroop/default/tmp/test.log");
		error_log($this->getWebServiceResponse($func, $func_args, $result, $this->response_type), 3, "/var/www/html/livingroop/default/tmp/test.log");*/
			
			return $this->getWebServiceResponse($func, $func_args, $result, $this->response_type);
		}
	}
}
?>
