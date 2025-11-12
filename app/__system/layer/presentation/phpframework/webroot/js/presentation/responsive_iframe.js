/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

function onChangeTemplateLayoutScreenToDesktop(elm) {
	elm = $(elm);
	var p = elm.parent();
	
	elm.addClass("active");
	p.addClass("desktop");
	p.children(".mobile").removeClass("active");
	
	onChangeTemplateLayoutScreenSize(elm[0]);
}

function onChangeTemplateLayoutScreenToMobile(elm) {
	elm = $(elm);
	var p = elm.parent();
	
	elm.addClass("active");
	p.removeClass("desktop");
	p.children(".desktop").removeClass("active");
	
	onChangeTemplateLayoutScreenSize(elm[0]);
}

function onChangeTemplateLayoutScreenSize(elm) {
	var p = $(elm).parent().closest(".iframe_toolbar");
	var main_parent = p.parent();
	var is_desktop = p.hasClass("desktop");
	var width = "";
	var height = "";
	var mt = 0;
	var fit = p.children(".fit_to_screen").is(":checked");
	
	if (!is_desktop) {
		width = p.children(".width").val();
		height = p.children(".height").val();
	}
	
	var max_width = main_parent.width();
	var max_height = main_parent.height();
	
	if (fit && $.isNumeric(width) && width > max_width) {
		StatusMessageHandler.showError("Width of " + width + "px exceeds the maximum width of " + max_width + "px!");
		width = max_width;
		p.children(".width").val(width);
	}
	
	if (fit && $.isNumeric(height) && height > max_height) {
		StatusMessageHandler.showError("Height of " + height + "px exceeds the maximum height of " + max_height + "px!");
		height = max_height;
		p.children(".height").val(height);
	}
	else if ($.isNumeric(height) && height < max_height)
		mt = parseInt((max_height - height) / 2);
	
	var iframe = main_parent.children("iframe");
	var iframe_body = $(iframe[0].contentWindow.document.body);
	iframe.css({
		"width": width, 
		"height": height,
		"margin-top": mt + "px",
		"margin-bottom": mt + "px",
	});
	
	if (!is_desktop)
		iframe_body.addClass("mobile_cursor");
	else
		iframe_body.removeClass("mobile_cursor");
}
