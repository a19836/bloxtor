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

var ArrayTaskUtilObj = {
	
	onLoadArrayItems : function(array_items_html_elm, items, root_label, parent_name) {
		array_items_html_elm = $(array_items_html_elm);
		
		if (!items)
			items = {};
		
		if (items.hasOwnProperty("key") || items.hasOwnProperty("value") || items.hasOwnProperty("value_type") || items.hasOwnProperty("items"))
			items = {0 : items};
		
		//get default parent_name based in the first class_name
		if (!parent_name) {
			var class_name = array_items_html_elm[0] && array_items_html_elm[0].className ? array_items_html_elm[0].className.split(" ") : ["items"];
			parent_name = class_name[0];
		}
		
		var html = this.getItemsHtml(parent_name, null, null, items, true, root_label);
		
		array_items_html_elm.html(html);
		
		ProgrammingTaskUtil.onProgrammingTaskPropertiesNewHtml( array_items_html_elm.children() );
	},
	
	getItemsHtml : function(parent_name, key, key_type, items, is_root, root_label) {
		key = typeof key != "undefined" && key != null ? "" + key : "";
		key_type = typeof key_type != "undefined" ? "" + key_type : "";
		
		var html = '<div class="items">' +
				(!is_root ? 
					'<input type="text" class="key task_property_field" name="' + parent_name + '[key]" value="' + key.replace(/"/g, "&quot;") + '" title="Item key" onKeyUp="ArrayTaskUtilObj.onFieldValueBlur(this)" />' +
					'<select class="key_type task_property_field" name="' + parent_name + '[key_type]" title="Item key type">' +
						'<option></option>' +
						'<option' + (key_type == "variable" ? " selected" : "") + '>variable</option>' +
						'<option' + (key_type == "string" ? " selected" : "") + '>string</option>' +
						'<option value="null"' + (key_type == "null" ? " selected" : "") + '>-- none --</option>' +
					'</select>' +
					'<a class="icon remove" onClick="ArrayTaskUtilObj.removeGroup(this)" title="Remove group">remove</a>'
				 : "<label>" + (root_label ? root_label + ":" : "") + "</label>") + 
				'<a class="icon group_add" onClick="ArrayTaskUtilObj.addGroup(this)" title="Add new group">add group</a>' + 
				'<a class="icon item_add" onClick="ArrayTaskUtilObj.addItem(this)" title="Add new item">add item</a>' + 
			'</div>';
		
		parent_name += is_root ? "" : "[items]";
		
		html += '<ul parent_name="' + parent_name + '">';
		
		//convert object to array
		if ($.isArray(items) && !$.isPlainObject(items)) {
			var items_obj = {};
			
			for (var i = 0, t = items.length; i < t; i++)
				items_obj[i] = items[i];
			
			items = items_obj;
		}
		
		var idx = 0;
		for (var i in items) {
			idx++;
			
			var item = items[i];
			
			if ($.isPlainObject(item)) {
				var key = item["key"];
				var key_type = item["key_type"];
				
				if (item.hasOwnProperty("items") && item["items"]) {
				/*
				Sample:
				Array
				(
					 [0] => Array
					(
						 [key] => 0
						 [key_type] => 
						 [items] => Array
						(
							 [0] => Array
							     (
							         [key] => index
							         [key_type] => string
							         [value] => 1
							         [value_type] => string
							     )

							 [1] => Array
							     (
							         [key] => name
							         [key_type] => string
							         [value] => $condo['special_block_1_name']
							         [value_type] => 
							     )

							 [2] => Array
							     (
							         [key] => active
							         [key_type] => string
							         [value] => $condo['special_block_1_active']
							         [value_type] => 
							     )

						)

					)

					 	[1] => Array....
					 */
					var sub_items = item["items"];
					
					if (sub_items.hasOwnProperty("key") || sub_items.hasOwnProperty("value") || sub_items.hasOwnProperty("value_type") || sub_items.hasOwnProperty("items")) {
						sub_items = {0: sub_items};
					}
					
					html += '<li>' + this.getItemsHtml(parent_name + "[" + idx + "]", key, key_type, sub_items) + '</li>';
				}
				else if (!item.hasOwnProperty("items") && (!item.hasOwnProperty("value") || $.isPlainObject(item["value"])) && (!item.hasOwnProperty("value_type") || $.isPlainObject(item["value_type"]))) {
					/*
					It means it is an associative array with real key values. It means it is a simply array.
					
					Note that we may have some cases where the "value" key or "value_type" can be an object create by the user, so we need to cover this cases. Here is an example: 
						"conditions" => array(
							"da.begin_date" => array(
								array(
									"value" => $_POST["begin_date"],
									"operator" => ">="
								),
								array(
									"value" => $_POST["end_date"],
									"operator" => "<="
								)
							)
						)
						We use this example when we call business-logic with conditions to be passed for the sql queries or in the Hibernate. For more info please see the Hibernate::getSQLConditions method.
						And as we can see in this example, the user uses the "value" key as a real object for other purposes. In this case the "value" will be an object. That's why we must check if: $.isPlainObject(item["value"])
						
						In this case the items will be something like:
						Array(
							...
							Array(
								[0] => Array(
									"items" => Array(
										[0] => Array(
											"value" => Array(
												[key] => value
												[key_type] => string
												[value] => $_POST["begin_date"]
												[value_type] => 
											),
											"operator" => Array(
												[key] => operator
												[key_type] => string
												[value] => >=
												[value_type] => string
											)
										),
										[1] => Array(
											"value" => Array(
												[key] => value
												[key_type] => string
												[value] => $_POST["end_date"]
												[value_type] => 
											),
											"operator" => Array(
												[key] => operator
												[key_type] => string
												[value] => <=
												[value_type] => string
											)
										)
									),
									[key] => da.begin_date
									[key_type] => string
								)
							)
							...
						)
					*/
					
					/*
					Another sample, more simple:
						Array(
							 [0] => Array(
								 [index] => Array(
									 [key] => index
									 [key_type] => string
									 [value] => 1
									 [value_type] => string
								)

								 [name] => Array(
									 [key] => name
									 [key_type] => string
									 [value] => $condo['special_block_1_name']
									 [value_type] => 
								)

								 [active] => Array(
									 [key] => active
									 [key_type] => string
									 [value] => $condo['special_block_1_active']
									 [value_type] => 
								)
							)
							 [1] => Array....
					*/
				
					var sub_items = [];
					for (var k in item)
						sub_items.push(item[k]);
					
					html += '<li>' + this.getItemsHtml(parent_name + "[" + idx + "]", key, key_type, sub_items) + '</li>';
				}
				else {
				/*
				Sample:
				Array
				     (
				         [key] => index
				         [key_type] => string
				         [value] => 1
				         [value_type] => string
				     )
				*/
					var value = item["value"];
					var value_type = item["value_type"];
					
					html += this.getItemHtml(parent_name + "[" + idx + "]", key, key_type, value, value_type);
				}
			}
			else { //This case should not happen, but just in case, we leave it here...
				html += this.getItemHtml(parent_name + "[" + idx + "]", "", "null", item, "");
			}
		}
		
		html += '</ul>';
		
		return html;
	},
	
	getItemHtml : function(parent_name, key, key_type, value, value_type) {
		key = typeof key != "undefined" && key != null ? "" + key : "";
		key_type = typeof key_type != "undefined" ? "" + key_type : "";
		value = typeof value != "undefined" && value != null ? "" + value : "";
		value_type = typeof value_type != "undefined" ? "" + value_type : "";
		
		var key_contains_end_line = key.indexOf("\n") != -1;
		var value_contains_end_line = value.indexOf("\n") != -1;
		var add_variable_icon = value_contains_end_line && typeof ProgrammingTaskUtil.on_programming_task_choose_created_variable_callback == "function";
		
		//prepare key for textarea, otherwise if key contains some html like textareas, the ui will look messy.
		if (key_contains_end_line) { 
			var escapeEl = document.createElement('textarea');
			escapeEl.textContent = key;
		   	key = escapeEl.innerHTML;
		}
		
		//prepare value for textarea, otherwise if value contains some html like textareas, the ui will look messy.
		if (value_contains_end_line) {
			var escapeEl = document.createElement('textarea');
			escapeEl.textContent = value;
		   	value = escapeEl.innerHTML;
		}
		
		return '<li class="item">' +
			(key_contains_end_line ? '<textarea class="key task_property_field" name="' + parent_name + '[key]" title="Item key" onKeyUp="ArrayTaskUtilObj.onFieldValueBlur(this)">' + key + '</textarea>' : '<input type="text" class="key task_property_field" name="' + parent_name + '[key]" value="' + key.replace(/"/g, "&quot;") + '" title="Item key" onKeyUp="ArrayTaskUtilObj.onFieldValueBlur(this)" />') +
			'<select class="key_type task_property_field" name="' + parent_name + '[key_type]" title="Item key type">' +
				'<option></option>' +
				'<option' + (key_type == "variable" ? " selected" : "") + '>variable</option>' +
				'<option' + (key_type == "string" ? " selected" : "") + '>string</option>' +
				'<option value="null"' + (key_type == "null" ? " selected" : "") + '>-- none --</option>' +
			'</select>' +
			(value_contains_end_line ? '<textarea class="value task_property_field" name="' + parent_name + '[value]" title="Item value" onKeyUp="ArrayTaskUtilObj.onFieldValueBlur(this)">' + value + '</textarea>' : '<input type="text" class="value task_property_field" name="' + parent_name + '[value]" value="' + value.replace(/"/g, "&quot;") + '" title="Item value" onKeyUp="ArrayTaskUtilObj.onFieldValueBlur(this)" />') +
			(add_variable_icon ? '' : '<span class="icon add_variable inline" onClick="ProgrammingTaskUtil.onProgrammingTaskChooseCreatedVariable(this)">Add Variable</span>') +
			'<select class="value_type task_property_field" name="' + parent_name + '[value_type]" title="Item value type">' +
				'<option></option>' +
				'<option' + (value_type == "variable" ? " selected" : "") + '>variable</option>' +
				'<option' + (value_type == "string" ? " selected" : "") + '>string</option>' +
			'</select>' +
			'<a class="icon remove" onClick="ArrayTaskUtilObj.removeItem(this)" title="Remove item">remove</a>' +
		'</li>';
	},
	
	addGroup : function(a) {
		var main_ul = $(a).parent().parent().children("ul").first();

		if (main_ul) {
			var idx = $(main_ul).attr('li_counter');
			if (!idx || idx <= 0) {
				idx = $(main_ul).children().length;
			}
			++idx;
			main_ul.attr('li_counter', idx);

			var parent_name = $(main_ul).attr('parent_name');
			var sub_parent_name = parent_name + "[" + idx + "]";
			
			var items = {0: {key_type: "null", value_type: "string"}};
			var html = '<li>' + this.getItemsHtml(sub_parent_name, "", "null", items, false) + '</li>';
			
			main_ul.append(html);
			
			ProgrammingTaskUtil.onProgrammingTaskPropertiesNewHtml( main_ul.children("li").last() );
		}
	},

	addItem : function(a) {
		var main_ul = $(a).parent().parent().children("ul").first();

		if (main_ul[0]) {
			var idx = main_ul.attr('li_counter');
			if (!idx || idx <= 0) {
				idx = main_ul.children().length;
			}
			++idx;
			main_ul.attr('li_counter', idx);

			var parent_name = main_ul.attr('parent_name');
			var sub_parent_name = parent_name + '[' + idx + ']';
			
			var html = this.getItemHtml(sub_parent_name, "", "null", "", "string");
			
			main_ul.append(html);
			
			ProgrammingTaskUtil.onProgrammingTaskPropertiesNewHtml( main_ul.children("li").last() );
			
			return main_ul.last();
		}
	},

	removeItem : function(a) {
		try {
			$(a).parent().remove();
		}
		catch(e) {
			alert('Error trying to remove item.');
		}
	},

	removeGroup : function(a) {
		try {
			var li = $(a).parent().parent().first();
			if (!li.hasClass('array_items')) {
				li.remove();
			}
		}
		catch(e) {
			alert('Error trying to remove group.');
		}
	},
	
	onFieldValueBlur : function(field) {
		field = $(field);
		var value = field.val();
		
		if (value) {
			if (field[0].nodeName.toLowerCase() == "input" && this.existsCharNonEscaped(value, '\\n')) {
				var textarea = $('<textarea></textarea>');
				var attrs = field[0].attributes;
				
				if (attrs)
					$.each(attrs, function(idx, attr) {
						textarea.attr(attr.name, attr.value);
					});
				
				textarea.val(value);
				
				field.replaceWith(textarea);
				
				ProgrammingTaskUtil.onProgrammingTaskPropertiesNewHtml(textarea);
			}
			else if (field[0].nodeName.toLowerCase() == "textarea" && value.indexOf("\n") == -1) {
				var input = $('<input />');
				var attrs = field[0].attributes;
				
				if (attrs)
					$.each(attrs, function(idx, attr) {
						input.attr(attr.name, attr.value);
					});
				
				input.val(value);
				
				field.replaceWith(input);
				
				ProgrammingTaskUtil.onProgrammingTaskPropertiesNewHtml(input);
			}
		}
		
		if (field.hasClass("key")) {
			var l = ("" + value).length;
			var key_type = field.parent().children(".key_type");
			
			if (l > 0) {
				var is_code = $.isNumeric(value) || value == "true" || value == "false" || value == "TRUE" || value == "FALSE" || value == "null" || value == "NULL";
				
				if (key_type.val() == "null" || key_type.val() == "")
					key_type.val(is_code ? "" : (("" + value).substr(0, 1) == "$" ? "variable" : "string") );
				else if (key_type.val() != "" && is_code)
					key_type.val("");
				else if (key_type.val() == "string" && !is_code && ("" + value).substr(0, 1) == "$")
					key_type.val("variable");
			}
			else if (l == 0 && key_type.val() != "null")
				key_type.val("null");
		}
	},
	
	//check if a string is escaped, this is, check if \n ("\\n") is escaped. It's not! But \\n ("\\\\n") is escaped!
	existsCharNonEscaped : function(text, char) {
		var pos = 0, escaped, i;
		
		do {
			escaped = false;
			pos = text.indexOf(char, pos);
			
			if (pos != -1) { //exists char
				for (i = pos - 1; i >= 0; i--) {
					if (text[i] == "\\")
						escaped = !escaped;
					else
						break;
				}
						
				if (!escaped)
					return true;
				
				pos += char.length;
			}
		}
		while(pos != -1);
			
		return false;
	},
	
	arrayToString : function(items) {
		var str = "";
		if (items) {
			if (items.hasOwnProperty('value')) {
				items = {0 : items};
			}
			
			for (var i in items) {
				if (i >= 0) {
					var item = items[i];
					
					var key = item["key"];
					var key_type = item["key_type"];
					var value = item["value"];
					var value_type = item["value_type"];
					var sub_items = item["items"];
					
					key = typeof key_type != "undefined" && key_type != "NULL" && key != null ? "" + key : "";
					key = key.trim();
					
					if (sub_items) {
						value = "array(...)";
					}
					else {
						value = value ? ("" + value + "").trim() : value;
						value = ProgrammingTaskUtil.getValueString(value, value_type);
					}
					
					str += (str ? ", " : "") + (key.length > 0 ? key + " => " : "") + value;
				}
			}
		}
		return "array(" + str + ")";
	},
};
