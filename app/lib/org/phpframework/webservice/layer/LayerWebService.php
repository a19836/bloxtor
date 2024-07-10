<?php
abstract class LayerWebService {
	protected $PHPFrameWork;
	protected $settings;
	protected $url;
	
	protected $web_service_validation_string;
	protected $broker_server_bean_name;
	
	public function __construct($PHPFrameWork, $settings = false) {
		$this->PHPFrameWork = $PHPFrameWork;
		$this->settings = $settings;
		
		$this->init();
	}
	
	private function init() {
		$url = $this->settings && isset($this->settings["url"]) ? $this->settings["url"] : false;
		$url = empty($url) && isset($_GET["url"]) ? $_GET["url"] : $url;
		
		$aux = strstr($url, "?", true);
		$this->url = $aux ? $aux : $url;
		$this->url = $this->url && substr($this->url, -1, 1) == "/" ? substr($this->url, 0, -1) : $this->url;
		
		$this->cleanURLVarFromGlobalVars();
		$this->setUserGlobalVariables();
	}
	
	private function cleanURLVarFromGlobalVars() {
		unset($_GET["url"]);
		
		if (isset($_SERVER["QUERY_STRING"]))
			$_SERVER["QUERY_STRING"] = preg_replace("/url=([^&]*)([&]?)/u", "", $_SERVER["QUERY_STRING"]); //'/u' means with accents and ç too.
		
		if (isset($_SERVER["REDIRECT_QUERY_STRING"]))
			$_SERVER["REDIRECT_QUERY_STRING"] = preg_replace("/url=([^&]*)([&]?)/u", "", $_SERVER["REDIRECT_QUERY_STRING"]); //'/u' means with accents and ç too.
		
		if (isset($_SERVER["argv"]) && $_SERVER["argv"])
			$_SERVER["argv"][0] = preg_replace("/url=([^&]*)([&]?)/u", "", $_SERVER["argv"][0]);
	}
	
	private function setUserGlobalVariables() {
		//set global variables predefined before or sent from the client request.
		$global_variables = isset($this->settings["global_variables"]) ? $this->settings["global_variables"] : null;
		$request_encryption_key = isset($this->settings["request_encryption_key"]) ? $this->settings["request_encryption_key"] : null;
		
		//prepare global variables
		if ($request_encryption_key && $global_variables) {
			include_once get_lib("org.phpframework.encryption.CryptoKeyHandler");
			
			$key = CryptoKeyHandler::hexToBin($request_encryption_key);
			$cipher_bin = CryptoKeyHandler::hexToBin($global_variables);
			$global_variables = CryptoKeyHandler::decryptSerializedObject($cipher_bin, $key);
		}
		
		//set $GLOBALS
		if ($global_variables)
			foreach ($global_variables as $var_name => $var_value)
				if ($var_name)
					$GLOBALS[$var_name] = $var_value;
	}
	
	public function callWebService() {
		//This is to only check if the webservice is working.
		if ($this->web_service_validation_string && $this->url == $this->web_service_validation_string) {
			echo 1; 
			die();
		}
		
		$this->PHPFrameWork->loadBeansFile(BEANS_FILE_PATH);
		set_log_handler_settings();
		
		$Broker = $this->PHPFrameWork->getObject($this->broker_server_bean_name);
		
		return $Broker->callWebService($this->url);
	}
}
?>
