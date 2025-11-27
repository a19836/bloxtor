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
	var lis = $(".template_region_obj li");
	
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
	var is_sample_hidden = sample.css("display") == "none";
	
	if (is_sample_hidden) {
		elm.removeClass("maximize").addClass("minimize");
		
		var iframe = sample.find("iframe");
		
		if (!iframe.attr("src")) {
			iframe.attr("src", iframe.attr("orig_src"));
			
			iframe.load(function() {
				$(this.contentWindow.document.body).find(".selected_region_sample").css({
					border: "2px solid red",
				});
			});
		}
		
		var view_source = sample.children(".view_source");
		var textarea = view_source.children("textarea")[0];
		
		if (textarea) {
			ace.require("ace/ext/language_tools");
			
			var editor = ace.edit(textarea);
			editor.setTheme("ace/theme/chrome");
			editor.session.setMode("ace/mode/html");
			editor.setOptions({
				enableBasicAutocompletion: true,
				enableSnippets: true,
				enableLiveAutocompletion: true,
			});
			editor.setOption("wrap", true);
			
			if (typeof setCodeEditorAutoCompleter == "function")
				setCodeEditorAutoCompleter(editor);
			
			view_source.find("textarea.ace_text-input").removeClass("ace_text-input"); //fixing problem with scroll up, where when focused or pressed key inside editor the page scrolls to top.
			
			view_source.data("editor", editor);
		}
		
		sample.show();
	}
	else {
		elm.removeClass("minimize").addClass("maximize");
		sample.hide();
	}
	
}
