/*
 * Copyright (c) 2007 PHPMyFrameWork - Joao Pinto
 * AUTHOR: Joao Paulo Lopes Pinto -- http://jplpinto.com
 * 
 * The use of this code must be allowed first by the creator Joao Pinto, since this is a private and proprietary code.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY EXPRESS 
 * OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY 
 * AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR 
 * CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL 
 * DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, 
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER 
 * IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT 
 * OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE. IN NO EVENT SHALL 
 * THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN 
 * AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE 
 * OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */

var FormFieldsUtilObj = {
	
	move_action_enabled : true,
	
	//PREPARING ARRAY UTILS
	convertFormSettingsDataArrayToSettings : function(form_settings_data) { //Note that this method is used by other classes, so be very carefull changing this.
		var settings = {};
		
		if (form_settings_data) {
			if (form_settings_data.hasOwnProperty("key"))
				form_settings_data = {1 : form_settings_data};
			
			$.each(form_settings_data, function(i, fsd) {
				var key = fsd["key"];
				if (fsd["key_type"] == "null" || (key !== 0 && !key))
					key = i;
				
				settings[key] = parseFormSettingsDataArrayItem(fsd);
			});
		}
		
		function parseFormSettingsDataArrayItem(fsd) {
			if (fsd.hasOwnProperty("items") && fsd["items"]) {
				var items = fsd["items"];
				
				/* items can be:
				- [items => [items => ...]]
				- [items => [0 => ..., 1 => ...]]
				- [items => [value => ...]]
				*/
				if (items.hasOwnProperty("items")) //for the cases with [items=>[items=>[...]]]
					items = [ items ];
				else if (items.hasOwnProperty("value")) { //for the cases with [items=>[value=>"..."]]
					//check if is a simple value and not an associative array with other keys where value is one of them. This means that we have a list of real items and not a single value. This avoids the issue where we have the createForm with "value" attriibute for the input fields. To be sure this function is working try to load a BLOCK with the "form" module with predefined values in a text-field.
					var exists_other_keys = false;
					
					for (var k in items)
						if (k != "value" && k != "value_type" && k != "key" && k != "key_type") {
							exists_other_keys = true;
							break;
						}
					
					if (!exists_other_keys)
						items = [ items ];
				}
				
				var s = {};
				$.each(items, function(i, item) {
					var key = item["key"];
					if (item["key_type"] == "null" || (key !== 0 && !key)) {
						key = i;
					}
				
					s[ key ] = parseFormSettingsDataArrayItem(item);
				});
				return s;
			}
			else
				return getFormSettingsAttributeValueConfigured(fsd["value"], fsd["value_type"]);
		}
		
		function getFormSettingsAttributeValueConfigured(value, type) {
			if (value == null || typeof value == "undefined") {
				return "";
			}
			
			value = "" + value;
			//console.log(type+":"+value);
			
			if (type == "variable")
				return value.trim().substr(0, 1) != "$" ? "$" + value : value;
			else if (type == "string") {
				var fc = value.charAt(0);
				var lc = value.charAt(value.length - 1);
				
				//if value has \n then the regex won't work. So we need to use .replace(/\n/g, "")
				if (/([^\\]["']|^["'])/.test(value.replace(/\n/g, "")) && !((fc == '"' && lc == '"') || (fc == "'" && lc == "'")))
					return '"' + value.replace(/"/g, '\\"') + '"';
				
				return value;
			}
			else {
				if ($.isNumeric(value))
					return parseInt(value);
				else if (value.toLowerCase() == "true") 
					return 1;
				else if (value.toLowerCase() == "false") 
					return 0;
				//else if (/^"(.*)([^\\])"(.*)"$/.test(value.replace(/\n/g, ""))) //Check if exists quotes in the beginning and end of the value and in the middle (which are not escaped), that means, there are a php code in between. if value has \n then the regex won't work. So we need to use .replace(/\n/g, "")
				else if (/([^\\]["']|^["'])/.test(value.replace(/\n/g, ""))) //Check if exists quotes (which are not escaped), this is, there is php code in this field. if value has \n then the regex won't work. So we need to use .replace(/\n/g, "")
					return value;
				else 
					return "<?= " + value + " ?>";
			}
		}
		
		return settings;
	},
	
	convertFormSettingsDataSettingsToArray : function(form_html_elm) {
		var form_settings_obj = this.getFormSettingsDataSettings(form_html_elm);
		//console.log(form_settings_obj);
		
		return this.convertFormSettingsObjectToArray(form_settings_obj);
	},
	
	convertFormSettingsObjectToArray : function(form_settings_obj) { //Note that this method is used by other classes, so be very carefull changing this.
		var arr_settings = parseFormSettingsToArray(form_settings_obj);
		//console.log(arr_settings);
		
		function parseFormSettingsToArray(settings) {
			var arr = [];
			
			if (settings) {
				if ($.isArray(settings)) {
					var s = {};
					for (var i = 0; i < settings.length; i++)
						s[i] = settings[i];
					settings = s;
				}
				
				for (var key in settings) {
					var value = settings[key];
					
					var item = {
						"key": key,
						"key_type": parseInt(key) >= 0 ? "" : "string",
					};
					
					if ($.isPlainObject(value) || $.isArray(value))
						item["items"] = parseFormSettingsToArray(value);
					else {
						var v = FormFieldsUtilObj.getFormSettingsAttributeValueDesconfigured(value);
						//console.log(v[1]+":"+v[0]);
						
						item["value"] = v[0];
						item["value_type"] = v[1];
					}
					
					arr.push(item);
				}
			}
			
			return arr;
		}
		
		return arr_settings;
	},
		
	/* OLD VERSION - DEPRECATED:
	getFormSettingsAttributeValueDesconfigured : function(value) {
		if (value == null || typeof value == "undefined")
			return [null, ""];
		
		value = "" + value;
		
		if (value.trim().substr(0, 1) == "$")
			return [value.trim().substr(1), "variable"];//$var_x or $var_x["as"][...]
		else if ($.isNumeric(value))
			return [value, ""];
		else if (value.toLowerCase() == "true" || value.toLowerCase() == "false")
			return [value, ""];
		else if (value.trim().substr(0, 2) == "<?") {
			value = value.replace("<?php", "").replace("<?=", "").replace("<?", "").replace("?>", "");
			return [value.trim(), ""];
		}
		//else if (/^"(.*)([^\\])"(.*)"$/.test(value.replace(/\n/g, ""))) //Check if exists quotes in the beginning and end of the value and in the middle (which are not escaped), which means, there are a php code in between. if value has \n then the regex won't work. So we need to use .replace(/\n/g, "")
		else if (/([^\\]["']|^["'])/.test(value.replace(/\n/g, ""))) //Check if exists quotes (which are not escaped), this is, there is php code in this field. if value has \n then the regex won't work. So we need to use .replace(/\n/g, "")
			return [value, ""];
		else
			return [value, "string"];
	},
	
	NEW VERSION:
	*/
	getFormSettingsAttributeValueDesconfigured : function(value) {
		if (value == null || typeof value == "undefined")
			return [null, ""];
		
		value = "" + value; //convert value to string, if numeric or something else (including if array or object, but these cases should not happen)
		
		if (value.trim().length == 0)
			return [value, "string"];
		
		if ($.isNumeric(value) || isValidPHPCode(value)) //if is a valid php code simple return empty type "", which means php code.
			return [value, ""];
		
		return [value, "string"];
		
		/*
		  Is php code:
			- if 1 var or multiple vars joined by operators: like: "$x . $y" or "$x + $y" or "$x ? $y : 1" or "$x && $y"
			- if starts with <? and ends in ?>
			- if string wrapped in quotes
				Everytime that there is a string wrapped in quotes in the first level of the quote, it means that it is a code!
				This is:
				- text must be outside of var
				- and not escaped (with backslash)
				- and in the beginning of the argument
				- or at the end of the argument
				- precedent/followed by the concat operator: "."
				- outside of ( and [ => must be the first level of code
			
			This is, is code if:
				- if there is a $var or a quote-text-quote, where both are joined by operators like: . + - / & | : ? % !
			Otherwise is string.
			
			This is php code: 
			- $asd[0]["a"]
			- <?= foo() ?>
			- "asd" . 
			- $asd . "asd"
			- "" . foo("as", 123)
			- foo() . "dd"
			- '"" . foo("as", 'as') . "dd"'
			
			This isn't php code:
			- asd("asd" . "fggho") => bc is inside of a "(". Not at the first level
			- $asd["asd" . "fggho"] => bc is inside of a "(". Not at the first level
		*/
		//COPIED FROM CMSPresentationLayerHandler::isValidPHPCode (app/__system/phpframework/src/util/CMSPresentationLayerHandler.php)
		function isValidPHPCode(value) {
			//if value is empty string, returns false
			if (value == null || typeof value == "undefined" || ("" + value).trim().length == 0)
				return false;
			
			value = "" + value;
			
			if ($.isNumeric(value) || value.toLowerCase() == "true" || value.toLowerCase() == "false")
				return true;
			
			//if value contains ", ', $ or <?, checks if value is a php code
			if (value.indexOf('"') != -1 || value.indexOf("'") != -1 || value.indexOf('$') != -1 || value.indexOf('<?') != -1) { 
				var tokens = parseCode(value);
				var t = tokens.length;
				
				//remove empty strings, bc to check if is a php code, we must first to remove the empty strings to check the rules bellow. If we have the code: '$x . $y', we need to convert it first to: '$x.$y', in order to check the rules bellow. However do not remove the empty quotes...
				var new_tokens = new Array();
				for (var i = 0; i < t; i++)
					if (tokens[i][0].trim() != "" || tokens[i][1] != "") //avoid removing the empty quotes...
						new_tokens.push( tokens[i] );
				
				//console.log(value);
				//console.log(new_tokens);
				
				t = new_tokens.length;
				if (t) {
					if (t == 1 && (new_tokens[0][1] == "php" || new_tokens[0][1] == "variable" || new_tokens[0][1] == "quotes")) //if is php code with php open and close tags or a variable or quotes
						return true;
					
					var exists_variables_or_quotes_joinned = false;
					var parenthesis = 0;
					
					for (var i = 0; i < t; i++) {
						var tt = new_tokens[i][1];
						var ntt = i + 1 < t ? new_tokens[i + 1][1] : null;
						
						if (tt == "variable" || tt == "quotes") {
							if (i >= 0 && i + 2 < t) { //first or middle char and if exists next char, but that is not the last char.
								if (ntt != "operator") //we cannot have variables or quotes before something else than an operator. If char is variable/quotes, next char must be operator.
									return false;
								else if (new_tokens[i + 1][0] == ".") //if there is a variable or quotes followed by an operator join ".", is php code! This solve the case: '"" . foo("as")'
									exists_variables_or_quotes_joinned = true; 
							}
							else if (i + 1 <= t && i - 1 > 0) { //last or middle char and if exists previous char, but that is not the first char.
								if (new_tokens[i - 1][1] != "operator") //we cannot have variables or quotes after something else than an operator. If char is variable/quotes, previous char must be a operator.
									return false;
								else if (new_tokens[i - 1][0] == ".") //if there is a variable or quotes preceeded by an operator join ".", is php code! This solve the case: 'foo() . "dd"'
									exists_variables_or_quotes_joinned = true; 
							}
						}
						else if (tt == "operator") {
							if (i == 0 || i + 1 == t) // first or last char cannot be operator
								return false;
							else if (ntt == "operator") //.+ => 2 operators together is wrong
								return false;
						}
					}
					
					//console.log("exists_variables_or_quotes_joinned:"+exists_variables_or_quotes_joinned);
					return exists_variables_or_quotes_joinned; //if there isn't any variable or quotes it means is not a code!
				}
			}
			
			return false;
		}
		
		//Parses a text code and divides it with tokens
		//COPIED FROM CMSPresentationLayerHandler::parseCode (app/__system/phpframework/src/util/CMSPresentationLayerHandler.php)
		function parseCode(text) {
			var tokens = new Array();
			var odq = false;
			var osq = false;
			var offset = 0;
			var current_type = "";
			var operators = {
				".": ".", //for concat strings
				"+": "+",
				"-": "-",
				"/": "/",
				"*": "*",
				"%": "%",
				">": [">==", ">=", ">"],
				"<": ["<==", "<=", "<"],
				"=": ["===", "=="],
				"&": "&",//for if conditions
				"|": "|",//for if conditions
				"!": "!", //for: !$xxx
				"?": "?", //for: xxx ? yyy : www
				":": ":", //for: xxx ? yyy : www
			};
			
			var l = text.length;
			for (var i = 0; i < l; i++) {
				var char = text[i];
				
				if (!isCharEscaped(text, i)) {
					if (char == '"' && !osq) { //for open/close double quotes
						odq = !odq;
						
						var str = text.substr(offset, i - offset + (!odq ? 1 : 0));
						if (str)
							tokens.push( [str, !odq ? "quotes" : current_type] );
						
						current_type = !odq ? "" : "quotes";
						offset = !odq ? i + 1 : i;
					}
					else if (char == "'" && !odq) { //for open/close single quotes
						osq = !osq;
						
						var str = text.substr(offset, i - offset + (!osq ? 1 : 0));
						if (str)
							tokens.push( [str, !osq ? "quotes" : current_type] );
						
						current_type = !osq ? "" : "quotes";
						offset = !osq ? i + 1 : i;
					}
					else if (char == "$" && !osq && !odq) { //for variables
						var str = text.substr(i);
						//var match = /^\$[\p{L}\w]+/u.exec(str); //'\w' means all words with '_' and '/u' means with accents and ç too. Cannot use this bc it does not work in IE.
						var match = /^\$[\w\u00C0-\u00D6\u00D8-\u00F6\u00F8-\u024F\u1EBD\u1EBC]+/.exec(str); //'\w' means all words with '_' and 'u' means with accents and ç too.
						
						if (match) {
							match = match[0];
							var bc = 0;
							var pc = 0;
							
							for (var j = i + match.length; j < l; j++) {
								char = text[j];

								if (char == '"' && !isCharEscaped(text, i) && !osq)
									odq = !odq;
								else if (char == "'" && !isCharEscaped(text, i) && !odq)
									osq = !osq;
								else if (char == "[" && !osq && !odq)
									++bc;
								else if (char == "]" && !osq && !odq)
									--bc;
								else if (char == "(" && !osq && !odq) //could be a method inside of a variable with an object
									++pc;
								else if (char == ")" && !osq && !odq)
									--pc;
								else if (char == "-" && text[j + 1] == ">" && !osq && !odq) { //for the cases like: $EVC->getUtilPath("MasterCondoUtil", "mastercondo")
									str = text.substr(j + 2);
									//var sub_match = /^[\p{L}\w]+/u.exec(str); //'\w' means all words with '_' and '/u' means with accents and ç too. Cannot use this bc it does not work in IE.
									var sub_match = /^[\w\u00C0-\u00D6\u00D8-\u00F6\u00F8-\u024F\u1EBD\u1EBC]+/.exec(str); //'\w' means all words with '_' and 'u' means with accents and ç too.
									
									if (sub_match && sub_match[0]) 
										j += 1 + sub_match[0].length;
									else
										break;
								}
								else if (!osq && !odq && bc <= 0 && pc <= 0)
									break;
							}
							
							var str = text.substr(offset, i - offset);
							if (str)
								tokens.push( [str, current_type] );
							
							tokens.push( [text.substr(i, j - i), "variable"] );
							current_type = "";
							i = j - 1;
							offset = j;
						}
						//else doesn't do anything and treat $ as a normal character
					}
					else if (char == "<" && text[i + 1] == "?" && !osq && !odq) { //for php open and close tags
						for (var j = i + 2; j < l; j++) {
							char = text[j];

							if (char == '"' && !isCharEscaped(text, i) && !osq)
								odq = !odq;
							else if (char == "'" && !isCharEscaped(text, i) && !odq)
								osq = !osq;
							else if (char == "?" && text[i + 1] == ">" && !osq && !odq)
								break;
						}
						
						var str = text.substr(offset, i - offset);
						if (str)
							tokens.push( [str, current_type] );
						
						tokens.push( [text.substr(i, j - i + 2), "php"] );
						current_type = "";
						i = j + 1;
						offset = j + 2;
					}
					else if (char == "(" && !osq && !odq) { //for paranthesis. Only do the first level of code. If there are methods with args, ignore them...
						var pc = 1;
						
						for (var j = i + 1; j < l; j++) {
							char = text[j];

							if (char == '"' && !isCharEscaped(text, i) && !osq)
								odq = !odq;
							else if (char == "'" && !isCharEscaped(text, i) && !odq)
								osq = !osq;
							else if (char == "(" && !osq && !odq)
								++pc;
							else if (char == ")" && !osq && !odq) {
								--pc;
								
								if (!osq && !odq && pc <= 0)
									break;
							}
						}
						
						i = j;
					}
					else if (operators[char] && !osq && !odq) {
						ops = operators[char];
						ops = !$.isArray(ops) ? [ops] : ops;
						
						for (var k in ops) {
							var op = ops[k];
							
							if (text.substr(i, op.length) == op) {
								var str = text.substr(offset, i - offset);
								if (str)
									tokens.push( [str, current_type] );
								
								tokens.push( [op, "operator"] );
								current_type = "";
								i += op.length - 1;
								offset = i + 1;
								
								break;
							}
						}
					}
				}
			}
			
			var str = text.substr(offset);
			if (str)
				tokens.push( [str, current_type] );
			
			return tokens;
		}
		
		//COPIED FROM CMSPresentationLayerHandler::isCharEscaped (app/__system/phpframework/src/util/CMSPresentationLayerHandler.php)
		function isCharEscaped(text, idx) {
			var escaped = false;
			
			for (var i = idx - 1; i >= 0; i--) {
				if (text[i] == "\\")
					escaped = !escaped;
				else
					break;
			}
			
			return escaped;
		}
	},
	
	getFormSettingsDataSettings : function(form_html_elm) {
		var items = form_html_elm.find(".field .label_extra_attributes, .field .input_extra_attributes, .field .input_available_values, .field .input_options");
		
		//Remove class task_property_field
		for (var i = 0; i < items.length; i++) {
			var item = $(items[i]);
			var item_type = item.children("select").val();
			
			if (item_type == "variable" || item_type == "string") {
				var fields = item.children("table").find(".task_property_field");
				for (var j = 0; j < fields.length; j++)
					$(fields[j]).removeClass("task_property_field").addClass("task_property_field_old");
			}
			else
				item.children("input").removeClass("task_property_field");
		}
		
		//Preparing form fields to array
		var fields = form_html_elm.find(".task_property_field");
		var names_with_special_chars = {}; //Note that the parse_str replaces all dots and spaces by underscore, so we need to put the original names again with dots and spaces.
		var rand = parseInt(Math.random() * 1000000000);
		
		var query_string = "";
		for (var i = 0; i < fields.length; i++) {
			var field = $(fields[i]);
			var name = field.attr("name");//form_containers[test][class]blable
			
			if (name) {
				var type = field.attr("type");
				var value = (type == "checkbox" || type == "radio") && !field.is(":checked") ? "" : field.val();
				
				if (name.match(/[\.\s]/)) {
					var sub_matches = name.match(/([^\[\]]+)/g);
					
					for (var j = 0, tj = sub_matches.length; j < tj; j++) {
						var sm = sub_matches[j];
						var new_sm = sm.replace(/[\.\s]/g, "#secial_char_" + rand + "_#");
						
						if (sm != new_sm) {
							names_with_special_chars[new_sm] = sm;
							name = name.replace(sm, new_sm);
						}
					}
				}
				
				//query_string += (i > 0 ? "&" : "") + escape(name) + "=" + escape(value);
				query_string += (i > 0 ? "&" : "") + encodeURIComponent(name) + "=" + encodeURIComponent(value);
			}
		}
		
		var form_settings = {};
		parse_str(query_string, form_settings);
		
		//Note that the parse_str replaces all dots and spaces by underscore, so we need to put the original names again with dots and spaces.
		if (!$.isEmptyObject(names_with_special_chars)) {
			try {
				var json = JSON.stringify(form_settings);
				
				for (var new_name in names_with_special_chars)
					json = json.replace(new_name, names_with_special_chars[new_name]);
				
				form_settings = JSON.parse(json);
			}
			catch(e) {
				if (console && console.log)
					console.log(e);
			}
		}
		
		//console.log(query_string);
		//console.log(form_settings);
		//console.log(names_with_special_chars);
		
		//Re-Add class task_property_field again
		for (var i = 0; i < items.length; i++) {
			var item = $(items[i]);
			var item_type = item.children("select").val();
			
			if (item_type == "variable" || item_type == "string") {
				var fields = item.children("table").find(".task_property_field_old");
				for (var j = 0; j < fields.length; j++)
					$(fields[j]).removeClass("task_property_field_old").addClass("task_property_field");
			}
			else 
				item.children("input").addClass("task_property_field");
		}
		
		return form_settings;
	},
	
	prepareChildElements : function(prefix, elements) {
		var html = '';
		var count = 0;
		
		if (elements) {
			$.each(elements, function(i, element) {
				//console.log(element);
				
				var p = prefix + "[" + count + "]";
				
				if (element.hasOwnProperty("container"))
					html += FormFieldsUtilObj.getContainerHtml(p, element["container"]);
				else if (element.hasOwnProperty("field"))
					html += FormFieldsUtilObj.getFieldHtml(p, element["field"]);
				else if (element.hasOwnProperty("pagination"))
					html += FormFieldsUtilObj.getPaginationHtml(p, element["pagination"]);
				else if (element.hasOwnProperty("table"))
					html += FormFieldsUtilObj.getTableHtml(p, element["table"]);
				else if (element.hasOwnProperty("tree"))
					html += FormFieldsUtilObj.getTreeHtml(p, element["tree"]);
				
				count++;
			});
		}
		
		return [html, count - 1];
	},
	
	//PREPARING CONTAINER HTML
	loadContainers : function(containers_elm, prefix, containers) {
		var c = this.prepareChildElements(prefix, containers);
		
		containers_elm.html(c[0]);
		containers_elm.attr("count", c[1]);
		
		ProgrammingTaskUtil.onProgrammingTaskPropertiesNewHtml( containers_elm.children() );
	},
	
	addContainer : function(elm, suffix) {
		var p = $(elm).parent();
		var prefix = p.attr("prefix");
		prefix = typeof prefix == "undefined" ? "" : prefix;
		var parent_div = p.children(".fields");
		
		var idx = parseInt(parent_div.attr("count"));
		idx = idx >= 0 ? idx + 1 : 0;
		parent_div.attr("count", idx);
		
		var html = this.getContainerHtml(prefix + suffix + "[" + idx + "]", {});
		
		parent_div.append(html);
		
		ProgrammingTaskUtil.onProgrammingTaskPropertiesNewHtml( parent_div.children("div").last() );
		
		this.forceMaximizingContainer(elm);
	},
	
	getContainerHtml : function(prefix, data) {
		data = $.isEmptyObject(data) ? {} : data;
		
		var elements = data["elements"];
		var container_id = data["id"];
		var class_name = data["class"];
		var href = data["href"];
		var target = data["target"];
		var title = data["title"];
		var previous_html = data["previous_html"];
		var next_html = data["next_html"];
		
		prefix += '[container]';
		
		elements = elements ? elements : {};
		container_id = typeof container_id != "undefined" && container_id != null ? ("" + container_id).replace(/"/g, "&quot;") : "";
		class_name = typeof class_name != "undefined" && class_name != null ? ("" + class_name).replace(/"/g, "&quot;") : "";
		href = typeof href != "undefined" && href != null ? ("" + href).replace(/"/g, "&quot;") : "";
		target = typeof target != "undefined" && target != null ? ("" + target).replace(/"/g, "&quot;") : "";
		title = typeof title != "undefined" && title != null ? ("" + title).replace(/"/g, "&quot;") : "";
		
		previous_html = typeof previous_html != "undefined" && previous_html != null ? ("" + previous_html).replace(/"/g, "&quot;") : "";
		next_html = typeof next_html != "undefined" && next_html != null ? ("" + next_html).replace(/"/g, "&quot;") : "";
		
		var els = this.prepareChildElements(prefix + '[elements]', elements);
		
		var html = 
		'<div class="container" prefix="' + prefix + '">' + 
		'	<label>Container:</label>' +
		'	<span class="icon group_add" onClick="FormFieldsUtilObj.addContainer(this, \'[elements]\');" title="Add Sub Container">add sub container</span>' + 
		'	<span class="icon item_add" onClick="FormFieldsUtilObj.addInputField(this, \'[elements]\');" title="Add Input Field">add input</span>' + 
		'	<span class="icon pagination_add" onClick="FormFieldsUtilObj.addPagination(this, \'[elements]\');" title="Add Pagination">add pagination</span>' + 
		'	<span class="icon table_add" onClick="FormFieldsUtilObj.addTable(this, \'[elements]\');" title="Add Table">add table</span>' + 
		'	<span class="icon tree_add" onClick="FormFieldsUtilObj.addTree(this, \'[elements]\');" title="Add Tree">add tree</span>' + 
		'	<span class="icon remove remove_field" onClick="$(this).parent().remove()" title="Remove Container">remove field</span>' +
		'	<span class="icon info" onClick="FormFieldsUtilObj.toggleContainerProperties(this)" title="Toggle Container properties">toggle container props</span>' +
		'	<span class="icon minimize minimize_field" onClick="FormFieldsUtilObj.minimizeContainer(this)" title="Minimize/Maximize">minimize container</span>' +
		'	<span class="icon move_up" onClick="FormFieldsUtilObj.moveUp(this)" title="Move Up Container">move up container</span>' +
		'	<span class="icon move_down" onClick="FormFieldsUtilObj.moveDown(this)" title="Move Down Container">move down container</span>' +
		'	<span class="icon add_fields_automatically" onClick="FormFieldsUtilObj.addFieldsAutomatically(this)" title="Add Fields Automatically">add fields automatically</span>' +
		'	<div class="container_props">' +
		'		<div class="id">' +
		'			<label>Id:</label>' +
		'			<input type="text" class="task_property_field" name="' + prefix + '[id]" value="' + container_id + '" />' +
		'			<span class="icon add_variable inline" onClick="ProgrammingTaskUtil.onProgrammingTaskChooseCreatedVariable(this)">Add Variable</span>' +
		'		</div>' +
		'		<div class="class">' +
		'			<label>Class:</label>' +
		'			<input type="text" class="task_property_field" name="' + prefix + '[class]" value="' + class_name + '" />' +
		'			<span class="icon add_variable inline" onClick="ProgrammingTaskUtil.onProgrammingTaskChooseCreatedVariable(this)">Add Variable</span>' +
		'		</div>' +
		'		<div class="clear"></div>' +
		'		<div class="href">' +
		'			<label>Url:</label>' +
		'			<input type="text" class="task_property_field" name="' + prefix + '[href]" value="' + href + '" />' +
		'			<span class="icon add_variable inline" onClick="ProgrammingTaskUtil.onProgrammingTaskChooseCreatedVariable(this)">Add Variable</span>' +
		'		</div>' +
		'		<div class="target">' +
		'			<label>Target:</label>' +
		'			<input type="text" class="task_property_field" name="' + prefix + '[target]" value="' + target + '" />' +
		'			<span class="icon add_variable inline" onClick="ProgrammingTaskUtil.onProgrammingTaskChooseCreatedVariable(this)">Add Variable</span>' +
		'		</div>' +
		'		<div class="ctitle">' +
		'			<label>Title:</label>' +
		'			<input type="text" class="task_property_field" name="' + prefix + '[title]" value="' + title + '" />' +
		'			<span class="icon add_variable inline" onClick="ProgrammingTaskUtil.onProgrammingTaskChooseCreatedVariable(this)">Add Variable</span>' +
		'		</div>' +
		'		<div class="clear"></div>' +
		'		<div class="previous_html">' +
		'			<label>Previous Html:</label>' +
		'			<textarea class="task_property_field" name="' + prefix + '[previous_html]">' + previous_html + '</textarea>' +
		'			<span class="icon add_variable inline inline-area" onClick="ProgrammingTaskUtil.onProgrammingTaskChooseCreatedVariable(this)">Add Variable</span>' +
		'		</div>' +
		'		<div class="next_html">' +
		'			<label>Next Html:</label>' +
		'			<textarea class="task_property_field" name="' + prefix + '[next_html]">' + next_html + '</textarea>' +
		'			<span class="icon add_variable inline inline-area" onClick="ProgrammingTaskUtil.onProgrammingTaskChooseCreatedVariable(this)">Add Variable</span>' +
		'		</div>' +
		'		<div class="clear"></div>' +
		'	</div>' +
		'	<div class="fields" count="' + els[1] + '">' + els[0] + '</div>' + 
		'</div>';
		
		return html;
	},
	
	toggleContainerProperties : function(elm) {
		elm = $(elm);
		var main_div = elm.parent();
		var container_props = main_div.children(".container_props");
		
		container_props.toggle();
	},
	
	minimizeContainer : function(elm) {
		elm = $(elm);
		var main_div = elm.parent();
		var fields = main_div.children(".fields");
		
		if (elm.hasClass("minimize")) {
			fields.hide();
			elm.removeClass("minimize").addClass("maximize");
		}
		else {
			fields.show();
			elm.removeClass("maximize").addClass("minimize");
		}
	},
	
	forceMaximizingContainer : function(elm) {
		elm = $(elm);
		var main_div = elm.parent();
		var fields = main_div.children(".fields");
		
		if (fields.css("display") == "none")
			this.minimizeContainer( main_div.children(".maximize")[0] );
	},
	
	//PREPARING FIELDS HTML
	addInputField : function(elm, suffix) {
		var p = $(elm).parent();
		var prefix = p.attr("prefix");
		prefix = typeof prefix == "undefined" ? "" : prefix;
		var parent_div = p.children(".fields");
		
		var idx = parseInt(parent_div.attr("count"));
		idx = idx >= 0 ? idx + 1 : 0;
		parent_div.attr("count", idx);
		
		var html = this.getFieldHtml(prefix + suffix + "[" + idx + "]", {
			"label" : {}, 
			"input" : {}, 
		});
		
		parent_div.append(html);
		
		ProgrammingTaskUtil.onProgrammingTaskPropertiesNewHtml( parent_div.children("div").last() );
		
		this.forceMaximizingContainer(elm);
	},
	
	loadInputFieldData : function(field_elm, data, options) {
		field_elm = $(field_elm);
		
		if (data && field_elm[0]) {
			//console.log(data);
			var label_settings_elm = field_elm.children(".label_settings");
			var input_settings_elm = field_elm.children(".input_settings");
			
			//remove settings_elm if doesn't exist in the data object
			if (options && options["remove"]) {
				if (!data.hasOwnProperty("label"))
					label_settings_elm.remove();
			
				if (!data.hasOwnProperty("input"))
					input_settings_elm.remove();
			}
			
			var previous_settings_elm = null;
			var current_settings_elm = null;
			
			$.each(data, function(key, value) {
				if (key == "label") {
					if ($.isPlainObject(value) && !$.isEmptyObject(value)) {
						//prepare some composite item keys, so it can load them with empty values
						if (!value.hasOwnProperty("extra_attributes"))
							value["extra_attributes"] = "";
						
						if (!value.hasOwnProperty("available_values"))
							value["available_values"] = "";
						
						if (!value.hasOwnProperty("options"))
							value["options"] = "";
						
						//load item values
						$.each(value, function(item_key, item_value) {
							loadInputFieldItemData(label_settings_elm, item_key, item_value, "label_");
						});
					}
				}
				else if (key == "input") {
					if ($.isPlainObject(value) && !$.isEmptyObject(value)) {
						//prepare some composite item keys, so it can load them with empty values
						if (!value.hasOwnProperty("extra_attributes"))
							value["extra_attributes"] = "";
						
						if (!value.hasOwnProperty("available_values"))
							value["available_values"] = "";
						
						if (!value.hasOwnProperty("options"))
							value["options"] = "";
						
						//load item values
						$.each(value, function(item_key, item_value) {
							loadInputFieldItemData(input_settings_elm, item_key, item_value, "input_");
						});
					}
				}
				else 
					loadInputFieldItemData(field_elm, key, value, "");
				
				//Move settings_elm to the correspondent position;
				if (options && options["sort"] && (key == "label" || key == "input")) {
					current_settings_elm = key == "label" ? label_settings_elm : (key == "input" ? input_settings_elm : input_settings_elm);
					
					if (previous_settings_elm)
						field_elm[0].insertBefore(current_settings_elm[0], previous_settings_elm[0].nextSibling);//if nextSibling doesn't exist, the prop_elm will be inserted at the end, which is correct!
					else //insert to first position
						field_elm[0].insertBefore(current_settings_elm[0], field_elm.children(".moving_not_allowing").first()[0].nextSibling);
					
					previous_settings_elm = current_settings_elm;
				}
			});
		}
		
		function loadInputFieldItemData(field_elm, key, value, prefix_key) {
			var item = field_elm.children("." + prefix_key + key);
			
			if (key != "extra_attributes" && key != "available_values" && key != "options") {
				if (!item[0])
					item = field_elm.children(".other_settings").children("." + key);
				
				if (item[0]) {
					var input = item.children("input, select, textarea").first();
					
					if (input.attr("type") == "checkbox" || input.attr("type") == "radio") {
						if (input.val() == value)
							input.attr("checked", "checked").prop("checked", true);
						else if (typeof value != "undefined") //if value == "", it should remove the checked attribute, bc the value exists but is empty. Do not add here the code: "if (vale.length > 0)" bc if the value is "", it should still remove the checked attr.
							input.removeAttr("checked").prop("checked", false);
					}
					else
						input.val(value);
				}
			}
			else {
				if ($.isArray(value) || $.isPlainObject(value)) {
					//console.log(value);
					
					switch(key) {
						case "extra_attributes": value = FormFieldsUtilObj.parseInputFieldExtraAttributes(value); break;
						case "available_values": value = FormFieldsUtilObj.parseInputFieldAvailableValues(value); break;
						case "options": value = FormFieldsUtilObj.parseInputFieldOptions(value); break;
					}
					//console.log(value);
					
					var table = item.children("table");
					var icon = table.find("th.icons .add").first();
					
					table.find("td.icons").parent().remove();//empty table first
					
					$.each(value, function(idx, sub_item) {
						var trs_num_1 = table.find("tr").length;
						icon.click();
						var trs_num_2 = table.find("tr").length;
					
						if (trs_num_2 > trs_num_1) {
							var last = table.find("tr").last();
							
							$.each(sub_item, function(sub_key, sub_value) {
								last.find("td." + sub_key).children("input, select, textarea").val(sub_value);
							});
						}
						else
							status = false;
					});
				}
				else {
					item.children("input").val($.isNumeric(value) || value ? value : "");
					var select = item.children("select");
					var value_type = ("" + value).trim().charAt(0) == '$' ? "variable" : "string";
					select.val(value_type);
					
					switch(key) {
						case "extra_attributes": FormFieldsUtilObj.onChangeExtraAttributesType(select[0]); break;
						case "available_values": FormFieldsUtilObj.onChangeAvailableValuesType(select[0]); break;
						case "options": FormFieldsUtilObj.onChangeOptionsType(select[0]); break;
					}
				}
			}
		}
		
		return status;
	},
	
	getFieldHtml : function(prefix, data) {
		prefix += '[field]';
		
		data = $.isEmptyObject(data) ? {} : data;
		
		var disable_field_group = data["disable_field_group"];
		var class_name = data["class"];
		var label_data = data["label"];
		var input_data = data["input"];
		
		disable_field_group = $.isNumeric(disable_field_group) ? parseInt(disable_field_group) : 0;
		class_name = typeof class_name != "undefined" && class_name != null ? ("" + class_name).replace(/"/g, "&quot;") : "";
		label_data = $.isEmptyObject(data["label"]) ? {} : label_data;
		input_data = $.isEmptyObject(data["input"]) ? {} : input_data;
		
		//Note that if the data variable does not contain the label or input key, the html will not contain this settings. This means that the field will only contain the settings for the correspondent data variable's keys...
		
		//HTML
		var html = 
			'<div class="field" prefix="' + prefix + '">' +
			'	<label>Field:</label>' + 
			'	<span class="icon remove remove_field" onClick="$(this).parent().remove()" title="Remove Field">remove field</span>' +
			'	<span class="icon maximize minimize_field" onClick="FormFieldsUtilObj.minimizeField(this)" title="Minimize/Maximize">minimize field</span>' +
			'	<span class="icon move_up" onClick="FormFieldsUtilObj.moveUp(this)" title="Move Up Field">move up field</span>' +
			'	<span class="icon move_down" onClick="FormFieldsUtilObj.moveDown(this)" title="Move Down Field">move down field</span>' +
			'	<div class="disable_field_group">' +
			'		<label>Disable Field Group:</label>' +
			'		<input type="checkbox" class="task_property_field" name="' + prefix + '[disable_field_group]" value="1" ' + (disable_field_group == 1 ? 'checked' : '') + ' title="Group the Label and Input settings html into a div." onClick="FormFieldsUtilObj.onChangeFieldDisableGroup(this)" />' +
			'	</div>' +
			'	<div class="class"' + (disable_field_group == 1 ? ' style="display:none;"' : '') + '>' +
			'		<label>Class:</label>' +
			'		<input type="text" class="task_property_field" name="' + prefix + '[class]" value="' + class_name + '" />' +
			'		<span class="icon add_variable inline" onClick="ProgrammingTaskUtil.onProgrammingTaskChooseCreatedVariable(this)">Add Variable</span>' +
			'	</div>' +
			'	<span class="moving_not_allowing" style="display:none;"></span>';
		
		for (var k in data) {
			switch(k) {
				case "label":
					html += '' +
			'	<div class="label_settings" prefix="[label]">' +
			'		<label>Label Settings:</label>' +
			'		<span class="icon maximize minimize_field_label_settings" onClick="FormFieldsUtilObj.minimizeFieldLabelSettings(this)" title="Minimize/Maximize">minimize field label</span>' +
			'		<span class="icon move_up" onClick="FormFieldsUtilObj.moveUp(this)" title="Move Up Label">move up label</span>' +
			'		<span class="icon move_down" onClick="FormFieldsUtilObj.moveDown(this)" title="Move Down Label">move down label</span>' +
					this.getFieldLabelSettingsHtml(prefix + '[label]', label_data) + 
			'	</div>';
					break;
				
				case "input":
					html += '' +
			'	<div class="input_settings" prefix="[input]">' +
			'		<label>Input Field Settings:</label>' +
			'		<span class="icon maximize minimize_field_input_settings" onClick="FormFieldsUtilObj.minimizeFieldInputSettings(this)" title="Minimize/Maximize">minimize field label</span>' +
			'		<span class="icon move_up" onClick="FormFieldsUtilObj.moveUp(this)" title="Move Up Input">move up input</span>' +
			'		<span class="icon move_down" onClick="FormFieldsUtilObj.moveDown(this)" title="Move Down Input">move down input</span>' +
					this.getFieldInputSettingsHtml(prefix + '[input]', input_data) +
			'	</div>';
					break;
			}
		}
		
		html += '	<div class="clear moving_not_allowing"></div>' +
			'</div>';
		
		return html;
	},
	
	getFieldLabelSettingsHtml : function(prefix, data) {
		data = $.isEmptyObject(data) ? {} : data;
		
		//LABEL BLOCK
		var label_type = data["type"];
		var label_value = data["value"];
		var label_class = data["class"];
		var label_title = data["title"];
		var label_previous_html = data["previous_html"];
		var label_next_html = data["next_html"];
		var label_extra_attributes = data["extra_attributes"];
		
		label_value = typeof label_value != "undefined" && label_value != null ? ("" + label_value).replace(/"/g, "&quot;") : "";
		label_class = typeof label_class != "undefined" && label_class != null ? ("" + label_class).replace(/"/g, "&quot;") : "";
		label_title = typeof label_title != "undefined" && label_title != null ? ("" + label_title).replace(/"/g, "&quot;") : "";
		label_previous_html = typeof label_previous_html != "undefined" && label_previous_html != null ? ("" + label_previous_html).replace(/"/g, "&quot;") : "";
		label_next_html = typeof label_next_html != "undefined" && label_next_html != null ? ("" + label_next_html).replace(/"/g, "&quot;") : "";
		
		//This part with the extra_attributes must happen after the prefix
		var label_extra_attributes_type = $.isPlainObject(label_extra_attributes) || $.isArray(label_extra_attributes) || !label_extra_attributes ? "array" : (("" + label_extra_attributes).trim().charAt(0) == '$' ? "variable" : "string");
		var label_extra_attributes_array = this.prepareInputFieldExtraAttributes(prefix + "[extra_attributes]", label_extra_attributes_type == "array" ? label_extra_attributes : null);
		var label_extra_attributes_variable = label_extra_attributes_type == "variable" && label_extra_attributes ? ("" + label_extra_attributes).replace(/"/g, "&quot;") : "";
		var label_extra_attributes_string = label_extra_attributes_type == "string" && label_extra_attributes ? label_extra_attributes : "";
		
		//TYPES
		var label_types = ["label", "h1", "h2", "h3", "h4", "h5", "span"];
		
		//HTML
		var html = '' +
		'		<div class="label_type">' +
		'			<label>Type:</label>' +
		'			<select class="task_property_field" name="' + prefix + '[type]">';
		
		var exists = false;
		for (var i = 0; i < label_types.length; i++) {
			html += '<option' + (label_types[i] == label_type ? ' selected' : '') + '>' + label_types[i] + '</option>';

			if (label_types[i] == label_type)
				exists = true;
		}

		if (!exists && label_type)
			html += '<option selected>' + label_type + '</option>';

		html += '' +
		'			</select>' +
		'		</div>' +
		'		<div class="label_value">' +
		'			<label>Label:</label>' +
		'			<input type="text" class="task_property_field" name="' + prefix + '[value]" value="' + label_value + '" />' +
		'			<span class="icon add_variable inline" onClick="ProgrammingTaskUtil.onProgrammingTaskChooseCreatedVariable(this)">Add Variable</span>' +
		'		</div>' +
		'		<div class="label_class">' +
		'			<label>Class:</label>' +
		'			<input type="text" class="task_property_field" name="' + prefix + '[class]" value="' + label_class + '" />' +
		'			<span class="icon add_variable inline" onClick="ProgrammingTaskUtil.onProgrammingTaskChooseCreatedVariable(this)">Add Variable</span>' +
		'		</div>' +
		'		<div class="label_title">' +
		'			<label>Title:</label>' +
		'			<input type="text" class="task_property_field" name="' + prefix + '[title]" value="' + label_title + '" />' +
		'			<span class="icon add_variable inline" onClick="ProgrammingTaskUtil.onProgrammingTaskChooseCreatedVariable(this)">Add Variable</span>' +
		'		</div>' +
		'		<div class="clear"></div>' +
		'		<div class="label_previous_html">' +
		'			<label>Previous Html:</label>' +
		'			<textarea class="task_property_field" name="' + prefix + '[previous_html]">' + label_previous_html + '</textarea>' +
		'			<span class="icon add_variable inline inline-area" onClick="ProgrammingTaskUtil.onProgrammingTaskChooseCreatedVariable(this)">Add Variable</span>' +
		'		</div>' +
		'		<div class="label_next_html">' +
		'			<label>Next Html:</label>' +
		'			<textarea class="task_property_field" name="' + prefix + '[next_html]">' + label_next_html + '</textarea>' +
		'			<span class="icon add_variable inline inline-area" onClick="ProgrammingTaskUtil.onProgrammingTaskChooseCreatedVariable(this)">Add Variable</span>' +
		'		</div>' +
		'		<div class="clear"></div>' +
		'		<div class="label_extra_attributes">' +
		'			<label>Extra Attributes:</label>' +
		'		' +
		'			<select class="extra_attributes_type" name="extra_attributes_type" onChange="FormFieldsUtilObj.onChangeExtraAttributesType(this)">' + 
		'				<option value="array"' + (label_extra_attributes_type == "array" ? " selected" : "") + '>Hard-Coded Values</option>' + 
		'				<option value="variable"' + (label_extra_attributes_type == "variable" ? " selected" : "") + '>External Variable or String</option>' + 
		'				<option value="string"' + (label_extra_attributes_type == "string" ? " selected" : "") + '>Input data key with values between #...#</option>' + 
		'			</select>' + 
		'			<input class="task_property_field extra_attributes_variable" type="text" name="' + prefix + '[extra_attributes]" value="' + (label_extra_attributes_type == "variable" ? label_extra_attributes_variable : label_extra_attributes_string) + '"' + (label_extra_attributes_type == "variable" || label_extra_attributes_type == "string" ? "" : ' style="display:none;"') + ' />' +
		'			<span class="icon add_variable inline" onClick="ProgrammingTaskUtil.onProgrammingTaskChooseCreatedVariable(this)"' + (label_extra_attributes_type == "variable" || label_extra_attributes_type == "string" ? "" : ' style="display:none;"') + '>Add Variable</span>' +
		'			<table class="attributes" count="' + label_extra_attributes_array[1] + '"' + (label_extra_attributes_type == "variable" || label_extra_attributes_type == "string" ? 'style="display:none;"' : "") + '><tbody>' +
		'				<tr>' +
		'					<th class="table_header name">Name</th>' +
		'					<th class="table_header value">Value</th>' +
		'					<th class="table_header icons">' +
		'						<span class="icon add" onClick="FormFieldsUtilObj.addInputFieldExtraAttribute(this, \'[extra_attributes]\');">add extra attribute</span>' +
		'					</th>' +
		'				</tr>' +
						label_extra_attributes_array[0] +
		'			</tbody></table>' +
		'		</div>';
		
		return html;
	},
	
	getFieldInputSettingsHtml : function(prefix, data) {
		data = $.isEmptyObject(data) ? {} : data;
		
		var input_type = data["type"];
		var input_name = data["name"];
		var input_class = data["class"];
		var input_value = data["value"];
		var input_place_holder = data["place_holder"];
		var input_href = data["href"];
		var input_target = data["target"];
		var input_src = data["src"];
		var input_title = data["title"];
		var input_previous_html = data["previous_html"];
		var input_next_html = data["next_html"];
		var input_extra_attributes = data["extra_attributes"];
		var input_available_values = data["available_values"];
		var input_options = data["options"];
		
		var confirmation = data["confirmation"];
		var validation_label = data["validation_label"];
		var validation_message = data["validation_message"];
		var allow_null = data["allow_null"];
		var allow_javascript = data["allow_javascript"];
		var confirmation_message = data["confirmation_message"];
		var validation_type = data["validation_type"];
		var validation_regex = data["validation_regex"];
		var validation_func = data["validation_func"];
		var min_length = data["min_length"];
		var max_length = data["max_length"];
		var min_value = data["min_value"];
		var max_value = data["max_value"];
		var min_words = data["min_words"];
		var max_words = data["max_words"];
		
		input_name = typeof input_name != "undefined" && input_name != null ? ("" + input_name).replace(/"/g, "&quot;") : "";
		input_class = typeof input_class != "undefined" && input_class != null ? ("" + input_class).replace(/"/g, "&quot;") : "";
		input_value = typeof input_value != "undefined" && input_value != null ? ("" + input_value).replace(/"/g, "&quot;") : "";
		input_place_holder = typeof input_place_holder != "undefined" && input_place_holder != null ? ("" + input_place_holder).replace(/"/g, "&quot;") : "";
		input_href = typeof input_href != "undefined" && input_href != null ? ("" + input_href).replace(/"/g, "&quot;") : "";
		input_target = typeof input_target != "undefined" && input_target != null ? ("" + input_target).replace(/"/g, "&quot;") : "";
		input_src = typeof input_src != "undefined" && input_src != null ? ("" + input_src).replace(/"/g, "&quot;") : "";
		input_title = typeof input_title != "undefined" && input_title != null ? ("" + input_title).replace(/"/g, "&quot;") : "";
		input_previous_html = typeof input_previous_html != "undefined" && input_previous_html != null ? ("" + input_previous_html).replace(/"/g, "&quot;") : "";
		input_next_html = typeof input_next_html != "undefined" && input_next_html != null ? ("" + input_next_html).replace(/"/g, "&quot;") : "";
		
		//This part with the extra_attributes must happen after the prefix
		var input_extra_attributes_type = $.isPlainObject(input_extra_attributes) || $.isArray(input_extra_attributes) || !input_extra_attributes ? "array" : (("" + input_extra_attributes).trim().charAt(0) == '$' ? "variable" : "string");
		var input_extra_attributes_array = this.prepareInputFieldExtraAttributes(prefix + "[extra_attributes]", input_extra_attributes_type == "array" ? input_extra_attributes : null);
		var input_extra_attributes_variable = input_extra_attributes_type == "variable" && input_extra_attributes ? ("" + input_extra_attributes).replace(/"/g, "&quot;") : "";
		var input_extra_attributes_string = input_extra_attributes_type == "string" && input_extra_attributes ? input_extra_attributes : "";
		
		//This part with the available_values must happen after the prefix
		var input_available_values_type = $.isPlainObject(input_available_values) || $.isArray(input_available_values) || !input_available_values ? "array" : (("" + input_available_values).trim().charAt(0) == '$' ? "variable" : "string");
		var input_available_values_array = this.prepareInputFieldAvailableValues(prefix + "[available_values]", input_available_values_type == "array" ? input_available_values : null);
		var input_available_values_variable = input_available_values_type == "variable" && input_available_values ? ("" + input_available_values).replace(/"/g, "&quot;") : "";
		var input_available_values_string = input_available_values_type == "string" && input_available_values ? input_available_values : "";
		
		//This part with the options must happen after the prefix
		var input_options_type = $.isPlainObject(input_options) || $.isArray(input_options) || !input_options ? "array" : (("" + input_options).trim().charAt(0) == '$' ? "variable" : "string");
		var input_options_array = this.prepareInputFieldOptions(prefix + "[options]", input_options_type == "array" ? input_options : null);
		var input_options_variable = input_options_type == "variable" && input_options ? ("" + input_options).replace(/"/g, "&quot;") : "";
		var input_options_string = input_options_type == "string" && input_options ? input_options : "";
		
		confirmation = $.isNumeric(confirmation) ? parseInt(confirmation) : 0;
		confirmation_message = typeof confirmation_message != "undefined" && confirmation_message != null ? ("" + confirmation_message).replace(/"/g, "&quot;") : "";
		allow_null = allow_null === "" ? 0 : ($.isNumeric(allow_null) ? parseInt(allow_null) : 1);
		allow_javascript = allow_javascript === "" ? 0 : ($.isNumeric(allow_javascript) ? parseInt(allow_javascript) : 0);
		validation_label = typeof validation_label != "undefined" && validation_label != null ? ("" + validation_label).replace(/"/g, "&quot;") : "";
		validation_message = typeof validation_message != "undefined" && validation_message != null ? ("" + validation_message).replace(/"/g, "&quot;") : "";
		validation_type = typeof validation_type != "undefined" && validation_type != null ? ("" + validation_type).replace(/"/g, "&quot;") : "";
		validation_regex = typeof validation_regex != "undefined" && validation_regex != null ? ("" + validation_regex).replace(/"/g, "&quot;") : "";
		validation_func = typeof validation_func != "undefined" && validation_func != null ? ("" + validation_func).replace(/"/g, "&quot;") : "";
		min_length = $.isNumeric(min_length) ? parseInt(min_length) : "";
		max_length = $.isNumeric(max_length) ? parseInt(max_length) : "";
		min_value = $.isNumeric(min_value) ? parseInt(min_value) : "";
		max_value = $.isNumeric(max_value) ? parseInt(max_value) : "";
		min_words = $.isNumeric(min_words) ? parseInt(min_words) : "";
		max_words = $.isNumeric(max_words) ? parseInt(max_words) : "";
		
		//IS_XXX
		var is_input = input_type == "text" || input_type == "textarea" || input_type == "password" || input_type == "file";
		var is_select = input_type == "select" || input_type == "checkbox" || input_type == "radio";
		var is_hidden = input_type == "hidden";
		var is_search = input_type == "search";
		var is_url = input_type == "url";
		var is_email = input_type == "email";
		var is_tel = input_type == "tel";
		var is_number = input_type == "number";
		var is_range = input_type == "range";
		var is_date = input_type == "date" || input_type == "month" || input_type == "week" || input_type == "time" || input_type == "datetime" || input_type == "datetime-local";
		var is_color = input_type == "color";
		var is_button = input_type == "submit" || input_type == "button" || input_type == "button_img";
		var is_label = input_type == "label" || input_type == "h1" || input_type == "h2" || input_type == "h3" || input_type == "h4" || input_type == "h5";
		var is_link = input_type == "link";
		var is_image = input_type == "image";
		
		//TYPES
		var input_types = ["text", "select", "checkbox", "radio", "textarea", "password", "file", "search", "email", "url", "tel", "number", "range", "date", "month", "week", "time", "datetime", "datetime-local", "color", "hidden", "button", "submit", "button_img", "link", "image", "label", "h1", "h2", "h3", "h4", "h5"];
		var validation_types = ["", "int", "bigint", "number", "double", "float", "decimal", "phone", "fax", "email", "date", "datetime", "time", "ipaddress", "smallint", "filename"];
		
		//HTML
		var html = '' +
		'		<div class="input_type">' +
		'			<label>Type:</label>' +
		'			<select class="task_property_field" name="' + prefix + '[type]" onChange="FormFieldsUtilObj.onChangeInputFieldType(this)">';
		
		var exists = false;
		for (var i = 0; i < input_types.length; i++) {
			html += '<option' + (input_types[i] == input_type ? ' selected' : '') + '>' + input_types[i] + '</option>';

			if (input_types[i] == input_type)
				exists = true;
		}
		
		if (!exists && input_type)
			html += '<option selected>' + input_type + '</option>';
		
		html += '' +
		'			</select>' +
		'		</div>' +
		'		<div class="input_name"' + (is_input || is_select || is_search || is_url || is_email || is_tel || is_number || is_range || is_date || is_color || is_button || is_hidden ? '' : 'style="display:none;"') + '>' +
		'			<label>Name:</label>' +
		'			<input type="text" class="task_property_field" name="' + prefix + '[name]" value="' + input_name + '" />' +
		'			<span class="icon add_variable inline" onClick="ProgrammingTaskUtil.onProgrammingTaskChooseCreatedVariable(this)">Add Variable</span>' +
		'		</div>' +
		'		<div class="input_class">' +
		'			<label>Class:</label>' +
		'			<input type="text" class="task_property_field" name="' + prefix + '[class]" value="' + input_class + '" />' +
		'			<span class="icon add_variable inline" onClick="ProgrammingTaskUtil.onProgrammingTaskChooseCreatedVariable(this)">Add Variable</span>' +
		'		</div>' +
		'		<div class="input_value" ' + (is_input || is_select || is_search || is_url || is_email || is_tel || is_number || is_range || is_date || is_color || is_button || is_hidden || is_link || is_label ? '' : 'style="display:none;"') + '>' +
		'			<label>Value:</label>' +
		'			<input type="text" class="task_property_field" name="' + prefix + '[value]" value="' + input_value + '" />' +
		'			<span class="icon add_variable inline" onClick="ProgrammingTaskUtil.onProgrammingTaskChooseCreatedVariable(this)">Add Variable</span>' +
		'			<span class="icon add_field_automatically" onClick="FormFieldsUtilObj.addFieldAutomatically(this)" title="Add Field Automatically">add field automatically</span>' +
		'		</div>' +
		'		<div class="input_place_holder" ' + (is_input || is_search || is_url || is_email || is_tel || is_number || is_range || is_date || is_color ? '' : 'style="display:none;"') + ' title="Place Holder">' +
		'			<label>P. Holder:</label>' +
		'			<input type="text" class="task_property_field" name="' + prefix + '[place_holder]" value="' + input_place_holder + '" />' +
		'			<span class="icon add_variable inline" onClick="ProgrammingTaskUtil.onProgrammingTaskChooseCreatedVariable(this)">Add Variable</span>' +
		'		</div>' +
		'		<div class="input_href" ' + (is_link ? '' : 'style="display:none;"') + '>' +
		'			<label>Url:</label>' +
		'			<input type="text" class="task_property_field" name="' + prefix + '[href]" value="' + input_href + '" />' +
		'			<span class="icon add_variable inline" onClick="ProgrammingTaskUtil.onProgrammingTaskChooseCreatedVariable(this)">Add Variable</span>' +
		(ProgrammingTaskUtil.on_programming_task_choose_page_url_callback ? '<span class="icon search search_page" onClick="ProgrammingTaskUtil.onProgrammingTaskChoosePageUrl(this)">Add Variable</span>' : '') +
		'		</div>' +
		'		<div class="input_target" ' + (is_link ? '' : 'style="display:none;"') + '>' +
		'			<label>Target:</label>' +
		'			<input type="text" class="task_property_field" name="' + prefix + '[target]" value="' + input_target + '" />' +
		'			<span class="icon add_variable inline" onClick="ProgrammingTaskUtil.onProgrammingTaskChooseCreatedVariable(this)">Add Variable</span>' +
		'		</div>' +
		'		<div class="input_src" ' + (is_image ? '' : 'style="display:none;"') + '>' +
		'			<label>Src:</label>' +
		'			<input type="text" class="task_property_field" name="' + prefix + '[src]" value="' + input_src + '" />' +
		'			<span class="icon add_variable inline" onClick="ProgrammingTaskUtil.onProgrammingTaskChooseCreatedVariable(this)">Add Variable</span>' +
		'			<span class="icon add_field_automatically" onClick="FormFieldsUtilObj.addFieldAutomatically(this)" title="Add Field Automatically">add field automatically</span>' +
		(ProgrammingTaskUtil.on_programming_task_choose_image_url_callback ? '<span class="icon search search_page" onClick="ProgrammingTaskUtil.onProgrammingTaskChooseImageUrl(this)">Add Variable</span>' : '') +
		'		</div>' +
		'		<div class="input_title">' +
		'			<label>Title:</label>' +
		'			<input type="text" class="task_property_field" name="' + prefix + '[title]" value="' + input_title + '" />' +
		'			<span class="icon add_variable inline" onClick="ProgrammingTaskUtil.onProgrammingTaskChooseCreatedVariable(this)">Add Variable</span>' +
		'		</div>' +
		'		<div class="clear"></div>' +
		'		<div class="input_previous_html">' +
		'			<label>Previous Html:</label>' +
		'			<textarea class="task_property_field" name="' + prefix + '[previous_html]">' + input_previous_html + '</textarea>' +
		'			<span class="icon add_variable inline inline-area" onClick="ProgrammingTaskUtil.onProgrammingTaskChooseCreatedVariable(this)">Add Variable</span>' +
		'		</div>' +
		'		<div class="input_next_html">' +
		'			<label>Next Html:</label>' +
		'			<textarea class="task_property_field" name="' + prefix + '[next_html]">' + input_next_html + '</textarea>' +
		'			<span class="icon add_variable inline inline-area" onClick="ProgrammingTaskUtil.onProgrammingTaskChooseCreatedVariable(this)">Add Variable</span>' +
		'		</div>' +
		'		<div class="clear"></div>' +
		'		<div class="input_extra_attributes">' +
		'			<label>Extra Attributes:</label>' +
		'		' +
		'			<select class="extra_attributes_type" name="extra_attributes_type" onChange="FormFieldsUtilObj.onChangeExtraAttributesType(this)">' + 
		'				<option value="array"' + (input_extra_attributes_type == "array" ? " selected" : "") + '>Hard-Coded Values</option>' + 
		'				<option value="variable"' + (input_extra_attributes_type == "variable" ? " selected" : "") + '>External Variable or String</option>' + 
		'				<option value="string"' + (input_extra_attributes_type == "string" ? " selected" : "") + '>Input data key with values between #...#</option>' + 
		'			</select>' + 
		'			<input class="task_property_field extra_attributes_variable" type="text" name="' + prefix + '[extra_attributes]" value="' + (input_extra_attributes_type == "variable" ? input_extra_attributes_variable : input_extra_attributes_string) + '"' + (input_extra_attributes_type == "variable" || input_extra_attributes_type == "string" ? "" : ' style="display:none;"') + ' placeHolder="Variable name..." />' +
		'			<span class="icon add_variable inline" onClick="ProgrammingTaskUtil.onProgrammingTaskChooseCreatedVariable(this)"' + (input_extra_attributes_type == "variable" || input_extra_attributes_type == "string" ? "" : ' style="display:none;"') + '>Add Variable</span>' +
		'			<table class="attributes" count="' + input_extra_attributes_array[1] + '" ' + (input_extra_attributes_type == "variable" || input_extra_attributes_type == "string" ? 'style="display:none;"' : "") + '><tbody>' +
		'				<tr>' +
		'					<th class="table_header name">Name</th>' +
		'					<th class="table_header value">Value</th>' +
		'					<th class="table_header icons">' +
		'						<span class="icon add" onClick="FormFieldsUtilObj.addInputFieldExtraAttribute(this, \'[extra_attributes]\');">add extra attribute</span>' +
		'					</th>' +
		'				</tr>' +
						input_extra_attributes_array[0] +
		'			</tbody></table>' +
		'		</div>' +
		'		<div class="input_available_values">' +
		'			<label>Available Values:</label>' +
		'		' + 
		'			<select class="available_values_type" name="available_values_type" onChange="FormFieldsUtilObj.onChangeAvailableValuesType(this)">' + 
		'				<option value="array"' + (input_available_values_type == "array" ? " selected" : "") + '>Hard-Coded Values</option>' + 
		'				<option value="variable"' + (input_available_values_type == "variable" ? " selected" : "") + '>External Variable or String</option>' + 
		'				<option value="string"' + (input_available_values_type == "string" ? " selected" : "") + '>Input data key with values between #...#</option>' + 
		'			</select>' + 
		'			<input class="task_property_field available_values_variable" type="text" name="' + prefix + '[available_values]" value="' + (input_available_values_type == "variable" ? input_available_values_variable : input_available_values_string) + '"' + (input_available_values_type == "variable" || input_available_values_type == "string" ? "" : ' style="display:none;"') + ' placeHolder="Variable name..." />' +
		'			<span class="icon add_variable inline" onClick="ProgrammingTaskUtil.onProgrammingTaskChooseCreatedVariable(this)"' + (input_available_values_type == "variable" || input_available_values_type == "string" ? "" : ' style="display:none;"') + '>Add Variable</span>' +
		'			<table class="values" count="' + input_available_values_array[1] + '" ' + (input_available_values_type == "variable" || input_available_values_type == "string" ? 'style="display:none;"' : "") + '><tbody>' +
		'				<tr>' +
		'					<th class="table_header old_value">Old Value</th>' +
		'					<th class="table_header new_value">New Value</th>' +
		'					<th class="table_header icons">' +
		'						<span class="icon add" onClick="FormFieldsUtilObj.addInputFieldAvailableValue(this, \'[available_values]\');">add available value</span>' +
		'					</th>' +
		'				</tr>' +
						input_available_values_array[0] +
		'			</tbody></table>' +
		'		</div>' +
		'		<div class="input_options" ' + (is_select ? '' : 'style="display:none;"') + '>' +
		'			<label>Options:</label>' +
		'		' + 
		'			<select class="options_type" name="options_type" onChange="FormFieldsUtilObj.onChangeOptionsType(this)">' + 
		'				<option value="array"' + (input_options_type == "array" ? " selected" : "") + '>Hard-Coded Values</option>' + 
		'				<option value="variable"' + (input_options_type == "variable" ? " selected" : "") + '>External Variable or String</option>' +
		'				<option value="string"' + (input_options_type == "string" ? " selected" : "") + '>Input data key with values between #...#</option>' + 
		'			</select>' + 
		'			<input class="task_property_field options_variable" type="text" name="' + prefix + '[options]" value="' + (input_options_type == "variable" ? input_options_variable : input_options_string) + '"' + (input_options_type == "variable" || input_options_type == "string" ? "" : ' style="display:none;"') + ' placeHolder="Variable name..." />' +
		'			<span class="icon add_variable inline" onClick="ProgrammingTaskUtil.onProgrammingTaskChooseCreatedVariable(this)"' + (input_options_type == "variable" || input_options_type == "string" ? "" : ' style="display:none;"') + '>Add Variable</span>' +
		'			<table class="options" count="' + input_options_array[1] + '" ' + (input_options_type == "variable" || input_options_type == "string" ? 'style="display:none;"' : "") + '><tbody>' +
		'				<tr>' +
		'					<th class="table_header value">Value</th>' +
		'					<th class="table_header label">Label</th>' +
		'					<th class="table_header other_attributes">Other Attributes</th>' +
		'					<th class="table_header icons">' +
		'						<span class="icon add" onClick="FormFieldsUtilObj.addInputFieldOption(this, \'[options]\');">add option</span>' +
		'					</th>' +
		'				</tr>' +
						input_options_array[0] +
		'			</tbody></table>' +
		'		</div>' +
		'		<div class="other_settings"' + (is_hidden ? 'style="display:none;"' : '') + '>' +
		'			<label>Other Settings:</label>' +
		'			' +
		'			<div class="confirmation" ' + (is_button ? '' : 'style="display:none;"') + ' title="Check this box if you wish the user confirms his intention every time that he clicks in this button.">' +
		'				<label>Confirm:</label>';
		
		if (confirmation === 1 || confirmation === 0)
			html += '			<input type="checkbox" class="task_property_field" name="' + prefix + '[confirmation]" value="1" ' + (confirmation === 1 ? 'checked' : '') + ' />';
		else
			html += '' +
			'			<select class="task_property_field" name="' + prefix + '[confirmation]">' +
			'				<option value="1">YES</option>' +
			'				<option value="0">NO</option>' +
			'				<option selected>' + confirmation + '</option>' +
			'			</select>';
	
		html += '' +
		'			</div>' +
		'			<div class="confirmation_message" ' + (is_button ? '' : 'style="display:none;"') + ' title="This is the confirmation message that appears to the user when he clicks in this button.">' +
		'				<label>Confirm Message:</label>' +
		'				<input type="text" class="task_property_field" name="' + prefix + '[confirmation_message]" value="' + confirmation_message + '" />' +
		'				<span class="icon add_variable inline" onClick="ProgrammingTaskUtil.onProgrammingTaskChooseCreatedVariable(this)">Add Variable</span>' +
		'			</div>' +
		'			<div class="allow_null" ' + (is_input || is_select || is_search || is_url || is_email || is_tel || is_number || is_range || is_date || is_color ? '' : 'style="display:none;"') + ' title="Check this box to allow empty values.">' +
		'				<label>Allow Null:</label>';
		
		if (allow_null === 1 || allow_null === 0)
			html += '<input type="checkbox" class="task_property_field" name="' + prefix + '[allow_null]" value="1" ' + (allow_null === 1 ? 'checked' : '') + ' />';
		else
			html += '' +
		'				<select class="task_property_field" name="' + prefix + '[allow_null]">' +
		'					<option value="1">YES</option>' +
		'					<option value="0">NO</option>' +
		'					<option selected>' + allow_null + '</option>' +
		'				</select>';
	
		html += '' +
		'			</div>' +
		'			<div class="allow_javascript" ' + (is_input || is_select || is_search || is_url || is_email || is_tel || is_number || is_range || is_date || is_color ? '' : 'style="display:none;"') + ' title="Allow Javascript Code">' +
		'				<label>Allow Javascript:</label>';
		
		if (allow_javascript === 1 || allow_javascript === 0)
			html += '<input type="checkbox" class="task_property_field" name="' + prefix + '[allow_javascript]" value="1" ' + (allow_javascript === 1 ? 'checked' : '') + ' />';
		else
			html += '' +
		'				<select class="task_property_field" name="' + prefix + '[allow_javascript]">' +
		'					<option value="1">YES</option>' +
		'					<option value="0">NO</option>' +
		'					<option selected>' + allow_javascript + '</option>' +
		'				</select>';
	
		html += '' +
		'			</div>' +
		'			<div class="validation_label" ' + (is_input || is_select || is_search || is_url || is_email || is_tel || is_number || is_range || is_date || is_color ? '' : 'style="display:none;"') + ' title="This label should contain the input label to be shown in the automatic validation message, in case is not defined bellow.">' +
		'				<label>Validation Label:</label>' +
		'				<input type="text" class="task_property_field" name="' + prefix + '[validation_label]" value="' + validation_label + '" />' +
		'				<span class="icon add_variable inline" onClick="ProgrammingTaskUtil.onProgrammingTaskChooseCreatedVariable(this)">Add Variable</span>' +
		'			</div>' +
		'			<div class="validation_message" ' + (is_input || is_select || is_search || is_url || is_email || is_tel || is_number || is_range || is_date || is_color ? '' : 'style="display:none;"') + ' title="This message should contain the correct input format that the user should follow.">' +
		'				<label>Validation Message:</label>' +
		'				<input type="text" class="task_property_field" name="' + prefix + '[validation_message]" value="' + validation_message + '" />' +
		'				<span class="icon add_variable inline" onClick="ProgrammingTaskUtil.onProgrammingTaskChooseCreatedVariable(this)">Add Variable</span>' +
		'			</div>' +
		'			<div class="validation_type" ' + (is_input || is_search || is_url || is_email || is_tel || is_number || is_range || is_date || is_color ? '' : 'style="display:none;"') + ' title="This will automatically check the user input value and see if it\'s a valid value.">' +
		'				<label>Validation Type:</label>' +
		'				<select class="task_property_field" name="' + prefix + '[validation_type]" onChange="FormFieldsUtilObj.onChangeInputFieldValidationType(this)">';
	
		exists = false;
		for (var i = 0; i < validation_types.length; i++) {
			html += '<option' + (validation_types[i] == validation_type ? ' selected' : '') + '>' + validation_types[i] + '</option>';

			if (validation_types[i] == validation_type)
				exists = true;
		}

		if (!exists && validation_type)
			html += '<option selected>' + validation_type + '</option>';
	
		html += '' +
		'				</select>' +
		'			</div>' +
		'			<div class="validation_regex" ' + (!validation_type && (is_input || is_search || is_url || is_email || is_tel || is_number || is_range || is_date || is_color) ? '' : 'style="display:none;"') + ' title="Insert here the regex which will validate the user input value. Regex can be between slashes or without.">' +
		'				<label>Validation Regex:</label>' +
		'				<input type="text" class="task_property_field" name="' + prefix + '[validation_regex]" value="' + validation_regex + '" />' +
		'				<span class="icon add_variable inline" onClick="ProgrammingTaskUtil.onProgrammingTaskChooseCreatedVariable(this)">Add Variable</span>' +
		'			</div>' +
		'			<div class="validation_func" ' + (!validation_type && (is_input || is_search || is_url || is_email || is_tel || is_number || is_range || is_date || is_color) ? '' : 'style="display:none;"') + ' title="Insert here the javascript func which will validate the user input value. This function must return true/false and receives the arguments: Input value, Validation-Type as string, Validation-Regex as string.">' +
		'				<label>Validation Func:</label>' +
		'				<input type="text" class="task_property_field" name="' + prefix + '[validation_func]" value="' + validation_func + '" />' +
		'				<span class="icon add_variable inline" onClick="ProgrammingTaskUtil.onProgrammingTaskChooseCreatedVariable(this)">Add Variable</span>' +
		'			</div>' +
		'			<div class="min_length" ' + (is_input || is_search || is_url || is_email || is_tel ? '' : 'style="display:none;"') + ' title="Minimum user input length">' +
		'				<label>Min Length:</label>' +
		'				<input type="text" class="task_property_field" name="' + prefix + '[min_length]" value="' + min_length + '" />' +
		'				<span class="icon add_variable inline" onClick="ProgrammingTaskUtil.onProgrammingTaskChooseCreatedVariable(this)">Add Variable</span>' +
		'			</div>' +
		'			<div class="max_length" ' + (is_input || is_search || is_url || is_email || is_tel ? '' : 'style="display:none;"') + ' title="Maximum user input length">' +
		'				<label>Max Length:</label>' +
		'				<input type="text" class="task_property_field" name="' + prefix + '[max_length]" value="' + max_length + '" />' +
		'				<span class="icon add_variable inline" onClick="ProgrammingTaskUtil.onProgrammingTaskChooseCreatedVariable(this)">Add Variable</span>' +
		'			</div>' +
		'			<div class="min_value" ' + (is_input || is_date || is_search || is_tel || is_number || is_range ? '' : 'style="display:none;"') + ' title="Minimum user input value">' +
		'				<label>Min Value:</label>' +
		'				<input type="text" class="task_property_field" name="' + prefix + '[min_value]" value="' + min_value + '" />' +
		'				<span class="icon add_variable inline" onClick="ProgrammingTaskUtil.onProgrammingTaskChooseCreatedVariable(this)">Add Variable</span>' +
		'			</div>' +
		'			<div class="max_value" ' + (is_input || is_date || is_search || is_tel || is_number || is_range ? '' : 'style="display:none;"') + ' title="Maximum user input value">' +
		'				<label>Max Value:</label>' +
		'				<input type="text" class="task_property_field" name="' + prefix + '[max_value]" value="' + max_value + '" />' +
		'				<span class="icon add_variable inline" onClick="ProgrammingTaskUtil.onProgrammingTaskChooseCreatedVariable(this)">Add Variable</span>' +
		'			</div>' +
		'			<div class="min_words" ' + (is_input || is_search ? '' : 'style="display:none;"') + ' title="Minimum words number">' +
		'				<label>Min Words #:</label>' +
		'				<input type="text" class="task_property_field" name="' + prefix + '[min_words]" value="' + min_words + '" />' +
		'				<span class="icon add_variable inline" onClick="ProgrammingTaskUtil.onProgrammingTaskChooseCreatedVariable(this)">Add Variable</span>' +
		'			</div>' +
		'			<div class="max_words" ' + (is_input || is_search ? '' : 'style="display:none;"') + ' title="Maximum words number">' +
		'				<label>Max Words #:</label>' +
		'				<input type="text" class="task_property_field" name="' + prefix + '[max_words]" value="' + max_words + '" />' +
		'				<span class="icon add_variable inline" onClick="ProgrammingTaskUtil.onProgrammingTaskChooseCreatedVariable(this)">Add Variable</span>' +
		'			</div>' +
		'		</div>';
		
		return html;
	},
	
	onChangeFieldDisableGroup : function(elm) {
		elm = $(elm);
		
		if (elm.is(":checked"))
			elm.parent().parent().children(".class").hide();
		else 
			elm.parent().parent().children(".class").show();
	},
	
	onChangeInputFieldValidationType : function(elm) {
		/* DEPRECATED bc now we can have validation_type together with validation_regex and validation_func
		 elm = $(elm);
		
		if (elm.val() == "")
			elm.parent().parent().children(".validation_regex, .validation_func").show();
		else 
			elm.parent().parent().children(".validation_regex, .validation_func").hide();*/
	},
	
	onChangeInputFieldType : function(elm) {
		elm = $(elm);
		var type = elm.val();
		var input_div = elm.parent().closest(".input_settings");
		var is_visible = input_div.css("display") == "block";
		
		if (is_visible) {
			var input_other_settings = input_div.children(".other_settings");
			var are_input_setings_visible = input_div.children(".minimize_field_input_settings").hasClass("minimize");
			
			if (type == "select" || type == "checkbox" || type == "radio") {
				input_div.children(".input_name, .input_value, .input_options").show();
				input_div.children(".input_place_holder, .input_href, .input_target, .input_src").hide();
				input_other_settings.children(".allow_javascript, .confirmation, .confirmation_message, .validation_type, .validation_regex, .validation_func, .min_length, .max_length, .min_value, .max_value, .min_words, .max_words").hide();
				
				if (are_input_setings_visible) {
					input_div.children(".other_settings").show();
					input_other_settings.children(".allow_null, .validation_label, .validation_message").show();
				}
				else
					input_div.children(".other_settings").hide();
			}
			else if (type == "image") {
				input_div.children(".input_name, .input_value, .input_place_holder, .input_href, .input_target, .input_options, .other_settings").hide();
				input_div.children(".input_src").show();
			}
			else if (type == "file") {
				input_div.children(".input_place_holder, .input_href, .input_target, .input_src, .input_options").hide();
				input_div.children(".input_name, .input_value").show(); //leave input_value shown. This is very important bc it will be used to create a new field with the current value of this correspondent attribute. This is, there could be a POST field with this new value.
				
				if (are_input_setings_visible) {
					input_div.children(".other_settings").show();
					input_other_settings.children(".allow_null, .validation_label, .validation_message").show();
					input_other_settings.children(".allow_javascript, .validation_type, .validation_regex, .validation_func, .min_length, .max_length, .min_value, .max_value, .min_words, .max_words").hide();
				}
				else
					input_div.children(".other_settings").hide();
			}
			else if (type == "submit" || type == "button" || type == "button_img") {
				input_div.children(".input_name, .input_value").show();
				input_div.children(".input_options, .input_href, .input_target, .input_place_holder").hide();
				input_other_settings.children(".allow_null, .allow_javascript, .validation_label, .validation_message, .validation_type, .validation_regex, .validation_func, .min_length, .max_length, .min_value, .max_value, .min_words, .max_words").hide();
				
				if (are_input_setings_visible) {
					input_div.children(".other_settings").show();
					input_other_settings.children(".confirmation, .confirmation_message").show();
				}
				else
					input_div.children(".other_settings").hide();
				
				if (type == "button_img")
					input_div.children(".input_src").show();
				else
					input_div.children(".input_src").hide();
			}
			else {
				input_div.children(".input_options, .input_place_holder, .input_src").hide();
				input_div.children(".input_value").show();
				input_other_settings.children(".confirmation, .confirmation_message").hide();
				
				if (type == "hidden") {
					input_div.children(".input_href, .input_target, .other_settings").hide();
					input_div.children(".input_name").show();
				}
				else if (type == "label" || type == "h1" || type == "h2" || type == "h3" || type == "h4" || type == "h5")
					input_div.children(".input_href, .input_target, .input_name, .other_settings").hide();
				else if (type == "link") {
					input_div.children(".input_name, .other_settings").hide();
					input_div.children(".input_href, .input_target").show();
				}
				else {
					input_div.children(".input_href, .input_target").hide();
					input_div.children(".input_name, .input_place_holder").show();
					
					if (are_input_setings_visible) {
						input_div.children(".other_settings").show();
						input_other_settings.children(".allow_null, .allow_javascript, .validation_label, .validation_message, .validation_type").show();
						
						switch (type) {
							case "email": 
							case "url": 
								input_other_settings.children(".min_length, .max_length").show();
								input_other_settings.children(".min_value, .max_value, .min_words, .max_words").hide();
								break;
							case "tel": 
								input_other_settings.children(".min_value, .max_value, .min_length, .max_length").show();
								input_other_settings.children(".min_words, .max_words").hide();
								break;
							case "number": 
							case "range": 
							case "date": 
							case "month": 
							case "week": 
							case "time": 
							case "datetime": 
							case "datetime-local": 
								input_other_settings.children(".min_value, .max_value").show();
								input_other_settings.children(".min_length, .max_length, .min_words, .max_words").hide();
								break;
							case "color": 
								input_other_settings.children(".min_length, .max_length, .min_value, .max_value, .min_words, .max_words").hide();
								break;
							default: 
								input_other_settings.children(".allow_null, .allow_javascript, .validation_label, .validation_message, .validation_type, .min_length, .max_length, .min_value, .max_value, .min_words, .max_words").show();
						}
						
						if (input_other_settings.find(".validation_type select").val() == "")
							input_other_settings.find(".validation_regex, .validation_func").show();
					}
					else
						input_div.children(".other_settings").hide();
				}
			}
		}
	},
	
	minimizeField : function(elm) {
		elm = $(elm);
		var main_div = elm.parent();
		
		if (elm.hasClass("minimize")) {
			main_div.children(".label_settings, .input_settings").hide();
			elm.removeClass("minimize").addClass("maximize");
		}
		else {
			main_div.children(".label_settings, .input_settings").show();
			elm.removeClass("maximize").addClass("minimize");
			
			this.onChangeInputFieldType( main_div.find(".input_type select")[0] );
		}
	},
	
	minimizeFieldLabelSettings : function(elm) {
		elm = $(elm);
		var main_div = elm.parent();
		
		if (elm.hasClass("minimize")) {
			main_div.children(".label_title, .label_previous_html, .label_next_html, .label_extra_attributes").hide();
			elm.removeClass("minimize").addClass("maximize");
		}
		else {
			main_div.children(".label_title, .label_previous_html, .label_next_html, .label_extra_attributes").show();
			elm.removeClass("maximize").addClass("minimize");
		}
	},
	
	minimizeFieldInputSettings : function(elm) {
		elm = $(elm);
		var main_div = elm.parent();
		
		if (elm.hasClass("minimize")) {
			main_div.children(".input_title, .input_previous_html, .input_next_html, .input_extra_attributes, .input_available_values, .other_settings").hide();
			elm.removeClass("minimize").addClass("maximize");
		}
		else {
			main_div.children(".input_title, .input_previous_html, .input_next_html, .input_extra_attributes, .input_available_values, .other_settings").show();
			elm.removeClass("maximize").addClass("minimize");
			
			this.onChangeInputFieldType( main_div.find(".input_type select")[0] );
		}
	},
	
	//PREPARING EXTRA ATTRIBUTES HTML
	onChangeExtraAttributesType : function(elm) {
		this.onChangeVariableValuesType(elm);
	},
	
	prepareInputFieldExtraAttributes : function(prefix, extra_attributes) {
		extra_attributes = this.parseInputFieldExtraAttributes(extra_attributes);
		
		return this.prepareInputFieldVariableValues(prefix, extra_attributes, this.getInputFieldExtraAttributeHtml);
	},
	
	parseInputFieldExtraAttributes : function(extra_attributes) {
		if (extra_attributes && $.isPlainObject(extra_attributes)) {
			var is_associative = true;
			
			$.each(extra_attributes, function(key, value) {
				if ($.isPlainObject(value)) { //it means for each item there will be: {name: "", value: ""}
					is_associative = false;
					return false;
				}
			});
			
			if (is_associative) {
				var eas = [];
				$.each(extra_attributes, function(key, value) {
					eas.push({"name": key, "value": value});
				});
				extra_attributes = eas;
			}
		}
		
		return extra_attributes;
	},
	
	addInputFieldExtraAttribute : function(elm, suffix) {
		this.addInputFieldVariableValues(elm, suffix, this.getInputFieldExtraAttributeHtml, {});
	},
	
	getInputFieldExtraAttributeHtml : function(prefix, data) {
		data = $.isEmptyObject(data) ? {} : data;
		
		var name = data["name"];
		var value = data["value"];
		
		name = typeof name != "undefined" && name != null ? ("" + name).replace(/"/g, "&quot;") : "";
		value = typeof value != "undefined" && value != null ? ("" + value).replace(/"/g, "&quot;") : "";
		
		var html = 
		'<tr>' +
		'	<td class="name">' +
		'		<input type="text" class="task_property_field" name="' + prefix + '[name]" value="' + name + '" />' +
		'		<span class="icon add_variable inline" onClick="ProgrammingTaskUtil.onProgrammingTaskChooseCreatedVariable(this)">Add Variable</span>' +
		'	</td>' +
		'	<td class="value">' +
		'		<input type="text" class="task_property_field" name="' + prefix + '[value]" value="' + value + '" />' +
		'		<span class="icon add_variable inline" onClick="ProgrammingTaskUtil.onProgrammingTaskChooseCreatedVariable(this)">Add Variable</span>' +
		'	</td>' +
		'	<td class="table_header icons">' +
		'		<span class="icon remove" onClick="$(this).parent().parent().remove()">remove attribute</span>' +
		'	</td>' +
		'</tr>';
		
		return html;
	},
	
	//PREPARING AVAILABLE VALUES HTML
	onChangeAvailableValuesType : function(elm) {
		this.onChangeVariableValuesType(elm);
	},
	
	prepareInputFieldAvailableValues : function(prefix, available_values) {
		available_values = this.parseInputFieldAvailableValues(available_values);
		
		return this.prepareInputFieldVariableValues(prefix, available_values, this.getInputFieldAvailableValueHtml);
	},
	
	parseInputFieldAvailableValues : function(available_values) {
		if (available_values && $.isPlainObject(available_values)) {
			var is_associative = true;
			
			$.each(available_values, function(key, value) {
				if ($.isPlainObject(value)) { //it means for each item there will be: {old_value: "", new_value: ""}
					is_associative = false;
					return false;
				}
			});
			
			if (is_associative) {
				var avs = [];
				$.each(available_values, function(key, value) {
					avs.push({"old_value": key, "new_value": value});
				});
				available_values = avs;
			}
		}
		
		return available_values;
	},
	
	addInputFieldAvailableValue : function(elm, suffix) {
		this.addInputFieldVariableValues(elm, suffix, this.getInputFieldAvailableValueHtml, {});
	},
	
	getInputFieldAvailableValueHtml : function(prefix, data) {
		data = $.isEmptyObject(data) ? {} : data;
		
		var old_value = data["old_value"];
		var new_value = data["new_value"];
		
		old_value = typeof old_value != "undefined" && old_value != null ? ("" + old_value).replace(/"/g, "&quot;") : "";
		new_value = typeof new_value != "undefined" && new_value != null ? ("" + new_value).replace(/"/g, "&quot;") : "";
		
		var html = 
		'<tr>' +
		'	<td class="old_value">' +
		'		<input type="text" class="task_property_field" name="' + prefix + '[old_value]" value="' + old_value + '" />' +
		'		<span class="icon add_variable inline" onClick="ProgrammingTaskUtil.onProgrammingTaskChooseCreatedVariable(this)">Add Variable</span>' +
		'	</td>' +
		'	<td class="new_value">' +
		'		<input type="text" class="task_property_field" name="' + prefix + '[new_value]" value="' + new_value + '" />' +
		'		<span class="icon add_variable inline" onClick="ProgrammingTaskUtil.onProgrammingTaskChooseCreatedVariable(this)">Add Variable</span>' +
		'	</td>' +
		'	<td class="table_header icons">' +
		'		<span class="icon remove" onClick="$(this).parent().parent().remove()">remove attribute</span>' +
		'	</td>' +
		'</tr>';
		
		return html;
	},
	
	//PREPARING OPTIONS HTML
	onChangeOptionsType : function(elm) {
		this.onChangeVariableValuesType(elm);
	},
	
	prepareInputFieldOptions : function(prefix, options) {
		options = this.parseInputFieldOptions(options);
		
		return this.prepareInputFieldVariableValues(prefix, options, this.getInputFieldOptionHtml);
	},
	
	parseInputFieldOptions : function(options) {
		if (options && $.isPlainObject(options)) {
			var is_associative = true;
			
			$.each(options, function(key, value) {
				if ($.isPlainObject(value)) { //it means for each item there will be: {value: "", label: ""}
					is_associative = false;
					return false;
				}
			});
			
			if (is_associative) {
				var opts = [];
				$.each(options, function(key, value) {
					opts.push({"value": key, "label": value});
				});
				options = opts;
			}
		}
		
		return options;
	},
	
	addInputFieldOption : function(elm, suffix) {
		this.addInputFieldVariableValues(elm, suffix, this.getInputFieldOptionHtml, {});
	},
	
	getInputFieldOptionHtml : function(prefix, data) {
		data = $.isEmptyObject(data) ? {} : data;
		
		var value = data["value"];
		var label = data["label"];
		var other_attributes = data["other_attributes"];
		
		value = typeof value != "undefined" && value != null ? ("" + value + "").replace(/"/g, "&quot;") : "";
		label = typeof label != "undefined" && label != null ? ("" + label).replace(/"/g, "&quot;") : "";
		other_attributes = typeof other_attributes != "undefined" && other_attributes != null ? ("" + other_attributes).replace(/"/g, "&quot;") : "";
		
		var html = 
		'<tr>' +
		'	<td class="value">' +
		'		<input type="text" class="task_property_field" name="' + prefix + '[value]" value="' + value + '" />' +
		'		<span class="icon add_variable inline" onClick="ProgrammingTaskUtil.onProgrammingTaskChooseCreatedVariable(this)">Add Variable</span>' +
		'	</td>' +
		'	<td class="label">' +
		'		<input type="text" class="task_property_field" name="' + prefix + '[label]" value="' + label + '" />' +
		'		<span class="icon add_variable inline" onClick="ProgrammingTaskUtil.onProgrammingTaskChooseCreatedVariable(this)">Add Variable</span>' +
		'	</td>' +
		'	<td class="other_attributes">' +
		'		<input type="text" class="task_property_field" name="' + prefix + '[other_attributes]" value="' + other_attributes + '" />' +
		'		<span class="icon add_variable inline" onClick="ProgrammingTaskUtil.onProgrammingTaskChooseCreatedVariable(this)">Add Variable</span>' +
		'	</td>' +
		'	<td class="table_header icons">' +
		'		<span class="icon remove" onClick="$(this).parent().parent().remove()">remove option</span>' +
		'	</td>' +
		'</tr>';
		
		return html;
	},
	
	//PREPARING PAGINATION HTML
	addPagination : function(elm, suffix) {
		var p = $(elm).parent();
		var prefix = p.attr("prefix");
		prefix = typeof prefix == "undefined" ? "" : prefix;
		var parent_div = p.children(".fields");
		
		var idx = parseInt(parent_div.attr("count"));
		idx = idx >= 0 ? idx + 1 : 0;
		parent_div.attr("count", idx);
		
		var html = this.getPaginationHtml(prefix + suffix + "[" + idx + "]", {});
		
		parent_div.append(html);
		
		ProgrammingTaskUtil.onProgrammingTaskPropertiesNewHtml( parent_div.children("div").last() );
		
		this.forceMaximizingContainer(elm);
	},
	
	getPaginationHtml : function(prefix, data) {
		data = $.isEmptyObject(data) ? {} : data;
		
		var total_rows = data["total_rows"];
		var rows_per_page = data["rows_per_page"];
		var page_number = data["page_number"];
		var max_num_of_shown_pages = data["max_num_of_shown_pages"];
		var pagination_template = data["pagination_template"];
		var page_attr_name = data["page_attr_name"];
		var on_click_js_func = data["on_click_js_func"];
		
		prefix += '[pagination]';
		
		total_rows = total_rows ? total_rows : "";
		rows_per_page = rows_per_page ? rows_per_page : "";
		page_number = page_number ? page_number : "";
		max_num_of_shown_pages = max_num_of_shown_pages ? max_num_of_shown_pages : "";
		pagination_template = pagination_template ? pagination_template : "";
		page_attr_name = page_attr_name ? page_attr_name : "";
		on_click_js_func = on_click_js_func ? on_click_js_func : "";
		
		var html = 
		'<div class="pagination" prefix="' + prefix + '">' +
			'<label>Pagination: </label>' +
			'<span class="icon remove" onClick="$(this).parent().remove()" title="Remove Pagination">remove pagination</span>' + 
			'<div class="pagination_template">' +
			'	<label>Pagination Template:</label>' +
			'	<select class="task_property_field" name="' + prefix + '[pagination_template]">';
		
		var templates = ["design1"];
		var exists = false;
		for (var i = 0; i < templates.length; i++) {
			html += '<option' + (templates[i] == pagination_template ? ' selected' : '') + '>' + templates[i] + '</option>';
			
			if (templates[i] == pagination_template)
				exists = true;
		}
		
		if (!exists)
			html += '<option selected>' + pagination_template + '</option>';
		
		html += '</select>' +
			'</div>' +
			'<div class="rows_per_page">' +
			'	<label>Rows per Page:</label>' +
			'	<input type="text" class="task_property_field" name="' + prefix + '[rows_per_page]" value="' + rows_per_page + '" />' +
			'	<span class="icon add_variable inline" onClick="ProgrammingTaskUtil.onProgrammingTaskChooseCreatedVariable(this)">Add Variable</span>' +
			'</div>' +
			'<div class="page_number">' +
			'	<label>Page Number:</label>' +
			'	<input type="text" class="task_property_field" name="' + prefix + '[page_number]" value="' + page_number + '" />' +
			'	<span class="icon add_variable inline" onClick="ProgrammingTaskUtil.onProgrammingTaskChooseCreatedVariable(this)">Add Variable</span>' +
			'</div>' +
			'<div class="max_num_of_shown_pages">' +
			'	<label>Max # of Shown Pages:</label>' +
			'	<input type="text" class="task_property_field" name="' + prefix + '[max_num_of_shown_pages]" value="' + max_num_of_shown_pages + '" />' +
			'	<span class="icon add_variable inline" onClick="ProgrammingTaskUtil.onProgrammingTaskChooseCreatedVariable(this)">Add Variable</span>' +
			'</div>' +
			'<div class="total_rows">' +
			'	<label>Total # of Rows:</label>' +
			'	<input type="text" class="task_property_field" name="' + prefix + '[total_rows]" value="' + total_rows + '" />' +
			'	<span class="icon add_variable inline" onClick="ProgrammingTaskUtil.onProgrammingTaskChooseCreatedVariable(this)">Add Variable</span>' +
			'</div>' +
			'<div class="page_attr_name">' +
			'	<label>Page Attr Name:</label>' +
			'	<input type="text" class="task_property_field" name="' + prefix + '[page_attr_name]" value="' + page_attr_name + '" />' +
			'	<span class="icon add_variable inline" onClick="ProgrammingTaskUtil.onProgrammingTaskChooseCreatedVariable(this)">Add Variable</span>' +
			'</div>' +
			'<div class="on_click_js_func">' +
			'	<label>OnClick Func Name:</label>' +
			'	<input type="text" class="task_property_field" name="' + prefix + '[on_click_js_func]" value="' + on_click_js_func + '" placeHolder="...default function..." />' +
			'	<span class="icon add_variable inline" onClick="ProgrammingTaskUtil.onProgrammingTaskChooseCreatedVariable(this)">Add Variable</span>' +
			'</div>' +
		'</div>';
		
		return html;
	},
	
	//PREPARING TABLE HTML
	addTable : function(elm, suffix) {
		var p = $(elm).parent();
		var prefix = p.attr("prefix");
		prefix = typeof prefix == "undefined" ? "" : prefix;
		var parent_div = p.children(".fields");
		
		var idx = parseInt(parent_div.attr("count"));
		idx = idx >= 0 ? idx + 1 : 0;
		parent_div.attr("count", idx);
		
		var html = this.getTableHtml(prefix + suffix + "[" + idx + "]", {});
		
		parent_div.append(html);
		
		ProgrammingTaskUtil.onProgrammingTaskPropertiesNewHtml( parent_div.children("div").last() );
		
		this.forceMaximizingContainer(elm);
	},
	
	getTableHtml : function(prefix, data) {
		data = $.isEmptyObject(data) ? {} : data;
		
		var table_class = data["table_class"];
		var rows_class = data["rows_class"];
		var default_input_data = data["default_input_data"];
		var elements = data["elements"];
		
		prefix += '[table]';
		
		table_class = table_class ? table_class : "";
		rows_class = rows_class ? rows_class : "";
		default_input_data = default_input_data ? default_input_data : "";
		elements = elements ? elements : {};
		
		var els = this.prepareChildElements(prefix + '[elements]', elements);
		
		var html = 
		'<div class="table" prefix="' + prefix + '">' + 
		'	<label>Table: </label>' +
		'	<span class="icon group_add" onClick="FormFieldsUtilObj.addContainer(this, \'[elements]\');" title="Add Sub Container">add sub container</span>' + 
		'	<span class="icon item_add" onClick="FormFieldsUtilObj.addInputField(this, \'[elements]\');" title="Add Input Field">add input</span>' + 
		'	<span class="icon pagination_add" onClick="FormFieldsUtilObj.addPagination(this, \'[elements]\');" title="Add Pagination">add pagination</span>' + 
		'	<span class="icon table_add" onClick="FormFieldsUtilObj.addTable(this, \'[elements]\');" title="Add Table">add table</span>' + 
		'	<span class="icon tree_add" onClick="FormFieldsUtilObj.addTree(this, \'[elements]\');" title="Add Tree">add tree</span>' + 
		'	<span class="icon remove remove_field" onClick="$(this).parent().remove()" title="Remove Table">remove table</span>' +
		'	<span class="icon info" onClick="FormFieldsUtilObj.toggleTableProperties(this)" title="Toggle Table properties">toggle table props</span>' +
		'	<span class="icon minimize minimize_field" onClick="FormFieldsUtilObj.minimizeContainer(this)" title="Minimize/Maximize">minimize table</span>' +
		'	<span class="icon move_up" onClick="FormFieldsUtilObj.moveUp(this)" title="Move Up Table">move up table</span>' +
		'	<span class="icon move_down" onClick="FormFieldsUtilObj.moveDown(this)" title="Move Down Table">move down table</span>' +
		'	<span class="icon add_fields_automatically" onClick="FormFieldsUtilObj.addFieldsAutomatically(this)" title="Add Fields Automatically">add fields automatically</span>' +
		'	<div class="table_props">' +
		'		<div class="table_class">' +
		'			<label>Table Class:</label>' +
		'			<input type="text" class="task_property_field" name="' + prefix + '[table_class]" value="' + table_class + '" />' + 
		'			<span class="icon add_variable inline" onClick="ProgrammingTaskUtil.onProgrammingTaskChooseCreatedVariable(this)">Add Variable</span>' +
		'		</div>' +
		'		<div class="rows_class">' +
		'			<label>Rows Class:</label>' +
		'			<input type="text" class="task_property_field" name="' + prefix + '[rows_class]" value="' + rows_class + '" />' +
		'			<span class="icon add_variable inline" onClick="ProgrammingTaskUtil.onProgrammingTaskChooseCreatedVariable(this)">Add Variable</span>' +
		'		</div>' +
		'		<div class="default_input_data">' +
		'			<label>Input Data:</label>' +
		'			<input type="text" class="task_property_field" name="' + prefix + '[default_input_data]" value="' + default_input_data + '" />' +
		'			<span class="icon add_variable inline" onClick="ProgrammingTaskUtil.onProgrammingTaskChooseCreatedVariable(this)">Add Variable</span>' +
		'			<span class="info">Change the default input data, if applies...</span>' +
		'		</div>' +
		'	</div>' +
		'	<div class="fields" count="' + els[1] + '">' + els[0] + '</div>' + 
		'</div>';
		
		return html;
	},
	
	toggleTableProperties : function(elm) {
		elm = $(elm);
		var main_div = elm.parent();
		var table_props = main_div.children(".table_props");
		
		table_props.toggle();
	},
	
	//PREPARING TREE HTML
	addTree : function(elm, suffix) {
		var p = $(elm).parent();
		var prefix = p.attr("prefix");
		prefix = typeof prefix == "undefined" ? "" : prefix;
		var parent_div = p.children(".fields");
		
		var idx = parseInt(parent_div.attr("count"));
		idx = idx >= 0 ? idx + 1 : 0;
		parent_div.attr("count", idx);
		
		var html = this.getTreeHtml(prefix + suffix + "[" + idx + "]", {"recursive_input_data" : "#[0]#"});
		
		parent_div.append(html);
		
		ProgrammingTaskUtil.onProgrammingTaskPropertiesNewHtml( parent_div.children("div").last() );
		
		this.forceMaximizingContainer(elm);
	},
	
	getTreeHtml : function(prefix, data) {
		data = $.isEmptyObject(data) ? {} : data;
		
		var ordered = data["ordered"];
		var recursive = data["recursive"];
		var tree_class = data["tree_class"];
		var lis_class = data["lis_class"];
		var default_input_data = data["default_input_data"];
		var recursive_input_data = data["recursive_input_data"];
		var elements = data["elements"];
		
		prefix += '[tree]';
		
		ordered = ordered ? ordered : 0;
		recursive = recursive ? recursive : 0;
		tree_class = tree_class ? tree_class : "";
		lis_class = lis_class ? lis_class : "";
		default_input_data = default_input_data ? default_input_data : "";
		recursive_input_data = recursive_input_data ? recursive_input_data : "";
		elements = elements ? elements : {};
		
		var els = this.prepareChildElements(prefix + '[elements]', elements);
		
		var html = 
		'<div class="tree" prefix="' + prefix + '">' + 
		'	<label>Tree: </label>' +
		'	<select class="task_property_field ordered" name="' + prefix + '[ordered]">' + 
		'		<option value="0">Unordered</option>' + 
		'		<option value="1"' + (ordered == 1 ? ' selected' : '') + '>Ordered</option>' + 
		'	</select>' + 
		'	<select class="task_property_field ordered" name="' + prefix + '[recursive]" onChange="FormFieldsUtilObj.onChangeTreeRecursiveAttribute(this)">' + 
		'		<option value="1">Recursive</option>' + 
		'		<option value="0"' + (recursive != 1 ? ' selected' : '') + '>Non Recursive</option>' + 
		'	</select>' + 
		'	<span class="icon group_add" onClick="FormFieldsUtilObj.addContainer(this, \'[elements]\');" title="Add Sub Container">add sub container</span>' + 
		'	<span class="icon item_add" onClick="FormFieldsUtilObj.addInputField(this, \'[elements]\');" title="Add Input Field">add input</span>' + 
		'	<span class="icon pagination_add" onClick="FormFieldsUtilObj.addPagination(this, \'[elements]\');" title="Add Pagination">add pagination</span>' + 
		'	<span class="icon table_add" onClick="FormFieldsUtilObj.addTable(this, \'[elements]\');" title="Add Table">add table</span>' + 
		'	<span class="icon tree_add" onClick="FormFieldsUtilObj.addTree(this, \'[elements]\');" title="Add Tree">add tree</span>' + 
		'	<span class="icon remove remove_field" onClick="$(this).parent().remove()" title="Remove Tree">remove tree</span>' +
		'	<span class="icon info" onClick="FormFieldsUtilObj.toggleTreeProperties(this)" title="Toggle Tree properties">toggle tree props</span>' +
		'	<span class="icon minimize minimize_field" onClick="FormFieldsUtilObj.minimizeContainer(this)" title="Minimize/Maximize">minimize tree</span>' +
		'	<span class="icon move_up" onClick="FormFieldsUtilObj.moveUp(this)" title="Move Up Tree">move up tree</span>' +
		'	<span class="icon move_down" onClick="FormFieldsUtilObj.moveDown(this)" title="Move Down Tree">move down tree</span>' +
		'	<span class="icon add_fields_automatically" onClick="FormFieldsUtilObj.addFieldsAutomatically(this)" title="Add Fields Automatically">add fields automatically</span>' +
		'	<div class="tree_props">' +
		'		<div class="tree_class">' +
		'			<label>Tree Class:</label>' +
		'			<input type="text" class="task_property_field" name="' + prefix + '[tree_class]" value="' + tree_class + '" />' + 
		'			<span class="icon add_variable inline" onClick="ProgrammingTaskUtil.onProgrammingTaskChooseCreatedVariable(this)">Add Variable</span>' +
		'		</div>' +
		'		<div class="lis_class">' +
		'			<label>Items Class:</label>' +
		'			<input type="text" class="task_property_field" name="' + prefix + '[lis_class]" value="' + lis_class + '" />' +
		'			<span class="icon add_variable inline" onClick="ProgrammingTaskUtil.onProgrammingTaskChooseCreatedVariable(this)">Add Variable</span>' +
		'		</div>' +
		'		<div class="default_input_data">' +
		'			<label>Input Data:</label>' +
		'			<input type="text" class="task_property_field" name="' + prefix + '[default_input_data]" value="' + default_input_data + '" />' +
		'			<span class="icon add_variable inline" onClick="ProgrammingTaskUtil.onProgrammingTaskChooseCreatedVariable(this)">Add Variable</span>' +
		'			<span class="info">Change the default input data, if applies...</span>' +
		'		</div>' +
		'		<div class="recursive_input_data"' + (recursive == 1 ? '' : ' style="display:none;"') + '>' +
		'			<label>Recursive Input Data:</label>' +
		'			<input type="text" class="task_property_field" name="' + prefix + '[recursive_input_data]" value="' + recursive_input_data + '" />' +
		'			<span class="icon add_variable inline" onClick="ProgrammingTaskUtil.onProgrammingTaskChooseCreatedVariable(this)">Add Variable</span>' +
		'			<span class="info">If tree is recursive, add the correspondent input data for each item...</span>' +
		'		</div>' +
		'	</div>' +
		'	<div class="fields" count="' + els[1] + '">' + els[0] + '</div>' + 
		'</div>';
		
		return html;
	},
	
	toggleTreeProperties : function(elm) {
		elm = $(elm);
		var main_div = elm.parent();
		var tree_props = main_div.children(".tree_props");
		
		tree_props.toggle();
	},
	
	onChangeTreeRecursiveAttribute : function(elm) {
		elm = $(elm);
		var main_div = elm.parent();
		var tree_props = main_div.children(".tree_props");
		
		if (elm.val() == 1)
			tree_props.children(".recursive_input_data").show();
		else
			tree_props.children(".recursive_input_data").hide();
	},
	
	//ADD FIELDS AUTOMATICALLY
	addFieldAutomatically : function(elm) {
		var name = prompt("Please write the attribute name:");
		
		if (name) {
			elm = $(elm);
			var p = elm.parent();
			var main_p = p.parent().closest(".container, .table, .tree");
			
			if (main_p.hasClass("table") || main_p.hasClass("tree"))
				name = '[\\$idx][' + name + ']';
				
			p.children("input").val('#' + name + '#');
		}
	},
	
	addFieldsAutomatically : function(elm) {
		elm = $(elm);
		var p = elm.parent();
		var popup = p.children(".add_fields_automatically_popup");
		
		if (!popup[0]) {
			var type = p.hasClass("container") ? "container" : (p.hasClass("table") ? "table" : (p.hasClass("tree") ? "tree" : "")); //don't simply this code to 'p.attr("class")' bc "p" can have multiple different classes...
			var props_elm_class = type + "_props";
			var props_elm = p.children("." + props_elm_class);
			
			popup = '<div class="add_fields_automatically_popup" style="display:none">';
			
			if (type == "table" || type == "tree")
				popup += '<div class="input_data_variable_name">' +
					'		<label>Input Data Variable Name:</label>' +
					'		<input type="text">' +
					'		<span class="icon add_variable inline" onClick="ProgrammingTaskUtil.onProgrammingTaskChooseCreatedVariable(this)">Add Variable</span>' +
					'		<span class="info">Change the default input data, if applies...</span>' +
					'	</div>';
			
			popup += '	<div class="attributes_name">' +
					'		<label>Attributes Name: </label>' +
					' 		<span class="icon add" onClick="FormFieldsUtilObj.addAttributesToFieldsAutomatically(this);">Add attribute name</span>' +
					'	</div>' +
					'	<div class="button">' +
					'		<button onClick="FormFieldsUtilObj.createFieldsAutomatically(this)">Add</button>' +
					'		<button onClick="FormFieldsUtilObj.cancelFieldsAutomatically(this)">Cancel</button>' +
					'	</div>' +
					'</div>';
			
			popup = $(popup);
			this.addAttributesToFieldsAutomatically( popup.find(".attributes_name .add")[0] );
			
			ProgrammingTaskUtil.onProgrammingTaskPropertiesNewHtml(popup);
			
			props_elm.before(popup);
		}
		
		popup.toggle("slow");
		//popup.show();
	},
	
	createFieldsAutomatically : function(elm) {
		elm = $(elm);
		var popup = elm.parent().closest(".add_fields_automatically_popup");
		var main_p = popup.parent().closest(".container, .table, .tree");
		var type = main_p.hasClass("container") ? "container" : (main_p.hasClass("table") ? "table" : (main_p.hasClass("tree") ? "tree" : "")); //don't simply this code to 'main_p.attr("class")' bc "main_p" can have multiple different classes...
		var is_list = type == "table" || type == "tree";
		var add_field_icon = main_p.children(".item_add");
		var fields = main_p.children(".fields");
		
		//prepare input_data_variable_name
		if (is_list) {
			var input_data_variable_name = popup.find(".input_data_variable_name input").val();
			input_data_variable_name = input_data_variable_name ? input_data_variable_name.replace(/^\s+/g, "").replace(/\s+$/g, "") : "";
			
			if (input_data_variable_name) {
				if (!input_data_variable_name.match(/^\$/) && !input_data_variable_name.match(/^#/) && !input_data_variable_name.match(/#$/))
					input_data_variable_name = '#' + input_data_variable_name + '#';
				
				main_p.children("." + type + "_props").find(".default_input_data input").val(input_data_variable_name);
			}
		}
		
		//prepare attributes
		var inputs = popup.find(".attributes_name .attribute_name input");
		
		$.each(inputs, function(idx, input) {
			var attribute_name = $(input).val();
			attribute_name = attribute_name ? attribute_name.replace(/^\s+/g, "").replace(/\s+$/g, "") : "";
			
			if (attribute_name) {
				add_field_icon.trigger("click");
				
				var field = fields.children(".field").last();
				var attribute_class = attribute_name.replace(/[\_ ]/g, "-").toLowerCase();
				var attribute_label = attribute_name.replace(/[\-_]/g, " ").toLowerCase().replace(/\b[a-z]/g, function(letter) { //ucwords function
					return letter.toUpperCase();
				});
				var attribute_value = '#' + (is_list ? '[\\$idx][' + attribute_name + ']' : attribute_name) + '#';
				
				field.find(".class input").val(attribute_class);
				field.find(".label_value input").val(attribute_label);
				field.find(".input_value input").val(attribute_value);
			}
		});
		
		popup.toggle("slow", function() {
			popup.remove();
		});
	},
	
	cancelFieldsAutomatically : function(elm) {
		elm = $(elm);
		var popup = elm.parent().closest(".add_fields_automatically_popup");
		
		popup.toggle("slow", function() {
			popup.remove();
		});
	},
	
	addAttributesToFieldsAutomatically : function(elm) {
		var html = '<div class="attribute_name">' +
				'	<input type="text">' +
				'	<span class="icon remove" onClick="FormFieldsUtilObj.removeAttributesToFieldsAutomatically(this);">Remove attribute name</span>' +
				'</div>';
		
		var item = $(html);
		
		$(elm).parent().closest(".attributes_name").append(item);
		
		ProgrammingTaskUtil.onProgrammingTaskPropertiesNewHtml(item);
		
		return item;
	},
	
	removeAttributesToFieldsAutomatically : function(elm) {
		$(elm).parent().remove();
	},
	
	//GENERIC
	onChangeVariableValuesType : function(elm) {
		elm = $(elm);
		
		var p = elm.parent();
		var variable = p.children("input");
		var array = p.children("table");
		
		if (elm.val() == "variable" || elm.val() == "string") {
			variable.show();
			array.hide();
		}
		else {
			variable.hide();
			array.show();
		}
		
		ProgrammingTaskUtil.onChangeTaskFieldType(elm[0]);
	},
	
	prepareInputFieldVariableValues : function(prefix, variable_values, get_html_function) {
		var html = '';
		var count = 0;
	
		if (variable_values)
			$.each(variable_values, function(i, value) {
				html += get_html_function(prefix + "[" + count + "]", value);
				count++;
			});
		
		return [html, count - 1];
	},
	
	addInputFieldVariableValues : function(elm, suffix, get_html_function, default_value) {
		var table = $(elm).parent().parent().parent().parent(); //$(elm).parent().closest("table"); //Do not use this, bc this might not exist
		var input_settings = table.parent().parent(); //table.parent().closest(".input_settings"); //Do not use this, bc this might not exist
		var field = input_settings.parent(); //input_settings.parent().closest(".field"); //Do not use this, bc this might not exist
		var midfix = input_settings.attr("prefix");
		var prefix = field.attr("prefix");
		
		midfix = midfix ? midfix : "";
		prefix = prefix ? prefix : "";
		
		var idx = parseInt(table.attr("count"));
		idx = idx >= 0 ? idx + 1 : 0;
		table.attr("count", idx);
	
		var html = get_html_function(prefix + midfix + suffix + "[" + idx + "]", default_value);
		var new_item = $(html);
		
		table.append(new_item);
		
		ProgrammingTaskUtil.onProgrammingTaskPropertiesNewHtml(new_item);
	},
	
	moveUp : function(elm) {
		if (this.move_action_enabled) {//only move if there is no previous moving action still running... Leave this here otherwise the indexes can be messed up (if there is 2 moving actions running at the same time).
			this.move_action_enabled = false;
			
			var field = $(elm).parent();
			var prev = field.prev();
			
			if (prev[0] && (!prev.is("tr") || prev.children("th").length == 0) && !prev.hasClass("moving_not_allowing")) {
				field.parent()[0].insertBefore(field[0], prev[0]);
			
				this.updateMovedItemsPrefix(field, prev);
			}
			
			this.move_action_enabled = true;
		}
	},
	
	moveDown : function(elm) {
		if (this.move_action_enabled) {//only move if there is no previous moving action still running... Leave this here otherwise the indexes can be messed up (if there is 2 moving actions running at the same time).
			this.move_action_enabled = false;
			
			var field = $(elm).parent();
			var next = field.next();
			
			if (next[0] && !next.hasClass("moving_not_allowing")) {
				field.parent()[0].insertBefore(next[0], field[0]);
				
				this.updateMovedItemsPrefix(next, field);
			}
			this.move_action_enabled = true;
		}
	},
	
	//Updating items' prefix
	updateMovedItemsPrefix : function(prev, next) {
		var prev_suffix = getItemPrefixSuffix(prev);
		var next_suffix = getItemPrefixSuffix(next);
		
		var prev_idx = getItemPrefixIndex(prev, prev_suffix);
		var next_idx = getItemPrefixIndex(next, next_suffix);
		
		if ($.isNumeric(prev_idx) && $.isNumeric(next_idx) && prev_idx != next_idx) {//be sure that items previous and next exist and are different items.
			var prev_new_prefix = getItemNewPrefix(prev, prev_suffix, next_idx);
			var next_new_prefix = getItemNewPrefix(next, next_suffix, prev_idx);
			
			replaceItemsPrefix(prev, prev_new_prefix);
			replaceItemsPrefix(next, next_new_prefix);
		}
		
		//Replacing the prefix for the item and all sub-items
		function replaceItemsPrefix(item, new_prefix) {
			var old_prefix = item.attr("prefix");
			
			if (old_prefix != new_prefix) {
				item.attr("prefix", new_prefix);
				
				//prepare all inner items with new prefix like other sub-containers, sub-trees, sub-tables, etc...
				item.find(".fields").children(".container, .field, .pagination, .table, .tree").each(function(idx, child) {
					child = $(child);
					var child_prefix = child.attr("prefix");
					var new_child_prefix = prepareItemChildPrefix(child_prefix, old_prefix, new_prefix);
					//console.log("prefix:"+child_prefix+"###"+new_child_prefix);
					child.attr("prefix", new_child_prefix);
				});
				
				//prepare all input fields with new names
				item.find(".task_property_field").each(function(idx, child) {
					child = $(child);
					var child_name = child.attr("name");
					var new_child_name = prepareItemChildPrefix(child_name, old_prefix, new_prefix);
					//console.log("name:"+child_name+"###"+new_child_name);
					child.attr("name", new_child_name);
				});
			}
		}
		
		//Getting a child name/prefix and replace the old prefix by the new one.
		function prepareItemChildPrefix(child_prefix, item_old_prefix, item_new_prefix) {
			if (child_prefix) {
				var aux = child_prefix.substr(0, item_old_prefix.length);
				
				if (aux == item_old_prefix)
					return item_new_prefix + child_prefix.substr(item_old_prefix.length);
			}
			
			return child_prefix;
		}
		
		//Creating a new prefix based in a new index
		function getItemNewPrefix(item, suffix, idx) {
			if (suffix != "") {
				var prefix = item.attr("prefix");
				var s = prefix.substr(prefix.length - suffix.length);
		
				if (s == suffix) {
					var p = prefix.substr(0, prefix.length - suffix.length);
					var pos = p.lastIndexOf("[");
				
					if (pos > 0)
						return prefix.substr(0, pos) + "[" + idx + suffix;
				}
			}
			
			return null;
		}
	
		//Getting the index for a prefix
		function getItemPrefixIndex(item, suffix) {
			if (suffix != "") {
				var prefix = item.attr("prefix");
				var s = prefix.substr(prefix.length - suffix.length);
			
				if (s == suffix) {
					var p = prefix.substr(0, prefix.length - suffix.length);
					var pos = p.lastIndexOf("[");
				
					if (pos > 0) {
						var idx = p.substr(pos + 1);
						return parseInt(idx);
					}
				}
			}
		
			return null;
		}
	
		//Getting a suffix for a prefix
		function getItemPrefixSuffix(item) {
			var suffix = "";
		
			if (item.hasClass("container"))
				suffix = "][container]";
			else if (item.hasClass("field"))
				suffix = "][field]";
			else if (item.hasClass("pagination"))
				suffix = "][pagination]";
			else if (item.hasClass("table"))
				suffix = "][table]";
			else if (item.hasClass("tree"))
				suffix = "][tree]";
		
			return suffix;
		}
	},
};
