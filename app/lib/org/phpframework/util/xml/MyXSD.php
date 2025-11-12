<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

include get_lib("org.phpframework.util.xml.exception.MyXSDException");

class MyXSD {
	
	public static function validate($content, $xml_schema_file_path) {
		if(!file_exists($xml_schema_file_path)) {
			launch_exception(new MyXSDException(2, $xml_schema_file_path));
			return false;
		}
		
		if ($content) {
			// Enable user error handling
			$previous_use_internal_errors_value = libxml_use_internal_errors();
			libxml_use_internal_errors(true);

			$DOMDocument = new DOMDocument();
			$DOMDocument->loadXML($content);
			
			$errors = false;
			if(!$DOMDocument->schemaValidate($xml_schema_file_path)) {
				$errors = self::initErrors();
			}

			libxml_use_internal_errors($previous_use_internal_errors_value);
			
			return $errors === false ? true : $errors;
		}
		
		return false;
	}

	private static function initErrors() {
		$err = array();
		
		$errors = libxml_get_errors();
		foreach ($errors as $error) {
			$err[] = self::initError($error);
		}
		libxml_clear_errors();
		
		return $err;
	}
	
	private static function initError($error) {
		$return = "";
		switch ($error->level) {
			case LIBXML_ERR_WARNING:
				$return .= "<b>Warning $error->code</b>: ";
				break;
			case LIBXML_ERR_ERROR:
				$return .= "<b>Error $error->code</b>: ";
				break;
			case LIBXML_ERR_FATAL:
				$return .= "<b>Fatal Error $error->code</b>: ";
				break;
		}
		$return .= trim($error->message);
		/*if($error->file) {
			$return .= " in <b>$error->file</b>";
		}*/
		$return .= " on line <b>$error->line</b>\n";

		return $return;
	}
}
?>
