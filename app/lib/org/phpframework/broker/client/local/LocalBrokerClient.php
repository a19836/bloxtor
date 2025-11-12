<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

include_once get_lib("org.phpframework.broker.BrokerClient");
include_once get_lib("org.phpframework.broker.server.local.LocalBrokerServer");

abstract class LocalBrokerClient extends BrokerClient {
	private $BrokerServer;
	
	public function setBrokerServer(LocalBrokerServer $BrokerServer) {
		$this->BrokerServer = $BrokerServer;
	}
	
	//getBrokerServer should NOT be Public, bc it should not be used outside of this class or parent classes, otherwise if a BrokerClient is from Rest type, this method cannot be called bc it will not exists in the RestBrokerClient class. Leave this protected. 2020-07-30
	public function getBrokerServer() {
		if(!$this->BrokerServer || !$this->PHPFrameWorkHandler->objExists()) {
			$this->PHPFrameWorkHandler->loadBeansFile();
			$this->BrokerServer = $this->PHPFrameWorkHandler->getObject();
		}
		return $this->BrokerServer;
	}
}
?>
