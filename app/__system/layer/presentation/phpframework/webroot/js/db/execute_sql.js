$(function () {
	var wh = $(window).height();
	var h = wh - 155;
	h = h < 300 ? 300 : h;
	
	var sql_results = $(".sql_results");
	sql_results.css("height", h + "px");
	
	/*sql_results.find("table").first().DataTable({
		"scrollY": (h - 70) + "px",
		"scrollCollapse": true,
		"scrollX": false,
		"lengthMenu": [[50, 100, 200, 500, -1], [50, 100, 200, 500, "All"]],
	});

	sql_results.find(".dataTables_scrollHead, .dataTables_scrollHead thead tr").each(function(idx, elm){
		$(elm).addClass("table_header");
	});*/
});

function execute(type) {
	var editor = $(".sql_text_area").data("editor");
	var sql = editor.getValue();
	
	type = type ? type : "";
	
	$("#main_column").append('<form id="form_sql" method="post" style="display:none"><textarea name="sql"></textarea><input type="hidden" name="type" value="' + type + '"/></form>');
	$("#form_sql textarea").val(sql);
	$("#form_sql")[0].submit();
}

function createSQLEditor() {
	var sql_text_area = $(".sql_text_area");
	var textarea = sql_text_area.children("textarea")[0];
	
	//prepare editor
	ace.require("ace/ext/language_tools");
	var editor = ace.edit(textarea);
	editor.setTheme("ace/theme/chrome");
	editor.session.setMode("ace/mode/sql");
 	editor.setAutoScrollEditorIntoView(true);
	editor.setOption("minLines", 30);
	editor.setOptions({
		enableBasicAutocompletion: true,
		enableSnippets: true,
		enableLiveAutocompletion: true,
	});
	editor.setOption("wrap", true);
	
	if (typeof setCodeEditorAutoCompleter == "function")
		setCodeEditorAutoCompleter(editor);
	
	//prepare special autocomplete
	if (table_attrs) {
		var table_completer = {
			getCompletions: function(editor, session, pos, prefix, callback) {
				var aux = [{
					caption: table,
					value: table,
					meta: "table",
					score: 11
				}];
				callback(null, aux);
			},
		};
		editor.completers.push(table_completer); //append to default completers
		
		var table_completions = [];
		
		$.each(table_attrs, function(attr_name, attr_props) {
			table_completions.push({
				caption: attr_name,
				value: attr_name,
				meta: "attribute",
				score: 10
			});
		});
		
		var table_attrs_completer = {
			getCompletions: function(editor, session, pos, prefix, callback) {
				callback(null, table_completions);
			},
		};
		editor.completers.push(table_attrs_completer); //append to default completers
		
		// Default completers
		var default_completers = editor.completers;
		
		// Set up autocompletion switching logic
		editor.commands.on("afterExec", function (e) {
			if (e.command.name === "insertstring") {
				var char_typed = e.args;
				var filter_by_table_attrs = false;
				
				if (char_typed === ".") {
					var cursor = editor.getCursorPosition(); // Current cursor position
					var line = editor.session.getLine(cursor.row); // Get the full line at the cursor's row
					var text_before_cursor = line.substring(0, cursor.column); // Extract characters up to the cursor position
					var match = text_before_cursor.match(/\b`?(\w+)`?\.$/);
					var last_word = match ? match[1] : null;
					
					if (last_word && last_word == table || last_word.toLowerCase() == table.toLowerCase())
						filter_by_table_attrs = true;
				}
				
				// Switch completers based on the character typed. Note that the language_tools.setCompleters doesn't work.
				if (filter_by_table_attrs) //if filter_by_table_attrs exists, only show table_attrs_completer
					editor.completers = [table_attrs_completer];
				else
					editor.completers = default_completers;
					
				// Trigger autocomplete but if not white space
				var is_white_space = typeof char_typed == "string" && char_typed.match(/\s/);
				
				if (!is_white_space)
					editor.execCommand("startAutocomplete");
			}
		});
	}
	
	//prepare chatbot
	editor.system_message = getCodeChatBotSystemMessage;
	editor.showCodeEditorChatBot = openCodeChatBot;
	
	sql_text_area.find("textarea.ace_text-input").removeClass("ace_text-input"); //fixing problem with scroll up, where when focused or pressed key inside editor the page scrolls to top.
	
	sql_text_area.data("editor", editor);
}

/* START: AI */
function openGenerateSQLPopup() {
	if (typeof manage_ai_action_url != "undefined") {
		var popup = $(".generate_sql");
		
		if (!popup[0]) {
			popup = $('<div class="myfancypopup generate_sql with_title">'
							+ '<div class="title">Generate SQL</div>'
							+ '<div class="instructions">'
								+ '<label>Please write in natural language which type of sql statement do you wish to generate:</label>'
								+ '<textarea placeHolder="eg: get all records"></textarea>'
							+ '</div>'
							+ '<div class="button">'
								+ '<button onClick="generateSQL(this)">Generate SQL</button>'
							+ '</div>'
						+ '</div>');
			$(document.body).append(popup);
			
			var choose_db_table_or_attribute = $("#choose_db_table_or_attribute");
			var cloned = choose_db_table_or_attribute.find(".contents").clone(true); //true: clone data events also
			cloned.children(".db_attribute").remove();
			
			popup.find(".title").after(cloned);
		}
		
		MyFancyPopup.init({
			elementToShow: popup,
			parentElement: document,
		});
		
		MyFancyPopup.showPopup();
	}
}
function generateSQL(elm) {
	var zindex = parseInt(MyFancyPopup.settings.elementToShow.css("z-index")) + 1;
	
	if (typeof manage_ai_action_url == "undefined") {
		StatusMessageHandler.showError("Manage AI Action url is not defined. Please talk with sysadmin");
		StatusMessageHandler.getMessageHtmlObj()[0].style.setProperty("z-index", zindex, "important"); //move error to front of filemanager popup
	}
	else if (!manage_ai_action_url) {
		StatusMessageHandler.showError("Artificial Intelligence is disabled. To enable it, please add your OpenAI Key in the 'Manage Permissions/Users' panel.");
		StatusMessageHandler.getMessageHtmlObj()[0].style.setProperty("z-index", zindex, "important"); //move error to front of filemanager popup
	}
	else {
		var p = $(elm).parent().parent();
		var button = p.find(".button");
		var instructions = p.find("textarea").val();
		
		if (!instructions) {
			StatusMessageHandler.showError("Please write what sql statement do you wish to create.");
			StatusMessageHandler.getMessageHtmlObj()[0].style.setProperty("z-index", zindex, "important"); //move error to front of filemanager popup
		}
		else {
			var msg = StatusMessageHandler.showMessage("AI loading. Wait a while...", "", "bottom_messages", 60000);
			StatusMessageHandler.getMessageHtmlObj()[0].style.setProperty("z-index", zindex, "important"); //move error to front of filemanager popup
			
			var editor = $(".sql_text_area").data("editor");
			var system_instructions = getCodeChatBotSystemMessage(editor);
			var url = manage_ai_action_url + (manage_ai_action_url.indexOf("?") != -1 ? "" : "?") + "&action=generate_sql";
			
			MyFancyPopup.showLoading();
			button.hide();
			
			$.ajax({
				type : "post",
				url : url,
				data: {
					db_table: table,
					instructions: instructions,
					system_instructions: system_instructions
				},
				dataType : "json",
				success : function(data, textStatus, jqXHR) {
					//console.log(sql);
					MyFancyPopup.hideLoading();
					button.show();
					msg.remove();
					
					var sql = $.isPlainObject(data) && data.hasOwnProperty("sql") ? data["sql"] : null;
					
					if (sql) {
						var editor = $(".sql_text_area").data("editor");
						editor.setValue(sql);
						
						MyFancyPopup.hidePopup();
						p.find("textarea").val("");
					}
					else {
						StatusMessageHandler.showError("Error: Couldn't process this request with AI. Please try again...");
						StatusMessageHandler.getMessageHtmlObj()[0].style.setProperty("z-index", zindex, "important"); //move error to front of filemanager popup
					}
				},
				error : function(jqXHR, textStatus, errorThrown) {
					MyFancyPopup.hideLoading();
					button.show();
					msg.remove();
					
					if (jqXHR.responseText) {
						StatusMessageHandler.showError(jqXHR.responseText);
						StatusMessageHandler.getMessageHtmlObj()[0].style.setProperty("z-index", zindex, "important"); //move error to front of filemanager popup
					}
				},
			});
		}
	}
}

function explainSQL() {
	if (typeof manage_ai_action_url == "undefined")
		StatusMessageHandler.showError("Manage AI Action url is not defined. Please talk with sysadmin");
	else if (!manage_ai_action_url)
		StatusMessageHandler.showError("Artificial Intelligence is disabled. To enable it, please add your OpenAI Key in the 'Manage Permissions/Users' panel.");
	else {
		var editor = $(".sql_text_area").data("editor");
		var sql = editor.getValue();
		var url = manage_ai_action_url + (manage_ai_action_url.indexOf("?") != -1 ? "" : "?") + "&action=explain_sql";
		
		if (!sql)
			StatusMessageHandler.showMessage("There is no sql to comment...", "", "bottom_messages", 1500);
		else {
			var msg = StatusMessageHandler.showMessage("AI loading. Wait a while...", "", "bottom_messages", 60000);
			
			$.ajax({
				type : "post",
				url : url,
				processData: false,
				contentType: 'text/plain',
				data: sql,
				dataType : "html",
				success : function(message, textStatus, jqXHR) {
					//console.log(message);
					
					msg.remove();
					
					if (message) {
						var new_sql = "-- " + message.replace(/\n/g, "\n-- ") + "\n" + sql;
						editor.setValue(new_sql);
						
						StatusMessageHandler.showMessage("SQL explanation:\n" + message + "\n\nSQL:\n" + sql, "", "", 600000); //1 hour
					}
					else
						StatusMessageHandler.showError("Error: Couldn't process this request with AI. Please try again...");
				},
				error : function(jqXHR, textStatus, errorThrown) {
					msg.remove();
					
					if (jqXHR.responseText)
						StatusMessageHandler.showError(jqXHR.responseText);
				},
			});
		}
	}
}

function openCodeChatBot() {
	if (typeof manage_ai_action_url == "undefined")
		StatusMessageHandler.showError("Manage AI Action url is not defined. Please talk with sysadmin");
	else if (!manage_ai_action_url)
		StatusMessageHandler.showError("Artificial Intelligence is disabled. To enable it, please add your OpenAI Key in the 'Manage Permissions/Users' panel.");
	else {
		var editor = $(".sql_text_area").data("editor");
		
		if (editor)
			showCodeEditorChatBot(editor);
	}
}

function getCodeChatBotSystemMessage(editor) {
	var system_message = getCodeEditorChatBotDefaultSystemMessage(editor) + "\n\n";
	system_message += "Current selected table: `" + table + "`";
	
	if (table_attrs) {
		system_message += ", with following attributes:"
		
		$.each(table_attrs, function(attr_name, attr_props) {
			system_message += "\n- `" + attr_name + "`";
		});
	}
	
	return system_message;
}
/* END: AI */
