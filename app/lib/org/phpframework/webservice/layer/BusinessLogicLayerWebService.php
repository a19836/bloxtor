<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

include_once get_lib("org.phpframework.webservice.layer.LayerWebService");

class BusinessLogicLayerWebService extends LayerWebService {
	
	public function __construct($PHPFrameWork, $settings = false) {
		parent::__construct($PHPFrameWork, $settings);
		
		$this->web_service_validation_string = "_is_businesslogic_webservice";
		$this->broker_server_bean_name = BUSINESS_LOGIC_BROKER_SERVER_BEAN_NAME;
	}
}
?>
