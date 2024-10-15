<?php
include_once get_lib("org.phpframework.dispatcher.exception.EVCDispatcherException");
include_once get_lib("org.phpframework.dispatcher.Dispatcher");

class EVCDispatcher extends Dispatcher {
	
	private $url;
	private $page_code;
	private $parameters;
	private $requested_file_path;
	
	private $Router;
	private $EVC;
	
	public function __construct() {}
	
	public function setEVC($EVC) { $this->EVC = $EVC; }
	public function getEVC() { return $this->EVC; }
	
	public function setRouter($Router) { $this->Router = $Router; }
	public function getRouter() { return $this->Router; }
	
	public function dispatch($url) {
		$this->Router->load();
		$this->url = $this->Router->parse($url);
		
		$explode = explode("/", $this->url);
		
		while (count($explode) > 0 && $explode[ count($explode) - 1] == "") //deletes empty parameters - normally the last one will be an empty string by default
			array_pop($explode);
		
		$this->page_code = count($explode) ? $explode[0] : null;
		if($this->page_code && $this->EVC->controllerExists($this->page_code)) {
			$this->requested_file_path = $this->EVC->getControllerPath($this->page_code);
			$this->parameters = $explode;
			array_shift($this->parameters);
		}
		else {
			$default_controller_code = $this->EVC->getDefaultController();
			
			if($default_controller_code && $this->EVC->controllerExists($default_controller_code)) {
				$this->requested_file_path = $this->EVC->getControllerPath($default_controller_code);
				$this->parameters = $explode;
			}
			else {
				launch_exception(new EVCDispatcherException(1, array($this->page_code, $default_controller_code)));
			}
		}
	}
	
	public function getURL() { return $this->url;}
	public function getPageCode() { return $this->page_code;}
	public function getParameters() { return $this->parameters;}
	public function getRequestedFilePath() { return $this->requested_file_path;}
}
?>
