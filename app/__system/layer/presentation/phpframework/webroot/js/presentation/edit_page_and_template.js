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

var choosePresentationIncludeFromFileManagerTree = null;
var chooseCodeLayoutUIEditorModuleBlockFromFileManagerTree = null;
var chooseCodeLayoutUIEditorModuleBlockFromFileManagerTreeRightContainer = null;

var CodeLayoutUIEditorFancyPopup = new MyFancyPopupClass();
var CodeLayoutUIEditorDBTableUisDiagramBlockFancyPopup = new MyFancyPopupClass();
var TemplateSamplesFancyPopup = new MyFancyPopupClass();
var TemplateRegionBlockHtmlEditorFancyPopup = new MyFancyPopupClass();
var TemplateRegionBlockComboBoxImportModuleBlockOptionFancyPopup = new MyFancyPopupClass();
var DBTableWidgetOptionsFancyPopup = new MyFancyPopupClass();
var DBTableWidgetOptionsCreateDBTableFancyPopup = new MyFancyPopupClass();
var ReplaceExistingDBTableWidgetFancyPopup = new MyFancyPopupClass();

var block_params_values_list = {};
var regions_blocks_params_latest_values = {};
var project_blocks_params = {};
var project_blocks_params_loading = {};
var project_blocks_params_loading_interval_id = {};
var available_blocks_list_inited = false;
var available_blocks_list_loading = false;
var available_blocks_list_options_html = "";
var loaded_modules_info = {};
var shown_module_info_id = null;
var show_module_info_timeout = null;
var resize_iframe_timeout = null;
var entity_or_template_obj_inited = false;
var auto_convert_settings_from_layout = true;
var settings_need_to_be_converted_from_layout = false;

var update_layout_iframe_field_html_value_from_settings_func = null;
var update_settings_from_layout_iframe_func = null;
var update_settings_from_layout_iframe_timeout_id = null;
var update_layout_iframe_from_settings_func = null;
var update_layout_iframe_from_settings_timeout_id = null;

var regions_blocks_html_editor_save_func = null;

/* TEMPLATE-REGIONS-BLOCKS */

function createChoosePresentationIncludeFromFileManagerTree() {
	choosePresentationIncludeFromFileManagerTree = new MyTree({
		multiple_selection : false,
		toggle_children_on_click : true,
		ajax_callback_before : prepareLayerNodes1,
		ajax_callback_after : removeAllInvalidPresentationIncludePagesFromTree,
	});
	choosePresentationIncludeFromFileManagerTree.init("choose_presentation_include_from_file_manager");
}

function removeAllInvalidPresentationIncludePagesFromTree(ul, data) {
	ul = $(ul);
	
	ul.find("i.controller_file, i.undefined_file, i.js_file, i.css_file, i.img_file, i.cache_file, i.controllers_folder, i.caches_folder, i.routers_folder, i.dispatchers_folder").each(function(idx, elm){
		$(elm).parent().parent().remove();
	});
	
	ul.find("i.folder").each(function(idx, elm){
		var label = $(elm).parent().children("label").text();
		
		if (label == "webroot" || label == "others") {
			$(elm).parent().parent().remove();
		}
	});
}

function removeAllThatIsNotBlocksOrModulesFromTree(ul, data, tree_obj) {
	ul = $(ul);
	
	ul.find("i.file, i.view_file, i.entity_file, i.template_file, i.util_file, i.controller_file, i.config_file, i.undefined_file, i.js_file, i.css_file, i.img_file, i.module_file, .entities_folder, .views_folder, .templates_folder, .template_folder, .utils_folder, .webroot_folder, .configs_folder, .controllers_folder, .caches_folder").each(function(idx, elm){
		$(elm).parent().parent().remove();
	});
	
	ul.find("li.module_folder_disabled").each(function(idx, elm) {
		$(elm).remove();
	});
	
	ul.find("i.project_folder").each(function(idx, elm) {
		//adding refresh button to folders
		addRefreshIconToFileManagerPopupTreeNode(elm, tree_obj);
	});
	
	ul.find("i.folder").each(function(idx, elm) {
		elm = $(elm);
		var a = elm.parent();
		var li = a.parent();
		var label = a.children("label").text();
		
		if (label == "pages (entities)" || label == "views" || label == "templates" || label == "utils" || label == "webroot" || label == "configs")
			li.remove();
		else {
			//adding refresh button to folders
			addRefreshIconToFileManagerPopupTreeNode(elm, tree_obj);
		}
	});
	
	ul.find("i.project, i.project_common").each(function(idx, elm) {
		elm = $(elm);
		var a = elm.parent();
		var li = a.parent();
		var project_ul = li.children("ul");
		
		project_ul.find("i.properties").each(function(idx, sub_elm) { //remove "others" folder
			$(sub_elm).parent().parent().remove();
		});
		
		//adding refresh button to folders
		addRefreshIconToFileManagerPopupTreeNode(elm, tree_obj);
	});
	
	ul.find("i.blocks_folder").each(function(idx, elm) {
		elm = $(elm);
		var a = elm.parent();
		var li = a.parent();
		var blocks_folder_ul = li.children("ul");
		var project_li = li.parent().parent();
		
		if (project_li.children("a").children("i.project, i.project_common").length > 0) {
			var project_ul = project_li.children("ul");
			project_li.append(blocks_folder_ul);
			project_ul.remove();
		}
		else {
			li.addClass("jstree-last");
			
			//adding refresh button to folders
			addRefreshIconToFileManagerPopupTreeNode(elm, tree_obj);
		}
	});
	
	//prepare block draggable items
	ul.find("i.block_file").each(function(idx, elm) {
		elm = $(elm);
		var a = elm.parent();
		var li = a.parent();
		
		//adding edit button to edit blocks
		if (typeof edit_block_url != "undefined") {
			var edit_icon = $('<i class="icon edit" title="Edit Block"></i>');
			edit_icon.on("click", function(event) {
				event.preventDefault();
				event.stopPropagation();
				
				var url = edit_block_url.replace("#path#", a.attr("file_path"));
				openPretifyRegionBlockComboBoxImportModuleBlockOptionPopup(null, url);
			});
			a.after(edit_icon);
		}
		
		initIframeModulesBlocksToolbarDraggableMenuItem(li);
	});
	
	//prepare module draggable items
	ul.find("li.module_folder_enabled").each(function(idx, elm) {
		elm = $(elm);
		var a = elm.children("a");
		
		elm.removeClass("jstree-close").addClass("jstree-leaf");
		elm.children("ul").remove();
		
		//adding edit button to edit blocks
		var info_icon = $('<i class="icon info" title="Module Info"></i>');
		info_icon.on("click", function(event) {
			event.preventDefault();
			event.stopPropagation();
			
			var func = a.attr("module_info_func_name");
			eval(func + "(a[0])");
		});
		a.after(info_icon);
		
		initIframeModulesBlocksToolbarDraggableMenuItem(elm);
	});
	
	//prepare table draggable items
	var tables = ul.find("i.table");
	
	if (tables.length > 0) {
		var db_driver_node = tables.first().parent().closest("li[db_driver_bean_name]");
		var db_driver_name = db_driver_node.attr("db_driver_name");
		var db_driver_broker = db_driver_node.attr("db_driver_broker");
		var add_table_to_db_brokers_drivers_tables_attributes = db_driver_broker && db_driver_name && $.isPlainObject(db_brokers_drivers_tables_attributes[db_driver_broker]) && $.isPlainObject(db_brokers_drivers_tables_attributes[db_driver_broker][db_driver_name]) && $.isPlainObject(db_brokers_drivers_tables_attributes[db_driver_broker][db_driver_name]["db"]);
		
		//add tables diagram icon
		if (typeof edit_db_driver_tables_diagram_url != "undefined" && db_driver_node.children(".sub_menu").length == 0) {
			var sub_menu = $('<span class="sub_menu" onclick="openSubmenu(this)">'
							+ '<i class="icon sub_menu active"></i>'
							+ '<ul class="mycontextmenu with_top_right_triangle">'
								+ '<li class="edit_diagram" title="Edit tables diagram">'
									+ '<a><i class="icon diagram"></i> Edit Tables Diagram</a>'
								+ '</li>'
								+ '<li class="toggle_reserved_tables" title="Show or hide reserved tables">'
									+ '<a><i class="icon enable"></i> <span>Show</span> Reserved Tables</a>'
								+ '</li>'
							+ '</ul>'
						+ '</span>');
			db_driver_node.children("a").after(sub_menu);
			
			sub_menu.find(" > ul > .edit_diagram a").on("click", function(event) {
				event.preventDefault();
				event.stopPropagation();
				
				var url = edit_db_driver_tables_diagram_url
					.replace("#bean_name#", db_driver_node.attr("db_driver_bean_name"))
					.replace("#bean_file_name#", db_driver_node.attr("db_driver_bean_file_name"))
					.replace("#layer_bean_folder_name#", db_driver_node.attr("db_driver_broker_folder"));
				
				openEditObjFromFileManagerTreePopup(db_driver_node, tree_obj, url);
			});
			
			sub_menu.find(" > ul > .toggle_reserved_tables a").on("click", function(event) {
				event.preventDefault();
				event.stopPropagation();
				
				db_driver_node.toggleClass("with_reserved_tables");
				
				if (db_driver_node.hasClass("with_reserved_tables"))
					$(this).children("span").html("Hide");
				else
					$(this).children("span").html("Show");
			});
		}
		
		//add refresh icon
		if (db_driver_node.children(".icon.refresh").length == 0)
			addRefreshIconToFileManagerPopupTreeNode(db_driver_node, tree_obj);
		
		//add insert new table icon
		if (typeof create_db_driver_table_or_attribute_url != "undefined" && db_driver_node.children(".icon.add").length == 0) {
			var add_icon = $('<i class="icon add" title="Add table"></i>');
			add_icon.on("click", function(event) {
				event.preventDefault();
				event.stopPropagation();
				
				var url = create_db_driver_table_or_attribute_url
					.replace("#bean_name#", db_driver_node.attr("db_driver_bean_name"))
					.replace("#bean_file_name#", db_driver_node.attr("db_driver_bean_file_name"))
					.replace("#layer_bean_folder_name#", db_driver_node.attr("db_driver_broker_folder"))
					.replace("#type#", db_driver_node.attr("db_driver_type"))
					.replace("#table#", "");
				
				openEditObjFromFileManagerTreePopup(db_driver_node, tree_obj, url);
			});
			db_driver_node.children("a").after(add_icon);
		}
		
		tables.each(function(idx, elm) {
			elm = $(elm);
			var a = elm.parent();
			var li = a.parent();
			
			//adding edit button to edit tables
			if (typeof create_db_driver_table_or_attribute_url != "undefined" && db_driver_node.length > 0) {
				var edit_icon = $('<i class="icon edit" title="Edit table"></i>');
				edit_icon.on("click", function(event) {
					event.preventDefault();
					event.stopPropagation();
					
					var url = create_db_driver_table_or_attribute_url
						.replace("#bean_name#", db_driver_node.attr("db_driver_bean_name"))
						.replace("#bean_file_name#", db_driver_node.attr("db_driver_bean_file_name"))
						.replace("#layer_bean_folder_name#", db_driver_node.attr("db_driver_broker_folder"))
						.replace("#type#", db_driver_node.attr("db_driver_type"))
						.replace("#table#", a.attr("table"));
					
					openEditObjFromFileManagerTreePopup(li.parent(), tree_obj, url);
				});
				a.after(edit_icon);
			}
			
			initIframeModulesBlocksToolbarDraggableMenuItem(li);
			
			//add table to db_brokers_drivers_tables_attributes
			if (add_table_to_db_brokers_drivers_tables_attributes)
				db_brokers_drivers_tables_attributes[db_driver_broker][db_driver_name]["db"][ a.attr("table") ] = {};
		});
	}
}

function onPresentationIncludeTaskChoosePage(elm) {
	var popup = $("#choose_presentation_include_from_file_manager");
	var auto_save_bkp = auto_save;
	auto_save = false;
	
	MyFancyPopup.init({
		elementToShow: popup,
		parentElement: document,
		
		onClose: function() {
			auto_save = auto_save_bkp;
		},
		targetField: $(elm).parent(),
		updateFunction: choosePresentationInclude
	});
	
	MyFancyPopup.showPopup();
}
function choosePresentationInclude(elm) {
	var node = choosePresentationIncludeFromFileManagerTree.getSelectedNodes();
	node = node[0];
	
	if (node) {
		var a = $(node).children("a");
		var file_path = a.attr("file_path");
		var bean_name = a.attr("bean_name");
		var include_path = file_path ? getNodeIncludePath(node, file_path, bean_name) : null;
		
		if (include_path) {
			var p = MyFancyPopup.settings.targetField;
			p.children("input.path").val(include_path);
			p.children("select").val("");
	
			MyFancyPopup.hidePopup();
		}
		else {
			alert("Selected item must be a valid page!\nPlease try again...");
		}
	}
}

function onPresentationProgrammingTaskChooseCreatedVariable(elm) {
	elm = $(elm);
	var p = $(elm).parent();
	var select = p.children("select");
	var target_field = elm.is("input[type=text]") ? elm : p.children("input[type=text]").first();
	
	if (!target_field[0]) //input may not have the type attribute
		target_field = elm.is("input") ? elm : p.children("input").first(); 
	
	var auto_save_bkp = auto_save;
	auto_save = false;
	
	onProgrammingTaskChooseCreatedVariable(elm);
	
	MyFancyPopup.settings.targetField = target_field[0];
	MyFancyPopup.settings.onClose = function() {
		auto_save = auto_save_bkp;
	};
	MyFancyPopup.settings.updateFunction = function(sub_elm) {
		chooseCreatedVariable(sub_elm);
		
		select.val("string");
		select.trigger("change");
	};
}

function onPresentationIncludePageUrlTaskChooseFile(elm) {
	elm = $(elm);
	var p = elm.parent();
	var select = p.children("select");
	var target_field = elm.is("input[type=text]") ? elm : p.children("input[type=text]").first();
	
	if (!target_field[0]) //input may not have the type attribute
		target_field = elm.is("input") ? elm : p.children("input").first(); 
	
	var auto_save_bkp = auto_save;
	auto_save = false;
	
	onIncludePageUrlTaskChooseFile(elm[0]);
	
	var popup = $("#choose_page_url_from_file_manager");
	onUrlQueryString(elm[0], popup, target_field);
	
	IncludePageUrlFancyPopup.settings.targetField = target_field;
	IncludePageUrlFancyPopup.settings.onClose = function() {
		auto_save = auto_save_bkp;
	};
	IncludePageUrlFancyPopup.settings.updateFunction = function(sub_elm) {
		chooseIncludePageUrl(sub_elm);
		
		select.val("string");
		select.trigger("change");
	};
}

function onPresentationIncludeImageUrlTaskChooseFile(elm) {
	elm = $(elm);
	var p = elm.parent();
	var select = p.children("select");
	var target_field = elm.is("input[type=text]") ? elm : p.children("input[type=text]").first();
	
	if (!target_field[0]) //input may not have the type attribute
		target_field = elm.is("input") ? elm : p.children("input").first(); 
	
	var auto_save_bkp = auto_save;
	auto_save = false;
	
	onIncludeImageUrlTaskChooseFile(elm[0]);
	
	MyFancyPopup.settings.targetField = target_field;
	MyFancyPopup.settings.onClose = function() {
		auto_save = auto_save_bkp;
	};
	MyFancyPopup.settings.updateFunction = function(sub_elm) {
		chooseIncludeImageUrl(sub_elm);
		
		select.val("string");
		select.trigger("change");
	};
}

function onPresentationIncludeWebrootFileUrlTaskChooseFile(elm) {
	elm = $(elm);
	var p = elm.parent();
	var select = p.children("select");
	var target_field = elm.is("input[type=text]") ? elm : p.children("input[type=text]").first();
	
	if (!target_field[0]) //input may not have the type attribute
		target_field = elm.is("input") ? elm : p.children("input").first(); 
	
	var auto_save_bkp = auto_save;
	auto_save = false;
	
	onIncludeWebrootFileUrlTaskChooseFile(elm[0]);
	
	MyFancyPopup.settings.targetField = target_field;
	MyFancyPopup.settings.onClose = function() {
		auto_save = auto_save_bkp;
	};
	MyFancyPopup.settings.updateFunction = function(sub_elm) {
		chooseIncludeWebrootFileUrl(sub_elm);
		
		select.val("string");
		select.trigger("change");
	};
}

//target_field is used by the workflow task: GetUrlContentsTaskPropertyObj
function onPresentationChooseColor(elm) {
	elm = $(elm);
	var color = elm[0].value;
	var p = elm.parent();
	var target_field = elm.is("input[type=text]") ? elm : p.children("input[type=text]").first();
	
	if (!target_field[0]) //input may not have the type attribute
		target_field = elm.is("input") ? elm : p.children("input").first(); 
	
	target_field.val(color);
}

function openTemplateSamples() {
	if (!template_samples_url) {
		alert("There is any url to open popup with the template samples. Please talk with the system administrator.");
		return;
	}
	
	var template = $(".entity_obj .template > select[name=template]").val(); //this only applies to the edit_entity_simple.php. the edit_template_simple.php already contains the template in the url. Do not add p.parent() here otherwise when this function gets call from the simple_template_layout.php iframe will not work.
	var template_genre = $(".entity_obj .template > select[name=template_genre]").val();
	
	if (template_genre)
		template = "";
	else
		template = template ? template : (typeof layer_default_template != "undefined" ? layer_default_template : "");
	
	if (template) {
		var url = template_samples_url.replace("#template#", template);
		url += (url.indexOf("?") == -1 ? "?" : "&") + "popup=1";
		
		//get popup
		var popup = $("body > .template_region_info_popup");
		
		if (!popup[0]) {
			popup = $('<div class="myfancypopup with_iframe_title template_region_info_popup"></div>');
			$(document.body).append(popup);
		}
		
		popup.html('<iframe></iframe>'); //cleans the iframe so we don't see the previous html
		
		//prepare popup iframe
		var iframe = popup.children("iframe");
		iframe.attr("src", url);
		
		//open popup
		TemplateSamplesFancyPopup.init({
			elementToShow: popup,
			parentElement: document,
		});
		
		TemplateSamplesFancyPopup.showPopup();
	}
	else
		StatusMessageHandler.showMessage("No samples for this template!"); //this only happens if the template_genre is external
}

function prepareRegionsBlocksHtmlValue(parent) {
	parent = parent instanceof jQuery ? parent : $(parent);
	var textareas = parent ? (parent.hasClass("template_region_item") ? parent.find(" > .block_html > textarea") : parent.find(".template_region_item > .block_html > textarea")) : $(".template_region_item > .block_html > textarea");
	
	$.each(textareas, function (idx, textarea) { //must be synchronous in order to the editor creation only happens after this code gets executed.
		textarea = $(textarea);
		var value = textarea.attr("value");
		
		if (value)
			textarea.val( prepareFieldValueIfValueTypeIsString(value) );
	});
}

function createRegionsBlocksHtmlEditor(parent) {
	parent = parent instanceof jQuery ? parent : $(parent);
	var selects = parent ? (parent.hasClass("template_region_item") ? parent.find(".type") : parent.find(".template_region_item .type")) : $(".template_region_item .type");
	
	selects.each(function(idx, select) { 
		select = $(select);
		var type = select.val();
		
		if (type == 1) //is html
			createRegionBlockHtmlEditor(select.parent().children(".block_html"));
	});
}

function createRegionBlockHtmlEditor(block_html, opts) {
	block_html = block_html instanceof jQuery ? block_html : $(block_html);
	var is_wyswyg_editor = block_html.hasClass("editor");
	
	if (is_wyswyg_editor) {
		var parent_body = block_html.closest("body"); //this is very important, bc if the textarea does not exists yet, this is, if was not added to the DOM yet, does not create the editor. Which means the textarea creation must be done in manually in all the addRepeatedRegionBlock methods.
		
		if (parent_body[0] && !hasRegionBlockHtmlEditor(block_html))
			createRegionBlockHtmlWyswygEditor(block_html, opts);
	}
}

function createRegionBlockHtmlWyswygEditor(block_html, opts) {
	block_html = block_html instanceof jQuery ? block_html[0] : block_html;
	var textarea_orig = null;
	
	for (var i = 0; i < block_html.childNodes.length; i++)
		if (block_html.childNodes[i].nodeName.toLowerCase() == "textarea") {
			textarea_orig = block_html.childNodes[i];
			break;
		}
	
	if (textarea_orig) {
		var editor = block_html.editor;
		
		if (!editor) {
			//Note that this method will execute inside of the iframe too, so we must call the right ace based in the current window
			var doc = block_html.ownerDocument || document;
			var win = doc.defaultView || doc.parentWindow;
			
			//remove textarea onBlur attribute, bc we will use the editor onChange event instead.
			textarea_orig.removeAttribute("onBlur");
			
			//clone textarea, otherwise the ace editor will remove it
			var textarea = textarea_orig.cloneNode(true);
			textarea_orig.parentNode.insertBefore(textarea, textarea_orig);
			
			//create ace editor
			win.ace.require("ace/ext/language_tools");
			var editor = win.ace.edit(textarea);
			editor.setTheme("ace/theme/chrome");
			editor.session.setMode("ace/mode/html");
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
			
			var save_func = opts && typeof opts.save_func == "function" ? opts.save_func : regions_blocks_html_editor_save_func;
			
			if (typeof save_func == "function")
				editor.commands.addCommand({
					name: 'saveFile',
					bindKey: {
						win: 'Ctrl-S',
						mac: 'Command-S',
						sender: 'editor|cli'
					},
					exec: function(env, args, request) {
						save_func();
					},
				});
			
			//set on change event
			/* on change method is anoying, so we use instead the onblur
			editor.on("change", function() {
				//Note that this method will execute inside of the iframe too, so we must call the right onBlurRegionBlock
				if (win.region_block_html_editor_on_change_timeout_id)
					clearTimeout(win.region_block_html_editor_on_change_timeout_id);
				
				win.region_block_html_editor_on_change_timeout_id = setTimeout(function() {
					var str = getRegionBlockHtmlEditorValue(block_html);
					
					textarea_orig.value = str;
					win.onChangeRegionBlockEditor(textarea_orig, str);
				}, 2000); //waits 2 secs
			});*/
			editor.on("blur", function() {
				//Note that this method will execute inside of the iframe too, so we must call the right onBlurRegionBlock
				var str = getRegionBlockHtmlEditorValue(block_html);
				
				textarea_orig.value = str;
				win.onChangeRegionBlockEditor(textarea_orig, str);
			});
			
			//fixing problem with scroll up, where when focused or pressed key inside editor the page scrolls to top.
			var t = block_html.querySelector("textarea.ace_text-input");
			t && t.classList.remove("ace_text-input"); 
			
			//set editor instance in block_html
			block_html.editor = editor; //bc this method will be called from the edit_template_simple_layout.js, we shouldn't use the jQuery.data() method.
			
			//hide textarea_orig
			textarea_orig.style.display = 'none';
		}
	}
	
	//all your after init logics here.
	block_html.setAttribute("html_editor_inited", 1);
	
	if (opts && typeof opts.ready_func == "function")
		opts.ready_func();
	
	return editor;
}

function hasRegionBlockHtmlEditor(block_html) {
	block_html = block_html instanceof jQuery ? block_html : $(block_html);
	return block_html.attr("html_editor_inited");
}

function cleanRegionBlockHtmlEditorInstances(region_blocks) {
	//clean editor instances before it empty the html from region_blocks. This is very important, otherwise the editor may overload the browser memory with too many instances...
	var blocks_html = region_blocks.find(".block_html");
	
	for (var i = 0; i < blocks_html.length; i++)
		removeRegionBlockHtmlEditorInstance(blocks_html[i]);
}

function removeRegionBlockHtmlEditorInstance(block_html) {
	block_html = block_html instanceof jQuery ? block_html : $(block_html);
	var editor = block_html[0].editor;
	
	if (editor) { //not sure about this
		var el = editor.container;
		
		editor.destroy();
		
		el = el instanceof jQuery ? el : $(el);
		el.remove();
		
		block_html[0].editor = null;
		block_html.removeAttr("html_editor_inited");
	}
}

function getRegionBlockHtmlEditorValue(block_html) {
	block_html = block_html instanceof jQuery ? block_html : $(block_html);
	
	if (hasRegionBlockHtmlEditor(block_html)) {
		var editor = block_html[0].editor;
		var html = editor ? editor.getValue() : block_html.children("textarea").val();
		
		return html;
	}
	
	return block_html.children("textarea").val();
}

function setRegionBlockHtmlEditorValue(block_html, html) {
	block_html = block_html instanceof jQuery ? block_html : $(block_html);
	
	if (hasRegionBlockHtmlEditor(block_html)) {
		var editor = block_html[0].editor;
		
		if (editor)
			editor.setValue(html, 1);
		else
			block_html.children("textarea").val(html);
	}
	else
		block_html.children("textarea").val(html);
	
	updateLayoutIframeFromSettingsField();
}

function loadAvailableBlocksList(parent, opts) {
	parent = parent instanceof jQuery ? parent : $(parent);
	//alert("loadAvailableBlocksList:"+(opts ? opts["on_load_available_blocks_handler"] : "null"))
	
	if (!available_blocks_list_inited) {
		var timeout_id = null;
		
		if (available_blocks_list_loading) { 
			//Bc this function will get executed asynchronous, we need to be sure that we don't get multiple requests to the server of the same thing. So if a process is already running, don't do anything and waits until the ajax process finishes.
			timeout_id = setTimeout(function() {
				loadAvailableBlocksList(parent, opts);
			}, 500);
		}
		else {
			available_blocks_list_loading = true;
			
			var url = get_available_blocks_list_url;
			url += (url.indexOf("?") != -1 ? "" : "?") + "&time=" + (new Date()).getTime();
			
			//ajax request: get_available_blocks_list_url
			$.ajax({
				type : "get",
				url : url,
				dataType : "json",
				success : function(data, textStatus, jqXHR) {
					//set new data
					available_blocks_list = data ? data : {};
					loadAvailableBlocksListOptionsHtml();
					
					//clear timeout, otherwise it will execute loadAvailableBlocksListInRegionsBlocks twice 
					if (timeout_id)
						clearTimeout(timeout_id);
					
					//set flags
					available_blocks_list_inited = true; //2020-02-10: must be here inside of the ajax request, otherwise when we call this in the edit_simple_template_layout iframe, the available_blocks_list will not have the new values. DO NOT REMOVE THIS FROM HERE!
					available_blocks_list_loading = false;
					
					//execute loadAvailableBlocksListInRegionsBlocks
					//if (data && $.isPlainObject(data)) {
						loadAvailableBlocksListInRegionsBlocks(parent, opts);
						
						if (opts && typeof opts["on_get_available_blocks_handler"] == "function")
							opts["on_get_available_blocks_handler"](data);
					//}
					
					//2020-04-28: DO NOT SHOW ANY ERROR BC IF IT IS A NEW AND ONLY PROJECT, THERE WILL NOT BE ANY BLOCKS CREATED. SO THIS WILL BE EMPTY.
					//else StatusMessageHandler.showMessage("Error trying to get the available blocks list. Please refresh the page to try again.");
				},
				error : function(jqXHR, textStatus, errorThrown) { 
					available_blocks_list_loading = false;
					
					var msg = jqXHR.responseText ? "\n" + jqXHR.responseText : "";
					StatusMessageHandler.showError("Error trying to get the available blocks list. Please refresh the page to try again." + msg);
				},
			});
		}
	}
	else
		loadAvailableBlocksListInRegionsBlocks(parent, opts);
}

function addBlockToAvailableBlocksList(parent, block_id, project_id) {
	if (!$.isPlainObject(available_blocks_list))
		available_blocks_list = {};
	
	if (!$.isArray(available_blocks_list[project_id]))
		available_blocks_list[project_id] = [];
	
	if ($.inArray(block_id, available_blocks_list[project_id]) == -1) {
		available_blocks_list[project_id].push(block_id);
		
		loadAvailableBlocksListOptionsHtml();
		loadAvailableBlocksListInRegionsBlocks(parent);
	}
}

function loadAvailableBlocksListOptionsHtml() {
	var html = '<option value=""></option>';
	
	$.each(available_blocks_list, function(proj, proj_blocks) {
		var proj_blocks_by_folder = getProjectAvailableBlocksListWithFolders(proj_blocks);
		
		var prepare_options_html_func = function(pabs, prefix) {
			var inner_html = "";
			
			for (var k in pabs) 
				if (pabs[k]) { 
					var is_block = $.isPlainObject(pabs[k]) && pabs[k]["is_block"] === true;
					
					if (is_block)
						inner_html += '<option value="' + pabs[k]["block"] + '" project="' + proj + '">' + prefix + k + '</option>';
					else
						inner_html += '<option disabled>' + prefix + "- " + k + '</option>'
								   + prepare_options_html_func(pabs[k], prefix + "&nbsp;&nbsp;&nbsp;&nbsp;");
				}
			
			return inner_html;
		};
		
		html += '<optgroup label="' + proj + '">';
		html += prepare_options_html_func(proj_blocks_by_folder, "");
		
		//$.each(proj_blocks, function(idx, block) {
		//	html += '<option value="' + block + '" project="' + proj + '">' + block + '</option>';
		//});
		
		html += '</optgroup>';
	});
	
	available_blocks_list_options_html = html;
	
	return html;
}

function loadAvailableBlocksListInRegionsBlocks(parent, opts) {
	if (available_blocks_list && $.isPlainObject(available_blocks_list)) {
		if (!available_blocks_list_options_html)
			loadAvailableBlocksListOptionsHtml()
		
		var items = parent ? (parent.hasClass("template_region_item") ? parent.find("select.block_options") : parent.find(".template_region_item select.block_options")) : $(".template_region_item select.block_options");
		
		//loop items synchronously
		$.each(items, function(idx, item) { //must be synchronous otherwise the selectmenus will messy
			item = $(item);
			var block = item.val();
			var option = item.find("option:selected");
			var block_input = item.parent().children("input.block");
			var block_input_value = block_input.val();
			var region_block_type = item.parent().children(".region_block_type");
			
			region_block_type.data("on_change_triggered", false);
			
			item.html(available_blocks_list_options_html);
			
			/*if (block == "" && region_block_type.val() == "string" && block_input_value != "" && selected_project_id &&	available_blocks_list.hasOwnProperty(selected_project_id) && $.isArray(available_blocks_list[selected_project_id]) && $.inArray(block_input_value, available_blocks_list[selected_project_id]) != -1) {
				//cannot execute 'item.val(block_input_value);' bc if 2 projects have blocks with the same name, then it can select the block from the other project. 
				//item.find("optgroup[label=\"" + selected_project_id + "\"] option[value=\"" + block_input_value + "\"]").attr("selected", "selected"); //or the line below. Both work!
				item.find("option[value='" + block_input_value + "']").filter("[project='" + selected_project_id + "']").first().attr("selected", "selected");
				
				region_block_type.val("options");
				region_block_type.trigger("change");
				region_block_type.data("on_change_triggered", true);
			}
			else */if (block != "" && option[0] && region_block_type.val() != "") { //if region_block_type.val() == "", it means is a code type, so we should not do anything in this case!
				var proj = option.attr("project");
				
				if (available_blocks_list.hasOwnProperty(proj) && $.isArray(available_blocks_list[proj]) && $.inArray(block, available_blocks_list[proj]) != -1) {
					//cannot execute 'item.val(block);' bc if 2 projects have blocks with the same name, then it can select the block from the other project. 
					//item.find("optgroup[label=\"" + proj + "\"] option[value=\"" + block + "\"]").attr("selected", "selected"); //or the line below. Both work!
					item.find("option[value='" + block + "']").filter("[project='" + proj + "']").first().attr("selected", "selected");
					
					if (region_block_type.val() == "string") {
						region_block_type.val("options");
						region_block_type.trigger("change");
						region_block_type.data("on_change_triggered", true);
					}
				}
				else if (region_block_type.val() == "options") {
					region_block_type.val("string");
					region_block_type.trigger("change");
					region_block_type.data("on_change_triggered", true);
					block_input.val(block); //puts old block into the input after the onChangeRegionBlockType executed, otherwise this value will be overwrited.
				}
			}
			
			pretifyRegionBlockComboBox(item, opts);
		});
	}
	
	if (opts && typeof opts["on_load_available_blocks_handler"] == "function")
		opts["on_load_available_blocks_handler"](parent);
}

function getProjectAvailableBlocksListWithFolders(proj_blocks) {
	var pabs = {};
	
	if (proj_blocks)
		for (var i = 0; i < proj_blocks.length; i++) {
			var block = proj_blocks[i];
			var dirs = block.split("/");
			var file_name = dirs.pop();
			var obj = pabs;
			
			for (var j = 0; j < dirs.length; j++) {
				var dir = dirs[j];
				
				if (obj.hasOwnProperty(dir) && obj[dir]["is_block"]) //if there is a block file with the same name, adds space to dir. Note that dir will only be used for display the folder and not be really used, so we can mess with it as we want.
					while (obj[dir] && obj[dir]["is_block"])
						dir += " ";
				
				if (!obj.hasOwnProperty(dir))
					obj[dir] = {};
				
				obj = obj[dir];
			}
			
			while (obj.hasOwnProperty(file_name))
				file_name += " ";
			
			obj[file_name] = {
				block: block,
				is_block: true,
			}
		}
	
	return pabs;
}

function pretifyRegionsBlocksComboBox(parent, opts) {
	parent = parent instanceof jQuery ? parent : $(parent);
	var items = parent ? (parent.hasClass("template_region_item") ? parent.find("select.block_options") : parent.find(".template_region_item select.block_options")) : $(".template_region_item select.block_options");
	
	$.each(items, function(idx, item) { //must be synchronous otherwise the selectmenus will be messy
		pretifyRegionBlockComboBox(item, opts);
	});
}

function destroyPretifyRegionBlockComboBox(select_elm) {
	//try{throw new Error("12");}catch(e){console.log("destroyPretifyRegionBlockComboBox");console.log(e);}
	
	select_elm = select_elm instanceof jQuery ? select_elm : $(select_elm);
	var p = select_elm.parent();
	var select_elm_id = select_elm.attr("id");
	
	if(select_elm.selectmenu("instance"))
		select_elm.selectmenu("destroy");
	
	//sometimes the select_elm looses the connection with the selectmenu's button and menu elements
	var pretty_select_button = p.children("#" + select_elm_id + "-button");
	
	if (!pretty_select_button[0])
		pretty_select_button = p.children(".pretty_block_options_button");
	
	if (pretty_select_button[0])
		pretty_select_button.remove();
	
	var pretty_select_menu_ul = p.children("#" + select_elm_id + "-menu");
	if (pretty_select_menu_ul[0])
		pretty_select_menu_ul.parent().remove();
}

function pretifyRegionBlockComboBox(select_elm, opts) {
	select_elm = select_elm instanceof jQuery ? select_elm : $(select_elm);
	var p = select_elm.parent();
	
	destroyPretifyRegionBlockComboBox(select_elm);
	
	var parent_body = p.closest("body"); //this is very important, bc if the select_elm does not exists yet, this is, if was not added to the DOM yet, does not create the selectmenu. Which means the selectmenu creation must be done in manually in all the addRepeatedRegionBlock methods.
	
	if (parent_body[0]) {
		select_elm.selectmenu({
			change: function(event, data) {
				//console.log("pretifyRegionBlockComboBox change");
				//console.log($(data.item.element[0]).closest("select")[0]);
				//console.log(data.item.value);
				
				select_elm.trigger("change"); //already setted the right value to select_elm
				p.children("#" + select_elm.attr("id") + "-button").children(".ui-selectmenu-text").html( select_elm.val() ).attr("title", select_elm.val());
			},
			open: function(event, ui) {
				//console.log("pretifyRegionBlockComboBox open");
				event.stopPropagation(); //In edit_template_simple, when we click in the combobox, it is selecting the parent widget in the LayoutUIEditor. To avoid this, we need to set the stopPropagation
				
				var pretty_select_menu_ul = select_elm.selectmenu("menuWidget");
				
				//check if menu popup is showing correctly
				var pretty_select_menu = pretty_select_menu_ul.parent();
				pretty_select_menu.css("right", "auto"); //reset right so we can get the correct offset
				var o = pretty_select_menu.offset();
				var w = pretty_select_menu.width();
				var ww = $(window).width();
				
				if (o.left + w > ww && ww > w) {
					pretty_select_menu.css({
						left: "auto",
						right: 0
					});
				}
				
				setTimeout(function() { //setTimeout to open faster
					//prepare options
					preparePretifyRegionBlockComboBoxImportModuleBlockOption(select_elm);
					
					//set selected block class
					var selected_option = pretty_select_menu_ul.find("li.ui-menu-item.ui-state-focus");
					pretty_select_menu_ul.find(".ui_menu_item_selected").removeClass("ui_menu_item_selected");
					selected_option.addClass("ui_menu_item_selected");
					
					//scroll blocks to the selected block
					if (pretty_select_menu_ul.scrollTop() == 0 && select_elm.val()) {
						var offset = selected_option.first().position();
						pretty_select_menu_ul.scrollTop(offset.top - 20);
					}
				}, 100);
			},
			create: function(event, ui) {
				//console.log("pretifyRegionBlockComboBox create");
				//console.log(select_elm[0]);
				
				select_elm.addClass("hidden");
				p.children(".invisible").removeClass("invisible");
				
				if (p.children(".region_block_type").val() != "options")
					p.children("#" + select_elm.attr("id") + "-button").addClass("hidden");
				
				//var pretty_select_menu_ul = select_elm.selectmenu("menuWidget");
				//pretty_select_menu_ul.append("<li>Loading...</li>");
			},
		});
		
		var pretty_select_button = p.children("#" + select_elm.attr("id") + "-button");
		var pretty_select_menu_ul = select_elm.selectmenu("menuWidget");
		var pretty_select_menu = pretty_select_menu_ul.parent();
		
		pretty_select_button.addClass("pretty_block_options_button");
		pretty_select_menu.addClass("pretty_block_options_menu");
	
		//try{throw new Error("12");}catch(e){console.log(e);console.log(pretty_select_menu[0]);console.log(opts);}
		
		if (select_elm.val())
			pretty_select_button.children(".ui-selectmenu-text").html( select_elm.val() ).attr("title", select_elm.val());
	}
	
	if (opts && typeof opts["on_pretify_handler"] == "function")
		opts["on_pretify_handler"](select_elm);
}

function preparePretifyRegionBlockComboBoxImportModuleBlockOption(select_elm) {
	var ul = select_elm.selectmenu("menuWidget");
	
	if (create_page_module_block_url && ul[0] && ul.children(".import_module_block").length == 0) {
		var li = $('<li class="ui-selectmenu-optgroup ui-menu-divider import_module_block"><a href="javascript:void(0)">Import new Module <i class="icon add"></i></a></li>');
		
		li.children("a").click(function(event) {
			event.preventDefault();
			openPretifyRegionBlockComboBoxImportModuleBlockOptionPopup(select_elm, create_page_module_block_url);
		});
		
		ul.prepend(li);
	}
}

function openPretifyRegionBlockComboBoxImportModuleBlockOptionPopup(select_elm, url, delete_select_elm_on_cancel) {
	if (!url) {
		alert("There is any url to open popup. Please talk with the system administrator.");
		return;
	}
	
	//get popup
	var popup= $(".choose_module_block_popup");
	
	if (!popup[0]) {
		popup = $('<div class="myfancypopup with_iframe_title choose_module_block_popup"></div>');
		$(document.body).append(popup);
	}
	
	popup.html('<iframe></iframe>'); //cleans the iframe so we don't see the previous html
	
	//prepare popup iframe
	var iframe = popup.children("iframe");
	
	url += (url.indexOf("?") != -1 ? "&" : "?") + "popup=1";
	iframe.attr("src", url);
	
	if (select_elm) 
		iframe.load(function() {
			this.contentWindow.$.ajaxSetup({
				complete: function(jqXHR, textStatus) {
					if (jqXHR.status == 200 && this.url.indexOf("/presentation/save_page_module_block?") > 0) {
						var ajax_response = null;
						
						try {
							if (jqXHR.responseText && ("" + jqXHR.responseText).charAt(0) == "{")
								ajax_response = $.parseJSON(jqXHR.responseText);
						}
						catch(e) {
							if (console && console.log) {
								console.log("jqXHR.responseText: " + jqXHR.responseText);
								console.log(e);
							}
						}
						
						if (ajax_response && ajax_response["status"] == 1) {
							TemplateRegionBlockComboBoxImportModuleBlockOptionFancyPopup.setOption("onClose", null);
							popup.hide(); //force popup to hide faster bc when we call the addRegionBlockOption, it will make the browser slow and the hidePopup bc it calls the jquery fadeOut function, it will be freezed for a second, which gives a bad user experience and is not user-friendly.
							TemplateRegionBlockComboBoxImportModuleBlockOptionFancyPopup.hidePopup();
							
							setTimeout(function() {
								addRegionBlockOption(select_elm, ajax_response["block_id"], selected_project_id);
							}, 10);
						}
					}
			    	}
			});
		});
	
	//open popup
	TemplateRegionBlockComboBoxImportModuleBlockOptionFancyPopup.init({
		elementToShow: popup,
		parentElement: document,
		onClose: function(elm) {
			if (select_elm) {
				var item_elm = select_elm.parent().closest(".template_region_item");
				//var exists_multiple_items = item_elm.parent().closest(".template_region_items").find(".template_region_item").length > 1;
				//console.log(item_elm[0]);
				
				if (delete_select_elm_on_cancel/* && exists_multiple_items*/) //Deprecated, bc in the layout iframe, we can remove items even if there is only one existent item.
					item_elm.remove();
				
				//trigger change function so it can refresh the block simulated html. Basically this will call the prepareRegionBlockSimulatedHtml method inside of the edit_simple_template_layout.php
				if (select_elm.is("select"))
					select_elm.trigger("change");
				else if (select_elm.is("input"))
					select_elm.trigger("blur");
			}
		}
	});
	
	TemplateRegionBlockComboBoxImportModuleBlockOptionFancyPopup.showPopup();
}

function refreshPretifyRegionBlockComboBox(select_elm) {
	var sm_text = select_elm.parent().find(" > #" + select_elm.attr("id") + "-button > .ui-selectmenu-text");
	var value = select_elm.val();
	
	try {
		setTimeout(function() {
			if (select_elm.selectmenu("instance")) //be sure that the selectmenu was already inited.
				select_elm.selectmenu("refresh", true);
			
			sm_text.html(value).attr("title", value);
		}, 300);
	} catch(e) {
		//if (console && console.log) 
		//	console.log(e);
	}

	sm_text.html(value).attr("title", value);
}

//this function is used in this file and in the app/__system/layer/presentation/phpframework/src/view/presentation/view_editor_widget/advanced/others/view.xml
function setRegionBlockOption(select_elm, block, project) {
	var options = select_elm.find("option[value='" + block + "']");
	var p = select_elm.parent();
	var type = p.children(".type");
	var region_block_type = p.children(".region_block_type");
	var is_block = type == 2 || type == 3;
	var exists = false;
	
	if (is_block)
		$.each(options, function(idx, option) {
			option = $(option);
			
			if (option.attr("project") == project) {
				region_block_type.val("options");
				region_block_type.trigger("change");
				
				option.attr("selected", "selected");
				refreshPretifyRegionBlockComboBox(select_elm);
				select_elm.trigger("change");
				exists = true;
				
				return false;
			}
		});
	
	if (!exists) {
		region_block_type.val("string");
		region_block_type.trigger("change");
		
		var input = p.children(".block");
		input.val(block);
		input.trigger("blur");
	}
}

function addRegionBlockOption(select_elm, block, project) {
	if (select_elm[0] && select_elm.is("select") && block) { //select_elm may be an input
		project = project ? project : selected_project_id;
		
		//load new block id
		select_elm.val("");
		var optgroup = select_elm.find("optgroup[label='" + project + "']");
		
		if (!optgroup[0]) {
			optgroup = $('<optgroup label="' + project + '"></optgroup>');
			select_elm.append(optgroup);
		}
		
		var option = $('<option value="' + block + '" project="' + project + '" selected>' + block + '</option>');
		optgroup.append(option);
		
		var p = select_elm.parent();
		select_elm.selectmenu("close");
		refreshPretifyRegionBlockComboBox(select_elm);
		select_elm.trigger("change");
		
		//load all other fields to have this new block
		var doc = select_elm[0].ownerDocument;
		var win = doc.defaultView || doc.parentWindow;
		var main_body = $("body");
		var iframe_body = win == window ? getContentTemplateLayoutIframe(main_body)[0].contentWindow.document.body : doc.body;
		iframe_body = $(iframe_body);
		
		var main_body_items = main_body.find(".regions_blocks_includes_settings .template_region_items");
		var iframe_body_items = iframe_body.find(".template_region .template_region_items");
		
		main_body_items.find(".region_block_type, .icon").addClass("invisible");
		iframe_body_items.find(".region_block_type, .icon").addClass("invisible");
		
		//disable update funcs
		available_blocks_list_inited = false;
		loadAvailableBlocksList(main_body_items, {
			"on_get_available_blocks_handler": function(data) {
				loadAvailableBlocksListInRegionsBlocks(iframe_body_items);
			},
		});
	}
}

function updateSelectedTemplateRegionsBlocks(p, data) {
	//BACKING UP GLOBAL VARS - bc the getRegionBlockHtml triggers the onChange events which changes then these global vars, if the synchronization with the template layout is active...
	var regions_blocks_list_clone = JSON.parse(JSON.stringify(regions_blocks_list)); //cannot use Object.assign bc regions_blocks_list is an array
	var template_params_values_list_clone = assignObjectRecursively({}, template_params_values_list);
	
	//PREPARING TEMPLATE REGIONS
	var regions = data && data.regions ? data.regions : [];
	
	var region_blocks = p.find(".region_blocks .template_region_items");
	var other_region_blocks = p.find(".other_region_blocks .template_region_items");
	var defined_regions = p.find(".defined_regions .template_region_items");
	var region_blocks_no_items = p.find(".region_blocks .no_items");
	var other_region_blocks_no_items = p.find(".other_region_blocks .no_items");
	var defined_regions_no_items = p.find(".defined_regions .no_items");
	
	cleanRegionBlockHtmlEditorInstances(region_blocks); //clean editor instances before it empty the html from region_blocks. This is very important, otherwise the editor is overloading the browser memory with too many instances...
	
	region_blocks.html("");
	other_region_blocks.html("");
	defined_regions.html("");
	
	region_blocks_no_items.show();
	other_region_blocks_no_items.show();
	defined_regions_no_items.show();
	
	var existent_regions_blocks = {};
	var rb_indexes = {};
	
	if (regions && regions.length > 0) {
		region_blocks_no_items.hide();
		
		//prepare rb_indexes
		for (var i = 0; i < regions_blocks_list_clone.length; i++) {
			var rbl = regions_blocks_list_clone[i];
			var region = rbl[0];
			var block = rbl[1];
			var proj = rbl[2];
			var type = rbl[3];
			var rb_index = rbl[4];
			var block_hash = type == 1 ? $.md5(block) : (type == 2 || type == 3 ? "block_" : "view_") + block; //if html set md5
			var rm_index_key = region + "-" + block_hash + "-" + proj;
			
			if ($.isNumeric(rb_index) && (!$.isNumeric(rb_indexes[rm_index_key]) || rb_index > rb_indexes[rm_index_key]))
				rb_indexes[rm_index_key] = rb_index;
		}
		
		//prepare regions
		for (var i = 0; i < regions.length; i++) {
			var region = regions[i];
			
			for (var j = 0; j < regions_blocks_list_clone.length; j++) {
				var rbl = regions_blocks_list_clone[j];
				
				if (rbl[0] == region) {
					var block = rbl[1];
					var proj = rbl[2];
					var type = rbl[3];
					var rb_index = rbl[4];
					var block_hash = type == 1 ? $.md5(block) : (type == 2 || type == 3 ? "block_" : "view_") + block; //if html set md5
					var rm_index_key = region + "-" + block_hash + "-" + proj;
					
					if (!existent_regions_blocks.hasOwnProperty(region))
						existent_regions_blocks[region] = {};
						
					if (!existent_regions_blocks[region].hasOwnProperty(block_hash)) 
						existent_regions_blocks[region][block_hash] = {};
						
					existent_regions_blocks[region][block_hash][proj] = true;
					
					if (!$.isNumeric(rb_index)) {
						if ($.isNumeric(rb_indexes[rm_index_key]))
							rb_indexes[rm_index_key]++;
						else
							rb_indexes[rm_index_key] = 0;
						
						rb_index = rb_indexes[rm_index_key];
					}
					
					var rb_html = getRegionBlockHtml(region, block, proj, type, rb_index);
					region_blocks.append(rb_html);
				}
			}
			
			if (!existent_regions_blocks.hasOwnProperty(region)) {
				var rb_html = getRegionBlockHtml(region, null, null, false, 0);
				region_blocks.append(rb_html);
			}
		}
	}
	
	//prepare other regions
	var other_regions_exists = false;
	
	for (var i = 0; i < regions_blocks_list_clone.length; i++) {
		var rbl = regions_blocks_list_clone[i];
		
		var region = rbl[0];
		var block = rbl[1];
		var proj = rbl[2];
		var type = rbl[3];
		var rb_index = rbl[4];
		var block_hash = type == 1 ? $.md5(block) : (type == 2 || type == 3 ? "block_" : "view_") + block; //if html set md5
		
		if (!existent_regions_blocks[region] || !existent_regions_blocks[region][block_hash] || !existent_regions_blocks[region][block_hash][proj]) {
			var rm_index_key = region + "-" + block_hash + "-" + proj;
			
			if (!$.isNumeric(rb_index)) {
				if ($.isNumeric(rb_indexes[rm_index_key]))
					rb_indexes[rm_index_key]++;
				else
					rb_indexes[rm_index_key] = 0;
				
				rb_index = rb_indexes[rm_index_key];
			}
			
			var rb_html = getRegionBlockHtml(region, block, proj, type, rb_index);
			other_region_blocks.append(rb_html);
			
			other_regions_exists = true;
		}
	}
	
	if (other_regions_exists) 
		other_region_blocks_no_items.hide();
	
	pretifyRegionsBlocksComboBox(p);
	createRegionsBlocksHtmlEditor(p);
	
	//prepare defined regions
	if ($.isArray(defined_regions_list)) {
		var defined_regions_exists = false;
		
		for (var i = 0; i < defined_regions_list.length; i++) {
			var region = defined_regions_list[i];
			var rb_html = getDefinedRegionHtml(region);
			
			defined_regions.append(rb_html);
			defined_regions_exists = true;
		}
		
		if (defined_regions_exists) 
			defined_regions_no_items.hide();
	}
	
	//PREPARING TEMPLATE PARAMS
	var params = data && data.params ? data.params : [];
	
	var template_params = p.find(".template_params .items");
	var other_template_params = p.find(".other_template_params .items");
	var defined_template_params = p.find(".defined_template_params .items");
	var template_params_no_items = p.find(".template_params .no_items");
	var other_template_params_no_items = p.find(".other_template_params .no_items");
	var defined_template_params_no_items = p.find(".defined_template_params .no_items");
	
	template_params.html("");
	other_template_params.html("");
	defined_template_params.html("");
	
	template_params_no_items.show();
	other_template_params_no_items.show();
	defined_template_params_no_items.show();
	
	var existent_params = {};
	
	if (params && params.length > 0) {
		template_params_no_items.hide();
		
		for (var i = 0; i < params.length; i++) {
			var param = params[i];
			var param_value = null;
			var default_value = null;
			
			if (param && !existent_params.hasOwnProperty(param)) {
				if (template_params_values_list_clone.hasOwnProperty(param))
					param_value = template_params_values_list_clone[param];
				
				if (defined_template_params_values && defined_template_params_values.hasOwnProperty(param))
					default_value = defined_template_params_values[param];
				
				var param_html = getTemplateParamHtml(param, param_value, default_value);
				template_params.append(param_html);
				existent_params[param] = true;
			}
		}
		
		if (defined_template_params_values) {
			for (var param in defined_template_params_values) {
				if (param && !existent_params.hasOwnProperty(param)) {
					var default_value= defined_template_params_values[param];
					var param_value = null;
					
					if (template_params_values_list_clone.hasOwnProperty(param))
						param_value = template_params_values_list_clone[param];
					
					var param_html = getTemplateParamHtml(param, param_value, default_value);
					template_params.append(param_html);
					existent_params[param] = true;
				}
			}
		}
	}
	
	//prepare other params
	var exists = false;
	for (var param in template_params_values_list_clone) {
		if (param && !existent_params.hasOwnProperty(param)) {
			var param_value = template_params_values_list_clone[param];
			var param_html = getTemplateParamHtml(param, param_value === null || param_value === undefined ? "" : param_value);
	
			other_template_params.append(param_html);
			exists = true;
		}
	}
	
	if (exists) 
		other_template_params_no_items.hide();
	
	//prepare defined params
	if (defined_template_params_values) {
		var exists = false;
		for (var param in defined_template_params_values) {
			if (param) {
				var param_html = getDefinedTemplateParamHtml(param);
				defined_template_params.append(param_html);
				exists = true;
			}
		}
		
		if (exists) 
			defined_template_params_no_items.hide();
	}
	
	//resizeAllLabels();
	
	//setting again the global vars with the correct values, bc if the synchronization with the template layout is active, it will change these global vars
	regions_blocks_list = regions_blocks_list_clone;
	template_params_values_list = template_params_values_list_clone;
}

function getDefinedRegionHtml(region) {
	var label = region.replace(/"/g, "");
	
	return $( defined_region_html.replace(/#region#/g, label) ); //replace #block# with empty strnig bc if block is an html string, it will break the javascript. So, the block value will be assigned in the code bellow.
}

function getRegionBlockHtml(region, block, block_project, type, rb_index) {
	var is_html = type == 1;
	var is_block = type == 2 || type == 3;
	var is_view = type == 4 || type == 5;
	
	block = block ? block : "";
	block_project = block_project ? block_project : "";
	rb_index = $.isNumeric(rb_index) ? rb_index : "";
	
	var reg = region.replace(/"/g, "&quot;");
	var rb_html = $( region_block_html.replace(/#region#/g, reg).replace(/#block#/g, "").replace(/#rb_index#/g, rb_index) ); //replace #block# with empty strnig bc if block is an html string, it will break the javascript. So, the block value will be assigned in the code bellow.
	
	var select = rb_html.children(".region_block_type");
	var select_type = rb_html.children(".type");
	select_type.val(is_html ? 1 : (is_view ? 4 : 2));
	
	//prepare new region_block_html item
	if (is_block || is_view) {
		var b = block.substr(0, 1) == '"' ? block.replace(/"/g, "") : block;
		var bt = block.substr(0, 1) == '"' && block.substr(block.length - 1) == '"' ? block.substr(1, block.length - 2) : block;
		var bp = block_project.substr(0, 1) == '"' ? block_project.replace(/"/g, "") : block_project;
		var is_text = b.indexOf("\n") != -1;
		var exists_in_blocks = false;
		
		if (is_block) {
			var apbl = available_blocks_list ? available_blocks_list[bp] : null;
			var is_bp_html_or_text = is_text || /<\/?[a-z][\s\S]*>/i.test(b);
			exists_in_blocks = (apbl && !is_bp_html_or_text && $.inArray(b, apbl) != -1) ? true : false;
		}
		
		var block_options = rb_html.children(".block_options");
		var block_input = rb_html.children("input.block");
		var block_text = rb_html.children(".block_text");
		
		if (!is_text) {
			block_input.val(b);
			
			if (is_block) {
				//cannot execute 'block_options.val(b);' bc if 2 projects have blocks with the same name, then it can select the block from the other project. 
				//block_options.find("optgroup[label=\"" + bp + "\"] option[value=\"" + b + "\"]").attr("selected", "selected"); //or the line below. Both work!
				block_options.find("option[value='" + b + "']").filter("[project='" + bp + "']").first().attr("selected", "selected");
			}
		}
		else
			block_text.children("textarea").text( stripslashes(bt) );
		
		if (b == "" || exists_in_blocks) 
			select.val("options");
		else {
			var block_type = getArgumentType(block); //if no block, by default sets to string
			select.val(block_type);
			
			rb_html.addClass(is_text ? "is_text" : "is_input");
		}
	}
	else {
		var block_html = rb_html.children(".block_html");
		var textarea = block_html.children("textarea");
		textarea.val(block);
		
		rb_html.addClass("is_html");
	}
	
	//prepare region label
	var label = rb_html.children("label").first();
	label.html( label.html().replace(/"/g, "") );
	label.attr("title", label.attr("title").replace(/"/g, ""));
	
	//loadAvailableBlocksListInRegionsBlocks and trigger select.onChange
	select.data("on_change_triggered", false); //be sure that the select contains on_change_triggered false
	
	var ret = loadAvailableBlocksListInRegionsBlocks(rb_html, {
		"on_pretify_handler" : function(block_options2) {
			if (select.val() == "options") {
				//cannot execute 'block_options2.val(b);' bc if 2 projects have blocks with the same name, then it can select the block from the other project.
				//block_options2.find("optgroup[label=\"" + bp + "\"] option[value=\"" + b + "\"]").attr("selected", "selected"); //or the line below. Both work!
				block_options2.find("option[value='" + b + "']").filter("[project='" + bp + "']").first().attr("selected", "selected"); 
				select.trigger("change");
				select.data("on_change_triggered", true);
			}
		}
	});
	
	//if change event was not triggered yet, trigger it.
	if (!select.data("on_change_triggered")) {
		select_type.trigger("change"); //this on change event alreay triggers the select.trigger("change");
		//select.trigger("change");
	}
	
	return rb_html;
}

function addOtherRegionBlock(elm) {
	var region = prompt("Please write the Region Id that you wish to add?");
	
	if (region == null) 
		return true;
	
	if (region && region.trim()) {
		region = region.trim();
		region = region.substr(0, 1) != '$' ? '"' + region + '"' : region;
		
		var rb_html = getRegionBlockHtml(region, null, null, false);
		
		var regions_blocks_includes_settings = $(elm).parent().closest(".regions_blocks_includes_settings");
		var orb = regions_blocks_includes_settings.find(".region_blocks");
		var orb_items = orb.find(".template_region_items");
		var regions = orb_items.find(".template_region_item input.region");
		var exists_in_template = false;
		$.each(regions, function(idx, input) {
			if ($(input).val() == region) {
				exists_in_template = true;
				return false;
			}
		});
		
		if (exists_in_template) {
			orb_items.append(rb_html);
			orb.find(".no_items").hide();	
		}
		else {
			orb = regions_blocks_includes_settings.find(".other_region_blocks");
			orb.find(".template_region_items").append(rb_html);
			orb.find(".no_items").hide();	
		}
		
		pretifyRegionBlockComboBox( rb_html.children(".block_options") );
		//resizeElementLabels(orb);
	}
	else 
		alert("Error: Region Id cannot be undefined!");
}

//This function will be used in the edit_simple_template_layout file
function addFirstRegionBlock(elm) {
	var template_region = $(elm).parent().closest(".template_region");
	var region = template_region.attr("region");
	
	if (region && region.trim()) {
		var rb_html = getRegionBlockHtml(region, null, null, false);
		var items = template_region.children(".template_region_items");
		
		items.append(rb_html);
		
		pretifyRegionBlockComboBox( rb_html.children(".block_options") );
		//resizeElementLabels(region_blocks);
	}
	else 
		alert("Error trying to add new region!");
}

function addRepeatedRegionBlock(elm) {
	elm = $(elm);
	var item = elm.parent();
	
	var region = item.children("input.region").val();
	
	if (region == null)
		return true;
	
	if (region && region.trim()) {
		var rb_html = getRegionBlockHtml(region, null, null, false);
		
		item.after(rb_html);
		var region_blocks = item.parent().parent();
		region_blocks.find(".no_items").hide();	
		
		pretifyRegionBlockComboBox( rb_html.children(".block_options") );
		//resizeElementLabels(region_blocks);
	}
	else 
		alert("Error trying to add new region!");
}

function editRegionBlock(elm, opts) {
	var item = $(elm).parent();
	var type = item.children(".type").val();
	var block_type = item.children(".region_block_type").val();
	var is_html = type == 1;
	var is_block = type == 2 || type == 3;
	var is_view = type == 4 || type == 5;
	
	if (is_html) {
		var block_html = item.children(".block_html");
		var html = getRegionBlockHtmlEditorValue(block_html);
		
		var popup = $(".template_region_block_html_editor_popup");
		var layout_ui_editor_elm = popup.children(".layout-ui-editor");
		var PtlLayoutUIEditor = null;
		
		if (!layout_ui_editor_elm[0].hasAttribute("inited")) {
			layout_ui_editor_elm.attr("inited", 1);
			
			PtlLayoutUIEditor = new LayoutUIEditor();
			PtlLayoutUIEditor.options.ui_element = layout_ui_editor_elm;
			
			if (typeof onContextMenuLayoutUIEditorWidgetSetting == "function")
				PtlLayoutUIEditor.options.on_context_menu_widget_setting = onContextMenuLayoutUIEditorWidgetSetting;
			
			if (typeof setCodeEditorAutoCompleter == "function")
				PtlLayoutUIEditor.options.template_source_editor_ready_func = setCodeEditorAutoCompleter;
			
			if (typeof onPresentationIncludePageUrlTaskChooseFile == "function")
				PtlLayoutUIEditor.options.on_choose_page_url_func = function(elm) {
					onPresentationIncludePageUrlTaskChooseFile(elm);
					IncludePageUrlFancyPopup.settings.is_code_html_base = typeof is_code_html_base == "undefined" ? true : is_code_html_base;
				}
			
			if (typeof onPresentationIncludeImageUrlTaskChooseFile == "function")
				PtlLayoutUIEditor.options.on_choose_image_url_func = function(elm) {
					onPresentationIncludeImageUrlTaskChooseFile(elm);
					MyFancyPopup.settings.is_code_html_base = typeof is_code_html_base == "undefined" ? true : is_code_html_base;
				}
			
			if (typeof onPresentationIncludeWebrootFileUrlTaskChooseFile == "function")
				PtlLayoutUIEditor.options.on_choose_webroot_file_url_func = function(elm) {
					onPresentationIncludeWebrootFileUrlTaskChooseFile(elm);
					MyFancyPopup.settings.is_code_html_base = typeof is_code_html_base == "undefined" ? true : is_code_html_base;
				}
			
			//set new func for on_convert_project_url_php_vars_to_real_values_func that replaces inline vars too. This is only for the edit_entity_simple. The edit_template_simple should have this flag disabled and only replace the project_url_prefix inside of the php tags.
			PtlLayoutUIEditor.options.on_convert_project_url_php_vars_to_real_values_func = function(str, widget) {
				var replace_inline_vars = typeof replace_inline_project_url_php_vars == "undefined" ? false : replace_inline_project_url_php_vars;
				replace_inline_vars = widget && $(widget).parent().closest(".template_region").length > 0 ? true : replace_inline_vars;
				
				return convertProjectUrlPHPVarsToRealValues(str, replace_inline_vars);
			};
			//set new func for on_convert_project_url_real_values_to_php_vars_func that replaces the project_url_prefix with the original_project_url_prefix var. This is only for the edit_template_simple. The edit_entity_simple should have the project_url_prefix var instead.
			PtlLayoutUIEditor.options.on_convert_project_url_real_values_to_php_vars_func = function(str, widget) {
				var html_base = typeof is_code_html_base == "undefined" ? true : is_code_html_base;
				html_base = widget && $(widget).parent().closest(".template_region").length > 0 ? false : html_base;
				
				return convertProjectUrlRealValuesToPHPVars(str, typeof give_priority_to_original_project_url_prefix == "undefined" ? false : give_priority_to_original_project_url_prefix, html_base);
			};
			
			initLayoutUIEditorWidgetResourceOptions(PtlLayoutUIEditor);
			
			PtlLayoutUIEditor.options.on_ready_func = function() {
				setCodeLayoutUIEditorCode(popup, html);
				PtlLayoutUIEditor.forceTemplateLayoutConversionAutomatically();
			};
			
			window["TemplateRegionBlockHtmlPTLLayoutUIEditor"] = PtlLayoutUIEditor;
			PtlLayoutUIEditor.init("TemplateRegionBlockHtmlPTLLayoutUIEditor");
			
			//bc the layout editor will show on a draggable popup, we must disable the draggable events in the template-source, otherwise we cannot select the text
			PtlLayoutUIEditor.getTemplateSource().on("mousedown", function(e) {
				e.preventDefault ? e.preventDefault() : e.returnValue = false;
				e.stopPropagation();
				
				return false;
			});
		}
		else {
			setCodeLayoutUIEditorCode(popup, html);
			
			PtlLayoutUIEditor = layout_ui_editor_elm.data("LayoutUIEditor");
			
			if (PtlLayoutUIEditor) 
				PtlLayoutUIEditor.forceTemplateLayoutConversionAutomatically();
		}
		
		//open popup
		TemplateRegionBlockHtmlEditorFancyPopup.init({
			elementToShow: popup,
			parentElement: document,
			onOpen: function() {
				layout_ui_editor_elm.css("z-index", popup.css("z-index")); //set z-index so the draggable elements can be over the popup, otherwise when we drag an element we will not see it.
			},
			onClose: function() {
				var html = getCodeLayoutUIEditorCode(popup);
				
				//2020-10-08: TODO fix the issue with html content with multiple backslashes, this is, save an entity file with the html: '<h1>\\\\\\ \\p</h1>' and then try to call the template_region_block_html_editor_popup popup. When it gets openned it has the double of the backslashes. I think the issue is not from the LayoutUIEditor, but from the tinymce.
				if (opts && typeof opts.on_popup_close_func == "function") //bc of the iframe that must pass the correspondent function to set the html.
					opts.on_popup_close_func(html);
				else
					setRegionBlockHtmlEditorValue(block_html, html);
			},
		});
		
		TemplateRegionBlockHtmlEditorFancyPopup.showPopup();
	}
	else if (block_type == "options" || block_type == "string") {
		var block_options = item.find(".block_options");
		var block = "";
		var project = "";
		
		if (is_block && block_type == "options") {
			var opt = block_options.find("option:selected");
			block = opt.attr("value");
			project = opt.attr("project");
			project = project ? project : selected_project_id;
		}
		else if (is_view) {
			block_options = item.find(".block");
			block = block_options.val();
			project = selected_project_id;
		}
		
		if (block) {
			var url = null;
			
			if (is_block && typeof edit_block_url != "undefined")
				url = edit_block_url.replace("#path#", project + "/src/block/" + block + ".php");
			else if (is_view && typeof edit_view_url != "undefined")
				url = edit_view_url.replace("#path#", project + "/src/view/" + block + ".php");
			
			if (url)
				openPretifyRegionBlockComboBoxImportModuleBlockOptionPopup(block_options, url);
			else
				StatusMessageHandler.showError("Edit not possible!");
		}
		else
			StatusMessageHandler.showError("Block is empty!");
	}
	else
		StatusMessageHandler.showError("Edit not possible!");
}

function onChangeRegionBlockEditor(elm, html) {
	var main_p = $(elm).parent().closest(".template_region, .regions_blocks_includes_settings");
	
	//if we are in settings panel and not in the template layout.
	if (main_p.hasClass("regions_blocks_includes_settings")) {
		//set handler to update directly the the html in the template layout without refreshing the entire layout.
		updateLayoutIframeFieldHtmlValueFromSettingsField(elm, html);
	}
	
	onBlurRegionBlock(elm);
}

function onBlurRegionBlock(elm) {
	onChangeRegionBlock(elm);
}

function moveUpRegionBlock(elm) {
	moveRegionBlock(elm, "up");
}

function moveDownRegionBlock(elm) {
	moveRegionBlock(elm, "down");
}

function moveRegionBlock(elm, direction) {
	var item = $(elm).parent();
	
	if (direction == "up") {
		var prev_item = item.prev();
		
		if (prev_item[0]) 
			item.parent()[0].insertBefore(item[0], prev_item[0]);
	}
	else {
		var next_item = item.next();
		
		if (next_item[0]) 
			item.parent()[0].insertBefore(next_item[0], item[0]);
	}
	
	updateLayoutIframeFromSettingsField();
}

function deleteRegionBlock(elm) {
	var item = $(elm).parent();
	var items = item.parent();

	if (items.parent().hasClass("other_region_blocks"))  {
		item.remove();
	
		updateLayoutIframeFromSettingsField();
	}
	else {
		var region = item.children("input.region").val();
		var other_similar_rb = items.find(".template_region_item input.region[value='" + region + "']");

		if (other_similar_rb.length > 1) {
			item.remove();
			
			//when delete multiple region blocks at the same time (but with 700 milliseconds os spacing. Do not do it very fast!), in the settings panel, the template layout is not getting refresh, or is only getting refreshed for the 1st deleted block. In this case the user should click in the button to update the layout ui.
			updateLayoutIframeFromSettingsField();
		}
		else {
			resetRegionBlock(item); //resetRegionBlock already triggers the onChange and onBlur events for the selected field which calls the updateLayoutIframeFromSettingsField method.
			
			StatusMessageHandler.showError("Cannot remove this region-block because is the only one of his kind. You must leave at least 1 block-region for each region.", "", "bottom_messages", 1500);
		}
	}
}

function resetRegionBlock(item) {
	var item_type = item.children(".type");
	var region_block_type = item.children(".region_block_type");
	var block_options = item.children(".block_options");
	var block_input = item.children("input.block");
	var block_text = item.children(".block_text");
	var block_html = item.children(".block_html");
	var block_params = item.children(".block_params");
	var block_simulated_html = item.children(".block_simulated_html");
	
	var type = item_type.val();
	var block_type = region_block_type.val();
	var is_html = type == 1;
	
	//reset params and simulated html
	block_params.html("");
	block_simulated_html.remove();
	
	//reset selected field
	if (is_html) {
		setRegionBlockHtmlEditorValue(block_html, "");
	}
	else if (block_type == "options") {
		var pretty_select = item.children(".pretty_block_options_button");
		
		block_options.val("");
		
		if (pretty_select[0])
			refreshPretifyRegionBlockComboBox(block_options);
		
		block_options.trigger("change");
	}
	else if (block_type == "text") {
		var text_textarea = block_text.children("textarea");
		
		text_textarea.val("");
		text_textarea.trigger("blur");
	}
	else {
		block_input.val("");
		block_input.trigger("blur");
	}
}

function onChangeTemplateRegionItemType(elm) {
	elm = $(elm);
	var type = elm.val();
	var is_html = type == 1;
	var is_block = type == 2 || type == 3;
	var is_view = type == 4 || type == 5;
	
	var p = elm.parent();
	var block_options_select = p.children(".block_options");
	var pretty_select = p.children(".pretty_block_options_button");
	var input = p.children("input.block");
	var text = p.children(".block_text");
	var text_textarea = text.children("textarea");
	var block_html = p.children(".block_html");
	var block_params = p.children(".block_params");
	
	if (is_html) {
		block_options_select.hide().addClass("hidden");
		pretty_select.hide().addClass("hidden");
		input.hide().addClass("hidden");
		text.hide().addClass("hidden");
		block_html.show().removeClass("hidden");
		block_params.hide().addClass("hidden");
		
		p.removeClass("is_input").removeClass("is_select").removeClass("is_text").addClass("is_html has_edit");
		
		if (!hasRegionBlockHtmlEditor(block_html))
			createRegionBlockHtmlEditor(block_html, {ready_func : function() {
				updateLayoutIframeFromSettingsField();
			}});
		else
			updateLayoutIframeFromSettingsField();
	}
	else {
		block_html.hide().addClass("hidden");
		
		if (is_block) 
			block_params.show().removeClass("hidden");
		else
			block_params.hide().addClass("hidden");
		
		onChangeRegionBlockType( p.children(".region_block_type")[0] );
	}
}

function onChangeRegionBlockType(elm) {
	elm = $(elm);
	var type = elm.val();
	var p = elm.parent();
	
	var select = p.children(".block_options");
	var pretty_select = p.children(".pretty_block_options_button");
	var input = p.children("input.block");
	var text = p.children(".block_text");
	var text_textarea = text.children("textarea");
	var item_type_select = p.children(".type");
	var is_block = item_type_select.val() == 2;
	
	p.removeClass("has_edit"); //hide edit icon. it will be shown by the onChangeRegionBlock function, if apply...
	
	//only allow options if is block
	if (type == "options" && !is_block) {
		type = "string";
		elm.val(type);
	}
	
	if (type == "options") {
		var set_value = p.hasClass("is_text") || p.hasClass("is_input"); //if was already options no need to change value
		var value = p.hasClass("is_text") ? text_textarea.val() : input.val();
		
		if (set_value) {
			//cannot execute 'select.val(value);' bc if 2 projects have blocks with the same name, then it can select the block from the other project. 
			//var option_to_select = selected_project_id ? select.find("optgroup[label=\"" + selected_project_id + "\"] option[value=\"" + value + "\"]") : null; //or the line below. Both work!
			var option_to_select = selected_project_id ? select.find("option[value='" + value + "']").filter("[project='" + selected_project_id + "']").first() : null;
			
			if (option_to_select.length > 0)
				option_to_select.attr("selected", "selected");
			else
				select.val(value);
		}
		
		if (pretty_select[0]) {
			select.hide().addClass("hidden");
			pretty_select.show().removeClass("hidden");
			
			if (set_value) {
				//console.log("called refreshPretifyRegionBlockComboBox");
				refreshPretifyRegionBlockComboBox(select);
			}
		}
		else
			select.show().removeClass("hidden");
		
		input.hide().addClass("hidden");
		text.hide().addClass("hidden");
		
		p.removeClass("is_input").removeClass("is_text").removeClass("is_html").addClass("is_select");
		select.trigger("change");
	}
	else if (type == "text") {
		if (!p.hasClass("is_text") && !p.hasClass("is_html")) //very important bc the getRegionBlockHtml triggers this function with all the values already setted. if we don't do this check it will puty the fild in blank
			text_textarea.val( p.hasClass("is_input") ? input.val() : select.val() );
		
		select.hide().addClass("hidden");
		pretty_select.hide().addClass("hidden");
		input.hide().addClass("hidden");
		text.show().removeClass("hidden");
		
		p.removeClass("is_input").removeClass("is_select").removeClass("is_html").addClass("is_text");
		text_textarea.trigger("blur");
	}
	else {
		if (!p.hasClass("is_input") && !p.hasClass("is_html")) //very important bc the getRegionBlockHtml triggers this function with all the values already set. if we don't do this check, it will put the field in blank
			input.val( p.hasClass("is_text") ? text_textarea.val() : select.val() );
		
		select.hide().addClass("hidden");
		pretty_select.hide().addClass("hidden");
		input.show().removeClass("hidden");
		text.hide().addClass("hidden");
		
		p.removeClass("is_text").removeClass("is_select").removeClass("is_html").addClass("is_input");
		input.trigger("blur");
	}
}

function onChangeRegionBlock(elm) {
	elm = $(elm);
	var block = elm.val();
	var parent = elm.parent();
	var region = parent.children("input.region").val();
	var block_type = parent.children("select.region_block_type").val();
	
	if (block != "" && (block_type == "options" || block_type == "string"))
		parent.addClass("has_edit");
	else
		parent.removeClass("has_edit");
	
	//Update Block Params
	onLoadRegionBlockParams(parent);
	
	//Update Block Join Points
	onLoadRegionBlockJoinPoints(parent, region, (block ? '"' + block + '"' : null));
	
	//layout settings
	updateLayoutIframeFromSettingsField();
}

function addInclude(elm) {
	var html = include_html.replace(/#path#/g, "");
	var include = $(html);
	
	include.children("select").val("string");
	
	var inc = $(".regions_blocks_includes_settings .includes");
	inc.find(".items").append(include);
	inc.find(".no_items").hide();
}

function onBlurInclude(Elm) {
	updateLayoutIframeFromSettingsField();
}

function onChangeIncludeType(elm) {
	updateLayoutIframeFromSettingsField();
}

function onChangeIncludeOnce(elm) {
	updateLayoutIframeFromSettingsField();
}

function removeInclude(elm) {
	$(elm).parent().remove();
	
	updateLayoutIframeFromSettingsField();
}

function onLoadRegionBlockParams(region_block_item) {
	var region = region_block_item.children("input.region").val();
	
	var block_options = region_block_item.children("select.block_options");
	var block = block_options.val();
	var project = block_options.find("option:selected").attr("project");
	project = project ? project : selected_project_id;
	
	var block_params = region_block_item.children(".block_params");
	block_params.html("");
	
	//prepare region block index;
	var rb_index = region_block_item.attr("rb_index");
	
	if (block) {
		var url = get_block_params_url.replace(/#project#/g, project).replace(/#block#/g, block);
		var md5 = $.md5(url);
		
		var handler = function(data) {
			if(data) {
				var bpv = getRegionBlockParamValues(region, '"' + block + '"', rb_index);
				bpv = bpv ? bpv : {};
				
				for (var i = 0; i < data.length; i++) {
					var param = data[i];
					param = param ? param: "";
					
					var exists = false;
					var existent_block_params = block_params.children(".block_param");
					for (var j = 0; j < existent_block_params.length; j++) {
						var ebp = existent_block_params[j];
						
						if (ebp.getAttribute("param") == param) {
							exists = true;
							break;
						}
					}
					
					if (!exists) {
						var value = bpv[param];
						value = value ? value: "";
						var value_type = value.length > 0 ? getArgumentType(value) : "string";;
						var p = ("" + param).replace(/"/g, "&quot;");
						var v = ("" + value).charAt(0) == '"' ? ("" + value).substr(1, ("" + value).length - 2).replace(/\\"/g, '"') : value;
						var v = ("" + v).replace(/"/g, "&quot;");
						
						//prepare param html
						var param_html = $( block_param_html.replace(/#param#/g, p).replace(/#value#/g, v) );
						var label = param_html.children("label").first();
						label.html( label.text().replace(/"/g, "") );
						
						param_html.children("select").val(value_type);
						
						if (value_type == "text") {
							param_html.children("input.block_param_value").hide();
							param_html.children(".block_param_text").show();
							
							var vt = value.substr(0, 1) == '"' && value.substr(value.length - 1) == '"' ? value.substr(1, value.length - 2) : value;
							param_html.children(".block_param_text").children("textarea").val(stripslashes(vt));
						}
						
						block_params.append(param_html);
					}
				}
				
				//resizeRegionBlockParamsLabels(block_params);
			}
		};
		
		if (project_blocks_params.hasOwnProperty(md5))
			handler( project_blocks_params[md5] );
		else if (project_blocks_params_loading[md5]) { //Bc this function will get executed asynchronous, we need to be sure that we don't get multiple requests to the server of the same thing.
			var func = function() {
				if (!project_blocks_params_loading[md5])
					handler( project_blocks_params[md5] );
				else 
					setTimeout(function() {
						func();
					}, 500);
			};
			
			func();
		}
		else {
			project_blocks_params_loading[md5] = true;
			
			url += (url.indexOf("?") != -1 ? "" : "?") + "&time=" + (new Date()).getTime();
			
			$.ajax({
				type : "get",
				url : url,
				dataType : "json",
				success : function(data, textStatus, jqXHR) {
					project_blocks_params[md5] = data;
					
					project_blocks_params_loading[md5] = null;
					delete project_blocks_params_loading[md5];
					
					handler(data);
				},
				error : function(jqXHR, textStatus, errorThrown) { 
					project_blocks_params_loading[md5] = null;
					delete project_blocks_params_loading[md5];
					
					if (jqXHR.responseText)
						StatusMessageHandler.showError(jqXHR.responseText);
				},
			});
		}
	}
}

function getRegionBlockParamValues(region, block, rb_index) {
	var objs = null;
	
	if ($.isNumeric(rb_index)) {
		objs = regions_blocks_params_latest_values && regions_blocks_params_latest_values.hasOwnProperty(region) && regions_blocks_params_latest_values[region].hasOwnProperty(block) && regions_blocks_params_latest_values[region][block].hasOwnProperty(rb_index) && regions_blocks_params_latest_values[region][block][rb_index] ? regions_blocks_params_latest_values[region][block][rb_index] : {};
		
		if (!objs || $.isEmptyObject(objs))
			objs = block_params_values_list && block_params_values_list[region] && block_params_values_list[region][block] ? block_params_values_list[region][block][rb_index] : {};
	}
	
	return objs;
}

function onLoadRegionBlocksParams(region_blocks_elm) {
	//console.log(project_blocks_params);
	
	var items = region_blocks_elm.find(".template_region_items .template_region_item");
	//console.log(items);
	
	for (var i = 0; i < items.length; i++)
		onLoadRegionBlockParams( $(items[i]) );
}

function onBlurRegionBlockParam(elm) {
	updateLayoutIframeFromSettingsField();
}

function onChangeRegionBlockParamType(elm) {
	elm = $(elm);

	var type = elm.val();
	var p = elm.parent();
	
	var input = p.children("input.block_param_value");
	var text = p.children(".block_param_text");
	
	if (type == "text") {
		input.hide();
		text.show();
	}
	else {
		input.show();
		text.hide();
	}
	
	updateLayoutIframeFromSettingsField();
}

function getDefinedTemplateParamHtml(param) {
	var label = param.replace(/"/g, "");
	
	return $( defined_template_param_html.replace(/#param#/g, label) );
}

function getTemplateParamHtml(param, value, default_value) {
	var is_inactive = value === null || value === undefined;
	var default_value_exists = default_value !== null && default_value !== undefined;
	//console.log(param+", "+value+", "+default_value+", "+is_inactive+", "+default_value_exists);
	
	value = value || $.isNumeric(value) ? value : "";
	var v = value.substr(0, 1) == '"' ? value.substr(1, value.length - 2).replace(/\\"/g, '"') : value;
	var vt = value.substr(0, 1) == '"' && value.substr(value.length - 1) == '"' ? value.substr(1, value.length - 2) : value;
	var p = param.substr(0, 1) == '"' ? param.substr(1, param.length - 2).replace(/\\"/g, '"') : param;
	
	var par = param.replace(/"/g, "&quot;");
	var val = value.replace(/"/g, "&quot;");
	var tp_html = $( template_param_html.replace(/#param#/g, par).replace(/#value#/g, val) );
	var template_param_active = tp_html.children(".template_param_active");
	
	template_param_active.removeAttr("checked").prop("checked", false).removeAttr("disabled").prop("disabled", false);
	
	if (is_inactive) {
		tp_html.addClass("inactive");
		tp_html.children(".template_param_name, .template_param_value, select, .template_param_text > textarea").attr("disabled", "").prop("disabled", true);
	}
	else
		template_param_active.attr("checked", "").prop("checked", true);
	
	var label_elm = tp_html.children("label").first();
	var label_text = label_elm.text().replace(/"/g, "");
	var probably_a_boolean = p.match(/^(is_|are_)/);
	
	if (probably_a_boolean)
		label_text = p.replace(/_/g, " ").replace(/\b[a-z]/g, function(letter) {
			return letter.toUpperCase();
		});
	
	label_elm.html(label_text);
	
	tp_html.children("input.template_param_value").val(v);
	tp_html.children(".template_param_text").children("textarea").val(stripslashes(vt));
	
	tp_html.children("input.template_param_value").attr("placeHolder", default_value_exists ? default_value : "");
	
	var param_value_type = value.length > 0 ? getArgumentType(value) : "string";
	var param_value_type_select = tp_html.children("select");
	param_value_type_select.val(param_value_type);
	
	//if is boolean
	if ((param_value_type == "string" || !param_value_type) && (
		value === "true" || 
		value === "false" || 
		value === 1 || 
		value === 0 || 
		v === "1" || 
		v === "0" || 
		(!v && probably_a_boolean)
	))
		param_value_type_select.val("boolean");
	
	onChangeTemplateParamType(param_value_type_select[0], true);
	
	return tp_html;
}

function addOtherTemplateParam(elm) {
	var param = prompt("Please write the Param Id that you wish to add?");
	
	if (param == null) {
		return true;
	}
	
	if (param && param.trim()) {
		param = param.trim();
		param = param.substr(0, 1) != '$' ? '"' + param + '"' : param;
		
		var tp_html = getTemplateParamHtml(param, "");
		
		var otp = $(".regions_blocks_includes_settings .other_template_params");
		otp.find(".items").append(tp_html);
		otp.find(".no_items").hide();
		
		//resizeElementLabels(otp);
	}
	else {
		alert("Error: Param Id cannot be undefined!");
	}
}

function onChangeTemplateParamType(elm, do_not_update) {
	elm = $(elm);
	
	var type = elm.val();
	var p = elm.parent();
	
	var input = p.children("input.template_param_value");
	var text = p.children(".template_param_text");
	
	if (type == "text") {
		input.hide();
		text.show();
	}
	else {
		input.show();
		text.hide();
		
		if (type == "boolean") {
			p.addClass("boolean");
			input.attr("onChange", input.attr("onBlur"));
			input.removeAttr("onBlur");
			
			var value = input.val();
			input.attr("value", value ? 1 : 0);
			input[0].type = "checkbox";
			
			if (value)
				input.prop("checked", true).attr("checked", "checked");
			else
				input.prop("checked", false).removeAttr("checked");
		}
		else {
			p.removeClass("boolean");
			input.attr("onBlur", input.attr("onChange"));
			input.removeAttr("onChange");
			
			input.prop("checked", false).removeAttr("checked");
			//Do not set the value here, bc this input can come from the type text also.
			
			input[0].type = "text";
		}
	}
	
	if (!do_not_update)
		updateLayoutIframeFromSettingsField();
}

function onActivateTemplateParam(elm) {
	elm = $(elm);
	var p = elm.parent();
	var inputs = p.find("input:not([type=checkbox]), input[type=checkbox].template_param_value, select, textarea"); //note that there are some checkboxes which are template_param_value. for the cases "Are Main Head Tags Included In Page Level?" and "Is Bootstrap Lib Included In Page Level?"
	
	if (elm[0].checked) {
		p.removeClass("inactive");
		inputs.removeAttr("disabled");
	}
	else {
		p.addClass("inactive");
		inputs.attr("disabled", "");
	}
}

function onBlurTemplateParam(elm) {
	if (elm.type == "checkbox")
		elm.value = elm.checked ? 1 : 0;
	
	updateLayoutIframeFromSettingsField();
}

function removeTemplateParam(elm) {
	$(elm).parent().remove();
	
	updateLayoutIframeFromSettingsField();
}

function getRegionsBlocksAndIncludesObjToSave() {
	var main_obj = $(".regions_blocks_includes_settings");
	
	var regions_blocks = getRegionBlockItems( main_obj.find(".region_blocks") );
	var other_regions_blocks = getRegionBlockItems( main_obj.find(".other_region_blocks") );
	
	var regions_blocks_params = regions_blocks[1];
	var regions_blocks_join_points = regions_blocks[2];
	regions_blocks = regions_blocks[0];
	
	regions_blocks_params = regions_blocks_params.concat(other_regions_blocks[1]);
	regions_blocks_join_points = regions_blocks_join_points.concat(other_regions_blocks[2]);
	other_regions_blocks = other_regions_blocks[0];
	
	var includes = [];
	var items = main_obj.find(".includes .items .item");
	for (var i = 0; i < items.length; i++) {
		var item = $(items[i]);
		
		var path = item.children("input.path").val();
		var type = item.children("select").val();
		var once = item.children("input.once").is(":checked") ? 1 : 0;
		
		includes.push({"path": path, "path_type": type, "once": once});
	}
	
	var template_params = getTemplateParamsItems( main_obj.find(".template_params") );
	var other_template_params = getTemplateParamsItems( main_obj.find(".other_template_params") );
	
	var obj = {
		regions_blocks: regions_blocks,
		other_regions_blocks: other_regions_blocks,
		regions_blocks_params: regions_blocks_params,
		regions_blocks_join_points: regions_blocks_join_points,
		includes: includes,
		template_params: template_params,
		other_template_params: other_template_params,
	};
	
	return obj;
}

function getRegionBlockItems(region_blocks_elm) {
	var regions_blocks = [];
	var regions_blocks_params = [];
	var regions_blocks_join_points = [];
	
	var items = region_blocks_elm.find(".template_region_items .template_region_item");
	for (var i = 0; i < items.length; i++) {
		var item = $(items[i]);
		
		//prepare region block
		var region = item.children("input.region").val();
		var region_type = getArgumentType(region);
		region = region_type == "string" ? region.replace(/"/g, "") : region;
		
		var type = item.children(".type").val();
		var block_type = item.children(".region_block_type").val();
		var block = null;
		var block_project = null;
		var is_html = type == 1;
		var rb_index = item.attr("rb_index");
		
		rb_index = $.isNumeric(rb_index) ? parseInt(rb_index) : null;
		
		if (block_type == "options") {
			var select = item.children(".block_options");
			
			block = select.val();
			block_type = "string";
			block_project = select[0].options[select[0].selectedIndex].getAttribute("project");
			block_project = block_project ? block_project : selected_project_id;
		}
		else if (is_html) {
			var block_html = item.children(".block_html");
			block = getRegionBlockHtmlEditorValue(block_html);
			block = block.replace(/\\/g, "\\\\"); //very important otherwise if we add a backslash it will be remove after the next time we reload the edit page (edit_entity_simple or edit_template_simple). Everytime that the edit page gets reloaded with the editor or textarea, it will show the backslashes unescaped. So when we save them, we must escape them, otherwise the next time we reload this page the backslashes will disappear.
			
			block_type = "string";
		}
		else if (block_type == "text") 
			block = item.children(".block_text").children("textarea").val();
		else
			block = item.children("input.block").val();
		
		regions_blocks.push({
			"type": type,
			"region": region,
			"region_type": region_type == "text" ? "string" : region_type,
			"block": block,
			"block_type": block_type == "text" ? "string" : block_type,
			"block_project": block_project,
			"rb_index": rb_index,
		});
		
		//prepare params
		var sub_items = item.find(".block_params .block_param");
		
		var region_block_params = [];
		for (var j = 0; j < sub_items.length; j++) {
			sub_item = $(sub_items[j]);
			
			var block_param_name = sub_item.children("input.block_param_name").val();
			var block_param_name_type = getArgumentType(block_param_name);
			block_param_name = block_param_name_type == "string" ? block_param_name.replace(/"/g, "") : block_param_name;
			
			var block_param_value_type = sub_item.children("select").val();
			var block_param_value = block_param_value_type == "text" ? sub_item.children(".block_param_text").children("textarea").val() : sub_item.children("input.block_param_value").val();
			
			region_block_params.push({
				"region": region, 
				"region_type": region_type == "text" ? "string" : region_type,
				"block": block, 
				"block_type": block_type == "text" ? "string" : block_type,
				"name": block_param_name, 
				"name_type": block_param_name_type == "text" ? "string" : block_param_name_type, 
				"value": block_param_value, 
				"value_type": block_param_value_type == "text" ? "string" : block_param_value_type,
			});
		}
		regions_blocks_params.push(region_block_params);
		
		//prepare join points
		var region_block_join_points = [];
		
		if (typeof getBlockJoinPointsObjForSaving == "function") {
			var join_points_elm = item.children(".module_join_points").children(".join_points");
			var joint_points = getBlockJoinPointsObjForSaving(join_points_elm, "module_join_points_property");
			
			if (joint_points) {
				region_block_join_points.push({
					"region": region, 
					"region_type": region_type == "text" ? "string" : region_type,
					"block": block, 
					"block_type": block_type == "text" ? "string" : block_type,
					"join_points": joint_points,
				});
			}
		}
		regions_blocks_join_points.push(region_block_join_points);
	}
	
	return [regions_blocks, regions_blocks_params, regions_blocks_join_points];
}

function getTemplateParamsItems(template_params_elm) {
	var template_params = [];
	
	var items = template_params_elm.find(".items .item");
	for (var i = 0; i < items.length; i++) {
		var item = $(items[i]);
		var is_param_disabled = item.children("input.template_param_name[disabled]").length > 0;
		
		if (!is_param_disabled) {
			var param_name = item.children("input.template_param_name").val();
			var param_name_type = getArgumentType(param_name);
			param_name = param_name_type == "string" ? param_name.replace(/"/g, "") : param_name;
			
			var param_value_type = item.children("select").val();
			var param_value = param_value_type == "text" ? item.children(".template_param_text").children("textarea").val() : item.children("input.template_param_value").val();
			
			template_params.push({
				"param": param_name, 
				"param_type": param_name_type == "text" ? "string" : param_name_type, 
				"value": param_value, 
				"value_type": param_value_type == "text" ? "string" : param_value_type,
			});
		}
	}
	
	return template_params;
}

function getSettingsTemplateRegionsBlocks(ets) {
	var template_regions = {};
	var regions_blocks = [];
	var params = {};
	var includes = [];
	
	var items = ets.find(".region_blocks, .other_region_blocks").find(".template_region_items .template_region_item");
	
	$.each(items, function (idx, item) {
		var item_data = getSettingsTemplateRegionBlockValues(item);
		var region = item_data["region"];
		var type = item_data["type"];
		var block = item_data["block"];
		var project = item_data["project"];
		var rb_index = item_data["rb_index"];
		
		if (block != "") {
			if (!$.isArray(template_regions[region]))
				template_regions[region] = [];
			
			var data = [region, block, project, type, rb_index];
			template_regions[region].push(data);
			regions_blocks.push(data);
		}
		else if (!template_regions.hasOwnProperty(region))
			template_regions[region] = "";
	});
	
	var items = ets.find(".template_params, .other_template_params").find(".items .item input.template_param_name");
	
	$.each(items, function (idx, input) {
		var is_param_disabled = input.hasAttribute("disabled");
		
		if (!is_param_disabled) {
			input = $(input);
			var param_name = input.attr("value");
			var item = input.parent();
			
			var type = item.children("select").val();
			var param_value = null;
			
			if (type == "text") {
				param_value = item.find(".template_param_text textarea").val();
				param_value = param_value ? getArgumentCode(param_value, "string") : "";
			}
			else {
				param_value = item.children("input.template_param_value").val();
				param_value = param_value ? getArgumentCode(param_value, type) : "";
			}
			
			params[param_name] = param_value;
		}
	});
	
	var items = ets.find(".includes .items .item");
	
	$.each(items, function (idx, item) {
		item = $(item);
		var path = item.children("input.path").val();
		var type = item.children("select").val();
		var once = item.children("input.once").is(":checked") ? 1 : 0;
		
		var inc_path = path ? getArgumentCode(path, type) : "";
		
		includes.push({"path": inc_path, "once": once});
	});
	
	return {"template_regions": template_regions, "regions_blocks": regions_blocks, "params": params, "includes": includes};
}

function getSettingsTemplateRegionBlockValues(item) {
	item = $(item);
	
	var type = item.children(".type").val();
	var region = item.find("input.region").attr("value");
	var block_type = item.children(".region_block_type").val();
	var block = null;
	var project = null;
	var rb_index = item.attr("rb_index");
	
	rb_index = $.isNumeric(rb_index) ? parseInt(rb_index) : null;
	
	if (type == 1) { //is html
		var block_html = item.find(".block_html");
		block = getRegionBlockHtmlEditorValue(block_html);
	}
	else if (block_type == "options") {
		var opt = item.find(".block_options option:selected");
		block = opt.attr("value");
		project = opt.attr("project");
		
		block = block ? getArgumentCode(block, "string") : block;
		project = project ? getArgumentCode(project, "string") : "";
	}
	else if (block_type == "text") {
		block = item.find(".block_text textarea").val();
		block = block ? getArgumentCode(block, "string") : "";
	}
	else {
		block = item.children("input.block").val();
		block = block ? getArgumentCode(block, block_type) : "";
	}
	
	return {
		type: type,
		region: region, 
		block: block, 
		block_type: block_type, //used in edit_simple_template_layout.js
		project: project,
		rb_index: rb_index
	};
}

function getIframeTemplateRegionsBlocksForSettings(iframe, settings_elm) {
	var data;
	var settings = getSettingsTemplateRegionsBlocks(settings_elm);
	var iframe_available_regions = [];
	
	//must do this check bc if the template gives any php error due to a bad settings configurations (in the settings_elm panel), there will be no iframe[0].contentWindow.getTemplateRegionsBlocks function. If this is not done, it will give a javascript error.
	if (!iframe[0].contentWindow || typeof iframe[0].contentWindow.getTemplateRegionsBlocks != "function") {
		//set a default data object with the settings_elm items
		var regions = [];
		if (settings["template_regions"])
			$.each(settings["template_regions"], function(region, aux) {
				regions.push(region);
			});
		
		data = {
			regions: regions,
			//regions_blocks: assignObjectRecursively({}, settings["regions_blocks"]) //clone obj
			regions_blocks: JSON.parse(JSON.stringify(settings["regions_blocks"])) //clone obj and convert it to an array. Do not use 'Object.assign({}, settings["regions_blocks"])', bc the settings["regions_blocks"] is a Plain Object and we must to have an array!
		};
	}
	else {
		data = iframe[0].contentWindow.getTemplateRegionsBlocks();
		
		iframe_available_regions = iframe[0].contentWindow.getAvailableTemplateRegions();
	}
	//console.log(assignObjectRecursively({}, data));
	//console.log(assignObjectRecursively({}, settings));
	
	var new_regions_blocks_list = data["regions_blocks"]; //global var
	var new_template_params_values_list = settings["params"]; //global var
	
	//prepare regions from settings that are not in the layout iframe
	//check if exists any of this that is not inside of data["regions"] and add to new_regions_blocks_list the correspondent item
	if (settings["regions_blocks"])
		$.each(settings["regions_blocks"], function(idx, rb) {
			var region = rb[0];
			var region_exists = $.inArray(region, data["regions"]) != -1;
			
			if (!region_exists) {
				var is_region_visible = iframe_available_regions && $.inArray(region, iframe_available_regions) != -1; //check if is visible in iframe
				
				//if layout have this region (which means it is visible), then it means the layout has empty region_blocks and we should only add an empty region_block with empty values. We should only add 1 region_block, which is the default. 
				if (is_region_visible) {
					$.each(new_regions_blocks_list, function(idx, rb2) {
						var rb_region = rb2[0];
						
						if (rb_region == region) {
							region_exists = true;
							return false;
						}
					});
					
					if (!region_exists) {
						//console.log(rb);
						new_regions_blocks_list.push([region, "", "", 2]); //Do not add the rb object, otherwise the layout will not be synced with the settings. Must be empty values. 2 == block
					}
				}
				else //if layout doesn't have this region or is hidden, we should add the correspondent region_block from settings. If there is more than one hidden region_block, we should add them all. Not only the first one. We should add all the hidden regions_blocks.
					new_regions_blocks_list.push(rb);
			}
		});
	
	//check if exists any of this that is not inside of data["regions"] and add to new_regions_blocks_list an empty element
	if (settings["template_regions"])
		$.each(settings["template_regions"], function(region, aux) {
			var region_exists = false;
			
			$.each(new_regions_blocks_list, function(idx, rb) {
				var rb_region = rb[0];
				
				if (rb_region == region) {
					region_exists = true;
					return false;
				}
			});
			
			if (!region_exists)
				new_regions_blocks_list.push([region, "", "", 2]); //2 == block
		});
		
	//sort regions in new_regions_blocks_list according with settings["regions_blocks"]
	if (settings["regions_blocks"]) {
		var sorted = [];
		var repeated = [];
		
		$.each(settings["regions_blocks"], function(idx, rb) {
			var region = rb[0];
			
			if (repeated.indexOf(region) == -1) {
				repeated.push(region);
				
				$.each(new_regions_blocks_list, function(idy, rb2) {
					if (region == rb2[0])
						sorted.push(rb2);
				});
			}
		});
		
		$.each(new_regions_blocks_list, function(idx, rb) {
			var region = rb[0];
			
			if (repeated.indexOf(region) == -1)
				sorted.push(rb);
		});
		
		new_regions_blocks_list = sorted;
	}
		
	//console.log(data);
	//console.log(settings);
	//console.log(new_regions_blocks_list);
	//console.log(new_template_params_values_list);
	
	//prepare template params
	//get the template params but only the original ones. Leave the extra params out.
	var orig_template_params = [];
	var inputs = settings_elm.find(".template_params .items .item input.template_param_name");
	$.each(inputs, function (idx, input) {
		var is_param_disabled = input.hasAttribute("disabled");
		
		if (!is_param_disabled) {
			var param_name = $(input).attr("value");
			orig_template_params.push(param_name);
		}
	});
	
	//create the params array with only the original template params
	var params = [];
	$.each(settings["params"], function(param_name, param_value) {
		if ($.inArray(param_name, orig_template_params) != -1)
			params.push(param_name);
	});
	
	//get only regions belonging to the template. Leave the extra regions out.
	var orig_template_regions = [];
	var inputs = settings_elm.find(".region_blocks .template_region_items .template_region_item input.region");
	
	$.each(inputs, function (idx, input) {
		var region = $(input).attr("value");
		
		if ($.inArray(region, orig_template_regions) == -1)
			orig_template_regions.push(region);
	});
	
	return {
		regions_blocks_list: new_regions_blocks_list,
		template_params_values_list: new_template_params_values_list,
		regions: orig_template_regions, 
		params: params
	};
}

function areLayoutAndSettingsDifferent(iframe, settings_elm, include_order) {
	if (!iframe[0].contentWindow || typeof iframe[0].contentWindow.getTemplateRegionsBlocks != "function")
		return true;
	
	var iframe_data = iframe[0].contentWindow.getTemplateRegionsBlocks();
	var iframe_available_regions = iframe[0].contentWindow.getAvailableTemplateRegions();
	var settings_data = getSettingsTemplateRegionsBlocks(settings_elm);
	
	var are_different = false;
	var regions_count = 0;
	var visible_regions_count = 0;
	
	//check if unique regions exists in both sides
	if (settings_data["template_regions"]) {
		//get only regions belonging to the template. Leave the extra regions out.
		var orig_template_regions = [];
		var inputs = settings_elm.find(".region_blocks .template_region_items .template_region_item input.region");
		
		$.each(inputs, function (idx, input) {
			var region = $(input).attr("value");
			orig_template_regions.push(region);
		});
		
		//check if unique regions exists in both sides, but only the original template regions and visible
		$.each(settings_data["template_regions"], function(region, aux) {
			var is_template_region = $.inArray(region, orig_template_regions) != -1;
			
			if (is_template_region) {
				var is_visible_region = iframe_available_regions && $.inArray(region, iframe_available_regions) != -1;
				
				regions_count += $.isArray(aux) ? aux.length : 1;
				
				if (is_visible_region)
					visible_regions_count += $.isArray(aux) ? aux.length : 1;
				
				if ($.inArray(region, iframe_data["regions"]) == -1) {
					
					if (is_visible_region) {
						are_different = true;
						return false; //exit loop
					}
					else {
						//will be taken care in the code below
					}
				}
			}
		});
	}
	
	if (!are_different && (visible_regions_count > 0 && !iframe_data["regions"]) || (iframe_data["regions"] && visible_regions_count != iframe_data["regions"].length))
		are_different = true;
	//console.log("are_different0:"+are_different);
	
	if (!are_different && settings_data["regions_blocks"]) {
		//check if unique regions exists in both sides, but only the original template regions and hidden ones
		$.each(settings_data["regions_blocks"], function(idx, region_block) {
			var region = region_block[0];
			var is_hidden_region = !iframe_available_regions || $.inArray(region, iframe_available_regions) == -1;
			
			if (is_hidden_region) {
				region_block_item = regions_blocks_list[idx];
				
				if (!region_block_item) {
					are_different = true;
					return false; //exit loop
				}
				
				var type = region_block[3] == 3 ? 2 : (region_block[3] == 5 ? 4 : region_block[3]);
				var item_type = region_block_item[3] == 3 ? 2 : (region_block_item[3] == 5 ? 4 : region_block_item[3]);
				var is_html = type == 1;
				var is_different = region_block[0] != region_block_item[0] || region_block[1] != region_block_item[1] || (!is_html && region_block[2] != region_block_item[2]) || type != item_type;
				
				if (is_different) {
					are_different = true;
					return false; //exit loop
				}
			}
		});
		//console.log("are_different1:"+are_different);
		
		//check if regions-blocks are the same in both sides, but only check for region-blocks that belongs to the template, this is, extra region-blocks will be ignore. Include order in settings_data and iframe_data.
		//use for settings_data["regions_blocks"]
		if (!are_different)
			are_different = areRegionsBlocksDifferent(settings_data["regions_blocks"], iframe_data["regions_blocks"], iframe_data["regions"], include_order);
		//console.log("are_different2:"+are_different);
	}
	
	//check if regions-blocks are the same in both sides, but only check for region-blocks that belongs to the template, this is, extra region-blocks will be ignore. Include order in settings_data and iframe_data.
	//use for iframe_data["regions_blocks"]
	if (!are_different && iframe_data["regions_blocks"])
		are_different = areRegionsBlocksDifferent(iframe_data["regions_blocks"], settings_data["regions_blocks"], iframe_data["regions"], include_order);
	//console.log("are_different3:"+are_different);
	
	//console.log(JSON.parse(JSON.stringify(settings_data)));
	//console.log(JSON.parse(JSON.stringify(iframe_data)));
	//console.log(are_different);
	return are_different;
}

//check if regions-blocks are the same in both sides, but only check for region-blocks that belongs to the template, this is, extra region-blocks will be ignore. Include order in settings_data and iframe_data.
function areRegionsBlocksDifferent(regions_blocks_1, regions_blocks_2, iframe_data_regions, include_order) {
	var are_different = false;
	var region_block_orders_1 = {};
	//console.log(assignObjectRecursively({}, regions_blocks_1));
	//console.log(assignObjectRecursively({}, regions_blocks_2));
	
	$.each(regions_blocks_1, function(idx, rb_1) {
		var region_1 = rb_1[0];
		var is_template_region = $.inArray(region_1, iframe_data_regions) != -1;
		
		if (is_template_region) {
			var rb_json_1 = JSON.stringify(rb_1);
			var region_exists = false;
			var region_block_orders_2 = {};
			
			region_block_orders_1[region_1] = region_block_orders_1.hasOwnProperty(region_1) ? region_block_orders_1[region_1] + 1 : 0;
			
			$.each(regions_blocks_2, function(idy, rb_2) {
				var region_2 = rb_2[0];
				
				if (region_1 == region_2) {
					region_block_orders_2[region_2] = region_block_orders_2.hasOwnProperty(region_2) ? region_block_orders_2[region_2] + 1 : 0;
					
					if ((!include_order || region_block_orders_1[region_1] == region_block_orders_2[region_2]) && areRegionBlocksEqual(rb_1, rb_2, rb_json_1)) {
						region_exists = true;
						return false; //exit loop
					}
					/*else if (include_order && areRegionBlocksEqual(rb_1, rb_2, rb_json_1)) {
						console.log(rb_1);
						console.log(rb_2);
						console.log(region_block_orders_1[region_1]+"=="+region_block_orders_2[region_2]);
					}*/
				}
			});
			
			if (!region_exists) {
				are_different = true;
				return false; //exit loop
			}
		}
	});
	
	return are_different;
}

function areRegionBlocksEqual(rb_1, rb_2, rb_json_1, rb_json_2) {
	var is_equal = false;
	var rb_json_1 = rb_json_1 ? rb_json_1 : JSON.stringify(rb_1);
	var rb_json_2 = rb_json_2 ? rb_json_2 : JSON.stringify(rb_2);
	
	if (rb_json_1 == rb_json_2)
		is_equal = true;
	else { //check html by removing space characters
		var type = rb_1[3] == 3 ? 2 : (rb_1[3] == 5 ? 4 : rb_1[3]);
		var item_type = rb_2[3] == 3 ? 2 : (rb_2[3] == 5 ? 4 : rb_2[3]);
		var is_html = type == 1;
		var is_item_html = item_type == 1;
		var block = is_html ? ("" + rb_1[1]).replace(/\s/g, "") : rb_1[1];
		var item_block = is_item_html ? ("" + rb_2[1]).replace(/\s/g, "") : rb_2[1];
		
		if (rb_1[0] == rb_2[0] && block == item_block && (is_html || rb_1[2] == rb_2[2]) && type == item_type && rb_1[4] == rb_2[4])
			is_equal = true;
	}
	
	return is_equal;
}

function updateRegionsBlocksParamsLatestValues(regions_blocks_includes_settings) {
	regions_blocks_params_latest_values = {};
	
	var region_blocks_items = getRegionBlockItems( regions_blocks_includes_settings.find(".region_blocks") );
	var other_region_blocks_items = getRegionBlockItems( regions_blocks_includes_settings.find(".other_region_blocks") );
	
	var items = [region_blocks_items, other_region_blocks_items];
	
	$.each(items, function(idx, rb_items) {
		var region_blocks_items_values = rb_items[0];
		var region_blocks_items_params = rb_items[1];
		
		for (var i = 0; i < region_blocks_items_params.length; i++) {
			var region_block_item_params = region_blocks_items_params[i];
			
			if (region_block_item_params.length) {
				var region_block_item_values = region_blocks_items_values[i];
				var rb_index = region_block_item_values["rb_index"];
				
				if (!$.isNumeric(rb_index)) {
					updateRegionBlockRBIndex();
				}
				
				if ($.isNumeric(rb_index)) {
					var r = getArgumentCode(region_block_item_values["region"], region_block_item_values["region_type"]);
					var b = getArgumentCode(region_block_item_values["block"], region_block_item_values["block_type"]);
					
					for (var j = 0; j < region_block_item_params.length; j++) {
						var param = region_block_item_params[j];
						var p_name = getArgumentCode(param["name"], param["name_type"]);
						var p_value = getArgumentCode(param["value"], param["value_type"]);
						
						if (!regions_blocks_params_latest_values.hasOwnProperty(r))
							regions_blocks_params_latest_values[r] = {};
						
						if (!regions_blocks_params_latest_values[r].hasOwnProperty(b))
							regions_blocks_params_latest_values[r][b] = {};
						
						if (!regions_blocks_params_latest_values[r][b].hasOwnProperty(rb_index))
							regions_blocks_params_latest_values[r][b][rb_index] = {};
						
						regions_blocks_params_latest_values[r][b][rb_index][p_name] = p_value;
					}
				}
			}
		}
	});
}

//if elm was added dynamically it will not have the rb_index, so we must add it dynamically. if rb_index is already set and block changes, do not do nothing
function updateRegionsBlocksRBIndexIfNotSet(regions_blocks_includes_settings) {
	var items = regions_blocks_includes_settings.find(".region_blocks, .other_region_blocks").find(".template_region_items .template_region_item")
	
	for (var i = 0; i < items.length; i++) {
		var item = $(items[i]);
		var rb_index = item.attr("rb_index");
		
		if (!$.isNumeric(rb_index)) {
			var item_data = getSettingsTemplateRegionBlockValues(item);
			var item_type = item_data["type"];
			var block_hash = item_type == 1 ? $.md5(item_data["block"]) : (item_type == 2 || item_type == 3 ? "block_" : "view_") + item_data["block"]; //if html set md5
			var item_rb_index_key = item_data["region"] + "-" + block_hash + "-" + item_data["project"];
			var rb_index = 0;
			
			for (var j = 0; j < items.length; j++) {
				if (j != i) {
					var sub_item_data = getSettingsTemplateRegionBlockValues(items[j]);
					var sub_item_type = sub_item_data["type"];
					var block_hash = sub_item_type == 1 ? $.md5(sub_item_data["block"]) : (sub_item_type == 2 || sub_item_type == 3 ? "block_" : "view_") + sub_item_data["block"]; //if html set md5
					var sub_item_rb_index_key = sub_item_data["region"] + "-" + block_hash + "-" + sub_item_data["project"];
					
					if (sub_item_rb_index_key == item_rb_index_key && $.isNumeric(sub_item_data["rb_index"]) && sub_item_data["rb_index"] >= rb_index)
						rb_index = sub_item_data["rb_index"] + 1;
				}
			}
			
			item.attr("rb_index", rb_index);
		}
	}
}

function updateSettingsFromLayoutIframeField(on_complete) {
	if (typeof update_settings_from_layout_iframe_func == "function") {
		if (auto_convert_settings_from_layout) {
			settings_need_to_be_converted_from_layout = false;
			
			//disable auto save
			var auto_save_bkp = auto_save;
			auto_save = false;
			
			//clear previous func
			if (update_settings_from_layout_iframe_timeout_id)
				clearTimeout(update_settings_from_layout_iframe_timeout_id);
			
			//execute func
			update_settings_from_layout_iframe_timeout_id = setTimeout(function() { //must be in a settimeout bc sometimes this function is called multiple times in 1 sec and we must be sure this doesn't happen... This function gets called when we trigger the onChange events from the block_options select events in the loadAvailableBlocksListInRegionsBlocks method and when we write something in the LayoutUIEditor
				update_settings_from_layout_iframe_timeout_id && clearTimeout(update_settings_from_layout_iframe_timeout_id);
				update_settings_from_layout_iframe_timeout_id = null;
				
				//save synchronization functions
				var update_settings_from_layout_iframe_func_bkp = update_settings_from_layout_iframe_func;
				var update_layout_iframe_from_settings_func_bkp = update_layout_iframe_from_settings_func;
				var update_layout_iframe_field_html_value_from_settings_func_bkp = update_layout_iframe_field_html_value_from_settings_func;
				
				//disable synchronization functions in case some call recursively by mistake
				update_settings_from_layout_iframe_func = null;
				update_layout_iframe_from_settings_func = null;
				update_layout_iframe_field_html_value_from_settings_func = null;
				
				//synchronize
				if (typeof update_settings_from_layout_iframe_func_bkp == "function")
					update_settings_from_layout_iframe_func_bkp();
				
				//sets back synchronization functions
				update_settings_from_layout_iframe_func = update_settings_from_layout_iframe_func_bkp;
				update_layout_iframe_from_settings_func = update_layout_iframe_from_settings_func_bkp;
				update_layout_iframe_field_html_value_from_settings_func = update_layout_iframe_field_html_value_from_settings_func_bkp;
				
				//execute on complete. Note that the update_settings_from_layout_iframe_func_bkp in edit_template_simple and edit_entity_simple is a synchronous method, so we can simply execute the on_complete method here.
				if (typeof on_complete == "function")
					on_complete();
				
				//enable auto save if apply
				if (auto_save_bkp)
					auto_save = auto_save_bkp;
			}, 1500);
		}
		else {
			settings_need_to_be_converted_from_layout = true;
			
			if (typeof on_complete == "function")
				on_complete();
		}
	}
	else if (typeof on_complete == "function")
		on_complete();
}

function updateLayoutIframeFromSettingsField() {
	if (typeof update_layout_iframe_from_settings_func == "function") {
		//disable auto save
		var auto_save_bkp = auto_save;
		auto_save = false;
		
		//clear previous func
		if (update_layout_iframe_from_settings_timeout_id)
			clearTimeout(update_layout_iframe_from_settings_timeout_id);
		
		//execute func
		update_layout_iframe_from_settings_timeout_id = setTimeout(function() { //must be in a settimeout bc sometimes this function is called multiple times in 1 sec and we must be sure this doesn't happen... This function gets called when we trigger the onChange events from the block_options select events in the loadAvailableBlocksListInRegionsBlocks method
			update_layout_iframe_from_settings_timeout_id && clearTimeout(update_layout_iframe_from_settings_timeout_id);
			update_layout_iframe_from_settings_timeout_id = null;
			
			//save synchronization functions
			var update_settings_from_layout_iframe_func_bkp = update_settings_from_layout_iframe_func;
			var update_layout_iframe_from_settings_func_bkp = update_layout_iframe_from_settings_func;
			var update_layout_iframe_field_html_value_from_settings_func_bkp = update_layout_iframe_field_html_value_from_settings_func;
			
			//disable synchronization functions in case some call recursively by mistake
			update_settings_from_layout_iframe_func = null;
			update_layout_iframe_from_settings_func = null;
			update_layout_iframe_field_html_value_from_settings_func = null;
			
			//synchronize
			if (typeof update_layout_iframe_from_settings_func_bkp == "function")
				update_layout_iframe_from_settings_func_bkp();
			
			//sets back synchronization functions
			update_settings_from_layout_iframe_func = update_settings_from_layout_iframe_func_bkp;
			update_layout_iframe_from_settings_func = update_layout_iframe_from_settings_func_bkp;
			update_layout_iframe_field_html_value_from_settings_func = update_layout_iframe_field_html_value_from_settings_func_bkp;
			
			//enable auto save if apply
			if (auto_save_bkp)
				auto_save = auto_save_bkp;
		}, 1500);
	}
}

function updateLayoutIframeFieldHtmlValueFromSettingsField(elm, html) {
	if (typeof update_layout_iframe_field_html_value_from_settings_func == "function") {
		//disable auto save
		var auto_save_bkp = auto_save;
		auto_save = false;
		
		//execute func
		update_layout_iframe_field_html_value_from_settings_func(elm, html);
		
		//enable auto save if apply
		if (auto_save_bkp)
			auto_save = auto_save_bkp;
	}
}

function updateLayoutIframeRegionBlockHtmlFromSettingsHtmlField(elm, html, iframe) {
	//set handler to update directly the the html in the template layout without refreshing the entire layout.
	var item = $(elm).parent().closest(".template_region_item");
	var item_data = getSettingsTemplateRegionBlockValues(item);
	var region = item_data["region"];
	var is_item_html = item_data["type"] == 1;
	var item_index = -1;
	
	var siblings = item.parent().find(".template_region_item");
	for (var i = 0; i < siblings.length; i++) {
		var sibling = $(siblings[i]);
		var sibling_data = getSettingsTemplateRegionBlockValues(sibling);
		
		if (region == sibling_data["region"]) {
			item_index++;
			
			if (item[0] == sibling[0]) 
				break;
		}
	}
	
	if (item_index > -1) {
		var iframe_body = iframe.contents().find("body");
		var iframe_items = iframe_body.find(".template_region[region='" + region + "']").first();
		var iframe_item = iframe_items.find(" > .template_region_items .template_region_item")[item_index];
		
		if (iframe_item) {
			iframe_item = $(iframe_item);
			
			var iframe_item_data = getSettingsTemplateRegionBlockValues(iframe_item);
			var iframe_item_type = iframe_item_data["type"];
			var is_iframe_item_html = iframe_item_type == 1;
			
			if (is_iframe_item_html && is_item_html && iframe_item_data["project"] == item_data["project"] && iframe_item_data["block"] != item_data["block"]) {
				//save synchronization functions
				var update_settings_from_layout_iframe_func_bkp = update_settings_from_layout_iframe_func;
				var update_layout_iframe_from_settings_func_bkp = update_layout_iframe_from_settings_func;
				var update_layout_iframe_field_html_value_from_settings_func_bkp = update_layout_iframe_field_html_value_from_settings_func;
				
				//disable synchronization functions in case some call recursively by mistake
				update_settings_from_layout_iframe_func = null;
				update_layout_iframe_from_settings_func = null;
				update_layout_iframe_field_html_value_from_settings_func = null;
				
				//update value
				var iframe_block_html = iframe_item.children(".block_html");
				setRegionBlockHtmlEditorValue(iframe_block_html, html);
				
				//sets back synchronization functions
				update_settings_from_layout_iframe_func = update_settings_from_layout_iframe_func_bkp;
				update_layout_iframe_from_settings_func = update_layout_iframe_from_settings_func_bkp;
				update_layout_iframe_field_html_value_from_settings_func = update_layout_iframe_field_html_value_from_settings_func_bkp;
			}
		}
	}
}

function updateLayoutIframeFromSettings(iframe, data, settings, iframe_html_to_parse) {
	if (checkIfReloadLayoutIframeFromSettings(iframe, data, settings))
		reloadLayoutIframeFromSettings(iframe, data, iframe_html_to_parse);
	else {
		//find the elements that are different and update only that ones. Not sure if this is possible bc of the region params... Or even if it makes sense anymore... Test first and see if I still need to do this...
		var iframe_body = iframe.contents().find("body");
		var template_regions = data["template_regions"];
		//console.log("updateLayoutIframeFromSettings");
		
		if (!template_regions) {
			cleanRegionBlockHtmlEditorInstances(iframe_body); //clean editor instances before it empty the html from region_blocks. This is very important, otherwise the editor is overloading the browser memory with too many instances...
			
			iframe_body.find(".template_region > .template_region_items").html("");
		}
		else {
			var iframe_settings = iframe[0].contentWindow.getTemplateRegionsBlocks();
			var PtlLayoutUIEditor = $(".code_layout_ui_editor .layout-ui-editor").data("LayoutUIEditor");
			
			//prepare iframe data to have the same structure than template_regions var
			var iframe_regions_blocks = {};
			
			if (iframe_settings["regions_blocks"])
				$.each(iframe_settings["regions_blocks"], function(idx, region_item) {
					var region_name = region_item[0];
					
					if (!iframe_regions_blocks.hasOwnProperty(region_name))
						iframe_regions_blocks[region_name] = [];
					
					iframe_regions_blocks[region_name].push(region_item);
				});
			
			//console.log(iframe_regions_blocks);
			//console.log(template_regions);
			
			//compare template_regions with iframe_regions_blocks
			$.each(template_regions, function(region_name, region_items) {
				var iframe_region_items = iframe_regions_blocks[region_name];
				var template_region_elm = iframe_body.find(".template_region[region='" + region_name + "']");
				var template_region_items = template_region_elm.children(".template_region_items");
				
				//only if template_region exists or is visible, otherwise this case was already took care previously by the checkIfReloadLayoutIframeFromSettings method.
				if (template_region_elm[0]) {
					/* DEPRECATED: this case is already taken care in the checkIfReloadLayoutIframeFromSettings method
					//if template_region does not exists in iframe, reload iframe and get out from loop
					if (!template_region_items[0]) {
						reloadLayoutIframeFromSettings(iframe, data, iframe_html_to_parse);
						return false;
					}
					else */if (!$.isArray(region_items) && !$.isPlainObject(region_items) && iframe_region_items && iframe_region_items.length > 0) { //if region has no items, set correspondent iframe .template_region_items with empty html.
						cleanRegionBlockHtmlEditorInstances(template_region_items); //clean editor instances before it empty the html from region_blocks. This is very important, otherwise the editor is overloading the browser memory with too many instances...
							
						template_region_items.html("");
					}
					else {
						//append template_region_item to template_region_items. Note that the template_region_item can be inside of inner html elements
						var iframe_region_items_children = template_region_items.find(".template_region_item");
						
						for (var i = 0; i < iframe_region_items_children.length; i++)
							template_region_items.append(iframe_region_items_children[i]);
						
						//then removes all children that are not .item
						var nodes = template_region_items.contents();
						//console.log(nodes);
						
						for (var i = 0; i < nodes.length; i++) {
							var node = nodes[i];
							
							if (node.nodeType != Node.ELEMENT_NODE || !node.classList.contains("template_region_item")) {
								//console.log(node);
								template_region_items[0].removeChild(node);
							}
						}
						//console.log(template_region_items[0].childNodes);
						
						//compares iframe and new regions, and update the different region items
						var item_to_insert_index = 0;
						//console.log(iframe_region_items);
						//console.log(iframe_region_items_children);
						
						$.each(region_items, function(idx, region_item) {
							var iframe_region_item = iframe_region_items ? iframe_region_items[idx] : null;
							var iframe_item = $(iframe_region_items_children[item_to_insert_index]); //get the iframe_item where the items will be inserted before...
							var type = region_item[3];
							var is_html = type == 1;
							
							if (!is_html)
								item_to_insert_index++; //prepare the next index for iframe_item
							
							if (!iframe_region_item || JSON.stringify(region_item) != JSON.stringify(iframe_region_item) || is_html) {
								//add new iframe item with new data
								var block = region_item[1];
								var project = region_item[2];
								var rb_index = region_item[4];
								var rb_html = getRegionBlockHtml(region_name, block, project, type, rb_index);
								
								if (iframe_item[0]) {
									//if previous iframe_item exists, insert new item after it
									rb_html.insertBefore(iframe_item);
									
									if (!is_html) {
										//remove old iframe item
										cleanRegionBlockHtmlEditorInstances(iframe_item); //clean editor instances before it empty the html from region_blocks. This is very important, otherwise the editor is overloading the browser memory with too many instances...
										iframe_item.remove();
									}
								}
								else //insert new item at the end of .template_region_items
									template_region_items.append(rb_html);
								
								if (!is_html) { //only if not html, otherwise it will be converted in html elements
									//prepare new region block with iframe handlers
									pretifyRegionBlockComboBox( rb_html.children(".block_options") ); //call pretifyRegionBlockComboBox before the prepareNewRegion, otherwise gives a javascript error.
									iframe[0].contentWindow.prepareNewRegion(rb_html[0]);
									
									//simulate region-block if applies
									var region_block_type = rb_html.children(".region_block_type").val();
									
									if (block && region_block_type == "options")
										iframe[0].contentWindow.prepareRegionBlockSimulatedHtml(rb_html[0]);
									//else 
									//	iframe[0].contentWindow.prepareRegionBlockHtmlEditor(rb_html[0]); //no need this anymore bc this .item will be converted in html elements
								}
							}
						});
						
						//remove all the other items
						for (var i = item_to_insert_index; i < iframe_region_items_children.length; i++) {
							var iframe_item = $(iframe_region_items_children[i]);
							
							cleanRegionBlockHtmlEditorInstances(iframe_item); //clean editor instances before it empty the html from region_blocks. This is very important, otherwise the editor is overloading the browser memory with too many instances...
							
							iframe_item.remove();
						}
						
						//convert html blocks into inner elements
						iframe[0].contentWindow.convertRegionBlocksToSimpleHtml(template_region_items);
						PtlLayoutUIEditor.parseElementInnerHtml(template_region_items);
					}
				}
			});
		}
	}
}

function checkIfReloadLayoutIframeFromSettings(iframe, iframe_data, settings_data) {
	var reload = false;
	
	//check if there is any new region and it is, call the reloadLayoutIframeFromSettings with iframe_html_to_parse
	var iframe_available_regions = iframe[0].contentWindow.getAvailableTemplateRegions();
	var template_regions = iframe_data["template_regions"];
	
	//console.log(assignObjectRecursively({}, settings_data["regions_blocks"]));
	//console.log(assignObjectRecursively({}, template_regions));
	//console.log(assignObjectRecursively({}, regions_blocks_list));
	
	//check if there are any hidden template_region
	var hidden_or_not_existent_template_regions = [];
	
	if (template_regions) {
		$.each(template_regions, function(region_name, region_items) {
			if (!iframe_available_regions || $.inArray(region_name, iframe_available_regions) == -1)
				hidden_or_not_existent_template_regions.push(region_name);
		});
		
		if (settings_data["regions_blocks"] && regions_blocks_list) {
			//check if there is any visible template_region that is empty or doesn't exists in iframe
			var iframe_body = iframe.contents().find("body");
			
			$.each(template_regions, function(region_name, region_items) {
				//only if template_region exists or visible, otherwise this case will be taken care below.
				if ($.inArray(region_name, hidden_or_not_existent_template_regions) == -1) {
					var template_region_elm = iframe_body.find(".template_region[region='" + region_name + "']");
					
					//if template_region does not exists or doesn't contain the div.template_region_items in iframe
					if (!template_region_elm[0] || !template_region_elm.children(".template_region_items")[0]) {
						reload = true;
						//console.log("reload 1");
						return false;
					}
				}
			});
		}
	}
	
	if (!reload && hidden_or_not_existent_template_regions.length > 0) {
		//check if there are any changes in any hidden template_region (which mean that this template_region was converted in a comment in the edit_simple_template_layout.php and we cannot update dynamically the correspondent region_blocks bc this region doesn't exists in the layout), so we need to reload the iframe in order for the update to be executed.
		if (settings_data["regions_blocks"] && regions_blocks_list) {
			$.each(settings_data["regions_blocks"], function(idx, region_block) {
				//if region is hidden, check if there are any changes
				if ($.inArray(region_block[0], hidden_or_not_existent_template_regions) != -1) {
					region_block_item = regions_blocks_list[idx];
					
					if (!region_block_item) {
						reload = true;
						//console.log("reload 2");
						return false;
					}
					
					var type = region_block[3] == 3 ? 2 : (region_block[3] == 5 ? 4 : region_block[3]);
					var item_type = region_block_item[3] == 3 ? 2 : (region_block_item[3] == 5 ? 4 : region_block_item[3]);
					var is_html = type == 1;
					var is_item_html = item_type == 1;
					var is_different = region_block[0] != region_block_item[0] || region_block[1] != region_block_item[1] || (!is_html && region_block[2] != region_block_item[2]) || type != item_type;
					
					if (is_different) {
						//console.log(region_block);
						//console.log(region_block_item);
						//console.log("reload 3");
						reload = true;
						return false;
					}
				}
			});
		}
		else { //if there are hidden regions but not regions_blocks, reload the iframe
			reload = true;
			//console.log("reload 4");
		}
	}
	
	//check if params are different
	if (!reload)
		reload = JSON.stringify(template_params_values_list) != JSON.stringify(settings_data["params"]);
	
	//check if includes are different
	if (!reload)
		reload = JSON.stringify(includes_list) != JSON.stringify(settings_data["includes"]);
	
	//console.log("reload:"+reload);
	return reload;
}

function reloadLayoutIframeFromSettings(iframe, data, iframe_html_to_parse) {
	//console.log("inside reloadLayoutIframeFromSettings");
	
	try {
		//close menu settings if open, otherwise we loose the reference for the selected widget
		var PtlLayoutUIEditor = $(".code_layout_ui_editor .layout-ui-editor").data("LayoutUIEditor");
		
		if (PtlLayoutUIEditor && PtlLayoutUIEditor.isMenuSettingsOpened())
			PtlLayoutUIEditor.getMenuSettings().find(" > .settings-info > .close").trigger("click");
		
		var iframe_parent = iframe.parent().closest(".code_layout_ui_editor");
		var iframe_url = iframe.attr("edit_simple_template_layout_url");
		//console.log(iframe);
		//console.log(iframe_url);
		//console.log(data);
		
		//set iframe url for the LayoutUIEditor so it can call theinitAfterTemplateWidgetsIframeReload
		iframe.attr("data-init-src", iframe_url);
		
		//clean iframe first
		var iframe_head = iframe.contents().find("head");
		var iframe_body = iframe.contents().find("body");
		
		//reset iframe html
		cleanRegionBlockHtmlEditorInstances(iframe_body); //clean editor instances before it empty the html from region_blocks. This is very important, otherwise the editor is overloading the browser memory with too many instances...
		
		iframe_head.html("");
		iframe_body.html("");
		
		//load new html
		var post_data = data ? data : {};
		post_data["html_to_parse"] = iframe_html_to_parse;
		
		if (entity_or_template_obj_inited)
			MyFancyPopup.showLoading();
		
		$.ajax({
			url: iframe_url,
			type: 'post',
			processData: false,
			contentType: 'text/html',
			data: JSON.stringify(post_data),
			dataType: 'html',
			success: function(parsed_html, textStatus, jqXHR) {
				//show login popup
				if (jquery_native_xhr_object && isAjaxReturnedResponseLogin(jquery_native_xhr_object.responseURL)) {
					if (entity_or_template_obj_inited)
						MyFancyPopup.hideLoading();
					
					showAjaxLoginPopup(jquery_native_xhr_object.responseURL, iframe_url, function() {
						reloadLayoutIframeFromSettings(iframe, data, iframe_html_to_parse)
					});
				}
				else {
					//prepare iframe_on_load_update_settings_layout_handler
					var iframe_on_load_update_settings_layout_handler = null;
					
					if (update_settings_from_layout_iframe_func || update_layout_iframe_from_settings_func) {
						//save synchronization functions
						var update_settings_from_layout_iframe_func_bkp = update_settings_from_layout_iframe_func;
						var update_layout_iframe_from_settings_func_bkp = update_layout_iframe_from_settings_func;
						var update_layout_iframe_field_html_value_from_settings_func_bkp = update_layout_iframe_field_html_value_from_settings_func;
						
						//disable synchronization functions in case some call recursively by mistake
						update_settings_from_layout_iframe_func = null;
						update_layout_iframe_from_settings_func = null;
						update_layout_iframe_field_html_value_from_settings_func = null;
						
						iframe_on_load_update_settings_layout_handler = function() {
							//console.log("iframe_on_load_update_settings_layout_handler - only one time!");
							iframe.unbind("load", iframe_on_load_update_settings_layout_handler);
							
							//sets back synchronization functions
							update_settings_from_layout_iframe_func = update_settings_from_layout_iframe_func_bkp;
							update_layout_iframe_from_settings_func = update_layout_iframe_from_settings_func_bkp;
							update_layout_iframe_field_html_value_from_settings_func = update_layout_iframe_field_html_value_from_settings_func_bkp;
						};
						iframe.load(iframe_on_load_update_settings_layout_handler);
					}
					
					//prepare iframe_on_load_hide_popup_handler
					var iframe_on_load_hide_popup_handler = null;
					
					if (entity_or_template_obj_inited) {
						iframe_on_load_hide_popup_handler = function() {
							//console.log("iframe_on_load_hide_popup_handler - only one time!");
							iframe.unbind("load", iframe_on_load_hide_popup_handler);
							
							//hide loading icon
							MyFancyPopup.hideLoading();
						};
						iframe.load(iframe_on_load_hide_popup_handler);
					}
					
					//template may have javascript errors
					try {
						//console.log("iframe_on_load_update_settings_layout_handler:"+iframe_on_load_update_settings_layout_handler);
						//console.log("iframe_on_load_hide_popup_handler:"+iframe_on_load_hide_popup_handler);
						
						//prepare parsed_html
						var html = parsed_html;
						var luie = iframe_parent.children(".layout-ui-editor");
						var PtlLayoutUIEditor = luie.data("LayoutUIEditor");
						
						if (PtlLayoutUIEditor) {
							var exists_head = PtlLayoutUIEditor.existsTagFromSource(html, "head");
							var exists_body = PtlLayoutUIEditor.existsTagFromSource(html, "body");
							var exists_html = PtlLayoutUIEditor.existsTagFromSource(html, "html");
							var code_exists = html.replace(/^\s+/g, "").replace(/\s+$/g, "") != "";
							var non_standard_code = code_exists && !exists_html && !exists_head && !exists_body;
							
							//prepare body html, with right php tags parsed.
							if (exists_body) {
								var body_html = PtlLayoutUIEditor.getTagContentFromSource(html, "body");
								
								if (body_html) {
									//parse body html and replace the original body with that parsed html. This is very important bc if there are php codes inside of html element attributes, this will take care of this case and other cases...
									var new_body_html = PtlLayoutUIEditor.getParsedHtmlFromHtmlSource(body_html);
									html = PtlLayoutUIEditor.replaceTagContentFromSource(html, "body", new_body_html);
									//console.log(html);
								}
							}
							else if (html && non_standard_code) { //This must execute first than the head html parser (this is, the code below), otherwise we are parsing the script_code too
								html = PtlLayoutUIEditor.getParsedHtmlFromHtmlSource(html);
							}
							//console.log(html);
							
							//prepare head html by adding a script code that avoids showing errors, before anything runs in the html. Note that although the edit_simple_template_layout.js already contains the window.onerror already defined, it will only be loaded after some html runs first, and if this html contains javascript errors, they won't be cached by the edit_simple_template_layout.js. So we need to add the following lines of code to run before anything.
							var script_code = '<script class="layout-ui-editor-reserved">'
								+ 'window.onerror = function(msg, url, line, col, error) {'
									+ 'if (window.parent && window.parent.$)'
									+ '	window.parent.$(".template_loaded_with_errors").removeClass("hidden");' //show an alert message saying that the html may not be loaded correctly and this files should be edited via code Layout.
									+ ''
									+ 'if (console && console.log)'
										+ 'console.log("[edit_page_and_template.js:reloadLayoutIframeFromSettings()] Layout Iframe error:" + "\\n- message: " + msg + "\\n- line " + line + "\\n- column " + col + "\\n- url: " + url + "\\n- error: " + error);'
									+ 'return true;' //return true, avoids the error to be shown and other scripts to stop.
								+ '};'
							+ '</script>';
							 
							if (exists_head) {
								var head_html = PtlLayoutUIEditor.getTagContentFromSource(html, "head");
								
								//if not exists yet, just incase someone removed these javascript from the edit_simple_template_layout.php
								if (head_html.indexOf("window.onerror") == -1) {
									head_html = script_code + head_html;
									html = PtlLayoutUIEditor.replaceTagContentFromSource(html, "head", head_html);
								}
							}
							else if (html && non_standard_code && html.indexOf("window.onerror") == -1)
								html = script_code + html;
						}
						
						var doc = iframe[0].contentDocument ? iframe[0].contentDocument : iframe[0].contentWindow.document;
						doc.open();
						doc.write(html);
						doc.close();
					}
					catch(e) {
						if (iframe_on_load_update_settings_layout_handler)
							iframe_on_load_update_settings_layout_handler();
						
						if (iframe_on_load_hide_popup_handler)
							iframe_on_load_hide_popup_handler();
						
						if (console && console.log) 
							console.log(e);
					}
				}
			},
			error: function (jqXHR, textStatus, errorThrown) {
				if (entity_or_template_obj_inited)
					MyFancyPopup.hideLoading();
				
				var msg = "Couldn't preview template. Error in reloadLayoutIframeFromSettings function. Please try again...";
				alert(msg);
				
				if (jqXHR.responseText)
					StatusMessageHandler.showError(msg + "\n" + jqXHR.responseText);
			},
		});
	}
	catch(e) {
		if (entity_or_template_obj_inited)
			MyFancyPopup.hideLoading();
		
		if (console && console.log) 
			console.log(e);
	}
}

function getTemplateHtmlTagContent(html, tag_name) {
	var html_lower = html.toLowerCase();
	var pos = html_lower.indexOf("<" + tag_name);
	var tag_html = MyHtmlBeautify.getTagContent(html, pos, tag_name); //This returns the content with the tag, this is, the outer-html
	//console.log("getTagContentFromSource");
	//console.log(tag_html);
	
	return tag_html ? tag_html[0].replace(/^\s+/, "").replace(/\s+$/, "").replace(/\n\t\t/g, "\n") : "";  //remove end-lines, trim
}

function getTemplateHtmlTagAttributes(html, tag_name) {
	var tag_name = "<" + tag_name + " ";
	var pos = html.indexOf(tag_name);
	
	if (pos != -1) {
		var tag_html = MyHtmlBeautify.getAttributesContent(html, pos + tag_name.length, ">");
		
		if (tag_html)
			return $('<div ' + tag_html[0] + '></div>')[0].attributes;
	}
	
	return null;
}

function resizeAllLabels() {
	resizeElementLabels( $(".regions_blocks_includes_settings .region_blocks"), ".template_region_items .template_region_item");
	resizeElementLabels( $(".regions_blocks_includes_settings .other_region_blocks"), ".template_region_items .template_region_item");
	resizeElementLabels( $(".regions_blocks_includes_settings .template_params"), ".items .item");
	resizeElementLabels( $(".regions_blocks_includes_settings .other_template_params"), ".items .item");
}

function resizeElementLabels(main_obj, items_selector) {
	var items = main_obj.find(items_selector);
	var labels = items.children("label");
	
	var length = 0;
	for (var i = 0; i < labels.length; i++) {
		var l = $(labels[i]).text().length;
		length = length > l ? length : l;
	}
	
	var label_width = $(labels[0]).width();
	var new_label_width = parseInt(length * 7.5);
	
	labels.css("width", new_label_width + "px");
	
	resizeRegionBlockParamsLabels( items.children(".block_params") );
}

function resizeRegionBlockParamsLabels(block_params_elm) {
	var labels = block_params_elm.find(".block_param label");
	
	var length = 0;
	for (var i = 0; i < labels.length; i++) {
		var l = $(labels[i]).text().length;
		length = length > l ? length : l;
	}
	
	var label_width = $(labels[0]).width();
	var new_label_width = parseInt(length * 7.5);
	
	labels.css("width", new_label_width + "px");
}

/* VIEW REGION INFO */

function openTemplateRegionInfoPopup(elm) {
	if (!template_region_info_url) {
		alert("There is any url to open popup with the template region info. Please talk with the system administrator.");
		return;
	}
	
	elm = $(elm);
	var p = elm.parent();
	var region = p.children(".region").length > 0 ? p.children(".region").val() : p.closest(".template_region").attr("region");
	var region_type = getArgumentType(region);
	region = region_type == "string" ? region.replace(/"/g, "") : region;
	
	var template = $(".entity_obj .template > select[name=template]").val(); //this only applies to the edit_entity_simple.php. the edit_template_simple.php already contains the template in the url. Do not add p.parent() here otherwise when this function gets call from the simple_template_layout.php iframe will not work.
	var template_genre = $(".entity_obj .template > select[name=template_genre]").val();
	
	if (template_genre)
		template = "";
	else
		template = template ? template : (typeof layer_default_template != "undefined" ? layer_default_template : "");
	
	if (template) {
		var url = template_region_info_url.replace("#template#", template).replace("#region#", region);
		url += (url.indexOf("?") == -1 ? "?" : "&") + "popup=1";
		
		//get popup
		var popup = $("body > .template_region_info_popup");
		
		if (!popup[0]) {
			popup = $('<div class="myfancypopup with_iframe_title template_region_info_popup"></div>');
			$(document.body).append(popup);
		}
		
		popup.html('<iframe></iframe>'); //cleans the iframe so we don't see the previous html
		
		//prepare popup iframe
		var iframe = popup.children("iframe");
		iframe.attr("src", url);
		
		//open popup
		MyFancyPopup.init({
			elementToShow: popup,
			parentElement: document,
		});
		
		MyFancyPopup.showPopup();
	}
	else
		StatusMessageHandler.showMessage("No info"); //this only happens if the template_genre is external
}

/* TAB CONTENT TEMPLATE LAYOUT */

function getContentTemplateLayoutIframe(main_elm) {
	if (main_elm)
		return main_elm.find(".code_layout_ui_editor > .layout-ui-editor > .template-widgets > iframe").first();
	else
		return $(".code_layout_ui_editor > .layout-ui-editor > .template-widgets > iframe").first();
}

function initPageAndTemplateLayout(main_parent_elm, opts) {
	opts = opts ? opts : {};
	
	if (opts.save_func)
		regions_blocks_html_editor_save_func = opts.save_func;
	
	//init trees
	choosePropertyVariableFromFileManagerTree = new MyTree({
		multiple_selection : false,
		toggle_children_on_click : true,
		ajax_callback_before : prepareLayerNodes1,
		ajax_callback_after : removeObjectPropertiesAndMethodsAndFunctionsFromTreeForVariables,
	});
	choosePropertyVariableFromFileManagerTree.init("choose_property_variable_from_file_manager .class_prop_var");
	
	chooseMethodFromFileManagerTree = new MyTree({
		multiple_selection : false,
		toggle_children_on_click : true,
		ajax_callback_before : prepareLayerNodes1,
		ajax_callback_after : removeObjectPropertiesAndMethodsAndFunctionsFromTreeForMethods,
	});
	chooseMethodFromFileManagerTree.init("choose_method_from_file_manager");
	
	chooseFunctionFromFileManagerTree = new MyTree({
		multiple_selection : false,
		toggle_children_on_click : true,
		ajax_callback_before : prepareLayerNodes1,
		ajax_callback_after : removeObjectPropertiesAndMethodsAndFunctionsFromTreeForFunctions,
	});
	chooseFunctionFromFileManagerTree.init("choose_function_from_file_manager");
	
	chooseFileFromFileManagerTree = new MyTree({
		multiple_selection : false,
		toggle_children_on_click : true,
		ajax_callback_before : prepareLayerNodes1,
		ajax_callback_after : removeObjectPropertiesAndMethodsAndFunctionsFromTree,
	});
	chooseFileFromFileManagerTree.init("choose_file_from_file_manager");
	
	chooseFolderFromFileManagerTree = new MyTree({
		multiple_selection : false,
		toggle_children_on_click : true,
		ajax_callback_before : prepareLayerNodes1,
		ajax_callback_after : removeAllThatIsNotFoldersFromTree,
	});
	chooseFolderFromFileManagerTree.init("choose_folder_from_file_manager");
	
	choosePresentationFromFileManagerTree = new MyTree({
		multiple_selection : false,
		toggle_children_on_click : true,
		ajax_callback_before : prepareLayerNodes1,
		ajax_callback_after : removeAllThatIsNotPresentationPagesFromTree,
	});
	choosePresentationFromFileManagerTree.init("choose_presentation_from_file_manager");
	
	choosePageUrlFromFileManagerTree = new MyTree({
		multiple_selection : false,
		toggle_children_on_click : true,
		ajax_callback_before : prepareLayerNodes1,
		ajax_callback_after : removeAllThatIsNotPagesFromTree,
	});
	choosePageUrlFromFileManagerTree.init("choose_page_url_from_file_manager");
	
	chooseImageUrlFromFileManagerTree = new MyTree({
		multiple_selection : false,
		toggle_children_on_click : true,
		ajax_callback_before : prepareLayerNodes1,
		ajax_callback_after : removeAllThatIsNotAPossibleImageFromTree,
	});
	chooseImageUrlFromFileManagerTree.init("choose_image_url_from_file_manager");
	
	chooseWebrootFileUrlFromFileManagerTree = new MyTree({
		multiple_selection : false,
		toggle_children_on_click : true,
		ajax_callback_before : prepareLayerNodes1,
		ajax_callback_after : removeAllThatIsNotWebrootFileFromTree,
	});
	chooseWebrootFileUrlFromFileManagerTree.init("choose_webroot_file_url_from_file_manager");
	
	createChoosePresentationIncludeFromFileManagerTree();
	
	//disable auto_convert_settings_from_layout
	disableAutoConvertSettingsFromLayout();
	
	//prepare regions_blocks_includes_settings
	var regions_blocks_includes_settings = main_parent_elm.find(".regions_blocks_includes_settings");
	var regions_blocks_items = regions_blocks_includes_settings.find(".region_blocks, .other_region_blocks");
	
	prepareRegionsBlocksHtmlValue(regions_blocks_items);
	pretifyRegionsBlocksComboBox(regions_blocks_items);
	createRegionsBlocksHtmlEditor(regions_blocks_items);
	onLoadRegionBlocksJoinPoints(regions_blocks_items);
	onLoadRegionBlocksParams(regions_blocks_items);
	
	//resizeAllLabels();
	
	//set resizable settings
	var iframe = getContentTemplateLayoutIframe(main_parent_elm);
	var settings_overlay = main_parent_elm.find(".regions_blocks_includes_settings_overlay");
	var settings_header = regions_blocks_includes_settings.children(".settings_header");
	
	regions_blocks_includes_settings.draggable({
		axis: "y",
		appendTo: 'body',
		cursor: 'move',
          tolerance: 'pointer',
          handle: ' > .settings_header',
          cancel: '.icon', //this means that is inside of .settings_header
		start: function(event, ui) {
			iframe.css("visibility", "hidden");
			settings_overlay.show();
			regions_blocks_includes_settings.addClass("resizing").removeClass("collapsed");
			settings_header.find(".icon").addClass("minimize").removeClass("maximize");
			return true;
		},
		drag: function(event, ui) {
			var h = $(window).height() - (ui.offset.top - $(window).scrollTop());
			
			regions_blocks_includes_settings.css({
				height: h + "px",
				top: "", 
				left: "", 
				bottom: ""
			});
		},
		stop: function(event, ui) {
			var top = parseInt(ui.helper.css("top"));//Do not use ui.offset.top bc if the window has scrollbar, it will get the wrong top for the calculations inside of resizeSettingsPanel
			resizeSettingsPanel(main_parent_elm, top);
		},
	});
	
	//prepare last func to be executed
	var load_available_block_list_finished = false;
	var init_code_layout_ui_editor_finished = false;
	
	var last_func_to_be_executed = function() {
		//console.log("load_available_block_list_finished:"+load_available_block_list_finished);
		//console.log("init_code_layout_ui_editor_finished:"+init_code_layout_ui_editor_finished);
		
		if (load_available_block_list_finished && init_code_layout_ui_editor_finished) {
			var func = function() {
				main_parent_elm.removeClass("inactive");
				
				//call ready callback
				if (typeof opts.ready_func == "function") //must be inside here bc of the update_settings_from_layout_iframe_func and update_layout_iframe_from_settings_func vars, otherwise the loadAvailableBlocksList will trigger some select.change events that will trigger this functions
					opts.ready_func();
			};
			var is_finished = (typeof page_blocks_join_points_htmls_loading != "object" || $.isEmptyObject(page_blocks_join_points_htmls_loading)) && (typeof project_blocks_params_loading != "object" || $.isEmptyObject(project_blocks_params_loading));
			//console.log(page_blocks_join_points_htmls_loading);
			//console.log(project_blocks_params_loading);
			
			if (is_finished)
				func();
			else
				setTimeout(function() {
					last_func_to_be_executed();
				}, 700);
		}
	};
	
	//load available blocks list
	loadAvailableBlocksList(regions_blocks_includes_settings, {
		"on_pretify_handler" : function(select_elm) {
			select_elm.parent().children(".invisible").removeClass("invisible");
		},
		"on_load_available_blocks_handler" : function() {
			load_available_block_list_finished = true;
			
			last_func_to_be_executed(); //only executes last_func_to_be_executed, if load_available_block_list_finished && init_code_layout_ui_editor_finished are true
		},
	});
	
	//init ui layout editor
	var code_layout_ui_editor = main_parent_elm.find(".code_layout_ui_editor");
	
	initCodeLayoutUIEditor(code_layout_ui_editor, {
		save_func: opts.save_func, 
		beautify: false,
		ready_func: function() {
			//console.log("initCodeLayoutUIEditor ready_func");
			
			var luie = code_layout_ui_editor.children(".layout-ui-editor");
			var PtlLayoutUIEditor = luie.data("LayoutUIEditor");
			
			//set on_drag_stop_func event to set the position absolute to the elements that are dropped to body
			PtlLayoutUIEditor.options.on_drag_stop_func = function(menu_widget, widget, event, ui_obj) {
				if (widget.parent().is(".template_region_items.main-droppable") && widget.parent().parent().closest(".template_region").is(".full_body") && widget.data("absolute-position")) {
					var parent_offset = PtlLayoutUIEditor.getTemplateWidgetsIframe().offset();
					var dragged_elm_offset = ui_obj ? ui_obj.offset : ui_obj.helper.offset();
					var top = parseInt(dragged_elm_offset.top - parent_offset.top);
					var left = parseInt(dragged_elm_offset.left - parent_offset.left);
					
					widget.css({position: "absolute", top: top + "px", left: left + "px"});
				}
			};
			
			//show view layout panel instead of code
			var view_layout = luie.find(" > .tabs > .view-layout");
			var view_source = luie.find(" > .tabs > .view-source");
			view_layout.addClass("do-not-confirm");
			view_source.removeClass("is-source-changed");
			view_layout.trigger("click");
			view_layout.removeClass("do-not-confirm");
			view_source.addClass("is-source-changed");
			
			addCodeLayoutUIEditorAvailableToggle("with-advanced-options", function(PtlLayoutUIEditorObj, value) {
				if (value == 1)
					toggleAdvancedOptions();
			});
			prepareCodeLayoutUIEditorUserDefinedToggles(PtlLayoutUIEditor);
			
			//prepare iframe
			var iframe_unload_func = function () {
				main_parent_elm.addClass("inactive");
			};
			iframe.load(function() {
				//console.log("iframe load");
				//try{throw new Error("as");}catch(e){console.log(e);}
				
				$(iframe[0].contentWindow).unload(iframe_unload_func);
				
				main_parent_elm.removeClass("inactive");
				
				//set beautify to defaults
				PtlLayoutUIEditor.options.beautify = true;
			});
			$(iframe[0].contentWindow).unload(iframe_unload_func);
			
			var iframe_on_load_func = function() {
				//console.log("iframe_on_load_func - only one time!");
				iframe.unbind("load", iframe_on_load_func);
				
				init_code_layout_ui_editor_finished = true;
				
				last_func_to_be_executed(); //only executes last_func_to_be_executed, if load_available_block_list_finished && init_code_layout_ui_editor_finished are true
			};
			iframe.load(iframe_on_load_func);
			
			//load iframe with right layout
			//console.log("reloadLayoutIframeFromSettings");
			reloadLayoutIframeFromSettings(iframe, edit_simple_template_layout_data);
		},
	});
}

function initPageAndTemplateLayoutSLA(regions_blocks_includes_settings) {
	var resource_settings = regions_blocks_includes_settings.children(".resource_settings");
	initSLA(resource_settings);
	
	var sla = resource_settings.children(".sla");
	var workflow_menu_ul = sla.find(" > #ui > .taskflowchart").addClass("with_top_bar_menu fixed_properties").children(".workflow_menu").addClass("top_bar_menu").children("ul");
	workflow_menu_ul.children("li.save, li.auto_save_activation, li.auto_convert_activation, li.tasks_flow_full_screen").remove();
	workflow_menu_ul.children("li").last().filter(".separator").remove();
	
	//overwrite the removeGroupItem function to check if the resource is being used in the html
	changeRemoveSLAGroupItemFunctionToCheckUsedResources();
	
	//initSLA changes the ProgrammingTaskUtil.on_programming_task_choose_created_variable_callback, so we need to update it here too.
	var PtlLayoutUIEditor = $(".code_layout_ui_editor > .layout-ui-editor").data("LayoutUIEditor");
	
	if (PtlLayoutUIEditor)
		PtlLayoutUIEditor.options.on_choose_variable_func = ProgrammingTaskUtil.on_programming_task_choose_created_variable_callback; 
}

function changeRemoveSLAGroupItemFunctionToCheckUsedResources() {
	//overwrite the removeGroupItem function to check if the resource is being used in the html
	window.old_removeGroupItem = removeGroupItem;

	window.removeGroupItem = function(elm, do_not_confirm) {
		var status = true;
		
		if (!do_not_confirm) {
			var PtlLayoutUIEditor = $(".code_layout_ui_editor > .layout-ui-editor").data("LayoutUIEditor");
			
			if (PtlLayoutUIEditor) {
				var item = $(elm).parent().closest(".sla_group_item");
				var resource_name = item.find(" > .sla_group_header > .result_var_name").val();
				
				if (resource_name) {
					var filtered_resources_name = PtlLayoutUIEditor.LayoutUIEditorWidgetResource.filterResourcesIfNotUsedAnymore(resource_name);
					
					//if filtered_resources_name.length == 0, it means that the resource_name is being by some widget
					if (filtered_resources_name.length == 0) {
						if (confirm("This resource is being used in the html. By removing it, the html may not work as expected. Do you still wish to remove it?"))
							do_not_confirm = true;
						else
							status = false;
					}
				}
			}
		}
		
		if (status)
			window.old_removeGroupItem(elm, do_not_confirm);
	}
}

function loadPageAndTemplateLayoutSLASettings(regions_blocks_includes_settings, asynchronous) {
	//console.log(assignObjectRecursively({}, sla_settings_obj));
	var actions_obj = {
		actions: {
			key: "actions",
			key_type: "string",
			items: sla_settings_obj
		}
	};
	var original_actions_obj = assignObjectRecursively({}, actions_obj); //Note that the convertSettingsToTasksValues changes the actions_obj so we need to call the assignObjectRecursively
	var original_tasks_values = convertSettingsToTasksValues(original_actions_obj, false);
	var tasks_values = convertSettingsToTasksValues(actions_obj, true);
	//console.log(tasks_values);
	
	if (tasks_values && tasks_values.hasOwnProperty("actions")) {
		var add_group_icon = regions_blocks_includes_settings.find(" > .resource_settings > .sla > .sla_groups_flow > nav > .add_sla_group");
		
		loadSLASettingsActions(add_group_icon[0], tasks_values["actions"], false, asynchronous, original_tasks_values["actions"]); //set asynchronous argument to true so it can load the slas asynchrounously. This will make the system 3 to 5 secs faster.
	}
}

function resizeSettingsPanel(main_parent_elm, top) {
	var settings = main_parent_elm.find(".regions_blocks_includes_settings");
	var settings_overlay = main_parent_elm.find(".regions_blocks_includes_settings_overlay");
	var iframe = getContentTemplateLayoutIframe(main_parent_elm);
	var icon = settings.find(" > .settings_header .icon");
	
	var wh = $(window).height();
	var top_bar_height = $(".top_bar header").outerHeight();
	var height = 0;
	
	iframe.css("visibility", "");
	settings_overlay.hide();
	settings.removeClass("resizing");
	settings.css({top: "", left: "", bottom: ""}); //remove top, left and bottom from style attribute in #settings_header
	
	if (top < top_bar_height) {
		height = wh - (top_bar_height + 5); //5 is the height of the #settings_header resize bar
		
		settings.css("height", height + "px");
		
		enableAutoConvertSettingsFromLayout();
	}
	else if (top > wh - 35) { //35 is the size of #settings .settings_header when collapsed
		icon.addClass("maximize").removeClass("minimize");
		settings.addClass("collapsed");
		
		settings.css("height", ""); //remove height from style attribute in #settings
		
		disableAutoConvertSettingsFromLayout();
	}
	else {
		settings.css("height", (wh - top) + "px");
		
		enableAutoConvertSettingsFromLayout();
	}
}

function toggleSettingsPanel(elm) {
	var settings = $(".regions_blocks_includes_settings");
	var toggle_main_settings = $(".top_bar li.toggle_main_settings");
	var input = toggle_main_settings.find("input");
	var span = toggle_main_settings.find("span");
	var icon = settings.find(" > .settings_header > .icon").filter(".maximize, .minimize");
	
	icon.toggleClass("maximize").toggleClass("minimize");
	settings.toggleClass("collapsed");
	toggle_main_settings.toggleClass("active");
	
	if (settings.hasClass("collapsed")) {
		input.removeAttr("checked").prop("checked", false);
		span.html("Show Main Settings");
		
		disableAutoConvertSettingsFromLayout();
	}
	else {
		input.attr("checked", "checked").prop("checked", true);
		span.html("Hide Main Settings");
		
		enableAutoConvertSettingsFromLayout();
	}
}

function toggleAdvancedOptions() {
	var main_obj = $(".entity_obj, .template_obj");
	var top_bar = $(".top_bar");
	var toggle_advanced_options = top_bar.find("li.toggle_advanced_options");
	var input = toggle_advanced_options.find("input");
	var span = toggle_advanced_options.find("span");
	
	toggle_advanced_options.toggleClass("active");
	main_obj.toggleClass("with_advanced_options");
	top_bar.toggleClass("with_advanced_options");
	
	var is_active = toggle_advanced_options.hasClass("active");
	
	if (is_active) {
		input.attr("checked", "checked").prop("checked", true);
		span.html("Hide Advanced Features");
	}
	else {
		input.removeAttr("checked").prop("checked", false);
		span.html("Show Advanced Features");
	}
	
	saveCodeLayoutUIEditorUserDefinedToggle("with-advanced-options", is_active);
}

function enableAutoConvertSettingsFromLayout(on_complete) {
	auto_convert_settings_from_layout = true;
	
	//check if there is any change to be converted
	if (settings_need_to_be_converted_from_layout)
		updateSettingsFromLayoutIframeField(on_complete);
	else if (typeof on_complete == "function")
		on_complete();
}

function disableAutoConvertSettingsFromLayout() {
	auto_convert_settings_from_layout = false;
}

function disableLinksAndButtonClickEvent(parent_elm) {
	$(parent_elm).find("a, button, input[type=submit], input[type=button]").each(function(idx, elm) {
		elm = $(elm);
		//console.log(elm[0]);
		
		//only disable click events for buttons or "a" elements that have urls in the href attribute. This avoid disabling the cases where we have "a" elements in menus to open sub_menus
		if (!elm.is("a") || !elm[0].hasAttribute("href") || elm.attr("href").toLowerCase().indexOf("javascript:") !== 0) 
			elm.bind("click", function(e) {
				e.preventDefault ? e.preventDefault() : e.returnValue = false;
				e.stopPropagation();
				
				//alert("Action temporary disabled, because this is a template demo to choose the region-blocks.");
				return false;
			});
	});
}

function initIframeModulesBlocksToolbarDraggableMenuItem(menu_item) {
	menu_item.addClass("draggable_menu_item");
	var menu_item_icon = menu_item.find(" > a > i");
	
	if (menu_item_icon.hasClass("block_file"))
		menu_item.addClass("draggable_menu_item_block");
	else if (menu_item_icon.is(".table"))
		menu_item.addClass("draggable_menu_item_table");
	else
		menu_item.addClass("draggable_menu_item_module");
	
	/*menu_item.draggable({
		iframeFix: true,
		iframeScroll:true,
		scroll: true,
		scrollSensitivity:20,
		
		opacity: 0.7,
		helper: "clone",
		appendTo: 'body',
		containment: "window",
		cursor: 'move',
		tolerance: 'pointer',
		revertDuration: 300,
		revert: true,
		
		helper: function(event) {
			var label = menu_item.find(" > a > label").text();
			var helper = $('<div class="dragging_menu_item">' + label + '</div>');
			helper.css("width", menu_item.css("width"));
			return helper;
		},
		start: function(event, ui) {
			closeModuleInfo();
		},
	});*/
}

function getMenuItemModuleId(menu_item) {
	var path = menu_item.children("a").attr("folder_path");
	var pos = path.indexOf("/src/module/");
	var module_id = path.substr(pos + "/src/module/".length);
	module_id = module_id.substr(module_id.length - 1) == "/" ? module_id.substr(0, module_id.length - 1) : module_id;
	
	return module_id;
}

/* DESIGN SETTINGS PANEL */

function loadCodeEditorLayoutJSAndCSSFilesToSettings(opts) {
	var head_code = getTemplateHeadEditorCode(); //The getTemplateHeadEditorCode function needs to be defined in the js files that call this method, bc it doesn't exists here.
	var body_code = getTemplateBodyEditorCode(); //The getTemplateBodyEditorCode function needs to be defined in the js files that call this method, bc it doesn't exists here.
	var code = "<html><head>" + head_code + "</head><body>" + body_code + "</body></html>";
	
	//return css and js files
	var js_and_css_files = getCodeJSAndCSSFiles(code);
	var css_files = js_and_css_files["css_files"];
	var js_files = js_and_css_files["js_files"];
	var regions_blocks_includes_settings = $(".regions_blocks_includes_settings");
	
	//prepare opts with head and body code
	if (!$.isPlainObject(opts))
		opts = {};
	
	opts["head_code"] = head_code;
	opts["body_code"] = body_code;
	
	updateCodeEditorLayoutFilesInSettings(css_files, regions_blocks_includes_settings.find(".css_files > ul"), opts);
	updateCodeEditorLayoutFilesInSettings(js_files, regions_blocks_includes_settings.find(".js_files > ul"), opts);

}

function getCurrentCodeJSAndCSSFiles() {
	var head_code = getTemplateHeadEditorCode(); //The getTemplateHeadEditorCode function needs to be defined in the js files that call this method, bc it doesn't exists here.
	var body_code = getTemplateBodyEditorCode(); //The getTemplateBodyEditorCode function needs to be defined in the js files that call this method, bc it doesn't exists here.
	
	var code = "<html><head>" + head_code + "</head><body>" + body_code + "</body></html>";
	
	//return css and js files
	return getCodeJSAndCSSFiles(code);
}

//remove the inner html from a specific tag
function stripHtmlTagCode(tag_name, html) {
	var html_lower = html.toLowerCase();
	var pos = 0;
	
	tag_name = tag_name.toLowerCase();
	
	do {
		var start_pos = html_lower.indexOf("<" + tag_name, pos);
		
		if (start_pos != -1) {
			var tag_code = MyHtmlBeautify.getTagContent(html, start_pos, tag_name);
			var start_tag_settings = MyHtmlBeautify.getTagHtml(html, start_pos);
			var is_short_tag = start_tag_settings[0].substr(start_tag_settings[0].length - 2) == "/>";
			
			start_pos = start_tag_settings[1] + 1;
			
			if (is_short_tag)
				pos = start_pos;
			else {
				var end_pos = html_lower.indexOf("</" + tag_name, start_pos + (tag_code && tag_code[0] ? tag_code[0].length : 0));
				
				if (end_pos != -1) {
					var end_tag_settings = MyHtmlBeautify.getTagHtml(html, end_pos);
					end_pos = (end_tag_settings[1] + 1) - end_tag_settings[0].length;
					pos = start_pos + end_tag_settings[0].length;
				}
				else {
					end_pos = html.length;
					pos = end_pos;
				}
				
				html = html.substr(0, start_pos) + html.substr(end_pos);
				html_lower = html_lower.substr(0, start_pos) + html_lower.substr(end_pos);
			}
		}
	}
	while(start_pos != -1);
	
	return html;
}

function getCodeJSAndCSSFiles(code) {
	var css_files = [];
	var js_files = [];
	
	//strip script/style/textarea content, just it may contain css or js code inside. bc in this case we should ignore it
	code = stripHtmlTagCode("script", code);
	code = stripHtmlTagCode("style", code);
	code = stripHtmlTagCode("textarea", code);
	//console.log("code without script|style|textarea content:"+code);
	
	//find css and js code
	var regex = /<(script|link)\s+/gi;
	var m = regex.exec(code);
	
	while (m != null) {
		var tag_start = m.index;
		var tag_html = MyHtmlBeautify.getTagHtml(code, tag_start, "");
		var tag_code = tag_html[0];
		var tag_end = tag_html[1];
		
		/*console.log(m);
		console.log("tag_code:"+tag_code);
		console.log("tag_start:"+tag_start);
		console.log("tag_end:"+tag_end);*/
		
		//parse tag_code, ignoring the layout-ui-editor-reserved
		if (tag_code && tag_code.indexOf("layout-ui-editor-reserved") == -1) {
			var is_script = m[1].toLowerCase() == "script";
			var sub_regex = new RegExp("\\s+(" + (is_script ? "src" : "href") + ")\\s*=\\s*(\"|'|)", "gi");
			var sub_m = sub_regex.exec(tag_code);
			
			if (sub_m != null) {
				var attr_start = sub_m.index + sub_m[0].length;
				var attr = MyHtmlBeautify.getAttribute(tag_code, attr_start, sub_m[2]);
				var attr_html = attr[0];
				var attr_end = attr[1];
				//console.log("tag_code:"+tag_code);
				//console.log("attr_html:"+attr_html);
				
				if (attr_html) {
					if (is_script)
						js_files.push(attr_html);
					else
						css_files.push(attr_html);
				}
			}
		}
		
		m = regex.exec(code);
	}
	
	return {"css_files": css_files, "js_files": js_files};
}

function updateCodeEditorLayoutFilesInSettings(files, ul, opts) {
	//console.log(files);
	var empty_files_li = ul.children(".empty_files");
	
	//remove old files
	ul.children("li:not(.empty_files)").remove()
	
	//prepare new files
	if (files.length > 0) {
		empty_files_li.hide();
		
		for (var i = 0; i < files.length; i++)
			addCodeEditorLayoutFileInSettings(files[i], ul, opts);
	}
	else
		empty_files_li.show();
}

function addCodeEditorLayoutFileInSettings(file, ul, opts) {
	var inline_html = opts && opts["inline_html"];
	
	//prepare file if inline html
	var parsed_file = file;
	var file_name = file;
	
	if (inline_html) {
		var m = /(\{\$|\$\{)(project_url_prefix|original_project_url_prefix|project_common_url_prefix|original_project_common_url_prefix)\}?/.exec(file);
		
		if (!m)
			m = /(\$)(project_url_prefix|original_project_url_prefix|project_common_url_prefix|original_project_common_url_prefix)[^a-z0-9_]?/.exec(file);
		
		if (m) {
			var var_code = m[0];
			var var_name = m[2];
			
			parsed_file = file.replace(var_code, '<? echo \$' + var_name + ';?>');
		}
		
		//if edit_entity, replace project_url_prefix with real url
		file_name = file_name.replace(/(\$\{project_url_prefix\}|\$\{original_project_url_prefix\}|\{\$project_url_prefix\}|\{\$original_project_url_prefix\}|\$project_url_prefix\/|\$original_project_url_prefix\/)/g, selected_project_url_prefix).replace(/(\$\{project_common_url_prefix\}|\$\{original_project_common_url_prefix\}|\{\$project_common_url_prefix\}|\{\$original_project_common_url_prefix\}|\$project_common_url_prefix\/|\$original_project_common_url_prefix\/)/g, selected_project_common_url_prefix);
	}
	
	var li_id = $.md5(file_name); //md5 of file_name which is already converted with the right url prefix.
	var li = ul.children("li[data_file_id='" + li_id + "']");
	var force = opts && opts["force"];
	
	if (li[0] && !force) 
		return li;
	
	//prepare non https or http urls
	var selected_project_url_prefix_aux = selected_project_url_prefix.match(/^http:/) ? selected_project_url_prefix.replace(/^http:/, "https:") : selected_project_url_prefix.replace(/^https:/, "http:");
	var selected_project_common_url_prefix_aux = selected_project_common_url_prefix.match(/^http:/) ? selected_project_common_url_prefix.replace(/^http:/, "https:") : selected_project_common_url_prefix.replace(/^https:/, "http:");
	
	//prepare file path
	var path = null;
	
	if (parsed_file.indexOf(selected_project_url_prefix) === 0) //checks if file starts with url in vars: selected_project_url_prefix
		path = selected_project_id + "/webroot/" + parsed_file.substr(selected_project_url_prefix.length);
	else if (parsed_file.indexOf(selected_project_common_url_prefix) === 0) //checks if file starts with url in vars: selected_project_common_url_prefix
		path = common_project_name + "/webroot/" + parsed_file.substr(selected_project_common_url_prefix.length);
	else if (parsed_file.indexOf(selected_project_url_prefix_aux) === 0) //checks if file starts with url in vars: selected_project_url_prefix_aux
		path = selected_project_id + "/webroot/" + parsed_file.substr(selected_project_url_prefix_aux.length);
	else if (parsed_file.indexOf(selected_project_common_url_prefix_aux) === 0) //checks if file starts with url in vars: selected_project_common_url_prefix_aux
		path = common_project_name + "/webroot/" + parsed_file.substr(selected_project_common_url_prefix_aux.length);
	else { //checks if file starts with php code: $project_url_prefix or $project_common_url_prefix
		var m = /^<\?(|=|php)\s*(|echo|print)\s*(\$[a-z_]+)\s*;?\s*\?>/g.exec(parsed_file);
		
		if (m && (m[3] == "$project_url_prefix" || m[3] == "$original_project_url_prefix" || m[3] == "$project_common_url_prefix"/* || m[3] == "$original_project_common_url_prefix"*/)) { 
			var relative_file = parsed_file.substr(m[0].length);
			
			path = (m[3] == "$project_url_prefix" || m[3] == "$original_project_url_prefix" ? selected_project_id : common_project_name) + "/webroot/" + relative_file;
		}
	}
	
	file_name = file_name.replace(/</g, "&lt;").replace(/>/g, "&gt;");
	
	if (path)
		file_name = '<a href="javascript:void(0)" onClick="editWebrootFile(\'' + path + '\')" title="Click to edit file">' + file_name + '</a>';
	
	var html = '<li data_file_id="' + li_id + '">' + file_name;
	
	if (opts && opts["remove"] && typeof opts["remove"] == "string")
		html += '<span class="icon delete" onClick="' + opts["remove"] + '(this, \'' + file + '\')" title="Remove this file">Remove</span>';
	
	html += '</li>';
	
	var new_li = $(html);
	
	if (opts && opts["remove"] && typeof opts["remove"] == "function") {
		var remove_icon = $('<span class="icon delete" title="Remove this file">Remove</span>');
		remove_icon.on("click", function() {
			opts["remove"](this, file);
		});
		new_li.append(remove_icon);
	}
	
	if (force && li[0]) {
		li.before(new_li);
		li.remove();
	}
	else
		ul.append(new_li);
	
	if (opts && opts["create"] && typeof opts["create"] == "function")
		opts["create"](file, new_li, opts);
	
	return new_li;
}

function appendRegionsBlocksHtmlFileInSettings(filter_by_region_name, opts) {
	var regions_blocks_includes_settings = $(".regions_blocks_includes_settings");
	var data = getSettingsTemplateRegionsBlocks(regions_blocks_includes_settings);
	var template_regions = data["template_regions"];
	var regions_code = "";
	
	filter_by_region_name = filter_by_region_name ? filter_by_region_name.replace(/"/g, "").toLowerCase() : "";
	
	if (template_regions)
		for (var region in template_regions) {
			var regions = template_regions[region];
			var r = region.substr(0, 1) == '"' ? region.replace(/"/g, "") : region;
			
			if ((!filter_by_region_name || r.toLowerCase() == filter_by_region_name) && $.isArray(regions)) {
				for (var i = 0, t = regions.length; i < t; i++) {
					var item = regions[i];
					var block_html = item[1];
					var type = item[3];
					var is_html = type == 1;
					
					if (is_html && block_html)
						regions_code += block_html;
				}
			}
		}
	
	if (regions_code) {
		var js_and_css_files = getCodeJSAndCSSFiles(regions_code);
		var css_files = js_and_css_files["css_files"];
		var js_files = js_and_css_files["js_files"];
		
		if (!$.isPlainObject(opts))
			opts = {};
		
		opts["inline_html"] = true;
		
		if (css_files)
			for (var i = 0, t = css_files.length; i < t; i++)
				addCodeEditorLayoutFileInSettings(css_files[i], regions_blocks_includes_settings.find(".css_files > ul"), opts);
		
		if (js_files)
			for (var i = 0, t = js_files.length; i < t; i++)
				addCodeEditorLayoutFileInSettings(js_files[i], regions_blocks_includes_settings.find(".js_files > ul"), opts);
	}
}

function removeFileFromCode(code, file) {
	var regex = /<(script|link)\s+/gi;
	var m = regex.exec(code);
	
	while (m != null) {
		var tag_start = m.index;
		var tag_html = MyHtmlBeautify.getTagHtml(code, tag_start, "");
		var tag_code = tag_html[0];
		var tag_end = tag_html[1];
		
		/*console.log(m);
		console.log("tag_code:"+tag_code);
		console.log("tag_start:"+tag_start);
		console.log("tag_end:"+tag_end);*/
		
		//parse tag_code, ignoring the layout-ui-editor-reserved
		if (tag_code && tag_code.indexOf("layout-ui-editor-reserved") == -1) {
			var is_script = m[1].toLowerCase() == "script";
			var sub_regex = new RegExp("\\s+(" + (is_script ? "src" : "href") + ")\\s*=\\s*(\"|'|)", "gi");
			var sub_m = sub_regex.exec(tag_code);
			
			if (sub_m != null) {
				var attr_start = sub_m.index + sub_m[0].length;
				var attr = MyHtmlBeautify.getAttribute(tag_code, attr_start, sub_m[2]);
				var attr_html = attr[0];
				var attr_end = attr[1];
				//console.log("tag_code:"+tag_code);
				//console.log("attr_html:"+attr_html);
				
				if (attr_html && attr_html == file) {
					if (is_script) {
						var tag_content_html = MyHtmlBeautify.getNonParseInnerTagsNodeContent(code, tag_end + 1, "script");
						var tag_content_code = tag_content_html[0];
						var tag_content_pos = tag_content_html[1];
						
						var tag_end_html = MyHtmlBeautify.getTagHtml(code, tag_content_pos + 1, "");
						var tag_end_code = tag_end_html[0];
						var tag_end_pos = tag_end_html[1];
						
						tag_code += tag_content_code + tag_end_code;
					}
					
					while(code.indexOf(tag_code) != -1)
						code = code.replace(tag_code, "");
				}
			}
		}
		
		m = regex.exec(code);
	}
	
	return code;
}

function removeFileFromRegionBlockHtml(file, region_name) {
	//check if file exists in head region also.
	var regions_blocks_includes_settings = $(".regions_blocks_includes_settings");
	var items = regions_blocks_includes_settings.find(".region_blocks, .other_region_blocks").find(".template_region_items .template_region_item");
	var status = false;
	
	region_name = region_name ? region_name.replace(/"/g, "").toLowerCase() : "";
	
	//save synchronization functions
	var update_settings_from_layout_iframe_func_bkp = update_settings_from_layout_iframe_func;
	var update_layout_iframe_from_settings_func_bkp = update_layout_iframe_from_settings_func;
	var update_layout_iframe_field_html_value_from_settings_func_bkp = update_layout_iframe_field_html_value_from_settings_func;
	
	//disable synchronization functions bc the updateSelectedTemplateRegionsBlocks calls the sync func, when it triggers the on change event from the blok_type in the getRegionBlockHtml
	update_settings_from_layout_iframe_func = null;
	update_layout_iframe_from_settings_func = null;
	update_layout_iframe_field_html_value_from_settings_func = null;
	
	//remove file from all regions blocks
	$.each(items, function (idx, item) {
		var item_data = getSettingsTemplateRegionBlockValues(item);
		var region = item_data["region"];
		var block_html = item_data["block"];
		var is_html = item_data["type"] == 1;
		
		var r = region.substr(0, 1) == '"' ? region.replace(/"/g, "") : region;
		
		if ((!region_name || r.toLowerCase() == region_name) && block_html && is_html) {
			var new_block_html = removeFileFromCode(block_html, file);
			
			//update code in this region block
			if (block_html != new_block_html) {
				new_block_html = new_block_html.replace(/^\s+/g, "").replace(/\s+$/g, ""); //trim html
				
				var block_html_elm = $(item).find(".block_html");
				setRegionBlockHtmlEditorValue(block_html_elm, new_block_html);
				
				status = true;
			}
		}
	});
	
	//sets back synchronization functions
	update_settings_from_layout_iframe_func = update_settings_from_layout_iframe_func_bkp;
	update_layout_iframe_from_settings_func = update_layout_iframe_from_settings_func_bkp;
	update_layout_iframe_field_html_value_from_settings_func = update_layout_iframe_field_html_value_from_settings_func_bkp;
	
	if (status && typeof update_layout_iframe_from_settings_func == "function")
		update_layout_iframe_from_settings_func();
	
	//set click event on the Content tab (but only once), to refresh all opened and visible editors, so the editors can update the new values in the editors. This is to fix the bug, where the setRegionBlockHtmlEditorValue removes new code, and this new code doesn't appear until the user mouse click inside the editor.
	if (status)
		setOnClickEventToContentSettingsTabToUpdateRegionsBlocksHtmlEditorValue(true);
	
	return status;
}

function setOnClickEventToContentSettingsTabToUpdateRegionsBlocksHtmlEditorValue(only_once) {
	var regions_blocks_includes_settings = $(".regions_blocks_includes_settings");
	var content_settings_elm = regions_blocks_includes_settings.find(" > ul.tabs > li > a[href='#content_settings']");
	
	//only add 1 click event
	if (content_settings_elm.data("with_on_click_content_settings_tab") != 1) {
		var on_click_content_settings_tab_func = function() {
			//console.log("on_click_content_settings_tab_func");
			
			setTimeout(function() { //must be in a settimeout, otherwsie it won't refresh the new values. Basically the ace editor only shows the new values when its visible or something like this...
				updateRegionsBlocksHtmlEditorValue();
			}, 300);
			
			if (only_once) {
				content_settings_elm.unbind("click", on_click_content_settings_tab_func);
				content_settings_elm.data("with_on_click_content_settings_tab", null);
			}
		};
		content_settings_elm.bind("click", on_click_content_settings_tab_func);
		content_settings_elm.data("with_on_click_content_settings_tab", 1);
	}
}

function updateRegionsBlocksHtmlEditorValue() {
	//when click on the Content tab, all open and visible editors should be resized, so the editors can update the new values in the editors. This is to fix the bug, where the some other panel sets (add or remove) new code in some region block editor, and this new code doesn't appear until the user mouse click inside the editor.
	var regions_blocks_includes_settings = $(".regions_blocks_includes_settings");
	regions_blocks_includes_settings.find(".region_blocks, .other_region_blocks").find(" > .template_region_items > .template_region_item.is_html > .block_html.editor").each(function (idx, block_html) {
		if (block_html.editor)
			block_html.editor.resize(); //the refresh action in the ace editor is the resize method.
	});
}

/* MODULE INFO POPUP */

function showModuleInfo(elm) {
	elm = $(elm);
	var module_id = getMenuItemModuleId(elm.parent());
	
	if (!shown_module_info_id || shown_module_info_id != module_id) {
		shown_module_info_id = module_id;
		
		//open tooltip
		var module_info_tooltip = $("body > #module_info_tooltip");
		
		if (!module_info_tooltip[0]) {
			module_info_tooltip = $('<div id="module_info_tooltip"><div class="icon close"></div><div class="module_data"></div></div>');
			module_info_tooltip.children(".close").on("click", function() {
				closeModuleInfo();
			});
			$("body").append(module_info_tooltip);
		}
		
		module_info_tooltip.children(".module_data").html('<div class="loading">Loading...</div>');
		module_info_tooltip.css({
			inset: "",
			top: window.event.y,
			left: window.event.x + 10,
		})
		.show();
		
		if (loaded_modules_info.hasOwnProperty(module_id))
			updateModuleInfoData(module_info_tooltip, loaded_modules_info[module_id]);
		else
			$.ajax({
				type : "get",
				url : get_module_info_url.replace("#module_id#", module_id),
				dataType : "json",
				success : function(data, textStatus, jqXHR) {
					if (data && $.isPlainObject(data)) {
						loaded_modules_info[module_id] = data;
						updateModuleInfoData(module_info_tooltip, data);
					}
					else 
						StatusMessageHandler.showError("Error trying to get this module info. Please refresh the page to try again.");
				},
				error : function(jqXHR, textStatus, errorThrown) { 
					var msg = jqXHR.responseText ? "\n" + jqXHR.responseText : "";
					StatusMessageHandler.showError("Error trying to get this module info. Please refresh the page to try again." + msg);
				},
				complete : function() {
					elm.attr("is_info_shown", 1);
				}
			});
	}
}

function showModuleInfoWithSmartPosition(elm) {
	var is_module_info_tooltip_new = $("body > #module_info_tooltip").length > 0 ? true : false;
	
	showModuleInfo(elm);
	
	var module_info_tooltip = $("body > #module_info_tooltip");
	var popup = $(elm).parent().closest(".myfancypopup");
	var layout_ui_editor_right_container = $(elm).parent().closest(".layout_ui_editor_right_container");
	var iframe_modules_blocks_toolbar = $(elm).parent().closest(".iframe_modules_blocks_toolbar");
	var z_index = null;
	
	if (layout_ui_editor_right_container.length > 0 || iframe_modules_blocks_toolbar.length > 0) {
		var w = layout_ui_editor_right_container.length > 0 ? layout_ui_editor_right_container.width() : iframe_modules_blocks_toolbar.width();
		
		module_info_tooltip.css({
			/*top: "auto",
			left: "auto",
			bottom: $(window).height() - window.event.y,
			right: $(window).width() - window.event.x + 10,*/
			top: window.event.y - 20,
			left: "auto",
			right: w - 20,
			bottom: "auto",
		});
	}
	
	if (popup.length > 0)
		z_index = popup.css("z-index");
	else if (layout_ui_editor_right_container.length > 0)
		z_index = layout_ui_editor_right_container.css("z-index");
	
	if (z_index)
		module_info_tooltip.css("z-index", parseInt(z_index) + 10);
	
	if (is_module_info_tooltip_new)
		module_info_tooltip.children(".close").on("click", function() {
			module_info_tooltip.css("z-index", "");
		});
}

function closeModuleInfo() {
	$("body > #module_info_tooltip").hide();
	shown_module_info_id = null;
}

function updateModuleInfoData(module_info_tooltip, data) {
	if (data) {
		var html =  '<input type="hidden" name="module_id" value="' + data["id"] + '" />'
				+ '<div class="module_img">' + (data["images"] && data["images"][0] && data["images"][0]["url"] ? '<img src="' + data["images"][0]["url"] + '" />' : '<span class="no_photo">No Photo</span>') + '</div>'
				+ '<div class="module_label">' + data["label"] + '</div>'
				+ '<div class="module_description">' + data["description"].replace(/\n/g, "<br>") + '</div>';
		
		module_info_tooltip.children(".module_data").html(html);
	}
}

/* CODE TABS */

//if user was in tasks flow tab, then converts layout to source first and then tasks flow to source.
//if user was in layout tab, then converts layout to source only.
function onClickLayoutEditorUICodeTab(elm) {
	var ul = $(elm).parent().closest("ul");
	var previous_active_tab = ul.children(".ui-state-active");
	var is_tasks_flow_tab = previous_active_tab.attr("id") == "tasks_flow_tab";
	var PtlLayoutUIEditor = $(".code_layout_ui_editor .layout-ui-editor").data("LayoutUIEditor");
	var view_source_tab = null;
	
	if (PtlLayoutUIEditor) {
		var luie = PtlLayoutUIEditor.getUI();
		var tabs = luie.find(" > .tabs > .tab");
		view_source_tab = tabs.filter(".view-source").first(); //leave this code here in this position. do not move it inside of the if below, otherwise the client logic won't work properly.
		
		if (is_tasks_flow_tab) {
			var old_code_id = $("#code").attr("generated_code_id");
			onClickCodeEditorTab(elm, {"async": false}); //only update new code into source tab but not show it.
			
			var new_code_id = $("#code").attr("generated_code_id");
			
			//if source was changed, set class is-source-changed so when we click in the layout, it will convert the new code to layout.
			if (old_code_id != new_code_id)
				view_source_tab.addClass("is-source-changed");
			
			view_source_tab.addClass("do-not-convert");
		}
		else if (!auto_convert)
			view_source_tab.addClass("do-not-convert");
		
		view_source_tab.trigger("click");
		
		if (is_tasks_flow_tab || !auto_convert)
			view_source_tab.removeClass("do-not-convert");
	}
	else if (is_tasks_flow_tab)
		onClickCodeEditorTab(elm); //show and update new code into source tab
}

//if user was in tasks flow tab, then converts workflow to code first and then click in layout tab
//if user was in code tab, then click in layout tab
function onClickLayoutEditorUIVisualTab(elm) {
	var PtlLayoutUIEditor = $(".code_layout_ui_editor .layout-ui-editor").data("LayoutUIEditor");
	
	if (PtlLayoutUIEditor) {
		var ul = $(elm).parent().closest("ul");
		var previous_active_tab = ul.children(".ui-state-active");
		var is_tasks_flow_tab = previous_active_tab.attr("id") == "tasks_flow_tab";
		var luie = PtlLayoutUIEditor.getUI();
		var tabs = luie.find(" > .tabs > .tab");
		var view_source_tab = tabs.filter(".view-source").first();
		
		//user was in tasks flow tab, then converts workflow to code first and then show the source tab, so then we can convert it to the layout tab
		if (is_tasks_flow_tab) {
			var old_code_id = $("#code").attr("generated_code_id");
			onClickCodeEditorTab(elm, {"async": false}); //only update new code into source tab but not show it.
			
			var new_code_id = $("#code").attr("generated_code_id");
			
			//if source was changed, set class is-source-changed so when we click in the layout, it will convert the new code to layout.
			if (old_code_id != new_code_id) {
				view_source_tab.addClass("is-source-changed");
				
				//if shource tab is not shown and if auto_convert is active, otherwise you don't need to show the source, bc you won't convert the new code.
				if (!tabs.filter(".tab-active").is(".view-source") && auto_convert) {
					view_source_tab.addClass("do-not-convert");
					view_source_tab.trigger("click"); //show source tab so then we can convert it when we click in the layout tab.
					view_source_tab.removeClass("do-not-convert");
				}
			}
		}
		
		//click in the layout tab and convert new code if auto_convert is active
		if (!auto_convert)
			view_source_tab.addClass("do-not-convert");
		
		tabs.filter(".view-layout").first().trigger("click");
		
		if (!auto_convert)
			view_source_tab.removeClass("do-not-convert");
	}
}

//if it comes from visual tab, generates the code before it clicks in the workflow tab
function onClickLayoutEditorUITaskWorkflowTab(elm) {
	//if it comes from visual tab, generates the code befoe it clicks in the workflow tab
	if (auto_convert) {
		var ul = $(elm).parent().closest("ul");
		var previous_active_tab = ul.children(".ui-state-active");
		
		if (previous_active_tab.attr("id") == "visual_editor_tab") {
			var PtlLayoutUIEditor = $(".code_layout_ui_editor .layout-ui-editor").data("LayoutUIEditor");
			
			if (PtlLayoutUIEditor) {
				//convert visual to code
				PtlLayoutUIEditor.convertTemplateLayoutToSource(); //don't ask confirmation message
			}
		}
	}
	
	onClickTaskWorkflowTab(elm);
}

/* CODE LAYOUT UI EDITOR */

function initCodeLayoutUIEditor(main_obj, opts) {
	chooseCodeLayoutUIEditorModuleBlockFromFileManagerTree = new MyTree({
		multiple_selection : false,
		toggle_children_on_click : true,
		ajax_callback_before : prepareLayerNodes1,
		ajax_callback_after : function(ul, data) {
			removeAllThatIsNotBlocksOrModulesFromTree(ul, data, chooseCodeLayoutUIEditorModuleBlockFromFileManagerTree);
		},
	});
	chooseCodeLayoutUIEditorModuleBlockFromFileManagerTree.init("choose_layout_ui_editor_module_block_from_file_manager");
	
	chooseCodeLayoutUIEditorModuleBlockFromFileManagerTreeRightContainer = new MyTree({
		multiple_selection : false,
		toggle_children_on_click : true,
		ajax_callback_before : prepareLayerNodes1,
		ajax_callback_after : function(ul, data) {
			removeAllThatIsNotBlocksOrModulesFromTree(ul, data, chooseCodeLayoutUIEditorModuleBlockFromFileManagerTreeRightContainer);
			
   			setCodeLayoutUIEditorTreeItemsDraggableEvent(ul);
   			
   			//bc the removeAllThatIsNotBlocksOrModulesFromTree is async, run the setCodeLayoutUIEditorTreeItemsDraggableEvent again just in case we miss some item like the db table items.
   			setTimeout(function() {
   				setCodeLayoutUIEditorTreeItemsDraggableEvent(ul);
   			}, 1000);
		},
	});
	chooseCodeLayoutUIEditorModuleBlockFromFileManagerTreeRightContainer.init("layout_ui_editor_right_container");
	
	var textarea = main_obj.find(".layout-ui-editor > textarea")[0];
	createCodeLayoutUIEditorEditor(textarea, opts);
	
	//set event to escape the draggable_menu_item
	$(document).bind("keyup", function(e) {
		if (e.which === 27 || e.keyCode === 27) //if escape key
			$(".dragging_menu_item.ui-draggable-dragging").data("escape_key_pressed", true).trigger('mouseup');
	});
}

function setCodeLayoutUIEditorTreeItemsDraggableEvent(ul) {
	ul = $(ul);
	
	var PtlLayoutUIEditor = $(".code_layout_ui_editor .layout-ui-editor").data("LayoutUIEditor");
	var cmob = PtlLayoutUIEditor.getMenuWidgets().find(".menu-widget-call-tree-module-or-block");
	var cmob_attributes = cmob[0].attributes;
	var cmob_template_widget = cmob.children(".template-widget");
	
	//prepare draggable items from right container
	ul.find(".draggable_menu_item").each(function(idx, item) {
		item = $(item);
		
		if (item.attr("tree-draggable-inited") != 1) {
			item.attr("tree-draggable-inited", 1);
			
			if (item.hasClass("ui-draggable"))
				item.draggable('destroy'); //remove the previous draggable event setted by the edit_page_and_template.js
			
	  		var li = item.closest("li");
	  		li.append( cmob_template_widget.clone() );
	  		
	  		if (cmob_attributes)
	  			for (var i = 0; i < cmob_attributes.length; i++) {
	  				var attr = cmob_attributes[i];
	  				var attr_name = ("" + attr.name).toLowerCase();
	  				
	  				if (attr_name != "class" && attr_name != "id")
	  					li.attr(attr_name, attr.value);
	  			}
	  		
	  		PtlLayoutUIEditor.setDraggableMenuWidget(li, {
	  			menu_widget_to_create_template_widget: cmob[0],
	  			on_drag_start_func: function(menu_widget, event, ui_obj, ret) {
	  				//close module info popup
		  			closeModuleInfo();
		  			
		  			return ret;
		  		}
	  		});
		}
	});
}

//To be used by the module_block.xml widget
function onCodeLayoutUIEditorModuleBlockWidgetDragAndDrop(widget, tree_obj, do_not_delete_widget) {
	var popup = $("#choose_layout_ui_editor_module_block_from_file_manager");
	
	CodeLayoutUIEditorFancyPopup.init({
		elementToShow: popup,
		parentElement: document,
		
		targetField: widget,
		updateFunction: function() {
			chooseCodeLayoutUIEditorModuleBlock(tree_obj);
		},
		onClose: function() {
			//delete widget and update menu layer
			var PtlLayoutUIEditor = $(".code_layout_ui_editor .layout-ui-editor").data("LayoutUIEditor");
			
			if (!do_not_delete_widget && PtlLayoutUIEditor)
				PtlLayoutUIEditor.deleteTemplateWidget(widget); //delete widget and update menu layer from layout ui editor
		}
	});
	
	CodeLayoutUIEditorFancyPopup.showPopup();
}

function chooseCodeLayoutUIEditorModuleBlock(tree_obj) {
	var node = tree_obj.getSelectedNodes();
	node = node[0];
	
	//close module info popup in case be open.
	closeModuleInfo();
	
	if (node) {
		node = $(node);
		
		if (node.hasClass("draggable_menu_item_block")) {
	    		var path = node.children("a").attr("file_path");
	    		var pos = path.indexOf("/src/block/");
	    		var project = path.substr(0, pos);
	    		var block = path.substr(pos + "/src/block/".length);
	    		block = block.substr(0, block.length - 4);
	    		
	    		var widget = CodeLayoutUIEditorFancyPopup.settings.targetField;
	    		
	    		CodeLayoutUIEditorFancyPopup.setOption("onClose", null);
	    		CodeLayoutUIEditorFancyPopup.hidePopup();
	    		
			updateCodeLayoutUIEditorModuleBlockWidgetWithBlockId(widget, block, project);
	    	}
		else if (node.hasClass("draggable_menu_item_module")) {
	    		var module_id = getMenuItemModuleId(node);
	    		var url = add_block_url.replace("#module_id#", module_id);
	    		
	    		chooseCodeLayoutUIEditorImportModulePopup(url);
		}
		else if (node.hasClass("draggable_menu_item_table")) {
	    		var i = node.find(" > a > i");
	    		var db_driver_node = node.parent().closest("li");
	    		
	    		var db_broker = db_driver_node.attr("db_driver_broker");
	    		var db_driver = db_driver_node.attr("db_driver_name");
	    		var db_type = db_driver_node.attr("db_driver_type");
	    		var db_table = node.children("a").attr("table");
	    		var widget = CodeLayoutUIEditorFancyPopup.settings.targetField;
	    		
	    		var PtlLayoutUIEditor = $(".code_layout_ui_editor .layout-ui-editor").data("LayoutUIEditor");
	    		var elm_from_point = PtlLayoutUIEditor && window.event && window.event.target ? PtlLayoutUIEditor.getWidgetDroppedElement(window.event, $(window.event.target)) : null;
	    		var widget_group = elm_from_point ? $(elm_from_point).closest("[data-widget-group-list], [data-widget-group-form]") : null;
			var inside_of_widget_group = widget_group && widget_group.length > 0;
			
			var opts = {
				hide_db_broker: true,
				hide_db_driver: true,
				hide_type: true,
				find_best_type: true,
				hide_db_table: true,
				hide_table_alias: true,
				hide_widget_type: true,
				widget_type: "html"
			};
			
			if (inside_of_widget_group)
				onReplaceCodeLayoutUIEditorDBTableWidgetOptions(db_broker, db_driver, db_type, db_table, widget, opts);
			else 
				onChooseCodeLayoutUIEditorDBTableWidgetOptions(db_broker, db_driver, db_type, db_table, widget, opts);
		}
	}
}

function onValidateCodeLayoutUIEditorDBTableWidget(db_broker, db_driver, db_type, db_table, widget) {
	var validated = typeof db_brokers_drivers_tables_attributes != "undefined";
	
	//check if db_driver exists and only proceed if yes
	if (validated && db_driver) {
		var exists = false;
		
		for (var db_broker_aux in db_brokers_drivers_tables_attributes)
			if (db_brokers_drivers_tables_attributes[db_broker_aux].hasOwnProperty(db_driver)) {
				exists = true;
				break;
			}
		
		if (!exists)
			validated = false;
	}
	
	if (!validated) {
		StatusMessageHandler.showError("Action not possible because there are no DB Drivers available or selected DB Driver cannot be used!");
		
		//delete widget and update menu layer from layout ui editor
		var PtlLayoutUIEditor = $(".code_layout_ui_editor .layout-ui-editor").data("LayoutUIEditor");
		
		if (PtlLayoutUIEditor)
			PtlLayoutUIEditor.deleteTemplateWidget(widget); //delete widget and update menu layer from layout ui editor
	}
	
	return validated;
}

function onReplaceCodeLayoutUIEditorDBTableWidgetOptions(db_broker, db_driver, db_type, db_table, widget, opts) {
	if (!onValidateCodeLayoutUIEditorDBTableWidget(db_broker, db_driver, db_type, db_table, widget))
		return;
	
	//must have settimeout bc the widget doesn't have the right parent yet.
	setTimeout(function() {
		var widget_parent = widget.parent();
		var widget_group = widget_parent.closest("[data-widget-group-list], [data-widget-group-form]");
		
		if (widget_group[0]) {
			//show popup asking if user wants to replace the table in existing widget or to create a sub_widget.
			var popup = $(".replace_existing_db_table_widget_popup");
			
			if (!popup[0]) {
				popup = $('<div class="myfancypopup with_title replace_existing_db_table_widget_popup">'
						+ '<div class="title">Choose an action</div>'
						+ '<div class="info">You are about to drop this item into an existing widget group.<br/>Do you wish to replace the table name in the existing widget settings or create a new widget inside of the existing one?</div>'
						+ '<div class="button">'
							+ '<input type="button" value="Replace table name" onclick="ReplaceExistingDBTableWidgetFancyPopup.settings.replaceTableNameFunction(this)">'
							+ '<input type="button" value="Create sub-widget" onclick="ReplaceExistingDBTableWidgetFancyPopup.settings.createSubWidgetFunction(this)">'
						+ '</div>'
						+ '<div class="table_alias">'
							+ '<label>Table alias:</label>'
							+ '<input placeHolder="Leave blank for default" />'
						+ '</div>'
					+ '</div>');
				$(document.body).append(popup);
			}
			
			//init and show popup
			ReplaceExistingDBTableWidgetFancyPopup.init({
				elementToShow: popup,
				parentElement: document,
				
				onClose: function() {
					//delete widget and update menu layer
					var PtlLayoutUIEditor = $(".code_layout_ui_editor .layout-ui-editor").data("LayoutUIEditor");
					
					if (PtlLayoutUIEditor)
						PtlLayoutUIEditor.deleteTemplateWidget(widget); //delete widget and update menu layer from layout ui editor
				},
				replaceTableNameFunction: function(btn) {
					var table_alias = popup.find(".table_alias input").val();
					
					replaceCodeLayoutUIEditorDBTableWidgetSettings(db_broker, db_driver, db_type, db_table, table_alias, widget_group, opts);
					ReplaceExistingDBTableWidgetFancyPopup.hidePopup();
				},
				createSubWidgetFunction: function(btn) {
					onChooseCodeLayoutUIEditorDBTableWidgetOptions(db_broker, db_driver, db_type, db_table, widget, opts);
					
					var table_alias = popup.find(".table_alias input").val();
					$(".choose_db_table_widget_options_popup .table_alias input").val(table_alias);
					
					ReplaceExistingDBTableWidgetFancyPopup.setOption("onClose", null);
					ReplaceExistingDBTableWidgetFancyPopup.hidePopup();
				},
			});
			ReplaceExistingDBTableWidgetFancyPopup.showPopup();
		}
		else {
			//delete widget and update menu layer
			var PtlLayoutUIEditor = $(".code_layout_ui_editor .layout-ui-editor").data("LayoutUIEditor");
			
			if (PtlLayoutUIEditor)
				PtlLayoutUIEditor.deleteTemplateWidget(widget); //delete widget and update menu layer from layout ui editor
		}
	}, 100);
}

function replaceCodeLayoutUIEditorDBTableWidgetSettings(db_broker, db_driver, db_type, db_table, db_table_alias, widget_group, opts) {
	var PtlLayoutUIEditor = $(".code_layout_ui_editor .layout-ui-editor").data("LayoutUIEditor");
	
	if (PtlLayoutUIEditor) {
		MyFancyPopup.showLoading();
		
		var settings = {
			db_broker: db_broker,
			db_driver: db_driver,
	    		db_type: db_type,
			db_table: db_table,
			db_table_alias: db_table_alias,
		};
		
		//add db_broker
		settings = addDBBrokerToCodeLayoutUIEditorDBTableWidgetSettings(settings);
		
		PtlLayoutUIEditor.LayoutUIEditorWidgetResource.replaceUserSettingsInWidgetGroup(widget_group, settings);
		
		MyFancyPopup.hideLoading();
	}
}

function onChooseCodeLayoutUIEditorDBTableWidgetOptions(db_broker, db_driver, db_type, db_table, widget, opts) {
	if (!onValidateCodeLayoutUIEditorDBTableWidget(db_broker, db_driver, db_type, db_table, widget))
		return;
	
	//get existent popup
	var popup = $(".choose_db_table_widget_options_popup");
	
	//prepare on_change_callback
	var on_change_callback = function(p, widget_type, widget_group) {
		if (widget_type == "html" && widget_group == "list")
			p.children(".widget_list_type").show();
		else
			p.children(".widget_list_type").hide();
	};
	
	//create popup if not exists yet
	if (!popup[0]) {
		popup = $('<div class="myfancypopup with_title choose_db_table_widget_options_popup">'
				+ '<div class="title">Choose your options:</div>'
				+ '<div class="toggle_advanced_options"><a href="javascript:void(0)">Show Advanced Options</a></div>'
				+ '<div class="db_broker">'
					+ '<label>DB Broker:</label>'
					+ '<select onchange="updateDBDrivers(this)"></select>'
				+ '</div>'
				+ '<div class="db_driver">'
					+ '<label>DB Driver:</label>'
					+ '<select onchange="updateDBTablesOnChooseCodeLayoutUIEditorDBTableWidgetOptionsPopup(this)"></select>'
				+ '</div>'
				+ '<div class="type">'
					+ '<label>Table Origin/Type:</label>'
					+ '<select onchange="updateDBTablesOnChooseCodeLayoutUIEditorDBTableWidgetOptionsPopup(this)">'
						+ '<option value="db">From DB Server</option>'
						+ '<option value="diagram">From DB Diagram</option>'
					+ '</select>'
				+ '</div>'
				+ '<div class="db_table">'
					+ '<label>Table Name:</label>'
					+ '<select></select>'
					+ '<span class="icon refresh" onClick="refreshDBTables(this)"></span>'
				+ '</div>'
				+ '<div class="table_alias">'
					+ '<label>Table Alias:</label>'
					+ '<input placeHolder="Leave blank for default" />'
				+ '</div>'
				+ '<div class="widget_type">'
					+ '<label>Widget Type:</label>'
					+ '<select>'
						+ '<option value="html" title="Widget based in editable html elements">Html</option>'
						+ '<option value="block" title="Widget based in a Block file">Block</option>'
					+ '</select>'
				+ '</div>'
				+ '<div class="widget_group">'
					+ '<label>UI Type:</label>'
					+ '<select>'
						+ '<option value="list">List</option>'
						+ '<option value="form">Form</option>'
					+ '</select>'
				+ '</div>'
				+ '<div class="widget_list_type">'
					+ '<label>List Type:</label>'
					+ '<select>'
						+ '<option value="table" title="Table layout">Table</option>'
						+ '<option value="tree" title="Tree layout based in ul/li">Tree</option>'
						+ '<option value="both" title="Table and tree layout">Table and Tree</option>'
					+ '</select>'
				+ '</div>'
				+ '<div class="widget_action">'
					+ '<label>Action:</label>'
					+ '<select>'
						+ '<option value="view">View</option>'
						+ '<option value="edit">Edit</option>'
						+ '<option value="remove">Remove</option>'
						+ '<option value="add">Add</option>'
					+ '</select>'
				+ '</div>'
				+ '<div class="button">'
					+ '<input type="button" value="Proceed" onclick="DBTableWidgetOptionsFancyPopup.settings.updateFunction(this)">'
				+ '</div>'
			+ '</div>');
		$(document.body).append(popup);
		
		popup.find(".toggle_advanced_options a").on("click", function(event) {
			var elm = $(this);
			popup.toggleClass("with_advanced_options");
			
			if (popup.hasClass("with_advanced_options"))
				elm.html("Hide Advanced Options");
			else
				elm.html("Show Advanced Options");
			
			DBTableWidgetOptionsFancyPopup.updatePopup();
			
			setTimeout(function() {
				DBTableWidgetOptionsFancyPopup.updatePopup();
			}, 500);
		});
		
		popup.find(".widget_type select").on("change", function(event) {
			var widget_type = $(this).val();
			var widget_group = popup.find(".widget_group select").val();
			
			on_change_callback(popup, widget_type, widget_group);
		});
		
		popup.find(".widget_group select").on("change", function(event) {
			var widget_group = $(this).val();
			var widget_type = popup.find(".widget_type select").val();
			
			on_change_callback(popup, widget_type, widget_group);
		});
	}
	
	//get db_broker for selected db_driver
	if (!db_broker) {
		var aux = addDBBrokerToCodeLayoutUIEditorDBTableWidgetSettings({db_driver: db_driver})
		db_broker = aux["db_broker"];
	}
	
	//prepare db select fields html
	var db_broker_options_html = '<option value="' + (default_dal_broker ? default_dal_broker : "") + '">-- default --</option>';
	var db_driver_options_html = "";
	var db_table_options_html = "";
	var add_db_table_allowed = typeof create_db_table_or_attribute_url != "undefined" && create_db_table_or_attribute_url;
	
	if ($.isPlainObject(db_brokers_drivers_tables_attributes)) {
		for (broker_name in db_brokers_drivers_tables_attributes)
			db_broker_options_html += '<option>' + broker_name + '</option>';
		
		if (db_broker && $.isPlainObject(db_brokers_drivers_tables_attributes[db_broker])) {
			for (driver_name in db_brokers_drivers_tables_attributes[db_broker])
				db_driver_options_html += '<option>' + driver_name + '</option>';
			
			if (db_driver && $.isPlainObject(db_brokers_drivers_tables_attributes[db_broker][db_driver]) && $.isPlainObject(db_brokers_drivers_tables_attributes[db_broker][db_driver][db_type || "db"])) {
				if (add_db_table_allowed)
					db_table_options_html = '<option value="">-- Create a new Table --</option><option value="0" disabled></option>'; //value == 0, so it doesn't get never selected
				
				for (table_name in db_brokers_drivers_tables_attributes[db_broker][db_driver][db_type || "db"])
					db_table_options_html += '<option>' + table_name + '</option>';
			}
		}
	}
	
	if (db_driver && !db_driver_options_html)
		db_driver_options_html = '<option>' + db_driver + '</option>';
	
	var update_db_tables = true;
	
	if (db_driver && db_table && !db_table_options_html)
		db_table_options_html = '<option>' + db_table + '</option>';
	else if (db_driver)
		update_db_tables = false;
	
	popup.find(".db_broker select").html(db_broker_options_html).val(db_broker);
	popup.find(".db_driver select").html(db_driver_options_html).val(db_driver);
	popup.find(".type select").val(db_type || "db");
	popup.find(".db_table select").html(db_table_options_html).val(db_table);
	
	if (update_db_tables) {
		var db_broker = popup.find(".db_broker select").val();
		var db_driver = popup.find(".db_driver select").val();
		var db_type = popup.find(".type select").val();
		
		if (db_broker && db_driver && db_brokers_drivers_tables_attributes[db_broker] && db_brokers_drivers_tables_attributes[db_broker][db_driver] && db_brokers_drivers_tables_attributes[db_broker][db_driver][db_type])
			db_brokers_drivers_tables_attributes[db_broker][db_driver][db_type] = null;
		
		updateDBTablesOnChooseCodeLayoutUIEditorDBTableWidgetOptionsPopup( popup.find(".db_driver select")[0] );
	}
	
	//load some default values
	popup.find(".widget_type, .widget_group, .widget_list_type, .widget_action").removeClass("hidden");
	
	if (opts) {
		if (opts["find_best_type"])
			setBestTableDBTypeOnChooseCodeLayoutUIEditorDBTableWidgetOptionsPopup(popup);
		
		if (opts["hide_db_broker"])
			popup.find(".db_broker").addClass("hidden");
		
		if (opts["hide_db_driver"])
			popup.find(".db_driver").addClass("hidden");
		
		if (opts["hide_type"])
			popup.find(".type").addClass("hidden");
		
		if (opts["hide_db_table"])
			popup.find(".db_table").addClass("hidden");
		
		if (opts["hide_table_alias"])
			popup.find(".table_alias").addClass("hidden");
		
		if (opts["hide_widget_type"])
			popup.find(".widget_type").addClass("hidden");
		
		if (opts["hide_widget_group"])
			popup.find(".widget_group").addClass("hidden");
		
		if (opts["hide_widget_list_type"])
			popup.find(".widget_list_type").addClass("hidden");
		
		if (opts["hide_widget_action"])
			popup.find(".widget_action").addClass("hidden");
		
		if (opts.hasOwnProperty("table_alias"))
			popup.find(".table_alias input").val( opts["table_alias"] );
		
		if (opts.hasOwnProperty("widget_type"))
			popup.find(".widget_type select").val( opts["widget_type"] );
		
		if (opts.hasOwnProperty("widget_group"))
			popup.find(".widget_group select").val( opts["widget_group"] );
		
		if (opts.hasOwnProperty("widget_list_type"))
			popup.find(".widget_list_type select").val( opts["widget_list_type"] );
		
		if (opts.hasOwnProperty("widget_action"))
			popup.find(".widget_action select").val( opts["widget_action"] );
		
		on_change_callback(popup, popup.find(".widget_type select").val(), popup.find(".widget_group select").val());
	}
	
	//set update func
	var update_func = function(elm) {
		var db_broker = popup.find(".db_broker select").val();
		var db_driver = popup.find(".db_driver select").val();
		var db_type = popup.find(".type select").val();
		var db_table = popup.find(".db_table select").val();
		var table_alias = popup.find(".table_alias input").val();
		var widget_type = popup.find(".widget_type select").val();
		var widget_group = popup.find(".widget_group select").val();
		var widget_list_type = popup.find(".widget_list_type select").val();
		var widget_action = popup.find(".widget_action select").val();
		
		if (!db_table) {
			if (add_db_table_allowed) {
				//open new popup to add a new DB Table
				var add_popup = $(".create_db_table_or_attribute_popup");
				
				if (!add_popup[0]) {
					add_popup = $('<div class="myfancypopup create_db_table_or_attribute_popup with_iframe_title popup_with_popup_close_button popup_with_left_popup_close">'
								+ '<div class="iframe_top_bar_bg"></div>'
								+ '<button class="continue" onClick="DBTableWidgetOptionsCreateDBTableFancyPopup.settings.continueFunc(this)">Continue</button>'
							+ '</div>');
					$(document.body).append(add_popup);
				}
				
				//remove and readd iframe so we don't see the previous loaded html
				var url = create_db_table_or_attribute_url;
				url += (url.indexOf("?") != -1 ? "&" : "?") + "db_broker=" + db_broker + "&db_driver=" + db_driver + "&db_type=" + db_type + "&popup=1";
				add_popup.children("iframe").remove(); 
				add_popup.prepend('<iframe src="' + url + '"></iframe>');
				
				var iframe = add_popup.children("iframe");
				iframe.load(function() {
					var iframe_body = iframe.contents().find("body");
					var top_bar = iframe_body.find(".top_bar");
					
					if (top_bar.length > 0) {
						top_bar.addClass("popup_with_left_popup_close popup_with_popup_close_button");
						add_popup.children(".iframe_top_bar_bg").hide();
					}
					else { //it means some error happens
						iframe_body[0].style.paddingTop="50px"; //Do not use .css function bc it doesn't work.
						add_popup.children(".iframe_top_bar_bg").show();
					}
				});
				
				//backup db_tables
				var old_db_tables = getDBTables(db_broker, db_driver, db_type);
				
				//open popup to add a new table
				DBTableWidgetOptionsCreateDBTableFancyPopup.init({
					elementToShow: add_popup,
					parentElement: document,
					
					onClose: function() {
						//open popup again but with new table selected and with_advanced_options active
						onChooseCodeLayoutUIEditorDBTableWidgetOptions(db_broker, db_driver, db_type, db_table, widget, opts);
					},
					continueFunc: function() {
						//update new tables
						popup.find(".db_table .icon.refresh").trigger("click");
						
						//get new table
						var new_db_tables = getDBTables(db_broker, db_driver, db_type);
						var new_db_tables_name = $.isPlainObject(new_db_tables) ? Object.keys(new_db_tables) : [];
						var old_db_tables_name = $.isPlainObject(old_db_tables) ? Object.keys(old_db_tables) : [];
						
						var created_tables = new_db_tables_name.filter(function(table) {return !old_db_tables_name.includes(table); });
						db_table = created_tables[0] ? created_tables[0] : "";
						//console.log("created_table:"+db_table);
						
						DBTableWidgetOptionsCreateDBTableFancyPopup.hidePopup();
					},
				});
				DBTableWidgetOptionsCreateDBTableFancyPopup.showPopup();
				
				DBTableWidgetOptionsFancyPopup.setOption("onClose", null);
				DBTableWidgetOptionsFancyPopup.hidePopup();
				
				return null;
			}
			else {
				StatusMessageHandler.showError("You must choose a DB Table first, in order to continue.");
				
				if (!popup.hasClass("with_advanced_options"))
					popup.addClass("with_advanced_options");
			}
		}
		else {
			//hide popup first
			DBTableWidgetOptionsFancyPopup.setOption("onClose", null);
			DBTableWidgetOptionsFancyPopup.hidePopup();
			
			if (widget_type == "html") //replace widget by real widget
				updateCodeLayoutUIEditorDBTableWidget(widget, {
			    		widget_group: widget_group,
			    		widget_list_type: widget_list_type,
			    		widget_action: widget_action,
			    		db_broker: db_broker,
			    		db_driver: db_driver,
			    		db_type: db_type,
					db_table: db_table,
			    		db_table_alias: table_alias,
				});
			else {
				var task_tag = null;
				var task_tag_action = null;
				
				if (widget_group == "list")
		    			task_tag = "listing";
		    		else if (widget_group == "form") {
		    			if (widget_action == "view")
			    			task_tag = "view";
			    		else 
			    			task_tag = "form";
		    		}
	    			
				if (widget_action == "add")
		    			task_tag_action = "insert";
		    		else if (widget_action == "edit")
		    			task_tag_action = "update,delete";
		    		else if (widget_action == "remove")
		    			task_tag_action = "delete";
				
				openCodeLayoutUIEditorDBTableUisDiagramBlockPopup(widget, {
			    		task_tag: task_tag,
			    		task_tag_action: task_tag_action,
			    		db_broker: db_broker,
			    		db_driver: db_driver,
			    		db_type: db_type,
					db_table: db_table,
			    		db_table_alias: table_alias,
		    		});
		    	}
		}
	};
	
	//init and show popup
	DBTableWidgetOptionsFancyPopup.init({
		elementToShow: popup,
		parentElement: document,
		
		onClose: function() {
			//delete widget and update menu layer
			var PtlLayoutUIEditor = $(".code_layout_ui_editor .layout-ui-editor").data("LayoutUIEditor");
			
			if (PtlLayoutUIEditor)
				PtlLayoutUIEditor.deleteTemplateWidget(widget);
		},
		updateFunction: update_func,
	});
	DBTableWidgetOptionsFancyPopup.showPopup();
}

function setBestTableDBTypeOnChooseCodeLayoutUIEditorDBTableWidgetOptionsPopup(popup) {
	var db_broker = popup.find(".db_broker select").val();
	var db_driver = popup.find(".db_driver select").val();
	var type = popup.find(".type select").val();
	var db_table = popup.find(".db_table select").val();
	
	//check if table exists in diagram, and if so, give priority to the diagram
	if (db_broker && db_driver && type != "diagram" && db_table) {
		if (db_broker && db_driver && db_brokers_drivers_tables_attributes[db_broker] && db_brokers_drivers_tables_attributes[db_broker][db_driver] && db_brokers_drivers_tables_attributes[db_broker][db_driver]["diagram"])
			db_brokers_drivers_tables_attributes[db_broker][db_driver]["diagram"] = null;
		
		var db_tables = getDBTables(db_broker, db_driver, "diagram");
		
		if ($.isPlainObject(db_tables) && db_tables.hasOwnProperty(db_table))
			popup.find(".type select").val("diagram").trigger("change");
	}
}

function updateDBTablesOnChooseCodeLayoutUIEditorDBTableWidgetOptionsPopup(elm) {
	updateDBTables(elm);
	
	var add_db_table_allowed = typeof create_db_table_or_attribute_url != "undefined" && create_db_table_or_attribute_url;
	
	if (add_db_table_allowed) {
		var select = $(elm).parent().parent().find(" > .db_table > select");
		var option = select.children("option").first();
		
		if (option.val() == "") {
			option.attr("value", ""); //set value attribute to empty, bc this option doesn't have the value and we must set it to empty string. IMPORTANT: if we do NOT do this, we get then a javascript error, after refresh
			option.html("-- Create a new Table --");
			option.after('<option value="0" disabled></option>'); //value == 0, so it doesn't get never selected
		}
		else
			select.prepend('<option value="">-- Create a new Table --</option><option value="0" disabled></option>'); //value == 0, so it doesn't get never selected
	}
}

function updateCodeLayoutUIEditorDBTableWidget(widget, settings) {
	var PtlLayoutUIEditor = $(".code_layout_ui_editor .layout-ui-editor").data("LayoutUIEditor");
	
	if (PtlLayoutUIEditor) {
		widget.hide();
		MyFancyPopup.showLoading();
		
		//add db_broker
		settings = addDBBrokerToCodeLayoutUIEditorDBTableWidgetSettings(settings);
		
		//must have settimeout bc the widget doesn't have the right parent yet.
		setTimeout(function() {
			//prepare widget_settings. Note that the widget is a block widget and we simply want an html widget, so we need to create a new one
			var new_widget = $('<div></div>');
			widget.after(new_widget);
			widget.remove();
			
			//convert new_widget to a real widget
			PtlLayoutUIEditor.convertHtmlElementToWidget(new_widget);
			PtlLayoutUIEditor.refreshElementMenuLayer( new_widget.parent() );
			
			if (!PtlLayoutUIEditor.LayoutUIEditorWidgetResource.prepareWidgetBasedInUserSettings(new_widget, settings)) {
				new_widget.remove();
			}
			
			MyFancyPopup.hideLoading();
		}, 100);
	}
}

function addDBBrokerToCodeLayoutUIEditorDBTableWidgetSettings(settings) {
	//prepare db_broker
	if (!settings["db_broker"] && settings["db_driver"]) {
		if (default_dal_broker && $.isPlainObject(db_brokers_drivers_tables_attributes[default_dal_broker]))
			for (db_driver in db_brokers_drivers_tables_attributes[default_dal_broker])
				if (db_driver == settings["db_driver"]) {
					settings["db_broker"] = default_dal_broker;
					break;
				}
		
		if (!settings["db_broker"])
			for (db_broker in db_brokers_drivers_tables_attributes)
				if ($.isPlainObject(db_brokers_drivers_tables_attributes[db_broker])) {
					for (db_driver in db_brokers_drivers_tables_attributes[db_broker])
						if (db_driver == settings["db_driver"]) {
							settings["db_broker"] = db_broker;
							break;
						}
					
					if (settings["db_broker"])
						break;
				}
	}
	
	if (settings["db_broker"] == default_dal_broker)
		settings["db_broker"] = "";
	
	return settings;
}

function openCodeLayoutUIEditorDBTableUisDiagramBlockPopup(widget, settings) {
	var task_tag = settings["task_tag"] ? settings["task_tag"] : "";
	var task_tag_action = settings["task_tag_action"] ? settings["task_tag_action"] : "";
	var db_driver = settings["db_driver"] ? settings["db_driver"] : (typeof default_db_driver != "undefined" ? default_db_driver : "");
	var db_type = settings["db_type"] ? settings["db_type"] : (typeof default_db_driver != "undefined" ? default_db_type : "");
	var db_table = settings["db_table"] ? settings["db_table"] : "";
	
	var popup_elm = $(".db_table_uis_diagram_block");
	
	//remove and readd iframe so we don't see the previous loaded html
	popup_elm.children("iframe").remove(); 
	popup_elm.prepend('<iframe></iframe>');
	
	var url = popup_elm.attr("create_page_presentation_uis_diagram_block_url");
	url += (url.indexOf("?") != -1 ? "&" : "?") + "&parent_add_block_func=addCodeLayoutUIEditorDBTableUisDiagramBlockToPage&task_tag=" + task_tag + "&task_tag_action=" + task_tag_action + "&db_driver=" + db_driver + "&db_type=" + db_type + "&db_table=" + db_table;
	
	CodeLayoutUIEditorDBTableUisDiagramBlockFancyPopup.init({
		elementToShow: popup_elm,
		parentElement: document,
		type: "iframe",
		url: url,
		
		targetField: widget,
	});
	CodeLayoutUIEditorDBTableUisDiagramBlockFancyPopup.showPopup();
}

function addCodeLayoutUIEditorDBTableUisDiagramBlockToPage(block_file_path) {
	var pos = block_file_path.indexOf("/src/block/");
	var project = block_file_path.substr(0, pos);
	var block = block_file_path.substr(pos + "/src/block/".length);
	
	var widget = CodeLayoutUIEditorDBTableUisDiagramBlockFancyPopup.settings.targetField;
	updateCodeLayoutUIEditorModuleBlockWidgetWithBlockId(widget, block, project);
	
	CodeLayoutUIEditorDBTableUisDiagramBlockFancyPopup.hidePopup();
	
	CodeLayoutUIEditorFancyPopup.setOption("onClose", null);
	CodeLayoutUIEditorFancyPopup.hidePopup();
	closeModuleInfo();
}

function chooseCodeLayoutUIEditorImportModulePopup(url) {
	if (!url) {
		alert("There is any url to open popup. Please talk with the system administrator.");
		return;
	}
	
	//get popup
	var popup= $(".choose_module_block_popup");
	
	if (!popup[0]) {
		popup = $('<div class="myfancypopup with_iframe_title choose_module_block_popup"></div>');
		$(document.body).append(popup);
	}
	
	popup.html('<iframe></iframe>'); //cleans the iframe so we don't see the previous html
	
	//prepare popup iframe
	var iframe = popup.children("iframe");
	
	url += (url.indexOf("?") != -1 ? "&" : "?") + "popup=1";
	iframe.attr("src", url);
	
	iframe.load(function() {
		this.contentWindow.$.ajaxSetup({
			complete: function(jqXHR, textStatus) {
				if (jqXHR.status == 200 && this.url.indexOf("/presentation/save_page_module_block?") > 0) {
					var ajax_response = $.parseJSON(jqXHR.responseText);
					
					if (ajax_response && ajax_response["status"] == 1) {
						var block_id = ajax_response["block_id"];
						var widget = CodeLayoutUIEditorFancyPopup.settings.targetField;
						
						if (block_id)
							updateCodeLayoutUIEditorModuleBlockWidgetWithBlockId(widget, block_id);
						else {
							alert("Error trying to create block! Please try again...");
							
							var PtlLayoutUIEditor = $(".code_layout_ui_editor .layout-ui-editor").data("LayoutUIEditor");
							
							if (PtlLayoutUIEditor)
								PtlLayoutUIEditor.deleteTemplateWidget(widget); //delete widget and update menu layer from layout ui editor
						}
						
						TemplateRegionBlockComboBoxImportModuleBlockOptionFancyPopup.hidePopup();
						
						CodeLayoutUIEditorFancyPopup.setOption("onClose", null);
						CodeLayoutUIEditorFancyPopup.hidePopup();
						closeModuleInfo();
					}
				}
		    	}
		});
	});
	
	//open popup
	TemplateRegionBlockComboBoxImportModuleBlockOptionFancyPopup.init({
		elementToShow: popup,
		parentElement: document,
		onClose: function(elm) {
			var widget = CodeLayoutUIEditorFancyPopup.settings.targetField;
			
			if (widget) {
				var PtlLayoutUIEditor = $(".code_layout_ui_editor .layout-ui-editor").data("LayoutUIEditor");
				
				if (PtlLayoutUIEditor)
					PtlLayoutUIEditor.deleteTemplateWidget(widget); //delete widget and update menu layer from layout ui editor
			}
		}
	});
	
	TemplateRegionBlockComboBoxImportModuleBlockOptionFancyPopup.showPopup();
}

function updateCodeLayoutUIEditorModuleBlockWidgetWithBlockId(widget, block_id, project) {
	var PtlLayoutUIEditor = $(".code_layout_ui_editor .layout-ui-editor").data("LayoutUIEditor");
	
	if (PtlLayoutUIEditor) {
		widget.hide();
		
		//must have settimeout bc the widget doesn't have the right parent yet.
		setTimeout(function() {
			//prepare template region block item
			var template_region = widget.parent().closest(".template_region");
			
			if (!template_region[0]) {
				var TemplateCallModuleOrBlockWidgetObj = PtlLayoutUIEditor.menu_widgets_objs["call-module-or-block"];
				TemplateCallModuleOrBlockWidgetObj.updateWidgetWithBlockId(widget, block_id, project);
				
				widget.show();
			}
			else {
				//find an empty direct-sibling
				var prev = widget.prev().filter(".template_region_item");
				var next = widget.next().filter(".template_region_item");
				var blocks_options = [];
				
				if (prev[0])
					blocks_options.push(prev.children(".block_options")[0]);
				
				if (next[0])
					blocks_options.push(next.children(".block_options")[0]); 
			 	
			 	var new_item = null;
			 	
			 	//check if there is any empty block that we can use for the draggable_menu_item
			 	for (var i = 0; i < blocks_options.length; i++) {
			 		var block_options = $(blocks_options[i]);
			 		var item = block_options.parent();
			 		var type = item.children(".type").val();
			 		var block_type = item.children(".region_block_type").val();
			 		var is_html = type == 1;
			 		var block = "";
			 		
			 		if (is_html) {
						var block_html = item.children(".block_html");
			 			block = getRegionBlockHtmlEditorValue(block_html);
					}
			 		else if (block_type == "options")
			 			block = block_options.val();
			 		else if (block_type == "text")
			 			block = item.children(".block_text").children("textarea").val();
					else 
			 			block = item.children("input.block").val();
					
					block = block.replace(/\n\r\t/g, "");
					
					if (block == "") {
			 			new_item = item;
			 			return false;
		 			}
			 	}
				
			 	//if no empty block available, create a new one
			 	if (!new_item) {
			    		template_region.find(" > .template_region_name > .template_region_name_link").trigger("click"); //add new region-block item
			    		new_item = template_region.children(".template_region_items").children(".template_region_item").last(); //get last inserted item
			    	}
			    	
			    	if (new_item)
			    		widget.after(new_item); //then add new item after widget.
		    		else
		    			StatusMessageHandler.showError("Error trying to create new widget for block: '" + block_id + "'. Please try again...");
		    		
		    		//add new block to available block list
		    		addBlockToAvailableBlocksList(new_item, block_id, project ? project : selected_project_id);
		    		
		    		//set block_options from new item
		    		var block_options = new_item.children(".block_options");
		    		setRegionBlockOption(block_options, block_id, project ? project : selected_project_id);
				
				//remove widget
				widget.remove();
			}
		}, 100);
	}
}

function createCodeLayoutUIEditorEditor(textarea, opts) {
	if (textarea) {
		var parent = $(textarea).parent();
		
		if (typeof LayoutUIEditor == "function") {
			var is_full_source = $(textarea).hasClass("full-source");
			var ui = parent;
			parent.append('<ul class="menu-widgets"></ul><div class="template-source"></div>');
			
			var mwb = parent.children(".layout-ui-menu-widgets-backup");
			ui.children(".menu-widgets").append( mwb.contents() );
			mwb.remove();
			
			ui.children(".template-source").append(textarea);
			
			var ptl_ui_creator_var_name = "PTLLayoutUIEditor_" + Math.floor(Math.random() * 1000);
			var PtlLayoutUIEditor = new LayoutUIEditor();
			PtlLayoutUIEditor.options.ui_element = ui;
			PtlLayoutUIEditor.options.on_context_menu_widget_setting = typeof onContextMenuLayoutUIEditorWidgetSetting == "function" ? onContextMenuLayoutUIEditorWidgetSetting : null;
			PtlLayoutUIEditor.options.on_template_source_editor_ready_func = typeof setCodeEditorAutoCompleter == "function" ? setCodeEditorAutoCompleter : null;
			PtlLayoutUIEditor.options.on_template_source_editor_save_func = opts && opts.save_func ? opts.save_func : null;
			PtlLayoutUIEditor.options.beautify = opts && opts.beautify ? opts.beautify : true;
			
			PtlLayoutUIEditor.options.auto_convert = typeof auto_convert != "undefined" && auto_convert;
			PtlLayoutUIEditor.options.on_panels_resize_func = onResizeCodeLayoutUIEditorWithRightContainer;
			PtlLayoutUIEditor.options.on_choose_variable_func = typeof ProgrammingTaskUtil == "object" && typeof ProgrammingTaskUtil.on_programming_task_choose_created_variable_callback == "function" ? ProgrammingTaskUtil.on_programming_task_choose_created_variable_callback : onPresentationProgrammingTaskChooseCreatedVariable;
			
			if (typeof onPresentationIncludePageUrlTaskChooseFile == "function")
				PtlLayoutUIEditor.options.on_choose_page_url_func = function(elm) {
					onPresentationIncludePageUrlTaskChooseFile(elm);
					IncludePageUrlFancyPopup.settings.is_code_html_base = typeof is_code_html_base == "undefined" ? true : is_code_html_base;
				}
			
			if (typeof onPresentationIncludeImageUrlTaskChooseFile == "function")
				PtlLayoutUIEditor.options.on_choose_image_url_func = function(elm) {
					onPresentationIncludeImageUrlTaskChooseFile(elm);
					MyFancyPopup.settings.is_code_html_base = typeof is_code_html_base == "undefined" ? true : is_code_html_base;
				}
			
			if (typeof onPresentationIncludeWebrootFileUrlTaskChooseFile == "function")
				PtlLayoutUIEditor.options.on_choose_webroot_file_url_func = function(elm) {
					onPresentationIncludeWebrootFileUrlTaskChooseFile(elm);
					MyFancyPopup.settings.is_code_html_base = typeof is_code_html_base == "undefined" ? true : is_code_html_base;
				}
			
			//set new func for on_convert_project_url_php_vars_to_real_values_func that replaces inline vars too. This is only for the edit_entity_simple. The edit_template_simple should have this flag disabled and only replace the project_url_prefix inside of the php tags.
			PtlLayoutUIEditor.options.on_convert_project_url_php_vars_to_real_values_func = function(str, widget) {
				var replace_inline_vars = typeof replace_inline_project_url_php_vars == "undefined" ? false : replace_inline_project_url_php_vars;
				replace_inline_vars = widget && $(widget).parent().closest(".template_region").length > 0 ? true : replace_inline_vars; //in case of edit_template simple.
				
				return convertProjectUrlPHPVarsToRealValues(str, replace_inline_vars);
			};
			//set new func for on_convert_project_url_real_values_to_php_vars_func that replaces the project_url_prefix with the original_project_url_prefix var. This is only for the edit_template_simple. The edit_entity_simple should have the project_url_prefix var instead.
			PtlLayoutUIEditor.options.on_convert_project_url_real_values_to_php_vars_func = function(str, widget) {
				var html_base = typeof is_code_html_base == "undefined" ? true : is_code_html_base;
				html_base = widget && $(widget).parent().closest(".template_region").length > 0 ? false : html_base; //in case of edit_template simple.
				
				return convertProjectUrlRealValuesToPHPVars(str, typeof give_priority_to_original_project_url_prefix == "undefined" ? false : give_priority_to_original_project_url_prefix, html_base);
			};
			
			initLayoutUIEditorWidgetResourceOptions(PtlLayoutUIEditor);
			
			PtlLayoutUIEditor.options.on_ready_func = function() {
				//add right_container to layout ui editor
				var right_container = $(".layout_ui_editor_right_container");
				var luie = PtlLayoutUIEditor.getUI();
				var menu_widgets = PtlLayoutUIEditor.getMenuWidgets();
				var menu_layers = PtlLayoutUIEditor.getMenuLayers();
				var template_widgets_options = PtlLayoutUIEditor.getTemplateWidgetsOptions();
				var menu_settings = PtlLayoutUIEditor.getMenuSettings();
     			var options = luie.children(".options");
     			
     			luie.append(right_container);
     			right_container.hide();
     			
     			var right_container_icon = $('<i class="zmdi zmdi-view-dashboard option show-right-container hidden" title="Show Modules and Blocks"></i>');
     			var right_container_icon_click_handler = function(icon, is_dbs) {
					luie.addClass("switching-panel");
					menu_widgets.fadeOut("slow");
					menu_layers.fadeOut("slow");
					template_widgets_options.fadeOut("slow");
		  				right_container.fadeIn("slow", function() {
						luie.removeClass("switching-panel");
					});
					
					options.find(".show-option-panel").val(is_dbs ? "show-right-container-dbs" : "show-right-container");
					options.find(".show-widgets, .show-layers, .show-layout-options, .show-right-container, .show-right-container-dbs").removeClass("option-active");
					$(icon).addClass("option-active");
					
					if (!luie.hasClass("fixed-properties") && !luie.hasClass("fixed-side-properties")) {
						menu_settings.fadeOut("slow");
						options.find(".show-settings").removeClass("option-active");
					}
     			}; 
     			right_container_icon.click(function() {
     				right_container_icon_click_handler(this, false);
     				
					right_container.removeClass("only_dbs");
     			});
     			options.find(".show-widgets").before(right_container_icon);
     			options.find(".show-option-panel").append('<option value="show-right-container">Modules, Blocks</option>');
     			
     			var right_container_dbs_icon = null;
     			
     			if (existCodeLayoutUIEditorSideBarRightContainerDBs()) {
		  			right_container_dbs_icon = $('<i class="zmdi zmdi-db option show-right-container-dbs hidden" title="Show DBs"></i>');
						right_container_dbs_icon.click(function() {
						right_container_icon_click_handler(this, true);
						
						right_container.addClass("only_dbs");
					});
					right_container_icon.after(right_container_dbs_icon);
					options.find(".show-option-panel").append('<option value="show-right-container-dbs">Databases</option>');
					
					if (showCodeLayoutUIEditorSideBarRightContainerDBs()) {
						var with_right_container_dbs = getCodeLayoutUIEditorUserDefinedToggle("show-right-container-dbs");
						
						if (with_right_container_dbs === null || with_right_container_dbs == 2) {
							luie.addClass("with_right_container_dbs");
							saveCodeLayoutUIEditorUserDefinedToggle("show-right-container-dbs", 2); //2 is the default
						}
					}
					else
						options.find(".show-option-panel option[value='show-right-container-dbs']").hide();
				}
				
   			options.find(".show-widgets, .show-layers, .show-layout-options" + (!luie.hasClass("fixed-properties") && !luie.hasClass("fixed-side-properties") ? ", .show-settings" : "")).click(function() {
   				right_container_icon.removeClass("option-active");
   				
   				if (right_container_dbs_icon)
	   				right_container_dbs_icon.removeClass("option-active");
   				
   				right_container.fadeOut("slow");
   			});
   			
	   		//add toggle_left_panel to layout ui editor
				var toggle_left_panel = $('<i class="zmdi zmdi-swap-vertical-circle zmdi-hc-rotate-90 option toggle-left-options" title="Toggle Panel"></i>');
				toggle_left_panel.click(function() {
					luie.toggleClass("left-panel-collapsed");
					
					saveCodeLayoutUIEditorUserDefinedToggle("collapse-left-options", luie.hasClass("left-panel-collapsed"));
				});
        			options.find(".show-layout-options").before(toggle_left_panel);
				
				//prepare template-widgets-options and options-right > .toggles
				PtlLayoutUIEditor.getTemplateWidgetsOptions().find(" > .options > .show-option").click(function() {
					var option = $(this);
					var m = option.attr("class").replace("show-option", "").match(/show-(\w+)/);
					var option_name = m ? m[1] : null;
					
					if (option_name)
						saveCodeLayoutUIEditorUserDefinedToggle("show-" + option_name, option.hasClass(option_name + "-shown"));
				});
				
				options.find(" > .options-right > .toggles > ul > li.toggle-option").click(function() {
					var option = $(this);
					var m = option.attr("class").replace("toggle-option", "").match(/toggle-(\w+)/);
					var option_name = m ? m[1] : null;
					
					if (option_name)
						saveCodeLayoutUIEditorUserDefinedToggle("show-" + option_name, option.hasClass(option_name + "-shown"));
				});
				
				//prepare save button
        		var code_layout_ui_editor = $(".code_layout_ui_editor");
				var code_menu = code_layout_ui_editor.find(".code_menu");
				var save_option = $('<i class="zmdi zmdi-floppy option save" title="Save" style="display:none"></i>');
     			
     			save_option.click(function() {
     				code_menu.find(".save a").trigger("click");
     				PtlLayoutUIEditor.clickViewLayoutTabWithoutSourceConversion();
     			});
     			options.children(".options-left").prepend(save_option);
        		
				//prepare tabs click
     			luie.find(" > .tabs > .tab").click(function() {
				if ($(this).hasClass("view-layout")) {
					save_option.show();
				}
				else {
					save_option.hide();
				}
				
				if ($(this).hasClass("view-source"))
					code_menu.show();
				else
					code_menu.hide();
	   		});
		   		
		   	//prepare full-screen option
				options.find(".full-screen").click(function() {
        			toggleEditorFullScreen();
        			
        			if (menu_settings.is(":visible"))
	        			PtlLayoutUIEditor.showFixedMenuSettings(true);
     				
     				/*if (luie.hasClass("full-screen")) {
     					var z_index = luie.css("z-index");
     					code_layout_ui_editor.addClass("editor_full_screen");
     					code_menu.css("z-index", z_index + 1);
     					openFullscreen();
     				}
     				else	{
     					code_layout_ui_editor.removeClass("editor_full_screen");
     					closeFullscreen();
     				}*/
     				
     				setTimeout(function() {
        				PtlLayoutUIEditor.TextSelection.refreshMenu();
        			}, 1000);
     			});

				//initResizeCodeLayoutUIEditorRightContainer(PtlLayoutUIEditor);
				
				if (opts && opts.ready_func)
					opts.ready_func();
				
				//must be after the opts.ready_func, otherwise the PtlLayoutUIEditor executes the on_template_widgets_layout_changed_func which is unnecessary
				PtlLayoutUIEditor.options.on_template_widgets_layout_changed_func = onChangeLayoutUIEditorWidgets;
			};
			window[ptl_ui_creator_var_name] = PtlLayoutUIEditor;
			PtlLayoutUIEditor.init(ptl_ui_creator_var_name);
			
			var editor = ui.children(".template-source").data("editor");
			
			if (is_full_source) {
				var code = PtlLayoutUIEditor.getTemplateSourceEditorValue();
				PtlLayoutUIEditor.setTemplateFullSourceEditorValue(code);
				
				PtlLayoutUIEditor.getUI().find(" > .options .option.show-full-source").addClass("option-active");
				PtlLayoutUIEditor.showTemplateSource();
				
				editor = PtlLayoutUIEditor.getTemplateFullSource().data("editor");
			}
			
			parent.data("editor", editor);
			parent.parent().closest(".code_layout_ui_editor").data("editor", editor);
		}
	}
}

function initCodeLayoutUIEditorAvailableToggles() {
	window.layoutuieditor_available_toggles = ["show-background", "show-borders", "show-php", "show-ptl", "show-comments", "show-js", "show-css", "show-right-container-dbs", "collapse-left-options"];
	window.layoutuieditor_available_toggles_handler = {
		"show-background": function(PtlLayoutUIEditor, value) {
			if (value == 1)
				PtlLayoutUIEditor.showTemplateWidgetsDroppableBackground();
		}, 
		"show-borders": function(PtlLayoutUIEditor, value) {
			if (value == 1)
				PtlLayoutUIEditor.showTemplateWidgetsBorders();
		}, 
		"show-php": function(PtlLayoutUIEditor, value) {
			if (value == 1)
				PtlLayoutUIEditor.showTemplatePHPWidgets();
		}, 
		"show-ptl": function(PtlLayoutUIEditor, value) {
			if (value == 1)
				PtlLayoutUIEditor.showTemplatePTLWidgets();
		}, 
		"show-comments": function(PtlLayoutUIEditor, value) {
			if (value == 1)
				PtlLayoutUIEditor.showTemplateCommentsWidgets();
		}, 
		"show-js": function(PtlLayoutUIEditor, value) {
			if (value == 1)
				PtlLayoutUIEditor.showTemplateJSWidgets();
		}, 
		"show-css": function(PtlLayoutUIEditor, value) {
			if (value == 1)
				PtlLayoutUIEditor.showTemplateCSSWidgets();
		},
		"show-right-container-dbs": function(PtlLayoutUIEditor, value) {
			var is_active = PtlLayoutUIEditor.getUI().hasClass("with_right_container_dbs");
			
			if ((is_active && value == 0) || (!is_active && value == 1))
				onToggleCodeLayoutUIEditorSideBarRightContainerDBs();
		},
		"collapse-left-options": function(PtlLayoutUIEditor, value) {
			if (value == 1)
				PtlLayoutUIEditor.getUI().find(" > .options > .options-left .option.toggle-left-options").trigger("click");
		}
	};
}

function addCodeLayoutUIEditorAvailableToggle(toggle_name, handler) {
	if (typeof window.layoutuieditor_available_toggles == "undefined")
		initCodeLayoutUIEditorAvailableToggles();
	
	window.layoutuieditor_available_toggles.push(toggle_name);
	window.layoutuieditor_available_toggles_handler[toggle_name] = handler;
}

function getCodeLayoutUIEditorAvailableToggles() {
	if (typeof window.layoutuieditor_available_toggles == "undefined")
		initCodeLayoutUIEditorAvailableToggles();
	
	return window.layoutuieditor_available_toggles;
}

function getCodeLayoutUIEditorAvailableTogglesHandler() {
	if (typeof window.layoutuieditor_available_toggles_handler == "undefined")
		initCodeLayoutUIEditorAvailableToggles();
	
	return window.layoutuieditor_available_toggles_handler;
}

function getCodeLayoutUIEditorUserDefinedToggle(option_name) {
	if (option_name) {
		var options = getCodeLayoutUIEditorAvailableToggles();
		var toggles = MyJSLib.CookieHandler.getCookie('luietoggles');
		var i = options.indexOf(option_name);
		
		if (i > 0)
			return i < toggles.length && $.isNumeric(toggles[i]) ? toggles[i] : null;
	}
	
	return null;
}

function getCodeLayoutUIEditorDefaultToggleValue(option_name) {
	return option_name == "show-right-container-dbs" ? 2 : 0; //default value for show-right-container-dbs is 2 which means is defined dynamically from other code
}

function saveCodeLayoutUIEditorUserDefinedToggle(option_name, option_value) {
	//console.log(option_name+":"+option_value);
	if (option_name) {
		var options = getCodeLayoutUIEditorAvailableToggles();
		var toggles = MyJSLib.CookieHandler.getCookie('luietoggles');
		var new_toggles = "";
		
		option_value = $.isNumeric(option_value) && option_value > 0 && option_value <= 9 ? option_value : (
			option_value ? 1 : 0 //option_value can be a boolean
		);
		
		for (var i = 0, t = options.length; i < t; i++) {
			var default_value = getCodeLayoutUIEditorDefaultToggleValue(options[i]);
			new_toggles += option_name == options[i] ? option_value : (
				i < toggles.length && $.isNumeric(toggles[i]) ? toggles[i] : default_value
			);
		}
		
		if (new_toggles != toggles)
			window.MyJSLib.CookieHandler.setCurrentDomainEternalRootSafeCookie('luietoggles', new_toggles);
	}
}

function prepareCodeLayoutUIEditorUserDefinedToggles(PtlLayoutUIEditor) {
	//show php widgets, borders and background
	var options = getCodeLayoutUIEditorAvailableToggles();
	var handlers = getCodeLayoutUIEditorAvailableTogglesHandler();
	var toggles = MyJSLib.CookieHandler.getCookie('luietoggles');
	
	for (var i = 0, t = options.length; i < t; i++) {
		var option_name = options[i];
		var default_value = getCodeLayoutUIEditorDefaultToggleValue(option_name);
		var value = i < toggles.length && $.isNumeric(toggles[i]) ? parseInt(toggles[i]) : default_value;
		var handler = handlers[option_name];
		
		if (typeof handler == "function")
			handler(PtlLayoutUIEditor, value);
	}
}

function existCodeLayoutUIEditorSideBarRightContainerDBs() {
	return $(".code_layout_ui_editor > .layout-ui-editor .layout_ui_editor_right_container > .mytree.db_drivers_tree").length > 0;
}

function showCodeLayoutUIEditorSideBarRightContainerDBs() {
	//add show-right-container-dbs if not admin_advanced, bc the admin_advanced already contains the DB tab with all the DBs in a tree, which means this will be redundant and may confuse the user.
	var show_right_container_dbs = window == window.parent || typeof window.parent.$ != "function" || window.parent.$.find("#left_panel .mytree .db_layers").length == 0;
	
	return show_right_container_dbs;
}

function addCodeLayoutUIEditorRightContainerDBsMenu(next_elm, class_name, with_separator) {
	if (existCodeLayoutUIEditorSideBarRightContainerDBs()) {
		var PtlLayoutUIEditor = $(".code_layout_ui_editor > .layout-ui-editor").data("LayoutUIEditor");
		var luie = PtlLayoutUIEditor.getUI();
		var is_shown = luie.hasClass("with_right_container_dbs");
		
		var li = $('<li class="toggle_right_container_dbs ' + class_name + (is_shown ? ' active' : '') + '" title="Toggle Side Bar DBs Panel"><a onClick="toggleCodeLayoutUIEditorRightContainerDBsMenu(this)"><i class="icon toggle_ids"></i> <span>' + (is_shown ? 'Hide' : 'Show') + '</span> Side Bar DBs Panel <input type="checkbox"' + (is_shown ? ' checked' : '') + '/></a></li>');
		next_elm.before(li);
		
		if (with_separator)
			li.after('<li class="separator"></li>');
	}
}

function toggleCodeLayoutUIEditorRightContainerDBsMenu(elm) {
	elm = $(elm);
	var li = elm.closest("li");
	var input = elm.find("input");
	var span = elm.find("span");
	
	li.toggleClass("active");
	
	if (li.hasClass("active")) {
		input.attr("checked", "checked").prop("checked", true);
		span.html("Hide");
	}
	else {
		input.removeAttr("checked").prop("checked", false);
		span.html("Show");
	}
	
	var PtlLayoutUIEditor = $(".code_layout_ui_editor > .layout-ui-editor").data("LayoutUIEditor");
	var luie = PtlLayoutUIEditor.getUI();
	
	if (
		(luie.hasClass("with_right_container_dbs") && !li.hasClass("active")) || 
		(!luie.hasClass("with_right_container_dbs") && li.hasClass("active"))
	)
		onToggleCodeLayoutUIEditorSideBarRightContainerDBs();
}

function onToggleCodeLayoutUIEditorSideBarRightContainerDBs() {
	var PtlLayoutUIEditor = $(".code_layout_ui_editor > .layout-ui-editor").data("LayoutUIEditor");
	var luie = PtlLayoutUIEditor.getUI();
	var options = luie.children(".options");
	var options_left = options.children(".options-left");
	var current_selection = options_left.find("select.show-option-panel").val();
	var select_opt = options_left.find("select.show-option-panel > option[value='show-right-container-dbs']");
	var options_show_right_container_dbs = options_left.children(".option.show-right-container-dbs");
	
	luie.toggleClass("with_right_container_dbs");
	
	var is_active = luie.hasClass("with_right_container_dbs");
	
	if (is_active) {
		select_opt.show();
		//options_show_right_container_dbs.show(); //Done from css
	}
	else {
		select_opt.hide();
		//options_show_right_container_dbs.hide(); //Done from css
		
		if (current_selection == "show-right-container-dbs")
			options_left.children(".option.show-widgets").trigger("click");
	}
	
	saveCodeLayoutUIEditorUserDefinedToggle("show-right-container-dbs", is_active);
}

//Note that when this function gets call from PtlLayoutUIEditor.options.on_template_widgets_layout_changed_func it may or may not pass a widget as first argument
function onChangeLayoutUIEditorWidgets() {
	updateSettingsFromLayoutIframeField();
}

//To be used in the toggleFullScreen function
function onToggleCodeEditorFullScreen(in_full_screen, main_obj) {
	setTimeout(function() {
		var PtlLayoutUIEditor = main_obj.find(".code_layout_ui_editor > .layout-ui-editor").data("LayoutUIEditor");
		
		if (!PtlLayoutUIEditor)
			PtlLayoutUIEditor = main_obj.find(".layout-ui-editor").data("LayoutUIEditor");
		
		if (PtlLayoutUIEditor) {
			var menu_settings = PtlLayoutUIEditor.getMenuSettings();
			
			if (menu_settings.is(":visible"))
				PtlLayoutUIEditor.showFixedMenuSettings();
			
			PtlLayoutUIEditor.TextSelection.refreshMenu();
		}
	}, 500);
}

function toggleCodeEditorHtmlBeautify(elm) {
	var PtlLayoutUIEditor = $(".code_layout_ui_editor .layout-ui-editor").data("LayoutUIEditor");
	
	if (PtlLayoutUIEditor) {
		elm = $(elm);
		var li = elm.parent();
		var title = li.attr("title");
		var html = elm.html();
		
		if (PtlLayoutUIEditor.options.beautify) {
			PtlLayoutUIEditor.options.beautify = false;
			title = title.replace("Disable", "Enable");
			html = html.replace("Disable", "Enable");
		}
		else {
			PtlLayoutUIEditor.options.beautify = true;
			title = title.replace("Enable", "Disable");
			html = html.replace("Enable", "Disable");
		}
		
		li.attr("title", title);
		elm.html(html);
	}
}

function flipCodeLayoutUIEditorPanelsSide(elm) {
	var PtlLayoutUIEditor = $(".code_layout_ui_editor .layout-ui-editor").data("LayoutUIEditor");
	PtlLayoutUIEditor.flipPanelsSide();
	
	var ui = PtlLayoutUIEditor.getUI();
	var options_left = ui.find(" > .options > .options-left");
	var layout_ui_editor_right_container = ui.children(".layout_ui_editor_right_container");
	
	options_left.css({"left": "", "right": ""});
	layout_ui_editor_right_container.css({"left": "", "right": ""});
}

function onResizeCodeLayoutUIEditorPanels(props) {
	var options_left = props.ui.find(" > .options > .options-left");
	
	if (props.is_reverse)
		options_left.css({"left": "calc(" + props.perc + "% + " + props.resize_panels_width + "px)"});
	else
		options_left.css({"right": "calc(100% - " + props.perc + "%)"});
}

function onResizeCodeLayoutUIEditorWithRightContainer(props) {
	onResizeCodeLayoutUIEditorPanels(props);
	
	var layout_ui_editor_right_container = props.ui.children(".layout_ui_editor_right_container");
	
	if (layout_ui_editor_right_container[0]) {
		if (props.direction == "horizontal") {
			if (props.is_reverse)
				layout_ui_editor_right_container.css({"left": props.calc_str});
			else
				layout_ui_editor_right_container.css({"right": props.perc_str});
		}
		else if (props.direction == "vertical")
			layout_ui_editor_right_container.css({"bottom": props.offset ? props.offset + "px" : ""});
	}
}

/*function initResizeCodeLayoutUIEditorRightContainer(PtlLayoutUIEditor) {
	var layout_ui_editor_right_container = $(".layout_ui_editor_right_container");
	var layout_ui_editor_container = PtlLayoutUIEditor.getUI().parent();
	var template_widgets = PtlLayoutUIEditor.getTemplateWidgets();
	var template_widgets_options = PtlLayoutUIEditor.getTemplateWidgetsOptions();
	
	$(".layout_ui_editor_right_container_resize").draggable({
		axis: "x",
		appendTo: 'body',
	    	//containment: PtlLayoutUIEditor.getUI(),
		cursor: 'move',
          tolerance: 'pointer',
		start: function(event, ui_obj) {
			PtlLayoutUIEditor.getTemplateWidgetsIframe().addClass("hidden");
		},
		drag: function(event, ui_obj) {
			var left = ui_obj.position.left;
			var width = layout_ui_editor_container.width() - left - 5; //bc of the resize panel which has 5px of width
			width = width > 0 ? width : 0;
			
			layout_ui_editor_right_container.css("width", width + "px");
			template_widgets.css("right", (width + 5) + "px"); //bc of the resize panel which has 5px of width
			template_widgets_options.css("right", (width + 5) + "px"); //bc of the resize panel which has 5px of width
		},
		stop: function(event, ui_obj) {
			PtlLayoutUIEditor.getTemplateWidgetsIframe().removeClass("hidden");
			
			var left = ui_obj.position.left;
			var width = layout_ui_editor_container.width() - left - 5; //bc of the resize panel which has 5px of width
			width = width > 0 ? width : 0;
			
			layout_ui_editor_right_container.css("width", width + "px");
			template_widgets.css("right", (width + 5) + "px"); //bc of the resize panel which has 5px of width
			template_widgets_options.css("right", (width + 5) + "px"); //bc of the resize panel which has 5px of width
			ui_obj.helper.css({left: "", top: "", right: width + "px"}); //reset layout_ui_editor_right_container_resize
			
			if (PtlLayoutUIEditor.TextSelection)
				PtlLayoutUIEditor.TextSelection.refreshMenu();
		},
	});
}*/

function getCodeLayoutUIEditorCode(main_obj) {
	var PtlLayoutUIEditor = main_obj.find(".layout-ui-editor").data("LayoutUIEditor");
	var code = null;
	
	if (PtlLayoutUIEditor) {
		//converts visual into code if visual tab is selected
		var is_template_layout_tab_show = PtlLayoutUIEditor.isTemplateLayoutShown();
		
		if (is_template_layout_tab_show)
			PtlLayoutUIEditor.convertTemplateLayoutToSource({
				with_head_attributes: true,
				with_body_attributes: true,
			});
		
		/*var is_template_preview_tab_show = PtlLayoutUIEditor.isTemplatePreviewShown();
		
		PtlLayoutUIEditor.forceTemplateSourceConversionAutomatically(); //If template source is not selected, it will select this tab.
		
		//When auto save active, the getCodeLayoutUIEditorCode function will be called and the forceTemplateSourceConversionAutomatically will select the SOURCE tab. So we need to re-open again the previous opened tab.
		if (is_template_preview_tab_show)
			PtlLayoutUIEditor.showTemplatePreview();
		else if (is_template_layout_tab_show)
			PtlLayoutUIEditor.clickViewLayoutTabWithoutSourceConversion();
		*/
		var luie = PtlLayoutUIEditor.getUI();
		var is_full_source_active = luie.find(" > .options .option.show-full-source").hasClass("option-active");
		
		code = is_full_source_active ? PtlLayoutUIEditor.getTemplateFullSourceEditorValue() : PtlLayoutUIEditor.getTemplateSourceEditorValue();
	}
	else {
		var editor = main_obj.data("editor");
		code = editor ? editor.getValue() : main_obj.find(".layout-ui-editor > .template-source > textarea").first().val();
	}
	
	return code;
}

function setCodeLayoutUIEditorCode(main_obj, code) {
	var PtlLayoutUIEditor = main_obj.find(".layout-ui-editor").data("LayoutUIEditor");
	
	if (PtlLayoutUIEditor) {
		PtlLayoutUIEditor.forceTemplateSourceConversionAutomatically(); //Be sure that the template source is selected
		
		var luie = PtlLayoutUIEditor.getUI();
		var is_full_source_active = luie.find(" > .options .option.show-full-source").hasClass("option-active");
		
		return is_full_source_active ? PtlLayoutUIEditor.setTemplateFullSourceEditorValue(code) : PtlLayoutUIEditor.setTemplateSourceEditorValue(code);
	}
	
	var editor = PtlLayoutUIEditor.data("editor");
	return editor ? editor.setValue(code) : main_obj.find(".layout-ui-editor > .template-source > textarea").first().val(code);
}

/* DESIGN TAB FUNCTIONS */
function addWebrootFile(elm, type, is_str_html_base, add_handler, remove_handler) {
	if (type) {
		var file_name = prompt("Please write the file name:");
		
		if (file_name) {
			//trim name
			file_name = ("" + file_name).replace(/^\s+/g, "").replace(/\s+$/g, "");
			
			//normalize new file name
			file_name = normalizeFileName(file_name, true);
			
			//set extension, but only if local file
			if (file_name.toLowerCase().substr(0, 4) != "http" && file_name.toLowerCase().substr(0, 2) != "//" && file_name.toLowerCase().substr(- (type.length + 1)) != "." + type)
				file_name += "." + type;
			
			var is_create_file = !file_name.match(/^http(s?):\/\//);
			var ul = $(elm).parent().closest(".css_files, .js_files").children("ul");
			
			if (is_create_file) {
				//prepare url
				var url = create_webroot_file_url.replace("#folder#", type).replace("#file_name#", file_name);
				
				url = encodeUrlWeirdChars(url); //Note: Is very important to add the encodeUrlWeirdChars otherwise if a value has accents, won't work in IE.
				url += (url.indexOf("?") != -1 ? "" : "?") + "&time=" + (new Date()).getTime();
				
				$.ajax({
					type : "get",
					url : url,
					success : function(data, textStatus, jqXHR) {
						if (jquery_native_xhr_object && isAjaxReturnedResponseLogin(jquery_native_xhr_object.responseURL))
							showAjaxLoginPopup(jquery_native_xhr_object.responseURL, url, function() {
								StatusMessageHandler.removeLastShownMessage("error");
								addWebrootFile(elm, type);
							});
						else if (data == "1") {
							StatusMessageHandler.showMessage("File created correctly", "", "bottom_messages", 1500);
							
							//add file based on selected_project_url_prefix
							var var_name = typeof give_priority_to_original_project_url_prefix != "undefined" && give_priority_to_original_project_url_prefix ? "original_project_url_prefix" : "project_url_prefix";
							var replacement = is_str_html_base ? "<? echo $" + var_name + " ?>" : "{$" + var_name + "}";
							var file_url = replacement + type + "/" + file_name;
							var file_code = type == "css" ? '<link rel="stylesheet" href="' + file_url + '" />' : '<script language="javascript" type="text/javascript" src="' + file_url + '"></script>';
							
							var template_file_url = is_str_html_base ? file_url : file_url.replace("{$" + var_name + "}", selected_project_url_prefix);
							var opts = {
								inline_html: !is_str_html_base,
								remove: remove_handler
							};
							var li = addCodeEditorLayoutFileInSettings(template_file_url, ul, opts);
							
							//call handler
							if (typeof add_handler == "function")
								add_handler(elm, type, file_name, file_url, file_code);
							
							//open edit popup
							li.children("a").trigger("click");
						}
						else
							StatusMessageHandler.showError("There was a problem trying to create file. Please try again..." + (data ? "\n" + data : ""));
					},
					error : function(jqXHR, textStatus, errorThrown) { 
						var msg = jqXHR.responseText ? "\n" + jqXHR.responseText : "";
						StatusMessageHandler.showError((errorThrown ? errorThrown + " error.\n" : "") + "Error trying to create file.\nPlease try again..." + msg);
					},
				});
			}
			else { //append file to html
				var file_code = type == "css" ? '<link rel="stylesheet" href="' + file_name + '" />' : '<script language="javascript" type="text/javascript" src="' + file_name + '"></script>';
				
				var opts = {
					remove: remove_handler
				};
				var li = addCodeEditorLayoutFileInSettings(file_name, ul, opts);
				
				//call handler
				if (typeof add_handler == "function")
					add_handler(elm, type, file_name, file_name, file_code);
			}
		}
		else if (typeof file_name == "string")
			StatusMessageHandler.showError("File name cannot be empty");
	}
	else
		StatusMessageHandler.showError("File type cannot be empty");
}

/* UTIL FUNCTIONS */
if (typeof flushCache != "function" && typeof flush_cache_url != "undefined" && flush_cache_url)
	function flushCache(opts) {
		opts = $.isPlainObject(opts) ? opts : {};
		var do_not_show_messages = opts["do_not_show_messages"];
		var url = flush_cache_url;
		url += (url.indexOf("?") != -1 ? "" : "?") + "&time=" + (new Date()).getTime();
		
		$.ajax({
			type : "get",
			url : url,
			success : function(data, textStatus, jqXHR) {
				if (jquery_native_xhr_object && isAjaxReturnedResponseLogin(jquery_native_xhr_object.responseURL))
					showAjaxLoginPopup(jquery_native_xhr_object.responseURL, url, function() {
						flushCache();
					});
				else if (!do_not_show_messages) {
					if (data == "1")
						taskFlowChartObj.StatusMessage.showMessage("Cache flushed!", "", "bottom_messages", 1500);
					else
						taskFlowChartObj.StatusMessage.showError("Error: Cache not flushed!\nPlease try again..." + (data ? "\n" + data : ""));
				}
			},
			error : function(jqXHR, textStatus, errorThrown) { 
				if (!do_not_show_messages) {
					var msg = jqXHR.responseText ? "\n" + jqXHR.responseText : "";
					taskFlowChartObj.StatusMessage.showError("Error: Cache not flushed!\nPlease try again..." + msg);
				}
			},
			async : opts.hasOwnProperty("async") ? opts["async"] : true
		});
		
		return false;
	}
