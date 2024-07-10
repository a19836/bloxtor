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
