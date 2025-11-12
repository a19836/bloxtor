/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

function toggleAdvancedTutorials(elm) {
	var p = $(elm).parent().closest(".choose_available_tutorial");
	var targets = p.find(".toggle_advanced_videos a, .next a");
	
	p.toggleClass("with_advanced_tutorials");
	
	if (p.hasClass("with_advanced_tutorials"))
		targets.html("Hide Advanced Videos");
	else
		targets.html("Show Advanced Videos");
	
	$(window).scrollTop(0);
}

function toggleSubTutorials(elm) {
	$(elm).parent().closest("li").toggleClass("open");
}

function openVideoPopup(elm) {
	elm = $(elm);
	var popup = $(".show_video_popup");
	var video_url = elm.attr("video_url");
	var image_url = elm.attr("image_url");
	var p = elm.parent().closest("li, .card");
	var title = "";
	var description = "";
	
	if (p.is(".card")) {
		var p_body = p.children(".card-body");
		title = p_body.find(".card-title").text();
		description = p_body.find(".card-text").html();
	}
	else {
		title = p.find(".tutorial_title").text();
		description = p.find(".tutorial_description").html();
	}
	
	if (description)
		popup.find(".description").show();
	else
		popup.find(".description").hide();
	
	popup.find(".title").html(title);
	popup.find(".description").html(description);
	popup.find(".image").show().attr("src", image_url);
	popup.find("iframe").attr("src", video_url).attr("title", title).css("background-image", "url(" + image_url + ")");
	
	MyFancyPopup.init({
		elementToShow: popup,
		parentElement: document,
		onClose: function() {
			popup.find("iframe").removeAttr("src"); //remove src attribute so the video stops playing, in case is playing already...
		},
	});
	
	MyFancyPopup.showPopup();
}

