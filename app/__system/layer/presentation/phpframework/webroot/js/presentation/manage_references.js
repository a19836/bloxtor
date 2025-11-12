/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

$(function() {
	prepareFileTreeCheckbox( $(".layout_type_permissions_content input[type=checkbox]") );
	
	updateLayoutTypePermissionsById(layout_type_id);
});

function submitForm(elm) {
	elm = $(elm);
	var oForm = elm.parent().closest(".top_bar").parent().find(".layout_type_permissions_list form");
	elm.hide();
	oForm.submit();
}

function saveProjectLayoutTypePermissions() {
	return confirm('Do you wish to save these permissions for this project?');
}
