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

function toggleContent(elm) {
	elm = $(elm);
	var li = elm.parent().closest("li");
	var content = li.children(".content");
	var is_content_hidden = content.css("display") == "none";
	
	if (is_content_hidden) {
		elm.removeClass("maximize").addClass("minimize");
		
		if (li.hasClass("sample")) {
			content.tabs();
			
			var iframe = content.find("iframe");
			
			if (!iframe.attr("src")) {
				iframe.attr("src", iframe.attr("orig_src"));
				
				iframe.load(function() {
					$(this.contentWindow.document.body).find(".selected_region_sample").css({
						border: "2px solid red",
					});
				});
			}
			
			var view_source = content.children(".view_source");
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
		}
		
		content.show();
	}
	else {
		elm.removeClass("minimize").addClass("maximize");
		content.hide();
	}
	
}

function openTemplateSamples(elm) {
	var url = elm.getAttribute("template_samples_url");
	
	//get popup
	var popup = $("body > .template_region_info_popup");
	
	if (!popup[0]) {
		popup = $('<div class="myfancypopup with_iframe_title template_region_info_popup"></div>');
		$(document.body).append(popup);
	}
	
	popup.html('<iframe></iframe>'); //cleans the iframe so we don't see the previous html
	
	//prepare popup iframe
	url += (url.indexOf("?") == -1 ? "?" : "&") + "popup=1";
	
	var iframe = popup.children("iframe");
	iframe.attr("src", url);
	
	//open popup
	MyFancyPopup.init({
		elementToShow: popup,
		parentElement: $(".templates_regions_html_obj"),
	});
	
	MyFancyPopup.showPopup();
}

