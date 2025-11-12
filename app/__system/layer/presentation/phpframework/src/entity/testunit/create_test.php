<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "write");

$path = isset($_GET["path"]) ? $_GET["path"] : null;
$file_name = isset($_GET["file_name"]) ? ucfirst($_GET["file_name"]) : null;

$path = str_replace("../", "", $path);//for security reasons

$path = TEST_UNIT_PATH . $path;
$status = null;

if (file_exists($path) && $file_name) {
	$UserAuthenticationHandler->checkInnerFilePermissionAuthentication("vendor/testunit/$path", "layer", "access");
	
	$file_path = "$path/$file_name";
	$path_info = pathinfo($file_path);
	$file_path .= isset($path_info["extension"]) && $path_info["extension"] == "php" ? "" : ".php";
	
	$contents = getTestUnitClassContents($path_info["filename"]);
	
	if (!$contents)
		$file_path = "";
	else if (!PHPScriptHandler::isValidPHPContents($contents, $error_message)) { // in case the user creates a class with a name: "as" or any other php reserved word.
		echo $error_message ? $error_message : "Error creating test unit with name: $file_name";
		die();
	}
	
	$status = $file_path ? file_put_contents($file_path, $contents) !== false : false;
}

echo $status;
die();

function getTestUnitClassContents($class_name) {
	include_once get_lib("org.phpframework.testunit.TestUnit"); //include TestUnit file so all the dependents classes get included too and we can check if the class already exists
	
	if (class_exists($class_name))
		return false;
	
	return '<?php
include_once get_lib("org.phpframework.testunit.TestUnit");

class ' . $class_name . ' extends TestUnit {
	
	/**
	 * @enabled
	 */
	public function execute() {
		//TODO: add some code to create your test unit...
		
		/*
		 * You can call the following inner methods:
		 * - $this->getLayersObjects()
		 * - $this->getLayerObject($type, $name = null) $type: db_layers, data_access_layers, ibatis_layers, hibernate_layers, business_logic_layers, presentation_layers, presentation_layers_evc
		 * - $this->getDBLayer($name = null) $name is a string with the layer name
		 * - $this->getDataAcessLayer($name = null) $name is a string with the layer name
		 * - $this->getIbatisLayer($name = null) $name is a string with the layer name
		 * - $this->getHibernateLayer($name = null) $name is a string with the layer name
		 * - $this->getBusinessLogicLayer($name = null) $name is a string with the layer name
		 * - $this->getPresentationLayer($name = null) $name is a string with the layer name
		 * - $this->getPresentationLayerEVC($name = null) $name is a string with the layer name
		 * - $this->addError($error) $error is a string
		 * - $this->setErrors($errors) $errors is an array of strings
		 * - $this->getErrors()
		 */
		 
		 return true; //it must return something. If you would like to display something when this test gets executed, return a string with what you wish to display.
	}
}
?>';
}
?>
