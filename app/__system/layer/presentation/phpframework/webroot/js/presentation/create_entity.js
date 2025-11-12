/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

$(function () {
	var create_entity = $(".create_entity");
	
	if (window.parent != window) {
		prepareParentWindowPopup();
		
		if (popup)
			create_entity.find(".top_bar.create_entity_top_bar").addClass("popup_without_popup_close");
	}
	
	create_entity.removeClass("changing_to_step");
	
	//if install page and post action of upload or remote url, show advanced features
	if (create_entity.find(".install_page").length > 0 && (is_remote_url || is_zip_file)) {
		var exists_remote_url = create_entity.find(".install_page .install_page_url input.remote_url").val().length > 0;
		
		if (exists_remote_url)
			toggleLocalUpload( create_entity.find(".sub_title")[0] );
	}
});

function prepareParentWindowPopup() {
	var parent_popup = getParentWindowPopup();
	
	if (parent_popup && window.parent != window) {
		if (popup) { //hide the close button for the popup
			parent_popup.addClass("popup_without_popup_close");
			
			//in case the popup gets closed without being from the cancel button, we must set the onClose handler from the parent popup.
			if (typeof window.parent.MyFancyPopup == "object" && parent_popup.is(window.parent.MyFancyPopup.settings.elementToShow)) {
				if (window.parent.MyFancyPopup.isPopupOpened()) {
					var func = window.parent.MyFancyPopup.settings.onClose;
					
					window.parent.MyFancyPopup.settings.onClose = function() {
						parent_popup.removeClass("popup_without_popup_close");
						
						window.parent.MyFancyPopup.settings.onClose = func;
					};
				}
				else //if was closed very fast
					parent_popup.removeClass("popup_without_popup_close");
			}
		}
		else //show the close button for the popup
			parent_popup.removeClass("popup_without_popup_close");
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
	var create_entity = $(".create_entity");
	
	var parent_popup = getParentWindowPopup();
	
	if (parent_popup && window.parent != window) {
		parent_popup.removeClass("popup_without_popup_close");
		create_entity.find(".top_bar.create_entity_top_bar").removeClass("popup_without_popup_close");
	}
	
	create_entity.children(".creation_step").children().not(".top_bar").remove();
	
	//call onSucessfullPageCreation
	onSucessfullPageCreation();
}

function onSucessfullPageCreation() {
	var func = null;
	var is_ctrl_key_pressed = window.event && (window.event.ctrlKey || window.event.keyCode == 65);
	
	if (on_success_js_func_name) {
		eval("func = typeof window.parent." + on_success_js_func_name + " == 'function' ? window.parent." + on_success_js_func_name + " : null;");

		if (!func) //could be inside of the admin_home_project.php which is inside of the admin_advanced.php
			eval("func = typeof window.parent.parent." + on_success_js_func_name + " == 'function' ? window.parent.parent." + on_success_js_func_name + " : null;");

		if (func) {
			var on_success_js_func_opts = {
				"is_ctrl_key_pressed": is_ctrl_key_pressed
			};
			
			func(on_success_js_func_opts);
		}
	}
	
	if (!func && edit_entity_url) {
		if (is_ctrl_key_pressed) {
			var rand = Math.random() * 10000;
			var win = window.open(edit_entity_url, "tab" + rand);
			
			if (win) { //Browser has allowed it to be opened
				win.focus();
				
				//refreshes parent window
				if (window.parent != window)
					window.parent.document.location = "" + window.parent.document.location;
				
				return true; //don't execute code below.
			}
		}
		
		if (typeof window.parent.parent.goToHandler == "function" && window.parent.parent != window.parent) //if inside of the admin_home_project.php which is inside of the admin_advanced.php
			window.parent.parent.goToHandler(edit_entity_url);
		else if (window.parent != window) { //if inside of the admin_advanced.php
			if (typeof window.parent.goToHandler == "function") {
				window.parent.goToHandler(edit_entity_url);
				window.parent.MyFancyPopup.hidePopup();
			}
			else //if in an independent window
				window.parent.document.location = edit_entity_url;
		}
		else
			document.location = edit_entity_url;
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
	var create_entity = $(".create_entity");
	create_entity.addClass("changing_to_step");
	
	document.location = url;
}

function postToUrl(url, data) {
	var create_entity = $(".create_entity");
	create_entity.addClass("changing_to_step");
	
	var oForm = $('<form method="post" action="' + url + '"></form>');
	oForm.hide();
	
	appendDataToForm(oForm, data);
	create_entity.append(oForm);
	
	oForm.submit();
}

function initInstallPages() {
	initStorePages();
	
	var create_entity = $(".create_entity");
	var install_page = create_entity.find(".install_page");
	var a = create_entity.find(".top_bar.create_entity_top_bar header > ul > li.continue > a");
	
	//init input file upload
	install_page.find(".file_upload form input.upload_file").on("change", function() {
		if (this.files.length > 0)
			a.addClass("active");
		else
			a.removeClass("active");
	});
	
	//init install_store_page
	install_page.find(" > ul > li > a").on("click", function() {
		setTimeout(function() {
			var active_tab = install_page.tabs("option", "active");
			var exists_selection = false;
			
			if (active_tab == 3)
				exists_selection = install_page.find(".install_page_with_ai > ul > li.selected").length > 0;
			else if (active_tab == 2)
				exists_selection = install_page.find(".install_page_url > ul > li.selected").length > 0;
			else if (active_tab == 0)
				exists_selection = install_page.find(".install_store_page > ul > li.selected").length > 0;
			else
				exists_selection = install_page.find(".file_upload form input.upload_file")[0].files.length > 0;
			
			if (exists_selection)
				a.addClass("active");
			else
				a.removeClass("active");
		}, 300);
	});
	
	//init install_page_url and install_page_with_ai
	var input = install_page.find(".install_page_url form input.remote_url");
	var check_value_func = function() {
		var value = $(this).val();
		value = ("" + value).replace(/(^\s+|\s+$)/g, ""); //trim
		
		if (value.length > 0)
			a.addClass("active");
		else
			a.removeClass("active");
	};
	input.on("keyup", check_value_func).on("blur", check_value_func);
	install_page.find(".install_page_with_ai form .instructions textarea").on("keyup", check_value_func).on("blur", check_value_func);
	install_page.find(".install_page_with_ai form .image input").on("change", check_value_func).on("blur", check_value_func);
}

function initStorePages() {
	if (get_store_pages_url)
		$.ajaxSetup({
			complete: function(jqXHR, textStatus) {
				if (jqXHR.status == 200 && this.url.indexOf(get_store_pages_url) != -1) {
					var create_entity = $(".create_entity");
					var a = create_entity.find(".top_bar.create_entity_top_bar header > ul > li.continue > a");
					
					if (!loaded_pages || $.isEmptyObject(loaded_pages))
						a.addClass("active");
					else {
						var lis = create_entity.find(".install_page .install_store_page > ul > li");
						
						lis.each(function(idx, item) {
							item = $(item);
							
							if (!item.attr("url"))
								item.hide();
							
							item.children(".img_label").removeAttr("onClick").off();
							item.children("button").on("click", function(e) {
								e.stopPropagation();
							});
							
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

function viewStorePage(preview_url, zip_url) {
	if (preview_url) {
		var popup = $(".view_store_page_popup");
		
		if (!popup[0]) {
			popup = $('<div class="myfancypopup with_title view_store_page_popup' + (is_popup ? " in_popup" : "") + '"></div>');
			$(document.body).append(popup);
		}
		
		var html = '<div class="title">Pre-built Page Preview <button class="install_page" onClick="selectStorePageSelectionThroughUrl(\'' + zip_url + '\')">Select this pre-built page</button></div>'
				+ '<iframe src="' + preview_url + '"></iframe>';
		popup.html(html);
		
		MyFancyPopupViewPage.init({
			elementToShow: popup,
			parentElement: document,
		});
		MyFancyPopupViewPage.showPopup();
	}
	else
		alert("Error: You cannot view this pre-built page. Please contact the sysadmin.");
}

function selectStorePageSelectionThroughUrl(url) {
	var create_entity = $(".create_entity");
	var ul = create_entity.find(".install_store_page > ul");
	
	ul.children("li").each(function(idx, li) {
		li = $(li);
		
		if (li.attr("url") == url && !li.hasClass("selected")) {
			li.trigger("click");
			
			return false;
		}
	});
	
	MyFancyPopupViewPage.hidePopup();
}

function choosePage(elm, choose_url) {
	//if no data in store, then redirect to the final creation_step
	if (!loaded_pages || $.isEmptyObject(loaded_pages)) {
		var current_url = "" + document.location;
		current_url = current_url.indexOf("#") != -1 ? current_url.substr(0, current_url.indexOf("#")) : current_url; //remove # so it can refresh page
		current_url = current_url.replace(/creation_step=([^&]*)&?/g, ""); //erase previous creation_step attribute
		current_url += current_url.indexOf("?") != -1 ? "" : "?"; //add "?" if apply
		current_url += "&creation_step=2"; //add creation_step to show successfull message
		current_url = current_url.replace(/\?&+/, "?"); //replace "?&&&" with "?"
		
		goToUrl(current_url);
	}
	else {
		var install_page = $(".create_entity .install_page");
		var active_tab = install_page.tabs("option", "active");
		var status = true;
		
		elm = $(elm);
		var on_click = elm.attr("onClick");
		elm.removeAttr("onClick").addClass("loading").prepend('<span class="icon loading"></span>');
		
		if (active_tab == 3) { //if remote url
			var oForm = install_page.find(".install_page_with_ai form");
			var instructions = oForm.find(".instructions textarea").val();
			var image = oForm.find(".image input").val();
			
			if (instructions.replace(/\s+/g, "").length > 0 || image.replace(/\s+/g, "").length > 0) {
				StatusMessageHandler.showMessage("Generating page through AI. Please wait a while...");
				
				oForm.attr("action", choose_url);
				oForm.submit();
			}
			else {
				StatusMessageHandler.showError("Please write the instructions or upload an image with the layout for the page you wish to generate!");
				status = false;
			}
		}
		else if (active_tab == 2) { //if remote url
			var oForm = install_page.find(".install_page_url form");
			var remote_url = oForm.find("input.remote_url").val();
			
			if (remote_url.replace(/\s+/g, "").length > 0) {
				StatusMessageHandler.showMessage("Parsing url '" + remote_url + "' and converting it to a page. Please wait a while...");
				
				oForm.attr("action", choose_url);
				oForm.submit();
			}
			else {
				StatusMessageHandler.showError("Please write an url for the page you wish to install!");
				status = false;
			}
		}
		else if (active_tab == 0) { //if store
			var li = install_page.find(".install_store_page > ul > li.selected");
			
			if (!li[0]) {
				StatusMessageHandler.showError("You must select a pre-built page first.\nOr go back and click in the 'Empty Page' button.");
				status = false;
			}
			else {
				StatusMessageHandler.showMessage("Downloading selected pre-built page and installing it. Please wait a while...");
				
				var post_data = {
					zip_url: li.attr("url")
				};
				postToUrl(choose_url, post_data);
			}
		}
		else { //if file_upload
			var oForm = install_page.find(".file_upload form");
			var zip_file = oForm.find("input.upload_file");
			
			if (zip_file[0] && zip_file[0].files.length > 0) {
				StatusMessageHandler.showMessage("Uploading file and installing it. Please wait a while...");
				
				oForm.attr("action", choose_url);
				oForm.find(".upload_url").remove();
				oForm.submit();
			}
			else {
				StatusMessageHandler.showError("You must upload a pre-built page first!");
				status = false;
			}
		}
		
		if (!status)
			elm.removeClass("loading").attr("onClick", on_click).children(".icon.loading").remove();
		/*else
			setTimeout(function() {
				elm.removeClass("loading").attr("onClick", on_click).children(".icon.loading").remove();
			}, 2000);*/
	}
}

function toggleLocalUpload(elm) {
	elm = $(elm);
	var create_entity = $(".create_entity");
	var install_page = create_entity.find(".install_page");
	var active_tab = install_page.tabs("option", "active");
	
	create_entity.toggleClass("local_upload_shown");
	
	if (active_tab == 1)
		install_page.find(" > ul > li:first-child > a").first().trigger("click");
	
	if (create_entity.hasClass("local_upload_shown"))
		elm.html("Hide Advanced Features");
	else
		elm.html("Show Advanced Features");
}
