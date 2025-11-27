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

include_once get_lib("org.phpframework.broker.BrokerServer");
include_once get_lib("org.phpframework.broker.server.IRESTBrokerServer");
include_once get_lib("org.phpframework.util.xml.XMLSerializer");
include_once get_lib("org.phpframework.encryption.CryptoKeyHandler");

abstract class RESTBrokerServer extends BrokerServer implements IRESTBrokerServer {
	protected $settings;
	protected $LocalBrokerServer;
	
	protected $url;
	protected $response_type;
	protected $parameters;
	protected $options;
	
	public function __construct(Layer $Layer, $settings = null) {
		parent::__construct($Layer);
		
		$this->settings = $settings ? $settings : array();
		
		$this->setLocalBrokerServer();
	}
	
	abstract protected function setLocalBrokerServer();
	abstract protected function executeWebServiceResponse();
	
	public function callWebService($url) {
		$status = $this->prepareWebServiceRequest($url, $verified);
		
		debug_log_function("[verified:$verified]: " . get_class($this) . "->callWebService", array($this->url, $this->parameters, $this->options, $this->response_type));
		
		if ($status)
			return $this->executeWebServiceResponse();
		
		if (!$verified)
			launch_exception(new Exception("Request NOT allowed with url: $url"));
		
		return null;
	}
	
	/*
	 * (mandatory) $url
	 * 
	 * (optional) $_POST = array(
	 * 	"data" => array(
	 * 		"parameters" => ...
	 * 		"options" => ...
	 * 	),
	 * 	"data" => cipher_text, 
	 * 	"response_type" => ...
	 * 	"no_cache" => ...
	 * );
	 *
	 * Note: global variables will be set in the LayerWebService->setUserGlobalVariables method. Do not set them here, bc they must be set before the beans get initialized, so needs to be done in the LayerWebService.php file.
	 */
	protected function prepareWebServiceRequest($url, &$verified = null) {
		//getting post data
		$post = !empty($_POST) ? $_POST : array();
		$data = array_key_exists("data", $post) ? $post["data"] : null;
		$response_type = array_key_exists("response_type", $post) ? $post["response_type"] : (isset($this->settings["response_type"]) ? $this->settings["response_type"] : null);
		$verified = true;
		
		//authenticate request
		$rest_auth_user = isset($this->settings["rest_auth_user"]) ? $this->settings["rest_auth_user"] : null;
		$rest_auth_pass = isset($this->settings["rest_auth_pass"]) ? $this->settings["rest_auth_pass"] : null;
		
		if ($rest_auth_user && $rest_auth_pass) {
			$post_rest_auth_user = isset($post["rest_auth_user"]) ? $post["rest_auth_user"] : null;
			$post_rest_auth_pass = isset($post["rest_auth_pass"]) ? $post["rest_auth_pass"] : null;
			
			$verified = password_verify($rest_auth_user, $post_rest_auth_user) && password_verify($rest_auth_pass, $post_rest_auth_pass);
			
			if (!$verified)
				return false;
		}
		
		//prepare data if applyied
		if (!empty($this->settings["request_encryption_key"]) && $data) {
			$key = CryptoKeyHandler::hexToBin($this->settings["request_encryption_key"]);
			$cipher_bin = CryptoKeyHandler::hexToBin($data);
			$data = CryptoKeyHandler::decryptSerializedObject($cipher_bin, $key);
		}
		
		$data = $data ? $data : array();
		
		//prepare parameters
		$parameters = is_array($data) && isset($data["parameters"]) ? $data["parameters"] : $_GET;
		
		if (is_array($parameters)) {
			//fix the cases where the parameters is simply an id (like 5). This happens bc when we call a ibatis query we can simply pass to the query a primitive parameter (like 5). In the url, this will be translated to something like: "http://jplpinto.localhost/__system/dataaccess/ibatis/TEST/select/select_item_simple/?5"
			$keys = array_keys($parameters);
			if (count($keys) == 1 && is_numeric($keys[0]) && $parameters[ $keys[0] ] == "")
				$parameters = $keys[0];
		}
		
		//prepare options
		$options = is_array($data) && isset($data["options"]) ? $data["options"] : null;
		$options = $options ? (is_array($options) ? $options : array($options)) : array();
		
		if (array_key_exists("no_cache", $post))
			$options["no_cache"] = $post["no_cache"];
		
		//error_log("\nServer Request:".get_class($this)."\nresponse_type:$response_type\nurl:$url\nparameters:".print_r($parameters, 1)."\noptions:".print_r($options, 1)."\n\n", 3, "/var/www/html/livingroop/default/tmp/test.log");
		
		//prepare private vars
		$this->url = $url;
		$this->response_type = $response_type;
		$this->parameters = $parameters;
		$this->options = $options;
		
		return true;
	}
	
	protected function getWebServiceResponse($method, $args, $result, $response_type) {
		$response = array(
			"method" => $method,
			"arguments" => $args,
			"result" => $result,
		);
		
		if (is_object($result)) {
			$reflector = new \ReflectionClass($result);
			$obj_path = $reflector->getFileName();
			
			if (!headers_sent())
				header("Response-Object-Lib: $obj_path");
		}
		
		if ($response_type == "xml") {
			header("Content-Type: text/xml; charset=UTF-8");
			$output = XMLSerializer::generateValidXmlFromVar($response, "response", "row");
		}
		else if ($response_type == "json") {
			header("Content-Type: application/json; charset=UTF-8");
			$output = json_encode($response);
		}
		else {
			header("Content-Type: text/php; charset=UTF-8");
			
			//if $content is a object class, we SHOULD NOT include first the file, bc this action is the user responsability. This is, the include of this file should be done in the client side by the user. The responsability of doing this is not from the borkers. If a user calls a service that will return object he should include first the correspondent file. So delete the current web-service code where we pass the file path in the headers and then include that file.
			$output = serialize($response);
		}
		
		//decrypt content
		if (!empty($this->settings["response_encryption_key"]) && $output) {
			$key = CryptoKeyHandler::hexToBin($this->settings["response_encryption_key"]);
			$cipher_bin = CryptoKeyHandler::encryptText($output, $key);
			$output = CryptoKeyHandler::binToHex($cipher_bin);
		}
		
		return $output;
	}
}
?>
