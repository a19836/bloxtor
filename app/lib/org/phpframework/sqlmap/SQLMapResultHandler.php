<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

include_once get_lib("org.phpframework.sqlmap.exception.SQLMapResultException");
include_once get_lib("org.phpframework.object.ObjectHandler");

class SQLMapResultHandler extends SQLMap {
	public function __construct() {
		parent::__construct();
	}
	
	public function configureSortOptions(&$sorts, $result_map, $xml_data = false) {
		if (is_array($sorts)) {
			$o2i = $this->getResultMapOutputToInputAttributes($result_map, $xml_data);
			
			if ($o2i) {
				foreach ($sorts as $i => $sort) {
					$sort_column = isset($sort["column"]) ? $sort["column"] : null;
					
					if (!empty($o2i[$sort_column])) {
						$sorts[$i]["column"] = $o2i[$sort_column];
					}
				}
			}
		}
	}
	
	public function getResultMapOutputToInputAttributes($result_map, $xml_data = false) {
		$outputs_to_inputs = array();
		
		if($result_map) {
			if(!is_array($result_map)) {
				if(isset($xml_data["result_map"][$result_map])) {
					$result_map = $xml_data["result_map"][$result_map];
				}
			}
			
			if(is_array($result_map)) {
				$map_results = isset($result_map["result"]) ? $result_map["result"] : false;
				foreach ($map_results as $map_result) {
					$output_name = isset($map_result["output_name"]) ? trim($map_result["output_name"]) : "";
					$input_name = isset($map_result["input_name"]) ? trim($map_result["input_name"]) : "";
					
					if ($output_name && $input_name) {
						$outputs_to_inputs[$output_name] = $input_name;
					}
				}
			}
		}
		
		return $outputs_to_inputs;
	}
	
	public function transformData(&$data, $result_class = false, $result_map = false, $xml_data = false) {
		$db_types = isset($data["fields"]) ? $data["fields"] : array();
		$db_results = isset($data["result"]) ? $data["result"] : array();
	
		if(count($db_results)) {
			if($result_class) {
				$data = $this->transformDataToClass($result_class, $db_types, $db_results);
			}
			else if($result_map) {
				if(!is_array($result_map)) {
					if(isset($xml_data["result_map"][$result_map])) {
						$result_map = $xml_data["result_map"][$result_map];
					}
					else {
						launch_exception(new SQLMapResultException(4));
					}
				}
				$data = $this->transformDataToMap($result_map, $db_types, $db_results);
			}
			else {
				$data = $db_results;
			}
		}
		else {
			$data = $db_results;
		}
	}
	
	private function transformDataToClass($result_class, $db_types, $db_results) {
		$new_data = array();
		
		if($this->getErrorHandler()->ok() && $db_results) {
			$t = count($db_results);
			for($i = 0; $i < $t; $i++) {
				$db_result = $db_results[$i];
				$result_obj = ObjectHandler::createInstance($result_class);
				
				if(ObjectHandler::checkIfObjType($result_obj)) {
					$result_obj->setData($db_result);
				}
				$new_data[] = $result_obj;
			}
		}
		return $new_data;
	}
	
	private function transformDataToMap($result_map, $db_types, $db_results) {
		$new_data = array();
		
		$main_obj_class = isset($result_map["attrib"]["class"]) ? $result_map["attrib"]["class"] : false;
		$map_results = isset($result_map["result"]) ? $result_map["result"] : false;
		$columns_name = isset($db_results[0]) ? array_keys($db_results[0]) : array();
		
		if(!$map_results || !count($map_results)) {
			launch_exception(new SQLMapResultException(3));
		}
		else {
			$t = $db_results ? count($db_results) : 0;
			$t2 = count($map_results);
			
			for($i = 0; $i < $t; $i++) {
				$db_result = $db_results[$i];
				
				for($j = 0; $j < $t2; $j++) {
					$map_result = $map_results[$j];
					
					$output_name = isset($map_result["output_name"]) ? trim($map_result["output_name"]) : "";
					if(strlen($output_name) == 0) {
						launch_exception(new SQLMapResultException(2));
					}
					
					$input_name = isset($map_result["input_name"]) ? trim($map_result["input_name"]) : "";
					if(strlen($input_name) == 0) {
						launch_exception(new SQLMapResultException(1));
					}
					
					if(!isset($db_result[$input_name]) && !empty($map_result["mandatory"])) {
						launch_exception(new SQLMapResultException(6, $input_name));
					}
					
					$property_obj = $this->getPropertyObj($db_types, $db_result, $columns_name, $map_result);
					unset($db_result[$input_name]);
					$db_result[$output_name] = $property_obj;
				}
				
				if($main_obj_class) {
					$obj = ObjectHandler::createInstance($main_obj_class);
					if(ObjectHandler::checkIfObjType($obj)) {
						$obj->setData($db_result);
					}
					$new_data[] = $obj;
				}
				else {
					$new_data[] = $db_result;
				}
			}
		}
		return $new_data;
	}
	
	private function getPropertyObj($db_types, $db_result, $columns_name, $map_result) {
	/*echo "$db_types, $db_result, $columns_name, $map_result";
	print_r($db_types);
	print_r($db_result);
	print_r($columns_name);
	print_r($map_result);
	die();*/
		$input_name = isset($map_result["input_name"]) ? trim($map_result["input_name"]) : "";
		$input_value = isset($db_result[$input_name]) ? $db_result[$input_name] : null;
		$input_type = isset($map_result["input_type"]) ? $map_result["input_type"] : false;
		$input_obj = false;
		
		if($input_type) {
			$input_type = ObjTypeHandler::convertSimpleTypeIntoCompositeType($input_type);
			$input_obj = ObjectHandler::createInstance($input_type);
			if(ObjectHandler::checkIfObjType($input_obj) && $this->getErrorHandler()->ok()) {
				$continue = true;
				$input_type_class_name = ObjectHandler::getClassName($input_type);
				if(!$input_obj->is_primitive && ( !ObjectHandler::checkObjClass($input_value, $input_type_class_name) || !$this->getErrorHandler()->ok() ) ) {
					$continue = false;
				}
				
				if($continue) {
					$index = array_search($input_name, $columns_name);
					if(!is_numeric($index)) {
						launch_exception(new SQLMapResultException(5, array($input_name, $columns_name)));
					}
				
					$input_obj->setField( (is_numeric($index) && isset($db_types[$index]) ? $db_types[$index] : false) );
					$input_obj->setInstance($input_value);
				}
			}
		}
	
		$output_value = $input_obj ? $input_obj->getData() : $input_value;
		$output_type = isset($map_result["output_type"]) ? $map_result["output_type"] : false;
		$output_obj = false;

		if($output_type && $this->getErrorHandler()->ok()) {
			$output_type = ObjTypeHandler::convertSimpleTypeIntoCompositeType($output_type);
			$output_obj = ObjectHandler::createInstance($output_type);
			if(ObjectHandler::checkIfObjType($output_obj) && $this->getErrorHandler()->ok()) {
				$output_obj->setField($input_obj);
				$output_obj->setInstance($output_value);
			}
		}
		
		$value = false;
		if($this->getErrorHandler()->ok()) {
			if(!$output_obj && !is_numeric($output_obj))//in case of being 0
				$value = $output_value;
			elseif(isset($output_obj->is_primitive) && $output_obj->is_primitive)
				$value = $output_obj->getData();
			else 
				$value = $output_obj;
		}
		return $value;
	}
}
?>
