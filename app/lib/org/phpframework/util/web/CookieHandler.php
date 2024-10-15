<?php
class CookieHandler {
	
	public static function setCurrentDomainEternalRootSafeCookie($name, $value = "", $expire_days = 0, $path = "", $options = array()) {
		$expire_days = $expire_days ? $expire_days : 366 * 10; //10 years
		$expires = time() + intval(60 * 60 * 24 * $expire_days);
		
		if (!$path)
			$path = "/";
		
		if (!is_array($options))
			$options = array();
		
		if (empty($options) || !array_key_exists("domain", $options))
			$options["domain"] = isset($_SERVER["HTTP_HOST"]) ? $_SERVER["HTTP_HOST"] : null; //Setting the domain is very important so the cookies can be set based in the domain. Note that is not possible to set different cookies for the same domain but with different ports. Cookies are only based on hostname.
		
		self::setSafeCookie($name, $value, $expires, $path, $options);
	}
	
	public static function setSafeCookie($name, $value, $expires, $path, $options = array()) {
		$_COOKIE[$name] = $value;
		
		//Note that is not possible to set different cookies for the same domain but with different ports. Cookies are only based on hostname.
		//The current cookie specification is RFC 6265: Cookies do not provide isolation by port. If a cookie is readable by a service running on one port, the cookie is also readable by a service running on another port of the same server. If a cookie is writable by a service on one port, the cookie is also writable by a service running on another port of the same server. For this reason, servers SHOULD NOT both run mutually distrusting services on different ports of the same host and use cookies to store security sensitive information.
		//This means that we need to remove the port from the domain, if it exists
		if ($options && !empty($options["domain"]) && strpos($options["domain"], ":") !== false)
			$options["domain"] = substr($options["domain"], 0, strpos($options["domain"], ":"));
		
		if (version_compare(PHP_VERSION, "7.3") < 0) { //or if (PHP_VERSION_ID < 70300) {
			$flags = "";
			$domain = "";
			$secure = false;
			$httponly = false;
			
			if ($options) {
				foreach ($options as $k => $v)
					if (!preg_match("/^(httponly|secure)$/i", $k)) {
						$flags .= "; $k=$v";
						
						if ($k == "domain")
							$domain = $v;
					}
					else if ($k == "secure") {
						$flags .= "; secure";
						$secure = true;
					}
					else if ($k == "httponly") {
						$flags .= "; httponly";
						$httponly = true;
					}
			}
			
			//echo $flags;die();
			return setcookie($name, $value, $expires, $path . $flags) || setcookie($name, $value, $expires, $path, $domain, $secure, $httponly);
		}
		
		$flags = $options ? $options : array();
		$flags["expires"] = isset($flags["expires"]) ? $flags["expires"] : $expires;
		$flags["path"] = isset($flags["path"]) ? $flags["path"] : $path;
		
		return setcookie($name, $value, $flags);
	}
}
?>
