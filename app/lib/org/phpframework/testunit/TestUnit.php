<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 *
 * Original Bloxtor Repo: https://github.com/a19836/bloxtor
 *
 * YOU ARE NOT AUTHORIZED TO MODIFY OR REMOVE ANY PART OF THIS NOTICE!
 */

include_once get_lib("org.phpframework.testunit.ITestUnit");

abstract class TestUnit implements ITestUnit {
	protected $bean_objects = array();
	protected $layers_objs = array();
	protected $errors = array();
	
	public function setBeanObjects($bean_objects) {
		$this->bean_objects = $bean_objects;
		$this->parseBeanObjects($bean_objects);
	}
	
	public function addBeanObjects($bean_objects) {
		$this->bean_objects = array_merge($this->bean_objects, $bean_objects);
		$this->parseBeanObjects($bean_objects);
	}
	
	public function parseBeanObjects($bean_objects) {
		if ($bean_objects)
			foreach ($bean_objects as $bean_name => $obj) {
				if (is_a($obj, "ILayer")) {
					if (is_a($obj, "DBLayer")) {
						$name = substr($bean_name, - strlen("DBLayer")) == "DBLayer" ? substr($bean_name, 0, strlen($bean_name) - strlen("DBLayer")) : $bean_name;
						$name = self::getBrokerNameFromRawLabel($name);
						$this->setLayerObject("db_layers", $name, $obj);
					}
					else if (is_a($obj, "IbatisDataAccessLayer")) {
						$name = substr($bean_name, - strlen("IDALayer")) == "IDALayer" ? substr($bean_name, 0, strlen($bean_name) - strlen("IDALayer")) : $bean_name;
						$name = self::getBrokerNameFromRawLabel($name);
						$this->setLayerObject("ibatis_layers", $name, $obj);
					}
					else if (is_a($obj, "HibernateDataAccessLayer")) {
						$name = substr($bean_name, - strlen("HDALayer")) == "HDALayer" ? substr($bean_name, 0, strlen($bean_name) - strlen("HDALayer")) : $bean_name;
						$name = self::getBrokerNameFromRawLabel($name);
						$this->setLayerObject("hibernate_layers", $name, $obj);
					}
					else if (is_a($obj, "BusinessLogicLayer")) {
						$name = substr($bean_name, - strlen("BLLayer")) == "BLLayer" ? substr($bean_name, 0, strlen($bean_name) - strlen("BLLayer")) : $bean_name;
						$name = self::getBrokerNameFromRawLabel($name);
						$this->setLayerObject("business_logic_layers", $name, $obj);
					}
					else if (is_a($obj, "PresentationLayer")) {
						$name = substr($bean_name, - strlen("PLayer")) == "PLayer" ? substr($bean_name, 0, strlen($bean_name) - strlen("PLayer")) : $bean_name;
						$name = self::getBrokerNameFromRawLabel($name);
						$this->setLayerObject("presentation_layers", $name, $obj);
					}
				}
				else if (is_a($obj, "EVC")) {
					$layer_path = $obj->getPresentationLayer()->getLayerPathSetting();
					$name = substr($layer_path, strlen(LAYER_PATH));
					$name = substr($name, -1) == "/" ? substr($name, 0, -1) : $name;
					
					if ($name)
						$this->setLayerObject("presentation_layers_evc", $name, $obj);
				}
				else if (is_a($obj, "DB")) {
					$name = self::getBrokerNameFromRawLabel($bean_name);
					$this->setLayerObject("db_drivers", $name, $obj);
				}
			}
	}
	
	public function getLayersObjects() {
		return $this->layers_objs;
	}
	
	public function setLayerObject($type, $name, $obj) {
		$this->layers_objs[$type][$name] = $obj;
		
		if ($type == "ibatis_layers" || $type == "hibernate_layers")
			$this->layers_objs["data_access_layers"][$name] = $obj;
	}
	public function getLayerObject($type, $name = null) {
		$this->prepareObjectLayername($type, $name);
		return isset($this->layers_objs[$type][$name]) ? $this->layers_objs[$type][$name] : null;
	}
	
	public function getDBDriver($name = null) {
		$this->prepareObjectLayername("db_drivers", $name);
		return isset($this->layers_objs["db_drivers"][$name]) ? $this->layers_objs["db_drivers"][$name] : null;
	}
	
	public function getDBLayer($name = null) {
		$this->prepareObjectLayername("db_layers", $name);
		return isset($this->layers_objs["db_layers"][$name]) ? $this->layers_objs["db_layers"][$name] : null;
	}
	
	public function getDataAcessLayer($name = null) {
		$this->prepareObjectLayername("data_access_layers", $name);
		return isset($this->layers_objs["data_access_layers"][$name]) ? $this->layers_objs["data_access_layers"][$name] : null;
	}
	
	public function getIbatisLayer($name = null) {
		$this->prepareObjectLayername("ibatis_layers", $name);
		return isset($this->layers_objs["ibatis_layers"][$name]) ? $this->layers_objs["ibatis_layers"][$name] : null;
	}
	
	public function getHibernateLayer($name = null) {
		$this->prepareObjectLayername("hibernate_layers", $name);
		return isset($this->layers_objs["hibernate_layers"][$name]) ? $this->layers_objs["hibernate_layers"][$name] : null;
	}
	
	public function getBusinessLogicLayer($name = null) {
		$this->prepareObjectLayername("business_logic_layers", $name);
		return isset($this->layers_objs["business_logic_layers"][$name]) ? $this->layers_objs["business_logic_layers"][$name] : null;
	}
	
	public function getPresentationLayer($name = null) {
		$this->prepareObjectLayername("presentation_layers", $name);
		return isset($this->layers_objs["presentation_layers"][$name]) ? $this->layers_objs["presentation_layers"][$name] : null;
	}
	
	public function getPresentationLayerEVC($name = null) {
		$this->prepareObjectLayername("presentation_layers_evc", $name);
		return isset($this->layers_objs["presentation_layers_evc"][$name]) ? $this->layers_objs["presentation_layers_evc"][$name] : null;
	}
	
	private function prepareObjectLayername($type, &$name) {
		if (!$name && isset($this->layers_objs[$type]) && is_array($this->layers_objs[$type])) {
			$keys = array_keys($this->layers_objs[$type]);
			$name = count($keys) ? $keys[0] : null;
		}
	}
	
	public function addError($error) {
		$this->errors[] = $error;
	}
	public function setErrors($errors) {
		$this->errors = $errors;
	}
	public function getErrors() {
		return $this->errors;
	}
	
	private function getBrokerNameFromRawLabel($label) {
		if (!class_exists("WorkFlowBeansConverter"))
			launch_exception(new Exception("Class 'WorkFlowBeansConverter' must be loaded first!"));
		
		return WorkFlowBeansConverter::getBrokerNameFromRawLabel($label);
	}
}
?>
