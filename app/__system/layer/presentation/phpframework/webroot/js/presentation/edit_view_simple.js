function saveViewWithDelay() {
	saveTemplateWithDelay();
}

function saveView() {
	saveTemplate();
}

//overright getTemplateCodeForSaving method to only save the code inside of body
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
	var template_code = getTemplateEditorCode();
	
	//parse the code to only get the code inside of body
	var code_layout_ui_editor = template_obj.find(".code_layout_ui_editor");
	var PtlLayoutUIEditor = code_layout_ui_editor.find(".layout-ui-editor").data("LayoutUIEditor");
	
	if (PtlLayoutUIEditor && PtlLayoutUIEditor.existsTagFromSource(template_code, "body"))
		template_code = PtlLayoutUIEditor.getTagContentFromSource(template_code, "body");
	
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
