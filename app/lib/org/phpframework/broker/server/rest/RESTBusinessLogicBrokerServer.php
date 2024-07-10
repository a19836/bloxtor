<?php
include_once get_lib("org.phpframework.broker.server.rest.RESTBrokerServer");
include_once get_lib("org.phpframework.broker.server.local.LocalBusinessLogicBrokerServer");

class RESTBusinessLogicBrokerServer extends RESTBrokerServer {
	
	protected function setLocalBrokerServer() {
		$this->LocalBrokerServer = new LocalBusinessLogicBrokerServer($this->Layer);
	}
	
	protected function executeWebServiceResponse() {
		$parts = explode("/", $this->url);
		
		if (strtolower($parts[0]) == "getbrokersdbdriversname") {
			$result = $this->LocalBrokerServer->getBrokersDBdriversName();
			return $this->getWebServiceResponse("getBrokersDBdriversName", null, $result, $this->response_type);
		}
		else {
			$service = array_pop($parts);
			$module = implode("/", $parts);
			$result = $this->LocalBrokerServer->callBusinessLogic($module, $service, $this->parameters, $this->options);
			$func_args = array("module" => $module, "service" => $service, "parameters" => $this->parameters, "options" => $this->options);
			return $this->getWebServiceResponse("callBusinessLogic", $func_args, $result, $this->response_type);
		}
	}
}
?>
