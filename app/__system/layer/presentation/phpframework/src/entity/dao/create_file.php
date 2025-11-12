<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "write");

$type = isset($_GET["type"]) ? $_GET["type"] : null;
$path = isset($_GET["path"]) ? $_GET["path"] : null;
$file_name = isset($_GET["file_name"]) ? ucfirst($_GET["file_name"]) : null;

$path = str_replace("../", "", $path);//for security reasons

$path = DAO_PATH . $path;

if (file_exists($path) && $file_name) {
	$UserAuthenticationHandler->checkInnerFilePermissionAuthentication("vendor/dao/$path", "layer", "access");
	
	$file_path = "$path/$file_name";
	$path_info = pathinfo($file_path);
	
	$contents = "";
	
	if ($type == "hibernatemodel" || $type == "objtype") {
		$file_path .= isset($path_info["extension"]) && $path_info["extension"] == "php" ? "" : ".php";
		
		$contents = $type == "hibernatemodel" ? getHibernateModelClassContents($path_info["filename"]) : getObjTypeClassContents($path_info["filename"]);
		
		if (!$contents)
			$file_path = "";
		else if (!PHPScriptHandler::isValidPHPContents($contents, $error_message)) { // in cae the user creates a class with a name: "as" or any other php reserved word.
			echo !empty($error_message) ? $error_message : "Error creating $type with name: $file_name";
			die();
		}
	}
	
	$status = $file_path ? file_put_contents($file_path, $contents) !== false : false;
}

echo isset($status) ? $status : null;
die();

function getHibernateModelClassContents($class_name) {
	include_once get_lib("org.phpframework.sqlmap.hibernate.HibernateModel"); //include HibernateModel file so all the dependents classes get included too and we can check if the class already exists
	
	if (class_exists($class_name))
		return false;
	
	return '<?php
include_once get_lib("org.phpframework.sqlmap.hibernate.HibernateModel");

class ' . $class_name . ' extends HibernateModel {
	//TODO: overwrite some methods from HibernateModel or create new ones...
}
?>';
}

function getObjTypeClassContents($class_name) {
	include_once get_lib("org.phpframework.object.ObjType"); //include ObjType file so all the dependents classes get included too and we can check if the class already exists
	
	if (class_exists($class_name))
		return false;
	
	return '<?php
include_once get_lib("org.phpframework.object.ObjType");

/*
 * For more information about how to create ObjType classes, please read our documentation in the "Our Parameter and Result Maps" and "Our Parameter and Result Classes" sections.
 */
class ' . $class_name . ' extends ObjType {
	//TODO: create some properties if you wish
	//sample: private $status;
	
	public function __construct() {
		//TODO: init some properties or do some other complex logic
		//sample: $this->status = false;
	}
	
	/*
	 * The following code should only be used if this class should be used as a type in the Business-Logic Annotations or as a type in an attribute of a Parameter Map or Result Map in the Data-Access Layers. Additionally, it must return true or false according if the $data is valid or not.
	 * Otherwise if this class is to be used as a Result Class in the Data-Access Layers, please use the uncommented code.
	public function setData($value) {
		$s = parent::setData($value);
		
		if ($s) {
			//write some code here to validate your type...
		}
		
		if ($s)
			return true; //return true if valid. Additionally you can launch an exception if not valid too.
		else {
			//launch_exception(new ObjTypeException(get_class($this), $value)); //launch exception if not valid. This is optional...
			return false; //return false if invalid.
		}
	}*/
	public function setData($data) {
		parent::setData($data);
		
		//TODO: change the $this->data value or assign the $this->data\'s values to some properties
		//sample: $this->status = isset($this->data["status"]) ? $this->data["status"] : null;
	}
	
	//to be called if this class is to be used as a Parameter Class in the Data-Access Layers...
	public function getData() {
		//TODO: change the $this->data values or the properties values
		//sample: $this->data["status"] = $this->status;
		
		return parent::getData();
	}
	
	//TODO: create some getters and setters if this class is to be used as a Parameter or Result Class in the Data-Access Layer.
	//sample: public function setStatus($status) {$this->status = $status;}
	//sample: public function getStatus() {return $this->status;}
}
?>';
}
?>
