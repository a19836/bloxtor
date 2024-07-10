<?php
include_once get_lib("org.phpframework.broker.client.rest.RESTDataAccessBrokerClient");
include_once get_lib("org.phpframework.broker.client.IHibernateDataAccessBrokerClient");

class RESTHibernateDataAccessBrokerClient extends RESTDataAccessBrokerClient implements IHibernateDataAccessBrokerClient {
	
	public function callObject($module_id, $service_id, $options = false) {
		$settings = $this->settings;
		$settings["url"] .= "/$module_id/$service_id";
		
		return $this->requestResponse($settings, array("options" => $options));
	}
}
?>
