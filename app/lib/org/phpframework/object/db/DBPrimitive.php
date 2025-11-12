<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

include_once get_lib("org.phpframework.object.ObjType");
include_once get_lib("org.phpframework.object.exception.ObjTypeException");
include_once get_lib("org.phpframework.db.DB");

class DBPrimitive extends ObjType {
	private $type;
	public $is_primitive = true;
	
	public function __construct($type, $data = false) {
		$this->setType($type);
		$this->field = false;
		
		if($data !== false)
			$this->setData($data);
	}

	public static function getTypes() {
		return DB::getAllColumnTypes();
	}
	public static function getNumericTypes() {
		return DB::getAllColumnNumericTypes();
	}
	public static function getDateTypes() {
		return DB::getAllColumnDateTypes();
	}
	public static function getTextTypes() {
		return DB::getAllColumnTextTypes();
	}
	public static function getBlobTypes() {
		return DB::getAllColumnBlobTypes();
	}
	public static function getBooleanTypeAvailableValues() {
		return array_keys( DB::getAllBooleanTypeAvailableValues() );
	}
	public static function getCurrentTimestampAvailableValues() {
		return DB::getAllCurrentTimestampAvailableValues();
	}
	
	public function getType() {return $this->type;}
	public function setType($type) {$this->type = strtolower($type);}
	
	/**
	 * Same types than DB:getDBColumnTypes();
	 * TODO: Optimize the validation for each type
	 */
	public function setData($data) {
		$ok = false;
		
		switch ($this->type) {
			case "smallserial":
			case "serial":
			case "bigserial":
				$ok = is_numeric($data) && $data > 0; //must be unsigned
				break;
			case "bit":
			case "tinyint":
			case "smallint":
			case "int":
			case "bigint":
			case "decimal":
			case "double":
			case "float":
			case "money":
			case "coordinate":
			case "time":
				$ok = is_numeric($data);
				break;
			case "boolean":
				$ok = $data === TRUE || $data === false || $data === 0 || $data === 1 || $data === "0" || $data === "1" || in_array($data, self::getBooleanTypeAvailableValues());
				
				if (!$ok) {
					$v = strtolower($data);
					$ok = $v === "true" || $v === "false";
				}
				break;
			case "char":
			case "varchar":
			case "mediumtext":
			case "text":
			case "longtext":
			case "blob":
			case "longblob":
				$ok = !is_object($data) && !is_array($data);
				break;
			case "date":
			case "datetime":
			case "timestamp":
				$ok = !is_object($data) && !is_array($data);
				break;
			case "varchar(36)"://UUID
			case "varchar(44)"://CIDR
			case "varchar(43)"://INET
			case "varchar(17)"://MAC Addr
				$ok = !is_object($data) && !is_array($data);
				break;
			
			default: $ok = true;
		}
		
		if($ok) {
			$this->data = $data;
			return true;
		}
		
		launch_exception(new ObjTypeException($this->type, $data));
		return false;
	}
}
?>
