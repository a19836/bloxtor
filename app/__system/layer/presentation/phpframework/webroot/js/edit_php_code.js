var choosePropertyVariableFromFileManagerTree = null;
var chooseMethodFromFileManagerTree = null;
var chooseFunctionFromFileManagerTree = null;
var chooseFileFromFileManagerTree = null;
var chooseFolderFromFileManagerTree = null;
var chooseBusinessLogicFromFileManagerTree = null;
var chooseQueryFromFileManagerTree = null;
var chooseHibernateObjectFromFileManagerTree = null;
var chooseHibernateObjectMethodFromFileManagerTree = null;
var choosePresentationFromFileManagerTree = null;
var chooseBlockFromFileManagerTree = null;
var chooseViewFromFileManagerTree = null;
var choosePageUrlFromFileManagerTree = null; //used by the create_presentation_uis_diagram.js and module/menu/show_menu/settings.js and others
var chooseImageUrlFromFileManagerTree = null; //used by the create_presentation_uis_diagram.js and module/menu/show_menu/settings.js and others
var chooseWebrootFileUrlFromFileManagerTree = null; //used by the create_presentation_uis_diagram.js and module/menu/show_menu/settings.js and others

var TaskEditSourceFancyPopup = new MyFancyPopupClass();
var IncludePageUrlFancyPopup = new MyFancyPopupClass();
var UploadFancyPopup = new MyFancyPopupClass();
var EditObjFromFileManagerTreeFancyPopup = new MyFancyPopupClass();

var brokers_db_drivers = {};
var auto_scroll_active = true;
var word_wrap_active = false;
var show_low_code_first = true;
var running_save_obj_actions_count = 0;
var editor_code_errors_are_old = false;

$(function () {
	MyFancyPopup.init({
		parentElement: window,
	});
	MyFancyPopup.showOverlay();
	MyFancyPopup.showLoading();
});

/* AUTO SAVE & CONVERT FUNCTIONS */

function isPHPCodeAutoSaveMenuEnabled() {
	return $("#code .code_menu ul li.auto_save_activation input, #ui .taskflowchart .workflow_menu ul.dropdown li.auto_save_activation input, #code .layout-ui-editor > .options li.auto_save_activation input").first().is(":checked");
}
function onTogglePHPCodeAutoSave() {
	var lis = $("#code .code_menu ul li.auto_save_activation, #ui .taskflowchart .workflow_menu ul.dropdown li.auto_save_activation, #code .layout-ui-editor > .options li.auto_save_activation");
	var inputs = lis.find("input");
	var spans = lis.find("span");
	
	if (auto_save) {
		taskFlowChartObj.TaskFile.auto_save = false; //should be false bc the saveObj calls the getCodeForSaving method which already saves the workflow by default, and we don't need 2 saves at the same time.
		taskFlowChartObj.Property.auto_save = true;
		$(".taskflowchart").removeClass("auto_save_disabled");
		
		lis.addClass("active");
		inputs.attr("checked", "checked").prop("checked", true);
		spans.html("Disable Auto Save");
	}
	else {
		taskFlowChartObj.TaskFile.auto_save = false;
		taskFlowChartObj.Property.auto_save = false;
		$(".taskflowchart").addClass("auto_save_disabled");
		
		lis.removeClass("active");
		inputs.removeAttr("checked", "checked").prop("checked", false);
		spans.html("Enable Auto Save");
	}
}

function isPHPCodeAutoConvertMenuEnabled() {
	return $("#code .code_menu ul li.auto_convert_activation input, #ui .taskflowchart .workflow_menu ul.dropdown li.auto_convert_activation input, #code .layout-ui-editor > .options li.auto_convert_activation input").first().is(":checked");
}
function onTogglePHPCodeAutoConvert() {
	var lis = $("#code .code_menu ul li.auto_convert_activation, #ui .taskflowchart .workflow_menu ul.dropdown li.auto_convert_activation, #code .layout-ui-editor > .options li.auto_convert_activation");
	var inputs = lis.find("input");
	var spans = lis.find("span");
	
	if (auto_convert) {
		lis.addClass("active");
		inputs.attr("checked", "checked").prop("checked", true);
		spans.html("Disable Auto Convert");
	}
	else {
		lis.removeClass("active");
		inputs.removeAttr("checked").prop("checked", false);
		spans.html("Enable Auto Convert");
	}
	
	var PtlLayoutUIEditor = $(".layout-ui-editor").data("LayoutUIEditor");
	
	if (PtlLayoutUIEditor) 
		PtlLayoutUIEditor.options.auto_convert = auto_convert;
}

/* TREE FUNCTIONS */

function removeObjectPropertiesAndMethodsAndFunctionsFromTreeForVariables(ul, data, mytree_obj) {
	$(ul).find("i.function, i.method, i.undefined_file, i.css_file, i.js_file, i.img_file, .webroot_folder").each(function(idx, elm){
		$(elm).parent().parent().remove();
	});
	
	$(ul).find("i.folder").each(function(idx, elm){
		var label = $(elm).parent().children("label").text();
		
		if (label == "webroot") {
			$(elm).parent().parent().remove();
		}
	});
	
	$(ul).find("i.file, i.service, i.class, i.test_unit_obj, i.objtype, i.hibernatemodel, i.config_file, i.controller_file, i.entity_file, i.view_file, i.template_file, i.util_file, i.block_file, i.module_file").each(function(idx, elm){
		elm = $(elm);
		var a = elm.parent();
		var li = a.parent();
		var class_name = a.children("label").text();
		var file_path = a.attr("file_path");
		var bean_name = a.attr("bean_name");
		var get_file_properties_url = a.attr("get_file_properties_url");
		
		if (elm.is("i.file, i.util_file, i.module_file")) {
			if (!file_path || !("" + file_path).match(/\.php([0-9]*)$/i)) { //is not a php file
				li.remove();
				class_name = null;
			}
			else {
				var children = li.find(" > ul > li > a").children("i.service, i.class, i.test_unit_obj");
				
				if (children.length == 1) {
					class_name = $(children[0]).parent().children("label").text();
					var regex = new RegExp("\/" + class_name + "\.php([0-9]*)$", "i");
					
					if (("" + file_path).match(regex))
						li.children("ul").remove();
				}
			}
		}
		else
			li.children("ul").remove();
		
		if (class_name)
			a.click(function() {
				var items = getClassProperties(get_file_properties_url, file_path, class_name);
				var elm = $(MyFancyPopup.settings.elementToShow).find(".property select");
				elm.attr("class_name", class_name);
				elm.attr("file_path", file_path);
				elm.attr("bean_name", bean_name);
				elm.attr("get_file_properties_url", get_file_properties_url);
				
				updateSelectElementWithFileItems(elm, items);
			});
	});
}

function removeObjectPropertiesAndMethodsAndFunctionsFromTreeForFunctions(ul, data, mytree_obj) {
	var function_select = $(MyFancyPopup.settings.elementToShow).find(".function select");
	var is_presentation_layer = typeof layer_type == "string" && layer_type == "pres";
	var is_business_logic_layer = typeof layer_type == "string" && layer_type == "bl";
	var item_type = is_presentation_layer ? "presentation" : "businesslogic";
	
	var on_add_edit_icon_callback = function() {
		//reset select field, since the parent node got refreshed and there is no selected node now.
		function_select.html("");
	};
	
	$(ul).find("i.service, i.class, i.test_unit_obj, i.function, i.method, i.undefined_file, i.css_file, i.js_file, i.img_file, .webroot_folder").each(function(idx, elm){
		$(elm).parent().parent().remove();
	});
	
	$(ul).find("i.folder").each(function(idx, elm){
		var label = $(elm).parent().children("label").text();
		
		if (label == "webroot")
			$(elm).parent().parent().remove();
		else if (is_business_logic_layer || (is_presentation_layer && $(elm).parent().closest('li[data-jstree=\'{"icon":"utils_folder"}\']').length > 0)) { //only if inside of presentation layer and folder belongs to utils_folder, or inside of business logic and is a folder
			addRefreshIconToFileManagerPopupTreeNode(elm, mytree_obj);
			
			//add icon to create new function
			if (is_business_logic_layer)
				addIconToOpenClassObjFromFileManagerTreePopup(elm, mytree_obj, "add", "edit_file_manager_service_function_url", null, "function", true, "save_file_manager_service_function_url", on_add_edit_icon_callback);
			else
				addIconToOpenClassObjFromFileManagerTreePopup(elm, mytree_obj, "add", "edit_file_manager_function_url", {item_type: item_type}, "function", false, "save_file_manager_function_url", on_add_edit_icon_callback);
		}
	});
	
	if (is_presentation_layer)
		$(ul).find("i.utils_folder").each(function(idx, elm){
			addRefreshIconToFileManagerPopupTreeNode(elm, mytree_obj);
			
			//add icon to create new function
			addIconToOpenClassObjFromFileManagerTreePopup(elm, mytree_obj, "add", "edit_file_manager_function_url", {item_type: item_type}, "function", false, "save_file_manager_function_url", on_add_edit_icon_callback);
		});
	
	$(ul).find("i.file, i.objtype, i.hibernatemodel, i.config_file, i.controller_file, i.entity_file, i.view_file, i.template_file, i.util_file, i.block_file, i.module_file").each(function(idx, elm){
		elm = $(elm);
		var a = elm.parent();
		var li = a.parent();
		var file_path = a.attr("file_path");
		var bean_name = a.attr("bean_name");
		var get_file_properties_url = a.attr("get_file_properties_url");
		
		if (elm.is("i.file, i.util_file, i.module_file")) {
			if (!file_path || !("" + file_path).match(/\.php([0-9]*)$/i)) //is not a php file
				li.remove();
			else
				li.children("ul").remove(); //remove functions from file
		}
		else
			li.children("ul").remove();
		
		a.click(function() {
			var items = getFileFunctions(get_file_properties_url, file_path);
			function_select.attr("file_path", file_path);
			function_select.attr("bean_name", bean_name);
			function_select.attr("get_file_properties_url", get_file_properties_url);
			
			updateSelectElementWithFileItems(function_select, items);
		});
		
		//add icon to create new function
		if (is_business_logic_layer && $(elm).is("i.file"))
			addIconToOpenClassObjFromFileManagerTreePopup(elm, mytree_obj, "add", "edit_file_manager_service_function_url", null, "function", true, "save_file_manager_service_function_url", on_add_edit_icon_callback);
		else if (is_presentation_layer && $(elm).is("i.util_file"))
			addIconToOpenClassObjFromFileManagerTreePopup(elm, mytree_obj, "add", "edit_file_manager_function_url", {item_type: item_type}, "function", false, "save_file_manager_function_url", on_add_edit_icon_callback);
	});
}

function removeObjectPropertiesAndMethodsAndFunctionsFromTreeForMethods(ul, data, mytree_obj) {
	var method_select = $(MyFancyPopup.settings.elementToShow).find(".method select");
	var is_presentation_layer = typeof layer_type == "string" && layer_type == "pres";
	var is_business_logic_layer = typeof layer_type == "string" && layer_type == "bl";
	var item_type = is_presentation_layer ? "presentation" : "businesslogic";
	
	var on_add_edit_icon_callback = function() {
		//reset select field, since the parent node got refreshed and there is no selected node now.
		method_select.html("");
	};
	
	$(ul).find("i.function, i.method, i.undefined_file, i.css_file, i.js_file, i.img_file, .webroot_folder").each(function(idx, elm){
		$(elm).parent().parent().remove();
	});
	
	$(ul).find("i.folder").each(function(idx, elm){
		var label = $(elm).parent().children("label").text();
		
		if (label == "webroot")
			$(elm).parent().parent().remove();
		else if (is_business_logic_layer || (is_presentation_layer && $(elm).parent().closest('li[data-jstree=\'{"icon":"utils_folder"}\']').length > 0)) { //only if inside of presentation layer and folder belongs to utils_folder, or inside of business logic and is a folder
			addRefreshIconToFileManagerPopupTreeNode(elm, mytree_obj);
			
			//add icon to create new class
			if (is_business_logic_layer)
				addIconToOpenClassObjFromFileManagerTreePopup(elm, mytree_obj, "add", "edit_file_manager_service_obj_url", null, "service_object", true, "save_file_manager_service_obj_url", on_add_edit_icon_callback);
			else
				addIconToOpenClassObjFromFileManagerTreePopup(elm, mytree_obj, "add", "edit_file_manager_class_url", {item_type: item_type}, "class_object", false, "save_file_manager_class_url", on_add_edit_icon_callback);
		}
	});
	
	if (is_presentation_layer)
		$(ul).find("i.utils_folder").each(function(idx, elm){
			addRefreshIconToFileManagerPopupTreeNode(elm, mytree_obj);
			
			//add icon to create new class
			addIconToOpenClassObjFromFileManagerTreePopup(elm, mytree_obj, "add", "edit_file_manager_class_url", {item_type: item_type}, "class_object", false, "save_file_manager_class_url", on_add_edit_icon_callback);
		});
	
	$(ul).find("i.file, i.service, i.class, i.test_unit_obj, i.objtype, i.hibernatemodel, i.config_file, i.controller_file, i.entity_file, i.view_file, i.template_file, i.util_file, i.block_file, i.module_file").each(function(idx, elm) {
		elm = $(elm);
		var a = elm.parent();
		var li = a.parent();
		var file_path = a.attr("file_path");
		var bean_name = a.attr("bean_name");
		var get_file_properties_url = a.attr("get_file_properties_url");
		var class_name = a.children("label").text();
		
		if (elm.is("i.file, i.util_file, i.module_file")) {
			if (!file_path || !("" + file_path).match(/\.php([0-9]*)$/i)) { //is not a php file
				li.remove();
				class_name = null;
			}
			else {
				var children = li.find(" > ul > li > a").children("i.service, i.class, i.test_unit_obj");
				
				if (children.length == 1) {
					var file_class_name = $(children[0]).parent().children("label").text();
					var regex = new RegExp("\/" + file_class_name + "\.php([0-9]*)$", "i");
					
					if (("" + file_path).match(regex))
						class_name = file_class_name;
				}
			}
		}
		else
			li.children("ul").remove();
		
		if (class_name) {
			a.click(function() {
				var items = getClassMethods(get_file_properties_url, file_path, class_name);
				method_select.attr("class_name", class_name);
				method_select.attr("file_path", file_path);
				method_select.attr("bean_name", bean_name);
				method_select.attr("get_file_properties_url", get_file_properties_url);
			
				updateSelectElementWithFileItems(method_select, items);
			});
			
			//add icon to create new class method or edit class
			if (is_presentation_layer || is_business_logic_layer) {
				if (is_business_logic_layer && $(elm).is("i.file"))
					addIconToOpenClassObjFromFileManagerTreePopup(elm, mytree_obj, "add", "edit_file_manager_service_obj_url", null, "service_object", true, "save_file_manager_service_obj_url", on_add_edit_icon_callback);
				else if (is_presentation_layer && $(elm).is("i.util_file"))
					addIconToOpenClassObjFromFileManagerTreePopup(elm, mytree_obj, "add", "edit_file_manager_class_url", {item_type: item_type}, "class_object", false, "save_file_manager_class_url", on_add_edit_icon_callback);
				else if ($(elm).is("i.class")) {
					addIconToOpenClassObjFromFileManagerTreePopup(elm, mytree_obj, "edit", "edit_file_manager_class_url", {item_type: item_type}, "class_object", false, null, on_add_edit_icon_callback);
					addIconToOpenClassObjFromFileManagerTreePopup(elm, mytree_obj, "add", "edit_file_manager_class_method_url", {item_type: item_type}, "class_method", false, "save_file_manager_class_method_url", on_add_edit_icon_callback);
				}
				else if ($(elm).is("i.service")) { //add icon to create new class/service method and edit class/service
					addIconToOpenClassObjFromFileManagerTreePopup(elm, mytree_obj, "edit", "edit_file_manager_service_obj_url", null, "service_object", true, null, on_add_edit_icon_callback);
					addIconToOpenClassObjFromFileManagerTreePopup(elm, mytree_obj, "add", "edit_file_manager_service_method_url", null, "service_method", true, "save_file_manager_service_method_url", on_add_edit_icon_callback);
				}
			}
		}
	});
}

function removeObjectPropertiesAndMethodsAndFunctionsFromTreeForBusinessLogic(ul, data, mytree_obj) {
	var business_logic_select = $(MyFancyPopup.settings.elementToShow).find(".businesslogic select");
	
	var on_add_edit_icon_callback = function() {
		//reset select field, since the parent node got refreshed and there is no selected node now.
		business_logic_select.html("");
	};
	
	$(ul).find("i.function, i.method").each(function(idx, elm){
		$(elm).parent().parent().remove();
	});
	
	$(ul).find("i.folder").each(function(idx, elm){
		addRefreshIconToFileManagerPopupTreeNode(elm, mytree_obj);
		
		//add icon to create new class/service
		addIconToOpenClassObjFromFileManagerTreePopup(elm, mytree_obj, "add", "edit_file_manager_service_obj_url", null, "service_object", true, "save_file_manager_service_obj_url", on_add_edit_icon_callback);
	});
	
	$(ul).find("i.file, i.service").each(function(idx, elm){
		elm = $(elm);
		var a = elm.parent();
		var li = a.parent();
		var file_path = a.attr("file_path");
		var bean_name = a.attr("bean_name");
		var get_file_properties_url = a.attr("get_file_properties_url");
		var class_name = a.children("label").text();
		
		if (elm.is("i.file")) {
			if (!file_path || !("" + file_path).match(/\.php([0-9]*)$/i)) { //is not a php file
				li.remove();
				class_name = null;
			}
			else {
				var children = li.find(" > ul > li > a").children("i.service");
				
				if (children.length == 1) {
					var service_class_name = $(children[0]).parent().children("label").text();
					var regex = new RegExp("\/" + service_class_name + "\.php([0-9]*)$", "i");
					
					if (("" + file_path).match(regex))
						class_name = service_class_name;
				}
			}
		}
		else
			li.children("ul").remove();
		
		if (class_name) {
			a.click(function() {
				var items = getClassMethods(get_file_properties_url, file_path, class_name);
				business_logic_select.attr("class_name", class_name);
				business_logic_select.attr("file_path", file_path);
				business_logic_select.attr("bean_name", bean_name);
				business_logic_select.attr("get_file_properties_url", get_file_properties_url);
				
				updateSelectElementWithFileItems(business_logic_select, items);
			});
			
			//add icon to create new class/service
			if ($(elm).is("i.file")) {
				addIconToOpenClassObjFromFileManagerTreePopup(elm, mytree_obj, "add", "edit_file_manager_service_obj_url", null, "service_object", true, "save_file_manager_service_obj_url", on_add_edit_icon_callback);
			}
			else if ($(elm).is("i.service")) { //add icon to create new class/service method and edit class/service
				addIconToOpenClassObjFromFileManagerTreePopup(elm, mytree_obj, "edit", "edit_file_manager_service_obj_url", null, "service_object", true, null, on_add_edit_icon_callback);
				addIconToOpenClassObjFromFileManagerTreePopup(elm, mytree_obj, "add", "edit_file_manager_service_method_url", null, "service_method", true, "save_file_manager_service_method_url", on_add_edit_icon_callback);
			}
		}
	});
}

function removeObjectPropertiesAndMethodsAndFunctionsFromTree(ul, data, mytree_obj) {
	$(ul).find("i.function, i.method").each(function(idx, elm){
		$(elm).parent().parent().remove();
	});
	
	$(ul).find("i.folder").each(function(idx, elm){
		addRefreshIconToFileManagerPopupTreeNode(elm, mytree_obj);
	});
	
	$(ul).find("i.file, i.objtype, i.hibernatemodel").each(function(idx, elm){
		elm = $(elm);
		var a = elm.parent();
		var li = a.parent();
		
		if (!elm.hasClass("file"))
			li.children("ul").remove();
	});
	
	$(ul).find("i.service, i.class, i.test_unit_obj").each(function(idx, elm){
		var a = $(elm).parent();
		a.parent().children("ul").remove();
	});
}

function removeMapsAndOtherIbatisNodesFromTree(ul, data, mytree_obj) {
	$(ul).find("i.map").each(function(idx, elm){
		$(elm).parent().parent().remove();
	});
	
	$(ul).find("i.folder, i.file").each(function(idx, elm){
		//add refresh button
		if ($(elm).hasClass("folder"))
			addRefreshIconToFileManagerPopupTreeNode(elm, mytree_obj);
		
		//add icon to create new queries
		addIconToOpenDataAccessObjFromFileManagerTreePopup(elm, mytree_obj, "add", "edit_file_manager_query_url", {item_type: "ibatis"}, "query");
	});
	
	$(ul).find("i.query").each(function(idx, elm){
		//add icon to edit query
		addIconToOpenDataAccessObjFromFileManagerTreePopup(elm, mytree_obj, "edit", "edit_file_manager_query_url", {item_type: "ibatis"}, "query");
	});
}

function removeMapsAndOtherHbnNodesFromTree(ul, data, mytree_obj) {
	$(ul).find("i.map").each(function(idx, elm){
		$(elm).parent().parent().remove();
	});
	
	$(ul).find("i.folder, i.file").each(function(idx, elm){
		//add refresh button
		if ($(elm).hasClass("folder"))
			addRefreshIconToFileManagerPopupTreeNode(elm, mytree_obj);
		
		//add icon to create new hbn objs
		addIconToOpenDataAccessObjFromFileManagerTreePopup(elm, mytree_obj, "add", "edit_file_manager_hbn_obj_url", {item_type: "hibernate"}, "hibernate_object");
	});
	
	$(ul).find("i.obj").each(function(idx, elm){
		//add icon to edit obj
		addIconToOpenDataAccessObjFromFileManagerTreePopup(elm, mytree_obj, "edit", "edit_file_manager_hbn_obj_url", {item_type: "hibernate"}, "hibernate_object");
		
		//add icon to create new queries
		addIconToOpenDataAccessObjFromFileManagerTreePopup(elm, mytree_obj, "add", "edit_file_manager_query_url", {item_type: "hibernate"}, "query");
	});
	
	$(ul).find("i.query").each(function(idx, elm){
		//add icon to edit query
		addIconToOpenDataAccessObjFromFileManagerTreePopup(elm, mytree_obj, "edit", "edit_file_manager_query_url", {item_type: "hibernate"}, "query");
	});
}

function removeQueriesAndMapsAndOtherHbnNodesFromTree(ul, data, mytree_obj) {
	$(ul).find("i.query, i.map, i.relationship, i.hbn_native").each(function(idx, elm){
		$(elm).parent().parent().remove();
	});
	
	$(ul).find("i.folder, i.file").each(function(idx, elm){
		//add refresh button
		if ($(elm).hasClass("folder"))
			addRefreshIconToFileManagerPopupTreeNode(elm, mytree_obj);
		
		//add icon to create new hbn objs
		addIconToOpenDataAccessObjFromFileManagerTreePopup(elm, mytree_obj, "add", "edit_file_manager_hbn_obj_url", {item_type: "hibernate"}, "hibernate_object");
	});
	
	$(ul).find("i.obj").each(function(idx, elm){
		//add icon to edit obj
		addIconToOpenDataAccessObjFromFileManagerTreePopup(elm, mytree_obj, "edit", "edit_file_manager_hbn_obj_url", {item_type: "hibernate"}, "hibernate_object");
	});
}

function removeAllThatIsNotFoldersFromTree(ul, data, mytree_obj) {
	$(ul).find("i.file, i.objtype, i.hibernatemodel, i.service, i.class, i.test_unit_obj, i.undefined_file, i.function").each(function(idx, elm){
		$(elm).parent().parent().remove();
	});
}

function removeAllThatIsNotPresentationPagesFromTree(ul, data, mytree_obj) {
	ul = $(ul);
	
	ul.find("i.file, i.view_file, i.template_file, i.util_file, i.controller_file, i.config_file, i.undefined_file, i.js_file, i.css_file, i.img_file, i.properties, i.block_file, i.module_file, .views_folder, .templates_folder, .template_folder, .utils_folder, .webroot_folder, .modules_folder, .blocks_folder, .configs_folder").each(function(idx, elm){
		$(elm).parent().parent().remove();
	});
	
	ul.find("i.folder").each(function(idx, elm){
		var label = $(elm).parent().children("label").text();
		
		if (label == "views" || label == "templates" || label == "utils" || label == "webroot" || label == "others" || label == "modules" || label == "blocks" || label == "configs") 
			$(elm).parent().parent().remove();
		//else if (label == "pages (entities)") 
		//	$(elm).parent().parent().addClass("jstree-last");
	});
	
	//move pages to project node
	ul.find("i.entities_folder").each(function(idx, elm) {
		var entities_li = $(elm).parent().parent();
		var entities_ul = entities_li.children("ul");
		var project_li = entities_li.parent().parent();
		var project_ul = project_li.children("ul");
		
		project_li.append(entities_ul);
		project_ul.remove();
	});
}

function removeAllThatIsNotPagesFromTree(ul, data, mytree_obj) {
	ul = $(ul);
	var is_inside_of_webroot = ul.parent().closest('li[data-jstree=\'{"icon":"webroot_folder"}\']').length > 0;
	
	if (!is_inside_of_webroot) { //do not remove any webroot folders
		ul.find("i.file, i.view_file, i.template_file, i.util_file, i.controller_file, i.config_file, i.undefined_file, i.js_file, i.css_file, i.img_file, i.properties, i.block_file, i.module_file, .views_folder, .templates_folder, .template_folder, .utils_folder, .modules_folder, .blocks_folder, .configs_folder").each(function(idx, elm){
			$(elm).parent().parent().remove();
		});
		
		ul.find("i.folder").each(function(idx, elm){
			var label = $(elm).parent().children("label").text();
			
			if (label == "views" || label == "templates" || label == "utils" || label == "others" || label == "modules" || label == "blocks" || label == "configs") 
				$(elm).parent().parent().remove();
			//else if (label == "webroot") 
			//	$(elm).parent().parent().addClass("jstree-last");
		});
	}
}

function removeAllThatIsNotAPossibleImageFromTree(ul, data, mytree_obj) {
	ul = $(ul);
	
	ul.find("i.file, i.view_file, i.template_file, i.util_file, i.controller_file, i.config_file, i.js_file, i.css_file, i.properties, i.block_file, i.module_file, .views_folder, .templates_folder, .template_folder, .utils_folder, .modules_folder, .blocks_folder, .configs_folder").each(function(idx, elm){
		$(elm).parent().parent().remove();
	});
	
	ul.find("i.folder").each(function(idx, elm) {
		var a = $(elm).parent();
		var li = a.parent();
		var label = a.children("label").text();
		var parent_li_i = li.parent().parent().find(" > a > i");
		
		if (label == "views" || label == "templates" || label == "utils" || label == "others" || label == "modules" || label == "blocks" || label == "configs") 
			li.remove();
		//else if (label == "webroot") 
		//	li.addClass("jstree-last");
		else if (parent_li_i.is("i.webroot_folder, i.webroot_sub_folder")) {
			$(elm).addClass("webroot_sub_folder");
			
			if (a.attr("upload_url")) {
				addUploadIconToFileManagerPopupTreeNode(elm, mytree_obj);
				addRefreshIconToFileManagerPopupTreeNode(elm, mytree_obj);
			}
		}
	});
	
	ul.find("i.webroot_folder").each(function(idx, elm) {
		var a = $(elm).parent();
		a.parent().addClass("jstree-last");
		
		if (a.attr("upload_url")) {
			addUploadIconToFileManagerPopupTreeNode(elm, mytree_obj);
			addRefreshIconToFileManagerPopupTreeNode(elm, mytree_obj);
		}
	});
}

function removeAllThatIsNotWebrootFileFromTree(ul, data, mytree_obj) {
	ul = $(ul);
	
	ul.find("i.entity_file, i.view_file, i.template_file, i.util_file, i.controller_file, i.config_file, i.properties, i.block_file, i.module_file, .entities_folder, .views_folder, .templates_folder, .template_folder, .utils_folder, .modules_folder, .blocks_folder, .configs_folder").each(function(idx, elm){
		$(elm).parent().parent().remove();
	});
	
	ul.find("i.folder").each(function(idx, elm) {
		var a = $(elm).parent();
		var li = a.parent();
		var label = a.children("label").text();
		var parent_li_i = li.parent().parent().find(" > a > i");
		
		if (label == "pages (entities)" || label == "views" || label == "templates" || label == "utils" || label == "others" || label == "modules" || label == "blocks" || label == "configs") 
			li.remove();
		//else if (label == "webroot") 
		//	li.addClass("jstree-last");
		else if (parent_li_i.is("i.webroot_folder, i.webroot_sub_folder")) {
			$(elm).addClass("webroot_sub_folder");
			
			if (a.attr("upload_url")) {
				addUploadIconToFileManagerPopupTreeNode(elm, mytree_obj);
				addRefreshIconToFileManagerPopupTreeNode(elm, mytree_obj);
			}
		}
	});
	
	ul.find("i.webroot_folder").each(function(idx, elm) {
		var a = $(elm).parent();
		a.parent().addClass("jstree-last");
		
		if (a.attr("upload_url")) {
			addUploadIconToFileManagerPopupTreeNode(elm, mytree_obj);
			addRefreshIconToFileManagerPopupTreeNode(elm, mytree_obj);
		}
	});
}

function removeAllThatIsNotBlocksFromTree(ul, data, mytree_obj) {
	ul = $(ul);
	
	ul.find("i.file, i.view_file, i.entity_file, i.template_file, i.util_file, i.controller_file, i.config_file, i.undefined_file, i.js_file, i.css_file, i.img_file, i.properties, i.module_file, .entities_folder, .views_folder, .templates_folder, .template_folder, .utils_folder, .webroot_folder, .modules_folder, .configs_folder").each(function(idx, elm){
		$(elm).parent().parent().remove();
	});
	
	ul.find("i.folder").each(function(idx, elm){
		var label = $(elm).parent().children("label").text();
		
		if (label == "pages (entities)" || label == "views" || label == "templates" || label == "utils" || label == "webroot" || label == "others" || label == "modules" || label == "configs")
			$(elm).parent().parent().remove();
		//else if (label == "blocks")
		//	$(elm).parent().parent().addClass("jstree-last");
	});
	
	//move pages to project node
	ul.find("i.blocks_folder").each(function(idx, elm) {
		var blocks_li = $(elm).parent().parent();
		var blocks_ul = blocks_li.children("ul");
		var project_li = blocks_li.parent().parent();
		var project_ul = project_li.children("ul");
		
		project_li.append(blocks_ul);
		project_ul.remove();
	});
}

function removeAllThatIsNotViewsFromTree(ul, data, mytree_obj) {
	ul = $(ul);
	
	ul.find("i.file, i.block_file, i.entity_file, i.template_file, i.util_file, i.controller_file, i.config_file, i.undefined_file, i.js_file, i.css_file, i.img_file, i.properties, i.module_file, .entities_folder, .blocks_folder, .templates_folder, .template_folder, .utils_folder, .webroot_folder, .modules_folder, .configs_folder").each(function(idx, elm){
		$(elm).parent().parent().remove();
	});
	
	ul.find("i.folder").each(function(idx, elm){
		var label = $(elm).parent().children("label").text();
		
		if (label == "pages (entities)" || label == "blocks" || label == "templates" || label == "utils" || label == "webroot" || label == "others" || label == "modules" || label == "configs")
			$(elm).parent().parent().remove();
		//else if (label == "views")
		//	$(elm).parent().parent().addClass("jstree-last");
	});
	
	//move views to project node
	ul.find("i.views_folder").each(function(idx, elm) {
		var views_li = $(elm).parent().parent();
		var views_ul = views_li.children("ul");
		var project_li = views_li.parent().parent();
		var project_ul = project_li.children("ul");
		
		project_li.append(views_ul);
		project_ul.remove();
	});
}

/* POPUP FUNCTIONS */
/* POPUP FUNCTIONS - refresh */

function refreshFileManagerPopupTreeNodeFromInnerIcon(elm, mytree_obj) {
	window.event && typeof window.event.stopPropagation == "function" && window.event.stopPropagation(); //this function gets called by an icon which is inside of a "a" element. This avoids the event from the "a" element to be executed.
	
	if (mytree_obj) {
		var li = $(elm).closest("li");
		var node = li.children("ul[url]").length > 0 ? li : li.parent().closest("ul[url]").parent(); //finds proper node
		
		if (node.length > 0) {
			node.removeClass("jstree-closed").addClass("jstree-open"); //add in case it doesn't have, so we can then show the inner folder that was created.
			mytree_obj.refreshNodeChilds(node);
			
			node.children("ul").show();
		}
	}
}

function addRefreshIconToFileManagerPopupTreeNode(elm, mytree_obj) {
	if (mytree_obj) {
		var node = $(elm).closest("li");
		var icon = $('<span class="icon refresh" title="Refresh">Refresh</span>');
		icon.on("click", function() {
			refreshFileManagerPopupTreeNodeFromInnerIcon(this, mytree_obj);
		});
		node.children("a").after(icon);
	}
}

/* POPUP FUNCTIONS - upload */

function addUploadIconToFileManagerPopupTreeNode(elm, mytree_obj) {
	if (mytree_obj) {
		var node = $(elm).closest("li");
		var icon = $('<span class="icon upload" title="Upload">Upload</span>');
		icon.on("click", function() {
			openUploadPopupFromFileManagerPopupTreeNodeInnerIcon(this, mytree_obj);
		});
		node.children("a").after(icon);
	}
}

function openUploadPopupFromFileManagerPopupTreeNodeInnerIcon(elm, mytree_obj) {
	window.event && typeof window.event.stopPropagation == "function" && window.event.stopPropagation(); //this function gets called by an icon which is inside of a "a" element. This avoids the event from the "a" element to be executed.
	
	var a = $(elm).closest("li").children("a");
	var upload_url = a.attr("upload_url");
	
	if (upload_url) {
		var popup = $("#upload_from_file_manager");
		
		if (!popup[0]) {
			popup = $('<div id="upload_from_file_manager" class="myfancypopup with_iframe_title"></div>');
			$(document.body).append(popup);
		}
		
		upload_url += (upload_url.indexOf("?") != -1 ? "&" : "?") + "popup=1";
		popup.html('<iframe src="' + upload_url + '"></iframe>');
		
		UploadFancyPopup.init({
			elementToShow: popup,
			parentElement: document,
			onClose: function() {
				refreshFileManagerPopupTreeNodeFromInnerIcon(elm, mytree_obj);
			},
		});
		
		UploadFancyPopup.showPopup();
	}
}

/* POPUP FUNCTIONS - add and edit icons from FileManagerTree */

function addClassObjFromFileManagerTree(elm, mytree_obj, save_url, edit_url, params_to_replace, type, is_business_logic, on_close_callback) {
	//copied from the admin_menu.js:createClassObjectOrMethodOrFunction
	var type_label = type.replace(/_/g, " ");
	var new_file_name = prompt("Please write the " + type_label + " name:");
	var status = new_file_name && typeof new_file_name == "string" && new_file_name.replace(/\s/g, "") != "";
	
	if (status) {
		if (new_file_name) {
			new_file_name = ("" + new_file_name).replace(/(^\s+|\s+$)/g, ""); //trim name and remove spaces
			
			if (new_file_name) {
				//normalize new file name
				new_file_name = typeof normalizeFileName == "function" ? normalizeFileName(new_file_name, true) : new_file_name;
				new_file_name = new_file_name.toLowerCase().replace(/\b[a-z]/g, function(letter) {
					return letter.toUpperCase();
				}).replace(/\s+/g, ""); //ucwords
				
				if (type == "service_method" || type == "class_method" || type == "function")
					new_file_name = new_file_name[0].toLowerCase() + new_file_name.substr(1).replace(/\s+/g, "");
				
				//prepare save request
				var post_data = {
					object : {
						name : new_file_name,
						is_business_logic_service : is_business_logic ? 1 : null,
					}
				};
				
				$.ajax({
					type : "post",
					url : save_url,
					data : post_data,
					success : function(data, textStatus, jqXHR) {
						if (jquery_native_xhr_object && isAjaxReturnedResponseLogin(jquery_native_xhr_object.responseURL))
							showAjaxLoginPopup(jquery_native_xhr_object.responseURL, save_url, function() {
								StatusMessageHandler.removeLastShownMessage("error");
								addClassObjFromFileManagerTree(elm, mytree_obj, save_url, edit_url, params_to_replace, type, is_business_logic, on_close_callback);
							});
						else {
							var data_status = data == "1";
							var json_data = data && ("" + data).substr(0, 1) == "{" ? JSON.parse(data) : null;
							
							if ($.isPlainObject(json_data) && json_data.hasOwnProperty("status") && json_data["status"])
								data_status = true;
							
							if (data_status) {
								if (edit_url) {
									//prepare edit_url
									if (type == "service_method" || type == "class_method")
										params_to_replace["method"] = new_file_name;
									else if (type == "function") {
										//if node is a folder
										if (!params_to_replace["path"].match(/\.php$/))
											params_to_replace["path"] += (params_to_replace["path"].substr(params_to_replace["path"].length - 1) == "/" ? "" : "/") + "functions.php";
										
										//set function
										params_to_replace["function"] = new_file_name;
									}
									else {
										//if node is a folder
										if (!params_to_replace["path"].match(/\.php$/))
											params_to_replace["path"] += (params_to_replace["path"].substr(params_to_replace["path"].length - 1) == "/" ? "" : "/") + new_file_name + ".php";
										
										//set service
										if (type == "service_object")
											params_to_replace["service"] = new_file_name;
										else
											params_to_replace["class"] = new_file_name;
									}
									
									for (var k in params_to_replace)
										edit_url = edit_url.replace("#" + k + "#", params_to_replace[k]);
									
									edit_url = edit_url.replace(/#\w+#/g, ""); //replace all other vars, like #item_type# if not passed through argument in this function.
									
									//open popup
									openEditObjFromFileManagerTreePopup(elm, mytree_obj, edit_url, on_close_callback);
								}
								else {
									//refresh mytree_obj
									refreshFileManagerPopupTreeNodeFromInnerIcon(elm, mytree_obj);
									
									if (typeof on_close_callback == "function")
										on_close_callback(elm, mytree_obj);
								}
							}
							else
								StatusMessageHandler.showError("There was a problem trying to create this " + type_label + ". Please try again..." + (data && !json_data ? "\n" + data : "")); 
								StatusMessageHandler.getMessageHtmlObj()[0].style.setProperty("z-index", "99999999999", "important"); //move error to front of filemanager popup
						}
					},
					error : function(jqXHR, textStatus, errorThrown) { 
						var msg = jqXHR.responseText ? "\n" + jqXHR.responseText : "";
						StatusMessageHandler.showError((errorThrown ? errorThrown + " error.\n" : "") + "Error trying to create this " + type_label + ".\nPlease try again..." + msg);
						StatusMessageHandler.getMessageHtmlObj()[0].style.setProperty("z-index", "99999999999", "important"); //move error to front of filemanager popup
					},
				});
			}
		}
		else
			alert("Error: " + type_label + " name cannot be empty");
	}
}

function addIconToOpenClassObjFromFileManagerTreePopup(elm, mytree_obj, icon_class, url_var_name, params_to_replace, type, is_business_logic, save_url_var_name, on_close_callback) {
	if (icon_class) {
		var edit_url, save_url;
		
		if (url_var_name)
			eval('edit_url = typeof ' + url_var_name + ' != "undefined" ? ' + url_var_name + ' : null;');
		
		if (save_url_var_name)
			eval('save_url = typeof ' + save_url_var_name + ' != "undefined" ? ' + save_url_var_name + ' : null;');
		
		if ((icon_class != "add" && edit_url) || (icon_class == "add" && save_url)) {
			elm = $(elm);
			params_to_replace = $.isPlainObject(params_to_replace) ? params_to_replace : {};
			
			var elm_a = elm.parent();
			var elm_li = elm_a.parent();
			var is_folder = elm_li.children("ul").attr("url") ? true : false; //Do not use elm.is("i.folder"), bc the elm can be utils_folder or other folder type
			var ul_url = is_folder ? elm_li.children("ul").attr("url") : elm_li.closest("ul[url]").attr("url");
			
			if (ul_url) {
				//prepare params
				var params = getURLSearchParams(ul_url);
				
				params_to_replace["bean_name"] = params["bean_name"];
				params_to_replace["bean_file_name"] = params["bean_file_name"];
				params_to_replace["filter_by_layout"] = params["filter_by_layout"];
				params_to_replace["path"] = is_folder ? params["path"] : elm_a.attr("file_path");
				params_to_replace["service"] = elm.is("i.service") ? elm_a.children("label").text() : ""; //service, if apply
				params_to_replace["class"] = elm.is("i.service, i.class") ? elm_a.children("label").text() : ""; //service or class, if apply. Note that the service needs the class for the save_url to create new methods
				params_to_replace["method"] = elm.is("i.method") ? elm_a.children("label").text() : ""; //method, if apply
				
				if (elm.is("i.method") && !params_to_replace["path"]) {
					var service_li = elm_li.parent().parent();
					params_to_replace["path"] = service_li.children("a").attr("file_path");
				}
				
				if (params_to_replace["bean_name"] && params_to_replace["bean_file_name"] && params_to_replace["path"]) {
					//prepare icon
					var type_label = type.replace(/_/g, " ").toLowerCase().replace(/\b[a-z]/g, function(letter) {
						return letter.toUpperCase();
					}); //ucwords;
					var icon_label = icon_class.replace(/_/g, " ").toLowerCase().replace(/\b[a-z]/g, function(letter) {
						return letter.toUpperCase();
					}); //ucwords;
					
					var icon = $('<span class="icon ' + icon_class + '" title="' + icon_label + ' ' + type_label + '">' + icon_label + '</span>');
					icon.on("click", function(ev) {
						window.event && typeof window.event.stopPropagation == "function" && window.event.stopPropagation(); //this function gets called by an icon which is inside of a "a" element. This avoids the event from the "a" element to be executed.
						
						if (icon_class != "add") { //on edit action
							//prepare edit_url
							for (var k in params_to_replace)
								edit_url = edit_url.replace("#" + k + "#", params_to_replace[k]);
							
							edit_url = edit_url.replace(/#\w+#/g, ""); //replace all other vars, like #item_type# if not passed through argument in this function.
							
							//open popup
							openEditObjFromFileManagerTreePopup(this, mytree_obj, edit_url, on_close_callback);
						}
						else { //on add action
							//prepare save_url
							for (var k in params_to_replace)
								save_url = save_url.replace("#" + k + "#", params_to_replace[k]);
							
							save_url = save_url.replace(/#\w+#/g, ""); //replace all other vars, like #item_type# if not passed through argument in this function.
							
							//save and open popup
							addClassObjFromFileManagerTree(this, mytree_obj, save_url, edit_url, params_to_replace, type, is_business_logic, on_close_callback);
						}
					});
					elm_a.after(icon);
				}
			}
		}
	}
}

function addIconToOpenDataAccessObjFromFileManagerTreePopup(elm, mytree_obj, icon_class, url_var_name, params_to_replace, type, on_close_callback) {
	if (icon_class && url_var_name) {
		eval('var url = typeof ' + url_var_name + ' != "undefined" ? ' + url_var_name + ' : null;');
		
		if (url) {
			elm = $(elm);
			params_to_replace = $.isPlainObject(params_to_replace) ? params_to_replace : {};
			
			var elm_a = elm.parent();
			var elm_li = elm_a.parent();
			var is_folder = elm_li.children("ul").attr("url") ? true : false; //Do not use elm.is("i.folder"), bc the elm can be utils_folder or other folder type
			var ul_url = is_folder ? elm_a.parent().children("ul").attr("url") : elm_a.parent().closest("ul[url]").attr("url");
			
			if (ul_url) {
				//prepare params
				var params = getURLSearchParams(ul_url);
				
				params_to_replace["bean_name"] = params["bean_name"];
				params_to_replace["bean_file_name"] = params["bean_file_name"];
				params_to_replace["filter_by_layout"] = params["filter_by_layout"];
				params_to_replace["path"] = is_folder ? params["path"] : elm_a.attr("file_path");
				params_to_replace["obj"] = elm.is("i.obj") ? elm_a.children("label").text() : (elm.is("i.query") ? elm_a.attr("hbn_obj_id") : ""); //hbn obj id, if apply
				params_to_replace["query_id"] = elm.is("i.query") ? elm_a.children("label").text() : ""; //query id, if apply
				params_to_replace["query_type"] = elm.is("i.query") ? elm_a.attr("query_type") : ""; //query type, if apply
				params_to_replace["relationship_type"] = elm.is("i.query") ? elm_a.attr("relationship_type") : ""; //relationship type, if apply
				
				if (params_to_replace["bean_name"] && params_to_replace["bean_file_name"] && params_to_replace["path"]) {
					//prepare url
					for (var k in params_to_replace)
						url = url.replace("#" + k + "#", params_to_replace[k]);
					
					url = url.replace(/#\w+#/g, ""); //replace all other vars, like #item_type# if not passed through argument in this function.
					
					//prepare icon
					var type_label = type.replace(/_/g, " ").toLowerCase().replace(/\b[a-z]/g, function(letter) {
						return letter.toUpperCase();
					}); //ucwords;
					var icon_label = icon_class.replace(/_/g, " ").toLowerCase().replace(/\b[a-z]/g, function(letter) {
						return letter.toUpperCase();
					}); //ucwords;
					
					var icon = $('<span class="icon ' + icon_class + '" title="' + icon_label + ' ' + type_label + '">' + icon_label + '</span>');
					icon.on("click", function(ev) {
						window.event && typeof window.event.stopPropagation == "function" && window.event.stopPropagation(); //this function gets called by an icon which is inside of a "a" element. This avoids the event from the "a" element to be executed.
						
						openEditObjFromFileManagerTreePopup(this, mytree_obj, url, on_close_callback);
					});
					elm_a.after(icon);
				}
			}
		}
	}
}

function openEditObjFromFileManagerTreePopup(elm, mytree_obj, url, on_close_callback) {
	if (url) {
		var popup = $("#edit_obj_from_file_manager_tree_popup");
		
		if (!popup[0]) {
			popup = $('<div id="edit_obj_from_file_manager_tree_popup" class="myfancypopup edit_obj_from_file_manager_tree_popup with_iframe_title"></div>');
			$(document.body).append(popup);
		}
		
		url += (url.indexOf("?") != -1 ? "&" : "?") + "popup=1";
		popup.html('<iframe src="' + url + '"></iframe>');
		
		EditObjFromFileManagerTreeFancyPopup.init({
			elementToShow: popup,
			parentElement: document,
			onClose: function() {
				//refresh mytree_obj
				refreshFileManagerPopupTreeNodeFromInnerIcon(elm, mytree_obj);
				
				if (typeof on_close_callback == "function")
					on_close_callback(elm, mytree_obj);
				
				//remove iframe, bc if it contains auto_Save of a diagram, it will keep auto saving but incorrect data (wrong positions - since the iframe is closed.)
				popup.children("iframe").remove();
			},
		});
		
		EditObjFromFileManagerTreeFancyPopup.showPopup();
	}
}

/* UTIL FUNCTIONS */

function updateSelectElementWithFileItems(elm, items) {
	var options_html = "";
	
	if (items && items.length > 0) {
		var class_name = elm.attr("class_name");
		
		for (var i = 0; i < items.length; i++) {
			var item = items[i];
			var name = item["name"] ? item["name"] : "";
			var is_static = item["static"] == "1" ? 1 : 0;
			var is_hidden = item["hidden"] == "1" ? 1 : 0;
			
			if (!is_hidden) {
				options_html += '<option is_static="' + is_static + '" class_name="' + (class_name ? class_name : "") + '">' + name + '</option>';
			}
		}
	}
	
	elm.html(options_html);
}

function getClassProperties(url, file_path, class_name) {
	url = url.replace("#path#", file_path).replace("#class_name#", class_name).replace("#type#", "properties");
	
	return getFileProperties(url);
}

function getClassMethods(url, file_path, class_name) {
	url = url.replace("#path#", file_path).replace("#class_name#", class_name).replace("#type#", "methods");
	
	return getFileProperties(url);
}

function getFileFunctions(url, file_path) {
	url = url.replace("#path#", file_path).replace("#class_name#", "").replace("#type#", "functions");
	
	return getFileProperties(url);
}

function getMethodArguments(url, file_path, class_name, method_name) {
	url = url.replace("#path#", file_path).replace("#class_name#", class_name).replace("#type#", "arguments") + "&method=" + method_name;
	
	return getFileProperties(url);
}

function getFunctionArguments(url, file_path, func_name) {
	url = url.replace("#path#", file_path).replace("#class_name#", "").replace("#type#", "arguments") + "&function=" + func_name;
	
	return getFileProperties(url);
}

function getQueryParameters(bean_file_name, bean_name, db_driver, db_type, file_path, query_type, query_id, hbn_obj_id, relationship_type) {
	var url = get_query_properties_url.replace("#bean_file_name#", bean_file_name).replace("#bean_name#", bean_name).replace("#db_driver#", db_driver).replace("#db_type#", db_type).replace("#path#", file_path).replace("#query_type#", query_type).replace("#query#", query_id).replace("#obj#", hbn_obj_id).replace("#relationship_type#", relationship_type);
	
	return getFileProperties(url);
}

function getBusinessLogicParameters(bean_file_name, bean_name, file_path, service_id) {
	var url = get_business_logic_properties_url.replace("#bean_file_name#", bean_file_name).replace("#bean_name#", bean_name).replace("#path#", file_path).replace("#service#", service_id);
	
	return getFileProperties(url);
}

function getBrokerDBDrivers(broker, bean_file_name, bean_name, item_type) {
	var url = get_broker_db_drivers_url.replace("#bean_file_name#", bean_file_name).replace("#bean_name#", bean_name).replace("#broker#", broker).replace("#item_type#", item_type);
	
	return getFileProperties(url);
}

function getFileProperties(url) {
	var props = null;
	
	$.ajax({
		type : "get",
		url : url,
		dataType : "json",
		success : function(data, textStatus, jqXHR) {
			if (data) {
				props = data;
			}
		},
		async : false,
	});
	
	return props;
}

function getTargetFieldForProgrammingTaskChooseFromFileManager(elm) {
	var target_field = null;
	var p = elm.parent();
	var input_selector = elm.attr("input_selector");
	
	if (elm.is("input, textarea"))
		target_field = elm;
	else if (input_selector && p.find(input_selector).length > 0)
		target_field = p.find(input_selector); //selectors can have multiple items. Is the selector that should define if is the 1 or more items.
	else if (elm.prev("input").is("input"))
		target_field = elm.prev();
	else if (elm.next("input").is("input"))
		target_field = elm.next();
	else
		target_field = p.find("input").first();
	
	if (!target_field[0]) {
		if (elm.prev("textarea").is("textarea"))
			target_field = elm.prev();
		else if (elm.next("textarea").is("textarea"))
			target_field = elm.next();
		else
			target_field = p.find("textarea").first();
	}
	
	return target_field;
}

function onProgrammingTaskEditSource(elm, data) {
	//console.log("onProgrammingTaskEditSource");
	//console.log(data);
	
	if (edit_task_source_url) {
		//filter data ignoring inner arrays and objects, in order to don't overload the url.
		for (var k in data)
			if ($.isArray(data[k]) || $.isPlainObject(data[k])) {
				data[k] = null;
				delete data[k];
			}
		
		//prepare url
		var url = edit_task_source_url + (edit_task_source_url.indexOf("?") == -1 ? "?" : "");
		url += "&popup=1";
		
		//get popup
		var popup = $("body > .edit_task_source_popup");
		
		if (!popup[0]) {
			popup = $('<div class="myfancypopup with_iframe_title edit_task_source_popup"></div>');
			$(document.body).append(popup);
		}
		
		var rand = parseInt(Math.random() * 10000); //this rand is very important bc if we have multiple onProgrammingTaskEditSource methods called inside of it-self, even if its inside of different iframes, the form.submit() will always get the target in the parent main window.
		var html = '<form action="' + url + '" method="post" target="edit_task_source_frame_' + rand + '" style="display:none;">'
			+ '<textarea name="data">' + JSON.stringify(data) + '</textarea>'
			+ '<input type="submit">'
		+ '</form>'
		+ '<iframe name="edit_task_source_frame_' + rand + '"></iframe>';
		
		popup.html(html); //cleans the iframe so we don't see the previous html
		
		//prepare popup iframe
		var oForm = popup.children("form");
		oForm.submit();
		
		TaskEditSourceFancyPopup.init({
			elementToShow: popup,
			parentElement: document,
			onClose: function() {
				//Remove iframe, bc if the iframe has a diagram with auto-save active, is saving the diagram after the popup is close, whcih is saving a messy diagram bc it looses the tasks' positions.
				popup.find("form, iframe").remove();
			}
		});
		
		TaskEditSourceFancyPopup.showPopup();
	}
	else
		StatusMessageHandler.showError("Undefined edit_task_source_url variable in onProgrammingTaskEditSource method");
}

function onFunctionTaskEditMethodCode(elm, popup) {
	if (typeof openSubmenu == "function") 
		popup.find("#code > .code_menu, #ui > .taskflowchart > .workflow_menu").attr("onClick", "openSubmenu(this)");
}

function onChangePropertyVariableType(elm) {
	elm = $(elm);
	var type = elm.val();
	
	var main_elm = elm.parent().parent();
	
	main_elm.children(".variable_type, .variable_settings").hide();
	main_elm.children("." + type).show();
	
	if (type == "new_var")
		onChangePropertyVariableScope( main_elm.find(" > .new_var > .scope > select")[0] );
	
	MyFancyPopup.updatePopup();
}

function onChangePropertyVariableScope(elm) {
	elm = $(elm);
	var selected_option = elm.find("option:selected[is_final_var]");
	var main_elm = elm.parent().parent();
	var name_elm = main_elm.children(".name");
	var name_input = name_elm.children("input");
	var name_icon_add = name_elm.children(".icon.add");
	var name_sub_group = name_elm.children(".sub_group");
	
	if (selected_option.length > 0) {
		if (selected_option.css("display") == "none") {
			elm.val("");
			name_input.val("").removeAttr("disabled");
			name_icon_add.show();
			name_sub_group.show();
		}
		else {
			name_input.val( selected_option.val() ).attr("disabled", "disabled");
			name_icon_add.hide();
			name_sub_group.hide().html("");
		}
	}
	else {
		name_input.removeAttr("disabled");
		
		if (name_icon_add.css("display") == "none")
			name_icon_add.show();
		
		if (name_sub_group.css("display") == "none")
			name_sub_group.show();
	}
}

function onProgrammingTaskChooseCreatedVariable(elm) {
	elm = $(elm);
	var popup = $("#choose_property_variable_from_file_manager");
	var target_field = getTargetFieldForProgrammingTaskChooseFromFileManager(elm);
	
	if (target_field[0]) {
		popup.children(".type").show();
		
		var type_select = popup.find(" > .type > select");
		onChangePropertyVariableType(type_select[0]);
		
		MyFancyPopup.init({
			elementToShow: popup,
			parentElement: document,
			onOpen: function() {
				var select = popup.find(" > .existent_var > .variable > select");
				var total = populateVariablesOfTheWorkflowInSelectField(select);
				
				if (total == 0) {
					type_select.find("option[value=existent_var]").hide();
					
					if (type_select.val() == "existent_var") {
						type_select.val("");
						onChangePropertyVariableType(type_select[0]);
					}
				}
				else
					popup.find(" > .type > select option[value=existent_var]").show();
			},
			
			targetField: target_field[0],
			updateFunction: chooseCreatedVariable
		});
		
		MyFancyPopup.showPopup();
	}
	else
		StatusMessageHandler.showMessage("No targeted field found!");
}

function populateVariablesOfTheWorkflowInSelectField(select) {
	var total = 0;
	
	if (typeof ProgrammingTaskUtil == "object" && ProgrammingTaskUtil.variables_in_workflow) {
		var default_option = select.val();
		
		var options_html = "<option></option>";
		for (var var_name in ProgrammingTaskUtil.variables_in_workflow) {
			options_html += "<option>" + var_name + "</option>";
			
			total++;
		}

		select.html(options_html);
		select.val(default_option);
	}
	
	return total;
}

function chooseCreatedVariable(elm) {
	var popup = $("#choose_property_variable_from_file_manager");
	var type = popup.find(" > .type > select").val();
	var type_elm = popup.find("." + type);
	var value = null;
	
	if (type == "existent_var")
		value = type_elm.find(" > .variable > select").val();
	else if (type == "class_prop_var") {
		var select = type_elm.find(" > .property > select");
		var option = select.find(":selected").first();
		var class_name = option.attr("class_name");
		value = select.val();
		
		if (class_name && value) {
			if (option.attr("is_static") == "1")
				value = option.attr("class_name") + "::" + value;
			else
				value = '$' + option.attr("class_name") + "->" + value;
		}
	}
	else if (type) {
		var is_final_var = type == "new_var" && type_elm.find(" > .scope > select option:selected[is_final_var]").length;
		
		if (is_final_var) { //used for GLOBALS['logged_user_id']
			value = type_elm.find(" > .scope > select").val();
			value = value.replace(/\[([^'"])/g, "['$1").replace(/([^'"])\]/g, "$1']"); //replaces [xxx] with ['xxx']
		}
		else {
			value = type_elm.find(" > .name > input").val();
			value = ("" + value).replace(/^\s+/g, "").replace(/\s+$/g, "");
		}
		
		if (value) {
			value = value[0] == '$' ? value.substr(1, value.length) : value; //remove $ if exists
			
			if (type == "new_var" && !is_final_var)
				value = getNewVarWithSubGroupsInProgrammingTaskChooseCreatedVariablePopup(type_elm, value, true);
			
			value = '$' + value; //adds '$'
		}
	}
	
	var convert_to_hash_tag = type_elm.find(".convert_to_hash_tag input").is(":checked");
	convert_to_hash_tag = value && convert_to_hash_tag && type != "class_prop_var" && value.substr(0, 1) == '$';
	
	if (convert_to_hash_tag)
		value = "#" + value.substr(1).replace(/\[("|')/g, "[").replace(/("|')\]/g, "]") + "#";
	
	var input = $(MyFancyPopup.settings.targetField);
	input.val(value ? value : "");
	
	//update var_type if exists
	var var_type = convert_to_hash_tag ? "string" : "";
	var var_type_select = input.parent().parent().find(".var_type select");
	var_type_select.val(var_type);
	var_type_select.trigger("change");
	
	//set value_type if exists and if only input name is simple without "]" and "[" chars:
	var input_name = input.attr("name");
	var input_parent = input.parent();
	
	if (input_name && !input_name.match(/[\[\]]/)) {
		var input_type = input_parent.children("select[name=" + input_name + "_type]");
		
		if (input_type[0])
			input_type.val(var_type);
		else if (input_parent.is(".value") && input_parent.parent().find(" > .type > select")[0]) //in case of return task and other tasks
			input_parent.parent().find(" > .type > select").val(var_type);
	}
	else if (input.is(".key") && input_parent.is(".item")) { //in case of array items
		var input_type = input_parent.children(".key_type");
		
		if (input_type[0])
			input_type.val(var_type);
	}
	else if (input.is(".value") && input_parent.is(".item")) { //in case of array items
		var input_type = input_parent.children(".value_type");
		
		if (input_type[0])
			input_type.val(var_type);
	}
	else if (input.parent().is(".table_arg_value")) //in case of method/function args
		input.parent().parent().find(".table_arg_type select").val(var_type);
	else if (input.is(".var") && input_parent.is(".item")) { //in case of conditions items
		//fins the next sibling with class .var_type
		var node = input;
		
		do {
			var next = node.next();
			
			if (next.hasClass("var_type")) {
				next.val(var_type);
				break;
			}
			
			node = next;
		} 
		while(next[0]);
	}
	
	//put cursor in targetField
	input.focus();
	
	MyFancyPopup.hidePopup();
}
function getNewVarWithSubGroupsInProgrammingTaskChooseCreatedVariablePopup(type_elm, value, with_quotes) {
	var group = type_elm.find(" > .scope > select").val();
	var quotes = with_quotes ? "'" : ""; //with or without single quotes
	
	if (group)
		value = group + "[" + ($.isNumeric(value) ? value : quotes + value + quotes) + "]";
	
	var lis = type_elm.find(" > .name > .sub_group li"); //Do not add ".sub_group > li" bc li can have other sub li items inside of each other
	
	for (var i = 0; i < lis.length; i++) {
		var li = $(lis[i]);
		var sub_group_value = li.children("input").val();
		
		value += "[" + ($.isNumeric(sub_group_value) ? sub_group_value : quotes + sub_group_value + quotes) + "]";
	};
	
	return value;
}
function addNewVarSubGroupToProgrammingTaskChooseCreatedVariablePopup(elm) {
	var sub_groups = $(elm).parent().find(".sub_group").last();
	var sub_group = $('<li><input /><span class="icon delete" onClick="removeNewVarSubGroupToProgrammingTaskChooseCreatedVariablePopup(this)" title="Remove">Remove</span><ul class="sub_group"></ul></li>');
	
	sub_groups.append(sub_group);
}
function removeNewVarSubGroupToProgrammingTaskChooseCreatedVariablePopup(elm) {
	$(elm).parent().closest("li").remove();
}

function onProgrammingTaskChooseObjectProperty(elm) {
	elm = $(elm);
	var popup = $("#choose_property_variable_from_file_manager");
	
	var select = popup.find(" > .type > select");
	select.val("class_prop_var");
	onChangePropertyVariableType(select[0]);
	
	MyFancyPopup.init({
		elementToShow: popup,
		parentElement: document,
		onOpen: function() {
			popup.children(".type").hide();
			select.find("option[value=new_var], option[value=existent_var]").hide();
		},
		onClose: function() {
			popup.children(".type").show();
			select.find("option[value=new_var], option[value=existent_var]").show();
		},
		
		targetField: getTargetFieldForProgrammingTaskChooseFromFileManager(elm), //elm.parent().find("input")[0],
		updateFunction: chooseObjectProperty
	});
	
	MyFancyPopup.showPopup();
}

function chooseObjectProperty(elm) {
	var popup = $("#choose_property_variable_from_file_manager");
	var select = popup.find(" > .class_prop_var > .property > select");
	
	var value = select.val();
	
	var dest = $(MyFancyPopup.settings.targetField);
	dest.val(value ? value : "");
	
	if (value) {
		var obj_field;
		var static_field;
		
		if (dest.parent().hasClass("prop_name")) {
			obj_field = dest.parent().parent().find(".obj_name input");
			static_field = dest.parent().parent().find(".static input");
		}
		else {
			obj_field = dest.parent().parent().find(".result_obj_name input");
			static_field = dest.parent().parent().find(".result_static input");
		}
		
		updateObjNameAccordingWithObjectPropertySelection(select, obj_field, static_field);
	}
	
	MyFancyPopup.hidePopup();
}

function updateObjNameAccordingWithObjectPropertySelection(select, obj_field, static_field) {	
	if (obj_field) {
		var selected_option = select.find(":selected").first();
		
		var class_name = selected_option.attr("class_name");
		var is_static = selected_option.attr("is_static");
		
		if (is_static == "1") {
			obj_field.val(class_name ? class_name : "");
			
			if (static_field) {
				static_field.attr("checked", "checked").prop("checked", true);
			}
		}
		else {
			if (obj_field.val() == "") {
				obj_field.val(class_name ? class_name : "");
			}
			
			if (static_field) {
				static_field.removeAttr("checked").prop("checked", false);
			}
		}
	}
}

function onProgrammingTaskChooseObjectMethod(elm) {
	elm = $(elm);
	var popup = $("#choose_method_from_file_manager");
	
	MyFancyPopup.init({
		elementToShow: popup,
		parentElement: document,
		onOpen: function() {
			popup.find(".method").show();
		},
		
		targetField: getTargetFieldForProgrammingTaskChooseFromFileManager(elm), //elm.parent().find("input")[0],
		updateFunction: chooseObjectMethod
	});
	
	MyFancyPopup.showPopup();
}

//Note that any change here must be replicated in admin_menu.js:onChooseWorkflowCallObjectMethodTask
function chooseObjectMethod(elm) {
	var popup = $("#choose_method_from_file_manager");
	var select = popup.find(".method select");
	
	var value = select.val();
	
	//set file path
	setTaskIncludeFileFromSelectedNode(chooseMethodFromFileManagerTree);
	
	//set method
	var dest = $(MyFancyPopup.settings.targetField);
	dest.val(value ? value : "");
	
	if (value) {
		if (dest.parent().hasClass("method_name")) {
			var p = dest.parent().parent();
			var obj_field = p.find(".method_obj_name input");
			var static_field = p.find(".method_static input");
			
			updateObjNameAccordingWithObjectPropertySelection(select, obj_field, static_field);
			
			//if (auto_convert || confirm("Do you wish to update automatically this method arguments?")) {
				var args = getMethodArguments( select.attr("get_file_properties_url"), select.attr("file_path"), select.attr("class_name"), value);
				ProgrammingTaskUtil.setArgs(args, dest.parent().parent().find(".method_args .args"));
			//}
		}
	}
	
	MyFancyPopup.hidePopup();
}

function onProgrammingTaskChooseFunction(elm) {
	elm = $(elm);
	
	MyFancyPopup.init({
		elementToShow: $("#choose_function_from_file_manager"),
		parentElement: document,
		
		targetField: getTargetFieldForProgrammingTaskChooseFromFileManager(elm), //elm.parent().find("input")[0],
		updateFunction: chooseFunction
	});
	
	MyFancyPopup.showPopup();
}

//Note that any change here must be replicated in admin_menu.js:onChooseWorkflowCallFunctionTask
function chooseFunction(elm) {
	var popup = $("#choose_function_from_file_manager");
	var select = popup.find(".function select");
	var value = select.val();
	
	//set file path
	setTaskIncludeFileFromSelectedNode(chooseFunctionFromFileManagerTree);
	
	//set method
	$(MyFancyPopup.settings.targetField).val(value ? value : "");
	
	if (value/* && (auto_convert || confirm("Do you wish to update automatically this function arguments?"))*/) {
		var args = getFunctionArguments( select.attr("get_file_properties_url"), select.attr("file_path"), value);
		ProgrammingTaskUtil.setArgs(args, $(MyFancyPopup.settings.targetField).parent().parent().find(".func_args .args"));
	}
		
	MyFancyPopup.hidePopup();
}

function onProgrammingTaskChooseClassName(elm) {
	elm = $(elm);
	var popup = $("#choose_method_from_file_manager");
	
	var do_not_update_args = $(elm).attr("do_not_update_args");
	do_not_update_args = do_not_update_args == 1 || do_not_update_args == "true" ? 1 : 0;
	
	MyFancyPopup.init({
		elementToShow: popup,
		parentElement: document,
		onOpen: function() {
			popup.find(".method").hide();
		},
		
		targetField: getTargetFieldForProgrammingTaskChooseFromFileManager(elm), //elm.parent().find("input")[0],
		updateFunction: function(element) {
			chooseClassName(element, do_not_update_args);
		},
	});
	
	MyFancyPopup.showPopup();
}

//Note that any change here must be replicated in admin_menu.js:onChooseWorkflowCreateClassObjectTask
function chooseClassName(elm, do_not_update_args) {
	var node = chooseMethodFromFileManagerTree.getSelectedNodes();
	node = node[0];
	
	if (node) {
		var class_name = $(node).children("a").children("label").first().text();
		
		//set file path
		setTaskIncludeFileFromSelectedNode(chooseMethodFromFileManagerTree);
		
		$(MyFancyPopup.settings.targetField).val(class_name ? class_name : "");
		
		if (!do_not_update_args/* && (auto_convert || confirm("Do you wish to update automatically this class arguments?"))*/) {
			var select = $("#choose_method_from_file_manager .method select");
			var file_path = select.attr("file_path");
			var get_file_properties_url = select.attr("get_file_properties_url");
			
			var args = getMethodArguments(get_file_properties_url, file_path, class_name, "__construct");
			ProgrammingTaskUtil.setArgs(args, $(MyFancyPopup.settings.targetField).parent().parent().find(".class_args .args"));
		}
	}
	
	MyFancyPopup.hidePopup();
}

function setTaskIncludeFileFromSelectedNode(tree) {
	var node = tree.getSelectedNodes();
	node = node[0];
	
	if (node) {
		var a = $(node).children("a");
		var file_path = a.attr("file_path");
		var bean_name = a.attr("bean_name");
		var include_path = file_path ? getNodeIncludePath(node, file_path, bean_name) : null;
		
		if (include_path) {
			var include_file = $(MyFancyPopup.settings.targetField).parent().parent().children(".include_file");
			
			if (include_file[0]) {
				include_file.children("input[type=text]").val(include_path);
				include_file.children("input[type=checkbox]").prop("checked", true).attr("checked", "");
			}
		}
	}
}

function onIncludeFileTaskChooseFile(elm) {
	elm = $(elm);
	var popup = $("#choose_file_from_file_manager");
	
	MyFancyPopup.init({
		elementToShow: popup,
		parentElement: document,
		
		targetField: getTargetFieldForProgrammingTaskChooseFromFileManager(elm), //elm.parent().find("input")[0],
		updateFunction: chooseIncludeFile
	});
	
	MyFancyPopup.showPopup();
}

//Note that any change here must be replicated in admin_menu.js:onChooseWorkflowIncludeFileTask
function chooseIncludeFile(elm) {
	var node = chooseFileFromFileManagerTree.getSelectedNodes();
	node = node[0];
	
	if (node) {
		var a = $(node).children("a");
		var file_path = a.attr("file_path");
		var bean_name = a.attr("bean_name");
		var include_path = file_path ? getNodeIncludePath(node, file_path, bean_name) : null;
		
		if (include_path) {
			var input = $(MyFancyPopup.settings.targetField);
			var p = input.parent();
			input.val(include_path);
			p.parent().find(".type select").val("");
			
			//This is for the presentation task: includes and includes_once items and array items.
			if (input.is(".key")) //in case of array items
				p.children(".key_type").val("");
			else if (input.is(".value")) //in case of array items
				p.children(".value_type").val("");
			else if (p.is(".table_arg_value")) //in case of method/functions args
				p.parent().find(".table_arg_type select").val("");
			else {
				p.children(".value_type").val("");
				p.children(".includes_type").val("");
				p.children(".includes_once_type").val("");
			}
			
			MyFancyPopup.hidePopup();
		}
		else {
			alert("invalid selected file.\nPlease choose a valid file.");
		}
	}
}

function onIncludeFolderTaskChooseFile(elm) {
	elm = $(elm);
	var popup = $("#choose_folder_from_file_manager");
	
	MyFancyPopup.init({
		elementToShow: popup,
		parentElement: document,
		
		targetField: getTargetFieldForProgrammingTaskChooseFromFileManager(elm), //elm.parent().find("input")[0],
		updateFunction: chooseIncludeFolder
	});
	
	MyFancyPopup.showPopup();
}

function chooseIncludeFolder(elm) {
	var node = chooseFolderFromFileManagerTree.getSelectedNodes();
	node = node[0];
	
	if (node) {
		var a = $(node).children("a");
		var i = a.children("i.folder, .entities_folder, .views_folder, .templates_folder, .template_folder, .utils_folder, .webroot_folder, .modules_folder, .blocks_folder, .configs_folder, i.webroot_sub_folder");
		
		if (i.length > 0) {
			var ul = $(node).children("ul");
			var url = ul.attr("url");
			var params = getURLSearchParams(url);
			var bean_name = params["bean_name"] ? params["bean_name"] : params["item_type"];
			var folder_path = params["path"];
			
			var include_path = getNodeIncludeFolderPath(node, folder_path, bean_name);
			
			if (include_path) {
				var input = $(MyFancyPopup.settings.targetField);
				var p = input.parent();
				input.val(include_path);
				p.parent().find(".type select").val("");
				
				//This is for the presentation task: includes and includes_once items and array items.
				if (input.is(".key")) //in case of array items
					p.children(".key_type").val("");
				else if (input.is(".value")) //in case of array items
					p.children(".value_type").val("");
				else if (p.is(".table_arg_value")) //in case of method/function args
					p.parent().find(".table_arg_type select").val("");
				else {
					p.children(".value_type").val("");
					p.children(".includes_type").val("");
					p.children(".includes_once_type").val("");
				}
				
				MyFancyPopup.hidePopup();
			}
		}
		else {
			alert("invalid selected folder.\nPlease choose a valid folder.");
		}
	}
}

function getNodeIncludeFolderPath(node, folder_path, bean_name) {
	var include_path = "";
	
	if (folder_path) {
		if (bean_name == "dao")
			include_path = "DAO_PATH";
		else if (bean_name == "lib")
			include_path = "LIB_PATH";
		else if (bean_name == "vendor")
			include_path = "VENDOR_PATH";
		else if (bean_name == "test_unit")
			include_path = "TEST_UNIT_PATH";
		else if (typeof layer_type == "string" && layer_type == "bl")
			include_path = "$vars['business_logic_path']";
		else if (typeof layer_type == "string" && layer_type == "pres") {
			var project_path = node ? getNodeProjectPath(node) : "";
			project_path = project_path && project_path.substr(project_path.length - 1) == "/" ? project_path.substr(0, project_path.length - 1) : project_path;
			
			if (!project_path)
				include_path = "$EVC->getPresentationLayer()->getLayerPathSetting()";
			else {
				var project_arg = project_path == selected_project_id ? "" : "\"" + project_path + "\"";
				var pos;
				
				if ( (pos = folder_path.indexOf("/src/entity/")) != -1) {
					folder_path = folder_path.substr(pos + 12);
					include_path = "$EVC->getEntitiesPath(" + project_arg + ")";
				}
				else if ( (pos = folder_path.indexOf("/src/view/")) != -1) {
					folder_path = folder_path.substr(pos + 10);
					console.log("folder_path:"+folder_path);
					include_path = "$EVC->getViewsPath(" + project_arg + ")";
				}
				else if ( (pos = folder_path.indexOf("/src/template/")) != -1) {
					folder_path = folder_path.substr(pos + 14);
					include_path = "$EVC->getTemplatesPath(" + project_arg + ")";
				}
				else if ( (pos = folder_path.indexOf("/src/controller/")) != -1) {
					folder_path = folder_path.substr(pos + 16);
					include_path = "$EVC->getControllersPath(" + project_arg + ")";
				}
				else if ( (pos = folder_path.indexOf("/src/util/")) != -1) {
					folder_path = folder_path.substr(pos + 10);
					include_path = "$EVC->getUtilsPath(" + project_arg + ")";
				}
				else if ( (pos = folder_path.indexOf("/src/config/")) != -1) {
					folder_path = folder_path.substr(pos + 12);
					include_path = "$EVC->getConfigsPath(" + project_arg + ")";
				}
				else if ( (pos = folder_path.indexOf("/webroot/")) != -1) {
					folder_path = folder_path.substr(pos + 9);
					include_path = "$EVC->getWebrootPath(" + project_arg + ")";
				}
				else if ( (pos = folder_path.indexOf("/src/block/")) != -1) {
					folder_path = folder_path.substr(pos + 11);
					include_path = "$EVC->getBLocksPath(" + project_arg + ")";
				}
				else if ( (pos = folder_path.indexOf("/src/module/")) != -1) {
					folder_path = folder_path.substr(pos + 12);
					include_path = "$EVC->getModulesPath(" + project_arg + ")";
				}
				else
					include_path = "$EVC->getPresentationLayer()->getLayerPathSetting()";
			}
		}
		else if (getBeanItemType(bean_name)) { //to be used by the testunit pages and deployment
			var broker_name = getBeanBrokerName(bean_name);
			
			if (layer_brokers_settings["business_logic_brokers_obj"][broker_name])
				include_path = layer_brokers_settings["business_logic_brokers_obj"][broker_name] + "->getLayerPathSetting()";
			else if (layer_brokers_settings["presentation_brokers_obj"][broker_name])
				include_path = layer_brokers_settings["presentation_brokers_obj"][broker_name] + "->getLayerPathSetting()";
		}
		
		if (include_path && folder_path)
			include_path += " . \"" + folder_path + "\"";
	}
	
	return include_path;
}

function getNodeIncludePath(node, file_path, bean_name) {
	var include_path = "";
		
	if (file_path) {
		if (bean_name == "dao")
			include_path = "DAO_PATH . \"" + file_path + "\"";
		else if (bean_name == "lib")
			include_path = "LIB_PATH . \"" + file_path + "\"";
		else if (bean_name == "vendor")
			include_path = "VENDOR_PATH . \"" + file_path + "\"";
		else if (bean_name == "test_unit")
			include_path = "TEST_UNIT_PATH . \"" + file_path + "\"";
		else if (typeof layer_type == "string" && layer_type == "bl")
			include_path = "$vars['business_logic_path'] . \"" + file_path + "\"";
		else if (typeof layer_type == "string" && layer_type == "pres") {
			var project_path = node ? getNodeProjectPath(node) : "";
			project_path = project_path && project_path.substr(project_path.length - 1) == "/" ? project_path.substr(0, project_path.length - 1) : project_path;
			
			if (!project_path)
				include_path = "$EVC->getPresentationLayer()->getLayerPathSetting() . \"" + file_path + "\"";
			else {
				var project_arg = project_path == selected_project_id ? "" : ", \"" + project_path + "\"";
				var pos;
				
				if ( (pos = file_path.indexOf("/src/entity/")) != -1) {
					file_path = file_path.substr(pos + 12);
					file_path = file_path.substr(file_path.length - 4, 1) == "." ? file_path.substr(0, file_path.lastIndexOf(".")) : file_path;
					include_path = "$EVC->getEntityPath(\"" + file_path + "\"" + project_arg + ")";
				}
				else if ( (pos = file_path.indexOf("/src/view/")) != -1) {
					file_path = file_path.substr(pos + 10);
					file_path = file_path.substr(file_path.length - 4, 1) == "." ? file_path.substr(0, file_path.lastIndexOf(".")) : file_path;
					include_path = "$EVC->getViewPath(\"" + file_path + "\"" + project_arg + ")";
				}
				else if ( (pos = file_path.indexOf("/src/template/")) != -1) {
					file_path = file_path.substr(pos + 14);
					file_path = file_path.substr(file_path.length - 4, 1) == "." ? file_path.substr(0, file_path.lastIndexOf(".")) : file_path;
					include_path = "$EVC->getTemplatePath(\"" + file_path + "\"" + project_arg + ")";
				}
				else if ( (pos = file_path.indexOf("/src/controller/")) != -1) {
					file_path = file_path.substr(pos + 16);
					file_path = file_path.substr(file_path.length - 4, 1) == "." ? file_path.substr(0, file_path.lastIndexOf(".")) : file_path;
					include_path = "$EVC->getControllerPath(\"" + file_path + "\"" + project_arg + ")";
				}
				else if ( (pos = file_path.indexOf("/src/util/")) != -1) {
					file_path = file_path.substr(pos + 10);
					file_path = file_path.substr(file_path.length - 4, 1) == "." ? file_path.substr(0, file_path.lastIndexOf(".")) : file_path;
					include_path = "$EVC->getUtilPath(\"" + file_path + "\"" + project_arg + ")";
				}
				else if ( (pos = file_path.indexOf("/src/config/")) != -1) {
					file_path = file_path.substr(pos + 12);
					file_path = file_path.substr(file_path.length - 4, 1) == "." ? file_path.substr(0, file_path.lastIndexOf(".")) : file_path;
					include_path = "$EVC->getConfigPath(\"" + file_path + "\"" + project_arg + ")";
				}
				else if ( (pos = file_path.indexOf("/webroot/")) != -1) {
					file_path = file_path.substr(pos + 9);
					project_arg = project_arg.substr(0, 2) == ", " ? project_arg.substr(2) : project_arg;
					include_path = "$EVC->getWebrootPath(" + project_arg + ") . \"" + file_path + "\"";
				}
				else if ( (pos = file_path.indexOf("/src/block/")) != -1) {
					file_path = file_path.substr(pos + 11);
					file_path = file_path.substr(file_path.length - 4, 1) == "." ? file_path.substr(0, file_path.lastIndexOf(".")) : file_path;
					include_path = "$EVC->getBLockPath(\"" + file_path + "\"" + project_arg + ")";
				}
				else if ( (pos = file_path.indexOf("/src/module/")) != -1) {
					file_path = file_path.substr(pos + 12);
					file_path = file_path.substr(file_path.length - 4, 1) == "." ? file_path.substr(0, file_path.lastIndexOf(".")) : file_path;
					include_path = "$EVC->getModulePath(\"" + file_path + "\"" + project_arg + ")";
				}
				else
					include_path = "$EVC->getPresentationLayer()->getLayerPathSetting() . \"" + file_path + "\"";
			}
		}
		else if (getBeanItemType(bean_name)) { //to be used by the testunit pages and deployment
			var broker_name = getBeanBrokerName(bean_name);
			
			if (layer_brokers_settings["business_logic_brokers_obj"][broker_name])
				include_path = layer_brokers_settings["business_logic_brokers_obj"][broker_name] + "->getLayerPathSetting() . \"" + file_path + "\"";
			else if (layer_brokers_settings["presentation_brokers_obj"][broker_name])
				include_path = layer_brokers_settings["presentation_brokers_obj"][broker_name] + "->getLayerPathSetting() . \"" + file_path + "\"";
		}
	}
	
	return include_path;
}

function getNodeProjectPath(node) {
	var parent_li = $(node);
	
	do {
		parent_li = parent_li.parent().parent();
		
		if (parent_li) {
			var a = parent_li.children("a");
			
			if (a.children("i.project")[0] || a.children("i.project_common")[0])
				return parent_li.children("a").attr("project_path");
		}
	}
	while (parent_li && parent_li[0]);
	
	return "";
}

function onIncludeBlockTaskChooseFile(elm) {
	var popup = $("#choose_block_from_file_manager");
	
	MyFancyPopup.init({
		elementToShow: popup,
		parentElement: document,
		
		targetField: $(elm).parent().parent(),
		updateFunction: chooseIncludeBlock
	});
	
	MyFancyPopup.showPopup();
}

function chooseIncludeBlock(elm) {
	var node = chooseBlockFromFileManagerTree.getSelectedNodes();
	node = node[0];
	
	if (node) {
		var a = $(node).children("a");
		var file_path = a.attr("file_path");
		var bean_name = a.attr("bean_name");
		var pos = file_path ? file_path.indexOf("/src/block/") : -1;
		
		if (file_path && pos != -1) {
			var project_path = getNodeProjectPath(node);
			project_path = project_path && project_path.substr(project_path.length - 1) == "/" ? project_path.substr(0, project_path.length - 1) : project_path;
			project_path = project_path == selected_project_id ? "" : project_path;
			
			var block_path = file_path.substr(pos + 11);//11 == /src/block/
			block_path = block_path.substr(block_path.length - 4, 1) == "." ? block_path.substr(0, block_path.lastIndexOf(".")) : block_path;
			
			var project = MyFancyPopup.settings.targetField.children(".project");
			project.children("input").hide();
			project.children("select.project").show();
			project.children("select.project").val(project_path);
			project.children("select.type").val("options");
			
			var block = MyFancyPopup.settings.targetField.children(".block");
			block.children("input").val(block_path);
			block.children("select").val("string");
			
			MyFancyPopup.hidePopup();
		}
		else {
			alert("invalid selected file.\nPlease choose a valid file.");
		}
	}
}

function onIncludeViewTaskChooseFile(elm) {
	var popup = $("#choose_view_from_file_manager");
	
	MyFancyPopup.init({
		elementToShow: popup,
		parentElement: document,
		
		targetField: $(elm).parent().parent(),
		updateFunction: chooseIncludeView
	});
	
	MyFancyPopup.showPopup();
}

function chooseIncludeView(elm) {
	var node = chooseViewFromFileManagerTree.getSelectedNodes();
	node = node[0];
	
	if (node) {
		var a = $(node).children("a");
		var file_path = a.attr("file_path");
		var bean_name = a.attr("bean_name");
		var pos = file_path ? file_path.indexOf("/src/view/") : -1;
		
		if (file_path && pos != -1) {
			var project_path = getNodeProjectPath(node);
			project_path = project_path && project_path.substr(project_path.length - 1) == "/" ? project_path.substr(0, project_path.length - 1) : project_path;
			project_path = project_path == selected_project_id ? "" : project_path;
			
			var view_path = file_path.substr(pos + 10);//10 == /src/view/
			view_path = view_path.substr(view_path.length - 4, 1) == "." ? view_path.substr(0, view_path.lastIndexOf(".")) : view_path;
			
			var project = MyFancyPopup.settings.targetField.children(".project");
			project.children("input").hide();
			project.children("select.project").show();
			project.children("select.project").val(project_path);
			project.children("select.type").val("options");
			
			var view = MyFancyPopup.settings.targetField.children(".view");
			view.children("input").val(view_path);
			view.children("select").val("string");
			
			MyFancyPopup.hidePopup();
		}
		else {
			alert("invalid selected file.\nPlease choose a valid file.");
		}
	}
}

//target_field is used by the workflow task: GetUrlContentsTaskPropertyObj
function onIncludePageUrlTaskChooseFile(elm) {
	elm = $(elm);
	var popup = $("#choose_page_url_from_file_manager");
	var target = getTargetFieldForProgrammingTaskChooseFromFileManager(elm); //elm.parent().children("input");
	
	onUrlQueryString(elm, popup, target);
	
	IncludePageUrlFancyPopup.init({
		elementToShow: popup,
		parentElement: document,
		onOpen: function() {
			var html = popup.find(".mytree ul").html();
			if (!html) {
				updateLayerUrlFileManager( popup.find(".broker select")[0] );
			}
		},
		
		targetField: target,
		updateFunction: function(element) {
			chooseIncludePageUrl(element);
		}
	});
	
	IncludePageUrlFancyPopup.showPopup();
}

function chooseIncludePageUrl(elm) {
	//prepare query string if exists
	var popup = $("#choose_page_url_from_file_manager");
	var exists_query_string_attributes = popup.find(".query_string_attributes").length > 0;
	
	//prepare selected node
	var node = choosePageUrlFromFileManagerTree.getSelectedNodes();
	node = node[0];
	
	if (node) {
		var a = $(node).children("a");
		var i = a.children("i").first();
		var is_folder = i.hasClass("folder");
		var file_path = is_folder ? a.attr("folder_path") : a.attr("file_path");
		
		if (file_path) {
			var bean_name = a.attr("bean_name");	
			var is_inside_of_webroot = file_path.indexOf("/webroot/") != -1;
			var pos = file_path.indexOf(is_inside_of_webroot ? "/webroot/" : "/src/entity/");
			var is_page = is_inside_of_webroot || is_folder || i.hasClass("entity_file") || pos != -1;
			
			if (!is_page) 
				alert("Selected item must be a valid page or webroot file!\nPlease try again...");
			else {
				var project_path = getNodeProjectPath(node);
				project_path = project_path && project_path.substr(project_path.length - 1) == "/" ? project_path.substr(0, project_path.length - 1) : project_path;
				project_path = project_path == selected_project_id ? "" : project_path + "/";
				
				var entity_path = is_inside_of_webroot ? file_path.substr(pos + ("/webroot/").length) : file_path.substr(pos + ("/src/entity/").length);
				
				if (!is_folder && !is_inside_of_webroot) {
					entity_path = entity_path.substr(entity_path.length - 4, 1) == "." ? entity_path.substr(0, entity_path.lastIndexOf(".")) : entity_path;
					
					var pos = entity_path.lastIndexOf("/");
					pos = pos == -1 ? 0 : pos + 1;
					if (entity_path.substr(pos).toLowerCase() == "index")
						entity_path = entity_path.substr(0, pos);
				}
				
				var url_str = project_path == "common/" ? "project_common_url_prefix" : "project_url_prefix";
				var url = IncludePageUrlFancyPopup.settings.is_code_html_base ? "<?= $" + url_str + " ?>" : "{$" + url_str + "}";
				url += project_path == "common/" ? "" : project_path;
				var selected_project_name = project_path ? project_path.replace(/\/+$/g, "") : selected_project_id;
				
				//used in the testunit/edit_test.php
				if (typeof layers_projects_urls != "undefined" && $.isPlainObject(layers_projects_urls) && layers_projects_urls.hasOwnProperty(bean_name) && $.isPlainObject(layers_projects_urls[bean_name]) && layers_projects_urls[bean_name].hasOwnProperty(selected_project_name) && layers_projects_urls[bean_name][selected_project_name])
					url = layers_projects_urls[bean_name][selected_project_name];
				
				url += entity_path;
				
				//add query string
				if (exists_query_string_attributes)
					url = getUrlWithQueryStringFromAttributes(popup, url);
				else {
					var previous_url = IncludePageUrlFancyPopup.settings.targetField.val();
					var m = previous_url ? previous_url.match(/[^<]\?[^>]/) : null;
					var query_string = m ? previous_url.substr(m.index + 2) : ""; //m.index is the char before the "?"
					
					if (query_string != "") 
						url += "?" + query_string;
					else {
						var url_suffix = IncludePageUrlFancyPopup.settings.targetField.attr("url_suffix");
						url += url_suffix ? url_suffix : "";
					}
				}
				
				//set url in target
				IncludePageUrlFancyPopup.settings.targetField.val(url);
				IncludePageUrlFancyPopup.settings.targetField.focus(); //if IncludePageUrlFancyPopup.settings.targetField is an input from the LayoutUIEditor, then we must set the cursor inside of that input, bc the value will onnly be updated in the html, with the onBlur event of that input. So we must first to focus it or trigger the onBlur event for that input element.
				
				//update var_Type if exists
				var var_type = IncludePageUrlFancyPopup.settings.targetField.parent().parent().find(".var_type select");
				
				if (var_type.length > 0) {
					var_type.val("string");
					var_type.trigger("change");
				}
				else if (IncludePageUrlFancyPopup.settings.targetField.is(".key")) //in case of array items
					IncludePageUrlFancyPopup.settings.targetField.parent().children(".key_type").val("string");
				else if (IncludePageUrlFancyPopup.settings.targetField.is(".value")) //in case of array items
					IncludePageUrlFancyPopup.settings.targetField.parent().children(".value_type").val("string");
				else if (IncludePageUrlFancyPopup.settings.targetField.parent().is(".table_arg_value")) //in case of method/function args
					IncludePageUrlFancyPopup.settings.targetField.parent().parent().find(".table_arg_type select").val("string");
				
				IncludePageUrlFancyPopup.hidePopup();
			}
		}
		else
			alert("invalid selected file.\nPlease choose a valid file.");
	}
	else if (exists_query_string_attributes) { //update url with user query string in target event if no node selected. If no node selected, keep original url from target.
		var url = IncludePageUrlFancyPopup.settings.targetField.val();
		var pos = url.indexOf("?");
		
		//remove query string from url
		if (pos != -1) 
			url = url.substr(0, pos);
		
		//add user query string to url
		url = getUrlWithQueryStringFromAttributes(popup, url);
		
		//set url in target
		IncludePageUrlFancyPopup.settings.targetField.val(url);
		IncludePageUrlFancyPopup.settings.targetField.focus(); //if IncludePageUrlFancyPopup.settings.targetField is an input from the LayoutUIEditor, then we must set the cursor inside of that input, bc the value will onnly be updated in the html, with the onBlur event of that input. So we must first to focus it or trigger the onBlur event for that input element.
		
		//update var_Type if exists
		var var_type = IncludePageUrlFancyPopup.settings.targetField.parent().parent().find(".var_type select");
		var_type.val("string");
		var_type.trigger("change");
		
		IncludePageUrlFancyPopup.hidePopup();
	}
}

function onUrlQueryString(elm, popup, target) {
	//clean query_string_attributes
	var query_string_attributes = popup.find(".query_string_attributes");
	query_string_attributes.find(" > ul > li:not(.empty_query_string_attributes)").remove();
	
	//prepare url
	var url = target.val();
	
	if (url) {
		var add_icon = popup.find(".query_string_attributes .icon.add");
		var params = getURLSearchParams(url);
		
		for (var param_name in params) {
			var param_value = params[param_name];
			
			var item = addUrlQueryStringAttribute(add_icon[0]);
			item.find("input.attribute_name").val(param_name);
			item.find("input.attribute_value").val(param_value);
		}
	}
}

function getURLSearchParams(url) {
	var params = {};
	
	try {
		//try to get parameteres through URL
		var url_obj = new URL(url);
		
		if (url_obj)
			url_obj.searchParams.forEach(function(param_value, param_name) {
				params[param_name] = param_value;
			});
	}
	catch(e) {
		//If url is invalid for 'new URL' object, then try to get parameters through regex
		var pos = url.indexOf("?");
		
		if (pos != -1) {
			var query_string = url.substr(pos + 1);
			
			if (query_string) {
				var regex = /&?([^=&]+)=([^&]*)&?/g;
				var m;
				
				while ((m = regex.exec(query_string)) !== null) {
					var param_name = m[1];
					var param_value = m[2];
					
					params[param_name] = param_value;
				}
			}
		}
	}
	
	return params;
}

function addUrlQueryStringAttribute(elm) {
	var ul = $(elm).parent().closest(".query_string_attributes").children("ul");
	ul.children(".empty_query_string_attributes").hide();
	
	var on_click_func = typeof ProgrammingTaskUtil != "undefined" && ProgrammingTaskUtil.on_programming_task_choose_created_variable_callback ? "ProgrammingTaskUtil.on_programming_task_choose_created_variable_callback" : "onProgrammingTaskChooseCreatedVariableForUrlQueryStringAttribute";
	
	var html = '<li class="query_string_attribute">'
				+ '<input class="attribute_name" placeHolder="Attribute name" />'
				+ ' = '
				+ '<input class="attribute_value" placeHolder="Attribute value" />'
				+ '<span class="icon add_variable" onClick="' + on_click_func + '(this)"></span>'
				+ '<span class="icon remove" onClick="removeUrlQueryStringAttribute(this)">Remove</span>'
			+ '</li>';
	var attribute = $(html);
	
	ul.append(attribute);
	
	return attribute;
}

function removeUrlQueryStringAttribute(elm) {
	var li = $(elm).parent().closest("li");
	var ul = li.parent();
	
	li.remove();
	
	if (ul.children("li:not(.empty_query_string_attributes)").length == 0)
		ul.children("li.empty_query_string_attributes").show();
}

function getUrlQueryStringFromAttributes(popup) {
	var query_string = "";
	
	if (popup && popup[0]) {
		var query_string_attributes = popup.find(".query_string_attributes");
		
		if (query_string_attributes[0]) {
			var lis = query_string_attributes.find(" > ul > li:not(.empty_query_string_attributes)");
			
			$.each(lis, function(idx, li) {
				li = $(li);
				var attribute_name = li.find("input.attribute_name").val();
				var attribute_value = li.find("input.attribute_value").val();
				
				if (attribute_name.replace(/^\s+/g, "").replace(/\s+$/g, "").length > 0)
					query_string += (query_string ? "&" : "") + attribute_name + "=" + attribute_value;
			});
		}
	}
	
	return query_string;
}

function getUrlWithQueryStringFromAttributes(popup, url) {
	var query_string = getUrlQueryStringFromAttributes(popup);
	
	if (query_string)
		url += (url.indexOf("?") != -1 ? "&" : "?") + query_string;
	
	return url;
}

function onProgrammingTaskChooseCreatedVariableForUrlQueryStringAttribute(elm) {
	var popup = $("#choose_property_variable_from_file_manager");
	var select = popup.find(" > .type > select");
	select.val("new_var");
	
	onProgrammingTaskChooseCreatedVariable(elm);
	
	//hide option 2 bc it doesn't matter
	var class_prop_var_option = select.children("option[value=class_prop_var]");
	class_prop_var_option.hide();
	
	MyFancyPopup.settings.onClose = function() {
		class_prop_var_option.show();
	};
}

//target_field is used by the workflow task: GetUrlContentsTaskPropertyObj
function onIncludeImageUrlTaskChooseFile(elm) {
	elm = $(elm);
	var popup = $("#choose_image_url_from_file_manager");
	
	MyFancyPopup.init({
		elementToShow: popup,
		parentElement: document,
		onOpen: function() {
			var html = popup.find(".mytree ul").html();
			if (!html) {
				updateLayerUrlFileManager( popup.find(".broker select")[0] );
			}
		},
		
		targetField: getTargetFieldForProgrammingTaskChooseFromFileManager(elm), //elm.parent().children("input"),
		updateFunction: function(elm) {
			chooseIncludeImageUrl(elm);
		}
	});
	
	MyFancyPopup.showPopup();
}

function chooseIncludeImageUrl(elm) {
	var node = chooseImageUrlFromFileManagerTree.getSelectedNodes();
	node = node[0];
	
	if (node) {
		var a = $(node).children("a");
		var i = a.children("i").first();
		var is_folder = i.hasClass("folder");
		var file_path = is_folder ? a.attr("folder_path") : a.attr("file_path");
		
		if (file_path) {
			var bean_name = a.attr("bean_name");	
			var page_pos = file_path.indexOf("/src/entity/");
			var webroot_pos = file_path.indexOf("/webroot/");
			var is_possible_img = is_folder || i.hasClass("entity_file") || page_pos != -1 || webroot_pos != -1;
			
			if (!is_possible_img || !file_path)
				alert("Selected item must be a valid file!\nPlease try again...");
			else {
				var project_path = getNodeProjectPath(node);
				project_path = project_path && project_path.substr(project_path.length - 1) == "/" ? project_path.substr(0, project_path.length - 1) : project_path;
				project_path = project_path == selected_project_id ? "" : project_path + "/";
				
				var img_path = file_path;
				
				if (page_pos != -1) {
					img_path = file_path.substr(page_pos + ("/src/entity/").length);
					
					if (!is_folder) {
						img_path = img_path.substr(img_path.length - 4, 1) == "." ? img_path.substr(0, img_path.lastIndexOf(".")) : img_path;
						
						var pos = img_path.lastIndexOf("/");
						pos = pos == -1 ? 0 : pos + 1;
						if (img_path.substr(pos).toLowerCase() == "index")
							img_path = img_path.substr(0, pos);
					}
				}
				else if (webroot_pos != -1)
					img_path = file_path.substr(webroot_pos + ("/webroot/").length);
				else if (project_path == "common/")
					img_path = file_path.substr(project_path.length);
				
				var url_str = project_path == "common/" ? "project_common_url_prefix" : "project_url_prefix";
				var url = MyFancyPopup.settings.is_code_html_base ? "<?= $" + url_str + " ?>" : "{$" + url_str + "}";
				url += project_path == "common/" ? "" : project_path;
				var selected_project_name = project_path ? project_path.replace(/\/+$/g, "") : selected_project_id;
				
				//used in the testunit/edit_test.php
				if (typeof layers_projects_urls != "undefined" && $.isPlainObject(layers_projects_urls) && layers_projects_urls.hasOwnProperty(bean_name) && $.isPlainObject(layers_projects_urls[bean_name]) && layers_projects_urls[bean_name].hasOwnProperty(selected_project_name) && layers_projects_urls[bean_name][selected_project_name])
					url = layers_projects_urls[bean_name][selected_project_name];
				
				var previous_url = MyFancyPopup.settings.targetField.val();
				var m = previous_url ? previous_url.match(/[^<]\?[^>]/) : null;
				var query_string = m ? previous_url.substr(m.index + 2) : ""; //m.index is the char before the "?"
				url += img_path;
				
				if (query_string != "") 
					url += "?" + query_string;
				else {
					var url_suffix = MyFancyPopup.settings.targetField.attr("url_suffix");
					url += url_suffix ? url_suffix : "";
				}
				
				MyFancyPopup.settings.targetField.val(url);
				
				//if MyFancyPopup.settings.targetField is an input from the LayoutUIEditor, then we must set the cursor inside of that input, bc the value will only be updated in the html, with the onBlur event of that input. So we must first to focus it or trigger the onBlur event for that input element.
				MyFancyPopup.settings.targetField.focus(); 
				MyFancyPopup.settings.targetField.blur(); //to refresh the image in the canvas editor
				MyFancyPopup.settings.targetField.focus(); //focus again so the cursor be inside of the input field
				
				//update var_Type if exists
				var var_type = MyFancyPopup.settings.targetField.parent().parent().find(".var_type select");
				
				if (var_type.length > 0) {
					var_type.val("string");
					var_type.trigger("change");
				}
				else if (MyFancyPopup.settings.targetField.is(".key")) //in case of array items
					MyFancyPopup.settings.targetField.parent().children(".key_type").val("string");
				else if (MyFancyPopup.settings.targetField.is(".value")) //in case of array items
					MyFancyPopup.settings.targetField.parent().children(".value_type").val("string");
				else if (MyFancyPopup.settings.targetField.parent().is(".table_arg_value")) //in case of method/function args
					MyFancyPopup.settings.targetField.parent().parent().find(".table_arg_type select").val("string");
				
				MyFancyPopup.hidePopup();
			}
		}
		else
			alert("invalid selected file.\nPlease choose a valid file.");
	}
}

//target_field is used by the workflow task: GetUrlContentsTaskPropertyObj
function onIncludeWebrootFileUrlTaskChooseFile(elm) {
	elm = $(elm);
	var popup = $("#choose_webroot_file_url_from_file_manager");
	
	MyFancyPopup.init({
		elementToShow: popup,
		parentElement: document,
		onOpen: function() {
			var html = popup.find(".mytree ul").html();
			if (!html) {
				updateLayerUrlFileManager( popup.find(".broker select")[0] );
			}
		},
		
		targetField: getTargetFieldForProgrammingTaskChooseFromFileManager(elm), //elm.parent().children("input"),
		updateFunction: function(elm) {
			chooseIncludeWebrootFileUrl(elm);
		}
	});
	
	MyFancyPopup.showPopup();
}

function chooseIncludeWebrootFileUrl(elm) {
	var node = chooseWebrootFileUrlFromFileManagerTree.getSelectedNodes();
	node = node[0];
	
	if (node) {
		var a = $(node).children("a");
		var i = a.children("i").first();
		var is_folder = i.hasClass("folder");
		var file_path = is_folder ? a.attr("folder_path") : a.attr("file_path");
		
		if (file_path) {
			var bean_name = a.attr("bean_name");
			var webroot_pos = file_path.indexOf("/webroot/");
			var is_possible_file = !is_folder && webroot_pos != -1;
			
			if (!is_possible_file)
				alert("Selected item must be a valid file!\nPlease try again...");
			else {
				var project_path = getNodeProjectPath(node);
				project_path = project_path && project_path.substr(project_path.length - 1) == "/" ? project_path.substr(0, project_path.length - 1) : project_path;
				project_path = project_path == selected_project_id ? "" : project_path + "/";
				
				var new_file_path = file_path.substr(webroot_pos + ("/webroot/").length);
				
				var url_str = project_path == "common/" ? "project_common_url_prefix" : "project_url_prefix";
				var url = MyFancyPopup.settings.is_code_html_base ? "<?= $" + url_str + " ?>" : "{$" + url_str + "}";
				url += project_path == "common/" ? "" : project_path;
				var selected_project_name = project_path ? project_path.replace(/\/+$/g, "") : selected_project_id;
				
				//used in the testunit/edit_test.php
				if (typeof layers_projects_urls != "undefined" && $.isPlainObject(layers_projects_urls) && layers_projects_urls.hasOwnProperty(bean_name) && $.isPlainObject(layers_projects_urls[bean_name]) && layers_projects_urls[bean_name].hasOwnProperty(selected_project_name) && layers_projects_urls[bean_name][selected_project_name])
					url = layers_projects_urls[bean_name][selected_project_name];
				
				var previous_url = MyFancyPopup.settings.targetField.val();
				var m = previous_url ? previous_url.match(/[^<]\?[^>]/) : null;
				var query_string = m ? previous_url.substr(m.index + 2) : ""; //m.index is the char before the "?"
				url += new_file_path;
				
				if (query_string != "") 
					url += "?" + query_string;
				else {
					var url_suffix = MyFancyPopup.settings.targetField.attr("url_suffix");
					url += url_suffix ? url_suffix : "";
				}
				
				MyFancyPopup.settings.targetField.val(url);
				
				//if MyFancyPopup.settings.targetField is an input from the LayoutUIEditor, then we must set the cursor inside of that input, bc the value will only be updated in the html, with the onBlur event of that input. So we must first to focus it or trigger the onBlur event for that input element.
				MyFancyPopup.settings.targetField.focus(); 
				MyFancyPopup.settings.targetField.blur(); //to refresh the image in the canvas editor
				MyFancyPopup.settings.targetField.focus(); //focus again so the cursor be inside of the input field
				
				//update var_Type if exists
				var var_type = MyFancyPopup.settings.targetField.parent().parent().find(".var_type select");
				
				if (var_type.length > 0) {
					var_type.val("string");
					var_type.trigger("change");
				}
				else if (MyFancyPopup.settings.targetField.is(".key")) //in case of array items
					MyFancyPopup.settings.targetField.parent().children(".key_type").val("string");
				else if (MyFancyPopup.settings.targetField.is(".value")) //in case of array items
					MyFancyPopup.settings.targetField.parent().children(".value_type").val("string");
				else if (MyFancyPopup.settings.targetField.parent().is(".table_arg_value")) //in case of method/function args
					MyFancyPopup.settings.targetField.parent().parent().find(".table_arg_type select").val("string");
				
				MyFancyPopup.hidePopup();
			}
		}
		else
			alert("invalid selected file.\nPlease choose a valid file.");
	}
}

function onBusinessLogicTaskChooseBusinessLogic(elm) {
	var popup = $("#choose_business_logic_from_file_manager");
	
	MyFancyPopup.init({
		elementToShow: popup,
		parentElement: document,
		onOpen: function() {
			var html = popup.find(".mytree ul").html();
			if (!html) {
				updateLayerUrlFileManager( popup.find(".broker select")[0] );
			}
		},
		
		targetField: $(elm).parent().parent(),
		updateFunction: chooseBusinessLogic
	});
	
	MyFancyPopup.showPopup();
}

//Note that any change here must be replicated in admin_menu.js:onChooseWorkflowCallBusinessLogicTask
function chooseBusinessLogic(elm) {
	var popup = $("#choose_business_logic_from_file_manager");
	var select = popup.find(".businesslogic select");
	
	var class_name = select.attr("class_name");
	var service_id = (class_name ? class_name + "." : "") + select.val();
	
	var file_path = select.attr("file_path");
	var module_id = file_path.lastIndexOf("/") != -1 ? file_path.substr(0, file_path.lastIndexOf("/")) : file_path;
	module_id = module_id.replace(/\//g, ".");
	
	var main_div = $(MyFancyPopup.settings.targetField);
	
	//PREPARING MODULE ID
	var module = $(MyFancyPopup.settings.targetField).find(".module_id");
	module.find("input").val(module_id);
	module.find("select").val("string");
	
	//PREPARING SERVICE ID
	var service = $(MyFancyPopup.settings.targetField).find(".service_id");
	service.find("input").val(service_id);
	service.find("select").val("string");
	
	//PREPARING PARAMETERS
	var select = popup.find(".broker select")[0];
	var option = select.options[ select.selectedIndex ];
	var bean_file_name = option.getAttribute("bean_file_name");
	var bean_name = option.getAttribute("bean_name");
	var selected_option_text = $(option).text();
	
	updateBusinessLogicParams(main_div, bean_file_name, bean_name, file_path, service_id);
	
	//update the selected broker
	var select = main_div.find(".broker_method_obj select");
	var exists = false;
	for (var i = 0; i < select[0].options.length; i++) {
		var option = select[0].options[i];
		
		if (option.value.indexOf('("' + selected_option_text + '")') != -1) {
			select.val( option.value );
			BrokerOptionsUtilObj.onBrokerChange(select[0]);
			exists = true;
			break;
		}
	}
	
	//in case of being already in the business logic layer
	if (!exists) {
		select.val("this->getBusinessLogicLayer()");
		BrokerOptionsUtilObj.onBrokerChange(select[0]);
	}
	
	MyFancyPopup.hidePopup();
}

function updateBusinessLogicParams(main_div, bean_file_name, bean_name, file_path, service_id) {
	var attrs = getBusinessLogicParameters(bean_file_name, bean_name, file_path, service_id);
	attrs = attrs ? attrs : {};
	
	var params_elm = main_div.children(".params");

	var parameters = params_elm.children(".parameters");
	parameters.html("");

	var parameters_type = params_elm.children(".parameters_type");
	parameters_type.val("array");
	CallBusinessLogicTaskPropertyObj.onChangeParametersType(parameters_type[0]);
	
	drawBusinessLogicParamsItems(parameters, attrs);
}

function drawBusinessLogicParamsItems(array_items, attrs) {
	var items = array_items.children(".items").first();
	var add_item = items.children(".item_add");
	var add_group = items.children(".group_add");
	var ul = array_items.children("ul");

	for (var pname in attrs) {
		var ptype = attrs[pname];
		var item = ul.children(".item").last();
		
		if ($.isPlainObject(ptype)) {
			if (item[0])
				item.remove();
			
			add_group.click();
			item = ul.children("li:not(.item)").last();
			var item_items = item.children(".items").first();
			item_items.children(".key").val(pname);
			item_items.children(".key_type").val("string");
			
			drawBusinessLogicParamsItems(item, ptype);
		}
		else {
			if (!item[0]) {
				add_item.click();
				item = ul.children(".item").last();
			}
			
			item.children(".key").val(pname);
			item.children(".key_type").val("string");
			item.children(".value").val("");
			item.children(".value_type").val(ptype);
		}
		
		add_item.click();
	}

	ul.children(".item").last().remove();
}

function onChooseIbatisQuery(elm) {
	var popup = $("#choose_query_from_file_manager");
	
	MyFancyPopup.init({
		elementToShow: popup,
		parentElement: document,
		onOpen: function() {
			updateDBDriverOnBrokerNameChange( popup.find(".broker select") );
			
			var html = popup.find(".mytree ul").html();
			if (!html) 
				updateLayerUrlFileManager( popup.find(".broker select")[0] );
		},
		
		targetField: $(elm).parent().parent(),
		updateFunction: chooseQuery
	});
	
	MyFancyPopup.showPopup();
}

//Note that any change here must be replicated in admin_menu.js:onChooseWorkflowTaskCallIbatisQuery
function chooseQuery(elm) {
	var popup = $("#choose_query_from_file_manager");
	
	var node = chooseQueryFromFileManagerTree.getSelectedNodes();
	node = node[0];
	
	if (node) {
		var a = $(node).children("a");
		var is_query = a.children("i").first().hasClass("query");
		
		if (!is_query) {
			alert("Selected item must be a query!\nPlease try again...");
		}
		else {
			var query_id = a.children("label").first().text();
			var file_path = a.attr("file_path");
			var query_type = a.attr("query_type");
			var relationship_type = a.attr("relationship_type");
			var hbn_obj_id = a.attr("hbn_obj_id");
		
			var main_div = $(MyFancyPopup.settings.targetField);
		
			//PREPARING MODULE ID
			var module = file_path.lastIndexOf("/") != -1 ? file_path.substr(0, file_path.lastIndexOf("/")) : file_path;
			module = module.replace(/\//g, ".");
			
			var module_id = main_div.children(".module_id");
			module_id.children("input").val(module);
			module_id.children("select").val("string");
			
			//PREPARING SERVICE TYPE
			var service_type = main_div.children(".service_type");
			var service_type_type = service_type.children(".service_type_type");
			service_type_type.val("string");
			CallIbatisQueryTaskPropertyObj.onChangeServiceType(service_type_type[0]);
			service_type.children(".service_type_string").val(query_type.toLowerCase());
			
			//PREPARING SERVICE ID
			var service_id = main_div.children(".service_id");
			service_id.children("input").val(query_id);
			service_id.children("select").val("string");
			
			//PREPARING PARAMETERS
			var select = popup.find(".broker select")[0];
			var option = select.options[ select.selectedIndex ];
			var bean_file_name = option.getAttribute("bean_file_name");
			var bean_name = option.getAttribute("bean_name");
			var selected_option_text = $(option).text();
			var db_driver = popup.find(".db_driver select").val();
			var db_type = popup.find(".type select").val();
			
			updateQueryParams(main_div, bean_file_name, bean_name, db_driver, db_type, file_path, query_type, query_id, hbn_obj_id, relationship_type);
			
			//update the selected broker
			var select = main_div.find(".broker_method_obj select");
			for (var i = 0; i < select[0].options.length; i++) {
				var option = select[0].options[i];
				
				if (option.value.indexOf('("' + selected_option_text + '")') != -1) {
					select.val( option.value );
					BrokerOptionsUtilObj.onBrokerChange(select[0]);
					break;
				}
			}
			
			MyFancyPopup.hidePopup();
		}
	}
}

function updateQueryParams(main_div, bean_file_name, bean_name, db_driver, db_type, file_path, query_type, query_id, hbn_obj_id, relationship_type) {
	var attrs = getQueryParameters(bean_file_name, bean_name, db_driver, db_type, file_path, query_type, query_id, hbn_obj_id, relationship_type);
	attrs = attrs ? attrs : {};

	var params_elm = main_div.children(".params");

	var parameters = params_elm.children(".parameters");
	parameters.html("");

	var parameters_type = params_elm.children(".parameters_type");
	parameters_type.val("array");
	CallIbatisQueryTaskPropertyObj.onChangeParametersType(parameters_type[0]);

	var add_item = parameters.children(".items").first().children(".item_add");
	var ul = parameters.children("ul");

	for (var pname in attrs) {
		var ptype = attrs[pname];
	
		var item = ul.children(".item").last();
	
		item.children(".key").val(pname);
		item.children(".key_type").val("string");
		item.children(".value").val("");
		item.children(".value_type").val(ptype);
	
		add_item.click();
	}

	ul.children(".item").last().remove();
}

function updateLayerUrlFileManager(elm) {
	var option = elm.options[ elm.selectedIndex ];
	var bean_file_name = option.getAttribute("bean_file_name");
	var bean_name = option.getAttribute("bean_name");
	
	var mytree = $(elm).parent().parent().find(".mytree");
	var root_elm = mytree.children("li").first();
	var ul = root_elm.children("ul").first();
	
	root_elm.removeClass("jstree-open").addClass("jstree-closed");
	ul.html("");
	
	var url = ul.attr("layer_url");
	url = url.replace("#bean_file_name#", bean_file_name).replace("#bean_name#", bean_name);
	ul.attr("url", url);
}

function onChooseHibernateObject(elm) {
	var popup = $("#choose_hibernate_object_from_file_manager");
	
	MyFancyPopup.init({
		elementToShow: popup,
		parentElement: document,
		onOpen: function() {
			updateDBDriverOnBrokerNameChange( popup.find(".broker select") );
			
			var html = popup.find(".mytree ul").html();
			if (!html) {
				updateLayerUrlFileManager( popup.find(".broker select")[0] );
			}
		},
		
		targetField: $(elm).parent().parent(),
		updateFunction: chooseHibernateObject,
	});
	
	MyFancyPopup.showPopup();
}

//Note that any change here must be replicated in admin_menu.js:onChooseWorkflowCallHibernateObjectTask
function chooseHibernateObject(elm) {
	var popup = $("#choose_hibernate_object_from_file_manager");
	
	var node = chooseHibernateObjectFromFileManagerTree.getSelectedNodes();
	node = node[0];

	if (node) {
		var a = $(node).children("a");
		var file_path = a.attr("file_path");
		
		if (a.children("i").hasClass("obj") && file_path) {
			var main_div = $(MyFancyPopup.settings.targetField);
			
			//PREPARING MODULE ID
			var module_id = file_path.lastIndexOf("/") != -1 ? file_path.substr(0, file_path.lastIndexOf("/")) : file_path;
			module_id = module_id.replace(/\//g, ".");
			
			main_div.find(".module_id input").val(module_id);
			main_div.find(".module_id select").val("string");
			
			//PREPARING SERVICE ID
			var service_id = a.children("label").first().text();
			
			main_div.find(".service_id input").val(service_id);
			main_div.find(".service_id select").val("string");
		
			//update the selected broker
			var select = popup.find(".broker select")[0];
			var option = select.options[ select.selectedIndex ];
			var selected_option_text = $(option).text();
			
			var select = main_div.find(".broker_method_obj select");
			for (var i = 0; i < select[0].options.length; i++) {
				var option = select[0].options[i];
				
				if (option.value.indexOf('("' + selected_option_text + '")') != -1) {
					select.val( option.value );
					BrokerOptionsUtilObj.onBrokerChange(select[0]);
					break;
				}
			}
			
			MyFancyPopup.hidePopup();
		}
		else {
			alert("You must select a valid Hibernate Object.\nPlease try again...");
		}
	}
}

function onChooseHibernateObjectMethod(elm) {
	var popup = $("#choose_hibernate_object_method_from_file_manager");
	
	MyFancyPopup.init({
		elementToShow: popup,
		parentElement: document,
		onOpen: function() {
			updateDBDriverOnBrokerNameChange( popup.find(".broker select") );
			
			var html = popup.find(".mytree ul").html();
			if (!html) {
				updateLayerUrlFileManager( popup.find(".broker select")[0] );
			}
		},
		
		targetField: $(elm).parent().parent(),
		updateFunction: chooseHibernateObjectMethod,
	});
	
	MyFancyPopup.showPopup();
}

//Note that any change here must be replicated in admin_menu.js:onChooseWorkflowCallHibernateMethodTask
function chooseHibernateObjectMethod(elm) {
	var popup = $("#choose_hibernate_object_method_from_file_manager");
	
	var node = chooseHibernateObjectMethodFromFileManagerTree.getSelectedNodes();
	node = node[0];

	if (node) {
		var a = $(node).children("a");
		var i = a.children("i").first();
		var is_query = i.hasClass("query");
		var is_relationship = i.hasClass("relationship");
		var is_hbn_native = i.hasClass("hbn_native");
		
		if (!is_query && !is_relationship && !is_hbn_native) {
			alert("Selected item must be a query or a relationship!\nPlease try again...");
		}
		else {
			var query_id = a.children("label").first().text();
			var file_path = a.attr("file_path");
			var query_type = a.attr("query_type");
			var relationship_type = a.attr("relationship_type");
			var hbn_obj_id = a.attr("hbn_obj_id");
		
			var main_div = $(MyFancyPopup.settings.targetField);
			
			//PREPARING MODULE ID
			var module_id = file_path.lastIndexOf("/") != -1 ? file_path.substr(0, file_path.lastIndexOf("/")) : file_path;
			module_id = module_id.replace(/\//g, ".");
			
			main_div.find(".module_id input").val(module_id);
			main_div.find(".module_id select").val("string");
			
			//PREPARING HBN OBJ ID
			main_div.find(".service_id input").val(hbn_obj_id);
			main_div.find(".service_id select").val("string");
		
			//PREPARING QUERY ID / REL NAME
			var method = null;
			if (relationship_type == "queries") {
				main_div.find(".sma_query_id input").val(query_id);
				main_div.find(".sma_query_id select").val("string");
				
				main_div.find(".sma_query_type input").val(query_type);
				main_div.find(".sma_query_type select[name=sma_query_type]").val(query_type);
				main_div.find(".sma_query_type select[name=sma_query_type_type]").val("string");
				
				method = "call" + query_type.charAt(0).toUpperCase() + query_type.slice(1).toLowerCase();
			}
			else if (relationship_type == "relationships") {
				main_div.find(".sma_rel_name input").val(query_id);
				main_div.find(".sma_rel_name select").val("string");
				
				method = "findRelationship";
			}
			else if (relationship_type == "native") {
				method = query_id;
			}
			
			//PREPARING METHOD NAME
			main_div.find(".service_method .service_method_string").val(method);
			main_div.find(".service_method .service_method_type").val("string");
			
			CallHibernateMethodTaskPropertyObj.onChangeServiceMethodType( main_div.find(".service_method .service_method_type")[0] );
			CallHibernateMethodTaskPropertyObj.onChangeServiceMethod( main_div.find(".service_method .service_method_string")[0] );
			
			//PREPARING PARAMETERS
			var select = popup.find(".broker select")[0];
			var option = select.options[ select.selectedIndex ];
			var bean_file_name = option.getAttribute("bean_file_name");
			var bean_name = option.getAttribute("bean_name");
			var selected_option_text = $(option).text();
			var db_driver = popup.find(".db_driver select").val();
			var db_type = popup.find(".type select").val();
			
			updateHibernateObjectMethodParams(main_div, bean_file_name, bean_name, db_driver, db_type, file_path, query_type, query_id, hbn_obj_id, relationship_type);
			
			//update the selected broker
			var select = main_div.find(".broker_method_obj select");
			for (var i = 0; i < select[0].options.length; i++) {
				var option = select[0].options[i];
				
				if (option.value.indexOf('("' + selected_option_text + '")') != -1) {
					select.val( option.value );
					BrokerOptionsUtilObj.onBrokerChange(select[0]);
					break;
				}
			}
			
			MyFancyPopup.hidePopup();
		}
	}
}

function updateHibernateObjectMethodParams(main_div, bean_file_name, bean_name, db_driver, db_type, file_path, query_type, query_id, hbn_obj_id, relationship_type) {
	if (relationship_type == "native") {
		switch(query_id) {
			case "findRelationships":
			case "findRelationship":
			case "countRelationships":
			case "countRelationship":
				var sma_rel_name = main_div.find(".sma_rel_name input").val();
				if (sma_rel_name && main_div.find(".sma_rel_name select").val() == "string") {
					query_id = sma_rel_name;
					relationship_type = "relationships";
				}
				break;
		}
	}
	
	var attrs = getQueryParameters(bean_file_name, bean_name, db_driver, db_type, file_path, query_type, query_id, hbn_obj_id, relationship_type);
	attrs = attrs ? attrs : {};
	
	var method = main_div.children(".service_method").children(".service_method_string").val();
	
	var params_class_name = "sma_data";
	if (method == "findRelationships" || method == "findRelationship" || method == "countRelationships" || method == "countRelationship") {
		params_class_name = "sma_parent_ids";
	}
	
	var params_elm = main_div.children(".service_method_args").children("." + params_class_name);

	var parameters = params_elm.children("." + params_class_name);
	parameters.html("");

	var parameters_type = params_elm.children("select");
	parameters_type.val("array");
	CallHibernateMethodTaskPropertyObj.onChangeSMAType(parameters_type[0]);

	var add_item = parameters.children(".items").first().children(".item_add");
	var ul = parameters.children("ul");

	for (var pname in attrs) {
		var ptype = attrs[pname];

		var item = ul.children(".item").last();

		item.children(".key").val(pname);
		item.children(".key_type").val("string");
		item.children(".value").val("");
		item.children(".value_type").val(ptype);

		add_item.click();
	}

	ul.children(".item").last().remove();
}

function onChooseDBDriver(elm) {
	var popup = $("#choose_db_driver");
	
	MyFancyPopup.init({
		elementToShow: popup,
		parentElement: document,
		onOpen: function() {
			updateDBDriverOnBrokerNameChange( popup.find(".broker select") );
		},
		
		updateFunction: function(element) {
			var db_driver = popup.find(".db_driver select").val();
			$(elm).parent().children("input.value").val(db_driver);
			
			MyFancyPopup.hidePopup();
		}
	});
	
	MyFancyPopup.showPopup();
}

function onChooseDBTableAndAttributes(elm, callback) { //This is used in the workflow task: DBDAOActionTaskPropertyObj.on_choose_table_callback.
	if (typeof callback != "function")
		StatusMessageHandler.showError("callback argument in onGetDBTableAndAttributes function must be a function reference!");
	
	var popup = $("#choose_db_driver_table");
	
	MyFancyPopup.init({
		elementToShow: popup,
		parentElement: document,
		onOpen: function() {
			updateDBDriverOnBrokerNameChange( popup.find(".broker select") );
			updateDBTablesOnBrokerDBDriverChange( popup.find(".db_driver select") );
		},
		
		updateFunction: function(element) {
			var table_and_attributes = {
				table: popup.find(".db_table select").val(),
				attributes: []
			};
			
			if (table_and_attributes["table"] && get_broker_db_data_url) {
				var option = popup.find(".broker select option:selected");
				var broker_bean_name = option.attr("bean_name");
				var broker_bean_file_name = option.attr("bean_file_name");
				var broker = option.val();
				var db_driver = popup.find(".db_driver select").val();
				var type = popup.find(".type select").val();
				
				$.ajax({
					type : "post",
					url : get_broker_db_data_url,
					data : {"db_broker" : broker, "db_driver" : db_driver, "type" : type, "db_table" : table_and_attributes["table"], "detailed_info" : 1},
					dataType : "json",
					success : function(data, textStatus, jqXHR) {
						table_and_attributes["attributes"] = data;
						
						callback(table_and_attributes);
					},
					error : function(jqXHR, textStatus, errorThrown) { 
						if (jqXHR.responseText)
							StatusMessageHandler.showError(jqXHR.responseText);
					},
				});
			}
			else
				callback(table_and_attributes);
		
			MyFancyPopup.hidePopup();
		}
	});
	
	MyFancyPopup.showPopup();
}

function updateDBDriverOnBrokerNameChange(elm) {
	elm = $(elm);
	var broker = elm.val();
	var select = elm.parent().parent().find(".db_driver select");
	
	if (!brokers_db_drivers.hasOwnProperty(broker)) {
		var selected_option = elm.find("option:selected");
		var bean_file_name = selected_option.attr("bean_file_name");
		var bean_name = selected_option.attr("bean_name");
		var item_type = getBeanItemType(bean_name); //to be used by the testunit pages
		
		brokers_db_drivers[broker] = getBrokerDBDrivers(broker, bean_file_name, bean_name, item_type);
	}
	
	var db_drivers = brokers_db_drivers[broker];
	var selected_db_driver = select.val();
	var html = "";
	
	if (db_drivers)
		for (var db_driver_name in db_drivers) {
			var db_driver_props = db_drivers[db_driver_name];
			
			html += '<option value="' + db_driver_name + '">' + db_driver_name + (db_driver_props && db_driver_props.length > 0 ? '' : ' (Rest)') + '</option>'; 
		}
	
	select.html(html);
	select.val(selected_db_driver);
}

function updateDBTablesOnBrokerDBDriverChange(elm) {
	if (get_broker_db_data_url) {
		var p = $(elm).parent().parent();
		var option = p.find(".broker select option:selected");
		var broker_bean_name = option.attr("bean_name");
		var broker_bean_file_name = option.attr("bean_file_name");
		var broker = option.val();
		var db_driver = p.find(".db_driver select").val();
		var type = p.find(".type select").val();
		
		$.ajax({
			type : "post",
			url : get_broker_db_data_url,
			data : {"db_broker" : broker, "db_driver" : db_driver, "type" : type},
			dataType : "json",
			success : function(data, textStatus, jqXHR) {
				if(data) {
					var html = '<option></option>';
					for (var i = 0; i < data.length; i++)
						html += '<option>' + data[i] + '</option>';
					
					var select = p.find(".db_table select");
					var selected_table = select.val();
					select.html(html);
					select.val(selected_table);
				}
			},
			error : function(jqXHR, textStatus, errorThrown) { 
				if (jqXHR.responseText)
					StatusMessageHandler.showError(jqXHR.responseText);
			},
		});
	}
	else
		StatusMessageHandler.showError("Error: get_broker_db_data_url is not defined!");
}

function refreshDBTablesOnBrokerDBDriverChange(elm) {
	var p = $(elm).parent().parent();
	var select_with_trigger = p.find(".db_driver select[onChange], .type select[onChange]").first();
	
	if (select_with_trigger[0])
		select_with_trigger.trigger("change");
}

//to be used by the testunit pages, bc the layer_brokers_settings is only set in the view/testunit/edit_test.php
function getBeanItemType(bean_name) { 
	if (typeof layer_brokers_settings != "undefined" && $.isPlainObject(layer_brokers_settings) && bean_name) {
		for (var settings_type in layer_brokers_settings) {
			//settings_type: db_brokers, db_brokers_obj, ibatis_brokers, ibatis_brokers_obj, business_logic_brokers, business_logic_brokers_obj, presentation_brokers, presentation_brokers_obj, presentation_evc_brokers, presentation_evc_template_brokers, available_projects, etc...
			
			//if settings_type is: db_brokers, ibatis_brokers, business_logic_brokers, presentation_brokers, presentation_evc_brokers, presentation_evc_template_brokers...
			if (settings_type.match(/^([a-z_]+)_brokers$/i)) {
				var brokers_settings = layer_brokers_settings[settings_type];
				
				if ($.isArray(brokers_settings))
					for (var i = 0, l = brokers_settings.length; i < l; i++) {
						var broker_settings = brokers_settings[i];
						var broker_bean_name = broker_settings[2];
						
						if (broker_bean_name == bean_name)
							switch (settings_type) {
								case "db_brokers": return "db";
								case "ibatis_brokers": return "ibatis";
								case "hibernate_brokers": return "hibernate";
								case "business_logic_brokers": return "businesslogic";
								case "presentation_brokers": return "presentation";
							}
					}
			}
		}
	}
	
	return null;
}

//to be used by the testunit pages, bc the layer_brokers_settings is only set in the view/testunit/edit_test.php
function getBeanBrokerName(bean_name) { 
	if (typeof layer_brokers_settings != "undefined" && $.isPlainObject(layer_brokers_settings) && bean_name) {
		for (var settings_type in layer_brokers_settings) {
			//settings_type: db_brokers, db_brokers_obj, ibatis_brokers, ibatis_brokers_obj, business_logic_brokers, business_logic_brokers_obj, presentation_brokers, presentation_brokers_obj, presentation_evc_brokers, presentation_evc_template_brokers, available_projects, etc...
			
			//if settings_type is: db_brokers, ibatis_brokers, business_logic_brokers, presentation_brokers, presentation_evc_brokers, presentation_evc_template_brokers...
			if (settings_type.match(/^([a-z_]+)_brokers$/i)) {
				var brokers_settings = layer_brokers_settings[settings_type];
				
				if ($.isArray(brokers_settings))
					for (var i = 0, l = brokers_settings.length; i < l; i++) {
						var broker_settings = brokers_settings[i];
						var broker_bean_name = broker_settings[2];
						
						if (broker_bean_name == bean_name) 
							return broker_settings[0];
					}
			}
		}
	}
	
	return null;
}

function onPresentationTaskChoosePage(elm) {
	var popup = $("#choose_presentation_from_file_manager");
	
	MyFancyPopup.init({
		elementToShow: popup,
		parentElement: document,
		onOpen: function() {
			var html = popup.find(".mytree ul").html();
			if (!html) {
				updateLayerUrlFileManager( popup.find(".broker select")[0] );
			}
		},
		
		targetField: $(elm).parent().parent(),
		updateFunction: choosePresentation
	});
	
	MyFancyPopup.showPopup();
}

function choosePresentation(elm) {
	var node = choosePresentationFromFileManagerTree.getSelectedNodes();
	node = node[0];
	
	if (node) {
		var a = $(node).children("a");
		var file_path = a.attr("file_path");
		var pos = file_path.indexOf("/src/entity/");
		var is_page = a.children("i").first().hasClass("entity_file");
		
		if (!is_page || !file_path || pos == -1) {
			alert("Selected item must be a valid page!\nPlease try again...");
		}
		else {
			//var project_path = file_path.substr(0, file_path.indexOf("/"));
			var project_path = getNodeProjectPath(node);
			project_path = project_path && project_path.substr(project_path.length - 1) == "/" ? project_path.substr(0, project_path.length - 1) : project_path;
			project_path = project_path == selected_project_id ? "" : project_path;
			
			var pos = file_path.indexOf("/src/entity/");
			var page_path = file_path.substr(pos + ("/src/entity/").length);//12 == /src/entity/
			page_path = page_path.substr(page_path.length - 4, 1) == "." ? page_path.substr(0, page_path.lastIndexOf(".")) : page_path;
			
			var project = $(MyFancyPopup.settings.targetField).children(".project");
			project.find("input").val(project_path);
			project.find("select").val("string");
			
			var page = $(MyFancyPopup.settings.targetField).children(".page");
			page.find("input").val(page_path);
			page.find("select").val("string");
	
			MyFancyPopup.hidePopup();
		}
	}
}

/* UI, TABS, WORKFLOW & CODE EDITOR FUNCTIONS */

function onLoadTaskFlowChartAndCodeEditor(opts) {
	var parent = opts && $.isPlainObject(opts) && opts["parent_elm"] ? $(opts["parent_elm"]) : $("body");
	var taskflowchart = parent.find(".taskflowchart");
	
	if (taskflowchart[0]) {
		var workflow_menu = taskflowchart.find(".workflow_menu");
		var ui_panel = parent.find("#ui");
		var code_panel = parent.find("#code");
		
		resizeTaskFlowChart()
		resizeCodeEditor();
		
		$(window).resize(function() {
			resizeTaskFlowChart();
			resizeCodeEditor();
			
			MyFancyPopup.updatePopup();
		});
		
		taskFlowChartObj.onReady(function() {
			workflow_menu.show();
			parent.find(".big_white_panel").hide();
		});
		
		//init the code_id in #ui and #code so the system doesn't re-generate the code and workflow when clicked in the code and taskflow tabs and bc of the isCodeAndWorkflowObjChanged method, when the #tasks_flow_tab is not inited yet.
		if (ui_panel.length && code_panel.length) {
			var code = getEditorCodeRawValue();
			var code_id = getCodeId(code);
			
			code_panel.attr("generated_code_id", code_id);
			ui_panel.attr("code_id", code_id);
		}
	}
	
	if (!opts || !opts["do_not_hide_popup"])
		MyFancyPopup.hidePopup();
}

function createCodeEditor(textarea, options) {
	var parent = $(textarea).parent();
	var mode = options && options["mode"] ? options["mode"] : null;
	mode = mode ? mode : "php";
	
	var editor = ace.edit(textarea);
	editor.setTheme("ace/theme/chrome");
	editor.session.setMode("ace/mode/" + mode);
	editor.setAutoScrollEditorIntoView(true);
	editor.setOption("minLines", 5);
	editor.setOptions({
		enableBasicAutocompletion: true,
		enableSnippets: true,
		enableLiveAutocompletion: true,
	});
	editor.setOption("wrap", true);
	
	if (typeof setCodeEditorAutoCompleter == "function")
		setCodeEditorAutoCompleter(editor);
	
	//add on key press event
	/*editor.keyBinding.addKeyboardHandler(function(data, hashId, keyString, keyCode, e) {
		console.log(data);
		console.log(hashId);
		console.log(keyString);
		console.log(keyCode);
		console.log(e);
	});*/
	
	if (options && typeof options.save_func == "function") {
		editor.commands.addCommand({
			name: 'saveFile',
			bindKey: {
				win: 'Ctrl-S',
				mac: 'Command-S',
				sender: 'editor|cli'
			},
			exec: function(env, args, request) {
				options.save_func();
			},
		});
	}
	
	var on_change_timeout_id, auto_convert_bkp, auto_save_bkp;
	
	//add on change event to disable auto save and convert when user is writing code.
	editor.on("change", function(data, ed) {
		//console.log(on_change_timeout_id);
		if (on_change_timeout_id) {
			clearTimeout(on_change_timeout_id);
			
			auto_convert = auto_convert_bkp;
			auto_save = auto_save_bkp;
		}
		
		auto_convert_bkp = auto_convert;
		auto_convert = false;
		
		auto_save_bkp = auto_save;
		auto_save = false;
		
		on_change_timeout_id = setTimeout(function() {
			auto_convert = auto_convert_bkp;
			auto_save = auto_save_bkp;
			//console.log("edit end");
		}, 5000);
	});
	editor.on("blur", function(data, ed) {
		//console.log(on_change_timeout_id);
		if (on_change_timeout_id) {
			clearTimeout(on_change_timeout_id);
			
			auto_convert = auto_convert_bkp;
			auto_save = auto_save_bkp;
		}
	});
	
	if (options && typeof options.change_func == "function")
		editor.on("change", options.change_func);
	
	parent.find("textarea.ace_text-input").removeClass("ace_text-input"); //fixing problem with scroll up, where when focused or pressed key inside editor the page scrolls to top.
	
	parent.data("editor", editor);
	
	if (!options.hasOwnProperty("no_height") || !options["no_height"]) {
		var h = getCodeEditorHeight(parent);
		parent.children(".ace_editor").css("height", h + "px");
	}
	
	return editor;
}

function resizeTaskFlowChart() {
	$(".taskflowchart").height(getTaskFlowChartHeight() + "px");
	
	taskFlowChartObj.resizePanels();
}

function onResizeTaskFlowChartPanels(WF, height) {
	if ($("#" + WF.ContextMenu.main_tasks_menu_obj_id).parent().hasClass("with_top_bar_menu"))
		$("#" + WF.ContextMenu.main_tasks_menu_obj_id + ", #" + WF.ContextMenu.main_tasks_menu_hide_obj_id + ", #" + WF.TaskFlow.main_tasks_flow_obj_id).css("top", "");
}

function resizeCodeEditor(code_elm) {
	code_elm = code_elm ? code_elm : $("#code");
	
	var height = getCodeEditorHeight(code_elm);
	var editor_elm = code_elm.find(".ace_editor");
	
	if (editor_elm[0])
		editor_elm.css("height", height + "px");
	else
		code_elm.find(" > textarea, > .layout-ui-editor > textarea").css("height", getCodeEditorHeight(code_elm) + "px");
	
	var editor = code_elm.data("editor");
	if (editor)
		editor.resize();
}

function getTaskFlowChartHeight() {
	var taskflowchart = $(".taskflowchart");
	var offset = taskflowchart.offset();
	var top = parseInt(offset.top) + 2;
	
	return parseInt( $(window).height() ) - top;
}

function getCodeEditorHeight(code_editor_parent) {
	if (code_editor_parent[0]) {
		var offset = code_editor_parent.offset();
		var top = parseInt(offset.top) + 2;
		
		var code_menu = code_editor_parent.children(".code_menu:not(.top_bar_menu)");
		top += (code_menu[0] ? code_menu.height() : 0) + 10;
		
		return parseInt( $(window).height() ) - top;
	}
	return null;
}

function prettyPrintCode() {
	var editor = $("#code").data("editor");
	
	var code = editor ? editor.getValue() : $("#code textarea").first().val();
	code = MyHtmlBeautify.beautify(code);
	code = code.replace(/^\s+/g, "").replace(/\s+$/g, "");
	
	if (editor)
		editor.setValue(code);
	else
		$("#code textarea").first().val(code);
}

function setWordWrap(elm) {
	var editor = $("#code").data("editor");
	
	if (editor) {
		var wrap = $(elm).attr("wrap") != 1 ? false : true;
		$(elm).attr("wrap", wrap ? 0 : 1);
		
		editor.getSession().setUseWrapMode(wrap);
		StatusMessageHandler.showMessage("Wrap is now " + (wrap ? "enable" : "disable"), "", "bottom_messages", 1500);
	}
}

function openEditorSettings() {
	var editor = $("#code").data("editor");
	
	if (editor) {
		editor.execCommand("showSettingsMenu");
		
		//prepare font size option
		setTimeout(function() {
			var input = $("#ace_settingsmenu input#setFontSize");
			
			if (input[0]) {
				var value = input.val();
				var title = "eg: 12px, 12em, 12rem, 12pt or 120%";
				
				input.attr("title", title).attr("placeHolder", title);
				input.after('<div style="text-align:right; opacity:.5;">' + title + '</div>');
				
				if ($.isNumeric(value))
					input.val(value + "px");
				
				if (input.data("with_keyup_set") != 1) {
					input.data("with_keyup_set", 1);
					
					input.on("keyup", function() {
						var v = $(this).val();
						
						if (v.match(/([0-9]+(\.[0-9]*)?|\.[0-9]+)(px|em|rem|%|pt)/i))
							$(this).trigger("blur").focus();
					});
				}
			}
		}, 300);
	}
	else {
		StatusMessageHandler.showError("Error trying to open the editor settings...");
	}
}

function commentCodeAutomatically() {
	if (typeof manage_ai_action_url == "undefined")
		StatusMessageHandler.showError("Manage AI Action url is not defined. Please talk with sysadmin");
	else if (!manage_ai_action_url)
		StatusMessageHandler.showError("Artificial Intelligence is disabled. To enable it, please add your OpenAI Key in the 'Manage Permissions/Users' panel.");
	else {
		var editor = $("#code").data("editor");
		var code = editor ? editor.getValue() : $("#code textarea").first().val();
		
		var url = manage_ai_action_url + (manage_ai_action_url.indexOf("?") != -1 ? "" : "?") + "&action=comment_php_code";
		
		if (!code)
			StatusMessageHandler.showMessage("There is no code to comment...", "", "bottom_messages", 1500);
		else {
			var msg = StatusMessageHandler.showMessage("AI loading. Wait a while...", "", "bottom_messages", 60000);
			
			$.ajax({
				type : "post",
				url : url,
				processData: false,
				contentType: 'text/plain',
				data: code,
				dataType : "html",
				success : function(message, textStatus, jqXHR) {
					//console.log(message);
					
					msg.remove();
					
					if (message) {
						if (editor)
							editor.setValue(message);
						else
							$("#code textarea").first().val(message);
					}
					else
						StatusMessageHandler.showError("Error: Couldn't process this request with AI. Please try again...");
				},
				error : function(jqXHR, textStatus, errorThrown) {
					msg.remove();
					
					if (jqXHR.responseText)
						StatusMessageHandler.showError(jqXHR.responseText);
				},
			});
		}
	}
}

function openCodeChatBot() {
	if (typeof manage_ai_action_url == "undefined")
		StatusMessageHandler.showError("Manage AI Action url is not defined. Please talk with sysadmin");
	else if (!manage_ai_action_url)
		StatusMessageHandler.showError("Artificial Intelligence is disabled. To enable it, please add your OpenAI Key in the 'Manage Permissions/Users' panel.");
	else {
		var editor = $("#code").data("editor");
		
		if (editor) {
			if (typeof editor.showCodeEditorChatBot == "function")
				editor.showCodeEditorChatBot(editor);
			else
				showCodeEditorChatBot(editor);
		}
	}
}

function toggleTaskFlowFullScreen(elm) {
	toggleEditorFullScreen(elm);
	
	resizeTaskFlowChart();
}

function toggleCodeEditorFullScreen(elm) {
	toggleEditorFullScreen(elm);
	
	resizeCodeEditor( $(elm).parent().closest(".code_menu").parent() );
}

function toggleEditorFullScreen(elm) {
	elm = $(elm);
	
	var in_full_screen = isInFullScreen();
	var ui = $("#ui");
	var code = $("#code");
	
	toggleFullScreen(elm);
	
	if (in_full_screen) {
		code.removeClass("editor_full_screen");
		ui.removeClass("tasks_flow_full_screen");
		
		code.find(" > .code_menu > ul > .editor_full_screen a").removeClass("active");
		ui.find(" > .taskflowchart > .workflow_menu > .dropdown > .tasks_flow_full_screen a").removeClass("active");
		
		//bc of the LayoutUIEditor
		code.children(".layout-ui-editor, .layout_ui_editor_right_container").removeClass("full-screen");
	   	code.find(".layout-ui-editor .options .full-screen").removeClass("zmdi-fullscreen-exit").addClass("zmdi-fullscreen");
	}
	else {
		code.addClass("editor_full_screen");
		ui.addClass("tasks_flow_full_screen");
		
		code.find(" > .code_menu > ul > .editor_full_screen a").addClass("active");
		ui.find(" > .taskflowchart > .workflow_menu > .dropdown > .tasks_flow_full_screen a").addClass("active");
		
		//bc of the LayoutUIEditor
		code.children(".layout-ui-editor, .layout_ui_editor_right_container").addClass("full-screen");
	   	code.find(".layout-ui-editor .options .full-screen").addClass("zmdi-fullscreen-exit").removeClass("zmdi-fullscreen");
	}
}

function getCodeId(code) {
	if (code == undefined)
		code = getEditorCodeRawValue();
	
	return code == undefined ? 0 : $.md5(code.replace(/\s/g)); //remove white spaces to be sure that the code is not different bc of simple spaces
}

function setEditorCodeRawValue(code) {
	var editor = $("#code").data("editor");
	
	if (editor) 
		editor.setValue(code, 1);
	else
		$("#code textarea").val(code);
}

function getEditorCodeRawValue() {
	var code = "";
	var editor = $("#code").data("editor");
	
	if (editor) 
		code = editor.getValue();
	else
		code = $("#code textarea").val();
	
	return code;
}

function getEditorCodeValue() {
	var code = getEditorCodeRawValue();
	
	if (code) {
		code = code ? code.trim() : "";
	
		if (code != "") {
			if (code.substr(0, 2) == "<?") {
				code = code.substr(0, 5) == "<?php" ? code.substr(5) : (code.substr(0, 2) == "<?" ? code.substr(2) : code);
			}
			else {
				code = "?>\n" + code;
			}
		
			if (code.substr(code.length - 2) == "?>") {
				code = code.substr(0, code.length - 2);
			}
		
			else if (code.lastIndexOf("<?") < code.lastIndexOf("?>")) {//this means that exists html elements at the end of the file
				code += "\n<?php";
			}
			
			while(code.indexOf("<?php\n?>") != -1) {
				code = code.replace("<?php\n?>", "");
			}
			
			code = code.trim();
		}
	}
	
	return code;
}

function getEditorCodeErrors() {
	var errors = [];
	var editor = $("#code").data("editor");
	
	if (editor) {
		var annotations = editor.getSession().getAnnotations();
		//console.log(annotations);
		
		if (annotations)
			errors = annotations.filter(function(annotation) {
				return annotation["type"] == "error";
			});
	}
	
	return errors;
}

function isEditorCodeWithErrors() {
	var errors = getEditorCodeErrors();
	return errors.length > 0;
}

//This function is called in the save method of the low-code editors.
//Basically if code has errors, wokflow tab is selectd and diagram was changed meanwhile, then the system will create the new code and return false, setting the editor_code_errors_are_old var to true, so the next time the auto-save runs or this function gets called, it will return false, proceeding to the saving action...
function checkIfWorkflowDoesNotNeedToChangePreviousCodeWithErrors(main_obj_elm, strip_php_tags) {
	var status = true;
	
	if (isEditorCodeWithErrors()) {
		//if tasks_flow_tab is selected and if diagram changed
		if (main_obj_elm.find("#tasks_flow_tab").is("li.ui-tabs-selected, li.ui-tabs-active") && taskFlowChartObj.TaskFile.isWorkFlowChangedFromLastSaving()) {
			var old_code = getEditorCodeRawValue();
			//force tasks flow tab to convert workfow into code, otherwise the code will never be rebuild again bc the auto_save will always enter in this condition.
			var new_code = getCodeForSaving(main_obj_elm, {strip_php_tags: strip_php_tags}); //if tasks flow tab is selected ask user to convert workfow into code
			
			if (old_code != new_code)
				//ace editor doesn't update the errors as soon as the code get updated in the editor, so we need to set this var to true and use it below.
				editor_code_errors_are_old = true;
		}
	}
	else if (editor_code_errors_are_old)
		status = editor_code_errors_are_old = false;
	
	return status;
}

function sortWorkflowTask(sort_type) {
	taskFlowChartObj.getMyFancyPopupObj().init({
		parentElement: $("#" + taskFlowChartObj.TaskFlow.main_tasks_flow_obj_id),
	});
	taskFlowChartObj.getMyFancyPopupObj().showOverlay();
	taskFlowChartObj.getMyFancyPopupObj().showLoading();
	
	if (!sort_type) {
		sort_type = prompt("Please choose the sort type that you wish? You can choose 1, 2, 3 or 4.");
	}
	
	if (sort_type) {
		taskFlowChartObj.TaskSort.sortTasks(sort_type);
		StatusMessageHandler.showMessage("Done sorting tasks based in the sort type: " + sort_type + ".", "", "bottom_messages", 1500);
	}
	
	taskFlowChartObj.getMyFancyPopupObj().hidePopup();
}

function flipTasksFlowPanelsSide(elm) {
	taskFlowChartObj.ContextMenu.flipPanelsSide();
}

function toggleHeader(elm) {
	elm = $(elm);
	var p = elm.parent();
	var items = p.children(".title, .sub_title");
	
	if (elm.hasClass("maximize")) {
		items.show();
		elm.removeClass("maximize").addClass("minimize");
		p.removeClass("minimized");
	}
	else {
		items.hide();
		elm.removeClass("minimize").addClass("maximize");
		p.addClass("minimized");
	}
	
	resizeTaskFlowChart();
	resizeCodeEditor();
}

function onClickCodeEditorTab(elm, options) {
	taskFlowChartObj.TaskFile.stopAutoSave();
	
	if (auto_convert) {
		//close properties popup in case the auto_save be active on close task properties popup, but only if is not auto_save, otherwise the task properties can become messy, like it happens with the task inlinehtml.
		if (auto_save && taskFlowChartObj.Property.auto_save && !is_from_auto_save) {
			if (taskFlowChartObj.Property.isSelectedTaskPropertiesOpen())
				taskFlowChartObj.Property.saveTaskProperties({do_not_call_hide_properties: true});
			else if (taskFlowChartObj.Property.isSelectedConnectionPropertiesOpen())
				taskFlowChartObj.Property.saveConnectionProperties({do_not_call_hide_properties: true});
		}
		
		options = $.isPlainObject(options) ? options : {};
		options["do_not_change_to_code_tab"] = true;
		options["success"] = function() {
			StatusMessageHandler.removeMessages("info");
		};
		options["error"] = function() {
			StatusMessageHandler.removeMessages("info");
		};
		
		generateCodeFromTasksFlow(true, options);
		
		if (options["generating"])
			StatusMessageHandler.showMessage("Generating code based in workflow... Loading...", "", "bottom_messages", 1500);
	}
	
	setTimeout(function() {
		resizeCodeEditor();
		
		var editor = $("#code").data("editor");
		if (editor && $("#code").is(":visible"))
			editor.focus();
	}, 10);
}

function onClickTaskWorkflowTab(elm, options) {
	elm = $(elm);
	
	if (elm.attr("is_init") != 1) {
		elm.attr("is_init", 1);
		
		MyFancyPopup.init({
			parentElement: window,
		});
		MyFancyPopup.showOverlay();
		MyFancyPopup.showLoading();
		
		var selector = elm.attr("href");
		var p = elm.parent().closest("ul").parent();
		var ui_elm = p.children(selector);
		
		if (!ui_elm[0])
			ui_elm = p.find(selector);
		
		var workflow_menu = ui_elm.find(".workflow_menu");
		workflow_menu.hide();
		
		var auto_save_bkp = auto_save;
		
		if (auto_save_bkp && isPHPCodeAutoSaveMenuEnabled())
			auto_save = false;
		
		taskFlowChartObj.TaskFile.read(get_workflow_file_url, {
			"success": function(data, textStatus, jqXHR) {
				resizeTaskFlowChart();
				
				MyFancyPopup.hidePopup();
				workflow_menu.show();
				taskFlowChartObj.resizePanels();
				
				if (auto_save_bkp && isPHPCodeAutoSaveMenuEnabled())
					auto_save = auto_save_bkp;
				
				//set code_id from data
				var saved_code_id = $.isPlainObject(taskFlowChartObj.TaskFile.file_settings) ? taskFlowChartObj.TaskFile.file_settings["code_id"] : null;
				//console.log("saved_code_id:"+saved_code_id);
				//console.log("ui_code_id:"+$("#ui").attr("code_id"));
				
				if (saved_code_id)
					$("#ui").attr("code_id", saved_code_id);
				
				//check if user is logged in
				if (jquery_native_xhr_object && isAjaxReturnedResponseLogin(jquery_native_xhr_object.responseURL))
					showAjaxLoginPopup(jquery_native_xhr_object.responseURL, get_workflow_file_url, function() {
						elm.removeAttr("is_init");
						taskFlowChartObj.StatusMessage.removeLastShownMessage("error");
						onClickTaskWorkflowTab(elm[0], options);
					});
				else {
					//set workflow_id to #ui so it doesn't re-generate the code when clicked in the code tab
					var workflow_id = getCurrentWorkFlowId();
					var code = getEditorCodeRawValue();
					var new_code_id = getCodeId(code);
					
					//Note that the code_id and the generated_code_id were already set in the onLoadTaskFlowChartAndCodeEditor, when the page loads
					$("#ui").attr("workflow_id", workflow_id);
					
					//If auto_convert is active and:
					//- if diagram is empty and code exists
					//- or if taskflow is not yet inited and we meanwhile changed the code
					//so we must generate a new diagram
					if (auto_convert) {
						var is_empty_diagram = code && (!data || (!$.isArray(data["tasks"]) && !$.isPlainObject(data["tasks"])) || $.isEmptyObject(data["tasks"]));
						var old_code_id = $("#ui").attr("code_id");
						
						if (is_empty_diagram || (old_code_id != new_code_id)) {
							generateTasksFlowFromCode(true, {
								force : true,
								success : function(data, textStatus, jqXHR) {
									StatusMessageHandler.removeMessages("info");
									
									if (typeof options == "object" && typeof options["on_success"] == "function")
										options["on_success"]();
									
									//save new workflow for the first time
									taskFlowChartObj.TaskFile.save(null, {
										overwrite: true, 
										silent: true, 
										success: taskFlowChartObj.TaskFile.save_options["success"], 
									});
								},
								error : function(jqXHR, textStatus, errorThrown) {
									StatusMessageHandler.removeMessages("info");
									
									if (typeof options == "object" && typeof options["on_error"] == "function")
										options["on_error"]();
								},
							});
						}
						else if (typeof options == "object" && typeof options["on_success"] == "function")
							options["on_success"]();
					}
					else if (typeof options == "object" && typeof options["on_success"] == "function")
						options["on_success"]();
				}
			},
			"error": function(jqXHR, textStatus, errorThrown) {
				resizeTaskFlowChart();
				
				MyFancyPopup.hidePopup();
				workflow_menu.show();
				taskFlowChartObj.resizePanels();
				
				if (auto_save_bkp && isPHPCodeAutoSaveMenuEnabled())
					auto_save = auto_save_bkp;
				
				if (jquery_native_xhr_object && isAjaxReturnedResponseLogin(jquery_native_xhr_object.responseURL))
					showAjaxLoginPopup(jquery_native_xhr_object.responseURL, get_workflow_file_url, function() {
						elm.removeAttr("is_init");
						taskFlowChartObj.StatusMessage.removeLastShownMessage("error");
						onClickTaskWorkflowTab(elm[0], options);
					});
				else if (typeof options == "object" && typeof options["on_error"] == "function")
					options["on_error"]();
			},
		});
	}
	else {
		//when editing code, if auto_save is active, the system will try to convert the code to a workflow, but if the php code contains errors (bc the user didn't finish yet his code, the generateTasksFlowFromCode will show errors, so we must remove this errors first, before generateTasksFlowFromCode.
		if (auto_save) {
			StatusMessageHandler.removeMessages("error");
			taskFlowChartObj.StatusMessage.removeMessages("error");
		}
		
		setTimeout(function() {
			updateTasksFlow();
			resizeTaskFlowChart();
			
			taskFlowChartObj.TaskFile.startAutoSave();
			
			if (auto_convert) {
				var opts = {
					success : function(data, textStatus, jqXHR) {
						StatusMessageHandler.removeMessages("info");
						
						if (typeof options == "object" && typeof options["on_success"] == "function")
							options["on_success"]();
					},
					error : function(jqXHR, textStatus, errorThrown) {
						StatusMessageHandler.removeMessages("info");
						
						if (typeof options == "object" && typeof options["on_error"] == "function")
							options["on_error"]();
					},
				};
				generateTasksFlowFromCode(true, opts);
				
				if (opts["generating"])
					StatusMessageHandler.showMessage("Generating workflow based in code... Loading...", "", "bottom_messages", 1500);
			}
		}, 10);
	}
}

function updateTasksFlow() {
	taskFlowChartObj.getMyFancyPopupObj().updatePopup();
		
	var tasks = taskFlowChartObj.TaskFlow.getAllTasks();
	
	for (var i = 0; i < tasks.length; i++) {
		var task = tasks[i];
		
		onEditLabel(task.id);
		taskFlowChartObj.TaskFlow.repaintTask( $(task) );
	}
}

function getCurrentWorkFlowId() {
	var data = taskFlowChartObj.TaskFile.getWorkFlowData();
	data = assignObjectRecursively({}, data);
	var tasks = data && data["tasks"]; //only use tasks so it can ignore the settings and containers items inside of data var.
	
	//regularize the sizes and offsets from tasks, so we can get the real workflow id and check if there were changes or not in the workflow...
	if (tasks)
		for (var task_id in tasks) {
			tasks[task_id]["width"] = 100;
			tasks[task_id]["height"] = 100;
			tasks[task_id]["offset_top"] = 0;
			tasks[task_id]["offset_left"] = 0;
		}
	
	var workflow_id = $.md5(JSON.stringify(tasks));
	
	return workflow_id;
}

function generateCodeFromTasksFlow(do_not_confirm, options) {
	var status = true;
	var options = typeof options == "object" ? options : {};
	options["generating"] = false;
	
	var old_workflow_id = $("#ui").attr("workflow_id");
	var new_workflow_id = getCurrentWorkFlowId();
	
	var generated_code_id = $("#code").attr("generated_code_id");
	var code = getEditorCodeRawValue();
	var new_code_id = getCodeId(code);
	//console.log("generateCodeFromTasksFlow");
	//console.log(generated_code_id+"="+new_code_id);
	//console.log(old_workflow_id+"="+new_workflow_id);
	
	var is_tasks_flow_tab_inited = $("#code").parent().find(" > ul > #tasks_flow_tab > a").attr("is_init") == 1;
	
	if (is_tasks_flow_tab_inited && (
		(old_workflow_id && old_workflow_id != new_workflow_id) || (generated_code_id && generated_code_id != new_code_id) || options["force"]
	)) { //check if old_workflow_id exists bc the workflow may be still loading and opening and in this case the workflow_id attribute is not yet set.
		if (do_not_confirm || auto_convert || confirm("Do you wish to update this code accordingly with the workflow tasks?")) {
			status = false;
			options["generating"] = true;
			
			if (!is_from_auto_save) {
				MyFancyPopup.init({
					parentElement: window,
				});
				MyFancyPopup.showOverlay();
				MyFancyPopup.showLoading();
				$(".workflow_menu").hide();
			}
			
			var save_options = {
				overwrite: true,
				silent: true,
				do_not_silent_errors: true,
				success: function(data, textStatus, jqXHR) {
					if (jquery_native_xhr_object && isAjaxReturnedResponseLogin(jquery_native_xhr_object.responseURL))
						showAjaxLoginPopup(jquery_native_xhr_object.responseURL, set_tmp_workflow_file_url, function() {
							taskFlowChartObj.StatusMessage.removeLastShownMessage("error");
							StatusMessageHandler.removeLastShownMessage("error");
							generateCodeFromTasksFlow(true, options);
						});
				},
			};
			
			if (taskFlowChartObj.TaskFile.save(set_tmp_workflow_file_url, save_options)) {
				//if not default start task, the system will try to figure out one by default, but is always good to show a message to the user alerting him of this situation...
				if (!is_from_auto_save) {
					var exists_start_tasks = $("#" + taskFlowChartObj.TaskFlow.main_tasks_flow_obj_id + " ." + taskFlowChartObj.TaskFlow.task_class_name + "." + taskFlowChartObj.TaskFlow.start_task_class_name).length > 0;
					
					if (!exists_start_tasks)
						StatusMessageHandler.showMessage("There is no startup task selected. The system tried to select a default one, but is more reliable if you define one manually...");
				}
				
				var url = create_code_from_workflow_file_url;
				url += (url.indexOf("?") != -1 ? "" : "?") + "&time=" + (new Date()).getTime();
				
				$.ajax({
					type : "get",
					url : url,
					dataType : "json",
					success : function(data, textStatus, jqXHR) {
						if (data && data.hasOwnProperty("code")) {
							var code = "<?php\n" + data.code.trim() + "\n?>";
							code = code.replace(/^<\?php\s+\?>\s*/, "").replace(/<\?php\s+\?>$/, ""); //remove empty php tags
							
							setEditorCodeRawValue(code);
							var new_code_id = getCodeId(code);
							
							$("#ui").attr("workflow_id", new_workflow_id);
							$("#ui").attr("code_id", new_code_id);
							$("#code").attr("generated_code_id", new_code_id);
							
							//set new code id into file settings
							if (!$.isPlainObject(taskFlowChartObj.TaskFile.file_settings))
								taskFlowChartObj.TaskFile.file_settings = {};
							
							taskFlowChartObj.TaskFile.file_settings["code_id"] = new_code_id;
							//console.log(taskFlowChartObj.TaskFile.file_settings);
							
							//console.log("Code updated");
							//console.log("code:"+getEditorCodeRawValue());
							
							if (data["error"] && data["error"]["infinit_loop"] && data["error"]["infinit_loop"][0]) {
								var loops = data["error"]["infinit_loop"];
								
								var msg = "";
								for (var i = 0; i < loops.length; i++) {
									var loop = loops[i];
									var slabel = taskFlowChartObj.TaskFlow.getTaskLabelByTaskId(loop["source_task_id"]);
									var tlabel = taskFlowChartObj.TaskFlow.getTaskLabelByTaskId(loop["target_task_id"]);
									
									msg += (i > 0 ? "\n" : "") + "- '" + slabel + "' => '" + tlabel + "'";
								}
								
								msg = "The system detected the following invalid loops and discarded them from the code:\n" + msg + "\n\nYou should remove them from the workflow and apply the correct 'loop task' for doing loops.";
								taskFlowChartObj.StatusMessage.showError(msg);
								
								if (typeof options["error"] == "function")
									options["error"]();
								
								alert(msg);
							}
							else {
								if (!options["do_not_change_to_code_tab"]) {
									var edit_tab = $("#raw_editor_tab a").first(); //bc of edit_template_simple
									edit_tab = edit_tab[0] ? edit_tab : $("#code_editor_tab a").first(); //bc of all others
									edit_tab.click();
								}
								
								status = true;
								
								if (typeof options["success"] == "function")
									options["success"]();
							}
						}
						else {
							StatusMessageHandler.showError("There was an error trying to update this code. Please try again.");
							
							if (typeof options["error"] == "function")
								options["error"]();
						}
						
						if (!is_from_auto_save) {
							MyFancyPopup.hidePopup();
							$(".workflow_menu").show();
						}
					},
					error : function(jqXHR, textStatus, errorThrown) { 
						var msg = jqXHR.responseText ? "\n" + jqXHR.responseText : "";
						
						if (jquery_native_xhr_object && isAjaxReturnedResponseLogin(jquery_native_xhr_object.responseURL))
							showAjaxLoginPopup(jquery_native_xhr_object.responseURL, create_code_from_workflow_file_url, function() {
								generateCodeFromTasksFlow(true, options);
							});
						else {
							StatusMessageHandler.showError("There was an error trying to update this code. Please try again." + msg);
				
							if (!is_from_auto_save) {
								MyFancyPopup.hidePopup();
								$(".workflow_menu").show();
							}
							
							if (typeof options["error"] == "function")
								options["error"]();
						}
					},
					async : options.hasOwnProperty("async") ? options["async"] : true,
				});
			}
			else {
				StatusMessageHandler.showError("There was an error trying to update this code. Please try again.");
				
				if (!is_from_auto_save) {
					MyFancyPopup.hidePopup();
					$(".workflow_menu").show();
				}
				
				if (typeof options["error"] == "function")
					options["error"]();
			}
		}
		else if (typeof options["success"] == "function")
			options["success"]();
	}
	else {
		if (!is_from_auto_save) {
			if (!is_tasks_flow_tab_inited)
				StatusMessageHandler.showMessage("Tasks flow diagram was not loaded yet. Please open the tasks flow diagram first, before any conversion...");
			else
				StatusMessageHandler.showMessage("The tasks flow diagram has no changes. No need to update the code.", "", "bottom_messages", 1500);
		}
		
		if (typeof options["success"] == "function")
			options["success"]();
	}
	
	return status;
}

function generateTasksFlowFromCode(do_not_confirm, options) {
	var status = true;
	var options = typeof options == "object" ? options : {};
	options["generating"] = false;
	
	//only if no errors detected
	var errors = getEditorCodeErrors();
	
	prepareAutoSaveVars();
	
	if (errors.length == 0) {
		var old_code_id = $("#ui").attr("code_id");
		var code = getEditorCodeRawValue();
		var new_code_id = getCodeId(code);
		//console.log("generateTasksFlowFromCode");
		//console.log(old_code_id+"="+new_code_id);
		
		if (old_code_id != new_code_id || options["force"]) {
			if (do_not_confirm || auto_convert || confirm("Do you wish to update this workflow accordingly with the code in the editor?")) {
				status = false;
				options["generating"] = true;
				
				if (!is_from_auto_save) {
					taskFlowChartObj.getMyFancyPopupObj().hidePopup();
					MyFancyPopup.init({
						parentElement: window,
					});
					MyFancyPopup.showOverlay();
					MyFancyPopup.showLoading();
					$(".workflow_menu").hide();
				}
				
				var auto_save_bkp = auto_save;
				
				if (auto_save_bkp && isPHPCodeAutoSaveMenuEnabled())
					auto_save = false;
				
				$.ajax({
					type : "post",
					url : create_workflow_file_from_code_url,
					data : code,
					dataType : "text",
					success : function(data, textStatus, jqXHR) {
						if (jquery_native_xhr_object && isAjaxReturnedResponseLogin(jquery_native_xhr_object.responseURL)) {
							if (auto_save_bkp && isPHPCodeAutoSaveMenuEnabled())
								auto_save = auto_save_bkp;
							
							showAjaxLoginPopup(jquery_native_xhr_object.responseURL, create_workflow_file_from_code_url, function() {
								generateTasksFlowFromCode(true, options);
							});
						}
						else if (data == 1) {
							var previous_callback = taskFlowChartObj.TaskFile.on_success_read;
							var previous_tasks_flow_saved_data_obj = taskFlowChartObj.TaskFile.saved_data_obj; //save the previous TaskFile.saved_data_obj, bc when we run the TaskFile.reload method, this var will be with the new workflow data obj and then the auto_save won't run bc the TaskFile.isWorkFlowChangedFromLastSaving will return false. So we must save this var before and then re-put it again with the previous value.
							
							//check if there is any task properties open and if it is, hide then, bc they won't do anything bc the tasks will be new and with new ids, so the task properties that were previously open, doesn't belong to any of the new tasks. So for a good user-experience, we need to close them.
							if (taskFlowChartObj.Property.isSelectedTaskPropertiesOpen())
								taskFlowChartObj.Property.hideSelectedTaskProperties();
							else if (taskFlowChartObj.Property.isSelectedConnectionPropertiesOpen())
								taskFlowChartObj.Property.hideSelectedConnectionProperties();
							
							taskFlowChartObj.TaskFile.on_success_read = function(data, text_status, jqXHR) {
								if (!data) {
									taskFlowChartObj.StatusMessage.showError("There was an error trying to load the workflow's tasks.");
									
									if (typeof options["error"] == "function")
										options["error"]();
								}
								else {
									//sort tasks
									taskFlowChartObj.TaskSort.sortTasks();
									
									setTimeout(function() { //must be in timeout otherwise the connections will appear weird
										taskFlowChartObj.TaskFlow.repaintAllTasks();
									}, 5);
									
									//update code id
									$("#code").attr("generated_code_id", new_code_id);
									$("#ui").attr("code_id", new_code_id);
									$("#ui").attr("workflow_id", getCurrentWorkFlowId());
									
									//set new code id into file settings
									if (!$.isPlainObject(taskFlowChartObj.TaskFile.file_settings))
										taskFlowChartObj.TaskFile.file_settings = {};
									
									taskFlowChartObj.TaskFile.file_settings["code_id"] = new_code_id;
									//console.log(taskFlowChartObj.TaskFile.file_settings);
									
									status = true;
									
									if (typeof options["success"] == "function")
										options["success"]();
								}
								
								if (auto_save_bkp && isPHPCodeAutoSaveMenuEnabled())
									auto_save = auto_save_bkp;
								
								//The TaskFile will call after this function the TaskFile.startAutoSave method which updates the TaskFile.saved_data_obj var with the new workflow data obj. So we must execute a setTimeout so we can then update the old value to the TaskFile.saved_data_obj var.
								setTimeout(function() {
									taskFlowChartObj.TaskFile.saved_data_obj = previous_tasks_flow_saved_data_obj;
								}, 100);
								
								taskFlowChartObj.TaskFile.on_success_read = previous_callback;
							}
							
							taskFlowChartObj.TaskFile.reload(get_tmp_workflow_file_url, {
								"async": true,
								error: function() {
									if (auto_save_bkp && isPHPCodeAutoSaveMenuEnabled())
										auto_save = auto_save_bkp;
								}
							});
						}
						else {
							taskFlowChartObj.StatusMessage.showError("There was an error trying to update this workflow. Please try again." + (data ? "\n" + data : ""));
							
							if (auto_save_bkp && isPHPCodeAutoSaveMenuEnabled())
								auto_save = auto_save_bkp;
						
							if (typeof options["error"] == "function")
								options["error"]();
						}
						
						if (!is_from_auto_save) {
							MyFancyPopup.hidePopup();
							$(".workflow_menu").show();
						}
					},
					error : function(jqXHR, textStatus, errorThrown) { 
						var msg = jqXHR.responseText ? "\n" + jqXHR.responseText : "";
						taskFlowChartObj.StatusMessage.showError("There was an error trying to update this workflow. Please try again." + msg);
						if (auto_save_bkp && isPHPCodeAutoSaveMenuEnabled())
							auto_save = auto_save_bkp;
						
						if (typeof options["error"] == "function")
							options["error"]();
						
						if (!is_from_auto_save) {
							MyFancyPopup.hidePopup();
							$(".workflow_menu").show();
						}
					},
					async : options.hasOwnProperty("async") ? options["async"] : true,
				});
			}
			else if (typeof options["success"] == "function")
				options["success"]();
		}
		else {
			if (!is_from_auto_save)
				StatusMessageHandler.showMessage("The code has no changes. No need to update the tasks flow diagram.", "", "bottom_messages", 1500);
			
			if (typeof options["success"] == "function")
				options["success"]();
		}
	}
	else {
		if (!is_from_auto_save) {
			//show errors
			var msg = "";
			
			for (var i = 0, t = errors.length; i < t; i++) {
				var error = errors[i];
				msg += "\n- " + error["text"];
				
				if ($.isNumeric(error["row"]))
					msg += " in line " + error["row"] + ($.isNumeric(error["column"]) ? ", " + error["column"] : "");
				 
				 msg += ".";
			}
			//console.log(msg);
			
			StatusMessageHandler.showError("The code has the following errors, which means we cannot update the tasks flow diagram:" + msg);
		}
		
		if (typeof options["error"] == "function")
			options["error"]();
	}
	
	return status;
}

//This function will be used by the widgets in the LayoutUIEditor, mainly the script, link, href, image, video and iframe widgets.
//replace_inline_vars should be true on edit_entity_simple. The edit_template_simple should have this flag disabled and only replace the project_url_prefix inside of the php tags.
function convertProjectUrlPHPVarsToRealValues(str, replace_inline_vars) {
	if (str) {
		var regex = /<\?(|=|php)\s*(|echo|print)\s*(\$[a-z_]+)\s*;?\s*\?>/g;
		var m;
		//console.log("old str:"+str);
		
		while ((m = regex.exec(str)) !== null) {
			//console.log(m);
			
			if ((m[3] == "$project_url_prefix" || m[3] == "$original_project_url_prefix") && typeof selected_project_url_prefix != "undefined" && selected_project_url_prefix)
				str = str.replace(m[0], selected_project_url_prefix);
			else if ((m[3] == "$project_common_url_prefix"/* || m[3] == "$original_project_common_url_prefix"*/) && typeof selected_project_common_url_prefix != "undefined" && selected_project_common_url_prefix)
				str = str.replace(m[0], selected_project_common_url_prefix);
		}
		
		if (replace_inline_vars) {
			var regex = /{?\$([a-z_]+)}?/g;
			var m;
			
			while ((m = regex.exec(str)) !== null) {
				//console.log(str);
				//console.log(m);
				var reg = new RegExp("(\"|')\\s*\\.\\s*\\$" + m[1] + "\\s*\\.\\s*(\"|')");
				//console.log(reg);
				//console.log("match:"+str.match(reg));
				
				//ignores cases like: ' somehting ' . $project_url_prefix . ' something '
				if (!str.match(reg)) {
					if ((m[1] == "project_url_prefix" || m[1] == "original_project_url_prefix") && typeof selected_project_url_prefix != "undefined" && selected_project_url_prefix)
						str = str.replace(m[0], selected_project_url_prefix);
					else if ((m[1] == "project_common_url_prefix"/* || m[1] == "original_project_common_url_prefix"*/) && typeof selected_project_common_url_prefix != "undefined" && selected_project_common_url_prefix)
						str = str.replace(m[0], selected_project_common_url_prefix);
				}
			}
		}
		
		//console.log("new str:"+str);
		//console.log("replace_inline_vars:"+replace_inline_vars);
	}
	
	return str;
}

//This function will be used by the widgets in the LayoutUIEditor, mainly the script, link, href, image, video and iframe widgets.
//give_priority_to_original_project_url_prefix should be true on edit_template_simple. The edit_entity_simple should have the project_url_prefix var instead.
function convertProjectUrlRealValuesToPHPVars(str, give_priority_to_original_project_url_prefix, is_str_html_base) {
	if (str) {
		var replace_func = function(to_search, to_replace, text) {
			do {
				text = text.replace(to_search, to_replace);
			}
			while (text.indexOf(to_search) != -1);
			
			return text;
		};
		
		if (typeof selected_project_url_prefix != "undefined" && selected_project_url_prefix && str.indexOf(selected_project_url_prefix) != -1) {
			var var_name = give_priority_to_original_project_url_prefix ? "original_project_url_prefix" : "project_url_prefix";
			var replacement = is_str_html_base ? "<?= $" + var_name + " ?>" : "{$" + var_name + "}";
			//console.log("replacement:"+replacement);
			str = replace_func(selected_project_url_prefix, replacement, str);
		}
		
		if (typeof selected_project_common_url_prefix != "undefined" && selected_project_common_url_prefix && str.indexOf(selected_project_common_url_prefix) != -1) {
			//var var_name = give_priority_to_original_project_url_prefix ? "original_project_common_url_prefix" : "project_common_url_prefix";
			var var_name = "project_common_url_prefix";
			var replacement = is_str_html_base ? "<?= $" + var_name + " ?>" : "{$" + var_name + "}";
			str = replace_func(selected_project_common_url_prefix, replacement, str);
		}
		//console.log(str);
	}
	
	return str;
}

function addProgrammingTaskUtilInputsContextMenu(elm) {
	if (typeof ProgrammingTaskUtil == "object" && typeof MyContextMenu == "object" && elm && elm[0] && elm.attr("is_context_menu_init") != 1) {
		elm.attr("is_context_menu_init", 1)
		
		var doc = elm[0].ownerDocument || elm[0].document;
		var body = $(doc.body);
		var context_menu = body.children("#sla_input_context_menu");
		
		if (!context_menu[0]) {
			context_menu = $('<ul id="sla_input_context_menu" class="mycontextmenu layout-ui-editor-menu-settings-context-menu"></ul>');
			
			if (ProgrammingTaskUtil.on_programming_task_choose_created_variable_callback) {
				context_menu.append('<li class="choose_variable"><a>Choose Variable</a></li>');
				context_menu.children().last("li").children("a").on("click", function() {
					ProgrammingTaskUtil.on_programming_task_choose_created_variable_callback( MyContextMenu.getSelectedEventTarget() );
				});
			}
			
			if (ProgrammingTaskUtil.on_programming_task_choose_file_path_callback) {
				context_menu.append('<li class="choose_file_path"><a>Choose File Path</a></li>');
				context_menu.children().last("li").children("a").on("click", function() {
					ProgrammingTaskUtil.on_programming_task_choose_file_path_callback( MyContextMenu.getSelectedEventTarget() );
				});
			}
			
			if (ProgrammingTaskUtil.on_programming_task_choose_folder_path_callback) {
				context_menu.append('<li class="choose_folder_path"><a>Choose Folder Path</a></li>');
				context_menu.children().last("li").children("a").on("click", function() {
					ProgrammingTaskUtil.on_programming_task_choose_folder_path_callback( MyContextMenu.getSelectedEventTarget() );
				});
			}
			
			if (ProgrammingTaskUtil.on_programming_task_choose_page_url_callback) {
				context_menu.append('<li class="choose_page_url"><a>Choose Page Url</a></li>');
				context_menu.children().last("li").children("a").on("click", function() {
					ProgrammingTaskUtil.on_programming_task_choose_page_url_callback( MyContextMenu.getSelectedEventTarget() );
				});
			}
			
			if (ProgrammingTaskUtil.on_programming_task_choose_image_url_callback) {
				context_menu.append('<li class="choose_image_url"><a>Choose Image Url</a></li>');
				context_menu.children().last("li").children("a").on("click", function() {
					ProgrammingTaskUtil.on_programming_task_choose_image_url_callback( MyContextMenu.getSelectedEventTarget() );
				});
			}
			
			if (context_menu.children().length > 0)
				body.append(context_menu);
			else
				context_menu = null;
		}
		
		if (context_menu && context_menu[0]) {
			var inputs = elm.is("input") ? elm : elm.find("input");
			
			inputs.each(function(idx, input) {
				if ((!input.type || input.type == "text" || input.type == "search" || input.type == "url" || input.type == "hidden") && !MyContextMenu.isContextMenuSet(input))
					$(input).addcontextmenu(context_menu, {callback: null});
			});
		}
	}
}

/* SAVING FUNCTIONS */

function isCodeAndWorkflowObjChanged(main_obj_with_tabs) {
	//checks if code is different
	var code = getEditorCodeRawValue();
	var new_code_id = getCodeId(code);
	var old_code_id = $("#ui").attr("code_id");
	
	var is_changed = old_code_id != new_code_id;
	
	//if code is the same and task flow tab was already opened by user, checks if diagram is different but ignoring the tasks positioning, this is, only comparing the tasks' data.
	if (!is_changed) {
		var is_tasks_flow_tab_inited = main_obj_with_tabs.find(" > ul > #tasks_flow_tab > a").attr("is_init");
		
		if (is_tasks_flow_tab_inited) {
			var old_workflow_id = $("#ui").attr("workflow_id");
			var new_workflow_id = getCurrentWorkFlowId();
			
			is_changed = old_workflow_id != new_workflow_id;
			
			//if code and diagram are the same, checks if the the tasks positioning are different
			if (!is_changed) {
				var selected_tab = main_obj_with_tabs.children("ul").find("li.ui-tabs-selected, li.ui-tabs-active").first();
				
				is_changed = selected_tab.attr("id") == "tasks_flow_tab" && taskFlowChartObj.TaskFile.isWorkFlowChangedFromLastSaving(); //compares if tasks' sizes and offsets are different, but only if workflow tab is selected.
			}
		}
	}
	
	/*console.log("is_changed:"+is_changed);
	console.log("old_workflow_id:"+old_workflow_id);
	console.log("new_workflow_id:"+new_workflow_id);
	console.log("old_code_id:"+old_code_id);
	console.log("new_code_id:"+new_code_id);*/
	
	return is_changed;
}

function getCodeForSaving(parent_elm, options) {
	prepareAutoSaveVars();
	
	if (!is_from_auto_save) { //only show loading bar if a manual save action
		MyFancyPopup.init({
			parentElement: window,
		});
		MyFancyPopup.showOverlay();
		MyFancyPopup.showLoading();
		
		$(".workflow_menu").hide();
	}
	
	options = typeof options == "object" ? options : {};
	
	var raw_code = getEditorCodeRawValue();
	var code = options.strip_php_tags ? getEditorCodeValue() : raw_code;
	var new_code_id = getCodeId(raw_code);
	var old_code_id = $("#ui").attr("code_id");
	
	var is_tasks_flow_tab_inited = parent_elm.find("#tasks_flow_tab a").attr("is_init");
	var status = true;
	
	if (is_tasks_flow_tab_inited) {
		var old_workflow_id = $("#ui").attr("workflow_id");
		var new_workflow_id = getCurrentWorkFlowId();
		var selected_tab = parent_elm.children("ul").find("li.ui-tabs-selected, li.ui-tabs-active").first();
		
		//if in tasks_flow_tab, saves workflow if manual save action, then gets the new code to be saved, if auto_convert is active or if user accepts confirmation message.
		if (selected_tab.attr("id") == "tasks_flow_tab") {
			updateTasksFlow();
			
			//close properties popup in case the auto_save be active on close task properties popup, but only if is not auto_save, otherwise the task properties can become messy, like it happens with the task inlinehtml.
			if (auto_save && taskFlowChartObj.Property.auto_save && !is_from_auto_save) {
				if (taskFlowChartObj.Property.isSelectedTaskPropertiesOpen())
					taskFlowChartObj.Property.saveTaskProperties({do_not_call_hide_properties: true});
				else if (taskFlowChartObj.Property.isSelectedConnectionPropertiesOpen())
					taskFlowChartObj.Property.saveConnectionProperties({do_not_call_hide_properties: true});
				
				new_workflow_id = getCurrentWorkFlowId();
			}
			
			//save workflow
			if (taskFlowChartObj.TaskFile.isWorkFlowChangedFromLastSaving() || !is_from_auto_save) { //if it is a manual save action, saves workflow
				//set old code id into file
				if (!$.isPlainObject(taskFlowChartObj.TaskFile.file_settings))
					taskFlowChartObj.TaskFile.file_settings = {code_id: old_code_id};
				else if (!taskFlowChartObj.TaskFile.file_settings.hasOwnProperty("code_id"))
					taskFlowChartObj.TaskFile.file_settings["code_id"] = old_code_id;
				//console.log(taskFlowChartObj.TaskFile.file_settings);
				
				status = taskFlowChartObj.TaskFile.save(null, {
					overwrite: true, 
					silent: true, 
					do_not_silent_errors: !is_from_auto_save, //only show errors if not from auto_save
					success: taskFlowChartObj.TaskFile.save_options["success"], 
				});
			}
			
			//generate code if some changes in workflow
			if (status && ((old_workflow_id != new_workflow_id) || (old_code_id != new_code_id))) {
				var convert_code = auto_convert || (!is_from_auto_save && confirm("Do you wish to generate new code based in the Workflow UI tab, before you save?\nIf you click the cancel button, the system will discard the changes in the UI tab and give preference to the Code tab.")); //if auto_convert is active or is a manual user save action with a confirmation msg.
				
				if (convert_code) {
					status = generateCodeFromTasksFlow(true, {do_not_change_to_code_tab: auto_convert, async: false});
					
					if (status) 
						code = options.strip_php_tags ? getEditorCodeValue() : getEditorCodeRawValue();
				}
			}
		}
		else if (selected_tab.attr("id") == "code_editor_tab" || selected_tab.attr("id") == "visual_editor_tab") { //if in code_editor_tab or in visual_editor_tab and auto_convert is active, saves the code as it is and then converts asynchronous the workflow and then save it.
			if (auto_convert)
				generateTasksFlowFromCode(true, {
					success: function() {
						//only saves workflow if it was really generated
						if (old_code_id != new_code_id) { 
							//disable auto_convert so it doesn't call the generateTasksFlowFromCode method in onClickTaskWorkflowTab
							auto_convert = false;
							
							//click in workflow tab
							parent_elm.find("ul #tasks_flow_tab a").first().click();
							updateTasksFlow();
							
							//save workflow
							taskFlowChartObj.TaskFile.save(null, {
								overwrite: true, 
								silent: true, 
								do_not_silent_errors: true,
								success: taskFlowChartObj.TaskFile.save_options["success"],
							});
							
							//click in code tab
							selected_tab.children("a").click();
							
							//enable auto_convert
							auto_convert = true;
						}
						else if (is_from_auto_save) //remove messages saying there are no changes to save, but only if is auto_save, otherwise if manual save, we are removing the message saying "saved successfully"
							StatusMessageHandler.removeMessages("info");
					},
				});
		}
		else { //if in other tab (like any other tab that is not the code and task_flow tabs), saves workflow and if workflow is changed, generate the new code
			//backup auto_convert value
			var auto_convert_bkp = auto_convert;
			
			auto_convert = false; //set auto_convert to false so it doesn't call the generateTasksFlowFromCode method in onClickTaskWorkflowTab
		
			//click in workflow tab
			parent_elm.find("ul #tasks_flow_tab a").first().click();
			updateTasksFlow();
			
			//close properties popup in case the auto_save be active on close task properties popup, but only if is not auto_save, otherwise the task properties can become messy, like it happens with the task inlinehtml.
			if (auto_save && taskFlowChartObj.Property.auto_save && !is_from_auto_save) {
				if (taskFlowChartObj.Property.isSelectedTaskPropertiesOpen())
					taskFlowChartObj.Property.saveTaskProperties({do_not_call_hide_properties: true});
				else if (taskFlowChartObj.Property.isSelectedConnectionPropertiesOpen())
					taskFlowChartObj.Property.saveConnectionProperties({do_not_call_hide_properties: true});
				
				new_workflow_id = getCurrentWorkFlowId();
			}
			
			//save workflow
			if (taskFlowChartObj.TaskFile.isWorkFlowChangedFromLastSaving() || !is_from_auto_save) { //if it is a manual save action, saves workflow
				//set old code id into file
				if (!$.isPlainObject(taskFlowChartObj.TaskFile.file_settings))
					taskFlowChartObj.TaskFile.file_settings = {code_id: old_code_id};
				else if (!taskFlowChartObj.TaskFile.file_settings.hasOwnProperty("code_id"))
					taskFlowChartObj.TaskFile.file_settings["code_id"] = old_code_id;
				//console.log(taskFlowChartObj.TaskFile.file_settings);
				
				status = taskFlowChartObj.TaskFile.save(null, {
					overwrite: true, 
					silent: true, 
					do_not_silent_errors: !is_from_auto_save, //only show errors if not from auto_save
					success: taskFlowChartObj.TaskFile.save_options["success"], 
				});
			}
			
			//generate code if some changes in workflow
			if (status && ((old_workflow_id != new_workflow_id) || (old_code_id != new_code_id))) {
				var convert_code = auto_convert_bkp || (!is_from_auto_save && confirm("Do you wish to generate new code based in the Workflow UI tab, before you save?\nIf you click the cancel button, the system will discard the changes in the UI tab and give preference to the Code tab.")); //if auto_convert is active or is a manual user save action with a confirmation msg.
				
				if (convert_code) {
					status = generateCodeFromTasksFlow(true, {do_not_change_to_code_tab: auto_convert_bkp, async: false});
					
					if (status) 
						code = options.strip_php_tags ? getEditorCodeValue() : getEditorCodeRawValue();
				}
			}
			
			//click in code tab
			selected_tab.children("a").click();
			
			//reset the auto_convert with previous value
			auto_convert = auto_convert_bkp;
		}
	}
	
	return status ? code : null;
}

function saveObjCode(save_object_url, obj, opts) {
	if (obj && obj.code != null && (!obj.errors || obj.errors.length == 0))
		saveObj(save_object_url, obj, opts);
	else {
		//prepare and clone opts just in case exists a concurrent process that changes something in the opts.
		opts = opts ? opts : {};
		
		//prepare error func
		var error_func = typeof opts.error == "function" ? function() {
			try { //just in case execute the func inside of a try and catch to continue executing code below
				return opts.error();
			}
			catch(e) {
				if (console && console.log)
					console.log(e);
			}
		} : null;
		
		//call error func
		if (!error_func || error_func()) {
			if (!is_from_auto_save)
				StatusMessageHandler.showError("Error trying to save this file. Please try again...");
		}
		
		//call complete func
		if (typeof opts.complete == "function") {
			try { //just in case execute the func inside of a try and catch to continue executing code below
				opts.complete();
			}
			catch(e) {
				if (console && console.log)
					console.log(e);
			}
		}
		
		//hide popup and reset auto save
		if (!is_from_auto_save) {
			MyFancyPopup.hidePopup();
			$(".workflow_menu").show();
		}
		else
			resetAutoSave();
	}
}

function saveObj(save_object_url, obj, opts) {
	//saves the running_save_obj_actions_count to a local variable before it executes anything else, and then sets it to true.
	var running_save_obj_actions_count_bkp = running_save_obj_actions_count;
	//console.log("running_save_obj_actions_count:"+running_save_obj_actions_count_bkp);
	running_save_obj_actions_count++;
	
	//prepare and clone opts just in case exists a concurrent process that changes something in the opts.
	opts = opts ? opts : {};
	
	//backup the is_from_auto_save, just in case there is an auto_save concurrent process running, it will change the value of is_from_auto_save after the ajax request below, so we must save this value to a local variable.
	var is_from_auto_save_bkp = is_from_auto_save; 
	
	//disable auto_save during the saving action
	var auto_save_bkp = auto_save;
	
	if (/*!is_from_auto_save_bkp && */auto_save_bkp && (isAutoSaveMenuEnabled() || isPHPCodeAutoSaveMenuEnabled()))
		auto_save = false;
	
	//prepare complete func, by resetting the running_save_obj_actions_count var
	var complete_func = function() {
		var ret = null;
		
		if (typeof opts.complete == "function") {
			try { //just in case execute the func inside of a try and catch to continue executing code below
				ret = opts.complete();
				
				//Note that if the opts.complete wants to change the value for running_save_obj_actions_count, after some internal action, it should return an object with do_not_update_running_save_obj_actions_count=true.
			}
			catch(e) {
				if (console && console.log)
					console.log(e);
			}
		}
		
		//sets auto_save back to its original value
		if (/*!is_from_auto_save_bkp && */auto_save_bkp && (isAutoSaveMenuEnabled() || isPHPCodeAutoSaveMenuEnabled())) //This must be before the success_func and error_func get executed or before the saveObj
			auto_save = auto_save_bkp;
		
		//hide popup and reset auto save
		if (!is_from_auto_save_bkp) {
			MyFancyPopup.hidePopup();
			$(".workflow_menu").show();
		}
		else
			resetAutoSave();
		
		//change running_save_obj_actions_count to false. If the opts.complete wants to change the value for running_save_obj_actions_count, after some internal action, it should return an object with do_not_update_running_save_obj_actions_count=true.
		if (!ret || !$.isPlainObject(ret) || !ret["do_not_update_running_save_obj_actions_count"]) {
			running_save_obj_actions_count = running_save_obj_actions_count < 0 ? 0 : running_save_obj_actions_count - 1; //be sure that is never smalled than 0.
			//console.log("running_save_obj_actions_count:"+running_save_obj_actions_count);
		}
	};
	
	if (running_save_obj_actions_count_bkp == 0) {
		//if there was a previous function that tried to execute an ajax request, like the getCodeForSaving method, we detect here if the user needs to login, and if yes, alerts him that needs to login first. 
		//Do not re-call this method (saveObj) again, otherwise there could be some other files that will not be saved, this is, the getCodeForSaving saves the workflow and if we only call the saveObj method, the workflow won't be saved. To avoid this situation, we simple show a message so the user can execute again the save action manually.
		var needs_to_login = jquery_native_xhr_object && isAjaxReturnedResponseLogin(jquery_native_xhr_object.responseURL); 
		
		if (!needs_to_login) {
			//prepare parse_url func
			var parse_url_func = typeof opts.parse_url == "function" ? function(url) {
				try { //just in case execute the func inside of a try and catch to continue executing code below
					return opts.parse_url(url);
				}
				catch(e) {
					if (console && console.log)
						console.log(e);
				}
			} : null;
			
			//prepare success func
			var success_func = typeof opts.success == "function" ? function(data, textStatus, jqXHR) {
				try { //just in case execute the func inside of a try and catch to continue executing code below
					return opts.success(data, textStatus, jqXHR);
				}
				catch(e) {
					if (console && console.log)
						console.log(e);
				}
			} : null;
			
			//prepare error func
			var error_func = typeof opts.error == "function" ? function(jqXHR, textStatus, errorThrown) {
				try { //just in case execute the func inside of a try and catch to continue executing code below
					return opts.error(jqXHR, textStatus, errorThrown);
				}
				catch(e) {
					if (console && console.log)
						console.log(e);
				}
			} : null;
			
			//prepare confirmation_save func
			var confirmation_save_func = typeof opts.confirmation_save == "function" ? function(data) {
				try { //just in case execute the func inside of a try and catch to continue executing code below
					return opts.confirmation_save(data);
				}
				catch(e) {
					if (console && console.log)
						console.log(e);
				}
			} : null;
			
			//prepare confirmation_cancel func
			var confirmation_cancel_func = typeof opts.confirmation_cancel == "function" ? function(data) {
				try { //just in case execute the func inside of a try and catch to continue executing code below
					return opts.confirmation_cancel(data);
				}
				catch(e) {
					if (console && console.log)
						console.log(e);
				}
			} : null;
			
			//prepare url
			var url = save_object_url + (typeof file_modified_time != "undefined" && file_modified_time ? (save_object_url.indexOf("?") != -1 ? "&" : "?") + "file_modified_time=" + file_modified_time : "");
			var url_aux = save_object_url;
			
			if (parse_url_func) {
				url = parse_url_func(url);
				url_aux = parse_url_func(url_aux);
			}
			
			//Note: DO NOT use the prepareAutoSaveVars bc the auto_save maybe was disable before by a parent function which already called prepareAutoSaveVars before disable the auto_save, like it happens in the edit_template_simple.js and edit_entity_simple.js.
			
			var new_saved_obj_id = $.md5(url_aux + JSON.stringify(obj));
			
			//only saves if object is different
			if (!saved_obj_id || saved_obj_id != new_saved_obj_id) {
				//prepare save ajax settings
				var ajax_options = {
					type : "post",
					url : url,
					data : {"object" : obj},
					dataType : "text",
					success : function(data, textStatus, jqXHR) {
						//show login popup
						if (jquery_native_xhr_object && isAjaxReturnedResponseLogin(jquery_native_xhr_object.responseURL))
							showAjaxLoginPopup(jquery_native_xhr_object.responseURL, url, function() {
								$.ajax(ajax_options);
							});
						else {
							var json_data = data && ("" + data).substr(0, 1) == "{" ? JSON.parse(data) : null;
							var status = parseInt(data) == 1 || ($.isPlainObject(json_data) && json_data["status"] == 1);
							var file_was_changed = !status && $.isPlainObject(json_data) && json_data["status"] == "CHANGED";
							
							//if status is true
							if (status) {
								//sets new file modified time
								if ($.isPlainObject(json_data) && json_data["modified_time"])
									file_modified_time = json_data["modified_time"];
								
								//sets new saved_obj_id
								saved_obj_id = new_saved_obj_id;
								
								//set new code id if tasks_flow_tab was not inited yet
								if ($("#code, #ui").length >= 2) {
									var is_tasks_flow_tab_inited = $("#code").parent().find(" > ul > #tasks_flow_tab > a").attr("is_init") == 1;
									
									if (!is_tasks_flow_tab_inited) {
										var raw_code = getEditorCodeRawValue();
										var new_code_id = getCodeId(raw_code);
										$("#ui").attr("code_id", new_code_id);
										
										//set new code id into file settings
										if (!$.isPlainObject(taskFlowChartObj.TaskFile.file_settings))
											taskFlowChartObj.TaskFile.file_settings = {};
										
										taskFlowChartObj.TaskFile.file_settings["code_id"] = new_code_id;
										//console.log(taskFlowChartObj.TaskFile.file_settings);
									}
								}
								
								//call on success 
								if (!success_func || success_func(data, textStatus, jqXHR)) {
									if (!is_from_auto_save_bkp) //only show message if a manual save action
										StatusMessageHandler.showMessage("Saved successfully.", "", "bottom_messages", 1500);
								}
								
								//call on complete func
								complete_func();
							}
							//if status is FILE CHANGED
							else if (file_was_changed) {
								//if manual save, show confirmation popup
								if (!is_from_auto_save_bkp) { 
									//hide popup so the user can see the confirmation popup, otherwise it appears bellow the loading icon and correspondent overlay
									MyFancyPopup.hidePopup();
									
									//only show this message if is a manual save, otherwise we don't want to do anything. Otherwise the browser is showing this popup constantly and is annoying for the user.
									showConfirmationCodePopup(json_data["old_code"], json_data["new_code"], {
										save: function() {
											//if confirmation_save is true, shows loading icon again and call saveObj
											if (!confirmation_save_func || confirmation_save_func(data)) {
												//show loading icon again
												MyFancyPopup.showOverlay();
												MyFancyPopup.showLoading();
												
												//prepare url, by removing the file_modified_time from the url, so the ajax request doesn't check if the file was changed. 
												var ajax_options_clone = assignObjectRecursively({}, ajax_options);
												ajax_options_clone.url = ajax_options_clone.url.replace(/(&|\?)file_modified_time=([0-9]*)/g, "");
												
												//call saveObj again
												$.ajax(ajax_options_clone); //This function will then hide the loading icon, show the workflow_menu and call the complete_func handler
												
												return true;
											}
											else //Don't do anything, so the confirmation popup continues showing. If the user wants to close it, it needs to click in the cancel button.
												StatusMessageHandler.showError("Error: cannot proceed with save action!"); //by default the confirmation_save method should show a error message if is false, but in case it gives a javascript error, we show/append this error too.
										},
										cancel: function() {
											//call on complete func
											complete_func();
											
											//call confirmation cancel
											return !confirmation_cancel_func || confirmation_cancel_func(data);
										},
									});
								}
								else { //if is auto save
									//call on complete func
									complete_func();
								}
							}
							else {
								//call error func and show error message
								if (!error_func || error_func(jqXHR, textStatus, data)) {
									if (!is_from_auto_save_bkp) //only show error if manual save, bc if is auto_save the user shouldn't be bother with errors while is changing the code.
									StatusMessageHandler.showError("Error trying to save new changes. Please try again..." + (data ? "\n" + data : ""));
								}
								
								//call on complete func
								complete_func();
							}
						}
					},
					error : function(jqXHR, textStatus, errorThrown) { 
						//shows login popup
						if (jquery_native_xhr_object && isAjaxReturnedResponseLogin(jquery_native_xhr_object.responseURL))
							showAjaxLoginPopup(jquery_native_xhr_object.responseURL, url, function() {
								$.ajax(ajax_options);
							});
						else {
							//call on error and show error message
							if (!error_func || error_func(jqXHR, textStatus, errorThrown)) {
								if (!is_from_auto_save_bkp) //only show error if manual save, bc if is auto_save the user shouldn't be bother with errors while is changing the code.
									StatusMessageHandler.showError((errorThrown ? errorThrown + " error.\n" : "") + "Error trying to save new changes. Please try again..." + (jqXHR.responseText ? "\n" + jqXHR.responseText : ""));
							}
							
							//call on complete func
							complete_func();
						}
					},
				};
				
				//sets connection ttl
				if (is_from_auto_save_bkp && auto_save_connection_ttl)
					ajax_options["timeout"] = auto_save_connection_ttl; //add timeout to auto save connection.
				
				//sets async
				if (opts.hasOwnProperty("async")) 
					ajax_options["async"] = opts["async"];
				
				//executes ajax
				$.ajax(ajax_options);
			}
			else {
				//call on complete func
				complete_func();
				
				if (!is_from_auto_save_bkp)
					StatusMessageHandler.showMessage("Nothing to save.", "", "bottom_messages", 1500);
			}
		}
		else {
			//call on complete func
			complete_func();
			
			if (!is_from_auto_save_bkp) {
				//if there was a previous function that tried to execute an ajax request, like the getCodeForSaving method, we detect here if the user needs to login, and if yes, alerts him that needs to login first. 
				//Do not re-call this method (saveObj) again, otherwise there could be some other files that will not be saved, this is, the getCodeForSaving saves the workflow and if we only call the saveObj method, the workflow won't be saved. To avoid this situation, we simple show a message so the user can execute again the save action manually.
				showAjaxLoginPopup(jquery_native_xhr_object.responseURL, url, function() {
					StatusMessageHandler.showError("The file was not saved previously, but now that you are logged in, please save it again...");
				});
			}
		}
	}
	else {
		//call on complete func
		complete_func();
		
		if (!is_from_auto_save_bkp)
			StatusMessageHandler.showMessage("There is already a saving process running. Please wait a few seconds and try again...");
	}
}

function showConfirmationCodePopup(old_code, new_code, opts) {
	old_code = typeof old_code == "undefined" || old_code == null ? "" : old_code;
	new_code = typeof new_code == "undefined" || new_code == null ? "" : new_code;
	opts = opts ? opts : {};
	
	var confirm_save_elm = $(".confirm_save");
	
	//prepare popup
	if (!confirm_save_elm[0]) {
		confirm_save_elm = $('<div class="confirm_save hidden">'
			+ '	<div class="title">Please confirm if the code is correct and if it is, click on the save button...</div>'
			+ '	'
			+ '	<div class="code_comparison">'
			+ '		<div class="old_code">'
			+ '			<label>Old code:</label>'
			+ '			<pre><code class="php"></code></pre>'
			+ '		</div>'
			+ '		<div class="new_code">'
			+ '			<label>New code to be saved:</label>'
			+ '			<pre><code class="php"></code></pre>'
			+ '		</div>'
			+ '	</div>'
			+ '	'
			+ '	<div class="buttons"></div>'
			+ '	'
			+ '	<div class="disable_auto_scroll">Click here to disable auto scroll.</div>'
			+ '	<div class="disable_word_wrap">Click here to enable word wrap.</div>'
			+ '</div>');
		
		$("body").append(confirm_save_elm);
		
		//prepare code ui
		var old_code_area = confirm_save_elm.find(".code_comparison .old_code pre code");
		var new_code_area = confirm_save_elm.find(".code_comparison .new_code pre code");
		var old_code_parent = old_code_area.parent();
		var new_code_parent = new_code_area.parent();
		
		old_code_parent.scroll(function() {
			if (auto_scroll_active) {
				auto_scroll_active = false;
				
				new_code_parent.scrollTop( $(this).scrollTop() );
				new_code_parent.scrollLeft( $(this).scrollLeft() );
				
				setTimeout(function() {
					auto_scroll_active = true;
				}, 50);
			}
		});
		
		new_code_parent.scroll(function() {
			if (auto_scroll_active) {
				auto_scroll_active = false;
				
				old_code_parent.scrollTop( $(this).scrollTop() );
				old_code_parent.scrollLeft( $(this).scrollLeft() );
				
				setTimeout(function() {
					auto_scroll_active = true;
				}, 50);
			}
		});

		confirm_save_elm.children(".disable_auto_scroll").on("click", function (ev) {
			auto_scroll_active = !auto_scroll_active;
			$(this).html(auto_scroll_active ? "Click here to disable auto scroll." : "Click here to enable auto scroll.");
		});

		confirm_save_elm.children(".disable_word_wrap").on("click", function (ev) {
			word_wrap_active = !word_wrap_active;
			$(this).html(word_wrap_active ? "Click here to disable word wrap." : "Click here to enable word wrap.");
			
			if (word_wrap_active) {
				old_code_area.css({"display": "block", "white-space": "pre-line"});
				new_code_area.css({"display": "block", "white-space": "pre-line"});
			}
			else {
				old_code_area.css({"display": "", "white-space": ""});
				new_code_area.css({"display": "", "white-space": ""});
			}
		});
	}
	
	//prepare buttons
	var buttons = confirm_save_elm.find(".buttons");
	buttons.html('<input class="cancel" type="button" name="cancel" value="Cancel" /><input class="save" type="button" name="save" value="Save" />'); //be sure that everytime the popup is open has new buttons with new handlers
	
	buttons.children(".save").on("click", function(ev) {
		if (typeof opts.save != "function" || opts.save())
			confirm_save_elm.hide();
	});
	
	buttons.children(".cancel").on("click", function(ev) {
		if (typeof opts.cancel != "function" || opts.cancel())
			confirm_save_elm.hide();
	});
	
	//prepare codes
	var old_code_area = confirm_save_elm.find(".code_comparison .old_code pre code");
	var old_code_parsed = old_code ? old_code.replace(/>/g, "&gt;").replace(/</g, "&lt;") : "";
	old_code_area.html(old_code_parsed);
	
	var new_code_area = confirm_save_elm.find(".code_comparison .new_code pre code");
	var new_code_parsed = new_code ? new_code.replace(/>/g, "&gt;").replace(/</g, "&lt;") : "";
	new_code_area.html(new_code_parsed);
	
	confirm_save_elm.show();
	
	if (typeof hljs == "object") {
		hljs.highlightBlock(old_code_area[0]);
		hljs.highlightBlock(new_code_area[0]);
	}
	
	if (old_code.trim() == "" || old_code.trim().hashCode() == new_code.trim().hashCode())
		buttons.children(".save").trigger("click");
}
