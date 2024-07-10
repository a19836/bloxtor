var scroll_top = 0;
var editor_code_type = "php";
var code_id = '';
var auto_scroll_active = true;

$(function () {
	MyFancyPopup.init({
		parentElement: window,
	});
	MyFancyPopup.showOverlay();
	MyFancyPopup.showLoading();
	
	if (!readonly) {
		$(window).bind('beforeunload', function () {
			if (isCodeChanged()) {
				if (window.parent && window.parent.iframe_overlay)
					window.parent.iframe_overlay.hide();
				
				return "If you proceed your changes won't be saved. Do you wish to continue?";
			}
			
			return null;
		});
		
		//init auto save
		addAutoSaveMenu(".top_bar li.dummy_elm_to_add_auto_save_options");
		enableAutoSave(onToggleAutoSave);
		initAutoSave(".top_bar li.sub_menu li.save a");
	}
	
	//init ui
	var code_area = $(".code_area");
	var textarea = code_area.children("textarea")[0];
	
	if (textarea) {
		ace.require("ace/ext/language_tools");
		
		var options = {
			enableBasicAutocompletion: true,
			enableSnippets: true,
			enableLiveAutocompletion: false,
		};
		
		if (readonly) {
			options["readOnly"] = true;
		    	options["highlightActiveLine"] = false;
			options["highlightGutterLine"] = false;
		}
		
		var editor = ace.edit(textarea);
		editor.setTheme("ace/theme/chrome");
		editor.session.setMode("ace/mode/" + editor_code_type);
		editor.setAutoScrollEditorIntoView(true);
		//editor.setOption("maxLines", "Infinity");
		editor.setOption("minLines", 30);
		editor.setOption("wrap", true);
		editor.setOptions(options);
		//editor.$blockScrolling = "Infinity";
		
		if (readonly)
			editor.renderer.$cursorLayer.element.style.opacity = 0
		
		if (!readonly)
			editor.commands.addCommand({
				name: "saveFile",
				bindKey: {
					win: "Ctrl-S",
					mac: "Command-S",
					sender: "editor|cli"
				},
				exec: function(env, args, request) {
					save(false);
				},
			});
		
		code_area.find("textarea.ace_text-input").removeClass("ace_text-input"); //fixing problem with scroll up, where when focused or pressed key inside editor the page scrolls to top.
		
		code_area.data("editor", editor);

		editor.focus();
	}
	
	if (scroll_top > 0)
		$(window).scrollTop(scroll_top);
	
	var old_file_code = $(".confirm_save .file_code .old_file_code pre");
	var new_file_code = $(".confirm_save .file_code .new_file_code pre");
	
	if (old_file_code.children("code")[0])
		hljs.highlightBlock( old_file_code.children("code")[0] );
	
	old_file_code.scroll(function() {
		if (auto_scroll_active) {
			new_file_code.scrollTop( $(this).scrollTop() );
			//new_file_code.scrollLeft( $(this).scrollLeft() );
		}
	});
	
	new_file_code.scroll(function() {
		if (auto_scroll_active) {
			old_file_code.scrollTop( $(this).scrollTop() );
			//old_file_code.scrollLeft( $(this).scrollLeft() );
		}
	});
	
	MyFancyPopup.hidePopup();
});

function enableDisableAutoScroll(elm) {
	auto_scroll_active = !auto_scroll_active;
	
	$(elm).html(auto_scroll_active ? "Click here to disable auto scroll." : "Click here to enable auto scroll.");
}

function isCodeChanged() {
	var code_area = $(".code_area");
	var editor = code_area.data("editor");
	var code = editor ? editor.getValue() : code_area.children("textarea").val();
	
	return code_id != $.md5(code);
}

function save(force) {
	if (!readonly) {
		prepareAutoSaveVars();
		
		//only saves if object is different
		if (isCodeChanged() || force) {
			if (!is_from_auto_save) {
				MyFancyPopup.showOverlay();
				MyFancyPopup.showLoading();
			}
			
			var editor = $(".code_area").data("editor");
			var code = editor.getValue();
			var url = window.location.href + "&scroll_top=" + $(window).scrollTop() + (file_modified_time ? "&file_modified_time=" + file_modified_time : "");
			
			$.ajax({
				type : "post",
				url : url,
				data : {"force" : force ? 1 : 0, "code_id" : code_id, "code" : code},
				dataType : "text",
				success : function(data, textStatus, jqXHR) {
					if (jquery_native_xhr_object && isAjaxReturnedResponseLogin(jquery_native_xhr_object.responseURL))
						showAjaxLoginPopup(jquery_native_xhr_object.responseURL, url, function() {
							save(force);
						});
					else {
						var json_data = data && ("" + data).substr(0, 1) == "{" ? JSON.parse(data) : null;
						var status = parseInt(data) == 1 || ($.isPlainObject(json_data) && json_data["status"] == 1);
						var file_was_changed = !status && $.isPlainObject(json_data) && json_data["status"] == "CHANGED";
						
						if(status) {
							if ($.isPlainObject(json_data) && json_data["code_id"])
								code_id = json_data["code_id"];
							
							if ($.isPlainObject(json_data) && json_data["modified_time"])
								file_modified_time = json_data["modified_time"];
							
							$(".confirm_save").hide();
							editor.focus();
							
							if (!is_from_auto_save) //only show message if a manual save action
								StatusMessageHandler.showMessage("File saved successfully.", "", "bottom_messages", 1500);
						}
						else if (file_was_changed) 
							showSavingActionConfirmation(json_data["old_code"], json_data["new_code"]);
						else
							StatusMessageHandler.showError("Error trying to save new changes. Please try again..." + (data ? "\n" + data : ""));
					}
						
					if (!is_from_auto_save)
						MyFancyPopup.hidePopup();
					else
						resetAutoSave();
				},
				error : function(jqXHR, textStatus, errorThrown) { 
					var msg = jqXHR.responseText ? "\n" + jqXHR.responseText : "";
					StatusMessageHandler.showError((errorThrown ? errorThrown + " error.\n" : "") + "Error trying to save new changes.\nPlease try again..." + msg);
					
					if (!is_from_auto_save)
						MyFancyPopup.hidePopup();
					else
						resetAutoSave();
				},
				timeout: is_from_auto_save && auto_save_connection_ttl ? auto_save_connection_ttl : 0,
			});
		}
		else if (!is_from_auto_save) {
			StatusMessageHandler.showMessage("Nothing to save.", "", "bottom_messages", 1500);
		}
		else
			resetAutoSave();
	}
}

function showSavingActionConfirmation(old_code, new_code) {
	var confirm_save_elm = $(".confirm_save");
	
	var old_code_area = confirm_save_elm.find(".file_code .old_file_code pre code");
	var old_code_parsed = old_code ? old_code.replace(/>/g, "&gt;").replace(/</g, "&lt;") : "";
	old_code_area.html(old_code_parsed);
	
	var new_code_area = confirm_save_elm.find(".file_code .new_file_code pre code");
	var new_code_parsed = new_code ? new_code.replace(/>/g, "&gt;").replace(/</g, "&lt;") : "";
	new_code_area.html(new_code_parsed);
	
	confirm_save_elm.show();
	
	if (typeof hljs == "object") {
		hljs.highlightBlock(old_code_area[0]);
		hljs.highlightBlock(new_code_area[0]);
	}
	
	if (old_code.trim() == "" || old_code.trim().hashCode() == new_code.trim().hashCode())
		confirm_save_elm.find(".buttons > input[name='save']").trigger("click");
}

function cancelSave() {
	$(".confirm_save").hide();
	
	var editor = $(".code_area").data("editor");
	editor.focus();
}

function prettyPrintCode() {
	var editor = $(".code_area").data("editor");

	var code = editor ? editor.getValue() : $(".code_area textarea").first().val();
	code = MyHtmlBeautify.beautify(code);
	code = code.replace(/^\s+/g, "").replace(/\s+$/g, "");
	
	if (editor) {
		editor.setValue(code);
	}
	else {
		$(".code_area textarea").first().val(code);
	}
}

function setWordWrap(elm) {
	var editor = $(".code_area").data("editor");

	if (editor) {
		var wrap = $(elm).attr("wrap") == 1 ? false : true;
		$(elm).attr("wrap", wrap ? 1 : 0);
	
		editor.getSession().setUseWrapMode(wrap);
		//alert("Wrap is now " + (wrap ? "enable" : "disable"));
		StatusMessageHandler.showMessage("Wrap is now " + (wrap ? "enable" : "disable"), "", "bottom_messages", 1500);
	}
}

function openEditorSettings() {
	var editor = $(".code_area").data("editor");

	if (editor) {
		editor.execCommand("showSettingsMenu");
		
		//prepare font size option
		setTimeout(function() {
			var input = $("#ace_settingsmenu input#setFontSize");
			
			if (input[0]) {
				var value = input.val();
				var title = "eg: 12px, 12em, 12rem, 12pt or 120%";
				
				input.attr("title", title).attr("placeHolder", title);
				input.after('<div style="text-align:right; opacity:.5;">' + title + '</div>');
				
				if ($.isNumeric(value))
					input.val(value + "px");
				
				if (input.data("with_keyup_set") != 1) {
					input.data("with_keyup_set", 1);
					
					input.on("keyup", function() {
						var v = $(this).val();
						
						if (v.match(/([0-9]+(\.[0-9]*)?|\.[0-9]+)(px|em|rem|%|pt)/i))
							$(this).trigger("blur").focus();
					});
				}
			}
		}, 300);
	}
	else {
		//alert("Error trying to open the editor settings...");
		StatusMessageHandler.showError("Error trying to open the editor settings...");
	}
}
