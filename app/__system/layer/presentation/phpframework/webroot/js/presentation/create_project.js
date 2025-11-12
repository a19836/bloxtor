/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

$(function () {
	var create_project = $(".create_project");
	
	if (window.parent != window) {
		prepareParentWindowPopup();
		
		if (popup)
			create_project.find(".top_bar.create_project_top_bar").addClass("popup_without_popup_close");
	}
	
	create_project.removeClass("changing_to_step");
});

function prepareParentWindowPopup() {
	var parent_popup = getParentWindowPopup();
	
	if (parent_popup && window.parent != window) {
		if (popup) {
			if (!project_exists) //fix error here when is the first time and the user clicks in cancel
				parent_popup.addClass("popup_with_left_popup_close popup_with_popup_close_button popup_with_popup_close_transparent");
			else {
				//hide the close button for the popup
				parent_popup.addClass("popup_without_popup_close");
				
				parent_popup.removeClass("popup_with_left_popup_close popup_with_popup_close_button popup_with_popup_close_transparent");
			}
			
			//in case the popup gets closed without being from the cancel button, we must set the onClose handler from the parent popup.
			if (typeof window.parent.MyFancyPopup == "object" && parent_popup.is(window.parent.MyFancyPopup.settings.elementToShow)) {
				if (window.parent.MyFancyPopup.isPopupOpened()) {
					var func = window.parent.MyFancyPopup.settings.onClose;
					
					window.parent.MyFancyPopup.settings.onClose = function() {
						parent_popup.removeClass("popup_without_popup_close popup_with_left_popup_close popup_with_popup_close_button popup_with_popup_close_transparent");
						
						window.parent.MyFancyPopup.settings.onClose = func;
					};
				}
				else //if was closed very fast
					parent_popup.removeClass("popup_without_popup_close popup_with_left_popup_close popup_with_popup_close_button popup_with_popup_close_transparent");
			}
		}
		else {
			//show the close button for the popup
			parent_popup.removeClass("popup_without_popup_close popup_with_left_popup_close popup_with_popup_close_button popup_with_popup_close_transparent");
		}
	}
}

function getParentWindowPopup() {
	if (!window.parent_popup) {
		var parent_iframe = null;
		
		$.each($(window.parent.document.body).find("iframe"), function(idx, iframe) {
			if (iframe.contentWindow == window) {
				parent_iframe = iframe;
				return false;
			}
		});
		
		window.parent_popup = parent_iframe ? $(parent_iframe).parent().closest(".myfancypopup") : null;
	}
	
	return window.parent_popup;
}

function cancel() { //This function is only used on a popup
	var create_project = $(".create_project");
	var parent_popup = getParentWindowPopup();
	
	if (parent_popup && window.parent != window) {
		parent_popup.removeClass("popup_without_popup_close popup_with_left_popup_close popup_with_popup_close_button popup_with_popup_close_transparent");
		create_project.find(".top_bar.create_project_top_bar").removeClass("popup_without_popup_close");
	}
	
	create_project.children(".creation_step").children().not(".top_bar").remove();
	
	//call onSucessfullProjectCreation
	var url = null;
	
	if (typeof window.parent.parent.goTo == "function" && window.parent.parent != window.parent) //if inside of the admin_home_project.php which is inside of the admin_advanced.php
		url = window.parent.document.location;
	else if (window.parent != window) { //if inside of the admin_advanced.php
		if (typeof window.parent.goTo == "function")
			url = MyJSLib.CookieHandler.getCookie('default_page');
		else
			url = window.parent.document.location;
	}
	//else - should never enter here bc this function will only be called when there is a popup, this is, when: window.parent != window
	
	onSucessfullProjectCreation(url);
}

function goToProjectDashboard(url) {
	onSucessfullProjectCreation(url);
}

function onSucessfullProjectCreation(url) {
	var func = null;
	var is_ctrl_key_pressed = window.event && (window.event.ctrlKey || window.event.keyCode == 65);
	
	if (on_success_js_func_name) {
		eval("func = typeof window.parent." + on_success_js_func_name + " == 'function' ? window.parent." + on_success_js_func_name + " : null;");

		if (!func) //could be inside of the admin_home_project.php which is inside of the admin_advanced.php
			eval("func = typeof window.parent.parent." + on_success_js_func_name + " == 'function' ? window.parent.parent." + on_success_js_func_name + " : null;");

		if (func) {
			if (!$.isPlainObject(on_success_js_func_opts))
				on_success_js_func_opts = {};
			
			on_success_js_func_opts["is_ctrl_key_pressed"] = is_ctrl_key_pressed;
			
			func(on_success_js_func_opts);
		}
	}
	
	if (!func && url) {
		if (is_ctrl_key_pressed) {
			var rand = Math.random() * 10000;
			var win = window.open(url, "tab" + rand);
			
			if (win) { //Browser has allowed it to be opened
				win.focus();
				
				//refreshes parent window
				if (window.parent.parent != window.parent)
					window.parent.parent.document.location = "" + window.parent.parent.document.location;
				else if (window.parent != window)
					window.parent.document.location = "" + window.parent.document.location;
				
				return true; //don't execute code below.
			}
		}
		
		if (typeof window.parent.parent.goToHandler == "function" && window.parent.parent != window.parent) //if inside of the admin_home_project.php which is inside of the admin_advanced.php
			window.parent.document.location = url;
		else if (window.parent != window) { //if inside of the admin_advanced.php
			if (typeof window.parent.goToHandler == "function") {
				window.parent.goToHandler(url);
				window.parent.MyFancyPopup.hidePopup();
			}
			else //if in an independent window
				window.parent.document.location = url;
		}
		else
			document.location = url;
	}
}

function appendDataToForm(oForm, data, prefix_name) {
	if ($.isPlainObject(data) || $.isArray(data))
		$.each(data, function(k, v) {
			var name = prefix_name ? prefix_name + "[" + k + "]" : k;
			
			if ($.isPlainObject(v) || $.isArray(v))
				appendDataToForm(oForm, v, name);
			else {
				var input = $('<input type="hidden" name="' + name + '"/>');
				input.val(v);
				
				oForm.append(input);
			}
		});
}

function goToUrl(url) {
	var create_project = $(".create_project");
	create_project.addClass("changing_to_step");
	
	document.location = url;
}

function postToUrl(url, data) {
	var create_project = $(".create_project");
	create_project.addClass("changing_to_step");
	
	var oForm = $('<form method="post" action="' + url + '"></form>');
	oForm.hide();
	
	appendDataToForm(oForm, data);
	create_project.append(oForm);
	
	oForm.submit();
}

function createProject(elm) {
	var create_project = $(".create_project");
	create_project.addClass("changing_to_step");
	
	var btn = create_project.find(".edit_project_details .buttons").find("input, button");
	btn.trigger("click");
	
	setTimeout(function() {
		if (!btn.hasClass("loading"))
			create_project.removeClass("changing_to_step");
	}, 1000);
}

function initInstallPrograms() {
	initStorePrograms();
	
	var create_project = $(".create_project");
	var install_program = create_project.find(".install_program .step_0 .install_program_step_0_with_tabs");
	var a = create_project.find(".top_bar.create_project_top_bar header > ul > li.continue > a");
	
	//init input file upload
	install_program.find(".file_upload form input.upload_file").on("change", function() {
		if (this.files.length > 0)
			a.addClass("active");
		else
			a.removeClass("active");
	});
	
	install_program.find(" > ul > li > a").on("click", function() {
		setTimeout(function() {
			var active_tab = install_program.tabs("option", "active");
			var exists_selection = false;
			
			if (active_tab == 0)
				exists_selection = install_program.find(".install_store_program > ul > li.selected").length > 0;
			else 
				exists_selection = install_program.find(".file_upload form input.upload_file")[0].files.length > 0;
			
			if (exists_selection)
				a.addClass("active");
			else
				a.removeClass("active");
		}, 300);
	});
}

function initStorePrograms() {
	if (get_store_programs_url)
		$.ajaxSetup({
			complete: function(jqXHR, textStatus) {
				if (jqXHR.status == 200 && this.url.indexOf(get_store_programs_url) != -1) {
					var create_project = $(".create_project");
					var a = create_project.find(".top_bar.create_project_top_bar header > ul > li.continue > a");
					
					if (!loaded_programs || $.isEmptyObject(loaded_programs))
						a.addClass("active");
					else {
						var lis = create_project.find(".install_program .step_0 .install_program_step_0_with_tabs .install_store_program > ul > li");
						
						lis.each(function(idx, item) {
							item = $(item);
							
							if (!item.attr("url"))
								item.hide();
							
							item.on("click", function(event) {
								var li = $(this);
								var is_selected = li.hasClass("selected");
								
								lis.removeClass("selected");
								a.removeClass("active");
								
								if (!is_selected) {
									li.addClass("selected");
									a.addClass("active");
								}
							});
						});
					}
				}
		    	}
		});
}

function chooseProgram(elm, choose_url, post_data) {
	//if no data in store, then redirect to the final creation_step
	if (!loaded_programs || $.isEmptyObject(loaded_programs)) {
		choose_url = choose_url.indexOf("#") != -1 ? choose_url.substr(0, choose_url.indexOf("#")) : choose_url; //remove # so it can refresh page
		choose_url = choose_url.replace(/creation_step=([^&]*)&?/g, ""); //erase previous creation_step attribute
		choose_url += choose_url.indexOf("?") != -1 ? "" : "?"; //add "?" if apply
		choose_url += "&creation_step=2"; //add creation_step to show successfull message
		choose_url = choose_url.replace(/\?&+/, "?"); //replace "?&&&" with "?"
		
		postToUrl(choose_url, post_data);
	}
	else {
		var install_program = $(".create_project .install_program .step_0 .install_program_step_0_with_tabs");
		var active_tab = install_program.tabs("option", "active");
		
		if (active_tab == 0) { //if store
			var li = install_program.find(".install_store_program > ul > li.selected");
			
			if (!li[0])
				StatusMessageHandler.showError("You must select a program first.\nOr go back and click in the 'Empty Project' button.");
			else {
				post_data["step"] = 1;
				post_data["program_url"] = li.attr("url");
				
				postToUrl(choose_url, post_data);
			}
		}
		else { //if file_upload
			var oForm = install_program.find(".file_upload form");
			var zip_file = oForm.find("input.upload_file");
			
			if (zip_file[0] && zip_file[0].files.length > 0) {
				oForm.attr("action", choose_url);
				oForm.find(".upload_url").remove();
				oForm.submit();
			}
			else
				StatusMessageHandler.showError("You must upload a program first!");
		}
	}
}

function toggleLocalUpload(elm) {
	elm = $(elm);
	var create_project = $(".create_project");
	var install_program = create_project.find(".install_program .step_0 .install_program_step_0_with_tabs");
	var active_tab = install_program.tabs("option", "active");
	
	create_project.toggleClass("local_upload_shown");
	
	if (active_tab == 1)
		install_program.find(" > ul > li:first-child > a").first().trigger("click");
	
	if (create_project.hasClass("local_upload_shown"))
		elm.html("Hide Advanced Features");
	else
		elm.html("Show Advanced Features");
}

function installProgramStep(elm, data) {
	$(elm).removeAttr("onClick");
	
	var create_project = $(".create_project");
	create_project.addClass("changing_to_step");
	
	var install_program = create_project.find(".install_program");
	var oForm = install_program.find("form").first();
	
	appendDataToForm(oForm, data);
	
	var a = install_program.find(".top_bar > header > ul li.continue > a");
	a.trigger("click");
	
	setTimeout(function() {
		if (!a.hasClass("loading"))
			create_project.removeClass("changing_to_step");
	}, 1000);
}
