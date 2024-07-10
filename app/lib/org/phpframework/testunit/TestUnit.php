<?php
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
						$name = WorkFlowBeansConverter::getBrokerNameFromRawLabel($name);
						$this->setLayerObject("db_layers", $name, $obj);
					}
					else if (is_a($obj, "IbatisDataAccessLayer")) {
						$name = substr($bean_name, - strlen("IDALayer")) == "IDALayer" ? substr($bean_name, 0, strlen($bean_name) - strlen("IDALayer")) : $bean_name;
						$name = WorkFlowBeansConverter::getBrokerNameFromRawLabel($name);
						$this->setLayerObject("ibatis_layers", $name, $obj);
					}
					else if (is_a($obj, "HibernateDataAccessLayer")) {
						$name = substr($bean_name, - strlen("HDALayer")) == "HDALayer" ? substr($bean_name, 0, strlen($bean_name) - strlen("HDALayer")) : $bean_name;
						$name = WorkFlowBeansConverter::getBrokerNameFromRawLabel($name);
						$this->setLayerObject("hibernate_layers", $name, $obj);
					}
					else if (is_a($obj, "BusinessLogicLayer")) {
						$name = substr($bean_name, - strlen("BLLayer")) == "BLLayer" ? substr($bean_name, 0, strlen($bean_name) - strlen("BLLayer")) : $bean_name;
						$name = WorkFlowBeansConverter::getBrokerNameFromRawLabel($name);
						$this->setLayerObject("business_logic_layers", $name, $obj);
					}
					else if (is_a($obj, "PresentationLayer")) {
						$name = substr($bean_name, - strlen("PLayer")) == "PLayer" ? substr($bean_name, 0, strlen($bean_name) - strlen("PLayer")) : $bean_name;
						$name = WorkFlowBeansConverter::getBrokerNameFromRawLabel($name);
						$this->setLayerObject("presentation_layers", $name, $obj);
					}
				}
				else if (is_a($obj, "EVC")) {
					$presentation_bean_name = null;
					
					if ($bean->properties)
						foreach ($bean->properties as $property)
							if ($property->name == "presentationLayer") {
								$presentation_bean_name = $property->reference;
								break;
							}
					
					if ($presentation_bean_name) { //$property->reference can be null
						$name = substr($presentation_bean_name, - strlen("PLayer")) == "PLayer" ? substr($presentation_bean_name, 0, strlen($presentation_bean_name) - strlen("PLayer")) : $presentation_bean_name;
						$name = WorkFlowBeansConverter::getBrokerNameFromRawLabel($name);
						$this->setLayerObject("presentation_layers_evc", $name, $obj);
					}
				}
				else if (is_a($obj, "DB")) {
					$name = WorkFlowBeansConverter::getBrokerNameFromRawLabel($bean_name);
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
			$name = $keys[0];
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
}
?>
