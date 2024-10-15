<?php
include_once get_lib("org.phpframework.util.text.TextSanitizer");

class HtmlStringHandler {
	
	//This vars are used in the self::convertHtmlToElementsArray method.
	public static $single_tags_name = array("area", "base", "br", "col", "embed", "hr", "img", "input", "link", "meta", "param", "source", "track", "wbr", "command", "keygen", "menuitem", "frame", "iframe", "basefont", "bgsound", "isindex");
	
	public static $auto_close_siblings_tag_name = array("li" => array("li"), "dd" => array("dd", "dt"), "dt" => array("dd", "dt")); //TODO: add other tags where this happens
	
	public static $tags_to_jump = array("style", "script");
	
	/*
	 * This method will be used by the $elements returned from the self::convertHtmlToElementsArray method.
	 * Basically passing an elements array as the first argument, this method creates the correspondent html for that array.
	 */
	public static function convertElementsArrayToHtml($elements, $options = array("trim" => false, "auto_close_missing_tags" => false)) {
		$html = "";
		
		$auto_close_missing_tags = $options && !empty($options["auto_close_missing_tags"]);
		
		if ($elements) 
			foreach ($elements as $element) {
				if (is_array($element)) {
					$node_type = isset($element["nodeType"]) ? $element["nodeType"] : null;
					
					switch ($node_type) {
						case 3: //text node
						case 8: //comment node
							$html .= isset($element["nodeValue"]) ? $element["nodeValue"] : (isset($element["textContent"]) ? $element["textContent"] : null);
							break;
						
						case 1: //element node
							$tag_name = isset($element["nodeName"]) ? $element["nodeName"] : null;
							$open_tag = isset($element["openTag"]) ? $element["openTag"] : null;
							$close_tag = isset($element["closeTag"]) ? $element["closeTag"] : null;
							$children = isset($element["childNodes"]) ? $element["childNodes"] : null;
							
							$children_html = $children ? self::convertElementsArrayToHtml($children, $options) : "";
							
							$html .= $open_tag . $children_html . ($close_tag ? $close_tag : ($auto_close_missing_tags ? "</$tag_name>" : ""));
							break;
					}
				}
				else
					$html .= $element;
			}
		
		return $html;
	}
	
	public static function joinElementsArrayTextNodes($elements) {
		$new_elements = array();
		
		if ($elements) 
			foreach ($elements as $element) {
				$previous_index = count($new_elements) - 1;
				
				if (is_array($element)) {
					$node_type = isset($element["nodeType"]) ? $element["nodeType"] : null;
					
					if ($node_type == 3) { //text node
						if ($previous_index >= 0 && isset($new_elements[$previous_index]) && is_array($new_elements[$previous_index]) && isset($new_elements[$previous_index]["nodeType"]) && $new_elements[$previous_index]["nodeType"] == $node_type)
							$new_elements[$previous_index]["nodeValue"] .= isset($element["nodeValue"]) ? $element["nodeValue"] : null;
						else
							$new_elements[] = $element;
					}
					else 
						$new_elements[] = $element;
				}
				else {
					if ($previous_index >= 0 && (!isset($new_elements[$previous_index]) || !is_array($new_elements[$previous_index])))
						$new_elements[$previous_index] .= $element;
					else
						$new_elements[] = $element;
				}
			}
		
		return $new_elements;
	}
	
	/*
	 * convert a html string into an array of elements
	 * 
	 * Use this method carefully bc of the missing closing tags, this is, if there is any open tag that doesn't have its closing tag, there will be some cases where we will not closing it automatically correctly or making all the next elements as inner elements.
	 * This is, if a tag is not closed properly, one of the behaviours of the browsers and the DomDocument php class (according with the test that I did!), is to automatically close this tag without any inner html. Another behaviour would be to get the html until the end, depending of the tag type and the doctype... 
	 * Basically we try to replicate the Browsers behaviour, but couldn't do it for all cases, bc this process is very complicated, so USE THIS METHOD CAREFULLY!
	 *
	 * To test this method, execute the following code:
		$html = '
		<section>
			s1
			<article>
				a1
				<span>Some span bla</span>
				a2
				<i class="icon add">Add</i>
				a3
				
				<script>
					var foo = "</article>";
				</script>
				
			</article>
			
			<script>
				var foo = "<article><div>";
			</script>
			
			s2
			<div>
				d1
				<header>
					<input name="first_name" />
				<!--header-->
				d2 - Note that the header tag will be closed after this text and not before, as it shows in the comment! This is on purpose!
			</div>
			s3
			<ul>
				<li>li1</li>
				<li>li2<!--li-->
				
				<style>
					.foo {
						content: "</li><li>";
					}
				</style>
				
				<li>li3</li>
			</ul>
			<dl>
				<dt>Coffee</dt>
				<dd>Black hot drink<!--dd-->
				<dt>Milk<!--dt-->
				<dd>White cold drink</dd>
			</dl>
			<!--strong-->Bold text</strong>
		</section> ';
		
		$elements = HtmlStringHandler::convertHtmlToElementsArray($html);
		$new_html = HtmlStringHandler::convertElementsArrayToHtml($elements);
		echo "<pre>";print_r($elements);echo "\nNew html:$new_html";die();
	 *
	 * 
	 * or the code:
		$html = '
		</div>
	</div>
	<article>asds</article>
</section>
</body>';
		
		$elements = HtmlStringHandler::convertHtmlToElementsArray($html);
		$elements = HtmlStringHandler::joinElementsArrayTextNodes($elements);
		
		foreach ($elements as $idx => $element)
			if (is_array($element) && $element["nodeType"] == 1) //1 == is Element node
				unset($elements[$idx]);
		
		$new_html = HtmlStringHandler::convertElementsArrayToHtml($elements);
		echo "<pre>";print_r($elements);echo "\nNew html:$new_html";die();
	 */
	public static function convertHtmlToElementsArray($html, $options = array("force_no_closing_tag_to_end" => true, "trim" => false)) {
		$elements = array();
		
		if ($html) {
			$force_no_closing_tag_to_end = $options && !empty($options["force_no_closing_tag_to_end"]);
			$trim = $options && !empty($options["trim"]);
			
			$html_length = strlen($html);
			$offset = 0;
			
			do {
				$open_tag_start_pos = strpos($html, "<", $offset);
				
				if ($open_tag_start_pos !== false) {
					//prepare text node
					$text_node = substr($html, $offset, $open_tag_start_pos - $offset);
					$text_element = null;
					
					if ( ($trim && trim($text_node)) || (!$trim && $text_node) ) //do not add the $text_element to the $elements array yet. See bellow...
						$text_element = array(
							"nodeType" => 3, //text
							"nodeValue" => $text_node,
						);
					
					//if is comment
					if (substr($html, $open_tag_start_pos + 1, 3) == "!--") { 
						//prepare comment
						$open_tag_end_pos = strpos($html, "-->", $open_tag_start_pos + 3);
						$open_tag_end_pos = $open_tag_end_pos === false ? $html_length : $open_tag_end_pos + 3;
						
						$comment = substr($html, $open_tag_start_pos, $open_tag_end_pos - $open_tag_start_pos);
						$offset = $open_tag_end_pos;
						
						//add text element if exists
						if ($text_element)
							$elements[] = $text_element;
						
						//add comment element
						$elements[] = array(
							"nodeType" => 8, //comment
							"textContent" => $comment,
						);
					}
					else { //is a element node
						//prepare tag
						$open_tag_end_pos = strpos($html, ">", $open_tag_start_pos);
						$open_tag_end_pos = $open_tag_end_pos === false ? $html_length : $open_tag_end_pos;
						
						$tag = substr($html, $open_tag_start_pos, ($open_tag_end_pos + 1) - $open_tag_start_pos);
						preg_match("/<\/?([^>\s]+)/", $tag, $match, PREG_OFFSET_CAPTURE);
						$tag_name = isset($match[1]) ? $match[1][0] : null;
						
						if ($tag_name) {
							//set new offset to open_tag_end_pos
							$offset = $open_tag_end_pos + 1; //very important ot set the $offset otherwise we will have an infinit loop.
							
							//add text element if exists
							if ($text_element)
								$elements[] = $text_element;
							
							//if is close tag
							if (isset($html[$open_tag_start_pos + 1]) && $html[$open_tag_start_pos + 1] == "/") {
								//add close tag as a text node bc is not a html tag element, since it doesn't corresponds to any open tag! The default browsers behaviour is to delete this tag and not included it! We are including it, bc this is used in other methods like in the org/phpframework/cms/wordpress/WordPressHacker::getContentParentsHtmlBellow(...) method. 
								//If you wish, you can use it appended to the last text node too, like it shows in the code bellow... However NEVER DELETE OR DISCARD this tag. You must always include it, bc this method is used to parse fragmented HTML that looks not well formatted, bc the HTML is not completed and comes from multiple php vars. So you MUST ALWAYS include this closed tags (as an independent text node or together with the last text node, as it shows the code bellow)!
								$elements[] = array(
									"nodeType" => 3, //text
									"nodeValue" => $tag,
								);
								//or the following code:
								/*$last_element_index = count($elements) - 1;
								$elements[ $last_element_index ] = array(
									"nodeType" => 3, //text
									"nodeValue" => $elements[ $last_element_index ]["nodeValue"] . $tag,
								);*/
							}
							else { //if is open tag
								//add new element
								$element = array(
									"nodeType" => 1, //element
									"nodeName" => $tag_name,
									"openTag" => $tag,
								);
								//error_log("open tag_name:$tag_name => $tag!\n", 3, "/var/www/html/livingroop/default/tmp/test.log");
								
								$is_single_tag = in_array(strtolower($tag_name), self::$single_tags_name);
								
								//if is a single tag
								if ($html[$open_tag_end_pos - 1] == "/" || $is_single_tag) { // -1 bc the $open_tag_end_pos is the '>' char
									$element["singleTag"] = true;
									
									if ($is_single_tag) {
										$close_tag_end_pos = self::getSingleTagEndPositionIfThereIsACloseTag($html, $tag_name, $offset);
										
										//prepare close tag
										if ($close_tag_end_pos != $offset) {
											//prepare inner html
											$inner_html = substr($html, $offset, ($close_tag_end_pos + 1) - $offset);
											$close_tag_start_pos = strripos($inner_html, "</$tag_name");
											
											//prepare closing tag
											$closing_tag = substr($inner_html, $close_tag_start_pos, ($close_tag_end_pos + 1) - $close_tag_start_pos);
											$element["closeTag"] = $closing_tag;
											
											//prepare text node inside of single tag
											$text_node = substr($inner_html, 0, $close_tag_start_pos);
											
											if ( ($trim && trim($text_node)) || (!$trim && $text_node) )
												$element["childNodes"] = array(
													array(
														"nodeType" => 3, //text
														"nodeValue" => $text_node,
													)
												);
											
											//prepare next offset
											$offset = $close_tag_end_pos + 1;
										}
									}
								}
								else if (in_array(strtolower($tag_name), self::$tags_to_jump)) { //if style or script
									//check if composed tag has a closing tag
									$close_tag_start_pos = stripos($html, "</$tag_name", $offset); //get first closing tag offset. Do not use the getHtmlTagFirstEndOffset bc it uses the style and scripts' intervals.
									
									if ($close_tag_start_pos !== false)
										$close_tag_end_pos = stripos($html, ">", $close_tag_start_pos);
									else {
										$close_tag_start_pos = $html_length;
										$close_tag_end_pos = false; //very important to init the close_tag_end_pos to false, otherwise we will have an infinit loop.
									}
									
									$close_tag_end_pos = $close_tag_end_pos !== false ? $close_tag_end_pos : $html_length;
									
									//prepare closing tag
									$closing_tag = substr($html, $close_tag_start_pos, ($close_tag_end_pos + 1) - $close_tag_start_pos);
									$element["closeTag"] = $closing_tag;
									
									//prepare code
									$code = substr($html, $offset, $close_tag_start_pos - $offset);
									$element["childNodes"] = array(
										array(
											"nodeType" => 3, //text
											"nodeValue" => $code,
										)
									);
									
									//prepare next offset
									$offset = $close_tag_end_pos + 1;
								}
								else { //if is composed tag
									//check if composed tag has a closing tag
									$new_end = self::getHtmlTagFirstEndOffset($html, $tag_name, $offset); //get first closing tag offset
									
									if ($new_end !== false)
										$new_end = self::getHtmlTagEndOffset($html, $tag_name, $open_tag_start_pos, $new_end); //be sure that the $new_end position belongs this open tag and not to another inner tag
									
									//if $new_end exists, it means that exists a closing tag, so gets this tag element's children.
									//if no closing tag, one of the behaviours of the browsers and the DomDocument php class (according with the test that I did!), is to automatically close this tag without any inner html. Another behaviour would be to get the html until the end, depending of the tag type and the doctype... Bc is very complicate this process, I leave this choice to the programmer choice according with the $force_no_closing_tag_to_end variable. By default if a tag doesn't have the closing tag, it closes it-self! if $force_no_closing_tag_to_end variable is true, then if a tag doesn't have the closing tag, it closes at the end of the html automatically!
									if ($new_end !== false) {
										//prepare inner html
										$close_tag_end_pos = $new_end;
										$inner_html = substr($html, $offset, ($close_tag_end_pos + 1) - $offset);
										$close_tag_start_pos = strripos($inner_html, "</$tag_name");
										
										//prepare closing tag
										$closing_tag = substr($inner_html, $close_tag_start_pos, ($close_tag_end_pos + 1) - $close_tag_start_pos);
										$element["closeTag"] = $closing_tag;
										
										//prepare children
										$children_html = substr($inner_html, 0, $close_tag_start_pos);
										$element["childNodes"] = self::convertHtmlToElementsArray($children_html, $options);
										
										//prepare next offset
										$offset = $close_tag_end_pos + 1;
									}
									else if ($force_no_closing_tag_to_end) {
										$end_pos = $html_length;
										
										//check tag is a self close tag automatically based in the repeated siblings, and if so finds the next sibling position and close tag before it. This is for the cases where we have multiple LI inside of UL and of the LI elements is not closed! In this case the LI that is not closed, should be closed automatically before the next LI starts.
										$end_pos = self::prepareSiblingTagEndOffsetWhenMissingCloseTag($html, $tag_name, $offset, $end_pos);
										
										//prepare children
										$children_html = substr($html, $offset, $end_pos - $offset);
										$element["childNodes"] = self::convertHtmlToElementsArray($children_html, $options);
										
										//prepare next offset
										$offset = $end_pos; //exit the while bc the there is no html left to parse.
									}
									//else Do nothing and the tag will be closed automatically with no children!
								}
								
								$elements[] = $element;
							}
						}
					}
				}
			}
			while ($open_tag_start_pos !== false);
			
			if ($offset < $html_length) {
				$text_node = substr($html, $offset, $html_length - $offset);
				
				if ( ($trim && trim($text_node)) || (!$trim && $text_node) )
					$elements[] = array(
						"nodeType" => 3, //text
						"nodeValue" => $text_node,
					);
			}
		}
		
		return $elements;
	}
	
	//I can use the php DomDocument class, bc even if the Html is incomplete and not have closed tags, it doesn't matter, bc we only want to parse the html correspondent to the tag attributes.
	public static function getHtmlTagAttributes($html) {
		$attrs = [];
		
		//prepare html and remove the tag_name if exists
		$html = preg_replace("/<([<-z0-9_\-:]+)([^>]*)>/i", "\${2}", $html);
		
		if (substr($html, -1) == "/") //remove last / if exists in case be a single tag
			$html = substr($html, 0, -1);
		
		//parse html with a default div tag
		$html = "<div $html></div>";
		
		$dom = new DOMDocument();
		@$dom->loadHTML($html);
		
		//get attributes
		$divs = $dom->getElementsByTagName('div');
		$div = isset($divs[0]) ? $divs[0] : null;
		
		if ($div && $div->attributes)
			foreach ($div->attributes as $attribute)
				$attrs[ $attribute->nodeName ] = $attribute->nodeValue;
		
		return $attrs;
	}
	
	public static function containsHtmlTagAttributeValue($html, $attr_name, $attr_value, $case_sensitive = false) {
		$attributes = self::getHtmlTagAttributes($html);
		
		foreach ($attributes as $k => $v)
			if ($case_sensitive && ($k == $attr_name && $v == $attr_value) || (!$case_sensitive && strtolower($k) == $attr_name && strtolower($v) == $attr_value))
				return true;
		
		return false;
	}
	
	/*
	 * A good example to test this method is with the $html equals to:
	 	$html = '<title>A
			<title>B
				<title>C</title>
				<title>D</title>
			W</title>
			Z
			<title>JPLPITO</title>
		</title>
		<title>JP</title>';
		
		$tags = HtmlStringHandler::getHtmlTags($html, "title");
		echo "<pre>";print_r($tags);die();
	 */
	//Do NOT use the php DomDocument class, bc the Html may be incomplete and not have closed tags. If I use the DomDocument class it will close the missing html tags. And I don't want this!
	public static function getHtmlTags($html, $tag_name, $force_no_closing_tag_to_end = false) {
		$tags = array();
		
		$offset = 0;
		$html_length = strlen($html);
		$tag_name_length = strlen($tag_name);
		
		do {
			//prepare start offset
			preg_match("/<$tag_name([^>]*)>/i", $html, $matches, PREG_OFFSET_CAPTURE, $offset);
			
			if (!$matches || !$matches[0])
				break;
			
			$start = $matches[0][1];
			$tag = $matches[0][0];
			$is_single_tag = in_array(strtolower($tag_name), self::$single_tags_name);
			
			//check if tag is a single tag
			if (substr($tag, -2) == "/>" || $is_single_tag) {
				$end = $start + strlen($tag);
				
				if ($is_single_tag) {
					//check if there are any close tag for the single tags, this is, check if example the iframe has after any closing tag.
					$end_aux = self::getSingleTagEndPositionIfThereIsACloseTag($html, $tag_name, $end);
					
					//create tag
					if ($end_aux != $end) {
						$end = $end_aux + 1;
						$tag = substr($html, $start, $end - $start);
					}
				}
			}
			else { //for composed tags:
				//prepare end offset
				$new_end = self::getHtmlTagFirstEndOffset($html, $tag_name, $start + $tag_name_length);
				
				if ($new_end !== false)
					$new_end = self::getHtmlTagEndOffset($html, $tag_name, $start, $new_end);
				
				//create tag
				if ($new_end !== false) {
					$end = $new_end + 1;
					$tag = substr($html, $start, $end - $start);
				}
				else { //if no closing tag, sets the $end to the html_length, so it can exit the while statement. But do not break or exit the while statement bc the $tag must be added to the $tags array. 
					
					//if no closing tag, one of the behaviours of the browsers and the DomDocument php class (according with the test that I did!), is to automatically close this tag without any inner html. Another behaviour would be to get the html until the end, depending of the tag type and the doctype... Bc is very complicate this process, I leave this choice to the programmer choice according with the $force_no_closing_tag_to_end variable. By default if a tag doesn't have the closing tag, it closes it-self! if $force_no_closing_tag_to_end variable is true, then if a tag doesn't have the closing tag, it closes at the end of the html automatically!
					if ($force_no_closing_tag_to_end)
						$tag = substr($html, $start, $html_length - $start);
					
					$end = $html_length;
				}
			}
			
			$tags[] = $tag;
			
			//echo "start:$start\n";
			//echo "end:$end\n";
			$offset = $end;
		}
		while (true);
		
		return $tags;
	}
	
	//get the correct end position for a tag, based on a $start position. Note that a tag can have other inner tags, so we need to detect this cases and return the right end position.
	//if the tag doesn't have any closed tag, returns false
	private static function getHtmlTagEndOffset($html, $tag_name, $start, $end) {
		$html_length = strlen($html);
		$tag_name_length = strlen($tag_name);
		$offset = $start + $tag_name_length;
		
		//prepare jump tags intervals
		$intervals = self::getOffsetsIntervalsToJump($html, $offset, $end);
		
		//check if exists inner tags and how many are they
		$inner_tags_counts = 0;
		
		do {
			$pos = stripos($html, "<$tag_name", $offset);
			
			if ($pos === false || $pos >= $end) 
				break;
			
			if (!self::isOffsetInsideOfIntervalsToJump($intervals, $pos)) //only add inner_tags_counts if is not inside of style or script tags
				$inner_tags_counts++;
			
			$offset = $pos + $tag_name_length;
		}
		while (true);
		//echo "end:$end\n";
		//echo "inner_tags_counts:$inner_tags_counts\n";
		
		//prepare right end position
		if ($inner_tags_counts > 0) {
			$offset = $start + $tag_name_length;
			$new_end = $end;
			
			while ($inner_tags_counts >= 0) {
				do {
					$new_end = self::getHtmlTagFirstEndOffset($html, $tag_name, $offset);
					
					//if inside of style or script tags, try to find end position again
					if ($new_end !== false && self::isOffsetInsideOfIntervalsToJump($intervals, $new_end))
						$offset = $new_end + $tag_name_length;
					else
						break;
				}
				while (true);
				
				if ($new_end === false || $new_end >= $html_length)
					break;
				
				$offset = $new_end;
				$inner_tags_counts--;
			}
			
			if ($new_end === false)
				return false;
			
			if ($end != $new_end)
				$end = self::getHtmlTagEndOffset($html, $tag_name, $start, $new_end);
		}
		//else leave the $end bc is already the right position
		
		return $end; //return numeric value or false if not exists
	}
	
	//if the tag doesn't have any closed tag, returns false
	private static function getHtmlTagFirstEndOffset($html, $tag_name, $start) {
		//prepare end offset
		$end = stripos($html, "</$tag_name", $start);
		
		if ($end !== false) {
			$intervals = self::getOffsetsIntervalsToJump($html, $start, $end);
			
			if (self::isOffsetInsideOfIntervalsToJump($intervals, $end)) //if is inside of style or script tags gets new offset
				$end = self::getHtmlTagFirstEndOffset($html, $tag_name, $end + strlen($tag_name));
			
			if ($end !== false) 
				$end = stripos($html, ">", $end);
		}
		
		//note: if $end is === false, it should return false. It should not return $html_length
		
		return $end; //return numeric value or false if not exists
	}
	
	private static function getSingleTagEndPositionIfThereIsACloseTag($html, $tag_name, $tag_end_pos) {
		//check if there are any closing tag for the single tags, this is, check if example the iframe has after any closing tag.
		$pos = strpos($html, "<", $tag_end_pos);
		
		//if exists a < starts with a letter it means there is a html tag and if that tag is == $tag_name and is a closing tag
		if ($pos !== false) {
			$sub_html = substr($html, $pos);
			
			//it means it could be a normal text like "4 < 5". In this case we need to find the next one, so we call this method again but with a different position.
			if (!preg_match("/^<\/?[a-z_]/i", $sub_html)) {
				$new_pos = self::getSingleTagEndPositionIfThereIsACloseTag($html, $tag_name, $pos + 1);
				
				if ($pos + 1 != $new_pos) //returned position "$new_pos" is different than the argument position: "$pos"
					$tag_end_pos = $new_pos;
			}
			else if (preg_match("/^<\/$tag_name/i", $sub_html)) {
				preg_match("/^<\/$tag_name([^>]*)>/i", $sub_html, $matches_3, PREG_OFFSET_CAPTURE);
				$tag_end_pos = $pos + strlen($matches_3[0][0]) - 1;
			}
		}
		
		return $tag_end_pos;
	}
	
	private static function prepareSiblingTagEndOffsetWhenMissingCloseTag($html, $tag_name, $start, $end) {
		//check tag is a self close tag automatically based in the repeated siblings, and if so finds the next sibling position and close tag before it. This is for the cases where we have multiple LI inside of UL and of the LI elements is not closed! In this case the LI that is not closed, should be closed automatically before the next LI starts.
		if (in_array($tag_name, self::$auto_close_siblings_tag_name) || self::$auto_close_siblings_tag_name[$tag_name]) {
			$siblings_tag_name = self::$auto_close_siblings_tag_name[$tag_name];
			$siblings_tag_name = is_array($siblings_tag_name) ? $siblings_tag_name : array( (trim($siblings_tag_name) ? $siblings_tag_name : $tag_name) );
			
			$next_open_tag_start_pos = false;
			$siblings_intervals = self::getOffsetsIntervalsToJump($html, $start, $end);
			
			foreach ($siblings_tag_name as $sibling_tag_name) {
				$siblings_offset = $start;
				$sibling_tag_name = trim($sibling_tag_name);
				
				if ($sibling_tag_name) {
					while (true) {
						$pos = stripos($html, "<" . $sibling_tag_name, $siblings_offset);
						
						if ($pos !== false) {
							if (self::isOffsetInsideOfIntervalsToJump($siblings_intervals, $pos))  //if is inside of style or script tags gets new offset
								$siblings_offset = $pos + strlen($sibling_tag_name);
							else if ($pos < $next_open_tag_start_pos || $next_open_tag_start_pos === false) {
								$next_open_tag_start_pos = $pos;
								break;
							}
							else
								break;
						}
						else
							break;
					}
				}
			}
				
			if ($next_open_tag_start_pos !== false)
				$end = $next_open_tag_start_pos;
		}
		
		return $end;
	}
	
	private static function isOffsetInsideOfIntervalsToJump($intervals, $offset) {
		foreach ($intervals as $interval) {
			$start = isset($interval[0]) ? $interval[0] : null;
			$end = isset($interval[1]) ? $interval[1] : null;
			
			if ($offset > $start && $offset < $end)
				return true;
		}
		
		return false;
	}
	
	private static function getOffsetsIntervalsToJump($html, $start, $end) {
		$intervals = array();
		$html_length = strlen($html);
		
		foreach (self::$tags_to_jump as $tag_to_jump) {
			$offset = $start;
			$tag_to_jump_length = strlen($tag_to_jump);
			
			while($offset < $end) {
				$tag_to_jump_start_pos = stripos($html, "<$tag_to_jump", $offset);
				
				if ($tag_to_jump_start_pos !== false && $tag_to_jump_start_pos < $end) {
					$tag_to_jump_end_pos = self::getHtmlTagFirstEndOffset($html, $tag_to_jump, $tag_to_jump_start_pos + $tag_to_jump_length);
					$tag_to_jump_end_pos = $tag_to_jump_end_pos === false ? $html_length : $tag_to_jump_end_pos;
					
					$intervals[] = array($tag_to_jump_start_pos, $tag_to_jump_end_pos);
					
					$offset = $tag_to_jump_end_pos + 1;
				}
				else
					break;
			}
		}
		
		return $intervals;
	}
}
?>
