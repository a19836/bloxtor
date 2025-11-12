<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

include_once get_lib("org.phpframework.broker.server.local.LocalDataAccessBrokerServer");
include_once get_lib("org.phpframework.broker.server.IIbatisDataAccessBrokerServer");

class LocalIbatisDataAccessBrokerServer extends LocalDataAccessBrokerServer implements IIbatisDataAccessBrokerServer {
	
	public function callQuerySQL($module, $type, $service, $parameters = false, $options = false) {
		return $this->Layer->callQuerySQL($module, $type, $service, $parameters, $options);
	}
	public function callQuery($module, $type, $service, $parameters = false, $options = false) {
		return $this->Layer->callQuery($module, $type, $service, $parameters, $options);
	}
	
	public function callSelectSQL($module, $service, $parameters = false, $options = false) {
		return $this->Layer->callSelectSQL($module, $service, $parameters, $options);
	}
	public function callSelect($module, $service, $parameters = false, $options = false) {
		return $this->Layer->callSelect($module, $service, $parameters, $options);
	}
	
	public function callInsertSQL($module, $service, $parameters = false, $options = false) {
		return $this->Layer->callInsertSQL($module, $service, $parameters, $options);
	}
	public function callInsert($module, $service, $parameters = false, $options = false) {
		return $this->Layer->callInsert($module, $service, $parameters, $options);
	}
	
	public function callUpdateSQL($module, $service, $parameters = false, $options = false) {
		return $this->Layer->callUpdateSQL($module, $service, $parameters, $options);
	}
	public function callUpdate($module, $service, $parameters = false, $options = false) {
		return $this->Layer->callUpdate($module, $service, $parameters, $options);
	}
	
	public function callDeleteSQL($module, $service, $parameters = false, $options = false) {
		return $this->Layer->callDeleteSQL($module, $service, $parameters, $options);
	}
	public function callDelete($module, $service, $parameters = false, $options = false) {
		return $this->Layer->callDelete($module, $service, $parameters, $options);
	}
	
	public function callProcedureSQL($module, $service, $parameters = false, $options = false) {
		return $this->Layer->callProcedureSQL($module, $service, $parameters, $options);
	}
	public function callProcedure($module, $service, $parameters = false, $options = false) {
		return $this->Layer->callProcedure($module, $service, $parameters, $options);
	}
}
?>
