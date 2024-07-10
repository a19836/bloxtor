var iframe_overlay = null; //To be used by sub-pages

$(function() {
	//prepare left panel
	var left_panel = $("#left_panel");
	left_panel.tabs();
	
	left_panel.find(" > ul.tabs > .tab a").click(function(idx, a){
		MyContextMenu.hideAllContextMenu();
	});
	
	//prepare right panel iframe
	var win_url = "" + document.location;
	win_url = win_url.indexOf("#") != -1 ? win_url.substr(0, win_url.indexOf("#")) : win_url;
	
	iframe_overlay = $('#right_panel .iframe_overlay');
	var iframe = $('#right_panel iframe');
	var iframe_unload_func = function (e) {
		iframe_overlay.show();
	};
	
	iframe.load(function() {
		$(iframe[0].contentWindow).unload(iframe_unload_func);
	
		iframe_overlay.hide();
		
		//prepare redirect when user is logged out
		try {
			iframe[0].contentWindow.$.ajaxSetup({
				complete: function(jqXHR, textStatus) {
					if (jqXHR.status == 200 && jqXHR.responseText.indexOf('<div class="login">') > 0 && jqXHR.responseText.indexOf('<div id="layoutAuthentication">') > 0) 
						document.location = win_url;
			    	}
			});
		}
		catch (e) {}
	});
	$(iframe[0].contentWindow).unload(iframe_unload_func);
	
	//prepare redirect when user is logged out
	$.ajaxSetup({
		complete: function(jqXHR, textStatus) {
			if (jqXHR.status == 200 && jqXHR.responseText.indexOf('<div class="login">') > 0 && jqXHR.responseText.indexOf('<div id="layoutAuthentication">') > 0)
				document.location = win_url;
	    	}
	});
	
	//prepare hide_panel
	$("#hide_panel").draggable({
		axis: "x",
		appendTo: 'body',
		containment: $("#hide_panel").parent(),
		cursor: 'move',
		cancel: '.button',
		start : function(event, ui) {
			if ($(this).children(".button").hasClass("minimize")) {
				$('#right_panel iframe').hide(); // We need to hide the iframe bc the draggable event has some problems with iframes
				return true;
			}
			return false;
		},
		drag : function(event, ui) {
			updatePanelsAccordingWithHidePanel();
		},
		stop : function(event, ui) {
			updatePanelsAccordingWithHidePanel();
			$('#right_panel iframe').show();
		}
	});
	
	//prepare menu tree
	initFileTreeMenu();
});

function updatePanelsAccordingWithHidePanel() {
	var menu_panel = $("#menu_panel");
	var left_panel = $("#left_panel");
	var sub_menu_panel = left_panel.find(".sub_menus");
	var hide_panel = $("#hide_panel");
	var right_panel = $("#right_panel");
	
	var left = parseInt(hide_panel.css("left"));
	left = left < 50 ? 50 : (left > $(window).width() - 50 ? $(window).width() - 50 : left);
	
	menu_panel.css("width", left + "px");
	left_panel.css("width", left + "px");
	sub_menu_panel.css("width", left + "px");
	hide_panel.css("left", left + "px");
	right_panel.css("left", (left + 5) + "px"); //5 is the width of the hide_panel
}

function toggleLeftPanel(elm) {
	button = $(elm);
	
	var menu_panel = $("#menu_panel");
	var left_panel = $("#left_panel");
	var hide_panel = $("#hide_panel");
	var right_panel = $("#right_panel");
	
	if (button.hasClass("maximize")) {
		menu_panel.show();
		left_panel.show();
		hide_panel.css("left", hide_panel.attr("bkp_left"));
		right_panel.css("left", right_panel.attr("bkp_left"));
		button.removeClass("maximize").addClass("minimize");
	}
	else {
		hide_panel.attr("bkp_left", hide_panel.css("left"));
		right_panel.attr("bkp_left", right_panel.css("left"));
		
		menu_panel.hide();
		left_panel.hide();
		hide_panel.css("left", "0");
		right_panel.css("left", "5px");
		button.removeClass("minimize").addClass("maximize");
	}
}

function toggleSubmenus(elm) {
	$(elm).parent().toggleClass("open");
}

function toggleComplexityLevel(elm) {
	elm = $(elm);
	var level = elm.val();
	var left_panel = $("#left_panel");
	
	if (level == 1)
		left_panel.addClass("advanced_level");
	else 
		left_panel.removeClass("advanced_level");
}

//is used in the goTo function
function goToHandler(url, a, attr_name, originalEvent) {
	iframe_overlay.show();
	
	setTimeout(function() {
		try {
			$("#right_panel iframe")[0].src = url;
		}
		catch(e) {
			//sometimes gives an error bc of the iframe beforeunload event. This doesn't matter, but we should catch it and ignore it.
			if (console && console.log)
				console.log(e);
		}
	}, 100);
}

function goBack() {
	var iframe = $("#right_panel iframe")[0];
	var win = iframe.contentWindow;
	
	if (win)
		win.history.go(-1);
}

function refreshIframe() {
	$("#right_panel .iframe_overlay").show();
	
	var iframe = $("#right_panel iframe")[0];
	var doc = (iframe.contentWindow || iframe.contentDocument);
	doc = doc.document ? doc.document : doc;
	
	try {
		var url = "" + doc.location;
		
		if (url.indexOf("#") != -1)
			url = url.substr(0, url.indexOf("#"));
		
		iframe.src = url;
	}
	catch(e) {
		//sometimes gives an error bc of the iframe beforeunload event. This doesn't matter, but we should catch it and ignore it.
		if (console && console.log)
			console.log(e);
	}
}

//overwrite the initFileTreeMenu function in the admin_menu.js
function initFileTreeMenu() {
	var tree_items = $("#left_panel .mytree");
	//console.log(tree_items);
	
	$.each(tree_items, function(idx, item) {
		item = $(item);
		var id = item.attr("id");
		
		var item_tree = new MyTree({
			multiple_selection : false,
			toggle_selection : false,
			toggle_children_on_click : true,
			ajax_callback_before : prepareLayerNodes1,
			ajax_callback_after : prepareLayerNodes2,
			ajax_callback_error : validateLayerNodesRequest,
			on_select_callback : selectMyTreeNode,
			default_id: id + "_",
		});
		item_tree.init(id);
		
		item.data("item_tree", item_tree);
	});
	
	//init tree items contextmenus
	initContextMenus();
	
	//prepare tree items with only one layer
	$.each(tree_items, function(idx, item) {
		item = $(item);
		var item_tree = item.data("item_tree");
		
		//if layer is unique, then show only its content
		var main_ul = item.children("ul");
		var children = main_ul.children("li");
		
		if (children.length == 1) {
			var child = children.first();
			var sub_children = child.find(" > ul > li");
			
			item.addClass("hide_tree_item").removeClass("with_sub_groups");
			child.removeClass("jstree-closed").addClass("jstree-open");
			
			if (sub_children.length == 0)
				item_tree.refreshNodeChilds(child, {ajax_callback_last: function(ul, data) {
					iniSubMenu(child, item_tree);
				}});
			else if (child.is(".main_node_presentation")) { //it means the project is inside
				var project_li = sub_children.first();
				project_li.removeClass("jstree-closed").addClass("jstree-open hide_tree_item with_sub_groups");
				
				iniSubMenu(project_li, item_tree);
				
				//open the pages li by default
				var pages_li = project_li.find(" > ul > li > a > i.entities_folder").parent().parent();
				mytree = item_tree;
				refreshAndShowNodeChilds(pages_li);
			}
			else {
				iniSubMenu(child, item_tree);
			}
		}
		
		item.removeClass("hidden");
	});
}

function iniSubMenu(tree_item_li, item_tree) {
	var a = tree_item_li.children("a");
	var context_menu_id = a.data("context_menu_id");
	var context_menu_options = a.data("context_menu_options");
	
	if (context_menu_id && $.isPlainObject(context_menu_options) && typeof context_menu_options.callback == "function") {
		var layers_elm = tree_item_li.parent().closest(".layers");
		var sub_menus_elm = layers_elm.children(".sub_menus");
		var sub_menus_ul = sub_menus_elm.children("ul");
		var contextmenu = $("#" + context_menu_id);
		var contextmenu_items_exists = contextmenu.children("li").length > 0;
		var originalEvent = window.event;
		var originalEvent = jQuery.Event("click", { 
			target: a.children("label")[0] 
		} );
		
		if (contextmenu_items_exists) {
			if (context_menu_options.callback(a, contextmenu, originalEvent)) {
				var new_contextmenu = contextmenu.clone();
				var new_li = document.createElement("li");
				new_li = $(new_li);
				new_li.attr("class", "external_sub_menu");
				new_li.append(new_contextmenu);
				sub_menus_ul.prepend(new_li);
				
				new_contextmenu.find("a").click(function() {
					//overwrite the mytree object with the correct object
					mytree = item_tree;
					
					//hide submenus
					sub_menus_elm.removeClass("open");
				});
			}
		}
		else if (sub_menus_ul.children("li").length == 0) { //delete sub_menus bc there are none
			sub_menus_elm.remove();
			layers_elm.removeClass("with_sub_menus");
		}
	}
}

//overwrite the initContextMenus function in the admin_menu.js
function initContextMenus() {
	var tree_items = $("#left_panel .mytree");
	
	$.each(tree_items, function(idx, item) {
		item = $(item);
		var item_id = item.attr("id");
		
		//remove _tree if exists in id
		if (item_id.substr(item_id.length - 5) == "_tree")
			item_id = item_id.substr(0, item_id.length - 5);
		
		var obj = null;
		
		if (item_id == "db_layers") {
			obj = item.find("li.main_node_db");
			addLiContextMenu(obj.children("a").addClass("link"), "main_db_group_context_menu", {callback: onDBContextMenu});
			initDBContextMenu(obj);//This covers the scenario where the DB_DRIVER node is inside of the ".db_layers li.main_node_db" and ".db_layers" node
		}
		else if (item_id == "data_access_layers") {
			obj = item.find("li.main_node_ibatis");
			addLiContextMenu(obj.children("a").addClass("link"), "main_ibatis_group_context_menu", {callback: onIbatisContextMenu});
			initIbatisContextMenu(obj);
			
			obj = item.find("li.main_node_hibernate");
			addLiContextMenu(obj.children("a").addClass("link"), "main_hibernate_group_context_menu", {callback: onHibernateContextMenu});
			initHibernateContextMenu(obj);
		}
		else if (item_id == "business_logic_layers") {
			obj = item.find("li.main_node_businesslogic");
			addLiContextMenu(obj.children("a").addClass("link"), "main_business_logic_group_context_menu", {callback: onContextContextMenu});
			initContextContextMenu(obj);
		}
		else if (item_id == "presentation_layers") {
			obj = item.find("li.main_node_presentation");
			addLiContextMenu(obj.children("a").addClass("link"), "main_presentation_group_context_menu", {callback: onPresentationContextMenu});
			initPresentationContextMenu(item);
		}
		else {
			obj = item.find("li.main_node_lib");
			initLibContextMenu(obj);
			
			obj = item.find("li.main_node_dao");
			addLiContextMenu(obj.children("a").addClass("link"), "main_dao_group_context_menu", {callback: onDaoContextMenu});
			initDaoContextMenu(obj);
			
			obj = item.find("li.main_node_vendor");
			addLiContextMenu(obj.children("a").addClass("link"), "main_vendor_group_context_menu", {callback: onVendorContextMenu});
			initVendorContextMenu(obj);
			
			obj = item.find("li.main_node_test_unit");
			addLiContextMenu(obj.children("a").addClass("link"), "main_test_unit_group_context_menu", {callback: onTestUnitContextMenu});
			initTestUnitContextMenu(obj);
			
			obj = item.find("li.main_node_other");
			addLiContextMenu(obj.children("a").addClass("link"), "main_other_group_context_menu", {callback: onVendorContextMenu});
			initOtherContextMenu(obj);
		}
		
		prepareParentChildsEventToHideContextMenu(item);
		addSubMenuIconToParentChildsWithContextMenu(item);
		
		//var selected_menu_properties = $("#selected_menu_properties");
	});
}

//overwrite the addLiContextMenu function in the admin_menu.js so we can init the mytree var.
function addLiContextMenu(target, context_menu_id, options) {
	//set mytree var with the correct object
	options = $.isPlainObject(options) ? options : {};
	
	var item_tree = target.parent().closest(".mytree");
	var default_mytree = typeof options["item_tree"] == "object" ? options["item_tree"] : null;
	
	var func = options["callback"];
	options["callback"] = function(t, cm, e) {
		//overwrite the mytree object with the correct object
		mytree = default_mytree ? default_mytree : item_tree.data("item_tree");
		
		if (typeof func == "function")
			return func(t, cm, e) ? true : false;
		
		return true;
	};
	
	//all code below should be the same than the original addLiContextMenu function in js/admin/admin_menu.js
	target.addcontextmenu(context_menu_id, options);
	
	//this will be used in the presentation/list.js
	target.data("context_menu_id", context_menu_id);
	target.data("context_menu_options", options);
}

function selectMyTreeNode(node) {
	var item_tree = $(node).parent().closest(".mytree");
	mytree = item_tree.data("item_tree");
	
	return true;
}
