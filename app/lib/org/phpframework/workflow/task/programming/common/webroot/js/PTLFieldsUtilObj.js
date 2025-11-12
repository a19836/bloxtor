/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

var PTLFieldsUtilObj = {
	
	default_input_data_var_name : "input",
	input_data_var_name : "input",
	idx_var_name : "i",
	external_vars : {},
	
	convertFormSettingsDataSettingsToPTL : function(form_html_elm) {
		var form_settings = FormFieldsUtilObj.getFormSettingsDataSettings(form_html_elm);
		//console.log(form_settings);
		
		/*
		 * The values from form_settings are coming raw, which means we need to parse them first and converted to correct php code, this is, a there are values that could be already encapsulated in quotes. This values should be decapsulated, othewise when we convert them to PTL, the conversion will duplicate the quotes and the PTL will be messy.
		 * This code was added 2019-10-06 because an input's previous_html containing a html encapsulated with double quotes, this is, something like is:
		 * 	"<input type=\"hidden\" name=\"ma_article[article_id]\" value=\"#[idx][article_id]#\" />"
		 * which would be converted to the following ptl:
		 * 	<ptl:echo "\"<input type=\\"hidden\\" name=\\"ma_article[article_id]\\" value=\\"" @\$input_734[\$idx_734][article_id] "\\" /&gt;\""/>
		 * which is wrong!!!
		 * So we need to fix this cases and remove all the encapsulated values which are strings and are not codes. The decapsulateEncapsulatedStrings function does this!
		 * Basically this function will convert the example above in:
		 * 	<input type="hidden" name="ma_article[article_id]" value="#[idx][article_id]#" />
		 * which would be converted to the following ptl:
		 * 	<ptl:echo "<input type=\"hidden\" name=\"ma_article[article_id]\" value=\"" @\$input_389[\$idx_389][article_id] "\" /&gt;"/>
		 * which is correct!!!
		 */
		form_settings = this.decapsulateEncapsulatedStrings(form_settings);
		//console.log(form_settings);
		
		var code = this.getHtmlForm(form_settings);
		//console.log(code);
		
		//console.log(this.external_vars);
		
		return {"code" : code, "external_vars" : this.external_vars};
	},
	
	getHtmlForm : function(form_settings) {
		var code = "";
		
		if (form_settings) {
			var with_form = this.isPropNotEmpty(form_settings, "with_form") && form_settings["with_form"] == "1";
			
			if (with_form) {
				code += '<form';
		
				var r = parseInt(Math.random() * 10000);
				var form_id = this.isPropNotEmpty(form_settings, "form_id") ? this.parseSettingsAttributeValue(form_settings["form_id"]) : "form_" + r;
				if (form_id) 
					code += ' id="' + form_id + '"';
			
				if (this.isPropNotEmpty(form_settings, "form_method"))
					code += ' method="' + this.parseSettingsAttributeValue(form_settings["form_method"]) + '"';
			
				code += ' class="';
				if (this.isPropNotEmpty(form_settings, "form_type")) {
					//this only works with boostrap. More info in: http://getbootstrap.com/css/#forms
					switch (form_settings["form_type"]) {
						case "horizontal": code += 'form-horizontal'; break;
						case "inline": code += 'form-inline'; break;
					}
				}
			
				if (this.isPropNotEmpty(form_settings, "form_class"))
					code += ' ' + this.parseSettingsAttributeValue(form_settings["form_class"]);
				code += '"';
			
				if (this.isPropNotEmpty(form_settings, "form_action"))
					code += ' action="' + this.parseSettingsAttributeValue(form_settings["form_action"]) + '"';
			
				//Note that the form_settings["form_on_submit"] must be before the MyJSLib.FormHandler.formCheck, bc in case exists an html editor, we need to populate first the field with the text from the editor.
				code += ' onSubmit="return ' + (form_settings["form_on_submit"] ? this.parseSettingsAttributeValue(form_settings["form_on_submit"]) + ' && ' : '') + '(typeof MyJSLib == \'undefined\' || MyJSLib.FormHandler.formCheck(this));"';
		
				code += ' enctype="multipart/form-data">' + "\n";
			}
			
			code += this.createElements(form_settings["form_containers"]);
			code = this.codeBeautify(code); //Note: DO NOT add the codeBeautify to the css or js, because they may contain some php code and it will break the code.
			
			if (with_form)
				code += "\n</form>\n"
				+ '<script>if (typeof MyJSLib != "undefined") { MyJSLib.FormHandler.initForm( $("#' + form_id + '")[0] ) }</script>'; //Note: we need to have the 'typeof MyJSLib != "undefined"', otherwise we will have a javascript error. This was well tested and it's correct! Please do NOT change it!
			
			if (this.isPropNotEmpty(form_settings, "form_js")) {
				//checks if js contains some php code like: "function foo() {return \"" . translateProjectText($EVC, "bla ble") . "\";}"
				var js = form_settings["form_js"];
				var is_php_code = js.charAt(0) == '"' && js.charAt(js.length - 1) == '"';
				if (is_php_code)
					js = "<script type=\\\"text/javascript\\\">\n" + js.substr(1, js.length - 2) + "\n</script>\n";
				else			
					js = "<script type=\"text/javascript\">\n" + js + "\n</script>\n";
			
				if (code.charAt(0) == '"' && code.charAt(code.length - 1) == '"')
					code = '"' + (is_php_code ? js : js.replace(/"/g, '\\"')) + code.substr(1, code.length - 2) + "\n\"";
				else if (is_php_code)
					code = '"' + js + code.replace(/"/g, '\\"') + "\n\"";
				else
					code = js + code;
			}
			
			if (this.isPropNotEmpty(form_settings, "form_css")) {
				//checks if css contains some php code like: ".class_xxx {content:\"" . translateProjectText($EVC, "bla ble") . "\";}"
				var css = form_settings["form_css"];
				var is_php_code = css.charAt(0) == '"' && css.charAt(css.length - 1) == '"';
				if (is_php_code)
					css = "<style type=\\\"text/css\\\">\n" + css.substr(1, css.length - 2) + "\n</style>\n";
				else			
					css = "<style type=\"text/css\">\n" + css + "\n</style>\n";
			
				if (code.charAt(0) == '"' && code.charAt(code.length - 1) == '"')
					code = '"' + (is_php_code ? css : css.replace(/"/g, '\\"')) + code.substr(1, code.length - 2) + "\n\"";
				else if (is_php_code)
					code = '"' + css + code.replace(/"/g, '\\"') + "\n\"";
				else
					code = css + code;
			}
		}
		
		return code;
	},
	
	codeBeautify : function(code) {
		if (!code || typeof MyHtmlBeautify == "undefined" || typeof MyHtmlBeautify.beautify != "function")
			return code;
		
		return MyHtmlBeautify.beautify(code, {'indent_size' : 1, 'indent_char' : "\t"});
	},
	
	isPropNotEmpty : function(arr, key) {
		return arr && arr.hasOwnProperty(key) && arr[key] != "" && arr[key] != null && typeof arr[key] != "undefined";
	},
	
	getParsedValueFromData : function(value, available_values) {
		available_values = this.parseAvailableValues(available_values);
		return this.parseSettingsValue(value, available_values);
	},
	
	getFieldHtml : function(field) {
		return this.createField(field);
	},
	
	getFieldLabelHtml : function(field) {
		return this.createFieldSettingsLabel(field);
	},
	
	getFieldInputHtml : function(field) {
		return this.createFieldSettingsInput(field);
	},
	
	createElements : function(elements) {
		var code = "";
		//console.log(elements);
		
		if (elements)
			for (var i in elements)
				code += this.createElement(elements[i]);
		
		return code;
	},
	
	createElement : function(element, with_idx) {
		var code = "";
		
		if (this.isPropNotEmpty(element, "ptl")) {
			if (this.isPropNotEmpty(element["ptl"], "input_data_var_name") && this.input_data_var_name != element["ptl"]["input_data_var_name"])
				code += '<ptl:var:' + element["ptl"]["input_data_var_name"] + ' @\\$' + this.input_data_var_name + ' />';
			
			code += element["ptl"]["code"];
			
			if (element["ptl"]["external_vars"] && $.isPlainObject(element["ptl"]["external_vars"]))
				for (var k in element["ptl"]["external_vars"])
					this.external_vars[ k.substr(0, 1) == "$" ? k.substr(1) : k ] = element["ptl"]["external_vars"][k];
		}
		else if (this.isPropNotEmpty(element, "container"))
			code += this.createContainer(element["container"], with_idx);
		else if (this.isPropNotEmpty(element, "field"))
			code += this.createField(element["field"]);
		else if (this.isPropNotEmpty(element, "pagination"))
			code += this.createPagination(element["pagination"]);
		else if (this.isPropNotEmpty(element, "table") || this.isPropNotEmpty(element, "tree")) {
			if (with_idx) {
				var old_input_data_var_name = this.input_data_var_name;
				this.input_data_var_name = old_input_data_var_name + "_" + parseInt(Math.random() * 1000);
				
				code += '<ptl:var:' + this.input_data_var_name + ' @\\$' + old_input_data_var_name + '[\\$' + this.idx_var_name + '] />';
			}
			
			code += element["table"] ? this.createTable(element["table"]) : this.createTree(element["tree"]);
			
			if (with_idx)
				this.input_data_var_name = old_input_data_var_name;
		}
		
		return code;
	},
	
	createContainer : function(container, with_idx) {
		var code = '<div';
		
		if (this.isPropNotEmpty(container, "class"))
			code += ' class="' + this.parseSettingsAttributeValue(container["class"]) + '"';
		
		code += '>';
		var href_exists = this.isPropNotEmpty(container, "href");
		
		if (href_exists) {
			code += '<a';
			
			var attrs = ["href", "title", "target"];
			code += this.getFieldAttributes(container, attrs);
			
			code += '>';
		}
		
		code += this.isPropNotEmpty(container, "previous_html") ? this.parseSettingsValue(container["previous_html"]) : "";
		code += this.isPropNotEmpty(container, "elements") ? this.createElements(container["elements"], with_idx) : "";
		code += this.isPropNotEmpty(container, "next_html") ? this.parseSettingsValue(container["next_html"]) : "";
		
		if (href_exists)
			code += '</a>';
		code += '</div>';
		
		return code;
	},
	
	createField : function(field) {
		var code = '';
		
		field["input"] = this.isPropNotEmpty(field, "input") ? field["input"] : {};
		field["input"]["type"] = this.isPropNotEmpty(field["input"], "type") ? ("" + field["input"]["type"]).toLowerCase() : "";
		
		var disable_field_group = this.isPropNotEmpty(field, "disable_field_group") && $.isNumeric(field["disable_field_group"]) ? parseInt(field["disable_field_group"]) : 0;
		
		if (!disable_field_group) {
			var c = this.isPropNotEmpty(field, "class") ? this.parseSettingsAttributeValue(field["class"]) : "";
			c += field["input"]["type"] == "hidden" ? " hidden" : "";
			
			code += '<div' + (c ? ' class="' + c + '"' : '') + '>';
		}
		
		for (var k in field)
			switch (k) {
				case "label":
					code += this.createFieldSettingsLabel(field);
					break;
				case "input":
					code += this.createFieldSettingsInput(field);
					break;
			}
		
		if (!disable_field_group)
			code += '</div>';
		
		return code;
	},
	
	createPagination : function(pagination) {
		var code = '';
		
		if (pagination) {
			var page_attr_name = pagination.hasOwnProperty("page_attr_name") && pagination["page_attr_name"] ? pagination["page_attr_name"] : 'pg';
			var max_num_of_shown_pages = pagination.hasOwnProperty("max_num_of_shown_pages") && pagination["max_num_of_shown_pages"] ? pagination["max_num_of_shown_pages"] : 5;
			var total_rows = pagination.hasOwnProperty("total_rows") ? pagination["total_rows"] : "";
			var rows_per_page = pagination.hasOwnProperty("rows_per_page") ? pagination["rows_per_page"] : "";
			var page_number = pagination.hasOwnProperty("page_number") ? pagination["page_number"] : "";
			var pagination_template = pagination.hasOwnProperty("pagination_template") ? pagination["pagination_template"] : "";
			
			code += '<ptl:var:PaginationLayout new PaginationLayout(' + total_rows + ', ' + rows_per_page + ', array("' + page_attr_name + '" =&gt; ' + page_number + '), "' + page_attr_name + '") />' +
			'<ptl:var:PaginationLayout-&gt;show_x_pages_at_once ' + max_num_of_shown_pages + ' />' +
			'<ptl:var:pagination_data \\$PaginationLayout-&gt;data />' +
			'<ptl:var:pagination_data["style"] "' + pagination_template + '" />' +
			'<ptl:echo \\$PaginationLayout-&gt;designWithStyle(1, \\$pagination_data) />';
		}
		
		return code;
	},
	
	createTable : function(table) {
		var code = '';
		
		if (table) {
			var rand = parseInt(Math.random() * 1000);
			var input_data_var_name = this.input_data_var_name + "_" + rand;
			
			if (this.isPropNotEmpty(table, "default_input_data") && table["default_input_data"])
				code += '<ptl:var:' + input_data_var_name + ' ' + this.parseNewInputData(table["default_input_data"]) + '/>';
			else
				code += '<ptl:var:' + input_data_var_name + ' @\\$' + this.input_data_var_name + '/>';
			
			var old_input_data_var_name = this.input_data_var_name;
			this.input_data_var_name = input_data_var_name;
		
			code += '<table class="' + this.parseSettingsAttributeValue(table["table_class"]) + '">';
		
			var elements = this.isPropNotEmpty(table, "elements") ? table["elements"] : null;
		
			if (elements && ($.isArray(elements) || $.isPlainObject(elements))) {
				var old_idx_var_name = this.idx_var_name;
				this.idx_var_name = 'idx_' + rand;
			
				var rows_class = this.isPropNotEmpty(table, "rows_class") && table["rows_class"] ? ' class="' + this.parseSettingsAttributeValue(table["rows_class"]) + '"' : '';
			
				code += '<thead>' +
					'<tr' + rows_class + '>';
				
				$.each(elements, function (idx, element) {
					var first_key = Object.keys(element)[0];//get first key of the element object
					var c = PTLFieldsUtilObj.isPropNotEmpty(element[first_key], "class") && element[first_key]["class"] ? ' class="' + PTLFieldsUtilObj.parseSettingsAttributeValue(element[first_key]["class"]) + '"' : '';
				
					code += '<th' + c + '>' + PTLFieldsUtilObj.createFieldSettingsLabel(element[first_key]) + '</th>';
				
					if (PTLFieldsUtilObj.isPropNotEmpty(elements[idx], "field") && PTLFieldsUtilObj.isPropNotEmpty(elements[idx]["field"], "label"))
					elements[idx]["field"]["label"] = null;//in case of the element be a FIELD element. This will be used bellow in the createElement function, if the element is a field. This avoids creating labels when call the createElement function.
				});
			
				code += '</tr>' +
				'</thead>' +
				'<tbody>' +
				'	<ptl:if is_array(@\\$' + input_data_var_name + ')>' +
				'		<ptl:foreach \\$' + input_data_var_name + ' ' + this.idx_var_name + ' item>' +
				'			<tr' + rows_class + '>';
				$.each(elements, function (idx, element) {
					var first_key = Object.keys(element)[0];//get first key of the element object
					var c = PTLFieldsUtilObj.isPropNotEmpty(element[first_key], "class") && element[first_key]["class"] ? ' class="' + PTLFieldsUtilObj.parseSettingsAttributeValue(element[first_key]["class"]) + '"' : '';
					element[first_key]["class"] = "";
					element[first_key]["disable_field_group"] = 1;
					
					code += '		<td' + c + '>' + PTLFieldsUtilObj.createElement(element, true)  + '</td>';
					//this.createElement(element, true): true here is very important so the inner trees or tables have the right input_data. Otherwise it won't be correct.
				});
				code += '		</tr>' +		
				'		</ptl:foreach>' +
				'	</ptl:if>' +
				'</tbody>';
			
				this.idx_var_name = old_idx_var_name;
			}
		
			this.input_data_var_name = old_input_data_var_name;
		
			code += '</table>';
		}
		return code;
	},
	
	createTree : function(tree) {
		var code = '';
		
		if (tree) {
			var html_tag = tree.hasOwnProperty("ordered") && tree["ordered"] ? 'ol' : 'ul';
			var rand = parseInt(Math.random() * 1000);
			var input_data_var_name = this.input_data_var_name + "_" + rand;
			
			if (this.isPropNotEmpty(tree, "default_input_data") && tree["default_input_data"])
				code += '<ptl:var:' + input_data_var_name + ' ' + this.parseNewInputData(tree["default_input_data"]) + '/>';
			else
				code += '<ptl:var:' + input_data_var_name + ' @\\$' + this.input_data_var_name + '/>';
			
			var old_input_data_var_name = this.input_data_var_name;
			this.input_data_var_name = input_data_var_name;
		
			code += '<' + html_tag;
			if (tree.hasOwnProperty("tree_class") && tree["tree_class"])
				code += ' class="' + this.parseSettingsAttributeValue(tree["tree_class"]) + '"';
			code += '>';
		
			var elements = this.isPropNotEmpty(tree, "elements") ? tree["elements"] : null;
		
			if (elements && ($.isArray(elements) || $.isPlainObject(elements))) {
				this.input_data_var_name = 'input_data';
				var old_idx_var_name = this.idx_var_name;
				this.idx_var_name = 'i';
				var didvn = this.default_input_data_var_name;
				
				var lis_class = tree.hasOwnProperty("lis_class") && tree["lis_class"] ? ' class="' + this.parseSettingsAttributeValue(tree["lis_class"]) + '"' : '';
			
				var fields = "";
				$.each(elements, function (idx, element) {
					fields += PTLFieldsUtilObj.createElement(element, true); //true here is very important so the inner trees or tables have the right input_data. Otherwise it won't be correct.
				});
				
				//must be outisde of the do-while, otherwise it will give an infinitive loop. '\w' means all words with '_' and '/u' means with accents and ç too.
				//var reg = /<ptl:function:([\p{L}\w]+) input_data>/giu; //Cannot use this bc it does not work in IE. Cannot use this bc it does not work in IE.
				//var reg = /<ptl:function:([\w\u00C0-\u00D6\u00D8-\u00F6\u00F8-\u024F\u1EBD\u1EBC]+) input>/gi; 
				var reg = new RegExp("<ptl:function:([\\w\\u00C0-\\u00D6\\u00D8-\\u00F6\\u00F8-\\u024F\\u1EBD\\u1EBC]+) " + didvn + ">", "gi");
				
				//Get inner functions and put them outside of main function
				do {
					var matches = reg.exec(fields);
					
					if (matches !== null && matches.length > 1 && matches[1]) {
						var tag = "</ptl:function:" + matches[1] + ">";
						var start = matches.index;
						var end = fields.indexOf(tag, start) + tag.length;
			
						code += fields.substr(start, end - start);
						fields = fields.substr(0, start) + fields.substr(end);
					}
				} 
				while (matches !== null);
				
				//Create Tree function
				code += '<ptl:function:createTree_' + rand + ' ' + didvn + '>' +
				'	<ptl:if !empty(\\$' + didvn + ') && (is_array(\\$' + didvn + ') || is_object(\\$' + didvn + '))>' +
				'		<ptl:foreach \\$' + didvn + ' i item>' +
				'			<li' + lis_class + '>' + fields;
				
				//Preparing input_data item's childs
				if (tree.hasOwnProperty("recursive") && tree["recursive"] && tree.hasOwnProperty("recursive_input_data") && tree["recursive_input_data"]) {
					this.input_data_var_name = 'item';
					code += '		<ptl:var:sub_items ' + this.parseNewInputData(tree["recursive_input_data"]) + '/>' +
					'			<ptl:createTree_' + rand + ' \\$sub_items />';
				}
			
				code += '		</li>' +
				'		</ptl:foreach>' +
				'	</ptl:if>' +
				'</ptl:function:createTree_' + rand + '>';
			
				//Call Tree function
				code += '<ptl:createTree_' + rand + ' @\\$' + input_data_var_name + ' />';
			
				this.idx_var_name = old_idx_var_name;
			}
		
			this.input_data_var_name = old_input_data_var_name;
		
			code += '</' + html_tag + '>';
		}
		return code;
	},
	
	/* CREATE FIELDS */
	createFieldSettingsLabel : function(field) {
		var label_field = field && field.hasOwnProperty("label") ? field["label"] : null;
		
		if (label_field) {
			var code = this.isPropNotEmpty(label_field, "previous_html") ? this.parseSettingsValue(label_field["previous_html"]) : "";
			
			if (this.isPropNotEmpty(label_field, "value")) {
				var label_type = this.isPropNotEmpty(label_field, "type") ? label_field["type"] : "span";
				
				code += '<' + label_type + ' class="form-label control-label ' + this.getSettingsClass(label_field) + '"';
			
				var attrs = ["title", "extra_attributes"];
				code += this.getFieldAttributes(label_field, attrs);
			
				code += '>' + this.parseSettingsValue(label_field["value"]) + '</' + label_type + '>';
			}
		
			code += this.isPropNotEmpty(label_field, "next_html") ? this.parseSettingsValue(label_field["next_html"]) : "";

			return code;
		}
		return "";
	},
	
	createFieldSettingsInput : function(field) {
		//Preparing available_values according with settings from form task
		var available_values = this.isPropNotEmpty(field["input"], "available_values") ? field["input"]["available_values"] : null;
		field["input"]["available_values"] = this.parseAvailableValues(available_values);
		
		var code = this.isPropNotEmpty(field["input"], "previous_html") ? this.parseSettingsValue(field["input"]["previous_html"]) : "";
		
		switch(field["input"]["type"]) {
			case "text":
			case "password":
			case "file":
			case "search":
			case "url":
			case "email":
			case "tel":
			case "number":
			case "range":
			case "date":
			case "month":
			case "week":
			case "time":
			case "datetime":
			case "datetime-local":
			case "color":
			case "hidden":
			case "button":
			case "submit":
			case "button_img": 
				code += this.createFieldInput(field); 
				break;
			case "checkbox":
			case "radio":
				code += this.createFieldRadioOrCheckbox(field);
				break;
			case "select": code += this.createFieldSelect(field); break;
			case "textarea": code += this.createFieldTextarea(field); break;
			case "link": code += this.createFieldLink(field); break;
			case "image": code += this.createFieldImage(field); break;
			case "label": 
			case "h1": 
			case "h2": 
			case "h3": 
			case "h4": 
			case "h5": 
				code += this.createFieldLabel(field); 
				break;
		}
		
		code += this.isPropNotEmpty(field["input"], "next_html") ? this.parseSettingsValue(field["input"]["next_html"]) : "";
		
		return code;
	},
	
	createFieldInput : function(field) {
		var input_field = field && field.hasOwnProperty("input") ? field["input"] : null;
		
		if (input_field) {
			var field_type = this.isPropNotEmpty(input_field, "type") ? (input_field["type"] == "button_img" ? "image" : input_field["type"]) : "";
			var available_values = this.isPropNotEmpty(input_field, "available_values") ? input_field["available_values"] : null;
			
			var code = '<input type="' + field_type + '" class="' + this.getSettingsClass(input_field) + '" value="' + (this.isPropNotEmpty(input_field, "value") ? this.parseSettingsAttributeValue(input_field["value"], available_values) : "") + '"';
		
			var attrs = ["extra_attributes", "name", "title", "place_holder", "confirmation"];
			
			if (field_type == "image")
				attrs.push("src");
			
			if (field_type != "hidden") {
				attrs.push("confirmation_message");
				attrs.push("allow_null");
				attrs.push("allow_javascript");
				attrs.push("validation_label");
				attrs.push("validation_message");
				attrs.push("validation_type");
				attrs.push("validation_regex");
				attrs.push("validation_func");
				attrs.push("min_length");
				attrs.push("max_length");
				attrs.push("min_value");
				attrs.push("max_value");
				attrs.push("min_words");
				attrs.push("max_words");
			}
			
			code += this.getFieldAttributes(input_field, attrs);
			
			code += ' />';
		
			return code;
		}
		
		return "";
	},
	
	createFieldRadioOrCheckbox : function(field) {
		var input_field = field && field.hasOwnProperty("input") ? field["input"] : null;
		
		if (input_field) {
			var c = this.getSettingsClass(input_field);
			
			var attrs = ["extra_attributes", "name", "title", "allow_null", "validation_label", "validation_message"];
			var extra_html = this.getFieldAttributes(input_field, attrs);
			
			if(!input_field.hasOwnProperty("options") || !input_field["options"])
				input_field["options"] = [{"value" : 1}];
		
			var code = "";
			
			if($.isArray(input_field["options"]) || $.isPlainObject(input_field["options"])) {
				var available_values = this.isPropNotEmpty(input_field, "available_values") ? input_field["available_values"] : null;
				var selected_value = this.isPropNotEmpty(input_field, "value") ? this.parseSettingsValue(input_field["value"], available_values) : "";
				var t = input_field["options"].length;
				
				$.each(input_field["options"], function(idx, opt) {
					var opt_label = null;
					if (PTLFieldsUtilObj.isPropNotEmpty(opt, "label"))
						opt_label = PTLFieldsUtilObj.parseSettingsValue(opt["label"]);
					
					code += '<div class="checkbox">';
					code += '	<label>';
					code += '		<input type="' + (PTLFieldsUtilObj.isPropNotEmpty(input_field, "type") ? input_field["type"] : "") + '"' + extra_html;
				
					
					var opt_extra_html = extra_html;
				
					if (t > 1 || opt_label)
						code += '<div class="' + c + '">' + (opt_label ? '<label>' + opt_label + '</label>' : '');
					else if (c)
						opt_extra_html = opt_extra_html.indexOf('class="') != -1 ? this.strReplace('class="', 'class="' + c + ' ', opt_extra_html) : ' class="' + c + '" ' + opt_extra_html;
				
					code += '<input type="' + (PTLFieldsUtilObj.isPropNotEmpty(input_field, "type") ? input_field["type"] : "") + '"' + opt_extra_html;
					
					var opt_value = "";
					if (opt.hasOwnProperty("value")) {
						opt_value = PTLFieldsUtilObj.parseSettingsValue(opt["value"]);
						code += ' value="' + PTLFieldsUtilObj.parseSettingsAttributeValue(opt["value"]) + '"';
					}
					else if (opt_label != null)
						opt_value = opt_label;
					
					if (PTLFieldsUtilObj.isPropNotEmpty(opt, "other_attributes"))
						code += ' ' + PTLFieldsUtilObj.parseSettingsValue(opt["other_attributes"]);
					
					//Add checked if selected
					if (opt_value.indexOf("<ptl:echo ") != -1)
						opt_value = opt_value.replace(/<ptl:echo /g, "<ptl:var:opt_value ");
					else {
						opt_value = PTLFieldsUtilObj.isCode(opt_value) || $.isNumeric(opt_value) ? opt_value : '"' + opt_value.replace(/\\/g, '\\\\').replace(/"/g, '\\"') + '"';
						opt_value = "<ptl:var:opt_value " + opt_value + ">";
					}
					
					if (selected_value.indexOf("<ptl:echo ") != -1)
						selected_value = selected_value.replace(/<ptl:echo /g, "<ptl:var:selected_value ");
					else {
						selected_value = PTLFieldsUtilObj.isCode(selected_value) || $.isNumeric(selected_value) ? selected_value : '"' + selected_value.replace(/\\/g, '\\\\').replace(/"/g, '\\"') + '"';
						selected_value = "<ptl:var:selected_value " + selected_value + ">";
					}
					
					code += opt_value + selected_value + '<ptl:if \\$opt_value == \\$selected_value><ptl:echo checked/></ptl:if>';
			
					code += '/>';
					
					if (t > 1 || opt_label)
						code += '</div>';
				});
			}
	
			return code;
		}
		
		return "";
	},
	
	createFieldSelect : function(field) {
		var input_field = field && field.hasOwnProperty("input") ? field["input"] : null;
		
		if (input_field) {
			var code = '<select class="' + this.getSettingsClass(input_field) + '"';
			
			var attrs = ["extra_attributes", "name", "title", "allow_null", "validation_label", "validation_message"];
			code += this.getFieldAttributes(input_field, attrs);
			
			code += '>';
		
			var available_values = this.isPropNotEmpty(input_field, "available_values") ? input_field["available_values"] : null;
			var selected_value = this.isPropNotEmpty(input_field, "value") ? this.parseSettingsValue(input_field["value"], available_values) : "";
		
			if(input_field.hasOwnProperty("options") && ($.isArray(input_field["options"]) || $.isPlainObject(input_field["options"])))
				$.each(input_field["options"], function(idx, opt) {
					var opt_label = null;
					if (PTLFieldsUtilObj.isPropNotEmpty(opt, "label"))
						opt_label = PTLFieldsUtilObj.parseSettingsValue(opt["label"]);
					
					code += '<option';
					
					var opt_value = "";
					if (opt.hasOwnProperty("value")) {
						opt_value = PTLFieldsUtilObj.parseSettingsValue(opt["value"]);
						code += ' value="' + PTLFieldsUtilObj.parseSettingsAttributeValue(opt["value"]) + '"';
					}
					else if (opt_label != null)
						opt_value = opt_label;
					
					
					if (PTLFieldsUtilObj.isPropNotEmpty(opt, "other_attributes"))
						code += ' ' + PTLFieldsUtilObj.parseSettingsValue(opt["other_attributes"]);
					
					//Add checked if selected
					if (opt_value.indexOf("<ptl:echo ") != -1)
						opt_value = opt_value.replace(/<ptl:echo /g, "<ptl:var:opt_value ");
					else {
						opt_value = PTLFieldsUtilObj.isCode(opt_value) || $.isNumeric(opt_value) ? opt_value : '"' + opt_value.replace(/\\/g, '\\\\').replace(/"/g, '\\"') + '"';
						opt_value = "<ptl:var:opt_value " + opt_value + ">";
					}
					
					if (selected_value.indexOf("<ptl:echo ") != -1)
						selected_value = selected_value.replace(/<ptl:echo /g, "<ptl:var:selected_value ");
					else {
						selected_value = PTLFieldsUtilObj.isCode(selected_value) || $.isNumeric(selected_value) ? selected_value : '"' + selected_value.replace(/\\/g, '\\\\').replace(/"/g, '\\"') + '"';
						selected_value = "<ptl:var:selected_value " + selected_value + ">";
					}
					
					code += opt_value + selected_value + '<ptl:if \\$opt_value == \\$selected_value><ptl:echo selected/></ptl:if>';
					
					code += '>' + (opt_label != null ? opt_label : "") + '</option>';
				});
		
			code += '</select>';
	
			return code;
		}
		return "";
	},
	
	createFieldTextarea : function(field) {
		var input_field = field && field.hasOwnProperty("input") ? field["input"] : null;
		
		if (input_field) {
			var code = '<textarea class="' + this.getSettingsClass(input_field) + '"';
			
			var attrs = ["extra_attributes", "name", "title", "allow_null", "allow_javascript", "validation_label", "validation_message", "validation_type", "validation_regex", "validation_func", "min_length", "max_length", "min_value", "max_value", "min_words", "max_words", "place_holder"];
			code += this.getFieldAttributes(input_field, attrs);
			
			var available_values = this.isPropNotEmpty(input_field, "available_values") ? input_field["available_values"] : null;
			var value = this.isPropNotEmpty(input_field, "value") ? this.parseSettingsValue(input_field["value"], available_values) : "";
			//avoids to close the </textarea> by mistake
			code += '>' + this.strReplace("</textarea", "&lt;/textarea", value) + '</textarea>';
			
			return code;
		}
		return "";
	},
	
	createFieldLabel : function(field) {
		var input_field = field && field.hasOwnProperty("input") ? field["input"] : null;
		
		if (input_field) {
			var html_tag = this.isPropNotEmpty(input_field, "type") && (input_field["type"] == "h1" || input_field["type"] == "h2" || input_field["type"] == "h3" || input_field["type"] == "h4" || input_field["type"] == "h5") ? input_field["type"] : "span";
		
			var code = '<' + html_tag + ' class="' + this.getSettingsClass(input_field) + '"';
		
			var attrs = ["title", "extra_attributes"];
			code += this.getFieldAttributes(input_field, attrs);
			
			var available_values = this.isPropNotEmpty(input_field, "available_values") ? input_field["available_values"] : null;
			code += '>' + (this.isPropNotEmpty(input_field, "value") ? this.parseSettingsValue(input_field["value"], available_values) : "") + '</' + html_tag + '>';
		
			return code;
		}
		return "";
	},
	
	createFieldLink : function(field) {
		var input_field = field && field.hasOwnProperty("input") ? field["input"] : null;
		
		if (input_field) {
			var code = '<a class="' + this.getSettingsClass(input_field) + '"';
		
			var attrs = ["href", "title", "target", "extra_attributes"];
			code += this.getFieldAttributes(input_field, attrs);
			
			var available_values = this.isPropNotEmpty(input_field, "available_values") ? input_field["available_values"] : null;
			code += '>' + (this.isPropNotEmpty(input_field, "value") ? this.parseSettingsValue(input_field["value"], available_values) : "") + '</a>';
		
			return code;
		}
		return "";
	},
	
	createFieldImage : function(field) {
		var input_field = field && field.hasOwnProperty("input") ? field["input"] : null;
		
		if (input_field) {
			var code = '<img class="' + this.getSettingsClass(input_field) + '"';
		
			if (this.isPropNotEmpty(input_field, "src"))
				code += ' src="' + this.parseSettingsAttributeValue(input_field["src"]) + '"';
			else if (this.isPropNotEmpty(input_field, "value")) {
				var available_values = this.isPropNotEmpty(input_field, "available_values") ? input_field["available_values"] : null;
				code += ' src="' + this.parseSettingsAttributeValue(input_field["value"], available_values) + '"';
			}
			
			var attrs = ["title", "extra_attributes"];
			code += this.getFieldAttributes(input_field, attrs);
			
			code += ' />';
			
			return code;
		}
		return "";
	},
	
	/* UTILS */
	getSettingsClass : function(field, attrs) {
		var c = "";
		
		if (attrs && ($.isArray(attrs) || $.isPlainObject(attrs)))
			$.each(attrs, function(idx, attr) {
				if (field.hasOwnProperty(attr) && field[attr])
					c += PTLFieldsUtilObj.parseSettingsAttributeValue(field[attr]) + " ";
			});
		
		c += field.hasOwnProperty("class") ? this.parseSettingsAttributeValue(field["class"]) : "";
		
		if(this.isPropNotEmpty(field, "extra_attributes") && ($.isArray(field["extra_attributes"]) || $.isPlainObject(field["extra_attributes"])))
			$.each(field["extra_attributes"], function(idx, f) {
				if (PTLFieldsUtilObj.isPropNotEmpty(f, "name") && PTLFieldsUtilObj.isPropNotEmpty(f, "value")) {
					var aux = PTLFieldsUtilObj.parseSettingsValue(f["name"]);
					
					if (("" + aux).toLowerCase() == "class") {
						c += (c ? ' ' : '') + PTLFieldsUtilObj.parseSettingsAttributeValue(f["value"]);
						field["extra_attributes"][idx] = null;
					}
				}
			});
		
		return c;
	},
	
	getFieldAttributes : function(field, attrs) {
		var code = "";
		
		if ($.isArray(attrs) || $.isPlainObject(attrs))
			$.each(attrs, function(idx, attr) {
				if (field.hasOwnProperty(attr)) {
					switch (attr) {
						case "extra_attributes":
							if (field["extra_attributes"]) {
								if($.isArray(field["extra_attributes"]) || $.isPlainObject(field["extra_attributes"]))
									$.each(field["extra_attributes"], function(idw, f) {
										if (PTLFieldsUtilObj.isPropNotEmpty(f, "name"))
											code += ' ' + PTLFieldsUtilObj.parseSettingsValue(f["name"]) + '="' + (f.hasOwnProperty("value") ? PTLFieldsUtilObj.parseSettingsAttributeValue(f["value"]) : "") + '"';
									});
								else if (typeof field["extra_attributes"] == "string") { //in case be a variable or a string with the attributes
									/*if (field["extra_attributes"].indexOf("$") === 0) {
										//No need anymore because it writes directly the variable into the html
										PTLFieldsUtilObj.external_vars[ field["extra_attributes"].substr(1) ] = field["extra_attributes"];
										code += " <ptl:echo \\" + field["extra_attributes"] + " />";
									}
									else*/
										code += " " + field["extra_attributes"];
								}
							}
							break;
						case "href":
							if (field["href"]) {
								var href = PTLFieldsUtilObj.parseSettingsAttributeValue(field["href"]);
								code += ' href="' + PTLFieldsUtilObj.strReplace(' ', '%20', href) + '"';
							}
							break;
						case "allow_null":
							//Do not add strlen(field["allow_null"]) in this if bc, if the allow_null exists but is empty, it means that empty values are not allowed!
							//if(strlen(field["allow_null"]) || is_bool(field["allow_null"]))
							code += ' data-allow-null="' + (field["allow_null"] ? field["allow_null"] : 0) + '"';
							break;
						case "allow_javascript":
							//only add the allow-javascript if is == 1, otherwise do not add it to the html for security reasons.
							if (field["allow_javascript"] && (field["allow_javascript"] == "1" || ("" + field["allow_javascript"]).toLowerCase() == "true"))
								code += ' data-allow-javascript="1"';
							
							break;
						case "validation_regex":
							code += field["validation_regex"] ? ' data-validation-regex="' + field["validation_regex"] + '"' : '';
							break;
						case "validation_func":
							code += field["validation_func"] ? ' data-validation-func="' + field["validation_func"] + '"' : '';
							break;
						case "validation_label":
						case "validation_message":
						case "validation_type":
						case "confirmation":
						case "confirmation_message":
							code += field[attr] ? ' data-' + attr.replace(/\_/g, "-") + '="' + PTLFieldsUtilObj.parseSettingsAttributeValue(field[attr]) + '"' : '';
							break;
						case "min_words":
						case "max_words":
							code += $.isNumeric(field[attr]) ? ' data-' + attr.replace(/\_/g, "-") + '="' + field[attr] + '"' : '';
							break;
						case "min_length":
							code += $.isNumeric(field["min_length"]) ? ' minLength="' + field["min_length"] + '"' : '';
							break;
						case "max_length":
							code += $.isNumeric(field["max_length"]) ? ' maxLength="' + field["max_length"] + '"' : '';
							break;
						case "min_value":
							code += $.isNumeric(field["min_value"]) ? ' min="' + field["min_value"] + '"' : '';
							break;
						case "max_value":
							code += $.isNumeric(field["max_value"]) ? ' max="' + field["max_value"] + '"' : '';
							break;
						case "place_holder":
							code += field["place_holder"] ? ' placeHolder="' + PTLFieldsUtilObj.parseSettingsAttributeValue(field["place_holder"]) + '"' : '';
							break;
						default:
							if (PTLFieldsUtilObj.isPropNotEmpty(field, attr))
								code += ' ' + attr + '="' + PTLFieldsUtilObj.parseSettingsAttributeValue(field[attr]) + '"';
					}
				}
			});
		
		return code;
	},
	
	parseAvailableValues : function(available_values) {
		//Preparing available_values according with settings from form task
		if (available_values) {
			if (($.isArray(available_values) || $.isPlainObject(available_values)) && available_values[0] && (available_values[0].hasOwnProperty("old_value") || available_values[0].hasOwnProperty("new_value"))) {
				var avs = {};
				$.each(available_values, function(idx, av) {
					var k = av.hasOwnProperty("old_value") ? av["old_value"] : "";
					avs[k] = av.hasOwnProperty("new_value") ? av["new_value"] : "";
				});
				available_values = avs;
			}
			else if (typeof available_values == "string") {
				var available_values = (available_values.indexOf("$") !== 0 ? "$" : "") + available_values;
				this.external_vars[ available_values.substr(1) ] = available_values;
			}
		}
		
		return available_values;
	},
	
	strReplace : function(to_search, replacement, string) {
		to_search = "" + to_search;
		replacement = "" + replacement;
		string = "" + string;
		
		var offset = 0;
		var length = string.length;
		var str = '';
		var reg = /<(php|ptl|\?):([^ ]+) ([^>]*)\/?>/ig; //must be outisde of the do-while, otherwise it will give an infinitive loop
		
		do {
			var matches = reg.exec(string);
			
			if (matches !== null && matches.length > 2) {
				var prev = string.substr(offset, matches.index - offset);
				var v = matches[3].substr(matches[3].length - 1) == "/" ? matches[3].substr(0, matches[3].length - 1) : matches[3];// substr(matches[3][0], 0, -1) is to remove the last char /, correspondent to />
				
				str += prev.replace(to_search, replacement) + '<' + matches[1] + ':' + matches[2] + ' ';
				
				if (matches[2] == "echo")
					str += 'str_replace(\'' + to_search.replace(/'/g, "\\'") + '\', \'' + replacement.replace(/'/g, "\\'") + '\', (' + v + '))';
				else
					str += v;
				
				str += (str.substr(str.length - 1) != " " ? " " : "") + '/>';
			
				offset = matches.index + matches[0].length;
			}
		}
		while (matches !== null && offset < length);
		
		str += string.substr(offset).replace(to_search, replacement);
		
		return str;
	},
	
	isCode : function(value) {
		var vl = ("" + value).toLowerCase();
		
		if ($.isNumeric(value) || vl == "true" || vl == "false")
			return false;
		
		var v = FormFieldsUtilObj.getFormSettingsAttributeValueDesconfigured(value);
	
		return v[1] == "" || v[1] == "variable";
	},
	
	//Loop for all form_settings and if there is any value which is string encapsulated with quotes in the beginning and at the end, convert it to a normal string by decapsulated, this is without quotes.
	decapsulateEncapsulatedStrings : function(form_settings) {
		if (form_settings)
			$.each(form_settings, function(key, value) {
				if ($.isPlainObject(value) || $.isArray(value)) 
					form_settings[key] = PTLFieldsUtilObj.decapsulateEncapsulatedStrings(value);
				else if ($.type(value) == "string" && PTLFieldsUtilObj.isCode(value)) 
					form_settings[key] = convertToNormalTextIfValueIsSurroundedWithQuotesAndIsNotAPHPCode(value);
			});
		
		return form_settings;
	},
	
	parseSettingsAttributeValue : function(value, available_values) {
		var value = this.parseSettingsValue(value, available_values);
		
		return this.strReplace('"', '&quot;', value);
	},
	
	parseSettingsValue : function(value, available_values) {
		orig_value = value;
		value = this.getParsedValue(value);
		
		if (value && value.length) {
			
			//TODO: only do this if the $value does not contain any ptl. Otherwise we will have ptl inside of ptl of will give a php error. Find a way to fix this error.
			
			if (available_values) {
				var rand = parseInt(Math.random() * 1000);
				var av_str = typeof available_values == "string" && available_values.substr(0, 1) == '$' ? "\\" + available_values : arrayToPHPCode(available_values);
				value = '<ptl:var:av_exists_' + rand + ' false/>' +
				'<ptl:var:var_aux_' + rand + ' ' + value.replace(/>/g, "&gt;") + '/>' +
				'<ptl:var:avs_' + rand + ' ' + av_str.replace(/>/g, "&gt;") + '/>' +
				'<ptl:if is_array(\\$avs_' + rand + ') />' +
				'	<ptl:foreach \\$avs_' + rand + ' k v>' +
				'		<ptl:if \\$k == \\$var_aux_' + rand + '>' +
				'			<ptl:echo \\$v/>' +
				'			<ptl:var:av_exists_' + rand + ' true/>' +
				'			<ptl:break/>' +
				'		</ptl:if>' +
				'	</ptl:foreach>' +
				'</ptl:if>' +
				'<ptl:if !\\$av_exists_' + rand + '>' +
				'	<ptl:echo \\$var_aux_' + rand + '/>' +
				'</ptl:if>';
				
				function arrayToPHPCode(v) {
					var code = '';
					if ($.isArray(v) || $.isPlainObject(v)) {
						code += "array(";
						var is_first = true;
						for (var idx in v) {
							code += (is_first ? "" : ", ") + ($.isNumeric(idx) ? idx : '"' + idx + '"') + " => " + arrayToPHPCode(v[idx]);
							is_first = false;
						}
						code += ")";
					}
					else
						code += $.isNumeric(v) ? v : '"' + v + '"';
					return code;
				}
			}
			else if (this.isCode(value)) {
				if (value.trim().substr(0, 2) == "<?")
					value = value.replace("<?php", "").replace("<?=", "").replace("<?", "").replace("?>", "").trim();
				
				//Checks if exists quotes in the beginning and end of the value. Addidionally checks if there are quotes in the middle and if they are escaped. If all these conditions are true, it means the value is a simple string writen as code, which means, we can simple strip the quotes and echo the value directly. Otherwise it means it is php code.
				if (orig_value == value && checkIfValueIsSurroundedWithQuotesAndIsNotAPHPCode(value))
					return convertToNormalTextIfValueIsSurroundedWithQuotesAndIsNotAPHPCode(value);
				
				if (value.indexOf("$") != -1) {
					//Gettings external vars from code. Gets the variables that are not escaped.
					//var reg = /\$([\p{L}\w]+)/gu; //must be outisde of the do-while, otherwise it will give an infinitive loop. '\w' means all words with '_' and '/u' means with accents and ç too. Cannot use this bc it does not work in IE.
					var reg = /\$([\w\u00C0-\u00D6\u00D8-\u00F6\u00F8-\u024F\u1EBD\u1EBC]+)/g; //must be outisde of the do-while, otherwise it will give an infinitive loop. '\w' means all words with '_' and 'u' means with accents and ç too.
					
					do {
						var matches = reg.exec(value);
						
						if (matches !== null && matches.length > 1 && matches[1] && (matches.index == 0 || value.charAt(matches.index - 1) != "\\")) { //checks if variable is not escaped
							this.external_vars[ matches[1] ] = "$" + matches[1];
							
							value = value.substr(0, matches.index) + "\\" + value.substr(matches.index); //escape variable in code
						}
					}
					while (matches !== null);
				}
				
				value = '<ptl:echo ' + value.replace(/>/g, "&gt;") + '/>';
			}
			else if (orig_value != value)
				value = '<ptl:echo ' + value.replace(/>/g, "&gt;") + '/>';
		}
		
		return value;
	},
	
	//any change here should be replicated in the HtmlFormHandler.php::parseNewInputData and MyWidgetResourceLib.js.HashTagHandler::parseNewInputData methods too
	parseNewInputData : function(value) {
		if ($.isArray(value) || $.isPlainObject(value))
			return value;
		
		if (value) {
			value = ("" + value).trim();
			
			//be sure that $value is something like: #foo#. "#bar##foo#" will not be allowed, bc it doesn't make sense here!
			if (value.substr(0, 1) == "#" && value.substr(value.length - 1, 1) == "#" && value.substr(1, value.length - 2).indexOf("#") == -1) {
				var results = this.getParsedValue(value, true);
				value = results[0];
			}
			
			return value;
		}
		
		return ""; //return empty ptl code in case there isn't value.
	},
	
	//any change here should be replicated in the HtmlFormHandler.php::getParsedValue and MyWidgetResourceLib.js.HashTagHandler::parseNewInputData methods too
	getParsedValue : function(value, result_in_array) {
		var results = new Array();
		
		if (value && typeof value == "string" && value.indexOf("#") != -1) {
			var offset = 0;
			var length = value.length;
			//var reg = /#([\p{L}\w"' \-\+\[\]\.\$]+)#/gu; //must be outside of the do-while, otherwise it will give an infinitive loop. '\w' means all words with '_' and '/u' means with accents and ç too. Cannot use this bc it does not work in IE.
			var reg = new RegExp("#([\\w\\u00C0-\\u00D6\\u00D8-\\u00F6\\u00F8-\\u024F\\u1EBD\\u1EBC\"' \\-\\+\\[\\]\\.\\$\\\\]+)#", "g"); //must be outside of the do-while, otherwise it will give an infinitive loop. '\w' means all words with '_' and 'u' means with accents and ç too. The '\' in the regex is bc we want to parse the cases of #[\$idx][name]#
			var matches_exists = false;
			
			do {
				var matches = reg.exec(value);
				
				if (matches !== null && matches.length > 1 && matches[1]) {
					var m = matches[1];
					var to_search = matches[0];
					var replacement = "";
					//console.log(m);
					
					matches_exists = true;
					
					//echo "m($value):$m<br>";
					if (m.indexOf("[") != -1 || m.indexOf("]") != -1) { //if value == #[0]name# or #[$idx - 1][name]#, returns $input[0]["name"] or $input[$idx - 1]["name"]
						var sub_matches = m.match(/([^\[\]]+)/g);
						m = "[" + sub_matches.join("][") + "]"; //fix the cases like: #articles[$idx - 1]name#
						m = m.replace(/\[idx\]/gi, '[\\$' + this.idx_var_name + "]").replace(/\[\\\$?idx\]/gi, '[\\$' + this.idx_var_name + "]").replace(/\\\$?idx[^a-z\_]/gi, '\\$' + this.idx_var_name);
						
						replacement = '\\$' + this.input_data_var_name + m;
					}
					else if (m == "$" + this.input_data_var_name) //#$input#, returns $input - Returns it-self.
						replacement = '\\$' + this.input_data_var_name;
					else if (m == "$" + this.default_input_data_var_name) //#$input#, returns $input - Returns it-self.
						replacement = '\\$' + this.default_input_data_var_name;
					else if (m == "$input" || m == "$input_data") { //this.default_input_data_var_name or this.input_data_var_name should have this already covered, otherwise something is wrong with the above code.
						alert("MAJOR ERRO no metodo getParsedValue dos ficheiros: HTMLFormHandler.php e PTLFieldsUtilObj.js. Falta aqui qualquer coisa. Verificar o código deste método.");
					}
					else if (m == "idx" || m == "$idx" || m == "\\$idx") //#idx#, returns $idx
						replacement = '\\$' + this.idx_var_name;//replace by the correspondent key
					else //if $value == #name#, returns $input["name"]
						replacement = '\\$' + this.input_data_var_name + '[' + m + ']';
					
					var aux = value.substr(offset, matches.index - offset);
					if (aux)
						results.push('"' + aux.replace(/\\/g, '\\\\').replace(/"/g, '\\"') + '"'); //must contain the escape for the slash, otherwise if exist any slash it will get lost on the way
					
					results.push(replacement); //in case the $replacemente be an array, the array is safetly save in the $results variable.
					
					offset = matches.index + to_search.length;
				}
			}
			while (matches && matches.length > 0 && offset < length);
			
			if (matches_exists)	{
				var aux = value.substr(offset);
				if (aux)
					results.push('"' + aux.replace(/\\/g, '\\\\').replace(/"/g, '\\"') + '"'); //must contain the escape for the slash, otherwise if exist any slash it will get lost on the way
			}
			else
				results.push(value);
		}
		else
			results.push(value);
		
		//$result_in_array is used to save the correct types of objects inside of the $results varialble, otherwise if any replacemente is an array (like Array X), when we concatenate with a string, this array (the Array X) will be converted to a string and lose his items...
		return result_in_array ? results : results.join(" ");
	},
	
	/*
	parseSettingsValue : function(value, available_values) {
		value = "" + value;
		var orig_value = value;
		
		if (value && value.indexOf("#") !== -1) {
			var v = "";
			var offset = 0;
			var length = value.length;
			var reg = /#([^#]+)#/g; //must be outisde of the do-while, otherwise it will give an infinitive loop
			
			do {
				var matches = reg.exec(value);
				
				if (matches !== null && matches.length > 1 && matches[1]) {
					var m = matches[1];
					var replacement = "";
					//console.log(m);
					
					//echo "m($value):$m<br>";
					if (m.indexOf("[") != -1) { //#[0]name# or #[$idx - 1][name]# or #articles[$idx - 1][name]#
						var sub_matches = m.match(/([^\[\]]+)/g);
						m = "[" + sub_matches.join("][") + "]"; //fix the cases like: #articles[$idx - 1]name#
						
						replacement = '\\$' + this.input_data_var_name + m.replace(/\[\\\$?idx\]/gi, '[\\$' + this.idx_var_name + "]").replace(/\\\$?idx[^a-z\_]/gi, '\\$' + this.idx_var_name);
					}
					else if (m == "$" + this.input_data_var_name) //#$input# if this.input_data_var_name == "input"
						replacement = '\\$' + this.input_data_var_name;
					else if (m == "$idx") //#$idx#
						replacement = '\\$' + this.input_data_var_name + '[\\$' + this.idx_var_name + ']';
					else if (m == "idx")
						replacement = '\\$' + this.idx_var_name;//replace by the correspondent key
					else //#name#
						replacement = '\\$' + this.input_data_var_name + '[' + m + ']';
					
					var aux = value.substr(offset, matches.index - offset);
					v += (v ? " " : "") + (aux ? '"' + aux.replace(/"/g, '\\"') + '" ' : "") + replacement;
					
					offset = matches.index + matches[0].length;
				}
			}
			while (matches !== null && offset < length);
			
			var aux = value.substr(offset);
			v += (v ? " " : "") + (aux ? '"' + aux.replace(/"/g, '\\"') + '"' : "");
			
			value = v;
		}
		
		if (value && value.length) {
			if (available_values) {
				var rand = parseInt(Math.random() * 1000);
				var av_str = typeof available_values == "string" && available_values.substr(0, 1) == '$' ? "\\" + available_values : arrayToPHPCode(available_values);
				value = '<ptl:var:av_exists_' + rand + ' false/>' +
				'<ptl:var:var_aux_' + rand + ' ' + value.replace(/>/g, "&gt;") + '/>' +
				'<ptl:var:avs_' + rand + ' ' + av_str.replace(/>/g, "&gt;") + '/>' +
				'<ptl:if is_array(\\$avs_' + rand + ') />' +
				'	<ptl:foreach \\$avs_' + rand + ' k v>' +
				'		<ptl:if \\$k == \\$var_aux_' + rand + '>' +
				'			<ptl:echo \\$v/>' +
				'			<ptl:var:av_exists_' + rand + ' true/>' +
				'			<ptl:break/>' +
				'		</ptl:if>' +
				'	</ptl:foreach>' +
				'</ptl:if>' +
				'<ptl:if !\\$av_exists_' + rand + '>' +
				'	<ptl:echo \\$var_aux_' + rand + '/>' +
				'</ptl:if>';
				
				function arrayToPHPCode(v) {
					var code = '';
					if ($.isArray(v) || $.isPlainObject(v)) {
						code += "array(";
						var is_first = true;
						for (var idx in v) {
							code += (is_first ? "" : ", ") + ($.isNumeric(idx) ? idx : '"' + idx + '"') + " => " + arrayToPHPCode(v[idx]);
							is_first = false;
						}
						code += ")";
					}
					else
						code += $.isNumeric(v) ? v : '"' + v + '"';
					return code;
				}
			}
			else if (this.isCode(value)) {
				if (value.trim().substr(0, 2) == "<?")
					value = value.replace("<?php", "").replace("<?=", "").replace("<?", "").replace("?>", "").trim();
				
				if (orig_value == value &&                                                                                                                                                                                                                                                     checkIfValueIsSurroundedWithQuotesAndIsNotAPHPCode(value))
					return convertToNormalTextIfValueIsSurroundedWithQuotesAndIsNotAPHPCode(value);
				
				if (value.indexOf("$") != -1) {
					//Gettings external vars from code. Gets the variables that are not escaped.
					//var reg = /\$([\p{L}\w]+)/gu; //must be outisde of the do-while, otherwise it will give an infinitive loop. '\w' means all words with '_' and '/u' means with accents and ç too. Cannot use this bc it does not work in IE.
					var reg = /\$([\w\u00C0-\u00D6\u00D8-\u00F6\u00F8-\u024F\u1EBD\u1EBC]+)/g; //must be outisde of the do-while, otherwise it will give an infinitive loop. '\w' means all words with '_' and 'u' means with accents and ç too.
					
					do {
						var matches = reg.exec(value);
						
						if (matches !== null && matches.length > 1 && matches[1] && (matches.index == 0 || value.charAt(matches.index - 1) != "\\")) { //checks if variable is not escaped
							this.external_vars[ matches[1] ] = "$" + matches[1];
							
							value = value.substr(0, matches.index) + "\\" + value.substr(matches.index); //escape variable in code
						}
					}
					while (matches !== null);
				}
				
				value = '<ptl:echo ' + value.replace(/>/g, "&gt;") + '/>';
				
			}
			else if (orig_value != value)
				value = '<ptl:echo ' + value.replace(/>/g, "&gt;") + '/>';
		}
		
		return value;
	},
	
	parseNewInputData : function(value) {
		if ($.isArray(value) || $.isPlainObject(value))
			return value;
		
		if (value) {
			value = ("" + value).trim();
			
			if (value.substr(0, 1) == "#" && value.substr(value.length - 1, 1) == "#") {
				var m = value.substr(1, value.length - 2);
				
				if (m.indexOf("[") != -1) //if value == #[0]name# or #[$idx - 1][name]#, returns $input[0]["name"] or $input[$idx - 1]["name"]
					return '\\$' + this.input_data_var_name + m.replace(/\[\\\$?idx\]/gi, '[\\$' + this.idx_var_name + "]").replace(/\\\$?idx[^a-z\_]/gi, '\\$' + this.idx_var_name);
				else if (m == "\$idx") //#$idx#, returns $input[$idx]
					return '\\$' + this.input_data_var_name + '[\\$' + this.idx_var_name + ']'; //Not sure about this: $input[' + $idx + ']
				else //if $value == #name#, returns $input["name"]
					return '\\$' + this.input_data_var_name + '[' + m + ']';
				
				return null;//returns null in case #...# doesn't exists inside of $input or if there isn't #...#
			}
			
			return value;//return string value
		}
		
		return "";//return empty ptl code in case there isn't value.
	},*/
};
