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

if (typeof is_global_layer_common_file_already_included == "undefined") {
	var is_global_layer_common_file_already_included = 1;
	var workflow_global_variables = {};
	var normalize_task_layer_label = true;
	
	/* HANDLERS */
	
	function onLoadLayerTaskProperties(properties_html_elm, task_id, task_property_values) {
		//if no task_property_values hides fields
		$.each(properties_html_elm.find(".layer_rest_server_html, .layer_soap_server_html").children("input.active"), function(idx, input) {
			activateLayerRestServer(input);
		});
		
		if (task_property_values && task_property_values.hasOwnProperty("layer_brokers") && $.isPlainObject(task_property_values["layer_brokers"])) {
			var layer_brokers = task_property_values["layer_brokers"];
			
			//type is rest or soap
			for (var type in layer_brokers) {
				var type_values = layer_brokers[type];
				
				if ($.isPlainObject(type_values)) {
					var layer_type_server_html_elm = properties_html_elm.find(".layer_" + type + "_server_html");
					
					if (type_values.hasOwnProperty("active") && (parseInt(type_values["active"]) == 1 || ("" + type_values["active"]).toLowerCase() == "true")) {
						var input = layer_type_server_html_elm.children("input.active");
						input.attr("checked", "checked").prop("checked", true);
						activateLayerRestServer(input[0]);
					}
					
					for (var k in type_values) {
						var value = type_values[k];
						var prop_elm = layer_type_server_html_elm.children("." + k);
						
						if (k == "other_settings" || k == "global_variables") {
							if ($.isPlainObject(value) && value["vars_name"]) {
								var add_icon = prop_elm.find("> table > thead .table_attr_icons > .add")[0];
								var vars_name = value["vars_name"];
								var vars_value = value["vars_value"];
								
								if (!$.isArray(vars_name) && !$.isPlainObject(vars_name)) {
									vars_name = [ vars_name ];
									vars_value = [ vars_value ];
								}
								
								$.each (vars_name, function(idx, n) {
									var v = vars_value[idx];
									var item = k == "other_settings" ? addLayerTypeServerPropertiesOtherSetting(add_icon, type) : addLayerTypeServerPropertiesGlobalVariable(add_icon, type);
									
									item.children(".setting_name, .var_name").children("input").val(n);
									item.children(".setting_value, .var_value").children("input").val(v);
								});
							}
						}
						else if (k != "active") {
							prop_elm.children("input, select").val(value);
							
							if (k == "rest_auth_pass" && ("" + value).substr(0, 1) == '$')
								prop_elm.children(".toggle_password").trigger("click");
						}
					}
				}
			}
		}
	}
	
	function onSubmitLayerTaskProperties(selected_task_properties_elm, task_id, task_property_values) {
		checkIfLayerTaskPropertiesContainsGlobalVariables(selected_task_properties_elm);
		
		return true;
	}
	
	function onLoadLayerConnectionProperties(properties_html_elm, connection, connection_property_values) {
		var layer_connection_html = properties_html_elm.find(".layer_connection_html");
		
		//prepare connection_type
		var WF = myWFObj.getTaskFlowChart();
		var target_task_id = connection.targetId;
		var target_task_properties = WF.TaskFlow.tasks_properties[target_task_id];
		var layer_brokers = target_task_properties && target_task_properties.hasOwnProperty("layer_brokers") && $.isPlainObject(target_task_properties["layer_brokers"]) ? target_task_properties["layer_brokers"] : null;
		var available_connection_types = [""];
		
		if (layer_brokers) {
			for (var layer_broker_type in layer_brokers) {
				var layer_broker = layer_brokers[layer_broker_type];
				var is_active = $.isPlainObject(layer_broker) && layer_broker.hasOwnProperty("active") && (parseInt(layer_broker["active"]) == 1 || ("" + layer_broker["active"]).toLowerCase() == "true");
				
				if (is_active)
					available_connection_types.push(layer_broker_type);
			}
		}
		
		var select = layer_connection_html.find("> .connection_type > select");
		
		$.each(select.find("option"), function(idx, option) {
			option = $(option);
			
			if ($.inArray(option.val(), available_connection_types) === -1)
				option.remove();
		});
		
		if (connection_property_values.hasOwnProperty("connection_type") && connection_property_values["connection_type"] && $.inArray(connection_property_values["connection_type"], available_connection_types) === -1) {
			//reset the connection_type bc the current saved value is invalid. This happens when the connection_type doesn't corresponds to the created brokers servers in the target task properties, this is, in order words, it means that the current saved value doesn't corresponds to anything, so we must reset it to the Local broker.
			select.val(""); 
			
			//shows warning message
			var task_label = WF.TaskFlow.getTaskLabelByTaskId(target_task_id);
			select.parent().append('<span class="info">Note that the current saved connection type is "' + connection_property_values["connection_type"].toUpperCase() + '" but there is any ' + connection_property_values["connection_type"] + ' server defined in the "' + task_label + '" layer. If you continue with these settings the "LOCAL" connection type will be used instead!</span>');
		}
		
		onChangeLayerConnectionPropertiesType(select[0]);
		
		//prepare connection_settings
		if (connection_property_values.hasOwnProperty("connection_settings") && $.isPlainObject(connection_property_values["connection_settings"]) && connection_property_values["connection_settings"]["vars_name"]) {
			var add_icon = layer_connection_html.find("> .connection_settings > table > thead .table_attr_icons > .add")[0];
			var vars_name = connection_property_values["connection_settings"]["vars_name"];
			var vars_value = connection_property_values["connection_settings"]["vars_value"];
			
			if (!$.isArray(vars_name) && !$.isPlainObject(vars_name)) {
				vars_name = [ vars_name ];
				vars_value = [ vars_value ];
			}
			
			$.each (vars_name, function(idx, name) {
				var value = vars_value[idx];
				var item = addLayerConnectionPropertiesSetting(add_icon);
				
				item.children(".setting_name").children("input").val(name);
				item.children(".setting_value").children("input").val(value);
			});
		}
		
		//prepare connection_global_variables_name
		if (connection_property_values.hasOwnProperty("connection_global_variables_name") && connection_property_values["connection_global_variables_name"]) {
			var add_icon = layer_connection_html.find("> .connection_global_variables_name > table > thead .table_attr_icons > .add")[0];
			var gvs = connection_property_values["connection_global_variables_name"];
			
			if (!$.isArray(gvs) && !$.isPlainObject(gvs))
				gvs = [ gvs ];
			
			$.each (gvs, function(idx, name) {
				var item = addLayerConnectionGLobalVarName(add_icon);
				
				item.find(" > .var_name > input").val(name);
			});
		}
	}
	
	function onSubmitLayerConnectionProperties(properties_html_elm, connection, connection_property_values) {
		checkIfLayerTaskPropertiesContainsGlobalVariables(properties_html_elm);
		
		return true;
	}
	
	function onLayerConnectionDrop(conn) {
		if (!invalidateTaskConnectionIfItIsToItSelf(conn))
			return false;
		
		var WF = myWFObj.getTaskFlowChart();
		var source_task_id = conn.sourceId;
		var target_task_id = conn.targetId;
		var source_task = WF.TaskFlow.getTaskById(source_task_id);
		var target_task = WF.TaskFlow.getTaskById(target_task_id);
		var source_task_tag = source_task.attr("tag");
  		var target_task_tag = target_task.attr("tag");
  		
  		//console.debug(source_task_tag + "=>" + target_task_tag);
  		
  		var status = true;
  		
  		if (source_task_id == target_task_id)
  			status = false;
  		//If DBDriver
  		else if (source_task_tag == "dbdriver") 
  			status = false; //dbdriver cannot have connections with other layers
  		//If DB layer
  		else if (source_task_tag == "db") { 
		  	if (target_task_tag != "dbdriver") //db-layer can only be connected with dbdriver
	  			status = false;
	  	}
	  	//If DataAccess layer
  		else if (source_task_tag == "dataaccess") {
		  	if (!DataAccessLayerTaskPropertyObj.allow_multi_lower_level_layer_connections) { //only allow direct connections with 1 layer bellow
		  		if (target_task_tag != "db") //dataaccess can only be connected with db-layer. Only db-layer can connect with db driver
	  				status = false;
	  		}
	  		else {
		  		if (target_task_tag == "dbdriver" && !DataAccessLayerTaskPropertyObj.allow_dbdriver_connections) //only allow db-access to connect with db driver if allow_dbdriver_connections is true, otherwise only DB layer can connect with DBDrivers.
		  			status = false;
			  	else if (target_task_tag == "presentation" || target_task_tag == "businesslogic") //dataaccess cannot connect with presentation or businesslogic bc is from a level above.
			  		status = false;
		  	}
  		}
  		//If BusinessLogic layer
  		else if (source_task_tag == "businesslogic") {
		  	if (source_task_tag == target_task_tag && !BusinessLogicLayerTaskPropertyObj.allow_same_layer_connection) //layers cannot be connected to other layers with same type
  				status = false;
		  	else if (!BusinessLogicLayerTaskPropertyObj.allow_multi_lower_level_layer_connections) { //only allow direct connections with 1 layer bellow
		  		if (target_task_tag != "dataaccess")
		  			status = false;
		  	}
		  	else {
		  		if (target_task_tag == "dbdriver" && !BusinessLogicLayerTaskPropertyObj.allow_dbdriver_connections) //only allow businesslogic to connect with db driver if allow_dbdriver_connections is true, otherwise only DB layer can connect with DBDrivers.
		  			status = false;
			  	else if (target_task_tag == "presentation") //businesslogic cannot connect with presentation, bc is from a level above.
			  		status = false;
		  	}
	  	}
  		//If Presentation layer
  		else if (source_task_tag == "presentation") {
	  		if (!PresentationLayerTaskPropertyObj.allow_multi_lower_level_layer_connections) { //only allow direct connections with 1 layer bellow
		  		if (target_task_tag != "businesslogic")
		  			status = false;
		  	}
		  	else {
		  		if (target_task_tag == "dbdriver" && !PresentationLayerTaskPropertyObj.allow_dbdriver_connections) //only allow presentation to connect with db driver if allow_dbdriver_connections is true, otherwise only DB layer can connect with DBDrivers.
		  			status = false;
		  	}
	  	}
	  	
	  	//check if already a connection with the same tasks
	  	if (status) {
	  		var source_connections = WF.TaskFlow.getSourceConnections(source_task_id);
	  		var count = 0;
	  		//console.log(source_connections);
	  		
	  		if (source_connections)
	  			for (var i = 0; i < source_connections.length; i++)
	  				if (source_connections[i].targetId == target_task_id)
	  					count++;
	  		
	  		if (count > 1)
	  			status = false;
	  	}
  		
  		if (!status) {
  			var source_label = WF.TaskFlow.getTaskLabelByTaskId(source_task_id);
  			var target_label = WF.TaskFlow.getTaskLabelByTaskId(target_task_id);
  			
  			WF.StatusMessage.showError("ERROR: You cannot connect the task '" + source_label + "' to the task '" + target_label + "'.");
  		}
  		else //set connection color based if task is active or not
  			changeLayerTaskConnectionColorBasedInTaskActiveStatus(conn.connection);
  		
  		return status;
	}
	
	function onCompleteLayerTaskProperties(properties_html_elm, task_id, task_property_values, status) {
		var task = myWFObj.getTaskFlowChart().TaskFlow.getTaskById(task_id);
		task_property_values["active"] = task.hasClass("active") ? 1 : 0;
	}
	
	function onShowLayerTaskMenu(task_id, j_task, task_context_menu) {
		//prepare active menu
		var activate_menu_elm = task_context_menu.children(".activate_task");
		
		if (!activate_menu_elm[0]) {
			activate_menu_elm = $('<li class="activate_task"><a href="#" onClick="return setLayerTaskMenuActiveStatus();">Activate</a></li>');
			task_context_menu.children(".properties").after(activate_menu_elm); //inserts after the properties menu
		}
		
		if (j_task.hasClass("active"))
			activate_menu_elm.children("a").addClass("active").html("Inactivate");
		else
			activate_menu_elm.children("a").removeClass("active").html("Activate");
		
		//prepare default-layer menu
		task_context_menu.children(".start_task").children("a").attr("onClick", "return setLayerTaskMenuDefaultLayer();").html("Is Default Layer");
	}
	
	function onLayerTaskCreation(task_id) {
		var WF = myWFObj.getTaskFlowChart();
		var task = WF.TaskFlow.getTaskById(task_id);
		
		task.addClass("layer_task");
	}
	
	function onLayerTaskCloning(task_id, opts) {
		opts = opts ? opts : {do_not_show_task_properties : true};
		onTaskCloning(task_id, opts);
		
		var WF = myWFObj.getTaskFlowChart();
		var task = WF.TaskFlow.getTaskById(task_id);
		task.addClass("active");
		
		prepareLayerTaskActiveStatus(task);
	}
	
	/* TASK */
	
	function setLayerTaskMenuDefaultLayer() {
		var WF = myWFObj.getTaskFlowChart();
		var task_id = WF.ContextMenu.getContextMenuTaskId();
		WF.ContextMenu.setSelectedStartTask();
		
		var task = WF.TaskFlow.getTaskById(task_id);
		
		if (parseInt(task.attr("is_start_task")) > 0)
			task.attr("is_start_task", 1);
		
		WF.TaskFlow.getAllTasks().each(function(idx, task) {
			var task = $(task);
			
			if (task.attr("id") != task_id)
				task.removeClass(WF.TaskFlow.start_task_class_name).removeAttr("is_start_task");
		});
	}
	
	function setLayerTaskMenuActiveStatus() {
		var WF = myWFObj.getTaskFlowChart();
		var task_id = WF.ContextMenu.getContextMenuTaskId();
		var task = WF.TaskFlow.getTaskById(task_id);
		
		WF.ContextMenu.hideContextMenus();
		
		task.toggleClass("active");
		
		prepareLayerTaskActiveStatus(task);
	}
	
	function prepareLayerTaskActiveStatus(task) {
		var WF = myWFObj.getTaskFlowChart();
		var is_active = task.hasClass("active");
		var task_id = task.attr("id");
		var source_connections = WF.TaskFlow.getSourceConnections(task_id);
		var target_connections = WF.TaskFlow.getTargetConnections(task_id);
		
		//prepare properties
		if (!WF.TaskFlow.tasks_properties.hasOwnProperty(task_id))
			WF.TaskFlow.tasks_properties[task_id] = {};
		
		WF.TaskFlow.tasks_properties[task_id]["active"] = is_active ? 1 : 0;
		
		//prepare connections
		if (source_connections)
			for (var i = 0; i < source_connections.length; i++)
				changeLayerTaskConnectionColorBasedInTaskActiveStatus( source_connections[i] );
		
		if (target_connections)
			for (var i = 0; i < target_connections.length; i++)
				changeLayerTaskConnectionColorBasedInTaskActiveStatus( target_connections[i] );
	}
	
	function changeLayerTaskConnectionColorBasedInTaskActiveStatus(connection) {
		var WF = myWFObj.getTaskFlowChart();
		var is_conn_active = WF.TaskFlow.getTaskById(connection.sourceId).hasClass("active") && WF.TaskFlow.getTaskById(connection.targetId).hasClass("active");
		
		var parameters = connection.getParameters();
		var color = parameters.connection_exit_color;
		color = color ? color : connection.getPaintStyle().strokeStyle;
		color = ("" + color).length == "4" && color[0] == "#" ? color + color.substr(1) : color; //in case of #000 convert it to #000000
		color = ("" + color).length > 7 ? color.substr(0, 7) : color; //color could be already with the transparency #00000033.
		
		WF.TaskFlow.changeConnectionColor(connection, color + (is_conn_active ? "" : "33"));
	}
	
	/* TASK LABEL */
	
	function onCheckTaskLayerLabel(label_obj, task_id) {
		var status = isTaskLayerLabelValid(label_obj, task_id, true);
		
		if (!status && normalize_task_layer_label) {
			myWFObj.getTaskFlowChart().StatusMessage.removeLastShownMessage("error");
			
			label_obj.label = normalizeTaskLayerName(label_obj.label);
			
			status = isTaskLayerLabelValid(label_obj, task_id, true);
		}
		else if (normalize_task_layer_label)
			label_obj.label = normalizeTaskLayerName(label_obj.label);
		
		if (!status) {
			myWFObj.getTaskFlowChart().StatusMessage.removeLastShownMessage("error");
			
			var msg = label_obj.error;
			
			if (label_obj.from_prompt)
				alert(msg);
			else
				myWFObj.getTaskFlowChart().StatusMessage.showError(msg);
		}
		
		if (status)
			containsLayerGlobalVariables(label_obj.label);
		
		return status;
	}
	
	function isTaskLayerLabelValid(label_obj, task_id, ignore_msg) {
		var valid = false;
		
		if (label_obj.label && label_obj.label.length > 0) {
			var valid = isTaskLabelValid(label_obj, task_id, ignore_msg);
			
			if (valid)
				isTaskLayerNameAdvisable(label_obj.label);
		}
		
		if (!valid) {
			var already_exists_error = myWFObj.getTaskFlowChart().StatusMessage.getMessageHtmlObj().children(".error").last().text();
			var msg = (already_exists_error ? "\n" : "") + "Invalid label. Please choose a different label.\nYou cannot have repeated labels, only this characters are allowed: a-z, A-Z, 0-9 and '_'" + (normalize_task_layer_label ? "" : ", '-', '.', ' ', '$'") + " and you must have at least 1 letter.";
			myWFObj.getTaskFlowChart().StatusMessage.showError(msg);
			//console.log(msg);
			
			label_obj.error = msg;
		}
		
		return valid;
	}
	
	function isTaskLayerNameAdvisable(name) {
		if (name) {
			var normalized = ("" + name);
			
			if (typeof normalized.normalize == "function") //This doesn't work in IE11
				normalized = normalized.normalize("NFD");
				
			normalized = normalized.replace(/\./g, "_"); //replaces '.' by '_'
			normalized = normalized.replace(/[\u0300-\u036f]/g, ""); //replaces all characters with accents with non accented characters including 'ç' to 'c'
			
			if (name != normalized)
				myWFObj.getTaskFlowChart().StatusMessage.showError("Is NOT advisable to add names with accents and with non-standard characters. Please try to only use A-Z 0-9 and '_'.");
		}
	}
	
	function normalizeTaskLayerName(name) {
		//return name ? ("" + name).replace(/\n/g, "").replace(/[ \-\.]+/g, "_").match(/[\p{L}\w]+/giu).join("") : name; //\p{L} and /../u is to get parameters with accents and ç. Already includes the a-z. Cannot use this bc it does not work in IE.
		return name ? ("" + name).replace(/(^\s+|\s+$)/g, "").replace(/\n/g, "").replace(/[ \-\.]+/g, "_").match(/[\w\u00C0-\u00D6\u00D8-\u00F6\u00F8-\u024F\u1EBD\u1EBC]+/gi).join("") : name; //'\w' means all words with '_' and 'u' means with accents and ç too.
	}

	function containsLayerGlobalVariables(label) {
		//var vars = label.match(/\{?([\\]*)\$\{?([\p{L}\w]+)\}?/giu); //'\w' means all words with '_' and '/u' means with accents and ç too. Cannot use this bc it does not work in IE.
		var vars = label.match(/\{?([\\]*)\$\{?([\w\u00C0-\u00D6\u00D8-\u00F6\u00F8-\u024F\u1EBD\u1EBC]+)\}?/gi); //'\w' means all words with '_' and 'u' means with accents and ç too.
		
		if (vars && vars.length > 0) {
			var variables_not_defined = new Array();
			
			for (var i = 0; i < vars.length; i++) {
				var variable = vars[i].replace(/[{}]+/g, "");
				var slashes = variable.match(/^\\+\$/g) ? variable.substr(0, variable.indexOf("$")).match(/\\/g) : null;
				var is_escaped = slashes && slashes.length % 2 !== 0; //check if the number of slashes is odd
				
				//check if is not escape
				if (!is_escaped) {
					variable = variable.replace(/^\\*\$/, "");
					
					if (!workflow_global_variables.hasOwnProperty(variable)) 
						variables_not_defined.push("'$" + variable + "'");
				}
			}
		
			if (variables_not_defined.length > 0) {
				myWFObj.getTaskFlowChart().StatusMessage.showError("You are calling the following Gobal Variables: " + variables_not_defined.join(", ") + "; which are not yet defined as Global-Variables.\nPlease be sure that these Global Variables are defined correctly in the 'Global Variables' Menu bar.");
			}
			
			return true;
		}
		
		return false;
	}

	function checkIfLayerTaskPropertiesContainsGlobalVariables(properties_elm) {
		var main_value = "";
	
		var property_fields = $(properties_elm).find(".task_property_field, .connection_property_field");
		
		var length = property_fields.length;
		for (var i = 0; i < length; i++) {
			var field = property_fields[i];
			var j_field = $(field);
		
			main_value += " " + j_field.val();
		}
	
		containsLayerGlobalVariables(main_value);
	}
	
	/* TASK PROPERTIES */
	
	function activateLayerRestServer(elm) {
		elm = $(elm);
		var is_active = elm.is(":checked");
		var elms = elm.parent().closest(".layer_rest_server_html").children(".url, .http_auth, .user_pwd, .response_type, .rest_auth_user, .rest_auth_pass, .request_encryption_key, .response_encryption_key, .other_settings, .global_variables");
		
		if (is_active)
			elms.show();
		else 
			elms.hide();
		
		//update popup
		myWFObj.getTaskFlowChart().getMyFancyPopupObj().updatePopup();
	}
	
	function activateLayerSoapServer(elm) {
		elm = $(elm);
		var is_active = elm.is(":checked");
		var elms = elm.parent().closest(".layer_soap_server_html").children(".other_settings, .global_variables");
		
		if (is_active)
			elms.show();
		else 
			elms.hide();
	}
	
	function addLayerTypeServerPropertiesOtherSetting(elm, type) {
		var html = 
		'<tr>'
			+ '<td class="setting_name"><input class="task_property_field" type="text" name="layer_brokers[' + type + '][other_settings][vars_name][]" /></td>'
			+ '<td class="setting_value"><input class="task_property_field" type="text" name="layer_brokers[' + type + '][other_settings][vars_value][]" /></td>'
			+ '<td class="table_attr_icons"><a class="icon remove" onClick="$(this).parent().closest(\'tr\').remove()">Remove</a></td>'
		+ '</tr>';
		
		html = $(html);
		$(elm).parent().closest("table").children("tbody").append(html);
		
		return html;
	}
	
	function addLayerTypeServerPropertiesGlobalVariable(elm, type) {
		var html = 
		'<tr>'
			+ '<td class="var_name"><input class="task_property_field" type="text" name="layer_brokers[' + type + '][global_variables][vars_name][]" /></td>'
			+ '<td class="var_value"><input class="task_property_field" type="text" name="layer_brokers[' + type + '][global_variables][vars_value][]" /></td>'
			+ '<td class="table_attr_icons"><a class="icon remove" onClick="$(this).parent().closest(\'tr\').remove()">Remove</a></td>'
		+ '</tr>';
		
		html = $(html);
		$(elm).parent().closest("table").children("tbody").append(html);
		
		return html;
	}
	
	function toggleLayerRestConnectionPropertiesSettingPasswordField(elm) {
		var field = $(elm).parent().children("input");
		
		if (field.attr("type") == "password")
			field[0].type = "text";
		else
			field[0].type = "password";
	}
	
	/* CONNECTION PROPERTIES */
	
	function onChangeLayerConnectionPropertiesType(elm) {
		elm = $(elm);
		var type = elm.val();
		var connection_elms = elm.parent().closest(".layer_connection_html").children(".connection_response_type, .connection_settings, .connection_global_variables_name");
		
		if (type == "rest")
			connection_elms.show();
		else 
			connection_elms.hide();
	}
	
	function addLayerConnectionPropertiesSetting(elm) {
		var html = 
		'<tr>'
			+ '<td class="setting_name"><input class="connection_property_field" type="text" name="connection_settings[vars_name][]" /></td>'
			+ '<td class="setting_value"><input class="connection_property_field" type="text" name="connection_settings[vars_value][]" /></td>'
			+ '<td class="table_attr_icons"><a class="icon remove" onClick="$(this).parent().closest(\'tr\').remove()">Remove</a></td>'
		+ '</tr>';
		
		html = $(html);
		$(elm).parent().closest("table").children("tbody").append(html);
		
		return html;
	}
	
	function addLayerConnectionGLobalVarName(elm) {
		var html = 
		'<tr>'
			+ '<td class="var_name"><input class="connection_property_field" name="connection_global_variables_name[]" type="text" /></td>'
			+ '<td class="table_attr_icons"><a class="icon remove" onClick="$(this).parent().closest(\'tr\').remove()">Remove</a></td>'
		+ '</tr>';
		
		html = $(html);
		$(elm).parent().closest("table").children("tbody").append(html);
		
		return html;
	}
}
