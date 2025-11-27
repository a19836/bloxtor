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

var loaded_programs = {};

$(function () {
	$(window).resize(function() {
		MyFancyPopup.updatePopup();
	});
});

function submitForm(elm, on_submit_func) {
	elm = $(elm);
	var oForm = elm.parent().closest(".top_bar").parent().find("form").first();
	var status = typeof on_submit_func == "function" ? on_submit_func( oForm[0] ) : true;
	
	if (status) {
		var on_click = elm.attr("onClick");
		elm.addClass("loading").removeAttr("onClick");
		
		oForm.submit();
		
		/*setTimeout(function() {
			elm.removeClass("loading").attr("onClick", on_click);
		}, 2000);*/
	}
	
	return status;
}

function checkUploadedFiles(oForm) {
	oForm = $(oForm);
	
	if (oForm[0]) {
		var zip_url = oForm.find(".upload_url input");
		var zip_file = oForm.find("input.upload_file");
		var status = (zip_url[0] && zip_url.val() != "") || (zip_file[0] && zip_file[0].files.length > 0);
		
		if (status)
			return true;
		
		if ($(".install_program .step_0 .install_program_step_0_with_tabs").tabs("option", "active") == 0)
			StatusMessageHandler.showError("Please click in one of the available programs to install!");
		else
			StatusMessageHandler.showError("You must upload a program first!");
	}
	else
		StatusMessageHandler.showError("form object undefined! Please contact the sysadmin...");
	
	return false;
}

function openUsersManagementAdminPanelPopup(elm) {
	elm = $(elm);
	var popup_elm = elm.parent().closest(".users_permissions").children(".users_management_admin_panel_popup");
	var iframe = popup_elm.children("iframe");
	
	if (iframe[0])
		iframe.remove();
	
	popup_elm.append('<iframe></iframe>');
	
	var url = modules_admin_panel_url;
	
	MyFancyPopup.init({
		elementToShow: popup_elm,
		type: "iframe",
		url: url,
	});
	MyFancyPopup.showPopup();
}

function initInstallStoreProgram() {
	if (get_store_programs_url)
		$.ajax({
			type : "get",
			url : get_store_programs_url,
			dataType : "json",
			crossDomain: true,
			success : function(data, textStatus, jqXHR) {
				//console.log(data);
				loaded_programs = data;
				
				var html = '';
				
				if (!data)
					html += '<li class="empty">Unfortunately there are no programs available at this time...</li>';
				else
					$.each(data, function(label, item) {
						if (!list_programs_with_dbs && item["with_db"])
							html += '';
						else
							html += '<li class="program" url="' + item["zip"] + '">'
									+ '<div class="with_db">' + (item["with_db"] ? '<span class="icon db"></span><span class="title">Include DB data</span>' : '<span class="icon no_db"></span><span class="title">Only static pages</span>') + '</div>'
									+ (item["logo"] ? '<img src="' + item["logo"] + '" />' : '')
									+ '<label>' + label + '</label>'
									+ (item["description"] ? '<div>' + item["description"] + '</div>' : '')
									+ (item["zip"] ? '<button class="choose_program" href="javascript:void(0)" onClick="chooseStoreProgram(\'' + item["zip"] + '\')">Install</button>' : '')
								+ '</li>';
					});
				
				$(".install_store_program > ul").html(html);
			},
			error : function(jqXHR, textStatus, errorThrown) { 
				if (jqXHR.responseText)
					StatusMessageHandler.showError(jqXHR.responseText);
			},
		});
}

function chooseStoreProgram(url) {
	if (url) {
		StatusMessageHandler.showMessage("Download and installing program... Please be patient...");
		
		var upload_url = $('<div class="upload_url"><label>Url:</label><input type="text" name="program_url" value="' + url + '"><span class="icon delete" onClick="removeStoreProgramUrl(this);"></span></div>');
		
		var f = $(".install_program .step_0 .file_upload > form");
		f.find(".upload_url").remove();
		f.append(upload_url);
		f.find(".upload_file").remove();
		
		//install template
		$(".top_bar li.continue > a").trigger("click");
	}
	else
		alert("Error: You cannot choose this program. Please contact the sysadmin.");
}

function removeStoreProgramUrl(elm) {
	var upload_url = $(elm).parent();
	var url = upload_url.children("input").val();
	
	$(".install_program .step_0 .file_upload > form .upload_url input").each(function(idx, input) {
		input = $(input);
		
		if (input.val() == url) {
			var p = input.parent();
			
			if (p.parent().children(".upload_file").length == 0)
				p.after('<input class="upload_file" type="file" name="program_file" />');
			
			p.remove();
		}
	});
}

function searchPrograms(elm) {
	if ($.isPlainObject(loaded_programs)) {
		elm = $(elm);
		var to_search = elm.val().toLowerCase().replace(/^\s*/, "").replace(/\s*$/, "");
		var ul = elm.parent().parent().children("ul");
		var lis = ul.children("li");
		
		if (to_search == "")
			lis.removeClass("hidden");
		else {
			lis.addClass("hidden");
			
			$.each(loaded_programs, function(label, item) {
				var matched = label.toLowerCase().indexOf(to_search) != -1 || ("" + item["description"]).toLowerCase().indexOf(to_search) != -1;
				
				if (matched) {
					$.each(lis, function(label, li) {
						li = $(li);
						
						if (li.attr("url") == item["zip"]) {
							li.removeClass("hidden");
							return false;
						}
					});
				}
			});
		}
	}
}

function resetSearchPrograms(elm) {
	var input = $(elm).parent().children("input");
	input.val("");
	searchPrograms(input[0]);
}
