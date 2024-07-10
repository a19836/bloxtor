<?php
include_once get_lib("org.phpframework.broker.client.local.LocalDataAccessBrokerClient");
include_once get_lib("org.phpframework.broker.client.IHibernateDataAccessBrokerClient");

class LocalHibernateDataAccessBrokerClient extends LocalDataAccessBrokerClient implements IHibernateDataAccessBrokerClient {
	
	public function callObject($module_id, $service_id, $options = false) {
		return $this->getBrokerServer()->callObject($module_id, $service_id, $options);
	}
}
?>
