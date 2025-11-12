<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

include_once get_lib("org.phpframework.util.xml.MyXML");

class SoapConnector { 
	
	protected $SoapClient = null;
	
	public static function connect($data, $result_type = null) {
		$SoapConnector = new SoapConnector();
		return isset($data["type"]) && $data["type"] == "callSoapClient" ? $SoapConnector->callSoapClient($data) : $SoapConnector->callSoapFunction($data, $result_type);
	}
	
	public function callSoapFunction($data, $result_type = null) {
		$this->SoapClient = $this->callSoapClient($data);
		
		//Call remote function
		$content = null;
		$error = null;
		
		try {
			$remote_function_name = isset($data["remote_function_name"]) ? $data["remote_function_name"] : null;
			$remote_function_args = isset($data["remote_function_args"]) ? $data["remote_function_args"] : null;
			
			$content = $this->SoapClient->__call($remote_function_name, array($remote_function_args));
		}
		catch (SoapFault $fault) {
			$error = "Soap Server returned the following ERROR: " . $fault->faultcode . " - " . $fault->faultstring;
		}
		
		//prepare result
		$res = array(
			"settings" => $data,
			"content" => $content,
			"error" => $error,
		);
		
		$res = in_array($result_type, array("content", "content_json", "content_xml", "content_xml_simple", "content_serialized")) ? $res["content"] : (
			$result_type == "settings" ? (isset($content["settings"]) ? $content["settings"] : null) : $res
		);
		
		if ($res) {
			if ($result_type == "content_json")
				$res = json_decode($res, true);
			else if ($result_type == "content_xml") {
				$MyXML = new MyXML($res);
				$res = $MyXML->toArray();
				
				if ($result_type == "content_xml_simple")
					$res = MyXML::complexArrayToBasicArray($res, array("convert_attributes_to_childs" => true));
			}
			else if ($result_type == "content_serialized")
				$res = unserialize($res);
		}
		
		return $res;
	}
	
	public function callSoapClient($data) {
		if (!$this->SoapClient) {
			//Create Soap Client
			$soap_url = isset($data["wsdl_url"]) ? $data["wsdl_url"] : null;
			$soap_options = !empty($data["options"]) ? $data["options"] : array();
			$this->SoapClient = new SoapClient($soap_url, $soap_options);
			
			//Prepare Soap headers
			if (!empty($data["headers"])) {
				$headers = array();
				
				/* example: 
				$sh_param = array(
			          'Username'    =>    'username',
			          'Password'    =>    'password');
		 		$headers = new SoapHeader('http://soapserver.example.com/webservices', 'UserCredentials', $sh_param);
				*/
				foreach ($data["headers"] as $header)
					$headers[] = new SoapHeader(
						isset($header["namespace"]) ? $header["namespace"] : null,
						isset($header["name"]) ? $header["name"] : null,
						isset($header["parameters"]) ? $header["parameters"] : null,
						isset($header["must_understand"]) ? $header["must_understand"] : null,
						isset($header["actor"]) ? $header["actor"] : null
					);
				
				if ($headers)
					$this->SoapClient->__setSoapHeaders($headers);
			}
		}
		
		return $this->SoapClient;
	}
}
?>
