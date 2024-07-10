<?php
include_once get_lib("org.phpframework.broker.client.rest.RESTBrokerClient");
include_once get_lib("org.phpframework.broker.client.IBusinessLogicBrokerClient");

class RESTBusinessLogicBrokerClient extends RESTBrokerClient implements IBusinessLogicBrokerClient {
	
	public function callBusinessLogic($module, $service, $parameters = false, $options = false) {
		$settings = $this->settings;
		$settings["url"] .= "/$module/$service";
		
		return $this->requestResponse($settings, array("parameters" => $parameters, "options" => $options));
	}
	
	public function getBrokersDBdriversName() {
		$settings = $this->settings;
		$settings["url"] .= "/getBrokersDBdriversName";
		
		return $this->requestResponse($settings);
	}
}
?>
