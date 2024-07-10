<?php
include_once get_lib("org.phpframework.broker.client.rest.RESTBrokerClient");
include_once get_lib("org.phpframework.broker.client.IDBBrokerClient");

class RESTDBBrokerClient extends RESTBrokerClient implements IDBBrokerClient {
	
	public function getDBDriversName() {
		$settings = $this->settings;
		$settings["url"] .= "/" . __FUNCTION__;
		
		return $this->requestResponse($settings);
	}
	
	public function getFunction($function_name, $parameters = false, $options = false) {
		$settings = $this->settings;
		$settings["url"] .= "/" . __FUNCTION__ . "/$function_name";
		
		return $this->requestResponse($settings, array("parameters" => $parameters, "options" => $options));
	}
	
	public function getData($sql, $options = false) {
		$settings = $this->settings;
		$settings["url"] .= "/" . __FUNCTION__;
		
		return $this->requestResponse($settings, array("parameters" => $sql, "options" => $options));
	}
	
	public function setData($sql, $options = false) {
		$settings = $this->settings;
		$settings["url"] .= "/" . __FUNCTION__;
		
		return $this->requestResponse($settings, array("parameters" => $sql, "options" => $options));
	}
	
	public function getSQL($sql, $options = false) {
		$settings = $this->settings;
		$settings["url"] .= "/" . __FUNCTION__;
		
		return $this->requestResponse($settings, array("parameters" => $sql, "options" => $options));
	}
	
	public function setSQL($sql, $options = false) {
		$settings = $this->settings;
		$settings["url"] .= "/" . __FUNCTION__;
		
		return $this->requestResponse($settings, array("parameters" => $sql, "options" => $options));
	}
	
	public function getInsertedId($options = false) {
		$settings = $this->settings;
		$settings["url"] .= "/" . __FUNCTION__;
		
		return $this->requestResponse($settings, array("options" => $options));
	}
	
	public function insertObject($table_name, $attributes, $options = false) {
		$settings = $this->settings;
		$settings["url"] .= "/" . __FUNCTION__;
		
		return $this->requestResponse($settings, array("parameters" => array(
			"table_name" => $table_name, 
			"attributes" => $attributes, 
		), "options" => $options));
	}
	
	public function updateObject($table_name, $attributes, $conditions = false, $options = false) {
		$settings = $this->settings;
		$settings["url"] .= "/" . __FUNCTION__;
		
		return $this->requestResponse($settings, array("parameters" => array(
			"table_name" => $table_name, 
			"attributes" => $attributes, 
			"conditions" => $conditions,
		), "options" => $options));
	}
	
	public function deleteObject($table_name, $conditions = false, $options = false) {
		$settings = $this->settings;
		$settings["url"] .= "/" . __FUNCTION__;
		
		return $this->requestResponse($settings, array("parameters" => array(
			"table_name" => $table_name, 
			"conditions" => $conditions,
		), "options" => $options));
	}
	
	public function findObjects($table_name, $attributes = false, $conditions = false, $options = false) {
		$settings = $this->settings;
		$settings["url"] .= "/" . __FUNCTION__;
		
		return $this->requestResponse($settings, array("parameters" => array(
			"table_name" => $table_name, 
			"attributes" => $attributes, 
			"conditions" => $conditions,
		), "options" => $options));
	}
	
	public function countObjects($table_name, $conditions = false, $options = false) {
		$settings = $this->settings;
		$settings["url"] .= "/" . __FUNCTION__;
		
		return $this->requestResponse($settings, array("parameters" => array(
			"table_name" => $table_name, 
			"conditions" => $conditions,
		), "options" => $options));
	}
	
	public function findRelationshipObjects($table_name, $rel_elm, $parent_conditions = false, $options = false) {
		$settings = $this->settings;
		$settings["url"] .= "/" . __FUNCTION__;
		
		return $this->requestResponse($settings, array("parameters" => array(
			"table_name" => $table_name, 
			"rel_elm" => $rel_elm, 
			"parent_conditions" => $parent_conditions,
		), "options" => $options));
	}
	
	public function countRelationshipObjects($table_name, $rel_elm, $parent_conditions = false, $options = false) {
		$settings = $this->settings;
		$settings["url"] .= "/" . __FUNCTION__;
		
		return $this->requestResponse($settings, array("parameters" => array(
			"table_name" => $table_name,
			"rel_elm" => $rel_elm, 
			"parent_conditions" => $parent_conditions,
		), "options" => $options));
	}
	
	public function findObjectsColumnMax($table_name, $attribute_name, $options = false) {
		$settings = $this->settings;
		$settings["url"] .= "/" . __FUNCTION__;
		
		return $this->requestResponse($settings, array("parameters" => array(
			"table_name" => $table_name,
			"attribute_name" => $attribute_name,
		), "options" => $options));
	}
}
?>
