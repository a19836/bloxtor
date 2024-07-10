<?php
include_once get_lib("org.phpframework.broker.server.local.LocalBrokerServer");
include_once get_lib("org.phpframework.broker.server.IBusinessLogicBrokerServer");

class LocalBusinessLogicBrokerServer extends LocalBrokerServer implements IBusinessLogicBrokerServer {
	
	public function callBusinessLogic($module, $service, $parameters = false, $options = false) {
		return $this->Layer->callBusinessLogic($module, $service, $parameters, $options);
	}
	
	public function getBrokersDBDriversName() {
		return $this->Layer->getBrokersDBDriversName();
	}
}
?>
