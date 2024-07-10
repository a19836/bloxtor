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
