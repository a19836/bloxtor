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

$(function () {
	var lis = $(".template_samples_obj li");
	
	$.each(lis, function(idx, li) {
		$(li).children(".sample").tabs();
	});
	
	var icon = lis.first().find(" > .header .icon")[0];
	toggleSampleContent(icon);
});

function toggleSampleContent(elm) {
	elm = $(elm);
	var li = elm.parent().closest("li");
	var sample = li.children(".sample");
	var iframe = sample.children("iframe");
	var is_sample_hidden = sample.css("display") == "none";
	
	if (is_sample_hidden) {
		elm.removeClass("maximize").addClass("minimize");
		
		if (!iframe.attr("src"))
			iframe.attr("src", iframe.attr("orig_src"));
		
		sample.show();
	}
	else {
		elm.removeClass("minimize").addClass("maximize");
		sample.hide();
	}
	
}
