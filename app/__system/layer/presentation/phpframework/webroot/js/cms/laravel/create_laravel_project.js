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

$(function() {
	onChangeProjectWithDB( $(".project_db_driver select")[0] );
});

function submitForm(elm) {
	elm = $(elm);
	var oForm = elm.parent().closest(".top_bar").parent().children("form");
	var status = MyJSLib.FormHandler.formCheck(oForm[0]);
	
	if (status) {
		var on_click = elm.attr("onClick");
		elm.addClass("loading").removeAttr("onClick");
		
		oForm.submit();
		
		/*setTimeout(function() {
			elm.removeClass("loading").attr("onClick", on_click);
		}, 2000);*/
	}
	
	return status;
}

function onProjectKitChange(elm) {
	elm = $(elm);
	var kit = elm.val();
	var oForm = elm.parent().closest("form");
	var project_stack_select = oForm.find(".project_stack select");
	var stacks = null;
	
	if (kit)
		stacks = laravel_kit_stacks[kit];
	
	if (stacks) {
		var html = '';
		for (var k in stacks)
			html += '<option value="' + k + '">' + stacks[k] + '</option>';
		
		project_stack_select.html(html);
	}
	else
		project_stack_select.html('<option value="">-- none --</option>');
	
	onProjectStackChange(project_stack_select[0]);
}

function onProjectStackChange(elm) {
	elm = $(elm);
	var stack = elm.val();
	var oForm = elm.parent().closest("form");
	var kit = oForm.find(".project_kit select").val();
	var project_features_ul = oForm.find(".project_features ul");
	var features = null;
	
	if (kit && stack)
		features = laravel_kit_stack_features[kit] ? laravel_kit_stack_features[kit][stack] : null;
	
	if (features) {
		var html = '';
		for (var k in features)
			html += '<li><input type="checkbox" name="project_features[]" value="' + k + '" /> ' + features[k] + '</li>';
		
		project_features_ul.html(html);
	}
	else
		project_features_ul.html('<li class="empty">No features avaiable for this selection</li>');
}

function onChangeProjectWithDB(elm) {
	elm = $(elm);
	var value = $(elm).val();
	var oForm = elm.parent().closest("form");
	var new_db = oForm.find(".new_db");
	
	if (value === "1")
		new_db.show();
	else
		new_db.hide();
}
