<?php
include_once get_lib("org.phpframework.broker.client.rest.RESTDataAccessBrokerClient");
include_once get_lib("org.phpframework.broker.client.IIbatisDataAccessBrokerClient");

class RESTIbatisDataAccessBrokerClient extends RESTDataAccessBrokerClient implements IIbatisDataAccessBrokerClient {
	
	public function callQuerySQL($module, $type, $service, $parameters = false, $options = false) {
		$settings = $this->settings;
		$settings["url"] .= "/$module/$type-sql/$service";
		
		return $this->requestResponse($settings, array("parameters" => $parameters, "options" => $options));
	}
	public function callQuery($module, $type, $service, $parameters = false, $options = false) {
		$settings = $this->settings;
		$settings["url"] .= "/$module/$type/$service";
		
		return $this->requestResponse($settings, array("parameters" => $parameters, "options" => $options));
	}
	
	public function callSelectSQL($module, $service, $parameters = false, $options = false) {
		return $this->callQuerySQL($module, "select", $service, $parameters, $options);
	}
	public function callSelect($module, $service, $parameters = false, $options = false) {
		return $this->callQuery($module, "select", $service, $parameters, $options);
	}
	
	public function callInsertSQL($module, $service, $parameters = false, $options = false) {
		return $this->callQuerySQL($module, "insert", $service, $parameters, $options);
	}
	public function callInsert($module, $service, $parameters = false, $options = false) {
		return $this->callQuery($module, "insert", $service, $parameters, $options);
	}
	
	public function callUpdateSQL($module, $service, $parameters = false, $options = false) {
		return $this->callQuerySQL($module, "update", $service, $parameters, $options);
	}
	public function callUpdate($module, $service, $parameters = false, $options = false) {
		return $this->callQuery($module, "update", $service, $parameters, $options);
	}
	
	public function callDeleteSQL($module, $service, $parameters = false, $options = false) {
		return $this->callQuerySQL($module, "delete", $service, $parameters, $options);
	}
	public function callDelete($module, $service, $parameters = false, $options = false) {
		return $this->callQuery($module, "delete", $service, $parameters, $options);
	}
	
	public function callProcedureSQL($module, $service, $parameters = false, $options = false) {
		return $this->callQuerySQL($module, "procedure", $service, $parameters, $options);
	}
	public function callProcedure($module, $service, $parameters = false, $options = false) {
		return $this->callQuery($module, "procedure", $service, $parameters, $options);
	}
}
?>
