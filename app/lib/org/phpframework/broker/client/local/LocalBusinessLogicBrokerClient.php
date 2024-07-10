<?php
include_once get_lib("org.phpframework.broker.client.local.LocalBrokerClient");
include_once get_lib("org.phpframework.broker.client.IBusinessLogicBrokerClient");

class LocalBusinessLogicBrokerClient extends LocalBrokerClient implements IBusinessLogicBrokerClient {
	
	public function __construct() {
		parent::__construct();
	}
	
	public function callBusinessLogic($module, $service, $parameters = false, $options = false) {
		return $this->getBrokerServer()->callBusinessLogic($module, $service, $parameters, $options);
	}
	
	public function getBrokersDBdriversName() {
		return $this->getBrokerServer()->getBrokersDBdriversName();
	}
}
?>
