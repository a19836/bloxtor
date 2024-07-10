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

if (typeof is_global_presentation_common_file_already_included == "undefined") {
	var is_global_presentation_common_file_already_included = 1;
	
	var PresentationTaskUtil = {
		db_drivers : null,
		selected_db_driver : null,
		get_broker_db_data_url : null,
		on_choose_file_callback : null,
		
		auto_increment_db_attributes_types : null,
		
		db_drivers_tables : null,
		created_files : null,
		
		available_user_types : null,
		available_activities : null,
		users_management_admin_panel_url : null,
		
		html_db_types : {
			checkbox : ['smallint', 'tinyint', 'boolean'],
			number : ['bit', 'bigint', 'int', 'decimal', 'money', 'coordinate', 'double', 'float', 'smallint', 'tinyint'],
			date : ['date'],
			datetime : ['timestamp', 'datetime'],
			time : ['time'],
			textarea : ['mediumtext', 'text', 'longtext', 'blob', 'longblob'],
		},
		
		ui_table_attribute_properties_popup: new MyFancyPopupClass(),
		
		/* TASK HANDLERS */
		
		onLoadTaskProperties : function(task_html_element, task_id, task_property_values) {
			var WF = myWFObj.getTaskFlowChart();
			
			//prepare tabs
			task_html_element.tabs();
			task_html_element.children("ul").first().find("li a").click(function() {
				//update popup size everytime that we click in the tabs bc the content of the UI tab is dynamic
				setTimeout(function() {
					WF.getMyFancyPopupObj().updatePopup();
				}, 100);
			});
			
			//prepare interface type
			var j_task = WF.TaskFlow.getTaskById(task_id);
			var task_parent = WF.TaskFlow.getTaskParentTasks(j_task);
			var task_parent_tag = $(task_parent[0]).attr("tag");
			var is_task_inner_child = task_parent[0] && task_parent_tag != "page"; //if is a child task inside of another task that is not a page
			var inner_task_settings = task_html_element.find(".inner_task_settings");
			
			if (is_task_inner_child) {
				if (!inner_task_settings.hasClass("ui-tabs"))
					inner_task_settings.tabs();
				
				inner_task_settings.show();
			}
			else
				inner_task_settings.hide();
			
			//prepare choose_db_table
			var choose_db_table_elm = task_html_element.find(".choose_db_table");
			var db_driver_select = choose_db_table_elm.find(".db_driver > select");
			
			if (choose_db_table_elm[0]) {
				//prepare db drivers
				var html = '<option></option>';
				if (PresentationTaskUtil.db_drivers)
					$.each(PresentationTaskUtil.db_drivers, function(idx, db_driver_name) {
						html += '<option>' + db_driver_name + '</option>';
					});
				
				db_driver_select.html(html);
				db_driver_select.val(PresentationTaskUtil.selected_db_driver);
				PresentationTaskUtil.updateDBTables( db_driver_select[0] );
				
				//prepare ui_table_attribute_properties_popup
				var ui_table_attribute_properties_popup_elm = task_html_element.find(".ui_table_attribute_properties_popup");
				var ui_table_attribute_db_driver_select = ui_table_attribute_properties_popup_elm.find(".db_driver > select");
				ui_table_attribute_db_driver_select.html(html);
				ui_table_attribute_db_driver_select.val(PresentationTaskUtil.selected_db_driver);
				PresentationTaskUtil.updateDBTables( ui_table_attribute_db_driver_select[0] );
				
				//prepare db_table advanced settings
				var is_listing_task = j_task[0] ? j_task.attr("tag") == "listing" : task_html_element.hasClass("listing_task_html");
				if (is_task_inner_child || !is_listing_task)
					choose_db_table_elm.find(".db_table_parent, .db_table_parent_alias").remove();
			}
			
			//prepare users_management_admin_panel
			if (PresentationTaskUtil.users_management_admin_panel_url)
				task_html_element.find(" > .permissions > .users_management_admin_panel").show();
			else
				task_html_element.find(" > .permissions > .users_management_admin_panel").hide();
			
			//prepare task_property_values
			if (task_property_values) {
				//prepare choose_db_table
				if (task_property_values["choose_db_table"]) {
					var db_driver = task_property_values["choose_db_table"]["db_driver"];
					var db_type = task_property_values["choose_db_table"]["db_type"];
					var db_table = task_property_values["choose_db_table"]["db_table"];
					var db_table_alias = task_property_values["choose_db_table"]["db_table_alias"];
					var db_table_parent = task_property_values["choose_db_table"]["db_table_parent"];
					var db_table_parent_alias = task_property_values["choose_db_table"]["db_table_parent_alias"];
					
					db_driver_select.val(db_driver);
					choose_db_table_elm.find(".db_type > select").val(db_type);
					PresentationTaskUtil.updateDBTables( db_driver_select[0] );
					
					var db_table_select = choose_db_table_elm.find(".db_table > select");
					db_table_select.val(db_table).attr("orig_db_table", db_table);
					
					choose_db_table_elm.find(".db_table_alias > input").val(db_table_alias);
					choose_db_table_elm.find(".db_table_parent > select").val(db_table_parent);
					choose_db_table_elm.find(".db_table_parent_alias > input").val(db_table_parent_alias);
					
					PresentationTaskUtil.prepareAddUITableAttributesOptions(task_html_element);
					
					//prepare table conditions
					PresentationTaskUtil.loadDBTableConditions(choose_db_table_elm, task_property_values["choose_db_table"]["db_table_conditions"]);
				}
				else
					PresentationTaskUtil.updateDBTables( db_driver_select[0] );
				
				//prepare actions
				if (task_property_values["action"]) {
					var actions_elm = task_html_element.find(".actions");
					var available_advanced_settings = ["confirmation_message", "ok_msg_message", "ok_msg_redirect_url", "error_msg_message", "error_msg_redirect_url"];
					
					$.each(task_property_values["action"], function(action_type, action_value) {
						var exists = false;
						
						for (var idx in available_advanced_settings) {
							var aas = available_advanced_settings[idx];
							var pos = action_type.indexOf(aas);
							
							if (pos != -1) {
								var t = action_type.substr(0, pos - 1); //-1 bc of the last underscore
								var input = actions_elm.find("." + t + " ." + aas);
								
								if (!input.is("input"))
									input = input.find("input");
								
								input.val(action_value);
								exists = true;
								break;
							}
						}
						
						if (!exists) {
							var input = actions_elm.find("." + action_type + " .action_active");
							
							if (action_value)
								input.attr("checked", "checked").prop("checked", true);
							else
								input.removeAttr("checked").prop("checked", false);
						}
					});
				}
				
				//prepare links
				PresentationTaskUtil.loadLinks(task_html_element, task_property_values["links"]);
				
				//prepare attributes
				PresentationTaskUtil.loadTableUIAttributes(task_html_element, task_property_values["attributes"]);
				
				//prepare users perms
				PresentationTaskUtil.loadUsersPerms(task_html_element, task_property_values["users_perms"]);
			}
			else
				PresentationTaskUtil.updateDBTables( db_driver_select[0] );
		},
		
		onSubmitTaskProperties : function(properties_html_elm, task_id, task_property_values) {
			//save created_files to another var, otherwise it will get lost in the middle of the saving process
			PresentationTaskUtil.created_files = task_property_values["created_files"];
			
			return true;
		},
		
		onCompleteTaskProperties : function(properties_html_elm, task_id, task_property_values, status) {
			//add created_files var to the task_property_values
			if (PresentationTaskUtil.created_files) {
				task_property_values["created_files"] = PresentationTaskUtil.created_files;
				PresentationTaskUtil.created_files = null;
			}
		},
		
		onTaskCreation : function(task_id) {
			var WF = myWFObj.getTaskFlowChart();
			var j_task = WF.TaskFlow.getTaskById(task_id);
			
			var droppable = $('<div class="' + WF.TaskFlow.task_droppable_class_name + '"></div>');
			j_task.append(droppable);
			
			WF.ContextMenu.prepareTaskDroppables(j_task, {accept: ".task_form, .task_listing, .task_view"});
			
			var label_elm = WF.TaskFlow.getTaskLabelElement(j_task);
			label_elm.closest("." + WF.TaskFlow.task_label_class_name).attr("title", label_elm.text());
			
			/*DEPRECATED Do not add the 'WF.TaskFlow.resizeTaskParentTask(droppable)' bc since we are loading the width and height of the task, we don't need this anymore, and there could be a situation where the user really wants a specific size and if we call resizeTaskParentTask the size will be overwrited.
			//because the inner tasks will only get appended to this task, after this function get called, we need to set a timeout to resize the task
			setTimeout(function() {
				WF.TaskFlow.resizeTaskParentTask(droppable, true);
			}, 1000);
			*/
		},
		
		onTaskCloning : function(task_id) {
			var WF = myWFObj.getTaskFlowChart();
			var j_task = WF.TaskFlow.getTaskById(task_id);
			var parent_task = WF.TaskFlow.getTaskParentTasks(j_task);
			
			if (parent_task[0] && parent_task.is(".task_page, .task_form, .task_listing, .task_view")) {
				onTaskCloning(task_id);
				
				WF.TaskFlow.resizeTaskParentTask(j_task, true); //resize parent task with the new width and height according with this inner task
				
				return true;
			}
			
			//delete task
			j_task.hide();
			WF.StatusMessage.showError("This task cannot be dropped here!");
			
			setTimeout(function() {
				WF.TaskFlow.deleteTask(task_id, {confirm: false});
			}, 3000); //wait until TaskFlow finish to create the task successfully, otherwise it will break or the browser becomes very slow or freezes, bc TaskFlow is trying to do something to a element that doesn't exist anymore...
			
			return false;
		},
		
		onTaskDragStopValidation : function(j_task) {
			var droppable = j_task.data("droppable");
			
			if (droppable) {
				var droppable_parent = myWFObj.getTaskFlowChart().TaskFlow.getTaskParentTasks( $(droppable) );
				droppable_parent = droppable_parent[0];
				
				return droppable_parent && $(droppable_parent).is(".task_page, .task_form, .task_listing, .task_view");
			}
			return false;
		},
		
		onTaskDragStopEnd : function(j_task) {
			var WF = myWFObj.getTaskFlowChart();
			var parent_task = WF.TaskFlow.getTaskParentTasks(j_task);
			
			if (parent_task[0])
				WF.TaskFlow.resizeTaskParentTask(j_task, true); //resize parent task with the new width and height according with this inner task
		},
		
		onCheckLabel : function(label_obj, task_id) {
			return PresentationTaskUtil.isTaskFileLabelValid(label_obj, task_id);
		},

		onCancelLabel : function(task_id) {
			return prepareLabelIfUserLabelIsInvalid(task_id);
		},

		onCompleteLabel : function(task_id) {
			var WF = myWFObj.getTaskFlowChart();
			var label_elm = WF.TaskFlow.getTaskLabelElementByTaskId(task_id);
			label_elm.closest("." + WF.TaskFlow.task_label_class_name).attr("title", label_elm.text());
			
			WF.TaskFlow.repaintTaskByTaskId(task_id);
			
			return true;
		},
		
		/* CONNECTION HANDLERS */
		
		onLoadConnectionProperties : function(properties_html_elm, connection, connection_property_values) {
			var presentation_connection_html = properties_html_elm.find(".presentation_connection_html");
			PresentationTaskUtil.onChangeConnectionType( presentation_connection_html.find(".connection_type select")[0] );
			
			//prepare connection label
			var label_overlay = connection.getOverlay("label");
			var conn_label = label_overlay.getLabel();
			properties_html_elm.find(".connection_label input").val(conn_label);
		},
		
		onSubmitConnectionProperties : function(properties_html_elm, connection, connection_property_values) {
			//update connection label
			var presentation_connection_html = properties_html_elm.find(".presentation_connection_html");
			var conn_label = presentation_connection_html.find(".connection_label input").val();
			var label_overlay = connection.getOverlay("label");
			
			if (label_overlay)
				label_overlay.setLabel(conn_label);
			
			return true;
		},
		
		onCompleteConnectionLabel : function(label_overlay, connection_id) {
			if (label_overlay) {
				var WF = myWFObj.getTaskFlowChart();
				var conn_label = label_overlay.getLabel();
				WF.TaskFlow.connections_properties[connection_id]["connection_label"] = conn_label;
			}
			
			return true;
		},
		
		onTaskConnection : function(conn) {
			var WF = myWFObj.getTaskFlowChart();
			var source_task_id = conn.sourceId;
		  	var target_task_id = conn.targetId;
			
			var source_task = WF.TaskFlow.getTaskById(source_task_id);
			var target_task = WF.TaskFlow.getTaskById(target_task_id);
			
			var is_inner_task = WF.TaskFlow.isTaskAncestorTask(target_task, source_task) || WF.TaskFlow.isTaskAncestorTask(source_task, target_task);
			var is_page = target_task.attr("tag") == "page";
			
			if (is_inner_task) {
				WF.StatusMessage.showError("You cannot link these tasks. Inner tasks connections are not allowed!");
				return false;
			}
			else if (!is_page) {
				WF.StatusMessage.showError("You cannot link these tasks. You can only connect a task to a Page task!");
				return false;
			}
			
			return true;
		},
		
		onChangeConnectionType : function(elm) {
			elm = $(elm);
			
			if (elm.val() == "link")
				elm.parent().parent().children(".connection_target").show();
			else
				elm.parent().parent().children(".connection_target").hide();
		},
		
		/* UTILS */
		/* UTILS - Clean Form Data */
		cleanFormData : function(task_html_element) {
			task_html_element.find("input:not([type=checkbox]):not([type=radio]), textarea, select").val("");
			task_html_element.find("input[type=checkbox], input[type=radio]").removeAttr("checked");
			
			this.updateDBTables( task_html_element.find(".choose_db_table .db_driver > select")[0] );
			
			task_html_element.find(".choose_db_table .db_table_conditions table tbody tr:not(.no_conditions) td.actions .remove").each(function(idx, icon) {
				PresentationTaskUtil.removeDBTableCondition(icon);
			});
			
			task_html_element.find(".links table tbody tr:not(.no_links) td.actions .remove").each(function(idx, icon) {
				PresentationTaskUtil.removeLink(icon);
			});
			
			task_html_element.find(".users_perms table tbody tr:not(.no_users) td.actions .remove").each(function(idx, icon) {
				PresentationTaskUtil.removeUserPerm(icon);
			});
		},
		
		/* UTILS - File Name Functions */
		
		isTaskFileLabelValid : function(label_obj, task_id) {
			//var valid = !inputTextContainsRegex(label_obj.label, /[^\p{L}\w\-\. ]+/u)); //'\w' means all words with '_' and '/u' means with accents and ç too. Cannot use this bc it does not work in IE.
			var valid = !inputTextContainsRegex(label_obj.label, /[^\w\u00C0-\u00D6\u00D8-\u00F6\u00F8-\u024F\u1EBD\u1EBC\-\. ]+/); //'\w' means all words with '_' and 'u' means with accents and ç too.
			
			if (valid)
				valid = inputTextContainsAtLeastOneLetter(label_obj.label); //checks if label has at least one letter
			
			if (!valid) {
				myWFObj.getTaskFlowChart().StatusMessage.showError("Invalid label. Please choose a different label.\nOnly this characters are allowed: a-z, A-Z, 0-9, '-', '_', '.', ' ' and you must have at least 1 letter.");
				
				return false;
			}
			
			return isTaskLabelRepeated(label_obj, task_id) == false;
		},
		
		/* UTILS - Choose DB Table Functions */
		
		updateDBTables : function(elm) {
			var p = $(elm).parent().closest("ul");
			var db_driver = p.find(".db_driver > select").val();
			var db_type = p.find(".db_type > select").val();
			
			var db_table_select = p.find(".db_table > select");
			
			db_table_select.html("").removeAttr("orig_db_table");
			p.find(".db_table_parent > select").html("");
			p.find(".db_attribute > select").html("");
			
			if (db_driver && db_type) {
				var db_tables = this.getDBTables(db_driver, db_type);
				
				var html = "<option></option>";
				for (var db_table in db_tables)
					html += "<option>" + db_table + "</option>";
				
				p.find(".db_table > select").html(html);
				p.find(".db_table_parent > select").html(html);
			}
			
			//empty Table UI
			if (!db_table_select.val()) {
				var db_table_container = p.parent().closest(".list_from_db, .ui_table_attribute_properties_popup, .choose_db_table");
				
				if (db_table_container.hasClass("choose_db_table")) //only if belongs to choose_db_table, otherwise it means it belongs to ".ui_table_attribute_properties_popup .list_from_db" and we shouldn't do anything or we will have an infinity cicle.
					this.updateTableUI( db_table_container.parent().closest(".page_content_task_html") );
			}
		},
		
		updateDBAttributes : function(elm) {
			var p = $(elm).parent().closest("ul");
			var db_driver = p.find(".db_driver > select").val();
			var db_type = p.find(".db_type > select").val();
			var db_table = p.find(".db_table > select").val();
			
			var db_attribute_select = p.find(".db_attribute > select");
			db_attribute_select.html("");
			
			if (db_driver && db_type && db_table) {
				var db_attributes = this.getDBTableAttributes(db_driver, db_type, db_table);
				
				var html = "<option></option>";
				for (var attribute_name in db_attributes)
					html += "<option>" + attribute_name + "</option>";
				
				db_attribute_select.html(html);
				db_attribute_select.val( db_attribute_select.attr("default_attribute") );
			}
		},
		
		onChangeDBTable : function(elm, do_not_confirm) {
			elm = $(elm);
			
			//if (do_not_confirm || confirm("This will update the UI. Do you wish to continue?")) {
				var task_html_element = elm.parent().closest(".page_content_task_html"); //in here I can use .page_content_task_html
				var table = elm.val();
				elm.attr("orig_db_table", table);
				
				this.updateTableUI(task_html_element);
				
				this.updateDBTableConditionsAccordingWithSelectedDBTable(task_html_element);
			/*}
			else {
				var orig_db_table = elm.attr("orig_db_table");
				elm.val(orig_db_table ? orig_db_table : "");
			}*/
		},
		
		onChangeDBTableParent : function(elm) {
			elm = $(elm);
			var task_html_element = elm.parent().closest(".page_content_task_html"); //in here I can use .page_content_task_html
			this.updateDBTableConditionsAccordingWithSelectedDBTable(task_html_element);
		},
		
		onChangeFormAction : function(elm, do_not_confirm) {
			elm = $(elm);
			
			//if (do_not_confirm || confirm("This will update the UI. Do you wish to continue?")) {
				var task_html_element = elm.parent().closest(".page_content_task_html"); //in here I can use .page_content_task_html
				var filter_by_attributes = this.getDesignedDBTableAttributes(task_html_element);
				this.updateTableUI(task_html_element, filter_by_attributes);
			/*}
			else //undo checkbox click action
				elm.is(":checked") ? elm.removeAttr("checked").prop("checked", false) : elm.attr("checked", "checked").prop("checked", true);
			*/
		},
		
		loadTableUIAttributes : function(task_html_element, attributes) {
			if (attributes) {
				if (attributes.hasOwnProperty("name") || attributes.hasOwnProperty("active"))
					attributes = [ attributes ];
				
				if ($.isArray(attributes) || $.isPlainObject(attributes)) {
					var attributes_by_name = {};
					
					$.each(attributes, function(key, attribute_props) {
						var attribute_name = attribute_props["name"] ? attribute_props["name"] : key;
						attributes_by_name[attribute_name] = attribute_props;
					});
					
					PresentationTaskUtil.updateTableUI(task_html_element, attributes_by_name);
					task_html_element.find(" > .ui > .add_ui_table_attribute").show();
					
					PresentationTaskUtil.prepareUIAttributesProperties(task_html_element, attributes_by_name);
				}
			}
		},
		
		updateTableUI : function(task_html_element, filter_by_attributes) {
			var update_table_ui_func = task_html_element.attr("updateTableUIHandler");
			eval("update_table_ui_func = update_table_ui_func && typeof " + update_table_ui_func + " == 'function' ? " + update_table_ui_func + " : null;");
			
			if (update_table_ui_func) {
				this.prepareAddUITableAttributesOptions(task_html_element);
				var table_attributes = this.getSelectedDBTableAttributes( task_html_element.find(".choose_db_table"), filter_by_attributes);
				var ui_attributes_props = this.getUIAttributesProperties(task_html_element);
				
				update_table_ui_func(task_html_element, table_attributes);
				
				if (ui_attributes_props)
					this.prepareUIAttributesProperties(task_html_element, ui_attributes_props);
				
				//update popup size bc the content of the UI tab is dynamic
				myWFObj.getTaskFlowChart().getMyFancyPopupObj().updatePopup();
			}
			else
				alert("Invalid updateTableUIHandler function in PresentationTaskUtil.updateTableUI.");
		},
		
		getDBTables : function(db_driver, db_type) {
			var db_tables = this.getLoadedDBTables(db_driver, db_type);
			
			if (this.get_broker_db_data_url && (!db_tables || $.isEmptyObject(db_tables))) {
				this.initLoadedDBTables(db_driver, db_type);
				
				$.ajax({
					type : "post",
					url : this.get_broker_db_data_url,
					data : {"db_driver" : db_driver, "type" : db_type},
					dataType : "json",
					success : function(data, textStatus, jqXHR) {
						if(data) {
							db_tables = {};
							for (var i = 0; i < data.length; i++)
								db_tables[ data[i] ] = {};
							
							PresentationTaskUtil.db_drivers_tables[db_driver][db_type] = db_tables;
						}
					},
					async: false,
				});
			}
			
			return db_tables;
		},
		
		getDBTableAttributes : function(db_driver, db_type, db_table) {
			var db_attributes = null;
			
			var parts = db_table ? db_table.split(" ") : [];
			db_table = parts[0];
			
			if (db_table) {
				db_attributes = this.getLoadedDBTableAttributes(db_driver, db_type, db_table);
				
				if (this.get_broker_db_data_url && (!db_attributes || $.isEmptyObject(db_attributes)) && db_table) {
					this.initLoadedDBTableAttributes(db_driver, db_type, db_table);
					
					$.ajax({
						type : "post",
						url : this.get_broker_db_data_url,
						data : {"db_driver" : db_driver, "type" : db_type, "db_table" : db_table, "detailed_info" : 1},
						dataType : "json",
						success : function(data, textStatus, jqXHR) {
							db_attributes = data ? data : {};
							
							PresentationTaskUtil.db_drivers_tables[db_driver][db_type][db_table] = db_attributes;
						},
						async: false,
					});
				}
			}
			
			return db_attributes ? db_attributes : {};
		},
		
		prepareDBTableAttributes : function(db_attributes, filter_by_attributes) {
			var new_table_attributes = {};
			
			if (filter_by_attributes) {
				for (var idx in filter_by_attributes) {
					var attribute = filter_by_attributes[idx];
					var attribute_name = attribute.hasOwnProperty("name") ? attribute["name"] : idx;
					
					var attr_name = PresentationTaskUtil.getAttributePropertiesName(attribute_name, db_attributes); 
					
					if (attr_name) {
						var new_attribute = $.extend(true, {}, filter_by_attributes[attribute_name]); //clone this object, otherwise it will be passed by reference. Do not change directly the value of filter_by_attributes[attribute_name], bc is being passed by reference and it will change the main filter_by_attributes object, which could be the WF.TaskFlow.tasks_properties object
						
						new_attribute["db_details"] = db_attributes[attr_name];
						new_table_attributes[attr_name] = new_attribute;
					}
				}
			}
			else
				for (var attribute_name in db_attributes)
					new_table_attributes[attribute_name] = {"db_details" : db_attributes[attribute_name]};
			
			return new_table_attributes;
		},
		
		getSelectedDBTableAttributes : function(choose_db_table_elm, filter_by_attributes) {
			var db_driver = choose_db_table_elm.find(".db_driver > select").val();
			var db_type = choose_db_table_elm.find(".db_type > select").val();
			var db_table = choose_db_table_elm.find(".db_table > select").val();
			
			var db_attributes = this.getDBTableAttributes(db_driver, db_type, db_table);
			var table_attributes = this.prepareDBTableAttributes(db_attributes, filter_by_attributes);
			
			return table_attributes;
		},
		
		getSelectedDBTableParentAttributes : function(choose_db_table_elm, filter_by_attributes) {
			var db_driver = choose_db_table_elm.find(".db_driver > select").val();
			var db_type = choose_db_table_elm.find(".db_type > select").val();
			var db_table_parent = choose_db_table_elm.find(".db_table_parent > select").val();
			
			var db_attributes = this.getDBTableAttributes(db_driver, db_type, db_table_parent);
			var table_attributes = this.prepareDBTableAttributes(db_attributes, filter_by_attributes);
			
			return table_attributes;
		},
		
		getDesignedDBTableAttributes : function(task_html_element) {
			var table_attributes = {};
			var table = task_html_element.find(" > .ui > .ui_html > table");
			
			var query_string = myWFObj.getTaskFlowChart().Property.getPropertiesQueryStringFromHtmlElm(table, "task_property_field");
			
			try {
				parse_str(query_string, table_attributes);
				table_attributes = table_attributes["attributes"];
			}
			catch(e) {
				//alert(e);
				if (console && console.log)
					console.log(e);
			}
			
			return table_attributes;
		},
		
		getLoadedDBTables : function(db_driver, db_type) {
			return this.db_drivers_tables && this.db_drivers_tables[db_driver] && this.db_drivers_tables[db_driver][db_type] ? this.db_drivers_tables[db_driver][db_type] : null;
		},
		
		getLoadedDBTableAttributes : function(db_driver, db_type, db_table) {
			return this.db_drivers_tables && this.db_drivers_tables[db_driver] && this.db_drivers_tables[db_driver][db_type] && this.db_drivers_tables[db_driver][db_type][db_table] ? this.db_drivers_tables[db_driver][db_type][db_table] : null;
		},
		
		initLoadedDBTables : function(db_driver, db_type) {
			if (!this.db_drivers_tables)
				this.db_drivers_tables = {};
			
			if (!this.db_drivers_tables[db_driver])
				this.db_drivers_tables[db_driver] = {};
			
			if (!this.db_drivers_tables[db_driver][db_type])
				this.db_drivers_tables[db_driver][db_type] = {};
		},
		
		initLoadedDBTableAttributes : function(db_driver, db_type, db_table) {
			this.initLoadedDBTables(db_driver, db_type);
			
			if (!this.db_drivers_tables[db_driver][db_type][db_table])
				this.db_drivers_tables[db_driver][db_type][db_table] = {};
		},
		
		toggleAdvancedChooseTableSettings : function(elm) {
			elm = $(elm);
			var advanced_table_settings = elm.parent().closest(".choose_db_table").find(" > ul > .advanced_table_settings");
			advanced_table_settings.toggle();
			
			if (elm.hasClass("maximize"))
				elm.removeClass("maximize").addClass("minimize");
			else
				elm.removeClass("minimize").addClass("maximize");
		},
		
		/* UTILS - DB Table Conditions Functions */
		
		loadDBTableConditions : function(choose_db_table_elm, db_table_conditions) {
			if (db_table_conditions) {
				if (db_table_conditions.hasOwnProperty("attribute") || db_table_conditions.hasOwnProperty("value"))
					db_table_conditions = [ db_table_conditions ];
				
				var add_elm = choose_db_table_elm.find(".db_table_conditions table thead .add");
				
				if ($.isArray(db_table_conditions) || $.isPlainObject(db_table_conditions))
					$.each(db_table_conditions, function(idx, db_table_condition) {
						var row = PresentationTaskUtil.addDBTableCondition(add_elm[0], db_table_condition["attribute"]);
						
						row.find(".value input").val(db_table_condition["value"]);
					});
			}
		},
		
		addDBTableCondition : function(elm, default_attribute) {
			var tbody = $(elm).parent().closest("table").children("tbody");
			tbody.children(".no_conditions").hide();
			var index = getListNewIndex(tbody);
			
			var choose_db_table_elm = tbody.parent().closest(".db_table_conditions").parent();
			var options = this.getDBTableConditionAttributeOptionsHtml(choose_db_table_elm, default_attribute);
			
			var row = '<tr>'
				+ '<td class="attribute"><select class="task_property_field" name="choose_db_table[db_table_conditions][' + index + '][attribute]">' + options + '</select></td>'
				+ '<td class="value"><input class="task_property_field" type="text" name="choose_db_table[db_table_conditions][' + index + '][value]"/></td>'
				+ '<td class="actions"><i class="icon remove" onClick="PresentationTaskUtil.removeDBTableCondition(this)"></i></td>'
			+ '</tr>';
			
			row = $(row);
			tbody.append(row);
			
			return row;
		},
		
		removeDBTableCondition : function(elm) {
			var tr = $(elm).parent().closest("tr");
			var tbody = tr.parent();
			
			tr.remove();
			
			if (tbody.children().length == 1)
				tbody.children(".no_conditions").show();
		},
		
		getDBTableConditionAttributeOptionsHtml : function(parent_elm, default_attribute) {
			var db_table = parent_elm.find(".db_table > select").val();
			var db_table_parent = parent_elm.find(".db_table_parent > select").val();
			var db_attributes = db_table ? this.getSelectedDBTableAttributes(parent_elm) : {};
			var db_parent_attributes = db_table_parent && db_table_parent != db_table ? this.getSelectedDBTableParentAttributes(parent_elm) : {};
			
			var html = '<option></option>';
			
			if (!$.isEmptyObject(db_attributes) || !$.isEmptyObject(db_parent_attributes)) {
				var exists = false;
				
				if (default_attribute) {
					var pos = default_attribute.indexOf(".");
					if (pos == -1)
						default_attribute = (db_table_parent ? db_table_parent : db_table) + "." + default_attribute;
				}
				
				if (db_attributes)
					for (var attribute_name in db_attributes) {
						var attribute_label = (db_table_parent ? db_table + '.' : '') + attribute_name;
						attribute_name = db_table + '.' + attribute_name;
						var selected = attribute_name == default_attribute;
						
						html += '<option value="' + attribute_name + '"' + (selected ? ' selected' : '') + '>' + attribute_label + '</option>';
						
						if (selected)
							exists = true;
					}
				
				if (db_parent_attributes)
					for (var attribute_name in db_parent_attributes) {
						var attribute_label = (db_table ? db_table_parent + '.' : '') + attribute_name;
						attribute_name = db_table_parent + '.' + attribute_name;
						var selected = attribute_name == default_attribute;
						
						html += '<option value="' + attribute_name + '"' + (selected ? ' selected' : '') + '>' + attribute_label + '</option>';
						
						if (selected)
							exists = true;
					}
				
				if (default_attribute && !exists)
					html += '<option value="' + default_attribute + '" class="warning" selected>* ' + default_attribute + ' - DOES NOT BELONG TO THIS TABLE</option>';
			}
			
			return html;
		},
		
		updateDBTableConditionsAccordingWithSelectedDBTable : function(task_html_element) {
			var choose_db_table_elm = task_html_element.find(" > .settings > .choose_db_table");
			var selects = choose_db_table_elm.find(".db_table_conditions > table > tbody > tr > td.attribute > select");
			var options = this.getDBTableConditionAttributeOptionsHtml(choose_db_table_elm);
			
			$.each(selects, function(idx, select) {
				select = $(select);
				var default_attribute = select.val();
				
				select.html(options);
				select.val(default_attribute);
				
				if (select.val() != default_attribute)
					select.append('<option value="' + default_attribute + '" class="warning" selected>* ' + default_attribute + ' - DOES NOT BELONG TO THIS TABLE</option>');
			});
		},
		
		/* UTILS - UI Functions */
		
		getTableAttributeInputType : function(attribute) {
			if (attribute && attribute["db_details"]) {
				var type = attribute["db_details"]["type"].toLowerCase();
				
				for (var input_type in this.html_db_types) {
					var input_db_types = this.html_db_types[input_type];
					var idx = input_db_types.indexOf(type);
					
					if (idx != -1) {
						if ((input_type == "checkbox" || input_type == "number") && (type == "smallint" || type == "tinyint")) {
							if (attribute["db_details"]["length"] == "1")
								return "checkbox";
						}
						else
							return input_type;
					}
				}
			}
			
			return "input";
		},
		
		isTableAttributePKAutoIncremented : function(attribute) {
			if (attribute && attribute["db_details"] && attribute["db_details"]["primary_key"]) {
				var attribute_props = attribute["db_details"];
				var el = attribute_props["extra"] ? ("" + attribute_props["extra"]).toLowerCase() : "";
				
				return attribute_props["auto_increment"] || 
					  el.indexOf("auto_increment") != -1 || 
					  el.indexOf("nextval") != -1 || 
					  (PresentationTaskUtil.auto_increment_db_attributes_types && $.inArray(attribute_props["type"].toLowerCase(), PresentationTaskUtil.auto_increment_db_attributes_types) != -1);
				}
				
			return false;
		},
		
		getTableAttributeInputHtml : function(input_type) {
			switch(input_type) {
				case "checkbox":
					return '<input type="checkbox" value="1" checked disabled/>';
				case "number":
					return '<input type="number" placeHolder="nn..." disabled/>';
				case "date":
					return '<input type="date" placeHolder="yyyy-mm-dd" disabled/>';
				case "datetime":
					return '<input type="datetime" placeHolder="yyyy-mm-dd hh:ii" disabled/>';
				case "time":
					return '<input type="time" placeHolder="hh:ii" disabled/>';
				case "textarea":
					return '<textarea placeHolder="..." disabled></textarea>';
			}
			
			return '<input type="text" placeHolder="..." disabled/>';
		},
		
		setSortableUITableRows : function(elm_task_html) {
			elm_task_html.find(" > .ui > .ui_html > table > tbody").sortable({
				items: "tr",
				axis: "y",
				placeholder: "ui-state-highlight",
				start : function(event, ui) {
					var helper_tds = ui.helper.find("td");
					var place_holder_tds = ui.placeholder.find("td"); //placeHolder contains the tds too and does not have position==absolute, which means we can get the width and height from the inner tds
					
					$.each(helper_tds, function(idx, td) {
						$(td).css({width: $(place_holder_tds[idx]).outerWidth() + "px", height: "100%"});
					});
					
					ui.placeholder.css({height: ui.helper.height() + "px"});
				},
				beforeStop : function(event, ui) {
					$.each(ui.helper.find("td"), function(idx, td) {
						$(td).css({width: "", height: ""});
					});
					
					ui.placeholder.css({height: ""});
				},
				stop : function(event, ui) {
					var tr = ui.item;
					updateListChildrenIndexes(tr.parent());
					
					//prepare child inner elms
					updateListChildrenInnerIndexPrefixIndexes(tr.parent());
				},
			}).disableSelection();
		},
		
		setSortableUITableCols : function(elm_task_html) {
			var ui_html_elm = elm_task_html.find(" > .ui > .ui_html");
			var ui_table_elm = ui_html_elm.find("table");
			
			ui_table_elm.find(" > tr, > thead > tr, > tbody > tr").each(function(idx, tr) {
				$(tr).sortable({
					items: "th",
					axis: "x",
					placeholder: "ui-state-highlight",
					helper: function(event, item) {
						var index = item.index();
						
						//prepare helper table with correspondent columns
						var table = item.parent().closest("table");
						var table_trs = table.find(" > tr, > thead > tr, > tbody > tr, > tfoot > tr");
						var new_table = $('<table></table>');
						var width = 0;
						
						$.each(table_trs, function(idx, table_tr) {
							var table_td = $( $(table_tr).children()[index] );
							var table_td_clone = table_td.clone().addClass("ui-sortable-helper").css({height: table_td.height() + "px"});
							var tr_aux = $('<tr></tr>');
							
							tr_aux.append(table_td_clone);
							new_table.append(tr_aux);
							
							if (width < table_td.width())
								width = table_td.width();
						});
						
						new_table.css({width: width + "px"});
						
						ui_html_elm.prepend(new_table); //prepend helper to .ui_html, in order to the helper position be correct
						
						//save width ad height here. This must be done here and cannot be done in the start handler bc the item will loose the correct width and height.
						var pt = parseInt(item.css("padding-top"));
						var pl = parseInt(item.css("padding-left"));
						var pr = parseInt(item.css("padding-right"));
						var pb = parseInt(item.css("padding-bottom"));
						ui_table_elm.data("item-width", item.width() - (pt ? pt : 0) - (pb ? pb : 0));
						ui_table_elm.data("item-height", item.height() - (pl ? pl : 0) - (pr ? pr : 0));
						return new_table;
					},
					start : function(event, ui) {
						var index = ui.item.index();
						var td_width = ui_table_elm.data("item-width");
						var td_height = ui_table_elm.data("item-height");
						var tr_parent = ui.item.parent();
						var table = tr_parent.parent().closest("table");
						
						tr_parent.children(".ui-state-highlight").html('<div style="width:' + td_width + 'px; height:' + td_height + 'px">');
						
						//prepare place holder in the correspondent columns
						var table_trs = table.find(" > tr, > thead > tr, > tbody > tr, > tfoot > tr");
						$.each(table_trs, function(idx, table_tr) {
							table_tr = $(table_tr);
							
							if (!table_tr.is(tr_parent[0])) {
								var table_td = $(table_tr.children()[index] );
								var pt = parseInt(table_td.css("padding-top"));
								var pl = parseInt(table_td.css("padding-left"));
								var pr = parseInt(table_td.css("padding-right"));
								var pb = parseInt(table_td.css("padding-bottom"));
								var td_w = table_td.width() - (pt ? pt : 0) - (pb ? pb : 0);
								var td_h = table_tr.height() - (pl ? pl : 0) - (pr ? pr : 0);
								
								if (td_width < td_w)
									td_width = td_w;
								
								table_td.css({display: "none"}).addClass("ui-state-sortable-hidden");
								$('<td class="ui-state-highlight"><div style="width:' + td_w + 'px; height:' + td_h + 'px"></td>').insertAfter(table_td); //add place holder. The inner div is what will decide width of the placeholder
							}
						});
					},
					change: function(event, ui) { //only gets triggered when the column is moved to another index position
						var place_holder_index = ui.placeholder.index();
						var tr_parent = ui.placeholder.parent();
						var table = tr_parent.parent().closest("table");
						
						//prepare correspondent columns
						var table_trs = table.find(" > tr, > thead > tr, > tbody > tr, > tfoot > tr");
						$.each(table_trs, function(idx, table_tr) {
							table_tr = $(table_tr);
							
							if (!table_tr.is(tr_parent[0])) {
								var table_tds = table_tr.children();
								var td_place_holder = table_tds.filter(".ui-state-highlight");
								var td_place_holder_index = td_place_holder.index();
								
								var ph_idx = place_holder_index;
								//console.log(place_holder_index+":"+td_place_holder_index+":"+ph_idx);
								if (td_place_holder_index < place_holder_index)
									ph_idx++;
								//console.log(place_holder_index+":"+td_place_holder_index+":"+ph_idx);
								
								var td_next = $(table_tds[ph_idx]);
								
								if (td_next[0])
									td_place_holder.insertBefore(td_next);
								else //last element
									table_tr.append(td_place_holder);
							}
						});
					},
					beforeStop : function(event, ui) {
						var tr_parent = ui.placeholder.parent();
						var table = tr_parent.parent().closest("table");
						
						//prepare correspondent columns
						var table_trs = table.find(" > tr, > thead > tr, > tbody > tr, > tfoot > tr");
						$.each(table_trs, function(idx, table_tr) {
							table_tr = $(table_tr);
							
							if (!table_tr.is(tr_parent[0])) {
								var table_tds = table_tr.children();
								var td_place_holder = table_tds.filter(".ui-state-highlight");
								var td = table_tds.filter(".ui-state-sortable-hidden");
								
								td.removeClass("ui-state-sortable-hidden").css({display: ""});
								td.insertAfter(td_place_holder);
								td_place_holder.remove();
							}
						});
						
						//resets data values
						ui_table_elm.data("item-width", null);
						ui_table_elm.data("item-height", null);
					},
					stop : function(event, ui) {
						var tr = ui.item.parent();
						var table = tr.parent().closest("table");
						var parent_prefix = table.children("thead[index_prefix], tbody[index_prefix], tfoot[index_prefix]").first();
						var table_sections = table.children("thead, tbody, tfoot");
						var parent_index_prefix = parent_prefix.attr("index_prefix");
						
						$.each(table_sections, function(idx, table_section) {
							var trs = $(table_section).children();
							
							$.each(trs, function(idj, tr) {
								var tds = $(tr).children();
								
								$.each(tds, function(idw, td) {
									td = $(td);
									
									//prepare column
									changeListChildWithNewIndex(parent_prefix, td, idw);
									
									//prepare child inner elms
									changeListChildInnerIndexPrefixWithNewIndex(parent_prefix, td, idw);
								});
							});
						});
					},
				}).disableSelection();
			});
		},
		
		removeUITableAttributeRow : function(elm) {
			if (confirm("Do you wish to remove this attribute from this UI?"))
				$(elm).parent().closest("tr").remove();
		},
		
		removeUITableAttributeCol : function(elm) {
			if (confirm("Do you wish to remove this attribute from this UI?")) {
				var td = $(elm).parent().closest("td, th").first();
				var td_index = td.index();
				
				var table_trs = td.parent().closest("table").find(" > tr, > thead > tr, > tbody > tr, > tfoot > tr");
				table_trs.each(function(idx, table_tr) {
					var col = $(table_tr).children()[td_index];
					
					if (col)
						$(col).remove();
				});
			}
		},
		
		prepareAddUITableAttributesOptions : function(task_html_element) {
			var table_attributes = this.getSelectedDBTableAttributes( task_html_element.find(".choose_db_table") );
			var options = "";
			
			if (table_attributes)
				$.each(table_attributes, function(attribute_name, attribute_details) {
					options += '<option value="' + attribute_name + '">' + PresentationTaskUtil.attributeNameToLabel(attribute_name) + '</option>';
				});
			
			task_html_element.find(" > .ui > .add_ui_table_attribute > select").html(options);
		},
		
		addUITableAttribute : function(elm) {
			alert("addUITableAttribute function is not defined. Please set the right function in each task, like: TableTaskPropertyObj.addUITableAttribute");
		},
		
		editUITableAttributeRowProperties : function(elm) {
			var attribute = $(elm).parent().closest("tr").attr("ui_table_attribute_name");
			this.editUITableAttributeProperties(elm, attribute);
		},
		
		editUITableAttributeColProperties : function(elm) {
			var attribute = $(elm).parent().closest("th").attr("ui_table_attribute_name");
			this.editUITableAttributeProperties(elm, attribute);
		},
		
		editUITableAttributeProperties : function(elm, attribute) {
			elm = $(elm);
			var popup_elm = elm.parent().children(".ui_table_attribute_properties_popup");
			
			if (!popup_elm.hasClass("ui-tabs"))
				popup_elm.tabs();
			
			this.ui_table_attribute_properties_popup.hidePopup();
			
			this.updateUITableAttributeListType( popup_elm.find(".list_type select")[0] );
			
			this.ui_table_attribute_properties_popup.init({
				elementToShow: popup_elm,
				onClose: function() {
					PresentationTaskUtil.updateUIDBAttributeLabel( popup_elm[0] );
				}
			});
			this.ui_table_attribute_properties_popup.showPopup();
		},
		
		getUITableAttributePropertiesHtml : function(task_html_element, attribute_idx, attribute_name) {
			var ui_table_attribute_properties_popup_elm = task_html_element.find(" > .ui > .ui_table_attribute_properties_popup");
			var html = ui_table_attribute_properties_popup_elm.html();
			html = html.replace(/#attribute_idx#/g, attribute_idx).replace(/#attribute#/g, attribute_name).replace(/#task_property_field_class#/g, "task_property_field");
			html = '<div class="' + ui_table_attribute_properties_popup_elm.attr("class") + '">' + html + '</div>';
			
			return html;
		},
		
		attributeNameToLabel : function(attribute_name) {
			return stringToUCWords(attribute_name.replace(/[_\-]/g, " "));
		},
		
		/* UTILS - UI - DB Table Attributes Properties Functions */
		
		prepareUIAttributesProperties : function(task_html_element, attributes_properties) {
			if (attributes_properties) {
				var is_table_ui = task_html_element.children(".ui").hasClass("table");
				
				if (is_table_ui) 
					this.prepareColUIAttributesProperties(task_html_element, attributes_properties);
				else
					this.prepareRowUIAttributesProperties(task_html_element, attributes_properties);
			}
		},
		
		prepareRowUIAttributesProperties : function(task_html_element, attributes_properties) {
			task_html_element.find(" > .ui > .ui_html > table > tbody > tr").each(function(idx, tr) {
				tr = $(tr);
				var attribute_name = tr.attr("ui_table_attribute_name");
				
				if (attribute_name && attributes_properties.hasOwnProperty(attribute_name)) {
					var popup = tr.find(".ui_table_attribute_properties_popup");
					
					PresentationTaskUtil.prepareUIAttributeProperties(attribute_name, attributes_properties, popup);
				}
			});
		},
		
		prepareColUIAttributesProperties : function(task_html_element, attributes_properties) {
			task_html_element.find(" > .ui > .ui_html > table > thead > tr > th").each(function(idx, th) {
				th = $(th);
				var attribute_name = th.attr("ui_table_attribute_name");
				
				if (attribute_name && PresentationTaskUtil.getAttributePropertiesName(attribute_name, attributes_properties)) {
					var index = th.index();
					var td = th.parent().closest("table").find(" > tbody > tr > td").get(index);
					var popup = $(td).find(".ui_table_attribute_properties_popup");
					
					PresentationTaskUtil.prepareUIAttributeProperties(attribute_name, attributes_properties, popup);
				}
			});
		},
		
		prepareUIAttributeProperties : function(attribute_name, attributes_properties, popup) {
			var attribute_props_name = attribute_name ? PresentationTaskUtil.getAttributePropertiesName(attribute_name, attributes_properties) : null;
			
			if (attribute_props_name && popup[0]) {
				var attribute_properties = attributes_properties[attribute_props_name];
				attribute_properties = $.isPlainObject(attribute_properties) ? attribute_properties : {};
				
				var field_ids = ["type", "link", "target", "class", "label_class", "label_value", "label_previous_html", "label_next_html", "input_class", "input_value", "input_previous_html", "input_next_html"];
				
				$.each(field_ids, function(idx, field_id) {
					var v = attribute_properties[field_id];
					var field_input = popup.find("." + field_id).children("input, textarea, select");
					field_input.val(v);
				});
				
				var list_type = attribute_properties["list_type"];
				var list_type_select = popup.find(".list_type > select");
				list_type_select.val(list_type);
				this.updateUITableAttributeListType(list_type_select[0]);
				
				if (list_type == "manual")
					this.loadUITableAttributeManualListItems(popup, attribute_properties["manual_list"]);
				else if (list_type == "from_db") {
					var db_driver = attribute_properties["db_driver"];
					var db_type = attribute_properties["db_type"];
					var db_table = attribute_properties["db_table"];
					var db_table_alias = attribute_properties["db_table_alias"];
					var db_attribute_label = attribute_properties["db_attribute_label"];
					var db_attribute_fk = attribute_properties.hasOwnProperty("db_attribute_fk") ? attribute_properties["db_attribute_fk"] : attribute_name;
					
					var db_driver_select = popup.find(".db_driver > select");
					db_driver_select.val(db_driver);
					popup.find(".db_type > select").val(db_type);
					this.updateDBTables( db_driver_select[0] );
					
					var db_table_select = popup.find(".db_table > select");
					db_table_select.val(db_table);
					this.updateDBAttributes( db_table_select[0] );
					
					var db_table_alias_input = popup.find(".db_table_alias > input");
					db_table_alias_input.val(db_table_alias);
					
					var db_attribute_label_select = popup.find(".db_attribute_label > select");
					db_attribute_label_select.val(db_attribute_label);
					
					var db_attribute_fk_select = popup.find(".db_attribute_fk > select");
					db_attribute_fk_select.val(db_attribute_fk);
				}
				
				var link_input = popup.find(".link > input");
				this.updateUIDBAttributeLabel( link_input[0] );
			}
			else
				popup.find("input, select").val("");
		},
		
		getAttributePropertiesName : function(attribute_name, attributes_properties) {
			if (attributes_properties)
				for (var attr_name in attributes_properties)
					if (attr_name.toLowerCase() == attribute_name.toLowerCase())
						return attr_name;
				
			return null;
		},
		
		updateUITableAttributeListType : function(elm) {
			elm = $(elm);
			var list_type = elm.val();
			var p = elm.parent().closest(".ui_table_attribute_properties_popup");
			var list_from_db = p.find(".list_from_db");
			var manual_list = p.find(".manual_list");
			
			list_from_db.hide();
			manual_list.hide();
			
			if (list_type == "manual")
				manual_list.show();
			else if (list_type == "from_db")
				list_from_db.show();
		},
		
		updateUIDBAttributeLabel : function(elm) {
			var td = $(elm).parent().closest("td");
			var popup = td.find(".ui_table_attribute_properties_popup");
			var type = popup.find(".type select").val();
			var list_type = popup.find(".list_type select").val();
			var info = td.hasClass("ui_table_attribute_value") ? td.children(".info") : td.parent().find(" > .ui_table_attribute_value > .info");
			
			if (!info[0]) {
				info = $('<div class="info"></div>');
				
				td.hasClass("ui_table_attribute_value") ? td.append(info) : td.parent().children(".ui_table_attribute_value").append(info);
			}
			
			var info_text = "";
			
			if (list_type == "manual")
				info_text = "manual_list";
			else if (list_type == "from_db") {
				var db_table = popup.find(".db_table > select").val();
				var db_table_alias = popup.find(".db_table_alias > input").val();
				var db_attribute_label = popup.find(".db_attribute_label > select").val();
				
				if (db_table && db_attribute_label)
					info_text = (db_table_alias ? db_table_alias : db_table) + "->" + db_attribute_label;
			}
			
			if (type)
				info_text += " (" + type + ")";
			
			if (info_text) {
				info.html(info_text);
				
				var link = popup.find(".link > input").val();
				link ? info.addClass("with_link") : info.removeClass("with_link");
			}
			else if (info[0])
				info.remove();
		},
		
		getUIAttributesProperties : function(task_html_element) {
			var attributes_properties = {};
			var query_string = myWFObj.getTaskFlowChart().Property.getPropertiesQueryStringFromHtmlElm(task_html_element.find(".ui"), "task_property_field");
			
			try {
				parse_str(query_string, attributes_properties);
			}
			catch(e) {
				//alert(e);
				if (console && console.log)
					console.log(e);
			}
			//console.log(attributes_properties);
			
			//convert attributes by index to attributes by name
			var attributes = attributes_properties && attributes_properties.hasOwnProperty("attributes") ? attributes_properties["attributes"] : null;
			var attributes_by_name = null;
			
			if (attributes) {
				attributes_by_name = {};
				
				for (var idx in attributes) {
					var attribute = attributes[idx];
					
					if (attribute.hasOwnProperty("name"))
						attributes_by_name[ attribute["name"] ] = attribute;
				}
			}
			
			return attributes_by_name;
		},
		
		/* UTILS - UI - DB Table Attributes Properties - Manual List Functions */
		
		loadUITableAttributeManualListItems : function(popup, items) {
			if (items) {
				if (items.hasOwnProperty("value") || items.hasOwnProperty("label"))
					items = [ items ];
				
				if ($.isArray(items) || $.isPlainObject(items)) {
					var add_elm = popup.find(".manual_list thead .add");
				
					$.each(items, function(idx, item) {
						var row = PresentationTaskUtil.addUITableAttributeManualListItem(add_elm[0]);
						
						row.find(".value input").val(item["value"]);
						row.find(".label input").val(item["label"]);
					});
				}
			}
		},
		
		addUITableAttributeManualListItem : function(elm) {
			var tbody = $(elm).parent().closest("table").children("tbody");
			tbody.children(".no_items").hide();
			var index = getListNewIndex(tbody);
			var prefix_name = tbody.attr("index_prefix");
			
			var row = '<tr>'
				+ '<td class="value"><input class="task_property_field" type="text" name="' + prefix_name + '[' + index + '][value]"/></td>'
				+ '<td class="label"><input class="task_property_field" type="text" name="' + prefix_name + '[' + index + '][label]"/></td>'
				+ '<td class="actions"><i class="icon remove" onClick="PresentationTaskUtil.removeUITableAttributeManualListItem(this)"></i></td>'
			+ '</tr>';
			
			row = $(row);
			tbody.append(row);
			
			return row;
		},
		
		removeUITableAttributeManualListItem : function(elm) {
			var tr = $(elm).parent().closest("tr");
			var tbody = tr.parent();
			
			tr.remove();
			
			if (tbody.children().length == 1)
				tbody.children(".no_items").show();
		},
		
		/* UTILS - Links Functions */
		
		loadLinks : function(task_html_element, links) {
			if (links) {
				if (links.hasOwnProperty("url") || links.hasOwnProperty("value") || links.hasOwnProperty("title") || links.hasOwnProperty("class") || links.hasOwnProperty("target"))
					links = [ links ];
				
				if ($.isArray(links) || $.isPlainObject(links)) {
					var add_elm = task_html_element.find(".links table thead .add");
				
					$.each(links, function(idx, link) {
						var row = PresentationTaskUtil.addLink(add_elm[0]);
						
						row.find(".url input").val(link["url"]);
						row.find(".value input").val(link["value"]);
						row.find(".title input").val(link["title"]);
						row.find(".class input").val(link["class"]);
						row.find(".target input").val(link["target"]);
					});
				}
			}
		},
		
		addLink : function(elm) {
			var tbody = $(elm).parent().closest("table").children("tbody");
			tbody.children(".no_links").hide();
			var index = getListNewIndex(tbody);
			
			var row = '<tr>'
				+ '<td class="value"><input class="task_property_field" type="text" name="links[' + index + '][value]"/></td>'
				+ '<td class="title"><input class="task_property_field" type="text" name="links[' + index + '][title]"/></td>'
				+ '<td class="url"><input class="task_property_field" type="text" name="links[' + index + '][url]"/><i class="icon search" onclick="PresentationTaskUtil.onChooseLinkPageUrl(this)">Search</i></td>'
				+ '<td class="class"><input class="task_property_field" type="text" name="links[' + index + '][class]"/></td>'
				+ '<td class="target"><input class="task_property_field" type="text" name="links[' + index + '][target]"/></td>'
				+ '<td class="actions"><i class="icon remove" onClick="PresentationTaskUtil.removeLink(this)"></i></td>'
			+ '</tr>';
			
			row = $(row);
			tbody.append(row);
			
			return row;
		},
		
		removeLink : function(elm) {
			var tr = $(elm).parent().closest("tr");
			var tbody = tr.parent();
			
			tr.remove();
			
			if (tbody.children().length == 1)
				tbody.children(".no_links").show();
		},
		
		onChooseLinkPageUrl : function(elm) {
			if (typeof this.on_choose_file_callback == "function")
				this.on_choose_file_callback(elm);
		},
		
		/* UTILS - Actions Functions */
		
		toggleAdvancedActionSettings : function(elm) {
			elm = $(elm);
			var advanced_action_settings = elm.parent().closest("li").children(".advanced_action_settings");
			advanced_action_settings.toggle();
			
			if (elm.hasClass("maximize"))
				elm.removeClass("maximize").addClass("minimize");
			else
				elm.removeClass("minimize").addClass("maximize");
		},
		
		/* UTILS - Permissions Functions */
		
		loadUsersPerms : function(task_html_element, users_perms) {
			if (users_perms) {
				if (users_perms.hasOwnProperty("user_type_id") || users_perms.hasOwnProperty("activity_id")) 
					users_perms = [ users_perms ];
				
				if ($.isArray(users_perms) || $.isPlainObject(users_perms)) {
					var add_elm = task_html_element.find(".permissions > .users_perms > table > thead .add");
					
					$.each(users_perms, function(idx, user_perm) {
						var row = PresentationTaskUtil.addUserPerm(add_elm[0]);
						var user_type_id_elm = row.find(".user_type_id select");
						var activity_id_elm = row.find(".activity_id select");
						
						user_type_id_elm.val(user_perm["user_type_id"]);
						activity_id_elm.val(user_perm["activity_id"]);
						
						//in case the values don't exist, add them...
						if (user_type_id_elm.val() != user_perm["user_type_id"]) 
							user_type_id_elm.append('<option selected>NOT IN DB: ' + user_perm["user_type_id"] + '</option>');
						
						//in case the values don't exist, add them...
						if (activity_id_elm.val() != user_perm["activity_id"]) 
							activity_id_elm.append('<option selected>NOT IN DB: ' + user_perm["activity_id"] + '</option>');
					});
				}
			}
		},
		
		addUserPerm : function(elm) {
			var tbody = $(elm).parent().closest("table").children("tbody");
			tbody.children(".no_users").hide();
			var index = getListNewIndex(tbody);
			
			var tr = '<tr>'
				+ '		<td class="user_type_id">'
				+ '			<select class="task_property_field" name="users_perms[' + index + '][user_type_id]">';
				
			if (PresentationTaskUtil.available_user_types)
				$.each(PresentationTaskUtil.available_user_types, function(user_type_id, user_type_name) {
					tr += '		<option value="' + user_type_id + '">' + user_type_name + '</option>';
				});
				
			tr += '			</select>'
				+ '		</td>'
				+ '		<td class="activity_id">'
				+ '			<select class="task_property_field" name="users_perms[' + index + '][activity_id]">';
				
			if (PresentationTaskUtil.available_activities)
				$.each(PresentationTaskUtil.available_activities, function(activity_id, activity_name) {
					tr += '		<option value="' + activity_id + '">' + activity_name + '</option>';
				});
				
			tr += '			</select>'
				+ '		</td>'
				+ '		<td class="actions">'
				+ '			<i class="icon remove" onClick="PresentationTaskUtil.removeUserPerm(this)"></i>'
				+ '		</td>'
				+ '	</tr>';
			
			tr = $(tr);
			
			tbody.append(tr);
			
			return tr;
		},
		
		removeUserPerm : function(elm) {
			var tr = $(elm).parent().closest('tr');
			var tbody = tr.parent().closest("tbody");
			tr.remove();
			
			if (tbody.children().length == 1)
				tbody.children(".no_users").show();
		},
		
		/* UTILS - Users Management Admin Panel Functions */
		
		openUsersManagementAdminPanelPopup : function(elm) {
			elm = $(elm);
			var popup_elm = elm.parent().children(".users_management_admin_panel_popup");
			var iframe = popup_elm.children("iframe");
			var url = !iframe.attr("src") ? this.users_management_admin_panel_url : null;
			
			var popup = new MyFancyPopupClass();
			popup.init({
				elementToShow: popup_elm,
				type: "iframe",
				url: url,
			});
			popup.showPopup();
		},
	}
}
