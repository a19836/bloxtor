var chooseProjectFolderUrlFromFileManagerTree = null;
var MyFancyPopupEditProjectDetailsWithProgram = new MyFancyPopupClass();

$(function () {
	//init ui
	MyFancyPopup.init({
		parentElement: window,
	});
	
	chooseProjectFolderUrlFromFileManagerTree = new MyTree({
		multiple_selection : false,
		toggle_selection : false,
		toggle_children_on_click : true,
		ajax_callback_before : prepareLayerNodes1,
		ajax_callback_after : removeAllThatIsNotProjectFolderFromTree,
	});
	chooseProjectFolderUrlFromFileManagerTree.init("choose_project_folder_url_from_file_manager");
	
	MyFancyPopup.hidePopup();
});

function removeAllThatIsNotProjectFolderFromTree(ul, data) {
	ul = $(ul);
	
	ul.find("i.project, i.project_common").each(function(idx, elm){
		$(elm).parent().parent().remove();
	});
}

function onChangeProjectWithDB(elm) {
	elm = $(elm);
	var value = $(elm).val();
	var p = elm.parent().closest(".edit_project_details");
	var db_details = p.find(".db_details");
	
	if (value === "1") {
		onChangeDBType( db_details.find(".db_type > select")[0] );
		
		db_details.show();
	}
	else
		db_details.hide();
}

function onChooseProjectFolder(elm) {
	var p = $(elm).parent();
	var popup = $("#choose_project_folder_url_from_file_manager");
	
	MyFancyPopup.init({
		elementToShow: popup,
		parentElement: document,
		onOpen: function() {
			var html = popup.find(".mytree ul").html();
			
			if (!html) 
				updateLayerUrlFileManager( popup.find(".broker select")[0] );
		},
		
		targetField: p,
		updateFunction: chooseProjectFolder
	});
	
	MyFancyPopup.showPopup();
}

function chooseProjectFolder(elm) {
	var node = chooseProjectFolderUrlFromFileManagerTree.getSelectedNodes();
	node = node[0];
	
	if (node) {
		var a = $(node).children("a");
		var folder_path = a.attr("folder_path");
		var is_project_folder = a.children("i").first().is(".project_folder");
		
		if (folder_path && is_project_folder) {
			var p = MyFancyPopup.settings.targetField;
			p.children("input[name=project_folder]").val(folder_path);
			
			MyFancyPopup.hidePopup();
		}
		else
			alert("invalid selected project folder.\nPlease choose a valid project folder.");
	}
}

function toggleAdvancedOptions(elm) {
	elm = $(elm);
	var edit_project_details = elm.parent().closest(".edit_project_details");
	
	edit_project_details.toggleClass("with_advanced_options");
	
	if (edit_project_details.hasClass("with_advanced_options"))
		elm.html("Hide Advanced Mode");
	else
		elm.html("Show Advanced Mode");
	
	//scroll to the end of the page
	$(window).scrollTop(1000);
}

function toggleDBAdvancedOptions(elm) {
	toggleDBAdvancedFields(elm);
	
	elm = $(elm);
	
	if (elm.hasClass("can_be_shown"))
		elm.html("Hide Advanced DB Options");
	else
		elm.html("Show Advanced DB Options");
}

function goToManageLayoutTypePermissions(elm) {
	var url = $(elm).attr("url");
	
	if (url) {
		if (is_popup) {
			//if inside of the admin_home_project.php which is inside of the admin_advanced.php
			if (typeof window.parent.parent.goTo == "function" &&  window.parent.parent != window.parent) 
				window.parent.document.location = url;
			//if inside of the admin_advanced.php
			else if (typeof window.parent.goToNew == "function") { //when this is the main parent window, it means we are in the choose project in the simple workspace, which means, we need to open a new window
				window.parent.goToNew(elm, "url");
				window.parent.MyFancyPopup.hidePopup();
			}
			//if in an independent window
			else
				window.parent.document.location = url;
		}
		//if no popup
		else
			document.location = url;
	}
}

//is used in the goTo function
function goToHandler(url, a, attr_name, originalEvent) {
	document.location = url;
}

function submitForm(elm) {
	elm = $(elm);
	var oForm = elm.parent().closest(".top_bar").parent().find(".edit_project_details form");
	oForm.submit();
}

function addProject(oForm) {
	oForm = $(oForm);
	var btn = oForm.find(".buttons input");
	var icon = $(".top_bar header li.save > a");
	
	if (btn.hasClass("loading")) {
		StatusMessageHandler.showMessage("Another saving action is already running. Please wait until it finishes...");
		return false;
	}
	
	btn.addClass("loading");
	icon.addClass("loading");
	
	var is_project_created = oForm.attr("project_created") == "1";
	var project_name = oForm.find(".name input[name=name]").val();
	project_name = project_name ? ("" + project_name).replace(/^\s+/g, "").replace(/\s+$/g, "") : "";
	
	if (project_name) {
		var normalize = oForm.find(".name .auto_normalize input[type=checkbox]").is(":checked");
		project_name = normalizeFileName(project_name, false, normalize);
		oForm.find(".name input[name=name]").val(project_name);
		
		MyFancyPopup.showOverlay();
		MyFancyPopup.showLoading();
		
		var old_project_folder = oForm.find(".project_folder input[name=old_project_folder]").val();
		var project_folder = oForm.find(".project_folder input[name=project_folder]").val();
		var old_project_name = oForm.find(".name input[name=old_name]").val();
		var rename_project = is_project_created && old_project_name != project_name;
		var move_project = is_project_created && old_project_folder != project_folder;
		var action = rename_project ? "rename" : (move_project ? "paste_and_remove" : "create_folder");
		var path = "";
		var extra = (project_folder ? project_folder + "/" : "") + project_name;
		
		if (rename_project) {
			path = (project_folder ? project_folder + "/" : "") + old_project_name;
			extra = project_name;
		}
		else if (move_project) {
			path = (project_folder ? project_folder + "/" : "");
			var original_project_path = (old_project_folder ? old_project_folder + "/" : "") + project_name;
			var item_type = getParameterByName(manage_project_url, "item_type");
			extra = "[" + bean_name + "," + bean_file_name + "," + original_project_path + "," + item_type + "]";
		}
		
		var url = manage_project_url.replace("#action#", action).replace("#path#", path).replace("#bean_name#", bean_name).replace("#bean_file_name#", bean_file_name).replace("#extra#", extra);
		
		if (is_project_created && !rename_project && !move_project) {
			setTimeout(function() {
				btn.removeClass("loading");
				icon.removeClass("loading");
			}, 1000);
			
			return true;
		}
		
		$.ajax({
			type : "get",
			url : url,
			dataType : "text",
			success : function(data, textStatus, jqXHR) {
				if (data == "1") {
					oForm.attr("onSubmit", "");
					oForm.submit();
				}
				else
					StatusMessageHandler.showError("Error: Project not " + (action == "create_folder" ? "created" : "renamed") + "! Please try again." + (data ? "\n" + data : ""));
			},
			error : function(jqXHR, textStatus, errorThrown) { 
				if (jqXHR.responseText);
					StatusMessageHandler.showError(jqXHR.responseText);
			}
		}).always(function() {
			MyFancyPopup.hidePopup();
			
			setTimeout(function() {
				btn.removeClass("loading");
				icon.removeClass("loading");
			}, 1000);
		});
	}
	else {
		StatusMessageHandler.showError("Project name cannot be empty");
		btn.removeClass("loading");
		icon.removeClass("loading");
	}
	
	return false;
}
