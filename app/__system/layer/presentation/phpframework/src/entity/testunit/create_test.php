<?php
$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "write");

$path = $_GET["path"];
$file_name = ucfirst($_GET["file_name"]);

$path = str_replace("../", "", $path);//for security reasons

$path = TEST_UNIT_PATH . $path;

if (file_exists($path) && $file_name) {
	$UserAuthenticationHandler->checkInnerFilePermissionAuthentication("vendor/testunit/$path", "layer", "access");
	
	$file_path = "$path/$file_name";
	$path_info = pathinfo($file_path);
	$file_path .= $path_info["extension"] == "php" ? "" : ".php";
	
	$contents = getTestUnitClassContents($path_info["filename"]);
	
	if (!$contents)
		$file_path = "";
	else if (!PHPScriptHandler::isValidPHPContents($contents, $error_message)) { // in case the user creates a class with a name: "as" or any other php reserved word.
		echo $error_message ? $error_message : "Error creating $type with name: $file_name";
		die();
	}
	
	$status = $file_path ? file_put_contents($file_path, $contents) !== false : false;
}

die($status);

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
