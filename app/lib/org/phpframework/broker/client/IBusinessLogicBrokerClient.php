<?php
interface IBusinessLogicBrokerClient {
	
	public function callBusinessLogic($module, $service, $parameters = false, $options = false);
}
?>
