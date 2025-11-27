<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 *
 * Original Bloxtor Repo: https://github.com/a19836/bloxtor
 *
 * YOU ARE NOT AUTHORIZED TO MODIFY OR REMOVE ANY PART OF THIS NOTICE!
 */

include_once get_lib("org.phpframework.util.text.TextSanitizer");

class CssAndJSFilesOptimizer {
	const CURL_CONNECTION_TIMEOUT_SECS = 30; //30 seconds
	const CURL_MAX_HOST_CHUNK_REQUESTS = 10; //10 requests at once
	
	private $webroot_cache_folder_path = null;
	private $webroot_cache_folder_url = null;
	private $settings = null;
	
	public function __construct($webroot_cache_folder_path, $webroot_cache_folder_url, $settings = null) {
		$this->webroot_cache_folder_path = $webroot_cache_folder_path;
		$this->webroot_cache_folder_url = $webroot_cache_folder_url;
		$this->settings = $settings ? $settings : array();
		
		if (!empty($this->settings["urls_prefix"]) && !is_array($this->settings["urls_prefix"]))
			$this->settings["urls_prefix"] = array($this->settings["urls_prefix"]);
		
		if (!empty($this->settings["url_strings_to_avoid"]) && !is_array($this->settings["url_strings_to_avoid"]))
			$this->settings["url_strings_to_avoid"] = array($this->settings["url_strings_to_avoid"]);
	}
	
	/*
	 * There are the following public methods:
	 * - getCssAndJSFilesHtml
	 * - prepareHtmlWithOptimizedCssAndJSFiles
	 * - prepareHtmlWithOptimizedCssAndJSFilesViaDomHandler: DEPRECATED. See bellow why...
	 */
	
	/* get Css And JS Files Html */
	
	//based on a array of css and js files, group them to only 1 file and return the correspondent html with that file. It minimizes the css and js contents too...
	public function getCssAndJSFilesHtml($css_files, $js_files) {
		$unique_css = $unique_js = array();
		
		if ($css_files)
			foreach ($css_files as $path => $url) {
				$path = trim($path);
				
				if ($path && file_exists($path)) {
					$path = realpath($path);
					$url = self::getUrlConfigured($url);
					$unique_css[$path] = $url;
				}
			}
		
		if ($js_files)
			foreach ($js_files as $path => $url) {
				$path = trim($path);
				
				if ($path && file_exists($path)) {
					$path = realpath($path);
					$url = self::getUrlConfigured($url);
					$unique_js[$path] = $url;
				}
			}
		
		$html = "";
		
		//prepare cached files
		if ($this->webroot_cache_folder_path && $this->webroot_cache_folder_url) {
			//prepare all css file
			if ($unique_css) {
				$all_file_id = md5(json_encode($unique_css));
				$all_file_path = $this->webroot_cache_folder_path . "/$all_file_id.css";
				$status = true;
				
				if (!file_exists($all_file_path)) {
					$files_contents = "";
					
					foreach ($unique_css as $path => $url) {
						$file_contents = file_get_contents($path);
						$file_contents = self::optimizeCss($file_contents, $url);
						
						$files_contents .= $file_contents . "\n";
					}
					
					$files_contents = trim($files_contents);
					
					if ($files_contents) {
						if (!is_dir($this->webroot_cache_folder_path))
							mkdir($this->webroot_cache_folder_path, 0755, true);
						
						$status = file_put_contents($all_file_path, $files_contents) !== false;
					}
				}
				
				if (file_exists($all_file_path)) {
					$html .= '<link rel="stylesheet" href="' . $this->webroot_cache_folder_url . "/$all_file_id.css" . '" type="text/css" />';
					$unique_css = null;
				}
			}
			
			//prepare all js file
			if ($unique_js) {
				$all_file_id = md5(json_encode($unique_js));
				$all_file_path = $this->webroot_cache_folder_path . "/$all_file_id.js";
				$urls_file_path = $this->webroot_cache_folder_path . "/$all_file_id.urls";
				$status = true;
				
				if (!file_exists($all_file_path) || !file_exists($urls_file_path)) {
					$files_contents = "";
					$urls_to_cache = array();
					
					foreach ($unique_js as $path => $url) {
						$file_contents = file_get_contents($path);
						
						if (self::isValidJS($file_contents)) {
							$file_contents = self::optimizeJS($file_contents, $url);
							
							$files_contents .= $file_contents . "\n";
							$urls_to_cache[] = $url;
						}
					}
					
					$files_contents = trim($files_contents);
					
					if ($files_contents) {
						if (!is_dir($this->webroot_cache_folder_path))
							mkdir($this->webroot_cache_folder_path, 0755, true);
						
						$status = file_put_contents($all_file_path, $files_contents) !== false;
						
						if ($status)
							$status = file_put_contents($urls_file_path, json_encode($urls_to_cache)) !== false;
					}
				}
				else
					$urls_to_cache = json_decode(file_get_contents($urls_file_path), true);
				
				if (file_exists($all_file_path)) {
					$html .= '<script language="javascript" type="text/javascript" src="' . $this->webroot_cache_folder_url . "/$all_file_id.js" . '"></script>';
					$unique_js = array_diff($unique_js, $urls_to_cache);
				}
			}
		}
		
		//prepare css
		if ($unique_css) {
			$css_imports = array("");
			$idx = 0;
			$c = 0;
			
			foreach ($unique_css as $url) 
				if (trim($url)) {
					$css_imports[$idx] .= '@import url("' . $url . '");' . "\n";
					
					if ($c > 30)
						$idx++;
					
					$c++;
				}
			
			$t = count($css_imports);
			for ($i = 0; $i < $t; $i++)
				if ($css_imports[$i])
					$html .= '<style type="text/css">' . $css_imports[$i] . '</style>' . "\n";
		}
		
		//prepare js
		if ($unique_js)
			foreach ($unique_js as $url)
				if (trim($url))
					$html .= '<script language="javascript" type="text/javascript" src="' . $url . '"></script>' . "\n";
		
		return $html;
	}
	
	/* Html With Optimized Css And JS Files - parses with strpos */
	
	//parse html and get all styles and scripts. then check if they are call local files and if they are, group them into 1 file.
	public function prepareHtmlWithOptimizedCssAndJSFiles($html, $cache_html = true) {
		if ($html) {
			if ($cache_html && $this->webroot_cache_folder_path) {
				$file_id = md5($html);
				$file_path = $this->webroot_cache_folder_path . "/$file_id.html";
				
				if (file_exists($file_path)) {
					$new_html = file_get_contents($file_path);
					
					if (trim($new_html))
						return $new_html;
				}
				
				$html = $this->parseHtmlAndOptimizedCssAndJSFiles($html);
				
				//prepare main dirname
				if (!is_dir($this->webroot_cache_folder_path))
					mkdir($this->webroot_cache_folder_path, 0755, true);
				
				file_put_contents($file_path, $html);
			}
			else
				$html = $this->parseHtmlAndOptimizedCssAndJSFiles($html);
		}
		
		//echo $html;die();
		
		return $html;
	}
	
	private function parseHtmlAndOptimizedCssAndJSFiles($html) {
		//die("asd");
		$l = strlen($html);
		$odq = $osq = $open_tag = $is_link = $is_style = $is_script = $find_closed_tag = false;
		$outter_start_pos = $outter_end_pos = $inner_start_pos = $inner_end_pos = $close_start_pos = $close_end_pos = null;
		
		$css_items = $js_items = $items_to_delete = array();
		$new_html = $html;
		
		if (is_numeric($html))
			$html = (string)$html; //bc of php > 7.4 if we use $var[$i] gives an warning
		
		for ($i = 0; $i < $l; $i++) {
			$char = $html[$i];
			
			if ($char == '"' && !$osq && $open_tag && !TextSanitizer::isCharEscaped($html, $i)) {
				$odq = !$odq;
				
				if ($odq) {
					$pos = strpos($html, '"', $i + 1);
					$i = $pos !== false ? $pos - 1 : $l;
				}
			}
			else if ($char == "'" && !$odq && $open_tag && !TextSanitizer::isCharEscaped($html, $i)) {
				$osq = !$osq;
				
				if ($osq) {
					$pos = strpos($html, "'", $i + 1);
					$i = $pos !== false ? $pos - 1 : $l;
				}
			}
			else if ($char == "<" && !$odq && !$osq && !$open_tag) {
				if ($i + 1 < $l && preg_match("/[a-z]/i", $html[$i + 1]))
					$open_tag = true;
				
				if ($i + 5 < $l && strtolower(substr($html, $i, 5)) == "<link" && preg_match("/(\s|>)/", $html[$i + 5])) { //strlen("<link") == 5
					$outter_start_pos = $i;
					$inner_start_pos = $i + 5;
					$is_link = true;
					
					$i = $inner_start_pos - 1;
				}
				else if ($i + 6 < $l && strtolower(substr($html, $i, 6)) == "<style" && preg_match("/(\s|>)/", $html[$i + 6])) { //strlen("<style") == 6
					$outter_start_pos = $i;
					$inner_start_pos = $i + 6;
					$is_style = true;
					
					$i = $inner_start_pos - 1;
				}
				else if ($i + 7 < $l && strtolower(substr($html, $i, 7)) == "<script" && preg_match("/(\s|>)/", $html[$i + 7])) { //strlen("<script") == 7
					$outter_start_pos = $i;
					$inner_start_pos = $i + 7;
					$is_script = true;
					
					$i = $inner_start_pos - 1;
				}
				else if (substr($html, $i, 4) == "<!--") { //everytime it finds any <script or <link inside of a comment bc it could be only for IE like: <!--[if lt IE 9]>  ...  <![endif]-->
					$pos = strpos($html, "\n", $i);
					$pos = $pos !== false ? $pos : $l;
					$line = substr($html, $i, $pos - $i);
					
					if (preg_match("/<!--\s*\[\s*if/i", $line)) {
						$this->optimizedItems($new_html, $css_items, $items_to_delete);
						$this->optimizedItems($new_html, $js_items, $items_to_delete);
						
						$pos = strpos($html, "-->", $i);
						$i = $pos !== false ? $pos + 2 : $l;
					}
				}
			}
			else if ($char == ">" && !$odq && !$osq && !$open_tag && $find_closed_tag && ($is_style || $is_script)) {
				$find_closed_tag = false;
				$close_end_pos = $i;
				
				if ($is_script && empty($code) && count($js_items)) {
					$js_item_idx = count($js_items) - 1;
					$js_item = $js_items[$js_item_idx];
					$js_items[$js_item_idx]["close_end_pos"] = $close_end_pos;
					$js_items[$js_item_idx]["html"] = substr($html, $js_item["outter_start_pos"], ($close_end_pos + 1) - $js_item["outter_start_pos"]);
				}
				
				$is_style = $is_script = false;
			}
			else if ($char == ">" && !$odq && !$osq && $open_tag) {
				$open_tag = false;
				
				if ($is_link || $is_style || $is_script) {
					$outter_end_pos = $i;
					$inner_end_pos = $i - 1;
					
					if (isset($html[$inner_end_pos]) && $html[$inner_end_pos] == "/")
						$inner_end_pos--;
					
					$attrs_html = substr($html, $inner_start_pos, ($inner_end_pos + 1) - $inner_start_pos);
					
					if ($is_link) {
						$is_link = false;
						
						$url = self::getHtmlAttribute($attrs_html, "href");
						$valid = $this->isValidUrl($url);
						
						//check if css is media=screen and not media=print
						if ($valid) {
							$valid = preg_match("/\.css$/i", $url); //be sure that the url ends in .css, bc it could be an image with the rel=icon
							
							if ($valid) {
								$media = self::getHtmlAttribute($attrs_html, "media");
								$rel = self::getHtmlAttribute($attrs_html, "rel");
								$type = self::getHtmlAttribute($attrs_html, "type");
								
								$valid = !trim($media) || preg_match("/(screen|all)/i", $media);
								$valid = $valid && (!trim($rel) || strtolower($rel) == "stylesheet"); //be sure that is a real css file
								$valid = $valid && (!trim($type) || strtolower($type) == "text/css"); //be sure that is a real css file
								
								//check if css is charset=utf-8
								if ($valid) {
									$charset = self::getHtmlAttribute($attrs_html, "charset");
									$valid = !trim($charset) || preg_match("/utf8|utf-8/i", $charset);
								}
							}
						}
						
						if ($valid)
							$css_items[] = array(
								"type" => "link",
								"url" => $url,
								"outter_start_pos" => $outter_start_pos,
								"outter_end_pos" => $outter_end_pos,
								"html" => substr($html, $outter_start_pos, ($outter_end_pos + 1) - $outter_start_pos),
							);
						else
							$this->optimizedItems($new_html, $css_items, $items_to_delete);
					}
					else if ($is_style) {
						$pos = stripos($html, "</style", $outter_end_pos + 1);
						$close_start_pos = $pos ? $pos : $l;
						$i = $pos ? $pos + 7 - 1 : $l; //strlen("</style") == 7
						
						$find_closed_tag = true;
						
						$code = trim(substr($html, $outter_end_pos + 1, $close_start_pos - ($outter_end_pos + 1)));
						
						if ($code)
							$this->optimizedItems($new_html, $css_items, $items_to_delete);
					}
					else if ($is_script) {
						$pos = stripos($html, "</script", $outter_end_pos + 1);
						$close_start_pos = $pos ? $pos : $l;
						$i = $pos ? $pos + 8 - 1 : $l; //strlen("</script") == 8
						
						$find_closed_tag = true;
						
						$code = trim(substr($html, $outter_end_pos + 1, $close_start_pos - ($outter_end_pos + 1)));
						
						if ($code)
							$this->optimizedItems($new_html, $js_items, $items_to_delete);
						else {
							$url = self::getHtmlAttribute($attrs_html, "src");
							$valid = $this->isValidUrl($url);
							
							if ($valid)
								$js_items[] = array(
									"type" => "script",
									"url" => $url,
									"outter_start_pos" => $outter_start_pos,
									"outter_end_pos" => $outter_end_pos,
									"close_start_pos" => $close_start_pos,
									"close_end_pos" => null,
									"html" => null,
								);
							else
								$this->optimizedItems($new_html, $js_items, $items_to_delete);
						}
					}
				}
			}
		}
		
		//optimize nodes
		$this->optimizedItems($new_html, $css_items, $items_to_delete);
		$this->optimizedItems($new_html, $js_items, $items_to_delete);
		
		//remove nodes
		if ($items_to_delete)
			foreach ($items_to_delete as $item)
				if (!empty($item["html"]))
					$new_html = str_replace($item["html"], "", $new_html);
		
		return $new_html;
	}
	
	private function getHtmlAttribute($attrs_html, $attr_name) {
		$l = strlen($attrs_html);
		$odq = $osq = $open_attr = false;
		$start_pos = $end_pos = null;
		$al = strlen($attr_name);
		$fc = $al ? strtolower($attr_name[0]) : "";
		
		if (is_numeric($attrs_html))
			$attrs_html = (string)$attrs_html; //bc of php > 7.4 if we use $var[$i] gives an warning
		
		for ($i = 0; $i < $l; $i++) {
			$char = $attrs_html[$i];
			
			if ($char == '"' && !$osq && !TextSanitizer::isCharEscaped($attrs_html, $i)) {
				$odq = !$odq;
				
				if ($odq) {
					if ($open_attr)
						$start_pos = $i + 1;
					
					$pos = strpos($attrs_html, '"', $i + 1);
					$i = $pos !== false ? $pos - 1 : $l;
				}
				else if ($open_attr && !$odq) {
					$end_pos = $i - 1;
					break;
				}
			}
			else if ($char == "'" && !$odq && !TextSanitizer::isCharEscaped($attrs_html, $i)) {
				$osq = !$osq;
				
				if ($osq) {
					if ($open_attr)
						$start_pos = $i + 1;
					
					$pos = strpos($attrs_html, "'", $i + 1);
					$i = $pos !== false ? $pos - 1 : $l;
				}
				else if ($open_attr && !$osq){
					$end_pos = $i - 1;
					break;
				}
			}
			else if ($char == $fc && !$odq && !$osq && strtolower(substr($attrs_html, $i, $al)) == $attr_name && isset($attrs_html[$i + $al]) && preg_match("/(\s|=)/", $attrs_html[$i + $al]))
				$open_attr = true;
		}
		
		if (is_numeric($start_pos)) {
			$end_pos = is_numeric($end_pos) ? $end_pos + 1 : $l;
			
			return substr($attrs_html, $start_pos, $end_pos - $start_pos);
		}
		
		return "";
	}
	
	//groups all the files into 1 file
	private function optimizedItems(&$html, &$items, &$items_to_delete) {
		$l1 = count($items);
		//print_r($items);
		
		if ($l1 > 1) {
			//group all $items into only 1 file and return new url for that file.
			$groups = $this->getCachedGroupItemsUrls($items);
			
			if ($groups) {
				$extension = isset($items[0]["type"]) && $items[0]["type"] == "link" ? "css" : "js";
				
				foreach ($groups as $group) {
					$valid = isset($group["valid"]) ? $group["valid"] : null;
					
					if ($valid) {
						$group_items = isset($group["items"]) ? $group["items"] : null;
						$urls = isset($group["urls"]) ? $group["urls"] : null;
						$all_files_url = isset($group["all_files_url"]) ? $group["all_files_url"] : null;
						
						//create new item
						$new_item = $extension == "css" ? '<link rel="stylesheet" type="text/css" href="' . $all_files_url . '" />' : '<script language="javascript" type="text/javascript" src="' . $all_files_url . '"></script>';
						
						//insert new item before the first_item
						foreach ($group_items as $group_item)
							if (!empty($group_item["html"])) {
								$pos = strpos($html, $group_item["html"]);
								
								if ($pos !== false) {
									$html = substr($html, 0, $pos) . $new_item . substr($html, $pos);
									$items_to_delete = array_merge($items_to_delete, $group_items);
								}
								
								break;
							}
					}
				}
			}
		}
		
		//resets items
		$items = array();
	}
	
	private function getCachedGroupItemsUrls($items) {
		if ($this->webroot_cache_folder_path) {
			$file_id = md5(json_encode($items));
			$file_path = $this->webroot_cache_folder_path . "/$file_id.groups";
			
			if (file_exists($file_path)) {
				$cached_groups = json_decode(file_get_contents($file_path), true);
				
				if ($cached_groups)
					return $cached_groups;
			}
			
			$groups = $this->groupItemsUrls($items);
			
			//prepare main dirname
			if (!is_dir($this->webroot_cache_folder_path))
				mkdir($this->webroot_cache_folder_path, 0755, true);
			
			file_put_contents($file_path, json_encode($groups));
		}
		else
			$groups = $this->groupItemsUrls($items);
		
		return $groups;
	}
	
	//groups all $items into only 1 file and return new url for that file.
	private function groupItemsUrls($items) {
		$item_groups = array();
		
		if ($items) {
			//get $urls from items
			$extension = isset($items[0]["type"]) && $items[0]["type"] == "link" ? "css" : "js";
			$urls = array();
			
			foreach ($items as $idx => $item) {
				$url = isset($item["url"]) ? trim($item["url"]) : "";
				
				if ($url) 
					$urls[$idx] = $url;
			}
			
			//prepare main dirname
			if (!is_dir($this->webroot_cache_folder_path))
				mkdir($this->webroot_cache_folder_path, 0755, true);
			
			//get urls content
			$data = self::getURLsContent($urls, self::CURL_CONNECTION_TIMEOUT_SECS, self::CURL_MAX_HOST_CHUNK_REQUESTS); 
			$data_by_url = array();
			$valid_urls = array();
			$valid_items = array();
			$files_contents = "";
			
			//group items' urls 
			if ($data)
				foreach ($data as $request_data) {
					$url = isset($request_data["url"]) ? $request_data["url"] : null;
					$data_by_url[$url] = $request_data;
				}
			
			foreach ($items as $idx => $item) {
				$url = $urls[$idx];
				$valid = false;
				
				if ($url) {
					$request_data = isset($data_by_url[$url]) ? $data_by_url[$url] : null;
					
					if ($request_data && empty($request_data["error"]) && isset($request_data["info"]["http_code"]) && $request_data["info"]["http_code"] == 200) {
						$content = isset($request_data["content"]) ? trim($request_data["content"]) : "";
						$valid = !empty($content);
						
						$file_contents = "/*** File: $url ***/";
						$file_contents .= $content;
						
						if ($extension == "css")
							$file_contents = self::optimizeCss($file_contents, $url);
						else {
							$valid = self::isValidJS($file_contents);
							
							if ($valid) 
								$file_contents = self::optimizeJS($file_contents, $url);
						}
					}
				}
				
				if ($valid) {
					$files_contents .= $file_contents . "\n";
					
					$valid_urls[] = $url;
					$valid_items[] = $item;
				}
				else if ($files_contents) {
					$all_files_id = md5(json_encode($valid_urls));
					$all_files_path = $this->webroot_cache_folder_path . "/" . $all_files_id . ".$extension";
					$status = file_put_contents($all_files_path, $files_contents) !== false;
					
					if ($status) {
						$item_groups[] = array(
							"valid" => true,
							"items" => $valid_items,
							"urls" => $valid_urls,
							"all_files_url" => $this->webroot_cache_folder_url . "/$all_files_id.$extension",
						);
						$item_groups[] = array(
							"valid" => false,
							"items" => array($item),
							"urls" => array($url),
						);
						
						$files_contents = "";
						$valid_urls = array();
						$valid_items = array();
					}
					else 
						return false;
				}
			}
			
			if ($files_contents) {
				$all_files_id = md5(json_encode($valid_urls));
				$all_files_path = $this->webroot_cache_folder_path . "/" . $all_files_id . ".$extension";
				$status = file_put_contents($all_files_path, $files_contents) !== false;
				
				if ($status) {
					$item_groups[] = array(
						"valid" => true,
						"items" => $valid_items,
						"urls" => $valid_urls,
						"all_files_url" => $this->webroot_cache_folder_url . "/$all_files_id.$extension",
					);
				}
				else 
					return false;
			}
		}	
		
		return $item_groups;
	}
	
	/* Html With Optimized Css And JS Files Via DomHandler */
	
	//This method has a problem, thi is, if there is a javascript with the following code: "something".replace(/</g, "foo"), the returned html will be "something".replace(/, "foo"), which will break the javascript giving an error. TRY NOT TO USE THIS METHOD!!!
	//parse html and get all styles and scripts. then check if they are call local files and if they are, group them into 1 file.
	public function prepareHtmlWithOptimizedCssAndJSFilesViaDomHandler($html) {
		$encoding = !empty($this->settings["encoding"]) ? $this->settings["encoding"] : "utf-8";
		
		//TODO: cache
		
		$HtmlDomHandler = new HtmlDomHandler($html, $encoding);
		
		if ($HtmlDomHandler->isHTML()) {
			$DOMDocument = $HtmlDomHandler->getDOMDocument();
			
			$nodes = $DOMDocument->childNodes;
			self::parseNodesAndOptimizedCssAndJSFiles($DOMDocument, $nodes);
			
			$html = $HtmlDomHandler->getHtml();
			//echo $html; die();
		}
		
		return $html;
	}
	
	private function parseNodesAndOptimizedCssAndJSFiles($DOMDocument, $nodes, &$css_nodes = array(), &$js_nodes = array(), $depth = 0) {
		if ($nodes) {
			$l = $nodes->count();
			$nodes_to_delete = array();
			
			for ($i = 0; $i < $l; $i++) {
				$node = $nodes->item($i);
				$node_name = strtolower($node->nodeName);
				
				if ($node_name == "link") {
					$url = trim($node->getAttribute("href"));
					$valid = $this->isValidUrl($url);
					
					if ($valid) 
						$css_nodes[] = $node;
					else
						$this->optimizedCssNodes($DOMDocument, $css_nodes, $nodes_to_delete);
				}
				else if ($node_name == "style") { //everytime it finds a style node with css code, it groups all the files detected until here, add the new file before the first detected node and them delete the other nodes.
					$code = trim($node->textContent);
					
					if ($code)
						$this->optimizedCssNodes($DOMDocument, $css_nodes, $nodes_to_delete);
				}
				else if ($node_name == "script") { //everytime it finds a script node with js code, it groups all the files detected until here, add the new file before the first detected node and them delete the other nodes.
					$code = trim($node->textContent);
					
					if ($code)
						$this->optimizedJSNodes($DOMDocument, $js_nodes, $nodes_to_delete);
					else {
						$url = trim($node->getAttribute("src"));
						$valid = $this->isValidUrl($url);
						
						if ($valid) 
							$js_nodes[] = $node;
						else
							$this->optimizedJSNodes($DOMDocument, $js_nodes, $nodes_to_delete);
					}
				}
				else if ($node->hasChildNodes())
					$this->parseNodesAndOptimizedCssAndJSFiles($DOMDocument, $node->childNodes, $css_nodes, $js_nodes, $depth + 1);
			}
			
			//optimize nodes
			if ($depth == 0) {
				$this->optimizedCssNodes($DOMDocument, $css_nodes, $nodes_to_delete);
				$this->optimizedJSNodes($DOMDocument, $js_nodes, $nodes_to_delete);
			}
			
			//remove nodes
			if ($nodes_to_delete)
				foreach ($nodes_to_delete as $node) {
					$parent = $node->parentNode;
					$parent->removeChild($node);
				}
		}
	}
	
	//groups all the files into 1 file
	private function optimizedCssNodes($DOMDocument, &$css_nodes, &$nodes_to_delete) {
		$l1 = count($css_nodes);
		
		if ($l1 > 1) {
			//group all $css_nodes into only 1 file and return new url for that file.
			$new_url = $this->groupNodesUrls($css_nodes, $nodes_to_delete);
			
			if ($new_url) {
				//create new node
				$new_node = $DOMDocument->createElement("link");
				$new_node->setAttribute("rel", "stylesheet");
				$new_node->setAttribute("type", "text/css");
				$new_node->setAttribute("href", $new_url);
				
				//insert new node before the first_node
				$first_node = isset($css_nodes[0]) ? $css_nodes[0] : null;
				$parent = $first_node->parentNode;
				$parent->insertBefore($new_node, $first_node);
			}
		}
		
		//resets nodes
		$css_nodes = array();
	}
	
	//groups all the files into 1 file
	private function optimizedJSNodes($DOMDocument, &$js_nodes, &$nodes_to_delete) {
		$l1 = count($js_nodes);
		
		if ($l1 > 1) {
			//group all $js_nodes into only 1 file and return new url for that file.
			$new_url = $this->groupNodesUrls($js_nodes, $nodes_to_delete);
			
			if ($new_url) {
				//create new node
				$new_node = $DOMDocument->createElement("script");
				$new_node->setAttribute("language", "javascript");
				$new_node->setAttribute("type", "text/javascript");
				$new_node->setAttribute("src", $new_url);
				
				//insert new node before the first_node
				$first_node = isset($js_nodes[0]) ? $js_nodes[0] : null;
				$parent = $first_node->parentNode;
				$parent->insertBefore($new_node, $first_node);
			}
		}
		
		//resets nodes
		$js_nodes = array();
	}
	
	//groups all $nodes into only 1 file and return new url for that file.
	//$nodes is an array
	private function groupNodesUrls($nodes, &$nodes_to_delete) {
		if ($nodes) {
			$extension = isset($nodes[0]) && strtolower($nodes[0]->nodeName) == "link" ? "css" : "js";
			$urls = array();
			
			foreach ($nodes as $idx => $node) {
				$url = trim($node->getAttribute($extension == "css" ? "href" : "src"));
				
				if ($url) 
					$urls[$idx] = $url;
			}
			
			$all_file_id = md5(json_encode($urls));
			$all_file_path = $this->webroot_cache_folder_path . "/$all_file_id.$extension";
			$urls_file_path = $this->webroot_cache_folder_path . "/$all_file_id.urls";
			
			if (!file_exists($all_file_path) || !file_exists($urls_file_path)) {
				$data = self::getURLsContent($urls, self::CURL_CONNECTION_TIMEOUT_SECS, self::CURL_MAX_HOST_CHUNK_REQUESTS);
				$data_by_url = array();
				$success_urls = array();
				
				if ($data)
					foreach ($data as $request_data) {
						$url = isset($request_data["url"]) ? $request_data["url"] : null;
						$data_by_url[$url] = $request_data;
					}
				
				$files_contents = "";
				$requests_nodes_to_delete = array();
				
				foreach ($nodes as $idx => $node) {
					$url = $urls[$idx];
					
					if ($url) {
						$request_data = $data_by_url[$url];
						
						if ($request_data && empty($request_data["error"]) && isset($request_data["info"]["http_code"]) && $request_data["info"]["http_code"] == 200) {
							$content = isset($request_data["content"]) ? trim($request_data["content"]) : "";
							$valid = !empty($content);
							
							$file_contents = "/*** File: $url ***/";
							$file_contents .= $content;
							
							if ($extension == "css") 
								$file_contents = self::optimizeCss($file_contents, $url);
							else {
								$valid = self::isValidJS($file_contents);
								
								if ($valid)
									$file_contents = self::optimizeJS($file_contents, $url);
							}
							
							if ($valid) {
								$files_contents .= $file_contents . "\n";
								
								$success_urls[] = $url;
								$requests_nodes_to_delete[] = $node;
							}
						}
					}
				}
				
				$files_contents = trim($files_contents);
				
				if ($files_contents) {
					if (!is_dir(dirname($all_file_path)))
						mkdir(dirname($all_file_path), 0755, true);
					
					$status = file_put_contents($all_file_path, $files_contents) !== false;
					
					if ($status) {
						//saves success_urls to $urls_file_path
						$status = file_put_contents($urls_file_path, json_encode($success_urls)) !== false;
						
						if ($status) {
							$nodes_to_delete = array_merge($nodes_to_delete, $requests_nodes_to_delete);
							
							return $this->webroot_cache_folder_url . "/$all_file_id.$extension";
						}
					}
				}
			}
			else {
				//update nodes to delete
				$cached_urls = json_decode(file_get_contents($urls_file_path), true);
				
				foreach ($nodes as $node) {
					$url = trim($node->getAttribute($extension == "css" ? "href" : "src"));
					
					if ($url && in_array($url, $cached_urls))
						$nodes_to_delete[] = $node;
				}
				
				return $this->webroot_cache_folder_url . "/$all_file_id.$extension";
			}
		}
	}
	
	/* UTILS */
	
	private static function getURLsContent($urls, $connection_timeout, $max_host_chunk_requests) { //$connection_timeout is in seconds
		$data = array();
		$repeated_urls = array();
		
		$current_host = isset($_SERVER["HTTP_HOST"]) ? explode(":", $_SERVER["HTTP_HOST"]) : null; //maybe it contains the port
		$current_host = isset($current_host[0]) ? $current_host[0] : null;
		
		foreach ($urls as $url) 
			if (!in_array($url, $repeated_urls)) {
				$repeated_urls[] = $url;
				$url_host = parse_url($url, PHP_URL_HOST);
				
				$settings = array(
					"url" => $url,  
					"cookie" => $current_host == $url_host ? $_COOKIE : null,
					"settings" => array(
						"referer" => isset($_SERVER["HTTP_REFERER"]) ? $_SERVER["HTTP_REFERER"] : null,
						"follow_location" => 1,
						"connection_timeout" => $connection_timeout,
					)
				);
				
				if (!empty($_SERVER["AUTH_TYPE"]) && !empty($_SERVER["PHP_AUTH_USER"])) {
					$settings["settings"]["http_auth"] = $_SERVER["AUTH_TYPE"];
					$settings["settings"]["user_pwd"] = $_SERVER["PHP_AUTH_USER"] . ":" . (isset($_SERVER["PHP_AUTH_PW"]) ? $_SERVER["PHP_AUTH_PW"] : null);
				}
				
				$data[] = $settings;
			}
		
		$MyCurl = new MyCurl();
		$MyCurl->initMultiple($data);
		$MyCurl->get_contents(array("wait" => true, "max_host_chunk_requests" => $max_host_chunk_requests));
		$data = $MyCurl->getData();
			
		return $data;
	}
	
	private function isValidUrl($url) {
		$urls_prefix = isset($this->settings["urls_prefix"]) ? $this->settings["urls_prefix"] : null;
		
		if ($url && (stripos($url, "http://") === 0 || stripos($url, "https://") === 0) && self::hasUrlPrefixes($url, $urls_prefix)) {
			if (!empty($this->settings["url_strings_to_avoid"]))
				foreach ($this->settings["url_strings_to_avoid"] as $str) 
					if (stripos($url, $str) !== false)
						return false;
			
			return true;
		}
		
		return false;
	}
	
	//checks if the $url contains any of the prefixes in the $urls_prefix
	private static function hasUrlPrefixes($url, $urls_prefix) {
		if ($urls_prefix && count($urls_prefix) > 0) {
			$url = trim($url);
			$l = count($urls_prefix);
			
			for ($i = 0; $i < $l; $i++) {
				$up = trim($urls_prefix[$i]);
				
				if (!$up || substr($url, 0, strlen($up)) == $up)
					return true;
			}
			
			return false;
		}
		
		return true;
	}
	
	//parse css and replace all "url(" that are not "url/data:" with the right url..., but only relative urls
	private static function updateCssContentsUrlsWithFullUrl($contents, $file_url) {
		$folder_url = self::getUrlDirname($file_url);
		//echo "folder_url:$folder_url\n";
		
		//preparing the cases: url("relative path") to url("http://full path")
		$pos = -1;
		$keyword_length = strlen("url");
		
		if (is_numeric($contents))
			$contents = (string)$contents; //bc of php > 7.4 if we use $var[$i] gives an warning
		
		do {
			$pos = stripos($contents, "url", $pos + 1);
			
			if ($pos > 0) {
				$prev_char = $contents[$pos - 1];
				$valid = $prev_char == ":" || $prev_char == " " || $prev_char == ",";
				
				if ($valid) {
					if (preg_match("/^\s*\(/", substr($contents, $pos + $keyword_length), $matches, PREG_OFFSET_CAPTURE)) {
						$start_pos = $matches[0][1] + $pos + $keyword_length;
						$end_pos = null;
						$l = strlen($contents);
						$odq = $osq = false;
						
						for ($i = $start_pos + 1; $i < $l; $i++) {
							$char = $contents[$i];
							
							if ($char == '"' && !$osq && !TextSanitizer::isCharEscaped($contents, $i))
								$odq = !$odq;
							else if ($char == "'" && !$odq && !TextSanitizer::isCharEscaped($contents, $i))
								$osq = !$osq;
							else if ($char == ")" && !$odq && !$osq) {
								$end_pos = $i;
								break;
							}
						}
						
						if ($end_pos) {
							$url = trim(substr($contents, $start_pos + 1, $end_pos - ($start_pos + 1)));
							$valid = $url && !preg_match("/^data\s*:/", $url); //check if url is an inline image
							
							if ($valid) {
								$fc = $url[0];
								$url = $fc == '"' || $fc == "'" ? trim(substr($url, 1, -1)) : $url;
								
								$valid = !preg_match("/^([a-z]+:\/\/|\/\/)/i", $url); //check if path is relative (url:... or src:...)
							}
							
							if ($valid) {
								$new_url = self::getUrlConfigured("$folder_url/$url");
								$new_url = '"' . addcslashes($new_url, '"') . '"';
								//echo "$url : $new_url\n";
								$contents = substr($contents, 0, $start_pos + 1) . $new_url . substr($contents, $end_pos);
								
								$pos = $start_pos + strlen($new_url) + 1; //+1 for the char: ")"
							}
							else
								$pos = $end_pos;
						}
						else
							$pos = $l;
					}
				}
			}
		}
		while($pos > 0);
		
		//preparing the cases: @import "relative path" to @import "http://full path"
		$pos = -1;
		$keyword_length = strlen("@import");
		
		do {
			$pos = stripos($contents, "@import", $pos + 1);
			
			if ($pos !== false) {
				$start_pos = $pos + $keyword_length;
				$end_pos = null;
				$l = strlen($contents);
				$odq = $osq = false;
				
				for ($i = $start_pos; $i < $l; $i++) {
					$char = $contents[$i];
					
					if ($char == '"' && !$osq && !TextSanitizer::isCharEscaped($contents, $i))
						$odq = !$odq;
					else if ($char == "'" && !$odq && !TextSanitizer::isCharEscaped($contents, $i))
						$osq = !$osq;
					else if ($char == ";" && !$odq && !$osq) {
						$end_pos = $i;
						break;
					}
				}
				
				if ($end_pos) {
					$url = trim(substr($contents, $start_pos, $end_pos - $start_pos));
					$valid = $url && !preg_match("/^data\s*:/", $url); //check if url is an inline image
					
					if ($valid) {
						$fc = $url[0];
						$url = $fc == '"' || $fc == "'" ? trim(substr($url, 1, -1)) : $url;
						
						$valid = !preg_match("/^([a-z]+:\/\/|\/\/)/i", $url); //check if path is relative (url:... or src:...)
					}
					
					if ($valid) {
						$new_url = self::getUrlConfigured("$folder_url/$url");
						$new_url = ' "' . addcslashes($new_url, '"') . '"';
						//echo "$url : $new_url\n";
						$contents = substr($contents, 0, $start_pos) . $new_url . substr($contents, $end_pos);
						
						$pos = $start_pos + strlen($new_url);
					}
					else
						$pos = $end_pos;
				}
				else
					$pos = $l;
			}
		}
		while($pos !== false);
		
		return $contents;
	}
	
	private static function getUrlDirname($url) {
		$url = trim($url);
		
		if ($url) {
			//removes everything after ?
			$pos = strpos($url, "?");
			$url = $pos !== false ? substr($url, 0, $pos) : $url;
			
			//removes everything after #
			$pos = strpos($url, "#");
			$url = $pos !== false ? substr($url, 0, $pos) : $url; 
			
			//removes spaces, tabs, end lines...
			$url = trim($url);
			
			//removes last /
			while (substr($url, -1) == "/") 
				$url = substr($url, 0, -1);
			
			//gets minimum offset according with the url's domain
			$offset = strpos($url, "://");
			$offset = $offset !== false ? $offset + 3 : (substr($url, 0, 2) == "//" ? 2 : 0);
			
			//removes last folder
			$pos = strrpos($url, "/", $offset);
			$url = $pos !== false ? substr($url, 0, $pos) : $url;
			
			//removes last / - this deletes repeated /
			while (substr($url, -1) == "/") 
				$url = substr($url, 0, -1);
			
			//remove repeated //
			$path = substr($url, $offset);
			while (strpos($path, "//")) 
				$path = str_replace("//", "/", $path);
			
			//construct new url
			$url = substr($url, 0, $offset) . $path . "/";
		}
		
		return $url;
	}
	
	//check if exists any ../ in the url and returns the new url parsed without ../
	private static function getUrlConfigured($url) {
		$pos = strpos($url, "?");
		$pos = $pos !== false ? $pos : strpos($url, "#");

		if ($pos !== false) {
			$part_1 = substr($url, 0, $pos);
			$part_2 = substr($url, $pos, strlen($url));
		}
		else {
			$part_1 = $url;
			$part_2 = "";
		}
		
		$pos = strpos($part_1, "://");
		if ($pos !== false) {
			$part_0 = substr($part_1, 0, $pos + 3);
			$part_1 = substr($part_1, $pos + 3, strlen($part_1));
		}
		else
			$part_0 = "";

		$part_1 = str_replace(array("//", "/./"), "/", $part_1);

		do {
			$start_pos = strpos($part_1, "/../");
		 
			if ($start_pos !== false) {
				$end_pos = strlen($part_1);
			 
				for ($i = $start_pos - 1; $i >= 0; $i--) {
			        if ($part_1[$i] == "/") {
		                $end_pos = $i;
		                break;
			        }
				}
			 
				$part_1 = substr($part_1, 0, $end_pos + 1) . substr($part_1, $start_pos + 4, strlen($part_1));
			}
		}
		while ($start_pos !== false);

		$url = $part_0 . $part_1 . $part_2;
		
		return $url;
	}
	
	private static function isValidJS($js) {
		$has_local_include = preg_match("/\.require\s*\(/", $js) || strpos($js, "scripts.length") !== false;
		
		return !$has_local_include;
	}
	
	private static function optimizeCss($contents, $url) {
		$contents = trim($contents);
		
		//update urls in css file
		$contents = self::updateCssContentsUrlsWithFullUrl($contents, $url);
		
		//minimize css content
		$already_optimized = preg_match("/.min.css$/i", $url) || preg_match("/.pack.css$/i", $url);
		
		if (!$already_optimized && (strpos($contents, "\n") !== false || strpos($contents, "\r") !== false || strpos($contents, "/*") !== false)) {
			//remove comments and end lines. Don't do anything else bc it could be already encrypted or minimized.
			$options = array(
				"remove_single_line_comments" => false, //single line comments are not allowed in css
				"remove_multiple_lines_comments" => true, 
				"remove_white_spaces" => true
			);
			$contents = self::removeCommentsAndEndLines($contents, $options, "css");
		}
		
		return $contents;
	}
	
	private static function optimizeJS($contents, $url) {
		$contents = trim($contents);
		
		//minimize js content
		$already_optimized = preg_match("/.min.js$/i", $url) || preg_match("/.pack.js$/i", $url);
		
		if (!$already_optimized && (strpos($contents, "\n") !== false || strpos($contents, "\r") !== false || strpos($contents, "/*") !== false || strpos($contents, "//") !== false)) {
			//remove comments. Don't do anything else bc it could be already encrypted or minimized.
			$options = array(
				"remove_single_line_comments" => true, 
				"remove_multiple_lines_comments" => true, 
				"remove_white_spaces" => true
			);
			$contents = self::removeCommentsAndEndLines($contents, $options, "js");
		}
		
		return $contents;
	}
	
	//Note that this function cannot remove spaces bc in javascript if exists 'else if (...)' it will then replace by 'elseif(...)', which will give a javascript error. Same happens with end-lines which should be replaced by space, otherwise can happen the same thing!
	public static function removeCommentsAndEndLines($contents, $options, $type) {
		if (!empty($options["remove_single_line_comments"]) || !empty($options["remove_multiple_lines_comments"]) || !empty($options["remove_white_spaces"])) {
			$contents_chars = TextSanitizer::mbStrSplit($contents);
			$l = count($contents_chars);
			$odq = $osq = false;
			$last_char = "";
			$new_contents = "";
			
			for ($i = 0; $i < $l; $i++) {
				$char = $contents_chars[$i];
				
				if ($char == '"' && !$osq && !TextSanitizer::isMBCharEscaped($contents, $i, $contents_chars))
					$odq = !$odq;
				else if ($char == "'" && !$odq && !TextSanitizer::isMBCharEscaped($contents, $i, $contents_chars))
					$osq = !$osq;
				else if ($char == "/" && !$odq && !$osq) {
					$next_char = $i + 1 < $l ? $contents_chars[$i + 1] : null;
					
					if (!empty($options["remove_single_line_comments"]) && $next_char == "/") { //remove single line comments
						//sets $i to the next end-line
						for ($j = $i + 2; $j < $l; $j++) {
							$c = $contents_chars[$j];
							
							if (($c == "\n" || $c == "\r") && !TextSanitizer::isMBCharEscaped($contents, $j, $contents_chars))
								break;
						}
						
						$i = $j - 1; //decrease 1 bc we want to include the end-line
						continue 1;
					}
					else if (!empty($options["remove_multiple_lines_comments"]) && $next_char == "*") { //remove multiple lines comments
						//sets $i to the comments closed tag
						for ($j = $i + 3; $j < $l; $j++) 
							if ($contents_chars[$j - 1] == "*" && $contents_chars[$j] == "/")
								break;
						
						$i = $j;
						continue 1;
					}
					else if ($type == "js" && !TextSanitizer::isMBCharEscaped($contents, $i, $contents_chars)) { //is a regex, something like: /^<\?(php|=)?(\s+|\$|"|'|[0-9])/.test("...");
						$is_regex = false;
						
						for ($j = $i + 1; $j < $l; $j++) {
							$c = $contents_chars[$j];
							
							if ($c == "/" && !TextSanitizer::isMBCharEscaped($contents, $j, $contents_chars)) {
								$is_regex = $j > $i + 1;//this means that the regex must have something, this is cannot be like '//'. Must have something like '/ /' or '/\w/'
								
								//checks if it is a real regex and not only the division operator, by checking the next chars of $j
								if ($is_regex)
									for ($w = $j + 1; $w < $l; $w++) {
										$c = $contents_chars[$w];
										
										// It could be '/\w+/.match("foo")' or 'var regex=/\w+/;' or 'foo(/\w+/)' or 'foo(/\w+/, bar)' or '/\w+/gi' or '/\w+/  .  match(...)' or 'foo(/\w+/  ,)'
										if (preg_match("/[a-z\.;,\)\n\r]/i", $c)) //\s includes end-lines too, bc I can have 'var x = /\w/' and then an end line. I don't need to have the ';'.
											break;
										else if ($c != " " && $c != "\t") {
											$is_regex = false;
											break;
										}
									}
								
								break;
							}
							else if (($c == "\n" || $c == "\r") && !TextSanitizer::isMBCharEscaped($contents, $j, $contents_chars))
								break;
						}
						
						if ($is_regex) {
							//error_log("REGEX($is_regex|$i|$j|$l):".implode("", array_slice($contents_chars, $i, ($j + 1) - $i))."\n\n", 3, "/var/www/html/livingroop/default/tmp/test.log");
							$new_contents .= implode("", array_slice($contents_chars, $i, ($j + 1) - $i));
							$last_char = $contents_chars[$j];
							$i = $j;
							continue 1;
						}
					}
				}
				else if (!empty($options["remove_white_spaces"]) && preg_match("/\s/", $char) && !$odq && !$osq && !TextSanitizer::isMBCharEscaped($contents, $i, $contents_chars)) { //remove white spaces, including \n, \r, \t and spaces
					$next_char = $i + 1 < $l ? $contents_chars[$i + 1] : null;
					
					//if chars are white spaces or '(' or ')' or '{' or '}' or ';', removes white spaces, otherwise adds a simple space.
					//Note that cannot remove white-spaces in the javascript case: 'else\tif (...)', otherwise this code will be replaced by 'elseif(...)', which will give a javascript error.
					//in case of '.foo .bar', do not convert to '.foo.bar', 
					//or in case of '.foo #bar', do not convert to '.foo#bar', 
					//or in case of '.foo :first-child', do not convert to '.foo:first-child'
					//or in case of '.foo:not(.x) .d', do not convert to '.foo:not(.x)'
					$last_char_regex = $type == "css" ? "/[\s\(\{\};]/" : "/[\s\(\)\{\};]/"; //if css do not add ")", otherwise '.foo:not(.x) .d' will be converted to '.foo:not(.x).d'
					
					if (!preg_match($last_char_regex, $last_char) && !preg_match("/[\s\(\)\{\};]/", $next_char)) {
						$new_contents .= " "; //add a simple space
						$last_char = " ";
					}
					
					continue 1;
				}
				
				$new_contents .= $char;
				$last_char = $char;
			}
			
			$contents = $new_contents;
		}
		
		return $contents;
	}
}
?>
