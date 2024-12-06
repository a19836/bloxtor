var auto_convert = false;
var auto_save = false;
var init_auto_save_interval_id = null;
var last_auto_save_time = null;
var auto_save_action_interval = 5 * 1000; //5 seconds in milliseconds
var auto_save_connection_ttl = 30 * 1000; //30 seconds in milliseconds
var is_from_auto_save = false;
var saved_obj_id = null;
var MyFancyPopupLogin = new MyFancyPopupClass();

if (typeof _orgAjax == "undefined") { //this avoids the infinit loop if this file gets call twice, like it happens in some modules. DO NOT REMOVE THIS - 2020-09-28!
	var jquery_native_xhr_object = null;
	var _orgAjax = jQuery.ajaxSettings.xhr;
	
	jQuery.ajaxSettings.xhr = function () {
		jquery_native_xhr_object = _orgAjax();
		return jquery_native_xhr_object;
	};
}

/* Prepare full screen when press F11 key or escape when fullscreen */
if (document.addEventListener) {
	document.addEventListener("keydown", onF11KeyPress);
	document.addEventListener('webkitfullscreenchange', onEscapeF11KeyPress, false);
	document.addEventListener('mozfullscreenchange', onEscapeF11KeyPress, false);
	document.addEventListener('fullscreenchange', onEscapeF11KeyPress, false);
	document.addEventListener('MSFullscreenChange', onEscapeF11KeyPress, false);
}

/* Auto Save Functions */
function addAutoSaveMenu(selector, callback) {
	var elm = $(selector);
	var p = elm.parent();
	var item = p.children(".auto_save_activation");
	
	if (!item[0]) {
		if (!callback)
			callback = "onToggleAutoSave";
		
		item = $('<li class="auto_save_activation" title="Is Auto Save Active" onClick="toggleAutoSaveCheckbox(this, ' + callback + ')">'
					+ '<i class="icon auto_save_activation"></i>'
					+ ' <span>Enable Auto Save</span> ' //space is very important here, otherwise the label won't be aligned with the other submenus
					+ '<input type="checkbox" value="1">'
				+ '</li>');
		
		elm.before(item);
	}
	
	return item;
}
function initAutoSave(selector) {
	if (init_auto_save_interval_id)
		clearInterval(init_auto_save_interval_id);
	
	init_auto_save_interval_id = setInterval(function() {
		if (auto_save) {
			var time = last_auto_save_time + auto_save_connection_ttl + auto_save_action_interval; //Note that the timeout for the auto save ajax requests is auto_save_connection_ttl, so we add the auto_save_action_interval to the auto_save_connection_ttl so we can have a bigger margin.
			
			if (!last_auto_save_time || time < (new Date()).getTime()) { //if last_auto_save_time is null (or was reseted) or if took more than 1 minute
				last_auto_save_time = (new Date()).getTime();
				is_from_auto_save = true;
				
				//execute save function
				$(selector).trigger("click");
			}
		}
	}, auto_save_action_interval);
}
function resetAutoSave() {
	last_auto_save_time = null;
}
function toggleAutoSaveCheckbox(elm, callback) {
	setTimeout(function() {
		auto_save = !auto_save;
		
		if (typeof callback == "function")
			callback();
	}, 10);
}
function enableAutoSave(callback) {
	auto_save = true;
	
	if (typeof callback == "function")
		callback();
}
function disableAutoSave(callback) {
	auto_save = false;
	
	if (typeof callback == "function")
		callback();
}
function isAutoSaveMenuEnabled() {
	return $(".top_bar li.auto_save_activation input").is(":checked");
}
function onToggleAutoSave() {
	var li = $(".top_bar li.auto_save_activation");
	var input = li.find("input");
	var span = li.find("span");
	
	if (auto_save) {
		li.addClass("active");
		input.attr("checked", "checked").prop("checked", true);
		span.html("Disable Auto Save");
	}
	else {
		li.removeClass("active");
		input.removeAttr("checked").prop("checked", false);
		span.html("Enable Auto Save");
	}
}
function onToggleWorkflowAutoSave() {
	var li = $(".taskflowchart .workflow_menu ul.dropdown li.auto_save_activation");
	var input = li.find("input");
	var span = li.find("span");
	
	if (auto_save) {
		taskFlowChartObj.TaskFile.auto_save = false; //should be false bc the saveObj calls the getCodeForSaving method which already saves the workflow by default, and we don't need 2 saves at the same time.
		taskFlowChartObj.Property.auto_save = true;
		$(".taskflowchart").removeClass("auto_save_disabled");
		
		li.addClass("active");
		input.attr("checked", "checked").prop("checked", true);
		span.html("Disable Auto Save");
	}
	else {
		taskFlowChartObj.TaskFile.auto_save = false;
		taskFlowChartObj.Property.auto_save = false;
		$(".taskflowchart").addClass("auto_save_disabled");
		
		li.removeClass("active");
		input.removeAttr("checked").prop("checked", false);
		span.html("Enable Auto Save");
	}
}
function prepareAutoSaveVars() {
	var e = window.event;
	
	//this means that there was a real event clicked and that was an user action. So we reset the is_from_auto_save var, so it doesn't show the confirmation box when it tries to convert the workflow to code and hide the successfull saving message.
	if (e && (
		(e.screenX && e.screenX != 0 && e.screenY && e.screenY != 0) 
		|| e.shiftKey //shift is down
		|| e.altKey //alt is down
		|| e.ctrlKey //ctrl is down
		|| e.metaKey //cmd is down
	)) 
		is_from_auto_save = false;
}

function disableTemporaryAutoSaveOnInputFocus(elm) {
	if (auto_save) {
		$(elm).data("auto_save", auto_save);
		disableAutoSave();
	}
}

function undoDisableTemporaryAutoSaveOnInputBlur(elm) {
	elm = $(elm);
	
	if (!auto_save && elm.data("auto_save")) {
		enableAutoSave();
		elm.data("auto_save", null);
	}
}

/* Auto convert Functions */
function addAutoConvertMenu(selector, callback) {
	var elm = $(selector);
	var p = elm.parent();
	var item = p.children(".auto_convert_activation");
	
	if (!item[0]) {
		if (!callback)
			callback = "onToggleAutoConvert";
		
		item = $('<li class="auto_convert_activation" title="Is Auto Convert Active" onClick="toggleAutoConvertCheckbox(this, ' + callback + ')">'
					+ '<i class="icon auto_convert_activation"></i>'
					+ ' <span>Enable Auto Convert</span> ' //space is very important here, otherwise the label won't be aligned with the other submenus
					+ '<input type="checkbox" value="1">'
				+ '</li>');
		
		elm.before(item);
	}
	
	return item;
}
function toggleAutoConvertCheckbox(elm, callback) {
	setTimeout(function() {
		auto_convert = !auto_convert;
		
		if (typeof callback == "function")
			callback();
	}, 10);
}
function enableAutoConvert(callback) {
	auto_convert = true;
	
	if (typeof callback == "function")
		callback();
}
function disableAutoConvert(callback) {
	auto_convert = false;
	
	if (typeof callback == "function")
		callback();
}
function isAutoConvertMenuEnabled() {
	return $(".top_bar li.auto_convert_activation input").is(":checked");
}
function onToggleAutoConvert() {
	var li = $(".top_bar li.auto_convert_activation");
	var input = li.find("input");
	var span = li.find("span");
	
	if (auto_convert) {
		li.addClass("active");
		input.attr("checked", "checked").prop("checked", true);
		span.html("Disable Auto Convert");
	}
	else {
		li.removeClass("active");
		input.removeAttr("checked").prop("checked", false);
		span.html("Enable Auto Convert");
	}
}
function onToggleWorkflowAutoConvert() {
	var li = $(".taskflowchart .workflow_menu ul.dropdown li.auto_convert_activation");
	var input = li.find("input");
	var span = li.find("span");
	
	if (auto_convert) {
		li.addClass("active");
		input.attr("checked", "checked").prop("checked", true);
		span.html("Disable Auto Convert");
	}
	else {
		li.removeClass("active");
		input.removeAttr("checked").prop("checked", false);
		span.html("Enable Auto Convert");
	}
}

/* AJAX Functions */
function isAjaxReturnedResponseLogin(url) {
	return url && url.indexOf("/__system/auth/login") > 0;
}

function showAjaxLoginPopup(login_url, urls_to_match, success_func) {
	login_url = login_url + (login_url.indexOf("?") > -1 ? "&" : "?") + "popup=1";
	urls_to_match = $.isArray(urls_to_match) ? urls_to_match : [urls_to_match];
	var auto_save_bkp = auto_save;
	
	//prepare popup
	var popup = $('.ajax_login_popup');
	var is_popup_opened = MyFancyPopupLogin.isPopupOpened();// && popup[0] && popup.find("iframe").attr("src");
	
	if (!popup[0]) {
		popup = $('<div class="ajax_login_popup myfancypopup" style="padding:0; border-radius:5px;"></div>'); //set css here bc there is no css file for this popup.
		$("body").append(popup);
	}
	
	if (!is_popup_opened) {
		//reset iframe so we can add the new load handlers
		popup.children("iframe").remove();
		popup.html('<iframe style="border-radius:5px;"></iframe>'); //set css here bc there is no css file for this popup.
	}
	
	var iframe = popup.children("iframe");
	
	//if popup is already opened, bind the new success_func too, otherwise the window.is_save_func_running or running_save_obj_actions_count from edit_php_code and edit_file_class_method may not be reset. Note that bc the edit_file_class_method:saveFileClassMethod calls the saveObj and before calls the getFileClassMethodObj which calls the getCodeSaving which calls the generateTasksFlowFromCode asynchronously (when on code editor tab), which runs at the same time than the saveObj. Which means if the user is not logged-in, then the showAjaxLoginPopup will be called twice (by the generateTasksFlowFromCode and saveObj), so we need to save the success_func everytime the showAjaxLoginPopup gets called.
	var iframe_on_load_func = function() {
		var current_iframe_url = decodeURI(this.contentWindow.location.href);
		//console.log(current_iframe_url);
		//console.log(urls_to_match);
		
		if ($.inArray(current_iframe_url, urls_to_match) != -1 || 
			(login_url == current_iframe_url && $(this).contents().find("body").html() == "1")
		) {
			MyFancyPopupLogin.hidePopup();
			auto_save = auto_save_bkp;
			
			if (typeof success_func == "function")
				success_func();
		}
		else {
			/*var contents = $(this).contents();
			var w = contents.width();
			var h = contents.height();
			
			w = w > 380 ? w : 380;
			h = h > 280 ? h : 280;
			
			iframe.css({width: w + "px", height: h + "px"});*/
			iframe.css({width: "380px", height: "290px"});
			MyFancyPopupLogin.updatePopup(); //recenter popup
		}
	};
	iframe.bind("load", iframe_on_load_func);
	iframe.bind("unload", function() {
		iframe.bind("load", iframe_on_load_func);
	});
	
	if (!is_popup_opened)
		iframe[0].src = login_url;
	
	MyFancyPopupLogin.init({
		elementToShow: popup,
		parentElement: document,
		onOpen: function() {
			MyFancyPopupLogin.getOverlay().off();
			MyFancyPopupLogin.getPopupCloseButton().off().hide();
		},
	});
	
	MyFancyPopupLogin.showPopup();
}

/* FullScreen Functions */

function isInFullScreen() {
	return !window.screenTop && !window.screenY;
	//return document.webkitIsFullScreen || document.mozFullScreen || document.msFullscreenElement ? true : false;
}

function toggleFullScreen(elm, already_executed, in_full_screen) {
	elm = $(elm);
	var html = elm.html();
	var doc = elm[0].ownerDocument || elm[0].document;
	var win = doc.defaultView || doc.parentWindow;
	var parent_win = win;
	
	while (parent_win.parent != parent_win && typeof parent_win.parent.toggleFullScreen == "function")
		parent_win = parent_win.parent;
	
	var in_full_screen = typeof in_full_screen != "undefined" ? in_full_screen : win.isInFullScreen();
	
	if (already_executed) { //after full screen action be executed
		if (in_full_screen) {
			elm.addClass("active");
			elm.html( html.replace("Maximize", "Minimize") );
		}
		else {
			elm.removeClass("active");
			elm.html( html.replace("Minimize", "Maximize") );
		}
	}
	else {
		if (in_full_screen) {
			parent_win.closeFullscreen();
			
			elm.removeClass("active");
			elm.html( html.replace("Minimize", "Maximize") );
		}
		else {
			parent_win.openFullscreen();
			
			elm.addClass("active");
			elm.html( html.replace("Maximize", "Minimize") );
		}
	}
	
	if (typeof win.onToggleFullScreen == "function")
		win.onToggleFullScreen(already_executed ? in_full_screen : !in_full_screen);
}

function openFullscreen(elm) {
	if (!elm)
		elm = $("html")[0]; //Do not use body otherwise it loose some properties and the workflow task menu dragging items will be messy.
	
	if (elm.requestFullscreen)
		elm.requestFullscreen();
	else if (elm.webkitRequestFullscreen) /* Safari */
		elm.webkitRequestFullscreen();
	else if (elm.msRequestFullscreen) /* IE11 */
		elm.msRequestFullscreen();
}

function closeFullscreen() {
	try {
		if (document.exitFullscreen && (document.fullscreenElement || document.webkitFullscreenElement || document.mozFullScreenElement))
			document.exitFullscreen();
		else if (document.webkitExitFullscreen && document.webkitFullscreenElement) /* Safari */
			document.webkitExitFullscreen();
		else if (document.msExitFullscreen) /* IE11 */
			document.msExitFullscreen();
	}
	catch(e) {
		//if no fullscreen and this function gets called, it will give an exception
	}
}

function onF11KeyPress(event) { //on enter full screen
	var code = event.keyCode || event.which;
	
	if (code == 122 && !isInFullScreen()) { //F11
		event.preventDefault();
		event.stopPropagation();
		
		/*var target = event.target;
		var doc = target ? (target.ownerDocument || target.document) : document;
		var win = doc.defaultView || doc.parentWindow || window;*/
		var win = window;
		
		while (win.parent != win && typeof win.parent.toggleFullScreen == "function")
			win = win.parent;
		
		var elm = win.$("#top_panel .full_screen, .top_bar .full_screen > a").first();
		
		if (elm[0] && typeof win.toggleFullScreen == "function") {
			win.toggleFullScreen(elm[0]);
			win.f11_pressed = true;
		}
	}
}

function onEscapeF11KeyPress(event) { //when exit full screen
	if (!isInFullScreen()) {
		var win = window;
		
		while (win.parent != win && typeof win.parent.toggleFullScreen == "function")
			win = win.parent;
		
		if (win.f11_pressed) { //check if f11_pressed from parent window
			win.f11_pressed = false;
			
			var elm = win.$("#top_panel .full_screen, .top_bar .full_screen > a").first();
			
			if (elm[0] && typeof win.toggleFullScreen == "function")
				win.toggleFullScreen(elm[0], true, false);
		}
	}
}

/* Submenu Functions */

function openSubmenu(elm) {
	var doc = elm ? (elm.ownerDocument || elm.document) : document;
	var win = doc.defaultView || doc.parentWindow || window;
	
	if (win.event && win.event.target) { //must do this to be sure that the it was a manual click, otherwise it could be an automatic save that was executed by the system and we want to avoid this events!
		elm = $(elm);
		var sub_menu = elm.closest(".sub_menu, .top_bar_menu");
		
		if (sub_menu[0]) {
			var open_interval = sub_menu.data("open_interval");
			
			sub_menu.toggleClass("open");
			
			if (open_interval)
				win.clearInterval(open_interval);
			
			if (sub_menu.hasClass("open")) {
				var close_sub_menu = function(target) {
					var is_out = true;
					var sub_menu_elm = target ? $(target) : null;
					var menu_parent = sub_menu_elm ? $(sub_menu_elm).closest(".sub_menu, .top_bar_menu") : null;
					var sub_menu_ul = sub_menu.children("ul");
					var sub_menu_ul_lis = sub_menu_ul.find("li");
					
					if (sub_menu.is(menu_parent)) 
						is_out = false;
					
					try {
						if (is_out && (sub_menu_ul.filter(":hover").length > 0 || sub_menu_ul_lis.filter(":hover").length > 0))
							is_out = false;
					}
					catch (e) {
						//this is always giving an exception bc :hover is giving an error on jquery. Only log to console for testing purposes
						//if (console && console.log)
						//	console.log(e);
					}
					
					if (is_out) {
						//console.log(window.sub_menu_open_interval);
						var open_interval = sub_menu.data("open_interval");
						
						if (open_interval)
							win.clearInterval(open_interval);
						
						sub_menu.removeClass("open");
					}
				};
				
				open_interval = win.setInterval(function() {
					close_sub_menu(win.event ? win.event.target : null);
				}, 5000);
				
				sub_menu.data("open_interval", open_interval);
				
				if (sub_menu.data("hover_inited") != 1) {
					sub_menu.data("hover_inited", 1);
					
					sub_menu.children("ul").hover(
						function(ev) {
							
						},
						function(ev) {
							close_sub_menu(ev.target);
							
							setTimeout(function() {
								close_sub_menu(win.event ? win.event.target : null);
							}, 500);
						}
					);
				}
			}
		}
	}
}

/* Ace Editor Functions */

function setCodeEditorAutoCompleter(editor) {
	//console.log(editor);
	//var language_tools = editor.language_tools ? editor.language_tools : ace.require("ace/ext/language_tools");
	var mode = editor.session.$modeId; //eg: ace/mode/javascript, ace/mode/php, ace/mode/html
	
	if (mode == "ace/mode/php") {
		//prepare pathname by removing 2 levels from pathname
		var pathname = "" + document.location.pathname;
		var is_index_page = pathname.substring(pathname.length - 1) == "/"; //if last char is '/' means it is a folder and is opening the index page
		var pos = pathname.lastIndexOf('/');
		var status = true;
		
		if (pos != -1) {
			pathname = pathname.substring(0, pos);
			
			if (!is_index_page) {
				pos = pathname.lastIndexOf('/');
				
				if (pos != -1)
					pathname = pathname.substring(0, pos);
				else
					status = false;
			}
		}
		else
			status = false;
		
		if (status) {
			//prepare url
			pathname += "/autocomplete/get_auto_completions";
			
			var query_string = "" + document.location.search;
			query_string += (query_string.substr(0, 1) == "?" ? "&" : "?") + "pathname=" + document.location.pathname;
			
			var url = document.location.protocol + "//" +
				document.location.hostname +
				(document.location.port ? ":" + document.location.port : "") +
				pathname +
				query_string;
			
			// get completions from server
			var php_completions = [];
			
			$.ajax({
				type : "get",
				url : url,
				dataType : "json",
				success : function(completions, textStatus, jqXHR) {
					php_completions = completions;
				},
				error : function(jqXHR, textStatus, errorThrown) {
					
				}
			});
			
			// PHP completer
			var php_completer = {
				getCompletions: function (editor, session, pos, prefix, callback) {
					callback(null, php_completions);
				}
			};
			editor.completers.push(php_completer); //append to default completers
			
			// Default completers
			var default_completers = editor.completers;
			
			// Set up autocompletion switching logic
			editor.commands.on("afterExec", function (e) {
				if (e.command.name === "insertstring") {
					var char_typed = e.args;
					var prefix_to_filter = false;
					
					if (char_typed === ">" || char_typed === ":") {
						var cursor = editor.getCursorPosition(); // Current cursor position
						var line = editor.session.getLine(cursor.row); // Get the full line at the cursor's row
						var text_before_cursor = line.substring(0, cursor.column); // Extract characters up to the cursor position
						var match = text_before_cursor.match(/(\b\w+::|\$\w[^;]+\->)$/);
						//console.log(text_before_cursor);
						//console.log(match);
						
						if (match) // Check if the word being typed is '$this->', 'self::' or 'SomeOTherClass::'
							prefix_to_filter = match[1];
					}
					
					// Switch completers based on the character typed. Note that the language_tools.setCompleters doesn't work.
					if (prefix_to_filter) { //if prefix_to_filter exists, then find the correspondent php_completions that start with it
						var filtered_completions = [];
						
						if (php_completions)
							$.each(php_completions, function(idx, completion) {
								if (("" + completion["value"]).startsWith(prefix_to_filter)) {
									var comp = Object.assign({}, completion);
									comp["value"] = comp["value"].substr(prefix_to_filter.length);
									
									if (("" + completion["caption"]).startsWith(prefix_to_filter))
										comp["caption"] = comp["caption"].substr(prefix_to_filter.length);
									
									filtered_completions.push(comp);
								}
							});
						
						var filtered_completer = {
							getCompletions: function (editor, session, pos, prefix, callback) {
								callback(null, filtered_completions);
							}
						};
						
						editor.completers = [filtered_completer];
					}
					else
						editor.completers = default_completers;
					
					// Trigger autocomplete but if not white space
					var is_white_space = typeof char_typed == "string" && char_typed.match(/\s/);
					
					if (!is_white_space)
						editor.execCommand("startAutocomplete");
				}
			});
		}
	}
	
	editor.setOptions({
		enableBasicAutocompletion: true,
		enableSnippets: true,
		enableLiveAutocompletion: true,
	});
	
	//add on key press event
	/*editor.keyBinding.addKeyboardHandler(function(data, hashId, keyString, keyCode, e) {
		console.log(data);
		console.log(hashId);
		console.log(keyString);
		console.log(keyCode);
		console.log(e);
	});*/
	
	setCodeEditorSnippets(editor);
	setCodeEditorGhostText(editor);
	setCodeEditorInlineAI(editor);
	
	return editor;
}

function setCodeEditorSnippets(editor) {
	var snippet_manager = editor.snippet_manager ? editor.snippet_manager : ace.require("ace/snippets").snippetManager;
	var mode = editor.session.$modeId; //eg: ace/mode/javascript, ace/mode/php, ace/mode/html
	
	if (mode == "ace/mode/php") {
		// Adicionar snippets personalizados
		/*var php_snippets = [
			{
				content: "<?php\n\n?>",
				name: "PHP Template",
				tabTrigger: "php",
			},
			{
				content: "if (${1:condition}) {\n\t${2:// code}\n}",
				name: "If Statement",
				tabTrigger: "if",
			},
			{
				content: "function ${1:name}(${2:args}) {\n\t${3:// code}\n}",
				name: "Function",
				tabTrigger: "fn",
			},
		];

		snippet_manager.register(php_snippets, "php");*/
		
		/*setTimeout(function() {
			// Log all snippets for all modes
			for (var mode in snippet_manager.snippetMap) {
				console.log("Snippets for mode:", mode);
				console.log(snippet_manager.snippetMap[mode]);
			}

			// Log global snippets
			console.log("Global snippets:");
			console.log(snippet_manager.snippetMap["*"]);
		}, 2000);*/
	}
}

function setCodeEditorGhostText(editor) {
	var Range = ace.require("ace/range").Range;
	
	editor.ghost_marker_id = null; // Store the marker ID for cleanup
	editor.ghost_range = null;
	editor.ghost_text = null;
	
	// Function to show ghost text
	editor.showGhostText = function(text, class_name) {
		//console.log("showGhostText");
		
		if (editor.ghost_range !== null)
			editor.removeGhostText();
		
		var start_cursor = editor.getCursorPosition();
		var session = editor.session;
		
		// Add ghost text as a marker
		session.insert(start_cursor, text);
		
		var end_cursor = editor.getCursorPosition();
		
		editor.ghost_range = new Range(start_cursor.row, start_cursor.column, end_cursor.row, end_cursor.column);
		editor.ghost_marker_id = session.addMarker(editor.ghost_range, "ace-editor-marker-ghost-text" + (class_name ? " " + class_name : ""), "text", true);
		editor.ghost_text = text;
		
		editor.ghost_text_accepted = false;
	};

	// Function to remove ghost text
	editor.removeGhostText = function() {
		if (editor.ghost_range !== null) {
			//console.log("removeGhostText");
			
			editor.session.remove(editor.ghost_range);
			editor.session.removeMarker(editor.ghost_marker_id);
			
			editor.ghost_range = null;
			editor.ghost_marker_id = null;
			editor.ghost_text = null;
			editor.ghost_text_accepted = false;
		}
	}
	
	editor.acceptGhostText = function() {
		if (editor.ghost_range) {
			//console.log("acceptGhostText");
			
			editor.session.removeMarker(editor.ghost_marker_id);
			
			editor.ghost_range = null;
			editor.ghost_marker_id = null;
			editor.ghost_text = null;
			
			editor.ghost_text_accepted = true;
		}
		else
			editor.ghost_text_accepted = false;
	};

	// Handle Tab to accept ghost text
	editor.commands.addCommand({
		name: "enterKeyEvent",
		bindKey: { win: "Enter", mac: "Enter" },
		exec: function (editor2) {
			// Insert ghost text
			if (editor.ghost_range) {
				//console.log("enterKeyEvent");
			
				editor.acceptGhostText();
			}
			else { // Default Enter behavior
				editor.commands.byName["insertstring"].exec(editor, "\n");
				editor.ghost_text_accepted = false;
			}
		},
	});
	
	editor.commands.addCommand({
		name: "escapeKeyEvent", // Name of the command
		bindKey: { win: "Esc", mac: "Esc" }, // Bind Escape key for Windows and macOS
		exec: function (editor) {
			//console.log("escapeKeyEvent");
			
			// Trigger the default Escape key behavior
			editor.commands.passEvent = true;
		},
		readOnly: true, // Set to false if you want this to work in edit mode
	});
	
	// Override the getValue method to not include the ghost text before it gets accepted
	var original_get_value = editor.getValue;

	editor.getValue = function () {
		var value = null;

		if (editor.ghost_range) {
			editor.session.remove(editor.ghost_range);

			value = original_get_value.apply(this, arguments); // Call the original method

			var cursor = editor.getCursorPosition();
			editor.session.insert(cursor, editor.ghost_text);
		}
		else
			value = original_get_value.apply(this, arguments); // Call the original method
		
		return value;
	};
	
	editor.commands.on("afterExec", function (e) {
		//console.log("afterExec:" + e.command.name);
		
		if (editor.ghost_range && e.command.name == "startAutocomplete") {
			//console.log("afterExec startAutocomplete");
			if (editor.completer && editor.completer.popup && editor.completer.popup.isOpen)
				editor.completer.detach(); // Closes the autocomplete popup
		}
		// Handle typing to dismiss ghost text
		else if (editor.ghost_range && e.command.name !== "enterKeyEvent") {
			//console.log("afterExec enterKeyEvent");
			editor.removeGhostText(); // Dismiss ghost text on typing
		}
	});
}

function setCodeEditorInlineAI(editor) {
	editor.commands.on("afterExec", function (e) {
		if (typeof manage_ai_action_url != "undefined") {
			var mode = editor.session.$modeId; //eg: ace/mode/javascript, ace/mode/php, ace/mode/html
			mode = mode ? mode.replace("ace/mode/", "") : "";
			
			if (mode == "php" || mode == "css" || mode == "javascript" || mode == "html" || mode == "sql") {
				var regex = mode == "php" ? /(\/(\/|\*)|<\!\-\-)\s*ai\s*(:| |\t)/i : (
					mode == "javascript" ? /\/(\/|\*)\s*ai\s*(:| |\t)/i : (
						mode == "css" ? /\/\*\s*ai\s*(:| |\t)/i : (
							mode == "html" ? /<\!\-\-\s*ai\s*(:| |\t)/i : (
								mode == "sql" ? /\-\-\s*ai\s*(:| |\t)/i : null
							)
						)
					)
				);
				
				if (regex) {
					//show default ai instructions - add ghost text when the user types "//AI:"
					var cursor = editor.getCursorPosition(); // Current cursor position
					var text_cursor = editor.session.getLine(cursor.row); // Get the full line at the previous cursor's row
					var m = text_cursor.match(regex);
					
					if (m) {
						var prefix = m[0];
						var pos = m.index + prefix.length;
						var instructions = text_cursor.substr(pos);
						instructions = instructions.replace(/(^\s+|\s+$)/g, "");
						
						if (instructions == "" && e.command.name === "insertstring" && e.args !== "\n") {
							//console.log("afterExec insertstring");
							editor.showGhostText(" write 'chatbot' to open the chat-bot popup or describe what code would you like, and then press enter key.");
							
							return true;
						}
					}
					
					//parse user ai instructions
					var is_enter = e.command.name === "insertstring" && e.args === "\n" && !editor.ghost_text_accepted; //ghost_text_accepted is very important otherwise the AI will run twice, bc the enterKeyEvent runs first than this afterExec, accepting the ghost_text and then enter here again.
					
					if (!is_enter && e.command.name === "enterKeyEvent" && !editor.ghost_text_accepted)
						is_enter = true;
					
					//console.log(e.command.name+"(enter: " + is_enter + "):"+e.args+"!");
					
					if (is_enter) {
						var text_before_cursor = editor.session.getLine(cursor.row - 1); // Get the full line at the previous cursor's row
						var m = text_before_cursor.match(regex);
						/*console.log(text_before_cursor);
						console.log(regex);
						console.log(m);*/
						
						if (m) {
							var prefix = m[0];
							var pos = m.index + prefix.length;
							var instructions = text_before_cursor.substr(pos);
							
							if (instructions.replace(/\s*/, "") == "")
									StatusMessageHandler.showError("There are no instructions to be interpreted. Please write something after '" + text_before_cursor.substr(0, pos) + "' and only after press enter key.", "", "bottom_messages", 10000);
							else if (!manage_ai_action_url)
									StatusMessageHandler.showError("Artificial Intelligence is disabled. To enable it, please add your OpenAI Key in the 'Manage Permissions/Users' panel.", "", "bottom_messages", 10000);
							else {
								//console.log(instructions);
								//remove new line recently added
								var Range = ace.require("ace/range").Range;
								var range = new Range(cursor.row, 0, cursor.row + 1, 0);
								editor.session.remove(range);
								
								if (prefix.substr(0, 2) == "/*" && text_before_cursor.indexOf("*/", pos) == -1)
									editor.session.insert({row: cursor.row - 1, column: text_before_cursor.length}, " */");
								else if (prefix.substr(0, 4) == "<!--" && text_before_cursor.indexOf("-->", pos) == -1)
									editor.session.insert({row: cursor.row - 1, column: text_before_cursor.length}, " -->");
								
								//then get the value without the new line
								var code = editor.getValue();
								
								//then add again the new line and move cursor to the original place
								editor.session.insert({row: cursor.row, column: 0}, "\n");
								editor.selection.moveCursorTo(cursor.row, cursor.column);
								
								if (instructions.match(/^\s*chat(\s|_|\-)*bot\s*/)) {
									if (typeof editor.showCodeEditorChatBot == "function")
										editor.showCodeEditorChatBot(editor);
									else
										showCodeEditorChatBot(editor);
								}
								else {
									//get selected text and range
									var selected_code = editor.getSelectedText();
									var selected_range = editor.getSelectionRange();
									var ghost_text_feature_exists = typeof editor.showGhostText == "function";
									var system_instructions = "";
									
									if (typeof editor.system_message == "function") {
										system_instructions = editor.system_message(editor);
									}
									else if (typeof editor.system_message == "string")
										system_instructions = editor.system_message;
									
									var msg = StatusMessageHandler.showMessage("AI loading. Wait a while...", "", "bottom_messages", 60000);
									ghost_text_feature_exists && editor.showGhostText("//AI loading. Wait a while...", "ace-editor-marker-ghost-text-ai");
									
									var url = manage_ai_action_url + (manage_ai_action_url.indexOf("?") != -1 ? "" : "?") + "&action=generate_inline_code";
									
									var post_data = {
										lang: mode,
										instructions: instructions,
										system_instructions: system_instructions,
										code: code,
										selected_code: selected_code,
										selected_range: selected_range,
									};
									
									try {
										$.ajax({
											type : "post",
											url : url,
											processData: false,
											contentType: 'text/plain',
											data: JSON.stringify(post_data),
											dataType : "json",
											success : function(data, textStatus, jqXHR) {
												//console.log(data);
												msg.remove();
												ghost_text_feature_exists && editor.removeGhostText();
												
												var status = $.isPlainObject(data) ? data["status"] : false;
												
												if (status) {
													var new_code = data["code"];
													//console.log(new_code);
													
													if (!new_code && new_code !== 0)
														StatusMessageHandler.showError("Error: AI didn't return any code.");
													else {
														new_code = new_code.replace(/\\n/g, "\n"); //replace all \n in code bc AI sometimes returns the code with escaped end lines.
														
														var replacement_type = data["replacement_type"];
														var replacement_range = data["replacement_range"];
														
														if (replacement_type == "replace_selection" && !selected_code && !replacement_range)
															replacement_type = "append";
														
														switch(replacement_type) {
															case "replace":
																editor.setValue(new_code);
																break;
															case "replace_selection":
																//remove added line, so the ranges work fine
																var range = new Range(cursor.row, 0, cursor.row + 1, 0);
																editor.session.remove(range);
																
																//prepare range
																var range = selected_code && $.isPlainObject(selected_range) && selected_range.start && selected_range.end ? selected_range : null;
																
																if (!range && $.isPlainObject(replacement_range) && replacement_range.hasOwnProperty("start") && $.isPlainObject(replacement_range.start) && replacement_range.start.hasOwnProperty("row") && replacement_range.start.hasOwnProperty("column")) {
																	range = replacement_range;
																}
																
																//replace code
																if (range)
																	editor.session.replace(range, new_code);
																else
																	StatusMessageHandler.showError("Error: no selected range to append new code");
																break;
															
															case "append_to_selection":
																//remove added line, so the ranges work fine
																var range = new Range(cursor.row, 0, cursor.row + 1, 0);
																editor.session.remove(range);
																
																//prepare range
																var range = selected_code && $.isPlainObject(selected_range) && selected_range.end ? selected_range.end : null;
																
																if (!range && $.isPlainObject(replacement_range) && replacement_range.hasOwnProperty("end") && $.isPlainObject(replacement_range.end) && replacement_range.end.hasOwnProperty("row") && replacement_range.end.hasOwnProperty("column")) {
																	range = replacement_range.end;
																}
																
																//append code
																if (range) {
																	var suffix = new_code.substr(new_code.length - 1) != "\n" ? "\n" : "";
																	range = {
																		row: range.row + 1,
																		column: 0
																	};
																	
																	if (ghost_text_feature_exists) {
																		editor.selection.moveCursorTo(range.row, range.column);
																		editor.showGhostText(new_code + suffix);
																		StatusMessageHandler.showMessage("Press Enter key to accept AI suggestion", "", "bottom_messages");
																	}
																	else
																		editor.session.insert(range, new_code + suffix);
																}
																else
																	StatusMessageHandler.showError("Error: no selected range to append new code");
																break;
																
															//case "append":
															default:
																//show ai suggestion
																if (ghost_text_feature_exists) {
																	editor.showGhostText(new_code);
																	StatusMessageHandler.showMessage("Press Enter key to accept AI suggestion", "", "bottom_messages");
																}
																else
																	editor.session.insert(cursor, new_code);
														}
													}
												}
												else
													StatusMessageHandler.showError("Error: Couldn't process this request with AI. Please try again...");
											},
											error : function(jqXHR, textStatus, errorThrown) {
												msg.remove();
												ghost_text_feature_exists && editor.removeGhostText();
												
												if (jqXHR.responseText)
													StatusMessageHandler.showError(jqXHR.responseText);
											},
										});
									}
									catch(e) {
										msg.remove();
										ghost_text_feature_exists && editor.removeGhostText();
										
										StatusMessageHandler.showError("During: Exception during the request with AI. Please try again..." + (e.message ? e.message : e));
									}
								}
							}
						}
					}
				}
			}
		}
	});
}

function getCodeEditorChatBotDefaultSystemMessage(editor) {
	var system_message = "";
	
	if (editor) {
		//prepare system_message with selected text and range
		var mode = editor.session.$modeId; //eg: ace/mode/javascript, ace/mode/php, ace/mode/html
		mode = mode ? mode.replace("ace/mode/", "") : "";
		
		var all_code = editor.getValue();
		var selected_code = editor.getSelectedText();
		var selected_range = editor.getSelectionRange();
		
		system_message = "You are an expert in " + mode + (mode == "php" ? " and html" : "") + ".";
		
		if (selected_code)
			system_message += "\n\nCode of user selection:\n```" + mode + "\n" + selected_code + "\n```";
		
		if (selected_range && $.isPlainObject(selected_range.start) && $.isPlainObject(selected_range.end) && (selected_range.start.row != selected_range.end.row || selected_range.start.column != selected_range.end.column))
			system_message += "\n\nRange of user selection:"
						+ "\n- start row: " + selected_range.start.row + ";"
						+ "\n- start column: " + selected_range.start.column + ";"
						+ "\n- end row: " + selected_range.end.row + ";"
						+ "\n- end column: " + selected_range.end.column + ";";
		
		if (all_code)
			system_message += "\n\nAll code in the editor:\n```" + mode + "\n" + all_code + "\n```"; 
	}
	
	return system_message;
}

function showCodeEditorChatBot(editor) {
	if (typeof manage_ai_action_url != "undefined") {
		showChatBotPopup();
		
		//prepare system_message with selected text and range
		var popup = MyFancyPopup.settings.elementToShow;
		var chat_bot_elm = popup.children(".chat_bot");
		chat_bot_elm[0].system_message = function() {
			if (typeof editor.system_message == "function")
				return editor.system_message(editor);
			else if (typeof editor.system_message == "string" && editor.system_message.length > 0)
				return editor.system_message;
			else
				return getCodeEditorChatBotDefaultSystemMessage(editor);
		};
		
		//Disable auto save, when on code editor, because the systems focus the editor everytime runs the save function, meaning that if the user is writing at the same time in the '.user_input' field, the cursor will move to the editor, getting out from the popup and giving a bad user experience. So we need to disable auto_save temporary, so the user can write freely in the user_input.
		chat_bot_elm.find(" > .user_box > .user_input").on("focus", function(event) {
			if (!this.enable_auto_save_on_blur) {
				if (auto_save) {
					this.enable_auto_save_on_blur = true;
					auto_save = false;
				}
				else
					this.enable_auto_save_on_blur = false;
			}
		})
		.on("blur", function(event) {
			if (this.enable_auto_save_on_blur) {
				this.enable_auto_save_on_blur = false;
				auto_save = true;
			}
		})
	}
}

/* AI Functions */

function showChatBotPopup() {
	if (typeof manage_ai_action_url != "undefined") {
		var popup = $(".chat_bot_popup");
		var chat_bot_elm = popup.children(".chat_bot");
		
		if (!popup[0]) {
			chat_bot_elm = getChatBotElm();
			/* Deprecated bc the popup has now a fixed width!
			var appendMessage_bkp = chat_bot_elm.appendMessage;
			
			chat_bot_elm.appendMessage = function(role, message, class_name) {
				appendMessage_bkp(role, message, class_name);
				
				MyFancyPopup.updatePopup();
			}*/
			
			popup = $('<div class="myfancypopup chat_bot_popup with_title">'
							+ '<div class="title">Co-Pilot Assistant</div>'
						+ '</div>');
			popup.append(chat_bot_elm);
			$(document.body).append(popup);
		}
		
		//init and show popup
		MyFancyPopup.init({
			elementToShow: popup,
			parentElement: document,
			popup_class: "chat_bot_popup",
		});
		
		MyFancyPopup.showPopup();
		
		//disable draggable for some elements
		popup.draggable("option", "cancel", ".session_selector, .chat_box, .user_input, .send_button");
		
		//allow selections on .chat_bot, so the user can copy code.
		chat_bot_elm.children(".chat_box").on('selectstart', (e) => {
			e.stopPropagation(); // Prevent selection interference
		});
		
		//focus on user input
		chat_bot_elm.find(" > .user_box > .user_input").focus();
	}
}

function getChatBotElm() {
	var url = manage_ai_action_url + (manage_ai_action_url.indexOf("?") != -1 ? "" : "?") + "&action=chat&convert_to_html=1";
	
	var chat_bot_elm = $('<div class="chat_bot">'
					+ '<div class="title">Co-Pilot Assistant</div>'
					+ '<div class="session">'
						+ '<label>Select or Create a Chat Session:</label>'
						+ '<select class="session_selector">'
							+ '<option value="new">New Session</option>'
						+ '</select>'
					+ '</div>'
					+ '<div class="chat_box"></div>'
					+ '<div class="user_box">'
						+ '<input class="user_input" type="text" placeholder="Type a message..." />'
						+ '<span class="icon send send_button">Send</span>'
					+ '</div>'
				+ '</div>');
	$(document.body).append(chat_bot_elm);
	
	var chat_box = chat_bot_elm.children(".chat_box");
	var session_selector = chat_bot_elm.find("> .session > .session_selector");
	var user_input = chat_bot_elm.find(" > .user_box > .user_input");
	var send_button = chat_bot_elm.find(" > .user_box > .send_button");
	
	var current_session = null;
	var sessions = {}; // Store conversation histories

	// Refresh the chat window with messages from the current session
	chat_bot_elm.refreshChat = function() {
		chat_box.html("");
		
		if (sessions[current_session]) {
			sessions[current_session].forEach(msg => {
				chat_bot_elm.appendMessage(msg.role, msg.message);
			});
		}
	}

	// Append a message to the chat window
	chat_bot_elm.appendMessage = function(role, message, class_name) {
		var msg = $('<div class="' + role + (class_name ? " " + class_name : "") + '">' + message + '</div>'
			+ '<span class="clearfix"></span>');
		chat_box.append(msg);
		
		$.each(msg.find("pre > code"), function(idx, code) {
			var p = $(code).parent();
			p.addClass("code_block");
			
			//highlight code
			if (typeof hljs == "object")
				hljs.highlightBlock(code);
			
			//add copy icon
			var btn = $('<button class="copy_button">Copy</button>');
			p.prepend(btn);
			
			btn.on("click", function() {
				// Get the text content of the <code> tag
				var codet_text = code.textContent;

				// Copy the text to clipboard
				navigator.clipboard.writeText(codet_text).then(function() {
					btn.html("Copied!");
					
					setTimeout(function() {
						btn.html("Copy");
					}, 2000);
				})
				.catch(function() {
					btn.html("Failed");
					
					setTimeout(function() {
						btn.html("Copy");
					}, 2000);
				});
			});
		});
		
		chat_box.scrollTop(chat_box[0].scrollHeight);
	}

	// Create a new session after the user sends their first message
	chat_bot_elm.initializeNewSession = function() {
		var new_session_id = "session-" + Date.now();
		sessions[new_session_id] = [];
		current_session = new_session_id;

		// Add the new session to the session selector dropdown
		session_selector.append('<option value="' + new_session_id + '">Session ' + Object.keys(sessions).length + '</option>');
		session_selector.val(new_session_id);
		
		chat_bot_elm.refreshChat();
	}

	// Send message to the backend
	chat_bot_elm.sendMessage = function() {
		// Initialize session if not already initialized
		if (!current_session)
			chat_bot_elm.initializeNewSession();
		
		var user_message = user_input.val().trim();
		var system_message = "";
		
		if (typeof chat_bot_elm[0].system_message == "function")
			system_message = chat_bot_elm[0].system_message();
		else if (typeof chat_bot_elm[0].system_message == "string" && chat_bot_elm[0].system_message.length > 0)
			system_message = chat_bot_elm[0].system_message;
		
		if (!user_message || !current_session)
			return;

		// Store the user message in the session history
		sessions[current_session].push({ role: "user", user_message });
		chat_bot_elm.appendMessage("user", user_message);
		user_input.val("");
		
		send_button.addClass("loading");
		
		$.ajax({
			type : "post",
			url : url,
			data : {
				system_message: system_message,
				user_message: user_message,
				session: current_session
			},
			dataType : "json",
			success : function(data, textStatus, jqXHR) {
				send_button.removeClass("loading");
				
				if (data && data.reply_html) {
					var reply = data.reply_html;
					
					// Store the bot reply in the session history
					sessions[current_session].push({ role: "bot", message: reply });
					chat_bot_elm.appendMessage("bot", reply);
				}
				else
					chat_bot_elm.appendMessage("bot", "Error: No response.", "error");
			},
			error : function(jqXHR, textStatus, errorThrown) {
				send_button.removeClass("loading");
				
				chat_bot_elm.appendMessage("bot", "Error: Could not fetch response." + (jqXHR.responseText ? "\n" + jqXHR.responseText : ""), "error");
			}
		});
	}

	// Event listeners
	send_button.click(chat_bot_elm.sendMessage);
	
	user_input.keypress(function(event) {
		if (event.key === "Enter") 
			chat_bot_elm.sendMessage();
	});

	// Session selector change event
	session_selector.change(function() {
		var selected_session = $(this).val();
		
		if (selected_session === "new")
			chat_bot_elm.initializeNewSession();
		else {
			current_session = selected_session;
			chat_bot_elm.refreshChat();
		}
	});

	// Wait for the user to send the first message before initializing the session
	
	return chat_bot_elm;
}

/* LayoutUIEditor Functions */

function onContextMenuLayoutUIEditorWidgetSetting(elm, context_menu_elm, ev) {
	if (typeof manage_ai_action_url != "undefined") {
		var menu_settings = elm.parent().closest(".menu-settings");
		
		if (menu_settings) {
			var is_image_src = elm.attr("name") == "src" && menu_settings.hasClass("menu-settings-image");
			var exists = context_menu_elm.children(".generate_image_with_ai").length > 0;
			
			if (is_image_src && !exists) {
				var html = $('<li class="generate_image_with_ai" title="Generate Image through AI"><a>Generate Image through AI</a></li>');
				
				html.children("a").on("click", function() {
					MyContextMenu.hideContextMenu(context_menu_elm);
					
					openImageUrlCreationWithAIPopup(function(url) {
						elm.val(url);
						elm.blur();
					});
				});
				
				context_menu_elm.append(html);
			}
			else if (!is_image_src && exists)
				context_menu_elm.children(".generate_image_with_ai").remove();
		}
	}
	
	return true;
}

function openImageUrlCreationWithAIPopup(handler) {
	if (typeof manage_ai_action_url != "undefined") {
		var popup = $(".image_url_creation_with_ai_popup");
		var chat_bot_elm = popup.children(".chat_bot");
		
		if (!popup[0]) {
			popup = $('<div class="myfancypopup image_url_creation_with_ai_popup with_title">'
							+ '<div class="title">Generate Image through AI</div>'
							+ '<div class="sub_title">Create automatic <strong>Image</strong> through Artificial Intelligence.<br/>Please write below in natural language which type of image you are looking for:</div>'
							+ '<textarea></textarea>'
							+ '<button onClick="MyFancyPopup.settings.sendFunction(this)">Generate</button>'
						+ '</div>');
			$(document.body).append(popup);
		}
		
		//init and show popup
		MyFancyPopup.init({
			elementToShow: popup,
			parentElement: document,
			sendFunction: function(elm) {
				var url = manage_ai_action_url + (manage_ai_action_url.indexOf("?") != -1 ? "" : "?") + "&action=generate_html_image";
				
				var msg = StatusMessageHandler.showMessage("AI is generating image. Wait a while...", "", "bottom_messages", 60000);
				StatusMessageHandler.getMessageHtmlObj()[0].style.setProperty("z-index", parseInt(popup.css("z-index")) + 1, "important"); //move error to front of popup
				
				var btn = popup.children("button");
				btn.addClass("loading");
				
				var post_data = "Generates 1 image based in this instructions:\n" + popup.children("textarea").val();
				
				$.ajax({
					type : "post",
					url : url,
					data: {
						instructions: post_data,
					},
					dataType : "json",
					success : function(data, textStatus, jqXHR) {
						//console.log(data);
						
						msg.remove();
						btn.removeClass("loading");
						
						var items = data.hasOwnProperty("items") ? data["items"] : "";
						var url = items.length > 0 && $.isPlainObject(items[0]) && items[0].hasOwnProperty("url") ? items[0]["url"] : null;
						
						if (url) {
							handler(url);
							MyFancyPopup.hidePopup();
						}
						else {
							StatusMessageHandler.showError("Error: Couldn't generate image through AI. Please try again...");
							StatusMessageHandler.getMessageHtmlObj()[0].style.setProperty("z-index", parseInt(popup.css("z-index")) + 1, "important"); //move error to front of popup
						}
					},
					error : function(jqXHR, textStatus, errorThrown) {
						msg.remove();
						btn.removeClass("loading");
						
						if (jqXHR.responseText) {
							StatusMessageHandler.showError(jqXHR.responseText);
							StatusMessageHandler.getMessageHtmlObj()[0].style.setProperty("z-index", parseInt(popup.css("z-index")) + 1, "important"); //move error to front of popup
						}
					},
				});
			}
		});
		
		MyFancyPopup.showPopup();
		
		if (!manage_ai_action_url) {
			StatusMessageHandler.showMessage("Artificial Intelligence is disabled. To enable it, please add your OpenAI Key in the 'Manage Permissions/Users' panel.");
			StatusMessageHandler.getMessageHtmlObj()[0].style.setProperty("z-index", parseInt(popup.css("z-index")) + 1, "important"); //move error to front of popup
		}
	}
}

/* Utils Functions */

function normalizeFileName(new_file_name, allow_upper_case, confirmed) {
	//normalize new file name
	if (new_file_name) {
		var has_accents = new_file_name.match(/([\x7f-\xff\u1EBD\u1EBC]+)/gi);
		var has_spaces = new_file_name.match(/\s+/g);
		var has_upper_case = !allow_upper_case && new_file_name.toLowerCase() != new_file_name;
		//var has_weird_chars = new_file_name.match(/([\p{L}\w\.]+)/giu).join("") != new_file_name; // \. is very important bc the new_file_name is the complete filename with the extension. \p{L} and /../u is to get parameters with accents and ç. Already includes the a-z. Cannot use this bc it does not work in IE.
		var has_weird_chars = new_file_name.match(/([\w\u00C0-\u00D6\u00D8-\u00F6\u00F8-\u024F\u1EBD\u1EBC\.]+)/gi);
		has_weird_chars = has_weird_chars && has_weird_chars.join("") != new_file_name; // \. is very important bc the new_file_name is the complete filename with the extension. '\w' means all words with '_' and 'u' means with accents and ç too.
		
		if ((has_accents || has_spaces || has_upper_case || has_weird_chars) && (confirmed || confirm("Is NOT advisable to have file names with spaces, dashes, letters with accents, upper case letters or weird characters.\nYou should only use the following letters: A-Z, 0-9 and '_'.\nCan I normalize this name and convert it to a proper name?"))) {
			if (typeof new_file_name.normalize == "function") //This doesn't work in IE11
				new_file_name = new_file_name.normalize("NFD");
			
			new_file_name = new_file_name.replace(/[\u0300-\u036f]/g, "").replace(/[\s\-]+/g, "_").match(/[\w\.]+/g).join(""); // \. is very important bc the new_file_name is the complete filename with the extension.
			
			if (!allow_upper_case)
				new_file_name = new_file_name.toLowerCase();
		}
	}
	
	return new_file_name;
}
