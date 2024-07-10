function submitForm(elm) {
	elm = $(elm);
	var oForm = elm.parent().closest(".top_bar").parent().children("form");
	elm.hide();
	oForm.submit();
	
	setTimeout(function() {
		elm.show();
	}, 1000);
}
