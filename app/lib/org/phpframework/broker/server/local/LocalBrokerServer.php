<?php
include_once get_lib("org.phpframework.broker.BrokerServer");

abstract class LocalBrokerServer extends BrokerServer {
	
	//just in case someone call this method by mistake, the system warns him. 
	public function callWebService() {
		launch_exception( new Exception("You cannot call the callWebService in the LocalBrokerServer, because is not a web service!\n The LocalBrokerServer should be called internally by other files, through the 'include' php function!") );
		return null;
	}
}
?>
