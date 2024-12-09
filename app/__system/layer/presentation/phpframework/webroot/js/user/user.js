var saved_form_obj_id = null;

$(function () {
	if (isEditFormPage()) {
		$(window).bind('beforeunload', function () {
			if (isFormChanged()) {
				if (window.parent && window.parent.iframe_overlay)
					window.parent.iframe_overlay.hide();
				
				return "If you proceed your changes won't be saved. Do you wish to continue?";
			}
			
			return null;
		});
		
		//set saved_record_obj_id
		saved_form_obj_id = getFormObjId();
	}
});

function isEditFormPage() {
	return $("#content > div > form").length > 0;
}

function getFormObjId() {
	var obj = $("#content > div > form").serialize();
	
	return $.md5(obj);
}

function isFormChanged() {
	var new_form_obj_id = getFormObjId();
	
	return saved_form_obj_id != new_form_obj_id;
}

function submitForm(elm, input_class) {
	$(window).unbind('beforeunload');
	
	//add loading to save button
	var top_bar = $(elm).parent().closest(".top_bar");
	var save_btn = top_bar.find(".save");
	
	save_btn.addClass("loading");
	
	//submit form
	var f = top_bar.parent().find("form");
	var submit_btn = f.find(".buttons .submit_button input" + (input_class ? "." + input_class : "") + "");
	submit_btn.trigger("click");
	
	//check if there were any form validation errors
	var on_submit = f.attr("onSubmit");
	if (on_submit && on_submit.indexOf("MyJSLib.FormHandler.formCheck") && typeof MyJSLib != 'undefined') {
		var elements = MyJSLib.FormHandler.getFormElements(f[0]);
		var attrs = MyJSLib.FormHandler.getFormElementsChecks(elements);
		
		if (attrs.errors > 0)
			save_btn.removeClass("loading");
	}
}

function toggleAllPermissions(elm, class_name) {
	elm = $(elm);
	var table = elm.parent().closest("table");
	var is_checked = elm.is(":checked");
	
	table.find("td." + class_name + " input[type=checkbox]").each(function(idx, sub_elm) {
		sub_elm = $(sub_elm);
		
		if (is_checked) 
			sub_elm.attr("checked", "checked").prop("checked", true);
		else 
			sub_elm.removeAttr("checked").prop("checked", false);
		
		if (sub_elm.is(":disabled")) //used by the view/user/manage_user_type_permissions.php in the second table, this is, in the layers table.
			sub_elm.parent().children(".toggle").click();
	});
}

function saveUserTypePermissions() {
	var select = $(".user_type select")[0];
	var opt = select.options[select.selectedIndex];
	
	if (confirm('Do you wish to save these permissions for the user type: "' + $(opt).text() + '"?')) {
		var inputs = $(".user_type_permissions_list table .user_type_permission input[type='checkbox']");
		
		$.each(inputs, function(idx, input) {
			input = $(input);
			
			if (!input.is(":checked") && input[0].hasAttribute("default_value") && input.attr("default_value").length > 0) {
				input[0].type = "test";
				input.val( input.attr("default_value") );
				input.hide();
				input.parent().removeClass("new").children(".toggle").hide();
			}
		});
		
		return true;
	}
	return false;
}

function updateUserTypePermissions(elm) {
	MyFancyPopup.init({
		parentElement: window,
	});
	MyFancyPopup.showOverlay();
	MyFancyPopup.showLoading();
	
	var user_type_id = $(elm).val();
	
	if (user_type_id) {
		$.ajax({
			type : "get",
			url : get_user_type_permissions_url.replace("#user_type_id#", user_type_id),
			dataType : "json",
			success : function(items, textStatus, jqXHR) {
				if (items) {
					var user_type_permissions = {};
						
					for (var i = 0; i < items.length; i++) {
						var item = items[i];
						var object_type_id = item["object_type_id"];
						var object_id = item["object_id"];
						var permission_id = item["permission_id"];
						
						if (object_type_id && object_id && permission_id) {
							if (!user_type_permissions.hasOwnProperty(object_type_id)) {
								user_type_permissions[object_type_id] = {}
							}
						
							if (!user_type_permissions[object_type_id].hasOwnProperty(object_id)) {
								user_type_permissions[object_type_id][object_id] = {}
							}
						
							user_type_permissions[object_type_id][object_id][permission_id] = 1;
						}
					}
					
					$(".user_type_permissions_list table").each(function(idx, table) {
						var object_type_id = table.getAttribute("object_type_id");
						
						if ($.isNumeric(object_type_id))
							$(table).find("td.object_id").each(function(idx, elm) {
								elm = $(elm);
								var object_id = elm[0].hasAttribute("object_id") ? elm.attr("object_id") : elm.html();
								var user_type_permission_elms = elm.parent().children(".user_type_permission");
								
								if (user_type_permissions.hasOwnProperty(object_type_id) && user_type_permissions[object_type_id].hasOwnProperty(object_id)) {
									user_type_permission_elms.each(function(idx, sub_elm) {
										sub_elm = $(sub_elm);
										var permission_id = sub_elm.attr("permission_id");
										var input = sub_elm.children("input");
										input.removeAttr("disabled");
										sub_elm.children(".toggle").addClass("active");
										
										if (user_type_permissions[object_type_id][object_id].hasOwnProperty(permission_id))
											input.attr("checked", "checked").prop("checked", true);
										else
											input.removeAttr("checked").prop("checked", false);
										
										sub_elm.removeClass("new");
									});
								}
								else 
									user_type_permission_elms.find("input").each(function(idx, input) {
										input = $(input);
										input.removeAttr("checked").prop("checked", false);
										
										if (input[0].hasAttribute("default_value")) {
											var p = input.parent();
											
											input.attr("disabled", "disabled");
											p.children(".toggle").removeClass("active");
											p.addClass("new");
										}
									});
							});
					});
				}
				else
					$(".user_type_permissions_list table td.user_type_permission input").removeAttr("checked").prop("checked", false).parent().removeClass("new");
				
				//update saved_form_obj_id with new loaded items
				saved_form_obj_id = getFormObjId();
				
				MyFancyPopup.hidePopup();
			},
			error : function(jqXHR, textStatus, errorThrown) {
				MyFancyPopup.hidePopup();
				
				if (jqXHR.responseText)
					StatusMessageHandler.showError(jqXHR.responseText);
			},
		});
	}
	else {
		alert("Error: user_type_id undefined!");
		MyFancyPopup.hidePopup();
	}
}

function toggleLayerPermissionVisibility(elm) {
	elm = $(elm);
	var input = elm.parent().children("input");
	
	if (input[0].hasAttribute("disabled")) {
		input.removeAttr("disabled");
		elm.addClass("active");
	}
	else {
		input.attr("disabled", "disabled");
		elm.removeClass("active");
	}
}

function onChangeLocalDBSettings(elm) {
	elm = $(elm);
	var p = elm.parent().parent();
	
	if (elm.val() == 1)
		p.children(".form_field_db").hide().addClass("hidden");
	else
		p.children(".form_field_db").show().removeClass("hidden");
	
	if (p.find(".is_local_db select").val() != 1)
		onChangeDBType( p.find(".db_type select")[0] )
}

function saveLayoutTypePermissions() {
	var select = $(".layout_type select[name=layout_type_id]");
	var opt = select.find("option:selected");
	
	if (confirm('Do you wish to save these permissions for the layout type: "' + opt.text() + '"?')) {
		var layer = opt.attr("layer");
		select.parent().find("input").val(layer);
		
		return true;
	}
	return false;
}

function onChangeLayoutType(elm) {
	var type_id = $(elm).val();
	var url = ("" + document.location);
	url = url.replace(/(&?)type_id=[^&]*/g, "");
	url += "&type_id=" + type_id;
	url = url.replace(/[&]+/g, "&");
	
	document.location=url;
}

function updateLayoutTypePermissions(elm) {
	elm = $(elm);
	var layout_type_id = elm.val();
	updateLayoutTypePermissionsById(layout_type_id);
}
	
function updateLayoutTypePermissionsById(layout_type_id) {
	if (layout_type_id) {
		MyFancyPopup.init({
			parentElement: window,
		});
		MyFancyPopup.showOverlay();
		MyFancyPopup.showLoading();
		
		var main_elm = $(".layout_type_permissions_content");
		main_elm.find("input[type=checkbox]").removeAttr("checked").prop("checked", false).removeAttr("disabled");
		main_elm.find(".jstree-clicked").removeClass("jstree-clicked");
		
		var loaded_permissions_by_objects = main_elm.children(".loaded_permissions_by_objects");
		loaded_permissions_by_objects.html("");
		
		loaded_layout_type_permissions = {};
		
		$.ajax({
			type : "get",
			url : get_layout_type_permissions_url.replace("#layout_type_id#", layout_type_id),
			dataType : "json",
			success : function(items, textStatus, jqXHR) {
				if (items) {
					for (var i = 0; i < items.length; i++) {
						var item = items[i];
						var object_type_id = item["object_type_id"];
						var object_id = item["object_id"];
						var permission_id = item["permission_id"];
						
						if (object_type_id && object_id && permission_id) {
							if (!loaded_layout_type_permissions.hasOwnProperty(object_type_id))
								loaded_layout_type_permissions[object_type_id] = {}
						
							if (!loaded_layout_type_permissions[object_type_id].hasOwnProperty(object_id))
								loaded_layout_type_permissions[object_type_id][object_id] = {}
						
							loaded_layout_type_permissions[object_type_id][object_id][permission_id] = 1;
							
							loaded_permissions_by_objects.append('<input class="permissions_by_objects_' + object_type_id + '_' + $.md5(object_id) + '_' + permission_id + '" type="hidden" name="permissions_by_objects[' + object_type_id + '][' + object_id + '][]" value="' + permission_id + '" />');
						}
					}
					
					loadLayoutTypePermissions(main_elm);
				}
				
				//update saved_form_obj_id with new loaded items
				saved_form_obj_id = getFormObjId();
				
				MyFancyPopup.hidePopup();
			},
			error : function(jqXHR, textStatus, errorThrown) {
				MyFancyPopup.hidePopup();
				
				if (jqXHR.responseText)
					StatusMessageHandler.showError(jqXHR.responseText);
			},
		});
	}
	else
		alert("Error: layout_type_id undefined!");
}

function loadLayoutTypePermissions(main_elm) {
	var loaded_permissions_by_objects = $(".layout_type_permissions_content .loaded_permissions_by_objects").first();
	
	if (loaded_layout_type_permissions)
		main_elm.find("input[type=checkbox]").each(function(idx, input) {
			input = $(input);
			var name = input.attr("name");
			var permission_id = input.val();
			
			var m = name.match(/^permissions_by_objects\[([0-9])\]\[(.*)\]\[\]/);
			var object_type_id = m[1];
			var object_id = m[2];
			
			var exists = loaded_layout_type_permissions.hasOwnProperty(object_type_id) && 
						loaded_layout_type_permissions[object_type_id].hasOwnProperty(object_id) && 
						loaded_layout_type_permissions[object_type_id][object_id].hasOwnProperty(permission_id);
			
			if (exists) {
				input.attr("checked", "checked").prop("checked", true);
				
				var a = input.parent().parent("a");
				a.addClass("jstree-clicked");
			}
			else
				input.removeAttr("checked").prop("checked", false);
			
			loaded_permissions_by_objects.find('.permissions_by_objects_' + object_type_id + '_' + $.md5(object_id) + '_' + permission_id).remove();
			
			prepareChildrenFileTreeCheckbox(input);
		});
}

function removeAllThatIsFolderFromTree(ul, data) {
	ul = $(ul);
	
	//add checkbox to all folders
	ul.find("a > i.jstree-icon").each(function(idx, elm){
		elm = $(elm);
		var a = elm.parent();
		var li = a.parent();
		
		if (elm.is(".folder, .cms_common, .cms_module, .cms_program, .cms_resource, .project, .project_common, .project_folder")) {
			addTreeItemCheckbox(li, data, permissions[permission_belong_name], li.parent().attr("object_id_prefix"));
			
			if (elm.is(".project, .project_common"))
				li.addClass("jstree-leaf");
		}
		else {
			if (li.next().length == 0)
				li.prev().addClass("jstree-last");
			
			li.remove();
		}
	});
}
	
function removeAllThatCannotBeReferencedFromTree(ul, data) {
	ul = $(ul);
	
	//remove all that are not files
	ul.find("i.function, i.reserved_file").each(function(idx, elm){
		var li = $(elm).parent().parent();
		
		if (li.next().length == 0)
			li.prev().addClass("jstree-last");
		
		li.remove();
	});
	
	//add checkbox to all files
	ul.find("i.file, i.objtype, i.hibernatemodel, i.service, i.class, i.test_unit_obj, i.config_file, i.entity_file, i.view_file, i.template_file, i.block_file, i.util_file, i.controller_file").each(function(idx, elm) {
		elm = $(elm);
		var a = elm.parent();
		var li = a.parent();
		
		li.removeClass("jstree-close disabled").addClass("jstree-leaf");
		li.children("ul").remove();
		
		addTreeItemCheckbox(li, data, permissions[permission_referenced_name], li.parent().attr("object_id_prefix"));
	});
	
	//add checkbox to all webroot files
	ul.find("li.jstree-node").each(function(idx, li) {
		li = $(li);
		var a = li.children("a");
		var file_path = a.attr("file_path");
		
		if (file_path && file_path.indexOf("/webroot/") != -1)
			addTreeItemCheckbox(li, data, permissions[permission_referenced_name], li.parent().attr("object_id_prefix"));
	});
	
	//add checkbox to all folders
	ul.find("i.folder, i.cms_common, i.cms_module, .module_folder, i.cms_program, i.cms_resource, .project, .project_common, .project_folder, .configs_folder, .entities_folder, .views_folder, .templates_folder, .template_folder, .blocks_folder, .utils_folder, .webroot_folder, .controllers_folder, .caches_folder").each(function(idx, elm){
		var li = $(elm).parent().parent();
		addTreeItemCheckbox(li, data, permissions[permission_referenced_name], li.parent().attr("object_id_prefix"));
	});
}

function addTreeItemCheckbox(li, data, permission_id, object_id_prefix) {
	var a = li.children("a");
	var properties_id = a.attr("properties_id");
	var object_id = "";
	var item_data = null;
	
	var parent_li = li.parent().parent();
	
	if (parent_li.find(" > a > i").is(".properties")) //.properties is the others folder from each project
		data = data["others"];
	
	if (object_id_prefix && properties_id)
		for (var k in data)
			if ($.isPlainObject(data[k]) && $.isPlainObject(data[k]["properties"]) && data[k]["properties"]["item_id"] == properties_id) {
				var path = data[k]["properties"]["path"];
				path = path.replace(/\/+$/g, ""); //remove last slash
				
				if (path) {
					object_id = object_id_prefix + "/" + path;
					item_data = data[k];
				}
				break;
			}
	
	if (object_id) {
		var disabled = isParentTreeNodeChecked(li) ? " disabled" : "";
		var input = $('<input type="checkbox" name="permissions_by_objects[' + layer_object_type_id + '][' + object_id + '][]" value="' + permission_id + '"' + disabled + ' />');
		var ul = li.children("ul");
		
		prepareFileTreeCheckbox(input);
		
		a.children("label").append(input);
		ul.attr("object_id_prefix", object_id_prefix);
		
		loadLayoutTypePermissions(li);
		
		//prepare inner lis, like it will happen with the item: "project"
		var has_childs = false;
		for (var item_key in item_data) {
			if (item_key != "properties") {
				has_childs = true;
				break;
			}
		}
		
		if (has_childs)
			ul.find("li").each(function(idx, sub_li) {
				addTreeItemCheckbox($(sub_li), item_data, permission_id, object_id_prefix);
			});
	}
}

function prepareFileTreeCheckbox(input) {
	input.click(function(e) {
		e.stopPropagation();
		
		var a = $(this).parent().closest("a");
		
		if ($(this).is(":checked"))
			a.addClass("jstree-clicked");
		else
			a.removeClass("jstree-clicked");
		
		prepareChildrenFileTreeCheckbox(this);
	});
}

function toggleFileTreeCheckbox(node) {
	var a = $(node).children("a");
	var input = a.children("label").children("input");
	
	if (a.hasClass("jstree-clicked"))
		input.removeAttr("checked").prop("checked", false);
	else
		input.attr("checked", "checked").prop("checked", true);
	
	prepareChildrenFileTreeCheckbox(input[0]);
	
	return true;
}

function prepareChildrenFileTreeCheckbox(input) {
	input = $(input);
	var is_checked = input.is(":checked");
	var loaded_permissions_by_objects = $(".layout_type_permissions_content .loaded_permissions_by_objects").first();
	
	//remove all hidden inputs based in parent prefix
	if (is_checked) {
		var name = input.attr("name");
		var permission_id = input.val();
		
		var m = name.match(/^permissions_by_objects\[([0-9])\]\[(.*)\]\[\]/);
		var object_type_id = m[1];
		var object_id = m[2];
		
		loaded_permissions_by_objects.find("input").each(function(idx, sub_input) {
			sub_input = $(sub_input);
			var name = sub_input.attr("name");
			var sub_permission_id = sub_input.val();
			
			var m = name.match(/^permissions_by_objects\[([0-9])\]\[(.*)\]\[\]/);
			var sub_object_type_id = m[1];
			var sub_object_id = m[2];
			
			if (permission_id == sub_permission_id && object_type_id == sub_object_type_id && sub_object_id.indexOf(object_id) === 0)
				uncheckLoadedTreeNode(loaded_permissions_by_objects, sub_object_type_id, sub_object_id, sub_permission_id);
		});
	}
	
	input.parent().closest("li").children("ul").find("input").each(function(idx, sub_input) {
		sub_input = $(sub_input);
		var a = sub_input.parent().closest("a");
		
		if (is_checked) {
			sub_input.removeAttr("checked").prop("checked", false).attr("disabled", "disabled");
			a.removeClass("jstree-clicked");
			
			//remove hidden input
			var name = sub_input.attr("name");
			var permission_id = sub_input.val();
			
			var m = name.match(/^permissions_by_objects\[([0-9])\]\[(.*)\]\[\]/);
			var object_type_id = m[1];
			var object_id = m[2];
			
			uncheckLoadedTreeNode(loaded_permissions_by_objects, object_type_id, object_id, permission_id);
		}
		else
			sub_input.removeAttr("disabled");
	});
}

function uncheckLoadedTreeNode(loaded_permissions_by_objects, object_type_id, object_id, permission_id) {
	loaded_permissions_by_objects.find('.permissions_by_objects_' + object_type_id + '_' + $.md5(object_id) + '_' + permission_id).remove();
	
	var exists = loaded_layout_type_permissions.hasOwnProperty(object_type_id) && 
			loaded_layout_type_permissions[object_type_id].hasOwnProperty(object_id) && 
			loaded_layout_type_permissions[object_type_id][object_id].hasOwnProperty(permission_id);

	if (exists)
		delete loaded_layout_type_permissions[object_type_id][object_id][permission_id];
}

function isParentTreeNodeChecked(li) {
	var p = li.parent().parent();
	
	if (p.find(" > a > label > input").is(":checked"))
		return true;
	else if (p.is(".jstree-node"))
		return isParentTreeNodeChecked(p);
	
	return false;
}
