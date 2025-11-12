<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

include_once get_lib("org.phpframework.object.exception.ObjException");

class ObjectHandler {
	public function __construct() {
		//parent::__construct();
	}
	
	public static function getClassName($class_lib) {
		$explode = explode(".", $class_lib);
		$class = $explode[count($explode) - 1];
		$class = explode("(", $class);
		$class_name = $class[0];
		
		return $class_name;
	}
	
	public static function createInstance($class_lib) {
		$explode = explode(".", $class_lib);
		$class = $explode[count($explode) - 1];

		$primitive = strpos($class_lib, "(") > 0 ? true : false;

		$obj = false;
		if ($primitive) {
			$class = explode("(", $class);
			$class_name = $class[0];
			$class = explode(")", isset($class[1]) ? $class[1] : null);
			$class = str_replace(array("'", '"'), "", $class[0]);
			
			array_pop($explode);
			$obj_path = get_lib( implode(".", $explode) . "." . $class_name);
			$obj_class_code = "\$obj = new {$class_name}('$class');";
		}
		else {
			$obj_path = get_lib($class_lib);
			$class_name = $class;
			$obj_class_code = "\$obj = new {$class_name}();";
		}
		
		if (!class_exists($class_name)) {
			if (file_exists($obj_path))
				include_once $obj_path;
			else
				launch_exception(new ObjException(1, array($obj_path)));
		}
		
		if (class_exists($class_name))
			eval($obj_class_code);
		else
			launch_exception(new ObjException(2, array($obj_class_code)));
		
		if (!$obj)
			launch_exception(new ObjException(2, array($obj_class_code)));
		
		return $obj;
	}
	
	public static function checkObjClass($obj, $parent_class_name) {
		if ($obj && !is_numeric($obj)) {
			$status = false;
			
			if (is_object($obj))
				$status = is_a($obj, $parent_class_name);
			elseif ($obj == $parent_class_name || is_subclass_of($obj, $parent_class_name))
				$status = true;
			
			if ($status)
				return true;
		}
		
		$obj_class_name = is_object($obj) ? get_class($obj) : $obj;
		launch_exception(new ObjException(3, array($obj_class_name, $parent_class_name)));
		
		return false;
	}
	
	public static function checkIfObjType($obj) {
		return self::checkObjClass($obj, "ObjType");
	}
	
	public static function arrayToObject($array, $className) {
		return unserialize(sprintf(
			'O:%d:"%s"%s',
			strlen($className),
			$className,
			strstr(serialize($array), ':')
		));
	}
	
	public static function objectToObject($instance, $className) {
		return unserialize(sprintf(
			'O:%d:"%s"%s',
			strlen($className),
			$className,
			strstr(strstr(serialize($instance), '"'), ':')
		));
	}
}
?>
