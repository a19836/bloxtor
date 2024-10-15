<?php
include_once get_lib("org.phpframework.util.MimeTypeHandler");
include_once get_lib("org.phpframework.util.PathHandler");

class ConvertRemoteUrlHandler {

	//save js, css and images local files. Basically searches for all files that should convert to local, download them and save them into $webroot_folder_path and change the link url to point to the new downloaded file (which is now a local file)
	public static function saveHtmlRelativeUrls($webroot_folder_path, $url, &$html, $url_replacement) {
		$status = true;
		
		$matches = self::getHtmlUrls($html);
		
		$url_replacement = preg_replace("|/+$|", "", $url_replacement);//remove last "/"
		
		if ($matches) {
			$links = $matches[2];
			
			if ($links) {
				$full_matches = $matches[0];
				$available_image_extensions = MimeTypeHandler::getAvailableFileExtensions("image");
				$available_extensions = $available_image_extensions;
				$available_extensions[] = "css";
				$available_extensions[] = "js";
				
				$pu = parse_url($url);
				$pu_host = isset($pu["host"]) ? $pu["host"] : null;
				
				foreach ($links as $idx => $link) {
					$lu = parse_url($link);
					$lu_host = isset($lu["host"]) ? $lu["host"] : null;
					
					if ($lu_host == $pu_host) {
						$path = isset($lu["path"]) ? $lu["path"] : null;
						$extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
						
						if (in_array($extension, $available_extensions)) {
							$is_local_file = substr($link, 0, 7) == "file://";
							$content = $content_type = null;
							
							if ($is_local_file) {
								$fp = substr($link, 7);
								
								if (file_exists($fp)) {
									$content = file_get_contents($fp);
									$content_type = mime_content_type($fp);
								}
							}
							else {
								$settings = array(
									"url" => $link, 
									"settings" => array(
										"connection_timeout" => 60, //in seconds
										"follow_location" => 1,
										"referer" => isset($_SERVER["HTTP_REFERER"]) ? $_SERVER["HTTP_REFERER"] : null,
										"CURLOPT_USERAGENT" => isset($_SERVER["HTTP_USER_AGENT"]) ? $_SERVER["HTTP_USER_AGENT"] : null
									)
								);
								
								$MyCurl = new MyCurl();
								$MyCurl->initSingle($settings);
								$MyCurl->get_contents();
								$data = $MyCurl->getData();
								$content = isset($data[0]["content"]) ? $data[0]["content"] : null;
								$content_type = isset($data[0]["info"]["content_type"]) ? strtolower($data[0]["info"]["content_type"]) : "";
							}
							
							$valid = true;
							
							if (in_array($extension, $available_image_extensions))
								$valid = MimeTypeHandler::isImageMimeType($content_type);
							
							if ($valid) {
								$relative_file_path = $path;
								//echo "$relative_file_path\n";
								
								$file_path = PathHandler::getAbsolutePath($webroot_folder_path . $relative_file_path);
								$file_dir_path = dirname($file_path) . "/";
								//echo "file_dir_path:$file_dir_path<br/>\n";
								
								if (!is_dir($file_dir_path))
									mkdir($file_dir_path, 0755, true);
								
								if (!is_dir($file_dir_path) || file_put_contents($file_path, $content) === false)
									$status = false;
								else { //success
									$new_link = $url_replacement . (substr($relative_file_path, 0, 1) == "/" ? "" : "/") . $relative_file_path;
									
									$replacement = str_replace($link, $new_link, $full_matches[$idx]);
									$html = str_replace($full_matches[$idx], $replacement, $html);
								}
							}
						}
					}
				}
			}
		}
		
		return $status;
	}
	
	//get html from an url and set the real_url to the redirected url if apply
	public static function getUrlHtml($url, &$real_url = null) {
		$is_local_file = substr($url, 0, 7) == "file://";
		$html = null;
		
		if ($is_local_file) {
			$fp = substr($url, 7);
			$html = file_exists($fp) ? file_get_contents($fp) : "";
		}
		else {
			$settings = array(
				"url" => $url, 
				"settings" => array(
					"connection_timeout" => 60, //in seconds
					"follow_location" => 1,
					"referer" => isset($_SERVER["HTTP_REFERER"]) ? $_SERVER["HTTP_REFERER"] : null,
					"CURLOPT_USERAGENT" => isset($_SERVER["HTTP_USER_AGENT"]) ? $_SERVER["HTTP_USER_AGENT"] : null
				)
			);
			
			$MyCurl = new MyCurl();
			$MyCurl->initSingle($settings);
			$MyCurl->get_contents();
			$data = $MyCurl->getData();
			$html = isset($data[0]["content"]) ? $data[0]["content"] : null;
			
			$url = !empty($data[0]["info"]["url"]) ? $data[0]["info"]["url"] : $url;
		}
		
		if ($html)
			$html = self::prepareHtmlRelativeUrls($url, $html);
		
		$real_url = $url;
		
		return $html;
	}
	
	//replace all urls with the prefix $url, without the prefix. Basically converts the absolute urls to relative urls, by removing its hostname.
	public static function replaceLocalUrlsInHtml($url, &$html) {
		$matches = self::getHtmlUrls($html);
		
		if ($matches) {
			$links = $matches[2];
			
			if ($links) {
				$full_matches = $matches[0];
				$pu = parse_url($url);
				$pu_host = isset($pu["host"]) ? $pu["host"] : null;
				
				foreach ($links as $idx => $link) {
					$lu = parse_url($link);
					$lu_host = isset($lu["host"]) ? $lu["host"] : null;
					
					if ($lu_host == $pu_host) {
						$new_link = isset($lu["path"]) ? $lu["path"] : null;
						$new_link = substr($new_link, 0, 1) == "/" ? substr($new_link, 1) : $new_link;
						
						$replacement = str_replace($link, $new_link, $full_matches[$idx]);
						$html = str_replace($full_matches[$idx], $replacement, $html);
					}
				}
			}
		}
	}
	
	//replace all urls with # which belong to $url, followed by the rest of each link - This function is only to disable the urls so the browser doesn't redirect the user to another pages.
	public static function replaceExtraUrlsInHtml($url, &$html) {
		$matches = self::getHtmlUrls($html);
		
		if ($matches) {
			$links = $matches[2];
			
			if ($links) {
				$full_matches = $matches[0];
				$pu = parse_url($url);
				$pu_host = isset($pu["host"]) ? $pu["host"] : null;
				
				foreach ($links as $idx => $link) {
					$lu = parse_url($link);
					$lu_host = isset($lu["host"]) ? $lu["host"] : null;
					
					if ($lu_host == $pu_host) {
						$new_link = '#';
						$replacement = str_replace($link, $new_link, $full_matches[$idx]);
						$html = str_replace($full_matches[$idx], $replacement, $html);
					}
				}
			}
		}
	}

	//replace relative js, css and images urls with absolute urls - search all the urls in a html and if belong to the $url or are local url, then normalize these urls with http(s) and the $url prefix. Basicallt normalizes all the urls that belong to the domain of the $url.
	public static function prepareHtmlRelativeUrls($url, $html) {
		$matches = self::getHtmlUrls($html);
		
		if ($matches) {
			$links = $matches[2];
			
			if ($links) {
				$full_matches = $matches[0];
				
				$prefix_url = (!preg_match("/^(http:\/\/|https:\/\/|\/\/)/i", $url) ? "http://" : "") . $url;
				
				$pu = parse_url($prefix_url);
				
				if ($pu) {
					$prefix_root_url = (!empty($pu["scheme"]) ? $pu["scheme"] . "://" : "//") . (!empty($pu["user"]) ? $pu["user"] . (!empty($pu["pass"]) ? ":" . $pu["pass"] : "") . "@" : "") . $pu["host"] . (!empty($pu["port"]) ? ":" . $pu["port"] : "");
					
					$prefix_url = $prefix_root_url . (
						pathinfo($pu["path"], PATHINFO_EXTENSION) ? (
							dirname($pu["path"]) != "." ? dirname($pu["path"]) : ""
						) : $pu["path"]
					);
					$prefix_url .= substr($prefix_url, -1) != "/" ? "/" : "";
					$prefix_root_url .= substr($prefix_root_url, -1) != "/" ? "/" : "";
					
					foreach ($links as $idx => $link) 
						if (!preg_match("/^(http:\/\/|https:\/\/|\/\/)/i", trim($link))) {
							$new_link = trim($link);
							$new_link = $new_link[0] == "/" ? $prefix_root_url . substr($new_link, 1) : $prefix_url . $new_link;
							$replacement = str_replace($link, $new_link, $full_matches[$idx]);
							$html = str_replace($full_matches[$idx], $replacement, $html);
						}
				}
			}
		}
		
		return $html;
	}
	
	//get all urls in a html - urls inside of src and href attributes. Note that this will return the matches from the preg_match_all function
	public static function getHtmlUrls($html) {
		preg_match_all('/\s*(href|src|srcset)\s*=\s*"([^"]*)"/iu', $html, $matches_1, PREG_PATTERN_ORDER); //'/u' means converts unicode.
		preg_match_all("/\s*(href|src|srcset)\s*=\s*'([^']*)'/iu", $html, $matches_2, PREG_PATTERN_ORDER); //'/u' means converts unicode.
		
		$matches = $matches_1;
		
		if ($matches_2)
			foreach ($matches_2 as $idx => $m) 
				if (is_array($m)) {
					if (!is_array($matches[$idx]))
						$matches[$idx] = array();
					
					$matches[$idx] = array_merge($matches[$idx], $m);
				}
		
		return $matches;
	}
}
?>
