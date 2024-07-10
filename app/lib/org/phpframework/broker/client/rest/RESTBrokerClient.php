<?php
include_once get_lib("org.phpframework.broker.BrokerClient");
include_once get_lib("org.phpframework.util.web.MyCurl");
include_once get_lib("org.phpframework.util.xml.XMLSerializer");
include_once get_lib("org.phpframework.encryption.CryptoKeyHandler");

abstract class RESTBrokerClient extends BrokerClient {
	protected $settings; //request settings
	protected $global_variables_name;
	
	public function __construct($settings = null, $global_variables_name = null) {
		parent::__construct();
		
		$this->settings = $settings ? $settings : array();
		$this->global_variables_name = $global_variables_name;
	}
	
	/*
	 * (mandatory) $settings["url"] or $this->settings["url"]
	 */
	protected function requestResponse($settings, $data = null) {
		$url = isset($settings["url"]) ? $settings["url"] : $this->settings["url"];
		
		if ($url) {
			//prepare request settings
			$response_type = isset($settings["response_type"]) ? $settings["response_type"] : $this->settings["response_type"];
			$request_encryption_key = isset($this->settings["request_encryption_key"]) ? $this->settings["request_encryption_key"] : null;
			$response_encryption_key = isset($this->settings["response_encryption_key"]) ? $this->settings["response_encryption_key"] : null;
			$rest_auth_user = isset($this->settings["rest_auth_user"]) ? $this->settings["rest_auth_user"] : null;
			$rest_auth_pass = isset($this->settings["rest_auth_pass"]) ? $this->settings["rest_auth_pass"] : null;
			
			unset($settings["url"]);
			unset($settings["response_type"]);
			unset($settings["rest_auth_user"]);
			unset($settings["rest_auth_pass"]);
			unset($settings["request_encryption_key"]);
			unset($settings["response_encryption_key"]);
			
			if (!isset($settings["follow_location"]))
				$settings["follow_location"] = 1;
			
			if (!isset($settings["referer"]))
				$settings["referer"] = isset($_SERVER["HTTP_REFERER"]) ? $_SERVER["HTTP_REFERER"] : null;
			
			$settings["header"] = true; //must be always true bc of the serialization of responses that are Class objects. See bellow the logic code.
			
			//prepare post data
			$post_data = array(
				"response_type" => $response_type,
			);
			
			if ($data) {
				//encrypt data
				if ($request_encryption_key) {
					$key = CryptoKeyHandler::hexToBin($request_encryption_key);
					$cipher_bin = CryptoKeyHandler::encryptSerializedObject($data, $key);
					$data = CryptoKeyHandler::binToHex($cipher_bin);
				}
				
				$post_data["data"] = $data;
			}
			
			if ($rest_auth_user && $rest_auth_pass) {
				$post_data["rest_auth_user"] = password_hash($rest_auth_user, PASSWORD_DEFAULT);
				$post_data["rest_auth_pass"] = password_hash($rest_auth_pass, PASSWORD_DEFAULT);
			}
			
			if ($this->global_variables_name) {
				$global_variables = array();
				
				foreach ($this->global_variables_name as $var_name)
					$global_variables[$var_name] = $GLOBALS[$var_name];
				
				//encrypt global_variables
				if ($request_encryption_key && $global_variables) {
					$key = $key ? $key : CryptoKeyHandler::hexToBin($request_encryption_key);
					$cipher_bin = CryptoKeyHandler::encryptSerializedObject($global_variables, $key);
					$global_variables = CryptoKeyHandler::binToHex($cipher_bin);
				}
				
				$post_data["gv"] = $global_variables;
			}
			
			//error_log("\nClient Request:".get_class($this)."\nresponse_type:$response_type\nurl:$url\npost_data:".print_r($post_data, 1)."\n\n", 3, "/var/www/html/livingroop/default/tmp/test.log");
			
			//prepare cookies
			$url_host = parse_url($url, PHP_URL_HOST);
			$current_host = explode(":", $_SERVER["HTTP_HOST"]); //maybe it contains the port
			$current_host = $current_host[0];
			$cookies = $current_host == $url_host ? $_COOKIE : null;
			
			//send request
			debug_log_function(get_class($this) . "->requestResponse", array($url, $settings));
			
			$MyCurl = new MyCurl();
			$MyCurl->initSingle(
				array(
					"url" => $url,
					"cookie" => $cookies,
					"settings" => $settings,
					"post" => $post_data,
				)
			);
			$MyCurl->get_contents(false);
			$request = $MyCurl->getData();
			//error_log("\nClient RAW Response:".get_class($this)."\nrequest:".print_r($request, 1)."\n\n", 3, "/var/www/html/livingroop/default/tmp/test.log");
			
			//prepare response
			$content = isset($request[0]["content"]) ? $request[0]["content"] : null;
			$header = isset($request[0]["header"]) ? $request[0]["header"] : null;
			//error_log("\nClient RAW Response Content:".get_class($this)."\ncontent:".print_r($content, 1)."\n\n", 3, "/var/www/html/livingroop/default/tmp/test.log");
			
			//decrypt content
			if ($response_encryption_key && $content) {
				$key = CryptoKeyHandler::hexToBin($response_encryption_key);
				$cipher_bin = CryptoKeyHandler::hexToBin($content);
				$content = CryptoKeyHandler::decryptText($cipher_bin, $key);
			}
			
			if ($response_type == "xml")
				$response = XMLSerializer::convertValidXmlToVar($content, "response", "row");
			else if ($response_type == "json")
				$response = json_decode($content, true);
			else {
				//if $content is a object class, we SHOULD NOT include first the file, bc this action is the user responsability. This is, the include of this file should be done in the client side by the user. The responsability of doing this is not from the borkers. If a user calls a service that will return object he should include first the correspondent file. So delete the current web-service code where we pass the file path in the headers and then include that file.
				
				if ($header && !empty($header["Response-Object-Lib"])) {
					$file_path = get_lib($header["Response-Object-Lib"]);
					
					if (file_exists($file_path))
						include_once $file_path;
				}
				
				$response = unserialize($content);
			}
			
			//error_log("\nClient Response:".get_class($this)."\nresponse_type:$response_type\ncontent:".print_r($content, 1)."\n\n", 3, "/var/www/html/livingroop/default/tmp/test.log");
			//error_log("\nresponse($url):\n".print_r($content, 1)."\n".print_r($response, 1), 3, "/var/www/html/livingroop/default/tmp/test.log");
			//error_log(print_r($response["result"][0], 1), 3, "/var/www/html/livingroop/default/tmp/test.log");
			//error_log(print_r($response["result"][0]->getData(), 1), 3, "/var/www/html/livingroop/default/tmp/test.log");
			
			//return response
			if ($response && isset($response["method"]))
				return $response["result"];
			
			launch_exception(new Exception("Error connecting to REST broker with url: $url"));
			return null;
		}
		
		launch_exception(new Exception("Empty REST broker url!"));
		return null;
	}
}
?>
