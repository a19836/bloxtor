var iframe_overlay = null; //Tobe used by sub-pages

$(function() {
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
});

function toggleLeftpanel(elm) {
	elm = $(elm);
	var body = $("body");
	
	if (body.hasClass("left_panel_shrinked")) {
		body.removeClass("left_panel_shrinked");
		//elm.removeClass("fa-arrow-right").addClass("fa-arrow-left");
	}
	else {
		body.addClass("left_panel_shrinked");
		//elm.removeClass("fa-arrow-left").addClass("fa-arrow-right");
	}
}

function showSubMenu(elm) {
	event.preventDefault();
	event.stopPropagation();
	
	$(elm).parent().closest("li").toggleClass("open");
	
	return false;
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
