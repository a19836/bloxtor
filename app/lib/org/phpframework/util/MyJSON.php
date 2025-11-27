<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 *
 * Original Bloxtor Repo: https://github.com/a19836/bloxtor
 *
 * YOU ARE NOT AUTHORIZED TO MODIFY OR REMOVE ANY PART OF THIS NOTICE!
 */

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
