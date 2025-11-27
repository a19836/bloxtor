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

var ConditionsTaskUtilObj = {
	
	loadPropertyValues : function(group, group_name, is_root) {
		var html = '<ul group_name=\"' + group_name + '\">';

		if (group['join']) {
			html += this.getPropertyJoinHtml(group_name, group, is_root);
		}

		for (var key in group) {
			var props = group[key];

			if ((key == 'group' && props.hasOwnProperty('join')) || (key == 'item' && props.hasOwnProperty('first'))) {
				var temp = props;
				props = {'0' : temp};
			}

			for (var i in props) {
				var prop = props[i];
				var idx = parseInt(i) + 1;
				var prop_name = group_name + '[' + key + '][' + idx + ']';
				
				if (key == 'group') {
					html += '<li>' + this.loadPropertyValues(prop, prop_name) + '</li>';
				}
				else if (key == 'item') {
					html += this.getPropertyItemHtml(prop_name, prop);
				}
			}
		}

		html += '</ul>';

		return html;
	},

	getPropertyJoinHtml : function(group_name, group, is_root) {
		return '<li class="group">' +
			'<select class=\"task_property_field\" name=\"' + group_name + '[join]\">' +
				'<option' + (group['join'] == 'and' ? ' selected' : '') + '>and</option>' +
				'<option' + (group['join'] == 'or' ? ' selected' : '') + '>or</option>' +
			'</select>' +
			(is_root ? '' : '<a class="icon remove" onClick=\"ConditionsTaskUtilObj.removePropertyGroup(this)\" title="Remove condition group">remove</a>') + 
			'<a class="icon group_add" onClick=\"ConditionsTaskUtilObj.addPropertyGroup(this)\" title="Add new condition group">add group</a>' + 
			'<a class="icon item_add" onClick=\"ConditionsTaskUtilObj.addPropertyItem(this)\" title="Add new condition item">add item</a>' + 
		'</li>';
	},

	getPropertyItemHtml : function(prop_name, prop) {
		prop = prop ? prop : {};
		
		var first_value = prop['first'] ? prop['first']['value'] : null;
		var first_type = prop['first'] ? prop['first']['type'] : null;
		var second_value = prop['second'] ? prop['second']['value'] : null;
		var second_type = prop['second'] ? prop['second']['type'] : null;
		
		first_value = typeof first_value != "undefined" && first_value != null ? ("" + first_value).replace(/"/g, "&quot;") : "";
		first_type = typeof first_type != "undefined" && first_type != null ? first_type : "";
		second_value = typeof second_value != "undefined" && second_value != null ? ("" + second_value).replace(/"/g, "&quot;") : "";
		second_type = typeof second_type != "undefined" && second_type != null ? second_type : "";
		
		if (first_type == "variable" && first_value.substr(0, 1) == '$') {
			first_value = first_value.substr(1);
		}
		
		if (second_type == "variable" && second_value.substr(0, 1) == '$') {
			second_value = second_value.substr(1);
		}
		
		return '<li class="item">' +
			'<input class=\"task_property_field var\" type=\"text\" name=\"' + prop_name + '[first][value]\" value=\"' + first_value + '\" />' +
			'<span class="icon add_variable inline" onClick="ProgrammingTaskUtil.onProgrammingTaskChooseCreatedVariable(this)">Search</span>' +
			'<select class=\"task_property_field var_type\" name=\"' + prop_name + '[first][type]\">' +	
				'<option' + (first_type == "string" ? ' selected' : '') + '>string</option>' +	
				'<option' + (first_type == "variable" ? ' selected' : '') + '>variable</option>' +
				'<option value=""' + (first_value && first_type != "string" && first_type != "variable" ? ' selected' : '') + '>code</option>' +
			'</select>' +
			'<select class=\"task_property_field\" name=\"' + prop_name + '[operator]\">' +
				'<option' + (prop['operator'] == '==' ? ' selected' : '') + '>==</option>' +
				'<option' + (prop['operator'] == '!=' ? ' selected' : '') + '>!=</option>' +
				'<option' + (prop['operator'] == '>' || prop['operator'] == '&gt;' ? ' selected' : '') + '>&gt;</option>' +
				'<option' + (prop['operator'] == '>=' || prop['operator'] == '&ge;' ? ' selected' : '') + '>&gt;=</option>' +
				'<option' + (prop['operator'] == '<' || prop['operator'] == '&lt;' ? ' selected' : '') + '>&lt;</option>' +
				'<option' + (prop['operator'] == '<=' || prop['operator'] == '&le;' ? ' selected' : '') + '>&lt;=</option>' +
				'<option' + (prop['operator'] == '===' ? ' selected' : '') + '>===</option>' +
				'<option' + (prop['operator'] == '!==' ? ' selected' : '') + '>!==</option>' +
			'</select>' +
			'<input class=\"task_property_field var\" type=\"text\" name=\"' + prop_name + '[second][value]\" value=\"' + second_value + '\" />' +
			'<span class="icon add_variable inline" onClick="ProgrammingTaskUtil.onProgrammingTaskChooseCreatedVariable(this)">Search</span>' +
			'<select class=\"task_property_field var_type\" name=\"' + prop_name + '[second][type]\">' +
				'<option' + (second_type == "string" ? ' selected' : '') + '>string</option>' +	
				'<option' + (second_type == "variable" ? ' selected' : '') + '>variable</option>' +
				'<option value=""' + (second_value && second_type != "string" && second_type != "variable" ? ' selected' : '') + '>code</option>' +		
			'</select>' +
			'<a class="icon remove" onClick=\"ConditionsTaskUtilObj.removePropertyItem(this)\" title="Remove condition item">remove</a>' + 
		'</li>';
	},

	addPropertyGroup : function(a) {
		var main_ul = $(a).parent().parent();

		if (main_ul) {
			var group_name = main_ul.attr('group_name');

			var idx = main_ul.attr('li_counter');
			if (!idx || idx <= 0) {
				idx = main_ul.children().length;
			}
			++idx;
			main_ul.attr('li_counter', idx);

			var prop_name = group_name + '[group][' + idx + ']';
			var prop = {first : {value: '', type: 'string'}, operator : '', second : {value: '', type: 'string'}};

			var html = '<ul group_name=\"' + prop_name + '\">';
			html += this.getPropertyJoinHtml(prop_name, {});
			html += this.getPropertyItemHtml(prop_name + '[item][1]', prop);
			html += this.getPropertyItemHtml(prop_name + '[item][2]', prop);
			html += '</ul>';

			main_ul.append(html);
			
			ProgrammingTaskUtil.onProgrammingTaskPropertiesNewHtml( main_ul.children("ul").last() );
		}
	},

	addPropertyItem : function(a) {
		var main_ul = $(a).parent().parent();

		if (main_ul) {
			var group_name = main_ul.attr('group_name');

			var idx = main_ul.attr('li_counter');
			if (!idx || idx <= 0) {
				idx = main_ul.children().length;
			}
			++idx;
			main_ul.attr('li_counter', idx);

			var prop_name = group_name + '[item][' + idx + ']';
			var prop = {first : {value: '', type: 'string'}, operator : '', second : {value: '', type: 'string'}};

			var html = this.getPropertyItemHtml(prop_name, prop);

			main_ul.append(html);
			
			ProgrammingTaskUtil.onProgrammingTaskPropertiesNewHtml( main_ul.children("li").last() );
		}
	},

	removePropertyItem : function(a) {
		try {
			$(a).parent().remove();
		}
		catch(e) {
			alert('Error trying to remove item.');
		}
	},

	removePropertyGroup : function(a) {
		try {
			var main_div = $(a).parent().parent().parent()[0];
			if (!$(main_div).hasClass('conditions')) {
				$(a).parent().parent().remove();
			}
		}
		catch(e) {
			alert('Error trying to remove group.');
		}
	},
};
