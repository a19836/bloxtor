/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

var MyFancyPopupAvailableTemplate = new MyFancyPopupClass();
var MyFancyPopupInstallTemplate = new MyFancyPopupClass();
var MyFancyPopupAvailableTemplateDemo = new MyFancyPopupClass();
var MyFancyPopupEditProjectGlobalVariables = new MyFancyPopupClass();
var default_available_templates = ["empty", "ajax", "blank"];

function chooseAvailableTemplate(select, options) {
	options = options ? options : {};
	
	var popup = $(".choose_available_template_popup");
	var on_open_func = null;
	var project_id = options.selected_project_id ? options.selected_project_id : selected_project_id;
	var folder_to_filter = options.folder_to_filter ? options.folder_to_filter : "";
	
	if (!popup[0]) {
		var html = '<div class="myfancypopup with_title choose_available_template_popup">'
					+ '<label class="title">Choose a Theme Template</label>'
					+ '<div class="content' + (options["hide_template_options"] ? " without_hide_template_options" : "") + '"></div>'
				+ '</div>';
		
		popup = $(html);
		$(document.body).append(popup);
		
		on_open_func = options["show_templates_only"] ? prepareChooseAvailableTemplateMainProjectHtml : prepareChooseAvailableTemplateTypeHtml;
	}
	
	if (!MyFancyPopupAvailableTemplate.settings) //only init if not inited before
		MyFancyPopupAvailableTemplate.init({
			//options vars
			include_template_samples: options["include_template_samples"] ? options["include_template_samples"] : null,
			include_template_samples_in_regions: options["include_template_samples_in_regions"] == "all" ? "all" : "empty",
			onSelect: options["on_select"] ? options["on_select"] : null,
			onSelectFromOtherProject: options["on_select_from_other_project"] ? options["on_select_from_other_project"] : null,
			available_projects_props: options["available_projects_props"] ? options["available_projects_props"] : null,
			available_projects_templates_props: options["available_projects_templates_props"] ? options["available_projects_templates_props"] : null,
			get_available_templates_props_url: options["get_available_templates_props_url"] ? options["get_available_templates_props_url"] : null,
			install_template_url: options["install_template_url"] ? options["install_template_url"] : null,
			project_global_variables_url: options["project_global_variables_url"] ? options["project_global_variables_url"] : null,
			onInstall: options["on_install"] ? options["on_install"] : null,
			hide_choose_different_editor: options["hide_choose_different_editor"] ? true : false,
			hide_choose_different_project: options["hide_choose_different_project"] ? true : false,
			hide_template_options: options["hide_template_options"] ? true : false,
			hide_chosen_project_template: options["hide_chosen_project_template"] ? true : false,
			chosen_template: options["chosen_template"] ? options["chosen_template"] : null,
			chosen_project: options["chosen_project"] ? options["chosen_project"] : null,
			
			//internal vars
			elementToShow: popup,
			parentElement: document,
			onOpen: function() {
				if (typeof on_open_func == "function")
					on_open_func(project_id, folder_to_filter); //only execute once - the first time.
			},
			targetField: select,
			default_project_id: project_id,
		});
	else { //simply open the popup where the user left, instead of loading the on_open_func handler. 
		MyFancyPopupAvailableTemplate.settings.chosen_template = options["chosen_template"] ? options["chosen_template"] : null;
		MyFancyPopupAvailableTemplate.settings.chosen_project = options["chosen_project"] ? options["chosen_project"] : null;
		MyFancyPopupAvailableTemplate.settings.onOpen = function() {
			//update chosen template
			prepareChooseAvailableTemplateChosenTemplateHtml(project_id);
		};
	}
	
	MyFancyPopupAvailableTemplate.showPopup();
}

function installTemplatePopup(project_id, folder_to_filter) {
	var url = MyFancyPopupAvailableTemplate.settings.install_template_url;
	
	if (url) {
		url += (url.indexOf("?") != -1 ? "&" : "?") + "popup=1&on_success_js_func=MyFancyPopupInstallTemplate.hidePopup";
		
		var popup = $(".install_template_popup");
		
		if (!popup[0]) {
			popup = $('<div class="myfancypopup with_iframe_title install_template_popup"></div>');
			$(document.body).append(popup);
		}
		
		popup.html('<iframe src="' + url + '"></iframe>');
		
		if (!MyFancyPopupInstallTemplate.settings) //only init if not inited before
			MyFancyPopupInstallTemplate.init({
				elementToShow: popup,
				parentElement: document,
				onClose: function() {
					//reset templates
					MyFancyPopupAvailableTemplate.settings.available_projects_templates_props[project_id] = null;
					
					//reload templates
					loadAvailableProjectTemplatesHtml(project_id, function(proj_id, server_data_fetched) {
						prepareChooseAvailableTemplateInstalledHtml(project_id, folder_to_filter);
						
						if (server_data_fetched && typeof MyFancyPopupAvailableTemplate.settings.onInstall == "function")
							MyFancyPopupAvailableTemplate.settings.onInstall(project_id, MyFancyPopupAvailableTemplate.settings.available_projects_templates_props[project_id]);
					});
				},
			});
	
		MyFancyPopupInstallTemplate.showPopup();
	}
	else
		alert("install_template_url is undefined. Please contact the sysadmin!");
}

function prepareChooseAvailableTemplateTypeHtml(project_id, folder_to_filter) {
	var popup = $(".choose_available_template_popup");
	var popup_content = popup.children(".content");
	
	popup.removeClass("with_options");
	popup.children(".back, .choose_different_editor, .install_template").remove();
	
	var html = '<div class="choose_page_workspace">'
				+ '<div class="title">How do you want to design your page?</div>'
				+ '<div class="editor_type html_editor" project_id="' + project_id + '" template_id="blank">'
					+ '<div class="title">For web-designers</div>'
					+ '<div class="description">Design your page from scratch by designing your HTML with drag and drop, including the head and body tags.</div>'
					+ '<button onClick="selectAvailableTemplate(\'' + project_id + '\', \'blank\')">Empty Canvas</button>'
				+ '</div>'
				+ '<div class="editor_type template_editor">'
					+ '<div class="title">For all developers</div>'
					+ '<div class="description">Accelerate your design process by starting with a ready-made, customizable theme template.</div>'
					+ '<button onClick="prepareChooseAvailableTemplateMainProjectHtml(\'' + project_id + '\', \'' + folder_to_filter + '\')">Browse Themes</button>'
				+ '</div>'
				+ '<div class="editor_type ajax_template advanced_template" project_id="' + project_id + '" template_id="ajax">'
					+ '<div class="title">For apis</div>'
					+ '<div class="description">Print raw data in any format you wish to be used by external apis...</div>'
					+ '<button onClick="selectAvailableTemplate(\'' + project_id + '\', \'ajax\')">Ajax Template</button>'
				+ '</div>'
				+ '<div class="editor_type empty_template advanced_template" project_id="' + project_id + '" template_id="empty">'
					+ '<div class="title">For non-standards pages</div>'
					+ '<div class="description">Create and print html directly from your page without any template...<br/>To be used in reports.</div>'
					+ '<button onClick="selectAvailableTemplate(\'' + project_id + '\', \'empty\')">No Template</button>'
				+ '</div>';
	
	if (typeof layer_default_template != "undefined" && layer_default_template)
		html += 	'<div class="editor_type project_default_template advanced_template" project_id="' + project_id + '" template_id="">'
					+ '<div class="title">Based in the project\'s global settings' + (MyFancyPopupAvailableTemplate.settings.project_global_variables_url ? ' <span class="icon edit" title="Edit Project Global Settings" onClick="editProjectGlobalVariables()">Edit</span>' : '') + '</div>'
					+ '<div class="description">Create a page based on the project\'s default template, defined in the project\'s global settings. This means that when you change the default project template, this page will also change.</div>'
					+ '<button onClick="selectAvailableTemplate(\'' + project_id + '\', \'\')">Project Default Template</button>'
				+ '</div>';
	
	html += 		'<a class="toggle_advanced_templates" onClick="toggleChooseAvailableTemplateTypeHtmlMode(this)">Show Advanced Mode</a>'
			+ '</div>';
	
	popup_content.html(html).scrollTop(0);
	popup.children(".loading_templates").hide();
	
	//load chosen template
	prepareChooseAvailableTemplateChosenTemplateHtml(project_id);
	
	MyFancyPopupAvailableTemplate.updatePopup();
}

function toggleChooseAvailableTemplateTypeHtmlMode(elm) {
	elm = $(elm);
	var p = elm.parent();
	
	p.toggleClass("with_advanced_templates");
	
	if (p.hasClass("with_advanced_templates"))
		elm.html("Hide Advanced Mode");
	else
		elm.html("Show Advanced Mode");
}

function prepareChooseAvailableTemplateMainProjectHtml(project_id, folder_to_filter) {
	var popup = $(".choose_available_template_popup");
	var popup_content = popup.children(".content");
	var include_template_samples = MyFancyPopupAvailableTemplate.settings.include_template_samples;
	var include_template_samples_in_regions = MyFancyPopupAvailableTemplate.settings.include_template_samples_in_regions;
	
	if (!MyFancyPopupAvailableTemplate.settings.hide_choose_different_editor && !popup.children(".choose_different_editor")[0])
		popup_content.before('<button class="choose_different_editor" onClick="prepareChooseAvailableTemplateTypeHtml(\'' + project_id + '\', \'' + folder_to_filter + '\');"><i class="icon go_back"></i> Back</button>');
	
	if (MyFancyPopupAvailableTemplate.settings.install_template_url && !popup.children(".install_template")[0])
		popup_content.before('<button class="install_template" onClick="installTemplatePopup(\'' + project_id + '\', \'' + folder_to_filter + '\')">Import New Theme</button>');
	
	var html = '';
	
	if (!MyFancyPopupAvailableTemplate.settings.hide_chosen_project_template)
		html += '<div class="chosen_project_template"></div>';
	
	html +=	'<ul class="' + (MyFancyPopupAvailableTemplate.settings.hide_chosen_project_template ? "without_chosen_project_template" : "") + '">'
				+ '<li><a href="#installed_templates">Current Project Installed Templates</a></li>'
				+ (!MyFancyPopupAvailableTemplate.settings.hide_choose_different_project ? '<li><a href="#projects_templates">Other Projects\' Templates</a></li>' : '')
			+ '</ul>'
			+ '<div id="installed_templates" class="installed_templates">'
				+ '<div class="current_template_folder"></div>'
				+ '<ul class="template_files">'
					+ '<li class="loading_templates"><span class="icon loading"></span> Loading theme templates...</li>'
				+ '</ul>'
			+ '</div>';
	
	if (!MyFancyPopupAvailableTemplate.settings.hide_choose_different_project)
		html += '<div id="projects_templates" class="projects_templates">'
				+ '<div class="current_template_folder"></div>'
				+ '<ul class="template_files">'
					+ '<li class="loading_templates"><span class="icon loading"></span> Loading projects...</li>'
				+ '</ul>'
			+ '</div>';
	
	popup_content.html(html).scrollTop(0);
	
	//init tabs
	if (popup_content.tabs("instance") != undefined)
		popup_content.tabs("destroy");
	
	popup_content.tabs();
	
	//add options
	if (!MyFancyPopupAvailableTemplate.settings.hide_template_options) {
		popup.addClass("with_options");
		
		if (popup.children(".options").length == 0)
			popup_content.after('<div class="options">'
					+ '<div class="include_template_samples">'
						+ '<input type="checkbox" ' + (include_template_samples ? " checked" : "") + ' onChange="onChangeIncludeTemplateSamples(this)"/>'
						+ '<label>Include samples from selected template?</label>'
						+ '<select onChange="onChangeIncludeTemplateSamplesInRegions(this)">'
							+ '<option value="empty"' + (include_template_samples_in_regions == "empty" ? " selected" : "") + '>Only on empty regions</option>'
							+ '<option value="all"' + (include_template_samples_in_regions == "all" ? " selected" : "") + '>In all regions</option>'
						+ '</select>'
						+ '<span class="info" title="Each template comes with samples for each region. If this option is active, the system will add these code to the correspondent regions of the selected template."><i class="icon info"></i></span>'
					+ '</div>'
				+ '</div>');
	}
	else
		popup.removeClass("with_options");
	
	//load templates
	prepareChooseAvailableTemplateInstalledHtml(project_id, folder_to_filter);
	
	//load chosen template
	prepareChooseAvailableTemplateChosenTemplateHtml(project_id);
	
	if (!MyFancyPopupAvailableTemplate.settings.hide_choose_different_project)
		prepareChooseAvailableTemplateProjectsHtml();
}

function prepareChooseAvailableTemplateChosenTemplateHtml(project_id) {
	var popup = $(".choose_available_template_popup");
	var popup_content = popup.children(".content");
	var chosen_project_template = popup_content.children(".chosen_project_template");
	var is_external_project = MyFancyPopupAvailableTemplate.settings.chosen_project && MyFancyPopupAvailableTemplate.settings.chosen_project != project_id;
	var chosen_project = MyFancyPopupAvailableTemplate.settings.chosen_project ? MyFancyPopupAvailableTemplate.settings.chosen_project : project_id;
	var chosen_template = MyFancyPopupAvailableTemplate.settings.chosen_template;
	
	if (!is_external_project && !chosen_template && typeof layer_default_template != "undefined" && layer_default_template)
		chosen_template = layer_default_template;
	
	if (chosen_project_template[0]) {
		var parts = chosen_template.split("/");
		var chosen_template_file = parts[ parts.length - 1 ];
		parts.pop(); //remove last part which is the template file name
		var chosen_template_folder = parts.join("/");
		
		//prepare html
		var html = '';
		
		if (chosen_template_file) {
			if (is_external_project) {
				html = '<span class="path_parts" onClick="prepareChooseAvailableTemplateProjectsHtml(\'\')" title="Open projects root folder">projects</span> ' + getChooseAvailableTemplateCurrentProjectHtml(chosen_project, true, true);
				html += getChooseAvailableTemplateCurrentFolderHtml(chosen_project, chosen_template_folder);
				html += '<span class="path_parts" onClick="selectAvailableTemplate(\'' + chosen_project + '\', \'' + chosen_template + '\')" title="Select this template again">' + chosen_template_file + '</span>';
			}
			else {
				html = '<span class="path_parts" onClick="prepareChooseAvailableTemplatesHtml(\'' + project_id + '\', \'\')" title="Open templates root folder">templates</span>';
				html += getChooseAvailableTemplateCurrentFolderHtml(project_id, chosen_template_folder);
				html += '<span class="path_parts" onClick="selectAvailableTemplate(\'' + project_id + '\', \'' + chosen_template + '\')" title="Select this template again">' + chosen_template_file + '</span>';
			}
		}
		
		chosen_project_template.children(".path_parts").remove();
		chosen_project_template.html(html);
		
		//when click in path select the correct tab
		chosen_project_template.children(".path_parts:not(:last-child)").click(function() {
			if (is_external_project)
				popup_content.tabs("option", "active", 1);
			else
				popup_content.tabs("option", "active", 0);
		});
	}
	
	//set chosen template class
	popup_content.find(".choose_page_workspace > .editor_type, .template_files > li.file").each(function(idx, li) {
		li = $(li);
		var template_id = li.attr("template_id");
		
		//prepare chosen_template
		if (chosen_template && li.attr("project_id") == chosen_project && (
			template_id == chosen_template || 
			(!MyFancyPopupAvailableTemplate.settings.chosen_template && !template_id) //must be MyFancyPopupAvailableTemplate.settings.chosen_template, not chosen_template bc of the .editor_type.project_default_template
		))
			li.addClass("chosen_template");
		else
			li.removeClass("chosen_template");
		
		//prepare chosen_project_default_template
		if (li.attr("project_id") == chosen_project && !MyFancyPopupAvailableTemplate.settings.chosen_template && typeof layer_default_template != "undefined" && layer_default_template == template_id)
			li.addClass("chosen_project_default_template");
		else
			li.removeClass("chosen_project_default_template");
	});
}

function prepareChooseAvailableTemplateInstalledHtml(project_id, folder_to_filter) {
	prepareChooseAvailableTemplatesHtml(project_id, folder_to_filter);
}

function prepareChooseAvailableTemplateProjectsHtml(folder_to_filter) {
	var popup = $(".choose_available_template_popup");
	var ul = popup.find(" > .content > .projects_templates > ul");
	ul.parent().children(".back").remove();
	
	var html = '';
	
	if (MyFancyPopupAvailableTemplate.settings.available_projects_props) {
		var available_projects_props = assignObjectRecursively({}, MyFancyPopupAvailableTemplate.settings.available_projects_props);
		delete available_projects_props[ MyFancyPopupAvailableTemplate.settings.default_project_id ]; //remove current project
		//console.log(available_projects_props);
		
		var aps = getAvailableFilesPropsConvertedWithFolders(available_projects_props, folder_to_filter, false);
		//console.log(aps);
		
		if (folder_to_filter) {
			folder_to_filter = folder_to_filter.replace(/[\/]+/, "/").replace(/[\/]+$/, "");
			var dirs = folder_to_filter.split("/");
			dirs.pop();
			var parent_folder = dirs.join("/");
			
			ul.before('<div class="back" onClick="prepareChooseAvailableTemplateProjectsHtml(\'' + parent_folder + '\');"><i class="icon go_up"></i> Go to parent folder</div>');
		}
		
		if (!$.isEmptyObject(aps)) {
			//add files 
			for (var k in aps) 
				if (aps[k])
					html += getChooseAvailableTemplateProjectHtml(folder_to_filter, k, aps[k]);
		}
	}
	
	if (html == "")
		html = '<li class="empty">There are no available projects...</li>';
	
	ul.html(html);
	
	var info = folder_to_filter ? '<span class="path_parts" onClick="prepareChooseAvailableTemplateProjectsHtml(\'\')" title="Open projects root folder">projects</span> ' + getChooseAvailableTemplateCurrentProjectHtml(folder_to_filter, false, false) : '';
	ul.parent().children(".current_template_folder").html(info);
	
	MyFancyPopupAvailableTemplate.updatePopup();
}

function prepareChooseAvailableTemplatesHtml(project_id, folder_to_filter) {
	var aptp = MyFancyPopupAvailableTemplate.settings.available_projects_templates_props;
	var items = $.isPlainObject(aptp) && $.isPlainObject(aptp[project_id]) ? aptp[project_id] : {};
	var is_external_project = project_id && project_id != MyFancyPopupAvailableTemplate.settings.default_project_id;
	var ats = getAvailableFilesPropsConvertedWithFolders(items, folder_to_filter, true);
	
	var popup = $(".choose_available_template_popup");
	var ul = popup.find(" > .content > " + (is_external_project ? ".projects_templates" : ".installed_templates") + " > ul");
	ul.parent().children(".back").remove();
	
	if (folder_to_filter) {
		folder_to_filter = folder_to_filter.replace(/[\/]+/, "/").replace(/[\/]+$/, "");
		var dirs = folder_to_filter.split("/");
		dirs.pop();
		var parent_folder = dirs.join("/");
		
		ul.before('<div class="back" onClick="prepareChooseAvailableTemplatesHtml(\'' + project_id + '\', \'' + parent_folder + '\');"><i class="icon go_up"></i> Go to parent folder</div>');
	}
	else if (is_external_project)
		ul.before('<div class="back back_to_type" onClick="prepareChooseAvailableTemplateProjectsHtml(\'\');"><i class="icon go_up"></i> Go back to projects</div>');
	
	var is_selected_project_root_folder = !folder_to_filter && !is_external_project;
	var html = '';
	
	if (!$.isEmptyObject(ats)) {
		//remove default templates
		var exlude_default_templates = !folder_to_filter && !is_external_project;
		
		//add folders
		/*var folders_exists = false;
		
		for (var k in ats) 
			if (ats[k]) { 
				var is_file = $.isPlainObject(ats[k]) && ats[k]["is_file"] === true;
				
				if (!is_file) {
					html += getChooseAvailableTemplateHtml(project_id, folder_to_filter, k, ats[k]);
					folders_exists = true;
					ats[k] = null;
				}
			}
		
		if (folders_exists)
			html += '<li class="separator"></li>';
		*/
		//add files 
		for (var k in ats) 
			if (ats[k] && (!is_selected_project_root_folder || !ats[k]["is_default_template"]))
				html += getChooseAvailableTemplateHtml(project_id, folder_to_filter, k, ats[k]);
	}
	
	if (html == "") {
		html += '<li class="empty">';
		
		//is root of the selected project and if install_template_url exists 
		if (is_selected_project_root_folder && MyFancyPopupAvailableTemplate.settings.install_template_url )
			html += '<div class="title">There are no templates installed yet.</div>'
				+ '<div class="description">Accelerate your design process by importing a customizable theme template from the online store.</div>'
				+ '<button onClick="installTemplatePopup(\'' + project_id + '\', \'' + folder_to_filter + '\')">Import New Theme</button>';
		else
			html += 'There are no available templates...';
		
		//if (MyFancyPopupAvailableTemplate.settings.install_template_url && !is_external_project)
		//	html += '<br/>Please install new templates by clicking <a href="javascript:void(0)" onClick="installTemplatePopup(\'' + project_id + '\', \'' + folder_to_filter + '\')">here</a>.';
		
		html += '</li>';
	}
	
	ul.html(html);
	ul.scrollTop(0);
	
	var info = '';
	info += is_external_project ? '<span class="path_parts" onClick="prepareChooseAvailableTemplateProjectsHtml(\'\')" title="Open projects root folder">projects</span> ' + getChooseAvailableTemplateCurrentProjectHtml(project_id, true, folder_to_filter) : "";
	info += folder_to_filter ? (
			!is_external_project ? '<span class="path_parts" onClick="prepareChooseAvailableTemplatesHtml(\'' + project_id + '\', \'\')" title="Open templates root folder">templates</span> ' : ''
		) + getChooseAvailableTemplateCurrentFolderHtml(project_id, folder_to_filter) : '';
	ul.parent().children(".current_template_folder").html(info);
	
	MyFancyPopupAvailableTemplate.updatePopup();
}

function getChooseAvailableTemplateHtml(project_id, folder_to_filter, fp, template_props) {
	var html = "";
	template_props = $.isPlainObject(template_props) ? template_props : {};
	
	var is_file = template_props["is_file"] === true;
	fp = is_file ? fp.substr(0, fp.length - 4) : fp;//remove extension if is file
	var template_id = (folder_to_filter ? folder_to_filter + "/" : "") + fp;
	var label = fp.replace("/_/g", " ");
	label = label.charAt(0).toUpperCase() + label.substr(1, label.length - 1);
	var is_external_project = project_id && project_id != MyFancyPopupAvailableTemplate.settings.default_project_id;
	var is_default_template = !is_external_project ? template_props["is_default_template"] : false;
	var is_project_default_template = !is_external_project && typeof layer_default_template != "undefined" && layer_default_template == template_id;
	var demo = is_file ? template_props["demo"] : null;
	var logo = is_file ? template_props["logo"] : null;
	
	var is_chosen_template = (!MyFancyPopupAvailableTemplate.settings.chosen_project || MyFancyPopupAvailableTemplate.settings.chosen_project == project_id) && MyFancyPopupAvailableTemplate.settings.chosen_template == template_id; //Note that the chosen_template can be empty string or null in case is the same than the layer_default_template
	var is_chosen_project_default_template = (!MyFancyPopupAvailableTemplate.settings.chosen_project || MyFancyPopupAvailableTemplate.settings.chosen_project == project_id) && !MyFancyPopupAvailableTemplate.settings.chosen_template && typeof layer_default_template != "undefined" && layer_default_template == template_id;
	
	if (is_file)
		html += '<li class="file' + (demo ? " with_demo" : "") + (is_default_template ? " default_template" : "") + (is_project_default_template ? " project_default_template" : "") + (is_chosen_project_default_template ? " chosen_project_default_template" : "") + (is_chosen_template ? " chosen_template" : "") + '" title="' + label + '" project_id="' + project_id + '" template_id="' + template_id + '" ' + (demo ? ' onClick="showAvailableTemplateDemo(\'' + project_id + '\', \'' + template_id + '\', \'' + demo + '\')"' : '') + '>';
	else
		html += '<li class="folder" onClick="prepareChooseAvailableTemplatesHtml(\'' + project_id + '\', \'' + template_id + '\');" title="' + label + '">';
	
	if (is_file) {
		if (logo)
			html += '<div class="image"><img src="' + logo + '" onError="$(this).parent().parent().children(\'.photo\').removeClass(\'hidden\'); $(this).parent().remove();" /></div>';
		
		html += '<div class="photo' + (default_available_templates.indexOf(fp) != -1 ? "_" + fp : "") + (logo ? " hidden" : "") + '"></div>';
		html += '<label>' + label + '</label>';
		
		if (demo)
			html += '<button class="show_demo" title="View Demo"><span class="icon view"></span>Preview</button>';
		
		html += '<button class="select_template" onClick="selectAvailableTemplate(\'' + project_id + '\', \'' + template_id + '\')" title="Select Template"><span class="icon download"></span>Select</button>';
	}
	else { //if is folder
		var logos = getFolderFilesAvailableLogos(template_props);
		var t = logos.length >= 3 ? 4 : (logos.length == 2 ? 2 : 1);
		
		html += '<div class="image ' + (logos.length >= 3 ? "image_4" : (logos.length == 2 ? "image_2" : (logos.length == 0 ? "image_0" : ""))) + '">';
		
		for (var i = 0; i < t; i++) {
			if (i < logos.length)
				html += '<img src="' + logos[i] + '" onError="$(this).parent().children(\'.photo_' + i + '\').removeClass(\'hidden\'); $(this).remove();" /><div class="photo hidden photo_' + i + '"></div>';
			else
				html += '<div class="photo photo_' + i + '"></div>';
		}
		
		html += '</div>';
		
		html += '<label>' + label + '</label>';
		html += '<div class="open_folder" title="Open Folder"><span class="icon door"></span>Open</div>';
	}
	
	html += '</li>';
	
	return html;
}

function getChooseAvailableTemplateProjectHtml(folder_to_filter, fp, project_props) {
	var html = "";
	
	var is_project = $.isPlainObject(project_props) && project_props["is_file"] === true;
	var project_id = (folder_to_filter ? folder_to_filter + "/" : "") + fp;
	var project_logo_url = $.isPlainObject(project_props) && project_props["logo"] ? project_props["logo"] : null;
	var label = fp.replace("/_/g", " ");
	label = label.charAt(0).toUpperCase() + label.substr(1, label.length - 1);
	
	if (is_project)
		html += '<li class="project ' + (!folder_to_filter && project_id == "common" ? "project_common" : "") + '" onClick="loadAvailableProjectTemplatesHtml(\'' + project_id + '\');" title="' + label + '">'
	else
		html += '<li class="folder project_folder" onClick="prepareChooseAvailableTemplateProjectsHtml(\'' + project_id + '\');" title="' + label + '">'
	
	if (project_logo_url)
		html += '<div class="image">' + (project_logo_url ? '<img src="' + project_logo_url + '" onError="$(this).parent().removeClass("image").addClass("photo");$(this).remove();" />' : '') + '</div>';
	else
		html += '<div class="photo"></div>';
	
	html += '<label>' + label + '</label>';
	html += '<div class="open_folder" title="Open Folder"><span class="icon door"></span>Open</div>';
	html += '</li>';
	
	return html;
}

function getChooseAvailableTemplateCurrentFolderHtml(project_id, current_path) {
	current_path = current_path.replace(/^\/+/g, "").replace(/\/+$/g, "");
	var dirs = current_path.split("/");
	var html = '';
	var parent_folder = "";
	
	for (var i = 0; i < dirs.length; i++) {
		var dir = dirs[i];
		
		if (dir) {
			parent_folder += (parent_folder ? "/" : "") + dir;
			
			html += '<span class="path_parts" onClick="prepareChooseAvailableTemplatesHtml(\'' + project_id + '\', \'' + parent_folder + '\');" title="Open this folder">' + dir + '</span>';
		}
	}
	
	return html;
}

function getChooseAvailableTemplateCurrentProjectHtml(current_path, is_project, with_project) {
	current_path = current_path.replace(/^\/+/g, "").replace(/\/+$/g, "");
	var dirs = current_path.split("/");
	var html = '';
	var parent_folder = "";
	
	for (var i = 0; i < dirs.length; i++) {
		var dir = dirs[i];
		
		if (dir) {
			parent_folder += (parent_folder ? "/" : "") + dir;
			var is_part_project = i + 1 == dirs.length && is_project;
			
			html += '<span class="path_parts' + (is_part_project ? ' path_part_project' + (with_project ? ' with_project' : '') : '') + '" onClick="' + (is_part_project ? 'prepareChooseAvailableTemplatesHtml(\'' + parent_folder + '\', \'\');' : 'prepareChooseAvailableTemplateProjectsHtml(\'' + parent_folder + '\');') + '" title="Open this folder">' + dir + '</span>';
		}
	}
	
	return html;
}

function loadAvailableProjectTemplatesHtml(project_id, handler_func) {
	handler_func = typeof handler_func == "function" ? handler_func : function(proj_id, server_data_fetched) { return prepareChooseAvailableTemplatesHtml(proj_id); };
	
	if (MyFancyPopupAvailableTemplate.settings.get_available_templates_props_url) {
		if ($.isPlainObject(MyFancyPopupAvailableTemplate.settings.available_projects_templates_props[project_id])) {
			handler_func(project_id);
		}
		else {
			var is_external_project = project_id && project_id != MyFancyPopupAvailableTemplate.settings.default_project_id;
			var popup = $(".choose_available_template_popup");
			var ul = popup.find(" > .content > " + (is_external_project ? ".projects_templates" : ".installed_templates") + " > ul");
			ul.html('<li class="loading_templates"><span class="icon loading"></span> Loading theme templates...</li>');
			
			var url = MyFancyPopupAvailableTemplate.settings.get_available_templates_props_url.replace(/#path#/, project_id);
			
			$.ajax({
				type : "get",
				url : url,
				dataType : "json",
				success : function(data, textStatus, jqXHR) {
					MyFancyPopupAvailableTemplate.settings.available_projects_templates_props[project_id] = data;
					
					handler_func(project_id, true);
				},
				error : function(jqXHR, textStatus, errorThrown) { 
					if (jqXHR.responseText)
						StatusMessageHandler.showError(jqXHR.responseText);
				},
			});
		}
	}
}

function selectAvailableTemplate(project_id, selected_template) {
	window.event.stopPropagation(); //prevent the event to fire in the parent "li" html element.
	
	var select = $(MyFancyPopupAvailableTemplate.settings.targetField);
	var current_template = select.val();
	var is_external_project = project_id && project_id != MyFancyPopupAvailableTemplate.settings.default_project_id;
	
	if (is_external_project) {
		MyFancyPopupAvailableTemplate.hidePopup();
		
		if (typeof MyFancyPopupAvailableTemplate.settings.onSelectFromOtherProject == "function")
			MyFancyPopupAvailableTemplate.settings.onSelectFromOtherProject(selected_template, project_id);
	}
	else if (current_template != selected_template || (MyFancyPopupAvailableTemplate.settings.chosen_project && MyFancyPopupAvailableTemplate.settings.chosen_project != MyFancyPopupAvailableTemplate.settings.default_project_id)) {
		if (select && select[0]) { //note that select could be null
			select.val(selected_template);
			
			//add template to selet field, if not exists yet, bc it may be a new template recent installed.
			if (select.val() != selected_template) {
				select.append('<option value="' + selected_template + '">' + selected_template + '</option>');
				select.val(selected_template);
			}
			
			select.trigger("change"); //on edit_entity_simple we must trigger the onChangeTemplate method.
		}
		
		if (typeof layer_default_template != "undefined" && (selected_template == layer_default_template || !selected_template))
			StatusMessageHandler.showMessage("This template is currently the default template for this project!");
		
		MyFancyPopupAvailableTemplate.hidePopup();
		
		if (typeof MyFancyPopupAvailableTemplate.settings.onSelect == "function")
			MyFancyPopupAvailableTemplate.settings.onSelect(selected_template, {
				include_template_samples: MyFancyPopupAvailableTemplate.settings.include_template_samples,
				include_template_samples_in_regions: MyFancyPopupAvailableTemplate.settings.include_template_samples_in_regions
			});
	}
	else {
		StatusMessageHandler.showMessage("This template is already the current selected template!");
		MyFancyPopupAvailableTemplate.hidePopup();
	}
}

function showAvailableTemplateDemo(project_id, template_id, demo_url) {
	window.event.stopPropagation(); //prevent the event to fire in the parent "li" html element.
	
	var popup = $(".show_available_template_demo_popup");
	
	if (!popup[0]) {
		popup = $('<div class="myfancypopup with_title show_available_template_demo_popup"><div class="title">Template Preview <button class="select_template">Select this template</button></div><iframe></iframe></div>');
		$(document.body).append(popup);
	}
	else {
		//remove and reload iframe so we don't see the previous loaded html
		popup.children("iframe").remove(); 
		popup.append('<iframe></iframe>');
	}
	
	popup.find(".select_template").off("click").on("click", function() {
		MyFancyPopupAvailableTemplateDemo.hidePopup();
		
		selectAvailableTemplate(project_id, template_id);
	});
	
	MyFancyPopupAvailableTemplateDemo.init({
		elementToShow: popup,
		parentElement: document,
		type: "iframe",
		url: demo_url,
	});
	MyFancyPopupAvailableTemplateDemo.showPopup();
}

function editProjectGlobalVariables() {
	if (MyFancyPopupAvailableTemplate.settings.project_global_variables_url) {
		var popup = $(".edit_project_global_variables_popup");
		
		if (!popup[0]) {
			popup = $('<div class="myfancypopup with_title edit_project_global_variables_popup"><div class="title">Edit Project Global Settings</div><iframe></iframe></div>');
			$(document.body).append(popup);
		}
		else {
			//remove and reload iframe so we always see the updated settings
			popup.children("iframe").remove(); 
			popup.append('<iframe></iframe>');
		}
		
		MyFancyPopupEditProjectGlobalVariables.init({
			elementToShow: popup,
			parentElement: document,
			type: "iframe",
			url: MyFancyPopupAvailableTemplate.settings.project_global_variables_url,
			onClose: function() {
				//update the layer_default_template
				if (typeof layer_default_template != "undefined") {
					var iframe = popup.children("iframe");
					var obj = iframe[0].contentWindow.getGlobalVariablesCodeObj();
					var vars_name = obj["vars_name"];
					var vars_value = obj["vars_value"];
					var vars = {};
					
					if (vars_name && $.isArray(vars_name) && $.isArray(vars_value))
						for (var i = 0; i < vars_name.length; i++)
							vars[ vars_name[i] ] = vars_value[i];
					else	if (obj["code"])
						vars = iframe[0].contentWindow.convertCodeIntoList(obj["code"]);
					
					if (vars && vars.hasOwnProperty("project_default_template")) {
						layer_default_template = vars["project_default_template"];
						//console.log("new layer_default_template:"+layer_default_template);
					}
				}
			},
		});
		MyFancyPopupEditProjectGlobalVariables.showPopup();
	}
}

function getAvailableFilesPropsConvertedWithFolders(available_props, folder_to_filter, add_extension) {
	var ats = {};
	folder_to_filter = folder_to_filter ? folder_to_filter.replace(/[\/]+/, "/").replace(/[\/]+$/, "") + "/" : "";
	
	for (var fp in available_props) {
		var props = available_props[fp];
		
		fp = fp.replace(/[\/]+/, "/"); //remove duplicated "/"
		fp += add_extension ? ".php" : ""; //This avoids the case where there is a file and a folder with the same name. If we do not add ".php", the one of them will be overwriten by the other one.
		
		if (!folder_to_filter || fp.substr(0, folder_to_filter.length) == folder_to_filter) {
			var fp_aux = fp;
			
			if (folder_to_filter)
				fp_aux = fp_aux.substr(folder_to_filter.length);
			
			var dirs = fp_aux.split("/");
			var file_name = dirs.pop();
			var obj = ats;
			
			for (var j = 0; j < dirs.length; j++) {
				var dir = dirs[j];
				
				if (!obj.hasOwnProperty(dir))
					obj[dir] = {};
				
				obj = obj[dir];
			}
			
			props["is_file"] = true;
			
			if (dirs.length == 0) {
				var file_id = file_name.replace(/\.php$/, "");
				
				if ($.inArray(file_id, default_available_templates) != -1)
					props["is_default_template"] = true;
			}
			
			obj[file_name] = props;
		}
	}
	
	return ats;
}

function getFolderFilesAvailableLogos(available_props) {
	var photos = [];
	
	for (var fp in available_props) {
		var props = available_props[fp];
		
		if ($.isPlainObject(props)) {
			if (props["is_file"]) {
				if (props["logo"])
					photos.push(props["logo"]);
			}
			else {
				var sub_photos = getFolderFilesAvailableLogos(props);
				photos = photos.concat(sub_photos);
			}
		}
	}
	
	return photos;
}

function onChangeIncludeTemplateSamples(elm) {
	MyFancyPopupAvailableTemplate.settings.include_template_samples = elm.checked ? true : false;
}

function onChangeIncludeTemplateSamplesInRegions(elm) {
	MyFancyPopupAvailableTemplate.settings.include_template_samples_in_regions = $(elm).val();
}

