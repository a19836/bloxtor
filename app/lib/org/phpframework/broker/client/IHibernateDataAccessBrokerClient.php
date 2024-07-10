<?php
interface IHibernateDataAccessBrokerClient {
	
	public function callObject($module_id, $service_id, $options = false);
}
?>
