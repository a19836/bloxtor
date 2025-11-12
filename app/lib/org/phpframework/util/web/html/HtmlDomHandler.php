<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

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
	
	public function getNodeCssSelector($node) {
		// Generate CSS selector for the matching node
		$selector = [];

		while ($node && $node->nodeType === XML_ELEMENT_NODE) {
			$tag = strtolower($node->nodeName);

			if (!$tag || $tag == "html")
				break;
			else if ($tag == "body" || $tag == "head") {
				$selector[] = $tag;
				break;
			}
			
			//get node index
			$parent = $node->parentNode;

			if ($parent && $parent->hasChildNodes()) {
				$index = 0;
				$children = $parent->childNodes;
				
				for ($i = 0, $t = $children->length; $i < $t; $i++) {
					$pn = $children->item($i);
					$index++;

					if ($node->isSameNode($pn)) {
						$tag .= ":nth-child($index)";
						break;
					}
				}
			}

			// Add ID or class if available for specificity
			if ($node->hasAttributes()) {
				if ($node->hasAttribute('id')) {
					$id = preg_split("/\s+/", $node->getAttribute('id'));
					$id = preg_replace("/[^\w\-]+/u", "", $id[0]); //remove all non allowed chars, just in case

					$selector[] = $tag . '#' . $id;
					break; // IDs are unique, so we can stop here
				} 
				elseif ($node->hasAttribute('class')) {
					$classes = preg_split("/\s+/", $node->getAttribute('class'));
					$classes = array_filter($classes); //remove all empty values
					$classes = array_map(function($c) { return preg_replace("/[^\w\-]+/u", "", $c); }, $classes); //remove all non allowed chars, just in case

					$selector[] = $tag . '.' . implode('.', $classes);
				} 
				else
					$selector[] = $tag;
			} 
			else
				$selector[] = $tag;

			$node = $parent;
		}

		return implode(' > ', array_reverse($selector));
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
				
				if (function_exists("imagedestroy"))
					@imagedestroy($im);
			}
		
			if (!$status)
				$status = file_put_contents($file_path, $img_data) > 0;
		}
		
		return $status;
	}
	
	// Função auxiliar para dividir o seletor CSS em partes
	public function splitSelector($selector) {
		$selector = trim($selector);
		
		if (!$selector)
			return null;
		
		// Regular expression to match CSS parts separated by combinators
		$pattern = '/' .
			'(' .  
			  '[a-zA-Z0-9\-_#\.]+' . //Match tags, classes, IDs, or simple selectors
			  '(' .
					'(:nth-child\([^\)]+\))' . //Optionally match pseudo-classes like :nth-child(3)
					'|((\.|#)[a-zA-Z0-9\-_]+)' . 
					'|(\[[^\]]+\])' . //Optionally match attributes like [name="value"]
			  ')*' . 
		  '|>)' . 
		'/u';
		preg_match_all($pattern, $selector, $matches, PREG_PATTERN_ORDER);

		// Filter out empty matches and trim whitespace
		$parts = array_filter(array_map('trim', $matches[0]));
		//print_r($parts);die();

		return $parts;
	}

	// Função para verificar se um elemento corresponde a um seletor
	public function matchesSelector($node, $selector) {
		if (!$node || !$selector)
			return false;
		
		// Verifica por node name
		preg_match("/^(\w[\w\-]+)/u", $selector, $match, PREG_OFFSET_CAPTURE);
		
		if ($match && $match[1][0] && strtolower($node->nodeName) !== strtolower($match[1][0]))
			return false;
		
		// Verifica por atributos
		if (preg_match_all('/\[(.*?)\]/', $selector, $matches, PREG_OFFSET_CAPTURE)) {
			$selector = preg_replace('/\[(.*?)\]/', "", $selector); //clean attributes selector bc of class selector check
			$matches = $matches[1];
			
			foreach ($matches as $match) {
				$attribute_selector = $match[0];
				$parts = explode('=', $attribute_selector, 2);
				$attr_name = trim($parts[0], '"\' ');
				$attr_value = null;
				
				if (isset($parts[1])) {
					$attr_value = trim($parts[1]);
					$attr_value = preg_replace("/^'(.*)'$/", '$1', $attr_value);
					$attr_value = preg_replace('/^"(.*)"$/', '$1', $attr_value);
				}
				//echo "attr_name:$attr_name|attr_value:$attr_value\n";
				
				if (!$node->hasAttribute($attr_name) || (isset($attr_value) && $node->getAttribute($attr_name) != $attr_value))
					return false;
			}
		}

		// Verifica a pseudo-classe :nth-child
		if (preg_match('/:nth-child\((\d+)\)/', $selector, $matches, PREG_OFFSET_CAPTURE)) {
			$selector = preg_replace('/:nth-child\((\d+)\)/', "", $selector); //clean attributes selector bc of class selector check
			
			$nth_child_index = (int)$matches[1][0];
			$siblings = $node->parentNode->childNodes;
			$sibling = $siblings->item($nth_child_index - 1);
			
			if (!$sibling)
				return false; // Não encontrou o elemento na posição correta
			
			$node_outer_html = $this->outerHTML($node);
			$sibling_outer_html = $this->outerHTML($sibling);
			
			if ($sibling_outer_html != $node_outer_html)
				return false; // Não encontrou o elemento na posição correta
		}

		// Verifica por node id
		if (strpos($selector, '#') !== false) {
			preg_match("/#([\w\-]+)/u", $selector, $match, PREG_OFFSET_CAPTURE);
			$id = $match ? $match[1][0] : null;
			
			if (!$id || $node->getAttribute('id') !== $id)
				return false;
		}
		
		// Verifica por node class
		if (strpos($selector, '.') !== false) {
			preg_match_all("/.([\w\-]+)/u", $selector, $matches, PREG_PATTERN_ORDER);
			
			if ($matches) {
				$node_classes = preg_split("/\s+/", $node->getAttribute('class'));
				$classes = array_map('trim', $matches[1]);
				
				foreach ($classes as $class)
					if (!in_array($class, $node_classes))
						return false;
			}
		}

		return true;
	}

	// Função recursiva para verificar todos os descendentes ou filhos diretos
	public function checkDescendants($parent, $selectors, &$result, $current_index = 0) {
		if (!$parent || !$selectors)
			return false;
		
		$current_selector = isset($selectors[$current_index]) ? $selectors[$current_index] : null;
		$is_direct_children = false;
		
		if ($current_selector == ">") {
			$is_direct_children = true;
			$current_index++;
			$current_selector = isset($selectors[$current_index]) ? $selectors[$current_index] : null;
		}

		if ($current_index >= count($selectors)) {
			// Se chegamos ao final, adicionamos o nó ao resultado, se o ultimo selector for diferente de '>'
			if (!$is_direct_children)
				$result[] = $parent;
			
			return false;
		}
		
		foreach ($parent->childNodes as $child)
			if ($child instanceof DOMElement) {
				if (self::matchesSelector($child, $current_selector)) {
					//echo "checked:".$child->textContent."\n";
					// Se o filho corresponde ao seletor atual, verificamos o resto do seletor nos seus descendentes
					self::checkDescendants($child, $selectors, $result, $current_index + 1);
				}
				
				// Se o seletor é de filhos diretos, não percorremos os descendentes
				if ($is_direct_children)
					continue; // Não verifica descendentes, apenas filhos diretos
				
				//echo "verifica descendentes:".$child->textContent."\n";
				// Verifica os descendentes, se não for um seletor de filhos diretos
				self::checkDescendants($child, $selectors, $result, $current_index);
			}
		
		return true;
	}

	public function querySelectorAll($css_selector, $element = null) {
		$css_selector = preg_replace("/^\s*:scope\s*(\s|>)/", '$1', $css_selector); //allow ':scope > '
		$result = array();
		
		if (!$element)
			$element = $this->DOMDocument->documentElement;
		
		if (method_exists($element, "querySelectorAll")) {
			$nodes_list = $element->querySelectorAll($css_selector);
			
			if ($nodes_list)
				foreach ($nodes_list as $node)
					$result[] = $node;
		}
		
		// Divida o seletor CSS em partes
		$selectors = self::splitSelector($css_selector);
		//print_r($selectors);

		// Iniciar a verificação a partir do nó pai
		self::checkDescendants($element, $selectors, $result);

		return $result;
	}

	public function querySelector($css_selector, $element = null) {
		if (!$element)
			$element = $this->DOMDocument->documentElement;
		
		if (method_exists($element, "querySelector")) {
			$css_selector = preg_replace("/^\s*:scope\s*(\s|>)/", '$1', $css_selector); //allow ':scope > '
			return $element->querySelector($css_selector);
		}
		
		$result = self::querySelectorAll($css_selector, $element);
		
		return $result ? $result[0] : null;
	}
	
	public function innerHTML($element) {
		if (property_exists($element, "innerHTML"))
			return $element->innerHTML;
		
		$dom = new DOMDocument();
		
		foreach ($element->childNodes as $child)
        $dom->appendChild($dom->importNode($child, true));
		
		return $dom->saveHTML();
	}
	
	public function outerHTML($element) {
		return $element->ownerDocument->saveHTML($element);
	}
}
?>
