<?php
include_once get_lib("org.phpframework.util.xml.MyXML");

class MyJSON {
	
	public static function arrayToJSON($arr) {
		return json_encode($arr);
	}
	
	public static function jSONToArray($json) {
		return json_decode($json);
	}
	
	public static function xmlToJSON($xml) {
		$MyXML = new MyXML($xml);
		$arr = $MyXML->toArray();
		
		return self::arrayToJSON($arr);
	}
}
?>
