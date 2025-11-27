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

var timeout = 10000; //10 secs

$(function () {
	setTimeout(function() {
		var textarea = $(".logs_console .logs > textarea");
		textarea.scrollTop(textarea[0].scrollHeight - textarea.height());
	}, 300);
	
	setTimeout(function() {
		updateLogs();
	}, timeout);
});

function refresh() {
	document.location = "" + document.location;
}

function updateLogs() {
	//console.log("updateLogs");
	var logs = $(".logs_console .logs");
	var file_created_time = logs.attr("file_created_time");
	var file_pointer = logs.attr("file_pointer");
	
	var url = "" + document.location;
	url = url.replace(/(ajax|file_created_time|file_created_time|explain_logs)=[^&]*/g, "");
	url += (url.indexOf("?") != -1 ? "" : "?") + "&ajax=1&file_created_time=" + file_created_time + "&file_pointer=" + file_pointer;
	
	$.ajax({
		type : "get",
		url : url,
		dataType : "json",
		success : function(data, textStatus, jqXHR) {
			var output = data["output"];
			var file_created_time = data["file_created_time"];
			var file_pointer = data["file_pointer"];
			
			if (output != "") {
				var textarea = logs.children("textarea");
				textarea.append( document.createTextNode("\n" + output) );
				
				textarea.animate({ scrollTop: textarea[0].scrollHeight - textarea.height() }, 1000);
				
				timeout = 10000; //reset timeout to 10 secs
			}
			else if (timeout < 60000) //add more 10 secs but only if timeout is not bigger than 1 min.
				timeout += 10000;
			
			logs.attr("file_created_time", file_created_time);
			logs.attr("file_pointer", file_pointer);
			
			setTimeout(function() {
				updateLogs();
			}, timeout);
		},
		error : function(jqXHR, textStatus, errorThrown) { 
			if (jqXHR.responseText)
				StatusMessageHandler.showError(jqXHR.responseText);
		},
	});
}

function explainLogs() {
	if (typeof manage_ai_action_url == "undefined")
		StatusMessageHandler.showError("Manage AI Action url is not defined. Please talk with sysadmin");
	else if (!manage_ai_action_url)
		StatusMessageHandler.showError("Artificial Intelligence is disabled. To enable it, please add your OpenAI Key in the 'Manage Permissions/Users' panel.");
	else {
		//console.log("explainLogs");
		var logs = $(".logs_console .logs");
		var textarea = logs.children("textarea");
		var icon = $(".top_bar .icon.ai");
		var ai_replies = $(".logs_console .ai_replies");
		
		var url = manage_ai_action_url + (manage_ai_action_url.indexOf("?") != -1 ? "" : "?") + "&action=explain_logs";
		var logs_output = textarea.val();
		
		if (!logs_output)
			StatusMessageHandler.showMessage("There are no logs to explain...", "", "bottom_messages", 1500);
		else if (icon.hasClass("loading"))
			StatusMessageHandler.showMessage("AI still loading. Wait a while...", "", "bottom_messages", 1500);
		else {
			icon.addClass("loading").removeClass("ai");
			var msg = StatusMessageHandler.showMessage("AI loading. Wait a while...", "", "bottom_messages", 60000);
			
			$.ajax({
				type : "post",
				url : url,
				processData: false,
				contentType: 'text/plain',
				data: logs_output,
				dataType : "html",
				success : function(message, textStatus, jqXHR) {
					//console.log(message);
					
					icon.addClass("ai").removeClass("loading");
					msg.remove();
					
					if (message) {
						logs.addClass("with_ai_replies");
						ai_replies.show();
						
						var li = $("<li>");
						li.append('<p>' + message.replace(/\n/g, "</p><p>") + '</p>');
						
						ai_replies.children("ul").append(li);
					}
					else
						StatusMessageHandler.showError("Error: Couldn't process this request with AI. Please try again...");
				},
				error : function(jqXHR, textStatus, errorThrown) {
					icon.addClass("ai").removeClass("loading");
					
					if (jqXHR.responseText)
						StatusMessageHandler.showError(jqXHR.responseText);
				},
			});
		}
	}
}

function closeLogsExplanation() {
	var logs = $(".logs_console .logs");
	var ai_replies = $(".logs_console .ai_replies");
	
	logs.removeClass("with_ai_replies");
	ai_replies.hide();
}
