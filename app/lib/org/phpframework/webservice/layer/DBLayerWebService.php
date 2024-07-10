<?php
include_once get_lib("org.phpframework.webservice.layer.LayerWebService");

class DBLayerWebService extends LayerWebService {
	
	public function __construct($PHPFrameWork, $settings = false) {
		parent::__construct($PHPFrameWork, $settings);
		
		$this->web_service_validation_string = "_is_db_webservice";
		$this->broker_server_bean_name = DB_BROKER_SERVER_BEAN_NAME;
	}
}
?>
