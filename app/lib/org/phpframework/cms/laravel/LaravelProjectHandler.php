<?php
class LaravelProjectHandler {
	
	private $laravel_project_path;
	private $app;
	private $kernel;
	private $original_request_uri;
	private $original_script_name;
	private $original_script_file_name;
	private $original_redirect_url;
	
	public function __construct($laravel_project_path) {
		$this->laravel_project_path = $laravel_project_path;
		$this->initLaravel();
	}
	
	public function start() {
		return; //the code below is not needed.
		
		$this->original_request_uri = isset($_SERVER["REQUEST_URI"]) ? $_SERVER["REQUEST_URI"] : null;
		$this->original_script_name = isset($_SERVER["SCRIPT_NAME"]) ? $_SERVER["SCRIPT_NAME"] : null;
		$this->original_script_file_name = isset($_SERVER["SCRIPT_FILENAME"]) ? $_SERVER["SCRIPT_FILENAME"] : null;
		$this->original_redirect_url = isset($_SERVER["REDIRECT_URL"]) ? $_SERVER["REDIRECT_URL"] : null;
		
		$env_path = $this->laravel_project_path . "/.env";
		$contents = file_exists($env_path) ? file_get_contents($env_path) : "";
		$app_url = $contents && preg_match("/APP_URL\s*=([^\n]*)/", $contents, $match, PREG_OFFSET_CAPTURE) ? trim($match[1][0]) : null;
		$request_uri = $app_url ? parse_url($app_url, PHP_URL_PATH) : "";
		$script_name = $this->laravel_project_path . "/public/index.php";
		$script_name = preg_replace("/\/+/", "/", $script_name);
		$script_file_name = $script_name;
		
		if (strpos($this->original_script_name, "/app/") !== false) {
			$document_root = str_replace("//", "/", (isset($_SERVER["CONTEXT_DOCUMENT_ROOT"]) ? $_SERVER["CONTEXT_DOCUMENT_ROOT"] : (isset($_SERVER["DOCUMENT_ROOT"]) ? $_SERVER["DOCUMENT_ROOT"] : "") ) . "/");
			
			$script_name = strstr($this->original_script_name, "/app/", true) . strstr($script_name, "/app/");
			$script_file_name = $document_root . $script_name;
			$script_file_name = preg_replace("/\/+/", "/", $script_file_name);
		}
		
		$redirect_url = strstr($script_name, "/public/index.php", true) . "/";
		
		$_SERVER["REQUEST_URI"] = $request_uri;
		$_SERVER["SCRIPT_NAME"] = $_SERVER["PHP_SELF"] = $script_name;
		$_SERVER["SCRIPT_FILENAME"] = $script_file_name;
		$_SERVER["REDIRECT_URL"] = $redirect_url;
		//echo "<pre>";print_r($_SERVER);die();
	}
	
	public function end() {
		return; //the code below is not needed.
		
		if ($this->original_script_name) {
			$_SERVER["REQUEST_URI"] = $this->original_request_uri;
			$_SERVER["SCRIPT_NAME"] = $_SERVER["PHP_SELF"] = $this->original_script_name;
			$_SERVER["SCRIPT_FILENAME"] = $this->original_script_file_name;
			$_SERVER["REDIRECT_URL"] = $this->original_redirect_url;
			
			$this->original_request_uri = $this->original_script_name = $this->original_script_file_name = $this->original_redirect_url = null;
		}
	}
	
	public function initLaravel() {
		$this->start();
		
		if (!$this->app) {
			// Define the path to Laravel's base directory
			if (!defined("LARAVEL_START"))
				define("LARAVEL_START", microtime(true));
			
			// Include Laravel's Autoloader
			if (file_exists($this->laravel_project_path . "/vendor/autoload.php"))
				include_once $this->laravel_project_path . "/vendor/autoload.php";  // For Laravel 6+
			else if (file_exists($this->laravel_project_path . "/bootstrap/autoload.php"))
				include_once $this->laravel_project_path . "/bootstrap/autoload.php";  // For Laravel 5
			
			// Boot the Laravel application
			$this->app = (include_once $this->laravel_project_path . "/bootstrap/app.php");

			// Resolve the Kernel (For Laravel Services)
			$this->kernel = $this->app->make(Illuminate\Contracts\Http\Kernel::class);
			
			$request = Illuminate\Http\Request::capture();
			$response = $this->kernel->handle($request);
			//$response = $app->handleRequest($request);
			
			// Now Laravel is fully booted, and you can call Routers, Controllers, Views...
		}
		
		$this->end();
	}
	
	public function callRouter($uri, $settings = null, &$status = null) {
		if (empty($_SERVER["APP_URL"]))
			launch_exception(new Exception("APP_URL is not defined!"));
		
		$this->start();
		
		//get settings
		$headers = isset($settings["headers"]) && is_array($settings["headers"]) ? $settings["headers"] : "";
		$cookies = isset($settings["cookies"]) ? $settings["cookies"] : "";
		$authentication = isset($settings["authentication"]) ? $settings["authentication"] : "";
		//echo "headers:".print_r($headers, 1);die();
		
		//prepare url
		$app_url = $_SERVER["APP_URL"];
		$app_url = preg_replace("/\/+/", "/", $app_url);
		$app_url = preg_replace("/\/$/", "", $app_url);
		$app_url = preg_replace("/:\//", "://", $app_url, 1);
		
		$url = $app_url . $uri;
		//echo "url:$url";die();
		
		//prepare authentication
		$authentication_user = $authentication_pass = null;
		
		if ($authentication) {
			if (is_string($authentication))
				list($authentication_user, $authentication_pass) = explode(":", $authentication);
			else if (is_array($authentication)) {
				if (isset($authentication["user"]) && isset($authentication["pass"])) {
					$authentication_user = $authentication["user"];
					$authentication_pass = $authentication["pass"];
				}
				else {
					$authentication_user = $authentication[0];
					$authentication_pass = $authentication[1];
				}
			}
		}
		
		//prepare cookies
		if ($cookies) {
			$cookies_header = "";
			
			if (is_array($cookies))
				foreach ($cookies as $key => $value)
					$cookies_header .= "$key=$value; ";
			else if (is_string($cookies))
				$cookies_header = $cookies;
			
			$cookies_header = rtrim($cookies_header, "; "); // Trim the last semicolon and space
			
			if ($cookies_header) {
				if (!is_array($headers))
					$headers = array();
				
				$headers["Cookie"] = $cookies_header;
			}
		}
		
		//prepare request
		if ($headers) {
			$obj = Illuminate\Support\Facades\Http::withHeaders($headers);
			
			if ($authentication)
				$obj->withBasicAuth($authentication_user, $authentication_pass);
			
			$response = $obj->get($url);
		}
		else if ($authentication)
			$response = Illuminate\Support\Facades\Http::withBasicAuth($authentication_user, $authentication_pass)->get($url);
		else
			$response = Illuminate\Support\Facades\Http::get($url);
		
		$status = $response->status();
		$ret = $response->body();
		
		$this->end();
		
		return $ret;
	}
	
	/**
	 * @param (name=data[class], not_null=1, sanitize_html=1)
	 * @param (name=data[method], not_null=1, sanitize_html=1)
	 */
	public function callController($class_name, $method_name) {
		if (!file_exists($this->laravel_project_path . "/app/Http/Controllers/" . $class_name . ".php"))
			launch_exception(new Exception("Laravel class: '$class_name' doesn't exists in 'app/Http/Controllers' folder!"));
		
		$this->start();
		
		eval("\$controller = \$this->app->make(App\Http\Controllers\\" . $class_name . "::class);"); // Resolve the controller instance using Laravel’s container
		
		if (!$controller) {
			$this->end();
			launch_exception(new Exception("Could not create Laravel controller for class '\App\Http\Controllers\$class_name'!"));
		}
		
		if (!method_exists($controller, $method_name)) {
			$this->end();
			launch_exception(new Exception("Method '$method_name' doesn't exist in class: '\App\Http\Controllers\$class_name'!"));
		}
		
		$ret = $controller->$method_name(); // Call the method
		
		$this->end();
		
		return $ret;
	}
	
	public function callView($view_name, $view_data) {
		$this->start();
		$ret = null;
		
		if ($this->existsView($view_name)) {
			$ret = Illuminate\Support\Facades\View::make($view_name, $view_data)->render();
		
			$this->end();
		}
		else {
			$this->end();
			launch_exception(new Exception("View '$view_name' doesn't exist!"));
		}
		
		return $ret;
	}
	
	public function existsView($view_name) {
		$this->start();
		
		$ret = Illuminate\Support\Facades\View::exists($view_name);
		
		$this->end();
		
		return $ret;
	}
	
	public function getSQLResults($sql, $model = null) {
		$this->start();
		
		$ret = Illuminate\Support\Facades\DB::select($sql);
		
		if ($ret && $model) {
			if (!file_exists($this->laravel_project_path . "/app/Models/" . $model . ".php")) {
				$this->end();
				launch_exception(new Exception("Laravel model: '$model' doesn't exists in 'app/Models' folder!"));
			}
			else
				eval("\$ret = App\Models\\" . $model . "::hydrate(\$ret);");
		}
		
		$this->end();
		
		return $ret;
	}
}
?>
