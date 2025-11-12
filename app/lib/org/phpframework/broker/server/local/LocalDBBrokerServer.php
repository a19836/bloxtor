<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

include_once get_lib("org.phpframework.broker.server.local.LocalBrokerServer");
include_once get_lib("org.phpframework.broker.server.IDBBrokerServer");

class LocalDBBrokerServer extends LocalBrokerServer implements IDBBrokerServer {
	
	public function getDBDriversName() {
		return $this->Layer->getDBDriversName();
	}
	
	public function getFunction($function_name, $parameters = false, $options = false) {
		return $this->Layer->getFunction($function_name, $parameters, $options);
	}
	
	public function getData($sql, $options = false) {
		return $this->Layer->getData($sql, $options);
	}
	
	public function setData($sql, $options = false) {
		return $this->Layer->setData($sql, $options);
	}
	
	public function getSQL($sql, $options = false) {
		return $this->Layer->getSQL($sql, $options);
	}
	
	public function setSQL($sql, $options = false) {
		return $this->Layer->setSQL($sql, $options);
	}
	
	public function getInsertedId($options = false) {
		return $this->Layer->getInsertedId($options);
	}
	
	public function insertObject($table_name, $attributes, $options = false) {
		return $this->Layer->insertObject($table_name, $attributes, $options);
	}
	
	public function updateObject($table_name, $attributes, $conditions = false, $options = false) {
		return $this->Layer->updateObject($table_name, $attributes, $conditions, $options);
	}
	
	public function deleteObject($table_name, $conditions = false, $options = false) {
		return $this->Layer->deleteObject($table_name, $conditions, $options);
	}
	
	public function findObjects($table_name, $attributes = false, $conditions = false, $options = false) {
		return $this->Layer->findObjects($table_name, $attributes, $conditions, $options);
	}
	
	public function countObjects($table_name, $conditions = false, $options = false) {
		return $this->Layer->countObjects($table_name, $conditions, $options);
	}
	
	public function findRelationshipObjects($table_name, $rel_elm, $parent_conditions = false, $options = false) {
		return $this->Layer->findRelationshipObjects($table_name, $rel_elm, $parent_conditions, $options);
	}
	
	public function countRelationshipObjects($table_name, $rel_elm, $parent_conditions = false, $options = false) {
		return $this->Layer->countRelationshipObjects($table_name, $rel_elm, $parent_conditions, $options);
	}
	
	public function findObjectsColumnMax($table_name, $attribute_name, $options = false) {
		return $this->Layer->findObjectsColumnMax($table_name, $attribute_name, $options);
	}
}
?>
