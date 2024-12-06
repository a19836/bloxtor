$(function () {
	if ($(".sql_text_area").length > 0)
		createSQLEditor();
});

function createSQLEditor() {
	var sql_text_area = $(".sql_text_area");
	var textarea = sql_text_area.children("textarea")[0];

	ace.require("ace/ext/language_tools");
	var editor = ace.edit(textarea);
	editor.setTheme("ace/theme/chrome");
	editor.session.setMode("ace/mode/sql");
	editor.setAutoScrollEditorIntoView(true);
	editor.setOption("maxLines", "Infinity");
	editor.setOption("minLines", 30);
	editor.setOptions({
		enableBasicAutocompletion: true,
		enableSnippets: true,
		enableLiveAutocompletion: true,
	});
	editor.setOption("wrap", true);
	editor.$blockScrolling = "Infinity";

	if (typeof setCodeEditorAutoCompleter == "function")
		setCodeEditorAutoCompleter(editor);
	
	sql_text_area.find("textarea.ace_text-input").removeClass("ace_text-input"); //fixing problem with scroll up, where when focused or pressed key inside editor the page scrolls to top

	sql_text_area.data("editor", editor);
	
	editor.focus();
}

function execute() {
	if ($(".sql_text_area").length > 0) {
		var editor = $(".sql_text_area").data("editor");
		var sql = editor.getValue();

		$("#main_column").append('<form id="form_sql" method="post" style="display:none"><textarea name="sql"></textarea></form>');
		$("#form_sql textarea").val(sql);
		$("#form_sql")[0].submit();
	}
}
