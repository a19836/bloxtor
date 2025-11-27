<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 *
 * Original Bloxtor Repo: https://github.com/a19836/bloxtor
 *
 * YOU ARE NOT AUTHORIZED TO MODIFY OR REMOVE ANY PART OF THIS NOTICE!
 */

include_once get_lib("org.phpframework.broker.client.local.LocalDataAccessBrokerClient");
include_once get_lib("org.phpframework.broker.client.IIbatisDataAccessBrokerClient");

class LocalIbatisDataAccessBrokerClient extends LocalDataAccessBrokerClient implements IIbatisDataAccessBrokerClient {
	
	public function callQuerySQL($module, $type, $service, $parameters = false, $options = false) {
		return $this->getBrokerServer()->callQuerySQL($module, $type, $service, $parameters, $options);
	}
	public function callQuery($module, $type, $service, $parameters = false, $options = false) {
		return $this->getBrokerServer()->callQuery($module, $type, $service, $parameters, $options);
	}
	
	public function callSelectSQL($module, $service, $parameters = false, $options = false) {
		return $this->getBrokerServer()->callSelectSQL($module, $service, $parameters, $options);
	}
	public function callSelect($module, $service, $parameters = false, $options = false) {
		return $this->getBrokerServer()->callSelect($module, $service, $parameters, $options);
	}
	
	public function callInsertSQL($module, $service, $parameters = false, $options = false) {
		return $this->getBrokerServer()->callInsertSQL($module, $service, $parameters, $options);
	}
	public function callInsert($module, $service, $parameters = false, $options = false) {
		return $this->getBrokerServer()->callInsert($module, $service, $parameters, $options);
	}
	
	public function callUpdateSQL($module, $service, $parameters = false, $options = false) {
		return $this->getBrokerServer()->callUpdateSQL($module, $service, $parameters, $options);
	}
	public function callUpdate($module, $service, $parameters = false, $options = false) {
		return $this->getBrokerServer()->callUpdate($module, $service, $parameters, $options);
	}
	
	public function callDeleteSQL($module, $service, $parameters = false, $options = false) {
		return $this->getBrokerServer()->callDeleteSQL($module, $service, $parameters, $options);
	}
	public function callDelete($module, $service, $parameters = false, $options = false) {
		return $this->getBrokerServer()->callDelete($module, $service, $parameters, $options);
	}
	
	public function callProcedureSQL($module, $service, $parameters = false, $options = false) {
		return $this->getBrokerServer()->callProcedureSQL($module, $service, $parameters, $options);
	}
	public function callProcedure($module, $service, $parameters = false, $options = false) {
		return $this->getBrokerServer()->callProcedure($module, $service, $parameters, $options);
	}
}
?>
