function toggleLeftpanel(elm) {
	elm = $(elm);
	var body = $("body");
	
	if (body.hasClass("side_panel_shrinked")) {
		body.removeClass("side_panel_shrinked");
		//elm.removeClass("fa-arrow-right").addClass("fa-arrow-left");
	}
	else {
		body.addClass("side_panel_shrinked");
		//elm.removeClass("fa-arrow-left").addClass("fa-arrow-right");
	}
}

function showSubMenu(elm) {
	event.preventDefault();
	event.stopPropagation();
	
	$(elm).parent().closest("li").toggleClass("open");
	
	return false;
}
