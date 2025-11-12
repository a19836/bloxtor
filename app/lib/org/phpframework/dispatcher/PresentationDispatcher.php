<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

include_once get_lib("org.phpframework.dispatcher.exception.PresentationDispatcherException");
include_once get_lib("org.phpframework.dispatcher.Dispatcher");

class PresentationDispatcher extends Dispatcher {
	
	private $url;
	private $page_code;
	private $parameters;
	private $requested_file_path;
	
	private $Router;
	private $PresentationLayer;
	
	public function __construct() {}
	
	public function setPresentationLayer($PresentationLayer) { $this->PresentationLayer = $PresentationLayer; }
	public function getPresentationLayer() { return $this->PresentationLayer; }
	
	public function setRouter($Router) { $this->Router = $Router; }
	public function getRouter() { return $this->Router; }
	
	public function dispatch($url) {
		$this->Router->load();
		$this->url = $this->Router->parse($url);
		
		$explode = explode("/", $this->url);
		
		while ($explode[ count($explode) - 1] == "") //deletes empty parameters - normally the last one will be an empty string by default
			array_pop($explode);
			
		$this->page_code = $explode[0];
		$this->parameters = $explode;
		array_shift($this->parameters);
		
		$this->requested_file_path = $this->PresentationLayer->getPagePath($this->page_code);
		if(!file_exists($this->requested_file_path)) {
			launch_exception(new PresentationDispatcherException(1, $this->requested_file_path));
		}
	}
	
	public function getURL() { return $this->url;}
	public function getPageCode() { return $this->page_code;}
	public function getParameters() { return $this->parameters;}
	public function getRequestedFilePath() { return $this->requested_file_path;}
}
?>
