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

var docbookFilesFromFileManagerTree = null;

$(function () {
	docbookFilesFromFileManagerTree = new MyTree({
		multiple_selection : false,
		toggle_children_on_click : true,
		ajax_callback_before : prepareLayerNodes1,
		ajax_callback_after : addDocbookAction,
	});
	docbookFilesFromFileManagerTree.init("docbook_tree");
});

function addDocbookAction(ul, data) {
	ul = $(ul);
	
	ul.find("i.file").each(function(idx, elm){
		elm = $(elm);
		var p = elm.parent();
		
		if (p.closest(".org, .vendor").length) {
			var icon = $('<i class="icon view" title="View Doc Book"></i>');
			icon.on("click", function(event) {
				openFileDocbook(p.parent());
			});
			p.append(icon);
		}
		
		var icon = $('<i class="icon code" title="View Code"></i>');
		icon.on("click", function(event) {
			openFileCode(p.parent());
		});
		p.append(icon);
	});
	
	ul.find("i.folder").each(function(idx, elm){
		var a = $(elm).parent();
		var label = a.children("label").text();
		
		if ((label == "org" || label == "vendor") && a.parent().parent().parent().children("a").children("label").text() == "LIB") 
			a.parent().addClass(label);
	});
}

function openFileDocbook(li) {
	openFile(open_file_docbook_url, li);
}

function openFileCode(li) {
	openFile(open_file_code_url, li);
}

function openFile(url, li) {
	var a = li.children("a");
	var bean_name = a.attr("bean_name");
	var file_path = a.attr("file_path");
	var iframe = li.children("iframe");
	var file_url = url.replace("#path#", bean_name + "/" + file_path);
	
	//console.log(bean_name + "/" + file_path);
	
	if (!iframe[0]) {
		iframe = $('<iframe class="file_docbook" src="' + file_url + '"></iframe>');
		li.append(iframe);
	}
	else if (iframe.attr("src") != file_url)
		iframe.attr("src", file_url);
	else
		iframe.toggle();
}
