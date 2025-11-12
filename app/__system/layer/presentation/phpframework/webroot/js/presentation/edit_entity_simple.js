/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

var available_projects_props = null;
var available_templates_props = null;
var show_popup_interval_id = null;
var chooseProjectTemplateUrlFromFileManagerTree = null; //used by the create_presentation_uis_diagram.js and module/menu/show_menu/settings.js and others
var MyFancyPopupEditTemplateFile = new MyFancyPopupClass();
var MyFancyPopupEditWebrootFile = new MyFancyPopupClass();
var is_code_html_base = false; //sets the callbacks (like on_choose_page_url_func and on_choose_image_url_func and on_choose_webroot_file_url_func) in the edit_page_and_template.js to return the choosen file as an inline php code.
var replace_inline_project_url_php_vars = true; //set this to true so the method edit_page_and_template.js:convertProjectUrlPHPVarsToRealValues can replace the inline vars: $project_url_prefix and $project_common_url_prefix with the real url values

//var start = (new Date()).getTime();

$(function () {
	var init_finished = false;
	
	MyFancyPopup.init({
		parentElement: window,
	});
	MyFancyPopup.showOverlay();
	MyFancyPopup.showLoading();
	
	$(window).bind('beforeunload', function () {
		if (init_finished && isEntityCodeObjChanged()) {
			if (window.parent && window.parent.iframe_overlay)
				window.parent.iframe_overlay.hide();
			
			return "If you proceed your changes won't be saved. Do you wish to continue?";
		}
		
		return null;
	});
	
	//prepare top_bar
	$(".code_layout_ui_editor > .code_menu").addClass("top_bar_menu");
	$(".code_layout_ui_editor > .layout-ui-editor").addClass("with_top_bar_menu");
	
	//init trees
	chooseProjectTemplateUrlFromFileManagerTree = new MyTree({
		multiple_selection : false,
		toggle_selection : false,
		toggle_children_on_click : true,
		ajax_callback_before : prepareLayerNodes1,
		ajax_callback_after : removeAllThatIsNotTemplatesFromTree,
	});
	chooseProjectTemplateUrlFromFileManagerTree.init("choose_project_template_url_from_file_manager");
	
	//init ui
	var entity_obj = $(".entity_obj");
	
	if (entity_obj[0]) {
		//add choose_db_table_or_attribute popup before the LayouUIEditor gets inited otherwise the choose-widget-resource icons won't be shown.
		if (typeof choose_db_table_or_attribute_elm != "undefined" && choose_db_table_or_attribute_elm)
			entity_obj.append(choose_db_table_or_attribute_elm);
		
		//prepare main settings tab
		var regions_blocks_includes_settings = entity_obj.find(".regions_blocks_includes_settings");
		regions_blocks_includes_settings.tabs();
		
		setTimeout(function() {
			//init template list
			initTemplatesList(entity_obj);
			
			//prepare advanced entity settings
			initPageAdvancedSettings( regions_blocks_includes_settings.find(".advanced_settings") );
			
			//init sla
			initPageAndTemplateLayoutSLA(regions_blocks_includes_settings);
			
			//load sla settings
			loadPageAndTemplateLayoutSLASettings(regions_blocks_includes_settings, false);
			
			init_finished = true;
		}, 10);
		
		//init page template layout
		initPageAndTemplateLayout(entity_obj, {
			save_func: saveEntity, 
			ready_func: function() {
				//console.log("initPageAndTemplateLayout ready_func");
				
				//add top bar menu: Show/Hide Side Bar DBs Panel
				addCodeLayoutUIEditorRightContainerDBsMenu( $(".top_bar li.sub_menu li.toggle_main_settings") );
				
				//prepare some PtlLayoutUIEditor options
				var luie = entity_obj.find(".code_layout_ui_editor > .layout-ui-editor");
				var PtlLayoutUIEditor = luie.data("LayoutUIEditor");
				
				//set on_template_widgets_iframe_reload_func so everytime the template layout is changed or reload we update the css and js files.
				PtlLayoutUIEditor.options.on_template_widgets_iframe_reload_func = function() {
					//reload js and css files
					loadPageCodeEditorLayoutJSAndCSSFilesToSettings();
				};
				
				luie.find(" > .options > .options-left").append('<i class="zmdi zmdi-view-compact option choose-template" title="Switch Theme Template"></i>').children(".choose-template").click(function() {
					onChooseAvailableTemplate(true);
				});
				
				//DEPRECATED - waits until the load params and joinpoints gets loaded. No need this anymorebc the initPageAndTemplateLayout method already covers this case.
				//setTimeout(function() {
					//load js and css files
					loadPageCodeEditorLayoutJSAndCSSFilesToSettings();
					
					var func = function() {
						if (init_finished) {
							//set saved_obj_id
							saved_obj_id = getEntityCodeObjId();
							
							//init auto save
							addAutoSaveMenu(".top_bar li.sub_menu li.save");
							//enableAutoSave(onToggleSLAAutoSave); //Do not enable auto save bc it gets a litte bit slow editing the template.
							initAutoSave(".top_bar li.sub_menu li.save a");
							StatusMessageHandler.showMessage("Auto save is disabled for better user-experience...", "", "bottom_messages", 10000);
							
							//change the toggle Auto save handler bc the edit_query task
							initSLAAutoSaveActivationMenu();
							
							//set update handlers
							var iframe = getContentTemplateLayoutIframe(entity_obj);
							
							update_settings_from_layout_iframe_func = function() {
								//console.log("updateSettingsFromLayout");
								updateSettingsFromLayout(entity_obj);
							};
							update_layout_iframe_from_settings_func = function() {
								//console.log("updateLayoutFromSettings");
								updateLayoutFromSettings(entity_obj, false);
							};
							update_layout_iframe_field_html_value_from_settings_func = function(elm, html) { //set handler to update directly the the html in the template layout without refreshing the entire layout.
								//console.log("updateLayoutIframeRegionBlockHtmlFromSettingsHtmlField");
								updateLayoutIframeRegionBlockHtmlFromSettingsHtmlField(elm, html, iframe);
								
								//update css and js files from region blocks
								loadPageRegionsBlocksHtmlJSAndCSSFilesToSettings();
							};
							
							var droppables_exist = iframe.contents().find(".droppable:not(.edit_entity_droppable_disabled)").length > 0;
							
							if (!droppables_exist)
								StatusMessageHandler.showMessage("Looks like this template has no droppables to drag widgets. We recommend you to choose another template.", "", "bottom_messages", 10000);
							
							if (typeof invalid_msg != "undefined" && invalid_msg)
								StatusMessageHandler.showMessage(invalid_msg, "warning", "bottom_messages", 10000);
							
							//hide loading icon
							MyFancyPopup.hidePopup();
							
							//var end = (new Date()).getTime();
							//console.log("loading time: "+((end-start)/1000)+" secs");
						}
						else
							setTimeout(function() {
								func();
							}, 700);
					};
					func();
					
					entity_or_template_obj_inited = true;
				//}, 2000);
			}
		});
		
		if (!code_exists)
			onChooseAvailableTemplate(show_templates_only, true ); //open template popup automatically if entity is new
	}
});

//To be used in the toggleFullScreen function
function onToggleFullScreen(in_full_screen) {
	var entity_obj = $(".entity_obj");
	onToggleCodeEditorFullScreen(in_full_screen, entity_obj);
	
	setTimeout(function() {
		var top = parseInt(entity_obj.find(".regions_blocks_includes_settings").css("top"));
		
		resizeSettingsPanel(entity_obj, top);
	}, 500);
}

/* CHOOSE TEMPLATE FUNCTIONS */

function initTemplatesList(entity_obj) {
	$.ajax({
		type : "get",
		url : get_available_templates_list_url,
		dataType : "json",
		success : function(data, textStatus, jqXHR) {
			//console.log(data);
			var templates_select = entity_obj.find(" > .template select[name=template]");
			var current_template = templates_select.val();
			var html = '<option value="">-- DEFAULT --</option>';
			
			$.each(data, function(idx, template_id) {
				html += '<option value="' + template_id + '">' + template_id + '</option>';
			});
			
			templates_select.html(html);
			templates_select.val(current_template);
		},
		error : function(jqXHR, textStatus, errorThrown) { 
			if (jqXHR.responseText);
				StatusMessageHandler.showError(jqXHR.responseText);
		},
	});
}

function removeAllThatIsNotTemplatesFromTree(ul, data) {
	ul = $(ul);
	
	ul.find("i.file, i.entity_file, i.view_file, i.util_file, i.controller_file, i.config_file, i.undefined_file, i.js_file, i.css_file, i.img_file, i.properties, i.block_file, i.module_file, .entities_folder, .views_folder, .utils_folder, .webroot_folder, .modules_folder, .blocks_folder, .configs_folder").each(function(idx, elm){
		$(elm).parent().parent().remove();
	});
	
	ul.find("i.folder").each(function(idx, elm){
		var label = $(elm).parent().children("label").text();
		
		if (label == "pages (entities)" || label == "views" || label == "utils" || label == "webroot" || label == "others" || label == "modules" || label == "blocks" || label == "configs") 
			$(elm).parent().parent().remove();
		//else if (label == "templates") 
		//	$(elm).parent().parent().addClass("jstree-last");
	});
	
	//move templates to project node
	ul.find("i.templates_folder").each(function(idx, elm) {
		var templates_li = $(elm).parent().parent();
		var templates_ul = templates_li.children("ul");
		var project_li = templates_li.parent().parent();
		var project_ul = project_li.children("ul");
		
		project_li.append(templates_ul);
		project_ul.remove();
	});
}

function onChangeTemplate(elm) {
	elm = $(elm);
	elm.attr("title", elm.val());
	
	//update template layout ui
	updateTemplateLayout(elm.parent().parent());
}

function onChangeTemplateGenre(elm) {
	elm = $(elm);
	var is_external_template = elm.val() ? true : false;
	var p = elm.parent();
	var entity_obj = p.parent();
	var top_bar = $(".top_bar");
	var select = p.children("select[name=template]");
	var external_template_params = entity_obj.children(".external_template_params");
	var external_template_params_toggle_btn = p.children(".external_template_params_toggle_btn");
	var template_value = null;
	
	if (!is_external_template) {
		entity_obj.removeClass("is_external_template");
		top_bar.removeClass("is_external_template");
		template_value = select.val();
		
		select.show();
		external_template_params.hide();
		external_template_params_toggle_btn.hide();
		
		//update template layout ui
		updateTemplateLayout(entity_obj);
	}
	else {
		entity_obj.addClass("is_external_template");
		top_bar.addClass("is_external_template");
		select.hide();
		external_template_params.show();
		external_template_params_toggle_btn.show();
		
		onChangeExternalTemplateType( external_template_params.find(".external_template_type select")[0] );
	}
}

function onChooseAvailableTemplate(show_templates_only, include_template_samples) {
	var entity_obj = $(".entity_obj");
	var template_elm = entity_obj.children(".template").first();
	var cat_select = template_elm.children("select[name=template]");
	var chosen_template = cat_select.val(); //Do not use the getSelectedTemplate(entity_obj) bc we only want the selected template without the layer_default_template
	var chosen_project = null;
	
	if (isExternalTemplate(entity_obj)) {
		var external_template_params = entity_obj.children(".external_template_params");
		chosen_template = external_template_params.find(" > .template_id input").val();
		chosen_project = external_template_params.find(" > .external_project_id input").val();
	}
	
	var func = function(selected_template) {
		if (!code_exists) { //only if file is new
			var iframe = getContentTemplateLayoutIframe(entity_obj);
			var is_not_saved_layout_empty = true;
			
			//if meanwhile the user added some widgets to the layout, then the settings may not be updated bc the auto_convert_settings_from_layout may be false. So in this case, do not call the updateLayoutFromSettings and automatically the new widgets will appear in the new template bc the updateTemplateLayout method will be triggered by the selectAvailableTemplate method in the choose_available_template.js, that triggers the onChange event from the select field.
			if (!auto_convert_settings_from_layout && iframe[0] && iframe[0].contentWindow && typeof iframe[0].contentWindow.getTemplateRegionsBlocks == "function") {
				var iframe_data = iframe[0].contentWindow.getTemplateRegionsBlocks();
				
				if (iframe_data["regions_blocks"] && iframe_data["regions_blocks"].length > 0)
					is_not_saved_layout_empty = false;
			}
			
			if (is_not_saved_layout_empty)
				updateLayoutFromSettings(entity_obj, true);
		}
	};
	var available_projects_templates_props = {};
	available_projects_templates_props[selected_project_id] = available_templates_props;
	
	var cat_props = {
		show_templates_only: show_templates_only,
		include_template_samples: include_template_samples,
		available_projects_templates_props: available_projects_templates_props,
		available_projects_props: available_projects_props,
		get_available_templates_props_url: get_available_templates_props_url,
		install_template_url: install_template_url,
		project_global_variables_url: project_global_variables_url,
		chosen_template: chosen_template,
		chosen_project: chosen_project,
		
		on_install: function(project_id, installed_templates) {
			if (installed_templates && project_id == selected_project_id) {
				//update the list of new templates in cat_select
				var curr_templates = [];
				var options = cat_select.find("option");
				
				$.each(options, function(idx, option) {
					curr_templates.push(option.value);
				});
				
				$.each(installed_templates, function(template_id, template_data) {
					if ($.inArray(template_id, curr_templates) == -1)
						cat_select.append('<option value="' + template_id + '">' + template_id + '</option>');
				});
				
				//refresh the template list in the navigator tree of the parent window
				if (window.parent != window && typeof window.parent.refreshOpenNodeChildsBasedInPath == "function")
					window.parent.refreshOpenNodeChildsBasedInPath(selected_project_id + "/src/template/");
			}
		},
		
		on_select: function(selected_template, opts) {
			//add template samples, if apply
			if ($.isPlainObject(opts) && opts["include_template_samples"] && selected_template)
				addTemplateSamples(entity_obj, selected_template, opts["include_template_samples"], opts["include_template_samples_in_regions"]);
			
			//trigger change
			var template_genre = template_elm.children("select[name=template_genre]");
			
			if (template_genre.val() != "") {
				template_genre.val("");
				template_genre.trigger("change");
				
				cat_select.trigger("change"); //must execute again this trigger bc when this trigger was executed in the chage_available_template.js, the template_genre was external type, and so the template was not correctly updated in the canvas. So we need to call it again
			}
			
			//execute refresh template if new entity
			func(selected_template);
		},
		on_select_from_other_project: function(selected_template, choose_template_selected_project_id) {
			var template_genre = template_elm.children("select[name=template_genre]");
			template_genre.val("external_template");
			template_genre.trigger("change");
			
			var external_template_params = entity_obj.children(".external_template_params");
			var external_template_type = external_template_params.find(" > .external_template_type select");
			external_template_type.val("project");
			external_template_type.trigger("change");
			
			var external_template_id = external_template_params.find(" > .template_id input");
			external_template_id.val(selected_template);
			
			var external_project_id = external_template_params.find(" > .external_project_id input");
			external_project_id.val(choose_template_selected_project_id);
			
			var keep_original_project_url_prefix = external_template_params.find(" > .keep_original_project_url_prefix input");
			keep_original_project_url_prefix.attr("checked", "checked").prop("checked", true);
			
			external_project_id.trigger("blur");
			
			func(selected_template);
		}
	};
	
	//init available_projects_props
	if (!available_templates_props && get_available_templates_props_url) {
		MyFancyPopup.showLoading();
		
		$.ajax({
			type : "get",
			url : get_available_templates_props_url.replace("#path#", entity_path),
			dataType : "json",
			success : function(data, textStatus, jqXHR) {
				//console.log(data);
				available_templates_props = data;
				cat_props["available_projects_templates_props"][selected_project_id] = data;
				
				MyFancyPopup.hideLoading();
			},
			error : function(jqXHR, textStatus, errorThrown) { 
				if (jqXHR.responseText);
					StatusMessageHandler.showError(jqXHR.responseText);
				
				MyFancyPopup.hideLoading();
			},
			async: false
		});
	}
	
	//init available_projects_props
	if (!available_projects_props && get_available_projects_props_url) {
		MyFancyPopup.showLoading();
		
		$.ajax({
			type : "get",
			url : get_available_projects_props_url,
			dataType : "json",
			success : function(data, textStatus, jqXHR) {
				//console.log(data);
				available_projects_props = data;
				cat_props["available_projects_props"] = data;
				
				MyFancyPopup.hideLoading();
			},
			error : function(jqXHR, textStatus, errorThrown) { 
				if (jqXHR.responseText);
					StatusMessageHandler.showError(jqXHR.responseText);
				
				MyFancyPopup.hideLoading();
			},
			async: false
		});
	}
	
	chooseAvailableTemplate(cat_select[0], cat_props);
}

function addTemplateSamples(entity_obj, template, include_template_samples, include_template_samples_in_regions) {
	var regions_blocks_includes_settings = entity_obj.find(".regions_blocks_includes_settings");
	var data = getSettingsTemplateRegionsBlocks(regions_blocks_includes_settings);
	var template_regions = data["template_regions"];
	var ignore_regions = [];
	
	if (template_regions) {
		for (var region in template_regions) {
			var regions = template_regions[region];
			
			if (include_template_samples_in_regions == "empty" && $.isArray(regions) && regions.length > 0) //include_template_samples_in_regions == "empty" => only in regions that are empty
				ignore_regions.push(region);
		}
	
		//get template samples
		var samples = getTemplateSamplesHtml(template);
		
		//console.log(regions_blocks_list);
		//console.log(data);
		//console.log(samples);
		
		//add that template samples where region is not inside of ignore_regions
		if (samples) {
			var region_blocks = regions_blocks_includes_settings.find(".region_blocks .template_region_items");
			var other_region_blocks = regions_blocks_includes_settings.find(".other_region_blocks .template_region_items");
			
			for (var region in samples)
				if (ignore_regions.indexOf(region) == -1) {
					var region_samples = samples[region];
					
					if (region_samples) {
						for (var i = 0, t = region_samples.length; i < t; i++) {
							var sample_code = region_samples[i];
							
							//prepare sample_code
							if (sample_code) {
								//replace urls with right urls
								sample_code = sample_code.replace(/<\?((php)?\s+(echo|print)|=)\s+\$(original_project_url_prefix|project_url_prefix)\s*;?\s*\?>/g, '{$project_url_prefix}');
								sample_code = sample_code.replace(/<\?((php)?\s+(echo|print)|=)\s+\$(original_project_common_url_prefix|project_common_url_prefix)\s*;?\s*\?>/g, '{$project_common_url_prefix}');
								
								//add new html to region
								var block = sample_code;
								var proj = null;
								var type = 1; //is html
								var rb_index = 0;
								var rb_html = getRegionBlockHtml(region, block, proj, type, rb_index);
								
								if (template_regions.hasOwnProperty(region))
									region_blocks.append(rb_html);
								else
									other_region_blocks.append(rb_html);
								
								regions_blocks_list.push([region, block, proj, type, rb_index]);
							}
						}
					}
				}
		}
	}
}

function getTemplateSamplesHtml(template) {
	var url = get_template_regions_samples_url.replace("#template#", template);
	var samples = null;
	
	StatusMessageHandler.showMessage("Loading template region samples...", "", "bottom_messages", 3000);
	
	$.ajax({
		type : "get",
		url : url,
		dataType : "json",
		success : function(data, textStatus, jqXHR) {
			samples = data;
			
			StatusMessageHandler.removeLastShownMessage("info", "bottom_messages");
		},
		error : function(jqXHR, textStatus, errorThrown) { 
			StatusMessageHandler.removeLastShownMessage("info", "bottom_messages");
			
			if (jqXHR.responseText);
				StatusMessageHandler.showError(jqXHR.responseText);
		},
		async: false
	});
	
	return samples;
}

function toggleExternalTemplateParams(elm) {
	elm = $(elm);
	elm.toggleClass("dropdown_arrow dropup_arrow");
	elm.parent().closest(".entity_obj").children(".external_template_params").toggleClass("collapsed");
}

function onChangeExternalTemplateType(elm) {
	elm = $(elm);
	var external_template_type = elm.val();
	var external_template_params = elm.parent().parent();
	
	external_template_params.children(":not(.external_template_type)").hide();
	
	if (external_template_type)
		external_template_params.find(".external_template_params_toggle_btn").show();
	else
		external_template_params.find(".external_template_params_toggle_btn").hide();
	
	if (external_template_type == "project")
		external_template_params.children(".project_param").show();
	else if (external_template_type == "block")
		external_template_params.children(".block_param").show();
	else if (external_template_type == "wordpress_template")
		external_template_params.children(".wordpress_template_param").show();
	else if (external_template_type == "url")
		external_template_params.children(".url_param").show();
	
	//update template layout ui
	updateTemplateLayout( external_template_params.parent() );
}

function onChooseProjectTemplate(elm) {
	var p = $(elm).parent();
	var popup = $("#choose_project_template_url_from_file_manager");
	
	MyFancyPopup.init({
		elementToShow: popup,
		parentElement: document,
		
		targetField: p,
		updateFunction: chooseProjectTemplateFile
	});
	
	MyFancyPopup.showPopup();
}

function chooseProjectTemplateFile(elm) {
	var node = chooseProjectTemplateUrlFromFileManagerTree.getSelectedNodes();
	node = node[0];
	
	if (node) {
		var a = $(node).children("a");
		var file_path = a.attr("file_path");
		var bean_name = a.attr("bean_name");
		var pos = file_path ? file_path.indexOf("/src/template/") : -1;
		var is_template = a.children("i").first().is(".template_file");
		
		if (file_path && pos != -1 && is_template) {
			var project_path = getNodeProjectPath(node);
			project_path = project_path && project_path.substr(project_path.length - 1) == "/" ? project_path.substr(0, project_path.length - 1) : project_path;
			project_path = project_path == selected_project_id ? "" : project_path;
			
			var template_path = file_path.substr(pos + ("/src/template/").length);//14 == /src/template/
			template_path = template_path.substr(template_path.length - 4, 1) == "." ? template_path.substr(0, template_path.lastIndexOf(".")) : template_path;
			
			var p = MyFancyPopup.settings.targetField;
			p.children("input").val(template_path);
			p.parent().find(".external_project_id input").val(project_path);
			
			//update template layout ui
			updateTemplateLayout( p.parent().parent() );
			
			MyFancyPopup.hidePopup();
		}
		else
			alert("invalid selected template file.\nPlease choose a valid template file.");
	}
}

function onChooseBlockTemplate(elm) {
	elm = $(elm);
	var p = elm.parent();
	var popup = $("#choose_block_from_file_manager");
	
	MyFancyPopup.init({
		elementToShow: popup,
		parentElement: document,
		
		targetField: p,
		updateFunction: chooseBlockTemplate
	});

	MyFancyPopup.showPopup();
}

function chooseBlockTemplate(elm) {
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
			
			var p = MyFancyPopup.settings.targetField;
			p.children("input").val(block_path);
			p.parent().find(".external_project_id input").val(project_path);
			
			//update template layout ui
			updateTemplateLayout( p.parent().parent() );
			
			MyFancyPopup.hidePopup();
		}
		else
			alert("invalid selected file.\nPlease choose a valid file.");
	}
}

function onBlurExternalTemplate(elm) {
	//update template layout ui
	updateTemplateLayout( $(elm).parent().closest(".entity_obj") );
}

function updateTemplateLayout(entity_obj) {
	StatusMessageHandler.showMessage("Loading template...", "", "bottom_messages", 5000);
	
	var func = function() {
		//close the widget properties if open, otherwise we will loose the reference for the selected widget in the LayoutUIEditor when the page gets reloaded. Do this by clicking in the body, which disables the selected widget automatically.
		var PtlLayoutUIEditor = entity_obj.find(".code_layout_ui_editor .layout-ui-editor").data("LayoutUIEditor");
		
		if (PtlLayoutUIEditor)
			PtlLayoutUIEditor.getTemplateWidgetsIframeBody().trigger("click");
		
		//save synchronization functions
		var update_settings_from_layout_iframe_func_bkp = update_settings_from_layout_iframe_func;
		var update_layout_iframe_from_settings_func_bkp = update_layout_iframe_from_settings_func;
		var update_layout_iframe_field_html_value_from_settings_func_bkp = update_layout_iframe_field_html_value_from_settings_func;
		
		//disable synchronization functions in case some call recursively by mistake
		update_settings_from_layout_iframe_func = null;
		update_layout_iframe_from_settings_func = null;
		update_layout_iframe_field_html_value_from_settings_func = null;
		
		//prepare new template load
		var iframe = getContentTemplateLayoutIframe(entity_obj);
		var template = getSelectedTemplate(entity_obj);
		var is_template_ok = template ? true : false;
		
		if (template == "parse_php_code") {
			var external_template_params = entity_obj.children(".external_template_params");
			var external_template_type = external_template_params.find(" > .external_template_type select").val();
			
			if (external_template_type == "project" && external_template_params.find(" > .template_id input").val() == "")
				is_template_ok = false;
			else if (external_template_type == "block" && external_template_params.find(" > .block_id input").val() == "")
				is_template_ok = false;
			else if (external_template_type == "url" && external_template_params.find(" > .url input").val() == "")
				is_template_ok = false;
			else if (external_template_type == "")
				is_template_ok = false;
		}
		
		if (is_template_ok) {
			var is_external_template = isExternalTemplate(entity_obj) ? 1 : 0;
			var external_template_params = getExternalSetTemplateParams(entity_obj);
			
			var regions_blocks_includes_settings = entity_obj.find(".regions_blocks_includes_settings");
			var data = getSettingsTemplateRegionsBlocks(regions_blocks_includes_settings);
			var template_includes = data["includes"];
			
			var url = get_template_regions_and_params_url.replace(/#template#/g, template) + "&is_external_template=" + is_external_template + "&external_template_params=" + encodeURIComponent(JSON.stringify(external_template_params)) + "&template_includes=" + encodeURIComponent(JSON.stringify(template_includes));
			
			$.ajax({
				type : "get",
				url : url,
				dataType : "json",
				success : function(data, textStatus, jqXHR) {
					//console.log(data);
					
					//remove loading message
					StatusMessageHandler.removeLastShownMessage("info", "bottom_messages");
					
					//update regions_blocks_list and template_params_values_list
					var settings = getSettingsTemplateRegionsBlocks( entity_obj.find(".entity_template_settings") );
					regions_blocks_list = settings["regions_blocks"]; //global var
					template_params_values_list = settings["params"]; //global var
					var template_includes = settings["includes"];
					
					defined_regions_list = data["defined_regions"]; //global var
					defined_template_params_values = data["params_values"]; //global var
					
					//update settings with data
					updateSelectedTemplateRegionsBlocks(entity_obj, data);
					
					//show iframe with new regions and params
					var regions = data["regions"];
					var template_regions = {};
					
					if (regions)
						for (var i in regions) {
							var region = regions[i];
							template_regions[region] = "";
							
							for (var j in regions_blocks_list) {
								var rbl = regions_blocks_list[j];
								
								if (rbl[0] == region) {
									if (!$.isArray(template_regions[region]))
										template_regions[region] = [];
									
									template_regions[region].push(rbl);
								}
							}
						}
					
					reloadLayoutIframeFromSettings(iframe, {
						"template": template,
						"template_regions" : template_regions,
						"template_params": template_params_values_list,
						"template_includes": template_includes,
						"is_external_template": is_external_template,
						"external_template_params": external_template_params,
					});
					
					//sets back synchronization functions
					update_settings_from_layout_iframe_func = update_settings_from_layout_iframe_func_bkp;
					update_layout_iframe_from_settings_func = update_layout_iframe_from_settings_func_bkp;
					update_layout_iframe_field_html_value_from_settings_func = update_layout_iframe_field_html_value_from_settings_func_bkp;
				},
				error : function(jqXHR, textStatus, errorThrown) {
					StatusMessageHandler.removeLastShownMessage("info", "bottom_messages"); //remove loading message
					
					if (jqXHR.responseText);
						StatusMessageHandler.showError(jqXHR.responseText);
					
					//sets back synchronization functions
					update_settings_from_layout_iframe_func = update_settings_from_layout_iframe_func_bkp;
					update_layout_iframe_from_settings_func = update_layout_iframe_from_settings_func_bkp;
					update_layout_iframe_field_html_value_from_settings_func = update_layout_iframe_field_html_value_from_settings_func_bkp;
				}
			});
		}
		else {
			StatusMessageHandler.removeLastShownMessage("info", "bottom_messages"); //remove loading message
			
			//update new template
			updateSelectedTemplateRegionsBlocks(entity_obj, null);
			reloadLayoutIframeFromSettings(iframe, {template: ""});
			
			//sets back synchronization functions
			update_settings_from_layout_iframe_func = update_settings_from_layout_iframe_func_bkp;
			update_layout_iframe_from_settings_func = update_layout_iframe_from_settings_func_bkp;
			update_layout_iframe_field_html_value_from_settings_func = update_layout_iframe_field_html_value_from_settings_func_bkp;
		}
	};	
	
	//console.log("auto_convert_settings_from_layout:"+auto_convert_settings_from_layout);
	
	if (!auto_convert_settings_from_layout) {
		enableAutoConvertSettingsFromLayout(function() {
			func();
		});
		disableAutoConvertSettingsFromLayout();
	}
	else
		func();
}

function getSelectedTemplate(entity_obj) {
	var template = "";
	var is_external_template = isExternalTemplate(entity_obj);
	
	if (is_external_template) 
		template = "parse_php_code";
	else {
		template = entity_obj.find(" > .template select[name=template]").val();
		template = template ? template : layer_default_template;
	}
	
	return template;
}

function isExternalTemplate(entity_obj) {
	return entity_obj.find(" > .template > select[name=template_genre]").val() ? true : false;
}

function editCurrentTemplateFile(elm) {
	var entity_obj = $(".entity_obj");
	var template = getSelectedTemplate(entity_obj);
	var url = null;
	
	if (template == "parse_php_code") {
		var template_args = getExternalTemplateParams(entity_obj);
		//console.log(template_args);
		
		if (template_args["type"] == "project" && template_args["template_id"]) {
			var path = (template_args["external_project_id"] ? template_args["external_project_id"] : selected_project_id) + "/src/template/" + template_args["template_id"] + ".php";
			url = edit_template_file_url.replace("#path#", path);
		}
		else if (template_args["type"] == "block" && template_args["block_id"]) {
			var path = (template_args["external_project_id"] ? template_args["external_project_id"] : selected_project_id) + "/src/block/" + template_args["block_id"] + ".php";
			url = edit_block_url.replace("#path#", path);
		}
	}
	else {
		var path = selected_project_id + "/src/template/" + (template ? template : layer_default_template) + ".php";
		url = edit_template_file_url.replace("#path#", path);
	}
	
	if (!url)
		alert("Cannot edit this template through here. Please go directly to the file path through the navigator panel.");
	else {
		url += (url.indexOf("?") != -1 ? "&" : "?") + "popup=1";
		
		//prepare popup
		var entity_obj = $(".entity_obj");
		var popup = entity_obj.children("#edit_template_file");
		
		if (!popup[0]) {
			popup = $('<div id="edit_template_file" class="myfancypopup with_iframe_title"><iframe></iframe></div>');
			entity_obj.append(popup);
		}
		else 
			popup.html('<iframe></iframe>');
		
		var iframe = popup.children("iframe");
		
		iframe.attr("src", url);
		//console.log(url);
		
		//open popup
		MyFancyPopupEditTemplateFile.init({
			elementToShow: popup,
			parentElement: document,
			
			onClose: function() {
				updateTemplateLayout(entity_obj);
				
				popup.children("iframe").remove();
			},
		});
		
		MyFancyPopupEditTemplateFile.showPopup();
	}
}

function editWebrootFile(path) {
	//prepare popup
	var entity_obj = $(".entity_obj");
	var popup = entity_obj.children("#edit_webroot_file");
	
	if (!popup[0]) {
		popup = $('<div id="edit_webroot_file" class="myfancypopup with_iframe_title"><iframe></iframe></div>');
		entity_obj.append(popup);
	}
	else 
		popup.html('<iframe></iframe>');
	
	var iframe = popup.children("iframe");
	var url = edit_webroot_file_url.replace("#path#", path);
	
	iframe.attr("src", url);
	
	//open popup
	MyFancyPopupEditWebrootFile.init({
		elementToShow: popup,
		parentElement: document,
		
		onClose: function() {
			updateTemplateLayout(entity_obj);
		},
	});
	
	MyFancyPopupEditWebrootFile.showPopup();
}

function getTemplateHeadEditorCode() {
	var code_layout_ui_editor = $(".entity_obj .code_layout_ui_editor");
	var PtlLayoutUIEditor = code_layout_ui_editor.find(".layout-ui-editor").data("LayoutUIEditor");
	
	if (PtlLayoutUIEditor) {
		//disable beauty in PtlLayoutUIEditor so it can get the code faster
		var beautify = PtlLayoutUIEditor.options.beautify;
		PtlLayoutUIEditor.options.beautify = false; //This will make the system 3 secs faster everytime this function gets called.
		
		//remove active class so the getCodeLayoutUIEditorCode calls the getTemplateSourceEditorValue method, instead of getTemplateFullSourceEditorValue
		var luie = PtlLayoutUIEditor.getUI();
		luie.find(" > .options .option.show-full-source").addClass("option-active"); 
	
		var code = getCodeLayoutUIEditorCode(code_layout_ui_editor);
		
		//set original beauty
		PtlLayoutUIEditor.options.beautify = beautify;
		
		//filter head 
		if (PtlLayoutUIEditor.existsTagFromSource(code, "head"))
			return PtlLayoutUIEditor.getTagContentFromSource(code, "head"); 
	}
	
	return "";
}

function getTemplateBodyEditorCode() {
	var code_layout_ui_editor = $(".entity_obj .code_layout_ui_editor");
	var PtlLayoutUIEditor = code_layout_ui_editor.find(".layout-ui-editor").data("LayoutUIEditor");
	
	if (PtlLayoutUIEditor) {
		//disable beauty in PtlLayoutUIEditor so it can get the code faster
		var beautify = PtlLayoutUIEditor.options.beautify;
		PtlLayoutUIEditor.options.beautify = false; //This will make the system 3 secs faster everytime this function gets called.
		
		//remove active class so the getCodeLayoutUIEditorCode calls the getTemplateSourceEditorValue method, instead of getTemplateFullSourceEditorValue
		var luie = PtlLayoutUIEditor.getUI();
		luie.find(" > .options .option.show-full-source").removeClass("option-active"); 
	}
	
	var code = getCodeLayoutUIEditorCode(code_layout_ui_editor);
	
	//set original beauty
	if (PtlLayoutUIEditor)
		PtlLayoutUIEditor.options.beautify = beautify;
	
	return code;
}

/* LAYOUT-SETTINGS-LAYOUT UPDATE FUNCTIONS */

function updateLayoutFromSettings(entity_obj, reload_iframe) {
	if (entity_obj[0] && !entity_obj.hasClass("inactive")) {
		var orig_template_params_values_list = JSON.stringify(template_params_values_list);
		var orig_includes_list = JSON.stringify(includes_list);
		
		var iframe = getContentTemplateLayoutIframe(entity_obj);
		var regions_blocks_includes_settings = entity_obj.find(".regions_blocks_includes_settings");
		
		updateRegionsBlocksRBIndexIfNotSet(regions_blocks_includes_settings); //very important, otherwise we will loose the region-block params-values and joinpoints for the new regions added dynamically
		
		//very important, otherwise we will loose the region-block params-values and joinpoints
		updateRegionsBlocksParamsLatestValues(regions_blocks_includes_settings); 
		updateRegionsBlocksJoinPointsSettingsLatestObjs(regions_blocks_includes_settings);
		
		var are_different = areLayoutAndSettingsDifferent(iframe, regions_blocks_includes_settings, true);
		var data = getSettingsTemplateRegionsBlocks(regions_blocks_includes_settings);
		//console.log(data);
		
		if (!are_different)
			are_different = orig_template_params_values_list != JSON.stringify(data["params"]);
		
		if (!are_different)
			are_different = orig_includes_list != JSON.stringify(data["includes"]);
		
		if (are_different /*&& confirm("Do you wish to convert the template settings to the layout panel?")*/) {
			var template = getSelectedTemplate(entity_obj);
			var iframe_data = {
				"template": template,
				"template_regions" : data["template_regions"],
				"template_params": data["params"],
				"template_includes": data["includes"],
				"is_external_template": isExternalTemplate(entity_obj) ? 1 : 0,
				"external_template_params": getExternalSetTemplateParams(entity_obj)
			};
			
			if (reload_iframe)
				reloadLayoutIframeFromSettings(iframe, iframe_data);
			else
				updateLayoutIframeFromSettings(iframe, iframe_data, data);
			
			//update regions_blocks_list
			regions_blocks_list = data["regions_blocks"];
			
			//update template_params_values_list
			template_params_values_list = data["params"];
			
			//update includes_list
			includes_list = data["includes"];
			
			//update css and js files from region blocks
			loadPageRegionsBlocksHtmlJSAndCSSFilesToSettings();
		}
	}
}

function updateSettingsFromLayout(entity_obj) {
	if (!entity_obj.hasClass("inactive")) {
		var iframe = getContentTemplateLayoutIframe(entity_obj);
		var regions_blocks_includes_settings = entity_obj.find(".regions_blocks_includes_settings");
		var are_different = areLayoutAndSettingsDifferent(iframe, regions_blocks_includes_settings, true);
		
		if (are_different /*&& confirm("Do you wish to convert the template regions to the settings panel?")*/) {
			var data = getIframeTemplateRegionsBlocksForSettings(iframe, regions_blocks_includes_settings);
			
			regions_blocks_list = data["regions_blocks_list"]; //global var
			template_params_values_list = data["template_params_values_list"]; //global var
			
			updateSelectedTemplateRegionsBlocks(regions_blocks_includes_settings, {
				regions: data["regions"], 
				params: data["params"], 
			});
			
			//update css and js files from region blocks
			loadPageRegionsBlocksHtmlJSAndCSSFilesToSettings();
		}
	}
}

function loadPageCodeEditorLayoutJSAndCSSFilesToSettings() {
	var opts = {
		inline_html: true,
	};
	loadCodeEditorLayoutJSAndCSSFilesToSettings(opts);
	
	loadPageRegionsBlocksHtmlJSAndCSSFilesToSettings();
}

function loadPageRegionsBlocksHtmlJSAndCSSFilesToSettings() {
	//remove all files coming from region blocks html
	var regions_blocks_includes_settings = $(".regions_blocks_includes_settings");
	regions_blocks_includes_settings.find(".css_files, js_files").find(" > ul > li.from_region_block").remove();
	
	var region_opts = {
		inline_html: true,
		force: true,
		remove: removePageWebrootFile,
		create: function(file, li, func_opts) {
			li.addClass("from_region_block");
		}
	};
	appendRegionsBlocksHtmlFileInSettings(null, region_opts);
}

function addPageWebrootFile(elm, file_type, file_name, file_url, file_code) {
	var entity_obj= $(".entity_obj");
	var regions_blocks_includes_settings = entity_obj.find(".regions_blocks_includes_settings");
	var data = getSettingsTemplateRegionsBlocks(regions_blocks_includes_settings);
	var template_regions = data["template_regions"];
	
	if (template_regions) {
		var selected_region = null;
		
		for (var region in template_regions) {
			var regions = template_regions[region];
			var r = region.substr(0, 1) == '"' ? region.replace(/"/g, "") : region;
			
			if (r.toLowerCase() == "head") {
				selected_region = region;
				break;
			}
			else
				selected_region = region;
		}
		
		if (selected_region) {
			//add new html to region
			var block = file_code;
			var proj = null;
			var type = 1; //is html
			var rb_index = 0;
			var rb_html = getRegionBlockHtml(selected_region, block, proj, type, rb_index);
			
			var region_blocks = regions_blocks_includes_settings.find(".region_blocks .template_region_items");
			region_blocks.append(rb_html);
			
			regions_blocks_list.push([selected_region, block, proj, type, rb_index]);
			
			updateLayoutFromSettings(entity_obj, true);
		}
		else
			StatusMessageHandler.showError("Cannot add new file because this template doesn't contain any region defined.");
	}
}

function removePageWebrootFile(elm, file) {
	//disalbed the update_layout_iframe_from_settings_func, bc the reload_iframe argument in the updateLayoutFromSettings method is false. And we want to force to reload the iframe, so we need to call below this method directly.	
	
	//save synchronization functions
	var update_layout_iframe_from_settings_func_bkp = update_layout_iframe_from_settings_func;
	
	//disable synchronization functions bc the updateSelectedTemplateRegionsBlocks calls the sync func, when it triggers the on change event from the blok_type in the getRegionBlockHtml
	update_layout_iframe_from_settings_func = null;
	
	//check if file exists in head region also, but do not refresh iframe.
	var status = removeFileFromRegionBlockHtml(file);
	
	//sets back synchronization functions
	update_layout_iframe_from_settings_func = update_layout_iframe_from_settings_func_bkp;
	
	if (status) {
		$(elm).parent().closest("li").remove();
		
		//reload iframe
		updateLayoutFromSettings($(".entity_obj"), true);
	}
	else
		StatusMessageHandler.showError("Could not find this file in the \"Head\" region blocks.");
}

/* ADVANCED SETTINGS FUNCTIONS */

function initPageAdvancedSettings(elm) {
	onChangeParseHtml( elm.find(".parser .parse_html select")[0] );
	
	elm.find(".cache input[type=checkbox]").each(function(idx, elm) {
		onChangeCacheOption(elm);
	});
}

function onChangeParseHtml(elm) {
	elm = $(elm);
	var type = elm.val();
	var parser_elm = elm.parent().closest(".parser");
	var divs = parser_elm.children("div:not(.parse_html)");
	var inputs = divs.find("input, select");
	
	if (type == 0) {
		divs.hide();
		inputs.attr("disabled", "disabled");
	}
	else {
		divs.show();
		inputs.removeAttr("disabled", "disabled");
	}
}

function onChangeCacheOption(elm) {
	elm = $(elm);
	var input = elm.parent().children("input:not([type=checkbox])");
	
	if (elm.is(":checked"))
		input.removeAttr("disabled").show();
	else
		input.attr("disabled", "disabled").hide();
}

/* SAVING FUNCTIONS */

function getEntityCodeObjId() {
	var obj = getObjToSave();
	
	return $.md5(save_object_url + JSON.stringify(obj));
}

function isEntityCodeObjChanged() {
	var entity_obj = $(".entity_obj");
	
	if (!entity_obj[0])
		return false;
	
	var new_saved_obj_id = getEntityCodeObjId();
	
	return saved_obj_id != new_saved_obj_id;
}

//if manual save, execute save function in a short setTimeout, bc if cursor pointer is inside of an input of a widget properties and then we click save, that input is not getting saved and the user will need to save it again. So we need to have the save function inside of a timeout so the input onBlur event be trigered before.
function saveEntityWithDelay(opts) {
	prepareAutoSaveVars();
	
	if (!is_from_auto_save)
		setTimeout(function() {
			saveEntity(opts);
		}, 500);
	else
		saveEntity(opts);
}

function saveEntity(opts) {
	var entity_obj = $(".entity_obj");
	
	prepareAutoSaveVars();
	
	if (entity_obj[0]) {
		var func = function() {
			if (confirm_save)
				confirmSave(opts);
			else
				save(opts);
		};
		
		if (!auto_convert_settings_from_layout) {
			if (!is_from_auto_save) {
				MyFancyPopup.init({
					parentElement: window,
				});
				MyFancyPopup.showOverlay();
				MyFancyPopup.showLoading();
			}
			
			enableAutoConvertSettingsFromLayout(function() {
				if (!is_from_auto_save)				
					MyFancyPopup.hidePopup();
				
				func();
			});
			disableAutoConvertSettingsFromLayout();
		}
		else
			func();
	}
	else if (!is_from_auto_save)
		alert("No entity object to save! Please contact the sysadmin...");
}

function save(opts) {
	var entity_obj = $(".entity_obj");
	
	prepareAutoSaveVars();
	
	var is_from_auto_save_bkp = is_from_auto_save; //backup the is_from_auto_save, bc if there is a concurrent process running at the same time, this other process may change the is_from_auto_save value.
		
	if (entity_obj[0]) {
		if (!window.is_save_func_running) {
			window.is_save_func_running = true;
			
			//prepare save
			var obj = getObjToSave();
			var new_saved_obj_id = $.md5(save_object_url + JSON.stringify(obj)); //Do not use getEntityCodeObjId, so it can be faster...
			
			if (!saved_obj_id || saved_obj_id != new_saved_obj_id) {
				var save_btn = $(".top_bar ul li.save a");
				
				if (!is_from_auto_save_bkp) {
					save_btn.first().addClass("loading"); //only for the short-action icon
					
					MyFancyPopup.init({
						parentElement: window,
					});
					MyFancyPopup.showOverlay();
					MyFancyPopup.showLoading();
				}
				
				opts = opts ? opts : {};
				opts.complete = function() {
					if (!is_from_auto_save_bkp) {
						save_btn.removeClass("loading");
						//MyFancyPopup.hidePopup(); //the saveObj function already hides the popup
					}
					//else
					//	resetAutoSave(); //the saveObj function already resetAutoSave
					
					window.is_save_func_running = false;
					
					//flushes the cache
					if (typeof flush_cache != "undefined" && flush_cache) {
						flushCache({do_not_show_messages: is_from_auto_save_bkp});
						flush_cache = false;
					}
				};
				
				saveObj(save_object_url, obj, opts);
			}
			else {
				if (!is_from_auto_save_bkp)
					StatusMessageHandler.showMessage("Nothing to save.", "", "bottom_messages", 1500);
				else
					resetAutoSave();
				
				window.is_save_func_running = false;
			}
		}
		else if (!is_from_auto_save_bkp)
			StatusMessageHandler.showMessage("There is already a saving process running. Please wait a few seconds and try again...");
	}
	else if (!is_from_auto_save_bkp)
		alert("No entity object to save! Please contact the sysadmin...");
}

function saveAndPreview() {
	var opts = {
		success: function() {
			//open popup with preview
			//setTimeout very important bc if confirmSave, after this success function be executed the MyFancyPopup will be hided.
			setTimeout(function() {
				testAndPreview(false);
			}, 300);
		}
	};
	
	if (confirm_save) 
		confirmSave(opts);
	else
		save(opts);
}

function confirmSave(opts) {
	if ($(".entity_obj").length == 0){
		alert("There is no entity object! Please contact the sysadmin...");
		return false;
	}
	
	prepareAutoSaveVars();
	
	//if is confirm popup, when from auto_save, it should not do anyting
	if (is_from_auto_save)
		return false;
	
	//disable auto_save if manual action
	var auto_save_bkp = auto_save;
	
	if (/*!is_from_auto_save && */auto_save_bkp && isAutoSaveMenuEnabled())
		auto_save = false;
	
	//prepare save
	var obj = getObjToSave();
	var new_saved_obj_id = $.md5(save_object_url + JSON.stringify(obj)); //Do not use getEntityCodeObjId, so it can be faster...
	
	if (!saved_obj_id || saved_obj_id != new_saved_obj_id) {
		if (!is_from_auto_save) {
			MyFancyPopup.init({
				parentElement: window,
			});
			MyFancyPopup.showOverlay();
			MyFancyPopup.showLoading();
		}
		
		opts = opts ? opts : {};
		
		$.ajax({
			type : "post",
			url : create_entity_code_url,
			data : {"object" : obj},
			dataType : "json",
			success : function(data, textStatus, jqXHR) {
				if (!is_from_auto_save) {
					//only show this message if is a manual save, otherwise we don't want to do anything. Otherwise the browser is showing this popup constantly and is annoying for the user.
					var old_code = $(".current_entity_code").text();
					
					MyFancyPopup.hidePopup();
					
					showConfirmationCodePopup(old_code, data, {
						save: function() {
							if (/*!is_from_auto_save && */auto_save_bkp && isAutoSaveMenuEnabled())
								auto_save = auto_save_bkp;
							
							//change save button action to be simply save, otherwise it is always showing the confirmation popup everytime we save the file.
							if (opts && typeof opts["success"] == "function") {
								var prev_func = opts["success"];
								
								opts["success"] = function() {
									prev_func();
									
									confirm_save = false; //set confirm_save to false so it doesn't call again the confirmSave, othewsie it is annoying.
									
									return true;
								}
							}
							else {
								if (!opts)
									opts = {};
								
								opts["success"] = function() {
									confirm_save = false; //set confirm_save to false so it doesn't call again the confirmSave, othewsie it is annoying.
									
									return true;
								}
							}
							
							save(opts);
							
							return true;
						},
						cancel: function() {
							if (/*!is_from_auto_save && */auto_save_bkp && isAutoSaveMenuEnabled())
								auto_save = auto_save_bkp;
							
							return typeof opts.confirmation_cancel != "function" || opts.confirmation_cancel(data);
						},
					});
				}
				else {
					if (/*!is_from_auto_save && */auto_save_bkp && isAutoSaveMenuEnabled())
						auto_save = auto_save_bkp;
					
					resetAutoSave();
				}
			},
			error : function(jqXHR, textStatus, errorThrown) { 
				if (/*!is_from_auto_save && */auto_save_bkp && isAutoSaveMenuEnabled())
					auto_save = auto_save_bkp;
				
				if (jquery_native_xhr_object && isAjaxReturnedResponseLogin(jquery_native_xhr_object.responseURL))
					showAjaxLoginPopup(jquery_native_xhr_object.responseURL, create_entity_code_url, function() {
						StatusMessageHandler.removeLastShownMessage("error");
						
						confirmSave(opts);
					});
				else if (!is_from_auto_save) {
					var msg = jqXHR.responseText ? "\n" + jqXHR.responseText : "";
					StatusMessageHandler.showError("Error trying to save new changes.\nPlease try again..." + msg);
					
					MyFancyPopup.hidePopup();
				}
				else
					resetAutoSave();
			},
		});
	}
	else {
		if (/*!is_from_auto_save && */auto_save_bkp)
			auto_save = auto_save_bkp;
		
		if (!is_from_auto_save)
			StatusMessageHandler.showMessage("Nothing to save.", "", "bottom_messages", 1500);
		else
			resetAutoSave();
	}
}

function testAndPreviewWithDelay(do_not_check_for_changes) {
	if (!auto_convert_settings_from_layout) {
		MyFancyPopup.init({
			parentElement: window,
		});
		MyFancyPopup.showOverlay();
		MyFancyPopup.showLoading();
		
		enableAutoConvertSettingsFromLayout(function() {
			if (!is_from_auto_save)				
				MyFancyPopup.hidePopup();
			
			testAndPreview(do_not_check_for_changes);
		});
		disableAutoConvertSettingsFromLayout();
	}
	else
		testAndPreview(do_not_check_for_changes);
}

function testAndPreview(do_not_check_for_changes) {
	if (page_preview_url) {
		var status = do_not_check_for_changes || !isEntityCodeObjChanged() || confirm("You didn't save your new changes.\nIf you proceed you will only preview the saved changes.\nDo you wish to continue?");
		
		if (status) {
			//get popup
			var popup= $(".page_preview_popup");
			
			if (!popup[0]) {
				popup = $('<div class="myfancypopup page_preview_popup with_iframe_title"></div>');
				$(document.body).append(popup);
			}
			
			var url = page_preview_url + (page_preview_url.indexOf("?") != -1 ? "&" : "?") + "popup=1";
			
			popup.html('<iframe></iframe>'); //cleans the iframe so we don't see the previous html
			popup.children("iframe").attr("src", url);
			
			//open popup
			MyFancyPopup.init({
				elementToShow: popup,
				parentElement: document,
			});
			
			MyFancyPopup.showPopup();
		}
	}
}

function previewWithDelay(do_not_check_for_changes) {
	if (!auto_convert_settings_from_layout) {
		MyFancyPopup.init({
			parentElement: window,
		});
		MyFancyPopup.showOverlay();
		MyFancyPopup.showLoading();
		
		enableAutoConvertSettingsFromLayout(function() {
			if (!is_from_auto_save)				
				MyFancyPopup.hidePopup();
			
			preview(do_not_check_for_changes);
		});
		disableAutoConvertSettingsFromLayout();
	}
	else
		preview(do_not_check_for_changes);
}

function preview(do_not_check_for_changes) {
	if (view_project_url) {
		var status = do_not_check_for_changes || !isEntityCodeObjChanged() || confirm("You didn't save your new changes.\nIf you proceed you will only preview the saved changes.\nDo you wish to continue?");
		
		if (status) {
			var win = window.open(view_project_url, "preview_tab");
			
			if(win) //Browser has allowed it to be opened
				win.focus();
		}
	}
}

function getExternalSetTemplateParams(entity_obj) {
	var is_external_template = isExternalTemplate(entity_obj);
	
	//prepare template params
	var external_template_params = entity_obj.children(".external_template_params");
	var external_template_type = external_template_params.find(".external_template_type select").val();
	var template_args = [];
	
	if (is_external_template && external_template_type) {
		template_args.push({
			"key": "project_id",
			"key_type": "string",
			"value": "$EVC->getCommonProjectName()",
			"value_type": "method",
		});
	
		$.each( external_template_params.find(".external_template_type, ." + external_template_type + "_param").find("input, select, textarea"), function(idx, input) {
			input = $(input);
			var input_type = input.attr("type");
			var input_name = input.attr("name");
			
			if (input_name) {
				var input_value = null;
				
				if (input_type == "checkbox" || input_type == "radio")
					input_value = input.is(":checked") ? 1 : 0;
				else
					input_value = input.val();
				
				template_args.push({
					"key": input_name,
					"key_type": "string",
					"value": input_value,
					"value_type": $.isNumeric(input_value) ? "" : "string",
				});
			}		
		});
	}
	
	return template_args;
}

function getExternalTemplateParams(entity_obj) {
	var is_external_template = isExternalTemplate(entity_obj);
	
	//prepare template params
	var external_template_params = entity_obj.children(".external_template_params");
	var external_template_type = external_template_params.find(".external_template_type select").val();
	var template_args = {};
	
	if (is_external_template && external_template_type) {
		template_args["project_id"] = '$EVC->getCommonProjectName()';
		
		$.each( external_template_params.find(".external_template_type, ." + external_template_type + "_param").find("input, select, textarea"), function(idx, input) {
			input = $(input);
			var input_type = input.attr("type");
			var input_name = input.attr("name");
			
			if (input_name) {
				if (input_type == "checkbox" || input_type == "radio")
					template_args[input_name] = input.is(":checked") ? 1 : 0;
				else
					template_args[input_name] = input.val();
			}		
		});
	}
	
	return template_args;
}

function getObjToSave() {
	//get regions blocks settings
	var obj = getRegionsBlocksAndIncludesObjToSave();
	//console.log(obj);
	
	//get sla settings
	var sla = $(".sla");
	obj["sla_settings"] = getSLASettings(sla);
	
	//get advanced properties
	var advanced_settings = $(".advanced_settings");
	obj["advanced_settings"] = getAdvancedSettings(advanced_settings);
	
	//prepare template
	var entity_obj = $(".entity_obj");
	var template_elm = entity_obj.find(".template");
	var is_external_template = isExternalTemplate(entity_obj);
	var template = getSelectedTemplate(entity_obj);
	template = !is_external_template && template == layer_default_template ? "" : template;
	
	//prepare template params
	var template_args = getExternalTemplateParams(entity_obj);
	
	if (template) {
		obj["templates"] = [
			{
				"template": template,
				"template_type": "string",
				"template_args": template_args,
			},
		];
	}
	
	return obj;
}

function getAdvancedSettings(advanced_settings_elm) {
	var inputs = advanced_settings_elm.find("input, select, textarea");
	var setttings = {};
	
	for (var i = 0; i < inputs.length; i++) {
		var input = inputs[i];
		
		if (!input.hasAttribute("disabled")) {
			var name = input.name;
			
			if (name) {
				if ((input.type == "checkbox" || input.type == "radio")) {
					if (input.checked)
						setttings[name] = input.value;
				}
				else
					setttings[name] = input.value;	
			}
		}
	}
	
	return setttings;
}
