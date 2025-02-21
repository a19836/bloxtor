<?php
function getAppUrl($default_url = null) {
	if (!$default_url) {
		if (!empty($_SERVER["HTTP_HOST"])) {
			$project_protocol = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? "https://" : "http://"; //Do not add " || $_SERVER['SERVER_PORT'] == 443" bc the ssl port may not be 443 depending of the server configuration
			
			$default_url = $project_protocol . $_SERVER["HTTP_HOST"];
		}
		else
			$default_url = "http://localhost";
	}
	
	$app_url = env('APP_URL', $default_url);
	
	if (defined("ROUTE_PREFIX"))
		$route_prefix = ROUTE_PREFIX;
	else {
		$route_prefix = "";
		
		if (!empty($_SERVER["SCRIPT_NAME"])) {
			$script = $_SERVER["SCRIPT_NAME"];
			$script = preg_replace("/\/+/", "/", $script);
			
			$script_prefix = strstr($script, "/public/index.php", true);
			$script_prefix = preg_replace("/\/$/", "", $script_prefix);
			
			if ($script_prefix) {
				$uri = isset($_SERVER["REQUEST_URI"]) ? $_SERVER["REQUEST_URI"] : "";
				$uri = preg_replace("/\/+/", "/", $uri);
				
				$uri_prefix = substr($uri, 0, strlen($script_prefix));
				$uri_prefix = preg_replace("/\/$/", "", $uri_prefix);
				
				//http://jplpinto.localhost/app/layer/soa/proj_b/: REQUEST_URI=>"/app/layer/soa/proj_b/", SCRIPT_NAME=>"/app/layer/soa/proj_b/public/index.php"
				if ($uri_prefix == $script_prefix)
					$route_prefix = $script_prefix;
				//http://jplpinto.localhost/soa/proj_b/: REQUEST_URI=>"/soa/proj_b/", SCRIPT_NAME=>"/app/layer/soa/proj_b/public/index.php"
				else if (strpos($script, $uri) !== false)
					$route_prefix = $uri;
				//http://jplpinto.localhost/app/soa/proj_b/: REQUEST_URI=>"/app/soa/proj_b/", SCRIPT_NAME=>"/app/layer/soa/proj_b/public/index.php"
				//http://jplpinto.localhost/layer/soa/proj_b/: REQUEST_URI=>"/layer/soa/proj_b/", SCRIPT_NAME=>"/app/layer/soa/proj_b/public/index.php"
				else if (isUriInsideOfPath($script, $uri))
					$route_prefix = $uri;
				//http://jplpinto.localhost/soa/proj_b/login: REQUEST_URI=>"/soa/proj_b/login", SCRIPT_NAME=>"/app/layer/soa/proj_b/public/index.php"
				else {
					do {
						$uri = dirname($uri);
						
						if (isUriInsideOfPath($script, $uri)) {
							$route_prefix = $uri;
							break;
						}
					} 
					while ($uri && $uri != "/");
    			}
    			
    			$route_prefix = preg_replace("/\/+$/", "", $route_prefix);
			}
		}
		
		define("ROUTE_PREFIX", $route_prefix);
		//echo "<pre>";print_r($_SERVER);die();
		//echo "ROUTE_PREFIX:".ROUTE_PREFIX;die();
	}
	
	if ($route_prefix) {
		//$route_prefix .= "/";
		$path = $app_url ? parse_url($app_url, PHP_URL_PATH) : "";
		
		if (!$app_url || !$path || substr($path, 0, strlen($route_prefix)) != $route_prefix) {
			$app_url = request()->getSchemeAndHttpHost();
			$app_url .= "/" . preg_replace("/^\/+/", "", $route_prefix);
		}
	}
	//echo "route_prefix:$route_prefix|$app_url";die();
	
	return $app_url;
}

function configureUrlPath(&$path) {
	//echo ROUTE_PREFIX."|".$path."\n<br>";die();
	
	if (ROUTE_PREFIX) {
		$route_prefix = ROUTE_PREFIX . "/";
		$p = preg_replace("/\/+/", "/", $path);
		
		if (ROUTE_PREFIX != $p && $route_prefix != $p && substr($p . "/", 0, strlen($route_prefix)) != $route_prefix)
			$path = preg_replace("/\/+/", "/", $route_prefix . $p);
	}
	//echo ROUTE_PREFIX."|".$path."\n<br>";
	
	return $path;
}

function isUriInsideOfPath($path, $uri) {
	//remove duplicates, bc the $uri can have duplicates and then it will not match correctly in the path
	$path = preg_replace("/\/+/", "/", $path);
	$uri = preg_replace("/\/+/", "/", $uri);
	
	//remove last slash so we can compare it correctly, otherwise if the $uri is smaller than the $path, then this function will always returned false, bc is comparing the last empty part of the uri
	$path = preg_replace("/\/+$/", "", $path);
	$uri = preg_replace("/\/+$/", "", $uri);
	
	$uri_parts = explode('/', $uri);
	$path_parts = explode('/', $path);
	$path_parts_total = count($path_parts);
	$index = 0;
	//echo "uri:$uri|path$path<br/>";

	foreach ($uri_parts as $part) {
		while ($index < $path_parts_total && $path_parts[$index] !== $part)
			$index++;

		if ($index >= $path_parts_total)
			return false;

		//echo "index:$index|$part!<br/>";
		$index++;
	}

	//echo "IS INSIDE<br/>";
	return true;
}
?>
