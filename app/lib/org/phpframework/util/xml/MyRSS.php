<?php
include_once get_lib("org.phpframework.util.web.MyCurl");

class MyRSS {
	private $url;
	private $connection_time_out;
	private $content;
	private $multiple;
	
	public function __construct($url, $auto_reload = true, $connection_time_out = 60) {
		$this->connection_time_out = $connection_time_out ? $connection_time_out : 60;
		
		if (is_array($url)) {
			$t = count($url);
			for ($i = 0; $i < $t; $i++) {
				$url[$i] = $this->getConfiguredURL($url[$i]);
			}
			$this->url = $url;
			$this->multiple = true;
		}
		else {
			$this->url = array($this->getConfiguredURL($url));
			$this->multiple = false;
		}
		
		if ($auto_reload) {
			$this->content = $this->getRssContent();
		}
	}	
	
	public function getRssContent() {
		$data = array();
		$current_host = explode(":", $_SERVER["HTTP_HOST"]); //maybe it contains the port
		$current_host = $current_host[0];
		
		foreach ($this->url as $url) {
			$url_host = parse_url($url, PHP_URL_HOST);
			
			$data[] = array(
				"url" => $url, 
				"cookie" => $current_host == $url_host ? $_COOKIE : null,
				"settings" => array(
					"referer" => isset($_SERVER["HTTP_REFERER"]) ? $_SERVER["HTTP_REFERER"] : null,
					"follow_location" => 1, 
					"connection_timeout" => $this->connection_time_out
				)
			);
		}
		//echo "<pre>";print_r($data);die();
		
		$MyCurl = new MyCurl();
		$MyCurl->initMultiple($data);
		$MyCurl->get_contents( $this->multiple ? array("wait" => true) : false );
		$request = $MyCurl->getData();
		//echo "<pre>";print_r($request);die();
		
		if ($this->multiple) {
			$content = array();
			
			$t = $request ? count($request) : 0;
			for ($i = 0; $i < $t; $i++) {
				$content[$i] = isset($request[$i]["content"]) ? $request[$i]["content"] : null;
			}
		}
		else {
			$content = isset($request[0]["content"]) ? $request[0]["content"] : null;
		}
		
		return $content;
	}
	
	public function isRSSURL() {
		$content = $this->content;
		
		if ($this->multiple) {
			$statuses = array();
			
			$keys = array_keys($content);
			$t = count($keys);
			for ($i = 0; $i < $t; $i++) {
				$content_i = $content[ $keys[$i] ];
				
				$status = false;
				if (is_numeric(stripos($content_i,"<rss")) || is_numeric(stripos($content_i,"<feed"))) {
					$xml = simplexml_load_string($content_i);
					$status = $xml ? true : false;
				}
				$statuses[ $keys[$i] ] = $status;
			}
			
			return $statuses;
		}
		else if (is_numeric(stripos($content,"<rss")) || is_numeric(stripos($content,"<feed"))) {
			$xml = simplexml_load_string($content);
			return $xml ? true : false;
		}
		
		return false;
	}
	
	public function getRSSRequestData() {
		$data = array();
		$data["is_rss_url"] = $this->isRSSURL();
		
		if ($this->multiple) {
			$keys = array_keys($data["is_rss_url"]);
			
			$t = count($keys);
			for ($i = 0; $i < $t; $i++) {
				if (!empty($data["is_rss_url"][ $keys[$i] ])) {
					$data["content"][ $keys[$i] ] = $this->content[ $keys[$i] ];
				}
			}
		}
		else {
			if (!empty($data["is_rss_url"])) {
				$data["content"] = $this->content;
			}
		}
		
		return $data;
	}
	
	public function getRSSObject($simple = true) {
		$data = $this->getRSSRequestData();
		$content = isset($data["content"]) ? $data["content"] : null;
		
		if ($this->multiple) {
			$obj = array();
			
			$t = count($content);
			for ($i = 0; $i < $t; $i++) {
				if($content[$i])
					$obj[] = $this->convertToXmlToArray($content[$i], $simple);
			}
		}
		else {
			$obj = $this->convertToXmlToArray($content, $simple);
		}
		
		return $obj;
	}
	
	public function convertToXmlToArray($xml, $simple = null) {
		$MyXML = new MyXML($xml);
		$arr = $MyXML->toArray();
		//echo "<pre>$arr:".print_r($arr, 1)."<pre><br/>";die();
		
		if ($simple) {
			$simple_options = is_array($simple) ? $simple : array("convert_attributes_to_childs" => true);
			$arr = MyXML::complexArrayToBasicArray($arr, $simple_options);
		}
		
		return $arr;
	}
	
	private function getConfiguredURL(&$url) {
		if (!is_numeric(strpos($url, "://"))) {
			return "http://" . $url;
		}
		return $url;
	}
}
?>
