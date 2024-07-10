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
