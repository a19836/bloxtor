<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

include_once get_lib("org.phpframework.object.db.DBPrimitive");
include_once get_lib("org.phpframework.object.php.Primitive");

class ObjTypeHandler {
	
	public static $attribute_names_as_created_date = array("created_date", "created_at");
	public static $attribute_names_as_modified_date = array("modified_date", "modified_at");
	public static $attribute_names_as_created_user_id = array("created_user_id", "created_by");
	public static $attribute_names_as_modified_user_id = array("modified_user_id", "modified_by");
	public static $attribute_names_as_title = array(
		"name", "nome", "nombre", "nom",
		"title", "titulo", "título", "titre",
		"label", "etiqueta", "etiquette", "étiquette"
	);
	
	public static function getPHPTypesPaths() {
		$types = array();
		
		$primitive_types = Primitive::getTypes();
		foreach ($primitive_types as $type_id => $type_title)
			$types[ "org.phpframework.object.php.Primitive($type_id)" ] = $type_title;
		
		$files = array_diff(scandir(__DIR__ . "/php/"), array('.', '..', 'Primitive.php'));
		
		foreach ($files as $file) {
			$file_name = pathinfo($file, PATHINFO_FILENAME);
			$types[ "org.phpframework.object.php.$file_name" ] = $file_name;
		} 
		
		return $types;
	}
	
	public static function getPHPTypes() {
		$types = Primitive::getTypes();
		
		$files = array_diff(scandir(__DIR__ . "/php/"), array('.', '..', 'Primitive.php'));
		foreach ($files as $file) {
			$file_name = pathinfo($file, PATHINFO_FILENAME);
			$types[ $file_name ] = $file_name;
		} 
		
		return $types;
	}
	
	public static function getPHPNumericTypes() {
		return Primitive::getNumericTypes();
	}
	
	public static function getDBTypesPaths() {
		$types = array();
		
		$db_types = DBPrimitive::getTypes();
		foreach ($db_types as $type_id => $type_title)
			$types[ "org.phpframework.object.db.DBPrimitive($type_id)" ] = $type_title;
		
		return $types;
	}
	
	public static function getDBTypes() {
		return DBPrimitive::getTypes();
	}
	
	public static function getDBNumericTypes() {
		return DBPrimitive::getNumericTypes();
	}
	
	public static function getDBDateTypes() {
		return DBPrimitive::getDateTypes();
	}
	
	public static function getDBTextTypes() {
		return DBPrimitive::getTextTypes();
	}
	
	public static function getDBBlobTypes() {
		return DBPrimitive::getBlobTypes();
	}
	
	public static function getDBBooleanTypeAvailableValues() {
		return DBPrimitive::getBooleanTypeAvailableValues();
	}
	
	public static function getDBAttributeNameTitleAvailableValues() {
		return self::$attribute_names_as_title;
	}
	
	public static function getDBAttributeNameCreatedDateAvailableValues() {
		return self::$attribute_names_as_created_date;
	}
	
	public static function getDBAttributeNameModifiedDateAvailableValues() {
		return array_merge(self::$attribute_names_as_created_date, self::$attribute_names_as_modified_date);
	}
	
	public static function getDBAttributeNameCreatedUserIdAvailableValues() {
		return self::$attribute_names_as_created_user_id;
	}
	
	public static function getDBAttributeNameModifiedUserIdAvailableValues() {
		return array_merge(self::$attribute_names_as_created_user_id, self::$attribute_names_as_modified_user_id);
	}
	
	public static function getDBCurrentTimestampAvailableValues() {
		return DBPrimitive::getCurrentTimestampAvailableValues();
	}
	
	public static function convertSimpleTypeIntoCompositeType($type, $primitive_type = "org.phpframework.object.php.Primitive") {
		$type = trim($type);
		
		if ($type) {
			if ($type == "MyString" || $type == "Integer" || $type == "Double" || $type == "MyFloat" || $type == "ArrayList" || $type == "HashMap" || $type == "MyObj") {//Class names from the files inside of the ./php/ and ./db/ folders. The Primitive classes should NOT be here!
				return $type;
			}
			else if (preg_match("/^([\w\-\+\ ]+)$/iu", $type)) { //'\w' means all words with '_' and '/u' means with accents and ç too.
				$type = "$primitive_type($type)";
			}
		}
		return $type;
	}
	
	public static function convertCompositeTypeIntoSimpleType($type) {
		$type = trim($type);
		
		if ($type) {
			if (strpos($type, "(") !== false) {
				preg_match_all('/\((.+)\)/u', $type, $matches, PREG_PATTERN_ORDER); //'/u' means with accents and ç too.
				
				if (!empty($matches[0]))
					$type = $matches[1][0];
			}
			else if (($pos = strrpos($type, ".")) !== false)
				$type = substr($type, $pos + 1);
		}
		
		return $type;
	}
	
	/**
	 * No need for conversion because all the DB types are in the PHP Types.
	 * Attention: Because NOT all the DB types are in the PHP types. PHP types have all DB types, but the inverse is NOT true.
	 * @Deprecated but is still being used in the WorkFLowDataAccessHandler.php
	 */
	public static function convertDBToPHPType($type) {
		return $type;
	}
	
	public static function isPHPTypeNumeric($type) {
		return $type && self::isType($type, self::getPHPNumericTypes());
	}
	
	public static function isDBTypeNumeric($type) {
		return $type && self::isType($type, self::getDBNumericTypes());
	}
	
	public static function isDBTypeDate($type) {
		return $type && self::isType($type, self::getDBDateTypes());
	}
	
	public static function isDBTypeText($type) {
		return $type && self::isType($type, self::getDBTextTypes());
	}
	
	public static function isDBTypeBlob($type) {
		return $type && self::isType($type, self::getDBBlobTypes());
	}
	
	public static function isDBTypeBoolean($type) {
		return $type && self::isType($type, self::getDBBooleanTypeAvailableValues());
	}
	
	private static function isType($type, $types) {
		$type = self::convertCompositeTypeIntoSimpleType($type);
		
		return in_array($type, $types);
	}
	
	public static function isDBAttributeNameATitle($name) {
		return $name && in_array($name, self::getDBAttributeNameTitleAvailableValues());
	}
	
	public static function isDBAttributeNameACreatedDate($name) {
		return $name && in_array($name, self::getDBAttributeNameCreatedDateAvailableValues());
	}
	
	public static function isDBAttributeNameAModifiedDate($name) {
		return $name && in_array($name, self::getDBAttributeNameModifiedDateAvailableValues());
	}
	
	public static function isDBAttributeValueACurrentTimestamp($value) {
		if ($value) {
			$values = self::getDBCurrentTimestampAvailableValues();
			
			if (in_array($value, $values))
				return true;
			
			$lv = strtolower($value);
			
			for ($i = 0, $t = count($values); $i < $t; $i++)
				if ($lv == strtolower($values[$i]))
					return true;
		}
		
		return false;
	}
	
	public static function isDBAttributeNameACreatedUserId($name) {
		return $name && in_array($name, self::getDBAttributeNameCreatedUserIdAvailableValues());
	}
	
	public static function isDBAttributeNameAModifiedUserId($name) {
		return $name && in_array($name, self::getDBAttributeNameModifiedUserIdAvailableValues());
	}
}
?>
