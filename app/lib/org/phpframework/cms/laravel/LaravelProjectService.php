<?php
include_once $vars["business_logic_modules_service_common_file_path"];
include_once get_lib("org.phpframework.cms.laravel.LaravelProjectHandler");

class LaravelProjectService extends #COMMON_SERVICE# {
	
	private $LaravelProjectHandler;
	
	private function initLaravel() {
		if (!$this->LaravelProjectHandler) {
			$this->LaravelProjectHandler = new LaravelProjectHandler(__DIR__ . "/");

			// Now Laravel is fully booted, and you can call Routers, Controllers, Views...
		}
	}
	
	/**
	 * @param (name=data[uri], sanitize_html=1)
	 * @param (name=data[headers], sanitize_html=1)
	 * @param (name=data[authentication][user], sanitize_html=1) 
	 * @param (name=data[authentication][pass], sanitize_html=1) 
	 * @param (name=data[cookies], sanitize_html=1)
	 */
	public function callRouter($data) {
		$this->initLaravel();
		
		$uri = isset($data["uri"]) ? $data["uri"] : "";
		$body = $this->LaravelProjectHandler->callRouter($uri, $data, $status);
		
		return array(
			"body" => $body,
			"status" => $status,
		);
	}
	
	/**
	 * @param (name=data[class], not_null=1, sanitize_html=1)
	 * @param (name=data[method], not_null=1, sanitize_html=1)
	 */
	public function callController($data) {
		$this->initLaravel();
		
		return $this->LaravelProjectHandler->callController($data["class"], $data["method"]);
	}
	
	/**
	 * @param (name=data[view], not_null=1, sanitize_html=1)
	 * @param (name=data[data])
	 */
	public function callView($data) {
		$this->initLaravel();
		
		$view_data = isset($data["data"]) ? $data["data"] : "";
		return $this->LaravelProjectHandler->callView($data["view"], $view_data);
	}
	
	/**
	 * @param (name=data[view], not_null=1, sanitize_html=1)
	 */
	public function existsView($data) {
		$this->initLaravel();
		
		return $this->LaravelProjectHandler->existsView($data["view"]);
	}
	
	/**
	 * @param (name=data[sql], not_null=1, sanitize_html=1)
	 * @param (name=data[model], sanitize_html=1)
	 */
	public function getSQLResults($data) {
		$this->initLaravel();
		
		$model = isset($data["model"]) ? $data["model"] : "";
		return $this->LaravelProjectHandler->getSQLResults($data["sql"], $model);
	}
}
?>
