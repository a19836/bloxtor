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

class XMLSerializer {

	public static function generateValidXmlFromObj(stdClass $obj, $node_block = 'nodes', $node_name = 'node') {
		$arr = get_object_vars($obj);

		return self::generateValidXmlFromVar($arr, $node_block, $node_name);
	}

	public static function generateValidXmlFromVar($var, $node_block = 'nodes', $node_name = 'node') {
		$xml = '<?xml version="1.0" encoding="UTF-8" ?>';

		$xml .= '<' . $node_block . '>';
		$xml .= self::generateXmlFromVar($var, $node_name);
		$xml .= '</' . $node_block . '>';

		return $xml;
	}

	public static function generateXmlFromVar($var, $node_name = 'node') {
		$xml = '';
		
		if (is_object($var)) {
			$class = get_class($var);
			$reflector = new ReflectionClass($class);
			$fp = $reflector->getFileName();
			
			$lib = $fp ? str_replace("/", ".", substr($fp, strlen(CMS_PATH), -4)) : ""; //remove .php
			
			$obj = urlencode(serialize($var)); //we need to call the urlencode method otherwise the serialized string can contain invalid xml chars, which will break the xml.
			
			$xml = "<" . $node_name . "_object><class><![CDATA[" . $class . "]]></class><lib><![CDATA[" . $lib . "]]></lib><code><![CDATA[" . $obj . "]]></code></" . $node_name . "_object>";
			
			//error_log("\nclass:".$class, 3, "/var/www/html/livingroop/default/tmp/test.log");
			//error_log("\nobj:".var_export($var, 1), 3, "/var/www/html/livingroop/default/tmp/test.log");
			//error_log("\nobj serialized:".var_export($obj, 1), 3, "/var/www/html/livingroop/default/tmp/test.log");
		}
		else if (is_array($var)) {
			foreach ($var as $key => $value) {
				if (is_numeric($key))
					$key = $node_name;

				$xml .= '<' . $key . '>' . self::generateXmlFromVar($value, $node_name) . '</' . $key . '>';
			}
		} 
		else {
			//$xml = htmlspecialchars($var, ENT_QUOTES);
			$xml = "<![CDATA[$var]]>";
		}

		return $xml;
	}

	public static function convertValidXmlToVar($xml, $node_block = 'nodes', $node_name = 'node') {
		if ($xml) {
			if (!MyXML::isXMLContentValid($xml)) {
				launch_exception(new Exception("Incorrect xml: <br>" . $xml));
				return null;
			}
			
			//error_log("\nxml:".$xml, 3, "/var/www/html/livingroop/default/tmp/test.log");
			$MyXML = new MyXML($xml);
			$var = $MyXML->toArray(array("simple" => 1));
			
			$var = MyXML::complexArrayToBasicArray($var);
			
			if (isset($var[$node_block])) {
				$var = $var[$node_block];
				//print_r($var);die();
				
				if (is_array($var))
					$var = self::convertVarArrayWithNumericIndexes($var, $node_name);
				
				//print_r($var);die();
				return $var;
			}
		}
		
		return null;
	}
	
	public static function convertVarArrayWithNumericIndexes($arr, $node_name = 'node') {
		if (is_array($arr))
			foreach ($arr as $k => $v) {
				if ($k == $node_name) { //reput the right array keys with numeric keys
					$is_array_with_numeric_keys = is_array($v) && $v && array_keys($v) === range(0, count($v) - 1);
					unset($arr[$k]);
					
					$v = self::convertVarArrayWithNumericIndexes($v, $node_name);
					
					if ($is_array_with_numeric_keys)
						$arr = array_merge($arr, $v);
					else
						$arr[0] = $v;
				}
				else if ($k == $node_name . "_object") { //unserialize object in xml
					//$class = $v["class"];
					$lib = isset($v["lib"]) ? $v["lib"] : null;
					$obj = isset($v["code"]) ? $v["code"] : null;
					
					$lib = $lib ? get_lib($lib) : "";
					if ($lib && file_exists($lib))
						include_once $lib;
					
					return unserialize(urldecode($obj)); //We must return the $node_name . "_object" so it be in the proper order in the parent object. Don't worry bc the $node_name . "_object" will be the only single node in the $arr!
				}
				else
					$arr[$k] = self::convertVarArrayWithNumericIndexes($v, $node_name);
			}
		
		return $arr;
	}
}
?>
