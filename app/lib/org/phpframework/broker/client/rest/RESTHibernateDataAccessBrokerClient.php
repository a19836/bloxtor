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
