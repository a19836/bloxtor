<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

include_once get_lib("org.phpframework.broker.BrokerServer");

abstract class LocalBrokerServer extends BrokerServer {
	
	//just in case someone call this method by mistake, the system warns him. 
	public function callWebService() {
		launch_exception( new Exception("You cannot call the callWebService in the LocalBrokerServer, because is not a web service!\n The LocalBrokerServer should be called internally by other files, through the 'include' php function!") );
		return null;
	}
}
?>
