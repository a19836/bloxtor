<?php
include_once get_lib("org.phpframework.util.web.ImageHandler");

class HtmlDomHandler {
	private $html = null;
	private $DOMDocument = null;
	
	private $is_inline_html = false;
	
	public function __construct($html, $encoding = "utf-8") {
		$this->html = $html;
		
		if ($encoding)
			$html = mb_convert_encoding($html, 'HTML-ENTITIES', $encoding);
		
		$this->is_inline_html = stripos($html, "<html>") === false && stripos($html, "<html ") === false;
		
		$this->DOMDocument = new DOMDocument();
		
		//To suppress the warnings of the wrong html tags (like Unexpected end tag p). set libxml_use_internal_errors(true)
		$uie = libxml_use_internal_errors();
		libxml_use_internal_errors(true);
		
		$this->DOMDocument->loadHTML($html);
		
		libxml_use_internal_errors($uie);
		//libxml_clear_errors();
	}
	
	public function getDOMDocument() {
		return $this->DOMDocument;
	}
	
	public function getHtml() {
		if ($this->is_inline_html) {
			$node = $this->DOMDocument->getElementsByTagName("body");
			$node = $node ? $node->item(0) : null;
			
			if (!$node) {
				$node = $this->DOMDocument->getElementsByTagName("head");
				$node = $node ? $node->item(0) : null;
			}
			
			$html = '';
			if ($node && $node->childNodes) {
				foreach($node->childNodes as $child)
					$html .= $this->DOMDocument->saveHTML($child);
			}
			
			return $html;//only returns the innerhtml of $node
		}
		
		return $this->DOMDocument->saveHTML();//This includes the html tag
	}
	
	public function getHtmlExact() {
		$html = $this->getHtml();
		$this->rollbackNonDesiredUrlsEnconding($html);
		
		return $html;
	}
	
	//The $this->DOMDocument->saveHTML() will encode some part of the urls in src and href attributes, like '[' and ']' characters, so we need to put them back again with the decoded value
	public function rollbackNonDesiredUrlsEnconding(&$html) {
		//source: https://stackoverflow.com/questions/2725156/complete-list-of-html-tag-attributes-which-have-a-url-value
		$nodes_to_search = array(
			"body" => "background",
			"html" => "manifest",
			"meta" => "content",
			"a" => "href",
			"link" => "href",
			"area" => "href",
			"base" => "href",
			"svg" => "href",
			"img" => array("src", "longdesc", "usemap", "srcset", "href"),
			"iframe" => array("src", "longdesc"),
			"frame" => array("src", "longdesc"),
			"input" => array("src", "usemap", "formaction"),
			"video" => array("src", "poster"),
			"audio" => "src",
			"script" => "src",
			"embed" => "src",
			"source" => array("src", "srcset"),
			"track" => "src",
			"form" => "action",
			"object" => array("data", "classid", "codebase", "usemap", "archive"),
			"applet" => array("codebase", "archive"),
			"blockquote" => "cite",
			"del" => "cite",
			"ins" => "cite",
			"q" => "cite",
			"head" => "profile",
			"button" => "formaction",
			"command" => "icon",
		);
		
		foreach ($nodes_to_search as $tag => $attributes_name)
			if ($attributes_name)
				foreach ($this->DOMDocument->getElementsByTagName($tag) as $node) {
					if (!is_array($attributes_name))
						$attributes_name = array($attributes_name);
					
					foreach ($attributes_name as $attribute_name) 
						if ($attribute_name) {
							$attribute_value = $node->getAttribute($attribute_name);
							
							if ($attribute_value) {
								$node_aux = $this->DOMDocument->createElement($tag); 
						   		$node_aux->setAttribute($attribute_name, $attribute_value);
								$node_aux_html = $this->DOMDocument->saveHTML($node_aux);
								unset($node_aux);
								
								preg_match('/' . $attribute_name . '\s*=\s*"([^"]*)"/i', $node_aux_html, $matches, PREG_OFFSET_CAPTURE);
								
								if ($matches && $matches[1]) {
									$attribute_value_encoded = $matches[1][0];
									
									if ($attribute_value_encoded && $attribute_value_encoded != $attribute_value) {
										$html = str_replace($attribute_value_encoded, $attribute_value, $html);
										//error_log("$attribute_name original:$attribute_value\n$attribute_name encoded:".$attribute_value_encoded."\n\n", 3, $GLOBALS["log_file_path"] ? $GLOBALS["log_file_path"] : "/var/www/html/livingroop/default/tmp/phpframework.log");
									}
								}
							}
						}
				}
		
		//error_log("html:$html\n\n", 3, $GLOBALS["log_file_path"] ? $GLOBALS["log_file_path"] : "/var/www/html/livingroop/default/tmp/phpframework.log");
	}
	
	public function isHTML() {
		return $this->html != strip_tags($this->html);
	}
	
	public function resizeImages() {
		$status = true;
		
		//resizes inline images if necessary
		if (stripos($this->html, "<img ") !== false) {
			$imgs = $this->DOMDocument->getElementsByTagName('img');
			
			if ($imgs)
				foreach ($imgs as $img)
					if ($this->isInlineImage($img)) {
						$width = strstr($this->getElementStyle($img, "width", "px"), "px", true);
						$height = strstr($this->getElementStyle($img, "height", "px"), "px", true);
						
						$width = $width ? $width : $img->getAttribute("width");
						$height = $height ? $height : $img->getAttribute("height");
						
						if (is_numeric($width) || is_numeric($height)) {
							$img_data = $this->getInlineImageBase64DataDecoded($img);
								
							if ($img_data) {
								$src_path = tempnam(sys_get_temp_dir(), 'src_img_resize_');
								
								if (file_put_contents($src_path, $img_data) > 0) {
									if (!$width || !$height) {
										list($w, $h) = getimagesize($src_path);
										
										if ($w && $h) {
											if (!$width)
												$width = ($height * $w) / $h;
											else
												$height = ($width * $h) / $w;
										}
									}
									
									if ($width && $height) {
										$ImageHandler = new ImageHandler();
										$dst_path = tempnam(sys_get_temp_dir(), 'dst_img_resize_');
								
										if (@$ImageHandler->isImageBinaryValid($src_path) && @$ImageHandler->imageResize($src_path, $dst_path, $width, $height)) {
											$img_data = file_get_contents($dst_path);
											$img_data = base64_encode($img_data);
											
											$src = $img->getAttribute("src");
											$start = stripos($src, "base64,") + strlen("base64,");
											$img->setAttribute("src", substr($src, 0, $start) . " " . $img_data);
										}
										else
											$status = false;
										
										if (file_exists($dst_path))
											unlink($dst_path);
									}
									else
										$status = false;
								}
								else 
									$status = false;
								
								if (file_exists($src_path))
									unlink($src_path);
							}
						}
					}
		}
		
		return $status;
	}
	
	public function getElementStyle($element, $style_name, $filter = false) {
		$style_name = strtolower($style_name);
		$filter = is_array($filter) ? $filter : ($filter ? array($filter) : $filter);
		
		$style = $element->getAttribute("style");
		
		if ($style) {
			$attrs = explode(";", $style);
			$t = count($attrs);
			for ($i = $t - 1; $i >= 0; $i--) {
				$parts = explode(":", $attrs[$i]);
				
				if (strtolower(trim($parts[0])) == $style_name) {
					$v = isset($parts[1]) ? trim($parts[1]) : "";
					
					if (!$filter) 
						return $v;
					else
						foreach ($filter as $f)
							if (stripos($v, $f) !== false)
								return $v;
				}
			}
		}
		
		return null;
	}
	
	public function setElementStyle($element, $style_name, $style_value, $filter = false) {
		$style_name = strtolower($style_name);
		$filter = is_array($filter) ? $filter : ($filter ? array($filter) : $filter);
		
		$style = $element->getAttribute("style");
		$new_style = $style;
		
		if ($style) {
			$attrs = explode(";", $style);
			$t = count($attrs);
			for ($i = $t - 1; $i >= 0; $i--) {
				$parts = explode(":", $attrs[$i]);
				
				if (strtolower(trim($parts[0])) == $style_name) {
					$v = isset($parts[1]) ? trim($parts[1]) : "";
					$exists = false;
					
					if (!$filter) 
						$exists = true;
					else
						foreach ($filter as $f)
							if (stripos($v, $f) !== false) {
								$exists = true;
								break;
							}
					
					if ($exists) {
						$replacement = $style_value === "" ? "" : $parts[0] . ":$style_value;"; //if $style_value is an empty string, then we remove the correspondent style attribute.
						$new_style = str_replace($attrs[$i] . ";", $replacement, $style);
						
						//break; //Do not break bc there could be multiple repeated style types.
					}
				}
			}
			
			//if there were not style_name in style
			if ($new_style == $style && $style_value !== "")
				$new_style .= (preg_match("/;\s*$/", $new_style) ? "" : ";") . "$style_name:$style_value;";
		}
		else //if style doesn't exists
			$new_style = "$style_name:$style_value;";
		
		if ($new_style != $style)
			$element->setAttribute("style", $new_style);
	}
	
	public function isInlineImage($img) {
		if ($img) {
			$src = $img->getAttribute("src");
			
			return $src && substr($src, 0, 5) == "data:";
		}
	}
	
	public function getInlineImageBase64Data($img) {
		if ($img) {
			$src = $img->getAttribute("src");
			
			if ($src && substr($src, 0, 5) == "data:") {
				//$src = preg_replace('#^data:image/\w+;base64,#i', '', $src);
				
				$start = stripos($src, "base64,");
				
				if ($start !== false) {
					$start += strlen("base64,");
					$src = substr($src, $start);
				}
				
				//urldecode is very important bc the base64 html string, might be url encoded.
				//trim must be right after the urldecode so it can remove the leading spaces.
				//in case exists spaces, replaces them by +. (Not sure if this part is necessary. I didn't test any example with this, only saw some comments online about this - maybe is not needed.)
				return str_replace(' ', '+', trim(urldecode($src)));
			}
		}
	}
	
	public function getInlineImageBase64DataDecoded($img) {
		$img_data = $this->getInlineImageBase64Data($img);
		
		if ($img_data) {
			//for really big strings
			$decoded = ""; 
			for ($i = 0; $i < ceil(strlen($img_data) / 256); $i++) 
				$decoded = $decoded . base64_decode(substr($img_data, $i*256, 256)); 
			return $decoded;
			
			//for normal strings
			return base64_decode($img_data);
		}
	}
	
	public function getInlineImageContentType($img) {
		if ($img) {
			$src = $img->getAttribute("src");
			
			if ($src && substr($src, 0, 5) == "data:") {
				$pos = stripos($src, ";", 5);
				
				if ($pos) {
					$content_type = substr($src, 5, $pos - 5);
					return strtolower(substr($content_type, 0, 6)) == "image/" ? $content_type : null;
				}
			}
		}
	}
	
	public function saveInlineImageToFile($img, $file_path) {
		$img_data = $this->getInlineImageBase64DataDecoded($img);
		
		if ($img_data) {
			//TODO: fix Warning: imagecreatefromstring(): Data is not in a recognized format in /var/www/html/phpframework/trunk/app/lib/web/HtmlDomHandler.php on line 196
			$im = @imagecreatefromstring($img_data);
		
			if ($im) {
				$mime_type = $this->getInlineImageContentType($img);
			
				switch(strtolower($mime_type)){
					case 'image/bmp': 
						$status = imagewbmp($im, $file_path);
						break; 
					case 'image/gif': 
						$status = imagegif($im, $file_path); 
						break; 
					case 'image/jpeg': 
					case 'image/jpg': 
						$status = imagejpeg($im, $file_path); 
						break; 
					case 'image/png': 
						$status = imagepng($im, $file_path);
						break; 
					default:
						$status = imagejpeg($im, $file_path);
				}
			
				imagedestroy($im);
			}
		
			if (!$status)
				$status = file_put_contents($file_path, $img_data) > 0;
		}
		
		return $status;
	}
}
?>
