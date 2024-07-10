var saved_template_obj_id = null;
var saved_head_code = null;
var MyFancyPopupTemplatePreview = new MyFancyPopupClass();
var MyFancyPopupEditWebrootFile = new MyFancyPopupClass();
var give_priority_to_original_project_url_prefix = true; //set this to true so the method edit_page_and_template.js:convertProjectUrlRealValuesToPHPVars can replace the project url with the $original_project_url_prefix var.

$(function () {
	var init_finished = false;
	
	MyFancyPopup.showOverlay();
	MyFancyPopup.showLoading();
	
	$(window).bind('beforeunload', function () {
		if (init_finished && isTemplateCodeObjChanged()) {
			if (window.parent && window.parent.iframe_overlay)
				window.parent.iframe_overlay.hide();
			
			return "If you proceed your changes won't be saved. Do you wish to continue?";
		}
		
		return null;
	});
	
	//prepare top_bar
	$(".code_layout_ui_editor > .code_menu").addClass("top_bar_menu");
	$(".code_layout_ui_editor > .layout-ui-editor").addClass("with_top_bar_menu");
	
	//init ui
	var template_obj = $(".template_obj");
	
	if (template_obj[0]) {
		//add choose_db_table_or_attribute popup before the LayouUIEditor gets inited otherwise the choose-widget-resource icons won't be shown.
		if (typeof choose_db_table_or_attribute_elm != "undefined" && choose_db_table_or_attribute_elm)
			template_obj.append(choose_db_table_or_attribute_elm);
		
		//prepare main settings tab
		var regions_blocks_includes_settings = template_obj.find(".regions_blocks_includes_settings");
		regions_blocks_includes_settings.tabs();
		
		//prepare head editor
		var textarea = template_obj.find(".head textarea")[0];
		
		if (textarea)
			createCodeEditor(textarea, {save_func: saveTemplate, no_height: true});
		
		setTimeout(function() {
			//init sla
			initPageAndTemplateLayoutSLA(regions_blocks_includes_settings);
			
			//load sla settings
			loadPageAndTemplateLayoutSLASettings(regions_blocks_includes_settings, false);
			
			init_finished = true;
		}, 10);
		
		//init page template layout
		initPageAndTemplateLayout(template_obj, {
			save_func: saveTemplate, 
			ready_func: function() {
				//console.log("initPageAndTemplateLayout ready_func");
				
				//add top bar menu: Show/Hide Side Bar DBs Panel
				addCodeLayoutUIEditorRightContainerDBsMenu( $(".top_bar li.sub_menu li.toggle_main_settings") );
				
				//prepare some PtlLayoutUIEditor options
				var luie = template_obj.find(".code_layout_ui_editor > .layout-ui-editor");
				var PtlLayoutUIEditor = luie.data("LayoutUIEditor");
				PtlLayoutUIEditor.showTemplateWidgetsDroppableBackground();
				
				//check if is dragged widget is PTL, and if yes, be sure that the droppable belongs to a template_region
				var on_drag_stop_func = PtlLayoutUIEditor.options.on_drag_stop_func;
				PtlLayoutUIEditor.options.on_drag_stop_func = function(menu_widget, widget, event, ui_obj) {
					if (typeof on_drag_stop_func == "function")
						on_drag_stop_func(menu_widget, widget, event, ui_obj);
					
					if ($(menu_widget).is(".menu-widget-ptl")) {
						var template_region = widget.parent().closest(".template_region");
						
						if (!template_region[0]) {
							widget.hide();
							
							PtlLayoutUIEditor.showError("You cannot drop this widget outside of the template regions.");
							
							setTimeout(function() {
								PtlLayoutUIEditor.deleteTemplateWidget(widget);
							}, 500);
						}
					}
				};
				
				//set default head code from iframe
				var head_code = null;
				
				if (!code_exists) {
					head_code = PtlLayoutUIEditor.getTemplateLayoutHeadHtml();
					setTemplateHeadEditorCode(head_code);
				}
				else { //beautify head code
					head_code = getTemplateHeadEditorCode();
					
					if (PtlLayoutUIEditor.options.beautify)
						head_code = MyHtmlBeautify.beautify(head_code); 
					
					setTemplateHeadEditorCode(head_code);
				}
				
				saved_head_code = head_code;
				
				//set change event to head editor
				if (textarea) {
					var editor = template_obj.find(".code_editor_settings .head").data("editor");
					
					if (editor)
						editor.on("blur", updateCodeEditorLayoutHeadFromSettings);
					else
						$(textarea).on("blur", updateCodeEditorLayoutHeadFromSettings);
				}
				
				//DEPRECATED - waits until the load params and joinpoints gets loaded. No need this anymorebc the initPageAndTemplateLayout method already covers this case.
				//setTimeout(function() {
					//load js and css files
					loadTemplateCodeEditorLayoutJSAndCSSFilesToSettings();
					
					var func = function() {
						if (init_finished) {
							//set saved_template_obj_id
							saved_template_obj_id = getTemplateCodeObjId();//only for testing. Then uncomment this line!
							
							//init auto save
							addAutoSaveMenu(".top_bar li.sub_menu li.save");
							//enableAutoSave(onToggleSLAAutoSave); //Do not enable auto save bc it gets a litte bit slow editing the template.
							initAutoSave(".top_bar li.sub_menu li.save a");
							StatusMessageHandler.showMessage("Auto save is disabled for better user-experience...", "", "bottom_messages", 10000);
							
							//change the toggle Auto save handler bc the edit_query task
							initSLAAutoSaveActivationMenu();
							
							//set update handlers
							var iframe = getContentTemplateLayoutIframe(template_obj);
							
							update_settings_from_layout_iframe_func = function() {
								//console.log("update_settings_from_layout_iframe_func");
								updateCodeEditorSettingsFromLayout(template_obj);
							};
							update_layout_iframe_from_settings_func = function() {
								//console.log("update_layout_iframe_from_settings_func");
								updateCodeEditorLayoutFromSettings(template_obj, false, true);
							};
							update_layout_iframe_field_html_value_from_settings_func = function(elm, html) { //set handler to update directly the the html in the template layout without refreshing the entire layout.
								//console.log("update_layout_iframe_field_html_value_from_settings_func");
								updateLayoutIframeRegionBlockHtmlFromSettingsHtmlField(elm, html, iframe);
								
								//update css and js files from region blocks
								loadTemplateRegionsBlocksHtmlJSAndCSSFilesToSettings();
							};
							
							if (typeof invalid_msg != "undefined" && invalid_msg)
								StatusMessageHandler.showMessage(invalid_msg, "warning", "bottom_messages", 10000);
							
							//hide loading icon
							MyFancyPopup.hidePopup();
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
	}
});

//To be used in the toggleFullScreen function
function onToggleFullScreen(in_full_screen) {
	var template_obj = $(".template_obj");
	onToggleCodeEditorFullScreen(in_full_screen, template_obj);
	
	setTimeout(function() {
		var top = parseInt(template_obj.find(".regions_blocks_includes_settings").css("top"));
		
		resizeSettingsPanel(template_obj, top);
	}, 500);
}

/* PREVIEW FUNCTIONS */

function preview() {
	//prepare popup
	preparePreviewPopup();
	
	//get popup
	var popup= $(".template_obj > #preview");
	
	//open popup
	MyFancyPopupTemplatePreview.init({
		elementToShow: popup,
		parentElement: document,
	});
	
	MyFancyPopupTemplatePreview.showPopup();
}

function preparePreviewPopup() {
	var iframe = $(".template_obj > #preview > iframe");
	var url = iframe.attr("orig_src");
	var iframe_head = iframe.contents().find("head");
	var iframe_body = iframe.contents().find("body");
	
	//clean iframe first
	iframe_head.html("");
	iframe_body.html("");
	
	//load new html
	var code = getTemplateCodeForSaving();
	var ajax_opts = {
		url: url,
		type: 'post',
		processData: false,
		contentType: 'text/html',
		data: code,
		success: function(parsed_html, textStatus, jqXHR) {
			//console.log(parsed_html);
			
			//show login popup
			if (jquery_native_xhr_object && isAjaxReturnedResponseLogin(jquery_native_xhr_object.responseURL)) {
				MyFancyPopup.hideLoading();
				
				showAjaxLoginPopup(jquery_native_xhr_object.responseURL, url, function() {
					$.ajax(ajax_opts);
				});
			}
			else {
				try {
					var doc = iframe[0].contentDocument ? iframe[0].contentDocument : iframe[0].contentWindow.document;
					doc.open();
					doc.write(parsed_html);
					doc.close();
				}
				catch(e) {
					if (console && console.log) 
						console.log(e);
				}
				
				//hide loading icon
				MyFancyPopup.hidePopup();
			}
		},
		error: function (jqXHR, textStatus, errorThrown) {
			//hide loading icon
			MyFancyPopup.hidePopup();
			MyFancyPopupTemplatePreview.hidePopup();
			
			//show error msg
			var msg = "Couldn't preview template. Error in preparePreviewPopup() function. Please try again...";
			alert(msg);
			
			if (jqXHR.responseText)
				StatusMessageHandler.showError(msg + "\n" + jqXHR.responseText);
		},
	};
	
	$.ajax(ajax_opts);
}

/* LAYOUT-SETTINGS-LAYOUT UPDATE FUNCTIONS */

function updateCodeEditorLayoutFromSettings(template_obj, reload_iframe, do_not_update_from_body_editor, force) {
	if (template_obj[0] && !template_obj.hasClass("inactive") || force) {
		//must happen before the updateRegionsFromBodyEditor
		var orig_template_params_values_list = JSON.stringify(template_params_values_list);
		var orig_includes_list = JSON.stringify(includes_list);
		
		var iframe = getContentTemplateLayoutIframe(template_obj);
		var regions_blocks_includes_settings = template_obj.find(".regions_blocks_includes_settings");
		
		updateRegionsBlocksRBIndexIfNotSet(regions_blocks_includes_settings); //very important, otherwise we will loose the region-block params-values and joinpoints for the new regions added dynamically
		
		//very important, otherwise we will loose the region-block params-values and joinpoints
		updateRegionsBlocksParamsLatestValues(regions_blocks_includes_settings); 
		updateRegionsBlocksJoinPointsSettingsLatestObjs(regions_blocks_includes_settings);
		
		//updates the last regions and params. This is very important. otherwise the template preview won't show the latest regions and params from the html.
		if (!do_not_update_from_body_editor)
			updateRegionsFromBodyEditor(false, true);
		
		var are_different = areLayoutAndSettingsDifferent(iframe, regions_blocks_includes_settings, true);
		var data = getSettingsTemplateRegionsBlocks(regions_blocks_includes_settings); //get regions and params from settings
		//console.log(data);
		//console.log("are_different:"+are_different);
		
		if (!are_different) //if params are different
			are_different = orig_template_params_values_list != JSON.stringify(data["params"]);
		
		if (!are_different) //if includes are different
			are_different = orig_includes_list != JSON.stringify(data["includes"]);
		
		if (!are_different) //if head is different
			are_different = getTemplateHeadEditorCode() != saved_head_code;
		//console.log("are_different:"+are_different);
		
		if (force || (are_different /*&& confirm("Do you wish to convert the template settings to the layout panel?\n\nNote: You must save the html code first, in order to see the new changes that you made (if you made any).")*/)) {
			//prepare iframe with new data
			var iframe_data = {
				"template_regions" : data["template_regions"],
				"template_params": data["params"],
				"template_includes": data["includes"]
			};
			//console.log(iframe_data);
			
			var current_html = getTemplateCodeForSaving(true);
			MyFancyPopup.hidePopup();
			//console.log(current_html);
			
			if (reload_iframe)
				reloadLayoutIframeFromSettings(iframe, iframe_data, current_html); //show template preview based in the html source
			else
				updateLayoutIframeFromSettings(iframe, iframe_data, data, current_html);
			
			//update regions_blocks_list
			regions_blocks_list = data["regions_blocks"];
			
			//update template_params_values_list
			template_params_values_list = data["params"];
			
			//update includes_list
			includes_list = data["includes"];
			
			//update css and js files from region blocks
			loadTemplateRegionsBlocksHtmlJSAndCSSFilesToSettings();
		}
	}
}

function updateCodeEditorSettingsFromLayout(template_obj) {
	if (!template_obj.hasClass("inactive")) {
		//updates the last regions and params. This is very important. otherwise the template preview won't show the latest regions and params from the html.
		updateRegionsFromBodyEditor(false, true);
		
		var iframe = getContentTemplateLayoutIframe(template_obj);
		var regions_blocks_includes_settings = template_obj.find(".regions_blocks_includes_settings");
		var are_different = areLayoutAndSettingsDifferent(iframe, regions_blocks_includes_settings, true);
		
		if (are_different /*&& confirm("Do you wish to convert the template regions to the settings panel?\n\nNote: You must save the html code first, in order to see the new changes that you made (if you made any).")*/) {
			var data = getIframeTemplateRegionsBlocksForSettings(iframe, regions_blocks_includes_settings);
			//console.log(data);
			
			regions_blocks_list = data["regions_blocks_list"]; //global var
			template_params_values_list = data["template_params_values_list"]; //global var
			
			updateSelectedTemplateRegionsBlocks(regions_blocks_includes_settings, {
				regions: data["regions"], 
				params: data["params"], 
			});
			
			//update css and js files from region blocks
			loadTemplateRegionsBlocksHtmlJSAndCSSFilesToSettings();
		}
	}
}

//update regions from layout to main settings
function updateRegionsFromBodyEditor(show_info_message, do_not_update_layout) {
	//disable auto_save
	var auto_save_bkp = auto_save;
	auto_save = false;
	
	//save synchronization functions
	var update_settings_from_layout_iframe_func_bkp = update_settings_from_layout_iframe_func;
	var update_layout_iframe_from_settings_func_bkp = update_layout_iframe_from_settings_func;
	var update_layout_iframe_field_html_value_from_settings_func_bkp = update_layout_iframe_field_html_value_from_settings_func;
	
	//disable synchronization functions bc the updateSelectedTemplateRegionsBlocks calls the sync func, when it triggers the on change event from the blok_type in the getRegionBlockHtml
	update_settings_from_layout_iframe_func = null;
	update_layout_iframe_from_settings_func = null;
	update_layout_iframe_field_html_value_from_settings_func = null;
	
	//update regions blocks
	var regions_blocks_includes_settings = $(".template_obj .regions_blocks_includes_settings");
	
	var settings = getSettingsTemplateRegionsBlocks(regions_blocks_includes_settings);
	regions_blocks_list = settings["regions_blocks"];
	template_params_values_list = settings["params"]; //global var
	
	var regions = getCurrentCodeRegions();
	var params = getCurrentCodeParams();
	
	updateSelectedTemplateRegionsBlocks(regions_blocks_includes_settings, {
		"regions": regions,
		"params": params,
	});
	
	//update js and css files in main settings
	loadTemplateCodeEditorLayoutJSAndCSSFilesToSettings();
	
	//sets back synchronization functions
	update_settings_from_layout_iframe_func = update_settings_from_layout_iframe_func_bkp;
	update_layout_iframe_from_settings_func = update_layout_iframe_from_settings_func_bkp;
	update_layout_iframe_field_html_value_from_settings_func = update_layout_iframe_field_html_value_from_settings_func_bkp;
	
	if (!do_not_update_layout)
		updateLayoutIframeFromSettingsField(); //then reload the layout again
	
	if (show_info_message && regions.length == 0)
		StatusMessageHandler.showMessage("No regions to be updated...");
	
	if (auto_save_bkp)
		auto_save = auto_save_bkp;
}

function updateCodeEditorLayoutHeadFromSettings() {
	//for each character changed or writen this function wil be called, so we must add a settimeout and only execute this function when the user finishes to write
	var head_code = getTemplateHeadEditorCode();
	
	if (head_code != saved_head_code) {
		var template_obj = $(".template_obj");
		var iframe = getContentTemplateLayoutIframe(template_obj);
		var iframe_head = iframe.contents().find("head").first();
		var contains_php = head_code.indexOf("<?") != -1;
		
		if (contains_php)
			updateCodeEditorLayoutFromSettings(template_obj, true);
		else {
			//remove old head
			var nodes = iframe_head.contents();
			
			for (var i = 0; i < nodes.length; i++) {
				var node = nodes[i];
				
				if (node.nodeType != Node.ELEMENT_NODE || !node.classList.contains("layout-ui-editor-reserved")) {
					if (node.nodeType == Node.ELEMENT_NODE && node.classList.contains("template-widget-script"))
						$(node).children("script").remove();
					
					node.parentNode.removeChild(node);
				}
			}
			
			//add new head
			var code_layout_ui_editor = template_obj.find(".code_layout_ui_editor");
			var PtlLayoutUIEditor = code_layout_ui_editor.find(".layout-ui-editor").data("LayoutUIEditor");
			PtlLayoutUIEditor.parseHtmlSource(head_code, iframe_head);
		}
		
		saved_head_code = head_code;
	}
}

function editWebrootFile(path) {
	//prepare popup
	var template_obj = $(".template_obj");
	var popup= template_obj.children("#edit_webroot_file");
	
	if (!popup[0]) {
		popup = $('<div id="edit_webroot_file" class="myfancypopup with_iframe_title"><iframe></iframe></div>');
		template_obj.append(popup);
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
			updateCodeEditorLayoutFromSettings(template_obj, true, true, true);
		},
	});
	
	MyFancyPopupEditWebrootFile.showPopup();
}

function getCurrentCodeRegions() {
	var head_code = getTemplateHeadEditorCode();
	var body_code = getTemplateBodyEditorCode();
	
	var code = "<html><head>" + head_code + "</head><body>" + body_code + "</body></html>";
	code = code.replace(/&gt;/gi, ">").replace(/&lt;/gi, "<");
	
	var matches = code.match(/\$([^\$]+)([ ]*)->([ ]*)renderRegion([ ]*)\(([ ]*)([^\)]*)([ ]*)\)/gi); //includes the hidden regions, like in the php code camufulated with html comments. Is very important to include the hidden regions, so when the updateRegionsFromBodyEditor method gets executed, we get all the available regions and not only the visible ones.
	var regions = [];
	//console.log(matches);
	
	if (matches) {
		for (var i = 0; i < matches.length; i++) {
			var m = matches[i];
			m = m.substring(m.lastIndexOf("(") + 1, m.lastIndexOf(")")).trim().replace(/'/g, '"');
		
			if (regions.indexOf(m) == -1)
				regions.push(m);
		}
	}
	
	return regions;
}

function getCurrentCodeParams() {
	var head_code = getTemplateHeadEditorCode();
	var body_code = getTemplateBodyEditorCode();
	
	var code = "<html><head>" + head_code + "</head><body>" + body_code + "</body></html>";
	code = code.replace(/&gt;/gi, ">").replace(/&lt;/gi, "<");
	
	var matches = code.match(/\$([^\$]+)([ ]*)->([ ]*)getParam([ ]*)\(([ ]*)([^\)]*)([ ]*)\)/gi); //includes the hidden params, like in the php code camufulated with html comments. Is very important to include the hidden params, so when the updateRegionsFromBodyEditor method gets executed, we get all the available params and not only the visible ones.
	var params = [];
	if (matches) {
		for (var i = 0; i < matches.length; i++) {
			var m = matches[i];
			m = m.substring(m.lastIndexOf("(") + 1, m.lastIndexOf(")")).trim().replace(/'/g, '"');
			
			if (params.indexOf(m) == -1)
				params.push(m);
		}
	}
	
	return params;
}

function setTemplateHeadEditorCode(value) {
	var editor = $(".template_obj .code_editor_settings .head").data("editor");
	return editor ? editor.setValue(value, 1) : $(".template_obj .code_editor_settings .head textarea").first().val(value);
}

function getTemplateHeadEditorCode() {
	var editor = $(".template_obj .code_editor_settings .head").data("editor");
	return editor ? editor.getValue() : $(".template_obj .code_editor_settings .head textarea").first().val();
}

function getTemplateBodyEditorCode() {
	var code_layout_ui_editor = $(".template_obj .code_layout_ui_editor");
	var PtlLayoutUIEditor = code_layout_ui_editor.find(".layout-ui-editor").data("LayoutUIEditor");
	
	if (PtlLayoutUIEditor) {
		var luie = PtlLayoutUIEditor.getUI();
		luie.find(" > .options .option.show-full-source").removeClass("option-active"); //remove active class so the getCodeLayoutUIEditorCode calls the getTemplateSourceEditorValue method, instead of getTemplateFullSourceEditorValue
	}
	
	var code = getCodeLayoutUIEditorCode(code_layout_ui_editor);
	
	return code;
}

function getTemplateEditorCode() {
	var code_layout_ui_editor = $(".template_obj .code_layout_ui_editor");
	var PtlLayoutUIEditor = code_layout_ui_editor.find(".layout-ui-editor").data("LayoutUIEditor");
	
	if (PtlLayoutUIEditor) {
		var luie = PtlLayoutUIEditor.getUI();
		luie.find(" > .options .option.show-full-source").addClass("option-active"); //add active class so the getCodeLayoutUIEditorCode calls the getTemplateFullSourceEditorValue method, instead of getTemplateSourceEditorValue
	}
	
	var code = getCodeLayoutUIEditorCode(code_layout_ui_editor);
	
	return code;
}

function loadTemplateCodeEditorLayoutJSAndCSSFilesToSettings() {
	var opts = {
		remove: removeTemplateWebrootFile,
		create: function(file, li, func_opts) {
			if (func_opts && typeof func_opts["head_code"] == "string" && func_opts["head_code"].indexOf(file) != -1)
				li.addClass("from_head");
			else
				li.addClass("from_body");
		}
	};
	loadCodeEditorLayoutJSAndCSSFilesToSettings(opts);
	
	loadTemplateRegionsBlocksHtmlJSAndCSSFilesToSettings();
}

function loadTemplateRegionsBlocksHtmlJSAndCSSFilesToSettings() {
	//remove all files coming from region blocks html
	var regions_blocks_includes_settings = $(".regions_blocks_includes_settings");
	regions_blocks_includes_settings.find(".css_files, js_files").find(" > ul > li.from_region_block").remove();
	
	var region_opts = {
		remove: removeTemplateWebrootFile,
		create: function(file, li, func_opts) {
			li.addClass("from_region_block");
		}
	};
	appendRegionsBlocksHtmlFileInSettings(null, region_opts);
}

function addTemplateWebrootFile(elm, type, file_name, file_url, file_code) {
	var head_code = getTemplateHeadEditorCode();
	head_code += "\n" + file_code;
	head_code = head_code.replace(/^\s+/g, "").replace(/\s+$/g, ""); //trim html
	setTemplateHeadEditorCode(head_code);
	
	updateCodeEditorLayoutHeadFromSettings();
}

function removeTemplateWebrootFile(elm, file) {
	var head_code = getTemplateHeadEditorCode();
	var new_head_code = removeFileFromCode(head_code, file);
	var li = $(elm).parent().closest("li");
	var changed = false;
	
	if (head_code != new_head_code) {
		setTemplateHeadEditorCode(new_head_code);
		changed = true;
	}
	else {
		var template_code = getTemplateEditorCode();
		var new_template_code = removeFileFromCode(template_code, file);
		
		if (template_code != new_template_code)
			StatusMessageHandler.showMessage("This file is not in the HEAD html tag. Please remove it directly from the canvas area");
	}
	
	//check if file exists in head region also.
	var status = removeFileFromRegionBlockHtml(file);
	
	if (status || changed)
		li.remove();
	
	//if removeFileFromRegionBlockHtml didn't change anything but the head editor was changed. Note that the update_layout_iframe_from_settings_func will call the updateCodeEditorLayoutFromSettings, that will call getTemplateCodeForSaving which calls the getTemplateHeadEditorCode, getting the new head code and updating it in the iframe
	if (!status && changed && typeof update_layout_iframe_from_settings_func == "function")
		updateCodeEditorLayoutFromSettings($(".template_obj"), true, true);
		//update_layout_iframe_from_settings_func(); //Do not call update_layout_iframe_from_settings_func bc the reload_iframe argument in the updateCodeEditorLayoutFromSettings method is false. And we want to force to reload the iframe, so we need to call this method directly.
}

/* SAVING FUNCTIONS */

function getTemplateCodeForSaving(without_regions_blocks_and_includes) {
	prepareAutoSaveVars();
	
	var template_obj = $(".template_obj");
	
	//save synchronization function
	var update_settings_from_layout_iframe_func_bkp = update_settings_from_layout_iframe_func;
	var update_layout_iframe_from_settings_func_bkp = update_layout_iframe_from_settings_func;
	var update_layout_iframe_field_html_value_from_settings_func_bkp = update_layout_iframe_field_html_value_from_settings_func;
	
	//disable synchronization function
	update_settings_from_layout_iframe_func = null;
	update_layout_iframe_from_settings_func = null;
	update_layout_iframe_field_html_value_from_settings_func = null;
	
	//preparing new code
	var status = true;
	var code = "";
	
	if (!is_from_auto_save) {
		MyFancyPopup.init({
			parentElement: window,
		});
		MyFancyPopup.showOverlay();
		MyFancyPopup.showLoading();
	}
	
	//prepare php code
	if (!without_regions_blocks_and_includes) {
		//DEPRECATED - update settings from body, Do not execute updateRegionsFromBodyEditor here, bc if we add a new repeated-region from settings tab, the auto-save will remove it when we execute updateRegionsFromBodyEditor. The new regions from the layout will be updated automatically on created the new region.
		//updateRegionsFromBodyEditor(false, true); 
		
		//get regions blocks settings
		var ret = getRegionsBlocksAndIncludesObjCodeToSave();
		code = ret["code"];
		status = ret["status"];
	}
	
	//prepare html
	var doc_type_attributes = template_obj.find(".code_editor_settings .doc_type_attributes input").val();
	var html_attributes = template_obj.find(".code_editor_settings .html_attributes input").val();
	var head_attributes = template_obj.find(".code_editor_settings .head_attributes input").val();
	var special_body_attributes = template_obj.find(".code_editor_settings .special_body_attributes input").val();
	var head_code = getTemplateHeadEditorCode();
	var template_code = getTemplateEditorCode();
	
	var code_layout_ui_editor = template_obj.find(".code_layout_ui_editor");
	var PtlLayoutUIEditor = code_layout_ui_editor.find(".layout-ui-editor").data("LayoutUIEditor");
	
	template_code = PtlLayoutUIEditor.replaceTagAttributesFromSource(template_code, "!doctype", doc_type_attributes);
	template_code = PtlLayoutUIEditor.replaceTagAttributesFromSource(template_code, "html", html_attributes);
	template_code = PtlLayoutUIEditor.replaceTagAttributesFromSource(template_code, "head", head_attributes);
	template_code = PtlLayoutUIEditor.replaceTagContentFromSource(template_code, "head", head_code);
	
	var new_special_body_attributes = removeDuplicatesFromSpecialBodyAttributes(PtlLayoutUIEditor, template_code, special_body_attributes);
	template_code = getHtmlWithSpecialBodyAttributes(PtlLayoutUIEditor, template_code, new_special_body_attributes);
	
	if (new_special_body_attributes != special_body_attributes)
		template_obj.find(".code_editor_settings .special_body_attributes input").val(new_special_body_attributes);
	
	if (PtlLayoutUIEditor.options.beautify)
		template_code = MyHtmlBeautify.beautify(template_code); //do not beautify the code, bc sometimes it messes the php code and the inner html inside of the addRegionHtml method
	//console.log(template_code); //already contains the head tags including the scripts.
	
	code += template_code;
	//console.log(code);
	
	//sets back synchronization function
	update_settings_from_layout_iframe_func = update_settings_from_layout_iframe_func_bkp;
	update_layout_iframe_from_settings_func = update_layout_iframe_from_settings_func_bkp;
	update_layout_iframe_field_html_value_from_settings_func = update_layout_iframe_field_html_value_from_settings_func_bkp;
	
	return status ? code : null;
}

//add special body attributes to body attributes
function getHtmlWithSpecialBodyAttributes(PtlLayoutUIEditor, html, special_body_attributes) {
	if (special_body_attributes && html) {
		var body_attributes = PtlLayoutUIEditor.getTagAttributesFromSource(html, "body");
		var body_attributes_str = special_body_attributes;
		
		if (body_attributes && body_attributes.length) {
			var body_attributes_html = MyHtmlBeautify.convertAttributesToHtml(body_attributes);
			body_attributes_str = MyHtmlBeautify.joinAttributesHtmls(special_body_attributes, body_attributes_html);
		}
		
		if (body_attributes_str)
			body_attributes_str = body_attributes_str.replace(/(^\s+|\s+$)/, ""); //trim
		
		html = PtlLayoutUIEditor.replaceTagAttributesFromSource(html, "body", body_attributes_str);
		
		/*console.log(body_attributes);
		console.log(special_body_attributes);
		console.log(body_attributes_str);
		console.log(html);*/
	}
	
	return html;
}

function removeDuplicatesFromSpecialBodyAttributes(PtlLayoutUIEditor, html, special_body_attributes) {
	if (special_body_attributes && html) {
		var body_attributes = PtlLayoutUIEditor.getTagAttributesFromSource(html, "body");
		
		if (body_attributes && body_attributes.length) {
			var body_attributes_html = MyHtmlBeautify.convertAttributesToHtml(body_attributes);
			var body_attributes_str = MyHtmlBeautify.diffAttributesHtmls(special_body_attributes, body_attributes_html);
			
			if (body_attributes_str)
				body_attributes_str = body_attributes_str.replace(/(^\s+|\s+$)/, ""); //trim
			
			/*console.log(body_attributes);
			console.log(special_body_attributes);
			console.log(body_attributes_str);*/
			
			special_body_attributes = body_attributes_str;
		}
	}
	
	return special_body_attributes;
}

function getRegionsBlocksAndIncludesObjCodeToSave(opts) {
	var code = "";
	var status = true;
	
	//get regions blocks settings
	var obj = getRegionsBlocksAndIncludesObjToSave();
	
	//get sla settings
	var sla = $(".sla");
	obj["sla_settings"] = getSLASettings(sla);
	
	//get php code for regions blocks
	$.ajax({
		type : "post",
		url : create_entity_code_url,
		data : {"object" : obj},
		dataType : "json",
		success : function(data, textStatus, jqXHR) {
			code = data ? data + "\n" : "";
		},
		error : function(jqXHR, textStatus, errorThrown) { 
			status = false;
			
			if (jqXHR.responseText && !is_from_auto_save) {
				if (jquery_native_xhr_object && isAjaxReturnedResponseLogin(jquery_native_xhr_object.responseURL))
					StatusMessageHandler.showError("Please Login first!");
				else 
					StatusMessageHandler.showError(jqXHR.responseText);
			}
		},
		async: false,
	});
	
	return {code: code, status: status};
}

function getTemplateCodeObjId() {
	var obj_1 = getTemplateCodeForSaving(true);
	var obj_2 = getRegionsBlocksAndIncludesObjToSave();
	var obj_3 = getSLASettings( $(".sla") );
	
	MyFancyPopup.hidePopup();
	
	return $.md5(save_object_url + JSON.stringify(obj_1) + JSON.stringify(obj_2) + JSON.stringify(obj_3));
}

function isTemplateCodeObjChanged() {
	var template_obj = $(".template_obj");
	
	if (!template_obj[0])
		return false;
	
	var new_saved_template_obj_id = getTemplateCodeObjId();
	
	return saved_template_obj_id != new_saved_template_obj_id;
}

function saveTemplateWithDelay() {
	prepareAutoSaveVars();
	
	if (!is_from_auto_save)
		setTimeout(function() {
			saveTemplate();
		}, 500);
	else
		saveTemplate();
}

function saveTemplate() {
	var template_obj = $(".template_obj");
	
	prepareAutoSaveVars();
	
	if (template_obj[0]) {
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
				
				save();
			});
			disableAutoConvertSettingsFromLayout();
		}
		else
			save();
	}
	else if (!is_from_auto_save)
		alert("No template object to save! Please contact the sysadmin...");
}

function save() {
	//console.log("saveTemplate");
	var template_obj = $(".template_obj");
	
	prepareAutoSaveVars();
	
	var is_from_auto_save_bkp = is_from_auto_save; //backup the is_from_auto_save, bc if there is a concurrent process running at the same time, this other process may change the is_from_auto_save value.
	
	if (template_obj[0]) {
		if (!window.is_save_func_running) {
			window.is_save_func_running = true;
			
			//prepare save
			var new_saved_template_obj_id = getTemplateCodeObjId();
			
			if (!saved_template_obj_id || saved_template_obj_id != new_saved_template_obj_id) {
				var save_btn = $(".top_bar ul li.save a");
				
				if (!is_from_auto_save_bkp) {
					save_btn.first().addClass("loading"); //only for the short-action icon
					
					var scroll_top = $(document).scrollTop();
				}
				
				var code = getTemplateCodeForSaving();
				
				//call saveObjCode
				var obj = {"code": code};
				saveObjCode(save_object_url, obj, {
					success: function(data, textStatus, jqXHR) {
						saved_template_obj_id = getTemplateCodeObjId();
						
						return true;
					},
					complete: function() {
						if (!is_from_auto_save_bkp) {
							save_btn.removeClass("loading");
							$(document).scrollTop(scroll_top);
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
					},
				});
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
		alert("No template object to save! Please contact the sysadmin...");
}
