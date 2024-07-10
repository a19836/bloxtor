/*
 * Copyright (c) 2007 PHPMyFrameWork - Joao Paulo Lopes Pinto -- http://jplpinto.com
 * The use of this code must comply with the Bloxtor framework license.
 */

//Beautify html, css and js, including ptl code

if (!String.prototype.startsWith) {
  String.prototype.startsWith = function(searchString, position) {
    position = position || 0;
    return this.indexOf(searchString, position) === position;
  };
}

var MyHtmlBeautify = {
	alert_errors : false,
	indent : "",
	
	single_html_tags : ["meta", "base", "basefont", "input", "img", "link", "br", "wbr", "hr", "frame", "area", "source", "track", "circle", "col", "embed", "param"],
	
	single_ptl_tags : ["definevar", "var", "incvar", "decvar", "joinvar", "concatvar", "echo", "print", "return", "break", "die", "require", "include", "include_once", "require_once", "throw", "code"],
	
	dec_prefix_ptl_tags : ["elseif", "else", "catch", "case", "default"],
	
	close_single_ptl_tags : false,
	
	beautify : function (html, options) {
		/* #ADD_SECURITY_CODE_HERE# */  //Important: do not remove this comment because it will be used by the other/scripts/obfuscate_js_files.php to only allow this code in the allowed domains.
		
		var new_html = "";
		
		//html = '<?= "Hello" ?><!-- This is only a test --><ptl:if \$is_insertion><div class="form-group article_id hidden"><div class="form-input"><input type="hidden" class="form-control " value="<ptl:echo str_replace(\'"\', \'&quot;\', (\$input[article_id] )) />" name="article_id" /><? $x = 2;$y = "jp";?></div></div><ptl:else><div class="form-group article_id"><label class="form-label control-label ">Article Id: </label><div class="form-input"><span class="form-control "><ptl:echo \$input[article_id] /></span></div></div></ptl:if><?php echo "$x"; $y .= "into"; echo $y;?><div class="form-group title"><label class="form-label control-label ">Title: </label><div class="form-input"><input type="text" class="form-control " value="<ptl:echo str_replace(\'"\', \'&quot;\', (\$input[title] )) />" name="title" data-allow-null="0" /></div></div><div class="form-group sub_title"><label class="form-label control-label ">Sub Title: </label><div class="form-input"><input type="text" class="form-control " value="<ptl:echo str_replace(\'"\', \'&quot;\', (\$input[sub_title] )) />" name="sub_title" data-allow-null="0" /></div></div><div class="form-group published"><label class="form-label control-label ">Published: </label><div class="form-input"><div class="checkbox"><label><input type="checkbox" class="" name="published" data-allow-null="1" value="1"<ptl:var:opt_value 1><ptl:var:selected_value \$input[published] /><ptl:if \$opt_value == \$selected_value><ptl:echo checked/></ptl:if>/></label></div></div></div><div class="form-group tags"><label class="form-label control-label ">Tags: </label><div class="form-input"><input type="text" class="form-control " value="<ptl:echo str_replace(\'"\', \'&quot;\', (\$input[tags] )) />" name="tags" data-allow-null="0" /></div></div><div class="form-group photo_id hidden"><div class="form-input"><input type="hidden" class="form-control " value="<ptl:echo str_replace(\'"\', \'&quot;\', (\$input[photo_id] )) />" name="photo_id" /></div></div><div class="form-group photo_file"><label class="form-label control-label ">Photo: </label><div class="form-input"><input type="file" class="form-control " value="" name="photo" data-allow-null="1" /></div></div><ptl:if \$photo_url><div class="form_field photo_url"><ptl:var:photo_url_time str_replace(\'"\', \'&quot;\', \$photo_url) . (strpos(\$photo_url, "?") !== false ? "&" : "?") . "t=" . time() /><a href="<ptl:echo \$photo_url_time />" target="photo"><div class="form-group"><div class="form-input"><img class="form-control" src="<ptl:echo \$photo_url_time />" onError="deletePhoto(\$(this).parent().closest(\'.photo_url\').find(\'.photo_remove\')[0])" alt="<ptl:echo str_replace(\'"\', \'&quot;\', translateProjectText(\$EVC, "No Photo")) />" /></div></div></a><a class="photo_remove" onClick="deletePhoto(this)"><ptl:echo str_replace(\'"\', \'&quot;\', translateProjectText(\$EVC, "Remove this photo")) /></a></div></ptl:if><div class="form-group summary"><label class="form-label control-label ">Summary: </label><div class="form-input"><textarea class="form-control " name="summary" data-allow-null="0"><ptl:echo str_replace(\'</textarea\', \'&lt;/textarea\', (\$input[summary] )) /></textarea></div></div><div class="form-group content"><label class="form-label control-label ">Content: </label><div class="form-input"><textarea class="form-control " name="content" data-allow-null="0"><ptl:echo str_replace(\'</textarea\', \'&lt;/textarea\', (\$input[content] )) /></textarea></div></div><div class="form-group allow_comments"><label class="form-label control-label ">Allow Comments: </label><div class="form-input"><div class="checkbox"><label><input type="checkbox" class="" name="allow_comments" data-allow-null="1" value="1"<ptl:var:opt_value 1><ptl:var:selected_value \$input[allow_comments] /><ptl:if \$opt_value == \$selected_value><ptl:echo checked/></ptl:if>/></label></div></div></div><ptl:echo \$attachments_html /><style type="text/css">.asd{width:10px;}</style><script type="text/javascript">function foo(x) {if (x)return x;}</script>';
		
		if (html) {
			html = "" + html;
			var char, tag_html, is_tag_close, is_single_html_tag, tag_name, tag_name_lower, prev_tag_name, is_tag_dec, is_style, is_script, is_textarea, is_br, is_tag, is_ptl, is_short_tag, is_single_ptl_tag, prefix = "", code, code_length;
			
			//Preparing indent string
			var indent_size = options && parseInt(options.indent_size) >= 0 ? parseInt(options.indent_size) : 1;
			var indent_char = options && options.indent_char ? options.indent_char : "\t";
			this.indent = "";
			for (var i = 0; i < indent_size; i++) 
				this.indent += indent_char;
			
			//Prepare html indentation
			var html_length = html.length;
			for (var i = 0; i < html_length; i++) {
				char = html[i];
				
				if (char == '<') {
					if (this.isComment(html, i)) { //parse comment if exists
						tag_name = "<!--";
						tag_html = this.getComment(html, i);
						
						//only add end line + prefix, if code is a tag and is inside of another tag. Note that if the parent tag is a "display:inline" it will show the end line in the browser, so we want to avoid this cases.
						if (new_html.match(/>\s*$/)) {
							//checks if there isn't any end line in the previous chars
							if (new_html != "" && !new_html.match(/\n\s*$/))
								new_html += "\n";
							
							new_html += prefix;
						}
						
						new_html += tag_html[0];
						
						//checks if there isn't any end line in the next chars but only if the next char is a tag name
						var c = html.substr(tag_html[1] + 1);
						if (c != "" && !c.match(/^\s*\n/) && !tag_html[0].match(/\n\s*$/) && c.match(/^\s*</))
							new_html += "\n";
						
						i = tag_html[1];
					}
					else if (this.isPHP(html, i)) { //parse php code if exists
						tag_name = "<?";
						tag_html = this.getPHP(html, i);
						code = tag_html[0];
						
						try {
							if (typeof MyCodeBeautifier != "undefined" && MyCodeBeautifier && typeof MyCodeBeautifier.prettyPrint == "function") {
								//console.log(code);
								code = MyCodeBeautifier.prettyPrint(code);
								code = code.replace(/\n/g, "\n" + prefix);
								//console.log(code);
							}
						}
						catch(e) {
							if (alert_errors)
								alert(e);
						
							if (console && typeof console.log == "function")
								console.log(e);
						}
						
						//only add end line + prefix, if code is a tag and is inside of another tag. Note that if the parent tag is a "display:inline" it will show the end line in the browser, so we want to avoid this cases.
						if (new_html.match(/>\s*$/)) {
							//checks if there isn't any end line in the previous chars
							if (new_html != "" && !new_html.match(/\n\s*$/))
								new_html += "\n";
							
							new_html += prefix;
						}
						
						new_html += code;
						
						//checks if there isn't any end line in the next chars but only if the next char is a tag name
						var c = html.substr(tag_html[1] + 1);
						if (c != "" && !c.match(/^\s*\n/) && !code.match(/\n\s*$/)&& c.match(/^\s*</))
							new_html += "\n";
						
						i = tag_html[1];
					}
					else if (!this.isTagHtml(html, i)) { //check if html or ptl tag. This could be a simple text
						new_html += char;
						tag_name = "";
					}
					else {
						is_tag = true;
						is_ptl = this.isPTL(html, i);
						
						//getting tag
						tag_html = is_ptl ? this.getPTL(html, i) : this.getTagHtml(html, i, prefix + this.indent);
						code = tag_html[0];
						
						if (code) {
							tag_name = is_ptl ? this.getPTLTagName(code, 0) : this.getTagName(code, 0);
							tag_name_lower = tag_name.toLowerCase();
							i = tag_html[1];
							//console.log("TAG_HTML:"+code);
							
							//preparing tag
							code_length = code.length;
							
							is_tag_close = code.substr(0, 2) == "</";
							is_tag_dec = this.isDecrementPrefixPTLTag(code);
							is_single_html_tag = this.isSingleHtmlTag(code);
							is_short_tag = code.substr(code_length - 2) == "/>";
							is_single_ptl_tag = this.isSinglePTLTag(code);
							is_style = tag_name_lower == "style";
							is_script = tag_name_lower == "script";
							is_textarea = tag_name_lower == "textarea";
							is_br = tag_name_lower == "br";
							
							if (is_tag_close  || is_tag_dec)
								prefix = prefix.substr(0, prefix.length - 1);
							
							//make sure that the html respects all the closing tags as XML.
							if (is_ptl && !is_tag_close && this.close_single_ptl_tags) {
								if (is_short_tag)
									code = code.substr(0, code_length - 2) + "></" + tag_name + ">";
								else if (is_single_ptl_tag) {
									var next_html = html.substr(i + 1).replace(/^\s+/g, "");
									var patt = new RegExp("^</" + tag_name + "( |>)", "i");
									
									//check if already exists </ptl...>, and if not, add closing tags
									if (!patt.test(next_html))
										code = code.substr(0, code_length - 1) + "></" + tag_name + ">";
								}
								
								code_length = code.length;
							}
							
							if (is_tag_close && is_textarea) //Do not indent the content of textareas
								 new_html += code;
							else {
								//ignores the cases with empty lines with only spaces and only \n
								if (new_html.match(/\n\s+$/))
									new_html = new_html.substr(0, new_html.lastIndexOf("\n"));
								
								//if html node is empty, closes in the same line.
								if (is_tag_close && tag_name && tag_name == prev_tag_name) {
									new_html += code;
									tag_name = ""; //reset the tag_name, so the prev_tag_name can be reset too. In case the parent node have the same name (<div><div></div></div>), it will not enter here again when running the parent closing tag (</div>)!
								}
								else {
									//only add end line + prefix, if code is a tag and is inside of another tag. Note that if the parent tag is a "display:inline" it will show the end line in the browser, so we want to avoid this cases.
									if (new_html.match(/>\s*$/) && code[0] == "<")
										new_html += (!new_html || new_html.substr(new_html.length - 1) == "\n" ? "" : "\n") + prefix;
									
									new_html += code;
									
									//add end line + prefix, if code is br tag
									if (is_br)
										new_html += "\n"/* + prefix*/; //Do not add prefix when is a br otherwise the browser and the LayoutUIEditor will convert the prefix in &emsp; Simply add a new end-line. Do not add anything else.
								}
							}
							
							//check if is open tag 
							if (!is_tag_close && code.substr(code_length - 1) == ">" && !is_short_tag && !is_single_ptl_tag && !is_single_html_tag)
								prefix += this.indent;
							
							//preparing style and script code
							if (!is_tag_close && (is_style || is_script || is_textarea)) {
								tag_html = this.getNonParseInnerTagsNodeContent(html, i + 1, tag_name);
								code = tag_html[0];
								i = tag_html[1];
								
								//console.log(tag_name_lower+":" + code);
								
								try {
									if (is_style && typeof css_beautify == "function")
										code = css_beautify(code, {'indent_size' : indent_size, 'indent_char' : indent_char});
									if (is_script && typeof js_beautify == "function")
										code = js_beautify(code, {'indent_size' : indent_size, 'indent_char' : indent_char});
									
									//console.log(code);
									/*
									Solve the cases where there are php variables inside of the css of javascript code like following code:
										console.log({$x});
										console.log({\$x});
									
									Without the code bellow the code above wil look like:
										console.log({
											$x
										});
										console.log({\
											$x
										});
									*/
									//code = ("" + code).replace(/\{\s*(\\?)\s*(\$[\p{L}\w \[\]"'\-\+\.\$]+)\s*\}/gu, "\{$1$2\}"); //'\w' means all words with '_' and '/u' means with accents and ç too. Cannot use this bc it does not work in IE.
									code = ("" + code).replace(/\{\s*(\\?)\s*(\$[\w\u00C0-\u00D6\u00D8-\u00F6\u00F8-\u024F\u1EBD\u1EBC"' \-\+\[\]\.\$]+)\s*\}/g, "\{$1$2\}"); //'\w' means all words with '_' and 'u' means with accents and ç too.
									//console.log(code);
								}
								catch(e) {
									if (alert_errors)
										alert(e);
								
									if (console && typeof console.log == "function")
										console.log(e);
								}
								
								//only add end line + prefix, if code is a tag and is inside of another tag. Note that if the parent tag is a "display:inline" it will show the end line in the browser, so we want to avoid this cases.
								if (!is_textarea && new_html.match(/>\s*$/) && code[0] == "<") //Do not indent the content of textareas
									new_html += (!new_html || new_html.substr(new_html.length - 1) == "\n" ? "" : "\n");
								
								new_html += code;
								
								if (!is_textarea)
									tag_name = "";
							}
						}
						else
							i = html_length;
					}
					
					prev_tag_name = tag_name;
				}
				else if (char != "\n" && char != "\r" && char != "\t") {
					var is_multiple_spaces = char.match(/\s/) && new_html.substr(new_html.length - 1).match(/\s/); //avoid the case where we will have empty lines and multiple spaces together
					
					if (!is_multiple_spaces) {
						//console.log("before new_html:"+new_html);
						if (is_tag)
							new_html += !new_html.match(/\n\s*$/) && new_html.match(/>\s*$/) && char == "<" ? "\n" + prefix : ""; //only add \n if there is not one yet.
						
						new_html += char; //do not add prefix or end-line otherwise if the parent tag is a "display:inline" it will show the end line in the browser.
					}
					
					if (!char.match(/\s/))
						prev_tag_name = "";
					
					is_tag = false;
				}
			}
		}
		//console.log("**************************************");
		//console.log(new_html);
		
		return new_html;
	},
	
	//idx must be the position where the html tag starts, this is, where the "<" is, so then the getTagHtml function parses after the "<"
	getTagHtml : function (html, idx, prefix) {
		var tag_html = html[idx];
		var char, attr, ptl = [], php = [], comment = [], odq = false, osq = false, is_tag_close, is_tag_dec;
		
		for (var i = idx + 1, t = html.length; i < t; i++) {
			char = html[i];
			
			if (char == '"' && !osq && !this.isCharEscaped(html, i))
				odq = !odq;
			else if (char == "'" && !odq && !this.isCharEscaped(html, i))
				osq = !osq;
			else if (char == "<") {
				if (this.isComment(html, i)) {
					comment = this.getComment(html, i);
					i = comment[1];
					tag_html += comment[0];
					
					continue;
				}
				else if (this.isPHP(html, i)) {
					php = this.getPHP(html, i);
					i = php[1];
					tag_html += php[0];
					
					continue;
				}
				else if (this.isPTL(html, i)) {
					ptl = this.getPTL(html, i);
					i = ptl[1];
					
					//only if is not inside of attribute, otherwise we are messing the attributes with wrong values by including the tabs, end lines or any other prefix.
					if (!odq && !osq) {
						is_tag_close = ptl[0].substr(0, 2) == "</";
						is_tag_dec = this.isDecrementPrefixPTLTag(ptl[0]);
						
						if (is_tag_close || is_tag_dec)
							prefix = prefix.substr(0, prefix.length - 1);
						
						//console.log("PTL: " + ptl[0]);
						tag_html += (tag_html.substr(tag_html.length - 1) == "\n" ? "" : "\n") + prefix + ptl[0];
						
						if (!is_tag_close && ptl[0].substr(ptl[0].length - 1) == ">" && ptl[0].substr(ptl[0].length - 2) != "/>" && !this.isSinglePTLTag(ptl[0]))
							prefix += this.indent;
					}
					
					continue;
				}
			}
			
			if (char != "\n" && char != "\r" && char != "\t") {
				if (ptl[0] && !odq && !osq) //only if is not inside of attribute, otherwise we are messing the attributes with wrong values by including end lines.
					tag_html += "\n" + prefix;
				
				tag_html += char;
				ptl = [];
			}
			
			if (char == ">" && !odq && !osq)
				break;
			else if ((char == '"' && odq) || (char == "'" && osq)) {
				attr = this.getAttribute(html, i + 1, char);
				tag_html += attr[0];
				i = attr[1];
				//console.log("ATTR:"+attr[0]);
			}
		}
		
		//console.log("TAG HTML:"+tag_html);
		return [tag_html, i];
	},
	
	//
	/*
	 * parse an html like: 
	 * this.getAttributes('<div x=y w="as" foo==="sdf""as" ass" bar="as""as"></div>', 4, ">")
	 * this.getAttributes(' x=y w="as" foo==="sdf""as" ass" bar="as""as"')
	 * this.getAttributes('<div x=y w="as" foo==="sdf""as" ass" bar="as""as" <?php echo "x=12"; ?> x=<?php echo "x=12"; ?> x=12></div>', 4, ">");
	 *
	 * return attributes: {x: 'y', w: '"as"', foo: '=="sdf""as"', 'ass"': '', bar: '"as""as"', '<?php echo "x=12"; ?>': '', x: '<?php echo "x=12"; ?>', x: 12}, which is the default behaviour of browsers
	 */
	getAttributes : function (html, idx, delimiter) {
		var attributes = [];
		var char, ptl, php, odq = false, osq = false, open_attr_value = false, attr_name = "", attr_value = "", first_char = null, last_char = null;
		
		if (!idx)
			idx = 0;
		
		var add_new_attribute_handler = function() {
			//console.log("add_new_attribute_handler:");
			//console.log("attr_name:"+attr_name);
			//console.log("attr_value:"+attr_value);
			
			if (attr_name.length > 0) {
				//save attr_name
				attributes.push({
					name: attr_name,
					value: ""
				});
				
				attr_name = ""; //reset attr_name
			}
			
			if (attr_value.length > 0) {
				//save attr_value
				if (attributes.length > 0)
					attributes[attributes.length - 1]["value"] += attr_value;
				else //this case should not happen, but just in case we still put it here
					attributes.push({
						name: "",
						value: attr_value
					});
				
				open_attr_value = false; //reset open_attr_value
				attr_value = ""; //reset attr_value
			}
		};
		
		for (var i = idx, t = html.length; i < t; i++) {
			char = html[i];
			
			if (char == '"' && !osq && open_attr_value && !this.isCharEscaped(html, i)) //only if attr_value is open, otherwise should treat the quote char as a normal char. This is the default behaviour of browsers.
				odq = !odq;
			else if (char == "'" && !odq && open_attr_value && !this.isCharEscaped(html, i)) //only if attr_value is open, otherwise should treat the quote char as a normal char. This is the default behaviour of browsers.
				osq = !osq;
			else if (char == "<" && !odq && !osq) {
				if (this.isPTL(html, i)) {
					ptl = this.getPTL(html, i);
					char = ptl[0];
					i = ptl[1];
				}
				else if (this.isPHP(html, i)) {
					php = this.getPHP(html, i);
					char = php[0];
					i = php[1];
				}
			}
			
			//if quotes are opened
			if ((char == '"' && odq) || (char == "'" && osq)) {
				attr = this.getAttribute(html, i + 1, char);
				i = attr[1];
				
				//prepare attr_name/attr_value with quotes. Do not add the last char, bc we will add it below, if the char really exists... Otherwise leave the attr_name/attr_value like this, so the user can see what he did wrong. Note that we have some ptl or php quote, this may then be correct, so we should NOT correct the missing quote here, leaving these cases for the user.
				if (open_attr_value)
					attr_value += char + attr[0];
				else //The else must be always appended to attr_name, otherwise we will loose some chars.
					attr_name += char + attr[0];
			}
			else if ((char == '"' && !odq) || (char == "'" && !osq)) { //this must be before the 'else if (!odq && !osq)'
				if (open_attr_value) {
					attr_value += char;
					
					//if attr_value already exists and is encapsulated with quotes, then save it and start a new attribute, otherwise continue appending to the attr_value var
					if (attr_value.length >= 2) {
						first_char = attr_value[0];
						last_char = attr_value[attr_value.length - 1];
						
						if (first_char == char && last_char == char)
							add_new_attribute_handler();
					}
				}
				else
					attr_name += char;
			}
			else if (!odq && !osq) {
				if (delimiter && char == delimiter)
					break;
				else if (char.match(/^\s$/)) //char.match(/^\s$/) => is white space. The '^' and '$' are very important bc if exists php or ptl, it cannot enter here.
					add_new_attribute_handler();
				else if (char == "=" && attr_name.length > 0 && !open_attr_value) //only if attr_name and open_attr_value. The open_attr_value here is very important bc if we an html like this: 'foo==', then this method should return [{name: "foo", value: "="}]. However if you have an html: 'foo===="', then this method should return [{name: "foo", value: "===\""}]. This is what happens with the browsers
					open_attr_value = true;
				else if (open_attr_value)
					attr_value += char;
				else //The else must be always appended to attr_name, otherwise we will loose some chars.
					attr_name += char;
			}
			//else: there is no else bc all cases are covered above, or they should be...
		}
		
		add_new_attribute_handler();
		
		for (var i = 0, t = attributes.length; i < t; i++) {
			attr_value = attributes[i]["value"];
			attributes[i]["raw_value"] = attr_value;
			
			if (attr_value.length >= 2) {
				first_char = attr_value[0];
				last_char = attr_value[attr_value.length - 1];
				
				if ((first_char == '"' && last_char == '"') || (first_char == "'" && last_char == "'"))
					attributes[i]["value"] = attr_value.substr(1, attr_value.length - 2);
			}
		}
		
		return attributes;
	},
	
	getAttribute : function (html, idx, delimiter) {
		var char, ptl, php;
		
		for (var i = idx, t = html.length; i < t; i++) {
			char = html[i];
			
			if (char == '"' && delimiter == char && !this.isCharEscaped(html, i))
				break;
			else if (char == "'" && delimiter == char && !this.isCharEscaped(html, i))
				break;
			else if (char == "<") {
				if (this.isPTL(html, i)) {
					ptl = this.getPTL(html, i);
					i = ptl[1];
				}
				else if (this.isPHP(html, i)) {
					php = this.getPHP(html, i);
					i = php[1];
				}
			}
		}
		
		//console.log("ATTR:"+html.substr(idx - 1, i - idx + 2)+"===>"+html.substr(idx, i - idx));
		var attr = i == html.length ? html.substr(idx) : html.substr(idx, i - idx);
		return [attr, i == html.length ? i : i - 1];
	},
	
	convertAttributesToHtml : function(attributes) {
		var html = '';
		
		if (attributes && attributes.length)
			for (var i = 0, ti = attributes.length; i < ti; i++) {
				var attribute = attributes[i];
				var attr_name = attribute.name;
				var attr_value = attribute.value !== null || attribute.value !== undefined ? "" + attribute.value : "";
				
				//TODO: escape attr value for non ptl and php values
				//attr_value.replace(/"/, '\\"'); //escape double quotes
				
				var new_attr_value = "";
				var char, ptl, php;
				//console.log(repeated_values);
				
				//escape quotes so we can create correctly the html
				for (var j = 0, tj = attr_value.length; j < tj; j++) {
					char = attr_value[j];
					
					if (char == "<") {
						if (this.isPTL(attr_value, j)) {
							ptl = this.getPTL(attr_value, j);
							char = ptl[0];
							j = ptl[1];
						}
						else if (this.isPHP(attr_value, j)) {
							php = this.getPHP(attr_value, j);
							char = php[0];
							j = php[1];
							//console.log("PHP CODE:"+char);
						}
					}
					else if (char == '"' && !this.isCharEscaped(attr_value, j))
						char = '\\' + char;
					
					new_attr_value += char;
				}
				
				attr_value = new_attr_value;
				
				html += (html ? " " : "") + attr_name + (attr_value.length ? "=\"" + attr_value + "\"" : "");
			}
		
		return html;
	},
	
	convertAttributesToObject : function(attributes) {
		var obj = {};
		
		if (attributes && attributes.length) {
			for (var i = 0, t = attributes.length; i < t; i++) {
				var attribute = attributes[i];
				var attr_name = attribute.name;
				var attr_value = attribute.value !== null || attribute.value !== undefined ? "" + attribute.value : "";
				
				obj[attr_name] = attr_value;
			}
		}
		
		return obj;
	},
	
	joinAttributes : function(attributes_1, attributes_2) {
		if (attributes_2 && attributes_2.length) {
			var attributes_2_obj = this.convertAttributesToObject(attributes_2);
			
			if (!attributes_1 || typeof attributes_1.length == "undefined")
				attributes_1 = [];
			else
				for (var i = 0, t = attributes_1.length; i < t; i++) {
					var attribute = attributes_1[i];
					var attr_name = attribute.name;
					var attr_value = attribute.value;
					
					if (attributes_2_obj.hasOwnProperty(attr_name)) {
						if (attributes_2_obj[attr_name].length)
							attr_value += (attr_value.length && !attr_value[attr_value.length - 1].match(/\s/) ? " " : "") + attributes_2_obj[attr_name];
						
						attributes_2_obj[attr_name] = null;
						delete attributes_2_obj[attr_name];
					}
					
					attributes_1[i].value = attr_value;
				}
			
			//add other attributes in body_attributes_obj
			for (var attr_name in attributes_2_obj)
				attributes_1.push({
					name: attr_name,
					value: attributes_2_obj[attr_name]
				});
		}
		
		return attributes_1;
	},
	
	joinAttributesHtmls : function(attributes_html_1, attributes_html_2) {
		var attributes_1 = this.getAttributes(attributes_html_1, 0, null);
		var attributes_2 = this.getAttributes(attributes_html_2, 0, null);
		
		var attributes = this.joinAttributes(attributes_1, attributes_2);
		
		return this.convertAttributesToHtml(attributes);
	},
	
	diffAttributes : function(attributes_1, attributes_2) {
		if (attributes_1 && attributes_1.length && attributes_2 && attributes_2.length) {
			var attributes_2_obj = this.convertAttributesToObject(attributes_2);
			var new_attributes = [];
			
			for (var i = 0, ti = attributes_1.length; i < ti; i++) {
				var attribute = attributes_1[i];
				var attr_name = attribute.name;
				var attr_value = attribute.value;
				
				if (attr_value.length && attributes_2_obj.hasOwnProperty(attr_name) && attributes_2_obj[attr_name].length) {
					var repeated_values = attributes_2_obj[attr_name].split(" ");
					var new_attr_value = "";
					var char, ptl, php, part = "";
					//console.log(repeated_values);
					
					//remove duplicates
					for (var j = 0, tj = attr_value.length; j < tj; j++) {
						char = attr_value[j];
						
						if (char == "<") {
							if (this.isPTL(attr_value, j)) {
								ptl = this.getPTL(attr_value, j);
								char = ptl[0];
								j = ptl[1];
							}
							else if (this.isPHP(attr_value, j)) {
								php = this.getPHP(attr_value, j);
								char = php[0];
								j = php[1];
								//console.log("PHP CODE:"+char);
							}
						}
						else if (char.match(/^\s$/)) { //char.match(/^\s$/) => is white space. The '^' and '$' are very important bc if exists php or ptl, it cannot enter here.
							//console.log(part);
							if (repeated_values.indexOf(part) != -1) { //is duplicated
								new_attr_value = new_attr_value.substr(0, new_attr_value.length - part.length); //remove duplicated value
								char = "";
							}
							
							part = "";
						}
						else
							part += char;
						
						new_attr_value += char;
					}
					
					if (part && repeated_values.indexOf(part) != -1)
						new_attr_value = new_attr_value.substr(0, new_attr_value.length - part.length); //remove duplicated value
					
					if (attr_value != new_attr_value)
						attr_value = new_attr_value;
					
					if (attr_value.length == 0)
						continue;
					
					attributes_1[i].value = attr_value;
				}
				
				new_attributes.push(attributes_1[i]);
			}
			
			attributes_1 = new_attributes;
		}
		
		return attributes_1;
	},
	
	diffAttributesHtmls : function(attributes_html_1, attributes_html_2) {
		var attributes_1 = this.getAttributes(attributes_html_1, 0, null);
		var attributes_2 = this.getAttributes(attributes_html_2, 0, null);
		
		var attributes = this.diffAttributes(attributes_1, attributes_2);
		
		return this.convertAttributesToHtml(attributes);
	},
	
	getPTL : function (html, idx) {
		var res = this.getNodeContent(html, idx, ">", "php"); //if there is a php element inside of the ptl element, the getNodeContent should parse the php first.
		var i = res[1] + 1 < html.length ? res[1] + 1 : html.length; //i + 1: bc > contains 1 char.
		var ptl = res[0] + html.substr(res[1] + 1, 1);
		
		return [ptl, i];
	},
	
	getPHP : function (html, idx) {
		var res = this.getNodeContent(html, idx, "?>");
		var i = res[1] + 2 < html.length ? res[1] + 2 : html.length; //i + 2: bc ?> contains 2 chars.
		var php = res[0] + html.substr(res[1] + 1, 2);
		
		return [php, i];
	},
	
	getComment : function (html, idx) {
		var end = html.indexOf("-->", idx + 1);
		var comment = end > idx ? html.substr(idx, end + 3 - idx) : html.substr(idx); //end + 3: bc --> contains 3 chars and is length.
		
		idx = end > idx ? end + 2 : html.length; //end + 2: bc --> contains 3 chars.
		
		return [comment, idx];
	},
	
	//to get the attributes in a string inside of a tag node
	getAttributesContent : function (html, idx, stop_str) {
		return this.getNodeContent(html, idx, stop_str);
	},
	
	//to get the content inside of: script, style and textarea nodes
	getNonParseInnerTagsNodeContent : function (html, idx, tag_name) { //if there is a php or ptl element inside of the style/script/textarea element, the getNodeContent should parse the the php or ptl first.
		return this.getNodeContent(html, idx, "</" + tag_name, ["php", "ptl"], tag_name.toLowerCase() == "textarea");
	},
	
	//get a node content until it finds the stop_str. Be careful using this method for textarea. For textarea you should use the getNonParseInnerTagsNodeContent instead.
	getNodeContent : function (html, idx, stop_str, ignore_elements, is_textarea) {
		var char, odq = false, osq = false;
		stop_str = stop_str.toLowerCase();
		
		ignore_elements = ignore_elements ? (Array.isArray(ignore_elements) ? ignore_elements : [ignore_elements]) : [];
		
		for (var i = idx, t = html.length; i < t; i++) {
			char = html[i];
			
			if (char == '"' && !is_textarea && !osq && !this.isCharEscaped(html, i))
				odq = !odq;
			else if (char == "'" && !is_textarea && !odq && !this.isCharEscaped(html, i))
				osq = !osq;
			else if (char == "<" && ignore_elements) {
				if (ignore_elements.indexOf("ptl") != -1 && this.isPTL(html, i)) {
					ptl = this.getPTL(html, i);
					i = ptl[1];
				}
				else if (ignore_elements.indexOf("php") != -1 && this.isPHP(html, i)) {
					php = this.getPHP(html, i);
					i = php[1];
				}
			}
			
			if (char == stop_str[0] && !odq && !osq && html.substr(i, stop_str.length).toLowerCase() == stop_str)
				break;
		}
		
		//console.log("content:"+html.substr(idx, i - (idx - 1)));
		var content = i == html.length ? html.substr(idx) : html.substr(idx, i - idx);
		idx = i == html.length ? i : i - 1;
		
		return [content, idx];
	},
	
	//gets the text until exists any tag (html tag, comment, php, ptl)
	getTextContent : function (html, idx) {
		var i = idx - 1;
		
		do {
			i = html.indexOf("<", i + 1);
			
			if (i == -1)
				i = html.length;
			else if (this.isPTL(html, i) || this.isPHP(html, i) || this.isComment(html, i) || this.isTagHtml(html, i))
				break;
		}
		while (i < html.length);
		
		//console.log("content:"+html.substr(idx, i - (idx - 1)));
		var content = i == html.length ? html.substr(idx) : html.substr(idx, i - idx);
		idx = i == html.length ? i : i - 1;
		
		return [content, idx];
	},
	
	//To be used by external javascripts like the vendor/jquerylayoutuieditor/js/LayoutUIEditor.js
	getTagContent : function (html, idx, tag_name) {
		var char, is_same_tag = false, is_no_parse_tags = false, is_single_tag = false, is_open_tag = false, is_close_tag = false, inner_repeated_tags = 0, tag_html, code, tn, tnl, orig_i, outer_start_pos, outer_end_pos, inner_start_pos, inner_end_pos;
		tag_name = tag_name.toLowerCase();
		
		for (var i = idx, t = html.length; i < t; i++) {
			char = html[i];
			
			if (char == "<") {
				if (this.isComment(html, i)) {
					tag_html = this.getComment(html, i);
					i = tag_html[1];
				}
				else if (this.isPHP(html, i)) {
					tag_html = this.getPHP(html, i);
					i = tag_html[1];
				}
				else if (this.isPTL(html, i)) {
					tag_html = this.getPTL(html, i);
					i = tag_html[1];
				}
				else if (this.isTagHtml(html, i)) {
					orig_i = i;
					tag_html = this.getTagHtml(html, i, "");
					code = tag_html[0];
					i = tag_html[1];
					
					tn = this.getTagName(code, 0);
					tnl = tn.toLowerCase();
					is_same_tag = tnl == tag_name;
					is_no_parse_tags = tnl == "style" || tnl == "script" || tnl == "textarea"; //it means that this tags cannot have inner childs with the same name.
					
					//if (is_no_parse_tags) {console.log(is_same_tag);console.log(tn);console.log(tag_html);}
					
					if (is_same_tag) {
						is_close_tag = code[1] == "/"; //</...
						is_single_tag = code[ code.length - 2 ] == "/"; //<.../>
						
						if (is_single_tag) {
							if (inner_repeated_tags == 0) {
								outer_start_pos = orig_i;
								outer_end_pos = i;
								inner_start_pos = inner_end_pos = null;
								//console.log("outer_end_pos:"+outer_end_pos+"\nstr:"+html.substr(outer_end_pos, 10));
								break;
							}
						}
						else if (is_close_tag) {
							if (!is_no_parse_tags)
								--inner_repeated_tags;
							
							if (inner_repeated_tags == 0) {
								outer_end_pos = i;
								inner_end_pos = i - code.length;
								break;
							}
						}
						else {
							if (inner_repeated_tags == 0) {
								outer_start_pos = orig_i;
								inner_start_pos = i + 1;
							}
							
							if (!is_no_parse_tags) 
								inner_repeated_tags++;
							else {
								tag_html = this.getNonParseInnerTagsNodeContent(html, i + 1, tn);
								i = tag_html[1];
								
								/*console.log(tag_html);
								console.log(html.substr(tag_html[1], 20));*/
							}
						}
					}
					else if (is_no_parse_tags) {
						tag_html = this.getNonParseInnerTagsNodeContent(html, i + 1, tn);
						i = html.indexOf(">", tag_html[1] + ("</" + tnl).length + 1);
						
						/*console.log("IS NO PARSE TAG: " + tnl);
						console.log("inner index: "+tag_html[1]);
						console.log("inner html: "+tag_html[0]);
						console.log("outer html: "+html.substr(orig_i, tag_html[1] + ("</" + tnl).length + 2 - orig_i));
						console.log(html.substr(tag_html[1], 50));
						//console.log(html.substr(i - 50, 51));*/
					}
				}
			}
		}
		
		if (is_same_tag) {
			var inner_content = is_single_tag ? "" : (inner_end_pos >= 0 ? html.substr(inner_start_pos, inner_end_pos - inner_start_pos + 1) : html.substr(inner_start_pos));
			var outer_content = outer_end_pos >= 0 ? html.substr(outer_start_pos, outer_end_pos - outer_start_pos + 1) : html.substr(outer_start_pos);
			idx = outer_end_pos >= 0 ? outer_end_pos : html.length;
			
			//console.log("tag: "+tag_name);
			//console.log("inner html: "+inner_content);
			//console.log("outer html: "+outer_content);
			return [inner_content, idx, outer_content]; //i retornado tem que ser do outer_content, ou seja, o ultimo char do outer_content
		}
		
		return null;
	},
	
	getTagName : function (html, idx) {
		var m = html.substr(idx).match(/^<\/?([a-z0-9\-_]+)/i);
		m = m ? m : html.substr(idx).match(/^<!([a-z0-9\-_]+)/i); //bc of: <!DOCTYPE ...>
		m = m[0].substr(1);
		
		if (m.substr(0, 1) == "/")
			m = m.substr(1);
		
		return m;
	},
	
	getPTLTagName : function (html, idx) {
		var m = html.substr(idx).match(/^<\/?(php|ptl|\?):([a-z0-9])([^ >\/])*/i);
		
		if (!m) //It can be an empty ptl code
			m = html.substr(idx).match(/^<\/?(php|ptl|\?):([^ >\/])*/i);
		
		m = m[0].substr(1);
		
		if (m.substr(0, 1) == "/")
			m = m.substr(1);
		
		return m;
	},
	
	isTagHtml : function(html, idx) {
		return /^<\/?[a-z]+/i.test( html.substr(idx, 5) );
	},
	
	isPTL : function (html, idx) {
		return /^<\/?(php|ptl|\?):([a-z0-9])/i.test( html.substr(idx) );
	},
	
	isPHP : function (html, idx) {
		return /^<\?(php|=)?(\s+|\$|"|'|[0-9])/.test( html.substr(idx, 10) );
	},
	
	isComment : function (html, idx) {
		return /^<!--/.test( html.substr(idx) );
	},
	
	//single html tags are tags that finish in /> but the html code doesn't need to contain the closing tag />. Can simply finish in >. Example: <input name="xx">
	isSingleHtmlTag : function (html) {
		var matches = /^<(\w+)/.exec(html);
		var tag = matches ? matches[1].toLowerCase() : null;
		
		return tag && this.single_html_tags.indexOf(tag) != -1;
	},
	
	//single ptl tags are tags that finish in />, this is: <ptl... />. Or that doesn't need to close the tags. Can simply finish in >
	isSinglePTLTag : function (ptl) {
		var matches = /^<\/?(php|ptl|\?):(.+)$/i.exec(ptl);
		var tag = matches ? matches[2] : null;
		
		if (tag)
			for (var k in this.single_ptl_tags)
				if (tag.startsWith( this.single_ptl_tags[k] ))
					return true;
		
		return false;
	},
	
	isDecrementPrefixPTLTag : function (ptl) {
		var matches = /^<\/?(php|ptl|\?):(.+)$/i.exec(ptl);
		var tag = matches ? matches[2] : null;
		
		if (tag)
			for (var k in this.dec_prefix_ptl_tags)
				if (tag.startsWith( this.dec_prefix_ptl_tags[k] ))
					return true;
		
		return false;
	},
	
	isCharEscaped : function (str, idx) {
		var escaped = false;
		
		for (var i = idx - 1; i >= 0; i--) {
			if (str[i] == "\\")
				escaped = !escaped;
			else
				break;
		}
		
		return escaped;
	},
}
