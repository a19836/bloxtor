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
	url = url.replace(/(ajax|file_created_time|file_created_time)=[^&]*/g, "");
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
