/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

var MyFancyPopupInstallStoreModule = new MyFancyPopupClass();
var MyFancyPopupViewModule = new MyFancyPopupClass();
var loaded_modules = {};

$(function () {
	var install_module = $(".install_module");
	install_module.tabs();
	
	if (is_zip_file) {
		var tab_index = install_module.find(" > ul > li > a[href=#local]").parent().index();
		install_module.tabs("option", "active", tab_index);
	}
	
	onChangeProject( install_module.find(" > .project > select")[0] );
	onChangeDBDriver( install_module.find(" > .db_driver > select")[0] );
	
	$(window).resize(function() {
		MyFancyPopupInstallStoreModule.updatePopup();
		MyFancyPopupViewModule.updatePopup();
	});
	
	initInstallStoreModule();
});

function onChangeProject(elm) {
	$(".install_module form input[name=project]").val( $(elm).val() );
}

function onChangeDBDriver(elm) {
	$(".install_module form input[name=db_driver]").val( $(elm).val() );
}

function addNewFile(elm) {
	var html = '<div class="upload_file"><input type="file" name="zip_file[]" multiple> <span class="icon delete" onClick="$(this).parent().remove()"></span></div>';
	var upload_files = $(elm).parent().closest("form").children(".upload_file, .upload_url");
	
	if (upload_files.filter(".upload_file").length < max_file_uploads)
		upload_files.last().after(html);
	else
		alert("Maximum number of allowable file uploads has been reached!");
}

function onSubmitButtonClick(elm) {
	elm = $(elm);
	
	if (checkUploadedFiles()) {
		var on_click = elm.attr("onClick");
		elm.addClass("loading").removeAttr("onClick");
		
		var oForm = elm.parent().closest(".top_bar").parent().find(".file_upload form");
		oForm.submit();
		
		/*setTimeout(function() {
			elm.removeClass("loading").attr("onClick", on_click);
		}, 2000);*/
	}
}

function checkUploadedFiles() {
	var inputs = $(".file_upload").find(".upload_file input[type=file], .upload_url input");
	var count = 0;
	var count_files = 0;
	
	$.each(inputs, function (idx, input) {
		if (input.type == "file" && input.files) {
			count += input.files.length;
			count_files += input.files.length;
		}
		else if (input.value != "")
			count++;
	});
	
	if (count_files > max_file_uploads) {
		alert("You can only upload " + max_file_uploads + " zip files maximum each time!\nPlease remove some files before you proceed...");
		return false;
	}
	else if (!count) {
		alert("You must select at least 1 zip file to proceed!");
		return false;
	}
	
	//remove input files with empty values
	$.each(inputs, function (idx, input) {
		if (idx > 0) {
			if (input.type == "file" && (!input.files || !input.files.length))
				$(input).parent().closest(".upload_file").remove();
			else if (input.value == "")
				$(input).parent().closest(".upload_url").remove();
		}
	});
	
	return true;
}

function initInstallStoreModule() {
	if (get_store_modules_url)
		$.ajax({
			type : "get",
			url : get_store_modules_url,
			dataType : "json",
			crossDomain: true,
			success : function(data, textStatus, jqXHR) {
				//console.log(data);
				loaded_modules = data;
				
				var html = '';
				
				if (!data)
					html += '<li class="empty">Unfortunately there are no modules available at this time...</li>';
				else
					$.each(data, function(label, item) {
						html += '<li class="module' + (item["modules"] ? ' with_view_module' : '') + '" url="' + item["zip"] + '" ' + (item["modules"] ? 'onClick="viewStoreModule(\'' + label + '\')"' : '') + '>'
								+ (item["logo"] ? '<img src="' + item["logo"] + '" />' : '<div class="photo"><span class="icon image"></span></div>')
								+ '<label>' + label + '</label>'
								+ (item["description"] ? '<div>' + item["description"] + '</div>' : '')
								+ (item["modules"] ? '<button class="view_module" href="javascript:void(0)">View</button>' : '')
								+ (item["zip"] ? '<button class="choose_module' + (item["modules"] ? ' with_view_module' : '') + '" href="javascript:void(0)" onClick="chooseStoreModule(this, \'' + item["zip"] + '\')">Select to install</button>' : '')
							+ '</li>';
					});
				
				$(".install_store_module > ul").html(html);
			},
			error : function(jqXHR, textStatus, errorThrown) { 
				if (jqXHR.responseText)
					StatusMessageHandler.showError(jqXHR.responseText);
			},
		});
}

function viewStoreModule(module_label) {
	if (module_label) {
		var popup = $(".view_store_module_popup");
		
		if (!popup[0]) {
			popup = $('<div class="myfancypopup view_store_module_popup with_title"></div>');
			$(document.body).append(popup);
		}
		
		var sub_modules = $.isPlainObject(loaded_modules) && $.isPlainObject(loaded_modules[module_label]) ? loaded_modules[module_label]["modules"] : null;
		var html = '<div class="title">Sub modules of "' + module_label + '"</div><ul>';
		
		if (sub_modules && $.isPlainObject(sub_modules) && !$.isEmptyObject(sub_modules)) {
			$.each(sub_modules, function(label, item) {
				html += '<li class="sub_module">'
						+ (item["logo"] ? '<img src="' + item["logo"] + '" />' : '<div class="photo"><span class="icon image"></span></div>')
						+ '<label>' + label + '</label>'
						+ (item["description"] ? '<div>' + item["description"] + '</div>' : '')
					+ '</li>';
			});
		}
		else
			html += '<li class="no_sub_modules">No sub modules available</li>';
		
		html += '</ul>';
		popup.html(html);
		
		MyFancyPopupViewModule.init({
			elementToShow: popup,
			parentElement: document,
		});
		MyFancyPopupViewModule.showPopup();
	}
	else
		alert("Error: You cannot view this module. Please contact the sysadmin.");
}

function chooseStoreModule(elm, url) {
	window.event.stopPropagation(); //prevent the event to fire in the parent "li" html element.
	
	if (url) {
		elm = $(elm);
		var module = elm.parent().closest(".module");
		module.toggleClass("selected");
		
		if (module.hasClass("selected")) {
			elm.html("Unselect");
			
			var upload_url = $('<div class="upload_url"><label>Url:</label><input type="text" name="zip_url[]" value="' + url + '"><span class="icon delete" onClick="removeStoreModuleUrl(this);"></span></div>');
			
			var upload_file = $(".file_upload").find(".upload_file, .upload_url").last();
			upload_file.after(upload_url);
		}
		else {
			elm.html("Select to install");
			
			$(".file_upload .upload_url input").each(function(idx, input) {
				input = $(input);
				
				if (input.val() == url)
					input.parent().remove();
			});
		}
		
		MyFancyPopupInstallStoreModule.hidePopup();
	}
	else
		alert("Error: You cannot choose this module. Please contact the sysadmin.");
}

function removeStoreModuleUrl(elm) {
	var upload_url = $(elm).parent();
	var url = upload_url.children("input").val();
	
	upload_url.remove();
	
	$(".install_store_module li.module").each(function(idx, li) {
		li = $(li);
		
		if (li.attr("url") == url) {
			li.removeClass("selected");
			li.find(".choose_module").html("Select to install");
			
			return false;
		}
	});
}

function searchModules(elm) {
	if ($.isPlainObject(loaded_modules)) {
		elm = $(elm);
		var to_search = elm.val().toLowerCase().replace(/^\s*/, "").replace(/\s*$/, "");
		var ul = elm.parent().parent().children("ul");
		var lis = ul.children("li");
		
		if (to_search == "")
			lis.removeClass("hidden");
		else {
			lis.addClass("hidden");
			
			$.each(loaded_modules, function(label, item) {
				var matched = label.toLowerCase().indexOf(to_search) != -1 || ("" + item["description"]).toLowerCase().indexOf(to_search) != -1;
				
				if (!matched && $.isPlainObject(item["modules"]))
					$.each(item["modules"], function(sub_label, sub_item) {
						if (sub_label.toLowerCase().indexOf(to_search) != -1) {
							matched = true;
							return false;
						}
						else if (("" + sub_item["description"]).toLowerCase().indexOf(to_search) != -1) {
							matched = true;
							return false;
						}
					});
				
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

function resetSearchModules(elm) {
	var input = $(elm).parent().children("input");
	input.val("");
	searchModules(input[0]);
}
