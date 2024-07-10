<?php
interface IHibernateDataAccessBrokerServer {
	
	public function callObject($module_id, $service_id, $options = false);
}
?>
