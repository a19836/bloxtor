/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

function addAttachment(elm) {
	var html = '<li><input type="file" name="msg[attachment][]" multiple /></li>';
	var li = $(html);
	var ul = $(elm).parent().closest(".attachments").children("ul");
	
	ul.children(".empty").hide();
	ul.append(li);
	
	return li;
}

function sendEmail(oForm) {
	oForm = $(oForm);
	oForm.hide();
	
	var feedback = oForm.parent().closest(".feedback");
	var loading = feedback.children(".loading");
	
	loading.show();
	
	return true;
}
