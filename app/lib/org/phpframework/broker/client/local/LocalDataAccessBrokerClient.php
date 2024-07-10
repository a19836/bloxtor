<?php
include_once get_lib("org.phpframework.broker.client.local.LocalBrokerClient");
include_once get_lib("org.phpframework.broker.client.IDataAccessBrokerClient");

class LocalDataAccessBrokerClient extends LocalBrokerClient implements IDataAccessBrokerClient {
	
	public function getBrokersDBDriversName() {
		return $this->getBrokerServer()->getBrokersDBDriversName();
	}
	
	public function getFunction($function_name, $parameters = false, $options = false) {
		return $this->getBrokerServer()->getFunction($function_name, $parameters, $options);
	}
	
	public function getData($sql, $options = false) {
		return $this->getBrokerServer()->getData($sql, $options);
	}
	
	public function setData($sql, $options = false) {
		return $this->getBrokerServer()->setData($sql, $options);
	}
	
	public function getSQL($sql, $options = false) {
		return $this->getBrokerServer()->getSQL($sql, $options);
	}
	
	public function setSQL($sql, $options = false) {
		return $this->getBrokerServer()->setSQL($sql, $options);
	}
	
	public function getInsertedId($options = false) {
		return $this->getBrokerServer()->getInsertedId($options);
	}
	
	public function insertObject($table_name, $attributes, $options = false) {
		return $this->getBrokerServer()->insertObject($table_name, $attributes, $options);
	}
	
	public function updateObject($table_name, $attributes, $conditions = false, $options = false) {
		return $this->getBrokerServer()->updateObject($table_name, $attributes, $conditions, $options);
	}
	
	public function deleteObject($table_name, $conditions = false, $options = false) {
		return $this->getBrokerServer()->deleteObject($table_name, $conditions, $options);
	}
	
	public function findObjects($table_name, $attributes = false, $conditions = false, $options = false) {
		return $this->getBrokerServer()->findObjects($table_name, $attributes, $conditions, $options);
	}
	
	public function countObjects($table_name, $conditions = false, $options = false) {
		return $this->getBrokerServer()->countObjects($table_name, $conditions, $options);
	}
	
	public function findRelationshipObjects($table_name, $rel_elm, $parent_conditions = false, $options = false) {
		return $this->getBrokerServer()->findRelationshipObjects($table_name, $rel_elm, $parent_conditions, $options);
	}
	
	public function countRelationshipObjects($table_name, $rel_elm, $parent_conditions = false, $options = false) {
		return $this->getBrokerServer()->countRelationshipObjects($table_name, $rel_elm, $parent_conditions, $options);
	}
	
	public function findObjectsColumnMax($table_name, $attribute_name, $options = false) {
		return $this->getBrokerServer()->findObjectsColumnMax($table_name, $attribute_name, $options);
	}
}
?>
