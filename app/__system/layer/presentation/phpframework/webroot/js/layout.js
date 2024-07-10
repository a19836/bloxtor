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
