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

var saved_mp_obj_id = null;

$(function () {
	$(window).bind('beforeunload', function () {
		if (isMPChanged()) {
			if (window.parent && window.parent.iframe_overlay)
				window.parent.iframe_overlay.hide();
			
			return "If you proceed your changes won't be saved. Do you wish to continue?";
		}
		
		return null;
	});
	
	//init ui
	MyFancyPopup.init({
		parentElement: window,
	});
	
	//set saved_mp_obj_id
	saved_mp_obj_id = getMPObjId();
});

function getMPObjId() {
	return $(".projects_list > form .default_project select").val();
}

function isMPChanged() {
	var new_mp_obj_id = getMPObjId();
	
	return saved_mp_obj_id != new_mp_obj_id;
}

function submitForm(elm) {
	elm = $(elm);
	var oForm = elm.parent().closest(".top_bar").parent().find(".projects_list form");
	elm.hide();
	oForm.submit();
}

function showProjectsLayer(elm) {
	var selected_option = elm.options[elm.selectedIndex];
	var bean_name = selected_option.getAttribute("bean_name");
	var bean_file_name = selected_option.getAttribute("bean_file_name");
	
	var url = "" + document.location;
	var pos = url.indexOf("?");
	
	if (pos != -1)
		url = url.substr(0, pos);
	
	url += "?bean_name=" + bean_name + "&bean_file_name=" + bean_file_name;
	
	document.location = url;
}

function deleteProject(elm, url) {
	if (confirm("Are you sure that you wish to delete this project?")) {
		if (confirm("If you delete this project, you will loose all the created pages and other files inside of this project!\nDo you wish to continue?")) {
			if (confirm("LAST WARNING:\nIf you proceed, you cannot undo this deletion!\nAre you sure you wish to remove this project?")) {
				MyFancyPopup.showOverlay();
				MyFancyPopup.showLoading();
	
				$.ajax({
					type : "get",
					url : url,
					dataType : "text",
					success : function(data, textStatus, jqXHR) {
						if (data == "1") {
							var tr = $(elm).parent().parent();
							tr.remove();
				
							//StatusMessageHandler.showMessage("Project successfully deleted!");
							alert("Project successfully deleted!");
							
							refreshPage();
						}
						else
							StatusMessageHandler.showError("Error: Project not deleted! Please try again." + (data ? "\n" + data : ""));
					},
					error : function(jqXHR, textStatus, errorThrown) { 
						if (jqXHR.responseText);
							StatusMessageHandler.showError(jqXHR.responseText);
					}
				}).always(function() {
					MyFancyPopup.hidePopup();
				});
			}
		}
	}
}

function addProject(elm, url, show_programs) {
	var choose_available_project= $(".choose_available_project");
	var option = choose_available_project.find(" > .layer select option:selected").first();
	var folder_to_filter = choose_available_project.find(".current_project_folder").attr("folder_to_filter");
	var bean_name = option.attr("bean_name");
	var bean_file_name = option.attr("bean_file_name");
	var path = folder_to_filter ? folder_to_filter + "/" : "";
	url = url.replace("#bean_name#", bean_name).replace("#bean_file_name#", bean_file_name).replace("#path#", path);
	
	//get popup
	var popup = $("body > .edit_project_details_popup");
	
	if (!popup[0]) {
		popup = $('<div class="myfancypopup with_iframe_title edit_project_details_popup"></div>');
		$(document.body).append(popup);
	}
	
	if (show_programs)
		popup.addClass("big");
	else
		popup.removeClass("big");
	
	popup.html('<iframe></iframe>'); //cleans the iframe so we don't see the previous html
	
	//prepare popup iframe
	var iframe = popup.children("iframe");
	iframe.attr("src", url);
	
	//open popup
	MyFancyPopup.init({
		elementToShow: popup,
		parentElement: document
	});
	
	MyFancyPopup.showPopup();
}

function editProject(elm, url) {
	addProject(elm, url, false);
}

function onSuccessfullAddProject(opts) {
	var filter_by_layout, bean_name, bean_file_name, project;
	
	if (opts) {
		filter_by_layout = opts["new_filter_by_layout"];
		bean_name = opts["new_bean_name"];
		bean_file_name = opts["new_bean_file_name"];
		project = opts["new_project"];
	}
	
	if (filter_by_layout || (bean_name && bean_file_name && project)) {
		if (window.parent && window.parent != window) {
			var parent_url = window.parent.location;
			
			parent_url = "" + parent_url; //convert to string
			parent_url = parent_url.indexOf("#") != -1 ? parent_url.substr(0, parent_url.indexOf("#")) : parent_url; //remove # so it can refresh parent page
			parent_url = parent_url.replace(/(bean_name|bean_file_name|project|filter_by_layout)=([^&]*)&?/g, ""); //erase previous bean_name|bean_file_name|project|filter_by_layout attributes
			parent_url += parent_url.indexOf("?") != -1 ? "" : "?"; //add "?" if apply
			parent_url += "&bean_name=" + bean_name + "&bean_file_name=" + bean_file_name + "&project=" + project + "&filter_by_layout=" + filter_by_layout; //add new bean_name|bean_file_name|project|filter_by_layout
			parent_url = parent_url.replace(/\?&+/, "?"); //replace "?&&&" with "?"
			
			if (filter_by_layout) {
				var url = admin_home_project_page_url.replace("#filter_by_layout#", filter_by_layout);
				
				//set cookie with default page
				window.MyJSLib.CookieHandler.setCurrentDomainEternalRootSafeCookie('default_page', url); //save cookie with url, so when we refresh the browser, the right panel contains the latest opened url
			}
			
			window.parent.location = parent_url;
		}
		else if (filter_by_layout) {
			var url = admin_home_project_page_url.replace("#filter_by_layout#", filter_by_layout);
			
			//set cookie with default page
			window.MyJSLib.CookieHandler.setCurrentDomainEternalRootSafeCookie('default_page', url); //save cookie with url, so when we refresh the browser, the right panel contains the latest opened url
			
			document.location = url;
		}
		else //should not enter here, because opts should always exists
			refreshPage();
	}
	else //should not enter here, because opts should always exists
		refreshPage();
}

function onSuccessfullEditProject() {
	refreshPage();
}

function refreshPage() {
	var url = document.location;
	
	if (window.parent && window.parent != window) {
		//set cookie with default page
		window.parent.MyJSLib.CookieHandler.setCurrentDomainEternalRootSafeCookie('default_page', url); //save cookie with url, so when we refresh the browser, the right panel contains the latest opened url
		
		var parent_url = window.parent.location;
		window.parent.location = parent_url;
	}
	else
		document.location = url;
}

function goToUrl(elm, url) {
	document.location = url;
}

function openWindow(elm, url, tab) {
	var win = typeof tab != "undefined" && tab ? window.open(url, tab) : window.open(url);
	
	if(win) { //Browser has allowed it to be opened
		win.focus();
		return win;
	}
	else //Broswer has blocked it
		alert('Please allow popups for this site');
}
