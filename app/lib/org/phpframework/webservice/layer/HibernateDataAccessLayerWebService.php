<?php
include_once get_lib("org.phpframework.webservice.layer.LayerWebService");

class HibernateDataAccessLayerWebService extends LayerWebService {
	
	public function __construct($PHPFrameWork, $settings = false) {
		parent::__construct($PHPFrameWork, $settings);
		
		$this->web_service_validation_string = "_is_hibernate_webservice";
		$this->broker_server_bean_name = HIBERNATE_DATA_ACCESS_BROKER_SERVER_BEAN_NAME;
	}
}
?>
