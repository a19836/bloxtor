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
