var loaded_templates = {};
var MyFancyPopupInstallStoreTemplate = new MyFancyPopupClass();
var MyFancyPopupViewTemplate = new MyFancyPopupClass();

$(function () {
	var install_template = $(".install_template");
	install_template.tabs();
	
	if (is_zip_file) {
		var tab_index = install_template.find(" > ul > li > a[href=#local]").parent().index();
		install_template.tabs("option", "active", tab_index);
	}
	
	onChangeLayer( install_template.find(" > .layer > select")[0] );
	onChangeProject( install_template.find(" > .project > select")[0] );
	
	$(window).resize(function() {
		MyFancyPopupInstallStoreTemplate.updatePopup();
		MyFancyPopupViewTemplate.updatePopup();
	});
	
	//if project is already shown, no need to show the view_project icon
	if (install_template.children(".layer").hasClass("unique_layer") && !install_template.children(".project").hasClass("hidden"))
		$(".top_bar > header > ul > .view_project").hide();
	
	initInstallStoreTemplate();
});

function toggleLayerAndProject() {
	var install_template = $(".install_template");
	var layer = install_template.children(".layer");
	var project = install_template.children(".project");
	
	if (!layer.hasClass("unique_layer"))
		layer.toggleClass("hidden");
	
	project.toggleClass("hidden");
}

function onChangeLayer(elm) {
	elm = $(elm);
	var bn = elm.val();
	
	$("form").hide();
	$("#form_" + bn).show();
}

function onChangeProject(elm) {
	elm = $(elm);
	var proj = elm.val();
	var bn = elm.parent().parent().find(" > .layer > select").val();
	
	$("#form_" + bn + " > input[name=project]").val(proj);
}

function initInstallStoreTemplate() {
	if (get_store_templates_url)
		$.ajax({
			type : "get",
			url : get_store_templates_url,
			dataType : "json",
			crossDomain: true,
			success : function(data, textStatus, jqXHR) {
				//console.log(data);
				loaded_templates = data;
				
				var html = '';
				
				if (!data)
					html += '<li class="empty">Unfortunately there are no templates available at this time...</li>';
				else
					$.each(data, function(idx, item) {
						html += '<li class="template" title="' + item["label"] + '" url="' + item["zip"] + '">'
								+ (item["file"] ? '<a class="img_label" href="javascript:void(0)" onClick="viewStoreTemplate(\'' + item["file"] + '\', \'' + item["zip"] + '\')">' : '')
									+ '<div class="photo">' + (item["logo"] ? '<img src="' + item["logo"] + '" />' : '<span class="icon image"></span>') + '</div>'
									+ '<label>' + item["label"] + '</label>'
								+ (item["file"] ? '</a>' : '')
								+ (item["zip"] ? '<button class="choose_template" href="javascript:void(0)" onClick="chooseStoreTemplate(\'' + item["zip"] + '\')"><i class="icon save"></i> Install</button>' : '')
								+ (item["file"] ? '<button class="view_template" href="javascript:void(0)" onClick="viewStoreTemplate(\'' + item["file"] + '\', \'' + item["zip"] + '\')"><i class="icon view"></i> Preview</button>' : '')
							+ '</li>';
					});
				
				$(".install_store_template > ul").html(html);
			},
			error : function(jqXHR, textStatus, errorThrown) { 
				if (jqXHR.responseText)
					StatusMessageHandler.showError(jqXHR.responseText);
			},
		});
}

function viewStoreTemplate(preview_url, zip_url) {
	if (preview_url) {
		var popup = $(".view_store_template_popup");
		
		if (!popup[0]) {
			popup = $('<div class="myfancypopup with_title view_store_template_popup' + (is_popup ? " in_popup" : "") + '"></div>');
			$(document.body).append(popup);
		}
		
		var html = '<div class="title">Template Preview <button class="install_template" onClick="chooseStoreTemplate(\'' + zip_url + '\')">Install this template</button></div>'
				+ '<iframe src="' + preview_url + '"></iframe>';
		popup.html(html);
		
		MyFancyPopupViewTemplate.init({
			elementToShow: popup,
			parentElement: document,
		});
		MyFancyPopupViewTemplate.showPopup();
	}
	else
		alert("Error: You cannot view this template. Please contact the sysadmin.");
}

function chooseStoreTemplate(url) {
	if (url) {
		MyFancyPopupViewTemplate.hidePopup(); //in case it comes from the preview popup
		
		StatusMessageHandler.showMessage("Download and installing template... Please be patient...", "", "", 60000); //1 minute. This message will disappear on submit...
		
		var upload_url = $('<div class="upload_url"><label>Url:</label><input type="text" name="zip_url" value="' + url + '"><span class="icon delete" onClick="removeStoreTemplateUrl(this);"></span></div>');
		
		var f = $(".install_template > .file_upload > form");
		f.find(".upload_url").remove();
		f.append(upload_url);
		f.find(".upload_file").remove();
		
		MyFancyPopupInstallStoreTemplate.hidePopup();
		
		//install template
		$(".top_bar li.continue > a").trigger("click");
	}
	else
		alert("Error: You cannot choose this template. Please contact the sysadmin.");
}

function removeStoreTemplateUrl(elm) {
	var upload_url = $(elm).parent();
	var url = upload_url.children("input").val();
	
	$(".install_template > .file_upload > form .upload_url input").each(function(idx, input) {
		input = $(input);
		
		if (input.val() == url) {
			var p = input.parent();
			
			if (p.parent().children(".upload_file").length == 0)
				p.after('<input class="upload_file" type="file" name="zip_file">');
			
			p.remove();
		}
	});
}

function searchTemplates(elm) {
	if ($.isPlainObject(loaded_templates) || $.isArray(loaded_templates)) {
		elm = $(elm);
		var to_search = elm.val().toLowerCase().replace(/^\s*/, "").replace(/\s*$/, "");
		var ul = elm.parent().parent().children("ul");
		var lis = ul.children("li");
		
		if (to_search == "")
			lis.removeClass("hidden");
		else {
			lis.addClass("hidden");
			
			$.each(loaded_templates, function(idx, item) {
				var matched = ("" + item["label"]).toLowerCase().indexOf(to_search) != -1;
				
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

function resetSearchTemplates(elm) {
	var input = $(elm).parent().children("input");
	input.val("");
	searchTemplates(input[0]);
}

function installTemplate(elm) {
	var install_template = $(".install_template");
	var layer_bn = install_template.find(" > .layer select").val();
	var oForm = install_template.find(" > .file_upload > #form_" + layer_bn);
	
	if (oForm[0]) {
		var zip_url = oForm.find(".upload_url input");
		var zip_file = oForm.find("input.upload_file");
		var status = (zip_url[0] && zip_url.val() != "") || (zip_file[0] && zip_file[0].files.length > 0);
		
		if (status) {
			elm = $(elm);
			var on_click = elm.attr("onClick");
			elm.addClass("loading").removeAttr("onClick");
			
			oForm.submit();
			
			/*setTimeout(function() {
				elm.removeClass("loading").attr("onClick", on_click);
			}, 2000);*/
		}
		else {
			if (install_template.tabs("option", "active") == 0)
				StatusMessageHandler.showError("Please click in one of the available templates to install!");
			else
				StatusMessageHandler.showError("You must upload a template first!");
		}
	}
	else
		StatusMessageHandler.showError("form object undefined! Please contact the sysadmin...");
}
