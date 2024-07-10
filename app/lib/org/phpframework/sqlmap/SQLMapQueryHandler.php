<?php
include_once get_lib("org.phpframework.sqlmap.exception.SQLMapQueryException");
include_once get_lib("org.phpframework.object.ObjectHandler");
include_once get_lib("org.phpframework.util.HashTagParameter");

class SQLMapQueryHandler extends SQLMap {
	
	public function __construct() {
		parent::__construct();
	}
	
	public function configureQuery(&$sql, $parameters = false, $auto_add_slashes = true) {
		if($parameters !== false) {
			$matches = self::getSQLParameters($sql);
			
			if(is_array($parameters)) {
				$is_associative = array_keys($parameters) !== range(0, count($parameters) - 1);
				
				if($is_associative) {
					self::replaceSQLParameterWithHashMap($sql, $matches, $parameters, $auto_add_slashes);
				}
				else {
					self::replaceSQLParameterWithArrayList($sql, $matches, $parameters, $auto_add_slashes);
				}
			}
			else {
				self::replaceSQLParameterWithValue($sql, $matches, $parameters, $auto_add_slashes);
			}
		}
	}
	
	//if $allow_multiple_values_for_parameter_item is true it means is a condition
	public function transformData(&$parameters, $parameter_class = false, $parameter_map = false, $xml_data = false, $allow_multiple_values_for_parameter_item = false) {
		if($parameter_class) {
			$parameters = $this->transformClassToData($parameter_class, $parameters);
		}
		elseif($parameter_map) {
			if(!is_array($parameter_map)) {
				if(isset($xml_data["parameter_map"][$parameter_map])) {
					$parameter_map = $xml_data["parameter_map"][$parameter_map];
				}
				else {
					launch_exception(new SQLMapQueryException(4));
				}
			}
			$parameters = $this->transformMapToData($parameter_map, $parameters, $allow_multiple_values_for_parameter_item);
		}
	}
	
	private static function getSQLParameters($sql) {
		preg_match_all(HashTagParameter::SQL_HASH_TAG_PARAMETER_FULL_REGEX, $sql, $out); //'\w' means all words with '_' and '/u' means with accents and ç too.
		//echo "<pre>";print_r($out);die();
		return $out[0];
	}
	
	private static function replaceSQLParameterWithArrayList(&$sql, $matches, $parameters, $auto_add_slashes = true) {
		$t = count($matches);
		for($i = 0; $i < $t; $i++) {
			$var_name = str_replace("#", "", $matches[$i]);
			$value = $parameters[$i];
			
			if($auto_add_slashes) {
				$value = (is_numeric($value) || is_bool($value)) && !is_string($value) ? $value : self::addSlashesToValue($value);
			}
			$sql = str_replace($matches[$i], $value, $sql);
		}
	}
	
	private static function replaceSQLParameterWithHashMap(&$sql, $matches, $parameters, $auto_add_slashes = true) {
		$t = count($matches);
		for($i = 0; $i < $t; $i++) {
			$var_name = str_replace("#", "", $matches[$i]);
			$value = $parameters[$var_name];
			
			if($auto_add_slashes) {
				$value = (is_numeric($value) || is_bool($value)) && !is_string($value) ? $value : self::addSlashesToValue($value);
			}
			$sql = str_replace($matches[$i], $value, $sql);
		}
	}
	
	private static function replaceSQLParameterWithValue(&$sql, $matches, $value, $auto_add_slashes = true) {
		$t = count($matches);
		for($i = 0; $i < $t; $i++) {
			if($auto_add_slashes) {
				$value = (is_numeric($value) || is_bool($value)) && !is_string($value) ? $value : self::addSlashesToValue($value);
			}
			$sql = str_replace($matches[$i], $value, $sql);
		}
	}
	
	private static function addSlashesToValue($value) {
		return addcslashes($value, "\\'");
	}
	
	private function transformClassToData($parameter_class, $parameters) {
		$new_parameters = array();
		
		$parameter_class_name = ObjectHandler::getClassName($parameter_class);
		if(ObjectHandler::checkObjClass($parameters, $parameter_class_name) && ObjectHandler::checkIfObjType($parameter_class_name) && $this->getErrorHandler()->ok()) {
			$new_parameters = $parameters->getData();
		}
		return $new_parameters;
	}
	
	//if $allow_multiple_values_for_parameter_item is true it means is a condition
	private function transformMapToData($parameter_map, $parameters, $allow_multiple_values_for_parameter_item = false) {
		$new_data = array();
		
		$map_results = isset($parameter_map["parameter"]) ? $parameter_map["parameter"] : false;
		$main_obj_class = isset($parameter_map["attrib"]["class"]) ? $parameter_map["attrib"]["class"] : false;
		
		$new_parameters = $parameters;
		if ($main_obj_class) {
			$main_obj_class_name = ObjectHandler::getClassName($main_obj_class);
			if(ObjectHandler::checkObjClass($parameters, $main_obj_class_name) && ObjectHandler::checkIfObjType($main_obj_class_name) && $this->getErrorHandler()->ok()) 
				$new_parameters = $parameters->getData();
		}
		
		if (!$map_results || !count($map_results))
			launch_exception(new SQLMapQueryException(3));
		elseif (!is_array($new_parameters))
			launch_exception(new SQLMapQueryException(7));
		else {
			$t = count($map_results);
			for ($j = 0; $j < $t; $j++) {
				$map_result = $map_results[$j];
					
				$output_name = isset($map_result["output_name"]) ? trim($map_result["output_name"]) : "";
				if (strlen($output_name) == 0)
					launch_exception(new SQLMapQueryException(2));
				
				$input_name = isset($map_result["input_name"]) ? trim($map_result["input_name"]) : "";
				if (strlen($input_name) == 0)
					launch_exception(new SQLMapQueryException(1));
				
				if (!isset($new_parameters[$input_name])) {
					if (!empty($map_result["mandatory"]) && !$allow_multiple_values_for_parameter_item)
						launch_exception(new SQLMapResultException(8, $input_name));
					//else DO NOTHING
				}
				else {
					if ($allow_multiple_values_for_parameter_item && isset($new_parameters[$input_name]) && is_array($new_parameters[$input_name])) {
						$property_obj = $new_parameters[$input_name];
						
						if ($property_obj) {
							$t = count($property_obj);
							for ($i = 0; $i < $t; $i++)
								$property_obj[$i] = $this->getPropertyObj(array($input_name => $property_obj[$i]), $map_result);
						}
					}
					else
						$property_obj = $this->getPropertyObj($new_parameters, $map_result);
					
					unset($new_parameters[$input_name]);
					$new_parameters[$output_name] = $property_obj;
				}
			}
			$new_data = $new_parameters;
		}
		return $new_data;
	}
	
	private function getPropertyObj($parameters, $map_result) {
		$input_name = isset($map_result["input_name"]) ? trim($map_result["input_name"]) : "";
		$input_value = isset($parameters[$input_name]) ? $parameters[$input_name] : null;
		$input_type = isset($map_result["input_type"]) ? $map_result["input_type"] : false;
		$input_obj = false;
		
		if ($input_type) {
			$input_type = ObjTypeHandler::convertSimpleTypeIntoCompositeType($input_type);
			$input_obj = ObjectHandler::createInstance($input_type);
			
			if (ObjectHandler::checkIfObjType($input_obj) && $this->getErrorHandler()->ok()) {
				$continue = true;
				$input_type_class_name = ObjectHandler::getClassName($input_type);
				if(!$input_obj->is_primitive && ( !ObjectHandler::checkObjClass($input_value, $input_type_class_name) || !$this->getErrorHandler()->ok() ) ) {
					$continue = false;
				}
			
				if($continue) {
					$input_obj->setField(false);
					$input_obj->setInstance($input_value);
				}
			}
		}
	
		$output_value = $input_obj ? $input_obj->getData() : $input_value;
		$output_type = isset($map_result["output_type"]) ? $map_result["output_type"] : false;
		$output_obj = false;

		if ($output_type && $this->getErrorHandler()->ok()) {
			$output_type = ObjTypeHandler::convertSimpleTypeIntoCompositeType($output_type);
			$output_obj = ObjectHandler::createInstance($output_type);
			
			if (ObjectHandler::checkIfObjType($output_obj) && $this->getErrorHandler()->ok()) {
				$output_obj->setField($input_obj);
				$output_obj->setInstance($output_value);
			}
		}
		
		$value = false;
		if ($this->getErrorHandler()->ok()) {
			if (!$output_obj && !is_numeric($output_obj))//in case of being 0
				$value = $output_value;
			elseif (isset($output_obj->is_primitive) && $output_obj->is_primitive)
				$value = $output_obj->getData();
			else 
				$value = $output_obj;
		}
		return $value;
	}
}
?>
