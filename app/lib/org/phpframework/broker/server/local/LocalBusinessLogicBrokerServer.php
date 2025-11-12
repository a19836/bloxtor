<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

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
