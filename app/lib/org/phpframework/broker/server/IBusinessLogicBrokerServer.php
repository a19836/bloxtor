<?php
interface IBusinessLogicBrokerServer {
	
	public function callBusinessLogic($module, $service, $parameters = false, $options = false);
}
?>
