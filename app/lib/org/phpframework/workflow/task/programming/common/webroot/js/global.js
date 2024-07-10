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

if (typeof is_global_programming_common_file_already_included == "undefined") {
	var is_global_programming_common_file_already_included = 1;
	
	var ProgrammingTaskUtil = {
		variables_in_workflow : {},
		
		on_programming_task_edit_source_callback : null,
		on_programming_task_choose_created_variable_callback : null,
		on_programming_task_choose_object_property_callback : null,
		on_programming_task_choose_object_method_callback : null,
		on_programming_task_choose_function_callback : null,
		on_programming_task_choose_class_name_callback : null,
		on_programming_task_choose_file_path_callback : null,
		on_programming_task_choose_folder_path_callback : null,
		on_programming_task_choose_page_url_callback : null,
		on_programming_task_choose_image_url_callback : null,
		on_programming_task_choose_webroot_file_url_callback : null,
		on_programming_task_properties_new_html_callback : null,
		
		connections_to_add_after_deletion: null,
		
		onTaskCreation : function(task_id) {
			var WF = myWFObj.getTaskFlowChart();
			var task = WF.TaskFlow.getTaskById(task_id);
			
			task.addClass("logic_task");
		},
		
		onTaskCloning : function(task_id) {
			onTaskCloning(task_id);
			
			//add default label
			var WF = myWFObj.getTaskFlowChart();
			WF.TaskFlow.setTaskLabelByTaskId(task_id, {label: "Add your label"});
		},
		
		onConnectionDrag : function(conn) {
			if (onlyAllowOneConnectionPerExitAndNotToItSelf(conn)) {
				var end_point_elm = conn.sourceEndpoint.element[0];
				var connection_label = $(end_point_elm).children("span").text();
				connection_label = connection_label ? connection_label : end_point_elm.getAttribute("connection_exit_id");
				
				conn.connection.getOverlay("label").setLabel(connection_label);
				
				return true;
			}
			
			return false;
		},
		
		onConnectionDrop : function(conn) {
			//check if target is start task, and if so sets source to start task and remove target as start task.
			if (conn.target.attr("is_start_task")) {
				var WF = myWFObj.getTaskFlowChart();
				
				conn.source.attr("is_start_task", 1);
				conn.source.addClass(WF.TaskFlow.start_task_class_name);
				
				conn.target.removeAttr("is_start_task");
				conn.target.removeClass(WF.TaskFlow.start_task_class_name);
			}
			
			return true;
		},
		
		addCodeMenuOnShowTaskMenu : function(task_id, j_task, task_context_menu) {
			var show_code_menu = task_context_menu.children(".show_code");
			
			if (!show_code_menu[0]) {
				var li = $('<li class="show_code"><a href="#">Show Code</a></li>');
				
				li.click(function(originalEvent) {
					var WF = myWFObj.getTaskFlowChart();
					var selected_task_id = WF.ContextMenu.getContextMenuTaskId();
					var selected_task = WF.TaskFlow.getTaskById(selected_task_id);
					var show_code_timeout_id = selected_task.data("show_code_timeout_id");
					
					if (show_code_timeout_id)
						clearTimeout(show_code_timeout_id);
					
					selected_task.addClass("show_code");
					
					show_code_timeout_id = setTimeout(function() {
						selected_task.removeClass("show_code");
					}, 5000);
					
					selected_task.data("show_code_timeout_id", show_code_timeout_id);
				});
				
				task_context_menu.children(".delete").before(li);
			}
		},
		
		removeCodeMenuOnShowTaskMenu : function(task_id, j_task, task_context_menu) {
			task_context_menu.children(".show_code").remove();
		},
		
		addIncludeFileTaskBeforeTaskIfNotExistsYet : function(task_id, file_path, type, once) {
			var WF = myWFObj.getTaskFlowChart();
			WF.StatusMessage.showMessage("Checking if \"" + file_path + "\" exists and if not, add the correspondent include_file task");
			
			if (!this.existsIncludeFileTaskBeforeTask(task_id, file_path, type)) {
				ProgrammingTaskUtil.addIncludeFileTaskBeforeTask(task_id, file_path, type, once);
				
				WF.StatusMessage.removeLastShownMessage("status");
			}
		},
		
		//given a task id, checks all above connections and check if before there is an include task with the same file_path
		existsIncludeFileTaskBeforeTask : function(task_id, file_path, type) {
			var WF = myWFObj.getTaskFlowChart();
			var connections = WF.TaskFlow.getTargetConnections(task_id);
			
			var task_tag = "includefile";
			var task_menu = $("#" + WF.ContextMenu.main_tasks_menu_obj_id + " .task_menu[tag=" + task_tag + "]");
			var task_type = task_menu.attr("type");
			
			for (var i = 1, t = connections.length; i < t; i++) {
				var connection = connections[i];
				var source_task = connection.source;
				var source_task_id = connection.sourceId;
				var source_task_type = source_task.attr("type");
				
				if (source_task_type == task_type) {
					var task_property_values = WF.TaskFlow.tasks_properties[source_task_id];
					
					if (task_property_values && task_property_values["file_path"] == file_path && task_property_values["type"] == type)
						return true;
				}
				
				if (this.existsIncludeFileTaskBeforeTask(source_task_id, file_path, type))
					return true;
			}
			
			return false;
		},
		
		addIncludeFileTaskBeforeTask : function(task_id, file_path, type, once) {
			var WF = myWFObj.getTaskFlowChart();
			var task_tag = "includefile";
			var task_menu = $("#" + WF.ContextMenu.main_tasks_menu_obj_id + " .task_menu[tag=" + task_tag + "]");
			var task_type = task_menu.attr("type");
			
			if (task_type) {
				var connections = WF.TaskFlow.getTargetConnections(task_id);
				var connection = connections[0];
				var include_task_id = null;
				
				if (connection) { //add new task between task
					include_task_id = WF.ContextMenu.addTaskByTypeToConnection(task_type, connection);
					
					//change other target connections to include_task_id
					if (include_task_id) {
						var include_task = WF.TaskFlow.getTaskById(include_task_id);
						
						for (var i = 1, t = connections.length; i < t; i++) {
							var connection = connections[i];
							
							WF.ContextMenu.setTaskBetweenConnection(include_task, connection);
						}
						
						//bc the above code added the include_task to all the previous and after connections, creating multiple connections between the include_task and task_id, now we need to delete the extra connections between the include_task and task_id.
						var include_source_connections = WF.TaskFlow.getSourceConnections(include_task_id);
						
						for (var i = 1, t = include_source_connections.length; i < t; i++) {
							var connection = include_source_connections[i];
							
							WF.TaskFlow.deleteConnection(connection.id, true);
						}
					}
				}
				else { //add task before task
					var task = WF.TaskFlow.getTaskById(task_id);
					var task_offset = task.offset();
					var obj_offset = {
						top : task_offset.top - 100 > 0 ? task_offset.top - 100 : 20,
						left : task_offset.left + 100,
					};
					var droppable = task.parent();
					include_task_id = WF.ContextMenu.addTaskByType(task_type, obj_offset, droppable);
					
					if (include_task_id) {
						//connect both tasks
						//get new task default exit - based in task properties
						var task_properties_html_element = $("#" + WF.TaskFlow.main_tasks_properties_obj_id + " .task_properties_" +  task_type.toLowerCase()).html();
						var task_property_exits = $(task_properties_html_element).find(".task_property_exit");
						var task_property_exit = task_property_exits[0];
						
						if (task_property_exit && task_property_exit.hasAttribute("exit_id")) {
							var exit_id = task_property_exit.getAttribute("exit_id");
							var exit_color = task_property_exit.getAttribute("exit_color");
							var exit_label = task_property_exit.getAttribute("exit_label");
							var include_task = WF.TaskFlow.getTaskById(include_task_id);
							
							if (!exit_color) {
								var default_color = include_task.css("border-color");
								exit_color = default_color ? default_color : "#000";
							}
							
							connection = WF.TaskFlow.connect(include_task_id, task_id, exit_label, null, null, {
								id : exit_id,
								color : exit_color,
							});
							
							if (connection) {
								//check if target is start task, and if so sets source to start task and remove target as start task.
								if (task.attr("is_start_task")) {
									include_task.attr("is_start_task", 1);
									include_task.addClass(WF.TaskFlow.start_task_class_name);
									
									task.removeAttr("is_start_task");
									task.removeClass(WF.TaskFlow.start_task_class_name);
								}
							}
							else {
								WF.TaskFlow.deleteTask(include_task_id, {to_confirm: false});
								include_task_id = null;	
							}
						}
					}
				}
				
				if (include_task_id) {
					var task_property_values = WF.TaskFlow.tasks_properties[include_task_id];
					task_property_values["file_path"] = file_path;
					task_property_values["type"] = type;
					task_property_values["once"] = once;
					
					return true;
				}
			}
			
			return false;
		},
		
		addIncludeFileTaskBeforeTaskFromSelectedTaskProperties : function(file_path, type, once) {
			var WF = myWFObj.getTaskFlowChart();
			var selected_task_properties = $("#" + WF.Property.selected_task_properties_id);
			var task_id = selected_task_properties.attr("task_id");
			
			this.addIncludeFileTaskBeforeTask(task_id, file_path, type, once);
		},
		
		onSuccessTaskBetweenConnection : function(task_id) {
			var WF = myWFObj.getTaskFlowChart();
			
			//set new created task on the same position than the following task.
			var task = WF.TaskFlow.getTaskById(task_id);
			var extra_top = WF.TaskSort.task_margins_top_and_bottom_average * 2;
			var parent_connections = WF.TaskFlow.getTargetConnections(task_id);
			var child_connections = WF.TaskFlow.getSourceConnections(task_id);
			var pl = parent_connections.length;
			var cl = child_connections.length;
			var parent_id = null;
			var child_id = null;
			
			//get parent task id
			for (var i = 0; i < pl; i++) {
				parent_id = parent_connections[i].sourceId;
				
				if (parent_id)
					break;
			}
			
			//get child task id
			for (var i = 0; i < cl; i++) {
				child_id = child_connections[i].targetId;
				
				if (child_id)
					break;
			}
			
			if (child_id) {
				var parent_task = WF.TaskFlow.getTaskById(parent_id);
				var child_task = WF.TaskFlow.getTaskById(child_id);
				var parent_top = parseInt(parent_task.css("top"));
				var child_top = parseInt(child_task.css("top"));
				var push_tasks_down = true;
				
				//check if there is enough space between parent and children
				if (parent_id) {
					var task_top = parseInt(task.css("top"));
					
					//push_tasks_down = child_top - (parent_top + parent_task.height()) < extra_top + task.height() + extra_top; 
					push_tasks_down = parent_top + parent_task.height() + extra_top + task.height() + extra_top > child_top;
					
					//console.log(child_top - (parent_top + parent_task.height()));
					//console.log(extra_top + task.height() + extra_top);
					//console.log(push_tasks_down);
				}
				
				//by default, align the new task to the left of the child
				task.css("left", child_task.css("left"));
				
				//pull all children down
				if (push_tasks_down) {
					var new_top = parent_top + parent_task.height() + extra_top;
					task.css("top", new_top + "px");
					var push_down_ignore_tasks_id = [parent_id, task_id];
					
					//then push down all following tasks that are connected to this new task.
					for (var i = 0; i < cl; i++) {
						var child_id = child_connections[i].targetId;
						var child_task = WF.TaskFlow.getTaskById(child_id);
						var child_top = parseInt(child_task.css("top"));
						
						if (child_top > parent_top + parent_task.height()) {
							//find the top diff to push child tasks down
							var diff = child_top - (parent_top + parent_task.height()) + task.height();
							
							//push children down
							ProgrammingTaskUtil.pushDownFollowingTask(child_id, diff, push_down_ignore_tasks_id);
						}
					}
				}
				
				WF.TaskFlow.repaintAllTasks();
			}
		},
		
		pushDownFollowingTask : function(task_id, extra_top, ignore_tasks_id) {
			ignore_tasks_id = $.isArray(ignore_tasks_id) ? ignore_tasks_id : [];
			
			if (ignore_tasks_id.indexOf(task_id) == -1) { //ignore_tasks_id are very important bc if there is an infinit loop the repeated tasks should be stopped.
				ignore_tasks_id.push(task_id);
				
				var WF = myWFObj.getTaskFlowChart();
				var task = WF.TaskFlow.getTaskById(task_id);
				var top = parseInt(task.css("top")) + extra_top;
				task.css("top", top + "px");
				
				var child_connections = WF.TaskFlow.getSourceConnections(task_id);
				var cl = child_connections.length;
				
				for (var i = 0; i < cl; i++)
					ProgrammingTaskUtil.pushDownFollowingTask(child_connections[i].targetId, extra_top, ignore_tasks_id);
			}
		},
		
		prepareEditSourceIcon : function(task_html_elm) {
			var hide = typeof ProgrammingTaskUtil.on_programming_task_edit_source_callback != "function";
			
			if (hide)
				task_html_elm.find(".edit_source").each(function(idx, icon) {
					$(icon).hide();
				});
		},
		
		createTaskLabelField : function(properties_html_elm, task_id) {
			var label = myWFObj.getTaskFlowChart().TaskFlow.getTaskLabelByTaskId(task_id);
			label = label ? label.replace(/"/g, "&quot;") : "";
			
			properties_html_elm.find(".properties_task_id").html('<input type="text" value="' + label + '" old_value="' + label + '" />');
		},
		
		onEditLabel : function(task_id) {
			onEditLabel(task_id);
			
			updateTaskLabelInShownTaskProperties(task_id, ".properties_task_id input");
			
			myWFObj.getTaskFlowChart().TaskFlow.repaintTaskByTaskId(task_id);
			
			return true;
		},
		
		saveTaskLabelField : function(properties_html_elm, task_id) {
			var old_label = properties_html_elm.find(".properties_task_id input").attr("old_value");
			var new_label = properties_html_elm.find(".properties_task_id input").val();
			
			if (new_label && old_label != new_label) {
				myWFObj.getTaskFlowChart().TaskFlow.getTaskLabelElementByTaskId(task_id).html(new_label);
				
				onEditLabel(task_id);
			}
		},
		
		saveNewVariableInWorkflowAccordingWithType : function(task_html_elm, class_name) {
			var type = task_html_elm.find(".result_var_type select").val();
		
			class_name = class_name ? class_name : null;
		
			if (type == "variable") {
				var var_name = task_html_elm.find(".result_var_name input").val();
				var_name = var_name ? var_name.replace(/^\s+/, "").replace(/\s+$/, "") : "";//trim;
				
				if (var_name) {
					var_name = var_name.charAt(0) == '$' ? var_name : '$' + var_name;
					this.variables_in_workflow[var_name] = {"class_name" : class_name};
				}
			}
			else if (type == "obj_prop") {
				var obj_name = task_html_elm.find(".result_obj_name input").val();
				var prop_name = task_html_elm.find(".result_prop_name input").val();
				var is_static = task_html_elm.find(".result_static input").prop("checked");
				
				obj_name = obj_name ? obj_name.replace(/^\s+/, "").replace(/\s+$/, "") : "";//trim;
				prop_name = prop_name ? prop_name.replace(/^\s+/, "").replace(/\s+$/, "") : "";//trim;
				
				if (obj_name && prop_name) {
					var var_name = obj_name + (is_static ? "::" : "->") + prop_name;
					var_name = var_name.charAt(0) == '$' || is_static ? var_name : '$' + var_name;
					this.variables_in_workflow[var_name] = {"class_name" : class_name};
				}
			}
		},
		
		saveNewVariableInWorkflowAccordingWithTaskPropertiesValues : function(task_property_values, class_name) {
			if (task_property_values) {
				var result_var_name = task_property_values["result_var_name"];
				var result_obj_name = task_property_values["result_obj_name"];
				var result_prop_name = task_property_values["result_prop_name"];
				var result_static = task_property_values["result_static"];
				
				result_var_name = result_var_name ? result_var_name.replace(/^\s+/, "").replace(/\s+$/, "") : "";//trim;
				result_obj_name = result_obj_name ? result_obj_name.replace(/^\s+/, "").replace(/\s+$/, "") : "";//trim;
				result_prop_name = result_prop_name ? result_prop_name.replace(/^\s+/, "").replace(/\s+$/, "") : "";//trim;
				
				if (result_var_name) {
					result_var_name = result_var_name.charAt(0) == '$' ? result_var_name : '$' + result_var_name;
					
					if (class_name) {
						this.variables_in_workflow[result_var_name] = {"class_name" : class_name};
					}
					else {
						this.variables_in_workflow[result_var_name] = {};
					}
				}
				else if (result_obj_name || result_prop_name) {
					var var_name = result_obj_name + (result_static ? "::" : "->") + result_prop_name;
					var_name = var_name.charAt(0) == '$' || result_static ? var_name : '$' + var_name;
					
					if (class_name) {
						this.variables_in_workflow[var_name] = {"class_name" : class_name};
					}
					else {
						this.variables_in_workflow[var_name] = {};
					}
				}
			}
		},
		
		onSubmitResultVariableType : function(task_html_elm) {
			var type = task_html_elm.find(".result .result_var_type select").val();
			
			switch(type) {
				case "variable":
					task_html_elm.find(".type_obj_prop, .type_echo, .type_return").remove();
					break;
				case "obj_prop": 
					task_html_elm.find(".type_variable, .type_echo, .type_return").remove();
					break;
				case "echo": 
					task_html_elm.find(".type_echo input").val(1);
					task_html_elm.find(".type_variable, .type_obj_prop, .type_return").remove();
					break;
				case "return": 
					task_html_elm.find(".type_return input").val(1);
					task_html_elm.find(".type_variable, .type_obj_prop, .type_echo").remove();
					break;
				default:
					task_html_elm.find(".type_variable, .type_obj_prop, .type_echo, .type_return").remove();
			}
		},
	
		onChangeResultVariableType : function(elm) {
			elm = $(elm);
		
			var type = elm.val();
			var task_html_elm = elm.parent().parent().parent();
			var WF = myWFObj.getTaskFlowChart();
			
			switch(type) {
				case "variable":
					task_html_elm.find(".type_variable").show();
					task_html_elm.find(".type_obj_prop, .type_echo, .type_return").hide();
					WF.getMyFancyPopupObj().resizeOverlay();
					break;
				case "obj_prop": 
					task_html_elm.find(".type_obj_prop").show();
					task_html_elm.find(".type_variable, .type_echo, .type_return").hide();
					WF.getMyFancyPopupObj().resizeOverlay();
					break;
				case "echo": 
					task_html_elm.find(".type_echo").show();
					task_html_elm.find(".type_variable, .type_obj_prop, .type_return").hide();
					WF.getMyFancyPopupObj().resizeOverlay();
					break;
				case "return": 
					task_html_elm.find(".type_return").show();
					task_html_elm.find(".type_variable, .type_obj_prop, .type_echo").hide();
					WF.getMyFancyPopupObj().resizeOverlay();
					break;
				default:
					task_html_elm.find(".type_variable, .type_obj_prop, .type_echo, .type_return").hide();
			}
		},
	
		setResultVariableType : function(task_property_values, task_html_elm) {
			var result_var_name = task_property_values["result_var_name"];
			var result_obj_name = task_property_values["result_obj_name"];
			var result_prop_name = task_property_values["result_prop_name"];
			var result_static = task_property_values["result_static"];
			var result_echo = task_property_values["result_echo"];
			var result_return = task_property_values["result_return"];
			
			if (result_var_name) {
				task_html_elm.find(".type_variable").show();
				task_html_elm.find(".type_obj_prop, .type_echo, .type_return").hide();
				task_html_elm.find(".result_var_type select").val("variable");
			}
			else if (result_obj_name || result_prop_name) {
				task_html_elm.find(".type_obj_prop").show();
				task_html_elm.find(".type_variable, .type_echo, .type_return").hide();
				task_html_elm.find(".result_var_type select").val("obj_prop");
				
				result_obj_name = result_obj_name ? result_obj_name : "";
				result_obj_name = result_static != 1 && result_obj_name.substr(0, 1) == "$" ? result_obj_name.substr(1) : result_obj_name;
				task_html_elm.find(".result_obj_name input").val(result_obj_name);
			}
			else if (result_echo) {
				task_html_elm.find(".type_echo").show();
				task_html_elm.find(".type_variable, .type_obj_prop, .type_return").hide();
				task_html_elm.find(".result_var_type select").val("echo");
			}
			else if (result_return) {
				task_html_elm.find(".type_return").show();
				task_html_elm.find(".type_variable, .type_obj_prop, .type_echo").hide();
				task_html_elm.find(".result_var_type select").val("return");
			}
			else {
				task_html_elm.find(".type_variable, .type_obj_prop, .type_echo, .type_return").hide();
				task_html_elm.find(".result_var_type select").val("");
			}
		},
		
		setIncludeFile : function(task_property_values, task_html_elm) {
			var file_path = task_property_values["include_file_path"];
			var file_path_type = task_property_values["include_file_path_type"];
			var once = task_property_values["include_once"];
			
			if (file_path) {
				var include_file_elm = task_html_elm.find(".include_file");
				
				include_file_elm.find("input[type=text]").val(file_path);
				include_file_elm.find("select").val(file_path_type);
				
				if (once)
					include_file_elm.find("input[type=checkbox]").prop("checked", true).attr("checked", "");
				else
					include_file_elm.find("input[type=checkbox]").prop("checked", false).removeAttr("checked");
			}
		},
		
		getVariableAssignmentOperator : function(assignment) {
			return assignment == "concat" || assignment == "concatenate" ? ".=" : (assignment == "increment" ? "+=" : (assignment == "decrement" ? "-=" : "="));
		},
		
		getValueString : function(value, type) {
			if (typeof value == "undefined" || value == null) {
				return type == "string" || type == "date" ? "''" : (!type ? "null" : "");
			}
			
			value = "" + value + "";
			value = value.trim();
			value = type == "variable" ? (value.substr(0, 1) != '$' ? '$' : '') + value : (type == "string" || type == "date" ? "'" + value.replace(/'/g, "\\'") + "'" : (!type && value.trim().length == 0 ? "null" : value));
			
			return value;
		},
		
		getResultVariableString : function(task_property_values) {
			var result_var_name = task_property_values["result_var_name"];
			var result_var_assignment = task_property_values["result_var_assignment"];
			var result_obj_name = task_property_values["result_obj_name"];
			var result_prop_name = task_property_values["result_prop_name"];
			var result_static = task_property_values["result_static"];
			var result_prop_assignment = task_property_values["result_prop_assignment"];
			var result_echo = task_property_values["result_echo"];
			var result_return = task_property_values["result_return"];
			
			if (result_var_name) {
				return result_var_name ? this.getValueString(result_var_name, "variable") + " " + this.getVariableAssignmentOperator(result_var_assignment) + " " : "";
			}
			else if (result_obj_name && result_prop_name) {
				if (result_static == 1) {
					return result_obj_name.trim() + "::" + this.getValueString(result_prop_name, "variable") + " " + this.getVariableAssignmentOperator(result_prop_assignment) + " ";
				}
				else {
					return this.getValueString(result_obj_name, "variable") + "->" + result_prop_name + " " + this.getVariableAssignmentOperator(result_prop_assignment) + " ";
				}
			}
			else if (result_echo) {
				return "echo ";
			}
			else if (result_return) {
				return "return ";
			}
			
			return "";
		},
	
		getArgsString : function(args) {
			if (args) {
				if (args["value"] || args["type"] || args["name"]) {
					args = [args];
				}
				
				var str = "";
				var c = 0;
				for (var i in args) {
					var arg = args[i];
					
					var type = arg["type"] ? arg["type"] : "";
					var value = this.getValueString(arg["value"], type);
					
					str += (c > 0 ? ", " : "") + value;
					c++;
				}
				
				return str;
			}
			return "";
		},
	
		setArgs : function(args, args_html_elm) {
			if (args_html_elm[0]) {
				var class_name = args_html_elm.parent()[0].className;
				class_name = class_name ? class_name.split(" ") : ["args"];
				class_name = class_name[0];
				
				if (args && (args.hasOwnProperty("value") || args.hasOwnProperty("type") || args.hasOwnProperty("name"))) {
					args = [args];
				}
				
				var html = '';
				var count = 0;
				
				if (args) {
					for (var i in args) {
						var arg = args[i];
						
						html += this.getTableArg(class_name, arg["name"], arg["value"], arg["type"], count);
						++count;
					}
				}
				else {
					html += '<tr class="table_arg_empty">' + 
							'<td class="table_arg_name"></td>' + 
							'<td colspan="3">There are no arguments defined...</td>' +
						'<tr>';
				}
				
				html = '<table count="' + count + '">' +
						'<tr>' +
							'<th class="table_arg_name"></th>' +
							'<th class="table_arg_value table_header">Value</th>' +
							'<th class="table_arg_type table_header">Type</th>' +
							'<th class="table_arg_remove table_header">' +
								'<a class="icon add" onclick="ProgrammingTaskUtil.addTableArg(this, \'' + class_name + '\')">add</a>' +
							'</th>' +
						'</tr>' +
						html +
						'</table>';
			
				args_html_elm.html(html);
				
				var table = args_html_elm.children("table");
				var w = table.width();
				if (w > 0)
					args_html_elm.css("width", w + "px");
				
				ProgrammingTaskUtil.onProgrammingTaskPropertiesNewHtml(table);
			}
		},
		
		addTableArg : function(elm, class_name) {
			var table = $(elm).parent().parent().parent().parent();
			
			var count = table.attr("count");
			count = count ? ++count : 0;
			table.attr("count", count);
			
			var html = this.getTableArg(class_name, "", "", "string", count);
			var new_item = $(html);
			
			table.append(new_item);
			table.find(".table_arg_empty").remove();
			
			ProgrammingTaskUtil.onProgrammingTaskPropertiesNewHtml(new_item);
		},
		
		getTableArg : function(class_name, name, value, type, count) {
			name = name ? name : "";
			value = typeof value != "undefined" || value != null ? "" + value + "" : "";
			type = type ? type : "";
			
			if (type == "variable" && typeof value == "string" && value[0] == '$')
				value = value.substr(1);
			
			var n = name ? "$" + name + ":" : "";
			
			return '<tr>' +
				'<td class="table_arg_name">' +
					'<input type="hidden" class="task_property_field" name="' + class_name + '[' + count + '][name]" value="' + name.replace(/"/g, "&quot;") + '" />' + 
					'<div>' + n + '</div>' + 
				'</td>' +
				'<td class="table_arg_value">' + 
					'<input type="text" class="task_property_field" name="' + class_name + '[' + count + '][value]" value="' + value.replace(/"/g, "&quot;") + '" />' + 
					'<span class="icon add_variable inline" onClick="ProgrammingTaskUtil.onProgrammingTaskChooseCreatedVariable(this)">Add Variable</span>' +
				'</td>' +
				'<td class="table_arg_type">' +
					'<select class="task_property_field" name="' + class_name + '[' + count + '][type]">' +
						'<option' + (type == "string" ? " selected" : "") + '>string</option>' +
						'<option' + (type == "variable" ? " selected" : "") + '>variable</option>' +
						'<option value=""' + (value && type != "string" && type != "variable" ? " selected" : "") + '>code</option>' +
					'</select>' +
				'</td>' +
				'<td class="table_arg_remove table_header">' +
					'<a class="icon remove" onclick="$(this).parent().parent().remove()">remove</a>' +
				'</td>' +
			'</tr>';
		},
		
		updateTaskDefaultExitLabel : function(task_id, label) {
			var labels = {"default_exit": label};
			this.updateTaskExitsLabels(task_id, labels);
		},
		
		updateTaskExitsLabels : function(task_id, labels) {
			var WF = myWFObj.getTaskFlowChart();
			var task = WF.TaskFlow.getTaskById(task_id);
			var exits = task.find(" > ." + WF.TaskFlow.task_eps_class_name + " ." + WF.TaskFlow.task_ep_class_name);
			
			var exit, connection_exit_id, span, bg, title;
			
			for (var i = 0; i < exits.length; i++) {
				exit = $(exits[i]);
				
				connection_exit_id = exit.attr("connection_exit_id");
				
				if (connection_exit_id && labels.hasOwnProperty(connection_exit_id)) {
					if (labels[connection_exit_id]) {
						span = $('<span>' + labels[connection_exit_id].replace(/</g, "&lt;") + '</span>');
						
						//setting text color according with background
						bg = exit.css("background-color");
						if (bg) {
							if (bg.indexOf("rgb") != -1)
								bg = colorRgbToHex(bg);
							
							span.css("color", bg && getContrastYIQ(bg) == "white" ? "#FFF" : "#000");
						}
						
						title = labels[connection_exit_id];
						
						exit.html(span);
						exit.attr("title", title);
					}
					else {
						exit.html("");
						exit.attr("title", "");
					}
				}
			}
			
			var height = 28 + (exits.length * 25);
			var is_resizable_task = task.attr("is_resizable_task");
			var resize_height = is_resizable_task ? height > task.height() : height != task.height();
			
			if (resize_height) {
				task.css("height", height + "px");
			
				WF.TaskFlow.repaintTask(task);
			}
		},
		
		updateTaskExitsConnectionExitLabelAttribute : function(task_id, labels) {
			var WF = myWFObj.getTaskFlowChart();
			var task = WF.TaskFlow.getTaskById(task_id);
			var exits = task.find(" > ." + WF.TaskFlow.task_eps_class_name + " ." + WF.TaskFlow.task_ep_class_name);
			
			var exit, connection_exit_id;
			
			for (var i = 0; i < exits.length; i++) {
				exit = $(exits[i]);
				
				connection_exit_id = exit.attr("connection_exit_id");
				
				if (connection_exit_id && labels.hasOwnProperty(connection_exit_id)) {
					if (labels[connection_exit_id])
						exit.attr("connection_exit_label", labels[connection_exit_id]).attr("title", labels[connection_exit_id]);
					else
						exit.attr("connection_exit_label", "").attr("title", "");
				}
			}
		},
		
		updateTaskExitsConnectionsLabels : function(task_id, labels) {
			//update exits that were changed
			var WF = myWFObj.getTaskFlowChart();
			var child_connections = WF.TaskFlow.getSourceConnections(task_id);
			var cl = child_connections.length;
			
			for (var i = 0; i < cl; i++) {
				var child_connection = child_connections[i];
				var parameters = child_connection.getParameters();
				var exit_id = parameters["connection_exit_id"];
				
				if (exit_id && labels.hasOwnProperty(exit_id))
					child_connection.getOverlay("label").setLabel(labels[exit_id]);
			}
		},
		
		onChangeTaskFieldType : function(elm) {
			elm = $(elm);
			var p = elm.parent();
			
			if (p.children("input[type=text]").css("display") != "none")
				p.children(".add_variable").css("display", "inline"); //do not use show() otherwise the display will be block and UI will be weired.
			else
				p.children(".add_variable").hide();
		},
		
		onBeforeTaskDeletion : function(task_id, task) {
			this.connections_to_add_after_deletion = [];
			this.new_start_task_id = null;
			this.new_start_task_order = null;
			
			var WF = myWFObj.getTaskFlowChart();
			var child_connections = WF.TaskFlow.getSourceConnections(task_id);
			var cl = child_connections.length;
			var target_id = cl > 0 && child_connections[0] ? child_connections[0].targetId : null;
			
			if (target_id) {
				//prepare new start task
				var start_task_order = task.attr("is_start_task");
				
				if (start_task_order > 0) {
					this.new_start_task_id = target_id;
					this.new_start_task_order = start_task_order;
				}
				
				//prepare parent connections
				var parent_connections = WF.TaskFlow.getTargetConnections(task_id);
				var pl = parent_connections.length;
				
				if (pl > 0)
					for (var i = 0; i < pl; i++) {
						var parent_connection = parent_connections[i];
						var source_id = parent_connection.sourceId;
						
						if (source_id) {
							var parameters = parent_connection.getParameters();
							var connector_type = parameters.connection_exit_type;
							var connection_overlay = parameters.connection_exit_overlay;
							var connection_label = parent_connection.getOverlay("label").getLabel();
							var connection_color = parameters.connection_exit_color;
							
							if (!connection_color) {
								connection_color = parent_connection.endpoints[0].element[0].getAttribute("connection_exit_color");
		    						connection_color = connection_color ? connection_color : parent_connection.getPaintStyle().strokeStyle;
							}
							
							var connection_exit_props = {
								id: parameters.connection_exit_id, 
								color: connection_color
							};
						
							this.connections_to_add_after_deletion.push([source_id, target_id, connection_label, connector_type, connection_overlay, connection_exit_props]);
						}
					}
			}
			
			return true;
		},
		
		onAfterTaskDeletion : function(task_id, task) {
			var WF = myWFObj.getTaskFlowChart();
			
			//prepare new start task
			if (this.new_start_task_id) {
				var new_task = WF.TaskFlow.getTaskById(this.new_start_task_id);
				new_task.attr("is_start_task", this.new_start_task_order).addClass("is_start_task");
			}
			
			//prepare new connections
			if ($.isArray(this.connections_to_add_after_deletion) && this.connections_to_add_after_deletion.length > 0) {
				for (var i = 0; i < this.connections_to_add_after_deletion.length; i++) {
					var c = this.connections_to_add_after_deletion[i];
					var source_task_id = c[0];
					var target_task_id = c[1];
					var connection_label = c[2];
					var connector_type = c[3];
					var connection_overlay = c[4];
					var connection_exit_props = c[5];
					
					WF.TaskFlow.connect(source_task_id, target_task_id, connection_label, connector_type, connection_overlay, connection_exit_props);
				}
			}
			
			return true;
		},
		
		getTaskTagFromTaskPropertiesHtmlElementClass : function(task_html_elm) {
			//get task type based in properties parent class
			var classes = task_html_elm[0].hasAttribute("class") ? task_html_elm.attr("class").split(" ") : [];
			var task_type = null;
			
			for (var i = 0, t = classes.length; i < t; i++) {
				var c = classes[i];
				
				if (c.indexOf("_task_html") != -1) {
					task_type = c.substr(0, c.length - "_task_html".length).replace(/_/g, "");
					break;
				}
			}
			
			return task_type;
		},
		
		onEditIncludeFile : function(elm) {
			var task_html_elm = $(elm).closest(".include_file").parent();
			ProgrammingTaskUtil.onEditSource(elm, task_html_elm, "file");
		},
		
		onEditSource : function(elm, task_html_elm, type) {
			data = {};
			var WF = myWFObj.getTaskFlowChart();
			var query_string = WF.Property.getPropertiesQueryStringFromHtmlElm(task_html_elm, "task_property_field");
			
			try {
				parse_str(query_string, data);
			}
			catch(e) {}
			
			if (!$.isEmptyObject(data)) {
				data["task_tag"] = ProgrammingTaskUtil.getTaskTagFromTaskPropertiesHtmlElementClass(task_html_elm);
				data["edit_type"] = type ? type : "file";
				
				ProgrammingTaskUtil.onProgrammingTaskEditSource(elm, data);
			}
			else
				WF.StatusMessage.showError("Cannot edit file");
		},
		
		onProgrammingTaskEditSource : function(elm, data) {
			//Do not use "this.", but "ProgrammingTaskUtil." instead, bc if we assign this function to a variable (var x = ProgrammingTaskUtil.onProgrammingTaskChooseImageUrl), the "this." will not work.
			if (typeof ProgrammingTaskUtil.on_programming_task_edit_source_callback == "function") {
				ProgrammingTaskUtil.on_programming_task_edit_source_callback(elm, data);
			}
		},
		
		onProgrammingTaskChooseCreatedVariable : function(elm) {
			//Do not use "this.", but "ProgrammingTaskUtil." instead, bc if we assign this function to a variable (var x = ProgrammingTaskUtil.onProgrammingTaskChooseImageUrl), the "this." will not work.
			if (typeof ProgrammingTaskUtil.on_programming_task_choose_created_variable_callback == "function") {
				ProgrammingTaskUtil.on_programming_task_choose_created_variable_callback(elm);
			}
		},
	
		onProgrammingTaskChooseObjectProperty : function(elm) {
			//Do not use "this.", but "ProgrammingTaskUtil." instead, bc if we assign this function to a variable (var x = ProgrammingTaskUtil.onProgrammingTaskChooseImageUrl), the "this." will not work.
			if (typeof ProgrammingTaskUtil.on_programming_task_choose_object_property_callback == "function") {
				ProgrammingTaskUtil.on_programming_task_choose_object_property_callback(elm);
			}
		},
	
		onProgrammingTaskChooseObjectMethod : function(elm) {
			//Do not use "this.", but "ProgrammingTaskUtil." instead, bc if we assign this function to a variable (var x = ProgrammingTaskUtil.onProgrammingTaskChooseImageUrl), the "this." will not work.
			if (typeof ProgrammingTaskUtil.on_programming_task_choose_object_method_callback == "function") {
				ProgrammingTaskUtil.on_programming_task_choose_object_method_callback(elm);
			}
		},
	
		onProgrammingTaskChooseFunction : function(elm) {
			//Do not use "this.", but "ProgrammingTaskUtil." instead, bc if we assign this function to a variable (var x = ProgrammingTaskUtil.onProgrammingTaskChooseImageUrl), the "this." will not work.
			if (typeof ProgrammingTaskUtil.on_programming_task_choose_function_callback == "function") {
				ProgrammingTaskUtil.on_programming_task_choose_function_callback(elm);
			}
		},
		
		onProgrammingTaskChooseClassName : function(elm) {
			//Do not use "this.", but "ProgrammingTaskUtil." instead, bc if we assign this function to a variable (var x = ProgrammingTaskUtil.onProgrammingTaskChooseImageUrl), the "this." will not work.
			if (typeof ProgrammingTaskUtil.on_programming_task_choose_class_name_callback == "function") {
				ProgrammingTaskUtil.on_programming_task_choose_class_name_callback(elm);
			}
		},
		
		onProgrammingTaskChooseFilePath : function(elm) {
			//Do not use "this.", but "ProgrammingTaskUtil." instead, bc if we assign this function to a variable (var x = ProgrammingTaskUtil.onProgrammingTaskChooseImageUrl), the "this." will not work.
			if (typeof ProgrammingTaskUtil.on_programming_task_choose_file_path_callback == "function") {
				ProgrammingTaskUtil.on_programming_task_choose_file_path_callback(elm);
			}
		},
		
		onProgrammingTaskChooseFolderPath : function(elm) {
			//Do not use "this.", but "ProgrammingTaskUtil." instead, bc if we assign this function to a variable (var x = ProgrammingTaskUtil.onProgrammingTaskChooseImageUrl), the "this." will not work.
			if (typeof ProgrammingTaskUtil.on_programming_task_choose_folder_path_callback == "function") {
				ProgrammingTaskUtil.on_programming_task_choose_folder_path_callback(elm);
			}
		},
		
		onProgrammingTaskChoosePageUrl : function(elm) {
			//Do not use "this.", but "ProgrammingTaskUtil." instead, bc if we assign this function to a variable (var x = ProgrammingTaskUtil.onProgrammingTaskChooseImageUrl), the "this." will not work.
			if (typeof ProgrammingTaskUtil.on_programming_task_choose_page_url_callback == "function") {
				ProgrammingTaskUtil.on_programming_task_choose_page_url_callback(elm);
			}
		},
		
		onProgrammingTaskChooseImageUrl : function(elm) {
			//Do not use "this.", but "ProgrammingTaskUtil." instead, bc if we assign this function to a variable (var x = ProgrammingTaskUtil.onProgrammingTaskChooseImageUrl), the "this." will not work.
			if (typeof ProgrammingTaskUtil.on_programming_task_choose_image_url_callback == "function") {
				ProgrammingTaskUtil.on_programming_task_choose_image_url_callback(elm);
			}
		},
		
		onProgrammingTaskChooseWebrootFileUrl : function(elm) {
			//Do not use "this.", but "ProgrammingTaskUtil." instead, bc if we assign this function to a variable (var x = ProgrammingTaskUtil.onProgrammingTaskChooseImageUrl), the "this." will not work.
			if (typeof ProgrammingTaskUtil.on_programming_task_choose_webroot_file_url_callback == "function") {
				ProgrammingTaskUtil.on_programming_task_choose_webroot_file_url_callback(elm);
			}
		},
		
		onProgrammingTaskPropertiesNewHtml : function(elm) {
			//Do not use "this.", but "ProgrammingTaskUtil." instead, bc if we assign this function to a variable (var x = ProgrammingTaskUtil.onProgrammingTaskPropertiesNewHtml), the "this." will not work.
			if (typeof ProgrammingTaskUtil.on_programming_task_properties_new_html_callback == "function") {
				ProgrammingTaskUtil.on_programming_task_properties_new_html_callback(elm);
			}
		},
	};
}
