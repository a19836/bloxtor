<?php
include_once get_lib("org.phpframework.broker.server.local.LocalDataAccessBrokerServer");
include_once get_lib("org.phpframework.broker.server.IHibernateDataAccessBrokerServer");

class LocalHibernateDataAccessBrokerServer extends LocalDataAccessBrokerServer implements IHibernateDataAccessBrokerServer {
	
	public function callObject($module_id, $service_id, $options = false) {
		return $this->Layer->callObject($module_id, $service_id, $options);
	}
}
?>
