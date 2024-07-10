function addBlock(elm, module_id) {
	$(elm).addClass("loading").removeAttr("onClick");
	document.location = add_block_url.replace("#module_id#", module_id);
}

function toggleGroupOfMopdules(elm, group_module_id) {
	elm = $(elm);
	
	if (elm.hasClass("maximize")) {
		elm.removeClass("maximize").addClass("minimize");
		elm.parent().parent().parent().children("tr[group_module_id='" + group_module_id + "']").show();
	}
	else {
		elm.removeClass("minimize").addClass("maximize");
		elm.parent().parent().parent().children("tr[group_module_id='" + group_module_id + "']").hide();
	}
}
