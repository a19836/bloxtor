var testUnitFilesFromFileManagerTree = null;
var executed_tests_responses = {};

$(function () {
	testUnitFilesFromFileManagerTree = new MyTree({
		multiple_selection : false,
		toggle_children_on_click : true,
		ajax_callback_before : prepareLayerNodes1,
		ajax_callback_after : addTestUnitAction,
	});
	testUnitFilesFromFileManagerTree.init("test_units_tree");
	
	$(".test_units_tree > .mytree > li > a").each(function(idx, elm) {
		elm = $(elm);
		elm.attr("file_path", "");
		var li = elm.parent();
		li.prepend( elm.children(".select_test_unit")[0] );
		
		var icon = $('<i class="icon refresh" title="Refresh"></i>');
		icon.on("click", function(event) {
			event.preventDefault();
			event.stopPropagation();
			
			testUnitFilesFromFileManagerTree.refreshNodeChilds(li);
			li.children(".executed_test_response").hide();
		});
		elm.append(icon);
		
		var icon = $('<i class="icon add_folder" title="Add Group"></i>');
		icon.on("click", function(event) {
			event.preventDefault();
			event.stopPropagation();
			
			manageFile(this, "create_folder");
		});
		elm.append(icon);
		
		var icon = $('<i class="icon add_test" title="Add Test-Unit"></i>');
		icon.on("click", function(event) {
			event.preventDefault();
			event.stopPropagation();
			
			manageFile(this, "create_file");
		});
		elm.append(icon);
		
		var icon = $('<i class="icon execute" title="Execute"></i>');
		icon.on("click", function(event) {
			event.preventDefault();
			event.stopPropagation();
			
			executeTests( $(this).parent().closest("li").children(".select_test_unit"), true );
		});
		elm.append(icon);
		
		var icon = $('<i class="icon info" title="Test Results"></i>');
		icon.on("click", function(event) {
			event.preventDefault();
			event.stopPropagation();
			
			li.children(".executed_test_response").toggle("fast");
		});
		icon.hide();
		elm.append(icon);
	});
});

function addTestUnitAction(ul, data) {
	ul = $(ul);
	
	ul.find("i.file").each(function(idx, elm){
		var li = $(elm).parent().parent();
		
		if (li.children("ul").children().length == 0)
			li.remove();
	});
	
	ul.find("i.test_unit_obj").each(function(idx, elm) {
		elm = $(elm);
		var p = elm.parent();
		var li = p.parent();
		
		var icon = $('<i class="icon edit" title="Edit"></i>');
		icon.on("click", function(event) {
			event.preventDefault();
			event.stopPropagation();
			
			openTestUnit(li);
		});
		p.append(icon);
		
		var icon = $('<i class="icon remove" title="Remove"></i>');
		icon.on("click", function(event) {
			event.preventDefault();
			event.stopPropagation();
			
			manageFile(this, "remove");
		});
		p.append(icon);
		
		var icon = $('<i class="icon rename" title="Rename"></i>');
		icon.on("click", function(event) {
			event.preventDefault();
			event.stopPropagation();
			
			manageFile(this, "rename");
		});
		p.append(icon);
		
		var icon = $('<i class="icon execute" title="Execute"></i>');
		icon.on("click", function(event) {
			event.preventDefault();
			event.stopPropagation();
			
			executeTests( $(this).parent().closest("li").children(".select_test_unit"), true );
		});
		p.append(icon);
		
		var icon = $('<i class="icon info" title="Test Results"></i>');
		icon.on("click", function(event) {
			event.preventDefault();
			event.stopPropagation();
			
			li.children(".executed_test_response").toggle("fast");
		});
		icon.hide();
		p.append(icon);
		
		var is_checked = li.parent().closest("li").children(".select_test_unit").is(":checked");
		var checkbox = $('<input class="select_test_unit" type="checkbox" value=1 onClick="onTestUnitCheckboxClick(this)" ' + (is_checked ? "checked" : "") + '/>');
		li.prepend(checkbox);
		
		var path = p.attr("file_path");
		if (path && executed_tests_responses.hasOwnProperty(path))
			parseExecutedTestReponse(li, executed_tests_responses[path]);
	});
	
	ul.find("i.folder").each(function(idx, elm) {
		elm = $(elm);
		var p = elm.parent();
		var li = p.parent();
		
		var icon = $('<i class="icon refresh" title="Refresh"></i>');
		icon.on("click", function(event) {
			event.preventDefault();
			event.stopPropagation();
			
			testUnitFilesFromFileManagerTree.refreshNodeChilds(li);
			li.children(".executed_test_response").hide();
		});
		p.append(icon);
		
		var icon = $('<i class="icon add_folder" title="Add Folder"></i>');
		icon.on("click", function(event) {
			event.preventDefault();
			event.stopPropagation();
			
			manageFile(this, "create_folder");
		});
		p.append(icon);
		
		var icon = $('<i class="icon add_test" title="Add Test-Unit"></i>');
		icon.on("click", function(event) {
			event.preventDefault();
			event.stopPropagation();
			
			manageFile(this, "create_file");
		});
		p.append(icon);
		
		var icon = $('<i class="icon remove" title="Remove"></i>');
		icon.on("click", function(event) {
			event.preventDefault();
			event.stopPropagation();
			
			manageFile(this, "remove");
		});
		p.append(icon);
		
		var icon = $('<i class="icon rename" title="Rename"></i>');
		icon.on("click", function(event) {
			event.preventDefault();
			event.stopPropagation();
			
			manageFile(this, "rename");
		});
		p.append(icon);
		
		var icon = $('<i class="icon execute" title="Execute"></i>');
		icon.on("click", function(event) {
			event.preventDefault();
			event.stopPropagation();
			
			executeTests( $(this).parent().closest("li").children(".select_test_unit"), true );
		});
		p.append(icon);
		
		var icon = $('<i class="icon info" title="Test Results"></i>');
		icon.on("click", function(event) {
			event.preventDefault();
			event.stopPropagation();
			
			li.children(".executed_test_response").toggle("fast");
		});
		icon.hide();
		p.append(icon);
		
		var is_checked = li.parent().closest("li").children(".select_test_unit").is(":checked");
		var checkbox = $('<input class="select_test_unit" type="checkbox" value=1 onClick="onTestUnitCheckboxClick(this)" ' + (is_checked ? "checked" : "") + '/>');
		li.prepend(checkbox);
		
		var path = p.attr("file_path");
		if (path && executed_tests_responses.hasOwnProperty(path))
			parseExecutedTestReponse(li, executed_tests_responses[path]);
	});
	
	ul.find("i.file").each(function(idx, elm) {
		elm = $(elm);
		var p = elm.parent();
		var li = p.parent();
		
		var path = p.attr("file_path");
		if (path && executed_tests_responses.hasOwnProperty(path))
			parseExecutedTestReponse(li, executed_tests_responses[path]);
	});
}

function onTestUnitCheckboxClick(elm) {
	setTimeout(function() {
		elm = $(elm);
		var li = elm.parent().closest("li");
		var is_checked = elm.is(":checked");
		
		li.children("ul").find(".select_test_unit").prop("checked", is_checked);
	}, 100);
}

function openTestUnit(li) {
	var a = li.children("a");
	var file_path = a.attr("file_path");
	var edit_test_unit_file = li.children(".edit_test_unit_file");
	
	if (!edit_test_unit_file[0]) {
		var url = open_test_unit_file_url.replace("#path#", file_path);
		edit_test_unit_file = $('<div class="edit_test_unit_file"><i class="icon refresh" title="Refresh this frame"></i><iframe src="' + url + '"></iframe></div>');
		
		edit_test_unit_file.children(".refresh").click(function() {
			edit_test_unit_file.children("iframe")[0].src = url;
		});
		
		li.append(edit_test_unit_file);
	}
	else 
		edit_test_unit_file.toggle();
}

function executeSelectedTests(force) {
	//get selected paths
	var inputs = $("#test_units_tree .select_test_unit:checked");
	executeTests(inputs, force);
}

function executeTests(inputs, force) {
	var selected_paths = [];
	var paths_lis = {};
	
	$.each(inputs, function(idx, input) {
		var li = $(input).parent().closest("li");
		var a = li.children("a");
		var path = a.attr("file_path");
		
		paths_lis[path] = li[0];
		selected_paths.push(path);
	});
	
	//execute tests in groups of 5 requests at once.
	var index = 0;
	var length = selected_paths.length;
	var limit = 5;
	var timeout_id = null;
	
	var func = function() {
		var l = index + limit < length ? index + limit : length;
		var paths_group = selected_paths.slice(index, l);
		
		executeTestsGroup(paths_group, paths_lis, force);
		
		index += limit;
		
		if (index < length)
			timeout_id = setTimeout(func, 1000);
		else if (timeout_id)
			clearTimeout(timeout_id);
	};
	
	func();
}

function executeTestsGroup(paths_group, paths_lis, force) {
	var paths_to_request = [];
	
	$.each(paths_group, function(idx, path) {
		var li = $(paths_lis[path]);
		li.removeClass("success warning error");
		li.children(".executed_test_response").remove();
		
		//only execcute the tests that were not executed already.
		if (!force && executed_tests_responses.hasOwnProperty(path))
			parseExecutedTestReponse(li, executed_tests_responses[path]);
		else 
			paths_to_request.push(path);
	});
	
	if (paths_to_request.length > 0)
		$.ajax({
			type : "post",
			url : execute_tests_url,
			data : {selected_paths: paths_to_request},
			dataType : "json",
			success : function(data, textStatus, jqXHR) {
				if (data && $.isPlainObject(data))
					$.each(data, function(item_path, item_response) {
						//backup responses
						if ($.isPlainObject(item_response))
							executed_tests_responses[item_path] = item_response;
						
						//prepare test response
						var item_li = paths_lis.hasOwnProperty(item_path) ? paths_lis[item_path] : null;
						
						if (!item_li)
							item_li = $("#test_units_tree li > a[file_path='" + item_path + "']").first().parent()[0];
						
						if (item_li) {
							item_li = $(item_li);
							
							parseExecutedTestReponse(item_li, item_response);
							
							//if item_li is a file, show response in the test_unit_obj too
							if (item_li.find(" > a > i").hasClass("file")) {
								item_li = item_li.find(" > ul > li > a[file_path='" + item_path + "']").first().parent();
								
								if (item_li[0])
									parseExecutedTestReponse(item_li, item_response);
							}
						}
					});
			},
			error : function(jqXHR, textStatus, errorThrown) {
				$.each(paths_group, function(idx, path) {
					var li = $(paths_lis[path]);
					li.addClass("error");
					li.find(" > a > .info").show();
					
					var msg = "Error trying to execute test: " + path + ". Please try again...";
					msg += jqXHR.responseText ? "\n" + jqXHR.responseText : "";
					showExecutedTestResponse(li, msg);
				});
			},
		});
}

function parseExecutedTestReponse(li, response) {
	li.removeClass("success warning error");
	li.children(".executed_test_response").remove();
	li.find(" > a > .info").show();
	
	if (response && $.isPlainObject(response)) {
		if (response["status"]) {
			if (response["error"]) {
				li.addClass("warning");
				showExecutedTestResponse(li, response["error"]);
			}
			else {
				li.addClass("success");
				
				if (parseInt(response["status"]) != 1)
					showExecutedTestResponse(li, "Test Result: " + response["status"]);
			}
		}
		else {
			li.addClass("error");
			
			if (response["error"])
				showExecutedTestResponse(li, response["error"]);
		}
	}
}

function showExecutedTestResponse(li, msg) {
	var div = li.children(".executed_test_response");
	
	if (!div[0]) {
		div = $('<div class="executed_test_response"></div>');
		
		if (li.children("ul").length)
			li.children("ul").before(div);
		else
			li.children("a").after(div);
	}
	
	div.html(msg);
	div.hide();
}

function manageFile(icon, action, on_success_callback) {
	var a = $(icon).parent().closest("a");
	var file_path = a[0].hasAttribute("file_path") ? a.attr("file_path") : "";
	var url = action == "create_file" ? create_test_url : manage_file_url;
	
	if (url && (file_path || (action == "create_folder" || action == "create_file"))) {
		var new_file_name;
		var status = false;
		var tree_node_id_to_be_updated = a.parent().attr("id");
		
		file_name = file_path.substr(file_path.length - 1, 1) == "/" ? file_path.substr(0, file_path.length - 1) : file_path;
		file_name = file_name.lastIndexOf("/") != -1 ? file_name.substr(file_name.lastIndexOf("/") + 1) : file_name;
		
		switch (action) {
			case "remove": 
				status = confirm("Do you wish to remove the file '" + file_name + "'?"); 
				break;
				
			case "create_folder": 
			case "create_file": 
				status = (new_file_name = prompt("Please write the file name:")); 
				break;
				
			case "rename": 
				var pos = file_name.lastIndexOf(".");
				
				if (pos != -1) {
					var base_name = file_name.substr(0, pos);
					var extension = file_name.substr(pos + 1);
					status = (new_file_name = prompt("Please write the new name:", base_name));
					new_file_name += "." + extension;
				}
				else
					status = (new_file_name = prompt("Please write the new name:", base_name));
				break;
		}
		
		if (status) {
			if ((action == "rename" || action == "create_folder" || action == "create_file") && !new_file_name)
				alert("Error: File name cannot be empty");
			else {
				url = url.replace("#path#", file_path);
				url = url.replace("#action#", action);
				url = url.replace("#extra#", new_file_name);
				
				var str = action == "create_folder" || action == "create_file" ? "create" : action;
				
				$.ajax({
					type : "get",
					url : url,
					success : function(data, textStatus, jqXHR) {
						if (data == "1") {
							StatusMessageHandler.showMessage("The file was " + str + "d correctly", "", "bottom_messages", 1500);
							
							if (typeof on_success_callback == "function")
								on_success_callback(icon, action, new_file_name, url, tree_node_id_to_be_updated);
							
							if (action == "create_folder" || action == "create_file")
								testUnitFilesFromFileManagerTree.refreshNodeChildsByNodeId(tree_node_id_to_be_updated);
							else if (action == "rename" || action == "remove") {
								//finds the main node with url to refresh
								var parent_node_id = tree_node_id_to_be_updated;
								var url = "", parent_node = null;
								
								do {
									parent_node = $("#" + parent_node_id).parent().parent();
									url = parent_node.children("ul").attr("url");
									parent_node_id = parent_node.attr("id");
								}
								while (!url);
								
								testUnitFilesFromFileManagerTree.refreshNodeChilds(parent_node);
							}
						}
						else
							StatusMessageHandler.showError("There was a problem trying to " + str + " file. Please try again..." + (data ? "\n" + data : ""));
					},
					error : function(jqXHR, textStatus, errorThrown) { 
						var msg = jqXHR.responseText ? "\n" + jqXHR.responseText : "";
						StatusMessageHandler.showError((errorThrown ? errorThrown + " error.\n" : "") + "Error trying to " + str + " file.\nPlease try again..." + msg);
					},
				});
			}
		}
	}
	
	return false;
}
