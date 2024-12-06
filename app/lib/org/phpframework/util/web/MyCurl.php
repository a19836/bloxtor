<?php
class MyCurl {
	private $data;
	
	public static function downloadFile($file_url, &$fp = null) {
		if ($file_url) {
			$fp = tmpfile(); //This is the file where we save the information
			$meta_data = stream_get_meta_data($fp);
			$fp_path = isset($meta_data['uri']) ? $meta_data['uri'] : null;
			
			$settings = array(
				"url" => str_replace(" ","%20", $file_url), //replace spaces with %20
				"settings" => array(
					"follow_location" => true,
					"connection_timeout" => 50,
					"CURLOPT_TIMEOUT" => 50,
					"CURLOPT_FILE" => $fp, // write curl response to file
				)
			);
			
			$MyCurl = new MyCurl();
			$MyCurl->initSingle($settings);
			$MyCurl->get_contents();
			$data = $MyCurl->getData();
			$data = isset($data[0]) ? $data[0] : null;
			
			if (!empty($data["info"]) && isset($data["info"]["http_code"]) && $data["info"]["http_code"] == 200) {
				return array(
					"name" => basename(parse_url($file_url, PHP_URL_PATH)),
					"type" => !empty($data["info"]["content_type"]) ? $data["info"]["content_type"] : mime_content_type($fp_path),
					"tmp_name" => $fp_path,
					"error" => 0,
					"size" => !empty($data["info"]["size_download"]) ? $data["info"]["size_download"] : filesize($fp_path),
				);
			}
		}
		
		return null;
	}
	
	public static function getUrlContents($single_data, $result_type = null) {
		$MyCurl = new MyCurl();
		$MyCurl->initSingle($single_data);
		$MyCurl->get_contents();
		$data = $MyCurl->getData();
		//echo "<pre>";print_r($data);die();
		$data = isset($data[0]) ? $data[0] : null;
		
		$data_header = isset($data["header"]) ? $data["header"] : null;
		$data_content = isset($data["content"]) ? $data["content"] : null;
		$data_settings = isset($data["settings"]) ? $data["settings"] : null;
		
		$res = $result_type == "header" ? $data_header : (
			in_array($result_type, array("content", "content_json", "content_xml", "content_xml_simple", "content_serialized")) ? $data_content : (
				$result_type == "settings" ? $data_settings : $data
			)
		);
		
		if ($res) {
			if ($result_type == "content_json")
				$res = json_decode($res, true);
			else if ($result_type == "content_xml" || $result_type == "content_xml_simple") {
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
	
	public function initSingle($data) {
		if (!isset($data["post"])) $data["post"] = array();
		if (!isset($data["get"])) $data["get"] = array();
		if (!isset($data["cookie"])) $data["cookie"] = array();
		if (!isset($data["url"])) $data["url"] = false;
		if (!isset($data["files"])) $data["files"] = false;
		if (!isset($data["settings"])) $data["settings"] = array();
		
		$data["settings"] = $this->prepareSettingsData($data["settings"]);
		$data["post"] = $this->preparePostData($data["post"], $data["settings"]);
		$data["get"] = $this->prepareGetData($data["get"], $data["settings"]);
		$data["cookie"] = $this->prepareCookiesData($data["cookie"], $data["settings"]);
		
		if ($data["get"]) {
			$index = strpos($data["url"], "?");
			$data["url"] .= is_numeric($index) ? $data["get"] : "?" . $data["get"];
		}
		
		$this->data = array(
			array(
				"url" => $data["url"],
				"post" => $data["post"],
				"get" => $data["get"],
				"cookie" => $data["cookie"],
				"files" => $data["files"],
				"settings" => $data["settings"],
				"content" => false,
				"error" => false,
			)
		);
	}
	
	public function initMultiple($data) {
		$t = $data ? count($data) : 0;
		
		for ($i = 0; $i < $t; $i++) {
			if (!isset($data[$i]["post"])) $data[$i]["post"] = array();
			if (!isset($data[$i]["get"])) $data[$i]["get"] = array();
			if (!isset($data[$i]["cookie"])) $data[$i]["cookie"] = array();
			if (!isset($data[$i]["url"])) $data[$i]["url"] = false;
			if (!isset($data[$i]["files"])) $data[$i]["files"] = false;
			if (!isset($data[$i]["settings"])) $data[$i]["settings"] = array();
		
			$data[$i]["settings"] = $this->prepareSettingsData($data[$i]["settings"]);
			$data[$i]["post"] = $this->preparePostData($data[$i]["post"], $data[$i]["settings"]);
			$data[$i]["get"] = $this->prepareGetData($data[$i]["get"], $data[$i]["settings"]);
			$data[$i]["cookie"] = $this->prepareCookiesData($data[$i]["cookie"], $data[$i]["settings"]);
			
			if ($data[$i]["get"]) {
				$index = strpos($data[$i]["url"], "?");
				$data[$i]["url"] .= is_numeric($index) ? $data[$i]["get"] : "?" . $data[$i]["get"];
			}
			
			$data[$i]["content"] = false;
			$data[$i]["error"] = false;
		}
		
		$this->data = $data;
	}
	
	public function initSingleGroup($data) {
		if (!isset($data["post"])) $data["post"] = array();
		if (!isset($data["get"])) $data["get"] = array();
		if (!isset($data["cookie"])) $data["cookie"] = array();
		if (!isset($data["files"])) $data["files"] = false;
		if (!isset($data["settings"])) $data["settings"] = array();
		
		$data["settings"] = $this->prepareSettingsData($data["settings"]);
		$data["post"] = $this->preparePostData($data["post"], $data["settings"]);
		$data["get"] = $this->prepareGetData($data["get"], $data["settings"]);
		$data["cookie"] = $this->prepareCookiesData($data["cookie"], $data["settings"]);
		
		$this->data = array();
		
		$t = !empty($data["urls"]) ? count($data["urls"]) : 0;
		for ($i = 0; $i < $t; $i++) {
			if ($data["get"]) {
				$index = strpos($data["urls"][$i], "?");
				$data["urls"][$i] .= is_numeric($index) ? $data["get"] : "?" . $data["get"];
			}
			
			$this->data[] = array(	
				"url" => $data["urls"][$i], 
				"post" => $data["post"], 
				"get" => $data["get"], 
				"cookie" => $data["cookie"],
				"files" => $data["files"], 
				"settings" => $data["settings"],
				"content" => false, 
				"error" => false, 
			);
		}
	}
	
	public function get_contents($thread = false) {
		$status = false;
		$session_id = !defined("PHP_SESSION_NONE") || session_status() != PHP_SESSION_NONE ? session_id() : false;
		
		if ($session_id)
			session_write_close();
			//This is needed, because if the session_id in the COOKIE var is the same than the current Session id, the PHP engine will lock the session file and PHP will not behave correctly. If this happens, Curl will be in a infinit loop. To avoid this bug, we need to close the write, then call the curl requests with the same PHPSESSID and only after it finishes, call the session_start again. More info in: http://php.net/session_write_close
		
		if ($this->data) {
			if ($thread)
				$status = $this->get_contents_assyn($thread);
			else
				$status = $this->get_contents_syn();
		}
		
		if ($session_id)
			session_start();
		
		return $status;
	}

	private function get_contents_syn() {
		$status = true;
		$t = $this->data ? count($this->data) : 0;
		$verbose = false;
		
		for ($i = 0; $i < $t; $i++) {
			$conn = curl_init();
			$this->setCurlOpts($conn, $this->data[$i]);
			
			// Habilita o modo verbose para capturar todos os detalhes da requisição
			if ($verbose) {
				$verbose_temp = fopen('php://temp', 'w+'); // Cria um ficheiro temporário para capturar os detalhes da requisição
				curl_setopt($conn, CURLOPT_VERBOSE, true);
				curl_setopt($conn, CURLOPT_STDERR, $verbose_temp);
				//error_log(print_r($this->data[$i], 1), 3, "/var/www/html/livingroop/default/tmp/phpframework.log");
			}
			
			//exec curl
			$content = curl_exec($conn);
			
			if (!empty($this->data[$i]["settings"]["header"])) {
				$header_size = curl_getinfo($conn, CURLINFO_HEADER_SIZE);
				$header = substr($content, 0, $header_size);
				$content = substr($content, $header_size);
				
				$this->data[$i]["header"] = self::parseHeadersText($header);
			}
			
			$this->data[$i]["content"] = $content;
			
			if (curl_errno($conn)) {
				$this->data[$i]["error"] = curl_error($conn);
				$status = false;
			}
			else
				$this->data[$i]["info"] = curl_getinfo($conn);
			//error_log(print_r(curl_getinfo($conn), 1), 3, "/var/www/html/livingroop/default/tmp/phpframework.log");
			
			// Lê e exibe a mensagem de requisição cURL (cabeçalhos e corpo)
			if ($verbose) {
				rewind($verbose_temp); // Retorna para o início do ficheiro temporário para ler o conteúdo
				$verbose_info = stream_get_contents($verbose_temp);
				error_log($verbose_info, 3, "/var/www/html/livingroop/default/tmp/phpframework.log");
			}
			
			if (function_exists("curl_close"))	
				curl_close($conn);
		}
		
		return $status;
	}
	
	/*
	 * CURLMOPT_MAX_TOTAL_CONNECTIONS: 
	 * 	Basically it divides the urls into chunks if the request are bigger than CURLMOPT_MAX_TOTAL_CONNECTIONS.
	 * 	https://curl.haxx.se/libcurl/c/CURLMOPT_MAX_TOTAL_CONNECTIONS.html: "Pass a long for the amount. The set number will be used as the maximum number of simultaneously open connections in total using this multi handle. For each new session, libcurl will open a new connection up to the limit set by CURLMOPT_MAX_TOTAL_CONNECTIONS. When the limit is reached, the sessions will be pending until there are available connections. If CURLMOPT_PIPELINING is enabled, libcurl will try to pipeline or use multiplexing if the host is capable of it."
	 * 	https://www.php.net/manual/en/function.curl-multi-setopt.php: Pass a number that specifies the maximum number of simultaneously open connections.
	 *
	 * CURLMOPT_MAX_HOST_CONNECTIONS:
	 * 	Basically divides the urls into chunks per hosts if the request are bigger than CURLMOPT_MAX_HOST_CONNECTIONS.
	 * 	https://curl.haxx.se/libcurl/c/CURLMOPT_MAX_HOST_CONNECTIONS.html: Pass a long to indicate max. The set number will be used as the maximum amount of simultaneously open connections to a single host (a host being the same as a host name + port number pair). For each new session to a host, libcurl will open a new connection up to the limit set by CURLMOPT_MAX_HOST_CONNECTIONS. When the limit is reached, the sessions will be pending until a connection becomes available. If CURLMOPT_PIPELINING is enabled, libcurl will try to pipeline if the host is capable of it.
	 * 	https://www.php.net/manual/en/function.curl-multi-setopt.php: Pass a number that specifies the maximum number of connections to a single host.
	 * 
	 */
	private function get_contents_assyn($thread) {
		$wait = isset($thread["wait"]) ? $thread["wait"] : false;
		$max_chunk_requests = isset($thread["max_chunk_requests"]) && $thread["max_chunk_requests"] > 0 ? $thread["max_chunk_requests"] : false;
		$max_host_chunk_requests = isset($thread["max_host_chunk_requests"]) && $thread["max_host_chunk_requests"] > 0 ? $thread["max_host_chunk_requests"] : false;
		
		$conn = array();
		$mh = curl_multi_init();
		
		//defines the maximum number of requests that curl will execute simultaneously. The others will be pending. Basically this is more related with the server that makes the requests, otherwise to limit the requests based in the destination server, please use CURLMOPT_MAX_HOST_CONNECTIONS. Please see the description of the this flag above.
		//CURLMOPT_MAX_TOTAL_CONNECTIONS only exists from PHP 7.0.7, so we must check if exists by doing is_numeric(...)
		if ($max_chunk_requests && is_numeric(CURLMOPT_MAX_TOTAL_CONNECTIONS)) 
			curl_multi_setopt($mh, CURLMOPT_MAX_TOTAL_CONNECTIONS, $max_chunk_requests);
		
		//defines the maximum number of requests that curl will execute simultaneously per host. The others will be pending. Basically this is more related with the server that receives the requests, this is, the host server. Please see the description of the this flag above.
		//CURLMOPT_MAX_TOTAL_CONNECTIONS only exists from PHP 7.0.7, so we must check if exists by doing is_numeric(...)
		if ($max_host_chunk_requests && is_numeric(CURLMOPT_MAX_HOST_CONNECTIONS)) 
			curl_multi_setopt($mh, CURLMOPT_MAX_HOST_CONNECTIONS, $max_host_chunk_requests);
		
		$t = $this->data ? count($this->data) : 0;
		for ($i = 0; $i < $t; $i++) {
			$conn[$i]=curl_init();
			$this->setCurlOpts($conn[$i], $this->data[$i]);
			
			if (!$wait) {
				curl_setopt($conn[$i], CURLOPT_TIMEOUT, 1);
				curl_setopt($conn[$i], CURLOPT_NOSIGNAL, 1);
			}
			
			curl_multi_add_handle($mh, $conn[$i]);
		}
		
		//execute the handles while we're still active, execute curl
		$active = null;
		
		do {
			$mrc = curl_multi_exec($mh, $active);
		}
		while ($mrc == CURLM_CALL_MULTI_PERFORM);
		//echo "active:$active, mrc: $mrc\n";
		
		/*https://www.php.net/manual/en/function.curl-multi-select.php:
			"When libcurl returns -1 in max_fd, it is because libcurl currently does something that isn't possible for your application to monitor with a socket and unfortunately you can then not know exactly when the current action is completed using select(). When max_fd returns with -1, you need to wait a while and then proceed and call curl_multi_perform anyway. How long to wait? I would suggest 100 milliseconds at least, but you may want to test it out in your own particular conditions to find a suitable value."
		
		libcurl can break, so when ($active && $mrc == CURLM_OK) happens, we should wait 100 milliseconds or 1 microsecond.
		If everything went ok the $active should be false and should not enter in the while bellow...
		*/
		while ($active && $mrc == CURLM_OK) {
			// Wait for activity on any curl-connection
			//if $wait is false, sleeps for 300 millseconds until libcurl recovered and then try again...
			if (!$wait)
				usleep(3);
			//curl_multi_select blocks the calling process until there is activity on any of the connections opened by the curl_multi interface, or until the timeout period has expired. In other words, it waits for data to be received in the opened connections. So we should only call it if $wait is true.
			else if (curl_multi_select($mh) == -1) 
				usleep(1);

			// Continue to exec until curl is ready to
			// give us more data
			do {
				$mrc = curl_multi_exec($mh, $active);
			}
			while ($mrc == CURLM_CALL_MULTI_PERFORM);
		}
		//echo "active:$active, mrc: $mrc\n";
		
		$status = !$active && $mrc == CURLM_OK;
		
		//iterate through the handles and get your content
		for ($i = 0; $i < $t; $i++) {
			if ($wait) {
				$content = curl_multi_getcontent($conn[$i]);
				
				if (!empty($this->data[$i]["settings"]["header"])) {
					$header_size = curl_getinfo($conn[$i], CURLINFO_HEADER_SIZE);
					$header = substr($content, 0, $header_size);
					$content = substr($content, $header_size);
					
					$this->data[$i]["header"] = self::parseHeadersText($header);
				}
				
				$this->data[$i]["content"] = $content;
				
				if (curl_errno($conn[$i])) {
					$this->data[$i]["error"] = curl_error($conn[$i]);
					//echo $this->data[$i]["url"].":".$this->data[$i]["error"]."\n";die();
					$status = false;
				}
				else
					$this->data[$i]["info"] = curl_getinfo($conn[$i]);
			}
			
			curl_multi_remove_handle($mh,$conn[$i]);
			
			if (function_exists("curl_close"))	
				curl_close($conn[$i]);
		}
		
		if (function_exists("curl_multi_close"))	
			curl_multi_close($mh);
		
		return $status;
	}
	
	private function setCurlOpts(&$conn, &$data) {
		$url = isset($data["url"]) ? $data["url"] : null;
		$this->setCurlUrlOpt($conn, $url);
		
		curl_setopt($conn, CURLOPT_RETURNTRANSFER, 1);
		
		if (!empty($data["post"])) {
			if (empty($data["settings"]["put"]))
				curl_setopt($conn, CURLOPT_POST, 1);
			
			$this->setCurlPostOpts($conn, $data);
		}
		
		if (!empty($data["cookie"]))
			curl_setopt($conn, CURLOPT_COOKIE, $data["cookie"]);
			//curl_setopt($conn, CURLOPT_COOKIE, self::removeBomCharacter($data["cookie"]));
		
		if (!empty($data["settings"]["put"]))
			curl_setopt($conn, CURLOPT_PUT, true);
		
		if (isset($data["settings"]["header"]))
			curl_setopt($conn, CURLOPT_HEADER, $data["settings"]["header"] ? true : false);
		
		if (isset($data["settings"]["connection_timeout"]))
			curl_setopt($conn, CURLOPT_CONNECTTIMEOUT, is_numeric($data["settings"]["connection_timeout"]) ? $data["settings"]["connection_timeout"] : 60); //in seconds
		
		if (!empty($data["settings"]["no_body"]))
			curl_setopt($conn, CURLOPT_NOBODY, $data["settings"]["no_body"] ? true : false);
		
		if (!empty($data["settings"]["http_header"])) 
			curl_setopt($conn, CURLOPT_HTTPHEADER, $data["settings"]["http_header"]);
		
		if (!empty($data["settings"]["referer"])) 
			curl_setopt($conn, CURLOPT_REFERER	, $data["settings"]["referer"]);
		
		if (!empty($data["settings"]["follow_location"])) 
			curl_setopt($conn, CURLOPT_FOLLOWLOCATION, $data["settings"]["follow_location"] ? true : false);
		
		if (!empty($data["settings"]["http_auth"]))
			curl_setopt($conn, CURLOPT_HTTPAUTH, $data["settings"]["http_auth"]);
		
		if (!empty($data["settings"]["user_pwd"]))
			curl_setopt($conn, CURLOPT_USERPWD, $data["settings"]["user_pwd"]);
		
		//The name of the file containing the cookie data. The cookie file can be in Netscape format, or just plain HTTP-style headers dumped into a file.
		if (!empty($data["settings"]["read_cookies_from_file"]))
			curl_setopt($conn, CURLOPT_COOKIEFILE, $data["settings"]["read_cookies_from_file"]);
		
		//The name of a file to save all internal cookies to when the connection closes.
		if (!empty($data["settings"]["save_cookies_to_file"]))
			curl_setopt($conn, CURLOPT_COOKIEJAR, $data["settings"]["save_cookies_to_file"]);
		
		//set other curlopt settings
		if (!empty($data["settings"]))
			foreach ($data["settings"] as $k => $v)
				if (substr($k, 0, 8) == "CURLOPT_") {
					eval("\$curlopt_key = $k;");
					curl_setopt($conn, $curlopt_key, $v);
				}
	}
	
	private function setCurlUrlOpt(&$conn, &$url) {
		$index = strpos($url, "?");
		
		if ($index !== false) {
			$query_string = substr($url, $index + 1);
			
			if ($query_string) {
				parse_str($query_string, $parsed);
				$url = substr($url, 0, $index + 1) . http_build_query($parsed);
			}
		}
		
		curl_setopt($conn, CURLOPT_URL, $url);
	}
	
	private function setCurlPostOpts(&$conn, &$data) {
		if (isset($data["files"]) && is_array($data["files"]) && count($data["files"]) > 0) {
			$boundary = "";
			for ($i = 0; $i < 27; $i++) {
		  		$boundary .= "-";
		  	}
		  	$boundary .= rand().rand();
			
			$body = '';
			
			if (isset($data["post"]) && is_array($data["post"]))
				foreach ($data["post"] as $key => $value) {
					$body .= '--'.$boundary."\n"
						. 'Content-Disposition: form-data; name="'.$key.'"'."\n"
						. "\n"
						. urlencode($value)."\n";
				}
			
			foreach ($data["files"] as $key => $value) {
				$tmp_name = isset($value["tmp_name"]) ? $value["tmp_name"] : null;
				$name = isset($value["name"]) ? $value["name"] : null;
				$type = isset($value["type"]) ? $value["type"] : null;
				
				$bin_content = $this->getFileContent($tmp_name);
				if (substr($bin_content, strlen($bin_content) - 1) == "\n")
					$bin_content = substr($bin_content, 0, strlen($bin_content) - 1);
		
				$body .= '--'.$boundary."\n"
				. 'Content-Disposition: form-data; name="'.$key.'"; filename="'.$name.'"'."\n" 
				. 'Content-Type: '.$type."\n"
				. "\n"
				. $bin_content."\n"
				. '--'.$boundary;
			}
			$body .= "--";
			
			$headers = array('Content-type: multipart/form-data; boundary="'.$boundary.'"', 'Content-length: '.strlen($body));
			
			$data["settings"]["http_header"] = isset($data["settings"]["http_header"]) ? $data["settings"]["http_header"] : array();
			$data["settings"]["http_header"] = array_merge($headers, $data["settings"]["http_header"]);//the $headers variable needs to be the first one!
		}
		else if (isset($data["post"]) && is_array($data["post"]) && empty($settings["do_not_prepare_post_data"]))
			$body = http_build_query($data["post"]);
		else
			$body = isset($data["post"]) ? $data["post"] : null;
		
		curl_setopt($conn, CURLOPT_POSTFIELDS, $body);
	}
	
	private function getFileContent($file_path) {
		$buffer = "";
	
		$handle = file_exists($file_path) ? fopen($file_path, "r") : false;
		if ($handle) {
			while (!feof($handle)) 
				$buffer .= fgets($handle);
			fclose($handle);
		}
		
		return $buffer;
	}
	
	private function preparePostData($post_str, $settings = null) {
		if ($settings && !empty($settings["do_not_prepare_post_data"]))
			return $post_str;
		else if (is_array($post_str)) 
			return $post_str;
		else if ($post_str) {
			$post = array();
			$explode = explode("?", $post_str);
			$explode = explode("&", $explode[count($explode) - 1]);
			
			$t = count($explode);
			for ($i = 0; $i < $t; $i++) {
				$sub_explode = explode("=", $explode[$i]);
				
				if (strlen(trim($sub_explode[0])) > 0)
					$post[trim($sub_explode[0])] = isset($sub_explode[1]) ? $sub_explode[1] : null;
			}
			
			return $post;
		}
	}
	
	private function prepareGetData($get, $settings = null) {
		if ($settings && !empty($settings["do_not_prepare_get_data"]))
			return $post_str;
		else if (is_array($get)) {
			$get_str = "";
			foreach ($get as $key => $value)
				$get_str .= "&{$key}={$value}";
			
			return $get_str;
		}
		else if ($get) {
			$explode = explode("?", $get);
			$get_inc = $explode[count($explode) - 1];
			
			return $get_inc ? "&" . $get_inc : "";
		}
	}
	
	private function prepareCookiesData($cookies, $settings = null) {
		if ($settings && !empty($settings["do_not_prepare_cookies_data"]))
			return $post_str;
		else if (is_array($cookies)) {
			$cookie_str = "";
			foreach ($cookies as $key => $value) 
				$cookie_str .= "{$key}={$value}; ";
			
			return $cookie_str;
		}
		
		return $cookies;
	}
	
	private function prepareSettingsData($settings) {
		if (is_array($settings)) {
			if (!empty($settings["put"]) && !is_bool($settings["put"]) && $settings["put"] !== 0 && $settings["put"] !== 1)
				unset($settings["put"]);
			
			if (!empty($settings["header"]) && !is_bool($settings["header"]) && $settings["header"] !== 0 && $settings["header"] !== 1)
				unset($settings["header"]);
			
			if (!empty($settings["connection_timeout"]) && !is_numeric($settings["connection_timeout"]))
				unset($settings["connection_timeout"]);
			
			if (!empty($settings["no_body"]) && !is_bool($settings["no_body"]) && $settings["no_body"] !== 0 && $settings["no_body"] !== 1)
				unset($settings["no_body"]);
			
			if (!empty($settings["http_header"]) && !is_array($settings["http_header"]))
				$settings["http_header"] = explode("\n", str_replace(array("\r\n"), "\n", $settings["http_header"]));
			
			if (!empty($settings["follow_location"]) && !is_bool($settings["follow_location"]) && $settings["follow_location"] !== 0 && $settings["follow_location"] !== 1)
				unset($settings["follow_location"]);
			
			if (!empty($settings["http_auth"])) {
				$auth = $settings["http_auth"];
				
				switch (strtolower($auth)) {
					case "basic": $auth = CURLAUTH_BASIC; break;
					case "digest": $auth = CURLAUTH_DIGEST; break;
				}
				
				$available_options = array(CURLAUTH_BASIC, CURLAUTH_DIGEST, CURLAUTH_GSSNEGOTIATE, CURLAUTH_NTLM, CURLAUTH_ANY, CURLAUTH_ANYSAFE);
				
				if (!in_array($auth, $available_options))
					unset($settings["http_auth"]);
				else
					$settings["http_auth"] = $auth;
			}
		}
		
		return $settings;
	}
	
	private static function parseHeadersText($header_text) {
		$headers = array();
		$lines = explode("\n", str_replace(array("\r\n"), "\n", $header_text));
		
		foreach ($lines as $i => $line) {
			if ($i === 0)
				$headers['http_code'] = $line;
			else if (trim($line)) {
				$pos = strpos($line, ":");
				
				if ($pos !== false) {
					$key = trim(substr($line, 0, $pos));
					$value = trim(substr($line, $pos + 1));
				}
				else {
					$key = $line;
					$value = null;
				}
				
				if ($key)
					$headers[$key] = $value;
			}
		}
		
		return $headers;
	}
	
	private static function removeBomCharacter($var) {
		return preg_replace('/\\0/', "", $var); //remove null character
	}
	
	public function getData() { 
		return $this->data;
	}
}
?>
