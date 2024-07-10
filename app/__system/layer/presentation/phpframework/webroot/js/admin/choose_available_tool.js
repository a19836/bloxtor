var is_popup = false;

function goTo(url, originalEvent, on_parent_location) {
	originalEvent = originalEvent || window.event;
	
	//if ctrl key is pressed
	if (originalEvent && (originalEvent.ctrlKey || originalEvent.keyCode == 65)) {
		var win = window.open(url);
		
		if (win)
			win.focus();
	}
	else if (is_popup) { //if is popup
		if (on_parent_location)
			window.parent.document.location = url;
		else if (typeof window.parent.ToolsFancyPopup != "undefined" && typeof window.parent.ToolsFancyPopup.settings.goTo == "function")
			window.parent.ToolsFancyPopup.settings.goTo(url, originalEvent);
		else
			window.parent.document.location = url;
	}
	else //if is current window
		document.location = url;
	
	return false;
}

function flushCacheFromAdmin(url) {
	$.ajax({
		type : "get",
		url : url,
		dataType : "text",
		success : function(data, textStatus, jqXHR) {
			if (jquery_native_xhr_object && isAjaxReturnedResponseLogin(jquery_native_xhr_object.responseURL))
				showAjaxLoginPopup(jquery_native_xhr_object.responseURL, url, function() {
					StatusMessageHandler.removeLastShownMessage("error");
					flushCacheFromAdmin(url);
				});
			else if (data == "1") 
				StatusMessageHandler.showMessage("Cache flushed!", "", "bottom_messages", 1500);
			else
				StatusMessageHandler.showError("Cache NOT flushed! Please try again..." + (data ? "\n" + data : ""));
		},
		error : function(jqXHR, textStatus, errorThrown) { 
			if (jqXHR.responseText);
				StatusMessageHandler.showError(jqXHR.responseText);
		}
	});
	
	return false;
}
