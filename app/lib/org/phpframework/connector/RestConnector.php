<?php
include_once get_lib("org.phpframework.util.web.MyCurl");
include_once get_lib("org.phpframework.util.xml.MyXML");

class RestConnector { 
	
	public static function connect($data, $result_type = null) {
		return MyCurl::getUrlContents($data, $result_type);
	}
}
?>
